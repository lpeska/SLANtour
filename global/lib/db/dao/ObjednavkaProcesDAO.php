<?php


class ObjednavkaProcesDAO
{

    /**
     * @var DatabaseProvider
     */
    private static $database;

    public static function init()
    {
        self::$database = DatabaseProvider::get_instance();
    }

    /**
     * @param $idZajezd
     * @return null|\ZajezdEnt
     */
    public static function readZajezdById($idZajezd)
    {
        $zajezd = null;

        //open db
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectZajezdByIdSQL(array(':idZajezd' => $idZajezd)));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $zajezd = new ZajezdEnt($row->id_zajezd, $row->od, $row->do, $row->dlouhodobe_zajezdy);

        //serial
        $serial = new SerialEnt($row->id_serial, $row->nazev_serial, $row->id_sablony_zobrazeni);
        $serial->nazev_web = $row->nazev_web;
        //serial -> objekty
        $objekt = new ObjektEnt(null, null, null);
        $objekt->organizace = new OrganizaceEnt(null, null, null, null, null);
        $objekt->organizace->ubytovani[] = new UbytovaniEnt(null, $row->nazev_ubytovani, null, null, null, null, null, null, null, null);
        $serial->objekty[] = $objekt;
        //serial -> mainFoto
        $serial->mainFoto = new FotoEnt(null, $row->foto_url, $row->foto_nazev, null);
        //zajezd -> serial
        $zajezd->serial = $serial;

        //close db
        self::$database->close();

