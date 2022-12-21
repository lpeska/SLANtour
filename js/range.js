(function ($) {

    "use strict";

    var $range = $("#range");
    var $inputFrom = $("#range-min");
    var $inputTo = $("#range-max");
    var instance;
    var min = 0;
    var max = 100000;
    var from = 0;
    var to = 100000;

    $range.ionRangeSlider({
        skin: "flat",
        type: "double",
        min: min,
        max: max,
        from: from,
        to: to,
        step: 1000,
        postfix: " Kč",
        onStart: updateInputs,
        onChange: updateInputs,
        onFinish: updateInputs
    });
    instance = $range.data("ionRangeSlider");

    function updateInputs(data) {
        from = data.from;
        to = data.to;

        $inputFrom.prop("value", from + " Kč");
        $inputTo.prop("value", to + " Kč");
    }

    function removeCurrency (value) {
        return value.replace(/ Kč/g, "");
    }

    $inputFrom.on("change", function () {
        var val = $(this).prop("value");

        // validate
        if (val < min) {
            val = min;
        } else if (val > to) {
            val = to;
        }

        instance.update({
            from: val
        });

        $(this).prop("value", val + " Kč");

    });

    $inputFrom.on("focus", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val));
    });

    $inputFrom.on("blur", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val) + " Kč");
    });

    $inputTo.on("change", function () {
        var val = $(this).prop("value");

        // validate
        if (val < from) {
            val = from;
        } else if (val > max) {
            val = max;
        }

        instance.update({
            to: val
        });

        $(this).prop("value", val + " Kč");
    });

    $inputTo.on("focus", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val));
    });
    
    $inputTo.on("blur", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val) + " Kč");
    });

})(window.jQuery);

