/* global BEWTemplateLibraryModal, Marionette */
/**
 * Preview page header — back, live preview, insert, close.
 */
var BosaPreviewHeaderView = Marionette.ItemView.extend({

	className: 'bosa-tk-preview-header',

	template: function () {
		var i18n = BEWTemplateLibraryModal.i18n;

		return [
			'<div class="bosa-tk-preview-header__left">',
				'<button type="button" class="bosa-tk-preview-back">',
					'<i class="eicon-chevron-left" aria-hidden="true"></i>',
					'<span class="bosa-tk-preview-back__label"></span>',
				'</button>',
			'</div>',
			'<div class="bosa-tk-preview-header__right">',
				'<a href="#" class="bosa-tk-preview-live elementor-button e-btn-txt" target="_blank" rel="noopener noreferrer">',
					'<i class="eicon-preview-medium" aria-hidden="true"></i>',
					'<span>' + ( i18n.live_preview || 'Live Preview' ) + '</span>',
				'</a>',
				'<button type="button" class="bosa-tk-preview-insert elementor-button elementor-button-success">',
					'<i class="eicon-library-download" aria-hidden="true"></i>',
					'<span>' + i18n.insert + '</span>',
				'</button>',
				'<button type="button" class="bosa-tk-preview-close" aria-label="' + ( i18n.close || 'Close' ) + '">&times;</button>',
			'</div>',
		].join( '' );
	},

	events: {
		'click .bosa-tk-preview-back':   'onBack',
		'click .bosa-tk-preview-insert': 'onInsert',
		'click .bosa-tk-preview-close':  'onClose',
	},

	setContext: function ( model, tab ) {
		this.model = model;
		this.tab   = tab || 'sections';
		this.render();
	},

	onRender: function () {
		var i18n     = BEWTemplateLibraryModal.i18n;
		var backMap  = i18n.back_to || {};
		var backText = 'kit-details' === this.tab
			? ( i18n.back_to_kit || 'Back To Kit' )
			: ( backMap[ this.tab ] || backMap.sections || 'Back' );

		this.$( '.bosa-tk-preview-back__label' ).text( backText );

		var previewUrl = this.model ? ( this.model.get( 'preview_url' ) || '' ) : '';
		var $live      = this.$( '.bosa-tk-preview-live' );

		if ( previewUrl && /^https?:\/\//i.test( previewUrl ) ) {
			$live.attr( 'href', previewUrl ).removeClass( 'bosa-tk-preview-live--hidden' );
		} else {
			$live.addClass( 'bosa-tk-preview-live--hidden' );
		}

		this._updateInsertButton();
	},

	_updateInsertButton: function () {
		var i18n    = BEWTemplateLibraryModal.i18n;
		var $insert = this.$( '.bosa-tk-preview-insert' );
		var locked  = this.model && bosaTkIsProLocked( this.model );

		if ( locked ) {
			$insert
				.removeClass( 'elementor-button-success' )
				.addClass( 'bosa-tk-go-pro' )
				.find( 'span' )
				.text( i18n.go_pro || 'Go Pro' );
			return;
		}

		$insert
			.addClass( 'elementor-button-success' )
			.removeClass( 'bosa-tk-go-pro' )
			.find( 'span' )
			.text( i18n.insert );
	},

	onBack: function () {
		this.trigger( 'preview:back' );
	},

	onInsert: function ( e ) {
		if ( this.model && bosaTkIsProLocked( this.model ) ) {
			if ( e ) {
				e.preventDefault();
			}
			window.open( bosaTkGetProUpgradeUrl(), '_blank', 'noopener,noreferrer' );
			return;
		}

		this.trigger( 'preview:insert' );
	},

	onClose: function () {
		this.trigger( 'preview:close' );
	},

} );
