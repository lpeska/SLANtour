<?
/**   \file
 * centralni_datay.php  - správa jednotlivých centralni_dataù administraèní èásti systému
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_centralni_data = id centralni_datau
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru	
//nahrani potrebnych trid spolecnych pro vsechny centralni_datay a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

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
    if ($_GET["typ"] == "centralni_data_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_data"] = $_POST["nazev"];
                $_SESSION["text_data"] = $_POST["text"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["data_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=centralni_data_list";
        }
    } else if ($_GET["typ"] == "centralni_data") {

        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Centralni_data("create", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["poznamka"], $_POST["text"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=centralni_data_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Centralni_data("update", $zamestnanec->get_id(), $_GET["id_data"], $_POST["nazev"], $_POST["poznamka"], $_POST["text"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=centralni_data_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Centralni_data("delete", $zamestnanec->get_id(), $_GET["id_data"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=centralni_data_list";
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
                $_GET["typ"] = "centralni_data_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "centralni_data_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci klient_list
                $centralni_data_list = new Centralni_data_list($_SESSION["data_order_by"], $_SESSION["nazev_data"], $_SESSION["text_data"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $centralni_data_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=centralni_data&amp;pozadavek=new">vytvoøit nový záznam</a>
                </div>
                <?
                //zobrazim hlavicku seznamu
                echo $centralni_data_list->show_filtr();
                echo $centralni_data_list->show_list_header();

                //vypis jednotlivych serialu
                while ($centralni_data_list->get_next_radek()) {
                    echo $centralni_data_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "centralni_data" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=centralni_data_list">&lt;&lt; seznam dat</a>
                </div>
                <?
                $centralni_data = new Centralni_data("new", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["poznamka"], $_POST["text"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit nový záznam</h3><?
                echo $centralni_data->show_form();

            } else if ($_GET["typ"] == "centralni_data" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=centralni_data_list">&lt;&lt; seznam dat</a>
                    <a href="?typ=centralni_data&amp;pozadavek=new">vytvoøit nový záznam</a>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $centralni_data = new Centralni_data("edit", $zamestnanec->get_id(), $_GET["id_data"], $_POST["nazev"], $_POST["poznamka"], $_POST["text"], $_GET["pozadavek"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Editace záznamu</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $centralni_data->show_form();
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