var osobyData;

$(function () {
    initTypeahead();
    initEvents();
    initRedirected();
});

function initRedirected() {
    var idSerial = getParameterByName('id_serial');

    if (idSerial) {
        //nacti a zobraz serial
        loadSerial(idSerial);
        //nacti a zobraz zajezdy serialu
        loadZajezdySelector(idSerial, true);
        //vyplnit serial nazev, text a odkaz
        $('#serial-id').html('[ ' + idSerial + ' ]');
        $('#serial-id').attr('href', 'https://slantour.cz/admin/serial.php?typ=serial&pozadavek=edit&id_serial=' + idSerial);
        $('input[name=id_serial]').val(idSerial);
    }
}

/**
 * Pomocna funkce pro normalizovani znaku ze vstupu uzivatele
 * @param q
 * @returns {*}
 */
var queryTokenizer = function (q) {
    var normalized = Bloodhound.diacriticsNormalize(q);
    return Bloodhound.tokenizers.whitespace(normalized);
};

/**
 * Definuje akce po vyberu typeahead entity.
 * @param element typeahead element
 * @param hiddenIdEl jquery element, do ktereho se ulozi id jako value
 * @param showIdEl jquery element, do ktereho se ulozi id jako inner html
 * @param callFnc funkce, ktera se zavola s jednim parametrem typu string, ktery odpovida id vybrane veci
 * @param href href na kterou bude odkazovat element showIdEl
 */
function onTypeaheadSelected(element, hiddenIdEl, showIdEl, callFnc, href) {
    element.on('typeahead:selected', function (e, data) {
        hiddenIdEl ? hiddenIdEl.val(data['id']) : null;
        showIdEl ? showIdEl.html('[ ' + data['id'] + ' ]') : null;
        showIdEl ? showIdEl.attr('href', href + data['id']) : null;
        callFnc ? callFnc(data['id']) : null;
        changeProvize();
    });
    element.on('typeahead:autocompleted', function (e, data) {
        hiddenIdEl ? hiddenIdEl.val(data['id']) : null;
        showIdEl ? showIdEl.html('[ ' + data['id'] + ' ]') : null;
        showIdEl ? showIdEl.attr('href', href + data['id']) : null;
        callFnc ? callFnc(data['id']) : null;
        changeProvize();
    });
    element.on('blur', function () {
        if ($(this).val() == '') {
            hiddenIdEl ? hiddenIdEl.val('') : null;
            showIdEl ? showIdEl.html('') : null;
            showIdEl ? showIdEl.attr('href', '') : null;
            callFnc ? callFnc('') : null;
        }
    });
}

/**
 * Ajax pro naplneni selektoru zajezdu (ktere odpovidaji vyranemu serialu)
 * @param idSerial
 * @param oldZajezdy
 */
function loadZajezdySelector(idSerial, oldZajezdy) {
    var emptyOption = $("<option></option>").text('[id] a období zájezdu').val(0);
    var sluzbyWrapper = $("#sluzbyWrapper");
    var url = oldZajezdy ? 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&clear=1&oldZajezdy=true&ajax=zajezdy&serial_id=' + idSerial : 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&clear=1&ajax=zajezdy&serial_id=' + idSerial;

    $.ajax({
        type: 'GET',
        url: url,
        success: function (result) {
            var zajezdySelect = $("#zajezdy-select");
            zajezdySelect.empty();
            sluzbyWrapper.empty();
            zajezdySelect.append(emptyOption);
            $.each(result, function (key, value) {
                zajezdySelect.append($("<option></option>").attr("value", value.id).text("[" + value.id + "] " + value.nazev));
            });
            //pokud je zajezd nacitan z id - tzn z presmerovani vyber spravny option
            var idZajezd = getParameterByName('id_zajezd');
            if (idZajezd) {
                zajezdySelect.val(idZajezd).change();
            }
        },
        error: function (result) {
            var el = $("#zajezdy-select");
            el.empty();
            el.append(emptyOption);
        }
    });
}

