<?php

class VoucheryObjednavkaHolder extends Generic_data_class
{

    //region PRIVATE MEMBERS *******************************************************************
    /**
     * @var int
     */
    private $id_objednavka;
    /**
     * @var string
     */
    private $security_code;
    /**
     * @var tsObjednavka
     */
    private $objednavka;
    /**
     * @var tsObjednavajici
     */
    private $objednavajici;
    /**
     * @var tsOrganizace
     */
    private $objednavajiciOrg;
    /**
     * @var tsPlatba
     */
    private $platba;
    /**
     * @var tsProdejce
     */
    private $prodejce;
    /**
     * @var tsProvize
     */
    private $provize;
    /**
     * @var tsSleva
     */
    private $sleva;
    /**
     * @var tsSluzba[]
     */
    private $sluzby;
    /**
     * @var tsOsoba
     */
    private $vystavil;
    /**
     * @var tsZajezd
     */
    private $zajezd;
    /**
     * todo: udelat tridu pro centralni data
     * @var arr[]
     */
    private $centralniData;
    /**
     * @var tsSmluvniPodminky
     */
    private $zaloha;
    /**
     * @var tsSmluvniPodminky
     */
    private $doplatek;
    /**
     * @var tsOsoba[]
     */
    private $ucastnici;
    /**
     * @var tsObjekt[]
     */
    private $objekty;
    /**
     * @var string[]
     */
    private $pdfVouchery;

    //endregion

    function __construct($id_objednavka, $security_code, $language)
    {
        $this->id_objednavka = $this->check_int($id_objednavka);
        $this->security_code = $this->check($security_code);
        $this->loadObjednavka($language);
    }

    //region GETTERS ***************************************************************************
    /**
     * @return \arr[]
     */
    public function getCentralniData()
    {
        return $this->centralniData;
    }

    /**
     * @return \tsSmluvniPodminky
     */
    public function getDoplatek()
    {
        return $this->doplatek;
    }

    /**
     * @return int
     */
    public function getIdObjednavka()
    {
        return $this->id_objednavka;
    }

    /**
     * @return \tsObjednavajici
     */
    public function getObjednavajici()
    {
        return $this->objednavajici;
    }

    /**
     * @return tsOrganizace
     */
    public function getObjednavajiciOrg()
    {
        return $this->objednavajiciOrg;
    }

    /**
     * @return \tsObjednavka
     */
    public function getObjednavka()
    {
        return $this->objednavka;
    }

    /**
     * @return \tsPlatba
     */
    public function getPlatba()
    {
        return $this->platba;
    }

    /**
     * @return \tsProdejce
     */
    public function getProdejce()
    {
        return $this->prodejce;
    }

    /**
     * @return \tsProvize
     */
    public function getProvize()
    {
        return $this->provize;
    }

    /**
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->security_code;
    }

    /**
     * @return \tsSluzba[]
     */
    public function getSluzby()
    {
        return $this->sluzby;
    }

    /**
     * @return \tsSleva
     */
    public function getSleva()
    {
        return $this->sleva;
    }

    /**
     * @return \tsOsoba
     */
    public function getVystavil()
    {
        return $this->vystavil;
    }

    /**
     * @return \tsZajezd
     */
    public function getZajezd()
    {
        return $this->zajezd;
    }

    /**
     * @return \tsSmluvniPodminky
     */
    public function getZaloha()
    {
        return $this->zaloha;
    }

    /**
     * @return \tsOsoba[]
     */
    public function getUcastnici()
    {
        return $this->ucastnici;
    }

    /**
     * @return \tsObjekt[]
     */
    public function getObjekty()
    {
        return $this->objekty;
    }

    /**
     * @return \string[]
     */
    public function getPdfVouchery()
    {
        return $this->pdfVouchery;
    }

    //endregion

    //region PRIVATE METHODS *******************************************************************
    private function loadObjednavka($language)
    {
        $this->objednavajici = ObjednavkaDAO::dataObjednavajici($this->id_objednavka);
        $this->objednavajiciOrg = ObjednavkaDAO::dataObjednavajiciOrg($this->id_objednavka);
        $this->objednavka = ObjednavkaDAO::dataObjednavka($this->id_objednavka);
        $this->platba = ObjednavkaDAO::dataPlatby($this->id_objednavka);
        $this->prodejce = ObjednavkaDAO::dataProdejce($this->id_objednavka);
        $this->provize = ObjednavkaDAO::dataProvize($this->id_objednavka);
        $this->sleva = ObjednavkaDAO::dataSleva($this->id_objednavka);

        //sluzby s jazykovou verzi
        $this->sluzby = ObjednavkaDAO::dataSluzby($this->id_objednavka, $language);
        $this->vystavil = ObjednavkaDAO::dataVystavil($this->id_objednavka);
        $this->zajezd = ObjednavkaDAO::dataZajezd($this->id_objednavka);
        $this->centralniData = ObjednavkaDAO::dataCentralniData();
        $this->zaloha = ObjednavkaDAO::dataZaloha($this->id_objednavka);
        $this->doplatek = ObjednavkaDAO::dataDoplatek($this->id_objednavka);

        $this->ucastnici = ObjednavkaDAO::dataOsoby($this->id_objednavka);
        $this->objekty = ObjednavkaDAO::dataObjekty($this->id_objednavka);
        $this->pdfVouchery = ObjednavkaDAO::dataPdfVouchery($this->id_objednavka);
    }
    //endregion

}