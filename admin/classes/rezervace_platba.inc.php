<?php
/** 
* rezervace_platba.inc.php - trida pro zobrazeni platby rezervace
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_platba extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $id_zamestnance;
		
	protected $id_platba;
	protected $id_objednavka;
	protected $castka;
	protected $mena;
	protected $zbyva_zaplatit;
	protected $vystaveno;
	protected $splaceno;
		
	protected $data;
	protected $platba;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_platba,$id_objednavka="",$castka="",$vystaveno="",$splatit_do="",$splaceno="",$cislo_dokladu="",$zpusob_uhrady="",$id_faktury=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
			//print_r($_POST)	;
                        //exit();
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
                $this->typ_dokladu = $this->check($_POST["typ_dokladu"]);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_faktury = $this->check_int($id_faktury);
                
		$this->id_platba = $this->check_int($id_platba);
		$this->id_objednavka = $this->check_int($id_objednavka);
		$this->castka = $this->check_double($castka);
		
		$this->vystaveno = $this->change_date_cz_en( $this->check($vystaveno) );
		$this->splatit_do = $this->change_date_cz_en( $this->check($splatit_do) );
		$this->splaceno = $this->change_date_cz_en( $this->check($splaceno) );
                $this->cislo_dokladu = $this->check($cislo_dokladu );
                $this->zpusob_uhrady = $this->check($zpusob_uhrady );
		if(intval($this->splaceno) == 0){$this->splaceno = "";}
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					//update nesmí mìnit èástku!!
					$this->database->start_transaction();
					
					if($this->typ_pozadavku=="delete" or $this->typ_pozadavku=="update" ){
						$data_castka = mysqli_fetch_array( $this->database->transaction_query($this->create_query("select_info_o_platbe")) );
						$castka = $data_castka["castka"];
						$old_splaceno = $data_castka["splaceno"];
					}
					
					$this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni)  .$this->create_query($this->typ_pozadavku) );
					
					if($this->typ_pozadavku=="create"){
						$this->id_platba = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
					}
					
					//neni treba old/new castka, protoze update nemuze menit castku k zaplaceni	
					$this->change_zbyva_zaplatit($this->typ_pozadavku, $castka, $old_splaceno, $this->splaceno);

					//vygenerování potvrzovací hlášky
					if( !$this->get_error_message() ){
						$this->database->commit();//potvrdim transakci
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
					
					if($this->typ_pozadavku=="create"){
						$this->id_platba = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
					}
						
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze");
						
					$this->platba=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_platba = $this->platba["id_platba"];
						$this->id_objednavka = $this->platba["id_objednavka"];
						$this->castka = $this->platba["castka"];
                                                $this->typ_dokladu = $this->platba["typ_dokladu"];
                                                
                                                //v databazi je kvuli pocitani ulozena zaporna castka, uzivateli se zobrazuje kladna
                                                if($this->typ_dokladu=="vydajovy"){ 
                                                    $this->castka = - $this->castka;
                                                }
                                                
                                                $this->cislo_dokladu = $this->platba["cislo_dokladu"];
                                                $this->zpusob_uhrady = $this->platba["zpusob_uhrady"];
						$this->mena = $this->platba["mena"];
				 		$this->vystaveno = $this->change_date_en_cz( $this->platba["vystaveno"] );
						$this->splatit_do = $this->change_date_en_cz( $this->platba["splatit_do"] );
						$this->splaceno = $this->change_date_en_cz( $this->platba["splaceno"] );
						if(intval($this->splaceno) == 0){$this->splaceno = "";}
                                                if(intval($this->splatit_do) == 0){$this->splatit_do = "";}
			}else if($this->typ_pozadavku=="new"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
						
					$this->platba=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->mena = $this->platba["mena"];
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
                        if($this->splatit_do!=""){ 
                            $coll_splatit_do = "`splatit_do`,";
                            $val_splatit_do = "'".$this->splatit_do."',";
                        }
                        if($this->splaceno!=""){ 
                            $coll_splaceno = "`splaceno`,";
                            $val_splaceno = "'".$this->splaceno."',";
                        }
                        if($this->id_faktury!=""){ 
                            $coll_fa = "`id_faktury`,";
                            $val_fa = "".$this->id_faktury.",";
                        }
                        //v databazi je kvuli pocitani ulozena zaporna castka, uzivateli se zobrazuje kladna
                        if($this->typ_dokladu=="vydajovy"){ 
                            $this->castka = - $this->castka;
                        }
                        
                        //u faktur se nepridava typ dokladu - docasny fix
                        if($this->typ_dokladu=="" and $this->castka < 0){ 
                            $this->typ_dokladu="vydajovy";
                        }else if($this->typ_dokladu==""){
                            $this->typ_dokladu="prijmovy";
                        }
                        
			$dotaz= "INSERT INTO `objednavka_platba` 
							(`id_objednavka`,`typ_dokladu`,`castka`,`vystaveno`,".$coll_splatit_do.$coll_splaceno.$coll_fa."`cislo_dokladu`,`zpusob_uhrady`,
							`id_user_create`,`id_user_edit`)
						VALUES
							 (".$this->id_objednavka.",\"".$this->typ_dokladu."\",".$this->castka.",'".Date("Y-m-d")."',".$val_splatit_do.$val_splaceno.$val_fa."'".$this->cislo_dokladu."','".$this->zpusob_uhrady."',
							  ".$this->id_zamestnance.",".$this->id_zamestnance." )";
			//echo $dotaz;
			return $dotaz;exit();
		}else if($typ_pozadavku=="update"){
                        if($this->splatit_do!=""){ 
                            $coll_splatit_do = "`splatit_do`= '".$this->splatit_do."',";
                        }
                        if($this->splaceno!=""){
                            $coll_splaceno = "`splaceno`='".$this->splaceno."',";                            
                        }
                        
                        
			$dotaz= "UPDATE `objednavka_platba` 
						SET
							".$coll_splatit_do.$coll_splaceno."`cislo_dokladu`='".$this->cislo_dokladu."',`zpusob_uhrady`='".$this->zpusob_uhrady."',`id_user_edit`= ".$this->id_zamestnance."
						WHERE `id_platba`=".$this->id_platba."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `objednavka_platba` 
						WHERE `id_platba`=".$this->id_platba."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `objednavka_platba` 
						WHERE `id_platba`=".$this->id_platba."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
                }else if($typ_pozadavku=="get_platby_faktury"){
			$dotaz= "SELECT * FROM `objednavka_platba` 
						WHERE `id_faktury`=".$this->id_faktury."";
			//echo $dotaz;
			return $dotaz;	   
                }else if($typ_pozadavku=="get_faktura"){
			$dotaz= "SELECT * FROM `faktury` 
						WHERE `id_faktury`=".$this->id_faktury."";
			//echo $dotaz;
			return $dotaz;	        
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `objednavka_platba` 
						WHERE `id_platba`=".$this->id_platba."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="new"){
			$dotaz= "SELECT `mena`  FROM `objednavka` 
						WHERE `id_objednavka`=".$this->id_objednavka."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;			
					
		}else if($typ_pozadavku=="select_info_o_platbe"){
			$dotaz= "SELECT `splaceno`,`castka`
						FROM `objednavka_platba` 
						WHERE `id_platba`=".$this->id_platba."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku=="select_zbyva_zaplatit"){
			$dotaz= "SELECT `zbyva_zaplatit` FROM `objednavka` 
						WHERE `id_objednavka`=".$this->id_objednavka."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;					

		}else if($typ_pozadavku=="update_zbyva_zaplatit"){
			$dotaz= "UPDATE `objednavka` 
						SET `zbyva_zaplatit`='".$this->zbyva_zaplatit."'   
						WHERE `id_objednavka`=".$this->id_objednavka."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `objednavka` 
						WHERE `id_objednavka`=".$this->id_objednavka."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;					
		}		
	}	
        
        //pokud mam id_faktury, zkontroluju vsechny platby prirazene k fakture a na zaklade jejich souctu pripadne upravim stav zaplaceni faktury
        function update_stav_faktury(){
            if($this->id_faktury!=""){
                $suma_plateb = 0;
                $suma_faktura = 0;
                $stav_faktury = 0;
                $data=$this->database->query($this->create_query("get_platby_faktury"))
                    or $this->chyba("Chyba pøi dotazu do databáze");
                while ($row = mysqli_fetch_array($data)) {
                    $suma_plateb = $suma_plateb+$row["castka"];
                }
                $data2=$this->database->query($this->create_query("get_faktura"))
                    or $this->chyba("Chyba pøi dotazu do databáze");
                while ($row = mysqli_fetch_array($data2)) {
                    $suma_faktura = $row["celkova_castka"];
                }
                if($suma_plateb==0){
                    $stav_faktury = 0;
                }else if($suma_plateb < $suma_faktura){
                    $stav_faktury = 1;
                }else if($suma_plateb == $suma_faktura){
                    $stav_faktury = 2;
                }else if($suma_plateb > $suma_faktura){
                    $stav_faktury = 3;
                }
                $dotaz= "UPDATE `faktury` 
                            SET `zaplaceno`=$stav_faktury
                            WHERE `id_faktury`=".$this->id_faktury."
                            LIMIT 1";
                $data=$this->database->query($dotaz)
                    or $this->chyba("Chyba pøi dotazu do databáze");
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
			return $zamestnanec->get_bool_prava($id_modul,"read");
			
		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create"){
			//tvorba casti objednavky := editace objednavky
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
			//delete casti objednavky := editace objednavky
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
		//kontrolovane pole id_serial, od, do
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){		
			/*if(!Validace::datum_en($this->splatit_do) ){
				$ok = 0;
				$this->chyba("Datum splatnosti není ve tvaru dd.mm.RRRR");
			}*/
			if(!Validace::int_min($this->id_objednavka,1) ){
				$ok = 0;
				$this->chyba("Objednávka není identifikována");
			}								
		}
		if($typ_pozadavku == "create"){
			if(!Validace::int($this->castka) or $this->castka == 0 ){
				$ok = 0;
				$this->chyba("Èástka nené vyplnìna");
			}				
		}		
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}	
	}

	/**zmena hodnoty zbyva_zaplatit u prislusne objednavky po prijeti platby*/
	function change_zbyva_zaplatit($typ_pozadavku,$castka,$old_splaceno,$splaceno){
		if(intval($old_splaceno) == 0){
			$old_splaceno = "";
		}
		if(intval($splaceno) == 0){
			$splaceno = "";
		}		
		//echo $typ_pozadavku."-".$castka."-".$old_splaceno."-".$splaceno;
		
		if($typ_pozadavku == "delete" and $old_splaceno != ""){
		//pricist castku ke zbyva_zaplatit
			$objednavka = mysqli_fetch_array( $this->database->transaction_query( $this->create_query("select_zbyva_zaplatit") ) )
				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//vytvorim novou castku
			$this->zbyva_zaplatit = $objednavka["zbyva_zaplatit"] + $castka;
			$zaplaceno = $this->database->transaction_query( $this->create_query("update_zbyva_zaplatit") )
				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		
		
		}else if($typ_pozadavku == "update" and $old_splaceno != "" and $splaceno == ""){
		//pricist castku k zbyva_zaplatit
			$objednavka = mysqli_fetch_array( $this->database->transaction_query( $this->create_query("select_zbyva_zaplatit") ) )
				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//vytvorim novou castku
			$this->zbyva_zaplatit = $objednavka["zbyva_zaplatit"] + $castka;
			$zaplaceno = $this->database->transaction_query( $this->create_query("update_zbyva_zaplatit") )
				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
		
		}else if(($typ_pozadavku == "update" or $typ_pozadavku == "create") and $old_splaceno == "" and $splaceno != ""){
		//odecist castku od zbyva_zaplatit
			$objednavka = mysqli_fetch_array( $this->database->transaction_query( $this->create_query("select_zbyva_zaplatit") ) )
				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//vytvorim novou castku
			$this->zbyva_zaplatit = $objednavka["zbyva_zaplatit"] - $castka;
			$zaplaceno = $this->database->transaction_query( $this->create_query("update_zbyva_zaplatit") )
				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
							
		
		}
	}
	
	/**zobrazeni formulare pro vytvoreni/editaci platby objednavky*/
	function show_form(){	
		//vytvorim jednotliva pole
		$cislo_dokladu="<div class=\"form_row\"> <div class=\"label_float_left\">Èíslo dokladu:</div> <div class=\"value\"> <input name=\"cislo_dokladu\" type=\"text\" value=\"".$this->cislo_dokladu."\" /></div></div>\n";
		$zpusob_uhrady="<div class=\"form_row\"> <div class=\"label_float_left\">Zpùsob úhrady (hotovì, pøevodem...):</div> <div class=\"value\"> <input name=\"zpusob_uhrady\" type=\"text\" value=\"".$this->zpusob_uhrady."\" /></div></div>\n";
		
		$splatit_do="<div class=\"form_row\"> <div class=\"label_float_left\">splatnost do: <span class=\"red\"></span></div><div class=\"value\">  <input name=\"splatit_do\" type=\"text\" value=\"".$this->splatit_do."\" /></div></div>\n";
		$splaceno="<div class=\"form_row\"> <div class=\"label_float_left\">splaceno dne:</div> <div class=\"value\"> <input name=\"splaceno\" type=\"text\" value=\"".$this->splaceno."\" /></div></div>\n";
				
		if($this->typ_pozadavku=="new"){
			
			$castka="<div class=\"form_row\"> <div class=\"label_float_left\">èástka: <span class=\"red\">*</span></div><div class=\"value\">  <input name=\"castka\" type=\"text\" value=\"".$this->castka."\" /> Kè</div></div>\n";	
			//cil formulare
			$action="?id_objednavka=".$this->id_objednavka."&amp;typ=rezervace_platba&amp;pozadavek=create";
			//odesílací tlaèítko
			if( $this->legal("create") ){
				$submit= "<input type=\"submit\" value=\"Vytvoøit platbu\" />\n";		
			}else{
				$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení platby</strong>\n";
			}
			
			

		}else if($this->typ_pozadavku=="edit"){	
			$castka="<div class=\"form_row\"> <div class=\"label_float_left\">èástka:</div> <div class=\"value\"> ".$this->castka." Kè</div></div>";
			//cil formulare
			$action="?id_objednavka=".$this->id_objednavka."&amp;id_platba=".$this->id_platba."&amp;typ=rezervace_platba&amp;pozadavek=update";
			//odesílací tlaèítko
			if( $this->legal("update") ){
				$submit= "<input type=\"submit\" value=\"Upravit platbu\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
				$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci této platby</strong>\n";
			}
			
			
		}
		
		$vystup= "<form action=\"".$action."\" method=\"post\">".
						$cislo_dokladu.$castka.$splatit_do.$splaceno.$zpusob_uhrady
						.$submit."</form>";
		
		return $vystup;
	}

	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_objednavka == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}
	
} 




?>
