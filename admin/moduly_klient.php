<?
/**   \file
 * moduly.php  - správa jednotlivých modulù klientské èásti systému
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_modul = id modulu
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru	
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/modul_klient_list.inc.php"; //seznamy klientù
require_once "./classes/modul_klient.inc.php"; //detail seriálu

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
    if ($_GET["typ"] == "modul_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "ord_by") {
                $_SESSION["modul_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=modul_list";
        }
    } else if ($_GET["typ"] == "modul") {

        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Modul_klient("create", $zamestnanec->get_id(), "", $_POST["nazev_modulu"], $_POST["adresa_modulu"], $_POST["povoleno"], $_POST["typ_modulu"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=modul_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Modul_klient("update", $zamestnanec->get_id(), $_GET["id_modul"], $_POST["nazev_modulu"], $_POST["adresa_modulu"], $_POST["povoleno"], $_POST["typ_modulu"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=modul_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Modul_klient("delete", $zamestnanec->get_id(), $_GET["id_modul"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=modul_list";
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
                $_GET["typ"] = "modul_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "modul_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=modul&amp;pozadavek=new">vytvoøit nový modul</a>
                </div>
                <?
                //vytvorime instanci klient_list
                $modul_list = new Modul_klient_list($_SESSION["modul_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $modul_list->get_error_message();
                //zobrazim hlavicku seznamu
                echo $modul_list->show_list_header();

                //vypis jednotlivych serialu
                while ($modul_list->get_next_radek()) {
                    echo $modul_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "modul" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=modul_list">&lt;&lt; seznam modulù</a>
                </div>
                <?
                $modul = new Modul_klient("new", $zamestnanec->get_id(), "", $_POST["nazev_modulu"], $_POST["adresa_modulu"], $_POST["povoleno"], $_POST["typ_modulu"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit nový modul</h3><?
                echo $modul->show_form();

            } else if ($_GET["typ"] == "modul" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=modul_list">&lt;&lt; seznam modulù</a>
                    <a href="?typ=modul&amp;pozadavek=new">vytvoøit nový modul</a>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $modul = new Modul_klient("edit", $zamestnanec->get_id(), $_GET["id_modul"], $_POST["nazev_modulu"], $_POST["adresa_modulu"], $_POST["povoleno"], $_POST["typ_modulu"], $_GET["pozadavek"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Editace modulu</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $modul->show_form();
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