/**
 * Ziska vzdalena data ze serveru
 * @param params
 * @param remote
 * @returns {Bloodhound}
 */
function loadRemoteData(params, remote) {
    var settings = {
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value', 'id'), //podle nich vyhledava - value = nazev bez diakritiky
        queryTokenizer: queryTokenizer,
        limit: 15
    };

    if (remote) {
        settings.remote = {
            url: 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&query=%QUERY&clear=1' + params + '&cache=' + (new Date()).getTime(),
            filter: function (data) {
                console.log(data);
                var resultList = data.map(function (item) {
                    return {
                        id: item.id,
                        value: Bloodhound.diacriticsNormalize(item.nazev), //uloz si nazev bez diakritiky do pole value
                        nazev: item.nazev,
                        prijmeni: item.prijmeni,
                        jmeno: item.jmeno,
                        titul: item.titul,
                        datumNarozeni: item.datumNarozeni,
                        rodneCislo: item.rodneCislo,
                        email: item.email,
                        telefon: item.telefon,
                        cisloOP: item.cisloOP,
                        cisloPasu: item.cisloPasu,
                        mesto: item.mesto,
                        ulice: item.ulice,
                        psc: item.psc
                    };
                });
                return resultList;
            }
        }
    } else {
        settings.prefetch = {
            url: 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=new&clear=1' + params,
            filter: function (data) {
                var resultList = data.map(function (item) {
                    return {
                        id: item.id,
                        value: Bloodhound.diacriticsNormalize(item.nazev), //uloz si nazev bez diakritiky do pole value
                        nazev: item.nazev
                    };
                });
                return resultList;
            }
        }
    }
    var bloodhound = new Bloodhound(settings);
    bloodhound.initialize(true);

    return bloodhound;
}

/**
 * Inicializuje typeahead na elementu, ktery je predan jako parametr (je pocitano s elementem, ktery je parent inputu s class=typeahead)
 * @param typeaheadEl
 * @param name
 * @param bloodhound
 * @param minlength
 * @param hint
 * @param displayValue
 */
function setupTypeahead(typeaheadEl, name, bloodhound, minlength, hint, displayValue) {
    typeaheadEl.typeahead({
        hint: hint,
        highlight: true,
        minLength: minlength
    }, {
        name: name,
        displayKey: Handlebars.compile('{{' + displayValue + '}}'), //zobrazi se po vybrani elementu
        source: bloodhound.ttAdapter(),
        templates: {
            suggestion: Handlebars.compile('<p>[{{id}}] {{nazev}}</p>') //zobrazi se v select boxu pro kazdy zaznam
        }
    });
}

/**
 * Nacte termin zajezdu
 * @param zajezdId
 */
function loadTermin(zajezdId) {
    $.ajax({
        url: "../objednavka-proces/index.php?page=ajax-termin-zajezd&zajezd_id=" + zajezdId
    }).done(function (response) {
            initTermin(response);
        }).fail(function (response) {
            console.log(response);
        });
}

/**
 * Nacte vyprodane terminy/blackdays v json formatu pro daterangepicker
 */
function loadSpecialRanges(zajezdId) {
    $.ajax({
        url: "../objednavka-proces/index.php?page=ajax-sr&zajezd_id=" + zajezdId
    }).done(function (response) {
            initDatePicker(response);
        }).fail(function (response) {
            console.log(response);
        });
}

/**
 * Nacte sluzby daneho zajezdu
 */
function loadSluzby(zajezdId) {
    $.ajax({
        url: "../objednavka-proces/index.php?page=ajax-sluzby&zajezd_id=" + zajezdId
    }).done(function (response) {
            initSluzby(response);
        }).fail(function (response) {
            console.log(response);
        });
}

/**
 * Nacte slevy daneho zajezdu
 */
function loadSlevy(zajezdId) {
    $.ajax({
        url: "../objednavka-proces/index.php?page=ajax-slevy&zajezd_id=" + zajezdId
    }).done(function (response) {
            initSlevy(response, $("#slevyWrapper"), "sleva");
        }).fail(function (response) {
            console.log(response);
        });
}

