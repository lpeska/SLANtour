<?php

class AdresaHolder {

    /** @var  AdresaEnt[] */
    private $adresy;

    function __construct($adresy)
    {
        $this->adresy = $adresy;
    }

    /**
     * @return AdresaEnt[]
     */
    public function getAdresy()
    {
        return $this->adresy;
    }
}