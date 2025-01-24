<?php

/**
 * Class ObjednavkaView
 * Sdruzuje spolecne casti view vsem objednavkovym view
 */
class ObjednavkaView
{

    const MENA_KC = "Kč";
    const MENA_EUR = "€";

    /**
     * Vytvori navigacni menu objednakvy
     * @param $viewId int cislo kroku objednavky
     * @return string html
     */
    protected function menu($viewId)
    {
        $out = "";

        $out .= "   <div id='order-nav'>";
        $out .= "       <ul id='order-nav-list'>";
        $out .= "           <li class='step-first " . ($viewId == 1 ? "step-active" : "") . "'>";
        $out .= "               <span class='step-number'>1</span><a href='index.php?page=" . ObjednavkaController::PAGE_ZAJEZD . "'>Výběr služeb</a>";
        $out .= "           </li>";
        $out .= "           <li " . ($viewId == 2 ? "class='step-active'" : "") . ">";
        $out .= "               <span class='step-number'>2</span>";
        $out .= $viewId >= 2 ? "<a href='index.php?page=" . ObjednavkaController::PAGE_OSOBNI_UDAJE . "'>Kontakní údaje</a>" : "Kontakní údaje";
        $out .= "           </li>";
        $out .= "           <li " . ($viewId == 3 ? "class='step-active'" : "") . ">";
        $out .= "               <span class='step-number'>3</span>";
        $out .= $viewId >= 3 ? "<a href='index.php?page=" . ObjednavkaController::PAGE_PLATBA . "'>Způsob platby</a>" : "Způsob platby";
        $out .= "           </li>";
        $out .= "           <li " . ($viewId == 4 ? "class='step-active'" : "") . ">";
        $out .= "               <span class='step-number'>4</span>";
        $out .= $viewId >= 4 ? "<a href='index.php?page=" . ObjednavkaController::PAGE_SOUHRN . "'>Souhrn</a>" : "Souhrn";
        $out .= "           </li>";
        $out .= "       </ul>";
        $out .= "   </div>";

        return $out;
    }

    /**
     * Hlavicka webu urcujici z ktereho webu prisla objednavka
     * @return string html
     */
    protected function srcWebHeader()
    {
        $out = "";

        $out .= "<div id='header'>";
        $out .= "    <h1>SLAN tour s.r.o. - Objednávka z webu " . $_SESSION["src_web"] . "</h1>";

        if(isset($_SESSION["id_klient"])) {
            $out .= "<div class='agentura'>Přihlášená agentura: " . $_SESSION["jmeno_klient"] . "</div>";
        }

        $out .= "</div>";

        return $out;
    }

    /**
     * Hlavicka
     * @return string html
     */
    protected function header()
    {
        $out = "";

        $out .= "<!DOCTYPE html>";
        $out .= "<!--[if IE 8]><html class='ie8'><![endif]-->";
        $out .= "<!--[if IE 9]><html class='ie9'><![endif]-->";
        $out .= "<!--[if IE 10]><html class='ie10'><![endif]-->";
        $out .= "<!--[if !IE]><html><![endif]-->";
        $out .= "   <head>";
         $out .= "      <title>Objednávka zájezdu</title>";
        $out .= "       <meta http-equiv='Content-Type' content='text/html; charset=windows-1250'>";

        $out .= "       <link rel='stylesheet' type='text/css' href='./res/css/style.css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='./res/date-range-picker/daterangepicker-bs3.css'>";

        $out .= "       <script type='text/javascript' src='./res/js/jquery-min.js'></script>";
        $out .= "       <script type='text/javascript' src='./res/js/script.js'></script>";
        $out .= "       <script type='text/javascript' src='./res/date-range-picker/moment.js'></script>";
        $out .= "       <script type='text/javascript' src='./res/date-range-picker/daterangepicker.js'></script>";

        $out .= "   </head>";
        $out .= "   <body>";
        $out .= "       <div id='order-body'>";

        return $out;
    }

    /**
     * Paticka
     * @return string html
     */
    protected function footer()
    {
        $out = "";

        $out .= "       </div>"; //#order-body
        $out .= "   </body>";
        $out .= "</html>";

        return $out;
    }

    /**
     * @param $sluzba SluzbaEnt
     * @param $pocet
     * @return string
     */
    protected function viewSluzbaStatus($sluzba, $pocet) {
        $pocet = $pocet == 0 ? 1 : $pocet;
        if($sluzba->isVyprodana()) {
            $status = "<span class='sl-status sl-status-red'>vyprodáno</span>";
        } else if($sluzba->isNaDotaz() || $sluzba->isPlnaKapacita($pocet)) {
            $status = "<span class='sl-status sl-status-orange'>na dotaz</span>";
        } else {
            $status = "<span class='sl-status sl-status-green'>volno</span>";
        }

        return $status;
    }
}