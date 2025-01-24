<?php

class VoucheryModel
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
     * @var IVoucheryModelEditObserver[]
     */
    private $observersEdit;
    /**
     * @var IVoucheryModelPdfObserver[]
     */
    private $observersPdf;
    /**
     * @var IVoucheryModelEmailObserver[]
     */
    private $observersEmail;
    /**
     * @var VoucheryObjednavkaHolder
     */
    private $objednavkaHolder;
    /**
     * @var mPDF
     */
    private $pdfGenerator;
    /**
     * @var tsSluzba[]
     */
    private $sluzbySelected;
    /**
     * @var tsObjekt
     */
    private $objektSelected;
    /**
     * @var string [] pole emailu
     */
    private $emailsSelected;
    /**
     * @var string nazev pdf souboru
     */
    private $voucherPdfSelected;
    /**
     * @var string text emailu
     */
    private $emailText;
    /**
     * @var string
     */
    private $poznamkaEditor;
    /**
     * @var string
     */
    private $cenaZahrnuje;
    /**
     * @var string
     */
    private $language;

    //endregion

    //region GETTERS & SETTERS ***********************************************************
    /**
     * @return \tsSluzba[]
     */
    public function getSluzbySelected()
    {
        return $this->sluzbySelected;
    }

    /**
     * @return tsObjekt
     */
    public function getObjektSelected()
    {
        return $this->objektSelected;
    }

    /**
     * @return \VoucheryObjednavkaHolder
     */
    public function getObjednavkaHolder()
    {
        return $this->objednavkaHolder;
    }

    /**
     * @return \mPDF
     */
    public function getPdfGenerator()
    {
        return $this->pdfGenerator;
    }

    /**
     * @return string
     */
    public function getPoznamkaEditor()
    {
        return $this->poznamkaEditor;
    }

    /**
     * @return string
     */
    public function getCenaZahrnuje()
    {
        return $this->cenaZahrnuje;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getEmailText()
    {
        return $this->emailText;
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

    public function registerEmailObserver($observer)
    {
        $this->observersEmail[] = $observer;
    }

    public function objednavkaEdit()
    {
        $this->loadObjednavka();
        $this->notifyEditObservers();
    }

    public function createPdf($type)
    {
        //lang
        $this->parseLanguage();

        $this->loadObjednavka();

        //zpracuj/uloz $_REQUEST
        $this->parseSluzbySelected();
        $this->parseObjektoveKategorieSelected();
        $this->parseObjektSelected();
        $this->parseUcastniciSelected();
        $this->poznamkaEditor = $_REQUEST["poznamka-editor"];
        $this->cenaZahrnuje = $_REQUEST["cena-zahrnuje"];

        $this->initPdf();

        if($type == VoucheryModelConfig::PDF_TYPE_VOUCHER)
            $this->notifyPdfVoucherObservers();
        else if($type == VoucheryModelConfig::PDF_TYPE_OBJEDNAVKA_OBJEKT)
            $this->notifyPdfObjednavkaObjektObservers();
    }

    public function generatePdf($pdfHtml, $typ)
    {
        $idObjednavka = $this->objednavkaHolder->getObjednavka()->id_objednavka;
        $dateTime = date("Y-m-d-H-i-s");
        if($typ == VoucheryModelConfig::PDF_TYPE_VOUCHER)
            $typString = $this->isObjednavkaObjekt() ? "objekt" : "zajezd";
        else if($typ == VoucheryModelConfig::PDF_TYPE_OBJEDNAVKA_OBJEKT)
            $typString = "objednavka-objekt";
        $pathToPdf = VoucheryModelConfig::PDF_FOLDER . $idObjednavka . "_" . $dateTime . "_" . $typString . ".pdf";

        $this->pdfGenerator->WriteHTML($pdfHtml, 2);
        $this->pdfGenerator->Output();
        $this->pdfGenerator->Output($pathToPdf, 'F');
    }

    public function getObjektEmailById($idObjekt)
    {
        $this->loadObjednavka();

        foreach ($this->objednavkaHolder->getObjekty() as $objekt) {
            if ($objekt->id == $idObjekt)
                return $objekt->email;
        }
        return null;
    }

    public function constructPdfEmails()
    {
        $this->loadObjednavka();

        $this->emailsSelected = $_REQUEST["cb-emaily"];
        $this->voucherPdfSelected = $_REQUEST["rb-pdf-voucher"];
        $this->emailText = $_REQUEST["email-text"];
        $this->notifyEmailObservers();
    }

    public function sendPdfEmails($subject, $message)
    {
        if (count((array)$this->emailsSelected) <= 0)
            $out = null;

        $sender = VoucheryModel::$zamestnanec->get_email();

        $out = array();
        for ($i = 0; $i < count((array)$this->emailsSelected); $i++) {
            $e = $this->emailsSelected[$i];
            $isEmailSend = VoucheryUtils::sendEmailWithAttachment($sender, $e, $subject, $message, $this->voucherPdfSelected);
            $out[] = array("email" => $e, "isSend" => $isEmailSend);
        }

        return json_encode($out);
    }

    /**
     * Vraci true pokud je objednavka typu objekt
     * @return bool
     */
    public function isObjednavkaObjekt()
    {
        $objekty = $this->objednavkaHolder->getObjekty();
        if (!is_null($objekty) && count((array)$objekty) > 0)
            return true;
        return false;
    }

    public function deletePdf()
    {
        @unlink(VoucheryModelConfig::PDF_FOLDER . $_REQUEST["pdf_filename"]);

        $url = $_SERVER["SCRIPT_NAME"] . "?page=edit-voucher&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"];
        header("Location: $url");
        exit();
    }

    public function deleteAllPdf()
    {
        foreach (glob(VoucheryModelConfig::PDF_FOLDER . $_REQUEST["id_objednavka"] . "*.pdf") as $filename) {
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
            $o->modelEditChanged();
        }
    }

    private function notifyPdfVoucherObservers()
    {
        foreach ($this->observersPdf as $o) {
            $o->modelPdfVoucherChanged();
        }
    }

    private function notifyPdfObjednavkaObjektObservers()
    {
        foreach ($this->observersPdf as $o) {
            $o->modelPdfObjednavkaObjektChanged();
        }
    }

    private function notifyEmailObservers()
    {
        foreach ($this->observersEmail as $o) {
            $o->modelEmailChanged();
        }
    }

    /**
     * Ulozi sluzby, ktere byly vybrany
     */
    private function parseSluzbySelected()
    {
        $sluzbyIdsSelected = $_REQUEST["cb-sluzby"];
        foreach ($this->objednavkaHolder->getSluzby() as $sluzba) {
            if (@in_array($sluzba->id_cena, $sluzbyIdsSelected)) {
                $this->sluzbySelected[] = $sluzba;
            }
        }
    }

    /**
     * Do objektu $this->objednavkaHolder->sluzby ulozi vybrane OK
     */
    private function parseObjektoveKategorieSelected()
    {
        $vybraneSluzby = $this->sluzbySelected;

        //projdi request (key = id_cena, value = index OK v poli objektovych kategorii tsSluzba->objektoveKategorie)
        foreach ($_REQUEST as $idCena => $index) {
            //pokud naleznes vybrane OK, vytahni z nich id sluzby, ke ktere patri
            if (strpos($idCena, "rb-ok-") === 0) {
                $idSluzba = str_replace("rb-ok-", "", $idCena);
                //nalezni sluzbu, ke ktere vybrana OK patri a z atributu objektoveKategorie vyber tu, ktera byla
                //vybrana a nastav ji jako vybranaObjektovaKategorie
                foreach ((array)$vybraneSluzby as $s) {
                    if ($s->id_cena == $idSluzba)
                        $s->vybranaObjektovaKategorie = $s->objektoveKategorie[$index];
                }
            }
        }
    }

    /**
     * Ulozi vybrany objekt
     */
    private function parseObjektSelected()
    {
        $objekty = $this->getObjednavkaHolder()->getObjekty();
        $idObjekt = $_REQUEST["rb-objekt"];

        for ($i = 0; $i < count((array)$objekty); $i++) {
            if ($objekty[$i]->id == $idObjekt) {
                $this->objektSelected = $objekty[$i];
            }
        }
    }

    /**
     * Do objektu $this->objednavkaHolder->sluzby ulozi vybrane ucastniky
     */
    private function parseUcastniciSelected()
    {
        $ucastnici = $this->objednavkaHolder->getUcastnici();
        $vybraneSluzby = $this->sluzbySelected;

        //projdi request (key = id_cena, value = id_osoba)
        foreach ($_REQUEST as $idCena => $idOsoby) {
            //pokud naleznes vybrane ucastniky, vytahni z nich id sluzby, ke ktere patri
            if (strpos($idCena, "cb-ucastnici-") === 0) {
                $idSluzba = str_replace("cb-ucastnici-", "", $idCena);
                //nalezni sluzbu, ke ktere vybrany ucastnik patri, pak projdi id vybranych ucastniku zaroven
                //se vsemi ucastniky a pridej je do pole ucastniku (tsSluzba->vybraniUcastnici) dane sluzby
                foreach ((array)$vybraneSluzby as $s) {
                    if ($s->id_cena == $idSluzba) {
                        $s->pocet = $_REQUEST["sluzba-pocet-$idSluzba"];
                        $s->nazev_ceny = $_REQUEST["sluzba-nazev-$idSluzba"];
                        foreach ($idOsoby as $idOsoba) {
                            foreach ($ucastnici as $u) {
                                if ($u->id == $idOsoba) {
                                    $s->vybraniUcastnici[] = $u;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    private function loadObjednavka()
    {
        //jen pokud jiz neni nactena
        if (is_null($this->objednavkaHolder))
            $this->objednavkaHolder = new VoucheryObjednavkaHolder($_REQUEST["id_objednavka"], $_REQUEST["security_code"], $this->language);
    }

    private function initPdf()
    {
        define('_MPDF_PATH', '../mpdf/');
        include_once('../mpdf/mpdf.php');
        $this->pdfGenerator = new mPDF('cs', 'A4', 7, 'dejavusans', 8, 8, 5, 5, 1, 1);

        $this->pdfGenerator->keep_table_proportions = true;
        $this->pdfGenerator->allow_charset_conversion = true;
        $this->pdfGenerator->charset_in = 'windows-1250';
        $stylesheet = file_get_contents('./classes/ts/ts_default.css');
        $this->pdfGenerator->WriteHTML($stylesheet, 1);
    }

    private function parseLanguage()
    {
        if ($_REQUEST["language"] == "") {
            $this->language = VoucheryModelConfig::LANG_CS;
        } else {
            $this->language = $_REQUEST["language"];
        }
    }
}