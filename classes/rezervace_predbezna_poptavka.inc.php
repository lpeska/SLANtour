<?php
/** 
* trida pro obslouzeni prichoziho formulare s predbeznou poptavkou
*	- poptavky se neukladaji do databaze
*	- odesle se e-mail tvurci serialu, na centralni e-mail systemu a potvrzeni klientovi
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_predbezna_poptavka extends Generic_data_class{
	private $array_ceny;
	private $text_ceny;
	
	//vstupni data
	private $typ_pozadavku;
		
	private $id_serial;
	private $id_zajezd;
	private $id_klient;
	
	private $pocet_osob;
	private $poznamky;	
	private $jmeno;	
	private $prijmeni;	
	private $email;	
	private $telefon;				
	private $novinky;
	private $upresneni_terminu;
	
	private $vyplnena_cena;			
	protected $data;
	protected $serial;
	
	public $database; //trida pro odesilani dotazu	
	
		
//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì prvkù formuláøe pøedbìžná poptávka*/
	function __construct($typ_pozadavku, $id_serial, $id_zajezd,  $id_klient, $pocet_osob="", $poznamky="", $jmeno="", $prijmeni="", $email="", $telefon="", $novinky="", $upresneni_terminu=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
		$uzivatel = User::get_instance();		
		$this->id_klient = $uzivatel->get_id();
		$this->typ_pozadavku = $this->check($typ_pozadavku);		
		
		$this->id_serial = $this->check_int($id_serial);
		$this->id_zajezd = $this->check_int($id_zajezd);
		$this->pocet_osob = $this->check_int($pocet_osob);		
		$this->poznamky = $this->check($poznamky);
		$this->novinky = $this->check($novinky);
		$this->upresneni_terminu = $this->check($upresneni_terminu);
		//pokud je klient prihlaseny, stahneme si data z nej
		if($this->id_klient){
			$this->jmeno = $uzivatel->get_jmeno();
			$this->prijmeni = $uzivatel->get_prijmeni();
			$this->email = $uzivatel->get_email();
			$this->telefon = $uzivatel->get_telefon();			
		}else{
			$this->jmeno = $this->check($jmeno);
			$this->prijmeni = $this->check($prijmeni);
			$this->email = $this->check($email);
			$this->telefon = $this->check($telefon);				
		}
		if($typ_pozadavku == "odeslat"){
			//inicializace promennych pro ceny	
			$this->text_ceny="";	
			$this->array_ceny = array();
			$this->vyplnena_cena = 0; //znaci zda uzivatel vyplnil alespon jednu cenu
		
			//vytvorime si pole tvaru $array_ceny(id_ceny -> nazev_ceny.castka.mena)
			$data_ceny = $this->database->query($this->create_query("get_ceny") ) 
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			while($ceny = mysqli_fetch_array($data_ceny) ){
				$this->array_ceny[ intval($ceny["id_cena"]) ] = $ceny["nazev_ceny"]."</td><td>".$ceny["castka"]." ".$ceny["mena"]."";
			}
		}
	}	
	
