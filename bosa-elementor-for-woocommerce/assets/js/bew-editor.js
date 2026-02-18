jQuery(document).ready(function($) {
    $(document).on('click', '#elementor-panel-inner .elementor-element--promotion', function() {
        var dialogContainer = $('#elementor-element--promotion__dialog');
        var isInBewProCategory = $(this).closest('#elementor-panel-category-bew-pro-widget-category').length > 0;
        if (isInBewProCategory) {
            // Hide default button, show custom
            dialogContainer.addClass("bew-hide-default-upgrade-button");
            dialogContainer.removeClass("bew-hide-my-upgrade-button");
            // Append custom button if not already there
            if (dialogContainer.find('.bew-go-pro-button-wrapper').length === 0) {
                var nextButton = '<div class="bew-go-pro-button-wrapper"><a href="https://bosathemes.com/bosa-elementor-for-woocommerce/#pricing" target="_blank" class="bew-pro-urls">Upgrade to BEW Pro</a></div>';
                dialogContainer.append(nextButton);
            }
        } else {
            // Show default button, hide custom
            dialogContainer.removeClass("bew-hide-default-upgrade-button");
            dialogContainer.addClass("bew-hide-my-upgrade-button");
        }
    });
});