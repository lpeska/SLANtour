<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of topologie_ts
 *
 * @author Pesi
 */
class TopologieTS extends Generic_data_class
{


    protected $id_tok_topologie;


    function __construct($id_tok_topologie)
    {

        //kontrola vstupnich dat
        $this->id_tok_topologie = $this->check_int($id_tok_topologie);
    }

    function createHtml()
    {
        $topologieDisplayer = new TopologieDisplayer($this->id_tok_topologie);
        $html = "<body>";

        //hlavicka
        $html .= $topologieDisplayer->getHeader();

        //polozky
        $html .= "<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\">  "
            . $topologieDisplayer->getPolozky().
            "</table>";

        //pata
        $html .= $topologieDisplayer->getFooter();
        $html .= "</body></html>";
//        echo $html;
        return $html;
    }  
}

?>
