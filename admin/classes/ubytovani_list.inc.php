<?php
/** 
* serial_list.inc.php - trida pro zobrazeni seznamu serialu
*/

/*------------------- SEZNAM serialu -------------------  */

class Ubytovani_list extends Generic_list{
	//vstupni data
	protected $moznosti_editace;
	
	protected $typ;
	protected $podtyp;
	protected $nazev;
	protected $zeme;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR  -----------------	
/**konstruktor tøídy*/
	function __construct($typ, $podtyp, $nazev, $zeme, $zacatek, $order_by, $moznosti_editace="", $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		
	//kontrola vstupnich dat	
		$this->moznosti_editace = $this->check($moznosti_editace);
		$this->nazev = $this->check($nazev);
		$this->zacatek = $this->check_int($zacatek);
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu);

		
		if( $this->legal() ){		
			//ziskam celkovy pocet zajezdu ktere odpovidaji
			$data_pocet=$this->database->query($this->create_query("show",1))
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$zaznam_pocet = mysqli_fetch_array($data_pocet);
			$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

			
			
			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show"))
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			
			
			//kontrola zda jsme ziskali nejake zajezdy
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Pro zadané podmínky neexistuje žádný zájezd!");
			}
			
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
		
		//pokud chceme zobrazit seznam seriálù
		if($typ_pozadavku=="show"){

			if($this->nazev!=""){
				$where_serial=" `nazev` like '%".$this->nazev."%' and";
			}else{
				$where_serial=" ";
			}	
	
			
			if($this->zacatek!=0){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			
			$order=$this->order_by($this->order_by);

			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$select="select count(`ubytovani`.`id_ubytovani`) as pocet";
				$limit="";
			}else{
				$select="select `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev`";
			}
		
			//tvorba vysledneho dotazu
			$dotaz= $select." 
					from `ubytovani`
					where ".$where_serial." 1
					order by ".$order."
					 ".$limit."";
			//echo $dotaz;
			return $dotaz;
			
			//vypis vsech typu a podtypu zajezdu - potreba pro filtry
		}
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`ubytovani`.`id_ubytovani`";
   			 break;
			case "id_down":
				 return "`ubytovani`.`id_ubytovani` desc";
   			 break;			 				 			 
			case "nazev_up":
				 return "`ubytovani`.`nazev`";
   			 break;
			case "nazev_down":
				 return "`ubytovani`.`nazev` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`ubytovani`.`id_ubytovani`";
	}

	/**zobrazi formular pro filtorvani vypisu serialu*/
	function show_filtr(){
		//promenne, ktere musime pridat do odkazu
		$vars="&amp;moznosti_editace=".$this->moznosti_editace;
		
		//tvroba input nazev
		$input_nazev="<input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" />";
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";
		

			//vysledny formular		
			$vystup="
				<form method=\"post\" action=\"?typ=ubytovani_list&amp;pozadavek=change_filter&amp;pole=typ-podtyp-nazev".$vars."\">
				<table class=\"filtr\">
					<tr>
						<td>Název: ".$input_nazev."</td>
						<td>".$submit."</td>
					</tr>
				</table>
				</form>
			";
			return $vystup;
		
	}		
	
	/**zobrazi nadpis seznamu*/
	function show_header(){
		if($this->moznosti_editace=="select_serial_objednavky"){
			$vystup="
				<h3>Vyberte seriál objednávky</h3>
			";		
		}else{
			$vystup="
				<h3>Seznam ubytování</h3>
			";
		}
		return $vystup;
	}	
	
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
		if( !$this->get_error_message()){
			//promenne, ktere musime pridat do odkazu
			$vars="&amp;moznosti_editace=".$this->moznosti_editace;
		
			$vystup="
				<table class=\"list\">
					<tr>
						<th>id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=ubytovani_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$vars."\"></a>
							<a class='sort-down' href=\"?typ=ubytovani_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$vars."\"></a>
							</div>
						</th>
						<th>Název
						<div class='sort'>
							<a class='sort-up' href=\"?typ=ubytovani_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$vars."\"></a>
							<a class='sort-down' href=\"?typ=ubytovani_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$vars."\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}
	}
		/*z*obrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
		if( !$this->get_error_message()){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];
		
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			$vypis = $vypis."
							<td class=\"id\">".$this->radek["id_ubytovani"]."</td>
							<td class=\"nazev\">".$this->radek["nazev"]."</td>
							<td class=\"menu\">";			 
			if($this->moznosti_editace=="select_serial_objednavky"){
				$vypis = $vypis."
								<a href=\"".$adresa_modulu."?id_ubytovani=".$this->radek["id_ubytovani"]."&amp;typ=zajezd_list&amp;moznosti_editace=select_zajezd_objednavky\">vybrat ubytovani</a>";
			}else{					 
				$vypis = $vypis."
								<a href=\"".$adresa_modulu."?id_ubytovani=".$this->radek["id_ubytovani"]."&amp;typ=ubytovani&amp;pozadavek=edit\">ubytování</a>";
					
				if($adresa_foto = $core->get_adress_modul_from_typ("fotografie") ){	 			
					$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_ubytovani=".$this->radek["id_ubytovani"]."&amp;typ=foto\">foto</a>";
				}				
				$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_ubytovani=".$this->radek["id_ubytovani"]."&amp;typ=ubytovani&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>";
				
				$vypis = $vypis." <form style=\"font-size:0.9em;\" action=\"".$adresa_modulu."?id_ubytovani=".$this->radek["id_ubytovani"]."&amp;typ=ubytovani&amp;pozadavek=copy\" method=\"post\"><strong>kopírovat ubytování</strong>: nový název <input type=\"text\" name=\"nazev\" /><input type=\"submit\" value=\"&gt;&gt;\" /></form>";
				
			}
			$vypis = $vypis."
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
		
}
?>
