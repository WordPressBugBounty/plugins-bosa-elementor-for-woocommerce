(function () {
    'use strict';

    var BEW_PRICING_URL = 'https://bew.bosathemes.com/pricing';
    var BEW_PROMO_ICON_CLASS = 'bew-pro-widget-promotion';

    // data-library-element-type is "undefined" for promotion tiles, so we
    // identify BEW tiles via our own icon class instead (set in get_pro_widgets()).
    // Real registered widgets use "bew-pro-widget" (no "-promotion" suffix).
    function isBewPromotionTile(tile) {
        return !!(tile && tile.querySelector && tile.querySelector('.' + BEW_PROMO_ICON_CLASS));
    }

    // Elementor's promotion card reads event.detail.ctaUrl; wrapping
    // dispatchEvent lets us set it before Elementor's own listener runs,
    // without touching its native card UI.
    var nativeDispatchEvent = document.dispatchEvent.bind(document);
    document.dispatchEvent = function (event) {
        if (event && 'widget-promotion:open' === event.type && event.detail) {
            if (isBewPromotionTile(event.detail.target)) {
                event.detail.ctaUrl = BEW_PRICING_URL;
            }
        }
        return nativeDispatchEvent(event);
    };

    // Swap the crown icon for a lock icon on BEW promotion tiles. Class
    // swap (not CSS) survives Elementor's panel re-renders; inline styles
    // restore the crown's original top-right 5px position.
    function patchBewProCrowns(root) {
        var scope = root || document;
        var promoIcons = scope.querySelectorAll('.' + BEW_PROMO_ICON_CLASS);
        for (var i = 0; i < promoIcons.length; i++) {
            var tile = promoIcons[i].closest('.elementor-element');
            if (!tile) {
                continue;
            }
            var crown = tile.querySelector('.eicon-upgrade-crown-full');
            if (!crown || crown.classList.contains('eicon-lock')) {
                continue;
            }
            crown.classList.remove('eicon-upgrade-crown-full');
            crown.classList.add('eicon-lock');
            crown.style.color = '#ffffff';
            crown.style.position = 'absolute';
            crown.style.top = '5px';
            crown.style.right = '5px';
        }
    }

    function watchElementorPanel() {
        patchBewProCrowns();

        var panel = document.getElementById('elementor-panel-elements-wrapper') || document.body;
        var observer = new MutationObserver(function () {
            patchBewProCrowns(panel);
        });
        observer.observe(panel, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watchElementorPanel);
    } else {
        watchElementorPanel();
    }

    jQuery(document).ready(function ($) {
        // Fallback for older Elementor builds using the legacy Backbone dialog.
        $(document).on('click', '#elementor-panel-inner .elementor-element--promotion', function () {
            var $dialog = $('#elementor-element--promotion__dialog');
            if (!$dialog.length) return;

            var isBewPro = $(this).find('.' + BEW_PROMO_ICON_CLASS).length > 0;

            if (isBewPro) {
                $dialog.find('.dialog-buttons-wrapper').hide();

                if ($dialog.find('.bew-go-pro-button-wrapper').length === 0) {
                    $dialog.append(
                        '<div class="bew-go-pro-button-wrapper">' +
                        '<a href="' + BEW_PRICING_URL + '" target="_blank" class="bew-pro-urls">Upgrade Now</a>' +
                        '</div>'
                    );
                } else {
                    $dialog.find('.bew-go-pro-button-wrapper').show();
                }
            } else {
                $dialog.find('.dialog-buttons-wrapper').show();
                $dialog.find('.bew-go-pro-button-wrapper').hide();
            }
        });
    });
}());
