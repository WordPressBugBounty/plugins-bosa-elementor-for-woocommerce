/**
 * Runs in the Elementor editor. Shows the back-to-top button in the preview only
 * while the Site Settings panel is open, and patches its content/classes live as
 * Site Settings controls change (Elementor only auto-updates controls that have
 * CSS `selectors`; content controls like Icon/Text/Tooltip/Position need this).
 */
( function () {
	'use strict';

	function togglePreviewClass( isOpen ) {
		try {
			var previewDoc = elementor.$preview && elementor.$preview[ 0 ] && elementor.$preview[ 0 ].contentDocument;
			if ( previewDoc && previewDoc.body ) {
				previewDoc.body.classList.toggle( 'bew-site-settings-open', isOpen );

				var button = previewDoc.querySelector( '.bew-btt-button.bew-btt-editor-preview' );
				if ( button ) {
					// Drive the real "visible" state (not just a CSS override) so
					// scroll-gated styles like Opacity resolve to their live value.
					button.classList.toggle( 'bew-btt-visible', isOpen );
				}
			}
		} catch ( e ) {
			// Preview iframe not ready/accessible yet — ignore.
		}
	}

	function isSiteSettingsOpen() {
		return !! ( window.$e && $e.routes && $e.routes.isPartOf( 'panel/global' ) );
	}

	function isKitDocument() {
		try {
			return !! ( window.elementor && elementor.documents && 'kit' === elementor.documents.getCurrent().config.type );
		} catch ( e ) {
			return false;
		}
	}

	function syncState() {
		togglePreviewClass( isSiteSettingsOpen() );
	}

	function onSettingsChanged( settings ) {
		try {
			var previewDoc = elementor.$preview && elementor.$preview[ 0 ] && elementor.$preview[ 0 ].contentDocument;
			var button = previewDoc && previewDoc.querySelector( '.bew-btt-button' );
			if ( ! button ) {
				return;
			}

			if ( 'bew_btt_text' in settings ) {
				var textEl = button.querySelector( '.bew-btt-text' );
				if ( settings.bew_btt_text ) {
					if ( ! textEl ) {
						textEl = previewDoc.createElement( 'span' );
						textEl.className = 'bew-btt-text';
						button.appendChild( textEl );
					}
					textEl.textContent = settings.bew_btt_text;
				} else if ( textEl ) {
					textEl.parentNode.removeChild( textEl );
				}
			}

			if ( 'bew_btt_tooltip' in settings ) {
				if ( settings.bew_btt_tooltip ) {
					button.setAttribute( 'title', settings.bew_btt_tooltip );
				} else {
					button.removeAttribute( 'title' );
				}
			}

			if ( 'bew_btt_position' in settings ) {
				button.classList.remove( 'bew-btt-left', 'bew-btt-right' );
				button.classList.add( 'bew-btt-' + ( settings.bew_btt_position || 'right' ) );
			}

			if ( 'bew_btt_entrance_animation' in settings ) {
				button.classList.remove( 'bew-btt-entrance-fade-in', 'bew-btt-entrance-slide-up', 'bew-btt-entrance-float' );
				button.classList.add( 'bew-btt-entrance-' + ( settings.bew_btt_entrance_animation || 'fade-in' ) );
			}

			if ( 'bew_btt_scroll_offset' in settings ) {
				button.setAttribute( 'data-scroll-offset', settings.bew_btt_scroll_offset );
			}

			if ( 'bew_btt_icon' in settings ) {
				var icon = settings.bew_btt_icon;
				// Font-icon libraries render as a single class on <i>; custom SVG
				// uploads need attachment data we don't have here and keep showing
				// their previous icon live, then correct on the next full refresh.
				if ( icon && 'string' === typeof icon.value && 'svg' !== icon.library ) {
					var existingIcon = button.querySelector( 'i, svg' );
					var iEl = previewDoc.createElement( 'i' );
					iEl.className = icon.value;
					iEl.setAttribute( 'aria-hidden', 'true' );
					if ( existingIcon ) {
						existingIcon.parentNode.replaceChild( iEl, existingIcon );
					} else {
						button.insertBefore( iEl, button.firstChild );
					}
				}
			}
		} catch ( e ) {
			// Preview iframe not ready/accessible yet — ignore.
		}
	}

	if ( window.elementor && typeof elementor.on === 'function' ) {
		elementor.on( 'preview:loaded', syncState );
	}

	if ( window.$e && $e.routes ) {
		$e.routes.on( 'run:after', syncState );
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
