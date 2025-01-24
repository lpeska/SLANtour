<?php

class ObjednavkaSouhrnView extends ObjednavkaView implements IObjednavkaModelSouhrnObserver
{
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
        $this->model->registerSouhrnObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelSouhrnChanged()
    {
        echo $this->header();
        echo $this->srcWebHeader();
        echo $this->menu(4);
        echo $this->souhrn();
        echo $this->buttons();
        echo $this->footer();
    }

    // PRIVATE METHODS *******************************************************************

    private function souhrn()
    {
        $zajezd = $this->model->getZajezd();
        $mainFoto = $zajezd->serial->mainFoto;
        $os = $this->model->getObjednavkaSession();
        $termin = $os->getTermin();
        $pocetOsob = $os->getPocetOsob();
        $nazevZajezdu = $zajezd->constructNazev();
        $out = "";

        //todo action zmenit na controller
        $out .= "<div id='order-zajezd-info'>";
        $out .= "   <div class='float-left image'><img src='https://www.slantour.cz/foto/nahled/$mainFoto->url' alt='$mainFoto->nazev'></div>";
        $out .= "   <div class='info float-left'>";
        $out .= "       <div class='header'>$nazevZajezdu</div>";
        $out .= "       <div class='row row-nospace'>";
        $out .= "           <strong>Termín:</strong> $termin";
        $out .= "       </div>";
        $out .= "       <div class='row-medium'>";
        $out .= "           <strong>Počet osob:</strong> $pocetOsob";
        $out .= "       </div>";
        $out .= "   </div>";
        $out .= "   <div class='clearfix'></div>";
        $out .= "</div>";

        $out .= "<div class='left top'>";

        $out .= $this->osobniUdajeObjednatel();
        if ($os->hasUcastnici())
            $out .= $this->osobniUdajeUcastnici();
        if ($os->hasPoznamka())
            $out .= $this->osobniUdajePoznamka();
        $out .= $this->objednaneSluzby();
        $out .= $this->zpusobPlatby();

        $out .= $this->povinneInfo();
        
        $out .= "</div>";

        $out .= $this->submitForm();

        return $out;
    }

    private function buttons()
    {
        $out = "";

        $out .= "   <div id='order-btns'>";
        $out .= "       <a class='btn-round btn-medium btn-green btn-arrow-r-white-medium float-right' id='btn-s4-objednat' style='padding-right:40px' href=''>Objednávka zavazující k platbě</a>";
        $out .= "       <a class='btn-round btn-small btn-white btn-arrow-l-black-small float-left' href='index.php?page=" . ObjednavkaController::PAGE_PLATBA . "' id='btn-s4-zpet'>Zpět</a>";
        $out .= "   </div>";

        return $out;
    }
    private function povinneInfo()
    {
        $zajezd = $this->model->getZajezd();
        $smluvni_podminky = $zajezd->serial->smluvni_podminky;

        $out .= "<table class='order order-recap'>";
        $out .= "   <tr class='recap-tr'><th>Prohlášení zákazníka</th></tr>";
        $out .= "   <tr><td>
            <b>Odesláním objednávky zároveň:</b><br/>
            <ul>
                <li>Souhlasím se <a href=\"https://www.slantour.cz/dokumenty/".$smluvni_podminky."\" target=\"_blank\">smluvními podmínkami cestovní kanceláře SLAN tour</a>, s.r.o., které jsou nedílnou součástí objednávky/smlouvy o zájezdu.</li>
                <li>Potvrzuji, že jsem se seznámil s podrobným vymezením zájezdu.</li>
                <li>Prohlašuji, že jsem oprávněn uzavřít smlouvu za všechny osoby, uvedené v této smlouvě a odpovídám za úhradu celkové ceny zájezdu.</li>
                <li>Potvrzuji, že jsem se seznámil s příslušným <a href=\"https://www.slantour.cz/dokumenty/3126-povinne-informace-k-zajezdu.pdf\" target=\"_blank\">formulářem dle vyhlášky č. 122/2018 Sb.</a>, o vzorech formulářů pro jednotlivé typy zájezdů a spojených cestovních služeb, a s <a href=\"https://www.slantour.cz/dokumenty/3041-certifikat-pojistovny.pdf\" target=\"_blank\">dokladem o pojištění CK proti úpadku</a>.</li>
            </ul>";
        $out .= "</table>";
        //print_r($zajezd);
        return $out;        
        
    }
    
