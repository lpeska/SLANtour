<?php

class ZajezdHolder
{

    /**
     * @var ZajezdEnt[]
     */
    private $zajezdy;

    function __construct($zajezdy)
    {
        $this->zajezdy = $zajezdy;
    }

    /**
     * @return ZajezdEnt[]
     */
    public function getZajezdy()
    {
        return $this->zajezdy;
    }
}