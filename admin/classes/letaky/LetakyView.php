<?php

//todo tahle trida byla asi zamyslena jako trida pro editaci view voucheru, tzn pro editaci vsech udaju co se maji promitnout v PDF, takze jeste neni nikde vyuzita? Radeji zkontrolovat.
class VoucheryView implements IVoucheryModelObserver
{
    /**
     * @var VoucheryModel
     */
    private $model;
    /**
     * @var VoucheryController
     */
    private $ctrl;
    /**
     * @var string[][] jazykove verze - cs, en
     */
    private $lang;

    /**
     * @var string
     */
    public static $emailSubject = "";
    /**
     * @var string
     */
    public static $emailMessage = "";
    /**
     * @var string
     */
    public static $pdfHtml = "";

    function __construct($model, $ctrl, $lang = "cs")
    {
        $this->model = $model;
        $this->ctrl = $ctrl;
        $this->lang = parse_ini_file("./lang/$lang.conf");
        $this->registerAsObserver();
    }

    // PUBLIC METHODS ********************************************************************
    /**
     * @param $lang string "en"/"cs"
     */
    public function setLanguage($lang)
    {
        $this->lagn = $lang;
    }

    public function loginErr()
    {
        $out = "";
        $out .= SlouceniKlientuView::$employee->get_error_message();
        $out .= SlouceniKlientuView::$employee->show_login_form();

        echo $out;
    }

    public function modelObjednavkaEditChanged()
    {
        $this->viewObjEdit();
    }

    public function modelPdfChanged()
    {
        $this->viewObjednavkaPdf();
    }

    public function modelEmailChanged()
    {
        VoucheryView::$emailSubject = "Voucher";
        VoucheryView::$emailMessage = "Zasíláme Vám voucher.";
    }

    public function selectVoucher()
    {
        $this->viewHead();
        $this->viewSelectVoucherMain();
        $this->viewFoot();
    }

    // PRIVATE METHODS *******************************************************************
    private function viewObjEdit()
    {
        $this->viewHead();
        $this->viewObjEditMain();
        $this->viewFoot();
    }

    private function viewHead()
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
        $out .= "       <link rel='stylesheet' type='text/css' href='styly.css' media='all'/>";
        $out .= "       <script type='text/javascript' src='./js/jquery-min.js'></script>";
        $out .= "       <script type='text/javascript' src='./js/objednavky.js'></script>";
        $out .= "       <script type='text/javascript' src='./js/vouchery.js'></script>";
        $out .= "   </head>";
        $out .= "   <body>";
        $out .= "       <h1>Administrace systému RSCK</h1>";

        $out .= VoucheryModel::$zamestnanec->show_info_about_user();
        $out .= VoucheryModel::$zamestnanec->show_main_menu();

        $out .= "       <div class='main'>";
        $out .= "       <h2>Modul " . VoucheryModel::$core->show_nazev_modulu() . "</h2>";

