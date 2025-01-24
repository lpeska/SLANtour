<?php


class tsTypSerial {

    public $id;
    public $nazev;

    function __construct($id, $nazev)
    {
        $this->id = $id;
        $this->nazev = $nazev;
    }

}