<?php
/** 
* informace.inc.php - tridy pro zobrazeni dalsich informaci
*/

/*--------------------- SERIAL -------------------------------------------*/
class Informace extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;
	
	protected $id_informace;
	protected $nazev;
	protected $popisek;
	protected $popis;
        protected $popis_lazni;
        protected $popis_strediska;
        
	protected $id_zeme;
	protected $id_destinace;
	protected $typ_informace;
	protected $id_user_create;
			
	protected $data;
	protected $informace;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_informace,$nazev="",$popisek="",$popis="",$popis_lazni="",$popis_strediska="",
								$id_zeme="",$id_destinace="",$typ_informace="",$detailni_typ="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
									
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_informace = $this->check_int($id_informace);
		$this->nazev = $this->check_slashes( $this->check_with_html($nazev) );
		$this->popisek = $this->check_slashes( $this->check_with_html($popisek) );
		$this->popis = $this->check_slashes( $this->check_with_html($popis) );
                $this->popis_lazni = $this->check_slashes( $this->check_with_html($popis_lazni) );
                $this->popis_strediska = $this->check_slashes( $this->check_with_html($popis_strediska) );
		$this->id_zeme = $this->check_int($id_zeme);
		$this->id_destinace = $this->check_int($id_destinace);
		$this->typ_informace = $this->check_int($typ_informace);
		$this->detailni_typ = $this->check($detailni_typ);
	
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
					//pokud vytvarime novy serial, ulozime si jeho id
					if($this->typ_pozadavku=="create"){
						$this->id_informace = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
						$this->informace["id_informace"] = $this->id_informace;
					}

					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
											
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show") and $this->minuly_pozadavek!="update" ){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku,$this->id_zamestnance,$this->id_serial))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$this->informace=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_informace = $this->informace["id_informace"];
						$this->nazev = $this->informace["nazev"];
						$this->popisek = $this->informace["popisek"];
						$this->popis = $this->informace["popis"];
						$this->popis_lazni = $this->informace["popis_lazni"];
                                                $this->popis_strediska = $this->informace["popis_strediska"];
                                                
						$this->id_zeme = $this->informace["id_zeme"];
						$this->id_destinace = $this->informace["id_destinace"];			
						$this->typ_informace = $this->informace["typ_informace"];
						$this->detailni_typ = $this->informace["detailni_typ"];
						$this->id_user_create = $this->informace["id_user_create"];						
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `informace` 
							(`nazev`,`nazev_web`,`popisek`,`popis`,`popis_lazni`,`popis_strediska`,`id_zeme`,`id_destinace`,`typ_informace`,`detailni_typ`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('".$this->nazev."','".$this->nazev_web($this->nazev)."','".$this->popisek."','".$this->popis."','".$this->popis_lazni."','".$this->popis_strediska."',
							 ".$this->id_zeme.",".$this->id_destinace.",".$this->typ_informace.",'".$this->detailni_typ."',
							 ".$this->id_zamestnance.",".$this->id_zamestnance." )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `informace` 
						SET
							`nazev`='".$this->nazev."',`nazev_web`='".$this->nazev_web($this->nazev)."',`popisek`='".$this->popisek."',`popis`='".$this->popis."',`popis_lazni`='".$this->popis_lazni."',`popis_strediska`='".$this->popis_strediska."',
							`id_zeme`=".$this->id_zeme.",`id_destinace`=".$this->id_destinace.",`typ_informace`=".$this->typ_informace.",`detailni_typ`='".$this->detailni_typ."',
							`id_user_edit`=".$this->id_zamestnance."
						WHERE `id_informace`=".$this->id_informace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `informace` 
						WHERE `id_informace`=".$this->id_informace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `informace` 
						WHERE `id_informace`=".$this->id_informace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `informace` 
						WHERE `id_informace`=".$this->id_informace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `informace` 
						WHERE `id_informace`=".$this->id_informace."
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
				$this->chyba("Musíte vyplnit název informace");
			}
			if(!Validace::text($this->popisek) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit popisek informace");
			}						
		
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
	/**zobrazeni menu - moznosti editace pro konkretni serial*/
	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];

		$vypis = "<div class='submenu'>$this->nazev:
						<a href=\"".$adresa_modulu."?id_informace=".$this->id_informace."&amp;typ=informace&amp;pozadavek=edit\">edit</a>
					 	<a href=\"".$adresa_modulu."?id_informace=".$this->id_informace."&amp;typ=foto\">foto</a>
					 	<a class='action-delete' href=\"".$adresa_modulu."?id_informace=".$this->id_informace."&amp;typ=informace&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
					 	</div>";
					 	
		return $vypis;
	}
	
	
	/**zobrazeni formulare pro vytvoreni/editaci informace*/
	function show_form(){
		
		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\">název informace: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" class=\"width-500px\"/></div></div>\n";
		$popisek="<div class=\"form_row\"> <div class=\"label_float_left\">popisek: <span class=\"red\">*</span></div> <div class=\"value\"> <textarea name=\"popisek\"  id=\"popisek_\" rows=\"5\" cols=\"100\">".$this->popisek."</textarea></div></div>\n";
		$popis="<div class=\"form_row\"> <div class=\"label_float_left\">popis:</div> <div class=\"value\"> <textarea name=\"popis\" id=\"popis_\" rows=\"15\" cols=\"100\">".$this->popis."</textarea></div></div>\n";
		$popis_lazni="<div class=\"form_row\" id=\"popis_lazni\"> <div class=\"label_float_left\">Zamìøení lázní - heslovitì (oddìlujte støedníkem ;)</div> <div class=\"value\"> <textarea name=\"popis_lazni\" id=\"popis_lazni_\" rows=\"6\" cols=\"100\">".$this->popis_lazni."</textarea></div></div>\n";
                $popis_strediska="<div class=\"form_row\" id=\"popis_strediska\"> <div class=\"label_float_left\">Popis støediska - lyžování (oddìlujte støedníkem ;)</div> <div class=\"value\"> <textarea name=\"popis_strediska\" id=\"popis_strediska_\" rows=\"6\" cols=\"100\">".$this->popis_strediska."</textarea></div></div>\n";

                $det_typ="<div class=\"form_row\"> <div class=\"label_float_left\">Upøesnìní typu:</div> <div class=\"value\">
		<select name=\"detailni_typ\">
			<option value=\"\">---žádný---</option>
			<option value=\"lazne\" ".(($this->detailni_typ=="lazne")?("selected=\"selected\""):("")).">Láznì</option>
			<option value=\"pobytova_mista\" ".(($this->detailni_typ=="pobytova_mista")?("selected=\"selected\""):("")).">Pobytové místo</option>
			<option value=\"prirodni_zajimavost\" ".(($this->detailni_typ=="prirodni_zajimavost")?("selected=\"selected\""):("")).">Pøírodní zajímavost</option>
			<option value=\"historicka_zajimavost\" ".(($this->detailni_typ=="historicka_zajimavost")?("selected=\"selected\""):("")).">Kulturní/historické místo</option>
		</select>
		</div></div>\n";
		$make_whizzywig = "
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"popisek_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
					makeWhizzyWig(\"popis_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
				</script>
		";
		
		//tvorba select typ informace
		$i=0;
		$typ="<div class=\"form_row\"> <div class=\"label_float_left\">Typ informace:</div>\n
							 <div class=\"value\">
							 <select name=\"typ_informace\">\n";						
			while(Informace_library::get_typ_informace($i)!=""){
				if($this->typ_informace==($i+1)){
					$typ=$typ."<option value=\"".($i+1)."\" selected=\"selected\">".Informace_library::get_typ_informace($i)."</option>\n";
				}else{
					$typ=$typ."<option value=\"".($i+1)."\">".Informace_library::get_typ_informace($i)."</option>\n";
				}
				$i++;
			}
			$typ=$typ."</select></div></div>\n";	
		
		//tvorba select_zeme_destinace
		$zeme="<div class=\"form_row\"> <div class=\"label_float_left\">Zemì a destinace informace (pro zobrazení v menu):</div>\n
						 <div class=\"value\">
						<select name=\"zeme-destinace\">\n";
		//do promenne typy_serialu vytvorim instanci tridy seznam zemi
		$zeme_informace = new Zeme_list($this->id_zamestnance,"",$this->id_zeme,$this->id_destinace);
		//vypisu seznam zemi
		$zeme = $zeme.$zeme_informace->show_list("select_zeme_destinace");	
		$zeme=$zeme."</select></div></div>\n";	
		
		
		
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=informace&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit informaci\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení informace</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_informace=".$this->id_informace."&amp;typ=informace&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit informaci\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci teto informace</strong>\n";
			}
		}		
 
		$javascript_funkce="
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig63.js\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>			
		";		
				
		$vystup= $javascript_funkce.
					"<form action=\"".$action."\" method=\"post\">".
						$typ.$det_typ.$zeme.
						$nazev.$popisek.$popis.$popis_lazni.$popis_strediska.
						$submit.
						$make_whizzywig.
					"</form>";
		return $vystup;
	}
	
	function get_id() { return $this->informace["id_informace"];}
	function get_nazev() { return $this->informace["nazev"];}
	function get_id_zeme() { return $this->informace["id_zeme"];}
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
