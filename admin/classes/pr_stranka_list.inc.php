<?php
/**
* dokument_list.inc.php - trida pro zobrazeni seznamu dokumentù
*/

/*------------------- SEZNAM dokumentù -------------------  */

class PrStranka_list extends Generic_list{
	//vstupni data
	protected $id_zamestnance;
	protected $nazev;
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
				$where_nazev=" `nazev` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `pr_stranky`.`id_pr_stranky`";
			}
			if($only_count==1){
				$select="select count(`id_pr_stranky`) as pocet";
				$limit="";
			}else{
				$select="select * ";
			}

			$dotaz=	$select."
					  from  `pr_stranky` 
						where ".$where_nazev." 1
						order by ".$order." ".
						$limit;
//			echo $dotaz;
			return $dotaz;
		}
	}

	/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`id_pr_stranky` ";
   			 break;
			case "id_down":
				 return "`id_pr_stranky` desc";
   			 break;
			case "nazev_up":
				 return "`nazev`";
   			 break;
			case "nazev_down":
				 return "`nazev` desc";
   			 break;
			case "nadpis_up":
				 return "`nadpis`";
   			 break;
			case "nadpis_down":
				 return "`nadpis` desc";
   			 break;
		}
		return "`id_pr_stranky`";
	}

	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
			$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
			$_GET["id_informace"]?($serial="&amp;id_informace=".$_GET["id_informace"]." "):($serial="");
			$_GET["id_aktuality"]?($serial="&amp;id_aktuality=".$_GET["id_aktuality"]." "):($serial="");

		//tvroba input nazev
		$input_nazev="<input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" />";
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";

		//vysledny formular
		$vystup="
			<form method=\"post\" action=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>Název PR stránky: ".$input_nazev."</td>
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
				    <colgroup><col width='4%'><col width='15%'><col width='16%'><col width='10%'><col width='40%'><col width='15%'></colgroup>
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Název
						<div class='sort'>
							<a class='sort-up' href=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>						
						<th>Nadpis
						<div class='sort'>
							<a class='sort-up' href=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nadpis_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=pr_stranka_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nadpis_down".$serial."\"></a>
							</div>
						</th>
						<th>URL
						</th>
                                                <th>Použito v
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

		if($typ_zobrazeni=="tabulka_pr_stranka"){
			//zobrazeni v editaci dokumentu (dokumenty.php)
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}

			$vypis=$vypis."
							<td  class=\"id\">
								".$this->get_id_pr_stranky()."
							</td>
							<td  class=\"datum\">
								".$this->get_nazev()."
							</td>									
							<td  class=\"nazev\">
									".$this->get_nadpis()."
							</td>
							<td class=\"popisek\">".$this->get_adresa()."</td>
                                                        <td class=\"popisek\">".$this->get_adresy_list()."</td>
							<td class=\"menu\">
								<a href=\"".$adresa_modulu."?id_pr_stranky=".$this->get_id_pr_stranky()."&amp;typ=pr_stranka&amp;pozadavek=edit\">editovat PR stránku</a>
                                                                | <a href=\"".$adresa_modulu."?id_pr_stranky=".$this->get_id_pr_stranky()."&amp;typ=foto\">foto</a>
                                                                | <a class='anchor-delete' href=\"".$adresa_modulu."?id_pr_stranky=".$this->get_id_pr_stranky()."&amp;typ=pr_stranka&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
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
	function get_id_pr_stranky() { return $this->radek["id_pr_stranky"];}
	function get_nazev() { return $this->radek["nazev"];}
        function get_nadpis() { return $this->radek["nadpis"];}
        function get_adresa() { return $this->radek["adresa"];}
        function get_adresy_list() { return $this->radek["adresy_list"];}

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
