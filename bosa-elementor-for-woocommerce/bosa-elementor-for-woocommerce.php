<?php
/*
Plugin Name: BEW - Elementor Addons, Templates & AI Builder
Plugin URI: https://bew.bosathemes.com
Description: Enhance your website building experience with powerful Elementor widgets and a versatile, ready-to-use template library & AI-powered Content Generation for seamless customization and improved workflow efficiency.
Version:     2.1.3
Requires at least: 6.9
Requires PHP: 7.4
Requires Plugins: elementor
Author:      Bosa Themes
Author URI:  https://bosathemes.com
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /languages
Text Domain: bosa-elementor-for-woocommerce
*/

if (!defined('ABSPATH')) exit;

define('BEW_VERSION', '2.1.3');

// Tells Bosa Starter Sites that BEW shows its own "Bosa theme" recommendation
// (admin/notices/theme-notice.php), so Starter Sites stays silent while BEW is
// active and a site only ever sees one of the two.
define( 'BEW_RECOMMENDS_BOSA_THEME', true );

define('BEW_FILE', __FILE__);
define('BEW_PLUGIN_BASENAME', plugin_basename(BEW_FILE));
define('BEW_PATH', plugin_dir_path(BEW_FILE));
define('BEW_URL', plugins_url('/', BEW_FILE));

if (!defined('ABSPATH'))
    exit;

// Keeps BEW active after a WP Reset -- see inc/class-bew-wp-reset-compat.php.
require_once BEW_PATH . 'inc/class-bew-wp-reset-compat.php';

require_once BEW_PATH . 'admin/class-bew-settings.php';
add_filter( 'bew_enable_template_library', function () {
    return BEW_Plugin_Settings::get( 'template_library', true );
} );

if ( apply_filters( 'bew_enable_template_library', true ) ) {
    require_once __DIR__ . '/inc/template-library/init.php';
}

if ( BEW_Plugin_Settings::is_module_enabled( 'back_to_top' ) ) {
    require_once BEW_PATH . 'modules/back-to-top/init.php';
}

if ( BEW_Plugin_Settings::is_module_enabled( 'preloader' ) ) {
    require_once BEW_PATH . 'modules/preloader/init.php';
}

// wp_doing_cron() is included alongside is_admin() -- a real wp-cron.php
// request never sets is_admin() true, but bew_ai_weekly_model_refresh() (and
// everything it needs: BEW_Crypto, the model-cache functions) lives in this
// same admin-only block. The classes instantiated here only register hooks
// that fire on real admin/editor requests, so loading them during a cron
// run is harmless -- those hooks simply never fire in that context.
if ( is_admin() || wp_doing_cron() ) {
    require_once BEW_PATH . 'admin/ai-provider-types.php';
    require_once BEW_PATH . 'admin/class-bew-crypto.php';
    require_once BEW_PATH . 'admin/ai-provider-models.php';
    require_once BEW_PATH . 'admin/ai-text-generator.php';
    require_once BEW_PATH . 'admin/class-bew-admin.php';
    require_once BEW_PATH . 'admin/class-bew-ajax.php';
    require_once BEW_PATH . 'admin/class-bew-ai-editor.php';
    require_once BEW_PATH . 'admin/class-bew-ai-history.php';
    require_once BEW_PATH . 'admin/class-bew-ai-presets.php';
    new BEW_Admin();
    new BEW_AJAX();
    new BEW_AI_Editor();
    new BEW_AI_History();
    new BEW_AI_Presets();

    add_action( 'admin_init', function () {
        $stored = BEW_Plugin_Settings::get( 'current_version', '' );
        // Only treat this as an upgrade (and shift the rollback target) when the
        // stored version is genuinely older -- a `!==` check here would also fire
        // on a lateral/backward BEW_VERSION change (e.g. a cache-busting bump
        // during dev that never got reverted), recording a "previous" version
        // that's actually newer than what's running.
        if ( $stored && version_compare( $stored, BEW_VERSION, '<' ) ) {
            BEW_Plugin_Settings::save( 'previous_version', $stored );
            delete_transient( 'bew_rollback_version' );
        }
        if ( $stored !== BEW_VERSION ) {
            BEW_Plugin_Settings::save( 'current_version', BEW_VERSION );
        }
    } );
}

// Weekly background refresh of every connected provider's model cache (see
// bew_ai_weekly_model_refresh() in admin/ai-provider-models.php) -- keeps
// the editor cards' Model lists current without a manual "Refresh Models"
// click, and without the card itself ever fetching live. Self-heals on any
// request rather than relying solely on activation, since an update to an
// already-active plugin doesn't re-fire register_activation_hook().
add_filter( 'cron_schedules', function ( $schedules ) {
    if ( ! isset( $schedules['weekly'] ) ) {
        $schedules['weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display'  => __( 'Once Weekly', 'bosa-elementor-for-woocommerce' ),
        ];
    }
    return $schedules;
} );

add_action( 'init', function () {
    if ( ! wp_next_scheduled( 'bew_ai_weekly_model_refresh' ) ) {
        wp_schedule_event( time(), 'weekly', 'bew_ai_weekly_model_refresh' );
    }
} );

// YITH WooCommerce Compare's own comparison-table field list
// ('yith_woocompare_fields') is never populated until an admin manually
// visits and saves its Comparison Table settings tab at least once -- a
// fresh install has no default there at all (confirmed by reading YITH's
// own get_fields() and its settings-form definition: the form's own "all
// fields" default only takes effect on submit). Until that happens, YITH's
// compare popup renders a table with just a "Remove" row and nothing else
// -- no image, price, description, any of it. Seed a sensible default
// (every standard field except the "repeat at bottom" duplicates, which
// YITH itself also keeps off by default) the first time BEW loads with
// YITH Compare active and this option has genuinely never been saved.
// Never overwrites a real admin choice, even an all-unchecked one --
// get_option()'s own $default is only ever returned when the option row
// doesn't exist in wp_options at all.
add_action( 'init', function () {
    if ( ! class_exists( 'YITH_WooCompare_Helper' ) ) {
        return;
    }

    if ( null !== get_option( 'yith_woocompare_fields', null ) ) {
        return;
    }

    $defaults = YITH_WooCompare_Helper::get_default_table_fields( false );
    $fields   = [];

    foreach ( array_keys( $defaults ) as $key ) {
        $fields[ $key ] = ! in_array( $key, [ 'price_2', 'add_to_cart_2' ], true );
    }

    update_option( 'yith_woocompare_fields', $fields );
} );

