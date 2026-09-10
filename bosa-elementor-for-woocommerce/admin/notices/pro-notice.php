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
                $dismiss_until = get_option( 'bew_upgrade_notice_dismiss_' . BEW_VERSION );
                if ( ! $dismiss_until || $this->current_date >= (int) $dismiss_until ) {
                    add_action( 'admin_notices', [$this, 'admin_notice_bew_pro' ]);
                }
            }
        }

        public function bew_remind_me_later() {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

            if ( !wp_verify_nonce( $nonce, 'bew_upgrade_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            update_option( 'bew_remind_me_later_time', time() + WEEK_IN_SECONDS );
        }

        public function upgrade_dismiss() {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

            if ( !wp_verify_nonce( $nonce, 'bew_upgrade_dismiss_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            update_option( 'bew_upgrade_notice_dismiss_' . BEW_VERSION, $this->current_date + MONTH_IN_SECONDS );
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
                    $img_url = BEW_URL . 'admin/assets/images/bew-banner.png';
                    echo '<div class="bew-notice left-thick-border upgrade-to-pro bew-pro-notice notice notice-success is-dismissible">';
                    echo '<div class="getting-img">';
                            echo '<img id="" src="'.esc_url( $img_url ).'" />';
                        echo '</div>';
                        echo '<div class="getting-content">';
                            echo '<h2 class="notice-title">' . esc_html( 'BEW Pro' ) . '</h2>';
                            echo '<div class="bew-demo-info-list">';
                            echo '<div><p>' . wp_kses( 'Enhance your website building experience with powerful Elementor widgets, modules, and 2,500+ready-to-use template library & AI-powered Content Generation for seamless customization and improved workflow efficiency.', array( 'br' => array() ) ) . '</p></div>';
                            echo '</div>';
                            echo '<div class="quick-link">';
                                echo '<a href="' . esc_url( 'https://bew.bosathemes.com/pricing' ) . '" class="button button-primary bew-upgrade-pro" target="_blank">' . esc_html( 'Upgrade To Pro' ) . '</a>';
                                echo '<button class="button button-transparent  bew-remind-me-later" >' . esc_html( 'Remind Me Later' ) . '</button>';
                            echo '</div>';
                         echo '</div>';
                        echo '<a href="#" id="bew-upgrade-dismiss" class="admin-notice-dismiss bew-top-dissmiss-btn">' . esc_html( 'Dismiss' ) . '</a>';
                    echo '</div>';
                }
            }
        }
    }
}
return new Bew_Upgrade_Notice();
