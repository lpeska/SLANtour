<?php

class FakturaEnt
{

    const FAKTURA_ZAPLACENO_NE = 0;
    const FAKTURA_ZAPLACENO_ANO = 1;
    const FAKTURA_MENA_CZK = "Kè";

    public $id;
    public $cislo_faktury;
    public $mena;
    public $celkova_castka;
    public $datum_vystaveni;
    public $datum_splatnosti;
    public $zaplaceno;

    public $pdfFilename;

    public $poznamka;
    /** @var  ObjednavkaEnt */
    public $objednavka;

    public $prijemce_text;

    function __construct($id, $cislo_faktury, $mena, $celkova_castka, $datum_vystaveni, $datum_splatnosti, $zaplaceno)
    {
        $this->id = $id;
        $this->cislo_faktury = $cislo_faktury;
        $this->mena = $mena;
        $this->celkova_castka = $celkova_castka;
        $this->datum_vystaveni = $datum_vystaveni;
        $this->datum_splatnosti = $datum_splatnosti;
        $this->zaplaceno = $zaplaceno;
    }

    public static function GET_ALL_MENY()
    {
        return [self::FAKTURA_MENA_CZK];
    }
}