<?php


class PlatebniDokladViewEdit extends PlatebniDokladView implements IPlatebniDokladModelEditObserver
{

    /**
     * @var PlatebniDokladModel
     */
    private $model;

    /**
     * @param $model PlatebniDokladModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerEditObserver($this);
    }

    public function modelEditChanged()
    {
        echo $this->head();
        echo $this->main();
        echo $this->foot();
    }

    // PRIVATE METHODS *******************************************************************

    private function head()
    {
        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(PlatebniDokladModel::$core->show_all_allowed_moduls()), PlatebniDokladModel::$zamestnanec, PlatebniDokladModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(PlatebniDokladModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();

        $out .= $this->formHead();

        $out .= $this->heading();
        $out .= $this->obecneInfo();
        $out .= $this->klienti();
        $out .= $this->platby();

        $out .= $this->formFoot();

        $out .= $this->pdfEmailActions();
        $out .= $this->emaily();
        $out .= $this->pdf();


        return $out;
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a href='#' id='btn-generuj-pdf' target='_blank'>vygenerovat pdf</a>";
        $out .= "</div>";

        return $out;
    }

    private function klienti()
    {
        $out = "";

        $out .= "<h4>Vystavit klientovi</h4>";
        $out .= "   <table class='list'>";
        $out .= "       <tr><th></th><th>Typ</th><th>Jméno</th><th>Telefon</th><th>Email</th><th>Adresa</th></tr>";

        $out .= $this->objednavajici();
        $out .= $this->ucastnici();
        $out .= $this->agentura();

        $out .= "   </table>";

        return $out;
    }

    private function obecneInfo()
    {
        $zajezd = $this->model->getZajezd();
        $objednavka = $this->model->getObjednavka();
        $out = "";

        $out .= "<h4>Obecné informace zájezdu</h4>";
        $out .= "<div class='form_row'>";
        $out .= "   <div class='label_float_left'>Název:</div>";
        $out .= "   <div class='value'>" . $zajezd->constructNazev() . "</div>";
        $out .= "</div>";
        $out .= "<div class='form_row'>";
        $out .= "   <div class='label_float_left'>Termín:</div>";
        $out .= "   <div class='value'>" . PlatebniDokladUtils::czechDate($objednavka->termin_od) . " - " . PlatebniDokladUtils::czechDate($objednavka->termin_do) . "</div>";
        $out .= "</div>";
        $out .= "<div class='form_row'>";
        $out .= "   <div class='label_float_left'>Poèet osob:</div>";
        $out .= "   <div class='value'>" . $objednavka->pocet_osob . "</div>";
        $out .= "</div>";
        $out .= "<div class='form_row'>";
        $out .= "   <div class='label_float_left'>Datum rezervace:</div>";
        $out .= "   <div class='value'>" . PlatebniDokladUtils::czechDateTime($objednavka->datum_rezervace) . "</div>";
        $out .= "</div>";

        return $out;
    }

    private function platby()
    {
        $platby = $this->model->getPlatby();
        $objednavka = $this->model->getObjednavka();
        $out = "";

        if (is_null($platby))
            return $out;

        $out .= "<h4>Platby</h4>";
        $out .= "<table class='list'>";
        $out .= "   <th>Název platby</th><th>Èástka</th>";

        foreach ($platby as $p) {
            $out .= "<tr class='selectable'>";
            $out .= "   <td>Platba za objednávku èíslo $objednavka->id_objednavka</td>";
            $out .= "   <td>$p->castka</td>";
            $out .= "</tr>";
        }

        $out .= "</table>";

        return $out;
    }

    private function objednavajici()
    {
        $objednavka = $this->model->getObjednavka();
        $objednavajici = $objednavka->objednavajici;
        $objednavajiciOrg = $objednavka->objednavajiciOrg;
        $out = "";

        if (is_null($objednavajiciOrg)) {
            $out .= "   <tr class='selectable important'>";
            $out .= "       <td><input type='radio' name='klient' checked='checked' value='" . self::REQUEST_PREFIX_OBJEDNAVAJICI . "$objednavajici->id'/></td>";
            $out .= "       <td>objednavající</td>";
            $out .= "       <td>$objednavajici->jmeno $objednavajici->prijmeni</td>";
            $out .= "       <td>$objednavajici->telefon</td>";
            $out .= "       <td>$objednavajici->email</td>";
            $out .= "       <td>$objednavajici->adresa_ulice, $objednavajici->adresa_mesto, $objednavajici->adresa_psc</td>";
            $out .= "   </tr>";
        } else {
            $adresa = $objednavajiciOrg->adresa;
            $out .= "   <tr class='selectable important'>";
            $out .= "       <td><input type='radio' name='klient' checked='checked' value='" . self::REQUEST_PREFIX_OBJEDNAVAJICI . "$objednavajiciOrg->id'/></td>";
            $out .= "       <td>objednavající</td>";
            $out .= "       <td>$objednavajiciOrg->nazev</td>";
            $out .= "       <td>$objednavajiciOrg->telefon</td>";
            $out .= "       <td>$objednavajiciOrg->email</td>";
            $out .= "       <td>$adresa->ulice, $adresa->mesto, $adresa->psc</td>";
            $out .= "   </tr>";
        }

        return $out;
    }

    private function ucastnici()
    {
        $objednavka = $this->model->getObjednavka();
        $ucastnici = $objednavka->ucastnici;
        $out = "";

        foreach ($ucastnici as $u) {
            $adresaOut = $u->adresa->ulice . ", " . $u->adresa->mesto . ", " . $u->adresa->psc;
            $out .= "   <tr class='selectable'>";
            $out .= "       <td><input type='radio' name='klient' value='" . self::REQUEST_PREFIX_UCASTNIK . "$u->id'/></td>";
            $out .= "       <td>úèastník</td>";
            $out .= "       <td>$u->jmeno $u->prijmeni</td>";
            $out .= "       <td>$u->telefon</td>";
            $out .= "       <td>$u->email</td>";
            $out .= "       <td>$adresaOut</td>";
            $out .= "   </tr>";
        }

        return $out;
    }

    private function agentura()
    {
        $objednavka = $this->model->getObjednavka();
        $prodejce = $objednavka->prodejce;
        $out = "";

        if (!is_null($prodejce)) {
            $adresaOut = $prodejce->adresa_ulice . ", " . $prodejce->adresa_mesto . ", " . $prodejce->adresa_psc;
            $out .= "   <tr class='selectable'>";
            $out .= "       <td><input type='radio' name='klient' value='" . self::REQUEST_PREFIX_PRODEJCE . "$prodejce->id'/></td>";
            $out .= "       <td>agentura</td>";
            $out .= "       <td>$prodejce->nazev</td>";
            $out .= "       <td>$prodejce->telefon</td>";
            $out .= "       <td>$prodejce->email</td>";
            $out .= "       <td>$adresaOut</td>";
            $out .= "   </tr>";
        }

        return $out;
    }

    private function formHead()
    {
        $objednavka = $this->model->getObjednavka();
        $out = "";

        $out .= "<form id='frm-platebni-doklad' method='post' target='_blank' action='?page=create-pdf&id_objednavka=$objednavka->id_objednavka'>";

        return $out;
    }

    private function formFoot()
    {
        $out = "";

        $out .= "</form>";

        return $out;
    }

    private function heading()
    {
        $objednavka = $this->model->getObjednavka();
        $out = "";

        $out .= "<h3>Editace platebního dokladu objednávky [ $objednavka->id_objednavka ] </h3>";

        return $out;
    }

    private function pdfEmailActions()
    {
        $platbyPdf = $this->model->getPlatbyPdf();
        $out = "";

        if (is_null($platbyPdf))
            return null;

        $out .= "<div class='list-add'>";
        $out .= "   <div id='email-status'></div>";
        $out .= "   <div class='submenu'><a class='btn btn-action' id='btn-send-pdf' href='#'>odeslat pdf</a></div>";

        return $out;
    }

    private function emaily()
    {
        $platbyPdf = $this->model->getPlatbyPdf();
        $objednavka = $this->model->getObjednavka();
        $objednavajici = $objednavka->objednavajici;
        $ucastnici = $objednavka->ucastnici;
        $prodejce = $objednavka->prodejce;

        if (is_null($platbyPdf))
            return null;

        $out = "";

        $out .= "<form target='_blank' id='form-send-pdf' method='post' action='?page=send-pdf'>";
        $out .= "   <h4>Odeslat pdf na email</h4>";
        $out .= "   <table class='list' id='tbl-emaily'>";
        $out .= "       <tr><th></th><th>email</th><th>role</th></tr>";

        if (!is_null($objednavajici)) {
            $out .= "   <tr class='selectable'><td><input type='checkbox' name='cb-emaily[]' value='$objednavajici->email' /></td><td>$objednavajici->email</td><td>objednávající</td></tr>";
        }

        if (!is_null($ucastnici)) {
            foreach ($ucastnici as $u) {
                if($u->email == "")
                    continue;
                $out .= "<tr class='selectable'><td><input type='checkbox' name='cb-emaily[]' value='$u->email' /></td><td>$u->email</td><td>úèastník</td></tr>";
            }
        }

        if (!is_null($prodejce)) {
            $out .= "   <tr class='selectable'><td><input type='checkbox' name='cb-emaily[]' value='$prodejce->email' /></td><td>$prodejce->email</td><td>prodejce</td></tr>";
        }

        $out .= "       <tr class='edit'>";
        $out .= "           <td></td>";
        $out .= "           <td><input type='text' name='new-email' value='' /></td>";
        $out .= "           <td><a id='btn-add-email' href=''>pøidat email</a></td>";
        $out .= "       </tr>";

        $out .= "   </table>";

        return $out;
    }

    private function pdf()
    {
        $platbyPdf = $this->model->getPlatbyPdf();
        $objednavka = $this->model->getObjednavka();

        if (is_null($platbyPdf))
            return null;

        $out = "";

        $out .= "   <h4>Výbìr pdf k odeslání</h4>";
        $out .= "   <table class='list' id='tbl-pdf-voucher'>";
        $out .= "       <tr><th></th><th>datum & èas</th><th>soubor</th><th></th></tr>";

        //prvni je defaultne vybrany
        $checked = "checked='checked'";
        foreach ($platbyPdf as $filePath) {
            $filePathDump = explode("_", explode(".", $filePath)[0]);
            $dateTime = $filePathDump[1];
            $dateTime = date("d.m.Y G:i:s", strtotime(PlatebniDokladUtils::refractorDate($dateTime)));

            $out .= "   <tr>";
            $out .= "       <td><input type='radio' name='rb-pdf-voucher' $checked value='$filePath' /></td>";
            $out .= "       <td>$dateTime</td>";
            $out .= "       <td><a target='_blank' href='" . PlatebniDokladModelConfig::PDF_FOLDER . "$filePath'>platebni-doklad.pfd</a></td>";
            $out .= "       <td class='edit'>";
            $out .= "           <a class='confirm-delete' href='?page=delete-pdf&id_objednavka=$objednavka->id_objednavka&pdf_filename=$filePath'>";
            $out .= "               <img width='10' src='./img/delete-cross.png'/>";
            $out .= "           </a>";
            $out .= "       </td>";
            $out .= "   </tr>";

            $checked = "";
        }

        $out .= "       <tr class='edit'>";
        $out .= "           <td colspan='4'>";
        $out .= "               <a class='confirm-delete anchor-delete' href='?page=delete-all-pdf&id_objednavka=$objednavka->id_objednavka'>smazat všechna pdf</a>";
        $out .= "           </td>";
        $out .= "       </tr>";

        $out .= "   </table>";
        $out .= "</form>";
        $out .= "</div>";

        return $out;
    }
}