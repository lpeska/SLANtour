<?php

class UserKlientHolder {

    /** @var  UserKlientEnt[] */
    private $klienti;

    function __construct($klienti)
    {
        $this->klienti = $klienti;
    }

    /**
     * @param $id
     * @return null|UserKlientEnt
     */
    public function getKlient($id)
    {
        foreach ($this->klienti as $klient) {
            if($klient->id == $id)
                return $klient;
        }
        return null;
    }

    /**
     * @return UserKlientEnt[]
     */
    public function getKlienti()
    {
        return $this->klienti;
    }
}