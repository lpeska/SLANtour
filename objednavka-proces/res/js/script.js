$(document).ready(function () {
    var btnS1Vybrat = $('#btn-s1-vybrat'), btnS2Vybrat = $('#btn-s2-vybrat'), btnS3Vybrat = $('#btn-s3-vybrat');
    var btnS4Vybrat = $('#btn-s4-objednat');
    var iArrNumber = $('.i-num'), iDatumNarDay = $('#datum-narozeni-day'), iDatumNarYear = $('#datum-narozeni-year');
    var iTelefon = $('#telefon'), iPsc = $('#psc'), iOsobniUdaje = $('#frm-osobni-udaje input, #frm-osobni-udaje select');
    var iTelefonPre = $('#telefon-pre'), body = $('body'), ucastnik = $('#ucastnik');

    //datepicker
    loadSpecialRanges(getUrlParameter('zajezd_id'));

    //version
    var bInfo = getBrowserInfo();
    if (bInfo.browser == 'MSIE') {
        if (bInfo.browserMajorVersion < 11) {
            bInfo.browserMajorVersion = bInfo.browserMajorVersion <= 7 ? 7 : bInfo.browserMajorVersion;
            document.documentElement.className = 'ie' + bInfo.browserMajorVersion;
        }
    }

    //+ / - ciselne selectory
    iArrNumber.each(function () {
        setupNumberInputs($(this));
        recalcPrice($(this));
    });

    //btns
    btnS1Vybrat.on("click", function () {
        return valZajezdUdaje();
    });
    btnS2Vybrat.on("click", function () {
        return valKontaktniUdaje();
    });
    btnS3Vybrat.on("click", function () {
        return sendForm('frm-platba');
    });
    btnS4Vybrat.on("click", function () {
        return sendForm('frm-souhrn');
    });
    $("[id^=jmeno-], [id^=prijmeni-]").on("focus", function () {
        var id = $(this).prop("id");
        var number = id.split('-')[1];
        var ucastnikOptional = $("#ucastnik-optional-" + number);
        var ucastnikOptionalOthers = $("[id^=ucastnik-optional-]").not("#ucastnik-optional-" + number);
        ucastnikOptional.slideDown(80);
        ucastnikOptionalOthers.slideUp(80);
    });

    //validance - osob. udaje
    $('#jmeno').on("blur", function () {
        valEmpty($(this), $('#err-jmeno'));
    });
    $('#prijmeni').on("blur", function () {
        valEmpty($(this), $('#err-prijmeni'));
    });
    $('#mesto').on("blur", function () {
        valEmpty($(this), $('#err-mesto'));
    });
    $('.uc-jmeno:visible').on("blur", function () {
        valEmpty($(this), $('#err-uc-' + $(this).prop("id").split("-")[1]));
    });
    $('.uc-prijmeni:visible').on("blur", function () {
        valEmpty($(this), $('#err-uc-' + $(this).prop("id").split("-")[1]));
    });
    $('#email').on("blur", function () {
        valEmail($(this));
    });
    iTelefon.on("blur", function () {
        //varovani jen pokud probehla validace
        if (valPhone($(this))) {
            valCzechPhone($(this));
        }
    });
    iDatumNarDay.on("blur", function () {
        valDatumNarDay($(this));
    });
    $('#datum-narozeni-month').on("blur", function () {
        valDatumNarMonth($(this));
    });
    iDatumNarYear.on("blur", function () {
        valDatumNarYear($(this));
    });
    iPsc.on("blur", function () {
        valPsc($(this));
    });

    //filter number inputs - fakt. udaje
    iDatumNarDay.on("keypress", {maxLength: 2}, keyNumberFilter);
    iDatumNarYear.on("keypress", {maxLength: 4}, keyNumberFilter);
    iTelefon.on("keypress", {maxLength: 14}, keyNumberFilter);
    iPsc.on("keypress", {maxLength: 5}, keyNumberFilter);

    //odstranit chybovou hlasku pokud je na elementu focus
    iOsobniUdaje.each(function () {
        $(this).on("focus", function () {
            $(this).removeClass('val-border');
        });
    });

    //vyber vlajku podle vlozene predvolby (ceska, pokud je predvolba neznama)
    iTelefonPre.on("keyup", {inEl: iTelefonPre}, changeFlag);
    //zmen vlajku i pri nacteni stranky (kdyz se vyplni udaje pri navratu z dalsiho kroku)
    if (iTelefonPre.length > 0)
        changeFlag(iTelefonPre);

    //otevreni selectoru predvoleb
    $('#select-flag, .flag').on("click", function (e) {
        e.stopPropagation();
        $('#country-selector').slideToggle(80, slideComplete);
    });
    //veber predvolby ze selectoru
    $("#country-selector").find("li").on("click", function (e) {
        e.stopPropagation();
        var predvolba = $(this).find('input[type=hidden]').val();
        iTelefonPre.val(predvolba);
        changeFlag(iTelefonPre);
        $('#country-selector').slideToggle(80, slideComplete);
    });

    //jsem ucastnikem zajezdu
    ucastnik.on("change", function () {
        jsemUcastnikem($(this));
    });
    jsemUcastnikem(ucastnik);

    //vyber platebni metody - zobrazeni info o metode
    $("#list-platba").find("input[type=radio]").on("change", showInfo);

    //warning buttons
    $("#warning-btn-yes").on('click', function() {
        $("#frm-zajezd").submit();
    });
    $("#warning-btn-no").on('click', function() {
        $('.validation-skip').remove();
        $(this).parent().remove();
    });
});

