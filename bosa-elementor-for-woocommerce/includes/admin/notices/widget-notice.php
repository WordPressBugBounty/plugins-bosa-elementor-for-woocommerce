<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('BewWidgetNotice')) {
    class BewWidgetNotice {

        public function __construct() {

            add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
            if ( !get_option('bew_widget_update_dismiss_notice_' . BEW_VERSION) ) {
                add_action( 'admin_init', [$this, 'render_notice'] );
            }
            add_action( 'wp_ajax_bew_widget_update', [$this, 'bew_widget_update'] );

        }

        public function admin_scripts() {
            wp_localize_script( 'bew-elementor-kit', 'BEW_NEW', 
                array( 
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'bew_new_widget_nonce' ),
                ) 
            );
        }

        public function render_notice() {
            add_action( 'admin_notices', [$this, 'render_plugin_update_notice' ]);
        }

        public function bew_widget_update() {
            $nonce = $_POST['nonce'];

            if ( !wp_verify_nonce( $nonce, 'bew_new_widget_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            add_option( 'bew_widget_update_dismiss_notice_' . BEW_VERSION, true );
        }   

        /**
         * To Check Plugin is installed or not
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function _is_plugin_installed($plugin_path ) {
            $installed_plugins = get_plugins();
            return isset( $installed_plugins[ $plugin_path ] );
        }

        function render_plugin_update_notice() {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            $pro_img_url = BEW_URL . 'assets/images/bew-new-widget.png';
            echo '<div class="bew-notice left-thick-border bew-new-widget-notice">';
                echo '<div class="getting-content">';
                    echo '<p class="notice-sub-title">New Features</p>';
                    echo '<h2 class="notice-title">Introducing New Widgets</h2>';
                    echo '<p class="text"> We are excited to annouce that we have added new Widgets, Templates and other Elementor Features to enhance your website building experience. Unlock more design possibilities with our latest widgets, crafted to elevate your website-building experience with enhanced functionality and customization options. Stay tuned for the updates!</p>';
                    echo '<div class="quick-link align-items-center">';
                            echo '<a href="https://bosathemes.com/bosa-elementor-for-woocommerce/#pricing" class="button button-primary" target="_blank">Upgrade to Pro</a>';
                            echo '<a href="https://bosathemes.com/bosa-elementor-for-woocommerce/#bew-widgets" class="button button-primary" target="_blank">View Widgets</a>';
                    echo '</div>';
                echo '</div>';
                echo '<a href="#" class="admin-notice-dismiss" id="widget-dismiss"><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></a>';
                echo '<figure class="getting-img">';
                    echo '<img id="" src="'.esc_url( $pro_img_url ).'" />';
                echo '</figure>';
            echo '</div>';
        }   
    }
}
return new BewWidgetNotice();