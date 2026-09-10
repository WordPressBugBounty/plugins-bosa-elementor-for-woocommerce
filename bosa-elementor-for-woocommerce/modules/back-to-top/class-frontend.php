<?php
/**
 * BEW Back to Top — Frontend rendering and asset loading.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Back_To_Top_Frontend {

	private $settings       = null;
	private $settings_loaded = false;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_footer', [ $this, 'render' ] );
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

	public function enqueue_assets() {
		$settings = $this->get_settings();
		if ( ! $settings ) {
			return;
		}

		wp_enqueue_style(
			'bew-back-to-top',
			BEW_URL . 'modules/back-to-top/assets/css/back-to-top.css',
			[],
			BEW_VERSION
		);

		wp_enqueue_script(
			'bew-back-to-top',
			BEW_URL . 'modules/back-to-top/assets/js/back-to-top.js',
			[],
			BEW_VERSION,
			true
		);
	}

	public function render() {
		$settings = $this->get_settings();
		if ( ! $settings ) {
			return;
		}

		$position        = ! empty( $settings['bew_btt_position'] ) ? $settings['bew_btt_position'] : 'right';
		$scroll_offset   = isset( $settings['bew_btt_scroll_offset'] ) ? absint( $settings['bew_btt_scroll_offset'] ) : 300;
		$tooltip         = ! empty( $settings['bew_btt_tooltip'] ) ? $settings['bew_btt_tooltip'] : '';
		$button_text     = ! empty( $settings['bew_btt_text'] ) ? $settings['bew_btt_text'] : '';
		$entrance        = ! empty( $settings['bew_btt_entrance_animation'] ) ? $settings['bew_btt_entrance_animation'] : 'fade-in';

		$icon = ! empty( $settings['bew_btt_icon'] ) ? $settings['bew_btt_icon'] : [
			'value'   => 'fas fa-arrow-up',
			'library' => 'fa-solid',
		];

		$classes   = [ 'bew-btt-button', 'bew-btt-' . $position ];
		if ( $this->is_editor_preview() ) {
			// Hidden by default inside any Elementor editor preview; revealed only
			// while the Site Settings (Kit) panel is open — see site-settings-preview.js.
			$classes[] = 'bew-btt-editor-preview';
		}
		if ( $entrance ) {
			$classes[] = 'bew-btt-entrance-' . $entrance;
		}

		$aria_label = $tooltip ? $tooltip : esc_attr__( 'Scroll to top', 'bosa-elementor-for-woocommerce' );
		?>
		<button
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-scroll-offset="<?php echo esc_attr( $scroll_offset ); ?>"
			aria-label="<?php echo esc_attr( $aria_label ); ?>"
			<?php if ( $tooltip ) : ?>
				title="<?php echo esc_attr( $tooltip ); ?>"
			<?php endif; ?>
			type="button"
		>
			<?php
			if ( ! empty( $icon['value'] ) ) {
				\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
			}
			?>
			<?php if ( $button_text ) : ?>
				<span class="bew-btt-text"><?php echo esc_html( $button_text ); ?></span>
			<?php endif; ?>
		</button>
		<?php
	}
}