/**
 * Nacte vyprodane terminy/blackdays v json formatu pro daterangepicker
 */
function loadSpecialRanges(zajezdId) {
    $.ajax({
        url: "./index.php?page=ajax-sr&zajezd-id=" + zajezdId
    }).done(function (response) {
        initDatePicker(response);
    }).error(function (response) {
        console.log(response);
    });
}

function initDatePicker(specialRanges) {
    //pokud nemam zadne specialRanges, musim prevest "" na null
    specialRanges = specialRanges ? specialRanges : null;
    var datePicker = $('#datepicker'), calendarIco = $('.calendar-ico'), frmZajezd = $('#frm-zajezd'), body = $('body');
    var dateRangePickerOptions = {
        showDropdowns: true,
        format: 'DD.MM. YYYY',
        specialRanges: $.parseJSON(specialRanges),
        locale: {
            applyLabel: 'Potvrdit',
            cancelLabel: 'Zrušit',
            fromLabel: 'Termín od',
            toLabel: 'Termín do',
            daysOfWeek: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pá', 'So'],
            monthNames: ['leden', 'únor', 'březen', 'duben', 'květen', 'červen', 'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec'],
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
            frmZajezd.prop("action", "index.php?page=zajezd&action=zajezd-termin-changed");
            frmZajezd.submit();
        });
    }
}

/**
 * Script z http://stackoverflow.com/questions/17907445/how-to-detect-ie11 na detekovani prohlizece
 * @returns {*|window|window}
 */
function getBrowserInfo() {
    //browser
    var nAgt = navigator.userAgent;
    var browser = navigator.appName;
    var version = '' + parseFloat(navigator.appVersion);
    var majorVersion, nameOffset, verOffset, ix;

    // Opera
    if ((verOffset = nAgt.indexOf('Opera')) != -1) {
        browser = 'Opera';
        version = nAgt.substring(verOffset + 6);
        if ((verOffset = nAgt.indexOf('Version')) != -1) {
            version = nAgt.substring(verOffset + 8);
        }
    }
    // MSIE
    else if ((verOffset = nAgt.indexOf('MSIE')) != -1) {
        browser = 'MSIE';
        version = nAgt.substring(verOffset + 5);
    }

    //IE 11 no longer identifies itself as MS IE, so trap it
    //http://stackoverflow.com/questions/17907445/how-to-detect-ie11
    else if ((browser == 'Netscape') && (nAgt.indexOf('Trident/') != -1)) {

        browser = 'MSIE';
        version = nAgt.substring(verOffset + 5);
        if ((verOffset = nAgt.indexOf('rv:')) != -1) {
            version = nAgt.substring(verOffset + 3);
        }

    }

    // Chrome
    else if ((verOffset = nAgt.indexOf('Chrome')) != -1) {
        browser = 'Chrome';
        version = nAgt.substring(verOffset + 7);
    }
    // Safari
    else if ((verOffset = nAgt.indexOf('Safari')) != -1) {
        browser = 'Safari';
        version = nAgt.substring(verOffset + 7);
        if ((verOffset = nAgt.indexOf('Version')) != -1) {
            version = nAgt.substring(verOffset + 8);
        }

        // Chrome on iPad identifies itself as Safari. Actual results do not match what Google claims
        //  at: https://developers.google.com/chrome/mobile/docs/user-agent?hl=ja
        //  No mention of chrome in the user agent string. However it does mention CriOS, which presumably
        //  can be keyed on to detect it.
        if (nAgt.indexOf('CriOS') != -1) {
            //Chrome on iPad spoofing Safari...correct it.
            browser = 'Chrome';
            //Don't believe there is a way to grab the accurate version number, so leaving that for now.
        }
    }
    // Firefox
    else if ((verOffset = nAgt.indexOf('Firefox')) != -1) {
        browser = 'FF';
        version = nAgt.substring(verOffset + 8);
    }
    // Other browsers
    else if ((nameOffset = nAgt.lastIndexOf(' ') + 1) < (verOffset = nAgt.lastIndexOf('/'))) {
        browser = nAgt.substring(nameOffset, verOffset);
        version = nAgt.substring(verOffset + 1);
        if (browser.toLowerCase() == browser.toUpperCase()) {
            browser = navigator.appName;
        }
    }
    // trim the version string
    if ((ix = version.indexOf(';')) != -1) version = version.substring(0, ix);
    if ((ix = version.indexOf(' ')) != -1) version = version.substring(0, ix);
    if ((ix = version.indexOf(')')) != -1) version = version.substring(0, ix);

    majorVersion = parseInt('' + version, 10);
    if (isNaN(majorVersion)) {
        version = '' + parseFloat(navigator.appVersion);
        majorVersion = parseInt(navigator.appVersion, 10);
    }

    return {
        browser: browser,
        browserVersion: version,
        browserMajorVersion: majorVersion
    };
}

