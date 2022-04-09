<?php

class SeznamyUcastnikuDAO extends Generic_data_class
{

    //region STATIC MEMBERS *******************************************************************

    /**
     * @var Database
     */
    private static $db;

    //endregion

    //region PUBLIC METHODS *******************************************************************

    public static function init()
    {
        self::$db = Database::get_instance();
    }

    /**
     * @param $filter AbstractFilter
     * @return DBResult
     */
    public static function readSerialListFiltered($filter)
    {
        $serialy = null;
        $dataSerialy = self::$db->query(SeznamyUcastnikuSQLBuilder::selectSerialySQL($filter, true));
        $dataFoundRows = self::$db->query(SeznamyUcastnikuSQLBuilder::selectFoundRowsSQL());
        $foundRows = mysqli_fetch_object($dataFoundRows)->cnt;

        while (@$rSerialy = mysqli_fetch_object($dataSerialy)) {
            $objekty = self::dataObjektySerialu($rSerialy->id_serial);

            $serial = new tsSerial($rSerialy->id_serial, $rSerialy->nazev_typ, $rSerialy->nazev);
            $serial->objekty = $objekty;

            $serialy[] = $serial;
        }

        return new DBResult($serialy, $foundRows);
    }

    /**
     * @param $filter SerialFilter
     * @return tsSerial[]|null
     */
    public static function readSerialListWithZajezdy($filter)
    {
        $serialy = null;
        $dataSerialy = self::$db->query(SeznamyUcastnikuSQLBuilder::selectSerialySQL($filter, false, SerialFilter::MODE_SELECTED_SERIAL));

        while (@$rSerialy = mysqli_fetch_object($dataSerialy)) {
            $zajezdy = self::dataZajezdySerialu($filter, $rSerialy->id_serial, $rSerialy->nazev);

            $serial = new tsSerial($rSerialy->id_serial, $rSerialy->nazev_typ, $rSerialy->nazev);
            $serial->zajezdy = $zajezdy;

            if ($zajezdy != null)
                $serialy[] = $serial;
        }

        return $serialy;
    }

    /**
     * @param $filter SerialFilter
     * @return tsSerial[]|null
     */
    public static function readSerialListWithZajezdyAndUcastnici($filter)
    {
        $serialy = null;
        $dataSerialy = self::$db->query(SeznamyUcastnikuSQLBuilder::selectSerialyByZajezdIdSQL($filter));

        while (@$rSerialy = mysqli_fetch_object($dataSerialy)) {
            $zajezdy = self::dataZajezdyObjednavkyUcastniciSerialu($filter, $rSerialy->id_serial, $rSerialy->nazev);

            $serial = new tsSerial($rSerialy->id_serial, $rSerialy->nazev_typ, $rSerialy->nazev);
            $serial->zajezdy = $zajezdy;

            if ($zajezdy != null)
                $serialy[] = $serial;
        }

        return $serialy;
    }

    /**
     * @return tsZeme[]|null
     */
    public static function dataZeme()
    {
        $zeme = null;
        $dataZeme = self::$db->query(SeznamyUcastnikuSQLBuilder::selectZemeSQL());

        while (@$rZeme = mysqli_fetch_object($dataZeme)) {
            $zeme[] = new tsZeme($rZeme->id_zeme, $rZeme->nazev_zeme);
        }

        return $zeme;
    }

    /**
     * @return array
     */
    public static function dataCentralniData()
    {
        $dataCentralni = self::$db->query(SeznamyUcastnikuSQLBuilder::selectCentralniDataSQL());
        $centralni_data = array();
        while ($row = mysqli_fetch_array($dataCentralni)) {
            $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
            $centralni_data[$row["nazev"]] = $row["text"];
        }
        return $centralni_data;
    }

    /**
     * @param $filterValues SerialFilter
     * @return array|null
     */
    public static function readPdfList($filterValues)
    {
        $pdfPrefix = SeznamyUcastnikuUtils::generatePdfPrefix($filterValues);
        $pdfUcastnici = null;
        if ($handle = opendir(SeznamyUcastnikuModelConfig::PDF_FOLDER)) {
            while (false !== ($entry = readdir($handle))) {
                $entryDump = explode("_", $entry);
                if ($entryDump[0] == $pdfPrefix) {
                    $pdfUcastnici[] = $entry;
                }
            }
            closedir($handle);
        }
        //nejnovejsi nejdrive
        @rsort($pdfUcastnici);

        return $pdfUcastnici;
    }

    //region PRIVATE METHODS ******************************************************************

    /**
     * @param $idSerial int
     * @return tsObjekt[]|null
     */
    private static function dataObjektySerialu($idSerial)
    {
        $objekty = null;
        $dataObjekty = self::$db->query(SeznamyUcastnikuSQLBuilder::selectObjektyBySerialIdSQL($idSerial));

        while ($rObjekty = mysqli_fetch_object($dataObjekty)) {
            //note nektere objekty na ktere odkazuje tabulka objekt_serial neexistuji (chybi cizi klic)!
            if (!is_null($rObjekty->id_objektu))
                $objekty[] = new tsObjekt($rObjekty->id_objektu, "", $rObjekty->nazev_objektu, "", "", "", "", "", "");
        }

        return $objekty;
    }

    /**
     * @param $filter SerialFilter
     * @param $idSerial int
     * @param $nazevSerial string
     * @return tsZajezd[]|null
     */
    private static function dataZajezdySerialu($filter, $idSerial, $nazevSerial)
    {
        $zajezdy = null;
        $dataZajezdy = self::$db->query(SeznamyUcastnikuSQLBuilder::selectZajezdyBySerialIdSQL($filter, $idSerial));

        while (@$rZajezdy = mysqli_fetch_object($dataZajezdy)) {
            $zajezdy[] = new tsZajezd($rZajezdy->id_zajezd, $idSerial, $nazevSerial, $rZajezdy->nazev_zajezdu, $rZajezdy->od, $rZajezdy->do);
        }

        return $zajezdy;
    }

