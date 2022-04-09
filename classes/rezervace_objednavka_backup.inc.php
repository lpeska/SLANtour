<?php
/** 
* trida pro obslouzeni prichoziho formulare s objedn�vkou z�jezdu
* 	- zpracov�n� objedn�vky m� 2 f�ze
*	- po uspesne druhe fazi je objednavka ulozena do databaze
* 	- odesle se e-mail tvurci serialu, na centralni e-mail systemu a potvrzeni klientovi
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_objednavka extends Generic_data_class{
	private $array_ceny;
	private $id_ceny; //pole id_cen
	private $pocet_ceny; //pole poctu objednavanych kapacit jednotlivych cen
	private $text_ceny; //vypis cen do e-mailu
	private $text_ceny_klient; //vypis cen do e-mailu	
	private $zajezd_info;
	
	private $array_osoby;
	private $new_clients; //pole dotazu na vytvoreni novych klientu
	private $id_clients; //pole id klientu
	private $text_klienti; //vypis klient� do e-mailu
	private $text_ucastnici_klient;

	private $objednavajici_ca;//pokud je zajezd objednavany agenturou, prida se odkaz na agenturu do rezervace
	private $stav;
	private $celkova_cena;
	private $rezervace_do;
	private $potvrzovaci_hlaska; //hlaska pro potvrzeni objednavky (ruzna v zavislosti na dostupnosti kapacit)
	private $upresneni_terminu_od;
	private $upresneni_terminu_do;
	private $pocet_noci;
	
	//vstupni data
	private $typ_pozadavku;	
	private $id_serial;
	private $id_zajezd;
	private $id_smluvni_podminky;
	private $id_sablony_objednavka;
	private $id_sablony_zobrazit;	
	private $id_klient;
	
	private $pocet_osob;
	private $pocet_cen;
	
	private $poznamky;	
	private $jmeno;	
	private $prijmeni;	
	private $datum_narozeni;
	private $email;	
	private $telefon;		
	private $ulice;	
	private $mesto;	
	private $psc;			
	private $novinky;
	
	private $cislo_ceny;
	private $cislo_osoby;	
	
	private $nazev_slevy;	
	private $castka_slevy;			
	private $velikost_slevy;
	
	private $vyplnena_cena;			
	private $vyplnene_odjezd_misto;		
	private $odjezd_misto_exist;
	protected $data;
	protected $serial;
	
	
	public $database; //trida pro odesilani dotazu	
	
		
//------------------- KONSTRUKTOR -----------------
	/**konstruktor t��dy na z�klad� formul��ov�ch dat odpov�daj�c�ch tabulce objednavka*/
	function __construct($typ_pozadavku, $id_serial, $id_zajezd,  
				$jmeno="", $prijmeni="", $datum_narozeni="", $email="", $telefon="", $ulice="", $mesto="", $psc="", 
				$pocet_osob="", $poznamky="", $pocet_cen="", $novinky="", $upresneni_terminu_od="", $upresneni_terminu_do=""){

		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//$uzivatel = User::get_instance();		
		//$this->id_klient = $uzivatel->get_id();
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->id_serial = $this->check_int($id_serial);
		$this->id_zajezd = $this->check_int($id_zajezd);
		
		$this->jmeno = $this->check($jmeno);
		$this->prijmeni = $this->check($prijmeni);
		$this->datum_narozeni = $this->check($datum_narozeni);
		$this->email = $this->check($email);
		$this->telefon = $this->check($telefon);
		$this->ulice = $this->check($ulice);
		$this->mesto = $this->check($mesto);
		$this->psc = $this->check($psc);
		$this->novinky = $this->check($novinky);
		
		$this->upresneni_terminu_od = $this->check($upresneni_terminu_od);
		$this->upresneni_terminu_do = $this->check($upresneni_terminu_do);
		
		$this->poznamky = $this->check($poznamky);		
		$this->pocet_osob = $this->check_int($pocet_osob);		
		$this->pocet_cen = $this->check_int($pocet_cen);				
		
		$this->zajezd_info = mysqli_fetch_array( $this->database->query($this->create_query("get_zajezd") ) )
			 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
				
		$this->zajezd_od = $this->zajezd_info["od"];
		$this->zajezd_do = $this->zajezd_info["do"];			
		
		//pokud je klient prihlaseny, stahneme si od nej info o CA
		if($_SESSION["id_klient"]!=""){
			$this->objednavajici_ca = $_SESSION["id_klient"];	
		}else{
			$this->objednavajici_ca = 0;
			/*$this->chyba("U�ivatel nen� spr�vn� p�ihl�en!");*/
		}

		//zkontroluju data o uzivateli (chybove hlasky jsou primo ve funkci correct_data() )
		$this->correct_data();
		
		$this->id_ceny = array();
		$this->pocet_ceny = array();
		$this->cislo_ceny = 0;		
		$this->text_ceny="";
				$this->text_ceny_klient="";	
		
		$this->array_osoby = array();
		$this->new_clients = array();
		$this->id_clients = array();
		$this->cislo_osoby = 0;		
		$this->odjezd_misto_exist = 0;
		$this->text_klient="";
	}	
	
/**prijima informace o jednotlivych sluzbach a sestavuje z nich ��sti dotazu do datab�ze*/
	function add_to_query_cena($id_cena,$pocet){
		//kontrola vstupnich dat
		$id_cena = $this->check_int($id_cena);
		$pocet = $this->check_int($pocet);
		
		//pokud jsou vporadku data, vytvorim danou cast dotazu
		if($this->legal_data_ceny($id_cena,$pocet)){		
			$this->cislo_ceny++;

			$this->id_ceny[ $this->cislo_ceny ] = $id_cena;		
			$this->pocet_ceny[ $this->cislo_ceny ] = $pocet;				
		}//if legal_data
	}
	
