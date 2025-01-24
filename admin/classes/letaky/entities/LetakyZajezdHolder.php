<?php

class LetakyZajezdHolder extends Generic_data_class
{

    //region PRIVATE MEMBERS *******************************************************************
    /**
     * @var int
     */
    private $id_serial;
    /**
     * @var tsSerial
     */
    private $serial;
   
    /**
     * todo: udelat tridu pro centralni data
     * @var arr[]
     */
    private $centralniData;
    
    /**
     * @var string[]
     */
    private $pdfLetaky;

    //endregion

    function __construct($id_serial)
    {
        $this->id_serial = $this->check_int($id_serial);
        $this->loadZajezdy();
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
     * @return \tsSerial
     */
    public function getSerial()
    {
        return $this->serial;
    }

    /**
     * @return int
     */
    public function getIdSerial()
    {
        return $this->id_serial;
    }


    /**
     * @return \string[]
     */
    public function getPdfLetaky()
    {
        return $this->pdfLetaky;
    }

    //endregion

    //region PRIVATE METHODS *******************************************************************
    private function loadZajezdy()
    {
        $this->serial = SerialDAO::readSerialById($this->id_serial);
        $this->centralniData = ObjednavkaDAO::dataCentralniData();
        
        //$this->pdfLetaky = ObjednavkaDAO::dataPdfLetaky($this->id_objednavka);
    }
    //endregion

}