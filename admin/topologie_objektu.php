<?
/**   \file
 * klienti.php  - seznam klientù ck
 *                 - jejich pøidávání/editace
 *                 - odkazy na rezervace klientù
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_klient = id klienta
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";


require_once "./classes/topologie_list.inc.php"; //seznamy klientù
require_once "./classes/topologie.inc.php"; //detail seriálu

require_once "./classes/tok_list.inc.php"; //seznam fotografií serialu
require_once "./classes/tok.inc.php"; //seznam fotografií serialu

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";
/*
//pripojeni k databazi
$database = new Database();

//spusteni prace se sessions
	
	
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
    if ($_GET["typ"] == "topologie_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy klient_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["topologie_nazev"] = $_POST["nazev_topologie"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["topologie_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=topologie_list&moznosti_editace=" . $_GET["moznosti_editace"] . "";
        }

        /*---------------------serial---------------*/
    
    }else if($_GET["typ"] == "topologie"){
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Topologie("create", $zamestnanec->get_id());
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                if ($_POST["ulozit"] != "") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_topologie=" . $dotaz->get_id() . "&typ=topologie&pozadavek=edit";
                } else if ($_POST["ulozit_a_zavrit"]) {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=topologie_list";
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            
        }else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Topologie("update", $zamestnanec->get_id(), $_GET["id_topologie"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                if ($_POST["ulozit"] != "") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_topologie=" . $_GET["id_topologie"] . "&typ=topologie&pozadavek=edit";
                } else if ($_POST["ulozit_a_zavrit"]) {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=topologie_list";
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }else if ($_GET["pozadavek"] == "update_zasedaci_poradek") {
            $dotaz = new Topologie("update_zasedaci_poradek", $zamestnanec->get_id(), $_POST["id_topologie"], $_GET["id_tok_topologie"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                if ($_POST["ulozit"] != "") {
                    $adress = $_SERVER['SCRIPT_NAME']."?id_serial=".$_GET["id_serial"]."&id_zajezd=".$_GET["id_zajezd"]."&id_topologie=".$_POST["id_topologie"]."&id_tok_topologie=".$_GET["id_tok_topologie"]."&typ=topologie&pozadavek=zasedaci_poradek";
                } else if ($_POST["ulozit_a_zavrit"]) {
                    $adress = "/admin/serial.php?id_serial=".$_GET["id_serial"]."&id_zajezd=".$_GET["id_zajezd"]."&typ=topologie&pozadavek=show";
                } else if ($_POST["ulozit_a_vygenerovat"]) {
                    $adress = "/admin/ts_topologie.php?id_tok_topologie=".$_GET["id_tok_topologie"]."";
                }
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
//print_r($_POST);
//zpracovani hlasky poslane z minule stranky (jsme za headerem pro presmerovani)	
if ($_SESSION["hlaska"] != "") {
    $hlaska_k_vypsani = $_SESSION["hlaska"];
    $_SESSION["hlaska"] = "";
} else {
    $hlaska_k_vypsani = "";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html  xmlns="http://www.w3.org/1999/xhtml">
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
    <link rel="stylesheet" type="text/css" href="/admin/gridster/dist/jquery.gridster.css"/>
    <link rel="stylesheet" type="text/css" href="/admin/gridster/demo.css"/>

  
            <link type="text/css" href="/jqueryui/css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
    <script type="text/javascript" src="/jqueryui/js/jquery-1.7.1.min.js"></script>
        <script type="text/javascript" src="/jqueryui/js/jquery-ui-1.8.18.custom.min.js"></script>
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
        $_GET["typ"] = "topologie_list";
    }

    /*----------------	seznam seriálù -----------*/
    if ($_GET["typ"] == "topologie_list") {

        //pokud nemam strankovani, zacnu nazacatku:)
        if ($_GET["str"] == "") {
            $_GET["str"] = "0";
        }

        //vytvorime instanci objekty_list
        $topologie_list = new Topologie_list($_SESSION["topologie_nazev"], $_GET["str"], $_SESSION["topologie_order_by"]);
        //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
        echo $topologie_list->get_error_message();

        //vypisu menu
        ?>
        <div class="submenu">
            <? echo "<a href=\"?typ=topologie&amp;pozadavek=new\">vytvoøit novou topologii</a>" ?>
        </div>
        <?
        //zobrazim filtry
        echo $topologie_list->show_filtr();
        //zobrazim hlavicku seznamu
        echo $topologie_list->show_list_header();

        //vypis jednotlivych serialu
        while ($topologie_list->get_next_radek()) {
            echo $topologie_list->show_list_item("tabulka");
        }
        ?>
        </table>
        <?
        //zobrazeni strankovani
        echo ModulView::showPaging($topologie_list->getZacatek(), $topologie_list->getPocetZajezdu(), $topologie_list->getPocetZaznamu());

        /*----------------	nový seriál -----------*/
    } else if ($_GET["typ"] == "topologie" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

        ?>
        <div class="submenu">
            <a href="?typ=topologie_list">&lt;&lt; seznam topologií</a>
        </div>
        <?
        $topologie = new Topologie("new", $zamestnanec->get_id());
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        ?><h3>Vytvoøit novou topologii</h3><?
        echo $topologie->show_form();

    } else if ($_GET["typ"] == "topologie" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
        //nejaky objekty uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=topologie_list">&lt;&lt; seznam topologií</a>
            <a href="?typ=topologie&amp;pozadavek=new">vytvoøit novou topologii</a>
        </div>
        <?
        // print_r($_GET);
        //podle typu pozadvku vytvorim instanci tridy serial
        $topologie = new Topologie("edit", $zamestnanec->get_id(), $_GET["id_topologie"]);
        //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
        ?>
        <h3>Editace Topologie</h3>
        <?
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        echo $topologie->show_form();    
        
    }else if ($_GET["typ"] == "topologie" and  ($_GET["pozadavek"] == "zasedaci_poradek")) {
        //nejaky objekty uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=topologie_list">&lt;&lt; seznam topologií</a>
            <a href="?typ=topologie&amp;pozadavek=new">vytvoøit novou topologii</a>
        </div>
        <script type="text/javascript" src="js/topologie_tok.js"></script>
        <?
        // print_r($_GET);
        //podle typu pozadvku vytvorim instanci tridy serial
        $topologie = new Topologie("zasedaci_poradek", $zamestnanec->get_id(), $_GET["id_topologie"], $_GET["id_tok_topologie"]);
        //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
        ?>
        <h3>Editace zasedacího poøádku</h3>
        <?
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        echo $topologie->show_form_tok();    
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