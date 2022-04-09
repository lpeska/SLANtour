<?php


class SerialEnt
{
    const SABLONA_ZOBRAZENI_12 = 12;
    const ZAKLADNI_FOTO_ANO = 1;
    const TYP_PROVIZE_FIXNI_OSOBA = 1;
    const TYP_PROVIZE_PROCENTO_Z_OBJEDNAVKY = 2;
    const TYP_PROVIZE_DLE_SLUZEB = 3;

    public $id;
    public $nazev;
    public $typ_dopravy;
    public $id_sablony_zobrazeni;
    /** @var string URL smluvnich podminek */
    public $smluvni_podminky;
    public $dlouhodobe_zajezdy;
    public $typ_provize;
    public $vyse_provize;

    public $nazev_web;
    public $hasZajezd;

    /** @var SerialTypEnt */
    public $typ;

    /** @var ObjektEnt[] */
    public $objekty;

    /** @var  FotoEnt */
    public $mainFoto;

    /** @var  FotoEnt[] */
    public $fotky;

    /** @var  SmluvniPodminkyNazevEnt */
    public $smluvniPodminkyNazev;

    /** @var  ZajezdHolder */
    private $zajezdHolder;

    function __construct($id, $nazev, $id_sablony_zobrazeni, $smluvni_podminky="", $typ_dopravy = "")
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->typ_dopravy = $typ_dopravy;
        $this->id_sablony_zobrazeni = $id_sablony_zobrazeni;
        $this->smluvni_podminky = $smluvni_podminky;
    }

    //note tomuhle porat prense nerozumim - uz sem to menil asi 4x
    public function constructNazev()
    {
        $nazevUbytovani = is_null($this->objekty) ? "" : $this->objekty[0]->nazev;
        if ($nazevUbytovani != "") {            //note && $this->id_sablony_zobrazeni == self::SABLONA_ZOBRAZENI_12
            return $nazevUbytovani . ", " . $this->nazev;
        }

        return $this->nazev;
    }

    public function hasAktivniZajezd()
    {
        $zajezdy = $this->zajezdHolder->getZajezdy();
        if (!is_null($zajezdy)) {
            foreach ($zajezdy as $zajezd) {
                if ($zajezd->isAktivni())
                    return true;
            }
        }
        return false;
    }

    public function hasZajezd()
    {
        if (!is_null($this->zajezdHolder->getZajezdy()))
            return true;
        return $this->hasZajezd;
    }

    public function setZajezdy($zajezdy)
    {
        $this->zajezdHolder = new ZajezdHolder($zajezdy);
    }

    /**
     * @return ZajezdHolder
     */
    public function getZajezdHolder()
    {
        return $this->zajezdHolder;
    }

    /**
     * @return SmluvniPodminkyNazevEnt
     */
    public function getSmluvniPodminkyNazev()
    {
        return $this->smluvniPodminkyNazev;
    }
}