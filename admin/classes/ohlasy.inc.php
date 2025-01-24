<?php
/** 
* informace.inc.php - tridy pro zobrazeni dalsich informaci
*/

/*--------------------- SERIAL -------------------------------------------*/
class Ohlasy extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;
	
	protected $id_aktuality;
	protected $nazev;
	protected $popisek;
	protected $datum;
	protected $weby;
	protected $zobrazit;

	protected $id_user_create;
			
	protected $data;
	protected $informace;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_aktuality,$nazev="",$popisek="",$datum="",
								$weby="",$weby_navic="",$zobrazit="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
									
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_aktuality= $this->check_int($id_aktuality);
		$this->nazev = $this->check_slashes( $this->check_with_html($nazev) );
		$this->popisek = $this->check_slashes( $this->check_with_html($popisek) );
		$this->datum = $this->change_date_cz_en( $this->check($datum) );
		$this->weby = "";
                $this->weby_navic = $this->check($weby_navic);
		$this->zobrazit = $this->check_int($zobrazit);
			
		$i=0;	
		while($i<=40){
			$this->weby .= $this->check_slashes( $this->check($_POST["ohlasy_".$i])); 
			if($_POST["ohlasy_".$i]!=""){
				$this->weby .= ", ";
			}
			$i++;
		}	
		$this->weby .=	$this->weby_navic;
    
   // echo $this->weby; 
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
					//pokud vytvarime novy serial, ulozime si jeho id
					if($this->typ_pozadavku=="create"){
						$this->id_aktuality= mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
						$this->informace["id_aktuality"] = $this->id_aktuality;
					}

					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
											
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show") and $this->minuly_pozadavek!="update" ){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku,$this->id_zamestnance,$this->id_aktuality))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$this->informace=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_aktuality= $this->informace["id_ohlasu"];
						$this->nazev = $this->informace["nadpis"];
						$this->popisek = $this->informace["kr_popis"];
						$this->datum = $this->change_date_en_cz( $this->informace["datum"]);
						$this->weby = $this->informace["weby"];

						$this->zobrazit  = $this->informace["zobrazit"];						
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `ohlasy` 
							(`nadpis`,`kr_popis`,`datum`,`weby`,`zobrazit`)
						VALUES
							 ('".$this->nazev."','".$this->popisek."','".$this->datum."','".$this->weby."',
							 ".$this->zobrazit." )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `ohlasy` 
						SET
							`nadpis`='".$this->nazev."',`datum`='".$this->datum."',`kr_popis`='".$this->popisek."',`weby`='".$this->weby."',
							`zobrazit`=".$this->zobrazit."
						WHERE `id_ohlasu`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `ohlasy` 
						WHERE `id_ohlasu`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `ohlasy` 
						WHERE `id_ohlasu`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `ohlasy` 
						WHERE `id_ohlasu`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT * FROM `informace` 
						WHERE `id_informace`=".$this->informace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
		}
	}	
	
