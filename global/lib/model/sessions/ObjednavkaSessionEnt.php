<?php


class ObjednavkaSessionEnt
{

    private static $SESSION_NAME = "objednavka-session";

    private $termin;
    private $pocetOsob;
    /**
     * @var SluzbaSessionEnt[]
     */
    private $lastMinute;
    /**
     * @var SluzbaSessionEnt[]
     */
    private $sluzby;
    /**
     * @var SluzbaSessionEnt[]
     */
    private $priplatky;
    /**
     * @var SluzbaSessionEnt[]
     */
    private $slevy;
    /**
     * @var SluzbaSessionEnt[]
     */
    private $odjezdovaMista;

    /**
     * @var ObjednatelSessionEnt
     */
    private $objednatel;
    /**
     * @var UcastnikSessionEnt[]
     */
    private $ucastnici;
    private $poznamka;

    private $zpusobPlatby;

    public static function pullFromSession()
    {
        $objednavkaSession = unserialize($_SESSION[self::$SESSION_NAME]);
        if (!$objednavkaSession)
            $objednavkaSession = new ObjednavkaSessionEnt();

        return $objednavkaSession;
    }

    public static function clearFromSession()
    {
        $_SESSION[self::$SESSION_NAME] = null;
    }

    public function pushToSession()
    {
        $_SESSION[self::$SESSION_NAME] = serialize($this);
    }

    //region GETTERS *************************************************************
    /**
     * @return \SluzbaSessionEnt[]
     */
    public function getLastMinute()
    {
        return $this->lastMinute;
    }

    /**
     * @return \ObjednatelSessionEnt
     */
    public function getObjednatel()
    {
        return is_object($this->objednatel) ? $this->objednatel : new ObjednatelSessionEnt();
    }

    /**
     * @return \SluzbaSessionEnt[]
     */
    public function getOdjezdovaMista()
    {
        return $this->odjezdovaMista;
    }

    /**
     * @return mixed
     */
    public function getPocetOsob()
    {
        return $this->pocetOsob;
    }

    /**
     * @return \SluzbaSessionEnt[]
     */
    public function getPriplatky()
    {
        return $this->priplatky;
    }

    /**
     * @return \SluzbaSessionEnt[]
     */
    public function getSlevy()
    {
        return $this->slevy;
    }

    /**
     * @return \SluzbaSessionEnt[]
     */
    public function getSluzby()
    {
        return $this->sluzby;
    }

    /**
     * @return mixed
     */
    public function getTermin()
    {
        return $this->termin;
    }

    /**
     * @return \UcastnikSessionEnt[]
     */
    public function getUcastnici()
    {
        return $this->ucastnici;
    }

    /**
     * @return mixed
     */
    public function getPoznamka()
    {
        return $this->poznamka;
    }

    /**
     * @return mixed
     */
    public function getZpusobPlatby()
    {
        return $this->zpusobPlatby;
    }

    /**
     * @return SluzbaSessionEnt[]
     */
    public function getSluzbyAll()
    {
        return array_merge($this->getLastMinute(), $this->getSluzby(), $this->getPriplatky(), $this->getSlevy(), $this->getOdjezdovaMista());
    }

    //endregion

    //region SETTERS *************************************************************
    /**
     * @param \SluzbaSessionEnt[] $lastMinute
     */
    public function setLastMinute($lastMinute)
    {
        $this->lastMinute = $lastMinute;
    }

    /**
     * @param \ObjednatelSessionEnt $objednatel
     */
    public function setObjednatel($objednatel)
    {
        $this->objednatel = $objednatel;
    }

    /**
     * @param \SluzbaSessionEnt[] $odjezdovaMista
     */
    public function setOdjezdovaMista($odjezdovaMista)
    {
        $this->odjezdovaMista = $odjezdovaMista;
    }

    /**
     * @param mixed $pocetOsob
     */
    public function setPocetOsob($pocetOsob)
    {
        $this->pocetOsob = $pocetOsob;
    }

    /**
     * @param \SluzbaSessionEnt[] $priplatky
     */
    public function setPriplatky($priplatky)
    {
        $this->priplatky = $priplatky;
    }

    /**
     * @param \SluzbaSessionEnt[] $slevy
     */
    public function setSlevy($slevy)
    {
        $this->slevy = $slevy;
    }

    /**
     * @param \SluzbaSessionEnt[] $sluzby
     */
    public function setSluzby($sluzby)
    {
        $this->sluzby = $sluzby;
    }

    /**
     * @param mixed $termin
     */
    public function setTermin($termin)
    {
        $this->termin = $termin;
    }

    /**
     * @param \UcastnikSessionEnt[] $ucastnici
     */
    public function setUcastnici($ucastnici)
    {
        $this->ucastnici = $ucastnici;
    }

    /**
     * @param mixed $poznamka
     */
    public function setPoznamka($poznamka)
    {
        $this->poznamka = $poznamka;
    }

