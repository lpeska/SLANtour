<?php


class ObjednavkaProcesSQLBuilder extends SQLBuilder
{

    const ANO = 1;
    const ZAKLADNI_FOTO_ANO = 1;
    const TYP_OBJEKTU_UBYTOVANI = 1;
    const DLOUHODOBE_ZAJEZDY_ANO = 1;
    const TYP_OBJEKTU_1 = 1;
    const CENTRALNI_DATA_KURZ_EUR_TITLE = "kurz EUR";
    const CENTRALNI_DATA_NAZEV_CK_TITLE = "hlavicka:nazev_spolecnosti";
    const CENTRALNE_DATA_PLATEBNI_METODY = "%platebni_spojeni:%";
    const NO_DATE = "'0000-00-00'";
    const ID_SABLONY_ZOBRAZENI_12 = 12;
    const SLEVA_PLATNOST_ANO = 1;
    const SLEVA_STALY_KLIENT_NE = 0;
    const CENA_ZAJEZD_ZOBRAZOVAT_ANO = 1;

    /**
     * @param $params []
     * @return SQLQuery
     */
    public static function selectZajezdByIdSQL($params)
    {
        $sql = "SELECT z.*, s.dlouhodobe_zajezdy, s.id_sablony_zobrazeni,`dokument`.`dokument_url` as `smluvni_podminky`
                FROM zajezd z
                    JOIN serial s ON (z.id_serial = s.id_serial)
                    join `smluvni_podminky_nazev` on (`smluvni_podminky_nazev`.`id_smluvni_podminky_nazev` = `s`.`id_sml_podm`) 
                    join `dokument` on (`dokument`.`id_dokument` = `smluvni_podminky_nazev`.`dokument_id`) 
                WHERE z.id_zajezd = :idZajezd;";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    public static function selectSerialByIdSQL($params)
    {
        $sql = "SELECT s.*, GROUP_CONCAT(DISTINCT o.nazev_objektu ORDER BY o.nazev_objektu SEPARATOR \";\") AS nazev_ubytovani
                FROM serial s
                    LEFT JOIN (objekt_serial os
                      JOIN objekt o ON (o.id_objektu = os.id_objektu)) ON (s.id_serial = os.id_serial)
                WHERE s.id_serial = :idSerial;";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    /**
     * @param $params []
     * @return SQLQuery
     */
    public static function selectZajezdAllByIdSQL($params)
    {
        $sql = "SELECT z.*, s.nazev AS nazev_serial, f.foto_url, f.nazev_foto, s.doprava, s.nazev_web, s.dlouhodobe_zajezdy, s.id_sablony_zobrazeni, ou.nazev_ubytovani, `dokument`.`dokument_url` as `smluvni_podminky`
                FROM zajezd z
                    JOIN serial s ON (z.id_serial = s.id_serial)                    
                    join `smluvni_podminky_nazev` on (`smluvni_podminky_nazev`.`id_smluvni_podminky_nazev` = `s`.`id_sml_podm`) 
                    join `dokument` on (`dokument`.`id_dokument` = `smluvni_podminky_nazev`.`dokument_id`) 
                    LEFT JOIN objekt_serial os ON (s.id_serial = os.id_serial)
                    LEFT JOIN objekt o ON (os.id_objektu = o.id_objektu)
                    LEFT JOIN objekt_ubytovani ou ON (o.id_objektu = ou.id_objektu)
                    LEFT JOIN foto_serial fs ON (s.id_serial = fs.id_serial)
                    LEFT JOIN foto f ON (fs.id_foto = f.id_foto)
                WHERE z.id_zajezd = :idZajezd && (fs.zakladni_foto = :zaklFotoAno || fs.zakladni_foto is NULL) && (
                    CASE
                        WHEN os.id_objektu IS NOT NULL && s.id_sablony_zobrazeni = " . self::ID_SABLONY_ZOBRAZENI_12 . "
                            THEN o.typ_objektu = :typObjektu1
                        ELSE 1
                    END);";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    /**
     * @param $params []
     * @return SQLQuery
     */
    public static function selectSluzbyByZajezdId($params)
    {
        $sql = "SELECT c.nazev_ceny, c.typ_ceny, c.use_pocet_noci, c.kapacita_bez_omezeni, cz.*,
                    SUM(okt.kapacita_volna + ( (1 - ok.prodavat_jako_celek) * ( ok.hlavni_kapacita - 1 ) * okt.kapacita_volna) ) AS objekt_kapacita_volna,
                    MAX(okt.kapacita_bez_omezeni) AS objekt_kapacita_bez_omezeni,
                    MIN(okt.vyprodano) AS objekt_vyprodano,
                    MIN(okt.na_dotaz) AS objekt_na_dotaz
                FROM zajezd z
                    LEFT JOIN serial s ON (z.id_serial = s.id_serial)
                    LEFT JOIN cena_zajezd cz ON (z.id_zajezd = cz.id_zajezd && cz.nezobrazovat != " . self::CENA_ZAJEZD_ZOBRAZOVAT_ANO . ")
                    LEFT JOIN cena c ON (c.id_cena = cz.id_cena)
                    LEFT JOIN (
                        cena_zajezd_tok czt
                                JOIN objekt_kategorie_termin okt ON (czt.id_termin = okt.id_termin && czt.id_objekt_kategorie = okt.id_objekt_kategorie)
                                JOIN objekt_kategorie ok ON (ok.id_objekt_kategorie = okt.id_objekt_kategorie)
                        ) ON (czt.id_zajezd = cz.id_zajezd &&  czt.id_cena = cz.id_cena)
                WHERE z.id_zajezd = :idZajezd && c.id_serial = s.id_serial
                GROUP BY cz.id_zajezd, cz.id_cena
                ORDER BY c.poradi_ceny;";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    /**
     * Nejaktualnejsi zajezd, serialu s danym nazev_web
     * @param $params []
     * @return SQLQuery
     */
    public static function selectZajezdBySerialNazevWebSQL($params)
    {
        $sql = "SELECT z.id_zajezd, s.id_serial
                FROM zajezd z
                    LEFT JOIN serial s ON (z.id_serial = s.id_serial)
                WHERE s.nazev_web = :nazevWeb and z.nezobrazovat_zajezd != 1
                ORDER BY z.od DESC;";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    public static function centralniDataByName($params)
    {
        $sql = "SELECT *
                FROM centralni_data
                WHERE nazev LIKE :nazev
                ORDER BY nazev";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    public static function readBlackdaysByZajezdIdSQL($params)
    {
        $sql = "SELECT *
                FROM zajezd_blackdays
                WHERE id_zajezd = :idZajezd";
        $query = new SQLQuery($sql, $params);

        return $query;
    }

    public static function selectSlevyKlientBySerialId($params)
    {
        $today = date("Y-m-d");
        $sql = "SELECT s.*
                FROM slevy s
                    LEFT JOIN slevy_serial ss ON (s.id_slevy = ss.id_slevy)
                WHERE (s.platnost_do = " . self::NO_DATE . " || s.platnost_do >= '$today') &&
                        s.platnost_od <= '$today' &&
                        ss.platnost = " . self::SLEVA_PLATNOST_ANO . " &&
                        ss.id_serial = :idSerial
                ORDER BY s.sleva_staly_klient DESC;";
        //echo $sql;
        //print_r( $params);
        
        $query = new SQLQuery($sql, $params);
        
        return $query;
    }

    public static function selectSlevyKlientByZajezdId($params)
    {
        $today = date("Y-m-d");
        $sql = "SELECT s.*
                FROM slevy s
                    LEFT JOIN slevy_zajezd sz ON (s.id_slevy = sz.id_slevy)
                WHERE (s.platnost_do = " . self::NO_DATE . " || s.platnost_do >= '$today')
                        && s.platnost_od <= '$today'
                        && sz.platnost = " . self::SLEVA_PLATNOST_ANO . "
                        && sz.id_zajezd = :idZajezd
                ORDER BY s.sleva_staly_klient DESC;";
        //echo $sql;
        //print_r( $params);
        
        $query = new SQLQuery($sql, $params);
        
        return $query;
    }

    /**
     * Vybere nejvetsi (pozor Kc jsou temer vzdy vetsi nez procenta) platnou casovou slevu ze souhrnu vsech klientskych slev zajezdu i serialu a odfiltruje ty co patri stalym klientum
     * @param $params
     * @return SQLQuery
     */
    public static function selectSlevyKlientNoStalyKlientByZajezdSerialId($params)
    {
        $today = date("Y-m-d");
        $sql = "SELECT s.*
                FROM slevy s
                    LEFT JOIN slevy_zajezd sz ON (s.id_slevy = sz.id_slevy && sz.id_zajezd = :idZajezd )
                    LEFT JOIN slevy_serial ss ON (s.id_slevy = ss.id_slevy && ss.id_serial = :idSerial )
                WHERE
                    (ss.platnost = " . self::SLEVA_PLATNOST_ANO . " or  sz.platnost = " . self::SLEVA_PLATNOST_ANO . " ) &&
                    (s.platnost_od = " . self::NO_DATE . " || s.platnost_od <= '$today') &&
                    (s.platnost_do = " . self::NO_DATE . " || s.platnost_do >= '$today') &&
                    s.sleva_staly_klient = " . self::SLEVA_STALY_KLIENT_NE . "
                ORDER BY s.castka DESC
                LIMIT 1;";
        $query = new SQLQuery($sql, $params);
        //print_r($params); echo $sql;       
        return $query;
    }


}