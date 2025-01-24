<?php
/**   \file
 * pdf_objednavka.php - hlavni stranka klientske casti systemu
 *    - je na ni provaden reload pø zpracování formuláøù ostatních modulù
 *    - zobrazuje podrobné vyhledávání a tipy na zájezdy
 */
//spusteni prace se sessions
//session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

//note - v casti TS je pouzit globalni model
require_once "../global/lib/utils/CommonUtils.php";


require_once "./classes/serial_list.inc.php";
require_once "./classes/zajezd_list.inc.php"; //seznam zajezdu serialu
require_once "./classes/foto_list.inc.php"; //seznamy fotografii
require_once "./classes/slevy_list.inc.php"; //seznamy fotografii
require_once "./classes/dokument_list.inc.php"; //seznamy dokumentu
require_once "./classes/zeme_list.inc.php"; //seznamy fotografii
require_once "./classes/informace_list.inc.php"; //seznamy fotografii
require_once "./classes/objekty_list.inc.php"; //seznamy fotografii
require_once "./classes/typ_serialu_list.inc.php"; //seznamy typu seria
require_once "./classes/ts/utils_ts.inc.php";

$html = "";        
if($_GET["typ"]=="prehled_objednavek"){
    $serial_list = new Serial_list($_SESSION["serial_typ"], $_SESSION["serial_podtyp"], $_SESSION["serial_nazev"], $_SESSION["serial_zeme"], $_GET["str"], $_SESSION["serial_ord_by"], $_GET["moznosti_editace"]);
    $html = $serial_list->createHTMLTSPrehledObjednavek();
}
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
            SLAN tour | tvorba PDF seriál - pøehled obsazenosti
        </title>
        <meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/>
        <meta name="Robots" content="noindex, nofollow"/>
        <link type="text/css" href="/admin/classes/ts/ts_default.css" rel="stylesheet" />
    </head>

    <body>
    <h2><? echo $errorMessage; echo $html; ?></h2>
    </body>
    </html>
<?
}
?>

    