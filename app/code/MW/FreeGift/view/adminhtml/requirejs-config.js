var config = {
    map: {
        '*': {
        }
    },
    paths: {
        'backbone': 'MW_FreeGift/js/newlib/backbone-min',
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
    }
};
