<?php
/**
 * rezervace.inc.php - tridy pro zobrazeni rezervace - obecných informcí
 */

/*--------------------- SERIAL -------------------------------------------*/
class TiskoveSestavyObjednavka extends Generic_data_class
{
    //vstupni data

    protected $id_objednavka;

    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /*konstruktor tøídy na základì typu požadavku a formularovych poli
        -	konstruktor zaroven vola funkce pro upravu kapacit sluzeb*/
    function __construct($id_objednavka)
    {
        $zamestnanec = User_zamestnanec::get_instance();
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->id_objednavka = $this->check_int($id_objednavka);
    }

    /**
     *spocte spravne celkovou cenu za sluzbu (v nekterych pripadech je treba ji nasobit poctem noci
     * @param type $castka
     * @param type $pocet
     * @param int $pocet_noci
     * @param type $use_pocet_noci identifikator zda se ma castka nasobit poctem noci
     * @return type
     */
    function computePrice($castka, $pocet, $pocet_noci, $use_pocet_noci = 0)
    {
        if ($pocet_noci == 0) {
            $pocet_noci = 1;
        }
        if ($use_pocet_noci != 0) {
            return $castka * $pocet * $pocet_noci;
        } else {
            return $castka * $pocet;
        }

    }

    /**
     * return objekt tsZajezd(nazev_serialu,nazev_zajezdu, termin_od, termin_do)
     */
    function dataZajezd()
    {
        $dataSerial = $this->database->query($this->create_query("select_zajezd"));
        while ($row = mysqli_fetch_array($dataSerial)) {
            //v objednavce muze byt upresneni terminu zajezdu:
            $dataObjednavka = $this->database->query($this->create_query("select_objednavka"));
            while ($row2 = mysqli_fetch_array($dataObjednavka)) {
                if ($row2["termin_od"] != "" and $row2["termin_od"] != "0000-00-00") {
                    $row["od"] = $row2["termin_od"];
                }
                if ($row2["termin_do"] != "" and $row2["termin_do"] != "0000-00-00") {
                    $row["do"] = $row2["termin_do"];
                }
            }
            //do nazvu serialu automaticky pridam ubytovani
            if ($row["nazev_ubytovani"] != "") {
                $row["nazev"] = $row["nazev_ubytovani"] . " - " . $row["nazev"];
            }
            return new tsZajezd($row["id_zajezd"], $row["id_serial"], $row["nazev"], $row["nazev_zajezdu"], $row["od"], $row["do"]);
        }
    }

    /**
     * return objekt tsObjednavajici(jmeno a prijmeni, adresa, pripadne dalsi pole dle potreby - telefon, email...)
     */
    function dataObjednavajici()
    {
        $dataObjednavajici = $this->database->query($this->create_query("select_klient"));
        while ($row = mysqli_fetch_array($dataObjednavajici)) {
            return new tsObjednavajici($row["id_klient"], $row["titul"], $row["jmeno"], $row["prijmeni"], $row["ulice"], $row["mesto"], $row["psc"], "", $row["email"], $row["telefon"]);
        }
    }

    /** Objednávka, sleva, provize
     * return objekt tsObjednavka(id_objednavky, pocet_osob, datum_rezervace, celkova_cena, zbyva_zaplatit, pocet_noci,
     *                              nazev_slevy, velikost_slevy, suma_slevy, provize_vc_dph, suma_provize, poznamka_provize, )
     */
    function dataObjednavka()
    {
        $dataObjednavka = $this->database->query($this->create_query("select_objednavka"));
        while ($row = mysqli_fetch_array($dataObjednavka)) {
            return new tsObjednavka($row["id_objednavka"], $row["pocet_osob"], $row["celkova_cena"], $row["zbyva_zaplatit"], $row["poznamky"], $row["pocet_noci"],
                $row["nazev_slevy"], $row["castka_slevy"], $row["velikost_slevy"], $row["provize_vc_dph"], $row["suma_provize"], $row["poznamka_provize"], $row["stav"], $row["storno_datum"], $row["storno_poplatek"]);
        }
    }


    /**
     * return objekt tsProdejce(nazev, ico, adresa_prodejce )
     */
    function dataProdejce()
    {
        $dataProdejce = $this->database->query($this->create_query("select_prodejce"));
        while ($row = mysqli_fetch_array($dataProdejce)) {
            return new tsProdejce($row["nazev_agentury"], $row["kontaktni_osoba"], $row["ico"], $row["ulice"], $row["mesto"], $row["psc"], "", $row["email"], $row["telefon"]);
        }
        return null;
    }

    /**
     * return pole objektu tsSluzba(nazev, jednotkova_cena, pocet, pouzit pocet noci )
     * nejlip se prochazi pomoci foreach:
     * foreach ($sluzby as $value) {
    ...
    }
     * pro vypocet celkove ceny za danou sluzbu pouzij metodu computePrice(jednotkova_cena, pocet, pocet_noci, use_pocet_noci)
     */
    function dataSluzby()
    {
        $sluzby = array();
        $dataSluzby = $this->database->query($this->create_query("select_ceny"));
        while ($row = mysqli_fetch_array($dataSluzby)) {
            $sluzby[] = new tsSluzba($row["id_cena"], $row["nazev_ceny"], $row["castka"], $row["mena"], $row["pocet"], $row["use_pocet_noci"]);
        }
        //pridavaji se i sluzby dodane bez vazby na zajezd
        $dataSluzby = $this->database->query($this->create_query("select_ceny2"));
        while ($row = mysqli_fetch_array($dataSluzby)) {
            $sluzby[] = new tsSluzba($row["id_cena"], $row["nazev_ceny"], $row["castka"], $row["mena"], $row["pocet"], $row["use_pocet_noci"]);
        }
        return $sluzby;
    }

