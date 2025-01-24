<?php


class PlatebniDokladViewEmail implements IPlatebniDokladModelEmailObserver {

    /**
     * @var PlatebniDokladModel
     */
    private $model;

    /**
     * @param $model PlatebniDokladModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerEmailObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelEmailChanged()
    {
        $objednavka = $this->model->getObjednavka();
        $zajezd = $this->model->getZajezd();
        $objednavajici = $objednavka->objednavajici;
        $zajezdNazev = $zajezd->constructNazev();
        $objednavkaTermin = PlatebniDokladUtils::czechDate($objednavka->termin_od) . " - " . PlatebniDokladUtils::czechDate($objednavka->termin_do);

        $subject = "Platebn� doklad z�jezdu - $zajezdNazev";
        $message = "V p��loze V�m zas�l�me platebn� doklad k z�jezdu $zajezdNazev v term�nu $objednavkaTermin.\n" .
                    "Objednatel: $objednavajici->jmeno $objednavajici->prijmeni.";

        echo $this->model->sendPdfEmails($subject, $message);
    }
    
}