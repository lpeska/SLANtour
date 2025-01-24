<?php

class SeznamyUcastnikuModel
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
     * @var ISeznamyUcastnikuModelSerialyObserver[]
     */
    private $observersSerialy;
    /**
     * @var ISeznamyUcastnikuModelZajezdyObserver[]
     */
    public $observersZajezdy;
    /**
     * @var ISeznamyUcastnikuModelUcastniciObserver[]
     */
    public $observersUcastnici;
    /**
     * @var ISeznamyUcastnikuModelPdfObserver[]
     */
    public $observersPdf;
    /**
     * @var ISeznamyUcastnikuModelEmailObserver[]
     */
    private $observersEmail;
    /**
     * @var SerialHolder
     */
    private $serialHolder;
    /**
     * @var SerialFilter
     */
    private $serialFilter;
    /**
     * @var mPDF
     */
    private $pdfGenerator;
    /**
     * @var tsZeme[]
     */
    private $zeme;
    /**
     * @var array
     */
    private $centralniData;
    /**
     * @var string [] pole emailu
     */
    private $emailsSelected;
    /**
     * @var string nazev pdf souboru
     */
    private $seznamyUcastnikuPdfSelected;
    /**
     * @var string[]
     */
    private $pdfUcastnici;

    //endregion

    //region GETTERS & SETTERS ***********************************************************
    /**
     * @return \SerialHolder
     */
    public function getSerialHolder()
    {
        return $this->serialHolder;
    }

    /**
     * @return SerialFilter
     */
    public function getSerialFilter()
    {
        return $this->serialFilter;
    }

    /**
     * @return \tsZeme[]
     */
    public function getZeme()
    {
        return $this->zeme;
    }

    /**
     * @return array
     */
    public function getCentralniData()
    {
        return $this->centralniData;
    }

    /**
     * @return string[]
     */
    public function getPdfUcastnici()
    {
        return $this->pdfUcastnici;
    }


    //endregion

    // PUBLIC METHODS ********************************************************************
    public function registerSerialyObserver($observer)
    {
        $this->observersSerialy[] = $observer;
    }

    public function registerZajezdyObserver($observer)
    {
        $this->observersZajezdy[] = $observer;
    }

    public function registerUcastniciObserver($observer)
    {
        $this->observersUcastnici[] = $observer;
    }

    public function registerPdfObserver($observer)
    {
        $this->observersPdf[] = $observer;
    }

    public function registerEmailObserver($observer)
    {
        $this->observersEmail[] = $observer;
    }

    public function serialy()
    {
        $filterValues = $this->loadFilter();
        $filterValues->setSerialIds($_REQUEST["filter_id"]);
        $filterValues->setSerialNazev($_REQUEST["filter_nazev"]);
        $filterValues->setSerialZeme($_REQUEST["filter_zeme"]);
        $filterValues->setPagingPage($_REQUEST["p"]);
        $filterValues->saveFilter(SeznamyUcastnikuModelConfig::FILTER_NAME);
        $this->serialFilter = $filterValues;

        $this->zeme = SeznamyUcastnikuDAO::dataZeme();
        $this->loadSerialy();

        $this->notifySerialyObservers();
    }

    public function zajezdy()
    {
        $filterValues = $this->loadFilter();
        $filterValues->setSerialIdsSelected($_REQUEST["cb-serialy"]);
        $filterValues->setZajezdNovejsiNez(CommonUtils::engDate($_REQUEST["filter-novejsi-nez"]));
        //note finta fò - u checkboxu neni poznat zda prisel nezaskrnuty nebo neprisel zadny, tak si pomaham textovym inputem, u ktereho ano
        if (isset($_REQUEST["filter-novejsi-nez"]))
            $filterValues->setZajezdSkrytProsle($_REQUEST["filter-skryt-prosle"]);
        $filterValues->saveFilter(SeznamyUcastnikuModelConfig::FILTER_NAME);
        $this->serialFilter = $filterValues;

        $this->loadZajezdy();

        $this->notifyZajezdyObservers();
    }

    public function ucastnici()
    {
        $filterValues = $this->loadFilter();
        $filterValues->setZajezdIdsSelected($_REQUEST["cb-zajezdy"]);
        $filterValues->saveFilter(SeznamyUcastnikuModelConfig::FILTER_NAME);
        $this->serialFilter = $filterValues;

        $this->loadUcastnici();

        $this->notifyUcastniciObservers();
    }

    public function createPdf()
    {
        $filterValues = $this->loadFilter();
        $filterValues->setUcastniciIdsSelected($_REQUEST["cb-ucastnici"]);
        $filterValues->setObjednavkyIdsSelected($_REQUEST["cb-objednavky"]);
        $filterValues->setZajezdSetup();
        $filterValues->setObjednavkaSetup();
        $filterValues->saveFilter(SeznamyUcastnikuModelConfig::FILTER_NAME);
        $this->serialFilter = $filterValues;

        $this->centralniData = SeznamyUcastnikuDAO::dataCentralniData();
        $this->loadUcastnici();

        $this->initPdf();

        $this->notifyPdfObservers();
    }

    public function generatePdf($pdfHtml)
    {
        $pdfPrefix = SeznamyUcastnikuUtils::generatePdfPrefix($this->loadFilter());
        $dateTime = date("Y-m-d-H-i-s");
        $pathToPdf = SeznamyUcastnikuModelConfig::PDF_FOLDER . $pdfPrefix . "_" . $dateTime . ".pdf";

        $this->pdfGenerator->WriteHTML($pdfHtml, 2);
        $this->pdfGenerator->Output();
        $this->pdfGenerator->Output($pathToPdf, 'F');
    }

    public function deletePdf()
    {
        @unlink(SeznamyUcastnikuModelConfig::PDF_FOLDER . $_REQUEST["pdf_filename"]);

        $url = $_SERVER["SCRIPT_NAME"] . "?page=ucastnici";
        header("Location: $url");
        exit();
    }

    public function deleteAllPdf()
    {
        foreach (glob(SeznamyUcastnikuModelConfig::PDF_FOLDER . $_REQUEST["hash"] . "*.pdf") as $filename) {
            @unlink($filename);
        }

        $url = $_SERVER["SCRIPT_NAME"] . "?page=ucastnici";
        header("Location: $url");
        exit();
    }

    public function constructPdfEmails()
    {
        $filterValues = $this->loadFilter();
        $this->serialFilter = $filterValues;
        $this->loadUcastnici();

        $this->emailsSelected = $_REQUEST["cb-emaily"];
        $this->seznamyUcastnikuPdfSelected = $_REQUEST["rb-pdf-voucher"];
        $this->notifyEmailObservers();
    }

    public function sendPdfEmails($subject, $message)
    {
        if (count((array)$this->emailsSelected) <= 0)
            $out = null;

        $sender = SeznamyUcastnikuModel::$zamestnanec->get_email();

        $out = array();
        for ($i = 0; $i < count((array)$this->emailsSelected); $i++) {
            $e = $this->emailsSelected[$i];
            $isEmailSend = SeznamyUcastnikuUtils::sendEmailWithAttachment($sender, $e, $subject, $message, $this->seznamyUcastnikuPdfSelected);
            $out[] = array("email" => $e, "isSend" => $isEmailSend);
        }

        return json_encode($out);
    }

    // PRIVATE METHODS *******************************************************************
    private function notifySerialyObservers()
    {
        foreach ($this->observersSerialy as $o) {
            $o->modelSerialyChanged();
        }
    }

    private function notifyZajezdyObservers()
    {
        foreach ($this->observersZajezdy as $o) {
            $o->modelZajezdyChanged();
        }
    }

    private function notifyUcastniciObservers()
    {
        foreach ($this->observersUcastnici as $o) {
            $o->modelUcastniciChanged();
        }
    }

    private function notifyPdfObservers()
    {
        foreach ($this->observersPdf as $o) {
            $o->modelPdfChanged();
        }
    }

    private function notifyEmailObservers()
    {
        foreach ($this->observersEmail as $o) {
            $o->modelEmailChanged();
        }
    }

    private function loadSerialy()
    {
        if (is_null($this->serialHolder)) {
            $this->serialHolder = new SerialHolder($this->serialFilter);
            $dbResult = SeznamyUcastnikuDAO::readSerialListFiltered($this->serialFilter);
            $this->serialHolder->setSerialyList($dbResult->object);
            $this->serialHolder->setFoundRows($dbResult->foundRows);

            //po tom co nactu serialy jste musim spocitat strankovani
            $this->serialFilter->calculatePaging($this->serialHolder->getFoundRows());
        }
    }

    private function loadZajezdy()
    {
        if (is_null($this->serialHolder)) {
            $this->serialHolder = new SerialHolder($this->serialFilter);
            $this->serialHolder->setSerialyList(SeznamyUcastnikuDAO::readSerialListWithZajezdy($this->serialFilter));
        }
    }

    private function loadUcastnici()
    {
        if (is_null($this->serialHolder)) {
            $this->serialHolder = new SerialHolder($this->serialFilter);
            $this->serialHolder->setSerialyList(SeznamyUcastnikuDAO::readSerialListWithZajezdyAndUcastnici($this->serialFilter));
            $this->pdfUcastnici = SeznamyUcastnikuDAO::readPdfList($this->serialFilter);
        }
    }

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

    /**
     * @return bool|SerialFilter
     */
    private function loadFilter() {
        return SerialFilter::loadFilter(SeznamyUcastnikuModelConfig::FILTER_NAME, SeznamyUcastnikuModelConfig::SERIALY_PAGING_MAX_PAGES, SeznamyUcastnikuModelConfig::SERIALY_PAGING_MAX_PER_PAGE);
    }
}