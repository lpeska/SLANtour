//prida formular pro novou platbu k faktu�e - pou��v� se pouze u faktura_list
function faktura_pridat_platbu(id_faktury, id_objednavky){
    var d = new Date();
    var curr_date = d.getDate();
    var curr_month = (d.getMonth()+1);
    var curr_year = d.getFullYear();
    var dateFormat = curr_date+"."+curr_month+"."+curr_year;
    var position = document.getElementById("nova_platba_"+id_faktury);
    position.innerHTML = '<td colspan="9">\n\
<form action="faktury.php?id_faktury='+id_faktury+'&amp;typ=faktury&amp;pozadavek=add_platba&amp;id_objednavka='+id_objednavky+'" method="post">\n\
<table class="round" style="position:relative;top:-10px;border:2px solid white;border-radius: 4px;-moz-border-radius: 4px;-webkit-border-radius: 4px;">\n\
<tr>\n\
<td style="border:none;" colspan="7"><b>Nov� platba</b>\n\
<tr>\n\
<td style="border:none;"><input type="radio"  name="typ_dokladu" id="typ_dokladu_prijmovy" checked="checked" value="prijmovy" /> P��jmov� <input type="radio" name="typ_dokladu" value="vydajovy" /> V�dajov�  \n\
<td style="border:none;">��slo dokladu: <input type=\"text\" value=\"\" name=\"cislo_dokladu\"/>\n\
<td style="border:none;">��stka: <input type="text" value="0" name="nova_platba_castka"/ >\n\
<td style="border:none;">Splaceno: <input type="text" value="'+dateFormat+'" name="nova_platba_splaceno"/>\n\
<td style="border:none;">Zp�sob �hrady: <input type="text" name="nova_platba_zpusob_uhrady"/>\n\
<td style="border:none;">P�i�azeno k faktu�e\n\
<td style="border:none;"><input type="submit" value="Ulo�it"/></table></form>';
    return false;
}
