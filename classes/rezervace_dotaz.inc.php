<?php
/** 
* trida pro obslouzeni prichoziho formulare s dotazem k zajezdu
* 	- dotazy se neukladaji do databaze
* 	- odesle se e-mail tvurci serialu, na centralni e-mail systemu a potvrzeni klientovi
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_dotaz extends Generic_data_class{
	//vstupni data
	private $typ_pozadavku;
	
	private $id_serial;
	private $id_zajezd;
	private $id_klient;
	
	private $dotaz;
	private $jmeno;	
	private $prijmeni;	
	private $email;	
	private $telefon;				
	private $novinky;
	private $zajezd;
	private $dalsi_form;
	
	protected $data;
	protected $serial;
	
	public $database; //trida pro odesilani dotazu	
	
		
//------------------- KONSTRUKTOR -----------------
	/**konstruktor třídy na základě prvků formuláře dotazu*/
	function __construct($typ_pozadavku, $id_serial, $id_zajezd,  $id_klient, $dotaz, $jmeno="", $prijmeni="", $email="", $telefon="", $novinky=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
		//$uzivatel = User::get_instance();
		//$this->id_klient = $uzivatel->get_id();
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		
		$this->id_serial = $this->check_int($id_serial);
		$this->id_zajezd = $this->check_int($id_zajezd);
		$this->novinky = $this->check($novinky);
		$this->dotaz = $this->check($dotaz);

		
		//pokud je klient prihlaseny, stahneme si data z nej
		/*if($this->id_klient){
			$this->jmeno = $uzivatel->get_jmeno();
			$this->prijmeni = $uzivatel->get_prijmeni();
			$this->email = $uzivatel->get_email();
			$this->telefon = $uzivatel->get_telefon();			
		}else{*/
			$this->jmeno = $this->check($jmeno);
			$this->prijmeni = $this->check($prijmeni);
			$this->email = $this->check($email);
			$this->telefon = $this->check($telefon);	
		/*}*/
		
	//ziskani serialu z databaze	
		if($this->id_zajezd!=0){
			$this->zajezd = mysqli_fetch_array( $this->database->query($this->create_query("get_zajezd") ) )
			 	or $this->chyba("Chyba při dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		}else{
			$this->zajezd = mysqli_fetch_array( $this->database->query($this->create_query("get_serial") ) )
			 	or $this->chyba("Chyba při dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
		}
                if($this->zajezd["nazev_ubytovani"]!=""){
                    $this->zajezd["nazev"] = $this->zajezd["nazev_ubytovani"]." - ".$this->zajezd["nazev"];
                }
			
		if($this->typ_pozadavku =="odeslat"){                    
			//predregistrace
                   
			$preg = $this->check_int($_POST["predregistrace"]);
			if($preg!=0 and $preg!=""){
				$i=0;
				$dot="";
				while ($i<=$preg and $i<=100){
					if($_POST["predregistrace_".$i]!=""){
						$dot .= $this->check($_POST["predregistrace_".$i]).", "; 
					}
					$i++;
				}
			}
			if($dot!=""){
				$this->dotaz = "Mám předběžný zájem o: ".$dot." a chci zaslat konkrétní nabídky, až budou k dispozici.<br/>".$this->dotaz;
			}

			//kontrola odeslanych dat
			if( $this->correct_data() and !$this->get_error_message() ){
				//odeslani e-mailu	
				if( !$this->get_error_message() ){
					if($preg!=0 and $dot!=""){//ulozim do databaze
						mysqli_query($GLOBALS["core"]->database->db_spojeni,"
							insert into  `predregistrace` (`id_serial`,`jmeno`,`prijmeni`,`telefon`,`email`,`objednavka`) 
							values(".$this->id_serial.",\"".$this->jmeno."\",\"".$this->prijmeni."\",\"".$this->telefon."\",\"".$this->email."\",\"".$dot."\")
						");
					}
					//odeslu klientovi e-mail s potvrzovacim kodem
					$predmet_ck = "Dotaz k zájezdu: ".$this->zajezd["nazev"]." ";
					$predmet_klient = "Potvrzení odeslání dotazu k zájezdu";								
					$klient_jmeno = $this->prijmeni." ".$this->jmeno;
					$klient_email = $this->email;
					$rsck_email = PRIJIMACI_EMAIL;
					$zamestnanec_email = $zajezd["email"];
					if($this->id_klient){$id = "id: ".$this->id_klient."; ";}
					
                                        if($this->novinky == 1 or $this->novinky == "ano" ){
                                            $this->novinky = "Ano";
                                        }else{
                                            $this->novinky = "Ne";
                                        }
                                        
					$ck_text = "
						<strong>Dotaz k zájezdu CK SLAN tour:</strong><br/>
							Zájezd: <strong>".$this->zajezd["nazev"]."</strong>, ".$this->zajezd["nazev_zajezdu"]." (".$this->change_date_en_cz($this->zajezd["od"])." - ".$this->change_date_en_cz($this->zajezd["do"]).")<br/> 
							Odesilatel: ".$id." <strong>".$this->prijmeni." ".$this->jmeno."</strong>; ".$this->email."; ".$this->telefon."<br/><br/>							
							Dotaz: ".nl2br($this->dotaz)."<br/><br/>
							Souhlas se zasíláním aktuálních nabídek CK SLAN tour: ".$this->novinky."<br/><br/>
							Dotaz z webu: ".$_SERVER["SERVER_NAME"]."<br/>	<br/>
              
Beru na vědomí, že proti zasílání obchodních sdělení mohu vznést kdykoliv námitku, a to buď na adrese cestovní kanceláře nebo e-mailem zaslaným na e-mailovou adresu info@slantour.cz. V tomto případě mi nebude cestovní kancelář dále zasílat obchodní sdělení, ani jinak zpracovávat mé osobní údaje pro účely přímého marketingu.<br/>              
							";
					$klient_text = "Váš dotaz byl úspěšně odeslán, pracovníci CK na něj odpoví co nejdříve.<br/>
						Původní zpráva:
						<i>".$ck_text."</i>
						";		
						//odeslani emailu s dotazem					
					$mail = Send_mail::send($klient_jmeno, $klient_email, $rsck_email, $predmet_ck, $ck_text);
					if($mail){
						//odeslani potvrzovaciho e-mailu
						Send_mail::send(AUTO_MAIL_SENDER, AUTO_MAIL_EMAIL, $klient_email, $predmet_klient, $klient_text);
						//odesilani e-mailu zamestnanci - tvurci serialu
						Send_mail::send($klient_jmeno, $klient_email,  "lpeska@seznam.cz", $predmet_ck, $ck_text);					
						$this->confirm("Dotaz k zájezdu byl úspěšně odeslán.");
					
						//echo $mail;
					}else{
						$this->chyba("Nepodařilo se odeslat e-mail s dotazem. Odešlete jej prosím ještě jednou.");
					}		
				}
			}//typ_pozadavku=="odeslat"
		}
	}	
	
	function set_dalsi_form($df){
		$this->dalsi_form = $df;
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku == "get_zajezd"){
                        $dotaz= "select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`dlouhodobe_zajezdy`,`serial`.`id_smluvni_podminky`,`serial`.`id_sablony_zobrazeni`,`serial`.`id_sablony_objednavka`,`zajezd`.`nazev_zajezdu`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,
                                                `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                                 `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`

					from `serial` join
						`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                                            left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  

					where `serial`.`id_serial`= ".$this->id_serial."
						and `zajezd`.`id_zajezd`=".$this->id_zajezd."
					limit 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku == "get_serial"){
			$dotaz= "select `serial`.*,
                                        `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                                 `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`
					from `serial` 
                                        left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  

					where `serial`.`id_serial`= ".$this->id_serial." 
					limit 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku == "get_all_zajezdy"){
			$dotaz= "select `zajezd`.`nazev_zajezdu`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`

					from `serial` join
						`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
					where `serial`.`id_serial`= ".$this->id_serial." and `zajezd`.`nezobrazovat_zajezd`<>1
                                            and (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) )
					";
			//echo $dotaz;
			return $dotaz;
		}
	}	

	/**kontrola zda mam odpovidajici data*/
	function correct_data(){
		$ok = 1;
		if($this->typ_pozadavku == "odeslat" ){
		//kontrolovaná data: název seriálu, popisek,  id_typ, 
			if(!Validace::text($this->jmeno) ){
				$ok = 0;
				$this->chyba("Je třeba vyplnit Vaše jméno!");
			}
                       /* if(!Validace::telefon($this->telefon) ){
				$ok = 0;
				$this->chyba("Telefon může obsahovat pouze číslice, mezeru, případně \"+\"!");
			}*/
			if(!Validace::text($this->prijmeni) ){
				$ok = 0;
				$this->chyba("Je třeba vyplnit Vaše příjmení!");
			}
			if(!Validace::email($this->email) ){
				$ok = 0;
				$this->chyba("Email není správně vyplněn!");
			}						
			if(!Validace::text($this->dotaz) ){
				$ok = 0;
				$this->chyba("Dotaz není vyplněn!");
			}	
		}																	
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
/** zobrazeni formulare pro dotaz k zajezdu */
	function show_form_dotaz($typ=""){
		//$uzivatel = User::get_instance();
		//objednavka je dostupna pouze pro prihlaseneho uzivatele
		if($typ=="predregistrace"){
	 if(!$this->get_error_message()){
			/*if( $uzivatel->get_correct_login() ){
				$klient = "<tr><td valign=\"top\">Odesilatel:</td><td><strong>".$this->prijmeni." ".$this->jmeno."</strong>; ".$this->email."; ".$this->telefon."</td></tr>\n";
			}else{*/
				$klient = " <tr>
									<td valign=\"top\">Jméno: <span class=\"red\">*</span></td>
									<td><input  name=\"jmeno\" type=\"text\" value=\"".$this->jmeno."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">Příjmení: <span class=\"red\">*</span></td>
									<td><input  name=\"prijmeni\" type=\"text\" value=\"".$this->prijmeni."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">E-mail: <span class=\"red\">*</span></td>
									<td><input  name=\"email\" type=\"text\" value=\"".$this->email."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">Telefon:</td>
									<td><input  name=\"telefon\" type=\"text\" value=\"".$this->telefon."\" /></td>
									</td>
								</tr>								";
			/*}	*/
			$serial = "<tr><th valign=\"top\">Zájezd:</th><th><strong>".$this->zajezd["nazev"]."</strong> (".$this->change_date_en_cz( $this->zajezd["od"] )." - ".$this->change_date_en_cz( $this->zajezd["do"] ).")</th></tr>\n";		
			
                            $hidden = "
				<input name=\"id_serial\" type=\"hidden\" value=\"".$this->id_serial."\" />
				<input name=\"id_zajezd\" type=\"hidden\" value=\"".$this->id_zajezd."\" />
				";
                            $list_zajezdy = "";
                        
                        
                        	
			$dotaz = "<tr><td valign=\"top\">Mám zájem o: <span class=\"red\">*</span></td><td>".$this->dalsi_form."</td></tr>\n
						<tr><td valign=\"top\">Poznámky: <span class=\"red\">*</span></td><td><textarea name=\"dotaz\" type=\"text\" cols=\"50\" rows=\"7\">".$this->dotaz."</textarea></td></tr>\n";				
											
				$vystup="<h3>Předběžná registrace zájemců</h3>
					<table>
					<tr><td>
					<form action=\"".str_replace("/zobrazit", "/dotaz", $_SERVER["REQUEST_URI"])."?odeslat=1\" method=\"post\">
						".$hidden."
						<table class=\"rezervace\"  cellpadding=\"0\" cellspacing=\"0\" style=\"float:left;\">
							".$serial.$klient.$dotaz."
						</table>	
                                            </td></tr>    
                                            <tr><td>       
						<div class=\"resetovac\">&nbsp;</div>
						<input type=\"submit\" value=\"Odeslat dotaz\" />
					
						<p><span class=\"red\">*</span> - položky označené hvězdičkou je třeba vyplnit</p>				
						<h2>Co se stane po odeslání?</h2>
						<p>Po odeslání formuláře zaregistrujeme Váš předběžný zájem o daný typ akce a budeme Vás kontaktovat, jakmile vytvoříme konkrétní nabídku.<br/>
						 Odesláním předběžné registrace pro Vás nevzniká žádná povinnost zájezd později objednat.</p>
						 <br/>Mám zájem o zasílání aktuálních nabídek CK: <input type=\"checkbox\" name=\"novinky\"  value=\"ano\"/><br/><br/>
             
             Zaškrtnutím výše uvedeného checkboxu projevujete souhlas se zpracováním osobních údajů v rozsahu jméno, příjmení, telefonní číslo a e-mailová adresa za účelem zasílání obchodních sdělení. Cestovní kancelář může zasílat obchodní sdělení formou SMS, MMS, elektronické pošty, poštou či sdělovat telefonicky a to maximálně 1x týdně.<br/><br/>
              Proti zasílání obchodních sdělení je možno vznést kdykoliv námitku, a to buď na adrese cestovní kanceláře nebo e-mailem zaslaným na adresu info@slantour.cz. V tomto případě nebude cestovní kancelář dále zasílat obchodní sdělení, ani jinak zpracovávat vaše osobní údaje pro účely přímého marketingu.
					</td></tr>
					</form>
					</table>
					";
			
			return $vystup;	
		 }		
		
		}else{
		 if(!$this->get_error_message()){
			/*if( $uzivatel->get_correct_login() ){
				$klient = "<tr><td valign=\"top\">Odesilatel:</td><td><strong>".$this->prijmeni." ".$this->jmeno."</strong>; ".$this->email."; ".$this->telefon."</td></tr>\n";
			}else{*/
				$klient = " <tr>
									<td valign=\"top\">Jméno: <span class=\"red\">*</span></td>
									<td><input  name=\"jmeno\" type=\"text\" value=\"".$this->jmeno."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">Příjmení: <span class=\"red\">*</span></td>
									<td><input  name=\"prijmeni\" type=\"text\" value=\"".$this->prijmeni."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">E-mail: <span class=\"red\">*</span></td>
									<td><input  name=\"email\" type=\"text\" value=\"".$this->email."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">Telefon:</td>
									<td><input  name=\"telefon\" type=\"text\" value=\"".$this->telefon."\" /></td>
									</td>
								</tr>								";
			/*}	*/
                        if($this->id_zajezd == 0){
                            $serial = "<tr><th valign=\"top\">Zájezd:</th><th><strong>".$this->zajezd["nazev"]."</strong> </th></tr>\n";					                            
                        }else{
                            $serial = "<tr><th valign=\"top\">Zájezd:</th><th><strong>".$this->zajezd["nazev"]."</strong> (".$this->change_date_en_cz( $this->zajezd["od"] )." - ".$this->change_date_en_cz( $this->zajezd["do"] ).")</th></tr>\n";		
			
                        }
			if($this->id_zajezd >0){
                            $hidden = "
				<input name=\"id_serial\" type=\"hidden\" value=\"".$this->id_serial."\" />
				<input name=\"id_zajezd\" type=\"hidden\" value=\"".$this->id_zajezd."\" />
				";
                            $list_zajezdy = "";
                        }else{
                            $hidden = "
				<input name=\"id_serial\" type=\"hidden\" value=\"".$this->id_serial."\" />
				";
                            $option_zajezdy = "";
                            $data_zajezdy = $this->database->query($this->create_query("get_all_zajezdy") );
                            while ($row = mysqli_fetch_array($data_zajezdy)) {
                                if($row["nazev_zajezdu"]!=""){
                                    $nazev = $row["nazev_zajezdu"].", ".$this->change_date_en_cz($row["od"]);
                                }else{
                                    $nazev = $this->change_date_en_cz($row["od"])." - ".$this->change_date_en_cz($row["do"]);
                                }
                                $option_zajezdy .= "<option value=\"".$row["id_zajezd"]."\">".$nazev."</option>";
                            }
                            $list_zajezdy = "
                                <tr>
				<td valign=\"top\">Zájezd/termín o který máte zájem</td>
				<td>
                                <select name=\"id_zajezd\" />
                                    <option value=\"0\">---</option>
                                    ".$option_zajezdy."
                                </select>
                                </td>
                                </tr>
                                ";
                        }	
			$dotaz = "<tr><td valign=\"top\">Dotaz: <span class=\"red\">*</span></td><td><textarea name=\"dotaz\" type=\"text\" cols=\"50\" rows=\"7\">".$this->dotaz."</textarea></td></tr>\n";				
			
										
				$vystup="
					<table>
					<tr><td>
					<form action=\"".$_SERVER["REQUEST_URI"]."?odeslat=1\" method=\"post\">
						".$hidden."
						<table class=\"rezervace\"  cellpadding=\"0\" cellspacing=\"0\" style=\"float:left;\">
							".$serial.$klient.$list_zajezdy.$dotaz."
						</table>		
						<div class=\"resetovac\">&nbsp;</div>
						<input type=\"submit\" value=\"Odeslat dotaz\" />
					
						<p><span class=\"red\">*</span> - položky označené hvězdičkou je třeba vyplnit</p>				
						<h3>Co se stane po odeslání?</h3>
						<p>Po odeslání formuláře Vám na zadaný dotaz co nejdříve odpoví pracovníci CK SLAN tour.<br/>
						 Odesláním dotazu pro Vás nevzniká žádná povinnost zájezd později objednat.</p>
						 <br/>Mám zájem o zasílání aktuálních nabídek CK: <input type=\"checkbox\" name=\"novinky\" value=\"ano\"/></p>
             
             Zaškrtnutím výše uvedeného checkboxu projevujete souhlas se zpracováním osobních údajů v rozsahu jméno, příjmení, telefonní číslo a e-mailová adresa za účelem zasílání obchodních sdělení. Cestovní kancelář může zasílat obchodní sdělení formou SMS, MMS, elektronické pošty, poštou či sdělovat telefonicky a to maximálně 1x týdně.<br/><br/>
              Proti zasílání obchodních sdělení je možno vznést kdykoliv námitku, a to buď na adrese cestovní kanceláře nebo e-mailem zaslaným na adresu info@slantour.cz. V tomto případě nebude cestovní kancelář dále zasílat obchodní sdělení, ani jinak zpracovávat vaše osobní údaje pro účely přímého marketingu.
             
					</td></tr>
					</form>
					</table>
					";
			
			return $vystup;	
		 }	
	   }			
	}		
	
	
	
} 

?>
