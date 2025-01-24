<?
/**    \file
 * uzivatele.php  - administrace u�ivatel� syst�mu (pracovn�k� CK)
 *                    - zm�na osobn�ch �daj� a pr�v u�ivatele
 *                    - vytv��en� nov�ch u�ivatel�
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_user = id u�ivatele
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/zamestnanec_list.inc.php"; //seznamy serialu
require_once "./classes/zamestnanec.inc.php"; //detail seri�lu

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
    if ($_GET["typ"] == "zamestnanec_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (username, jmeno, prijmeni) nebo odkazem (order by)
            if ($_GET["pole"] == "username-jmeno-prijmeni") {
                $_SESSION["zamestnanec_username"] = $_POST["zamestnanec_username"];
                $_SESSION["zamestnanec_jmeno"] = $_POST["zamestnanec_jmeno"];
                $_SESSION["zamestnanec_prijmeni"] = $_POST["zamestnanec_prijmeni"];


            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["zamestnanec_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zamestnanec_list";
        }
        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "zamestnanec") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seri�l�
            $dotaz = new Zamestnanec(
                "create", $zamestnanec->get_id(), "", $_POST["uzivatelske_jmeno"], "", $_POST["heslo1"], $_POST["heslo2"],
                $_POST["jmeno"], $_POST["prijmeni"], $_POST["email"], $_POST["telefon"]
            );
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zamestnanec_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Zamestnanec(
                "update", $zamestnanec->get_id(), $_GET["id_zamestnanec"], "", "", $_POST["heslo1"], "",
                $_POST["jmeno"], $_POST["prijmeni"], $_POST["email"], $_POST["telefon"]
            );
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zamestnanec_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete" || $_GET["pozadavek"] == "delete_all") {
            $dotaz = new Zamestnanec($_GET["pozadavek"], $zamestnanec->get_id(), $_GET["id_zamestnanec"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zamestnanec_list";
            if (!$dotaz->get_error_message()) {
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
    echo "<title>" . $core->show_nazev_modulu() . " | Administrace syst�mu RSCK</title>";
    ?>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/>
    <meta name="copyright" content="&copy; Slantour"/>
    <meta http-equiv="pragma" content="no-cache"/>
    <meta name="robots" content="noindex,noFOLLOW"/>
    <script type="text/javascript" src="./js/jquery-min.js"></script>
    <script type="text/javascript" src="./js/uzivatele.js"></script>
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
        $_GET["typ"] = "zamestnanec_list";
    }

    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            /*----------------	informace o zamestnanci + vypis ostatnich (je-li opravneni) -----------*/
            if ($_GET["typ"] == "zamestnanec_list") {
                //vytvorime instanci serial_list
                $zamestnanec_list = new Zamestnanec_list($zamestnanec->get_id(), $_SESSION["zamestnanec_username"], $_SESSION["zamestnanec_jmeno"],
                    $_SESSION["zamestnanec_prijmeni"], $_SESSION["zamestnanec_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $zamestnanec_list->get_error_message();
                ?>

                <div class="submenu">
                    <a href="?typ=zamestnanec&amp;pozadavek=new">vytvo�it nov�ho u�ivatele</a>
                </div>
                <h3>Seznam u�ivatel�</h3>

                <?
                //zobrazim filtry
                echo $zamestnanec_list->show_filtr();
                //hlavi�ka tabulky
                echo $zamestnanec_list->show_list_header();

                //vypis jednotlivych zam�stnanc�
                while ($zamestnanec_list->get_next_radek()) {
                    echo $zamestnanec_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?

                /*----------------	nov� seri�l -----------*/
            } else if ($_GET["typ"] == "zamestnanec" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=zamestnanec_list">&lt;&lt; seznam u�ivatel�</a>
                </div>
                <?
                $edit_zamestnanec = new Zamestnanec(
                    "new", $zamestnanec->get_id(), "", $_POST["uzivatelske_jmeno"], "", "", "",
                    $_POST["jmeno"], $_POST["prijmeni"], $_POST["email"], $_POST["telefon"], $_GET["pozadavek"]
                );
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvo�it nov�ho u�ivatele</h3><?
                echo $edit_zamestnanec->show_form();

            } else if ($_GET["typ"] == "zamestnanec" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //vypisu menu
                ?>

                <div class="submenu">
                    <a href="?typ=zamestnanec_list">&lt;&lt; seznam u�ivatel�</a>
                    <a href="?typ=zamestnanec&amp;pozadavek=new">vytvo�it nov�ho u�ivatele</a>
                </div>
                <h3>Editace u�ivatele</h3>
                <?
                $edit_zamestnanec = new Zamestnanec("edit", $zamestnanec->get_id(), $_GET["id_zamestnanec"], $_POST["uzivatelske_jmeno"], "", "", "",
                    $_POST["jmeno"], $_POST["prijmeni"], $_POST["email"], $_POST["telefon"], $_GET["pozadavek"]
                );
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $edit_zamestnanec->show_form();

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