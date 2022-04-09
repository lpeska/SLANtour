<?php

class EmailHolder
{

    /** @var  EmailEnt[] */
    private $emaily;

    function __construct($emaily)
    {
        $this->emaily = $emaily;
    }

    /**
     * @return EmailEnt[]
     */
    public function getEmaily()
    {
        return $this->emaily;
    }
}