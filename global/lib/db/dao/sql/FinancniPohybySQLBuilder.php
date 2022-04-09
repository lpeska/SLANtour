<?php

/**
 * Class FinancniPohybySQLBuilder todo WHERE nepocitaji s prepared statementy
 */
class FinancniPohybySQLBuilder
{

    const TYP_OBJEKTU_UBYTOVANI = 1;

    /**
     * @param SerialFilter $filter
     * @param bool $paging
     * @param mixed[] $params
     * @return SQLQuery
     */
    public static function readSerialListFilteredSQL($filter, $paging = true, $params)
    {
        $page = $filter->getPagingPage();

        $whereSql = self::whereSerialySQL($filter);
        $offset = ($page - 1) * $filter->pagingMaxPerPage;
        $limit = $paging ? "LIMIT $offset, " . $filter->pagingMaxPerPage : "";
        $calc = $paging ? "SQL_CALC_FOUND_ROWS" : "";

        $sql = "SELECT $calc s.id_serial, s.nazev, ts.nazev_typ
                FROM serial s
                    LEFT JOIN typ_serial ts ON (s.id_typ = ts.id_typ)
                    LEFT JOIN zeme_serial zs ON (s.id_serial = zs.id_serial)
                $whereSql
                $limit;";
       // echo nl2br($sql);
        return new SQLQuery($sql, $params);
    }

    /**
     * @param SerialFilter $filter
     * @return SQLQuery
     */
    public static function readSelectedSerialListSQL($filter)
    {
        $filterZajezdSelectedIds = $filter->getZajezdIdsSelected();

        if ($filterZajezdSelectedIds != "") {
            $whereZajezdSelectedIds = "(";
            foreach ($filterZajezdSelectedIds as $id)
                $whereZajezdSelectedIds .= "z.id_zajezd = '$id' || ";
            $whereZajezdSelectedIds = substr($whereZajezdSelectedIds, 0, -4) . ")";
        } else {
            $whereZajezdSelectedIds = " z.id_zajezd = -1 ";
        }

        $sql = "SELECT DISTINCT s.id_serial, s.nazev, s.id_sablony_zobrazeni
                FROM serial s
                    LEFT JOIN zajezd z ON (s.id_serial = z.id_serial)
                WHERE $whereZajezdSelectedIds;";

        return new SQLQuery($sql, array());
    }

    /**
     * @param SerialFilter $filter
     * @param $idSerial
     * @return SQLQuery
     */
    public static function readZajezdListBySerialIdSQL($filter, $idSerial)
    {
        $zajezdOd = $filter->getZajezdOd();
        $zajezdDo = $filter->getZajezdDo();
        $objednavkaOd = $filter->getObjednavkaOd();
        $objednavkaDo = $filter->getObjednavkaDo();
        $zajezdNoObjednavka = $filter->getZajezdNoObjednavka();
        $zajezdObjednavka = $filter->getZajezdObjednavka();

        $whereZajezd = ($zajezdOd != "" and $zajezdOd != "0000-00-00") ? " && z.do >= '$zajezdOd' " : "";
        $whereZajezd .= ($zajezdDo != ""  and $zajezdDo != "0000-00-00") ? " && z.od <= '$zajezdDo' " : "";
        $whereZajezd .= ($objednavkaOd != "" and $objednavkaOd != "0000-00-00") ? " && (SELECT COUNT(obj.id_objednavka) FROM objednavka obj WHERE obj.id_zajezd = z.id_zajezd && obj.termin_do >= '$objednavkaOd') >= 1 " : "";
        $whereZajezd .= ($objednavkaDo != "" and $objednavkaDo != "0000-00-00") ? " && (SELECT COUNT(obj.id_objednavka) FROM objednavka obj WHERE obj.id_zajezd = z.id_zajezd && obj.termin_od <= '$objednavkaDo') >= 1 " : "";
        if ($zajezdNoObjednavka != "" && $zajezdObjednavka != "") {
            $whereZajezd .= " && (
                                ((SELECT COUNT(obj.id_objednavka) FROM objednavka obj WHERE obj.id_zajezd = z.id_zajezd) = 0) ||
                                ((SELECT COUNT(obj.id_objednavka) FROM objednavka obj WHERE obj.id_zajezd = z.id_zajezd) >= 1)
                              ) ";
        } else {
            $whereZajezd .= ($zajezdNoObjednavka != "") ? " && (SELECT COUNT(o.id_objednavka) FROM objednavka o WHERE o.id_zajezd = z.id_zajezd) = 0 " : "";
            $whereZajezd .= $zajezdObjednavka != "" ? " && (SELECT COUNT(o.id_objednavka) FROM objednavka o WHERE o.id_zajezd = z.id_zajezd) >= 1 " : "";
        }

