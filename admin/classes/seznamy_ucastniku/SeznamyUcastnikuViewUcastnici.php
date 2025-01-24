<?php

class SeznamyUcastnikuViewUcastnici extends SeznamyUcastnikuView implements ISeznamyUcastnikuModelUcastniciObserver
{
    /**
     * @var SeznamyUcastnikuModel
     */
    private $model;

    /**
     * @param $model SeznamyUcastnikuModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerUcastniciObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelUcastniciChanged()
    {
        $this->ucastnici();
    }

    // PRIVATE METHODS *******************************************************************
    private function ucastnici()
    {
        echo $this->head();
        echo $this->ucastniciMain();
        echo $this->foot();
    }

    private function head()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(SeznamyUcastnikuModel::$core->show_all_allowed_moduls()), SeznamyUcastnikuModel::$zamestnanec, SeznamyUcastnikuModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        if (is_null($serialy))
            $out .= "<h2 class='red'>Nenalezeny žádné záznamy vyhovující podmínkám.</h2>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(SeznamyUcastnikuModel::$core->show_current_modul()["napoveda"]);;

        $out .= self::htmlFoot();

        return $out;
    }

    private function ucastniciMain()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->ucastniciFilter();
        $out .= $this->ucastniciList();
        $out .= $this->pdfEmailActions();
        $out .= $this->ucastniciEmaily();
        $out .= $this->pdfUcastnici();

        return $out;
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a class='btn btn-action' href='seznamy_ucastniku.php?page=zajezdy'><< seznam zájezdù</a>";
        $out .= "   <a class='btn btn-action' href='' id='btn-generuj-pdf'>vygenerovat pdf</a>";
        $out .= "</div>";

        return $out;
    }

    private function ucastniciFilter()
    {
        $filterValues = $this->model->getSerialFilter();
        $zajezdSetup = $filterValues->getZajezdSetup();
        $objednavkaSetup = $filterValues->getObjednavkaSetup();
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = "";

        if (!is_null($serialy)) {
            //form konci v $this->ucastniciList()
            $out .= "<form method='post' action='seznamy_ucastniku.php?page=create-pdf' id='form-ucastnici' target='_blank'>";

            $out .= "<table class='filtr float-left width-20 delim-right'>";
            $out .= "   <tr><th class='header header-blue align-left'>Nejèastìjší filtry:</th></tr>";
            $out .= "   <tr class='height-40'>";
            $out .= "       <td>";
            $out .= "           <select id='cbFilterUcastniciSetup'>";
            $out .= "               <option value=''>-- výbìr filtru --</option>";
            $out .= "               <option value='da-te-sl-na'>dat. nar., telefon, služby, nastupní místo</option>";
            $out .= "               <option value='ro-ci-te-na'>rè, pas, telefon, nastupní místo</option>";
            $out .= "               <option value='ti-da-ro-ci-ad-te-em-id-sl-ob-pr-na'>vše</option>";
            $out .= "           </select>";
            $out .= "       </td>";
            $out .= "   </tr>";
            $out .= "</table>";
            $out .= "<table class='filtr float-left width-50 delim-right'>";
            $out .= "   <tr><th colspan='7' class='header header-blue align-left'>Zobrazit údaje úèastníkù:</th></tr>";
            $out .= "   <tr class='height-40'>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->titul ? "checked='checked'" : "") . " name='cb-f-zaj-titul' value='titul' /> titul</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->datumNarozeni ? "checked='checked'" : "") . " name='cb-f-zaj-datum-narozeni' value='datum narození' /> datum narození</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->rodneCislo ? "checked='checked'" : "") . " name='cb-f-zaj-rodne-cislo' value='rodné èíslo' /> rodné èíslo</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->cisloPasu ? "checked='checked'" : "") . " name='cb-f-zaj-cislo-pasu' value='èíslo pasu' /> èíslo pasu</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->adresa ? "checked='checked'" : "") . " name='cb-f-zaj-adresa' value='adresa' /> adresa</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->telefon ? "checked='checked'" : "") . " name='cb-f-zaj-telefon' value='telefon' /> telefon</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($zajezdSetup->email ? "checked='checked'" : "") . " name='cb-f-zaj-email' value='email' /> email</label></td>";
            $out .= "   </tr>";
            $out .= "</table>";
            $out .= "<table class='filtr float-left width-30'>";
            $out .= "   <tr><th colspan='4'>Zobrazit údaje objednávek:</th></tr>";
            $out .= "   <tr class='height-40'>";
            $out .= "       <td class=\"red\"><label><input type='checkbox' " . ($objednavkaSetup->nezobrazovat_objednavky ? "checked='checked'" : "") . " name='cb-f-obj-nezobrazovat' value='nezobrazovat' /> Nezobrazovat objednávky!</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($objednavkaSetup->id ? "checked='checked'" : "") . " name='cb-f-obj-id' value='id' /> id</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($objednavkaSetup->sluzby ? "checked='checked'" : "") . " name='cb-f-sl-sluzby' value='sluzby' /> služby</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($objednavkaSetup->objednavajici ? "checked='checked'" : "") . " name='cb-f-obj-objednavajici' value='objednávající' /> objednávající</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($objednavkaSetup->prodejce ? "checked='checked'" : "") . " name='cb-f-obj-prodejce' value='prodejce' /> prodejce</label></td>";
            $out .= "       <td><label><input type='checkbox' " . ($objednavkaSetup->nastupniMisto ? "checked='checked'" : "") . " name='cb-f-obj-nastupni-misto' value='nástupní' /> nástupní místo</label></td>";
            $out .= "   </tr>";
            $out .= "</table>";
            $out .= "<div class='clearfix'></div>";
        }

        return $out;
    }

    private function ucastniciList()
    {
        $filterValues = $this->model->getSerialFilter();
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = "";

        if (!is_null($serialy)) {
            $out .= "   <h3>Seznam seriálù, jejich zájezdù a úèastníkù</h3>";
            $out .= "   <table class='list' id='tbl-ucastnici'>";

            $out .= $this->serialyLoop($serialy);

            $out .= "   </table>";

            $out .= "   <input type='hidden' name='cb-serialy' value='" . $filterValues->getSerialIdsAsString() . "' />";
            $out .= "   <input type='hidden' name='cb-zajezdy' value='" . $filterValues->getZajezdIdsAsString() . "' />";
            $out .= "   <input type='hidden' name='filter-skryt-prosle' value='" . $filterValues->getZajezdSkrytProsle() . "' />";
            $out .= "   <input type='hidden' name='filter-novejsi-nez' value='" . $filterValues->getZajezdNovejsiNez() . "' />";

            $out .= "</form>";
        }

        return $out;
    }

    private function serialyLoop($serialy)
    {
        $out = "";

        foreach ($serialy as $serial) {
            $zajezdy = $serial->zajezdy;
            if (!is_null($zajezdy)) {
                $out .= "   <tr>";
                $out .= "       <th colspan='11' class='superheader'>Seriál: $serial->nazev</th>";
                $out .= "   </tr>";

                $out .= $this->zajezdyLoop($zajezdy);
            }
        }

        return $out;
    }

    /**
     * @param $zajezdy tsZajezd[]
     * @return string
     */
    private function zajezdyLoop($zajezdy)
    {
        $out = "";

        foreach ($zajezdy as $zajezd) {
            if (!$zajezd->hasObjednavky())
                continue;
            
            $objednavky = $zajezd->objednavky;

            $out .= "<tr>";
            $out .= "   <th colspan='11'>";
            $out .= "Zájezd: $zajezd->id" . ($zajezd->nazevUbytovani == "" ? "" : ", $zajezd->nazevUbytovani") .
                " [ " . CommonUtils::czechDate($zajezd->terminOd) .
                " - " . CommonUtils::czechDate($zajezd->terminDo) . " ]";
            $out .= "   </th>";
            $out .= "</tr>";

            $out .= $this->objednavkyLoop($objednavky, $zajezd->id);
        }

        return $out;
    }

