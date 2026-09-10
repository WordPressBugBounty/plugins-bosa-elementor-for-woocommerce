(function($) {

    /* Open cart content on add-to-cart click */
    $(document).on('click', '.button.ajax_add_to_cart', function () {
        $('.bew-open-widget-cart').addClass('product-added-to-cart');
    });
    $(document).on('mouseover', '.product-added-to-cart .bew-shoping-detail-cart', function () {
        $('.bew-open-widget-cart').removeClass('product-added-to-cart');
    });
    
    /* My Account popup login toggle */
    jQuery(document).on('click', '#bew_popuplogin', function () {
        if ($('body').hasClass('bew-show-popup-login')) {
            $('body').removeClass('bew-show-popup-login');
        } else {
            $('body').addClass('bew-show-popup-login');
        }
    });
    $(document).on('click', '.bew-toggle-icon', function () {
        $('body').removeClass('bew-show-popup-login');
    });
    
    /* Dropdown Nav — scope to the clicked widget container */
    jQuery(document).on('click', '.bew-category-nav-container .bew-category-parent-label', function (e) {
        e.stopPropagation();
        var $container = jQuery(this).closest('.bew-category-nav-container.bew-clickable');
        if ($container.hasClass('show')) {
            $container.removeClass('show');
        } else {
            $container.addClass('show');
        }
    });
    
    jQuery(document).on('click', function (e) {
        if (!jQuery(e.target).closest('.bew-category-nav-container .bew-category-parent-label').length) {
            jQuery('.bew-category-nav-container.bew-clickable').removeClass('show');
        }
    });
    
    /**
     * Initialise the custom category select for a given Search widget scope.
     * Called both on DOMReady and per-widget via Elementor's element_ready hook
     * so it works on initial load, Elementor editor drag-in, and AJAX reloads.
     *
     * @param {jQuery} $scope  The widget wrapper element (or $(document) on DOMReady).
     */
    function bew_init_search_select($scope) {
        $scope.find('.bew-product-search-select select').each(function () {
            var $this = $(this);
    
            // Avoid double-initialisation
            if ($this.hasClass('bew-select-hidden')) {
                return;
            }
    
            var numberOfOptions = $this.children('option').length;
    
            $this.addClass('bew-select-hidden');
            $this.wrap('<div class="bew-select-wrap"></div>');
            $this.after('<div class="bew-select-styled"></div>');
    
            var $styledSelect = $this.next('div.bew-select-styled');
            $styledSelect.text($this.children('option').eq(0).text());
    
            var $list = $('<ul />', {
                'class': 'bew-select-options'
            }).insertAfter($styledSelect);
    
            for (var i = 0; i < numberOfOptions; i++) {
                $('<li />', {
                    text: $this.children('option').eq(i).text(),
                    rel:  $this.children('option').eq(i).val()
                }).appendTo($list);
                if ($this.children('option').eq(i).is(':selected')) {
                    $list.find('li[rel="' + $this.children('option').eq(i).val() + '"]').addClass('bew-is-selected');
                }
            }
    
            var $listItems = $list.children('li');
    
            $styledSelect.on('click', function (e) {
                e.stopPropagation();
                $('div.bew-select-styled.select-active').not(this).each(function () {
                    $(this).removeClass('select-active').next('ul.bew-select-options').hide();
                });
                $(this).toggleClass('select-active').next('ul.bew-select-options').toggle();
            });
    
            $listItems.on('click', function (e) {
                e.stopPropagation();
                $styledSelect.text($(this).text()).removeClass('select-active');
                $this.val($(this).attr('rel'));
                $list.find('li.bew-is-selected').removeClass('bew-is-selected');
                $list.find('li[rel="' + $(this).attr('rel') + '"]').addClass('bew-is-selected');
                $list.hide();
            });
    
            $(document).on('click.bew_select', function () {
                $styledSelect.removeClass('select-active');
                $list.hide();
            });
        });
    }
    
    // Expose for Elementor hook callbacks executed outside this closure.
    window.bew_init_search_select = bew_init_search_select;

    bew_init_search_select($(document));
    
    })(jQuery);