/**
 * Nacte serial
 */
function loadSerial(serialId) {
    $.ajax({
        url: "../objednavka-proces/index.php?page=ajax-serial&serial_id=" + serialId
    }).done(function (response) {
            var serial = $.parseJSON(response);
            if (serial)
                $('#serialy-select').typeahead('val', serial.nazev);
        }).fail(function (response) {
            console.log(response);
        });
}

/**
 * Nacte klientske slevy daneho zajezdu
 */
function loadKlientskeSlevyZajezd(zajezdId) {
    $.ajax({url: "../objednavka-proces/index.php?page=ajax-slevy-klient-zajezd&zajezd_id=" + zajezdId})
        .done(function (response) {
            initSlevy(response, $("#slevyKlientZajezdWrapper"), "sleva-klient-zajezd");
        })
        .fail(function (response) {
            console.log(response);
        });
}

/**
 * Nacte klientske slevy daneho serialu
 */
function loadKlientskeSlevySerial(serialId) {
    $.ajax({url: "../objednavka-proces/index.php?page=ajax-slevy-klient-serial&serial_id=" + serialId})
        .done(function (response) {
            initSlevy(response, $("#slevyKlientSerialWrapper"), "sleva-klient-serial");
        })
        .fail(function (response) {
            console.log(response);
        });
}

function initTermin(data) {
    if (data == "") {
        return;
    }

    data = $.parseJSON(data);

    var datepicker = $('#datepicker');
    if (data.termin == "null") {
        datepicker.val("");
        changePocetNoci(null);
        datepicker.addClass('datepicker').addClass("float-left").removeClass("inputText").prop('disabled', false);
        datepicker.next().show();
    } else {
        datepicker.val(data.termin);
        changePocetNoci(data.termin);
        datepicker.removeClass('datepicker').removeClass("float-left").addClass("inputText").prop('disabled', true);
        datepicker.next().hide();
    }
}

function initSluzby(data) {
    var sluzbyWrapper = $("#sluzbyWrapper");

    if (data == "") {
        sluzbyWrapper.empty();
        return;
    }

    var sluzby = $.parseJSON(data);
    sluzbyWrapper.empty();
    for (var i = 0; i < sluzby.length; i++) {
        var row = $("<div class='value_row sluzba_row'></div>");
        var inputPocet = $("<input type='text' class='smallNumber' name='sluzba-pocet-" + i + "'/>").val(0);
        var inputName = $("<span class='inputText'></span>").html(sluzby[i].nazev + ": ");
        var inputCastka = $("<span class='smallNumber'></span>").html(sluzby[i].castka + " Kè");
        var inputHidId = $("<input type='hidden' name='sluzba-id-" + i + "'/>").val(sluzby[i].id);
        inputPocet.on('change', function () {
            changeProvize();
        });
        row.append(inputPocet).append(inputName).append(inputCastka).append(inputHidId);
        sluzbyWrapper.append().append(row);
    }
}

function initSlevy(data, slevyWrapper, prefix) {
    if (data == "") {
        slevyWrapper.empty();
        return;
    }

    var slevy = $.parseJSON(data);
    var inpuCheckbox;
    var inputHidId;

    slevyWrapper.empty();
    for (var i = 0; i < slevy.length; i++) {
        var row = $("<div class='value_row sleva_row'></div>");
        var inputName = $("<span class='inputText'></span>").html(slevy[i].nazev + ": ");
        var inputCastka = $("<span class='smallNumber'></span>").html(slevy[i].castka + " " + slevy[i].mena);
        if (slevy[i].slevaStalyKlient == 1) {
            inpuCheckbox = $("<input type='checkbox' name='" + prefix + "-pocet-" + i + "'/>").val(1);
            inputHidId = $("<input type='hidden' name='" + prefix + "-id-" + i + "'/>").val(slevy[i].id);
        } else {
            inpuCheckbox = $();
            inputHidId = $();
        }
        row.append(inpuCheckbox).append(inputName).append(inputCastka).append(inputHidId);
        inpuCheckbox.on('change', function () {
            changeProvize();
        });
        slevyWrapper.append().append(row);
    }
}

