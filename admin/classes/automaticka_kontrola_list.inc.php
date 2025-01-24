<?php
/** 
* dokument_list.inc.php - trida pro zobrazeni seznamu dokumentù
*/

/*------------------- SEZNAM dokumentù -------------------  */

class Automaticka_kontrola_list extends Generic_list{
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
	function __construct($id_zamestnance, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);

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

                        
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `automaticka_kontrola`.`id_kontrola` desc";
			}		
			if($only_count==1){
				$select="select count(`id_kontrola`) as pocet";
				$limit="";
			}else{
				$select="select * ";
			}
		
			$dotaz=	$select."
					  from  `automaticka_kontrola` 
						where  `date_from` >= \"".Date("Y-m-d")."\"
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
				 return "`id_kontrola` ";
   			 break;
			case "id_down":
				 return "`id_kontrola` desc";
   			 break;			
			case "datum_up":
				 return "`date_check`";
   			 break;
			case "datum_down":
				 return "`date_check` desc";
   			 break;
			case "datum_odlet_up":
				return "`date_from`";
				break;
			case "datum_odlet_down":
				return "`date_from` desc";
				break;
			case "datum_prilet_up":
				return "`date_to`";
				break;
			case "datum_prilet_down":
				return "`date_to` desc";
				break;
			case "to_up":
				 return "`flight_to`";
   			 break;
			case "to_down":
				 return "`flight_to` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`id_kontrola`";
	}

	
	/**zobrazi hlavicku k seznamu*/
	function show_list_header(){
		if( !$this->get_error_message()){
			//predani id_serial (pokud existuje - editace serialu->foto)
			$serial="";

			$vystup="
				<table class=\"list\">
					<tr>
						<th>Id
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Datum kontroly
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_down".$serial."\"></a>
							</div>
						</th>						
						<th>Odlet - pøílet
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=to_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=to_down".$serial."\"></a>
							</div>
						</th>
						<th>Datum odletu
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_odlet_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_odlet_down".$serial."\"></a>
							</div>
						</th>	
						<th>Datum pøíletu
						    <div class='sort'>
							<a class='sort-up' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_prilet_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=kontroly_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=datum_prilet_down".$serial."\"></a>
							</div>
						</th>	
						<th>Pùvodní cena
						</th>
						<th>Nová cena
						</th>
						<th>Poznámky
						</th>
						<th>
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

			if($typ_zobrazeni=="tabulka"){
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
									".$this->radek["id_kontrola"]."
								</td>
								<td  class=\"datum\">
									".$this->change_date_en_cz($this->radek["date_check"])."
								</td>	
								<td  class=\"datum\">
									".$this->radek["flight_from"]." - ".$this->radek["flight_to"]." 
								</td>						
								<td  class=\"datum\">
									".$this->change_date_en_cz($this->radek["date_from"])."
								</td>	
								<td  class=\"datum\">
									".$this->change_date_en_cz($this->radek["date_to"])."
								</td>	
								<td  class=\"id\">
									".$this->radek["price_from"]." Kè
								</td>
								<td  class=\"id\">
									".$this->radek["price_to"]." Kè
								</td>	
								
								<td class=\"popisek\">
									".$this->radek["note"]."
								</td>
								<td class=\"menu\">
								  <a href=\"/admin/objekty.php?id_objektu=".$this->radek["id_objektu"]."&typ=tok_list&pozadavek=show_letuska\">Editovat Objekt</a> 
								  |  <a class='anchor-delete' href=\"".$adresa_modulu."?id_kontrola=".$this->radek["id_kontrola"]."&amp;typ=kontrola&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
								</td>
							</tr>";
					return $vypis;
				}


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
