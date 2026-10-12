<?php
/**
 * Bosa theme recommendation notice.
 *
 * Suggests the free Bosa theme on Dashboard, Plugins and Themes when no
 * Bosa-family theme is active. Mirrors the notice in Bosa Starter Sites;
 * BEW_RECOMMENDS_BOSA_THEME (defined in the main plugin file) makes Starter
 * Sites stay silent while BEW is active, so a site only ever sees one of them.
 *
 * It snoozes rather than dismisses for good: "Maybe later" hides it for 7
 * days and the X for 30 -- the same rule as BEW's rating and Pro notices. No auto-install, no external requests and no
 * tracking -- the button hands off to WordPress's own theme installer or
 * activation action.
 *
 * @package BEW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BEW_Theme_Notice' ) ) {

	class BEW_Theme_Notice {

		const SLUG         = 'bosa';
		const DISMISS_META = 'bew_theme_notice_dismissed'; // User meta: unix time it's hidden until (1 = hidden for good).
		const LEARN_MORE   = 'https://bosathemes.com/bosa/';
		const LATER_DAYS   = 7;
		const CLOSE_DAYS   = 30;

		/**
		 * Set once the notice has actually printed, so the script is only added
		 * to pages that show it.
		 *
		 * @var bool
		 */
		private static $print_script = false;

		public static function init() {
			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
			add_action( 'admin_footer', array( __CLASS__, 'print_script' ) );
			add_action( 'wp_ajax_bew_theme_notice_snooze', array( __CLASS__, 'snooze' ) );
		}

		/**
		 * Bosa family = the `bosa` theme or any `bosa-*` slug (bosa-pro, bosa-agency, ...).
		 */
		private static function is_family_slug( $slug ) {
			return self::SLUG === $slug || 0 === strpos( (string) $slug, self::SLUG . '-' );
		}

		/**
		 * Checked on both template and stylesheet so child themes count through their parent.
		 */
		private static function is_family_active() {
			return self::is_family_slug( get_template() ) || self::is_family_slug( get_stylesheet() );
		}

		/**
		 * Slug of an installed (inactive) Bosa-family theme to offer for activation, or ''.
		 * The free `bosa` theme is preferred.
		 */
		private static function installed_family_theme() {
			$themes = wp_get_themes();
			if ( isset( $themes[ self::SLUG ] ) ) {
				return self::SLUG;
			}
			foreach ( array_keys( $themes ) as $slug ) {
				if ( self::is_family_slug( $slug ) ) {
					return $slug;
				}
			}
			return '';
		}

		private static function is_snoozed() {
			$until = (int) get_user_meta( get_current_user_id(), self::DISMISS_META, true );

			return 1 === $until || $until > time();
		}

		/**
		 * Cheapest checks first -- the theme list is only read once we know we'll print.
		 */
		private static function should_display() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || ! in_array( $screen->id, array( 'dashboard', 'plugins', 'themes' ), true ) ) {
				return false;
			}

			if ( ! current_user_can( 'switch_themes' ) || self::is_family_active() ) {
				return false;
			}

			return ! self::is_snoozed();
		}

		public static function render() {
			if ( ! self::should_display() ) {
				return;
			}

			self::$print_script = true;

			$installed = self::installed_family_theme();
			?>
			<div class="notice notice-info is-dismissible bew-theme-notice">
				<button type="button" class="button-link bew-theme-notice-later"><?php esc_html_e( 'Maybe later', 'bosa-elementor-for-woocommerce' ); ?></button>
				<h3><?php esc_html_e( 'Get the most from BEW with the Bosa theme.', 'bosa-elementor-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'Bosa is a free, fast Elementor and WooCommerce theme that pairs with BEW, so its widgets and templates look and work as designed.', 'bosa-elementor-for-woocommerce' ); ?></p>
				<p class="bew-theme-notice-actions">
					<?php if ( $installed ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'themes.php?action=activate&stylesheet=' . rawurlencode( $installed ) ), 'switch-theme_' . $installed ) ); ?>" class="button button-primary"><?php esc_html_e( 'Activate Bosa', 'bosa-elementor-for-woocommerce' ); ?></a>
					<?php elseif ( current_user_can( 'install_themes' ) ) : ?>
						<a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-theme&theme=' . self::SLUG ), 'install-theme_' . self::SLUG ) ); ?>" class="button button-primary"><?php esc_html_e( 'Install Bosa', 'bosa-elementor-for-woocommerce' ); ?></a>
					<?php endif; ?>
					<a href="<?php echo esc_url( self::LEARN_MORE ); ?>" class="button" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn More', 'bosa-elementor-for-woocommerce' ); ?></a>
				</p>
			</div>
			<?php
		}

		/**
		 * "Maybe later" snoozes for 7 days; the X (WordPress fades the notice out
		 * itself) for 30. Both just tell the server how long to stay quiet.
		 */
		public static function print_script() {
			if ( ! self::$print_script ) {
				return;
			}

			$config = wp_json_encode(
				array(
					'url'    => admin_url( 'admin-ajax.php' ),
					'action' => 'bew_theme_notice_snooze',
					'nonce'  => wp_create_nonce( 'bew_theme_notice_snooze' ),
				)
			);

			wp_print_inline_script_tag(
				'( function () {
					var config = ' . $config . ';

					function snooze( mode ) {
						fetch( config.url, {
							method: "POST",
							credentials: "same-origin",
							body: new URLSearchParams( { action: config.action, nonce: config.nonce, mode: mode } )
						} );
					}

					document.addEventListener( "click", function ( event ) {
						if ( ! event.target.closest ) {
							return;
						}
						if ( event.target.closest( ".bew-theme-notice .notice-dismiss" ) ) {
							snooze( "close" );
							return;
						}
						var later = event.target.closest( ".bew-theme-notice .bew-theme-notice-later" );
						if ( later ) {
							snooze( "later" );
							later.closest( ".bew-theme-notice" ).remove();
						}
					} );
				} )();'
			);
		}

		public static function snooze() {
			check_ajax_referer( 'bew_theme_notice_snooze', 'nonce' );

			if ( ! current_user_can( 'switch_themes' ) ) {
				wp_send_json_error( null, 403 );
			}

			$days = array(
				'later' => self::LATER_DAYS,
				'close' => self::CLOSE_DAYS,
			);
			$mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : '';
			if ( ! isset( $days[ $mode ] ) ) {
				wp_send_json_error( null, 400 );
			}

			update_user_meta( get_current_user_id(), self::DISMISS_META, time() + $days[ $mode ] * DAY_IN_SECONDS );
			wp_send_json_success();
		}
	}

	BEW_Theme_Notice::init();
}