function initDatePicker(specialRanges) {
    //pokud nemam zadne specialRanges, musim prevest "" na null
    specialRanges = specialRanges ? specialRanges : null;
    var datePicker = $('#datepicker'), calendarIco = $('.calendar-ico'), body = $('body');
    var dateRangePickerOptions = {
        showDropdowns: true,
        format: 'DD.MM. YYYY',
        specialRanges: $.parseJSON(specialRanges),
        locale: {
            applyLabel: 'Potvrdit',
            cancelLabel: 'Zrušit',
            fromLabel: 'Termín od',
            toLabel: 'Termín do',
            daysOfWeek: ['Ne', 'Po', 'Út', 'St', 'Èt', 'Pá', 'So'],
            monthNames: ['leden', 'únor', 'bøezen', 'duben', 'kvìten', 'èerven', 'èervenec', 'srpen', 'záøí', 'øíjen', 'listopad', 'prosinec'],
            firstDay: 1
        }
    };

    //daterangepicker - je hodne spatne napsany - je treba ho vytvorit a schovat. Musel jsem odstranit jejich on blur funkci, ktera kalendar schovavala a udelat si vlastni zpusob (jinak neslo pouzit tlacitko spolu s on focus na inputu pro spusteni kalendare)
    if (datePicker.length) {
        datePicker.daterangepicker(dateRangePickerOptions);
        var dateRPicker = datePicker.data().daterangepicker;
        dateRPicker.hide();
        body.on("click", function (event) {
            dateRPicker.clickCancel(event);
        });
        datePicker.on("click", function (event) {
            event.stopPropagation();
        });
        calendarIco.on("click", function (event) {
            event.stopPropagation();
            dateRPicker.toggle();
        });
        //jinak kalendar zmizi, kdyz na nej kamkoliv kliknu
        $(".daterangepicker").on("click", function (event) {
            event.stopPropagation();
        });
        datePicker.on("apply.daterangepicker", function () {
            if ($(this).val() == "") {
                changePocetNoci(null);
                $('input[name=pocet_noci]').val("0");
            } else {
                changePocetNoci($(this).val());
            }
        });
    }
}

function initTypeahead() {
    var typeaheadSerialy = $('#serialy-select'), typeaheadAgentury = $('#agentury-select'), typeaheadObjednavajiciOrg = $('#objednavajici-org-select'), typeaheadNovyKlientPrijmeni = $('#new-klient-prijmeni');

    //serialy
    setupTypeahead(typeaheadSerialy, 'serialy', loadRemoteData('&ajax=serialy', false), 1, true, "value");
    onTypeaheadSelected(typeaheadSerialy, $('input[name=id_serial]'), $('#serial-id'), loadZajezdySelector, 'https://slantour.cz/admin/serial.php?typ=serial&pozadavek=edit&id_serial=');
    //agentury
    setupTypeahead(typeaheadAgentury, 'agentury', loadRemoteData('&ajax=agentury', false), 1, true, "value");
    onTypeaheadSelected(typeaheadAgentury, $('input[name=agentura]'), $('#id_agentura'), null, 'https://slantour.cz/admin/organizace.php?typ=organizace&pozadavek=edit&id_organizace=');
    //organizace
    setupTypeahead(typeaheadObjednavajiciOrg, 'organizace', loadRemoteData('&ajax=agentury', false), 1, true, "value");
    onTypeaheadSelected(typeaheadObjednavajiciOrg, $('input[name=organizace]'), $('#id_organizace'), null, 'https://slantour.cz/admin/organizace.php?typ=organizace&pozadavek=edit&id_organizace=');
    //data pro ucastniky a objednavajiciho
    osobyData = loadRemoteData('&ajax=osoby', true);
    //novy klient prijmeni
    setupTypeahead(typeaheadNovyKlientPrijmeni, 'novy-klient', osobyData, 2, false, "prijmeni");
}

