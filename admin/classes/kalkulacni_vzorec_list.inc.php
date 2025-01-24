<?php
/** 
 * * TODO: dodelat upravu classy
* modul_list.inc.php - trida pro zobrazeni seznamu modulu  administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM modulu -------------------  */

class Kalkulacni_vzorec_list extends Generic_list{
	//vstupni data
	protected $order_by;
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($order_by){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		
		//kontrola vstupnich dat
		$this->order_by = $this->check($order_by);
		
		//pokud mam dostatecna prava pokracovat
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
			$select="select * ";
		
			$dotaz=	$select."
					  from  `kalkulacni_vzorec_definice` 
						where 1 
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
				 return "`id_vzorec_def` ";
   			 break;
			case "id_down":
				 return "`id_vzorec_def` desc";
   			 break;				 
			case "nazev_up":
				 return "`nazev_vzorce`";
   			 break;
			case "nazev_down":
				 return "`nazev_vzorce` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`id_vzorec_def`";
	}
	
		
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
		$vystup="
				<table class=\"list\">
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=modul_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up\"></a>
							<a class='sort-down' href=\"?typ=modul_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down\"></a>
							</div>
						</th>
						<th>Název
						<div class='sort'>
							<a class='sort-up' href=\"?typ=modul_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up\"></a>
							<a class='sort-down' href=\"?typ=modul_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down\"></a>
							</div>
						</th>
						<th>Vzorec
						</th>
						<th>Možnosti editace
						</th>
					</tr>
		";
		return $vystup;
	}		
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){	
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
			$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_vzorec_def()."
							</td>
							<td  class=\"nazev\">
									".$this->get_nazev_vzorce()."
							</td>
							<td class=\"adresa\">
								".$this->get_vzorec()."
							</td>
							<td class=\"menu\">
							  <a href=\"".$adresa_modulu."?id_vzorec_def=".$this->get_id_vzorec_def()."&amp;typ=kalkulacni_vzorec&amp;pozadavek=edit\">upravit</a>
							 | <a class='anchor-delete' href=\"".$adresa_modulu."?id_vzorec_def=".$this->get_id_vzorec_def()."&amp;typ=kalkulacni_vzorec&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
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
	function get_id_vzorec_def() { return $this->radek["id_vzorec_def"];}
	function get_nazev_vzorce() { return $this->radek["nazev_vzorce"];}
	function get_vzorec() { return $this->radek["vzorec"];}
} 

?>
