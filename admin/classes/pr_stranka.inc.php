<?php
/** 
* pr_stranka.inc.php - tridy pro zobrazeni PR stranky
*/

/*--------------------- SERIAL -------------------------------------------*/
class PrStranka extends Generic_data_class {
	//vstupni data
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
	protected $id_zamestnance;
	
        protected $id_pr_stranky;
        protected $nazev;
        protected $nadpis;
        protected $titulek;
        protected $text;       
        protected $klicova_slova;
        protected $adresa;
        protected $adresy_list;
        protected $id_user_create;
		
	protected $data;
	protected $pr_stranka;
	
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_pr_stranky,$nazev="",$nadpis="",$titulek="",$text="",$klicova_slova="",$adresa="",$adresy_list="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu            
		$this->database = Database::get_instance();
	
			//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_pr_stranky = $this->check_int($id_pr_stranky);                
		$this->nazev = $this->check_slashes($this->check($nazev));
		$this->nadpis = $this->check_slashes($this->check($nadpis));
		$this->titulek = $this->check_slashes($this->check($titulek));
		$this->text = $this->check_slashes($this->check_with_html($text));
		$this->img1 = $this->check_slashes($this->check($img1));
		$this->img1_alt = $this->check_slashes($this->check($img1_alt));
		$this->img1_titulek = $this->check_slashes($this->check($img1_titulek));
		$this->img2 = $this->check_slashes($this->check($img2));
		$this->img2_alt = $this->check_slashes($this->check($img2_alt));
		$this->img2_titulek = $this->check_slashes($this->check($img2_titulek));
		$this->klicova_slova = $this->check_slashes($this->check($klicova_slova));
		$this->adresa = $this->check_slashes($this->check($adresa));
		$this->adresy_list = $this->check_slashes($this->check($adresy_list));
		$this->id_user_create = $this->get_id_user_create();
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			if($this->typ_pozadavku=="create"){
                            $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                            $this->id_pr_stranky = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                            $this->pr_stranka["id_pr_stranky"] = $this->id_pr_stranky;						//								
			}else if($this->typ_pozadavku=="update"){			
                            $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	                        
			}else if($this->typ_pozadavku=="delete"){
                            $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );						
			}else if($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update" ){
                            $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

                            $this->pr_stranka=mysqli_fetch_object($this->data);		
                            //jednotlive sloupce ulozim do promennych tridy
                            $this->id_pr_stranky = $this->pr_stranka->id_pr_stranky;
                            $this->nazev = $this->pr_stranka->nazev;
                            $this->nadpis = $this->pr_stranka->nadpis;
                            $this->titulek = $this->pr_stranka->titulek;
                            $this->text = $this->pr_stranka->text;                            
                            $this->klicova_slova = $this->pr_stranka->klicova_slova;
                            $this->adresa = $this->pr_stranka->adresa;
                            $this->adresy_list = $this->pr_stranka->adresy_list;
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}
	
		//pokud se akce uspìšnì zapsala do databáze, vypíšu potvrzovací hlášku
		if(!$this->get_error_message() and 
			($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "delete") ){
			$this->confirm("Požadovaná akce probìhla úspìšnì");
		}		
		

	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `pr_stranky` 
                                        (`nazev`,`nadpis`,`titulek`,`text`,`klicova_slova`,`adresa`,`adresy_list`,`id_user_create`,`id_user_edit`)
                                    VALUES
                                        ('$this->nazev','$this->nadpis','$this->titulek','$this->text',
                                         '$this->klicova_slova','$this->adresa','$this->adresy_list',$this->id_zamestnance,$this->id_zamestnance)";
//			echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="update"){
			//neupdatuju dokument_url
			$dotaz= "UPDATE `pr_stranky` 
                                    SET
                                        `nazev`='$this->nazev',`nadpis`='$this->nadpis',`titulek`='$this->titulek',
                                        `text`='$this->text',`klicova_slova`='$this->klicova_slova',`adresa`='$this->adresa',
                                        `adresy_list`='$this->adresy_list',`id_user_edit`=$this->id_zamestnance
                                    WHERE `id_pr_stranky`=$this->id_pr_stranky
                                    LIMIT 1";
