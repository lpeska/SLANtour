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
class FakturaTS extends Generic_data_class
{
    protected $id_faktury;
    const PDF_FOLDER = './classes/faktury/temp-pdf/';
    const EMAIL_SENDER = 'info@slantour.cz';

    function __construct($id_faktury)
    {
        $this->id_faktury = $id_faktury;

    }

    function createHtml()
    {
        $fakturaDisplayer = new FakturaDisplayer($this->id_faktury);
        $html = "<html><body>";

        $html .= $fakturaDisplayer->getHeader();
        $html .= $fakturaDisplayer->getFakturaInfo();
        $html .= $fakturaDisplayer->getPolozky();
        $html .= $fakturaDisplayer->getVystavil();
        $html .= $fakturaDisplayer->getFooter();


        $html .= "</body></html>";
//        echo $html;
        return $html;
    }

}

?>
