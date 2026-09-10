/* global BEWTLPluginInstaller */
/**
 * BEW Template Library — In-editor plugin installer.
 *
 * Handles Elementor editor install/activate clicks via AJAX.
 * Updates button state and displays success/error messages.
 */
( function () {
	'use strict';

	var i18n = ( typeof BEWTLPluginInstaller !== 'undefined' && BEWTLPluginInstaller.i18n )
		? BEWTLPluginInstaller.i18n
		: { installing: 'Installing…', installed: 'Installed ✓', install: 'Install & Activate' };

	var ajaxUrl = ( typeof BEWTLPluginInstaller !== 'undefined' && BEWTLPluginInstaller.ajaxUrl )
		? BEWTLPluginInstaller.ajaxUrl
		: ( typeof ajaxurl !== 'undefined' ? ajaxurl : '' );

	/**
	 * Populates the missing plugins panel after a successful import.
	 * Displays notices without requiring a page reload.
	 *
	 * @param {Object} missingPlugins Keyed by plugin slug; each entry has name,
	 *   description, and nonce (generated server-side and included in the response).
	 */
	window.BEWTLShowMissingPlugins = function ( missingPlugins ) {
		var keys = Object.keys( missingPlugins || {} );
		if ( ! keys.length ) {
			return;
		}

		var container = document.getElementById( 'bew-template-library-missing-plugins' );

		if ( ! container ) {
			container = document.createElement( 'div' );
			container.id = 'bew-template-library-missing-plugins';
			container.className = 'bew-template-library-plugin-prompt';
			// Append inside the Elementor editor panel, falling back to body.
			var target = document.getElementById( 'elementor-panel-inner' )
				|| document.getElementById( 'elementor-editor' )
				|| document.body;
			target.appendChild( container );
		}

		var pluginsHtml = '';
		keys.forEach( function ( slug ) {
			pluginsHtml += buildPluginItemHtml( slug, missingPlugins[ slug ] );
		} );

		container.innerHTML = '<div class="bew-plugin-notice elementor-message elementor-message-warning">'
			+ '<div class="elementor-message-icon"><i class="eicon-info-circle"></i></div>'
			+ '<div class="elementor-message-content">'
			+ '<h3>' + escHtml( i18n.title || 'Required Plugins Missing' ) + '</h3>'
			+ '<p>' + escHtml( i18n.description || 'This template uses widgets from third-party plugins. Install them to display the full design.' ) + '</p>'
			+ '<div class="bew-plugins-list">' + pluginsHtml + '</div>'
			+ '</div>'
			+ '</div>';

		container.style.display = '';
	};

	function buildPluginItemHtml( slug, plugin ) {
		return '<div class="bew-plugin-item" data-plugin-slug="' + escAttr( slug ) + '">'
			+ '<div class="bew-plugin-info">'
			+ '<h4>' + escHtml( plugin.name || slug ) + '</h4>'
			+ '<p>' + escHtml( plugin.description || '' ) + '</p>'
			+ '</div>'
			+ '<div class="bew-plugin-action">'
			+ '<button type="button" class="button button-primary bew-install-plugin"'
			+ ' data-slug="' + escAttr( slug ) + '"'
			+ ' data-nonce="' + escAttr( plugin.nonce || '' ) + '">'
			+ escHtml( i18n.install )
			+ '</button>'
			+ '</div>'
			+ '</div>';
	}

	function escHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function escAttr( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	/**
	 * Reveals the missing-plugins panel after Elementor initializes.
	 * Prevents flashing during editor loading.
	 */
	function revealPrompt() {
		var panel = document.getElementById( 'bew-template-library-missing-plugins' );
		if ( panel ) {
			panel.style.display = '';
		}
	}

	if ( typeof elementor !== 'undefined' && elementor.on ) {
		elementor.on( 'init', revealPrompt );
	} else {
		document.addEventListener( 'DOMContentLoaded', revealPrompt );
	}

	/**
	 * Delegates install plugin button clicks.
	 * Supports dynamically injected prompt HTML after DOM ready.
	 */
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.bew-install-plugin' );
		if ( ! button ) {
			return;
		}

		event.preventDefault();
		installPlugin( button );
	} );

	/**
	 * Trigger plugin installation for the clicked button.
	 *
	 * @param {HTMLElement} button The install button element.
	 */
	function installPlugin( button ) {
		var slug  = button.getAttribute( 'data-slug' );
		var nonce = button.getAttribute( 'data-nonce' );

		if ( ! slug || ! nonce || ! ajaxUrl ) {
			return;
		}

		button.disabled     = true;
		button.textContent  = i18n.installing;
		button.classList.add( 'loading' );

		var body = 'action=bew_template_library_install_plugin'
			+ '&plugin_slug=' + encodeURIComponent( slug )
			+ '&nonce=' + encodeURIComponent( nonce );

		fetch( ajaxUrl, {
			method:  'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body:    body,
		} )
			.then( function ( response ) { return response.json(); } )
			.then( function ( data ) {
				button.classList.remove( 'loading' );

				if ( data.success ) {
					button.textContent = i18n.installed;
					button.classList.add( 'success' );
					showMessage( 'success', data.data.message );
				} else {
					var msg = ( typeof data.data === 'string' ) ? data.data : i18n.install;
					button.textContent = i18n.install;
					button.disabled    = false;
					showMessage( 'error', msg );
				}
			} )
			.catch( function ( error ) {
				button.classList.remove( 'loading' );
				button.textContent = i18n.install;
				button.disabled    = false;
				showMessage( 'error', 'Request failed: ' + error.message );
			} );
	}

	/**
	 * Insert a transient status message at the top of the prompt panel.
	 *
	 * @param {'success'|'error'} type    Message type.
	 * @param {string}            message Text to display.
	 */
	function showMessage( type, message ) {
		var container = document.getElementById( 'bew-template-library-missing-plugins' );
		if ( ! container ) {
			return;
		}

		var div = document.createElement( 'div' );
		div.className = 'bew-message bew-' + type;
		div.textContent = message;
		container.insertBefore( div, container.firstChild );

		var delay = type === 'success' ? 3000 : 5000;

		setTimeout( function () {
			div.classList.add( 'fade-out' );
			setTimeout( function () {
				if ( div.parentNode ) {
					div.parentNode.removeChild( div );
				}
			}, 300 );
		}, delay );
	}
}() );
