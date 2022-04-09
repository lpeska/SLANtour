<?php

class AFakturaProvizeSQLBuilder
{

    const FAKTURY_PROVIZE_ZAPLACENO_ANO = 1;

    /**
     * @param PagingFilter $filter
     * @return SQLQuery
     */
    public static function readFakturyProvizeSQL($filter)
    {
        $offset = ($filter->getPagingPage() - 1) * $filter->pagingMaxPerPage;

        $sql = "SELECT SQL_CALC_FOUND_ROWS fp.*, o.*, uk.*, org.*, s.nazev AS s_nazev
                FROM faktury_provize fp
                  LEFT JOIN objednavka o ON (fp.id_objednavka = o.id_objednavka)
                  LEFT JOIN organizace org ON (o.id_agentury = org.id_organizace)
                  LEFT JOIN user_klient uk ON (o.id_klient = uk.id_klient)
                  LEFT JOIN serial s ON (o.id_serial = s.id_serial)
                LIMIT ?, ?;";

        return new SQLQuery($sql, [$offset, $filter->pagingMaxPerPage]);
    }

    public static function payFakturaByIdSQL($params)
    {
        $sql = "UPDATE faktury_provize
                SET zaplaceno = " . self::FAKTURY_PROVIZE_ZAPLACENO_ANO . "
                WHERE id_faktury = ?;";
        return new SQLQuery($sql, $params);
    }
}