function showSpecial() {
    var role = document.getElementById("typ_objektu").value;
    document.getElementById("special_text").innerHTML = '<h3>Rozšíøené údaje</h3>';
    if (role == 1) {
        document.getElementById("special_text").innerHTML += '<div class=\"form_row\"><div class=\"label_float_left\">Název ubytování</div><div class=\"value\"><input name="nazev_ubytovani" type="text" value="' + document.getElementById("nazev").value + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Název pro web</div><div class=\"value\"><input name="nazev_ubytovani_web" type="text" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Popis a poloha</div><div class=\"value\"><textarea name="popis_poloha" id="popisek_" rows="10" cols="100">' + popis_poloha + '</textarea></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Pokoje, ubytování</div><div class=\"value\"><textarea name="pokoje_ubytovani" id="popis_" rows="10" cols="100">' + pokoje_ubytovani + '</textarea></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Pobyt se psem</div><div class=\"value\">Není známo, nezobrazovat <input type="radio" name="pes" value="0" ' + ((pes == 0) ? ('checked="checked"') : ('')) + '/>,Nelze <input type="radio" name="pes" value="2" ' + ((pes == 2) ? ('checked="checked"') : ('')) + ' />, Lze <input type="radio" name="pes" value="1" ' + ((pes == 1) ? ('checked="checked"') : ('')) + ' />,cena pobytu: <input type="text" name="pes_cena" value="' + pes_cena + '" size="8" /> /den.</div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Typ ubytování</div><div class=\"value\"><select name="typ_ubytovani"><option value="0">--- (neznámý typ) </option>  <option value="1" ' + ((typ_ubytovani == 1) ? ('selected="selected"') : ('')) + '>stan/kemp</option>  <option value="2"  ' + ((typ_ubytovani == 2) ? ('selected="selected"') : ('')) + '>chatky/bungalovy/mobilhome</option>  <option value="3"  ' + ((typ_ubytovani == 3) ? ('selected="selected"') : ('')) + '>apartmány</option> <option value="4"  ' + ((typ_ubytovani == 4) ? ('selected="selected"') : ('')) + '>penzion</option> <option value="5"  ' + ((typ_ubytovani == 5) ? ('selected="selected"') : ('')) + '>hotel</option>  <option value="6"  ' + ((typ_ubytovani == 6) ? ('selected="selected"') : ('')) + '>lázeòský dùm</option> </select></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Kategorie ubytování</div><div class=\"value\"><select name="kategorie_ubytovani"><option value="0">--- (neznámá kategorie) </option>  <option value="1" ' + ((kategorie_ubytovani == 1) ? ('selected="selected"') : ('')) + '>*</option>  <option value="2"  ' + ((kategorie_ubytovani == 2) ? ('selected="selected"') : ('')) + '>**</option> <option value="2.5"  ' + ((kategorie_ubytovani == 2.5) ? ('selected="selected"') : ('')) + '>**+</option>   <option value="3"  ' + ((kategorie_ubytovani == 3) ? ('selected="selected"') : ('')) + '>***</option> <option value="3.5"  ' + ((kategorie_ubytovani == 3.5) ? ('selected="selected"') : ('')) + '>***+</option> <option value="4"  ' + ((kategorie_ubytovani == 4) ? ('selected="selected"') : ('')) + '>****</option> <option value="4.5"  ' + ((kategorie_ubytovani == 4.5) ? ('selected="selected"') : ('')) + '>****+</option> <option value="5"  ' + ((kategorie_ubytovani == 5) ? ('selected="selected"') : ('')) + '>*****</option></select></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">GoGlobal identifikátor hotelu</div><div class=\"value\"><input name="goglobal_hotel_id" type="text" value="' + goglobal_hotel_id + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Zemìpisná šíøka (Praha: 14.4)</div><div class=\"value\"><input name="posX" type="text"  value="' + posX + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Zemìpisná délka (Praha: 50.1)</div><div class=\"value\"><input name="posY" type="text"   value="' + posY + '"class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Highlights (oddìlujte èárkou)</div><div class=\"value\"><textarea name="highlights" rows="3" cols="100">' + highlights + '</textarea>';
        makeWhizzyWig("popisek_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");
    } else if (role == 3) {
        document.getElementById("special_text").innerHTML += '<table>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">SPORT (odpovídá tabulce zemì)</div><div class=\"value\">' + vstupenka_sport + '</div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Akce</div><div class=\"value\"><input name="akce" type="text" value="' + vstupenka_akce + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Kód vstupenky</div><div class=\"value\"><input name="kod" type="text" value="' + vstupenka_kod + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Popis objektu</div><div class=\"value\"><textarea name="popis_objektu" rows="10" cols="100" id="popis_">' + popis + '</textarea></div></div>';
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");
    } else if (role == 5) {
        document.getElementById("special_text").innerHTML += '<table>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Let Z (kód letištì)</div><div class=\"value\"><input name="flight_from" type="text" value="' + flight_from + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Let Do (kód letištì</div><div class=\"value\"><input name="flight_to" type="text" value="' + flight_to + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Pouze pøímé lety</div><div class=\"value\"><input name="flight_direct" type="checkbox" value="1" '+ ((flight_direct == 1) ? ('checked="checked"') : ('')) +' class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Automaticky kontrolovat ceny</div><div class=\"value\"><input name="automaticka_kontrola_cen" type="checkbox" value="1" '+ ((automaticka_kontrola_cen == 1) ? ('checked="checked"') : ('')) +' class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Popis objektu</div><div class=\"value\"><textarea name="popis_objektu" rows="10" cols="100" id="popis_">' + popis + '</textarea></div></div>';
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");

    } else {
        document.getElementById("special_text").innerHTML += '<div class=\"form_row\"><div class=\"label_float_left\">Popis objektu</div><div class=\"value\"><textarea name="popis_objektu" rows="10" cols="100"  id="popis_" >' + popis + '</textarea></div></div>';
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");

    }                                                    

    showEditors();
}

function showSpecialEdit() {
    var role = document.getElementById("typ_objektu").value;
    if (role == 1) {
        document.getElementById("special_text").innerHTML = ''
            + '<div class=\"form_row\"><div class=\"label_float_left\">Název ubytování</div><div class=\"value\"><input name="nazev_ubytovani" type="text" value="' + nazev_ubytovani + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Název pro web</div><div class=\"value\"><input name="nazev_ubytovani_web" type="text" value="' + nazev_ubytovani_web + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Popis a poloha</div><div class=\"value\"><textarea name="popis_poloha" id="popisek_" rows="10" cols="100">' + popis_poloha + '</textarea></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Pokoje, ubytování</div><div class=\"value\"><textarea name="pokoje_ubytovani" id="popis_" rows="10" cols="100">' + pokoje_ubytovani + '</textarea></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Pobyt se psem</div><div class=\"value\">Není známo, nezobrazovat <input type="radio" name="pes" value="0" ' + ((pes == 0) ? ('checked="checked"') : ('')) + '/>,Nelze <input type="radio" name="pes" value="2" ' + ((pes == 2) ? ('checked="checked"') : ('')) + ' />, Lze <input type="radio" name="pes" value="1" ' + ((pes == 1) ? ('checked="checked"') : ('')) + ' />,cena pobytu: <input type="text" name="pes_cena" value="' + pes_cena + '" size="8" /> /den.</div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Typ ubytování</div><div class=\"value\"><select name="typ_ubytovani"><option value="0">--- (neznámý typ) </option>  <option value="1" ' + ((typ_ubytovani == 1) ? ('selected="selected"') : ('')) + '>stan/kemp</option>  <option value="2"  ' + ((typ_ubytovani == 2) ? ('selected="selected"') : ('')) + '>chatky/bungalovy/mobilhome</option>  <option value="3"  ' + ((typ_ubytovani == 3) ? ('selected="selected"') : ('')) + '>apartmány</option> <option value="4"  ' + ((typ_ubytovani == 4) ? ('selected="selected"') : ('')) + '>penzion</option> <option value="5"  ' + ((typ_ubytovani == 5) ? ('selected="selected"') : ('')) + '>hotel</option>  <option value="6"  ' + ((typ_ubytovani == 6) ? ('selected="selected"') : ('')) + '>lázeòský dùm</option></select></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Kategorie ubytování</div><div class=\"value\"><select name="kategorie_ubytovani"><option value="0">--- (neznámá kategorie) </option>  <option value="1" ' + ((kategorie_ubytovani == 1) ? ('selected="selected"') : ('')) + '>*</option>  <option value="2"  ' + ((kategorie_ubytovani == 2) ? ('selected="selected"') : ('')) + '>**</option> <option value="2.5"  ' + ((kategorie_ubytovani == 2.5) ? ('selected="selected"') : ('')) + '>**+</option>   <option value="3"  ' + ((kategorie_ubytovani == 3) ? ('selected="selected"') : ('')) + '>***</option> <option value="3.5"  ' + ((kategorie_ubytovani == 3.5) ? ('selected="selected"') : ('')) + '>***+</option> <option value="4"  ' + ((kategorie_ubytovani == 4) ? ('selected="selected"') : ('')) + '>****</option> <option value="4.5"  ' + ((kategorie_ubytovani == 4.5) ? ('selected="selected"') : ('')) + '>****+</option> <option value="5"  ' + ((kategorie_ubytovani == 5) ? ('selected="selected"') : ('')) + '>*****</option></select></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">GoGlobal identifikátor hotelu</div><div class=\"value\"><input name="goglobal_hotel_id" type="text" value="' + goglobal_hotel_id + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Zemìpisná šíøka (Praha: 14.4)</div><div class=\"value\"><input name="posX" type="text"  value="' + posX + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Zemìpisná délka (Praha: 50.1)</div><div class=\"value\"><input name="posY" type="text"   value="' + posY + '"class="inputText"/></div></div>'                      
            + '<div class=\"form_row\"><div class=\"label_float_left\">Highlights (oddìlujte èárkou)</div><div class=\"value\"><textarea name="highlights" rows="3" cols="100">' + highlights + '</textarea></div></div>';
        makeWhizzyWig("popisek_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");

    } else if (role == 3) {
        document.getElementById("special_text").innerHTML = ''
            + '<div class=\"form_row\"><div class=\"label_float_left\">SPORT (odpovídá tabulce zemì)</div><div class=\"value\">' + vstupenka_sport + '</div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Akce</div><div class=\"value\"><input name="akce" type="text" value="' + vstupenka_akce + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Kód vstupenky</div><div class=\"value\"><input name="kod" type="text" value="' + vstupenka_kod + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Popis objektu</div><div class=\"value\"><textarea name="popis_objektu" rows="10" cols="100" id="popis_">' + popis + '</textarea></div></div>';
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");
    

    } else if (role == 5) {
        document.getElementById("special_text").innerHTML = ''
            + '<div class=\"form_row\"><div class=\"label_float_left\">Let Z (kód letištì)</div><div class=\"value\"><input name="flight_from" type="text" value="' + flight_from + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Let Do (kód letištì</div><div class=\"value\"><input name="flight_to" type="text" value="' + flight_to + '" class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Pouze pøímé lety</div><div class=\"value\"><input name="flight_direct" type="checkbox" value="1" '+ ((flight_direct == 1) ? ('checked="checked"') : ('')) +' class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Automaticky kontrolovat ceny</div><div class=\"value\"><input name="automaticka_kontrola_cen" type="checkbox" value="1" '+ ((automaticka_kontrola_cen == 1) ? ('checked="checked"') : ('')) +' class="inputText"/></div></div>'
            + '<div class=\"form_row\"><div class=\"label_float_left\">Popis objektu</div><div class=\"value\"><textarea name="popis_objektu" rows="10" cols="100" id="popis_">' + popis + '</textarea></div></div>';
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");


    } else {
        document.getElementById("special_text").innerHTML = '<div class=\"form_row\"><div class=\"label_float_left\">Popis objektu</div><div class=\"value\"><textarea name="popis_objektu" rows="10" cols="100" id="popis_" >' + popis + '</textarea></div></div>';
        makeWhizzyWig("popis_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");

    }

    showEditors();
}
function showEditors() {


}

function addOK() {
    ok_count++;
    var html_snipet ='<h4>Objektová kategorie ' + ok_count + '</h4>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Název kategorie</div><div class=\"value\"><input name="ok_nazev_kategorie_' + ok_count + '" type="text"  class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Krátký název</div><div class=\"value\"><input name="ok_kratky_nazev_kategorie_' + ok_count + '" type="text"  class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Cizí název</div><div class=\"value\"><input name="ok_cizi_nazev_kategorie_' + ok_count + '" type="text" class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">GoGlobal identifikátor hotelu</div><div class=\"value\"><input name="goglobal_hotel_id_ok_' + ok_count + '" type="text" class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Základní kategorie</div><div class=\"value\"><input name="ok_zakladni_kategorie_' + ok_count + '" type="checkbox" value="1" /></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Hlavní kapacita</div><div class=\"value\"><input name="ok_hlavni_kapacita_' + ok_count + '" type="text" class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Vedlejší kapacita (pøistýlka atp.)</div><div class=\"value\"><input name="ok_vedlejsi_kapacita_' + ok_count + '" type="text"  class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Objektovou kategorii prodávat jako celek *</div><div class=\"value\"><input name="ok_jako_celek_' + ok_count + '" type="checkbox" value="1" /></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Poznámka</div><div class=\"value\"><input name="ok_poznamka_kategorie_' + ok_count + '" type="text" class="inputText"/></div></div>'
        + '<div class=\"form_row\"><div class=\"label_float_left\">Popis kategorie</div><div class=\"value\"><textarea id="ok_popis_kategorie_' + ok_count + '_" name="ok_popis_kategorie_' + ok_count + '" rows="4" cols="80" > </textarea></div></div>';


    $('#ok_next').before(html_snipet);

    makeWhizzyWig("ok_popis_kategorie_" + ok_count + "_", "fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen");

    return false;
}


function autocheckSelected(selOption) {
    var data = "";

    $("input[name=selected_ids]:checked").each(function () {
        data += "selected_ids[]=" + $(this).val() + "&";
    });
    data = data.substr(0, data.length - 1);
    if(selOption == "select"){
        $.ajax({
            async: false,
            type: "POST",
            url: "objekty.php?typ=objekty_list&pozadavek=mass-autocheck-select",
            data: data
        });
    }else if(selOption == "unselect"){
        $.ajax({
            async: false,
            type: "POST",
            url: "objekty.php?typ=objekty_list&pozadavek=mass-autocheck-unselect",
            data: data
        });        
    }else if(selOption == "select-delayed"){
        $.ajax({
            async: false,
            type: "POST",
            url: "objekty.php?typ=objekty_list&pozadavek=mass-autocheck-select-delayed",
            data: data
        });        
    }else if(selOption == "unselect-delayed"){
        $.ajax({
            async: false,
            type: "POST",
            url: "objekty.php?typ=objekty_list&pozadavek=mass-autocheck-unselect-delayed",
            data: data
        });        
    }
    
    location.reload();
}

$(document).ready(function () {
    $('#autocheck-selected').on('click', function () {
        autocheckSelected("select");
    });
    $('#remAutocheck-selected').on('click', function () {
        autocheckSelected("unselect");
    });
    $('#autocheck-selected-delayed').on('click', function () {
        autocheckSelected("select-delayed");
    });
    $('#remAutocheck-selected-delayed').on('click', function () {
        autocheckSelected("unselect-delayed");
    });    
}); 
                               