        echo $out;
    }

    private function viewFoot()
    {
        $out = "";

        $out .= "           <h2 class='napoveda'>Nápovìda k modulu</h2>";
        $out .= "           <p class='napoveda'>" . VoucheryModel::$core->show_napoveda() . "</p>";
        $out .= "       </div>";
        $out .= "   </body>";
        $out .= "</html>";

        echo $out;
    }

    private function viewObjEditMain()
    {
        $obj = $this->model->getObjednavkaHolder();
        $sluzby = $obj->getSluzby();
        $ucastnici = $obj->getUcastnici();
        $objekty = $obj->getObjekty();
        $objednavajici = $obj->getObjednavajici();
        $zamestnanecEmail = VoucheryModel::$zamestnanec->get_email();
        $pdfVouchery = $this->model->getObjednavkaHolder()->getPdfVouchery();

        $this->viewObjednavkaHeader();
        $this->viewObjednavkaInfo();
        $this->viewSluzby($sluzby, $ucastnici, $obj->getIdObjednavka(), $obj->getSecurityCode());
        $this->viewObjekty($objekty);
        $this->viewPoznamka();
        $this->viewPdfGenActions();

        $this->viewEmaily($objednavajici, $objekty, $zamestnanecEmail, $pdfVouchery);
        $this->viewPdfVoucher($pdfVouchery);
        $this->viewPdfEmailActions($pdfVouchery);
    }

    private function registerAsObserver()
    {
        $this->model->registerObserver($this);
    }

    private function viewObjednavkaHeader()
    {
        $out = "";

        if ($this->model->isObjednavkaObjekt())
            $out .= "<h2>objekty</h2><br/>";
        else
            $out .= "<h2>zajezd</h2><br/>";
        $out .= "<a href='vouchery_objednavka.php?page=select-voucher&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"] . "'>zpìt</a><br/><br/>";

        echo $out;
    }

    private function viewObjednavkaInfo()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $objednavka = $objednavkaHolder->getObjednavka();
        $objednavajici = $objednavkaHolder->getObjednavajici();
        $zajezd = $objednavkaHolder->getZajezd();
        $terminOd = VoucheryUtils::czechDate($zajezd->terminOd);
        $terminDo = VoucheryUtils::czechDate($zajezd->terminDo);

        $out = "";

        $out .= "<table class='main'>";
        $out .= "   <caption class='header'>Info objednávky</caption>";
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

        echo $out;
    }

    /**
     * @param $sluzby tsSluzba[]
     * @param $ucastnici tsOsoba[]
     * @param $id int id objednavky
     * @param $securityCode string security code
     */
    private function viewSluzby($sluzby, $ucastnici, $id, $securityCode)
    {
        $out = $checked = "";

        $out .= "<form target='_blank' id='form-generate-pdf' method='post' action='vouchery_objednavka.php?page=create-pdf&id_objednavka=$id&security_code=$securityCode'>";
        $out .= "   <table id='tbl-sluzby' class='main'>";
        $out .= "       <caption>Služby</caption>";
        $out .= "       <tr><th></th><th>id</th><th>název</th><th>èástka</th><th>poèet</th><th>objektové kategorie</th><th>osoby</th></tr>";
        foreach ($sluzby as $s) {
            $out .= "   <tr>";

            $out .= "       <td class='valign-mid align-center'><input type='checkbox' name='cb-sluzby[]' checked='on' value='$s->id_cena' /></td>";
            $out .= "       <td class='valign-mid align-center'>$s->id_cena</td>"; //todo: odkaz
            $out .= "       <td class='valign-mid align-center'>$s->nazev_ceny</td>";
            $out .= "       <td class='valign-mid align-center'>$s->castka $s->mena</td>";
            $out .= "       <td class='valign-mid align-center'>$s->pocet</td>";

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

            $out .= "       <td class='no-space'>";
            $out .= "           <table id='subtbl-osoby' class='subtable hoverable'>";
            if (count((array)$ucastnici) == $s->pocet) $checked = "checked='on'"; else $checked = "";
            for ($i = 0; $i < count((array)$ucastnici); $i++) {
                $out .= "           <tr>";
                $out .= "               <td><input type='checkbox' class='cb-osoba' name='cb-ucastnici-$s->id_cena[]' $checked value='" . $ucastnici[$i]->id . "' /></td>";
                $out .= "               <td><a target='_blank' href='klienti.php?id_klient=" . $ucastnici[$i]->id . "&typ=klient&pozadavek=edit'>" . $ucastnici[$i]->id . "</a></td>";
                $out .= "               <td>" . $ucastnici[$i]->jmeno . " " . $ucastnici[$i]->prijmeni . "</td>";
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

        /**<form> konci v @see($this->viewPoznamka()) */

        echo $out;
    }

    /**
     * @param $objekty tsObjekt[]
     */
    private function viewObjekty($objekty)
    {
        if (!$this->model->isObjednavkaObjekt()) return;
        $out = "";

        $out .= "   <table class='main'>";
        $out .= "       <caption>Objekty</caption>";
        $out .= "       <tr><th></th><th>id</th><th>název</th><th>adresa</th><th>poznámka</th></tr>";

        /** defaultne je oznacen objekt[0] @see($this->viewEmaily()) */
        $checked = "checked='on'";
        foreach ($objekty as $o) {
            $out .= "   <tr>";
            $out .= "       <td><input type='radio' name='rb-objekt' $checked value='$o->id' /></td>";
            $out .= "       <td>$o->id</td>";
            $out .= "       <td>$o->nazev_objektu</td>";
            $out .= "       <td>$o->ulice<br/>$o->mesto, $o->psc<br/>$o->stat</td>";
            $out .= "       <td>" . strip_tags($o->poznamka) . "</td>";
            $out .= "   </tr>";
            $checked = "";
        }
        $out .= "   </table>";

        echo $out;
    }

    private function viewPoznamka()
    {
        $out = "";

        $out .= "   <table class='main'>";
        $out .= "       <caption>Poznámka editora</caption>";
        $out .= "       <tr>";
        $out .= "           <td>";
        $out .= "               <textarea name='poznamka-editor' rows='6' cols='60'></textarea>";
        $out .= "           </td>";
        $out .= "       </tr>";
        $out .= "   </table>";
        $out .= "</form>";

        echo $out;
    }

    /**
     * @param $objednavajici tsObjednavajici
     * @param $objekty tsObjekt[]
     * @param $zamestnanecEmail string
     * @param $pdfVouchery string[]
     * @return null
     */
    private function viewEmaily($objednavajici, $objekty, $zamestnanecEmail, $pdfVouchery)
    {
        if (is_null($pdfVouchery))
            return null;

        $out = "";

        $out .= "<form target='_blank' id='form-send-pdf' method='post' action='vouchery_objednavka.php?page=send-pdf'>";
        $out .= "   <table class='main offset-top-2' id='tbl-emaily'>";
        $out .= "       <caption>Odeslat pdf na email</caption>";
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

        //todo proc count? chyba?
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

        /**<form> konci v @see($this->viewPdfVoucher()) */

        echo $out;
    }

    private function viewPdfVoucher($pdfVouchery)
    {
        if (is_null($pdfVouchery))
            return null;

        $out = "";

        $out .= "   <table class='main offset-top-2 hoverable' id='tbl-pdf-voucher'>";
        $out .= "       <caption>Výbìr pdf k odeslání</caption>";
        $out .= "       <tr><th></th><th>typ</th><th>datum & èas</th><th>soubor</th><th></th></tr>";

        //prvni je defaultne vybrany
        $checked = "checked='checked'";
        foreach ($pdfVouchery as $filePath) {
            $filePathDump = explode("_", $filePath);
            $typ = explode(".", $filePathDump[2])[0];
            $dateTime = date("d.m.Y G:i:s", strtotime(VoucheryUtils::refractorDate($filePathDump[1])));
            $out .= "   <tr>";
            $out .= "       <td><input type='radio' name='rb-pdf-voucher' $checked value='$filePath' /></td>";
            $out .= "       <td>$typ</td>";
            $out .= "       <td>$dateTime</td>";
            $out .= "       <td><a target='_blank' href='" . VoucheryModel::$PDF_FOLDER . "$filePath'>voucher.pfd</a></td>";
            $out .= "       <td class='edit'>";
            $out .= "           <a class='confirm-delete' href='vouchery_objednavka.php?page=delete-pdf&pdf_filename=$filePath&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"] . "'>";
            $out .= "               <img width='10' src='./img/delete-cross.png'/>";
            $out .= "           </a>";
            $out .= "       </td>";
            $out .= "   </tr>";
            $checked = "";
        }

        $out .= "   </table>";
        $out .= "</form>";

        echo $out;
    }

    private function viewPdfGenActions()
    {
        $out = "";
        $out .= "<a id='btn-generate-pdf' href='#'>vygenerovat pdf</a>";
        echo $out;
    }

    private function viewPdfEmailActions($pdfVouchery)
    {
        $out = "";
        if (!is_null($pdfVouchery)) {
            $out .= "<div id='email-status'></div>";
            $out .= "<a id='btn-send-pdf' href='#'>odeslat pdf</a>";
        }
        echo $out;
    }

    private function viewObjednavkaPdf()
    {
        $html = "";

        $html .= $this->pdfHeader();
        $html .= $this->pdfZajezd();
        $html .= $this->pdfObjednaneSluzby();
        $html .= $this->pdfPoznamka();
        $html .= $this->pdfPoznamkaEditor();
        $html .= $this->pdfVystavil();
        $html .= $this->pdfDatumRezervace();
        $html .= $this->pdfFooter();

        VoucheryView::$pdfHtml = $html;
    }

    private function pdfHeader()
    {
        $idObjednavka = $this->model->getObjednavkaHolder()->getIdObjednavka();
        $objekty = $this->model->getObjednavkaHolder()->getObjekty();

        $html = "";
        $html .= "<table cellpadding='0' cellspacing='8'  width='810'>";
        $html .= "  <tr>";
        $html .= "      <th colspan='3' style='padding-left:0;padding-right:0;'>";

        if ($this->model->isObjednavkaObjekt()) {
            $html .= "      <h1>" . $this->lang["voucher"] . "</h1></br>";
        } else {
            $html .= "      <h1>" . $this->lang["voucher-k-zajezdu"] . "</h1></br>";
        }

        $html .= "      </th>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td class='border2' valign='top' rowspan='4' colspan='2'>";
        $html .= $this->pdfZakaznik();
        $html .= "      </td>";
        $html .= "      <td lign='center' valign='top' width='180'>";
        $html .= "          <img src='../pix/logo_slantour.gif' width='150' height='83' /><br/>";
        $html .= "          <strong>" . $this->lang["cislo-objednavky"] . ":</strong>";
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td class='border2'  align='center'>";
        $html .= "          <strong>$idObjednavka</strong>";
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function pdfZakaznik()
    {
        $objednavajici = $this->model->getObjednavkaHolder()->getObjednavajici();
        $titul = $objednavajici->titul == "" ? "" : $objednavajici->titul . " ";

        $html = "";

        $html .= "<h2>Zákazník</h2><br/>";
        $html .= "<p style='font-size:1.2em;'>";
        $html .= "  <strong class='capitals'>" . $titul . "$objednavajici->jmeno $objednavajici->prijmeni</strong><br/>";
        $html .= "  <strong>Adresa:</strong> $objednavajici->adresa_ulice, $objednavajici->adresa_psc, $objednavajici->adresa_mesto<br/>";
        if ($objednavajici->email != "")
            $html .= "  <strong>Email:</strong> $objednavajici->email<br/>";
        if ($objednavajici->telefon != "")
            $html .= "  <strong>Telefon:</strong> $objednavajici->telefon<br/>";
        $html .= "</p>";

        return $html;
    }

    private function pdfZajezd()
    {
        $zajezd = $this->model->getObjednavkaHolder()->getZajezd();
        $objednavka = $this->model->getObjednavkaHolder()->getObjednavka();
        $objekty = $this->model->getObjednavkaHolder()->getObjekty();
        $objekt = $objekty[$this->model->getObjektSelected()];
        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";

        if ($this->model->isObjednavkaObjekt()) {
            $html .= "  <tr>";
            $html .= "      <td class='border2 strong'>$objekt->nazev_objektu</td>";
            $html .= "      <td class='border2'>";
            $html .= "          <strong>Kontakt:</strong><br/>";
            $html .= "          $objekt->nazev_objektu<br/>";

            //nektere objekty nemaji adresu - asi chyba toho kdo objekt zadaval
            if ($objekt->ulice) {
                $html .= "          $objekt->ulice<br/>";
                $html .= "          $objekt->mesto, $objekt->psc<br/>";
                $html .= "          $objekt->stat<br/>";
                $html .= "          tel: $objekt->telefon<br/>";
            }

            $html .= "      </td>";
            $html .= "  </tr>";
            $html .= "  <tr>";
            $html .= "      <td class='border2 strong'></td>";
            $html .= "      <td class='border2'>";
            $html .= "          Termín: " . VoucheryUtils::czechDate($zajezd->terminOd) . " - " . VoucheryUtils::czechDate($zajezd->terminDo) . "<br/>";
            $html .= "          Poèet nocí: $objednavka->pocet_noci<br/>";
            $html .= "          Poèet osob: $objednavka->pocet_osob<br/>";
            $html .= "      </td>";
            $html .= "  </tr>";
        } else {
            $html .= "  <tr>";
            $html .= "      <td class='border2 strong'>$zajezd->nazevSerialu" . ($zajezd->nazevUbytovani == "" ? "" : " ($zajezd->nazevUbytovani)") . "</td>";
            $html .= "      <td class='border2'>";
            $html .= "          Termín: " . VoucheryUtils::czechDate($zajezd->terminOd) . " - " . VoucheryUtils::czechDate($zajezd->terminDo) . "<br/>";
            $html .= "          Poèet nocí: $objednavka->pocet_noci<br/>";
            $html .= "          Poèet osob: $objednavka->pocet_osob<br/>";
            $html .= "      </td>";
            $html .= "  </tr>";
        }

        $html .= "</table>";

        return $html;
    }

    private function pdfObjednaneSluzby()
    {
        $vybraneSluzby = $this->model->getSluzbySelected();
        $objednaneSluzby = $this->model->getObjednavkaHolder()->getSluzby();
        $ucastnici = $this->model->getObjednavkaHolder()->getUcastnici();
        $vybraniUcastnici = $this->model->getUcastniciSelected();
        $vybraneObjektoveKategorie = $this->model->getObjektoveKategorieSelected();

        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td colspan='3'>Objednané služby:</td>";
        $html .= "  </tr>";
        $html .= "  <tr><th class='border2' width='8%'>Poèet</th><th class='border2'>Název služby</th><th class='border2'>OK Název</th><th class='border2'>Úèastník</th></tr>";

        foreach ($objednaneSluzby as $objednanaSluzba) {
            if (!in_array($objednanaSluzba->id_cena, $vybraneSluzby))
                continue;
            $objektovaKategorie = $objednanaSluzba->objektoveKategorie[$vybraneObjektoveKategorie[$objednanaSluzba->id_cena]];
            $html .= "  <tr>";
            $html .= "      <td class='border2'>$objednanaSluzba->pocet</td>";
            $html .= "      <td class='border2'>$objednanaSluzba->nazev_ceny</td>";
            $html .= "      <td class='border2'>$objektovaKategorie->nazev</td>";
            $html .= "      <td class='border2'>";
            foreach ($ucastnici as $u) {
                if (@!in_array($u->id, $vybraniUcastnici[$objednanaSluzba->id_cena]))
                    continue;
                $html .= "$u->jmeno $u->prijmeni - ";
                $html .= $this->model->isDate($u->rodne_cislo) ? VoucheryUtils::czechDate($u->rodne_cislo) : $u->rodne_cislo;
                $html .= "<br/>";
            }
            $html .= "      </td>";
            $html .= "  </tr>";
        }

        $html .= "</table>";

        return $html;
    }

    private function pdfPoznamka()
    {
        $objekty = $this->model->getObjednavkaHolder()->getObjekty();
        $objekt = $objekty[$this->model->getObjektSelected()];

        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td class='border2'>";
        $html .= "          Poznámka:<br/>" . strip_tags($objekt->poznamka);
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function pdfPoznamkaEditor()
    {
        $poznamkaEditor = $this->model->getPoznamkaEditor();

        if ($poznamkaEditor == "")
            return;

        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td class='border2'>";
        $html .= "          Poznámka editora:<br/>" . strip_tags($poznamkaEditor);
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function pdfVystavil()
    {
        $vystavil = $this->model->getObjednavkaHolder()->getVystavil();
        $datum = date("d.m.Y");
        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td class='border2' style='width:65%'>Vystavil: $vystavil->jmeno  $vystavil->prijmeni</td>";
        $html .= "      <td class='border2'>Datum: $datum</td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function pdfDatumRezervace()
    {
        $datumRezervace = $this->model->getObjednavkaHolder()->getObjednavka()->datum_rezervace;
        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td  style='width:65%'>Datum rezervace: " . VoucheryUtils::czechDatetime($datumRezervace) . "</td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function pdfFooter()
    {
        $centralniData = $this->model->getObjednavkaHolder()->getCentralniData();
        $html = "";

        $html .= "<hr/>";
        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "<tr>";
        $html .= "  <td valign='left'>";
        $html .= "      <h2>" . $centralniData["nazev_spolecnosti"] . "</h2>";
        $html .= "      <p style='font-size:1.1em;'>";
        $html .= "" . $centralniData["adresa"] . "<br/>";
        $html .= "" . $centralniData["firma_zapsana"] . "<br/>";
        $html .= "          tel.: " . $centralniData["telefon"] . "<br/>";
        $html .= "          IÈO: " . $centralniData["ico"] . " DIÈ: " . $centralniData["dic"] . "<br/>";
        $html .= "          Bankovní spojení: " . $centralniData["bankovni_spojeni"] . "<br/>";
        $html .= "          e-mail: " . $centralniData["email"] . ", web: " . $centralniData["web"] . "<br/>";
        $html .= "      </p>";
        $html .= "  </td>";
        $html .= "<tr>";
        $html .= "</table>";

        return $html;
    }

    private function viewSelectVoucherMain()
    {
        $out = "";

        $out .= "   <br/>";
        $out .= "   <a href='rezervace.php?typ=rezervace_list'>zpìt</a><br/><br/>";
        $out .= "   <a href='vouchery_objednavka.php?page=obj-edit-objekt&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"] . "'>voucher objekt</a><br/>";
        $out .= "   <a href='vouchery_objednavka.php?page=obj-edit-zajezd&id_objednavka=" . $_REQUEST["id_objednavka"] . "&security_code=" . $_REQUEST["security_code"] . "'>voucher zajezd</a>";
        $out .= "</form>";

        echo $out;
    }
}

//todo rozdelit view na 2 pdf a html web
//todo delete nesmi jit refreshnout - respektive muze, ale musi se to odchytit, jinakbudu mazat soubor co neexistuje(staci mozna @ u unlink();

?>