    /**
     * @param $filter SerialFilter
     * @param $idSerial int
     * @param $nazevSerial string
     * @return tsZajezd[]|null
     */
    private static function dataZajezdyObjednavkyUcastniciSerialu($filter, $idSerial, $nazevSerial)
    {
        $zajezdy = null;
        $dataZajezdy = self::$db->query(SeznamyUcastnikuSQLBuilder::selectZajezdyBySerialAndZajezdIdSQL($filter, $idSerial));

        while (@$rZajezdy = mysqli_fetch_object($dataZajezdy)) {
            $objednavky = self::dataObjednavkyUcastniciZajezdu($filter, $rZajezdy->id_zajezd);

            $zajezd = new tsZajezd($rZajezdy->id_zajezd, $idSerial, $nazevSerial, $rZajezdy->nazev_zajezdu, $rZajezdy->od, $rZajezdy->do);
            $zajezd->objednavky = $objednavky;
            $zajezdy[] = $zajezd;
        }

        return $zajezdy;
    }

    /**
     * @param $filter SerialFilter
     * @param $idZajezd int
     * @return tsObjednavka[]|null
     */
    private static function dataObjednavkyUcastniciZajezdu($filter, $idZajezd)
    {
        $objednavky = null;
        $dataObjednavky = self::$db->query(SeznamyUcastnikuSQLBuilder::selectObjednavkyByZajezdIdSQL($idZajezd));

        while (@$rObjednavky = mysqli_fetch_object($dataObjednavky)) {
            $ucastnici = self::dataUcastniciObjednavky($filter, $rObjednavky->id_objednavka);
            $sluzby = self::dataSluzbyObjednavky($filter, $rObjednavky->id_objednavka);
            $nastupniMista = self::dataObjednavkaNastupniMista($rObjednavky->id_objednavka);

            $objednavka = new tsObjednavka($rObjednavky->id_objednavka, null, null, null, null, null, null, null, null, null);
            $objednavka->objednavajici = new tsObjednavajici(null, null, $rObjednavky->jmeno, $rObjednavky->prijmeni, null, null, null, null, null, null);
            $objednavka->prodejce = new tsProdejce($rObjednavky->nazev, null, null, null, null, null, null, null, null);
            $objednavka->ucastnici = $ucastnici;
            $objednavka->sluzby = $sluzby;
            $objednavka->nastupni_mista = $nastupniMista;
            $objednavky[] = $objednavka;
        }

        return $objednavky;
    }

    private static function dataObjednavkaNastupniMista($idObjednavka)
    {
        $nastupniMista = null;
        $dataNastupniMista = self::$db->query(SeznamyUcastnikuSQLBuilder::selectNastupniMistaByObjednavkaId($idObjednavka));

        while (@$rNastupniMista = mysqli_fetch_object($dataNastupniMista)) {
            $nastupniMista[] = $rNastupniMista->nazev_ceny;
        }

        return $nastupniMista;
    }

    /**
     * @param $filter SerialFilter
     * @param $idObjednavka int
     * @return tsOsoba[]|null
     */
    private static function dataUcastniciObjednavky($filter, $idObjednavka)
    {
        $ucastnici = null;
        $dataUcastnici = self::$db->query(SeznamyUcastnikuSQLBuilder::selectUcastniciByObjednavkaIdSQL($filter, $idObjednavka));

        while (@$rUcastnici = mysqli_fetch_object($dataUcastnici)) {
            $ucastnik = new tsOsoba($rUcastnici->id_klient, $rUcastnici->jmeno, $rUcastnici->prijmeni, $rUcastnici->rodne_cislo, null, $rUcastnici->telefon, $rUcastnici->email);
            $ucastnik->titul = $rUcastnici->titul;
            $ucastnik->datum_narozeni = $rUcastnici->datum_narozeni;
            $ucastnik->cislo_pasu = $rUcastnici->cislo_pasu;
            $ucastnik->adresa = new tsAdresa(null, $rUcastnici->mesto, $rUcastnici->psc, null, $rUcastnici->ulice);
            $ucastnici[] = $ucastnik;
        }

        return $ucastnici;
    }

    /**
     * @param $filter SerialFilter
     * @param $idObjednavka int
     * @return tsSluzba[]|null
     */
    private static function dataSluzbyObjednavky($filter, $idObjednavka)
    {
        $sluzby = null;
        $dataSluzby = self::$db->query(SeznamyUcastnikuSQLBuilder::selectSluzbyByObjednavkaIdSQL($filter, $idObjednavka));

        while (@$rSluzby = mysqli_fetch_object($dataSluzby)) {
            $sluzba = new tsSluzba($rSluzby->id_cena, $rSluzby->nazev_ceny, $rSluzby->castka, $rSluzby->mena, $rSluzby->pocet, $rSluzby->use_pocet_noci);
            $sluzby[] = $sluzba;
        }

        return $sluzby;
    }

    //endregion
}

//todo tohle pozdeji zmizi - az bude existovat jednotne DAO pro cely slantour
interface ISerialyDAO {
    public static function readSerialListFiltered($filter);
    public static function readSerialListWithZajezdy($filter);
    public static function readSerialListWithZajezdyAndUcastnici($filter);
    public static function readPdfList($filterValues);
}

//todo tohle bych presunul do indexu (seznamy_ucestaniku.php)
SeznamyUcastnikuDAO::init();