// Bosa-family themes (bosa, bosa-agency, bosa-corporate-business, bosa-pro,
// etc. -- all share the bosa_compare_wishlist_buttons() hover-icon
// convention, hooked on woocommerce_after_shop_loop_item priority 10) render
// their own compare icon on every default shop-archive and related-products
// loop. YITH WooCommerce Compare's own auto-injected button (same hook,
// priority 20, controlled by its "show compare button in" setting) renders
// right alongside it -- two separate compare triggers on one product card.
// Remove YITH's own button only when a Bosa-family theme's own icon is
// actually registered on this exact hook, so the theme's icon is the single
// compare trigger there; this never touches BEW's own 3 product-listing
// widgets (already self-contained and CSS-scoped independently of this, see
// render_bew_compare_icon()) or any theme without its own compare icon --
// YITH's default button stays the correct, only trigger everywhere else.
add_action( 'wp', function () {
    $template   = get_template();
    $stylesheet = get_stylesheet();
    $is_bosa_family_theme = (
        'bosa' === $template || 0 === strpos( $template, 'bosa-' ) ||
        'bosa' === $stylesheet || 0 === strpos( $stylesheet, 'bosa-' )
    );

    if ( ! $is_bosa_family_theme ) {
        return;
    }

    if ( ! function_exists( 'bosa_compare_wishlist_buttons' )
        || ! has_action( 'woocommerce_after_shop_loop_item', 'bosa_compare_wishlist_buttons' ) ) {
        return;
    }

    if ( ! class_exists( 'YITH_WooCompare_Frontend' ) ) {
        return;
    }

    remove_action( 'woocommerce_after_shop_loop_item', [ \YITH_WooCompare_Frontend::instance(), 'output_button' ], 20 );
} );

register_deactivation_hook( BEW_FILE, function () {
    wp_clear_scheduled_hook( 'bew_ai_weekly_model_refresh' );
} );

/**
 * Main Class File of plugin
 * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
 */
