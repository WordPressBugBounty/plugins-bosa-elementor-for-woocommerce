/* global BEWTemplateLibraryModal, elementorModules, Marionette, Backbone */
/**
 * Modal body — header and grid regions.
 */
var BosaModalBodyView = Marionette.LayoutView.extend({

	className: 'bosa-modal-body',

	regions: {
		headerRegion:        '#bosa-modal-header',
		contentRegion:       '#bosa-modal-content',
		kitDetailsRegion:    '#bosa-kit-details-host',
		previewHeaderRegion: '#bosa-preview-header',
		previewBodyRegion:   '#bosa-preview-body',
	},

	template: function () {
		return [
			'<div class="bosa-tk-library-shell">',
				'<div id="bosa-modal-header"></div>',
				'<div id="bosa-modal-content"></div>',
			'</div>',
			'<div class="bosa-tk-kit-details-shell">',
				'<div id="bosa-kit-details-host"></div>',
			'</div>',
			'<div class="bosa-tk-preview-shell">',
				'<div id="bosa-preview-header"></div>',
				'<div id="bosa-preview-body"></div>',
			'</div>',
		].join( '' );
	},

	onRender: function () {
		var self = this;

		this._kitItemsCache      = {};
		this._templateLookup     = {};
		this._kitDetailsModel    = null;
		this._previewFromKit     = false;
		this._kitItemsCollection = new Backbone.Collection();

		this.listenTo( this.options.collection, 'bosa:fetched', function () {
			var items = self.options.collection._sourceData || [];
			self.options.collection._mergeCatalog( items );
			self._indexTemplateItems( items );
		} );

		// prefetchPageSections() is deferred to after the tab's own fetch — see showModal().

		this.headerView = new BosaHeaderView();
		this.contentView = new BosaModalContentView( {
			collection:         this.options.collection,
			initialTab:         this.options.initialTab,
			onSearchChange:     this.options.onSearchChange,
			onCategoryChange:   this.options.onCategoryChange,
		} );
		this.kitDetailsView = new BosaKitDetailsView( {
			itemsCollection: this._kitItemsCollection,
		} );

		this.previewHeaderView = new BosaPreviewHeaderView( { model: new Backbone.Model() } );
		this.previewBodyView   = new BosaTemplatePreviewView( { model: new Backbone.Model() } );

		this.headerRegion.show( this.headerView );
		this.contentRegion.show( this.contentView );
		this.kitDetailsRegion.show( this.kitDetailsView );
		this.previewHeaderRegion.show( this.previewHeaderView );
		this.previewBodyRegion.show( this.previewBodyView );

		this.templatesView = this.contentView.templatesView;

		this.listenTo( this.headerView, 'tab:change', this.options.onTabChange );
		this.listenTo( this.headerView, 'refresh',      this.options.onRefresh );
		this.listenTo( this.headerView, 'close',        this.options.onClose );

		this.listenTo( this.templatesView, 'preview:open', function ( model ) {
			self.enterPreviewMode( model );
		} );

		this.listenTo( this.templatesView, 'kit:open', function ( model ) {
			self.enterKitDetailsMode( model );
		} );

		this.listenTo( this.headerView, 'kit-details:back', function () {
			self.exitKitDetailsMode();
		} );

		this.listenTo( this.kitDetailsView, 'preview:open', function ( model ) {
			self.enterPreviewMode( model, { fromKitDetails: true } );
		} );

		this.listenTo( this.previewHeaderView, 'preview:back',  function () { self.exitPreviewMode(); } );
		this.listenTo( this.previewHeaderView, 'preview:close', this.options.onClose );
		this.listenTo( this.previewHeaderView, 'preview:insert', function () {
			self.onPreviewInsert();
		} );
	},

	clearKitRefreshCaches: function () {
		this._kitItemsCache  = {};
		this._templateLookup = {};
	},

	reindexCatalogLookup: function () {
		var catalog = this.options.collection._catalog || { page: {}, section: {} };
		var self    = this;

		Object.keys( catalog.page ).forEach( function ( id ) {
			self._templateLookup[ id ] = catalog.page[ id ];
		} );

		Object.keys( catalog.section ).forEach( function ( id ) {
			self._templateLookup[ id ] = catalog.section[ id ];
		} );
	},

	_indexTemplateItems: function ( items ) {
		var self = this;

		items.forEach( function ( item ) {
			if ( item && item.id && 'kit' !== item.type ) {
				self._templateLookup[ item.id ] = item;
			}
		} );
	},

	_normalizeIdList: function ( value ) {
		if ( Array.isArray( value ) ) {
			return value;
		}

		if ( value && typeof value === 'object' ) {
			return Object.keys( value ).map( function ( key ) {
				return value[ key ];
			} );
		}

		return [];
	},

	_extractIdFromEntry: function ( entry ) {
		if ( entry && typeof entry === 'object' ) {
			return parseInt( entry.id, 10 );
		}

		return parseInt( entry, 10 );
	},

	_normalizeIds: function ( value ) {
		var self = this;

		return this._normalizeIdList( value ).map( function ( entry ) {
			return self._extractIdFromEntry( entry );
		} ).filter( Boolean );
	},

	_getKitPageIds: function ( model ) {
		var pages = this._normalizeIds( model.get( 'included_pages' ) );

		if ( ! pages.length ) {
			pages = this._normalizeIds( model.get( 'pages' ) );
		}

		return pages;
	},

	_getKitSectionIds: function ( model ) {
		var sections = this._normalizeIds( model.get( 'included_sections' ) );

		if ( ! sections.length ) {
			sections = this._normalizeIds( model.get( 'sections' ) );
		}

		return sections;
	},

	_getKitItemIds: function ( model ) {
		return this._getKitPageIds( model ).concat( this._getKitSectionIds( model ) );
	},

	_normalizeItemList: function ( value ) {
		var list = this._normalizeIdList( value );

		return list.filter( function ( entry ) {
			return entry && typeof entry === 'object' && entry.id;
		} );
	},

	_normalizeKitResponse: function ( response, model ) {
		var pages    = [];
		var sections = [];

		if ( response && false !== response.success ) {
			if ( Array.isArray( response.pages ) ) {
				pages = response.pages.slice();
			}
			if ( Array.isArray( response.sections ) ) {
				sections = response.sections.slice();
			}

			if ( response.data && typeof response.data === 'object' && ! Array.isArray( response.data ) ) {
				if ( ! pages.length && Array.isArray( response.data.pages ) ) {
					pages = response.data.pages.slice();
				}
				if ( ! sections.length && Array.isArray( response.data.sections ) ) {
					sections = response.data.sections.slice();
				}
			}

			if ( ! pages.length && ! sections.length && Array.isArray( response.data ) ) {
				response.data.forEach( function ( item ) {
					if ( ! item || ! item.id ) {
						return;
					}
					if ( 'section' === item.type ) {
						sections.push( item );
					} else {
						pages.push( item );
					}
				} );
			}
		}

		if ( ! pages.length && ! sections.length && model ) {
			pages    = this._normalizeItemList( model.get( 'pages' ) );
			sections = this._normalizeItemList( model.get( 'sections' ) );
		}

		pages    = this._normalizeItemList( pages );
		sections = this._normalizeItemList( sections );

		return {
			pages:    pages,
			sections: sections,
			items:    pages.concat( sections ),
		};
	},

	_syncKitModelFromPayload: function ( model, payload ) {
		if ( ! model ) {
			return;
		}

		var pageIds    = this._getKitPageIds( model );
		var sectionIds = this._getKitSectionIds( model );
		var attrs      = {
			item_count: pageIds.length + sectionIds.length,
		};

		if ( payload && payload.items && payload.items.length ) {
			attrs.pages             = payload.pages;
			attrs.sections          = payload.sections;
			attrs.included_pages    = pageIds;
			attrs.included_sections = sectionIds;
		}

		model.set( attrs, { silent: true } );
	},

	_resolveKitItemsFromCatalog: function ( model ) {
		var catalog    = this.options.collection._catalog || { page: {}, section: {} };
		var pageIds    = this._getKitPageIds( model );
		var sectionIds = this._getKitSectionIds( model );
		var pages      = [];
		var sections   = [];

		pageIds.forEach( function ( id ) {
			var item = catalog.page[ id ] || this._templateLookup[ id ];

			if ( item ) {
				pages.push( item );
			}
		}, this );

		sectionIds.forEach( function ( id ) {
			var item = catalog.section[ id ] || this._templateLookup[ id ];

			if ( item ) {
				sections.push( item );
			}
		}, this );

		return this._buildKitPayload( model, pages, sections );
	},

	_sortKitItems: function ( model, items ) {
		items = items || [];

		if ( ! items.length ) {
			return [];
		}

		var order = this._getKitItemIds( model );

		if ( ! order.length ) {
			order = items.map( function ( item ) {
				return parseInt( item.id, 10 );
			} ).filter( Boolean );
		}

		var map = {};

		items.forEach( function ( item ) {
			if ( item && item.id ) {
				map[ parseInt( item.id, 10 ) ] = item;
			}
		} );

		return order.map( function ( id ) {
			return map[ id ];
		} ).filter( Boolean );
	},

	enterKitDetailsMode: function ( model ) {
		var self = this;

		if ( ! model || model.get( 'type' ) !== 'kit' ) {
			return;
		}

		this._kitDetailsModel = model;
		this.headerView.enterKitDetailsMode( model );
		this.kitDetailsView.showLoading();
		this._setViewMode( 'kit-details' );

		this.fetchKitItems( model, function ( payload ) {
			self._indexTemplateItems( payload.items );
			self.kitDetailsView.setItems( payload );
		} );
	},

	exitKitDetailsMode: function () {
		this._kitDetailsModel = null;
		this._kitItemsCollection.reset( [] );
		this.headerView.exitKitDetailsMode();
		this._setViewMode( 'library' );
	},

	_buildKitPayload: function ( model, pages, sections, items ) {
		var self    = this;
		var payload = {
			pages:    pages || [],
			sections: sections || [],
			items:    items || [],
		};

		if ( ! payload.items.length && ( payload.pages.length || payload.sections.length ) ) {
			payload.items = payload.pages.concat( payload.sections );
		}

		if ( payload.items.length && ! payload.pages.length && ! payload.sections.length ) {
			payload.items.forEach( function ( item ) {
				if ( 'section' === item.type ) {
					payload.sections.push( item );
				} else {
					payload.pages.push( item );
				}
			} );
		}

		payload.items = self._sortKitItems( model, payload.items );

		return payload;
	},

	fetchKitItems: function ( model, callback ) {
		var self = this;
		var id   = parseInt( model.get( 'id' ), 10 );

		function deliver( payload ) {
			payload = payload || { pages: [], sections: [], items: [] };
			self._indexTemplateItems( payload.items );
			self._syncKitModelFromPayload( model, payload );
			callback( payload );
		}

		if ( ! id ) {
			deliver( { pages: [], sections: [], items: [] } );
			return;
		}

		if ( this._kitItemsCache[ id ] && this._kitItemsCache[ id ].items && this._kitItemsCache[ id ].items.length ) {
			deliver( this._kitItemsCache[ id ] );
			return;
		}

		jQuery.ajax( {
			url:      BEWTemplateLibraryModal.restUrl + 'kits/' + id + '/items',
			type:     'GET',
			dataType: 'json',
			headers:  { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
		} ).done( function ( response ) {
			var payload = self._normalizeKitResponse( response, model );

			if ( payload.items.length ) {
				payload.items = self._sortKitItems( model, payload.items );
				self._kitItemsCache[ id ] = payload;
				deliver( payload );
				return;
			}

			self.options.collection.prefetchPageSections( function () {
				var fallbackPayload = self._resolveKitItemsFromCatalog( model );
				if ( fallbackPayload.items.length ) {
					self._kitItemsCache[ id ] = fallbackPayload;
				}
				deliver( fallbackPayload );
			} );
		} ).fail( function () {
			self.options.collection.prefetchPageSections( function () {
				var payload = self._resolveKitItemsFromCatalog( model );

				if ( payload.items.length ) {
					self._kitItemsCache[ id ] = payload;
				}

				deliver( payload );
			} );
		} );
	},

	enterPreviewMode: function ( model, options ) {
		if ( ! model ) {
			return;
		}

		options = options || {};
		this._previewFromKit = !! options.fromKitDetails;
		this._previewModel  = model;

		var backTab = this._previewFromKit ? 'kit-details' : this.headerView.currentTab;
		this.previewHeaderView.setContext( model, backTab );
		this.previewBodyView.setModel( model );
		this._setViewMode( 'preview' );
	},

	exitPreviewMode: function () {
		this._previewModel = null;

		if ( this._previewFromKit && this._kitDetailsModel ) {
			this._previewFromKit = false;
			this._setViewMode( 'kit-details' );
			return;
		}

		this._previewFromKit = false;
		this._setViewMode( 'library' );
	},

	_setViewMode: function ( mode ) {
		var self   = this;
		var $el    = this.$el;
		var $modal = window.BEWLibraryModal && typeof BEWLibraryModal.getModal === 'function'
			? BEWLibraryModal.getModal().$el
			: null;

		var wasPreview = $el.hasClass( 'bosa-tk-mode-preview' );

		$el.removeClass( 'bosa-tk-mode-preview bosa-tk-mode-kit-details' );
		if ( $modal ) {
			$modal.removeClass( 'bosa-tk-mode-preview bosa-tk-mode-kit-details' );
		}

		if ( 'preview' === mode ) {
			$el.addClass( 'bosa-tk-mode-preview' );
			if ( $modal ) {
				$modal.addClass( 'bosa-tk-mode-preview' );
			}
			return;
		}

		if ( 'kit-details' === mode ) {
			$el.addClass( 'bosa-tk-mode-kit-details' );
			if ( $modal ) {
				$modal.addClass( 'bosa-tk-mode-kit-details' );
			}
		}

		if ( wasPreview ) {
			this._relayoutGrid( mode );
		}
	},

	_relayoutGrid: function ( mode ) {
		// Kit Details renders in its own BosaTemplatesView instance, separate from templatesView.
		var grid = 'kit-details' === mode && this.kitDetailsView
			? this.kitDetailsView.gridView
			: this.templatesView;

		if ( ! grid || ! grid.collection.length ) {
			return;
		}

		grid.$( '.bosa-tk-card' ).css( 'visibility', 'hidden' );

		requestAnimationFrame( function () {
			grid.destroyMasonry();
			// Restore visibility only once masonry has actually positioned the cards.
			grid.layoutMasonry( function () {
				grid.$( '.bosa-tk-card' ).css( 'visibility', '' );
			} );
		} );
	},

	onPreviewInsert: function () {
		var self = this;
		if ( ! this._previewModel ) {
			return;
		}

		bosaTkRunInsert( this._previewModel, {
			$el: this.previewHeaderView.$el,
			onStart: function () {
				self.previewHeaderView.$( '.bosa-tk-preview-insert' ).prop( 'disabled', true );
			},
			onFinish: function () {
				self.previewHeaderView.$( '.bosa-tk-preview-insert' ).prop( 'disabled', false );
			},
			onSuccess: function () {
				self.exitPreviewMode();
			},
		} );
	},

} );

/**
 * Bosa Library Modal — extends Elementor's modal Layout.
 */
var BEWLibraryLayout = elementorModules.common.views.modal.Layout.extend({

	initialize: function () {
		elementorModules.common.views.modal.Layout.prototype.initialize.apply( this, arguments );

		this.collection     = new BosaTemplatesCollection();
		this._currentTab      = 'pages';
		this._currentSearch   = '';
		this._currentCategory = '';

		this.showLogo();
		this.showContentView();
	},

	getModalOptions: function () {
		return {
			id: 'bosa-library-modal',
			className: 'elementor-templates-modal',
			hide: {
				onBackgroundClick: true,
				onEscKeyPress:     true,
				onOutsideClick:    true,
			},
		};
	},

	getLogoOptions: function () {
		return {
			title: BEWTemplateLibraryModal.i18n.title,
		};
	},

	showContentView: function () {
		var self = this;

		this.modalContent.show( new BosaModalBodyView( {
			collection: this.collection,
			initialTab:       this._currentTab,
			onTabChange:      function ( tab ) { self._onTabChange( tab ); },
			onSearchChange:   function ( s ) { self._onSearchChange( s ); },
			onCategoryChange: function ( c ) { self._onCategoryChange( c ); },
			onRefresh:        function () { self._onRefresh(); },
			onClose:        function () { self.hide(); },
		} ) );

		this._bodyView = this.modalContent.currentView;
		if ( this._bodyView ) {
			this.templatesView = this._bodyView.templatesView;
			this._bindPreviewDelegation();
		}
	},

	_resetBodyFilters: function ( tab ) {
		var toolbar = this._bodyView && this._bodyView.contentView && this._bodyView.contentView.toolbarView;
		if ( toolbar ) {
			toolbar.resetFilters();
			toolbar.setActiveTab( tab || this._currentTab );
		}
	},

	showModal: function () {
		elementorModules.common.views.modal.Layout.prototype.showModal.apply( this, arguments );
		this._bindPreviewDelegation();
		this._renderGoProBanner();

		// Deferred until the tab's own fetch resolves (see onRender() above).
		var self = this;
		this._loadTemplates( function () {
			if ( self._bodyView && self._bodyView.options.collection ) {
				self._bodyView.options.collection.prefetchPageSections();
			}
		} );
	},

	/**
	 * Elementor always builds an empty buttons-wrapper footer for this modal.
	 * Free users get it wrapped in a link to the pricing page (new tab); Pro
	 * users get the same slot with a Pro-specific banner and no link.
	 */
	_renderGoProBanner: function () {
		var modal = this.getModal();

		if ( ! modal || 'function' !== typeof modal.getElements ) {
			return;
		}

		var $wrapper = modal.getElements( 'buttonsWrapper' );

		if ( ! $wrapper || ! $wrapper.length ) {
			return;
		}

		if ( ! $wrapper.find( '.bew-tl-go-pro-banner' ).length ) {
			var img = '<img src="' + BEWTemplateLibraryModal.goProBannerUrl + '" alt="' + ( BEWTemplateLibraryModal.i18n.go_pro || 'Go Pro' ) + '">';

			$wrapper.html(
				BEWTemplateLibraryModal.proPluginActive
					? '<div class="bew-tl-go-pro-banner">' + img + '</div>'
					: '<a href="' + BEWTemplateLibraryModal.proUpgradeUrl + '" target="_blank" rel="noopener noreferrer" class="bew-tl-go-pro-banner">' + img + '</a>'
			);
		}

		$wrapper.show();
	},

	_bindPreviewDelegation: function () {
		var self = this;
		var modal = this.getModal();

		if ( ! modal || ! modal.$el ) {
			return;
		}

		modal.$el.off( 'click.bosaTkPreview' );
		modal.$el.on( 'click.bosaTkPreview', '.bosa-tk-card__thumb, .bosa-tk-card__preview', function ( e ) {
			if ( e.target.closest && e.target.closest( '.bosa-tk-card__insert' ) ) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();

			var bodyView = self._bodyView;
			if ( ! bodyView ) {
				return;
			}

			var $card  = jQuery( e.currentTarget ).closest( '.bosa-tk-card' );
			var gridView = bodyView.$el.hasClass( 'bosa-tk-mode-kit-details' )
				? bodyView.kitDetailsView && bodyView.kitDetailsView.gridView
				: bodyView.templatesView;
			var model  = gridView ? gridView._findModelForCard( $card ) : null;

			if ( ! model ) {
				return;
			}

			if ( model.get( 'type' ) === 'kit' ) {
				bodyView.enterKitDetailsMode( model );
				return;
			}

			var fromKit = bodyView.$el.hasClass( 'bosa-tk-mode-kit-details' );
			bodyView.enterPreviewMode( model, { fromKitDetails: fromKit } );
		} );
	},

	show: function () {
		this.showModal();
	},

	hide: function () {
		if ( this.getModal() ) {
			this.getModal().hide();
		}
	},

	_loadTemplates: function ( callback ) {
		var self = this;

		if ( this.templatesView ) {
			this.templatesView.showLoading();
		}

		if ( callback ) {
			this.collection.once( 'bosa:fetched', callback );
			this.collection.once( 'bosa:error', callback );
		}

		this.collection.fetch( {
			type:     this._currentTab,
			search:   this._currentSearch,
			category: this._currentCategory,
		} );
	},

	_refreshKitUiAfterSync: function () {
		var body = this._bodyView;

		if ( ! body ) {
			return;
		}

		if ( ! body._kitDetailsModel ) {
			return;
		}

		var kitId    = parseInt( body._kitDetailsModel.get( 'id' ), 10 );
		var freshKit = this.collection.get( kitId );

		if ( freshKit ) {
			body._kitDetailsModel = freshKit;
		}

		body.kitDetailsView.showLoading();
		body.fetchKitItems( body._kitDetailsModel, function ( payload ) {
			body.kitDetailsView.setItems( payload );
		} );
	},

	_onTabChange: function ( tab ) {
		if ( this._bodyView ) {
			this._bodyView._previewFromKit  = false;
			this._bodyView._previewModel    = null;
			this._bodyView._kitDetailsModel = null;
			this._bodyView.headerView.exitKitDetailsMode();
			this._bodyView._setViewMode( 'library' );
		}

		this._currentTab      = tab;
		this._currentSearch   = '';
		this._currentCategory = '';
		this._resetBodyFilters( tab );
		this._loadTemplates();
	},

	_onSearchChange: function ( search ) {
		this._currentSearch = search;
		this._loadTemplates();
	},

	_onCategoryChange: function ( category ) {
		this._currentCategory = category;
		this._loadTemplates();
	},

	_onRefresh: function () {
		var self       = this;
		var bodyView   = this._bodyView;
		var headerView = bodyView && bodyView.headerView;
		var btn        = headerView ? headerView.$( '.bosa-tk-refresh' ) : null;

		if ( btn ) { btn.prop( 'disabled', true ).addClass( 'bosa-tk-refresh--spinning' ); }

		if ( bodyView ) {
			bodyView.clearKitRefreshCaches();

			if ( bodyView._kitDetailsModel && bodyView.kitDetailsView ) {
				bodyView.kitDetailsView.showLoading();
			}
		}

		if ( this.collection.resetRefreshCaches ) {
			this.collection.resetRefreshCaches();
		}

		Backbone.ajax( {
			url:     BEWTemplateLibraryModal.restUrl + 'clear-cache',
			type:    'POST',
			headers: { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
			success: function () {
				var refreshCatalog = self.collection.refreshCatalog
					? self.collection.refreshCatalog.bind( self.collection )
					: self.collection.prefetchPageSections.bind( self.collection );

				refreshCatalog( function () {
					if ( bodyView && bodyView.reindexCatalogLookup ) {
						bodyView.reindexCatalogLookup();
					}

					self._loadTemplates( function () {
						self._refreshKitUiAfterSync();

						if ( btn ) {
							btn.prop( 'disabled', false ).removeClass( 'bosa-tk-refresh--spinning' );
						}
					} );
				} );
			},
			error: function () {
				if ( btn ) { btn.prop( 'disabled', false ).removeClass( 'bosa-tk-refresh--spinning' ); }
			},
		} );
	},

	setTab: function ( tab ) {
		if ( tab && tab !== this._currentTab ) {
			this._onTabChange( tab );
		}
	},

} );
