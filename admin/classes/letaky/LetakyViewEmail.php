<?php

class LetakyViewEmail implements ILetakyModelEmailObserver
{
    /**
     * @var LetakyModel
     */
    private $model;

    /**
     * @param $model LetakyModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerEmailObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelEmailChanged()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $objednavajici = $objednavkaHolder->getObjednavajici();
        $vybranyObjekt = $this->model->getObjektSelected();

        $subject = "Voucher " . $vybranyObjekt->nazev_objektu . " - " . ucfirst($objednavajici->jmeno) . " " . ucfirst($objednavajici->prijmeni);
        $message = $this->model->getEmailText();

        echo $this->model->sendPdfEmails($subject, $message);
    }
}
