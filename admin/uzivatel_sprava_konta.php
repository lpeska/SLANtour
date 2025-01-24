<?
/**      \file
 * uzivatel_sprava_konta.php  - zobrazení osobních informací a práv pøihlášeného uživatele
 *                                    - editace informací o sobì
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/zamestnanec_list.inc.php"; //seznamy serialu
require_once "./classes/zamestnanec.inc.php"; //detail seriálu

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
    /*---------------------serial---------------*/
    if ($_GET["typ"] == "zamestnanec") {
        if ($_GET["pozadavek"] == "update_self") {
            $dotaz = new Zamestnanec(
                "update_self", $zamestnanec->get_id(), $_GET["id_zamestnanec"], "", $_POST["stare_heslo"], $_POST["heslo1"], $_POST["heslo2"],
                $_POST["jmeno"], $_POST["prijmeni"], $_POST["email"], $_POST["telefon"]
            );
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
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
</head>
<body>

<?
if ($zamestnanec->get_correct_login()) {
//prihlaseni probehlo vporadku, muzu pokracovat
//zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

//zobrazeni aktualnich informaci - nove rezervace, pozadavky...
//na zacatku zobrazim informace o uzivateli a seznam dalsich zamestnancu
    if ($_GET["typ"] == "") {
        $_GET["typ"] = "zamestnanec";
        $_GET["pozadavek"] = "show";
    }
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            /*----------------	informace o zamestnanci + vypis ostatnich (je-li opravneni) -----------*/
            if ($_GET["typ"] == "zamestnanec" and $_GET["pozadavek"] == "show") {

                echo "<div class=\"submenu\">";
                echo "<a href=\"?typ=zamestnanec&amp;pozadavek=edit_self&amp;id_zamestnanec=" . $zamestnanec->get_id() . "\">upravit informace</a>
			        </div>";
                echo "<div class='submenu'>";
                echo $zamestnanec->show_info_about_user("tabulka_zamestnanci");
                echo "</div>";
                echo "<h3>Práva uživatele</h3>";
                echo $zamestnanec->show_prava("tabulka");

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "zamestnanec" and ($_GET["pozadavek"] == "edit_self" or $_GET["pozadavek"] == "update_self")) {
                echo "<div class=\"submenu\">";
                echo "<a href=\"?typ=zamestnanec&amp;pozadavek=show\"><< práva uživatele</a>
			        </div>";
                ?><h3>Editace uživatelského konta</h3><?
                $edit_zamestnanec = new Zamestnanec("edit_self", $zamestnanec->get_id(), $zamestnanec->get_id(), "", "", "", "",
                    $_POST["jmeno"], $_POST["prijmeni"], $_POST["email"], $_POST["telefon"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $edit_zamestnanec->show_form_self();

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