<?php

interface IObjednavkaModelAJAXObserver
{
    public function modelAJAXSSChanged();

    public function modelAJAXSluzbyChanged();

    public function modelAJAXSlevyChanged();

    public function modelAJAXSlevyKlientChanged();

    public function modelAJAXTerminChanged();

    public function modelAJAXSerialChanged();
}