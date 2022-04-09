<?php

class SluzbaHolder
{
    /** @var  SluzbaEnt[] */
    private $sluzby;

    function __construct($sluzby)
    {
        $this->sluzby = $sluzby;
    }

    public function hasNaDotazSluzba()
    {
        foreach ($this->sluzby as $s) {
            if ($s->isNaDotaz())
                return true;
        }
        return false;
    }

    public function hasVyprodanoSluzba()
    {
        foreach ($this->sluzby as $s) {
            if ($s->isVyprodana())
                return true;
        }
        return false;
    }

    public function hasOdjezdoveMisto()
    {
        return $this->hasSluzbaOfType(SluzbaEnt::TYP_ODJEZDOVE_MISTO);
    }

    public function hasSleva()
    {
        return $this->hasSluzbaOfType(SluzbaEnt::TYP_SLEVA);
    }

    public function hasObjednanaSleva()
    {
        return $this->hasObjednanaSluzbaOfType(SluzbaEnt::TYP_SLEVA);
    }

    public function hasNeobjednanaSleva()
    {
        return $this->hasNeobjednanaSluzbaOfType(SluzbaEnt::TYP_SLEVA);
    }

    public function hasPriplatek()
    {
        return $this->hasSluzbaOfType(SluzbaEnt::TYP_PRIPLATEK);
    }

    public function hasSluzba()
    {
        return $this->hasSluzbaOfType(SluzbaEnt::TYP_SLUZBA);
    }

    public function hasLastMinute()
    {
        return $this->hasSluzbaOfType(SluzbaEnt::TYP_LAST_MINUTE);
    }

    /**
     * @param int $pocetOsob
     * @return bool
     */
    public function isVolnaKapacita($pocetOsob)
    {
        if (is_array($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                if ($s->isPlnaKapacita($pocetOsob))
                    return false;
            }
        }
        
        return true;
    }

    //region PUBLIC CALC *****************************************************************

    /**
     * todo nesikovne - upravit
     * Suma castek podmnoziny sluzeb
     * @param SluzbaSessionEnt[] $sluzbySession
     * @param int $pocetNoci
     * @param bool $isEur
     * @return int
     */
    public function calcCenaOfSluzbaSubset($sluzbySession, $pocetNoci, $isEur = false, $kurzEur = null)
    {
        $sum = 0;
        if (is_array($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                $sSubset = CommonUtils::searchArrayOfObjects($sluzbySession, $s->id_cena, "getId", null);
                $pocet = is_null($sSubset) ? 0 : $sSubset->getPocet();
                $pocetNociSluzba = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $pocetNoci : 1;
                if ($isEur) {
                    $castka = round($s->castka / $kurzEur);
                } else {
                    $castka = $s->castka;
                }
                $sum += $castka * $pocet * $pocetNociSluzba;
            }
        }
        return $sum;
    }
 /**
     * todo nesikovne - upravit
     * TODO, Lada: pro potreby vypoctu vyse casove slevy - je to trochu nesikovne, dalo by se to integrovat do pomoci jedne promenne do 
     *     calcCenaOfSluzbaSubset - jen jsem ti nechtel moc hrabat do existujiciho kodu, pokud bys s tim mel jine plany
     * Suma castek podmnoziny sluzeb
     * @param SluzbaSessionEnt[] $sluzbySession
     * @param int $pocetNoci
     * @param bool $isEur
     * @return int
     */
    public function calcCenaOfSluzbaSubsetOnlySluzbyAndLastminute($sluzbySession, $pocetNoci, $isEur = false, $kurzEur = null)
    {
        $sum = 0;
        $sumSluzby = 0;
          if (is_array($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                $sSubset = CommonUtils::searchArrayOfObjects($sluzbySession, $s->id_cena, "getId", null);
                $pocet = is_null($sSubset) ? 0 : $sSubset->getPocet();
                $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $pocetNoci : 1;
                if ($isEur) {
                    $castka = round($s->castka / $kurzEur);
                } else {
                    $castka = $s->castka;
                }
                $sum += $castka * $pocet * $pocetNoci;                
                if($s->isTypSluzba() || $s->isTypLastMinute()){
                    $sumSluzby += $castka * $pocet * $pocetNoci;
                }

            }
        }
        return $sumSluzby;
    }    
 /**
    * Lada: nove vytvorena funkce ktera pocita vysi casove slevy na zaklade objednavky - pouzita v souhrnu objednavky
     * Suma castek podmnoziny sluzeb
     * @param SluzbaSessionEnt[] $sluzbySession
     * @param int $pocetNoci
     * @param bool $isEur
     * @return int
     */
    public function calcCasovaSleva($sluzbyCastka, $pocetOsob, $slevaCastka, $slevaMena,  $isEur = false, $kurzEur = null){
        if($slevaMena=="%"){
            return $sluzbyCastka * $slevaCastka / 100;
        }else{//$slevaMena=="Kè"
            if($isEur == true){
                //sleva je v Kè, poèítáme v eurech
                return $slevaCastka/$kurzEur * $pocetOsob;
            }else{
                return $slevaCastka * $pocetOsob;
            }
        }
    }

