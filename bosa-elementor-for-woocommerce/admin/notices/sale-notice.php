<?php
/**
 * Time-limited sale notice.
 *
 * Shows between START and END (UTC) to admins who don't have BEW Pro, then
 * switches itself off -- no cron or cleanup needed. While a sale is running,
 * the regular Pro upgrade notice (pro-notice.php) steps aside via
 * BEW_Sale_Notice::is_active().
 *
 * The countdown keeps this within the WordPress.org plugin guidelines (7, 9,
 * 11): it counts down to the real END time -- never a per-visit or "evergreen"
 * timer -- uses no external scripts/images/fonts, and stays inside a notice
 * that is dismissible, time-limited and shown on two screens only. Keep it
 * that way when editing.
 *
 * This is BEW's own notice, for BEW Pro. Bosa Starter Sites runs a separate
 * one for Bosa Pro and the Bosa theme runs none, so the two plugin notices are
 * independent: both can show on the same site, and dismissing one doesn't
 * touch the other (DISMISS_KEY is BEW-only).
 *
 * New campaign: change DISMISS_KEY, START, END, URL and the copy in render().
 * Retire it: delete this file, its require_once in
 * bosa-elementor-for-woocommerce.php, the is_active() guard in
 * Bew_Upgrade_Notice::check_pro_install(), and the "bew sale notice" block
 * in admin/notices/css/bew-admin-notice.css.
 *
 * @package BEW
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BEW_Sale_Notice' ) ) {

	class BEW_Sale_Notice {

		// Change these for each campaign.
		const DISMISS_KEY = 'bew_sale_dismissed_bfcm_2026'; // User meta; a new key resets everyone's dismissal.
		const START    = '2026-11-24 00:00:00'; // UTC.
		const END      = '2026-12-02 23:59:59'; // UTC.
		const URL      = 'https://bosathemes.com/black-friday/?product=bew-pro&utm_source=bew-plugin&utm_medium=notice&utm_campaign=bfcm_2026'; // product= lets the page highlight BEW Pro.

		// Same audience as the regular Pro notice this one temporarily replaces.
		const CAPABILITY = 'activate_plugins';
		const PRO_PLUGIN = 'bosa-elementor-for-woocommerce-pro/bosa-elementor-for-woocommerce-pro.php';

		/**
		 * Set once the notice has actually printed, so the dismiss script is
		 * only added to pages that show it.
		 *
		 * @var bool
		 */
		private static $print_script = false;

		public static function init() {
			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
			add_action( 'admin_footer', array( __CLASS__, 'print_script' ) );
			add_action( 'wp_ajax_bew_dismiss_sale_notice', array( __CLASS__, 'dismiss' ) );
		}

		/**
		 * Whether the campaign is running right now.
		 * Public so other BEW notices can step aside during the sale.
		 */
		public static function is_active() {
			$now = time();
			return $now >= strtotime( self::START . ' UTC' ) && $now <= strtotime( self::END . ' UTC' );
		}

		/**
		 * Dashboard and Plugins only. BEW's own admin pages are deliberately
		 * absent: BEW_Admin::suppress_foreign_admin_notices() strips every
		 * admin_notices callback on them, so a notice could never print there.
		 */
		private static function is_allowed_screen() {
			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

			return $screen && in_array( $screen->id, array( 'dashboard', 'plugins' ), true );
		}

		/**
		 * Pro active, or installed but inactive. Mirrors the regular Pro
		 * notice, which also stays quiet for anyone who already has Pro.
		 */
		private static function has_pro() {
			if ( class_exists( 'BEW_Pro' ) ) {
				return true;
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			return isset( get_plugins()[ self::PRO_PLUGIN ] );
		}

		/**
		 * Cheapest checks first -- has_pro() reads the plugin headers.
		 */
		private static function should_display() {
			if ( ! self::is_active() || ! current_user_can( self::CAPABILITY ) || ! self::is_allowed_screen() ) {
				return false;
			}

			if ( get_user_meta( get_current_user_id(), self::DISMISS_KEY, true ) ) {
				return false;
			}

			return ! self::has_pro();
		}

		public static function render() {
			if ( ! self::should_display() ) {
				return;
			}

			self::$print_script = true;

			$end_time  = strtotime( self::END . ' UTC' );
			$remaining = max( 0, $end_time - time() );
			// Non-breaking space so "December 2" can't wrap onto two lines.
			$ends = str_replace( ' ', "\u{00A0}", wp_date( 'F j', $end_time, new DateTimeZone( 'UTC' ) ) );
			?>
			<div class="notice notice-info is-dismissible bew-sale-notice">
				<?php // The purple PNG tile, not svg/bew-logo.svg: that one is a white-only admin-menu icon and vanishes on a light background. ?>
				<img src="<?php echo esc_url( BEW_URL . 'admin/assets/images/bew-logo.png' ); ?>" alt="" width="48" height="48" />
				<div>
					<p><strong><?php esc_html_e( 'Black Friday Sale: 40% off BEW Pro', 'bosa-elementor-for-woocommerce' ); ?></strong></p>
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: sale end date, e.g. "December 2". */
								__( 'Unlock 3,000+ templates, Pro WooCommerce widgets, and AI content generation. Ends %s.', 'bosa-elementor-for-woocommerce' ),
								$ends
							)
						);
						?>
					</p>
					<p class="bew-sale-cta">
						<a href="<?php echo esc_url( self::URL ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Get the deal', 'bosa-elementor-for-woocommerce' ); ?>
						</a>
						<?php // Stays hidden until the script starts it, so without JS only the static "Ends …" sentence above shows. ?>
						<span class="bew-sale-countdown" role="timer" data-remaining="<?php echo esc_attr( $remaining ); ?>" hidden></span>
					</p>
				</div>
			</div>
			<?php
		}

		/**
		 * Two jobs, both local (no external requests): the X in the notice's
		 * corner dismisses it for the rest of the campaign (WordPress already
		 * fades it out; this makes it stay hidden), and the countdown ticks
		 * down to END. The countdown starts from the server's own "seconds
		 * left" rather than the visitor's clock, and removes the notice when it
		 * reaches zero.
		 */
		public static function print_script() {
			if ( ! self::$print_script ) {
				return;
			}

			$config = wp_json_encode(
				array(
					'url'    => admin_url( 'admin-ajax.php' ),
					'action' => 'bew_dismiss_sale_notice',
					'nonce'  => wp_create_nonce( 'bew_dismiss_sale_notice' ),
					'units'  => array(
						/* translators: Abbreviation for "days" in the sale countdown, e.g. the "d" in "5d 14h". */
						'd' => _x( 'd', 'countdown days', 'bosa-elementor-for-woocommerce' ),
						/* translators: Abbreviation for "hours" in the sale countdown, e.g. the "h" in "5d 14h". */
						'h' => _x( 'h', 'countdown hours', 'bosa-elementor-for-woocommerce' ),
						/* translators: Abbreviation for "minutes" in the sale countdown, e.g. the "m" in "32m 10s". */
						'm' => _x( 'm', 'countdown minutes', 'bosa-elementor-for-woocommerce' ),
						/* translators: Abbreviation for "seconds" in the sale countdown, e.g. the "s" in "32m 10s". */
						's' => _x( 's', 'countdown seconds', 'bosa-elementor-for-woocommerce' ),
					),
					/* translators: %s: time left in the sale, e.g. "5d 14h 32m 10s". */
					'format' => __( '%s left', 'bosa-elementor-for-woocommerce' ),
				)
			);

			wp_print_inline_script_tag(
				'( function () {
					var config = ' . $config . ';

					document.addEventListener( "click", function ( event ) {
						if ( ! event.target.closest || ! event.target.closest( ".bew-sale-notice .notice-dismiss" ) ) {
							return;
						}
						fetch( config.url, {
							method: "POST",
							credentials: "same-origin",
							body: new URLSearchParams( { action: config.action, nonce: config.nonce } )
						} );
					} );

					var el = document.querySelector( ".bew-sale-notice .bew-sale-countdown" );
					if ( ! el ) {
						return;
					}

					var end = Date.now() + parseInt( el.getAttribute( "data-remaining" ), 10 ) * 1000;

					function format( left ) {
						var d = Math.floor( left / 86400 ),
							h = Math.floor( left % 86400 / 3600 ),
							m = Math.floor( left % 3600 / 60 ),
							parts = [];
						if ( d ) { parts.push( d + config.units.d ); }
						if ( d || h ) { parts.push( h + config.units.h ); }
						if ( d || h || m ) { parts.push( m + config.units.m ); }
						parts.push( left % 60 + config.units.s );
						return config.format.replace( "%s", parts.join( " " ) );
					}

					function tick() {
						if ( ! el.isConnected ) {
							clearInterval( timer );
							return;
						}
						var left = Math.max( 0, Math.ceil( ( end - Date.now() ) / 1000 ) );
						if ( ! left ) {
							clearInterval( timer );
							el.closest( ".bew-sale-notice" ).remove();
							return;
						}
						el.textContent = format( left );
						el.hidden = false;
					}

					var timer = setInterval( tick, 1000 );
					tick();
				} )();'
			);
		}

		public static function dismiss() {
			check_ajax_referer( 'bew_dismiss_sale_notice', 'nonce' );

			if ( ! current_user_can( self::CAPABILITY ) ) {
				wp_send_json_error( null, 403 );
			}

			update_user_meta( get_current_user_id(), self::DISMISS_KEY, 1 );
			wp_send_json_success();
		}
	}

	BEW_Sale_Notice::init();
}
