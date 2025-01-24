<?php
/**
 * rezervace.inc.php - tridy pro zobrazeni rezervace - obecných informcí
 */
/*--------------------- SERIAL -------------------------------------------*/

class ObjednavkaDAO extends Generic_data_class
{

    const STAV_STORNO = 8;
    const STAV_STORNO_CK = 9;
    /**
     * @var Database
     */
    private static $database;

    static function init()
    {
        self::$database = Database::get_instance();
    }

    /**
     * @param $id_objednavka
     * @return tsZajezd
     */
    static function dataZajezd($id_objednavka)
    {
        $dataSerial = self::$database->query(self::create_query("select_zajezd", $id_objednavka));
        while ($row = mysqli_fetch_array($dataSerial)) {
            //v objednavce muze byt upresneni terminu zajezdu:
            $dataObjednavka = self::$database->query(self::create_query("select_objednavka", $id_objednavka));
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
            $zajezd = new tsZajezd($row["id_zajezd"], $row["id_serial"], $row["nazev"], $row["nazev_zajezdu"], $row["od"], $row["do"]);
          //  print_r($row2);
          //  print_r($zajezd);
           // exit();
            $zajezd->cenaZahrnuje = $row["cena_zahrnuje"];
            if($row["povinne_poplatky"]!=""){
                $zajezd->povinnePoplatky = "Upozoròujeme na následující povinné poplatky: ".$row["povinne_poplatky"]."<br/>";
            }else{
                $zajezd->povinnePoplatky = "";
            }
            return $zajezd;
        }
    }

    /**
     * @param $id_objednavka
     * @return tsObjednavajici
     */
    static function dataObjednavajici($id_objednavka)
    {
        $dataObjednavajici = self::$database->query(self::create_query("select_klient", $id_objednavka));
        while ($row = mysqli_fetch_array($dataObjednavajici)) {
            return new tsObjednavajici(
                    $row["id_klient"], $row["titul"], $row["jmeno"], $row["prijmeni"], $row["ulice"], 
                    $row["mesto"], $row["psc"], "", $row["email"], $row["telefon"], 
                    $row["datum_narozeni"], $row["storno"]
                    );
        }
    }

    public static function dataObjednavajiciOrg($id_objednavka)
    {
        $dataObjednavajici = self::$database->query(self::create_query("select_objednavajici_org", $id_objednavka));
        $objednavajiciOrg = null;
        while ($row = mysqli_fetch_array($dataObjednavajici)) {
            $objednavajiciOrg = new tsOrganizace($row["ico"], $row["id_organizace"], $row["nazev"], null);
            $objednavajiciOrg->email = $row["email"];
            $objednavajiciOrg->telefon = $row["telefon"];
            $objednavajiciOrg->adresa = new tsAdresa(null, $row["mesto"], $row["psc"], null, $row["ulice"]);
        }
        return $objednavajiciOrg;
    }

