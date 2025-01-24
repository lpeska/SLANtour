<?php

class AFakturaProvizeModel
{

    static $zamestnanec;
    static $core;

    /** @var  IAFakturaProvizeObserver[] */
    private $observers;

    /** @var  PagingFilter */
    private $pagingFilter;

    /** @var  FakturaHolder */
    private $fakturaHolder;

    //region GETTERS/SETTERS *************************************************************
    /**
     * @return FakturaHolder
     */
    public function getFakturaHolder()
    {
        return $this->fakturaHolder;
    }

    /**
     * @return PagingFilter
     */
    public function getPagingFilter()
    {
        return $this->pagingFilter;
    }

    //endregion

    public function registerFPObserver($observer)
    {
        $this->observers[] = $observer;
    }

    public function faktury()
    {
        //paging filter
        $this->pagingFilter = PagingFilter::loadFilter(null, AFakturaProvizeModelConfig::SERIALY_PAGING_MAX_PAGES, AFakturaProvizeModelConfig::SERIALY_PAGING_MAX_PER_PAGE); //jmeno = null -> neni potreba filter ukladat, obstarava jen bezstavovy paging
        $this->pagingFilter->setPagingPage($_REQUEST["p"]);

        $this->loadFaktury();

        $this->notifyFakturyObservers();
    }

    public function zaplatitFakturu()
    {
        AFakturaProvizeDAO::payFakturaById($_REQUEST["id"]);

        CommonUtils::redirect("faktura_provize.php");
    }

    //region PRIVATE METHODS ******************************************************************
    private function notifyFakturyObservers()
    {
        if (is_null($this->observers))
            return;

        foreach ($this->observers as $o) {
            $o->fakturyChanged();
        }
    }

    private function loadFaktury()
    {
        $dbResult = AFakturaProvizeDAO::readFakturyProvize($this->pagingFilter);
        $this->fakturaHolder = new FakturaHolder($dbResult->object);
        $this->pagingFilter->calculatePaging($dbResult->foundRows);
    }
    //endregion


}