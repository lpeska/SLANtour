/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

              
                    var address_count = 2;
                    function addAddress(){
                        address_count++;
                        var html_snipet = '<tr><th colspan="2">Další adresa <input type="hidden" name="typ_'+address_count+'" value="3" /><tr><td>Stát<td><input name="stat_'+address_count+'" type=\"text\" value=\"\" class=\"wide\"/><tr><td>Mìsto<td><input name=\"mesto_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/><tr><td>Ulice a ÈP<td><input name=\"ulice_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/> <tr><td>PSÈ<td><input name=\"psc_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/><tr><td>poznámka<td><input name=\"poznamka_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/> ';
                        $('#adresa_next').before(html_snipet);
                    }
          




