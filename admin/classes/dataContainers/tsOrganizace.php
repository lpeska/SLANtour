<?php


class tsOrganizace {

    public $id;
    public $nazev;
    public $ico;
    public $role;

    public $telefon;
    public $email;
    /**
     * @var tsAdresa
     */
    public $adresa;

    function __construct($ico, $id, $nazev, $role)
    {
        $this->ico = $ico;
        $this->id = $id;
        $this->nazev = $nazev;
        $this->role = $role;
    }


}