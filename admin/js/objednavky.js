//stav definovano v HTML - rezervace.inc.php->show(); Selektor nacteny z DB.
var stav;

//datepickery do editace/vytváøení plateb
$(document).ready(function () {
      $("#checkboxSelectAll").click(function(){
          $(".toDeleteCheckbox").prop('checked',true);
      
      });
      
      $("#checkboxSelectNone").click(function(){
          $(".toDeleteCheckbox").prop('checked',false);
      
      });


});

function getIDsToMassDelete(){
    idsArr = [];
    $(".toDeleteCheckbox:checked").each(function(){
        idsArr.push($(this).val());
    });
    
    if(idsArr <= 0){
        return false;
    }else{
        idsString =  idsArr.join(",");
        $("#listOfIDs").val(idsString);
        return confirm('Opravdu chcete smazat všechny oznaèené objednávky?');
    }
    
}

function edit_obj() {
    var tblObjednavka = $('#obj_table');
    var values = tblObjednavka.find('.edit-value');
    //ziskej hodnoty elementu
    var cellVals = [];

    //uprav nektere hodnoty
    var sdphProvize = "";
    if (values.eq(6).html() == "ano") sdphProvize = "checked='checked'"; else sdphProvize = "";

    //vytvor nove elementy
    var inp = [];
    inp[0] = "<input type='text' name='organizace' value='" + values.eq(0).html() + "' /> (id organizace)";
    inp[1] = "<input type='text' name='pocet_osob' value='" + values.eq(1).html() + "' />";
    inp[2] = stav;
    inp[3] = values.eq(3).html();
    inp[4] = "<input class='calendar-ymd' type='text' name='rezervace_do' value='" + values.eq(4).html() + "' />";
    inp[5] = "" + values.eq(5).html() + " Kè";
    inp[6] = "<input type='text' name='nazev_provize' value='" + values.eq(6).html() + "' />";
    inp[7] = "<input type='checkbox' name='sdph_provize' value='1' " + sdphProvize + "/>";
    inp[8] = "<input type='text' name='suma_provize' value='" + values.eq(8).html() + "' /> Kè";

    if (values.eq(9).html() == "dlouhodobý termín")
        inp[9] = values.eq(9).html();
    else
        inp[9] = "<input class='calendar-ymd' type='text' name='termin_od' value='" + values.eq(9).html() + "' />";

    if (values.eq(11).html() == "dlouhodobý termín")
        inp[10] = values.eq(10).html();
    else
        inp[10] = "<input class='calendar-ymd' type='text' name='termin_do' value='" + values.eq(10).html() + "' />";

    inp[11] = "<input type='text' name='doprava' value='" + values.eq(11).html() + "' />";
    inp[12] = "<input type='text' name='stravovani' value='" + values.eq(12).html() + "' />";
    inp[13] = "<input type='text' name='ubytovani' value='" + values.eq(13).html() + "' />";
    inp[14] = "<input type='text' name='pojisteni' value='" + values.eq(14).html() + "' />";
    inp[15] = "<textarea cols='60' rows='4' name='poznamky'>" + values.eq(15).html() + "</textarea>";
    inp[16] = "<textarea cols='60' rows='4' name='poznamky_tajne'>" + values.eq(16).html() + "</textarea>";
    if(values.eq(17).data("name") == "k_uhrade_celkem") {
        inp[18] = "<input type='text' name='k_uhrade_celkem' value='" + values.eq(17).html() + "' />" +
        " - <input class='calendar-ymd' type='text' name='k_uhrade_celkem_datspl' value='" + values.eq(18).html() + "' />";
    } else {
        inp[18] = "<input type='text' name='k_uhrade_zaloha' value='" + values.eq(17).html() + "' />" +
        " - <input class='calendar-ymd' type='text' name='k_uhrade_zaloha_datspl' value='" + values.eq(18).html() + "' />";
        inp[19] = "<input type='text' name='k_uhrade_doplatek' value='" + values.eq(19).html() + "' />" +
        " - <input class='calendar-ymd' type='text' name='k_uhrade_doplatek_datspl' value='" + values.eq(20).html() + "' />";
    }
    var submitUlozit = "<input type='submit' value='Uložit' />";
    var submitZrusit = "<input type='submit' value='Zrušit' onclick='window.location.reload(); return false;' />";

    //pridej nove elementy do DOMU / odeber edit button
    tblObjednavka.find('.edit-value').each(function(i) {
        $(this).parent().html(inp[i]);
    });
    tblObjednavka.find('tr:last-child td:last-child').html(submitUlozit + submitZrusit);
    initDatepickers();//viz /js/common_functions.js
}

