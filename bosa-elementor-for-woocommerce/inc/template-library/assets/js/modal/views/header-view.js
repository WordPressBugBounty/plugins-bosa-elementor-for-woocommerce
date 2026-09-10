/* global BEWTemplateLibraryModal, Marionette */
/**
 * Modal header: brand, tab nav, and actions.
 */
var bewLogoSvg =
	'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" aria-hidden="true" focusable="false">'
	+ '<circle fill="#624fe0" cx="150" cy="150" r="150"/>'
	+ '<path fill="#FFFFFF" d="M65.47,148.008h9.405c1.719,0,3.217-0.277,4.494-0.832c1.276-0.554,2.26-1.359,2.954-2.413c0.693-1.053,1.04-2.302,1.04-3.745c0-2.218-0.763-3.911-2.289-5.076c-1.526-1.166-3.593-1.749-6.2-1.749h-6.408v39.283h7.657c1.997,0,3.8-0.291,5.409-0.874c1.609-0.582,2.872-1.512,3.787-2.788c0.915-1.276,1.373-2.913,1.373-4.91c0-1.443-0.264-2.676-0.791-3.704c-0.528-1.026-1.277-1.885-2.247-2.579c-0.972-0.693-2.095-1.193-3.371-1.499c-1.277-0.304-2.663-0.457-4.161-0.457H65.47v-6.492h11.735c2.829,0,5.535,0.306,8.115,0.915c2.58,0.612,4.896,1.568,6.949,2.872c2.052,1.304,3.675,2.982,4.869,5.035c1.193,2.053,1.79,4.523,1.79,7.407c0,3.829-0.902,6.964-2.705,9.404c-1.803,2.443-4.342,4.245-7.615,5.41c-3.274,1.165-7.074,1.747-11.402,1.747h-20.64v-58.259h19.391c3.883,0,7.295,0.541,10.237,1.623c2.94,1.081,5.243,2.705,6.908,4.869c1.664,2.164,2.497,4.91,2.497,8.239c0,2.942-0.833,5.466-2.497,7.574c-1.664,2.109-3.968,3.703-6.908,4.785c-2.942,1.081-6.354,1.623-10.237,1.623H65.47V148.008z"/>'
	+ '<path fill="#FFFFFF" d="M227.927,124.704h13.982l-24.552,61.504l-18.31-38.283l-18.393,38.283l-24.468-61.504h13.983l12.65,36.204l16.229-39.116l16.396,39.116L227.927,124.704z"/>'
	+ '<rect x="110.385" y="123.451" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '<rect x="110.385" y="147.725" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '<rect x="110.385" y="170.843" fill="#FFFFFF" width="37.409" height="11.97"/>'
	+ '</svg>';

function bosaTkHeaderEsc( str ) {
	return String( str || '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}

var BosaHeaderView = Marionette.ItemView.extend({

	className: 'bosa-tk-modal-header',

	currentTab: 'pages',

	_kitDetailsModel: null,

	serializeData: function () {
		var i18n = BEWTemplateLibraryModal.i18n;

		if ( this._kitDetailsModel ) {
			return {
				mode:  'kit-details',
				title: this._kitDetailsModel.get( 'title' ) || '',
				back:  i18n.back_to_kits || 'Back To Templates',
			};
		}

		return {
			mode:  'library',
			title: i18n.title,
			tabs:  i18n.tabs,
		};
	},

	template: function ( data ) {
		if ( 'kit-details' === data.mode ) {
			return [
				'<div class="bosa-tk-header__bar bosa-tk-header__bar--kit-details">',
					'<button type="button" class="bosa-tk-kit-details-back">',
						'<i class="eicon-chevron-left" aria-hidden="true"></i>',
						'<span>' + data.back + '</span>',
					'</button>',
					'<h2 class="bosa-tk-header__kit-title">' + bosaTkHeaderEsc( data.title ) + '</h2>',
					'<div class="bosa-tk-header__actions">',
						'<button type="button" class="bosa-tk-close" aria-label="Close">&times;</button>',
					'</div>',
				'</div>',
			].join( '' );
		}

		return [
			'<div class="bosa-tk-header__bar">',
				'<div class="bosa-tk-header__brand">',
					'<span class="bosa-tk-header__logo">' + bewLogoSvg + '</span>',
					'<span class="bosa-tk-header__title">' + bosaTkHeaderEsc( data.title ) + '</span>',
				'</div>',
				'<div class="bosa-tk-tabs">',
					'<button type="button" class="bosa-tk-tab" data-tab="kits">' + data.tabs.kits + '</button>',
					'<button type="button" class="bosa-tk-tab bosa-tk-tab--active" data-tab="pages">' + data.tabs.pages + '</button>',
					'<button type="button" class="bosa-tk-tab" data-tab="sections">' + data.tabs.sections + '</button>',
				'</div>',
				'<div class="bosa-tk-header__actions">',
					'<button type="button" class="bosa-tk-refresh" title="' + ( BEWTemplateLibraryModal.i18n.refresh || 'Sync templates' ) + '">&#8635;</button>',
					'<button type="button" class="bosa-tk-close" aria-label="Close">&times;</button>',
				'</div>',
			'</div>',
		].join( '' );
	},

	events: {
		'click .bosa-tk-tab':              'onTabClick',
		'click .bosa-tk-refresh':          'onRefresh',
		'click .bosa-tk-close':            'onClose',
		'click .bosa-tk-kit-details-back': 'onKitDetailsBack',
	},

	onRender: function () {
		if ( ! this._kitDetailsModel && this.currentTab ) {
			this.$( '.bosa-tk-tab' ).removeClass( 'bosa-tk-tab--active' );
			this.$( '.bosa-tk-tab[data-tab="' + this.currentTab + '"]' ).addClass( 'bosa-tk-tab--active' );
		}
	},

	onTabClick: function ( e ) {
		var tab = e.currentTarget.getAttribute( 'data-tab' );
		this.currentTab = tab;
		this.$( '.bosa-tk-tab' ).removeClass( 'bosa-tk-tab--active' );
		this.$( e.currentTarget ).addClass( 'bosa-tk-tab--active' );
		this.trigger( 'tab:change', tab );
	},

	onRefresh: function () {
		this.trigger( 'refresh' );
	},

	onClose: function () {
		this.trigger( 'close' );
	},

	enterKitDetailsMode: function ( model ) {
		this._kitDetailsModel = model;
		this.$el.addClass( 'bosa-tk-header--kit-details' );
		this.render();
	},

	exitKitDetailsMode: function () {
		this._kitDetailsModel = null;
		this.$el.removeClass( 'bosa-tk-header--kit-details' );
		this.render();
	},

	onKitDetailsBack: function () {
		this.trigger( 'kit-details:back' );
	},

} );
