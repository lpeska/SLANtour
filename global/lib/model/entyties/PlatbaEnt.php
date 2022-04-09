<?php

class PlatbaEnt
{
    const PLATBA_VYDAJOVY_DOKLAD = "vydajovy";
    
    public $id;
    public $cislo_dokladu;
    public $castka;
    public $vystaveno;
    public $splatit_do;
    public $splaceno;
    public $typ_dokladu;
    public $zpusob_uhrady;

    function __construct($id, $cislo_dokladu, $castka, $vystaveno, $splatit_do, $splaceno, $typ_dokladu, $zpusob_uhrady)
    {
        $this->id = $id;
        $this->cislo_dokladu = $cislo_dokladu;
        $this->castka = $castka;
        $this->vystaveno = $vystaveno == '0000-00-00' ? null : $vystaveno;
        $this->splatit_do = $splatit_do == '0000-00-00' ? null : $splatit_do;
        $this->splaceno = $splaceno == '0000-00-00' ? null : $splaceno;
        $this->typ_dokladu = $typ_dokladu;
        $this->zpusob_uhrady = $zpusob_uhrady;
    }

}