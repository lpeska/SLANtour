<?php


class ObjektEnt {

    public $id;
    public $nazev;
    public $poznamka;

    /** @var  AdresaEnt[] */
    public $adresy;
    /** @var  EmailEnt[] */
    public $emaily;
    /** @var  TelefonEnt[] */
    public $telefony;
    /** @var  OrganizaceEnt */
    public $organizace;

    function __construct($id, $nazev, $poznamka)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->poznamka = $poznamka;
    }
    
}