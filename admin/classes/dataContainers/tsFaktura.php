<?php


class tsFaktura
{

    public $id_faktury;
    public $id_objednavka;
    public $cislo_faktury;
    public $typ_dokladu;
    public $typ_faktury;
    public $cislo_faktury_vystavovatele;
    public $mena;
    public $kurz;
    public $celkova_castka;
    public $text_faktury;
    public $datum_vystaveni;
    public $datum_splatnosti;
    public $datum_zdanitelneho_plneni;
    public $zpusob_uhrady;
    public $zaplaceno;
    public $dph_snizene;
    public $dph_zakladni;
    public $dodavatel_text;
    public $prijemce_text;
    public $pata_faktury;


    function __construct($id_faktury,
                         $id_objednavka,
                         $cislo_faktury,
                         $typ_dokladu,
                         $typ_faktury,
                         $cislo_faktury_vystavovatele,
                         $mena,
                         $kurz,
                         $celkova_castka,
                         $text_faktury,
                         $datum_vystaveni,
                         $datum_splatnosti,
                         $datum_zdanitelneho_plneni,
                         $zpusob_uhrady,
                         $zaplaceno,
                         $dph_snizene,
                         $dph_zakladni,
                         $dodavatel_text,
                         $prijemce_text,
                         $pata_faktury)
    {
        $this->id_faktury = $id_faktury;
        $this->id_objednavka = $id_objednavka;
        $this->cislo_faktury = $cislo_faktury;
        $this->typ_dokladu = $typ_dokladu;
        $this->typ_faktury = $typ_faktury;
        $this->cislo_faktury_vystavovatele = $cislo_faktury_vystavovatele;
        $this->mena = $mena;
        $this->kurz = $kurz;
        $this->celkova_castka = $celkova_castka;
        $this->text_faktury = $text_faktury;
        $this->datum_vystaveni = $datum_vystaveni;
        $this->datum_splatnosti = $datum_splatnosti;
        $this->datum_zdanitelneho_plneni = $datum_zdanitelneho_plneni;
        $this->zpusob_uhrady = $zpusob_uhrady;
        $this->zaplaceno = $zaplaceno;
        $this->dph_snizene = $dph_snizene;
        $this->dph_zakladni = $dph_zakladni;
        $this->dodavatel_text = $dodavatel_text;
        $this->prijemce_text = $prijemce_text;
        $this->pata_faktury = $pata_faktury;
    }


}