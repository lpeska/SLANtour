<?php
/**
 * Description of tsSerial
 * obsahuje zakladni data z tabulky serial
 * @author Martin Jelinek
 */
class tsSerial {
    const SABLONA_JEDNO_UBYTOVANI = 12;
    public $id;
    public $typ;
    public $nazev;
    /**
     * @var tsObjekt[]
     */
    public $objekty;
    /**
     * @var tsZajezd[]
     */
    public $zajezdy;

    /**
     * @var boolean
     */
    public $hasZajezdy;

    public function __construct($id, $typ, $nazev) {
        $this->id = $id;
        $this->typ = $typ;
        $this->nazev = $nazev;
    }

    /**
     * @return bool true pokud ma aktivni alespon 1 zajezd, jinak false
     */
    public function hasAktivniZajezd()
    {
        foreach($this->zajezdy as $zajezd) {
            if($zajezd->isAktivni())
                return true;
        }
        return false;
    }
    
    /**
     * @return nazev zajezdu vc. ubytovani
     */
    public function getNazev()
    {
        if($this->dataSerial->id_sablony_zobrazeni == self::SABLONA_JEDNO_UBYTOVANI){
            if($this->dataSerial->nazev_ubytovani != ""){
                return $this->dataSerial->nazev_ubytovani.", ".$this->nazev;
            }
            return $this->nazev;
            
        }
        return $this->nazev;
    }    

}