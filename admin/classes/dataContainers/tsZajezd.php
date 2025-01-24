<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsZajezd
 * (nazev_serialu,nazev_zajezdu, termin_od, termin_do)
 * @author lpeska
 */
class tsZajezd {

    const SABLONA_ZOBRAZENI_12 = 12;

    public $id;
    public $idSerial;
    public $nazevZajezdu;
    public $nazevSerialu;
    public $nazevUbytovani;
    public $terminOd;
    public $terminDo;
    public $cenaZahrnuje;

    /**
     * @var tsObjednavka[]
     */
    public $objednavky;

    /**
     * @var boolean
     */
    public $hasObjednavky;
    public $idSablonyZobrazeni;

    public function __construct($id, $idSerial, $nazevSerialu, $nazevUbytovani, $terminOd, $terminDo) {
        $this->id = $id;
        $this->idSerial = $idSerial;
        $this->nazevSerialu = $nazevSerialu;
        $this->nazevUbytovani = $nazevUbytovani;
        $this->terminDo = $terminDo;
        $this->terminOd = $terminOd;
    }

    public function isAktivni() {
        $now = date("Y-m-d");
        if($this->terminDo < $now) {
            return false;
        }
        return true;
    }

    public function constructNazev()
    {
        if ($this->idSablonyZobrazeni != self::SABLONA_ZOBRAZENI_12) {
            $nazev = $this->nazevSerialu;
        } else if ($this->nazevUbytovani != "") {
            $nazev = $this->nazevSerialu . " - " . $this->nazevUbytovani;
        } else {
            $nazev = $this->nazevSerialu;
        }

        return $nazev;
    }

    public function hasObjednavky()
    {
        $this->hasObjednavky = !is_null($this->objednavky);
        return $this->hasObjednavky;
    }
}

?>
