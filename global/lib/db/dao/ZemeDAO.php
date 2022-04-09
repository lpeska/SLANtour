<?php


class ZemeDAO
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
     * @return ZemeEnt[]|null
     */
    public static function readZemeList()
    {
        $zeme = null;

        self::$db->connect();

        $stmt = self::$db->query(ZemeSQLBuilder::readZemeListSQL());
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $zeme[] = new ZemeEnt($row->id_zeme, $row->nazev_zeme);
        }

        self::$db->close();

        return $zeme;
    }

}