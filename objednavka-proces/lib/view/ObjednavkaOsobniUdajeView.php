<?php

class ObjednavkaOsobniUdajeView extends ObjednavkaView implements IObjednavkaModelOsobniUdajeObserver {
    /**
     * @var IObjednavkaModelForView
     */
    private $model;

    /**
     * @param $model IObjednavkaModelForView
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerOsobniUdajeObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelOsobniUdajeChanged()
    {
        echo $this->header();
        echo $this->srcWebHeader();
        echo $this->menu(2);
        echo $this->main();
        echo $this->buttons();
        echo $this->footer();
    }

    // PRIVATE METHODS *******************************************************************

    private function main()
    {
        $os = $this->model->getObjednavkaSession();
        $zajezd = $this->model->getZajezd();
        $serial = $zajezd->serial;
        $nazevCK = $this->model->getNazevCK();

        $objednatel = $os->getObjednatel();
        $poznamka = $os->getPoznamka();
        $pocetOsob = $os->getPocetOsob();
        $hesError = MessagesEnt::hasErrMsgs() ? "display-b" : "no-display";
        $out = "";

        $out .= "<form class='order' id='frm-osobni-udaje' method ='post' action='index.php?page=" . ObjednavkaController::PAGE_PLATBA . "'>";
        $out .= "   <div class='left top'>";
        $out .= "       <div class='$hesError sys-msg error' id='val-fail'>Některé údaje nejsou správně vyplněny!</div>";

        $out .= $this->osobniUdajeObjednatele($objednatel, $serial, $nazevCK);
        $out .= $this->kontaktniUdajeObjednatele($objednatel);
        $out .= $this->udajeUcastnikuZajezdu();
        $out .= $this->poznamka($poznamka);

        $out .= "   </div>";
        $out .= "</form>";

        return $out;
    }

    /**
     * @param $objednatel ObjednatelSessionEnt
     * @param $serial
     * @param $nazevCK
     * @return string html
     */
    private function osobniUdajeObjednatele($objednatel, $serial, $nazevCK)
    {
        $mesice = $this->model->getMesice();
        $jsemUcastnikem = $objednatel->getJsemUcastnikemZajezdu() == ObjednatelSessionEnt::JSEM_UCASTNIKEM_ZAJEZDU_ANO;
        $errJmeno = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_JMENO) ? "display-ib" : "no-display";
        $errPrijmeni = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_PRIJMENI) ? "display-ib" : "no-display";
        $errDatumNar = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_DAY) || MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_MONTH) || MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_YEAR) ? "display-ib" : "no-display";
        $errBorderJmeno = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_JMENO) ? "val-err-border" : "";
        $errBorderPrijmeni = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_PRIJMENI) ? "val-err-border" : "";
        $errBorderDatumNarDay = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_DAY) ? "val-err-border" : "";
        $errBorderDatumNarMonth = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_MONTH) ? "val-err-border" : "";
        $errBorderDatumNarYear = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_DATUM_NAR_YEAR) ? "val-err-border" : "";
        $out = "";

        $out .= "       <fieldset>";
        $out .= "           <legend>Osobní údaje objednatele</legend>";
        $out .= "           <div><label for='jmeno'>Jméno:</label><input value='" . $objednatel->getJmeno() . "' class='$errBorderJmeno' type='text' name='jmeno' id='jmeno' /><div class='$errJmeno val-err' id='err-jmeno'>Pole musí být vyplněno</div></div>";
        $out .= "           <div><label for='prijmeni'>Příjmení:</label><input value='" . $objednatel->getPrijmeni() . "' class='$errBorderPrijmeni' type='text' name='prijmeni' id='prijmeni' /><div class='$errPrijmeni val-err' id='err-prijmeni'>Pole musí být vyplněno</div></div>";
        $out .= "           <div>";
        $out .= "               <label for='datum-narozeni-day'>Datum narození:</label><input value='" . $objednatel->getDatumNarozeniDen() . "' class='$errBorderDatumNarDay' type='text' name='datum-narozeni-day' id='datum-narozeni-day' placeholder='Den' size='2' />";

        $out .= "               <select id='datum-narozeni-month' name='datum-narozeni-month' class='$errBorderDatumNarMonth'>";
        $out .= "                   <option value='0' " . ($objednatel->getDatumNarozeniMesic() ? "" : "selected='selected'") . ">Měsíc</option>";
        foreach ($mesice as $mesic) {
            $out .= "                   <option value='$mesic[0]' " . ($objednatel->getDatumNarozeniMesic() == $mesic[0] ? "selected='selected'" : "") . ">$mesic[1]</option>";
        }
        $out .= "               </select>";

        $out .= "               <input value='" . $objednatel->getDatumNarozeniRok() . "' type='text' id='datum-narozeni-year' class='$errBorderDatumNarYear' name='datum-narozeni-year' placeholder='Rok'/><div class='$errDatumNar val-err' id='err-datum-nar'>Datum narození není správně vyplněno</div>";
        $out .= "           </div>";
       
        $out .= "           <div class='ioff-t-1'><label class='optional'></label><span class='checkbox'><input type='checkbox' " . ($jsemUcastnikem ? "checked='checked'" : "") . " value='1' name='ucastnik' id='ucastnik' /><label for='ucastnik'></label><span>jsem účastníkem zájezdu</span></span></div>";
            $out .= "       <div class='ioff-t-1' id='objednavatel_dalsi_udaje'><label class='optional'></label>";
            $out .= "<input value='" . $objednatel->getRodneCislo() . "' type='text' placeholder='Rodné číslo' name='rodne-cislo' id='rodne-cislo' size='10' />";
            $out .= "               <input value='" . $objednatel->getCisloDokladu() . "' type='text' placeholder='Číslo dokladu' name='cislo-dokladu' id='cislo-dokladu'  size='10' />";
            $out .= "       </div>"; 
        $out .= "           <div class='ioff-t-1'><label class='optional'></label><span class='checkbox'><input type='checkbox' checked='checked' value='1' name='souhlas' id='souhlas' /><label for='souhlas'></label><span>souhlasím se <a href=\"/dokumenty/" . $serial->smluvni_podminky . "\" target=\"_blank\" title=\"Dokument obsahuje smluvní podmínky pro tento zájezd.\">smluvními podmínkami</a> účasti na zájezdech $nazevCK</span></span></div><div class='val-err no-display' id='err-souhlas'>Bez souhlasu nelze pokračovat</div>";
        $out .= "       </fieldset>";

        return $out;
    }

    /**
     * @param $objednatel ObjednatelSessionEnt
     * @return string html
     */
    private function kontaktniUdajeObjednatele($objednatel)
    {
        $newsletter = $objednatel->getZasilatNewsletter() == "1";
        $errEmail = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_EMAIL) ? "display-ib" : "no-display";
        $errTelefon = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_TELEFON) ? "display-ib" : "no-display";
        $errMesto = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_MESTO) ? "display-ib" : "no-display";
        $errPsc = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_PSC) ? "display-ib" : "no-display";
        $errBorderEmail = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_EMAIL) ? "val-err-border" : "";
        $errBorderTelefon = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_TELEFON) ? "val-err-border" : "";
        $errBorderMesto = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_MESTO) ? "val-err-border" : "";
        $errBorderPsc = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_PSC) ? "val-err-border" : "";
        $out = "";

        $out .= "       <fieldset>";
        $out .= "           <legend>Kontaktní údaje objednatele</legend>";
        $out .= "           <div><label for='email'>E-mail:</label><input value='" . $objednatel->getEmail() . "' class='$errBorderEmail' type='email' name='email' id='email' /><span class='input-help'>např.: novak@mujemail.cz</span><div class='$errEmail val-err' id='err-email'>Email není správně vyplněn</div></div>";
        $out .= "           <div>";
        $out .= "               <label for='telefon'>Telefon:</label>";

        $out .= $this->flags();

        $out .= "               <input value='" . $objednatel->getTelefonPredvolba() . "' type='text' name='telefon-pre' value='+420' size='2' id='telefon-pre' />";
        $out .= "               <input value='" . $objednatel->getTelefon() . "' class='$errBorderTelefon' type='text' name='telefon' id='telefon' /><span class='input-help'>např.: 999888777</span><div class='$errTelefon val-err' id='err-telefon'>Telefon není správně vyplněn</div><div class='no-display val-warn' id='warn-telefon'>Telefon neodpovídá formátu českého telefoního čísla (v objednávce lze pokračovat)</div>";
        $out .= "           </div>";
        $out .= "           <div><label for='mesto'>Město:</label><input value='" . $objednatel->getMesto() . "' class='$errBorderMesto' type='text' name='mesto' id='mesto' /><div class='$errMesto val-err' id='err-mesto'>Pole musí být vyplněno</div></div>";
        $out .= "           <div><label for='ulice-cp' class='optional'>Ulice a čp:</label><input value='" . $objednatel->getUliceCp() . "' type='text' name='uliceCp' id='ulice-cp' /></div>";
        $out .= "           <div><label for='psc' class='optional'>PSČ:</label><input value='" . $objednatel->getPsc() . "' class='$errBorderPsc' type='text' id='psc' name='psc' /><span class='input-help'>např.: 17012</span><div class='$errPsc val-err' id='err-psc'>PSČ není správně vyplněno</div></div>";
        $out .= "           <div class='ioff-t-1'><label class='optional'></label><span class='checkbox'><input type='checkbox' name='newsletter' value='1' id='newsletter' name='' /><label for='newsletter'></label><span>mám zájem o zasílání aktuálních nabídek CK SLAN tour</span><br/>
                           
             Zaškrtnutím výše uvedeného checkboxu projevujete souhlas se zpracováním osobních údajů v rozsahu jméno, příjmení, telefonní číslo a e-mailová adresa za účelem zasílání obchodních sdělení. Cestovní kancelář může zasílat obchodní sdělení formou SMS, MMS, elektronické pošty, poštou či sdělovat telefonicky a to maximálně 1x týdně.<br/><br/>
              Proti zasílání obchodních sdělení je možno vznést kdykoliv námitku, a to buď na adrese cestovní kanceláře nebo e-mailem zaslaným na adresu info@slantour.cz. V tomto případě nebude cestovní kancelář dále zasílat obchodní sdělení, ani jinak zpracovávat vaše osobní údaje pro účely přímého marketingu.  </span>

              </div>";
        //Lada: removed from newsletter checkbox due to GDPR " . ($newsletter ? "checked='checked'" : "") . "
        $out .= "       </fieldset>";

        return $out;
    }

    /**
     * @return string html
     */
    private function udajeUcastnikuZajezdu()
    {
        $os = $this->model->getObjednavkaSession();
        $ucastnici = $os->getUcastnici();
        $pocetOsob = $os->getPocetOsob();
        $out = "";

        $out .= "       <fieldset id='fs-ucastnici'>";
        $out .= "           <legend>Osobní údaje účastníků zájezdu</legend>";

        for ($i = 1; $i <= $pocetOsob; $i++) {
            $errJmenoPrijmeni = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_UC_JMENO . "-$i") || MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_UC_PRIJMENI . "-$i") ? "display-ib" : "no-display";
            $errBorderJmeno = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_UC_JMENO . "-$i") ? "val-err-border" : "";
            $errBorderPrijmeni = MessagesEnt::hasErrMsg(MessageEnt::ID_OS_UDAJE_UC_PRIJMENI . "-$i") ? "val-err-border" : "";
            $u = is_object($ucastnici[$i - 1]) ? $ucastnici[$i - 1] : new UcastnikSessionEnt();
            $out .= "       <div class='ucastnik ucastnik-$i'>";
            $out .= "           <label for='jmeno-$i'>Jméno:</label><input value='" . $u->getJmeno() . "' class='$errBorderJmeno input-large uc-jmeno' type='text' name='jmeno-$i' id='jmeno-$i' />";
            $out .= "           <label for='prijmeni-$i'>Příjmení:</label><input value='" . $u->getPrijmeni() . "' class='$errBorderPrijmeni input-large uc-prijmeni' type='text' name='prijmeni-$i' id='prijmeni-$i' />";
            $out .= "           <div class='$errJmenoPrijmeni val-err-uc' id='err-uc-$i'>Pole musí být vyplněno</div>";
            $out .= "           <div id='ucastnik-optional-$i'>";
            $out .= "               <label class='optional' for='email-$i'>Email:</label><input value='" . $u->getEmail() . "' class='' type='text' name='email-$i' id='email-$i' />";
            $out .= "               <label class='optional' for='telefon-$i'>Telefon:</label><input value='" . $u->getTelefon() . "' type='text' name='telefon-$i' id='telefon-$i' />";
            $out .= "               <label class='optional' for='datum-narozeni-$i'>Datum nar.:</label><input value='" . $u->getDatumNarozeni() . "' type='text' name='datum-narozeni-$i' id='datum-narozeni-$i' />";
            $out .= "               <label class='optional' for='rodne-cislo-$i'>Rodné č.:</label><input value='" . $u->getRodneCislo() . "' type='text' name='rodne-cislo-$i' id='rodne-cislo-$i' />";
            $out .= "               <label class='optional' for='cislo-dokladu-$i'>Č. dokladu:</label><input value='" . $u->getCisloDokladu() . "' type='text' name='cislo-dokladu-$i' id='cislo-dokladu-$i' />";
            $out .= "           </div>";
            $out .= "       </div>";
        }

        $out .= "       </fieldset>";

        return $out;
    }

    /**
     * @param $poznamka string
     * @return string html
     */
    private function poznamka($poznamka)
    {
        $out = "";

        $out .= "       <fieldset>";
        $out .= "           <legend>Poznámka (nepovinné)</legend>";
        $out .= "           <div id='poznamka-wrapper'><textarea name='poznamka' id='poznamka'>$poznamka</textarea></div>";
        $out .= "           <div id='legend'><span>*</span> takto označená pole je nutné vyplnit</div>";
        $out .= "       </fieldset>";

        return $out;
    }

    private function flags()
    {
        $topZeme = $this->model->getTopZeme();
        $zeme = $this->model->getZeme();


        $out = "";

        $out .= "<div id='flag-widget'>";
        $out .= "   <span id='select-flag'><div class='select-triangle'></div></span>";

        $out .= "   <span class='flag flag-picker flag-active' style='background-position: 0 -2256px;'></span>";
        //aktivni vlajky
        foreach ($zeme as $z)
            $out .= "<span class='flag flag-picker' id='flag-" . substr($z[2], 1, strlen($z[2])) . "' style='background-position: 0 " . $z[0] . ";'></span>";
        $out .= "</div>";

        //selektor vlajek
        $out .= "<ul id='country-selector'>";
        foreach ($topZeme as $tz) {
            $cssCountryBorder = $tz == end($topZeme) ? "class='country-border'" : "";
            $out .= "<li $cssCountryBorder>";
            $out .= "   <span class='flag flag-selector' style='background-position: 0 " . $tz[0] . ";'></span>";
            $out .= "$tz[1] ($tz[2])<input type='hidden' value='$tz[2]' />";
            $out .= "</li>";
        }
        foreach ($zeme as $z) {
            $out .= "<li>";
            $out .= "   <span class='flag flag-selector' style='background-position: 0 " . $z[0] . ";'></span>";
            $out .= "$z[1] ($z[2])<input type='hidden' value='$z[2]' />";
            $out .= "</li>";
        }
        $out .= "</ul>";

        return $out;
    }

    private function buttons()
    {
        $out = "";

        $out .= "   <div id='order-btns'>";
        $out .= "       <a class='btn-round btn-medium btn-green btn-arrow-r-white-medium float-right' id='btn-s2-vybrat' href=''>Vybrat</a>";
        $out .= "       <a class='btn-round btn-small btn-white btn-arrow-l-black-small float-left' href='index.php?page=" . ObjednavkaController::PAGE_ZAJEZD . "' id='btn-s2-zpet'>Zpět</a>";
        $out .= "   </div>";

        return $out;
    }
}