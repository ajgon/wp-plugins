jQuery(document).ready(function() {
    jQuery('#short-title-prompt-text').css('visibility', 'visible');
    if(jQuery('#short-title').val() != '') {
        jQuery('#short-title-prompt-text').hide();
    }
    jQuery('#short-title').focus(function() {
        jQuery('#short-title-prompt-text').hide();
    }).blur(function() {
        if(jQuery(this).val() == '')
            jQuery('#short-title-prompt-text').show();
    });
});