    /**
     * @param $id_objednavka
     * @return tsObjednavka
     */
    static function dataObjednavkaList($params, $type, $noStorno=0)
    {
        /*params: colName: value*/
        //print_r($params);
        $SQL_params = array();
        if($params["zajezdy_dle_zakona"]==1){
            $SQL_params[] = " `serial`.`zajezd_dle_zakona`=1 ";
        }
        if($type == HlaseniPojistovneTS::TYPE_NOVE_OBJEDNAVKY){
            if($params["objednavka_termin_od"]!=""){
                $SQL_params[] = " `objednavka`.`datum_rezervace`>=\"".$params["objednavka_termin_od"]." 00:00:00\"";
            }
            if($params["objednavka_termin_do"]!=""){
                $SQL_params[] = " `objednavka`.`datum_rezervace`<=\"".$params["objednavka_termin_do"]." 23:59:59\"";
            }
            $order_by = "Order by `objednavka`.`datum_rezervace`";
            
        }else if($type == HlaseniPojistovneTS::TYPE_REALIZOVANE_OBJEDNAVKY){   
            $terminOd = $params["zajezd_termin_od"];
            $terminDo = $params["zajezd_termin_do"];
            if($params["zajezd_termin_od"]!=""){
                $SQL_params[] = " ((`objednavka`.`termin_od`!=\"0000-00-00\" and `objednavka`.`termin_od`>=\"".$terminOd."\") or (`objednavka`.`termin_od`=\"0000-00-00\" and `zajezd`.`od`>=\"".$terminOd."\"))";
            }
            if($params["zajezd_termin_do"]!=""){
                $SQL_params[] = " ((`objednavka`.`termin_od`!=\"0000-00-00\" and `objednavka`.`termin_od`<=\"".$terminDo."\") or (`objednavka`.`termin_od`=\"0000-00-00\" and`zajezd`.`od`<=\"".$terminDo."\"))";
            }
            $order_by = "Order by GREATEST(`objednavka`.`termin_od`,`zajezd`.`od`), GREATEST(`objednavka`.`termin_do`,`zajezd`.`do`)";
        }
        $SQL_params = implode(" and ", $SQL_params);
        
        $dataObjednavka = self::$database->query(self::create_query("select_objednavky_list", "", $SQL_params,$order_by));
        $objednavky = array();
        $sum_pocet_osob = 0;
        $sum_aktualni_platba = 0;
        $sum_celkova_castka = 0;
        while ($row = mysqli_fetch_array($dataObjednavka)) {
            $objednavka = new tsObjednavka($row["id_objednavka"], $row["pocet_osob"], $row["celkova_cena"], $row["zbyva_zaplatit"], $row["poznamky"], $row["pocet_noci"], $row["datum_rezervace"], $row["stav"], $row["storno_datum"], $row["storno_poplatek"]);
            $objednavka->aktualni_platba = intval($row["aktualni_platba"]);
            if($row["prijmeni"]!=""){
                $objednavka->klient = $row["prijmeni"].", ".$row["jmeno"];
            }else{
                $objednavka->klient = $row["nazev_objednavajici_organizace"];
            }
            
            if($row["termin_od"]!="" and $row["termin_od"]!="0000-00-00"){
                $row["od"] = $row["termin_od"];
            }
            if($row["termin_do"]!="" and $row["termin_do"]!="0000-00-00"){
                $row["do"] = $row["termin_do"];
            }
            $zajezd = new tsZajezd($row["id_zajezd"], $row["id_serial"], $row["nazev"], $row["nazev_objektu"], $row["od"], $row["do"]);
            $zajezd->idSablonyZobrazeni = $row["id_sablony_zobrazeni"];
            $objednavka->zajezd = $zajezd;
            $objednavky[] = $objednavka;
            
            $sum_pocet_osob += $row["pocet_osob"];
            $sum_aktualni_platba += $row["aktualni_platba"];
            $sum_celkova_castka += $row["celkova_cena"];
            
        }
        $objednavky["agregovane"] = array("pocet_osob"=>$sum_pocet_osob, "aktualni_platba"=>$sum_aktualni_platba, "celkova_castka"=>$sum_celkova_castka);
        return $objednavky;
    }    
    
