<?php


class SlevaEnt
{
    const SLEVA_PLATNOST_ANO = 1;
    const SLEVA_STALY_KLIENT_NE = 0;
    const SLEVA_TYP_NEOBJEDNANA = 'neobjednana';
    const SLEVA_TYP_OBJEDNANA = 'objednana';

    public $id_slevy;
    public $nazev_slevy;
    public $castka;
    public $mena;
    public $sleva_staly_klient;

    /** @var  jeden z typu objednavky self::SLEVA_TYP_NEOBJEDNANA, self::SLEVA_TYP_OBJEDNANA */
    private $typ;

    function __construct($id_slevy, $nazev_slevy, $castka, $mena, $sleva_staly_klient)
    {
        $this->id_slevy = $id_slevy;
        $this->nazev_slevy = $nazev_slevy;
        $this->castka = $castka;
        $this->mena = $mena;
        $this->sleva_staly_klient = $sleva_staly_klient;
    }

    /**
     * @return mixed
     */
    public function getTyp()
    {
        return $this->typ;
    }

    /**
     * @param mixed $typ
     */
    public function setTyp($typ)
    {
        $this->typ = $typ;
    }

    public function calcCelkovaCastkaSlevy($zakladniCenaObjednavky, $pocetOsob)
    {
        $celkovaCastkaSlevy = 0;

        switch ($this->mena) {
            case ViewConfig::MENA_PERCENT:
                $celkovaCastkaSlevy = round($zakladniCenaObjednavky / 100 * $this->castka);
                break;
            case ViewConfig::MENA_KC:
                $celkovaCastkaSlevy = $this->castka;
                break;
            case ViewConfig::MENA_KC_OS:
                $celkovaCastkaSlevy = $this->castka * $pocetOsob;
                break;
        }

        return $celkovaCastkaSlevy;
    }

}