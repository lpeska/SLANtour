function searchKlient(idKlient) {
    var klient_jmeno = $("#klient_jmeno").val();
    var klient_prijmeni = $("#klient_prijmeni").val();
    var klient_datum_narozeni = $("#klient_datum_narozeni").val();
 
    if(klient_jmeno == "" && klient_prijmeni == "" && klient_datum_narozeni == "") {
        showTable("");
        return;
    }
    $.ajax({
        type: 'POST',
        url: 'rezervace.php?typ=klient_list&pozadavek=change_filter&pole=jmeno_prijmeni_datum&id_objednavka=' + idKlient,
        data: {
            'klient_ajax_uzivatele': 'true',
            'klient_jmeno': klient_jmeno,
            'klient_prijmeni': klient_prijmeni,
            'klient_datum_narozeni': klient_datum_narozeni
        },
        success: function(result){
            showTable(result);
        }
    });
}

function showTable(result) {
    //    alert(msg);
    var div = $("#osoby_result");
    var table = "<table class='list'>";
    table += "      <tr>";
    table += "          <th>Id</th><th>Pøíjmení</th><th>Jméno</th><th>Telefon</th><th>E-mail</th><th>Možnosti editace</th>";
    table += result;
    table += "      </tr>";
    table += "  </table><br/>";

    if(result == "")
        table = "";

    div.html(table);
}

/* Najdi radku s id userem a vytahni z ni info o userovi a nakopiruj do inputu - jmeno, prijmeni, e-mail, telefon. Pak zkopiruj i id do skryteho pole */
function copy_user(id_userm, el) {
    var tr = el.parentNode.parentNode;
    var id = $(tr).find('.id_klient').html();    
    var jmeno = $(tr).find('.jmeno').html();    
    var prijmeni = $(tr).find('.prijmeni').html();    
    var telefon = $(tr).find('.telefon').html();    
    var email = $(tr).find('.email').html();    
    
    $('#klient_id').val(id);
    $('#klient_jmeno').val(jmeno);
    $('#klient_prijmeni').val(prijmeni);
    $('#klient_telefon').val(telefon);
    $('#klient_email').val(email);
}