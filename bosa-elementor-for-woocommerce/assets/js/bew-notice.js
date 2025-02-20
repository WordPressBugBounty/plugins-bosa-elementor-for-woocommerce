jQuery(document).ready(function($){
	 //Admin Page Notice 
    $(document).on( 'click', '.bew-maybe-later', function() {
        $(document).find('.bew-rating-notice').slideUp();
         $.ajax({
            url: BEW_NOTICE.ajaxurl,
            type: 'POST',
            data: {
                nonce: BEW_NOTICE.nonce,
                action: 'bew_rating_maybe_later',
            },
        });
    });

    //BEW Pro Admin Page Notice 
    $(document).on( 'click', '.bew-remind-me-later', function() {
        $(document).find('.bew-pro-notice').slideUp();
         $.ajax({
            url: BEW_UPGRADE.ajaxurl,
            type: 'POST',
            data: {
                nonce: BEW_UPGRADE.nonce,
                action: 'bew_remind_me_later',
            },
        });
    });
    $(document).on( 'click', '#bew-upgrade-dismiss', function() {
        $(document).find('.bew-pro-notice').slideUp();
         $.ajax({
            url: BEW_UPGRADE.ajaxurl,
            type: 'POST',
            data: {
                nonce: BEW_UPGRADE.dismiss_nonce,
                action: 'bew_upgrade_notice_dismiss',
            },
        });
    });

    // Widget Update Notice
    jQuery(document).on( 'click', '#widget-dismiss', function() {
        jQuery(document).find('.bew-new-widget-notice').slideUp();
        jQuery.post({
            url: BEW_NEW.ajaxurl,
            data: {
                nonce: BEW_NEW.nonce,
                action: 'bew_widget_update',
            }
        });
    });
});