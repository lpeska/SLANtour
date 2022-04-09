<?php

class TerminObjektoveKategorieEnt
{
    public $id;
    public $nazev;
    public $cena;
    public $terminOd;
    public $terminDo;
    public $kapacitaCelkova;
    public $kapacitaVolna;
    public $naDotaz;
    public $vyprodano;
    public $kapacitaBezOmezeni;

    /** @var  ObjektovaKategorieEnt */
    private $objektovaKategorie;

    /**
     * TerminObjektoveKategorieEnt constructor.
     * @param $id
     * @param $nazev
     * @param $cena
     * @param $terminOd
     * @param $terminDo
     * @param $kapacitaCelkova
     * @param $kapacitaVolna
     * @param $naDotaz
     * @param $vyprodano
     * @param $kapacitaBezOmezeni
     */
    public function __construct($id, $nazev, $cena, $terminOd, $terminDo, $kapacitaCelkova, $kapacitaVolna, $naDotaz, $vyprodano, $kapacitaBezOmezeni)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->cena = $cena;
        $this->terminOd = $terminOd;
        $this->terminDo = $terminDo;
        $this->kapacitaCelkova = $kapacitaCelkova;
        $this->kapacitaVolna = $kapacitaVolna;
        $this->naDotaz = $naDotaz;
        $this->vyprodano = $vyprodano;
        $this->kapacitaBezOmezeni = $kapacitaBezOmezeni;
    }

    /**
     * @return ObjektovaKategorieEnt
     */
    public function getObjektovaKategorie()
    {
        return $this->objektovaKategorie;
    }

    /**
     * @param ObjektovaKategorieEnt $objektovaKategorie
     */
    public function setObjektovaKategorie($objektovaKategorie)
    {
        $this->objektovaKategorie = $objektovaKategorie;
    }
}