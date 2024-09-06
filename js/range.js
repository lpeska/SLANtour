    var $range = $("#range");
    var $inputFrom = $("#range-min");
    var $inputTo = $("#range-max");
    var instance;
    var min = 0;
    var max = 100000;
    var from = 0;
    var to = 100000;
    var step = 1000;
   
   function updateInputs(data) {
        from = data.from;
        to = data.to;

        $inputFrom.prop("value", from + " Kč");
        $inputTo.prop("value", to + " Kč");
    }

    

    function removeCurrency (value) {
        value += "";
        return value === "" ? "" : value.replace(/ Kč/g, "");
    }

(function ($) {

    "use strict";



    $range.ionRangeSlider({
        skin: "flat",
        type: "double",
        min: min,
        max: max,
        from: from,
        to: to,
        step: step,
        postfix: " Kč",
        onStart: updateInputs,
        onChange: updateInputs,
        onFinish: function () {
            console.log("slider finish");
            $inputFrom.trigger('change');
        }
    });
    instance = $range.data("ionRangeSlider");



    $inputFrom.on("change", function () {
        var val = $(this).prop("value");

        // validate
        if (val < min || !val) {
            val = min;
        } else if (val > to) {
            val = to;
            if (val - step >= min) {
                val -= step;
            }
        }

        from = parseInt(removeCurrency(val));

        instance.update({
            from: removeCurrency(val)
        });

        $(this).prop("value", removeCurrency(val) + " Kč");

    });

    $inputFrom.on("focus", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val));
    });

    $inputFrom.on("blur", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val) + " Kč");
    });

    const typingDelay = 1000;
    let typingTimerFrom;

    $inputFrom.on("input", function () {
        clearTimeout(typingTimerFrom);   // Clear the previous timer on each keystroke
        typingTimerFrom = setTimeout(function() {
            // Code to execute after delay
            $inputFrom.trigger('change'); // Trigger the event
        }, typingDelay);
    });

    $inputTo.on("change", function () {
        var val = $(this).prop("value");

        // validate
        if (val < from) {
            val = from;
            if (val + step <= max) {
                val += step;
            }
        } else if (val > max) {
            val = max;
        }

        to = parseInt(removeCurrency(val));

        instance.update({
            to: removeCurrency(val)
        });

        $(this).prop("value", removeCurrency(val) + " Kč");
    });

    $inputTo.on("focus", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val));
    });
    
    $inputTo.on("blur", function () {
        var val = $(this).prop("value");
        $(this).prop("value", removeCurrency(val) + " Kč");
    });

    let typingTimerTo;

    $inputTo.on("input", function () {
        clearTimeout(typingTimerTo);   // Clear the previous timer on each keystroke
        typingTimerTo = setTimeout(function() {
            // Code to execute after delay
            $inputTo.trigger('change'); // Trigger the event
        }, typingDelay);
    });

})(window.jQuery);

