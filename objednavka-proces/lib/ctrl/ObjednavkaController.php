<?php

class ObjednavkaController
{

    const PAGE_ZAJEZD = 'zajezd';
    const PAGE_OSOBNI_UDAJE = 'os-udaje';
    const PAGE_PLATBA = 'platba';
    const PAGE_SOUHRN = 'souhrn';
    const PAGE_AJAX_SPECIAL_RANGES = 'ajax-sr';
    const PAGE_AJAX_SLUZBY = 'ajax-sluzby';
    const PAGE_AJAX_SLEVY = 'ajax-slevy';
    const PAGE_AJAX_SLEVY_KLIENT_ZAJEZD = 'ajax-slevy-klient-zajezd';
    const PAGE_AJAX_SLEVY_KLIENT_SERIAL = 'ajax-slevy-klient-serial';
    const PAGE_ZAJEZD_ACTION_NONE = 'zajezd-none';
    const PAGE_ZAJEZD_ACTION_TERMIN_CHANGED = 'zajezd-termin-changed';
    const PAGE_AJAX_TERMIN_ZAJEZD = 'ajax-termin-zajezd';
    const PAGE_AJAX_SERIAL = 'ajax-serial';

    /**
     * @var IObjednavkaModelForController
     */
    private $model;

    function __construct($model)
    {
        $this->model = $model;
    }

    // PUBLIC METHODS ********************************************************************

    public function dispatchRequests()
    {
        switch ($_REQUEST["page"]) {
            default:
            case self::PAGE_ZAJEZD:
                $this->dispatchZajezd();
                break;
            case self::PAGE_OSOBNI_UDAJE:
                $this->model->osobniUdaje();
                break;
            case self::PAGE_PLATBA:
                $this->model->platba();
                break;
            case self::PAGE_SOUHRN:
                $this->model->souhrn();
                break;
            case self::PAGE_AJAX_SPECIAL_RANGES:
                $this->model->specialRangesAjax();
                break;
            case self::PAGE_AJAX_SLUZBY:
                $this->model->sluzbyAjax();
                break;
            case self::PAGE_AJAX_SLEVY:
                $this->model->slevyAjax();
                break;
            case self::PAGE_AJAX_SLEVY_KLIENT_SERIAL:
                $this->model->slevyKlientSerialAjax();
                break;
            case self::PAGE_AJAX_SLEVY_KLIENT_ZAJEZD:
                $this->model->slevyKlientZajezdAjax();
                break;
            case self::PAGE_AJAX_TERMIN_ZAJEZD:
                $this->model->terminZajezdAjax();
                break;
            case self::PAGE_AJAX_SERIAL:
                $this->model->serialAjax();
                break;
        }
    }//{"status": "", "termin": "21.1.2001 - 22.2.2005"}

    private function dispatchZajezd()
    {
        switch ($_REQUEST["action"]) {
            default:
            case self::PAGE_ZAJEZD_ACTION_NONE:
                $this->model->zajezd();
                break;
            case self::PAGE_ZAJEZD_ACTION_TERMIN_CHANGED:
                $this->model->terminChanged();
                break;
        }
    }

}