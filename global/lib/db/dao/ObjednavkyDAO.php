<?php

class ObjednavkyDAO {

    /**
     * @var DatabaseProvider
     */
    private static $db;

    public static function init()
    {
        self::$db = DatabaseProvider::get_instance();
    }

    public static function readObjednavkaDetailsById($idObjednavka)
    {
        $objednavka = null;
        self::$db->connect();

        $stmt = self::$db->query(ObjednavkySQLBuilder::readObjednavkaDetailsByIdSQL([$idObjednavka, SerialEnt::ZAKLADNI_FOTO_ANO]));
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        //var_dump($stmt);
        if ($row) {
            if($row->termin_do == "" or $row->termin_do == "0000-00-00" or is_null($row->termin_do)){
               $row->termin_do = $row->do ;
            }
            if($row->termin_od == "" or $row->termin_od == "0000-00-00" or is_null($row->termin_od)){
               $row->termin_od = $row->od ;
            }
            $pocetNoci = round((strtotime($row->termin_do) - strtotime($row->termin_od)) / (60 * 60 * 24));
            $objednavka = new ObjednavkaEnt($row->id_objednavka, $row->pocet_osob, $pocetNoci, $row->celkova_cena, $row->zbyva_zaplatit, $row->poznamky, $row->datum_rezervace, $row->stav, $row->termin_od, $row->termin_do, $row->storno_datum, $row->storno_poplatek);
            $objednavka->suma_provize = $row->suma_provize;
            $objednavka->provize_vc_dph = $row->provize_vc_dph;
            $objednavka->security_code = $row->security_code;
            $objednavka->poznamky_tajne = $row->poznamky_tajne;
            $objednavka->doprava = $row->doprava;
            $objednavka->stravovani = $row->stravovani;
            $objednavka->ubytovani = $row->ubytovani;
            $objednavka->pojisteni = $row->pojisteni;
            $objednavka->k_uhrade_zaloha = $row->k_uhrade_zaloha;
            $objednavka->k_uhrade_zaloha_datspl = $row->k_uhrade_zaloha_datspl;
            $objednavka->k_uhrade_doplatek = $row->k_uhrade_doplatek;
            $objednavka->k_uhrade_doplatek_datspl = $row->k_uhrade_doplatek_datspl;

            //serial
            $serial = new SerialEnt($row->id_serial, $row->nazev, $row->id_sablony_zobrazeni);
            $serial->dlouhodobe_zajezdy = $row->dlouhodobe_zajezdy;
            $serial->mainFoto = new FotoEnt(null, $row->foto_url, $row->foto_nazev, null);;
            $serial->typ_provize = $row->typ_provize;
            $serial->vyse_provize = $row->vyse_provize;
            $objednavka->setSerial($serial);

            //zajezd
            $objednavka->setZajezd(new ZajezdEnt($row->id_zajezd, $row->od, $row->do, $row->dlouhodobe_zajezdy));
        }

        //objednavka neexistuje
        if (is_null($objednavka))
            return $objednavka;

        //objekty serialu
        $stmt = self::$db->query(ObjednavkySQLBuilder::readObjektyBySerialId([$objednavka->getSerial()->id]));
        $objekty = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $objekt = new ObjektEnt($row->id_objektu, $row->nazev_objektu, $row->poznamka);
            $objekty[] = $objekt;
        }
        $objednavka->getSerial()->objekty = $objekty;