    /**
     * @param $id_objednavka
     * @return tsObjednavka
     */
    static function dataObjednavka($id_objednavka)
    {
        $dataObjednavka = self::$database->query(self::create_query("select_objednavka", $id_objednavka));
        while ($row = mysqli_fetch_array($dataObjednavka)) {
            if(!($row["termin_od"]=="0000-00-00" or $row["termin_do"]=="0000-00-00" or $row["termin_od"]=="" or $row["termin_do"]=="")){
                //vypocteme pocet noci z aktualnich terminu objednavky
                $row["pocet_noci"] = UtilsTS::calculate_pocet_noci($row["termin_od"], $row["termin_do"]);
            }else{
                $row["pocet_noci"] = UtilsTS::calculate_pocet_noci($row["od"], $row["do"]);
            }
            
            $objednavka = new tsObjednavka($row["id_objednavka"], $row["pocet_osob"], $row["celkova_cena"], $row["zbyva_zaplatit"], $row["poznamky"], $row["pocet_noci"], $row["datum_rezervace"], $row["stav"], $row["storno_datum"], $row["storno_poplatek"]);
            $objednavka->k_uhrade_celkem = $row["k_uhrade_celkem"];
            $objednavka->k_uhrade_zaloha = $row["k_uhrade_zaloha"];
            $objednavka->k_uhrade_doplatek = $row["k_uhrade_doplatek"];
            $objednavka->k_uhrade_celkem_datspl = $row["k_uhrade_celkem_datspl"];
            $objednavka->k_uhrade_zaloha_datspl = $row["k_uhrade_zaloha_datspl"];
            $objednavka->k_uhrade_doplatek_datspl = $row["k_uhrade_doplatek_datspl"];
            return $objednavka;
        }
    }

    /**
     * @param $id_objednavka
     * @return tsObjednavka
     */
    static function dataStaticDescription($id_objednavka)
    {
        $dataObjednavka = self::$database->query(self::create_query("select_objednavka_detailed", $id_objednavka));
        while ($row = mysqli_fetch_array($dataObjednavka)) {
          //  print_r($row);
            $staticInfo = array();
            if ($row["ubytovani"] != "") {
                $staticInfo[] = new tsStaticDescription("Ubytování: " . $row["ubytovani"]);
            }else if($row["nazev_objektu"]!="" and $row["id_sablony_zobrazeni"] == tsZajezd::SABLONA_ZOBRAZENI_12){
                $staticInfo[] = new tsStaticDescription("Ubytování: " . $row["nazev_objektu"]);
            }
            
            if ($row["doprava"] != "") {
                $staticInfo[] = new tsStaticDescription("Doprava: " . $row["doprava"]);
            }
            if ($row["stravovani"] != "") {
                $staticInfo[] = new tsStaticDescription("Strava: " . $row["stravovani"]);
            }
            if ($row["pojisteni"] != "") {
                $staticInfo[] = new tsStaticDescription("Pojištìní: " . $row["pojisteni"]);
            }


            return $staticInfo;
        }
    }


    /**
     * @param $id_objednavka
     * @return null|tsProdejce
     */
    static function dataProdejce($id_objednavka)
    {
        $dataProdejce = self::$database->query(self::create_query("select_prodejce", $id_objednavka));
        while ($row = mysqli_fetch_array($dataProdejce)) {
            return new tsProdejce($row["nazev_agentury"], $row["kontaktni_osoba"], $row["ico"], $row["ulice"], $row["mesto"], $row["psc"], "", $row["email"], $row["telefon"]);
        }
        return null;
    }

