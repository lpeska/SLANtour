<?php
/**   \file
 * pdf_objednavka.php - hlavni stranka klientske casti systemu
 *    - je na ni provaden reload p� zpracov�n� formul��� ostatn�ch modul�
 *    - zobrazuje podrobn� vyhled�v�n� a tipy na z�jezdy
 */
//spusteni prace se sessions
//session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/dataContainers/tsTopologie.php";
require_once "./classes/dataContainers/tsPolozkaTopologie.php";

require_once "./classes/ts/topologie_dao.inc.php";
require_once "./classes/ts/objednavka_dao.inc.php";
require_once "./classes/ts/topologie_displayer.inc.php";
require_once "./classes/ts/topologie_ts.inc.php";
require_once "./classes/ts/utils_ts.inc.php";

require_once "./classes/dataContainers/tsObjednavajici.php";
require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsOsoba.php";
require_once "./classes/dataContainers/tsPlatba.php";
require_once "./classes/dataContainers/tsProdejce.php";
require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsStaticDescription.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsProvize.php";
require_once "./classes/dataContainers/tsSleva.php";
require_once "./classes/dataContainers/tsSmluvniPodminky.php";
require_once "./classes/dataContainers/tsObjektovaKategorie.php";

$ts = new TopologieTS($_GET["id_tok_topologie"]);

$html = $ts->createHtml();
//echo $html;
$errorMessage = "";
if ($errorMessage == "") {
    define('_MPDF_PATH', '../mpdf/');
    include('../mpdf/mpdf.php');
    $mpdf = new mPDF('cs', 'A4', 7, 'DejaVuSans', 8, 8, 5, 5, 1, 1);

    $mpdf->keep_table_proportions = true;
    $mpdf->allow_charset_conversion = true;
    $mpdf->charset_in = 'windows-1250';
    $stylesheet = file_get_contents('classes/ts/ts_default.css');

    $mpdf->WriteHTML($stylesheet, 1);
    $mpdf->WriteHTML($html, 2);

    $mpdf->Output();
} else {
    ?>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html lang="cs">
    <head>
        <title>
            SLAN tour | tvorba PDF zasedac�ho po��dku
        </title>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/>
        <meta name="Robots" content="noindex, nofollow"/>

    </head>

    <body>
    <h2><? echo $errorMessage ?></h2>
    </body>
    </html>
<?
}
?>

