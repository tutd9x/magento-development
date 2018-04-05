var config = {
    map: {
        '*': {
            //freegiftCode:           'MW_FreeGift/js/freegift-codes'
            //discountCode:           'Magento_Checkout/js/discount-codes',
            //shoppingCart:           'Magento_Checkout/js/shopping-cart',
            //regionUpdater:          'Magento_Checkout/js/region-updater',
            //opcOrderReview:         'Magento_Checkout/js/opc-order-review',
            //sidebar:                'Magento_Checkout/js/sidebar',
            //payment:                'Magento_Checkout/js/payment',
            //paymentAuthentication:  'Magento_Checkout/js/payment-authentication'
        }
    },
    //baseUrl: 'MW_FreeGift',
    paths: {
        //'jquery': 'vendor/jquery/jquery',
        //'test': 'MW_FreeGift/js/test',
        //'bcarousel': 'MW_FreeGift/js/bcarousel',
        'backbone': 'MW_FreeGift/js/newlib/backbone-min',
        //'jquery_tooltip': 'MW_FreeGift/js/jquery.ezpz_tooltip',
        //'iosOverlay': 'MW_FreeGift/js/iosOverlay',
        //'canvasloader': 'MW_FreeGift/js/newlib/canvasloader-min',
        //'jcarousel': 'MW_FreeGift/js/jcarousel',
        //'custombox': 'MW_FreeGift/js/jquery.custombox',
        //'jquery_mousewheel': 'MW_FreeGift/js/jquery.mousewheel',
        //'jquery_mwheelIntent': 'MW_FreeGift/js/mwheelIntent',
        //'jscrollpane': 'MW_FreeGift/js/jquery.jscrollpane.min',
        //'view': 'MW_FreeGift/js/view',
        'prototype': 'legacy-build.min'
        //'calendar': 'MW_FreeGift/js/calendar/calendar'

    },
    shim: {
        'backbone': {
            //These script dependencies should be loaded before loading
            //backbone.js
            deps: ['underscore', 'jquery'],
            //Once loaded, use the global 'Backbone' as the
            //module value.
            exports: 'Backbone'
        }
        //'bcarousel': {
        //    deps: ['jquery']
        //},
        //'custombox': {
        //    deps: ['jquery']
        //},
        //'jcarousel': {
        //    deps: ['jquery']
        //},
        //'jquery_tooltip': {
        //    deps: ['jquery']
        //},
        //'iosOverlay': {
        //    deps: ['canvasloader']
        //},
        //jquery_mousewheel: {
        //    deps: ["jquery"]
        //},
        //jquery_mwheelIntent: {
        //    deps: ["jquery_mousewheel"]
        //},
        //jscrollpane: {
        //    deps: ["jquery_mwheelIntent"]
        //},
        //'view': {
        //    deps: ['bcarousel', 'backbone', 'iosOverlay', 'jquery_tooltip', 'jcarousel', 'custombox', 'jscrollpane']
        //}
    }
};
