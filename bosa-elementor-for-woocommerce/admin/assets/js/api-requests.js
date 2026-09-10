/* global bewAdmin */
( function ( window ) {
	'use strict';

	/**
	 * Low-level AJAX wrapper.
	 *
	 * @param {string}   action   WP AJAX action name.
	 * @param {Object}   data     Extra POST fields.
	 * @param {Function} [cb]     Optional callback ( success, responseData ).
	 */
	function bewApiRequest( action, data, cb ) {
		var body = new FormData();
		body.append( 'action',   action );
		body.append( '_ajax_nonce', bewAdmin.nonce );

		if ( data && typeof data === 'object' ) {
			Object.keys( data ).forEach( function ( key ) {
				body.append( key, data[ key ] );
			} );
		}

		fetch( bewAdmin.ajaxUrl, {
			method      : 'POST',
			credentials : 'same-origin',
			body        : body,
		} )
		.then( function ( response ) {
			return response.json();
		} )
		.then( function ( result ) {
			if ( typeof cb === 'function' ) {
				cb( result.success, result.data );
			}
		} )
		.catch( function () {
			if ( typeof cb === 'function' ) {
				cb( false, null );
			}
		} );
	}

	/**
	 * Collect toggles from the page and POST them via bew_save_settings.
	 *
	 * @param {Object}      toggles   Key-value pairs { setting_key: 0|1 }.
	 * @param {HTMLElement} [saveBtn] Button to disable during request.
	 */
	function bewSaveSettings( toggles, saveBtn ) {
		if ( saveBtn ) {
			saveBtn.disabled = true;
		}

		var notice = saveBtn ? saveBtn.nextElementSibling : null;

		bewApiRequest( 'bew_save_settings', { settings: JSON.stringify( toggles ) }, function ( success ) {
			if ( saveBtn ) {
				saveBtn.disabled = false;
			}

			if ( notice && notice.classList.contains( 'bew-notice' ) ) {
				notice.textContent  = success ? bewAdmin.i18n.saved : bewAdmin.i18n.error;
				notice.className    = 'bew-notice ' + ( success ? 'bew-notice-success' : 'bew-notice-error' );
				notice.style.display = 'inline-block';
				setTimeout( function () {
					notice.style.display = 'none';
				}, 2500 );
			}
		} );
	}

	// Expose globally so admin-settings.js can call them.
	window.bewApiRequest   = bewApiRequest;
	window.bewSaveSettings = bewSaveSettings;
}( window ) );
