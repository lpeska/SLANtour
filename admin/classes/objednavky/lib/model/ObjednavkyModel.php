<?php

class ObjednavkyModel {
//done 1 pri pridavani storno sluzeb musim zobrazit potvrzeni storno castky - skoro hotovo - vypocitat castku a ulozit
//done 2 pri stornovani objednavky odeberu vsechny sluzby
//todo 3 disable tlacitka, ktera nemaji byt pouzitelne v danem stavu (storno atd)
//todo 4 zaloha a doplatek k_uhrade editace TS - prepocitavat pri zmenach ceny
//todo 5 proces objednavky - objednatel, zapsat ho dolu a v poslednim kroku taky
//todo 6 proces objednavky - casove slevy
//todo 7 nova prace v mailu
//done 8 editace objednavky pridat novy text na ts
//todo predelat REQUEST uvnitr funkci na parametry metod aby nebyly takhle hloupe zavisle na globalni promenne
    const SESSION_NAME_OBJEDNAVKA_EDIT_SECTION_TOGGLE = 'objednavka-edit-section-toggle';
    const PLATBA_VYDAJOVY_DOKLAD = "vydajovy";
    static $zamestnanec;
    static $core;

    /** @var  IObjednavkyObserver[] */
    private $observers;

    /** @var  PagingFilter */
    private $pagingFilter;
    /** @var  ObjednavkaEnt */
    private $objednavka;
    /** @var  int id platby, kterou si chce nacist klient */
    private $ajaxLoadingPlatbaId;
    /** @var  int id ucastnika, ktereho si chce nacist klient */
    private $ajaxLoadingUcastnikId;

    /** @var  ValidatorResponse */
    private $sluzbaCreateValidatorResponse;
    /** @var  ValidatorResponse */
    private $ucastnikSaveValidatorResponse;
    /** @var  ValidatorResponse */
    private $userPlatbaSaveValidatorResponse;

    /** @var  ToggleSectionsSessionEnt */
    private $toggleState;

    //region GETTERS/SETTERS *************************************************************

    /**
     * @return PagingFilter
     */
    public function getPagingFilter()
    {
        return $this->pagingFilter;
    }

    /**
     * @return ObjednavkaEnt
     */
    public function getObjednavka()
    {
        return $this->objednavka;
    }

    /**
     * @return int
     */
    public function getAjaxLoadingPlatbaId()
    {
        return $this->ajaxLoadingPlatbaId;
    }

    public function getAjaxLoadingUcastnikId()
    {
        return $this->ajaxLoadingUcastnikId;
    }

    /**
     * @return ValidatorResponse
     */
    public function getSluzbaCreateValidatorResponse()
    {
        return $this->sluzbaCreateValidatorResponse;
    }

    /**
     * @return ValidatorResponse
     */
    public function getUcastnikSaveValidatorResponse()
    {
        return $this->ucastnikSaveValidatorResponse;
    }

    /**
     * @return ValidatorResponse
     */
    public function getUserPlatbaSaveValidatorResponse()
    {
        return $this->userPlatbaSaveValidatorResponse;
    }

    /**
     * @return ToggleSectionsSessionEnt
     */
    public function getToggleState()
    {
        return $this->toggleState;
    }

    //endregion

    public function registerObjednavkyObserver($observer)
    {
        $this->observers[] = $observer;
    }

