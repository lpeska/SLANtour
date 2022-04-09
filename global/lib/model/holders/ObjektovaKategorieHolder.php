<?php

class ObjektovaKategorieHolder {

    /** @var  ObjektovaKategorieEnt[] */
    private $objektoveKategorie;

    function __construct($objektoveKategorie)
    {
        $this->objektoveKategorie = $objektoveKategorie;
    }

    /**
     * @return ObjektovaKategorieEnt[]
     */
    public function getObjektoveKategorie()
    {
        return $this->objektoveKategorie;
    }
}