function show_storno(storno_poplatek, storno_poplatek_calc, stav_storno) {
    var selector = $("#storno_selector");
    var stornoWrapper = $("#storno_wrapper");
    if (selector.find(":selected").val() == stav_storno) {
        stornoWrapper.html(" storno poplatek: <input type='text' value='" + storno_poplatek + "' id='storno_input'  name='storno_poplatek' size='4' />Kè <span style='margin-left: 10px;'><a onclick='fill_storno(" + storno_poplatek_calc + ");' style='cursor: pointer'>" + storno_poplatek_calc + " Kè</a><a style='margin-left: 10px;' href='#' title='Èástka storno poplatku vypoèítaná k dnešnímu datu dle smluvních podmínek. Klikni na èástku pro zkopírování.'>co je to?</a></span>");
    } else {
        stornoWrapper.html("<input type='hidden' value='0' id='storno_input'  name='storno_poplatek' />");
    }
    return true;
}

/*vypocet storna pro pripad zmeny zajezdu*/
function show_storno_zmena_zajezdu(zmena_wraper_id, storno_poplatek, storno_poplatek_calc) {
    var textZmena = "Zmìnit seriál";
    if (zmena_wraper_id == "zmenit_zajezd") {
        textZmena = "Zmìnit zájezd";
    }
    var stornoWrapper = $("#" + zmena_wraper_id);
    stornoWrapper.html("<br/><strong>" + textZmena + "</strong> (nejprve zvolte, zda po klientovi požadujeme storno poplatek)<br/>storno poplatek: <input type='text' value='" + storno_poplatek + "' id='storno_input_zmena'  name='storno_poplatek_zmena' size='4' />Kè <span style='margin-left: 10px;'><a onclick='fill_storno_zmena(" + storno_poplatek_calc + ");' style='cursor: pointer'>" + storno_poplatek_calc + " Kè</a></span><input type=\"hidden\" name=\"typ_zmeny\" value=\"" + zmena_wraper_id + "\"/> <input type=\"submit\" name=\"submit_zmena\" value=\"Pokraèovat\"/>");
    return true;
}
function fill_storno_zmena(storno_poplatek_calc) {
    $("#storno_input_zmena").val(storno_poplatek_calc);
}


function select_value(selector_id, value) {
    $("#" + selector_id).val(value);
}

function fill_storno(storno_poplatek_calc) {
    $("#storno_input").val(storno_poplatek_calc);
}

function edit_platba(idObjednavka, idPlatba, cisloDokladu, splatitDo, splaceno, zpusobUhrady) {
    //disable all upravit buttons v tabulce
    $('input[id*=platby_upravit_]').each(function () {
        if ($(this).attr('id') != "platby_upravit_" + idPlatba)
            $(this).attr('disabled', 'disabled');
    });

    //nahrad divy inputy
    $("#cislo_dokladu_" + idPlatba).parent().html('<input type="text" id="cislo_dokladu_' + idPlatba + '" value="' + cisloDokladu + '" size="8"/>');
    $("#splatit_do_" + idPlatba).parent().html('<input type="text" class="calendar-ymd" id="splatit_do_' + idPlatba + '" value="' + splatitDo + '" size="8"/>');
    $("#splaceno_" + idPlatba).parent().html('<input type="text" class="calendar-ymd" id="splaceno_' + idPlatba + '" value="' + splaceno + '" size="8"/>');
    $("#zpusob_uhrady_" + idPlatba).parent().html('<input type="text" id="zpusob_uhrady_' + idPlatba + '" value="' + zpusobUhrady + '" />');

    initDatepickers();//viz /js/common_functions.js

    //zmen formular
    $("#form_platba_" + idPlatba).html("");
    $("#form_platba_" + idPlatba).append('<input type="hidden" name="cislo_dokladu" id="hid_cislo_dokladu"/>');
    $("#form_platba_" + idPlatba).append('<input type="hidden" name="splatit_do" id="hid_splatit_do"/>');
    $("#form_platba_" + idPlatba).append('<input type="hidden" name="splaceno" id="hid_splaceno"/>');
    $("#form_platba_" + idPlatba).append('<input type="hidden" name="zpusob_uhrady" id="hid_zpusob_uhrady"/>');
    $("#form_platba_" + idPlatba).append('<input type="submit" value="Uložit" onclick="edit_platba_request(' + idPlatba + ')"/>');
    $("#form_platba_" + idPlatba).append('<input type="submit" onclick="window.location.reload();return false;" value="Zrušit">');

    return false;
}

