<?php
/**
 * BEW Preloader — Frontend rendering and asset loading.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Preloader_Frontend {

	private $settings       = null;
	private $settings_loaded = false;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_body_open', [ $this, 'render' ] );
	}

	private function get_settings() {
		if ( $this->settings_loaded ) {
			return $this->settings;
		}
		$this->settings_loaded = true;

		if ( ! did_action( 'elementor/loaded' ) ) {
			return null;
		}

		$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
		if ( ! $kit ) {
			return null;
		}

		$settings = $kit->get_settings();

		$this->settings = $settings;
		return $this->settings;
	}

	/**
	 * Whether the current request is any Elementor editor preview iframe.
	 *
	 * Note: Elementor always loads the preview iframe against whatever page/post
	 * document is active — opening the Site Settings panel does NOT navigate the
	 * iframe to the Kit's own preview, so this cannot distinguish "Site Settings is
	 * open" from "a normal page is being edited" by URL alone. That distinction is
	 * handled client-side; see assets/js/site-settings-preview.js.
	 */
	private function is_editor_preview() {
		// Presence-only check (Elementor's own convention for its preview
		// iframe) -- the value is never read, so there's nothing here that
		// needs a nonce.
		return isset( $_GET['elementor-preview'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Cache-busting version based on file mtime, so edits to this module's own
	 * assets always bypass the browser cache without needing a plugin-wide
	 * BEW_VERSION bump.
	 *
	 * @param string $rel_path Path relative to the plugin root.
	 * @return string
	 */
	private function asset_version( $rel_path ) {
		$full_path = BEW_PATH . $rel_path;
		return file_exists( $full_path ) ? (string) filemtime( $full_path ) : BEW_VERSION;
	}

	public function enqueue_assets() {
		$settings = $this->get_settings();
		if ( ! $settings ) {
			return;
		}

		wp_enqueue_style(
			'bew-preloader',
			BEW_URL . 'modules/preloader/assets/css/preloader.css',
			[],
			$this->asset_version( 'modules/preloader/assets/css/preloader.css' )
		);

		$fade = isset( $settings['bew_preloader_fade_duration'] ) ? absint( $settings['bew_preloader_fade_duration'] ) : 400;

		wp_enqueue_script(
			'bew-preloader',
			BEW_URL . 'modules/preloader/assets/js/preloader.js',
			[],
			$this->asset_version( 'modules/preloader/assets/js/preloader.js' ),
			true
		);

		wp_localize_script( 'bew-preloader', 'bewPreloaderData', [
			'fadeDuration'    => $fade,
			// In editor preview the overlay must stay put for editing — never
			// auto-hide it the way a real page load would.
			'isEditorPreview' => $this->is_editor_preview(),
		] );
	}

	public function render() {
		$settings = $this->get_settings();
		if ( ! $settings ) {
			return;
		}

		$source = ! empty( $settings['bew_preloader_source'] ) ? $settings['bew_preloader_source'] : 'builtin';

		if ( 'custom' === $source ) {
			$image_url = ! empty( $settings['bew_preloader_custom_image']['url'] )
				? $settings['bew_preloader_custom_image']['url']
				: '';
			if ( ! $image_url ) {
				return;
			}
		} else {
			$builtin = ! empty( $settings['bew_preloader_builtin'] ) ? $settings['bew_preloader_builtin'] : 'preloader1';
			if ( ! preg_match( '/^preloader([1-9]|10)$/', $builtin ) ) {
				$builtin = 'preloader1';
			}
			$image_url = BEW_URL . 'modules/preloader/assets/images/' . $builtin . '.gif';
		}

		$classes = [ 'bew-preloader-overlay' ];
		if ( $this->is_editor_preview() ) {
			// Hidden by default inside any Elementor editor preview; revealed only
			// while the Site Settings (Kit) panel is open — see site-settings-preview.js.
			$classes[] = 'bew-preloader-editor-preview';
		}
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-hidden="true">
			<img class="bew-preloader-image" src="<?php echo esc_url( $image_url ); ?>" alt="">
		</div>
		<?php
	}
}
