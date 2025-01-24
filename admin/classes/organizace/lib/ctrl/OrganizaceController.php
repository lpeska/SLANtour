<?php

class OrganizaceController {

    /**
     * @var OrganizaceModel
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
            case "prodejce-objednavky":
                $this->prodejceObjednavky();
                break;
        }
    }

    private function init()
    {
        OrganizaceModel::$zamestnanec = User_zamestnanec::get_instance();
        OrganizaceModel::$core = Core::get_instance();
        $this->dispatchRequests();
    }

    private function prodejceObjednavky()
    {
        switch($_REQUEST["action"]) {
            default:
            case "list":
                $this->model->prodejceObjednavkaList();
                break;
        }
    }

}