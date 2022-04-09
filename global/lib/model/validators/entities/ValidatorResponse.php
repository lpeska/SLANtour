<?php


class ValidatorResponse {

    /** @var  boolean */
    private $valid;
    /** @var  int[] */
    private $invalidStates;

    /**
     * ValidatorResponse constructor.
     * @param int[] $invalidStates
     */
    public function __construct($invalidStates)
    {
        if(is_null($invalidStates) || !is_array($invalidStates) || empty($invalidStates))
            $this->valid = true;
        else
            $this->valid = false;

        $this->invalidStates = $invalidStates;
    }

    /**
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return int[]
     */
    public function getInvalidStates()
    {
        return $this->invalidStates;
    }

}