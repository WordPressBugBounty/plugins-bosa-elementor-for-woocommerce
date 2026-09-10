/* global BEWTemplateLibraryModal, elementor, elementorCommon, $e, Backbone */
/**
 * Shared template insert workflow — used by card and preview views.
 */

/**
 * Whether the Bosa Pro plugin is active on this site.
 *
 * @return {boolean}
 */
function bosaTkHasProAccess() {
	return !!( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.proPluginActive );
}

/**
 * Whether a template model is marked Pro (not free).
 *
 * @param {Backbone.Model} model Template model.
 * @return {boolean}
 */
function bosaTkIsProTemplate( model ) {
	return !!( model && typeof model.get === 'function' && model.get( 'is_free' ) === false );
}

/**
 * Whether a Pro template is locked for the current user.
 *
 * @param {Backbone.Model} model Template model.
 * @return {boolean}
 */
function bosaTkIsProLocked( model ) {
	return bosaTkIsProTemplate( model ) && ! bosaTkHasProAccess();
}

/**
 * Pricing URL for the Go Pro CTA.
 *
 * @return {string}
 */
function bosaTkGetProUpgradeUrl() {
	if ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.proUpgradeUrl ) {
		return BEWTemplateLibraryModal.proUpgradeUrl;
	}

	return 'https://bew.bosathemes.com/pricing';
}

/**
 * Deep-clone Elementor content and assign fresh element ids.
 *
 * @param {Array} content Elementor elements tree.
 * @return {Array}
 */
function bosaTkPrepareImportContent( content ) {
	var clone = JSON.parse( JSON.stringify( content ) );

	function regenerateIds( elements ) {
		if ( ! Array.isArray( elements ) ) {
			return;
		}
		elements.forEach( function ( element ) {
			if ( ! element || typeof element !== 'object' ) {
				return;
			}
			if ( window.elementorCommon && elementorCommon.helpers && typeof elementorCommon.helpers.getUniqueId === 'function' ) {
				element.id = String( elementorCommon.helpers.getUniqueId() );
			}
			if ( Array.isArray( element.elements ) && element.elements.length ) {
				regenerateIds( element.elements );
			}
		} );
	}

	regenerateIds( clone );
	return clone;
}

/**
 * Full-viewport "importing" overlay shown for the whole insert workflow
 * (content fetch → optional plugin install → re-fetch → Elementor
 * import), so a multi-second process never looks frozen or like nothing
 * happened. A single shared instance -- bosaTkRunInsert() below is the
 * one place that drives it.
 */
var _bosaTkImportOverlay = null;

function bosaTkShowImportOverlay() {
	if ( _bosaTkImportOverlay ) {
		return;
	}

	var message = ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.i18n && BEWTemplateLibraryModal.i18n.importing )
		? BEWTemplateLibraryModal.i18n.importing
		: 'Inserting your template';

	var icon = document.createElement( 'span' );
	icon.className = 'bosa-tk-loading__icon bosa-tk-refresh--spinning';
	icon.setAttribute( 'aria-hidden', 'true' );
	icon.innerHTML = '&#8635;';

	var text = document.createElement( 'span' );
	text.className = 'bosa-tk-import-overlay__text';
	text.textContent = message;

	_bosaTkImportOverlay = document.createElement( 'div' );
	_bosaTkImportOverlay.className = 'bosa-tk-import-overlay';
	_bosaTkImportOverlay.setAttribute( 'role', 'status' );
	_bosaTkImportOverlay.setAttribute( 'aria-live', 'polite' );
	_bosaTkImportOverlay.setAttribute( 'aria-busy', 'true' );
	_bosaTkImportOverlay.appendChild( icon );
	_bosaTkImportOverlay.appendChild( text );

	document.body.appendChild( _bosaTkImportOverlay );

	// Safety net: if the modal closes for any reason while this is still
	// showing, don't leave it orphaned on screen.
	if ( window.BEWLibraryModal && typeof BEWLibraryModal.getModal === 'function' ) {
		var modal = BEWLibraryModal.getModal();
		if ( modal && typeof modal.on === 'function' ) {
			modal.on( 'hide', bosaTkHideImportOverlay );
		}
	}
}

function bosaTkHideImportOverlay() {
	if ( _bosaTkImportOverlay && _bosaTkImportOverlay.parentNode ) {
		_bosaTkImportOverlay.parentNode.removeChild( _bosaTkImportOverlay );
	}
	_bosaTkImportOverlay = null;
}

/**
 * Show an insert error toast or alert.
 *
 * @param {string} message Error message.
 */
function bosaTkShowInsertError( message ) {
	if ( window.elementor && elementor.notifications && typeof elementor.notifications.showToast === 'function' ) {
		elementor.notifications.showToast( {
			message: message,
			button: false,
		} );
		return;
	}

	window.alert( message );
}

/**
 * Run the full import workflow for a template model.
 *
 * @param {Backbone.Model} model       Template model.
 * @param {Object}         options     { $el, onStart, onFinish, onSuccess }
 */
