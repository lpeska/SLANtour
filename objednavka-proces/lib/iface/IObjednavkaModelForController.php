<?php

interface IObjednavkaModelForController {

    public function zajezd();
    public function terminChanged();
    public function osobniUdaje();
    public function platba();
    public function souhrn();
    public function specialRangesAjax();
    public function sluzbyAjax();
    public function slevyAjax();
    public function slevyKlientSerialAjax();
    public function slevyKlientZajezdAjax();
    public function terminZajezdAjax();
}