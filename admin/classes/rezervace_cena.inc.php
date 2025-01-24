<?php

/**
 * rezervace_cena.inc.php - trida pro zobrazeni seznamu sluzeb rezervace
 * 											- a jejich editaci
 */
/* ------------------- SEZNAM cen objednavky -------------------  */
class Rezervace_cena extends Generic_list {

    protected $typ_pozadavku;
    protected $pocet_zaznamu; //pocet uploadovaných cen
    protected $id_zamestnance;
    protected $id_objednavka;
    protected $id_serial;
    protected $id_zajezd;
    protected $id_cena;
    protected $pocet;
    //stavy objednavek - na zaklade nich musim urcit, jestli bude probihat nejaka zmena ve vyhrazenych kapacitach
    protected $stav;
    protected $minuly_stav;
    //znaci ze smim pokracovat s add_to query
    protected $legal_operation;
    //prubezne konstruovany dotaz do databaze
    protected $query_insert;
    protected $query_update;
    //seznam cen ktere uz existuji (a maji se update misto insert into
    protected $ceny_update;
    //pocty zaznamu v query
    protected $pocet_zaznamu_insert;
    protected $pocet_zaznamu_update;
    protected $data;
    protected $data2;
    private $id_ceny; //pole id_cen
    private $pocet_ceny; //pole poctu objednavanych kapacit jednotlivych cen
    private $text_ceny; //vypis cen do e-mailu
    private $cislo_ceny;
    private $vyplnena_cena;
    protected $probihajici_transakce; //pokud je objekt soucasti probihajici transakce,nebude zahajena ani ukoncena
    public $id_cena_storno;
    public $id_cena2_storno;
    public $pocet_cena_storno;
    public $pocet_cena2_storno;

    //------------------- KONSTRUKTOR -----------------
    /* konstruktor tøídy na základì typu požadavku a formularovych poli */

    function __construct($typ_pozadavku, $id_zamestnance, $id_objednavka, $id_serial = "", $id_zajezd = "", $id_cena = "", $pocet_zaznamu = "", $stav = "", $minuly_stav = "", $probihajici_transakce = 0, $id_cena_storno = 0, $id_cena2_storno = 0, $pocet_cena_storno = 0, $pocet_cena2_storno = 0) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //inicializace
        $this->legal_operation = 0;

        $this->pocet_zaznamu_insert = 0;

        $this->id_ceny = array();
        $this->pocet_ceny = array();
        $this->cislo_ceny = 0;
        $this->text_ceny = "";

        //kontrola dat
        $this->probihajici_transakce = $probihajici_transakce;
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_objednavka = $this->check_int($id_objednavka);
        $this->id_serial = $this->check_int($id_serial);
        $this->id_zajezd = $this->check_int($id_zajezd);
        $this->id_cena = $this->check_int($id_cena);
        $this->pocet_zaznamu = $this->check_int($pocet_zaznamu);

        $this->stav = $this->check_int($stav);
        $this->minuly_stav = $this->check_int($minuly_stav);

        $this->id_cena_storno = $this->check_int($id_cena_storno);
        $this->id_cena2_storno = $this->check_int($id_cena2_storno);
        $this->pocet_cena_storno = $this->check_int($pocet_cena_storno);
        $this->pocet_cena2_storno = $this->check_int($pocet_cena2_storno);

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            //na zaklade typu pozadavku vytvorim dotaz
            $this->legal_operation = 1;