/**prijima informace o jednotlivych sluzbach a sestavuje z nich ��sti dotazu do datab�ze*/
	function add_to_query_cena_poznavaci($id_cena,$pocet,$typ_ceny){
		//kontrola vstupnich dat
		$id_cena = $this->check_int($id_cena);
		$pocet = $this->check_int($pocet);
		$typ_ceny = $this->check_int($typ_ceny);
		
		//pokud jsou vporadku data, vytvorim danou cast dotazu
		if($this->legal_data_ceny_poznavaci($id_cena,$pocet,$typ_ceny)){		
			$this->cislo_ceny++;

			$this->id_ceny[ $this->cislo_ceny ] = $id_cena;		
			$this->pocet_ceny[ $this->cislo_ceny ] = $pocet;				
		}//if legal_data
	}
	
	/**kontrola zda poslane ceny (jako celek) splnuji vsechny pozadavky - napr. alespon u jedne pocet<>0*/
	function check_ceny(){
		if(!$this->vyplnena_cena){
				$this->chyba("Je t�eba vyplnt alespo� jednu slu�bu!");
		}	
		if(!$this->get_error_message()){
			if($this->typ_pozadavku == "osoby"){
				$this->confirm("Po�adovan� slu�by byly zkontrolov�ny, pros�m vyplnte �daje o p�ihl�en�ch osob�ch");
			}else{
				$this->confirm("");
			}
		}
	}	
	/**kontrola zda poslane ceny (jako celek) splnuji vsechny pozadavky - napr. alespon u jedne pocet<>0*/
	function check_ceny_poznavaci(){
		if(!$this->vyplnena_cena){
				$this->chyba("Je t�eba vyplnt alespo� jednu slu�bu!");
		}	
		if(!$this->vyplnene_odjezd_misto and $this->odjezd_misto_exist){
				$this->chyba("Je t�eba zvolit odjezdov� m�sto!");
		}			
		if(!$this->get_error_message()){
			if($this->typ_pozadavku == "osoby"){
				$this->confirm("Po�adovan� slu�by byly zkontrolov�ny, pros�m vyplnte �daje o p�ihl�en�ch osob�ch");
			}else{
				$this->confirm("");
			}
		}
	}		
	
	/**prijima informace o jednotlivych osobach a sestavuje z nich ��sti dotazu do datab�ze*/
	function add_to_query_osoby(
			$checkbox_id_klient, $select_id_klient, $input_id_klient,
			$jmeno,$prijmeni, $titul, $email, $telefon, $datum_narozeni, $rodne_cislo, $cislo_pasu, $cislo_op,
			$ulice, $mesto, $psc
	){	
		$this->cislo_osoby++;
		//$uzivatel = User::get_instance();	
	
		//kontrola vstupnich dat
		
		//pokud m�m, naleznu id klienta
		//echo "osoba".$this->cislo_osoby."-id:".$checkbox_id_klient."-".$input_id_klient."-".$select_id_klient." \n";
		
		if($this->check_int($checkbox_id_klient)){
			$id_klient = $this->check_int($checkbox_id_klient);
		}else if($this->check_int($input_id_klient)){
			$id_klient = $this->check_int($input_id_klient);							
		}else if($this->check_int($select_id_klient)){
			$id_klient = $this->check_int($select_id_klient);
		}

		if($id_klient){//objednavajici prihlasuje sam sebe
				$jmeno = $this->jmeno;
				$prijmeni = $this->prijmeni;
				$email = $this->email;
				$telefon = $this->telefon;
				$datum_narozeni = $this->change_date_cz_en( $this->datum_narozeni);
				$ulice = $this->ulice;
				$mesto = $this->mesto;
				$psc = $this->psc;
				
			/*$data_klient = $this->database->transaction_query($this->create_query("get_klient",$id_klient) ) 
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
			$pocet_klient = mysqli_num_rows($data_klient);
			
			if($pocet_klient < 1){
				$this->chyba("Klient s dan�m Id neexistuje!!");
			}else{
				$klient = mysqli_fetch_array($data_klient);
				
				$jmeno = $this->check_slashes( $klient["jmeno"] );
				$prijmeni = $this->check_slashes( $klient["prijmeni"] );
				$titul = $this->check_slashes( $klient["titul"] );		
				$email = $this->check_slashes( $klient["email"] );
				$telefon = $this->check_slashes( $klient["telefon"] );
				$datum_narozeni =  $this->check($klient["datum_narozeni"]);
				$rodne_cislo = $this->check_slashes( $klient["rodne_cislo"] );
				$cislo_pasu = $this->check_slashes( $klient["cislo_pasu"] );
				$cislo_op = $this->check_slashes( $klient["cislo_op"] );			
				$ulice = $this->check_slashes( $klient["ulice"] );	
				$mesto = $this->check_slashes( $klient["mesto"] );	
				$psc = $this->check_slashes( $klient["psc"] );		
						
			}	*/
		}else{
			$jmeno = $this->check_slashes( $this->check($jmeno) );
			$prijmeni = $this->check_slashes( $this->check($prijmeni) );
			$titul = $this->check_slashes( $this->check($titul) );
		
			$email = $this->check_slashes( $this->check($email) );
			$telefon = $this->check_slashes( $this->check($telefon) );
			$datum_narozeni = $this->change_date_cz_en( $this->check($datum_narozeni) );
			$rodne_cislo = $this->check_slashes( $this->check($rodne_cislo) );
			$cislo_pasu = $this->check_slashes( $this->check($cislo_pasu) );
			$cislo_op = $this->check_slashes( $this->check($cislo_op) );	
		
			$ulice = $this->check_slashes( $this->check($ulice) );	
			$mesto = $this->check_slashes( $this->check($mesto) );	
			$psc = $this->check_slashes( $this->check($psc) );			
		}

		
		//pokud jsou vporadku data, vytvorim danou cast dotazu 
		
		if($this->legal_data_osoby($id_klient,$jmeno,$prijmeni,$titul,$email,$telefon,$datum_narozeni,$rodne_cislo,$cislo_pasu,$cislo_op,$ulice,$mesto,$psc)){				
			
			//objedn�vaj�c�ho vytvor�me ve finish()
			if($id_klient!=0){
				$create_new_client = 0; //zna�� zda m�m vytvo�it nov�ho klienta
				$this->id_clients[ $this->cislo_osoby ] = $id_klient;
			}else{
				$create_new_client = 1;
			}
			
			//ukladam do seznamu osob
			$this->array_osoby[$this->cislo_osoby] = array("jmeno" => $jmeno, "prijmeni" => $prijmeni, "titul" => $titul,
				"email" => $email, "telefon" => $telefon, "datum_narozeni" => $datum_narozeni, "rodne_cislo" => $rodne_cislo, 
				"cislo_pasu" => $cislo_pasu, "cislo_op" => $cislo_op, "ulice" => $ulice, "mesto" => $mesto, "psc" => $psc);
			
			//je-li treba, vytvarim pole pro tvorbu novych klientu
			if($create_new_client == 1){
				$this->new_clients[ $this->cislo_osoby ] = "INSERT INTO `user_klient` (`jmeno`,`prijmeni`,`titul`,`email`,`telefon`,`datum_narozeni`,`rodne_cislo`,
						`cislo_pasu`,`cislo_op`,`ulice`,`mesto`,`psc`,`vytvoren_klientem`) 
						VALUES ('".$jmeno."','".$prijmeni."','".$titul."','".$email."','".$telefon."','".$datum_narozeni."',
						'".$rodne_cislo."','".$cislo_pasu."','".$cislo_op."','".$ulice."','".$mesto."','".$psc."',
						1)";
			}
			
		}//if legal_data
	}

	
	function calculate_prize($castka, $pocet, $pocet_noci, $use_pocet_noci=0){	  
    //dummy
	 if($pocet_noci==0){
	 	$pocet_noci=1;
	 }
	 if($use_pocet_noci!=0){
    	$this->celkova_cena = $this->celkova_cena + ($castka*$pocet*$pocet_noci);
    	return $castka*$pocet*$pocet_noci;	 
	 }else{
    	$this->celkova_cena = $this->celkova_cena + ($castka*$pocet);
    	return $castka*$pocet;	 
	 }

  }
	
	/** funkce pro fin�ln� zpracov�n� 2. ��sti formul��e pro objedn�vku z�jezdu
	* - zkontroluje, zda lze zarezervovat kapacity
	* - po prijmuti vsech dat vytvori cely dotaz a odesle ho do datab�ze
	* - vytvo�� e-maily s potvrzen�m objedn�vky
	*/
	function finish(){
	if(!$this->get_error_message() ){
	
		$this->database->start_transaction();
		
		$this->stav = 2;
		$this->celkova_cena = 0;
		//ziskani serialu z databaze	
		$zajezd = mysqli_fetch_array( $this->database->transaction_query($this->create_query("get_zajezd") ) )
		 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		//ziskani jednotlivych cen
		$data_ceny = $this->database->transaction_query($this->create_query("get_ceny") ) 
			or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );			

			//ziskani info o agenture, pokud existuje
		if($this->objednavajici_ca){	
			$agentura = mysqli_fetch_array( $this->database->transaction_query($this->create_query("get_agentura") ) )
			 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		}
						
		//ziskani maximalni slevu
		$data_slevy = $this->database->transaction_query($this->create_query("get_sleva") ) 
			or $this->chyba("Chyba p�i dotazu do datab�ze: ".$this->create_query("get_sleva").mysqli_error($GLOBALS["core"]->database->db_spojeni) );									
			
		if(mysqli_num_rows($data_slevy)>=1){
			$slevy = mysqli_fetch_array( $data_slevy );
				
			$this->nazev_slevy = $slevy["nazev_slevy"];
			$this->castka_slevy = $slevy["castka"]." ".$slevy["mena"];
			if($slevy["nazev_slevy"]!=""){
				if($slevy["mena"]=="%"){
					$velikost_slevy = 0;
					$count_velikost_slevy = 1;
				}else{
					$velikost_slevy = floor($slevy["castka"]*$this->pocet_osob);
					$count_velikost_slevy = 0;
				}
			}else{
				$count_velikost_slevy = 0;
				$velikost_slevy = 0;
			}
		}else{
				$count_velikost_slevy = 0;
				$velikost_slevy = 0;
		}
		$vyprodano = 0;
		$na_dotaz = 0;
		$obsazena_kapacita = 0;
		$this->text_ceny = "";
		$this->text_ceny_klient="";
		$update_kapacity = array(); //pole pro pripadne dotazy se zmenou volne kapacity cen				
		//vypocitam pocet noci
		$this->pocet_noci = $this->calculate_pocet_noci($zajezd["od"],$zajezd["do"], $this->change_date_cz_en($this->upresneni_terminu_od), $this->change_date_cz_en($this->upresneni_terminu_do));	

		//slevy pro stale klienty
		if($_POST["pocet_slev"] >= 1){
			$i=0;
			$this->poznamky .= "\n <strong>DAL�� PO�ADAVKY:</strong>\n";
			while($i <= $_POST["pocet_slev"]){
				$this->poznamky .= $this->check_slashes( $_POST["sleva_".$i] )."\n";
				$i++;
			}
		}
		
	/*----------------------------kontrola cen--------------------------------*/
		//vyhrazeni kapacity cen
		while($ceny = mysqli_fetch_array( $data_ceny ) ){
			$cislo_ceny = array_search($ceny["id_cena"], $this->id_ceny);
			//vycleneni kapacit provadim pouze pro specifikovane ceny
			if( $cislo_ceny !== false ){
				$pocet = $this->pocet_ceny[ $cislo_ceny ];
				
				if($pocet!=0){
					//pridam do celkove ceny										
					$cena_sluzby = $this->calculate_prize($ceny["castka"],$pocet,$this->pocet_noci,$ceny["use_pocet_noci"]);					
					
					//pridam castku do slevy - zde musi byt udaj v %!!! pouze pro sluzby (ne priplatky aj)
					if($count_velikost_slevy and intval($ceny["poradi_ceny"]) < 200 and intval($ceny["typ_ceny"]) == 1){
						$velikost_slevy = $velikost_slevy + ($cena_sluzby*$slevy["castka"]/100);
					}
					
					//upravim textovou informaci o objednavanych kapacitach (do e-mailu)
					$this->text_ceny .= "<tr><td>".$ceny["nazev_ceny"]."</td><td>".$ceny["castka"]." ".$ceny["mena"]."</td><td>".$pocet."</td></tr>";
					$this->text_ceny_klient .="<tr>	
										<td style=\"padding-right:50px;\">".$ceny["nazev_ceny"]."</td><td align=\"right\">".$ceny["castka"]." ".$ceny["mena"]."</td><td align=\"right\">".$pocet."</td><td align=\"right\">".$cena_sluzby." ".$ceny["mena"]."</td>								
									</tr>";
					//kontroluju, zda jsou vsechny ceny dostupne
					if( $ceny["vyprodano"] == 1 ){
						$vyprodano = 1;
					}else if( $ceny["na_dotaz"] == 1 ){
						$na_dotaz = 1;
					}else if( $ceny["kapacita_bez_omezeni"] == 1 ){
						
					}else{
						if( $ceny["kapacita_volna"] >=  $pocet ){

						}else{
							$obsazena_kapacita = 1;
						}
					}
					//vytvorm dotaz pro zmenu kapacity ceny
					$update_kapacity[$cislo_ceny] = "
						UPDATE `cena_zajezd` 
						SET `kapacita_volna` = ".($ceny["kapacita_volna"] - $pocet)."
						WHERE `id_cena`=".$ceny["id_cena"]." and `id_zajezd`=".$this->id_zajezd." 
						LIMIT 1";					
				}
			}
		}//end while
		$this->velikost_slevy = floor($velikost_slevy);
		
		//uzivatel musi udat pocet alespon u jedne ceny (testovano v legal_data() )
			//kontrola zda jsem spravne vyplnil ceny
		if($_POST["zpusob_vyhodnoceni"]=="poznavaci"){						
			$this->check_ceny_poznavaci();				 
		}else{
			$this->check_ceny();	
		}		
			
		//pokud muzeme ihned rezervovat kapacitu
		if($vyprodano == 0 and $na_dotaz == 0 and $obsazena_kapacita == 0 and ALLOW_IMMEDIATE_RESERVATION == 1){
			$this->stav = 3;
			$this->potvrzovaci_hlaska = "D�kujeme za Va�i objedn�vku.<br/> Va�e objedn�vka byla p�ijata do syst�mu. Slu�by, o kter� jste projevil(a) z�jem jsou voln� a byly zarezervov�ny.";
			$hlaska_color="#009049";
			$bg_color="#bde38a";			
			//nastaveni data ukonceni rezervace
			//pokud je odjezd zajezdu jeste dostatecne daleko, nastavime standartni delku rezervace, jinak pouze jeden den
			if( $zajezd["od"] >= Date("Y-m-d",(time() + ( 2* PLATNOST_OPCE * 24 * 60 * 60)) ) ){
				$this->rezervace_do = Date("Y-m-d",(time() + (PLATNOST_OPCE * 24 * 60 * 60)) );
			}else{
				$this->rezervace_do = Date("Y-m-d",(time() + ( 1 * 24 * 60 * 60)) );
			}
			
			//updatuju kapacity jednotlivych cen
			foreach ($update_kapacity as $i => $dotaz) {
				$dotaz_kapacita = $this->database->query($dotaz)
	 				or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );				
			} 				
		}else if($vyprodano == 1){
			$this->potvrzovaci_hlaska = "Va�e objedn�vka byla p�ijata do syst�mu. N�kter� slu�by o kter� jste projevil(a) z�jem jsou nyn� vyprod�ny a tedy nen� mo�n� je rezervovat. Pokud se po�adovan� kapacity uvoln�, budeme V�s informovat.";			
			$hlaska_color="#da4000";
			$bg_color="#ffe3ca";
                        $this->rezervace_do = "0000-00-00";
		}else if($obsazena_kapacita == 1){
			$this->potvrzovaci_hlaska = "Va�e objedn�vka byla p�ijata do syst�mu. Dostupnost n�kter�ch slu�eb o kter� jste projevil(a) z�jem jsou pouze \"na dotaz\" a tedy nebylo mo�n� je ihned rezervovat. Pracovn�ci CK prov��� jejich aktu�ln� dostupnost a budou V�s d�le informovat.";			
			$hlaska_color="#009049";	
			$bg_color="#bde38a";
                        $this->rezervace_do = "0000-00-00";

		}else if($na_dotaz == 1){
			$this->potvrzovaci_hlaska = "Va�e objedn�vka byla p�ijata do syst�mu. Dostupnost n�kter�ch slu�eb o kter� jste projevil(a) z�jem jsou pouze \"na dotaz\" a tedy nebylo mo�n� je ihned rezervovat. Pracovn�ci CK prov��� jejich aktu�ln� dostupnost a budou V�s d�le informovat.";			
			$hlaska_color="#009049";
			$bg_color="#bde38a";
                        $this->rezervace_do = "0000-00-00";

		}else if(ALLOW_IMMEDIATE_RESERVATION == 0){
			$this->potvrzovaci_hlaska = "Va�e objedn�vka byla p�ijata do syst�mu. Pracovn�ci CK j� potvrd� a budou V�s d�le informovat.";			
			$hlaska_color="#009049";
			$bg_color="#bde38a";
                        $this->rezervace_do = "0000-00-00";

		}
		
		/*----------------------------create objednavky--------------------------------*/
		if( !$this->get_error_message() ){			
			//nejprve vlozim do databaze objedn�vaj�c�ho a ziskam jeho id
			$dotaz_objednavajici =  $this->database->transaction_query($this->create_query("create_objednavajici") )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			$this->id_klient = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
			
			
			//nejprve vlozim do databaze objednavku a ziskam jeji id
			$dotaz_objednavka =  $this->database->transaction_query($this->create_query("create_objednavka") )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			$id_objednavka = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
			
		/*----------------------------create cen objednavky--------------------------------*/	
			//vytvorim dotaz pro objednavku cen
			$objednavka_cen = "INSERT INTO `objednavka_cena` (`id_objednavka`,`id_cena`,`pocet`) VALUES ";
			$j=0;
			foreach ($this->id_ceny as $i => $id) {
				if($j==0){ //zjistim zda mam dat pred hodnoty carku
					$objednavka_cen = $objednavka_cen."(".$id_objednavka.",".$id.",".intval($this->pocet_ceny[ $i ]).")"; 
				}else{
					$objednavka_cen = $objednavka_cen.", (".$id_objednavka.",".$id.",".intval($this->pocet_ceny[ $i ]).")"; 
				}
   			$j++;
			} 
			$dotaz_ceny =  $this->database->transaction_query( $objednavka_cen )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			//echo $objednavka_cen;
			 
		/*----------------------------tvorba osob--------------------------------*/	 
			//vytvorim jednotlive osoby, pokud je to treba
			foreach ($this->new_clients as $i => $dotaz) {
				$dotaz_klient =  $this->database->transaction_query( $dotaz )
		 			or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				//doplnim informace o id klientu
				$this->id_clients[ $i ] = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
				
				//echo $dotaz;
			} 
			
			//vytvorim dotaz pro objednavku_osob
			$objednavka_osob = "INSERT INTO `objednavka_osoby` (`id_objednavka`,`id_klient`,`cislo_osoby`) VALUES ";			
			$j=0;
			foreach ($this->id_clients as $i => $id) {
				if($j==0){ //zjistim zda mam dat pred hodnoty carku
					$objednavka_osob = $objednavka_osob."(".$id_objednavka.",".$id.",".($i+1).")"; 
				}else{
					$objednavka_osob = $objednavka_osob.", (".$id_objednavka.",".$id.",".($i+1).")"; 
				}
   			$j++;
				//upravim textovou informaci o klientech -> pouzije se v potvrzovacim emailu
				if( $this->array_osoby[$i]["cislo_pasu"]!="" ){
					$doklad = $this->array_osoby[$i]["cislo_pasu"];
				}else{
					$doklad = $this->array_osoby[$i]["cislo_op"];
				}
				
				$this->text_ucastnici_klient .="
					<tr>
						<td rowspan=\"3\" valign=\"top\" width=\"20px;\">
							<strong style=\"font-size:2em;\">".$i."</strong>
						</td>
						<td><strong  style=\"font-size: 1.2em;\">".$this->array_osoby[$i]["titul"]." ".$this->array_osoby[$i]["jmeno"]." ".$this->array_osoby[$i]["prijmeni"]."</strong></td><td>e-mail: ".$this->array_osoby[$i]["email"]."</td> <td>tel.: ".$this->array_osoby[$i]["telefon"]."</td>
					</tr>
					<tr>
						<td>datum nar.: ".$this->change_date_en_cz( $this->array_osoby[$i]["datum_narozeni"] )."</td><td>R�: ".$this->array_osoby[$i]["rodne_cislo"]."</td><td>�. dokladu: ".$doklad."</td>
					</tr>
					<tr>
						<td colspan=\"3\">Adresa: ".$this->array_osoby[$i]["ulice"].",  ".$this->array_osoby[$i]["psc"].", ".$this->array_osoby[$i]["mesto"]."</td>
					</tr>		
					<tr>
						<td colspan=\"4\"><hr style=\"color: #4682B4; margin-right:15px; height:2px;\"/></td>
					</tr>	
				";
			} 
			//echo $objednavka_osob;
			$dotaz_osoby =  $this->database->transaction_query( $objednavka_osob )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );					

		/*----------------------------odeslani e-mailu s objednavkou--------------------------------*/	
			if( !$this->get_error_message() ){
			
				$this->database->commit();//potvrzeni transakce - odeslani e-mailu uz neni zasadni..
				
				//ziskani sablony pro odesilani objednavky
				$this->id_sablony_objednavka = $zajezd["id_sablony_objednavka"];
				$this->id_sablony_zobrazit = $zajezd["id_sablony_zobrazeni"];
				$sablona = mysqli_fetch_array( $this->database->transaction_query($this->create_query("sablona_objednavka") ) )
		 					or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				$sablona_zobrazeni = mysqli_fetch_array( $this->database->transaction_query($this->create_query("sablona_zobrazit") ) )
		 					or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );							
				require_once "./".ADRESAR_SABLONA."/".$sablona["adresa_sablony"]."";			
				
				//odeslu e-maily
				//odeslu klientovi e-mail s potvrzovacim kodem
				if($sablona_zobrazeni["adresa_sablony"]=="zobrazit_vstupenky.inc.php"){
					$predmet_ck = "Objedn�vka vstupenek: ".$zajezd["nazev"]." ";
					$predmet_klient = "Potvrzen� odesl�n� objedn�vky vstupenek";					
				}else{
					$predmet_ck = "Objedn�vka z�jezdu: ".$zajezd["nazev"]." ";
					$predmet_klient = "Potvrzen� odesl�n� objedn�vky z�jezdu";				
				}
				
								
				$klient_jmeno = $this->prijmeni." ".$this->jmeno;
				$klient_email = $this->email;
				$rsck_email = PRIJIMACI_EMAIL;
				$zamestnanec_email = $zajezd["email"];
	
				if($this->upresneni_terminu_od != ""){
					$termin = "<tr>
						<td><strong>Up�esn�n� term�nu:</strong> ".$this->upresneni_terminu_od." - ".$this->upresneni_terminu_do."</td>  <td></td>
						</tr>";
				}else{
					$termin = "";
				}
				if($this->objednavajici_ca){			
					$info_agentura = "
						<table class=\"objednavka\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
							<tr>
								<td style=\"border-top: 3px solid #3d3937;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #3d3937;	background-color: #efefef;	valign=\"top\">
									<h2 style=\"font-size: 1.4em;margin:0 0 0 10px;padding:0;\">Z�jezd je objedn�v�n prost�ednictv�m agentury:</h2>
									<p style=\"margin:0 5px 5px 20px;\">
									<strong style=\"font-size: 1.2em;\">".$agentura["jmeno"]."</strong>; ".$agentura["ulice"].", ".$agentura["mesto"].", ".$agentura["psc"]." <br/>
									telefon: ".$agentura["telefon"]." <br/>
									e-mail: ".$agentura["email"]." <br/>	
									</p>								
								</td>
							</tr>
						</table>
					";
				}else{
					$info_agentura = "";
				}
				if($this->velikost_slevy > 0){
					$text_slevy = "
							<tr>	
								<td colspan=\"4\"> <hr style=\"color: black; height:2px;\"/> </td>								
							</tr>	
							<tr>
								<th align=\"left\" style=\"padding-right:50px;\">N�ZEV SLEVY</th><th align=\"right\">Sleva</th><th align=\"right\"></th><th align=\"right\">Celkem</th>
							</tr>		
							<tr>	
								<td style=\"padding-right:50px;\">".$slevy["nazev_slevy"]."</td><td align=\"right\">".$slevy["castka"]." ".$slevy["mena"]."</td><td align=\"right\"></td><td align=\"right\">".$this->velikost_slevy." K�</td>								
							</tr>		
					";
				}else{
					$text_slevy = "";
				}	
						
				if($sablona_zobrazeni["adresa_sablony"]=="zobrazit_vstupenky.inc.php"){
					$obj_nadpis = "Objedn�vka vstupenek CK SLAN tour";		
					$zajezd_nadpis = "Vstupenky";		
				}else{
					$obj_nadpis = "Objedn�vka z�jezdu CK SLAN tour";			
					$zajezd_nadpis = "Z�jezd";			
				}						
				$klient_text="