/**
 * Zobrazi / schova ucastnika zajezdu
 */
function jsemUcastnikem(el) {
    if (el.is(":checked")) {
        //pridat udaje o objednavateli - ucastniku zajezdu
        $("#objednavatel_dalsi_udaje").show();
        //odebrat posledniho ucastnika zajezdu        
        $(".ucastnik").last().hide();
        //schovat cely fieldset ucastniku
        if (!$(".ucastnik:visible").length) {
            $("#fs-ucastnici").hide();
        }
    } else {
        //odebrat udaje o objednavateli - ucastniku zajezdu
        $("#objednavatel_dalsi_udaje").hide();
        //pridat posledniho ucastnika zajezdu
        $(".ucastnik").last().show();
        //zobrazit cely fieldset ucastniku
        $("#fs-ucastnici").show();
    }

    //pokud nemam zadne dalsi ucastniky, schovam i fieldset
}

/**
 * Zobrazi informace o platbe
 */
function showInfo() {
    var id = 'info-' + $(this).attr("id");
    $("#platba-info").find("div").addClass("no-display");
    $("#" + id).removeClass("no-display");
}

/**
 * Zmeni vlajku tak, ze najde element, ktery ma byt na miste vlajky a zobrazi ho a schova puvodni element
 * @param el
 */
function changeFlag(el) {
    if (el.data.inEl)
        el = el.data.inEl;
    var elId = "flag-" + el.val().replace("+", "");
    var foundFlag = $('#' + elId);
    if (foundFlag.length !== 0) {
        $('.flag-active').removeClass("flag-active");
        foundFlag.addClass("flag-active");
    } else {
        $('.flag-active').removeClass("flag-active");
        $('#flag-420').addClass("flag-active");
    }
}

/**
 * Po rozjeti selektoru nastavi po kliku kamkoliv na strance schovani selektoru
 */
function slideComplete() {
    $('body').on("click", function () {
        $('#country-selector').hide();
    });
}

/**
 * Inicializuje eventy +/- widgetu, ktery je predan jako parametr
 * @param iNum
 */
function setupNumberInputs(iNum) {
    var input = iNum.find("input");
    var plus = iNum.find(".p-sign");
    var minus = iNum.find(".m-sign");

    //plus
    plus.on("click", function () {
        if (checkNumberInput(input, '+'))
            input.val(parseInt(input.val()) + 1);
        recalcPrice(iNum);
    });
    //minus
    minus.on("click", function () {
        if (checkNumberInput(input, '-'))
            input.val(parseInt(input.val()) - 1);
        recalcPrice(iNum);
    });
    //povol max 2 cisla
    input.on("keypress", {maxLength: 2}, keyNumberFilter);
    //spocitej cenu x pocet
    input.on("keyup", function () {
        recalcPrice(iNum);
    });
    //0 misto prazdneho pole, spocitej cenu x pocet
    input.on("blur", function () {
        if (input.val() == "") {
            input.val("0");
        }
        recalcPrice(iNum);
    });
}

