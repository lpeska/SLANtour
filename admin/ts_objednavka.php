<?php
/**   \file
 * pdf_objednavka.php - hlavni stranka klientske casti systemu
 *    - je na ni provaden reload pø zpracování formuláøù ostatních modulù
 *    - zobrazuje podrobné vyhledávání a tipy na zájezdy
 */
//spusteni prace se sessions
//session_start();
if($_GET["from_klient"]==1 and $_SESSION["jmeno_klient"]!=""){
        $_SESSION["jmeno"] = "ts_proxy";
        $_SESSION["heslo"] = "6e02d14fa8bdc397eb89a455c6b7bbf369ca5958";
}




    $include_local = "./";
    $include_global = "../"; 

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once $include_local. "core/load_core.inc.php";

//note - v casti TS je pouzit globalni model
require_once $include_global.'global/lib/model/entyties/ObjednavkaEnt.php';
require_once $include_global.'global/lib/model/entyties/SerialEnt.php';
require_once $include_global.'global/lib/model/entyties/ZajezdEnt.php';
require_once $include_global.'global/lib/model/entyties/SmluvniPodminkyNazevEnt.php';
require_once $include_global.'global/lib/model/entyties/SmluvniPodminkyEnt.php';
require_once $include_global.'global/lib/model/entyties/OrganizaceEnt.php';
require_once $include_global.'global/lib/model/entyties/AdresaEnt.php';
require_once $include_global.'global/lib/model/entyties/UserKlientEnt.php';
require_once $include_global.'global/lib/model/entyties/SluzbaEnt.php';
require_once $include_global.'global/lib/model/entyties/SlevaEnt.php';
require_once $include_global.'global/lib/model/entyties/FakturaEnt.php';
require_once $include_global.'global/lib/model/entyties/PlatbaEnt.php';
require_once $include_global.'global/lib/model/entyties/FotoEnt.php';
require_once $include_global."global/lib/model/entyties/TerminObjektoveKategorieEnt.php";
require_once $include_global."global/lib/model/entyties/ObjektovaKategorieEnt.php";
require_once $include_global."global/lib/model/entyties/ObjektEnt.php";
require_once $include_global.'global/lib/model/holders/SmluvniPodminkyHolder.php';
require_once $include_global.'global/lib/model/holders/AdresaHolder.php';
require_once $include_global.'global/lib/model/holders/UserKlientHolder.php';
require_once $include_global.'global/lib/model/holders/SluzbaHolder.php';
require_once $include_global.'global/lib/model/holders/SlevaHolder.php';
require_once $include_global.'global/lib/model/holders/FakturaHolder.php';
require_once $include_global.'global/lib/model/holders/PlatbaHolder.php';
require_once $include_global.'global/lib/model/holders/ObjektovaKategorieHolder.php';
require_once $include_global."global/lib/model/holders/TerminObjektoveKategorieHolder.php";
require_once $include_global.'global/lib/cfg/ViewConfig.php';
require_once $include_global.'global/lib/cfg/DatabaseConfig.php';
require_once $include_global.'global/lib/cfg/CommonConfig.php';
require_once $include_global.'global/lib/db/SQLQuery.php';
require_once $include_global.'global/lib/db/DatabaseProvider.php';
require_once $include_global.'global/lib/db/dao/ObjednavkyDAO.php';
require_once $include_global.'global/lib/db/dao/sql/ObjednavkySQLBuilder.php';
ObjednavkyDAO::init();

/*LOCAL*/
require_once $include_local."classes/dataContainers/tsObjednavajici.php";
require_once $include_local."classes/dataContainers/tsObjednavka.php";
require_once $include_local."classes/dataContainers/tsOsoba.php";
require_once $include_local."classes/dataContainers/tsPlatba.php";
require_once $include_local."classes/dataContainers/tsProdejce.php";
require_once $include_local."classes/dataContainers/tsSluzba.php";
require_once $include_local."classes/dataContainers/tsStaticDescription.php";
require_once $include_local."classes/dataContainers/tsZajezd.php";
require_once $include_local."classes/dataContainers/tsProvize.php";
require_once $include_local."classes/dataContainers/tsSleva.php";
require_once $include_local."classes/dataContainers/tsSmluvniPodminky.php";
require_once $include_local."classes/dataContainers/tsObjektovaKategorie.php";
require_once $include_local."classes/dataContainers/tsOrganizace.php";
require_once $include_local."classes/dataContainers/tsAdresa.php";

require_once $include_local."classes/ts/objednavka_dao.inc.php";
require_once $include_local."classes/ts/objednavka_displayer.inc.php";
require_once $include_local."classes/ts/objednavka_ts.inc.php";
require_once $include_local."classes/ts/utils_ts.inc.php";

require_once $include_local."classes/vouchery/VoucheryModelConfig.php";

$ts = new ObjednavkaTS($_GET["id_objednavka"], $_GET["security_code"], $_GET["type"]);

$html = $ts->createHtml();
//echo $html;
$errorMessage = "";
/*if($_GET["type"]=="cestovni_smlouva"){
    echo $html;
}else */

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
    //echo $html;
} else {
    ?>

    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
    <html lang="cs">
    <head>
        <title>
            SLAN tour | tvorba PDF objednávky
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

