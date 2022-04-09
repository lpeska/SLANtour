<?php


class ObjednavkaEnt {

    const STAV_PREDBEZNA_POPTAVKA = 1;  //nepouzivany
    const STAV_OPCE = 3;
    const STAV_PNR = 2;
    const STAV_REZERVACE = 4;
    const STAV_ZALOHA = 5;
    const STAV_PRODANO = 6;
    const STAV_ODBAVENO = 7;
    const STAV_STORNO_KLIENT = 8;
    const STAV_STORNO_CK = 9;
    const STAV_VOUCHER = 10;

    const STAV_GROUP_PREDBEZNE = 1;
    const STAV_GROUP_PALCENO = 2;
    const STAV_GROUP_EXPOROTVANO = 3;
    const STAV_GROUP_STORNO = 4;

    const STAV_NAME_OPCE = 'opce';
    const STAV_NAME_PNR = 'pnr';
    const STAV_NAME_REZERVACE = 'rezervace';
    const STAV_NAME_ZALOHA = 'zaloha';
    const STAV_NAME_PRODANO = 'prodano';
    const STAV_NAME_ODBAVENO = 'odbaveno';
    const STAV_NAME_STORNO_KLIENT = 'storno-klient';
    const STAV_NAME_STORNO_CK = 'storno-ck';
    const STAV_NAME_VOUCHER = 'voucher';

    const STAV_NAME_GROUP_PREDBEZNE = 'predbezne';
    const STAV_NAME_GROUP_PALCENO = 'placeno';
    const STAV_NAME_GROUP_EXPOROTVANO = 'exportovano';
    const STAV_NAME_GROUP_STORNO = 'storno';
    const STAV_NAME_GROUP_VOUCHER = 'voucher';

    const STAV_PRECHOD_NENI_MOZNY = 0;
    const STAV_PRECHOD_JE_MOZNY = 1;
    const STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY = 2;
    const STAV_PRECHOD_JE_MOZNY_AUTOMATICKY = 3;

    const STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_NEMENIT = 0; //nemenit kapacity sluzeb
    const STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_UVOLNIT = 1;  //snizit kapacity sluzeb
    const STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_REZERVOVAT = 2; //navysit kapacity sluzeb

    const ZALOHA_DATE_TRESHOLD = 3;

    public $id;
    public $pocet_osob;
    public $pocet_noci;
    public $celkova_cena;
    public $zbyva_zaplatit;
    public $poznamky;
    public $datum_rezervace;
    public $stav;
    public $storno_datum;
    public $storno_poplatek;
    public $termin_od;
    public $termin_do;
    public $security_code;

    public $suma_provize;
    public $provize_vc_dph;
    public $poznamky_tajne;
    public $doprava;
    public $stravovani;
    public $ubytovani;
    public $pojisteni;

    public $k_uhrade_zaloha;
    public $k_uhrade_zaloha_datspl;
    public $k_uhrade_doplatek_datspl;
    public $k_uhrade_celkem_datspl;

    public $nastupni_mista;

    /** @var  UserKlientEnt */
    public $objednavajici;
    /** @var  OrganizaceEnt */
    public $objednavajiciOrganizace;
    /** @var  OrganizaceEnt */
    private $prodejce;
    /** @var  SerialEnt */
    private $serial;
    /** @var  ZajezdEnt */
    private $zajezd;
    /** @var  FakturaEnt */
    private $fakturaProdejce;

    /** @var  SluzbaHolder */
    private $sluzbaHolder;
    /** @var  SlevaHolder */
    private $slevaHolder;
    /** @var  FakturaHolder */
    private $fakturaHolder;
    /** @var  PlatbaHolder */
    private $platbaHolder;
    /** @var  UserKlientHolder */
    private $ucastnikHolder;

    public static function GET_STAV_SHORT_NAME($stavId)
    {
        return self::GET_STAVY()[$stavId];
    }

