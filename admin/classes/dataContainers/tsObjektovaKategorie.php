<?php

class tsObjektovaKategorie
{
    public $id;
    public $nazev;
    public $kratkyNazev;

    function __construct($id, $nazev, $kratkyNazev)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->kratkyNazev = $kratkyNazev;
    }

}