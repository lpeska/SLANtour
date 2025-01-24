<?php
/** 
* serial_cena.inc.php - trida pro zobrazeni seznamu cen serialu v administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM cen seriálu -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class TOK_topologie extends Generic_list{
	protected $typ_pozadavku;
	protected $pocet;	
	protected $id_zamestnance;
	
        protected $id_terminu;
	protected $id_objektu;
	protected $id_objektove_kategorie;
        protected $id_topologie;
	protected $id_tok_topologie;
	
        protected $query_add_sedadla;

	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_objektu,$id_terminu="", $id_objekt_kategorie="", $id_topologie="", $id_tok_topologie=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	

                

	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_zamestnance = $this->check_int($id_zamestnance);		
		$this->id_objektu = $this->check_int($id_objektu);
		$this->id_terminu = $this->check_int($id_terminu);
                $this->id_objekt_kategorie = $this->check_int($id_objekt_kategorie);
                $this->id_topologie = $this->check_int($id_topologie);
                $this->id_tok_topologie = $this->check_int($id_tok_topologie);
                	              
		//pokud mam dostatecna prava pokracovat
		if( $this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku) ){                 
                    
			//na zaklade typu pozadavku vytvorim dotaz
			$this->legal_operation=1;
			
			if($this->typ_pozadavku=="create"){
                            $dotaz=$this->database->query($this->create_query("create"))
                                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                            $this->id_tok_topologie = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                        }else if($this->typ_pozadavku=="update"){	
                            $delete_cena=$this->database->query($this->create_query("update"))
                                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }else if($this->typ_pozadavku=="show"){	
                            $this->id_tok_topologie = $this->check_int($id_tok_topologie);
                            
                            $this->data=$this->database->query($this->create_query("show"))
                                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));                            
                            
                            $this->informace = mysqli_fetch_array($this->data); 
                            $this->id_objektu = $this->informace["id_objektu"];
                            $this->id_terminu = $this->informace["id_terminu"];
                            $this->id_objekt_kategorie = $this->informace["id_objekt_kategorie"];
                            $this->id_tok_topologie = $this->informace["id_tok_topologie"];
                          
                            
			}else if($this->typ_pozadavku=="delete"){				
                            $delete_cena=$this->database->query($this->create_query("delete"))
                                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
					//vygenerování potvrzovací hlášky
                                                  			
			}else if($this->typ_pozadavku=="add_sedadla"){				
                            $this->query_add_sedadla = "INSERT INTO `topologie_tok_polozka`(`id_tok_topologie`, `id_termin`, `id_objekt_kategorie`, `id_topologie`, `size_x`, `size_y`, `col`, `row`, `class`, `text`) VALUES ";
                                                  			
			}			
			
		}else{
			$this->chyba($this->typ_pozadavku.$this->id_zamestnance.$this->id_objektu);
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}		
	}	
//------------------- METODY TRIDY -----------------	
        /*kopirovani polozek z topologie do topologie_tok*/
        public function add_to_query($row) {
            if($this->query_add_sedadla == "INSERT INTO `topologie_tok_polozka`(`id_tok_topologie`, `id_termin`, `id_objekt_kategorie`, `id_topologie`, `size_x`, `size_y`, `col`, `row`, `class`, `text`) VALUES "){
                $this->query_add_sedadla .=  "(".$this->id_tok_topologie.",".$this->id_terminu.",".$this->id_objekt_kategorie.",".$this->id_topologie.",".$row["size_x"].",".$row["size_y"].",".$row["col"].",".$row["row"].",\"".$row["class"]."\",\"".$row["text"]."\")";
            }else{
                $this->query_add_sedadla .=  ",(".$this->id_tok_topologie.",".$this->id_terminu.",".$this->id_objekt_kategorie.",".$this->id_topologie.",".$row["size_x"].",".$row["size_y"].",".$row["col"].",".$row["row"].",\"".$row["class"]."\",\"".$row["text"]."\")";
            }

        }
        /*kopirovani polozek z topologie do topologie_tok*/
        public function finish_query() {
            echo $this->query_add_sedadla;
            $this->database->query($this->query_add_sedadla)
                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni)); 
        }        
	/**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovane pole id_cena, id_zajezd
			if(!Validace::int_min($this->id_objektu,1) ){
				$ok = 0;
				$this->chyba("Objekt není identifikován");
			}
			/*if($typ_pozadavku == "new" or $typ_pozadavku == "create" or $typ_pozadavku == "update"){
				if(!Validace::int_min_max($this->pocet,1,MAX_CEN) ){
					$ok = 0;
					$this->chyba("Poèet Objekt kategorií není v povoleném intervalu 1 - ".MAX_CEN."");
				}		
			}	*/								
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}	
	}	
	
	/**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
	function legal_data(){
		$ok = 1;
		$this->cislo_ceny++;
		//kontrolovane pole nazev_cena
		/*	if(!Validace::text($nazev_ceny) ){
				$ok = 0;
				$this->chyba("Název ceny è.".$this->cislo_ceny." není specifikován, cena nebude vytvoøena/zmìnìna");
			}*/					
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}	
	}
	
	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku){
	
                if($typ_pozadavku=="delete"){
			$dotaz="delete
					  from `topologie_tok`
					where `id_tok_topologie`= ".$this->id_tok_topologie." ";
			//echo $dotaz;
			return $dotaz;	        
		}else if($typ_pozadavku=="create"){
			$dotaz="INSERT INTO `topologie_tok`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `id_topologie`) "
                                . "VALUES ($this->id_terminu,$this->id_objekt_kategorie,$this->id_objektu,$this->id_topologie) ";
			//echo $dotaz;
			return $dotaz;	
                }else if($typ_pozadavku=="show"){
			$dotaz="select * from `topologie_tok` where `id_tok_topologie`= ".$this->id_tok_topologie." ";
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
			return $zamestnanec->get_bool_prava($id_modul,"read");
			
		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "copy" or $typ_pozadavku == "add_sedadla" ){
			//tvorba casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				

		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}			
		
		}else if($typ_pozadavku == "delete"){
			//delete casti serialu := editace serialu
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
		
	/*metody pro pristup k parametrum*/
	function get_id_objektu() { return $this->id_objektu;}
	function get_id_termin() { return $this->id_terminu;}
        function get_id_objekt_kategorie() { return $this->id_objekt_kategorie;}
        function get_id_tok_topologie() { return $this->id_tok_topologie;}

	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_objektu == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}	
} 

?>
