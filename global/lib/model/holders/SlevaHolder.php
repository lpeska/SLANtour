<?php


class SlevaHolder
{

    /** @var  SlevaEnt[] */
    private $slevy;

    function __construct($slevy)
    {
        $this->slevy = $slevy;
    }

    /**
     * @return SlevaEnt[]
     */
    public function getSlevy()
    {
        return $this->slevy;
    }

    /**
     * @return SlevaEnt[]|null
     */
    public function getObjednaneSlevy()
    {
        $objednaneSlevy = null;

        if (is_array($this->slevy)) {
            foreach ($this->slevy as $sleva) {
                if ($sleva->getTyp() == SlevaEnt::SLEVA_TYP_OBJEDNANA)
                    $objednaneSlevy[] = $sleva;
            }
        }

        return $objednaneSlevy;
    }

    /**
     * @return SlevaEnt[]|null
     */
    public function getNeobjednaneSlevy()
    {
        $neobjednaneSlevy = null;

        if(is_array($this->slevy)) {
            foreach ($this->slevy as $sleva) {
                if ($sleva->getTyp() == SlevaEnt::SLEVA_TYP_NEOBJEDNANA)
                    $neobjednaneSlevy[] = $sleva;
            }
        }

        return $neobjednaneSlevy;
    }

    public function calcObjednaneSlevy($zakladniCenaObjednavky, $pocetOsob)
    {
        $sum = 0;

        if(!is_null($this->getObjednaneSlevy())) {
            foreach ($this->getObjednaneSlevy() as $sleva) {
                $sum += $sleva->calcCelkovaCastkaSlevy($zakladniCenaObjednavky, $pocetOsob);
            }
        }

        return $sum;
    }

}