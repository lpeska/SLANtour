<?php

class ObratyProdejcuModel {

    static $zamestnanec;
    static $core;

    /** @var  IOPObserver */
    private $opObservers;
    /** @var  mPDF */
    private $pdfGenerator;

    /** @var  OrganizaceHolder */
    public $organizace;

    //region GETTERS/SETTERS *************************************************************
    public function getZeme()
    {
        return $this->zeme;
    }

    /**
     * @return \SerialTypEnt[]
     */
    public function getSerialTypList()
    {
        return $this->serialTypList;
    }

    /**
     * @return \SerialHolder
     */
    public function getSerialHolder()
    {
        return $this->serialHolder;
    }

    /**
     * @return \SerialFilter
     */
    public function getSerialFilter()
    {
        return $this->serialFilter;
    }

    //endregion

    public function registerFPObserver($observer)
    {
        $this->opObservers[] = $observer;
    }

    public function prehled()
    {
        //dao
        $this->loadPrehled();

        $this->notifyPrehledObservers();
    }

    public function prehledPdf()
    {
        //dao
        $this->loadPrehled();

        //pdf
        $this->initPdf();

        $this->notifyPrehledPdfObservers();
    }

    public function generatePdf($pdfHtml)
    {
        $dateTime = date("Y-m-d-H-i-s");
        $pathToPdf = "./classes/financni_pohyby/res/temp-pdf/obraty_prodejcu_" . $dateTime . ".pdf";

        $this->pdfGenerator->WriteHTML($pdfHtml, 2);
        $this->pdfGenerator->Output();
    }

    //region PRIVATE METHODS ******************************************************************

    private function notifyPrehledObservers()
    {
        if (is_null($this->opObservers))
            return;

        foreach ($this->opObservers as $o) {
            $o->prehledChanged();
        }
    }

    private function notifyPrehledPdfObservers()
    {
        if (is_null($this->opObservers))
            return;

        foreach ($this->opObservers as $o) {
            $o->prehledPdfChanged();
        }
    }

    private function loadPrehled()
    {
        $dateOd = $_REQUEST['dateOd'] == '' ? '1970-01-01' : $_REQUEST['dateOd'];
        $dateDo = $_REQUEST['dateDo'] == '' ? '9999-12-31' : $_REQUEST['dateDo'];
        $orgList = ObratyProdejcuDAO::readOrganizaceList($_REQUEST['role'], $_REQUEST['dateType'], CommonUtils::engDate($dateOd), CommonUtils::engDate($dateDo));

        if (is_null($orgList)) {
            $this->organizace = new OrganizaceHolder($orgList);
            return;
        }

        //sort by obrat
        if ($_REQUEST['sortBy'] == 'obrat') {
            usort($orgList, function ($a, $b) {
                $aCena = $a->getObjednavkaHolder()->calcFinalniCena();
                $bCena = $b->getObjednavkaHolder()->calcFinalniCena();
                return ($aCena < $bCena) ? 1 : (($aCena > $bCena) ? -1 : 0);
            });
        }

        //zahrnout nulovy obrat
        if ($_REQUEST['includeZero'] != 1) {
            foreach ($orgList as $key => $org) {
                if ($org->getObjednavkaHolder()->calcFinalniCena() == 0)
                    unset($orgList[$key]);
            }
        }

        $this->organizace = new OrganizaceHolder($orgList);
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
    //endregion


}