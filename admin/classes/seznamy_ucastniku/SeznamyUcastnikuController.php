<?php

class SeznamyUcastnikuController
{
    /**
     * @var SeznamyUcastnikuModel
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
        if (SeznamyUcastnikuModel::$zamestnanec->get_correct_login()) {
            switch ($_REQUEST["page"]) {
                case "serialy":
                default:
                    $this->navSerialy();
                    break;
                case "zajezdy":
                    $this->navZajezdy();
                    break;
                case "ucastnici":
                    $this->navUcastnici();
                    break;
                case "create-pdf":
                    $this->navCreatePdf();
                    break;
                case "delete-pdf":
                    $this->navDeletePdf();
                    break;
                case "delete-all-pdf":
                    $this->navDeleteAllPdf();
                    break;
                case "ajax":
                    $this->navAjax($_REQUEST["action"]);
            }
        } else {
            SeznamyUcastnikuView::loginErr();
        }
    }

    // PRIVATE METHODS *******************************************************************

    private function init()
    {
        SeznamyUcastnikuModel::$zamestnanec = User_zamestnanec::get_instance();
        SeznamyUcastnikuModel::$core = Core::get_instance();
    }

    private function navSerialy()
    {
        switch ($_REQUEST["action"]) {
            case "filter":
            default:
                $this->model->serialy();
                break;
        }
    }

    private function navZajezdy()
    {
        switch ($_REQUEST["action"]) {
            case "filter":
            default:
                $this->model->zajezdy();
                break;
        }
    }

    private function navUcastnici()
    {
        switch ($_REQUEST["action"]) {
            default:
                $this->model->ucastnici();
                break;
        }
    }

    private function navCreatePdf()
    {
        switch ($_REQUEST["action"]) {
            default:
                $this->model->createPdf();
                break;
        }
    }

    private function navDeletePdf()
    {
        $this->model->deletePdf();
    }

    private function navDeleteAllPdf()
    {
        $this->model->deleteAllPdf();
    }

    private function navAjax($action)
    {
        switch ($action) {
            case "send-emails":
                $this->model->constructPdfEmails();
                break;
        }
    }

}