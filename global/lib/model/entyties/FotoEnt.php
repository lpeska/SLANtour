<?php


class FotoEnt {

    public $id;
    public $nazev;
    public $url;
    public $popisek;

    function __construct($id, $url, $nazev, $popisek)
    {
        $this->id = $id;
        $this->url = $url;
        $this->nazev = $nazev;
        $this->popisek = $popisek;
    }

}