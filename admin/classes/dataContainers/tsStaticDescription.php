<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsObjednavka
 * obsahuje informace "stravovani, doprava, ubytovani" z objednavky
 * @author lpeska
 */
class tsStaticDescription {

    //put your code here
    public $nazev_static_description;


    public function __construct($nazev_static_description) {
        $this->nazev_static_description = $nazev_static_description;
    }

}

?>
