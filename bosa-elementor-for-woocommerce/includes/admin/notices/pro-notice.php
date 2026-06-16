<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('Bew_Upgrade_Notice')) {
    class Bew_Upgrade_Notice {
        private $current_date;

        public function __construct() {

            $this->current_date = strtotime( 'now' );
            add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
            add_action( 'admin_init', [$this, 'check_pro_install'] );
            add_action( 'wp_ajax_bew_remind_me_later', [$this, 'bew_remind_me_later'] );
            add_action( 'wp_ajax_bew_upgrade_notice_dismiss', [$this, 'upgrade_dismiss'] );
        }

         public function admin_scripts() {
            wp_localize_script( 'bew-elementor-kit', 'BEW_UPGRADE', 
                array( 
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'bew_upgrade_nonce' ),
                    'dismiss_nonce'     => wp_create_nonce( 'bew_upgrade_dismiss_nonce' ),
                ) 
            );
        }

        public function check_pro_install() { 

            if ( $this->current_date >= (int)get_option('bew_remind_me_later_time') ) {
                if ( !get_option('bew_upgrade_notice_dismiss_' . BEW_VERSION) ) {
                    add_action( 'admin_notices', [$this, 'admin_notice_bew_pro' ]);
                }
            }
        }

        public function bew_remind_me_later() {
            $nonce = $_POST['nonce'];

            if ( !wp_verify_nonce( $nonce, 'bew_upgrade_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            update_option( 'bew_remind_me_later_time', strtotime('3 days') );
        }

        public function upgrade_dismiss() {
            $nonce = $_POST['nonce'];

            if ( !wp_verify_nonce( $nonce, 'bew_upgrade_dismiss_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            add_option( 'bew_upgrade_notice_dismiss_' . BEW_VERSION, true );
        }

        /**
         * To Check Plugin is installed or not
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function _is_plugin_installed($plugin_path ) {
            $installed_plugins = get_plugins();
            return isset( $installed_plugins[ $plugin_path ] );
        }

        function admin_notice_bew_pro() {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            $plugin = 'bosa-elementor-for-woocommerce-pro/bosa-elementor-for-woocommerce-pro.php';
            if ( !$this->_is_plugin_installed( $plugin ) ) {
                if( !get_user_meta( get_current_user_id(), 'dismiss_bew_upgrade_notice' ) ){
                    $img_url = BEW_URL . 'assets/images/bew-banner.png';
                    echo '<div class="bew-notice left-thick-border upgrade-to-pro bew-pro-notice notice notice-success is-dismissible">';
                    echo '<div class="getting-img">';
                            echo '<img id="" src="'.esc_url( $img_url ).'" />';
                        echo '</div>';
                        echo '<div class="getting-content">';
                            echo '<h2 class="notice-title">Bosa Elementor For WooCommerce Pro</h2>';
                            echo '<div class="bew-demo-info-list">';
                            echo ' <div> <p> Build a stunning WooCommerce store faster with powerful Elementor-based widgets, professionally designed templates </br> and seamless imports—giving you everything you need to customize, launch, and grow with confidence.</p></div>';       
                            echo '</div>';
                            echo '<div class="quick-link">';
                                echo '<a href="https://bosathemes.com/bosa-elementor-for-woocommerce/#pricing" class="button button-primary bew-upgrade-pro" target="_blank">Upgrade To Pro</a>';
                                echo '<button class="button button-transparent  bew-remind-me-later" >Remind Me Later</button>';
                            echo '</div>';
                         echo '</div>';
                        echo '<a href="#" id="bew-upgrade-dismiss" class="admin-notice-dismiss bew-top-dissmiss-btn">Dismiss</a>';
                    echo '</div>';
                }
            }
        }
    }
}
return new Bew_Upgrade_Notice();