/**
 * Pricita / odcita pocet osob
 * @param mode
 */
function pocetOsobPlusMinusOne(mode) {
    var pocetOsob = $("#pocet-osob");
    var pocetOsobHidden = $("input[name=pocet_osob]");
    if (mode == 'plus') {
        var pocetPlusOne = parseInt(pocetOsob.html()) + 1;
        pocetOsob.html(pocetPlusOne);
        pocetOsobHidden.val(pocetPlusOne);
    } else if (mode == 'minus') {
        var pocetMinusOne = parseInt(pocetOsob.html()) - 1;
        pocetOsob.html(pocetMinusOne);
        pocetOsobHidden.val(pocetMinusOne);
    }
    changeProvize();
}

function addUcastnik(status) {
    if (status == "neulozen") {
        showStatus(false, "Chyba, klient neuložen");
        return;
    }

    var allRows = $('.value_row.ucastnik_row [id^=ucastnik-select-]');
    var wrapper = ('#ucastniciWrapper');
    var nextId = allRows.length;
    var frmKlient = $('#frm-client');
    var idKlient = frmKlient.find("input[name=klient_id]").val();
    var jmenoPrijmeniKlient = frmKlient.find('input[name=jmeno]').val() + " " + frmKlient.find('input[name=prijmeni]').val();
    var href = 'https://slantour.cz/admin/klienti.php?typ=klient&pozadavek=edit&id_klient=' + idKlient;

    //create new row and fill it with data
    var newRow = $("<div class='value_row ucastnik_row'><span class='del-input remove-row offset-right-10'><img width='10' src='./img/delete-cross.png'></span><a tabindex='-1' target='_blank' class='valign-middle offset-right-10' href='" + href + "' id='ucastnik-id-" + nextId + "'>[ " + idKlient + " ]</a><span class='ib-display inputText valign-middle' id='ucastnik-select-" + nextId + "'>" + jmenoPrijmeniKlient + "</span><input type='hidden' name='ucastnik-" + nextId + "' value='" + idKlient + "' /></div>");

    //add new row
    newRow.appendTo(wrapper);

    //setup remove button
    var newRemoveRow = newRow.find('.remove-row');
    newRemoveRow.on('click', function () {
        //lower id of all following attr by 1
        var followingSiblings = $(this).parent().nextAll();
        followingSiblings.find('[id^=ucastnik-select-]').each(function () {
            var actualId = $(this).attr('id').split('-')[2];
            $(this).attr('id', 'ucastnik-select-' + (actualId - 1));
        });
        followingSiblings.find('input[name^=ucastnik-]').each(function () {
            var actualId = $(this).attr('name').split('-')[1];
            $(this).attr('name', 'ucastnik-' + (actualId - 1));
        });
        followingSiblings.find('[id^=ucastnik-id-]').each(function () {
            var actualId = $(this).attr('id').split('-')[2];
            $(this).attr('id', 'ucastnik-id-' + (actualId - 1));
        });

        pocetOsobPlusMinusOne('minus');

        $(this).parent().remove();
    });

    //plus 1 person to order
    pocetOsobPlusMinusOne('plus');

    //set status and remove form data
    if (status == "nevytvaren") {
        showStatus(true, "Existujici klient pøidán jako úèastník");
    } else if (status == "ulozen") {
        showStatus(true, "Klient vytvoøen a pøidán jako úèastník");

        //reinit typeahead (dataset changed)
        var newKlientPrijmeni = $('#new-klient-prijmeni');
        osobyData = loadRemoteData('&ajax=osoby', true);
        newKlientPrijmeni.typeahead('destroy');
        setupTypeahead(newKlientPrijmeni, 'novy-klient', osobyData, 2, false, "prijmeni");

    } else if (status == "neulozen") {
        showStatus(false, "Chyba, klient neuložen");
    }
}

