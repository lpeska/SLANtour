<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsObjednavka
 * 
 * @author jelinm12
 */
class tsSmluvniPodminky {

    //staticke promenne (v administraci jsou hardcoded, a ukladaji se tak do DB, chtelo by je to navazat na tenhle objekt -> centralizovat)
    public static $TYP_ZALOHA = "záloha";
    public static $TYP_DOPLATEK = "doplatek";
    public static $TYP_STORNO = "storno";
    
    //put your code here
    public $prodleva;
    public $typ;
    public $castka;
    public $procento;

    public function __construct($prodleva, $typ, $castka, $procento) {
        $this->prodleva = $prodleva;        
        $this->typ = $typ;        
        $this->castka = $castka;        
        $this->procento = $procento;        
    }

}

?>
