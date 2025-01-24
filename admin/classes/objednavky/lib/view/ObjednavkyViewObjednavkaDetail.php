<?php

class ObjednavkyViewObjednavkaDetail extends ObjednavkyView implements IObjednavkyObserver
{

    /** @var  ObjednavkyModel */
    private $model;
    /** @var  Twig_Environment */
    private $twig;

    function __construct($model, $twig)
    {
        $this->model = $model;
        $this->model->registerObjednavkyObserver($this);
        $this->twig = $twig;
    }

    public function objednavkaDetailChanged()
    {        
        echo $this->head();
        echo $this->objednavkaDetailMain();
        echo $this->foot();
    }

    private function head()
    {
        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(ObjednavkyModel::$core->show_all_allowed_moduls()), ObjednavkyModel::$zamestnanec, ObjednavkyModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        return $out;
    }

    private function objednavkaDetailMain()
    {
        echo $_REQUEST["page"];
        $objednavka = $this->model->getObjednavka();
        $provize = $objednavka->calcProvize();
        $stavString = ViewUtils::objednavkaStavNoToString($objednavka->stav);
        $stavCls = ViewUtils::objednavkaStavNoToClass($objednavka->stav);
        $mozneStavy = $objednavka->mozneStavy();
        $kUhradeZalohaCastka = $objednavka->calcPrvniZalohaCastka();
        $kUhradeZalohaDatum = CommonUtils::czechDate($objednavka->calcPrvniZalohaDatum());
        $kUhradeDoplatekDatum = CommonUtils::czechDate($objednavka->calcDoplatekDatum());
        $kUhradeCelkemDatum = CommonUtils::czechDate($objednavka->calcCelkemDatum());
        $aktualniStorno = $objednavka->getSerial()->getSmluvniPodminkyNazev()->getSmluvniPodminkyHolder()->getAktualniStorno($objednavka->termin_od);
        $toggleSekce = $this->model->getToggleState()->getState();
        $resUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/admin/classes/objednavky/res';
        $globalResUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/global/res';
        $out = "";

        $out .= $this->twig->render('objednavka-detail.html.twig', [
            'objednavka' => $objednavka,
            'stavString' => $stavString,
            'stavCls' => $stavCls,
            'resURL' => $resUrl,
            'globalResURL' => $globalResUrl,
            'mozneStavy' => $mozneStavy,
            'toggleSekce' => $toggleSekce,
            'aktualniStorno' => $aktualniStorno,
            'provize' => $provize,
            'kUhradeZalohaCatka' => $kUhradeZalohaCastka,
            'kUhradeZalohaDatum' => $kUhradeZalohaDatum,
            'kUhradeDoplatekDatum' => $kUhradeDoplatekDatum,
            'kUhradeCelkemDatum' => $kUhradeCelkemDatum,
            'FAKTURA_ZAPLACENO_ANO' => FakturaEnt::FAKTURA_ZAPLACENO_ANO,
            'CZECH_DATE_FORMAT' => ViewConfig::CZECH_DATE_FORMAT,
            'FAKTURA_PROVIZE_URL' => CommonConfig::FAKTURA_PROVIZE_PDF_FOLDER_URL,
            'SLANTOUR_PHOTO_NAHLED_URL' => CommonConfig::SLANTOUR_PHOTO_NAHLED_URL,
            'CESKE_SKLONOVANI_1' => ViewUtils::CESKE_SKLONOVANI_1,
            'CESKE_SKLONOVANI_2_4' => ViewUtils::CESKE_SKLONOVANI_2_4,
            'CESKE_SKLONOVANI_5_VIC' => ViewUtils::CESKE_SKLONOVANI_5_VIC,
            'SLUZBA_TYP_RUCNE_PRIDANA' => SluzbaEnt::TYP_RUCNE_PRIDANA,
            'MENA_KC' => ViewConfig::MENA_KC,
            'MENA_KC_OS' => ViewConfig::MENA_KC_OS,
            'MENA_PERCENT' => ViewConfig::MENA_PERCENT,
            'STAV_PRECHOD_JE_MOZNY' => ObjednavkaEnt::STAV_PRECHOD_JE_MOZNY,
            'STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY' => ObjednavkaEnt::STAV_PRECHOD_JE_MOZNY_NA_JEDEN_ZE_SKUPINY,
            'SECTION_OBJEDNAVKA_EDIT_BLOCK_UCASTNICI' => ToggleSectionsSessionEnt::SECTION_OBJEDNAVKA_EDIT_BLOCK_UCASTNICI,
            'SECTION_OBJEDNAVKA_EDIT_BLOCK_NEOBJEDNANE_SLUZBY' => ToggleSectionsSessionEnt::SECTION_OBJEDNAVKA_EDIT_BLOCK_NEOBJEDNANE_SLUZBY,
            'SECTION_OBJEDNAVKA_EDIT_BLOCK_NEOBJEDNANE_SLEVY' => ToggleSectionsSessionEnt::SECTION_OBJEDNAVKA_EDIT_BLOCK_NEOBJEDNANE_SLEVY,
            'SECTION_OBJEDNAVKA_EDIT_BLOCK_FAKTURY_PLATBY' => ToggleSectionsSessionEnt::SECTION_OBJEDNAVKA_EDIT_BLOCK_FAKTURY_PLATBY,
            'SECTION_OBJEDNAVKA_EDIT_BLOCK_POZNAMKY_TS' => ToggleSectionsSessionEnt::SECTION_OBJEDNAVKA_EDIT_BLOCK_POZNAMKY_TS,
        ]);

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(ObjednavkyModel::$core->show_current_modul()["napoveda"]);        
        $out .= self::htmlFoot();

        return $out;
    }

