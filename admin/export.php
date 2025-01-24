<?
/**    \file
 * zeme.php  - administrace zemí a destinací
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_zeme = id zemì
 * @param $id_destinace = id destinace
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./config/config_export_sdovolena.inc.php"; //seznamy dokumentu
require_once "./classes/create_export.inc.php"; //seznamy dokumentu
require_once "./classes/dokument.inc.php"; //seznamy dokumentu
require_once "./classes/dokument_list.inc.php"; //seznamy dokumentu

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

    /*--------------------- zeme ---------------*/
    if ($_GET["typ"] == "export_list" or $_GET["typ"] == "dokument_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_dokument"] = $_POST["nazev_dokument"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["dokument_order_by"] = $_GET["ord_by"];
            }
            if ($_SESSION["dokument_order_by"] == "") {
                $_SESSION["dokument_order_by"] = "datum_down";
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument_list";
        }
        $_GET["typ"] = "export_list";


    } else if ($_GET["typ"] == "export") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            //format = "zakladni", "cestujeme", "invia", "sdovolena"
            if ($_POST["format"] == "vse") {
                $format = array("zakladni", "cestujeme", "invia", "sdovolena");

            } else {
                $format = array($_POST["format"]);
            }
            $typ = array($_POST["typ_serialu"]);

            foreach ($format as $f) {
                foreach ($typ as $t) {

                    create_export($f, $t);
                }
            }

        }

    }
    //if($_GET["typ"]==...
    $_GET["typ"] = "export_list";
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
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            //na zacatku zobrazim seznam
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "export_list";
            }

            /*----------------	seznam zrmí -----------
             *
             Typ seriálu: <select name="typ_serialu">
                                <option value="cele" >Všechny typy najednou</option>
            <?
                $dotaz="SELECT distinct `nazev_typ`,`nazev_typ_web`
                        FROM `typ_serial`
                        where `id_nadtyp`=0 order by `nazev_typ` ";

            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
            //$text.=mysqli_errno($GLOBALS["core"]->database->db_spojeni) . ": " . mysqli_error($GLOBALS["core"]->database->db_spojeni). "<br/>\n";
            while($zaznam = mysqli_Fetch_Array($data)){
                echo "<option value=\"".$zaznam["nazev_typ_web"]."\" >".$zaznam["nazev_typ"]."</option>";
            }
                ?>
                    </select><br/>
                    Formát exportu: <select name="format">
                                <option value="vse" >Všechny formáty</option>
                                <option value="zakladni" >Základní</option>
                                <option value="cestujeme" >Cestujeme</option>
                                <option value="invia" >Invia</option>
                                <option value="sdovolena" >Sdovolená</option>
                    </select>       <br/>
             *
             */
            if ($_GET["typ"] == "export_list") {
                ?>
                <form action="export.php?typ=export&pozadavek=create" method="post">

                    <input type="hidden" name="typ_serialu" value="cele"/>
                    <input type="hidden" name="format" value="vse"/>

                    <input type="submit" value="Vygenerovat XML exporty"/>
                </form>
                <?

                //seznam zemí a destinací -
                $list = new Dokument_list($zamestnanec->get_id(), "XML dokument ", $_GET["str"], $_SESSION["dokument_order_by"]);
                ?>
                <h3>Seznam existujících xml exportù</h3>
                <?
                //zobrazeni hlavicky seznamu
                echo $list->show_list_header();
                //zobrazeni seznamu
                //zobrazeni jednotlivych zaznamu
                while ($list->get_next_radek()) {
                    echo $list->show_list_item("tabulka_export");
                }
                ?>
                </table>
                <?


                /*----------------	nová Zeme -----------*/


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