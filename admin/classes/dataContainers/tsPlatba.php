<?php

/**
 * Obsahuje zakladni data z tabulky objednavka
 * @author lpeska
 */
class tsPlatba {

    public $id_platba;

    public $cislo_dokladu;
    public $zpusob_uhrady;
    public $castka;
    public $vystaveno;
    public $splatit_do;
    public $splaceno;

    public $typ_dokladu;

    public function __construct($cislo_dokladu, $zpusob_uhrady, $castka, $vystaveno, $splatit_do, $splaceno)
    {
        $this->cislo_dokladu = $cislo_dokladu;
        $this->zpusob_uhrady = $zpusob_uhrady;
        $this->castka = $castka;
        $this->vystaveno = $vystaveno;
        $this->splatit_do = $splatit_do;
        $this->splaceno = $splaceno;
    }
}

?>
