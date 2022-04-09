<?php

class TelefonHolder {

    /** @var  TelefonEnt[] */
    private $telefony;

    function __construct($telefony)
    {
        $this->telefony = $telefony;
    }

    /**
     * @return TelefonEnt[]
     */
    public function getTelefony()
    {
        return $this->telefony;
    }
}