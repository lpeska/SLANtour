<?php

class VyberyModel
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
     * @var IVyberyModelSerialyObserver[]
     */
    private $observersSerialy;
    /**
     * @var tsZeme[]
     */
    private $zeme;
    /**
     * @var tsTypSerial[]
     */
    private $serialTypList;
    /**
     * @var SerialHolder
     */
    private $serialHolder;
    /**
     * @var SerialFilter
     */
    private $serialFilter;

    //endregion

    //region GETTERS & SETTERS ***********************************************************

    /**
     * @return \tsZeme[]
     */
    public function getZeme()
    {
        return $this->zeme;
    }

    /**
     * @return \tsTypSerial[]
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

    // PUBLIC METHODS ********************************************************************

    public function registerSerialyObserver($observer)
    {
        $this->observersSerialy[] = $observer;
    }

    public function serialy()
    {
        //uloz volbu filtru od uzivatele
        $serialyFilter = $this->loadFilter();
        $serialyFilter->setSerialTyp($_REQUEST["f_serial_typ"]);
        $serialyFilter->setSerialZeme($_REQUEST["f_serial_zeme"]);
        //finta fò - u checkboxu neni poznat zda prisel nezaskrnuty pozadavek nebo neprisel zadny, tak si k rozpoznani pomaham hidden inputem
        if (isset($_REQUEST["f-hid-issend"])) {
            $serialyFilter->setSerialNoZajezd($_REQUEST["f_serial_no_zajezd"]);
            $serialyFilter->setSerialNoAktivniZajezd($_REQUEST["f_serial_no_aktivni_zajezd"]);
            $serialyFilter->setSerialAktivniZajezd($_REQUEST["f_serial_aktivni_zajezd"]);
            $serialyFilter->setZajezdObjednavka($_REQUEST["f_zajezd_objednavka"]);
            $serialyFilter->setZajezdNoObjednavka($_REQUEST["f_zajezd_no_objednavka"]);
        }
        $serialyFilter->setPagingPage($_REQUEST["p"]);
        $serialyFilter->saveFilter(VyberyModelConfig::FILTER_NAME);
        $this->serialFilter = $serialyFilter;

        //nacti data pro filtr
        $this->zeme = VyberyDAO::dataZeme();
        $this->serialTypList = VyberyDAO::dataTypySerialu();

        //nacti serialy
        $this->loadSerialy();

        $this->notifySerialyObservers();
    }

    public function deleteSerial()
    {
        VyberyDAO::deleteSerialById($_REQUEST["id"]);

        $this->serialy();
    }

    public function deleteZajezd()
    {
        VyberyDAO::deleteZajezdById($_REQUEST["id"]);

        $this->serialy();
    }

    public function deleteZajezdSelected()
    {
        foreach((array)$_REQUEST["cb-zajezdy"] as $zajezdId) {
            VyberyDAO::deleteZajezdById($zajezdId);
        }

        $this->serialy();
    }

    // PRIVATE METHODS *******************************************************************

    private function notifySerialyObservers()
    {
        foreach ($this->observersSerialy as $o) {
            $o->modelSerialyChanged();
        }
    }

    private function loadSerialy()
    {
        if (is_null($this->serialHolder)) {
            $this->serialHolder = new SerialHolder($this->serialFilter);
            $dbResult = VyberyDAO::readSerialListFiltered($this->serialFilter);
            $this->serialHolder->setSerialyList($dbResult->object);
            $this->serialHolder->setFoundRows($dbResult->foundRows);

            //po tom co nactu serialy jste musim spocitat strankovani
            $this->serialFilter->calculatePaging($this->serialHolder->getFoundRows());
        }
    }

    private function loadFilter()
    {
        return SerialFilter::loadFilter(VyberyModelConfig::FILTER_NAME, VyberyModelConfig::SERIALY_PAGING_MAX_PAGES, VyberyModelConfig::SERIALY_PAGING_MAX_PER_PAGE);
    }

}

interface IVyberyModelSerialyObserver
{
    public function modelSerialyChanged();
}