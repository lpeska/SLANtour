<?php

class ZajezdEnt {
    const SABLONA_ZOBRAZENI_12 = 12;
    const DLOUHODOBE_ZAJEZDY_ANO = 1;

    public $id;
    public $terminOd;
    public $terminDo;
    public $hasObjednavka;

    /** @var  SerialEnt */
    public $serial;

    private $dlouhodobyZajezd;

    /** @var  SluzbaHolder */
    private $sluzbaHolder;

    /** @var  ObjednavkaHolder */
    private $objednavkaHodler;

    /** @var  SlevaEnt[] */
    private $casoveSlevy;

    /** @var  BlackDaysEnt[] */
    private $blackDays;

    public function __construct($id, $terminOd, $terminDo, $dlouhodobyZajezd)
    {
        $this->id = $id;
        $this->terminDo = $terminDo;
        $this->terminOd = $terminOd;
        $this->dlouhodobyZajezd = $dlouhodobyZajezd;
    }

    public function isAktivni()
    {
        $now = date("Y-m-d");
        if ($this->terminDo < $now) {
            return false;
        }
        return true;
    }

    public function isDlouhodoby()
    {
        return $this->dlouhodobyZajezd == self::DLOUHODOBE_ZAJEZDY_ANO ? true : false;
    }

    public function hasObjednavka()
    {
        if (!is_null($this->objednavkaHodler) && !is_null($this->objednavkaHodler->getObjednavky()))
            return true;
        return $this->hasObjednavka;
    }

    public function hasCasoveSlevy()
    {
        return !is_null($this->casoveSlevy);
    }

    public function isInBlackdays($odZajezd, $doZajezd)
    {
        if(is_array($this->blackDays)) {
            foreach ($this->blackDays as $blackDay) {
                if (($odZajezd >= $blackDay->terminOd && $odZajezd <= $blackDay->terminDo) ||
                    ($doZajezd >= $blackDay->terminOd && $doZajezd <= $blackDay->terminDo) ||
                    ($odZajezd <= $blackDay->terminOd && $doZajezd >= $blackDay->terminDo)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function constructNazev()
    {
        if (!is_null($this->serial))
            return $this->serial->constructNazev();
        return "";
    }

    public function setSluzby($sluzby)
    {
        $this->sluzbaHolder = new SluzbaHolder($sluzby);
    }

    public function setObjednavky($objednavky)
    {
        $this->objednavkaHodler = new ObjednavkaHolder($objednavky);
    }

    /**
     * @param SlevaEnt[] $casoveSlevy
     */
    public function setCasoveSlevy($casoveSlevy)
    {
        $this->casoveSlevy = $casoveSlevy;
    }

    /**
     * @param BlackDaysEnt[] $blackDays
     */
    public function setBlackDays($blackDays)
    {
        $this->blackDays = $blackDays;
    }

    /**
     * @return SluzbaHolder
     */
    public function getSluzbaHolder()
    {
        return $this->sluzbaHolder;
    }

    /**
     * @return ObjednavkaHolder
     */
    public function getObjednavkyHolder()
    {
        return $this->objednavkaHodler;
    }

    /**
     * @return BlackDaysEnt[]|null
     */
    public function getBlackDays()
    {
        return $this->blackDays;
    }

    /**
     * @return SlevaEnt[]
     */
    public function getCasoveSlevy()
    {
        return $this->casoveSlevy;
    }

}