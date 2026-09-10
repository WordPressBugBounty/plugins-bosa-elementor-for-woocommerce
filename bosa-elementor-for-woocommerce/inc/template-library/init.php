<?php
/**
 * Template Library module bootstrap.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BEW_TL_DIR',        __DIR__ . '/' );
define( 'BEW_TL_URL',        plugin_dir_url( __FILE__ ) );
define( 'BEW_TL_REMOTE_URL', 'https://demo.bosathemes.com/bosa' );
define( 'BEW_TL_REMOTE_TTL', DAY_IN_SECONDS );

/**
 * Shared key identifying requests as coming from a genuine BEW install, sent
 * as the X-Bosa-TK-Key header on every remote-client request. Must match
 * BOSA_TEMPLATE_KITS_DEMO_MANAGER_CLIENT_KEY on the hub.
 */
define( 'BEW_TL_API_KEY', 'b0e816301bf4f1ba6f37d56400f463e14b098fbfae1fd749' );

require_once BEW_TL_DIR . 'inc/helpers.php';
require_once BEW_TL_DIR . 'inc/class-cache.php';
require_once BEW_TL_DIR . 'inc/class-remote-client.php';
require_once BEW_TL_DIR . 'inc/class-sync.php';
require_once BEW_TL_DIR . 'inc/class-template-library.php';
require_once BEW_TL_DIR . 'api/class-api.php';
require_once BEW_TL_DIR . 'elementor/class-modal.php';
require_once BEW_TL_DIR . 'inc/ai-field-map.php';

add_action( 'plugins_loaded', function () {
	( new Bosa_Ewc_Template_Library() )->run();

	add_action( 'wp_ajax_bew_template_library_install_plugin', function () {
		require_once BEW_TL_DIR . 'admin/class-editor-prompt.php';
		require_once BEW_TL_DIR . 'frontend/class-widget-plugin-map.php';
		require_once BEW_TL_DIR . 'frontend/class-plugin-installer.php';
		( new Bosa_Ewc_Plugin_Installer() )->handle_plugin_installation();
	} );
} );
