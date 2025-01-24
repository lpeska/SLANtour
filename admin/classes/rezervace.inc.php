<?php

/**
 * rezervace.inc.php - tridy pro zobrazeni rezervace - obecných informcí
 */
/* --------------------- SERIAL ------------------------------------------- */
class Rezervace extends Generic_data_class {

    //vstupni data
    protected $typ_pozadavku;
    protected $id_zamestnance;
    protected $id_organizace;
    protected $id_objednavajici_organizace;
    protected $id_objednavka;
    protected $id_klient;
    protected $id_serial;
    protected $id_zajezd;
    protected $id_prodejce;
    protected $rezervace_do;
    protected $stav;
    protected $minuly_stav;
    protected $pocet_osob;
    protected $celkova_cena;
    protected $zbyva_zaplatit;
    protected $termin_od;
    protected $termin_do;
    protected $pocet_noci;
    protected $seznam_cen;
    protected $poznamky;
    protected $poznamky_tajne;
    protected $stare_sluzby;
    protected $doprava;
    protected $stravovani;
    protected $sluzby_array;
    
    protected $sleva_nazev;
    protected $sleva_velikost;
    protected $sleva_castka;
    protected $provize_suma;
    protected $provize_sdph;
    protected $provize_text;
    protected $data;
    protected $rezervace;
    protected $smluvPodm;
    protected $ceny;
    protected $storno_poplatek;
    protected $storno_datum;
    protected $k_uhrade_celkem;
    protected $k_uhrade_zaloha;
    protected $k_uhrade_doplatek;
    protected $k_uhrade_celkem_datspl;
    protected $k_uhrade_zaloha_datspl;
    protected $k_uhrade_doplatek_datspl;

    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /* konstruktor tøídy na základì typu požadavku a formularovych poli
      -	konstruktor zaroven vola funkce pro upravu kapacit sluzeb */

