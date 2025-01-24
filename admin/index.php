<?
/**   \file
 * index.php - hlavni stranka administracni casti systemu
 *    - zobrazi nove objednavky zajezdu a prosle opce
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/klient_list.inc.php"; //seznamy klientù
require_once "./classes/klient.inc.php"; //detail klientù
require_once "./classes/zeme_list.inc.php"; //seznamy klientù
require_once "./classes/typ_serialu_list.inc.php"; //seznamy klientù

require_once "./classes/rezervace_list.inc.php"; //seznamy rezervací
require_once "./classes/rezervace.inc.php"; //detail rezervací

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

//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

//zpracovani hlasky poslane z minule stranky (jsme za headerem pro presmerovani)	
if ($_SESSION["hlaska"] != "") {
    $hlaska_k_vypsani = $_SESSION["hlaska"];
    $_SESSION["hlaska"] = "";
} else {
    $hlaska_k_vypsani = "";
}
if ($zamestnanec->get_correct_login() ) {
    //tahle "uvodni" stranka je asi uplne zbytecna ne?
    header("Location: https://" . $_SERVER['SERVER_NAME'] . "/admin/rezervace.php");
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
    <link rel="stylesheet" type="text/css" href="https://yui.yahooapis.com/3.3.0/build/cssreset/reset-min.css">
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
            <div class="submenu">
                <!--<a href="?typ=rezervace&amp;pozadavek=new&amp;clear=1">vytvoøit novou rezervaci</a>-->
                <a href="?typ=rezervace&pozadavek=xxx">vytvoøit novou rezervaci</a>
            <?php echo $backlinks; ?>
            </div>
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            //na zacatku zobrazim seznam
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "rezervace_list";
            }
            /*----------------	seznam seriálù -----------*/
            //pokud nemam strankovani, zacnu nazacatku:)
            if ($_GET["str"] == "") {
                $_GET["str"] = "0";
            }
            //vypisu menu

            /*zobrazení objednávek konkretniho klienta*/

            //---------------------------nové rezervace---------------------------
            $nove_rezervace = new Rezervace_list("show_all", $_SESSION["rezervace_typ"], $_SESSION["rezervace_podtyp"], $_SESSION["rezervace_zeme"],
                $_SESSION["rezervace_destinace"], $_SESSION["rezervace_nazev_serialu"], $_SESSION["rezervace_datum_od"], $_SESSION["rezervace_datum_do"],
                $_SESSION["rezervace_datum_pobyt_od"], $_SESSION["rezervace_datum_pobyt_do"],
                $_SESSION["rezervace_jmeno_klienta"], $_SESSION["rezervace_prijmeni_klienta"], $_SESSION["rezervace_stav"],
                $_GET["str"], $_SESSION["rezervace_order_by"], $_SESSION["rezervace_ca_x_klient"], $_SESSION["rezervace_provize"], $_GET["id_serial"], $_GET["id_zajezd"], $_GET["id_klient"]
            );
            //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
            echo $nove_rezervace->get_error_message();
            //zobrazim filtry
            echo $nove_rezervace->show_filtr();
            ?>
            <h3>Objednávky</h3>
            <?
            //zobrazim hlavicku vypisu serialu
            echo $nove_rezervace->show_list_header();

            //vypis jednotlivych serialu
            while ($nove_rezervace->get_next_radek()) {
                echo $nove_rezervace->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($nove_rezervace->getZacatek(), $nove_rezervace->getPocetZajezdu(), $nove_rezervace->getPocetZaznamu());
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
}
?>
</body>
</html>