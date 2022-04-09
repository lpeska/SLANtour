<?php

class PlatbaHolder
{

    /** @var  PlatbaEnt[] */
    private $platby;

    function __construct($platby)
    {
        $this->platby = $platby;
    }

    /**
     * @return PlatbaEnt[]
     */
    public function getPlatby()
    {
        return $this->platby;
    }

    /**
     * @param $id
     * @return null|PlatbaEnt
     */
    public function getPlatba($id)
    {
        foreach ($this->platby as $platba) {
            if($platba->id == $id)
                return $platba;
        }
        return null;
    }

    public function calcCastkaSum()
    {
        $castkaSum = 0;

        if (!is_null($this->platby)) {
            foreach ($this->platby as $platba) {
                $castkaSum += $platba->castka;
            }
        }


        return $castkaSum;
    }

}