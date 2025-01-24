<?
/**   \file
 * faktury.php  - administrace dalších faktur
 *                - pridavani fotek k jednotlivým fakturm
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_foto = id fotky
 * @param $id_faktury = id faktury
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

//note - v casti TS je pouzit globalni model
require_once '../global/lib/model/entyties/ObjednavkaEnt.php';
require_once '../global/lib/model/entyties/SerialEnt.php';
require_once '../global/lib/model/entyties/ZajezdEnt.php';
require_once '../global/lib/model/entyties/SmluvniPodminkyNazevEnt.php';
require_once '../global/lib/model/entyties/SmluvniPodminkyEnt.php';
require_once '../global/lib/model/entyties/OrganizaceEnt.php';
require_once '../global/lib/model/entyties/AdresaEnt.php';
require_once '../global/lib/model/entyties/UserKlientEnt.php';
require_once '../global/lib/model/entyties/SluzbaEnt.php';
require_once '../global/lib/model/entyties/SlevaEnt.php';
require_once '../global/lib/model/entyties/FakturaEnt.php';
require_once '../global/lib/model/entyties/PlatbaEnt.php';
require_once '../global/lib/model/entyties/FotoEnt.php';
require_once "../global/lib/model/entyties/TerminObjektoveKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektovaKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektEnt.php";
require_once '../global/lib/model/holders/SmluvniPodminkyHolder.php';
require_once '../global/lib/model/holders/AdresaHolder.php';
require_once '../global/lib/model/holders/UserKlientHolder.php';
require_once '../global/lib/model/holders/SluzbaHolder.php';
require_once '../global/lib/model/holders/SlevaHolder.php';
require_once '../global/lib/model/holders/FakturaHolder.php';
require_once '../global/lib/model/holders/PlatbaHolder.php';
require_once "../global/lib/model/holders/TerminObjektoveKategorieHolder.php";
require_once "../global/lib/model/holders/ObjektovaKategorieHolder.php";
require_once '../global/lib/cfg/ViewConfig.php';
require_once '../global/lib/cfg/DatabaseConfig.php';
require_once '../global/lib/cfg/CommonConfig.php';
require_once "../global/lib/utils/CommonUtils.php";
require_once '../global/lib/db/SQLQuery.php';
require_once '../global/lib/db/DatabaseProvider.php';
require_once '../global/lib/db/dao/ObjednavkyDAO.php';
require_once '../global/lib/db/dao/sql/ObjednavkySQLBuilder.php';
ObjednavkyDAO::init();

require_once "./classes/faktury_list.inc.php"; //seznam serialu
require_once "./classes/faktury.inc.php"; //detail seriálu
require_once "./classes/rezervace_platba.inc.php"; //detail seriálu

require_once "./classes/ts/objednavka_dao.inc.php";
require_once "./classes/ts/objednavka_displayer.inc.php";
require_once "./classes/dataContainers/tsObjednavajici.php";
require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsOsoba.php";
require_once "./classes/dataContainers/tsPlatba.php";
require_once "./classes/dataContainers/tsProdejce.php";
require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsProvize.php";
require_once "./classes/dataContainers/tsSleva.php";
require_once "./classes/dataContainers/tsSmluvniPodminky.php";
require_once "./classes/dataContainers/tsObjektovaKategorie.php";
require_once "./classes/dataContainers/tsStaticDescription.php";
require_once "./classes/dataContainers/tsOrganizace.php";
require_once "./classes/dataContainers/tsAdresa.php";

require_once "./classes/vouchery/VoucheryModelConfig.php";
require_once "./classes/vouchery/VoucheryUtils.php";
require_once "./classes/ts/faktura_ts.inc.php";
require_once "./classes/ts/utils_ts.inc.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";
/*
//pripojeni k databazi
$database = new Database();

//spusteni prace se sessions
	session_start(); 
	
//vytvori do pormenne $zamestnanec instanci tridy User_zamestnanec na zaklade prihlaseni v $_POST nebo $_SESSION
	require_once "./includes/set_user.inc.php";
*/


