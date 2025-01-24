<?php

class FinancniPohybyViewPrehled extends FinancniPohybyView implements IFPObserver
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
        // note not interested in this model change
    }

    public function prehledChanged()
    {
        echo $this->head();
        echo $this->main();
        echo $this->foot();
    }

    private function head()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(FinancniPohybyModel::$core->show_all_allowed_moduls()), FinancniPohybyModel::$zamestnanec, FinancniPohybyModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        if (is_null($serialy))
            $out .= "<h2 class='red'>Nenalezeny ��dn� z�znamy vyhovuj�c� podm�nk�m.</h2>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(FinancniPohybyModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->prehled();

        return $out;
    }

    private function prehled()
    {
        $serialHolder = $this->model->getSerialHolder();
        $serialy = $serialHolder->getSerialList();
        $out = "";

        if (!is_null($serialy)) {
            $out .= "<h3>P�ehled finan�n�ch pohyb�</h3>";
            
            $out .= "<form method='post' action='?page=prehled-pdf' id='form-prehled' target='_blank'>";
            $out .= "   <table class='list'>";
            
            $out .= "<tr><td colspan='10' class='superheader' style=\"font-size:1.15em\"><b>Aplikovan� filtry:</b> ".$this->vypisFiltru();
            
            foreach ($serialy as $serial) {
                if (is_null($serial->getZajezdHolder()->getZajezdy()))
                    continue;

                $out .= "<tr><th colspan='10' class='superheader'>Seri�l: <a target='_blank' href='serial.php?id_serial=$serial->id&typ=serial&pozadavek=edit'>$serial->id</a> " . $serial->constructNazev() . "</th></tr>";

                $out .= $this->zajezdyLoop($serial->getZajezdHolder()->getZajezdy(), $serial->id);
            }

            $out .= "   </table>";
            $out .= "</form>";

            $out .= $this->souhrn();
        }

        return $out;
    }

    /*textovy vypis nastavenych poli filtru pro objednavky*/
    private function vypisFiltru(){
        $out = "";
        $serialFilter = $this->model->getSerialFilter();
        if($serialFilter->getObjednavkaOd() != "" and $serialFilter->getObjednavkaOd() != "0000-00-00"){
            $f[] = "Objedn�vka od:".CommonUtils::czechDate($serialFilter->getObjednavkaOd());
        }
        if($serialFilter->getObjednavkaDo() != "" and $serialFilter->getObjednavkaDo() != "0000-00-00"){
            $f[] = "Objedn�vka do:".CommonUtils::czechDate($serialFilter->getObjednavkaDo());
        }
        if($serialFilter->getRealizaceOd() != "" and $serialFilter->getRealizaceOd() != "0000-00-00"){
            $f[] = "Realizace objedn�vky od:".CommonUtils::czechDate($serialFilter->getRealizaceOd());
        }
        if($serialFilter->getRealizaceDo() != "" and $serialFilter->getRealizaceDo() != "0000-00-00"){
            $f[] = "Realizace objedn�vky do:".CommonUtils::czechDate($serialFilter->getRealizaceDo());
        }
        if(is_array($f)){
            $out = implode(", ", $f);
        }else{
            $out = "<i>��dn�</i>";
        }
        
        return $out;
    }
    /**
     * @param $zajezdy ZajezdEnt[]
     * @param $serialId
     * @return string
     */
    private function zajezdyLoop($zajezdy, $serialId)
    {
        $out = "";

        foreach ($zajezdy as $zajezd) {
            $objednavky = $zajezd->getObjednavkyHolder()->getObjednavky();
            
            $out .= "<tr>";
            $out .= "   <th colspan='10'>";
            $out .= "Z�jezd: <a target='_blank' href='serial.php?id_serial=$serialId&id_zajezd=$zajezd->id&typ=zajezd&pozadavek=edit'>$zajezd->id</a> " . $zajezd->constructNazev() .
                " [ " . CommonUtils::czechDate($zajezd->terminOd) .
                " - " . CommonUtils::czechDate($zajezd->terminDo) . " ]";
            $out .= "   </th>";
            $out .= "</tr>";

            $out .= $this->objednavkyLoop($objednavky, $zajezd);

            if(is_null($objednavky)) {
                $out .= "<tr>";
                $out .= "    <td></td>";
                $out .= "    <td colspan='2'>Tento z�jezd nem� �dn� objedn�vky.</td>";
                $out .= "</tr>";
            }
        }

        return $out;
    }

    /**
     * @param $objednavky ObjednavkaEnt[]
     * @return string
     */
    private function objednavkyLoop($objednavky, $zajezd)
    {
        $out = "";

        if (!is_null($objednavky)) {
            foreach ($objednavky as $objednavka) {
                if($zajezd->terminOd != $objednavka->termin_od or  $zajezd->terminDo != $objednavka->termin_do){
                    $terminObj = "[ " . CommonUtils::czechDate($objednavka->termin_od) . " - " . CommonUtils::czechDate($objednavka->termin_do) ." ]";
                }else{
                    $terminObj = "";
                }
                $datumObj = CommonUtils::czechDate($objednavka->datum_rezervace);
                $objednavajici = $objednavka->objednavajici;
                $platby = $objednavka->getPlatbyHolder()->getPlatby();
                $faktury = $objednavka->getFakturyHolder()->getFaktury();

                $out .= "<tr class='removable-obj subheader'>";
                $out .= "   <th></th><th colspan='9'>Objedn�vka <a target='_blank' href='objednavky.php?idObjednavka=$objednavka->id'>$objednavka->id</a> $terminObj datum rezervace: $datumObj,  objedn�vaj�c�: <a target='_blank' href='klienti.php?id_klient=$objednavajici->id&typ=klient&pozadavek=edit'>$objednavajici->jmeno $objednavajici->prijmeni</a></th>";
                $out .= "</tr>";

                //platby
                $out .= $this->platbyLoop($platby);

                //faktury
                $out .= $this->fakturyLoop($faktury);

                if(is_null($platby) && is_null($faktury)) {
                    $out .= "<tr>";
                    $out .= "    <td></td><td></td>";
                    $out .= "    <td>Tato objedn�vka nem� �dn� platby ani faktury.</td>";
                    $out .= "</tr>";
                }
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

        $out .= "<tr class='subheader'>";
        $out .= "    <th></th><th></th><th>Platby</th><th>id</th><th>��slo dokladu</th><th>��stka</th><th>vystaveno</th><th>splatnost do</th><th>uhrazeno</th><th>zp�sob �hrady</th>";
        $out .= "</tr>";

        foreach ($platby as $platba) {
            $out .= "<tr>";
            $out .= "    <td></td><td></td><td></td>";
            $out .= "    <td>$platba->id ($platba->typ_dokladu)</td>";
            $out .= "    <td>$platba->cislo_dokladu</td>";
            $out .= "    <td>$platba->castka</td>";
            $out .= "    <td>" . CommonUtils::czechDate($platba->vystaveno) . "</td>";
            $out .= "    <td>" . CommonUtils::czechDate($platba->splatit_do) . "</td>";
            $out .= "    <td>" . CommonUtils::czechDate($platba->splaceno) . "</td>";
            $out .= "    <td>$platba->zpusob_uhrady</td>";
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

        $out .= "<tr class='subheader'>";
        $out .= "    <th></th><th></th><th>Faktury</th><th>id</th><th>��slo faktury</th><th>��stka</th><th>vystaveno</th><th>splatnost do</th><th>uhrazeno</th><th></th>";
        $out .= "</tr>";

        foreach ($faktury as $faktura) {
            $out .= "<tr>";
            $out .= "    <td></td><td></td><td></td>";
            $out .= "    <td>$faktura->id</td>";
            $out .= "    <td>$faktura->cislo_faktury</td>";
            $out .= "    <td>$faktura->celkova_castka $faktura->mena</td>";
            $out .= "    <td>" . CommonUtils::czechDate($faktura->datum_vystaveni) . "</td>";
            $out .= "    <td>" . CommonUtils::czechDate($faktura->datum_splatnosti) . "</td>";
            $out .= "    <td>" . ($faktura->zaplaceno ? "<span class='green'>ANO</span>" : "<span class='red'>NE</span>") . "</td>";
            $out .= "    <td></td>";
            $out .= "</tr>";
        }


        return $out;
    }

    private function souhrn()
    {
        $serialHolder = $this->model->getSerialHolder();
        $zaSluzby = CommonUtils::formatPrice($serialHolder->calcZaSluzby(), 2);
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

        $out .= "<div class='list-add'>";
        $out .= "    <h4>Souhrn</h4>";
        $out .= "    <table class='list'>";
        $out .= "        <colgroup><col width='200px'></colgroup>";
        $out .= "        <tr>";
        $out .= "            <th>Celkem za slu�by</th>";
        $out .= "            <td>$zaSluzby K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Slevy - slu�by</th>";
        $out .= "            <td>$slevy K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Slevy - individu�ln�</th>";
        $out .= "            <td>$slevyIndividualni K�</td>";
        $out .= "        </tr>";        
        $out .= "        <tr>";
        $out .= "            <th>Provize</th>";
        $out .= "            <td>$provize K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Storna</th>";
        $out .= "            <td>$storna K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Celkem za z�jezdy</th>";
        $out .= "            <td>$celkemZaZajezdy K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Uhrazeno hotov�</th>";
        $out .= "            <td>$uhrazenoHotove K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Fakturov�no</th>";
        $out .= "            <td>$fakturovano K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Neuhrazen� faktury</th>";
        $out .= "            <td>$neuhrazeneFaktury K�</td>";
        $out .= "        </tr>";
        $out .= "        <tr>";
        $out .= "            <th>Neuhrazeno celkem</th>";
        $out .= "            <td>$celkemNeuhrazeno K�</td>";
        $out .= "        </tr>";
        $out .= "    </table>";
        $out .= "</div>";

        return $out;
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a href='?page=serialy'><< seznam z�jezd�</a>";
        $out .= "   <a href='' id='btn-zoprazit-fp-pdf'>zobrazit finan�n� pohyby pdf</a>";
        $out .= "</div>";

        return $out;
    }
}