/* global BEWTemplateLibraryModal, Marionette */
/**
 * Single template card: thumbnail, title, free/pro badge, Insert button.
 */
function bosaTkEsc( str ) {
	return String( str || '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}

var BosaTemplateCardView = Marionette.ItemView.extend({

	className: function () {
		return 'bosa-tk-card' + ( 'kit' === this.model.get( 'type' ) ? ' bosa-tk-card--kit' : '' );
	},

	normalizeIdList: function ( value ) {
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

	extractIds: function ( value ) {
		return this.normalizeIdList( value ).map( function ( entry ) {
			if ( entry && typeof entry === 'object' ) {
				return parseInt( entry.id, 10 );
			}

			return parseInt( entry, 10 );
		} ).filter( Boolean );
	},

	getKitItemCount: function ( data ) {
		var pages    = this.extractIds( data.included_pages );
		var sections = this.extractIds( data.included_sections );

		if ( ! pages.length ) {
			pages = this.extractIds( data.pages );
		}

		if ( ! sections.length ) {
			sections = this.extractIds( data.sections );
		}

		var count = pages.length + sections.length;

		if ( ! count && data.item_count ) {
			count = parseInt( data.item_count, 10 ) || 0;
		}

		return count;
	},

	serializeData: function () {
		var data   = Marionette.ItemView.prototype.serializeData.apply( this, arguments );
		var isKit  = 'kit' === data.type;
		var count  = isKit ? this.getKitItemCount( data ) : 0;

		return {
			title:      data.title || '',
			thumbnail:  data.thumbnail || '',
			is_free:    data.is_free,
			isKit:      isKit,
			itemCount:  count,
			showInsert: ! isKit,
		};
	},

	template: function ( data ) {
		var i18n = BEWTemplateLibraryModal.i18n;
		var thumb = data.thumbnail
			? '<img src="' + bosaTkEsc( data.thumbnail ) + '" alt="" loading="lazy">'
			: '<div class="bosa-tk-card__no-thumb"></div>';

		var thumbBadge = '';
		if ( ! data.isKit ) {
			thumbBadge = data.is_free === false
				? '<span class="bosa-tk-badge bosa-tk-badge--pro">' + i18n.pro + '</span>'
				: '<span class="bosa-tk-badge bosa-tk-badge--free">' + i18n.free + '</span>';
		}

		var isProLocked = data.is_free === false && ! BEWTemplateLibraryModal.proPluginActive;
		var insertBtn   = '';

		if ( data.showInsert ) {
			if ( isProLocked ) {
				insertBtn = '<a href="' + ( BEWTemplateLibraryModal.proUpgradeUrl || 'https://bew.bosathemes.com/pricing' ) + '" class="bosa-tk-card__insert bosa-tk-card__go-pro elementor-button" target="_blank" rel="noopener noreferrer">' + ( i18n.go_pro || 'Go Pro' ) + '</a>';
			} else {
				insertBtn = '<button type="button" class="bosa-tk-card__insert elementor-button elementor-button-success" data-btk-build="' + ( BEWTemplateLibraryModal.build || '' ) + '">' + i18n.insert + '</button>';
			}
		}

		var countHtml = data.isKit
			? '<span class="bosa-tk-card__count">' + data.itemCount + '</span>'
			: '';

		return [
			'<div class="bosa-tk-card__thumb">',
				thumb,
				thumbBadge,
				'<div class="bosa-tk-card__preview" role="presentation">',
					'<span class="bosa-tk-card__preview-icon"><i class="eicon-search"></i></span>',
				'</div>',
			'</div>',
			'<div class="bosa-tk-card__info' + ( data.isKit ? ' bosa-tk-card__info--kit' : '' ) + '">',
				'<span class="bosa-tk-card__title">' + bosaTkEsc( data.title ) + '</span>',
				countHtml,
				insertBtn,
			'</div>',
		].join( '' );
	},

	events: {
		'click .bosa-tk-card__insert':  'onInsert',
		'click .bosa-tk-card__thumb':   'onCardOpen',
		'click .bosa-tk-card__preview': 'onCardOpen',
	},

	onCardOpen: function ( e ) {
		if ( e.target.closest && e.target.closest( '.bosa-tk-card__insert' ) ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		if ( this.model.get( 'type' ) === 'kit' ) {
			this.trigger( 'kit:open' );
			return;
		}

		this.trigger( 'preview:open' );
	},

	onInsert: function ( e ) {
		if ( e ) {
			e.stopPropagation();
		}

		if ( bosaTkIsProLocked( this.model ) ) {
			return;
		}

		bosaTkRunInsert( this.model, { $el: this.$el } );
	},

} );
