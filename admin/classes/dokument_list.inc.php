<?php
/** 
* dokument_list.inc.php - trida pro zobrazeni seznamu dokumentù
*/

/*------------------- SEZNAM dokumentù -------------------  */

class Dokument_list extends Generic_list{
	//vstupni data
	protected $id_zamestnance;
	protected $nazev;
        protected $datum_od;
        protected $datum_do;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	protected $zobrazeni;
	protected $pocet_zajezdu;
	
	public $database; //trida pro odesilani dotazu
		
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($id_zamestnance, $nazev, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->nazev = $this->check($nazev);//odpovida poli nazev
                $this->datum_od = $this->change_date_cz_en($this->check($_SESSION["dokument_datum_od"]));
                $this->datum_do = $this->change_date_cz_en($this->check($_SESSION["dokument_datum_do"]));
                
		$this->zacatek = $this->check_int($zacatek); 
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu); 
		$this->zobrazeni = $this->check($zobrazeni);
                
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
				$where_nazev=" `nazev_dokument` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}	
                        if($this->datum_od!=""){
				$where_datum_od=" `datum_vytvoreni` >= '".$this->datum_od."' and";
			}else{
				$where_datum_od=" ";
			}
                        if($this->datum_do!=""){
				$where_datum_do=" `datum_vytvoreni` <= '".$this->datum_do."' and";
			}else{
				$where_datum_do=" ";
			}
                        
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `dokument`.`id_dokument`";
			}		
			if($only_count==1){
				$select="select count(`id_dokument`) as pocet";
				$limit="";
			}else{
				$select="select * ";
			}
		
			$dotaz=	$select."
					  from  `dokument` 
						where ".$where_nazev.$where_datum_od.$where_datum_do." 1 
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
				 return "`id_dokument` ";
   			 break;
			case "id_down":
				 return "`id_dokument` desc";
   			 break;			
			case "datum_up":
				 return "`datum_vytvoreni`";
   			 break;
			case "datum_down":
				 return "`datum_vytvoreni` desc";
   			 break;					 	 
			case "nazev_up":
				 return "`nazev_dokument`";
   			 break;
			case "nazev_down":
				 return "`nazev_dokument` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`id_dokument`";
	}
	
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
			$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
			$_GET["id_informace"]?($info="&amp;id_informace=".$_GET["id_informace"]." "):($info="");
			$_GET["id_aktuality"]?($aktual="&amp;id_aktuality=".$_GET["id_aktuality"]." "):($aktual="");
		$serial = $serial.$info.$aktual;
		//tvroba input nazev
		$input_nazev="<input name=\"nazev_dokument\" type=\"text\" value=\"".$this->nazev."\" />";
		$input_datum_od="<input name=\"dokument_datum_od\" class=\"calendar-ymd\" type=\"text\" value=\"".$this->change_date_en_cz($this->datum_od)."\" size=\"10\"/>";
		$input_datum_do="<input name=\"dokument_datum_do\" class=\"calendar-ymd\" type=\"text\" value=\"".$this->change_date_en_cz($this->datum_do)."\" size=\"10\"/>";                
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
				
		//vysledny formular			
		$vystup="
			<form method=\"post\" action=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
                                        <td>Datum vytvoøení od: ".$input_datum_od." do: ".$input_datum_do."</td>
					<td>Název dokumentu: ".$input_nazev."</td>
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
				<table class=\"list\">
					<tr>
						<th>Id
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Datum
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_down".$serial."\"></a>
							</div>
						</th>						
						<th>Název
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=dokument_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>
						<th>Popisek
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}
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
			if($adresa_serial = $core->get_adress_modul_from_typ("serial") ){
				GLOBAL $serial;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
			
				$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_dokument()."
							</td>
							<td  class=\"datum\">
								".$this->get_datum()."
							</td>							
							<td  class=\"nazev\">
								<a href=\"/".ADRESAR_DOKUMENT."/".$this->get_dokument_url()."\">
									".$this->get_nazev_dokument()."
								</a>
							</td>
							<td class=\"popisek\">
								".$this->get_popisek_dokument()."
							</td>
							<td class=\"menu\">
							  <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=create\">pøidat</a>
							</td>
						</tr>";
				return $vypis;
			}

		}else if($typ_zobrazeni=="tabulka_aktuality"){
		//zobrazeni v editaci serialu->dokumenty  (serial.php)
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($adresa_aktuality = $core->get_adress_modul_from_typ("aktuality") ){
				GLOBAL $aktuality;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
			
				$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_dokument()."
							</td>
							<td  class=\"datum\">
								".$this->get_datum()."
							</td>									
							<td  class=\"nazev\">
								<a href=\"/".ADRESAR_DOKUMENT."/".$this->get_dokument_url()."\">
									".$this->get_nazev_dokument()."
								</a>
							</td>
							<td class=\"popisek\">
								".$this->get_popisek_dokument()."
							</td>
							<td class=\"menu\">
							  <a href=\"".$adresa_aktuality."?id_aktuality=".$aktuality->get_id()."&amp;id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=create\">pøidat</a>
							</td>
						</tr>";
				return $vypis;
			}
			
		}else	if($typ_zobrazeni=="tabulka_export"){
			//zobrazeni v editaci dokumentu (dokumenty.php)
                        $adresa_dokumenty = $core->get_adress_modul_from_typ("dokumenty");
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}

			$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_dokument()."
							</td>
							<td  class=\"datum\">
								".$this->get_datum()."
							</td>
							<td  class=\"nazev\">
								<a href=\"".$this->get_dokument_url()."\">
									".$this->get_nazev_dokument()."
								</a>
							</td>
							<td class=\"popisek\">".$this->get_popisek_dokument()."</td>
							<td class=\"menu\">
								<a href=\"".$adresa_dokumenty."?id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=edit\">editovat dokument</a>
							    |  <a class='anchor-delete' href='$adresa_dokumenty?id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=delete' onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";
			return $vypis;
		}else	if($typ_zobrazeni=="tabulka_dokument"){
			//zobrazeni v editaci dokumentu (dokumenty.php)
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}

            if($this->isAssignedToSerial()) {
                $htmlBtnSerialy = "<a href=\"".$adresa_modulu."?id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=serialy_dokumenty\">seriály</a>";
            } else {
                $htmlBtnSerialy = "<a class='action disabled'>seriály</a>";
            }
			$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_dokument()."
							</td>
							<td  class=\"datum\">
								".$this->get_datum()."
							</td>									
							<td  class=\"nazev\">
								<a href=\"/".ADRESAR_DOKUMENT."/".$this->get_dokument_url()."\">
									".$this->get_nazev_dokument()."
								</a>
							</td>
							<td class=\"popisek\">".$this->get_popisek_dokument()."</td>
							<td class=\"menu\">
								<a href=\"".$adresa_modulu."?id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=edit\">editovat dokument</a>
								| $htmlBtnSerialy
							    |  <a class='anchor-delete' href=\"".$adresa_modulu."?id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							    |  <input value='" . $this->get_id_dokument() . "' type='checkbox' onchange='markUnmarkToDel(this);' />
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
	function get_id_dokument() { return $this->radek["id_dokument"];}
	function get_datum() { return $this->change_date_en_cz( $this->radek["datum_vytvoreni"] ); }	
	function get_nazev_dokument() { return $this->radek["nazev_dokument"];}
	function get_popisek_dokument() { return $this->radek["popisek_dokument"];}
	function get_dokument_url() { return $this->radek["dokument_url"];}

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

    private function isAssignedToSerial()
    {
        $_GET['id_dokument'] = $this->get_id_dokument();
        $serialy_list = new Serial_list(0, 0, "nazev", 0, 1, "s.nazev", null, 10, "serialy-dokumenty");

        //nenech se zmast - pres svuj nazev "pocet zajezdu" - vraci pocet nactenych zaznamu z db - narozdil od metody pocetZaznamu(), ktera vraci ~ pocet zaznamu z hlediska strankovani :)
        return $serialy_list->getPocetZajezdu() > 0;
    }
} 

?>
