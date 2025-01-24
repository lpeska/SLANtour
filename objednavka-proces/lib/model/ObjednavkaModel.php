<?php

class ObjednavkaModel implements IObjednavkaModelForController, IObjednavkaModelForView
{
    /**
     * @var ObjednavkaSessionEnt
     */
    private $objednavkaSession;
    /**
     * @var ZajezdEnt
     */
    private $zajezd;
    /**
     * @var CentralniDataEnt[]
     */
    private $zpusobyPlateb;
    /**
     * @var SlevaEnt[]
     */
    private $slevyKlient;
    /**
     * @var Serial
     */
    private $serial;
    /**
     * @var string
     */
    private $nazevCK;
    /**
     * @var IObjednavkaModelZajezdObserver[]
     */
    private $observersZajezdy;
    /**
     * @var IObjednavkaModelOsobniUdajeObserver[]
     */
    private $observersOsobniUdaje;
    /**
     * @var IObjednavkaModelPlatbaObserver[]
     */
    private $observersPlatba;
    /**
     * @var IObjednavkaModelSouhrnObserver[]
     */
    private $observersSouhrn;
    /**
     * @var IObjednavkaModelAJAXObserver[]
     */
    private $observersAjax;

    // PUBLIC METHODS ********************************************************************

    public function zajezd()
    {
        //ziskej id zajezdu z ruznych requestu "objednat"
        $this->fetchZajezdIdFromRequest();

        //zajezd
        $this->loadZajezdAll($_SESSION["id_zajezd"]);

        //session
        $this->objednavkaSession = ObjednavkaSessionEnt::pullFromSession();
        $this->objednavkaSession->setPocetOsob($_GET["pocet-osob"]);
        $this->saveOsobniUdajeToSession();

        $this->notifyZajezdObservers();
    }

    public function terminChanged()
    {
        //zajezd
        $this->loadZajezdAll($_SESSION["id_zajezd"]);

        //session
        $this->objednavkaSession = ObjednavkaSessionEnt::pullFromSession();
        //todo save sluzby jako SLuzbaEnt ne jako SluzbaSessionEnt
        $this->saveZajezdToSession();

        CommonUtils::redirect("index.php?page=zajezd");
    }

    public function osobniUdaje()
    {
        //zajezd
        $this->loadZajezdAll($_SESSION["id_zajezd"]);
        //centralni data nazev CK
        $this->loadNazevCK();

        //session
        $this->objednavkaSession = ObjednavkaSessionEnt::pullFromSession();
        $this->saveZajezdToSession();

        //validace - jen pokud postupuji v obj smerem vpred
        $isValid = isset($_POST["pocet-osob"]) ? $this->validateZajezd() : true;

        //validace statusu sluzeb - jen pokud postupuji v obj smerem vpred a pokud validace jeste neprobehla
        $isSluzbyStatusValid = true;
        if (!isset($_POST["sl-vyprodano-ok"]))
            $isSluzbyStatusValid = isset($_POST["pocet-osob"]) ? $this->validateSluzbyStatus() : true;

        //validace upresneni terminu zajezdu - jen pokud postupuji v obj smerem vpred  a pokud validace jeste neprobehla
        $isTerminValid = true;
        if (!isset($_POST["sl-termin-ok"]))
            $isTerminValid = isset($_POST["pocet-osob"]) ? $this->validateTermin() : true;

        //view
        if ($isValid)
            if ($isSluzbyStatusValid)
                if ($isTerminValid)
                    $this->notifyOsobniUdajeObservers();
                else
                    $this->zajezd();
            else
                $this->zajezd();
        else
            $this->zajezd();
    }

    public function platba()
    {
        //zajezd
        $this->loadZajezdAll($_SESSION["id_zajezd"]);
        //platebni metody
        $this->loadZpusobyPlateb();

        //session
        $this->objednavkaSession = ObjednavkaSessionEnt::pullFromSession();
        $this->saveOsobniUdajeToSession();

        //validace - jen pokud postupuji v obj smerem vpred
        $isValid = isset($_POST["jmeno"]) ? $this->validateOsobniUdaje() : true;

        if ($isValid)
            $this->notifyPlatbaObservers();
        else
            $this->osobniUdaje();
    }

    public function souhrn()
    {
        //zajezd
        $this->loadZajezdAll($_SESSION["id_zajezd"]);
        //platebni metody
        $this->loadZpusobyPlateb();

        //session
        $this->objednavkaSession = ObjednavkaSessionEnt::pullFromSession();
        $this->savePlatbaToSession();

        $this->notifySouhrnObservers();
    }

    public function specialRangesAjax()
    {
        //zajezd
        $this->loadZajezdAll($_GET["zajezd_id"]);

        $this->notifyAjaxSSObservers();
    }

