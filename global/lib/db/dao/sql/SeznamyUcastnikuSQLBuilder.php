<?php


class SeznamyUcastnikuSQLBuilder extends SQLBuilder
{

    //todo presunout (tohle bude na X mistech, nepodarilo se mi to najit)
    const OBJEDNAVKA_STAV_STORNO = 8;
    const OBJEDNAVKA_STAV_STORNO_CK = 9;

    /**
     * @param $filter SerialFilter
     * @param bool $paging strankovani ano/ne
     * @param int $mode int SerialFilter::FILTER_MODE_SELECTED_SERIAL|SerialFilter::FILTER_MODE_FILTERED_SERIAL
     * @return string
     */
    public static function selectSerialySQL($filter, $paging = true, $mode = SerialFilter::MODE_FILTERED_SERIAL)
    {
        $page = $filter->getPagingPage();

        $whereSql = self::whereSerialySQL($filter, $mode);
        $offset = ($page - 1) * $filter->pagingMaxPerPage;
        $limit = $paging ? "LIMIT $offset, " . $filter->pagingMaxPerPage : "";
        $calc = $paging ? "SQL_CALC_FOUND_ROWS" : "";

        $sql = "SELECT $calc s.id_serial, s.nazev, ts.nazev_typ
                FROM serial s
                    LEFT JOIN typ_serial ts ON (s.id_typ = ts.id_typ)
                    LEFT JOIN zeme_serial zs ON (s.id_serial = zs.id_serial)
                $whereSql
                $limit;";

//        echo "selectSerialySQL: $sql <br/>";
        return $sql;
    }

    /**
     * @param $filter SerialFilter
     * @return string
     */
    public static function selectSerialyByZajezdIdSQL($filter)
    {
        $zajezdyIds = $filter->getZajezdIdsSelected();
        $whereSql = self::whereZajezdySQL($zajezdyIds, "");

        $sql = "SELECT DISTINCT s.id_serial, s.nazev, ts.nazev_typ
                FROM zajezd z
                    LEFT JOIN serial s ON (s.id_serial = z.id_serial)
                    LEFT JOIN typ_serial ts ON (s.id_typ = ts.id_typ)
                $whereSql;";

//        echo "selectSerialyByZajezdIdSQL: $sql <br/>";
        return $sql;
    }

    public static function selectObjektyBySerialIdSQL($idSerial)
    {
        $sql = "SELECT o.id_objektu, o.nazev_objektu
                FROM objekt_serial os
                    LEFT JOIN objekt o ON (o.id_objektu = os.id_objektu)
                WHERE os.id_serial = $idSerial;";

//        echo "selectObjektyBySerialIdSQL: $sql <br/>";
        return $sql;
    }

    //todo smazat az zmenim DB
    public static function selectFoundRowsSQL()
    {
        $sql = "SELECT FOUND_ROWS() cnt;";

//        echo "selectFoundRowsSQL: $sql <br/>";
        return $sql;
    }

    /**
     * @param $filter SerialFilter
     * @param $idSerial
     * @return string
     */
    public static function selectZajezdyBySerialIdSQL($filter, $idSerial)
    {
        $zajezdNovejsiNez = $filter->getZajezdNovejsiNez();
        $skrytProsle = $filter->getZajezdSkrytProsle();
        $date = date('Y-m-d', time());
        $whereZajezd = $zajezdNovejsiNez != "" ? " && do >= '$zajezdNovejsiNez'" : "";
        $whereZajezd .= $skrytProsle == SerialFilter::VOLBA_ZASKRTNUTA ? " && do > '$date'" : "";

        $sql = "SELECT id_zajezd, nazev_zajezdu, od, do
                FROM zajezd
                WHERE id_serial = $idSerial $whereZajezd;";

//        echo "selectZajezdyBySerialIdSQL: $sql <br/>";
        return $sql;
    }

    /**
     * @param $filter SerialFilter
     * @param $serialId
     * @return string
     */
    public static function selectZajezdyBySerialAndZajezdIdSQL($filter, $serialId)
    {
        $zajezdIds = $filter->getZajezdIdsSelected();
        $whereSql = self::whereZajezdySQL($zajezdIds, $serialId);

        $sql = "SELECT z.id_zajezd, z.nazev_zajezdu, z.od, z.do
                FROM zajezd z
                $whereSql;";

//        echo "selectZajezdyBySerialAndZajezdIdSQL: $sql <br/>";
        return $sql;
    }

    public static function selectObjednavkyByZajezdIdSQL($idZajezd)
    {
        $sql = "SELECT o.id_objednavka, uk.jmeno, uk.prijmeni, org.nazev
                FROM objednavka o
                    LEFT JOIN user_klient uk ON (o.id_klient = uk.id_klient)
                    LEFT JOIN organizace org ON (o.id_agentury = org.id_organizace)
                WHERE id_zajezd = $idZajezd && stav != " . self::OBJEDNAVKA_STAV_STORNO . " && stav != " . self::OBJEDNAVKA_STAV_STORNO_CK . ";";

//        echo "selectObjednavkyByZajezdId: $sql <br/>";
        return $sql;
    }