    /**
     * return pole objektu tsOsoba(nazev, jednotkova_cena, pocet, cena_za_sluzbu )
     */
    function dataOsoby()
    {
        $osoby = array();
        $dataOsoby = $this->database->query($this->create_query("select_osoby"));
        while ($row = mysqli_fetch_array($dataOsoby)) {
            //sjednotim datum narozeni a rc
            if ($row["rodne_cislo"] == "") {
                $row["rodne_cislo"] = $row["datum_narozeni"];
            }
            //sjednotim cislo pasu a cislo op
            if ($row["cislo_op"] == "") {
                $row["cislo_op"] = $row["cislo_pasu"];
            }
            $osoby[] = new tsOsoba(null, $row["jmeno"], $row["prijmeni"], $row["rodne_cislo"], $row["cislo_op"], $row["telefon"], $row["email"]);
        }

        return $osoby;
    }

    /**
     * return pole objektu tsPlatba(nazev, jednotkova_cena, pocet, cena_za_sluzbu )
     */
    function dataPlatby()
    {
        $platby = array();
        $dataSluzby = $this->database->query($this->create_query("select_platby"));
        while ($row = mysqli_fetch_array($dataSluzby)) {
            $platba = new tsPlatba($row["cislo_dokladu"], $row["zpusob_uhrady"], $row["castka"], $row["vystaveno"], $row["splatit_do"], $row["splaceno"]);
        }

        return $platby;
    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    function create_query($typ_pozadavku)
    {
        if ($typ_pozadavku == "select_objednavka") {
            $dotaz = "SELECT * FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_platby") {
            $dotaz = "SELECT * FROM `objednavka_platba`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_osoby") {
            $dotaz = "SELECT `user_klient`.`id_klient`, `user_klient`.`jmeno`, `user_klient`.`prijmeni`, `user_klient`.`titul`,
                                            `user_klient`.`datum_narozeni`, `user_klient`.`rodne_cislo`, `user_klient`.`telefon`,`user_klient`.`email`,
                                            `user_klient`.`cislo_pasu`,`user_klient`.`cislo_op`, `user_klient`.`ulice`, `user_klient`.`mesto`, 
                                            `user_klient`.`psc`,`objednavka_osoby`.`cislo_osoby` 
                                        FROM `objednavka_osoby` JOIN `user_klient` ON (`objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`)
                                        WHERE `objednavka_osoby`.`id_objednavka`=$this->id_objednavka 
                                        ORDER BY `objednavka_osoby`.`cislo_osoby`
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_klient") {
            $dotaz = "SELECT `user_klient`.`id_klient`,`jmeno`,`prijmeni`,`email`,`telefon`,`datum_narozeni`,`email`,`ulice`,`mesto`,`psc`
						FROM `user_klient` join `objednavka` on (`objednavka`.`id_klient` =`user_klient`.`id_klient`)
						WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_zajezd") {
            $dotaz = "SELECT `serial`.`id_serial`,`serial`.`nazev`,`zajezd`.*,
                                                `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`
						FROM `serial`
                                                JOIN  `zajezd` ON (`serial`.`id_serial` = `zajezd`.`id_serial`)
                                                join `objednavka` on (`objednavka`.`id_serial` =`serial`.`id_serial` and `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                                left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
						WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny") {
            $dotaz = "SELECT distinct `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`use_pocet_noci`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet`
						FROM  `cena` 
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
							JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena`)
                                                        JOIN `objednavka` ON (`objednavka_cena`.`id_objednavka` = `objednavka`.`id_objednavka` and
                                                                               `objednavka`.`id_serial` = `cena`.`id_serial` and 
                                                                               `objednavka`.`id_zajezd` = `cena_zajezd`.`id_zajezd` )
						WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny2") {
            $dotaz = "SELECT distinct `objednavka_cena2`.`id_cena`,`objednavka_cena2`.`nazev_ceny`,`objednavka_cena2`.`use_pocet_noci`,`objednavka_cena2`.`castka`,`objednavka_cena2`.`mena`
						FROM `objednavka_cena2` 
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "select_sleva") {
            $dotaz = "SELECT `nazev_slevy`,`castka_slevy`,`velikost_slevy` FROM `objednavka`
						WHERE `id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
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
            $dotaz = "SELECT `prodejce`.`jmeno` as `nazev_agentury`, `prodejce`.`prijmeni` as `kontaktni_osoba`, `prodejce`.`ico` as `ico`,
                                `prodejce`.`email` as `email`, `prodejce`.`telefon` as `telefon`, `prodejce`.`mesto` as `mesto`, `prodejce`.`ulice` as `ulice`,
                                `prodejce`.`psc` as `psc` FROM `objednavka`
                                                 join `user_klient` as `prodejce` on (`objednavka`.`id_agentury` = `prodejce`.`id_klient`)
						WHERE `objednavka`.`id_objednavka`=" . $this->id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
    }


}


?>