    public function sluzbyAjax()
    {
        //zajezdv
        $this->loadZajezdAll($_GET["zajezd_id"]);

        $this->notifyAjaxSluzbyObservers();
    }

    public function slevyAjax()
    {
        //zajezdv
        $this->loadZajezdAll($_GET["zajezd_id"]);

        $this->notifyAjaxSlevyObservers();
    }

    public function slevyKlientSerialAjax()
    {
        //slevy
        $this->loadSlevyKlientSerial($_GET["serial_id"]);
        //print_r($_GET);

        $this->notifyAjaxSlevyKlientObservers();
    }

    public function slevyKlientZajezdAjax()
    {
        //slevy
        $this->loadSlevyKlientZajezd($_GET["zajezd_id"]);

        $this->notifyAjaxSlevyKlientObservers();
    }

    public function terminZajezdAjax()
    {
        //slevy
        $this->loadZajez($_GET["zajezd_id"]);

        $this->notifyAjaxTerminObservers();
    }

    public function serialAjax()
    {
        //slevy
        $this->loadSerial($_GET["serial_id"]);

        $this->notifyAjaxSerialObservers();
    }

    //region GETTERS & SETTERS ***********************************************************
    public function getNazevCK()
    {
        return $this->nazevCK;
    }

    public function getZajezd()
    {
        return $this->zajezd;
    }

    public function getZeme()
    {
        return EnumZemeImage::$ZEME;
    }

    public function getMesice()
    {
        return EnumMesice::$MESICE_CZ;
    }

    public function getTopZeme()
    {
        return EnumZemeImage::$TOP_ZEME;
    }

    public function getZpusobyPlateb()
    {
        return $this->zpusobyPlateb;
    }

    public function getObjednavkaSession()
    {
        return $this->objednavkaSession;
    }

    public function getKurzEur()
    {
        return ObjednavkaProcesDAO::kurzEur();
    }

    /**
     * @return \SlevaEnt[]
     */
    public function getSlevyKlient()
    {
        return $this->slevyKlient;
    }

    /**
     * @return \Serial
     */
    public function getSerial()
    {
        return $this->serial;
    }

    //endregion

    //region REGISTER OBSERVERS *******************************************************************
    public function registerZajezdObserver($observer)
    {
        $this->observersZajezdy[] = $observer;
    }

    public function registerOsobniUdajeObserver($observer)
    {
        $this->observersOsobniUdaje[] = $observer;
    }

    public function registerPlatbaObserver($observer)
    {
        $this->observersPlatba[] = $observer;
    }

    public function registerSouhrnObserver($observer)
    {
        $this->observersSouhrn[] = $observer;
    }

    public function registerAJAXObserver($observer)
    {
        $this->observersAjax[] = $observer;
    }

    //endregion

    // PRIVATE METHODS *******************************************************************

    private function saveZajezdToSession()
    {
        //ukladam jen kdyz jsou hodnoty nastaveny
        if (isset($_POST["pocet-osob"])) {
            $this->objednavkaSession->setTermin($_POST["termin"]);
            $this->objednavkaSession->setPocetOsob($_POST["pocet-osob"]);
            $this->objednavkaSession->saveLastMinute();
            $this->objednavkaSession->saveSluzby();
            $this->objednavkaSession->savePriplatky();
            $this->objednavkaSession->saveSlevy();
            $this->objednavkaSession->saveOdjezdovaMista();
            $this->objednavkaSession->pushToSession();
        }
    }

    private function saveOsobniUdajeToSession()
    {
        //ukladam jen kdyz jsou hodnoty nastaveny
        if (isset($_POST["jmeno"])) {
            $this->objednavkaSession->saveObjednatel();
            $this->objednavkaSession->saveUcastnici();
            $this->objednavkaSession->setPoznamka($_POST["poznamka"]);
            $this->objednavkaSession->pushToSession();
        }
    }

    private function savePlatbaToSession()
    {
        //ukladam jen kdyz jsou hodnoty nastaveny
        if (isset($_POST["method"])) {
            $this->objednavkaSession->setZpusobPlatby($_POST["method"]);
            $this->objednavkaSession->pushToSession();
        }
    }

    private function loadNazevCK()
    {
        $this->nazevCK = ObjednavkaProcesDAO::nazevCK();
    }
    
    private function loadZajez($idZajezd)
    {
        $this->zajezd = ObjednavkaProcesDAO::readZajezdById($idZajezd);
    }

    private function loadZajezdAll($idZajezd)
    {
        $this->zajezd = ObjednavkaProcesDAO::readZajezdAllById($idZajezd);
    }

    private function loadSlevyKlientSerial($idSerial)
    {
        $this->slevyKlient = ObjednavkaProcesDAO::readSlevyKlientBySerialId($idSerial);
    }