//			echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `pr_stranky` 
                                    WHERE `id_pr_stranky`=".$this->id_pr_stranky."
                                    LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `pr_stranky` 
                                    WHERE `id_pr_stranky`=".$this->id_pr_stranky."
                                    LIMIT 1";
			//echo $dotaz;
			return $dotaz;				
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `pr_stranky` 
                                    WHERE `id_pr_stranky`=".$this->id_pr_stranky."
                                    LIMIT 1";
			//echo $dotaz;
			return $dotaz;					
		}
	}	
	
	/**kontrola zda smi uzivatel provest danou akci*/
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
		//kontrolovaná data: název typ/název destinace, id_zeme (u destinací)
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název PR stránky");
			}					
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
	
	
	/**zobrazeni formulare pro vytvoreni/editaci dokumentu*/
	function show_form(){
		if($this->typ_pozadavku == "new"){
			$povinny_dokument =" <span class=\"red\">*</span>";
		}
		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\">název PR stránky: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" class=\"width-500px\"/></div></div>\n";
		$nadpis="<div class=\"form_row\"> <div class=\"label_float_left\">nadpis PR stránky: </div> <div class=\"value\"> <input name=\"nadpis\" type=\"text\" value=\"".$this->nadpis."\" class=\"width-500px\"/></div></div>\n";
		$titulek="<div class=\"form_row\"> <div class=\"label_float_left\">titulek PR stránky: </div> <div class=\"value\"> <input name=\"titulek\" type=\"text\" value=\"".$this->titulek."\" class=\"width-500px\"/></div></div>\n";
		$text="<div class=\"form_row\"> <div class=\"label_float_left\">text PR stránky:</div> <div class=\"value\"> <textarea name=\"text\" id=\"text_\" rows=\"15\" cols=\"100\">".$this->text."</textarea></div></div>\n";		                
                $klicova_slova="<div class=\"form_row\"> <div class=\"label_float_left\">klíèová slova: </div> <div class=\"value\"> <input name=\"klicova_slova\" type=\"text\" value=\"".$this->klicova_slova."\" class=\"width-500px\"/></div></div>\n";
                $adresa="<div class=\"form_row\"> <div class=\"label_float_left\">adresa PR stránky: </div> <div class=\"value\"> <input name=\"adresa\" type=\"text\" value=\"".$this->adresa."\" class=\"width-500px\"/></div></div>\n";
                $adresy_list="<div class=\"form_row\"> <div class=\"label_float_left\">výskyty PR stránky: </div> <div class=\"value\"> <input name=\"adresy_list\" type=\"text\" value=\"".$this->adresy_list."\" class=\"width-500px\" />
                                <br/> weby které podporují zobrazení PR stránek: tatratur,fotbal, hokej, olympiada, exotika, poznavaci-zajezdy, italie, slovensko, spanelsko, lastminute, sport, euro, khl, levpoprad, loh-2012, lazenske-pobyty
                            </div></div>\n";
                $make_whizzywig = "
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"text_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
				</script>
		";
                $javascript_funkce="
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig63.js\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>			
		";
		//tvorba select zeme (pouze pri novem serialu)
		if($this->typ_pozadavku == "new"){
			//cil formulare
			$action="?typ=pr_stranka&amp;pozadavek=create";
			//tlacitko pro odeslani
			if( $this->legal("create") ){
					$submit= "<input type=\"submit\" value=\"Vytvoøit PR stránku\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení PR stránky</strong>\n";
			}
		}else if($this->typ_pozadavku == "edit"){	
			//cil formulare
			$action="?id_pr_stranky=".$this->id_pr_stranky."&amp;typ=pr_stranka&amp;pozadavek=update";
			//tlacitko pro odeslani
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit PR stránku\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci této PR stránky</strong>\n";
			}
		}
		
		$vystup= $javascript_funkce."<form action=\"".$action."\" method=\"post\" >".
						$nazev.$nadpis.$titulek.$text.$img1.$img1_alt.$img1_titulek.$img2.$img2_alt.$img2_titulek.$klicova_slova.$adresa.$adresy_list.$submit.$make_whizzywig.
					"</form>";
		return $vystup;
	}
	
        /**zobrazeni menu - moznosti editace pro konkretni aktualitu*/
	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];
		$vypis = "<div class='submenu'>$this->nazev:
						<a href=\"".$adresa_modulu."?id_pr_stranky=".$this->id_pr_stranky."&amp;typ=pr_stranka&amp;pozadavek=edit\">edit</a>
                                                <a href=\"".$adresa_modulu."?id_pr_stranky=".$this->id_pr_stranky."&amp;typ=foto\">foto</a>
                                                <a class='action-delete' href=\"".$adresa_modulu."?id_pr_stranky=".$this->id_pr_stranky."&amp;typ=pr_stranka&amp;pozadavek=edit\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
						</div>";
		return $vypis;
	}
	
	function get_id() { return $this->id_pr_stranky; }
	function get_nazev() { return $this->nazev; }
	
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
                    return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_pr_stranky == 0){
                    return $this->id_zamestnance;	
		}else{
                    $data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
                    $this->id_user_create = $data_id["id_user_create"];
                    return $data_id["id_user_create"];
		}
	
	}
} 




?>
