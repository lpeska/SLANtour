<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of objednavka_ts
 *
 * @author Pesi
 */
class ObjednavkaTS extends Generic_data_class
{
    const TYPE_CESTOVNI_SMLOUVA = 'cestovni_smlouva';
    const TYPE_PLATEBNI_DOKLAD = 'platebni_doklad';
    const TYPE_POTVRZENI = 'potvrzeni';
    const TYPE_POTVRZENI_PRODEJCE = 'potvrzeni_prodejce';
    const TYPE_STORNO_KLIENT = 'storno_klient';
    const TYPE_STORNO_CK = 'storno_ck';

    protected $id_objednavka;
    protected $security_code;
    protected $type;


    function __construct($id_objednavka, $security_code, $type)
    {
        $this->type = $type;
        //kontrola vstupnich dat
        $this->id_objednavka = $this->check_int($id_objednavka);
        $this->security_code = $this->check($security_code);
    }

    function showUhradit(){
        $objednavkaDisplayer = new ObjednavkaDisplayer($this->id_objednavka);
        return $objednavkaDisplayer->getUhradit();
    }
    function createHtml()
    {
        $objednavkaDisplayer = new ObjednavkaDisplayer($this->id_objednavka);
        $html = "<body>";

        //hlavicka
        $html .= $objednavkaDisplayer->getHeader($this->type);
        if($this->type == ObjednavkaTS::TYPE_CESTOVNI_SMLOUVA){
            $html .= $objednavkaDisplayer->getObjednavajici($this->type);
            $html .= $objednavkaDisplayer->getPrihlaseneOsoby($this->type);
            $html .= $objednavkaDisplayer->getZajezdCenyPlatby($this->type);
            $html .= $objednavkaDisplayer->getFooterCestovniSmlouva($this->type);
        }else{            
            //zajezd a sluzby
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\">  "
                . $objednavkaDisplayer->getZajezd()
                . $objednavkaDisplayer->getSluzby() .
                "</table>";

            //platby
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >  "
                . $objednavkaDisplayer->getPlatby() .
                "</table>";
            
            //uhradit u potvrzeni
            if ($this->type == ObjednavkaTS::TYPE_POTVRZENI || $this->type == ObjednavkaTS::TYPE_POTVRZENI_PRODEJCE) {
                $html .= $objednavkaDisplayer->getUhradit();
            }

            //overview
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >  "
                . $objednavkaDisplayer->getOverview($this->type) .
                "</table>";

            //poznamka u storna
            if ($this->type == ObjednavkaTS::TYPE_STORNO_KLIENT || $this->type == ObjednavkaTS::TYPE_STORNO_CK) {
                $html .= $objednavkaDisplayer->getPoznamka();
            }

            //vystavil
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >  "
                . $objednavkaDisplayer->getVystavil() .
                "</table>";

            //datum rezervace
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >  "
                . $objednavkaDisplayer->getDatumObjednavky() .
                "</table>";
            if($this->type == ObjednavkaTS::TYPE_POTVRZENI_PRODEJCE){
                $html .= "Provize za cestovní služby poskytované v tuzemsku a zemích EU je vèetnì DPH 21%";
            }
            $html .= "<hr>";

            //footer
            $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >  "
                . $objednavkaDisplayer->getFooter() .
                "</table>";
         }
        $html .= "</body></html>";
//        echo $html;
        return $html;
    }
}

?>