    private function osobniUdajeObjednatel()
    {
        $objednatel = $this->model->getObjednavkaSession()->getObjednatel();
        $jmenoPrijemni = $objednatel->getJmeno() . " " . $objednatel->getPrijmeni();
        $adresa = ($objednatel->getUliceCp() == "" ? "" : $objednatel->getUliceCp() . ", ") . $objednatel->getMesto() . ($objednatel->getPsc() == "" ? "" : ", " . $objednatel->getPsc());
        $narozen = $objednatel->getDatumNarozeniDen() . "." . $objednatel->getDatumNarozeniMesic() . ". " . $objednatel->getDatumNarozeniRok();
        $kontakt = $objednatel->getEmail() . ", " . $objednatel->getTelefonPredvolba() . " " . $objednatel->getTelefon();
          $out = "";

        $out .= "<table class='order order-recap'>";
        $out .= "   <colgroup><col width='25%'><col width='30%'><col width='10%'><col width='35%'></colgroup>";
        $out .= "   <tr class='recap-tr'><th>Osobní údaje objednatele</th><th class='right'>Adresa</th><th class='center'>Narozen</th><th class='right'>Kontakt</th></tr>";
        $out .= "   <tr><td>$jmenoPrijemni</td><td class='right'>$adresa</td><td class='center'>$narozen</td><td class='right'>$kontakt</td></tr>";
        $out .= "</table>";

        return $out;
    }

    private function osobniUdajeUcastnici()
    {
        $os = $this->model->getObjednavkaSession();
        $ucastnici = $os->getUcastnici();
        $objednavatel = $os->getObjednatel();
        $prijmeni_obj_puvodni = $objednavatel->getPrijmeni();
        $objednavatel->setPrijmeni($objednavatel->getPrijmeni()." <span>(Objednavatel)</span>");
        if($objednavatel->getJsemUcastnikemZajezdu()){
            //pridam objednavatele jako prvniho ucastnika
            array_unshift ( $ucastnici , $objednavatel );
        }
        $out = "";
        $out .= "<table class='order order-recap'>";
        $out .= "   <colgroup><col width='26%'><col width='13%'><col width='13%'><col width='13%'><col width='35%'></colgroup>";
        $out .= "   <tr><th>Osobní údaje účastníkú</th><th class='center'>Narozen</th><th class='center'>Rodné č.</th><th class='center'>Č. dokladu</th><th class='right'>Kontakt</th></tr>";
        for ($i = 0; $i < $os->getPocetOsob(); $i++) {
            $uc = $ucastnici[$i];
            $jmenoPrijmeni = $uc->getJmeno() . " " . $uc->getPrijmeni();
            $narozen = $uc->getDatumNarozeni() == "" ? "-" : $uc->getDatumNarozeni();
            $rodneCislo = $uc->getRodneCislo() == "" ? "-" : $uc->getRodneCislo();
            $cisloDokladu = $uc->getCisloDokladu() == "" ? "-" : $uc->getCisloDokladu();
            $kontakt = $uc->getEmail() == "" && $uc->getTelefon() == "" ? "-" : ($uc->getEmail() != "" && $uc->getTelefon() != "" ? $uc->getEmail() . ", " . $uc->getTelefon() : $uc->getEmail() . $uc->getTelefon()); // - / email, telefon / email / telefon /
            $out .= "   <tr><td>$jmenoPrijmeni</td><td class='center'>$narozen</td><td class='center'>$rodneCislo</td><td class='center'>$cisloDokladu</td><td class='right'>$kontakt</td></tr>";
        }
        $out .= "</table>";
        $objednavatel->setPrijmeni($prijmeni_obj_puvodni);
        array_shift($ucastnici);
        return $out;
    }

