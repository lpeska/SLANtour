<?php
/** 
* dokument_list.inc.php - trida pro zobrazeni seznamu dokumentù
*/

/*------------------- SEZNAM dokumentù -------------------  */

class Preorder_list extends Generic_list{
	//vstupni data
	protected $id_zamestnance;
	protected $nazev;
	protected $objednavka;	
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
	
	public $database; //trida pro odesilani dotazu
		
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($id_zamestnance, $nazev, $objednavka, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->nazev = $this->check($nazev);//odpovida poli nazev
		$this->objednavka = $this->check($objednavka);//odpovida poli nazev		
		$this->zacatek = $this->check_int($zacatek); 
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu); 
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal()){
			//ziskam celkovy pocet zajezdu ktere odpovidaji
			$data_pocet=$this->database->query($this->create_query("show",1))
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				$zaznam_pocet = mysqli_fetch_array($data_pocet);
				$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
			}							
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu do databaze*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku=="show"){
			//definice jednotlivych casti dotazu
			if($this->nazev!=""){
				$where_serial=" `serial`.`nazev` like \"%".$this->nazev."%\" and";
			}else{
				$where_serial=" ";
			}		
			if($this->objednavka!=""){
				$where_objednavka=" `objednavka` like \"%".$this->objednavka."%\" and";
			}else{
				$where_objednavka=" ";
			}				
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `id_predregistrace`";
			}		
			if($only_count==1){
				$select="select count(`id_predregistrace`) as pocet";
				$limit="";
			}else{
				$select="select `predregistrace`.*, `serial`.`nazev` ";
			}
		
			$dotaz=	$select."
					  from  `predregistrace` join `serial` on (`predregistrace`.`id_serial` = `serial`.`id_serial`)
						where ".$where_serial." ".$where_objednavka." 1 
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
				 return "`id_predregistrace` ";
   			 break;
			case "id_down":
				 return "`id_predregistrace` desc";
   			 break;			
			case "jmeno_up":
				 return "`prijmeni`";
   			 break;
			case "jmeno_down":
				 return "`prijmeni` desc";
   			 break;					 	 
			case "nazev_up":
				 return "`nazev`";
   			 break;
			case "nazev_down":
				 return "`nazev` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`id_predregistrace`";
	}
	
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
			$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
			$_GET["id_informace"]?($serial="&amp;id_informace=".$_GET["id_informace"]." "):($serial="");
			$_GET["id_aktuality"]?($serial="&amp;id_aktuality=".$_GET["id_aktuality"]." "):($serial="");
		
		//tvroba input nazev
		$input_nazev="<input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" />";
		$input_objednavka="<input name=\"objednavka\" type=\"text\" value=\"".$this->objednavka."\" />";
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
				
		//vysledny formular			
		$vystup="
			<form method=\"post\" action=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>Název seriálu: ".$input_nazev."</td>
					<td>Požadovaná akce: ".$input_objednavka."</td>
					<td>".$submit."</td>
				</tr>
			</table>
			</form>
		";
		return $vystup;		
	}		
	
	/**zobrazi hlavicku k seznamu*/
	function show_list_header(){
		if( !$this->get_error_message()){
			//predani id_serial (pokud existuje - editace serialu->foto)
			$serial="";
			if($_GET["id_serial"]){
				$serial="&amp;id_serial=".$_GET["id_serial"]." ";
			}else if($_GET["id_informace"]){
				$serial="&amp;id_informace=".$_GET["id_informace"]." ";
			}else if($_GET["id_aktuality"]){
				$serial="&amp;id_aktuality=".$_GET["id_aktuality"]." ";
			}

			$vystup="
			<div class='submenu'>Poèet nalezených záznamù: ".$this->pocet_zajezdu."</div>
				<table class=\"list\">
				    <colgroup><col width='7%'><col width='25%'><col width='13%'><col width='45%'><col width='10%'></coulgroup>
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Název seriálu
						<div class='sort'>
							<a class='sort-up' href=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>						
						<th>Jméno a pøíjmení
						<div class='sort'>
							<a class='sort-up' href=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=jmeno_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=preorder_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=jmeno_down".$serial."\"></a>
							</div>
						</th>						
						<th>Objednávka
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}
	}		
	
	function show_sumary(){
		$ret = "";
		if( !$this->get_error_message()){
			$pole_sportu = array();
			$pole_serialu = array();
			while($this->get_next_radek()){
				$objednavky = explode(",", $this->get_objednavka() );
				foreach($objednavky as $name){
					$name = trim($name);
					if($name!=""){
						$value = $pole_sportu[$name];
						$value++; 
						$pole_sportu[$name] = $value;
					}
				}
				$val2 = $pole_serialu[$this->get_nazev()];
				$val2++;
				$pole_serialu[$this->get_nazev()] = $val2;				
			}	
			ksort($pole_sportu);
			ksort($pole_serialu);
			$ret .= "<table class=\"list\">
			    <colgroup><col width='80%'><col width='20%'></coulgroup>
				<tr>
				<th>Seriál</th>
				<th>Poèet objednávek</th>
				</tr>";
			foreach($pole_serialu as $name => $value){
				$ret .= "
					<tr class=\"suda\"><td>
						".$name."
					</td>
					<td>
						".$value." objednávek
					</td></tr>";
			}
			$ret .= "</table>";

			$ret .= "<table class=\"list\">
			    <colgroup><col width='80%'><col width='20%'></coulgroup>
				<tr>
				<th>Odvìtví/vstupenky/...</th>
				<th>Poèet objednávek</th>
				</tr>";
			foreach($pole_sportu as $name => $value){
				$ret .= "
					<tr class=\"suda\"><td>
						".$name."
					</td>
					<td>
						".$value." objednávek
					</td></tr>";
			}
			$ret .= "</table>";
				
		}
		return $ret;
	}
	
	/**zobrazi polozku ze seznamu*/
	function show_list_item($typ_zobrazeni){	
		if( !$this->get_error_message()){
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka_serial"){
		//zobrazeni v editaci serialu->dokumenty  (serial.php)
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
				GLOBAL $serial;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
			
				$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id()."
							</td>
							<td  class=\"datum\">
								".$this->get_nazev()."
							</td>							
							<td  class=\"nazev\">
								".$this->get_prijmeni()." ".$this->get_jmeno()."
							</td>
							<td class=\"popisek\">
								".$this->get_objednavka()."
							</td>
							<td class=\"menu\">							  
							</td>
						</tr>";
				return $vypis;
			}		
		}//no error message
	}	
	
	/**zobrazi odkazy na dalsi stranky vypisu*/
	function show_strankovani(){
		if( $this->pocet_zajezdu != 0 and $this->pocet_zaznamu != 0){
			//prvni cislo stranky ktere zobrazime
			$act_str=$this->zacatek-(10*$this->pocet_zaznamu);
			if($act_str<0){
				$act_str=0;
			}
		
			//zjistim vsechny dalsi promenne, odstranim z nich ale str
			$promenne= ereg_replace("str=[0-9]*&?","",$_SERVER["QUERY_STRING"]);
		
			//odkaz na prvni stranku
			$vypis = "<div class=\"strankovani\"><a href=\"?str=0&amp;".$promenne."\" title=\"první stránka\">&lt;&lt;</a> &nbsp;"; 
		
			//odkaz na dalsi stranky z rozsahu
			while(($act_str < $this->pocet_zajezdu) and ($act_str <= $this->zacatek+(10*$this->pocet_zaznamu))){
				if($this->zacatek!=$act_str){
					$vypis = $vypis."<a href=\"?str=".$act_str."&amp;".$promenne."\" title=\"strana ".(1+($act_str/$this->pocet_zaznamu))."\">".(1+($act_str/$this->pocet_zaznamu))."</a> ";					
				}else{
					$vypis = $vypis.(1+($act_str/$this->pocet_zaznamu))." ";
				}
				$act_str=$act_str+$this->pocet_zaznamu;
			}	
		
			//odkaz na posledni stranku
			$posl_str=$this->pocet_zaznamu*floor(($this->pocet_zajezdu-1)/$this->pocet_zaznamu);
				$vypis = $vypis." &nbsp; <a href=\"?str=".$posl_str."&amp;".$promenne."\" title=\"poslední stránka\">&gt;&gt;</a></div>";	
		
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
	function get_id() { return $this->radek["id_predregistrace"];}
	function get_jmeno() { return $this->radek["jmeno"] ; }	
	function get_prijmeni() { return  $this->radek["prijmeni"] ; }	
	function get_nazev() { return $this->radek["nazev"];}
	function get_objednavka() { return $this->radek["objednavka"];}
	function get_email() { return $this->radek["email"];}
	function get_telefon() { return $this->radek["telefon"];}

    public function getZacatek()
    {
        return $this->zacatek;
    }

    public function getPocetZajezdu()
    {
        return $this->pocet_zajezdu;
    }

    public function getPocetZaznamu()
    {
        return $this->pocet_zaznamu;
    }
} 

?>