    /**
     * Suma castek veskerych sluzeb (bez slev)
     * @param $pocetNoci
     * @return int
     */
    public function calcPriceTotalNoSlevy($pocetNoci)
    {
        $sum = 0;

        $sluzby = $this->getSluzbyAllNoSlevy();
        if (is_array($sluzby)) {
            foreach ($sluzby as $s) {
                $pocetNociCalc = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $pocetNoci : 1;
                $sum += $s->castka * $s->pocet * $pocetNociCalc;
//            echo "<pre>";
//            var_dump($s->castka . "*" . $s->pocet . "*" . $pocetNociCalc . "use_pocet_noci: " . $s->use_pocet_noci);
//            echo "</pre>";
            }
        }
//        echo "<pre>"; var_dump("--------------"); echo "</pre>";

        return $sum;
    }

    /**
     * Sumca castek veskerych zakladnich sluzeb (pozuiva se pro zaklad slevy)
     * @param $pocetNoci
     * @return int
     */
    public function calcPriceTotalZakladni($pocetNoci)
    {
        $sum = 0;

        $sluzby = $this->getSluzbyAllZakladni();
        if (is_array($sluzby)) {
            foreach ($sluzby as $s) {
                $pocetNociCalc = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $pocetNoci : 1;
                $sum += $s->castka * $s->pocet * $pocetNociCalc;
            }
        }

        return $sum;
    }

    public function calcPriceTotal($pocetNoci)
    {
        //note je tu zdanlive nelogicky plus - protoze slevy typu sluzba jsou ulozeny v databazi s minusovym znamenkem nebo nektere pak s plusovym a je to brane jako slevnena cena (napr cena za dite do 12 let je nizsi nez normalni sluzba)
        return $this->calcPriceTotalNoSlevy($pocetNoci) + $this->calcSlevaPriceTotal($pocetNoci);
    }

    /**
     * Suma castek veskerych slev
     * @param $pocetNoci
     * @return int
     */
    public function calcSlevaPriceTotal($pocetNoci)
    {
        $sum = 0;

        if (!is_null($this->getSlevy())) {
            foreach ($this->getSlevy() as $s) {
                $pocetNociCalc = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $pocetNoci : 1;
                $sum += $s->castka * $s->pocet * $pocetNociCalc;
//            echo "<pre>";
//            var_dump($s->id_cena . " - " . $s->castka . "*" . $s->pocet . "*" . $pocetNociCalc . "use_pocet_noci: " . $s->use_pocet_noci);
//            echo "</pre>";
            }
        }
//        echo "<pre>"; var_dump("--------------"); echo "</pre>";

        return $sum;
    }

    //endregion

    //region PUBLIC GETTERS/SETTERS ******************************************************

    /**
     * @return SluzbaEnt[]
     */
    public function getSluzbyAll()
    {
        return $this->sluzby;
    }

    /** @return SluzbaEnt[] */
    public function getSluzbyAllNoSlevy()
    {
        $sluzby = $this->getSluzbyByType(SluzbaEnt::TYP_SLUZBA);
        $lastMinute = $this->getSluzbyByType(SluzbaEnt::TYP_LAST_MINUTE);
        $priplatky = $this->getSluzbyByType(SluzbaEnt::TYP_PRIPLATEK);
        $odjezdovaMista = $this->getSluzbyByType(SluzbaEnt::TYP_ODJEZDOVE_MISTO);
        $rucnePridane = $this->getSluzbyByType(SluzbaEnt::TYP_RUCNE_PRIDANA);
        if (is_null($sluzby) && is_null($lastMinute) && is_null($priplatky) && is_null($odjezdovaMista) && is_null($rucnePridane))
            return null;
        else
            return array_merge((array)$sluzby, (array)$lastMinute, (array)$priplatky, (array)$odjezdovaMista, (array)$rucnePridane);
    }

    public function getSluzbyAllNoSlevyNoRucnePridane()
    {
        $sluzby = $this->getSluzbyByType(SluzbaEnt::TYP_SLUZBA);
        $lastMinute = $this->getSluzbyByType(SluzbaEnt::TYP_LAST_MINUTE);
        $priplatky = $this->getSluzbyByType(SluzbaEnt::TYP_PRIPLATEK);
        $odjezdovaMista = $this->getSluzbyByType(SluzbaEnt::TYP_ODJEZDOVE_MISTO);
        if (is_null($sluzby) && is_null($lastMinute) && is_null($priplatky) && is_null($odjezdovaMista))
            return null;
        else
            return array_merge((array)$sluzby, (array)$lastMinute, (array)$priplatky, (array)$odjezdovaMista);
    }

