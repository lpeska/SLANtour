<?php

class ObratyProdejcuController
{

    /**
     * @var ObratyProdejcuModel
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
        ObratyProdejcuModel::$zamestnanec = User_zamestnanec::get_instance();
        ObratyProdejcuModel::$core = Core::get_instance();
        $this->dispatchRequests();
    }
}