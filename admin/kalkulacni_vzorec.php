<?
/**   \file
 * moduly.php  - správa jednotlivých modulù administraèní èásti systému
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_modul = id modulu
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru	
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/kalkulacni_vzorec_list.inc.php"; //seznamy klientù
require_once "./classes/kalkulacni_vzorec.inc.php"; //detail seriálu
require_once "./classes/centralni_data_list.inc.php"; //seznamy klientù
require_once "./classes/centralni_data.inc.php"; //detail seriálu
//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

/*
//pripojeni k databazi
$database = new Database();
	
//vytvori do pormenne $zamestnanec instanci tridy User_zamestnanec na zaklade prihlaseni v $_POST nebo $_SESSION
	require_once "./includes/set_user.inc.php";
*/

/*--------------	POZADAVKY DO DATABAZE	-------------------------*/
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    /*---------------------serial_list ---------------*/
    /*--------------------- dokument_list ---------------*/
    if ($_GET["typ"] == "kalkulacni_vzorec_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "ord_by") {
                $_SESSION["kalkulacni_vzorec_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
        }
    } else if ($_GET["typ"] == "kalkulacni_vzorec") {

        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Kalkulacni_vzorec("create", $zamestnanec->get_id(), "", $_POST["nazev_vzorce"], $_POST["vzorec"], $_POST["poznamka"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Kalkulacni_vzorec("update", $zamestnanec->get_id(), $_GET["id_vzorec_def"], $_POST["nazev_vzorce"], $_POST["vzorec"], $_POST["poznamka"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Kalkulacni_vzorec("delete", $zamestnanec->get_id(), $_GET["id_vzorec_def"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

     } else if ($_GET["typ"] == "centralni_data") {

         $_POST["text"] = str_replace(",", ".", $_POST["text"]);
        if ($_GET["pozadavek"] == "create") {

            $_POST["nazev"] = "kalkulace_mena:".$_POST["nazev"];
            $_POST["poznamka"] = "Kurz mìny pouzity v modulu kalkulace - vypocet cen zajezdu";            
            //insert do tabulky seriálù
            $dotaz = new Centralni_data("create", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["poznamka"], $_POST["text"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Centralni_data("update", $zamestnanec->get_id(), $_GET["id_data"], $_POST["nazev"], $_POST["poznamka"], $_POST["text"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Centralni_data("delete", $zamestnanec->get_id(), $_GET["id_data"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kalkulacni_vzorec_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

    }

}
//if zamestnanec->correct_login

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
        <script type="text/javascript" src="./js/jquery-min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui-cze.min.js"></script>
    <script type="text/javascript" src="./js/common_functions.js"></script>
    <script type="text/javascript" src="./js/kalkulacni_vzorec.js"></script>
</head>
<body>

<?
if ($zamestnanec->get_correct_login()) {
//prihlaseni probehlo vporadku, muzu pokracovat
    //zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

    //zobrazeni aktualnich informaci - nove rezervace, pozadavky...
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            //na zacatku zobrazim seznam
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "kalkulacni_vzorec_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "kalkulacni_vzorec_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci klient_list
                $kalkulacni_vzorec_list = new Kalkulacni_vzorec_list($_SESSION["kalkulacni_vzorec_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $kalkulacni_vzorec_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=kalkulacni_vzorec&amp;pozadavek=new">vytvoøit nový Kalkulacni_vzorec</a>
                </div>
            <h3>Kalkulaèní vzorce</h3>
                <?
                //zobrazim hlavicku seznamu
                echo $kalkulacni_vzorec_list->show_list_header();

                //vypis jednotlivych serialu
                while ($kalkulacni_vzorec_list->get_next_radek()) {
                    echo $kalkulacni_vzorec_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?

                //vytvorime instanci klient_list
                $centralni_data_list = new Centralni_data_list("nazev_up", "kalkulace_mena:", "");
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $centralni_data_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=centralni_data&amp;pozadavek=new">Pøidat další kurz</a>
                </div>
             <h3>Seznam kurzù mìn</h3>
                <?
                echo $centralni_data_list->show_list_header("kurzy");

                //vypis jednotlivych serialu
                while ($centralni_data_list->get_next_radek()) {
                    echo $centralni_data_list->show_list_item("tabulka_kurzy");
                }
                ?>
                </table>
                <?
                
                
                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "kalkulacni_vzorec" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=kalkulacni_vzorec_list">&lt;&lt; seznam kalkulaèních vzorcù a kurzù</a>
                </div>
                <?
                $kalkulacni_vzorec = new Kalkulacni_vzorec("new", $zamestnanec->get_id(), "", $_POST["nazev_vzorce"], $_POST["vzorec"], $_POST["poznamka"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit nový modul</h3><?
                echo $kalkulacni_vzorec->show_form();

            } else if ($_GET["typ"] == "kalkulacni_vzorec" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=kalkulacni_vzorec_list">&lt;&lt; seznam kalkulaèních vzorcù a kurzù</a>
                    <a href="?typ=kalkulacni_vzorec&amp;pozadavek=new">vytvoøit nový Kalkulacni_vzorec</a>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $kalkulacni_vzorec = new Kalkulacni_vzorec("edit", $zamestnanec->get_id(), $_GET["id_vzorec_def"],  $_POST["nazev_vzorce"], $_POST["vzorec"], $_POST["poznamka"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Editace modulu</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $kalkulacni_vzorec->show_form();
           } else if ($_GET["typ"] == "centralni_data" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=kalkulacni_vzorec_list">&lt;&lt; seznam kalkulaèních vzorcù a kurzù</a>
                </div>
                <?
                $centralni_data = new Centralni_data("new", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["poznamka"], $_POST["text"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Pøidat nový kurz</h3><?
                echo $centralni_data->show_form("kurzy");

            } else if ($_GET["typ"] == "centralni_data" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=kalkulacni_vzorec_list">&lt;&lt; seznam kalkulaèních vzorcù a kurzù</a>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $centralni_data = new Centralni_data("edit", $zamestnanec->get_id(), $_GET["id_data"], $_POST["nazev"], $_POST["poznamka"], $_POST["text"], $_GET["pozadavek"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Editovat kurz</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $centralni_data->show_form("kurzy");
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