function edit_platba_request(idPlatba) {
    $("#hid_cislo_dokladu").val($("#cislo_dokladu_" + idPlatba).val());
    $("#hid_splatit_do").val($("#splatit_do_" + idPlatba).val());
    $("#hid_splaceno").val($("#splaceno_" + idPlatba).val());
    $("#hid_zpusob_uhrady").val($("#zpusob_uhrady_" + idPlatba).val());

    return false;
}

function copy_add_platba_form() {
    var typ_dokladu_prijmovy = document.getElementById("add_typ_dokladu_prijmovy");
    var cislo_dokladu = document.getElementById("add_cislo_dokladu");
    var castka = document.getElementById("add_castka");
    var splatit_do = document.getElementById("add_splatit_do");
    var splaceno = document.getElementById("add_splaceno");
    var zpusob_uhrady = document.getElementById("add_zpusob_uhrady");

    if (castka.value == "") {
        alert("Všechny povinné údaje nebyly vyplnìny.");
        return false;
    }
    if(typ_dokladu_prijmovy.checked){
        document.getElementById("hid_typ_dokladu").value = "prijmovy";
    }else{
        document.getElementById("hid_typ_dokladu").value = "vydajovy";
    }
    document.getElementById("hid_cislo_dokladu").value = cislo_dokladu.value;
    document.getElementById("hid_castka").value = castka.value;
    document.getElementById("hid_splatit_do").value = splatit_do.value;
    document.getElementById("hid_splaceno").value = splaceno.value;
    document.getElementById("hid_zpusob_uhrady").value = zpusob_uhrady.value;

    return true;
}

function copy_add_ceny2_form() {
    var nazev_ceny = document.getElementsByName("nazev_ceny")[0];
    var castka = document.getElementsByName("castka")[0];
    var mena = document.getElementsByName("mena")[0];
    var pocet = document.getElementsByName("pocet")[0];
    var use_pocet_noci = document.getElementsByName("use_pocet_noci")[0];

    if (castka.value == "") {
        alert("Všechny povinné údaje nebyly vyplnìny.");
        return false;
    }

    document.getElementById("hid_nazev_ceny_ceny2").value = nazev_ceny.value;
    document.getElementById("hid_castka_ceny2").value = castka.value;
    document.getElementById("hid_mena_ceny2").value = mena.value;
    document.getElementById("hid_pocet_ceny2").value = pocet.value;
    document.getElementById("hid_use_pocet_noci_ceny2").value = use_pocet_noci.checked;

    return true;
}

function searchKlient(idObj) {
    var klient_jmeno = $("#klient_jmeno").val();
    var klient_prijmeni = $("#klient_prijmeni").val();
    var klient_datum_narozeni = $("#klient_datum_narozeni").val();

    if (klient_jmeno == "" && klient_prijmeni == "" && klient_datum_narozeni == "") {
        showTable("");
        return;
    }
    $.ajax({
        type: 'POST',
        url: '?typ=klient_list&pozadavek=change_filter&pole=jmeno_prijmeni_datum&id_objednavka=' + idObj,
        data: {
            'klient_ajax': 'true',
            'klient_jmeno': klient_jmeno,
            'klient_prijmeni': klient_prijmeni,
            'klient_datum_narozeni': klient_datum_narozeni
        },
        success: function (result) {
            showTable(result);
        }
    });
}

function showTable(result) {
    //    alert(msg);
    var div = $("#osoby_result");
    var table = "<table class='list'>";
    table += "      <tr>";
    table += "          <th>Id</th><th>Pøíjmení</th><th>Jméno</th><th>Datum narození</th><th>Telefon</th><th>E-mail</th><th>Èíslo pasu / OP</th><th>Adresa</th><th>Možnosti editace</th>";
    table += result;
    table += "      </tr>";
    table += "  </table><br/>";

    if (result == "")
        table = "";

    div.html(table);
}

