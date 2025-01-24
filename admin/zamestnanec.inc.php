<?php
/** 
* zamestnanec.inc.php - tridy pro zobrazeni udaju o uzivateli-pracovnikovi ck
*/

/*--------------------- SERIAL -------------------------------------------*/
class Zamestnanec extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
	protected $id_zamestnance;
	
	protected $id_user;	
	protected $uzivatelske_jmeno;
	protected $jmeno;
	protected $prijmeni;
	protected $email;
	protected $telefon;

	protected $salt;	
	protected $stare_heslo;
	protected $heslo;
	protected $heslo2;
	protected $nove_heslo;		
			
	protected $data;
	protected $user;
	protected $zamestnanec;
		
	public $database; //trida pro odesilani dotazu
	
	//prubezne konstruovany dotaz do databaze
	protected $query_insert;
	protected $query_update;	
	
	//seznam cen ktere uz existuji (a maji se update misto insert into)
	protected $prava_update_id;//seznam id
	protected $prava_update;//seznam id
	
	//pocty zaznamu v query
	protected $pocet_zaznamu_insert;
	protected $pocet_zaznamu_update;
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct(
		$typ_pozadavku,$id_zamestnance,$id_user,$uzivatelske_jmeno="",$stare_heslo="",$nove_heslo1="",$nove_heslo2="",
		$jmeno="",$prijmeni="",$email="",$telefon="", $minuly_pozadavek=""		
	){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
			
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
	
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_user = $this->check_int($id_user);
		$this->uzivatelske_jmeno = strtolower($this->check($uzivatelske_jmeno));
		$this->jmeno = $this->check_slashes( $this->check($jmeno) );
		$this->prijmeni = $this->check_slashes( $this->check($prijmeni) );
		$this->email = $this->check_slashes( $this->check($email) );
		$this->telefon = $this->check_slashes( $this->check($telefon) );
		
		$this->stare_heslo = $this->check($stare_heslo);
		$this->heslo = $this->check($nove_heslo1);
		$this->heslo2 = $this->check($nove_heslo2);
		

		//inicializace
		$this->nove_heslo = "";
		$this->prava_update_id = array();
		$this->prava_update = array();
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create"){								
					//pokud odpovidaji hesla
					if($this->heslo==$this->heslo2){	
						$this->database->start_transaction();
						
						//vytvorim nahodny retezec nahodne delky:)
						$nahodny_retezec= sha1(mt_rand().mt_rand());
						$this->salt = substr($nahodny_retezec, 1, mt_rand(10,20));
						
						//vytvorim nove heslo ktere pouziju do databaze
						$this->nove_heslo = sha1($this->heslo.$this->salt);
                                                
                                                //rozliseni zda vytvarim noveho klienta nebo pridavam stavajiciho
                                                if($_POST["klient_id"] == "") {
                                                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku, mysqli_insert_id($GLOBALS["core"]->database->db_spojeni)))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                } else {
                                                    $id_user_klient = $this->check_int($_POST["klient_id"]);
                                                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku, $id_user_klient))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                                                }
						
					/*--------------------vytvareni seznamu prav----------------*/
						if(!$this->get_error_message() ){
							$this->id_user = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
							
							$this->pocet_zaznamu_insert=0;
							$this->pocet_zaznamu_update=0;		
							$this->query_insert = array();
						
							//najdu vsechny moduly, ke kterym ma prava prihlaseny uzivatel
							$zamestnanec = User_zamestnanec::get_instance();
							$prava_zamestnanec = array();
							$prava_zamestnanec = $zamestnanec->get_prava();
						
							//pro kazde pravo uzivatele najdeme formularove pole s pravem pro vytvareneho uzivatele
							foreach($prava_zamestnanec as $id_modul=>$modul){								
								$this->add_to_query_prava($id_modul, $_POST["prava_".$id_modul], $modul["prava"] );
							}
							$this->finish_prava();
						}
						
						//vygenerování potvrzovací hlášky
						if( !$this->get_error_message() ){
							$this->database->commit();//potvrdim transakci						
							$this->confirm("Požadovaná akce probìhla úspìšnì");
						}		
					}else{
						$this->chyba("Nové heslo a kontrolní nové heslo nejsou stejné!");
					}

			}else if($this->typ_pozadavku=="update"){	
					$this->database->start_transaction();
                                        //vytahni si id_user_klient z user_zamestnanec
                                        $this->data=$this->database->transaction_query($this->create_query("get_id_user_klient"))
                                            or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                                        $id_user_klient = mysqli_fetch_object($this->data);                                        
                                        $id_user_klient = $id_user_klient->id_user_klient;  
                                        //updatuj tabulku user_klient
					$this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku . "_user_klient", $id_user_klient))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );                                        
                                        //updatuj tabulku user_zamestnanec
                                        $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku . "_user_zamestnanec"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
					/*--------------------vytvareni seznamu prav----------------*/
					if(!$this->get_error_message() ){
							
							$this->pocet_zaznamu_insert=0;
							$this->pocet_zaznamu_update=0;		
							$this->query_insert = array();
							$this->query_update = array();
						
							//najdu vsechny moduly, ke kterym ma prava prihlaseny uzivatel
							$zamestnanec = User_zamestnanec::get_instance();
							$prava_zamestnanec = array();
							$prava_zamestnanec = $zamestnanec->get_prava();
							
							$data_prava = $this->database->query($this->create_query("get_prava"))
								or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
							
							if(!$this->get_error_message() ){
								while($prava_upravovany = mysqli_fetch_array($data_prava )){
									$this->prava_update_id[] = $prava_upravovany["id_modul"];
									$this->prava_update[] = $prava_upravovany;
								}
														
								//pro kazde pravo uzivatele najdeme formularove pole s pravem pro vytvareneho uzivatele
								foreach($prava_zamestnanec as $id_modul=>$modul){
									$this->add_to_query_prava($id_modul, $_POST["prava_".$id_modul], $modul["prava"] );
								}
								$this->finish_prava();
							}
						}
						
					//vygenerování potvrzovací hlášky
					if( !$this->get_error_message() ){
						$this->database->commit();//potvrdim transakci
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
										
			}else if($this->typ_pozadavku=="delete" || $this->typ_pozadavku=="delete_all"){
                                        $this->data=$this->database->transaction_query($this->create_query("get_id_user_klient"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                                        $id_user_klient = mysqli_fetch_object($this->data);
                                        $id_user_klient = $id_user_klient->id_user_klient;                             
                                        if($this->typ_pozadavku == "delete_all") {
                                            $this->data=$this->database->query($this->create_query("delete_user_klient", $id_user_klient))
    		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                        }
					$this->data=$this->database->query($this->create_query("delete_user_zamestnanec"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                                        $this->data=$this->database->query($this->create_query("delete_prava"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
					//vygenerování potvrzovací hlášky
					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}		
							
			}else if($this->typ_pozadavku=="update_self"){		
					//nactu puvodni informace o uzivateli
					$data_user=$this->database->query($this->create_query("get_user_password"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					$user=mysqli_fetch_array($data_user);	
					
					//pokud chceme zmenit heslo
					if($this->stare_heslo!=""){
						//pokud jsme spravne napsali stare heslo
						
						if(sha1($this->stare_heslo.$user["salt"])==$user["heslo_sha1"]){
						
						//echo "heslo1:".$this->heslo.";"."heslo2:".$this->heslo2.";";
						
							if($this->heslo==$this->heslo2){	
								//vytvorim nove heslo ktere pouziju do databaze
								$this->nove_heslo = sha1($this->heslo.$user["salt"]);
							}else{
								$this->chyba("Nové heslo a kontrolní nové heslo nejsou stejné!");
							}
						}else{
							$this->chyba("Staré heslo není správné!");
						}
					}
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
					//vygenerování potvrzovací hlášky
					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}	
					
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit_self" and $this->minuly_pozadavek!="update_self") or 
						($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update")  ){
					//ziskam data o uzivateli
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );				
					$this->zamestnanec=mysqli_fetch_array($this->data);		
					
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_user = $this->zamestnanec["id_user"];
						$this->uzivatelske_jmeno = $this->zamestnanec["uzivatelske_jmeno"];
						$this->jmeno = $this->zamestnanec["jmeno"];
						$this->prijmeni = $this->zamestnanec["prijmeni"];
						$this->email = $this->zamestnanec["email"];
						$this->telefon = $this->zamestnanec["telefon"];
                                                $this->heslo = $this->zamestnanec["heslo"];
					
					//nactu data o pravech k modulum	
						$data_prava = $this->database->query($this->create_query("get_prava"))
							or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );								
							
						if(!$this->get_error_message() ){
							while($prava_upravovany = mysqli_fetch_array($data_prava )){
								$this->prava_update_id[] = $prava_upravovany["id_modul"];                
								$this->prava_update[] = $prava_upravovany;
							}				
						}
			
			}else if($this->minuly_pozadavek=="update_self" or $this->minuly_pozadavek=="update"){

			
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}


	}	
	

/**prijima informace o jednotlivych pravech uzivatele a vytvari z nich dotaz do db*/
	function add_to_query_prava($id_modul, $prava, $prava_tvurce){
			//kontrola vstupnich dat
				$id_modul = $this->check_int($id_modul);		
				$prava = $this->check_int($prava);	
				$prava_tvurce = $this->check_int($prava_tvurce);	

		//pridelena prava nesmi byt vetsi nez vlastnni!
		if($prava <= $prava_tvurce){
		
			if($this->typ_pozadavku == "create"){		
				$this->pocet_zaznamu_insert++;
				$this->query_insert[$this->pocet_zaznamu_insert]["id_modul"] = $id_modul;
				$this->query_insert[$this->pocet_zaznamu_insert]["prava"] = $prava;
				
			}else if($this->typ_pozadavku == "update"){
				//hledame zda uz radek s pravy k tomuto modulu existuje, pokud ano -> update, jinak insert
				$key = array_search($id_modul, $this->prava_update_id);
				if($key!==false){
					$this->pocet_zaznamu_update++;
					$this->query_update[$this->pocet_zaznamu_update]["id_modul"] = $id_modul;
					$this->query_update[$this->pocet_zaznamu_update]["prava"] = $prava;
				}else{
					$this->pocet_zaznamu_insert++;
					$this->query_insert[$this->pocet_zaznamu_insert]["id_modul"] = $id_modul;
					$this->query_insert[$this->pocet_zaznamu_insert]["prava"] = $prava;
				}
			}	
				
		}	//if prava <= prava_tvurce

	}

	/**po prijmuti vsech dat vytvori cely dotaz a odesle ho do mysql*/
	function finish_prava(){
	  	//print_r($this->query_insert);
	  	//print_r($this->query_update);
		
			if($this->pocet_zaznamu_insert){
				//vytvorim zacatek dotazu - prvni hodnoty by zde mely byt vzdy
				$dotaz="INSERT INTO `user_zamestnanec_prava` (`id_modul` ,`id_user` ,`prava` ) VALUES "
					."(".$this->query_insert[1]["id_modul"].",".$this->id_user.",".$this->query_insert[1]["prava"].")";
										
				//$i = 2 protoze prvni zaznam je uz ulozeny jako inicializace 
					//(vzdy musi byt alespon jeden, jinak by neprosla podminka na pocet_zaznamu_insert )
				$i=2;
				while($i<=$this->pocet_zaznamu_insert){
					//skladam jednotlive casti dotazu - vznikne jeden insert s vice vkladanymi radky
					$dotaz=$dotaz." , (".$this->query_insert[$i]["id_modul"].",".$this->id_user.",".$this->query_insert[$i]["prava"].")";
					$i++;
				}
				//echo $dotaz;
				//odeslu dotaz
				$create_ceny=$this->database->transaction_query($dotaz)
		 			or $this->chyba("Chyba pøi dotazu do databáze");	
			
			}
			if($this->pocet_zaznamu_update){
				$i=1;
				while($i<=$this->pocet_zaznamu_update){
					$dotaz="UPDATE `user_zamestnanec_prava` SET "
						."`prava`=".$this->query_update[$i]["prava"]." WHERE `id_modul`=".$this->query_update[$i]["id_modul"]."  and `id_user`=".$this->id_user." ";
					//echo $dotaz;
					//skladam jednotlive dotazy a rovnou je odesilam
					$update_ceny=$this->database->transaction_query($dotaz)
		 				or $this->chyba("Chyba pøi dotazu do databáze");	
					$i++;
				}			
			}
	}	
	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku, $id_user_klient = ""){
		if($typ_pozadavku=="create"){
                    if($id_user_klient == "") {
                        $dotaz= "INSERT INTO `user_klient` 
                                    (`jmeno`,`prijmeni`,`email`,`telefon`,`id_user_create`,`id_user_edit`)
                                VALUES
                                    ('$this->jmeno','$this->prijmeni','$this->email','$this->telefon','$this->id_zamestnance','$this->id_zamestnance')";
                    } else {
                        $dotaz= "INSERT INTO `user_zamestnanec` 
                                    (`uzivatelske_jmeno`,`heslo_sha1`,`salt`,`heslo`,`id_user_create`,`id_user_edit`,`id_user_klient`)
                                VALUES
                                    ('$this->uzivatelske_jmeno','$this->nove_heslo','$this->salt','$this->heslo','$this->id_zamestnance','$this->id_zamestnance','$id_user_klient')";
                    }
			//echo $dotaz;
			return $dotaz;
                }else if($typ_pozadavku=="get_id_user_klient"){
                        $dotaz= "SELECT `id_user_klient` FROM `user_zamestnanec` WHERE `id_user` = '$this->id_user';";
//			echo $dotaz;
			return $dotaz;	
		}else if($typ_pozadavku=="update_user_klient"){                        
			$dotaz= "UPDATE `user_klient`
                                    SET
                                             `jmeno`='$this->jmeno',`prijmeni`='$this->prijmeni',`email`='$this->email',
                                             `telefon`='$this->telefon',
                                             `id_user_edit`= $this->id_zamestnance
                                    WHERE `id_klient`=$id_user_klient
                                    LIMIT 1";
//			echo $dotaz;
			return $dotaz;		
                }else if($typ_pozadavku=="update_user_zamestnanec"){  
                    //hlavni administrator smi zmenit heslo zamestnance bez znalosti stareho
                        $zamestnanec = User_zamestnanec::get_instance();
                       if( $zamestnanec->get_hlavni_administrator()){
                           //vytvorim nove heslo ktere pouziju do databaze
                           if($this->heslo!=""){
                               $salt = substr(sha1(mt_rand(0, 1000)), 0, 10);
                               $this->nove_heslo = sha1($this->heslo.$salt);
                               $set_heslo = ", `heslo`=\"".$this->heslo."\", `heslo_sha1`=\"".$this->nove_heslo."\", `salt`=\"".$salt."\" ";
                           }else{
                               $set_heslo = "";
                           } 
                       }else{
                           $set_heslo = "";
                       }
                        $dotaz= "UPDATE `user_zamestnanec`
                                    SET `id_user_edit`= ".$this->id_zamestnance.$set_heslo."
                                    WHERE `id_user`=".$this->id_user."
                                    LIMIT 1";
//			echo $dotaz;
			return $dotaz;	
		}else if($typ_pozadavku=="update_self"){
			if($this->nove_heslo){
				$update_heslo=", `heslo_sha1`='".$this->nove_heslo."' ";
			}else{
				$update_heslo=" ";
			}
			$dotaz= "UPDATE `user_zamestnanec` 
						SET
							`jmeno`='".$this->jmeno."',`prijmeni`='".$this->prijmeni."',`email`='".$this->email."',
							`telefon`='".$this->telefon."'".$update_heslo."
						WHERE `id_user`=".$this->id_user."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete_user_zamestnanec"){
			$dotaz= "DELETE FROM `user_zamestnanec` WHERE `id_user`=$this->id_user LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
                }else if($typ_pozadavku=="delete_user_klient"){
			$dotaz= "DELETE FROM `user_klient` WHERE `id_klient`=$id_user_klient LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
                }else if($typ_pozadavku=="delete_prava"){
                        $dotaz= "DELETE FROM `user_zamestnanec_prava` WHERE `id_user`=$this->id_user LIMIT 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT uz.id_user,uz.heslo, uk.*, uz.uzivatelske_jmeno FROM `user_zamestnanec` uz LEFT JOIN `user_klient` uk ON (uz.id_user_klient = uk.id_klient)
                                WHERE uz.id_user=$this->id_user
                                LIMIT 1";
//			echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit_self"){
			$dotaz= "SELECT uz.`id_user`,uk.`jmeno`,uk.`prijmeni`,uk.`email`,uk.`telefon` 
                                FROM `user_zamestnanec` uz LEFT JOIN `user_klient` uk ON (uz.`id_user_klient` = uk.`id_klient`)
                                WHERE uz.`id_user`=$this->id_user
                                LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_prava"){
			$dotaz= "SELECT `modul_administrace`.`id_modul`, `modul_administrace`.`nazev_modulu`, `modul_administrace`.`typ_modulu`, 
								`user_zamestnanec_prava`.`prava`
						FROM `modul_administrace`
							join `user_zamestnanec_prava` on (`modul_administrace`.`id_modul` = `user_zamestnanec_prava`.`id_modul`)
						WHERE `user_zamestnanec_prava`.`id_user` =$this->id_user order by `modul_administrace`.`nazev_modulu`
						";
			//echo $dotaz;
			return $dotaz;					
		}else if($typ_pozadavku=="get_user_password"){
			$dotaz= "SELECT `id_user`,`heslo_sha1`,`salt` FROM `user_zamestnanec` 
						WHERE `id_user`=".$this->id_user."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `user_zamestnanec` 
						WHERE `id_user`=".$this->id_user."
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
			
		}else if($typ_pozadavku == "edit_self"){
			return true;	
			
		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create"){
			return $zamestnanec->get_bool_prava($id_modul,"create");			

		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				
				if($zamestnanec->get_id() == $this->id_user){
					$this->chyba("Zde nemùžete mìnit sám sebe!");
					return false;
				}				
				return true;
			}else {
				return false;
			}			
			
		}else if($typ_pozadavku == "update_self"){
			return true;	
			
		}else if($typ_pozadavku == "delete" || $typ_pozadavku == "delete_all"){
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
		//kontrolovaná data: název seriálu, popisek,  id_typ, 
		if($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "update_self"){
			if(!Validace::text($this->jmeno) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit jméno");
			}
			if(!Validace::text($this->prijmeni) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit pøíjmení");
			}
			if(!Validace::email($this->email) ){
				$ok = 0;
				$this->chyba("Špatnì vyplnìný e-mail");
			}											
		}
		if($typ_pozadavku == "create"){
			if(!Validace::text($this->uzivatelske_jmeno) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit uživatelské jméno");
			}
			if(!Validace::text($this->heslo) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit heslo");
			}									
		}				
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
	
/**zobrazeni formulare pro editaci vlastnich osobnich udaju*/
	function show_form_self(){
		
		//vytvorim jednotliva pole
		$jmeno="			<div class=\"form_row\"> <div class=\"label_float_left\">jméno: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"jmeno\" type=\"text\" value=\"".$this->jmeno."\" /></div></div>\n";
		$prijmeni="		<div class=\"form_row\"> <div class=\"label_float_left\">pøíjmení: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"prijmeni\" type=\"text\" value=\"".$this->prijmeni."\" /></div></div>\n";
		$email="			<div class=\"form_row\"> <div class=\"label_float_left\">e-mail: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"email\" type=\"text\" value=\"".$this->email."\" /></div></div>\n";
		$telefon="		<div class=\"form_row\"> <div class=\"label_float_left\">telefon: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"telefon\" type=\"text\" value=\"".$this->telefon."\" /></div></div>\n";
		
		$stare_heslo="	<div class=\"form_row\"> <div class=\"label_float_left\">staré heslo:</div> <div class=\"value\"><input  name=\"stare_heslo\" type=\"password\" value=\"\" /></div></div>\n";
		$nove_heslo1="	<div class=\"form_row\"> <div class=\"label_float_left\">nové heslo:</div> <div class=\"value\"><input  name=\"heslo1\" type=\"password\" value=\"\" /></div></div>\n";			
		$nove_heslo2="	<div class=\"form_row\"> <div class=\"label_float_left\">nové heslo - kontrola:</div> <div class=\"value\"><input  name=\"heslo2\" type=\"password\" value=\"\" /></div></div>\n";	
		
		//cil formulare
		$action="?id_zamestnanec=".$this->id_user."&amp;typ=zamestnanec&amp;pozadavek=update_self";
		//odesilaci tpacitko
		$submit= "<input type=\"submit\" value=\"Upravit údaje\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";



				
		$vystup= "<form action=\"".$action."\" method=\"post\">
						<h3>Zmìna hesla</h3>".
						$stare_heslo.$nove_heslo1.$nove_heslo2.
						"<h3>Zmìna osobních údajù</h3>".
						$jmeno.$prijmeni.$email.$telefon.
						$submit.
					"</form>";
		return $vystup;
	}
	
	
	/**zobrazeni formulare pro vytvoreni/editaci uzivatelu systemu*/
	function show_form(){
		$zamestnanec = User_zamestnanec::get_instance();
		
		if($this->typ_pozadavku=="new"){
			$username = "	<div class=\"form_row\"> <div class=\"label_float_left\">uživatelské jméno: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"uzivatelske_jmeno\" type=\"text\" value=\"".$this->uzivatelske_jmeno."\" /></div></div>\n";
			$heslo1 = "<div class=\"form_row\"> <div class=\"label_float_left\">heslo: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"heslo1\" type=\"password\" autocomplete='off' value=\"\" /></div></div>\n";
			$heslo2 = "<div class=\"form_row\"> <div class=\"label_float_left\">heslo - kontrola: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"heslo2\" type=\"password\" value=\"\" /></div></div>\n";
                        $jmeno="		<input type='hidden' name='klient_id' id='klient_id'/><div class=\"form_row\"> <div class=\"label_float_left\">jméno: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"jmeno\" id=\"klient_jmeno\" onkeyup=\"searchKlient()\" type=\"text\" value=\"".$this->jmeno."\" /></div></div>\n";
        		$prijmeni="	<div class=\"form_row\"> <div class=\"label_float_left\">pøíjmení: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"prijmeni\" id=\"klient_prijmeni\" onkeyup=\"searchKlient()\" type=\"text\" value=\"".$this->prijmeni."\" /></div></div>\n";
                        
			//cil formulare
			$action="?typ=zamestnanec&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit = "<input type=\"submit\" value=\"Vytvoøit uživatele\" />\n";	
			}else{
					$submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení uživatele</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){	
			$username = "	<div class=\"form_row\"> <div class=\"label_float_left\">uživatelské jméno:</div> <div class=\"value\">".$this->uzivatelske_jmeno."</div></div>\n";
			$heslo1 = "";
			$heslo2 = "";
                        $jmeno="		<div class=\"form_row\"> <div class=\"label_float_left\">jméno: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"jmeno\" id=\"klient_jmeno\" type=\"text\" value=\"".$this->jmeno."\" /></div></div>\n";
        		$prijmeni="	<div class=\"form_row\"> <div class=\"label_float_left\">pøíjmení: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"prijmeni\" id=\"klient_prijmeni\" type=\"text\" value=\"".$this->prijmeni."\" /></div></div>\n";
                        
                        $zamestnanec = User_zamestnanec::get_instance();
                        if( $zamestnanec->get_hlavni_administrator()){
                           //vytvorim nove heslo ktere pouziju do databaze
                           $heslo1 = "<div class=\"form_row\"> <div class=\"label_float_left\">heslo: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"heslo1\" type=\"text\" autocomplete='off' value=\"".$this->heslo."\" /></div></div>\n";
                        } 
                          
			//cil formulare
			$action="?id_zamestnanec=".$this->id_user."&amp;typ=zamestnanec&amp;pozadavek=update";
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit uživatele\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto uživatele</strong>\n";
			}
		}	
		
		//vytvorim jednotliva pole		
		$email="		<div class=\"form_row\"> <div class=\"label_float_left\">e-mail: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"email\" id=\"klient_email\" type=\"text\" value=\"".$this->email."\" /></div></div>\n";
		$telefon="	<div class=\"form_row\"> <div class=\"label_float_left\">telefon: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"telefon\" id=\"klient_telefon\" type=\"text\" value=\"".$this->telefon."\" /></div></div>\n";

		//tvorba selectu s pravy
		$array_prava=array("žádná","pouze pro ètení","tvorba nových + editace a delete vlastních objektù","tvorba + editace a delete všech objektù");
		$j=0;
		$prava="";
		$prava_zamestnanec = array();
		$prava_zamestnanec = $zamestnanec->get_prava();
						
		//pro kazde pravo uzivatele najdeme formularove pole s pravem pro vytvareneho uzivatele
		foreach($prava_zamestnanec as $id_modul=>$modul){
			//najdu zaznam o drive pridelenych pravech, pokud je
			$key = array_search($id_modul, $this->prava_update_id);
			if($key!==false){
				$soucasna_prava = $this->prava_update[$key]["prava"];
			}else{
				$soucasna_prava = intval( $_POST["prava_".$id_modul] );
			}
			
			$prava=$prava."
				<div class=\"form_row\"> 
					<div class=\"label_float_left\">
						".$modul["nazev_modulu"].":
					</div> 
					<div class=\"value\" style='margin-bottom: 4px;'>
						<select name=\"prava_".$id_modul."\">";
						$i=0;
						while( $i <= $modul["prava"] and $array_prava[$i]){
							if( $soucasna_prava == $i ){
								$prava=$prava."<option value=\"".$i."\" selected=\"selected\">".$array_prava[$i]."</option>\n";
							}else{
								$prava=$prava."<option value=\"".$i."\">".$array_prava[$i]."</option>\n";
							}
							$i++;
						}		
			$prava=$prava."					
						</select>
					</div>
				</div>";
		}
				
		$vystup= "<form action='$action' method='post'>".
						$username.$jmeno.$prijmeni.$email.$telefon.
						$heslo1.$heslo2."<h3>Práva uživatele</h3>".
						$prava.
						$submit.
					"</form><div style='float: left;width: 200px;margin-left: 120px;' id='osoby_result'></div><div style='clear: both'></div>";
		return $vystup;
	}
	
	
	function get_id() { return $this->informace["id_informace"];}
	function get_nazev() { return $this->informace["nazev"];}
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_user == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	
	}
} 




?>
