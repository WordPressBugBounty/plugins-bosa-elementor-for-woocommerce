(function () {
    'use strict';

    var BEW_PRICING_URL = 'https://bew.bosathemes.com/pricing';
    var BEW_PROMO_ICON_CLASS = 'bew-pro-widget-promotion';
    // Locked "requires WooCommerce" tiles (set in BEW::get_woo_locked_tiles()).
    var BEW_WOO_ICON_CLASS = 'bew-woo-widget-promotion';

    // data-library-element-type is "undefined" for promotion tiles, so we
    // identify BEW tiles via our own icon class instead (set in get_pro_widgets()).
    // Real registered widgets use "bew-pro-widget" (no "-promotion" suffix).
    function isBewPromotionTile(tile) {
        return !!(tile && tile.querySelector && tile.querySelector('.' + BEW_PROMO_ICON_CLASS));
    }

    function isBewWooLockedTile(tile) {
        return !!(tile && tile.querySelector && tile.querySelector('.' + BEW_WOO_ICON_CLASS));
    }

    // Elementor's promotion card reads event.detail (title, content, ctaText,
    // ctaUrl, hideProTag); wrapping dispatchEvent lets us set those before
    // Elementor's own listener runs, without touching its native card UI.
    // Locked Pro tiles: "Activate License" / "Renew License" with BEW Pro
    // installed, otherwise "Upgrade Now" (BEW::pro_cta(), localized as BEW_PANEL).
    function proCtaText() {
        return (window.BEW_PANEL && window.BEW_PANEL.proCtaText) || 'Upgrade Now';
    }

    function proCtaUrl() {
        return (window.BEW_PANEL && window.BEW_PANEL.proCtaUrl) || BEW_PRICING_URL;
    }

    // Elementor renders the card with React and its button has no stable
    // class, so tag the button that links to our CTA and style that tag only
    // (panel.css: .bew-promo-cta) -- other plugins' cards are never touched.
    function tagBewCtaButton(url) {
        var tag = function () {
            var links = document.querySelectorAll('.e-widget-promotion-wrapper a[href], .MuiButton-root[href]');
            for (var i = 0; i < links.length; i++) {
                if (links[i].getAttribute('href') === url) {
                    links[i].classList.add('bew-promo-cta');
                    return true;
                }
            }
            return false;
        };
        if (tag()) return;
        var observer = new MutationObserver(function () {
            if (tag()) observer.disconnect();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        setTimeout(function () { observer.disconnect(); }, 3000);
    }

    var nativeDispatchEvent = document.dispatchEvent.bind(document);
    document.dispatchEvent = function (event) {
        var bewCtaUrl = '';
        if (event && 'widget-promotion:open' === event.type && event.detail) {
            if (isBewWooLockedTile(event.detail.target)) {
                if (window.BEW_PANEL && window.BEW_PANEL.wooTitle) {
                    event.detail.title = window.BEW_PANEL.wooTitle;
                    event.detail.content = window.BEW_PANEL.wooContent;
                    event.detail.ctaText = window.BEW_PANEL.wooCtaText;
                    event.detail.ctaUrl = window.BEW_PANEL.wooCtaUrl;
                    event.detail.hideProTag = true;
                    bewCtaUrl = event.detail.ctaUrl;
                }
            } else if (isBewPromotionTile(event.detail.target)) {
                event.detail.ctaText = proCtaText();
                event.detail.ctaUrl = proCtaUrl();
                bewCtaUrl = event.detail.ctaUrl;
            }
        }
        var result = nativeDispatchEvent(event);
        if (bewCtaUrl) {
            tagBewCtaButton(bewCtaUrl);
        }
        return result;
    };

    // Swap the crown icon for a lock icon on BEW promotion tiles. Class
    // swap (not CSS) survives Elementor's panel re-renders; inline styles
    // restore the crown's original top-right 5px position.
    function patchBewProCrowns(root) {
        var scope = root || document;
        var promoIcons = scope.querySelectorAll('.' + BEW_PROMO_ICON_CLASS + ', .' + BEW_WOO_ICON_CLASS);
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
            var isBewWoo = $(this).find('.' + BEW_WOO_ICON_CLASS).length > 0;

            if (isBewWoo) {
                // No Pro upsell button on a WooCommerce-locked tile; show why it is locked.
                $dialog.find('.dialog-buttons-wrapper').hide();
                $dialog.find('.bew-go-pro-button-wrapper').hide();
                if (window.BEW_PANEL) {
                    $dialog.find('.dialog-message').text(window.BEW_PANEL.wooContent);
                }
            } else if (isBewPro) {
                $dialog.find('.dialog-buttons-wrapper').hide();

                if ($dialog.find('.bew-go-pro-button-wrapper').length === 0) {
                    $dialog.append(
                        $('<div class="bew-go-pro-button-wrapper"></div>').append(
                            $('<a target="_blank" class="bew-pro-urls"></a>').attr('href', proCtaUrl()).text(proCtaText())
                        )
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
