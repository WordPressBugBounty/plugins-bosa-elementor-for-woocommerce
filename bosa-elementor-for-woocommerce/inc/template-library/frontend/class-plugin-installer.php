<?php
/**
 * In-editor plugin installation handler.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin installation and activation via AJAX without leaving the editor.
 */
class Bosa_Ewc_Plugin_Installer {

	/**
	 * AJAX: install and activate a plugin from WordPress.org.
	 *
	 * Expects POST fields: plugin_slug, nonce.
	 *
	 * @return void
	 */
	public function handle_plugin_installation() {
		$slug  = isset( $_POST['plugin_slug'] ) ? sanitize_key( wp_unslash( $_POST['plugin_slug'] ) ) : '';
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! $slug || ! wp_verify_nonce( $nonce, 'bosa_install_' . $slug ) ) {
			wp_send_json_error( __( 'Security check failed.', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'You do not have permission to install plugins.', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( ! $this->is_slug_allowed( $slug ) ) {
			wp_send_json_error( __( 'This plugin is not in the current template dependency list.', 'bosa-elementor-for-woocommerce' ) );
		}

		$result = $this->install_and_activate( $slug );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( [
			'message' => sprintf(
				/* translators: %s: Plugin name. */
				__( '%s installed and activated successfully.', 'bosa-elementor-for-woocommerce' ),
				$result['name']
			),
			'plugin'  => $result,
		] );
	}

	/**
	 * @param string $slug WordPress.org plugin slug.
	 * @return array{slug:string, name:string, status:string}|WP_Error
	 */
	public function install_plugin_by_slug( $slug ) {
		$slug = sanitize_key( $slug );

		if ( ! $slug ) {
			return new WP_Error( 'bew_tl_invalid_slug', __( 'Invalid plugin slug.', 'bosa-elementor-for-woocommerce' ) );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			return new WP_Error(
				'bew_tl_forbidden',
				__( 'You do not have permission to install plugins.', 'bosa-elementor-for-woocommerce' ),
				[ 'status' => 403 ]
			);
		}

		if ( ! $this->is_slug_allowed( $slug ) ) {
			return new WP_Error(
				'bew_tl_slug_not_allowed',
				__( 'This plugin is not in the current template dependency list.', 'bosa-elementor-for-woocommerce' ),
				[ 'status' => 403 ]
			);
		}

		return $this->install_and_activate( $slug );
	}

	/**
	 * @param string $slug
	 * @return bool
	 */
	private function is_slug_allowed( $slug ) {
		$missing = get_transient( Bosa_Ewc_Editor_Prompt::TRANSIENT_KEY );
		if ( is_array( $missing ) ) {
			foreach ( $missing as $plugin ) {
				// A synthetic "unknown_*" entry (no real plugin identified for
				// the widget) stores an empty plugin_slug -- never installable,
				// even though it's present in this same transient under its
				// own synthesized key.
				if ( ! is_array( $plugin ) || empty( $plugin['plugin_slug'] ) ) {
					continue;
				}
				if ( sanitize_key( $plugin['plugin_slug'] ) === $slug ) {
					return true;
				}
			}
		}

		return in_array( $slug, Bosa_Ewc_Widget_Plugin_Map::get_known_plugin_slugs(), true );
	}

	/**
	 * @param string $slug
	 * @return array{slug:string, name:string, status:string}|WP_Error
	 */
	private function install_and_activate( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$plugin_file = $this->find_plugin_file( $slug );

		if ( $plugin_file && is_plugin_active( $plugin_file ) ) {
			return [
				'slug'   => $slug,
				'name'   => get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file )['Name'] ?? $slug,
				'status' => 'already_active',
			];
		}

		if ( $plugin_file && ! is_plugin_active( $plugin_file ) ) {
			$activated = activate_plugin( $plugin_file, '', false, true );
			if ( is_wp_error( $activated ) ) {
				return $activated;
			}
			return [
				'slug'   => $slug,
				'name'   => get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file )['Name'] ?? $slug,
				'status' => 'activated',
			];
		}

		$api = plugins_api( 'plugin_information', [ 'slug' => $slug ] );

		if ( is_wp_error( $api ) ) {
			return new WP_Error(
				'bew_tl_plugin_not_found',
				/* translators: %s: Plugin slug. */
				sprintf( __( 'Plugin "%s" not found on WordPress.org.', 'bosa-elementor-for-woocommerce' ), $slug )
			);
		}

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error( 'bew_tl_install_failed', __( 'Plugin installation failed.', 'bosa-elementor-for-woocommerce' ) );
		}

		$plugin_file = $this->find_plugin_file( $slug );

		if ( ! $plugin_file ) {
			return new WP_Error( 'bew_tl_file_not_found', __( 'Plugin file not found after installation.', 'bosa-elementor-for-woocommerce' ) );
		}

		$activated = activate_plugin( $plugin_file, '', false, true );
		if ( is_wp_error( $activated ) ) {
			return $activated;
		}

		return [
			'slug'   => $slug,
			'name'   => $api->name,
			'status' => 'installed_and_activated',
		];
	}

	/**
	 * @param string $slug
	 * @return string|null
	 */
	private function find_plugin_file( $slug ) {
		foreach ( get_plugins() as $path => $data ) {
			if ( strpos( $path, $slug . '/' ) === 0 || $path === $slug . '.php' ) {
				return $path;
			}
		}
		return null;
	}
}