    public static function GET_PRECHODY_STAVU()
    {
        return [
            self::STAV_OPCE => [
                self::STAV_OPCE => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_NENI_MOZNY,
            ],
            self::STAV_PNR => [
                self::STAV_OPCE => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_NENI_MOZNY,
            ],
            self::STAV_REZERVACE => [
                self::STAV_OPCE => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_AUTOMATICKY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_AUTOMATICKY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_NENI_MOZNY,
            ],
            self::STAV_ZALOHA => [
                self::STAV_OPCE => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_AUTOMATICKY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_AUTOMATICKY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_JE_MOZNY,
            ],
            self::STAV_PRODANO => [
                self::STAV_OPCE => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_AUTOMATICKY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_AUTOMATICKY,
                self::STAV_PRODANO => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_JE_MOZNY,
            ],
            self::STAV_ODBAVENO => [
                self::STAV_OPCE => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_JE_MOZNY,
            ],
            self::STAV_STORNO_KLIENT => [
                self::STAV_OPCE => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_NENI_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_JE_MOZNY,
            ],
            self::STAV_STORNO_CK => [
                self::STAV_OPCE => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_NENI_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_JE_MOZNY,
            ],
	    self::STAV_VOUCHER => [
                self::STAV_OPCE => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_PNR => self::STAV_PRECHOD_NENI_MOZNY,
                self::STAV_REZERVACE => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ZALOHA => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_PRODANO => self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
                self::STAV_ODBAVENO => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_KLIENT => self::STAV_PRECHOD_JE_MOZNY,
                self::STAV_STORNO_CK => self::STAV_PRECHOD_JE_MOZNY,
		self::STAV_VOUCHER => self::STAV_PRECHOD_NENI_MOZNY,
            ],

        ];
    }

    public static function GET_STAVY()
    {
        return [
            self::STAV_OPCE => self::STAV_NAME_OPCE,
            self::STAV_PNR => self::STAV_NAME_PNR,
            self::STAV_REZERVACE => self::STAV_NAME_REZERVACE,
            self::STAV_ZALOHA => self::STAV_NAME_ZALOHA,
            self::STAV_PRODANO => self::STAV_NAME_PRODANO,
            self::STAV_ODBAVENO => self::STAV_NAME_ODBAVENO,
            self::STAV_STORNO_KLIENT => self::STAV_NAME_STORNO_KLIENT,
            self::STAV_STORNO_CK => self::STAV_NAME_STORNO_CK,
	    self::STAV_VOUCHER => self::STAV_NAME_VOUCHER,
        ];
    }

    //Lada: tady byla docela zasadni chyba - OPCE má mít zarezervované kapacity!!!
    public static function GET_STAVY_REZERVOVANA_KAPACITA()
    {
        return [
            self::STAV_OPCE,
            self::STAV_REZERVACE,
            self::STAV_ZALOHA,
            self::STAV_PRODANO,
            self::STAV_ODBAVENO,
        ];
    }

    public static function GET_STAVY_NEREZERVOVANA_KAPACITA()
    {
        return [  
	    self::STAV_PREDBEZNA_POPTAVKA,          
            self::STAV_PNR,
            self::STAV_STORNO_KLIENT,
            self::STAV_STORNO_CK,
	    self::STAV_VOUCHER,
        ];
    }
    function __construct($id, $pocet_osob, $pocet_noci, $celkova_cena, $zbyva_zaplatit, $poznamky, $datum_rezervace, $stav, $termin_od, $termin_do, $storno_datum, $storno_poplatek)
    {
        $this->id = $id;
        $this->pocet_osob = $pocet_osob;
        $this->pocet_noci = $pocet_noci;
        $this->celkova_cena = $celkova_cena;
        $this->zbyva_zaplatit = $zbyva_zaplatit;
        $this->poznamky = $poznamky;
        $this->datum_rezervace = $datum_rezervace;
        $this->stav = $stav;
        $this->setTerminOd($termin_od);
        $this->setTerminDo($termin_do);
        $this->storno_datum = $storno_datum;
        $this->storno_poplatek = $storno_poplatek;
    }

    //region GET & SET *****************************************************************************************

    /**
     * @param SluzbaEnt[] $sluzby
     */
    public function setSluzby($sluzby)
    {
        $this->sluzbaHolder = new SluzbaHolder($sluzby);
    }

    /**
     * @param SlevaEnt[] $slevy
     */
    public function setSlevy($slevy)
    {
        $this->slevaHolder = new SlevaHolder($slevy);
    }

    /**
     * @param FakturaEnt[] $faktury
     */
    public function setFaktury($faktury)
    {
        $this->fakturaHolder = new FakturaHolder($faktury);
    }

    /**
     * @param PlatbaEnt[] $platby
     */
    public function setPlatby($platby)
    {
        $this->platbaHolder = new PlatbaHolder($platby);
    }

    /**
     * @param UserKlientEnt[] $ucastnici
     */
    public function setUcastnici($ucastnici)
    {
        $this->ucastnikHolder = new UserKlientHolder($ucastnici);
    }

