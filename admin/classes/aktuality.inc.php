<?php
/** 
* informace.inc.php - tridy pro zobrazeni dalsich informaci
*/

/*--------------------- SERIAL -------------------------------------------*/
class Aktuality extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;
	
	protected $id_aktuality;
	protected $nazev;
	protected $popisek;
	protected $datum;
        protected $top_nabidka;
	protected $weby;
        protected $odkazy;
	protected $zobrazit;

	protected $id_user_create;
			
	protected $data;
	protected $informace;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_aktuality,$nazev="",$popisek="",$datum="", $top_nabidka="",
								$weby="",$zobrazit="",$odkazy="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
									
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_aktuality= $this->check_int($id_aktuality);
                $this->top_nabidka= $this->check_int($top_nabidka);
		$this->nazev = $this->check_slashes( $this->check_with_html($nazev) );
		$this->popisek = $this->check_slashes( $this->check_with_html($popisek) );
		$this->datum = $this->change_date_cz_en( $this->check($datum) );
		$this->weby_navic = $this->check($weby);
                $this->odkazy= $this->check_slashes( $this->check_with_html($odkazy) );
		$this->zobrazit = $this->check_int($zobrazit);
			
                //zaskrtavatka
                $this->weby = "";
                $i=0;
                while($i<=40){
			$this->weby .= $this->check_slashes( $this->check($_POST["aktuality_".$i])); 
			if($_POST["aktuality_".$i]!=""){
				$this->weby .= ", ";
			}
			$i++;
		}	
                $this->weby_navic = $this->check($weby);
                $this->weby .=	$this->weby_navic;
                
                if($this->odkazy=="https://"){
                   $this->odkazy=""; 
                }
                
                $centralni_data = $this->database->query($this->create_query("get_centralni_data"))
                        or $this->chyba("Chyba pøi dotazu do databáze central data: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //nacteme centralni data do pole
                while ($row = mysqli_fetch_array($centralni_data)) {
                    $this->centralni_data[$row["nazev"]] = $row["text"];
                }
                
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
						$this->id_aktuality= $this->informace["id_aktuality"];
                                                $this->top_nabidka= $this->informace["top_nabidka"];
                                                $this->odkazy= $this->informace["odkazy"];
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
			$dotaz= "INSERT INTO `aktuality` 
							(`nadpis`,`kr_popis`,`datum`,`top_nabidka`,`weby`,`zobrazit`,`odkazy`)
						VALUES
							 ('".$this->nazev."','".$this->popisek."','".$this->datum."',".$this->top_nabidka.",'".$this->weby."',
							 ".$this->zobrazit.", '".$this->odkazy."' )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `aktuality` 
						SET
							`nadpis`='".$this->nazev."',`datum`='".$this->datum."',`kr_popis`='".$this->popisek."',`weby`='".$this->weby."',
							`top_nabidka`=".$this->top_nabidka.",`odkazy`='".$this->odkazy."', `zobrazit`=".$this->zobrazit."
						WHERE `id_aktuality`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
                } else if ($typ_pozadavku == "get_centralni_data") {
                    $dotaz = "SELECT * FROM `centralni_data` 
                                                    WHERE 1
                            ";
                    //echo $dotaz;
                    return $dotaz;        
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `aktuality` 
						WHERE `id_aktuality`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `aktuality` 
						WHERE `id_aktuality`=".$this->id_aktuality."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `aktuality` 
						WHERE `id_aktuality`=".$this->id_aktuality."
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
				$this->chyba("Musíte vyplnit název aktuality");
			}
			if(!Validace::text($this->popisek) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit popisek aktuality");
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
						<a href=\"".$adresa_modulu."?id_aktuality=".$this->id_aktuality."&amp;typ=aktuality&amp;pozadavek=edit\">edit</a>
						<a href=\"".$adresa_modulu."?id_aktuality=".$this->id_aktuality."&amp;typ=foto\">foto</a>
						<a href=\"".$adresa_modulu."?id_aktuality=".$this->id_aktuality."&amp;typ=dokument\">dokumenty</a>
						</div>
						";					 	
		return $vypis;
	}
        /*pøepis jmen webù*/
        function get_name($name){
            switch ($name) {
                case "poznavaci":
                    return "Poznávací zájezdy";
                    break;
                case "dovolena":
                    return "Dovolená";
                    break;
                case "lazne":
                    return "Láznì";
                    break;
                case "lyzovani":
                    return "Lyžování";
                    break;
                case "sport":
                    return "Sport";
                    break;
                case "exotika":
                    return "Exotika";
                    break;
                default:
                    return $name;
                    break;
            }
        }
	/**zobrazeni formulare pro vytvoreni/editaci informace*/
	function show_form(){
		$restricted_weby = str_replace(array(",","slantour","poznavaci","dovolena","lazne","lyzovani","sport","exotika","_anglie","_francie","_italie","_nemecko","_chorvatsko","_slovensko","_skotsko","_spanelsko","_cr","_madarsko","_rakousko","_slovensko"
                        ,"_atletika","_formule","_motogp","_hokej","_fotbal","_tenis","_olympiada"), 
                        "", $this->weby);
		//vytvorim jednotliva pole
		$nazev="
					<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig63.js\"></script>
                                        <script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>
					<div class=\"form_row\"> <div class=\"label_float_left\">název aktuality: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nadpis\" type=\"text\" value=\"".$this->nazev."\" class=\"wide\"/></div></div>\n";
		$popisek="<div class=\"form_row\"> <div class=\"label_float_left\">popisek: <span class=\"red\">*</span></div> <div class=\"value\"> <textarea name=\"popisek\" id=\"popisek_\" rows=\"5\" cols=\"100\">".$this->popisek."</textarea></div></div>\n
                    <script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"popisek_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
				</script>	";
		$datum="<div class=\"form_row\"> <div class=\"label_float_left\">datum: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"datum\" type=\"text\" value=\"".(($this->datum=="")?(Date("d.m.Y")):($this->datum))."\" class=\"wide\"/></div></div>\n";
		
		$top_nabidka="<div class=\"form_row\"> <div class=\"label_float_left\">TOP nabídka (zobrazena jako baner na hlavní stránce): <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"top_nabidka\" type=\"checkbox\" value=\"1\" ".(($this->top_nabidka==1)?(" checked=\"checked\" "):(" "))."/></div></div>\n";
		$odkaz="<div class=\"form_row\"> <div class=\"label_float_left\">Odkaz z aktuality: </div> <div class=\"value\"> <input name=\"odkazy\" type=\"text\" value=\"".(($this->odkazy=="")?("https://"):($this->odkazy))."\" class=\"wide\"/></div></div>\n";
		
		
                $zobrazit="<div class=\"form_row\"> <div class=\"label_float_left\">zobrazit aktualitu: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"zobrazit\" type=\"checkbox\" value=\"1\" ".(($this->zobrazit==1)?(" checked=\"checked\" "):(" "))."/></div></div>\n";

                $weby="<div class=\"form_row\"> <div class=\"label_float_left\">Zobrazit na webech: <span class=\"red\">*</span></div> <div class=\"value\"> "
                        . "<table cellpadding=\"5\" cellspacing=\"10\">"
                        . "<tr>";
                
                $i = 0;
                $c_weby_coll = explode(";", $this->centralni_data["aktuality:seznam_webu"]);
                $c_weby = array();
                foreach ($c_weby_coll as $coll) {
                    $c_weby[] = explode(",", $coll);
                }
                
                //c_weby jsou dvouúrovnove pole: v prvnim jsou sloupce v druhem jednotlive bunky
                foreach ($c_weby as $key1 => $coll) {
                    $weby .="<td valign=\"top\" style=\"padding:0 5px 0 5px;\">";
                    foreach ($coll as $key2 => $web_name) {
                        if($web_name!=""){
                            $weby .="<input name=\"aktuality_$i\" type=\"checkbox\" value=\"$web_name\" ".((stripos($this->weby,$web_name)!==FALSE)?("checked=\"checked\""):(""))."/>".$this->get_name($web_name)."<br/>";
                            $i++;
                        }    
                    }
                    $weby .="</td>";
                }
                $weby .="</table></div></div>\n";

		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=aktuality&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit aktualitu\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení aktuality</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_aktuality=".$this->id_aktuality."&amp;typ=aktuality&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit aktualitu\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci teto aktuality</strong>\n";
			}
		}		

				
		$vystup= "<form action=\"".$action."\" method=\"post\">".
						$nazev.$popisek.$popis.$datum.$top_nabidka.$odkaz.$weby.$zobrazit.
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
