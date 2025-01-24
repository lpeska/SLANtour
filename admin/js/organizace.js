$(function () {
    initGoogleMaps();
});

function initGoogleMaps() {
    var zoom = 6;
    var center = new google.maps.LatLng(49.823809, 15.455932); //somewhere in the middle of CR
    var type = google.maps.MapTypeId.ROADMAP;

    $('#adresy').find('.map_canvas').each(function () {
        if (!$(this).data("initialized")) { //element data attribute HTML5+
            var orgData = $(this).parent().parent().prev();
            var lat = orgData.find('input[name^=lat_]').val();
            var lng = orgData.find('input[name^=lng_]').val();
            var orgName = $(".main").find('input[name=nazev]').val();
            var city = orgData.find('input[name^=mesto_]').val();
            var street = orgData.find('input[name^=ulice_]').val();
            var psc = orgData.find('input[name^=psc_]').val();

            //init map
            var googleMap = new GoogleMap(zoom, center, type, $(this)[0]);

            //place marker
            googleMap.addMarker(lat, lng, orgName, new Address(null, city, street, psc));

            //init search button
            $(this).prev().on('click', function(event) {
                event.preventDefault();
                var orgName = $(".main").find('input[name=nazev]').val();
                var city = orgData.find('input[name^=mesto_]').val();
                var street = orgData.find('input[name^=ulice_]').val();
                var psc = orgData.find('input[name^=psc_]').val();
                googleMap.clearMarkers();
                googleMap.findAddress(new Address(null, city, street, psc), orgName, function(location) {
                    orgData.find('input[name^=lat_]').val(location.lat());
                    orgData.find('input[name^=lng_]').val(location.lng());
                });
            });

            $(this).data("initialized", true);
        }
    });
}

function showSpecial() {
    var role = document.getElementById("role_organizace").value;
    if(typeof provizni_koeficient === 'undefined')
        provizni_koeficient = 1;
    if (role == 1) {
        document.getElementById("special_text").innerHTML = '<h4>Prodejce (CA)</h4><div class=\"form_row\"><div class=\"label_float_left\">Koeficient prodejce</div><div class=\"value\"><input name="koeficient_prodejce" type="text" value="' + provizni_koeficient + '"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">U�ivatelsk� jm�no</div><div class=\"value\"><input name="uzivatelske_jmeno" type="text" value="' + uzivatelske_jmeno + '"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Heslo</div><div class=\"value\"><input name="heslo" type="text" value="' + heslo + '"/><input name="heslo_sha1" type="hidden" value="' + heslo_sha1 + '"/><input name="salt" type="hidden" value="' + salt + '"/><input name="last_logon" type="hidden" value="' + last_logon + '"/></div></div>';
    } else if (role == 2) {
        document.getElementById("special_text").innerHTML = '<h4>Ubytovac� za��zen�</h4><div class=\"form_row\"><div class=\"label_float_left\">P�i�azen� ubytov�n�</div><div class=\"value\">' + ubytovani_seznam + '</div>';
    } else {
        document.getElementById("special_text").innerHTML = "";

    }
}

function addAddress() {
    address_count++;
    var html_snipet = '<h4>Dal�� adresa</h4><input type="hidden" name="adresa_typ_' + address_count + '" value="3" />' +
        '<div class="edit_wrapper"><div class="left_edit_box\">' +
        '<div class="form_row"><div class="label_float_left">St�t</div><div class="value\"><input name="stat_' + address_count + '" type="text" value=""/></div></div>' +
        '<div class="form_row"><div class="label_float_left">M�sto</div><div class="value\"><input name="mesto_' + address_count + '" type="text" value=""/></div></div>' +
        '<div class="form_row"><div class="label_float_left">Ulice a �P</div><div class="value\"><input name="ulice_' + address_count + '" type="text" value=""/></div></div>' +
        '<div class="form_row"><div class="label_float_left">PS�</div><div class="value\"><input name="psc_' + address_count + '" type="text" value=""/></div></div>' +
        '<div class="form_row"><div class="label_float_left">pozn�mka</div><div class="value\"><input name="adresa_poznamka_' + address_count + '" type="text" value=""/></div></div>' +
        '<div class="form_row"><div class="label_float_left">zem�pisn� ���ka</div><div class="value"><input name="lat_' + address_count +'" type="text" value=""/></div></div>' +
        '<div class="form_row"><div class="label_float_left">zem�pisn� d�lka</div><div class="value"><input name="lng_' + address_count +'" type="text" value=""/></div></div>' +
        '</div><div class="right_edit_box"><div class="form_box"><input type="submit" class="search-adr" value="Naj�t adresu na map�" /><div class="map_canvas"></div></div></div><div class="clearfix"></div></div>';
    $('#address_next').before(html_snipet);
    initGoogleMaps();
    return false;
}

