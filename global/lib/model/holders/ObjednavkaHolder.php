<?php

class ObjednavkaHolder {

    /** @var  ObjednavkaEnt[] */
    private $objednavky;

    function __construct($objednavky)
    {
        $this->objednavky = $objednavky;
    }

    /**
     * Konecna cena objednavek
     * @uses stav, storno_poplatek, sluzbaHolder, pocet_noci, sleva_holder
     * @return int
     */
    public function calcFinalniCena()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
                $sum += $o->calcFinalniCenaObjednavky();
        }

        return $sum;
    }

    /**
     * Suma castek sluzeb objednavek, neuvazuji se slevy
     * @return int
     */
    public function calcZaSluzbyNoSlevy()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
//            echo "<pre>"; var_dump("id:" . $o->id); echo "</pre>";
            if ($o->stav != ObjednavkaEnt::STAV_STORNO_KLIENT and $o->stav != ObjednavkaEnt::STAV_STORNO_CK)
                $sum += $o->calcZaSluzbyTotalNoSlevy();
        }

        return $sum;
    }

    /**
     * Suma castek sluzeb objednavek, neuvazuji se slevy
     * @return int
     */
    public function calcZaSluzby()
    {
        $sum = 0;
        $list = "Služby:";
        $list2 = "Objednávky:";
        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
//            echo "<pre>"; var_dump("id:" . $o->id); echo "</pre>";
            if ($o->stav != ObjednavkaEnt::STAV_STORNO_KLIENT and $o->stav != ObjednavkaEnt::STAV_STORNO_CK) {
                $sum += $o->calcZaSluzbyTotal();
                $list .= " + " . $o->calcZaSluzbyTotal();
                $list2 .= " , " . $o->id;
            }
        }
        // echo $list2."<br/>";
        //echo $list."<br/>";
        return $sum;
    }

    /**
     * Suma castek poskytnutych slev objednavek
     * @return int
     */

    public function calcSlevy()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
//            echo "<pre>"; var_dump("id:" . $o->id); echo "</pre>";
            if ($o->stav != ObjednavkaEnt::STAV_STORNO_KLIENT and $o->stav != ObjednavkaEnt::STAV_STORNO_CK)
                $sum += $o->calcSlevaTotal();
        }

        return $sum;
    }

    /**
     * Suma castek poskytnutych individuálních slev objednavek
     *  (nejsou to služby, vyskytují se v tabulce objednavka_sleva)
     * @return int
     */
    public function calcIndividualSlevy()
    {
        $sum = 0;
        $list = "Slevy Individuální:";

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
//            echo "<pre>"; var_dump("id:" . $o->id); echo "</pre>";
            if ($o->stav != ObjednavkaEnt::STAV_STORNO_KLIENT and $o->stav != ObjednavkaEnt::STAV_STORNO_CK) {
                $sum += $o->calcSlevyKtereNejsouSluzba();
                $list .= " + " . $o->calcSlevyKtereNejsouSluzba();
            }
        }
        //echo $list."<br/>";
        return $sum;
    }

    /**
     * Suma castek provizi objednavek
     * @return int
     */
    public function calcProvize()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
            if (!is_null($o->getProdejce()))
                $sum += $o->suma_provize;
        }

        return $sum;
    }

    /**
     * Suma castek storen objednavek
     * @return int
     */
    public function calcStorna()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
            $sum += $o->storno_poplatek;
        }

        return $sum;
    }

    /**
     * Suma castek sluzeb objednavek vcetne storno poplatku, ale bez slev
     * @return int
     */
    public function calcCelkemZaZajezdy()
    {
        return $this->calcZaSluzby() - $this->calcIndividualSlevy() + $this->calcStorna();
    }

    /**
     * Suma plateb objednavek, bez plateb faktur
     * @return int
     */
    public function calcUhrazenoHotove()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
            $platby = $o->getPlatbyHolder()->getPlatby();
            if (is_null($platby))
                continue;
            foreach ($platby as $platba) {
                $sum += $platba->castka;
            }
        }

        return $sum;
    }

    /**
     * Suma fakturovanych castek objednavek
     * @param $mena
     * @return int
     */
    public function calcFakturovano($mena = FakturaEnt::FAKTURA_MENA_CZK)
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
            $faktury = $o->getFakturyHolder()->getFaktury();
            if (is_null($faktury))
                continue;
            foreach ($faktury as $faktura) {
                if ($faktura->mena == $mena)
                    $sum += $faktura->celkova_castka;
            }
        }

        return $sum;
    }

    /**
     * Suma castek neuhrazenych faktur objednavek
     * @return int
     */
    public function calcNeuhrazeneFaktury()
    {
        $sum = 0;

        if (is_null($this->objednavky))
            return $sum;

        foreach ($this->objednavky as $o) {
            $faktury = $o->getFakturyHolder()->getFaktury();
            if (is_null($faktury))
                continue;
            foreach ($faktury as $faktura) {
                if ($faktura->zaplaceno == FakturaEnt::FAKTURA_ZAPLACENO_NE)
                    $sum += $faktura->celkova_castka;
            }
        }

        return $sum;
    }

    /**
     * Caska, kterou maji klienti stale zaplatit (vyjma nezaplacenych faktur)
     * @return int
     */
    public function calcCelkemNeuhrazeno()
    {
        return $this->calcCelkemZaZajezdy() - $this->calcUhrazenoHotove() - $this->calcNeuhrazeneFaktury();
    }

    public function getObjednavky()
    {
        return $this->objednavky;
    }

    public function calcUcastniciCount()
    {
        $cnt = 0;

        if (!is_null($this->objednavky)) {
            foreach ($this->objednavky as $o) {
                $cnt += $o->pocet_osob;
            }
        }

        return $cnt;
    }
}