    /**
     * @param mixed $zpusobPlatby
     */
    public function setZpusobPlatby($zpusobPlatby)
    {
        $this->zpusobPlatby = $zpusobPlatby;
    }

    //endregion

    public function saveLastMinute()
    {
        $this->setLastMinute($this->fetchSluzbaSession("last-minute-", SluzbaEnt::TYP_LAST_MINUTE));
    }

    public function saveSluzby()
    {
        $this->setSluzby($this->fetchSluzbaSession("sluzba-", SluzbaEnt::TYP_SLUZBA));
    }

    public function savePriplatky()
    {
        $this->setPriplatky($this->fetchSluzbaSession("priplatek-", SluzbaEnt::TYP_PRIPLATEK));
    }

    public function saveSlevy()
    {
        $this->setSlevy($this->fetchSluzbaSession("sleva-", SluzbaEnt::TYP_SLEVA));
    }

    public function saveObjednatel()
    {
        $objednatel = new ObjednatelSessionEnt(
            $_POST["jmeno"],
            $_POST["prijmeni"],
            $_POST["datum-narozeni-day"],
            $_POST["datum-narozeni-month"],
            $_POST["datum-narozeni-year"],
            $_POST["ucastnik"],
            $_POST["email"],
            $_POST["telefon-pre"],
            $_POST["telefon"],
            $_POST["mesto"],
            $_POST["uliceCp"],
            $_POST["psc"],
            $_POST["newsletter"],
            $_POST["rodne-cislo"],
            $_POST["cislo-dokladu"]    
        );
        $this->setObjednatel($objednatel);
    }

    public function saveUcastnici()
    {
        $jmena = CommonUtils::filterArrayUseNumSuffix($_POST, "jmeno-");
        $prijmeni = CommonUtils::filterArrayUseNumSuffix($_POST, "prijmeni-");
        $emaily = CommonUtils::filterArrayUseNumSuffix($_POST, "email-");
        $telefony = CommonUtils::filterArrayUseNumSuffix($_POST, "telefon-");
        $dataNarozeni = CommonUtils::filterArrayUseNumSuffix($_POST, "datum-narozeni-");
        $rodnaCisla = CommonUtils::filterArrayUseNumSuffix($_POST, "rodne-cislo-");
        $cislaDokladu = CommonUtils::filterArrayUseNumSuffix($_POST, "cislo-dokladu-");

        $ucastnici = array();
        for ($i = 1; $i <= count((array)$jmena); $i++) {
            $ucastnici[] = new UcastnikSessionEnt(
                $jmena[$i],
                $prijmeni[$i],
                $emaily[$i],
                $telefony[$i],
                $dataNarozeni[$i],
                $rodnaCisla[$i],
                $cislaDokladu[$i]
            );
        }

        $this->setUcastnici($ucastnici);
    }

    public function hasUcastnici()
    {
        $jeObjednatelUcastnikem = $this->getObjednatel()->getJsemUcastnikemZajezdu();
        $pocetOsob = $this->getPocetOsob();
        return $jeObjednatelUcastnikem ? ($pocetOsob >= 2 ? true : false) : ($pocetOsob >= 1 ? true : false);
    }

    public function hasPoznamka()
    {
        return $this->poznamka != "";
    }

    public function calcPocetUcastniku()
    {
        $jeObjednatelUcastnikem = $this->getObjednatel()->getJsemUcastnikemZajezdu();
        return $jeObjednatelUcastnikem ? $this->getPocetOsob() - 1 : $this->getPocetOsob();
    }

    public function calcPocetObjednanychSluzeb()
    {
        $pocet = 0;

        foreach ($this->getSluzbyAll() as $s) {
            if ($s->getPocet() > 0)
                $pocet++;
        }

        return $pocet;
    }

    public function getTerminOd()
    {
        return trim(explode("-", $this->getTermin())[0]);
    }

    public function getTerminDo()
    {
        return trim(explode("-", $this->getTermin())[1]);
    }

    public function saveOdjezdovaMista()
    {
        $this->setOdjezdovaMista($this->fetchSluzbaSession("odjezdove-misto-", SluzbaEnt::TYP_ODJEZDOVE_MISTO));
    }

    public function calcPocetNoci()
    {
        $dStart = new DateTime(CommonUtils::engDate($this->getTerminOd()));
        $dEnd  = new DateTime(CommonUtils::engDate($this->getTerminDo()));
        $dDiff = $dStart->diff($dEnd);

        return $dDiff->days <= 0 ? 1 : $dDiff->days;
    }

    private function fetchSluzbaSession($paramName, $typ)
    {
        $request = CommonUtils::filterArrayUseNumSuffix($_POST, $paramName);
        $sluzbaSession = array();

        foreach ($request as $id => $cnt) {
            $sluzbaSession[] = new SluzbaSessionEnt($id, $cnt, $typ);
        }

        return $sluzbaSession;
    }
}