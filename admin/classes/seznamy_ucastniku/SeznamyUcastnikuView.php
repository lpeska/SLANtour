<?php


class SeznamyUcastnikuView
{

    public static function loginErr()
    {
        $out = "";

        $out .= self::htmlHead();
        $out .= ModulView::showLoginForm(SeznamyUcastnikuModel::$zamestnanec->get_uzivatelske_jmeno());
        $out .= SeznamyUcastnikuModel::$zamestnanec->get_error_message();
        $out .= self::htmlFoot();

        echo $out;
    }

    protected static function htmlHead()
    {
        $out = "";

        $out .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";
        $out .= "<html>";
        $out .= "   <head>";
        $out .= "       <title>" . SeznamyUcastnikuModel::$core->show_nazev_modulu() . " | Administrace systému RSCK</title>";
        $out .= "       <meta http-equiv='Content-Type' content='text/html; charset=windows-1250'/>";
        $out .= "       <meta name='copyright' content='&copy; Slantour'/>";
        $out .= "       <meta http-equiv='pragma' content='no-cache' />";
        $out .= "       <meta name='robots' content='noindex,noFOLLOW' />";
        $out .= "       <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='css/reset-min.css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='./new-menu/style.css' media='all'/>";
        $out .= "       <link rel='stylesheet' href='/admin/css/jquery-ui.min.css'>";
        $out .= "       <script type='text/javascript' src='./js/jquery-min.js'></script>";
        $out .= "       <script type='text/javascript' src='./js/jquery-ui-cze.min.js'></script>";
        $out .= "       <script type='text/javascript' src='./classes/seznamy_ucastniku/js/seznamy_ucastniku.js'></script>";
        $out .= "       <script type='text/javascript' src='./js/common_functions.js'></script>";
        $out .= "   </head>";
        $out .= "   <body>";

        return $out;
    }

    protected static function htmlFoot()
    {
        $out = "";

        $out .= "</body></html>";

        return $out;
    }

}