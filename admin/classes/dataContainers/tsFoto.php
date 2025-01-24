<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsObjednavka
 * obsahuje zakladni data z tabulky objednavka
 * @author lpeska
 */
class tsFoto {

    //put your code here
    public $idFoto;
    public $nazevFoto;
    public $url;

    const URL_FULL = "/foto/full/";
    const URL_ICO = "/foto/ico/";

    public function __construct($idFoto, $nazevFoto, $url) {
        $this->idFoto = $idFoto;
        $this->nazevFoto = $nazevFoto;
        $this->url = $url;
    }

}

?>
