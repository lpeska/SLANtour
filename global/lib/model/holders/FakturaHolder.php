<?php

class FakturaHolder {

    /** @var  FakturaEnt[] */
    private $faktury;

    function __construct($faktury)
    {
        $this->faktury = $faktury;
    }

    /**
     * @return FakturaEnt[]
     */
    public function getFaktury()
    {
        return $this->faktury;
    }
}