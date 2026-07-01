(function () {
    'use strict';

    var PRICING_URL = 'https://bosathemes.com/bosa-elementor-for-woocommerce/#pricing';

    // Modern Elementor (React promotion card): rewrite the dispatched
    // event's ctaUrl before Elementor's own listener reads it.
    // Scoped to only our bew-pro-* widget types so we never touch
    // Elementor's own native promotion dialogs.
    var nativeDispatchEvent = document.dispatchEvent.bind(document);
    document.dispatchEvent = function (event) {
        if (event && 'widget-promotion:open' === event.type && event.detail) {
            var widgetType = (event.detail.widgetType || '').toLowerCase();
            if (widgetType.indexOf('bew-pro-') === 0) {
                event.detail.ctaUrl = PRICING_URL;
                event.detail.ctaText = 'Upgrade Now';
            }
        }
        return nativeDispatchEvent(event);
    };

    // Legacy Elementor (Backbone dialog): inject a custom button pointing
    // to your own URL instead of the default upgrade dialog buttons.
    jQuery(document).ready(function ($) {
        $(document).on('click', '#elementor-panel-inner .elementor-element--promotion', function () {
            var isInBewProCategory = $(this).closest('#elementor-panel-category-bew-pro-widget-category').length > 0;
            if (!isInBewProCategory) return;

            var $dialog = $('#elementor-element--promotion__dialog');
            if (!$dialog.length) return;

            $dialog.find('.dialog-buttons-wrapper').hide();

            if ($dialog.find('.bew-go-pro-button-wrapper').length === 0) {
                $dialog.append(
                    '<div class="bew-go-pro-button-wrapper">' +
                    '<a href="' + PRICING_URL + '" target="_blank" rel="noopener" class="bew-pro-urls">Upgrade Now</a>' +
                    '</div>'
                );
            } else {
                $dialog.find('.bew-go-pro-button-wrapper').show();
            }
        });
    });
}());