    private function loadSlevyKlientZajezd($idZajezd)
    {
        $this->slevyKlient = ObjednavkaProcesDAO::readSlevyKlientByZajezdId($idZajezd);
    }

    private function loadZpusobyPlateb()
    {
        $this->zpusobyPlateb = ObjednavkaProcesDAO::readZpusobyPlateb();
    }

    private function loadSerial($idSerial)
    {
        $this->serial = ObjednavkaProcesDAO::readSerialById($idSerial);
    }

    //region NOTIFY OBSERVERS *******************************************************************

    private function notifyZajezdObservers()
    {
        foreach ($this->observersZajezdy as $o) {
            $o->modelZajezdChanged();
        }
    }

    private function notifyOsobniUdajeObservers()
    {
        foreach ($this->observersOsobniUdaje as $o) {
            $o->modelOsobniUdajeChanged();
        }
    }

    private function notifyPlatbaObservers()
    {
        foreach ($this->observersPlatba as $o) {
            $o->modelPlatbaChanged();
        }
    }

    private function notifySouhrnObservers()
    {
        foreach ($this->observersSouhrn as $o) {
            $o->modelSouhrnChanged();
        }
    }

    private function notifyAjaxSSObservers()
    {
        foreach ($this->observersAjax as $o) {
            $o->modelAJAXSSChanged();
        }
    }

    private function notifyAjaxSluzbyObservers()
    {
        foreach ($this->observersAjax as $o) {
            $o->modelAJAXSluzbyChanged();
        }
    }

    private function notifyAjaxSlevyObservers()
    {
        foreach ($this->observersAjax as $o) {
            $o->modelAJAXSlevyChanged();
        }
    }

    private function notifyAjaxSlevyKlientObservers()
    {
        foreach ($this->observersAjax as $o) {
            $o->modelAJAXSlevyKlientChanged();
        }
    }

    private function notifyAjaxTerminObservers()
    {
        foreach ($this->observersAjax as $o) {
            $o->modelAJAXTerminChanged();
        }
    }

    private function notifyAjaxSerialObservers()
    {
        foreach ($this->observersAjax as $o) {
            $o->modelAJAXSerialChanged();
        }
    }

    private function fetchZajezdIdFromRequest()
    {
        if (isset($_REQUEST["id_zajezd"]) || isset($_REQUEST["nazev_web"])) {
            if (isset($_REQUEST["id_zajezd"]))
                $_SESSION["id_zajezd"] = $_REQUEST["id_zajezd"];
            else if (isset($_REQUEST["nazev_web"])) {
                $zajezd = ObjednavkaProcesDAO::readZajezdBySerialNazevWeb($_REQUEST["nazev_web"]);
                $_SESSION["id_zajezd"] = $zajezd->id;
            }
            $_SESSION["src_web"] = $_REQUEST["src_web"];
            ObjednavkaSessionEnt::clearFromSession();
        }
    }

    private function validateZajezd()
    {
        //termin
        if ($this->zajezd->isDlouhodoby()) {
            $zajezdTerminOd = CommonUtils::czechDate($this->zajezd->terminOd);
            $zajezdTerminDo = CommonUtils::czechDate($this->zajezd->terminDo);
            if ($_POST["termin"] == "" || $_POST["termin"] == "$zajezdTerminOd - $zajezdTerminDo") {
                ;
                MessagesEnt::addErrMsg(MessageEnt::ID_ZAJEZD_TERMIN);
            }
        }
        //pocet osob
        if ($_POST["pocet-osob"] <= 0) {
            MessagesEnt::addErrMsg(MessageEnt::ID_ZAJEZD_POCET_OSOB);
        }
        //sluzby / last minute
        $sluzbaFilledCnt = CommonUtils::cntMultipleFilledPostInputs("sluzba-");
        $lastMinuteFilledCnt = CommonUtils::cntMultipleFilledPostInputs("last-minute-");
        if ($this->zajezd->getSluzbaHolder()->hasSluzba() && $this->zajezd->getSluzbaHolder()->hasLastMinute()) {
            if (!($sluzbaFilledCnt > 0 || $lastMinuteFilledCnt > 0)) {
                MessagesEnt::addErrMsg(MessageEnt::ID_ZAJEZD_SLUZBY_LASTMINUTE);
            }
        } else if ($this->zajezd->getSluzbaHolder()->hasSluzba()) {
            if ($sluzbaFilledCnt == 0) {
                MessagesEnt::addErrMsg(MessageEnt::ID_ZAJEZD_SLUZBY);
            }
        } else if ($this->zajezd->getSluzbaHolder()->hasLastMinute()) {
            if ($lastMinuteFilledCnt == 0) {
                MessagesEnt::addErrMsg(MessageEnt::ID_ZAJEZD_LASTMINUTE);
            }
        }
        //odjezdova mista
        $odjezdoveMistoFilledCnt = CommonUtils::cntMultipleFilledPostInputs("odjezdove-misto-");
        if ($this->zajezd->getSluzbaHolder()->hasOdjezdoveMisto()) {
            if ($odjezdoveMistoFilledCnt == 0 && $this->zajezd->serial->typ_dopravy == 2) {//pouze pro autokarovou dopravu
                MessagesEnt::addErrMsg(MessageEnt::ID_ZAJEZD_ODJEZDOVE_MISTO);
            }
        }

        return !MessagesEnt::hasErrMsgs();
    }

