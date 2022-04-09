<?php

class UserKlientEnt {

    const USER_KLIENT_CREATED_BY_CK_YES = 1;

    public $id;
    public $titul;
    public $jmeno;
    public $prijmeni;
    public $email;
    public $telefon;
    public $datum_narozeni;
    public $rodne_cislo;
    public $cislo_pasu;
    public $cislo_op;
    public $storno;

    /** @var  AdresaEnt */
    public $adresa;

    function __construct($id, $titul, $jmeno, $prijmeni, $email, $telefon)
    {
        $this->id = $id;
        $this->titul = $titul;
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->email = $email;
        $this->telefon = $telefon;
    }

}