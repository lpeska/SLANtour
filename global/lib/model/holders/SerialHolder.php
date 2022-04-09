<?php

class SerialHolder
{
    /**
     * @var SerialEnt[]
     */
    private $serialyList;
    /**
     * @var int
     */
    private $foundRows;

    //region GETTERS/SETTERS *******************************************************************
    /**
     * todo U MODULU VYBERY A SMLUVNI PODMINKY VRACI tsSerial - chce to predelat, ale je to na nejakou chvili, takze zatim to nechavam byt
     * @return \SerialEnt[]
     */
    public function getSerialList()
    {
        return $this->serialyList;
    }

    /**
     * @return int
     */
    public function getFoundRows()
    {
        return $this->foundRows;
    }

    /**
     * @param int $foundRows
     */
    public function setFoundRows($foundRows)
    {
        $this->foundRows = $foundRows;
    }

    /**
     * @param \SerialEnt[] $serialyList
     */
    public function setSerialyList($serialyList)
    {
        $this->serialyList = $serialyList;
    }
    //endregion

    /**
     * Suma castek sluzeb objednavek zajezdu danych serialu, neuvazuji se slevy
     * @return int
     */
    public function calcZaSluzbyNoSlevy()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcZaSluzbyNoSlevy();
            }
        }

        return $sum;
    }
    /**
     * Suma castek sluzeb objednavek zajezdu danych serialu
     * @return int
     */
    public function calcZaSluzby()
    {
        $sum = 0;
        
        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcZaSluzby();
           
            }
        }
        
        return $sum;
    }
    /**
     * Suma castek poskytnutych slev objednavek zajezdu danych serialu
     * @return int
     */
    public function calcSlevy()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcSlevy();
            }
        }

        return $sum;
    }
   /**
     * Suma castek poskytnutych individualnich slev (tabulka objednavka_slevy)
     * @return int
     */
    public function calcIndividualSlevy()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcIndividualSlevy();
            }
        }

        return $sum;
    }
    /**
     * Suma castek provizi objednavek zajezdu danych serialu
     * @return int
     */
    public function calcProvize()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcProvize();
            }
        }

        return $sum;
    }

    /**
     * Suma castek storen objednavek zajezdu danych serialu
     * @return int
     */
    public function calcStorna()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcStorna();
            }
        }

        return $sum;
    }

    /**
     * Suma castek sluzeb objednavek zajezdu danych serialu vcetne storno poplatku, ale bez slev
     * @return int
     */
    public function calcCelkemZaZajezdy()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcCelkemZaZajezdy();
            }
        }

        return $sum;
    }

    /**
     * Suma plateb objednavek zajezdu danych serialu, bez plateb faktur
     * @return int
     */
    public function calcUhrazenoHotove()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcUhrazenoHotove();
            }
        }

        return $sum;
    }

    /**
     * Suma fakturovanych castek objednavek zajezdu danych serialu
     * @return int
     */
    public function calcFakturovano()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcFakturovano();
            }
        }

        return $sum;
    }

    /**
     * Suma castek neuhrazenych faktur objednavek zajezdu danych serialu
     * @return int
     */
    public function calcNeuhrazeneFaktury()
    {
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcNeuhrazeneFaktury();
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
        $sum = 0;

        foreach ($this->serialyList as $serial) {
            foreach ($serial->getZajezdHolder()->getZajezdy() as $zajezd) {
                $sum += $zajezd->getObjednavkyHolder()->calcCelkemNeuhrazeno();
            }
        }

        return $sum;
    }
}