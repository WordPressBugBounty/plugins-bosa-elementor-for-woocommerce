/* global BEWTemplateLibraryModal, Backbone */
/**
 * Backbone Collection that fetches templates from the Bosa REST v2 API.
 */
var BosaTemplatesCollection = Backbone.Collection.extend({

	/* Map tab slugs (plural) → API type param (singular). */
	_tabTypeMap: { kits: 'kit', pages: 'page', sections: 'section' },

	state: { type: 'page', page: 1, per_page: 60, search: '', category: '' },

	initialize: function () {
		this.totalPages           = 1;
		this.hasMore              = true;
		this._sourceData          = [];
		this._catalog             = { page: {}, section: {} };
		this._catalogPrefetching  = false;
		this._catalogPrefetchDone = false;
		this._kitsRefreshData     = [];
		this._fetchToken          = 0;
		this._isFetching          = false;
		// Accumulated items cache per (type, category, search) filter combo.
		this._batchCache          = {};
	},

	_mergeCatalog: function ( items ) {
		var self = this;

		( items || [] ).forEach( function ( item ) {
			var id;

			if ( ! item || ! item.id ) {
				return;
			}

			id = parseInt( item.id, 10 );

			if ( 'page' === item.type ) {
				self._catalog.page[ id ] = item;
			} else if ( 'section' === item.type ) {
				self._catalog.section[ id ] = item;
			}
		} );
	},

	resetRefreshCaches: function () {
		this._catalog             = { page: {}, section: {} };
		this._catalogPrefetchDone = false;
		this._catalogPrefetching  = false;
		this._kitsRefreshData     = [];
		this._batchCache          = {};
	},

	refreshCatalog: function ( callback ) {
		var self = this;

		callback = callback || function () {};

		this.resetRefreshCaches();
		this._catalogPrefetching = true;

		var types   = [ 'page', 'section', 'kit' ];
		var pending = types.length;

		function done() {
			pending--;

			if ( pending > 0 ) {
				return;
			}

			self._catalogPrefetching  = false;
			self._catalogPrefetchDone = true;
			self.trigger( 'bosa:catalog-ready' );
			callback();
		}

		types.forEach( function ( type ) {
			Backbone.ajax( {
				url:     BEWTemplateLibraryModal.restUrl + 'templates',
				type:    'GET',
				data:    { type: type, per_page: 100, page: 1 },
				headers: { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
				success: function ( response ) {
					if ( response && response.success && Array.isArray( response.data ) ) {
						self._mergeCatalog( response.data );

						if ( 'kit' === type ) {
							self._kitsRefreshData = response.data.slice();
						}
					}
					done();
				},
				error: done,
			} );
		} );
	},

	prefetchPageSections: function ( callback ) {
		var self = this;

		callback = callback || function () {};

		if ( this._catalogPrefetchDone ) {
			callback();
			return;
		}

		if ( this._catalogPrefetching ) {
			this.once( 'bosa:catalog-ready', callback );
			return;
		}

		this._catalogPrefetching = true;

		var types   = [ 'page', 'section' ];
		var pending = types.length;

		function done() {
			pending--;

			if ( pending > 0 ) {
				return;
			}

			self._catalogPrefetching  = false;
			self._catalogPrefetchDone = true;
			self.trigger( 'bosa:catalog-ready' );
			callback();
		}

		types.forEach( function ( type ) {
			Backbone.ajax( {
				url:     BEWTemplateLibraryModal.restUrl + 'templates',
				type:    'GET',
				data:    { type: type, per_page: 100, page: 1 },
				headers: { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
				success: function ( response ) {
					if ( response && response.success && Array.isArray( response.data ) ) {
						self._mergeCatalog( response.data );
					}
					done();
				},
				error: done,
			} );
		} );
	},

	// Cache key for _batchCache, scoped to (type, category, search).
	_cacheKey: function () {
		return JSON.stringify( { type: this.state.type, category: this.state.category, search: this.state.search } );
	},

	/**
	 * Fresh load for tab, search, or category changes, restoring from cache when available or fetching page 1 from the API.
	 *
	 * @param {Object} params New state to merge in (type/category/search/etc).
	 */
	fetch: function ( params ) {
		var resolved = Object.assign( {}, params || {} );
		if ( resolved.type && this._tabTypeMap[ resolved.type ] ) {
			resolved.type = this._tabTypeMap[ resolved.type ];
		}
		resolved.page = 1;

		this.state = Object.assign( {}, this.state, resolved );

		var self      = this;
		var cacheKey  = this._cacheKey();
		var cached    = this._batchCache[ cacheKey ];

		// Bump token even on a cache hit, so a stale in-flight request is ignored.
		this._fetchToken = ( this._fetchToken || 0 ) + 1;

		if ( cached ) {
			var requestToken = this._fetchToken;
			setTimeout( function () {
				if ( requestToken !== self._fetchToken ) {
					return;
				}
				self._sourceData = cached.items;
				self.hasMore     = cached.hasMore;
				self.totalPages  = cached.totalPages;
				self.state.page  = cached.page;
				self.reset( cached.items );
				self.trigger( 'bosa:fetched', self, { append: false } );
			}, 0 );
			return;
		}

		this.hasMore = true;
		this._request( false );
	},

	/**
	 * Loads and appends the next batch of items.
	 * Safely ignores calls while loading or when no more items exist.
	 */
	loadMore: function () {
		if ( this._isFetching || ! this.hasMore ) {
			return;
		}

		this.state = Object.assign( {}, this.state, { page: ( this.state.page || 1 ) + 1 } );
		this._request( true );
	},

	/**
	 * @param {boolean} append True to add to the current results (loadMore),
	 *        false to replace them (fresh fetch()).
	 */
	_request: function ( append ) {
		var self = this;

		this._fetchToken = ( this._fetchToken || 0 ) + 1;
		var requestToken = this._fetchToken;
		this._isFetching = true;

		function isStale() {
			return requestToken !== self._fetchToken;
		}

		function onSuccess( response ) {
			self._isFetching = false;

			if ( isStale() ) {
				return;
			}

			if ( response && response.success && Array.isArray( response.data ) ) {
				self._sourceData = append ? self._sourceData.concat( response.data ) : response.data;
				self._mergeCatalog( response.data );

				if ( append ) {
					self.add( response.data );
				} else {
					self.reset( response.data );
				}

				var perPage = self.state.per_page || 60;
				var total   = response.total || self._sourceData.length;
				self.totalPages = response.total_pages || Math.max( 1, Math.ceil( total / perPage ) );
				self.hasMore    = !! response.has_more;

				self._batchCache[ self._cacheKey() ] = {
					items:      self._sourceData.slice(),
					hasMore:    self.hasMore,
					totalPages: self.totalPages,
					page:       self.state.page,
				};

				if ( Array.isArray( response.category_options ) && response.category_options.length ) {
					self.trigger( 'bosa:category_options', response.category_options );
				}
			} else if ( ! append ) {
				self._sourceData = [];
				self.reset( [] );
				self.totalPages = 1;
				self.hasMore    = false;
			} else {
				self.hasMore = false;
			}

			self.trigger( 'bosa:fetched', self, { append: append } );
		}

		function onError() {
			self._isFetching = false;

			if ( isStale() ) {
				return;
			}

			if ( append ) {
				// Keep whatever's already showing; just stop trying to load more.
				self.hasMore = false;
				self.trigger( 'bosa:fetched', self, { append: true } );
				return;
			}

			self._sourceData = [];
			self.reset( [] );
			self.totalPages = 1;
			self.hasMore    = false;
			self.trigger( 'bosa:error', self );
		}

		return Backbone.ajax( {
			url:     BEWTemplateLibraryModal.restUrl + 'templates',
			type:    'GET',
			data:    this.state,
			headers: { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
			success: onSuccess,
			error:   onError,
		} );
	},

} );
