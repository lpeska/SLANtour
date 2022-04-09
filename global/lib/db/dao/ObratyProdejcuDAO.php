<?php


class ObratyProdejcuDAO {

    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    /**
     * @param $role
     * @param $dateType
     * @param $dateOd
     * @param $dateDo
     * @return null|OrganizaceEnt[]
     */
    public static function readOrganizaceList($role, $dateType, $dateOd, $dateDo)
    {
        $organizace = null;

        self::$db->connect();

        //s organizaci
        $stmtSerialy = self::$db->query(ObratyProdejcuSQLBuilder::readOrganizaceListSQL($role));

        while (@$row = $stmtSerialy->fetch(PDO::FETCH_OBJ)) {
            $objednavky = self::readObjednavkaByOrganizaceId($row->id_organizace, $dateType, $dateOd, $dateDo);
            $adresy = self::readAdresyByOrganizaceId($row->id_organizace);

            $o = new OrganizaceEnt($row->id_organizace, $row->nazev, null, null, $row->role);
            $o->setObjednavky($objednavky);
            $o->setAdresy($adresy);

            $organizace[] = $o;
        }

        self::$db->close();

        return $organizace;
    }

    private static function readObjednavkaByOrganizaceId($idOrganizace, $dateType, $dateOd, $dateDo)
    {
        $objednavky = null;

        $stmt = self::$db->query(ObratyProdejcuSQLBuilder::readObjednavkyByOrganizaceIdSQL($dateType, $dateOd, $dateDo, [$idOrganizace, ObjednavkaEnt::STAV_OPCE, ObjednavkaEnt::STAV_REZERVACE, ObjednavkaEnt::STAV_ZALOHA, ObjednavkaEnt::STAV_PRODANO, ObjednavkaEnt::STAV_ODBAVENO]));

        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $o = new ObjednavkaEnt(
                $row->id_objednavka, $row->pocet_osob, $row->pocet_noci, $row->celkova_cena, $row->zbyva_zaplatit,
                null, $row->datum_rezervace, $row->stav, $row->termin_od, $row->termin_do, $row->storno_datum, $row->storno_poplatek
            );
            $sluzby = self::readSluzbyByObjednavkaAndZajezdId($row->id_objednavka, $row->id_zajezd);
            $slevy = self::readSlevyByObjednavkaId($row->id_objednavka);
            $platby = self::readPlatbyByObjednavkaId($row->id_objednavka);
            $o->setSluzby($sluzby);
            $o->setSlevy($slevy);
            $o->setPlatby($platby);

            $objednavky[] = $o;
        }

        return $objednavky;
    }

    private static function readSlevyByObjednavkaId($idObjednavka)
    {
        $slevy = null;

        $stmt = self::$db->query(ObratyProdejcuSQLBuilder::readSlevyByObjednavkaIdSQL([$idObjednavka]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            if ($row->castka_slevy > 0 and $row->mena != "%") {
                $s = new SlevaEnt(null, $row->nazev_slevy, $row->castka_slevy, "Kè", null);
            } else {
                $s = new SlevaEnt(null, $row->nazev_slevy, $row->velikost_slevy, $row->mena, null);
            }

            $s->setTyp(SlevaEnt::SLEVA_TYP_OBJEDNANA);
            $slevy[] = $s;
        }

        return $slevy;
    }

    private static function readPlatbyByObjednavkaId($idObjednavka)
    {
        $platby = null;

        $stmt = self::$db->query(ObratyProdejcuSQLBuilder::readPlatbyByObjednavkaIdSQL([$idObjednavka]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $platby[] = new PlatbaEnt($row->id_platba, $row->cislo_dokladu, $row->castka, $row->vystaveno, $row->splatit_do, $row->splaceno, $row->typ_dokladu, $row->zpusob_uhrady);
        }

        return $platby;
    }

    private static function readSluzbyByObjednavkaAndZajezdId($idObjednavka, $idZajezd)
    {
        $sluzby = null;

        $stmt = self::$db->query(ObratyProdejcuSQLBuilder::readSluzbyByObjednavkaAndZajezdIdSQL([$idObjednavka, $idZajezd, $idObjednavka]));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $sluzby[] = new SluzbaEnt($row->id_cena, null, $row->typ, $row->castka, $row->mena, $row->pocet, $row->use_pocet_noci, null, null, null, null, null, null, null, null);
        }

        return $sluzby;
    }

    private static function readAdresyByOrganizaceId($idOrganizace)
    {
        $adresy = null;

        $stmt = self::$db->query(ObratyProdejcuSQLBuilder::readAdresyByOrganizaceIdSQL([$idOrganizace]));

        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $a = new AdresaEnt(null, null, $row->mesto, null, null, null, null, $row->typ_kontaktu, null);

            $adresy[] = $a;
        }

        return $adresy;
    }

}