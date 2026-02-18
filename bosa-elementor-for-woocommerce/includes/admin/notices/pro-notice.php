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
                    $img_url = BEW_URL . 'assets/images/bew-banner-image.png';
                    echo '<div class="bew-notice left-thick-border upgrade-to-pro bew-pro-notice notice notice-success is-dismissible">';
                        echo '<div class="getting-content">';
                            echo '<h2 class="notice-title">Bosa Elementor For WooCommerce Pro</h2>';
                            echo '<ul class="bew-demo-info-list">';
                            echo '<li> <div><strong>Premium Advanced Elementor Widgets</strong> – Latest collection of premium widgets designed to take your design capabilities to the next level and seamlessly integrate with your WooCommerce store. <a href="https://bosathemes.com/bosa-elementor-for-woocommerce/#bew-widgets" class="notice-link " target="_blank">Explore Widgets</a></div></li>';
                            echo '<li><div><strong>Pre-built Templates Library</strong> – Access a collection of Elementor Page Templates to build your site effortlessly and efficiently.</div></li>';
                            echo '<li> <div><strong>Variety of Shop Templates</strong> – A diverse collection of homepages and inner pages specially designed for your WooCommerce store. </div></li>';
                            echo '<li> <div><strong>Seamless Import System</strong> – Easily import Templates for quick and hassle-free customization.</div></li>';                        
                            echo '</ul>';
                            echo '<div class="quick-link">';
                                echo '<a href="https://bosathemes.com/bosa-elementor-for-woocommerce/#pricing" class="button button-primary bew-upgrade-pro" target="_blank">Upgrade To Pro</a>';
                                echo '<button class="button button-transparent  bew-remind-me-later" >Remind Me Later</button>';
                            echo '</div>';
                         echo '</div>';
                         echo '<div class="getting-img">';
                            echo '<img id="" src="'.esc_url( $img_url ).'" />';
                        echo '</div>';
                        echo '<a href="#" id="bew-upgrade-dismiss" class="admin-notice-dismiss bew-top-dissmiss-btn">Dismiss</a>';
                    echo '</div>';
                }
            }
        }
    }
}
return new Bew_Upgrade_Notice();