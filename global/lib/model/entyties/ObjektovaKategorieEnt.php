<?php

class ObjektovaKategorieEnt
{

    const PRODAVAT_JAKO_CELEK_ANO = 1;

    public $id;
    public $nazev;
    public $zakladni_kategorie;
    public $hlavni_kapacita;
    public $vedlejsi_kapacita;
    public $prodavat_jako_celek;

    /**
     * ObjektovaKategorieEnt constructor.
     * @param $id
     * @param $nazev
     * @param $zakladni_kategorie
     * @param $hlavni_kapacita
     * @param $vedlejsi_kapacita
     * @param $prodavat_jako_celek
     */
    public function __construct($id, $nazev, $zakladni_kategorie, $hlavni_kapacita, $vedlejsi_kapacita, $prodavat_jako_celek)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->zakladni_kategorie = $zakladni_kategorie;
        $this->hlavni_kapacita = $hlavni_kapacita;
        $this->vedlejsi_kapacita = $vedlejsi_kapacita;
        $this->prodavat_jako_celek = $prodavat_jako_celek;
    }
}