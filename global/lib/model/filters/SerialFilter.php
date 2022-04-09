<?php

class SerialFilter extends AbstractFilter
{

    //region CONSTANTS *******************************************************************
    const MODE_SELECTED_SERIAL = 1;
    const MODE_FILTERED_SERIAL = 2;

    const VOLBA_ZASKRTNUTA = true;
    //endregion

    //region PRIVATE MEMBERS *************************************************************

    private $serialIds;
    private $serialNazev;
    private $serialZeme;
    private $serialTyp;
    private $serialIdsSelected;

    private $serialNoZajezd = SerialFilter::VOLBA_ZASKRTNUTA;
    private $serialNoAktivniZajezd = SerialFilter::VOLBA_ZASKRTNUTA;
    private $serialAktivniZajezd = SerialFilter::VOLBA_ZASKRTNUTA;

    private $zajezdNoObjednavka = SerialFilter::VOLBA_ZASKRTNUTA;
    private $zajezdObjednavka = SerialFilter::VOLBA_ZASKRTNUTA;

    private $zajezdNovejsiNez;
    private $zajezdSkrytProsle = SerialFilter::VOLBA_ZASKRTNUTA;
    private $zajezdIdsSelected;
    private $zajezdOd;
    private $zajezdDo;
    private $objednavkaOd;
    private $objednavkaDo;
    private $realizaceOd;
    private $realizaceDo;
    

    private $ucastniciIdsSelected;

    /**
     * @var ZajezdSetup
     */
    private $zajezdSetup;
    /**
     * @var ObjednavkaSetup
     */
    private $objednavkaSetup;

    //endregion

    private function __construct($pagingMaxPages = null, $pagingMaxPerPage = null)
    {
        if ($pagingMaxPages == null)
            $this->pagingMaxPages = self::$PAGING_MAX_PAGES;
        else
            $this->pagingMaxPages = $pagingMaxPages;

        if ($pagingMaxPerPage == null)
            $this->pagingMaxPerPage = self::$PAGING_MAX_PER_PAGE;
        else
            $this->pagingMaxPerPage = $pagingMaxPerPage;
    }

    //region GETTERS *********************************************************************

    /**
     * @return string[]
     */
    public function getSerialIds()
    {
        return $this->serialIds;
    }

    /**
     * @return string
     */
    public function getSerialIdsAsString()
    {
        return $this->parseMultipleIdsAsString($this->serialIds);
    }

    /**
     * @return string
     */
    public function getSerialNazev()
    {
        return $this->serialNazev;
    }

    /**
     * @return mixed
     */
    public function getZajezdNovejsiNez()
    {
        return $this->zajezdNovejsiNez;
    }

    /**
     * @return mixed
     */
    public function getZajezdSkrytProsle()
    {
        return $this->zajezdSkrytProsle;
    }

    /**
     * @return mixed
     */
    public function getZajezdIdsSelected()
    {
        return $this->zajezdIdsSelected;
    }

    /**
     * @return string
     */
    public function getZajezdIdsAsString()
    {
        return $this->parseMultipleIdsAsString("zajezdIds");
    }

    /**
     * @return mixed
     */
    public function getUcastniciIdsSelected()
    {
        return $this->ucastniciIdsSelected;
    }
    /**
     * @return mixed
     */
    public function getObjednavkyIdsSelected()
    {
        return $this->objednavkyIdsSelected;
    }

    /**
     * @return ObjednavkaSetup
     */
    public function getObjednavkaSetup()
    {
        return $this->objednavkaSetup;
    }

    /**
     * @return ZajezdSetup
     */
    public function getZajezdSetup()
    {
        return $this->zajezdSetup;
    }

    /**
     * @return mixed
     */
    public function getSerialZeme()
    {
        return $this->serialZeme;
    }

    /**
     * @return mixed
     */
    public function getSerialIdsSelected()
    {
        return $this->serialIdsSelected;
    }

    /**
     * @return string
     */
    public function getSerialTyp()
    {
        return $this->serialTyp;
    }

    /**
     * @return mixed
     */
    public function getSerialAktivniZajezd()
    {
        return $this->serialAktivniZajezd;
    }

    /**
     * @return mixed
     */
    public function getSerialNoAktivniZajezd()
    {
        return $this->serialNoAktivniZajezd;
    }

    /**
     * @return mixed
     */
    public function getSerialNoZajezd()
    {
        return $this->serialNoZajezd;
    }

    /**
     * @return mixed
     */
    public function getZajezdNoObjednavka()
    {
        return $this->zajezdNoObjednavka;
    }

    /**
     * @return mixed
     */
    public function getZajezdObjednavka()
    {
        return $this->zajezdObjednavka;
    }

    /**
     * @return mixed
     */
    public function getZajezdOd()
    {
        return $this->zajezdOd;
    }

