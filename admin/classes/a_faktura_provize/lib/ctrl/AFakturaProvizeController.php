<?php

class AFakturaProvizeController
{

    /**
     * @var AFakturaProvizeModel
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
            case "faktury":
                $this->faktury();
                break;
        }
    }

    private function init()
    {
        AFakturaProvizeModel::$zamestnanec = User_zamestnanec::get_instance();
        AFakturaProvizeModel::$core = Core::get_instance();
        $this->dispatchRequests();
    }

    private function faktury()
    {
        switch($_REQUEST["action"]) {
            default:
            case "zobrazit":
                $this->model->faktury();
                break;
            case "zaplatit":
                $this->model->zaplatitFakturu();
                break;
        }
    }
}