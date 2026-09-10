<?php
/**
 * Back to Top module bootstrap.
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
		$kit->register_tab( 'bew-back-to-top', BEW_Back_To_Top_Tab::class );
	} );

	new BEW_Back_To_Top_Frontend();
} );

// Loaded in the top editor window (not the preview iframe) so it can watch which
// panel is open and reveal the button only while Site Settings is active.
add_action( 'elementor/editor/after_enqueue_scripts', function () {
	wp_enqueue_script(
		'bew-back-to-top-site-settings-preview',
		BEW_URL . 'modules/back-to-top/assets/js/site-settings-preview.js',
		[ 'elementor-editor' ],
		BEW_VERSION,
		true
	);
} );