<div style=\"	font-family: Helvetica, Arial,  sans-serif;font-size: 12px;	margin: 0;	padding: 0;\">

<table class=\"objednavka\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
	<tr>
		<td width=\"420\"  style=\"	border-top: 3px solid ".$hlaska_color."; border-left: 1px solid white;	 border-right:1px solid white;border-bottom: 3px solid ".$hlaska_color."; background-color: ".$bg_color.";\" valign=\"top\">
				<h1 style=\"font-size: 1.6em;color: ".$hlaska_color.";margin:0 0 0 10px;padding:0;\">".$obj_nadpis."</h1>
				<p style=\"margin:0 5px 5px 20px;	font-size:1.0em;	font-weight: bold;	clear:left;\">
					".$this->potvrzovaci_hlaska."
				</p>
			</td>		
		<td width=\"15\">&nbsp;</td>
		<td width=\"200\" style=\"	border-top: 3px solid #3d3937;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #3d3937;	background-color: #efefef;	valign=\"top\">
			<h2 style=\"font-size: 1.4em;margin:0 0 0 10px;padding:0;\">SLAN tour s.r.o.</h2>
			<p style=\"margin:0 5px 5px 20px;	font-size:1.0em;	font-weight: bold;	clear:left;\">
				Wilsonova 597, Slan�, 274 01<br/>
				tel.: 312520084, 312523836<br/>
				e-mail: <a href=\"mailto:info@slantour.cz\">info@slantour.cz</a><br/>
				web: <a href=\"https://www.slantour.cz\">www.slantour.cz</a><br/>
			</p>
		</td>		
