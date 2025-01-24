<?php

interface IObjednavkaModelForView
{
    public function registerZajezdObserver($observer);
    public function registerOsobniUdajeObserver($observer);
    public function registerPlatbaObserver($observer);
    public function registerAJAXObserver($observer);

    /**
     * @return \ZajezdEnt | null
     */
    public function getZajezd();

    public function getZeme();

    public function getTopZeme();

    public function getMesice();

    /**
     * @return ObjednavkaSessionEnt
     */
    public function getObjednavkaSession();

    /**
     * @return CentralniDataEnt[]
     */
    public function getZpusobyPlateb();

    public function getKurzEur();

    /**
     * @return SlevaEnt[]
     */
    public function getSlevyKlient();

    /**
     * @return SerialEnt
     */
    public function getSerial();
}