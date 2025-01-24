<?php

class VoucheryController
{
    /**
     * @var VoucheryModel
     */
    private $model;

    function __construct($model)
    {
        $this->model = $model;
        $this->init();
    }

    public function dispatchRequests()
    {
        if (VoucheryModel::$zamestnanec->get_correct_login()) {
            switch ($_REQUEST["page"]) {
                case "edit-voucher":
                    $this->navEditVoucher();
                    break;
                case "create-pdf-voucher":
                    $this->model->createPdf(VoucheryModelConfig::PDF_TYPE_VOUCHER);
                    break;
                case "create-pdf-objednavka-objekt":
                    $this->model->createPdf(VoucheryModelConfig::PDF_TYPE_OBJEDNAVKA_OBJEKT);
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
            VoucheryViewEdit::loginErr();
        }
    }

    // PRIVATE METHODS *******************************************************************
    private function init()
    {
        VoucheryModel::$zamestnanec = User_zamestnanec::get_instance();
        VoucheryModel::$core = Core::get_instance();
    }

    private function navEditVoucher()
    {
        $this->model->objednavkaEdit();
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
