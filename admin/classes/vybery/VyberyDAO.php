<?php

class VyberyDAO extends Generic_data_class
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

    /**
     * @return tsZeme[]|null
     */
    public static function dataZeme()
    {
        $zeme = null;
        $dataZeme = self::$database->query(VyberySQLBuilder::selectZemeSQL());

        while (@$rZeme = mysqli_fetch_object($dataZeme)) {
            $zeme[] = new tsZeme($rZeme->id_zeme, $rZeme->nazev_zeme);
        }

        return $zeme;
    }

    /**
     * @return tsTypSerial[]|null
     */
    public static function dataTypySerialu()
    {
        $typySerialu = null;
        $dataZeme = self::$database->query(VyberySQLBuilder::selectTypySerialuSQL());

        while (@$rZeme = mysqli_fetch_object($dataZeme)) {
            $typySerialu[] = new tsTypSerial($rZeme->id_typ, $rZeme->nazev_typ);
        }

        return $typySerialu;
    }

    /**
     * @param $filter AbstractFilter
     * @return DBResult
     */
    public static function readSerialListFiltered($filter)
    {
        $serialy = null;
        $dataSerialy = self::$database->query(VyberySQLBuilder::selectSerialySQL($filter, true));
        $dataFoundRows = self::$database->query(VyberySQLBuilder::selectFoundRowsSQL());
        $foundRows = mysqli_fetch_object($dataFoundRows)->cnt;

        while (@$rSerialy = mysqli_fetch_object($dataSerialy)) {
            $zajezdy = self::dataZajezdySerialu($rSerialy->id_serial, $rSerialy->nazev);
            $objekty = self::dataObjektySerialu($rSerialy->id_serial);

            $serial = new tsSerial($rSerialy->id_serial, $rSerialy->nazev_typ, $rSerialy->nazev);
            $serial->zajezdy = $zajezdy;
            $serial->hasZajezdy = $zajezdy != null;
            $serial->objekty = $objekty;

            $serialy[] = $serial;
        }

        return new DBResult($serialy, $foundRows);
    }

    /**
     * @param $serialId
     * @return true on success, false otherwise
     */
    public static function deleteSerialById($serialId)
    {
        self::$database->start_transaction();

        $result = self::$database->query(VyberySQLBuilder::deleteObjednavkyBySerialIdSQL($serialId));
        if ($result)
            $result = self::$database->query(VyberySQLBuilder::deleteZajezdyBySerialIdSQL($serialId));
        if ($result)
            $result = self::$database->query(VyberySQLBuilder::deleteSerialByIdSQL($serialId));

        if ($result)
            self::$database->commit();
        else
            self::$database->rollback();

        return $result;
    }

    /**
     * @param $zajezdId
     * @return true on success, false otherwise
     */
    public static function deleteZajezdById($zajezdId)
    {
        self::$database->start_transaction();

        $result = self::$database->query(VyberySQLBuilder::deleteObjednavkyByZajezdIdSQL($zajezdId));
        if ($result)
            $result = self::$database->query(VyberySQLBuilder::deleteZajezdByIdSQL($zajezdId));

        if ($result)
            self::$database->commit();
        else
            self::$database->rollback();

        return $result;
    }

    //region PRIVATE METHODS ******************************************************************
    /**
     * @param $idSerial int
     * @param $nazevSerial string
     * @return tsZajezd[]|null
     */
    private static function dataZajezdySerialu($idSerial, $nazevSerial)
    {
        $zajezdy = null;
        $dataZajezdy = self::$database->query(VyberySQLBuilder::selectZajezdyBySerialIdSQL($idSerial));

        while (@$rZajezdy = mysqli_fetch_object($dataZajezdy)) {
            $hasObjednavky = self::dataHasZajezdObjednavky($rZajezdy->id_zajezd);

            $zajezd = new tsZajezd($rZajezdy->id_zajezd, $idSerial, $nazevSerial, $rZajezdy->nazev_zajezdu, $rZajezdy->od, $rZajezdy->do);
            $zajezd->hasObjednavky = $hasObjednavky;
            $zajezdy[] = $zajezd;
        }

        return $zajezdy;
    }

    private static function dataHasZajezdObjednavky($idZajezd)
    {
        $hasObjednavky = false;
        $dataZajezdObjednavkyCount = self::$database->query(VyberySQLBuilder::zajezdObjednavkyCountSQL($idZajezd));

        while (@$rZajezdObjednavkyCount = mysqli_fetch_object($dataZajezdObjednavkyCount)) {
            $hasObjednavky = $rZajezdObjednavkyCount->count > 0 ? true : false;
        }

        return $hasObjednavky;
    }

    private static function dataObjektySerialu($idSerial)
    {
        $objekty = null;
        $dataObjekty = self::$database->query(VyberySQLBuilder::selectObjektyBySerialIdSQL($idSerial));

        while (@$rObjekty = mysqli_fetch_object($dataObjekty)) {
            $objekt = new tsObjekt(
                $rObjekty->id_objekt,
                $rObjekty->mesto,
                $rObjekty->nazev_objektu,
                $rObjekty->poznamka,
                $rObjekty->psc,
                $rObjekty->ulice,
                $rObjekty->stat,
                $rObjekty->email,
                $rObjekty->telefon);
            $objekty[] = $objekt;
        }

        return $objekty;
    }
    //endregion
}

//todo: tohle bych presunul do indexu (vybery_serialy_zajezdy.php) - nebo spis bych z dao udelal klasicky objekt misto statickych metod, ale na druhou stranu je to asi zbytecne
VyberyDAO::init();