    /**
     * @return OrganizaceEnt
     */
    public function getProdejce()
    {
        return $this->prodejce;
    }

    /**
     * @param OrganizaceEnt $prodejce
     */
    public function setProdejce($prodejce)
    {
        $this->prodejce = $prodejce;
    }

    public function getFakturyHolder()
    {
        return $this->fakturaHolder;
    }

    /**
     * @return PlatbaHolder
     */
    public function getPlatbyHolder()
    {
        return $this->platbaHolder;
    }

    /**
     * @return SluzbaHolder
     */
    public function getSluzbaHolder()
    {
        return $this->sluzbaHolder;
    }

    /**
     * @return SlevaHolder
     */
    public function getSlevaHolder()
    {
        return $this->slevaHolder;
    }

    /**
     * @return UserKlientHolder
     */
    public function getUcastnikHolder()
    {
        return $this->ucastnikHolder;
    }

    /**
     * @return FakturaEnt
     */
    public function getFakturaProdejce()
    {
        return $this->fakturaProdejce;
    }

    /**
     * @param string $fakturaProdejce
     */
    public function setFakturaProdejce($fakturaProdejce)
    {
        $this->fakturaProdejce = $fakturaProdejce;
    }

    /**
     * @return ZajezdEnt
     */
    public function getZajezd()
    {
        return $this->zajezd;
    }

    /**
     * @param ZajezdEnt $zajezd
     */
    public function setZajezd($zajezd)
    {
        $this->zajezd = $zajezd;
    }

    /**
     * @param mixed $termin_od
     */
    public function setTerminOd($termin_od)
    {
        $this->termin_od = $termin_od == '0000-00-00' ? null : $termin_od;
    }

    /**
     * @param mixed $termin_do
     */
    public function setTerminDo($termin_do)
    {
        $this->termin_do = $termin_do == '0000-00-00' ? null : $termin_do;
    }

    /**
     * @return SerialEnt
     */
    public function getSerial()
    {
        return $this->serial;
    }

    /**
     * @param SerialEnt $serial
     */
    public function setSerial($serial)
    {
        $this->serial = $serial;
    }

    //endregion

    //region CALC **********************************************************************************************

    /**
     * Suma castek veskerych sluzeb objednavky (bez slev)
     * @uses sluzbaHolder, pocet_noci
     * @return int
     */
    public function calcZaSluzbyTotalNoSlevy()
    {
        return $this->sluzbaHolder->calcPriceTotalNoSlevy($this->pocet_noci);
    }

    /**
     * Suma castek veskerych slev objednavky
     * @uses sluzbaHolder, pocet_noci
     * @return int
     */
    public function calcSlevaTotal()
    {
        return $this->sluzbaHolder->calcSlevaPriceTotal($this->pocet_noci);
    }

    /**
     * Spocita provizi prodejce objednavky
     */
    public function calcProvize()
    {
        $provize = 0;

        //manualne nastavena provize
        if ($this->suma_provize != -1)
            return $this->suma_provize;

        if ($this->serial->typ_provize == SerialEnt::TYP_PROVIZE_FIXNI_OSOBA) {
            $provize = $this->pocet_osob * $this->serial->vyse_provize;
        } else if ($this->serial->typ_provize == SerialEnt::TYP_PROVIZE_PROCENTO_Z_OBJEDNAVKY) {
            $provize = $this->calcFinalniCenaObjednavky() * ($this->serial->vyse_provize / 100);
        } else if ($this->serial->typ_provize == SerialEnt::TYP_PROVIZE_DLE_SLUZEB) {
            $objednaneSluzby = $this->sluzbaHolder->getObjednaneSluzby();
            if (!is_null($objednaneSluzby)) {
                foreach ($objednaneSluzby as $sluzba) {
                    if ($sluzba->typ_provize == SluzbaEnt::TYP_PROVIZE_FIXNI) {
                        $provize += $sluzba->vyse_provize;
                    } else if ($sluzba->typ_provize == SluzbaEnt::TYP_PROVIZE_PROCENTO) {
                        $provize += $sluzba->calcCastkaFull($sluzba->pocet, $this->pocet_noci) * ($sluzba->vyse_provize / 100);
                    }
                }
            }
        }

        //koeficient provizni agentury
        $provize = $provize * $this->prodejce->provizni_koeficient;

        return round($provize);
    }