        $sql = "SELECT z.id_zajezd, z.od, z.do
                FROM zajezd z
                WHERE z.id_serial = $idSerial $whereZajezd;";
     //   echo nl2br($sql);
        return new SQLQuery($sql, array());
    }

    /**
     * @param SerialFilter $filter
     * @param $idSerial
     * @return SQLQuery
     */
    public static function readSelectedZajezdListBySerialIdSQL($filter, $idSerial)
    {
        $filterZajezdSelectedIds = $filter->getZajezdIdsSelected();
        $whereZajezd = "";
        if ($filterZajezdSelectedIds != "") {
            $whereZajezd = " && (";
            foreach ($filterZajezdSelectedIds as $id)
                $whereZajezd .= "z.id_zajezd = '$id' || ";
            $whereZajezd = substr($whereZajezd, 0, -4) . ")";
        }

        $sql = "SELECT z.id_zajezd, z.od, z.do
                FROM zajezd z
                WHERE z.id_serial = $idSerial $whereZajezd;";

        return new SQLQuery($sql, array());
    }

    public static function selectObjektyBySerialIdSQL($params)
    {
        $sql = "SELECT o.*
                FROM objekt_serial os
                    LEFT JOIN objekt o ON (os.id_objektu = o.id_objektu)
                WHERE os.id_serial = ?;";

        return new SQLQuery($sql, $params);
    }

    
    public static function readObjednavkaListInTerminByZajezdIdSQL($params)
    {
        $termin = "";

        //edit Lada: dle vyjadreni od taty je lepsi, aby se v prehledu zobrazily uz vsechny financni pohyby na zajezdu (filtr slouzi jen k vyhledani zajezdu, ktere nas zajimaji)
        //edit 2 Lada: dle vyjadreni taty z 20.6. predchozi neplati - aspon nekam zaznamenam jaky filtr se aplikuje...
        if($params[1] != "" and $params[1] != "0000-00-00"){
            $termin .= " and o.datum_rezervace >=\"".$params[1]." 00:00:00\"";
        }
        if($params[2] != "" and $params[2] != "0000-00-00"){
            $termin .= " and o.datum_rezervace <=\"".$params[2]." 23:59:59\"";
        }
        if($params[3] != "" and $params[3] != "0000-00-00"){
            $termin .= " and o.termin_do >=\"".$params[3]."\"";
        }
        if($params[4] != "" and $params[4] != "0000-00-00"){
            $termin .= " and o.termin_do <=\"".$params[4]."\"";
        }
  
        $params = [$params[0]];
        

        $sql = "SELECT o.*
                FROM objednavka o
                LEFT JOIN zajezd z ON (o.id_zajezd = z.id_zajezd)
                WHERE o.id_zajezd = ? $termin;";
//echo $sql;
        return new SQLQuery($sql, $params);
    }

    public static function readSerialHasZajezdSQL($params)
    {
        $sql = "SELECT COUNT(z.id_zajezd) AS cnt
                FROM zajezd z
                WHERE z.id_serial = ?;";

        return new SQLQuery($sql, $params);
    }

    public static function readZajezdHasObjednavkaSQL($params)
    {
        $sql = "SELECT COUNT(o.id_objednavka) AS cnt
                FROM zajezd z
                    LEFT JOIN objednavka o ON (o.id_zajezd = z.id_zajezd)
                WHERE z.id_zajezd = ?;";

        return new SQLQuery($sql, $params);
    }

    public static function readObjednavajiciByObjednavkaIdSQL($params)
    {
        $sql = "SELECT uk.*
                FROM user_klient uk
                    LEFT JOIN objednavka o ON (o.id_klient = uk.id_klient)
                WHERE o.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }
//Lada: cely dotaz jsem prekopal - vydavat slevy za sluzby je obecne strasne problematicke, tak jsem to zrusil a pocitam je bokem
    
    public static function readSluzbyByObjednavkaAndZajezdIdSQL($params)
    {
        //u slev, ktere jsou ulozeny jako normalni slevy se sleva pocita s opacnym znamenkem nez u ostatnich slev
        $typSleva = SluzbaEnt::TYP_SLEVA;
         $sql = "SELECT c.id_cena, c.typ_ceny AS typ, oc.use_pocet_noci, oc.pocet,      
                  IF(oc.cena_mena IS NULL, cz.castka,  oc.cena_castka) AS castka,
                  IF(oc.cena_mena IS NULL, cz.mena, oc.cena_mena) AS mena
                FROM objednavka_cena oc
	              LEFT JOIN cena c ON (oc.id_cena = c.id_cena)
                  LEFT JOIN cena_zajezd cz ON (c.id_cena = cz.id_cena)
                WHERE oc.id_objednavka = ? && cz.id_zajezd = ?
                UNION ALL
                SELECT oc2.id_cena, 1 AS typ, oc2.use_pocet_noci, oc2.pocet, oc2.castka, oc2.mena
                FROM objednavka_cena2 oc2
                WHERE oc2.id_objednavka = ?;";
    
        //sluzba muze mit starou / novou cenu - tzn zmenila se u ni cena, ale u jiz provedenych objednavek musi zustat stara
       /*puvodni dotaz
        *  $sql = "SELECT c.id_cena, c.typ_ceny AS typ, oc.use_pocet_noci, oc.pocet,
        
                  IF(oc.cena_mena IS NULL, IF(c.typ_ceny = $typSleva, cz.castka * (-1), cz.castka), IF(c.typ_ceny = $typSleva, oc.cena_castka * (-1), oc.cena_castka)) AS castka,
                  IF(oc.cena_mena IS NULL, cz.mena, oc.cena_mena) AS mena
                FROM objednavka_cena oc
	              LEFT JOIN cena c ON (oc.id_cena = c.id_cena)
                  LEFT JOIN cena_zajezd cz ON (c.id_cena = cz.id_cena)
                WHERE oc.id_objednavka = ? && cz.id_zajezd = ?
                UNION ALL
                SELECT oc2.id_cena, 1 AS typ, oc2.use_pocet_noci, oc2.pocet, oc2.castka, oc2.mena
                FROM objednavka_cena2 oc2
                WHERE oc2.id_objednavka = ?
                UNION ALL
                SELECT 0 AS id_cena, 3 AS typ, 0 AS use_pocet_noci, 1 AS pocet, os.castka_slevy AS castka, 'Kè' AS mena
                FROM objednavka_sleva os
                WHERE os.id_objednavka = ?;";
          */
        return new SQLQuery($sql, $params);
    }

    public static function readPlatbyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT op.*
                FROM objednavka_platba op
                WHERE op.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }

    public static function readSlevyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT `objednavka_sleva`.*
					FROM `objednavka_sleva`
					WHERE `id_objednavka`= ?
					ORDER BY `objednavka_sleva`.`velikost_slevy` DESC;
		";
        return new SQLQuery($sql, $params);
    }
    
    
    
    public static function readFakturyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT f.*
                FROM faktury f
                WHERE f.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }

    /**
     * Creates WHERE clausule of SQL query. It depends on filter values (user selected filters) and mode. todo rozdelit na dalsi metody
     * @param SerialFilter $filter
     * @return string
     */
     private static function whereSerialySQL($filter)
    {
        $filterIds = $filter->getSerialIds();
        $filterName = $filter->getSerialNazev();
        $filterZemeId = $filter->getSerialZeme();
        $filterSerialTyp = $filter->getSerialTyp();
        $serialNoZajezd = $filter->getSerialNoZajezd();
        $serialNoAktivniZajezd = $filter->getSerialNoAktivniZajezd();
        $serialAktivniZajezd = $filter->getSerialAktivniZajezd();
        $zajezdNoObjednavka = $filter->getZajezdNoObjednavka();
        $zajezdObjednavka = $filter->getZajezdObjednavka();
        $zajezdOd = $filter->getZajezdOd();
        $zajezdDo = $filter->getZajezdDo();
        $objednavkaOd = $filter->getObjednavkaOd();
        $objednavkaDo = $filter->getObjednavkaDo();
        $realizaceOd = $filter->getRealizaceOd();
        $realizaceDo = $filter->getRealizaceDo();
        $where = array();
        $now = date("Y-m-d");
        $whereTypAZeme = $whereSerial = $whereZajezd = $whereIds = $whereZajezdTermin = $whereObjednavkaTermin = "";

        /* --------------------------------------------------------------------------------------------------------- */
        //odfiltrovane id
        if ($filterIds != "") {
            $whereIds = "(";
            foreach ($filterIds as $id)
                $whereIds .= "s.id_serial = '$id' || \n";
            $whereIds = substr($whereIds, 0, -4) . ")";
        } else {
            $whereIds = " 1=1 ";
        }
        /* --------------------------------------------------------------------------------------------------------- */

        /* --------------------------------------------------------------------------------------------------------- */
        //name, type, coutry
        if ($filterName != "") //note proc neni databaze cela v UTF8?
            //edit Lada: pridal jsem konstrukci "|| s.nazev LIKE '%$filterName%'" (pokud k serialu nebyl pripojeny zadny objekt, predchozi porovnani automaticky selhalo
            $where[] = "((SELECT CONCAT(CAST(s.nazev AS CHAR), ' ', CAST(obj.nazev_objektu AS CHAR)) AS nazev
                        FROM objekt_serial os
                            LEFT JOIN objekt obj ON (os.id_objektu = obj.id_objektu)
                        WHERE os.id_serial = s.id_serial && obj.typ_objektu = " . self::TYP_OBJEKTU_UBYTOVANI . "
                        LIMIT 1) LIKE '%$filterName%' || s.nazev LIKE '%$filterName%')";
        if ($filterSerialTyp != "")
            $where[] = "ts.id_typ = $filterSerialTyp";
        if ($filterZemeId != "") {
            $where[] = "zs.id_zeme = '$filterZemeId'";
            $zakladniZeme = " 1=1 ";
        } else {
            $zakladniZeme = " zs.zakladni_zeme = 1";
        }
        //sestav dotaz a pridej k aktualnimu dotazu
        foreach ($where as $w)
            $whereTypAZeme .= "($w) && \n";
        $whereTypAZeme = count((array)$where) > 0 ? "(" . substr($whereTypAZeme, 0, -4) . ")" : " 1=1 ";
        $where = array();
        /* --------------------------------------------------------------------------------------------------------- */

        /* --------------------------------------------------------------------------------------------------------- */
        //serial
        if ($serialNoZajezd == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial) = 0";
        if ($serialNoAktivniZajezd == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial) >= 1\n && (SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial && z.do >= '$now' ) = 0";
        if ($serialAktivniZajezd == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial) >= 1\n && (SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial && z.do >= '$now' ) >= 1";
        //sestav dotaz a pridej k aktualnimu
        foreach ($where as $w)
            $whereSerial .= "($w) || \n";
        $whereSerial = count((array)$where) > 0 ? "(" . substr($whereSerial, 0, -4) . ")" : " 1=1 ";
        $where = array();
        /* --------------------------------------------------------------------------------------------------------- */

        /* --------------------------------------------------------------------------------------------------------- */
        //zajezd
        if ($zajezdNoObjednavka == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(o.id_objednavka) FROM objednavka o WHERE o.id_serial = s.id_serial) = 0";
        if ($zajezdObjednavka == SerialFilter::VOLBA_ZASKRTNUTA)
            $where[] = "(SELECT COUNT(o.id_objednavka) FROM objednavka o WHERE o.id_serial = s.id_serial) >= 1";
        //sestav dotaz a pridej k aktualnimu
        foreach ($where as $w)
            $whereZajezd .= "($w) || \n";
        $whereZajezd = count((array)$where) > 0 ? "(" . substr($whereZajezd, 0, -4) . ")" : " 1=1 ";
        $where = array();
        /* --------------------------------------------------------------------------------------------------------- */

        /* --------------------------------------------------------------------------------------------------------- */
        //od - do zajezd
        //Edit Lada: nezavislost tech podminek zpusobovala radu problemu/necekaneho chovani. chtelo by vsechno idealne narvat do jednoho where, 
        //prozatim jsem odstranil aspon to nejkriklavejsi (terminy rezervace a zajezdu se vyhodnocovaly zvlast)
        if (($zajezdOd != ""  and $zajezdOd !="0000-00-00") or ($zajezdDo != ""  and $zajezdDo !="0000-00-00")) {
            $podminky = array();
            if ($zajezdOd != ""  and $zajezdOd !="0000-00-00"){
                $podminky[] = "z.do >= '$zajezdOd'";
            }
            if ($zajezdDo != ""  and $zajezdDo !="0000-00-00"){
                $podminky[] = "z.od <= '$zajezdDo'";
            }
            $podm = implode(" && ", $podminky);
            $where[] = "(SELECT COUNT(z.id_zajezd) FROM zajezd z WHERE z.id_serial = s.id_serial && $podm) >= 1";
        }

        //sestav dotaz a pridej k aktualnimu
        foreach ($where as $w)
            $whereZajezdTermin .= "($w) && \n";
        $whereZajezdTermin = count((array)$where) > 0 ? "(" . substr($whereZajezdTermin, 0, -4) . ")" : " 1=1 ";
        $where = array();

        //od - do objednavka
        //Edit Lada
        /* --------------------------------------------------------------------------------------------------------- */
        if (($objednavkaOd != "" and $objednavkaOd !="0000-00-00") 
                or ($objednavkaDo != "" and $objednavkaDo !="0000-00-00") 
                or ($realizaceOd != "" and $realizaceOd !="0000-00-00")
                or ($realizaceDo != "" and $realizaceDo !="0000-00-00")) {
            $podminky = array();
            if ($objednavkaOd != "" and $objednavkaOd !="0000-00-00"){
                $podminky[] = "obj.datum_rezervace >= '$objednavkaOd 00:00:00'";
            }
            if ($objednavkaDo != "" and $objednavkaDo !="0000-00-00"){
                $podminky[] = "obj.datum_rezervace <= '$objednavkaDo 23:59:59'";
            }
            if ($realizaceOd != "" and $realizaceOd !="0000-00-00"){
                $podminky[] = "obj.termin_do >= '$realizaceOd'";
            }
            if ($realizaceDo != "" and $realizaceDo !="0000-00-00"){
                $podminky[] = "obj.termin_do <= '$realizaceDo'";
            }
            $podm = implode(" && ", $podminky);
            $where[] = "(SELECT COUNT(obj.id_objednavka) FROM objednavka obj WHERE obj.id_serial = s.id_serial && $podm) >= 1";
        }        

        //sestav dotaz a pridej k aktualnimu
        foreach ($where as $w)
            $whereObjednavkaTermin .= "($w) && \n";
        $whereObjednavkaTermin = count((array)$where) > 0 ? "(" . substr($whereObjednavkaTermin, 0, -4) . ")" : " 1=1 ";
        $where = array();
        /* --------------------------------------------------------------------------------------------------------- */

        //zkompletuj dotaz
        $whereSql = "WHERE $whereIds\n\n && $whereTypAZeme\n\n && $whereSerial\n\n && $whereZajezd\n\n && $whereZajezdTermin\n\n && $whereObjednavkaTermin\n\n && $zakladniZeme";
        return $whereSql;
    }

}