<?php

class SeznamyUcastnikuViewEmail implements ISeznamyUcastnikuModelEmailObserver
{
    /**
     * @var SeznamyUcastnikuModel
     */
    private $model;

    /**
     * @param $model SeznamyUcastnikuModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerEmailObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelEmailChanged()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialyList = $serialyHolder->getSerialList();

        $subject = "Seznam úèastníkù zájezdu";
        $message = "Zasíláme Vám seznam úèastníkù následujících zájezdù:\n\n";

        foreach($serialyList as $serial) {
            $message .= "Seriál: $serial->nazev [$serial->id]\n";
            foreach($serial->zajezdy as $zajezd) {
                $message .= "-- Zájezd: $zajezd->nazev [$zajezd->id]\n";
            }
            $message .= "\n";
        }

        echo $this->model->sendPdfEmails($subject, $message);
    }
}
