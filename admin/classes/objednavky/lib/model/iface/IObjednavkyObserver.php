<?php

interface IObjednavkyObserver {

    //note z tohodle nemam uplne radost - zmenit?
    const METHOD_OBJEDNAVKA_DETAIL = 'objednavkaDetailChanged';
    const METHOD_OBJEDNAVKA_AJAX_OBECNE_INFO_STAV = 'objednavkaAjaxObecneInfoStavChanged';
    const METHOD_OBJEDNAVKA_AJAX_OBECNE_INFO_STAV_ODBAVENO_NEZAPLACENO = 'objednavkaAjaxObecneInfoStavOdbavenoNezaplacenoChanged';
    const METHOD_OBJEDNAVKA_AJAX_TS_POZNAMKY = 'objednavkaAjaxTsPoznamkyChanged';
    const METHOD_OBJEDNAVKA_AJAX_TS_TAJNE_POZNAMKY = 'objednavkaAjaxTsTajnePoznamkyChanged';
    const METHOD_OBJEDNAVKA_AJAX_TS_DOPRAVA_CS = 'objednavkaAjaxTsDopravaCSChanged';
    const METHOD_OBJEDNAVKA_AJAX_TS_STRAVOVANI_CS = 'objednavkaAjaxTsStravovaniCSChanged';
    const METHOD_OBJEDNAVKA_AJAX_TS_UBYTOVANI_CS = 'objednavkaAjaxTsUbytovaniCSChanged';
    const METHOD_OBJEDNAVKA_AJAX_TS_POJISTENI_CS = 'objednavkaAjaxTsPojisteniCSChanged';
    const METHOD_OBJEDNAVKA_AJAX_FINANCE_PLATBA = 'objednavkaAjaxFinancePlatbaChanged';
    const METHOD_OBJEDNAVKA_AJAX_OSOBY_UCASTNIK = 'objednavkaAjaxOsobyUcastnikChanged';
    const METHOD_OBJEDNAVKA_AJAX_OSOBY_UCASTNIK_SAVE_VALIDATION_ERROR = 'objednavkaAjaxOsobyUcastnikSaveValidationErrChanged';
    const METHOD_OBJEDNAVKA_AJAX_FINANCE_PLATBA_SAVE_VALIDATION_ERROR = 'objednavkaAjaxFinancePlatbaSaveValidationErrChanged';

    public function objednavkaDetailChanged();
    public function objednavkaAjaxObecneInfoStavChanged();
    public function objednavkaAjaxObecneInfoStavOdbavenoNezaplacenoChanged();
    public function objednavkaAjaxTsPoznamkyChanged();
    public function objednavkaAjaxTsTajnePoznamkyChanged();
    public function objednavkaAjaxTsDopravaCSChanged();
    public function objednavkaAjaxTsStravovaniCSChanged();
    public function objednavkaAjaxTsUbytovaniCSChanged();
    public function objednavkaAjaxTsPojisteniCSChanged();
    public function objednavkaAjaxFinancePlatbaChanged();
    public function objednavkaAjaxOsobyUcastnikChanged();
    public function objednavkaAjaxOsobyUcastnikSaveValidationErrChanged();
    public function objednavkaAjaxFinancePlatbaSaveValidationErrChanged();
}