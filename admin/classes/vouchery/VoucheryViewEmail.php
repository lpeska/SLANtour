<?php

class VoucheryViewEmail implements IVoucheryModelEmailObserver
{
    /**
     * @var VoucheryModel
     */
    private $model;

    /**
     * @param $model VoucheryModel
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