/**prijima informace o jednotlivych sluzbach a sestavuje z nich èásti dotazu do databáze*/
	function add_to_query($id_cena,$pocet){
		//kontrola vstupnich dat
		$id_cena = $this->check_int($id_cena);
		$pocet = $this->check_int($pocet);
		
		//pokud jsou vporadku data, vytvorim danou cast dotazu
		if($this->legal_data($id_cena,$pocet)){		
			//text ceny		
			$this->text_ceny .= "<tr><td>".$this->array_ceny[$id_cena]."</td><td>".$pocet."</td></tr>";				
		}//if legal_data
	}
	
	/** funkce pro finální zpracování formuláøe pøedbìžná poptávka
	* - vytvoøí e-maily s objednávkou a potvrzením objednávky a odešle je do CK a klientovi
	*/
	function finish(){
		if(!$this->get_error_message()){
			//ziskani serialu z databaze	
			$zajezd = mysqli_fetch_array( $this->database->query($this->create_query("get_zajezd") ) )
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				
			if($zajezd["nazev_ubytovani"]!=""){
            $zajezd["nazev"] = $zajezd["nazev_ubytovani"]." - ".$zajezd["nazev"];
         }
			//uzivatel musi udat pocet alespon u jedne ceny (testovano v legal_data() )
			if(!$this->vyplnena_cena){
					$this->chyba("Je tøeba vyplnt alespoò jednu službu!");
			}
			//odeslani e-mailu				
			if( !$this->get_error_message() and  $this->correct_data($zajezd["dlouhodobe_zajezdy"]) ){
			
				//odeslu klientovi e-mail s potvrzovacim kodem
				$predmet_ck = "Pøedbìžná poptávka zájezdu: ".$zajezd["nazev"]." ";
				$predmet_klient = "Potvrzení odeslání poptávky zájezdu";								
				$klient_jmeno = $this->prijmeni." ".$this->jmeno;
				$klient_email = $this->email;
				$rsck_email = PRIJIMACI_EMAIL;
				$zamestnanec_email = $zajezd["email"];
				if($this->id_klient){$id = "id: ".$this->id_klient."; ";}
				
				$ck_text = "
						<strong>Pøedbìžná poptávka zájezdu CK SLAN tour:</strong><br/>
							Zájezd: <strong>".$zajezd["nazev"]."</strong> (".$this->change_date_en_cz($zajezd["od"])." - ".$this->change_date_en_cz($zajezd["do"]).")<br/> 
							Upøesnìní termínu: ".$this->upresneni_terminu."<br/>
							Odesilatel: ".$id."<strong>".$this->prijmeni." ".$this->jmeno."</strong>; ".$this->email."; ".$this->telefon."<br/><br/>							
							Poèet osob: ".$this->pocet_osob."<br/>
							Poznámky: ".nl2br($this->poznamky)."<br/><br/>														
							<table>
								<tr><th>Služba</th> <th>Cena</th> <th>Poèet</th> </tr>
								".$this->text_ceny."
							</table>
							Zasílání aktuálních zpráv CK: ".$this->novinky."<br/><br/>
							Poptávka z webu: ".$_SERVER["SERVER_NAME"]."<br/>	<br/>
							";
				$klient_text = "Váš dotaz byl úspìšnì odeslán, pracovníci CK SLAN tour na nìj odpoví co nejdøíve.<br/>
						Pùvodní zpráva:<br/>
						<i>".$ck_text."</i>
					";
								
				//odeslani emailu s dotazem					
				$mail = Send_mail::send($klient_jmeno, $klient_email, $rsck_email, $predmet_ck, $ck_text);
				if($mail){
					//odeslani potvrzovaciho e-mailu klientovi
					Send_mail::send(AUTO_MAIL_SENDER, AUTO_MAIL_EMAIL, $klient_email, $predmet_klient, $klient_text);					
					//odesilani e-mailu zamestnanci - tvurci serialu
					Send_mail::send($klient_jmeno, $klient_email, "lpeska@seznam.cz", $predmet_ck, $ck_text);
					$this->confirm("Poptávka zájezdu byl úspìšnì odeslána.");
				}else{
					$this->chyba("Nepodaøilo se odeslat e-mail s dotazem. Zaregistrujte se prosím ještì jednou.");
				}		
			
			}
		}//!get_error_message()
	}			
	

	/**kontrola zda informace o cenach jsou spravne (nenulova id a pocet)*/
	function legal_data($id_cena,$pocet){
		$ok = 1;
		//kontrolovane pole id cena a poèet
			if(!Validace::int_min($id_cena,1) ){
				$ok = 0;
			}		
			if(!Validace::int_min($pocet,1) ){
				$ok = 0;
			}								
		//pokud je vse vporadku...
		if($ok == 1){
			$this->vyplnena_cena = 1;
			return true;
		}else{
			return false;
		}	
	}
		
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku == "get_zajezd"){
			$dotaz= "select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`dlouhodobe_zajezdy`,`serial`.`id_smluvni_podminky`,`serial`.`id_sablony_zobrazeni`,`serial`.`id_sablony_objednavka`,`zajezd`.`nazev_zajezdu`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,
                                        `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`

					from `serial` join
						`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                                                left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
					where `serial`.`id_serial`= ".$this->id_serial."
						and `zajezd`.`id_zajezd`=".$this->id_zajezd."
					limit 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku == "get_ceny"){
			$dotaz= "select `zajezd`.`id_zajezd`,`cena`.`id_cena`,`cena`.`nazev_ceny`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`
					from `zajezd` join
						`cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1) join
						`cena`  on (`cena`.`id_cena` = `cena_zajezd`.`id_cena`)
					where `zajezd`.`id_zajezd`=".$this->id_zajezd."";
			//echo $dotaz;
			return $dotaz;
		}
	}		
	
	/**kontrola zda mam odpovidajici data*/
	function correct_data($dlouhodobe){
		$ok = 1;
		if($this->typ_pozadavku == "odeslat"){
		//kontrolovaná data: název seriálu, popisek,  id_typ, 
			if(!Validace::text($this->jmeno) ){
				$ok = 0;
				$this->chyba("Je tøeba vyplnit Vaše jméno!");
			}
			if(!Validace::text($this->prijmeni) ){
				$ok = 0;
				$this->chyba("Je tøeba vyplnit Vaše pøíjmení!");
			}
			if(!Validace::email($this->email) ){
				$ok = 0;
				$this->chyba("Email není správnì vyplnìn!");
			}		
			if($dlouhodobe){
				if(!Validace::text($this->upresneni_terminu) ){
					$ok = 0;
					$this->chyba("U tohoto zájezdu je tøeba upøesnit termín, který požadujete!");
				}	
			}						
			if(!Validace::int_min($this->pocet_osob,1) ){
				$ok = 0;
				$this->chyba("Poèet osob není vyplnìný!");
			}	
		}																	
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}	
	/** zobrazeni formulare pro predbeznou poptavku zajezdu*/
	function show_form_predbezna_poptavka(){
		$uzivatel = User::get_instance();
		
		$zajezd = mysqli_fetch_array( $this->database->query($this->create_query("get_zajezd") ) )
			 or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
			if($zajezd["nazev_ubytovani"]!=""){
           $zajezd["nazev"] = $this->zajezd["nazev_ubytovani"]." - ".$zajezd["nazev"];
      	}					 	
		//data bud ziskam od prihlaseneho uzivatele automaticky, nebo je neprihlasen vyplni
			if( $uzivatel->get_correct_login() ){
				$klient = "<tr><td valign=\"top\">Objednávající:</td><td><strong>".$this->prijmeni." ".$this->jmeno."</strong>; ".$this->email."; ".$this->telefon."</td></tr>\n";
			}else{
				$klient = " <tr>
									<td valign=\"top\">Jméno: <span class=\"red\">*</span></td>
									<td><input  name=\"jmeno\" type=\"text\" value=\"".$this->jmeno."\" /></td>
									</td>
								</tr>
								<tr>
									<td valign=\"top\">Pøíjmení: <span class=\"red\">*</span></td>
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
			}	
			$serial = "<tr><th valign=\"top\">Zájezd:</th><th><strong>".$zajezd["nazev"]."</strong> (".$this->change_date_en_cz( $zajezd["od"] )." - ".$this->change_date_en_cz( $zajezd["do"] ).")</th></tr>\n";		
			$hidden = "
				<input name=\"id_serial\" type=\"hidden\" value=\"".$this->id_serial."\" />
				<input name=\"id_zajezd\" type=\"hidden\" value=\"".$this->id_zajezd."\" />
				";
			if($zajezd["dlouhodobe_zajezdy"]){
				$upresneni_term="<tr><td valign=\"top\">Upøesnìní termínu: <span class=\"red\">*</span></td><td><input name=\"upresneni_terminu\" type=\"text\" value=\"".$this->upresneni_terminu."\" /></td></tr>\n";
			}else{
				$upresneni_term="";
			}
			$pocet_osob = "<tr><td valign=\"top\">Poèet osob: <span class=\"red\">*</span></td><td><input name=\"pocet_osob\" type=\"text\" value=\"".$this->pocet_osob."\" /></td></tr>\n";			
			$poznamky = "<tr><td valign=\"top\">Poznámky:</td><td><textarea name=\"poznamky\" type=\"text\" cols=\"50\" rows=\"5\">".$this->poznamky."</textarea></td></tr>\n";
			
			//ziskam data z cen			
			$ceny_zajezdu = new Seznam_cen($this->id_serial,$this->id_zajezd);
			$ceny = $ceny_zajezdu->show_form_objednavka();

			$core = Core::get_instance();
			$adresa_rezervace = $core->get_adress_modul_from_typ("rezervace");
			if( $adresa_rezervace !== false ){//pokud existuje modul pro zpracovani
				$vystup="
					<table>
					<tr><td>
					<form action=\"".$this->get_adress(array("rezervace","predbezna_poptavka"))."\" method=\"post\">
						".$hidden."
						<div style=\"float:left;\">
						<table class=\"rezervace\"  cellpadding=\"0\" cellspacing=\"0\">
							".$serial.$klient.$upresneni_term.$pocet_osob.$poznamky."
						</table>
						<table class=\"rezervace_ceny\"  cellpadding=\"0\" cellspacing=\"0\" >
							".$ceny."
						</table>		
						</div>			
						<div class=\"resetovac\">&nbsp;</div>
						<input type=\"submit\" value=\"Odeslat pøedbìžnou poptávku\" />
						<p><span class=\"red\">*</span> - položky oznaèené hvìzdièkou je tøeba vyplnit</p>
						<p><span class=\"red\">**</span> - je tøeba vyplnit alespoò jednu službu, o kterou máte zájem</p> 
						<h3>Co se stane po odeslání?</h3>
						<p>Po odeslání formuláøe provìøí pracovníci CK dostupnost zájezdu a budou Vás dále informovat o možnosti objednat zájezd.<br/>
						 Odesláním poptávky pro Vás nevzniká žádná povinnost zájezd pozdìji závaznì objednat.
						 <br/>Mám zájem o zasílání aktuálních nabídek CK: <input type=\"checkbox\" name=\"novinky\" value=\"ano\"/></p>
						 						
					</form>
					</td></tr>
					</table>
					";
			}
			return $vystup;
	}	
	
} 

?>