function edit_obj_klient(rowIndex, id_klient) {
    //disable all upravit buttons v tabulce
    $('input[id*=klient_upravit_]').each(function () {
        if ($(this).attr('id') != "klient_upravit_" + rowIndex)
            $(this).attr('disabled', 'disabled');
    });

    var table = document.getElementById("table_osoby");
    var submitBtnParent = document.getElementById("klient_upravit_" + rowIndex).parentNode;

    var tr = table.rows[rowIndex + 3];
    var j = 0;
    var cellVals = [];
    for (var i = 0; i < tr.cells.length; i++) {
        cellVals[i] = tr.cells[i].innerHTML;
        j++;
    }

    //uprav nektere hodnoty (Kc/%...)
    var foo = cellVals[7].split("/");
    var cisloPasu = foo[0];
    var cisloOp = foo[1];
    foo = cellVals[8].split(",");
    var mesto = foo[0];
    var ulice = foo[1];
    var psc = foo[2];

    //vytvor nove elementy
    var inp = [];
    inp[0] = "<input type=\"hidden\" id=\"id_klient_" + rowIndex + "\" value=\"" + cellVals[0] + "\" />" + cellVals[0];
    inp[1] = "<input type=\"text\" id=\"klient_prijmeni_" + rowIndex + "\" value=\"" + cellVals[1] + "\" size=\"10\" />";
    inp[2] = "<input type=\"text\" id=\"klient_jmeno_" + rowIndex + "\" value=\"" + cellVals[2] + "\" size=\"10\" />";
    inp[3] = "<input type=\"text\" id=\"klient_datum_narozeni_" + rowIndex + "\" value=\"" + cellVals[3] + "\" size=\"10\" />";
    inp[4] = "<input type=\"text\" id=\"klient_rodne_cislo_" + rowIndex + "\" value=\"" + cellVals[4] + "\" size=\"10\" />";
    inp[5] = "<input type=\"text\" id=\"klient_telefon_" + rowIndex + "\" value=\"" + cellVals[5] + "\" size=\"10\" />";
    inp[6] = "<input type=\"text\" id=\"klient_email_" + rowIndex + "\" value=\"" + cellVals[6] + "\" size=\"20\" />";
    inp[7] = "<input type=\"text\" id=\"klient_cislo_pasu_" + rowIndex + "\" value=\"" + cisloPasu + "\" size=\"10\" /> / \n\
              <input type=\"text\" id=\"klient_cislo_op_" + rowIndex + "\" value=\"" + cisloOp + "\" size=\"10\" />";
    inp[8] = "<input type=\"text\" id=\"klient_mesto_" + rowIndex + "\" value=\"" + mesto + "\" size=\"20\" />,\n\
              <input type=\"text\" id=\"klient_ulice_" + rowIndex + "\" value=\"" + ulice + "\" size=\"20\" />,\n\
              <input type=\"text\" id=\"klient_psc_" + rowIndex + "\" value=\"" + psc + "\" size=\"4\" />";
    var form = "<input type='hidden' value='' name='id_klient' id='hid_id_klient_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='prijmeni' id='hid_klient_prijmeni_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='jmeno' id='hid_klient_jmeno_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='datum_narozeni' id='hid_klient_datum_narozeni_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='rodne_cislo' id='hid_klient_rodne_cislo_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='email' id='hid_klient_email_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='telefon' id='hid_klient_telefon_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='cislo_op' id='hid_klient_cislo_op_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='cislo_pasu' id='hid_klient_cislo_pasu_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='ulice' id='hid_klient_ulice_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='mesto' id='hid_klient_mesto_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='psc' id='hid_klient_psc_" + rowIndex + "' />\n\
                <input type=\"submit\" value=\"Uložit\" onclick=\"request_klient_update(" + rowIndex + ", " + id_klient + ", " + (rowIndex == 0 ? true : false) + "); return false;\" />\n\
                <input type=\"submit\" value=\"Zrušit\" onclick=\"window.location.reload();return false;\" />";

    //pridej nove elementy do DOMU / odeber edit button
    for (i = 0; i < tr.cells.length - 1; i++) {
        table.rows[rowIndex + 3].cells[i].innerHTML = inp[i];
    }

    submitBtnParent.innerHTML = form;
}