    /**
     * Vraci pouze zakladni sluzby - tedy typu sluzba a last minute
     * @return SluzbaEnt[]|null
     */
    private function getSluzbyAllZakladni()
    {
        $sluzby = $this->getSluzbyByType(SluzbaEnt::TYP_SLUZBA);
        $lastMinute = $this->getSluzbyByType(SluzbaEnt::TYP_LAST_MINUTE);
        if (is_null($sluzby) && is_null($lastMinute))
            return null;
        else
            return array_merge((array)$sluzby, (array)$lastMinute);
    }

    /**
     * @return SluzbaEnt[]
     */
    public function getLastMinute()
    {
        return $this->getSluzbyByType(SluzbaEnt::TYP_LAST_MINUTE);
    }

    /**
     * @return SluzbaEnt[]
     */
    public function getRucnePridane()
    {
        return $this->getSluzbyByType(SluzbaEnt::TYP_RUCNE_PRIDANA);
    }

    /**
     * @return SluzbaEnt[]
     */
    public function getSluzby()
    {
        return $this->getSluzbyByType(SluzbaEnt::TYP_SLUZBA);
    }

    /**
     * @param $idSluzba
     * @return SluzbaEnt|null
     */
    public function getSluzba($idSluzba)
    {
        if(!is_null($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                if($s->id_cena == $idSluzba)
                    return $s;
            }
        }

        return null;
    }

    /**
     * @return SluzbaEnt[]
     */
    public function getPriplatky()
    {
        return $this->getSluzbyByType(SluzbaEnt::TYP_PRIPLATEK);
    }

    /**
     * @return SluzbaEnt[]
     */
    public function getSlevy()
    {
        return $this->getSluzbyByType(SluzbaEnt::TYP_SLEVA);
    }

    /**
     * @return SluzbaEnt[]|null
     */
    public function getOdjezdovaMista()
    {
        return $this->getSluzbyByType(SluzbaEnt::TYP_ODJEZDOVE_MISTO);
    }

    /**
     * @return SluzbaEnt[]|null
     */
    public function getObjednaneSluzby() {
        $sluzbyAllNoSlevy = $this->getSluzbyAllNoSlevy();
        $objednaneSluzby = null;

        if(!is_null($sluzbyAllNoSlevy)) {
            foreach ($sluzbyAllNoSlevy as $sluzba) {
                if ($sluzba->pocet != 0 || $sluzba->getPocetStorno() != 0)
                    $objednaneSluzby[] = $sluzba;
            }
        }

        return $objednaneSluzby;
    }

    /**
     * @return SluzbaEnt[]|null
     */
    public function getNeobjednaneSluzby() {
        $sluzbyAllNoSlevy = $this->getSluzbyAllNoSlevy();
        $objednaneSluzby = null;

        if(!is_null($sluzbyAllNoSlevy)) {
            foreach ($sluzbyAllNoSlevy as $sluzba) {
                if ($sluzba->pocet == 0 && $sluzba->getPocetStorno() == 0)
                    $objednaneSluzby[] = $sluzba;
            }
        }

        return $objednaneSluzby;
    }

    /**
     * @return SluzbaEnt[]|null
     */
    public function getStornovaneSluzby() {
        $sluzbyAllNoSlevy = $this->getSluzbyAllNoSlevy();
        $stornovaneSluzby = null;

        if(!is_null($sluzbyAllNoSlevy)) {
            foreach ($sluzbyAllNoSlevy as $sluzba) {
                if ($sluzba->getPocetStorno() > 0)
                    $stornovaneSluzby[] = $sluzba;
            }
        }

        return $stornovaneSluzby;
    }

    //endregion

    //region PRIVATE METHODS *************************************************************

    /**
     * @param $type
     * @return SluzbaEnt[] array
     */
    private function getSluzbyByType($type)
    {
        $sluzby = null;

        if (!is_null($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                if ($s->typ == $type)
                    $sluzby[] = $s;
            }
        }

        return $sluzby;
    }

    private function hasSluzbaOfType($type)
    {
        if (!is_null($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                if ($s->typ == $type)
                    return true;
            }
        }

        return false;
    }

    private function hasObjednanaSluzbaOfType($type)
    {
        if (!is_null($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                if ($s->typ == $type && $s->pocet > 0)
                    return true;
            }
        }

        return false;
    }

    private function hasNeobjednanaSluzbaOfType($type)
    {
        if (!is_null($this->sluzby)) {
            foreach ($this->sluzby as $s) {
                if ($s->typ == $type && $s->pocet < 1)
                    return true;
            }
        }

        return false;
    }

    //endregion
}