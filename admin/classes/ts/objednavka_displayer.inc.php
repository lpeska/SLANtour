<?php

//note namichane model a view dohromady
class ObjednavkaDisplayer
{
    const ZALOHA_DATE_TRESHOLD = 3;

    private $id_objednavka;
    private $dataObjednavajici;
    private $dataObjednavka;
    private $dataPlatby;
    private $dataProdejce;
    private $dataProvize;
    private $dataSleva;
    private $dataSluzby;
    private $dataStaticDescription;
    private $dataVystavil;
    private $dataZajezd;
    private $dataCentralniData;
    private $dataZaloha;
    private $dataDoplatek;

    /**
     * @var ObjednavkaEnt
     */
    private $dataObjednavkaGlobal;

    function __construct($idObjednavka)
    {
        $this->id_objednavka = $idObjednavka;

        //note objednavka z globalniho modelu
        $this->dataObjednavkaGlobal = ObjednavkyDAO::readObjednavkaDetailsById($idObjednavka);

        $this->dataObjednavajici = ObjednavkaDAO::dataObjednavajici($idObjednavka);
        $this->dataOsoby = ObjednavkaDAO::dataOsoby($idObjednavka);
        $this->dataObjednavajiciOrg = ObjednavkaDAO::dataObjednavajiciOrg($idObjednavka);
        $this->dataObjednavka = ObjednavkaDAO::dataObjednavka($idObjednavka);
        $this->dataPlatby = ObjednavkaDAO::dataPlatby($idObjednavka);
        $this->dataProdejce = ObjednavkaDAO::dataProdejce($idObjednavka);
        $this->dataProvize = ObjednavkaDAO::dataProvize($idObjednavka);
        $this->dataSleva = ObjednavkaDAO::dataSleva($idObjednavka);
        $this->dataSluzby = ObjednavkaDAO::dataSluzby($idObjednavka);
        $this->dataStaticDescription = ObjednavkaDAO::dataStaticDescription($idObjednavka);
        $this->dataVystavil = ObjednavkaDAO::dataVystavil($idObjednavka);
        $this->dataZajezd = ObjednavkaDAO::dataZajezd($idObjednavka);
        $this->dataCentralniData = ObjednavkaDAO::dataCentralniData();
        $this->dataZaloha = ObjednavkaDAO::dataZaloha($idObjednavka);
        $this->dataDoplatek = ObjednavkaDAO::dataDoplatek($idObjednavka);
    }

    //region COMPUTE *******************************************************************************************

    /**
     * spocte spravne celkovou cenu za sluzbu (v nekterych pripadech je treba ji nasobit poctem noci
     * @param type $castka
     * @param type $pocet
     * @param int $pocet_noci
     * @param int|\type $use_pocet_noci identifikator zda se ma castka nasobit poctem noci
     * @return type
     */
    private function computePrice($castka, $pocet, $pocet_noci, $use_pocet_noci = 0)
    {
        if ($pocet_noci == 0) {
            $pocet_noci = 1;
        }
        if ($use_pocet_noci != 0) {
            return $castka * $pocet * $pocet_noci;
        } else {
            return $castka * $pocet;
        }
    }

    private function computeServicesPrice()
    {
        $totalPrice = 0;
        foreach ($this->dataSluzby as $value) {
            $totalPrice += $this->computePrice($value->castka, $value->pocet, UtilsTS::calculate_pocet_noci($this->dataZajezd->terminOd, $this->dataZajezd->terminDo), $value->use_pocet_noci);
        }

        return $totalPrice;
    }

    private function computeAlreadyPaid()
    {
        $alreadyPaid = 0;
        foreach ($this->dataPlatby as $value) {
            $alreadyPaid += $value->castka;
        }
        return $alreadyPaid;
    }

    private function computeDiscount()
    {
        $discount = 0;
        $slevy = $this->dataObjednavkaGlobal->getSlevaHolder()->getObjednaneSlevy();
        $zakladniCenaObjednavky = $this->dataObjednavkaGlobal->calcZaSluzbyZakladni();
        $pocetOsob = $this->dataObjednavkaGlobal->pocet_osob;
        
        foreach ((array)$slevy as $sleva) {
            $discount += $sleva->calcCelkovaCastkaSlevy($zakladniCenaObjednavky, $pocetOsob);
        }
        
        return $discount;
    }

    private function computeTotalPrice()
    {
        $servicesPrice = $this->computeServicesPrice();
        $discount = $this->computeDiscount();

        return $servicesPrice - $discount + $this->dataObjednavka->storno_poplatek;
    }

    private function computeDnuDoOdjezdu()
    {
        $now = new DateTime($this->dataObjednavka->datum_rezervace);
        $ref = new DateTime($this->dataZajezd->terminOd . " 00:00:00");
        $diff = $now->diff($ref, true);
        $dnu_do_odjezdu = $diff->days;
        return $dnu_do_odjezdu;
    }

    private function computeFirstZaloha()
    {
        $dnu_do_odjezdu = $this->computeDnuDoOdjezdu();
        foreach ($this->dataZaloha as $row) {
            if ($dnu_do_odjezdu >= $row->prodleva) {
                return $this->computeZaloha($row);
            }
        }

        return 0;
    }

    private function computeLastZaloha()
    {
        $zaloha = 0;
        foreach ($this->dataZaloha as $row) {
            $zaloha = $this->computeZaloha($row);
        }
        return $zaloha;
    }

    private function computeZaloha($row)
    {
        if ($row->castka == 0) {
            $totalPrice = $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky();
            $zaloha = round($totalPrice * $row->procento / 100);
        } else {
            $zaloha = $row->castka;
        }
        return $zaloha;
    }

    private function computeFirstZalohaDate()
    {
        return date('d-m-Y', strtotime($this->dataObjednavka->datum_rezervace . " + " . self::ZALOHA_DATE_TRESHOLD . " days"));
    }

    private function computeNextZalohaDate($prodleva)
    {
        return date('d-m-Y', strtotime($this->dataZajezd->terminOd . " - " . $prodleva . " days"));
    }

    private function computeDoplatek()
    {
        $totalPrice = $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky();
        $zaloha = $this->computeLastZaloha();
        if ($this->dataObjednavka->k_uhrade_zaloha != 0 and $this->dataObjednavka->k_uhrade_zaloha_datspl != "0000-00-00" ){
            //pokud je rucne nastavena jina zaloha, ma prednost
            $zaloha = $this->dataObjednavka->k_uhrade_zaloha; 
        }
        
        $zaplaceno = $this->computeAlreadyPaid();

        if($zaloha >= $zaplaceno){
            $doplatek = $totalPrice - $zaloha;
        }else{
            $doplatek = $totalPrice - $zaplaceno;
        }
      /*
       * Lada: cely kod je nejaky divny, nesedi u situace kdy se zaplatila i cast doplatku
        $doplatek = $totalPrice - $zaloha;

        //provedl klient nejakou platbu?
        if ($zaplaceno > 0) {
            $zalohaZbyvaZaplatit = max(0, $zaloha - $zaplaceno);
            $doplatek = $totalPrice - $zalohaZbyvaZaplatit - $zaplaceno;
        }
       * 
       */

        return $doplatek;
    }

    private function computeDoplatekDate($prodleva)
    {
        return date('d-m-Y', strtotime($this->dataZajezd->terminOd . ' - ' . $prodleva . ' days'));
    }

    private function computePlatbaObjednavkyDate()
    {
        $platbaObjednavkyDate = date('d-m-Y', strtotime($this->dataObjednavka->datum_rezervace . ' + 3 days'));
        if (strtotime($platbaObjednavkyDate) > strtotime(date('d-m-Y', strtotime($this->dataZajezd->terminOd . ' - 1 days')))) {
            $platbaObjednavkyDate = date('d-m-Y', strtotime($this->dataZajezd->terminOd . ' - 1 days'));
        }
        return $platbaObjednavkyDate;
    }

    private function computeStornoToReturn()
    {
        return $this->computeAlreadyPaid() - $this->dataObjednavka->storno_poplatek;
    }

