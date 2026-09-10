<?php
/**
 * Preloader module bootstrap.
 *
 * @package Bosa_Elementor_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'elementor/init', function () {
	require_once __DIR__ . '/class-site-settings.php';
	require_once __DIR__ . '/class-frontend.php';

	add_action( 'elementor/kit/register_tabs', function ( $kit ) {
		$kit->register_tab( 'bew-preloader', BEW_Preloader_Tab::class );
	} );

	new BEW_Preloader_Frontend();
} );

// Loaded in the top editor window (not the preview iframe) so it can watch which
// panel is open and reveal the overlay only while Site Settings is active.
add_action( 'elementor/editor/after_enqueue_scripts', function () {
	$rel_path = 'modules/preloader/assets/js/site-settings-preview.js';
	$version  = file_exists( BEW_PATH . $rel_path ) ? (string) filemtime( BEW_PATH . $rel_path ) : BEW_VERSION;

	wp_enqueue_script(
		'bew-preloader-site-settings-preview',
		BEW_URL . $rel_path,
		[ 'elementor-editor' ],
		$version,
		true
	);

	wp_localize_script( 'bew-preloader-site-settings-preview', 'bewPreloaderPreviewData', [
		'imagesBaseUrl' => BEW_URL . 'modules/preloader/assets/images/',
	] );
} );
