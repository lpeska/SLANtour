<?php


class PlatebniDokladDAO
{

    //region STATIC MEMBERS *******************************************************************

    /**
     * @var Database
     */
    private static $database;

    //endregion

    //region PUBLIC METHODS *******************************************************************

    public static function init()
    {
        self::$database = Database::get_instance();
    }

    public static function readCentralniData()
    {
        $dataCentralni = self::$database->query(PlatebniDokladSQLBuilder::readCentralniData());

        $centralni_data = array();
        while ($row = mysqli_fetch_array($dataCentralni)) {
            $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
            $centralni_data[$row["nazev"]] = $row["text"];
        }

        return $centralni_data;
    }

    public static function readZajezdByObjednavkaId($idObjednavka)
    {
        $zajezd = null;

        $data = self::$database->query(PlatebniDokladSQLBuilder::readZajezdByObjednavkaId($idObjednavka));
        $row = mysqli_fetch_object($data);
        $zajezd = new tsZajezd($row->id_zajezd, null, $row->nazev_serial, $row->nazev_ubytovani, null, null);
        $zajezd->idSablonyZobrazeni = $row->id_sablony_zobrazeni;

        return $zajezd;
    }

    public static function readObjednavkaByObjednavkaId($idObjednavka)
    {
        $objednavajici = $objednavajiciOrg = $ucastnici = $prodejce = $objednavka = null;

        //objednavajici
        $data = self::$database->query(PlatebniDokladSQLBuilder::readObjednavajiciByObjednavkaId($idObjednavka));
        while ($row = mysqli_fetch_object($data)) {
            $objednavajici = new tsObjednavajici($row->id_klient, null, $row->jmeno, $row->prijmeni, $row->ulice, $row->mesto, $row->psc, null, $row->email, $row->telefon);
        }

        //objednavajici org
        $data = self::$database->query(PlatebniDokladSQLBuilder::readObjednavajiciOrgByObjednavkaId($idObjednavka));
        while ($row = mysqli_fetch_object($data)) {
            $objednavajiciOrg = new tsOrganizace($row->ico, $row->id_organizace, $row->nazev, null);
            $objednavajiciOrg->email = $row->email;
            $objednavajiciOrg->telefon = $row->telefon;
            $objednavajiciOrg->adresa = new tsAdresa(null, $row->mesto, $row->psc, null, $row->ulice);
        }

        //ucastnici
        $data = self::$database->query(PlatebniDokladSQLBuilder::readUcastniciByObjednavkaId($idObjednavka));
        while ($row = mysqli_fetch_object($data)) {
            $adresa = new tsAdresa(null, $row->mesto, $row->psc, null, $row->ulice);
            $ucastnik = new tsOsoba($row->id_klient, $row->jmeno, $row->prijmeni, null, null, $row->telefon, $row->email);
            $ucastnik->adresa = $adresa;
            $ucastnici[] = $ucastnik;
        }

        //prodejce
        $data = self::$database->query(PlatebniDokladSQLBuilder::readProdejceByObjednavkaId($idObjednavka));
        while ($row = mysqli_fetch_object($data)) {
            $prodejce = new tsProdejce($row->nazev, null, null, $row->ulice, $row->mesto, $row->psc, null, $row->email, $row->telefon);
            $prodejce->id = $row->id_organizace;
        }

        //objednavka
        $data = self::$database->query(PlatebniDokladSQLBuilder::readObjednavkaById($idObjednavka));
        while ($row = mysqli_fetch_object($data)) {
            $objednavka = new tsObjednavka($row->id_objednavka, $row->pocet_osob, null, null, null, $row->pocet_noci, $row->datum_rezervace, null, null, null, null);
            $objednavka->termin_od = $row->termin_od;
            $objednavka->termin_do = $row->termin_do;
            $objednavka->objednavajici = $objednavajici;
            $objednavka->ucastnici = $ucastnici;
            $objednavka->prodejce = $prodejce;
            $objednavka->objednavajiciOrg = $objednavajiciOrg;
        }

        return $objednavka;
    }

    public static function readPlatbyByObjednavkaId($idObjednavka)
    {
        $platby = null;

        $data = self::$database->query(PlatebniDokladSQLBuilder::readPlatbyByObjednavkaId($idObjednavka));
        while ($row = mysqli_fetch_object($data)) {
            $platba = new tsPlatba($row->cislo_dokladu, $row->zpusob_uhrady, $row->castka, null, null, $row->splaceno);
            $platba->id_platba = $row->id_platba;
            $platby[] = $platba;
        }

        return $platby;
    }

    public static function readPlatbyPdfFiles($idObjednavky)
    {
        $pdfUcastnici = null;
        if ($handle = opendir(PlatebniDokladModelConfig::PDF_FOLDER)) {
            while (false !== ($entry = readdir($handle))) {
                $entryDump = explode("_", $entry);
                if ($entryDump[0] == $idObjednavky) {
                    $pdfUcastnici[] = $entry;
                }
            }
            closedir($handle);
        }
        //nejnovejsi nejdrive
        @rsort($pdfUcastnici);

        return $pdfUcastnici;
    }
}