    public function objednavkaAjaxObecneInfoStavChanged()
    {
        
        
        $objednavka = $this->model->getObjednavka();
        $out = json_encode([
            'stav-storno-poplatek' => $objednavka->calcStornoPoplatek(),
            'stav-storno-poplatek-ck' => $objednavka->calcStornoPoplatekCK()
        ]);

        echo $out;
    }

    public function objednavkaAjaxObecneInfoStavOdbavenoNezaplacenoChanged()
    {
        $out = json_encode([
            'status' => 'warning',
            'warning-msg' => iconv('windows-1250', 'utf-8', 'Objedn�vka ozna�ena jako odbavena, ale nen� zcela uhrazena. P�esto pokra�ovat?')
        ]);
        echo $out;
    }

    public function objednavkaAjaxTsPoznamkyChanged()
    {
        $poznamky = $this->model->getObjednavka()->poznamky;
        $out = json_encode(['ts-poznamky' => iconv('windows-1250', 'utf-8', $poznamky)]);

        echo $out;
    }

    public function objednavkaAjaxTsTajnePoznamkyChanged()
    {
        $tajnePoznamky = $this->model->getObjednavka()->poznamky_tajne;
        $out = json_encode(['ts-tajne-poznamky' => iconv('windows-1250', 'utf-8', $tajnePoznamky)]);

        echo $out;
    }

    public function objednavkaAjaxTsDopravaCSChanged()
    {
        $dopravaCS = $this->model->getObjednavka()->doprava;
        $out = json_encode(['ts-doprava-cs' => iconv('windows-1250', 'utf-8', $dopravaCS)]);

        echo $out;
    }

    public function objednavkaAjaxTsStravovaniCSChanged()
    {
        $stravovaniCS = $this->model->getObjednavka()->stravovani;
        $out = json_encode(['ts-stravovani-cs' => iconv('windows-1250', 'utf-8', $stravovaniCS)]);

        echo $out;
    }

    public function objednavkaAjaxTsUbytovaniCSChanged()
    {
        $ubytovaniCS = $this->model->getObjednavka()->ubytovani;
        $out = json_encode(['ts-ubytovani-cs' => iconv('windows-1250', 'utf-8', $ubytovaniCS)]);

        echo $out;
    }

    public function objednavkaAjaxTsPojisteniCSChanged()
    {
        $ubytovaniCS = $this->model->getObjednavka()->pojisteni;
        $out = json_encode(['ts-ubytovani-cs' => iconv('windows-1250', 'utf-8', $ubytovaniCS)]);

        echo $out;
    }