function request_klient_update(rowIndex, id_klient, is_owner) {
    var arr = new Array();
    arr[arr.length] = document.getElementById("id_klient_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_jmeno_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_prijmeni_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_datum_narozeni_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_rodne_cislo_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_email_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_telefon_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_cislo_op_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_cislo_pasu_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_ulice_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_mesto_" + rowIndex).value;
    arr[arr.length] = document.getElementById("klient_psc_" + rowIndex).value;

    document.getElementById("hid_id_klient_" + rowIndex).value = arr[0];
    document.getElementById("hid_klient_jmeno_" + rowIndex).value = arr[1];
    document.getElementById("hid_klient_prijmeni_" + rowIndex).value = arr[2];
    document.getElementById("hid_klient_datum_narozeni_" + rowIndex).value = arr[3];
    document.getElementById("hid_klient_rodne_cislo_" + rowIndex).value = arr[4];
    document.getElementById("hid_klient_email_" + rowIndex).value = arr[5];
    document.getElementById("hid_klient_telefon_" + rowIndex).value = arr[6];
    document.getElementById("hid_klient_cislo_op_" + rowIndex).value = arr[7];
    document.getElementById("hid_klient_cislo_pasu_" + rowIndex).value = arr[8];
    document.getElementById("hid_klient_ulice_" + rowIndex).value = arr[9];
    document.getElementById("hid_klient_mesto_" + rowIndex).value = arr[10];
    document.getElementById("hid_klient_psc_" + rowIndex).value = arr[11];

    var form = $("#klient_form_update_" + rowIndex);
    var serializedData = form.serialize();
    //console.log(serializedData);return;

    var request = $.ajax({
        url: "./klienti.php/?id_klient=" + id_klient + "&ajax=true&typ=klient&pozadavek=update" + (is_owner ? "&owner=true" : "&owner=false"),
        type: "post",
        data: serializedData,
        success: function (response) {
//            alert(response);
        }
    });

    request.done(function (response, textStatus, jqXHR) {
        window.location.reload();
    });

    request.fail(function (jqXHR, textStatus, errorThrown) {
        alert("Požadavek selhal.");
    });

    return false;
}

function request_klient_create(id_objednavka) {
    var arr = new Array();
    arr[arr.length] = document.getElementById("klient_jmeno").value;
    arr[arr.length] = document.getElementById("klient_prijmeni").value;
    arr[arr.length] = document.getElementById("klient_datum_narozeni").value;
    arr[arr.length] = document.getElementById("klient_rodne_cislo").value;
    arr[arr.length] = document.getElementById("klient_email").value;
    arr[arr.length] = document.getElementById("klient_telefon").value;
    arr[arr.length] = document.getElementById("klient_cislo_op").value;
    arr[arr.length] = document.getElementById("klient_cislo_pasu").value;
    arr[arr.length] = document.getElementById("klient_ulice").value;
    arr[arr.length] = document.getElementById("klient_mesto").value;
    arr[arr.length] = document.getElementById("klient_psc").value;

    if (arr[0] == "" || arr[1] == "" || arr[2] == "") {
        alert("Všechny povinné údaje nebyly vyplnìny.");
        return false;
    }

    document.getElementById("hid_klient_jmeno").value = arr[0];
    document.getElementById("hid_klient_prijmeni").value = arr[1];
    document.getElementById("hid_klient_datum_narozeni").value = arr[2];
    document.getElementById("hid_klient_rodne_cislo").value = arr[3];
    document.getElementById("hid_klient_email").value = arr[4];
    document.getElementById("hid_klient_telefon").value = arr[5];
    document.getElementById("hid_klient_cislo_op").value = arr[6];
    document.getElementById("hid_klient_cislo_pasu").value = arr[7];
    document.getElementById("hid_klient_ulice").value = arr[8];
    document.getElementById("hid_klient_mesto").value = arr[9];
    document.getElementById("hid_klient_psc").value = arr[10];

    var $form = $("#klient_form_create");
    var serializedData = $form.serialize();

    var request = $.ajax({
        url: "./klienti.php/?&typ=klient&pozadavek=create_ajax",
        type: "post",
        data: serializedData,
        async: false
    });

    var id_klient = null;
    request.done(function (response, textStatus, jqXHR) {
        id_klient = response;
    });

    request = $.ajax({
        url: "./rezervace.php?id_klient=" + id_klient + "&id_objednavka=" + id_objednavka + "&typ=rezervace_osoby&pozadavek=create",
        type: "post"
    });

    request.done(function (response, textStatus, jqXHR) {
        window.location.reload();
    });

    request.fail(function (jqXHR, textStatus, errorThrown) {
        alert("Požadavek selhal.");
    });

    return false;
}

function remove_URL_param(param) {
    var url = location.href;
    var urlparts = url.split('?');
    if (urlparts.length >= 2) {
        var prefix = encodeURIComponent(param) + '=';
        var pars = urlparts[1].split(/[&;]/g);
        for (var i = pars.length; i-- > 0;)
            if (pars[i].lastIndexOf(prefix, 0) !== -1)
                pars.splice(i, 1);
        url = urlparts[0] + '?' + pars.join('&');
    }
    return url;
}