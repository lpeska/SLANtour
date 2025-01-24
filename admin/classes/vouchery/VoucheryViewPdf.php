<?php

class VoucheryViewPdf implements IVoucheryModelPdfObserver
{
    /**
     * @var VoucheryModel
     */
    private $model;
    /**
     * @var string[][] retezce (popisky) v ruznych jazycich - cs, en
     */
    private $langStrings;

    /**
     * @param $model VoucheryModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerPdfObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelPdfVoucherChanged()
    {
        if (!$this->langStrings = @parse_ini_file(VoucheryModelConfig::LANG_DIR . $this->model->getLanguage() . ".conf")) {
            //todo: err msg nespravny jazyk
        }
        $this->objednavkaPdfVoucher();
    }

    public function modelPdfObjednavkaObjektChanged()
    {
        if (!$this->langStrings = @parse_ini_file(VoucheryModelConfig::LANG_DIR . $this->model->getLanguage() . ".conf")) {
            //todo: err msg nespravny jazyk
        }
        $this->objednavkaPdfObjednavkaObjekt();
    }

    // PRIVATE METHODS *******************************************************************

    private function objednavkaPdfVoucher()
    {
        $html = "";

        $html .= $this->headerVoucher();
        $html .= $this->zajezdHlavniInfo();
        $html .= $this->zajezdVedlejsiInfo();
        $html .= $this->objednaneSluzby();
        $html .= $this->poznamka();
        $html .= $this->poznamkaEditor();
        $html .= $this->vystavil();
        $html .= $this->datumRezervace();
        $html .= $this->footer();

        $this->model->generatePdf($html, VoucheryModelConfig::PDF_TYPE_VOUCHER);
    }

    private function objednavkaPdfObjednavkaObjekt()
    {
        $html = "";

        $html .= $this->headerObjednavkaObjekt();
        $html .= $this->zajezdHlavniInfo();
        $html .= $this->zajezdVedlejsiInfo();
        $html .= $this->objednaneSluzby();
        $html .= $this->poznamkaEditor();
        $html .= $this->vystavil();
        $html .= $this->datumRezervace();
        $html .= $this->footer(true);

        $this->model->generatePdf($html, VoucheryModelConfig::PDF_TYPE_OBJEDNAVKA_OBJEKT);
    }

    private function headerVoucher()
    {
        $idObjednavka = $this->model->getObjednavkaHolder()->getIdObjednavka();
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' cellspacing='8'  width='810'>";
        $html .= "  <tr>";
        $html .= "      <th colspan='3' style='padding-left:0;padding-right:0;'>";

        if ($this->model->isObjednavkaObjekt()) {
            $html .= "      <h1>" . $lang["voucher"] . "</h1><br/>";
        } else {
            $html .= "      <h1>" . $lang["voucher-k-zajezdu"] . "</h1><br/>";
        }

        $html .= "      </th>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td class='border2' valign='top' rowspan='4' colspan='2'>";
        $html .= $this->zakaznik();
        $html .= "      </td>";
        $html .= "      <td lign='center' valign='top' width='180'>";
        $html .= "          <img src='https://slantour.cz/foto/full/14628-logo-slantour.jpg' width='150' height='83' /><br/>";
        $html .= "          <strong>" . $lang["cislo-objednavky"] . ":</strong>";
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

    private function headerObjednavkaObjekt()
    {
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' cellspacing='8'  width='810'>";
        $html .= "  <tr>";
        $html .= "      <th colspan='3' style='padding-left:0;padding-right:0;'>";
        $html .= "      <h1>" . $lang["objednavka-objekt"] . "</h1>";
        $html .= "      </th>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td colspan='3' align='right'>";
        $html .= "          <img src='../pix/logo_slantour.gif' width='150' height='83' />";
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function zakaznik()
    {
        $objednavajici = $this->model->getObjednavkaHolder()->getObjednavajici();
        $objednavajiciOrg = $this->model->getObjednavkaHolder()->getObjednavajiciOrg();
        $titul = $objednavajici->titul == "" ? "" : $objednavajici->titul . " ";
        $lang = $this->langStrings;

        $html = "";

        $html .= "<h2>" . $lang["zakaznik"] . "</h2><br/>";
        $html .= "<p style='font-size:1.2em;'>";

        if (is_null($objednavajiciOrg)) {
            $html .= "  <strong class='capitals'>" . $titul . ucfirst($objednavajici->jmeno) . " " . ucfirst($objednavajici->prijmeni) . "</strong><br/>";
            $html .= "  <strong>" . $lang["adresa"] . "</strong> $objednavajici->adresa_ulice, $objednavajici->adresa_psc, $objednavajici->adresa_mesto<br/>";
            if ($objednavajici->email != "")
                $html .= "  <strong>" . $lang["email"] . "</strong> $objednavajici->email<br/>";
            if ($objednavajici->telefon != "")
                $html .= "  <strong>" . $lang["telefon"] . "</strong> $objednavajici->telefon<br/>";
        } else {
            $adresa = $objednavajiciOrg->adresa;
            $html .= "  <strong class='capitals'>" . $objednavajiciOrg->nazev . "</strong><br/>";
            $html .= "  <strong>" . $lang["adresa"] . "</strong> $adresa->ulice, $adresa->psc, $adresa->mesto<br/>";
            if ($objednavajiciOrg->email != "")
                $html .= "  <strong>" . $lang["email"] . "</strong> $objednavajiciOrg->email<br/>";
            if ($objednavajiciOrg->telefon != "")
                $html .= "  <strong>" . $lang["telefon"] . "</strong> $objednavajiciOrg->telefon<br/>";
        }

        $html .= "</p>";

        return $html;
    }

    private function zajezdHlavniInfo()
    {
        $zajezd = $this->model->getObjednavkaHolder()->getZajezd();
        $objednavka = $this->model->getObjednavkaHolder()->getObjednavka();
        $objekt = $this->model->getObjektSelected();
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' cellspacing='0' class='font-size-12' style='border-collapse: collapse;margin:8px;' width='810' >";

        if ($this->model->isObjednavkaObjekt()) {
            $html .= "  <tr>";
            $html .= "      <td class='border2 strong'>$objekt->nazev_objektu</td>";
            $html .= "      <td class='border2'>";
            $html .= "          <strong>" . $lang["kontakt"] . "</strong><br/>";
            $html .= "          $objekt->nazev_objektu<br/>";

            //nektere objekty nemaji adresu - asi chyba toho kdo objekt zadaval
            if ($objekt->ulice) {
                $html .= "          $objekt->ulice<br/>";
                $html .= "          $objekt->mesto, $objekt->psc<br/>";
                $html .= "          $objekt->stat<br/>";
                $html .= "          " . $lang["tel"] . " $objekt->telefon<br/>";
            }

            $html .= "      </td>";
            $html .= "  </tr>";
            $html .= "  <tr>";
            $html .= "      <td class='border2 strong'></td>";
            $html .= "      <td class='border2'>";
            $html .= "          " . $lang["termin"] . " " . VoucheryUtils::czechDate($zajezd->terminOd) . " - " . VoucheryUtils::czechDate($zajezd->terminDo) . ", ";
            $html .= "          " . $lang["pocet-noci"] . " $objednavka->pocet_noci, ";
            $html .= "          " . $lang["pocet-osob"] . " $objednavka->pocet_osob";
            $html .= "      </td>";
            $html .= "  </tr>";
        } else {
            $html .= "  <tr>";
            $html .= "      <td class='border2 strong'>$zajezd->nazevSerialu" . ($zajezd->nazevUbytovani == "" ? "" : " ($zajezd->nazevUbytovani)") . "</td>";
            $html .= "      <td class='border2'>";
            $html .= "          " . $lang["termin"] . " " . VoucheryUtils::czechDate($zajezd->terminOd) . " - " . VoucheryUtils::czechDate($zajezd->terminDo) . ", ";
            $html .= "          " . $lang["pocet-noci"] . " $objednavka->pocet_noci, ";
            $html .= "          " . $lang["pocet-osob"] . " $objednavka->pocet_osob";
            $html .= "      </td>";
            $html .= "  </tr>";
        }

        $html .= "</table>";


        return $html;
    }

    private function zajezdVedlejsiInfo()
    {
        $zajezd = $this->model->getObjednavkaHolder()->getZajezd();
        $cenaZahrnuje = $this->model->getCenaZahrnuje();
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' class='font-size-12' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td colspan='2'>" . $lang["informace-o-zajezdu"] . "</td>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td class='border2 valign-top'>$zajezd->nazevSerialu</td>";
        $html .= "      <td class='border2'>$cenaZahrnuje</td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function objednaneSluzby()
    {
        $vybraneSluzby = $this->model->getSluzbySelected();
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' class='font-size-12' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td colspan='3'>" . $lang["objednane-sluzby"] . "</td>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <th class='border2' width='8%'>" . $lang["tbl-th-pocet"] . "</th>";
        $html .= "      <th class='border2'>" . $lang["tbl-th-nazev-typ-ubyt"] . "</th>";
        $html .= "      <th class='border2'>" . $lang["tbl-th-nazev-sluzby"] . "</th>";
        $html .= "      <th class='border2'>" . $lang["tbl-th-ucastnici"] . "</th>";
        $html .= "  </tr>";

        foreach ((array)$vybraneSluzby as $sluzba) {
            $html .= "  <tr>";
            $html .= "      <td class='border2'>$sluzba->pocet</td>";
            $html .= "      <td class='border2'>" . $sluzba->vybranaObjektovaKategorie->nazev . "</td>";
            $html .= "      <td class='border2'>$sluzba->nazev_ceny</td>";
            $html .= "      <td class='border2'>";

            foreach ((array)$sluzba->vybraniUcastnici as $u) {
                $html .= ucfirst($u->jmeno) . " " . ucfirst($u->prijmeni);
                if (!VoucheryUtils::isEmptyDate($u->rodne_cislo))
                    $html .= " - " . (VoucheryUtils::isDate($u->rodne_cislo) ? VoucheryUtils::czechDate($u->rodne_cislo) : $u->rodne_cislo);
                $html .= "<br/>";
            }

            $html .= "      </td>";
            $html .= "  </tr>";
        }

        $html .= "</table>";

        return $html;
    }

    private function poznamka()
    {
        $objekt = $this->model->getObjektSelected();
        $lang = $this->langStrings;

        if ($objekt->poznamka == "")
            return null;

        $html = "";

        $html .= "<table cellpadding='0' class='font-size-12' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td>" . $lang["poznamka"] . "</td>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td class='border2'>";
        $html .= "          " . strip_tags($objekt->poznamka);
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function poznamkaEditor()
    {
        $poznamkaEditor = $this->model->getPoznamkaEditor();
        $lang = $this->langStrings;

        if ($poznamkaEditor == "")
            return null;

        $html = "";

        $html .= "<table cellpadding='0' class='font-size-12' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td>" . $lang["poznamka-editora"] . "</td>";
        $html .= "  </tr>";
        $html .= "  <tr>";
        $html .= "      <td class='border2'>";
        $html .= "          " . ($poznamkaEditor);
        $html .= "      </td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function vystavil()
    {
        $vystavil = $this->model->getObjednavkaHolder()->getVystavil();
        $datum = date("d.m.Y");
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' class='font-size-12' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td class='border2' style='width:65%'>" . $lang["vystavil"] . " $vystavil->jmeno  $vystavil->prijmeni</td>";
        $html .= "      <td class='border2'>" . $lang["datum"] . " $datum</td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function datumRezervace()
    {
        $datumRezervace = $this->model->getObjednavkaHolder()->getObjednavka()->datum_rezervace;
        $id_objednavka = $this->model->getObjednavkaHolder()->getObjednavka()->id_objednavka;
        $lang = $this->langStrings;

        $html = "";

        $html .= "<table cellpadding='0' class='font-size-12' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "  <tr>";
        $html .= "      <td  style='width:20%'>" . $lang["id_objednavka"] . " " . $id_objednavka . "</td><td  style='width:65%'>" . $lang["datum-rezervace"] . " " . VoucheryUtils::czechDatetime($datumRezervace) . "</td>";
        $html .= "  </tr>";
        $html .= "</table>";

        return $html;
    }

    private function footer()
    {
        $centralniData = $this->model->getObjednavkaHolder()->getCentralniData();
        $lang = $this->langStrings;

        $html = "";

        $html .= "<hr/>";
        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $html .= "<tr>";
        $html .= "  <td valign='left'>";
        $html .= "      <h2>" . $centralniData["nazev_spolecnosti"] . "</h2>";
        $html .= "      <p style='font-size:1.1em;'>";
        $html .= $centralniData["adresa"] . "<br/>";
        $html .= $centralniData["firma_zapsana"] . "<br/>";
        $html .= $lang["tel"] . " " . $centralniData["telefon"] . "<br/>";
        $html .= $lang["ico"] . " " . $centralniData["ico"] . " DIÈ: " . $centralniData["dic"] . "<br/>";
        $html .= $lang["bankovni-spojeni"] . " " . $centralniData["bankovni_spojeni"] . "<br/>";
        $html .= $lang["email"] . " " . $centralniData["email"] . ", web: " . $centralniData["web"] . "<br/>";
        $html .= "      </p>";
        $html .= "  </td>";
        $html .= "<tr>";
        $html .= "</table>";

        return $html;
    }
}