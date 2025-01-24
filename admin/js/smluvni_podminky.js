function disable_buttons(rowIndex, btnUpdateId) {
    $('input[id*=btn_update_]').each(function() {   
        if($(this).attr('id') != btnUpdateId + rowIndex)
            $(this).attr('disabled', 'disabled');
    });
}

function edit_smluv_podm(rowIndex, id, tableId, formUpdateId, btnUpdateId) {
    disable_buttons(rowIndex, btnUpdateId);

    var table = document.getElementById(tableId);
    var submitBtnParent = document.getElementById(btnUpdateId + rowIndex).parentNode;
    
    //vytahni data z radku ktery chceme editovat
    var tr = table.rows[rowIndex + 1];
    var j = 0;
    var cellVals = [];
    for(var i = 0; i < tr.cells.length; i++){
        cellVals[i] = tr.cells[i].innerHTML;
        j++;
    }
    
    //id_dokument je zabaleno v hidden inputu <input value='id_dokument' />
//    cellVals[5] = cellVals[5].split("value=\"")[1].split("\"")[0];
    
    //vytvor novou napln radku
    var inp = [];
    inp[0] = "<input type='hidden' id='id_smluv_podm_" + rowIndex + "' value='" + cellVals[0] + "' />" + cellVals[0];
    inp[1] = "<input type='text' id='castka_smluv_podm_" + rowIndex + "' value='" + cellVals[1] + "' size='7' />";
    inp[2] = "<input type='text' id='procento_smluv_podm_" + rowIndex + "' value='" + cellVals[2] + "' size='7' />";    
    inp[3] = "<input type='text' id='prodleva_smluv_podm_" + rowIndex + "' value='" + cellVals[3] + "' size='7' />";    
    inp[4] = "  <select id='typ_smluv_podm_" +rowIndex+"'>\n\
                    <option value='z&aacute;loha' "+(cellVals[4] == "záloha" ? "selected='selected'" : "")+">záloha</option>\n\
                    <option value='doplatek' "+(cellVals[4] == "doplatek" ? "selected='selected'" : "")+">doplatek</option>\n\
                    <option value='storno' "+(cellVals[4] == "storno" ? "selected='selected'" : "")+">storno</option>\n\
                </select>";
    var form = "<input type='hidden' value='' name='id_smluvni_podminky' id='hid_id_smluv_podm_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='castka' id='hid_castka_smluv_podm_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='procento' id='hid_procento_smluv_podm_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='prodleva' id='hid_prodleva_smluv_podm_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='typ' id='hid_typ_smluv_podm_" + rowIndex + "' />\n\
                <input type='submit' value='Uložit' onclick='request_smluv_podm_update(" + rowIndex + "," + id + ",\"" + formUpdateId + "\"); return false;' />\n\
                <input type='submit' value='Zrušit' onclick='window.location.reload();return false;' />";

    //pridej nove elementy do DOMU / odeber edit button
    for(i = 0; i < tr.cells.length - 1; i++){
        table.rows[rowIndex + 1].cells[i].innerHTML = inp[i];
    }

    submitBtnParent.innerHTML = form;    
}

function request_smluv_podm_update(rowIndex, id, formUpdateId) {
//    alert("asd");
    var arr = new Array();
    arr[arr.length] = document.getElementById("id_smluv_podm_" + rowIndex).value;
    arr[arr.length] = document.getElementById("castka_smluv_podm_" + rowIndex).value;
    arr[arr.length] = document.getElementById("procento_smluv_podm_" + rowIndex).value;
    arr[arr.length] = document.getElementById("prodleva_smluv_podm_" + rowIndex).value;
    arr[arr.length] = document.getElementById("typ_smluv_podm_" + rowIndex).value;

    document.getElementById("hid_id_smluv_podm_" + rowIndex).value = arr[0];
    document.getElementById("hid_castka_smluv_podm_" + rowIndex).value = arr[1];
    document.getElementById("hid_procento_smluv_podm_" + rowIndex).value = arr[2];  
    document.getElementById("hid_prodleva_smluv_podm_" + rowIndex).value = arr[3];  
    document.getElementById("hid_typ_smluv_podm_" + rowIndex).value = arr[4];  
//    alert("1");
    var form = $("#" + formUpdateId + rowIndex);
//    alert("2");
    var serializedData = form.serialize();    

    var request = $.ajax({
        url: "./smluvni_podminky.php/?id_smluvni_podminky=" + id + "&typ=smluvni_podminky&pozadavek=update",
        type: "post",
        data: serializedData
    });
   
    request.done(function (response, textStatus, jqXHR){
        window.location.reload();
    });
    
    request.fail(function (jqXHR, textStatus, errorThrown){
        alert("Požadavek selhal.");
    });

    
    return false;
}

