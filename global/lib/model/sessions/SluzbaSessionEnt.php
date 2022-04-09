<?php


class SluzbaSessionEnt {

    const TYP_SLUZBA = 1;
    const TYP_LAST_MINUTE = 2;
    const TYP_SLEVA = 3;
    const TYP_PRIPLATEK = 4;
    const TYP_ODJEZDOVE_MISTO = 5;

    private $id;
    private $typ;
    private $pocet;

    function __construct($id, $pocet, $typ)
    {
        $this->id = $id;
        $this->pocet = $pocet;
        $this->typ = $typ;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPocet()
    {
        return $this->pocet;
    }

    /**
     * @return mixed
     */
    public function getTyp()
    {
        return $this->typ;
    }
}