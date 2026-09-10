/**
 * Runs in the Elementor editor. Shows the preloader overlay in the preview
 * ONLY while a Preloader setting is actually being changed, and patches its
 * image live as those controls change (Loader Source / Built-in Preloader /
 * Custom Image all drive an <img> src, not a CSS selector, so Elementor can't
 * auto-update it).
 *
 * Deliberately does NOT react to Site Settings opening or to panel/tab
 * navigation — this overlay is full-screen, so re-showing it on every route
 * change forced Elementor to re-render the whole preview on every panel
 * click, which is what made switching between Site Settings tabs feel like
 * the editor was reloading. Reacting only to the specific preloader settings
 * actually changing avoids that entirely, while still giving a live preview
 * of the setting being edited: it appears when a preloader control changes,
 * then auto-hides after a few seconds idle.
 */
( function () {
	'use strict';

	var IDLE_HIDE_MS = 8000;

	var idleHideTimer = null;

	function getOverlay() {
		try {
			var previewDoc = elementor.$preview && elementor.$preview[ 0 ] && elementor.$preview[ 0 ].contentDocument;
			return previewDoc ? previewDoc.querySelector( '.bew-preloader-overlay' ) : null;
		} catch ( e ) {
			return null;
		}
	}

	function clearIdleHideTimer() {
		if ( idleHideTimer ) {
			clearTimeout( idleHideTimer );
			idleHideTimer = null;
		}
	}

	function hideOverlay() {
		var overlay = getOverlay();
		if ( overlay ) {
			overlay.classList.remove( 'bew-preloader-editor-reveal' );
		}
	}

	function revealOverlay() {
		var overlay = getOverlay();
		if ( overlay ) {
			overlay.classList.add( 'bew-preloader-editor-reveal' );
		}
		clearIdleHideTimer();
		idleHideTimer = setTimeout( hideOverlay, IDLE_HIDE_MS );
	}

	function isKitDocument() {
		try {
			return !! ( window.elementor && elementor.documents && 'kit' === elementor.documents.getCurrent().config.type );
		} catch ( e ) {
			return false;
		}
	}

	function currentKitSetting( key, fallback ) {
		try {
			var value = elementor.settings.page.model.get( key );
			return ( undefined === value || null === value || '' === value ) ? fallback : value;
		} catch ( e ) {
			return fallback;
		}
	}

	function onSettingsChanged( settings ) {
		try {
			var overlay = getOverlay();
			if ( ! overlay ) {
				return;
			}

			var touchesPreloader = Object.keys( settings ).some( function ( key ) {
				return 0 === key.indexOf( 'bew_preloader_' );
			} );
			if ( ! touchesPreloader ) {
				return;
			}

			revealOverlay();

			var img = overlay.querySelector( '.bew-preloader-image' );
			if ( ! img ) {
				return;
			}

			var sourceRelated = [ 'bew_preloader_source', 'bew_preloader_builtin', 'bew_preloader_custom_image' ];
			var changed = sourceRelated.some( function ( key ) {
				return key in settings;
			} );
			if ( ! changed ) {
				return;
			}

			var source = 'bew_preloader_source' in settings ? settings.bew_preloader_source : currentKitSetting( 'bew_preloader_source', 'builtin' );

			if ( 'custom' === source ) {
				var customImage = 'bew_preloader_custom_image' in settings ? settings.bew_preloader_custom_image : currentKitSetting( 'bew_preloader_custom_image', null );
				if ( customImage && customImage.url ) {
					img.src = customImage.url;
				}
			} else {
				var builtin = 'bew_preloader_builtin' in settings ? settings.bew_preloader_builtin : currentKitSetting( 'bew_preloader_builtin', 'preloader1' );
				if ( window.bewPreloaderPreviewData && bewPreloaderPreviewData.imagesBaseUrl ) {
					img.src = bewPreloaderPreviewData.imagesBaseUrl + builtin + '.gif';
				}
			}
		} catch ( e ) {
			// Preview iframe not ready/accessible yet — ignore.
		}
	}

	if ( window.$e && $e.commands ) {
		$e.commands.on( 'run:after', function ( _component, command, args ) {
			if ( 'document/elements/settings' !== command || ! isKitDocument() || ! args || ! args.settings ) {
				return;
			}
			onSettingsChanged( args.settings );
		} );
	}
}() );
