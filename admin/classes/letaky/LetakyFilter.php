<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of LetakyFilter
 *
 * @author lpeska
 */
class LetakyFilter {
    //put your code here
    private $empty;
    private $usePopisek;
    private $useProgram;
    private $useCenaZahrnuje;
    private $useCenanezahrnuje;
    private $useDalsiSluzby;
    private $useOdjezdovaMista;
    private $useMezeraHeadery;
    private $vynechatZakladniCenu;
    
    private $fotoIDs;
    private $zajezdyIDs;
    private $sluzbyIDs;
    private $slevyIDs;
    
    private $typSablony;
    
    private $preheaderText;
    private $preheaderStyle;
    private $headerText;
    private $headerStyle;
    private $nazevSerialuText;
    private $nazevSerialuStyle;
    private $highlightsText;
    private $highlightsStyle;   
    private $datumText;
    private $datumStyle;    
    private $cenaText;
    private $cenaStyle;
    private $footerText;
    
    private function __construct()
    {
        $this->empty = true;
    }
    //region Getter / Setter METHODS **************************************************************
    
    function getEmpty() {
        return $this->empty;
    }
    
    function getFooterText() {
        return $this->footerText;
    }

    function setFooterText($footerText) {
        $this->footerText = $footerText;
    }

        function getPreheaderText() {
        return $this->preheaderText;
    }

    function getPreheaderStyle() {
        return $this->preheaderStyle;
    }

    function setPreheaderText($preheaderText) {
        $this->preheaderText = $preheaderText;
    }

    function setPreheaderStyle($preheaderStyle) {
        $this->preheaderStyle = $preheaderStyle;
    }

        function getSlevyIDs() {
        return $this->slevyIDs;
    }

    function setSlevyIDs($slevyIDs) {
        $this->slevyIDs = $slevyIDs;
    }

        function getVynechatZakladniCenu() {
        return $this->vynechatZakladniCenu;
    }

    function setVynechatZakladniCenu($vynechatZakladniCenu) {
        $this->vynechatZakladniCenu = $vynechatZakladniCenu;
    }

        function getHighlightsText() {
        return $this->highlightsText;
    }

    function getHighlightsStyle() {
        return $this->highlightsStyle;
    }

    function setHighlightsText($highlightsText) {
        $this->highlightsText = $highlightsText;
    }

    function setHighlightsStyle($highlightsStyle) {
        $this->highlightsStyle = $highlightsStyle;
    }

        function setEmpty($empty) {
        $this->empty = $empty;
    }
    function getHeaderStyle() {
        return $this->headerStyle;
    }

    function getNazevSerialuText() {
        return $this->nazevSerialuText;
    }

    function getNazevSerialuStyle() {
        return $this->nazevSerialuStyle;
    }

    function getDatumText() {
        return $this->datumText;
    }

    function getDatumStyle() {
        return $this->datumStyle;
    }

    function getCenaText() {
        return $this->cenaText;
    }

    function getCenaStyle() {
        return $this->cenaStyle;
    }

    function setHeaderStyle($headerStyle) {
        $this->headerStyle = $headerStyle;
    }

    function setNazevSerialuText($nazevSerialuText) {
        $this->nazevSerialuText = $nazevSerialuText;
    }

    function setNazevSerialuStyle($nazevSerialuStyle) {
        $this->nazevSerialuStyle = $nazevSerialuStyle;
    }

    function setDatumText($datumText) {
        $this->datumText = $datumText;
    }

    function setDatumStyle($datumStyle) {
        $this->datumStyle = $datumStyle;
    }

    function setCenaText($cenaText) {
        $this->cenaText = $cenaText;
    }

    function setCenaStyle($cenaStyle) {
        $this->cenaStyle = $cenaStyle;
    }

        
    function getUseOdjezdovaMista() {
        return $this->useOdjezdovaMista;
    }

    function setUseOdjezdovaMista($useOdjezdovaMista) {
        $this->useOdjezdovaMista = $useOdjezdovaMista;
    }

        function getSluzbyIDs() {
        return $this->sluzbyIDs;
    }

    function setSluzbyIDs($sluzbyIDs) {
        $this->sluzbyIDs = $sluzbyIDs;
    }

    function getUsePopisek() {
        return $this->usePopisek;
    }

    function getUseProgram() {
        return $this->useProgram;
    }

    function getUseCenaZahrnuje() {
        return $this->useCenaZahrnuje;
    }

    function getUseCenaNezahrnuje() {
        return $this->useCenanezahrnuje;
    }

    function getUseDalsiSluzby() {
        return $this->useDalsiSluzby;
    }

    function getUseMezeraHeadery() {
        return $this->useMezeraHeadery;
    }

    function getHeaderText() {
        return $this->headerText;
    }

    function getFotoIDs() {
        return $this->fotoIDs;
    }

    function getZajezdyIDs() {
        return $this->zajezdyIDs;
    }

    function getTypSablony() {
        return $this->typSablony;
    }

    function setUsePopisek($usePopisek) {
        $this->usePopisek = $usePopisek;
    }

    function setUseProgram($useProgram) {
        $this->useProgram = $useProgram;
    }

    function setUseCenaZahrnuje($useCenaZahrnuje) {
        $this->useCenaZahrnuje = $useCenaZahrnuje;
    }

    function setUseCenanezahrnuje($useCenanezahrnuje) {
        $this->useCenanezahrnuje = $useCenanezahrnuje;
    }

    function setUseDalsiSluzby($useDalsiSluzby) {
        $this->useDalsiSluzby = $useDalsiSluzby;
    }

    function setUseMezeraHeadery($useMezeraHeadery) {
        $this->useMezeraHeadery = $useMezeraHeadery;
    }

    function setHeaderText($headerText) {
        $this->headerText = $headerText;
    }

    function setFotoIDs($fotoIDs) {
        $this->fotoIDs = $fotoIDs;
    }

    function setZajezdyIDs($zajezdyIDs) {
        $this->zajezdyIDs = $zajezdyIDs;
    }

    function setTypSablony($typSablony) {
        $this->typSablony = $typSablony;
    }
    
        //region STATIC METHODS **************************************************************

    /**
     * @param $name jmeno dane _instance filtru
     * @param $pagingMaxPages
     * @param $pagingMaxPerPage
     * @return SerialFilter|boolean _instance ulozene tridy FilterValue nebo false pokud v session neni ulozena
     */
    public static function loadFilter($name, $pagingMaxPages = null, $pagingMaxPerPage = null, $serial = null)
    {
        $filter = unserialize($_SESSION[$name]);
        if (!$filter){
            $filter = new LetakyFilter();
        }
        return $filter;
    }

    //endregion

    //region PUBLIC METHODS **************************************************************

    public function calculatePaging($foundRows)
    {
        parent::calcPaging($foundRows, $this->pagingMaxPerPage, $this->pagingMaxPages);
    }

    public function saveFilter($name)
    {
        $_SESSION[$name] = serialize($this);
    }

    //endregion


}