    /**
     * @param $objednavky tsObjednavka[]
     * @param $zajezdId
     * @return string
     */
    private function objednavkyLoop($objednavky, $zajezdId)
    {
        $out = "";

        if (!is_null($objednavky)) {
            foreach ($objednavky as $objednavka) {
                $sluzby = $objednavka->sluzby;
                $ucastnici = $objednavka->ucastnici;
                $objednavajici = $objednavka->objednavajici;
                $prodejce = $objednavka->prodejce;

                $out .= "<tr class='removable-obj'>";
                $out .= "   <th class='header header-violet align-left'></th>";
                $out .= "   <th class='header header-violet align-left' colspan='6'><input title=\"Nezobrazovat tuto objednávku a její úèastníky\" class=\"cb-deselect-obj\" type='checkbox' name='cb-objednavky[]' value='$objednavka->id_objednavka' checked='checked' /> Objednávka</th>";
                $out .= "   <th class='header header-violet align-center'>id: $objednavka->id_objednavka</th>";
                $out .= "   <th class='header header-violet align-left'>objednávající: $objednavajici->jmeno $objednavajici->prijmeni</th>";
                $out .= "   <th class='header header-violet align-left'>prodejce: $prodejce->nazev</th>";
                $out .= "   <th class='header header-violet align-left'>nástupní místo: ";

                //vypis nastupni mista
                if (!is_null($objednavka->nastupni_mista)) {
                    foreach ($objednavka->nastupni_mista as $nm)
                        $out .= "$nm, ";
                    $out = substr($out, 0, -2);
                }

                $out .= "   </th>";
                $out .= "</tr>";

                $out .= "<tr class='removable-sl'>";
                $out .= "   <th></th><th>poèet</th><th>id</th><th>název</th><th>èástka</th><th>mìna</th><th colspan='5'></th>";
                $out .= "</tr>";

                $out .= $this->sluzbyLoop($sluzby, $zajezdId);

                $out .= "<tr class='removable-zaj'>";
                //Lada:zmeneno neintuitivni chovani checkboxu objednavky: pokud se odskrtne, informace o objednavce se nezobrazi v seznamu PDF - u objednavky
                //zde tlacitko preprogramovano tak, aby odskrtnulo pouze ucastniky na dane objednavce, ne vsechny
                        /*      $out .= "   <th></th><th><input type='checkbox' class='check-all' value='$zajezdId' checked='checked' /></th><th>id</th><th>jméno</th><th>titul</th><th>datum narození</th>*/
 
                $out .= "   <th></th><th><input title=\"Zaškrtnout/odškrtnout všechny úèastníky z této objednávky\" type='checkbox' id='check-all-ucastnici-objednavka-$objednavka->id_objednavka' class='check-all-ucastnici-objednavka' value='$objednavka->id_objednavka' checked='checked' /></th><th>id</th><th>jméno</th><th>titul</th><th>datum narození</th>
                            <th>rodné èíslo</th><th>èíslo pasu</th><th>adresa</th><th>telefon</th><th>email</th>";
                $out .= "</tr>";

                $out .= $this->ucastniciLoop($ucastnici, $zajezdId, $objednavka->id_objednavka);
            }
        }

        return $out;
    }

