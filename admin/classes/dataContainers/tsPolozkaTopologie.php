<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tsObjednavajici
 *informace o objednavateli (prozatim pouze jako osoba, az bude inteligentneji vyreseny organizace upravime to)
 * @author lpeska
 */
class tsPolozkaTopologie
{
    //put your code here
    public $id;
    public $jmeno;
    public $prijmeni;
    public $id_objednavka;
    public $nazev;
    public $odjezdova_mista;
    public $rows;
    public $cols;
    public $posx;
    public $posy;
    public $class;
    public $text;
    public $text_obsazeno;
    public $obsazeno;

    /**
     * @var tsAdresa
     */



    public function __construct($id, $jmeno, $prijmeni, $id_objednavka, $nazev, $odjezdova_mista, $rows, $cols, $posx, $posy, $class, $text, $text_obsazeno, $obsazeno)
    {
        $this->id = $id;
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->id_objednavka = $id_objednavka;
        $this->nazev = $nazev;
        $this->odjezdova_mista = $odjezdova_mista;
        
        $this->rows = $rows;
        $this->cols = $cols;
        $this->posx = $posx;
        $this->posy = $posy;
        $this->class = $class;
        $this->text = $text;
        $this->text_obsazeno = $text_obsazeno;
        $this->obsazeno = $obsazeno;
    }
}

?>
