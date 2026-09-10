/* global bewAdmin, bewApiRequest, bewAiProviderTypes */
( function () {
	'use strict';

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	// wp_send_json_error() sends a bare string as `data` (not `{message: ...}`)
	// in most handlers here -- handle both shapes.
	function errorTextFrom( data, fallback ) {
		if ( 'string' === typeof data && data ) {
			return data;
		}
		if ( data && data.message ) {
			return data.message;
		}
		return fallback;
	}

	function renderModelSelect( container, models, selectId ) {
		var select = document.createElement( 'select' );
		if ( selectId ) {
			select.id = selectId;
		}
		models.forEach( function ( m ) {
			var opt = document.createElement( 'option' );
			opt.value = m.id;
			opt.textContent = m.label || m.id;
			select.appendChild( opt );
		} );
		container.appendChild( select );
		return select;
	}

	// Provider API model lists tend to surface heavy flagship/reasoning
	// models first -- not ideal for a day-to-day text generation widget.
	// Prefer the lighter general-purpose tier (haiku/flash/mini/small/...)
	// when present, else leave the provider's own default. Only run at
	// connect-time; a provider with a saved model never re-runs this.
	//
	// AVOID also excludes non-text modalities (image/tts/audio/etc.) and
	// "preview" builds, since some providers mix these into the same
	// catalog under names that would otherwise match PREFERRED (e.g.
	// Google's "nano-banana-pro-preview" image model).
	function pickTextModel( models ) {
		var PREFERRED = [ 'flash-lite', 'haiku', 'mini', 'flash', 'small', 'lite', 'nano' ];
		var AVOID     = [
			'opus', 'sonnet', 'pro', 'large', 'ultra', 'thinking', 'reasoning', 'code', 'coder', 'codex',
			'preview', 'image', 'tts', 'audio', 'robotics', 'computer-use', 'deep-research', 'antigravity'
		];

		function textOf( m ) {
			return ( m.id + ' ' + ( m.label || '' ) ).toLowerCase();
		}

		for ( var i = 0; i < PREFERRED.length; i++ ) {
			var pattern = PREFERRED[ i ];
			var candidates = models.filter( function ( m ) {
				var hay = textOf( m );
				return -1 !== hay.indexOf( pattern ) && ! AVOID.some( function ( bad ) { return -1 !== hay.indexOf( bad ); } );
			} );
			if ( ! candidates.length ) {
				continue;
			}

			// Prefer a "-latest"-style alias when present -- it tracks
			// whatever the provider currently considers current, rather
			// than a dated snapshot that can get deprecated later (e.g.
			// Google's gemini-2.5-flash 404ing while gemini-flash-latest
			// keeps working).
			var latest = candidates.find( function ( m ) { return -1 !== textOf( m ).indexOf( 'latest' ); } );
			return latest || candidates[ 0 ];
		}

		return null;
	}

	function renderManualInput( container, inputId, placeholder ) {
		var input = document.createElement( 'input' );
		input.type = 'text';
		if ( inputId ) {
			input.id = inputId;
		}
		input.className = 'regular-text';
		input.placeholder = placeholder || '';
		container.appendChild( input );
		return input;
	}

	function init() {
		var typeSelect  = document.getElementById( 'bew-ai-provider-type' );
		var helpText    = document.getElementById( 'bew-ai-provider-help' );
		var addButton   = document.getElementById( 'bew-ai-provider-add' );
		var labelInput  = document.getElementById( 'bew-ai-provider-label' );
		var keyInput    = document.getElementById( 'bew-ai-provider-key' );
		var loadButton  = document.getElementById( 'bew-ai-load-models' );
		var loadStatus  = document.getElementById( 'bew-ai-load-models-status' );
		var modelField  = document.getElementById( 'bew-ai-model-field' );
		var tbody       = document.getElementById( 'bew-ai-providers-tbody' );

		if ( ! typeSelect || ! addButton || ! tbody ) {
			return; // Not on the AI Integration tab.
		}

		var addButtonLabel  = addButton.textContent;
		var loadButtonLabel = loadButton ? loadButton.textContent : '';

		function updateHelpText() {
			var info = window.bewAiProviderTypes && window.bewAiProviderTypes[ typeSelect.value ];
			if ( helpText && info ) {
				helpText.innerHTML = info.help_html;
			}
		}

		function setStatus( text, kind ) {
			if ( ! loadStatus ) {
				return;
			}
			loadStatus.textContent = text || '';
			if ( 'ok' === kind ) {
				loadStatus.className = 'bew-notice bew-notice-success';
			} else if ( 'error' === kind ) {
				loadStatus.className = 'bew-notice bew-notice-error';
			} else {
				loadStatus.className = 'description';
			}
		}

		function resetModelField() {
			if ( modelField ) {
				modelField.innerHTML = '';
			}
			setStatus( '', '' );
		}

		typeSelect.addEventListener( 'change', function () {
			updateHelpText();
			resetModelField();
		} );
		keyInput.addEventListener( 'input', resetModelField );
		updateHelpText();

		if ( loadButton ) {
			loadButton.addEventListener( 'click', function () {
				var key = keyInput.value.trim();
				if ( ! key ) {
					keyInput.focus();
					return;
				}

				loadButton.disabled    = true;
				loadButton.textContent = bewAdmin.aiLoadingModels;
				if ( modelField ) {
					modelField.innerHTML = '';
				}
				setStatus( '', '' );

				bewApiRequest( 'bew_fetch_ai_models', {
					provider_type : typeSelect.value,
					api_key       : key,
				}, function ( success, data ) {
					loadButton.disabled    = false;
					loadButton.textContent = loadButtonLabel;

					if ( ! success || ! data ) {
						setStatus( bewAdmin.aiErrorMessage, 'error' );
						if ( modelField ) {
							renderManualInput( modelField, 'bew-ai-provider-model-manual', bewAdmin.aiManualModelPlaceholder );
						}
						return;
					}

					if ( 'ok' === data.status && data.models && data.models.length ) {
						var select    = renderModelSelect( modelField, data.models, 'bew-ai-provider-model' );
						var preferred = pickTextModel( data.models );
						if ( preferred ) {
							select.value = preferred.id;
						}
						setStatus( bewAdmin.aiModelsLoaded.replace( '%d', data.models.length ), 'ok' );
						return;
					}

					// 'manual' (no live detection for this provider) or 'error' (fetch failed) -- let the user type a model ID instead of blocking them.
					renderManualInput( modelField, 'bew-ai-provider-model-manual', bewAdmin.aiManualModelPlaceholder );
					setStatus( data.message || bewAdmin.aiManualModelHelp, 'error' === data.status ? 'error' : '' );
				} );
			} );
		}

		function getSelectedModel() {
			var select = document.getElementById( 'bew-ai-provider-model' );
			if ( select && select.value ) {
				return { model: select.value, label: select.options[ select.selectedIndex ].textContent };
			}
			var manual = document.getElementById( 'bew-ai-provider-model-manual' );
			if ( manual && manual.value.trim() ) {
				return { model: manual.value.trim(), label: manual.value.trim() };
			}
			return null;
		}

		// Keeps exactly one row marked default: the matching row gets the tag
		// and loses its button (hidden, not disabled, per design); every
		// other row loses the tag and regains its button if it doesn't have one.
		function applyDefaultProviderUI( defaultId ) {
			tbody.querySelectorAll( 'tr[data-provider-id]' ).forEach( function ( row ) {
				var isDefault  = row.dataset.providerId === defaultId;
				var tag        = row.querySelector( '.bew-ai-provider-default-tag' );
				var setBtn     = row.querySelector( '.bew-ai-provider-set-default' );
				var nameCell   = row.querySelector( 'td' );
				var actionCell = row.querySelector( '.bew-provider-actions' );

				if ( isDefault ) {
					if ( ! tag && nameCell ) {
						tag = document.createElement( 'span' );
						tag.className = 'bew-default-tag bew-ai-provider-default-tag';
						tag.textContent = bewAdmin.aiDefaultLabel;
						nameCell.appendChild( tag );
					}
					if ( setBtn ) {
						setBtn.remove();
					}
				} else {
					if ( tag ) {
						tag.remove();
					}
					if ( ! setBtn && actionCell ) {
						setBtn = document.createElement( 'button' );
						setBtn.type = 'button';
						setBtn.className = 'bew-box-button bew-button-secondary bew-ai-provider-set-default';
						setBtn.dataset.providerId = row.dataset.providerId;
						setBtn.textContent = bewAdmin.aiSetDefaultLabel;
						actionCell.insertBefore( setBtn, actionCell.firstChild );
					}
				}
			} );
		}

		function appendEmptyRow() {
			var row = document.createElement( 'tr' );
			row.className = 'bew-ai-providers-empty-row';
			row.innerHTML = '<td colspan="6">' + escapeHtml( bewAdmin.aiNoneConnected ) + '</td>';
			tbody.appendChild( row );
		}

		addButton.addEventListener( 'click', function () {
			var type  = typeSelect.value;
			var label = labelInput.value.trim();
			var key   = keyInput.value.trim();

			if ( ! key ) {
				keyInput.focus();
				return;
			}

			var chosen = getSelectedModel();
			if ( ! chosen ) {
				setStatus( bewAdmin.aiModelRequired, 'error' );
				if ( loadButton ) {
					loadButton.focus();
				}
				return;
			}

			addButton.disabled    = true;
			addButton.textContent = bewAdmin.saving;

			bewApiRequest( 'bew_save_ai_provider', {
				provider_type  : type,
				provider_label : label,
				api_key        : key,
				model          : chosen.model,
				model_label    : chosen.label,
			}, function ( success, data ) {
				addButton.disabled    = false;
				addButton.textContent = addButtonLabel;

				if ( ! success || ! data || ! data.provider ) {
					window.alert( errorTextFrom( data, bewAdmin.aiErrorMessage ) );
					return;
				}

				var emptyRow = tbody.querySelector( '.bew-ai-providers-empty-row' );
				if ( emptyRow ) {
					emptyRow.remove();
				}

				var p         = data.provider;
				var isDefault = !! p.is_default;
				var row       = document.createElement( 'tr' );
				row.setAttribute( 'data-provider-id', p.id );
				row.setAttribute( 'data-provider-type', type );
				row.innerHTML =
					'<td>' + escapeHtml( p.label ) + ( isDefault ? ' <span class="bew-default-tag bew-ai-provider-default-tag">' + escapeHtml( bewAdmin.aiDefaultLabel ) + '</span>' : '' ) + '</td>' +
					'<td>' + escapeHtml( p.type_label ) + '</td>' +
					'<td><code>••••' + escapeHtml( p.key_preview ) + '</code></td>' +
					'<td>' + escapeHtml( p.created_at ) + '</td>' +
					'<td><div class="bew-provider-actions">' +
					( isDefault ? '' : '<button type="button" class="bew-box-button bew-button-secondary bew-ai-provider-set-default" data-provider-id="' + escapeHtml( p.id ) + '">' + escapeHtml( bewAdmin.aiSetDefaultLabel ) + '</button>' ) +
					'<button type="button" class="bew-box-button bew-button-secondary bew-ai-provider-delete" data-provider-id="' + escapeHtml( p.id ) + '">' +
					escapeHtml( bewAdmin.aiDeleteLabel ) + '</button>' +
					'</div></td>';
				tbody.appendChild( row );

				if ( isDefault ) {
					applyDefaultProviderUI( p.id );
				}

				labelInput.value = '';
				keyInput.value   = '';
				resetModelField();
			} );
		} );

		function handleDelete( btn ) {
			if ( ! window.confirm( bewAdmin.aiConfirmDelete ) ) {
				return;
			}

			var id = btn.dataset.providerId;
			btn.disabled = true;

			bewApiRequest( 'bew_delete_ai_provider', { provider_id: id }, function ( success, data ) {
				if ( ! success ) {
					btn.disabled = false;
					window.alert( errorTextFrom( data, bewAdmin.aiErrorMessage ) );
					return;
				}

				var row = tbody.querySelector( '[data-provider-id="' + id + '"]' );
				if ( row ) {
					row.remove();
				}

				if ( ! tbody.querySelector( 'tr' ) ) {
					appendEmptyRow();
				}
			} );
		}

		function handleSetDefault( btn ) {
			var providerId = btn.dataset.providerId;
			btn.disabled = true;

			bewApiRequest( 'bew_set_default_ai_provider', { provider_id: providerId }, function ( success, data ) {
				if ( ! success ) {
					btn.disabled = false;
					window.alert( errorTextFrom( data, bewAdmin.aiErrorMessage ) );
					return;
				}

				applyDefaultProviderUI( providerId );
			} );
		}

		tbody.addEventListener( 'click', function ( e ) {
			var deleteBtn = e.target.closest( '.bew-ai-provider-delete' );
			if ( deleteBtn ) {
				handleDelete( deleteBtn );
				return;
			}

			var defaultBtn = e.target.closest( '.bew-ai-provider-set-default' );
			if ( defaultBtn && ! defaultBtn.disabled ) {
				handleSetDefault( defaultBtn );
			}
		} );
	}

	document.addEventListener( 'DOMContentLoaded', init );
}() );
