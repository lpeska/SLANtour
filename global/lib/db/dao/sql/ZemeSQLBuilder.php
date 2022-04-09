<?php


class ZemeSQLBuilder extends SQLBuilder
{

    public static function readZemeListSQL()
    {
        $sql = "SELECT id_zeme, nazev_zeme
                FROM zeme;";
        $query = new SQLQuery($sql, array());

        return $query;
    }

}