    /**
     * Spocita zaklad provize objednavky (tzn. od sumy provize odecte DPH)
     * todo k cemu toto slouzi?
     * @uses suma_provize, provize_vc_dph
     * @param int $currentDPH
     * @return int
     */
    public function calcProvizeZaklad($currentDPH)
    {
        return $this->calcProvize() - $this->calcProvizeDPHCastka($currentDPH);
    }

    /**
     * Spocita DPH provize
     * @uses provize_vc_dph, suma_provize
     * @param $currentDPH
     * @return float|int
     */
    public function calcProvizeDPHCastka($currentDPH)
    {
        //pokud neni provize vcetne dph, zadnou castku nepocitej, je nulova
        if ($this->provize_vc_dph == 0)
            return 0;

        return round($this->calcProvize() / (100 + $currentDPH) * $currentDPH);
    }

    /**
     * @uses stav, storno_poplatek, sluzbaHolder, pocet_noci, sleva_holder, pocet_noci, platbaHolder
     * @return int
     */
    public function calcZbyvaUhradit()
    {
        return $this->calcFinalniCenaObjednavky() - $this->calcUhrazeno();
    }

    /**
     * Konecna cena objednavky
     * @uses stav, storno_poplatek, sluzbaHolder, pocet_noci, sleva_holder
     * @return int
     */
    public function calcFinalniCenaObjednavky()
    {
        switch ($this->stav) {
            case self::STAV_STORNO_KLIENT:
                $finalniCenaObjednavky = $this->storno_poplatek;
                break;
	    case self::STAV_VOUCHER:
                $finalniCenaObjednavky = $this->storno_poplatek;
                break;

            case self::STAV_STORNO_CK:
                $finalniCenaObjednavky = 0;
                break;
            default:
                //za sluzby - za objednane slevy (ktere nejsou typu sluzba) - storno poplatek
                $finalniCenaObjednavky = $this->calcZaSluzbyTotal() - $this->calcSlevyKtereNejsouSluzba() + $this->storno_poplatek;
                break;
        }

        return $finalniCenaObjednavky;
    }

    /**
     * Suma castek veskerych sluzeb objednavky
     * @uses sluzbaHolder, pocet_noci
     * @return int
     */
    public function calcZaSluzbyTotal()
    {
        return $this->sluzbaHolder->calcPriceTotal($this->pocet_noci);
    }

    /**
     * Suma vsech slev, ktere nejsou typu sluzba
     * @uses slevaHolder, pocet_osob, sluzbaHolder
     * @return int
     */
    public function calcSlevyKtereNejsouSluzba()
    {
        return $this->slevaHolder->calcObjednaneSlevy($this->calcZaSluzbyZakladni(), $this->pocet_osob);
    }

    /**
     * Cena ze zakladnich sluzeb, z ktere se pocitaji slevy a storna
     * @uses sluzbaHolder, pocet_noci
     * @return int
     */
    public function calcZaSluzbyZakladni()
    {
        return $this->sluzbaHolder->calcPriceTotalZakladni($this->pocet_noci);
    }

    /**
     * @uses platbaHolder
     * @return int
     */
    public function calcUhrazeno()
    {
        return $this->platbaHolder->calcCastkaSum();
    }

    /**
     * @uses serial, termin_od, sluzbaHolder, pocet_noci
     * @return float|int
     */
    public function calcStornoPoplatek()
    {
        //termin neurcen, storno = 0
        if (is_null($this->termin_od))
            return 0;

        /** @var SmluvniPodminkyEnt[] $storna */
        $aktualniStorno = $this->getSerial()->getSmluvniPodminkyNazev()->getSmluvniPodminkyHolder()->getAktualniStorno($this->termin_od);
        if ($aktualniStorno->getCastka() == 0) {
            $stornoPoplatek = $this->calcZaSluzbyZakladni() * $aktualniStorno->getProcento() / 100;
        } else {
            $stornoPoplatek = $aktualniStorno->getCastka();
        }

        return $stornoPoplatek;
    }

    /**
     *
     * @return int
     */
    public function calcStornoPoplatekCK()
    {
        return 0;
    }