        //smluvni podminky
        $stmt = self::$db->query(ObjednavkySQLBuilder::readSmluvniPodminkyByObjednavkaIdSQL([$idObjednavka]));
        $smluvniPodminkyNazev = null;
        $smluvniPodminkyList = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            //pouze poprve vytvor objekt SmluvniPodminkyNazevEnt
            if (is_null($smluvniPodminkyNazev))
                $smluvniPodminkyNazev = new SmluvniPodminkyNazevEnt($row->id_smluvni_podminky_nazev, $row->nazev, $row->dokument_id);
            $smluvniPodminkyList[] = new SmluvniPodminkyEnt($row->id_smluvni_podminky, $row->id_smluvni_podminky_nazev, $row->castka, $row->procento, $row->prodleva, $row->typ);
        }
        //pridej smluvni podminky do objektu SmluvniPodminkyNazevEnt
        is_null($smluvniPodminkyNazev) ? null : $smluvniPodminkyNazev->setSmluvniPodminky($smluvniPodminkyList);
        $objednavka->getSerial()->smluvniPodminkyNazev = $smluvniPodminkyNazev;

        //provizni agentura
        $stmt = self::$db->query(ObjednavkySQLBuilder::readProvizniAgenturaByObjednavkaIdSQL([$idObjednavka]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        if ($row) {
            $prodejce = new OrganizaceEnt($row->id_organizace, $row->nazev, $row->ico, $row->dic, $row->role);
            $prodejce->setAdresy([new AdresaEnt(null, $row->stat, $row->mesto, $row->psc, $row->ulice, $row->lng, $row->lat, $row->typ_kontaktu, $row->poznamka)]);
            $prodejce->provizni_koeficient = is_null($row->provizni_koeficient) ? 1 : $row->provizni_koeficient;
            $objednavka->setProdejce($prodejce);
        }

        //objednatel - osoba
        $stmt = self::$db->query(ObjednavkySQLBuilder::readObjednavajiciByObjednavkaIdSQL([$idObjednavka]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        if ($row) {
            $objednavka->objednavajici = new UserKlientEnt($row->id_klient, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);
            $objednavka->objednavajici->datum_narozeni = $row->datum_narozeni;
            $objednavka->objednavajici->adresa = new AdresaEnt(null, null, $row->mesto, $row->psc, $row->ulice, null, null, null, null);
        }

        //objednatel - organizace
        $stmt = self::$db->query(ObjednavkySQLBuilder::readObjednavajiciOrganizaceByObjednavkaIdSQL([$idObjednavka]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        if ($row) {
            $objednavka->objednavajiciOrganizace = new OrganizaceEnt($row->id_organizace, $row->nazev, $row->ico, $row->dic, $row->role);
            if ($row->mesto) //prazdne adresy = null (ne prazdny objekt adresa)
                $objednavka->objednavajiciOrganizace->setAdresy([new AdresaEnt(null, null, $row->mesto, $row->psc, $row->ulice, null, null, null, null)]);
        }

        //ucastnici
        $stmt = self::$db->query(ObjednavkySQLBuilder::readUcastniciByObjednavkaIdSQL([$idObjednavka]));
        $ucastnici = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $ucastnik = new UserKlientEnt($row->id_klient, $row->titul, $row->jmeno, $row->prijmeni, $row->email, $row->telefon);
            $ucastnik->datum_narozeni = $row->datum_narozeni;
            $ucastnik->rodne_cislo = $row->rodne_cislo;
            $ucastnik->cislo_op = $row->cislo_op;
            $ucastnik->cislo_pasu = $row->cislo_pasu;
            $ucastnik->storno = $row->storno;
            $ucastnik->adresa = new AdresaEnt(null, null, $row->mesto, $row->psc, $row->ulice, null, null, null, null);
            $ucastnici[] = $ucastnik;
        }
        $objednavka->setUcastnici($ucastnici);

        //sluzby
        $stmtSluzby = self::$db->query(ObjednavkySQLBuilder::readSluzbyByObjednavkaIdSQL([$idObjednavka, $idObjednavka, $idObjednavka]));
        $sluzby = null;
        while (@$row = $stmtSluzby->fetch(PDO::FETCH_OBJ)) {
            //TOK
            $stmtTOK = self::$db->query(ObjednavkySQLBuilder::readTOKBySluzbaZajezdIdSQL([$row->id_cena, $objednavka->getZajezd()->id]));
            $terminyObjektoveKategoie = null;
            while (@$rowTOK = $stmtTOK->fetch(PDO::FETCH_OBJ)) {
                $terminObjektoveKategorie = new TerminObjektoveKategorieEnt($rowTOK->id_termin, $rowTOK->nazev_tok, $rowTOK->cena, $rowTOK->datetime_od, $rowTOK->datetime_do, $rowTOK->kapacita_celkova, $rowTOK->kapacita_volna, $rowTOK->na_dotaz, $rowTOK->vyprodano, $rowTOK->kapacita_bez_omezeni);
                $terminObjektoveKategorie->setObjektovaKategorie(new ObjektovaKategorieEnt($rowTOK->id_objekt_kategorie, $rowTOK->nazev, $rowTOK->zakladni_kategorie, $rowTOK->hlavni_kapacita, $rowTOK->vedlejsi_kapacita, $rowTOK->prodavat_jako_celek));
                $terminyObjektoveKategoie[] = $terminObjektoveKategorie;
            }
            //sluzba
            $sluzba = new SluzbaEnt($row->id_cena, $row->nazev_ceny, $row->typ_ceny, $row->castka, $row->mena, $row->pocet, $row->use_pocet_noci, null, null, null, null, null, null, null, null);
            $sluzba->typ_provize = $row->typ_provize;
            $sluzba->vyse_provize = $row->vyse_provize;
            $sluzba->setPocetStorno($row->pocet_storno);
            $sluzba->setTerminyObektoveKategorie($terminyObjektoveKategoie);
            $sluzby[] = $sluzba;
        }
        $objednavka->setSluzby($sluzby);

        //casove slevy
        $today = date('Y-m-d');
        $stmt = self::$db->query(ObjednavkySQLBuilder::readSlevyKlientByObjednavkaId([$idObjednavka, SlevaEnt::SLEVA_PLATNOST_ANO, $today, $today, $idObjednavka, SlevaEnt::SLEVA_PLATNOST_ANO, $today, $today, $idObjednavka]));
        $casoveSlevy = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $casovaSleva = new SlevaEnt($row->id_slevy, $row->nazev_slevy, $row->castka, $row->mena, $row->sleva_staly_klient);
            $casovaSleva->setTyp($row->typ);
            $casoveSlevy[] = $casovaSleva;
        }
        $objednavka->setSlevy($casoveSlevy);

        //faktura prodejce
        $stmt = self::$db->query(ObjednavkySQLBuilder::readFakturaProdejceByObjednavkaIdSQL([$idObjednavka]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        if ($row) {
            $fakturaProdejce = new FakturaEnt($row->id_faktury, $row->cislo_faktury, 'Kè', $row->celkova_castka, $row->datum_vystaveni, $row->datum_splatnosti, $row->zaplaceno);
            $fakturaProdejce->pdfFilename = "$row->id_objednavka.pdf";
            $pdfFileName = CommonConfig::GET_FAKTURA_PROVIZE_PDF_FOLDER() . "/" . $fakturaProdejce->pdfFilename;
            $objednavka->setFakturaProdejce( $fakturaProdejce );
        }

        //faktury
        $stmt = self::$db->query(ObjednavkySQLBuilder::readFakturyByObjednavkaIdSQL([$idObjednavka]));
        $faktury = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $faktura = new FakturaEnt($row->id_faktury, $row->cislo_faktury, $row->mena, $row->celkova_castka, $row->datum_vystaveni, $row->datum_splatnosti, $row->zaplaceno);
            $faktura->prijemce_text = trim(str_replace(array("Adresa:", "&nbsp;"),array(""," "),strip_tags ($row->prijemce_text)));
            
            
            $faktury[] = $faktura;
        }
        $objednavka->setFaktury($faktury);

        //platby
        $stmt = self::$db->query(ObjednavkySQLBuilder::readPlatbyByObjednavkaIdSQL([$idObjednavka]));
        $platby = null;
        while (@$row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $platba = new PlatbaEnt($row->id_platba, $row->cislo_dokladu, $row->castka, $row->vystaveno, $row->splatit_do, $row->splaceno, $row->typ_dokladu, $row->zpusob_uhrady);
            $platby[] = $platba;
        }
        $objednavka->setPlatby($platby);

        self::$db->close();

        return $objednavka;
    }

    public static function readZajezdById($idZajezd)
    {
        self::$db->connect();

        $stmt = self::$db->query(ObjednavkySQLBuilder::readZajezdByZajezdIdSQL([$idZajezd]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $zajezd = Array($row->od, $row->do);

        self::$db->close();

        return $zajezd;
    }    
    
    public static function readObjednavkaSluzba($idObjednavka, $idSluzba)
    {
        self::$db->connect();

        $stmt = self::$db->query(ObjednavkySQLBuilder::readObjednavkaSluzbaByIdSQL([$idObjednavka, $idSluzba, $idObjednavka, $idSluzba]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $sluzba = new SluzbaEnt($row->id_cena, null, null, null, null, $row->pocet, null, null, null, null, null, null, null, null, null);
        $sluzba->setPocetStorno($row->pocet_storno);
        $sluzba = $row ? $sluzba : null;

        self::$db->close();

        return $sluzba;
    }

    public static function readObjednavkaSleva($idObjednavka, $idSleva)
    {
        //jedna se sice o slevu, ale ta je v tomto kontextu stejna jako sluzba
        return self::readObjednavkaSluzba($idObjednavka, $idSleva);
    }

    public static function saveObecneInfoTerminByObjednavkaId($idObjednavka, $terminOd, $terminDo,$pocetNoci)
    {
        self::$db->connect();
   
        $result = self::$db->query(ObjednavkySQLBuilder::saveObecneInfoTerminByObjednavkaId([$terminOd, $terminDo,$pocetNoci, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function saveTsPoznamkyByObjednavkaId($idObjednavka, $poznamky, $tajnePoznamky, $dopravaCS, $stravovaniCS, $ubytovaniCS, $pojisteniCS)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveTsPoznamkyByObjednavkaIdSQL([$poznamky, $tajnePoznamky, $dopravaCS, $stravovaniCS, $ubytovaniCS, $pojisteniCS, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function saveTsKUhradeZalohaByObjednavkaId($idObjednavka, $tsKUhradeZalohaCastka, $tsKUhradeZalohaDatum)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveTsKUhradeZalohaByObjednavkaIdSQL([$tsKUhradeZalohaCastka, $tsKUhradeZalohaDatum, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function saveTsKUhradeDoplatekByObjednavkaId($idObjednavka, $tsKUhradeDoplatekDatum)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveTsKUhradeDoplatekByObjednavkaIdSQL([$tsKUhradeDoplatekDatum, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function saveFinancePlatbaById($idPlatba, $cisloDokladu, $typDokladu, $castka, $splatnostDo, $uhrazeno, $zpusobUhrady)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveFinancePlatbaByIdSQL([$cisloDokladu, $typDokladu, $castka, $splatnostDo, $uhrazeno, $zpusobUhrady, $idPlatba]));

        self::$db->close();

        return $result;
    }

    public static function saveOsobyUcastnikById($idUcastnik, $titul, $jmeno, $prijmeni, $datumNarozeni, $rodneCislo, $email, $telefon, $cisloOp, $cisloPasu, $ulice, $mesto, $psc)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveOsobyUcastnikByIdSQL([
            $titul,
            $jmeno,
            $prijmeni,
            $datumNarozeni,
            $rodneCislo,
            $email,
            $telefon,
            $cisloOp,
            $cisloPasu,
            $ulice,
            $mesto,
            $psc,
            $idUcastnik
        ]));

        self::$db->close();

        return $result;
    }

    public static function updateUcastnikCount($idObjednavka)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::countUcastnici([
            $idObjednavka            
        ]));
        @$row = $result->fetch(PDO::FETCH_OBJ);
        if ($row) {
            self::$db->query(ObjednavkySQLBuilder::saveCountUcastnici([
                $row->pocet, $idObjednavka           
            ]));
        }
        self::$db->close();

        return $result;
    }    

    public static function saveProvizniAgentura($idObjednavka, $idOrganizace)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveProvizniAgenturaSQL([$idOrganizace, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function saveObjednavajiciOsoba($idObjednavka, $idUser)
    {
        self::$db->connect();
        self::$db->start_transaction();

        $result = self::$db->query(ObjednavkySQLBuilder::saveObjednavajiciOsobaSQL([$idUser, $idObjednavka]));
        if ($result)
            $result = self::$db->query(ObjednavkySQLBuilder::clearObjednavajiciOrganizaceSQL([$idObjednavka]));

        if (!is_null($result))
            self::$db->commit();
        else
            self::$db->rollback();
        self::$db->close();

        return $result;
    }

    public static function saveObjednavajiciOrganizace($idObjednavka, $idOrganizace)
    {
        self::$db->connect();
        self::$db->start_transaction();

        $result = self::$db->query(ObjednavkySQLBuilder::saveObjednavajiciOrganizaceSQL([$idOrganizace, $idObjednavka]));
        if ($result)
            $result = self::$db->query(ObjednavkySQLBuilder::clearObjednavajiciOsobaSQL([$idObjednavka]));

        if (!is_null($result))
            self::$db->commit();
        else
            self::$db->rollback();
        self::$db->close();

        return $result;
    }

    public static function saveObecneInfoStav($idStav, $stornoPoplatek, $idObjednavka)
    {
        self::$db->connect();
        //LADA: je treba ukladat datum storna - mozna by slo resit i jinde a lepe, ale prozatim pridavam sem
        if($idStav >= 8){
            $datum_storna = Date("Y-m-d");            
        }else{
            $datum_storna = "0000-00-00";  
        }
        $result = self::$db->query(ObjednavkySQLBuilder::saveObecneInfoStavSQL([$idStav, $stornoPoplatek, $idObjednavka],$datum_storna));

        self::$db->close();

        return $result;
    }

    public static function saveObecneInfoStornoPoplatek($idObjednavka, $poplatek)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveObecneInfoStornoPoplatekSQL([$poplatek, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function saveFinanceProvize($idObjednavka, $provizeCastka)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::saveFinanceProvizeSQL([$provizeCastka, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function createFinancePlatba($idObjednavka, $cisloDokladu, $typDokladu, $castka, $vystaveno, $splatnost, $uhrazeno, $zpusobUhrady, $idUser)
    {
        self::$db->connect();

        $sqlQuery = ObjednavkySQLBuilder::createFinancePlatbaSQL([$idObjednavka, $cisloDokladu, $typDokladu, $castka, $vystaveno, $splatnost, $uhrazeno, $zpusobUhrady, $idUser, $idUser]);
        self::$db->prepare($sqlQuery->sql);
        $result = self::$db->execute($sqlQuery->params);

        $insertedId = false;
        if ($result)
            $insertedId = self::$db->getLastInsertedId();

        self::$db->close();

        return $insertedId;
    }

    public static function createOsobyUserKlient($titul, $jmeno, $prijmeni, $datumNarozeni, $rodneCislo, $email, $telefon, $cisloOp, $cisloPasu, $ulice, $mesto, $psc, $createdByCK, $idUserCreate, $idUserEdit)
    {
        self::$db->connect();

        $sqlQuery = ObjednavkySQLBuilder::createOsobyUserKlientSQL([$titul, $jmeno, $prijmeni, $datumNarozeni, $rodneCislo, $email, $telefon, $cisloOp, $cisloPasu, $ulice, $mesto, $psc, $createdByCK, $idUserCreate, $idUserEdit]);
        self::$db->prepare($sqlQuery->sql);
        $result = self::$db->execute($sqlQuery->params);

        $insertedId = false;
        if ($result)
            $insertedId = self::$db->getLastInsertedId();

        self::$db->close();

        return $insertedId;
    }

    public static function createSluzbyManualSluzba($idObjednavka, $nazevSluzby, $castka, $pocet, $usePocetNoci)
    {
        self::$db->connect();

        $sqlQuery = ObjednavkySQLBuilder::createSluzbyManualSluzbaSQL([$idObjednavka, $pocet, $nazevSluzby, $castka, 'Kè', $usePocetNoci]);
        self::$db->prepare($sqlQuery->sql);
        $result = self::$db->execute($sqlQuery->params);

        $insertedId = false;
        if ($result)
            $insertedId = self::$db->getLastInsertedId();

        self::$db->close();

        return $insertedId;
    }

    public static function createSlevyManualSleva($idObjednavka, $nazevSlevy, $vyseSlevy, $typSlevy)
    {
        self::$db->connect();

        $sqlQuery = ObjednavkySQLBuilder::createSlevyManualSlevaSQL([$idObjednavka, $nazevSlevy, $vyseSlevy, $typSlevy]);
        self::$db->prepare($sqlQuery->sql);
        $result = self::$db->execute($sqlQuery->params);

        $insertedId = false;
        if ($result)
            $insertedId = self::$db->getLastInsertedId();

        self::$db->close();

        return $insertedId;
    }

    public static function addOsobyUserKlientToObjednavka($idObjednavka, $idCreatedUser)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::addOsobyUserKlientToObjednavkaSQL([$idObjednavka, $idCreatedUser]));
        if ($result->errorCode() == "00000")
            self::$db->query(ObjednavkySQLBuilder::plusMinusPocetOsobObjednavkaSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_PLUS, [$idObjednavka]));    //note redundantne ukladam pocet osob z duvodu kompatibility se starsimi moduly

        self::$db->close();

        return $result;
    }

    public static function addExistingUcastnik($idObjednavka, $idUser)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::addExistingUcastnikSQL([$idObjednavka, $idUser]));
        if ($result->errorCode() == "00000")
            self::$db->query(ObjednavkySQLBuilder::plusMinusPocetOsobObjednavkaSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_PLUS, [$idObjednavka]));    //note redundantne ukladam pocet osob z duvodu kompatibility se starsimi moduly

        self::$db->close();

        return $result;
    }

    public static function addSluzbaToObjednavka($idObjednavka, $idSluzba, $pocet)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::addSluzbaToObjednavkaSQL([$idObjednavka, $idSluzba, $pocet, $idSluzba, $idSluzba, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function addSluzbaSlevaToObjednavka($idObjednavka, $idSluzba, $pocet)
    {
        //jedna se sice o slevu, ale ta je v tomto kontextu stejna jako sluzba
        return self::addSluzbaToObjednavka($idObjednavka, $idSluzba, $pocet);
    }

    public static function addSlevaToObjednavka($idObjednavka, $nazevSlevy, $velikostSlevy, $menaSlevy)
    {
        //note objednavka_slevy nema zadne id, takze se vzdy vytvari nova sleva - neexistuje zpetna navaznost na to, zda byla uz slea pridana nebo ne (pouze prez porovnani nazvu/castek slev)
        self::createSlevyManualSleva($idObjednavka, $nazevSlevy, $velikostSlevy, $menaSlevy);
    }

    public static function removeOsobyUcastnikById($idUcastnik, $idObjednavka)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::removeOsobyUcastnikByIdSQL([$idUcastnik, $idObjednavka]));
        if ($result->errorCode() == "00000")
            self::$db->query(ObjednavkySQLBuilder::plusMinusPocetOsobObjednavkaSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_MINUS, [$idObjednavka]));    //note redundantne ukladam pocet osob z duvodu kompatibility se starsimi moduly

        self::$db->close();

        return $result;
    }

    public static function deleteFinancePlatbaById($idPlatba)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::deleteFinancePlatbaByIdSQL([$idPlatba]));

        self::$db->close();

        return $result;
    }

    public static function deleteSluzbaFromObjednavka($idObjednavka, $idSluzba, $typ)
    {
        self::$db->connect();

        if ($typ == SluzbaEnt::TYP_RUCNE_PRIDANA) {
            $result = self::$db->query(ObjednavkySQLBuilder::deleteManualSluzbaFromObjednavkaSQL([$idObjednavka, $idSluzba]));
        } else {
            $result = self::$db->query(ObjednavkySQLBuilder::deleteSluzbaFromObjednavkaSQL([$idObjednavka, $idSluzba]));
        }

        self::$db->close();

        return $result;
    }

    public static function deleteSluzbaSlevaFromObjednavka($idObjednavka, $idSluzba)
    {
        //jedna se sice o slevu, ale ta je v tomto kontextu stejna jako sluzba
        //null jako typ znaci, ze se nejedna o rucne pridanou sluzbu
        return self::deleteSluzbaFromObjednavka($idObjednavka, $idSluzba, null);
    }

    public static function deleteSlevaFromObjednavka($idObjednavka, $nazevSlevy, $velikostSlevy)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::deleteSlevaFromObjednavkaSQL([$idObjednavka, $nazevSlevy, $velikostSlevy]));

        self::$db->close();

        return $result;
    }

    public static function payFakturaProvizeById($idFakturaProvize)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::payFakturaProvizeByIdSQL([$idFakturaProvize]));

        self::$db->close();

        return $result;
    }

    public static function changeObjednavkaStavById($idObjednavka, $stavId)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::changeObjednavkaStavByIdSQL([$stavId, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function changeObjednavkaCelkovaCastkaById($idObjednavka, $castka)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::changeObjednavkaCelkovaCastkaByIdSQL([$castka, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function changeSerialAndZajezdById($idObjednavka, $idSerial, $idZajezd)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::changeSerialAndZajezdById([$idSerial, $idZajezd, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function minusSluzbaPocet($idObjednavka, $idSluzba, $typ)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::plusMinusSluzbaPocetSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_MINUS, $typ, [$idObjednavka, $idSluzba]));

        self::$db->close();

        return $result;
    }

    public static function minusSlevaPocet($idObjednavka, $idSluzba)
    {
        //jedna se sice o slevu, ale ta je v tomto kontextu stejna jako sluzba
        //null jako typ znaci, ze se nejedna o rucne pridanou sluzbu
        return self::minusSluzbaPocet($idObjednavka, $idSluzba, null);
    }

    public static function plusSluzbaPocet($idObjednavka, $idSluzba, $typ)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::plusMinusSluzbaPocetSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_PLUS, $typ, [$idObjednavka, $idSluzba]));

        self::$db->close();

        return $result;
    }

    public static function plusSlevaPocet($idObjednavka, $idSluzba)
    {
        //jedna se sice o slevu, ale ta je v tomto kontextu stejna jako sluzba
        //null jako typ znaci, ze se nejedna o rucne pridanou sluzbu
        return self::plusSluzbaPocet($idObjednavka, $idSluzba, null);
    }

    public static function plusSluzbaPocetStorno($idObjednavka, $idSluzba, $typ)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::plusMinusSluzbaPocetStornoSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_PLUS, $typ, [$idObjednavka, $idSluzba]));

        self::$db->close();

        return $result;
    }

    public static function plusStornoPoplatek($idObjednavka, $stornoPoplatek)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::plusMinusStornoPoplatekSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_PLUS, [$stornoPoplatek, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function minusSluzbaPocetStorno($idObjednavka, $idSluzba, $typ)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::plusMinusSluzbaPocetStornoSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_MINUS, $typ, [$idObjednavka, $idSluzba]));

        self::$db->close();

        return $result;
    }

    public static function minusStornoPoplatek($idObjednavka, $stornoPoplatek)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::plusMinusStornoPoplatekSQL(ObjednavkySQLBuilder::PLUS_MINUS_MODE_MINUS, [$stornoPoplatek, $idObjednavka]));

        self::$db->close();

        return $result;
    }

    public static function reserveKapacitySluzby($idCena, $numKapacitTotal, $idZajezd)
    {
        self::$db->connect();

	//select all capacities that are reserved for this service
	$smtp = self::$db->query(ObjednavkySQLBuilder::getCelkovaKapacitaCenySQL([$idCena, $idZajezd]));
	$row = $smtp->fetch();
	$obsazeno = intval($row["pocet"]);

        $result = self::$db->query(ObjednavkySQLBuilder::reserveKapacitySluzbySQL([$obsazeno, $idCena]));

        self::$db->close();

        return $result;
    }

    public static function reserveKapacityTOK($idTOK, $numKapacitTOK)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::reserveKapacityTOKSQL([$numKapacitTOK, $idTOK]));

        self::$db->close();

        return $result;
    }

    public static function pridejOvlivneneTOKKObjednavce($idObjednavka, $idsOvlivneneTOK)
    {
        self::$db->connect();

        $result = true;
        foreach ($idsOvlivneneTOK as $idTok) {
            if ($result)
                $result = self::$db->query(ObjednavkySQLBuilder::pridejOvlivneneTOKKObjednavceSQL([$idObjednavka, $idTok]));
            else
                break;
        }

        self::$db->close();

        return $result;
    }

    public static function refreshObjednanaSluzbaCena($idObjednavka, $idSluzba)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::refreshObjednanaSluzbaCenaSQL([$idSluzba, $idObjednavka, $idSluzba, $idObjednavka, $idObjednavka, $idSluzba]));

        self::$db->close();

        return $result;
    }

    public static function stornoObjednavkaUcastnik($idObjednavka, $idUcastnik)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::stornoObjednavkaUcastnikSQL([$idObjednavka, $idUcastnik]));

        self::$db->close();

        return $result;
    }

    public static function stornoUndoObjednavkaUcastnik($idObjednavka, $idUcastnik)
    {
        self::$db->connect();

        $result = self::$db->query(ObjednavkySQLBuilder::stornoUndoObjednavkaUcastnikSQL([$idObjednavka, $idUcastnik]));

        self::$db->close();

        return $result;
    }

    public static function kurzEur()
    {
        //open db
        self::$db->connect();

        //zajezd
        $stmt = self::$db->query(ObjednavkySQLBuilder::centralniDataByName([':nazev' => ObjednavkySQLBuilder::CENTRALNI_DATA_KURZ_EUR_TITLE]));
        @$row = $stmt->fetch(PDO::FETCH_OBJ);
        $kurz = $row->text;

        //close db
        self::$db->close();

        return $kurz;
    }

}