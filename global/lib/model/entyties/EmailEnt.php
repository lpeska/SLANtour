<?php


class EmailEnt
{

    public $email;
    public $typ_kontaktu;

    function __construct($email)
    {
        $this->email = $email;
    }

}