function addObjednavajici(status) {
    if (status == "neulozen") {
        showStatus(false, "Chyba, klient neuložen");
        return;
    }

    var frmKlient = $('#frm-client');
    var idKlient = frmKlient.find("input[name=klient_id]").val();
    var jmenoPrijmeniKlient = frmKlient.find('input[name=jmeno]').val() + " " + frmKlient.find('input[name=prijmeni]').val();
    var href = 'https://slantour.cz/admin/klienti.php?typ=klient&pozadavek=edit&id_klient=' + idKlient;
    var objednavajiciId = $('#objednavajici-id');

    //add objednavajici
    $('#objednavajici-select').html(jmenoPrijmeniKlient);
    objednavajiciId.prop('href', href);
    objednavajiciId.html('[ ' + idKlient + ' ]');
    $('input[name=id_objednavajici]').val(idKlient);

    //set status and remove form data
    if (status == "nevytvaren") {
        showStatus(true, "Existujici klient pøidán jako objednávající");
    } else if (status == "ulozen") {
        showStatus(true, "Klient vytvoøen a pøidán jako objednávající");

        //reinit typeahead (dataset changed)
        var newKlientPrijmeni = $('#new-klient-prijmeni');
        osobyData = loadRemoteData('&ajax=osoby', true);
        newKlientPrijmeni.typeahead('destroy');
        setupTypeahead(newKlientPrijmeni, 'novy-klient', osobyData, 2, false, "prijmeni");

    }
}

function changePocetNoci(termin) {
    var durationDays = 0;

    if (termin != null) {
        var rangeArr = termin.split(' - ');
        var terminOd = moment(rangeArr[0], "DD.MM. YYYY");
        var terminDo = moment(rangeArr[1], "DD.MM. YYYY");
        durationDays = Math.ceil(moment.duration(terminDo.diff(terminOd)).asDays());
    }

    $('#pocet-noci').html(durationDays);
    $('input[name=pocet_noci]').val(durationDays);
    changeProvize();
}

