/* global BEWTemplateLibraryModal, jQuery */
/**
 * Pre-import Dependency Modal.
 *
 * Public API: window.BEWDependencyModal.show( missingPlugins, onInstalled, onSkip, onCancel )
 *   missingPlugins — REST response keyed by plugin slug: { name, nonce, description, widgets[], install_url }
 *   onInstalled    — import using the ORIGINAL content (new plugins can then render their widgets).
 *   onSkip         — import using the HTML-placeholder-converted content.
 *   onCancel       — import does not proceed.
 */
window.BEWDependencyModal = ( function () {
	'use strict';

	var _overlay     = null;
	var _plugins     = {};
	var _onInstalled = null;
	var _onSkip      = null;
	var _onCancel    = null;

	/* ----------------------------------------------------------------
	 * Public
	 * -------------------------------------------------------------- */

	function show( missingPlugins, onInstalled, onSkip, onCancel ) {
		if ( ! missingPlugins || ! Object.keys( missingPlugins ).length ) {
			onSkip && onSkip();
			return;
		}

		_plugins     = _normalizePlugins( missingPlugins );
		_onInstalled = onInstalled || function () {};
		_onSkip      = onSkip      || function () {};
		_onCancel    = onCancel    || function () {};

		_render();
	}

	/* ----------------------------------------------------------------
	 * Private — plugin helpers
	 * -------------------------------------------------------------- */

	function _normalizePlugins( missingPlugins ) {
		var normalized = {};
		if ( ! missingPlugins ) { return normalized; }

		if ( Array.isArray( missingPlugins ) ) {
			missingPlugins.forEach( function ( plugin ) {
				if ( ! plugin ) { return; }
				var slug = plugin.slug || plugin.plugin_slug || '';
				if ( slug ) { normalized[ slug ] = plugin; }
			} );
			return normalized;
		}

		for ( var key in missingPlugins ) {
			if ( missingPlugins.hasOwnProperty( key ) ) {
				normalized[ key ] = missingPlugins[ key ];
			}
		}
		return normalized;
	}

	function _pluginSlug( key, plugin ) {
		if ( plugin && plugin.slug )         { return plugin.slug; }
		if ( plugin && plugin.plugin_slug )  { return plugin.plugin_slug; }
		if ( key && key.indexOf( 'unknown_' ) !== 0 ) { return key; }
		return key;
	}

	function _pluginName( key, plugin, slug ) {
		if ( plugin && plugin.plugin_name ) { return plugin.plugin_name; }
		if ( plugin && plugin.name )        { return plugin.name; }
		return slug.replace( /^unknown_/, '' ).replace( /-/g, ' ' );
	}

	function _isInstallable( key, plugin ) {
		if ( ! plugin ) { return false; }
		var slug = _pluginSlug( key, plugin );
		return !! ( slug && slug.indexOf( 'unknown_' ) !== 0 );
	}

	/* ----------------------------------------------------------------
	 * Private — i18n helper
	 * -------------------------------------------------------------- */

	function _t( key, fallback ) {
		var i18n = ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.i18n )
			? BEWTemplateLibraryModal.i18n
			: {};
		return i18n[ key ] || fallback;
	}

	/* ----------------------------------------------------------------
	 * Private — render
	 * -------------------------------------------------------------- */

	function _render() {
		_close();

		_overlay = document.createElement( 'div' );
		_overlay.className = 'bosa-dep-overlay';
		_overlay.innerHTML = _buildHTML();

		document.body.appendChild( _overlay );

		// Single delegated listener on the overlay.
		// Avoids double-firing and stopPropagation conflicts from direct listeners.
		_overlay.addEventListener( 'click', function ( e ) {
			var target = e && e.target ? e.target : null;
			// Walk up from text nodes to their parent element.
			if ( target && target.nodeType !== 1 ) {
				target = target.parentElement;
			}
			if ( ! target || ! target.closest ) { return; }

			if ( target.closest( '.bosa-dep-close' ) )       { _cancel();    return; }
			if ( target.closest( '.bosa-dep-install-all' ) ) { _installAll(); return; }
			if ( target.closest( '.bosa-dep-skip' ) )        { _skip();      return; }
		} );
	}

	function _buildHTML() {
		// The Install & Activate button is ALWAYS rendered so it is always
		// reachable. _installAll() falls back to _skip() when no targets exist.
		return '<div class="bosa-dep-modal">'

			+ '<div class="bosa-dep-modal-head">'
			+   '<h2>' + _esc( _t( 'dep_title', 'Required Plugins' ) ) + '</h2>'
			+   '<button type="button" class="bosa-dep-close" aria-label="Close">&times;</button>'
			+ '</div>'

			+ '<div class="bosa-dep-modal-body">'
			+   '<p>' + _esc( _t( 'dep_desc', 'This template requires the following plugins. Select which ones to install before importing.' ) ) + '</p>'
			+   '<ul class="bosa-dep-list">' + _buildItems() + '</ul>'
			+ '</div>'

			// Footer — Install left, Skip right.
			+ '<div class="bosa-dep-modal-foot">'
			+   '<button type="button" class="button button-primary bosa-dep-install-all">'
			+     _esc( _t( 'dep_install_all', 'Install & Activate' ) )
			+   '</button>'
			+   '<button type="button" class="button bosa-dep-skip">'
			+     _esc( _t( 'dep_skip', 'Skip & Import Anyway' ) )
			+   '</button>'
			+ '</div>'

			+ '</div>';
	}

	function _buildItems() {
		var html = '';
		for ( var key in _plugins ) {
			if ( ! _plugins.hasOwnProperty( key ) ) { continue; }
			var p           = _plugins[ key ];
			var slug        = _pluginSlug( key, p );
			var installable = _isInstallable( key, p );
			var label       = _pluginName( key, p, slug );

			html += '<li class="bosa-dep-item' + ( installable ? '' : ' bosa-dep-item--info' ) + '" data-slug="' + _escAttr( slug ) + '">';

			if ( installable ) {
				html
					+= '<label class="bosa-dep-label">'
					+    '<input type="checkbox"'
					+      ' class="bosa-dep-check"'
					+      ' data-slug="'  + _escAttr( slug )         + '"'
					+      ' data-nonce="' + _escAttr( ( p && p.nonce ) ? p.nonce : '' ) + '"'
					+      ' checked>'
					+    '<span class="bosa-dep-plugin-name">' + _esc( label ) + '</span>'
					+ '</label>'
					+ '<span class="bosa-dep-status" data-slug="' + _escAttr( slug ) + '"></span>';
			} else {
				html
					+= '<div class="bosa-dep-label bosa-dep-label--info">'
					+    '<span class="bosa-dep-plugin-name">' + _esc( label ) + '</span>'
					+ '</div>';
			}

			html += '</li>';
		}
		return html;
	}

	/* ----------------------------------------------------------------
	 * Private — install flow
	 * -------------------------------------------------------------- */

	function _getInstallTargets() {
		var targets = [];

		// Prefer checked boxes; if none selected, use all checkboxes.
		var checked = _overlay.querySelectorAll( '.bosa-dep-check:checked' );
		var boxes   = checked.length
			? checked
			: _overlay.querySelectorAll( '.bosa-dep-check' );

		Array.prototype.forEach.call( boxes, function ( checkbox ) {
			var slug  = checkbox.getAttribute( 'data-slug' );
			var nonce = checkbox.getAttribute( 'data-nonce' );
			if ( slug ) {
				targets.push( { slug: slug, nonce: nonce || '' } );
			}
		} );

		// Fallback: if checkbox rows were not rendered for any reason, still build
		// install targets from normalized plugin data.
		if ( ! targets.length ) {
			for ( var key in _plugins ) {
				if ( ! _plugins.hasOwnProperty( key ) ) { continue; }
				var plugin = _plugins[ key ];
				if ( ! _isInstallable( key, plugin ) ) { continue; }
				targets.push( {
					slug: _pluginSlug( key, plugin ),
					nonce: ( plugin && plugin.nonce ) ? plugin.nonce : '',
				} );
			}
		}

		return _dedupeInstallTargets( targets );
	}

	function _dedupeInstallTargets( targets ) {
		var seen = {};
		var out  = [];

		targets.forEach( function ( target ) {
			if ( ! target || ! target.slug || seen[ target.slug ] ) {
				return;
			}
			seen[ target.slug ] = true;
			out.push( target );
		} );

		return out;
	}

	function _installAll() {
		if ( ! _overlay ) { return; }

		var modal   = _overlay.querySelector( '.bosa-dep-modal' );
		var btn     = _overlay.querySelector( '.bosa-dep-install-all' );
		var skipBtn = _overlay.querySelector( '.bosa-dep-skip' );
		var targets = _getInstallTargets();

		// No installable targets — keep modal open and report error.
		if ( ! targets.length ) {
			if ( btn ) {
				btn.disabled    = false;
				btn.textContent = _t( 'dep_install_all', 'Install & Activate' );
			}
			return;
		}

		if ( btn ) {
			btn.disabled    = true;
			btn.textContent = _t( 'dep_installing', 'Installing…' );
		}
		if ( skipBtn ) { skipBtn.disabled = true; }
		if ( modal )   { modal.classList.add( 'bosa-dep-modal--busy' ); }

		_installNext( targets, 0, { modal: modal, btn: btn, skipBtn: skipBtn } );
	}

	function _installNext( targets, index, ui ) {
		if ( index >= targets.length ) {
			setTimeout( function () {
				if ( window.console && console.log ) {
					console.log( '[BEW Template Library] All plugins installed — resuming import workflow.' );
				}
				_close();
				_onInstalled();
			}, 700 );
			return;
		}

		var target   = targets[ index ];
		var slug     = target.slug;
		var nonce    = target.nonce;
		var statusEl = _overlay.querySelector( '.bosa-dep-status[data-slug="' + slug + '"]' );

		if ( statusEl ) {
			statusEl.textContent = _t( 'dep_installing', 'Installing…' );
			statusEl.className   = 'bosa-dep-status bosa-dep-status--loading';
		}

		_installPlugin( slug, nonce, function ( success ) {
			if ( statusEl ) {
				if ( success ) {
					statusEl.textContent = _t( 'dep_installed', 'Installed ✓' );
					statusEl.className   = 'bosa-dep-status bosa-dep-status--done';
				} else {
					statusEl.textContent = _t( 'dep_install_failed', 'Failed' );
					statusEl.className   = 'bosa-dep-status bosa-dep-status--error';
				}
			}

			if ( ! success ) {
				if ( ui.btn ) {
					ui.btn.disabled    = false;
					ui.btn.textContent = _t( 'dep_install_all', 'Install & Activate' );
				}
				if ( ui.skipBtn ) { ui.skipBtn.disabled = false; }
				if ( ui.modal )   { ui.modal.classList.remove( 'bosa-dep-modal--busy' ); }
				return;
			}

			_installNext( targets, index + 1, ui );
		} );
	}

	function _installPlugin( slug, nonce, callback ) {
		var ajaxUrl = ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.ajaxUrl )
			? BEWTemplateLibraryModal.ajaxUrl
			: ( typeof ajaxurl !== 'undefined' ? ajaxurl : '' );
		var restUrl   = ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.restUrl )
			? BEWTemplateLibraryModal.restUrl + 'install-plugin'
			: '';
		var restNonce = ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.nonce )
			? BEWTemplateLibraryModal.nonce
			: '';

		if ( ! slug || typeof jQuery === 'undefined' ) {
			callback( false );
			return;
		}

		function tryRestInstall() {
			if ( ! restUrl || ! restNonce ) {
				callback( false );
				return;
			}
			jQuery.ajax( {
				url:     restUrl,
				type:    'POST',
				headers: { 'X-WP-Nonce': restNonce },
				data:    { plugin_slug: slug },
			} )
				.done( function ( response ) {
					callback( !! ( response && response.success ) );
				} )
				.fail( function () {
					callback( false );
				} );
		}

		// Preferred path: admin-ajax with per-plugin nonce.
		// This matches the existing installer workflow used elsewhere in the plugin.
		if ( ajaxUrl && nonce ) {
			jQuery.ajax( {
				url:  ajaxUrl,
				type: 'POST',
				data: {
					action: 'bew_template_library_install_plugin',
					plugin_slug: slug,
					nonce: nonce,
				},
			} )
				.done( function ( response ) {
					if ( response && response.success ) {
						callback( true );
						return;
					}
					// Fallback if ajax handler rejects unexpectedly.
					tryRestInstall();
				} )
				.fail( function () {
					// REST fallback keeps the Install button functional across contexts.
					tryRestInstall();
				} );
			return;
		}

		// Fallback path when ajax nonce is unavailable.
		tryRestInstall();
	}

	/* ----------------------------------------------------------------
	 * Private — actions
	 * -------------------------------------------------------------- */

	// Skip → use HTML-converted content (placeholder widgets).
	function _skip()   { _close(); _onSkip(); }
	// Cancel (×) → abort the import entirely.
	function _cancel() { _close(); _onCancel(); }

	function _close() {
		if ( _overlay && _overlay.parentNode ) {
			_overlay.parentNode.removeChild( _overlay );
		}
		_overlay = null;
	}

	/* ----------------------------------------------------------------
	 * Private — escaping
	 * -------------------------------------------------------------- */

	function _esc( str ) {
		return String( str || '' )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;'  )
			.replace( />/g, '&gt;'  )
			.replace( /"/g, '&quot;' );
	}

	function _escAttr( str ) {
		return String( str || '' )
			.replace( /&/g, '&amp;'  )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	return { show: show };

}() );
