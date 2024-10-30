/**
*   Document   : CRO
*   Author     : Babu K A
*   Description: Purpose of the script follows.
*   
*/
jQuery(document).ready(function(jQuery) {

    cro_reorder_set_order();

    jQuery('.cro_post_sortable').cro_post_sortable();
    jQuery('.handles').cro_post_sortable({
        handle: 'span'
    });
    jQuery('.connected').cro_post_sortable({
        connectWith: '.connected'
    });
    jQuery('.exclude').cro_post_sortable({
        items: ':not(.disabled)'
    });
    jQuery('.cro_post_sortable').cro_post_sortable().bind('sortupdate', function(e, ui) {
        cro_reorder_set_order();
    });

    function cro_reorder_set_order() {
        jQuery('.cro_post_sortable li').each(function() {
            jQuery(this).attr('cro_post_order', jQuery(this).index());
        });
    }

    jQuery('#set_order').click(function() {
        var post_path = jQuery('.cro_post_sortable').attr('data-action');
        var jsonObj = [];
        jQuery(this).attr('disabled','disable');
        
        jQuery('.cro_wid_update_status .cro_wid_updating').stop(true,false).fadeIn(200,function(){
            jQuery(this).delay(800).fadeOut(200);
        });
        
        jQuery('.cro_post_sortable li').each(function() {
            item = {}
            item ["id"] = jQuery(this).attr('id');
            item ["meta"] = jQuery(this).attr('data-meta');
            item ["cro_post_order"] = jQuery(this).attr('cro_post_order');
            jsonObj.push(item);
        });

        if( jQuery.isEmptyObject(jsonObj) ) {
            return false;
        }

        var data = {
            action: 'cro_update_order',
            data: jsonObj,
            dataType: "json"
        };

        jQuery.post('admin-ajax.php', data, function(response) {
            // whatever you need to do; maybe nothing
            jQuery('.cro_wid_update_status .cro_wid_updating').hide();
            jQuery('.cro_wid_update_status .cro_wid_saved').stop(true,false).fadeIn(200,function() {
                jQuery(this).delay(1000).fadeOut(200);
            });
            jQuery('#set_order').removeAttr('disabled');
        });
        
    });
});