</tr>
</table>				
		".$info_agentura."	
<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
	<tr>
		<td style=\"	border-top: 3px solid #E77919;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #E77919;	background-color: #FFFDD4;	padding-bottom:5px;	padding-top:2px;\" valign=\"top\">
			<h2 style=\"font-size: 1.4em;color: #BF6A00;margin:0 0 0 10px;\">Objedn�vaj�c�</h2>
			<table style=\"width:100%;margin-left:20px;	font-size: 12px;clear:left;\">
				<tr>
					<td><strong style=\"font-size: 1.2em;\">".$this->prijmeni." ".$this->jmeno."</strong></td> <td>e-mail: ".$this->email."</td> <td>tel.: ".$this->telefon."</td>
				</tr>
				<tr>
					<td colspan=\"2\" align=\"left\">Adresa: ".$this->ulice.", ".$this->psc.", ".$this->mesto."</td><td align=\"left\">datum nar.: ".$this->datum_narozeni."</td>
				</tr>								
			</table>
		</td>		
	</tr>	
</table>			
				
<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
	<tr>
		<td style=\"	border-top: 3px solid #DA251D;	 border-left: 1px solid white;	 border-right:1px solid white;	border-bottom: 3px solid #DA251D;	background-color: #FFFDD4;	padding-bottom:5px;	padding-top:2px;\" valign=\"top\">
			<h2 style=\"font-size: 1.4em;color: #DA251D;margin:0 0 0 10px;\">".$zajezd_nadpis."</h2>
			
			<table style=\"width:100%;margin-left:20px;	font-size: 12px;	clear:left;\">
				<tr>
					<td><strong style=\"font-size: 1.2em;\">".$zajezd["nazev"]."</strong>, ".$zajezd["nazev_zajezdu"]."</td>  <td align=\"right\" style=\"padding-right:50px;\">term�n: <b>".$this->change_date_en_cz($zajezd["od"])." - ".$this->change_date_en_cz($zajezd["do"])."</b></td>
				</tr>
				<tr>
					<td><strong>Po�et osob</strong>: ".$this->pocet_osob."</td>  <td></td>
				</tr>			
				".$termin."
				
				<tr>
					<td colspan=\"2\"><b>Po�adovan� slu�by</b></td>
				</tr>		
				<tr>
					<td colspan=\"2\">
						<table style=\"margin-left:15px; width:90%;font-size: 12px;	clear:left;\">							
							<tr>
								<th align=\"left\" style=\"padding-right:50px;\">N�zev slu�by</th><th align=\"right\">Cena</th><th align=\"right\">Po�et</th><th align=\"right\">Celkem</th>
							</tr>
							".$this->text_ceny_klient
							.$text_slevy."																	
							<tr>	
								<td colspan=\"4\"> <hr style=\"color: black; height:2px;\"/> </td>								
							</tr>	
														
							<tr>
								<th colspan=\"3\" align=\"left\" style=\"padding-right:50px;\"><strong style=\"font-size: 1.2em;\">P�edpokl�dan� celkov� cena</strong></th><th  align=\"right\"><strong style=\"font-size: 1.2em; color:red;\">".($this->celkova_cena - $this->velikost_slevy)." K�</strong></th>
							</tr>																																		
						</table>									
					</td>
				</tr>	
				<tr>
					<td colspan=\"2\"  style=\"padding-right:20px;\"><b>Pozn�mky:</b><br/>
						 ".nl2br($this->poznamky)."
					</td>
				</tr>													 		
			</table>
		</td>		
	</tr>	
</table>		


<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
	<tr>
		<td style=\"	border-top: 3px solid #007CC3;	 border-left: 1px solid white;	 border-right:1px solid white;	border-bottom: 3px solid #007CC3;	background-color: #FFFDD4;	padding-bottom:5px;	padding-top:2px; \" valign=\"top\">
			<h2 style=\"font-size: 1.4em;color: #007CC3;margin:0 0 0 10px;\">Seznam ��astn�k�</h2>
			<table style=\"width:100%;margin-left:15px;	font-size: 12px;	clear:left;\">
				".$this->text_ucastnici_klient."													 		
			</table>
	</td>
