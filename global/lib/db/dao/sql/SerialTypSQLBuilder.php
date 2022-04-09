<?php


class SerialTypSQLBuilder extends SQLBuilder {

    public static function readSerialTypListSQL()
    {
        $sql = "SELECT *
                FROM typ_serial;";
        $query = new SQLQuery($sql, array());

        return $query;
    }

}