    /**
     * @param $filter SerialFilter
     * @param $idObjednavka
     * @return string
     */
    public static function selectUcastniciByObjednavkaIdSQL($filter, $idObjednavka)
    {
        //JSEM ZVEDAVY CO TIM POSERU, ALE PRO SEZNAMY UCASTNIKU JE TREBA POUZE NESTORNOVANE LIDI...
        //IS NOT NULL je tam, protoze to nekdy odkazuje na klienty, kteri neexistuji (nebo tak nejak, nezkoumal sem to moc podrobne)
        $sql = "SELECT uk.id_klient, uk.jmeno, uk.prijmeni, uk.titul, uk.datum_narozeni, uk.mesto, uk.psc, uk.ulice, uk.rodne_cislo, uk.cislo_pasu, uk.telefon, uk.email
                FROM objednavka o
                    LEFT JOIN objednavka_osoby oo ON (o.id_objednavka = oo.id_objednavka)
                    LEFT JOIN user_klient uk ON (oo.id_klient = uk.id_klient)
                WHERE o.id_objednavka = $idObjednavka && uk.id_klient IS NOT NULL && oo.storno !=1;";

//        echo "selectUcastniciByZajezdIdSql: $sql <br/>";
        return $sql;
    }

    public static function selectSluzbyByObjednavkaIdSQL($filter, $idObjednavka)
    {
        $sql = "SELECT oc.pocet, c.*, cz.*
                FROM objednavka_cena oc
	                LEFT JOIN cena c ON (oc.id_cena = c.id_cena)
                    LEFT JOIN cena_zajezd cz ON (c.id_cena = cz.id_cena)
                WHERE oc.id_objednavka = $idObjednavka && cz.id_zajezd = (SELECT id_zajezd FROM objednavka WHERE id_objednavka = $idObjednavka);";

//        echo "selectSluzbyByObjednavkaIdSQL: $sql <br/>";
        return $sql;
    }

    public static function selectNastupniMistaByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT c.nazev_ceny
                FROM objednavka_cena oc
                    LEFT JOIN cena c ON (oc.id_cena = c.id_cena)
                WHERE oc.id_objednavka = $idObjednavka &&
                      c.typ_ceny = " . tsSluzba::TYP_NASTUPNI_MISTO . " &&
                      kapacita_bez_omezeni = " . tsSluzba::KAPACITA_BEZ_OMEZENI_ANO . ";";

//        echo "selectNastupniMistaByObjednavkaId: $sql <br/>";
        return $sql;
    }

    public static function selectZemeSQL()
    {
        $sql = "SELECT id_zeme, nazev_zeme
                FROM zeme;";

//        echo "selectZemeSQL: $sql <br/>";
        return $sql;
    }

    public static function selectCentralniDataSQL()
    {
        $sql = "SELECT *
                FROM `centralni_data`
                WHERE `nazev` LIKE 'hlavicka:%';";
//        echo "selectCentralniDataSQL: $sql <br/>";
        return $sql;
    }

    //todo 3 dost podobne metody, asi by chtelo je spojit do 1 - tady by to chtelo promyslet a pouzit vzor builder, ale je to na dyl to rozmyslet, kazdopadne se to zacina dosti komplikvoat
    /**
     * @param $filter SerialFilter
     * @param $mode int SerialFilter::FILTER_MODE_SELECTED_SERIAL|SerialFilter::FILTER_MODE_FILTERED_SERIAL
     * @return string
     */
    public static function whereSerialySQL($filter, $mode)
    {
        $filterIds = $filter->getSerialIds();
        $filterSelectedIds = $filter->getSerialIdsSelected();
        $filterName = $filter->getSerialNazev();
        $filterZemeId = $filter->getSerialZeme();
        $whereIds = $whereSelectedIds = $whereName = $whereZeme = null;
        $where = array();

        if ($filterIds != "") {
            foreach ($filterIds as $id)
                $whereIds .= "s.id_serial = '$id' || ";
            $whereIds = substr($whereIds, 0, strlen($whereIds) - 4);
            $where[] = $whereIds;
        }
        if ($mode == SerialFilter::MODE_SELECTED_SERIAL && $filterSelectedIds != "") {
            foreach ($filterSelectedIds as $id)
                $whereSelectedIds .= "s.id_serial = '$id' || ";
            $whereSelectedIds = substr($whereSelectedIds, 0, strlen($whereSelectedIds) - 4);
            $where[] = $whereSelectedIds;
        }
        if ($filterName != "" and $mode != SerialFilter::MODE_SELECTED_SERIAL)
            $where[] = "s.nazev LIKE '%$filterName%'";
        if ($filterZemeId != "" and $mode != SerialFilter::MODE_SELECTED_SERIAL)
            $where[] = "zs.id_zeme = '$filterZemeId'";

        $whereSql = "WHERE";
        foreach ($where as $w)
            $whereSql .= "($w) && ";
        $whereSql = count((array)$where) > 0 ? substr($whereSql, 0, strlen($whereSql) - 4) : $whereSql;

        $and = count((array)$where) > 0 ? " &&" : "";
        $whereSql .= "$and zs.zakladni_zeme = 1";

        return $whereSql;
    }

    public static function whereZajezdySQL($filterIds, $serialId)
    {
        $whereSql = "";
        $where = array();

        if ($filterIds != "") {
            $whereIds = "(";
            foreach ($filterIds as $id)
                $whereIds .= "z.id_zajezd = '$id' || ";
            $whereIds = substr($whereIds, 0, strlen($whereIds) - 4) . ")";
            $where[] = $whereIds;
        }

        if (count((array)$where) == 1)
            $whereSql = "WHERE $where[0] ";
        else if (count((array)$where) == 2)
            $whereSql = "WHERE ($where[0]) && $where[1]";

        if ($serialId != "")
            $whereSql .= " && z.id_serial = $serialId";

        return $whereSql;
    }

}