    public function objednavkaAjaxFinancePlatbaChanged()
    {
        $platba = $this->model->getObjednavka()->getPlatbyHolder()->getPlatba($this->model->getAjaxLoadingPlatbaId());
        $out = json_encode([
            'platba-cislo-dokladu' => $platba->cislo_dokladu,
            'platba-typ-dokladu' => iconv('windows-1250', 'utf-8', $platba->typ_dokladu),
            'platba-castka' => $platba->castka,
            'platba-splatnost-do' => CommonUtils::czechDate($platba->splatit_do),
            'platba-uhrazeno' => CommonUtils::czechDate($platba->splaceno),
            'platba-zpusob-uhrady' => iconv('windows-1250', 'utf-8', $platba->zpusob_uhrady)
        ]);

        echo $out;
    }

    public function objednavkaAjaxOsobyUcastnikChanged()
    {
        
        $user = $this->model->getObjednavka()->getUcastnikHolder()->getKlient($this->model->getAjaxLoadingUcastnikId());
        $out = json_encode([
            'user-titul' => iconv('windows-1250', 'utf-8', $user->titul),
            'user-jmeno' => iconv('windows-1250', 'utf-8', $user->jmeno),
            'user-prijmeni' => iconv('windows-1250', 'utf-8', $user->prijmeni),
            'user-datum-narozeni' => CommonUtils::czechDate($user->datum_narozeni),
            'user-rodne-cislo' => $user->rodne_cislo,
            'user-email' => $user->email,
            'user-telefon' => $user->telefon,
            'user-cislo-op' => $user->cislo_op,
            'user-cislo-pasu' => $user->cislo_pasu,
            'user-ulice' => iconv('windows-1250', 'utf-8', $user->adresa->ulice),
            'user-mesto' => iconv('windows-1250', 'utf-8', $user->adresa->mesto),
            'user-psc' => $user->adresa->psc,
        ]);

        echo $out;
    }

    public function objednavkaAjaxOsobyUcastnikSaveValidationErrChanged()
    {
        $validatorResponse = $this->model->getUcastnikSaveValidatorResponse();
        $errorList = $validatorResponse->getInvalidStates();
        $errorListJsonReady = [];
        foreach ($errorList as $error) {
            switch($error) {
                case UserKlientValidator::UCASTNIK_ADD_PRIJMENI_EMPTY:
                    $errorListJsonReady[] = ['elementId' => 'user-prijmeni', 'message' => iconv('windows-1250', 'utf-8', 'P��jmen� nesm� b�t pr�zdn�')];
                    break;
                case UserKlientValidator::UCASTNIK_ADD_JMENO_EMPTY:
                    $errorListJsonReady[] = ['elementId' => 'user-jmeno', 'message' => iconv('windows-1250', 'utf-8', 'Jm�no nesm� b�t pr�zdn�')];
                    break;
            }
        }

        $out = json_encode([
            'status' => 'validation-error',
            'header' => iconv('windows-1250', 'utf-8', 'Chyba p�i editaci ��astn�ka'),
            'error-list' => (array) $errorListJsonReady,
        ]);
        echo $out;
    }

    public function objednavkaAjaxFinancePlatbaSaveValidationErrChanged()
    {
        $validatorResponse = $this->model->getUserPlatbaSaveValidatorResponse();
        $errorList = $validatorResponse->getInvalidStates();
        $errorListJsonReady = [];
        foreach ($errorList as $error) {
            switch($error) {
                case PlatbaValidator::UCASTNIK_ADD_TYP_DOKLADU_EMPTY:
                    $errorListJsonReady[] = ['elementId' => 'platba-typ-dokladu', 'message' => iconv('windows-1250', 'utf-8', 'Typ platby mus� b�t bypln�n')];
                    break;
                case PlatbaValidator::UCASTNIK_ADD_CASTKA_EMPTY:
                    $errorListJsonReady[] = ['elementId' => 'platba-castka', 'message' => iconv('windows-1250', 'utf-8', '��stka mus� b�t kladn� ��slo')];
                    break;
            }
        }

        $out = json_encode([
            'status' => 'validation-error',
            'header' => iconv('windows-1250', 'utf-8', 'Chyba p�i editaci platby'),
            'error-list' => (array) $errorListJsonReady,
        ]);
        echo $out;
    }
}