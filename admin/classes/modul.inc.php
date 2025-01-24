<?php
/** 
* modul.inc.php - trida pro zobrazeni modulu administrace
*/

/*--------------------- SERIAL -------------------------------------------*/
class Modul extends Generic_data_class{
	//vstupnidata
    protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;
	
	protected $id_modul;
	protected $nazev_modulu;
	protected $adresa_modulu;
	protected $povoleno;	
	protected $typ_modulu;
    protected $modul_group;
	protected $napoveda;			
	
	protected $id_user_create;	
	protected $data;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_modul,$nazev_modulu="",$adresa_modulu="",$povoleno="",$typ_modulu="",$napoveda="",$minuly_pozadavek="", $modul_group=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
		
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		
		$this->id_modul = $this->check_int($id_modul);
		$this->nazev_modulu = $this->check_slashes( $this->check($nazev_modulu) );
		$this->adresa_modulu = $this->check_slashes( $this->check($adresa_modulu) );
		$this->povoleno = $this->check_int($povoleno);
		$this->typ_modulu = $this->check_slashes( $this->check($typ_modulu) );
		$this->modul_group = $this->check_slashes( $this->check($modul_group) );
		$this->napoveda = $this->check_slashes( $this->check_with_html($napoveda) );
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
					//pokud vytvarime novy serial, ulozime si jeho id
					if($this->typ_pozadavku=="create"){
						$this->id_modul = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
						
						$prava = $this->database->query( $this->create_query("add_prava") )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					}

					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
											
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show") and $this->minuly_pozadavek!="update" ){
					$data_modul=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$modul = mysqli_fetch_array( $data_modul );		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_informace = $modul["id_modul"];
						$this->nazev_modulu = $modul["nazev_modulu"];
						$this->adresa_modulu = $modul["adresa_modulu"];
						$this->povoleno = $modul["povoleno"];
						$this->typ_modulu = $modul["typ_modulu"];
						$this->modul_group = $modul["modul_group"];
						$this->napoveda = $modul["napoveda"];
						
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
			$dotaz= "INSERT INTO `modul_administrace` 
							(`nazev_modulu`,`adresa_modulu`,`povoleno`,`typ_modulu`, `modul_group`, `napoveda`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('$this->nazev_modulu','$this->adresa_modulu',$this->povoleno,'$this->typ_modulu', '$this->modul_group', '$this->napoveda', $this->id_zamestnance,$this->id_zamestnance)";
//			echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `modul_administrace` 
						SET
							`nazev_modulu`='$this->nazev_modulu',`adresa_modulu`='$this->adresa_modulu',`povoleno`=$this->povoleno,
							`typ_modulu`='$this->typ_modulu', `modul_group`='$this->modul_group', `napoveda`='$this->napoveda',`id_user_edit`=$this->id_zamestnance
						WHERE `id_modul`=$this->id_modul
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `modul_administrace` 
						WHERE `id_modul`=".$this->id_modul."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="add_prava"){
			$dotaz= "INSERT INTO `user_zamestnanec_prava` 
							(`id_modul`,`id_user`,`prava`)
						VALUES
							 (".$this->id_modul.",".$this->id_zamestnance.",3)";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `modul_administrace` 
						WHERE `id_modul`=".$this->id_modul."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `modul_administrace` 
						WHERE `id_modul`=".$this->id_modul."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `modul_administrace` 
						WHERE `id_modul`=".$this->id_modul."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
        }else if($typ_pozadavku=="get_modul_groups"){
            $dotaz= "SELECT DISTINCT `modul_group` FROM `modul_administrace`
						WHERE 1";
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
			if(!Validace::text($this->nazev_modulu) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název modulu");
			}
			if(!Validace::text($this->adresa_modulu) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit adresu modulu");
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
	function show_form(){
        $modulGroupsResult = $this->database->query($this->create_query("get_modul_groups")) or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
        $modulGroupsSelector = "<select name='modul_group'>";
        while($modulGroup = mysqli_fetch_object($modulGroupsResult)) {
            $selected = $this->modul_group == $modulGroup->modul_group ? "selected='selected'" : "";
            $modulGroupsSelector .= "<option $selected value='$modulGroup->modul_group'>$modulGroup->modul_group</option>";
        }
        $modulGroupsSelector .= "</select>";

		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"><div class=\"label_float_left\">název modulu: <span class=\"red\">*</span></div><div class=\"value\"><input name=\"nazev_modulu\" type=\"text\" value=\"".$this->nazev_modulu."\" /></div></div>\n";
		$group="<div class=\"form_row\"><div class=\"label_float_left\">modul group: <span class=\"red\">*</span></div><div class=\"value\">$modulGroupsSelector</div></div>\n";
		$adresa="<div class=\"form_row\"><div class=\"label_float_left\">adresa modulu: <span class=\"red\">*</span></div><div class=\"value\"><input name=\"adresa_modulu\" type=\"text\" value=\"".$this->adresa_modulu."\" /></div></div>\n";
		$typ_modulu="<div class=\"form_row\"><div class=\"label_float_left\">typ modulu:</div><div class=\"value\"><input name=\"typ_modulu\" type=\"text\" value=\"".$this->typ_modulu."\" /></div></div>\n";
		$napoveda="<div class=\"form_row\"><div class=\"label_float_left\">nápovìda k modulu:</div><div class=\"value\"><textarea name=\"napoveda\" rows=\"15\" cols=\"100\">".$this->napoveda."</textarea></div></div>\n";
		if($this->povoleno){//pokud je aktualne povoleno zobrazeni modulu
			$povoleno="<div class=\"form_row\"><div class=\"label_float_left\">modul povolen:</div><div class=\"value\"><input type=\"checkbox\" name=\"povoleno\" value=\"1\" checked=\"checked\" /></div></div>\n";
		}else{
			$povoleno="<div class=\"form_row\"><div class=\"label_float_left\">modul povolen:</div><div class=\"value\"><input type=\"checkbox\" name=\"povoleno\" value=\"1\" /></div></div>\n";
		}		
		
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=modul&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit modul\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení modulu</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){
			//cil formulare
			$action="?id_modul=".$this->id_modul."&amp;typ=modul&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit modul\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci tohoto modulu</strong>\n";
			}
		}		

				
		$vystup= "<form action=\"".$action."\" method=\"post\">".
						$nazev.$group.$adresa.$typ_modulu.$povoleno.$napoveda.
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
