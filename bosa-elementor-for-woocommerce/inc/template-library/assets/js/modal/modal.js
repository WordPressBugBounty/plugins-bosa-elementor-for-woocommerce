/* global $e, elementorModules, BEWLibraryLayout, BEWLibraryComponent */
/**
 * Bosa Library Modal — registers $e command; builds modal shell on first open only.
 */
( function () {
	'use strict';

	var booted = false;

	function isReady() {
		return (
			typeof elementorModules !== 'undefined' &&
			elementorModules.common &&
			elementorModules.common.views &&
			elementorModules.common.views.modal &&
			elementorModules.common.views.modal.Layout &&
			typeof BEWLibraryLayout !== 'undefined' &&
			typeof BEWLibraryComponent !== 'undefined' &&
			typeof $e !== 'undefined' &&
			$e.modules &&
			$e.modules.ComponentBase &&
			typeof BEWTemplateLibraryModal !== 'undefined'
		);
	}

	function getModal() {
		if ( ! isReady() ) {
			return null;
		}
		if ( ! window.BEWLibraryModal ) {
			window.BEWLibraryModal = new BEWLibraryLayout();
		}
		return window.BEWLibraryModal;
	}

	window.openBEWLibraryModal = function ( args ) {
		var modal;

		try {
			modal = getModal();
		} catch ( err ) {
			return false;
		}

		if ( ! modal ) {
			return false;
		}

		try {
			modal.showModal();
			if ( args && args.tab ) {
				modal.setTab( args.tab );
			}
		} catch ( err2 ) {
			return false;
		}

		return true;
	};

	function boot() {
		if ( booted || ! isReady() ) {
			return;
		}
		booted = true;
		if ( ! $e.components.get( 'bew-library' ) ) {
			$e.components.register( new BEWLibraryComponent() );
		}
	}

	function tryBoot() {
		if ( booted ) {
			return;
		}
		if ( isReady() ) {
			boot();
			return;
		}
		setTimeout( tryBoot, 100 );
	}

	if ( typeof elementor !== 'undefined' && elementor.on ) {
		elementor.on( 'init', tryBoot );
		elementor.on( 'panel:init', tryBoot );
	}

	jQuery( tryBoot );
}() );
