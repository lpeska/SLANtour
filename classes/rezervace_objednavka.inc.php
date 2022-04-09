<?php
/** 
* trida pro obslouzeni prichoziho formulare s objedn�vkou z�jezdu
* 	- zpracov�n� objedn�vky m� 2 f�ze
*	- po uspesne druhe fazi je objednavka ulozena do databaze
* 	- odesle se e-mail tvurci serialu, na centralni e-mail systemu a potvrzeni klientovi
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_objednavka extends Generic_data_class{
    const ID_PLATBA_HLAVNI = 24;
    const ID_PLATBA_HOTOVE = 32;
    const ID_PLATBA_PREVODEM = 35;
    const ID_PLATBA_SLOVENSKO = 36;
    const ID_PLATBA_SLOZENKOU = 33;
    const ID_PLATBA_KARTOU = 34;
    
        private $provizni_koeficient;
	private $array_ceny;
	private $id_ceny; //pole id_cen
	private $pocet_ceny; //pole poctu objednavanych kapacit jednotlivych cen
	private $text_ceny; //vypis cen do e-mailu
	private $text_ceny_klient; //vypis cen do e-mailu	
	private $zajezd_info;
	private $id_objednavka;
        private $current_cena;
        private $klient_email;
        private $castka_k_zaplaceni;
        
	private $array_osoby;
	private $new_clients; //pole dotazu na vytvoreni novych klientu
	private $id_clients; //pole id klientu
	private $text_klienti; //vypis klient� do e-mailu
	private $text_ucastnici_klient;

	private $stav;
	private $celkova_cena;
	private $rezervace_do;
	private $potvrzovaci_hlaska; //hlaska pro potvrzeni objednavky (ruzna v zavislosti na dostupnosti kapacit)
	private $varovna_zprava; //objekt VarovnaZprava s hlaskou o nejakem problemu v objednavce (black days/vyprodana kapacita...)
	private $upresneni_terminu_od;
	private $upresneni_terminu_do;
	private $pocet_noci;
	
	//vstupni data
	private $typ_pozadavku;	
	private $id_serial;
	private $id_zajezd;
        private $id_agentury;
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
        private $provize;
	
	private $cislo_ceny;
	private $cislo_osoby;	
        private $query_tok;
        private $query_tok_kapacity;
        private $castka_ceny;
        private $mena_ceny;
        private $use_pocet_noci_ceny;   
        private $src_web;
        
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
	function __construct($typ_pozadavku, $id_serial, $id_zajezd, $upresneni_terminu_od, $upresneni_terminu_do){

		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//$uzivatel = User::get_instance();		
		//$this->id_klient = $uzivatel->get_id();
		$this->typ_pozadavku = $this->check($typ_pozadavku);
                
		$this->id_serial = $this->check_int($id_serial);
		$this->id_zajezd = $this->check_int($id_zajezd);

		$this->upresneni_terminu_od = $this->check($upresneni_terminu_od);
		$this->upresneni_terminu_do = $this->check($upresneni_terminu_do);
                
                
                //pokud je klient prihlaseny, stahneme si od nej info o CA
		if($_SESSION["id_klient"]!=""){
			$this->objednavajici_ca = $_SESSION["id_klient"];	
		}else{
			$this->objednavajici_ca = 0;
			/*$this->chyba("U�ivatel nen� spr�vn� p�ihl�en!");*/
		}
                
                //echo $this->upresneni_terminu_od;

		//zkontroluju data o uzivateli (chybove hlasky jsou primo ve funkci correct_data() )
		//$this->correct_data();
		
		$this->id_ceny = array();
		$this->pocet_ceny = array();

                $this->id_vstup_v_cene = array();
                $this->pocet_vstup_v_cene = array();
                $this->kategorie_vstup_v_cene = array();
                $this->cislo_vstupenky_v_cene = 0;

                $this->id_vstup_k_doobjednani = array();
                $this->pocet_vstup_k_doobjednani = array();
                $this->kategorie_vstup_k_doobjednani = array();
                $this->cislo_vstupenky_k_doobjednani = 0;

		$this->pocet_vstupenek = array();
                $this->vyplnene_vstupenky = 0;

		$this->cislo_ceny = 0;		
		$this->text_ceny="";
		$this->text_ceny_klient="";	
		
		$this->array_osoby = array();
		$this->new_clients = array();
		$this->id_clients = array();
                $this->query_tok = array();
                $this->query_tok_kapacity = array();                    
		$this->cislo_osoby = 0;		
		$this->odjezd_misto_exist = 0;
		$this->text_klient="";
                //echo "osoby".$_POST["pocet_osob"];
                //print_r($_SESSION);
                //print_r($_POST);
                //print_r($this);
                if($this->typ_pozadavku=="finish"){
                    
                    //nahravam zajezd
                    $this->add_to_query_zajezd($this->id_serial,$this->id_zajezd,$_POST["nazev_ubytovani_web"]);                    
                    //nahravam ceny
                    $i=0;                                        
                    while($i <= 100 and $i <= $_POST["pocet_cen"]){
                        //spocitam provizi                        
                        $this->add_to_query_cena_poznavaci($_POST["id_cena_".$i],$_POST["cena_pocet_".$i],$_POST["typ_ceny_".$i]);
                        $i++;
                    }
                    //nahravam vstupenky
                   
                    //nahravam objednavajiciho
                        $this->jmeno = $this->check($_POST["jmeno"]);
                        $this->prijmeni = $this->check($_POST["prijmeni"]);
                        $this->datum_narozeni = $this->check($_POST["datum_narozeni"]);
                        $this->email = $this->check($_POST["email"]);
                        
                        $this->telefon = $this->check($_POST["telefon"]);
                        $this->ulice = $this->check($_POST["ulice"]);
                        $this->mesto = $this->check($_POST["mesto"]);
                        $this->psc = $this->check($_POST["psc"]);
                        $this->poznamky = $this->check($_POST["poznamky"]);
                        $this->novinky = $this->check($_POST["novinky"]);
                        $this->rodne_cislo = $this->check($_POST["rodne-cislo"]);
                        $this->cislo_dokladu = $this->check($_POST["cislo-dokladu"]);
                        //print_r($_POST);
                        
                        if($_POST["src_web"]!=""){
                            $this->src_web = "Objedn�vka z webu ".$this->check($_POST["src_web"]);
                        }
                        $this->objednavajici_je_ucastnik = $this->check_int($_POST["objednavajici_je_ucastnik"]);
                    //nahravam dalsi osoby
                    $i=1;
                    while($i <= 100 and $i <= $_SESSION["pocet_osob"]){
                        // echo $_POST["datum_narozeni_".$i];
                        $this->add_to_query_osoby(                                 
				$_POST["checkbox_id_klient_".$i],$_POST["select_id_klient_".$i],$_POST["id_klient_".$i],
				$_POST["jmeno_".$i],$_POST["prijmeni_".$i],$_POST["titul_".$i],$_POST["email_".$i],$_POST["telefon_".$i],
				$_POST["datum_narozeni_".$i],$_POST["rodne_cislo_".$i],$_POST["cislo_pasu_".$i],$_POST["cislo_op_".$i],
				$_POST["ulice_".$i],$_POST["mesto_".$i],$_POST["psc_".$i]
			);
                        $i++;
                    }
                    
                    $this->finish();
                }
	}	


	function add_to_query_zajezd($id_serial,$id_zajezd,$nazev_ubytovani){
		//kontrola vstupnich dat
		$this->id_serial = $this->check_int($id_serial);
		$this->nazev_ubytovani_web = $this->check($nazev_ubytovani);
                $this->pocet_osob = $_POST["pocet_osob"];
		$zajezd = mysqli_fetch_array( $this->database->transaction_query($this->create_query("get_zajezd") ) )
		 	or $this->chyba("Chyba p�i dotazu do datab�ze z�jezd: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );                
                $this->id_zajezd = $zajezd["id_zajezd"];                
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
                        
                        
                        //zkontroluju, zda je k cene pridana objektova kategorie
                        $sql = "select * from `cena_zajezd_tok`
                                    join `objekt_kategorie_termin` 
                                        on (`cena_zajezd_tok`.`id_termin`=`objekt_kategorie_termin`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie`=`objekt_kategorie_termin`.`id_objekt_kategorie`)
                                    join `objekt_kategorie` 
                                        on (`cena_zajezd_tok`.`id_objekt_kategorie`=`objekt_kategorie`.`id_objekt_kategorie`)
                                    join `cena_objekt_kategorie` 
                                        on (`cena_objekt_kategorie`.`id_objekt_kategorie`=`objekt_kategorie`.`id_objekt_kategorie` and `cena_zajezd_tok`.`id_cena`=`cena_objekt_kategorie`.`id_cena`)    
                                    join `objekt` 
                                        on (`objekt_kategorie`.`id_objektu`=`objekt`.`id_objektu`)    
                                        
                                where `cena_zajezd_tok`.`id_cena` = ".$id_cena." and `id_zajezd` = ".$this->id_zajezd."        
                                 order by  `kapacita_volna` desc  limit 1";
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                        while ($row = mysqli_fetch_array($data)) {
                            //je treba zjistit zda pocet mam delit hlavni kapacitou
                            if($row["prodavat_jako_celek"] == 0){
                                //prodava se po lidech, je treba to vydelit hlavni kapacitou
                                //je otazka zda pouzit horno nebo dolni celou cast
                                $pocet_tok = ceil($pocet/$row["hlavni_kapacita"]);
                            }else{
                                $pocet_tok = $pocet;
                            }
                            if($row["typ_objektu"]==1){
                                echo "<pre>"; var_dump($row); echo "</pre>";
                                $this->ubytovani = $row["nazev_objektu"].", ".$row["nazev"];
                            }
                            //vytvorime nove propojeni mezi objednavkou a TOKem
                            $this->query_tok[] = "INSERT INTO `objednavka_tok`(`id_objednavka`, `id_termin`, `id_objekt_kategorie`, `pocet`) VALUES ([id_objednavka],".$row["id_termin"].",".$row["id_objekt_kategorie"].",".$pocet_tok.")";
                            //existuje objektova kategorie prirazena k aktualnimu zajezdu a teto cene, odecteme od ni kapacitu (toto chce dale delat inteligentneji)
                            $this->query_tok_kapacity[] = "UPDATE `objekt_kategorie_termin` SET `kapacita_volna`=".($row["kapacita_volna"] - $pocet_tok)." WHERE `id_termin`=".$row["id_termin"]." and `id_objekt_kategorie`=".$row["id_objekt_kategorie"]." and `id_objektu`=".$row["id_objektu"]."";
                        }                         
		}//if legal_data
	}

/**prijima informace o jednotlivych sluzbach a sestavuje z nich ��sti dotazu do datab�ze*/
	function add_to_query_vstupenka_v_cene($id_vstupenky,$pocet,$kategorie,$cena){
		//kontrola vstupnich dat
		$id_vstupenky = $this->check_int($id_vstupenky);
		$pocet = $this->check_int($pocet);
		$kategorie = $this->check_int($kategorie);
                $cena = $this->check_int($cena);
		//pokud jsou vporadku data, vytvorim danou cast dotazu
		if($this->legal_data_vstupenky($id_vstupenky,$pocet,$kategorie)){
			$this->cislo_vstupenky_v_cene++;
			$this->id_vstup_v_cene[ $this->cislo_vstupenky_v_cene ] = $id_vstupenky;
                        $this->cena_vstup_v_cene[ $this->cislo_vstupenky_v_cene ] = $cena;
			$this->pocet_vstup_v_cene[ $this->cislo_vstupenky_v_cene ] = $pocet;
                        $this->kategorie_vstup_v_cene[ $this->cislo_vstupenky_v_cene ] = $kategorie;
		}//if legal_data
	}
/**prijima informace o jednotlivych sluzbach a sestavuje z nich ��sti dotazu do datab�ze*/
	function add_to_query_vstupenka_k_doobjednani($id_vstupenky,$pocet,$kategorie){
		//kontrola vstupnich dat
		$id_vstupenky = $this->check_int($id_vstupenky);
		$pocet = $this->check_int($pocet);
		$kategorie = $this->check_int($kategorie);

		//pokud jsou vporadku data, vytvorim danou cast dotazu
		if($this->legal_data_vstupenky($id_vstupenky,$pocet,$kategorie)){
			$this->cislo_vstupenky_k_doobjednani++;
			$this->id_vstup_k_doobjednani[ $this->cislo_vstupenky_k_doobjednani ] = $id_vstupenky;
			$this->pocet_vstup_k_doobjednani[ $this->cislo_vstupenky_k_doobjednani ] = $pocet;
                        $this->kategorie_vstup_k_doobjednani[ $this->cislo_vstupenky_k_doobjednani ] = $kategorie;
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
                                $cislo_pasu = $this->cislo_dokladu;
                                $rodne_cislo = $this->rodne_cislo;
				
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
                                
                        //prvni osoba je zaroven objednavajici zajezdu - vytvorim update dotaz        
                        }else if($this->cislo_osoby==1 and $this->objednavajici_je_ucastnik ==1){
                                $this->new_clients[ $this->cislo_osoby ] = "UPDATE `user_klient` set `rodne_cislo`='".$this->rodne_cislo."', `cislo_pasu`='".$this->cislo_dokladu."'
                                                                            WHERE `id_klient`=";
                                $create_new_client = 0;
                                $this->array_osoby[$this->cislo_osoby] = array("jmeno" => $jmeno, "prijmeni" => $prijmeni, "titul" => $titul,
				"email" => $email, "telefon" => $telefon, "datum_narozeni" => $datum_narozeni, "rodne_cislo" => $this->rodne_cislo, 
				"cislo_pasu" => $this->cislo_dokladu, "cislo_op" => $cislo_op, "ulice" => $this->ulice, "mesto" => $this->mesto, "psc" => $this->psc);
			}else{
				$create_new_client = 1;
			}
			
			//ukladam do seznamu osob
			
			
			//je-li treba, vytvarim pole pro tvorbu novych klientu
			if($create_new_client == 1){
				$this->new_clients[ $this->cislo_osoby ] = "INSERT INTO `user_klient` (`jmeno`,`prijmeni`,`titul`,`email`,`telefon`,`datum_narozeni`,`rodne_cislo`,
						`cislo_pasu`,`cislo_op`,`ulice`,`mesto`,`psc`,`vytvoren_klientem`) 
						VALUES ('".$jmeno."','".$prijmeni."','".$titul."','".$email."','".$telefon."','".$datum_narozeni."',
						'".$rodne_cislo."','".$cislo_pasu."','".$cislo_op."','".$ulice."','".$mesto."','".$psc."',
						1)";
                            $this->array_osoby[$this->cislo_osoby] = array("jmeno" => $jmeno, "prijmeni" => $prijmeni, "titul" => $titul,
				"email" => $email, "telefon" => $telefon, "datum_narozeni" => $datum_narozeni, "rodne_cislo" => $rodne_cislo, 
				"cislo_pasu" => $cislo_pasu, "cislo_op" => $cislo_op, "ulice" => $ulice, "mesto" => $mesto, "psc" => $psc);    
			}
			
		}//if legal_data
	}


	/** funkce pro fin�ln� zpracov�n� 2. ��sti formul��e pro objedn�vku z�jezdu
	* - zkontroluje, zda lze zarezervovat kapacity
	* - po prijmuti vsech dat vytvori cely dotaz a odesle ho do datab�ze
	* - vytvo�� e-maily s potvrzen�m objedn�vky
	*/
        function finish() {
            //echo "finish ".$this->get_error_message();
            if (!$this->get_error_message() or 1) {

                $this->database->start_transaction();

                $this->stav = 2;
                $this->celkova_cena = 0;
                //ziskani serialu z databaze
                $zajezd = mysqli_fetch_array($this->database->transaction_query($this->create_query("get_zajezd")))
                        or $this->chyba("Chyba p�i dotazu do datab�ze z�jezd: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                
                if ($zajezd["id_sablony_zobrazeni"]=="12" and $zajezd["nazev_ubytovani"] != "") {
                    $nazev_zajezd = $zajezd["nazev_ubytovani"] . " - " . $zajezd["nazev"];
                }else{
                    $nazev_zajezd = $zajezd["nazev"];
                }
                $this->doprava = Serial_library::get_typ_dopravy($zajezd["doprava"]-1);
                $this->stravovani = Serial_library::get_typ_stravy($zajezd["strava"]-1);
                

                if ($this->objednavajici_ca) {
                    $agentura = mysqli_fetch_array($this->database->transaction_query($this->create_query("get_agentura")))
                            or $this->chyba("Chyba p�i dotazu do datab�ze agentura: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                            $this->provizni_koeficient = $agentura["provizni_koeficient"];
                        
                }else {
                            $this->provizni_koeficient = 1;
                }
                //ziskani maximalni slevu                
                $data_slevy = $this->database->transaction_query($this->create_query("get_sleva"))
                        or $this->chyba("Chyba p�i dotazu do datab�ze sleva: " . $this->create_query("get_sleva") . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                //ziskani jednotlivych cen
                // echo $this->create_query("get_ceny");
                $data_ceny = $this->database->transaction_query($this->create_query("get_ceny"))
                        or $this->chyba("Chyba p�i dotazu do datab�ze ceny: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                //ziskani blackdays
                $data_blackdays = $this->database->transaction_query($this->create_query("get_blackdays"))
                        or $this->chyba("Chyba p�i dotazu do datab�ze blackdays: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                $centralni_data = $this->database->query($this->create_query("get_centralni_data"))
                        or $this->chyba("Chyba p�i dotazu do datab�ze central data: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                //nacteme centralni data do pole
                while ($row = mysqli_fetch_array($centralni_data)) {
                    $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
                    $this->centralni_data[$row["nazev"]] = $row["text"];
                }

                $this->dotaz_slevy = "";
                if (mysqli_num_rows($data_slevy) >= 1) {
                    $slevy = mysqli_fetch_array($data_slevy);

                    $this->nazev_slevy = $slevy["nazev_slevy"];
                    $this->castka_slevy = $slevy["castka"] . " " . $slevy["mena"];
                    if ($slevy["nazev_slevy"] != "") {
                        if ($slevy["mena"] == "%") {
                            $velikost_slevy = 0;
                            $count_velikost_slevy = 1;
                            $mena_slevy = "%";
                        } else {
                            $velikost_slevy = round($slevy["castka"] * $this->pocet_osob);
                            $mena_slevy = "K�/osoba";
                            $count_velikost_slevy = 0;
                        }
                        $this->dotaz_slevy = "INSERT INTO `objednavka_sleva` 
							(`id_objednavka`,`nazev_slevy`,`velikost_slevy`,`mena`,`castka_slevy`)
						VALUES
							([id_objednavka],\"".$this->nazev_slevy."\",".$slevy["castka"].",\"".$mena_slevy."\",[velikost_slevy] )";
                    } else {
                        $count_velikost_slevy = 0;
                        $velikost_slevy = 0;
                    }
                    
                    
                    
                } else {
                    $count_velikost_slevy = 0;
                    $velikost_slevy = 0;
                }
                $vyprodano = 0;
                $na_dotaz = 0;
                $obsazena_kapacita = 0;
                $objednano_blackdays = 0;
                $this->text_ceny = "";
                $this->text_ceny_klient = "";
                $update_kapacity = array(); //pole pro pripadne dotazy se zmenou volne kapacity cen
                //vypocitam pocet noci
                $this->pocet_noci = $this->calculate_pocet_noci($zajezd["od"], $zajezd["do"], $this->change_date_cz_en($_POST["upresneni_terminu_od"]), $this->change_date_cz_en($_POST["upresneni_terminu_do"]));

                //slevy pro stale klienty
                if ($_POST["pocet_slev"] >= 1) {
                    $i = 0;
                    $this->poznamky .= "\n <strong>DAL�� PO�ADAVKY:</strong>\n";
                    while ($i <= $_POST["pocet_slev"]) {
                        $this->poznamky .= $this->check_slashes($_POST["sleva_" . $i]) . "\n";
                        $i++;
                    }
                }
                $this->provize = 0;
                $this->zpusob_platby = $this->check($_REQUEST["zpusob_platby"]);
                if($this->zpusob_platby == EnumPaymentMethods::METHOD_CARD_ALL){
                   $this->zpusob_platby = "kartou" ;
                }
                if($this->zpusob_platby == Rezervace_objednavka::ID_PLATBA_HOTOVE){
                   $this->zpusob_platby = "hotove" ;
                }
                if($this->zpusob_platby == Rezervace_objednavka::ID_PLATBA_KARTOU){
                   $this->zpusob_platby = "kartou" ;
                }
                if($this->zpusob_platby == Rezervace_objednavka::ID_PLATBA_PREVODEM){
                   $this->zpusob_platby = "prevodem" ;
                }
                if($this->zpusob_platby == Rezervace_objednavka::ID_PLATBA_SLOVENSKO){
                   $this->zpusob_platby = "prevodem_sk" ;
                }
                if($this->zpusob_platby == Rezervace_objednavka::ID_PLATBA_SLOZENKOU){
                   $this->zpusob_platby = "slozenkou" ;
                }
                
                $terminy_blackdays = "";
                $first_blackdays = 1;
                /* ----------------------------kontrola blackdays-------------------------------- */
                while ($blackdays = mysqli_fetch_array($data_blackdays)) {
                    $objednano_blackdays = 1;

                    if (strtotime($blackdays["od"]) > strtotime($this->upresneni_terminu_od)) {
                        $termin_od = $blackdays["od"];
                    } else {
                        $termin_od = $this->change_date_cz_en($this->upresneni_terminu_od);
                    }
                    if (strtotime($blackdays["do"]) < strtotime($this->upresneni_terminu_do)) {
                        $termin_do = $blackdays["do"];
                    } else {
                        $termin_do = $this->change_date_cz_en($this->upresneni_terminu_do);
                    }

                    if ($first_blackdays) {
                        $first_blackdays = 0;
                        $terminy_blackdays .= $this->change_date_en_cz_short($termin_od) . " - " . $this->change_date_en_cz($termin_do);
                    } else {
                        $terminy_blackdays .=", " . $this->change_date_en_cz_short($termin_od) . " - " . $this->change_date_en_cz($termin_do);
                    }
                    //tady je pak mozny vypsat treba do mailu terminy black days                    
                }
                /* ----------------------------kontrola cen-------------------------------- */
                //vyhrazeni kapacity cen
                $last_typ_ceny="";
                while ($ceny = mysqli_fetch_array($data_ceny)) {
                    $cislo_ceny = array_search($ceny["id_cena"], $this->id_ceny);
                    //vycleneni kapacit provadim pouze pro specifikovane ceny
                    if ($cislo_ceny !== false) {
                        $pocet = $this->pocet_ceny[$cislo_ceny];
                        
                        if ($pocet != 0) {
                            $this->castka_ceny[$cislo_ceny] = $ceny["castka"];
                            $this->mena_ceny[$cislo_ceny] ="K�";
                            $this->use_pocet_noci_ceny[$cislo_ceny] = $ceny["use_pocet_noci"];
                            
                            //pridam do celkove ceny
                            $cena_sluzby = $this->calculate_prize($ceny["castka"], $pocet, $this->pocet_noci, $ceny["use_pocet_noci"]);

                            //pridam castku do slevy - zde musi byt udaj v %!!! pouze pro sluzby (ne priplatky aj)
                            if ($count_velikost_slevy and (intval($ceny["typ_ceny"]) == 1 or intval($ceny["typ_ceny"]) == 2)) {
                                $velikost_slevy = $velikost_slevy + ($cena_sluzby * $slevy["castka"] / 100);
                            }
                            if ($zajezd["typ_provize"] == "3") {
                                //echo "spravny typ zajezdu - provize = 3\n";
                                //provize dle sluzeb
                                if ($ceny["typ_provize"] == 2) {
                                   // echo "spravny provize ceny v %,\n ";
                                    //procentuelni
                                    $this->provize += ($cena_sluzby * $ceny["vyse_provize"] / 100);
                                } else {
                                   // echo "spravny provize ceny v K�,\n ";
                                    $this->provize += ($ceny["vyse_provize"] * $pocet);
                                }
                            }
                            if($last_typ_ceny != $ceny["typ_ceny"]){
                                if($last_typ_ceny==""){
                                    $this->text_ceny_klient .= "
					<tr >
						<th  align=\"left\" >".$this->name_of_typ_ceny($ceny["typ_ceny"])."</th>
						<th  align=\"right\" >Cena</th>
						<th  align=\"right\" >Po�et</th>
                                                <th  align=\"right\" >Celkem</th>
					</tr>
                                    ";
                                }else{
                                    $this->text_ceny_klient .= "
					<tr >
						<th  align=\"left\" >".$this->name_of_typ_ceny($ceny["typ_ceny"])."</th>
						<th  align=\"right\" ></th>
						<th  align=\"right\" ></th>
                                                <th  align=\"right\" ></th>
					</tr>
                                    ";
                                }
				$last_typ_ceny = $ceny["typ_ceny"];
				
                            }
                            //upravim textovou informaci o objednavanych kapacitach (do e-mailu)
                            $this->text_ceny .= "<tr><td>" . $ceny["nazev_ceny"] . "</td><td>" . $ceny["castka"] . " " . $ceny["mena"] . "</td><td>" . $pocet . "</td></tr>";
                            $this->text_ceny_klient .="<tr>
                                                                                    <td style=\"padding-right:10px;\">" . $ceny["nazev_ceny"] . "</td><td align=\"right\" style=\"width:75px;\">" . $ceny["castka"] . " " . $ceny["mena"] . "</td><td align=\"right\" style=\"width:75px;\">" . $pocet . "</td><td align=\"right\"  style=\"width:90px;\">" . $cena_sluzby . " " . $ceny["mena"] . "</td>
                                                                            </tr>";
					//kontroluju, zda jsou vsechny ceny dostupne
					if( $ceny["vyprodano"] == 1 or $ceny["objekt_vyprodano"] == 1 ){
						$vyprodano = 1;
					}else if( $ceny["na_dotaz"] == 1  or $ceny["objekt_na_dotaz"] == 1 ){
						$na_dotaz = 1;
					}else if( $ceny["kapacita_bez_omezeni"] == 1  or $ceny["objekt_kapacita_bez_omezeni"] == 1 ){

					}else{
						if( $ceny["kapacita_volna"] >=  $pocet or $ceny["objekt_kapacita_volna"] >=  $pocet ){

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
                $this->velikost_slevy = round($velikost_slevy);

                if ($zajezd["typ_provize"] == 2) {
                    echo "fixni provize na zajezdu";
                    //procentuelni z cele castky
                    $this->provize = ($zajezd["vyse_provize"] * ($this->celkova_cena - $this->velikost_slevy) / 100);
                } else if ($zajezd["typ_provize"] == 1) {
                    echo "fixni provize na zajezdu";
                    $this->provize = ($zajezd["vyse_provize"] * $this->pocet_osob);
                }
                echo "\n<br/>provize: ".$this->provize." data:".$zajezd["typ_provize"]."\n<br/>";

                //uzivatel musi udat pocet alespon u jedne ceny (testovano v legal_data() )
                //kontrola zda jsem spravne vyplnil ceny

                $this->check_ceny();

                //pokud muzeme ihned rezervovat kapacitu
                if ($objednano_blackdays == 0 and $vyprodano == 0 and $na_dotaz == 0 and $obsazena_kapacita == 0) {
                    $this->stav = 3;
                    //ziskam hlasku z centralnich dat

                    $this->potvrzovaci_hlaska = $this->centralni_data["objednavka:vse_ok"];
                    $hlaska_color = "#009049";
                    $bg_color = "#bde38a";
                    //nastaveni data ukonceni rezervace
                    //pokud je odjezd zajezdu jeste dostatecne daleko, nastavime standartni delku rezervace, jinak pouze jeden den
                    if ($zajezd["od"] >= Date("Y-m-d", (time() + ( 2 * PLATNOST_OPCE * 24 * 60 * 60)))) {
                        $this->rezervace_do = Date("Y-m-d", (time() + (PLATNOST_OPCE * 24 * 60 * 60)));
                    } else {
                        $this->rezervace_do = Date("Y-m-d", (time() + ( 1 * 24 * 60 * 60)));
                    }

                    //updatuju kapacity jednotlivych cen
                    foreach ($update_kapacity as $i => $dotaz) {
                        $dotaz_kapacita = $this->database->query($dotaz)
                                or $this->chyba("Chyba p�i dotazu do datab�ze kapacity: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    }
                        //updatuju kapacity na toku
                        foreach ($this->query_tok_kapacity as $key => $query) {
                            $dotaz_kapacitaTOK = $this->database->query($query)
	 				or $this->chyba("Chyba p�i dotazu do datab�ze: TOK".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                        }                        
		}else if($objednano_blackdays == 1){
                    $this->potvrzovaci_hlaska = $this->centralni_data["objednavka:blackdays"];
                    //replace za skutecne terminy s blackdays
                    $this->potvrzovaci_hlaska = str_replace('[$blackdays_terminy]', $terminy_blackdays, $this->potvrzovaci_hlaska);
                    $hlaska_color = "#da4000";
                    $bg_color = "#ffe3ca";
                    $this->varovna_zprava = new VarovnaZprava($this->potvrzovaci_hlaska, $hlaska_color, $bg_color);
                    $this->rezervace_do = "0000-00-00";
                } else if ($vyprodano == 1) {
                    $this->potvrzovaci_hlaska = $this->centralni_data["objednavka:vyprodano"];
                    $hlaska_color = "#da4000";
                    $bg_color = "#ffe3ca";
                    $this->varovna_zprava = new VarovnaZprava($this->potvrzovaci_hlaska, $hlaska_color, $bg_color);
                    $this->rezervace_do = "0000-00-00";
                } else if ($obsazena_kapacita == 1) {
                    $this->potvrzovaci_hlaska = $this->centralni_data["objednavka:na_dotaz"];
                    $hlaska_color = "#009049";
                    $bg_color = "#bde38a";
                    $this->varovna_zprava = new VarovnaZprava($this->potvrzovaci_hlaska, $hlaska_color, $bg_color);
                    $this->rezervace_do = "0000-00-00";
                } else if ($na_dotaz == 1) {
                    $this->potvrzovaci_hlaska = $this->centralni_data["objednavka:na_dotaz"];
                    $hlaska_color = "#009049";
                    $bg_color = "#bde38a";
                    $this->varovna_zprava = new VarovnaZprava($this->potvrzovaci_hlaska, $hlaska_color, $bg_color);
                    $this->rezervace_do = "0000-00-00";
                }
                // echo $this->potvrzovaci_hlaska;
                //print_r($this);
                /* ----------------------------create objednavky-------------------------------- */
                if (!$this->get_error_message()) {
                    //nejprve vlozim do databaze objedn�vaj�c�ho a ziskam jeho id
                    $dotaz_objednavajici = $this->database->transaction_query($this->create_query("create_objednavajici"))
                            or $this->chyba("Chyba p�i dotazu do datab�ze objedn�vaj�c�: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $this->id_klient = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);


                    //nejprve vlozim do databaze objednavku a ziskam jeji id
                    $dotaz_objednavka = $this->database->transaction_query($this->create_query("create_objednavka"))
                            or $this->chyba("Chyba p�i dotazu do datab�ze objedn�vka: " . mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->create_query("create_objednavka"));
                    $id_objednavka = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                    $this->id_objednavka = $id_objednavka;
                    
                    if($this->dotaz_slevy!=""){//chci vlo�it �asovou slevu
                        $this->dotaz_slevy = str_replace("[id_objednavka]", $this->id_objednavka, $this->dotaz_slevy);
                        $this->dotaz_slevy = str_replace("[velikost_slevy]", $this->velikost_slevy, $this->dotaz_slevy);
                        $dotaz_slevy = $this->database->transaction_query($this->dotaz_slevy)
                            or $this->chyba("Chyba p�i dotazu do datab�ze objedn�vka slevy: " . mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->dotaz_slevy);
                        $update_ceny = " update `objednavka` set `celkova_cena` = (`celkova_cena` - $this->velikost_slevy),
                                                        `zbyva_zaplatit` = (`zbyva_zaplatit` - $this->velikost_slevy)
                                                    where `id_objednavka` = $this->id_objednavka limit 1";
                        $dotaz_zmena_zaplatit = $this->database->transaction_query($update_ceny)
                            or $this->chyba("Chyba p�i dotazu do datab�ze zm�na zaplatit: " . mysqli_error($GLOBALS["core"]->database->db_spojeni).$update_ceny);
                    }
                    
                    /* ----------------------------create cen objednavky-------------------------------- */
                    //vytvorim dotaz pro objednavku cen
                    $objednavka_cen = "INSERT INTO `objednavka_cena` (`id_objednavka`,`id_cena`,`pocet`,`cena_castka`,`cena_mena`,`use_pocet_noci`) VALUES ";
                    $j = 0;
                    foreach ($this->id_ceny as $i => $id) {                                     
                        if ($j == 0) { //zjistim zda mam dat pred hodnoty carku
                            $objednavka_cen = $objednavka_cen . "(" . $id_objednavka . "," . $id . "," . intval($this->pocet_ceny[$i]) . "," . intval($this->castka_ceny[$i]) . ",\"" . $this->mena_ceny[$i] . "\"," . intval($this->use_pocet_noci_ceny[$i]) . ")";
                        } else {
                            $objednavka_cen = $objednavka_cen . ", (" . $id_objednavka . "," . $id . "," . intval($this->pocet_ceny[$i]) . "," . intval($this->castka_ceny[$i]) . ",\"" . $this->mena_ceny[$i] . "\"," . intval($this->use_pocet_noci_ceny[$i]) . ")";
                        }
                        $j++;
                    }
                    // echo $objednavka_cen;
                    $dotaz_ceny = $this->database->transaction_query($objednavka_cen)
                            or $this->chyba("Chyba p�i dotazu do datab�ze objedn�vka ceny: " . mysqli_error($GLOBALS["core"]->database->db_spojeni).$objednavka_cen);
                    //echo $objednavka_cen;
                    
                        //vytvorim objednavku tok
                        foreach ($this->query_tok as $key => $query) {
                            $query = str_replace("[id_objednavka]", $id_objednavka, $query);
                            $this->database->transaction_query($query);
                        }
                        

                    /* ----------------------------tvorba osob-------------------------------- */
                    //vytvorim jednotlive osoby, pokud je to treba
                    foreach ($this->new_clients as $i => $dotaz) {

                        if ($i == 1 and $this->objednavajici_je_ucastnik == 1) {
                            //update dotazm je treba dodat id_klienta
                            $dotaz_klient = $this->database->transaction_query($dotaz . $this->id_klient)
                                    or $this->chyba("Chyba p�i dotazu do datab�ze klienti 1: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                            $this->id_clients[$i] = $this->id_klient;
                        } else {

                            $dotaz_klient = $this->database->transaction_query($dotaz)
                                    or $this->chyba("Chyba p�i dotazu do datab�ze klienti 2: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                            //doplnim informace o id klientu
                            $this->id_clients[$i] = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                        }
                        //echo $dotaz;
                    }

                    //vytvorim dotaz pro objednavku_osob
                    $objednavka_osob = "INSERT INTO `objednavka_osoby` (`id_objednavka`,`id_klient`,`cislo_osoby`) VALUES ";
                    $j = 0;
                    foreach ($this->id_clients as $i => $id) {
                        if ($j == 0) { //zjistim zda mam dat pred hodnoty carku
                            $objednavka_osob = $objednavka_osob . "(" . $id_objednavka . "," . $id . "," . ($i + 1) . ")";
                        } else {
                            $objednavka_osob = $objednavka_osob . ", (" . $id_objednavka . "," . $id . "," . ($i + 1) . ")";
                        }
                        $j++;
                        //upravim textovou informaci o klientech -> pouzije se v potvrzovacim emailu
                        if( $this->array_osoby[$i]["cislo_pasu"] != "") {
                            $doklad = $this->array_osoby[$i]["cislo_pasu"];
                        } else {
                            $doklad = $this->array_osoby[$i]["cislo_op"];
                        }

                        $this->text_ucastnici_klient .="
                                            <tr>
                                                    <td rowspan=\"3\" valign=\"top\" width=\"20px;\">
                                                            <strong style=\"font-size:2em;\">" . $i . "</strong>
                                                    </td>
                                                    <td><strong  style=\"font-size: 1.2em;\">" . $this->array_osoby[$i]["titul"] . " " . $this->array_osoby[$i]["jmeno"] . " " . $this->array_osoby[$i]["prijmeni"] . "</strong></td><td>e-mail: " . $this->array_osoby[$i]["email"] . "</td> <td>tel.: " . $this->array_osoby[$i]["telefon"] . "</td>
                                            </tr>
                                            <tr>
                                                    <td>datum nar.: " . $this->change_date_en_cz($this->array_osoby[$i]["datum_narozeni"]) . "</td><td>R�: " . $this->array_osoby[$i]["rodne_cislo"] . "</td><td>�. dokladu: " . $doklad . "</td>
                                            </tr>
                                            <tr>
                                                    <td colspan=\"3\">Adresa: " . $this->array_osoby[$i]["ulice"] . ",  " . $this->array_osoby[$i]["psc"] . ", " . $this->array_osoby[$i]["mesto"] . "</td>
                                            </tr>
                                            <tr>
                                                    <td colspan=\"4\"><hr style=\"color: #4682B4; margin-right:15px; height:2px;\"/></td>
                                            </tr>
                                    ";
                    }
                    echo $objednavka_osob;
                    $dotaz_osoby = $this->database->transaction_query($objednavka_osob)
                            or $this->chyba("Chyba p�i dotazu do datab�ze objedn�vka osob: " . mysqli_error($GLOBALS["core"]->database->db_spojeni).$objednavka_osob);

                    /* ----------------------------odeslani e-mailu s objednavkou-------------------------------- */
                    if (!$this->get_error_message()) {

                        $this->database->commit(); //potvrzeni transakce - odeslani e-mailu uz neni zasadni..
                        //nahrada puvodnich sablon za centralni data v kombinaci s info o zaloze
                        $now = new DateTime(Date("Y-m-d h:i:s"));
                        if ($this->upresneni_terminu_od != "") {
                            $ref = new DateTime($this->change_date_cz_en($this->upresneni_terminu_od) . " 00:00:00");
                        } else {
                            $ref = new DateTime($zajezd["od"] . " 00:00:00");
                        }
                        $diff = $now->diff($ref, true);
                        $dnu_do_odjezdu = $diff->days;

                        //aktualni zaloha
                        $sql_podminky = "select `smluvni_podminky`.* from `smluvni_podminky`  where `id_smluvni_podminky_nazev`=" . $zajezd["id_sml_podm"] . " and (`typ`=\"z�loha\" or `typ`=\"doplatek\")
                                        order by `prodleva` desc ";

                        $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_podminky);
                        $existuje_zaloha = 0;
                        $existuje_druha_zaloha = 0;
                        $dalsi_zaloha_castka = array();
                        $dalsi_zaloha_jednotka = array();
                        $dalsi_zaloha_termin = array();

                        $prosla_zaloha = 0;
                        $prosly_rozhodujici_termin = 0;
                        $platba_cele_castky_najednou = 0;
                        $nalezena_aktualni_zaloha = 0;
                        $aktualni_zaloha = 100;
                        $jednotka_zaloha = "%";
                        $aktualni_rozhodujici_termin = 0;
                        $this->castka_k_zaplaceni = ($this->celkova_cena - $this->velikost_slevy) ;
                        $this->celkova_castka_po_sleve = ($this->celkova_cena - $this->velikost_slevy) ;
                        while ($row_podminky = mysqli_fetch_array($query)) {
                            $podm .= implode(" ", $row_podminky);
                            //nasli jsme aktualni polozku - bud zalohu nebo doplatek
                            if (!$nalezena_aktualni_zaloha and ($row_podminky["prodleva"] + 5) <= $dnu_do_odjezdu) {

                                if ($row_podminky["typ"] == "z�loha") {

                                    //nasli jsme zalohu
                                    $nalezena_aktualni_zaloha = 1;
                                    $existuje_zaloha = 1;
                                    if ($row_podminky["procento"] > 0) {
                                        $aktualni_zaloha = $row_podminky["procento"];
                                        $jednotka_zaloha = "%";
                                        $vyse_zalohy = round(($this->celkova_cena - $this->velikost_slevy) * $aktualni_zaloha * 0.01);
                                        $this->castka_k_zaplaceni = $vyse_zalohy ;
                                    } else {
                                        $aktualni_zaloha = $row_podminky["castka"];
                                        $jednotka_zaloha = "K�";
                                        //castka vztazena na osobu
                                        $vyse_zalohy = round($this->pocet_osob * $aktualni_zaloha);
                                        $this->castka_k_zaplaceni = $vyse_zalohy ;
                                    }
                                    $aktualni_rozhodujici_termin = $row_podminky["prodleva"];
                                } else {//nasli jsme az doplatek
                                    $platba_cele_castky_najednou = 1;
                                    if ($existuje_zaloha == 1) {
                                        $prosla_zaloha = 1;
                                    }
                                }
                            } else if (!$nalezena_aktualni_zaloha) {
                                //nasli jsme proslou zalohu - ale muze byt jeste vic zaloh
                                $existuje_zaloha = 1;
                                $prosly_rozhodujici_termin = $row_podminky["prodleva"];
                            } else if ($row_podminky["typ"] == "z�loha") {
                                //nasli jsme druhou zalohu, doplatek neni az tak zajimavy                                        
                                $existuje_druha_zaloha = 1;
                                if ($row_podminky["procento"] > 0) {
                                    $dalsi_zaloha_castka[] = $row_podminky["procento"];
                                    $dalsi_zaloha_jednotka[] = "%";
                                } else {
                                    $dalsi_zaloha_castka[] = $row_podminky["castka"];
                                    $dalsi_zaloha_jednotka[] = "K�";
                                }
                                $dalsi_zaloha_termin[] = $row_podminky["prodleva"];
                            }
                        }
                        $castka_eur = 0;

                        /* typy hlasek:
                         * zaloha_doplatek : mam aktualni zalohu a neexistuje druha zaloha
                         * zaloha_obecne: mam aktualni zalohu a existuje druha zaloha
                         * prosla_zaloha: prosla_zaloha = 1
                         * cela_castka: platba_cele_castky_najednou = 1 (nebo staci else)
                         * vyprodano, na dotaz, black days
                         */                        
                          if($vyprodano){
                                    $hlaska_platba = $this->centralni_data["platba:vyprodano"].$this->centralni_data["platebni_spojeni:hlavni"];
                                }else if($na_dotaz){
                                    $hlaska_platba = $this->centralni_data["platba:na_dotaz"].$this->centralni_data["platebni_spojeni:hlavni"];
                                }else if($objednano_blackdays){
                                    $hlaska_platba = $this->centralni_data["platba:vyprodano"].$this->centralni_data["platebni_spojeni:hlavni"];
                                }else if($obsazena_kapacita){
                                    $hlaska_platba = $this->centralni_data["platba:na_dotaz"].$this->centralni_data["platebni_spojeni:hlavni"];
                                }else{//volna kapacita
                            if ($nalezena_aktualni_zaloha and !$existuje_druha_zaloha) {
                                $hlaska_platba = $this->centralni_data["platba:zaloha_doplatek"];
                                $hlaska_platba = str_replace('[$vyse_zalohy_castka]', $aktualni_zaloha, $hlaska_platba);
                                $hlaska_platba = str_replace('[$vyse_zalohy_jednotka]', $jednotka_zaloha, $hlaska_platba);
                                $hlaska_platba = str_replace('[$doplatek_max_termin]', $aktualni_rozhodujici_termin, $hlaska_platba);
                                $hlaska_platba = str_replace('[$vyse_zalohy_objednavka]', $vyse_zalohy, $hlaska_platba);
                                $castka_eur = $vyse_zalohy/$this->centralni_data["kurz EUR"];
                                
                            } else if ($nalezena_aktualni_zaloha and $existuje_druha_zaloha) {
                                $hlaska_platba = $this->centralni_data["platba:zaloha_obecne"];
                                $hlaska_platba = str_replace('[$vyse_zalohy_castka]', $aktualni_zaloha, $hlaska_platba);
                                $hlaska_platba = str_replace('[$vyse_zalohy_jednotka]', $jednotka_zaloha, $hlaska_platba);
                                $hlaska_platba = str_replace('[$vyse_zalohy_objednavka]', $vyse_zalohy, $hlaska_platba);
                                $castka_eur = $vyse_zalohy/$this->centralni_data["kurz EUR"];
                                
                                $doplatky_text = "<br/>";
                                foreach ($dalsi_zaloha_castka as $key => $value) {
                                    if ($key == 0) {
                                        $rozh_termin = $aktualni_rozhodujici_termin;
                                    } else {
                                        $rozh_termin = $dalsi_zaloha_termin[($key - 1)];
                                    }
                                    if ($dalsi_zaloha_jednotka[$key] == "%") {
                                        $text = "Z�lohu do v��e $value % z ceny z�jezdu uhra�te nejpozd�ji " . $rozh_termin . " dn� p�ed odjezdem z�jezdu.<br/>";
                                    } else {
                                        $text = "Z�lohu do v��e $value K� za osobu uhra�te nejpozd�ji " . $rozh_termin . " dn� p�ed odjezdem z�jezdu.<br/>";
                                    }
                                    $doplatky_text.=$text;
                                }
                                $doplatky_text.= "Doplatek uhra�te pros�m nejpozd�ji " . $dalsi_zaloha_termin[max(array_keys($dalsi_zaloha_termin))] . " dn� p�ed odjezdem z�jezdu.<br/>";
                                $hlaska_platba = str_replace('[$doplatky_text]', $doplatky_text, $hlaska_platba);
                            } else if ($prosla_zaloha) {
                                $hlaska_platba = $this->centralni_data["platba:prosla_zaloha"] ;
                                $hlaska_platba = str_replace('[$max_termin_zaloha]', $prosly_rozhodujici_termin, $hlaska_platba);
                                $hlaska_platba = str_replace('[$vyse_zalohy_objednavka]', $this->celkova_castka_po_sleve, $hlaska_platba);
                                $castka_eur = $this->celkova_castka_po_sleve/$this->centralni_data["kurz EUR"];
                            } else {
                                $hlaska_platba = $this->centralni_data["platba:cela_castka"] ;
                                $hlaska_platba = str_replace('[$vyse_zalohy_objednavka]', $this->celkova_castka_po_sleve, $hlaska_platba);
                                $castka_eur = $this->celkova_castka_po_sleve/$this->centralni_data["kurz EUR"];
                            }
                            if($this->zpusob_platby=="hotove"){
                                $hlaska_platba .=  $this->centralni_data["platebni_spojeni:hotove"];
                            }else if($this->zpusob_platby=="prevodem" or $this->zpusob_platby=="prevodem_sk"){
                                $hlaska_platba .=  $this->centralni_data["platebni_spojeni:prevodem"];
                            }else if($this->zpusob_platby=="kartou"){
                                $hlaska_platba .=  $this->centralni_data["platebni_spojeni:kartou"];
                            }else if($this->zpusob_platby=="slozenkou"){
                                $hlaska_platba .=  $this->centralni_data["platebni_spojeni:slozenkou"];
                            }else{
                                $hlaska_platba .=  $this->centralni_data["platebni_spojeni:hlavni"];
                            }
                            
                        }
                        $castka_eur = round($castka_eur);
                        $hlaska_platba = str_replace('[$castka_eur]', $castka_eur, $hlaska_platba);
                        $hlaska_platba = str_replace('[$id_objednavka]', $id_objednavka, $hlaska_platba);
                        // $hlaska_platba.=$sql_podminky.$dnu_do_odjezdu."-".$aktualni_zaloha.$podm;
                        //ziskani sablony pro odesilani objednavky
                        $this->id_sablony_objednavka = $zajezd["id_sablony_objednavka"];
                        $this->id_sablony_zobrazit = $zajezd["id_sablony_zobrazeni"];
                        //print_r($zajezd);
                        /*$sablona = mysqli_fetch_array($this->database->transaction_query($this->create_query("sablona_objednavka")))
                                or $this->chyba("Chyba p�i dotazu do datab�ze �ablona objedn�vka: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));*/
                        //require_once "./".ADRESAR_SABLONA."/".$sablona["adresa_sablony"]."";

                        //require_once "./templates/" . $sablona["adresa_sablony"] . "";

                       //odeslu e-maily
                        //odeslu klientovi e-mail s potvrzovacim kodem
                        if ($sablona_zobrazeni["adresa_sablony"] == "zobrazit_vstupenky.inc.php") {
                            $predmet_ck = "Objedn�vka vstupenek: " . $nazev_zajezd ;
                            $predmet_klient = "Potvrzen� odesl�n� objedn�vky vstupenek";
                        } else {
                            $predmet_ck = "Objedn�vka z�jezdu: " . $nazev_zajezd ;
                            $predmet_klient = "Potvrzen� odesl�n� objedn�vky z�jezdu";
                        }

                        if ($zajezd["dlouhodobe_zajezdy"]) {
                            $termin_zajezdu = "Obdob�";
                            //kontrola zda je upresneni terminu uvnitr obdobi                                     
                            $termin_od = new DateTime($this->change_date_cz_en($this->upresneni_terminu_od) . " 00:00:00");
                            $termin_do = new DateTime($this->change_date_cz_en($this->upresneni_terminu_do) . " 00:00:00");
                            $obdobi_od = new DateTime($zajezd["od"] . " 00:00:00");
                            $obdobi_do = new DateTime($zajezd["do"] . " 00:00:00");

                            if ($obdobi_od <= $termin_od and $obdobi_do >= $termin_do) {
                                //hlaska ze je vse OK neni treba
                                $hlaska_termin = "";
                            } else {
                                $hlaska_termin = "<b>" . $this->centralni_data["objednavka:chybny_termin"] . "</b>";
                                $hlaska_termin = str_replace('[$termin_zajezdu]', "" . $this->change_date_en_cz($zajezd["od"]) . " - " . $this->change_date_en_cz($zajezd["do"]) . "", $hlaska_termin);
                            }
                        } else {
                            $termin_zajezdu = "Term�n";
                        }

                        $klient_jmeno = $this->prijmeni . " " . $this->jmeno;
                        $klient_email = $this->email;
                        $rsck_email = PRIJIMACI_EMAIL;
                        $zamestnanec_email = $zajezd["email"];
                        
                        /*delalo to problem s vytvarenim null objektu, vytvoril jsem dummy tridu abych to odstranil*/
                        $spravce = new spravce();
                        $spravce->email = $zajezd["email_zamestnanec"];
                        $spravce->jmeno = $zajezd["jmeno_zamestnanec"];
                        $spravce->prijmeni = $zajezd["prijmeni_zamestnanec"];
                        $spravce->telefon = $zajezd["telefon_zamestnanec"];

                        if ($this->upresneni_terminu_od != "") {
                            $termin = "<tr>
                                                    <td><strong>Up�esn�n� term�nu:</strong> " . $this->upresneni_terminu_od . " - " . $this->upresneni_terminu_do . "</td>  <td></td>
                                                    </tr>";
                        } else {
                            $termin = "";
                        }
                        if ($this->objednavajici_ca) {
                            $info_agentura = "
                                                    <table class=\"objednavka\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
                                                            <tr>
                                                                    <td style=\"border-top: 3px solid #3d3937;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #3d3937;	background-color: #efefef;	valign=\"top\">
                                                                            <h2 style=\"font-size: 1.4em;margin:0 0 0 10px;padding:0;\">Z�jezd je objedn�v�n prost�ednictv�m agentury:</h2>
                                                                            <p style=\"margin:0 5px 5px 20px;\">
                                                                            <strong style=\"font-size: 1.2em;\">" . $agentura["nazev"] . "</strong>; " . $agentura["ulice"] . ", " . $agentura["mesto"] . ", " . $agentura["psc"] . " <br/>
                                                                            telefon: " . $agentura["telefon"] . " <br/>
                                                                            e-mail: " . $agentura["email"] . " <br/>
                                                                            </p>
                                                                    </td>
                                                            </tr>
                                                    </table>
                                            ";
			    $objednavka_id_text = "<br/><br/><b>��slo objedn�vky (variabiln� symbol):</b> ".$id_objednavka;

                        } else {
                            $info_agentura = "";
			    $objednavka_id_text = "";
                        }
                        if ($this->velikost_slevy > 0) {
                            $text_slevy = "
                                                            <tr>
                                                                    <td colspan=\"4\"> <hr style=\"color: black; height:2px;\"/> </td>
                                                            </tr>
                                                            <tr>
                                                                    <th align=\"left\" style=\"padding-right:50px;\">N�ZEV SLEVY</th><th align=\"right\">Sleva</th><th align=\"right\"></th><th align=\"right\">Celkem</th>
                                                            </tr>
                                                            <tr>
                                                                    <td style=\"padding-right:50px;\">" . $slevy["nazev_slevy"] . "</td><td align=\"right\">" . $slevy["castka"] . " " . $slevy["mena"] . "</td><td align=\"right\"></td><td align=\"right\">" . $this->velikost_slevy . " K�</td>
                                                            </tr>
                                            ";
                        } else {
                            $text_slevy = "";
                        }

                        if ($sablona_zobrazeni["adresa_sablony"] == "zobrazit_vstupenky.inc.php") {
                            $obj_nadpis = "Objedn�vka vstupenek CK SLAN tour";
                            $zajezd_nadpis = "Vstupenky";
                        } else {
                            $obj_nadpis = "Objedn�vka z�jezdu CK SLAN tour";
                            $zajezd_nadpis = "Z�jezd/Pobyt";
                        }
                        $klient_text = "
    <div style=\"	font-family: Helvetica, Arial,  sans-serif;font-size: 12px;	margin: 0;	padding: 0;\">

    <table class=\"objednavka\" cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
            <tr>
                    <td width=\"420\"  style=\"	border-top: 3px solid " . $hlaska_color . "; border-left: 1px solid white;	 border-right:1px solid white;border-bottom: 3px solid " . $hlaska_color . "; background-color: " . $bg_color . ";\" valign=\"top\">
                                    <h1 style=\"font-size: 1.6em;color: " . $hlaska_color . ";margin:0 0 0 10px;padding:0;\">" . $obj_nadpis . "</h1>
                                    <p style=\"margin:0 5px 5px 20px;	font-size:1.0em;	font-weight: bold;	clear:left;\">
                                            " . $this->potvrzovaci_hlaska . "
                                    </p>
                            </td>
                    <td width=\"15\">&nbsp;</td>
                    <td width=\"200\" style=\"	border-top: 3px solid #3d3937;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #3d3937;	background-color: #efefef;	valign=\"top\">
                            <h2 style=\"font-size: 1.4em;margin:0 0 0 10px;padding:0;\">" . $this->centralni_data["nazev_spolecnosti"] . "</h2>
                            <p style=\"margin:0 5px 5px 20px;	font-size:1.0em;	font-weight: bold;	clear:left;\">
                                    " . $this->centralni_data["adresa"] . "<br/>
                                    tel.: " . $this->centralni_data["telefon"] . "<br/>
                                    e-mail: <a href=\"mailto:" . $this->centralni_data["email"] . "\">" . $this->centralni_data["email"] . "</a><br/>
                                    web: <a href=\"http://" . $this->centralni_data["web"] . "\">" . $this->centralni_data["web"] . "</a><br/>
                            </p>

           </p> 
                    </td>
    </tr>
    </table>
                    " . $info_agentura . "
    <table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
            <tr>
                    <td style=\"	border-top: 3px solid #E77919;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #E77919;	background-color: #FFFDD4;	padding-bottom:5px;	padding-top:2px;\" valign=\"top\">
                            <h2 style=\"font-size: 1.4em;color: #BF6A00;margin:0 0 0 10px;\">Objedn�vaj�c�</h2>
                            <table style=\"width:100%;margin-left:20px;	font-size: 12px;clear:left;\">
                                    <tr>
                                            <td><strong style=\"font-size: 1.2em;\">" . $this->prijmeni . " " . $this->jmeno . "</strong></td> <td>e-mail: " . $this->email . "</td> <td>tel.: " . $this->telefon . "</td>
                                    </tr>
                                    <tr>
                                            <td colspan=\"2\" align=\"left\">Adresa: " . $this->ulice . ", " . $this->psc . ", " . $this->mesto . "</td><td align=\"left\">datum nar.: " . $this->datum_narozeni . "</td>
                                    </tr>
                            </table>
                    </td>
            </tr>
    </table>

    <table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
            <tr>
                    <td style=\"	border-top: 3px solid #DA251D;	 border-left: 1px solid white;	 border-right:1px solid white;	border-bottom: 3px solid #DA251D;	background-color: #FFFDD4;	padding-bottom:5px;	padding-top:2px;\" valign=\"top\">
                            <h2 style=\"font-size: 1.4em;color: #DA251D;margin:0 0 0 10px;\">" . $zajezd_nadpis . "</h2>

                            <table style=\"width:100%;margin-left:20px;	font-size: 12px;	clear:left;\">
                                    <tr>
                                            <td><strong style=\"font-size: 1.2em;\">" . $nazev_zajezd .  "</strong> " . $zajezd["nazev_zajezdu"] . "</td>  <td align=\"right\" style=\"padding-right:50px;\">" . $termin_zajezdu . ": <b>" . $this->change_date_en_cz($zajezd["od"]) . " - " . $this->change_date_en_cz($zajezd["do"]) . "</b></td>
                                    </tr>
                                    <tr>
                                            <td><strong>Po�et osob</strong>: " . $this->pocet_osob . "</td>  <td></td>
                                    </tr>
                                    " . $termin . "

                                    <tr>
                                            <td colspan=\"2\"><b>Po�adovan� slu�by</b></td>
                                    </tr>
                                    <tr>
                                            <td colspan=\"2\">
                                                    <table style=\"margin-left:15px; width:90%;font-size: 12px;	clear:left;\">

                                                            " . $this->text_ceny_klient
                                . $text_slevy . "
                                                            <tr>
                                                                    <td colspan=\"4\"> <hr style=\"color: black; height:2px;\"/> </td>
                                                            </tr>

                                                            <tr>
                                                                    <th colspan=\"3\" align=\"left\" style=\"padding-right:50px;\"><strong style=\"font-size: 1.2em;\">P�edpokl�dan� celkov� cena</strong></th><th  align=\"right\"><strong style=\"font-size: 1.2em; color:red;\">" . ($this->celkova_cena - $this->velikost_slevy) . " K� (".round(($this->celkova_cena - $this->velikost_slevy)/$this->centralni_data["kurz EUR"])." EUR)</strong></th>
                                                            </tr>
                                                    </table>
                                            </td>
                                    </tr>
                                    <tr>
                                            <td colspan=\"2\"  style=\"padding-right:20px;\"><b>Pozn�mky:</b><br/>
                                                     " . nl2br($this->poznamky) . "
						     " . $objednavka_id_text. "

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
                                    " . $this->text_ucastnici_klient . "
                            </table>
            </td>
    </tr>
    </table>
    ";
                        if (!$this->objednavajici_ca) {
                            $klient_text.="
    <table cellpadding=\"0\" cellspacing=\"0\" style=\"width:640px;margin-bottom:15px;font-size: 12px;\">
            <tr>
                    <td style=\"	border-top: 3px solid #b3ae4a;	 border-left: 1px solid white;	 border-right: 1px solid white;	border-bottom: 3px solid #b3ae4a;	background-color: #FFFDD4;	padding-bottom:5px; padding-right:20px;	padding-top:2px;\" valign=\"top\">
                            <h2 style=\"font-size: 1.4em;color: #5a5727;margin:0 0 0 10px;\">Informace o platb�</h2>
                            <p style=\"width:100%;margin:0 20px 0 20px;	font-size: 12px;	clear:left;\">
                                    " . $hlaska_termin . $hlaska_platba . "<br/>
                               Spr�vce z�jezdu: $spravce->jmeno $spravce->prijmeni, $spravce->email, $spravce->telefon
                            </p>
                    </td>
            </tr>
    </table>";
                        }else{

                            
                            $klient_text .= "Spr�vce z�jezdu: $spravce->jmeno $spravce->prijmeni, $spravce->email, $spravce->telefon <br/> ";
                        }
                        if($this->novinky == 1){
                            $this->novinky = "Ano";
                        }else{
                            $this->novinky = "Ne";
                        }
                        $klient_text.="
                                                            Souhlas se zas�l�n�m aktu�ln�ch nab�dek CK SLAN tour: " . $this->novinky . "<br/><br/>
                                                                         
             (Souhlas se zpracov�n�m osobn�ch �daj� v rozsahu jm�no, p��jmen�, telefonn� ��slo a e-mailov� adresa za ��elem zas�l�n� obchodn�ch sd�len�. Cestovn� kancel�� m��e zas�lat obchodn� sd�len� formou SMS, MMS, elektronick� po�ty, po�tou �i sd�lovat telefonicky a to maxim�ln� 1x t�dn�.)<br/><br/>
              Proti zas�l�n� obchodn�ch sd�len� je mo�no vzn�st kdykoliv n�mitku, a to bu� na adrese cestovn� kancel��e nebo e-mailem zaslan�m na adresu info@slantour.cz. V tomto p��pad� nebude cestovn� kancel�� d�le zas�lat obchodn� sd�len�, ani jinak zpracov�vat va�e osobn� �daje pro ��ely p��m�ho marketingu.

            <br/><br/>                                                
            <b>Odesl�n�m objedn�vky z�rove�:</b><br/>
            <ul>
                <li>Souhlas�m se <a href=\"https://www.slantour.cz/dokumenty/".$_POST["smluvni_podminky"]."\" target=\"_blank\">smluvn�mi podm�nkami cestovn� kancel��e SLAN tour</a>, s.r.o., kter� jsou ned�lnou sou��st� objedn�vky/smlouvy o z�jezdu.</li>
                <li>Potvrzuji, �e jsem se sezn�mil s podrobn�m vymezen�m z�jezdu.</li>
                <li>Prohla�uji, �e jsem opr�vn�n uzav��t smlouvu za v�echny osoby, uveden� v t�to smlouv� a odpov�d�m za �hradu celkov� ceny z�jezdu.</li>
                <li>Potvrzuji, �e jsem se sezn�mil s p��slu�n�m <a href=\"https://www.slantour.cz/dokumenty/3126-povinne-informace-k-zajezdu.pdf\" target=\"_blank\">formul��em dle vyhl�ky �. 122/2018 Sb.</a>, o vzorech formul��� pro jednotliv� typy z�jezd� a spojen�ch cestovn�ch slu�eb, a s <a href=\"https://www.slantour.cz/dokumenty/3041-certifikat-pojistovny.pdf\" target=\"_blank\">dokladem o poji�t�n� CK proti �padku</a>.</li>
            </ul>


                Objedn�vka z webu: " . $_SERVER["SERVER_NAME"] . "<br/>	<br/>
                </div>
                                    ";
                        $ck_text = $klient_text;


                        //odeslani potvrzovaciho e-mailu
                        if ($this->objednavajici_ca) {
                            $mail = Send_mail::send(AUTO_MAIL_SENDER, AUTO_MAIL_EMAIL, $agentura["email"], $predmet_klient, $klient_text);
                        } else {
                            $mail = Send_mail::send(AUTO_MAIL_SENDER, AUTO_MAIL_EMAIL, $klient_email, $predmet_klient, $klient_text);
                        }

                        if ($mail) {
                            //odeslani emailu na standardni adresu systemu
                            if ($this->objednavajici_ca) {
                                $testMail = Send_mail::send($agentura["nazev"], $agentura["email"], $spravce->email, $predmet_ck, $ck_text);
                                Send_mail::send($agentura["nazev"], $agentura["email"], $rsck_email, $predmet_ck, $ck_text);
                                Send_mail::send($agentura["nazev"], $agentura["email"], "lpeska@seznam.cz", $predmet_ck, $ck_text);
                            } else {
                                $testMail = Send_mail::send($klient_jmeno, $klient_email, $spravce->email, $predmet_ck, $ck_text);
                                Send_mail::send($klient_jmeno, $klient_email, $rsck_email, $predmet_ck, $ck_text);
                                Send_mail::send($klient_jmeno, $klient_email, "lpeska@seznam.cz", $predmet_ck, $ck_text);
                            }

                            //odesilani e-mailu zamestnanci - tvurci serialu

                            if ($sablona_zobrazeni["adresa_sablony"] == "zobrazit_vstupenky.inc.php") { 
                                $this->confirm("Objedn�vka vstupenek byla �sp�n� odesl�na. ".$spravce->email.$testMail );
                            } else {
                                $this->confirm("Objedn�vka z�jezdu/pobytu byla �sp�n� odesl�na.".$spravce->email.$testMail );
                            }
                        } else {
                            $this->chyba("Nepoda�ilo se odeslat e-mail s objedn�vkou.");
                        }
                    }
                }
            }//!this->get_error_message()
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

	function name_of_typ_ceny($typ_ceny){
		if($typ_ceny == 1){
			return "Slu�by";
		}else if($typ_ceny == 2){
			return "Last minute";
		}else if($typ_ceny == 3){
			return "Slevy";
		}else if($typ_ceny == 4){
			return "P��platky";
		}else if($typ_ceny == 5){
			return "Odjezdov� m�sta";			
		}else{
			return "";
		}
	}

	/** funkce pro fin�ln� zpracov�n� 2. ��sti formul��e pro objedn�vku z�jezdu
	* - zkontroluje, zda lze zarezervovat kapacity
	* - po prijmuti vsech dat vytvori cely dotaz a odesle ho do datab�ze
	* - vytvo�� e-maily s potvrzen�m objedn�vky
	*/
        function get_kategorie($kat="") {
            if($kat==""){$kat=$this->radek["kategorie"];}
            switch ($kat) {
                case "1":
                   return "AA"; break;
                case "2":
                    return "A"; break;
                case "3":
                    return "B"; break;
                case "4":
                    return "C"; break;
                case "5":
                    return "D"; break;
                case "6":
                    return "E"; break;
            }

         }
 /** vytvori text pro nadpis stranky*/
        function get_name_for_doprava($doprava){
                       switch ($doprava) {
                        case "3":
                            return "Letecky";
                            break;
                        case "2":
                            return "Autokarem";
                            break;
                        case "1":
                            return "Vlastn� dopravou";
                            break;
                        default:
                            return $doprava;
                            break;
                        }
                }
        function calculate_pocet_noci($od, $do, $upresneni_od, $upresneni_do){
            if($upresneni_od!="" and $upresneni_od!="0000-00-00" and $upresneni_do!="" and $upresneni_do!="0000-00-00"){
                   $pole_od=explode("-", $upresneni_od);
                   $pole_do=explode("-", $upresneni_do);
            }else{
                   $pole_od=explode("-", $od);
                   $pole_do=explode("-", $do);
            }

            //echo "...........".$pole_od[2]."-".$pole_od[1]."-".$pole_od[0];
            //echo "...........".$pole_do[2]."-".$pole_do[1]."-".$pole_do[0];

            $time_od = mktime(0,0,0,intval($pole_od[1]),intval($pole_od[2]),intval($pole_od[0]));
            $time_do = mktime(0,0,0,intval($pole_do[1]),intval($pole_do[2]),intval($pole_do[0]));
            $pocet_noci = (round(($time_do - $time_od) / (24*60*60)));
            if($pocet_noci<0){
                    $pocet_noci=0;
            }
            return $pocet_noci;
        }
        static  function static_calculate_pocet_noci($od, $do, $upresneni_od, $upresneni_do){
	 if($upresneni_od!="" and $upresneni_od!="0000-00-00" and $upresneni_do!="" and $upresneni_do!="0000-00-00"){
	 	$pole_od=explode("-", $upresneni_od);
		$pole_do=explode("-", $upresneni_do);
	 }else{
	 	$pole_od=explode("-", $od);
		$pole_do=explode("-", $do);
	 }

	 	//echo "...........".$pole_od[2]."-".$pole_od[1]."-".$pole_od[0];
		//echo "...........".$pole_do[2]."-".$pole_do[1]."-".$pole_do[0];

	 	$time_od = mktime(0,0,0,intval($pole_od[1]),intval($pole_od[2]),intval($pole_od[0]));
		$time_do = mktime(0,0,0,intval($pole_do[1]),intval($pole_do[2]),intval($pole_do[0]));
		$pocet_noci = (round(($time_do - $time_od) / (24*60*60)));
		if($pocet_noci<0){
	 		$pocet_noci=0;
	 	}
		return $pocet_noci;
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
	function legal_data_ceny_poznavaci($id_cena,$pocet,$typ_ceny){
		$ok = 1;

		if($typ_ceny==5){
			$this->odjezd_misto_exist = 1;
		}

		//kontrolovane pole id cena a po�et
			if(!Validace::int_min($id_cena,1) ){
				$ok = 0;
			}
			if(!Validace::int_min_max($pocet,1,MAX_OSOB) ){
				$ok = 0;
                                if($pocet > MAX_OSOB){
                                    $this->chyba("Maxim�ln� objedn�vka od jedn� slu�by je ".MAX_OSOB." jednotek.");
                                }
			}
		//pokud je vse vporadku...
		if($ok == 1){
			if($typ_ceny==1 or $typ_ceny==2){
				$this->vyplnena_cena = 1;
			}else if($typ_ceny==5){
				$this->vyplnene_odjezd_misto = 1;
			}
			return true;
		}else{
			return false;
		}
	}

	/**kontrola zda informace o cen�ch (, nenulova id a po�et objednan�ch jednotek)*/
	function legal_data_vstupenky($id_vstupenky,$pocet,$kategorie){
		$ok = 1;

		//kontrolovane pole id cena a po�et
			if(!Validace::int_min($id_vstupenky,1) ){
				$ok = 0;
			}
			if(!Validace::int_min_max($kategorie,1,7) ){
				$ok = 0;
			}
			if(!Validace::int_min_max($pocet,1,MAX_OSOB) ){
				$ok = 0;
			}
		//pokud je vse vporadku...
		if($ok == 1){
                        $this->vyplnene_vstupenky = 1;
			$this->pocet_vstupenek[$id_vstupenky]+=$pocet;
			return true;
		}else{
			return false;
		}
	}

	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku, $id_klient = "") {
            if ($typ_pozadavku == "get_zajezd") {
                $dotaz = "select `serial`.*,
                                    `zajezd`.`nazev_zajezdu`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,
                                
                                     `objekt`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `popis_ubytovani`, 
                                
                                    `user_klient`.`email` as `email_zamestnanec`, `user_klient`.`jmeno` as `jmeno_zamestnanec`, `user_klient`.`prijmeni` as `prijmeni_zamestnanec`, `user_klient`.`telefon` as `telefon_zamestnanec`                                
                                            from `serial`   join
                                                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                                                         left join(
                                                           `objekt_serial` join
                                                           `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                                           `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`) 
                                                           )  on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                                           left join
                                                   `user_zamestnanec` on (`serial`.`id_spravce` = `user_zamestnanec`.`id_user`)
                                                            left join
                                                    `user_klient` on (`user_zamestnanec`.`id_user_klient` = `user_klient`.`id_klient`)
                                            where `serial`.`id_serial`= " . $this->id_serial . "
                                                    and `zajezd`.`id_zajezd`=" . $this->id_zajezd . "
                                            limit 1";

    //			echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_blackdays") {
                $odObj = $this->change_date_cz_en($this->upresneni_terminu_od);
                $doObj = $this->change_date_cz_en($this->upresneni_terminu_do);
                $dotaz = "SELECT `zajezd_blackdays`.`od`,`zajezd_blackdays`.`do`
                                            FROM `zajezd_blackdays`
                                            WHERE `zajezd_blackdays`.`id_zajezd` = $this->id_zajezd 
                                                AND (
                                                    ('$odObj' >= od AND '$odObj' <= do) OR 
                                                    ('$doObj' >= od AND '$doObj' <= do) OR 
                                                    ('$odObj' <= od AND '$doObj' >= do)
                                                    )";
                //Bud je $this->upresneni_terminu_od v itnervalu NEBO je $this->upresneni_terminu_do v intervalu NEBO je interval uvnitr objednanych datumu
    //			echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_ceny") {
			$dotaz= "select `zajezd`.`id_zajezd`,`cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`use_pocet_noci`,`cena`.`kapacita_bez_omezeni`,`cena`.`poradi_ceny`,`cena`.`typ_ceny`,`cena`.`typ_provize`,`cena`.`vyse_provize`,
					`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`kapacita_volna`,
                                            sum(`objekt_kategorie_termin`.`kapacita_volna` + ( (1 - `objekt_kategorie`.`prodavat_jako_celek`) * ( `objekt_kategorie`.`hlavni_kapacita` - 1 ) * `objekt_kategorie_termin`.`kapacita_volna`) ) as `objekt_kapacita_volna`,
                                            max(`objekt_kategorie_termin`.`kapacita_bez_omezeni`) as `objekt_kapacita_bez_omezeni`,
                                            min(`objekt_kategorie_termin`.`vyprodano`) as `objekt_vyprodano`,
                                             min(`objekt_kategorie_termin`.`na_dotaz`) as `objekt_na_dotaz`
					from `zajezd` join
						`cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1) join
						`cena`  on (`cena`.`id_cena` = `cena_zajezd`.`id_cena`)
                                           left join  (    
                                                `cena_zajezd_tok` join
                                                `objekt_kategorie_termin` on (`cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) join
                                                `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`)
                                              ) on(`cena_zajezd_tok`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and  `cena_zajezd_tok`.`id_cena` = `cena_zajezd`.`id_cena`)         
					where `zajezd`.`id_zajezd`=".$this->id_zajezd."
                                        group by `cena_zajezd`.`id_zajezd`, `cena_zajezd`.`id_cena`
                                        order by `cena`.`typ_ceny`,`cena`.`poradi_ceny`";
                        //echo $dotaz;
			return $dotaz;
            } else if ($typ_pozadavku == "get_current_cena") {
			$dotaz= "select `zajezd`.`id_zajezd`,`cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`use_pocet_noci`,`cena`.`kapacita_bez_omezeni`,`cena`.`poradi_ceny`,`cena`.`typ_ceny`,
					`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`kapacita_volna`,
                                            sum(`objekt_kategorie_termin`.`kapacita_volna` + ( (1 - `objekt_kategorie`.`prodavat_jako_celek`) * ( `objekt_kategorie`.`hlavni_kapacita` - 1 ) * `objekt_kategorie_termin`.`kapacita_volna`) ) as `objekt_kapacita_volna`,
                                            max(`objekt_kategorie_termin`.`kapacita_bez_omezeni`) as `objekt_kapacita_bez_omezeni`,
                                            min(`objekt_kategorie_termin`.`vyprodano`) as `objekt_vyprodano`,
                                             min(`objekt_kategorie_termin`.`na_dotaz`) as `objekt_na_dotaz`
					from `zajezd` join
						`cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1) join
						`cena`  on (`cena`.`id_cena` = `cena_zajezd`.`id_cena`)
                                           left join  (    
                                                `cena_zajezd_tok` join
                                                `objekt_kategorie_termin` on (`cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) join
                                                `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`)
                                              ) on(`cena_zajezd_tok`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and  `cena_zajezd_tok`.`id_cena` = `cena_zajezd`.`id_cena`)         
					where `zajezd`.`id_zajezd`=".$this->id_zajezd." and `cena`.`id_cena` = ".$this->current_cena."
                                        group by `cena_zajezd`.`id_zajezd`, `cena_zajezd`.`id_cena`";
                        //echo $dotaz;
			return $dotaz;            
            } else if ($typ_pozadavku == "get_sleva") {
                //$uzivatel = User::get_instance();
                $dotaz = "select * from `slevy` left join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy` and `slevy_serial`.`id_serial` = " . $this->id_serial . ")
                                                           left join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy` and `slevy_zajezd`.`id_zajezd` = " . $this->id_zajezd . ")
                                                            where (`slevy_serial`.`platnost`=1 or `slevy_zajezd`.`platnost` =1 )
                                                            and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" )
                                                            and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" )
                                                            and `slevy`.`sleva_staly_klient` = 0
                                                            order by `slevy`.`castka` desc limit 1";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_centralni_data") {
                $dotaz = "SELECT * FROM `centralni_data` 
                                                    WHERE 1
                            ";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_klient") {
                //$uzivatel = User::get_instance();
                $dotaz = "select `id_klient`,`jmeno`,`prijmeni`,`titul`,`email`,`telefon`,`datum_narozeni`,`rodne_cislo`,
                                                    `cislo_pasu`,`cislo_op`,`ulice`,`mesto`,`psc`
                                            from `user_klient`
                                            where `id_klient`=" . $id_klient . "";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_drive_objednane_osoby") {
                //$uzivatel = User::get_instance();
                $dotaz = "select `id_klient`,`jmeno`,`prijmeni`,`datum_narozeni`
                                            from `user_klient`
                                            where `id_klient_create`=" . intval($uzivatel->get_id()) . "";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_agentura") {
                //$uzivatel = User::get_instance();
                $dotaz = "select `organizace`.`id_organizace`,`nazev`,`ico`,`email`,`telefon`,`provizni_koeficient`,
                                                    `ulice`,`mesto`,`psc`,`organizace_email`.`poznamka`
                                            from `organizace`
                                                left join `prodejce` on (`organizace`.`id_organizace` = `prodejce`.`id_organizace`) 
                                                left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1) 
                                                left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0) 
                                                left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0) 
                                            where `organizace`.`id_organizace`=" . $this->objednavajici_ca . "";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "create_objednavka") {
                if ($this->objednavajici_ca) {
                    $name_agentury = "`id_agentury`,";
                    $val_agentury = "" . $this->objednavajici_ca . ",";
                } else {
                    $name_agentury = "";
                    $val_agentury = "";
                }

                if ($this->upresneni_terminu_od) {
                    $terminy = "`termin_od`,`termin_do`,";
                    $val_terminy = "'" . $this->change_date_cz_en($this->upresneni_terminu_od) . "','" . $this->change_date_cz_en($this->upresneni_terminu_do) . "',";
                } else {
                    $terminy = "";
                    $val_terminy = "";
                }
                //pred ulozenim se jeste provize prenasobi koeficientem prodejce (pokud existuje)
                if($this->provizni_koeficient <= 0){
                    $this->provizni_koeficient = 1;
                }
                if ($this->provize != 0) {
                    $provize_header = ",`suma_provize`,`provize_vc_dph`";
                    $provize_values = ", " . round( $this->provizni_koeficient * $this->provize ) . ", 1";
                   // $this->chyba($this->provizni_koeficient." ".$this->provize." ".round( $this->provizni_koeficient * $this->provize ));
                }
             /*   if ($this->nazev_slevy) {
                    $dotaz = "INSERT INTO `objednavka`
                                                            (`id_klient`," . $name_agentury . "`id_serial`,`id_zajezd`,`datum_rezervace`,`rezervace_do`,
                                                            `stav`,`pocet_osob`,`celkova_cena`,`zbyva_zaplatit`,`kurz_eur`,`poznamky`,`poznamky_tajne`,`doprava`,`stravovani`,`ubytovani`,`security_code`," . $terminy . "`pocet_noci`,`nazev_slevy`,`castka_slevy`,`velikost_slevy`,`zpusob_uhrady`  $provize_header)
                                                    VALUES
                                                             (" . $this->id_klient . "," . $val_agentury . "" . $this->id_serial . "," . $this->id_zajezd . ",'" . Date("Y-m-d H:i:s") . "','" . $this->rezervace_do . "',
                                                             " . $this->stav . "," . $this->pocet_osob . "," . $this->celkova_cena . "," . $this->celkova_cena . ",".$this->centralni_data["kurz EUR"].", '" . $this->poznamky . "','" . $this->src_web . "','" . $this->doprava . "','" . $this->stravovani . "','" . $this->ubytovani . "',
                                                             '" . sha1(mt_rand() . $this->id_klient) . "'," . $val_terminy . "" . $this->pocet_noci . ",'" . $this->nazev_slevy . "','" . $this->castka_slevy . "','" . $this->velikost_slevy . "' , '".$this->zpusob_platby."' $provize_values)";
                } else {*/
                    $dotaz = "INSERT INTO `objednavka`
                                                            (`id_klient`," . $name_agentury . "`id_serial`,`id_zajezd`,`datum_rezervace`,`rezervace_do`,
                                                            `stav`,`pocet_osob`,`celkova_cena`,`zbyva_zaplatit`,`kurz_eur`,`poznamky`,`poznamky_tajne`,`doprava`,`stravovani`,`ubytovani`,`security_code`," . $terminy . "`pocet_noci`,`zpusob_uhrady`  $provize_header)
                                                    VALUES
                                                             (" . $this->id_klient . "," . $val_agentury . "" . $this->id_serial . "," . $this->id_zajezd . ",'" . Date("Y-m-d H:i:s") . "','" . $this->rezervace_do . "',
                                                             " . $this->stav . "," . $this->pocet_osob . "," . $this->celkova_cena . "," . $this->celkova_cena . ",". $this->centralni_data["kurz EUR"].",'" . $this->poznamky . "','" . $this->src_web . "','" . $this->doprava . "','" . $this->stravovani . "','" . $this->ubytovani . "',
                                                             '" . sha1(mt_rand() . $this->id_klient) . "'," . $val_terminy . "" . $this->pocet_noci . " , '".$this->zpusob_platby."' $provize_values )";
              //  }
                return $dotaz;
            } else if ($typ_pozadavku == "create_objednavajici") {
                $dotaz = "INSERT INTO `user_klient` 
                                                            (`jmeno`,`prijmeni`,`datum_narozeni`,`email`,`telefon`,`ulice`,`mesto`,`psc`,`rodne_cislo`,`cislo_pasu`)
                                                    VALUES
                                                             ('" . $this->jmeno . "','" . $this->prijmeni . "','" . $this->change_date_cz_en($this->datum_narozeni) . "','" . $this->email . "',
                                                             '" . $this->telefon . "','" . $this->ulice . "','" . $this->mesto . "','" . $this->psc . "','" . $this->rodne_cislo . "','" . $this->cislo_pasu . "' )";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "get_agentury") {
                //$uzivatel = User::get_instance();
                $dotaz = "select `organizace`.`id_organizace`,`nazev`,`ico`,`email`,`telefon`,
                                                    `ulice`,`mesto`,`psc`,`organizace_email`.`poznamka`
                                            from `organizace`
                                                left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1) 
                                                left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0) 
                                                left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0) 

                                            where `organizace`.`id_organizace`=" . $this->id_agentury . "";

                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "smluvni_podminky") {
                $dotaz = "Select * from `dokument` 
                                                    where `id_dokument` = " . $this->id_smluvni_podminky . "
                                                    Limit 1
                                                    ";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "sablona_objednavka") {
                $dotaz = "Select * from `sablony` 
                                                    where `id_sablony` = " . $this->id_sablony_objednavka . "
                                                    Limit 1
                                                    ";
                //echo $dotaz;
                return $dotaz;
            } else if ($typ_pozadavku == "sablona_zobrazit") {
                $dotaz = "Select * from `sablony` 
                                                    where `id_sablony` = " . $this->id_sablony_zobrazit . "
                                                    Limit 1
                                                    ";
                //echo $dotaz;
                return $dotaz;
            }
        }	 
                //pokud existuje a je prihlasena, zobrazim informace o agenture
        public static function show_agentura_informace($id_agentury){
              if($id_agentury!=""){
                    $dotaz= "select `organizace`.`id_organizace`,`nazev`,`ico`,`email`,`telefon`,
						`ulice`,`mesto`,`psc`,`organizace_email`.`poznamka`
					from `organizace`
                                            left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1) 
                                            left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0) 
                                            left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0) 
					where `organizace`.`id_organizace`=".$id_agentury."";
                    $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                    while ($agentura = mysqli_fetch_array($data)) {
                        $info_agentura = "
						<table class=\"sluzby\"  cellpadding=\"3\" cellspacing=\"0\" style=\"width:100%\">
							<tr>
								<th style=\"background-color:#0070ca;color:white;border:1px solid #0080da;\">
									<strong>�daje cestovn� agentury:</strong>
                                                                </th>
                                                        </tr><tr>
                                                                <td style=\"background-color:#fafaea; border:1px solid #0080da;\">
									<p style=\"margin:0 5px 5px 20px;\">
									<strong style=\"font-size: 1.2em;\">".$agentura["nazev"]."</strong>; ".$agentura["ulice"].", ".$agentura["mesto"].", ".$agentura["psc"]." <br/>
									telefon: ".$agentura["telefon"]." <br/>
									e-mail: ".$agentura["email"]." <br/>
									</p>
								</td>
							</tr>
						</table>
					";
                    }
                    return $info_agentura;
              }
        }

/**
         * zjisti zda lze povolit platbu kartou nebo ne (zajezd nesmi byt vyprodany, termin neni v blackdays atd)
         * @return type boolean
         */
   function show_platba_kartou(){
       //kontrola blackdays
       $objednano_blackdays = 0;
       $objednano_na_dotaz = 0;
       $objednano_vyprodano = 0;
       if($this->upresneni_terminu_od!=""){
            
            $data_blackdays = $this->database->query($this->create_query("get_blackdays"))
               or $this->chyba("Chyba p�i dotazu do datab�ze blackdays: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            while ($row = mysqli_fetch_array($data_blackdays)) {
                $objednano_blackdays = 1;
                return false; //staci prvni chyba, abychom platbu kartou nepovolili
            }
       }
       $i=1;
       while($i <= 100 and $i <= intval($_POST["pocet_cen"])){
           if($_POST["cena_pocet_".$i]>0){
               //zkontrolujeme, jestli je cena vyprodana nebo na dotaz
               $this->current_cena = intval($_POST["id_cena_".$i]);
               //echo $this->create_query("get_current_cena");
               $data_cena = $this->database->query($this->create_query("get_current_cena"))
                  or $this->chyba("Chyba p�i dotazu do datab�ze blackdays: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
               while ($row_cena = mysqli_fetch_array($data_cena)) {
                    if($row_cena["vyprodano"]=="1" or $row_cena["na_dotaz"]=="1" or $row_cena["objekt_na_dotaz"]=="1" or $row_cena["objekt_na_dotaz"]=="1"  ){
                        return false;
                    }
                    if($row_cena["objekt_kapacita_bez_omezeni"]!="1" and  $row_cena["kapacita_bez_omezeni"]!="1"){
                        //nemame neomezenou kapacitu
                        if(intval($row_cena["objekt_kapacita_volna"]) <= 0 and intval($row_cena["kapacita_volna"]) <= 0 ){
                            return false;
                        }                        
                    }
                }
           }
           $i++;
       }
       return true;  
  }        
	
	/**zobrazeni formulare pro treti cast objednavky*/
   function show_form_platby($celkova_castka){
      $dotaz = "SELECT * FROM `centralni_data` WHERE 1  ";
      $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
      $centralni_data = array();
      while ($row = mysqli_fetch_array($data)) {
          $centralni_data[$row["nazev"]]=$row["text"];
      }
      $show_platba_kartou = $this->show_platba_kartou();
      if(!$show_platba_kartou){
          $karta_enabled = "disabled=\"disabled\""  ;

          $karta_text = "Platbu kartou bohu�el nelze realizovat, proto�e n�kter� ze slu�eb, kter� objedn�v�te jsou vyprodan�, pouze na dotaz, specifikovali jste term�n mimo rozsah z�jezdu, nebo je z jin�ho d�vodu nutn�, aby dostupnost z�jezdu nejprve potvrdil pracovn�k CK.<br/>";
      }else{
          $karta_enabled = "";
          $karta_text = "Online platba kartou. Po dokon�en� objedn�vky budete p�esm�rov�ni na platebn� br�nu AGMO, a.s.";
      }
      $script="
          <script type=\"text/javascript\">
          $(\"#platba_hotove_info\").click(function(){
              $(\"#hidden_platba_hotove\").toggle(\"blind\", 500);
              return false;
          });
          $(\"#platba_prevodem_info\").click(function(){
              $(\"#hidden_platba_prevodem\").toggle(\"blind\", 500);              
              return false;
          });
          $(\"#platba_prevodem_sk_info\").click(function(){
              $(\"#hidden_platba_prevodem_sk\").toggle(\"blind\", 500);
              return false;
          });
          $(\"#platba_kartou_info\").click(function(){
              $(\"#hidden_platba_kartou\").toggle(\"blind\", 500);
              return false;
          });
          $(\"#platba_slozenkou_info\").click(function(){
              $(\"#hidden_platba_slozenkou\").toggle(\"blind\", 500);
              return false;
          });          
          </script>
          <script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>
                <script>
                if(google.loader.ClientLocation){
                    visitor_countrycode = google.loader.ClientLocation.address.country_code;
                    if(visitor_countrycode!=\"CZ\"){
                        document.getElementById('prevod_slovensko').style.display = 'table-row';
                        document.getElementById('castka_euro').style.display = 'inline';
                    }else{
                        document.getElementById('prevod_slovensko').style.display = 'none';
                    }
                }
                </script>          
          ";
      $form = "
          <table class=\"sluzby\" style=\"width:100%;\"  cellpadding=\"2\" cellspacing=\"2\">
            <tr><th colspan=\"2\"><strong>Zp�sob platby:</strong></th></tr>
            <tr><td><img src=\"/img/platby/hotove.gif\" style=\"border:none;float:left;\" alt=\"Platba hotov�\"/>
                <input name=\"zpusob_platby\" id=\"platba_hotove\" type=\"radio\" value=\"hotove\" ".($_REQUEST["zpusob_platby"]=="hotove" ? "checked=\"checked\"" : "")." /> 
                Hotov� <a href=\"#\" id=\"platba_hotove_info\" style=\"border:none\"><img src=\"/img/platby/info.gif\"  alt=\"Informace o zp�sobu platby\" style=\"border:none\"/></a>
                <div id=\"hidden_platba_hotove\" style=\"display:none;clear:left;\">
                <b>Platbu hotov� m��ete prov�st na na�ich pobo�k�ch v Praze, Slan�m, Roudnici nad Labem</b><br/>
                <b>PRAHA 7 - Hole�ovice</b>: Veletr�n� 48, Praha 7, tel/fax: 224217521<br/>
                <b>SLAN�</b>: Wilsonova 597, Slan�, tel/fax: 312524174<br/>      
                <b>ROUDNICE NAD LABEM</b>: T��da T.G. Masaryka 989, Roudnice n. L., tel/fax: 416838914<br/>
                <b>Obchodn� zastoupen� BRNO - Agentura Zabloudil</b>: Lidick� 4, Brno<br/><br/>
                </div>
                
            <tr><td><img src=\"/img/platby/prevodem.gif\" style=\"border:none;float:left;\" alt=\"Platba bankovn�m p�evodem\"/>
            <input name=\"zpusob_platby\" type=\"radio\" value=\"prevodem\" ".($_REQUEST["zpusob_platby"]=="prevodem" ? "checked=\"checked\"" : "")." /> 
              Bankovn�m p�evodem  <a href=\"#\" id=\"platba_prevodem_info\" style=\"border:none\"><img src=\"/img/platby/info.gif\"  alt=\"Informace o zp�sobu platby\" style=\"border:none\"/></a>
                <div id=\"hidden_platba_prevodem\" style=\"display:none;clear:left;\">Platbu p�evodem m��ete prov�st na n� ��et <b>19-6706930207 / 0100</b>. Jako variabiln� symbol pros�m uve�te ��slo objedn�vky (��slo objedn�vky naleznete v potvrzovac�m e-mailu, kter� V�m za�leme po dokon�en� Va�� objedn�vky).<br/></div>          
              
            <tr id=\"prevod_slovensko\" style=\"dislay:none;\"><td><img src=\"/img/platby/prevodem.gif\" style=\"border:none;float:left;\" alt=\"Platba bankovn�m p�evodem Slovensko\"/>
            <input name=\"zpusob_platby\" type=\"radio\" value=\"prevodem_sk\" ".($_REQUEST["zpusob_platby"]=="prevodem" ? "checked=\"checked\"" : "")." /> 
              Bankovn�m p�evodem v eurech na Slovensku  <a href=\"#\" id=\"platba_prevodem_sk_info\" style=\"border:none\"><img src=\"/img/platby/info.gif\"  alt=\"Informace o zp�sobu platby\" style=\"border:none\"/></a>
                <div id=\"hidden_platba_prevodem_sk\" style=\"display:none;clear:left;\"><b>Pro klienty ze Slovenska je mo�n� zaplatit z�jezd v eurech na n� ��et u V�B.</b><br/>
                    Celkov� cena z�jezdu v eurech je <b>".round($celkova_castka/$centralni_data["kurz EUR"])." EUR</b>.<br/>
                    Platbu prove�te na ��et <b>".$centralni_data["platebni_spojeni:slovensko"]."</b>. Jako variabiln� symbol pros�m uve�te ��slo objedn�vky (��slo objedn�vky naleznete v potvrzovac�m e-mailu, kter� V�m za�leme po dokon�en� Va�� objedn�vky).<br/></div>          
  
              
            <tr ><td><img src=\"/img/platby/kartou.gif\" style=\"border:none;float:left;\" alt=\"Platba kartou\"/>
            <input $karta_enabled name=\"zpusob_platby\" type=\"radio\" value=\"".EnumPaymentMethods::METHOD_CARD_ALL."\" ".($_REQUEST["zpusob_platby"]=="".EnumPaymentMethods::METHOD_CARD_ALL."" ? "checked=\"checked\"" : "")." /> 
              Platebn� kartou <a href=\"#\" id=\"platba_kartou_info\" style=\"border:none\"><img src=\"/img/platby/info.gif\"  alt=\"Informace o zp�sobu platby\" style=\"border:none\"/></a>
                <div id=\"hidden_platba_kartou\" style=\"display:none;clear:left;\">$karta_text </div>
                  
            <tr><td><img src=\"/img/platby/slozenkou.gif\" style=\"border:none;float:left;\" alt=\"Platba po�tovn� pouk�zkou\"/>
            <input name=\"zpusob_platby\" type=\"radio\" value=\"poukazkou\" ".($_REQUEST["zpusob_platby"]=="poukazkou" ? "checked=\"checked\"" : "")." /> 
              Po�tovn� pouk�zkou <a href=\"#\" id=\"platba_slozenkou_info\" style=\"border:none\"><img src=\"/img/platby/info.gif\"  alt=\"Informace o zp�sobu platby\" style=\"border:none\"/></a>
                <div id=\"hidden_platba_slozenkou\" style=\"display:none;clear:left;\"><b>Adresa pro zasl�n� p��slu�n� ��stky</b><br/>
                SLAN tour s.r.o.<br/>
                Wilsonova 597<br/>
                Slan�, 27401</div>
          </table>
                ".$script;
	
      return $form;
        }	
        
	/**zobrazeni formulare pro prvni cast objednavky*/
		public function show_form_kontaktni_informace(){
			$serial = new Serial_with_zajezd($_GET["lev1"],$_GET["lev2"]);
                        $serial->create_ceny();
			if(!$_SESSION["id_klient"]){
				$povinny_email = "<span class=\"red\">*</span>";
                                $hlaska_ucastnik = "(Za�krtn�te, pokud se vy osobn� z��astn�te z�jezdu - nejedn� se nap�. o d�rek)";
			}else{
				$povinny_email = ""; 
                                $hlaska_ucastnik = "(Za�krtn�te, pokud se objedn�vaj�c� z��astn� z�jezdu)";
                                
			}	
                        $dotaz = "SELECT * FROM `centralni_data` WHERE 1  ";
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                        $centralni_data = array();
                        while ($row = mysqli_fetch_array($data)) {
                            $centralni_data[$row["nazev"]]=$row["text"];
                        }   
                       
			$klient = "<tr><th colspan=\"2\"><strong>Objedn�vaj�c�:</strong></th></tr>
							<tr><td width=\"110\">&nbsp;&nbsp;Jm�no: <span class=\"red\">*</span></td><td><input onChange=\"set_prvni_osoba()\"  name=\"jmeno\" type=\"text\" value=\"".$_POST["jmeno"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;P��jmen�: <span class=\"red\">*</span></td><td><input onChange=\"set_prvni_osoba()\"  name=\"prijmeni\" type=\"text\" value=\"".$_POST["prijmeni"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;Datum narozen�: ".$povinny_email."</td><td><input onChange=\"set_prvni_osoba()\"  name=\"datum_narozeni\" type=\"text\" value=\"".$_POST["datum_narozeni"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;E-mail: ".$povinny_email."</td><td><input onChange=\"set_prvni_osoba()\"  name=\"email\" type=\"text\" value=\"".$_POST["email"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;Telefon:<span class=\"red\">*</span></td><td><input onChange=\"set_prvni_osoba()\"  name=\"telefon\" type=\"text\" value=\"".$_POST["telefon"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;Ulice a �P: ".$povinny_email."</td><td><input onChange=\"set_prvni_osoba()\"  name=\"ulice\" type=\"text\" value=\"".$_POST["ulice"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;M�sto: <span class=\"red\">*</span></td><td><input onChange=\"set_prvni_osoba()\"  name=\"mesto\" type=\"text\" value=\"".$_POST["mesto"]."\" /></td></tr>
							<tr><td>&nbsp;&nbsp;PS�: ".$povinny_email."</td><td><input onChange=\"set_prvni_osoba()\"  name=\"psc\" type=\"text\" value=\"".$_POST["psc"]."\" /></td></tr>
                                                            <tr><td>&nbsp;&nbsp;Objedn�vaj�c� je ��astn�k z�jezdu: </td><td><input name=\"objednavajici_je_ucastnik\" onChange=\"set_prvni_osoba()\" value=\"1\" checked=\"checked\" type=\"checkbox\" ".(($_POST["objednavajici_je_ucastnik"]==1)?("checked=\"checked\""):(""))."\" />
                                                            <tr><td colspan=\"2\">$hlaska_ucastnik</td></tr> 
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


			$poznamky = "<tr><td valign=\"top\"><strong>Pozn�mky:</strong></td><td><textarea name=\"poznamky\" type=\"text\"  cols=\"17\" rows=\"6\">".$_POST["poznamky"]."</textarea></td></tr>\n";
                        
			$vystup="<script  type=\"text/javascript\">
                               function set_prvni_osoba(){
				var x = document.getElementsByName(\"objednavajici_je_ucastnik\");
                                var pocet = x[0].checked;
                                if(pocet == true){
                                     x = document.getElementsByName(\"jmeno\");
                                    var jmeno = x[0].value;
                                     x = document.getElementsByName(\"prijmeni\");
                                    var prijmeni = x[0].value; 
                                     x = document.getElementsByName(\"datum_narozeni\");
                                    var datumNarozeni = x[0].value;             
                                     x = document.getElementsByName(\"telefon\");
                                    var telefon = x[0].value;                                   
                                pole_form = \"jmeno_1\";
                                 y = document.getElementsByName(pole_form);
                                y[0].value = jmeno;
                                
                                 pole_form = \"prijmeni_1\";
                                 y = document.getElementsByName(pole_form);
                                y[0].value = prijmeni;
                                
                                 pole_form = \"datum_narozeni_1\";
                                 y = document.getElementsByName(pole_form);
                                y[0].value = datumNarozeni;                                
                                
                                 pole_form = \"telefon_1\";
                                 y = document.getElementsByName(pole_form);
                                y[0].value = telefon;
                                
                                }
                                    }
                                </script>
				 <table width=\"100%\">
                                   <tr>
                                      <td>
                                        <table class=\"sluzby\"  cellpadding=\"2\" cellspacing=\"2\" width=\"300\">
							".$klient.$poznamky."
                                        </table>
                                      </td><td valign=\"top\">
                                        <table class=\"sluzby\"  cellpadding=\"2\" cellspacing=\"2\" width=\"400\" style=\"margin-left:5px;\">
						<tr><th colspan=\"2\"><strong>Objedn�vka - rekapitulace:</strong></th></tr>
                                                <tr><td>Z�jezd <td>".$_POST["nazev_zajezdu"]."</td>
                                                <tr><td>Term�n<td>".(($serial->get_dlouhodobe_zajezdy())?($_POST["upresneni_terminu_od"]." - ".$_POST["upresneni_terminu_do"]):($serial->get_termin_od()." - ".$serial->get_termin_do()))."

                                                <tr><td colspan=\"2\">".$serial->get_ceny()->show_rekapitulace_objednavka()."
                                                <tr><td>Celkov� cena<td><strong>".$serial->get_ceny()->get_celkova_castka()." K� 
                                                    <span id=\"castka_euro\" style=\"display:none\">/ ".round($serial->get_ceny()->get_celkova_castka()/$centralni_data["kurz EUR"])." EUR</span></strong>
                                                  
                                        </table>
                                      </td>
                                   </tr>
                                </table>
				<table class=\"sluzby\"  cellpadding=\"2\" cellspacing=\"2\">
							".$dalsi_osoby."
				</table>	
                                ".$this->show_form_platby($serial->get_ceny()->get_celkova_castka())." 
				<input type=\"submit\" name=\"submit_kontakty\" value=\"ODESLAT OBJEDN�VKU &gt;&gt;\" />

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
		 	or $this->chyba("Chyba p�i dotazu do datab�ze z�jezd: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		//ziskani id_smluvnich podminek
		$this->id_smluvni_podminky = $zajezd["id_smluvni_podminky"];			
			//z�sk�n� dokumentu se smluvn�mi podm�nkami
			$data_smluvni_podminky=$this->database->query($this->create_query("smluvni_podminky"))
		 		or $this->chyba("Chyba p�i dotazu do datab�ze smluvn� podm�nky: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
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
		 	or $this->chyba("Chyba p�i dotazu do datab�ze objednan� osoby: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
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
					<br/>M�m z�jem o zas�l�n� aktu�ln�ch nab�dek CK: <input type=\"checkbox\" name=\"novinky\"  value=\"ano\"/></p>
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
	
        function get_id_objednavka(){return $this->id_objednavka;}
        function get_email(){return $this->email;}
        function get_castka(){return $this->castka_k_zaplaceni;}
	function get_pocet_cen(){return $this->pocet_cen;}
	function get_pocet_osob(){return $this->pocet_osob;}
        function get_varovna_zprava(){return $this->varovna_zprava;}
} 

?>