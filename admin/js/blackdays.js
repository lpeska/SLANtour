function edit_blackdays(rowIndex, id_blackdays) {
    //disable all upravit buttons v tabulce
    $('input[id*=blackdays_upravit_]').each(function () {
        if ($(this).attr('id') != "blackdays_upravit_" + rowIndex)
            $(this).attr('disabled', 'disabled');
    });

    var table = document.getElementById("table_blackdays");
    var submitBtnParent = document.getElementById("blackdays_upravit_" + rowIndex).parentNode;

    //vytahni data z radku ktery chceme editovat
    var tr = table.rows[rowIndex + 1];
    var j = 0;
    var cellVals = [];
    for (var i = 0; i < tr.cells.length; i++) {
        cellVals[i] = tr.cells[i].innerHTML;
        j++;
    }

    //vytvor novou napln radku
    var inp = [];
    inp[0] = "<input type=\"hidden\" id=\"id_blackdays_" + rowIndex + "\" value=\"" + cellVals[0] + "\" />" + cellVals[0];
    inp[1] = "<input type=\"text\" class=\"date\" id=\"od_blackdays_" + rowIndex + "\" value=\"" + cellVals[1] + "\" size=\"7\" />";
    inp[2] = "<input type=\"text\" class=\"date\" id=\"do_blackdays_" + rowIndex + "\" value=\"" + cellVals[2] + "\" size=\"7\" />";
    var form = "<input type='hidden' value='' name='id_blackdays' id='hid_id_blackdays_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='od' id='hid_od_blackdays_" + rowIndex + "' />\n\
                <input type='hidden' value='' name='do' id='hid_do_blackdays_" + rowIndex + "' />\n\
                <input type=\"submit\" value=\"Uložit\" onclick=\"request_blackdays_update(" + rowIndex + "," + id_blackdays + "); return false;\" />\n\
                <input type=\"submit\" value=\"Zrušit\" onclick=\"window.location.reload();return false;\" />";

    //pridej nove elementy do DOMU / odeber edit button
    for (i = 0; i < tr.cells.length - 1; i++) {
        table.rows[rowIndex + 1].cells[i].innerHTML = inp[i];
    }

    submitBtnParent.innerHTML = form;
}

function request_blackdays_update(rowIndex, id_blackdays) {
    var arr = new Array();
    arr[arr.length] = document.getElementById("id_blackdays_" + rowIndex).value;
    arr[arr.length] = document.getElementById("od_blackdays_" + rowIndex).value;
    arr[arr.length] = document.getElementById("do_blackdays_" + rowIndex).value;

    document.getElementById("hid_id_blackdays_" + rowIndex).value = arr[0];
    document.getElementById("hid_od_blackdays_" + rowIndex).value = arr[1];
    document.getElementById("hid_do_blackdays_" + rowIndex).value = arr[2];

    var form = $("#blackdays_form_update_" + rowIndex);
    var serializedData = form.serialize();

    var request = $.ajax({
        url: "./serial.php/?id_blackdays=" + id_blackdays + "&typ=blackdays&pozadavek=update",
        type: "post",
        data: serializedData
    });

    request.done(function (response, textStatus, jqXHR) {
        window.location.reload();
    });

    request.fail(function (jqXHR, textStatus, errorThrown) {
        alert("Požadavek selhal.");
    });


    return false;
}

function request_blackdays_create(id_zajezd) {
    var arr = new Array();
    arr[arr.length] = document.getElementById("add_od").value;
    arr[arr.length] = document.getElementById("add_do").value;

    if (arr[0] == "" || arr[1] == "") {
        alert("Všechny povinné údaje nebyly vyplnìny.");
        return false;
    }

    document.getElementById("hid_od_blackdays").value = arr[0];
    document.getElementById("hid_do_blackdays").value = arr[1];
    document.getElementById("hid_id_zajezd_blackdays").value = id_zajezd;

    var $form = $("#blackdays_form_create");
    var serializedData = $form.serialize();

    var request = $.ajax({
        url: "./serial.php/?&typ=blackdays&pozadavek=create",
        type: "post",
        data: serializedData,
        async: false
    });

    request.done(function (response, textStatus, jqXHR) {
        window.location.reload();
    });

    request.fail(function (jqXHR, textStatus, errorThrown) {
        alert("Požadavek selhal.");
    });

    return false;
}