<?php


class OrganizaceEnt {

    const ORGANIZACE_ROLE_PRODEJCE_CA = 1;
    const ORGANIZACE_ROLE_UBYTOVACI_ZARIZENI = 2;
    const ORGANIZACE_ROLE_PARTNER = 3;
    const ORGANIZACE_ROLE_DOPRAVCE = 4;
    const ORGANIZACE_ROLE_JINA = 5;
    const ORGANIZACE_ROLE_POBOCKA = 6;

    public $id;
    public $nazev;
    public $ico;
    public $dic;
    public $role;
    public $provizni_koeficient;

    /** @var  UbytovaniEnt[] */
    public $ubytovani;

    /** @var  AdresaHolder */
    private $adresaHolder;

    /** @var  EmailHolder */
    private $emailHolder;

    /** @var  TelefonHolder */
    private $telefonHolder;

    /** @var  ObjednavkaHolder */
    private $objednavkaHolder;

    function __construct($id, $nazev, $ico, $dic, $role)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->ico = $ico;
        $this->dic = $dic;
        $this->role = $role;
    }

    /**
     * @return AdresaHolder
     */
    public function getAdresaHolder()
    {
        return $this->adresaHolder;
    }

    /**
     * @param AdresaEnt[] $adresy
     */
    public function setAdresy($adresy)
    {
        $this->adresaHolder = new AdresaHolder($adresy);
    }

    /**
     * @return EmailHolder
     */
    public function getEmailHolder()
    {
        return $this->emailHolder;
    }

    /**
     * @param EmailEnt[] $emaily
     */
    public function setEmaily($emaily)
    {
        $this->emailHolder = new EmailHolder($emaily);
    }

    /**
     * @return TelefonHolder
     */
    public function getTelefonHolder()
    {
        return $this->telefonHolder;
    }

    /**
     * @param TelefonEnt[] $telefony
     */
    public function setTelefony($telefony)
    {
        $this->telefonHolder = new TelefonHolder($telefony);
    }

    /**
     * @return ObjednavkaHolder
     */
    public function getObjednavkaHolder()
    {
        return $this->objednavkaHolder;
    }

    /**
     * @param ObjednavkaEnt[] $objednavky
     */
    public function setObjednavky($objednavky)
    {
        $this->objednavkaHolder = new ObjednavkaHolder($objednavky);
    }
}