/**kontrola zda smim provest danou akci*/
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
				
		//podle jednotlivych typu pozadavku
		if($typ_pozadavku == "new"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
			
		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create"){
			return $zamestnanec->get_bool_prava($id_modul,"create");			

		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}			

		}else if($typ_pozadavku == "delete"){
			if( $zamestnanec->get_bool_prava($id_modul,"delete_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;
	}

	/**kontrola zda mam odpovidajici data*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovaná data: název informace, popisek
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit zájezd ohlasu");
			}
			if(!Validace::text($this->popisek) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit text ohlasu");
			}						
		
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
	/**zobrazeni menu - moznosti editace pro konkretni aktualitu*/
	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];

		$vypis = "<div class='submenu'>$this->nazev:
						<a href=\"".$adresa_modulu."?id_ohlasu=".$this->id_aktuality."&amp;typ=ohlasy&amp;pozadavek=edit\">edit</a>
						<a href=\"".$adresa_modulu."?id_ohlasu=".$this->id_aktuality."&amp;typ=foto\">foto</a>
						</div>";
		return $vypis;
	}
	/**zobrazeni formulare pro vytvoreni/editaci informace*/
	function show_form(){ 
                $restricted_weby = str_replace(array(",","slantour","poznavaci","dovolena","lazne","lyzovani","sport","exotika","_anglie","_francie","_italie","_nemecko","_chorvatsko","_slovensko","_skotsko","_spanelsko","_cr","_madarsko","_rakousko","_slovensko"
                        ,"_atletika","_formule","_motogp","_hokej","_fotbal","_tenis"), 
                        "", $this->weby);
		//vytvorim jednotliva pole
		$nazev="
					<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig60.js\"></script>
					<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>
					<div class=\"form_row\"> <div class=\"label_float_left\">nadpis (zájezd + datum zájezdu): <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nadpis\" type=\"text\" value=\"".$this->nazev."\" class=\"width-500px\"/></div></div>\n";
		$popisek="<div class=\"form_row\"> <div class=\"label_float_left\">text ohlasu: <span class=\"red\">*</span></div> <div class=\"value\"> <textarea name=\"text\" id=\"text_\" rows=\"5\" cols=\"100\">".$this->popisek."</textarea></div></div>\n";
		$datum="<div class=\"form_row\"> <div class=\"label_float_left\">datum: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"datum\" type=\"text\" value=\"".(($this->datum=="")?(Date("d.m.Y")):($this->datum))."\" class=\"wide\"/></div></div>\n";
		$weby="<div class=\"form_row\"> <div class=\"label_float_left\">weby: <span class=\"red\">*</span></div> <div class=\"value\"> 
			".$this->weby."<br/>
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"text_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
				</script>	
							
			<table cellpadding=5 cellspacing=\"10\">
			<tr>
				<th align=\"left\"><input name=\"ohlasy_0\" type=\"checkbox\" value=\"slantour\" checked=\"checked\"/>Slantour </th>
			</tr>
			<tr>
				<th align=\"left\"><input name=\"ohlasy_1\" type=\"checkbox\" value=\"poznavaci\" ".((stripos($this->weby,"poznavaci")!==FALSE)?("checked=\"checked\""):(""))."/>Poznávací zájezdy </th>
				<th align=\"left\"><input name=\"ohlasy_2\" type=\"checkbox\" value=\"dovolena\" ".((stripos($this->weby,"dovolena")!==FALSE)?("checked=\"checked\""):(""))."/>Dovolená </th>
				<th align=\"left\"><input name=\"ohlasy_3\" type=\"checkbox\" value=\"lazne\" ".((stripos($this->weby,"lazne")!==FALSE)?("checked=\"checked\""):(""))."/>Láznì </th>
				<th align=\"left\"><input name=\"ohlasy_4\" type=\"checkbox\" value=\"lyzovani\" ".((stripos($this->weby,"lyzovani")!==FALSE)?("checked=\"checked\""):(""))."/>Lyžování </th>
				<th align=\"left\"><input name=\"ohlasy_5\" type=\"checkbox\" value=\"sport\" ".((stripos($this->weby,"sport")!==FALSE)?("checked=\"checked\""):(""))."/>Sport </th>
                                <th align=\"left\"><input name=\"ohlasy_37\" type=\"checkbox\" value=\"exotika\" ".((stripos($this->weby,"exotika")!==FALSE)?("checked=\"checked\""):(""))."/>Exotika </th>
    
			</tr>
			<tr>
				<td valign=\"top\">
				<input name=\"ohlasy_6\" type=\"checkbox\" value=\"poznavaci_anglie\" ".((stripos($this->weby,"poznavaci_anglie")!==FALSE)?("checked=\"checked\""):(""))."/>Anglie <br/>
				<input name=\"ohlasy_7\" type=\"checkbox\" value=\"poznavaci_francie\" ".((stripos($this->weby,"poznavaci_francie")!==FALSE)?("checked=\"checked\""):(""))."/>Francie <br/>
				<input name=\"ohlasy_8\" type=\"checkbox\" value=\"poznavaci_italie\" ".((stripos($this->weby,"poznavaci_italie")!==FALSE)?("checked=\"checked\""):(""))."/>Itálie <br/>
				<input name=\"ohlasy_9\" type=\"checkbox\" value=\"poznavaci_nemecko\" ".((stripos($this->weby,"poznavaci_nemecko")!==FALSE)?("checked=\"checked\""):(""))."/>Nìmecko <br/>
				<input name=\"ohlasy_10\" type=\"checkbox\" value=\"poznavaci_chorvatsko\" ".((stripos($this->weby,"poznavaci_chorvatsko")!==FALSE)?("checked=\"checked\""):(""))."/>Chorvatsko <br/>
				<input name=\"ohlasy_11\" type=\"checkbox\" value=\"poznavaci_slovensko\" ".((stripos($this->weby,"poznavaci_slovensko")!==FALSE)?("checked=\"checked\""):(""))."/>Slovensko<br/>
				<input name=\"ohlasy_12\" type=\"checkbox\" value=\"poznavaci_skotsko\" ".((stripos($this->weby,"poznavaci_skotsko")!==FALSE)?("checked=\"checked\""):(""))."/>Skotsko <br/>
				<input name=\"ohlasy_13\" type=\"checkbox\" value=\"poznavaci_spanelsko\" ".((stripos($this->weby,"poznavaci_spanelsko")!==FALSE)?("checked=\"checked\""):(""))."/>Španìlsko <br/>
				</td>
				
				<td valign=\"top\">
				<input name=\"ohlasy_14\" type=\"checkbox\" value=\"dovolena_cr\" ".((stripos($this->weby,"dovolena_cr")!==FALSE)?("checked=\"checked\""):(""))."/>Èeská Republika <br/>
				<input name=\"ohlasy_15\" type=\"checkbox\" value=\"dovolena_francie\" ".((stripos($this->weby,"dovolena_francie")!==FALSE)?("checked=\"checked\""):(""))."/>Francie <br/>
				<input name=\"ohlasy_16\" type=\"checkbox\" value=\"dovolena_italie\" ".((stripos($this->weby,"dovolena_italie")!==FALSE)?("checked=\"checked\""):(""))."/>Itálie <br/>
				<input name=\"ohlasy_17\" type=\"checkbox\" value=\"dovolena_chorvatsko\" ".((stripos($this->weby,"dovolena_chorvatsko")!==FALSE)?("checked=\"checked\""):(""))."/>Chorvatsko <br/>
				<input name=\"ohlasy_18\" type=\"checkbox\" value=\"dovolena_madarsko\" ".((stripos($this->weby,"dovolena_madarsko")!==FALSE)?("checked=\"checked\""):(""))."/>Maïarsko <br/>
				<input name=\"ohlasy_19\" type=\"checkbox\" value=\"dovolena_rakousko\" ".((stripos($this->weby,"dovolena_rakousko")!==FALSE)?("checked=\"checked\""):(""))."/>Rakousko <br/>
				<input name=\"ohlasy_20\" type=\"checkbox\" value=\"dovolena_slovensko\" ".((stripos($this->weby,"dovolena_slovensko")!==FALSE)?("checked=\"checked\""):(""))."/>Slovensko <br/>
				<input name=\"ohlasy_21\" type=\"checkbox\" value=\"dovolena_spanelsko\" ".((stripos($this->weby,"dovolena_spanelsko")!==FALSE)?("checked=\"checked\""):(""))."/>Španìlsko <br/>
				</td>
				<td valign=\"top\">
				<input name=\"ohlasy_22\" type=\"checkbox\" value=\"lazne_cr\" ".((stripos($this->weby,"lazne_cr")!==FALSE)?("checked=\"checked\""):(""))."/>Èeská Republika <br/>
				<input name=\"ohlasy_23\" type=\"checkbox\" value=\"lazne_madarsko\" ".((stripos($this->weby,"lazne_madarsko")!==FALSE)?("checked=\"checked\""):(""))."/>Maïarsko <br/>
	 			<input name=\"ohlasy_24\" type=\"checkbox\" value=\"lazne_rakousko\" ".((stripos($this->weby,"lazne_rakousko")!==FALSE)?("checked=\"checked\""):(""))."/>Rakousko <br/>
				<input name=\"ohlasy_25\" type=\"checkbox\" value=\"lazne_slovensko\" ".((stripos($this->weby,"lazne_slovensko")!==FALSE)?("checked=\"checked\""):(""))."/>Slovensko <br/>				
				</td>
				<td valign=\"top\">
				<input name=\"ohlasy_26\" type=\"checkbox\" value=\"lyzovani_cr\" ".((stripos($this->weby,"lyzovani_cr")!==FALSE)?("checked=\"checked\""):(""))."/>Èeská Republika <br/>
				<input name=\"ohlasy_27\" type=\"checkbox\" value=\"lyzovani_francie\" ".((stripos($this->weby,"lyzovani_francie")!==FALSE)?("checked=\"checked\""):(""))."/>Francie <br/>
				<input name=\"ohlasy_28\" type=\"checkbox\" value=\"lyzovani_italie\" ".((stripos($this->weby,"lyzovani_italie")!==FALSE)?("checked=\"checked\""):(""))."/>Itálie <br/>
				<input name=\"ohlasy_29\" type=\"checkbox\" value=\"lyzovani_rakousko\" ".((stripos($this->weby,"lyzovani_rakousko")!==FALSE)?("checked=\"checked\""):(""))."/>Rakousko <br/>
				<input name=\"ohlasy_30\" type=\"checkbox\" value=\"lyzovani_slovensko\" ".((stripos($this->weby,"lyzovani_slovensko")!==FALSE)?("checked=\"checked\""):(""))."/>Slovensko <br/>					
				</td>
				<td valign=\"top\">
				<input name=\"ohlasy_31\" type=\"checkbox\" value=\"sport_atletika\" ".((stripos($this->weby,"sport_atletika")!==FALSE)?("checked=\"checked\""):(""))."/>Atletika <br/>
				<input name=\"ohlasy_32\" type=\"checkbox\" value=\"sport_formule\" ".((stripos($this->weby,"sport_formule")!==FALSE)?("checked=\"checked\""):(""))."/>Formule <br/>
				<input name=\"ohlasy_33\" type=\"checkbox\" value=\"sport_motogp\" ".((stripos($this->weby,"sport_motogp")!==FALSE)?("checked=\"checked\""):(""))."/>Moto GP <br/>
				<input name=\"ohlasy_34\" type=\"checkbox\" value=\"sport_hokej\" ".((stripos($this->weby,"sport_hokej")!==FALSE)?("checked=\"checked\""):(""))."/>Hokej <br/>
				<input name=\"ohlasy_35\" type=\"checkbox\" value=\"sport_fotbal\" ".((stripos($this->weby,"sport_fotbal")!==FALSE)?("checked=\"checked\""):(""))."/>Fotbal <br/>
				<input name=\"ohlasy_36\" type=\"checkbox\" value=\"sport_tenis\" ".((stripos($this->weby,"sport_tenis")!==FALSE)?("checked=\"checked\""):(""))."/>Tenis <br/>
				</td>
			</tr>
                        <tr>
                            <td colspan=\"5\">Další zemì: <input type=\"text\" name=\"weby_navic\" value=\"".$restricted_weby."\" />* oddìlujte mezerou, !!!nepoužívejte diakritiku!!!</td> 
                        </tr>
			</table>
		
		</div></div>\n
		
		
		
		";
		$zobrazit="<div class=\"form_row\"> <div class=\"label_float_left\">zobrazit ohlas: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"zobrazit\" type=\"checkbox\" value=\"1\" ".(($this->zobrazit==1)?(" checked=\"checked\" "):(" "))."/></div></div>\n";
		
		
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=ohlasy&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit ohlas\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení ohlasu</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_ohlasu=".$this->id_aktuality."&amp;typ=ohlasy&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit ohlas\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci teto ohlasu</strong>\n";
			}
		}		

				
		$vystup= "<form action=\"".$action."\" method=\"post\">".
						$nazev.$popisek.$popis.$datum.$weby.$zobrazit.
						$submit.
					"</form>";
		return $vystup;
	}
	
	function get_id() { return $this->id_aktuality;}
	function get_nazev() { return $this->informace["nadpis"];}
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_informace == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}
} 




?>
