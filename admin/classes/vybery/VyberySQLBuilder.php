<?php


class VyberySQLBuilder
{

    public static function selectZemeSQL()
    {
        $sql = "SELECT id_zeme, nazev_zeme
                FROM zeme;";

//        echo "selectZemeSQL: $sql <br/>";
        return $sql;
    }

    public static function selectTypySerialuSQL()
    {
        $sql = "SELECT *
                FROM typ_serial;";

//        echo "selectTypySerialuSQL: $sql <br/>";
        return $sql;
    }

    /**
     * @param $filter SerialFilter
     * @param $paging
     * @return string
     */
    public static function selectSerialySQL($filter, $paging)
    {
        $page = $filter->getPagingPage();

        $whereSql = self::whereSelectSerialySQL($filter);
        $offset = ($page - 1) * $filter->pagingMaxPerPage;
        $limit = $paging ? "LIMIT $offset, " . $filter->pagingMaxPerPage : "";
        $calc = $paging ? "SQL_CALC_FOUND_ROWS" : "";

        $sql = "SELECT DISTINCT $calc s.id_serial, s.nazev, ts.nazev_typ
                FROM serial s
                    LEFT JOIN typ_serial ts ON (s.id_typ = ts.id_typ)
                    LEFT JOIN zeme_serial zs ON (s.id_serial = zs.id_serial)
                $whereSql
                $limit;";

//        echo "selectSerialySQL: $sql <br/>";
        return $sql;
    }

    //todo presunout do rodice (vytvorit rodice)
    public static function selectFoundRowsSQL()
    {
        $sql = "SELECT FOUND_ROWS() cnt;";

//        echo "selectFoundRowsSQL: $sql <br/>";
        return $sql;
    }

    /**
     * @param $idSerial
     * @return string
     */
    public static function selectZajezdyBySerialIdSQL($idSerial)
    {
        $sql = "SELECT id_zajezd, nazev_zajezdu, od, do
                FROM zajezd
                WHERE id_serial = $idSerial;";

//        echo "selectZajezdyBySerialIdSQL: $sql <br/>";
        return $sql;
    }

    public static function zajezdObjednavkyCountSQL($idZajezd)
    {
        $sql = "SELECT COUNT(id_objednavka) as count
                FROM zajezd z
                    LEFT JOIN objednavka o ON (z.id_zajezd = o.id_zajezd)
                WHERE z.id_zajezd = $idZajezd;";

//        echo "hasSerialObjednavkySQL: $sql <br/>";
        return $sql;
    }

    /**
     * @param $filter SerialFilter
     * @return string
     */
    public static function whereSelectSerialySQL($filter)
    {
        $whereTypAZeme = $whereSerial = $whereZajezd = "";
        $where = array();
        $now = date("Y-m-d");

        $serialTyp = $filter->getSerialTyp();
        $serialZeme = $filter->getSerialZeme();
        $serialNoZajezd = $filter->getSerialNoZajezd();
        $serialNoAktivniZajezd = $filter->getSerialNoAktivniZajezd();
        $serialAktivniZajezd = $filter->getSerialAktivniZajezd();
        $zajezdNoObjednavka = $filter->getZajezdNoObjednavka();
        $zajezdObjednavka = $filter->getZajezdObjednavka();

        //TYP A ZEME
        if ($serialTyp != "")
            $where[] = "ts.id_typ = $serialTyp";
        if ($serialZeme != "")
            $where[] = "zs.id_zeme = $serialZeme";
        //sestav dotaz a pridej k aktualnimu dotazu
        foreach ($where as $w)
            $whereTypAZeme .= "($w) && ";
        $whereTypAZeme = count((array)$where) > 0 ? "(" . substr($whereTypAZeme, 0, strlen($whereTypAZeme) - 4) . ")" : " 1=1 ";
        $where = array();

        //SERIAL
        if ($serialNoZajezd == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial) = 0";
        if ($serialNoAktivniZajezd == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial) >= 1 && (SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial && z.do >= '$now' ) = 0";
        if ($serialAktivniZajezd == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial) >= 1 && (SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial && z.do >= '$now' ) >= 1";
        //sestav dotaz a pridej k aktualnimu
        foreach ($where as $w)
            $whereSerial .= "($w) || ";
        $whereSerial = count((array)$where) > 0 ? "(" . substr($whereSerial, 0, strlen($whereSerial) - 4) . ")" : " 1=1 ";
        $where = array();

        //ZAJEZD
        if ($zajezdNoObjednavka == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(o.id_objednavka) FROM objednavka o WHERE o.id_serial = s.id_serial) = 0";
        if ($zajezdObjednavka == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(o.id_objednavka) FROM objednavka o WHERE o.id_serial = s.id_serial) >= 1";
        //sestav dotaz a pridej k aktualnimu
        foreach ($where as $w)
            $whereZajezd .= "($w) || ";
        $whereZajezd = count((array)$where) > 0 ? "(" . substr($whereZajezd, 0, strlen($whereZajezd) - 4) . ")" : " 1=1 ";

        $whereSql = "WHERE $whereTypAZeme && $whereSerial && $whereZajezd";
        return $whereSql;
    }

    public static function deleteObjednavkyBySerialIdSQL($serialId)
    {
        $sql = "DELETE FROM objednavka WHERE id_serial = $serialId;";

//        echo "deleteObjednavkyBySerialIdSQL: $sql <br/>";
        return $sql;
    }

    public static function deleteZajezdyBySerialIdSQL($serialId)
    {
        $sql = "DELETE FROM zajezd WHERE id_serial = $serialId;";

//        echo "deleteZajezdyBySerialIdSQL: $sql <br/>";
        return $sql;
    }

    public static function deleteSerialByIdSQL($serialId)
    {
        $sql = "DELETE FROM serial WHERE id_serial = $serialId;";

//        echo "deleteSerialByIdSQL: $sql <br/>";
        return $sql;
    }

    public static function deleteObjednavkyByZajezdIdSQL($zajezdId)
    {
        $sql = "DELETE FROM objednavka WHERE id_zajezd = $zajezdId;";

//        echo "deleteObjednavkyByZajezdIdSQL: $sql <br/>";
        return $sql;
    }

    public static function deleteZajezdByIdSQL($zajezdId)
    {
        $sql = "DELETE FROM zajezd WHERE id_zajezd = $zajezdId;";

//        echo "deleteZajezdByIdSQL: $sql <br/>";
        return $sql;
    }

    public static function selectObjektyBySerialIdSQL($idSerial)
    {
        $sql = "SELECT o.*
                FROM objekt_serial os
                    LEFT JOIN objekt o ON (os.id_objektu = o.id_objektu)
                WHERE os.id_serial = $idSerial;";

//        echo "hasSerialObjednavkySQL: $sql <br/>";
        return $sql;
    }
}