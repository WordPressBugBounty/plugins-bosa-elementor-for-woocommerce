<?php
/*
Plugin Name: BEW - Elementor Addons, Templates & AI Builder
Plugin URI: https://bew.bosathemes.com
Description: Enhance your website building experience with powerful Elementor widgets and a versatile, ready-to-use template library & AI-powered Content Generation for seamless customization and improved workflow efficiency.
Version:     2.1.0
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

define('BEW_VERSION', '2.1.2');

define('BEW_FILE', __FILE__);
define('BEW_PLUGIN_BASENAME', plugin_basename(BEW_FILE));
define('BEW_PATH', plugin_dir_path(BEW_FILE));
define('BEW_URL', plugins_url('/', BEW_FILE));

if (!defined('ABSPATH'))
    exit;

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
        if ( $stored && $stored !== BEW_VERSION ) {
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
            
            if (!did_action('elementor/loaded')) {
                add_action( 'admin_notices', array($this, 'admin_notice__error_ele') );
            }else{
                //elementor hooks 
                add_action( 'elementor/frontend/after_enqueue_scripts', array($this, '_scripts') );
                add_action( 'elementor/elements/categories_registered', array($this, 'elementor_category') );
                add_action( 'elementor/widgets/register', array($this, 'register_widgets') );
                add_action( 'elementor/widgets/register', array($this, 'unregister_disabled_widgets'), 999 );
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

            $toggles = BEW_Plugin_Settings::get( 'widget_toggles', [] );

            $free_widgets = [
                'bew_products'          => [ 'bew-elements-products.php',          'BEW_Products' ],
                'bew_products_list'     => [ 'bew-elements-products-list.php',     'BEW_Products_List' ],
                'bew_categories'        => [ 'bew-elements-categories.php',        'BEW_Categories' ],
                'bew_carousel_products' => [ 'bew-elements-carousel-products.php', 'BEW_Carousel_Products' ],
                'bew_blog'              => [ 'bew-elements-blog.php',              'BEW_Blog' ],
                'bew_contact_form_7'    => [ 'bew-elements-contact-form-7.php',    'BEW_Contact_Form_7' ],
                'bew_site_logo'         => [ 'bew-elements-site-logo.php',         'BEW_Site_Logo' ],
                'bew_categories_list'   => [ 'bew-elements-categories-list.php',   'BEW_Categories_List' ],
                'bew_cart'              => [ 'bew-elements-cart.php',              'BEW_Cart' ],
                'bew_dropdown_nav'      => [ 'bew-elements-dropdown-nav.php',      'BEW_Dropdown_Nav' ],
                'bew_my_account'        => [ 'bew-elements-my-account.php',        'BEW_My_Account' ],
                'bew_search'            => [ 'bew-elements-search.php',            'BEW_Search' ],
                'bew_yith_compare'      => [ 'bew-elements-yith-compare.php',      'BEW_Yith_Compare' ],
                'bew_yith_wishlist'     => [ 'bew-elements-yith-wishlist.php',     'BEW_Yith_Wishlist' ],
            ];

            foreach ( $free_widgets as $toggle_id => $info ) {
                if ( isset( $toggles[ $toggle_id ] ) && ! $toggles[ $toggle_id ] ) {
                    continue;
                }
                require_once $this->this_dir . 'widgets/' . $info[0];
                $class = '\\Elementor\\' . $info[1];
                $widgets_manager->register( new $class() );
            }
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
            if ( !class_exists('BEW_Pro') ) {
                $pro_widgets = array(
                    array(
                        'name'       => 'bew-pro-grid-carousel-product',
                        'title'      => esc_html__( 'Woo - Grid Carousel', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-nested-carousel',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'carousel', 'grid', 'woo', 'woo carousel', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-grid-products',
                        'title'      => esc_html__( 'Woo - Grid Products', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-product-related',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'product', 'products', 'bew products', 'grid', 'woo', 'woo products', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-hot-deals',
                        'title'      => esc_html__( 'Woo - Hot Deals', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-single-product',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'hot', 'bew slider', 'woo', 'hot deals', 'woo products', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-image-carousel',
                        'title'      => esc_html__( 'Image Carousel', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-carousel-loop',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'image', 'bew carousel', 'woo', 'image carousel', 'archive', 'bosa' ),
                    ),
                    array(
                        'name'       => 'bew-pro-product-accordion',
                        'title'      => esc_html__( 'Woo - Products Accordion', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-accordion',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'product', 'products', "accordion", "products accordion", 'bew products', 'woo', 'woo products', 'bosa' ),
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
                        'name'       => 'bew-pro-testimonial-slider',
                        'title'      => esc_html__( 'Testimonial Slider', 'bosa-elementor-for-woocommerce' ),
                        'categories' => '["bosa-elementor-for-woocommerce"]',
                        'icon'       => 'bew-pro-widget-promotion eicon-testimonial-carousel',
                        'keywords'   => array( 'bew', 'pro', 'bew pro', 'testimonial', 'bew slider', 'woo', 'testimonial slider', 'archive', 'bosa' ),
                    ),
                );

                $bew_upgrade_url = 'https://bew.bosathemes.com/pricing';
                foreach ( $pro_widgets as &$pw ) {
                    $pw['promotion'] = [
                        'upgrade_url'  => $bew_upgrade_url,
                        'upgrade_text' => esc_html__( 'Upgrade Now', 'bosa-elementor-for-woocommerce' ),
                    ];
                }
                unset( $pw );

                $toggles = BEW_Plugin_Settings::get( 'widget_toggles', [] );
                $promotion_toggle_map = [
                    'bew-pro-grid-carousel-product' => 'bew_pro_grid_carousel',
                    'bew-pro-grid-products'         => 'bew_pro_grid_products',
                    'bew-pro-hot-deals'             => 'bew_pro_hot_deals',
                    'bew-pro-product-accordion'     => 'bew_product_accordion',
                    'bew-pro-product-slider'        => 'bew_pro_product_slider',
                    'bew-pro-product-tabs'          => 'bew_pro_product_tab',
                    'bew-pro-image-carousel'        => 'bew_pro_image_carousel',
                    'bew-pro-testimonial-slider'    => 'bew_pro_testimonial_slider',
                ];

                $pro_widgets = array_filter( $pro_widgets, function ( $widget ) use ( $toggles, $promotion_toggle_map ) {
                    if ( isset( $promotion_toggle_map[ $widget['name'] ] ) ) {
                        $tid = $promotion_toggle_map[ $widget['name'] ];
                        if ( isset( $toggles[ $tid ] ) && ! $toggles[ $tid ] ) {
                            return false;
                        }
                    }
                    return true;
                } );

                $combine_array = array_merge( $promotion_widgets, $pro_widgets );

                $config['promotionWidgets'] = $combine_array;
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