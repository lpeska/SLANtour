/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    var table = $('table');

    //zaskrtnutelne radky tabulky on click
    table.find('tr.selectable').mousedown(function (event) {
        $(this).on('mouseup mousemove', function handler(evt) {
            //pokud jsem klikl, ne pokud tahnu mysi >> umozni zabrani textu do bloku bez oznaceni radku
            if (evt.type === 'mouseup')
                selectRowBox(event, $(this));
            $(this).off('mouseup mousemove', handler);
        });
    });
    //musim zastavit propagaci u vsech "tlacitek" v tabulce aby se jednim klikem neodpalil event 2x
    table.find('a, button, input').mousedown(function (event) {
        event.stopPropagation();
    });
    //confirmations
    $('.confirm-delete').click(function () {
        return confirm('Opravdu smazat?');
    });
    $('.confirm-reload').click(function () {
        return confirm('Stránka bude znovu naètena a pøijdete o neuložená data. Pokraèovat?');
    });
    $('.confirm-action').click(function (event) {
        return confirm('Opravdu provést akci?');
    });
    //delete hodnot v inputech (input musi byt predchozi sibling)
    $('.del-input').click(function () {
        $(this).prev().val("");
        return false;
    });
    //checkall checkboxes
    $('.check-all').click(function () {
        var checked = $(this).prop('checked');
        var cbValue = $(this).val();
        if (cbValue == "on")
            $(this).parent().parent().parent().find("input[type=checkbox]").prop('checked', checked);
        else
            $(this).parent().parent().parent().find("input[type=checkbox]." + cbValue).prop('checked', checked);
    });

    initDatepickers();
});

/**
 * Inicializuje vsechny datepickery - staci jen input type=text dat tridu calendar-ymd
 */
function initDatepickers() {
    $('.calendar-ymd').datepicker({
        changeMonth: true,
        changeYear: true,
        dateFormat: "dd.mm. yy",
        dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Èt', 'Pa', 'So'],
        monthNames: ['Leden', 'Únor', 'Bøezen', 'Duben', 'Kvìten', 'Èerven', 'Èervenec', 'Srpen', 'Záøí', 'Øíjen', 'Listopad', 'Prosinec'],
        monthNamesShort: ['Leden', 'Únor', 'Bøezen', 'Duben', 'Kvìten', 'Èerven', 'Èervenec', 'Srpen', 'Záøí', 'Øíjen', 'Listopad', 'Prosinec'],
        yearRange: 'c-2:c+10',
        firstDay: 1
    });
    $('#ui-datepicker-div').on('click', function(event) {
        event.stopPropagation();
    });
}

/**
 * Pokud ma tabulka checkbox nebo radio, po kliku na radek ho zaskrtne / odskrtne
 */
function selectRowBox(event, clickedTr) {
    //v subTables nechci zaskrtabvat i rodice podtabulky
    event.stopPropagation();
    var chbox = clickedTr.find('td > input[type=checkbox]').first();
    chbox.prop('checked', !chbox.is(':checked'));
    var radio = clickedTr.find('td > input[type=radio]').first();
    radio.prop('checked', true);
}

/**
 * Prida email do tabulky
 */
function addEmail() {
    var email = $('input[name=new-email]').val();
    if (email != "" && validateEmail(email)) {
        var rowCount = $('#tbl-emaily tr').length;
        $('#tbl-emaily > tbody > tr').eq(rowCount - 2).after("" +
        "<tr class='selectable'>" +
        "   <td><input type='checkbox' name='cb-emaily[]' value='" + email + "' /></td>" +
        "   <td>" + email + "</td>" +
        "   <td>pøidaný ruènì</td>" +
        "</tr>");
        $('input[name=new-email]').val("");
    } else {
        alert("Neplatný email!");
    }
}

function validateEmail(email) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
}

/**
 * Ziska hodnotu parametru z URL podle jmena
 * @param name
 * @returns {string}
 */
