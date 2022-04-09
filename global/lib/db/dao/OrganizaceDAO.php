<?php

class OrganizaceDAO {

    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    public static function readObjednavkaListByOrganizaceId($idOrganizace)
    {
        self::$db->connect();

        //obj
        $objednavky = null;
        $stmt = self::$db->query(OrganizaceSQLBuilder::readObjednavkaListByOrganizaceIdSQL([$idOrganizace]));
        $stmtNumRows = self::$db->query(SQLBuilder::readFoundRows());
        $numRows = $stmtNumRows->fetch(PDO::FETCH_OBJ)->cnt;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $objednavka = new ObjednavkaEnt($row->id_objednavka, null, null, $row->celkova_cena, null, null, $row->datum_rezervace, $row->stav, $row->termin_od, $row->termin_do, null, null);
            $objednavka->suma_provize = $row->suma_provize;
            $objednavka->provize_vc_dph = $row->provize_vc_dph;
            $objednavka->security_code = $row->security_code;

            //objednatel
            $objednavajici = new UserKlientEnt($row->id_klient, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);
            $objednavka->objednavajici = $objednavajici;

            //serial
            $serial = new SerialEnt($row->id_serial, $row->nazev, null);
            $objednavka->setSerial($serial);


            //faktura prodejce pdf
            $fakturaProdejce = new FakturaEnt(null, null, null, null, null, null, null);
            $fakturaProdejce->pdfFilename = "/$row->id_objednavka.pdf";
            $pdfFilePath = CommonConfig::GET_FAKTURA_PROVIZE_PDF_FOLDER() . $fakturaProdejce->pdfFilename;     //
            $objednavka->setFakturaProdejce(file_exists($pdfFilePath) ? $fakturaProdejce : null);

            $sluzby = self::readSluzbyByObjednavkaAndZajezdId($row->id_objednavka, $row->id_zajezd);
            $slevy = self::readSlevyByObjednavkaId($row->id_objednavka);
            $objednavka->setSluzby($sluzby);
            $objednavka->setSlevy($slevy);

            $objednavky[] = $objednavka;
        }

        self::$db->close();

        return new DBResult(new ObjednavkaHolder($objednavky), $numRows);
    }

    private static function readSluzbyByObjednavkaAndZajezdId($idObjednavka, $idZajezd)
    {
        $sluzby = null;

        $stmt = self::$db->query(OrganizaceSQLBuilder::readSluzbyByObjednavkaAndZajezdIdSQL([$idObjednavka, $idZajezd, $idObjednavka]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $sluzby[] = new SluzbaEnt($row->id_cena, null, $row->typ, $row->castka, $row->mena, $row->pocet, $row->use_pocet_noci, null, null, null, null, null, null, null, null);
        }

        return $sluzby;
    }

    private static function readSlevyByObjednavkaId($idObjednavka)
    {
        $slevy = null;

        $stmt = self::$db->query(OrganizaceSQLBuilder::readSlevyByObjednavkaIdSQL([$idObjednavka]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            if ($row->castka_slevy > 0 and $row->mena != "%") {
                $s = new SlevaEnt(null, $row->nazev_slevy, $row->castka_slevy, "Kè", null);
            } else {
                $s = new SlevaEnt(null, $row->nazev_slevy, $row->velikost_slevy, $row->mena, null);
            }

            $s->setTyp(SlevaEnt::SLEVA_TYP_OBJEDNANA);
            $slevy[] = $s;
        }

        return $slevy;
    }
}