<?php

class FinancniPohybyModel
{

    static $zamestnanec;
    static $core;

    /** @var  SerialFilter */
    public $serialFilter;
    /** @var  ZemeEnt[] */
    public $zeme;
    /** @var  SerialTypEnt[] */
    public $serialTypList;
    /** @var  IFPObserver */
    private $fpObservers;
    /** @var  SerialHolder */
    private $serialHolder;
    /** @var  mPDF */
    private $pdfGenerator;

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
        $this->fpObservers[] = $observer;
    }

    public function serialList()
    {
        //print_r($_REQUEST);
        //filter
        $filterValues = $this->loadFilter();
        $filterValues->setZajezdIdsSelected(null); //reset selected zajezd ids
        $filterValues->setSerialIds($_REQUEST["filter_id"]);
        $filterValues->setSerialNazev($_REQUEST["filter_nazev"]);
        $filterValues->setSerialTyp($_REQUEST["filter_serial_typ"]);
        $filterValues->setSerialZeme($_REQUEST["filter_zeme"]);
        if (isset($_REQUEST["btn_change_filter"])) {
            $filterValues->setSerialNoZajezd($_REQUEST["filter_serial_no_zajezd"]);
            $filterValues->setSerialNoAktivniZajezd($_REQUEST["filter_serial_no_aktivni_zajezd"]);
            $filterValues->setSerialAktivniZajezd($_REQUEST["filter_serial_aktivni_zajezd"]);
            $filterValues->setZajezdObjednavka($_REQUEST["filter_zajezd_objednavka"]);
            $filterValues->setZajezdNoObjednavka($_REQUEST["filter_zajezd_no_objednavka"]);
        }
        $filterValues->setZajezdOd(isset($_REQUEST["filter_zajezd_od"]) ? CommonUtils::engDate($_REQUEST["filter_zajezd_od"]) : null);
        $filterValues->setZajezdDo(isset($_REQUEST["filter_zajezd_do"]) ? CommonUtils::engDate($_REQUEST["filter_zajezd_do"]) : null);
        $filterValues->setObjednavkaOd(isset($_REQUEST["filter_objednavka_od"]) ? CommonUtils::engDate($_REQUEST["filter_objednavka_od"]) : null);
        $filterValues->setObjednavkaDo(isset($_REQUEST["filter_objednavka_do"]) ? CommonUtils::engDate($_REQUEST["filter_objednavka_do"]) : null);
        $filterValues->setRealizaceOd(isset($_REQUEST["filter_realizace_od"]) ? CommonUtils::engDate($_REQUEST["filter_realizace_od"]) : null);
        $filterValues->setRealizaceDo(isset($_REQUEST["filter_realizace_do"]) ? CommonUtils::engDate($_REQUEST["filter_realizace_do"]) : null);
        
        $filterValues->setPagingPage($_REQUEST["p"]);
        $filterValues->saveFilter(FinancniPohybyModelConfig::FILTER_NAME);
        $this->serialFilter = $filterValues;

        //dao
        $this->zeme = ZemeDAO::readZemeList();
        $this->serialTypList = SerialTypDAO::readSerialTypList();
        $this->loadSerialList();

        //view results
        $this->notifySerialListObservers();
    }

    public function prehled()
    {
        //filter
        $filterValues = $this->loadFilter();
        $filterValues->setZajezdIdsSelected($_REQUEST["cb-zajezdy"]);
        $filterValues->saveFilter(FinancniPohybyModelConfig::FILTER_NAME);
        $this->serialFilter = $filterValues;

        //dao
        $this->loadPrehled();

        $this->notifyPrehledObservers();
    }

    public function prehledPdf()
    {
        //filter
        $this->serialFilter = $this->loadFilter();

        //dao
        $this->loadPrehled();

        //pdf
        $this->initPdf();

        $this->notifyPrehledPdfObservers();
    }

    public function generatePdf($pdfHtml)
    {
        $pdfPrefix = $this->generatePdfPrefix($this->loadFilter());
        $dateTime = date("Y-m-d-H-i-s");
        $pathToPdf = FinancniPohybyModelConfig::PDF_FOLDER . $pdfPrefix . "_" . $dateTime . ".pdf";

        $this->pdfGenerator->WriteHTML($pdfHtml, 2);
        $this->pdfGenerator->Output();
//        $this->pdfGenerator->Output($pathToPdf, 'F'); dont save it so far
    }

    /**
     * @param $filterValues SerialFilter
     * @return string pdf prefix
     */
    private function generatePdfPrefix($filterValues)
    {
        $zajezdIds = $filterValues->getZajezdIdsSelected();

        $pdfPrefix = "";
        foreach ($zajezdIds as $zajezdId) {
            $pdfPrefix .= $zajezdId;
        }

        return md5($pdfPrefix);
    }

    //region PRIVATE METHODS ******************************************************************

    private function notifySerialListObservers()
    {
        if (is_null($this->fpObservers))
            return;

        foreach ($this->fpObservers as $o) {
            $o->serialListChanged();
        }
    }

    private function notifyPrehledObservers()
    {
        if (is_null($this->fpObservers))
            return;

        foreach ($this->fpObservers as $o) {
            $o->prehledChanged();
        }
    }

    private function notifyPrehledPdfObservers()
    {
        if (is_null($this->fpObservers))
            return;

        foreach ($this->fpObservers as $o) {
            $o->prehledPdfChanged();
        }
    }

    private function loadFilter()
    {
        return SerialFilter::loadFilter(FinancniPohybyModelConfig::FILTER_NAME, FinancniPohybyModelConfig::SERIALY_PAGING_MAX_PAGES, FinancniPohybyModelConfig::SERIALY_PAGING_MAX_PER_PAGE);
    }

    private function loadSerialList()
    {
        if (is_null($this->serialHolder)) {
            $this->serialHolder = new SerialHolder($this->serialFilter);
            $dbResult = FinancniPohybyDAO::readSerialListFiltered($this->serialFilter);
            $this->serialHolder->setSerialyList($dbResult->object);
            $this->serialHolder->setFoundRows($dbResult->foundRows);

            //po tom co nactu serialy jste musim spocitat strankovani
            $this->serialFilter->calculatePaging($this->serialHolder->getFoundRows());
        }
    }

    private function loadPrehled()
    {
        if (is_null($this->serialHolder)) {
            $this->serialHolder = new SerialHolder();
            $this->serialHolder->setSerialyList(FinancniPohybyDAO::readSelectedSerialListWithZajezdy($this->serialFilter));
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

    //endregion

}