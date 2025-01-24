<?php
/** 
* centralni_data_list.inc.php - trida pro zobrazeni seznamu modulu  administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM modulu -------------------  */

class Centralni_data_list extends Generic_list{
	//vstupni data
    protected $nazev;
    protected $text;
    protected $order_by;
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($order_by, $nazev, $text){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		
		//kontrola vstupnich dat
		$this->order_by = $this->check($order_by);
		$this->nazev = $this->check($nazev);
                $this->text = $this->check($text);
		//pokud mam dostatecna prava pokracovat
               // print_r($this);
               // print_r($_SESSIONS);
		if($this->legal()){

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu do databaze*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show"){
			//definice jednotlivych casti dotazu
			$order=$this->order_by($this->order_by);
                        
                        if($this->nazev!=""){
				$where_nazev=" `nazev` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}
                        if($this->text!=""){
				$where_text=" `text` like '%".$this->text."%' and";
			}else{
				$where_text=" ";
			}
                        
			$select="select * ";
		
			$dotaz=	$select."
					  from  `centralni_data` 
						where ".$where_text.$where_nazev." 1 
						order by ".$order." ".
						$limit;
			//echo $dotaz;
			return $dotaz;
		}
	}	
	
	/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`id_data` ";
   			 break;
			case "id_down":
				 return "`id_data` desc";
   			 break;				 
			case "nazev_up":
				 return "`nazev`";
   			 break;
			case "nazev_down":
				 return "`nazev` desc";
   			 break;	
                        case "poznamka_up":
				 return "`poznamka`";
   			 break;
			case "poznamka_down":
				 return "`poznamka` desc";
   			 break;	
                     case "text_up":
				 return "`text`";
   			 break;
			case "text_down":
				 return "`text` desc";
   			 break;
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`id_data`";
	}
	
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)

		//tvroba input nazev
		$input_nazev="<input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" />";
		$input_text="<input name=\"text\" type=\"text\" value=\"".$this->text."\" />";
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
		
	
				
		//vysledny formular		
		$vystup="
			<form method=\"post\" action=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=nazev\">
			<table class=\"filtr\">
				<tr>
					<td>Název záznamu: ".$input_nazev."</td>
                                        <td>Text záznamu: ".$input_text."</td>
					<td>".$submit."</td>
				</tr>
			</table>
			</form>
		";
		return $vystup;
	
	}	
        
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header($type=""){
            if($type=="kurzy"){
                $vystup="
				<table class=\"list\">
				    <colgroup><col width='4%'><col width='10%'><col width='30%'><col width='48%'><col width='8%'></colgroup>
					<tr>
						<th>Id
						</th>
						<th>Mìna
						</th>
                                                <th>Kurz
						</th>
						<th>Možnosti editace
						</th>
					</tr>
		";                
            }else{                            
		$vystup="
				<table class=\"list\">
				    <colgroup><col width='4%'><col width='10%'><col width='30%'><col width='48%'><col width='8%'></colgroup>
					<tr>
						<th>Id
						    <div class='sort'>
							    <a class='sort-up' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up\"></a>
							    <a class='sort-down' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down\"></a>
							</div>
						</th>
						<th>Název
						    <div class='sort'>
							    <a class='sort-up' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up\"></a>
							    <a class='sort-down' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down\"></a>
							</div>
						</th>
						<th>Poznámka
						    <div class='sort'>
							    <a class='sort-up' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=poznamka_up\"></a>
							    <a class='sort-down' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=poznamka_down\"></a>
				            </div>
						</th>
                        <th>Text
                            <div class='sort'>
							    <a class='sort-up' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=text_up\"></a>
							    <a class='sort-down' href=\"?typ=centralni_data_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=text_down\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
		";
                }
		return $vystup;
	}		
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){	
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka" or $typ_zobrazeni=="tabulka_kurzy"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
                        if(strlen(strip_tags($this->get_text())) > 100){
                            $text = substr(strip_tags($this->get_text()), 0, 100)."...";
                        }else{
                            $text = strip_tags($this->get_text());
                        }
                        if($typ_zobrazeni=="tabulka"){
                            $out = "<td  class=\"nazev\">
                                        ".$this->get_nazev()."
                                    </td>
                                    <td class=\"adresa\">
                                        ".$this->get_poznamka()."
                                    </td>";
                        }else if($typ_zobrazeni=="tabulka_kurzy"){
                            $out = "<td  class=\"nazev\">
                                        ".$this->get_nazev("kurzy")."
                                    </td>";
                        }
			$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_data()."
							</td>
							$out
                                                        <td class=\"adresa\">
								".$text."
							</td>
							<td class=\"menu\">
							  <a href=\"".$adresa_modulu."?id_data=".$this->get_id_data()."&amp;typ=centralni_data&amp;pozadavek=edit\">upravit</a>
							 | <a class='anchor-delete' href=\"".$adresa_modulu."?id_data=".$this->get_id_data()."&amp;typ=centralni_data&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";
			return $vypis;
			
		}
	}		
	
	/**zjistim, zda mam opravneni k pozadovane akci*/
	function legal(){
		$zamestnanec = User_zamestnanec::get_instance();
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
				
		return $zamestnanec->get_bool_prava($id_modul,"read");
	}
	
	/*metody pro pristup k parametrum*/
	function get_id_data() { return $this->radek["id_data"];}
	function get_nazev($type="") {
            if($type=="kurzy"){
                return str_replace("kalkulace_mena:", "", $this->radek["nazev"]);
            }
            return $this->radek["nazev"];
            
            }
	function get_poznamka() { return $this->radek["poznamka"];}
	function get_text() { return $this->radek["text"];}	
} 

?>
