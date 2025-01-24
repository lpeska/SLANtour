<?php

class VyberyController
{
    /**
     * @var VyberyModel
     */
    private $model;

    function __construct($model)
    {
        $this->model = $model;
        $this->init();
    }

    // PUBLIC METHODS ********************************************************************

    public function dispatchRequests()
    {
        if (VyberyModel::$zamestnanec->correct_login()) {
            switch ($_REQUEST["page"]) {
                default:
                case "":
                case "filter-serialy":
                    $this->model->serialy();
                    break;
                case "delete-serial":
                    $this->model->deleteSerial();
                    break;
                case "delete-zajezd":
                    $this->model->deleteZajezd();
                    break;
                case "delete-zajezd-selected":
                    $this->model->deleteZajezdSelected();
                    break;
            }
        } else {
            VyberyView::loginErr();
        }
    }

    // PRIVATE METHODS *******************************************************************

    private function init()
    {
        VyberyModel::$zamestnanec = User_zamestnanec::get_instance();
        VyberyModel::$core = Core::get_instance();
    }

}