/**
 * Prepocita veskere ceny vsech poli (jednotlivych sluzeb i celkove)
 */
function recalcPrice(iNum) {
    calcPrice(iNum);
    calcPriceCasoveSlevy();
    calcFullPrice();
}

/**
 * Vypocte cenu v jednom radku
 * @param iNum
 */
function calcPrice(iNum) {
    var eCount = iNum.find("input");
    var ePriceOne = iNum.parent().prev().prev().find(".price-one");
    var ePocetNoci = iNum.parent().prev().find(".pocet-noci");
    var ePriceFull = iNum.parent().next().next().find(".price-full");

    if (ePriceOne.length === 0)
        return;

    var priceOneInt = parseInt(ePriceOne.html().replace(/ /g, ''));
    var count = parseInt(eCount.val());
    var pocetNociInt = ePocetNoci.length == 0 ? 1 : parseInt(ePocetNoci.html());
    count = isNaN(count) ? 0 : count;
    var priceFull = priceOneInt * count * pocetNociInt + "";
    priceFull = formatPrice(priceFull);
    ePriceFull.html(priceFull);
}

/**
 * Vypocte cenu v sekci casovych slev
 */
function calcPriceCasoveSlevy() {
    var casoveSlevyEl = $('#casove-slevy');

    if (casoveSlevyEl.length === 0)
        return;

    var ePriceFull = casoveSlevyEl.find('.price-full');
    var ePriceOne = casoveSlevyEl.find('.price-one');
    var priceOneInt = parseInt(ePriceOne.html().replace(/[ -]/g, ''));
    var currency = casoveSlevyEl.find('.price-currency').html();
    var pocetOsob = $('input[name=pocet-osob]').val();
    var priceFull = 0;

    if(currency === '%') {
        var ePriceSluzbyEls = $('.order-sluzby').find('.price-full');
        var priceSluzbyTotal = 0;
        ePriceSluzbyEls.each(function() {
            priceSluzbyTotal += parseInt($(this).html().replace(/ /g, ''));
        });

        priceFull = Math.round(priceSluzbyTotal / 100 * priceOneInt);
        ePriceFull.html("-" + priceFull);
    } else {
        priceFull = pocetOsob * priceOneInt;
        ePriceFull.html("-" + priceFull);
    }
}

/**
 * Vypocte cenu celkem za cely zajezd
 */
function calcFullPrice() {
    var iArrPrice = $('.price-full');
    var totalPrice = 0;
    iArrPrice.each(function () {
        totalPrice += parseInt($(this).html().replace(/ /g, ''));
    });
    totalPrice += "";
    totalPrice = formatPrice(totalPrice);
    $('.price-total').html(totalPrice);
}

/**
 * Formatuje ceny do formatu 123 456
 * @param price
 * @returns {string|void}
 */
function formatPrice(price) {
    return price.replace(/./g, function (c, i, a) {
        return i && c !== "." && !((a.length - i) % 3) ? ' ' + c : c;
    });
}

/**
 * Zkontrol
 * @param el
 * @param op
 * @returns {boolean}
 */
function checkNumberInput(el, op) {
    if (isNaN(parseInt(el.val()))) {
        el.val("0");
        return false;
    }
    if (op == "-" && parseInt(el.val()) <= 0) {
        el.val("0");
        return false;
    }
    if (op == "+" && parseInt(el.val()) >= 20) {
        el.val("20");
        return false;
    }

    return true;
}

/**
 * Validuje kompletni kontaktni udaje
 * @returns {boolean}
 */
function valKontaktniUdaje() {
    valDatumNarDay($("#datum-narozeni-day"));
    valDatumNarMonth($("#datum-narozeni-month"));
    valDatumNarYear($("#datum-narozeni-year"));
    valEmail($("#email"));
    valPhone($("#telefon"));
    valEmpty($('#jmeno'), $('#err-jmeno'));
    valEmpty($('#prijmeni'), $('#err-prijmeni'));
    valEmpty($('#mesto'), $('#err-mesto'));
    valPsc($("#psc"));
    valCheckbox($("#souhlas"), $("#err-souhlas"));
    $('.uc-jmeno:visible').each(function () {
        valEmpty($(this), $('#err-uc-' + $(this).prop("id").split("-")[1]));
    });
    $('.uc-prijmeni:visible').each(function () {
        valEmpty($(this), $('#err-uc-' + $(this).prop("id").split("-")[1]));
    });

    return sendFormWithValidation('frm-osobni-udaje');
}

