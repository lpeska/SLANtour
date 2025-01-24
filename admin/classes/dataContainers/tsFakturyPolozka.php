<?php


class tsFakturyPolozka
{

    public $id_polozky;
    public $id_faktury;
    public $nazev_polozky;
    public $jednotkova_cena;
    public $pocet;
    public $pocitat_dph;
    public $celkem;


    function __construct($id_polozky,
                         $id_faktury,
                         $nazev_polozky,
                         $jednotkova_cena,
                         $pocet,
                         $pocitat_dph,
                         $celkem
    )
    {
        $this->id_polozky = $id_polozky;
        $this->id_faktury = $id_faktury;
        $this->nazev_polozky = $nazev_polozky;
        $this->jednotkova_cena = $jednotkova_cena;
        $this->pocet = $pocet;
        $this->pocitat_dph = $pocitat_dph;
        $this->celkem = $celkem;
    }


}