<?php

if (!defined('ABSPATH')) exit;

if (!class_exists('BewRatingNotice')) {
    class BewRatingNotice {
        private $current_date;

        public function __construct() {

            $this->current_date = strtotime( 'now' );

            add_action( 'admin_init', [$this, 'check_plugin_install_time'] );
            add_action( 'wp_ajax_bew_rating_maybe_later', [$this, 'bew_rating_maybe_later'] );
            add_action( 'wp_ajax_bew_rating_rate_dismiss', [$this, 'bew_rating_rate_dismiss'] );
            add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
            add_action( 'admin_init',[ $this, 'bew_notice_dismissed' ]);
        }

        public function admin_scripts() {
            wp_enqueue_script('bew-elementor-kit', BEW_URL . 'admin/assets/js/bew-notice.js', array( 'jquery' ), BEW_VERSION, true );
            wp_localize_script( 'bew-elementor-kit', 'BEW_NOTICE',
                array(
                    'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'bew_ajax_notice_nonce' ),
                )
            );
            wp_enqueue_style('bew-admin-notice-style', BEW_URL . 'admin/notices/css/bew-admin-notice.css', array(), BEW_VERSION );
        }

        public function check_plugin_install_time() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            if ( get_user_meta( get_current_user_id(), 'dismiss_bew_rating_notice', true ) ) {
                return;
            }

            $top_dismiss_until = get_user_meta( get_current_user_id(), 'dismiss_bew_rating_top_notice', true );
            if ( $top_dismiss_until && $this->current_date < (int) $top_dismiss_until ) {
                return;
            }

            $install_date = (int) get_option( 'bew_activation_time' );
            if ( ! $install_date ) {
                return;
            }

            if ( ( $this->current_date - $install_date ) < 3 * DAY_IN_SECONDS ) {
                return;
            }

            $maybe_later_time = get_option( 'bew_maybe_later_time' );
            if ( false !== $maybe_later_time && $this->current_date < (int) $maybe_later_time ) {
                return;
            }

            add_action( 'admin_notices', [ $this, 'admin_notice_rating' ] );
        }

        public function bew_rating_maybe_later() {
            $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

            if ( !wp_verify_nonce( $nonce, 'bew_ajax_notice_nonce')  || !current_user_can( 'manage_options' ) ) {
              exit; // Get out of here, the nonce is rotten!
            }

            update_option( 'bew_maybe_later_time', time() + WEEK_IN_SECONDS );
            wp_die();
        }

        public function bew_rating_rate_dismiss() {
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'bew_ajax_notice_nonce' ) || ! current_user_can( 'manage_options' ) ) {
                exit;
            }

            add_user_meta( get_current_user_id(), 'dismiss_bew_rating_notice', true, true );
            wp_die();
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
            if (!current_user_can('manage_options')) {
                return;
            }
            $top_dismiss_until = get_user_meta( get_current_user_id(), 'dismiss_bew_rating_top_notice', true );
            $top_dismissed     = $top_dismiss_until && $this->current_date < (int) $top_dismiss_until;
            if( !get_user_meta( get_current_user_id(), 'dismiss_bew_rating_notice' ) && ! $top_dismissed ){
                $img_url = BEW_URL . 'admin/assets/images/bew-logo.png';
                echo '<div class="bew-notice left-thick-border bew-rating-notice notice notice-success is-dismissible">';
                    echo '<figure class="getting-img">';
                        echo '<img id="" src="'.esc_url( $img_url ).'" />';
                    echo '</figure>';
                    echo '<div class="getting-content">';
                        echo '<h2>' . esc_html( 'Thank you for using BEW - Elementor Addons, Templates & AI Builder to build this Website!' ) . '</h2>';
                        echo '<p class="text">' . esc_html( 'You\'ve been using BEW - Elementor Addons, Templates & AI Builder for a few days. If you\'re enjoying it, would you consider leaving us a 5-star rating on WordPress? Your support helps us improve and reach more users.' ) . '</p>';
                            echo '<div class="quick-link align-items-center">';
                                echo '<a href="' . esc_url( 'https://wordpress.org/support/plugin/bosa-elementor-for-woocommerce/reviews/' ) . '" class="button button-primary bew-rate-plugin" target="_blank">' . esc_html( 'OK, You deserve it!' ) . '</a>';
                                echo '<div class="bew-maybe-later button button-transparent bew-btn-wrapper">
                                    <span class="dashicons dashicons-clock"></span>
                                    <span class="btn-text">' . esc_html( 'May be Later' ) . '</span>
                                    </div>';
                                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'bew-rating-notice-dismissed', 'dismiss_bew_rating_notice' ), 'bew_rating_state', 'bew_rating_nonce' ) ) . '" class="button button-transparent bew-btn-wrapper" >
                                    <span class="dashicons dashicons-saved"></span>
                                    <span class="btn-text">' . esc_html( 'I Already did' ) . '</span>
                                </a>';
                                echo '<a href="' . esc_url( wp_nonce_url( add_query_arg( 'bew-rating-notice-top-dismissed', 'dismiss_bew_rating_top_notice' ), 'bew_rating_top_state', 'bew_rating_top_nonce' ) ) . '" class="bew-top-dissmiss-btn" >
                                    <span class="btn-text">' . esc_html( 'Dismiss' ) . '</span>
                                </a>';
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
            if ( isset( $_GET['bew-rating-notice-dismissed'], $_GET['bew_rating_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['bew_rating_nonce'] ) ), 'bew_rating_state' ) ){
                add_user_meta( get_current_user_id(), 'dismiss_bew_rating_notice', true, true );
            }
            if ( isset( $_GET['bew-rating-notice-top-dismissed'], $_GET['bew_rating_top_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['bew_rating_top_nonce'] ) ), 'bew_rating_top_state' ) ){
                update_user_meta( get_current_user_id(), 'dismiss_bew_rating_top_notice', $this->current_date + MONTH_IN_SECONDS );
            }
        }
    }
}
return new BewRatingNotice();