    /**
     * @return mixed
     */
    public function getZajezdDo()
    {
        return $this->zajezdDo;
    }

    /**
     * @return mixed
     */
    public function getObjednavkaOd()
    {
        return $this->objednavkaOd;
    }

    /**
     * @return mixed
     */
    public function getObjednavkaDo()
    {
        return $this->objednavkaDo;
    }
    
    /**
     * @return mixed
     */
    public function getRealizaceOd()
    {
        return $this->realizaceOd;
    }

    /**
     * @return mixed
     */
    public function getRealizaceDo()
    {
        return $this->realizaceDo;
    }
    //endregion

    //region SETTERS *********************************************************************

    /**
     * @param mixed $serialIdsSelected
     */
    public function setSerialIdsSelected($serialIdsSelected)
    {
        if (isset($serialIdsSelected))
            $this->serialIdsSelected = $this->parseMultipleIds($serialIdsSelected);
    }

    /**
     * @param mixed $serialZeme
     */
    public function setSerialZeme($serialZeme)
    {
        if (isset($serialZeme))
            $this->serialZeme = $serialZeme;
    }

    public function setZajezdSetup()
    {
        $zajezdSetup = new ZajezdSetup();
        $zajezdSetup->titul = isset($_REQUEST["cb-f-zaj-titul"]) ? true : false;
        $zajezdSetup->datumNarozeni = isset($_REQUEST["cb-f-zaj-datum-narozeni"]) ? true : false;
        $zajezdSetup->rodneCislo = isset($_REQUEST["cb-f-zaj-rodne-cislo"]) ? true : false;
        $zajezdSetup->cisloPasu = isset($_REQUEST["cb-f-zaj-cislo-pasu"]) ? true : false;
        $zajezdSetup->adresa = isset($_REQUEST["cb-f-zaj-adresa"]) ? true : false;
        $zajezdSetup->telefon = isset($_REQUEST["cb-f-zaj-telefon"]) ? true : false;
        $zajezdSetup->email = isset($_REQUEST["cb-f-zaj-email"]) ? true : false;

        $this->zajezdSetup = $zajezdSetup;
    }

    public function setObjednavkaSetup()
    {
        $objednavkaSetup = new ObjednavkaSetup();
        $objednavkaSetup->id = isset($_REQUEST["cb-f-obj-id"]) ? true : false;
        $objednavkaSetup->sluzby = isset($_REQUEST["cb-f-sl-sluzby"]) ? true : false;
        $objednavkaSetup->objednavajici = isset($_REQUEST["cb-f-obj-objednavajici"]) ? true : false;
        $objednavkaSetup->prodejce = isset($_REQUEST["cb-f-obj-prodejce"]) ? true : false;
        $objednavkaSetup->nastupniMisto = isset($_REQUEST["cb-f-obj-nastupni-misto"]) ? true : false;
        $objednavkaSetup->nezobrazovat_objednavky = isset($_REQUEST["cb-f-obj-nezobrazovat"]) ? true : false;
        
        
        $this->objednavkaSetup = $objednavkaSetup;
    }

    /**
     * @param mixed $ucastniciIdsSelected
     */
    public function setUcastniciIdsSelected($ucastniciIdsSelected)
    {
        if (isset($ucastniciIdsSelected))
            $this->ucastniciIdsSelected = $this->parseMultipleIds($ucastniciIdsSelected);
    }
    /**
     * @param mixed $ucastniciIdsSelected
     */
    public function setObjednavkyIdsSelected($objednavkyIdsSelected)
    {
        if (isset($objednavkyIdsSelected))
            $this->objednavkyIdsSelected = $this->parseMultipleIds($objednavkyIdsSelected);
    }    

    /**
     * @param mixed $zajezdIdsSelected
     */
    public function setZajezdIdsSelected($zajezdIdsSelected)
    {
        $this->zajezdIdsSelected = $this->parseMultipleIds($zajezdIdsSelected);
    }

    /**
     * @param mixed $zajezdSkrytProsle
     */
    public function setZajezdSkrytProsle($zajezdSkrytProsle)
    {
        $this->zajezdSkrytProsle = $zajezdSkrytProsle;
    }

    /**
     * @param mixed $zajezdNovejsiNez
     */
    public function setZajezdNovejsiNez($zajezdNovejsiNez)
    {
        if (isset($zajezdNovejsiNez))
            $this->zajezdNovejsiNez = $zajezdNovejsiNez;
    }

    /**
     * @param string $serialNazev
     */
    public function setSerialNazev($serialNazev)
    {
        if (isset($serialNazev))
            $this->serialNazev = $serialNazev;
    }

    /**
     * @param string|int[] $serialIds
     */
    public function setSerialIds($serialIds)
    {
        if (isset($serialIds))
            $this->serialIds = $this->parseMultipleIds($serialIds);
    }

