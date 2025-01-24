<?
/**   \file
 * dokumenty.php
 *                - administrace dokumentù
 *                - upload na server
 *                - zmeny popisku atd.
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_dokument = id dokumentu
 */
//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/dokument.inc.php"; //seznamy dokumentu
require_once "./classes/dokument_list.inc.php"; //seznamy dokumentu
require_once "./classes/serial_list.inc.php"; //seznamy serialu

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
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    /*--------------------- dokument_list ---------------*/
    if ($_GET["typ"] == "dokument_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_dokument"] = $_POST["nazev_dokument"];
                $_SESSION["dokument_datum_od"] = $_POST["dokument_datum_od"];
                $_SESSION["dokument_datum_do"] = $_POST["dokument_datum_do"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["dokument_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument_list";
        }

        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "dokument") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Dokument("create", $zamestnanec->get_id(), "", $_POST["nazev_dokument"], $_POST["popisek_dokument"], $_POST["dokument"], $_POST["is_tiskova_zprava"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Dokument("update", $zamestnanec->get_id(), $_GET["id_dokument"], $_POST["nazev_dokument"], $_POST["popisek_dokument"], $_POST["dokument"], $_POST["is_tiskova_zprava"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Dokument("delete", $zamestnanec->get_id(), $_GET["id_dokument"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
//					$adress = $_SERVER['SCRIPT_NAME']."?typ=dokument_list";					
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "mass_del") {
            $dotaz = new Dokument("mass_del", $zamestnanec->get_id());
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument_list";
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
                $_GET["typ"] = "dokument_list";
            }

            /*----------------	seznam dokumentù -----------*/
            if ($_GET["typ"] == "dokument_list") {
                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //seznam dokumentu - parametry nazev_dokumentu, pocatek vypisu a pocet zaznamu(default. nastaveny)
                $dokument_list = new Dokument_list($zamestnanec->get_id(), $_SESSION["nazev_dokument"], $_GET["str"], $_SESSION["dokument_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $dokument_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=dokument&amp;pozadavek=new">vytvoøit nový dokument</a>
                </div>
                <?

                //zobrazeni filtru pro vypis dokumentù
                echo $dokument_list->show_filtr();
                ?>
                <h3>Seznam dokumentù</h3>
                <?
                //zobrazeni hlavicky seznamu
                echo $dokument_list->show_list_header();

                //zobrazeni jednotlivych zaznamu
                while ($dokument_list->get_next_radek()) {
                    echo $dokument_list->show_list_item("tabulka_dokument");
                }
                ?>
                </table>
                <script type='text/javascript' src='js/massDel.js'></script>
                <form method="post" action="?typ=dokument&pozadavek=mass_del">
                    <input type="hidden" id="ids-massdel" name="massdel_ids"/>
                    <input type="submit" class="action-delete" value="Hromadnì smazat" id="button-massdel" onclick="return !isEmpty();" disabled="disabled"/>
                </form>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($dokument_list->getZacatek(), $dokument_list->getPocetZajezdu(), $dokument_list->getPocetZaznamu());

                /*----------------	nový dokument -----------*/
            } else if ($_GET["typ"] == "dokument" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=dokument_list">&lt;&lt; seznam dokumentù</a>
                </div>
                <?
                $dokument = new Dokument("new", $zamestnanec->get_id(), "", $_POST["nazev_dokument"], $_POST["popisek_dokument"], $_POST["dokument"], $_GET["pozadavek"]);

                ?><h3>Vytvoøit nový dokument</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $dokument->show_form();

                /*----------------	editace dokumentu -----------*/
            } else if ($_GET["typ"] == "dokument" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                ?>
                <div class="submenu">
                    <a href="?typ=dokument_list">&lt;&lt; seznam dokumentù</a>
                    <a href="?typ=dokument&amp;pozadavek=new">vytvoøit nový dokument</a>
                </div>
                <?
                $dokument = new Dokument("edit", $zamestnanec->get_id(), $_GET["id_dokument"], $_POST["nazev_dokument"], $_POST["popisek_dokument"], $_POST["dokument"], $_GET["pozadavek"]);

                ?><h3>Editace dokumentu</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $dokument->show_form();

            } else if ($_GET["typ"] == "dokument" and ($_GET["pozadavek"] == "serialy_dokumenty")) {
                $dokument = new Dokument("show", $zamestnanec->get_id(), $_GET["id_dokument"]);
                //TODO: inicializace - jen nahodne hodnoty
                $serialy_list = new Serial_list(0, 0, "nazev", 0, 1, "s.nazev", null, 10, "serialy-dokumenty");
                echo $serialy_list->get_error_message();

                echo "<div class='submenu'>" . $dokument->get_nazev() . "</div>";
                ?>
                <div class="submenu">
                    <a href="?typ=dokument_list">&lt;&lt; seznam dokumentu</a>
                    <a href="?typ=dokument&amp;pozadavek=new">vytvoøit nový dokument</a>
                </div>
                <h3>Seriály</h3>
                <?php
                echo $serialy_list->show_list_header();
                while ($serialy_list->get_next_radek()) {
                    echo $serialy_list->show_list_item("tabulka_slevy");
                }
                echo "</table>";
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