function addBankovniSpojeni() {
    bankovni_spojeni_count++;
    var html_snipet = '<h4>Dal�� bankovn� spojen�</h4><input type="hidden" name="banka_typ_' + bankovni_spojeni_count + '" value="2" />' +
        '<div class=\"form_row\"><div class=\"label_float_left\">N�zev banky</div><div class=\"value\"><input name="nazev_banky_' + bankovni_spojeni_count + '" type="text" value="" class="wide"/></div></div>' +
        '<div class=\"form_row\"><div class=\"label_float_left\">K�d banky</div><div class=\"value\"><input name="kod_banky_' + bankovni_spojeni_count + '" type="text" value="" class="wide"/></div></div>' +
        '<div class=\"form_row\"><div class=\"label_float_left\">��slo ��tu</div><div class=\"value\"><input name="cislo_uctu_' + bankovni_spojeni_count + '" type="text" value="" class="wide"/></div></div>' +
        '<div class=\"form_row\"><div class=\"label_float_left\">pozn�mka</div><div class=\"value\"><input name="banka_poznamka_' + bankovni_spojeni_count + '" type="text" value="" class="wide"/></div></div>';
    $('#bankovni_spojeni_next').before(html_snipet);

    return false;
}

function addKontakty() {
    kontakty_count++;
    var html_snipet = '<tr><th>Dal�� kontakty<input type="hidden" name=\"kontakt_typ_' + kontakty_count + '\" value=\"3\" /></th>' +
        '<td>E-mail</td><td><input name="email_' + kontakty_count + '" type="text" value="" class="wide"/></td>' +
        '<td>Telefon</td><td><input name="telefon_' + kontakty_count + '" type="text" value="" class="wide"/></td>' +
        '<td>Web</td><td><input name="web_' + kontakty_count + '" type="text" value="" class="wide"/></td>' +
        '<td>Pozn�mka</td><td><input name="kontakt_poznamka_' + kontakty_count + '" type="text" value="" class="wide"/></td></tr>';
    $('#kontakty_next').before(html_snipet);
    return false;
}

function addEmail() {
    kontakty_count++;
    var html_snipet = '<tr><td><input type="hidden" name="kontakt_typ_' + kontakty_count + '" value="3" /><input name="email_' + kontakty_count + '" type="text" value=""  style="width:150px;"/></td>' +
        '<td><input name="kontakt_poznamka_' + kontakty_count + '" type="text" value="" style="width:200px;"/></td>';
    $('#email_next').before(html_snipet);

    return false;
}

function addWeb() {
    kontakty_count++;
    var html_snipet = '<tr><td><input type="hidden" name="kontakt_typ_' + kontakty_count + '" value="3" /><input name="web_' + kontakty_count + '" type="text" value="" style="width:150px;"/></td>' +
        '<td><input name="kontakt_poznamka_' + kontakty_count + '" type="text" value="" style="width:200px;"/></td></tr>';
    $('#web_next').before(html_snipet);
    return false;
}

function addTelefon() {
    kontakty_count++;
    var html_snipet = '<tr><td><input type="hidden" name="kontakt_typ_' + kontakty_count + '" value="3" /><input name="telefon_' + kontakty_count + '" type="text" value="" style="width:150px;"/></td>' +
        '<td><input name="kontakt_poznamka_' + kontakty_count + '" type="text" value="" style="width:200px;"/></td></tr>';
    $('#telefon_next').before(html_snipet);

    return false;
}