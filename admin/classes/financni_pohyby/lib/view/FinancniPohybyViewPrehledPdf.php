<?php

class FinancniPohybyViewPrehledPdf extends FinancniPohybyView implements IFPObserver
{

    /**
     * @var FinancniPohybyModel
     */
    private $model;

    /**
     * @param $model FinancniPohybyModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerFPObserver($this);
    }

    public function serialListChanged()
    {
        //note not interested in this model change
    }

    public function prehledPdfChanged()
    {
        $this->pdf();
    }

    public function prehledChanged()
    {
        // note not interested in this model change
    }

    private function pdf()
    {
        $html = "";

        $html .= $this->header();
        $html .= $this->prehled();

        $this->model->generatePdf($html);
    }

    private function header()
    {
        $out = "";

        $out .= "<table cellpadding='0' cellspacing='8'  width='810'>";
        $out .= "   <tr>";
        $out .= "       <th colspan='3' style='padding-left:0;padding-right:0;'>";
        $out .= "           <h1>Finanèní pohyby</h1>";
        $out .= "       </th>";
        $out .= "   </tr>";
        $out .= "</table>";

        return $out;
    }

    private function prehled()
    {
        $serialHolder = $this->model->getSerialHolder();
        $serialy = $serialHolder->getSerialList();
        $out = "";

        if (is_null($serialy)) {
            $out .= "<div class='sys-msg error align-center'>Nenalezeny žádné záznamy vyhovující podmínkám.</div>";
        } else {
            foreach ($serialy as $serial) {
                if (is_null($serial->getZajezdHolder()->getZajezdy()))
                    continue;

                $out .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;border: 2px solid black; margin:8px;width: 100%;'>";
                $out .= "   <tr>";
                $out .= "       <td class='b-b-1-solid-black p-a-10' colspan='10'><h2>Seriál: $serial->id " . $serial->constructNazev() . "</h2></td>";
                $out .= "   </tr>";
                $out .= "       <tr><td></td></tr>";

                $out .= $this->zajezdyLoop($serial->getZajezdHolder()->getZajezdy());

                $out .= "</table>";
            }

            $out .= $this->souhrn();
        }

        return $out;
    }

    /**
     * @param $zajezdy ZajezdEnt[]
     * @return string
     */
    private function zajezdyLoop($zajezdy)
    {
        $out = "";

        foreach ($zajezdy as $zajezd) {
            $objednavky = $zajezd->getObjednavkyHolder()->getObjednavky();

            $out .= "<tr>";
            $out .= "   <td class='b-a-1-solid-black p-lr-10 p-tb-5' valign='top' colspan='10'>";
            $out .= "       <h3>Zájezd: $zajezd->id" . $zajezd->constructNazev() .
                " [" . CommonUtils::czechDate($zajezd->terminOd) .
                " - " . CommonUtils::czechDate($zajezd->terminDo) . "]</h3>";
            $out .= "   </td>";
            $out .= "</tr>";

            $out .= $this->objednavkyLoop($objednavky);
        }

        return $out;
    }

    /**
     * @param $objednavky ObjednavkaEnt[]
     * @return string
     */
    private function objednavkyLoop($objednavky)
    {
        $out = "";

        if (!is_null($objednavky)) {
            foreach ($objednavky as $objednavka) {
                $objednavajici = $objednavka->objednavajici;
                $platby = $objednavka->getPlatbyHolder()->getPlatby();
                $faktury = $objednavka->getFakturyHolder()->getFaktury();

                $out .= "<tr>";
                $out .= "   <th class='b-b-1-solid-black'></th><th class='b-b-1-solid-black p-a-5 ta-left' colspan='9'>Objednávka $objednavka->id [ " . CommonUtils::czechDate($objednavka->datum_rezervace) . " ] - objednávající: $objednavajici->jmeno $objednavajici->prijmeni</th>";
                $out .= "</tr>";

                //platby
                $out .= $this->platbyLoop($platby);

                //faktury
                $out .= $this->fakturyLoop($faktury);
            }
        }

        return $out;
    }

    /**
     * @param PlatbaEnt[] $platby
     * @return string|void
     */
    private function platbyLoop($platby)
    {
        if (is_null($platby))
            return "";

        $out = "";

        $out .= "<tr>";
        $out .= "    <th class='b-b-1-solid-black' colspan='2'></th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>Platby</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>id</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>èíslo dokladu</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>èástka</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>vystaveno</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>splatnost do</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>uhrazeno</th>";
        $out .= "    <th class='b-b-1-solid-black p-a-4'>zpùsob úhrady</th>";
        $out .= "</tr>";

        foreach ($platby as $platba) {
            $out .= "<tr>";
            $out .= "    <td class='b-br-1-solid-black p-a-4' colspan='3'></td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$platba->id ($platba->typ_dokladu)</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$platba->cislo_dokladu</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$platba->castka</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>" . CommonUtils::czechDate($platba->vystaveno) . "</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$platba->splatit_do</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>" . CommonUtils::czechDate($platba->splaceno) . "</td>";
            $out .= "    <td class='ta-center b-b-1-solid-black p-a-4'>$platba->zpusob_uhrady</td>";
            $out .= "</tr>";
        }


        return $out;
    }

