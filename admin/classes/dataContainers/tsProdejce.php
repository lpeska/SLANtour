<?php

class tsProdejce {
    public $nazev;
    public $kontaktni_osoba;
    public $ico;
    public $adresa_ulice;
    public $adresa_mesto;
    public $adresa_psc;
    public $adresa_zeme;
    public $email;
    public $telefon;

    public $id;
    
    public function __construct($nazev,$kontaktni_osoba,$ico,$ulice,$mesto,$psc,$zeme,$email,$telefon){
        $this->nazev = $nazev;
        $this->kontaktni_osoba = $kontaktni_osoba;
        $this->ico = $ico;
        $this->adresa_ulice = $ulice;
        $this->adresa_mesto = $mesto;
        $this->adresa_psc = $psc;
        $this->adresa_zeme = $zeme;
        $this->email = $email;
        $this->telefon = $telefon;
    }
}

?>
