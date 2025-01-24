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

require_once "./classes/organizace_list.inc.php"; //seznamy klientù
require_once "./classes/organizace.inc.php"; //detail seriálu

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
    if ($_GET["typ"] == "organizace_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy klient_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "jmeno_prijmeni_datum") {
                $_SESSION["organizace_nazev"] = $_POST["organizace_nazev"];
                $_SESSION["organizace_ico"] = $_POST["organizace_ico"];
                $_SESSION["organizace_typ"] = $_POST["organizace_typ"];
                $_SESSION["organizace_mesto"] = $_POST["organizace_mesto"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["organizace_order_by"] = $_GET["organizace_order_by"];
            }
            $_GET["id_objednavka"] ? ($objednavka = "&id_objednavka=" . $_GET["id_objednavka"] . "") : ($objednavka = "");
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=organizace_list&moznosti_editace=" . $_GET["moznosti_editace"] . $objednavka . "";
        }

        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "organizace") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Organizace("create", $zamestnanec->get_id());
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME']."?typ=organizace_list&moznosti_editace=".$_GET["moznosti_editace"]."";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Organizace("update", $zamestnanec->get_id(), $_GET["id_organizace"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME']."?typ=organizace_list&moznosti_editace=".$_GET["moznosti_editace"]."";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Organizace("delete", $zamestnanec->get_id(), $_GET["id_organizace"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=organizace_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "create_from_agentury") {
            $query = "select * from user_klient where uzivatel_je_ca = 1";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            while ($row = mysqli_fetch_array($data)) {

                $_POST["koeficient_prodejce"] = 1;
                $_POST["uzivatelske_jmeno"] = $row["uzivatelske_jmeno"];
                $_POST["heslo_sha1"] = $row["heslo_sha1"];
                $_POST["salt"] = $row["salt"];
                $_POST["last_logon"] = $row["last_logon"];

                $_POST["nazev"] = $row["jmeno"];
                $_POST["ico"] = $row["ico"];
                $_POST["role"] = 1;

                $_POST["kontakt_typ_1"] = 0;
                $_POST["email_1"] = $row["email"];
                $_POST["telefon_1"] = $row["telefon"];
                $_POST["kontakt_poznamka_1"] = $row["prijmeni"];

                $_POST["adresa_typ_1"] = 1;
                $_POST["stat_1"] = "Èeská republika";
                $_POST["mesto_1"] = $row["mesto"];
                $_POST["ulice_1"] = $row["ulice"];
                $_POST["psc_1"] = $row["psc"];
                $dotaz = new Organizace("create", $zamestnanec->get_id());
            }


            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=organizace_list";
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
    <script type='text/javascript' src='http://maps.google.com/maps/api/js?sensor=false'></script>
    <script type='text/javascript' src='./js/jquery-min.js'></script>
    <script type='text/javascript' src='../geocoding/js/lib/jquery-ui-1.9.1.custom.min.js'></script>
    <script type='text/javascript' src='../geocoding/js/lib/infobox.js'></script>
    <script type='text/javascript' src='../geocoding/js/lib/styledmarker.js'></script>
    <script type='text/javascript' src='../geocoding/js/GoogleMap.js'></script>
    <script type='text/javascript' src='./js/organizace.js'></script>
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
                $_GET["typ"] = "organizace_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "organizace_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }
                if ($_GET["moznosti_editace"] == "add_orgaizace_to_objednavka") {
                    $show = "show_agentury";
                } else {
                    $show = "show_all";
                }

                //vytvorime instanci organizace_list
                $organizace_list = new Organizace_list($show, $_SESSION["organizace_nazev"], $_SESSION["organizace_ico"], $_SESSION["organizace_typ"], $_GET["str"], $_SESSION["organizace_order_by"], $_GET["moznosti_editace"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $organizace_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <? echo "<a href=\"?typ=organizace&amp;pozadavek=new&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">vytvoøit novou organizaci</a>" ?>
                </div>
                <?
                //zobrazim filtry
                echo $organizace_list->show_filtr();
                //zobrazim nadpis seznamu
                echo $organizace_list->show_header();
                //zobrazim hlavicku seznamu
                echo $organizace_list->show_list_header();

                //vypis jednotlivych serialu
                while ($organizace_list->get_next_radek()) {
                    echo $organizace_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($organizace_list->getZacatek(), $organizace_list->getPocetZajezdu(), $organizace_list->getPocetZaznamu());

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "organizace" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=organizace_list">&lt;&lt; seznam organizací</a>
                </div>
                <?
                $organizace = new Organizace("new", $zamestnanec->get_id());
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit novou organizaci</h3><?
                echo $organizace->show_form();

            } else if ($_GET["typ"] == "organizace" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //nejaky organizace uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=organizace_list">&lt;&lt; seznam organizací</a>
                    <a href="?typ=organizace&amp;pozadavek=new">vytvoøit novou organizaci</a>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $organizace = new Organizace("edit", $zamestnanec->get_id(), $_GET["id_organizace"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Editace organizace</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $organizace->show_edit_form();
            } else if ($_GET["typ"] == "organizace" and $_GET["pozadavek"] == "objednavky") {

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=organizace_list">&lt;&lt; seznam organizací</a>
                    <a href="?typ=organizace&amp;pozadavek=new">vytvoøit novou organizaci</a>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $organizace = new Organizace("edit", $zamestnanec->get_id(), $_GET["id_organizace"]);

                ?><h3>Objednávky související s organizací</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $organizace->show_objednavky();
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