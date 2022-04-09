<?php

/**
 * Class DBResult
 * Slouzi jen jako "prepravka" na 2 navratove hodnoty z funkce, ktera vraci databazova data a zaroven pocet nalezenych radku
 */
class DBResult {
    public $object;
    public $foundRows;

    function __construct($object, $foundRows)
    {
        $this->object = $object;
        $this->foundRows = $foundRows;
    }
}