    /**
     * @param FakturaEnt[] $faktury
     * @return string|void
     */
    private function fakturyLoop($faktury)
    {
        if (is_null($faktury))
            return "";

        $out = "";

        $out .= "<tr>";
        $out .= "    <th class='b-b-1-solid-black'></th>";
        $out .= "    <th class='b-b-1-solid-black'></th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>Faktury</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>id</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>èíslo faktury</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>èástka</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>vystaveno</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>splatnost do</th>";
        $out .= "    <th class='b-br-1-solid-black p-a-4'>uhrazeno</th>";
        $out .= "    <th class='b-b-1-solid-black'></th>";
        $out .= "</tr>";

        foreach ($faktury as $faktura) {
            $out .= "<tr>";
            $out .= "    <td class='b-br-1-solid-black p-a-4' colspan='3'></td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$faktura->id</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$faktura->cislo_faktury</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>$faktura->celkova_castka $faktura->mena</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>" . CommonUtils::czechDate($faktura->datum_vystaveni) . "</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>" . CommonUtils::czechDate($faktura->datum_splatnosti) . "</td>";
            $out .= "    <td class='ta-center b-br-1-solid-black p-a-4'>" . ($faktura->zaplaceno ? "<span class='green'>ANO</span>" : "<span class='red'>NE</span>") . "</td>";
            $out .= "    <td class='ta-center b-b-1-solid-black p-a-4'></td>";
            $out .= "</tr>";
        }


        return $out;
    }

    private function souhrn()
    {
        $serialHolder = $this->model->getSerialHolder();
        $zaSluzbyNoSlevy = CommonUtils::formatPrice($serialHolder->calcZaSluzbyNoSlevy(), 2);
        $slevy = CommonUtils::formatPrice($serialHolder->calcSlevy(), 2);
        $slevyIndividualni = CommonUtils::formatPrice($serialHolder->calcIndividualSlevy(), 2);
        $provize = CommonUtils::formatPrice($serialHolder->calcProvize(), 2);
        $storna = CommonUtils::formatPrice($serialHolder->calcStorna(), 2);
        $celkemZaZajezdy = CommonUtils::formatPrice($serialHolder->calcCelkemZaZajezdy(), 2);
        $uhrazenoHotove = CommonUtils::formatPrice($serialHolder->calcUhrazenoHotove(), 2);
        $fakturovano = CommonUtils::formatPrice($serialHolder->calcFakturovano(), 2);
        $neuhrazeneFaktury = CommonUtils::formatPrice($serialHolder->calcNeuhrazeneFaktury(), 2);
        $celkemNeuhrazeno = CommonUtils::formatPrice($serialHolder->calcCelkemNeuhrazeno(), 2);

        $out = "";

        $out .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;width: 100%;'>";
        $out .= "    <tr>";
        $out .= "        <td colspan='2'><h2>Souhrn</h2></td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Celkem za služby</th>";
        $out .= "        <td class='align-left border2'>$zaSluzbyNoSlevy Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Slevy</th>";
        $out .= "        <td class='align-left border2'>$slevy Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Slevy - individuální</th>";
        $out .= "        <td class='align-left border2'>$slevyIndividualni Kè</td>";
        $out .= "    </tr>";        
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Provize</th>";
        $out .= "        <td class='align-left border2'>$provize Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Storna</th>";
        $out .= "        <td class='align-left border2'>$storna Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Celkem za zájezdy</th>";
        $out .= "        <td class='align-left border2'>$celkemZaZajezdy Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Uhrazeno hotovì</th>";
        $out .= "        <td class='align-left border2'>$uhrazenoHotove Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Fakturováno</th>";
        $out .= "        <td class='align-left border2'>$fakturovano Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Neuhrazené faktury</th>";
        $out .= "        <td class='align-left border2'>$neuhrazeneFaktury Kè</td>";
        $out .= "    </tr>";
        $out .= "    <tr>";
        $out .= "        <th class='align-left border2'>Neuhrazeno celkem</th>";
        $out .= "        <td class='align-left border2'>$celkemNeuhrazeno Kè</td>";
        $out .= "    </tr>";
        $out .= "</table>";

        return $out;
    }
}