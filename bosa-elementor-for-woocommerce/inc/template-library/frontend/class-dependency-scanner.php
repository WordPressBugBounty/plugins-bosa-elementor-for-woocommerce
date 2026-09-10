<?php
/**
 * Template dependency scanner.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scans Elementor template data for third-party widget dependencies.
 */
class Bosa_Ewc_Dependency_Scanner {

	/** @var Bosa_Ewc_Widget_Plugin_Map */
	private $plugin_map;

	/**
	 * @param Bosa_Ewc_Widget_Plugin_Map|null $plugin_map
	 */
	public function __construct( $plugin_map = null ) {
		$this->plugin_map = $plugin_map instanceof Bosa_Ewc_Widget_Plugin_Map
			? $plugin_map
			: new Bosa_Ewc_Widget_Plugin_Map();
	}

	/**
	 * Scan template content and return structured dependency data.
	 *
	 * @param array $template_content Decoded Elementor elements array.
	 * @return array{
	 *     all: array<string, array{widget_type:string, widget_label:string, plugin_slug:string|null, plugin_name:string|null, is_active:bool}>,
	 *     missing: array<string, array{slug:string, name:string|null, description:string, widgets:string[], install_url:string}>
	 * }
	 */
	public function scan( array $template_content ) {
		$widget_types = [];
		$this->collect_widgets( $template_content, $widget_types );

		$detected = $this->plugin_map->detect( $widget_types );

		$missing = [];
		foreach ( $detected as $type => $info ) {
			if ( $info['is_active'] ) {
				continue;
			}

			$plugin_slug = $info['plugin_slug'] ?? null;
			$plugin_name = $info['plugin_name'] ?? null;

			if ( empty( $plugin_slug ) ) {
				$hint = $this->plugin_map->lookup_inactive_plugin( $type );
				if ( $hint ) {
					$plugin_slug = $hint['slug'];
					$plugin_name = $hint['name'];
				}
			}

			if ( $plugin_slug && $this->is_plugin_slug_active( $plugin_slug ) ) {
				continue;
			}

			$slug = $plugin_slug ? sanitize_key( $plugin_slug ) : ( 'unknown_' . sanitize_key( $type ) );

			if ( ! isset( $missing[ $slug ] ) ) {
				$display_name = $plugin_name ?: ( $plugin_slug ? sanitize_key( $plugin_slug ) : null );
				$missing[ $slug ] = [
					'slug'        => $slug,
					'name'        => $display_name,
					'plugin_slug' => $plugin_slug,
					'plugin_name' => $plugin_name,
					'description' => '',
					'widgets'     => [],
					'install_url' => ( $plugin_slug && 0 !== strpos( $slug, 'unknown_' ) )
						? $this->get_plugin_install_url( $plugin_slug )
						: '',
				];
			}

			$label = ( $info['widget_label'] && $info['widget_label'] !== $type ) ? $info['widget_label'] : $type;
			$missing[ $slug ]['widgets'][]   = $label;
			$missing[ $slug ]['widgets']     = array_unique( $missing[ $slug ]['widgets'] );
			$missing[ $slug ]['description'] = sprintf(
				/* translators: %s: comma-separated widget names */
				__( 'Required by: %s', 'bosa-elementor-for-woocommerce' ),
				implode( ', ', $missing[ $slug ]['widgets'] )
			);
		}

		return [ 'all' => $detected, 'missing' => $missing ];
	}

	/**
	 * @param array $template_content
	 * @return array
	 */
	public function scan_for_required_plugins( array $template_content ) {
		return $this->scan( $template_content )['missing'];
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * @param array  $elements
	 * @param array &$widgets
	 * @return void
	 */
	private function collect_widgets( array $elements, array &$widgets ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( isset( $element['widgetType'] ) ) {
				$type = (string) $element['widgetType'];
				if ( ! in_array( $type, $widgets, true ) ) {
					$widgets[] = $type;
				}
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->collect_widgets( $element['elements'], $widgets );
			}
		}
	}

	/**
	 * @param string $slug
	 * @return bool
	 */
	private function is_plugin_slug_active( $slug ) {
		$slug = sanitize_key( $slug );
		if ( ! $slug ) {
			return false;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $path => $data ) {
			if ( strpos( $path, $slug . '/' ) !== 0 && $path !== $slug . '.php' ) {
				continue;
			}
			return is_plugin_active( $path );
		}
		return false;
	}

	/**
	 * @param string $slug
	 * @return string
	 */
	private function get_plugin_install_url( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $path => $data ) {
			if ( strpos( $path, $slug . '/' ) === 0 || $path === $slug . '.php' ) {
				if ( ! is_plugin_active( $path ) ) {
					return wp_nonce_url(
						admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $path ) ),
						'activate-plugin_' . $path
					);
				}
			}
		}
		return wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=' . urlencode( $slug ) ),
			'install-plugin_' . $slug
		);
	}
}