    /**
     * Castka prvni zalohy (dle slmuvnich podminek muze byt zaloh vice).
     */
    public function calcPrvniZalohaCastka()
    {
        /*edit Lada: chovani se zalohou bylo spatne:
         * jednak pokud je zalohaCastka implicitne definovana jako 0 a vsechny zalohy jsou prosle, tak by se mela platit plna castka v case prvni zalohy a ne 0
         * druhak nektere smluvni podminky nemaji zadne zalohy (vstupenky se 100% stornem treba) a na tom ti skapalo foreach a calcDoplatekDatum

                  */
        //  $zalohaCastka = 0;
        $zalohaCastka = $this->calcFinalniCenaObjednavky();

        //je u objednavky zaloha prepsana rucne?
        if ($this->k_uhrade_zaloha != 0) {
            $zalohaCastka = $this->k_uhrade_zaloha;
        } else {
            $dnuDoOdjezdu = $this->calcDnuDoOdjezdu();
            $zalohaList = $this->serial->getSmluvniPodminkyNazev()->getSmluvniPodminkyHolder()->getZalohy();
            //pokud nemame zadnou zalohu k dispozici (treba ve smluvnich podminkach neni ani uvedena, je prvni zaloha rovna bud 100% castky, nebo 0 - uvidime...
            if (is_array($zalohaList)) {
                foreach ($zalohaList as $zaloha) {
                    if ($dnuDoOdjezdu >= $zaloha->getProdleva()) {
                        $zalohaCastka = $zaloha->calcCastka($this->calcFinalniCenaObjednavky());
                        break;
                    }
                }
            }
        }

        return $zalohaCastka;
    }

    /**
     * Datum splatnosti prvni zalohy (dle slmuvnich podminek muze byt zaloh vice).
     */
    public function calcPrvniZalohaDatum()
    {
        //je u objednavky prepsane datum rucne?
        if ($this->k_uhrade_zaloha_datspl == '0000-00-00' || $this->k_uhrade_zaloha_datspl == '') {
            return date('d-m-Y', strtotime($this->datum_rezervace . " + " . self::ZALOHA_DATE_TRESHOLD . " days"));
        } else {
            return $this->k_uhrade_zaloha_datspl;
        }
    }

    /**
     * Castka doplatku
     * @return int
     */
    public function calcDoplatekCastka()
    {
        //je u objednavky prepsany doplatek rucne?
        $finalniCenaObjednavky = $this->calcFinalniCenaObjednavky();
        $zalohaCastka = $this->calcPrvniZalohaCastka();
        $uhrazeno = $this->calcUhrazeno();

        //provedl klient nejakou platbu?
        if ($uhrazeno > 0) {
            $doplatekCastka = $finalniCenaObjednavky - $uhrazeno;
        } else {
            $doplatekCastka = $finalniCenaObjednavky - $zalohaCastka;
        }

        return $doplatekCastka;
    }

    /**
     * Datum splatnosti doplatku
     * @return bool|string
     */
    public function calcDoplatekDatum()
    {
        //je u objednavky prepsane datum rucne?
        if ($this->k_uhrade_doplatek_datspl == '0000-00-00' || $this->k_uhrade_doplatek_datspl == '') {
            $zalohaList = $this->serial->getSmluvniPodminkyNazev()->getSmluvniPodminkyHolder()->getZalohy();
            if (is_array($zalohaList)) {
                $prvniZaloha = $zalohaList[0];
                return date('d-m-Y', strtotime($this->termin_od . ' - ' . $prvniZaloha->getProdleva() . ' days'));
            } else {
                return date('d-m-Y', strtotime($this->datum_rezervace . " + " . self::ZALOHA_DATE_TRESHOLD . " days"));
            }
        } else {
            return $this->k_uhrade_doplatek_datspl;
        }
    }

    /**
     * Datum splatnosti celkove castky
     * @return bool|string
     */
    public function calcCelkemDatum()
    {
        //je u objednavky prepsane datum rucne?
        if ($this->k_uhrade_celkem_datspl == '0000-00-00' || $this->k_uhrade_celkem_datspl == '') {
            return date('d-m-Y', strtotime($this->termin_od));
        } else {
            return $this->k_uhrade_celkem_datspl;
        }
    }

    /**
     * Pocet dnu mezi datem rezervace a datem odjezdu
     * @return mixed
     */
    private function calcDnuDoOdjezdu()
    {
        $now = new DateTime($this->datum_rezervace);
        $ref = new DateTime($this->termin_od . " 00:00:00");
        $diff = $now->diff($ref, true);
        $dnuDoOdjezdu = $diff->days;

        return $dnuDoOdjezdu;
    }

    //endregion

