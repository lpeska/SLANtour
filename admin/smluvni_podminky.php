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

require_once "./classes/smluvni_podminky.inc.php"; //seznamy dokumentu
require_once "./classes/smluvni_podminky_list.inc.php"; //seznamy dokumentu

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

/* *****************************************************************************
 * CONTROLLER ******************************************************************
 * ****************************************************************************/

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    if ($_GET["typ"] == "smluvni_podminky_nazev") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new SmluvniPodminky("create_nazev", "", $_POST["nazev"], "", "", "", "", $_POST["dokument_id"]);
            exit();
        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new SmluvniPodminky("update_nazev", "", $_POST["nazev"], "", "", "", "", $_POST["dokument_id"], $_GET["id_smluvni_podminky_nazev"]);
            exit();
        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new SmluvniPodminky("delete_nazev", "", "", "", "", "", "", "", $_GET["id_smluvni_podminky_nazev"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							            
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=smluvni_podminky_nazev_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    } else if ($_GET["typ"] == "smluvni_podminky") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new SmluvniPodminky("create", "", "", $_POST["castka"], $_POST["procento"], $_POST["prodleva"], $_POST["typ"], "", $_GET["id_smluvni_podminky_nazev"]);
            exit();
        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new SmluvniPodminky("update", $_GET["id_smluvni_podminky"], $_POST["nazev"], $_POST["castka"], $_POST["procento"], $_POST["prodleva"], $_POST["typ"], "");
            exit();
        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new SmluvniPodminky("delete", $_GET["id_smluvni_podminky"], "", "", "", "", "", "");
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							            
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=smluvni_podminky_list&smluvni_podminky_nazev_id=" . $_GET["smluvni_podminky_nazev_id"];
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

/* *****************************************************************************
 * VIEW ************************************************************************
 * ****************************************************************************/

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
    <script type="text/javascript" charset="windows-1250" src="js/smluvni_podminky.js"></script>
    <script type="text/javascript" src="js/jquery-min.js"></script>
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

    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            //defaultne zobrazim seznam zaznamu
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "smluvni_podminky_nazev_list";
            }

            /* ----------------	seznam ----------- */
            if ($_GET["typ"] == "smluvni_podminky_nazev_list") {
                //seznam zaznamu
                $smluv_podm = new SmluvniPodminky_list("show_nazev_list");

                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $smluv_podm->get_error_message();

                echo "<h3>Seznam záznamù</h3>";
                //zobrazeni hlavicky seznamu
                echo $smluv_podm->show_list_header();

                //zobrazeni jednotlivych zaznamu
                echo $smluv_podm->show_list();
                echo "</table>";
            } else if ($_GET["typ"] == "smluvni_podminky_list") {
                //seznam zaznamu
                $smluv_podm = new SmluvniPodminky_list($_GET["smluvni_podminky_nazev_id"], "show_list");
                $storno_smluv_podm = new SmluvniPodminky_list($_GET["smluvni_podminky_nazev_id"], "show_list_storno");

                //err
                echo $smluv_podm->get_error_message();
                echo $storno_smluv_podm->get_error_message();

                //menu
                echo "  <div class='submenu'>" . $smluv_podm->get_nazev() . ": <a href='smluvni_podminky.php'>zpìt na seznam</a></div>";
                echo "<h3>Seznam záznamù - platby</h3>";
                echo $smluv_podm->show_list_header("show_list");
                echo $smluv_podm->show_list("show_list", false);
                echo "</table><br/>";

                //STORNO
                echo "<h3>Seznam záznamù - storno</h3>";
                echo $storno_smluv_podm->show_list_header("show_list_storno");
                echo $storno_smluv_podm->show_list("show_list_storno");
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