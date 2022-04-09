<?php


class SerialTypDAO {

    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    /**
     * @return SerialTypEnt[]|null
     */
    public static function readSerialTypList()
    {
        $serialTypList = null;

        self::$db->connect();

        $stmt = self::$db->query(SerialTypSQLBuilder::readSerialTypListSQL());
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $serialTypList[] = new SerialTypEnt($row->id_typ, $row->nazev_typ);
        }

        self::$db->close();

        return $serialTypList;
    }
    
}