<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsObjednavka
 * obsahuje zakladni data z tabulky objednavka
 * @author lpeska
 */
class tsObjednavka {

    //put your code here
    public $id_objednavka;
    public $pocet_osob;
    public $pocet_noci;
    public $celkova_cena;
    public $zbyva_zaplatit;
    public $poznamky;
    public $datum_rezervace;
    public $stav;
    public $storno_datum;
    public $storno_poplatek;

    public $nastupni_mista;
    /**
     * @var tsObjednavajici
     */
    public $objednavajici;
    /**
     * @var tsOrganizace
     */
    public $objednavajiciOrg;
    /**
     * @var tsProdejce
     */
    public $prodejce;
    /**
     * @var tsOsoba[]
     */
    public $ucastnici;
    public $termin_od;
    public $termin_do;

    public $k_uhrade_celkem;
    public $k_uhrade_zaloha;
    public $k_uhrade_doplatek;
    public $k_uhrade_celkem_datspl;
    public $k_uhrade_zaloha_datspl;
    public $k_uhrade_doplatek_datspl;

    /**
     * @var tsSluzba[]
     */
    public $sluzby;

    public function __construct($id_objednavka, $pocet_osob, $celkova_cena, $zbyva_zaplatit, $poznamky, $pocet_noci, $datum_rezervace, $stav, $storno_datum, $storno_poplatek) {
        $this->id_objednavka = $id_objednavka;
        $this->pocet_osob = $pocet_osob;
        $this->pocet_noci = $pocet_noci;
        $this->celkova_cena = $celkova_cena;
        $this->zbyva_zaplatit = $zbyva_zaplatit;
        $this->poznamky = $poznamky;
        $this->datum_rezervace = $datum_rezervace;
        $this->stav = $stav;
        $this->storno_datum = $storno_datum;
        $this->storno_poplatek = $storno_poplatek;
    }

}

?>