jQuery( window ).on( 'elementor/frontend/init', function() {
    // Elementor fires 'frontend/element_ready/{widget-name}.{skin}' per widget; $scope is the widget's jQuery-wrapped root element.

    elementorFrontend.hooks.addAction( 'frontend/element_ready/bew-elements-carousel-products.default', function($scope, $){
        var bew_elements_carousel_products  = $scope.find('.bew-elements-carousel-products');
        var desktop_col                     = bew_elements_carousel_products.attr('slider-products');
        var tablet_col                      = bew_elements_carousel_products.attr('slider-products-tablet');
        var mobile_col                      = bew_elements_carousel_products.attr('slider-products-mobile');
        var slider_arrows                   = bew_elements_carousel_products.attr('slider-arrows');
        var slider_dots                     = bew_elements_carousel_products.attr('slider-dots');
        var auto_play                       = bew_elements_carousel_products.attr('auto-play');
        var infinite_loop                   = bew_elements_carousel_products.attr('infinite-loop');
        var transition_speed                = bew_elements_carousel_products.attr('transition-speed');
        var products_scroll                 = bew_elements_carousel_products.attr('products-scroll');
        var products_scroll_tablet          = bew_elements_carousel_products.attr('products-scroll-tablet');
        var products_scroll_mobile          = bew_elements_carousel_products.attr('products-scroll-mobile');

        $(bew_elements_carousel_products).owlCarousel({
            margin: 20,
            autoplay:auto_play,
            responsiveClass:true,
            loop:infinite_loop,
            nav:slider_arrows,
            navText:[
                '<div class="nav-btn prev-slide"><i class="fas fa-chevron-left"></i></div>',
                '<div class="nav-btn next-slide"><i class="fas fa-chevron-right"></i></div>'
            ],
            dots:slider_dots,
            autoplayTimeout: transition_speed,
            responsive:{
                0:{
                    items:mobile_col,
                    slideBy: products_scroll_mobile,
                },
                600:{
                    items:tablet_col,
                    slideBy: products_scroll_tablet,
                },
                1000:{
                    items:desktop_col,
                    slideBy: products_scroll,
                    loop:true
                }
            }
        })        

    });

    /**
     * Search widget — re-initialise custom select on editor drag-in / AJAX load
     */
        elementorFrontend.hooks.addAction('frontend/element_ready/bew-elements-search.default', function ($scope) {
            if (typeof window.bew_init_search_select === 'function') {
                window.bew_init_search_select($scope);
            }
        });

    elementorFrontend.hooks.addAction( 'frontend/element_ready/bew-elements-blog.default', function($scope, $){
        var masonry_section = $scope.find('.bew-blog-grid.bew-masonry');
        var $grid = masonry_section.masonry({
            itemSelector: '.bew-elements-post',
        });
        $grid.imagesLoaded().progress( function() {
            $grid.masonry('layout');
        });
    });

    /**
     * YITH Wishlist's own "Add to wishlist" button block is a JS-hydrated
     * component that only paints itself during the page's initial
     * DOMContentLoaded scan. BEW's own opt-in Compare & Wishlist buttons
     * (7 independent widgets) can render into the page after that --
     * dragging the widget into the Elementor editor, or any live settings
     * change, re-renders its markup via AJAX, which never fires
     * DOMContentLoaded again, leaving the wishlist block empty. YITH
     * itself exposes exactly this re-scan as a wp.hooks action for cases
     * like this; run it on every widget-ready event (global, not scoped
     * to a specific widget name, since new instances can appear via any
     * of the 7) so newly-injected wishlist blocks still hydrate.
     */
    elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function () {
        if ( window.wp && wp.hooks && typeof wp.hooks.doAction === 'function' ) {
            wp.hooks.doAction( 'yith_wcwl_init_add_to_wishlist_components' );
        }
    } );

} );