    /**
     * @param mixed $serialTyp
     */
    public function setSerialTyp($serialTyp)
    {
        if (isset($serialTyp))
            $this->serialTyp = $serialTyp;
    }

    /**
     * @param mixed $serialAktivniZajezd
     */
    public function setSerialAktivniZajezd($serialAktivniZajezd)
    {
        $this->serialAktivniZajezd = $serialAktivniZajezd;
    }

    /**
     * @param mixed $serialNoAktivniZajezd
     */
    public function setSerialNoAktivniZajezd($serialNoAktivniZajezd)
    {
        $this->serialNoAktivniZajezd = $serialNoAktivniZajezd;
    }

    /**
     * @param mixed $serialNoZajezd
     */
    public function setSerialNoZajezd($serialNoZajezd)
    {
        $this->serialNoZajezd = $serialNoZajezd;
    }

    /**
     * @param mixed $zajezdNoObjednavka
     */
    public function setZajezdNoObjednavka($zajezdNoObjednavka)
    {
        $this->zajezdNoObjednavka = $zajezdNoObjednavka;
    }

    /**
     * @param mixed $zajezdObjednavka
     */
    public function setZajezdObjednavka($zajezdObjednavka)
    {
        $this->zajezdObjednavka = $zajezdObjednavka;
    }

    /**
     * @param mixed $zajezdOd
     */
    public function setZajezdOd($zajezdOd)
    {
        if (isset($zajezdOd))
            $this->zajezdOd = $zajezdOd;
    }

    /**
     * @param mixed $zajezdDo
     */
    public function setZajezdDo($zajezdDo)
    {
        if (isset($zajezdDo))
            $this->zajezdDo = $zajezdDo;
    }

    /**
     * @param mixed $objednavkaOd
     */
    public function setObjednavkaOd($objednavkaOd)
    {
        if (isset($objednavkaOd))
            $this->objednavkaOd = $objednavkaOd;
    }

    /**
     * @param mixed $objednavkaDo
     */
    public function setObjednavkaDo($objednavkaDo)
    {
        if (isset($objednavkaDo))
            $this->objednavkaDo = $objednavkaDo;
    }
    /**
     * @param mixed $objednavkaOd
     */
    public function setRealizaceOd($realizaceOd)
    {
        if (isset($realizaceOd))
            $this->realizaceOd = $realizaceOd;
    }

    /**
     * @param mixed $objednavkaDo
     */
    public function setRealizaceDo($realizaceDo)
    {
        if (isset($realizaceDo))
            $this->realizaceDo = $realizaceDo;
    }

    //endregion

    //region STATIC METHODS **************************************************************

    /**
     * @param $name jmeno dane _instance filtru
     * @param $pagingMaxPages
     * @param $pagingMaxPerPage
     * @return SerialFilter|boolean _instance ulozene tridy FilterValue nebo false pokud v session neni ulozena
     */
    public static function loadFilter($name, $pagingMaxPages = null, $pagingMaxPerPage = null)
    {
        $filter = unserialize($_SESSION[$name]);
        if (!$filter)
            $filter = new SerialFilter($pagingMaxPages, $pagingMaxPerPage);

        return $filter;
    }

    //endregion

    //region PUBLIC METHODS **************************************************************

    public function calculatePaging($foundRows)
    {
        parent::calcPaging($foundRows, $this->pagingMaxPerPage, $this->pagingMaxPages);
    }

    public function saveFilter($name)
    {
        $_SESSION[$name] = serialize($this);
    }

    //endregion

}

//INNER CLASSES *******************************************************************

class ZajezdSetup
{
    /**
     * @var boolean
     */
    public $titul = false;
    /**
     * @var boolean
     */
    public $datumNarozeni = false;
    /**
     * @var boolean
     */
    public $rodneCislo = false;
    /**
     * @var boolean
     */
    public $cisloPasu = false;
    /**
     * @var boolean
     */
    public $adresa = false;
    /**
     * @var boolean
     */
    public $telefon = false;
    /**
     * @var boolean
     */
    public $email = false;

    public function getSetValuesCount()
    {
        $setCount = 0;
        $objProperties = get_object_vars($this);
        foreach ($objProperties as $p) {
            if ($p)
                $setCount++;
        }
        return $setCount;
    }
}

class ObjednavkaSetup
{
    /**
     * @var boolean
     */
    public $id = false;
    /**
     * @var boolean
     */
    public $sluzby = false;
    /**
     * @var boolean
     */
    public $objednavajici = false;
    /**
     * @var boolean
     */
    public $prodejce = false;
    /**
     * @var boolean
     */
    public $nastupniMisto = false;

    public function getSetValuesCount()
    {
        $setCount = 0;
        $objProperties = get_object_vars($this);
        foreach ($objProperties as $p) {
            if ($p)
                $setCount++;
        }
        return $setCount;
    }
}