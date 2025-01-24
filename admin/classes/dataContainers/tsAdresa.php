<?php


class tsAdresa {

    public $stat;
    public $mesto;
    public $psc;
    public $ulice;
    public $cp;

    function __construct($cp, $mesto, $psc, $stat, $ulice)
    {
        $this->cp = $cp;
        $this->mesto = $mesto;
        $this->psc = $psc;
        $this->stat = $stat;
        $this->ulice = $ulice;
    }


}