    /**
     * Vraci true, pokud se na cilovy stav da prejit, jinak false
     * @uses serial, platbaHolder, stav, storno_poplatek, sluzbaHolder, pocet_noci, sleva_holder
     * @param $idCilovyStav
     * @return bool
     */
    public function isAllowedPrechod($idCilovyStav)
    {
        $isAllowed = false;

        if (in_array($idCilovyStav, $this->mozneStavy()))
            $isAllowed = true;

        return $isAllowed;
    }

    /**
     * @param $idCilovyStav
     * @return int
     */
    public function requireCapacityChange($idCilovyStav)
    {
        if (
            in_array($this->stav, self::GET_STAVY_REZERVOVANA_KAPACITA()) &&
            in_array($idCilovyStav, self::GET_STAVY_NEREZERVOVANA_KAPACITA())
        )
            return self::STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_UVOLNIT;

        if (
            in_array($this->stav, self::GET_STAVY_NEREZERVOVANA_KAPACITA()) &&
            in_array($idCilovyStav, self::GET_STAVY_REZERVOVANA_KAPACITA())
        )
            return self::STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_REZERVOVAT;

        return self::STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_NEMENIT;
    }

    /**
     * Vrati pole stavu, na ktere je mozne z aktualniho stavu prejit. Pokud je mezi stavy mozne prejit na "skupinu stavu", je vracen jen aktualni stav z teto skupiny.
     * @uses serial, platbaHolder, stav, storno_poplatek, sluzbaHolder, pocet_noci, sleva_holder
     * @return array|null
     */
    public function mozneStavy()
    {
        $mozneStavy = null;
        $moznePrechody = self::GET_PRECHODY_STAVU()[$this->stav];

        if (is_array($moznePrechody)) {
            foreach ($moznePrechody as $stav => $prechod) {
                if ($prechod == self::STAV_PRECHOD_JE_MOZNY) {
                    $mozneStavy[] = $stav;
                } else if ($prechod == self::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY) {
                    $skupinovyStav = $this->predictStavZPlateb();
                    if (@!in_array($skupinovyStav, $mozneStavy))
                        $mozneStavy[] = $skupinovyStav;
                }
            }
        }

        return $mozneStavy;
    }

    /**
     * Vrati id stavu (ze skupiny stavu "placeno"), ktery by mela objednavka mit na zaklade plateb, ktere klient zaplatil
     * @uses serial, platbaHolder, stav, storno_poplatek, sluzbaHolder, pocet_noci, sleva_holder
     * @return int
     */
    public function predictStavZPlateb()
    {
        $stavId = null;
        $uhrazeno = $this->calcUhrazeno();
        //$zaloha = $this->serial->smluvniPodminkyNazev->getSmluvniPodminkyHolder()->getZalohy()[0];  //note zajima me jen prvni zaloha - mozna nekdy bude potreba zmenit
        //$zalohaCastka = is_null($zaloha) ? 0 : $zaloha->calcCastka($this->calcFinalniCenaObjednavky());

        //porovnej uhrazeno se smluv. podminkami a tim ziskej stav, na ktery ma byt zmeneno
        //edit Lada: zaloha je obcas nula (plati se cela objednavka), potom pri nulove platbe spatne fungoval navrat do stavu "rezervace"
        //mozna ze je chyba na radku 650 - misto ? 0 tam mit neco jineho, ale nejsem si jisty tvoji bussines logikou, tak jsem do toho nehrabal
        //edit 2 Lada: po rozhovoru s tatou jsem tu podminku prepsal na pokud existuje jakakoli nenulova platba, preved do stavu zaloha 
        // (platba nemusi byt tak velka jako pozadovana vyse zalohy - nekdy treba chybi par korun kvuli doobjednavanym sluzbam a mate to)
        if ($uhrazeno <= 0) {
            $stavId = self::STAV_REZERVACE;
        } else if ($uhrazeno >= $this->calcFinalniCenaObjednavky()) {
            $stavId = self::STAV_PRODANO;
        } else if ($uhrazeno > 0) {
            $stavId = self::STAV_ZALOHA;
        }
        //   echo "uhrazeno: ".$uhrazeno;

        //puvodni verze, pokud by byla nekdy potreba
        /*if ($uhrazeno <=0 or $uhrazeno < $zalohaCastka) {
            $stavId = self::STAV_REZERVACE;
        } else if ($uhrazeno >= $this->calcFinalniCenaObjednavky()) {
            $stavId = self::STAV_PRODANO;
        } else if ($uhrazeno >= $zalohaCastka) {
            $stavId = self::STAV_ZALOHA;
        }*/

        return $stavId;
    }
}