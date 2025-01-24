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
class tsSluzba {

    //put your code here
    public $id_cena;
    public $nazev_ceny;
    public $castka;
    public $pocet;
    public $use_pocet_noci;
    public $mena;
    public $typ;
    public $zakladni;
    /**
     * @var tsObjektovaKategorie[]
     */
    public $objektoveKategorie;
    /**
     * Slouzi k ulozeni vybrane OK, pokud lze OK vybrat
     * @var tsObjektovaKategorie
     */
    public $vybranaObjektovaKategorie = null;
    /**
     * Slouzi k ulozeni vybranych ucastniku, pokud lze ucastniky vybrat
     * @var tsOsoba[]
     */
    public $vybraniUcastnici = null;

    const TYP_NASTUPNI_MISTO = 5;
    const KAPACITA_BEZ_OMEZENI_ANO = 1;

    public function __construct($id_cena, $nazev_ceny, $castka, $mena, $pocet, $use_pocet_noci) {
        $this->id_cena = $id_cena;
        $this->nazev_ceny = $nazev_ceny;
        $this->castka = $castka;
        $this->mena = $mena;
        $this->pocet = $pocet;
        $this->use_pocet_noci = $use_pocet_noci;
    }

    public function getNazevTyp(){
        switch ($this->typ) {
            case 1:
                return "Služby";
                break;
            case 2:
                return "Last Minute";
                break;
            case 3:
                return "Slevy";
                break;
            case 4:
                return "Pøíplatky";
                break;
            case 5:
                return "Odjezdová místa";
                break;
            

        }
    }
}

?>
