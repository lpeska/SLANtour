<?php
/**
 * Description of tsObjednavka
 * obsahuje zakladni data z tabulek objekt, objekt_kategorie, objekt_kategorie_termin, objekt_ubytovani
 * @author Martin Jelinek
 */
class tsObjekt {

    public $id;
    public $nazev_objektu;
    public $poznamka;
    public $stat;
    public $mesto;
    public $ulice;
    public $psc;
    public $email;
    public $telefon;

    function __construct($id, $mesto, $nazev_objektu, $poznamka, $psc, $ulice, $stat, $email, $telefon)
    {
        $this->id = $id;
        $this->mesto = $mesto;
        $this->nazev_objektu = $nazev_objektu;
        $this->poznamka = $poznamka;
        $this->psc = $psc;
        $this->ulice = $ulice;
        $this->stat = $stat;
        $this->email = $email;
        $this->telefon = $telefon;
    }


}