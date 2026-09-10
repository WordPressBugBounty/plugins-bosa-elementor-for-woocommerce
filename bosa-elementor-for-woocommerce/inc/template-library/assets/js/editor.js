/**
 * BEW Template Library — editor toolbar button.
 *
 * @package Bosa_Elementor_WooCommerce
 */

/* global BEWTemplateLibrary, elementor */

( function ( $ ) {
	'use strict';

	var svgIcon =
	'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" aria-hidden="true" focusable="false">'
	+ '<circle fill="#624fe0" cx="150" cy="150" r="150"/>'
	+ '<path fill="#FFFFFF" d="M65.47,148.008h9.405c1.719,0,3.217-0.277,4.494-0.832c1.276-0.554,2.26-1.359,2.954-2.413c0.693-1.053,1.04-2.302,1.04-3.745c0-2.218-0.763-3.911-2.289-5.076c-1.526-1.166-3.593-1.749-6.2-1.749h-6.408v39.283h7.657c1.997,0,3.8-0.291,5.409-0.874c1.609-0.582,2.872-1.512,3.787-2.788c0.915-1.276,1.373-2.913,1.373-4.91c0-1.443-0.264-2.676-0.791-3.704c-0.528-1.026-1.277-1.885-2.247-2.579c-0.972-0.693-2.095-1.193-3.371-1.499c-1.277-0.304-2.663-0.457-4.161-0.457H65.47v-6.492h11.735c2.829,0,5.535,0.306,8.115,0.915c2.58,0.612,4.896,1.568,6.949,2.872c2.052,1.304,3.675,2.982,4.869,5.035c1.193,2.053,1.79,4.523,1.79,7.407c0,3.829-0.902,6.964-2.705,9.404c-1.803,2.443-4.342,4.245-7.615,5.41c-3.274,1.165-7.074,1.747-11.402,1.747h-20.64v-58.259h19.391c3.883,0,7.295,0.541,10.237,1.623c2.94,1.081,5.243,2.705,6.908,4.869c1.664,2.164,2.497,4.91,2.497,8.239c0,2.942-0.833,5.466-2.497,7.574c-1.664,2.109-3.968,3.703-6.908,4.785c-2.942,1.081-6.354,1.623-10.237,1.623H65.47V148.008z"/>'
	+ '<path fill="#FFFFFF" d="M227.927,124.704h13.982l-24.552,61.504l-18.31-38.283l-18.393,38.283l-24.468-61.504h13.983l12.65,36.204l16.229-39.116l16.396,39.116L227.927,124.704z"/>'
	+ '<rect x="110.385" y="123.451" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '<rect x="110.385" y="147.725" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '<rect x="110.385" y="170.843" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '</svg>';

	function addToolbarButton() {
		var $bar = $( '#elementor-panel-header-wrapper, #elementor-panel-header' ).first();

		if ( $bar.length ) {
			renderButton( $bar );
			return;
		}

		var observer = new MutationObserver( function ( _m, obs ) {
			var $found = $( '#elementor-panel-header-wrapper' );
			if ( $found.length ) {
				renderButton( $found );
				obs.disconnect();
			}
		} );

		observer.observe( document.body, { childList: true, subtree: true } );
		setTimeout( function () { observer.disconnect(); }, 15000 );
	}

	function renderButton( $container ) {
		if ( $( '#bew-template-library-btn' ).length ) {
			return;
		}

		var label = BEWTemplateLibrary.i18n.button_label || 'BEW Templates';
		var $target = $container.find( '.elementor-header-buttons, #elementor-panel-header-title' ).first();
		if ( ! $target.length ) {
			$target = $container;
		}

		$( '<button>', {
			id:              'bew-template-library-btn',
			type:            'button',
			'aria-haspopup': 'dialog',
			'aria-label':    label,
		} )
			.html( svgIcon )
			.on( 'click', openLibrary )
			.appendTo( $target );
	}

	function openLibrary( e ) {
		if ( e ) {
			e.preventDefault();
		}

		if ( typeof window.openBEWLibraryModal === 'function' && window.openBEWLibraryModal() ) {
			return;
		}

		if ( typeof window.$e !== 'undefined' && typeof window.$e.run === 'function' ) {
			window.$e.run( 'bew-library/open' );
		}
	}

	window.addEventListener( 'message', function ( event ) {
		if ( ! event.data || 'bew-tk-open-library' !== event.data.type ) {
			return;
		}
		openLibrary();
	} );

	function boot() {
		if ( typeof elementor !== 'undefined' && elementor.on ) {
			elementor.on( 'panel:init', addToolbarButton );
			elementor.on( 'preview:loaded', addToolbarButton );
		}
		$( addToolbarButton );
	}

	boot();

} )( jQuery );
