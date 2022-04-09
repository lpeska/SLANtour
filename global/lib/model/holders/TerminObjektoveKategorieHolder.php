<?php

class TerminObjektoveKategorieHolder
{
    /** @var  TerminObjektoveKategorieEnt[] */
    private $terminyObjektoveKategorie;

    function __construct($terminyObjektoveKategorie)
    {
        $this->terminyObjektoveKategorie = $terminyObjektoveKategorie;
    }

    /**
     * @return TerminObjektoveKategorieEnt[]
     */
    public function getTerminyObjektoveKategorie()
    {
        return $this->terminyObjektoveKategorie;
    }
}