/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

              
                    var address_count = 2;
                    function addAddress(){
                        address_count++;
                        var html_snipet = '<tr><th colspan="2">Dal�� adresa <input type="hidden" name="typ_'+address_count+'" value="3" /><tr><td>St�t<td><input name="stat_'+address_count+'" type=\"text\" value=\"\" class=\"wide\"/><tr><td>M�sto<td><input name=\"mesto_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/><tr><td>Ulice a �P<td><input name=\"ulice_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/> <tr><td>PS�<td><input name=\"psc_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/><tr><td>pozn�mka<td><input name=\"poznamka_'+address_count+'\" type=\"text\" value=\"\" class=\"wide\"/> ';
                        $('#adresa_next').before(html_snipet);
                    }
          




