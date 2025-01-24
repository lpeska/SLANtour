<?php
/** 
* rezervace_platba.inc.php - trida pro zobrazeni platby rezervace
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_sleva extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $id_zamestnance;
		
	protected $id_slevy;
	protected $id_objednavka;
	protected $castka;
	protected $mena;
        protected $celkem_sleva;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_slevy,$id_objednavka="",$nazev_slevy="",$castka="",$mena="", $old_nazev_slevy="", $old_velikost_slevy=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
				
		$this->id_slevy = $this->check_int($id_slevy);
		$this->id_objednavka = $this->check_int($id_objednavka);
                $this->nazev_slevy = $this->check($nazev_slevy );    
		$this->castka = $this->check_int($castka);		
		$this->mena = $this->check($mena );     
                
                $this->old_nazev_slevy = $this->check($old_nazev_slevy );  
		$this->old_velikost_slevy = $this->check_int($old_velikost_slevy);
                
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					//update nesmí mìnit èástku!!
                                        if(!$_REQUEST["probihajici_transakce"]){
                                            $this->database->start_transaction();
                                        }    
					
					if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" ){
                                            if ($this->mena == "%") {
                                                $this->velikost_slevy = 0;
                                                $data_objednavka = mysqli_fetch_array( $this->database->transaction_query($this->create_query("get_objednavka")) );
                                                 
                                                $query = $this->database->transaction_query($this->create_query("get_sluzby") );
                                                while ($ceny = mysqli_fetch_array($query)) {
                                                    //pridam castku do slevy - zde musi byt udaj v %!!! pouze pro sluzby (ne priplatky aj)
                                                    if (intval($ceny["poradi_ceny"]) < 200 and intval($ceny["typ_ceny"]) == 1) {
                                                        
                                                        $cena_sluzby = $this->calculate_prize($ceny["castka"], $ceny["pocet"], $data_objednavka["pocet_noci"], $ceny["use_pocet_noci"]);                                                        
                                                        $this->velikost_slevy = $this->velikost_slevy + ($cena_sluzby * $this->castka / 100);
                                                    }
                                                }
                                                $this->velikost_slevy = round($this->velikost_slevy);
                                                
                                            } else if($this->mena == "Kè_direct"){	
                                                $this->mena = "Kè";
                                                $this->velikost_slevy = round($this->castka);                                               
                                            } else {    
                                                $data_osoby = mysqli_fetch_array( $this->database->transaction_query($this->create_query("get_pocet_osob")) );						
                                                $this->velikost_slevy = round($this->castka * $data_osoby["pocet"]);
                                            }					
					}
					
					$this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni)  .$this->create_query($this->typ_pozadavku) );
					
					//neni treba old/new castka, protoze update nemuze menit castku k zaplaceni	
                                        //vykona se externe
					//$this->change_zbyva_zaplatit($this->typ_pozadavku, $castka, $old_splaceno, $this->splaceno);

					//vygenerování potvrzovací hlášky
                                        if(!$_REQUEST["probihajici_transakce"]){
                                            if( !$this->get_error_message() ){                                            
						$this->database->commit();//potvrdim transakci
						$this->confirm("Požadovaná akce probìhla úspìšnì");                                                 
                                            }else{
                                                $this->database->rollback();//zrusim transakci
                                            }
                                        }
						
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if($this->typ_pozadavku=="edit" or $this->typ_pozadavku=="show"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze");
						
					$this->sleva=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_slevy = $this->sleva["id_slevy"];
						$this->id_objednavka = $this->sleva["id_objednavka"];
                                                $this->nazev_slevy = $this->sleva["nazev_slevy"];
						$this->castka = $this->sleva["castka"];
                                                $this->mena = $this->sleva["mena"];
                                                $this->velikost_slevy = $this->sleva["castka_slevy"];
						
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){                        
			$dotaz= "INSERT INTO `objednavka_sleva` 
							(`id_objednavka`,`nazev_slevy`,`velikost_slevy`,`mena`,`castka_slevy`)
						VALUES
							(".$this->id_objednavka.",\"".$this->nazev_slevy."\",".$this->castka.",\"".$this->mena."\",".$this->velikost_slevy." )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "
                                UPDATE `objednavka_sleva` SET `nazev_slevy`=\"".$this->nazev_slevy."\",`velikost_slevy`=".$this->castka.",`mena`=\"".$this->mena."\",`castka_slevy`=".$this->velikost_slevy."
                                    WHERE `id_objednavka`=".$this->id_objednavka." and `nazev_slevy`=\"".$this->old_nazev_slevy."\"  and `velikost_slevy`=".$this->old_velikost_slevy."
                                    LIMIT 1 ";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `objednavka_sleva` 
						WHERE `id_objednavka`=".$this->id_objednavka." and `nazev_slevy`=\"".$this->old_nazev_slevy."\"  and `velikost_slevy`=".$this->old_velikost_slevy."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit" or $typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `objednavka_sleva` 
						WHERE `id_objednavka`=".$this->id_objednavka." and `nazev_slevy`=\"".$this->old_nazev_slevy."\"  and `velikost_slevy`=".$this->old_velikost_slevy."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
                }else if($typ_pozadavku=="get_objednavka"){
			$dotaz= "SELECT * FROM `objednavka` 
                                        WHERE `id_objednavka`=".$this->id_objednavka." 
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	        
		}else if($typ_pozadavku=="get_pocet_osob"){
			$dotaz= "select count(`id_klient`) as `pocet`
						from `objednavka_osoby`
						where `id_objednavka` = ".$this->id_objednavka."
			";
			//echo $dotaz;
			return $dotaz;	
                }else if($typ_pozadavku == "get_sluzby"){
                        $dotaz = "SELECT `cena`.*,`cena_zajezd`.*,
                                `objednavka_cena`.`pocet`
                            FROM `objednavka`
                            JOIN `cena` ON (`cena`.`id_serial`=`objednavka`.`id_serial`)
                            JOIN `cena_zajezd` ON (`cena`.`id_cena`=`cena_zajezd`.`id_cena` and `cena_zajezd`.`id_zajezd` = `objednavka`.`id_zajezd`)
                            JOIN `objednavka_cena` ON (`objednavka_cena`.`id_cena`=`cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=" . $this->id_objednavka . ")
                            WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
                            ORDER BY `zakladni_cena` DESC,`typ_ceny`,`nazev_ceny` ";
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
        
        function calculate_prize($castka, $pocet, $pocet_noci, $use_pocet_noci = 0) {
            //dummy
            if ($pocet_noci == 0) {
                $pocet_noci = 1;
            }
            if ($use_pocet_noci != 0) {
                $this->celkova_cena = $this->celkova_cena + ($castka * $pocet * $pocet_noci);
                return $castka * $pocet * $pocet_noci;
            } else {
                $this->celkova_cena = $this->celkova_cena + ($castka * $pocet);
                return $castka * $pocet;
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
		if($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "delete"){		
			/*if(!Validace::datum_en($this->splatit_do) ){
				$ok = 0;
				$this->chyba("Datum splatnosti není ve tvaru dd.mm.RRRR");
			}*/
			if(!Validace::int_min($this->id_objednavka,1) ){
				$ok = 0;
				$this->chyba("Objednávka není identifikována");
			}								
		}		
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}	
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
