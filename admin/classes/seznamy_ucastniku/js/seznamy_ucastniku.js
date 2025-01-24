/**
 * Spusti se po nacteni dokumentu
 */
$(document).ready(function () {
    var formFilterSerialy = $('#form-filter-serialy');
    var formFilterZajezdy = $('#form-filter-zajezdy');

    //tlacitka filtru
    $('#btn-filter-serialy').click(function () {
        formFilterSerialy.submit();
        return false;
    });
    $('#btn-filter-zajezdy').click(function () {
        formFilterZajezdy.submit();
        return false;
    });

    //potvrd filtry enterem
    formFilterSerialy.find('input').keypress(function (event) {
        if (event.keyCode == 13)
            formFilterSerialy.submit();
    });
    formFilterZajezdy.find('input').keypress(function (event) {
        if (event.keyCode == 13)
            formFilterZajezdy.submit();
    });

    $('#filter-nazev').focus();

    //tlacitko prechod na vyber zajezdu s hlaskou pokud nejsou zadne serialy vybrane
    $('#btn-vyber-zajezdu').click(function () {
        var frmSerialy = $('#form-serialy');
        if (frmSerialy.find('input[type=checkbox]:checked').length > 0) {
            frmSerialy.submit();
        } else {
            alert("Žádné vybrané seriály!");
        }
        return false;
    });

    //tlacitko prechod na vyber ucastniku s hlaskou pokud nejsou zadne zajezdy vybrane
    $('#btn-vyber-ucastniku').click(function () {
        var frmZajezdy = $('#form-zajezdy');
        if (frmZajezdy.find('input[type=checkbox]:checked').length > 0) {
            frmZajezdy.submit();
        } else {
            alert("Žádné vybrané zájezdy!");
        }
        return false;
    });

    //tlacitko prechod na vygenerovani tiskove sestavy
    $('#btn-generuj-pdf').click(function () {
        var frmUcastnici = $('#form-ucastnici');
        frmUcastnici.submit();

        //reloadovat page hned po submitu formulare do target="_blank" buhvi proc nejde
        setTimeout(function () {
            window.location.reload();
        }, 1000);

        return false;
    });

    //ovladani formularu pouze s jednim vyberem
    $('.select-single-serial').click(function () {
        return selectSingle($(this), "form-serialy");
    });
    $('.select-single-zajezd').click(function () {
        return selectSingle($(this), "form-zajezdy");
    });

    //checkboxy filtru ucastnku
    initCheckbox();

    //emaily
    $('#btn-add-email').click(function () {
        addEmail();
        return false;
    });
    $('#btn-send-pdf').click(function () {
        sendPdfEmails();
        return false;
    });

    //ucastnici selektor pro ukladani nastaveni checkboxu
    var cbFilterIcastniciSetup = $('#cbFilterUcastniciSetup');
    cbFilterIcastniciSetup.change(function () {
        setFilterSetup($(this).val());
    });
    cbFilterIcastniciSetup.val('da-te-na').change();
    
    
    //filtr ucastniku - checkboxy u objednavek
    $('.cb-deselect-obj').change(function () {
        var objednavkaID = $(this).val();
        var checked = $(this).prop('checked');
        $("#check-all-ucastnici-objednavka-"+objednavkaID).prop('checked',checked);
        $(".ucastnik_objednavky_"+objednavkaID).prop('checked',checked);
    });
    
    $('.check-all-ucastnici-objednavka').change(function () {
        var objednavkaID = $(this).val();
        var checked = $(this).prop('checked');
        $(".ucastnik_objednavky_"+objednavkaID).prop('checked',checked);                
    });
});

/**
 * Inicializuje checkboxy u filtru ucasntiku
 */
