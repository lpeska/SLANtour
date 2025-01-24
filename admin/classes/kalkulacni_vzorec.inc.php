<?php
/** 
 * TODO: dodelat upravu classy
* modul.inc.php - trida pro zobrazeni modulu administrace
*/

/*--------------------- SERIAL -------------------------------------------*/
class Kalkulacni_vzorec extends Generic_data_class{
	//vstupnidata
    protected $typ_pozadavku;
	protected $minuly_pozadavek;	//dobrovolny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;
	
	private $id_vzorec_def;
	private $nazev_vzorce;
        private $vzorec;
        private $poznamka;
        private $nazvy_promennych;
        private $typy_promennych;
        
	protected $id_user_create;	
	protected $data;
		
	public $database; //trida pro odesilani dotazu
        private $maxVars = 100;
        private $vzorecTemplate = "(Promenna_1 + Promenna_2) * Promenna_3 ";
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_vzorec_def,$nazev_vzorce="",$vzorec="",$poznamka="",$minuly_pozadavek="", $modul_group=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
		
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		
		$this->id_vzorec_def = $this->check_int($id_vzorec_def);
		$this->nazev_vzorce = $this->check_slashes( $this->check($nazev_vzorce) );
		$this->vzorec = $this->check_slashes( $this->check($vzorec) );
		$this->poznamka = $this->check_with_html($poznamka);
                $this->nazvy_promennych = "";
                $this->typy_promennych = "";
		$this->default_values = "";
                $this->bez_meny = "";
                $i=1;
                
