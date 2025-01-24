<?php

class ObjednavkyController {
    /**
     * @var ObjednavkyModel
     */
    private $model;

    private $zamestnanec;

    function __construct($model)
    {
        $this->model = $model;
        $this->init();
    }

    public function dispatchRequests()
    {
        //echo $_REQUEST["page"];
        switch ($_REQUEST["page"]) {
            default:
            case "":
                $this->objednavkaActions();
                break;
            case "platby":
                $this->platbyActions();
                break;
            case "sluzby":
                $this->sluzbyActions();
                break;
            case "slevy":
                $this->slevyActions();
                break;
            case "ucastnici":
                $this->ucastniciActions();
                break;
            case "ajax":
                $this->ajax();
                break;
        }
    }

    private function init()
    {
        ObjednavkyModel::$zamestnanec = User_zamestnanec::get_instance();
        ObjednavkyModel::$core = Core::get_instance();
        $this->dispatchRequests();
    }

    private function ajax()
    {
        switch ($_REQUEST["action"]) {
            //toggle stav sekci
            case "section-toggle-save":
                $this->model->ajaxSectionToggleSave();
                break;

            //obecne info
            case "obecne-info-termin-save":
                $this->model->ajaxObecneInfoTerminSave();
                break;
            case "obecne-info-stav-load":
                $this->model->ajaxObecneInfoStavLoad();
                break;
            case "obecne-info-stav-save":
                $this->model->ajaxObecneInfoStavSave();
                break;
            case "obecne-info-serial-save":
                $this->model->ajaxObecneInfoSerialSave();
                break;

            //osoby / organizace
            case "osoby-provizni-agentura-save":
                $this->model->ajaxProvizniAgenturaSave();
                break;
            case "osoby-objednavajici-osoba-save":
                $this->model->ajaxObjednavajiciOsobaSave();
                break;
            case "osoby-objednavajici-organizace-save":
                $this->model->ajaxObjednavajiciOrganizaceSave();
                break;
            case "osoby-ucastnik-load":
                $this->model->ajaxUcastnikLoad();
                break;
            case "osoby-ucastnik-save":
                $this->model->ajaxUcastnikSave();
                break;
            case "osoby-ucastnik-remove":
                $this->model->ajaxUcastnikRemove();
                break;
            case "osoby-ucastnik-storno-save":
                $this->model->ajaxUcastnikStornoSave();
                break;
            case "osoby-ucastnik-storno-undo":
                $this->model->ajaxUcastnikStornoUndo();
                break;
            case "osoby-ucastnik-add-existing":
                $this->model->ajaxUcastnikAddExisting();
                break;

            //sluzby
            case "sluzby-minus":
                $this->model->ajaxSluzbyMinus();
                break;
            case "sluzby-plus":
                $this->model->ajaxSluzbyPlus();
                break;
            case "sluzby-storno-minus":
                $this->model->ajaxSluzbyStornoMinus();
                break;
            case "sluzby-storno-plus":
                $this->model->ajaxSluzbyStornoPlus();
                break;
            case "sluzby-add":
                $this->model->ajaxSluzbyAdd();
                break;
            case "sluzby-price-refresh":
                $this->model->ajaxSluzbyPriceRefresh();
                break;

            //slevy
            case "slevy-minus":
                $this->model->ajaxSlevyMinus();
                break;
            case "slevy-plus":
                $this->model->ajaxSlevyPlus();
                break;
            case "slevy-remove":
                $this->model->ajaxSlevyRemove();
                break;
            case "slevy-sluzba-add":
                $this->model->ajaxSlevySluzbaAdd();
                break;
            case "slevy-add":
                $this->model->ajaxSlevyAdd();
                break;

            //finance
            case "finance-faktura-provize-pay":
                $this->model->ajaxFakturaProvizePay();
                break;
            case "finance-platba-load":
                $this->model->ajaxPlatbaLoad();
                break;
            case "finance-platba-save":
                $this->model->ajaxPlatbaSave();
                break;
            case "finance-platba-remove":
                $this->model->ajaxPlatbaDelete();
                break;
            case "finance-provize-save":
                $this->model->ajaxProvizeSave();
                break;

            //poznamky / ts
            case "ts-poznamky-load":
                $this->model->ajaxTsPoznamkyLoad();
                break;
            case "ts-poznamky-save":
                $this->model->ajaxTsPoznamkySave();
                break;
            case "ts-tajne-poznamky-load":
                $this->model->ajaxTsTajnePoznamkyLoad();
                break;
            case "ts-tajne-poznamky-save":
                $this->model->ajaxTsTajnePoznamkySave();
                break;
            case "ts-doprava-cs-load":
                $this->model->ajaxTsDopravaCSLoad();
                break;
            case "ts-doprava-cs-save":
                $this->model->ajaxTsDopravaCSSave();
                break;
            case "ts-stravovani-cs-load":
                $this->model->ajaxTsStravovaniCSLoad();
                break;
            case "ts-stravovani-cs-save":
                $this->model->ajaxTsStravovaniCSSave();
                break;
            case "ts-ubytovani-cs-load":
                $this->model->ajaxTsUbytovaniCSLoad();
                break;
            case "ts-ubytovani-cs-save":
                $this->model->ajaxTsUbytovaniCSSave();
                break;
            case "ts-pojisteni-cs-load":
                $this->model->ajaxTsPojisteniCSLoad();
                break;
            case "ts-pojisteni-cs-save":
                $this->model->ajaxTsPojisteniCSSave();
                break;
            case "ts-k-uhrade-zaloha-cs-save":
                $this->model->ajaxTsKUhradeZalohaCSSave();
                break;
            case "ts-k-uhrade-doplatek-cs-save":
                $this->model->ajaxTsKUhradeDoplatekCSSave();
                break;
        }
    }

    private function objednavkaActions()
    {
        switch ($_REQUEST["action"]) {
            default:
            case "":
                $this->model->objednavkaDetail();
                break;
        }
    }

    private function platbyActions()
    {
        switch ($_REQUEST["action"]) {
            case "add":
                $this->model->platbaAdd();
                break;
        }
    }

    private function ucastniciActions()
    {
        switch ($_REQUEST["action"]) {
            case "add":
                $this->model->ucastnikAdd();
                break;
        }
    }

    private function sluzbyActions()
    {
        switch ($_REQUEST["action"]) {
            case "create":
                $this->model->sluzbyCreate();
                break;
        }
    }

    private function slevyActions()
    {
        switch ($_REQUEST["action"]) {
            case "create":
                $this->model->slevyCreate();
                break;
        }
    }
}