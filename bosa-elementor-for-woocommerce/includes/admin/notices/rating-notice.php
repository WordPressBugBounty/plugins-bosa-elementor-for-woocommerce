<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('BewRatingNotice')) {
    class BewRatingNotice {
        private $current_date;

        public function __construct() {

            $this->current_date = strtotime( 'now' );

            add_action( 'admin_init', [$this, 'check_plugin_install_time'] );
            add_action( 'wp_ajax_bew_rating_maybe_later', [$this, 'bew_rating_maybe_later'] );
            add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
        }

        public function admin_scripts() {
            wp_enqueue_script('bew-elementor-kit', BEW_URL . 'assets/js/bew-notice.js', array( 'jquery' ));
            wp_localize_script( 'bew-elementor-kit', 'BEW_NOTICE', 
                array( 
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'bew_ajax_notice_nonce' ),
                ) 
            );
            wp_enqueue_style('bew-admin-notice-style', BEW_URL . 'includes/admin/notices/css/bew-admin-notice.css' );
        }

        public function check_plugin_install_time() {   
            $install_date = (int)get_option('bew_activation_time');
            $show_date = $this->current_date - strtotime('6 days');
            $time_difference = ($this->current_date - $install_date);
            
            if ( false == get_option('bew_maybe_later_time' ) && ( $time_difference >= abs($show_date ) ) ){
                add_action( 'admin_notices', [$this, 'admin_notice_rating' ]);
            }elseif ( $this->current_date >= (int)get_option('bew_maybe_later_time') ) {
                add_action( 'admin_notices', [$this, 'admin_notice_rating' ]);
            }
        }

        public function bew_rating_maybe_later() {
            $nonce = $_POST['nonce'];

            if ( !wp_verify_nonce( $nonce, 'bew_ajax_notice_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            update_option( 'bew_maybe_later_time', strtotime('3 days') );
        }

        /**
         * To Check Plugin is installed or not
         * @since Bosa Elementor Addons and Templates for WooCommerce 1.0.0
         */
        function _is_plugin_installed($plugin_path ) {
            $installed_plugins = get_plugins();
            return isset( $installed_plugins[ $plugin_path ] );
        }

        function admin_notice_rating() {
            if (!current_user_can('activate_plugins')) {
                return;
            }
            if( !get_user_meta( get_current_user_id(), 'dismiss_bew_rating_notice' ) ){
                $img_url = BEW_URL . 'assets/images/bew-logo.png';
                echo '<div class="bew-notice left-thick-border bew-rating-notice">';
                    echo '<figure class="getting-img">';
                        echo '<img id="" src="'.esc_url( $img_url ).'" />';
                    echo '</figure>';
                    echo '<div class="getting-content">';
                        echo '<h2>Thank you for using Bosa Elementor for WooCommerce to build this Website!</h2>';
                        echo '<p class="text">Could you please do us a BIG favour and give it a 5-star rating on Wordpress? Just to help us spread the word and boost our motivation.</p>';
                            echo '<div class="quick-link align-items-center">';
                                echo '<a href="https://wordpress.org/support/plugin/bosa-elementor-for-woocommerce/reviews/" class="button button-primary" target="_blank">OK, You deserve it!</a>';
                                echo '<button class="button button-transparent dashicons dashicons-clock clock-symbol bew-maybe-later" >Maybe Later</button>';
                                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'bew-rating-notice-dismissed', 'dismiss_bew_rating_notice' ), 'bew_rating_state', 'bew_rating_nonce' ) ) . '" class="button button-transparent tick-symbol " target="_blank"> I Already did</a>';
                        echo '</div>';
                    echo '</div>';
                echo '</div>';
            }
            
        }

         /**
         * Registers admin notice for current user.
         * 
         */
        function bew_notice_dismissed() {
            if ( isset( $_GET['bew-rating-notice-dismissed'] ) && wp_verify_nonce($_GET['bew_rating_nonce'], 'bew_rating_state') ){
                add_user_meta( get_current_user_id(), 'dismiss_bew_rating_notice', true, true );
            }
        }
    }
}
return new BewRatingNotice();