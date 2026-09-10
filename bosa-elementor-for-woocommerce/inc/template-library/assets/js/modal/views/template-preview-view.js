/* global Marionette */
/**
 * Preview page body — large template screenshot only.
 */
function bosaTkPreviewEsc( str ) {
	return String( str || '' )
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}

var BosaTemplatePreviewView = Marionette.ItemView.extend({

	className: 'bosa-tk-preview-body',

	template: function ( data ) {
		data = data || {};
		var thumb = data.thumbnail_full || data.thumbnail || '';
		var imgHtml = thumb
			? '<img class="bosa-tk-preview-body__img" src="' + bosaTkPreviewEsc( thumb ) + '" alt="">'
			: '<div class="bosa-tk-preview-body__no-img"></div>';

		return [
			'<div class="bosa-tk-preview-body__scroll">',
				'<div class="bosa-tk-preview-body__shot">',
					imgHtml,
				'</div>',
			'</div>',
		].join( '' );
	},

	setModel: function ( model ) {
		this.model = model;
		this.render();
	},

} );
