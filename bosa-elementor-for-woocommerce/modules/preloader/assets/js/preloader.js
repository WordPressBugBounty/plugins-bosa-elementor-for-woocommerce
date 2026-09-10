( function () {
	'use strict';

	var overlay = document.querySelector( '.bew-preloader-overlay' );
	if ( ! overlay ) {
		return;
	}

	// Inside any Elementor editor preview, visibility/timing for this overlay
	// is owned entirely by site-settings-preview.js (reveal-on-open, idle-hide,
	// reveal-on-setting-change via a dedicated CSS class) — this script's own
	// "hide once ready" logic below is for the real front-end page load only
	// and must not run here, or it would fight that mechanism.
	if ( typeof bewPreloaderData !== 'undefined' && bewPreloaderData.isEditorPreview ) {
		return;
	}

	var duration = ( typeof bewPreloaderData !== 'undefined' && bewPreloaderData.fadeDuration )
		? parseInt( bewPreloaderData.fadeDuration, 10 )
		: 400;

	overlay.style.transitionDuration = duration + 'ms';

	function hidePreloader() {
		overlay.classList.add( 'bew-preloader-fade' );
		setTimeout( function () {
			overlay.parentNode.removeChild( overlay );
		}, duration );
	}

	if ( document.readyState === 'complete' ) {
		hidePreloader();
	} else {
		window.addEventListener( 'load', hidePreloader );
	}

	setTimeout( hidePreloader, 8000 );
}() );
