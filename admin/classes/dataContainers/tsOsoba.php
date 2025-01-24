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
class tsOsoba
{
    //put your code here
    public $id;
    public $jmeno;
    public $prijmeni;
    public $rodne_cislo;
    public $cislo_op;
    public $email;
    public $telefon;

    public $titul;
    public $datum_narozeni;
    public $cislo_pasu;
    
    public $ulice;
    public $mesto;
    public $psc;
    
    /**
     * @var tsAdresa
     */
    public $adresa;


    public function __construct($id, $jmeno, $prijmeni, $rodne_cislo, $cislo_op, $telefon, $email, $ulice="", $mesto="", $psc="", $storno=0)
    {
        $this->id = $id;
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->rodne_cislo = $rodne_cislo;
        $this->cislo_op = $cislo_op;
        $this->email = $email;
        $this->telefon = $telefon;
        
        $this->ulice = $ulice;
        $this->mesto = $mesto;
        $this->psc = $psc;
        $this->storno = $storno;        
    }
}

?>
