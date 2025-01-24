<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsObjednavajici
 * informace o objednavateli (prozatim pouze jako osoba, az bude inteligentneji vyreseny organizace upravime to)
 * @author lpeska
 */
class tsObjednavajici {

    //put your code here
    public $id;
    public $titul;
    public $jmeno;
    public $prijmeni;
    public $adresa_ulice;
    public $adresa_mesto;
    public $adresa_psc;
    public $adresa_zeme;
    public $email;
    public $telefon;
    public $storno;

    public function __construct($id, $titul, $jmeno, $prijmeni, $ulice, $mesto, $psc, $zeme, $email, $telefon, $datum_narozeni="") {
        $this->id = $id;
        $this->titul = $titul;
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->adresa_ulice = $ulice;
        $this->adresa_mesto = $mesto;
        $this->adresa_psc = $psc;
        $this->adresa_zeme = $zeme;
        $this->email = $email;
        $this->telefon = $telefon;
        $this->datum_narozeni = $datum_narozeni;

    }

}

?>
