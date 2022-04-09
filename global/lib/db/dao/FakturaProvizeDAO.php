<?php

class FakturaProvizeDAO
{
    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    public static function readCentralniDataByTagLike($tag)
    {
        self::$db->connect();

        $centralniDataList = null;
        $stmt = self::$db->query(FakturaProvizeSQLBuilder::readCentralniDataByTagNameSQL([$tag]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $centralniDataList[] = new CentralniDataEnt($row->id, $row->nazev, $row->text);
        }

        self::$db->close();

        return $centralniDataList;
    }

    public static function readOrganizaceById($idOrganizace)
    {
        self::$db->connect();

        //org
        $organizace = null;
        $stmt = self::$db->query(FakturaProvizeSQLBuilder::readOrganizaceByIdSQL([$idOrganizace, $idOrganizace, $idOrganizace, $idOrganizace, $idOrganizace]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $organizace = new OrganizaceEnt($row->id, $row->nazev, $row->ico, $row->dic, null);

        //adresa
        $adresa = new AdresaEnt(null, null, $row->mesto, $row->psc, $row->ulice, null, null, null, null);
        $organizace->setAdresy([$adresa]);

        //email
        $email = new EmailEnt($row->email);
        if ($row->email != "")
            $organizace->setEmaily([$email]);

        //telefon
        $telefon = new TelefonEnt($row->telefon);
        if ($row->telefon != "")
            $organizace->setTelefony([$telefon]);

        $bs = new BankovniSpojeniEnt($row->nazev_banky, $row->kod_banky, $row->cislo_uctu);
        if ($row->cislo_uctu != "")
            $organizace->setBankovniSpojeni([$bs]);        
        
        self::$db->close();

        return $organizace;
    }

    public static function readObjednavkaById($idObjednavka)
    {
        self::$db->connect();

        //obj
        $objednavka = null;
        $stmt = self::$db->query(FakturaProvizeSQLBuilder::readObjednavkaByIdSQL([$idObjednavka]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $objednavka = new ObjednavkaEnt($row->id_objednavka, null, null, $row->celkova_cena, null, $row->poznamky, $row->datum_rezervace, null, $row->termin_od, $row->termin_do, null, null);
        $objednavka->suma_provize = $row->suma_provize;
        $objednavka->provize_vc_dph = $row->provize_vc_dph;

        //objednatel
        $objednavajici = new UserKlientEnt($row->id_objednavka, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);
        $objednavka->objednavajici = $objednavajici;

        //faktura prodejce pdf
        $fakturaProdejce = new FakturaEnt(null, null, null, null, null, null, null);
        $fakturaProdejce->pdfFilename = "/$idObjednavka.pdf";
        $pdfFilePath = CommonConfig::GET_FAKTURA_PROVIZE_PDF_FOLDER() . $fakturaProdejce->pdfFilename;
        $objednavka->setFakturaProdejce(file_exists($pdfFilePath) ? $fakturaProdejce : null);

        //serial
        $serial = new SerialEnt($row->id_serial, $row->nazev, null);
        $objednavka->setSerial($serial);
        $objednavka->setSerial($serial);

        self::$db->close();

        //ucastnici
        $ucastnici = self::readUcastniciByObjednavkaId($idObjednavka);
        $objednavka->setUcastnici($ucastnici);

        //sluzby
        $sluzby = self::readSluzbyByObjednavkaId($idObjednavka);
        $objednavka->setSluzby($sluzby);

        return $objednavka;
    }

    public static function readObjednavkaListByOrganizaceId($idOrganizace)
    {
        self::$db->connect();

        //obj
        $objednavky = null;
        $stmt = self::$db->query(FakturaProvizeSQLBuilder::readObjednavkaListByOrganizaceIdSQL([$idOrganizace]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $objednavka = new ObjednavkaEnt($row->id_objednavka, null, null, $row->celkova_cena, null, null, $row->datum_rezervace, null, $row->termin_od, $row->termin_do, null, null);
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
            //$fakturaProdejce->pdfFilename = "/$row->id_objednavka.pdf";
            //$pdfFilePath = CommonConfig::GET_FAKTURA_PROVIZE_PDF_FOLDER() . $fakturaProdejce->pdfFilename;
            //$objednavka->setFakturaProdejce(file_exists($pdfFilePath) ? $fakturaProdejce : null);
            
            $stmt_faktura = self::$db->query(FakturaProvizeSQLBuilder::readFakturaProvizeByIdObjednavka([$row->id_objednavka]));
            while (@$row2 = $stmt_faktura->fetch(PDO::FETCH_OBJ)) {
                $objednavka->cislo_faktury_provize = $row2->cislo_faktury;
                $objednavka->faktura_provize_zaplacena = $row2->zaplaceno;
            }
            

            $objednavky[] = $objednavka;
        }

        self::$db->close();

        return new ObjednavkaHolder($objednavky);
    }

    private static function readUcastniciByObjednavkaId($idObjednavka)
    {
        self::$db->connect();

        //obj
        $ucastnici = null;
        $stmt = self::$db->query(FakturaProvizeSQLBuilder::readUcastniciByObjednavkaIdSQL([$idObjednavka]));
        while(@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $ucastnik = new UserKlientEnt($row->id_klient, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);
            $ucastnik->datum_narozeni = $row->datum_narozeni;
            $ucastnik->rodne_cislo = $row->rodne_cislo;
            $ucastnik->cislo_pasu = $row->cislo_pasu;
            $ucastnik->cislo_op = $row->cislo_op;
            $ucastnik->adresa = new AdresaEnt(null, null, $row->mesto, $row->psc, $row->ulice, null, null, null, null);
            $ucastnici[] = $ucastnik;
        }

        self::$db->close();

        return $ucastnici;
    }

    private static function readSluzbyByObjednavkaId($idObjednavka)
    {
        self::$db->connect();

        //sluzby (vcetne slev)
        $sluzby = null;
        $stmt = self::$db->query(FakturaProvizeSQLBuilder::readSluzbyByObjednavkaIdSQL([$idObjednavka, $idObjednavka, $idObjednavka]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $sluzby[] = new SluzbaEnt($row->id_cena, $row->nazev_ceny, $row->typ, $row->castka, $row->mena, $row->pocet, $row->use_pocet_noci, $row->na_dotaz, $row->vyprodano, $row->kapacita_volna, $row->kapacita_bez_omezeni, $row->objekt_kapacita_volna, $row->objekt_kapacita_bez_omezeni, $row->objekt_vyprodano, $row->objekt_na_dotaz);
        }

        self::$db->close();

        return $sluzby;
    }

    public static function createFakturaProvize($idObjednavka, $idAgentura, $cisloFaktury, $celkovaCastka, $poznamka)
    {
        $today = date("Y-m-d");

        self::$db->connect();

        //obj
        $ucastnici = null;
        $query = FakturaProvizeSQLBuilder::createFakturaProvize([$idObjednavka, $idAgentura, $cisloFaktury, $today, $celkovaCastka, $poznamka]);
        self::$db->prepare($query->sql);
        $result = self::$db->execute($query->params);

        self::$db->close();

        return $result;
    }
}