<?php


class BlackDaysEnt {

    public $id;
    public $terminOd;
    public $terminDo;

    function __construct($id, $terminDo, $terminOd)
    {
        $this->id = $id;
        $this->terminDo = $terminDo;
        $this->terminOd = $terminOd;
    }

}