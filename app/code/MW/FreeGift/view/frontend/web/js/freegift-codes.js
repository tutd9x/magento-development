/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    "jquery",
    "jquery/ui"
], function($){
    "use strict";
    
    $.widget('mage.freegiftCode', {
        options: {
        },
        _create: function () {
            this.couponCode = $(this.options.freecouponCodeSelector);
            this.removeCoupon = $(this.options.removefreeCouponSelector);

            $(this.options.mwapplyButton).on('click', $.proxy(function () {
                this.couponCode.attr('data-validate', '{required:true}');
                this.removeCoupon.attr('value', '0');
                $(this.element).validation().submit();
            }, this));

            $(this.options.mwcancelButton).on('click', $.proxy(function () {
                this.couponCode.removeAttr('data-validate');
                this.removeCoupon.attr('value', '1');
                this.element.submit();
            }, this));
        }
    });

    return $.mage.freegiftCode;
});