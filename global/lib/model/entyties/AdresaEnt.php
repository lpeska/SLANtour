<?php


class AdresaEnt
{

    public $id;
    public $stat;
    public $mesto;
    public $psc;
    public $ulice;
    public $lng;
    public $lat;
    public $typ_kontaktu;
    public $poznamka;

    function __construct($id, $stat, $mesto, $psc, $ulice, $lng, $lat, $typ_kontaktu, $poznamka)
    {
        $this->id = $id;
        $this->stat = $stat;
        $this->mesto = $mesto;
        $this->psc = $psc;
        $this->ulice = $ulice;
        $this->lng = $lng;
        $this->lat = $lat;
        $this->typ_kontaktu = $typ_kontaktu;
        $this->poznamka = $poznamka;
    }


}