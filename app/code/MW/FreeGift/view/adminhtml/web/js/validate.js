require([
    'prototype'
], function() {
    var Validator = new Class.create();

    Validation.add(
        'mw-rewardpoint-validate-coupon-code',
        'The store code may contain only letters (a-z)(A-Z), numbers (0-9), the first character must be a letter',
        function (v) {
            if (Validation.get('IsEmpty').test(v)) {
                return true;
            }
            result = /^[a-zA-Z]+[a-zA-Z0-9]*$/.test(v);
            return result;
        }
    );
});