    //endregion

    public function getZajezd()
    {
        $html = "<tr>
                    <td valign='top' colspan='4' style='padding-left:0;padding-right:0;padding-bottom:8px;'><h2>Zájezd / objednané služby</h2></td>
                </tr>";

        if ($this->dataObjednavka->stav == Rezervace_library::$STAV_STORNO) {
            $html .= "<tr>
                        <td valign='top' colspan='3' style='padding-left:0;padding-right:0;padding-bottom:8px;'>Objednávka byla <strong>stornována</strong> dne: <strong>" . UtilsTS::change_date_en_cz($this->dataObjednavka->storno_datum) . "</strong></td>
                    </tr>";
        }

        $html .= "<tr>
                    <td class='border2' valign='top' colspan='4'  style='width:60%'>
                            <strong>" . $this->dataZajezd->nazevSerialu . "</strong>
                    </td>
                    <td nowrap class='border2' valign='bottom' align='right' colspan='2'  width='190'><b>TERMÍN: </b>"
            . (UtilsTS::czechDate($this->dataZajezd->terminOd) . " - " . UtilsTS::czechDate($this->dataZajezd->terminDo)) . "</td>
                    <td nowrap class='border2' valign='bottom' align='right' width='100'><b>POÈET NOCÍ:</b> " . UtilsTS::calculate_pocet_noci($this->dataZajezd->terminOd, $this->dataZajezd->terminDo). "</td>
                </tr>";
        return $html;
    }

    public function getSluzbyForFaktury()
    {
        $isDiscount = $this->dataSleva->nazev_slevy != "";
        $html = "
            <table border=\"0\" style=\"font-size:1.0em;border-collapse: collapse; margin:8px;\"> 
            <tr style=\"border-top: 2px solid black; border-bottom: 2px solid black; \">
                        <th align=\"left\" style=\"width:400px;border-right:none;border-left:none;\">Název služby</th>
                        <th align=\"right\" style=\"border-right:none;border-left:none;\">Jedn. cena</th>
                        <th align=\"right\" style=\"border-right:none;border-left:none;\">Množství</th>
                        <th align=\"right\" style=\"border-right:none;border-left:none;\">Celkem</th>
                    </tr>";
        $lenght = count((array)$this->dataSluzby);
        $count = 0;

        foreach ($this->dataSluzby as $value) {

            $price = $this->computePrice($value->castka, $value->pocet, UtilsTS::calculate_pocet_noci($this->dataZajezd->terminOd, $this->dataZajezd->terminDo), $value->use_pocet_noci);
            $html .= "<tr style=\"border-bottom: 1px solid black;\">	
                          <td style=\"border-right:none;border-left:none;\">" . $value->nazev_ceny . "</td>
                          <td align=\"right\" style=\"border-right:none;border-left:none;\">" . $value->castka . " " . $value->mena . "</td>
                          <td align=\"right\" style=\"border-right:none;border-left:none;\">" . $value->pocet . "</td>
                          <td align=\"right\" style=\"border-right:none;border-left:none;\">" . $price . " " . $value->mena . "</td>								
                     </tr>";
            $count++;
        }

        foreach ($this->dataSleva as $value) {
            $currency = $this->getCurrency();
            if($value->mena_slevy == "Kè"){
                $pocet = "---";
                $castka = $value->velikost_slevy;                        
            }else if($value->mena_slevy == "%"){
                $pocet = "---";
                $castka = $value->castka_slevy; 
            }else{
                $pocet = $this->dataObjednavkaGlobal->pocet_osob;
                $castka = $value->castka_slevy; 
            }
            $html .= "<tr style=\"border-bottom: 1px solid black;\">	
                          <td style=\"border-right:none;border-left:none;\">" . $value->nazev_slevy . "</td>
                          <td align=\"right\" style=\"border-right:none;border-left:none;\">" . $value->velikost_slevy . " " . $value->mena_slevy . "</td>
                          <td align=\"right\" style=\"border-right:none;border-left:none;\"> $pocet </td>
                          <td align=\"right\" style=\"border-right:none;border-left:none;\">- $castka " . $currency . "</td>							
                     </tr>";
        }
        $html .= "\n</table>";
        return $html;
    }

    public function getSluzby($type="")
    {
        $isDiscount = 0;
        if (count((array)$this->dataSleva) > 0) {
            $isDiscount = 1;
        }

        $html = "<tr>
                <th colspan=\"4\" class=\"border2l border1b\" align=\"left\"  width=\"540\">Název služby</th>
                <th align=\"right\" class=\"border2l border1b\" width=\"100\">Cenový rozpis</th>
                <th align=\"right\" class=\"border2l border1b\">Poèet</th>
                <th align=\"right\" class=\"border2l border2r border1b\">Celkem</th>
              </tr>";
        
        $lenght = count((array)$this->dataSluzby);
        $count = 0;
        $botBorder = "borderDotted";
        $size_static = count((array)$this->dataStaticDescription);
        
        if(is_array($this->dataSluzby)){
            foreach ($this->dataSluzby as $value) {
                if ($count == $lenght - 1 && !$isDiscount && ($type != ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA || !$size_static) ) {
                    $botBorder = "border2b";
                }
                $price = $this->computePrice($value->castka, $value->pocet, (UtilsTS::calculate_pocet_noci($this->dataZajezd->terminOd, $this->dataZajezd->terminDo)), $value->use_pocet_noci);
                $html .= "<tr>
                              <td class=\"border2l " . $botBorder . "\" colspan=\"4\">" . $value->nazev_ceny . "</td>
                              <td class=\"border2l " . $botBorder . "\" align=\"right\">" . $value->castka . " " . $value->mena . "</td>
                              <td class=\"border2l " . $botBorder . "\" align=\"right\">" . $value->pocet . "</td>
                              <td class=\"border2l border2r " . $botBorder . "\" align=\"right\">" . $price . " " . $value->mena . "</td>
                         </tr>";
                $count++;
            }
        }

        //note pouzit globalni model
        $objednaneSlevy = $this->dataObjednavkaGlobal->getSlevaHolder()->getObjednaneSlevy();
        $zakladniCenaObjednavky = $this->dataObjednavkaGlobal->calcZaSluzbyZakladni();
        $pocetOsob = $this->dataObjednavkaGlobal->pocet_osob;
        if(is_array($objednaneSlevy)){
            foreach ($objednaneSlevy as $sleva) {
                $currency = $this->getCurrency();
                $html .= "<tr style='border-bottom: 1px solid black;'>
                              <td class='border2l $botBorder' colspan='4'>" . $sleva->nazev_slevy . "</td>
                              <td  class='border2l $botBorder' align='right'>" . $sleva->castka . " " . $sleva->mena . "</td>
                              <td  class='border2l $botBorder' align='right'> --- </td>
                              <td class='border2l border2r $botBorder' align='right'>- " . $sleva->calcCelkovaCastkaSlevy($zakladniCenaObjednavky, $pocetOsob) . " $currency</td>
                         </tr>";
            }
        }

        
        foreach ($this->dataStaticDescription as $i => $value) {
            if ($i == $size_static-1) {
                $botBorder = "border2b";
            } else {
                $botBorder = "borderDotted";
            }
            if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
                $html .= "<tr style=\"border-bottom: 1px solid black;\">
                          <td class=\"border2l border2r " . $botBorder . "\" colspan=\"4\">" . $value->nazev_static_description . "</td>
                          <td class=\"border2l border2r " . $botBorder . "\" >
                          <td class=\"border2l border2r " . $botBorder . "\" >
                          <td class=\"border2l border2r " . $botBorder . "\" >
                     </tr>";
            }else{
                $html .= "<tr style=\"border-bottom: 1px solid black;\">
                          <td class=\"border2l border2r " . $botBorder . "\" colspan=\"4\">" . $value->nazev_static_description . "</td>
                     </tr>";
            }
            
        }

