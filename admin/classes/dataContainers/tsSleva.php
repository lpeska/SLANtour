<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsSleva
 *
 * @author Pesi
 */
class tsSleva {

    public $id_slevy;
    public $sleva_staly_klient;
    public $nazev_slevy;
    public $castka_slevy; //vysledna sleva
    public $velikost_slevy; // jednotkova cena
    public $mena_slevy; //procenta nebo kè
    

    public function __construct($nazev_slevy, $castka_slevy, $velikost_slevy, $mena_slevy, $id_slevy = "", $sleva_staly_klient = "") {
        $this->id_slevy = $id_slevy;
        $this->sleva_staly_klient = $sleva_staly_klient;
        $this->nazev_slevy = $nazev_slevy;
        $this->castka_slevy = $castka_slevy;
        $this->velikost_slevy = $velikost_slevy;
        $this->mena_slevy = $mena_slevy;
    }

}

?>
