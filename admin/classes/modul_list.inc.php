<?php
/** 
* modul_list.inc.php - trida pro zobrazeni seznamu modulu  administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM modulu -------------------  */

class Modul_list extends Generic_list{
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
					  from  `modul_administrace` 
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
				 return "`id_modul` ";
   			 break;
			case "id_down":
				 return "`id_modul` desc";
   			 break;				 
			case "nazev_up":
				 return "`nazev_modulu`";
   			 break;
			case "nazev_down":
				 return "`nazev_modulu` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`id_modul`";
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
						<th>Adresa
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
								".$this->get_id_modul()."
							</td>
							<td  class=\"nazev\">
									".$this->get_nazev_modulu()."
							</td>
							<td class=\"adresa\">
								".$this->get_adresa_modulu()."
							</td>
							<td class=\"menu\">
							  <a href=\"".$adresa_modulu."?id_modul=".$this->get_id_modul()."&amp;typ=modul&amp;pozadavek=edit\">upravit</a>
							 | <a class='anchor-delete' href=\"".$adresa_modulu."?id_modul=".$this->get_id_modul()."&amp;typ=modul&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
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
	function get_id_modul() { return $this->radek["id_modul"];}
	function get_nazev_modulu() { return $this->radek["nazev_modulu"];}
	function get_adresa_modulu() { return $this->radek["adresa_modulu"];}
	function get_povoleno() { return $this->radek["povoleno"];}	
} 

?>