        return $html;
    }

    public function getPlatby($type="")
    {
        $lenght = count((array)$this->dataPlatby);
        $alreadyPaid = 0;

        if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
            $html = "";
        }else{  
            $html = "<tr>
                        <td valign=\"top\" colspan=\"4\" style=\"padding-left:0;padding-right:0;padding-bottom:8;\"><h2>Platby</h2></td>
                    </tr>";
            if ($lenght == 0) {
                $html .= "<tr>
                            <td valign=\"top\" colspan=\"4\" style=\"padding-left:0;padding-right:0;padding-bottom:8;\">žádné</td>
                        </tr>";
                return $html;
            }
            $html .= "<tr>
                          <th align=\"left\" class=\"border2\">Èíslo dokladu</th>
                          <th align=\"left\" class=\"border2\">Datum úhrady</th>
                          <th align=\"left\" class=\"border2\">Typ dokladu</th>
                          <th align=\"left\" class=\"border2\">Zpùsob úhrady</th>
                          <th align=\"left\" class=\"border2\" align=\"right\">Èástka</th>
                     </tr>";
        }
        if(is_array($this->dataPlatby)){
            $pocet_plateb = sizeof((array)$this->dataPlatby);
            if($pocet_plateb < 2){
                $pocet_plateb = 2;                
            }            
            $text_platba = "<td colspan=\"3\" rowspan=\"$pocet_plateb\"  class=\"border2\"  width=\"250\">
                                * Zákazník je povinen uhradit platbu CK. Úhradu provede sám (hotovì,
                                složenkou na úèet nebo adresu, bankovním pøevodem), nebo udìlí plnou moc
                                prodejci k provedení úhrady plateb. Za øádnou a vèasnou úhradu platby
                                odpovídá cestovní kanceláøi vždy zákazník.
                            </td>";
            foreach ($this->dataPlatby as $i => $value) {
                $alreadyPaid += $value->castka;
                if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
                    $html .= "<tr>	
                              <td class=\"border2l borderDotted border1r\">" . UtilsTS::czechDate($value->splaceno) . "</td>
                              <td class=\"borderDotted border1r\" >" . $value->castka . " " . $this->getCurrency() . "</td>       
                              <td class=\"borderDotted border1r\">" . $value->typ_dokladu . "</td>
                              <td class=\"borderDotted border2r\">" . $value->zpusob_uhrady . "</td>
                                                         
                         ";
                    if($i==0){
                        $html .= $text_platba;
                    }
                }else{ 
                    $html .= "<tr>	
                              <td class=\"border2\">" . $value->cislo_dokladu . "</td>
                              <td class=\"border2\">" . UtilsTS::czechDate($value->splaceno) . "</td>
                              <td class=\"border2\">" . $value->typ_dokladu . "</td>
                              <td class=\"border2\">" . $value->zpusob_uhrady . "</td>
                              <td class=\"border2\" align=\"right\">" . $value->castka . " " . $this->getCurrency() . "</td>
                         </tr>";
                }    
            }
            if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA and sizeof((array)$this->dataPlatby)==1){
                $html .= "<tr>	
                              <td class=\"border2l borderDotted border1r\">
                              <td class=\"borderDotted border1r\">
                              <td class=\"borderDotted border1r\">
                              <td class=\"borderDotted border2r\">";
            }
        }
        if($lenght == 0 and $type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA ){
                $html .= "<tr>	
                              <td class=\"border2l borderDotted border1r\">
                              <td class=\"borderDotted border1r\">
                              <td class=\"borderDotted border1r\">
                              <td class=\"borderDotted border2r\">
                              $text_platba"
                        . "<tr>	
                              <td class=\"border2l borderDotted border1r\">
                              <td class=\"borderDotted border1r\">
                              <td class=\"borderDotted border1r\">
                              <td class=\"borderDotted border2r\">";
            
        }
        
        return $html;
    }

    public function getUhradit($type="")
    {
        $zaplaceno = $this->computeAlreadyPaid();
        $totalPrice = $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky(); //note '$totalPrice = $this->computeTotalPrice();' nahrazeno globalnim modelem
        $html = "";

        if ($zaplaceno >= $totalPrice)
            return $html;

        if ($this->dataObjednavka->stav == Rezervace_library::$STAV_STORNO)
            return $html;

        if($type==ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
            $nadpis = "Rozpis Plateb";
            $textFirstCell = "<strong>Èíslo úètu:</strong> " . $this->dataCentralniData["bankovni_spojeni"] . ", <strong>variabilní symbol:</strong> " . $this->id_objednavka . "";
            $textBeforeTable = "";
        }else{
            $nadpis = "K úhradì";
            $textFirstCell = "";
            $textBeforeTable = "<strong>Èíslo úètu:</strong> " . $this->dataCentralniData["bankovni_spojeni"] . ", <strong>variabilní symbol:</strong> " . $this->id_objednavka . "";
        }
        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >  ";
        $html .= "  <tr>
                        <td valign='top' colspan='3' style='padding-left:0;padding-right:0;padding-bottom:8px;'><h2>$nadpis</h2></td>
                    </tr>
                    <tr>
                        <td valign='top' colspan='3' style='padding-left:0;padding-right:0;padding-bottom:8px;'>$textBeforeTable</td>
                    </tr>";
        $html .= "  <tr>
                        <th align='left' class='border2' style='width:65%'>$textFirstCell</th>
                        <th align='left' class='border2' style='width:15%'>Datum splatnosti</th>
                        <th align='right' class='border2'>Èástka</th>
                    </tr>";
        
        //zalohu zobraz jen pokud neni nulova
        //Lada: predelan box k uhrade tak, aby zobrazoval soucasny skutecny stav 
        //(tj. pokud klient nedordzel presny rozpis plateb, zobrazi se kolik by mel uhradit v danou chvili)
        //navic pokud je nektery termin (zaloha/doplatek) prosly, zobrazi se cervene
        $dnu_do_odjezdu = $this->computeDnuDoOdjezdu();
        $cislo_zalohy = 0;
        $sum_zaloha = 0;
        $currency = $this->getCurrency();
        $rozpis_plateb = array();

        /*Pokud je ruène nastavená, je tøeba zobrazit zálohu
         *  terminy zalohy se pocitaji automaticky dle smluvnich podminek (ty jsou v dataZaloha
         *  ale muzou byt take rucne zmeneny u objednavky
         *  problem nastava u objednavek tesne pred zajezdem, kdy chceme zmenit castku a termin, ale vsechny zalohy dle sml. podminek uz jsou prosle
         *  dalo by se resit jen zmenou terminu doplatku, ale vysvetluj to holkam v praci:)       
         */
        $platna_zaloha = false;
        foreach ($this->dataZaloha as $zaloha) {
            if ($dnu_do_odjezdu >= $zaloha->prodleva) {//platná záloha                
                $platna_zaloha = true;
            }
        }   
        
        if ($this->dataObjednavka->k_uhrade_zaloha != 0 and $this->dataObjednavka->k_uhrade_zaloha_datspl != "0000-00-00" and !$platna_zaloha){            
            end($this->dataZaloha)->prodleva = 0;            
        }
        $last_prodleva = 0;
        foreach ($this->dataZaloha as $zaloha) {
            if ($dnu_do_odjezdu >= $zaloha->prodleva) {//platná záloha                
                $cislo_zalohy++;
                $zalohaCastka = $this->computeZaloha($zaloha);
                
                if ($cislo_zalohy == 1) {
                    $zalohaDate = $this->computeFirstZalohaDate();
                    $this->correctKUhradeData($foo, $zalohaCastka, $foo, $zalohaDate, $foo, $foo);
                }
                $zalohaCastka -= $sum_zaloha;
                $sum_zaloha += $zalohaCastka;
                                
                
                if($zaplaceno >= $sum_zaloha){
                    //zaloha uz byla zaplacena, nebudeme ji zobrazovat v poli "k uhrade"
                    $zobrazitZalohu = false;
                    $zalohaCastkaZaplatit = 0;
                }else if($zaplaceno >= ($sum_zaloha-$zalohaCastka)){
                    //ze soucasne zalohy uz bylo neco malo zaplaceno
                    $zobrazitZalohu = true;
                    $zalohaCastkaZaplatit = $sum_zaloha - $zaplaceno;                    
                }else{
                    //ze soucasne zalohy nebylo jeste nic zaplaceno
                    $zobrazitZalohu = true;
                    $zalohaCastkaZaplatit = $zalohaCastka;
                }                                                        
                if($zobrazitZalohu){
                    if ($cislo_zalohy != 1) {
                        $zalohaDate = $this->computeNextZalohaDate($last_prodleva);
                    }    
                    if(strtotime($zalohaDate)<=strtotime(Date("Y-m-d"))){
                        $style = " warning";
                    }else{
                        $style = "";
                    }
                    
                    $rozpis_plateb[$cislo_zalohy] = array("text" => "Záloha", "datum" => $zalohaDate, "castka" => $zalohaCastkaZaplatit, "mena" => $currency, "styl" => $style);
                    if($cislo_zalohy != 1){
                        //zkontrolujeme, zda dve po sobe jdouci platby z rozpisu se neprekryvaji s daty
                        $datePri = $rozpis_plateb[($cislo_zalohy-1)]["datum"];
                        $dateSec = $rozpis_plateb[$cislo_zalohy]["datum"];
                        if(strtotime($datePri) >= strtotime($dateSec)){
                            //datum prvni zalohy je vetsi nez datum druhe => spojime je obe s datem prvni zalohy
                            unset($rozpis_plateb[$cislo_zalohy]);
                            $rozpis_plateb[($cislo_zalohy-1)]["castka"] += $zalohaCastkaZaplatit;
                        }
                    }                    
                }
                
                $last_prodleva = $zaloha->prodleva;
            }
        }
        print_r($rozpis_plateb)    ;
        $doplatekDate = $this->computeDoplatekDate($last_prodleva);
        $doplatek = $this->computeDoplatek();
        $this->correctKUhradeData($foo, $foo, $foo, $foo, $doplatekDate, $foo);
        //TODO: zde si muzeme zavlect nekonzistenci upravou ceny doplatku (zrusena moznost menit vysi doplatku) - myslim ze bychom tu nemeli dovolit rucni zmenu vyse doplatku!!

        if(strtotime($doplatekDate)<=strtotime(Date("Y-m-d"))){
            $style = " warning";
        }else{
            $style = "";
        }
        if ($cislo_zalohy != 0) {
            $rozpis_plateb[($cislo_zalohy+1)] = array("text" => "Doplatek", "datum" => $doplatekDate, "castka" => $doplatek, "mena" => $currency, "styl" => $style);
            //zkontrolujeme, zda dve po sobe jdouci platby z rozpisu se neprekryvaji s daty
            $datePri = $rozpis_plateb[($cislo_zalohy)]["datum"];
            $dateSec = $doplatekDate;
            if(strtotime($datePri) >= strtotime($dateSec)){
                //datum zalohy je vetsi nez datum doplatku => prepiseme to na platbu cele castky / sloucime jednu zalohu s doplatkem s terminem zalohy
                //jako Platba objednávky to nazveme pouze pokud neni nic zaplaceno
                unset($rozpis_plateb[($cislo_zalohy+1)]);
                if($cislo_zalohy == 1){
                    //byla jedna zaloha => celkova cena
                    //$totalPriceDate = $this->computePlatbaObjednavkyDate();
                    //$this->correctKUhradeData($foo, $foo, $foo, $foo, $totalPriceDate, $foo);
                    if($zaplaceno <= 0){
                       $rozpis_plateb[$cislo_zalohy]["text"] = "Platba objednávky"; 
                    }else{
                       $rozpis_plateb[$cislo_zalohy]["text"] = "Doplatek objednávky";  
                    }
                    
                    $rozpis_plateb[$cislo_zalohy]["castka"] = ($totalPrice - $zaplaceno);

                    
                }else{
                    //byly dve nebo vice zaloh - sloucime s doplatkem
                    $rozpis_plateb[$cislo_zalohy]["text"] = "Doplatek";
                    $rozpis_plateb[$cislo_zalohy]["castka"] += $doplatek;
                    //upravime datum doplatku
                    //$this->correctKUhradeData($foo, $foo, $foo, $foo, $rozpis_plateb[$cislo_zalohy]["datum"], $foo);
                }
                
            }          
            
        } else {
            $totalPriceDate = $this->computePlatbaObjednavkyDate();            
            $this->correctKUhradeData($foo, $foo, $foo, $foo, $totalPriceDate, $foo);
            if(strtotime($totalPriceDate)<=strtotime(Date("Y-m-d"))){
                $style = " warning";
            }else{
                $style = "";
            }            
            if($zaplaceno <= 0){
               $text_pl = "Platba objednávky"; 
            }else{
               $text_pl = "Doplatek objednávky";  
            }
            $rozpis_plateb[($cislo_zalohy+1)] = array("text" => $text_pl, "datum" => $totalPriceDate, "castka" => ($totalPrice - $zaplaceno), "mena" => $currency, "styl" => $style);                                               
        }
        
        foreach ($rozpis_plateb as $rp) {
            $html .= "<tr>
                    <td class='border2".$rp["styl"]."'><strong>".$rp["text"]."</strong></td>
                    <td class='border2".$rp["styl"]."'>" . UtilsTS::czechDate($rp["datum"]) . "</td>
                    <td class='border2".$rp["styl"]."' align='right'>" . $rp["castka"] . " " . $rp["mena"] . "</td>
                  </tr>";
        }
        
        $html .= "</table>";
             
        

        return $html;
    }
    
    //Lada: dost jsem se v tom hrabal, prozatim ponechavam pro pripadny rollback
    /*
public function getUhradit_Old($type="")
    {
        $zaplaceno = $this->computeAlreadyPaid();
        $totalPrice = $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky(); //note '$totalPrice = $this->computeTotalPrice();' nahrazeno globalnim modelem
        $html = "";


        if ($zaplaceno >= $totalPrice)
            return $html;

        if ($this->dataObjednavka->stav == Rezervace_library::$STAV_STORNO)
            return $html;

        if($type==ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
            $nadpis = "Rozpis Plateb";
            $textFirstCell = "<strong>Èíslo úètu:</strong> " . $this->dataCentralniData["bankovni_spojeni"] . ", <strong>variabilní symbol:</strong> " . $this->id_objednavka . "";
            $textBeforeTable = "";
        }else{
            $nadpis = "K úhradì";
            $textFirstCell = "";
            $textBeforeTable = "<strong>Èíslo úètu:</strong> " . $this->dataCentralniData["bankovni_spojeni"] . ", <strong>variabilní symbol:</strong> " . $this->id_objednavka . "";
        }
        $html .= "<table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >  ";
        $html .= "  <tr>
                        <td valign='top' colspan='3' style='padding-left:0;padding-right:0;padding-bottom:8px;'><h2>$nadpis</h2></td>
                    </tr>
                    <tr>
                        <td valign='top' colspan='3' style='padding-left:0;padding-right:0;padding-bottom:8px;'>$textBeforeTable</td>
                    </tr>";
        $html .= "  <tr>
                        <th align='left' class='border2' style='width:65%'>$textFirstCell</th>
                        <th align='left' class='border2' style='width:15%'>Datum splatnosti</th>
                        <th align='right' class='border2'>Èástka</th>
                    </tr>";
        
        //zalohu zobraz jen pokud neni nulova
        //Lada: predelan box k uhrade tak, aby zobrazoval soucasny skutecny stav 
        //(tj. pokud klient nedordzel presny rozpis plateb, zobrazi se kolik by mel uhradit v danou chvili)
        //navic pokud je nektery termin (zaloha/doplatek) prosly, zobrazi se cervene
        $dnu_do_odjezdu = $this->computeDnuDoOdjezdu();
        $cislo_zalohy = 0;
        $sum_zaloha = 0;
        $currency = $this->getCurrency();
        foreach ($this->dataZaloha as $zaloha) {
            if ($dnu_do_odjezdu >= $zaloha->prodleva) {//platná záloha                
                $cislo_zalohy++;
                $zalohaCastka = $this->computeZaloha($zaloha);
                if ($cislo_zalohy == 1) {
                    $zalohaDate = $this->computeFirstZalohaDate();
                    $this->correctKUhradeData($foo, $zalohaCastka, $foo, $zalohaDate, $foo, $foo);
                }  
                $zalohaCastka -= $sum_zaloha;
                $sum_zaloha += $zalohaCastka;
                
                
                $last_prodleva = 0;
                if($zaplaceno >= $sum_zaloha){
                    //zaloha uz byla zaplacena, nebudeme ji zobrazovat v poli "k uhrade"
                    $zobrazitZalohu = false;
                    $zalohaCastkaZaplatit = 0;
                }else if($zaplaceno >= ($sum_zaloha-$zalohaCastka)){
                    //ze soucasne zalohy uz bylo neco malo zaplaceno
                    $zobrazitZalohu = true;
                    $zalohaCastkaZaplatit = $sum_zaloha - $zaplaceno;                    
                }else{
                    //ze soucasne zalohy nebylo jeste nic zaplaceno
                    $zobrazitZalohu = true;
                    $zalohaCastkaZaplatit = $zalohaCastka;
                }    
                
                if ($cislo_zalohy == 1) {
                    
                    if($zobrazitZalohu){
                        if(strtotime($zalohaDate)<=strtotime(Date("Y-m-d"))){
                            $style = " warning";
                        }else{
                            $style = "";
                        }

                        
                           $html .= "       <tr>
                            <td class='border2$style'><strong>Záloha</strong></td>
                            <td class='border2$style'>" . UtilsTS::czechDate($zalohaDate) . "</td>
                            <td class='border2$style' align='right'>$zalohaCastkaZaplatit $currency</td>
                          </tr>"; 
                        
                        
                    }
                } else {
                    if($zobrazitZalohu){
                        $zalohaDate = $this->computeNextZalohaDate($last_prodleva);
                        if(strtotime($zalohaDate)<=strtotime(Date("Y-m-d"))){
                            $style = " warning";
                        }else{
                            $style = "";
                        }
                        
                           $html .= "       <tr>
                            <td class='border2$style'><strong>Záloha</strong></td>
                            <td class='border2$style'>" . UtilsTS::czechDate($zalohaDate) . "</td>
                            <td class='border2$style' align='right'>$zalohaCastkaZaplatit $currency</td>
                          </tr>"; 
                        
                        
                    }
                }
                $last_prodleva = $zaloha->prodleva;
            }
        }
        $doplatekDate = $this->computeDoplatekDate($last_prodleva);
        $doplatek = $this->computeDoplatek();
        $this->correctKUhradeData($foo, $foo, $foo, $foo, $doplatekDate, $foo);
        //TODO: zde si muzeme zavlect nekonzistenci upravou ceny doplatku (zrusena moznost menit vysi doplatku) - myslim ze bychom tu nemeli dovolit rucni zmenu vyse doplatku!!
        
        if(strtotime($doplatekDate)<=strtotime(Date("Y-m-d"))){
            $style = " warning";
        }else{
            $style = "";
        }
        if ($cislo_zalohy != 0) {
            
               $html .= "<tr>
                    <td class='border2$style'><strong>Doplatek</strong></td>
                    <td class='border2$style'>" . UtilsTS::czechDate($doplatekDate) . "</td>
                    <td class='border2$style' align='right'>$doplatek $currency</td>
                  </tr>";
            
            
        } else {
            $totalPriceDate = $this->computePlatbaObjednavkyDate();
            $this->correctKUhradeData($totalPrice, $foo, $foo, $foo, $foo, $totalPriceDate);
            
               $html .= "<tr>
                    <td class='border2$style'><strong>Platba objednávky</strong></td>
                    <td class='border2$style'>" . UtilsTS::czechDate($totalPriceDate) . "</td>
                    <td class='border2$style' align='right'>" . $totalPrice . " " . $currency . "</td>
                  </tr>";
                        
            
        }
        $html .= "</table>";
             
        

        return $html;
    }
*/
    public function getObjednavajici($type="")
    {
        $titleSpace = " ";
        if ($this->dataObjednavajici->titul == "") {
            $titleSpace = "";
        }

        if (is_null($this->dataObjednavajiciOrg)) {
            if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
                $html = "
                    <table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
                        <tr>
                            <td class=\"border2 content\" valign=\"top\">
                                <h3>ZÁKAZNÍK - OBJEDNAVATEL</h3>
                                <table style=\"width:100% !important\">
                                    <tr>
                                        <td><strong>" .  $this->dataObjednavajici->titul . $titleSpace . $this->dataObjednavajici->jmeno . " " . $this->dataObjednavajici->prijmeni . "</strong></td> <td><b>e-mail:</b> " .$this->dataObjednavajici->email . "</td> <td><b>tel.:</b> " . $this->dataObjednavajici->telefon . "</td>
                                    </tr>
                                    <tr>
                                        <td colspan=\"2\"><b>Adresa:</b>  " . $this->dataObjednavajici->adresa_ulice . ", " . $this->dataObjednavajici->adresa_psc . ", " . $this->dataObjednavajici->adresa_mesto . "</td><td><b>RÈ/datum nar.: </b>".  UtilsTS::change_date_en_cz($this->dataObjednavajici->datum_narozeni)."</td>
                                    </tr>
                                </table>                                
                            </td>				
                        </tr>
                    </table>
                    ";
            }else{
                $html = "<h2>Zákazník</h2><br/>
                    <p style=\"font-size:1.2em;\">
                        <strong>" . $this->dataObjednavajici->titul . $titleSpace . $this->dataObjednavajici->jmeno . " " . $this->dataObjednavajici->prijmeni . " </strong><br/>
                        <strong>Adresa:</strong> " . $this->dataObjednavajici->adresa_ulice . ", " . $this->dataObjednavajici->adresa_psc . ", " . $this->dataObjednavajici->adresa_mesto . "<br/>
                        <strong>Email:</strong> " . $this->dataObjednavajici->email . "<br/>
                        <strong>Telefon:</strong> " . $this->dataObjednavajici->telefon . "
                    </p>  ";
            }
            
        } else {
            if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
                $html = "
                    <table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
                        <tr>
                            <td class=\"border2 content\" valign=\"top\">
                                <h3>ZÁKAZNÍK - OBJEDNAVATEL</h3>
                                 <table style=\"width:100% !important\">
                                    <tr>
                                        <td><strong>" . $this->dataObjednavajiciOrg->nazev. " </strong></td> <td><b>e-mail:</b> " . $this->dataObjednavajiciOrg->email. "</td> <td><b>tel.:</b> " . $this->dataObjednavajiciOrg->telefon . "</td>
                                    </tr>
                                    <tr>
                                        <td colspan=\"2\"><b>Adresa:</b> "  . $this->dataObjednavajiciOrg->adresa->ulice . ", " . $this->dataObjednavajiciOrg->adresa->psc . ", " . $this->dataObjednavajiciOrg->adresa->mesto .  "</td><td></td>
                                    </tr>
                                </table>                                
                            </td>				
                        </tr>
                    </table>
                    ";
            }else{
                $html = "<h2>Zákazník</h2><br/>
                    <p style=\"font-size:1.2em;\">
                        <strong>" . $this->dataObjednavajiciOrg->nazev . " </strong><br/>
                        <strong>Adresa:</strong> " . $this->dataObjednavajiciOrg->adresa->ulice . ", " . $this->dataObjednavajiciOrg->adresa->psc . ", " . $this->dataObjednavajiciOrg->adresa->mesto . "<br/>
                        <strong>Email:</strong> " . $this->dataObjednavajiciOrg->email . "<br/>
                        <strong>Telefon:</strong> " . $this->dataObjednavajiciOrg->telefon . "
                    </p>  ";
            }            
        }
        return $html;
    }


    public function getPrihlaseneOsoby($type="")
    {
        $html = "
            <table cellpadding=\"0\" cellspacing=\"8\" width=\"810\">
                <tr>
                    <td class=\"border2 content\" valign=\"top\">
                            <h3>PØIHLAŠUJI K ZÁJEZDU/POBYTU TYTO OSOBY:</h3>
                             <table style=\"width:100% !important\">		
         ";               
          if(is_array($this->dataOsoby)){
            $i=1;
            foreach ($this->dataOsoby as $osoba) {
                if($osoba->rodne_cislo == "0000-00-00"){
                    $osoba->rodne_cislo = "";                    
                }else if(stripos($osoba->rodne_cislo , "-")!==false){
                    $osoba->rodne_cislo = UtilsTS::change_date_en_cz($osoba->rodne_cislo);
                }
                
                if($osoba->storno >0){
                    $storno_text = "<span style=\"color:red\">STORNOVÁNO</span>";
                }else{
                    $storno_text = "";
                }
                $html.= " 
                    <tr>
                        <td rowspan=\"3\" valign=\"top\" width=\"10px;\">
                            <strong style=\"font-size:2em;\">$i</strong>
                        </td>
                        <td>
                            <strong>" . $storno_text. $osoba->titul . " " . $osoba->jmeno . " " . $osoba->prijmeni . "</strong></span>
                        </td>                        
                        <td><b>tel.:</b> " . $osoba->telefon . "</span></td>
                        <td><b>e-mail:</b> " . $osoba->email . "</span></td>                             
                    </tr>
                    <tr>
                        <td><b>datum nar. / RÈ:</b> " . $osoba->rodne_cislo  . "</td>                        
                        <td><b>è. dokladu:</b> " . $osoba->cislo_op . "</td>              
                        <td><b>Adresa:</b> " . $osoba->ulice . ", " . $osoba->psc . ", " . $osoba->mesto . "</td>
                    </tr>		
                    <tr>
                            <td colspan=\"4\"><hr style=\"margin-top:-2px;\"/></td>
                    </tr>	
                "; 
                $i++;
            }
        }
        $html .= "   	                      										 		
			</table>
                    </td>			
                </tr>
            </table>
            ";
        return $html;
    }
    
   /*pro potrebu cestovni smlouvy*/
   public function getZajezdCenyPlatby($type="")
    {
        $html = "
            <table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
                <tr>
                    <td class=\"border2\" valign=\"top\" colspan=\"4\"  style=\"width:60%\">
                        <h3>ZÁJEZD/LETOVISKO</h3>
                        <strong>" . $this->dataZajezd->nazevSerialu . " </strong>
                    </td>
                    <td nowrap class=\"border2\" valign=\"top\" align=\"left\" colspan=\"2\"  width=\"150\"><b>TERMÍN:</b><br/> " . UtilsTS::czechDate($this->dataZajezd->terminOd) . " - " . UtilsTS::czechDate($this->dataZajezd->terminDo). "</td>
                    <td nowrap class=\"border2\" valign=\"top\" align=\"left\" width=\"100\"><b>POÈET NOCÍ:</b> " . UtilsTS::calculate_pocet_noci($this->dataZajezd->terminOd, $this->dataZajezd->terminDo) . "<br/><br/></td>
                </tr>                    
                ".$this->getSluzby($type)."
                    
                <tr>
                    <th align=\"left\" class=\"border2\">Platby: Datum úhrady</th>
                            <th align=\"left\" class=\"border2\">Èástka</th>
                                <th align=\"left\" class=\"border2\">Typ dokladu</th>
                                <th align=\"left\" class=\"border2\">Zpùsob úhrady</th>

                                <th colspan=\"2\"  class=\"border2l border2t border2b\" align=\"left\"><strong>Celková cena</strong></th>
                    <th class=\"border2r border2t border2b\" align=\"right\"><strong>" .  $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky() . " Kè</strong></th>
                        </tr>
                        
                ".$this->getPlatby($type)."        

                        <tr>
                            <td class=\"border2\" colspan=\"4\" rowspan=\"4\" valign=\"top\">
                        <h3>POZNÁMKY/UPOZORNÌNÍ</h3>
                        ".$this->dataZajezd->povinnePoplatky."
                        " . nl2br($this->dataObjednavka->poznamky ) . "
                    </td>
                                <td class=\"border2\" colspan=\"3\">
                        <h3>ÚDAJE CK</h3>
                    </td>
                        </tr>
                        <tr>
                            <td class=\"border2\" colspan=\"2\"><b>Odeslání voucheru a pokynù</b></td>
                                <td class=\"border2\">" . ($this->dataObjednavka->stav == 7 ? "Odbaveno" : "") . " </td>
                        </tr>
                    <tr>
                            <td class=\"border2\" colspan=\"2\"><b>STORNO DNE</b></td>
                                <td class=\"border2\">" .( ($this->dataObjednavka->storno_datum!="0000-00-00" && $this->dataObjednavka->storno_datum!="")?($this->dataObjednavka->storno_datum):("") ). "</td>
                        </tr>
                        <tr>
                            <td class=\"border2\" colspan=\"2\"><b>STORNO POPLATEK</b></td>
                                <td class=\"border2\">" .( ($this->dataObjednavka->storno_poplatek > 0 || ($this->dataObjednavka->storno_datum!="0000-00-00"&& $this->dataObjednavka->storno_datum!="") )?($this->dataObjednavka->storno_poplatek." Kè"):("") ). " </td>
                        </tr>
                </table>

            ".$this->getUhradit($type)." 	
         ";
        return $html;

    }
    
    public function getProdejce()
    {
        $html = "<h2>Obchodní zástupce - prodejce</h2><br/>";
        if ($this->dataProdejce->nazev != "") {
            $html .= "<p style=\"font-size:1.2em;\">
                        <strong>" . $this->dataProdejce->nazev . "</strong><br/>
                        <strong>Adresa:</strong> " . $this->dataProdejce->adresa_ulice . ", " . $this->dataProdejce->adresa_mesto . ", " . $this->dataProdejce->adresa_psc . "<br/>
                        <strong>Telefon:</strong> " . $this->dataProdejce->telefon . "<br/>
                        <strong>Email:</strong> " . $this->dataProdejce->email . "<br/>	    
                        <strong>IÈO:</strong> " . $this->dataProdejce->ico . "<br/>
                    </p>";
        }

        return $html;
    }

    public function getPoznamka()
    {
        if ($this->dataObjednavka->poznamky == "")
            return "";

        $html = "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
                    <tr>
                        <td class=\"border2\" style=\"width:15%\">Poznámka:</td>
                        <td class=\"border2\">" . $this->dataObjednavka->poznamky . "</td>
                    </tr>
                </table>";
        return $html;
    }

    //todo - rozdelit na metody - zacina to byt neprehledne
    public function getOverview($tsType)
    {
        $servicesPrice = $this->computeServicesPrice();
        $currency = $this->getCurrency();
        $totalPrice = $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky(); //note '$this->computeTotalPrice()' nahrazeno globalnim modelem
        $alreadyPaid = $this->computeAlreadyPaid();
        $stornoAmount = $tsType == ObjednavkaTS::TYPE_STORNO_CK ? 0 : $this->dataObjednavka->storno_poplatek;
        $stornoToReturn = $this->computeStornoToReturn();

        $html = "";

        //POTVRZENI + POTVRZENI PRODEJCE
        if ($tsType == ObjednavkaTS::TYPE_POTVRZENI || $tsType == ObjednavkaTS::TYPE_POTVRZENI_PRODEJCE || $tsType == ObjednavkaTS::TYPE_PLATEBNI_DOKLAD) {
            $html .= "<tr>
                    <td valign=\"top\" colspan=\"2\" style=\"padding-left:0;padding-right:0;padding-bottom:8;\"><h2>Celkem</h2></td>
                </tr> 
                <tr>
                    <td class=\"border2\" style=\"width:85%\">Celkem za služby</td>
                    <td class=\"border2\" align=\"right\">$servicesPrice $currency</td>
                 </tr>";

            //existuje sleva
            if ($this->computeDiscount() > 0) {
                $html .= "<tr>
                        <td class=\"border2\" style=\"width:85%\">Slevy</td>
                        <td class=\"border2\" align=\"right\"> - " . $this->computeDiscount() . " $currency</td>
                      </tr>";
            }
            if ($this->dataObjednavka->storno_poplatek > 0 ) {
                $html .= "<tr>
                        <td class=\"border2\" style=\"width:85%\">Storno poplatek</td>
                        <td class=\"border2\" align=\"right\"> " . $this->dataObjednavka->storno_poplatek . " $currency</td>
                      </tr>";
            }                              

            
        }

        //POTVRZENI PRODEJCE
        if ($tsType == ObjednavkaTS::TYPE_POTVRZENI_PRODEJCE) {
            $textProvize = $this->dataObjednavkaGlobal->calcProvize() . " " . $currency;
            if ($this->dataObjednavkaGlobal->calcProvize() == 0) {
                $textProvize = "není zadána";
            }
            $html .= "<tr>
                        <td class=\"border2\" style=\"width:85%\">Provize pro prodejce</td>
                        <td class=\"border2\" align=\"right\">$textProvize</td>
                      </tr>";
        }

        //obj storno
        if ($this->dataObjednavka->stav == Rezervace_library::$STAV_STORNO) {
            $html .= "<tr>
                        <td class=\"border2\" style=\"width:85%\"><strong>Storno poplatek</strong></td>
                        <td class=\"border2\" align=\"right\">$stornoAmount Kè</td>
                      </tr>";
        }

        //POTVRZENI + POTVRZENI PRODEJCE
        if ($tsType == ObjednavkaTS::TYPE_POTVRZENI || $tsType == ObjednavkaTS::TYPE_POTVRZENI_PRODEJCE || $tsType == ObjednavkaTS::TYPE_PLATEBNI_DOKLAD) {
            //obj storno
            $vTotalPrice = $this->dataObjednavka->stav == Rezervace_library::$STAV_STORNO ? $stornoAmount : $totalPrice;
            $html .= "<tr>
                    <td class=\"border2\" style=\"width:85%\"><strong>Celková cena</strong></td>
                    <td class=\"border2\" align=\"right\"><strong>$vTotalPrice $currency</strong></td>
                  </tr>";

            $html .= "<tr>
                    <td class=\"border2\" style=\"width:85%\">Uhrazeno</td>
                    <td class=\"border2\" align=\"right\">$alreadyPaid $currency</td>
                  </tr>";

            //obj storno
            $vToPay = $this->dataObjednavka->stav == Rezervace_library::$STAV_STORNO ? $stornoAmount - $alreadyPaid : $totalPrice - $alreadyPaid;
            $html .= "<tr>
                    <td class=\"border2\" style=\"width:85%\">Zbývá zaplatit</td>
                    <td class=\"border2\" align=\"right\">$vToPay $currency</td>
                  </tr>";
            //STORNO KLIENT
        } else if ($tsType == ObjednavkaTS::TYPE_STORNO_KLIENT) {
            $html .= "<tr>
                    <td class=\"border2\" style=\"width:85%\">K vrácení</td>
                    <td class=\"border2\" align=\"right\">$stornoToReturn $currency</td>
                  </tr>";
            //STORNO CK
        } else if ($tsType == ObjednavkaTS::TYPE_STORNO_CK) {
            $html .= "<tr>
                    <td class=\"border2\" style=\"width:85%\">K vrácení</td>
                    <td class=\"border2\" align=\"right\">$alreadyPaid $currency</td>
                  </tr>";
        }

        return $html;
    }

    public function getVystavil()
    {
        $html = "<tr>
                    <td class=\"border2\" style=\"width:65%\">Vystavil: " . $this->dataVystavil->jmeno . " " . $this->dataVystavil->prijmeni . "</td>
                    <td class=\"border2\">Datum: " . Date("d.m.Y") . "</td>
                 </tr>";
        return $html;
    }

    public function getDatumObjednavky()
    {
        $html = "<tr>
                    <td  style=\"width:65%\">Datum rezervace: " . Date("d.m.Y G:i:s", strtotime($this->dataObjednavka->datum_rezervace)) . "</td>
                 </tr>";
        return $html;
    }
    
    public function getFooterCestovniSmlouva($type="")
    {
        $date = explode(" ",UtilsTS::change_date_en_cz($this->dataObjednavka->datum_rezervace));
        
        $html = "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
            <tr>
		<td class=\"border2\" valign=\"top\" colspan=\"3\">
                   <h3>PROHLÁŠENÍ ZÁKAZNÍKA</h3>
                    Prohlašuji, že souhlasím se Smluvními podmínkami úèasti na zájezdech, které jsou nedílnou souèástí této smlouvy o zájezdu a s ostatními podmínkami uvedenými v této smlouvì o zájezdu, a to i jménem výše uvedených osob, které mne k jejich pøihlášení a úèasti zmocnily. Zároveò potvrzuji, že jsem se seznámil s podrobným vymezením zájezdu, s dokladem o pojištìní CK proti úpadku a s pøíslušným formuláøem dle vyhlášky è. 122/2018 Sb., o vzorech formuláøù pro jednotlivé typy zájezdù a spojených cestovních služeb.
                </td>				
            </tr>
            <tr>
                <td class=\"border2\" valign=\"top\" height=\"40\">
                    <b>DATUM:</b> ".$date[0]."
                </td>	
                <td class=\"border2\" valign=\"top\">
                    <b>Podpis zákazníka:</b>
                </td>		
                <td class=\"border2\" valign=\"top\">
                    <b>Podpis CK (prodejce):</b>
                </td>		        			
            </tr>	
        </table>
        ";
        return $html;
    }

    public function getHeader($type)
    {
        if($type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
            		
            $html = "
            <table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
                    <tr>
                     <th colspan=\"3\" style=\"padding-left:0;padding-right:0;\">
                                    <h1 style=\"font-size:24px;\">" . $this->getTittle($type) . "</h1>
                </th>
              </tr>
              <tr>
                <th colspan=\"3\" align=\"right\" style=\"padding-right:20px;\">   			
                                    <h3>uzavøená ve smyslu zákona è. 159/1999 Sb.</h3>
               </th>
                    </tr>

              <tr>	   
                    <td class=\"border2\" valign=\"top\" rowspan=\"2\">                
                        <h2>" . $this->dataCentralniData["nazev_spolecnosti"] . "</h2>
                        <p style=\"font-size:1.1em;\">
                            " . $this->dataCentralniData["adresa"] . "<br/>
                            " . $this->dataCentralniData["firma_zapsana"] . "<br/>
                            tel.: " . $this->dataCentralniData["telefon"] . "<br/>
                            IÈO: " . $this->dataCentralniData["ico"] . " DIÈ: " . $this->dataCentralniData["dic"] . "<br/>
                            Bankovní spojení: " . $this->dataCentralniData["bankovni_spojeni"] . "<br/>
                            e-mail: " . $this->dataCentralniData["email"] . ", web: " . $this->dataCentralniData["web"] . "<br/>	    
                        </p>  
                    </td>	

                    <td rowspan=\"2\"  class=\"border2\" valign=\"top\"  width=\"280\" >                       
                            " . $this->getProdejce() . "
                    </td>	

                    <td lign=\"center\" valign=\"top\" width=\"180\">
                            <img src='https://slantour.cz/foto/full/14628-logo-slantour.jpg' width='150' height='83' /><br/>
                            <b>ÈÍSLO SMLOUVY - REZERVACE:</b>
                    </td>
                </tr>
                <tr>
                    <td class=\"border2\"  align=\"center\">		
                      <b>" . $this->id_objednavka . "
                      </b>
                    </td>    	
                </tr>
            </table> ";
        }else{
            $html = "<table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
            <tr>
                <th colspan=\"3\" style=\"padding-left:0;padding-right:0;\">
   			<h1>" . $this->getTittle($type) . "</h1></br>
                </th>
            </tr>
            <tr>	   
		<td class=\"border2\" valign=\"top\" rowspan=\"2\">
                    " . $this->getObjednavajici() . "
		</td>	
		
		<td rowspan=\"2\"  class=\"border2\" valign=\"top\"  width=\"280\" >
                    " . $this->getProdejce() . "
                </td>	
        
 		<td lign=\"center\" valign=\"top\" width=\"180\">
                    <img src='https://slantour.cz/foto/full/14628-logo-slantour.jpg' width='150' height='83' /><br/>
                    <b>ÈÍSLO OBJEDNÁVKY:</b>
		</td>
            </tr>
            <tr>
                <td class=\"border2\"  align=\"center\">		
                    <b>" . $this->id_objednavka . "</b>
		</td>    	
            </tr>
        </table>  ";
        }
        
        return $html;
    }

    public function getHeaderPlatebniDoklad($zakaznikInfo)
    {
        $title = "PLATEBNÍ DOKLAD";
        $html = "<table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
            <tr>
                <th colspan=\"3\" style=\"padding-left:0;padding-right:0;\">
   			<h1>" . $title . "</h1></br>
                </th>
            </tr>
            <tr>
		<td class=\"border2\" valign=\"top\" rowspan=\"2\" width=\"630\"><h2>Zákazník</h2><br/><br/>$zakaznikInfo</td>

 		<td lign=\"center\" valign=\"top\" width=\"180\">
                    <img src='https://slantour.cz/foto/full/14628-logo-slantour.jpg' width='150' height='83' /><br/>
                    <b>ÈÍSLO OBJEDNÁVKY:</b>
		</td>
            </tr>
            <tr>
                <td class=\"border2\"  align=\"center\">
                    <b>" . $this->id_objednavka . "</b>
		</td>
            </tr>
        </table>  ";
        return $html;
    }

    public function getFooter()
    {
        /* $html = "<tr>	   
          <td valign=\"left\">
          <h2>SLAN tour s.r.o.</h2>
          <p style=\"font-size:1.1em;\">
          Wilsonova 597, Slaný, 274 01<br/>
          Firma zapsána v OR u MS v Praze, oddíl C, vložka 51266<br/>
          tel.: 312520084, 312523836<br/>
          IÈO: 25118889 DIÈ: CZ25118889<br/>
          Bankovní spojení: KB Kladno, 19-6706930207 / 0100<br/>
          e-mail: info@slantour.cz, web: www.slantour.cz<br/>
          </p>
          </td><tr>"; */
        $html = "<tr>	   
		 <td valign=\"left\">
                    <h2>" . $this->dataCentralniData["nazev_spolecnosti"] . "</h2>
                    <p style=\"font-size:1.1em;\">
                        " . $this->dataCentralniData["adresa"] . "<br/>
			" . $this->dataCentralniData["firma_zapsana"] . "<br/>
			tel.: " . $this->dataCentralniData["telefon"] . "<br/>
			IÈO: " . $this->dataCentralniData["ico"] . " DIÈ: " . $this->dataCentralniData["dic"] . "<br/>
			Bankovní spojení: " . $this->dataCentralniData["bankovni_spojeni"] . "<br/>
			e-mail: " . $this->dataCentralniData["email"] . ", web: " . $this->dataCentralniData["web"] . "<br/>	    
                    </p> 
		 </td><tr>";
        return $html;
    }

    public function getKUHradeCelkem()
    {
        $totalPrice = $this->dataObjednavkaGlobal->calcFinalniCenaObjednavky();
        $this->correctKUhradeData($totalPrice, $foo, $foo, $foo, $foo, $foo);
        return $totalPrice;
    }

    public function getKUHradeZaloha()
    {
        $zaloha = $this->computeFirstZaloha();
        $this->correctKUhradeData($foo, $zaloha, $foo, $foo, $foo, $foo);
        return $zaloha;
    }

    public function getKUHradeDoplatek()
    {
        $doplatek = $this->computeDoplatek();
        $this->correctKUhradeData($foo, $foo, $doplatek, $foo, $foo, $foo);
        return $doplatek;
    }

    public function getKUHradeCelkemDatspl()
    {
        $totalPriceDate = $this->computePlatbaObjednavkyDate();
        $this->correctKUhradeData($foo, $foo, $foo, $foo, $foo, $totalPriceDate);
        return $totalPriceDate;
    }

    public function getKUHradeZalohaDatspl()
    {
        $zalohaDate = $this->computeFirstZalohaDate();
        $this->correctKUhradeData($foo, $foo, $foo, $zalohaDate, $foo, $foo);
        return $zalohaDate;
    }

    public function getKUHradeDoplatekDatspl()
    {
        foreach ($this->dataZaloha as $value) {
            $last = $value->prodleva;
        }
        $doplatekDate = $this->computeDoplatekDate($last);
        $this->correctKUhradeData($foo, $foo, $foo, $foo, $doplatekDate, $foo);
        return $doplatekDate;
    }

    /**
     * Vraci true, pokud se s cenou pocita jako se zalohou a doplatkem a false pokud se cena musi zaplatit jiz v celku (zavisle na datu uskutecneni objednavky versus datum odjezdu)
     * @return bool
     */
    public function hasZalohaDoplatek()
    {
        $zalohaDate = $this->computeFirstZalohaDate();
        foreach ($this->dataZaloha as $value) {
            $last = $value->prodleva;
        }
        $doplatekDate = $this->computeDoplatekDate($last);
        //echo $zalohaDate.$doplatekDate;

        return !(strtotime($zalohaDate) > strtotime($doplatekDate));
    }

    /**
     * Misto vypocitanych dat zapise do promenych data, ktera jsou zapsana u objednavky a maji prednost
     * @param $totalPrice
     * @param $zaloha
     * @param $doplatek
     * @param $zalohaDate
     * @param $doplatekDate
     * @param $totalPriceDate
     */
    private function correctKUhradeData(&$totalPrice, &$zaloha, &$doplatek, &$zalohaDate, &$doplatekDate, &$totalPriceDate)
    {
        //data vyplnena u objednavky maji prednost
        if ($this->dataObjednavka->k_uhrade_celkem != 0)
            $totalPrice = $this->dataObjednavka->k_uhrade_celkem;
        if ($this->dataObjednavka->k_uhrade_zaloha != 0)
            $zaloha = $this->dataObjednavka->k_uhrade_zaloha;
        if ($this->dataObjednavka->k_uhrade_doplatek != 0)
            $doplatek = $this->dataObjednavka->k_uhrade_doplatek;
        if ($this->dataObjednavka->k_uhrade_zaloha_datspl != "0000-00-00")
            $zalohaDate = $this->dataObjednavka->k_uhrade_zaloha_datspl;
        if ($this->dataObjednavka->k_uhrade_doplatek_datspl != "0000-00-00")
            $doplatekDate = $this->dataObjednavka->k_uhrade_doplatek_datspl;
        if ($this->dataObjednavka->k_uhrade_celkem_datspl != "0000-00-00")
            $totalPriceDate = $this->dataObjednavka->k_uhrade_celkem_datspl;
    }

    private function getTittle($type)
    {
        switch ($type) {
            default:
            case ObjednavkaTS::TYPE_POTVRZENI:
                $title = "POTVRZENÍ ZÁJEZDU / OBJEDNÁVKY";
                break;
            case ObjednavkaTS::TYPE_POTVRZENI_PRODEJCE:
                $title = "POTVRZENÍ ZÁJEZDU / OBJEDNÁVKY - PRODEJCI";
                break;
            case ObjednavkaTS::TYPE_STORNO_KLIENT:
                $title = "STORNO KLIENTA";
                break;
            case ObjednavkaTS::TYPE_STORNO_CK:
                $title = "STORNO OBJEDNÁVKY CESTOVNÍ KANCELÁØÍ / VRATKA PLATBY";
                break;
            case ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA:
                $title = "SMLOUVA O ZÁJEZDU - OBJEDNÁVKA CESTOVNÍCH SLUŽEB";
                break;
        }

        return $title;
    }

    private function getCurrency()
    {
        $currency = "";
        foreach ($this->dataSluzby as $value) {
            $currency = $value->mena;
            if ($currency != "") {
                break;
            }
        }
        if($currency == ""){
            $currency = "Kè";
        }
        return $currency;
    }
}
