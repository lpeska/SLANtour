<?php


class PlatebniDokladController
{

    /**
     * @var PlatebniDokladModel
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
        if (PlatebniDokladModel::$zamestnanec->get_correct_login()) {
            switch ($_REQUEST["page"]) {
                default:
                case "edit":
                    $this->model->edit();
                    break;
                case "create-pdf":
                    $this->model->createPdf();
                    break;
                case "delete-pdf":
                    $this->model->deletePdf();
                    break;
                case "delete-all-pdf":
                    $this->model->deleteAllPdf();
                    break;
                case "ajax":
                    $this->navAjax();
                    break;
            }
        } else {
            PlatebniDokladView::loginErr();
        }
    }

    // PRIVATE METHODS *******************************************************************

    private function init()
    {
        PlatebniDokladModel::$zamestnanec = User_zamestnanec::get_instance();
        PlatebniDokladModel::$core = Core::get_instance();
    }

    private function navAjax()
    {
        switch($_REQUEST["action"]) {
            case "send-emails":
                $this->model->constructPdfEmails();
                break;
        }
    }

}