function initCheckbox() {
    var inputCbFZaj = $("input[name^='cb-f-zaj-']");
    var inputCbFObj = $("input[name^='cb-f-obj-']");
    var inputCbFSl = $("input[name^='cb-f-sl-']");

    inputCbFZaj.click(function () {
        showHideTblCol($(this), 'removable-zaj');
    });
    inputCbFObj.click(function () {
        showHideTblCol($(this), 'removable-obj');
    });
    inputCbFSl.click(function () {
        showHideTblRow($(this), 'removable-sl');
    });

    //jeste musim vsechny sloupce po nacteni stranky projet a nastavit jim defaultni tridu, protoze se mi uklada nastaveni checkboxu
    inputCbFZaj.each(function () {
        showHideTblCol($(this), 'removable-zaj');
    });
    inputCbFObj.each(function () {
        showHideTblCol($(this), 'removable-obj');
    });
    inputCbFSl.each(function () {
        showHideTblRow($(this), 'removable-sl');
    });
}

/**
 * Zobrazi a schova sloupce tabulky, ktere obsahuji nazev odpovidajici value elementu, ktery je predan jako parametr
 * @param element
 * @param className
 */
function showHideTblCol(element, className) {
    var colName = element.val();
    var colNum = $("th:contains('" + colName + "')").index() + 1;
    var elementsToChange = $('#tbl-ucastnici').find('tr.' + className + '').find('td:nth-child(' + colNum + '), th:nth-child(' + colNum + ')');
    if (element.is(":checked")) {
        elementsToChange.removeClass("not-in-pdf");
    } else {
        elementsToChange.addClass("not-in-pdf");
    }
}

/**
 * Zobrazi a schova radky tabulky, ktere maji urcitou class
 * @param element
 * @param className
 */
function showHideTblRow(element, className) {
    var elementsToChange = $('#tbl-ucastnici').find('.' + className).find('td, th');
    if (element.is(":checked")) {
        elementsToChange.removeClass("not-in-pdf");
    } else {
        elementsToChange.addClass("not-in-pdf");
    }
}

/**
 * Odkaz na idecku odesle formular pouze s danym ideckem
 * @param el
 * @param formId
 * @returns {boolean}
 */
function selectSingle(el, formId) {
    var form = $('#' + formId);
    var checkbox = el.parent().parent().find('input[type=checkbox]');
    var frmCheckedChackboxes = form.find('input[type=checkbox]:checked');

    frmCheckedChackboxes.attr('checked', false);
    checkbox.attr('checked', 'checked');
    //nevim jestli js pousti ulohy paralelne, ale kdyz sem nedam delay, tak nejprve zaskrtne checkbox a pak teprv odstrani zaskrtnuti vsech
    setTimeout(function () {
    }, 100)
    form.submit();

    return false;
}

/**
 * Posle pozadavek na server o odeslani seznamu ucastniku na zaskrtle emaily
 */
function sendPdfEmails() {
    var btnSendPdf = $("#btn-send-pdf");
    $("#email-status").html("");

    $.post("seznamy_ucastniku.php?page=ajax&action=send-emails", $("#form-send-pdf").serialize())
        .success(function (response) {
            response = JSON.parse(response);
            viewStatus(response);
        })
        .always(function (response) {
            btnSendPdf.removeClass("disabled");
        });
}

function setFilterSetup(setup) {
    if (setup == null || setup == "")
        return;

    var setupDump = setup.split("-");
    var chbCodemapping = {
        ti: 'cb-f-zaj-titul',
        da: 'cb-f-zaj-datum-narozeni',
        ro: 'cb-f-zaj-rodne-cislo',
        ci: 'cb-f-zaj-cislo-pasu',
        ad: 'cb-f-zaj-adresa',
        te: 'cb-f-zaj-telefon',
        em: 'cb-f-zaj-email',
        ne: 'cb-f-obj-nezobrazovat',
        id: 'cb-f-obj-id',
        sl: 'cb-f-sl-sluzby',
        ob: 'cb-f-obj-objednavajici',
        pr: 'cb-f-obj-prodejce',
        na: 'cb-f-obj-nastupni-misto'
    };

    //vycisti vse
    $.each(chbCodemapping, function (index, value) {
        $("input[name='" + value + "']").prop('checked', false);
    });

    //vybrane zaskrtni
    for (var i = 0; i < setupDump.length; i++) {
        $("input[name='" + chbCodemapping[setupDump[i]] + "']").prop('checked', true);
    }

    //refreshni tabulku
    initCheckbox();
};