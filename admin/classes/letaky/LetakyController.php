<?php

class LetakyController
{
    /**
     * @var LetakyModel
     */
    private $model;

    function __construct($model)
    {
        $this->model = $model;
        $this->init();
    }

    public function dispatchRequests()
    {
        if (LetakyModel::$zamestnanec->get_correct_login()) {
            switch ($_REQUEST["page"]) {
                case "edit-settings":
                    $this->editSettings();
                    break;
                case "create-pdf":
                    $this->model->createPdf();
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
            LetakyViewEdit::loginErr();
        }
    }

    // PRIVATE METHODS *******************************************************************
    private function init()
    {
        LetakyModel::$zamestnanec = User_zamestnanec::get_instance();
        LetakyModel::$core = Core::get_instance();
    }

    private function editSettings()
    {
        $this->model->letakyEditSettings();//todo
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
            case "get-obj-email":
                echo $this->model->getObjektEmailById($_REQUEST["id_objekt"]);
                break;
            case "send-emails":
                $this->model->constructPdfEmails();
                break;
        }
    }
}
