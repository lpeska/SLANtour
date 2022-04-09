<?php


class FinancniPohybyDAO
{

    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    public static function readSerialListFiltered($filter)
    {
        $serialy = null;

        self::$db->connect();

        $stmtSerialy = self::$db->query(FinancniPohybySQLBuilder::readSerialListFilteredSQL($filter, true, array()));
        $stmtFR = self::$db->query(SQLBuilder::readFoundRows());
        $foundRows = $stmtFR->fetch(PDO::FETCH_OBJ)->cnt;

        while (@$row = $stmtSerialy->fetch(PDO::FETCH_OBJ)) {
            $objekty = self::readObjektListBySerialId($row->id_serial);
            $zajezdy = self::readZajezdListBySerialId($filter, $row->id_serial);

            $serial = new SerialEnt($row->id_serial, $row->nazev, null, null);
            $serial->typ = new SerialTypEnt(null, $row->nazev_typ);
            $serial->objekty = $objekty;
            $serial->setZajezdy($zajezdy);;
            $serial->hasZajezd = self::readSerialHasZajezd($row->id_serial);

            $serialy[] = $serial;
        }

        self::$db->close();

        return new DBResult($serialy, $foundRows);
    }

    public static function readSelectedSerialListWithZajezdy($filter)
    {
        $serialy = null;

        self::$db->connect();

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readSelectedSerialListSQL($filter));

        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $objekty = self::readObjektListBySerialId($row->id_serial);
            $zajezdy = self::readSelectedZajezdListWithObjednavky($filter, $row->id_serial);

            $serial = new SerialEnt($row->id_serial, $row->nazev, $row->id_sablony_zobrazeni, null);
            $serial->setZajezdy($zajezdy);
            $serial->objekty = $objekty;

