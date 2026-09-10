<?php
/**
 * Editor prompt for missing plugin dependencies.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a native-style Elementor panel notice whenever a recently imported
 * template requires plugins that are not yet active.
 */
class Bosa_Ewc_Editor_Prompt {

	/** Transient key used to carry missing-plugin data into the editor. */
	const TRANSIENT_KEY = 'bew_tl_missing_plugins';

	/**
	 * @return void
	 */
	public function run() {
		add_action( 'elementor/editor/footer', [ $this, 'render_prompt' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Store missing plugins so the editor footer can pick them up.
	 *
	 * @param array $missing_plugins
	 * @return void
	 */
	public static function store_missing_plugins( array $missing_plugins ) {
		if ( ! empty( $missing_plugins ) ) {
			set_transient( self::TRANSIENT_KEY, $missing_plugins, 5 * MINUTE_IN_SECONDS );
		}
	}

	/**
	 * @return void
	 */
	public function enqueue_assets() {
		$css_path = BEW_TL_DIR . 'assets/css/editor-prompt.css';
		$js_path  = BEW_TL_DIR . 'assets/js/plugin-installer.js';

		wp_enqueue_style(
			'bew-tl-plugin-prompt',
			BEW_TL_URL . 'assets/css/editor-prompt.css',
			[],
			file_exists( $css_path ) ? (string) filemtime( $css_path ) : BEW_VERSION
		);

		wp_enqueue_script(
			'bew-tl-plugin-installer',
			BEW_TL_URL . 'assets/js/plugin-installer.js',
			[ 'jquery' ],
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : BEW_VERSION,
			true
		);

		wp_localize_script(
			'bew-tl-plugin-installer',
			'BEWTLPluginInstaller',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => [
					'installing'  => __( 'Installing…', 'bosa-elementor-for-woocommerce' ),
					'installed'   => __( 'Installed ✓', 'bosa-elementor-for-woocommerce' ),
					'install'     => __( 'Install & Activate', 'bosa-elementor-for-woocommerce' ),
					'title'       => __( 'Required Plugins Missing', 'bosa-elementor-for-woocommerce' ),
					'description' => __( 'This template uses widgets from third-party plugins. Install them to display the full design.', 'bosa-elementor-for-woocommerce' ),
				],
			]
		);
	}

	/**
	 * Output the missing-plugins prompt HTML in the Elementor editor footer.
	 *
	 * @return void
	 */
	public function render_prompt() {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$missing     = get_transient( self::TRANSIENT_KEY );
		$has_missing = ! empty( $missing ) && is_array( $missing );

		if ( $has_missing ) {
			delete_transient( self::TRANSIENT_KEY );
		}

		$style = $has_missing ? '' : ' style="display:none"';
		echo '<div id="bew-template-library-missing-plugins" class="bew-template-library-plugin-prompt"' . $style . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $has_missing ) {
			echo '<div class="bew-plugin-notice elementor-message elementor-message-warning">';
			echo '<div class="elementor-message-icon"><i class="eicon-info-circle"></i></div>';
			echo '<div class="elementor-message-content">';
			echo '<h3>' . esc_html__( 'Required Plugins Missing', 'bosa-elementor-for-woocommerce' ) . '</h3>';
			echo '<p>' . esc_html__( 'This template uses widgets from third-party plugins. Install them to display the full design.', 'bosa-elementor-for-woocommerce' ) . '</p>';
			echo '<div class="bew-plugins-list">';

			foreach ( $missing as $slug => $plugin ) {
				echo $this->render_plugin_item( $slug, $plugin ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * @param string $slug   Plugin slug.
	 * @param array  $plugin Plugin metadata.
	 * @return string
	 */
	private function render_plugin_item( $slug, array $plugin ) {
		$nonce  = wp_create_nonce( 'bosa_install_' . $slug );
		$html   = '<div class="bew-plugin-item" data-plugin-slug="' . esc_attr( $slug ) . '">';
		$html  .= '<div class="bew-plugin-info">';
		$html  .= '<h4>' . esc_html( $plugin['name'] ) . '</h4>';
		$html  .= '<p>' . esc_html( $plugin['description'] ) . '</p>';
		$html  .= '</div>';
		$html  .= '<div class="bew-plugin-action">';
		$html  .= '<button type="button" class="button button-primary bew-install-plugin"'
			. ' data-slug="' . esc_attr( $slug ) . '"'
			. ' data-nonce="' . esc_attr( $nonce ) . '">';
		$html  .= esc_html__( 'Install & Activate', 'bosa-elementor-for-woocommerce' );
		$html  .= '</button>';
		$html  .= '</div>';
		$html  .= '</div>';
		return $html;
	}
}
