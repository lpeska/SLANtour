<?php

class VoucheryViewEdit implements IVoucheryModelEditObserver
{
    /**
     * @var VoucheryModel
     */
    private $model;

    /**
     * @param $model VoucheryModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerEditObserver($this);
    }

    // PUBLIC METHODS ********************************************************************
    //todo: tahle metoda by mela byt v samostatnem view, ale kvuli 1 metode jsem nove nedelal, kdyz se jich objevi vic, muze se vytvborit dalsi view
    public static function loginErr()
    {
        VoucheryUtils::redirect("http://test.slantour.cz/admin/index.php?typ=logout");
    }

    private static function htmlHead()
    {
        $out = "";

        $out .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";
        $out .= "<html>";
        $out .= "   <head>";
        $out .= "       <title>" . VoucheryModel::$core->show_nazev_modulu() . " | Administrace systému RSCK</title>";
        $out .= "       <meta http-equiv='Content-Type' content='text/html; charset=windows-1250'/>";
        $out .= "       <meta name='copyright' content='&copy; Slantour'/>";
        $out .= "       <meta http-equiv='pragma' content='no-cache' />";
        $out .= "       <meta name='robots' content='noindex,noFOLLOW' />";
        $out .= "       <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='css/reset-min.css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='./new-menu/style.css' media='all'/>";
        $out .= "       <script type='text/javascript' src='./js/jquery-min.js'></script>";
        $out .= "       <script type=\"text/javascript\" src=\"./js/jquery-ui-cze.min.js\"></script>";
        $out .= "     <link type=\"text/css\" href=\"./css/jquery-ui.min.css\" rel=\"stylesheet\" />";
        $out .= "       <script type='text/javascript' src='./classes/vouchery/js/vouchery.js'></script>";
        $out .= "       <script type='text/javascript' src='./js/common_functions.js'></script>";
        //$out .= "       <script language='JavaScript' type='text/javascript' src='./whizz/whizzywig63.js'></script>";
        $out .= "   </head>";
        $out .= "   <body>";

        return $out;
    }

    private static function htmlFoot()
    {
        $out = "";

        $out .= "</body></html>";

        return $out;
    }

    public function modelEditChanged()
    {
        $this->objEdit();
    }

    // PRIVATE METHODS *******************************************************************
    private function objEdit()
    {
        echo $this->head();
        echo $this->editMain();
        echo $this->foot();
    }

    private function head()
    {
        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(VoucheryModel::$core->show_all_allowed_moduls()), VoucheryModel::$zamestnanec, VoucheryModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(VoucheryModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function editMain()
    {
        $out = "";

        $out .= $this->objednavkaHeader();
        $out .= $this->objednavkaInfo();
        $out .= $this->sluzby();
        $out .= $this->objekty();
        $out .= $this->cenaZahrnuje();
        $out .= $this->jazykPoznamka();

        $out .= $this->pdfEmailActions();
        $out .= $this->emaily();
        $out .= $this->emailText();
        $out .= $this->pdfVoucher();

        return $out;
    }

    private function objednavkaHeader()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a class='btn btn-action' href='rezervace.php?typ=rezervace_list'>zpìt</a>";
        $out .= "   <a class='btn btn-action' id='btn-generate-pdf-voucher' href='#'>vygenerovat pdf voucher</a>";
        $out .= "   <a class='btn btn-action' id='btn-generate-pdf-objednavka-objekt' href='#'>vygenerovat pdf objednávka objekt</a>";
        $out .= "</div>";

        if ($this->model->isObjednavkaObjekt())
            $out .= "<h3>Objekty</h3>";
        else
            $out .= "<h3>Zájezd</h3>";

        return $out;
    }

    private function objednavkaInfo()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $objednavka = $objednavkaHolder->getObjednavka();
        $objednavajici = $objednavkaHolder->getObjednavajici();
        $zajezd = $objednavkaHolder->getZajezd();
        $terminOd = VoucheryUtils::czechDate($zajezd->terminOd);
        $terminDo = VoucheryUtils::czechDate($zajezd->terminDo);

        $out = "";

        $out .= "<table class='list'>";
        $out .= "   <tr><th colspan='6' class='header header-blue align-left'>Info objednávky</th></tr>";
        $out .= "   <tr><th>id</th><th>zájezd</th><th>objednávající</th><th>datum rezervace</th><th>poèet nocí</th><th>poèet osob</th></tr>";
        $out .= "   <tr>";
        $out .= "       <td class='align-center'>";
        $out .= "           <a target='_blank' href='rezervace.php?id_objednavka=$objednavka->id_objednavka&typ=rezervace&pozadavek=show'>$objednavka->id_objednavka</a>";
        $out .= "       </td>";
        $out .= "       <td class='align-center'>";
        $out .= "           <a href='serial.php?id_serial=$zajezd->idSerial&id_zajezd=$zajezd->id&typ=zajezd&pozadavek=edit' target='_blank'>$zajezd->nazevSerialu</a>";
        $out .= "       </td>";
        $out .= "       <td class='align-center'>";
        $out .= "           <a href='klienti.php?id_klient=$objednavajici->id&typ=klient&pozadavek=edit' target='_blank'>$objednavajici->jmeno $objednavajici->prijmeni</a>";
        $out .= "       </td>";
        $out .= "       <td class='align-center'>$terminOd - $terminDo</td>";
        $out .= "       <td class='align-center'>$objednavka->pocet_noci</td>";
        $out .= "       <td class='align-center'>$objednavka->pocet_osob</td>";
        $out .= "   </tr>";
        $out .= "</table>";

        return $out;
    }

    private function sluzby()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $idObjednavka = $objednavkaHolder->getIdObjednavka();
        $securityCode = $objednavkaHolder->getSecurityCode();
        $sluzby = $objednavkaHolder->getSluzby();
        $ucastnici = $objednavkaHolder->getUcastnici();
        $out = $checked = "";

        $out .= "<form target='_blank' id='form-generate-pdf' method='post' action='vouchery_objednavka.php?page=create-pdf-voucher&id_objednavka=$idObjednavka&security_code=$securityCode'>";
        $out .= "   <table id='tbl-sluzby' class='list'>";
        $out .= "       <tr><th colspan='7' class='header header-green align-left'>Služby</th></tr>";
        $out .= "       <tr><th></th><th>id</th><th>název</th><th>èástka</th><th>objektové kategorie</th><th>poèet</th><th>osoby</th></tr>";
        foreach ($sluzby as $s) {
            $out .= "   <tr>";

            $out .= "       <td class='valign-mid align-center'><input type='checkbox' name='cb-sluzby[]' checked='on' value='$s->id_cena' /></td>";
            $out .= "       <td class='valign-mid align-center'>$s->id_cena</td>";
            $out .= "       <td class='valign-mid align-center'><input value='$s->nazev_ceny' class='inputText' name='sluzba-nazev-$s->id_cena'/></td>";
            $out .= "       <td class='valign-mid align-center'>$s->castka $s->mena</td>";

            $out .= "       <td class='no-space valign-top'>";
            if (count((array)$s->objektoveKategorie) > 0) {
                $out .= "           <table class='subtable hoverable'>";
                $checked = "checked='on'";
                for ($i = 0; $i < count((array)$s->objektoveKategorie); $i++) {
                    $ok = $s->objektoveKategorie[$i];
                    $out .= "           <tr>";
                    $out .= "               <td><input type='radio' name='rb-ok-$s->id_cena' $checked value='$i' /></td>";
                    $out .= "               <td>$ok->nazev</td>";
                    $out .= "           </tr>";
                    $checked = "";
                }
                $out .= "           </table>";
            }
            $out .= "       </td>";

            $out .= "       <td class='valign-mid align-center'><input class='smallNumber' value='$s->pocet' name='sluzba-pocet-$s->id_cena'/></td>";

            $out .= "       <td class='no-space'>";
            $out .= "           <table id='subtbl-osoby' class='subtable hoverable'>";
            if (count((array)$ucastnici) == $s->pocet) $checked = "checked='on'"; else $checked = "";
            for ($i = 0; $i < count((array)$ucastnici); $i++) {
                $out .= "           <tr>";
                $out .= "               <td><input type='checkbox' class='cb-osoba' name='cb-ucastnici-$s->id_cena[]' $checked value='" . $ucastnici[$i]->id . "' /></td>";
                $out .= "               <td><a target='_blank' href='klienti.php?id_klient=" . $ucastnici[$i]->id . "&typ=klient&pozadavek=edit'>" . $ucastnici[$i]->id . "</a></td>";
                $out .= "               <td>" . ucfirst($ucastnici[$i]->jmeno) . " " . ucfirst($ucastnici[$i]->prijmeni) . "</td>";
                $out .= "               <td>" . $ucastnici[$i]->rodne_cislo . "</td>";
                $out .= "               <td>" . $ucastnici[$i]->email . "</td>";
                $out .= "               <td>" . $ucastnici[$i]->telefon . "</td>";
                $out .= "           </tr>";
            }
            $out .= "           </table>";
            $out .= "       </td>";

            $out .= "   </tr>";
        }
        $out .= "   </table>";

        return $out;
    }

    private function objekty()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $objekty = $objednavkaHolder->getObjekty();

        if (!$this->model->isObjednavkaObjekt()) return;
        $out = "";

        $out .= "   <table class='list'>";
        $out .= "       <tr><th colspan='5' class='header header-green align-left'>Objekty</th></tr>";
        $out .= "       <tr><th></th><th>id</th><th>název</th><th>adresa</th><th>poznámka</th></tr>";

        /** defaultne je oznacen objekt[0] @see($this->viewEmaily()) */
        $checked = "checked='on'";
        foreach ($objekty as $o) {
            $out .= "   <tr>";
            $out .= "       <td><input type='radio' name='rb-objekt' $checked value='$o->id' /></td>";
            $out .= "       <td>$o->id</td>";
            $out .= "       <td class='min-w-small'>$o->nazev_objektu</td>";
            $out .= "       <td class='min-w-medium'>$o->ulice<br/>$o->mesto, $o->psc<br/>$o->stat</td>";
            $out .= "       <td>" . $o->poznamka . "</td>";
            $out .= "   </tr>";
            $checked = "";
        }
        $out .= "   </table>";