    /**
     * pro vypocet celkove ceny za danou sluzbu pouzij metodu computePrice(jednotkova_cena, pocet, pocet_noci, use_pocet_noci)
     * @param $id_objednavka
     * @param $language
     * @return tsSluzba[]
     */
    static function dataSluzby($id_objednavka, $language = VoucheryModelConfig::LANG_CS)
    {
        $sluzby = array();
        $dataSluzby = self::$database->query(self::create_query("select_ceny", $id_objednavka));

        while ($row = mysqli_fetch_array($dataSluzby)) {
            //vyber jazyka
            if ($language == VoucheryModelConfig::LANG_EN) {
                $row["nazev_ceny"] = $row["nazev_ceny_en"] == "" ? $row["nazev_ceny"] : $row["nazev_ceny_en"];
                $row["ok_nazev"] = $row["ok_cizi_nazev"] == "" ? $row["ok_nazev"] : $row["ok_cizi_nazev"];
            }
            if ($row["cena_mena"] == "") {
                //nemame puvodni udaje, zadame tam aktualni z tabulky cena_zajezd
                $castka = $row["castka"];
                $mena = $row["mena"];
                $use_pocet_noci = intval($row["use_pocet_noci"]);

            } else {
                //mam puvodni udaje, dam moznost je editovat
                $castka = $row["cena_castka"];
                $mena = $row["cena_mena"];
                $use_pocet_noci = intval($row["cena_use_pocet_noci"]);

            }
            $sluzba = new tsSluzba($row["id_cena"], $row["nazev_ceny"], $castka, $mena, $row["pocet"], $use_pocet_noci);
            if ($row["ok_id"] != "")
                $sluzba->objektoveKategorie[] = new tsObjektovaKategorie($row["ok_id"], $row["ok_nazev"], $row["ok_kratky_nazev"]);

            //prohledej sluzby, jestli uz mam sluzbu s id = id_cena ulozenou
            $found = false;
            for ($i = 0; $i < count((array)$sluzby); $i++) {
                if ($sluzby[$i]->id_cena == $sluzba->id_cena) {
                    $found = true;
                    break;
                }
            }

            //pokud ji najdes pridej k ni objektovou kategorii jinak pridej novou sluzbu
            if ($found) {
                $sluzby[$i]->objektoveKategorie[] = new tsObjektovaKategorie($row["ok_id"], $row["ok_nazev"], $row["ok_kratky_nazev"]);
            } else {
                $sluzby[] = $sluzba;
            }
        }

        //pridavaji se i sluzby dodane bez vazby na zajezd
        $dataSluzby = self::$database->query(self::create_query("select_ceny2", $id_objednavka));
        while ($row = mysqli_fetch_array($dataSluzby)) {
            $sluzby[] = new tsSluzba($row["id_cena"], $row["nazev_ceny"], $row["castka"], $row["mena"], $row["pocet"], $row["use_pocet_noci"]);
        }

        return $sluzby;
    }

    /**
     * @param $id_objednavka
     * @return tsOsoba[]
     */
    static function dataOsoby($id_objednavka)
    {
        $osoby = array();
        $dataOsoby = self::$database->query(self::create_query("select_osoby", $id_objednavka));
        while ($row = mysqli_fetch_array($dataOsoby)) {
            //sjednotim datum narozeni a rc
            if ($row["rodne_cislo"] == "") {
                $row["rodne_cislo"] = $row["datum_narozeni"];
            }
            //sjednotim cislo pasu a cislo op
            if ($row["cislo_op"] == "") {
                $row["cislo_op"] = $row["cislo_pasu"];
            }
            $osoby[] = new tsOsoba($row["id_klient"], $row["jmeno"], $row["prijmeni"], $row["rodne_cislo"], $row["cislo_op"], $row["telefon"], $row["email"], $row["ulice"], $row["mesto"], $row["psc"],  $row["storno"]);
        }

        return $osoby;
    }

    /**
     * @param $id_objednavka
     * @return tsPlatba[]
     */
    static function dataPlatby($id_objednavka)
    {
        $platby = array();
        $dataSluzby = self::$database->query(self::create_query("select_platby", $id_objednavka));
        while ($row = mysqli_fetch_array($dataSluzby)) {
            $platba = new tsPlatba($row["cislo_dokladu"], $row["zpusob_uhrady"], $row["castka"], $row["vystaveno"], $row["splatit_do"], $row["splaceno"]);
            $platba->typ_dokladu = $row["typ_dokladu"];
            $platby[] = $platba;
        }

        return $platby;
    }

    /**
     * @param $id_objednavka
     * @return null|tsProvize
     */
    static function dataProvize($id_objednavka)
    {
        $dataProvize = self::$database->query(self::create_query("select_provize", $id_objednavka));
        while ($row = mysqli_fetch_array($dataProvize)) {
            return new tsProvize($row["poznamka_provize"], $row["suma_provize"], $row["provize_vc_dph"]);
        }
        return null;
    }

