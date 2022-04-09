<?php


class SerialDAO
{

    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    /**
     * @return tsSerial[]|null
     */
    public static function readSerialById($id_serial)
    {
        $serial = null;
        $zajezdy = array();
        $foto = array();

        self::$db->connect();

        $stmt = self::$db->query(SerialSQLBuilder::readSerialById($id_serial));
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $serial = new tsSerial($row->id_serial, $row->id_typ, $row->nazev);
            $serial->dataSerial = $row;
            $stmt2 = self::$db->query(SerialSQLBuilder::readFotoByIdSerial($id_serial));
            while (@$row2 = $stmt2->fetch(PDO::FETCH_OBJ)) {
                $foto[] = new tsFoto($row2->id_foto, $row2->nazev_foto, $row2->foto_url);
            }
            $serial->foto = $foto;
            $stmt2 = self::$db->query(SerialSQLBuilder::readZajezdyByIdSerial($id_serial));
            while (@$row2 = $stmt2->fetch(PDO::FETCH_OBJ)) {
                $zajezd = new tsZajezd($row2->id_zajezd, $id_serial, null, null, $row2->od, $row2->do);
                $zajezd->nazevZajezdu = $row2->nazev_zajezdu;
                $sluzby = array();
                $slevy = array();
                $stmt3 = self::$db->query(SerialSQLBuilder::readZajezdSluzby($row2->id_zajezd));
                while (@$row3 = $stmt3->fetch(PDO::FETCH_OBJ)) {
                    $sluzba =  new tsSluzba($row3->id_cena, $row3->nazev_ceny, $row3->castka, $row3->mena, null, $row3->use_pocet_noci);
                    $sluzba->typ = $row3->typ_ceny;
                    $sluzba->zakladni = $row3->zakladni_cena;
                    $sluzby[] = $sluzba;
                }
                $stmt4 = self::$db->query(SerialSQLBuilder::readZajezdSlevy($row2->id_zajezd));
                while (@$row4 = $stmt4->fetch(PDO::FETCH_OBJ)) {                    
                    $slevy[] = new tsSleva($row4->nazev_slevy, null, $row4->castka, $row4->mena, $row4->id_slevy, $row4->sleva_staly_klient);
                }       
                $zajezd->sluzby = $sluzby;
                $zajezd->slevy = $slevy;
                $zajezdy[] = $zajezd;
            }
            $serial->zajezdy = $zajezdy;
        }

        self::$db->close();

        return $serial;
    }

}