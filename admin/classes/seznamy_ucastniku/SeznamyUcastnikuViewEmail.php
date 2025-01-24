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

        $subject = "Seznam ��astn�k� z�jezdu";
        $message = "Zas�l�me V�m seznam ��astn�k� n�sleduj�c�ch z�jezd�:\n\n";

        foreach($serialyList as $serial) {
            $message .= "Seri�l: $serial->nazev [$serial->id]\n";
            foreach($serial->zajezdy as $zajezd) {
                $message .= "-- Z�jezd: $zajezd->nazev [$zajezd->id]\n";
            }
            $message .= "\n";
        }

        echo $this->model->sendPdfEmails($subject, $message);
    }
}
