<?php


class SQLQuery {
    /**
     * @var string prepared sql query (to znamena s otazniky misto parametru)
     */
    public $sql;

    /**
     * @var mixed[] parametry dotazu
     */
    public $params;

    function __construct($sql, $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }


}