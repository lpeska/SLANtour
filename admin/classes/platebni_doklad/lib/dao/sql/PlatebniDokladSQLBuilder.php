<?php


class PlatebniDokladSQLBuilder
{

    const KONTAKT_TYP_SIDLO_SPOLECNOSTI = 1;
    const TYP_OBJEKTU_1 = 1;

    public static function readCentralniData()
    {
        $sql = "SELECT *
                FROM centralni_data
                WHERE nazev LIKE 'hlavicka:%';";
//        echo "readCentralniDataSQL: $sql <br/>";
        return $sql;
    }

    public static function readObjednavajiciByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT uk.id_klient, uk.jmeno, uk.prijmeni, uk.email, uk.telefon, uk.ulice, uk.mesto, uk.psc
                FROM objednavka o
                    LEFT JOIN user_klient uk ON (o.id_klient = uk.id_klient)
                WHERE o.id_objednavka = $idObjednavka";
//        echo "readObjednavajiciByObjednavkaId: $sql <br/>";
        return $sql;
    }

    public static function readObjednavajiciOrgByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT org.nazev, org.ico, org.dic, oe.email, ot.telefon, oa.ulice, oa.mesto, oa.psc
						FROM objednavka o
						  RIGHT JOIN organizace org ON (o.id_organizace = org.id_organizace)
						  LEFT JOIN organizace_adresa oa ON (o.id_organizace = oa.id_organizace)
						  LEFT JOIN organizace_email oe ON (o.id_organizace = oe.id_organizace)
						  LEFT JOIN organizace_telefon ot ON (o.id_organizace = ot.id_organizace)
						WHERE o.id_objednavka = $idObjednavka
						LIMIT 1";
//        echo "readObjednavajiciOrgByObjednavkaId: $sql <br/>";
        return $sql;
    }

    public static function readUcastniciByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT uk.id_klient, uk.jmeno, uk.prijmeni, uk.email, uk.telefon, uk.ulice, uk.mesto, uk.psc
                FROM objednavka_osoby oo
                    LEFT JOIN user_klient uk ON (oo.id_klient = uk.id_klient)
                WHERE oo.id_objednavka = $idObjednavka";
//        echo "readUcastniciByObjednavkaId: $sql <br/>";
        return $sql;
    }

    public static function readProdejceByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT org.id_organizace, org.nazev, oe.email, ot.telefon, oa.ulice, oa.mesto, oa.psc
                FROM objednavka o
                    LEFT JOIN organizace org ON (o.id_agentury = org.id_organizace)
                    LEFT JOIN organizace_adresa oa ON (o.id_agentury = oa.id_organizace)
                    LEFT JOIN organizace_email oe ON (o.id_agentury = oe.id_organizace)
                    LEFT JOIN organizace_telefon ot ON (o.id_agentury = ot.id_organizace)
                WHERE
                    org.id_organizace IS NOT NULL &&
                    oa.typ_kontaktu = " . self::KONTAKT_TYP_SIDLO_SPOLECNOSTI . " &&
                    o.id_objednavka = $idObjednavka;";
//        echo "readAgenturaByObjednavkaId: $sql <br/>";
        return $sql;
    }

    public static function readZajezdByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT z.*, s.nazev as nazev_serial, s.id_sablony_zobrazeni, ou.nazev_ubytovani
                FROM objednavka obj
                    LEFT JOIN zajezd z ON (obj.id_zajezd = z.id_zajezd)
                    LEFT JOIN serial s ON (z.id_serial = s.id_serial)
                    LEFT JOIN objekt_serial os ON (s.id_serial = os.id_serial)
                    LEFT JOIN objekt o ON (os.id_objektu = o.id_objektu)
                    LEFT JOIN objekt_ubytovani ou ON (o.id_objektu = ou.id_objektu)
                WHERE obj.id_objednavka = $idObjednavka && (
                    CASE
                        WHEN os.id_objektu IS NOT NULL
                            THEN o.typ_objektu = " . self::TYP_OBJEKTU_1 . "
                        ELSE 1
                    END);";
//        echo "readZajezdByObjednavkaId: $sql <br/>";
        return $sql;
    }

    public static function readObjednavkaById($idObjednavka)
    {
        $sql = "SELECT *
                FROM objednavka
                WHERE id_objednavka = $idObjednavka";
//        echo "readObjednavkaById: $sql <br/>";
        return $sql;
    }

    public static function readPlatbyByObjednavkaId($idObjednavka)
    {
        $sql = "SELECT *
                FROM objednavka_platba
                WHERE id_objednavka = $idObjednavka";
//        echo "readPlatbyByObjednavkaId: $sql <br/>";
        return $sql;
    }

}