    public function objednavkaDetail()
    {
        $this->loadObjednavkaDetail();
        $this->loadObjednavkaToggleSectionState();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_DETAIL);
    }

    //region PUBLIC TOGGLE STAV SEKCE ****************************************************

    public function ajaxSectionToggleSave()
    {
        $this->loadObjednavkaToggleSectionState();

        $addStateId = $_POST['toggle-state-add'];
        $removeStateId = $_POST['toggle-state-remove'];

        $this->toggleState->push($addStateId);
        $this->toggleState->remove($removeStateId);
        $this->toggleState->pushToSession(self::SESSION_NAME_OBJEDNAVKA_EDIT_SECTION_TOGGLE);
    }

    //endregion

    //region PUBLIC OBECNE INFO **********************************************************

    public function ajaxObecneInfoSerialSave()
    {
        $this->loadObjednavkaDetail();

        //save new serial and zajezd - note has to be done first, some SQL queries are bound to serial or zajezd id
        ObjednavkyDAO::changeSerialAndZajezdById($_REQUEST["idObjednavka"], $_REQUEST['edit-serial-id'], $_REQUEST['edit-zajezd']);

        $zajezdTerminArray = ObjednavkyDAO::readZajezdById($_REQUEST['edit-zajezd']);
        //save termin
        $this->saveTermin($_REQUEST["serial-edit-termin"], $this->objednavka->id,$zajezdTerminArray);

        //unorder old sluzby
        $objednaneSluzby = $this->objednavka->getSluzbaHolder()->getObjednaneSluzby();
        if (!is_null($objednaneSluzby)) {
            foreach ($objednaneSluzby as $sluzba) {
                $_REQUEST['typ'] = is_null($sluzba->typ) ? SluzbaEnt::TYP_RUCNE_PRIDANA : $sluzba->typ;
                $_REQUEST["idSluzba"] = $sluzba->id_cena;
                $pocet = $sluzba->pocet;
                for ($i = 0; $i < $pocet; $i++) {
                    $this->sluzbaMinus($sluzba);
                    $sluzba->pocet--;   //sluzbaMinus() is designed to work with updated SluzbaEnt object, which is skiped in here - todo load sluzba from database each time the sluzbaMinus() is run
                }
            }
        }

        //add storno poplatek if there is any but dont change objednavka stav
        if ($_REQUEST['edit-serial-storno-poplatek'] != 0 && trim($_REQUEST['edit-serial-storno-poplatek']) != '') {
            ObjednavkyDAO::saveObecneInfoStornoPoplatek($_REQUEST["idObjednavka"], $_REQUEST['edit-serial-storno-poplatek']);
        }

        //order new sluzby
        foreach ($_REQUEST['sluzby'] as $sluzba) {
            if ($sluzba['pocet'] != 0) {
                $_REQUEST['idSluzba'] = $sluzba['id'];
                $_REQUEST['pocet'] = $sluzba['pocet'];
                $this->sluzbaAdd();
            }
        }
        $this->autoUpdateCelkovaCastka();
    }

    public function ajaxObecneInfoStavLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_OBECNE_INFO_STAV);
    }

    public function ajaxObecneInfoStavSave()
    {
        $this->loadObjednavkaDetail();
        $cilovyStavObjednavky = $_REQUEST['stav-stav-objednavky'];

        //pokud je prechod povolen, uloz novy stav
        if ($this->objednavka->isAllowedPrechod($cilovyStavObjednavky)) {
            //ve stavu odbaveno se ve chvili, kdy neni zaplacena cela castka objevi upozorneni o tom, ze objednavka je sice odbavena, ale neni zaplaceno vse co by v tomto stavu melo byt.
            if ($cilovyStavObjednavky == ObjednavkaEnt::STAV_ODBAVENO &&
                $this->objednavka->calcUhrazeno() < $this->objednavka->calcFinalniCenaObjednavky() &&
                $_REQUEST['confirmed'] != 1
            ) {
                $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_OBECNE_INFO_STAV_ODBAVENO_NEZAPLACENO);
                return;
            }

            //pokud jsem v jinem stavu nebo je zaplacena cela castka nebo uzivatel volbu potvrdil ($_REQUEST['confirmed'] = 1), uloz stav
            $stornoPoplatek = isset($_REQUEST['stav-storno-poplatek']) ? $_REQUEST['stav-storno-poplatek'] :
                (isset($_REQUEST['stav-storno-poplatek-ck']) ? $_REQUEST['stav-storno-poplatek-ck'] : 0);
            ObjednavkyDAO::saveObecneInfoStav(
                $cilovyStavObjednavky,
                $stornoPoplatek,
                $_REQUEST['idObjednavka']
            );

            //rezervuj nebo vrat kapacity, podle toho o jaky prechod stavu se jedna
            $requireCapacityChange = $this->objednavka->requireCapacityChange($cilovyStavObjednavky);
            if ($requireCapacityChange == ObjednavkaEnt::STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_REZERVOVAT) {
                //navysit kapacity vsech sluzeb
                foreach ($this->objednavka->getSluzbaHolder()->getSluzbyAll() as $sluzba) {
                    $this->reserveKapacity($sluzba->id_cena, $sluzba->pocet, false);
                }
            } else if ($requireCapacityChange == ObjednavkaEnt::STAV_PRECHOD_REQUIRE_CAPACITY_CHANGE_UVOLNIT) {
                //snizit kapacity vsech sluzeb
                foreach ($this->objednavka->getSluzbaHolder()->getSluzbyAll() as $sluzba) {
                    $this->reserveKapacity($sluzba->id_cena, $sluzba->pocet * (-1), false);
                }
            }
        }
    }

    public function ajaxObecneInfoTerminSave()
    {
        $this->saveTermin($_REQUEST["obecne-info-termin"], $_REQUEST["idObjednavka"]);
        $this->autoUpdateCelkovaCastka();
    }

    //endregion

    //region PUBLIC OSOBY / ORGANIZACE ***************************************************

    public function ajaxProvizniAgenturaSave()
    {
        ObjednavkyDAO::saveProvizniAgentura(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idOrganizace"]
        );
    }

    public function ajaxObjednavajiciOsobaSave()
    {
        ObjednavkyDAO::saveObjednavajiciOsoba(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idUser"]
        );
    }

    public function ajaxObjednavajiciOrganizaceSave()
    {
        ObjednavkyDAO::saveObjednavajiciOrganizace(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idOrganizace"]
        );
    }

    public function ucastnikAdd()
    {
        $userKlientAddValidator = UserKlientValidator::isUcastnikValid($_REQUEST["user-jmeno"], $_REQUEST["user-prijmeni"]);
        if ($userKlientAddValidator->isValid()) {
            $loggedUserId = self::$zamestnanec->get_id();
            $createdUserId = ObjednavkyDAO::createOsobyUserKlient(
                $_REQUEST["user-titul"],
                $_REQUEST["user-jmeno"],
                $_REQUEST["user-prijmeni"],
                CommonUtils::engDate($_REQUEST["user-datum-narozeni"]),
                $_REQUEST["user-rodne-cislo"],
                $_REQUEST["user-email"],
                $_REQUEST["user-telefon"],
                $_REQUEST["user-cislo-op"],
                $_REQUEST["user-cislo-pasu"],
                $_REQUEST["user-mesto"],
                $_REQUEST["user-ulice"],
                $_REQUEST["user-psc"],
                UserKlientEnt::USER_KLIENT_CREATED_BY_CK_YES,
                $loggedUserId,
                $loggedUserId
            );

            if ($createdUserId)
                ObjednavkyDAO::addOsobyUserKlientToObjednavka($_REQUEST["idObjednavka"], $createdUserId);
                ObjednavkyDAO::updateUcastnikCount($_REQUEST["idObjednavka"]); 
        }

        CommonUtils::redirect('objednavky.php?idObjednavka=' . $_REQUEST['idObjednavka']);
    }

    public function ajaxUcastnikLoad()
    {
        $this->loadObjednavkaDetail();
        $this->ajaxLoadingUcastnikId = $_REQUEST["idUcastnik"];

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_OSOBY_UCASTNIK);
    }

    public function ajaxUcastnikSave()
    {
        $this->ucastnikSaveValidatorResponse = UserKlientValidator::isUcastnikValid($_REQUEST["user-jmeno"], $_REQUEST["user-prijmeni"]);
        if ($this->ucastnikSaveValidatorResponse->isValid()) {
            ObjednavkyDAO::saveOsobyUcastnikById(
                $_REQUEST["idUcastnik"],
                iconv('utf-8', 'windows-1250', $_REQUEST["user-titul"]),
                iconv('utf-8', 'windows-1250', $_REQUEST["user-jmeno"]),
                iconv('utf-8', 'windows-1250', $_REQUEST["user-prijmeni"]),
                CommonUtils::czechToEnglishDate($_REQUEST["user-datum-narozeni"]),
                $_REQUEST["user-rodne-cislo"],
                iconv('utf-8', 'windows-1250', $_REQUEST["user-email"]),
                $_REQUEST["user-telefon"],
                $_REQUEST["user-cislo-op"],
                $_REQUEST["user-cislo-pasu"],
                iconv('utf-8', 'windows-1250', $_REQUEST["user-ulice"]),
                iconv('utf-8', 'windows-1250', $_REQUEST["user-mesto"]),
                $_REQUEST["user-psc"]
            );
           ObjednavkyDAO::updateUcastnikCount($_REQUEST["idObjednavka"]); 
        } else {
            $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_OSOBY_UCASTNIK_SAVE_VALIDATION_ERROR);
        }
    }

    public function ajaxUcastnikRemove()
    {
        ObjednavkyDAO::removeOsobyUcastnikById($_REQUEST["idUcastnik"], $_REQUEST["idObjednavka"]);
        ObjednavkyDAO::updateUcastnikCount($_REQUEST["idObjednavka"]); 
    }

    public function ajaxUcastnikStornoUndo()
    {
        $stornoSluzbyPocet = CommonUtils::filterArrayUseNumSuffix($_REQUEST, 'sluzby-storno-undo-pocet-');
        if (!is_null($stornoSluzbyPocet)) {
            foreach ($stornoSluzbyPocet as $id => $pocet) {
                if (ValuesValidator::intGreaterThenZero($pocet)) {
                    //note tohle je peknej hnus - metoda by mela mit parametry - vlastne asi kazda metoda v modelu
                    $_REQUEST['idSluzba'] = $id;
                    $_REQUEST['typ'] = $_REQUEST['sluzby-storno-undo-typ-' . $id];
                    $_REQUEST['stornoPoplatek'] = $_REQUEST['sluzby-storno-undo-poplatek-' . $id];
                    for ($i = 0; $i < $pocet; $i++)
                        $this->ajaxSluzbyStornoMinus();
                }
            }
        }
        

        //zmen status ucastnika i kdyz sluzby nejsou vyplneny
        ObjednavkyDAO::stornoUndoObjednavkaUcastnik($_REQUEST['idObjednavka'], $_REQUEST['idUcastnik']);
        ObjednavkyDAO::updateUcastnikCount($_REQUEST["idObjednavka"]); 
    }

    public function ajaxUcastnikStornoSave()
    {
        $stornoSluzbyPocet = CommonUtils::filterArrayUseNumSuffix($_REQUEST, 'sluzby-storno-pocet-');
        if (!is_null($stornoSluzbyPocet)) {
            foreach ($stornoSluzbyPocet as $id => $pocet) {
                if (ValuesValidator::intGreaterThenZero($pocet)) {
                    //note tohle je peknej hnus - metoda by mela mit parametry - vlastne asi kazda metoda v modelu
                    $_REQUEST['idSluzba'] = $id;
                    $_REQUEST['typ'] = $_REQUEST['sluzby-storno-typ-' . $id];
                    $_REQUEST['stornoPoplatek'] = $_REQUEST['sluzby-storno-poplatek-' . $id];
                    for ($i = 0; $i < $pocet; $i++)
                        $this->ajaxSluzbyStornoPlus();
                }
            }
        }

        //zmen status ucastnika i kdyz sluzby nejsou vyplneny
        ObjednavkyDAO::stornoObjednavkaUcastnik($_REQUEST['idObjednavka'], $_REQUEST['idUcastnik']);
        ObjednavkyDAO::updateUcastnikCount($_REQUEST["idObjednavka"]); 
    }

    public function ajaxUcastnikAddExisting()
    {
        ObjednavkyDAO::addExistingUcastnik(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idUser"]
        );
        ObjednavkyDAO::updateUcastnikCount($_REQUEST["idObjednavka"]); 
    }

    //endregion

    //region PUBLIC SLUZBY ***************************************************************

    public function ajaxSluzbyMinus()
    {
        $this->loadObjednavkaDetail();
        /** @var SluzbaEnt $sluzba */
        $sluzba = ObjednavkyDAO::readObjednavkaSluzba($_REQUEST["idObjednavka"], $_REQUEST["idSluzba"]);

        $this->sluzbaMinus($sluzba);
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
    }

    public function ajaxSluzbyPlus()
    {
        //lada: tady je treba pridat load objednavky kvuli dal volane funkci reserveKapacity
        $this->loadObjednavkaDetail();
        /** @var SluzbaEnt $sluzba */
        $sluzba = ObjednavkyDAO::readObjednavkaSluzba($_REQUEST["idObjednavka"], $_REQUEST["idSluzba"]);
        
        $this->sluzbaPlus();
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
    }

    public function ajaxSluzbyStornoMinus()
    {
        /** @var SluzbaEnt $sluzba */
        $sluzba = ObjednavkyDAO::readObjednavkaSluzba($_REQUEST["idObjednavka"], $_REQUEST["idSluzba"]);
        $_REQUEST["stornoPoplatek"] = $_REQUEST["sluzby-storno-undo-poplatek-" . $_REQUEST["idSluzba"]];    //note tohle je peknej hnus - metoda by mela mit parametry - vlastne asi kazda metoda v modelu
        $sluzbaOdebratPocet = $_REQUEST["sluzby-storno-undo-pocet-" . $_REQUEST["idSluzba"]];

        if ($sluzba->getPocetStorno() > 0  && $sluzbaOdebratPocet > 0) {
            //odeber pocet sluzeb
            for($i = 0; $i < $sluzbaOdebratPocet; $i++) {
                ObjednavkyDAO::minusSluzbaPocetStorno(
                    $_REQUEST["idObjednavka"],
                    $_REQUEST["idSluzba"],
                    $_REQUEST["typ"]
                );
                $this->ajaxSluzbyPlus();
            }

            //storno poplatek je caska za vsechny odebrane sluzby dohromady
            ObjednavkyDAO::minusStornoPoplatek(
                $_REQUEST["idObjednavka"],
                $_REQUEST["stornoPoplatek"]
            );
        }
        $this->autoUpdateCelkovaCastka();
    }

    public function ajaxSluzbyStornoPlus()
    {
        /** @var SluzbaEnt $sluzba */
        $sluzba = ObjednavkyDAO::readObjednavkaSluzba($_REQUEST["idObjednavka"], $_REQUEST["idSluzba"]);
        $_REQUEST["stornoPoplatek"] = $_REQUEST["sluzby-storno-poplatek-" . $_REQUEST["idSluzba"]];    //note tohle je peknej hnus - metoda by mela mit parametry - vlastne asi kazda metoda v modelu
        $sluzbaPridatPocet = $_REQUEST["sluzby-storno-pocet-" . $_REQUEST["idSluzba"]];

        if ($sluzba->pocet > 0 && $sluzbaPridatPocet > 0) {
            //pridej pocet sluzeb
            for($i = 0; $i < $sluzbaPridatPocet; $i++) {
                ObjednavkyDAO::plusSluzbaPocetStorno(
                    $_REQUEST["idObjednavka"],
                    $_REQUEST["idSluzba"],
                    $_REQUEST["typ"]
                );
                $this->sluzbaMinus($sluzba, false);
            }

            //storno poplatek je caska za vsechny odebrane sluzby dohromady
            ObjednavkyDAO::plusStornoPoplatek(
                $_REQUEST["idObjednavka"],
                $_REQUEST["stornoPoplatek"]
            );
        }
        $this->autoUpdateCelkovaCastka();
    }

    public function ajaxSluzbyAdd()
    {
        if (ValuesValidator::intGreaterThenZero($_REQUEST["pocet"])) {
            $this->loadObjednavkaDetail();

            $this->sluzbaAdd();
            $this->autoChangeObjednavkaStav();
            $this->autoUpdateCelkovaCastka();
        }
    }

    /**
     * Refreshne castku (cenu) objednane sluzby dane objednavky dle ceny uvedene u ceny zajezdu
     */
    public function ajaxSluzbyPriceRefresh()
    {
        ObjednavkyDAO::refreshObjednanaSluzbaCena(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idSluzba"]
        );
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
        CommonUtils::redirect('objednavky.php?idObjednavka=' . $_REQUEST['idObjednavka']);
    }

    public function sluzbyCreate()
    {
        $this->sluzbaCreateValidatorResponse = SluzbaValidator::validateSluzbaAdd($_REQUEST["nazev-sluzby"], $_REQUEST["castka"], $_REQUEST["pocet"]);
        if ($this->sluzbaCreateValidatorResponse->isValid()) {
            ObjednavkyDAO::createSluzbyManualSluzba(
                $_REQUEST["idObjednavka"],
                $_REQUEST["nazev-sluzby"],
                $_REQUEST["castka"],
                $_REQUEST["pocet"],
                $_REQUEST["use-pocet-noci"]
            );
            $this->autoChangeObjednavkaStav();
            $this->autoUpdateCelkovaCastka();
            CommonUtils::redirect('objednavky.php?idObjednavka=' . $_REQUEST['idObjednavka']);
        }
    }

    //endregion

    //region PUBLIC SLEVY ****************************************************************

    public function ajaxSlevyMinus()
    {
        $this->loadObjednavkaDetail();

        /** @var SluzbaEnt $sluzba */
        $sleva = ObjednavkyDAO::readObjednavkaSleva($_REQUEST["idObjednavka"], $_REQUEST["idSluzba"]);

        if ($sleva->pocet <= 1) {
            ObjednavkyDAO::deleteSluzbaSlevaFromObjednavka(
                $_REQUEST["idObjednavka"],
                $_REQUEST["idSleva"]
            );
        } else {
            ObjednavkyDAO::minusSlevaPocet(
                $_REQUEST["idObjednavka"],
                $_REQUEST["idSleva"]
            );
        }
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
        //vratit kapacity
        $this->reserveKapacity($_REQUEST["idSleva"], -1);
    }

    public function ajaxSlevyPlus()
    {
        $this->loadObjednavkaDetail();

        ObjednavkyDAO::plusSlevaPocet(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idSleva"]
        );
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
        //rezervovat kapacity
        $this->reserveKapacity($_REQUEST["idSleva"], 1);
    }

    public function ajaxSlevyRemove()
    {
        ObjednavkyDAO::deleteSlevaFromObjednavka(
            $_REQUEST["idObjednavka"],
            iconv('utf-8', 'windows-1250', $_REQUEST["nazev-slevy"]),
            $_REQUEST["velikost-slevy"]
        );
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
    }

    public function ajaxSlevySluzbaAdd()
    {
        if (ValuesValidator::intGreaterThenZero($_REQUEST["pocet"])) {
            $this->loadObjednavkaDetail();

            ObjednavkyDAO::addSluzbaSlevaToObjednavka(
                $_REQUEST["idObjednavka"],
                $_REQUEST["idSleva"],
                $_REQUEST["pocet"]
            );

            //rezervovat kapacity - zde se nemuze jednat o rucne pridanou sluzbu
            $this->reserveKapacity($_REQUEST["idSleva"], $_REQUEST["pocet"]);
            $this->autoChangeObjednavkaStav();
            $this->autoUpdateCelkovaCastka();
        }
    }

    public function ajaxSlevyAdd()
    {
        ObjednavkyDAO::addSlevaToObjednavka(
            $_REQUEST["idObjednavka"],
            iconv('utf-8', 'windows-1250', $_REQUEST["nazev-slevy"]),
            $_REQUEST["velikost-slevy"],
            iconv('utf-8', 'windows-1250', $_REQUEST["mena-slevy"])
        );
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
    }

    public function slevyCreate()
    {
        $slevaAddValidator = SlevaValidator::isAddSlevaValid($_REQUEST["nazev-slevy"], $_REQUEST["vyse-slevy"], $_REQUEST["typ-slevy"]);
        if ($slevaAddValidator->isValid()) {
            ObjednavkyDAO::createSlevyManualSleva(
                $_REQUEST["idObjednavka"],
                $_REQUEST["nazev-slevy"],
                $_REQUEST["vyse-slevy"],
                $_REQUEST["typ-slevy"]
            );
        }
        $this->autoChangeObjednavkaStav();
        $this->autoUpdateCelkovaCastka();
        CommonUtils::redirect('objednavky.php?idObjednavka=' . $_REQUEST['idObjednavka']);
    }

    //endregion

    //region PUBLIC FINANCE **************************************************************

    public function ajaxFakturaProvizePay()
    {
        ObjednavkyDAO::payFakturaProvizeById($_REQUEST["idFakturaProvize"]);
    }

    public function ajaxPlatbaLoad()
    {
        $this->loadObjednavkaDetail();
        $this->ajaxLoadingPlatbaId = $_REQUEST["idPlatba"];

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_FINANCE_PLATBA);
    }

    public function ajaxPlatbaSave()
    {
        $this->userPlatbaSaveValidatorResponse = PlatbaValidator::isAddPlatbaValid($_REQUEST["platba-typ-dokladu"], $_REQUEST["platba-castka"]);
        if ($this->userPlatbaSaveValidatorResponse->isValid()) {
            //lada: vsechny vydajove doklady se ukladaji se zapornou castkou
            if ($_REQUEST["platba-typ-dokladu"] == PlatbaEnt::PLATBA_VYDAJOVY_DOKLAD && $_REQUEST["platba-castka"] > 0) {
                $_REQUEST["platba-castka"] = -$_REQUEST["platba-castka"];
            }
            ObjednavkyDAO::saveFinancePlatbaById(
                $_REQUEST["idPlatba"],
                $_REQUEST["platba-cislo-dokladu"],
                $_REQUEST["platba-typ-dokladu"],
                $_REQUEST["platba-castka"],
                CommonUtils::czechToEnglishDate($_REQUEST["platba-splatnost-do"]),
                CommonUtils::czechToEnglishDate($_REQUEST["platba-uhrazeno"]),
                iconv('utf-8', 'windows-1250', $_REQUEST["platba-zpusob-uhrady"])
            );
            $this->autoChangeObjednavkaStav();
        } else {
            $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_FINANCE_PLATBA_SAVE_VALIDATION_ERROR);
        }
    }

    public function ajaxPlatbaDelete()
    {
        ObjednavkyDAO::deleteFinancePlatbaById($_REQUEST["idPlatba"]);
        $this->autoChangeObjednavkaStav();
    }

    public function platbaAdd()
    {
        $platbaAddValidator = PlatbaValidator::isAddPlatbaValid($_REQUEST["typ-dokladu"], $_REQUEST["castka"]);
        if ($platbaAddValidator->isValid()) {
            //lada: vsechny vydajove doklady se ukladaji se zapornou castkou
            if ($_REQUEST["typ-dokladu"] == self::PLATBA_VYDAJOVY_DOKLAD and $_REQUEST["castka"] > 0) {
                $_REQUEST["castka"] = -$_REQUEST["castka"];
            }
            ObjednavkyDAO::createFinancePlatba(
                $_REQUEST["idObjednavka"],
                $_REQUEST["cislo-dokladu"],
                $_REQUEST["typ-dokladu"],
                $_REQUEST["castka"],
                date('Y-m-d'),
                CommonUtils::engDate($_REQUEST["splatnost"]),
                CommonUtils::engDate($_REQUEST["uhrazeno"]),
                $_REQUEST["zpusob-uhrady"],
                self::$zamestnanec->get_id()
            );
            $this->autoChangeObjednavkaStav();
        }

        CommonUtils::redirect('objednavky.php?idObjednavka=' . $_REQUEST['idObjednavka']);
    }

    public function ajaxProvizeSave()
    {
        //if (Validace::int($_REQUEST["provize-castka"])) { //tohle IMHO nenecha projit nulu
	   if ($_REQUEST["provize-castka"] >=-1){
            ObjednavkyDAO::saveFinanceProvize(
                $_REQUEST["idObjednavka"],
                $_REQUEST["provize-castka"]
            );
        }
    }

    //endregion

    //region PUBLIC POZNAMKY / TISKOVE SESTAVY *******************************************

    public function ajaxTsPoznamkyLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_TS_POZNAMKY);
    }

    public function ajaxTsPoznamkySave()
    {
        ObjednavkyDAO::saveTsPoznamkyByObjednavkaId($_REQUEST["idObjednavka"], iconv('utf-8', 'windows-1250', $_REQUEST["ts-poznamky"]), null, null, null, null, null);
    }

    public function ajaxTsTajnePoznamkyLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_TS_TAJNE_POZNAMKY);
    }

    public function ajaxTsTajnePoznamkySave()
    {
        ObjednavkyDAO::saveTsPoznamkyByObjednavkaId($_REQUEST["idObjednavka"], null, iconv('utf-8', 'windows-1250', $_REQUEST["ts-tajne-poznamky"]), null, null, null, null);
    }

    public function ajaxTsDopravaCSLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_TS_DOPRAVA_CS);
    }

    public function ajaxTsDopravaCSSave()
    {
        ObjednavkyDAO::saveTsPoznamkyByObjednavkaId($_REQUEST["idObjednavka"], null, null, iconv('utf-8', 'windows-1250', $_REQUEST["ts-doprava-cs"]), null, null, null);
    }

    public function ajaxTsStravovaniCSLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_TS_STRAVOVANI_CS);
    }

    public function ajaxTsStravovaniCSSave()
    {
        ObjednavkyDAO::saveTsPoznamkyByObjednavkaId($_REQUEST["idObjednavka"], null, null, null, iconv('utf-8', 'windows-1250', $_REQUEST["ts-stravovani-cs"]), null, null);
    }

    public function ajaxTsUbytovaniCSLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_TS_UBYTOVANI_CS);
    }

    public function ajaxTsUbytovaniCSSave()
    {
        ObjednavkyDAO::saveTsPoznamkyByObjednavkaId($_REQUEST["idObjednavka"], null, null, null, null, iconv('utf-8', 'windows-1250', $_REQUEST["ts-ubytovani-cs"]), null);
    }

    public function ajaxTsPojisteniCSLoad()
    {
        $this->loadObjednavkaDetail();

        $this->notifyObjednavkyObservers(IObjednavkyObserver::METHOD_OBJEDNAVKA_AJAX_TS_POJISTENI_CS);
    }

    public function ajaxTsPojisteniCSSave()
    {
        ObjednavkyDAO::saveTsPoznamkyByObjednavkaId($_REQUEST["idObjednavka"], null, null, null, null, null, iconv('utf-8', 'windows-1250', $_REQUEST["ts-pojisteni-cs"]));
    }

    public function ajaxTsKUhradeZalohaCSSave()
    {
        $datum = CommonUtils::czechToEnglishDate($_REQUEST["ts-k-uhrade-zaloha-datum"]);
        ObjednavkyDAO::saveTsKUhradeZalohaByObjednavkaId(
            $_REQUEST["idObjednavka"],
            $_REQUEST["ts-k-uhrade-zaloha-castka"] == '' ? 'NULL' : $_REQUEST["ts-k-uhrade-zaloha-castka"],
            $datum == '' ? '0000-00-00' : $datum
        );
    }

    public function ajaxTsKUhradeDoplatekCSSave()
    {
        $datum = CommonUtils::czechToEnglishDate($_REQUEST["ts-k-uhrade-doplatek-datum"]);
        ObjednavkyDAO::saveTsKUhradeDoplatekByObjednavkaId(
            $_REQUEST["idObjednavka"],
            $datum == '' ? '0000-00-00' : $datum
        );
    }

    //endregion

    //region PRIVATE METHODS *************************************************************

    private function notifyObjednavkyObservers($observableMethod)
    {
        if (is_null($this->observers))
            return;

        foreach ($this->observers as $o) {
            $o->$observableMethod();
        }
    }

    private function autoChangeObjednavkaStav()
    {
        $this->loadObjednavkaDetail();
        $stav = $this->objednavka->stav;

        //automaticky se stav meni jen pokud je obj v nasledujicich trech stavech
        if (!($stav == ObjednavkaEnt::STAV_REZERVACE || $stav == ObjednavkaEnt::STAV_ZALOHA || $stav == ObjednavkaEnt::STAV_PRODANO))
            return;

        $stavId = $this->objednavka->predictStavZPlateb();
        if (!is_null($stavId))
            ObjednavkyDAO::changeObjednavkaStavById($_REQUEST["idObjednavka"], $stavId);
    }

    private function autoUpdateCelkovaCastka()
    {
        $this->loadObjednavkaDetail();
        $castka = $this->objednavka->calcFinalniCenaObjednavky();
        ObjednavkyDAO::changeObjednavkaCelkovaCastkaById($_REQUEST["idObjednavka"], $castka);
    }

    /**
     * Vyzaduje mit nactenou objednavku @link(ObjednavkyModel::loadObjednavkaDetail())
     * @param $idSluzba
     * @param $numKapacitTotal
     * @param bool $checkStav
     */
    private function reserveKapacity($idSluzba, $numKapacitTotal, $checkStav = true)
    {
        //neni-li objednavka nactena, ukonci note lepsi by bylo vyhodit vyjimku
        if (is_null($this->objednavka))
            return;

        //nema-li objednavka takovou sluzbu ukonci
        $sluzba = $this->objednavka->getSluzbaHolder()->getSluzba($idSluzba);
        if (is_null($sluzba))
            return;

        //neni li objednavka v jednom z techto stavu, kapacity se neresi
        //pozor!! tady byla chyba - kapacity se musi resit uz ve stavu opce!! 
        //(inicialne se v nem rezervuji a tedy pokud se cokoli zmenilo, je treba s tim pocitat)
        if ($checkStav && !(
                $this->objednavka->stav == ObjednavkaEnt::STAV_OPCE ||
                $this->objednavka->stav == ObjednavkaEnt::STAV_REZERVACE ||
                $this->objednavka->stav == ObjednavkaEnt::STAV_ZALOHA ||
                $this->objednavka->stav == ObjednavkaEnt::STAV_PRODANO ||
                $this->objednavka->stav == ObjednavkaEnt::STAV_ODBAVENO
            )
        ) return;

        //nacti vsechny navazane TOK (v DAO jsou nacteny serazene podle velikosti volne kapacity od nejvyzsi)
        $terminyObjektoveKategorie = $sluzba->getTerminyObektoveKategorieHolder()->getTerminyObjektoveKategorie();
        if (is_null($terminyObjektoveKategorie)) {
            //rezervuj kapacity sluzby
	    $idZajezd = $this->objednavka->getZajezd()->id;
	    if($idZajezd > 0){
	    	ObjednavkyDAO::reserveKapacitySluzby($sluzba->id_cena, $numKapacitTotal, $idZajezd);
	    }else{
		//report error
		echo "wrong zajezd id";
	    }
            
        } else {
            //rezervuj kapacity TOK
            //prochazej TOK a odcitej jim kapacitu, pokud u jedne kapacita pretece, odcitej u dalsi
            $idsOvlivneneTOK = null;
            foreach ($terminyObjektoveKategorie as $tok) {
                //pokud byly vycerpany vsechny kapacity urcene k rezervaci ukonci
                if ($numKapacitTotal == 0)
                    break;

                //ma OK daneho TOK nastaveno, ze se ma prodavat jako celek?
                if ($tok->getObjektovaKategorie()->prodavat_jako_celek == ObjektovaKategorieEnt::PRODAVAT_JAKO_CELEK_ANO) {
                    $numKapacitTOK = $numKapacitTotal;
                } else {
                    $hlavniKapacita = $tok->getObjektovaKategorie()->hlavni_kapacita == 0 ? 1 : $tok->getObjektovaKategorie()->hlavni_kapacita;
                    $numKapacitTOK = ceil($numKapacitTotal / $hlavniKapacita);
                }

                //pokud celkovy pocet kapacit pretekl, sniz pocet kapacit, ktere se maji u tohoto TOK rezervovat na aktualni volny pocet kapacit
                $numKapacitTOK = $numKapacitTOK > $tok->kapacitaVolna ? $tok->kapacitaVolna : $numKapacitTOK;

                if ($numKapacitTOK != 0) {
                    //pokud TOK ma nejakou kapacitu, rezervuj ji
                    ObjednavkyDAO::reserveKapacityTOK($tok->id, $numKapacitTOK);

                    //sniz celkovy pocet kapacit, co ma byt rezervovan
                    $numKapacitTotal -= $numKapacitTOK;

                    //pokud byl TOK ovlivnen pridej ho do ovlivnenych
                    $idsOvlivneneTOK[] = $tok->id;
                }
            }
        }
    }

    private function loadObjednavkaDetail()
    {
        $this->objednavka = ObjednavkyDAO::readObjednavkaDetailsById($_REQUEST["idObjednavka"]);
    }

    private function loadObjednavkaToggleSectionState()
    {
        $this->toggleState = ToggleSectionsSessionEnt::pullFromSession(self::SESSION_NAME_OBJEDNAVKA_EDIT_SECTION_TOGGLE);
    }

    /**
     * @param $sluzba SluzbaEnt
     * @param bool|true $removable
     */
    private function sluzbaMinus($sluzba, $removable = true)
    {
        if ($removable && $sluzba->pocet <= 1) {
            ObjednavkyDAO::deleteSluzbaFromObjednavka(
                $_REQUEST["idObjednavka"],
                $_REQUEST["idSluzba"],
                $_REQUEST["typ"]
            );
        } else {
            ObjednavkyDAO::minusSluzbaPocet(
                $_REQUEST["idObjednavka"],
                $_REQUEST["idSluzba"],
                $_REQUEST["typ"]
            );
        }

        //vratit kapacity, pokud se nejedna o rucne pridanou sluzbu
        if ($_REQUEST["typ"] != SluzbaEnt::TYP_RUCNE_PRIDANA)
            $this->reserveKapacity($_REQUEST["idSluzba"], -1);
    }

    private function sluzbaPlus()
    {
        ObjednavkyDAO::plusSluzbaPocet(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idSluzba"],
            $_REQUEST["typ"]
        );

        //rezervovat kapacity, pokud se nejedna o rucne pridanou sluzbu
        if ($_REQUEST["typ"] != SluzbaEnt::TYP_RUCNE_PRIDANA)
            $this->reserveKapacity($_REQUEST["idSluzba"], 1);
    }

    private function sluzbaAdd()
    {
        ObjednavkyDAO::addSluzbaToObjednavka(
            $_REQUEST["idObjednavka"],
            $_REQUEST["idSluzba"],
            $_REQUEST["pocet"]
        );

        //rezervovat kapacity - zde se nemuze jednat o rucne pridanou sluzbu
        $this->reserveKapacity($_REQUEST["idSluzba"], $_REQUEST["pocet"]);
    }

    private function saveTermin($termin, $idObjednavka, $terminZajezd = "")
    {
        //@Lada: changed behavior - pokud nema zajezd dlohodobe terminy, tak je pole $termin prazdne, coz je spatne => nactou se udaje ze zajezdu
        if($termin == ""){
            $terminOd = $terminZajezd[0];
            $terminDo = $terminZajezd[1];           
        }else{
            $terminDump = explode('-', $termin);
            $terminOd = CommonUtils::engDate(trim($terminDump[0]));
            $terminDo = CommonUtils::engDate(trim($terminDump[1]));            
        }

        $pocetNoci = round((strtotime($terminDo) - strtotime($terminOd)) / (60 * 60 * 24));
        
        ObjednavkyDAO::saveObecneInfoTerminByObjednavkaId($idObjednavka, $terminOd, $terminDo,$pocetNoci);
    }

    //endregion

}