<?php
/** 
* zeme.inc.php - trida pro editaci zemi a destinaci
*/

/*--------------------- SERIAL -------------------------------------------*/
class Zeme extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
	protected $id_zamestnance;
	
	protected $id_zeme;
	protected $id_destinace;
	protected $id_informace;
	protected $nazev_zeme;
        protected $geograficka_zeme;
	protected $nazev_destinace;
	protected $id_user_create;
		
	protected $data;
	protected $zeme;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_zeme,$id_destinace="",$id_informace="",$nazev="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
			
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		
		$this->id_zeme = $this->check_int($id_zeme);
		$this->id_destinace = $this->check_int($id_destinace);	
		$this->id_informace = $this->check_int($id_informace);	
		
		//podle typu pozadavku urcim, zda prisel nazev zeme, nebo nazev destinace
		if($this->typ_pozadavku=="create_destinace" or $this->typ_pozadavku=="update_destinace"){	
			$this->nazev_destinace = $this->check_slashes( $this->check($nazev) );
		}else{
			$this->nazev_zeme = $this->check_slashes( $this->check($nazev) );
                        $this->geograficka_zeme = $this->check_int($_POST["geograficka_zeme"]) ;
		}
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//provedu pozadavek
			$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			
			//vygenerování potvrzovací hlášky
			if( !$this->get_error_message() ){
				$this->confirm("Požadovaná akce probìhla úspìšnì");
			}	
							
			//pro pozadavek new_destinace edit a edit_destinace je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			if($this->typ_pozadavku=="new_destinace" or $this->typ_pozadavku=="edit" or $this->typ_pozadavku=="edit_destinace"){					
					$this->zeme=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_zeme = $this->zeme["id_zeme"];
						$this->id_destinace = $this->zeme["id_destinace"];
						$this->id_informace = $this->zeme["id_info"];
						$this->nazev_destinace = $this->zeme["nazev_destinace"];
                                                $this->geograficka_zeme = $this->zeme["geograficka_zeme"];
						$this->nazev_zeme = $this->zeme["nazev_zeme"];
						$this->id_user_create = $this->zeme["id_user_create"];						
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku,$with_upload=""){
		if($typ_pozadavku=="create"){
			$dotaz=	"INSERT INTO `zeme`
							(`nazev_zeme`,`nazev_zeme_web`,`geograficka_zeme`,`id_user_create`,`id_user_edit`,`id_info`)
						VALUES
							(\"".$this->nazev_zeme."\",\"".$this->nazev_web($this->nazev_zeme)."\",".$this->geograficka_zeme.",".$this->id_zamestnance.",".$this->id_zamestnance.",".$this->id_informace.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="create_destinace"){
			$dotaz=	"INSERT INTO `destinace`
							(`id_zeme`,`nazev_destinace`,`id_user_create`,`id_user_edit`,`id_info`)
						VALUES
							(".$this->id_zeme.",\"".$this->nazev_destinace."\",".$this->id_zamestnance.",".$this->id_zamestnance.",".$this->id_informace.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `zeme` 
						SET `nazev_zeme`=\"".$this->nazev_zeme."\", `nazev_zeme_web`=\"".$this->nazev_web($this->nazev_zeme)."\", `geograficka_zeme`=".$this->geograficka_zeme.",  `id_user_edit`=".$this->id_zamestnance.", `id_info`=".$this->id_informace."
						WHERE `id_zeme`=".$this->id_zeme."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="update_destinace"){
			$dotaz= "UPDATE `destinace` 
						SET `nazev_destinace`=\"".$this->nazev_destinace."\", `id_user_edit`=".$this->id_zamestnance.", `id_info`=".$this->id_informace."
						WHERE `id_zeme`=".$this->id_zeme." and `id_destinace`=".$this->id_destinace."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;					
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `zeme` 
						WHERE `id_zeme`=".$this->id_zeme."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
				
		}else if($typ_pozadavku=="delete_destinace"){
			$dotaz= "DELETE FROM `destinace` 
						WHERE `id_destinace`=".$this->id_destinace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete_all_destinace"){
			$dotaz= "DELETE FROM `destinace` 
						WHERE `id_zeme`=".$this->id_zeme." ";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `zeme` 
						WHERE `id_zeme`=".$this->id_zeme."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="edit_destinace"){
			$dotaz= "SELECT `zeme`.`id_zeme`,`zeme`.`nazev_zeme`,
								`destinace`.`id_destinace`,`destinace`.`nazev_destinace`,`destinace`.`id_info`,`destinace`.`id_user_create`
						FROM `destinace` join `zeme` on (`zeme`.`id_zeme`=`destinace`.`id_zeme`) 
						WHERE `id_destinace`=".$this->id_destinace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="new_destinace"){
			$dotaz= "SELECT `id_zeme`,`nazev_zeme` FROM `zeme` 
						WHERE `id_zeme`=".$this->id_zeme."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="select_informace"){
			$dotaz= "SELECT `informace`.`id_informace`,`nazev_zeme`,`nazev` 
						FROM `zeme` join `informace` on (`zeme`.`id_zeme`=`informace`.`id_zeme`) 
						Order by `nazev_zeme`,`nazev`
						";
			//echo $dotaz;
			return $dotaz;					
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `zeme` 
						WHERE `id_zeme`=".$this->id_zeme."
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
		if($typ_pozadavku == "new" or $typ_pozadavku == "new_destinace"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
			
		}else if($typ_pozadavku == "edit" or $typ_pozadavku == "edit_destinace"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show" or $typ_pozadavku == "show_destinace"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "create_destinace"){
			return $zamestnanec->get_bool_prava($id_modul,"create");			

		}else if($typ_pozadavku == "update" or $typ_pozadavku == "update_destinace"){
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

		}else if($typ_pozadavku == "delete_destinace"){
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
			if(!Validace::text($this->nazev_zeme) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název zemì");
			}					
		}else if($typ_pozadavku == "create_destinace" or $typ_pozadavku == "update_destinace"){
			if(!Validace::text($this->nazev_destinace) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název destinace");
			}				
			if(!Validace::int_min($this->id_zeme,1) ){
				$ok = 0;
				$this->chyba("Zemì není specifikována!");
			}					
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}

	/**zobrazeni formulare pro vytvoreni/editaci zeme*/
	function show_form_zeme(){

		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\">Název zemì: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"nazev\" type=\"text\" value=\"".$this->get_nazev_zeme()."\" class=\"wide\" /></div></div>\n";

                $geograficka_zeme="<div class=\"form_row\"> <div class=\"label_float_left\">Geografická zemì (nejde napø. o název sportu): </div> <div class=\"value\"><input name=\"geograficka_zeme\" type=\"checkbox\" value=\"1\" ".(($this->geograficka_zeme==1 or $this->typ_pozadavku=="new")?("checked=\"checked\""):(""))."  /></div></div>\n";

                
                
		$select_informace = $this->database->query($this->create_query("select_informace"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
		
		$informace="<div class=\"form_row\"> <div class=\"label_float_left\">informace pøiøazená k zemi: </div> 
			<div class=\"value\"> 
				<select name=\"id_informace\">\n
				<option value=\"\"> --- žádná informace --- </option> ";
				while($radek_informace = mysqli_fetch_array( $select_informace ) ){
					$informace.="<option value=\"".$radek_informace["id_informace"]."\" ".
									(($this->id_informace==$radek_informace["id_informace"])?("selected=\"selected\""):("")).">
									".$radek_informace["nazev_zeme"]." - ".$radek_informace["nazev"]."</option>";
				}				
		$informace.="</select>
			</div></div>\n";
			
		
		//cil formulare a tlacitka pro odeslani
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=zeme&amp;pozadavek=create";
			
			//tlacitko pro odeslani destinace zobrazime jen pokud ma zamestnanec opravneni ji vytvorit!
			if( $this->legal("create") ){
					//tlacitko pro odeslani
					$submit= "<input type=\"submit\" value=\"Vytvoøit zemi\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení zemì</strong>\n";
			}
			
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit zemi\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci teto zemì</strong>\n";
			}
		}
		
		$vystup= "<form action=\"".$action."\" method=\"post\" >".
						$nazev.$geograficka_zeme.$informace.$submit.
					"</form>";
		return $vystup;
	}
	
	/**zobrazeni formulare pro vytvoreni/editaci destinace*/
	function show_form_destinace(){
		//vytvorim jednotliva pole
		$nazev_zeme="<div class=\"form_row\"> <div class=\"label_float_left\">název zemì: </div> <div class=\"value\">".$this->get_nazev_zeme()."</div></div>\n";
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\">název destinace: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->get_nazev_destinace()."\" class=\"wide\"/></div></div>\n";
		
		$select_informace = $this->database->query($this->create_query("select_informace"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
		
		$informace="<div class=\"form_row\"> <div class=\"label_float_left\">informace(text) pøiøazený k destinaci: </div> 
			<div class=\"value\"> 
				<select name=\"id_informace\">\n
				<option value=\"\"> --- žádná informace --- </option> ";
				while($radek_informace = mysqli_fetch_array( $select_informace ) ){
					$informace.="<option value=\"".$radek_informace["id_informace"]."\" ".
									(($this->id_informace==$radek_informace["id_informace"])?("selected=\"selected\""):("")).">
									".$radek_informace["nazev_zeme"]." - ".$radek_informace["nazev"]."</option>";
				}				
		$informace.="</select>
			</div></div>\n";
		
		
		//cil formulare a tlacitka pro odeslani
		if($this->typ_pozadavku=="new_destinace"){
			//cil formulare
			$action="?id_zeme=".$this->get_id_zeme()."&amp;typ=destinace&amp;pozadavek=create";
			
			//tlacitko pro odeslani destinace zobrazime jen pokud ma zamestnanec opravneni ji vytvorit!
			if( $this->legal("create_destinace") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit destinaci\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení destinace</strong>\n";
			}
			
		}else if($this->typ_pozadavku=="edit_destinace"){	
			//cil formulare
			$action="?id_zeme=".$this->get_id_zeme()."&amp;id_destinace=".$this->get_id_destinace()."&amp;typ=destinace&amp;pozadavek=update";

			if( $this->legal("update_destinace") ){
					$submit= "<input type=\"submit\" value=\"Upravit destinaci\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci této destinace</strong>\n";
			}
		}
		
		$vystup= "<form action=\"".$action."\" method=\"post\" >".
						$nazev_zeme.$nazev.$informace.$submit.
					"</form>";
		return $vystup;
	}
	/*metody pro pristup k parametrum*/
	function get_id_zeme() { return $this->id_zeme;}
	function get_id_destinace() { return $this->id_destinace;}
	function get_nazev_zeme() { return $this->nazev_zeme;}
	function get_nazev_destinace() { return $this->nazev_destinace;}
	function get_id_user_create() { return $this->id_user_create;}
} 




?>