    private function osobniUdajePoznamka()
    {
        $os = $this->model->getObjednavkaSession();
        $poznamka = $os->getPoznamka();
        $out = "";

        $out .= "<table class='order order-recap'>";
        $out .= "   <tr><th>Poznámka</th></tr>";
        $out .= "   <tr><td>$poznamka</td></tr>";
        $out .= "</table>";

        return $out;
    }

    private function objednaneSluzby()
    {
        $zajezd = $this->model->getZajezd();
        $casoveSlevy = $zajezd->getCasoveSlevy();
        $sluzbyAll = $zajezd->getSluzbaHolder()->getSluzbyAll();
        $os = $this->model->getObjednavkaSession();
        $sluzbyAllSess = $os->getSluzbyAll();
        $zpusobPlatby = $os->getZpusobPlatby();
        $kurzEur = $this->model->getKurzEur();
        
        $isEur = $zpusobPlatby == CentralniDataEnt::ID_PLATBA_SLOVENSKO;
        $mena = $isEur ? " " . ObjednavkaView::MENA_EUR : " " . ObjednavkaView::MENA_KC;
        $baseCelkovaCenaKc = $zajezd->getSluzbaHolder()->calcCenaOfSluzbaSubset($sluzbyAllSess, $os->calcPocetNoci(), false);
        $baseCelkovaCenaEUR = $zajezd->getSluzbaHolder()->calcCenaOfSluzbaSubset($sluzbyAllSess, $os->calcPocetNoci(), true, $kurzEur);
        $castkaCasovaSlevaKc = 0;
        $castkaCasovaSlevaEUR = 0;
        
        $out = "";

        $out .= "<table class='order order-recap'>";
        $out .= "   <colgroup><col width='46%'><col width='12%'><col width='7%'><col width='10%'><col width='3%'><col width='22%'></colgroup>";
        $out .= "   <tr><th>Objednané služby</th><th class='right'>Cena</th><th class='right' colspan='4'>Celkem</th></tr>";

        foreach ($sluzbyAll as $s) {
            $sSess = CommonUtils::searchArrayOfObjects($sluzbyAllSess, $s->id_cena, "getId", null);
            $pocet = is_null($sSess) ? 0 : $sSess->getPocet();
            $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $os->calcPocetNoci() : 1;
            $status = $this->viewSluzbaStatus($s, $pocet);
            if ($pocet > 0) {
                $cenaSluzby = CommonUtils::formatPrice($s->calcCastkaFull($pocet, $pocetNoci, $isEur, $kurzEur)) . $mena;
                $out .= "   <tr><td>$status$s->nazev_ceny</td><td class='right'>" . CommonUtils::formatPrice($isEur ? $s->calcCastkaFull(1, 1, true, $kurzEur) : $s->castka) . "$mena</td><td>" . ($pocetNoci != 1 ? "&times; $pocetNoci" : "") . "</td><td>&times; $pocet</td><td>&#x0003D;</td><td class='price right'>$cenaSluzby</td></tr>";
            }
        }
     //   print_r($zajezd);
        if(!is_null($casoveSlevy)){
            foreach($casoveSlevy as $cs){ 
                $castkaSluzby = $zajezd->getSluzbaHolder()->calcCenaOfSluzbaSubsetOnlySluzbyAndLastminute($sluzbyAllSess, $os->calcPocetNoci(), false);
                $castkaSluzbyEUR = $zajezd->getSluzbaHolder()->calcCenaOfSluzbaSubsetOnlySluzbyAndLastminute($sluzbyAllSess, $os->calcPocetNoci(), true,$kurzEur);
                $castkaCasovaSlevaKc = $zajezd->getSluzbaHolder()->calcCasovaSleva($castkaSluzby, $os->getPocetOsob(), $cs->castka, $cs->mena);
                $castkaCasovaSlevaEUR = $zajezd->getSluzbaHolder()->calcCasovaSleva($castkaSluzbyEUR, $os->getPocetOsob(), $cs->castka, $cs->mena, true, $kurzEur);
                
                $out .= "   <tr><td>$cs->nazev_slevy</td><td class='right'>-$cs->castka $cs->mena</td><td></td><td>&times; 
                        ".(($cs->mena=="%")?($castkaSluzby. " " . ObjednavkaView::MENA_KC):($os->getPocetOsob()))."
                            </td><td>&#x0003D;</td><td class='price right'>
                            -".CommonUtils::formatPrice($castkaCasovaSlevaKc) . " " . ObjednavkaView::MENA_KC."</td></tr>";
                break; //lze uplatnit pouze jednu casovou slevu
            }
        }

        $celkovaCena = CommonUtils::formatPrice($baseCelkovaCenaKc-$castkaCasovaSlevaKc) . " " . ObjednavkaView::MENA_KC . " (" . CommonUtils::formatPrice($baseCelkovaCenaEUR-$castkaCasovaSlevaEUR) . " " . ObjednavkaView::MENA_EUR . ")";

        
        
        $out .= "   <tr><td colspan='5' class='lbl strong'>Celková cena</td><td class='price right'>$celkovaCena</td></tr>";
        $out .= "</table>";

        return $out;
    }

