require([
    'jquery',
    "backbone"
], function() {
    //console.log(require.toUrl("./MW_FreeGift/js/iosOverlay/img/check.png"));
    //console.log(mw_baseUrl);
    //window.hasGiftProduct = false;
    //window.hasPromotionMessage = false;
    //window.hasPromotionBanner = false;

    window.freegiftConfig = {
        url: {
            add                                 : mw_baseUrl+'freegift/cart/addg/',
            configure                           : mw_baseUrl+'freegift/cart/configure/',
            getproduct                          : mw_baseUrl+'freegift/cart/getproduct/',
            updatePost                          : mw_baseUrl+'freegift/cart/updatePost/',
            delete                              : mw_baseUrl+'freegift/cart/delete/',
            cart                                : mw_baseUrl+'checkout/cart/'
        },
    };

    (function(jQuery){
        if(jQuery('.mw_disbable_qty_edit').length > 0){
            jQuery('.mw_disbable_qty_edit').each(function(i,v){
                var id_input_qty = jQuery(this).attr('data-qtyid');
                jQuery('#'+id_input_qty).attr('disabled', 'disabled');
            });
        }
        window.FreeGift = {
            Models: {},
            Collections: {},
            Views: {},
            $: jQuery.noConflict()
        };

        _.extend(window.FreeGift, Backbone.Events);

        require(["freegift_view"]);

        //$(mage.apply);
    })(jQuery.noConflict());

    //require(['MW_FreeGift/js/app']);
    //require([
    //    'MW_FreeGift/js/app'
    //], function(App) {
    //    new App;
    //});
})


