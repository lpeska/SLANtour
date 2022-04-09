<?php


class BankovniSpojeniEnt
{

    public $nazev_banky;
    public $kod_banky;
    public $cislo_uctu;
    
    public $typ_kontaktu;

    function __construct($nazev_banky, $kod_banky, $cislo_uctu, $typ_kontaktu=1)
    {
        $this->nazev_banky = $nazev_banky;
        $this->kod_banky = $kod_banky;
        $this->cislo_uctu = $cislo_uctu;
        $this->typ_kontaktu = $typ_kontaktu;
    }

}