<?
/**   \file
 * foto.php
 *                - administrace fotek
 *                - upload na server
 *                - zmeny popisku atd.
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_foto = id fotky
 */
//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";
require_once "./classes/slevy.inc.php"; //upravy jednotlivych fotografii
require_once "./classes/slevy_list.inc.php"; //seznamy fotek
require_once "./classes/serial_list.inc.php";
require_once "./classes/zajezd_list.inc.php";

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


/* --------------	POZADAVKY DO DATABAZE	------------------------- */
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    /* --------------------- dokument_list --------------- */
    if ($_GET["typ"] == "slevy_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //je-li to treba, zaregistrujeme sessions
            //INFO: deprecated - nemelo by byt treba
//            if (!isset($_SESSION["slevy_order_by"])) {
//                session_register("nazev_slevy");
//                session_register("slevy_order_by");
//            }
            //kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_slevy"] = $_POST["nazev"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["slevy_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_list";
        }

        /* ---------------------serial--------------- */
    } else if ($_GET["typ"] == "slevy") {

        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù						
            $dotaz = new Slevy("create", $zamestnanec->get_id(), "", $_POST["nazev_slevy"], $_POST["zkraceny_nazev"],
                $_POST["platnost_od"], $_POST["platnost_do"], $_POST["castka"], $_POST["mena"], $_POST["poznamka"], $_POST["sleva_staly_klient"]);

            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Slevy("update", $zamestnanec->get_id(), $_GET["id_slevy"], $_POST["nazev_slevy"], $_POST["zkraceny_nazev"],
                $_POST["platnost_od"], $_POST["platnost_do"], $_POST["castka"], $_POST["mena"], $_POST["poznamka"], $_POST["sleva_staly_klient"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Slevy("delete", $zamestnanec->get_id(), $_GET["id_slevy"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    }
    //if($_GET["typ"]==...
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
    <!--  potrebuju funkci pro zobrazeni dalsich moznosti v moznosti editace  -->
    <script type="text/javascript" src="./js/serial.js"></script>
    <script type="text/javascript" src="./js/common_functions.js"></script>
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

            //na zacatku zobrazim seznam dokumentù
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "slevy_list";
            }

            /* ----------------	seznam dokumentù ----------- */
            if ($_GET["typ"] == "slevy_list") {
                if ($_GET["str"] == "") {
                    $_GET["str"] = 0;
                }

                //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
                $slevy_list = new Slevy_list($zamestnanec->get_id(), $_SESSION["nazev_slevy"], $_GET["str"], $_SESSION["slevy_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $slevy_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=slevy&amp;pozadavek=new">vytvoøit novou slevu</a>
                </div>
                <?
                //zobrazeni filtru pro vypis fotek
                echo $slevy_list->show_filtr();
                ?>
                <h3>Seznam slev</h3>
                <?
                echo $slevy_list->show_list_header();


                //zobrazeni jednotlivych zaznamu
                while ($slevy_list->get_next_radek()) {
                    echo $slevy_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($slevy_list->getZacatek(), $slevy_list->getPocetZajezdu(), $slevy_list->getPocetZaznamu());
                /* ----------------	nový dokument ----------- */
            } else if ($_GET["typ"] == "slevy" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
                ?>
                <div class="submenu">
                    <a href="?typ=slevy_list">&lt;&lt; seznam slev</a>
                </div>
                <?
                $foto = new Slevy("new", $zamestnanec->get_id(), "", $_POST["nazev_slevy"], $_POST["zkraceny_nazev"],
                    $_POST["platnost_od"], $_POST["platnost_do"], $_POST["castka"], $_POST["mena"], $_POST["poznamka"], $_POST["sleva_staly_klient"], $_GET["pozadavek"]);
                ?><h3>Vytvoøit novou slevu</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $foto->show_form();

                /* ----------------	editace dokumentu ----------- */
            } else if ($_GET["typ"] == "slevy" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                ?>
                <div class="submenu">
                    <a href="?typ=slevy_list">&lt;&lt; seznam slev</a>
                    <a href="?typ=slevy&amp;pozadavek=new">vytvoøit novou slevu</a>
                </div>
                <?
                $foto = new Slevy("edit", $zamestnanec->get_id(), $_GET["id_slevy"], $_POST["nazev_slevy"], $_POST["zkraceny_nazev"],
                    $_POST["platnost_od"], $_POST["platnost_do"], $_POST["castka"], $_POST["mena"], $_POST["poznamka"], $_POST["sleva_staly_klient"], $_GET["pozadavek"]);
                ?><h3>Editace slev</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $foto->show_form();
            } else if ($_GET["typ"] == "slevy" and ($_GET["pozadavek"] == "serialy_zajezdy")) {
                $sleva = new Slevy("show", $zamestnanec->get_id(), $_GET["id_slevy"]);
                //TODO: inicializace - jen nahodne hodnoty
                $serialy_list = new Serial_list(0, 0, "nazev", 0, 1, "s.nazev", null, 10, "serialy-slevy");
                echo $serialy_list->get_error_message();

                //mam id slevy a potrebuju id serialu, ke kterymu patri zajezd, ktery je slevneny
                $zajezdy_list = new Zajezd_list(0, "", "zajezdy-slevy");
                echo $zajezdy_list->get_error_message();

                echo "<div class='submenu'>" . $sleva->get_nazev_slevy() . "</div>";
                ?>
                <div class="submenu">
                    <a href="?typ=slevy_list">&lt;&lt; seznam slev</a>
                    <a href="?typ=slevy&amp;pozadavek=new">vytvoøit novou slevu</a>
                </div>
                <h3>Seriály</h3>
                <?php
                echo $serialy_list->show_list_header();
                while ($serialy_list->get_next_radek()) {
                    echo $serialy_list->show_list_item("tabulka_slevy");
                }
                echo "</table>";
                ?>
                <h3>Zájezdy</h3>
                <?php
                echo $zajezdy_list->show_list_header("tabulka_slevy");
                while ($zajezdy_list->get_next_radek()) {
                    echo $zajezdy_list->show_list_item("tabulka_slevy");
                }
                echo "</table>";
                ?>

            <?
            } //if typ
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