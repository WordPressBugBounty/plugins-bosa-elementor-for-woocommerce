/* global elementor, $e, jQuery, bewAiEditor */
( function ( $ ) {
	'use strict';

	// Shared Provider/Model field UI, used by every AI card -- Pro's scripts
	// depend on this file loading first. Defined ahead of the bewAiEditor
	// guard below so it's available even when this script's own card never
	// activates.
	window.BEWAIModelField = {
		optionLabel: function ( provider ) {
			return provider.type_label ? provider.label + ' – ' + provider.type_label : provider.label;
		},
		html: function ( modelFieldLabel, models, selectedModelId ) {
			if ( ! models || models.length < 2 ) {
				return '';
			}
			var div = document.createElement( 'div' );
			function esc( str ) { div.textContent = str; return div.innerHTML; }
			var options = models.map( function ( m ) {
				var selected = m.id === selectedModelId ? ' selected' : '';
				return '<option value="' + esc( m.id ) + '"' + selected + '>' + esc( m.label ) + '</option>';
			} ).join( '' );
			return '<label class="bew-ai-editor-instruction-label">' + esc( modelFieldLabel ) + '</label>' +
				'<select class="bew-ai-editor-model-select">' + options + '</select>';
		},
		// `holder` is an empty element already in the card's markup (e.g.
		// <div class="bew-ai-editor-model-field"></div>) -- called once for
		// the initially-selected provider, then again on every Provider change.
		refresh: function ( holder, modelFieldLabel, models, selectedModelId ) {
			if ( ! holder ) {
				return;
			}
			holder.innerHTML = this.html( modelFieldLabel, models, selectedModelId );
		},
	};

	// Shared Generation History UI. Every card's history-icon trigger swaps
	// the card's own content to renderView() for that field -- storage,
	// formatting, and restore logic all live here once instead of per-card.
	//
	// `opts`: { ajaxUrl, nonce, ownerType, ownerId, fieldKey, roleLabel, i18n }.
	// `i18n` must include the historyXxx keys (bewAiEditor.i18n does; a Pro
	// card's own separate i18n object usually doesn't). `fieldKey` is opaque
	// here -- see class-bew-ai-history.php for how to build one.
	window.BEWAIHistory = {
		// Reads bewAiEditor.i18n directly rather than the calling card's own
		// i18n -- History's copy is shared/global, and every Pro script
		// already depends on bew-ai-editor loading first.
		_i18n: function () {
			return ( window.bewAiEditor && bewAiEditor.i18n ) || {};
		},

		// Same glyph as core Elementor's own History icon (filled, not the
		// feather-style stroked icons used elsewhere here) so this reads as
		// "history" the same way it already does in native Elementor UI.
		iconHtml: function () {
			var i18n = this._i18n();
			return '<button type="button" class="bew-ai-history-icon" title="' + this._esc( i18n.historyIconLabel ) + '" aria-label="' + this._esc( i18n.historyIconLabel ) + '">' +
				'<svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" clip-rule="evenodd" d="M8.08961 4.0956C10.0932 3.02269 12.4216 2.72496 14.6307 3.25921C16.8397 3.79346 18.7748 5.1223 20.0667 6.99219C21.3585 8.86208 21.9168 11.1421 21.6349 13.3973C21.353 15.6525 20.2507 17.725 18.5383 19.2194C16.8259 20.7137 14.6233 21.5254 12.3506 21.4994C10.078 21.4734 7.89454 20.6117 6.21673 19.0786C4.53891 17.5456 3.48423 15.4484 3.25392 13.1874C3.21194 12.7753 3.51197 12.4072 3.92405 12.3652C4.33614 12.3233 4.70422 12.6233 4.7462 13.0354C4.93916 14.9298 5.82281 16.6868 7.22855 17.9713C8.63428 19.2558 10.4637 19.9777 12.3678 19.9995C14.2719 20.0212 16.1173 19.3412 17.552 18.0892C18.9867 16.8372 19.9103 15.1008 20.1464 13.2113C20.3826 11.3218 19.9149 9.41147 18.8325 7.84481C17.7502 6.27814 16.1289 5.16479 14.2781 4.71718C12.4272 4.26956 10.4764 4.51901 8.79772 5.41794C7.44561 6.14199 6.34633 7.24658 5.62839 8.58361H8.72228C9.13649 8.58361 9.47228 8.91939 9.47228 9.33361C9.47228 9.74782 9.13649 10.0836 8.72228 10.0836H4.48963C4.47805 10.0839 4.46644 10.0839 4.4548 10.0836H4.00006C3.58584 10.0836 3.25006 9.74782 3.25006 9.33361V4.61139C3.25006 4.19717 3.58584 3.86139 4.00006 3.86139C4.41427 3.86139 4.75006 4.19717 4.75006 4.61139V7.1337C5.58912 5.86995 6.73269 4.82222 8.08961 4.0956ZM12.4528 8.27753C12.867 8.27753 13.2028 8.61332 13.2028 9.02753V12.4946L14.872 14.1639C15.1649 14.4568 15.1649 14.9316 14.872 15.2245C14.5792 15.5174 14.1043 15.5174 13.8114 15.2245L11.9225 13.3356C11.7818 13.195 11.7028 13.0042 11.7028 12.8053V9.02753C11.7028 8.61332 12.0386 8.27753 12.4528 8.27753Z"></path></svg>' +
				'</button>';
		},

		_esc: function ( str ) {
			var div = document.createElement( 'div' );
			div.textContent = str || '';
			return div.innerHTML;
		},

		// Feather-style stroked SVG (distinct from iconHtml() above, which
		// now uses a filled Elementor-core glyph) -- a trash-can, used on
		// both the single-field (renderView) and paginated-items
		// (renderItemsView) delete buttons instead of a bare "×" so it
		// can't be mistaken for "close this panel".
		_deleteIconSvg: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',

		_ajax: function ( opts, action, extra, cb ) {
			var body = new FormData();
			body.append( 'action', action );
			body.append( 'nonce', opts.nonce );
			body.append( 'owner_type', opts.ownerType );
			body.append( 'owner_id', opts.ownerId );
			Object.keys( extra || {} ).forEach( function ( key ) {
				body.append( key, extra[ key ] );
			} );
			fetch( opts.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( json ) { cb( !! json.success, json.data ); } )
				.catch( function () { cb( false, null ); } );
		},

		fetchEntries: function ( opts, cb ) {
			this._ajax( opts, 'bew_ai_get_history', { field_key: opts.fieldKey }, function ( success, data ) {
				cb( success && data && data.entries ? data.entries : [] );
			} );
		},

		// `entry`: { value, providerLabel, modelLabel, instruction } -- value
		// may be a string, or an object/array (image {id,url}, repeater rows);
		// JSON-encoded here so the server decodes exactly one shape, never
		// guessing from raw bytes (see class-bew-ai-history.php).
		logEntry: function ( opts, entry, cb ) {
			this._ajax( opts, 'bew_ai_log_history', {
				field_key: opts.fieldKey,
				value: JSON.stringify( 'undefined' === typeof entry.value ? null : entry.value ),
				provider_label: entry.providerLabel || '',
				model_label: entry.modelLabel || '',
				instruction: entry.instruction || '',
			}, function ( success ) { if ( cb ) { cb( success ); } } );
		},

		deleteEntry: function ( opts, entryId, cb ) {
			this._ajax( opts, 'bew_ai_delete_history_item', { field_key: opts.fieldKey, entry_id: entryId }, cb );
		},

		// `fieldKey` omitted clears every field for this owner; passed clears
		// just that field. Kept as an explicit param rather than reading
		// opts.fieldKey (which every caller already sets for other purposes)
		// so "clear everything" callers don't silently get narrowed scope.
		clearAll: function ( opts, fieldKey, cb ) {
			this._ajax( opts, 'bew_ai_clear_history', fieldKey ? { field_key: fieldKey } : {}, cb );
		},

		_relativeTime: function ( i18n, unixSeconds ) {
			var diff = Math.max( 0, Math.floor( Date.now() / 1000 ) - unixSeconds );
			if ( diff < 60 ) {
				return i18n.historyJustNow;
			}
			if ( diff < 3600 ) {
				return i18n.historyMinutesAgo.replace( '%d', Math.floor( diff / 60 ) );
			}
			if ( diff < 86400 ) {
				return i18n.historyHoursAgo.replace( '%d', Math.floor( diff / 3600 ) );
			}
			return i18n.historyDaysAgo.replace( '%d', Math.floor( diff / 86400 ) );
		},

		// A history value is a plain string, an {id,url} image object, or a
		// repeater's array of row objects -- render whichever it is as a
		// short, safe-to-display snippet rather than assuming it's text.
		_valueSnippet: function ( value ) {
			var self = this;
			if ( Array.isArray( value ) ) {
				return value.length + ' item' + ( 1 === value.length ? '' : 's' );
			}
			if ( value && 'object' === typeof value ) {
				if ( value.url ) {
					return '<img class="bew-ai-history-thumb" src="' + self._esc( value.url ) + '" alt="" />';
				}
				return self._esc( Object.keys( value ).map( function ( k ) { return String( value[ k ] || '' ); } ).join( ' · ' ) );
			}
			var text = String( value || '' );
			return self._esc( text.length > 90 ? text.slice( 0, 90 ) + '…' : text );
		},

		// Replaces `card`'s own content with the history list for one field.
		// `opts` additionally needs: onRestore(value, done), onRestored()
		// (optional, called after a successful restore), onBack().
		renderView: function ( card, opts ) {
			var self = this;
			var i18n = self._i18n();

			card.innerHTML =
				'<div class="bew-ai-history-back"><i class="bew-ai-history-back-arrow">&larr;</i> ' + self._esc( i18n.historyBack ) + '</div>' +
				'<h4>' + self._esc( i18n.historyTitle.replace( '%s', opts.roleLabel ) ) + '</h4>' +
				'<div class="bew-ai-history-list">' + self._esc( i18n.historyLoading ) + '</div>' +
				'<p class="bew-ai-history-clear">' + self._esc( i18n.historyClearAll ) + '</p>';

			card.querySelector( '.bew-ai-history-back' ).addEventListener( 'click', function () {
				opts.onBack();
			} );

			card.querySelector( '.bew-ai-history-clear' ).addEventListener( 'click', function ( e ) {
				self._confirmClear( e.currentTarget, opts );
			} );

			self.fetchEntries( opts, function ( entries ) {
				var listEl = card.querySelector( '.bew-ai-history-list' );
				if ( ! listEl ) {
					return; // Card moved on (Back/close) before the fetch resolved.
				}

				if ( ! entries.length ) {
					listEl.innerHTML = '<p class="bew-ai-history-empty">' + self._esc( i18n.historyEmpty ) + '</p>';
					return;
				}

				listEl.innerHTML = entries.map( function ( entry, index ) {
					var meta = [ entry.provider_label, entry.model_label, self._relativeTime( i18n, entry.created_at ) ].filter( Boolean ).join( ' · ' );
					return '<div class="bew-ai-history-entry" data-index="' + index + '">' +
						'<div class="bew-ai-history-entry-value">' + self._valueSnippet( entry.value ) + '</div>' +
						'<div class="bew-ai-history-entry-row">' +
						'<span class="bew-ai-history-entry-meta">' + self._esc( meta ) + '</span>' +
						'<span class="bew-ai-history-entry-actions">' +
						'<button type="button" class="bew-ai-history-restore">' + self._esc( i18n.historyRestore ) + '</button>' +
						'<button type="button" class="bew-ai-history-delete" title="' + self._esc( i18n.historyDeleteLabel ) + '" aria-label="' + self._esc( i18n.historyDeleteLabel ) + '">' + self._deleteIconSvg + '</button>' +
						'</span></div></div>';
				} ).join( '' );

				listEl.querySelectorAll( '.bew-ai-history-entry' ).forEach( function ( row ) {
					var entry = entries[ Number( row.dataset.index ) ];

					row.querySelector( '.bew-ai-history-restore' ).addEventListener( 'click', function ( e ) {
						var btn = e.currentTarget;
						btn.disabled = true;
						// Full entry, not just its value -- a content-adapter
						// card's own onRestore needs the original
						// provider/model/instruction to forward into its own
						// write request (see logAfterRestore below).
						opts.onRestore( entry, function ( success ) {
							btn.disabled = false;
							if ( ! success ) {
								return;
							}
							// Elementor-settings cards write locally, so
							// nothing else logs a restore -- this does.
							// Content-adapter cards already log server-side
							// via apply_record_fields(), so logging again
							// here would double the entry; a card sets
							// logAfterRestore false when it knows that's the
							// case.
							if ( false !== opts.logAfterRestore ) {
								self.logEntry( opts, {
									value: entry.value,
									providerLabel: entry.provider_label,
									modelLabel: entry.model_label,
									instruction: entry.instruction,
								} );
							}
							if ( opts.onRestored ) {
								opts.onRestored();
							}
						} );
					} );

					row.querySelector( '.bew-ai-history-delete' ).addEventListener( 'click', function () {
						self.deleteEntry( opts, entry.id, function () {
							self.renderView( card, opts );
						} );
					} );
				} );
			} );
		},

		// `el` is the clear trigger just clicked -- resolved to its stable
		// `.bew-ai-editor-card` ancestor up front, since swapping a node's
		// own outerHTML detaches that node's own reference.
		_confirmClear: function ( el, opts ) {
			var self = this;
			var i18n = self._i18n();
			var card = el.closest( '.bew-ai-editor-card' );
			if ( ! card ) {
				return;
			}

			el.outerHTML = '<div class="bew-ai-history-clear-confirm">' +
				'<p>' + self._esc( i18n.historyClearConfirm ) + '</p>' +
				'<button type="button" class="bew-box-button bew-button-primary bew-ai-history-clear-yes">' + self._esc( i18n.historyClearYes ) + '</button>' +
				'<button type="button" class="bew-box-button bew-button-secondary bew-ai-history-clear-no">' + self._esc( i18n.historyClearNo ) + '</button>' +
				'</div>';

			card.querySelector( '.bew-ai-history-clear-yes' ).addEventListener( 'click', function () {
				self.clearAll( opts, null, function () {
					// Re-render this same field's (now empty) view -- clearer
					// feedback than silently jumping back to the main card.
					self.renderView( card, opts );
				} );
			} );

			card.querySelector( '.bew-ai-history-clear-no' ).addEventListener( 'click', function () {
				var confirmEl = card.querySelector( '.bew-ai-history-clear-confirm' );
				if ( confirmEl ) {
					confirmEl.outerHTML = '<p class="bew-ai-history-clear">' + self._esc( i18n.historyClearAll ) + '</p>';
				}
				card.querySelector( '.bew-ai-history-clear' ).addEventListener( 'click', function ( e ) {
					self._confirmClear( e.currentTarget, opts );
				} );
			} );
		},

		// Paginated-by-item History view -- for cards whose picker is a grid
		// of many items (Repeater/Products/Blog/Bulk), one entry point next
		// to "Select all" rather than one icon per field buried inside a
		// preview that only exists after spending a real generation. Shows
		// all of ONE item's mapped fields' history merged into one
		// chronological list, paginated "N of M" the same way the
		// generate-preview flow already does.
		//
		// `opts.items`: [{ ownerId, label, fields: [{fieldKey, roleLabel}] }]
		// `opts.page`: which item to show (0-based).
		// `opts.getOnRestore(item, field)` -> function(entry, done) -- built
		// per item+field so a card's own per-field restore logic is reused.
		renderItemsView: function ( card, opts ) {
			var self  = this;
			var i18n  = self._i18n();
			var items = opts.items || [];
			var page  = Math.max( 0, Math.min( opts.page || 0, items.length - 1 ) );
			var item  = items[ page ];

			// Built as ONE string, assigned to innerHTML ONCE, with every
			// listener attached only after -- a `+=` re-parse would silently
			// detach listeners already bound to earlier nodes (e.g. Back).
			var backHtml = '<div class="bew-ai-history-back"><i class="bew-ai-history-back-arrow">&larr;</i> ' + self._esc( i18n.historyBack ) + '</div>';

			if ( ! item ) {
				card.innerHTML = backHtml + '<p class="bew-ai-history-empty">' + self._esc( i18n.historyNoItems ) + '</p>';
				card.querySelector( '.bew-ai-history-back' ).addEventListener( 'click', function () {
					opts.onBack();
				} );
				return;
			}

			card.innerHTML = backHtml +
				'<h4>' + self._esc( i18n.historyItemTitle.replace( '%1$d', page + 1 ).replace( '%2$d', items.length ) ) + '</h4>' +
				'<p class="bew-ai-history-item-label">' + self._esc( item.label ) + '</p>' +
				'<div class="bew-ai-history-list">' + self._esc( i18n.historyLoading ) + '</div>' +
				( items.length > 1
					? '<div class="bew-ai-history-pagination">' +
						'<button type="button" class="bew-box-button bew-button-secondary bew-ai-history-prev"' + ( 0 === page ? ' disabled' : '' ) + '>← ' + self._esc( i18n.historyPrevious ) + '</button>' +
						'<button type="button" class="bew-box-button bew-button-secondary bew-ai-history-next"' + ( page >= items.length - 1 ? ' disabled' : '' ) + '>' + self._esc( i18n.historyNext ) + ' →</button>' +
						'</div>'
					: '' ) +
				'<p class="bew-ai-history-clear">' + self._esc( i18n.historyClearAllItem ) + '</p>';

			card.querySelector( '.bew-ai-history-back' ).addEventListener( 'click', function () {
				opts.onBack();
			} );

			var prevBtn = card.querySelector( '.bew-ai-history-prev' );
			var nextBtn = card.querySelector( '.bew-ai-history-next' );
			if ( prevBtn ) {
				prevBtn.addEventListener( 'click', function () {
					self.renderItemsView( card, Object.assign( {}, opts, { page: page - 1 } ) );
				} );
			}
			if ( nextBtn ) {
				nextBtn.addEventListener( 'click', function () {
					self.renderItemsView( card, Object.assign( {}, opts, { page: page + 1 } ) );
				} );
			}

			card.querySelector( '.bew-ai-history-clear' ).addEventListener( 'click', function ( e ) {
				self._confirmClearFields( e.currentTarget, opts, item );
			} );

			if ( ! item.fields.length ) {
				card.querySelector( '.bew-ai-history-list' ).innerHTML = '<p class="bew-ai-history-empty">' + self._esc( i18n.historyEmpty ) + '</p>';
				return;
			}

			var fieldOptsList = item.fields.map( function ( f ) {
				return { ajaxUrl: opts.ajaxUrl, nonce: opts.nonce, ownerType: opts.ownerType, ownerId: item.ownerId, fieldKey: f.fieldKey, roleLabel: f.roleLabel };
			} );

			var pending = fieldOptsList.length;
			var merged  = [];

			fieldOptsList.forEach( function ( fieldOpts ) {
				self.fetchEntries( fieldOpts, function ( entries ) {
					entries.forEach( function ( entry ) {
						merged.push( { entry: entry, fieldOpts: fieldOpts } );
					} );
					pending--;
					if ( 0 === pending ) {
						renderMerged();
					}
				} );
			} );

			function renderMerged() {
				var listEl = card.querySelector( '.bew-ai-history-list' );
				if ( ! listEl ) {
					return; // Card moved on (Back/Prev/Next) before every field's fetch resolved.
				}

				merged.sort( function ( a, b ) { return b.entry.created_at - a.entry.created_at; } );

				if ( ! merged.length ) {
					listEl.innerHTML = '<p class="bew-ai-history-empty">' + self._esc( i18n.historyEmpty ) + '</p>';
					return;
				}

				listEl.innerHTML = merged.map( function ( m, index ) {
					var entry = m.entry;
					var meta  = [ entry.provider_label, entry.model_label, self._relativeTime( i18n, entry.created_at ) ].filter( Boolean ).join( ' · ' );
					return '<div class="bew-ai-history-entry" data-index="' + index + '">' +
						'<div class="bew-ai-history-entry-field">' + self._esc( m.fieldOpts.roleLabel ) + '</div>' +
						'<div class="bew-ai-history-entry-value">' + self._valueSnippet( entry.value ) + '</div>' +
						'<div class="bew-ai-history-entry-row">' +
						'<span class="bew-ai-history-entry-meta">' + self._esc( meta ) + '</span>' +
						'<span class="bew-ai-history-entry-actions">' +
						'<button type="button" class="bew-ai-history-restore">' + self._esc( i18n.historyRestore ) + '</button>' +
						'<button type="button" class="bew-ai-history-delete" title="' + self._esc( i18n.historyDeleteLabel ) + '" aria-label="' + self._esc( i18n.historyDeleteLabel ) + '">' + self._deleteIconSvg + '</button>' +
						'</span></div></div>';
				} ).join( '' );

				listEl.querySelectorAll( '.bew-ai-history-entry' ).forEach( function ( row ) {
					var m         = merged[ Number( row.dataset.index ) ];
					var entry     = m.entry;
					var fieldOpts = m.fieldOpts;
					var field     = item.fields.filter( function ( f ) { return f.fieldKey === fieldOpts.fieldKey; } )[ 0 ];

					row.querySelector( '.bew-ai-history-restore' ).addEventListener( 'click', function ( e ) {
						var btn = e.currentTarget;
						btn.disabled = true;
						var onRestore = opts.getOnRestore( item, field );
						onRestore( entry, function ( success ) {
							btn.disabled = false;
							if ( ! success ) {
								return;
							}
							if ( false !== opts.logAfterRestore ) {
								self.logEntry( fieldOpts, {
									value: entry.value,
									providerLabel: entry.provider_label,
									modelLabel: entry.model_label,
									instruction: entry.instruction,
								} );
							}
							if ( opts.onRestored ) {
								opts.onRestored();
							}
						} );
					} );

					row.querySelector( '.bew-ai-history-delete' ).addEventListener( 'click', function () {
						self.deleteEntry( fieldOpts, entry.id, function () {
							self.renderItemsView( card, opts );
						} );
					} );
				} );
			}
		},

		// Same two-step confirm as _confirmClear, but clears every one of
		// `item`'s own field keys, not the whole owner -- for a repeater
		// (owner is the whole page, shared by every row), "clear all" here
		// means "clear what I'm looking at", narrower than clearing the page.
		_confirmClearFields: function ( el, opts, item ) {
			var self = this;
			var i18n = self._i18n();
			var card = el.closest( '.bew-ai-editor-card' );
			if ( ! card ) {
				return;
			}

			el.outerHTML = '<div class="bew-ai-history-clear-confirm">' +
				'<p>' + self._esc( i18n.historyClearConfirm ) + '</p>' +
				'<button type="button" class="bew-box-button bew-button-primary bew-ai-history-clear-yes">' + self._esc( i18n.historyClearYes ) + '</button>' +
				'<button type="button" class="bew-box-button bew-button-secondary bew-ai-history-clear-no">' + self._esc( i18n.historyClearNo ) + '</button>' +
				'</div>';

			card.querySelector( '.bew-ai-history-clear-yes' ).addEventListener( 'click', function () {
				var remaining = item.fields.length;
				if ( ! remaining ) {
					self.renderItemsView( card, opts );
					return;
				}
				item.fields.forEach( function ( f ) {
					var fieldOpts = { ajaxUrl: opts.ajaxUrl, nonce: opts.nonce, ownerType: opts.ownerType, ownerId: item.ownerId };
					self.clearAll( fieldOpts, f.fieldKey, function () {
						remaining--;
						if ( 0 === remaining ) {
							self.renderItemsView( card, opts );
						}
					} );
				} );
			} );

			card.querySelector( '.bew-ai-history-clear-no' ).addEventListener( 'click', function () {
				var confirmEl = card.querySelector( '.bew-ai-history-clear-confirm' );
				if ( confirmEl ) {
					confirmEl.outerHTML = '<p class="bew-ai-history-clear">' + self._esc( i18n.historyClearAllItem ) + '</p>';
				}
				card.querySelector( '.bew-ai-history-clear' ).addEventListener( 'click', function ( e ) {
					self._confirmClearFields( e.currentTarget, opts, item );
				} );
			} );
		},
	};

	// Saved instruction presets -- a named, reusable instruction (e.g.
	// "Casual bakery voice") loaded into any card's instruction box instead
	// of retyping it. Storage is global (one flat list), not scoped to a
	// field/owner like History, since the point is reusing one voice across
	// many pages/widgets/records. Mandatory in every card, same free/Pro
	// line as History: drawn at the generator level, not inside a shared
	// control. Self-contained (own _esc/_ajax) so Pro cards only depend on
	// this file having loaded, not on load order within it.
	window.BEWAIPresets = {
		_i18n: function () {
			return ( window.bewAiEditor && bewAiEditor.i18n ) || {};
		},

		_esc: function ( str ) {
			var div = document.createElement( 'div' );
			div.textContent = str || '';
			return div.innerHTML;
		},

		_renameIconSvg: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
		_deleteIconSvg: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>',
		_manageIconSvg: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path></svg>',
		_saveIconSvg: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>',

		_ajax: function ( action, data, cb ) {
			var body = new FormData();
			body.append( 'action', action );
			body.append( 'nonce', bewAiEditor.nonce );
			Object.keys( data || {} ).forEach( function ( key ) {
				body.append( key, data[ key ] );
			} );
			fetch( bewAiEditor.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( res ) { return res.json(); } )
				.then( function ( json ) { cb( !! json.success, json.data ); } )
				.catch( function () { cb( false, null ); } );
		},

		fetchAll: function ( cb ) {
			this._ajax( 'bew_ai_get_presets', {}, function ( success, data ) {
				cb( success && data && data.presets ? data.presets : [] );
			} );
		},

		save: function ( id, label, instruction, cb ) {
			this._ajax( 'bew_ai_save_preset', { id: id || '', label: label, instruction: instruction }, function ( success, data ) {
				cb( success, success && data ? data.id : null );
			} );
		},

		deleteOne: function ( id, cb ) {
			this._ajax( 'bew_ai_delete_preset', { id: id }, cb );
		},

		// One instruction block: label + "Manage presets" link, an empty
		// preset dropdown (populated by init() below), the note, textarea,
		// and "Save as preset" trigger.
		//
		// `opts.key` must be a class unique to this textarea WITHIN its card
		// -- e.g. a card-specific class when a card has two instruction
		// boxes that both also carry a shared class.
		instructionBlockHtml: function ( opts ) {
			var self = this;
			var i18n = self._i18n();
			return '<div class="bew-ai-preset-header">' +
					'<label class="bew-ai-editor-instruction-label">' + self._esc( opts.label ) + '</label>' +
					'<span class="bew-ai-preset-manage-link" data-preset-target="' + self._esc( opts.key ) + '">' + self._manageIconSvg + self._esc( i18n.presetManage ) + '</span>' +
				'</div>' +
				'<select class="bew-ai-preset-select" data-preset-target="' + self._esc( opts.key ) + '">' +
					'<option value="">' + self._esc( i18n.presetLoadPlaceholder ) + '</option>' +
				'</select>' +
				'<p class="bew-ai-editor-instruction-note">' + self._esc( opts.note ) + '</p>' +
				'<textarea class="' + opts.textareaClass + '" placeholder="' + self._esc( opts.placeholder ) + '">' + self._esc( opts.value || '' ) + '</textarea>' +
				'<div class="bew-ai-preset-save-row">' +
					'<span class="bew-ai-preset-save" data-preset-target="' + self._esc( opts.key ) + '">' + self._saveIconSvg + self._esc( i18n.presetSaveAs ) + '</span>' +
				'</div>';
		},

		// Call once, right after `card`'s innerHTML (with one or more
		// instructionBlockHtml() blocks) is in the DOM. Fetches the preset
		// list ONCE per card, even with multiple instruction boxes, and
		// wires every dropdown/save-link/manage-link against it.
		// `opts.onBack` re-renders whichever view the card was showing
		// before "Manage presets" was clicked.
		init: function ( card, opts ) {
			var self = this;
			opts = opts || {};
			self.fetchAll( function ( presets ) {
				if ( ! card.querySelector( '.bew-ai-preset-select' ) ) {
					return; // Card moved on before the fetch resolved.
				}
				self._populateSelects( card, presets );
				self._wireSelects( card, presets );
				self._wireSaveLinks( card );
				self._wireManageLinks( card, opts );
			} );
		},

		_populateSelects: function ( card, presets ) {
			var self = this;
			var i18n = self._i18n();
			card.querySelectorAll( '.bew-ai-preset-select' ).forEach( function ( sel ) {
				var options = presets.map( function ( p ) {
					return '<option value="' + self._esc( p.id ) + '">' + self._esc( p.label ) + '</option>';
				} ).join( '' );
				sel.innerHTML = '<option value="">' + self._esc( i18n.presetLoadPlaceholder ) + '</option>' + options;
			} );
		},

		// Loading a preset fills the textarea and resets the select back to
		// its placeholder -- it's a one-shot "paste this in," not a
		// persistent link between the box and whichever preset filled it, so
		// editing the textarea afterward doesn't silently drift out of sync
		// with a still-selected option.
		_wireSelects: function ( card, presets ) {
			card.querySelectorAll( '.bew-ai-preset-select' ).forEach( function ( sel ) {
				sel.addEventListener( 'change', function () {
					if ( ! sel.value ) {
						return;
					}
					var preset = presets.filter( function ( p ) { return p.id === sel.value; } )[ 0 ];
					var textarea = card.querySelector( '.' + sel.dataset.presetTarget );
					if ( preset && textarea ) {
						textarea.value = preset.instruction;
					}
					sel.value = '';
				} );
			} );
		},

		_wireSaveLinks: function ( card ) {
			var self = this;
			card.querySelectorAll( '.bew-ai-preset-save' ).forEach( function ( link ) {
				link.addEventListener( 'click', function () {
					var key = link.dataset.presetTarget;
					var textarea = card.querySelector( '.' + key );
					self._openSaveForm( card, key, textarea );
				} );
			} );
		},

		_openSaveForm: function ( card, key, textarea ) {
			var self = this;
			var i18n = self._i18n();

			if ( ! textarea || ! textarea.value.trim() ) {
				var status = card.querySelector( '.bew-ai-editor-card-status' );
				if ( status ) {
					status.textContent = i18n.presetSaveNeedsText;
				}
				return;
			}

			var row = card.querySelector( '.bew-ai-preset-save-row .bew-ai-preset-save[data-preset-target="' + key + '"]' );
			row = row && row.closest( '.bew-ai-preset-save-row' );
			if ( ! row ) {
				return;
			}

			row.innerHTML =
				'<input type="text" class="bew-ai-preset-name-input" placeholder="' + self._esc( i18n.presetNamePlaceholder ) + '" />' +
				'<span class="bew-ai-preset-save-confirm">' + self._esc( i18n.presetSaveConfirm ) + '</span>' +
				'<span class="bew-ai-preset-save-cancel">' + self._esc( i18n.presetSaveCancel ) + '</span>';

			var input = row.querySelector( '.bew-ai-preset-name-input' );
			input.focus();

			row.querySelector( '.bew-ai-preset-save-cancel' ).addEventListener( 'click', function () {
				self._resetSaveRow( card, row, key );
			} );

			row.querySelector( '.bew-ai-preset-save-confirm' ).addEventListener( 'click', function () {
				var label = input.value.trim();
				if ( ! label ) {
					return;
				}
				self.save( '', label, textarea.value, function ( success, id ) {
					if ( ! success ) {
						return;
					}
					self._resetSaveRow( card, row, key );
					// Immediately choosable without a reload/refetch.
					var sel = card.querySelector( '.bew-ai-preset-select[data-preset-target="' + key + '"]' );
					if ( sel ) {
						var opt = document.createElement( 'option' );
						opt.value = id;
						opt.textContent = label;
						sel.appendChild( opt );
					}
				} );
			} );
		},

		_resetSaveRow: function ( card, row, key ) {
			var self = this;
			var i18n = self._i18n();
			row.innerHTML = '<span class="bew-ai-preset-save" data-preset-target="' + self._esc( key ) + '">' + self._saveIconSvg + self._esc( i18n.presetSaveAs ) + '</span>';
			row.querySelector( '.bew-ai-preset-save' ).addEventListener( 'click', function () {
				var textarea = card.querySelector( '.' + key );
				self._openSaveForm( card, key, textarea );
			} );
		},

		_wireManageLinks: function ( card, opts ) {
			var self = this;
			card.querySelectorAll( '.bew-ai-preset-manage-link' ).forEach( function ( link ) {
				link.addEventListener( 'click', function () {
					self.renderManageView( card, opts );
				} );
			} );
		},

		// Full card-content swap, not a partial patch -- same convention
		// BEWAIHistory's own renderView/renderItemsView already use for
		// every view transition in this system. Flat list, no pagination
		// (unlike History's paginated-by-item view): presets are a short,
		// user-curated list, not an auto-logged one that grows unbounded.
		renderManageView: function ( card, opts ) {
			var self = this;
			var i18n = self._i18n();

			card.innerHTML =
				'<div class="bew-ai-history-back"><i class="bew-ai-history-back-arrow">&larr;</i> ' + self._esc( i18n.historyBack ) + '</div>' +
				'<h4>' + self._esc( i18n.presetsListTitle ) + '</h4>' +
				'<div class="bew-ai-history-list">' + self._esc( i18n.historyLoading ) + '</div>';

			card.querySelector( '.bew-ai-history-back' ).addEventListener( 'click', function () {
				opts.onBack();
			} );

			self.fetchAll( function ( presets ) {
				var listEl = card.querySelector( '.bew-ai-history-list' );
				if ( ! listEl ) {
					return; // Card moved on (Back) before the fetch resolved.
				}

				if ( ! presets.length ) {
					listEl.innerHTML = '<p class="bew-ai-history-empty">' + self._esc( i18n.presetsEmpty ) + '</p>';
					return;
				}

				listEl.innerHTML = presets.map( function ( preset, index ) {
					var snippet = preset.instruction.length > 90 ? preset.instruction.slice( 0, 90 ) + '…' : preset.instruction;
					return '<div class="bew-ai-history-entry" data-index="' + index + '">' +
						'<div class="bew-ai-preset-row-label">' + self._esc( preset.label ) + '</div>' +
						'<div class="bew-ai-history-entry-value">' + self._esc( snippet ) + '</div>' +
						'<div class="bew-ai-history-entry-row">' +
						'<span></span>' +
						'<span class="bew-ai-history-entry-actions">' +
						'<button type="button" class="bew-ai-preset-rename" title="' + self._esc( i18n.presetRenameLabel ) + '" aria-label="' + self._esc( i18n.presetRenameLabel ) + '">' + self._renameIconSvg + '</button>' +
						'<button type="button" class="bew-ai-history-delete" title="' + self._esc( i18n.presetDeleteLabel ) + '" aria-label="' + self._esc( i18n.presetDeleteLabel ) + '">' + self._deleteIconSvg + '</button>' +
						'</span></div></div>';
				} ).join( '' );

				listEl.querySelectorAll( '.bew-ai-history-entry' ).forEach( function ( row ) {
					var preset = presets[ Number( row.dataset.index ) ];

					row.querySelector( '.bew-ai-preset-rename' ).addEventListener( 'click', function () {
						self._openRenameForm( row, preset );
					} );

					row.querySelector( '.bew-ai-history-delete' ).addEventListener( 'click', function () {
						self.deleteOne( preset.id, function () {
							self.renderManageView( card, opts );
						} );
					} );
				} );
			} );
		},

		_openRenameForm: function ( row, preset ) {
			var self = this;
			var i18n = self._i18n();
			var labelEl = row.querySelector( '.bew-ai-preset-row-label' );
			if ( ! labelEl ) {
				return;
			}

			labelEl.outerHTML = '<div class="bew-ai-preset-rename-form">' +
				'<input type="text" class="bew-ai-preset-name-input" value="' + self._esc( preset.label ) + '" />' +
				'<span class="bew-ai-preset-rename-confirm">' + self._esc( i18n.presetSaveConfirm ) + '</span>' +
				'<span class="bew-ai-preset-rename-cancel">' + self._esc( i18n.presetSaveCancel ) + '</span>' +
			'</div>';

			var form = row.querySelector( '.bew-ai-preset-rename-form' );
			var input = form.querySelector( '.bew-ai-preset-name-input' );
			input.focus();

			form.querySelector( '.bew-ai-preset-rename-cancel' ).addEventListener( 'click', function () {
				form.outerHTML = '<div class="bew-ai-preset-row-label">' + self._esc( preset.label ) + '</div>';
			} );

			form.querySelector( '.bew-ai-preset-rename-confirm' ).addEventListener( 'click', function () {
				var newLabel = input.value.trim();
				if ( ! newLabel ) {
					return;
				}
				self.save( preset.id, newLabel, preset.instruction, function ( success ) {
					if ( ! success ) {
						return;
					}
					preset.label = newLabel;
					form.outerHTML = '<div class="bew-ai-preset-row-label">' + self._esc( newLabel ) + '</div>';
				} );
			} );
		},
	};

	// Shared post-write display refresh, used by every card (this file's own
	// Apply/Restore handlers and every Pro card's) right after
	// $e.run('document/elements/settings', ...) -- that command updates the
	// model but not a control's own DOM input. This used to be patched up by
	// forcing elementor.getPanelView().getCurrentPageView().render(), but
	// calling that directly, outside Elementor's own tab-routing flow, was
	// confirmed live to break the editor's Content/Style/Advanced tabs for
	// the rest of the session: activateTab() only re-binds its tab click
	// handlers on a successful tab click, never automatically after an
	// arbitrary render, and Elementor's own next real tab-switch attempt
	// then throws inside getElementData() reading a property off whatever
	// render() detached. A direct DOM value write sidesteps Marionette's
	// view lifecycle entirely, so it can't touch either.
	//
	// `settings`: the same { fieldKey: value } map just passed to
	// $e.run('document/elements/settings', ...). A plain string writes
	// straight into that control's own input. A repeater's array of rows
	// (e.g. Repeater Generator on Image Carousel) needs more: its control
	// view's OWN row collection is a separate object from the widget's
	// settings model (confirmed live -- `controlView.collection !==
	// container.settings.get(key)`), so the array landing in the model
	// doesn't reach it on its own. Elementor has no public "resync this
	// repeater" call, but Backbone's own Collection#set with `merge: true`
	// updates each row's existing model in place (matching by `_id`)
	// instead of constructing new ones -- constructing fresh row models
	// directly, e.g. via `.reset()`, throws inside Elementor's own
	// row-model initialize() (confirmed live), because it skips
	// Elementor-internal construction context .set()'s merge path doesn't
	// need. Re-rendering just that one control view afterward refreshes
	// the collapsed row title shown for each item.
	//
	// That alone still leaves each row's own EXPANDED sub-fields (e.g.
	// this widget's Image/Image Name inputs) stale, because every one of
	// a row's field-control views reads from a THIRD object -- a
	// row-scoped `elementSettingsModel` they all share (confirmed live:
	// identical model `cid` across every field of one row) -- distinct
	// from both the collection row model above and the widget's own
	// settings. Pushing the new row data into that shared model too, then
	// re-rendering each of that row's field views, is what actually
	// clears the stale Image/Image Name display.
	//
	// Image/link (plain-object) values on a non-repeater field are still
	// skipped entirely -- no single input to write to, and no live-
	// verified safe refresh for that control type yet.
	window.BEWAIFieldRefresh = function ( settings ) {
		var pageView = ( window.elementor && elementor.getPanelView && elementor.getPanelView() )
			? elementor.getPanelView().getCurrentPageView()
			: null;

		Object.keys( settings ).forEach( function ( key ) {
			var value = settings[ key ];

			if ( Array.isArray( value ) ) {
				if ( ! pageView || ! pageView.children ) {
					return;
				}
				var controlView = null;
				pageView.children.each( function ( child ) {
					if ( child.model && child.model.get && key === child.model.get( 'name' ) ) {
						controlView = child;
					}
				} );
				if ( controlView && controlView.collection ) {
					try {
						controlView.collection.set( value, { merge: true } );
						controlView.render();

						if ( controlView.children && controlView.children.each ) {
							controlView.children.each( function ( rowView ) {
								if ( ! rowView.model || ! rowView.children || ! rowView.children.each ) {
									return;
								}
								var rowId  = rowView.model.get( '_id' );
								var newRow = value.filter( function ( r ) { return r._id === rowId; } )[ 0 ];
								if ( ! newRow ) {
									return;
								}
								var esm = null;
								rowView.children.each( function ( subView ) {
									if ( ! esm && subView.elementSettingsModel ) {
										esm = subView.elementSettingsModel;
									}
								} );
								if ( ! esm ) {
									return;
								}
								esm.set( newRow );
								rowView.children.each( function ( subView ) {
									try {
										subView.render();
									} catch ( e ) {
										// Non-critical: the underlying data is already correct either way.
									}
								} );
							} );
						}
					} catch ( e ) {
						// Non-critical: the underlying data is already correct either way.
					}
				}
				return;
			}

			if ( 'string' !== typeof value ) {
				return;
			}
			var input = document.querySelector(
				'#elementor-controls .elementor-control-' + key + ' textarea,' +
				'#elementor-controls .elementor-control-' + key + ' input[type="text"],' +
				'#elementor-controls .elementor-control-' + key + ' input[type="url"]'
			);
			if ( input ) {
				input.value = value;
			}
		} );
	};

	if ( 'undefined' === typeof bewAiEditor ) {
		return;
	}

	// elementor-common/-editor-modules/-editor-document being enqueued guarantees the
	// SCRIPT FILES have loaded, not that the `elementor`/`$e` singletons are fully
	// initialized yet -- registering panel/open_editor/widget before that point is a
	// silent no-op. 'elementor:loaded' (used internally by Elementor itself for the
	// same reason) is the real signal that the editor is ready.
	$( window ).on( 'elementor:loaded', init );

	function init() {

	var i18n = bewAiEditor.i18n;

	// Tracks which field keys have already been AI-regenerated once in this
	// editor session (element id + field key) -- resets on reload by design,
	// so a field's first-ever generation still gets the richer context.
	var generatedThisSession = {};

	var state = {
		model: null,
		widgetType: null,
		eligibleFields: [], // [{ key, role, repeater, subFields }]
		card: null,
		lastGenerateForm: null, // { checkedKeys, providerId, instruction } -- in-progress selections on the field-picker card, kept live so an accidental outside click doesn't reset them.
		lastPreview: null, // { fields, providerLabel, modelLabel } from the last generate that hasn't been Applied yet -- lets the button reopen the same preview instead of a fresh generate card, and doubles as the "don't repeat this" context for the next regenerate.
		lastFullRepeaterRows: {}, // key -> original row objects, for merge-on-apply
		controlsObserver: null, // re-places the button if collapsing/expanding a section wipes it
		selectionToken: 0, // bumped on every selection change; guards deferred setTimeout placement callbacks against firing after the user has already moved on to a different element
	};

	function escapeHtml( str ) {
		var div = document.createElement( 'div' );
		div.textContent = str;
		return div.innerHTML;
	}

	// wp_send_json_error() sends a bare string as `data` (not `{message: ...}`)
	// almost everywhere in this plugin's AJAX handlers -- handle both shapes.
	function errorTextFrom( data, fallback ) {
		if ( 'string' === typeof data && data ) {
			return data;
		}
		if ( data && data.message ) {
			return data.message;
		}
		return fallback;
	}

	// Long provider error text would blow out the card's fixed width, so past
	// this length it collapses behind a Show more/Show less toggle instead of
	// being lost off the end of a truncated string.
	var STATUS_TEXT_LIMIT = 100;

	function setStatus( status, text ) {
		status.innerHTML = '';
		text = text || '';

		if ( ! text ) {
			return;
		}

		if ( text.length <= STATUS_TEXT_LIMIT ) {
			status.textContent = text;
			return;
		}

		var shortText = text.slice( 0, STATUS_TEXT_LIMIT ).replace( /\s+$/, '' ) + '…';
		var textNode  = document.createTextNode( shortText + ' ' );
		var toggleBtn = document.createElement( 'button' );
		var expanded  = false;

		toggleBtn.type        = 'button';
		toggleBtn.className   = 'bew-ai-editor-status-more';
		toggleBtn.textContent = i18n.showMore;

		toggleBtn.addEventListener( 'click', function () {
			expanded = ! expanded;
			textNode.textContent  = ( expanded ? text : shortText ) + ' ';
			toggleBtn.textContent = expanded ? i18n.showLess : i18n.showMore;
		} );

		status.appendChild( textNode );
		status.appendChild( toggleBtn );
	}

	function humanizeRole( role ) {
		var text = String( role || '' ).replace( /_/g, ' ' );
		return text.charAt( 0 ).toUpperCase() + text.slice( 1 );
	}

	// `white` renders the icon as a solid-white mask silhouette (an
	// <img>-embedded SVG can't take currentColor) for the Generate button's
	// navy background. The header variant uses the same mask technique with
	// a light-dark() color instead -- see .bew-ai-icon-header in
	// bew-ai-editor.css.
	function iconHtml( white ) {
		if ( ! bewAiEditor.iconUrl ) {
			return '';
		}
		if ( white ) {
			return '<span class="bew-ai-icon bew-ai-icon-white"></span>';
		}
		return '<span class="bew-ai-icon bew-ai-icon-header"></span>';
	}

	function ajaxRequest( action, data, cb ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', bewAiEditor.nonce );
		Object.keys( data || {} ).forEach( function ( key ) {
			var value = data[ key ];
			body.append( key, 'string' === typeof value ? value : JSON.stringify( value ) );
		} );

		fetch( bewAiEditor.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) { cb( !! json.success, json.data ); } )
			.catch( function () { cb( false, null ); } );
	}

	// Returns descriptors for every AI-eligible field on this widget type,
	// simple text fields and repeaters alike -- a widget made entirely of
	// repeaters (e.g. an accordion) still gets the button. "_anchor" is a
	// reserved key (see findIdealAnchor) naming a different control to
	// place the button next to instead of the first eligible field's own
	// row -- it isn't itself a generatable field, so it's excluded here.
	function eligibleFieldsFor( widgetType ) {
		var entry = bewAiEditor.fieldMap[ widgetType ];
		if ( ! entry ) {
			return [];
		}
		return Object.keys( entry ).filter( function ( key ) {
			return '_anchor' !== key;
		} ).map( function ( key ) {
			var value = entry[ key ];
			if ( 'string' === typeof value ) {
				return { key: key, role: value, repeater: false, subFields: null };
			}
			return { key: key, role: null, repeater: true, subFields: value };
		} );
	}

	function repeaterLabel( field ) {
		var parts = Object.keys( field.subFields ).map( function ( k ) {
			return humanizeRole( field.subFields[ k ] );
		} );
		return parts.join( ' & ' ) + ' (all items)';
	}

	// Elementor repeater control values come through as either a plain array
	// or a Backbone Collection depending on control/version -- normalize to a
	// plain array of plain objects either way.
	function toPlainArray( raw ) {
		if ( ! raw ) {
			return [];
		}
		if ( 'function' === typeof raw.toJSON ) {
			return raw.toJSON();
		}
		if ( Array.isArray( raw ) ) {
			return raw.map( function ( item ) {
				return ( item && 'function' === typeof item.toJSON ) ? item.toJSON() : item;
			} );
		}
		return [];
	}

	// Returns { values, fullRepeaterRows }: `values` is what gets sent to the
	// server (repeater rows trimmed to only the mapped sub-fields); the full
	// original rows are kept client-side only, so Apply can merge AI output
	// back into a copy of them rather than replacing rows outright and losing
	// Elementor-internal bookkeeping (_id, any per-row style overrides).
	function currentValuesFor( fields ) {
		var settings         = state.model.get( 'settings' );
		var values           = {};
		var fullRepeaterRows = {};

		fields.forEach( function ( field ) {
			if ( field.repeater ) {
				var rows = toPlainArray( settings ? settings.get( field.key ) : null );
				fullRepeaterRows[ field.key ] = rows;
				values[ field.key ] = rows.map( function ( row ) {
					var picked = {};
					Object.keys( field.subFields ).forEach( function ( subKey ) {
						picked[ subKey ] = ( row && 'string' === typeof row[ subKey ] ) ? row[ subKey ] : '';
					} );
					return picked;
				} );
			} else {
				var value = settings ? settings.get( field.key ) : '';
				values[ field.key ] = 'string' === typeof value ? value : ( value || '' );
			}
		} );

		return { values: values, fullRepeaterRows: fullRepeaterRows };
	}

	function alreadyGeneratedFor( keys ) {
		var elementId = state.model.get( 'id' );
		var map = {};
		keys.forEach( function ( key ) {
			map[ key ] = !! generatedThisSession[ elementId + ':' + key ];
		} );
		return map;
	}

	// No beta tag here by design -- it's already shown once, inside the card
	// that opens on click; repeating it on the trigger button itself is noise.
	function makeButton() {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.id = 'bew-ai-editor-button';
		btn.className = 'bew-ai-editor-button';
		btn.innerHTML =
			iconHtml( true ) +
			'<span class="bew-ai-editor-button-label">' + escapeHtml( i18n.buttonLabel ) + '</span>';
		btn.addEventListener( 'click', onButtonClick );
		return btn;
	}

	// Elementor rebuilds #elementor-controls on every widget selection, so the
	// button has to be (re)placed each time rather than persisted -- anchored
	// next to the first eligible simple field's own label row (the same row
	// Elementor's native "Write with AI" appears in).
	function firstSimpleField( fields ) {
		return fields.filter( function ( f ) { return ! f.repeater; } )[ 0 ] || null;
	}

	// A widget's field map entry can name an explicit "_anchor" control key
	// (e.g. a group's own on/off switcher) to place the button next to
	// instead of the first eligible field's own row -- for a widget like
	// BEW's shared Compare & Wishlist buttons, where two independent text
	// fields (Compare Label, Wishlist Label) sit side by side and the
	// button generates both at once, anchoring to either one specifically
	// reads as "this button is for that field only". Falls back to the
	// original first-simple-field behavior when no widget type sets one.
	function findIdealAnchor( fields ) {
		var entry = bewAiEditor.fieldMap[ state.widgetType ] || {};
		if ( entry._anchor ) {
			var anchorRow = findControlFieldRow( entry._anchor );
			if ( anchorRow ) {
				return anchorRow;
			}
		}
		var firstSimple = firstSimpleField( fields );
		return firstSimple ? findControlFieldRow( firstSimple.key ) : null;
	}

	// Widgets with no simple field mapped at all (e.g. a purely-repeater
	// widget like an accordion) have no per-field row to hide inside the way
	// "Write with AI" does, so their button always shows, parked at the top
	// of the controls panel instead.
	function placeFallbackButton() {
		var btn = makeButton();
		var controls = document.getElementById( 'elementor-controls' );
		if ( controls ) {
			btn.classList.add( 'bew-ai-editor-button-top' );
			controls.insertBefore( btn, controls.firstChild );
			return btn;
		}

		var panel = document.getElementById( 'elementor-panel' );
		btn.classList.add( 'bew-ai-editor-button-floating' );
		( panel || document.body ).appendChild( btn );
		return btn;
	}

	// Returns the button, or null if it's correctly not shown right now (its
	// field's control section is collapsed -- same as "Write with AI", which
	// disappears too since it lives in that same, currently un-rendered row).
	function placeButton( fields ) {
		var old = document.getElementById( 'bew-ai-editor-button' );
		if ( old ) {
			old.remove();
		}

		if ( ! fields.length ) {
			return null;
		}

		if ( ! firstSimpleField( fields ) ) {
			return placeFallbackButton();
		}

		var anchorRow = findIdealAnchor( fields );
		if ( ! anchorRow ) {
			return null;
		}

		var btn = makeButton();
		// Insert after the row's own label specifically -- appending to the
		// row raced against Elementor's own, separately-timed "Write with
		// AI" insertion, so which came first was inconsistent. The label is
		// always present and renders first, making this deterministic.
		var label = anchorRow.querySelector( '.elementor-control-title' );
		if ( label ) {
			label.insertAdjacentElement( 'afterend', btn );
		} else {
			anchorRow.appendChild( btn );
		}
		return btn;
	}

	// Collapsing/re-expanding a control section (or switching Content/Style/
	// Advanced tabs and back) makes Elementor re-render that section's DOM,
	// which silently wipes out our manually-inserted button -- and nothing
	// re-fires panel/open_editor/widget for that, since the widget itself was
	// never deselected. Watch for that and either re-place the button (its
	// field is visible again) or leave it correctly absent (still collapsed).
	function watchForRemoval( fields ) {
		if ( state.controlsObserver ) {
			state.controlsObserver.disconnect();
			state.controlsObserver = null;
		}

		if ( ! fields.length ) {
			return;
		}

		// Observing #elementor-controls itself doesn't survive a Content/
		// Style/Advanced tab switch -- Elementor replaces that whole node
		// rather than mutating it. #elementor-panel-content-wrapper is the
		// closest stable ancestor (verified live).
		var controls = document.getElementById( 'elementor-panel-content-wrapper' );
		if ( ! controls ) {
			return;
		}

		var hasSimpleField = !! firstSimpleField( fields );

		state.controlsObserver = new MutationObserver( function () {
			var btn = document.getElementById( 'bew-ai-editor-button' );

			if ( ! hasSimpleField ) {
				// No per-field row to key visibility off of -- always present.
				if ( ! btn ) {
					placeButton( fields );
				}
				return;
			}

			var anchorRow = findIdealAnchor( fields );

			if ( anchorRow && ( ! btn || btn.parentElement !== anchorRow ) ) {
				// Section (re)expanded and the button isn't correctly placed
				// inside it yet -- place it now.
				placeButton( fields );
			}
			// anchorRow missing and btn missing: section is legitimately
			// collapsed right now -- correctly stays hidden, nothing to do.
		} );

		state.controlsObserver.observe( controls, { childList: true, subtree: true } );
	}

	function findControlFieldRow( fieldKey ) {
		var controlWrap = document.querySelector( '#elementor-controls .elementor-control-' + fieldKey );
		return controlWrap ? controlWrap.querySelector( '.elementor-control-field' ) : null;
	}

	// The canvas is a same-origin iframe with its own document -- clicks
	// inside it (e.g. the "+" placeholder that opens Elementor's own
	// layout-picker popup) never bubble to the top-level `document`
	// onDocumentClick listens on, so a card left open and then clicked
	// "outside" via the canvas was never closing at all (confirmed live).
	function previewDocument() {
		var iframe = document.getElementById( 'elementor-preview-iframe' );
		return ( iframe && iframe.contentDocument ) ? iframe.contentDocument : null;
	}

	function closeCard() {
		if ( state.card ) {
			state.card.remove();
			state.card = null;
		}
		document.removeEventListener( 'click', onDocumentClick, true );
		var previewDoc = previewDocument();
		if ( previewDoc ) {
			previewDoc.removeEventListener( 'click', onDocumentClick, true );
		}
	}

	function onDocumentClick( e ) {
		if ( ! state.card ) {
			return;
		}
		var btn = document.getElementById( 'bew-ai-editor-button' );
		if ( state.card.contains( e.target ) || ( btn && btn.contains( e.target ) ) ) {
			return;
		}
		closeCard();
	}

	function openCard( buildFn ) {
		closeCard();

		var btn  = document.getElementById( 'bew-ai-editor-button' );
		if ( ! btn ) {
			return;
		}
		var rect = btn.getBoundingClientRect();

		var card = document.createElement( 'div' );
		card.className = 'bew-ai-editor-card';
		// Anchor by the button's LEFT edge, not right: the button usually sits
		// inside the narrow side panel, so aligning the card's right edge to it
		// would push the (wider, 340px) card off the left edge of the screen.
		// Clamp so it also never overflows the right edge of the viewport.
		var CARD_WIDTH = 340;
		var left = Math.min( rect.left, window.innerWidth - CARD_WIDTH - 10 );
		left = Math.max( left, 10 );
		card.style.left = left + 'px';

		// Clamp vertically too, mirroring the clamp above. The card's CSS
		// caps it at max-height: 70vh, but on a short viewport `rect.bottom
		// + 6` alone can still push the card below the fold -- and because
		// it's position:fixed, neither page scroll nor the card's own
		// internal overflow-y:auto can recover that (the internal scroll
		// only reaches the card's own, already off-screen, bottom edge).
		var MAX_CARD_HEIGHT_RATIO = 0.7; // matches CSS max-height: 70vh
		var maxCardHeight = window.innerHeight * MAX_CARD_HEIGHT_RATIO;
		var top = Math.min( rect.bottom + 6, window.innerHeight - maxCardHeight - 10 );
		top = Math.max( top, 10 );
		card.style.top = top + 'px';

		buildFn( card );

		document.body.appendChild( card );
		state.card = card;

		window.setTimeout( function () {
			document.addEventListener( 'click', onDocumentClick, true );
			var previewDoc = previewDocument();
			if ( previewDoc ) {
				previewDoc.addEventListener( 'click', onDocumentClick, true );
			}
		}, 0 );
	}

	function renderConnectCard() {
		openCard( function ( card ) {
			card.innerHTML =
				'<h4>' + iconHtml() + escapeHtml( i18n.buttonLabel ) +
				( bewAiEditor.isBeta ? ' <span class="bew-beta-tag">' + escapeHtml( i18n.betaTag ) + '</span>' : '' ) +
				'</h4>' +
				'<p class="bew-ai-editor-card-subtitle">' + escapeHtml( i18n.generatorSubtitle ) + '</p>' +
				'<p class="description">' + escapeHtml( i18n.connectMessage ) + '</p>' +
				'<div class="bew-ai-editor-card-status"></div>' +
				'<div class="bew-ai-editor-actions">' +
				'<a href="' + escapeHtml( bewAiEditor.settingsUrl ) + '#ai-integration" target="_blank" rel="noopener noreferrer" class="bew-box-button bew-button-primary bew-ai-open-settings">' +
				escapeHtml( i18n.openSettings ) + '</a>' +
				'<button type="button" class="bew-box-button bew-button-secondary bew-ai-check-again">' + escapeHtml( i18n.checkAgain ) + '</button>' +
				'</div>';

			var checkBtn = card.querySelector( '.bew-ai-check-again' );
			var status   = card.querySelector( '.bew-ai-editor-card-status' );

			checkBtn.addEventListener( 'click', function () {
				checkBtn.disabled    = true;
				checkBtn.textContent = i18n.checking;

				ajaxRequest( 'bew_ai_check_connected', {}, function ( success, data ) {
					checkBtn.disabled    = false;
					checkBtn.textContent = i18n.checkAgain;

					if ( success && data && data.connected ) {
						bewAiEditor.hasConnectedProvider = true;
						renderGenerateCard();
						return;
					}

					setStatus( status, i18n.connectMessage );
				} );
			} );
		} );
	}

	// Snapshots the field-picker card's current selections into
	// state.lastGenerateForm -- called on every change so an accidental
	// outside click (see onButtonClick) never loses more than the last
	// keystroke, instead of resetting the whole form back to defaults.
	function captureGenerateFormState( card ) {
		var checkedKeys = Array.prototype.slice.call( card.querySelectorAll( '.bew-ai-editor-field-checkbox:checked' ) ).map( function ( cb ) {
			return cb.value;
		} );
		var providerEl    = card.querySelector( '.bew-ai-editor-provider' );
		var modelEl        = card.querySelector( '.bew-ai-editor-model-select' );
		var instructionEl = card.querySelector( '.bew-ai-editor-instruction' );

		state.lastGenerateForm = {
			checkedKeys: checkedKeys,
			providerId: providerEl ? providerEl.value : '',
			modelId: modelEl ? modelEl.value : '',
			instruction: instructionEl ? instructionEl.value : '',
		};
	}

	function providerById( providerId ) {
		return bewAiEditor.connectedProviders.filter( function ( p ) { return p.id === providerId; } )[ 0 ] || null;
	}

	function currentPostId() {
		return ( window.elementor && elementor.documents && elementor.documents.getCurrent ) ? elementor.documents.getCurrent().id : 0;
	}

	// Composite key so one post's shared history meta blob can't conflate two
	// different widgets that happen to use the same setting name -- see
	// class-bew-ai-history.php's docblock.
	function historyFieldKey( field ) {
		return state.model.get( 'id' ) + ':' + field.key;
	}

	// Swaps whichever card is currently open to Generation History for one
	// field. `returnTo` is called by BEWAIHistory's own Back button/close
	// flow to re-render whichever view (picker or preview) the user came
	// from -- this function never needs to know which one that was.
	function openHistoryForField( field, returnTo ) {
		if ( ! state.card || ! state.model ) {
			return;
		}

		var elementId = state.model.get( 'id' );
		var roleLabel = field.repeater ? repeaterLabel( field ).replace( / \(all items\)$/, '' ) : humanizeRole( field.role );

		BEWAIHistory.renderView( state.card, {
			ajaxUrl: bewAiEditor.ajaxUrl,
			nonce: bewAiEditor.nonce,
			ownerType: 'post',
			ownerId: currentPostId(),
			fieldKey: historyFieldKey( field ),
			roleLabel: roleLabel,
			onRestore: function ( entry, done ) {
				var container = elementor.getContainer( elementId );
				if ( ! container ) {
					done( false );
					return;
				}

				var settings = {};
				settings[ field.key ] = entry.value;
				$e.run( 'document/elements/settings', { container: container, settings: settings } );
				BEWAIFieldRefresh( settings );

				generatedThisSession[ elementId + ':' + field.key ] = true;

				// Same selectionToken guard as the Apply handler below --
				// covers switching away in the brief window before this fires.
				var token = state.selectionToken;
				window.setTimeout( function () {
					if ( token !== state.selectionToken ) {
						return;
					}
					placeButton( state.eligibleFields );
					watchForRemoval( state.eligibleFields );
				}, 0 );

				done( true );
			},
			onRestored: function () {
				state.lastGenerateForm = null;
				state.lastPreview      = null;
				closeCard();
			},
			onBack: returnTo,
		} );
	}

	function renderGenerateCard() {
		openCard( function ( card ) {
			if ( ! state.eligibleFields.length ) {
				card.innerHTML = '<p class="description">' + escapeHtml( i18n.noEligibleFields ) + '</p>';
				return;
			}

			// Our system only has two card states worth restoring on an
			// accidental close -- this one (in-progress selections) and the
			// preview card (see state.lastPreview) -- so reopening always
			// lands back where the user actually was, not a reset form.
			var savedForm = state.lastGenerateForm;

			var providers        = bewAiEditor.connectedProviders;
			var initialProviderId = savedForm ? savedForm.providerId : ( providers.filter( function ( p ) { return p.is_default; } )[ 0 ] || providers[ 0 ] || {} ).id;

			var providerOptions = providers.map( function ( p ) {
				var isSelected = p.id === initialProviderId;
				return '<option value="' + escapeHtml( p.id ) + '"' + ( isSelected ? ' selected' : '' ) + '>' + escapeHtml( BEWAIModelField.optionLabel( p ) ) + '</option>';
			} ).join( '' );

			var fieldsHtml = state.eligibleFields.map( function ( field ) {
				var label     = field.repeater ? repeaterLabel( field ) : humanizeRole( field.role );
				var isChecked = savedForm ? ( savedForm.checkedKeys.indexOf( field.key ) !== -1 ) : true;
				return '<label class="bew-ai-editor-field-row">' +
					'<input type="checkbox" class="bew-ai-editor-field-checkbox" value="' + escapeHtml( field.key ) + '"' + ( isChecked ? ' checked' : '' ) + ' /> ' +
					escapeHtml( label ) +
					BEWAIHistory.iconHtml() +
					'</label>';
			} ).join( '' );

			card.innerHTML =
				'<h4>' + iconHtml() + escapeHtml( i18n.buttonLabel ) +
				( bewAiEditor.isBeta ? ' <span class="bew-beta-tag">' + escapeHtml( i18n.betaTag ) + '</span>' : '' ) +
				'</h4>' +
				'<p class="bew-ai-editor-card-subtitle">' + escapeHtml( i18n.generatorSubtitle ) + '</p>' +
				'<div class="bew-ai-editor-provider-row">' +
					'<div class="bew-ai-editor-provider-field">' +
					'<label class="bew-ai-editor-instruction-label">' + escapeHtml( i18n.providerLabel ) + '</label>' +
					( providers.length > 1
						? '<select class="bew-ai-editor-provider">' + providerOptions + '</select>'
						: '<p class="description">' + escapeHtml( providers.length ? BEWAIModelField.optionLabel( providers[0] ) : '' ) + '</p>' +
							'<input type="hidden" class="bew-ai-editor-provider" value="' + escapeHtml( ( providers[0] || {} ).id || '' ) + '" />' ) +
					'</div>' +
					'<div class="bew-ai-editor-model-field"></div>' +
				'</div>' +
				'<div class="bew-ai-editor-fields">' + fieldsHtml + '</div>' +
				BEWAIPresets.instructionBlockHtml( {
					label: i18n.instructionLabel,
					note: i18n.instructionNote,
					placeholder: i18n.instructionPlaceholder,
					value: savedForm ? savedForm.instruction : '',
					textareaClass: 'bew-ai-editor-instruction',
					key: 'bew-ai-editor-instruction',
				} ) +
				'<div class="bew-ai-editor-card-status"></div>' +
				'<div class="bew-ai-editor-actions">' +
				'<button type="button" class="bew-box-button bew-button-primary bew-ai-generate">' + iconHtml( true ) + escapeHtml( i18n.generate ) + '</button>' +
				'<button type="button" class="bew-box-button bew-button-secondary bew-ai-cancel">' + escapeHtml( i18n.cancel ) + '</button>' +
				'</div>' +
				( bewAiEditor.showUpgradeNote
					? '<p class="bew-ai-editor-upgrade-note">' + escapeHtml( i18n.upgradeNoteText ) + ' <a href="' + escapeHtml( bewAiEditor.pricingUrl ) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml( i18n.upgradeLinkLabel ) + '</a></p>'
					: '' );

			BEWAIModelField.refresh( card.querySelector( '.bew-ai-editor-model-field' ), i18n.modelLabel, ( providerById( initialProviderId ) || {} ).models, savedForm ? savedForm.modelId : ( providerById( initialProviderId ) || {} ).model );

			var providerSelectEl = card.querySelector( '.bew-ai-editor-provider' );
			if ( providerSelectEl && 'SELECT' === providerSelectEl.tagName ) {
				providerSelectEl.addEventListener( 'change', function () {
					var p = providerById( providerSelectEl.value );
					BEWAIModelField.refresh( card.querySelector( '.bew-ai-editor-model-field' ), i18n.modelLabel, p && p.models, p && p.model );
					captureGenerateFormState( card );
				} );
			}
			card.addEventListener( 'change', function ( e ) {
				if ( e.target.classList.contains( 'bew-ai-editor-model-select' ) ) {
					captureGenerateFormState( card );
				}
			} );

			card.querySelectorAll( '.bew-ai-editor-field-checkbox' ).forEach( function ( cb ) {
				cb.addEventListener( 'change', function () { captureGenerateFormState( card ); } );
			} );
			card.querySelector( '.bew-ai-editor-instruction' ).addEventListener( 'input', function () { captureGenerateFormState( card ); } );
			captureGenerateFormState( card ); // Initial snapshot, in case the user submits without touching anything.

			// Icons are rendered inside their row's own <label> (simplest way
			// to keep them aligned with the checkbox+text on one line) --
			// without stopping the click here, the label's native behavior
			// would also toggle that row's checkbox.
			card.querySelectorAll( '.bew-ai-history-icon' ).forEach( function ( btn, index ) {
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					e.stopPropagation();
					openHistoryForField( state.eligibleFields[ index ], renderGenerateCard );
				} );
			} );

			BEWAIPresets.init( card, { onBack: renderGenerateCard } );

			// An explicit Cancel here (as opposed to an outside click, treated
			// as accidental -- see onButtonClick) means "don't use this",
			// so clear it rather than resurrecting it on the next reopen.
			card.querySelector( '.bew-ai-cancel' ).addEventListener( 'click', function () {
				state.lastGenerateForm = null;
				closeCard();
			} );
			card.querySelector( '.bew-ai-generate' ).addEventListener( 'click', function () {
				triggerGenerate( card );
			} );
		} );
	}

	function triggerGenerate( card ) {
		var checkedKeys = Array.prototype.slice.call( card.querySelectorAll( '.bew-ai-editor-field-checkbox:checked' ) ).map( function ( cb ) {
			return cb.value;
		} );

		var status = card.querySelector( '.bew-ai-editor-card-status' );

		if ( ! checkedKeys.length ) {
			setStatus( status, i18n.noFieldsSelected );
			return;
		}

		var checkedFields = state.eligibleFields.filter( function ( f ) {
			return checkedKeys.indexOf( f.key ) !== -1;
		} );

		var providerEl   = card.querySelector( '.bew-ai-editor-provider' );
		var modelEl      = card.querySelector( '.bew-ai-editor-model-select' );
		var instruction  = card.querySelector( '.bew-ai-editor-instruction' ).value.trim();
		var generateBtn  = card.querySelector( '.bew-ai-generate' );

		var valuesResult = currentValuesFor( checkedFields );
		state.lastFullRepeaterRows = valuesResult.fullRepeaterRows;

		// If there's a not-yet-applied preview around (Regenerate, or
		// reopening after an accidental outside click), tell the model what
		// it already suggested -- current_values hasn't changed since then,
		// so an identical prompt tends to come back as a near-identical
		// rewrite instead of a real alternative.
		var previousAttempt = {};
		if ( state.lastPreview && state.lastPreview.fields ) {
			checkedKeys.forEach( function ( key ) {
				if ( Object.prototype.hasOwnProperty.call( state.lastPreview.fields, key ) ) {
					previousAttempt[ key ] = state.lastPreview.fields[ key ];
				}
			} );
		}

		var request = {
			widget_type: state.widgetType,
			fields: checkedKeys,
			current_values: valuesResult.values,
			already_generated: alreadyGeneratedFor( checkedKeys ),
			previous_attempt: previousAttempt,
			instruction: instruction,
			provider_id: providerEl ? providerEl.value : '',
			model: modelEl ? modelEl.value : '',
		};

		generateBtn.disabled    = true;
		generateBtn.textContent = i18n.generating;
		setStatus( status, '' );

		ajaxRequest( 'bew_ai_generate_text', request, function ( success, data ) {
			generateBtn.disabled    = false;
			generateBtn.innerHTML   = iconHtml( true ) + escapeHtml( i18n.generate );

			if ( ! success || ! data || ! data.fields ) {
				setStatus( status, errorTextFrom( data, i18n.errorMessage ) );
				return;
			}

			renderPreviewCard( data.fields, data.provider_label, data.model_label );
		} );
	}

	function renderPreviewCard( fields, providerLabel, modelLabel ) {
		// Kept until Applied (or explicitly cancelled/discarded) so the button
		// can reopen this same preview instead of a fresh generate card if the
		// card gets closed some other way (e.g. an accidental outside click).
		state.lastPreview = { fields: fields, providerLabel: providerLabel, modelLabel: modelLabel };

		openCard( function ( card ) {
			var widgetMap = bewAiEditor.fieldMap[ state.widgetType ] || {};

			var rows = Object.keys( fields ).map( function ( key ) {
				var value = fields[ key ];

				if ( Array.isArray( value ) ) {
					var subMap = ( widgetMap[ key ] && 'object' === typeof widgetMap[ key ] ) ? widgetMap[ key ] : {};
					var rowsHtml = value.map( function ( rowVal, rowIndex ) {
						var subInputs = Object.keys( subMap ).map( function ( subKey ) {
							return '<label class="bew-ai-editor-repeater-sublabel">' + escapeHtml( humanizeRole( subMap[ subKey ] ) ) + '</label>' +
								'<input type="text" class="bew-ai-editor-repeater-input" data-field-key="' + escapeHtml( key ) + '" data-sub-key="' + escapeHtml( subKey ) + '" data-row-index="' + rowIndex + '" value="' + escapeHtml( rowVal[ subKey ] || '' ) + '" />';
						} ).join( '' );
						return '<div class="bew-ai-editor-repeater-row"><div class="bew-ai-editor-repeater-row-title">' +
							escapeHtml( i18n.itemLabel ) + ' ' + ( rowIndex + 1 ) + '</div>' + subInputs + '</div>';
					} ).join( '' );

					return '<div class="bew-ai-editor-preview-row bew-ai-editor-preview-repeater" data-repeater-key="' + escapeHtml( key ) + '" data-history-key="' + escapeHtml( key ) + '">' +
						'<div class="bew-ai-editor-preview-row-header"><label>' + escapeHtml( humanizeRole( key ) ) + '</label>' + BEWAIHistory.iconHtml() + '</div>' +
						rowsHtml +
						'</div>';
				}

				return '<div class="bew-ai-editor-preview-row" data-history-key="' + escapeHtml( key ) + '">' +
					'<div class="bew-ai-editor-preview-row-header"><label>' + escapeHtml( humanizeRole( widgetMap[ key ] ) ) + '</label>' + BEWAIHistory.iconHtml() + '</div>' +
					'<textarea data-field-key="' + escapeHtml( key ) + '">' + escapeHtml( value ) + '</textarea>' +
					'</div>';
			} ).join( '' );

			card.innerHTML =
				'<h4>' + iconHtml() + escapeHtml( i18n.buttonLabel ) +
				( bewAiEditor.isBeta ? ' <span class="bew-beta-tag">' + escapeHtml( i18n.betaTag ) + '</span>' : '' ) +
				'</h4>' +
				'<p class="bew-ai-editor-card-subtitle">' + escapeHtml( i18n.generatorSubtitle ) + '</p>' +
				( providerLabel ? '<p class="description">' + escapeHtml( i18n.usingLabel ) + ': ' + escapeHtml( providerLabel ) + ( modelLabel ? ' · ' + escapeHtml( modelLabel ) : '' ) + '</p>' : '' ) +
				'<div class="bew-ai-editor-preview">' + rows + '</div>' +
				'<div class="bew-ai-editor-actions">' +
				'<button type="button" class="bew-box-button bew-button-primary bew-ai-apply">' + escapeHtml( i18n.apply ) + '</button>' +
				'<button type="button" class="bew-box-button bew-button-secondary bew-ai-regenerate">' + escapeHtml( i18n.regenerate ) + '</button>' +
				'<button type="button" class="bew-box-button bew-button-secondary bew-ai-cancel">' + escapeHtml( i18n.cancel ) + '</button>' +
				'</div>';

			card.querySelectorAll( '.bew-ai-editor-preview-row' ).forEach( function ( row ) {
				var icon = row.querySelector( '.bew-ai-history-icon' );
				if ( ! icon ) {
					return;
				}
				var field = state.eligibleFields.filter( function ( f ) { return f.key === row.dataset.historyKey; } )[ 0 ];
				if ( ! field ) {
					return;
				}
				icon.addEventListener( 'click', function () {
					openHistoryForField( field, function () { renderPreviewCard( fields, providerLabel, modelLabel ); } );
				} );
			} );

			// Unlike an outside click (treated as accidental, see
			// onButtonClick), this Cancel is an explicit "don't use this" --
			// clear both so reopening starts fresh instead of resurrecting a
			// rejected result.
			card.querySelector( '.bew-ai-cancel' ).addEventListener( 'click', function () {
				state.lastGenerateForm = null;
				state.lastPreview      = null;
				closeCard();
			} );

			// renderGenerateCard() restores the fields/provider/instruction that
			// were actually submitted for this result from state.lastGenerateForm
			// (still set from the request that produced this preview).
			card.querySelector( '.bew-ai-regenerate' ).addEventListener( 'click', function () {
				renderGenerateCard();
			} );

			card.querySelector( '.bew-ai-apply' ).addEventListener( 'click', function () {
				var settings  = {};
				var elementId = state.model.get( 'id' );

				card.querySelectorAll( '.bew-ai-editor-preview-row:not(.bew-ai-editor-preview-repeater) textarea' ).forEach( function ( ta ) {
					var key = ta.dataset.fieldKey;
					settings[ key ] = ta.value;
					generatedThisSession[ elementId + ':' + key ] = true;
				} );

				// Merge edited sub-fields back into a COPY of the original row
				// objects (not a fresh object) so per-row bookkeeping Elementor
				// relies on (_id, any per-row style overrides) survives.
				card.querySelectorAll( '.bew-ai-editor-preview-repeater' ).forEach( function ( group ) {
					var key = group.dataset.repeaterKey;
					var originalRows = state.lastFullRepeaterRows[ key ] || [];
					var byRow = {};

					group.querySelectorAll( '.bew-ai-editor-repeater-input' ).forEach( function ( input ) {
						var rowIndex = Number( input.dataset.rowIndex );
						byRow[ rowIndex ] = byRow[ rowIndex ] || {};
						byRow[ rowIndex ][ input.dataset.subKey ] = input.value;
					} );

					settings[ key ] = Object.keys( byRow ).map( function ( idx ) {
						var copy = Object.assign( {}, originalRows[ idx ] || {} );
						Object.keys( byRow[ idx ] ).forEach( function ( subKey ) {
							copy[ subKey ] = byRow[ idx ][ subKey ];
						} );
						return copy;
					} );

					generatedThisSession[ elementId + ':' + key ] = true;
				} );

				var container = elementor.getContainer( elementId );
				if ( container ) {
					$e.run( 'document/elements/settings', { container: container, settings: settings } );
					BEWAIFieldRefresh( settings );
				}

				// One history entry per applied field, not per Generate -- see
				// class-bew-ai-history.php's docblock for why Apply (not
				// preview/regenerate) is the log point.
				var appliedInstruction = state.lastGenerateForm ? state.lastGenerateForm.instruction : '';
				Object.keys( settings ).forEach( function ( key ) {
					BEWAIHistory.logEntry( {
						ajaxUrl: bewAiEditor.ajaxUrl,
						nonce: bewAiEditor.nonce,
						ownerType: 'post',
						ownerId: currentPostId(),
						fieldKey: elementId + ':' + key,
					}, {
						value: settings[ key ],
						providerLabel: providerLabel,
						modelLabel: modelLabel,
						instruction: appliedInstruction,
					} );
				} );

				// BEWAIFieldRefresh() above (not a forced whole-page
				// elementor.getPanelView().getCurrentPageView().render(), which
				// was confirmed live to break the Content/Style/Advanced tabs
				// for the rest of the session -- see BEWAIFieldRefresh()'s own
				// docblock) already handled the "field looks stale" concern.
				// Section collapse/expand still wipes our button/observer the
				// same way, so keep re-placing both -- same selectionToken
				// guard as onPanelOpenWidget.
				var applyToken = state.selectionToken;
				window.setTimeout( function () {
					if ( applyToken !== state.selectionToken ) {
						return;
					}
					placeButton( state.eligibleFields );
					watchForRemoval( state.eligibleFields );
				}, 0 );

				state.lastGenerateForm = null;
				state.lastPreview      = null;
				closeCard();
			} );
		} );
	}

	function onButtonClick() {
		if ( state.card ) {
			closeCard();
			return;
		}
		// A not-yet-applied preview is still around (e.g. the card was closed
		// by an accidental outside click) -- reopen that instead of starting
		// over, so the user can still decide (Apply/Regenerate/Cancel) without
		// having to spend another generation to see it again.
		if ( state.lastPreview ) {
			renderPreviewCard( state.lastPreview.fields, state.lastPreview.providerLabel, state.lastPreview.modelLabel );
			return;
		}
		if ( bewAiEditor.hasConnectedProvider ) {
			renderGenerateCard();
		} else {
			renderConnectCard();
		}
	}

	function onPanelOpenWidget( manager, model ) {
		var widgetType = model.get( 'widgetType' );
		var fields      = eligibleFieldsFor( widgetType );

		closeCard();

		state.model             = model;
		state.widgetType        = widgetType;
		state.eligibleFields    = fields;
		state.lastGenerateForm  = null; // A new widget selection invalidates in-progress/pending state from the last one.
		state.lastPreview       = null;

		// A microtask delay lets Elementor finish rendering #elementor-controls
		// for the new widget before we look inside it. Guarded by
		// selectionToken in case the user has already moved on by the time
		// this fires.
		var token = ++state.selectionToken;
		window.setTimeout( function () {
			if ( token !== state.selectionToken ) {
				return;
			}
			placeButton( fields );
			watchForRemoval( fields );
		}, 0 );
	}

	// Elementor never re-fires panel/open_editor/widget when selection moves
	// UP from this widget to its parent container/section/column -- only
	// Bulk Generator (a separate script) listens for those hooks, so without
	// this, our own button/observer/card from the widget selection is left
	// behind stacked next to Bulk's, instead of being replaced by it.
	function onOtherElementSelected() {
		state.selectionToken++;
		var old = document.getElementById( 'bew-ai-editor-button' );
		if ( old ) {
			old.remove();
		}
		if ( state.controlsObserver ) {
			state.controlsObserver.disconnect();
			state.controlsObserver = null;
		}
		closeCard();
	}

	elementor.hooks.addAction( 'panel/open_editor/widget', onPanelOpenWidget );
	elementor.hooks.addAction( 'panel/open_editor/container', onOtherElementSelected );
	elementor.hooks.addAction( 'panel/open_editor/section', onOtherElementSelected );
	elementor.hooks.addAction( 'panel/open_editor/column', onOtherElementSelected );

	} // end init()
}( jQuery ) );
