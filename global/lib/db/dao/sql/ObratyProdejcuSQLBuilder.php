<?php

class ObratyProdejcuSQLBuilder
{
    public static function readOrganizaceListSQL($role)
    {
        $roleSql = $role == 0 ? "" : " && role = $role ";

        $sql = "SELECT *
                FROM organizace
                WHERE 1 $roleSql
                ORDER BY nazev;";
        return new SQLQuery($sql, []);
    }

    public static function readObjednavkyByOrganizaceIdSQL($dateType, $dateOd, $dateDo, $params)
    {
        $dateSql = "";
        if($dateOd != '0000-00-00' && $dateDo != '0000-00-00') {
            if ($dateType == 'objednavka') {
                $dateSql = " && (datum_rezervace >= '$dateOd' && datum_rezervace <= '$dateDo')";
            } else if ($dateType == 'odjezd') {
                $dateSql = " && ((termin_od >= '$dateOd' && termin_od <= '$dateDo') || (od >= '$dateOd' && od <= '$dateDo'))";
            }
        }
        $sql = "SELECT objednavka.*
                FROM objednavka
                LEFT JOIN zajezd ON objednavka.id_zajezd = zajezd.id_zajezd
                WHERE id_agentury = ? && stav IN (?, ?, ?, ?, ?) $dateSql
                ;";
        return new SQLQuery($sql, $params);
    }

    public static function readSlevyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT *
					FROM objednavka_sleva
					WHERE id_objednavka = ?
					ORDER BY velikost_slevy DESC;
		";
        return new SQLQuery($sql, $params);
    }

    public static function readPlatbyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT op.*
                FROM objednavka_platba op
                WHERE op.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }

    public static function readSluzbyByObjednavkaAndZajezdIdSQL($params)
    {
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
        return new SQLQuery($sql, $params);
    }

    public static function readAdresyByOrganizaceIdSQL($params)
    {
        $sql = "SELECT *
                FROM organizace_adresa
                WHERE id_organizace = ?
                ;";
        return new SQLQuery($sql, $params);
    }

}