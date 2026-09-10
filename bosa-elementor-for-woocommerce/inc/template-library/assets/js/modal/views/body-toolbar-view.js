/* global BEWTemplateLibraryModal, Marionette, Backbone, BEWTLCategoryLists */
/**
 * Modal body toolbar — category filter (left) and search (right).
 */
var BosaBodyToolbarView = Marionette.ItemView.extend({

	className: 'bosa-tk-body-toolbar elementor-template-library-filter-toolbar',

	currentTab: 'pages',

	_tabTypeMap: { kits: 'kit', pages: 'page', sections: 'section' },

	serializeData: function () {
		var i18n = BEWTemplateLibraryModal.i18n;
		var tabs = i18n.search_tabs || {};
		var placeholder = ( tabs[ this.currentTab ] || tabs.pages || i18n.search || 'Search templates…' ).toUpperCase();

		return {
			categoryLabel: this.escapeAttr( i18n.category_label || 'Category' ),
			allCategories: this.escapeHtml( i18n.all_categories || 'All Categories' ),
			optionsHtml:   this.buildCategoryOptionsHtml(),
			placeholder:   this.escapeAttr( placeholder ),
		};
	},

	// Marionette calls template(data) without binding `this` to the view.
	template: function ( data ) {
		return [
			'<div class="bosa-tk-category-wrap">',
				'<div class="bosa-tk-category-field">',
					'<select class="bosa-tk-category" aria-label="' + data.categoryLabel + '">',
						'<option value="">' + data.allCategories + '</option>',
						data.optionsHtml,
					'</select>',
					'<i class="eicon-chevron-down" aria-hidden="true"></i>',
				'</div>',
			'</div>',
			'<div class="bosa-tk-search-wrap">',
				'<input type="text" class="bosa-tk-search" placeholder="' + data.placeholder + '">',
				'<i class="eicon-search" aria-hidden="true"></i>',
			'</div>',
		].join( '' );
	},

	initialize: function () {
		this._tabCategories = {};
		this._selectedCategory = '';
	},

	events: {
		'change .bosa-tk-category': 'onCategoryChange',
		'input .bosa-tk-search':  'onSearch',
	},

	onRender: function () {
		this.syncCategoryDropdown();
	},

	onShow: function () {
		this.syncCategoryDropdown();
	},

	getTypeForTab: function () {
		return this._tabTypeMap[ this.currentTab ] || 'page';
	},

	escapeHtml: function ( value ) {
		return String( value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' );
	},

	escapeAttr: function ( value ) {
		return this.escapeHtml( value ).replace( /"/g, '&quot;' );
	},

	normalizeList: function ( list ) {
		if ( ! list ) {
			return [];
		}
		if ( Array.isArray( list ) ) {
			return list;
		}
		if ( typeof list === 'object' ) {
			return Object.keys( list ).sort( function ( a, b ) {
				return Number( a ) - Number( b );
			} ).map( function ( key ) {
				return list[ key ];
			} );
		}
		return [];
	},

	getStaticCategoryLists: function () {
		if ( typeof window.BEWTLCategoryLists !== 'undefined' && window.BEWTLCategoryLists ) {
			return window.BEWTLCategoryLists;
		}

		if ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.categoryLists ) {
			return BEWTemplateLibraryModal.categoryLists;
		}

		return {};
	},

	getCategoryLabels: function () {
		var type  = this.getTypeForTab();
		var lists = this.getStaticCategoryLists();
		var labels = this.normalizeList( lists[ type ] );

		if ( ! labels.length ) {
			if ( 'kit' === type ) {
				labels = this.normalizeList( lists.kits );
			} else if ( 'page' === type ) {
				labels = this.normalizeList( lists.pages );
			} else if ( 'section' === type ) {
				labels = this.normalizeList( lists.sections );
			}
		}

		if ( labels.length ) {
			return labels;
		}

		if ( typeof BEWTemplateLibraryModal !== 'undefined' && BEWTemplateLibraryModal.restUrl ) {
			if ( ! this._tabCategories[ type ] ) {
				this.fetchCategoriesFromApi( type );
			}

			return this.normalizeList( this._tabCategories[ type ] );
		}

		return [];
	},

	buildCategoryOptionsHtml: function () {
		var self = this;
		return this.getCategoryLabels().map( function ( label ) {
			var text = self.escapeHtml( label );
			return '<option value="' + self.escapeAttr( label ) + '">' + text + '</option>';
		} ).join( '' );
	},

	getSearchPlaceholder: function () {
		var i18n = BEWTemplateLibraryModal.i18n;
		var tabs = i18n.search_tabs || {};
		return ( tabs[ this.currentTab ] || i18n.search || 'Search templates…' ).toUpperCase();
	},

	setActiveTab: function ( tab ) {
		this.currentTab = tab || 'pages';
		this.render();
		this.syncCategoryDropdown();
	},

	fetchCategoriesFromApi: function ( type ) {
		var self = this;

		jQuery.ajax( {
			url:      BEWTemplateLibraryModal.restUrl + 'categories',
			type:     'GET',
			dataType: 'json',
			data:     { type: type },
			headers:  { 'X-WP-Nonce': BEWTemplateLibraryModal.nonce },
		} ).done( function ( response ) {
			if ( ! response || ! response.success || ! Array.isArray( response.data ) ) {
				return;
			}

			var labels = response.data.map( function ( item ) {
				return item && item.label ? item.label : '';
			} ).filter( Boolean );

			if ( ! labels.length ) {
				return;
			}

			self._tabCategories[ type ] = labels;

			if ( type === self.getTypeForTab() ) {
				self.render();
				self.syncCategoryDropdown();
			}
		} );
	},

	syncCategoryDropdown: function () {
		if ( this._selectedCategory ) {
			this.$( '.bosa-tk-category' ).val( this._selectedCategory );
		}
	},

	updateCategoryOptions: function ( categories ) {
		if ( ! categories || ! categories.length ) {
			return;
		}

		var type = this.getTypeForTab();
		this._tabCategories[ type ] = categories;

		var self     = this;
		var current  = this._selectedCategory;
		var allLabel = BEWTemplateLibraryModal.i18n.all_categories || 'All Categories';
		var html     = '<option value="">' + this.escapeHtml( allLabel ) + '</option>';

		categories.forEach( function ( label ) {
			html += '<option value="' + self.escapeAttr( label ) + '">' + self.escapeHtml( label ) + '</option>';
		} );

		this.$( '.bosa-tk-category' ).html( html );
		if ( current ) {
			this.$( '.bosa-tk-category' ).val( current );
		}
	},

	resetFilters: function () {
		this._selectedCategory = '';
		this.$( '.bosa-tk-search' ).val( '' );
		this.$( '.bosa-tk-category' ).val( '' );
	},

	onCategoryChange: function ( e ) {
		this._selectedCategory = e.currentTarget.value || '';
		this.trigger( 'category:change', this._selectedCategory );
	},

	onSearch: function ( e ) {
		var self  = this;
		var value = e.currentTarget.value;
		clearTimeout( this._searchTimer );
		this._searchTimer = setTimeout( function () {
			self.trigger( 'search:change', value );
		}, 400 );
	},

} );