    /**
     * @param $sluzby tsSluzba[]
     * @return string
     */
    private function sluzbyLoop($sluzby)
    {
        $out = "";

        if (!is_null($sluzby)) {
            foreach ($sluzby as $s) {
                $out .= "<tr class='removable-sl'>";
                $out .= "   <td></td>";
                $out .= "   <td class='align-center'>$s->pocet</td>";
                $out .= "   <td class='align-center'>$s->id_cena</td>";
                $out .= "   <td>$s->nazev_ceny</td>";
                $out .= "   <td>$s->castka</td>";
                $out .= "   <td>$s->mena</td>";
                $out .= "   <td colspan='5'></td>";
                $out .= "</tr>";
            }
        }

        return $out;
    }

    private function ucastniciLoop($ucastnici, $zajezdId, $objednavkaId = "")
    {
        $out = "";

        if (!is_null($ucastnici)) {
            foreach ($ucastnici as $ucastnik) {
                $adr = $ucastnik->adresa;

                $out .= "<tr class='selectable removable-zaj'>";
                $out .= "   <td class='align-center'></td>";
                $out .= "   <td class='align-center'><input type='checkbox' class='$zajezdId ucastnik_objednavky_$objednavkaId' name='cb-ucastnici[]' value='$ucastnik->id' checked='checked' /></td>";
                $out .= "   <td class='align-center'>$ucastnik->id</td>";
                $out .= "   <td>$ucastnik->prijmeni, $ucastnik->jmeno</td>";
                $out .= "   <td>" . ($ucastnik->titul == "" ? "" : ", $ucastnik->titul") . "</td>";
                $out .= "   <td class='align-center'>" . CommonUtils::czechDate($ucastnik->datum_narozeni) . "</td>";
                $out .= "   <td class='align-center'>$ucastnik->rodne_cislo</td>";
                $out .= "   <td class='align-center'>$ucastnik->cislo_pasu</td>";
                $out .= "   <td>" . ($adr->ulice != "" ? "$adr->mesto, $adr->psc, $adr->ulice" : "") . "</td>";
                $out .= "   <td class='align-center'>$ucastnik->telefon</td>";
                $out .= "   <td>$ucastnik->email</td>";
                $out .= "</tr>";
            }
        }

        return $out;
    }