    function __construct(
    $typ_pozadavku, $id_zamestnance, $id_objednavka, $id_klient = "", $id_serial = "", $id_zajezd = "", $rezervace_do = "", $stav = "", $pocet_osob = "", $celkova_cena = "", $poznamky = "", $termin_od = "", $termin_do = "", $pocet_noci = "", $nazev_slevy = "", $velikost_slevy = 0, $castka_slevy = 0, $nazev_provize = "", $sdph_provize = 0, $suma_provize = 0, $storno_poplatek = 0
    ) {
        $zamestnanec = User_zamestnanec::get_instance();
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        
        
        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_klient = $this->check_int($id_klient);
        $this->id_objednavka = $this->check_int($id_objednavka);
        
        $this->id_objednavajici_organizace = $this->check_int($_POST["organizace"]);
        
        $this->id_serial = $this->check_int($id_serial);
        $this->id_zajezd = $this->check_int($id_zajezd);        

        $this->rezervace_do = CommonUtils::engDate($this->check($rezervace_do));

        $this->stav = $this->check_int($stav);
        $this->pocet_osob = $this->check_int($pocet_osob);
        $this->celkova_cena = $this->check_int($celkova_cena);

        $this->poznamky = $this->check_slashes($this->check($poznamky));
        
        $this->doprava = $this->check($_POST["doprava"]);
        $this->stravovani = $this->check($_POST["stravovani"]);
        $this->ubytovani = $this->check($_POST["ubytovani"]);
        $this->pojisteni = $this->check($_POST["pojisteni"]);

        $this->k_uhrade_celkem = $this->check_int($_POST["k_uhrade_celkem"]);
        $this->k_uhrade_zaloha = $this->check_int($_POST["k_uhrade_zaloha"]);
        $this->k_uhrade_doplatek = $this->check_int($_POST["k_uhrade_doplatek"]);
        $this->k_uhrade_celkem_datspl = CommonUtils::engDate($this->check($_POST["k_uhrade_celkem_datspl"]));
        $this->k_uhrade_zaloha_datspl = CommonUtils::engDate($this->check($_POST["k_uhrade_zaloha_datspl"]));
        $this->k_uhrade_doplatek_datspl = CommonUtils::engDate($this->check($_POST["k_uhrade_doplatek_datspl"]));
          
        //korekce dat
       if($this->typ_pozadavku == "create_new"){
           list($terminOd, $terminDo) = explode(" - ", $this->check($_REQUEST["termin"]));
           $this->termin_od = CommonUtils::engDate($this->check($terminOd));
           $this->termin_do = CommonUtils::engDate($this->check($terminDo));
           $this->id_organizace = $this->check_int($_REQUEST["agentura"]);
           $this->pocet_osob = $this->check_int($_REQUEST["pocet_osob"]);
           $this->poznamky_tajne = $this->check_slashes($this->check($_POST["poznamky_secret"]));
       }else{
            //nefunguje v nove rezervaci
            $this->id_organizace = $this->check_int($_GET["id_organizace"]);
            $this->termin_od = CommonUtils::engDate($this->check($termin_od));
            $this->termin_do = CommonUtils::engDate($this->check($termin_do));
            
            $this->sleva_nazev = $this->check($nazev_slevy);
            $this->sleva_castka = $this->check($castka_slevy);
            $this->sleva_velikost = $this->check($velikost_slevy);  
            $this->poznamky_tajne = $this->check_slashes($this->check($_POST["poznamky_tajne"]));
       }

        $data = $this->database->query($this->create_query("select_zajezd")) or $this->chyba("Chyba pøi dotazu do databáze");
        $result_serial = mysqli_fetch_array($data);
        
        $this->get_data_sluzby();
        $this->get_data_slevy();
        $this->get_data_casova_sleva();
        
        $this->pocet_noci = $this->calculate_pocet_noci($result_serial["od"], $result_serial["do"], $this->termin_od, $this->termin_do);

        $this->provize_suma = $this->check_int($suma_provize);
        $this->provize_sdph = $this->check_int($sdph_provize);
        $this->provize_text = $this->check($nazev_provize);
        $this->storno_poplatek = $this->check_int($storno_poplatek);
        if ($this->stav == Rezervace_library::$STAV_STORNO)
            $this->storno_datum = date('Y-m-d');
        else
            $this->storno_datum = "0000-00-00";        
        $subpozadavek = "";
        
        if($this->typ_pozadavku == "update" and $_POST["submit_zmena"]=="Pokraèovat" and $_POST["typ_zmeny"]=="zmenit_serial"){
            $subpozadavek = "zmena_serialu";            
        }else if($this->typ_pozadavku == "update" and $_POST["submit_zmena"]=="Pokraèovat" and $_POST["typ_zmeny"]=="zmenit_zajezd"){
            $subpozadavek = "zmena_zajezdu";        
        }    
        if($this->typ_pozadavku == "update" and $_GET["pozadavek"]=="change_serial"){
            //zmena serialu byla provedena v minulem kroku, nyni je treba zmenit zajezd
            $subpozadavek = "zmena_zajezdu";  
            $_POST["typ_zmeny"] = "zmenit_zajezd";
        }    
        //pokud mam dostatecna prava pokracovat                
        if ($this->legal($this->typ_pozadavku, $subpozadavek) and $this->correct_data($this->typ_pozadavku, $subpozadavek)) {
            
            
            if($this->typ_pozadavku == "update" and ($_POST["submit_zmena"]=="Pokraèovat" or $_GET["pozadavek"]=="change_serial")){
                //tady chceme zmenit zajezd nebo serial
                $this->castka_storno = $this->check_int($_POST["storno_poplatek_zmena"]);
               // print_r($_POST);
                
                $this->typ_zmeny = $this->check($_POST["typ_zmeny"]);
                
                if($_GET["pozadavek"]=="change_serial"){
                    //uz jsme predtim menili serial, nova hodnota bude ulozena v $this->id_serial
                    $id_serial = $this->id_serial;
                }else{
                    $data_objednavka = Rezervace::get_objednavka_info($this->id_objednavka);                
                    $id_serial = $data_objednavka["id_serial"];  
                }
                
                
                if($this->typ_zmeny == "zmenit_zajezd"){
                   // echo "zmenit zajezd";
                    echo $this->show_form_change_zajezd($id_serial, "objednavka_change_zajezd", "show");

                }else if($this->typ_zmeny == "zmenit_serial"){
                    echo $this->show_form_change_serial($id_serial, "objednavka_change_serial", "show");
                    //$serial = new Serial_list("", "", "", "", $zacatek, $order_by, "objednavka_change_serial");
                   // echo "zmenit serial";
                }
            }else if ($this->typ_pozadavku == "add_agentura" and $this->id_organizace > 0) {
                $data = $this->database->query($this->create_query("add_agentura"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                
            }else if ($this->typ_pozadavku == "delete_agentura") {
                $data = $this->database->query($this->create_query("delete_agentura"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni)); 
                
            }else if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "create_new" or $this->typ_pozadavku == "update") {

                //zacatek transakce
                $this->database->start_transaction();

                if ($this->typ_pozadavku == "update") {
                    $data_objednavka = mysqli_fetch_array($this->database->transaction_query($this->create_query("select_objednavka"))) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $this->zbyva_zaplatit = $data_objednavka["zbyva_zaplatit"] + $this->celkova_cena - $data_objednavka["celkova_cena"];
                    $this->minuly_stav = $data_objednavka["stav"];
                    $this->id_serial = $data_objednavka["id_serial"];
                    $this->id_zajezd = $data_objednavka["id_zajezd"];
                    $this->pocet_noci = $this->pocet_noci == "" ? $data_objednavka["pocet_noci"] : $this->pocet_noci;
                }

                $this->data = $this->database->transaction_query($this->create_query($this->typ_pozadavku)) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

              //chybi: pridat existujici slevy a pridat casove slevy
                if ($this->typ_pozadavku == "create_new") {
                    $this->id_objednavka = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                    $_REQUEST["probihajici_transakce"] = 1;
                    //musím vytvoøit objednávku cen
                    if (!$this->get_error_message()) {

                        //insert do tabulky cen - sluzby prirazene k zajezdu
                        $dotaz_cena = new Rezervace_cena(
                                        "create", $this->id_zamestnance, $this->id_objednavka, $this->id_serial, $this->id_zajezd, "", MAX_CEN,
                                        $this->stav, $this->minuly_stav, 1
                        );
                        if (!$dotaz_cena->get_error_message()) {
                            $i = 0;
                            //tvorba casti dotazu pro jednotlive ceny
                            while ($i <= MAX_CEN and isset($_REQUEST["sluzba-id-".$i])) {
                                $id = intval($_REQUEST["sluzba-id-".$i]);
                                $pocet = intval($_REQUEST["sluzba-pocet-".$i]);
                                if($id > 0 and $pocet > 0 and $this->sluzby_array[$id]["id_cena"] > 0 ){
                                    //máme skuteènou cenu
                                    $castka = $this->sluzby_array[$id]["castka"];
                                    $mena = $this->sluzby_array[$id]["mena"];
                                    $use_pocet_noci = $this->sluzby_array[$id]["use_pocet_noci"];                                    
                                    $dotaz_cena->add_to_query($id, $pocet, $castka, $mena, $use_pocet_noci);
                                }
                                $i++;
                            }
                            //odeslani dotazu
                            $dotaz_cena->finish_query();
                        }
                        //preneseni chybove hlasky
                        if ($dotaz_cena->get_error_message()) {
                            $this->chyba($dotaz_cena->get_error_message());
                        }
                        
                        //insert do tabulky nove vytvorenych sluzeb
                        $i=0;
                        while ($i <= MAX_CEN and isset($_REQUEST["new-sluzba-name-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                            if(intval($_REQUEST["new-sluzba-pocet-".$i]) > 0){
                                $_POST["add_ceny2"] = "add_ceny2";
                                $_POST["nazev_ceny"] = $_REQUEST["new-sluzba-name-".$i];
                                $_POST["castka"] = $_REQUEST["new-sluzba-price-".$i];
                                $_POST["mena"] = "Kè";
                                $_POST["use_pocet_noci"] = false;
                                $_POST["pocet"] = $_REQUEST["new-sluzba-pocet-".$i];
                                $rezervace_cena = new Rezervace_cena("edit_ceny2", $zamestnanec->get_id(), $this->id_objednavka, "", "", "", "", "", "", 1);
                                if ($rezervace_cena->get_error_message()) {
                                    $this->chyba($rezervace_cena->get_error_message());
                                }
                            }
                             $i++;
                        }

                        //pridavam ucastniky_zajezdu
                        if($_REQUEST["objednavajici-ucastnikem"]==1){
                            $dotaz_osoba = new Rezervace_osoba("create", $zamestnanec->get_id(), $this->id_objednavka, $this->id_klient);
                            if ($dotaz_osoba->get_error_message()) {
                                $this->chyba($dotaz_osoba->get_error_message());
                            }
                        }
                        $i=0;
                        while ($i <= intval($_REQUEST["pocet_osob"]) and isset($_REQUEST["ucastnik-".$i]) ) {
                            if(intval($_REQUEST["ucastnik-".$i]) > 0){
                                $dotaz_osoba = new Rezervace_osoba("create", $zamestnanec->get_id(), $this->id_objednavka, intval($_REQUEST["ucastnik-".$i]));
                                if ($dotaz_osoba->get_error_message()) {
                                    $this->chyba($dotaz_osoba->get_error_message());
                                }
                            }
                             $i++;
                        }                        
                        
                        //insert do tabulky nove vytvorenych slev
                        $i=0;
                        while ($i <= MAX_CEN and isset($_REQUEST["new-sleva-name-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                            if($_REQUEST["new-sleva-name-".$i] != "" and intval($_REQUEST["new-sleva-price-".$i])!=0){
                                $nazev_slevy = $_REQUEST["new-sleva-name-".$i];
                                $castka = intval($_REQUEST["new-sleva-price-".$i]);
                                $typ = $_REQUEST["new-sleva-type-".$i];
                                switch ($typ) {
                                    case "type_procento":
                                        $mena = "%";
                                        break;
                                    case "type_kc_os":
                                        $mena = "Kè";
                                        break;
                                    case "type_kc":
                                        $mena = "Kè_direct";
                                        break;
                                }
                                 $dotaz_sleva = new Rezervace_sleva("create", $zamestnanec->get_id(), "", $this->id_objednavka, $nazev_slevy, $castka, $mena, "", "");

                                if ($dotaz_sleva->get_error_message()) {
                                    $this->chyba($dotaz_sleva->get_error_message());
                                }
                            }
                             $i++;
                        }
                        //insert jiz drive vytvorenych slev
                        $i=0;
                        while ($i <= MAX_CEN and isset($_REQUEST["sleva-klient-serial-id-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                            if(intval($_REQUEST["sleva-klient-serial-pocet-".$i]) > 0){
                                $id_slevy = intval($_REQUEST["sleva-klient-serial-id-".$i]);
                                $nazev_slevy = $this->slevy_array[$id_slevy]["nazev_slevy"];
                                $castka = $this->slevy_array[$id_slevy]["castka"];
                                $mena = $this->slevy_array[$id_slevy]["mena"];                                
                                 $dotaz_sleva = new Rezervace_sleva("create", $zamestnanec->get_id(), $id_slevy, $this->id_objednavka, $nazev_slevy, $castka, $mena, "", "");
                                 //echo "sleva ID:".$id_slevy;
                                // print_r($this->slevy_array);
                                if ($dotaz_sleva->get_error_message()) {
                                    $this->chyba($dotaz_sleva->get_error_message());
                                }
                            }
                             $i++;
                        }
                        $i=0;
                        while ($i <= MAX_CEN and isset($_REQUEST["sleva-klient-zajezd-id-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                            if(intval($_REQUEST["sleva-klient-zajezd-pocet-".$i]) > 0){
                                $id_slevy = $_REQUEST["sleva-klient-zajezd-id-".$i];
                                $nazev_slevy = $this->slevy_array[$id_slevy]["nazev_slevy"];
                                $castka = $this->slevy_array[$id_slevy]["castka"];
                                $mena = $this->slevy_array[$id_slevy]["mena"];                                
                                 $dotaz_sleva = new Rezervace_sleva("create", $zamestnanec->get_id(), $id_slevy, $this->id_objednavka, $nazev_slevy, $castka, $mena, "", "");
                                if ($dotaz_sleva->get_error_message()) {
                                    $this->chyba($dotaz_sleva->get_error_message());
                                }
                            }
                             $i++;
                        }
                        //zjisteni a insert casove slevy
                        if($this->casova_castka > 0){                         
                                 $dotaz_sleva = new Rezervace_sleva("create", $zamestnanec->get_id(), $this->casova_id, $this->id_objednavka, $this->casova_nazev, $this->casova_castka, $this->casova_mena, "", "");
                                if ($dotaz_sleva->get_error_message()) {
                                    $this->chyba($dotaz_sleva->get_error_message());
                                }
                            }
                        
                        
                        
                        if (!$this->get_error_message()) {
                            $this->database->commit(); //potvrdim transakci
                        }
                        //vytvareni e-mailu odeslaneho klientovi
                        if (!$this->get_error_message()) {
                            //odeslu klientovi e-mail s potvrzovacim kodem
                            $predmet = "Vytvoøení objednávky zájezdu";

                            $text = "<strong>Na základì Vašeho požadavku byla vytvoøena objednávka zájezdu:</strong><br/>";
                            $zbyva_zaplatit = "";

                            $data_objednavka = Rezervace::get_objednavka_info($this->id_objednavka);

                            
                            $objednavka = new Pdf_objednavka_prepare($this->id_objednavka, $data_objednavka["security_code"]);

                            $objednavka->create_pdf_objednavka();
                            $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                            $platby_celkem = $objednavka->get_splacene_platby_celkem();
                            //provedeme prepocet ceny sluzeb
                            Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $this->id_objednavka);
                        }
                    }
                }
                
                
                if ($this->typ_pozadavku == "create") {
                    $this->id_objednavka = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);

                    //musím vytvoøit objednávku cen
                    if (!$this->get_error_message()) {

                        //insert do tabulky cen
                        $dotaz_cena = new Rezervace_cena(
                                        "create", $this->id_zamestnance, $this->id_objednavka, $this->id_serial, $this->id_zajezd, "", $_POST["pocet_cen"],
                                        $this->stav, $this->minuly_stav, 1
                        );
                        if (!$dotaz_cena->get_error_message()) {
                            $i = 1;
                            //tvorba casti dotazu pro jednotlive ceny
                            while ($i <= $dotaz_cena->get_pocet()) {
                                $dotaz_cena->add_to_query($_POST["id_cena_" . $i], $_POST["pocet_" . $i], $_POST["castka_" . $i], $_POST["mena_" . $i], $_POST["use_pocet_noci_" . $i]);
                                $i++;
                            }
                            //odeslani dotazu
                            $dotaz_cena->finish_query();
                        }
                        //preneseni chybove hlasky
                        if ($dotaz_cena->get_error_message()) {
                            $this->chyba($dotaz_cena->get_error_message());
                        }

                        //vytvareni e-mailu odeslaneho klientovi
                        if (!$this->get_error_message()) {
                            //odeslu klientovi e-mail s potvrzovacim kodem
                            $predmet = "Vytvoøení objednávky zájezdu";

                            $text = "<strong>Na základì Vašeho požadavku byla vytvoøena objednávka zájezdu:</strong><br/>";
                            $zbyva_zaplatit = "";

                            $data_objednavka = Rezervace::get_objednavka_info($this->id_objednavka);

                            
                            $objednavka = new Pdf_objednavka_prepare($this->id_objednavka, $data_objednavka["security_code"]);

                            $objednavka->create_pdf_objednavka();
                            $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                            $platby_celkem = $objednavka->get_splacene_platby_celkem();
                            //provedeme prepocet ceny sluzeb
//                            Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["celkova_cena"], $data_objednavka["zbyva_zaplatit"], $this->id_objednavka);
                            Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);
                        }
                    }
                }//typ pozadavku = create or update

                if ($this->typ_pozadavku == "update") {
                    if ($this->stav == Rezervace_library::$STAV_STORNO || $this->stav == Rezervace_library::$STAV_PREDBEZNA_POPTAVKA || $this->stav == Rezervace_library::$STAV_POZADAVEK_NA_REZERVACI) { //možné storno objednávky
                        //zaloguj storno
//                        if ($this->stav == Rezervace_library::$STAV_STORNO) {
//                            //vytahni si potrebne info
//                            
//                            $this->database->transaction_query($this->create_query("LOG")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
//                        }
                        //uvolneni kapacit a storno cen
                        $dotaz_cena = new Rezervace_cena("storno_objednavky", $this->id_zamestnance, $this->id_objednavka, $this->id_serial, $this->id_zajezd, "", "", $this->stav, $this->minuly_stav, 1);
                        //preneseni chybove hlasky
                        if ($dotaz_cena->get_error_message()) {
                            $this->chyba($dotaz_cena->get_error_message());
                        }
                    } else if ($this->stav >= Rezervace_library::$STAV_OPCE and $this->stav <= Rezervace_library::$STAV_ODBAVENO) {//možná bude tøeba zarezervovat kapacity
                        //rezervace kapacit a vraceni stornovanych cen
                        $dotaz_cena = new Rezervace_cena("rezervace_kapacit", $this->id_zamestnance, $this->id_objednavka, $this->id_serial, $this->id_zajezd, "", "", $this->stav, $this->minuly_stav, 1);
                        //preneseni chybove hlasky
                        if ($dotaz_cena->get_error_message()) {
                            $this->chyba($dotaz_cena->get_error_message());
                        }
                    }
                    //vytvareni e-mailu odeslaneho klientovi
                    if (!$this->get_error_message()) {
                        //odeslu klientovi e-mail s potvrzovacim kodem
                        $predmet = "Zmìna objednávky zájezdu";
                        $text = "
									<strong>Vaše objednávka zájezdu byla zmìnìna:</strong><br/>";
                        $zbyva_zaplatit = "Zbývá zaplatit:	" . $this->zbyva_zaplatit . " Kè <br/>		";
                    }
                }
                if (!$this->get_error_message()) {
                    $this->database->commit(); //potvrdim transakci
                    //pokusim se odeslat e-mail klientovi o vytvoreni/zmene objednavky
                    /*     $data_klient = $this->database->query($this->create_query("select_klient"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $data_zajezd = $this->database->query($this->create_query("select_zajezd"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                    if (!$this->get_error_message() and mysqli_num_rows($data_klient) == 1 and mysqli_num_rows($data_zajezd) == 1) {
                        $klient = mysqli_fetch_array($data_klient);
                        $zajezd = mysqli_fetch_array($data_zajezd);

                        $rsck_email = AUTO_MAIL_EMAIL;
                        $rsck_jmeno = AUTO_MAIL_SENDER;
                        $text = $text . "
										Zájezd: <strong>" . $zajezd["nazev"] . "</strong> (" . $this->change_date_en_cz($zajezd["od"]) . " - " . $this->change_date_en_cz($zajezd["do"]) . ")<br/>
										Objednávající: <strong>Id " . $klient["id_klient"] . ", " . $klient["prijmeni"] . " " . $klient["jmeno"] . "</strong>, " . $this->change_date_en_cz($klient["datum_narozeni"]) . "<br/> <br/>
										Celková cena:	" . $this->celkova_cena . " Kè <br/>
										" . $zbyva_zaplatit . "
										Stav objednávky:	" . Rezervace_library::get_stav($this->stav - 1) . " <br/>
										Rezervace kapacit do:	" . CommonUtils::czechDate($this->rezervace_do) . " <br/>
										Poèet osob:	" . $this->pocet_osob . " <br/>
										Poznámky: " . $this->poznamky . "	 <br/><br/>
									Detail objednávky si mùžete prohlédnout po pøihlášení ke svému úètu na adrese " . $_SERVER['SERVER_NAME'] . ".<br/>
									Pokud ještì uživatelský úèet nemáte, mùžete si jej zøídit po kliknutí na odkaz nová registrace -> formuláø vytvoøit úèet (je tøeba zadat Vaše Id = " . $klient["id_klient"] . " )
										";
                        //odeslani emailu s dotazem
                        $mail = Send_mail::send($rsck_jmeno, $rsck_email, $klient["email"], $predmet, $text);

                          if(!$mail){
                          $this->chyba("Nepodaøilo se odeslat potvrzovací e-mail klientovi.");
                          } 
                    } else {
                        $this->chyba("Klient nenalezen!");
                    }*/
                }
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
            } else if ($this->typ_pozadavku == "delete") {
                //zacatek transakce
                $this->database->start_transaction();

                //nejprve smažu ceny objednávky a vratim pridelene kapacity
                $dotaz_cena = new Rezervace_cena(
                                "delete_objednavky", $zamestnanec->get_id(), $this->id_objednavka, $this->id_serial, $this->id_zajezd, "", "",
                                $this->stav, $this->minuly_stav, 1
                );
                //preneseni chybove hlasky
                if ($dotaz_cena->get_error_message()) {
                    $this->chyba($dotaz_cena->get_error_message());
                }
                //zjisteni zda je to objednavka exportovana z LOH adminu, pokud ano, musim nastavit NULL u exportu (jine prihlaseni do databaze - nelze nastavit cizi klice!!)
                $query = "SELECT `id_exportovana_objednavka`, `exportovana_objednavka` FROM `objednavka` WHERE `id_objednavka`=$this->id_objednavka ";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                while ($row = mysqli_fetch_array($data)) {
                    if($row["exportovana_objednavka"]==1){
                        $query_update_export = "UPDATE `objednavka` SET `id_exportovana_objednavka`=NULL, `exportovano`=NULL where `id_objednavka` = ".$row["id_exportovana_objednavka"]." ";
                    }else{
                        $query_update_export = "";
                    }
                }
                
                
                //smazani samotne objednavky
                if (!$this->get_error_message()) {
                    $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                }

                //smazani voucheru
                if (!$this->get_error_message()) {
                    if ($handle = opendir(VoucheryModelConfig::PDF_FOLDER)) {
                        while (false !== ($entry = readdir($handle))) {
                            $entryDump = explode("_", $entry);
                            if ($entryDump[0] == $this->id_objednavka) {
                                @unlink(VoucheryModelConfig::PDF_FOLDER . $entry);
                                echo "unlinking" . VoucheryModelConfig::PDF_FOLDER . $entry;
                            }
                        }
                        closedir($handle);
                    }
                }
                
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->database->commit(); //potvrdim transakci
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
                if($query_update_export != ""){
                    //prihlaseni k databazi na RIU a update objednávky
                    require "../global/prihlaseni_do_databaze_rio.inc.php";
                    $spojeni2 = mysqli_connect($db_server,$db_jmeno,$db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $res= mysqli_select_db($spojeni2, $db_nazev_db ) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
            		mysqli_query($spojeni2,"SET character_set_results=cp1250",$spojeni2); 
			mysqli_query($spojeni2,"SET character_set_connection=UTF8",$spojeni2);
			mysqli_query($spojeni2,"SET character_set_client=cp1250",$spojeni2);
                    mysqli_query($spojeni2,$query_update_export);   
                    mysqli_close($spojeni2);
                    require "../global/prihlaseni_do_databaze.inc.php";
                    $spojeni = mysqli_connect($db_server,$db_jmeno,$db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $res= mysqli_select_db($spojeni, $db_nazev_db ) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
            		mysqli_query($spojeni,"SET character_set_results=cp1250",$spojeni); 
			mysqli_query($spojeni,"SET character_set_connection=UTF8",$spojeni);
			mysqli_query($spojeni,"SET character_set_client=cp1250",$spojeni);
                }
                

                //pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
            } else if ($this->typ_pozadavku == "show") {
                //ziskam data o uzivateli                
                $this->data = $this->database->query($this->create_query("select_objednavka")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->rezervace = mysqli_fetch_array($this->data);

                //jednotlive sloupce ulozim do promennych tridy
                $this->id_objednavka = $this->rezervace["id_objednavka"];
                $this->exportovana = $this->rezervace["exportovana_objednavka"];
                $this->id_exportovana_objednavka = $this->rezervace["id_exportovana_objednavka"];
                
                $this->id_klient = $this->rezervace["id_klient"];
                $this->id_organizace = $this->rezervace["id_agentury"];
                $this->id_objednavajici_organizace = $this->rezervace["id_organizace"];
                $this->id_serial = $this->rezervace["id_serial"];
                $this->id_zajezd = $this->rezervace["id_zajezd"];
                $this->stav = $this->rezervace["stav"];
                $this->rezervace_do = $this->rezervace["rezervace_do"];
                $this->pocet_osob = $this->rezervace["pocet_osob"];
                $this->celkova_cena = $this->rezervace["celkova_cena"];
                $this->zbyva_zaplatit = $this->rezervace["zbyva_zaplatit"];
                $this->termin_od = $this->rezervace["termin_od"];
                $this->termin_do = $this->rezervace["termin_do"];
                $this->pocet_noci = $this->rezervace["pocet_noci"];
                $this->poznamky = $this->rezervace["poznamky"];
                $this->poznamky_tajne = $this->rezervace["poznamky_tajne"];
                $this->doprava = $this->rezervace["doprava"];
                $this->stravovani = $this->rezervace["stravovani"];
                $this->ubytovani = $this->rezervace["ubytovani"];
                $this->pojisteni = $this->rezervace["pojisteni"];
                $this->storno_poplatek = $this->rezervace["storno_poplatek"];
                $this->storno_poplatek_calc = $this->calc_storno();
                $this->k_uhrade_celkem = $this->rezervace["k_uhrade_celkem"];
                $this->k_uhrade_zaloha = $this->rezervace["k_uhrade_zaloha"];
                $this->k_uhrade_doplatek = $this->rezervace["k_uhrade_doplatek"];
                $this->k_uhrade_celkem_datspl = $this->rezervace["k_uhrade_celkem_datspl"];
                $this->k_uhrade_zaloha_datspl = $this->rezervace["k_uhrade_zaloha_datspl"];
                $this->k_uhrade_doplatek_datspl = $this->rezervace["k_uhrade_doplatek_datspl"];

                $data = $this->database->query($this->create_query("select_zajezd")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $data_serial = mysqli_fetch_array($data);
                
                if($data_serial["id_sablony_zobrazeni"]==12){
                    $this->nazev_serial =  $data_serial["nazev_ubytovani"] . " " . $data_serial["nazev"];
                }else{
                    $this->nazev_serial =  $data_serial["nazev"];
                }
                
            } else if($this->typ_pozadavku == "edit_stav") {
                $this->data = $this->database->query($this->create_query("edit_stav")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

    function get_data_sluzby(){
        $data = $this->database->query($this->create_query("select_sluzby")) or $this->chyba("Chyba pøi dotazu do databáze");
        while($result_sluzby = mysqli_fetch_array($data)){
            $this->sluzby_array[$result_sluzby["id_cena"]] = $result_sluzby;
        }
        
    }

    function get_data_slevy(){
        $this->slevy_array = array();
        $data = $this->database->query($this->create_query("select_slevy")) or $this->chyba("Chyba pøi dotazu do databáze");
        while($result_sluzby = mysqli_fetch_array($data)){
            $array = array("nazev_slevy"=>$result_sluzby["nazev_slevy"],"id_slevy"=>$result_sluzby["id_slevy"], "castka"=>$result_sluzby["castka"], "mena"=>$result_sluzby["mena"] );
            $id_slevy = $result_sluzby["id_slevy"];
            $this->slevy_array[$id_slevy] = $array;
        }        
    }
    function get_data_casova_sleva(){
        $data = $this->database->query($this->create_query("select_casova_sleva")) or $this->chyba("Chyba pøi dotazu do databáze");
        while($result_sluzby = mysqli_fetch_array($data)){
            $this->casova_id = $result_sluzby["id_slevy"];
            $this->casova_nazev = $result_sluzby["nazev_slevy"];
            $this->casova_mena = $result_sluzby["mena"];
            $this->casova_castka = $result_sluzby["castka"];
            break;
        }
        
    }
    
//------------------- METODY TRIDY -----------------
    /*     * vytvoreni dotazu na zaklade typu pozadavku */
    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "create" or $typ_pozadavku == "create_new" ) {
            if ($this->id_organizace > 0) {
                $name_agentury = "`id_agentury`,";
                $val_agentury = "" . $this->id_organizace . ",";
            } else {
                $name_agentury = "";
                $val_agentury = "";
            }
            if ($this->id_klient > 0) {
                $name_klient = "`id_klient`,";
                $val_klient = "" . $this->id_klient . ",";
            } else {
                $name_klient = "";
                $val_klient = "";
            }
            if ($this->id_objednavajici_organizace > 0) {
                $name_organizace = "`id_organizace`,";
                $val_organizace = "" . $this->id_objednavajici_organizace . ",";
            } else {
                $name_organizace = "";
                $val_organizace = "";
            }
                
            $dotaz = "INSERT INTO `objednavka`
							($name_klient $name_agentury $name_organizace `id_serial`,`id_zajezd`,`datum_rezervace`,`rezervace_do`,
							`stav`,`pocet_osob`,`celkova_cena`,`zbyva_zaplatit`,`poznamky`,`poznamky_tajne`,`doprava`,`stravovani`,`ubytovani`,`pojisteni`,`security_code`,`termin_od`,`termin_do`,`pocet_noci`,
							`id_user_create`,`id_user_edit`,`nazev_slevy`,`castka_slevy`,`velikost_slevy`,`provize_vc_dph`,`poznamka_provize`,`suma_provize`)
						VALUES
							 ($val_klient $val_agentury $val_organizace " . $this->id_serial . "," . $this->id_zajezd . ",'" . Date("Y-m-d H:i:s") . "','" . $this->rezervace_do . "',
							 " . $this->stav . "," . $this->pocet_osob . "," . $this->celkova_cena . "," . $this->celkova_cena . ",'" . $this->poznamky . "','" . $this->poznamky_tajne . "',
                                                             '" . $this->doprava . "','" . $this->stravovani . "','" . $this->ubytovani . "','" . $this->pojisteni . "','" . sha1(mt_rand() . $this->id_klient) . "', '$this->termin_od', '$this->termin_do'," . $this->pocet_noci . ",
							  " . $this->id_zamestnance . "," . $this->id_zamestnance . ",'" . $this->sleva_nazev . "','" . $this->sleva_castka . "','" . $this->sleva_velikost . "','" . $this->provize_sdph . "','" . $this->provize_text . "','" . $this->provize_suma . "')";
          //  echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "update") {
            if ($this->id_objednavajici_organizace > 0) {
                $organizace = "`id_organizace` = " . $this->id_objednavajici_organizace . "";
            } else {
                $organizace = "`id_organizace` = NULL";
            }
            
            $dotaz = "UPDATE `objednavka`
                        SET
                                 `rezervace_do`='" . $this->rezervace_do . "',`stav`=" . $this->stav . ",`pocet_osob`=" . $this->pocet_osob . ", $organizace ,
                                 `poznamky`='" . $this->poznamky . "',`poznamky_tajne`='" . $this->poznamky_tajne . "',`doprava`='" . $this->doprava . "',`stravovani`='" . $this->stravovani . "',`ubytovani`='" . $this->ubytovani . "',`pojisteni`='" . $this->pojisteni . "',`termin_od`='$this->termin_od',`termin_do`='$this->termin_do',`pocet_noci`=" . $this->pocet_noci . ",
                                 `id_user_edit`= " . $this->id_zamestnance . ", `provize_vc_dph`= '" . $this->provize_sdph . "', `poznamka_provize`= '" . $this->provize_text . "', `suma_provize`= '" . $this->provize_suma . "', `storno_poplatek`=$this->storno_poplatek, `storno_datum`='$this->storno_datum', `celkova_cena`=$this->celkova_cena,
                                 `k_uhrade_celkem` = '$this->k_uhrade_celkem', `k_uhrade_zaloha` = '$this->k_uhrade_zaloha', `k_uhrade_doplatek` = '$this->k_uhrade_doplatek', `k_uhrade_celkem_datspl` = '$this->k_uhrade_celkem_datspl', `k_uhrade_zaloha_datspl` = '$this->k_uhrade_zaloha_datspl', `k_uhrade_doplatek_datspl` = '$this->k_uhrade_doplatek_datspl'
                        WHERE `id_objednavka`=" . $this->id_objednavka . "
                        LIMIT 1";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "add_agentura") {
            $dotaz = "UPDATE `objednavka`
                        SET `id_agentury`=" . $this->id_organizace . ", `id_user_edit`= " . $this->id_zamestnance . "
                        WHERE `id_objednavka`=" . $this->id_objednavka . "
                        LIMIT 1";
           // echo $dotaz;
            return $dotaz;   
        } else if ($typ_pozadavku == "delete_agentura") {
            $dotaz = "UPDATE `objednavka`
                        SET `id_agentury`=null, `id_user_edit`= " . $this->id_zamestnance . "
                        WHERE `id_objednavka`=" . $this->id_objednavka . "
                        LIMIT 1";
           // echo $dotaz;
            return $dotaz;             
        } else if ($typ_pozadavku == "change_zajezd") {
            $dotaz = "UPDATE `objednavka`
                        SET
                                 `storno_poplatek`= `storno_poplatek` + " . $this->storno_poplatek . ", `id_serial`=" . $this->id_serial. ", `id_zajezd`=" . $this->id_zajezd . ",
                                 `poznamky`= concat(`poznamky`,\"<br/> Zájezd zmìnìn na ID serial:" . $this->id_serial. ", ID zajezd:" . $this->id_zajezd . ", úètován storno poplatek: " . $this->storno_poplatek . " Kè\"),    
                                 `id_user_edit`= " . $this->id_zamestnance . "
                        WHERE `id_objednavka`=" . $this->id_objednavka . "
                        LIMIT 1";
           // echo $dotaz;
            return $dotaz;    
        } else if ($typ_pozadavku == "change_serial") {
            $dotaz = "UPDATE `objednavka`
                        SET
                                 `id_serial`=" . $this->id_serial. ", `id_zajezd`=" . $this->id_zajezd . ",`id_user_edit`= " . $this->id_zamestnance . "
                        WHERE `id_objednavka`=" . $this->id_objednavka . "
                        LIMIT 1";
           // echo $dotaz;
            return $dotaz;             
            
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_storno") {
            $dotaz = "  SELECT sp.*
                                    FROM `objednavka` o 
                                        JOIN `serial` s ON (o.id_serial = s.id_serial)
                                        JOIN `smluvni_podminky_nazev` spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                                        JOIN `smluvni_podminky` sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                                    WHERE o.`id_objednavka`='$this->id_objednavka' AND sp.typ='storno' ORDER BY sp.prodleva DESC;";
//                        echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_objednavka") {
            $dotaz = "SELECT * FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_klient") {
            $dotaz = "SELECT `id_klient`,`jmeno`,`prijmeni`,`email`,`telefon`,`datum_narozeni`,`email`,`ulice`,`mesto`,`psc`
						FROM `user_klient`
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
          //  echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_objednavajici_org") {
            $dotaz = "SELECT o.nazev, o.ico, o.dic, oe.email, ot.telefon, oa.ulice, oa.mesto, oa.psc
						FROM organizace o
						  LEFT JOIN organizace_adresa oa ON (o.id_organizace = oa.id_organizace)
						  LEFT JOIN organizace_email oe ON (o.id_organizace = oe.id_organizace)
						  LEFT JOIN organizace_telefon ot ON (o.id_organizace = ot.id_organizace)
						WHERE o.id_organizace = " . $this->id_objednavajici_organizace . "
						LIMIT 1";
           // echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_sluzby") {
            $dotaz = "SELECT * FROM `cena`
                                                JOIN  `cena_zajezd` ON (`cena`.`id_cena` = `cena_zajezd`.`id_cena`) 
						WHERE `cena`.`id_serial`=" . $this->id_serial . " and `cena_zajezd`.`id_zajezd`=" . $this->id_zajezd . "
						";
//            echo $dotaz;
            return $dotaz;     
        } else if ($typ_pozadavku == "select_slevy") {
            $dotaz = "select `slevy`.* from `slevy` left join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy` and `slevy_serial`.`id_serial` = " . $this->id_serial . ")
                                            left join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy` and `slevy_zajezd`.`id_zajezd` = " . $this->id_zajezd . ")
                            where (`slevy_serial`.`platnost`=1 or `slevy_zajezd`.`platnost` =1 )
                                and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" )  
                            ";
//            echo $dotaz;
            return $dotaz;   
        } else if ($typ_pozadavku == "select_casova_sleva") {
            $dotaz = "select `slevy`.* from `slevy` left join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy` and `slevy_serial`.`id_serial` = " . $this->id_serial . ")
                                            left join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy` and `slevy_zajezd`.`id_zajezd` = " . $this->id_zajezd . ")
                            where (`slevy_serial`.`platnost`=1 or `slevy_zajezd`.`platnost` =1 )
                                and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" )  
                                and `sleva_staly_klient` = 0    
                            order by `slevy`.`castka` desc limit 1";
//            echo $dotaz;
            return $dotaz;               
        } else if ($typ_pozadavku == "select_zajezd") {
            $dotaz = "SELECT `serial`.*,`zajezd`.*, `objekt`.`id_objektu`  as `id_ubytovani`,`objekt`.`nazev_objektu` as `nazev_ubytovani`
						FROM `serial`
                                                JOIN  `zajezd` ON (`serial`.`id_serial` = `zajezd`.`id_serial`)
                                                        left join (`objekt_serial` join
                                                                    `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
						WHERE `serial`.`id_serial`=" . $this->id_serial . " and `zajezd`.`id_zajezd`=" . $this->id_zajezd . "
						LIMIT 1";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny") {
            $dotaz = "SELECT `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet`
						FROM `serial`
							JOIN  `cena` ON (`serial`.`id_serial` = `cena`.`id_serial`)
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
							LEFT JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=" . $this->id_objednavka . ")
						WHERE `serial`.`id_serial`=" . $this->id_serial . " and `cena_zajezd`.`id_zajezd`=" . $this->id_zajezd . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_sleva_old") {
            $dotaz = "SELECT `nazev_slevy`,`castka_slevy`,`velikost_slevy` FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_sleva") {
            $dotaz = "SELECT * FROM `objednavka_sleva`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_mozne_slevy") {
            $dotaz = "   select `slevy`.`id_slevy`, `slevy`.`castka`, `slevy`.`mena`,
							`slevy`.`nazev_slevy`,`slevy_serial`.`platnost`  
					  from `slevy_serial` join
							`slevy` on (`slevy`.`id_slevy` =`slevy_serial`.`id_slevy`) 
					where `slevy_serial`.`id_serial`= " . $this->id_serial . " 
                                    union all 
                                    select `slevy`.`id_slevy`, `slevy`.`castka`, `slevy`.`mena`,
							`slevy`.`nazev_slevy`,`slevy_zajezd`.`platnost`  
					  from `slevy_zajezd` join
							`slevy` on (`slevy`.`id_slevy` =`slevy_zajezd`.`id_slevy`) 
					where `slevy_zajezd`.`id_zajezd`= " . $this->id_zajezd . " 
				";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_provize") {
            $dotaz = "SELECT `poznamka_provize`,`suma_provize`,`provize_vc_dph` FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_prodejce") {
            $dotaz = "SELECT `organizace`.`nazev` as `nazev_agentury`,`organizace`.`ico`,
                                        `organizace_email`.`email`,`organizace_email`.`poznamka` as `kontaktni_osoba`,
                                        `organizace_telefon`.`telefon`,
                                        `stat`,`mesto`,`ulice`,`psc`,
                                        `nazev_banky`,`kod_banky`,`cislo_uctu`
                                    from `objednavka` 
                                     join `organizace` on (`organizace`.`id_organizace` = `objednavka`.`id_agentury`)
                                     left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1) 
                                     left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0) 
                                     left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0) 
                                     left join `organizace_www` on (`organizace`.`id_organizace` = `organizace_www`.`id_organizace` and `organizace_www`.`typ_kontaktu` = 0) 
                                     left join `organizace_bankovni_spojeni` on (`organizace`.`id_organizace` = `organizace_bankovni_spojeni`.`id_organizace` and `organizace_bankovni_spojeni`.`typ_kontaktu` = 1) 
				WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
				LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "edit_stav") {
            $dotaz = "UPDATE objednavka SET stav=$this->stav WHERE id_objednavka=$this->id_objednavka;";
            //echo $dotaz;
            return $dotaz;
        }
    }

    /*     * kontrola zda smi uzivatel provest danou akci */

    function legal($typ_pozadavku, $subpozadavek="") {
        $zamestnanec = User_zamestnanec::get_instance();
        //z jadra zjistim ide soucasneho modulu
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        //podle jednotlivych typu pozadavku
        if ($typ_pozadavku == "new") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "edit_stav") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "set_stav_storno") {
            return $zamestnanec->get_bool_prava($id_modul, "edit_cizi") || $zamestnanec->get_bool_prava($id_modul, "edit_svuj");
        } else if ($typ_pozadavku == "show") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "create" or $typ_pozadavku == "create_new") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "update") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "add_agentura") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }     
        } else if ($typ_pozadavku == "delete_agentura") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }               
        } else if ($typ_pozadavku == "change_zajezd") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }  
        } else if ($typ_pozadavku == "change_serial") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }     
        } else if ($typ_pozadavku == "delete") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /*     * kontrola zda mam odpovidajici data */

    function correct_data($typ_pozadavku, $subpozadavek="") {
        $ok = 1;
        //kontrolovaná data: název seriálu, popisek,  id_typ,
        if (($typ_pozadavku == "update" and ($subpozadavek=="zmena_zajezdu" or $subpozadavek=="zmena_serialu")) or $typ_pozadavku == "change_zajezd") {
            if (!Validace::int_min($this->id_klient, 1) and !Validace::int_min($this->id_objednavajici_organizace, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován klient ani objednávající organizace!");
            }
   /*         if (!Validace::int_min($this->id_serial, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován seriál!");
            }
            if (!Validace::int_min($this->id_zajezd, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován zájezd!");
            }*/
        }else if ($typ_pozadavku == "create_new" or $typ_pozadavku == "update") {
            if (!Validace::int_min($this->id_klient, 1) and !Validace::int_min($this->id_objednavajici_organizace, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován klient ani objednávající organizace!");
            }
            if (!Validace::int_min($this->id_serial, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován seriál!");
            }
            if (!Validace::int_min($this->id_zajezd, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován zájezd!");
            }
            if (!Validace::int_min($this->stav, 1)) {
                $ok = 0;
                $this->chyba("Není specifikován stav objednávky!");
            }
            if (!Validace::int_min_max($this->pocet_osob, 1, MAX_OSOB)) {
                $ok = 0;
                $this->chyba("Poèet osob není v povoleném rozsahu 1 - " . MAX_OSOB . "!");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*provede v databazi zmenu zajezdu a serialu, smaze stare sluzby a provede update kapacit. na stejne obrazovce se pak zobrazi update sluzeb s predvyplnenymi pocty*/
    function change_zajezd(){
        $this->stare_sluzby = array();
        $sluzby_k_objednavce = new Rezervace_cena("edit", $this->id_zamestnance, $this->id_objednavka);
        while($sluzby_k_objednavce->get_next_radek()){
            $cena = $sluzby_k_objednavce->get_id_cena();
            $pocet = $sluzby_k_objednavce->get_objednavka_pocet();
            if($cena >0 and $pocet >0){
                $this->stare_sluzby[$cena] = $pocet; 
            }
        }
        //provedu smazání služeb a vrácení kapacit
        $smazani_sluzeb  = new Rezervace_cena("delete_objednavky", $this->id_zamestnance, $this->id_objednavka);
        $this->storno_poplatek = $this->check_int($_GET["storno"]);
        
        $update = $this->database->query($this->create_query("change_zajezd")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        
    }

    /*     * zobrazeni menu - moznosti editace pro konkretni objednavku */
    function show_submenu() {
        $data = $this->database->query($this->create_query("select_zajezd"))
                or $this->chyba("Chyba pøi dotazu do databáze");
        $result_serial = mysqli_fetch_array($data);

        if($this->id_klient>0){
        $data = $this->database->query($this->create_query("select_klient"))
                or $this->chyba("Chyba pøi dotazu do databáze");        
        $result_klient = mysqli_fetch_array($data);
        }
        if($result_serial["id_sablony_zobrazeni"]==12){
            $serial = "<strong>" . $result_serial["nazev_ubytovani"] . " " . $result_serial["nazev"] . "</strong>, ";
        }else{
            $serial = "<strong>" . $result_serial["nazev"] . "</strong>, ";
        }
        
        $klient = "<strong>" . $result_klient["prijmeni"] . " " . $result_klient["jmeno"] . "</strong>";

        $vystup = " " . $this->id_objednavka . ", " . $serial . $klient . ":                               
                            <a href=\"?id_objednavka=".$this->id_objednavka."&amp;typ=rezervace&amp;pozadavek=show\">EDITOVAT</a>
                            <a href=\"ts_objednavka.php?id_objednavka=".$this->id_objednavka."&amp;security_code=".$this->rezervace["security_code"]."&amp;type=cestovni_smlouva\" target=\"_blank\">TS Cestovní Smlouva</a>  
                            <a href=\"ts_objednavka.php?id_objednavka=".$this->id_objednavka."&amp;security_code=".$this->rezervace["security_code"]."&amp;type=potvrzeni\" target=\"_blank\">TS Potrvrzení Objednávky</a>
                            <a href=\"ts_objednavka.php?id_objednavka=".$this->id_objednavka."&amp;security_code=".$this->rezervace["security_code"]."&amp;type=potvrzeni_prodejce\" target=\"_blank\">TS Potrvrzení Objednávky Prodejci</a>    
                            <a href=\"vouchery_objednavka.php?page=edit-voucher&id_objednavka=".$this->id_objednavka."&amp;security_code=".$this->rezervace["security_code"]."\">TS Voucher</a>                                
                            <a href=\"platebni_doklad.php?page=edit&id_objednavka=".$this->id_objednavka."\" target=\"_blank\">TS Platební doklad</a>   
                            <a href='faktury.php?id_objednavka=".$this->id_objednavka."&typ=faktury&pozadavek=new'>TS Nová Faktura</a>    
                            <a href=\"ts_objednavka.php?id_objednavka=".$this->id_objednavka."&amp;security_code=".$this->rezervace["security_code"]."&amp;type=storno_klient\" target=\"_blank\">TS Storno klienta</a>
                            <a href=\"ts_objednavka.php?id_objednavka=".$this->id_objednavka."&amp;security_code=".$this->rezervace["security_code"]."&amp;type=storno_ck\" target=\"_blank\">TS Storno CK</a>    
                            <a class='action-delete' href='rezervace.php?id_objednavka=".$this->id_objednavka."&typ=rezervace&pozadavek=show&sub_pozadavek=show_edit' >Storno</a>    
			    <a class='action-delete' href=\"?id_objednavka=".$this->id_objednavka."&amp;typ=rezervace&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>							
				";

        return $vystup;
    }

    /*     * zobrazeni formulare pro rezervaci sluzeb */

    function show_ceny_form() {
        //zobrazeni seznamu cen, pouziju tridu Rezervace_cena
        $ceny = new Rezervace_cena("new", $this->id_zamestnance, "", $this->id_serial, $this->id_zajezd);
        return "<h3>Objednávka služeb/cen</h3>
				" . $ceny->show_form();
    }
    function show_form_change_zajezd($id_serial, $moznosti_editace, $dotaz) {
        $zajezd = new Zajezd_list($id_serial, $moznosti_editace, $dotaz);
        $text = $zajezd->show_list_header();
        while($zajezd->get_next_radek()){
            $text .= $zajezd->show_list_item("tabulka_change_zajezd");
        }        
        return $text."</table>";   
    }
    function show_form_change_serial() {
        $serial_list = new Serial_list($_SESSION["serial_typ"],$_SESSION["serial_podtyp"],$_SESSION["serial_nazev"],$_SESSION["serial_zeme"],$_GET["str"],$_SESSION["serial_ord_by"], "objednavka_change_serial");
        $text = $serial_list->get_error_message();		
				//zobrazim filtry	
	$text .= $serial_list->show_filtr();					
	if(!$serial_list->get_error_message() ){						
	//nadpis seznamu
            $text .= $serial_list->show_header();			
            $text .= $serial_list->show_list_header();
	    while($serial_list->get_next_radek()){
		$text .= $serial_list->show_list_item("tabulka");
            }	
            $text .= "</table>";
		//vypisu odkazy dalsi stranky
            $text .= $serial_list->show_strankovani();
	}  
        return $text;
    }    
    
    /** zobrazeni formulare pro vytvoreni /editaci objednavky */
    function show_form() {

        if ($this->typ_pozadavku == "new") {
            $seznam_cen = $this->show_ceny_form();
        } else {
            $seznam_cen = "";
        }

        $data = $this->database->query($this->create_query("select_zajezd"))
                or $this->chyba("Chyba pøi dotazu do databáze");
        $result_serial = mysqli_fetch_array($data);

        $data = $this->database->query($this->create_query("select_klient"))
                or $this->chyba("Chyba pøi dotazu do databáze");
        $result_klient = mysqli_fetch_array($data);


        $data = $this->database->query($this->create_query("select_provize"))
                or $this->chyba("Chyba pøi dotazu do databáze");
        $result_provize = mysqli_fetch_array($data);
        if ($result_provize["provize_vc_dph"] == 1)
            $provize_je_s_dph = " checked=\"checked\"";
        //vytvorim jednotliva pole
        $hidden = "
			<input name=\"id_klient\" type=\"hidden\" value=\"" . $this->id_klient . "\" />
			<input name=\"id_serial\" type=\"hidden\" value=\"" . $this->id_serial . "\" />
			<input name=\"id_zajezd\" type=\"hidden\" value=\"" . $this->id_zajezd . "\" />
			";

        $serial = "<div class=\"form_row\"> <div class=\"label_float_left\">seriál/zájezd:</div><div class=\"value\"><strong>" . $result_serial["nazev_ubytovani"] . " " . $result_serial["nazev"] . "</strong> (" . $this->change_date_en_cz($result_serial["od"]) . " - " . $this->change_date_en_cz($result_serial["do"]) . ")</div></div>";
        $klient = "<div class=\"form_row\"> <div class=\"label_float_left\">objednávající:</div><div class=\"value\"><strong>" . $result_klient["prijmeni"] . " " . $result_klient["jmeno"] . "</strong>; " . $this->change_date_en_cz($result_klient["datum_narozeni"]) . "; " . $result_klient["mesto"] . ", " . $result_klient["ulice"] . ", " . $result_klient["psc"] . "</div></div>";

        $rezervace_do = "	<div class=\"form_row\"> <div class=\"label_float_left\">opce do:</div> <div class=\"value\"><input name=\"rezervace_do\" type=\"text\" value=\"" . $this->rezervace_do . "\" /></div></div>";
        $pocet_osob = " <div class=\"form_row\"> <div class=\"label_float_left\">poèet osob: <span class=\"red\">*</span></div> <div class=\"value\"><input  name=\"pocet_osob\" type=\"text\" value=\"" . $this->pocet_osob . "\" /></div></div>";
        $celkova_cena = "	<div class=\"form_row\"> <div class=\"label_float_left\">celková cena:</div> <div class=\"value\">" . $this->celkova_cena . " Kè</div></div>";
        $poznamky = " <div class=\"form_row\"> <div class=\"label_float_left\">poznámky:</div> <div class=\"value\"><textarea name=\"poznamky\" rows=\"4\" cols=\"60\">" . $this->poznamky . "</textarea></div></div>";

     
        $termin_od = "	<div class=\"form_row\"> <div class=\"label_float_left\">upøesnìní termínu - od:</div> <div class=\"value\"><input  name=\"termin_od\" type=\"text\" value=\"" . $this->termin_od . "\" /></div></div>";
        $termin_do = "	<div class=\"form_row\"> <div class=\"label_float_left\">upøesnìní termínu - do:</div> <div class=\"value\"><input  name=\"termin_do\" type=\"text\" value=\"" . $this->termin_do . "\" /></div></div>";

        
        $doprava = "	<div class=\"form_row\"> <div class=\"label_float_left\">Doprava (text na cest. smlouvu):</div> <div class=\"value\"><input  name=\"doprava\" type=\"text\" value=\"" . $this->doprava . "\" /></div></div>";
        $strava = "	<div class=\"form_row\"> <div class=\"label_float_left\">Stravování (text na cest. smlouvu):</div> <div class=\"value\"><input  name=\"stravovani\" type=\"text\" value=\"" . $this->stravovani . "\" /></div></div>";
        $ubytovani = "	<div class=\"form_row\"> <div class=\"label_float_left\">Ubytování (text na cest. smlouvu):</div> <div class=\"value\"><input  name=\"ubytovani\" type=\"text\" value=\"" . $this->ubytovani . "\" /></div></div>";

        $pocet_noci = "	<div class=\"form_row\"> <div class=\"label_float_left\">poèet nocí:</div> <div class=\"value\">" . $this->pocet_noci . "</div></div>";

        $prov_text = "	<div class=\"form_row\"> <div class=\"label_float_left\">poznámka provize:</div> <div class=\"value\"><input  name=\"nazevprovize\" type=\"text\" value=\"" . $result_provize["poznamka_provize"] . "\" /></div></div>";
        $prov_sdph = "	<div class=\"form_row\"> <div class=\"label_float_left\">provize s dph:</div> <div class=\"value\"><input  name=\"sdphprovize\" type=\"checkbox\" value=\"1\"" . $provize_je_s_dph . " /></div></div>";
        $prov_suma = "	<div class=\"form_row\"> <div class=\"label_float_left\">provize suma:</div> <div class=\"value\"><input  name=\"sumaprovize\" type=\"text\" value=\"" . $result_provize["suma_provize"] . "\" /> Kè</div></div>";

        //tvorba select stav objednavky
        $i = 0;
        $stav = "<div class=\"form_row\"> <div class=\"label_float_left\">stav objednávky: <span class=\"red\">*</span></div>
					<div class=\"value\"><select name=\"stav\">";
        while (Rezervace_library::get_stav($i) != "") {
            if ($this->stav == ($i + 1)) {
                $stav = $stav . "<option value=\"" . ($i + 1) . "\" selected=\"selected\">" . Rezervace_library::get_stav($i) . "</option>";
            } else {
                $stav = $stav . "<option value=\"" . ($i + 1) . "\">" . Rezervace_library::get_stav($i) . "</option>";
            }
            $i++;
        }
        $stav = $stav . "</select></div></div>";

        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=rezervace&amp;pozadavek=create";
            $zbyva_zaplatit = "";

            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit = "<input type=\"submit\" value=\"Vytvoøit objednávku\" />";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení objednávky</strong>";
            }
        } else if ($this->typ_pozadavku == "edit_stav") {
            //cil formulare
            $action = "?id_objednavka=" . $this->id_objednavka . "&amp;typ=rezervace&amp;pozadavek=update_stav";
            $zbyva_zaplatit = "	<div class=\"form_row\"> <div class=\"label_float_left\">zbývá zaplatit:</div> <div class=\"value\">" . $this->zbyva_zaplatit . " Kè</div></div>";

            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Uložit\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto objednávky</strong>";
            }
        }


        $vystup = "<form action=\"" . $action . "\" method=\"post\">" .
                $serial . $klient . $rezervace_do . $stav . $pocet_osob . $celkova_cena . $nazev_slevy . $velikost_slevy .
                $castka_slevy . $prov_text . $prov_sdph . $prov_suma . $zbyva_zaplatit . $termin_od . $termin_do . $pocet_noci .$doprava.$strava.$ubytovani.
                $poznamky . $seznam_cen . $hidden . $submit .
                "</form>";
        return $vystup;
    }

    /** zobrazeni informaci o objednavce
     * - vcetne objednanych sluzeb, osob a plateb za objednavku */
    function show($typ_zobrazeni, $podtyp_zobrazeni = "") {
        $zamestnanec = User_zamestnanec::get_instance();

        if ($typ_zobrazeni == "tabulka") {
            
            $rezervace_cena = new Rezervace_cena("show", $zamestnanec->get_id(), $this->id_objednavka);
            $rezervace_platby = new Rezervace_platba_list($this->id_objednavka);
            $rezervace_osoby = new Rezervace_osoba("show", $zamestnanec->get_id(), $this->id_objednavka);
            $faktury = new Faktury_list("", "", "", "", "", $this->id_objednavka, "", "");
            $data = $this->database->query($this->create_query("select_zajezd")) or $this->chyba("Chyba pøi dotazu do databáze");
            $result_serial = mysqli_fetch_array($data);

            $data = $this->database->query($this->create_query("select_klient")) or $this->chyba("Chyba pøi dotazu do databáze");
            $result_objednavajici = @mysqli_fetch_array($data);

            $data = $this->database->query($this->create_query("select_objednavajici_org")) or $this->chyba("Chyba pøi dotazu do databáze");
            $result_objednavajici_org = @mysqli_fetch_array($data);

            $data = $this->database->query($this->create_query("select_provize")) or $this->chyba("Chyba pøi dotazu do databáze");
            $result_provize = mysqli_fetch_array($data);
            if ($result_provize["provize_vc_dph"] == 1)
                $provize_je_s_dph = "ano";else
                $provize_je_s_dph = "ne";

            $prodejce = $this->get_prodejce();

            if($result_serial["id_sablony_zobrazeni"]==12){
                $nazev_serial = "<strong>" . $result_serial["nazev_ubytovani"] . " " . $result_serial["nazev"] .  "</strong>";
            }else{
                $nazev_serial = "<strong>" . $result_serial["nazev"] . "</strong>";
            }
            
            $serial = "<tr class=\"suda\"><td>seriál:</td><td>$nazev_serial <span id=\"zmenit_serial\"><a href=\"#\" onclick='show_storno_zmena_zajezdu(\"zmenit_serial\",$this->storno_poplatek, $this->storno_poplatek_calc); return false;' >zmìnit seriál</a></span></td></tr>";
            $zajezd = "<tr class=\"suda\"><td>zájezd:</td><td>".$result_serial["nazev_zajezdu"]." ". $this->change_date_en_cz($result_serial["od"]) . " - " . $this->change_date_en_cz($result_serial["do"]) . " <span id=\"zmenit_zajezd\"><a href=\"#\"  onclick='show_storno_zmena_zajezdu(\"zmenit_zajezd\",$this->storno_poplatek, $this->storno_poplatek_calc); return false;'>zmìnit zájezd</a></span></td></tr>";
            $objednavajici = "<tr class=\"suda\"><td>objednávající:</td><td><strong>" . $result_objednavajici["prijmeni"] . " " . $result_objednavajici["jmeno"] . "</strong>; " . $result_objednavajici["email"] . ", " . $result_objednavajici["telefon"] . "; " . $this->change_date_en_cz($result_objednavajici["datum_narozeni"]) . "; " . $result_objednavajici["mesto"] . ", " . $result_objednavajici["ulice"] . ", " . $result_objednavajici["psc"] . "</div></div>";
            $objednavajici_org = "<tr class=\"suda\"><td>objednávající organizace:</td><td>[<span class='edit-value'>".$result_objednavajici_org["id"]."</span>]<strong>" . $result_objednavajici_org["nazev"] . " " . $result_objednavajici_org["ico"] . $result_objednavajici_org["dic"] . "</strong>; " . $result_objednavajici_org["email"] . ", " . $result_objednavajici_org["telefon"] . "; " . $this->change_date_en_cz($result_objednavajici_org["datum_narozeni"]) . "; " . $result_objednavajici_org["mesto"] . ", " . $result_objednavajici_org["ulice"] . ", " . $result_objednavajici_org["psc"] . "</div></div>";

            $pocet_osob = "<tr class=\"suda\"><td>poèet osob:</td><td><span class='edit-value'>$this->pocet_osob</span></td></tr>";
            $stav = "<tr class=\"suda\" ><td>stav objednávky:</td><td><span class='edit-value'>" . Rezervace_library::get_stav(($this->stav - 1)) . "</span></td></tr>";
            $storno_poplatek = "<tr class=\"suda\" ><td>storno poplatek:</td><td><span class='edit-value'>$this->storno_poplatek</span> Kè</td></tr>";
            $rezervace_do = "<tr class=\"suda\"><td>opce do:</td><td><span class='edit-value'>" . CommonUtils::czechDate($this->rezervace_do) . "</span></td></tr>";

            $celkova_cena = "<tr class=\"suda\"><td>celková cena:</td><td class='fw-bold'><span class='edit-value'>$this->celkova_cena</span> Kè</td></tr>";
            $prov_text = "	<tr class=\"suda\"><td>poznámka provize:</td><td><span class='edit-value'>" . $result_provize["poznamka_provize"] . "</span></td></tr>";
            $prov_sdph = "	<tr class=\"suda\"><td>provize s dph:</td><td><span class='edit-value'>$provize_je_s_dph</span></td></tr>";
            $prov_suma = "	<tr class=\"suda\"><td>provize suma:</td><td><span class='edit-value'>" . $result_provize["suma_provize"] . "</span> Kè</td></tr>";
            $zbyva_zaplatit = "<tr class=\"suda\"><td>zbývá zaplatit:</td><td>$this->zbyva_zaplatit Kè</td></tr>";

            $termin_od = "	<tr class=\"suda\"><td>upøesnìní termínu - od:</td><td><span class='edit-value'>" . CommonUtils::czechDate($this->termin_od) . "</span></td></tr>";
            $termin_do = "	<tr class=\"suda\"><td>upøesnìní termínu - do:</td><td><span class='edit-value'>" . CommonUtils::czechDate($this->termin_do) . "</span></td></tr>";
            $pocet_noci = "	<tr class=\"suda\"><td>poèet nocí:</td><td>$this->pocet_noci</td></tr>";

            $doprava = "	<tr class=\"suda\"><td>Doprava (text na cest. smlouvu):</td><td><span class='edit-value'>$this->doprava</span></td></tr>";
            $strava = "	<tr class=\"suda\"><td>Stravování (text na cest. smlouvu):</td><td><span class='edit-value'>$this->stravovani</span></td></tr>";
            $ubytovani = "	<tr class=\"suda\"><td>Ubytování (text na cest. smlouvu):</td><td><span class='edit-value'>$this->ubytovani</span></td></tr>".
                    "	<tr class=\"suda\"><td>Pojištìní (text na cest. smlouvu):</td><td><span class='edit-value'>$this->pojisteni</span></td></tr>";
            $poznamky = "     <tr class=\"suda\"><td valgin=\"top\">Poznámky:</td><td><span class='edit-value'>$this->poznamky</span></td></tr>
                              <tr class=\"suda\"><td valgin=\"top\">Tajné poznámky:</td><td><span class='edit-value'>$this->poznamky_tajne</span></td></tr>";

            //musim nacist spocitane ceny (z TS) - note dost nesikovne, ale co se da delat - ObjednavkaDisplayer je model + view dohromady - model by mel byt oddelen a pak by se dal pouzit v TS i tady
            $objednavkadDisplayer = new ObjednavkaDisplayer($this->get_id());
            $totalPrice = $objednavkadDisplayer->getKUHradeCelkem();
            $zaloha = $objednavkadDisplayer->getKUHradeZaloha();
            $doplatek = $objednavkadDisplayer->getKUHradeDoplatek();
            $totalPriceDate = $objednavkadDisplayer->getKUHradeCelkemDatspl();
            $zalohaDate = $objednavkadDisplayer->getKUHradeZalohaDatspl();
            $doplatekDate = $objednavkadDisplayer->getKUHradeDoplatekDatspl();
            if($objednavkadDisplayer->hasZalohaDoplatek()) {
                $k_uhrade = "<tr><td>K úhradì záloha:</td><td><span class='edit-value'>$zaloha</span>,- <span class='edit-value' data-name='k_uhrade_zaloha_datspl'>" . CommonUtils::czechDate($zalohaDate) . "</span></td></tr>
                            <tr><td>K úhradì doplatek:</td><td><span class='edit-value'>$doplatek</span>,- <span class='edit-value' data-name='k_uhrade_doplatek_datspl'>" . CommonUtils::czechDate($doplatekDate) . "</span></td></tr>";
            } else {
                $k_uhrade = "   <tr><td>K úhradì celkem:</td><td><span class='edit-value' data-name='k_uhrade_celkem'>$totalPrice</span>,- <span class='edit-value' data-name='k_uhrade_celkem_datspl'>" . CommonUtils::czechDate($totalPriceDate) . "</span></td></tr>";
            }

            $edit_button = "  <tr class=\"edit\"><td></td><td><button id=\"obj_edit\" type=\"button\" onclick=\"edit_obj();show_storno($this->storno_poplatek, $this->storno_poplatek_calc, " . Rezervace_library::$STAV_STORNO . ");\">Upravit</button></td></tr>";

            //nactu options stav obj a predam je JS - note js by si je mel nacist ze serveru sam
            $i = 0;
            $jsStav = "stav = \"<select name='stav' onchange='show_storno($this->storno_poplatek, $this->storno_poplatek_calc, " . Rezervace_library::$STAV_STORNO . ");' id='storno_selector'>";
            while (Rezervace_library::get_stav($i) != "") {
                if ($this->stav == ($i + 1)) {
                    $jsStav .= "<option value='" . ($i + 1) . "' selected='selected'>" . Rezervace_library::get_stav($i) . "</option>";
                } else {
                    $jsStav .= "<option value='" . ($i + 1) . "'>" . Rezervace_library::get_stav($i) . "</option>";
                }
                $i++;
            }
            $jsStav .= "</select><span id='storno_wrapper'></span>\";";
            $zobrazEditaci = "";
            if ($podtyp_zobrazeni == "show_edit")
                $zobrazEditaci = "<script type='text/javascript'>edit_obj();select_value(\"storno_selector\", " . Rezervace_library::$STAV_STORNO . ");show_storno($this->storno_poplatek, $this->storno_poplatek_calc, " . Rezervace_library::$STAV_STORNO . ");</script>";

            //formular s tabulkou
            $objednavka = " <form id=\"obj_form\" method=\"post\" action=\"?id_objednavka=$this->id_objednavka&typ=rezervace&pozadavek=update_stav\">
                                <table id=\"obj_table\" class=\"list\">
                                    <tr><th colspan=\"2\">Objednávka</th></tr>
                                    $serial $zajezd $objednavajici $objednavajici_org $pocet_osob $stav $storno_poplatek $rezervace_do $celkova_cena $prov_text $prov_sdph $prov_suma
                                    $zbyva_zaplatit $termin_od $termin_do $pocet_noci $doprava $strava $ubytovani $poznamky $k_uhrade
                                    $edit_button
                                </table>
                                <input type=\"hidden\" name=\"id_klient\" value=\"$this->id_klient\" />
                                <input type=\"hidden\" name=\"id_serial\" value=\"$this->id_serial\" />
                                <input type=\"hidden\" name=\"id_zajezd\" value=\"$this->id_zajezd\" />
                            </form>
                            <script type=\"text/javascript\">$jsStav</script>
                            $zobrazEditaci";

            $data = $this->database->query($this->create_query("select_sleva"))
                    or $this->chyba("Chyba pøi dotazu do databáze");
            $slevy = $this->show_header_slevy();
            $i=0;
            while ($result_sleva = mysqli_fetch_array($data)) {
                $i++;
                $slevy .= $this->show_slevy($result_sleva, $i);
            }
            $slevy .= $this->show_footer_slevy();


            //echo $this->create_query("select_mozne_slevy");
            $data = $this->database->query($this->create_query("select_mozne_slevy"))
                    or $this->chyba("Chyba pøi dotazu do databáze");
            $mozne_slevy = $this->show_header_mozne_slevy();
            $i=0;
            while ($result_sleva = mysqli_fetch_array($data)) {
                $i++;
                $mozne_slevy .= $this->show_mozne_slevy($result_sleva, $i);                
            }
            $i++;
            $mozne_slevy .= "<tr class=\"suda\">
                 <form name=\"insert_sleva_".$i."\" action=\"?id_objednavka=".$this->get_id()."&amp;id_slevy=0&amp;typ=rezervace_sleva&amp;pozadavek=create\" method=\"post\">
                    <td><input type=\"text\" size=\"40\" name=\"nazev_slevy\" value=\"Název slevy\" />
                    <td><input type=\"text\" size=\"6\"  name=\"castka\" value=\"0\" />
                        <select name=\"mena\">
                            <option value=\"%\">%</option>
                            <option value=\"Kè\">Kè/os</option>
                            <option value=\"Kè_direct\">Kè</option>
                        </select>
                    <td> <input type=\"submit\" value=\"Pøidat slevu\">
                 </form>";
            $mozne_slevy .= $this->show_footer_slevy();


            $osoby = $rezervace_osoby->show_list_header("tabulka_zobrazit") . $rezervace_osoby->show_list("tabulka_zobrazit") . "</table>";
            $ceny = $rezervace_cena->show("tabulka",$this->exportovana,$this->id_exportovana_objednavka);
            $ceny_edit = $rezervace_cena->show("tabulka_edit_ceny",$this->exportovana,$this->id_exportovana_objednavka);

            
            
            $platby = $rezervace_platby->show_list_header("tabulka_zobrazit");
            while ($rezervace_platby->get_next_radek()) {
                $platby .= $rezervace_platby->show_list_item("tabulka_zobrazit");
            }
            $platby .= $rezervace_platby->show_list_footer("tabulka_zobrazit");
            $platby .= "</table>";

            $faktury_text = $faktury->show_list_header("plain") ;
            while ($faktury->get_next_radek()) {
                $faktury_text .= $faktury->show_list_item("tabulka_objednavka");
            }
            $faktury_text .= "</table>";
            
            $vystup = $objednavka . "<br/>
                            " . $prodejce . "
                            " . $ceny . "<br/>
                            " . $ceny_edit . "<br/>
                            " . $slevy . "<br/>
                            " . $mozne_slevy . "<br/>    
                            " . $osoby . "<br/><div id='osoby_result'></div><br/>
                            " . $faktury_text . "<br/>
                            " . $platby . "<br/>";
            return $vystup;
        }
    }

    function show_header_slevy() {
        return " <table class=\"list\">
                        <tr><th colspan=\"4\">Pøiøazené Slevy</th></tr>
                         <tr><th>Název slevy</th><th>Výše slevy</th><th>Celková èástka</th><th>Možnosti editace</th></tr>";
    }

    function show_slevy($data, $i) {
        return "<tr class=\"suda\">
                  <form name=\"edit_sleva_".$i."\" action=\"?id_objednavka=".$this->get_id()."&amp;id_slevy=".$data["id_slevy"]."&amp;typ=rezervace_sleva&amp;pozadavek=delete\" method=\"post\">
                    <td><input type=\"hidden\" name=\"old_nazev_slevy\" value=\"" . $data["nazev_slevy"] . "\" />" . $data["nazev_slevy"] . "
                    <td><input type=\"hidden\" name=\"old_velikost_slevy\" value=\"" . $data["velikost_slevy"] . "\" />" . $data["velikost_slevy"] . " " . $data["mena"] . "
                    <td>" . $data["castka_slevy"] . " Kè 
                    <td> Upravit | <input type=\"submit\" value=\"Odebrat slevu\">
                  </form>";
    }

    function show_footer_slevy() {
        return " </table>";
    }

    function show_header_mozne_slevy() {
        return " <table class=\"list\">
                        <tr><th colspan=\"4\">Pøidat další slevy k objednávce</th></tr>
                         <tr><th>Název slevy</th><th>Výše slevy</th><th></th></tr>";
    }

    function show_mozne_slevy($data, $i) {
        
        return "<tr class=\"suda\">
                 <form name=\"insert_sleva_".$i."\" action=\"?id_objednavka=".$this->get_id()."&amp;id_slevy=".$data["id_slevy"]."&amp;typ=rezervace_sleva&amp;pozadavek=create\" method=\"post\">
                    <td><input type=\"hidden\" name=\"nazev_slevy\" value=\"" . $data["nazev_slevy"] . "\" />" . $data["nazev_slevy"] . "
                    <td><input type=\"hidden\" name=\"castka\" value=\"" . $data["castka"] . "\" />
                        <input type=\"hidden\" name=\"mena\" value=\"" . $data["mena"] . "\" />" . $data["castka"] . " " . $data["mena"] . "
                    <td> <input type=\"submit\" value=\"Pøidat slevu\">
                 </form>";
    }

    function get_prodejce() {
        $data = $this->database->query($this->create_query("select_prodejce"))
                or $this->chyba("Chyba pøi dotazu do databáze");
        $result_prodejce = mysqli_fetch_array($data);
        if ($result_prodejce["nazev_agentury"] == "")
            $vystup = "
                                 <table class=\"list\">
                                        <tbody>
					<tr><th>Detaily prodejce</th></tr>
					<tr class=\"suda\"><td>Tato objednávka nemá pøiøazeného žádného prodejce / agenturu<br/>
                                        <a href=\"/admin/organizace.php?typ=organizace_list&amp;moznosti_editace=add_orgaizace_to_objednavka&amp;id_objednavka=".$this->get_id()."\">Pøidat prodejce</a></td></tr>
                                        </tbody>
                                </table>
				<br/>                
                ";
        else
            $vystup = "
				<table class=\"list\">
                                        <tbody>
					<tr><th colspan=\"2\">Detaily prodejce</th></tr>
					<tr class=\"suda\"><td>Název cestovní agentury:</td><td>" . $result_prodejce["nazev_agentury"] . "</td></tr>
					<tr class=\"suda\"><td>IÈO:</td><td>" . $result_prodejce["ico"] . "</td></tr>
					<tr class=\"suda\"><td>Kontaktní osoba:</td><td>" . $result_prodejce["kontaktni_osoba"] . "</td></tr>
					<tr class=\"suda\"><td>Telefon:</td><td>" . $result_prodejce["telefon"] . "</td></tr>
					<tr class=\"suda\"><td>E-mail:</td><td>" . $result_prodejce["email"] . "</td></tr>
					<tr class=\"suda\"><td>Mìsto:</td><td>" . $result_prodejce["mesto"] . "</td></tr>
					<tr class=\"suda\"><td>Ulice, è.p.</td><td>" . $result_prodejce["ulice"] . "</td></tr>
					<tr class=\"suda\"><td>PSÈ</td><td>" . $result_prodejce["psc"] . "</td></tr>
                                        <tr class=\"suda\"><td colspan=2><a href=\"?typ=rezervace&amp;pozadavek=delete_agentura&amp;id_objednavka=".$this->get_id()."\"  onclick=\"javascript:return confirm('Opravdu chcete smazat prodejce?')\">Odstranit prodejce z této objednávky</a></td></tr>   
                                        </tbody>
                                </table>

   				<br/>
			";
//            SELECT `prodejce`.`jmeno` as `nazev_agentury`, `prodejce`.`prijmeni` as `kontaktni_osoba`, `prodejce`.`ico` as `ico`,
//                                `prodejce`.`email` as `email`, `prodejce`.`telefon` as `telefon`, `prodejce`.`mesto` as `mesto`, `prodejce`.`ulice` as `ulice`,
//                                `prodejce`.`psc` as `psc` FROM `objednavka`
        return $vystup;
    }

    function get_zbyva_zaplatit() {
        return $this->zbyva_zaplatit;
    }
    
    function get_celkova_cena() {
        return $this->celkova_cena;
    }


    function get_stav() {
        return $this->stav;
    }

    static function update_cena($celkova_cena_sluzby, $storno_poplatek, $platby_celkem, $id_objednavka) {
        $celkova_cena_sluzby = $celkova_cena_sluzby + $storno_poplatek;
        $zbyva_zaplatit = $celkova_cena_sluzby - $platby_celkem;
        $id_objednavka = intval($id_objednavka);

        /*zkontrolovat castky u "k uhrade zaloha a doplatek - pokud nesedi s celkovou cenou, tak je vymazeme*/
        $dotaz2 = "select `k_uhrade_celkem`, `k_uhrade_zaloha`, `k_uhrade_doplatek` from `objednavka`                         
                        WHERE `id_objednavka`=" . $id_objednavka . "
                        LIMIT 1";      
        $chybne_castky = 0;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz2) or Rezervace::chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        while ($row = mysqli_fetch_array($data)) {
            if($row["k_uhrade_celkem"]!=""){
                if($celkova_cena_sluzby != $row["k_uhrade_celkem"]){
                    $chybne_castky = 1;
                }
            }else if($row["k_uhrade_zaloha"]!="" or $row["k_uhrade_doplatek"]!=""){
                if($celkova_cena_sluzby != ($row["k_uhrade_doplatek"] + $row["k_uhrade_zaloha"])){
                    $chybne_castky = 1;
                }
            }            
        }
        $mazani_definice_castek = "";
        if($chybne_castky == 1){
            $mazani_definice_castek = ", `k_uhrade_celkem`= null, `k_uhrade_zaloha`= null, `k_uhrade_doplatek`= null";
        }
           
        
        
        $dotaz = "UPDATE `objednavka` 
                        SET
                            `celkova_cena`=" . $celkova_cena_sluzby . ",`zbyva_zaplatit`=" . $zbyva_zaplatit . $mazani_definice_castek . "
                        WHERE `id_objednavka`=" . $id_objednavka . "
                        LIMIT 1";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz) or Rezervace::chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
    }

    static function get_objednavka_info($id_objednavka) {
        $dotaz = "select * from `objednavka` where `id_objednavka`=" . $id_objednavka . " limit 1";
        $result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($row = mysqli_fetch_array($result)) {
            return $row;
        }
    }

//spocita castku dle objednavky        
        static function computeCastka($castka,$pocet, $use_pocet_noci, $pocet_noci){
            if ($pocet_noci == 0) {
                $pocet_noci = 1;
            }
            if ($use_pocet_noci > 0) {
                return $castka * $pocet * $pocet_noci;
            } else {
                return $castka * $pocet;
            }
            
        }
        //wrapper nad calculateProvize - zpracovava $_Request pole do prijatelnejsi podoby
        static function calculateProvizeWrapper(){
            
            $id_serial = intval($_REQUEST["id_serial"]);
            $id_zajezd = intval($_REQUEST["id_zajezd"]);
            $id_organizace = intval($_REQUEST["agentura"]);
            
            //ziskani koeficientu prodejce
            $provizni_koeficient = 1;
            if($id_organizace>0){
                $dotaz_koeficient = "select provizni_koeficient from prodejce where id_organizace=$id_organizace limit 1";
                $data_koeficient = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_koeficient);
                while($result_koeficient = mysqli_fetch_array($data_koeficient)){
                    $provizni_koeficient = $result_koeficient["provizni_koeficient"];
                }  
            }
            
            $sluzby_id_pocet = array();
            $pocet_osob = $_REQUEST["pocet_osob"];
            $pocet_noci = $_REQUEST["pocet_noci"];
            
            $sum_sleva_percent = 0;
            $sum_sleva_fix = 0;
            $sum_sleva_osoba = 0;            
            $dalsi_sluzby_suma = 0;
            //spocitani sluzeb
            $i=0;

            //print_r($_REQUEST);
            while ($i <= MAX_CEN and isset($_REQUEST["sluzba-id-".$i])) {
                $id = intval($_REQUEST["sluzba-id-".$i]);
                $pocet = intval($_REQUEST["sluzba-pocet-".$i]);
                if($id > 0 and $pocet > 0){
                    $sluzby_id_pocet[$id] = $pocet;
                }
                $i++;
            }
            $i=0;
            while ($i <= MAX_CEN and isset($_REQUEST["new-sluzba-price-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                if(intval($_REQUEST["new-sluzba-pocet-".$i]) > 0){
                    $castka = $_REQUEST["new-sluzba-price-".$i];
                    $pocet = $_REQUEST["new-sluzba-pocet-".$i];
                    $dalsi_sluzby_suma += $castka*$pocet;
                }
                 $i++;
            }
            $slevy_array = array();                
            //spoèítání celkových slev
            $dotaz = "select * from `slevy` left join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy` and `slevy_serial`.`id_serial` = " . $id_serial . ")
                                            left join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy` and `slevy_zajezd`.`id_zajezd` = " . $id_zajezd . ")
                            where (`slevy_serial`.`platnost`=1 or `slevy_zajezd`.`platnost` =1 )
                                and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" )  ";            
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
            while($result_sluzby = mysqli_fetch_array($data)){
                $slevy_array[$result_sluzby["id_slevy"]] = $result_sluzby;
            }  
            while ($i <= MAX_CEN and isset($_REQUEST["new-sleva-price-".$i]) ) {
                if(intval($_REQUEST["new-sleva-price-".$i])!=0){
                    $castka = intval($_REQUEST["new-sleva-price-".$i]);
                    $typ = $_REQUEST["new-sleva-type-".$i];
                    switch ($typ) {
                        case "type_procento":
                            $sum_sleva_percent += $castka;
                            break;
                        case "type_kc_os":
                            $sum_sleva_osoba += $castka;
                            break;
                        case "type_kc":
                            $sum_sleva_fix += $castka;
                            break;
                    }
                }
                $i++;
            }
            //insert jiz drive vytvorenych slev
            $i=0;
            while ($i <= MAX_CEN and isset($_REQUEST["sleva-klient-serial-id-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                if(intval($_REQUEST["sleva-klient-serial-pocet-".$i]) > 0){
                    $id_slevy = $_REQUEST["sleva-klient-serial-id-".$i];
                    $castka = $slevy_array[$id_slevy]["castka"];
                    $mena = $slevy_array[$id_slevy]["mena"]; 
                    if($mena=="Kè"){
                        $sum_sleva_osoba += $castka;
                    }else if($mena=="%"){
                        $sum_sleva_percent += $castka;
                    }
                }
                 $i++;
            }
            $i=0;
            while ($i <= MAX_CEN and isset($_REQUEST["sleva-klient-zajezd-id-".$i]) ) {//TODO: zjistit zda tu spravne funguje pocet
                if(intval($_REQUEST["sleva-klient-zajezd-pocet-".$i]) > 0){
                    $id_slevy = $_REQUEST["sleva-klient-zajezd-id-".$i];
                    $castka = $slevy_array[$id_slevy]["castka"];
                    $mena = $slevy_array[$id_slevy]["mena"];     
                    if($mena=="Kè"){
                        $sum_sleva_osoba += $castka;
                    }else if($mena=="%"){
                        $sum_sleva_percent += $castka;
                    }                    
                }
                $i++;
            }
            $max_sleva_casova = 0;
            $mena_sleva_casova = "";
            if(is_array($slevy_array)){
            foreach ($slevy_array as $key => $value) {
                if($value["sleva_staly_klient"]==0 and $value["castka"]>$max_sleva_casova){
                    $max_sleva_casova = $value["castka"];
                    $mena_sleva_casova = $value["mena"]; 
                }
            }
            if($mena_sleva_casova=="Kè"){
                $sum_sleva_osoba += $max_sleva_casova;
            }else if($mena_sleva_casova=="%"){
                $sum_sleva_percent += $max_sleva_casova;
            }                        
            }
            $var = Rezervace::calculateProvize($id_serial, $id_zajezd, $sluzby_id_pocet, $dalsi_sluzby_suma, $pocet_osob, $pocet_noci, $sum_sleva_percent, $sum_sleva_fix, $sum_sleva_osoba, $provizni_koeficient);
            return $var;
        }
        
        static function calculateProvize($id_serial, $id_zajezd, $sluzby_id_pocet, $dalsi_sluzby_suma,  $pocet_osob, $pocet_noci, $sum_sleva_percent, $sum_sleva_fix, $sum_sleva_osoba, $provizni_koeficient=1){
            $id_zajezd = intval(trim(strip_tags($id_zajezd)));
            $id_serial = intval(trim(strip_tags($id_serial)));
            $sum_sleva_fix = intval(trim(strip_tags($sum_sleva_fix)));
            $sum_sleva_osoba = intval(trim(strip_tags($sum_sleva_osoba)));
            $pocet_osob = intval(trim(strip_tags($pocet_osob)));
            $pocet_noci = intval(trim(strip_tags($pocet_noci)));
            $dalsi_sluzby_suma = intval(trim(strip_tags($dalsi_sluzby_suma)));
            
            //echo $id_zajezd." - ".$id_serial." - ".$sum_sleva_percent." - ".$sum_sleva_fix." - ".$sum_sleva_osoba." - ".$pocet_osob." - ".$pocet_noci." - ".$dalsi_sluzby_suma;
            //print_r($sluzby_id_pocet);
                $use_sluzby = 0;
                $sql_zajezd = 	"select *
					from `serial` join 
						`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)      
					where `zajezd`.`id_zajezd`=".$id_zajezd." and `serial`.`id_serial` = ".$id_serial."
                                            limit 1";    
                //echo $sql_zajezd;
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_zajezd);
                while ($row = mysqli_fetch_array($data)) {
                   // print_r($row);
                    if($row["typ_provize"]==0){
                        return 0;
                    }else if($row["typ_provize"]==1){ // fixní v kè
                        return round($row["vyse_provize"]*intval($pocet_osob)*$provizni_koeficient);
                    }else if($row["typ_provize"]==2){ //  v %
                        $use_sluzby = 1;
                    }else if($row["typ_provize"]==3){
                        $use_sluzby = 1;
                    }
                
                    if($use_sluzby==1){
                       // echo "inside provize";
                        $provize = 0;
                        $celkova_castka = 0;
                        $sleva = 0;
                        $sql_ceny = "select *
                            from `cena` join 
                            `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena`)      
                            where `cena_zajezd`.`id_zajezd`=".$id_zajezd." and `cena`.`id_serial` = ".$id_serial."";  
                        $data_ceny = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_ceny);
                        while ($row_ceny = mysqli_fetch_array($data_ceny)) {
                           // print_r($row_ceny);
                            if( intval($sluzby_id_pocet[$row_ceny["id_cena"]])>0){
                                //sluzba byla objednána
                                $castka = self::computeCastka($row_ceny["castka"],intval($sluzby_id_pocet[$row_ceny["id_cena"]]), $row_ceny["use_pocet_noci"], $row_ceny["pocet_noci"]);
                                $celkova_castka += $castka;
                                if($row_ceny["typ_provize"] == 1){
                                    $provize += $row_ceny["vyse_provize"] * intval($sluzby_id_pocet[$row_ceny["id_cena"]]);
                                }else if($row_ceny["typ_provize"]==2){
                                    $provize += ($row_ceny["vyse_provize"] * $castka /100);
                                }
                                //pro kazdou procentuelni slevu vypoctu soucet slev
                                if (intval($row_ceny["poradi_ceny"]) < 200 and intval($row_ceny["typ_ceny"]) == 1) {
                                    $sleva += ($castka*$sum_sleva_percent/100);
                                }
                            }
                            //echo $provize;
                        }
                        $sleva += $sum_sleva_fix;
                        $sleva += $sum_sleva_osoba * $pocet_osob;
                        $celkova_castka = $celkova_castka - $sleva;
                        $celkova_castka = $celkova_castka +$dalsi_sluzby_suma;

                        if($row["typ_provize"]==2){
                            return round(($row["vyse_provize"]*intval($celkova_castka)/100)*$provizni_koeficient);
                        }else if($row["typ_provize"]==3){
                            return round($provize*$provizni_koeficient);
                        }

                    }   
                }
        }    
    
    
    function calculate_pocet_noci($od, $do, $upresneni_od, $upresneni_do) {        
        if ($upresneni_od != "" and $upresneni_od != "0000-00-00" and $upresneni_do != "" and $upresneni_do != "0000-00-00") {
            $pole_od = explode("-", $upresneni_od);
            $pole_do = explode("-", $upresneni_do);
        } else {
            $pole_od = explode("-", $od);
            $pole_do = explode("-", $do);
        }

        //echo "...........".$pole_od[2]."-".$pole_od[1]."-".$pole_od[0];
        //echo "...........".$pole_do[2]."-".$pole_do[1]."-".$pole_do[0];

        $time_od = mktime(0, 0, 0, intval($pole_od[1]), intval($pole_od[2]), intval($pole_od[0]));
        $time_do = mktime(0, 0, 0, intval($pole_do[1]), intval($pole_do[2]), intval($pole_do[0]));
        $pocet_noci = (round(($time_do - $time_od) / (24 * 60 * 60)));
        if ($pocet_noci < 0) {
            $pocet_noci = 0;
        }        
        return $pocet_noci;
    }

    function get_id() {
        return $this->id_objednavka;
    }
    function get_id_serial() {
        return $this->id_serial;
    }
    function get_id_zajezd() {
        return $this->id_zajezd;
    }
    function get_nazev_serial() {
        return $this->nazev_serial;
    }
    function get_stare_sluzby() {
        return $this->stare_sluzby;
    }

    
    function get_id_user_create() {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_objednavka == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }

    function pretypuj($in, $typ) {
        if ($typ == "int") {
            return (int) $in;
        }
        if ($typ == "float") {
            return floatval($in);
        }
        if ($typ == "text") {
            if (strlen($in) == 0 or $in == null)
                return "nedefinováno";
            else
                return $in;
        }
    }

    function calc_storno() {
        //ziskam smluvni podminky
        $this->data = $this->database->query($this->create_query("select_storno")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        //vyberu spravne pasmo a spocitam storno poplatek                                            
        $storno_poplatek_calc = 0;
        while ($this->smluvPodm = mysqli_fetch_array($this->data)) {
            $hranice = date('d-m-Y', strtotime($this->termin_od . " - " . $this->smluvPodm["prodleva"] . " days"));
            $today = date('d-m-Y');
            if (strtotime($today) < strtotime($hranice)) {
                if ($this->smluvPodm["castka"] == 0) {
                    $storno_poplatek_calc = $this->celkova_cena * $this->smluvPodm["procento"] / 100;
                } else {
                    $storno_poplatek_calc = $this->smluvPodm["castka"];
                }
                break;
            } else {
                if ($this->smluvPodm["castka"] == 0) {
                    $storno_poplatek_calc = $this->celkova_cena * $this->smluvPodm["procento"] / 100;
                } else {
                    $storno_poplatek_calc = $this->smluvPodm["castka"];
                }
            }
        }
        return $storno_poplatek_calc;
    }

    /*stará se o kompletní kontrolu zda je tøeba zmìnit cenu která zbývá zaplatit a pøípadnì stav objednávky
     * pouze vložit id_objednavky jako parametr
     */
    static function update_zbyva_zaplatit($id_objednavka){
                //update celkove ceny
              //  echo "objednavka $id_objednavka";
                $data_objednavka = Rezervace::get_objednavka_info($id_objednavka);
                $stav = $data_objednavka["stav"];
                $objednavka = new Pdf_objednavka_prepare($id_objednavka, $data_objednavka["security_code"]);
                $objednavka->create_pdf_objednavka();
                $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                $platby_celkem = $objednavka->get_splacene_platby_celkem();
                //provedeme prepocet ceny sluzeb                
               // echo "cenlkova cena $celkova_cena_sluzby, platby celkem $platby_celkem";
                Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $id_objednavka); 
                
    //zmena stavu objednavky (zaloha/prodano) podle toho kolik zbyva doplatit
                //muzu provadet obecne, je ale treba jeste kontrolovat platby
            if ($stav != Rezervace_library::$STAV_STORNO && $stav != Rezervace_library::$STAV_PREDBEZNA_POPTAVKA && $stav != Rezervace_library::$STAV_ODBAVENO  && $platby_celkem  !=0 ) {
                if (($celkova_cena_sluzby + $data_objednavka["storno_poplatek"] - $platby_celkem ) > 0) {//zaloha
                   // echo "update zaloha";
                    $dotaz2 = "UPDATE objednavka SET stav=".Rezervace_library::$STAV_ZALOHA." WHERE id_objednavka=$id_objednavka";
                    mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz2);
                   // echo $dotaz2;
                    
                } else {//zaplaceno
                   // echo "update zaplaceno";
                    $dotaz2 = "UPDATE objednavka SET stav=".Rezervace_library::$STAV_PRODANO." WHERE id_objednavka=$id_objednavka";
                    mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz2);
                   // echo $dotaz2;
                }
            }                
    }
}