function valZajezdUdaje() {
    valTermin();
    valPocetOsob($("[name='pocet-osob']"));
    valSluzby();
    valOdjezd();

    return sendFormWithValidation('frm-zajezd');
}

/**
 * Odesle formular pokud nenajde zadny element, ktery znaci spatnou validaci, jinak zobrazi zpravu o chybe a odscroluje na zacatek stranky
 * @param id
 * @returns {boolean}
 */
function sendFormWithValidation(id) {
    if ($("[class^=val-err]:visible").length == 0) {
        return sendForm(id);
    } else {
        var valFail = $("#val-fail");
        valFail.removeClass("no-display");
        $('body,html').animate({
            scrollTop: 0
        }, 800);
        return false;
    }
}

/**
 * Zkontroluje zda lze schovat horni (calkovou) hlasku o spatne validaci a pokud ano, tak tak udela
 */
function hideValFail() {
    if ($("[class^=val-err]:visible").length == 0) {
        $('#val-fail').addClass("no-display");
    }
}

/**
 * Odesle formular s id
 * @param id
 * @returns {boolean}
 */
function sendForm(id) {
    var form = $('#' + id);
    form.submit();
    return false;
}

function valTermin() {
    var datePicker = $('#datepicker');
    var elErr = $('#datepicker-val');
    if (datePicker.length) {
        if (datePicker.val() == "") {
            elErr.removeClass('no-display');
            elErr.addClass('display-ib');
            return false;
        } else {
            elErr.removeClass('display-ib');
            elErr.addClass('no-display');
        }
    }
    return true;
}

function valPocetOsob(el) {
    var pocetOsob = parseInt(el.val());
    var elErr = $('#pocet-osob-val');
    if (pocetOsob && pocetOsob > 0) {
        elErr.removeClass('display-ib');
        elErr.addClass('no-display');
        return true;
    } else {
        elErr.removeClass('no-display');
        elErr.addClass('display-ib');
        return false;
    }
}

function valSluzby() {
    var tblSluzby = $('.order-sluzby');
    var tblLastMinute = $('.order-last-minute');
    var filledSluzby = 0, filledLastMinute = 0;
    var elLmSluzbyErr = $('#last-minute-sluzby-val');
    var elSluzbyErr = $('#sluzby-val');
    var elLmErr = $('#last-minute-val');

    if (tblSluzby.length != 0 && tblLastMinute.length != 0) { //mame sluzby i last minute
        filledSluzby = cntFilledNumberInputs(tblSluzby.find('input[type=text]'));
        filledLastMinute = cntFilledNumberInputs(tblLastMinute.find('input[type=text]'));
        if (filledSluzby > 0 || filledLastMinute > 0) { //val projde
            elLmSluzbyErr.removeClass('display-ib');
            elLmSluzbyErr.addClass('no-display');
        } else { //val neprojde
            elLmSluzbyErr.removeClass('no-display');
            elLmSluzbyErr.addClass('display-ib');
            return false;
        }
    } else if (tblSluzby.length != 0 && tblLastMinute.length == 0) { //mame jen sluzby
        filledSluzby = cntFilledNumberInputs(tblSluzby.find('input[type=text]'));
        if (filledSluzby > 0) { //validace projde
            elSluzbyErr.removeClass('display-ib');
            elSluzbyErr.addClass('no-display');
        } else { //validace neprojde - oznacit sluzby
            elSluzbyErr.removeClass('no-display');
            elSluzbyErr.addClass('display-ib');
            return false;
        }
    } else if (tblSluzby.length == 0 && tblLastMinute.length != 0) { //mame jen last minute
        filledLastMinute = cntFilledNumberInputs(tblLastMinute.find('input[type=text]'));
        if (filledLastMinute > 0) { //validace projde
            elLmErr.removeClass('display-ib');
            elLmErr.addClass('no-display');
        } else { //validace neprojde - oznacit lastminute (single)
            elLmErr.removeClass('no-display');
            elLmErr.addClass('display-ib');
            return false;
        }
    }

    return true;
}

