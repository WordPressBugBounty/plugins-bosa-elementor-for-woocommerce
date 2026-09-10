/* global bewAdmin, bewApiRequest, bewSaveSettings */
( function () {
	'use strict';

	// ── Tab switching ──────────────────────────────────────────────────────
	function initTabs() {
		var buttons = document.querySelectorAll( '.bew-tab-button' );
		var panels  = document.querySelectorAll( '.bew-tab-content' );

		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var target = btn.dataset.tab;

				buttons.forEach( function ( b ) { b.classList.remove( 'active' ); } );
				panels.forEach( function ( p ) { p.style.display = 'none'; } );

				btn.classList.add( 'active' );
				var panel = document.getElementById( 'bew-tab-' + target );
				if ( panel ) {
					panel.style.display = '';
				}
			} );
		} );
	}

	// ── Individual toggle AJAX ─────────────────────────────────────────────
	function initToggles() {
		document.querySelectorAll( '.bew-widget-toggle' ).forEach( function ( checkbox ) {
			checkbox.addEventListener( 'change', function () {
				if ( checkbox.dataset.pro === '1' && ! bewAdmin.isProActive && checkbox.checked ) {
					checkbox.checked = false;
					showProPopup();
				}
			} );
		} );
	}

	// ── Save buttons (general / templates) ────────────────────────────────
	function initSaveButtons() {
		var saveButtons = {
			'bew-save-settings-general'    : 'general',
			'bew-save-settings-templates'  : 'templates',
		};

		Object.keys( saveButtons ).forEach( function ( btnId ) {
			var btn = document.getElementById( btnId );
			if ( ! btn ) {
				return;
			}
			btn.addEventListener( 'click', function () {
				var section = saveButtons[ btnId ];
				var toggles = {};

				var panel = document.getElementById( 'bew-tab-' + section );
				if ( panel ) {
					panel.querySelectorAll( '.bew-widget-toggle' ).forEach( function ( cb ) {
						toggles[ cb.dataset.widget ] = cb.checked ? 1 : 0;
					} );
				}

				btn.disabled    = true;
				btn.textContent = bewAdmin.saving || 'Saving…';

				bewApiRequest( 'bew_save_settings', { settings: JSON.stringify( toggles ) }, function ( success ) {
					btn.disabled    = false;
					btn.textContent = success ? ( bewAdmin.saved || 'Saved!' ) : 'Error';
					setTimeout( function () {
						btn.textContent = bewAdmin.save || 'Save Changes';
					}, 2000 );
				} );
			} );
		} );
	}

	// ── Widgets bulk save ──────────────────────────────────────────────────
	function initWidgetsSave() {
		var btn = document.getElementById( 'bew-save-settings-widgets' );
		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var panel   = document.getElementById( 'bew-tab-widgets' );
			var toggles = {};

			if ( panel ) {
				panel.querySelectorAll( '.bew-widget-toggle' ).forEach( function ( cb ) {
					if ( cb.dataset.pro === '1' && ! bewAdmin.isProActive ) {
						return;
					}
					toggles[ cb.dataset.widget ] = cb.checked ? 1 : 0;
				} );
			}

			btn.disabled    = true;
			btn.textContent = bewAdmin.saving || 'Saving…';

			bewApiRequest( 'bew_save_widgets', { widgets: JSON.stringify( toggles ) }, function ( success ) {
				btn.disabled    = false;
				btn.textContent = success ? ( bewAdmin.saved || 'Saved!' ) : 'Error';
				setTimeout( function () {
					btn.textContent = bewAdmin.save || 'Save Changes';
				}, 2000 );
			} );
		} );
	}

	// ── Modules bulk save ─────────────────────────────────────────────────
	function initModulesSave() {
		var btn = document.getElementById( 'bew-save-settings-modules' );
		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var panel   = document.getElementById( 'bew-tab-modules' );
			var toggles = {};

			if ( panel ) {
				panel.querySelectorAll( '.bew-widget-toggle' ).forEach( function ( cb ) {
					if ( cb.dataset.pro === '1' && ! bewAdmin.isProActive ) {
						return;
					}
					toggles[ cb.dataset.widget ] = cb.checked ? 1 : 0;
				} );
			}

			btn.disabled    = true;
			btn.textContent = bewAdmin.saving || 'Saving…';

			bewApiRequest( 'bew_save_modules', { modules: JSON.stringify( toggles ) }, function ( success ) {
				btn.disabled    = false;
				btn.textContent = success ? ( bewAdmin.saved || 'Saved!' ) : 'Error';
				setTimeout( function () {
					btn.textContent = bewAdmin.save || 'Save Changes';
				}, 2000 );
			} );
		} );
	}

	// ── AI Features bulk save ────────────────────────────────────────────
	function initAiFeaturesSave() {
		var btn = document.getElementById( 'bew-save-settings-ai-features' );
		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var panel   = document.getElementById( 'bew-tab-ai-integration' );
			var toggles = {};

			if ( panel ) {
				panel.querySelectorAll( '.bew-widget-toggle' ).forEach( function ( cb ) {
					if ( 'ai_feature' !== cb.dataset.type ) {
						return;
					}
					if ( cb.dataset.pro === '1' && ! bewAdmin.isProActive ) {
						return;
					}
					toggles[ cb.dataset.widget ] = cb.checked ? 1 : 0;
				} );
			}

			btn.disabled    = true;
			btn.textContent = bewAdmin.saving || 'Saving…';

			bewApiRequest( 'bew_save_ai_features', { features: JSON.stringify( toggles ) }, function ( success ) {
				btn.disabled    = false;
				btn.textContent = success ? ( bewAdmin.saved || 'Saved!' ) : 'Error';
				setTimeout( function () {
					btn.textContent = bewAdmin.save || 'Save Changes';
				}, 2000 );
			} );
		} );
	}

	// ── Pro Popup ──────────────────────────────────────────────────────────
	function showProPopup() {
		var popup = document.getElementById( 'bew-pro-popup' );
		if ( popup ) {
			popup.style.display = 'flex';
		}
	}

	function initProPopup() {
		var popup = document.getElementById( 'bew-pro-popup' );
		if ( ! popup ) {
			return;
		}

		popup.querySelector( '.bew-pro-popup-close' ).addEventListener( 'click', function () {
			popup.style.display = 'none';
		} );

		popup.querySelector( '.bew-pro-popup-overlay' ).addEventListener( 'click', function () {
			popup.style.display = 'none';
		} );
	}

	// ── Rollback ───────────────────────────────────────────────────────────
	function initRollback() {
		var btn = document.getElementById( 'bew-rollback-button' );
		if ( ! btn ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var version   = btn.dataset.version;
			var confirmed = window.confirm(
				'Are you sure you want to reinstall version ' + version + '?\n\n' +
				'Please make sure you have a backup before proceeding.'
			);

			if ( ! confirmed ) {
				return;
			}

			var originalHTML = btn.innerHTML;
			btn.disabled     = true;
			btn.textContent  = 'Reinstalling…';

			bewApiRequest( 'bew_rollback', { version: version }, function ( success, data ) {
				if ( success ) {
					btn.textContent = data && data.message ? data.message : 'Done!';
					setTimeout( function () {
						window.location.reload();
					}, 1000 );
				} else {
					btn.disabled  = false;
					btn.innerHTML = originalHTML;
					window.alert( 'Rollback failed: ' + ( data || 'Unknown error' ) );
				}
			} );
		} );
	}

	// ── Boot ───────────────────────────────────────────────────────────────
	document.addEventListener( 'DOMContentLoaded', function () {
		initTabs();
		initToggles();
		initSaveButtons();
		initWidgetsSave();
		initModulesSave();
		initAiFeaturesSave();
		initProPopup();
		initRollback();
	} );
}() );