function edit_smluv_podm_nazev(rowIndex, id, tableId, selector) {
    disable_buttons(rowIndex, "btn_update_");

    var table = document.getElementById(tableId);
    var submitBtnParent = document.getElementById("btn_update_" + rowIndex).parentNode;
    
    //vytahni data z radku ktery chceme editovat
    var tr = table.rows[rowIndex + 1];
    var j = 0;
    var cellVals = [];
    for(var i = 0; i < tr.cells.length; i++) {
        cellVals[i] = tr.cells[i].innerHTML;
        j++;
    }    
        
    //smluv podm maj za nazvem hidden pole s id
    cellVals[2] = cellVals[2].split("<")[0];
    
    //vytvor novou napln radku
    var inp = [];
    inp[0] = "<input type='hidden' id='id_smluv_podm_nazev_" + rowIndex + "' value='" + cellVals[0] + "' />" + cellVals[0];
    inp[1] = "<input type='text' id='nazev_smluv_podm_" + rowIndex + "' value='" + cellVals[1] + "' size='35' />";    
    inp[2] = selector; 
    var form = "<input type='hidden' value='' name='id_smluvni_podminky_nazev' id='hid_id_smluvni_podminky_nazev_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='nazev' id='hid_nazev_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='dokument_id' id='hid_document_id_" + rowIndex + "' />\n\
                <input type='submit' value='Uložit' onclick='request_smluv_podm_nazev_update(" + rowIndex + "," + id + "); return false;' />\n\
                <input type='submit' value='Zrušit' onclick='window.location.reload();return false;' />";

    //pridej nove elementy do DOMU / odeber edit button
    for(i = 0; i < tr.cells.length - 1; i++){
        table.rows[rowIndex + 1].cells[i].innerHTML = inp[i];
    }

    submitBtnParent.innerHTML = form;    
}

function request_smluv_podm_nazev_update(rowIndex, id) {
    var arr = new Array();
    arr[arr.length] = document.getElementById("id_smluv_podm_nazev_" + rowIndex).value;
    arr[arr.length] = document.getElementById("nazev_smluv_podm_" + rowIndex).value;
    arr[arr.length] = document.getElementById("document_id_" + rowIndex).value;

    document.getElementById("hid_id_smluvni_podminky_nazev_" + rowIndex).value = arr[0];
    document.getElementById("hid_nazev_" + rowIndex).value = arr[1];
    document.getElementById("hid_document_id_" + rowIndex).value = arr[2];
    
    var form = $("#form_update_" + rowIndex);
    var serializedData = form.serialize();    

    var request = $.ajax({
        url: "./smluvni_podminky.php/?id_smluvni_podminky_nazev=" + id + "&typ=smluvni_podminky_nazev&pozadavek=update",
        type: "post",
        data: serializedData
    });
   
    request.done(function (response, textStatus, jqXHR){
        window.location.reload();
    });
    
    request.fail(function (jqXHR, textStatus, errorThrown){
        alert("Požadavek selhal.");
    });

    
    return false;
}

/**
 * @param nazvy - nazvy hodnot
 * @param povinne - ktere hodnoty jsou povinne
 * @param requestUrl - URL na ktere se ma odeslat
 */
function request_create(nazvy, povinne, requestUrl) {    
    //vytahni si hodnoty z vkladanych poli, serializuj a zkontroluj povinne hodnoty
    var serData = "";
    for(var i = 0; i < nazvy.length; i++) {
        var value = $("#add_" + nazvy[i]).val();        
        serData += nazvy[i] + "=" + value + "&";
        //zkontroluj povinne udaje
        if(povinne[i] == 1 && value == "") {
            alert("Všechny povinné údaje nebyly vyplnìny.");
            return false;
        }
    }
    serData = serData.substr(0, serData.length - 1);
    
    //odesli dotaz
    var request = $.ajax({
        url: requestUrl,
        type: "post",
        data: serData,
        async: false
    });
    
    //po uspesnem provedeni requestu reloadni stranku
    request.done(function (response, textStatus, jqXHR){
//        alert(response);
        window.location.reload();
    });          
    
    //po neuspesnem provedeni vyhod chybu
    request.fail(function (jqXHR, textStatus, errorThrown){
        alert("Požadavek selhal.");
    });
    
    return false;
}