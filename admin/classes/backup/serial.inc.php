<?php
/** 
* serial.inc.php - tridy pro zobrazeni serialu
*/

/*--------------------- SERIAL -------------------------------------------*/
class Serial extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;


	protected $id_smluvni_podminky;	
	protected $id_serial;
	protected $nazev;
        protected $nazev_ubytovani;
	protected $nazev_web;
	protected $popisek;
	protected $popis;
        
        protected $id_ubytovani;
	
	protected $popis_ubytovani;
	protected $popis_stravovani;
	protected $popis_strediska;
	protected $popis_lazni;
	protected $program_zajezdu;
	
	protected $cena_zahrnuje;
	protected $cena_nezahrnuje;
	protected $poznamky;
	protected $id_typ;
	protected $id_podtyp;
	protected $strava;
	protected $doprava;
	protected $ubytovani;
        protected $ubytovani_kategorie;
	protected $dlouhodobe_zajezdy;
	protected $highlights;
	protected $jazyk;	
	protected $predregistrace;
	protected $nezobrazovat;
        
        protected $typ_provize;
	protected $vyse_provize;
        
	protected $id_sablony_zobrazeni;
	protected $id_sablony_objednavka;
	protected $id_user_create;
	protected $id_zeme;		
	
	protected $data;
	protected $serial;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serial,$nazev="",$nazev_web="",$popisek="",$popis="",
								$popis_ubytovani="",$popis_stravovani="",$popis_strediska="",$popis_lazni="",$program_zajezdu="",
								$cena_zahrnuje="",$cena_nezahrnuje="",$poznamky="",$id_typ="",$id_podtyp="",
								$strava="",$doprava="",$ubytovani="",$ubytovani_kategorie="",$dlouhodobe_zajezdy="",$highlights="",$jazyk="",$predregistrace="",$nezobrazovat="",
                                                                $typ_provize="",$vyse_provize="",$id_smluvni_podminky="",$id_sablony_zobrazeni="",$id_sablony_objednavka="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		

				
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_serial = $this->check_int($id_serial);
		
                $this->id_ubytovani = $this->check_int($_POST["id_ubytovani"]);
		
		$this->nazev = $this->check_slashes( $this->check($nazev) );
		$this->nazev_web =  $this->check_slashes( $this->nazev_web( $this->check($nazev_web) ) );
		
		if($this->nazev_web==""){
			$this->nazev_web = $this->nazev_web($this->nazev);
		}
		
		$this->popisek = $this->check_slashes(  $this->check_with_html(trim($popisek)  ));

		$this->popis = $this->check_slashes(  $this->check_with_html(trim($popis) ) );
		$this->popis_ubytovani = $this->check_slashes(  $this->check_with_html(trim($popis_ubytovani) ) );
		$this->popis_stravovani = $this->check_slashes(  $this->check_with_html(trim($popis_stravovani) ) );
		$this->popis_strediska = $this->check_slashes(  $this->check_with_html(trim($popis_strediska) ) );
		$this->popis_lazni = $this->check_slashes( $this->check_with_html(trim($popis_lazni)) );
		$this->program_zajezdu = $this->check_slashes(  $this->check_with_html(trim($program_zajezdu) ) );
		
		$this->cena_zahrnuje = $this->check_slashes(  $this->check_with_html(trim($cena_zahrnuje) ) );
		$this->cena_nezahrnuje = $this->check_slashes(  $this->check_with_html(trim($cena_nezahrnuje) ) );
		$this->poznamky = $this->check_slashes(  $this->check_with_html(trim($poznamky) ) );
		$this->id_typ = $this->check_int($id_typ);
		$this->id_podtyp = $this->check_int($id_podtyp);			
		$this->strava = $this->check_int($strava);
		$this->doprava = $this->check_int($doprava);
		$this->ubytovani = $this->check_int($ubytovani);	
                $this->ubytovani_kategorie = $this->check_int($ubytovani_kategorie);
		$this->highlights = $this->check(trim($highlights));		
		$this->predregistrace = $this->check_slashes( $this->check(trim($predregistrace)) );
		$this->nezobrazovat = $this->check_int($nezobrazovat);
                
                $this->typ_provize = $this->check_int($typ_provize);
		$this->vyse_provize = $this->check_int($vyse_provize);
                
		$this->jazyk = $this->check($jazyk);
		if($this->jazyk != "english"){
			$this->jazyk = "";
		}
		
		$this->dlouhodobe_zajezdy = $this->check_int($dlouhodobe_zajezdy);	
		$this->id_smluvni_podminky = $this->check_int($id_smluvni_podminky);
		$this->id_sablony_zobrazeni = $this->check_int($id_sablony_zobrazeni);
		$this->id_sablony_objednavka = $this->check_int($id_sablony_objednavka);
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
		
			 if( ($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update") or $this->typ_pozadavku=="copy" or $this->typ_pozadavku=="show"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku,$this->id_zamestnance,$this->id_serial))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$this->serial=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->nazev_ubytovani = $this->serial["nazev_ubytovani"];
						$this->popisek = $this->serial["popisek"];
						$this->popis = $this->serial["popis"];

						$this->podtyp = $this->serial["podtyp"];
						$this->popis_ubytovani = $this->serial["popis_ubytovani"];
						$this->popis_stravovani = $this->serial["popis_stravovani"];
						$this->popis_strediska = $this->serial["popis_strediska"];
						$this->popis_lazni = $this->serial["popis_lazni"];
						$this->program_zajezdu = $this->serial["program_zajezdu"];
						
						$this->cena_zahrnuje = $this->serial["cena_zahrnuje"];
						$this->cena_nezahrnuje = $this->serial["cena_nezahrnuje"];
						$this->poznamky = $this->serial["poznamky"];
						$this->id_typ = $this->serial["id_typ"];
						$this->id_podtyp = $this->serial["id_podtyp"];		
                                                $this->id_ubytovani = $this->serial["id_ubytovani"];	
						$this->strava = $this->serial["strava"];
						$this->doprava = $this->serial["doprava"];
						$this->ubytovani = $this->serial["ubytovani"];	
                                                $this->ubytovani_kategorie = $this->serial["ubytovani_kategorie"];
                                                if($this->ubytovani_kategorie==""){
                                                    $this->ubytovani_kategorie = 0;
                                                }                                                
						$this->highlights = $this->serial["highlights"];	
						$this->jazyk = $this->serial["jazyk"];	
						$this->predregistrace = $this->serial["predregistrace"];	
						$this->nezobrazovat = $this->serial["nezobrazovat"];
                                                
                                                $this->typ_provize = $this->serial["typ_provize"];
						$this->vyse_provize = $this->serial["vyse_provize"];
                                                
						$this->dlouhodobe_zajezdy = $this->serial["dlouhodobe_zajezdy"];	
						$this->id_smluvni_podminky = $this->serial["id_smluvni_podminky"];
						$this->id_sablony_zobrazeni = $this->serial["id_sablony_zobrazeni"];
						$this->id_sablony_objednavka = $this->serial["id_sablony_objednavka"];
											
						$this->id_zeme = $this->serial["id_zeme"];
						//echo $this->id_zeme;
					if($this->typ_pozadavku!="copy"){			
						$this->id_user_create = $this->serial["id_user_create"];								
						$this->id_serial = $this->serial["id_serial"];
						$this->nazev = $this->serial["nazev"];
						$this->nazev_web = $this->serial["nazev_web"];
					}
				
					if($this->typ_pozadavku=="copy"){
						$zeme = new Zeme_serial("show",$this->id_zamestnance,$this->id_serial);
						$info = new Informace_serial("show",$this->id_zamestnance,$this->id_serial);
						$cena = new Cena_serial("show",$this->id_zamestnance,$this->id_serial);
						$foto = new Foto_serial("show",$this->id_zamestnance,$this->id_serial);
						
						$this->data=$this->database->transaction_query($this->create_query("create"), 1 )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
						if( !$this->get_error_message() ){
							$this->id_serial = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);		
							
							$last_zeme="";
							while($zeme->get_next_radek()){
								if($zeme->get_id_zeme()!=$last_zeme){
									$last_zeme=$zeme->get_id_zeme();
									$dotaz_zeme = new Zeme_serial("create",$this->id_zamestnance,$this->id_serial,$last_zeme,$zeme->get_zakladni_zeme(),"",$zeme->get_polozka_menu())	;	
								}
								if($zeme->get_nazev_destinace()!=""){
										$dotaz_zeme = new Zeme_serial("create_destinace",$this->id_zamestnance,$this->id_serial,$last_zeme,$zeme->get_zakladni_zeme(),$zeme->get_id_destinace(),$zeme->get_polozka_menu())	;																
								}
							}				
	
							$dotaz_cena = new Cena_serial("create",$this->id_zamestnance,$this->id_serial,"",20)	;	
							while($cena->get_next_radek()){
									$dotaz_cena->add_to_query("",$cena->get_nazev_ceny(),$cena->get_kratky_nazev(),$cena->get_zkraceny_vypis(),$cena->get_poradi_ceny(),$cena->get_typ_ceny(),$cena->get_zakladni_cena(),$cena->get_kapacita_bez_omezeni(),$cena->get_use_pocet_noci());																
							}		
							$dotaz_cena->finish_query();
	
							while($info->get_next_radek()){
									$dotaz_info = new Informace_serial("create",$this->id_zamestnance,$this->id_serial,$info->get_id_informace())	;	
							}	
							
							while($foto->get_next_radek()){
									$dotaz_foto = new Foto_serial("create",$this->id_zamestnance,$this->id_serial,$foto->get_id_foto(),$foto->get_zakladni_foto())	;
							}	
							
							$this->database->commit(); //potvrdim transakci
						}					
						
					}
			}
				//	$this->chyba("popisy:".$this->popis_strediska."..".$this->popis_lazni);
			$start_transaction=1;
			if($this->typ_pozadavku == "delete_with_objednavky"){
                            $query = "delete from `objednavka` where `id_serial`=".$this->id_serial." " ;
                            $d=$this->database->transaction_query($query, $start_transaction )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                            $this->typ_pozadavku = "delete";
                            $start_transaction=0;
                        }
                        
                        
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					
					if($this->typ_pozadavku == "create"){ //pouziju jinou funkci pro odeslani dotazu - vice dotazu v transakci
						$this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku), $start_transaction )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
						
						if( !$this->get_error_message() ){
							$this->id_serial = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);						
							$dotaz_zeme = new Zeme_serial("create",$this->id_zamestnance,$this->id_serial,$_POST["id_zeme"],1,"",1)	;	
							
							if($dotaz_zeme->get_error_message()){
								$this->chyba($dotaz_zeme->get_error_message() );
							}else{
								$this->database->commit(); //potvrdim transakci
							}
						}
										
					}else{
						$this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku), $start_transaction)
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni).";".$this->create_query($this->typ_pozadavku) );
                                                
                                                $this->database->commit();
					}

					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}		
	
	
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
		}
                
                }
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
                        if($this->id_ubytovani!=0){
                            $ubyt_name = "`id_ubytovani`, ";
                            $ubyt_value = "".$this->id_ubytovani.", ";
                        }else{
                            $ubyt_name = "";
                            $ubyt_value = "";
                        }
                        $this->podtyp = "";
                        for ($i = 0; $i < 20; $i++) {
                            if($_POST["podtyp_".$i]!=""){
                                $this->podtyp .= $this->check($_POST["podtyp_".$i]).",";
                            }
                        }
                        $provize_name = "";
                        $provize_value= "";
                        if($this->typ_provize!=""){
                            $provize_name .= "`typ_provize`,";
                            $provize_value .= $this->typ_provize.", ";                            
                        }
                        
                        if($this->vyse_provize!=""){
                            $provize_name .= "`vyse_provize`,";
                            $provize_value .= $this->vyse_provize.", ";                            
                        }
                        
			$dotaz= "INSERT INTO `serial`
							(`nazev`,`nazev_web`,".$ubyt_name."`popisek`,`popis`,`popis_ubytovani`,`popis_stravovani`,`popis_strediska`,`popis_lazni`,`program_zajezdu`,`cena_zahrnuje`,`cena_nezahrnuje`,`poznamky`,
							`id_typ`,`id_podtyp`,`podtyp`,`strava`,`doprava`,`ubytovani`,`ubytovani_kategorie`,`dlouhodobe_zajezdy`,`highlights`,`jazyk`,`predregistrace`,`nezobrazovat`,".$provize_name."`id_smluvni_podminky`,`id_sablony_zobrazeni`,`id_sablony_objednavka`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('".$this->nazev."','".$this->nazev_web."',".$ubyt_value."'".$this->popisek."','".$this->popis."',
							 '".$this->popis_ubytovani."','".$this->popis_stravovani."','".$this->popis_strediska."','".$this->popis_lazni."','".$this->program_zajezdu."',
							 '".$this->cena_zahrnuje."','".$this->cena_nezahrnuje."','".$this->poznamky."',
							 ".$this->id_typ.",".$this->id_podtyp.",'".$this->podtyp."',".$this->strava.",".$this->doprava.",".$this->ubytovani.",".$this->ubytovani_kategorie.",".$this->dlouhodobe_zajezdy.",
							 '".$this->highlights."','".$this->jazyk."','".$this->predregistrace."',".$this->nezobrazovat.",".$provize_value."".$this->id_smluvni_podminky.",".$this->id_sablony_zobrazeni.",".$this->id_sablony_objednavka.",
							 ".$this->id_zamestnance.",".$this->id_zamestnance." )";
			//echo $dotaz;
                        
                        //$this->chyba($dotaz);
			return $dotaz;
		}else if($typ_pozadavku=="update"){
                        if($this->id_ubytovani!=0){
                            $ubyt_name = "`id_ubytovani` = ".$this->id_ubytovani.", ";
                        }else{
                            $ubyt_name = "`id_ubytovani` = NULL,";
                        }
                        $this->podtyp = "";
                        for ($i = 0; $i < 20; $i++) {
                            if($_POST["podtyp_".$i]!=""){
                                $this->podtyp .= $this->check($_POST["podtyp_".$i]).",";
                            }
                        }
                        
                        $provize= "";
                        if($this->typ_provize!=""){
                            $provize .= "`typ_provize` =".$this->typ_provize.", ";                         
                        }
                        
                        if($this->vyse_provize!=""){
                            $provize .= "`vyse_provize`=".$this->vyse_provize.",";                           
                        }
			$dotaz= "UPDATE  `serial`
						SET
							`nazev`='".$this->nazev."',`nazev_web`='".$this->nazev_web."',".$ubyt_name."
							`popisek`='".$this->popisek."',`popis`='".$this->popis."',
							`popis_ubytovani`='".$this->popis_ubytovani."',`popis_stravovani`='".$this->popis_stravovani."',`popis_strediska`='".$this->popis_strediska."',`popis_lazni`='".$this->popis_lazni."',`program_zajezdu`='".$this->program_zajezdu."',
							`cena_zahrnuje`='".$this->cena_zahrnuje."',`cena_nezahrnuje`='".$this->cena_nezahrnuje."',`poznamky`='".$this->poznamky."',
							`id_typ`=".$this->id_typ.",`id_podtyp`=".$this->id_podtyp.",`podtyp`='".$this->podtyp."',
							`strava`=".$this->strava.",`doprava`=".$this->doprava.",`ubytovani`=".$this->ubytovani.",`ubytovani_kategorie`=".$this->ubytovani_kategorie.",`dlouhodobe_zajezdy`=".$this->dlouhodobe_zajezdy.",
							`highlights`='".$this->highlights."',`jazyk`='".$this->jazyk."',`predregistrace`='".$this->predregistrace."',`nezobrazovat`='".$this->nezobrazovat."',".$provize."`id_smluvni_podminky`=".$this->id_smluvni_podminky.",`id_sablony_zobrazeni`=".$this->id_sablony_zobrazeni.",`id_sablony_objednavka`=".$this->id_sablony_objednavka.",
							`id_user_edit`=".$this->id_zamestnance."
						WHERE `id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `serial` 
						WHERE `id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT `serial`.*, `zeme_serial`.*, `ubytovani`.`id_ubytovani`, `ubytovani`.`nazev` as `nazev_ubytovani`
                                                FROM `serial`
                                                    join `zeme_serial` on (`zeme_serial`.`zakladni_zeme`=1 and `zeme_serial`.`id_serial` = `serial`.`id_serial`)
                                                    left join `ubytovani` on  (`serial`.`id_ubytovani`= `ubytovani`.`id_ubytovani`)
						WHERE `serial`.`id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT `serial`.*, `zeme_serial`.*, `ubytovani`.`id_ubytovani`, `ubytovani`.`nazev` as `nazev_ubytovani`
                                                FROM `serial`
                                                    join `zeme_serial` on (`zeme_serial`.`zakladni_zeme`=1 and `zeme_serial`.`id_serial` = `serial`.`id_serial`)
                                                    left join `ubytovani` on  (`serial`.`id_ubytovani`= `ubytovani`.`id_ubytovani`)
						WHERE `serial`.`id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
                }else if($typ_pozadavku=="show_objednavky"){
			$dotaz= "SELECT `serial`.`id_serial`, `serial`.`nazev`, `zajezd`.`nazev_zajezdu`, `zajezd`.`od`, `zajezd`.`do`, `ubytovani`.`nazev` as `nazev_ubytovani`,
                                        `objednavka`.*,`klient`.`jmeno`, `klient`.`prijmeni`, `agentura`.`prijmeni` as `kontaktni_osoba`, `agentura`.`jmeno` as `nazev_ca`
                                                FROM `serial`
                                                    join `zeme_serial` on (`zeme_serial`.`zakladni_zeme`=1 and `zeme_serial`.`id_serial` = `serial`.`id_serial`)
                                                    join `objednavka` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
                                                    left join `user_klient` as `klient` on (`objednavka`.`id_klient` = `klient`.`id_klient`)
                                                    left join `user_klient` as `agentura` on (`objednavka`.`id_agentury` = `agentura`.`id_klient`)
                                                    left join `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                                    left join `ubytovani` on  (`serial`.`id_ubytovani`= `ubytovani`.`id_ubytovani`)
						WHERE `serial`.`id_serial`=".$this->id_serial."
						";
			//echo $dotaz;
			return $dotaz;        
		}else if($typ_pozadavku=="copy"){
			$dotaz= "SELECT `serial`.*, `zeme_serial`.*, `ubytovani`.`id_ubytovani`, `ubytovani`.`nazev` as `nazev_ubytovani`
                                                FROM `serial`
                                                    join `zeme_serial` on (`zeme_serial`.`zakladni_zeme`=1 and `zeme_serial`.`id_serial` = `serial`.`id_serial`)
                                                    left join `ubytovani` on  (`serial`.`id_ubytovani`= `ubytovani`.`id_ubytovani`)
						WHERE `serial`.`id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
						
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `serial` 
						WHERE `id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_ubytovani"){
			$dotaz= "SELECT `id_ubytovani`,`nazev`  FROM `ubytovani`
						WHERE 1 order by `nazev`
						";
			//echo $dotaz;
			return $dotaz;
                }else if($typ_pozadavku=="smluvni_podminky"){
			$dotaz= "SELECT * FROM `dokument` 
						WHERE `dokument_url` like \"%smluvni-podminky%\"
						Order by `nazev_dokument`";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="templateValueZobrazeni"){
			$dotaz= "SELECT * FROM `sablony` 
						WHERE `typ_sablony` like \"%zobrazit%\"
						Order by `id_sablony`";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="templateValueObjednavka"){
			$dotaz= "SELECT * FROM `sablony` 
						WHERE `typ_sablony` like \"%objednavka_text%\"
						Order by `id_sablony`";
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
					
		}else if($typ_pozadavku == "copy"){
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
		}else if($typ_pozadavku == "delete_with_objednavky"){
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
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název seriálu");
			}
			//echo $this->popisek;
			//echo "validace".Validace::text($this->popisek);
			
			if(!Validace::text($this->popisek) ){
				
				
				$ok = 0;
				$this->chyba("Musíte vyplnit popisek seriálu");
			}
			if(!Validace::int_min($this->id_typ,1) ){
				$ok = 0;
				$this->chyba("Nevyplnìný typ seriálu");
			}									
		
		}

		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}

	}

	/**zobrazeni menu - moznosti editace pro konkretni serial*/
	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];
                
                if($this->nazev_ubytovani!=""){
                   $ubyt = $this->nazev_ubytovani.",";
                }else{
                   $ubyt="";
                }
			
		$vypis = $ubyt." ".$this->nazev.": ";	
		$vypis = $vypis."
                                                <a href=\"/zajezdy/zobrazit/".$this->nazev_web."\" target=\"_blank\">zobrazit</a>
						| <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=serial&amp;pozadavek=edit\">seriál</a>
					 	| <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=cena\">služby</a>
					 	| <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=zajezd_list\">zájezdy</a>";
					 	
		if($adresa_zeme = $core->get_adress_modul_from_typ("zeme") ){					
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=zeme\">zemì/destinace</a>";
		}		
		if($adresa_foto = $core->get_adress_modul_from_typ("fotografie") ){	 			
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=foto\">foto</a>";
		}
		if($adresa_dokumenty = $core->get_adress_modul_from_typ("dokumenty") ){
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=dokument\">dokumenty</a>";
		}
		if($adresa_informace = $core->get_adress_modul_from_typ("informace") ){
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=informace\">informace</a>";						
		}
		if($adresa_slevy = $core->get_adress_modul_from_typ("slevy") ){
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=slevy\">slevy</a>";						
		}
                if($adresa_slevy = $core->get_adress_modul_from_typ("objednavky") ){
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=serial&amp;pozadavek=objednavky\">objednávky</a>";						
		}
		$vypis = $vypis."| <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=serial&amp;pozadavek=delete\">delete</a>";
                
                $vypis = $vypis."| <a href=\"".$adresa_modulu."?id_serial=".$this->id_serial."&amp;typ=serial&amp;pozadavek=delete_with_objednavky\">smazat vèetnì objednávek!</a>";
		return $vypis;
	}
	
	function show_objednavky(){
            $data=$this->database->query($this->create_query("show_objednavky") )
		 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
            $result = "<table class=\"list\">
                <tr>
                    <th>Id
                    <th>Zájezd
                    <th>Objednávající
                    <th>Prodejce
                    <th>Celková cena
                    <th>Stav
                    ";
            while($row = mysqli_fetch_array($data)){
                
                
                $result.="
                    <tr class=\"suda\">
                        <td><a href=\"rezervace.php?id_objednavka=".$row["id_objednavka"]."&typ=rezervace&pozadavek=show\">".$row["id_objednavka"]."</a>
                        <td>".$row["nazev_zajezdu"]." ".$row["od"]." - ".$row["do"]."
                        <td><a href=\"klienti.php?id_klient=".$row["id_klient"]."&typ=klient&pozadavek=edit\">".$row["jmeno"]." ".$row["prijmeni"]."</a>
                        <td>".$row["nazev_ca"]." ".$row["kontaktni_osoba"]."
                        <td>".$row["celkova_cena"]." Kè
                        <td>".Rezervace_library::get_stav(($row["stav"]-1))."";
            }
            $result.="</table>";
           return $result; 
        }
        /**zobrazeni formulare pro vytvoreni/editaci serialu*/
	function show_form(){
		if($this->nazev_ubytovani!=""){
                   $ubyt = "<b>".$this->nazev_ubytovani."</b>";
                }else{
                   $ubyt="";
                }
		//vytvorim jednotliva pole
                $javascript_hideshow_functions = "
                   <script language=\"JavaScript\" type=\"text/javascript\" >
                    function change_display_vyse_provize(){
                        var typ_provize = document.getElementById(\"typ_provize\").value;
                        
                        if(typ_provize == \"0\"){
                            hide(\"vyse_provize_wrapper\");
                        }else if(typ_provize == \"1\"){
                            show(\"vyse_provize_wrapper\");
                            hide(\"vyse_provize_procenta\") ;
                            show(\"vyse_provize_fixni\") ;  

                        }else if(typ_provize == \"2\"){
                            show(\"vyse_provize_wrapper\");
                            show(\"vyse_provize_procenta\") ;
                            hide(\"vyse_provize_fixni\"); 
                            
                        }else if(typ_provize == \"3\"){
                            hide(\"vyse_provize_wrapper\");
                        }
                    }

                    function change_shown_items(){                        
                        var sablona_value = document.getElementById(\"id_sablony_zobrazeni\").value;
                        var typ_value = document.getElementById(\"id_typ_serialu\").value;
                        
                        if(sablona_value == \"8\"){
                                show_predbezna_registrace();
                        }else if(typ_value == \"1:\" || typ_value == \"8:\" || typ_value == \"7:\" ){
                            show_pobytove();
                        }else if(typ_value == \"2:\" || typ_value == \"9:\"){
                            show_poznavaci();
                        }else if(typ_value == \"6:\"){
                            show_jednodenni();
                        }else if(typ_value == \"3:\"){
                            show_lazne();
                        }else if(typ_value == \"5:\"){
                            show_lyzovani();
                        }else if(typ_value == \"4:\"){
                            if(sablona_value == \"7\"){
                                show_vstupenky();
                            }else if(sablona_value == \"10\"){
                                show_poprad();
                            }else if(sablona_value == \"0\"){
                                hide_all();
                                show(\"sablony_zobrazeni\");
                            }else{
                                show_za_sportem();
                            }
                        }else{
                            hide_all();
                        }
                    }
                    function show_pobytove(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"nazev_ubytovani\");
                        show(\"popis_ubytovani\");
                        show(\"popis_stravovani\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                        show(\"dlouhodobe_serialy\");
                       show(\"strava\");
                       show(\"doprava\");
                       show(\"ubytovani\");
                       show(\"ubytovani_kategorie\");
                    }
                    function show_poznavaci(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"popis_stravovani\");
                        show(\"popis_ubytovani\");
                        show(\"program_zajezdu\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                        show(\"strava\");
                       show(\"doprava\");
                       show(\"ubytovani\");
                       show(\"ubytovani_kategorie\");
                    }
                    function show_jednodenni(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"program_zajezdu\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                        show(\"strava\");
                        show(\"doprava\");
                    }
                    function show_lazne(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"nazev_ubytovani\");
                        show(\"popis_ubytovani\");
                        show(\"podtyp_serialu\");
                        show(\"popis_stravovani\");
                        show(\"zamereni_lazni\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                        show(\"dlouhodobe_serialy\");
                       show(\"strava\");
                       show(\"doprava\");
                       show(\"ubytovani\");   
                       show(\"ubytovani_kategorie\");
                    }
                    function show_lyzovani(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"nazev_ubytovani\");
                        show(\"popis_ubytovani\");
                        show(\"popis_stravovani\");
                        show(\"lyzarske_stredisko\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                        show(\"dlouhodobe_serialy\");
                       show(\"strava\");
                       show(\"doprava\");
                       show(\"ubytovani\"); 
                       show(\"ubytovani_kategorie\");
                    }     
                    function show_za_sportem(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"nazev_ubytovani\");
                        show(\"popis_stravovani\");
                        show(\"popis_ubytovani\");
                        show(\"program_zajezdu\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"dlouhodobe_serialy\");
                        show(\"poznamky\");
                       show(\"strava\");
                       show(\"doprava\");
                       show(\"ubytovani\");      
                       show(\"ubytovani_kategorie\");
                    }
                    function show_poprad(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"nazev_ubytovani\");
                        show(\"popis_stravovani\");
                        show(\"program_zajezdu\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                       show(\"strava\");
                       show(\"doprava\");
                       show(\"ubytovani\");
                       show(\"ubytovani_kategorie\");
                    }
                    function show_vstupenky(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");                       
                    } 
                    function show_predbezna_registrace(){
                        hide_all();
                        show_basic();
                        show(\"spravce\");
                        show(\"predbezna_registrace\");
                        show(\"highlights\");
                        show(\"cena_zahrnuje\");
                        show(\"cena_nezahrnuje\");
                        show(\"poznamky\");
                        show(\"strava\");
                        show(\"doprava\");
                        show(\"ubytovani\"); 
                        show(\"ubytovani_kategorie\");
                    }
                    function show_basic(){
                       show(\"sablony_zobrazeni\");
                       show(\"zeme\");
                       show(\"nazev\");
                       show(\"nazev_web\");
                       show(\"popisek\");
                       show(\"popis\");
                       show(\"nezobrazovat\");
                       show(\"sablony\");
                       show(\"smluvni_podminky\");
                       show(\"pocet_cen\");
                       show(\"submit\");
                       show(\"typ_provize_wrapper\");
                       change_display_vyse_provize();
                       
                    }

                    function hide_all() {
                        //all ids!!
			var ids = new Array(\"spravce\",\"vyse_provize_wrapper\",\"typ_provize_wrapper\",\"nazev\",\"nazev_web\",\"zeme\",\"sablony_zobrazeni\",\"nazev_ubytovani\",\"podtyp_serialu\",\"popisek\",\"popis\",\"popis_stravovani\",\"popis_ubytovani\",\"lyzarske_stredisko\",\"zamereni_lazni\",\"program_zajezdu\",\"highlights\",\"predbezna_registrace\",\"nezobrazovat\",\"cena_zahrnuje\",\"cena_nezahrnuje\",\"poznamky\",\"strava\",\"doprava\",\"ubytovani\",\"ubytovani_kategorie\",\"sablony\",\"smluvni_podminky\",\"dlouhodobe_serialy\",\"anglicky\",\"pocet_cen\",\"submit\",\"\");
                        var i = 0;
                            while(ids[i]!=\"\"){
                                var id = ids[i];                                
                                i++;
				if (document.getElementById) { // DOM3 = IE5, NS6
                                    if(document.getElementById(id)!=null){
					document.getElementById(id).style.display = 'none';
                                    }
				}else {
					if (document.layers) { // Netscape 4
						document.id.display = 'none';
					}else { // IE 4
						document.all.id.style.display = 'none';
					}
				}
                            }
                    }

                    function show(id) {
				//safe function to show an element with a specified id
				if (document.getElementById) { // DOM3 = IE5, NS6
                                    if(document.getElementById(id)!=null){
					document.getElementById(id).style.display = 'block';
                                    }
				}else {
					if (document.layers) { // Netscape 4
						document.id.display = 'block';
					}else { // IE 4
						document.all.id.style.display = 'block';
					}
				}
                    }
                    function hide(id) {
				//safe function to show an element with a specified id
				if (document.getElementById) { // DOM3 = IE5, NS6
                                    if(document.getElementById(id)!=null){
					document.getElementById(id).style.display = 'none';
                                    }
				}else {
					if (document.layers) { // Netscape 4
						document.id.display = 'none';
					}else { // IE 4
						document.all.id.style.display = 'none';
					}
				}
                    }
                    
                   </script>
                ";



                if($this->id_typ == 4){
                    $nazev="<div class=\"form_row\" id=\"nazev\"> <div class=\"label_float_left\">Název seriálu: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" class=\"wide\"/>,".$ubyt."</div></div>\n
			<div class=\"form_row\" id=\"nazev_web\"> <div class=\"label_float_left\">Název pro web: </div> <div class=\"value\"> <input name=\"nazev_web\" type=\"text\" value=\"".$this->nazev_web."\" class=\"wide\"/></div></div>\n";
                }else{
                    $nazev="<div class=\"form_row\" id=\"nazev\"> <div class=\"label_float_left\">Název seriálu: <span class=\"red\">*</span></div> <div class=\"value\"> ".$ubyt.", <input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" class=\"wide\"/></div></div>\n
			<div class=\"form_row\" id=\"nazev_web\"> <div class=\"label_float_left\">Název pro web: </div> <div class=\"value\"> <input name=\"nazev_web\" type=\"text\" value=\"".$this->nazev_web."\" class=\"wide\"/></div></div>\n";
                }

                $podtyp_serialu="
		<div class=\"form_row\" id=\"podtyp_serialu\" > <div class=\"label_float_left\">Podtyp seriálu (láznì)</div> <div class=\"value\">
                    <input type=\"checkbox\" name=\"podtyp_1\" value=\"lecebne-pobyty\" ".((stripos($this->podtyp, "lecebne-pobyty")!==false)?("checked=\"checked\""):(""))." /> Léèebný pobyt
                    <input type=\"checkbox\" name=\"podtyp_2\" value=\"wellness-pobyty\" ".((stripos($this->podtyp, "wellness-pobyty")!==false)?("checked=\"checked\""):(""))." /> Wellness
                    <input type=\"checkbox\" name=\"podtyp_3\" value=\"termalni-lazne\" ".((stripos($this->podtyp, "termalni-lazne")!==false)?("checked=\"checked\""):(""))." /> Termální láznì
                    <input type=\"checkbox\" name=\"podtyp_4\" value=\"termalni-koupaliste\" ".((stripos($this->podtyp, "termalni-koupaliste")!==false)?("checked=\"checked\""):(""))." /> Termální koupalištì
                    <input type=\"checkbox\" name=\"podtyp_5\" value=\"seniorske-pobyty\" ".((stripos($this->podtyp, "seniorske-pobyty")!==false)?("checked=\"checked\""):(""))." /> Seniorský pobyt
                    <input type=\"checkbox\" name=\"podtyp_6\" value=\"vikendove-pobyty\" ".((stripos($this->podtyp, "vikendove-pobyty")!==false)?("checked=\"checked\""):(""))." /> Víkendový pobyt
                    <input type=\"checkbox\" name=\"podtyp_7\" value=\"relaxacni-pobyty\" ".((stripos($this->podtyp, "relaxacni-pobyty")!==false)?("checked=\"checked\""):(""))." /> Relaxaèní/hotelové pobyty
                    <input type=\"checkbox\" name=\"podtyp_8\" value=\"specialni-balicky\" ".((stripos($this->podtyp, "specialni-balicky")!==false)?("checked=\"checked\""):(""))." /> Speciální balíèek (rùzné)
                </div></div>\n";


		$popisek="
                    <div class=\"form_row\" id=\"popisek\"> <div class=\"label_float_left\">Popisek: <span class=\"red\">*</span></div> <div class=\"value\"> <textarea name=\"popisek\" id=\"popisek_\" rows=\"5\" cols=\"100\">".$this->popisek."</textarea>
                    </div></div>\n";
	
		$popis="
                    <div class=\"form_row\" id=\"popis\"> <div class=\"label_float_left\">Popis:</div> <div class=\"value\"> <textarea name=\"popis\" id=\"popis_\" rows=\"20\" cols=\"100\">".$this->popis."</textarea>

								</div></div>\n";					
		
		$popis_ubytovani="
                    <div class=\"form_row\" id=\"popis_ubytovani\"> <div class=\"label_float_left\">Popis ubytování:</div> <div class=\"value\"> <textarea name=\"popis_ubytovani\" id=\"popis_ubytovani_\" rows=\"5\" cols=\"100\">".$this->popis_ubytovani."</textarea></div></div>\n";
		
		$popis_stravovani="
                    <div class=\"form_row\" id=\"popis_stravovani\"> <div class=\"label_float_left\">Popis stravování:</div> <div class=\"value\"> <textarea name=\"popis_stravovani\" id=\"popis_stravovani_\" rows=\"5\" cols=\"100\">".$this->popis_stravovani."</textarea></div></div>\n";
		
		$popis_strediska="
                    <div class=\"form_row\" id=\"lyzarske_stredisko\"> <div class=\"label_float_left\">Charakterizace lyž. støed. (oddìlujte støedníkem ;)</div> <div class=\"value\"> <textarea name=\"popis_strediska\" id=\"popis_strediska_\" rows=\"8\" cols=\"100\">".$this->popis_strediska."</textarea></div></div>\n";
		
		$popis_lazni="
                    <div class=\"form_row\" id=\"zamereni_lazni\" > <div class=\"label_float_left\">Zamìøení lázní - heslovitì (oddìlujte støedníkem ;)</div> <div class=\"value\"> <textarea name=\"popis_lazni\" id=\"popis_lazni_\" rows=\"8\" cols=\"100\">".$this->popis_lazni."</textarea></div></div>\n";


		$program_zajezdu="
                    <div class=\"form_row\" id=\"program_zajezdu\"> <div class=\"label_float_left\">Program zájezdu</div> <div class=\"value\"> <textarea name=\"program_zajezdu\" id=\"program_zajezdu_\" rows=\"10\" cols=\"100\">".$this->program_zajezdu."</textarea></div></div>\n";
		
		$cena_zahrnuje="
                    <div class=\"form_row\" id=\"cena_zahrnuje\"> <div class=\"label_float_left\">Cena zahrnuje:</div> <div class=\"value\"> <textarea name=\"cena_zahrnuje\" id=\"cena_zahrnuje_\" rows=\"5\" cols=\"100\">".$this->cena_zahrnuje."</textarea></div></div>\n";

		$cena_nezahrnuje="
                    <div class=\"form_row\" id=\"cena_nezahrnuje\"> <div class=\"label_float_left\">Cena nezahrnuje:</div> <div class=\"value\"> <textarea name=\"cena_nezahrnuje\" id=\"cena_nezahrnuje_\" rows=\"5\" cols=\"100\">".$this->cena_nezahrnuje."</textarea></div></div>\n";

		$poznamky="
                    <div class=\"form_row\" id=\"poznamky\"> <div class=\"label_float_left\">Poznamky:</div> <div class=\"value\"> <textarea name=\"poznamky\" id=\"poznamky_\" rows=\"10\" cols=\"100\">".$this->poznamky."</textarea></div></div>\n";

		$highlights="
                    <div class=\"form_row\" id=\"highlights\" > <div class=\"label_float_left\">Highlights (oddìlujte èárkou): </div> <div class=\"value\"> <textarea name=\"highlights\" rows=\"3\" cols=\"100\">".$this->highlights."</textarea></div></div>\n";
								
		$predregistrace="
                    <div class=\"form_row\" id=\"predbezna_registrace\" > <div class=\"label_float_left\">Pøedbìžná registrace</div> <div class=\"value\"> Jednotlivé položky pro pøedbìžnou registraci (napø jednotlivé sporty): oddìlujte èárkou<br/><textarea name=\"predregistrace\" rows=\"3\" cols=\"100\">".$this->predregistrace."</textarea></div></div>\n";
		$nezobrazovat="
                    <div class=\"form_row\" id=\"nezobrazovat\"> <div class=\"label_float_left\">Nezobrazovat seriál</div> <div class=\"value\"> Zaškrtnìte, pokud nechcete, aby se seriál zobrazoval klientùm<br/><input type=\"checkbox\" name=\"nezobrazovat\" value=\"1\" ".(($this->nezobrazovat==1)?("checked=\"checked\""):(""))." />\n
							</div></div>\n";

		$data_ubytovani = $this->database->query($this->create_query("get_ubytovani"))
		 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		$ubytovani_vyber="
                        <div class=\"form_row\" id=\"nazev_ubytovani\"> <div class=\"label_float_left\">Ubytování pøiøazené k seriálu: </div> <div class=\"value\">
			<select name=\"id_ubytovani\" class=\"wide\">\n";
                        $ubytovani_vyber.="<option value=\"0\">---</option>";
			while($zaznam_tvz = mysqli_fetch_array($data_ubytovani)){
				if($zaznam_tvz["id_ubytovani"] == $this->id_ubytovani){
					$tvz_selected=" selected=\"selected\" ";
				}else{
					$tvz_selected=" ";
				}
				$ubytovani_vyber .= "<option value=\"".$zaznam_tvz["id_ubytovani"]."\"".$tvz_selected.">".$zaznam_tvz["nazev"]."</option>\n";
			}
                 $ubytovani_vyber.="</select>
                        </div></div>\n";

		$make_whizywig = "								
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"popisek_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
					makeWhizzyWig(\"popis_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"popis_ubytovani_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"popis_stravovani_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"popis_strediska_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"popis_lazni_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"program_zajezdu_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"cena_zahrnuje_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"cena_nezahrnuje_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"poznamky_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");					
                                        hide_all();
                                        change_shown_items();
                                </script>
				";
			

		$data_templateValueZobrazeni=$this->database->query($this->create_query("templateValueZobrazeni"))
		 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		$templateValueZobrazeni="";			
			while($zaznam_tvz = mysqli_fetch_array($data_templateValueZobrazeni)){
				if($zaznam_tvz["id_sablony"] == $this->id_sablony_zobrazeni){
					$tvz_selected=" selected=\"selected\" ";
				}else{
					$tvz_selected=" ";
				}
				$templateValueZobrazeni .= "<option value=\"".$zaznam_tvz["id_sablony"]."\"".$tvz_selected.">".$zaznam_tvz["nazev_sablony"]."</option>\n";			
			}	
		
		
		$data_templateValueObjednavka=$this->database->query($this->create_query("templateValueObjednavka"))
		 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		$templateValueObjednavka="";			
			while($zaznam_tob = mysqli_fetch_array($data_templateValueObjednavka)){
				if($zaznam_tob["id_sablony"] == $this->id_sablony_objednavka){
					$tob_selected=" selected=\"selected\" ";
				}else{
					$tob_selected=" ";
				}
				$templateValueObjednavka .= "<option value=\"".$zaznam_tob["id_sablony"]."\"".$tob_selected.">".$zaznam_tob["nazev_sablony"]."</option>\n";			
			}

                $template_serial="
                    <div class=\"form_row\" id=\"sablony_zobrazeni\"> <div class=\"label_float_left\">Zobrazení seriálu: </div> <div class=\"value\">
			<select name=\"id_sablony_zobrazeni\" id=\"id_sablony_zobrazeni\" class=\"wide\" onchange=\"change_shown_items();\">\n
                                <option value=\"0\">---</option>
				".$templateValueZobrazeni."
			</select>
                    </div></div>\n
                ";

		$templates="                    
                    <div class=\"form_row\"  id=\"sablony\"> <div class=\"label_float_left\">doplòující text objednávky: </div> <div class=\"value\">
			<select name=\"id_sablony_objednavka\" class=\"wide\">\n
				".$templateValueObjednavka."
			</select>
                    </div></div>\n";


		
		//tvorba select stravovani
		$i=0;
		$strava="<div class=\"form_row\"  id=\"strava\"> <div class=\"label_float_left\">Stravování:</div> <div class=\"value\">
							<select name=\"strava\">\n";						
			while(Serial_library::get_typ_stravy($i)!=""){
				if($this->strava==($i+1)){
					$strava=$strava."<option value=\"".($i+1)."\" selected=\"selected\">".Serial_library::get_typ_stravy($i)."</option>\n";
				}else{
					$strava=$strava."<option value=\"".($i+1)."\">".Serial_library::get_typ_stravy($i)."</option>\n";
				}
				$i++;
			}
			$strava=$strava."</select>\n</div></div>\n";	

		$i=0;
			$doprava="<div class=\"form_row\"  id=\"doprava\"> <div class=\"label_float_left\">Doprava:</div> <div class=\"value\">
							<select name=\"doprava\">\n";						
			while(Serial_library::get_typ_dopravy($i)!=""){
				if($this->doprava==($i+1)){
					$doprava=$doprava."<option value=\"".($i+1)."\" selected=\"selected\">".Serial_library::get_typ_dopravy($i)."</option>\n";
				}else{
					$doprava=$doprava."<option value=\"".($i+1)."\">".Serial_library::get_typ_dopravy($i)."</option>\n";
				}
				$i++;
			}
			$doprava=$doprava."</select>\n</div></div>\n";			
	
		//tvorba select ubytovani
		$i=0;
			$ubytovani="<div class=\"form_row\"  id=\"ubytovani\"> <div class=\"label_float_left\">Ubytování:</div> <div class=\"value\">
							<select name=\"ubytovani\">\n";						
			while(Serial_library::get_typ_ubytovani($i)!=""){
				if($this->ubytovani==($i+1)){
					$ubytovani=$ubytovani."<option value=\"".($i+1)."\" selected=\"selected\">".Serial_library::get_typ_ubytovani($i)."</option>\n";
				}else{
					$ubytovani=$ubytovani."<option value=\"".($i+1)."\">".Serial_library::get_typ_ubytovani($i)."</option>\n";
				}
				$i++;
			}
			$ubytovani=$ubytovani."</select>\n</div></div>\n";
                        
                        $ubytovani .= "<div class=\"form_row\"  id=\"ubytovani_kategorie\"> <div class=\"label_float_left\">Kategorie ubytování:</div> <div class=\"value\">
					<select name=\"ubytovani_kategorie\">\n
                                              <option value=\"0\" >----</option>\n
                                              <option value=\"1\" ".(($this->ubytovani_kategorie==1)?("selected=\"selected\""):(""))." > 1* </option>\n
                                              <option value=\"2\" ".(($this->ubytovani_kategorie==2)?("selected=\"selected\""):(""))."> 2* </option>\n
                                              <option value=\"3\" ".(($this->ubytovani_kategorie==3)?("selected=\"selected\""):(""))."> 3* </option>\n
                                              <option value=\"4\" ".(($this->ubytovani_kategorie==4)?("selected=\"selected\""):(""))."> 4* </option>\n
                                              <option value=\"5\" ".(($this->ubytovani_kategorie==5)?("selected=\"selected\""):(""))."> 5* </option>\n
                                        </select>\n</div></div>\n";
                        
				
		//tvorba checkbox dlouhodobe zajezdy
			$dlouhodobe_zajezdy="<div class=\"form_row\"  id=\"dlouhodobe_serialy\"> Seriál má dlouhodobé zájezdy (rozsah termínu je delší než délka pobytu):
							<input type=\"checkbox\" name=\"dlouhodobe_zajezdy\" value=\"1\" ".(($this->dlouhodobe_zajezdy==1)?("checked=\"checked\""):(""))." />\n
							</div>\n
							<div class=\"form_row\" id=\"anglicky\"> Seriál je v angliètinì:
							<input type=\"checkbox\" name=\"jazyk\" value=\"english\" ".(($this->jazyk=="english")?("checked=\"checked\""):(""))." />\n
							</div>\n
                                                        
                                            <div class=\"form_row\"  id=\"typ_provize_wrapper\"> Typ výpoètu provize:
							<select id=\"typ_provize\" name=\"typ_provize\" onchange=\"change_display_vyse_provize();\">\n
                                                            <option value=\"0\" >Neurèeno</option>\n
                                                             <option value=\"1\" ".(($this->typ_provize==1)?("selected=\"selected\""):(""))." > Fixní sazba za osobu</option>\n
                                                             <option value=\"2\" ".(($this->typ_provize==2)?("selected=\"selected\""):(""))."> Procentuelní z celé objednávky</option>\n
                                                             <option value=\"3\" ".(($this->typ_provize==3)?("selected=\"selected\""):(""))."> Dle služeb</option>\n
                                                        </select>
							</div>\n
							<div class=\"form_row\" id=\"vyse_provize_wrapper\"> 
                                                            <span id=\"vyse_provize_procenta\">Sazba provize v procentech:</span>
                                                            <span id=\"vyse_provize_fixni\">Sazba provize za osobu v KÈ:</span>
							<input type=\"text\" name=\"vyse_provize\" value=\"".$this->vyse_provize."\" />\n
							</div>\n
							";					
		//tvorba select typ-podtyp
		$typ="<div class=\"form_row\"> <div class=\"label_float_left\">Typ zájezdu: <span class=\"red\">*</span></div>
					 <div class=\"value\">
					<select name=\"typ-podtyp\" id=\"id_typ_serialu\" class=\"wide\"  onchange=\"change_shown_items();\">\n
                                            <option value=\"0:\">---</option>
                                        ";
		
		//do promenne typy_serialu vytvorim instanci tridy seznam typu serialu a nasledne vypisu seznam typu					
		$typy_serialu = new Typ_list($this->id_zamestnance,"",$this->id_typ,$this->id_podtyp);
		//vypisu seznam typu a podtypu
		$typ = $typ.$typy_serialu->show_list("select_typ_podtyp");											
		$typ=$typ."</select>\n</div></div>\n";		
		
		
		//tvorba selectu smluvnich podminek
			$data_smluvni_podminky=$this->database->query($this->create_query("smluvni_podminky"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			
			$smluvni_podminky = "<div class=\"form_row\"  id=\"smluvni_podminky\"> <div class=\"label_float_left\">Typ smluvních podmínek: <span class=\"red\">*</span></div>
					 <div class=\"value\">
					 <select name=\"id_smluvni_podminky\" class=\"wide\">\n";
			while($zaznam_smp = mysqli_fetch_array($data_smluvni_podminky)){
				if($zaznam_smp["id_dokument"] == $this->id_smluvni_podminky){
					$sml_selected=" selected=\"selected\" ";
				}else{
					$sml_selected=" ";
				}
				$smluvni_podminky .= "<option value=\"".$zaznam_smp["id_dokument"]."\"".$sml_selected.">".$zaznam_smp["nazev_dokument"]."</option>\n";			
			}
			$smluvni_podminky .= "</select>\n</div></div>\n";
		
		
		//tvorba select zeme (pouze pri novem serialu)
		if($this->typ_pozadavku=="new"){
			$zeme="<div class=\"form_row\" id=\"zeme\"> <div class=\"label_float_left\">Základní zemì <span class=\"red\">*</span></div> <div class=\"value\">
							<select name=\"id_zeme\" class=\"wide\">\n
                                                            <option value=\"0\">---</option>";
							
			//do promenne typy_serialu vytvorim instanci tridy seznam zemi
			$zeme_serialu = new Zeme_list($this->id_zamestnance,"");
			//vypisu seznam zemi
			$zeme = $zeme.$zeme_serialu->show_list("select_zeme");		
			$zeme=$zeme."</select>\n</div></div>\n";	
			
			//cil formulare
			$action="?typ=serial&amp;pozadavek=create";
			
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<div class=\"form_row\"  id=\"pocet_cen\"> <div class=\"label_float_left\">Poèet cen: <span class=\"red\">*</span></div>\n  <div class=\"value\"><input name=\"pocet_cen\" type=\"text\" value=\"1\" /></div></div>\n
							<input  id=\"submit\" type=\"submit\" value=\"Vytvoøit seriál\" />\n";
						
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení seriálu</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){
			$zeme="";
			
			//cil formulare
			$action="?id_serial=".$this->id_serial."&amp;typ=serial&amp;pozadavek=update";
				
			if(  $this->legal("update") ){

					$submit= "<span id=\"pocet_cen\"> </span><input type=\"submit\" value=\"Upravit seriál\" id=\"submit\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto seriálu</strong>\n";
			}
		}
		

		$javascript_funkce ="
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig63.js\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\" >
			function hidediv(id,id_odkaz) {
				//safe function to hide an element with a specified id
				if (document.getElementById) { // DOM3 = IE5, NS6
					document.getElementById(id).style.display = 'none';
					document.getElementById(id_odkaz).href = \"javascript:showdiv('\" +id+ \"','\" +id_odkaz+ \"');\";					
				}
				else {
					if (document.layers) { // Netscape 4
						document.id.display = 'none';
						document.id_odkaz.href = \"javascript:showdiv('\" +id+ \"','\" +id_odkaz+ \"');\";						
					}
					else { // IE 4
						document.all.id.style.display = 'none';
						document.all.id_odkaz.href = \"javascript:showdiv('\" +id+ \"','\" +id_odkaz+ \"');\";						
					}
				}
			}
			
			function showdiv(id,id_odkaz) {
				//safe function to show an element with a specified id
				//	  document.write('neco neco' + id);
				if (document.getElementById) { // DOM3 = IE5, NS6
					document.getElementById(id).style.display = 'block';
					document.getElementById(id_odkaz).href = \"javascript:hidediv('\" +id+ \"','\" +id_odkaz+ \"');\";
				}
				else {
					if (document.layers) { // Netscape 4
						document.id.display = 'block';
						document.id_odkaz.href = \"javascript:hidediv('\" +id+ \"','\" +id_odkaz+ \"');\";
					}
					else { // IE 4
						document.all.id.style.display = 'block';
						document.all.id_odkaz.href = \"javascript:hidediv('\" +id+ \"','\" +id_odkaz+ \"');\";
					}
				}
			}
		
		</script>
		
		";
                
                $seznam_spravcu = new Zamestnanec_list($this->id_zamestnance,"","","","");			
                
		$spravce = "<div class='form_row' id='spravce'>
                                <div class='label_float_left'>Správce zájezdu: <span class=\"red\">*</span></div>
                                <div class='value'>
                                    <select name='spravce' class='wide'>
                                        <option value='0'>---</option>";
                while($seznam_spravcu->get_next_radek()){
                        $spravce .= $seznam_spravcu->show_list_item("serial_spravce");
                }
                
                $spravce .= "       </select>
                                </div>
                            </div>";   		
		$vystup= $javascript_funkce.
					"<form action=\"".$action."\" method=\"post\" onsubmit=\"syncTextarea()\">".
						$typ.$spravce.$template_serial.$javascript_hideshow_functions.
                                                $zeme.$nazev.$ubytovani_vyber.$podtyp_serialu.$popisek.$popis.$popis_ubytovani.$popis_stravovani.$popis_strediska.$popis_lazni.$program_zajezdu.
						$highlights.$predregistrace.$nezobrazovat.$cena_zahrnuje.$cena_nezahrnuje.$poznamky.
						$strava.$doprava.$ubytovani.$templates.$smluvni_podminky.$dlouhodobe_zajezdy.$submit.
						$make_whizywig.
					"</form>";
		return $vystup;
	}
	
	
	function get_id() { return $this->id_serial;}
	function get_nazev() { return $this->nazev;}
	function get_id_zeme() { return $this->id_zeme;}	
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_serial == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}
} 




?>
