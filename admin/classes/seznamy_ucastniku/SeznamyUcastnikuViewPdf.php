<?php

class SeznamyUcastnikuViewPdf implements ISeznamyUcastnikuModelPdfObserver
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
        $this->model->registerPdfObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelPdfChanged()
    {
        $this->pdf();
    }

    // PRIVATE METHODS *******************************************************************
    private function pdf()
    {
        $html = "";

        $html .= $this->header();
        $html .= $this->viewPdfMain();
        $html .= $this->footer();

        $this->model->generatePdf($html);
    }

    private function header()
    {
        $out = "";

        $out .= "<table cellpadding='0' cellspacing='8'  width='810'>";
        $out .= "   <tr>";
        $out .= "       <th colspan='3' style='padding-left:0;padding-right:0;'>";
        $out .= "           <h1>Seznam úèastníkù</h1>";
        $out .= "       </th>";
        $out .= "   </tr>";
        $out .= "</table>";

        return $out;
    }

    private function footer()
    {
        $centralniData = $this->model->getCentralniData();

        $lang["tel"] = "tel:";
        $lang["ico"] = "ico:";
        $lang["bankovni-spojeni"] = "bankovni-spojeni:";
        $lang["email"] = "email:";
        $lang["web"] = "web:";

        $out = "";

        $out .= "<hr/>";
        $out .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >";
        $out .= "<tr>";
        $out .= "  <td valign='left'>";
        $out .= "      <h2>" . $centralniData["nazev_spolecnosti"] . "</h2>";
        $out .= "      <p style='font-size:1.1em;'>";
        $out .= $centralniData["adresa"] . "<br/>";
        $out .= $centralniData["firma_zapsana"] . "<br/>";
        $out .= $lang["tel"] . " " . $centralniData["telefon"] . "<br/>";
        $out .= $lang["ico"] . " " . $centralniData["ico"] . " DIÈ: " . $centralniData["dic"] . "<br/>";
        $out .= $lang["bankovni-spojeni"] . " " . $centralniData["bankovni_spojeni"] . "<br/>";
        $out .= $lang["email"] . " " . $centralniData["email"] . ", " . $lang["web"] . " " . $centralniData["web"] . "<br/>";
        $out .= "      </p>";
        $out .= "  </td>";
        $out .= "      <td lign='center' valign='top' width='180'>";
        $out .= "          <img src='../pix/logo_slantour.gif' width='150' height='63' /><br/>";
        $out .= "      </td>";
        $out .= "<tr>";
        $out .= "</table>";

        return $out;
    }

    private function viewPdfMain()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = "";

        if (is_null($serialy)) {
            $out .= "<div class='sys-msg error align-center'>Nenalezeny žádné záznamy vyhovující podmínkám.</div>";
        } else {
            $out .= $this->serialyLoop($serialy);
        }

        return $out;
    }

    private function serialyLoop($serialy)
    {
        $filterValues = $this->model->getSerialFilter();
        $zajezdSetup = $filterValues->getZajezdSetup();
        $colsCount = $zajezdSetup->getSetValuesCount() + 2;

        $out = "";

        foreach ($serialy as $serial) {
            $zajezdy = $serial->zajezdy;
            if (!is_null($zajezdy)) {
                $out .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;width: 100%;'>";
                $out .= "   <tr>";
                $out .= "       <td colspan='$colsCount'><h2>Seriál: $serial->nazev</h2></td>";
                $out .= "   </tr>";
                $out .= "       <tr><td></td></tr>";

                $out .= $this->zajezdyLoop($zajezdy, $colsCount);

                $out .= "</table>";
            }
        }

        return $out;
    }

    /**
     * @param $zajezdy tsZajezd[]
     * @param $colsCount
     * @return string
     */
    private function zajezdyLoop($zajezdy, $colsCount)
    {
        $out = "";

        foreach ($zajezdy as $zajezd) {
            if (!$zajezd->hasObjednavky())
                continue;

            $objednavky = $zajezd->objednavky;

            $out .= "<tr>";
            $out .= "   <td class='border2' valign='top' colspan='$colsCount'>";
            $out .= "       <h3>Zájezd: $zajezd->id" . ($zajezd->nazevUbytovani == "" ? "" : ", $zajezd->nazevUbytovani") .
                " [" . SeznamyUcastnikuUtils::czechDate($zajezd->terminOd) .
                " - " . SeznamyUcastnikuUtils::czechDate($zajezd->terminDo) . "]</h3>";
            $out .= "   </td>";
            $out .= "</tr>";

            $out .= $this->objednavkyLoop($objednavky, $colsCount);
        }

        return $out;
    }

    /**
     * @param $objednavky tsObjednavka[]
     * @param $colsCount
     * @return string
     */
    private function objednavkyLoop($objednavky, $colsCount)
    {
        $filterValues = $this->model->getSerialFilter();
        $zajezdSetup = $filterValues->getZajezdSetup();
        $objednavkaSetup = $filterValues->getObjednavkaSetup();
        $objednavkySelected = $filterValues->getObjednavkyIdsSelected();
        $out = "";

        $index = 1;
        if (!is_null($objednavky)) {
            if($objednavkaSetup->nezobrazovat_objednavky){
                //nezobrazuji se objednavky, je treba vypsat jednou header ucastniku
                $out .= $this->headerUcastnici($zajezdSetup);
            }            
            
            foreach ($objednavky as $objednavka) {
                //Lada: pokud bylo zaskrtnuto nezobrazovat objednavky, zobrazi se pouze ucastnici zajezdu
                //Lada: pokud nebyla zaskrtnuta objednavka, nezobrazuju k ni zadne informace, ani ucastniky
                if(!$objednavkaSetup->nezobrazovat_objednavky and in_array($objednavka->id_objednavka, $objednavkySelected)){                                        
                    $objednavajici = $objednavka->objednavajici;
                    $prodejce = $objednavka->prodejce;

                    $out .= "<tr>";
                    $out .= "   <td class='border2' valign='top' colspan='$colsCount'>";
                    $out .= "       <h4>";
                    $out .= "           Objednávka";
                    $out .= $objednavkaSetup->id ? " $objednavka->id_objednavka" : "";
                    $out .= $objednavkaSetup->objednavajici ? ", objednávající: $objednavajici->jmeno $objednavajici->prijmeni" : "";
                    $out .= $objednavkaSetup->prodejce && $prodejce->nazev != "" ? ", prodejce: $prodejce->nazev" : "";
                    
                    if ($objednavkaSetup->nastupniMisto){
                        $out .= ", nástupní místo: ";
                        //vypis nastupni mista
                        if (!is_null($objednavka->nastupni_mista)) {
                            foreach ($objednavka->nastupni_mista as $nm)
                                $out .= "$nm, ";
                            $out = substr($out, 0, -2);
                        }
                        
                    }
                    

                    if ($objednavkaSetup->sluzby) {
                        $out .= $this->sluzbyLoop($objednavka);
                    }

                    $out .= "       </h4>";
                    $out .= "   </td>";
                    $out .= "</tr>";
                    

                    $out .= $this->headerUcastnici($zajezdSetup);
                }
                //Lada: ucastniky zobrazim pouze pokud byla zaskrtnuta objednavka
                if(in_array($objednavka->id_objednavka, $objednavkySelected)){    
                    $out .= $this->ucastniciLoop($objednavka, $index);
                }
            }
        }

        return $out;
    }

    private function headerUcastnici($zajezdSetup){        
        $out = "<tr>";
                    $out .= "   <td class='border2 strong' width='10px'></td>";
                    $out .= "   <td class='border2 strong'>jméno</td>";
                    $out .= $zajezdSetup->titul ? "<td class='border2 strong'>titul</td>" : "";
                    $out .= $zajezdSetup->datumNarozeni ? "<td class='border2 strong'>datum narození</td>" : "";
                    $out .= $zajezdSetup->rodneCislo ? "<td class='border2 strong'>rodné èíslo</td>" : "";
                    $out .= $zajezdSetup->cisloPasu ? "<td class='border2 strong'>èíslo pasu</td>" : "";
                    $out .= $zajezdSetup->adresa ? "<td class='border2 strong'>adresa</td>" : "";
                    $out .= $zajezdSetup->telefon ? "<td class='border2 strong'>telefon</td>" : "";
                    $out .= $zajezdSetup->email ? "<td class='border2 strong'>email</td>" : "";
                    $out .= "</tr>";
        return $out;
    }
    
    private function sluzbyLoop($objednavka)
    {
        $sluzby = $objednavka->sluzby;

        $out = "";

        if (!is_null($sluzby)) {
            foreach ($sluzby as $s) {
                $out .= "<div class='fNormal'>$s->pocet x $s->nazev_ceny - $s->castka $s->mena</div>";
            }
        }

        return $out;
    }

    /**
     * @param $objednavka tsObjednavka
     * @param $index
     * @return string
     */
    private function ucastniciLoop($objednavka, &$index)
    {
        $ucastnici = $objednavka->ucastnici;
        $filterValues = $this->model->getSerialFilter();
        $zajezdSetup = $filterValues->getZajezdSetup();
        $ucastniciSelected = $filterValues->getUcastniciIdsSelected();

        $out = "";

        if (!is_null($ucastnici)) {
            foreach ($ucastnici as $ucastnik) {
                if(!in_array($ucastnik->id, $ucastniciSelected))
                    continue;
                $adr = $ucastnik->adresa;

                $out .= "<tr>";

                $out .= "   <td class='border2' width='10px'>" . ($index++) . ".</td>";
                $out .= "   <td class='border2'>$ucastnik->prijmeni, $ucastnik->jmeno</td>";
                $out .= $zajezdSetup->titul ? "<td class='border2'>" . ($ucastnik->titul == "" ? "" : ", $ucastnik->titul") . "</td>" : "";
                $out .= $zajezdSetup->datumNarozeni ? "<td class='border2'>" . SeznamyUcastnikuUtils::czechDate($ucastnik->datum_narozeni) . "</td>" : "";
                $out .= $zajezdSetup->rodneCislo ? "<td class='border2'>$ucastnik->rodne_cislo</td>" : "";
                $out .= $zajezdSetup->cisloPasu ? "<td class='border2'>$ucastnik->cislo_pasu</td>" : "";
                $out .= $zajezdSetup->adresa ? "<td class='border2'>" . ($adr->ulice != "" ? "$adr->mesto, $adr->psc, $adr->ulice" : "") . "</td>" : "";
                $out .= $zajezdSetup->telefon ? "<td class='border2'>$ucastnik->telefon</td>" : "";
                $out .= $zajezdSetup->email ? "<td class='border2'>$ucastnik->email</td>" : "";

                $out .= "</tr>";
            }
        }

        return $out;
    }

}
