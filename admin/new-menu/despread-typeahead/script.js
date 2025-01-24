$(window).load(function () {
    var charMap = {
        "�": "a",
        "�": "c",
        "�": "d",
        "�": "e",
        "�": "e",
        "�": "i",
        "�": "n",
        "�": "o",
        "�": "r",
        "�": "s",
        "�": "t",
        "?": "u",
        "�": "u",
        "�": "y",
        "�": "z"
    };
    var names = ["�qwe", "asd�mas", "asd�mas", "asd�mas", "asd�mas", "asd�mas", "asd�mas", "asd�mas", "asd�mas", "asd�mas",
        "asd�mas", "asd�mas", "asd�mas", "asd�mas", "asd�mas"];

    var normalize = function (input) {
        $.each(charMap, function (unnormalizedChar, normalizedChar) {
            if (unnormalizedChar != '?') {
                var regex = new RegExp(unnormalizedChar, 'gi');
                if(input)
                    input = input.replace(regex, normalizedChar);
            }
        });
        return input;
    };

    var queryTokenizer = function (q) {
        var normalized = normalize(q);
        return Bloodhound.tokenizers.whitespace(normalized);
    };

    var nombres = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: 'https://www.slantour.cz/admin/rezervace.php?typ=rezervace&pozadavek=new&query=%QUERY&clear=1&ajax=osoby',
//            url: 'https://www.slantour.cz/admin/new-menu/despread/temp.php?query=%QUERY',
            filter: function (data) {
                var resultList = data.map(function (item) {
                    return {
                        value: normalize(item.nazev),
                        displayValue: item.nazev
                    };
                });
                return resultList;
            }
        }
//        local: $.map(names, function (name) {
//            // Normalize the name - use this for searching
//            var normalized = normalize(name);
//            console.log(normalized + " - " + name);
//            return {
//                value: normalized,
//                // Include the original name - use this for display purposes
//                displayValue: name
//            };
//        })
    });
    nombres.initialize();

    $('#search').typeahead({
        minLength: 2,
        hint: true,
        highlight: true
    }, {
        displayKey: 'displayValue',
        source: nombres.ttAdapter()
    });
});
