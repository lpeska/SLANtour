<?php
/** 
* typ_serialu.inc.php - trida pro editaci typu a podtypu seriálu
*/

/*--------------------- SERIAL -------------------------------------------*/
class Typ_serialu extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
	protected $id_zamestnance;
	
	protected $id_typ;
	protected $id_podtyp;
	
	protected $nazev_typ;
	protected $nazev_podtyp;
	protected $nazev_typ_web;
	protected $nazev_podtyp_web;
	protected $id_user_create;
		
	protected $data;
	protected $typ;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_typ,$id_podtyp="",$nazev="",$nazev_web="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
			
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);	
		
		$this->id_typ = $this->check_int($id_typ);
		$this->id_podtyp = $this->check_int($id_podtyp);	
		
		//podle typu pozadavku urcim, zda prisel nazev zeme, nebo nazev destinace
		if($this->typ_pozadavku=="create_podtyp" or $this->typ_pozadavku=="update_podtyp" or $this->typ_pozadavku=="delete_podtyp"){	
			$this->nazev_podtyp = $this->check($nazev);
			$this->nazev_podtyp_web = $this->nazev_web( $this->check($nazev_web) );
		}else{
			$this->nazev_typ = $this->check($nazev);
			$this->nazev_typ_web = $this->nazev_web( $this->check($nazev_web) );
                        
                        $this->id_foto = $this->check($_GET["id_foto"]);
                        $this->text_foto = $this->check($_POST["text_foto"]);
                        $this->style_text = $this->check($_POST["style_text"]);
		}
			
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){


			$this->database->start_transaction();
			//pokud mazu zemi, smazu i vsechny destinace
			if($this->typ_pozadavku=="delete"){
					$delete_podtyp=$this->database->transaction_query($this->create_query("delete_all_podtyp"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
			}
			
			//provedu pozadavek
			if( !$this->get_error_message()){
				$this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			}
			//vygenerování potvrzovací hlášky
				if( !$this->get_error_message() ){
					$this->database->commit();
					$this->confirm("Požadovaná akce probìhla úspìšnì");
				}		
			
			
					
			//pro pozadavek new_podtyp edit a edit_podtyp je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			if($this->typ_pozadavku=="new_podtyp" or $this->typ_pozadavku=="edit" or $this->typ_pozadavku=="edit_podtyp"){					
					$this->typ=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_typ = $this->typ["id_typ"];
						$this->id_podtyp = $this->typ["id_podtyp"];
						$this->id_user_create = $this->typ["id_user_create"];	
						
						if( !($this->typ_pozadavku=="edit" and $this->minuly_pozadavek=="update") ){
							$this->nazev_typ = $this->typ["nazev_typ"];
							$this->nazev_typ_web = $this->typ["nazev_typ_web"];
                                                        $this->id_foto = $this->typ["id_foto"];
                                                        $this->foto_url = $this->typ["foto_url"];
                                                        $this->text_foto = $this->typ["text_foto"];
                                                        $this->style_text = $this->typ["style_text"];
						}
						if( !($this->typ_pozadavku=="edit_podtyp" and $this->minuly_pozadavek=="update_podtyp") ){							
							$this->nazev_podtyp = $this->typ["nazev_podtyp"];
							$this->nazev_podtyp_web = $this->typ["nazev_podtyp_web"];
                                                        $this->foto_url = $this->typ["foto_url"];
                                                        $this->text_foto = $this->typ["text_foto"];
                                                        $this->style_text = $this->typ["style_text"];                                                        
						}						
						
											
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz=	"INSERT INTO `typ_serial`
							(`nazev_typ`,`nazev_typ_web`,`text_foto`,`style_text`,`id_user_create`,`id_user_edit`)
						VALUES
							(\"".$this->nazev_typ."\",\"".$this->nazev_typ_web."\",\"".$this->text_foto."\",\"".$this->style_text."\", ".$this->id_zamestnance.",".$this->id_zamestnance.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="create_podtyp"){
			$dotaz=	"INSERT INTO `typ_serial`
							(`id_nadtyp`,`nazev_typ`,`nazev_typ_web`,`id_user_create`,`id_user_edit`)
						VALUES
							(".$this->id_typ.",\"".$this->nazev_podtyp."\",\"".$this->nazev_podtyp_web."\",".$this->id_zamestnance.",".$this->id_zamestnance.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `typ_serial` 
						SET `nazev_typ`=\"".$this->nazev_typ."\", `nazev_typ_web`=\"".$this->nazev_typ_web."\", 
                                                    `text_foto`=\"".$this->text_foto."\",`style_text`=\"".$this->style_text."\",
                                                    `id_user_edit`=".$this->id_zamestnance."
						WHERE `id_typ`=".$this->id_typ."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;	
                }else if($typ_pozadavku=="update_foto"){
			$dotaz= "UPDATE `typ_serial` 
						SET `id_foto`=".$this->id_foto.",
                                                    `id_user_edit`=".$this->id_zamestnance."
						WHERE `id_typ`=".$this->id_typ."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;	        
		}else if($typ_pozadavku=="update_podtyp"){
			$dotaz= "UPDATE `typ_serial` 
						SET `nazev_typ`=\"".$this->nazev_podtyp."\", `nazev_typ_web`=\"".$this->nazev_podtyp_web."\", `id_user_edit`=".$this->id_zamestnance."
						WHERE `id_nadtyp`=".$this->id_typ." and `id_typ`=".$this->id_podtyp."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;					
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `typ_serial` 
						WHERE `id_typ`=".$this->id_typ."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
				
		}else if($typ_pozadavku=="delete_podtyp"){
			$dotaz= "DELETE FROM `typ_serial` 
						WHERE `id_typ`=".$this->id_podtyp."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete_all_podtyp"){
			$dotaz= "DELETE FROM `typ_serial` 
						WHERE `id_nadtyp`=".$this->id_typ." ";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `typ_serial` left join foto on (typ_serial.id_foto = foto.id_foto)
						WHERE `id_typ`=".$this->id_typ."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="edit_podtyp"){
			$dotaz= "SELECT `typ`.`id_typ`,`typ`.`nazev_typ`,
								`podtyp`.`id_typ` as `id_podtyp`,`podtyp`.`nazev_typ` as `nazev_podtyp`,`podtyp`.`nazev_typ_web` as `nazev_podtyp_web`,`podtyp`.`id_user_create`
						FROM `typ_serial` as `typ` join `typ_serial` as `podtyp` on (`typ`.`id_typ`=`podtyp`.`id_nadtyp`) 
						WHERE `podtyp`.`id_typ`=".$this->id_podtyp."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="new_podtyp"){
			$dotaz= "SELECT `id_typ`,`nazev_typ` FROM `typ_serial` 
						WHERE `id_typ`=".$this->id_typ."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `typ_serial` 
						WHERE `id_typ`=".$this->id_typ."
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
			
		}else if($typ_pozadavku == "new_podtyp"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
						
		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");
			
		}else if($typ_pozadavku == "edit_podtyp"){
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
		}else if($typ_pozadavku == "update_foto"){
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
			
		}else if($typ_pozadavku == "create_podtyp"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}		
		}else if($typ_pozadavku == "update_podtyp"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}		
		}else if($typ_pozadavku == "delete_podtyp"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
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
		//kontrolovaná data: název typ/název podtyp, id_typ (u podtypù)
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev_typ) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název typu");
			}					
		}else if($typ_pozadavku == "create_podtyp" or $typ_pozadavku == "update_podtyp"){
			if(!Validace::text($this->nazev_podtyp) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název podtypu");
			}				
			if(!Validace::int_min($this->id_typ,1) ){
				$ok = 0;
				$this->chyba("Typ není specifikován!");
			}					
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}

	/**zobrazeni formulare pro vytvoreni/editaci typu nebo podtypu*/
	function show_form(){
		//tvoøíme podtyp
		if($this->typ_pozadavku == "edit_podtyp" or $this->typ_pozadavku == "new_podtyp"){
			$nazev_typu="<div class=\"form_row\"> <div class=\"label_float_left\">název typu: <span class=\"red\">*</span></div><div class=\"value\"><strong>".$this->nazev_typ."</strong> </div></div>\n";

			$nazev_podtypu="<div class=\"form_row\"> <div class=\"label_float_left\">název podtypu: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->nazev_podtyp."\" class=\"wide\"/></div></div>\n
					<div class=\"form_row\"> <div class=\"label_float_left\">název pro web: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"nazev_web\" type=\"text\" value=\"".$this->nazev_podtyp_web."\" class=\"wide\"/></div></div>\n";			
		//tvoøíme typ
		}else{
			$nazev_typu="<div class=\"form_row\"> <div class=\"label_float_left\">název typu: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->nazev_typ."\" class=\"wide\"/></div></div>\n	
                                    <div class=\"form_row\"> <div class=\"label_float_left\">název pro web: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"nazev_web\" type=\"text\" value=\"".$this->nazev_typ_web."\" class=\"wide\"/></div></div>\n
                                    <div class=\"form_row\"> <div class=\"label_float_left\">Text zobrazený u obrázku na homepage: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"text_foto\" type=\"text\" value=\"".$this->text_foto."\" class=\"wide\"/></div></div>\n	
                                    <div class=\"form_row\"> <div class=\"label_float_left\">Styl textu u obrázku: </div><div class=\"value\"> <input name=\"style_text\" type=\"text\" value=\"".$this->style_text."\" class=\"wide\"/></div></div>\n";	

                        

			$nazev_podtypu="";
		}
			
		//cil formulare a tlacitka pro odeslani
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=typ_serialu&amp;pozadavek=create";
			
			//tlacitko pro odeslani destinace zobrazime jen pokud ma zamestnanec opravneni ji vytvorit!
			if( $this->legal("create") ){
					//tlacitko pro odeslani
					$submit= "<input type=\"submit\" value=\"Vytvoøit typ\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení typu</strong>\n";
			}
		//cil formulare a tlacitka pro odeslani
		}else if($this->typ_pozadavku=="new_podtyp"){
			//cil formulare
			$action="?id_typ=".$this->id_typ."&amp;typ=typ_serialu&amp;pozadavek=create_podtyp";
			
			//tlacitko pro odeslani destinace zobrazime jen pokud ma zamestnanec opravneni ji vytvorit!
			if( $this->legal("create_podtyp") ){
					//tlacitko pro odeslani
					$submit= "<input type=\"submit\" value=\"Vytvoøit podtyp\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení podtypu</strong>\n";
			}
						
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_typ=".$this->id_typ."&amp;typ=typ_serialu&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit typ\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci typu</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit_podtyp"){	
			//cil formulare
			$action="?id_typ=".$this->id_typ."&amp;id_podtyp=".$this->id_podtyp."&amp;typ=typ_serialu&amp;pozadavek=update_podtyp";
			if( $this->legal("update_podtyp") ){
					$submit= "<input type=\"submit\" value=\"Upravit podtyp\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci podtypu</strong>\n";
			}
		}
		
		$vystup= "<form action=\"".$action."\" method=\"post\" >".
						$nazev_typu.$nazev_podtypu.$submit.
					"</form>";
		return $vystup;
	}
        function show_foto(){
            
            return "Foto ID: $this->id_foto <br/> <img alt=\"\" src=\"/foto/ico/$this->foto_url\"/>";
        }
	/*metody pro pristup k parametrum*/
	function get_id_typ() { return $this->id_typ;}
	function get_id_podtyp() { return $this->id_podtyp;}
	function get_nazev_typ() { return $this->nazev_typ;}
	function get_nazev_podtyp() { return $this->nazev_podtyp;}
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_typ == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}
} 




?>