        return $out;
    }

    private function cenaZahrnuje() {
        $zajezd = $this->model->getObjednavkaHolder()->getZajezd();

        $out = "";

        $out .= "<table class='list width-auto offset-top-20'>";
        $out .= "   <tr><th>Informace o zájezdu</th></tr>";
        $out .= "   <tr><td><textarea name='cena-zahrnuje' id='cena-zahrnuje_' rows='10' cols='100'>$zajezd->cenaZahrnuje</textarea></td></tr>";
        $out .= "</table>";

        return $out;
    }

    private function jazykPoznamka()
    {
        $out = "";

        $out .= "<div class='list-add'>";
        $out .= "   <table class='list'>";
        $out .= "       <colgroup><col width='3%'><col width='5%'><col width='82%'></colgroup>";
        $out .= "       <tr><th></th><th>jazyk</th><th>poznámka editora</th></tr>";
        $out .= "       <tr>";
        $out .= "           <td>";
        $out .= "               <input type='radio' name='language' value='" . VoucheryModelConfig::LANG_CS . "' checked='checked'>";
        $out .= "           </td>";
        $out .= "           <td>èesky</td>";
        $out .= "           <td rowspan='2'>";
        $out .= "               <textarea name='poznamka-editor' rows='6'></textarea>";
        $out .= "           </td>";
        $out .= "       </tr>";
        $out .= "       <tr>";
        $out .= "           <td>";
        $out .= "               <input type='radio' name='language' value='" . VoucheryModelConfig::LANG_EN . "'>";
        $out .= "           </td>";
        $out .= "           <td>anglicky</td>";
        $out .= "       </tr>";
        $out .= "   </table>";
        $out .= "</div>";
        $out .= "</form>";

        return $out;
    }

    private function emaily()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $objednavajici = $objednavkaHolder->getObjednavajici();
        $pdfVouchery = $objednavkaHolder->getPdfVouchery();
        $objekty = $objednavkaHolder->getObjekty();
        $zamestnanecEmail = VoucheryModel::$zamestnanec->get_email();

        if (is_null($pdfVouchery))
            return null;

        $out = "";

        $out .= "<form target='_blank' id='form-send-pdf' method='post' action='vouchery_objednavka.php?page=send-pdf'>";
        $out .= "   <table class='list width-auto offset-top-20' id='tbl-emaily'>";
        $out .= "       <tr><th colspan='3' class='header header-green align-left'>Odeslat pdf na email</th></tr>";
        $out .= "       <tr><th></th><th>email</th><th>role</th></tr>";

        if ($objednavajici->email != "") {
            $out .= "   <tr>";
            $out .= "       <td><input type='checkbox' name='cb-emaily[]' value='$objednavajici->email' /></td>";
            $out .= "       <td class='email'>$objednavajici->email</td>";
            $out .= "       <td>objednavající</td>";
            $out .= "   </tr>";
        }

        if ($this->model->isObjednavkaObjekt() && !is_null($objekty[0]->email)) {
            $out .= "   <tr>";
            $out .= "       <td><input type='checkbox' name='cb-emaily[]' value='" . $objekty[0]->email . "' /></td>";
            /**defaultne je oznacen objekt[0] @see($this->viewObjekty()) */
            $out .= "       <td class='email'>" . $objekty[0]->email . "</td>";
            $out .= "       <td>objekt</td>";
            $out .= "   </tr>";
        }

        if (count((array)$zamestnanecEmail) != "") {
            $out .= "   <tr>";
            $out .= "       <td><input type='checkbox' name='cb-emaily[]' value='$zamestnanecEmail' /></td>";
            $out .= "       <td class='email'>$zamestnanecEmail</td>";
            $out .= "       <td>zamìstnanec</td>";
            $out .= "   </tr>";
        }

        $out .= "       <tr class='edit'>";
        $out .= "           <td></td>";
        $out .= "           <td><input type='text' name='new-email' value='' /></td>";
        $out .= "           <td><a id='btn-add-email' href=''>pøidat email</a></td>";
        $out .= "       </tr>";

        $out .= "   </table>";

        return $out;
    }

    private function emailText() {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $pdfVouchery = $objednavkaHolder->getPdfVouchery();
        
        if (is_null($pdfVouchery))
            return null;

        $out = "";

        $out .= "<table class='list width-auto offset-top-20'>";
        $out .= "   <tr><th>text emailu</th></tr>";
        $out .= "   <tr><td><textarea name='email-text' rows='6' cols='80'>Zasíláme Vám voucher.\n\nS pozdravem CK SLAN tour s.r.o.</textarea></td></tr>";
        $out .= "</table>";

        return $out;
    }

    private function pdfVoucher()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $pdfVouchery = $objednavkaHolder->getPdfVouchery();
        $idObjednavka = $objednavkaHolder->getIdObjednavka();
        $securityCode = $objednavkaHolder->getSecurityCode();

        if (is_null($pdfVouchery))
            return null;

        $out = "";

        $out .= "   <table class='list width-auto offset-top-20' id='tbl-pdf-voucher'>";
        $out .= "       <tr><th colspan='5' class='header header-green align-left'>Výbìr pdf k odeslání</th></tr>";
        $out .= "       <tr><th></th><th>typ</th><th>datum & èas</th><th>soubor</th><th></th></tr>";

        //prvni je defaultne vybrany
        $checked = "checked='checked'";
        foreach ($pdfVouchery as $filePath) {
            $filePathDump = explode("_", $filePath); 
            $typ = explode(".", $filePathDump[2])[0];
            $pdfAnchor = $typ == "objednavka-objekt" ? "objednavka.pdf" : "voucher.pdf";
            $dateTime = date("d.m.Y G:i:s", strtotime(VoucheryUtils::refractorDate($filePathDump[1])));
            $out .= "   <tr>";
            $out .= "       <td><input type='radio' name='rb-pdf-voucher' $checked value='$filePath' /></td>";
            $out .= "       <td>$typ</td>";
            $out .= "       <td>$dateTime</td>";
            $out .= "       <td><a target='_blank' href='" . VoucheryModelConfig::PDF_FOLDER . "$filePath'>$pdfAnchor</a></td>";
            $out .= "       <td class='edit'>";
            $out .= "           <a class='confirm-delete' href='vouchery_objednavka.php?page=delete-pdf&pdf_filename=$filePath&id_objednavka=$idObjednavka&security_code=" . $_REQUEST["security_code"] . "'>";
            $out .= "               <img width='10' src='./img/delete-cross.png'/>";
            $out .= "           </a>";
            $out .= "       </td>";
            $out .= "   </tr>";
            $checked = "";
        }

        $out .= "       <tr class='edit'>";
        $out .= "           <td colspan='5' class='align-right'>";
        $out .= "               <a class='confirm-delete' href='vouchery_objednavka.php?page=delete-all-pdf&id_objednavka=$idObjednavka&security_code=$securityCode'>smazat všechny</a>";
        $out .= "           </td>";
        $out .= "       </tr>";

        $out .= "   </table>";
        $out .= "</form>";
        $out .= "</div>";

        return $out;
    }

    private function pdfEmailActions()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $pdfVouchery = $objednavkaHolder->getPdfVouchery();

        $out = "";

        if (!is_null($pdfVouchery)) {
            $out .= "<div class='list-add'>";
            $out .= "   <div id='email-status'></div>";
            $out .= "   <div class='submenu'><a class='btn btn-action' id='btn-send-pdf' href='#'>odeslat pdf</a></div>";
        }

        return $out;
    }
}