            $serialy[] = $serial;
        }
        self::$db->close();

        return $serialy;
    }

    //region PRIVATE METHODS *************************************************************
    //todo open/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readObjektListBySerialId($idSerial)
    {
        $objekty = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::selectObjektyBySerialIdSQL(array($idSerial)));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            //note nektere objekty na ktere odkazuje tabulka objekt_serial neexistuji (chybi cizi klic)!
            if (!is_null($row->id_objektu))
                $objekty[] = new ObjektEnt($row->id_objekt, $row->nazev_objektu, $row->poznamka);
        }

        return $objekty;
    }

    //todo open/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readZajezdListBySerialId($filter, $idSerial)
    {
        $zajezdList = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readZajezdListBySerialIdSQL($filter, $idSerial));

        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $zajezd = new ZajezdEnt($row->id_zajezd, $row->od, $row->do, null);
            $zajezd->hasObjednavka = self::readZajezdHasObjednavka($row->id_zajezd);
            $zajezdList[] = $zajezd;
        }

        return $zajezdList;
    }

    //todo open/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    /**
     * @param $filter SerialFilter
     * @param $idSerial
     * @return array|null
     */
    private static function readSelectedZajezdListWithObjednavky($filter, $idSerial)
    {
        $zajezdList = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readSelectedZajezdListBySerialIdSQL($filter, $idSerial));

        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $stmtObj = self::$db->query(FinancniPohybySQLBuilder::readObjednavkaListInTerminByZajezdIdSQL(
                    array(
                        $row->id_zajezd, 
                        $filter->getObjednavkaOd(), 
                        $filter->getObjednavkaDo(), 
                        $filter->getRealizaceOd(), 
                        $filter->getRealizaceDo()
                    )
                ));
            $objednavky = null;
            while (@$rowObj = $stmtObj->fetch(PDO::FETCH_OBJ)) {
                $objednavajici = self::readObjednavajiciByObjednavkaId($rowObj->id_objednavka);
                $sluzby = self::readSluzbyByObjednavkaAndZajezdId($rowObj->id_objednavka, $row->id_zajezd);
                $platby = self::readPlatbyByObjednavkaId($rowObj->id_objednavka);
                $faktury = self::readFakturyByObjednavkaId($rowObj->id_objednavka);

                $terminOd = $rowObj->termin_od == "0000-00-00" ? $row->od : $rowObj->termin_od; //pokud neni termin ulozen u obj, pouzije termin zajezdu
                $terminDo = $rowObj->termin_do == "0000-00-00" ? $row->do : $rowObj->termin_do;
                $objednavka = new ObjednavkaEnt($rowObj->id_objednavka, $rowObj->pocet_osob, $rowObj->pocet_noci, null, null, null, $rowObj->datum_rezervace, $rowObj->stav, $terminOd, $terminDo, null, $rowObj->storno_poplatek);
                $objednavka->suma_provize = $rowObj->suma_provize;
                $objednavka->objednavajici = $objednavajici;
                $objednavka->setProdejce(is_null($rowObj->id_agentury) ? null : new OrganizaceEnt($rowObj->id_agentury, null, null, null, null, null));
                $objednavka->setSluzby($sluzby);
                $objednavka->setPlatby($platby);
                $objednavka->setFaktury($faktury);
                $objednavky[] = $objednavka;
            }
            $zajezd = new ZajezdEnt($row->id_zajezd, $row->od, $row->do, null);
            $zajezd->setObjednavky($objednavky);
            $zajezdList[] = $zajezd;
        }

        return $zajezdList;
    }

    //todo open/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readObjednavajiciByObjednavkaId($idObjednavka)
    {
        $stmt = self::$db->query(FinancniPohybySQLBuilder::readObjednavajiciByObjednavkaIdSQL(array($idObjednavka)));

        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $objednavajici = new UserKlientEnt($row->id_klient, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);

        return $objednavajici;
    }

    //todo open/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readSluzbyByObjednavkaAndZajezdId($idObjednavka, $idZajezd)
    {
        $sluzby = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readSluzbyByObjednavkaAndZajezdIdSQL(array($idObjednavka, $idZajezd, $idObjednavka, $idObjednavka)));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $sluzby[] = new SluzbaEnt($row->id_cena, null, $row->typ, $row->castka, $row->mena, $row->pocet, $row->use_pocet_noci, null, null, null, null, null, null, null, null);
        }

        return $sluzby;
    }

    //todo oepn/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readPlatbyByObjednavkaId($idObjednavka)
    {
        $platby = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readPlatbyByObjednavkaIdSQL(array($idObjednavka)));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $platby[] = new PlatbaEnt($row->id_platba, $row->cislo_dokladu, $row->castka, $row->vystaveno, $row->splatit_do, $row->splaceno, $row->typ_dokladu, $row->zpusob_uhrady);
        }

        return $platby;
    }

    //todo oepn/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readFakturyByObjednavkaId($idObjednavka)
    {
        $faktury = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readFakturyByObjednavkaIdSQL(array($idObjednavka)));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $faktury[] = new FakturaEnt($row->id_faktury, $row->cislo_faktury, $row->mena, $row->celkova_castka, $row->datum_vystaveni, $row->datum_splatnosti, $row->zaplaceno);
        }

        return $faktury;
    }

    //todo oepn/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readSerialHasZajezd($idSerial)
    {
        $serialHasZajezd = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readSerialHasZajezdSQL(array($idSerial)));
        $serialHasZajezd = $stmt->fetch(PDO::FETCH_OBJ)->cnt > 0;

        return $serialHasZajezd;
    }

    //todo oepn/close db jsem pouzil vyse - metoda se tedy neda moc dobre znovu pouzit
    private static function readZajezdHasObjednavka($idZajezd)
    {
        $zajezdHasObjednavka = null;

        $stmt = self::$db->query(FinancniPohybySQLBuilder::readZajezdHasObjednavkaSQL(array($idZajezd)));
        $zajezdHasObjednavka = $stmt->fetch(PDO::FETCH_OBJ)->cnt > 0;

        return $zajezdHasObjednavka;
    }

    //endregion
}