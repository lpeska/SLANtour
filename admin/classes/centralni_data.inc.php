<?php
/** 
* modul.inc.php - trida pro zobrazeni modulu administrace
*/

/*--------------------- SERIAL -------------------------------------------*/
class Centralni_data extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;
	
	protected $id_data;
	protected $nazev;
	protected $poznamka;
	protected $text;	
		
	
	protected $id_user_create;	
	protected $data;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_data,$nazev="",$poznamka="",$text="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
		
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		
		$this->id_data = $this->check_int($id_data);
		$this->nazev = $this->check_slashes( $this->check($nazev) );
		$this->poznamka = $this->check_slashes( $this->check($poznamka) );
		$this->text = $this->check_slashes( $this->check_with_html($text) );	
                if($this->nazev == "kurz EUR"){
                    $this->text =str_replace(",", ".",$this->text);
                }
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
					//pokud vytvarime novy serial, ulozime si jeho id

					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
											
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show") and $this->minuly_pozadavek!="update" ){
					$data_modul=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$modul = mysqli_fetch_array( $data_modul );		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_data = $modul["id_data"];
						$this->nazev = $modul["nazev"];
						$this->poznamka = $modul["poznamka"];
						$this->text = $modul["text"];
                                                
						
						$this->id_user_create = $modul["id_user_create"];						
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `centralni_data` 
							(`nazev`,`poznamka`,`text`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('".$this->nazev."','".$this->poznamka."','".$this->text."',
							 ".$this->id_zamestnance.",".$this->id_zamestnance." )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `centralni_data` 
						SET
							`nazev`='".$this->nazev."',`poznamka`='".$this->poznamka."',`text`='".$this->text."',
                                                            `id_user_edit`=".$this->id_zamestnance."
						WHERE `id_data`=".$this->id_data."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `centralni_data` 
						WHERE `id_data`=".$this->id_data."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `centralni_data` 
						WHERE `id_data`=".$this->id_data."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `centralni_data` 
						WHERE `id_data`=".$this->id_data."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `centralni_data` 
						WHERE `id_data`=".$this->id_data."
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
		//kontrolovaná data: název informace, popisek
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název záznamu");
			}
			if(!Validace::text($this->text) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit text záznamu");
			}									
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}

	/**zobrazeni formulare pro vytvoreni/editaci modulu*/
	function show_form($type=""){		
		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\">název záznamu: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"nazev\" class=\"width-500px\" type=\"text\" value=\"".$this->nazev."\" /></div></div>\n";
		$poznamka="<div class=\"form_row\"> <div class=\"label_float_left\">Poznámka: </div> <div class=\"value\"><input name=\"poznamka\" class=\"width-500px\" type=\"text\" value=\"".$this->poznamka."\" /></div></div>\n";
		$text="<div class=\"form_row\"> <div class=\"label_float_left\">text záznamu: <span class=\"red\">*</span></div> <div class=\"value\"><textarea name=\"text\" rows=\"10\" cols=\"100\">".$this->text."</textarea></div></div>\n";
				
		if($type=="kurzy"){
                    if($this->typ_pozadavku=="new"){
                        $nazev="<div class=\"form_row\"> <div class=\"label_float_left\">Mìna: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" /></div></div>\n";
                    }else if($this->typ_pozadavku=="edit"){
                        $nazev="<div class=\"form_row\"> <div class=\"label_float_left\">Mìna: </div> <div class=\"value\">".str_replace("kalkulace_mena:","",$this->nazev)."<input name=\"nazev\" type=\"hidden\" value=\"".$this->nazev."\" /></div></div>\n";
                    }
                    $text="<div class=\"form_row\"> <div class=\"label_float_left\">Kurz: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"text\" type=\"text\" value=\"".$this->text."\" /></div></div>\n";
                    $poznamka="<input name=\"poznamka\"  type=\"hidden\" value=\"".$this->poznamka."\" />\n";                    
                }
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=centralni_data&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit záznam\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení záznamu</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_data=".$this->id_data."&amp;typ=centralni_data&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Uložit záznam\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci tohoto záznamu</strong>\n";
			}
		}		

				
		$vystup= "<form action=\"".$action."\" method=\"post\">".
						$nazev.$poznamka.$text.
						$submit.
					"</form>";
		return $vystup;
	}
	
	function get_id() { return $this->id_modul;}
	function get_nazev_modulu() { return $this->nazev_modulu;}
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
