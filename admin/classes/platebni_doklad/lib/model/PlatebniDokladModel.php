<?php


class PlatebniDokladModel
{

    //region STATIC MEMBERS **************************************************************
    /**
     * @var User_zamestnanec
     */
    public static $zamestnanec;
    /**
     * @var Core
     */
    public static $core;
    //endregion

    //region PRIVATE MEMBERS *************************************************************
    /**
     * @var IPlatebniDokladModelPdfObserver[]
     */
    public $observersPdf;
    /**
     * @var IPlatebniDokladModelEditObserver[]
     */
    public $observersEdit;
    /**
     * @var IPlatebniDokladModelEmailObserver[]
     */
    public $observersEmail;
    /**
     * @var mPDF
     */
    private $pdfGenerator;
    /**
     * @var array
     */
    private $centralniData;
    /**
     * @return tsZajezd
     */
    public $zajezd;
    /**
     * @return tsObjednavka
     */
    public $objednavka;
    /**
     * @return tsPlatba[]
     */
    public $platby;
    /**
     * @var string[]
     */
    public $platbyPdf;
    /**
     * @var string [] pole emailu
     */
    public $emailsSelected;
    /**
     * @var string nazev pdf souboru
     */
    public $pdfSelected;
    /**
     * @var string identifikator vybraneho klienta ve formatu [typ]_[id]
     */
    public $klientSelected;
    /**
     * @var int[] pole identifikatoru vybranych plateb
     */
    public $platbySelected;

    //endregion

    //region GETTERS & SETTERS ***********************************************************
    public function getCentralniData()
    {
        return $this->centralniData;
    }

    /**
     * @return tsZajezd
     */
    public function getZajezd()
    {
        return $this->zajezd;
    }

    /**
     * @return tsObjednavka
     */
    public function getObjednavka()
    {
        return $this->objednavka;
    }

    /**
     * @return tsPlatba[]
     */
    public function getPlatby()
    {
        return $this->platby;
    }

    /**
     * @return \string[]
     */
    public function getPlatbyPdf()
    {
        return $this->platbyPdf;
    }

    /**
     * @return string
     */
    public function getKlientSelected()
    {
        return $this->klientSelected;
    }

    /**
     * @return \int[]
     */
    public function getPlatbySelected()
    {
        return $this->platbySelected;
    }

    //endregion

    public function registerPdfObserver($observer)
    {
        $this->observersPdf[] = $observer;
    }

    public function registerEditObserver($observer)
    {
        $this->observersEdit[] = $observer;
    }

    public function registerEmailObserver($observer)
    {
        $this->observersEmail[] = $observer;
    }

    public function edit()
    {
        //load data
        $this->loadZajezd();
        $this->loadObjednavka();
        $this->loadPlatby();
        $this->loadPdf();

        //notify view
        $this->notifyEditObservers();
    }

    public function createPdf()
    {
        //save request
        $this->klientSelected = $_REQUEST["klient"];
//        $this->platbySelected = $_REQUEST["platby"];

        //load data
        $this->loadObjednavka();

        //init pdf
        $this->initPdf();

        //notify view
        $this->notifyPdfObservers();
    }

    public function generatePdf($pdfHtml)
    {
        $dateTime = date("Y-m-d-H-i-s");
        $pathToPdf = PlatebniDokladModelConfig::PDF_FOLDER . $this->getObjednavka()->id_objednavka . "_" . $dateTime . ".pdf"; //$this->platba->id_platba

        $this->pdfGenerator->WriteHTML($pdfHtml, 2);
        $this->pdfGenerator->Output();
        $this->pdfGenerator->Output($pathToPdf, 'F');
    }

    public function deletePdf()
    {
        @unlink(PlatebniDokladModelConfig::PDF_FOLDER . $_REQUEST["pdf_filename"]);

        $url = $_SERVER["SCRIPT_NAME"] . "?page=edit&id_objednavka=" . $_REQUEST["id_objednavka"];
        header("Location: $url");
        exit();
    }

    public function deleteAllPdf()
    {
        foreach (glob(PlatebniDokladModelConfig::PDF_FOLDER . $_REQUEST["id_objednavka"] . "*.pdf") as $filename) {
            @unlink($filename);
        }

        $url = $_SERVER["SCRIPT_NAME"] . "?page=edit&id_objednavka=" . $_REQUEST["id_objednavka"];
        header("Location: $url");
        exit();
    }

    public function constructPdfEmails()
    {
        $this->emailsSelected = $_REQUEST["cb-emaily"];
        $this->pdfSelected = $_REQUEST["rb-pdf-voucher"];

        $this->loadObjednavka();
        $this->loadZajezd();

        $this->notifyEmailObservers();
    }

    public function sendPdfEmails($subject, $message)
    {
        if (count((array)$this->emailsSelected) <= 0)
            $out = null;

        $sender = PlatebniDokladModel::$zamestnanec->get_email();

        $out = array();
        foreach ($this->emailsSelected as $e) {
            $isEmailSend = PlatebniDokladUtils::sendEmailWithAttachment($sender, $e, $subject, $message, $this->pdfSelected);
            $out[] = array("email" => $e, "isSend" => $isEmailSend);
        }

        return json_encode($out);
    }

    // PRIVATE METHODS *******************************************************************

    private function initPdf()
    {
        define('_MPDF_PATH', '../mpdf/');
        include_once('../mpdf/mpdf.php');
        $this->pdfGenerator = new mPDF('cs', 'A4', 7, 'DejaVuSans', 8, 8, 5, 5, 1, 1);

        $this->pdfGenerator->keep_table_proportions = true;
        $this->pdfGenerator->allow_charset_conversion = true;
        $this->pdfGenerator->charset_in = 'windows-1250';
        $stylesheet = file_get_contents('./classes/ts/ts_default.css');
        $this->pdfGenerator->WriteHTML($stylesheet, 1);

    }

    private function notifyPdfObservers()
    {
        foreach ($this->observersPdf as $o) {
            $o->modelPdfChanged();
        }
    }

    private function notifyEditObservers()
    {
        foreach ($this->observersEdit as $o) {
            $o->modelEditChanged();
        }
    }

    private function notifyEmailObservers()
    {
        foreach ($this->observersEmail as $o) {
            $o->modelEmailChanged();
        }
    }

    private function loadZajezd()
    {
        $this->zajezd = PlatebniDokladDAO::readZajezdByObjednavkaId($_REQUEST["id_objednavka"]);
    }

    private function loadObjednavka()
    {
        $this->objednavka = PlatebniDokladDAO::readObjednavkaByObjednavkaId($_REQUEST["id_objednavka"]);
    }

    private function loadPlatby()
    {
        $this->platby = PlatebniDokladDAO::readPlatbyByObjednavkaId($_REQUEST["id_objednavka"]);
    }

    private function loadPdf()
    {
        $this->platbyPdf = PlatebniDokladDAO::readPlatbyPdfFiles($_REQUEST["id_objednavka"]);
    }
}