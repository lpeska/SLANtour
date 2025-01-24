<?php

class FinancniPohybyController
{

    /**
     * @var FinancniPohybyModel
     */
    private $model;

    private $zamestnanec;

    function __construct($model)
    {
        $this->model = $model;
        $this->init();
    }

    public function dispatchRequests()
    {
        switch ($_REQUEST["page"]) {
            default:
            case "serialy":
                $this->model->serialList();
                break;
            case "prehled":
                $this->model->prehled();
                break;
            case "prehled-pdf":
                $this->model->prehledPdf();
                break;
        }
    }

    private function init()
    {
        FinancniPohybyModel::$zamestnanec = User_zamestnanec::get_instance();
        FinancniPohybyModel::$core = Core::get_instance();
        $this->dispatchRequests();
    }
}