    /**
     * @param $id_objednavka
     * @return null|tsSleva
     */
    static function dataSleva($id_objednavka)
    {
        $slevy = array();
        $dataSleva = self::$database->query(self::create_query("select_sleva", $id_objednavka));
        while ($row = mysqli_fetch_array($dataSleva)) {
            $slevy[] = new tsSleva($row["nazev_slevy"], $row["castka_slevy"], $row["velikost_slevy"], $row["mena"]);
        }
        return $slevy;
    }

    /**
     * @param $id_objednavka
     * @return tsOsoba
     */
    static function dataVystavil($id_objednavka)
    {
        $zamestnanec = User_zamestnanec::get_instance();
        return new tsOsoba(null, $zamestnanec->get_jmeno(), $zamestnanec->get_prijmeni(), "", "", $zamestnanec->get_email(), $zamestnanec->get_telefon());
    }

    /**
     * @return array
     */
    static function dataCentralniData()
    {
        $dataCentralni = self::$database->query(self::create_query("select_centralni_data", null));
        $centralni_data = array();
        while ($row = mysqli_fetch_array($dataCentralni)) {
            $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
            $centralni_data[$row["nazev"]] = $row["text"];
        }
        return $centralni_data;
    }

    /**
     * @param $id_objednavka
     * @return null|tsSmluvniPodminky
     */
    static function dataZaloha($id_objednavka)
    {
        $array_sml_pod = array();
        $dataSmluvniPodminky = self::$database->query(self::create_query("select_zaloha", $id_objednavka));
        while ($row = mysqli_fetch_array($dataSmluvniPodminky)) {
            $a = new tsSmluvniPodminky($row["prodleva"], $row["typ"], $row["castka"], $row["procento"]);
            $array_sml_pod[] = $a;
        }
      //  print_r($array_sml_pod);
        return $array_sml_pod;
    }

    /**
     * @param $id_objednavka
     * @return null|tsSmluvniPodminky
     */
    static function dataDoplatek($id_objednavka)
    {
        $dataSmluvniPodminky = self::$database->query(self::create_query("select_doplatek", $id_objednavka));
        while ($row = mysqli_fetch_array($dataSmluvniPodminky)) {
            return new tsSmluvniPodminky($row["prodleva"], $row["typ"], $row["castka"], $row["procento"]);
        }
        return null;
    }

    /**
     * @param $id_objednavka
     * @return tsObjekt[]
     */
    static function dataObjekty($id_objednavka)
    {
        $dataObjekt = self::$database->query(self::create_query("select_objekty", $id_objednavka));

        $objekty = array();
        while ($row = mysqli_fetch_array($dataObjekt)) {
            $objekty[] = new tsObjekt($row["id_objektu"], $row["mesto"], $row["nazev_objektu"], $row["poznamka"], $row["psc"], $row["ulice"], $row["stat"], $row["email"], $row["telefon"]);
        }
        return $objekty;
    }

