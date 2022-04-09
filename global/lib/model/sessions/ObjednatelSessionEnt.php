<?php


class ObjednatelSessionEnt {

    const JSEM_UCASTNIKEM_ZAJEZDU_ANO = 1;
    const JSEM_UCASTNIKEM_ZAJEZDU_NE = 0;

    private $jmeno;
    private $prijmeni;
    private $datumNarozeniDen;
    private $datumNarozeniMesic;
    private $datumNarozeniRok;
    private $jsemUcastnikemZajezdu;

    private $email;
    private $telefonPredvolba;
    private $telefon;
    private $mesto;
    private $uliceCp;
    private $psc;
    private $zasilatNewsletter;

    function __construct(
        $jmeno = null,
        $prijmeni = null,
        $datumNarozeniDen = null,
        $datumNarozeniMesic = null,
        $datumNarozeniRok = null,
        $jsemUcastnikemZajezdu = "1",
        $email = null,
        $telefonPredvolba = "+420",
        $telefon = null,
        $mesto = null,
        $uliceCp = null,
        $psc = null,
        $zasilatNewsletter = "1",
        $rodneCislo = null,
        $cisloDokladu = null    )
    {
        $this->jmeno = $jmeno;
        $this->prijmeni = $prijmeni;
        $this->datumNarozeniDen = $datumNarozeniDen;
        $this->datumNarozeniMesic = $datumNarozeniMesic;
        $this->datumNarozeniRok = $datumNarozeniRok;
        $this->jsemUcastnikemZajezdu = $jsemUcastnikemZajezdu;
        $this->email = $email;
        $this->telefonPredvolba = $telefonPredvolba;
        $this->telefon = $telefon;
        $this->mesto = $mesto;
        $this->uliceCp = $uliceCp;
        $this->psc = $psc;
        $this->zasilatNewsletter = $zasilatNewsletter;
        $this->rodneCislo = $rodneCislo;
        $this->cisloDokladu = $cisloDokladu;
    }


    /**
     * @param mixed $datumNarozeniDen
     */
    public function setDatumNarozeniDen($datumNarozeniDen)
    {
        $this->datumNarozeniDen = $datumNarozeniDen;
    }

    /**
     * @return mixed
     */
    public function getDatumNarozeniDen()
    {
        return $this->datumNarozeniDen;
    }

    /**
     * @param mixed $datumNarozeniMesic
     */
    public function setDatumNarozeniMesic($datumNarozeniMesic)
    {
        $this->datumNarozeniMesic = $datumNarozeniMesic;
    }

    /**
     * @return mixed
     */
    public function getDatumNarozeniMesic()
    {
        return $this->datumNarozeniMesic;
    }

    /**
     * @param mixed $datumNarozeniRok
     */
    public function setDatumNarozeniRok($datumNarozeniRok)
    {
        $this->datumNarozeniRok = $datumNarozeniRok;
    }

    /**
     * @return mixed
     */
    public function getDatumNarozeniRok()
    {
        return $this->datumNarozeniRok;
    }
    /**
     * @return mixed
     */
    public function getDatumNarozeni()
    {
        return $this->datumNarozeniDen.". ".$this->datumNarozeniMesic." ".$this->datumNarozeniRok;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $jmeno
     */
    public function setJmeno($jmeno)
    {
        $this->jmeno = $jmeno;
    }
    
    /**
     * @return mixed
     */
    public function getJmeno()
    {
        return $this->jmeno;
    }

    /**
     * @param mixed $jsemUcastnikemZajezdu
     */
    public function setJsemUcastnikemZajezdu($jsemUcastnikemZajezdu)
    {
        $this->jsemUcastnikemZajezdu = $jsemUcastnikemZajezdu;
    }

    /**
     * @return mixed
     */
    public function getJsemUcastnikemZajezdu()
    {
        return $this->jsemUcastnikemZajezdu;
    }

    /**
     * @param mixed $mesto
     */
    public function setMesto($mesto)
    {
        $this->mesto = $mesto;
    }

    /**
     * @return mixed
     */
    public function getMesto()
    {
        return $this->mesto;
    }

    /**
     * @param mixed $prijmeni
     */
    public function setPrijmeni($prijmeni)
    {
        $this->prijmeni = $prijmeni;
    }

    /**
     * @return mixed
     */
    public function getPrijmeni()
    {
        return $this->prijmeni;
    }

    /**
     * @param mixed $psc
     */
    public function setPsc($psc)
    {
        $this->psc = $psc;
    }

    /**
     * @return mixed
     */
    public function getPsc()
    {
        return $this->psc;
    }

    /**
     * @param mixed $telefon
     */
    public function setTelefon($telefon)
    {
        $this->telefon = $telefon;
    }

    /**
     * @return mixed
     */
    public function getTelefon()
    {
        return $this->telefon;
    }

    /**
     * @param mixed $telefonPredvolba
     */
    public function setTelefonPredvolba($telefonPredvolba)
    {
        $this->telefonPredvolba = $telefonPredvolba;
    }

    /**
     * @return mixed
     */
    public function getTelefonPredvolba()
    {
        return $this->telefonPredvolba;
    }

    /**
     * @param mixed $uliceCp
     */
    public function setUliceCp($uliceCp)
    {
        $this->uliceCp = $uliceCp;
    }

    /**
     * @return mixed
     */
    public function getUliceCp()
    {
        return $this->uliceCp;
    }

    /**
     * @param mixed $zasilatNewsletter
     */
    public function setZasilatNewsletter($zasilatNewsletter)
    {
        $this->zasilatNewsletter = $zasilatNewsletter;
    }

    /**
     * @return mixed
     */
    public function getZasilatNewsletter()
    {
        return $this->zasilatNewsletter;
    }

    /**
     * @return mixed
     */
    public function getRodneCislo()
    {
        return $this->rodneCislo;
    }

    /**
     * @param mixed $jmeno
     */
    public function setRodneCislo($rodneCislo)
    {
        $this->rodneCislo = $rodneCislo;
    }
    
    /**
     * @return mixed
     */
    public function getCisloDokladu()
    {
        return $this->cisloDokladu;
    }

    /**
     * @param mixed $jmeno
     */
    public function setCisloDokladu($cisloDokladu)
    {
        $this->cisloDokladu = $cisloDokladu;
    }
}