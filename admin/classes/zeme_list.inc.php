<?php
/** 
*zeme_list.inc.php - trida pro zobrazeni seznamu zemi a destinaci
*							- bez filtru a strankovani (predpoklad ze u zemi to nebude treba)
*/

/*------------------- SEZNAM zemi -------------------  */

class Zeme_list extends Generic_list{
	
	//vstupni data
	protected $id_zamestnance;
	protected $nazev;
	protected $order_by;
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($id_zamestnance, $order_by, $selected_zeme="", $selected_destinace=""){
	
		$this->database = Database::get_instance();
	
	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->order_by = $this->check($order_by);
                if(!is_array($selected_zeme)){
                    $this->selected_zeme = $this->check_int($selected_zeme);
                }else{
                    $this->selected_zeme = $selected_zeme;
                }
		
		$this->selected_destinace = $this->check_int($selected_destinace);
				
		//pokud mam dostatecna prava pokracovat
		if( $this->legal() ){

			//ziskani seznamu z databaze	
			$this->data = $this->database->query( $this->create_query() )
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query(){
		//definice jednotlivych casti dotazu
		
			$order=$this->order_by($this->order_by);
		
			$dotaz="select `zeme`.`nazev_zeme`,`zeme`.`id_zeme`,
								`destinace`.`nazev_destinace`,`destinace`.`id_destinace`
						 from `zeme` left join 
							  	`destinace`  on (`zeme`.`id_zeme`=`destinace`.`id_zeme`) 
						where 1
						order by ".$order." ";
			//echo $dotaz;
			return $dotaz;
	}	
	/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`zeme`.`id_zeme`,`destinace`.`id_destinace` ";
   			 break;
			case "id_down":
				 return "`zeme`.`id_zeme` desc,`destinace`.`id_destinace` desc ";
   			 break;				 
			case "nazev_up":
				 return "`zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`";
   			 break;
			case "nazev_down":
				 return "`zeme`.`nazev_zeme` desc,`destinace`.`nazev_destinace` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`nazev_zeme`,`nazev_destinace`";
	}
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		$vystup="
				<table class=\"list\">
					<tr>
						<th colspan=\"2\">Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=zeme_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=zeme_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Název
						<div class='sort'>
							<a class='sort-up' href=\"?typ=zeme_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=zeme_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
		";
		return $vystup;
	}		
	/**zobrazi seznam zamestnancu serialu*/	
	function show_list($typ_zobrazeni){	
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka_serial"){		
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($adresa_serial = $core->get_adress_modul_from_typ("serial") ){
				GLOBAL $serial;
			
				$last_zeme="";
				while($this->get_next_radek()){		
					//kazdou zemi zobrazime i bez destinace
					if($this->get_id_zeme()!=$last_zeme){
						$last_zeme=$this->get_id_zeme();		
						$tr="<tr class=\"suda\">";	 
							
						$vypis=$vypis.$tr."
								<td class=\"id_zeme\">
									".$this->get_id_zeme()."
								</td>
								<td class=\"id_destinace\"></td>
								<td class=\"nazev\">
									<strong>".$this->get_nazev_zeme()."</strong>
								</td>
								<td class=\"menu\">
								  <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=create&amp;zakladni_zeme=1&amp;polozka_menu=1\">pøidat jako základní</a>
                                                                 | <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=create&amp;zakladni_zeme=0&amp;polozka_menu=1\">pøidat jako položku menu</a>     
								 | <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=create&amp;zakladni_zeme=0&amp;polozka_menu=0\">pøidat (nezobrazí se v menu)</a>
								</td>
							</tr>";			
					}
					//pokud mame destinaci, vypiseme zemi i s ni
					if($this->get_nazev_destinace()!=""){
						$tr="<tr class=\"licha\">";				
						$vypis=$vypis.$tr."
							<td class=\"id_zeme\">
								".$this->get_id_zeme()."
							</td>
							<td class=\"id_destinace\">
								".$this->get_id_destinace()."
							</td>					
							<td class=\"nazev\">
								&nbsp;&nbsp; - ".$this->get_nazev_destinace()."
							</td>	
							<td class=\"menu\">
							  <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_zeme=".$this->get_id_zeme()."&amp;id_destinace=".$this->get_id_destinace()."&amp;typ=zeme&amp;pozadavek=create_destinace\">pøidat</a>						
								| <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_zeme=".$this->get_id_zeme()."&amp;id_destinace=".$this->get_id_destinace()."&amp;typ=zeme&amp;pozadavek=create_destinace&amp;polozka_menu=1\">pøidat jako položku menu</a>

							</td>
							</tr>";
					}
				}//end while			
				return $vypis;
			}
			
		}else if($typ_zobrazeni=="tabulka_zeme"){
		//vypis zemi a destinaci v editaci zemi (zeme.php)
			$last_zeme="";
			while($this->get_next_radek()){		
				//kazdou zemi zobrazime i bez destinace
				if($this->get_id_zeme()!=$last_zeme){
					$last_zeme=$this->get_id_zeme();	
					$tr="<tr class=\"suda\">";
							
					$vypis=$vypis.$tr."
							<td class=\"id_zeme\">
								".$this->get_id_zeme()."
							</td>
							<td class=\"id_destinace\"></td>					
							<td class=\"nazev\">
								<strong>".$this->get_nazev_zeme()."</strong>
							</td>
							<td class=\"menu\">
							  <a href=\"".$adresa_modulu."?id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=edit\">editovat zemi</a>
							| <a href=\"".$adresa_modulu."?id_zeme=".$this->get_id_zeme()."&amp;typ=destinace&amp;pozadavek=new\">vytvoøit novou destinaci</a>
							| <a class='anchor-delete' href=\"".$adresa_modulu."?id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>

							</td>
						</tr>";			
				}
				//pokud mame destinaci, vypiseme zemi i s ni
				if($this->get_nazev_destinace()!=""){
					$tr="<tr class=\"licha\">";
					$vypis=$vypis.$tr."
							<td class=\"id_zeme\">
								".$this->get_id_zeme()."
							</td>
							<td class=\"id_destinace\">
								".$this->get_id_destinace()."
							</td>							
							<td class=\"nazev\">
								&nbsp;&nbsp; - ".$this->get_nazev_destinace()."
							</td>	
							<td class=\"menu\">
							  <a href=\"".$adresa_modulu."?id_zeme=".$this->get_id_zeme()."&amp;id_destinace=".$this->get_id_destinace()."&amp;typ=destinace&amp;pozadavek=edit\">editovat destinaci</a>						
							| <a class='anchor-delete' href=\"".$adresa_modulu."?id_zeme=".$this->get_id_zeme()."&amp;id_destinace=".$this->get_id_destinace()."&amp;typ=destinace&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";
				}
			}//end while	
			return $vypis;						
						
			}else if($typ_zobrazeni=="select_zeme_destinace"){
				$last_zeme="";
				$vypis="";
				while( $this->get_next_radek() ){
					//kazdy typ zobrazime i bez podtypu
					if( $this->get_id_zeme() != $last_zeme ){
						$last_zeme = $this->get_id_zeme();
						$vypis=$vypis."<option value=\"".$this->get_id_zeme().":\"";			
						//kontrola zda nebyla minule vybrana tato polozka
							if($this->selected_zeme == $this->get_id_zeme() and $this->selected_destinace==0){
								$vypis=$vypis." selected=\"selected\"";
							}			
						$vypis=$vypis." >".$this->get_nazev_zeme()."</option>\n";
					}
					//pokud mame podtyp, vypiseme typ i s nim
					if($this->get_id_destinace()!=""){
						$vypis=$vypis."<option value=\"".$this->get_id_zeme().":".$this->get_id_destinace()."\"";	
						//kontrola zda nebyla minule vybrana tato polozka
							if($this->selected_zeme == $this->get_id_zeme() and $this->selected_destinace == $this->get_id_destinace() ){
								$vypis=$vypis." selected=\"selected\"";
							}							
						$vypis=$vypis." > &nbsp; &nbsp; - ".$this->get_nazev_destinace()."</option>\n";
					}
				}//end while
				return $vypis;				
	
			}else if($typ_zobrazeni=="select_zeme"){
				$last_zeme="";
				$vypis="";
                                $forwarded = "";
                               // print_r($this->selected_zeme);
				while( $this->get_next_radek() ){
					//kazdy typ zobrazime i bez podtypu
					if( $this->get_id_zeme() != $last_zeme ){
						$last_zeme = $this->get_id_zeme();
						$vypis=$vypis."<option value=\"".$this->get_id_zeme().":\"";			
						//kontrola zda nebyla minule vybrana tato polozka
							if($this->selected_zeme == $this->get_id_zeme() and $this->selected_destinace==0){
                                                            $forwarded=$forwarded."<option value=\"".$this->get_id_zeme().":\" selected=\"selected\"  class=\"selected\">".$this->get_nazev_zeme()."</option>\n";
                                                            $this->selected .= $this->get_nazev_zeme().", ";
							}else if(is_array($this->selected_zeme) and in_array($this->get_id_zeme().":", $this->selected_zeme)){
                                                            $forwarded=$forwarded."<option value=\"".$this->get_id_zeme().":\" selected=\"selected\"  class=\"selected\">".$this->get_nazev_zeme()."</option>\n";
                                                            $this->selected .= $this->get_nazev_zeme().", ";
                                                        }
                                                        
						$vypis=$vypis." >".$this->get_nazev_zeme()."</option>\n";
					}
				}//end while
				return $forwarded.$vypis;						
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
	function get_id_zeme() { return $this->radek["id_zeme"];}
	function get_id_destinace() { return $this->radek["id_destinace"];}
	function get_nazev_zeme() { return $this->radek["nazev_zeme"];}
	function get_nazev_destinace() { return $this->radek["nazev_destinace"];}
        function get_selected() { return $this->selected;}        
} 

?>