</tr>
</table>			
";
if(!$this->objednavajici_ca){
$klient_text.="
<table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
	<tr>
		<td style=\"	border-top: 3px solid #b3ae4a;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #b3ae4a;	background-color: #FFFDD4;	padding-bottom:5px; padding-right:20px;	padding-top:2px;\" valign=\"top\">
			<h2 style=\"font-size: 1.4em;color: #5a5727;margin:0 0 0 10px;\">Informace o platb�</h2>
			<p style=\"width:100%;margin:0 20px 0 20px;	font-size: 12px;	clear:left;\">
				".$pokyny_k_platbe."	
			</p>
		</td>		
	</tr>	
</table>";
}
$klient_text.="
							Zas�l�n� aktu�ln�ch zpr�v CK: ".$this->novinky."<br/>
							Objedn�vka z webu: ".$_SERVER["SERVER_NAME"]."<br/>	<br/>
							Odesl�n�m objedn�vky z�rove� souhlas�m se smluvn�mi podm�nkami CK SLAN tour.<br/><br/>				
	</div>		
				";							
				$ck_text = $klient_text;		
				
				//odeslani potvrzovaciho e-mailu
				if($this->objednavajici_ca){	
					$mail = Send_mail::send(AUTO_MAIL_SENDER, AUTO_MAIL_EMAIL, $agentura["email"], $predmet_klient, $klient_text);	
				}else{
					$mail = Send_mail::send(AUTO_MAIL_SENDER, AUTO_MAIL_EMAIL, $klient_email, $predmet_klient, $klient_text);	
				}							
				
				if($mail){
					//odeslani emailu na standardni adresu systemu		
					if($this->objednavajici_ca){	
						Send_mail::send($agentura["jmeno"], $agentura["email"], $rsck_email, $predmet_ck, $ck_text);	
						Send_mail::send($agentura["jmeno"], $agentura["email"], "lpeska@seznam.cz", $predmet_ck, $ck_text);
					}else{
						Send_mail::send($klient_jmeno, $klient_email, $rsck_email, $predmet_ck, $ck_text);		
						Send_mail::send($klient_jmeno, $klient_email, "lpeska@seznam.cz", $predmet_ck, $ck_text);
					}		
								
					//odesilani e-mailu zamestnanci - tvurci serialu					
					$this->confirm("Objedn�vka z�jezdu byla �sp�n� odesl�na.");
					
				}else{				
					$this->chyba("Nepoda�ilo se odeslat e-mail s objedn�vkou.");
				}	
								
			}
									
		}
	}//!this->get_error_message()
	}			
	

	/**kontrola zda jsou informace o osob�ch spr�vn� (neprazdne nazvy, nenulova id atd.)*/
	function legal_data_osoby($id_klient,$jmeno,$prijmeni,$titul,$email,$telefon,$datum_narozeni,$rodne_cislo,$cislo_pasu,$cislo_op,$ulice,$mesto,$psc){
		$ok = 1;
		if( !Validace::int_min($id_klient,1) ){
		
			if(!Validace::text($jmeno) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit jm�no u osoby �.".$this->cislo_osoby);
			}
			if(!Validace::text($prijmeni) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit p��jmen� u osoby �.".$this->cislo_osoby);
			}
			
			/*
			if(!Validace::datum_en($datum_narozeni) ){
				$ok = 0;
				$this->chyba("Datum narozen� mus� b�t ve form�tu dd.mm.rrrr u osoby �.".$this->cislo_osoby);
			}				
			if(!Validace::email($email) ){
				$ok = 0;
				$this->chyba("�patn� vypln�n� e-mail u osoby �.".$this->cislo_osoby);
			}	
			if(!Validace::text($ulice) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit ulici a ��slo popisn� u osoby �.".$this->cislo_osoby);
			}
			if(!Validace::text($mesto) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit m�sto u osoby �.".$this->cislo_osoby);
			}		
			if(!Validace::text($psc) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit PS� u osoby �.".$this->cislo_osoby);
			}	
			*/		
		}													
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
		
	/**kontrola zda informace o cen�ch (, nenulova id a po�et objednan�ch jednotek)*/
	function legal_data_ceny($id_cena,$pocet){
		$ok = 1;
		//kontrolovane pole id cena a po�et
			if(!Validace::int_min($id_cena,1) ){
				$ok = 0;
			}		
			if(!Validace::int_min_max($pocet,1,MAX_OSOB) ){
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
	/** vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku, $id_klient=""){
		if($typ_pozadavku == "get_zajezd"){
			$dotaz= "select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`dlouhodobe_zajezdy`,`serial`.`id_smluvni_podminky`,`serial`.`id_sablony_zobrazeni`,`serial`.`id_sablony_objednavka`,`zajezd`.`nazev_zajezdu`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`
					from `serial` join
						`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
					where `serial`.`id_serial`= ".$this->id_serial." 
						and `zajezd`.`id_zajezd`=".$this->id_zajezd."
					limit 1";
			//echo $dotaz;
			return $dotaz;			
			
		}else if($typ_pozadavku == "get_ceny"){
			$dotaz= "select `zajezd`.`id_zajezd`,`cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`use_pocet_noci`,`cena`.`kapacita_bez_omezeni`,`cena`.`poradi_ceny`,`cena`.`typ_ceny`,
						`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`kapacita_volna`
					from `zajezd` join
						`cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`) join
						`cena`  on (`cena`.`id_cena` = `cena_zajezd`.`id_cena`)
					where `zajezd`.`id_zajezd`=".$this->id_zajezd."";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku == "get_sleva"){
			$uzivatel = User::get_instance();
			$dotaz= "select * from `slevy` left join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy` and `slevy_serial`.`id_serial` = ".$this->id_serial.")
                                                       left join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy` and `slevy_zajezd`.`id_zajezd` = ".$this->id_zajezd.")
							where (`slevy_serial`.`platnost`=1 or `slevy_zajezd`.`platnost` =1 )
                                                        and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							and `slevy`.`sleva_staly_klient` = 0
							order by `slevy`.`castka` desc limit 1";
			//echo $dotaz;
			return $dotaz;


								
		}else if($typ_pozadavku == "get_klient"){
			$uzivatel = User::get_instance();
			$dotaz= "select `id_klient`,`jmeno`,`prijmeni`,`titul`,`email`,`telefon`,`datum_narozeni`,`rodne_cislo`,
						`cislo_pasu`,`cislo_op`,`ulice`,`mesto`,`psc`
					from `user_klient`
					where `id_klient`=".$id_klient."";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku == "get_agentura"){
			$uzivatel = User::get_instance();
			$dotaz= "select `id_klient`,`jmeno`,`prijmeni`,`email`,`telefon`,
						`ulice`,`mesto`,`psc`,`ico`
					from `user_klient`
					where `id_klient`=".$this->objednavajici_ca."";
			//echo $dotaz;
			return $dotaz;						
		}else if($typ_pozadavku == "get_drive_objednane_osoby"){
			$uzivatel = User::get_instance();
			$dotaz= "select `id_klient`,`jmeno`,`prijmeni`,`datum_narozeni`
					from `user_klient`
					where `id_klient_create`=".intval($uzivatel->get_id())."";
			//echo $dotaz;
			return $dotaz;			
						
		}else if($typ_pozadavku == "create_objednavka"){
			if($this->objednavajici_ca){
				$name_agentury = "`id_agentury`,";
				$val_agentury = "".$this->objednavajici_ca.",";
			}else{
				$name_agentury = "";
				$val_agentury = "";			
			}
			if($this->upresneni_terminu_od){
				$terminy = "`termin_od`,`termin_do`,";
				$val_terminy = "'".$this->change_date_cz_en($this->upresneni_terminu_od)."','".$this->change_date_cz_en($this->upresneni_terminu_do)."',";
			}else{
				$terminy = "";
				$val_terminy = "";
			}
			if($this->nazev_slevy){
			$dotaz= "INSERT INTO `objednavka` 
							(`id_klient`,".$name_agentury."`id_serial`,`id_zajezd`,`datum_rezervace`,`rezervace_do`,
							`stav`,`pocet_osob`,`celkova_cena`,`zbyva_zaplatit`,`poznamky`,`security_code`,".$terminy."`pocet_noci`,`nazev_slevy`,`castka_slevy`,`velikost_slevy`)
						VALUES
							 (".$this->id_klient.",".$val_agentury."".$this->id_serial.",".$this->id_zajezd.",'".Date("Y-m-d H:i:s")."','".$this->rezervace_do."',
							 ".$this->stav.",".$this->pocet_osob.",".$this->celkova_cena.",".$this->celkova_cena.",'".$this->poznamky."',
							 '".sha1(mt_rand().$this->id_klient)."',".$val_terminy."".$this->pocet_noci.",'".$this->nazev_slevy."','".$this->castka_slevy."','".$this->velikost_slevy."'  )";
			}else{
			$dotaz= "INSERT INTO `objednavka` 
							(`id_klient`,".$name_agentury."`id_serial`,`id_zajezd`,`datum_rezervace`,`rezervace_do`,
							`stav`,`pocet_osob`,`celkova_cena`,`zbyva_zaplatit`,`poznamky`,`security_code`,".$terminy."`pocet_noci`)
						VALUES
							 (".$this->id_klient.",".$val_agentury."".$this->id_serial.",".$this->id_zajezd.",'".Date("Y-m-d H:i:s")."','".$this->rezervace_do."',
							 ".$this->stav.",".$this->pocet_osob.",".$this->celkova_cena.",".$this->celkova_cena.",'".$this->poznamky."',
							 '".sha1(mt_rand().$this->id_klient)."',".$val_terminy."".$this->pocet_noci." )";
			}
			
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku == "create_objednavajici"){
			$dotaz= "INSERT INTO `user_klient` 
							(`jmeno`,`prijmeni`,`datum_narozeni`,`email`,`telefon`,`ulice`,`mesto`,`psc`)
						VALUES
							 ('".$this->jmeno."','".$this->prijmeni."','".$this->change_date_cz_en($this->datum_narozeni)."','".$this->email."',
							 '".$this->telefon."','".$this->ulice."','".$this->mesto."','".$this->psc."' )";
			//echo $dotaz;
			return $dotaz;			
			
		}else if($typ_pozadavku == "smluvni_podminky"){
			$dotaz= "Select * from `dokument` 
						where `id_dokument` = ".$this->id_smluvni_podminky."
						Limit 1
						";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku == "sablona_objednavka"){
			$dotaz= "Select * from `sablony` 
						where `id_sablony` = ".$this->id_sablony_objednavka."
						Limit 1
						";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku == "sablona_zobrazit"){
			$dotaz= "Select * from `sablony` 
						where `id_sablony` = ".$this->id_sablony_zobrazit."
						Limit 1
						";
			//echo $dotaz;
			return $dotaz;			
		}
	}	 
	
	function get_slevy_staly_klient(){
			$dotaz_slevy = "select * from `slevy` join
							`slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
							where `slevy_serial`.`id_serial` = ".$this->id_serial." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							and `slevy`.`sleva_staly_klient` = 1
							order by `slevy`.`castka` desc ";
							
			$dotaz_slevy_zajezd = "select * from `slevy` join
							`slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy`)
							where `slevy_zajezd`.`id_zajezd` = ".$this->id_zajezd." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							and `slevy`.`sleva_staly_klient` = 1
							order by `slevy`.`castka` desc";	
													
			//echo $dotaz_slevy;
			//trida pro odesilani dotazu
			$this->database = Database::get_instance();
			//ziskani slev z databaze	
			$data = $this->database->query($dotaz_slevy)
			 	or $this->chyba("Chyba p�i dotazu do datab�ze");									
			$data_zajezd = $this->database->query($dotaz_slevy_zajezd)
			 	or $this->chyba("Chyba p�i dotazu do datab�ze");				
			
			if( !$this->get_error_message() ){
				if(mysqli_num_rows($data) or  mysqli_num_rows($data_zajezd) ){//existuji takove slevy
					$pocet_slev = mysqli_num_rows($data) +  mysqli_num_rows($data_zajezd);
					
					$slevy = "
					<table class=\"rezervace_ceny\"  cellpadding=\"0\" cellspacing=\"0\">
						<tr>
							<th colspan=\"2\">SLEVY</th>
						</tr>
						<tr>
							<td class=\"nadpis_cena_objednavka\" colspan=\"2\">M�m n�rok na n�sleduj�c� slevy:</td>
						</tr>
						
						";
					$i=0;
					while( $zaznam = mysqli_fetch_array($data)){
						$slevy.= "<tr><td>".$zaznam["zkraceny_nazev"]." - ".$zaznam["castka"].$zaznam["mena"]."</td><td> <span class=\"red\">***</span> <input name=\"sleva_".$i."\" type=\"checkbox\" value=\"Po�adavek na: ".$zaznam["zkraceny_nazev"]."\"></td></tr>";
						$i++;
					}
					while( $zaznam = mysqli_fetch_array($data_zajezd)){
						$slevy.= "<tr><td>".$zaznam["zkraceny_nazev"]." - ".$zaznam["castka"].$zaznam["mena"]."</td><td> <span class=\"red\">***</span> <input name=\"sleva_".$i."\" type=\"checkbox\" value=\"Po�adavek na: ".$zaznam["zkraceny_nazev"]."\"></td></tr>";
						$i++;
					}	
					
					$slevy.="
					<tr>
						<td colspan=\"2\" style=\"border-top:1px solid navy;\"><input name=\"pocet_slev\" type=\"hidden\" value=\"".$i."\">
						<span class=\"red\">***</span> Za�krtn�te pros�m v�echny slevy, na kter� m�te n�rok. <br/>Slevy budou prov��eny CK SLAN tour a pot� ode�teny z celkov� ceny z�jezdu...</td>
					</tr>
				</table>";				
				}
			}
			return $slevy;
	}
	
	
	/**zobrazeni formulare pro prvni cast objednavky*/
		public static function show_form_kontaktni_informace(){
			GLOBAL $serial;
			if(!$_SESSION["id_klient"]){
				$povinny_email = "<span class=\"red\">*</span>";
			}else{
				$povinny_email = "";
			}			
			$klient = "<tr><th colspan=\"2\"><strong>Objedn�vaj�c�:</strong></th></tr>
							<tr><td>&nbsp;&nbsp;Jm�no: <span class=\"red\">*</span></td><td><input name=\"jmeno\" type=\"text\" value=\"".$_POST["jmeno"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;P��jmen�: <span class=\"red\">*</span></td><td><input name=\"prijmeni\" type=\"text\" value=\"".$_POST["prijmeni"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;Datum narozen�: <span class=\"red\">*</span></td><td><input name=\"datum_narozeni\" type=\"text\" value=\"".$_POST["datum_narozeni"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;E-mail: ".$povinny_email."</td><td><input name=\"email\" type=\"text\" value=\"".$_POST["email"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;Telefon:<span class=\"red\">*</span></td><td><input name=\"telefon\" type=\"text\" value=\"".$_POST["telefon"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;Ulice a �P: <span class=\"red\">*</span></td><td><input name=\"ulice\" type=\"text\" value=\"".$_POST["ulice"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;M�sto: <span class=\"red\">*</span></td><td><input name=\"mesto\" type=\"text\" value=\"".$_POST["mesto"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;PS�: <span class=\"red\">*</span></td><td><input name=\"psc\" type=\"text\" value=\"".$_POST["psc"]."\" /></td></tr>
			";
                        $i=0;
                        $dalsi_osoby="";
                        while( $i < $_SESSION["pocet_osob"] and $i < 100 ) {
                            $i++;					
                            //test na sudost/lichost zaznamu
                            if($i%2 == 0) {
                                $parita=" class=\"suda\"";
                            }else {
                                $parita=" class=\"licha\"";
                            }
                            //najdu minula data o osobach, pokud jsme u prvni osoby a nema nic vyplneneho, vypiseme jeji udaje
                            if($i == 1 and $_POST["jmeno_".$i]=="") {
                                $jmeno = $_POST["jmeno"];
                                $prijmeni = $_POST["prijmeni"];
                                $titul = $_POST["titul_".$i];
                                $email = $_POST["email"];
                                $telefon = $_POST["telefon"];				
                                $datum_narozeni = $_POST["datum_narozeni"];
                                $rodne_cislo = $_POST["rodne_cislo_".$i];
                                $cislo_pasu = $_POST["cislo_pasu_".$i];
                                $cislo_op = $_POST["cislo_op_".$i];
                                $ulice = $_POST["ulice"];
                                $mesto = $_POST["mesto"];
                                $psc = $_POST["psc"];
                            }else {
                                $jmeno = $_POST["jmeno_".$i];
                                $prijmeni = $_POST["prijmeni_".$i];
                                $titul = $_POST["titul_".$i];
                                $email = $_POST["email_".$i];
                                $telefon = $_POST["telefon_".$i];				
                                $datum_narozeni = $_POST["datum_narozeni_".$i];
                                $rodne_cislo = $_POST["rodne_cislo_".$i];
                                $cislo_pasu = $_POST["cislo_pasu_".$i];
                                $cislo_op = $_POST["cislo_op_".$i];
                                $ulice = $_POST["ulice_".$i];
                                $mesto = $_POST["mesto_".$i];
                                $psc = $_POST["psc_".$i];
                            }
                            $dalsi_osoby.="				
					<tr>
						<th colspan=\"6\">Osoba �. ".$i."</th>
					</tr>					
					<tr ".$parita.">
						<td colspan=\"6\"><strong>Vypl�te pot�ebn� �daje o p�ihl�en� osob�</strong></td>
					</tr>													
					<tr ".$parita.">
						<td>Jm�no: <span class=\"red\">*</span></td>		<td><input name=\"jmeno_".$i."\" type=\"text\" value=\"".$jmeno."\" /></td>
						<td>P��jmen�: <span class=\"red\">*</span></td>	<td><input name=\"prijmeni_".$i."\" type=\"text\" value=\"".$prijmeni."\" /></td>
						<td>Telefon:</td>		<td><input name=\"telefon_".$i."\" type=\"text\" value=\"".$telefon."\" /></td>
					</tr>
					<tr".$parita.">
                                                <td>Datum narozen�: </td>	<td><input name=\"datum_narozeni_".$i."\" type=\"text\" value=\"".$datum_narozeni."\" /></td>
						<td>Rodn� ��slo:</td>		<td><input name=\"rodne_cislo_".$i."\" type=\"text\" value=\"".$rodne_cislo."\" /></td>
						<td>��slo dokladu (OP / pas):</td> <td><input name=\"cislo_pasu_".$i."\" type=\"text\" value=\"".$cislo_pasu."\" /></td>
					</tr>
                            ";                            
                        }


			$poznamky = "<tr><td valign=\"top\"><strong>Pozn�mky:</strong></td><td><textarea name=\"poznamky\" type=\"text\"  cols=\"50\" rows=\"5\">".$_POST["poznamky"]."</textarea></td></tr>\n";

			$vystup="
                                <table>
                                   <tr>
                                      <td>
                                        <table class=\"sluzby\"  cellpadding=\"2\" cellspacing=\"2\" >
							".$klient.$poznamky."
                                        </table>
                                      </td><td>
                                        <table class=\"sluzby\"  cellpadding=\"2\" cellspacing=\"2\" >
						<tr><th colspan=\"2\"><strong>Objedn�vka - rekapitulace:</strong></th></tr>
                                                <tr><td>Z�jezd<td>".$_POST["nazev_zajezdu"]."
                                                <tr><td>Term�n<td>".$serial->change_date_en_cz($serial->get_termin_od())." - ".$serial->change_date_en_cz($serial->get_termin_do())."
                                                <tr><td>Slu�by<td>".$serial->get_ceny()->show_rekapitulace_objednavka()."
                                                <tr><td>Celkov� cena<td>".$serial->get_ceny()->get_celkova_castka()."
                                        </table>
                                      </td>
                                   </tr>
                                </table>

				<table class=\"sluzby\"  cellpadding=\"2\" cellspacing=\"2\">
							".$dalsi_osoby."
				</table>										
				<input type=\"submit\" name=\"kontaktni_informace\" value=\"Odeslat objedn�vku z�jezdu\" />

                        ";
				
			return $vystup;
			
		//}
	}
	
	/**zobrazeni formulare pro druhou cast objednavky*/
  function show_form_osoby(){
	 $core = Core::get_instance();
	 $adresa_rezervace = $core->get_adress_modul_from_typ("rezervace");
	 if( $adresa_rezervace !== false ){//musi existovat modul, ktery formular zpracuje
		//$uzivatel = User::get_instance();	
		$form = "
			
			<form action=\"".$this->get_adress(array($adresa_rezervace,"objednavka","osoby"))."\" method=\"post\" name=\"objednavka\">
		\n";
		$submit = "<input type=\"submit\" name=\"back\" value=\"&lt;&lt;Zp�t na 1. krok\" /><input type=\"submit\" value=\"Odeslat objedn�vku\" />\n";

		//vypisu seznam cen
		$ceny_objednavky = new Seznam_cen($this->id_serial, $this->id_zajezd );					
		$form_ceny = $ceny_objednavky->show_form_objednavka(1);
		
		
		
		//ziskani serialu z databaze	
		$zajezd = mysqli_fetch_array( $this->database->query($this->create_query("get_zajezd") ) )
		 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		//ziskani id_smluvnich podminek
		$this->id_smluvni_podminky = $zajezd["id_smluvni_podminky"];			
			//z�sk�n� dokumentu se smluvn�mi podm�nkami
			$data_smluvni_podminky=$this->database->query($this->create_query("smluvni_podminky"))
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$zaznam_smp = mysqli_fetch_array($data_smluvni_podminky);		
			
					
		$hidden = "
			<input name=\"jmeno\" type=\"hidden\" value=\"".$this->jmeno."\" />
			<input name=\"prijmeni\" type=\"hidden\" value=\"".$this->prijmeni."\" />
			<input name=\"datum_narozeni\" type=\"hidden\" value=\"".$this->datum_narozeni."\" />
			<input name=\"email\" type=\"hidden\" value=\"".$this->email."\" />
			<input name=\"telefon\" type=\"hidden\" value=\"".$this->telefon."\" />
			<input name=\"ulice\" type=\"hidden\" value=\"".$this->ulice."\" />
			<input name=\"mesto\" type=\"hidden\" value=\"".$this->mesto."\" />
			<input name=\"psc\" type=\"hidden\" value=\"".$this->psc."\" />
			<input name=\"novinky\" type=\"hidden\" value=\"".$this->novinky."\" />
			<input name=\"upresneni_terminu_od\" type=\"hidden\" value=\"".$this->upresneni_terminu_od."\" />
			<input name=\"upresneni_terminu_do\" type=\"hidden\" value=\"".$this->upresneni_terminu_do."\" />			
			
			<input name=\"id_serial\" type=\"hidden\" value=\"".$this->id_serial."\" />
			<input name=\"id_zajezd\" type=\"hidden\" value=\"".$this->id_zajezd."\" />
			";		
		if($_POST["zpusob_vyhodnoceni"]=="poznavaci"){
			$hidden .= "<input name=\"zpusob_vyhodnoceni\" type=\"hidden\" value=\"poznavaci\" />";
		}
		if($_POST["pocet_slev"] >= 1){
			$i=0;
				$hidden .= "<input name=\"pocet_slev\" type=\"hidden\" value=\"".$_POST["pocet_slev"]."\" />";
			while($i <= $_POST["pocet_slev"]){
				$hidden .= "<input name=\"sleva_".$i."\" type=\"hidden\" value=\"".$_POST["sleva_".$i]."\" />";
				$i++;
			}
		}
		
		$serial = "<tr><th valign=\"top\">Z�jezd:</th><th><strong>".$zajezd["nazev"]."</strong> (".$this->change_date_en_cz( $zajezd["od"] )." - ".$this->change_date_en_cz( $zajezd["do"] ).")</th></tr>\n";		
		$klient = "<tr><td valign=\"top\">Objedn�vaj�c�:</td><td><strong>".$this->prijmeni." ".$this->jmeno."</strong>; ".$this->change_date_en_cz( $this->datum_narozeni )."; ".$this->email."; ".$this->mesto.", ".$this->ulice.", ".$this->psc."</td></tr>\n";
		$pocet_osob = "<tr><td valign=\"top\">Po�et osob:</td><td>".$this->pocet_osob." <input name=\"pocet_osob\" type=\"hidden\" value=\"".$this->pocet_osob."\" /></td></tr>\n";			
		$poznamky = "<tr><td valign=\"top\">Pozn�mky:</td><td>".$this->poznamky." <input name=\"poznamky\" type=\"hidden\" value=\"".$this->poznamky."\" /></td></tr>\n";

		
			
		$vystup=$form.$hidden."
			<div style=\"float:left;\">
			<table class=\"rezervace\"  cellpadding=\"0\" cellspacing=\"0\" >
				".$serial.$klient.$pocet_osob.$poznamky."
			</table>
			<table class=\"rezervace_ceny\"  cellpadding=\"0\" cellspacing=\"0\">
				".$form_ceny."
			</table>		
			<table class=\"rezervace_osoby\">
		";
		
		//ziskani osob, ktere uz klient nekdy prihlasoval na zajezd
		$vytvorene_osoby = $this->database->query($this->create_query("get_drive_objednane_osoby") ) 
		 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		$pocet_vytvorenych_osob = mysqli_num_rows($vytvorene_osoby);
		
		$i=0;
		while( $i < $this->pocet_osob ){
			$i++;					
			//test na sudost/lichost zaznamu
			if($i%2 == 0){
				$parita=" class=\"suda\"";
				}else{
				$parita=" class=\"licha\"";
			}
			//najdu minula data o osobach, pokud jsme u prvni osoby a nema nic vyplneneho, vypiseme jeji udaje
				if($i == 1 and $_POST["jmeno_".$i]==""){
					$select_id_klient = $_POST["select_id_klient_".$i];
					$id_klient = $_POST["id_klient_".$i];
					$jmeno = $this->jmeno;
					$prijmeni = $this->prijmeni;
					$titul = $_POST["titul_".$i];
					$email = $this->email;
					$telefon = $this->telefon;				
					$datum_narozeni = $this->datum_narozeni;
					$rodne_cislo = $_POST["rodne_cislo_".$i];
					$cislo_pasu = $_POST["cislo_pasu_".$i];
					$cislo_op = $_POST["cislo_op_".$i];
					$ulice = $this->ulice;
					$mesto = $this->mesto;
					$psc = $this->psc;
				}else{
					$select_id_klient = $_POST["select_id_klient_".$i];
					$id_klient = $_POST["id_klient_".$i];
					$jmeno = $_POST["jmeno_".$i];
					$prijmeni = $_POST["prijmeni_".$i];
					$titul = $_POST["titul_".$i];
					$email = $_POST["email_".$i];
					$telefon = $_POST["telefon_".$i];				
					$datum_narozeni = $_POST["datum_narozeni_".$i];
					$rodne_cislo = $_POST["rodne_cislo_".$i];
					$cislo_pasu = $_POST["cislo_pasu_".$i];
					$cislo_op = $_POST["cislo_op_".$i];
					$ulice = $_POST["ulice_".$i];
					$mesto = $_POST["mesto_".$i];
					$psc = $_POST["psc_".$i];
				}
				
			//pokud existuji, vypisu osoby, ktere klient drive vytvoril
			if($pocet_vytvorenych_osob > 0){		
				if( mysqli_data_seek($vytvorene_osoby,0) ){//najdu zacatek dat				
				
					$select_vytvorene_osoby = "<select name=\"select_id_klient_".$i."\">
						<option value=\"\">---</option>";

					while( $osoba = mysqli_fetch_array($vytvorene_osoby) ){
						if($select_id_klient == $osoba["id_klient"]){
							$select_vytvorene_osoby .="<option value=\"".$osoba["id_klient"]."\" selected=\"selected\">
									".$osoba["id_klient"]." - ".$osoba["prijmeni"]." ".$osoba["jmeno"].", ".$this->change_date_en_cz($osoba["datum_narozeni"])."</option>";
						}else{
							$select_vytvorene_osoby .="<option value=\"".$osoba["id_klient"]."\">
									".$osoba["id_klient"]." - ".$osoba["prijmeni"]." ".$osoba["jmeno"].", ".$this->change_date_en_cz($osoba["datum_narozeni"])."</option>";						
						}
					}					
					$select_vytvorene_osoby .= "</select>";
					
					$table_vytvorene_osoby = 
						"<tr ".$parita.">
							<td colspan=\"4\"><strong><span class=\"red\">**</span> Nebo vyberte osobu, kterou jste ji� vytvo�il</strong> </td>
							<td colspan=\"2\">".$select_vytvorene_osoby."</td>
						</tr>	";	
				}else{
					$table_vytvorene_osoby = "";
				}

			}

			//u prvni osoby dam moznost prihlasit sam sebe
			if(0 /*$i == 1*/){
				if($_POST["checkbox_id_klient_".$i] != "" ){
					$checkbox_prvni_osoba = "<input type=\"checkbox\" name=\"checkbox_id_klient_".$i."\" value=\"1\" checked=\"checked\"  />";
				}else{
					$checkbox_prvni_osoba = "<input type=\"checkbox\" name=\"checkbox_id_klient_".$i."\" value=\"1\"/>";				
				}
				$prvni_osoba="
					<tr ".$parita.">
						<td colspan=\"2\">".$checkbox_prvni_osoba."</td>
					</tr>	";		
				$text_osoby="Nebo vypl�te pot�ebn� �jdaje o p�ihl�en� osob�:";		
			}else{
				$prvni_osoba="";
				$text_osoby="Vypl�te pot�ebn� �jdaje o p�ihl�en� osob�:";	
			}

					/*
					<tr ".$parita.">
						<td colspan=\"4\"><strong><span class=\"red\">**</span> Napi�te Id vybran� osoby</strong>  </td>
						<td colspan=\"2\"><input name=\"id_klient_".$i."\" type=\"text\" value=\"".$id_klient."\" /></td>
					</tr>		
					*/
			$vystup=$vystup."				
					<tr>
						<th colspan=\"6\">Osoba �. ".$i."</th>
					</tr>
					".$prvni_osoba."
					
					".$table_vytvorene_osoby."						
					<tr ".$parita.">
						<td colspan=\"6\"><strong>".$text_osoby."</strong></td>
					</tr>													
					<tr ".$parita.">
						<td>Jm�no: <span class=\"red\">*</span></td>		<td><input name=\"jmeno_".$i."\" type=\"text\" value=\"".$jmeno."\" /></td>
						<td>P��jmen�: <span class=\"red\">*</span></td>	<td><input name=\"prijmeni_".$i."\" type=\"text\" value=\"".$prijmeni."\" /></td>
						<td>Titul:</td>		<td><input name=\"titul_".$i."\" type=\"text\" value=\"".$titul."\" /></td>
					</tr>
					<tr".$parita.">	
						<td>E-mail: </td>		<td><input name=\"email_".$i."\" type=\"text\" value=\"".$email."\" /></td>
						<td>Telefon:</td>		<td><input name=\"telefon_".$i."\" type=\"text\" value=\"".$telefon."\" /></td>				
						<td>Datum narozen�: </td>	<td><input name=\"datum_narozeni_".$i."\" type=\"text\" value=\"".$datum_narozeni."\" /></td>
					</tr>
					<tr".$parita.">						
						<td>Rodn� ��slo:</td>		<td><input name=\"rodne_cislo_".$i."\" type=\"text\" value=\"".$rodne_cislo."\" /></td>
						<td>��slo dokladu:</td>			<td colspan=\"3\"><input name=\"cislo_pasu_".$i."\" type=\"text\" value=\"".$cislo_pasu."\" /> (ob�ansk� pr�kaz nebo pas)</td>
					</tr>
					<tr".$parita.">
						<td>M�sto: </td>		<td><input name=\"mesto_".$i."\" type=\"text\" value=\"".$mesto."\" /></td>						
						<td>Ulice a �P: </td>	<td><input name=\"ulice_".$i."\" type=\"text\" value=\"".$ulice."\" /></td>
						<td>PS�: </td>			<td><input name=\"psc_".$i."\" type=\"text\" value=\"".$psc."\" /></td>
					</tr>
				";
		}
		$vystup=$vystup."		
			</table>
			</div>
			<div class=\"resetovac\">&nbsp;</div>
			<input type=\"hidden\" name=\"pocet_klientu\" value=\"".$i."\" />\n
			".$submit."
			
					<p><span class=\"red\">*</span> - polo�ky ozna�en� hv�zdi�kou je t�eba vyplnit.</p>
					
						
					<h3>Smluvn� podm�nky</h3>
					<p>Odesl�n�m objedn�vky souhlas�te se smluvn�mi podm�nkami CK SLAN tour: <a href=\"/".ADRESAR_DOKUMENT."/".$zaznam_smp["dokument_url"]."\" target=\"_blank\" title=\"".$zaznam_smp["popisek_dokument"]."\">".$zaznam_smp["nazev_dokument"]."</a></p>					
					
					<h3>Co se stane po odesl�n�?</h3>
					<p>Po odesl�n� formul��e syst�m zkontroluje kapacity slu�eb, o kter� m�te z�jem. Pokud jsou voln�, provede �asov� omezenou rezervaci z�jezdu.<br/>
					 Pracovn�ci CK SLAN tour objedn�vku zkontroluj� a budou V�s d�le informovat o zp�sobech platby a p��padn� dal��ch podrobnostech.								
					<br/>M�m z�jem o zas�l�n� aktu�ln�ch nab�dek CK: <input type=\"checkbox\" name=\"novinky\" checked=\"checked\" value=\"ano\"/></p>
			</form>
			";

		return $vystup;
	 }//if adresa_rezervace !== false
	}	

	/**kontrola zda mam odpovidajici data pro tabulku objednavka*/
	function correct_data(){
		$ok = 1;
		if($this->typ_pozadavku == "osoby" or $this->typ_pozadavku == "odeslat"){
		//kontrolovan� data: n�zev seri�lu, popisek,  id_typ, 
			/*if(!Validace::int_min($this->id_klient,1) ){
				$ok = 0;
				$this->chyba("Klient nen� p�ihl�en!");
			}		*/
			if($this->zajezd_info["dlouhodobe_zajezdy"]){
				if(!Validace::datum_cz($this->upresneni_terminu_od) ){
					$ok = 0;
					$this->chyba("U dlouhodob�ho z�jezdu je t�eba up�esnit po�adovan� datum odjezdu ve form�tu dd.mm.rrrr");
				}
				if(!Validace::datum_cz($this->upresneni_terminu_do) ){
					$ok = 0;
					$this->chyba("U dlouhodob�ho z�jezdu je t�eba up�esnit po�adovan� datum n�vratu ve form�tu dd.mm.rrrr");

				}	
			/*kontrola upresneni terminu*/
				if($this->upresneni_terminu_od !="" and $this->upresneni_terminu_od!="0000-00-00" and $this->upresneni_terminu_do!="" and $this->upresneni_terminu_do!="0000-00-00"){
			 		$pole_upresneni_od=explode("-", $this->change_date_cz_en($this->upresneni_terminu_od));
					$pole_upresneni_do=explode("-", $this->change_date_cz_en($this->upresneni_terminu_do));
			 		$pole_od=explode("-", $this->zajezd_od);
					$pole_do=explode("-", $this->zajezd_do); 		

	
			 		$time_upresneni_od = mktime(0,0,0,$pole_upresneni_od[1],$pole_upresneni_od[2],$pole_upresneni_od[0]);
					$time_upresneni_do = mktime(0,0,0,$pole_upresneni_do[1],$pole_upresneni_do[2],$pole_upresneni_do[0]);				
			 		$time_od = mktime(0,0,0,$pole_od[1],$pole_od[2],$pole_od[0]);
					$time_do = mktime(0,0,0,$pole_do[1],$pole_do[2],$pole_do[0]);			

					if($time_upresneni_od < $time_od or $time_upresneni_do > $time_do){
						$ok = 0;
						$this->chyba("Up�esn�n� term�nu je mimo rozsah dan�ho z�jezdu (nelze garantovat cenu a kapacitu slu�eb): up�esn�te term�n v rozmez� od ".$this->change_date_en_cz($this->zajezd_od)." do ".$this->change_date_en_cz($this->zajezd_do).", nebo zvolte jin� z�jezd (nebo jin� term�nov� rozsah st�vaj�c�ho z�jezdu).");						
					}	
					if($time_upresneni_od > $time_upresneni_do){
						$ok = 0;
						$this->chyba("Datum odjezdu je zvoleno a� po datu n�vratu!");						
						
					}
				}							
			}
			if(!Validace::text($this->jmeno) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit Va�e jm�no!");
			}
			if(!Validace::text($this->prijmeni) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit Va�e p��jmen�!");
			}
			if(!Validace::text($this->datum_narozeni) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit Va�e datum narozen�!");
			}
			if(!Validace::text($this->ulice) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit Va�i adresu - ulici a ��slo popisn�");
			}
			if(!Validace::text($this->mesto) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit Va�i adresu - m�sto");
			}
			if(!Validace::text($this->psc) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit Va�i adresu - ps�");
			}
			if(!Validace::text($this->telefon) ){
				$ok = 0;
				$this->chyba("Je t�eba vyplnit V� telefon");
			}
			if(!$_SESSION["id_klient"]){
				if(!Validace::email($this->email) ){
					$ok = 0;
					$this->chyba("Email nen� spr�vn� vypln�n!");
				}				
			}		
			if(!Validace::int_min_max($this->pocet_osob,1,MAX_OSOB) ){
				$ok = 0;
				$this->chyba("Po�et osob nen� v intervalu 1 - ".MAX_OSOB."!");
			}
			if(!Validace::int_min_max($this->pocet_cen,1,MAX_CEN) ){
				$ok = 0;
				$this->chyba("Po�et slu�eb nen� v intervalu 1 - ".MAX_CEN."!");
			}			
		}																	
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}		
	
	function get_pocet_cen(){return $this->pocet_cen;}
	function get_pocet_osob(){return $this->pocet_osob;}
} 

?>