/*--------------	POZADAVKY DO DATABAZE	-------------------------*/
//nactu faktury o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    /*---------------------serial_list ---------------*/
    if ($_GET["typ"] == "faktury_list") {


        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //rozdeleni pole zeme:destinace na id_zeme a id_destinace
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "zeme-destinace-nazev") {
                $_SESSION["cislo_faktury"] = $_POST["cislo_faktury"];
                $_SESSION["faktura_prijemce"] = $_POST["faktura_prijemce"]; //hledá se v textu pøíjemce
                $_SESSION["serial_nazev"] = $_POST["serial_nazev"];
                $_SESSION["faktura_klient"] = $_POST["faktura_klient"]; //hledá se v navázaném objednávajícím
                $_SESSION["datum_vystaveni"] = $_POST["datum_vystaveni"];
                $_SESSION["id_objednavka"] = $_POST["id_objednavka"];
                $_SESSION["faktura_uhrazeno"] = $_POST["faktura_uhrazeno"];


            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["faktury_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury_list";
        } else if ($_GET["id_objednavka"] != "") {
            $_SESSION["id_objednavka"] = $_GET["id_objednavka"];
        }

        /*--------------------- faktury ---------------*/
    } else if ($_GET["typ"] == "faktury") {

        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Faktury("create", $zamestnanec->get_id(), "", $_GET["id_objednavka"]);
            if (!$dotaz->get_error_message()) {
                $id_faktury = $dotaz->get_id();
                $cislo_faktury = $dotaz->get_cislo_faktury();
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location dle zmacknuteho tlacitka
                if ($_POST["submit_button"] == "Uložit") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury&pozadavek=edit&id_faktury=$id_faktury";
                    
                } else if ($_POST["submit_button"] == "Uložit a zavøít") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury_list";
                    
                } else if ($_POST["submit_button"] == "Uložit a vygenerovat PDF") {
                    $adress = "/admin/ts_faktura.php?id_faktury=" . $id_faktury . "&page=create-new-pdf";
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();

            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            // echo "inside faktury";
            //  print_r($_GET);
            //  print_r($_POST);
            $dotaz = new Faktury("update", $zamestnanec->get_id(), $_GET["id_faktury"], $_GET["id_objednavka"]);
            if (!$dotaz->get_error_message()) {
                $id_faktury = $_GET["id_faktury"];
                $cislo_faktury = $dotaz->get_cislo_faktury();
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location dle zmacknuteho tlacitka
                if ($_POST["submit_button"] == "Uložit") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury&pozadavek=edit&id_faktury=" . $_GET["id_faktury"] . "";
                } else if ($_POST["submit_button"] == "Uložit a zavøít") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury_list";
                } else if ($_POST["submit_button"] == "Uložit a vygenerovat PDF") {
                    $adress = "/admin/ts_faktura.php?id_faktury=" . $_GET["id_faktury"] . "&page=create-new-pdf";
                    //$adress = $_SERVER['SCRIPT_NAME']."?typ=faktury&pozadavek=edit&id_faktury=$id_faktury";
                } else {
                    $adress = "/admin/ts_faktura.php?id_faktury=" . $_GET["id_faktury"] . "&page=create-new-pdf";
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Faktury("delete", $zamestnanec->get_id(), $_GET["id_faktury"], $_GET["id_objednavka"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
        if (($_GET["pozadavek"] == "update" or $_GET["pozadavek"] == "create") and $_POST["nova_platba_castka"] > 0 and !$dotaz->get_error_message()) {
            $dotaz_platba = new Rezervace_platba("create", $zamestnanec->get_id(), "", $_GET["id_objednavka"], $_POST["nova_platba_castka"], $_POST["datum_vytvoreni"], $_POST["datum_splatnosti"], $_POST["nova_platba_splaceno"], $_POST["cislo_dokladu"], $_POST["nova_platba_zpusob_uhrady"], $id_faktury);
        }else if($_GET["pozadavek"] == "add_platba" and $_POST["nova_platba_castka"] > 0){
           $dotaz_platba = new Rezervace_platba("create", $zamestnanec->get_id(), "", $_GET["id_objednavka"], $_POST["nova_platba_castka"], $_POST["datum_vytvoreni"], $_POST["datum_splatnosti"], $_POST["nova_platba_splaceno"], $_POST["cislo_dokladu"], $_POST["nova_platba_zpusob_uhrady"],  $_GET["id_faktury"]);
           $dotaz_platba->update_stav_faktury();
          //print_r($_POST);
           $adress = $_SERVER['SCRIPT_NAME'] . "?typ=faktury_list";
           if(!$dotaz_platba->get_error_message()){
               $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_platba->get_ok_message();
           }else{
               $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_platba->get_error_message();
           }
        }
        if ($_GET["pozadavek"] == "update" or $_GET["pozadavek"] == "create"){
            $dotaz->check_splaceno();
        }
    }

}

//pokud byl nejaky pozadavek na reload stranky, tak ho provedu
if ($adress) {
    header("Location: https://" . $_SERVER['SERVER_NAME'] . $adress);
    exit;
}
//zpracovani hlasky poslane z minule stranky (jsme za headerem pro presmerovani)	
if ($_SESSION["hlaska"] != "") {
    $hlaska_k_vypsani = $_SESSION["hlaska"];
    $_SESSION["hlaska"] = "";
} else {
    $hlaska_k_vypsani = "";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
    <?
    $core = Core::get_instance();
    echo "<title>" . $core->show_nazev_modulu() . " | Administrace systému RSCK</title>";
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/>
    <meta name="copyright" content="&copy; Slantour"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="robots" content="noindex,noFOLLOW"/>
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="css/reset-min.css">
    <link rel="stylesheet" type="text/css" href="./new-menu/style.css" media="all"/>
<link type="text/css" href="./css/jquery-ui.min.css" rel="stylesheet" />
    <script type="text/javascript" src="./js/jquery-min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui-cze.min.js"></script>    
    <!-- 
    <script type="text/javascript" src="./js/common_functions.js"></script>  -->
</head>
<body>
<?
if ($zamestnanec->get_correct_login()) {
//prihlaseni probehlo vporadku, muzu pokracovat
    //zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

    //zobrazeni aktualnich fakturu - nove rezervace, pozadavky...
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            /*
                nejprve zjistim v jake objekty budu obsluhovat
                    -(serial, zajezd, cena, cena_zajezdu, foto, dokument, faktury)
            */
            //na zacatku zobrazim seznam serialu
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "faktury_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "faktury_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci serial_list
                $faktury_list = new Faktury_list($_SESSION["cislo_faktury"], $_SESSION["faktura_prijemce"], $_SESSION["serial_nazev"], $_SESSION["faktura_klient"], $_SESSION["datum_vystaveni"], $_SESSION["id_objednavka"], $_GET["str"], $_SESSION["faktury_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $faktury_list->get_error_message();
                //zobrazim filtry
                echo $faktury_list->show_filtr();
                ?>
                <h3>Seznam faktur</h3>
                <?
                //hlavièka tabulky
                echo $faktury_list->show_list_header();
                //vypis jednotlivych serialu
                while ($faktury_list->get_next_radek()) {
                    echo $faktury_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($faktury_list->getZacatek(), $faktury_list->getPocetZajezdu(), $faktury_list->getPocetZaznamu());

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "faktury" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
                $faktury = new Faktury("new", $zamestnanec->get_id(), "", $_GET["id_objednavka"], $_GET["pozadavek"]);
                ?>
                <div class="submenu"><a href="?typ=faktury_list">&lt;&lt; seznam faktur</a></div>
                <?php
                $serial_nazev = $faktury->get_nazev();
                $objednavka_klient = $faktury->get_objednavajici();
                $id_objednavka = $faktury->get_id_objednavka();
                $id_serial = $faktury->get_id_serial();
                $id_zajezd = $faktury->get_id_zajezd();
                $zajezd = $faktury->get_zajezd();

                echo "<div class='submenu'>Seriál: <a href=\"/admin/serial.php?id_serial=" . $id_serial . "&typ=serial&pozadavek=edit\"> " . $id_serial . " (" . $serial_nazev . ")</a> ";
                echo "Zájezd: <a href=\"/admin/serial.php?id_serial=" . $id_serial . "&id_zajezd=$id_zajezd&typ=zajezd&pozadavek=edit\"> " . $id_zajezd . " (" . $zajezd . ")</a> ";
                echo "Objednávka: <a href=\"/admin/rezervace.php?id_objednavka=" . $id_objednavka . "&typ=rezervace&pozadavek=show\"> " . $id_objednavka . " (" . $objednavka_klient . ")</a></div>";
                ?>

                <?

                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit novou fakturu</h3><?
                echo $faktury->show_form();

            } else if ($_GET["typ"] == "faktury" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //vypisu menu
                $faktury = new Faktury("edit", $zamestnanec->get_id(), $_GET["id_faktury"], "", $_GET["pozadavek"]);


                echo "<div class='submenu'>";
                echo "  <a href='?typ=faktury_list'>&lt;&lt; seznam faktur</a>";
                echo "  <a href='?id_faktury=" . $faktury->get_id() . "&amp;typ=informace&amp;pozadavek=edit'>informace</a>";
                echo "  <a href='?id_faktury=" . $faktury->get_id() . "&amp;typ=foto'>foto</a>";
                echo "  <a class='action-delete' href='?id_faktury=" . $faktury->get_id() . "&amp;typ=informace&amp;pozadavek=delete' onclick='javascript:return confirm('Opravdu chcete smazat objekt?')'>delete</a>";
                echo "</div>";

                $serial_nazev = $faktury->get_nazev();
                $objednavka_klient = $faktury->get_klient_cele_jmeno();
                $id_objednavka = $faktury->get_id_objednavka();
                $id_serial = $faktury->get_id_serial();
                echo "<div class='submenu'>Seriál: <a href=\"/admin/serial.php?id_serial=" . $id_serial . "&typ=serial&pozadavek=edit\"> " . $id_serial . " (" . $serial_nazev . ")</a></div>";
                echo "<div class='submenu'>Objednávka: <a href=\"/admin/rezervace.php?id_objednavka=" . $id_objednavka . "&typ=rezervace&pozadavek=show\"> " . $id_objednavka . " (" . $objednavka_klient . ")</a></div>";
                ?>

                <h3>Editace faktury</h3><?
                echo $faktury->show_form();
            }
            ?>
        </div>
    </div>
    <?
    //zobrazeni napovedy k modulu
    $core = Core::get_instance();
    echo ModulView::showHelp($core->show_current_modul()["napoveda"]);
} else {
    //zadny uzivatel neni prihlasen, vypisu logovaci formular
    echo ModulView::showLoginForm($zamestnanec->get_uzivatelske_jmeno());
    echo $zamestnanec->get_error_message();
}
?>

</body>
</html>