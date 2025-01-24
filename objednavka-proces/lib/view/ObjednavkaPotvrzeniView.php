<?php

class ObjednavkaPotvrzeniView implements IObjednavkaModelPotvrzeniObserver
{
    /**
     * @var IObjednavkaModelForView
     */
    private $model;

    /**
     * @param $model IObjednavkaModelForView
     */
    function __construct($model)
    {
        $this->model = $model;
        //todo register observer
    }

    // PUBLIC METHODS ********************************************************************

    public function modelPotvrzeniChanged()
    {
        // TODO: Implement modelPotvrzeniChanged() method.
    }

    // PRIVATE METHODS *******************************************************************
}