            if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji zahajim
                $this->database->start_transaction();
            }

            //ziskam informace o objednavce - pokud je typ pozadavku = new, data uz mam
            if ($this->typ_pozadavku != "new") {
                $data_objednavka = $this->database->transaction_query($this->create_query("get_objednavka"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $objednavka = mysqli_fetch_array($data_objednavka);

                $this->id_zajezd = $objednavka["id_zajezd"];
                $this->id_serial = $objednavka["id_serial"];
                $this->stav = $objednavka["stav"];
            }

            if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update") {
                //zjistim informace o objednanych cenach
                $data_ceny = $this->database->transaction_query($this->create_query("get_ceny"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                if (!$this->get_error_message()) {
                    //vrat kapacity
                    while ($ceny = mysqli_fetch_array($data_ceny)) {
                        //zjistim pocet objednanych jednotek kapacity
                        if ($ceny["pocet"] == "") {
                            $ceny["pocet"] = 0;
                        }
                        $this->update_kapacity($ceny["id_cena"], $ceny["kapacita_volna"], $ceny["pocet"], $ceny["pocet"], 0);
                        if ($ceny["id_cena"] != "" && $ceny["id_objednavka"]) {
                            //stornuj VSECHNY ceny
                            $this->storno_cen($ceny["id_cena"], $ceny["id_objednavka"], $ceny["castka"], $ceny["mena"], $ceny["use_pocet_noci"]);
                        }
                    }
                }
                $this->query_insert = "INSERT INTO `objednavka_cena` (`id_objednavka`,`id_cena`,`pocet`,`cena_castka`,`cena_mena`,`use_pocet_noci`) VALUES ";


            } else if ($this->typ_pozadavku == "new") {
                $this->data = $this->database->transaction_query($this->create_query("new"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                    $this->database->commit();
                }

                //smazani cele objednavky, musim uvolnit kapacity a smazat ceny objednavky
            } else if ($this->typ_pozadavku == "delete_objednavky") {
                //musím vrátit kapacity, pokud jsem je mìl vyhrazené
                if ($this->stav >= 3 and $this->stav <= 7) {

                    //zjistim informace o objednanych cenach
                    $data_ceny = $this->database->transaction_query($this->create_query("get_ceny"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                    if (!$this->get_error_message()) {
                        while ($ceny = mysqli_fetch_array($data_ceny)) {
                            //zjistim pocet objednanych jednotek kapacity
                            if ($ceny["pocet"] == "") {
                                $ceny["pocet"] = 0;
                            }
                            $this->update_kapacity($ceny["id_cena"], $ceny["kapacita_volna"], $ceny["pocet"], $ceny["pocet"], 0);
                        }//end while
                    }
                }

                $this->delete_ceny_objednavky();
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                        $this->database->commit();
                    }
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }


                //stornovani objednavky objednavky, musim uvolnit kapacity
            } else if ($this->typ_pozadavku == "storno_objednavky") {
                //musim vyhodit vsechny klienty ze zasedaciho poradku
                //hledani pripadne prirazene topologie ke klientovi
                $data_topologie = $this->database->transaction_query($this->create_query("get_tok_topologie"))
                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->create_query("get_tok_topologie") );
                
                $data_klienti = $this->database->transaction_query($this->create_query("get_klienti"))
                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->create_query("get_klienti") );
                $klienti="";
                while ($row = mysqli_fetch_array($data_klienti)) {                    
                    $klienti = $row["klienti"];
                }                
                while ($topologie = mysqli_fetch_array($data_topologie)) {
                    $id_tok_topologie = $topologie["id_tok_topologie"];
                    $id_serial = $topologie["id_serial"];
                    $id_zajezd = $topologie["id_zajezd"];
                    $zaj_topologie = new Zajezd_topologie("delete_klient_zasedaci_poradek", $id_serial, $id_zajezd, $id_tok_topologie, $klienti);
                }
                
                $this->confirm("požadovaná akce probìhla úspìšnì");
                
                //musím vrátit kapacity, pokud jsem je mìl vyhrazené
                if ($this->minuly_stav >= Rezervace_library::$STAV_OPCE and $this->minuly_stav <= Rezervace_library::$STAV_ODBAVENO) {
                    //zjistim informace o objednanych cenach
                    $data_ceny = $this->database->transaction_query($this->create_query("get_ceny"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                    if (!$this->get_error_message()) {
                        //vrat kapacity
                        while ($ceny = mysqli_fetch_array($data_ceny)) {
                            //zjistim pocet objednanych jednotek kapacity
                            if ($ceny["pocet"] == "") {
                                $ceny["pocet"] = 0;
                            }
                            $this->update_kapacity($ceny["id_cena"], $ceny["kapacita_volna"], $ceny["pocet"], $ceny["pocet"], 0);
                            if ($ceny["id_cena"] != "" && $ceny["id_objednavka"]) {
                                //stornuj VSECHNY ceny
                                $this->storno_cen($ceny["id_cena"], $ceny["id_objednavka"], $ceny["castka"], $ceny["mena"], $ceny["use_pocet_noci"]);
                            }
                        }
                    }
                    //stornuj VSECHNY ceny2
                    $this->storno_cen2($this->id_objednavka);
                }
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                        $this->database->commit();
                    }
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
                //zmena stavu objednavky, ktera vyzaduje rezervaci kapacit zajezdu
            } else if ($this->typ_pozadavku == "rezervace_kapacit") {
                if (($this->stav >= Rezervace_library::$STAV_OPCE and $this->stav <= Rezervace_library::$STAV_ODBAVENO) and
                        ($this->minuly_stav < Rezervace_library::$STAV_OPCE or $this->minuly_stav > Rezervace_library::$STAV_ODBAVENO)) {

                    //zjistim informace o objednanych cenach
                    $data_ceny = $this->database->transaction_query($this->create_query("get_ceny"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                    if (!$this->get_error_message()) {
                        while ($ceny = mysqli_fetch_array($data_ceny)) {
                            //zjistim pocet objednanych jednotek kapacity
                            if ($ceny["pocet"] == "") {
                                $ceny["pocet"] = 0;
                            }
                            $this->update_kapacity($ceny["id_cena"], $ceny["kapacita_volna"], (-$ceny["pocet"]),0, $ceny["pocet"]);
                            if ($ceny["id_cena"] != "" && $ceny["id_objednavka"]) {
                                $this->vratit_storno_cen($ceny["id_cena"], $ceny["id_objednavka"]);
                            }
                        }
                    }
                    $this->vratit_storno_cen2($this->id_objednavka);
                }
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                        $this->database->commit();
                    }
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
            } else if ($this->typ_pozadavku == "show") {
                $this->data = $this->database->transaction_query($this->create_query("edit"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->data2 = $this->database->transaction_query($this->create_query("edit_ceny2"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                    $this->database->commit();
                }
            } else if ($this->typ_pozadavku == "edit") {
                $this->data = $this->database->transaction_query($this->create_query("edit"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->pocet_zaznamu = mysqli_num_rows($this->data);
                if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                    $this->database->commit();
                }
            } else if ($this->typ_pozadavku == "edit_ceny2") {
                if (isset($_POST["add_ceny2"]) && $_POST["add_ceny2"] != "") {
                    $this->data = $this->database->transaction_query($this->create_query("add_ceny2"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    @$this->pocet_zaznamu = mysqli_num_rows($this->data);
                    if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                        $this->database->commit();
                    }
                } else if (isset($_GET["del_ceny2"]) && $_GET["del_ceny2"] != "") {
                    $this->data = $this->database->transaction_query($this->create_query("del_ceny2"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                   // $this->pocet_zaznamu = mysqli_num_rows($this->data);
                    if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                        $this->database->commit();
                    }
                } else if (isset($_GET["storno_ceny2"]) && $_POST["stono_ceny2"] != "") {
                    //stornuj pocet ceny2
                    $this->storno_cen2($this->id_objednavka, $this->id_cena2_storno, $this->pocet_cena2_storno);

                    //vygenerování potvrzovací hlášky
                    if (!$this->get_error_message()) {
                        if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji potvrdim
                            $this->database->commit();
                        }
                        $this->confirm("Požadovaná akce probìhla úspìšnì");
                    }
                }
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------

    /** uprava kapacit sluzeb ajezdu */
    private function update_kapacity($id_cena, $puvodni_pocet, $zmena, $puvodni_pocet_osob, $novy_pocet_osob) {
        if ($zmena != 0) {
            $update_kapacity = "
			UPDATE `cena_zajezd`
			SET `kapacita_volna` = (`kapacita_volna`+" .  $zmena . ")
			WHERE `id_cena`=" . $id_cena . " and `id_zajezd`=" . $this->id_zajezd . "
			LIMIT 1";
            $vysledek = $this->database->transaction_query($update_kapacity)
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            
            
            //zmenim kapacity na objektech, pokud jsou pripojene
            $sql_objekty = "
                SELECT * FROM  `cena_zajezd_tok` join            
                    `objednavka_tok` on (`objednavka_tok`.`id_objednavka`=".$this->id_objednavka." and `cena_zajezd_tok`.`id_termin` = `objednavka_tok`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie` = `objednavka_tok`.`id_objekt_kategorie`) join
                    `objekt_kategorie_termin` on (`cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) join
                    `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) 
                   WHERE `cena_zajezd_tok`.`id_cena`=" . $id_cena . " and `cena_zajezd_tok`.`id_zajezd`=" . $this->id_zajezd . "
                   limit 1    
                ";
            $data = $this->database->transaction_query($sql_objekty);
            while ($row = mysqli_fetch_array($data)) {
                //mame prirazenou nejakou objednavku k TOKu, upravime pocty
                if($row["prodavat_jako_celek"]==0){
                    $puvodni_pocet_tok = ceil($puvodni_pocet_osob / $row["hlavni_kapacita"]);
                    $novy_pocet_tok = ceil(($novy_pocet_osob) / $row["hlavni_kapacita"]);
                    $zmena_tok = $puvodni_pocet_tok - $novy_pocet_tok;
                    $nova_kapacita_volna = $row["kapacita_volna"] + $zmena_tok;
                }else{
                    //nezajimaji nas kapacity pokoju atp.
                    $novy_pocet_tok = $novy_pocet_osob;
                    $nova_kapacita_volna = $row["kapacita_volna"] + $zmena;
                }
                
                $update_tok_objednavka = "UPDATE `objednavka_tok` SET `pocet` = " . $novy_pocet_tok . "
			WHERE `id_termin`=" . $row["id_termin"] . " and `id_objekt_kategorie`=" .  $row["id_objekt_kategorie"] . " and `id_objednavka`=" .  $this->id_objednavka . " LIMIT 1";
                $vysledek_tok = $this->database->transaction_query($update_tok_objednavka)
                    or $this->chyba("Chyba pøi dotazu do databáze: TOK " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                
                $update_tok_kapacity = "UPDATE `objekt_kategorie_termin` SET `kapacita_volna` = " . ($nova_kapacita_volna) . "
			WHERE `id_termin`=" . $row["id_termin"] . " and `id_objekt_kategorie`=" .  $row["id_objekt_kategorie"] . " LIMIT 1";
                $vysledek_tok = $this->database->transaction_query($update_tok_kapacity)
                    or $this->chyba("Chyba pøi dotazu do databáze: TOK " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }     
            
            return $vysledek;
        }
    }

    /**
     * Stornuje pocet osob
     */
    private function storno_cen($id_cena, $id_objednavka, $castka, $mena, $use_pocet_noci, $storno_pocet = 0) {
        //kompletni storno
        if ($storno_pocet == 0) {
            $storno_ceny = "
			UPDATE objednavka_cena 
                        SET pocet_storno = pocet, pocet = 0, castka = $castka, mena = '$mena', use_pocet_noci = $use_pocet_noci
			WHERE id_objednavka = $id_objednavka AND id_cena = $id_cena;";
            $vysledek = $this->database->transaction_query($storno_ceny) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        } else {
            $storno_ceny = "
			UPDATE objednavka_cena 
                        SET pocet_storno = $storno_pocet, pocet = pocet - $storno_pocet, castka = $castka, mena = '$mena', use_pocet_noci = $use_pocet_noci
			WHERE id_objednavka = $id_objednavka AND id_cena = $id_cena;";
            $vysledek = $this->database->transaction_query($storno_ceny) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        }

        return $vysledek;
    }

    /**
     * Stornuje pocet osob
     */
    private function storno_cen2($id_objednavka, $id_cena = 0, $storno_pocet = 0) {
        //kompletni storno
        if ($id_cena == 0) {
            $storno_ceny2 = "
			UPDATE objednavka_cena2
                        SET pocet_storno = pocet, pocet = 0
			WHERE id_objednavka = $id_objednavka;";
            $vysledek = $this->database->transaction_query($storno_ceny2) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        } else {
            $storno_ceny2 = "
			UPDATE objednavka_cena2
                        SET pocet_storno = $storno_pocet, pocet = pocet - $storno_pocet
			WHERE id_objednavka = $id_objednavka && id_cena = $id_cena;";
            $vysledek = $this->database->transaction_query($storno_ceny2) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        }
        return $vysledek;
    }

    /**
     * Presune pocet_stornovanych osob do poctu objednanych (zatim vsechny)
     */
    private function vratit_storno_cen($id_cena, $id_objednavka) {
        $storno_ceny = "
			UPDATE objednavka_cena 
                        SET pocet = pocet_storno, pocet_storno = NULL, castka = NULL, mena = NULL, use_pocet_noci = NULL
			WHERE id_objednavka = $id_objednavka AND id_cena = $id_cena;";
        $vysledek = $this->database->transaction_query($storno_ceny) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

        return $vysledek;
    }

    /**
     * Presune pocet_stornovanych osob do poctu objednanych (zatim vsechny)
     */
    private function vratit_storno_cen2($id_objednavka) {
        $storno_ceny = "
			UPDATE objednavka_cena2 
                        SET pocet = pocet_storno, pocet_storno = NULL
			WHERE id_objednavka = $id_objednavka;";
        $vysledek = $this->database->transaction_query($storno_ceny) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

        return $vysledek;
    }

    private function insert_ceny_objednavky($id_cena, $pocet, $castka, $mena, $use_pocet_noci) {
         $insert_kapacity = "(" . $this->id_objednavka . "," . $id_cena . "," . $pocet . ",".$castka.",\"".$mena."\",".$use_pocet_noci.")";

        return $insert_kapacity;
    }

    private function delete_ceny_objednavky() {
        //echo $this->create_query("delete_all_cena") ;

        $vysledek = $this->database->transaction_query($this->create_query("delete_all_cena"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

        return $vysledek;
    }

    /** prijima jednotlive objednavky sluzeb a vytvari z nich cast dotazu do db */
    function add_to_query($id_cena, $pocet, $castka, $mena, $use_pocet_noci) {
        //kontrola vstupnich dat
        $id_cena = $this->check_int($id_cena);
        $pocet = $this->check_int($pocet);
        $castka = $this->check_int($castka);
        $mena = $this->check($mena);
        $use_pocet_noci = $this->check_int($use_pocet_noci);


        //pokud jsou vporadku data, vytvorim danou cast dotazu
        if ($this->legal_data($id_cena, $pocet) and $this->legal_operation) {
            $this->cislo_ceny++;

            $this->id_ceny[$this->cislo_ceny] = $id_cena;
            $this->pocet_ceny[$this->cislo_ceny] = $pocet;
            $this->castka_ceny[$this->cislo_ceny] = $castka;
            $this->mena_ceny[$this->cislo_ceny] = $mena;
            $this->use_pocet_noci_ceny[$this->cislo_ceny] = $use_pocet_noci;
        }//if legal_data
    }

    /*     * kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.) */

    function correct_data($typ_pozadavku) {
        $ok = 1;
        //kontrolovane pole id_cena, id_zajezd
        if ($typ_pozadavku != "new") {
            if (!Validace::int_min($this->id_objednavka, 1)) {
                $ok = 0;
                $this->chyba("Je tøeba specifikovat objednávku!");
            }
        }
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {
            if (!Validace::int_min_max($this->pocet_zaznamu, 1, MAX_CEN)) {
                $ok = 0;
                $this->chyba("Poèet cen není v povoleném intervalu 1 - " . MAX_CEN . "");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*     * kontrola zda data o sluzbach jsou spravna( nenulova id  a pocet) */

    function legal_data($id_cena, $pocet) {
        $ok = 1;
        //kontrolovane pole id cena a poèet
        if (!Validace::int_min($id_cena, 1)) {
            $ok = 0;
        }
        if (!Validace::int_min($pocet, 1)) {
            $ok = 0;
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*     * po prijmuti vsech dat vytvori cely dotaz a odesle ho do mysql	 */

    function finish_query() {
        $this->celkova_cena = 0;
        $update_kapacity = array(); //pole pro pripadne dotazy se zmenou volne kapacity cen
        $insert_ceny = array(); //pole pro pripadne dotazy se zmenou volne kapacity cen
        //ziskani jednotlivych cen
        $data_ceny = $this->database->transaction_query($this->create_query("get_ceny"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

        //vyhrazení kapacity cen
        
        $mapa_cen_sluzeb = array();
        if (!$this->get_error_message()) {
            while ($ceny = mysqli_fetch_array($data_ceny)) {
                //najdu cislo ceny, pod kterym mam danou cenu ulozenou     
                if($ceny["cena_castka"]==0){
                    //kvuli starym zaznamum, kde neni cena_castka vubec vyplnena
                    $ceny["cena_castka"] = $ceny["castka"];
                }
                //naseldujici cyklus by mel probehnout dvakrat:jednou pro aktualni castku a jednou pro tu platnou v dobe objednavky (pokud se lisi)
                
              //castka platna v dobe objednavky  
              if($mapa_cen_sluzeb[$ceny["id_cena"]."-".$ceny["cena_castka"]] != 1){
                $cislo_ceny = false;
                foreach ($this->id_ceny as $key => $id) {
                    if($this->id_ceny[$key]==$ceny["id_cena"] and $this->castka_ceny[$key]==$ceny["cena_castka"]){
                        $cislo_ceny = $key;
                    }
                }                               
                $mapa_cen_sluzeb[$ceny["id_cena"]."-".$ceny["cena_castka"]] = 1;
                
                //pokud jsme nalezli cenu, zjistíme poèet objednávaných jednotek
                if ($cislo_ceny !== false) {
                    $pocet = $this->pocet_ceny[$cislo_ceny];
                } else {
                    $pocet = 0;
                }

                //pridam do celkove ceny
                $this->celkova_cena = $this->celkova_cena + ($pocet * $ceny["cena_castka"]);

                //z predchoziho stavu nemam objednavku teto kapacity
                if ($ceny["pocet"] == "") {
                    $ceny["pocet"] = 0;
                }

                //pokud mame rezervovanou nejakou kapacitu, je treba ji prerezervovat
                if ($this->stav >= 3 and $this->stav <= 7) {
                    $this->update_kapacity($ceny["id_cena"], $ceny["kapacita_volna"], ($ceny["pocet"] - $pocet), $ceny["pocet"], $pocet);
                }

                //vytvorime cast dotazu pro vlozeni ceny objednavky do databaze
                if ($pocet != 0) {
                    $insert_ceny[$this->pocet_zaznamu_insert] = $this->insert_ceny_objednavky($ceny["id_cena"], $pocet, $this->castka_ceny[$cislo_ceny], $this->mena_ceny[$cislo_ceny], $this->use_pocet_noci_ceny[$cislo_ceny]);
                    $this->pocet_zaznamu_insert++;
                }
              }
             
            }//end while
            mysqli_data_seek ( $data_ceny, 0 );
            while ($ceny = mysqli_fetch_array($data_ceny)) {
                 //aktualne platna castka (mohla se doobjednat) probehne jen pokud je jina, nez objednana
              if($mapa_cen_sluzeb[$ceny["id_cena"]."-".$ceny["castka"]] != 1){
                $ceny["pocet"] = 0;
                $cislo_ceny = false;
                foreach ($this->id_ceny as $key => $id) {
                    if($this->id_ceny[$key]==$ceny["id_cena"] and $this->castka_ceny[$key]==$ceny["castka"]){
                        $cislo_ceny = $key;
                    }
                }                               
                $mapa_cen_sluzeb[$ceny["id_cena"]."-".$ceny["castka"]] = 1;
                
                //pokud jsme nalezli cenu, zjistíme poèet objednávaných jednotek
                if ($cislo_ceny !== false) {
                    $pocet = $this->pocet_ceny[$cislo_ceny];
                } else {
                    $pocet = 0;
                }

                //pridam do celkove ceny
                $this->celkova_cena = $this->celkova_cena + ($pocet * $ceny["castka"]);

                //z predchoziho stavu nemam objednavku teto kapacity
                if ($ceny["pocet"] == "") {
                    $ceny["pocet"] = 0;
                }

                //pokud mame rezervovanou nejakou kapacitu, je treba ji prerezervovat
                if ($this->stav >= 3 and $this->stav <= 7) {
                    $this->update_kapacity($ceny["id_cena"], $ceny["kapacita_volna"], ($ceny["pocet"] - $pocet), $ceny["pocet"], $pocet);
                }

                //vytvorime cast dotazu pro vlozeni ceny objednavky do databaze
                if ($pocet != 0) {
                    $insert_ceny[$this->pocet_zaznamu_insert] = $this->insert_ceny_objednavky($ceny["id_cena"], $pocet, $this->castka_ceny[$cislo_ceny], $this->mena_ceny[$cislo_ceny], $this->use_pocet_noci_ceny[$cislo_ceny]);
                    $this->pocet_zaznamu_insert++;
                }
              }
            }//end while
            //smažu souèasné objednávky cen
            $this->delete_ceny_objednavky();


            //vytvoøím nové objednávky cen
            if ($this->pocet_zaznamu_insert) {
                //vytvorim zacatek dotazu - prvni hodnoty by zde mely byt vzdy
                $dotaz = $this->query_insert . $insert_ceny[0];
                $i = 1;
                while ($i < $this->pocet_zaznamu_insert) {
                    //skladam jednotlive casti dotazu
                    $dotaz = $dotaz . " , " . $insert_ceny[$i];
                    $i++;
                }
                //echo $dotaz;
                //odeslu dotaz
                $create_ceny = $this->database->transaction_query($dotaz)
                        or $this->chyba("Chyba pøi dotazu do databáze: " .$dotaz. mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }

            if (!$this->get_error_message()) {
                if ($this->probihajici_transakce == 0) {//pokud nejsem uprostred jine transakce, tak ji zahajim
                    $this->database->commit();
                }
                $this->confirm("Požadovaná akce probìhla úspìšnì");
            }
        }
    }

    /*
      //po prijmuti vsech dat vytvori cely dotaz a odesle ho do mysql
      function finish_query(){
      //print_r($this->query_insert);
      //print_r($this->query_update);

      if($this->pocet_zaznamu_insert){
      //vytvorim zacatek dotazu - prvni hodnoty by zde mely byt vzdy
      $dotaz=$this->query_insert[0].$this->query_insert[1];
      $i=2;
      while($i<=$this->pocet_zaznamu_insert){
      //skladam jednotlive casti dotazu
      $dotaz=$dotaz." , ".$this->query_insert[$i];
      $i++;
      }
      //echo $dotaz;
      //odeslu dotaz
      $create_ceny=$this->database->query($dotaz)
      or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));

      }
      if($this->pocet_zaznamu_update){
      $i=1;
      while($i<=$this->pocet_zaznamu_update){
      $dotaz=$this->query_update[0].$this->query_update[$i];
      //echo $dotaz;
      //skladam jednotlive dotazy a rovnou je odesilam
      $update_ceny=$this->database->query($dotaz)
      or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
      $i++;
      }
      }
      //vygenerování potvrzovací hlášky
      if( !$this->get_error_message() ){
      $this->confirm("Požadovaná akce probìhla úspìšnì");
      }

      }
     */
    /*     * vytvoreni dotazu podle typu pozadavku */

    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "edit") {
            $dotaz = "SELECT `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena`.`use_pocet_noci`,
                                `objednavka_cena`.`pocet`,`objednavka_cena`.`cena_castka`,`objednavka_cena`.`cena_mena`,`objednavka_cena`.`use_pocet_noci` as `cena_use_pocet_noci` ,
                                `objekt`.`kratky_nazev_objektu`,`objekt`.`id_objektu`,
                                `objekt_kategorie`.`kratky_nazev`, 
                                `objednavka_tok`.`pocet` as `pocet_tok`,
                                `objekt_kategorie_termin`.`id_termin`,
                                `objekt_kategorie_termin`.`id_objekt_kategorie`
                            FROM `objednavka`
                            JOIN `cena` ON (`cena`.`id_serial`=`objednavka`.`id_serial`)
                            JOIN `cena_zajezd` ON (`cena`.`id_cena`=`cena_zajezd`.`id_cena` and `cena_zajezd`.`id_zajezd` = `objednavka`.`id_zajezd`)
                            LEFT JOIN `objednavka_cena` ON (`objednavka_cena`.`id_cena`=`cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=" . $this->id_objednavka . ")
                            LEFT JOIN (`cena_zajezd_tok` join            
                                `objednavka_tok` on (`objednavka_tok`.`id_objednavka`=".$this->id_objednavka." and `cena_zajezd_tok`.`id_termin` = `objednavka_tok`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie` = `objednavka_tok`.`id_objekt_kategorie`) join
                                `objekt_kategorie_termin` on (`cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and `cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) join
                                `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) join
                                `objekt` on (`objekt_kategorie`.`id_objektu` = `objekt`.`id_objektu`) )
                                ON (`objednavka_cena`.`id_cena`=`cena_zajezd_tok`.`id_cena`)    
                           
                            WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
                            ORDER BY `zakladni_cena` DESC,`typ_ceny`,`nazev_ceny` ";
               //     echo "$dotaz<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "edit_ceny2") {
            $dotaz = "SELECT id_objednavka, id_cena, pocet, nazev_ceny, castka, mena, use_pocet_noci
                            FROM objednavka_cena2
                            WHERE id_objednavka=$this->id_objednavka
                            ORDER BY castka DESC;";
//                    echo "$dotaz<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "add_ceny2") {
            $nazev_ceny = $this->check($_POST["nazev_ceny"]);
            $castka = $this->check_int($_POST["castka"]);
            $mena = $this->check($_POST["mena"]);
            $use_pocet_noci = $this->check($_POST["use_pocet_noci"]);
            $use_pocet_noci = ($use_pocet_noci == "true" ? 1 : 0);
            $pocet = $this->check_int($_POST["pocet"]);
            $dotaz = "INSERT INTO objednavka_cena2 (id_objednavka, pocet, nazev_ceny, castka, use_pocet_noci, mena)
                            VALUES ($this->id_objednavka, $pocet, '$nazev_ceny', $castka, $use_pocet_noci, '$mena')";
//                    echo "$dotaz<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "del_ceny2") {
            $id_ceny = $this->check_int($_GET["del_ceny2"]);
            $dotaz = "DELETE FROM objednavka_cena2 WHERE id_cena=$id_ceny LIMIT 1;";
//                    echo "$dotaz<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "new") {
            $dotaz = "SELECT `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet`
                                            FROM `serial`
                                                    JOIN  `cena` ON (`serial`.`id_serial` = `cena`.`id_serial`)
                                                    JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
                                                    LEFT JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=" . $this->id_objednavka . ")
                                            WHERE `serial`.`id_serial`=" . $this->id_serial . " and `cena_zajezd`.`id_zajezd`=" . $this->id_zajezd . "";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show") {
            $dotaz = "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`kapacita_bez_omezeni`,
                                                            `cena_zajezd`.`id_zajezd`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
                                                            `cena_zajezd`.`kapacita_celkova`,`cena_zajezd`.`kapacita_volna`,
                                                            `cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`
                                      from `cena` left join
                                                     `cena_zajezd` on (`cena`.`id_cena`=`cena_zajezd`.`id_cena` and `id_zajezd`= " . $this->id_zajezd . ")
                                    where `id_serial`= " . $this->id_serial . "
                                    order by `zakladni_cena` desc,`typ_ceny`,`nazev_ceny` ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_objednavka") {
            $dotaz = "SELECT * FROM `objednavka`
                                            WHERE `id_objednavka`=" . $this->id_objednavka . "
                                            LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_klienti") {
            $dotaz = "SELECT group_concat(`id_klient` separator \",\") as `klienti` FROM `objednavka_osoby`
                                            WHERE `id_objednavka`=" . $this->id_objednavka . "
                                            LIMIT 1";
            //echo $dotaz;
            return $dotaz;    
            
        } else if ($typ_pozadavku == "get_ceny") {
            $dotaz = "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`kapacita_bez_omezeni`,
                                            `cena_zajezd`.`id_zajezd`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`kapacita_volna`,
                                            `objednavka_cena`.`cena_castka`,`objednavka_cena`.`pocet`,`objednavka_cena`.`id_objednavka`,`cena`.`use_pocet_noci`
                                    from `zajezd`
                                             join `cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`)
                                             join `cena`  on (`cena`.`id_cena` = `cena_zajezd`.`id_cena`)
                                             left join `objednavka_cena` on (`cena_zajezd`.`id_cena` = `objednavka_cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=" . $this->id_objednavka . ")
                                    where `zajezd`.`id_zajezd`=" . $this->id_zajezd . " ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `objednavka_cena`
                                            WHERE `id_cena`=" . $this->id_cena . " and `id_objednavka`=" . $this->id_objednavka . "
                                            LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_all_cena") {
            $dotaz = "DELETE FROM `objednavka_cena`
                                            WHERE `id_objednavka`=" . $this->id_objednavka . " ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `objednavka`
                                            WHERE `id_objednavka`=" . $this->id_objednavka . "
                                            LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if($typ_pozadavku=="get_tok_topologie"){
			$dotaz= "select `id_serial`,`id_tok_topologie`,`objednavka`.`id_zajezd` from `objednavka` join `zajezd_tok_topologie` on (`objednavka`.`id_zajezd` = `zajezd_tok_topologie`.`id_zajezd`)
					where `id_objednavka` = ".$this->id_objednavka."
			";
			//echo $dotaz;
			return $dotaz; 
        }
    }

    /*     * kontrola zda smi uzivatel provest danou akci */

    function legal($typ_pozadavku) {
        $zamestnanec = User_zamestnanec::get_instance();
        //z jadra zjistim ide soucasneho modulu
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();
        $id_modul_serial = $core->get_id_modul_from_typ("serial");



        //podle jednotlivych typu pozadavku
        if ($typ_pozadavku == "new") {
            return ( $zamestnanec->get_bool_prava($id_modul, "read") and $zamestnanec->get_bool_prava($id_modul_serial, "read"));
        } else if ($typ_pozadavku == "edit") {
            return ( $zamestnanec->get_bool_prava($id_modul, "read") and $zamestnanec->get_bool_prava($id_modul_serial, "read"));
        } else if ($typ_pozadavku == "show") {
            return ( $zamestnanec->get_bool_prava($id_modul, "read") and $zamestnanec->get_bool_prava($id_modul_serial, "read"));
        } else if ($typ_pozadavku == "create" or $typ_pozadavku == "delete" or $typ_pozadavku == "update" or
                $typ_pozadavku == "storno_objednavky" or $typ_pozadavku == "delete_objednavky" or $typ_pozadavku == "rezervace_kapacit") {
            //tvorba casti objednavky := editace objednavky
            if (($zamestnanec->get_bool_prava($id_modul, "edit_cizi") and $zamestnanec->get_bool_prava($id_modul_serial, "read") ) or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_bool_prava($id_modul_serial, "read") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "edit_ceny2") {
            return ( $zamestnanec->get_bool_prava($id_modul, "read") and $zamestnanec->get_bool_prava($id_modul_serial, "read"));
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /*     * zobrazeni formulare pro objednavku sluzeb */

    function show_form($pocty_cen="", $show_form=true) {

        //podle typu pozadavku vypisu cil formulare (u nove objednavky je formular soucasti formulare pro objednavku -> zadny cil)
        if ($this->typ_pozadavku == "edit" and $show_form==true) {
            $form = "<form action=\"?id_objednavka=" . $this->id_objednavka . "&amp;typ=rezervace&amp;pozadavek=update_ceny\" method=\"post\">\n";        
            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit ceny objednávky\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n</form>";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci této objednávky</strong>\n";
            }
        } else {
            $form = "";
            $submit = "";
        }
        $vystup = $form . "<table>";

        $i = 0;
        while ($result_ceny = mysqli_fetch_array($this->data)) {
            $i++;
            if($result_ceny["cena_castka"]=="" and $result_ceny["cena_mena"]==""){
                //nemame puvodni udaje, zadame tam aktualni z tabulky cena_zajezd
                $castka = $result_ceny["castka"] ;
                $mena =  $result_ceny["mena"];
                $use_pocet_noci = intval($result_ceny["use_pocet_noci"]);

                                
            }else{
                //mam puvodni udaje, dam moznost je editovat
                $castka = $result_ceny["cena_castka"] ;
                $mena =  $result_ceny["cena_mena"];
                $use_pocet_noci = intval($result_ceny["cena_use_pocet_noci"]);                
            }
            if(is_array($pocty_cen)){
                $objednavka_pocet = $this->check_int($pocty_cen[$result_ceny["id_cena"]]) ;
            }else{
                $objednavka_pocet = $this->check_int($result_ceny["pocet"]) ;
            }
            
            $vystup = $vystup . "
				<tr>
                                        <td><input type=\"hidden\" name=\"id_cena_" . $i . "\" value=\"" . $result_ceny["id_cena"] . "\" />\n                                            
					" . $result_ceny["nazev_ceny"] . " 
                                        <td><input type=\"hidden\" name=\"castka_" . $i . "\" value=\"" . $castka. "\" />
                                             <input type=\"hidden\" name=\"mena_" . $i . "\" value=\"" . $mena. "\" />
                                             <input type=\"hidden\" name=\"use_pocet_noci_" . $i . "\" value=\"" . $use_pocet_noci. "\" />
                                        " . $castka . " " . $mena. "\n
					<td><input name=\"pocet_" . $i . "\" type=\"text\" value=\"" . $objednavka_pocet . "\" />	\n
				";
        }
        $vystup = $vystup . "</table>
                    <input type=\"hidden\" name=\"pocet_cen\" value=\"" . $i . "\" />\n";
        $vystup = $vystup . $submit;

        return $vystup;
    }

    /*     * zobrazeni sluzeb objednanych v konkretni objednavce */

    function show($typ_zobrazeni, $exportovana="", $id_exportovana_objednavka="") {

        //ceny objednavky zadane uzivatelem
        //podle typu pozadavku vypisu cil formulare (u nove objednavky je formular soucasti formulare pro objednavku -> zadny cil)
        if ($typ_zobrazeni == "tabulka") {
            $vystup = "
                            <form method=\"post\" action=\"?id_objednavka=$this->id_objednavka&typ=rezervace&pozadavek=update_ceny\">
				<table class=\"list\">
					<tr>
						<th colspan=\"8\">
							Objednávané služby
						</th>
					</tr>
					<tr>
						<th>Id</th>
						<th>Název služby</th>
						<th>Cena</th>
						<th>Poèet</th>
                                                <th>Násobit poètem nocí</th>
						<th>Poèet storno</th>
						
                                                <th>Rezervovaná kapacita TOK</th>
                                                <th>Akce</th>
					</tr>
			";
            $i = 0;
            //mapa ve tvaru id_ceny-castka. slouzi k tomu, abychom vickrat nepublikovali stejne sluzby (kontrola pred vypsanim)
            $mapa_cen_sluzeb = array();
            $ceny_nove_id = array();
            $ceny_nove_nazvy = array();
            $ceny_nove_castky = array();            
            $ceny_nove_meny = array();
            $ceny_nove_use_pocet_noci = array();
            
            while ($result_ceny = mysqli_fetch_array($this->data)) {
                $i++;
                if($result_ceny["pocet_tok"]>0){
                   $tok_text = " <a href=\"/admin/objekty.php?id_objektu=".$result_ceny["id_objektu"]."&id_termin=".$result_ceny["id_termin"]."&typ=tok&pozadavek=show\">
                                                        ".$result_ceny["pocet_tok"].": ".$result_ceny["kratky_nazev_objektu"].", ".$result_ceny["kratky_nazev"]."</a>";
                }else{
                   $tok_text = ""; 
                }
            if($result_ceny["cena_mena"]==""){
                //nemame puvodni udaje, zadame tam aktualni z tabulky cena_zajezd
                $act_castka = $result_ceny["castka"] ;
                $castka = $result_ceny["castka"] ;
                $mena =  $result_ceny["mena"];
                $use_pocet_noci = intval($result_ceny["use_pocet_noci"]);                
                                
            }else{
                //mam puvodni udaje, dam moznost je editovat
                $act_castka = $result_ceny["cena_castka"] ;
                $castka = $result_ceny["cena_castka"] ;
                $mena =  $result_ceny["cena_mena"];
                $use_pocet_noci = intval($result_ceny["cena_use_pocet_noci"]);     
                if($result_ceny["castka"]!=$result_ceny["cena_castka"]){
                    $text_cena = " (cena platná v dobì objednávky)";
                    $castka = "<span title=\"Tuènì je cena platná v dobì objednávky, kurzívou souèasná cena\"><b>".$result_ceny["cena_castka"]."</b> / <i style=\"color:#999999\">".$result_ceny["castka"]."</i></span>";
                    //pridam moznost dodat jeste sluzbu se soucasnou cenou
                    $ceny_nove_id[] = $result_ceny["id_cena"];
                    $ceny_nove_nazvy[] = $result_ceny["nazev_ceny"];
                    $ceny_nove_castky[] = $result_ceny["castka"];            
                    $ceny_nove_meny[] = $result_ceny["mena"];
                    $ceny_nove_use_pocet_noci[] = $use_pocet_noci;
                    $ceny_nove_tok[] = $tok_text;
                }else{
                    $text_cena = "";
                }
            }
            $mapa_cen_sluzeb[$result_ceny["id_cena"]."-".$act_castka] = 1;
                //if($result_ceny["pocet"]>0){
                if($result_ceny["cena_mena"]==""){
                    $row_nasobit_poctem_noci = "<input type=\"hidden\" value=$use_pocet_noci name=\"use_pocet_noci_" . $i . "\" /> ".(($use_pocet_noci==1)?("ANO"):("NE"));
                }else{
                    $row_nasobit_poctem_noci = "<input type=\"checkbox\" value=1 name=\"use_pocet_noci_" . $i . "\" ".(($use_pocet_noci==1)?("checked=\"checked\""):("")). "\" />*";                                        
                }
                
                $vystup = $vystup . "
                                        <tr class=\"suda\">
                                                <td>" . $result_ceny["id_cena"] . "</td>
                                                <td>" . $result_ceny["nazev_ceny"] . $text_cena . "</td>
                                                <td>" . $castka . " " . $mena . "</td>
                                                <td>
                                                    <input type=\"text\" value=\"" . $result_ceny["pocet"] . "\" name=\"pocet_$i\" size=\"4\">                                                    
                                                    <input type=\"hidden\" value=\"" . $result_ceny["id_cena"] . "\" name=\"id_cena_$i\">
                                                    <input type=\"hidden\" name=\"castka_" . $i . "\" value=\"" . $act_castka. "\" />
                                                    <input type=\"hidden\" name=\"mena_" . $i . "\" value=\"" . $mena. "\" />
                                                    <input type=\"hidden\" name=\"use_pocet_noci_" . $i . "\" value=\"" . $use_pocet_noci. "\" />    
                                                </td>
                                                <td>
                                                    $row_nasobit_poctem_noci  
                                                </td>
                                                <td>
                                                    <input type=\"text\" value=\"" . $result_ceny["pocet_storno"] . "\" name=\"pocet_storno_$i\" size=\"4\">
                                                </td>
                                                <td>
                                                    ".$tok_text."                                                    
                                                </td>
                                                <td class='edit'>
                                                    <input disabled='true' type='submit' value='Storno' />
                                                </td>
                                                
                                        </tr>
                                    ";
                //ten vnoreny formular delal dost velky problem napriklad v IE (zmeny cen se vubec neodesilaly), zkus to vymyslet jinak - napr. pres input type=button a ajax
                // <form method='post' style='display: inline;' action='?typ=rezervace&pozadavek=edit_ceny&id_objednavka=" . $_GET["id_objednavka"] . "&storno_ceny=" . $result_ceny["id_cena"] . "' >
                   //   <input disabled='true' type='submit' value='Storno' />
                // </form>
                //}
            }
            //pridam moznost objednavky soucasne platnych cen sluzeb
            foreach ($ceny_nove_id as $key => $value) {                
                $text_cena = " (služba za v souèasnosti platnou cenu)";
                if($mapa_cen_sluzeb[$ceny_nove_id[$key]."-".$ceny_nove_castky[$key]] != 1){
                    $i++;
                    $mapa_cen_sluzeb[$ceny_nove_id[$key]."-".$ceny_nove_castky[$key]] = 1;
                    $vystup = $vystup . "
                                        <tr class=\"suda\">
                                                <td>" . $ceny_nove_id[$key] . "</td>
                                                <td><i>" . $ceny_nove_nazvy[$key] . $text_cena . "</i></td>
                                                <td>" . $ceny_nove_castky[$key] . " " . $ceny_nove_meny[$key] . "</td>
                                                <td>
                                                    <input type=\"text\" value=\"0\" name=\"pocet_$i\" size=\"4\">                                                    
                                                    <input type=\"hidden\" value=\"" . $ceny_nove_id[$key]. "\" name=\"id_cena_$i\">
                                                    <input type=\"hidden\" name=\"castka_" . $i . "\" value=\"" .  $ceny_nove_castky[$key]. "\" />
                                                    <input type=\"hidden\" name=\"mena_" . $i . "\" value=\"" . $ceny_nove_meny[$key]. "\" />  
                                                </td>
                                                <td>
                                                   <input type=\"hidden\" value=".$ceny_nove_use_pocet_noci[$key]." name=\"use_pocet_noci_" . $i . "\" /> ".(($ceny_nove_use_pocet_noci[$key]==1)?("ANO"):("NE"))." 
                                                </td>
                                                <td>
                                                    <input type=\"text\" value=\"\" name=\"pocet_storno_$i\" size=\"4\">
                                                </td>
                                                <td>
                                                    ".$ceny_nove_tok[$key]."                                                    
                                                </td>
                                                <td class='edit'>
                                                    <input disabled='true' type='submit' value='Storno' />
                                                </td>
                                                
                                        </tr>
                                    ";                                
                }
            }
            if($exportovana==1){
                $ulozit = "Objednávka je exportována z LOH, <br/>zmìny služeb je tøeba provést <a href=\"http://rio-2016.net/admin/rezervace.php?id_objednavka=$id_exportovana_objednavka&amp;typ=rezervace&amp;pozadavek=show\">tam</a>.";
            }else{
                $ulozit = "<input type=\"submit\" value=\"Uložit\" />";
            }
            $vystup .= "<tr class=\"edit\"><td colspan='7'>* násobení poètem nocí mìnit jen v odùvodnìných pøípadech (napø. chyba u pùvodního zadání služby)</td>
                        <td>$ulozit</td></tr>";
            $vystup .= "</table>
                                    <input type=\"hidden\" name=\"pocet_cen\" value=\"$i\" />
                                </form>\n";
            //cemy pridane primo v adminovi
        } else if ($typ_zobrazeni == "tabulka_edit_ceny") {
            $vystup = "   <table class='list'>
                                <tr>
                                    <th colspan='7'>
                                            Ruènì pøídané služby
                                    </th>
                                </tr>
                                <tr>
                                    <th>Id</th>
                                    <th>Název služby</th>
                                    <th>Cena</th>
                                    <th>Poèet</th>
                                    <th>Poèet storno</th>
                                    <th title='Použít poèet nocí'>Použít poè. nocí</th>
                                    <th>Akce</th>
                                </tr>";

            while ($result_ceny = mysqli_fetch_array($this->data2)) {
                $result_ceny["use_pocet_noci"] = $result_ceny["use_pocet_noci"] == 1 ? "Ano" : "Ne";
                if($exportovana==1){
                    $akce = "Objednávka je exportována z LOH, <br/>zmìny služeb je tøeba provést <a href=\"http://rio-2016.net/admin/rezervace.php?id_objednavka=$id_exportovana_objednavka&amp;typ=rezervace&amp;pozadavek=show\">tam</a>.";
                }else{
                    $akce = "<form method='post' style='display: inline;' action='?typ=rezervace&pozadavek=edit_ceny2&id_objednavka=" . $_GET["id_objednavka"] . "&del_ceny2=" . $result_ceny["id_cena"] . "' >
                                <input type='submit' value='Odebrat' />
                            </form>
                            <form method='post' style='display: inline;' action='?typ=rezervace&pozadavek=edit_ceny2&id_objednavka=" . $_GET["id_objednavka"] . "&storno_ceny2=" . $result_ceny["id_cena"] . "' >
                                <input type='submit' disabled='true' value='Storno' />
                            </form>";
                }
                
                
                $vystup .= "<tr class=\"suda\">
                                            <td>" . $result_ceny["id_cena"] . "</td>
                                            <td>" . $result_ceny["nazev_ceny"] . "</td>
                                            <td>" . $result_ceny["castka"] . " " . $result_ceny["mena"] . "</td>
                                            <td class=\"align_center\">" . $result_ceny["pocet"] . "</td>
                                                <td class=\"align_center\">" . $result_ceny["pocet_storno"] . "</td>
                                            <td class=\"align_center\">" . $result_ceny["use_pocet_noci"] . "</td>
                                            <td class=\"edit\">
                                                $akce
                                            </td>
                                         </tr>";
            }
            if($exportovana!=1){
                $vystup .= "<tr class=\"edit\">
                                    <td></td>
                                    <td><input type='text' name='nazev_ceny' size='30' /></td>
                                    <td>
                                        <input type='text' name='castka' size='10' />
                                        <select name='mena' ><option value='Kè'>Kè</option></select>
                                    </td>
                                    <td class=\"align_center\"><input type='text' name='pocet' size='2' /></td>
                                    <td></td>
                                    <td class=\"align_center\"><input type='checkbox' name='use_pocet_noci' /></td>                                    
                                    <td>
                                        <form method='post' action='?typ=rezervace&pozadavek=edit_ceny2&id_objednavka=" . $_GET["id_objednavka"] . "'>
                                            <input type='hidden' id='hid_nazev_ceny_ceny2' name='nazev_ceny' />
                                            <input type='hidden' id='hid_castka_ceny2' name='castka' />
                                            <input type='hidden' id='hid_mena_ceny2' name='mena' />
                                            <input type='hidden' id='hid_pocet_ceny2' name='pocet' />
                                            <input type='hidden' id='hid_use_pocet_noci_ceny2' name='use_pocet_noci' />
                                            <input type='submit' name='add_ceny2' value='Pøidat' onclick='return copy_add_ceny2_form();' />
                                        </form>
                                    </td>
                                </tr>";
            }    
            $vystup .= "</table>";
        }
        return $vystup;
    }

    /* metody pro pristup k parametrum */

    function get_id_serial() {
        return $this->radek["id_serial"];
    }

    function get_id_cena() {
        return $this->radek["id_cena"];
    }
    
    function get_objednavka_pocet() {
        return $this->radek["pocet"];
    }
    
    function get_pocet() {
        return $this->pocet_zaznamu;
    }

    function get_nazev_ceny() {
        return $this->radek["nazev_ceny"];
    }

    function get_typ_ceny() {
        return $this->radek["typ_ceny"];
    }

    function get_zakladni_cena() {
        return $this->radek["zakladni_cena"];
    }

    function get_kapacita_bez_omezeni() {
        return $this->radek["kapacita_bez_omezeni"];
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

}

?>