        return $zajezd;
    }

    /**
     * @param $idSerial
     * @return null|\SerialEnt
     */
    public static function readSerialById($idSerial)
    {
        $serial = null;

        self::$database->connect();

        //serial
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectSerialByIdSQL(array(':idSerial' => $idSerial)));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $serial = new SerialEnt($row->id, $row->nazev, $row->id_sablony_zobrazeni);
        $objekt = new ObjektEnt(null, null, null);
        $objekt->organizace = new OrganizaceEnt(null, null, null, null, null);
        $objekt->organizace->ubytovani[] = new UbytovaniEnt(null, $row->nazev_ubytovani, null, null, null, null, null, null, null, null);
        $serial->objekty[] = $objekt;

        self::$database->close();

        return $serial;
    }

    /**
     * @param $idZajezd
     * @return null|\ZajezdEnt
     */
    public static function readZajezdAllById($idZajezd)
    {
        $zajezd = null;

        //open db
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectZajezdAllByIdSQL(array(
                ':idZajezd' => $idZajezd,
                ':zaklFotoAno' => ObjednavkaProcesSQLBuilder::ZAKLADNI_FOTO_ANO,
                ':typObjektu1' => ObjednavkaProcesSQLBuilder::TYP_OBJEKTU_1)
        ));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $zajezd = new ZajezdEnt($row->id_zajezd, $row->od, $row->do, $row->dlouhodobe_zajezdy);

        //serial
        $serial = new SerialEnt($row->id_serial, $row->nazev_serial, $row->id_sablony_zobrazeni, $row->smluvni_podminky, $row->doprava);
        $serial->nazev_web = $row->nazev_web;
        //serial -> objekt

        $objekt = new ObjektEnt(null, null, null);
        $objekt->organizace = new OrganizaceEnt(null, null, null, null, null);
        $objekt->organizace->ubytovani[] = new UbytovaniEnt(null, $row->nazev_ubytovani, null, null, null, null, null, null, null, null);
        $serial->objekty[] = $objekt;
        //serial -> mainFoto
        $serial->mainFoto = new FotoEnt(null, $row->foto_url, $row->foto_nazev, null);
        //zajezd -> serial
        $zajezd->serial = $serial;

        //sluzby (vcetne slev)
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectSluzbyByZajezdId(array(':idZajezd' => $idZajezd)));
        $sluzby = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $sluzby[] = new SluzbaEnt($row->id_cena, $row->nazev_ceny, $row->typ_ceny, $row->castka, $row->mena, $row->pocet, $row->use_pocet_noci, $row->na_dotaz, $row->vyprodano, $row->kapacita_volna, $row->kapacita_bez_omezeni, $row->objekt_kapacita_volna, $row->objekt_kapacita_bez_omezeni, $row->objekt_vyprodano, $row->objekt_na_dotaz);
        }
        $zajezd->setSluzby($sluzby);

        //casove slevy
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectSlevyKlientNoStalyKlientByZajezdSerialId(array(':idZajezd' => $idZajezd, ':idSerial' => $serial->id)));
        $casoveSlevy = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $casoveSlevy[] = new SlevaEnt($row->id_slevy, $row->nazev_slevy, $row->castka, $row->mena, $row->sleva_staly_klient);
        }
        $zajezd->setCasoveSlevy($casoveSlevy);

        //blackdays
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::readBlackdaysByZajezdIdSQL(array(':idZajezd' => $idZajezd)));
        $blackDays = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $blackDays[] = new BlackDaysEnt($row->id_blackdays, $row->od, $row->do);
        }
        $zajezd->setBlackDays($blackDays);

        //close db
        self::$database->close();

        return $zajezd;
    }

    /**
     * @param $serialNazevWeb web nazev serialu
     * @return ZajezdEnt
     */
    public static function readZajezdBySerialNazevWeb($serialNazevWeb)
    {
        $zajezd = null;

        //open db
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectZajezdBySerialNazevWebSQL(array(':nazevWeb' => $serialNazevWeb)));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $zajezd = new ZajezdEnt($row->id_zajezd, null, null, null);
        //zajezd -> serial
        $zajezd->serial = new SerialEnt($row->id_serial, null, null);

        //close db
        self::$database->close();

        return $zajezd;
    }

    /**
     * @return mixed double
     */
    public static function kurzEur()
    {
        //open db
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::centralniDataByName(array(':nazev' => ObjednavkaProcesSQLBuilder::CENTRALNI_DATA_KURZ_EUR_TITLE)));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $kurz = $row->text;

        //close db
        self::$database->close();

        return $kurz;
    }

      /**
     * @return mixed string
     */
    public static function nazevCK()
    {
        //open db
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::centralniDataByName(array(':nazev' => ObjednavkaProcesSQLBuilder::CENTRALNI_DATA_NAZEV_CK_TITLE)));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $nazev = $row->text;

        //close db
        self::$database->close();

        return $nazev;
    }
    
    /**
     * @return array CentralniDataEnt[]
     */
    public static function readZpusobyPlateb()
    {
        //open db
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::centralniDataByName(array(':nazev' => ObjednavkaProcesSQLBuilder::CENTRALNE_DATA_PLATEBNI_METODY)));
        @$result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $platebniMetody = array();
        foreach ($result as $row) {
            //note tohle je hnus
            if ($row->id_data == CentralniDataEnt::ID_PLATBA_HLAVNI or $row->id_data == CentralniDataEnt::ID_UCET_SK or $row->id_data == CentralniDataEnt::ID_PLATBA_PREVODEM_MAIL)
                continue;
            $cd = new CentralniDataEnt($row->id_data, $row->nazev, $row->text);
            $cd->loadPaymentViewData();
            $platebniMetody[] = $cd;
        }

        //close db
        self::$database->close();

        return $platebniMetody;
    }

    /**
     * @param $idSerial
     * @return SlevaEnt[]|null
     */
    public static function readSlevyKlientBySerialId($idSerial) {
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectSlevyKlientBySerialId(array(":idSerial" => $idSerial)));
        @$result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $slevyKlient = null;
        foreach ($result as $row) {
            $slevyKlient[] = new SlevaEnt($row->id_slevy, $row->nazev_slevy, $row->castka, $row->mena, $row->sleva_staly_klient);
        }

        self::$database->close();

        return $slevyKlient;
    }

    /**
     * @param $idZajezd
     * @return SlevaEnt[]|null
     */
    public static function readSlevyKlientByZajezdId($idZajezd) {
        self::$database->connect();

        //zajezd
        $stmt = self::$database->query(ObjednavkaProcesSQLBuilder::selectSlevyKlientByZajezdId(array(":idZajezd" => $idZajezd)));
        @$result = $stmt->fetchAll(PDO::FETCH_OBJ);
        $slevyKlient = null;
        foreach ($result as $row) {
            $slevyKlient[] = new SlevaEnt($row->id_slevy, $row->nazev_slevy, $row->castka, $row->mena, $row->sleva_staly_klient);
        }

        self::$database->close();

        return $slevyKlient;
    }

}