function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

/**
 * Zobrazi status odeslani emailu
 * @param json
 */
function viewStatus(json, statusEl) {
    var html = "";

    for (var i = 0; i < json.length; i++) {
        if (json[i].isSend) {
            html += "<h2 class='green'>" + json[i].email + ": úspìšnì odeslán</h2>";
        } else {
            html += "<h2 class='red'>" + json[i].email + ": nebyl odeslán</h2>";
        }
    }

    $("#email-status").html(html);
}

//todo je to jen copy&paste a nikde se nevyuziva, ale kdyby to nekdo chtel jednou udelat, ma tady zaklad
/**
 * Aktualizuje pozici floating window pri scrollu - takoveto okynko co jezdi s uzivatelem nahoru a dolu podle toho jak scrolluje
 */
var setupFloatingWindows = function () {
    var el = $('.floating-window');
    var elpos_original = el.offset().top;
    alert(elpos_original);
    $(window).scroll(function () {
        var elpos = el.offset().top;
        var windowpos = $(window).scrollTop();
        var finaldestination = windowpos;
        if (windowpos < elpos_original) {
            finaldestination = elpos_original;
            el.stop().css({'top': 200});
        } else {
            el.stop().animate({'top': finaldestination - elpos_original + 10}, 500);
        }
    });
};

function datesDiff(firstDate, secondDate) {
    var oneDay = 24 * 60 * 60 * 1000; //1 day in ms
    var firstDate = new Date(2008, 01, 12);
    var secondDate = new Date(2008, 01, 22);
}

/**
 * Zamezuje napsani jinych znaku nez cisel s maximalnim poctem znaky e.data.maxLength. Note: v ff nelze pouzit klavesove zkratky ctrl+cokoliv
 * @param e
 * @returns {boolean}
 */
function keyNumberFilter(e) {
    var fAllowedChars = [
        48, 49, 50, 51, 52, 53, 54, 55, 56, 57          //numbers
    ];
    var fAllowedCharsFF = [
        8,                                              //backspace
        37,                                             //arr left
        39,                                             //arr right
        46,                                             //delete
        9,                                              //tab
        116,                                            //f5
        13                                              //enter
    ];
    var code = e.keyCode || e.which;
    var maxLength = e.data.maxLength;
    var selectionLength = $(this)[0].selectionEnd - $(this)[0].selectionStart;

    //ff - specialni znaky (tab, sipky...) nefunguji, kdyz jsou povoleny jen cisla
    if (/Firefox/i.test(navigator.userAgent)) {
        //input je plny a neni highlighted
        if ($(this).val().length == maxLength && selectionLength < maxLength) {
            //povol specialni znaky (tab, sipky...)
            if ($.inArray(code, fAllowedCharsFF) == -1) {
                e.preventDefault();
                return false;
            }
            //input neni plny
        } else {
            //povol cisla a specialni znaky
            if ($.inArray(code, fAllowedCharsFF) == -1 && $.inArray(code, fAllowedChars) == -1) {
                e.preventDefault();
                return false;
            }
        }
        //others - specialni znaky funguji, i kdyz jsou povoleny jen cisla
    } else {
        //input je plny a neni highlighted
        if ($(this).val().length == maxLength && selectionLength <= 0) {
            e.preventDefault();
            return false;
            //input neni plny
        } else {
            //povol cisla
            if ($.inArray(code, fAllowedChars) == -1) {
                e.preventDefault();
                return false;
            }
        }
    }
}

function showDetailActions(elementId) {
    var element = $('#' + elementId);
    var tooltip = $('#' + elementId + '_showhide');
    element.toggle();

    if (element.is(':visible')) {
        tooltip.html("<< skrýt");
        tooltip.css('display', 'block');
    } else {
        tooltip.html("další >>");
        tooltip.css('display', 'inline');
    }

    return false;
}