    /**
     * @param $id_objednavka
     * @return array|null pole nazvu pdf souboru voucheru srovnane podle casu
     */
    static function dataPdfVouchery($id_objednavka)
    {
        $pdfVouchery = null;
        if ($handle = opendir(VoucheryModelConfig::PDF_FOLDER)) {
            while (false !== ($entry = readdir($handle))) {
                $entryDump = explode("_", $entry);
                if ($entryDump[0] == $id_objednavka) {
                    $pdfVouchery[] = $entry;
                }
            }
            closedir($handle);
        }
        //nejnovejsi nejdrive
        @rsort($pdfVouchery);

        return $pdfVouchery;
    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    private static function create_query($typ_pozadavku, $id_objednavka, $params="", $order_by="")
    {
        if ($typ_pozadavku == "select_objednavky_list") {
            $dotaz = "SELECT objednavka.*, serial.*, zajezd.*, 
                            org.nazev as nazev_objednavajici_organizace, klient.jmeno, klient.prijmeni, objekt.nazev_objektu,
                            (sum(`objednavka_platba`.`castka`)*count(distinct objednavka_platba.id_platba)/count(objednavka_platba.id_platba)) as `aktualni_platba` 
                      FROM  `objednavka` 
                            join  `serial` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
                            join  `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                            left join  `user_klient` as `klient` on (`objednavka`.`id_klient` = `klient`.`id_klient`)
                            left join  `organizace` as `org` on (`objednavka`.`id_organizace` = `org`.`id_organizace`)                            
                            left join  `objednavka_platba` on (`objednavka`.`id_objednavka` = `objednavka_platba`.`id_objednavka` )                                                     
                            left join (`objekt_serial` join
                                `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                     where objednavka.stav!= ".ObjednavkaDAO::STAV_STORNO." and  objednavka.stav!= ".ObjednavkaDAO::STAV_STORNO_CK." and
                        $params 
                     group by objednavka.id_objednavka
                     $order_by

            ";
           // echo $dotaz;
            return $dotaz;
            
        } else if ($typ_pozadavku == "select_objednavka_detailed") {
            $dotaz = "SELECT objednavka.*, objekt.*, serial.id_sablony_zobrazeni FROM `objednavka`
                            join  `serial` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
                            join  `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                            left join  `user_klient` as `klient` on (`objednavka`.`id_klient` = `klient`.`id_klient`)
                            left join  `organizace` as `org` on (`objednavka`.`id_organizace` = `org`.`id_organizace`)                               
                            left join (`objekt_serial` join
                                `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                            
                        WHERE `id_objednavka`=" . $id_objednavka . "
                        LIMIT 1";
           // echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_objednavka") {
            $dotaz = "SELECT * FROM `objednavka`
                            join  `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                            
                        WHERE `id_objednavka`=" . $id_objednavka . "
                        LIMIT 1";
            //echo $dotaz;
            return $dotaz;            
        } else if ($typ_pozadavku == "select_platby") {
            $dotaz = "SELECT * FROM `objednavka_platba`
						WHERE `id_objednavka`=" . $id_objednavka . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_osoby") {
            $dotaz = "SELECT `user_klient`.`id_klient`, `user_klient`.`jmeno`, `user_klient`.`prijmeni`, `user_klient`.`titul`,
                            `user_klient`.`datum_narozeni`, `user_klient`.`rodne_cislo`, `user_klient`.`telefon`,`user_klient`.`email`,
                            `user_klient`.`cislo_pasu`,`user_klient`.`cislo_op`, `user_klient`.`ulice`, `user_klient`.`mesto`,
                            `user_klient`.`psc`,`objednavka_osoby`.`cislo_osoby`, `objednavka_osoby`.`storno`
                        FROM `objednavka_osoby`
                            JOIN `user_klient` ON (`objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`)
                        WHERE `objednavka_osoby`.`id_objednavka`=$id_objednavka
                        ORDER BY `objednavka_osoby`.`cislo_osoby`
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_klient") {
            $dotaz = "SELECT `user_klient`.`id_klient`,`jmeno`,`prijmeni`,`email`,`telefon`,`titul`,`datum_narozeni`,`email`,`ulice`,`mesto`,`psc`
						FROM `user_klient` JOIN `objednavka` ON (`objednavka`.`id_klient` =`user_klient`.`id_klient`)
						WHERE `objednavka`.`id_objednavka`=" . $id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_objednavajici_org") {
            $dotaz = "SELECT org.nazev, org.ico, org.dic, oe.email, ot.telefon, oa.ulice, oa.mesto, oa.psc
						FROM objednavka o
						  RIGHT JOIN organizace org ON (o.id_organizace = org.id_organizace)
						  LEFT JOIN organizace_adresa oa ON (o.id_organizace = oa.id_organizace)
						  LEFT JOIN organizace_email oe ON (o.id_organizace = oe.id_organizace)
						  LEFT JOIN organizace_telefon ot ON (o.id_organizace = ot.id_organizace)
						WHERE o.id_objednavka = $id_objednavka
						LIMIT 1";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_zajezd") {
            $dotaz = "SELECT `serial`.*,`zajezd`.*,
                            `objekt`.`id_objektu` AS `id_ubytovani`, `objekt_ubytovani`.`nazev_ubytovani` AS `nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha`  AS `popisek_ubytovani`
			FROM `serial`
                            JOIN  `zajezd` ON (`serial`.`id_serial` = `zajezd`.`id_serial`)
                            JOIN `objednavka` ON (`objednavka`.`id_serial` =`serial`.`id_serial` AND `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                            LEFT JOIN
                            (`objekt_serial` JOIN
                                `objekt` ON (`objekt`.`typ_objektu`= 1 AND `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) JOIN
                                `objekt_ubytovani` ON (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) ON (`serial`.`id_serial` = `objekt_serial`.`id_serial` AND `serial`.`id_sablony_zobrazeni`=12)
                        
			WHERE `objednavka`.`id_objednavka`=" . $id_objednavka . "
			LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny") {
            $dotaz = "SELECT DISTINCT `cena`.`id_cena`, `cena`.`nazev_ceny`, `cena`.`nazev_ceny_en`,`cena`.`use_pocet_noci`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet`,
                             ok.`id_objekt_kategorie` AS ok_id, ok.`nazev` AS ok_nazev, ok.`kratky_nazev` AS ok_kratky_nazev, ok.`cizi_nazev` AS ok_cizi_nazev,
                             `objednavka_cena`.`cena_castka`,`objednavka_cena`.`cena_mena`,`objednavka_cena`.`use_pocet_noci` as `cena_use_pocet_noci`
						FROM  `cena` 
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
							JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena`)
                            JOIN `objednavka` ON (`objednavka_cena`.`id_objednavka` = `objednavka`.`id_objednavka` and
                                                   `objednavka`.`id_serial` = `cena`.`id_serial` and
                                                   `objednavka`.`id_zajezd` = `cena_zajezd`.`id_zajezd` )
                            LEFT JOIN `cena_objekt_kategorie` cok ON (`cena`.`id_cena` = cok.`id_cena`)
                            LEFT JOIN `objekt_kategorie` ok ON (cok.`id_objekt_kategorie` = ok.`id_objekt_kategorie`)
						WHERE `objednavka`.`id_objednavka`=$id_objednavka
                        ORDER BY `cena`.`poradi_ceny`
						";
//            echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ceny2") {
            $dotaz = "SELECT DISTINCT `objednavka_cena2`.`id_cena`,`objednavka_cena2`.`nazev_ceny`,`objednavka_cena2`.`use_pocet_noci`,`objednavka_cena2`.`pocet`,`objednavka_cena2`.`castka`,`objednavka_cena2`.`mena`
						FROM `objednavka_cena2` 
						WHERE `id_objednavka`=" . $id_objednavka . "
						";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "select_sleva") {
            $dotaz = "SELECT `objednavka_sleva`.*
					FROM `objednavka_sleva`
					WHERE `id_objednavka`=" . $id_objednavka . "
					ORDER BY `objednavka_sleva`.`velikost_slevy` DESC
		";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "select_provize") {
            $dotaz = "SELECT `poznamka_provize`,`suma_provize`,`provize_vc_dph`
                        FROM `objednavka`
						WHERE `id_objednavka`=" . $id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_zaloha") {
            $dotaz = "  SELECT sp.*
                        FROM `objednavka` o
                            JOIN `serial` s ON (o.id_serial = s.id_serial)
                            JOIN `smluvni_podminky_nazev` spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                            JOIN `smluvni_podminky` sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                        WHERE o.`id_objednavka`='$id_objednavka' AND sp.typ='" . tsSmluvniPodminky::$TYP_ZALOHA . "' order by prodleva desc;";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_doplatek") {
            $dotaz = "  SELECT sp.*
                        FROM `objednavka` o
                            JOIN `serial` s ON (o.id_serial = s.id_serial)
                            JOIN `smluvni_podminky_nazev` spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                            JOIN `smluvni_podminky` sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                        WHERE o.`id_objednavka`='$id_objednavka' AND sp.typ='" . tsSmluvniPodminky::$TYP_DOPLATEK . "';";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create`
                        FROM `objednavka`
						WHERE `id_objednavka`=" . $id_objednavka . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_centralni_data") {
            $dotaz = "SELECT * FROM `centralni_data`
						WHERE `nazev` LIKE \"hlavicka:%\"
			";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_prodejce") {
            $dotaz = "SELECT `organizace`.`nazev` AS `nazev_agentury`,`organizace`.`ico`,
                            `organizace_email`.`email`,`organizace_email`.`poznamka` AS `kontaktni_osoba`,
                            `organizace_telefon`.`telefon`,
                            `stat`,`mesto`,`ulice`,`psc`,
                            `nazev_banky`,`kod_banky`,`cislo_uctu`
                        FROM `objednavka`
                         JOIN `organizace` ON (`organizace`.`id_organizace` = `objednavka`.`id_agentury`)
                         LEFT JOIN `organizace_adresa` ON (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` AND `organizace_adresa`.`typ_kontaktu` = 1)
                         LEFT JOIN `organizace_email` ON (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` AND `organizace_email`.`typ_kontaktu` = 0)
                         LEFT JOIN `organizace_telefon` ON (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` AND `organizace_telefon`.`typ_kontaktu` = 0)
                         LEFT JOIN `organizace_www` ON (`organizace`.`id_organizace` = `organizace_www`.`id_organizace` AND `organizace_www`.`typ_kontaktu` = 0)
                         LEFT JOIN `organizace_bankovni_spojeni` ON (`organizace`.`id_organizace` = `organizace_bankovni_spojeni`.`id_organizace` AND `organizace_bankovni_spojeni`.`typ_kontaktu` = 1)
                        WHERE `objednavka`.`id_objednavka`=" . $id_objednavka . "
                        LIMIT 1";

            /* SELECT `prodejce`.`jmeno` as `nazev_agentury`, `prodejce`.`prijmeni` as `kontaktni_osoba`, `prodejce`.`ico` as `ico`,
                    `prodejce`.`email` as `email`, `prodejce`.`telefon` as `telefon`, `prodejce`.`mesto` as `mesto`, `prodejce`.`ulice` as `ulice`,
                    `prodejce`.`psc` as `psc` FROM `objednavka`
                                     join `user_klient` as `prodejce` on (`objednavka`.`id_agentury` = `prodejce`.`id_klient`)
            WHERE `objednavka`.`id_objednavka`=".$id_objednavka."
            LIMIT 1*/
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_objekty") {
            //z objednavky vytahnu id_serialu, pak z objekt_serial id_objektu tykajicich se daneho serialu a pak info o objektu z dalsich tabulek
            $dotaz = "SELECT obj.*, org.*, orgadr.stat, orgadr.mesto, orgadr.ulice, orgadr.psc, orgem.email, orgtel.telefon
                          FROM objekt obj
                            LEFT JOIN organizace org ON (obj.id_organizace = org.id_organizace)
                            LEFT JOIN organizace_adresa orgadr ON (org.id_organizace = orgadr.id_organizace)
                            LEFT JOIN organizace_email orgem ON (org.id_organizace = orgem.id_organizace)
                            LEFT JOIN organizace_telefon orgtel ON (org.id_organizace = orgtel.id_organizace)
                          WHERE id_objektu IN
                        (SELECT id_objektu FROM objekt_serial WHERE id_serial
                          IN (SELECT id_serial FROM objednavka WHERE id_objednavka=$id_objednavka));";
//            echo $dotaz;
            return $dotaz;
        }
    }

}

//todo: tohle bych presunul do indexu danych modulu aby bylo videt, ze se to vola, tohle nikdo nikdy nenajde
ObjednavkaDAO::init();