    private function validateSluzbyStatus()
    {
        $sluzbyAll = $this->getZajezd()->getSluzbaHolder()->getSluzbyAll();
        $sluzbyAllSess = $this->getObjednavkaSession()->getSluzbyAll();
        foreach ($sluzbyAll as $s) {
            $sSess = CommonUtils::searchArrayOfObjects($sluzbyAllSess, $s->id_cena, "getId", null);
            $pocet = is_null($sSess) ? 0 : $sSess->getPocet();
            if ($pocet > 0) {
                if ($s->isNaDotaz() || $s->isVyprodana() || $s->isPlnaKapacita($pocet)) {
                    MessagesEnt::addWarnMsg(MessageEnt::ID_SLUZBA_VYPRODANO);
                    return false;
                }
            }
        }

        return true;
    }

    private function validateTermin()
    {
        $zajezdTerminOdTstmp = strtotime($this->zajezd->terminOd);
        $zajezdTerminDoTstmp = strtotime($this->zajezd->terminDo);
        $postTerminOdTstmp = strtotime(CommonUtils::engDate(trim(explode("-", $_POST["termin"])[0])));
        $postTerminDoTstmp = strtotime(CommonUtils::engDate(trim(explode("-", $_POST["termin"])[1])));

        if($this->zajezd->isDlouhodoby()) {
            if ($postTerminOdTstmp < $zajezdTerminOdTstmp || $postTerminOdTstmp > $zajezdTerminDoTstmp || $postTerminDoTstmp < $zajezdTerminOdTstmp || $postTerminDoTstmp > $zajezdTerminDoTstmp) {
                MessagesEnt::addWarnMsg(MessageEnt::ID_ZAJEZD_TERMIN_OUT_OF_SEASON);
                return false;
            }
        }

        return true;
    }

    private function validateOsobniUdaje()
    {
        //jmeno, prijmeni, mesto = not empty
        if ($_POST["jmeno"] == "") {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_JMENO);
        }
        if ($_POST["prijmeni"] == "") {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_PRIJMENI);
        }
        if ($_POST["mesto"] == "") {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_MESTO);
        }
        //den narozeni cislo 1-31
        if (!($_POST["datum-narozeni-day"] >= 1 && $_POST["datum-narozeni-day"] <= 31)) {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_DAY);
        }
        //mesic narozeni cislo 1-12
        if (!($_POST["datum-narozeni-month"] >= 1 && $_POST["datum-narozeni-month"] <= 12)) {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_MONTH);
        }
        //rok narozeni cislo 1925-2029
        if (!($_POST["datum-narozeni-year"] >= 1925 && $_POST["datum-narozeni-year"] <= 2029)) {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_YEAR);
        }
        //email format
        if (!(filter_var($_POST["email"], FILTER_VALIDATE_EMAIL))) {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_EMAIL);
        }
        //telefon cislo length 5-14
        if (!(strlen($_POST["telefon"]) >= 5 && strlen($_POST["telefon"]) <= 14)) {
            MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_TELEFON);
        }
        //psc cislo 5ti mistne jen pokud neni == "" - jinak nevalidovat
        if ($_POST["psc"] != "") {
            if (strlen($_POST["psc"]) != 5) {
                MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_PSC);
            }
        }
        //ucastnici jmena / prijmeni (pozor na objednatel je/neni ucastnikem)
        $pocetUcastniku = $this->getObjednavkaSession()->calcPocetUcastniku();
        $jmena = CommonUtils::filterArrayUseNumSuffix($_POST, "jmeno-");
        $prijmeni = CommonUtils::filterArrayUseNumSuffix($_POST, "prijmeni-");
        for ($i = 1; $i <= $pocetUcastniku; $i++) {
            if ($jmena[$i] == "") {
                MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_UC_JMENO . "-$i");
            }
            if ($prijmeni[$i] == "") {
                MessagesEnt::addErrMsg(MessageEnt::ID_OS_UDAJE_UC_PRIJMENI . "-$i");
            }
        }

        return !MessagesEnt::hasErrMsgs();
    }

    //endregion
}