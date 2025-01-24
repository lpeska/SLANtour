<?php
/**
 * Description of tsObjednavka
 * obsahuje zakladni data z tabulek topologie, TOK, topologie_tok
 * @author Martin Jelinek
 */
class tsTopologie {

    public $id; //id_tok_topologie
    public $nazev; //nazev zasedaciho poradku
    public $poznamka;
    public $zobrazit_id_klient;
    public $zobrazit_id_objednavka;
    public $zobrazit_nazev;
    public $zobrazit_odjezd;

    function __construct($id, $nazev, $poznamka, $zobrazit_id_klient, $zobrazit_id_objednavka, $zobrazit_nazev, $zobrazit_odjezd)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->poznamka = $poznamka;
        $this->zobrazit_id_klient = $zobrazit_id_klient;
        $this->zobrazit_id_objednavka = $zobrazit_id_objednavka;
        $this->zobrazit_nazev = $zobrazit_nazev;
        $this->zobrazit_odjezd = $zobrazit_odjezd;
    }


}