<?php

class OrganizaceModel
{

    static $zamestnanec;
    static $core;

    /** @var  IOrganizaceObserver[] */
    private $organizaceProdejceObservers;
    /** @var  ObjednavkaHolder */
    private $objednavkaHolder;

    //region GETTERS/SETTERS *************************************************************
    /**
     * @return ObjednavkaHolder
     */
    public function getObjednavkaHolder()
    {
        return $this->objednavkaHolder;
    }
    //endregion

    public function registerObserver($observer)
    {
        $this->organizaceProdejceObservers[] = $observer;
    }

    public function prodejceObjednavkaList()
    {
        $this->loadProdejceObjednavkaList();

        $this->notifyObjednavkaListObservers();
    }

    //region PRIVATE METHODS ******************************************************************

    private function notifyObjednavkaListObservers()
    {
        if (is_null($this->organizaceProdejceObservers))
            return;

        foreach ($this->organizaceProdejceObservers as $o) {
            $o->objednavkaListChanged();
        }
    }

    private function loadProdejceObjednavkaList()
    {
        $dbResult = OrganizaceDAO::readObjednavkaListByOrganizaceId($_REQUEST["id"]);
        $this->objednavkaHolder = $dbResult->object;
        $dbResult->foundRows;
    }

    //endregion
}