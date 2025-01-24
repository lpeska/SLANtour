<?php

class ObjednavkaZajezdView extends ObjednavkaView implements IObjednavkaModelZajezdObserver
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
        $this->model->registerZajezdObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelZajezdChanged()
    {
        echo $this->header();
        echo $this->srcWebHeader();
        echo $this->menu(1);
        echo $this->zajezd();
        echo $this->buttons();
        echo $this->footer();
    }

    public function modelTerminChanged()
    {

    }


    // PRIVATE METHODS *******************************************************************

    private function zajezd()
    {
        $zajezd = $this->model->getZajezd();
        $mainFoto = $zajezd->serial->mainFoto;
        $os = $this->model->getObjednavkaSession();
        $pocetOsob = $os->getPocetOsob() == "" ? "0" : $os->getPocetOsob();
        $nazevZajezdu = $zajezd->constructNazev();
        $hasErrCls = MessagesEnt::hasErrMsgs() ? "display-b" : "no-display";
        $errPocetOsob = MessagesEnt::hasErrMsg(MessageEnt::ID_ZAJEZD_POCET_OSOB) ? "display-ib" : "no-display";
        $out = "";

        $out .= "<form class='order' id='frm-zajezd' method ='post' action='index.php?page=" . ObjednavkaController::PAGE_OSOBNI_UDAJE . "'>";

        if (MessagesEnt::hasWarnMsg(MessageEnt::ID_SLUZBA_VYPRODANO)) {
            $out .= "<div class='display-b popup-msg warning'>";
            $out .= "    <div class='text'>Některé objednané služby jsou vyprodány nebo na dotaz!<br/>Služby lze přezto objednat, následně Vás bude kontaktovat náš pracovník.</br>Přejete si pokračovat?</div>";
            $out .= "    <span id='warning-btn-yes' class='btn-round btn-small btn-green off-r-10'>Ano</span><span id='warning-btn-no' class='btn-round btn-small btn-white'>Ne</span>";
            $out .= "</div>";
            $out .= "<input type='hidden' class='validation-skip' name='sl-vyprodano-ok' value='1'/>"; //preskoc tuto validaci
        }

        if (MessagesEnt::hasWarnMsg(MessageEnt::ID_ZAJEZD_TERMIN_OUT_OF_SEASON)) {
            $out .= "<div class='display-b popup-msg warning'>";
            $out .= "    <div class='text'>Vámi zvolený termín zasahuje mimo období zájezdu!<br/>Výsledná cena zájezdu se proto může mírně lišit.<br/>Pracovník CK Vás bude v nejbližší době kontaktovat.</br>Přejete si pokračovat?</div>";
            $out .= "    <span id='warning-btn-yes' class='btn-round btn-small btn-green off-r-10'>Ano</span><span id='warning-btn-no' class='btn-round btn-small btn-white'>Ne</span>";
            $out .= "</div>";
            $out .= "<input type='hidden' class='validation-skip' name='sl-vyprodano-ok' value='1'/>"; //preskoc predchozi validaci
            $out .= "<input type='hidden' class='validation-skip' name='sl-termin-ok' value='1'/>"; //preskoc tuto validaci
        }

        $out .= "<div class='$hasErrCls sys-msg error' id='val-fail'>Některé údaje nejsou správně vyplněny!</div>";
        $out .= "<div id='order-zajezd-info'>";
        $out .= "   <div class='float-left image'><img src='https://www.slantour.cz/foto/nahled/$mainFoto->url' alt='$mainFoto->nazev' /></div>";
        $out .= "   <div class='info float-left'>";
        $out .= "       <div class='header'>$nazevZajezdu</div>";

        $out .= $this->termin();

        $out .= "       <div class='row'>";
        $out .= "           <label for='' class='text'>Počet osob: </label><div class='i-num il-bl float-left'><input type='text' value='$pocetOsob' size='2' name='pocet-osob' /><div class='p-sign'>+</div><div class='m-sign'>-</div><div class='clearfix'></div></div>";
        $out .= "           <div id='pocet-osob-val' class='$errPocetOsob val-err float-left'>Vyplňte počet osob</div><div class='clearfix'></div>";
        $out .= "       </div>";
        $out .= "   </div>";
        $out .= "   <div class='clearfix'></div>";
        $out .= "</div>";

        if ($zajezd->getSluzbaHolder()->hasLastMinute())
            $out .= $this->zajezdLastMinute();
        if ($zajezd->getSluzbaHolder()->hasSluzba())
            $out .= $this->zajezdSluzby();
        if ($zajezd->getSluzbaHolder()->hasPriplatek())
            $out .= $this->zajezdPriplatky();
        if ($zajezd->getSluzbaHolder()->hasSleva())
            $out .= $this->zajezdSlevy();
        if ($zajezd->getSluzbaHolder()->hasOdjezdoveMisto())
            $out .= $this->zajezdOdjezdoveMisto();
        if ($zajezd->hasCasoveSlevy())
            $out .= $this->zajezdCasoveSlevy();
        $out .= $this->zajezdCenaCelkem();

        $out .= "</form>";

        return $out;
    }

    private function zajezdLastMinute()
    {
        $lastMinute = $this->model->getZajezd()->getSluzbaHolder()->getLastMinute();
        $os = $this->model->getObjednavkaSession();
        $lastMinuteSess = $os->getLastMinute();
        $errLastminute = MessagesEnt::hasErrMsg(MessageEnt::ID_ZAJEZD_LASTMINUTE) ? "display-ib" : "no-display";
        $errSluzbyLastminute = MessagesEnt::hasErrMsg(MessageEnt::ID_ZAJEZD_SLUZBY_LASTMINUTE) ? "display-ib" : "no-display";
        $out = "";

        $out .= "   <table class='order order-last-minute'>";
        $out .= "       <colgroup><col width='49%'><col width='12%'><col width='7%'><col width='9%'><col width='3%'><col width='20%'></colgroup>";
        $out .= "       <tr><th>Last minute<div id='last-minute-val' class='$errLastminute val-err'>Vyberte last minute</div><div id='last-minute-sluzby-val' class='$errSluzbyLastminute val-err'>Vyberte last minute nebo službu</div></th><th class='right'>Cena</th><th colspan='4' class='right'>Celkem</th></tr>";

        if (!is_null($lastMinute)) {
            foreach ($lastMinute as $s) {
                $lmSess = CommonUtils::searchArrayOfObjects($lastMinuteSess, $s->id_cena, "getId", null);
                $pocet = is_null($lmSess) ? "0" : $lmSess->getPocet();
                $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $os->calcPocetNoci() : 1;
                $status = $this->viewSluzbaStatus($s, $pocet);
                $out .= "       <tr>";
                $out .= "           <td>$status$s->nazev_ceny</td>";
                $out .= "           <td class='right'><span class='price-one'>" . CommonUtils::formatPrice($s->castka) . "</span> Kč</td>";
                $out .= "           <td class='center'>" . ($pocetNoci != 1 ? "&times;<span class='pocet-noci'>$pocetNoci</span>" : "") . "&times;</td>";
                $out .= "           <td><div class='i-num'><input type='text' value='$pocet' name='last-minute-$s->id_cena' size='2' /><div class='p-sign'>+</div><div class='m-sign'>-</div><div class='clearfix'></div></div></td>";
                $out .= "           <td>&#x0003D;</td>";
                $out .= "           <td class='price right'><span class='price-full'>0</span> Kč</td>";
                $out .= "       </tr>";
            }
        }

        $out .= "   </table>";

        return $out;
    }

    private function zajezdSluzby()
    {
        $sluzby = $this->model->getZajezd()->getSluzbaHolder()->getSluzby();
        $os = $this->model->getObjednavkaSession();
        $sluzbySess = $os->getSluzby();
        $errSluzby = MessagesEnt::hasErrMsg(MessageEnt::ID_ZAJEZD_SLUZBY) ? "display-ib" : "no-display";
        $out = "";

        $out .= "   <table class='order order-sluzby'>";
        $out .= "       <colgroup><col width='49%'><col width='12%'><col width='7%'><col width='9%'><col width='3%'><col width='20%'></colgroup>";
        $out .= "       <tr><th>Služby<div id='sluzby-val' class='$errSluzby val-err'>Vyberte službu</div></th><th class='right'>Cena</th><th colspan='4' class='right'>Celkem</th></tr>";

        if (!is_null($sluzby)) {
            foreach ($sluzby as $s) {
                $slSess = CommonUtils::searchArrayOfObjects($sluzbySess, $s->id_cena, "getId", null);
                $pocet = is_null($slSess) ? "0" : $slSess->getPocet();
                $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $os->calcPocetNoci() : 1;
                $status = $this->viewSluzbaStatus($s, $pocet);

                $out .= "       <tr>";
                $out .= "           <td>$status$s->nazev_ceny</td>";
                $out .= "           <td class='right'><span class='price-one'>" . CommonUtils::formatPrice($s->castka) . "</span> Kč</td>";
                $out .= "           <td class='center'>" . ($pocetNoci != 1 ? "&times;<span class='pocet-noci'>$pocetNoci</span>" : "") . "&times;</td>";
                $out .= "           <td><div class='i-num'><input type='text' value='$pocet' name='sluzba-$s->id_cena' size='2' /><div class='p-sign'>+</div><div class='m-sign'>-</div><div class='clearfix'></div></div></td>";
                $out .= "           <td>&#x0003D;</td>";
                $out .= "           <td class='price right'><span class='price-full'>0</span> Kč</td>";
                $out .= "       </tr>";
            }
        }

        $out .= "   </table>";

        return $out;
    }

    private function zajezdPriplatky()
    {
        $priplatky = $this->model->getZajezd()->getSluzbaHolder()->getPriplatky();
        $os = $this->model->getObjednavkaSession();
        $priplatkySess = $os->getPriplatky();
        $out = "";

        $out .= "   <table class='order order-priplatky'>";
        $out .= "       <colgroup><col width='49%'><col width='12%'><col width='7%'><col width='9%'><col width='3%'><col width='20%'></colgroup>";
        $out .= "       <tr><th>Příplatky</th><th class='right'>Cena</th><th colspan='4' class='right'>Celkem</th></tr>";

        if (!is_null($priplatky)) {
            foreach ($priplatky as $s) {
                $prSess = CommonUtils::searchArrayOfObjects($priplatkySess, $s->id_cena, "getId", null);
                $pocet = is_null($prSess) ? "0" : $prSess->getPocet();
                $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $os->calcPocetNoci() : 1;
                $status = $this->viewSluzbaStatus($s, $pocet);
                if($_REQUEST["debug"] == 1) {
                    echo "<pre>"; var_dump($s->id_cena); echo "</pre>";
                    echo "<pre>"; var_dump($s->use_pocet_noci); echo "</pre>";
                }

                $out .= "       <tr>";
                $out .= "           <td>$status$s->nazev_ceny</td>";
                $out .= "           <td class='right'><span class='price-one'>" . CommonUtils::formatPrice($s->castka) . "</span> Kč</td>";
                $out .= "           <td class='center'>" . ($pocetNoci != 1 ? "&times;<span class='pocet-noci'>$pocetNoci</span>" : "") . "&times;</td>";
                $out .= "           <td><div class='i-num'><input type='text' value='$pocet' name='priplatek-$s->id_cena' size='2' /><div class='p-sign'>+</div><div class='m-sign'>-</div><div class='clearfix'></div></div></td>";
                $out .= "           <td>&#x0003D;</td>";
                $out .= "           <td class='price right'><span class='price-full'>0</span> Kč</td>";
                $out .= "       </tr>";
            }
        }

        $out .= "   </table>";

        return $out;
    }

    private function zajezdSlevy()
    {
        $zajezd = $this->model->getZajezd();
        $slevy = $zajezd->getSluzbaHolder()->getSlevy();
        $os = $this->model->getObjednavkaSession();
        $slevySess = $os->getSlevy();
        $out = "";

        $out .= "   <table class='order order-slevy'>";
        $out .= "       <colgroup><col width='49%'><col width='12%'><col width='7%'><col width='9%'><col width='3%'><col width='20%'></colgroup>";
        $out .= "       <tr><th>Slevy</th><th class='right'>Cena</th><th colspan='4' class='right'>Celkem</th></tr>";

        if (!is_null($slevy)) {
            foreach ($slevy as $s) {
                $slSess = CommonUtils::searchArrayOfObjects($slevySess, $s->id_cena, "getId", null);
                $pocet = is_null($slSess) ? "0" : $slSess->getPocet();
                $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $os->calcPocetNoci() : 1;
                $status = $this->viewSluzbaStatus($s, $pocet);

                $out .= "       <tr>";
                $out .= "           <td>$status$s->nazev_ceny</td>";
                $out .= "           <td class='right'><span class='price-one'>" . CommonUtils::formatPrice($s->castka) . "</span> Kč</td>";
                $out .= "           <td class='center'>" . ($pocetNoci != 1 ? "&times;<span class='pocet-noci'>$pocetNoci</span>" : "") . "&times;</td>";
                $out .= "           <td><div class='i-num'><input type='text' value='$pocet' name='sleva-$s->id_cena' size='2' /><div class='p-sign'>+</div><div class='m-sign'>-</div><div class='clearfix'></div></div></td>";
                $out .= "           <td>&#x0003D;</td>";
                $out .= "           <td class='price right'><span class='price-full'>0</span> Kč</td>";
                $out .= "       </tr>";
            }
        }

        $out .= "   </table>";

        return $out;
    }

    private function zajezdOdjezdoveMisto()
    {
        $odjezdovaMista = $this->model->getZajezd()->getSluzbaHolder()->getOdjezdovaMista();
        $os = $this->model->getObjednavkaSession();
        $odjezdovaMistaSess = $os->getOdjezdovaMista();
        $errOdjezdoveMisto = MessagesEnt::hasErrMsg(MessageEnt::ID_ZAJEZD_ODJEZDOVE_MISTO) ? "display-ib" : "no-display";
        $out = "<input type=\"hidden\" id=\"typDopravy\" value=\"".$this->model->getZajezd()->serial->typ_dopravy."\" />";

        $out .= "   <table class='order order-odjezd'>";
        $out .= "       <colgroup><col width='49%'><col width='12%'><col width='7%'><col width='9%'><col width='3%'><col width='20%'></colgroup>";
        $out .= "       <tr><th>Odjezdová místa<div id='odjezd-val' class='$errOdjezdoveMisto val-err'>Vyberte odjezdové místo</div></th><th class='right'>Cena</th><th colspan='4' class='right'>Celkem</th></tr>";

        if (!is_null($odjezdovaMista)) {
            foreach ($odjezdovaMista as $s) {
                $omSess = CommonUtils::searchArrayOfObjects($odjezdovaMistaSess, $s->id_cena, "getId", null);
                $pocet = is_null($omSess) ? "0" : $omSess->getPocet();
                $pocetNoci = $s->use_pocet_noci == SluzbaEnt::USE_POCET_NOCI_YES ? $os->calcPocetNoci() : 1;
                $status = $this->viewSluzbaStatus($s, $pocet);

                $out .= "       <tr>";
                $out .= "           <td>$status$s->nazev_ceny</td>";
                $out .= "           <td class='right'><span class='price-one'>" . CommonUtils::formatPrice($s->castka) . "</span> Kč</td>";
                $out .= "           <td class='center'>" . ($pocetNoci != 1 ? "&times;<span class='pocet-noci'>$pocetNoci</span>" : "") . "&times;</td>";
                $out .= "           <td><div class='i-num'><input type='text' value='$pocet' name='odjezdove-misto-$s->id_cena' size='2' /><div class='p-sign'>+</div><div class='m-sign'>-</div><div class='clearfix'></div></div></td>";
                $out .= "           <td>&#x0003D;</td>";
                $out .= "           <td class='price right'><span class='price-full'>0</span> Kč</td>";
                $out .= "       </tr>";
            }
        }

        $out .= "   </table>";

        return $out;
    }

    private function zajezdCasoveSlevy()
    {
        $zajezd = $this->model->getZajezd();
        $casoveSlevy = $zajezd->getCasoveSlevy();

        $out = "";

        $out .= "   <table class='order order-slevy' id='casove-slevy'>";
        $out .= "       <colgroup><col width='49%'><col width='12%'><col width='7%'><col width='9%'><col width='3%'><col width='20%'></colgroup>";
        $out .= "       <tr><th>Dlouhodobé Slevy</th><th class='right'>Cena</th><th colspan='4' class='right'>Celkem</th></tr>";

        if (!is_null($casoveSlevy)) {
            foreach ($casoveSlevy as $s) {
                $out .= "       <tr>";
                $out .= "           <td>$s->nazev_slevy</td>";
                $out .= "           <td class='right'><span class='price-one'>" . CommonUtils::formatPrice($s->castka * -1) . "</span> <span class='price-currency'>$s->mena</span></td>";
                $out .= "           <td class='center'>&times;</td>";
                $out .= "           <td>1</td>";
                $out .= "           <td>&#x0003D;</td>";
                $out .= "           <td class='price right'><span class='price-full'>" . CommonUtils::formatPrice($s->castka * -1) . "</span> Kč</td>";
                $out .= "       </tr>";
            }
        }

        $out .= "   </table>";

        return $out;
    }

    private function zajezdCenaCelkem()
    {
        $out = "";

        $out .= "   <table class='order order-total-price'>";
        $out .= "       <tr>";
        $out .= "           <td class='lbl'>Celková cena</td>";
        $out .= "           <td class='price right'><span class='price-total'>0</span> Kč</td>";
        $out .= "       </tr>";
        $out .= "   </table>";

        return $out;
    }

    private function buttons()
    {
        $out = "";

        $out .= "   <div id='order-btns'>";
        $out .= "       <a class='btn-round btn-medium btn-green btn-arrow-r-white-medium float-right' id='btn-s1-vybrat' href=''>Vybrat</a>";
        $out .= "   </div>";

        return $out;
    }

    private function termin()
    {
        $zajezd = $this->model->getZajezd();
        $os = $this->model->getObjednavkaSession();
        $terminZajezd = CommonUtils::czechDate($zajezd->terminOd) . " - " . CommonUtils::czechDate($zajezd->terminDo);
        $termin = $os->getTermin();
        $errTermin = MessagesEnt::hasErrMsg(MessageEnt::ID_ZAJEZD_TERMIN) ? "display-ib" : "no-display";
        $out = "";

        if ($zajezd->isDlouhodoby()) {
            $out .= "<div class='row off-b-7'>";
            $out .= "   <label class='text'>Období: </label><span>$terminZajezd</span>";
            $out .= "</div>";
            $out .= "<div class='row'>";
            $out .= "   <label class='text'>Upřesnit termín: </label>";
            $out .= "   <input type='text' name='termin' class='float-left' id='datepicker' autocomplete='off' readonly value='$termin'/><span class='calendar-ico float-left btn-black'></span><div id='datepicker-val' class='$errTermin val-err float-left'>Vyberte termín</div><span class='clearfix'></span>";
            $out .= "</div>";
        } else {
            $out .= "<div class='row'>";
            $out .= "   <label class='text'>Termín: </label>";
            $out .= "   <span>$terminZajezd</span><input type='hidden' name='termin' value='$terminZajezd'/>";
            $out .= "</div>";
        }

        return $out;
    }
}