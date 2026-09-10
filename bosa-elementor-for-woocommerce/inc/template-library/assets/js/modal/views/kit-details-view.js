/* global BEWTemplateLibraryModal, Marionette, Backbone */
/**
 * Kit details — grid of contained pages/sections.
 */
var BosaKitDetailsView = Marionette.LayoutView.extend({

	className: 'bosa-tk-kit-details',

	regions: {
		gridRegion: '#bosa-kit-details-grid',
	},

	template: function () {
		return '<div class="bosa-tk-kit-details__grid" id="bosa-kit-details-grid"></div>';
	},

	onRender: function () {
		var self = this;

		this.gridView = new BosaTemplatesView( { collection: this.options.itemsCollection } );
		this.gridRegion.show( this.gridView );

		this.listenTo( this.gridView, 'preview:open', function ( model ) {
			self.trigger( 'preview:open', model );
		} );
	},

	showLoading: function () {
		if ( this.gridView ) {
			this.gridView.showLoading();
		}
	},

	setItems: function ( payload ) {
		var pages    = [];
		var sections = [];
		var items    = [];

		if ( payload && ( payload.pages || payload.sections || payload.items ) ) {
			pages    = Array.isArray( payload.pages ) ? payload.pages : [];
			sections = Array.isArray( payload.sections ) ? payload.sections : [];
			items    = Array.isArray( payload.items ) ? payload.items : pages.concat( sections );
		} else if ( Array.isArray( payload ) ) {
			items = payload;
		}

		if ( items.length && ! pages.length && ! sections.length ) {
			items.forEach( function ( item ) {
				if ( 'section' === item.type ) {
					sections.push( item );
				} else {
					pages.push( item );
				}
			} );
		}

		if ( ! this.gridView ) {
			return;
		}

		this.gridView.hideLoading();
		this.gridView.destroyMasonry();

		if ( ! pages.length && ! sections.length ) {
			this.options.itemsCollection.reset( [] );
			this.gridView.$el.html(
				'<p class="bosa-tk-no-results">' + BEWTemplateLibraryModal.i18n.no_results + '</p>'
			);
			return;
		}

		if ( ! this.gridView.$( '.bosa-tk-grid-items' ).length ) {
			this.gridView.$el.html(
				'<div class="bosa-tk-grid-sizer" aria-hidden="true"></div>' +
				'<div class="bosa-tk-grid-items"></div>'
			);
		}

		// Hide (visibility, not display) until masonry positions the cards.
		var gridView = this.gridView;
		gridView.$el.css( 'visibility', 'hidden' );

		this.options.itemsCollection.reset( items );
		gridView.render();
		gridView.scheduleLayout();

		window.requestAnimationFrame( function () {
			gridView.scheduleLayout( function () {
				gridView.$el.css( 'visibility', '' );
			} );
		} );
	},

} );
