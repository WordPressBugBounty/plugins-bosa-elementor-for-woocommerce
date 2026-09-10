/**
 * BEW Template Library — canvas empty-view button.
 *
 * Injects only into Elementor's "Drag widget here" placeholder
 * (.elementor-add-new-section), not column/widget empty views.
 *
 * @package Bosa_Elementor_WooCommerce
 */

( function () {
	'use strict';

	var PLACEHOLDER_SELECTOR = '.elementor-add-new-section';

	var svgIcon =
	'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" aria-hidden="true" focusable="false">'
	+ '<circle fill="#624fe0" cx="150" cy="150" r="150"/>'
	+ '<path fill="#FFFFFF" d="M65.47,148.008h9.405c1.719,0,3.217-0.277,4.494-0.832c1.276-0.554,2.26-1.359,2.954-2.413c0.693-1.053,1.04-2.302,1.04-3.745c0-2.218-0.763-3.911-2.289-5.076c-1.526-1.166-3.593-1.749-6.2-1.749h-6.408v39.283h7.657c1.997,0,3.8-0.291,5.409-0.874c1.609-0.582,2.872-1.512,3.787-2.788c0.915-1.276,1.373-2.913,1.373-4.91c0-1.443-0.264-2.676-0.791-3.704c-0.528-1.026-1.277-1.885-2.247-2.579c-0.972-0.693-2.095-1.193-3.371-1.499c-1.277-0.304-2.663-0.457-4.161-0.457H65.47v-6.492h11.735c2.829,0,5.535,0.306,8.115,0.915c2.58,0.612,4.896,1.568,6.949,2.872c2.052,1.304,3.675,2.982,4.869,5.035c1.193,2.053,1.79,4.523,1.79,7.407c0,3.829-0.902,6.964-2.705,9.404c-1.803,2.443-4.342,4.245-7.615,5.41c-3.274,1.165-7.074,1.747-11.402,1.747h-20.64v-58.259h19.391c3.883,0,7.295,0.541,10.237,1.623c2.94,1.081,5.243,2.705,6.908,4.869c1.664,2.164,2.497,4.91,2.497,8.239c0,2.942-0.833,5.466-2.497,7.574c-1.664,2.109-3.968,3.703-6.908,4.785c-2.942,1.081-6.354,1.623-10.237,1.623H65.47V148.008z"/>'
	+ '<path fill="#FFFFFF" d="M227.927,124.704h13.982l-24.552,61.504l-18.31-38.283l-18.393,38.283l-24.468-61.504h13.983l12.65,36.204l16.229-39.116l16.396,39.116L227.927,124.704z"/>'
	+ '<rect x="110.385" y="123.451" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '<rect x="110.385" y="147.725" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '<rect x="110.385" y="170.843" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '</svg>';

	/**
	 * Resolve the template source ID from the editor frame or localized data.
	 *
	 * @return {string}
	 */
	function getSourceId() {
		try {
			var top = window.top;
			if (
				top.elementorConfig &&
				top.elementorConfig.bew_template_library &&
				top.elementorConfig.bew_template_library.source
			) {
				return top.elementorConfig.bew_template_library.source;
			}
		} catch ( e ) {}

		return ( typeof BEWTemplateLibraryCanvas !== 'undefined' && BEWTemplateLibraryCanvas.source )
			? BEWTemplateLibraryCanvas.source
			: 'bew';
	}

	/**
	 * Return the Elementor editor window (top frame).
	 *
	 * @return {Window}
	 */
	function getEditorWindow() {
		try {
			return window.top;
		} catch ( e ) {
			return window.parent;
		}
	}

	/**
	 * Navigate the open library modal to the Templates source tab.
	 *
	 * @param {Window} editorWin Elementor editor window.
	 * @param {string} sourceId  Registered template source ID.
	 */
	function openBEWTab( editorWin, sourceId ) {
		if ( editorWin && editorWin.$e && typeof editorWin.$e.route === 'function' ) {
			editorWin.$e.route( 'library/templates/' + sourceId );
		}
	}

	/**
	 * Open the Template Library modal via the editor parent frame.
	 */
	function openLibrary( e ) {
		if ( e ) {
			e.preventDefault();
		}

		var editorWin = getEditorWindow();

		try {
			if ( editorWin && typeof editorWin.openBEWLibraryModal === 'function' && editorWin.openBEWLibraryModal() ) {
				return;
			}

			if ( editorWin && editorWin.$e && typeof editorWin.$e.run === 'function' ) {
				editorWin.$e.run( 'bew-library/open' );
				return;
			}
		} catch ( err ) {}

		try {
			window.top.postMessage( { type: 'bew-tk-open-library' }, '*' );
		} catch ( err2 ) {}
	}

	/**
	 * @return {HTMLButtonElement}
	 */
	function makeButton() {
		var btn = document.createElement( 'button' );
		btn.type        = 'button';
		btn.className   = 'bew-template-library-empty-btn';
		btn.title       = 'BEW Templates';
		btn.setAttribute( 'aria-label', 'BEW Templates' );
		btn.innerHTML   = svgIcon;
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			openLibrary( e );
		} );
		return btn;
	}

	/**
	 * Remove buttons injected outside the real empty placeholder.
	 */
	function cleanupMisplaced() {
		document.querySelectorAll( '.bew-template-library-empty-btn' ).forEach( function ( btn ) {
			if ( ! btn.closest( PLACEHOLDER_SELECTOR ) ) {
				btn.remove();
			}
		} );
	}

	/**
	 * Inject button into the "Drag widget here" action row only.
	 *
	 * @param {Element} placeholder .elementor-add-new-section node.
	 */
	function inject( placeholder ) {
		if ( ! placeholder.querySelector( '.elementor-add-section-drag-title' ) ) {
			return;
		}

		if ( placeholder.querySelector( '.bew-template-library-empty-btn' ) ) {
			return;
		}

		var btn = makeButton();
		var anchor = placeholder.querySelector( '.elementor-add-template-button' )
			|| placeholder.querySelector( '.elementor-add-section-button' );

		if ( anchor ) {
			anchor.insertAdjacentElement( 'afterend', btn );
			return;
		}

		placeholder.appendChild( btn );
	}

	/**
	 * Scan and sync all valid empty placeholders.
	 */
	function scan() {
		cleanupMisplaced();
		document.querySelectorAll( PLACEHOLDER_SELECTOR ).forEach( inject );
	}

	var observer = new MutationObserver( scan );

	function boot() {
		scan();
		observer.observe( document.body, { childList: true, subtree: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