    private function ucastniciEmaily()
    {
        $pdfUcastnici = $this->model->getPdfUcastnici();
        $zamestnanecEmail = SeznamyUcastnikuModel::$zamestnanec->get_email();

        if (is_null($pdfUcastnici))
            return null;

        $out = "";

        $out .= "<form target='_blank' id='form-send-pdf' method='post' action='vouchery_objednavka.php?page=send-pdf'>";
        $out .= "   <h4>Odeslat pdf na email</h4>";
        $out .= "   <table class='list' id='tbl-emaily'>";
        $out .= "       <tr><th></th><th>email</th><th>role</th></tr>";

        //todo proc tu mam count? chyba?
        if (count((array)$zamestnanecEmail) != "") {
            $out .= "   <tr>";
            $out .= "       <td><input type='checkbox' name='cb-emaily[]' value='$zamestnanecEmail' /></td>";
            $out .= "       <td class='email'>$zamestnanecEmail</td>";
            $out .= "       <td>zamìstnanec</td>";
            $out .= "   </tr>";
        }

        $out .= "   <tr class='edit'>";
        $out .= "       <td></td>";
        $out .= "       <td><input type='text' name='new-email' value='' /></td>";
        $out .= "       <td><a id='btn-add-email' href=''>pøidat email</a></td>";
        $out .= "   </tr>";

        $out .= "   </table>";

        return $out;
    }

    private function pdfUcastnici()
    {
        $pdfUcastnici = $this->model->getPdfUcastnici();

        if (is_null($pdfUcastnici))
            return null;

        $out = "";

        $out .= "   <h4>Výbìr pdf k odeslání</h4>";
        $out .= "   <table class='list' id='tbl-pdf-voucher'>";
        $out .= "       <tr><th></th><th>datum & èas</th><th>soubor</th><th></th></tr>";

        //prvni je defaultne vybrany
        $checked = "checked='checked'";
        foreach ($pdfUcastnici as $filePath) {
            $filePathDump = explode("_", explode(".", $filePath)[0]);
            $hash = $filePathDump[0];
            $dateTime = $filePathDump[1];
            $dateTime = date("d.m.Y G:i:s", strtotime(CommonUtils::refractorDateTime($dateTime)));
            $out .= "   <tr>";
            $out .= "       <td><input type='radio' name='rb-pdf-voucher' $checked value='$filePath' /></td>";
            $out .= "       <td>$dateTime</td>";
            $out .= "       <td><a target='_blank' href='" . SeznamyUcastnikuModelConfig::PDF_FOLDER . "$filePath'>seznam-ucastniku.pfd</a></td>";
            $out .= "       <td class='edit'>";
            $out .= "           <a class='confirm-delete' href='?page=delete-pdf&pdf_filename=$filePath'>";
            $out .= "               <img width='10' src='./img/delete-cross.png'/>";
            $out .= "           </a>";
            $out .= "       </td>";
            $out .= "   </tr>";
            $checked = "";
        }

        $out .= "       <tr class='edit'>";
        $out .= "           <td colspan='4' class='align-right'>";
        //todo zmenit odkaz - proc? uz si nepamatuju
        $out .= "               <a class='confirm-delete anchor-delete' href='?page=delete-all-pdf&hash=$hash'>smazat všechna pdf</a>";
        $out .= "           </td>";
        $out .= "       </tr>";

        $out .= "   </table>";
        $out .= "   <div class='clearfix'></div>";
        $out .= "</form>";
        $out .= "</div>";

        return $out;
    }

    private function pdfEmailActions()
    {
        $pdfUcastnici = $this->model->getPdfUcastnici();

        $out = "";

        if (!is_null($pdfUcastnici)) {
            $out .= "<div class='list-add'>";
            $out .= "   <div id='email-status'></div>";
            $out .= "   <div class='submenu'><a class='btn btn-action' id='btn-send-pdf' href='#'>odeslat pdf</a></div>";
        }

        return $out;
    }
}