                while($_POST["nazev_promenne_".$i]!="" and $i <= $this->maxVars){
                    $this->nazvy_promennych .= $this->check($_POST["nazev_promenne_".$i]).";";
                    $this->typy_promennych .= $this->check($_POST["typ_promenne_".$i]).";";
                    $this->default_values .= $this->check_int($_POST["default_value_".$i]).";";
                    $this->bez_meny .= $this->check_int($_POST["bez_meny_".$i]).";";
                    $i++;
                }
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
											
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show") and $this->minuly_pozadavek!="update" ){
					$data_vzorec=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$vzorec = mysqli_fetch_array( $data_vzorec );		
					//jednotlive sloupce ulozim do promennych tridy
                                            $this->id_vzorec_def = $vzorec["id_vzorec_def"];
                                            $this->nazev_vzorce = $vzorec["nazev_vzorce"];
                                            $this->vzorec = $vzorec["vzorec"];
                                            $this->poznamka = $vzorec["poznamka"];
                                            $this->nazvy_promennych = $vzorec["seznam_promennych"];
                                            $this->typy_promennych = $vzorec["seznam_typu"];    
                                            $this->default_values = $vzorec["default_values"];  
                                            $this->bez_meny = $vzorec["bez_meny"]; 
                                            $this->id_user_create = $vzorec["id_user_create"];						
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `kalkulacni_vzorec_definice` 
							(`nazev_vzorce`,`vzorec`,`poznamka`,`seznam_promennych`, `seznam_typu`, `default_values`,`bez_meny`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('$this->nazev_vzorce','$this->vzorec','$this->poznamka','$this->nazvy_promennych', '$this->typy_promennych', '$this->default_values','$this->bez_meny',   $this->id_zamestnance,$this->id_zamestnance)";
//			echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `kalkulacni_vzorec_definice` 
						SET
							`nazev_vzorce`='$this->nazev_vzorce',`vzorec`='$this->vzorec',`poznamka`='$this->poznamka',
							`seznam_promennych`='$this->nazvy_promennych', `seznam_typu`='$this->typy_promennych',`default_values`='$this->default_values', `bez_meny`='$this->bez_meny',  `id_user_edit`=$this->id_zamestnance
						WHERE `id_vzorec_def`=$this->id_vzorec_def
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `kalkulacni_vzorec_definice` 
						WHERE `id_vzorec_def`=".$this->id_vzorec_def."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `kalkulacni_vzorec_definice` 
						WHERE `id_vzorec_def`=".$this->id_vzorec_def."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `kalkulacni_vzorec_definice` 
						WHERE `id_vzorec_def`=".$this->id_vzorec_def."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `kalkulacni_vzorec_definice` 
						WHERE `id_vzorec_def`=".$this->id_vzorec_def."
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
			if(!Validace::text($this->nazev_vzorce) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název vzorce");
			}
			if(!Validace::text($this->vzorec) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit kalkulaèní vzorec");
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
		if($this->typ_pozadavku=="new"){
                        if($this->vzorec==""){
                            $this->vzorec = $this->vzorecTemplate;
                        }
                           
                        //cil formulare
			$action="?typ=kalkulacni_vzorec&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit kalkulaèní vzorec\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení!</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){
			//cil formulare
			$action="?id_vzorec_def=".$this->id_vzorec_def."&amp;typ=kalkulacni_vzorec&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit kalkulaèní vzorec\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto kalkulaèního vzorce</strong>\n";
			}
		}		
		//vytvorim jednotliva pole
                
		$nazev="<div class=\"form_row\"><div class=\"label_float_left\">Název vzorce: <span class=\"red\">*</span></div><div class=\"value\"><input name=\"nazev_vzorce\" class=\"width-500px\" type=\"text\" value=\"".$this->nazev_vzorce."\" /></div></div>\n";
		$vzorec="<div class=\"form_row\"><div class=\"label_float_left\">Zadání vzorce: <span class=\"red\">*</span></div><div class=\"value\"><span id=\"vzorec_check\"></span><input name=\"vzorec\" id=\"vzorec\" class=\"width-500px\" type=\"text\" value=\"".$this->vzorec."\" onchange=\"javascript:analyze_vzorec();\"  />
                        <input type=\"button\" value=\"analyzovat\" onclick=\"javascript:analyze_vzorec();\" />
                        </div></div>\n";
		$promenne="<div class=\"form_row\"><div class=\"label_float_left\">Promìnné: </div><div class=\"value\" id=\"promenne\"></div></div>";
                
                $poznamka="<div class=\"form_row\"><div class=\"label_float_left\">Poznámka: </div><div class=\"value\"><textarea name=\"poznamka\" rows=\"5\" cols=\"100\">".$this->poznamka."</textarea></div></div>\n";
                
                $np = explode(";", $this->nazvy_promennych);
                $np_ap = array();
                foreach ($np as $val) {
                    $np_ap[] = "\"".$val."\"";
                }
                $np_ap = implode(",", $np_ap);
                
                $tp = explode(";", $this->typy_promennych);
                $tp_ap = array();
                foreach ($tp as $val) {
                    $tp_ap[] = "\"".$val."\"";
                }
                $tp_ap = implode(",", $tp_ap);
                
                $df = explode(";", $this->default_values);
                $df_ap = array();
                foreach ($df as $val) {
                    $df_ap[] = "\"".$val."\"";
                }
                $df_ap = implode(",", $df_ap);
                
                $bm = explode(";", $this->bez_meny);
                $bm_ap = array();
                foreach ($bm as $val) {
                    $bm_ap[] = "\"".$val."\"";
                }
                $bm_ap = implode(",", $bm_ap);
                
                $script = "<script type=\"text/javascript\"  >
                                var nazvy_promennych = [$np_ap];
                                var typy_promennych = [$tp_ap];
                                var default_values = [$df_ap];    
                                var bez_meny = [$bm_ap] ;   
                           </script>
                        ";
		
				
		$vystup= "<form action=\"".$action."\" method=\"post\">".
						$nazev.$vzorec.$promenne.$poznamka.
						$submit.$script.
					"</form>";
		return $vystup;
	}
	
	function get_id() { return $this->id_vzorec_def;}
	function get_nazev_vzorce() { return $this->nazev_vzorce;}
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
