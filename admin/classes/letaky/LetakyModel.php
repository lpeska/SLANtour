<?php

class LetakyModel
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
     * @var ILetakyModelEditObserver[]
     */
    private $observersEdit;
    /**
     * @var ILetakyModelPdfObserver[]
     */
    private $observersPdf;

    /**
     * @var LetakyObjednavkaHolder
     */
    private $zajezdyHolder;
    
    private $filter;
    
    private $sablonyList;
    /**
     * @var mPDF
     */
    private $pdfGenerator;
    /**
     * @var tsSluzba[]
     */
   
    private $letakPdfSelected;


    //endregion

    //region GETTERS & SETTERS ***********************************************************
    function getFilter() {
        return $this->filter;
    }

        /**
     * @return \LetakyObjednavkaHolder
     */
    public function getZajezdHolder()
    {
        return $this->zajezdyHolder;
    }

    public function getSablonyList()
    {
        return $this->sablonyList;
    }
    
    /**
     * @return \mPDF
     */
    public function getPdfGenerator()
    {
        return $this->pdfGenerator;
    }

    //endregion

    // PUBLIC METHODS ********************************************************************
    public function registerEditObserver($observer)
    {
        $this->observersEdit[] = $observer;
    }

    public function registerPdfObserver($observer)
    {
        $this->observersPdf[] = $observer;
    }


    public function letakyEditSettings()
    {
        $this->loadFilter();
        $this->loadZajezdy();
        $this->sablonyList = array("Základní letak", "Vodorovné fotografie", "Seznam termínù", "Vodorovné fotografie se seznamem termínù");
        $this->notifyEditObservers();
    }

    public function createPdf()
    {
        $this->loadFilter();
        $this->loadZajezdy();

        //zpracuj/uloz $_REQUEST
        //$this->parseSluzbySelected();

        $this->initPdf();
        $this->notifyPdfObservers();
    }

    public function generatePdf($pdfHtml)
    {
        //$idObjednavka = $this->zajezdyHolder->getObjednavka()->id_objednavka;
        $dateTime = Date("Y-m-d_H-i-s");
        $pathToPdf = LetakyModelConfig::PDF_FOLDER . "letak_". $dateTime . ".pdf";

        @$this->pdfGenerator->WriteHTML($pdfHtml, 2);
        $this->pdfGenerator->Output();
        $this->pdfGenerator->Output($pathToPdf, 'F');
    }

    public function constructPdfEmails()
    {
        $this->loadZajezdy();

        $this->emailsSelected = $_REQUEST["cb-emaily"];
        $this->letakPdfSelected = $_REQUEST["rb-pdf-voucher"];
        $this->emailText = $_REQUEST["email-text"];
        $this->notifyEmailObservers();
    }

    public function deletePdf()//todo
    {
        @unlink(LetakyModelConfig::PDF_FOLDER . $_REQUEST["pdf_filename"]);

        $url = $_SERVER["SCRIPT_NAME"] . "?page=edit-voucher&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"];
        header("Location: $url");
        exit();
    }

    public function deleteAllPdf()//todo
    {
        foreach (glob(LetakyModelConfig::PDF_FOLDER . $_REQUEST["id_objednavka"] . "*.pdf") as $filename) {
            @unlink($filename);
        }

        $url = $_SERVER["SCRIPT_NAME"] . "?page=edit-voucher&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"];
        header("Location: $url");
        exit();
    }

    // PRIVATE METHODS *******************************************************************
    private function notifyEditObservers()
    {
        foreach ($this->observersEdit as $o) {
            $o->modelEditLetakyChanged();
        }
    }

    private function notifyPdfObservers()
    {
        foreach ($this->observersPdf as $o) {
            $o->modelPdfLetakyChanged();
        }
    }

    private function notifyEmailObservers()
    {
        foreach ($this->observersEmail as $o) {
            $o->modelEmailChanged();
        }
    }

    private function loadZajezdy()//todo
    {
        //jen pokud jiz neni nactena
        if (is_null($this->zajezdyHolder))
            $this->zajezdyHolder = new LetakyZajezdHolder($_REQUEST["id_serial"]);
    }
    private function loadFilter()//todo
    {
       $f = LetakyFilter::loadFilter(LetakyModelConfig::FILTER_NAME."_".$_REQUEST["id_serial"], 0, 0);
       if(isset($_REQUEST["typ_sablony"])){
           //aktualizace filteru, obnovim vsechny hodnoty dle zadani, navic zadam ze uz neni prazdny (maji se pouzivat hodnoty filteru a ne defaultni nastaveni)
           $f->setEmpty(false);
           $f->setUsePopisek(isset($_REQUEST["checkbox_popisek"]) ? true : false);
           $f->setUseProgram(isset($_REQUEST["checkbox_program"]) ? true : false);
           $f->setUseCenaZahrnuje(isset($_REQUEST["checkbox_cena_zahrnuje"]) ? true : false);
           $f->setUseCenanezahrnuje(isset($_REQUEST["checkbox_cena_nezahrnuje"]) ? true : false);
           $f->setUseDalsiSluzby(isset($_REQUEST["checkbox_dalsi_sluzby"]) ? true : false);
           $f->setUseOdjezdovaMista(isset($_REQUEST["checkbox_odjezdova_mista"]) ? true : false);
           $f->setUseMezeraHeadery(isset($_REQUEST["checkbox_mezery_nadpisy"]) ? true : false);
           $f->setVynechatZakladniCenu(isset($_REQUEST["checkbox_vynechat_zakladni_cenu"]) ? true : false);
           
           $f->setTypSablony($_REQUEST["typ_sablony"]);
           $f->setFotoIDs($_REQUEST["checkbox_foto"]);
           $f->setZajezdyIDs($_REQUEST["checkbox_zajezdy"]);
           $f->setSluzbyIDs($_REQUEST["checkbox_sluzby"]);
           $f->setSlevyIDs($_REQUEST["checkbox_slevy"]);
           
           $f->setPreheaderText($_REQUEST["text_preheader"]);
           $f->setPreheaderStyle($_REQUEST["styl_preheader"]);
           $f->setHeaderText($_REQUEST["text_nadpis_letaku"]);
           $f->setHeaderStyle($_REQUEST["styl_nadpis_letaku"]);
           $f->setNazevSerialuText($_REQUEST["text_nazev_serialu"]);
           $f->setNazevSerialuStyle($_REQUEST["styl_nazev_serialu"]);
           $f->setHighlightsText($_REQUEST["text_highlights"]);
           $f->setHighlightsStyle($_REQUEST["styl_highlights"]);
           $f->setDatumText($_REQUEST["text_datum"]);
           $f->setDatumStyle($_REQUEST["styl_datum"]);
           $f->setCenaText($_REQUEST["text_cena"]);
           $f->setcenaStyle($_REQUEST["styl_cena"]);
           $f->setFooterText($_REQUEST["text_footer"]);
           
           $f->saveFilter(LetakyModelConfig::FILTER_NAME."_".$_REQUEST["id_serial"]);
       }

       $this->filter = $f;
    }
    private function initPdf()
    {
        define('_MPDF_PATH', '../mpdf/');
        include_once('../mpdf/mpdf.php');
        $this->pdfGenerator = new mPDF('cs', 'A4', 7, 'dejavusans', 8, 8, 5, 5, 1, 1);

        $this->pdfGenerator->keep_table_proportions = true;
        $this->pdfGenerator->allow_charset_conversion = true;
        $this->pdfGenerator->charset_in = 'windows-1250';
        $stylesheet = file_get_contents('./classes/letaky/css/letaky_default.css');
        $this->pdfGenerator->WriteHTML($stylesheet, 1);
    }


}