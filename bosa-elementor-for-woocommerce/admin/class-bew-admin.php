<?php
/**
 * BEW Admin Menu Manager
 *
 * Registers the unified BEW admin menu (BEW Info, Settings, Our Products).
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_Admin {

	const MENU_SLUG   = 'bew-dashboard';
	const CAPABILITY  = 'manage_options';
	const PRICING_URL = 'https://bew.bosathemes.com/pricing';

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 15 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_footer', [ $this, 'print_upgrade_link_script' ] );
		add_action( 'admin_head', [ $this, 'suppress_foreign_admin_notices' ] );
	}

	/**
	 * Other plugins'/themes' admin notices break the page flow on BEW's own
	 * admin screens (banner, tabs, etc. expect to be the first thing in the
	 * page). Drops every admin_notices/all_admin_notices callback -- including
	 * BEW's own, if any get added there -- before they have a chance to print,
	 * scoped to screens whose id contains "bew" only.
	 */
	public function suppress_foreign_admin_notices() {
		$screen = get_current_screen();

		if ( $screen && false !== strpos( $screen->id, 'bew' ) ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	}

	/**
	 * Position 59: just before Appearance (60).
	 */
	public function register_menu() {
		add_menu_page(
			__( 'BEW', 'bosa-elementor-for-woocommerce' ),
			__( 'BEW', 'bosa-elementor-for-woocommerce' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			[ $this, 'render_info_page' ],
			BEW_URL . 'admin/assets/svg/bew-logo.svg',
			59
		);

		// Submenu 1: BEW Info (mirrors top-level for consistency)
		add_submenu_page(
			self::MENU_SLUG,
			__( 'BEW Info', 'bosa-elementor-for-woocommerce' ),
			__( 'BEW Info', 'bosa-elementor-for-woocommerce' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			[ $this, 'render_info_page' ]
		);

		// Submenu 2: Settings (Templates tab has the Elementor modal toggle)
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'bosa-elementor-for-woocommerce' ),
			__( 'Settings', 'bosa-elementor-for-woocommerce' ),
			self::CAPABILITY,
			'bew-settings',
			[ $this, 'render_settings_page' ]
		);

		// Submenu 3: Upgrade to BEW Pro (external link -- hidden once Pro is active).
		// A full URL as the menu slug makes WordPress render this as a plain link
		// to that address instead of an internal admin.php?page= route. Opens in a
		// new tab -- see print_upgrade_link_script(), since add_submenu_page() has
		// no target parameter of its own.
		if ( ! class_exists( 'BEW_Pro' ) ) {
			add_submenu_page(
				self::MENU_SLUG,
				__( 'Upgrade to BEW Pro', 'bosa-elementor-for-woocommerce' ),
				'<span style="color:#8382f3">' . esc_html__( 'Upgrade to BEW Pro', 'bosa-elementor-for-woocommerce' ) . '</span>',
				self::CAPABILITY,
				self::PRICING_URL
			);
		}

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Our Products', 'bosa-elementor-for-woocommerce' ),
			__( 'Our Products', 'bosa-elementor-for-woocommerce' ),
			self::CAPABILITY,
			'bew-products',
			[ $this, 'render_products_page' ]
		);

		// No Templates submenu — templates are accessed exclusively from the Elementor editor.
	}

	/**
	 * The "Upgrade to BEW Pro" submenu is a plain admin-menu link
	 * (add_submenu_page() has no target/rel parameters of its own), so
	 * opening it in a new tab needs a tiny script instead -- printed
	 * unconditionally in admin_footer since the admin menu itself renders on
	 * every wp-admin page, not just BEW's own. No-ops harmlessly once Pro is
	 * active and the link no longer exists in the DOM.
	 */
	public function print_upgrade_link_script() {
		if ( class_exists( 'BEW_Pro' ) ) {
			return;
		}
		?>
		<script>
		( function () {
			var link = document.querySelector( '#adminmenu a[href="<?php echo esc_js( self::PRICING_URL ); ?>"]' );
			if ( link ) {
				link.target = '_blank';
				link.rel = 'noopener noreferrer';
			}
		} )();
		</script>
		<?php
	}

	/**
	 * Enqueue admin CSS and JS — only on BEW pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_assets( $hook_suffix ) {
		$is_bew_page = (
			strpos( $hook_suffix, 'bew-' ) !== false ||
			'toplevel_page_bew-dashboard' === $hook_suffix
		);

		if ( ! $is_bew_page ) {
			return;
		}

		wp_enqueue_style(
			'bew-admin-settings',
			BEW_URL . 'admin/assets/css/admin-settings.css',
			[],
			BEW_VERSION
		);

		wp_enqueue_style(
			'bew-ui-components',
			BEW_URL . 'admin/assets/css/ui-components.css',
			[],
			BEW_VERSION
		);

		wp_enqueue_style(
			'bew-responsive',
			BEW_URL . 'admin/assets/css/responsive.css',
			[],
			BEW_VERSION
		);

		wp_enqueue_script(
			'bew-admin-settings',
			BEW_URL . 'admin/assets/js/admin-settings.js',
			[ 'jquery' ],
			BEW_VERSION,
			true
		);

		wp_enqueue_script(
			'bew-api-requests',
			BEW_URL . 'admin/assets/js/api-requests.js',
			[ 'jquery', 'bew-admin-settings' ],
			BEW_VERSION,
			true
		);

		wp_enqueue_script(
			'bew-admin-ai-integration',
			BEW_URL . 'admin/assets/js/admin-ai-integration.js',
			[ 'jquery', 'bew-admin-settings', 'bew-api-requests' ],
			BEW_VERSION,
			true
		);

		wp_localize_script( 'bew-admin-settings', 'bewAdmin', [
			'nonce'          => wp_create_nonce( 'bew-settings-nonce' ),
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'menuSlug'       => self::MENU_SLUG,
			'isProActive'    => class_exists( 'BEW_Pro' ),
			'save'           => __( 'Save Changes', 'bosa-elementor-for-woocommerce' ),
			'saving'         => __( 'Saving…', 'bosa-elementor-for-woocommerce' ),
			'saved'          => __( 'Saved!', 'bosa-elementor-for-woocommerce' ),
			'aiDeleteLabel'  => __( 'Delete', 'bosa-elementor-for-woocommerce' ),
			'aiConfirmDelete' => __( 'Remove this AI provider?', 'bosa-elementor-for-woocommerce' ),
			'aiErrorMessage' => __( 'Something went wrong. Please try again.', 'bosa-elementor-for-woocommerce' ),
			'aiNoneConnected' => __( 'No AI providers connected yet.', 'bosa-elementor-for-woocommerce' ),
			'aiLoadingModels' => __( 'Loading…', 'bosa-elementor-for-woocommerce' ),
			/* translators: %d is replaced client-side with the model count. */
			'aiModelsLoaded'  => __( '%d models loaded.', 'bosa-elementor-for-woocommerce' ),
			'aiManualModelPlaceholder' => __( 'Enter model ID, e.g. gpt-4o', 'bosa-elementor-for-woocommerce' ),
			'aiManualModelHelp' => __( 'Automatic detection isn\'t available for this provider. Enter the model ID manually.', 'bosa-elementor-for-woocommerce' ),
			'aiModelRequired' => __( 'Load models and choose one before adding this provider.', 'bosa-elementor-for-woocommerce' ),
			'aiDefaultLabel' => __( 'Default', 'bosa-elementor-for-woocommerce' ),
			'aiSetDefaultLabel' => __( 'Set as Default', 'bosa-elementor-for-woocommerce' ),
		] );

		wp_localize_script(
			'bew-admin-ai-integration',
			'bewAiProviderTypes',
			function_exists( 'bew_get_ai_provider_types' ) ? bew_get_ai_provider_types() : []
		);
	}

	public function render_info_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bosa-elementor-for-woocommerce' ) );
		}
		include BEW_PATH . 'admin/partials/page-info.php';
	}

	public function render_settings_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bosa-elementor-for-woocommerce' ) );
		}
		include BEW_PATH . 'admin/partials/page-settings.php';
	}

	public function render_products_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bosa-elementor-for-woocommerce' ) );
		}
		include BEW_PATH . 'admin/partials/page-products.php';
	}
}
