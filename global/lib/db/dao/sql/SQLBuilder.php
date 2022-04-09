<?php


class SQLBuilder
{

    public static function readFoundRows()
    {
        $sql = "SELECT FOUND_ROWS() cnt;";

        return new SQLQuery($sql, array());
    }

}