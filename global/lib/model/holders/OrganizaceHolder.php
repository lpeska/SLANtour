<?php


class OrganizaceHolder {

    /** @var  OrganizaceEnt[] */
    private $organizace;

    function __construct($organizace)
    {
        $this->organizace = $organizace;
    }

    public function getOrganizace()
    {
        return $this->organizace;
    }

    public function calcObrat()
    {
        $sum = 0;

        if (is_null($this->organizace))
            return $sum;

        foreach ($this->organizace as $org) {
            $sum += $org->getObjednavkaHolder()->calcFinalniCena();
        }

        return $sum;
    }

    public function calcObjednavkyPocetUcastniku()
    {
        $sum = 0;

        if (is_null($this->organizace))
            return $sum;

        foreach ($this->organizace as $org) {
            $sum += $org->getObjednavkaHolder()->calcUcastniciCount();
        }

        return $sum;
    }

    public function calcObjednavkyUhrazenoHotove()
    {
        $sum = 0;

        if (is_null($this->organizace))
            return $sum;

        foreach ($this->organizace as $org) {
            $sum += $org->getObjednavkaHolder()->calcUhrazenoHotove();
        }

        return $sum;
    }
}