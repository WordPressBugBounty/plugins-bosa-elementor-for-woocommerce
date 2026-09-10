/* global BEWTemplateLibraryModal, Marionette, imagesLoaded */
/**
 * Template grid — masonry layout with one BosaTemplateCardView per item.
 */
var BosaTemplatesView = Marionette.CollectionView.extend({

	className:   'bosa-tk-grid',
	childView:   BosaTemplateCardView,
	childViewContainer: '.bosa-tk-grid-items',

	_columnGutter: 20,
	_minThumbWidth: 253,
	_lastColumns: 0,
	_breakpoints: [
		{ minWidth: 880, cols: 4 },
		{ minWidth: 640, cols: 3 },
		{ minWidth: 400, cols: 2 },
		{ minWidth: 0,   cols: 1 },
	],

	template: function () {
		return '<div class="bosa-tk-grid-sizer" aria-hidden="true"></div><div class="bosa-tk-grid-items"></div>';
	},

	childEvents: {
		'preview:open': 'onChildPreviewOpen',
		'kit:open':     'onChildKitOpen',
	},

	events: {
		'click .bosa-tk-card__thumb':    'onThumbClick',
		'click .bosa-tk-card__preview':  'onThumbClick',
	},

	initialize: function () {
		this.listenTo( this.collection, 'bosa:fetched', this.onFetched );
		this.listenTo( this.collection, 'bosa:error',   this.onError );
		this.listenTo( this, 'childview:render', this.scheduleLayout );

		this._onResize = this.onResize.bind( this );
		jQuery( window ).on( 'resize.bosaTkGrid', this._onResize );

		if ( typeof ResizeObserver !== 'undefined' ) {
			var self = this;
			this._resizeObserver = new ResizeObserver( function () {
				self.onResize();
			} );
		}
	},

	onChildPreviewOpen: function ( childView ) {
		this.trigger( 'preview:open', childView.model );
	},

	onChildKitOpen: function ( childView ) {
		this.trigger( 'kit:open', childView.model );
	},

	onThumbClick: function ( e ) {
		if ( e.target.closest && e.target.closest( '.bosa-tk-card__insert' ) ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		var $card = jQuery( e.currentTarget ).closest( '.bosa-tk-card' );
		var model = this._findModelForCard( $card );

		if ( ! model ) {
			return;
		}

		if ( model.get( 'type' ) === 'kit' ) {
			this.trigger( 'kit:open', model );
			return;
		}

		this.trigger( 'preview:open', model );
	},

	_findModelForCard: function ( $card ) {
		var found = null;

		if ( ! $card.length ) {
			return null;
		}

		this.children.each( function ( view ) {
			if ( view.$el[ 0 ] === $card[ 0 ] ) {
				found = view.model;
			}
		} );

		return found;
	},

	onDestroy: function () {
		jQuery( window ).off( 'resize.bosaTkGrid', this._onResize );
		if ( this._resizeObserver ) {
			this._resizeObserver.disconnect();
		}
		if ( this._$scrollParent ) {
			this._$scrollParent.off( 'scroll.bosaTkLoadMore', this._onScroll );
		}
		clearTimeout( this._scrollTimer );
		this.destroyMasonry();
	},

	getAvailableWidth: function () {
		return this.$el.width() || 0;
	},

	getColumnsPerRow: function () {
		var available = this.getAvailableWidth();
		var i;

		for ( i = 0; i < this._breakpoints.length; i++ ) {
			if ( available >= this._breakpoints[ i ].minWidth ) {
				return this._breakpoints[ i ].cols;
			}
		}

		return 1;
	},

	updateColumnWidth: function () {
		var gutter    = this._columnGutter;
		var available = this.getAvailableWidth();

		if ( available <= 0 ) {
			return 208;
		}

		var cols     = this.getColumnsPerRow();
		var colWidth = Math.max( this._minThumbWidth, Math.floor( ( available - gutter * ( cols - 1 ) ) / cols ) );

		this.$( '.bosa-tk-grid-sizer, .bosa-tk-card' ).css( 'width', colWidth + 'px' );

		return colWidth;
	},

	destroyMasonry: function () {
		if ( this.$el.data( 'masonry' ) ) {
			this.$el.masonry( 'destroy' );
		}
	},

	/**
	 * @param {Function} [onComplete] Also used as a childview:render handler;
	 *        non-function args are ignored.
	 */
	scheduleLayout: function ( onComplete ) {
		var self = this;
		var cb   = 'function' === typeof onComplete ? onComplete : undefined;
		clearTimeout( this._layoutTimer );
		this._layoutTimer = setTimeout( function () {
			self.layoutMasonry( cb );
		}, 50 );
	},

	onResize: function () {
		var self = this;
		clearTimeout( this._resizeTimer );
		this._resizeTimer = setTimeout( function () {
			if ( ! self.collection.length || self.$el.hasClass( 'bosa-tk-grid--loading' ) ) {
				return;
			}

			// Skip while hidden — a 0-width container would collapse the grid to 1 column.
			if ( ! self.$el.is( ':visible' ) ) {
				return;
			}

			var cols = self.getColumnsPerRow();

			if ( cols !== self._lastColumns ) {
				self.destroyMasonry();
				self.layoutMasonry();
				return;
			}

			self.updateColumnWidth();
			if ( self.$el.data( 'masonry' ) ) {
				self.$el.masonry( 'layout' );
			} else {
				self.layoutMasonry();
			}
		}, 150 );
	},

	/**
	 * @param {Function} [onComplete] Fires after masonry positions the cards
	 *        (async, via imagesLoaded) — not right after this call returns.
	 */
	layoutMasonry: function ( onComplete ) {
		var self  = this;
		var $grid = this.$el;

		if ( ! this.collection.length || $grid.hasClass( 'bosa-tk-grid--loading' ) ) {
			if ( onComplete ) {
				onComplete();
			}
			return;
		}

		var applyLayout = function () {
			self.updateColumnWidth();
			self._lastColumns = self.getColumnsPerRow();

			if ( $grid.data( 'masonry' ) ) {
				// reloadItems() picks up cards appended since masonry was initialized.
				$grid.masonry( 'reloadItems' ).masonry( 'layout' );
			} else {
				$grid.masonry( {
					itemSelector: '.bosa-tk-card',
					columnWidth:  '.bosa-tk-grid-sizer',
					gutter:       self._columnGutter,
					percentPosition: false,
				} );
			}

			if ( onComplete ) {
				onComplete();
			}
		};

		$grid.find( 'img' ).off( 'load.bosaTkMasonry' ).on( 'load.bosaTkMasonry', function () {
			if ( $grid.data( 'masonry' ) ) {
				$grid.masonry( 'layout' );
			}
		} );

		if ( typeof imagesLoaded === 'function' ) {
			imagesLoaded( this.el, applyLayout );
		} else {
			applyLayout();
		}
	},

	onRender: function () {
		if ( this._resizeObserver && this.el ) {
			this._resizeObserver.observe( this.el );
		}
		this._bindScrollLoading();
		this.layoutMasonry();
	},

	/**
	 * Wires scroll-near-bottom detection to collection.loadMore(), when
	 * present (no-op for Kit Details' plain Backbone.Collection).
	 */
	_bindScrollLoading: function () {
		if ( this._scrollBound || 'function' !== typeof this.collection.loadMore ) {
			return;
		}

		var $parent = this._findScrollParent();
		if ( ! $parent || ! $parent.length ) {
			return;
		}

		this._scrollBound   = true;
		this._$scrollParent = $parent;
		this._onScroll       = this._onScroll.bind( this );
		$parent.on( 'scroll.bosaTkLoadMore', this._onScroll );
	},

	/**
	 * Finds the nearest scrollable ancestor (not hardcoded — this view
	 * renders inside different containers depending on context).
	 *
	 * @return {jQuery|null}
	 */
	_findScrollParent: function () {
		var el = this.el;

		while ( el && el.parentElement ) {
			el = el.parentElement;
			var overflowY = window.getComputedStyle( el ).overflowY;
			if ( 'auto' === overflowY || 'scroll' === overflowY ) {
				return jQuery( el );
			}
		}

		return null;
	},

	_onScroll: function () {
		var self = this;
		clearTimeout( this._scrollTimer );
		this._scrollTimer = setTimeout( function () {
			self._maybeLoadMore();
		}, 150 );
	},

	_maybeLoadMore: function () {
		// _isFetching guard avoids an orphaned spinner if scrolled while a fetch is already in flight.
		if ( ! this._$scrollParent || ! this._$scrollParent.length || ! this.collection.hasMore || this.collection._isFetching ) {
			return;
		}

		var el        = this._$scrollParent[ 0 ];
		var threshold = 400; // px from the bottom — start loading before the user hits the true end.

		if ( el.scrollTop + el.clientHeight >= el.scrollHeight - threshold ) {
			this._showLoadMore();
			this.collection.loadMore();
		}
	},

	/**
	 * Appended to _$scrollParent because masonry collapses .bosa-tk-grid-items height,
	 * causing siblings inside .bosa-tk-grid to render incorrectly.
	 */
	_ensureLoadMoreEl: function () {
		if ( ! this._$scrollParent || this._$scrollParent.find( '.bosa-tk-load-more' ).length ) {
			return;
		}
		this._$scrollParent.append(
			'<div class="bosa-tk-load-more">' +
				'<span class="bosa-tk-load-more__icon bosa-tk-refresh--spinning" aria-hidden="true">&#8635;</span>' +
			'</div>'
		);
	},

	// Toggled via a class, not the "hidden" attribute (loses to CSS specificity).
	_showLoadMore: function () {
		this._ensureLoadMoreEl();
		if ( this._$scrollParent ) {
			this._$scrollParent.find( '.bosa-tk-load-more' ).addClass( 'bosa-tk-load-more--visible' );
		}
	},

	_hideLoadMore: function () {
		if ( this._$scrollParent ) {
			this._$scrollParent.find( '.bosa-tk-load-more' ).removeClass( 'bosa-tk-load-more--visible' );
		}
	},

	_hideEndNotice: function () {
		if ( this._$scrollParent ) {
			this._$scrollParent.find( '.bosa-tk-end-notice' ).removeClass( 'bosa-tk-end-notice--visible' );
		}
	},

	_ensureEndNoticeEl: function () {
		if ( ! this._$scrollParent || this._$scrollParent.find( '.bosa-tk-end-notice' ).length ) {
			return;
		}
		this._$scrollParent.append(
			'<div class="bosa-tk-end-notice">' +
				'<img class="bosa-tk-end-notice__icon" src="' + BEWTemplateLibraryModal.endOfListIconUrl + '" alt="" aria-hidden="true">' +
				'<p class="bosa-tk-end-notice__text">' + BEWTemplateLibraryModal.i18n.end_of_list + '</p>' +
			'</div>'
		);
	},

	// Shown when the list has genuinely run out of items, not for the empty-results state.
	_updateEndNotice: function () {
		if ( ! this._$scrollParent ) {
			return;
		}
		this._ensureEndNoticeEl();
		var show = !! this.collection.length && ! this.collection.hasMore;
		this._$scrollParent.find( '.bosa-tk-end-notice' ).toggleClass( 'bosa-tk-end-notice--visible', show );
	},

	hideLoading: function () {
		this.$el.removeClass( 'bosa-tk-grid--loading' );
		this.$( '.bosa-tk-loading' ).remove();
	},

	/**
	 * @param {Backbone.Collection} collection
	 * @param {Object} [options] {append: true} for an incremental "load more"
	 *        batch — appends to the current grid instead of replacing it.
	 */
	onFetched: function ( collection, options ) {
		options = options || {};

		if ( options.append ) {
			this._hideLoadMore();
			this._updateEndNotice();

			// Only the new batch needs hide-until-positioned treatment.
			var $newCards = this.$( '.bosa-tk-card' ).slice( this._priorCount || 0 );
			$newCards.css( 'visibility', 'hidden' );
			this._priorCount = this.collection.length;

			this.scheduleLayout( function () {
				$newCards.css( 'visibility', '' );
			} );
			return;
		}

		this.hideLoading();
		this.destroyMasonry();

		// _$scrollParent's elements live outside this.$el and survive the
		// render() below, so reset them explicitly on every fresh load.
		this._hideLoadMore();
		this._updateEndNotice();

		if ( ! this.collection.length ) {
			this._priorCount = 0;
			this.$el.html(
				'<p class="bosa-tk-no-results">' + BEWTemplateLibraryModal.i18n.no_results + '</p>'
			);
			return;
		}

		if ( ! this.$( '.bosa-tk-grid-items' ).length ) {
			this.$el.html(
				'<div class="bosa-tk-grid-sizer" aria-hidden="true"></div>' +
				'<div class="bosa-tk-grid-items"></div>'
			);
		}

		// Hide until masonry positions the cards, to avoid a single-column flash.
		var self = this;
		this.$el.css( 'visibility', 'hidden' );

		this.render();
		this._ensureLoadMoreEl();
		this._priorCount = this.collection.length;
		this.scheduleLayout( function () {
			self.$el.css( 'visibility', '' );
		} );
	},

	onError: function () {
		this.hideLoading();
		this.destroyMasonry();
		this.$el.html(
			'<p class="bosa-tk-no-results">' + BEWTemplateLibraryModal.i18n.no_results + '</p>'
		);
	},

	showLoading: function () {
		this.destroyMasonry();
		this.$el.addClass( 'bosa-tk-grid--loading' );
		this.$el.html(
			'<div class="bosa-tk-loading" role="status" aria-live="polite" aria-busy="true">' +
				'<span class="bosa-tk-loading__icon bosa-tk-refresh--spinning" aria-hidden="true">&#8635;</span>' +
			'</div>'
		);

		// These live in _$scrollParent, outside this.$el, so html() above doesn't clear them.
		this._hideLoadMore();
		this._hideEndNotice();
	},

} );