function initEvents() {
    //add sluzba
    $('#sluzba-add').on('click', function () {
        //pokud neni vybrany zajezd - nefunguju
        if ($("#zajezdy-select").val() == 0) {
            alert("Nejprve vyber zájezd.");
            return false;
        }

        var allRows = $('.value_row.sluzba_row [name^=new-sluzba-name-]');
        var wrapper = ('#sluzbyWrapper');
        var nextId = allRows.length;
        var newRow = $("<div class='value_row sluzba_row'>" +
            "<input type='text' class='smallNumber' value='0' name='new-sluzba-pocet-" + nextId + "' />" +
            "<input type='text' class='medNumber' placeholder='cena' name='new-sluzba-price-" + nextId + "' />" +
            "<input type='text' class='inputText' placeholder='název' name='new-sluzba-name-" + nextId + "' />" +
            "<span class='del-input remove-row'><img width='10' src='./img/delete-cross.png'></span>" +
            "</div>");

        //add new content
        newRow.appendTo(wrapper);

        //setup remove button
        var newRemoveRow = newRow.find('.remove-row');
        newRemoveRow.on('click', function () {
            //lower all following attr by 1
            var followingSiblings = $(this).parent().nextAll();
            followingSiblings.find('[name^=new-sluzba-pocet-]').each(function () {
                var actualId = $(this).attr('name').split('-')[3];
                $(this).attr('name', 'new-sluzba-pocet-' + (actualId - 1));
            });
            followingSiblings.find('[name^=new-sluzba-price-]').each(function () {
                var actualId = $(this).attr('name').split('-')[3];
                $(this).attr('name', 'new-sluzba-price-' + (actualId - 1));
            });
            followingSiblings.find('[name^=new-sluzba-name-]').each(function () {
                var actualId = $(this).attr('name').split('-')[3];
                $(this).attr('name', 'new-sluzba-name-' + (actualId - 1));
            });
            $(this).parent().remove();
        });

        return false;
    });

    //add sleva
    $('#sleva-add').on('click', function () {
        //pokud neni vybrany zajezd - nefunguju
        if ($("#zajezdy-select").val() == 0) {
            alert("Nejprve vyber zájezd.");
            return false;
        }

        var allRows = $('.value_row.sleva_row [name^=new-sleva-name-]');
        var wrapper = ('#slevyWrapper');
        var nextId = allRows.length;
        var newRow = $("<div class='value_row sleva_row'>" +
            "<select name='new-sleva-type-" + nextId + "'><option value='type_kc'>Kè</option><option value='type_kc_os'>Kè/os</option><option value='type_procento'>%</option></select>" +
            "<input type='text' class='medNumber' placeholder='èástka' name='new-sleva-price-" + nextId + "' />* " +
            "<input type='text' class='inputText' placeholder='název' name='new-sleva-name-" + nextId + "' />" +
            "<span class='del-input remove-row'><img width='10' src='./img/delete-cross.png'></span>" +
            "</div>");

        //add new content
        newRow.appendTo(wrapper);

        //setup remove button
        var newRemoveRow = newRow.find('.remove-row');
        newRemoveRow.on('click', function () {
            //lower id of all following attr by 1
            var followingSiblings = $(this).parent().nextAll();
            followingSiblings.find('[name^=new-sleva-type-]').each(function () {
                var actualId = $(this).attr('name').split('-')[3];
                $(this).attr('name', 'new-sleva-type-' + (actualId - 1));
            });
            followingSiblings.find('[name^=new-sleva-price-]').each(function () {
                var actualId = $(this).attr('name').split('-')[3];
                $(this).attr('name', 'new-sleva-price-' + (actualId - 1));
            });
            followingSiblings.find('[name^=new-sleva-name-]').each(function () {
                var actualId = $(this).attr('name').split('-')[3];
                $(this).attr('name', 'new-sleva-name-' + (actualId - 1));
            });

            $(this).parent().remove();
        });

        return false;
    });

    //add objednavajici
    $('#add-klient-objednavajici').on('click', function () {
        var frmKlient = $("#frm-client");

        //klient doesnt exist > create new
        if (frmKlient.find("input[name=klient_id]").val() == "") {
            $.ajax({
                type: "POST",
                url: "klienti.php?typ=klient&pozadavek=create_ajax",
                data: frmKlient.serialize(),
                async: false,
                success: function (result) {
                    //v pripade chyby vypise celou stranku - nmeni tu zadny json, nic, takze result.length bude o dost vetsi nez pokud bude operace uspesna
                    if (result && result != "" && result.length < 10) {
                        setExistingKlientId(result);
                        addObjednavajici("ulozen");
                        $('#objednavajici-ucastnikem').prop('checked', true).change();
                    } else {
                        addObjednavajici("neulozen");
                    }
                },
                error: function () {
                    addObjednavajici("neulozen");
                }
            });
        } else {
            addObjednavajici("nevytvaren");
            $('#objednavajici-ucastnikem').prop('checked', true);
            if ($('#ucastnikObjednavajiciWrapper').length == 0)
                pocetOsobPlusMinusOne('plus');
            toggleObjednavajiciAsUcastnik(true);
        }

        return false;
    });

    //add ucastnik
    $('#add-klient-ucastnik').on('click', function () {
        var frmKlient = $("#frm-client");

        //klient doesnt exist > create new
        if (frmKlient.find("input[name=klient_id]").val() == "") {
            $.ajax({
                type: "POST",
                url: "klienti.php?typ=klient&pozadavek=create_ajax",
                data: frmKlient.serialize(),
                async: false,
                success: function (result) {
                    if (result && result != "" && result.length < 10) {
                        setExistingKlientId(result);
                        addUcastnik("ulozen");
                    } else {
                        addUcastnik("neulozen");
                    }
                },
                error: function () {
                    addUcastnik("neulozen");
                }
            });
        } else {
            addUcastnik("nevytvaren");
        }

        return false;
    });

    //load data when zajezd is picked
    $('#zajezdy-select').on('change', function () {
        loadTermin($(this).val());
        loadSpecialRanges($(this).val());
        loadSluzby($(this).val());
        loadSlevy($(this).val());
        loadKlientskeSlevySerial($('[name=id_serial]').val());
        loadKlientskeSlevyZajezd($(this).val());
    });

    $('#agentury-select').on('change', function(){
        changeProvize();
    });
    //change person count if objednavajici is/is not ucastnik
    $('#objednavajici-ucastnikem').on('change', function () {
        if ($(this).is(':checked')) {
            pocetOsobPlusMinusOne('plus');
        } else {
            pocetOsobPlusMinusOne('minus');
        }
        toggleObjednavajiciAsUcastnik($(this).is(':checked'));
    });

    var frmKlient = $("#frm-client");
    $('#new-klient-prijmeni').on('typeahead:selected', function (e, data) {
        $(this).blur();
        setExistingKlientId(data.id);
        frmKlient.find("input[name=prijmeni]").val(data.prijmeni);
        frmKlient.find("input[name=jmeno]").val(data.jmeno);
        frmKlient.find("input[name=titul]").val(data.titul);
        frmKlient.find("input[name=datum_narozeni]").val(data.datumNarozeni);
        frmKlient.find("input[name=rodne_cislo]").val(data.rodneCislo);
        frmKlient.find("input[name=email]").val(data.email);
        frmKlient.find("input[name=telefon]").val(data.telefon);
        frmKlient.find("input[name=cislo_op]").val(data.cisloOP);
        frmKlient.find("input[name=cislo_pasu]").val(data.cisloPasu);
        frmKlient.find("input[name=mesto]").val(data.mesto);
        frmKlient.find("input[name=ulice]").val(data.ulice);
        frmKlient.find("input[name=psc]").val(data.psc);
    });

    //delete id_klient filled with typeahead if user changes first or last name
    var frmClient = $('#frm-client');
    frmClient.find('input[type=text]').each(function () {
        $(this).on('change', function () {
            setExistingKlientId("");
        });
    });
}

