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
class HlaseniPojistovneTS extends Generic_data_class
{
    const TYPE_NOVE_OBJEDNAVKY = 'nove_objednavky';
    const TYPE_REALIZOVANE_OBJEDNAVKY = 'realizovane_objednavky';

    protected $params;
    protected $type;


    function __construct($params, $type)
    {
        $this->type = $type;
         $this->params = $params;
        //kontrola vstupnich dat
    }


    function createHtml()
    {
        $displayer = new HlaseniPojistovneDisplayer($this->params, $this->type);
        $html = "";

        //hlavicka
        $html .= $displayer->getHeader($this->type);
        $html .= $displayer->getSumValues();
        
        if($this->params["zobrazit_objednavky"]==1){
            $html .= $displayer->getRowsObjednavka();
        }                
//        echo $html;
        return $html;
    }
}

?>