function valOdjezd() {
    var tblOdjezd = $('.order-odjezd');
    var elErr = $('#odjezd-val');
    var typDopravy = $('#typDopravy');
    

    //pokud je odjezdove misto pritomno, navic zjistujeme pouze pro ciste autokarove zajezdy
    if (tblOdjezd.length != 0 && typDopravy.val() == 2) {
        var filledInputs = cntFilledNumberInputs(tblOdjezd.find('input[type=text]'));
        if (filledInputs > 0) {
            elErr.removeClass('display-ib');
            elErr.addClass('no-display');
        } else {
            elErr.removeClass('no-display');
            elErr.addClass('display-ib');
            return false;
        }
    }

    return true;
}

function cntFilledNumberInputs(elements) {
    var filledInputs = 0;
    elements.each(function () {
        var input = parseInt($(this).val());
        if (input > 0) filledInputs++;
    });
    return filledInputs;
}

/**
 * Validuje prazdna pole
 * @param el element, ktery je validovan
 * @param lastRowEl element, za ktery se prida varovna hlaska
 * @param groupId skupinove id - pokud chci jednu varovnou hlasku pro vice poli
 * @param cls trida, ktera meni vzhled valdiacni zpravy a okraj validovaneho pole
 * @returns {boolean}
 */
function valEmpty(el, errEl) {
    var txt = el.val();

    if (txt == "") {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    } else {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    }
    hideValFail();
}

/**
 * Validuje email
 * @param el
 * @returns {*}
 */
function valEmail(el) {
    var errEl = $('#err-email');
    var reg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    if (reg.test(el.val())) {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    } else {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    }
    hideValFail();
}

/**
 * Validuje telefon
 * @param el
 * @returns {*}
 */
function valPhone(el) {
    var errEl = $('#err-telefon');
    var reg = /^\d{5}|\d{6}|\d{7}|\d{8}|\d{9}|\d{10}|\d{11}|\d{12}|\d{13}|\d{14}$/;

    if (reg.test(el.val())) {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
        return true;
    } else {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    }

    hideValFail();
}

/**
 * Validuje telefon na zaklade vyplnene predvolby
 * @param el
 * @returns {*}
 */
function valCzechPhone(el) {
    var predvolba = $('#telefon-pre').val();
    var warnEl = $('#warn-telefon');
    if (predvolba == null || predvolba == '' || predvolba != '+420')
        return;

    var reg = /^\d{9}$/;
    if (reg.test(el.val())) {
        warnEl.removeClass("display-ib");
        warnEl.removeClass("no-display");
        warnEl.addClass("no-display");
        el.removeClass("val-err-border");
    } else {
        warnEl.removeClass("no-display");
        el.addClass("val-err-border");
    }
}

/**
 * Validuje PSC
 * @param el
 * @returns {*}
 */
function valPsc(el) {
    var errEl = $('#err-psc');
    var reg = /^\d{5}$/;

    if (el.val() != "" && !reg.test(el.val())) {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    } else {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    }
}

/**
 * Validuje zda je checkbox zaskrtnuty
 */
function valCheckbox(el, errEl) {
    if(!el.is(':checked')) {
        errEl.removeClass('no-display');
        el.addClass('val-err-border');
    } else {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    }
    hideValFail();
}

/**
 * Validuje den narozeni
 * @param el
 * @returns {*}
 */
function valDatumNarDay(el) {
    var errEl = $('#err-datum-nar');
    var reg = /^(([0]?[1-9])|([1-2][0-9])|(3[01]))$/;

    if (el.val() == 0) {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    } else {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    }
    hideValFail();
}

/**
 * Validuje mesic narozeni
 * @param el
 * @returns {*}
 */
function valDatumNarMonth(el) {
    var errEl = $('#err-datum-nar');

    if (el.val() == 0) {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    } else {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    }
    hideValFail();
}

/**
 * Validuje rok narozeni
 * @param el
 * @returns {*}
 */
function valDatumNarYear(el) {
    var errEl = $('#err-datum-nar');
    var reg = /^(192[5-9]|19[3-9]\d|200\d|201[0-9]|202[0-9])$/;

    if (reg.test(el.val())) {
        errEl.removeClass("display-ib");
        errEl.removeClass("no-display");
        errEl.addClass("no-display");
        el.removeClass("val-err-border");
    } else {
        errEl.removeClass("no-display");
        el.addClass("val-err-border");
    }
    hideValFail();
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

/**
 * Vrati url parametr
 * @param sParam
 * @returns {*}
 */
function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) {
            return sParameterName[1];
        }
    }
}