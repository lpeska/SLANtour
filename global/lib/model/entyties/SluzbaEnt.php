<?php

class SluzbaEnt
{
    const TYP_SLUZBA = 1;
    const TYP_LAST_MINUTE = 2;
    const TYP_SLEVA = 3;
    const TYP_PRIPLATEK = 4;
    const TYP_ODJEZDOVE_MISTO = 5;
    const TYP_RUCNE_PRIDANA = 99;
    const KAPACITA_BEZ_OMEZENI_ANO = 1;
    const USE_POCET_NOCI_YES = 1;
    const NA_DOTAZ_YES = 1;
    const VYPRODANO_YES = 1;
    const TYP_PROVIZE_FIXNI = 1;
    const TYP_PROVIZE_PROCENTO = 2;

    public $id_cena;
    public $nazev_ceny;
    public $castka;
    public $pocet;
    public $use_pocet_noci;
    public $mena;
    public $typ;
    public $typ_provize;
    public $vyse_provize;

    private $na_dotaz;
    private $vyprodano;
    private $kapacita_volna;
    private $kapacita_bez_omezeni;
    private $objekt_kapacita_volna;
    private $objekt_kapacita_bez_omezeni;
    private $objekt_vyprodano;
    private $objekt_na_dotaz;

    private $_pocetStorno;

    /** @var  TerminObjektoveKategorieHolder */
    private $terminObjektoveKategorieHolder;

    public function __construct($id_cena, $nazev_ceny, $typ, $castka, $mena, $pocet, $use_pocet_noci, $na_dotaz, $vyprodano, $kapacita_volna, $kapacita_bez_omezeni, $objekt_kapacita_volna, $objekt_kapacita_bez_omezeni, $objekt_vyprodano, $objekt_na_dotaz)
    {
        $this->id_cena = $id_cena;
        $this->nazev_ceny = $nazev_ceny;
        $this->typ = $typ;
        $this->castka = $castka;
        $this->mena = $mena;
        //je treba aby pocet byl cislo
        $this->setPocet($pocet);
        $this->use_pocet_noci = $use_pocet_noci;
        $this->na_dotaz = $na_dotaz;
        $this->vyprodano = $vyprodano;
        $this->kapacita_volna = $kapacita_volna;
        $this->kapacita_bez_omezeni = $kapacita_bez_omezeni;
        $this->objekt_kapacita_volna = $objekt_kapacita_volna;
        $this->objekt_kapacita_bez_omezeni = $objekt_kapacita_bez_omezeni;
        $this->objekt_vyprodano = $objekt_vyprodano;
        $this->objekt_na_dotaz = $objekt_na_dotaz;
    }

    public function isNaDotaz()
    {
        return $this->na_dotaz == self::NA_DOTAZ_YES || $this->objekt_na_dotaz == self::NA_DOTAZ_YES;
    }
    public function isTypSluzba()
    {
        return $this->typ == self::TYP_SLUZBA;
    }
    public function isTypLastMinute()
    {
        return $this->na_dotaz == self::TYP_LAST_MINUTE;
    }    
    
    
    public function isVyprodana()
    {
        return $this->vyprodano == self::VYPRODANO_YES || $this->objekt_vyprodano == self::VYPRODANO_YES;
    }

    public function isPlnaKapacita($pocetOsob)
    {
        if (is_null($this->kapacita_volna) && is_null($this->objekt_kapacita_volna)) {
            return false;
        }

        if ($this->kapacita_bez_omezeni == self::KAPACITA_BEZ_OMEZENI_ANO || $this->objekt_kapacita_bez_omezeni == self::KAPACITA_BEZ_OMEZENI_ANO) {
            return false;
        }

        return !($this->kapacita_volna >= $pocetOsob || $this->objekt_kapacita_volna >= $pocetOsob);
    }

    public function calcCastkaFull($pocetOsob, $pocetNoci, $isEur = false, $kurzEur = null)
    {
        if ($isEur) {
            $castka = round($this->castka / $kurzEur);
        } else {
            $castka = $this->castka;
        }

        //pokud je pocet noci 0 nebo se sluzba NEnasobi poctem noci, zmen pocet noci na 1
        $pocetNoci = $pocetNoci == 0 || $this->use_pocet_noci != self::USE_POCET_NOCI_YES ? 1 : $pocetNoci;

        return $castka * $pocetOsob * $pocetNoci;
    }

    /**
     * @param $procento int procento, odpovidajici storno podminkam serialu dane sluzby
     * @return float|int
     */
    public function calcStornoPoplatek($procento)
    {
        if(is_null($procento))
            return 0;

        return round($this->castka * $procento / 100);
    }

    /**
     * @param mixed $pocet
     */
    public function setPocet($pocet)
    {
        $this->pocet = is_null($pocet) || $pocet == '' ? 0 : $pocet;
    }

    /**
     * @return mixed
     */
    public function getPocetStorno()
    {
        return $this->_pocetStorno;
    }

    /**
     * @param mixed $_pocetStorno
     */
    public function setPocetStorno($_pocetStorno)
    {
        $this->_pocetStorno = is_null($_pocetStorno) || $_pocetStorno == '' ? 0 : $_pocetStorno;
    }

    public function setTerminyObektoveKategorie($terminyObjektoveKategorie)
    {
        $this->terminObjektoveKategorieHolder = new TerminObjektoveKategorieHolder($terminyObjektoveKategorie);
    }

    /**
     * @return TerminObjektoveKategorieHolder
     */
    public function getTerminyObektoveKategorieHolder()
    {
        return $this->terminObjektoveKategorieHolder;
    }

}