function bosaTkRunInsert( model, options ) {
	var opts = options || {};
	var $el  = opts.$el || null;
	var id   = model.get( 'id' );

	if ( ! id ) {
		bosaTkShowInsertError( 'Missing template ID.' );
		if ( opts.onFinish ) {
			opts.onFinish();
		}
		return;
	}

	if ( bosaTkIsProLocked( model ) ) {
		if ( opts.onFinish ) {
			opts.onFinish();
		}
		return;
	}

	if ( typeof $e === 'undefined' || typeof $e.run !== 'function' ) {
		bosaTkShowInsertError( 'Elementor editor API is not ready.' );
		if ( opts.onFinish ) {
			opts.onFinish();
		}
		return;
	}

	if ( $el ) {
		$el.addClass( 'bosa-tk-card--loading' );
	}
	if ( opts.onStart ) {
		opts.onStart();
	}
	bosaTkShowImportOverlay();

	function finish( message ) {
		bosaTkHideImportOverlay();
		if ( $el ) {
			$el.removeClass( 'bosa-tk-card--loading' );
		}
		if ( message ) {
			bosaTkShowInsertError( message );
		}
		if ( opts.onFinish ) {
			opts.onFinish( message );
		}
	}

	function fetchImport( usePlaceholders, onReady ) {
		Backbone.ajax( {
			url: BEWTemplateLibraryModal.restUrl + 'import',
			type: 'POST',
			headers: { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
			data: {
				template_id: id,
				use_placeholders: usePlaceholders ? 1 : 0,
				_btk: Date.now(),
			},
			success: onReady,
			error: function ( xhr ) {
				var message = 'Import request failed.';
				if ( xhr && xhr.responseJSON && xhr.responseJSON.message ) {
					message = xhr.responseJSON.message;
				}
				finish( message );
			},
		} );
	}

	function refreshElementorWidgetsThen( callback ) {
		if ( ! window.elementorCommon || ! elementorCommon.ajax
			|| typeof window.elementor === 'undefined'
			|| typeof elementor.addWidgetsCache !== 'function' ) {
			callback();
			return;
		}

		elementorCommon.ajax.addRequest( 'refresh_widgets_config' )
			.done( function ( data ) {
				elementor.widgetsCache = {};
				elementor.addWidgetsCache( data.widgets );
				if ( data.categories ) {
					elementor.config.document.panel.elements_categories = data.categories;
				}
				if ( elementor.hooks ) {
					elementor.hooks.doAction( 'elementor/widgets/refreshed' );
				}
				callback();
			} )
			.fail( function () {
				callback();
			} );
	}

	function doImport( content, pageSettings ) {
		if ( ! Array.isArray( content ) ) {
			finish( 'Template content was empty.' );
			return;
		}

		var importContent = bosaTkPrepareImportContent( content );
		var importModel = new Backbone.Model( {
			title: model.get( 'title' ) || '',
			template_id: String( id ),
			source: 'bosa-template-kits',
		} );

		try {
			$e.run( 'document/elements/import', {
				model: importModel,
				data: {
					content: importContent,
					page_settings: pageSettings || [],
				},
				options: { withPageSettings: false },
			} );
		} catch ( error ) {
			finish( error && error.message ? error.message : 'Import failed in Elementor.' );
			return;
		}

		bosaTkHideImportOverlay();
		if ( $el ) {
			$el.removeClass( 'bosa-tk-card--loading' );
		}
		if ( opts.onSuccess ) {
			opts.onSuccess();
		}
		if ( window.BEWLibraryModal ) {
			window.BEWLibraryModal.hide();
		}
	}

	fetchImport( false, function ( response ) {
		var payload        = response && response.data ? response.data : response;
		var content        = payload && payload.content ? payload.content : null;
		var missingPlugins = ( payload && payload.missing_plugins ) ? payload.missing_plugins : {};
		var hasMissing     = Object.keys( missingPlugins ).length > 0;

		if ( ! Array.isArray( content ) ) {
			finish( 'Template content was empty.' );
			return;
		}

		if ( ! hasMissing ) {
			doImport( content, payload.page_settings );
			return;
		}

		if ( typeof window.BEWDependencyModal === 'undefined' ) {
			finish( 'Required plugins are missing. Please reload the editor and try again.' );
			return;
		}

		// The Required Plugins dialog is the active UI from here until the
		// user picks Install/Skip -- showing "Inserting your template" behind
		// it would wrongly imply the import is already running before the
		// user has even chosen how to handle the missing plugins. Hidden here,
		// then re-shown once their choice resumes the real import work below.
		bosaTkHideImportOverlay();

		window.BEWDependencyModal.show(
			missingPlugins,
			function () {
				bosaTkShowImportOverlay();
				refreshElementorWidgetsThen( function () {
					fetchImport( false, function ( freshResponse ) {
						var freshPayload = freshResponse && freshResponse.data ? freshResponse.data : freshResponse;
						var freshContent = freshPayload.original_content || freshPayload.content;
						doImport( freshContent, freshPayload.page_settings );
					} );
				} );
			},
			function () {
				bosaTkShowImportOverlay();
				fetchImport( true, function ( skipResponse ) {
					var skipPayload = skipResponse && skipResponse.data ? skipResponse.data : skipResponse;
					doImport(
						skipPayload.content,
						skipPayload.page_settings
					);
				} );
			},
			function () {
				bosaTkHideImportOverlay();
				if ( $el ) {
					$el.removeClass( 'bosa-tk-card--loading' );
				}
				if ( opts.onFinish ) {
					opts.onFinish();
				}
			}
		);
	} );
}