    private function zpusobPlatby()
    {
        $os = $this->model->getObjednavkaSession();
        $zp = CentralniDataEnt::findZpusobPlatbyById($this->model->getZpusobyPlateb(), $os->getZpusobPlatby());
        $out = "";

        $out .= "<table class='order order-recap'>";
        $out .= "   <colgroup><col width='30%'><col width='70%'></colgroup>";
        $out .= "   <tr><th colspan='2'>Způsob platby</th></tr>";
        $out .= "   <tr><td class=''>$zp->nazevWeb</td><td>$zp->text</td></tr>";
        $out .= "</table>";

        return $out;
    }

    private function submitForm()
    {
        $zajezd = $this->model->getZajezd();
        $serial = $zajezd->serial;
        
        $smluvni_podminky = $serial->smluvni_podminky;
        $os = $this->model->getObjednavkaSession();
        $objednatel = $os->getObjednatel();
        $objednatelDatumNarozeni = CommonUtils::czechDate($objednatel->getDatumNarozeniRok() . "-" . $objednatel->getDatumNarozeniMesic() . "-" . $objednatel->getDatumNarozeniDen());
        $ucastnici = $os->getUcastnici();
        $allSluzby = $os->getSluzbyAll();
        $_SESSION["hotovo_termin"] = $_SESSION["hotovo_sluzby"] = $_SESSION["hotovo_kontakty"] = true;
        $_SESSION["upresneni_termin_od"] = $os->getTerminOd();
        $_SESSION["upresneni_termin_do"] = $os->getTerminDo();
        $_SESSION["pocet_osob"] = $os->getPocetOsob();
        $out = "";

        //zdrojovy web
        $out .= "<form id='frm-souhrn' method ='post' action='/objednavka.php?id_zajezd=$zajezd->id&id_serial=$serial->id&lev1=$serial->nazev_web'>";
        $out .= "   <input type='hidden' name='src_web' value='" . $_SESSION["src_web"] . "' />";

        //zajezd
        $out .= "   <input type='hidden' name='upresneni_terminu_od' value='" . $os->getTerminOd() . "' />";
        $out .= "   <input type='hidden' name='upresneni_terminu_do' value='" . $os->getTerminDo() . "' />";
        $out .= "   <input type='hidden' name='nazev_ubytovani_web' value='" . $serial->nazev_web . "' />";
        $out .= "   <input type='hidden' name='poznamky' value='" . $os->getPoznamka() . "' />";
        $out .= "   <input type='hidden' name='pocet_osob' value='" . $os->getPocetOsob() . "' />";
        $out .= "   <input type='hidden' name='submit_kontakty' value='new-objednavka' />";

        //objednavajici
        $out .= "   <input type='hidden' name='jmeno' value='" . $objednatel->getJmeno() . "' />";
        $out .= "   <input type='hidden' name='prijmeni' value='" . $objednatel->getPrijmeni() . "' />";
        $out .= "   <input type='hidden' name='datum_narozeni' value='" . $objednatelDatumNarozeni . "' />";
        $out .= "   <input type='hidden' name='email' value='" . $objednatel->getEmail() . "' />";
        $out .= "   <input type='hidden' name='telefon' value='" . $objednatel->getTelefon() . "' />";
        $out .= "   <input type='hidden' name='ulice' value='" . $objednatel->getUliceCp() . "' />";
        $out .= "   <input type='hidden' name='mesto' value='" . $objednatel->getMesto() . "' />";
        $out .= "   <input type='hidden' name='psc' value='" . $objednatel->getPsc() . "' />";
        $out .= "   <input type='hidden' name='rodne-cislo' value='" . $objednatel->getRodneCislo() . "' />";
        $out .= "   <input type='hidden' name='cislo-dokladu' value='" . $objednatel->getCisloDokladu() . "' />";
        $out .= "   <input type='hidden' name='novinky' value='" . $objednatel->getZasilatNewsletter() . "' />";
        $out .= "   <input type='hidden' name='objednavajici_je_ucastnik' value='" . $objednatel->getJsemUcastnikemZajezdu() . "' />";
        
        $out .= "   <input type='hidden' name='smluvni_podminky' value='" . $smluvni_podminky . "' />";

        //sluzby
        $out .= "   <input type='hidden' name='pocet_cen' value='" . $os->calcPocetObjednanychSluzeb() . "' />";
        $i = 0;
        foreach ($allSluzby as $s) {
            if ($s->getPocet() > 0) {
                $out .= "<input type='hidden' name='id_cena_$i' value='" . $s->getId() . "' />";
                $out .= "<input type='hidden' name='cena_pocet_$i' value='" . $s->getPocet() . "' />";
                $out .= "<input type='hidden' name='typ_ceny_$i' value='" . $s->getTyp() . "' />";
                $i++;
            }
        }

        //ucastnici
        //pokud je objwednatel ucastnikem, musim ho pridat i jako ucastnika, nestaci ho mit jen jako objednatele
        if ($objednatel->getJsemUcastnikemZajezdu()) {
            $out .= "<input type='hidden' name='jmeno_1' value='" . $objednatel->getJmeno() . "' />";
            $out .= "<input type='hidden' name='prijmeni_1' value='" . $objednatel->getPrijmeni() . "' />";
            $out .= "<input type='hidden' name='email_1' value='" . $objednatel->getEmail() . "' />";
            $out .= "<input type='hidden' name='telefon_1' value='" . $objednatel->getTelefon() . "' />";
            $out .= "<input type='hidden' name='datum_narozeni_1' value='" . $objednatel->getDatumNarozeniDen() . ". " . $objednatel->getDatumNarozeniMesic() . ". " . $objednatel->getDatumNarozeniRok() . "' />";
        }

        for ($i = 0; $i < $os->calcPocetUcastniku(); $i++) {
            //tady se musi projevit to zda jsem jiz pridal objednatele nebo ne
            $j = $objednatel->getJsemUcastnikemZajezdu() ? $i + 2 : $i + 1;
//            $out .= "<input type='hidden' name='id_klient_$i' value='" . $ucastnici[$i]->id . "' />"; //id?
            $out .= "<input type='hidden' name='jmeno_$j' value='" . $ucastnici[$i]->getJmeno() . "' />";
            $out .= "<input type='hidden' name='prijmeni_$j' value='" . $ucastnici[$i]->getPrijmeni() . "' />";
            $out .= "<input type='hidden' name='email_$j' value='" . $ucastnici[$i]->getEmail() . "' />";
            $out .= "<input type='hidden' name='telefon_$j' value='" . $ucastnici[$i]->getTelefon() . "' />";
            $out .= "<input type='hidden' name='datum_narozeni_$j' value='" . $ucastnici[$i]->getDatumNarozeni() . "' />";
            $out .= "<input type='hidden' name='rodne_cislo_$j' value='" . $ucastnici[$i]->getRodneCislo() . "' />";
            $out .= "<input type='hidden' name='cislo_pasu_$j' value='" . $ucastnici[$i]->getCisloDokladu() . "' />";
        }

        //zpusob platby
        $out .= "   <input type='hidden' name='zpusob_platby' value='" . $os->getZpusobPlatby() . "' />";

        $out .= "</form>";

        return $out;
    }
}