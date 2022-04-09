<?php
/** 
* trida pro praci s klientem
* - formuláøe pro registraci, zmìnu osobních údajù + jejich zpracování
*/

/*--------------------- UZIVATEL ----------------------------*/
class Prodejce extends Generic_data_class{
	protected $typ_pozadavku;
	protected $id_klient;
	protected $salt_potvrzeni;		
	protected $potvrzeni_expire;		
	
	protected $uzivatelske_jmeno;
	protected $jmeno;
	protected $prijmeni;
	protected $titul;	
	protected $email;
	protected $telefon;
	protected $datum_narozeni;
	protected $rodne_cislo;
	protected $cislo_pasu;
	protected $cislo_op;
	protected $ulice;
	protected $mesto;
	protected $psc;
	protected $ico;
	protected $uzivatel_je_ca;
		
	protected $stare_heslo;
	protected $heslo;
	protected $heslo2;
	protected $nove_heslo;		
	
	public $database; //trida pro odesilani dotazu	
	
  	
	/**
	*	konstruktor tøídy 
	* - parametry jsou hodnoty sloupcù z tabulky user_klient
	*/
        
        
	function __construct(
		$typ_pozadavku,$id_organizace="",$salt_potvrzeni="",$uzivatelske_jmeno="",$stare_heslo="",$nove_heslo1="",$nove_heslo2="",
		$nazev="",$kontaktni_osoba="",$ico="",
                $email="",$telefon="",$web="",
		$stat="",$ulice="",$mesto="",$psc="",
                $nazev_banky="",$kod_banky="",$cislo_uctu="",$minuly_pozadavek=""
	){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->id_organizace = $this->check_int($id_organizace);
		$this->salt_potvrzeni = $this->check($salt_potvrzeni) ;
		
		$this->uzivatelske_jmeno = strtolower($this->check($uzivatelske_jmeno));
                                
		$this->nazev = $this->check_slashes( $this->check($nazev) );
		$this->kontaktni_osoba = $this->check_slashes( $this->check($kontaktni_osoba) );
		$this->ico = $this->check_slashes( $this->check($ico) );		
		$this->email = $this->check_slashes( $this->check($email) );
		$this->telefon = $this->check_slashes( $this->check($telefon) );
                $this->web = $this->check_slashes( $this->check($web) );
                
                $this->stat = $this->check_slashes( $this->check($stat) );
                $this->ulice = $this->check_slashes( $this->check($ulice) );		
		$this->mesto = $this->check_slashes( $this->check($mesto) );		
		$this->psc = $this->check_slashes( $this->check($psc) );
	
		$this->nazev_banky = $this->check_slashes( $this->check($nazev_banky) );		
		$this->kod_banky = $this->check_slashes( $this->check($kod_banky) );		
		$this->cislo_uctu = $this->check_slashes( $this->check($cislo_uctu) );				
		
		$this->stare_heslo = $this->check($stare_heslo);
		$this->heslo = $this->check($nove_heslo1);
		$this->heslo2 = $this->check($nove_heslo2);
		$this->nove_heslo = "";

			
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku) ){
			//podle typu pozadavku
			if($this->typ_pozadavku=="create"){
					//pokud odpovidaji hesla
					if($this->heslo==$this->heslo2 and !$this->heslo2=""){	
						//vytvorim zakodovane heslo
						$nahodny_retezec = sha1(mt_rand().mt_rand()); 
						$this->salt = substr($nahodny_retezec, 1, mt_rand(10,20));
						$this->nove_heslo = sha1($this->heslo.$this->salt);
						$this->heslo_sha1 = $this->nove_heslo;
									
						
                                                //vytvorime organizaci
                                                $data = $this->database->transaction_query($this->create_query("create_organizace"),1)
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                $this->id_organizace = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                                $this->database->transaction_query($this->create_query("create_prodejce"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                $this->database->transaction_query($this->create_query("create_adresa_organizace"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                $this->database->transaction_query($this->create_query("create_web_organizace"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                $this->database->transaction_query($this->create_query("create_telefon_organizace"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                $this->database->transaction_query($this->create_query("create_email_organizace"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                $this->database->transaction_query($this->create_query("create_bankovni_spojeni_organizace"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                //
                                                if( !$this->get_error_message() ){
                                                    $this->database->commit();
                                                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                                                }else{
                                                    $this->database->rollback();
                                                }
                                               
						if( !$this->get_error_message() ){
							//odeslu klientovi e-mail s potvrzovacim kodem
							$predmet="Potvrzení registrace v systému agentur CK SLAN tour";
							$odesilatel_jmeno=AUTO_MAIL_SENDER;
							$odesilatel_email=AUTO_MAIL_EMAIL;
							$text = "Tento e-mail Vám byl zaslán na základì vyplnìní registraèního formuláøe na adrese ".$_SERVER['SERVER_NAME'].".<br/><br/>
                                                                Úspìšnì jste se zaregistrovali do systému pro provizní prodejce SLAN tour s.r.o.									
									Uživatelské jméno: ".$this->uzivatelske_jmeno."<br/>									
									Prodejce: ".$this->nazev."<br/>
									IÈO: ".$this->ico."<br/>
									Heslo: ".$this->heslo."<br/><br/>
                                                                Pøihlásit ke svému úètu se mùžete na adrese <a href=\"https://slantour.cz/agentury.php\">slantour.cz/agentury.php</a>,
                                                                pøípadné zmìny Vašich kontaktních a pøihlašovacích údajù mùžete provést po pøihlášení.
								";
								
							//odeslani potvrzovaciho e-mailu						
							$mail = Send_mail::send($odesilatel_jmeno, $odesilatel_email, $this->email, $predmet, $text);
							if(!$mail){
								$this->chyba("Nepodaøilo se odeslat kontrolní e-mail. Zaregistrujte se prosím ještì jednou.");
							}	
						}	
							
						//vygenerování potvrzovací hlášky
						if( !$this->get_error_message() ){
							$this->confirm("Registrace probìhla úspìšnì, na Vaši adresu byl odeslán potvrzovací e-mail.");
						}		
					}else{
						$this->chyba("Heslo a kontrolní heslo nejsou stejné nebo jsou prázdné!");
					}		
			
			//vytvoreni uziv. jmena a hesla pro existujiciho klienta															
			}else if($this->typ_pozadavku=="update"){
				$uzivatel = User::get_instance();
				$this->id_organizace = $this->check_int($id_organizace);	
				//pripojeni k databazi
				$this->database->start_transaction();				
				//pokud odpovidaji hesla
				//pokud chceme zmenit heslo
				if($this->stare_heslo!="" and $this->heslo!=""){				
					$data_user=mysqli_fetch_array( $this->database->transaction_query($this->create_query("get_user_password") ) )
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					//pokud jsme spravne napsali stare heslo								
					if( sha1($this->stare_heslo.$data_user["salt"]) == $data_user["heslo_sha1"]){			
						if($this->heslo==$this->heslo2 and $this->heslo2!=""){	
							//vytvorim nove heslo ktere pouziju do databaze
							$this->nove_heslo = sha1($this->heslo.$data_user["salt"]);
                                                        $this->heslo_sha1 = $this->nove_heslo;
                                                        
                                                        $this->data=$this->database->transaction_query($this->create_query("update_prodejce"))
                                                            or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );  
						}else{
							$this->chyba("Nové heslo a kontrolní nové heslo nejsou stejné!");
						}
					}else{
						$this->chyba("Staré heslo není správné!");
					}
                                        
                                                                              
				}                                
                                        $data = $this->database->transaction_query($this->create_query("update_organizace"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );    
                                        
                                                $this->database->transaction_query($this->create_query("update_adresa_organizace"));
                                                if(mysqli_affected_rows()==0){
                                                    $this->database->transaction_query($this->create_query("create_adresa_organizace"));
                                                }
                                                
                                                $this->database->transaction_query($this->create_query("update_web_organizace"));
                                                if(mysqli_affected_rows()==0){
                                                    $this->database->transaction_query($this->create_query("create_web_organizace"));
                                                }
                                                
                                                $this->database->transaction_query($this->create_query("update_telefon_organizace"));
                                                if(mysqli_affected_rows()==0){
                                                    $this->database->transaction_query($this->create_query("create_telefon_organizace"));
                                                }
                                                
                                                $this->database->transaction_query($this->create_query("update_email_organizace"));
                                                if(mysqli_affected_rows()==0){
                                                    $this->database->transaction_query($this->create_query("create_email_organizace"));
                                                }
                                                
                                                $this->database->transaction_query($this->create_query("update_bankovni_spojeni_organizace"));
                                                if(mysqli_affected_rows()==0){
                                                    $this->database->transaction_query($this->create_query("create_bankovni_spojeni_organizace"));
                                                }
                                
				if($this->nove_heslo){		
					$_SESSION["heslo_klient"] = $this->nove_heslo;
				}	
				//vygenerování potvrzovací hlášky
				if( !$this->get_error_message() ){
					$this->database->commit();//potvrzeni transakce
					$this->confirm("Zmìna firemních údajù byla úspìšnì provedena");
				}						
					
			}else if($this->typ_pozadavku=="editace_osobnich_udaju"){
				$uzivatel = User::get_instance();
				$this->id_organizace = $uzivatel->get_id();
						
				$data_uzivatel=mysqli_fetch_array( $this->database->query($this->create_query("editace_osobnich_udaju") ) )
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					
				$this->uzivatelske_jmeno = $data_uzivatel["uzivatelske_jmeno"];				
				
				if($minuly_pozadavek != "update_osobnich_udaju"){
                                        $this->nazev = $data_uzivatel["nazev"];
                                        $this->kontaktni_osoba = $data_uzivatel["poznamka"];
                                        $this->ico = $data_uzivatel["ico"];
                                        
					$this->email = $data_uzivatel["email"];
					$this->telefon = $data_uzivatel["telefon"];
                                        $this->web = $data_uzivatel["www"];
                                        
                                        $this->stat = $data_uzivatel["stat"];
					$this->mesto = $data_uzivatel["mesto"];
					$this->ulice = $data_uzivatel["ulice"];
					$this->psc = $data_uzivatel["psc"];
                                        
					$this->nazev_banky = $data_uzivatel["nazev_banky"];
                                        $this->kod_banky = $data_uzivatel["kod_banky"];
                                        $this->cislo_uctu = $data_uzivatel["cislo_uctu"];                                       
                                        
				}
                                
			}else if($this->typ_pozadavku=="potvrzeni_registrace"){
				$data_uzivatel = mysqli_fetch_array( $this->database->query( $this->create_query("get_potvrzeni_uctu") ) )
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				
				if($data_uzivatel["pocet"] == 1){
					//nastavim ucet jako potvrzeny
					$set_potvrzeni = $this->database->query( $this->create_query("set_ucet_potvrzen") ) 
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					//vygenerování potvrzovací hlášky
					if( !$this->get_error_message() ){
						$this->confirm("Registrace byla úspìšnì dokonèena, nyní se prosím pøihlašte.");
					}
				}else{
					$this->chyba("Kód je buï špatný, nebo je již prošlý. Zkontrolujte prosím správnost kódu.");
				}
				
			}				
	
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}
		
	}
	

	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create_organizace"){
                        $dotaz= "INSERT INTO `organizace` 
							(`nazev`,`ico`,`role`,`last_change`, `id_user_create`,`id_user_edit`)
						VALUES
							 ('".$this->nazev."','".$this->ico."',1,'".Date("Y-m-d")."'
                                                             ,0,0)";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update_organizace"){                        
			$dotaz= "UPDATE `organizace`  set
							`nazev`='".$this->nazev."',`ico`='".$this->ico."',
                                                            `last_change`='".Date("Y-m-d")."'
						where
                                                   `id_organizace` = ". $this->id_organizace."
                                                limit 1       ";
			//echo $dotaz . "<br/>";
			return $dotaz;	
                        
                }else if($typ_pozadavku=="create_adresa_organizace"){
                        
			$dotaz= "INSERT INTO `organizace_adresa` 
							(`id_organizace`,`stat`,`mesto`,`ulice`,`psc`, `typ_kontaktu`,`poznamka`)
						VALUES
							 (".$this->id_organizace.",'".$this->stat."','".$this->mesto."','".$this->ulice."','".$this->psc."'
                                                             ,1,'')";
			//echo $dotaz . "<br/>";
			return $dotaz;
                        
                 }else if($typ_pozadavku=="update_adresa_organizace"){                        
			$dotaz= "UPDATE `organizace_adresa`  set
							`stat`='".$this->stat."',`mesto`='".$this->mesto."',
                                                        `ulice`='".$this->ulice."',`psc`='".$this->psc."'
						where
                                                   `id_organizace` = ". $this->id_organizace." and `typ_kontaktu`=1
                                                limit 1       ";
			//echo $dotaz . "<br/>";
			return $dotaz;	    
                        
                }else if($typ_pozadavku=="create_web_organizace"){
                        
			$dotaz= "INSERT INTO `organizace_www` 
							(`id_organizace`,`www`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (".$this->id_organizace.",'".$this->web."'
                                                             ,0,'".$this->kontaktni_osoba."')";
			//echo $dotaz . "<br/>";
			return $dotaz;  
                        
                }else if($typ_pozadavku=="update_web_organizace"){
                        
			$dotaz= "UPDATE `organizace_www`  set
							`www`='".$this->web."',`poznamka`='".$this->kontaktni_osoba."'
						where
                                                   `id_organizace` = ". $this->id_organizace." and `typ_kontaktu`=0
                                                limit 1 ";
			//echo $dotaz . "<br/>";
			return $dotaz;         
                        
                }else if($typ_pozadavku=="create_telefon_organizace"){
                        
			$dotaz= "INSERT INTO `organizace_telefon` 
							(`id_organizace`,`telefon`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (".$this->id_organizace.",'".$this->telefon."'
                                                             ,0,'".$this->kontaktni_osoba."')";
			//echo $dotaz . "<br/>";
			return $dotaz;  
                }else if($typ_pozadavku=="update_telefon_organizace"){
                        
			$dotaz= "UPDATE `organizace_telefon`  set
							`telefon`='".$this->telefon."',`poznamka`='".$this->kontaktni_osoba."'
						where
                                                   `id_organizace` = ". $this->id_organizace." and `typ_kontaktu`=0
                                                limit 1 ";
			//echo $dotaz . "<br/>";
			return $dotaz;         
                        
                }else if($typ_pozadavku=="create_email_organizace"){
                        
			$dotaz= "INSERT INTO `organizace_email` 
							(`id_organizace`,`email`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (".$this->id_organizace.",'".$this->email."'
                                                             ,0,'".$this->kontaktni_osoba."')";
			//echo $dotaz . "<br/>";
			return $dotaz; 
                        
                }else if($typ_pozadavku=="update_email_organizace"){
                        
			$dotaz= "UPDATE `organizace_email`  set
							`email`='".$this->email."',`poznamka`='".$this->kontaktni_osoba."'
						where
                                                   `id_organizace` = ". $this->id_organizace." and `typ_kontaktu`=0
                                                limit 1 ";
			//$this->chyba( $dotaz );
			return $dotaz;     
                        
                }else if($typ_pozadavku=="create_bankovni_spojeni_organizace"){
                        
			$dotaz= "INSERT INTO `organizace_bankovni_spojeni` 
							(`id_organizace`,`nazev_banky`,`kod_banky`,`cislo_uctu`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (".$this->id_organizace.",'".$this->nazev_banky."','".$this->kod_banky."','".$this->cislo_uctu."'
                                                             ,1,'')";
			//echo $dotaz . "<br/>";
			return $dotaz; 
                        
                 }else if($typ_pozadavku=="update_bankovni_spojeni_organizace"){                        
                        
                        $dotaz= "
                                UPDATE `organizace_bankovni_spojeni`  set
							`nazev_banky`='".$this->nazev_banky."',`kod_banky`='".$this->kod_banky."',`cislo_uctu`='".$this->cislo_uctu."'
						where
                                                   `id_organizace` = ". $this->id_organizace." and `typ_kontaktu`=1
                                                limit 1 ";
			//echo $dotaz . "<br/>";
                        
                        //
			//echo $dotaz . "<br/>";
			return $dotaz;        
                        
		}else if($typ_pozadavku=="create_prodejce"){                        
			$dotaz= "INSERT INTO `prodejce` 
							(`id_organizace`,`provizni_koeficient`,`uzivatelske_jmeno`,`heslo`,`heslo_sha1`,`salt`,`last_logon`,`ucet_potvrzen`)
						VALUES
							 (".$this->id_organizace.",1,'".$this->uzivatelske_jmeno."'
                                                             ,'".$this->heslo."','".$this->heslo_sha1."','".$this->salt."'
                                                             ,'".Date("Y-m-d")."',1)";
			//echo $dotaz . "<br/>";
			return $dotaz;  
                        
                }else if($typ_pozadavku=="update_prodejce"){     
                        
                        $dotaz= "UPDATE `prodejce`  set
							`heslo`='".$this->heslo."',`heslo_sha1`='".$this->heslo_sha1."'
						where
                                                   `id_organizace` = ". $this->id_organizace." 
                                                limit 1 ";
			//echo $dotaz . "<br/>";
			return $dotaz;         
			
		
			
		}else if($typ_pozadavku=="editace_osobnich_udaju"){
			$dotaz= "SELECT `prodejce`.*,`organizace`.*,`organizace_email`.*, `organizace_telefon`.`telefon`, `organizace_www`.`www`, 
                                        `stat`,`mesto`,`ulice`,`psc`,
                                        `nazev_banky`,`kod_banky`,`cislo_uctu`
                                    from `prodejce` 
                                     join `organizace` on (`organizace`.`id_organizace` = `prodejce`.`id_organizace`)
                                     left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1) 
                                     left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0) 
                                     left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0) 
                                     left join `organizace_www` on (`organizace`.`id_organizace` = `organizace_www`.`id_organizace` and `organizace_www`.`typ_kontaktu` = 0) 
                                     left join `organizace_bankovni_spojeni` on (`organizace`.`id_organizace` = `organizace_bankovni_spojeni`.`id_organizace` and `organizace_bankovni_spojeni`.`typ_kontaktu` = 1) 
				WHERE `organizace`.`id_organizace`=".$this->id_organizace."
				LIMIT 1 ";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="get_user_password"){
			$dotaz= "SELECT `id_organizace`,`heslo_sha1`,`salt` FROM `prodejce` 
						WHERE `id_organizace`=".$this->id_organizace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;				
		}
	}	


/**kontrola zda smi uzivatel provest danou akci*/
	function legal($typ_pozadavku){
		$uzivatel = User::get_instance();
		
		if($typ_pozadavku == "create" or $typ_pozadavku == "create_ucet"  or $typ_pozadavku == "potvrzeni_registrace" ){
			return true;
		}else if( $uzivatel->get_correct_login() ){
			return true;
		}
		return false;
	}

	/**kontrola zda mam odpovidajici data*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovaná data: název seriálu, popisek,  id_typ, 
		if($typ_pozadavku == "create" or $typ_pozadavku == "update" ){

			/*if(!Validace::datum_en($this->datum_narozeni) ){
				$ok = 0;
				$this->chyba("Datum narození musí být ve formátu dd.mm.rrrr".$this->datum_narozeni);
			}	*/			
			if(!Validace::email($this->email) ){
				$ok = 0;
				$this->chyba("Špatnì vyplnìný e-mail");
			}	
			if(!Validace::text($this->ulice) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit ulici a èíslo popisné");
			}
			if(!Validace::text($this->mesto) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit mìsto");
			}		
			if(!Validace::text($this->psc) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit PSÈ");
			}		
			if(!Validace::text($this->ico) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit IÈO");
			}									
		}		
		if( $typ_pozadavku == "create" )	{//je treba jeste vyplnit uzivatelske jmeno a hesla
			if(!Validace::text($this->nazev) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název CA");
			}
			if(!Validace::text($this->kontaktni_osoba) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit kontaktní osobu");
			}		
			if(!Validace::text($this->uzivatelske_jmeno) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit uživatelské jméno");
			}		
			if(!Validace::text($this->heslo) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit heslo");
			}		
			if(!Validace::text($this->heslo2) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit kontrolní heslo");
			}								
		}															
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
	
	/**zobrazeni formulare pro registraci klienta*/
	function show_registration_form(){
	  $core = Core::get_instance();
	  $adresa_registrace = $core->get_adress_modul_from_typ("registrace");
	  if( $adresa_registrace !== false ){		
			
		if( $this->typ_pozadavku == "new" ){
			//cil formulare
			$action="".$this->get_adress(array($adresa_registrace,"nova_registrace"),0)."";
			$submit= "<input type=\"submit\" value=\"Zaregistrovat se\" />\n";	
			$username="
					<tr>
						<td>Uživatelské jméno: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"uzivatelske_jmeno\" value=\"".$this->uzivatelske_jmeno."\" size=\"40\" maxlength=\"40\" /></td>
					</tr>";
			$heslo=
					"<tr>
						<td>Heslo: <span class=\"red\">*</span></td>
						<td><input type=\"password\" name=\"heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>					
					<tr>
						<td>Heslo - kontrola: <span class=\"red\">*</span></td>
						<td><input type=\"password\" name=\"heslo_kontrola\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";							
							
					
				

		}else if( $this->typ_pozadavku == "editace_osobnich_udaju" ){	
			//cil formulare
			$action="".$this->get_adress(array($adresa_registrace,"update_osobnich_udaju"),0)."";
			$submit= "<input type=\"submit\" value=\"Uložit\" />\n";
			$username="
					<tr>
						<td>Uživatelské jméno:</td>
						<td>".$this->uzivatelske_jmeno." <input type=\"hidden\" name=\"id_organizace\" value=\"".$this->id_organizace."\" /></td>
					</tr>	";				
			$stare_heslo="					
					<tr>
						<td>Staré heslo:</td>
						<td><input type=\"password\" name=\"stare_heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";
			$heslo=
					"<tr>
						<td>Heslo:</td>
						<td><input type=\"password\" name=\"heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>					
					<tr>
						<td>Heslo - kontrola:</td>
						<td><input type=\"password\" name=\"heslo_kontrola\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";																				
		}					
								
		$vystup="
			<div id=\"uzivatel\">
			<form action=\"".$action."\" method=\"post\">
				<table class=\"uzivatel\">
					".$username."
					".$stare_heslo."
					".$heslo."
					<tr>
						<td>Název prodejce: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"nazev\" value=\"".$this->nazev."\" size=\"40\" /></td>
					</tr>	
					<tr>
						<td>Kontaktní osoba: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"kontaktni_osoba\" value=\"".$this->kontaktni_osoba."\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>Ièo: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"ico\" value=\"".$this->ico."\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>E-mail: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"email\" value=\"".$this->email."\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>Telefon:</td>
						<td><input type=\"text\" name=\"telefon\" value=\"".$this->telefon."\" size=\"40\" /></td>
					</tr>
                                        <tr>
						<td>Web:</td>
						<td><input type=\"text\" name=\"web\" value=\"".$this->web."\" size=\"40\" /></td>
					</tr>
					<tr><td><strong>Kontaktní adresa</strong></td></tr>
                                        <tr>
						<td>Stát: <span class=\"red\"></span></td>
						<td><input type=\"text\" name=\"stat\" value=\"".$this->stat."\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>Mìsto: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"mesto\" value=\"".$this->mesto."\" size=\"40\" /></td>
					</tr>		
					<tr>
						<td>Ulice a ÈP: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"ulice\" value=\"".$this->ulice."\" size=\"40\" /></td>
					</tr>		
					<tr>
						<td>PSÈ: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"psc\" value=\"".$this->psc."\" size=\"40\" /></td>
					</tr>	
                                        <tr><td><strong>Bankovní spojení</strong></td></tr>
                                        <tr>
						<td>Název banky: <span class=\"red\"></span></td>
						<td><input type=\"text\" name=\"nazev_banky\" value=\"".$this->nazev_banky."\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>Kód banky: <span class=\"red\"></span></td>
						<td><input type=\"text\" name=\"kod_banky\" value=\"".$this->kod_banky."\" size=\"40\" /></td>
					</tr>		
					<tr>
						<td>Èíslo úètu: <span class=\"red\"></span></td>
						<td><input type=\"text\" name=\"cislo_uctu\" value=\"".$this->cislo_uctu."\" size=\"40\" /></td>
					</tr>	
				</table>
				".$submit."
		</form>
			<p><span class=\"red\">*</span> - pole oznaèená hvìzdièkou je tøeba vyplnit.</p>";
		
		if($this->typ_pozadavku == "new"){	
			$vystup=$vystup."	<h3>Co se stane po odeslání?</h3>
				<p>Po odeslání zkontroluje systém vaše údaje a pokud bude vše vpoøádku, zaregistruje Vás jako prodejce SLAN tour.<br/>
                                Registrace nenahrazuje písemnou smlouvu o obchodním zastoupení, slouží pouze ke zjednodušení procesu objednávky zájezdu.</p>
				".Send_mail::$hlaska_osobni_udaje."	";
		}	
		
		$vystup=$vystup."</div>";
		return $vystup;
	  }//adresa_registrace !==false
	}
			
	/*metody pro pristup k parametrum*/
	function get_id(){ return $this->uzivatel["id_organizace"];	}
	function get_uzivatelske_jmeno(){ return $this->uzivatel["uzivatelske_jmeno"];}	
	function get_nazev(){ return $this->uzivatel["nazev"];}
	function get_ico(){ return $this->uzivatel["ico"];}

			
	function get_correct_login(){ return $this->correct_login;}
}




?>
