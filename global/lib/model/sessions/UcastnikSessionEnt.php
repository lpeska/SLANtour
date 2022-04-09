<?php


class UcastnikSessionEnt
{

    private $jmeno;
    private $prijmeni;

    private $email;
    private $telefon;
    private $datumNarozeni;
    private $rodneCislo;
    private $cisloDokladu;

    function __construct($jmeno = null, $prijmeni = null, $email = null, $telefon = null, $datumNarozeni = null, $rodneCislo = null, $cisloDokladu = null)
    {
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->email = $email;
        $this->telefon = $telefon;
        $this->datumNarozeni = $datumNarozeni;
        $this->rodneCislo = $rodneCislo;
        $this->cisloDokladu = $cisloDokladu;
    }

    /**
     * @return mixed
     */
    public function getCisloDokladu()
    {
        return $this->cisloDokladu;
    }

    /**
     * @return mixed
     */
    public function getDatumNarozeni()
    {
        return $this->datumNarozeni;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getJmeno()
    {
        return $this->jmeno;
    }

    /**
     * @return mixed
     */
    public function getPrijmeni()
    {
        return $this->prijmeni;
    }

    /**
     * @return mixed
     */
    public function getRodneCislo()
    {
        return $this->rodneCislo;
    }

    /**
     * @return mixed
     */
    public function getTelefon()
    {
        return $this->telefon;
    }


}