if (!class_exists('BEW')) {
    class BEW{
        public $this_uri;
        public $this_dir;

        /**
         * Get Instance
         * 
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        private static $_instance = null;
        public static function instance(){
            if( is_null( self::$_instance ) ){
                self::$_instance = new self();
            }
            return self::$_instance;
        }
        
        /*
         * Constructor
         */
        public function __construct() {

            // This uri & dir
            $this->this_uri = BEW_URL;
            $this->this_dir = BEW_PATH;

            require_once ( BEW_PATH . 'admin/notices/rating-notice.php' );
            require_once ( BEW_PATH . 'admin/notices/pro-notice.php' );
            require_once ( BEW_PATH . 'admin/notices/sale-notice.php' );
            require_once ( BEW_PATH . 'admin/notices/theme-notice.php' );

            if (!did_action('elementor/loaded')) {
                add_action( 'admin_notices', array($this, 'admin_notice__error_ele') );
            }else{
                //elementor hooks 
                add_action( 'elementor/frontend/after_enqueue_scripts', array($this, '_scripts') );
                add_action( 'elementor/elements/categories_registered', array($this, 'elementor_category') );
                add_action( 'elementor/widgets/register', array($this, 'register_widgets') );
                add_action( 'elementor/widgets/register', array($this, 'unregister_disabled_widgets'), 999 );
                add_filter( 'elementor/document/save/data', array($this, 'stash_hidden_woo_widgets'), 10, 2 );
                add_action( 'elementor/editor/after_save', array($this, 'restore_hidden_woo_widgets') );
                add_action( 'elementor/editor/after_enqueue_styles', [$this, 'elementor_panel_css'] );
                add_action( 'elementor/editor/after_enqueue_scripts', [$this, 'elementor_panel_script'] );

            }
            
            if ( class_exists( 'WooCommerce') ) {
                add_action( 'admin_action_elementor', [ $this, 'register_wc_hooks' ], 9);
            }

            add_action( 'admin_menu', [ $this, 'bew_addons_add_admin_menu' ] ); 
            add_filter( 'elementor/editor/localize_settings', [ $this, 'get_pro_widgets' ] );

        }

        public function elementor_panel_script() {
            $rel_path = 'assets/js/bew-editor.js';
            wp_enqueue_script(
                'bew-panel-script',
                $this->this_uri . $rel_path,
                [ 'jquery' ],
                $this->asset_version( $rel_path ),
                true
            );

            // Locked Pro tiles use the same call-to-action as the BEW pages:
            // Activate License / Renew License with BEW Pro installed, otherwise
            // Upgrade Now (see BEW::pro_cta()).
            $pro_cta = self::pro_cta();
            $panel   = array(
                'proCtaText' => $pro_cta['text'],
                'proCtaUrl'  => $pro_cta['url'],
            );

            if ( ! self::is_woo_active() ) {
                $cta = self::woo_lock_cta();

                $panel['wooTitle']   = __( 'WooCommerce Required', 'bosa-elementor-for-woocommerce' );
                $panel['wooContent'] = __( 'This widget requires WooCommerce. Install and activate WooCommerce to use it.', 'bosa-elementor-for-woocommerce' );
                $panel['wooCtaText'] = $cta['text'];
                $panel['wooCtaUrl']  = $cta['url'];
            }

            wp_localize_script( 'bew-panel-script', 'BEW_PANEL', $panel );
        }

        public function elementor_panel_css() {
            $rel_path = 'assets/css/panel.css';
            wp_enqueue_style(
                'bew-panel',
                $this->this_uri . $rel_path,
                [],
                $this->asset_version( $rel_path )
            );
        }

        /**
         * Cache-busting version based on file mtime.
         *
         * @param string $rel_path Path relative to the plugin root.
         * @return string
         */
        private function asset_version( $rel_path ) {
            $full_path = $this->this_dir . $rel_path;
            return file_exists( $full_path ) ? (string) filemtime( $full_path ) : BEW_VERSION;
        }

        /** 
         * WooCommerce Frontend Hooks
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.1
         */
        public function register_wc_hooks() {
            wc()->frontend_includes();
        }

        /**
         * To Check Plugin is installed or not
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function _is_plugin_installed($plugin_path ) {
            $installed_plugins = get_plugins();
            return isset( $installed_plugins[ $plugin_path ] );
        }

        /**
         * 
         * Admin Error Notice
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function admin_notice__error_ele() {

            if (!current_user_can('activate_plugins')) {
                return;
            }
    
            $elementor = 'elementor/elementor.php';
            if ( $this->_is_plugin_installed( $elementor ) ) {
                $activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $elementor . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $elementor);

                /* translators: %1$s: opening <strong> tag, %2$s: closing </strong> tag */
                $message = sprintf( esc_html__('%1$sBEW%2$s requires %1$sElementor%2$s plugin to be active. Please activate Elementor to continue.', 'bosa-elementor-for-woocommerce'), "<strong>", "</strong>");

                $button_text = esc_html__('Activate Elementor', 'bosa-elementor-for-woocommerce');
            } else {
                $activation_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=elementor'), 'install-plugin_elementor');

                /* translators: %1$s: opening <strong> tag, %2$s: closing </strong> tag */
                $message = sprintf( esc_html__('%1$sBEW%2$s requires %1$sElementor%2$s plugin to be installed and activated. Please install Elementor to continue.', 'bosa-elementor-for-woocommerce'), '<strong>', '</strong>');
                $button_text = esc_html__('Install Elementor', 'bosa-elementor-for-woocommerce');
            }
    
            $button = '<p><a href="' . esc_url( $activation_url ) . '" class="button-primary">' . $button_text . '</a></p>';

            printf('<div class="error"><p>%1$s</p>%2$s</div>', $message, $button); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        }

        /**
         * Load and register the required Elementor widgets file
         *
         * @param $widgets_manager
         *
         * @since Bosa Elementor Addons and Templates for WooCommerce
         */
        function register_widgets( $widgets_manager ) {

            include( $this->this_dir . 'inc/bew-common.php' );

            $toggles     = BEW_Plugin_Settings::get( 'widget_toggles', [] );
            $woo_active  = self::is_woo_active();
            $woo_widgets = self::woo_widgets();

            // Standard panel order: WooCommerce widgets first, then General;
            // matches the BEW Settings > Widgets tab. Woo Grid Products is
            // registered separately (own guard against a stale BEW Pro copy)
            // but must lead the Woo group, so it goes first, ahead of this loop.
            $this->register_grid_products_widget( $widgets_manager );

            $free_widgets = [
                // WooCommerce
                'bew_products'          => [ 'bew-elements-products.php',          'BEW_Products' ],
                'bew_carousel_products' => [ 'bew-elements-carousel-products.php', 'BEW_Carousel_Products' ],
                'bew_products_list'     => [ 'bew-elements-products-list.php',     'BEW_Products_List' ],
                'bew_categories'        => [ 'bew-elements-categories.php',        'BEW_Categories' ],
                'bew_categories_list'   => [ 'bew-elements-categories-list.php',   'BEW_Categories_List' ],
                'bew_search'            => [ 'bew-elements-search.php',            'BEW_Search' ],
                'bew_cart'              => [ 'bew-elements-cart.php',              'BEW_Cart' ],
                'bew_my_account'        => [ 'bew-elements-my-account.php',        'BEW_My_Account' ],
                'bew_yith_wishlist'     => [ 'bew-elements-yith-wishlist.php',     'BEW_Yith_Wishlist' ],
                'bew_yith_compare'      => [ 'bew-elements-yith-compare.php',      'BEW_Yith_Compare' ],
                // General
                'bew_site_logo'         => [ 'bew-elements-site-logo.php',         'BEW_Site_Logo' ],
                'bew_dropdown_nav'      => [ 'bew-elements-dropdown-nav.php',      'BEW_Dropdown_Nav' ],
                'bew_blog'              => [ 'bew-elements-blog.php',              'BEW_Blog' ],
                'bew_contact_form_7'    => [ 'bew-elements-contact-form-7.php',    'BEW_Contact_Form_7' ],
            ];

            foreach ( $free_widgets as $toggle_id => $info ) {
                if ( isset( $toggles[ $toggle_id ] ) && ! $toggles[ $toggle_id ] ) {
                    continue;
                }
                // These classes call WooCommerce functions, so they are not registered
                // while WooCommerce is inactive; a locked panel tile stands in for them
                // (see get_pro_widgets()). Saved toggles are left untouched.
                if ( ! $woo_active && isset( $woo_widgets[ $toggle_id ] ) ) {
                    continue;
                }
                require_once $this->this_dir . 'widgets/' . $info[0];
                $class = '\\Elementor\\' . $info[1];
                $widgets_manager->register( new $class() );
            }
        }

        /**
         * Woo Grid Products used to ship only with BEW Pro; current BEW Pro
         * versions no longer register their own copy at all. Called directly
         * from register_widgets(), before that function's own loop, so this
         * widget leads the Woo group in the panel. The "already registered"
         * guard below just skips redundant work if something has already
         * claimed the widget name -- e.g. a stale, pre-update BEW Pro that
         * still bundles and registers its own copy.
         *
         * @param \Elementor\Widgets_Manager $widgets_manager
         */
        function register_grid_products_widget( $widgets_manager ) {
            if ( ! self::is_woo_active() ) {
                return;
            }

            $toggles = BEW_Plugin_Settings::get( 'widget_toggles', [] );
            if ( isset( $toggles['bew_pro_grid_products'] ) && ! $toggles['bew_pro_grid_products'] ) {
                return;
            }

            if ( $widgets_manager->get_widget_types( 'bew-pro-grid-products' ) ) {
                return;
            }

            if ( ! class_exists( '\Elementor\BEW_Settings' ) ) {
                include_once $this->this_dir . 'inc/bew-common.php';
            }

            require_once $this->this_dir . 'widgets/bew-elements-grid-products.php';
            $widgets_manager->register( new \Elementor\BEW_Grid_Products() );
        }

        function unregister_disabled_widgets( $widgets_manager ) {
            $toggles = BEW_Plugin_Settings::get( 'widget_toggles', [] );

            $pro_map = [
                'bew_product_accordion'      => 'bew-pro-product-accordion',
                'bew_pro_product_tab'        => 'bew-pro-product-tabs',
                'bew_pro_hot_deals'          => 'bew-pro-hot-deals',
                'bew_pro_product_slider'     => 'bew-pro-product-slider',
                'bew_pro_grid_products'      => 'bew-pro-grid-products',
                'bew_pro_grid_carousel'      => 'bew-pro-grid-carousel-products',
                'bew_pro_image_carousel'     => 'bew-pro-image-carousel',
                'bew_pro_testimonial_slider' => 'bew-pro-testimonial-slider',
            ];

            foreach ( $pro_map as $toggle_id => $widget_name ) {
                if ( isset( $toggles[ $toggle_id ] ) && ! $toggles[ $toggle_id ] ) {
                    $widgets_manager->unregister( $widget_name );
                }
            }

            // While WooCommerce is inactive, also drop the WooCommerce widgets that
            // BEW Pro registered on its own; a locked panel tile replaces them.
            if ( ! self::is_woo_active() ) {
                foreach ( self::woo_widgets() as $woo_widget ) {
                    $widgets_manager->unregister( $woo_widget['name'] );
                }
            }
        }

        /**
         * Whether WooCommerce is loaded. Only call this from a hook that runs after
         * all plugins are loaded, never while this plugin file itself is loading.
         */
        public static function is_woo_active() {
            return class_exists( 'WooCommerce' );
        }

        /**
         * Whether the Bosa Pro theme is the active theme. It's a theme (folder
         * "bosa-pro"), not a plugin, so "active" means current theme -- checking
         * both get_template() and get_stylesheet() so a child theme of Bosa Pro
         * still counts.
         */
        public static function is_bosa_pro_theme_active() {
            return ( 'bosa-pro' === get_template() || 'bosa-pro' === get_stylesheet() );
        }

        /**
         * Whether the Bosa Shop Extension Pro plugin is active. It defines this
         * constant as soon as its main plugin file loads.
         */
        public static function is_bosa_shop_extension_pro_active() {
            return defined( 'BOSA_SHOP_EXTENSION_PRO_BASENAME' );
        }

        /**
         * Whether every Bosa product BEW cross-sells (Bosa Pro, Bosa Shop
         * Extension Pro, BEW Pro) is active on this site -- i.e. there's nothing
         * left to show on the "Our Products" page. See admin/partials/page-products.php
         * and BEW_Admin::register_menu().
         */
        public static function all_bosa_products_active() {
            return self::is_bosa_pro_theme_active()
                && self::is_bosa_shop_extension_pro_active()
                && self::is_pro_unlocked();
        }

        /**
         * BEW Pro's license state as BEW Pro reports it through the
         * 'bew_pro_license' filter: 'active' (active, lifetime or trial),
         * 'expired' or 'none', plus its activate/renew links. '' when BEW Pro
         * isn't active. The free plugin holds no license code itself; an older
         * BEW Pro without the report counts as active once it has loaded,
         * exactly as before.
         *
         * @return array
         */
        public static function pro_license() {
            static $info = null;

            if ( null === $info ) {
                $raw    = apply_filters( 'bew_pro_license', array() );
                $raw    = is_array( $raw ) ? $raw : array();
                $status = isset( $raw['status'] ) && in_array( $raw['status'], array( 'active', 'expired', 'none' ), true )
                    ? $raw['status']
                    : ( class_exists( 'BEW_Pro' ) ? 'active' : '' );

                $info = array(
                    'status'       => $status,
                    'activate_url' => ! empty( $raw['activate_url'] ) ? $raw['activate_url'] : admin_url( 'admin.php?page=bew-pro' ),
                    'renew_url'    => ! empty( $raw['renew_url'] ) ? $raw['renew_url'] : 'https://bew.bosathemes.com/pricing',
                );
            }

            return $info;
        }

        /**
         * @return string 'active', 'expired', 'none', or '' without BEW Pro.
         */
        public static function pro_status() {
            $info = self::pro_license();
            return $info['status'];
        }

        /**
         * BEW Pro is active as a plugin, whatever its license state.
         */
        public static function is_pro_installed() {
            return '' !== self::pro_status();
        }

        /**
         * BEW Pro features can be added: an active, lifetime or trial license.
         */
        public static function is_pro_unlocked() {
            return 'active' === self::pro_status();
        }

        /**
         * Call-to-action for a locked Pro feature: "Activate License" or
         * "Renew License" when BEW Pro is installed, otherwise the usual upgrade.
         *
         * @return array { url, text, new_tab }
         */
        public static function pro_cta() {
            $info = self::pro_license();

            if ( 'none' === $info['status'] ) {
                $cta = array( 'url' => $info['activate_url'], 'text' => __( 'Activate License', 'bosa-elementor-for-woocommerce' ) );
            } elseif ( 'expired' === $info['status'] ) {
                $cta = array( 'url' => $info['renew_url'], 'text' => __( 'Renew License', 'bosa-elementor-for-woocommerce' ) );
            } else {
                $cta = array( 'url' => 'https://bew.bosathemes.com/pricing', 'text' => __( 'Upgrade Now', 'bosa-elementor-for-woocommerce' ) );
            }

            // Links into this site's own admin open in the same tab.
            $cta['new_tab'] = 0 !== strpos( $cta['url'], admin_url() );

            return $cta;
        }

        /**
         * Every BEW widget that needs WooCommerce, free and Pro alike, keyed by the
         * toggle id stored in the "widget_toggles" option. `tier` only picks the
         * badge on the locked Elementor panel tile.
         *
         * @return array<string, array{name:string, title:string, icon:string, keywords:string[], tier:string}>
         */
        public static function woo_widgets() {
            return array(
                // WooCommerce -- Free
                'bew_pro_grid_products' => array(
                    'name'     => 'bew-pro-grid-products',
                    'title'    => __( 'Woo Grid Products', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-product-related',
                    'keywords' => array( 'bew', 'product', 'products', 'bew products', 'grid', 'woo', 'woo products', 'woo grid products', 'bosa' ),
                    'tier'     => 'free',
                ),
                'bew_products' => array(
                    'name'     => 'bew-elements-products',
                    'title'    => __( 'Woo Archive Products', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-products',
                    'keywords' => array( 'bew', 'product', 'products', 'bew products', 'woo', 'woo products', 'woo archive products', 'archive', 'bosa' ),
                    'tier'     => 'free',
                ),
                'bew_carousel_products' => array(
                    'name'     => 'bew-elements-carousel-products',
                    'title'    => __( 'Woo Archive Carousel', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-carousel',
                    'keywords' => array( 'bew', 'carousel', 'bew carousel', 'woo', 'woo carousel', 'woo archive carousel', 'archive', 'bosa' ),
                    'tier'     => 'free',
                ),
                'bew_products_list' => array(
                    'name'     => 'bew-elements-product-list',
                    'title'    => __( 'Woo Products List', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-post-list',
                    'keywords' => array( 'bew', 'product', 'products', 'list', 'products list', 'bew products', 'woo', 'woo products', 'woo products list', 'bosa' ),
                    'tier'     => 'free',
                ),
                'bew_categories' => array(
                    'name'     => 'bew-elements-product-categories',
                    'title'    => __( 'Woo Categories', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-product-categories',
                    'keywords' => array( 'bew', 'categories', 'bew categories', 'woo', 'woo categories', 'bosa' ),
                    'tier'     => 'free',
                ),
                'bew_categories_list' => array(
                    'name'     => 'bew-elements-product-categories-list',
                    'title'    => __( 'Woo Categories List', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-radio',
                    'keywords' => array( 'bew', 'categories', 'list', 'bew categories', 'woo', 'woo categories list', 'bosa' ),
                    'tier'     => 'free',
                ),
                'bew_search' => array(
                    'name'     => 'bew-elements-search',
                    'title'    => __( 'Woo Search', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'bew-icon-search',
                    'keywords' => array( 'search', 'products search', 'bew search', 'woo', 'woocommerce', 'woo search' ),
                    'tier'     => 'free',
                ),
                'bew_cart' => array(
                    'name'     => 'bew-elements-cart',
                    'title'    => __( 'Woo Cart', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'bew-icon-cart',
                    'keywords' => array( 'bew', 'cart', 'bew cart', 'woo', 'woocommerce', 'woo cart' ),
                    'tier'     => 'free',
                ),
                'bew_my_account' => array(
                    'name'     => 'bew-elements-my-account',
                    'title'    => __( 'Woo My Account', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'bew-icon-account',
                    'keywords' => array( 'bew', 'account', 'bew my account', 'my account', 'woo', 'woocommerce', 'woo my account' ),
                    'tier'     => 'free',
                ),
                'bew_yith_wishlist' => array(
                    'name'     => 'bew-elements-yith-wishlist',
                    'title'    => __( 'Woo YITH Wishlist', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'bew-icon-wishlist',
                    'keywords' => array( 'bew', 'wishlist', 'bew wishlist', 'woo', 'woocommerce', 'yith', 'yith wishlist', 'woo yith wishlist' ),
                    'tier'     => 'free',
                ),
                'bew_yith_compare' => array(
                    'name'     => 'bew-elements-yith-compare',
                    'title'    => __( 'Woo YITH Compare', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'bew-icon-compare',
                    'keywords' => array( 'bew', 'compare', 'bew compare', 'woo', 'woocommerce', 'yith', 'yith compare', 'woo yith compare' ),
                    'tier'     => 'free',
                ),
                // WooCommerce -- Pro
                'bew_pro_hot_deals' => array(
                    'name'     => 'bew-pro-hot-deals',
                    'title'    => __( 'Woo Hot Deals', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-single-product',
                    'keywords' => array( 'bew', 'pro', 'bew pro', 'hot', 'bew slider', 'woo', 'hot deals', 'woo hot deals', 'woo products', 'bosa' ),
                    'tier'     => 'pro',
                ),
                'bew_pro_product_slider' => array(
                    'name'     => 'bew-pro-product-slider',
                    'title'    => __( 'Woo Product Slider', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-post-slider',
                    'keywords' => array( 'bew', 'pro', 'bew pro', 'product slider', 'bew slider', 'woo', 'woo product slider', 'bosa' ),
                    'tier'     => 'pro',
                ),
                'bew_pro_product_tab' => array(
                    'name'     => 'bew-pro-product-tabs',
                    'title'    => __( 'Woo Product Tabs', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-product-tabs',
                    'keywords' => array( 'bew', 'pro', 'bew pro', 'tabs', 'bew tabs', 'bew pro tabs', 'panel', 'navigation', 'group', 'tabs content', 'product tabs', 'woo product tabs', 'bosa' ),
                    'tier'     => 'pro',
                ),
                'bew_pro_grid_carousel' => array(
                    'name'     => 'bew-pro-grid-carousel-products',
                    'title'    => __( 'Woo Grid Carousel', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-nested-carousel',
                    'keywords' => array( 'bew', 'pro', 'bew pro', 'carousel', 'grid', 'woo', 'woo carousel', 'woo grid carousel', 'bosa' ),
                    'tier'     => 'pro',
                ),
                'bew_product_accordion' => array(
                    'name'     => 'bew-pro-product-accordion',
                    'title'    => __( 'Woo Products Accordion', 'bosa-elementor-for-woocommerce' ),
                    'icon'     => 'eicon-accordion',
                    'keywords' => array( 'bew', 'pro', 'bew pro', 'product', 'products', 'accordion', 'products accordion', 'bew products', 'woo', 'woo products', 'woo products accordion', 'bosa' ),
                    'tier'     => 'pro',
                ),
            );
        }

        /**
         * Title of a WooCommerce-dependent BEW widget, looked up by its Elementor
         * widget type; null for anything else.
         *
         * @param string $widget_type
         * @return string|null
         */
        public static function woo_widget_title( $widget_type ) {
            foreach ( self::woo_widgets() as $widget ) {
                if ( $widget['name'] === $widget_type ) {
                    return $widget['title'];
                }
            }
            return null;
        }

        /**
         * What the locked WooCommerce tiles offer: activate WooCommerce when it is
         * installed but inactive, install it when it is missing.
         *
         * @return array{text:string, url:string}
         */
        public static function woo_lock_cta() {
            $file = 'woocommerce/woocommerce.php';

            if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $installed = get_plugins();

            if ( isset( $installed[ $file ] ) && current_user_can( 'activate_plugins' ) ) {
                return array(
                    'text' => __( 'Activate WooCommerce', 'bosa-elementor-for-woocommerce' ),
                    'url'  => wp_specialchars_decode( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . $file ), 'activate-plugin_' . $file ) ),
                );
            }

            if ( ! isset( $installed[ $file ] ) && current_user_can( 'install_plugins' ) ) {
                return array(
                    'text' => __( 'Install WooCommerce', 'bosa-elementor-for-woocommerce' ),
                    'url'  => wp_specialchars_decode( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' ) ),
                );
            }

            return array(
                'text' => __( 'Get WooCommerce', 'bosa-elementor-for-woocommerce' ),
                'url'  => 'https://wordpress.org/plugins/woocommerce/',
            );
        }

        /**
         * WooCommerce widgets found in the stored data of a page that is about to be
         * saved while WooCommerce is inactive, keyed by the id of the post being
         * written. See stash_hidden_woo_widgets() / restore_hidden_woo_widgets().
         *
         * @var array
         */
        private static $hidden_woo_widgets = array();

        /**
         * Elementor only loads registered widgets into the editor and re-instantiates
         * every element when it saves, so saving a page while WooCommerce is inactive
         * would silently drop every WooCommerce widget on it (they are deliberately
         * unregistered then). Step 1 of keeping them: remember which ones the stored
         * page has, before Elementor overwrites it. No-op whenever WooCommerce is
         * active.
         *
         * @param array                         $data     Document data being saved.
         * @param \Elementor\Core\Base\Document $document The document being saved.
         * @return array The data, unchanged.
         */
        public function stash_hidden_woo_widgets( $data, $document ) {
            $post_id = $document->get_post()->ID;
            unset( self::$hidden_woo_widgets[ $post_id ] );

            if ( self::is_woo_active() ) {
                return $data;
            }

            $stored = get_post_meta( $document->get_main_id(), '_elementor_data', true );
            if ( is_string( $stored ) ) {
                $stored = json_decode( $stored, true );
            }
            if ( ! is_array( $stored ) ) {
                return $data;
            }

            $hidden = array();
            self::collect_woo_widgets( $stored, '', wp_list_pluck( self::woo_widgets(), 'name' ), $hidden );
            if ( $hidden ) {
                self::$hidden_woo_widgets[ $post_id ] = $hidden;
            }

            return $data;
        }

        /**
         * Step 2: once Elementor has written the page, put the stashed WooCommerce
         * widgets back into the stored data -- same container, same position, exactly
         * as they were -- so they return with their settings when WooCommerce is
         * active again.
         *
         * @param int $post_id The post Elementor just saved.
         */
        public function restore_hidden_woo_widgets( $post_id ) {
            if ( empty( self::$hidden_woo_widgets[ $post_id ] ) ) {
                return;
            }

            $hidden = self::$hidden_woo_widgets[ $post_id ];
            unset( self::$hidden_woo_widgets[ $post_id ] );

            $saved = get_metadata( 'post', $post_id, '_elementor_data', true );
            if ( is_string( $saved ) ) {
                $saved = json_decode( $saved, true );
            }
            if ( ! is_array( $saved ) ) {
                return;
            }

            $present = array();
            self::collect_element_ids( $saved, $present );

            $merged = self::reinsert_woo_widgets( $saved, '', $hidden, $present );

            update_metadata( 'post', $post_id, '_elementor_data', wp_slash( wp_json_encode( $merged ) ) );
        }

        private static function collect_woo_widgets( array $elements, $parent_id, array $woo_types, array &$hidden ) {
            foreach ( array_values( $elements ) as $index => $element ) {
                if ( ! is_array( $element ) ) {
                    continue;
                }

                if ( isset( $element['elType'], $element['widgetType'] ) && 'widget' === $element['elType'] && in_array( $element['widgetType'], $woo_types, true ) ) {
                    $hidden[ $parent_id ][] = array(
                        'index'   => $index,
                        'element' => $element,
                    );
                    continue;
                }

                if ( isset( $element['id'], $element['elements'] ) && is_array( $element['elements'] ) ) {
                    self::collect_woo_widgets( $element['elements'], (string) $element['id'], $woo_types, $hidden );
                }
            }
        }

        private static function collect_element_ids( array $elements, array &$ids ) {
            foreach ( $elements as $element ) {
                if ( ! is_array( $element ) ) {
                    continue;
                }

                if ( isset( $element['id'] ) ) {
                    $ids[ (string) $element['id'] ] = true;
                }

                if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
                    self::collect_element_ids( $element['elements'], $ids );
                }
            }
        }

        private static function reinsert_woo_widgets( array $elements, $parent_id, array $hidden, array $present ) {
            // Recurse into every container -- including ones that arrive empty because
            // their only children were the hidden widgets.
            foreach ( $elements as $i => $element ) {
                if ( ! is_array( $element ) || ! isset( $element['id'] ) || ( isset( $element['elType'] ) && 'widget' === $element['elType'] ) ) {
                    continue;
                }

                $children                   = ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) ? $element['elements'] : array();
                $elements[ $i ]['elements'] = self::reinsert_woo_widgets( $children, (string) $element['id'], $hidden, $present );
            }

            // A hidden widget whose parent container no longer exists was removed
            // along with that container by the user, so it is not resurrected.
            if ( isset( $hidden[ $parent_id ] ) ) {
                foreach ( $hidden[ $parent_id ] as $item ) {
                    if ( isset( $present[ (string) $item['element']['id'] ] ) ) {
                        continue;
                    }
                    array_splice( $elements, min( $item['index'], count( $elements ) ), 0, array( $item['element'] ) );
                }
            }

            return $elements;
        }

        /**
         * Locked Elementor panel tiles for the WooCommerce widgets, shown in place
         * of the real widgets while WooCommerce is inactive. Same "promotion
         * widget" mechanism as the locked Pro tiles. Widgets the admin switched
         * off stay hidden.
         *
         * @param array $toggles Saved widget toggles.
         * @return array
         */
        public static function get_woo_locked_tiles( $toggles ) {
            $cta   = self::woo_lock_cta();
            $tiles = array();

            foreach ( self::woo_widgets() as $toggle_id => $widget ) {
                if ( isset( $toggles[ $toggle_id ] ) && ! $toggles[ $toggle_id ] ) {
                    continue;
                }

                $tiles[] = array(
                    'name'       => $widget['name'],
                    'title'      => $widget['title'],
                    'categories' => '["bosa-elementor-for-woocommerce"]',
                    'icon'       => 'bew-woo-widget-promotion bew-woo-lock-' . $widget['tier'] . ' ' . $widget['icon'],
                    'keywords'   => $widget['keywords'],
                    'promotion'  => array(
                        'upgrade_url'  => $cta['url'],
                        'upgrade_text' => $cta['text'],
                    ),
                );
            }

            return $tiles;
        }

        /**
         * Loads scripts on elementor editor
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function _scripts() {
            // preview script
            wp_enqueue_script('masonry');
            wp_enqueue_script('bew-elementor-kit-owl', $this->this_uri . 'assets/js/owl.carousel.min.js', array('jquery'), BEW_VERSION, true);
            wp_enqueue_script(
                'bew-elementor-kit-script',
                $this->this_uri . 'assets/js/bew-admin-script.js',
                array( 'jquery' ),
                $this->asset_version( 'assets/js/bew-admin-script.js' ),
                true
            );
            wp_enqueue_style('bew-elementor-kit-owl-css', $this->this_uri . 'assets/css/owl-carousel-min.css', array(), BEW_VERSION);
            wp_enqueue_style('bew-elementor-kit-owl-default', $this->this_uri . 'assets/css/owl.theme.default.min.css', array(), BEW_VERSION);
            // Blog widget's meta row hardcodes raw "far fa-clock"/"far fa-comment"/
            // "far fa-user" icon markup (not via Elementor's own icon-picker
            // control, which is the only case Elementor auto-enqueues Font Awesome
            // for) -- so without this, those icons render blank on every page that
            // has the widget. Elementor already bundles Font Awesome Free for its
            // own icon picker; reusing that copy instead of loading a second one.
            if ( defined( 'ELEMENTOR_ASSETS_URL' ) ) {
                wp_enqueue_style(
                    'bew-elementor-kit-font-awesome',
                    ELEMENTOR_ASSETS_URL . 'lib/font-awesome/css/all.min.css',
                    [],
                    defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : false
                );
            }
            wp_enqueue_style(
                'bew-elementor-kit-global-style',
                $this->this_uri . 'assets/css/bew-global.css',
                [],
                $this->asset_version( 'assets/css/bew-global.css' )
            );
            wp_enqueue_style(
                'bew-elementor-kit-style',
                $this->this_uri . 'assets/style.css',
                [],
                $this->asset_version( 'assets/style.css' )
            );
        }

        /**
         * Elementor Category
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function elementor_category() {

            // Register widget block category for Elementor section
            \Elementor\Plugin::instance()->elements_manager->add_category( 'bosa-elementor-for-woocommerce', array(
                'title' => esc_html__( 'BEW Elements', 'bosa-elementor-for-woocommerce' ),
                'order' => -1,  // topmost position
            ) );
        }

        function bew_get_page_templates(){
            $page_templates = get_posts( [
                'post_type'         => 'elementor_library',
                'posts_per_page'    => -1
            ] );

            $options = [];

            if ( ! empty( $page_templates ) && ! is_wp_error( $page_templates ) ){
                foreach ( $page_templates as $template ) {
                    $options[ $template->ID ] = $template->post_title;
                }
            }
            return $options;
        }

        function bew_ext_html_tags( $tag ) {
            $allowed_tags = [
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'p',
            ];
            return in_array( strtolower( $tag ), $allowed_tags ) ? $tag : 'h2';
        }

        // Add Settings page link to plugins screen
        function insert_plugin_links( $links ){
            // Settings
            $links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=bew-settings' ) ) . '">' . esc_html__('Settings', 'bosa-elementor-for-woocommerce') . '</a>';

            // Go Pro
            $pro = 'bosa-elementor-for-woocommerce-pro/bosa-elementor-for-woocommerce-pro.php';
            if ( !$this->_is_plugin_installed( $pro ) ) {
                $links['upgrade-pro'] = '<a href="https://bew.bosathemes.com/pricing" target="_blank" class="bew-plugins-upgrade-pro" style="color: #624FE0;font-weight:bold;">' . esc_html__('Upgrade to Pro', 'bosa-elementor-for-woocommerce') . '</a>';
            }

            return $links;
        }

        function bew_addons_add_admin_menu() {
            add_filter( 'plugin_action_links_'. BEW_PLUGIN_BASENAME, [ $this, 'insert_plugin_links' ] );
            add_action( 'admin_head-plugins.php', [ $this, 'print_upgrade_pro_link_style' ] );
        }

        // :hover can't be expressed via the inline style="" on the link itself
        // (see insert_plugin_links()), so it's printed here instead -- scoped to
        // the Plugins screen only via the admin_head-plugins.php hook.
        function print_upgrade_pro_link_style() {
            echo '<style>.bew-plugins-upgrade-pro:hover{color:#7968E4 !important;}</style>';
        }

        public static function get_pro_widgets($config) {

            $promotion_widgets = [];

            if ( isset( $config['promotionWidgets'] ) ) {
                $promotion_widgets = $config['promotionWidgets'];
            }

            $toggles   = BEW_Plugin_Settings::get( 'widget_toggles', [] );
            $woo_tiles = self::is_woo_active() ? [] : self::get_woo_locked_tiles( $toggles );

            if ( ! self::is_pro_unlocked() ) {
                $pro_widgets = array(
                    // WooCommerce -- Pro
                    array(
                        'name'       => 'bew-pro-hot-deals',
                        'title'      => esc_html__( 'Woo - Hot Deals', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-single-product',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'hot', 'bew slider', 'woo', 'hot deals', 'woo products', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-product-slider',
                        'title'      => esc_html__( 'Woo - Product Slider', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-post-slider',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'product slider', 'bew slider', 'woo', 'woo product slider', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-product-tabs',
                        'title'      => esc_html__( 'Woo - Product Tabs', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-product-tabs',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'tabs', 'bew tabs', 'bew pro tabs', 'panel', 'navigation', 'group', 'tabs content', 'product tabs', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-grid-carousel-product',
                        'title'      => esc_html__( 'Woo - Grid Carousel', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-nested-carousel',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'carousel', 'grid', 'woo', 'woo carousel', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-product-accordion',
                        'title'      => esc_html__( 'Woo - Products Accordion', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-accordion',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'product', 'products', "accordion", "products accordion", 'bew products', 'woo', 'woo products', 'bosa' ),
                    ),
                    // General -- Pro
                    array(
                        'name'       => 'bew-pro-image-carousel',
                        'title'      => esc_html__( 'Image Carousel', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-carousel-loop',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'image', 'bew carousel', 'woo', 'image carousel', 'archive', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-testimonial-slider',
                        'title'      => esc_html__( 'Testimonial Slider', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-testimonial-carousel',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'testimonial', 'bew slider', 'woo', 'testimonial slider', 'archive', 'bosa' ),
                    ),
                );

                $bew_pro_cta = self::pro_cta();
                foreach ( $pro_widgets as &$pw ) {
                    $pw['promotion'] = [
                        'upgrade_url'  => $bew_pro_cta['url'],
                        'upgrade_text' => esc_html( $bew_pro_cta['text'] ),
                    ];
                }
                unset( $pw );

                $promotion_toggle_map = [
                    'bew-pro-grid-carousel-product' => 'bew_pro_grid_carousel',
                    'bew-pro-hot-deals'             => 'bew_pro_hot_deals',
                    'bew-pro-product-accordion'     => 'bew_product_accordion',
                    'bew-pro-product-slider'        => 'bew_pro_product_slider',
                    'bew-pro-product-tabs'          => 'bew_pro_product_tab',
                    'bew-pro-image-carousel'        => 'bew_pro_image_carousel',
                    'bew-pro-testimonial-slider'    => 'bew_pro_testimonial_slider',
                ];

                $woo_toggle_ids = self::woo_widgets();

                $pro_widgets = array_filter( $pro_widgets, function ( $widget ) use ( $toggles, $promotion_toggle_map, $woo_tiles, $woo_toggle_ids ) {
                    if ( isset( $promotion_toggle_map[ $widget['name'] ] ) ) {
                        $tid = $promotion_toggle_map[ $widget['name'] ];
                        if ( isset( $toggles[ $tid ] ) && ! $toggles[ $tid ] ) {
                            return false;
                        }
                        // The locked "requires WooCommerce" tile replaces the locked "Pro" tile.
                        if ( $woo_tiles && isset( $woo_toggle_ids[ $tid ] ) ) {
                            return false;
                        }
                    }
                    return true;
                } );

                // With an expired license the real Pro widgets stay registered (hidden from
                // the panel) so existing pages keep rendering; the locked tiles get their own
                // names so they never clash with them.
                if ( class_exists( 'BEW_Pro' ) ) {
                    foreach ( $pro_widgets as &$pw ) {
                        $pw['name'] .= '-locked';
                    }
                    unset( $pw );
                }

                $promotion_widgets = array_merge( $promotion_widgets, $pro_widgets );
            }

            if ( $woo_tiles ) {
                $promotion_widgets = array_merge( $promotion_widgets, $woo_tiles );
            }

            if ( ! self::is_pro_unlocked() || $woo_tiles ) {
                $config['promotionWidgets'] = $promotion_widgets;
            }
            return $config;
        }
    }
}

function bew_activation_time() {//TODO: Try to locate this in rating-notice.php later if possible
    if ( false === get_option( 'bew_activation_time' ) ) {
        add_option( 'bew_activation_time', absint(intval(strtotime('now'))) );
    }
}

register_activation_hook( __FILE__, 'bew_activation_time' );

register_activation_hook( __FILE__, function () {
    if ( ! wp_next_scheduled( 'bew_daily_template_sync' ) ) {
        $tz        = new DateTimeZone( 'America/New_York' );
        $midnight  = new DateTime( 'tomorrow midnight', $tz );
        $timestamp = $midnight->getTimestamp();
        wp_schedule_event( $timestamp, 'daily', 'bew_daily_template_sync' );
    }
} );

register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'bew_daily_template_sync' );
} );

/**
 * Redirect to the BEW Info page after activation.
 * Sets a transient on activation; the actual redirect happens on the
 * next admin_init to avoid breaking bulk/network activation.
 */
register_activation_hook( __FILE__, function ( $network_wide ) {
    if ( $network_wide || is_network_admin() ) {
        return;
    }
    set_transient( 'bew_activation_redirect', true, 30 );
} );

add_action( 'admin_init', function () {
    // Don't consume the transient on a background AJAX request.
    if ( wp_doing_ajax() ) {
        return;
    }

    if ( ! get_transient( 'bew_activation_redirect' ) ) {
        return;
    }
    delete_transient( 'bew_activation_redirect' );

    // Presence-only check (WordPress core's own bulk-activate convention,
    // e.g. wp-admin/plugins.php) -- the value is never read, so there's
    // nothing here that needs a nonce.
    if ( isset( $_GET['activate-multi'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return;
    }

    wp_safe_redirect( admin_url( 'admin.php?page=bew-dashboard' ) );
    exit;
} );

add_action( 'bew_daily_template_sync', function () {
    if ( class_exists( 'Bosa_Ewc_Sync' ) ) {
        Bosa_Ewc_Sync::run();
    }
} );

add_action('after_setup_theme', function(){
    BEW::instance();
});