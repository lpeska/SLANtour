<?php

class OrganizaceSQLBuilder
{
    public static function readObjednavkaListByOrganizaceIdSQL($params)
    {
        $sql = "SELECT obj.*, uk.*, s.nazev
                FROM objednavka obj
                  LEFT JOIN user_klient uk ON (obj.id_klient = uk.id_klient)
                  LEFT JOIN serial s ON (obj.id_serial = s.id_serial)
                WHERE obj.id_agentury = ?
                ORDER BY obj.datum_rezervace DESC;";

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

    public static function readSlevyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT *
					FROM objednavka_sleva
					WHERE id_objednavka = ?
					ORDER BY velikost_slevy DESC;
		";
        return new SQLQuery($sql, $params);
    }

}