/* global Marionette */
/**
 * Modal body content — toolbar and template grid regions.
 */
var BosaModalContentView = Marionette.LayoutView.extend({

	className: 'bosa-tk-modal-content-wrap',

	regions: {
		toolbarRegion: '#bosa-modal-toolbar',
		gridRegion:    '#bosa-modal-grid',
	},

	template: function () {
		return [
			'<div class="bosa-tk-body-toolbar-host" id="bosa-modal-toolbar"></div>',
			'<div class="bosa-tk-body-grid-host" id="bosa-modal-grid"></div>',
		].join( '' );
	},

	onRender: function () {
		var self = this;

		this.toolbarView = new BosaBodyToolbarView();
		this.templatesView = new BosaTemplatesView( { collection: this.options.collection } );

		this.toolbarRegion.show( this.toolbarView );
		this.gridRegion.show( this.templatesView );

		if ( this.options.initialTab ) {
			this.toolbarView.setActiveTab( this.options.initialTab );
		}

		this.listenTo( this.toolbarView, 'search:change',   this.options.onSearchChange );
		this.listenTo( this.toolbarView, 'category:change', this.options.onCategoryChange );

		this.listenTo( this.options.collection, 'bosa:category_options', function ( categories ) {
			self.toolbarView.updateCategoryOptions( categories );
		} );
	},

} );
