<?php

class FakturaProvizeSQLBuilder
{

    //todo - zjistit o jaky kontakt jde a prejmenovat konstantu
    const TYP_KONTAKTU_0 = 0;
    const TYP_KONTAKTU_1 = 1;
    const TYP_KONTAKTU_2 = 2;

    public static function readCentralniDataByTagNameSQL($params)
    {
        $sql = "SELECT cd.*
                FROM centralni_data cd
                WHERE cd.nazev LIKE ?;";

        return new SQLQuery($sql, $params);
    }

    public static function readOrganizaceByIdSQL($params)
    {
        $sql = "SELECT org.*, oa.*
                FROM organizace org
                  LEFT JOIN organizace_adresa oa ON (
                    org.id_organizace = oa.id_organizace &&
                    CASE WHEN (SELECT id_organizace FROM organizace_adresa WHERE id_organizace = org.id_organizace && oa.typ_kontaktu = " . self::TYP_KONTAKTU_1 . ") IS NOT NULL
    		                        THEN oa.typ_kontaktu = " . self::TYP_KONTAKTU_1 . "
        	                        ELSE oa.typ_kontaktu = " . self::TYP_KONTAKTU_2 . "
    	            END)
                WHERE org.id_organizace = ?;";
        $sql = "SELECT org.*, oa.*, ot.telefon, oe.email, obs.*
                FROM organizace org
                  LEFT JOIN (
                              SELECT *
                              FROM organizace_adresa oa
                              WHERE oa.id_organizace = ? && oa.typ_kontaktu = " . self::TYP_KONTAKTU_1 . " || oa.typ_kontaktu = " . self::TYP_KONTAKTU_2 . "
                              ORDER BY oa.typ_kontaktu
                              LIMIT 1
                            ) AS oa ON (org.id_organizace = oa.id_organizace)
                  LEFT JOIN (
                              SELECT *
                              FROM organizace_telefon ot
                              WHERE ot.id_organizace = ? && ot.typ_kontaktu = " . self::TYP_KONTAKTU_0 . " || ot.typ_kontaktu = " . self::TYP_KONTAKTU_1 . "
                              ORDER BY ot.typ_kontaktu
                              LIMIT 1
                            ) AS ot ON (org.id_organizace = ot.id_organizace)
                  LEFT JOIN (
                              SELECT *
                              FROM organizace_email oe
                              WHERE oe.id_organizace = ? && oe.typ_kontaktu = " . self::TYP_KONTAKTU_0 . " || oe.typ_kontaktu = " . self::TYP_KONTAKTU_1 . "
                              ORDER BY oe.typ_kontaktu
                              LIMIT 1
                            ) AS oe ON (org.id_organizace = oe.id_organizace)
                  LEFT JOIN (
                              SELECT *
                              FROM organizace_bankovni_spojeni obs
                              WHERE obs.id_organizace = ? && obs.typ_kontaktu = " . self::TYP_KONTAKTU_1 . "
                              ORDER BY obs.typ_kontaktu
                              LIMIT 1
                            ) AS obs ON (org.id_organizace = obs.id_organizace)                            
                WHERE org.id_organizace = ?;";

        return new SQLQuery($sql, $params);
    }
    public static function readFakturaProvizeByIdObjednavka($params)
    {
        $sql = "SELECT fp.*
                FROM `faktury_provize` fp
                WHERE fp.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }

    public static function readObjednavkaByIdSQL($params)
    {
        $sql = "SELECT obj.*, uk.*, s.nazev
                FROM objednavka obj
                  LEFT JOIN user_klient uk ON (obj.id_klient = uk.id_klient)
                  LEFT JOIN serial s ON (obj.id_serial = s.id_serial)
                WHERE obj.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }

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

    public static function readUcastniciByObjednavkaIdSQL($params)
    {
        $sql = "SELECT uk.*
                FROM objednavka_osoby oo
                  LEFT JOIN user_klient uk ON (oo.id_klient = uk.id_klient)
                WHERE oo.id_objednavka = ?;";

        return new SQLQuery($sql, $params);
    }

    public static function createFakturaProvize($params)
    {
        $sql = "INSERT INTO faktury_provize
                  (id_objednavka, id_agentura, cislo_faktury, datum_vystaveni, celkova_castka, poznamka)
                  VALUES
                  (?, ?, ?, ?, ?, ?);";

        return new SQLQuery($sql, $params);
    }

    public static function readSluzbyByObjednavkaIdSQL($params)
    {
        /*Lada: nechapu proc je tady LEFT join u ceny a ceny zajezdu. navic chybi navazani mezi id zajezdu a id ceny zajezdu - duplikuje to data
         * - opraveno, left joiny jsem nechal, ale myslim ze jsou zbytecne
         */
        $sql = "SELECT c.id_cena, c.nazev_ceny, c.typ_ceny AS typ, c.use_pocet_noci, cz.castka, cz.mena, oc.pocet
                  FROM objednavka o                  
                        join objednavka_cena oc on (o.id_objednavka = oc.id_objednavka)
	                LEFT JOIN cena c ON (oc.id_cena = c.id_cena)
                    LEFT JOIN cena_zajezd cz ON (c.id_cena = cz.id_cena and o.id_zajezd = cz.id_zajezd)
                  WHERE o.id_objednavka = ?
                UNION
                  SELECT c.id_cena, c.nazev_ceny, 1 AS typ, oc2.use_pocet_noci, oc2.castka, oc2.mena, oc2.pocet
                  FROM objednavka_cena2 oc2
                    LEFT JOIN cena c ON (oc2.id_cena = c.id_cena)
                  WHERE oc2.id_objednavka = ?
                UNION
                  SELECT 0 AS id_cena, os.nazev_slevy AS nazev_ceny, 3 AS typ, 0 AS use_pocet_noci, os.castka_slevy AS castka, 'Kè' AS mena, 1 AS pocet
                  FROM objednavka_sleva os
                  WHERE os.id_objednavka = ?;";
        return new SQLQuery($sql, $params);
    }
}