/**
 *  Zobrazi nebo schova objednavajiciho jako ucastnika zajezdu
 */
function toggleObjednavajiciAsUcastnik(isUcastnik) {
    if (isUcastnik) {
        var ucastniciWrapper = $('#ucastniciWrapper');
        ucastniciWrapper.find('#ucastnikObjednavajiciWrapper').remove();
        ucastniciWrapper.prepend($('<div id="ucastnikObjednavajiciWrapper">' + $('#objednavajiciWrapper').html() + '</div>'));
    } else {
        $('#ucastnikObjednavajiciWrapper').remove();
    }
}

function setExistingKlientId(id) {
    var frmKlient = $('#frm-client');
    frmKlient.find("input[name=klient_id]").val(id);
    //make existing / new client status visible to user
    if (id == "") {
        frmKlient.find("input[type=text]").each(function () {
            $(this).removeClass('inp-yellow');
        });
    } else {
        frmKlient.find("input[type=text]").each(function () {
            $(this).addClass('inp-yellow');
        });
    }
}

function showStatus(status, msg) {
    var clientStatus = $('#client-status');
    clientStatus.hide();
    clientStatus.removeClass('red').removeClass('green');

    if (status) {
        clientStatus.addClass('green');
        clientStatus.html(msg);
        $('#frm-client').find('input[type=text], input[type=hidden]').each(function () {
            $(this).removeClass('inp-yellow').val('');
        });
    } else {
        clientStatus.addClass('red');
        clientStatus.html(msg);
    }
    clientStatus.slideDown(300);
}

function changeProvize() {
    $.ajax({
        type: 'POST',
        url: 'https://' + window.location.host + '/admin/rezervace.php?typ=rezervace&pozadavek=ajax_provize',
        data: $('#frm-objednavka').serialize(),
        success: function (result) {
            $('input[name=sumaprovize]').val(result);
        },
        error: function (result) {
            $('input[name=sumaprovize]').val(0);
        }
    });
}

