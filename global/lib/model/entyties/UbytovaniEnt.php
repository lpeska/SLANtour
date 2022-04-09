<?php


class UbytovaniEnt {

    public $id;
    public $nazev;
    public $nazev_web;
    public $kategorie;
    public $popisek;
    public $popis;
    public $zamereni_lazni;
    public $highlights;
    public $pes;
    public $pes_cena;

    function __construct($id, $nazev, $nazev_web, $kategorie, $popisek, $popis, $zamereni_lazni, $highlights, $pes, $pes_cena)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->nazev_web = $nazev_web;
        $this->kategorie = $kategorie;
        $this->popisek = $popisek;
        $this->popis = $popis;
        $this->zamereni_lazni = $zamereni_lazni;
        $this->highlights = $highlights;
        $this->pes = $pes;
        $this->pes_cena = $pes_cena;
    }

}