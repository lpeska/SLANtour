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

require_once "./classes/zeme_list.inc.php"; //seznamy fotek
require_once "./classes/foto.inc.php"; //upravy jednotlivych fotografii
require_once "./classes/foto_list.inc.php"; //seznamy fotek

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";


//pripojeni k databazi
//$database = new Database();

//spusteni prace se sessions
session_start();
/*	
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
    if ($_GET["typ"] == "foto_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //je-li to treba, zaregistrujeme sessions
            //INFO: deprecated - nemelo by byt treba
//				if(!isset($_SESSION["foto_order_by"])){
//					session_register("zeme"); 
//					session_register("destinace"); 
//					session_register("nazev_foto");
//					session_register("foto_order_by");
//                                      session_register("foto_nepouzite");
//				}
            //rozdeleni pole zeme:destinace na id_zeme a id_destinace
            if ($_POST["zeme-destinace"] != "") {
                //vstup je ve tvaru zeme:destinace
                $typ_array = explode(":", $_POST["zeme-destinace"]);
                $id_zeme = $typ_array[0];
                $id_destinace = $typ_array[1];
            } else {
                $id_zeme = "";
                $id_destinace = "";
            }
            //kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "zeme-destinace-nazev") {
                $_SESSION["zeme"] = $id_zeme;
                $_SESSION["destinace"] = $id_destinace;
                $_SESSION["nazev_foto"] = $_POST["nazev_foto"];
                $_SESSION["foto_nepouzite"] = intval($_POST["foto_nepouzite"]);
                //echo $_SESSION["zeme"].$_SESSION["destinace"].$_SESSION["nazev_foto"].$_SESSION["foto_nepouzite"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["foto_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto_list";
        }

        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "foto") {
        //rozdeleni pole zeme-destinace na id_zeme a id_destinace
        if ($_POST["zeme-destinace"] != "") {
            //vstup je ve tvaru zeme:destinace
            $typ_array = explode(":", $_POST["zeme-destinace"]);
            $id_zeme = $typ_array[0];
            $id_destinace = $typ_array[1];
        } else {
            $id_zeme = "";
            $id_destinace = "";
        }


        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Foto("create", $zamestnanec->get_id(), "", $id_zeme, $id_destinace, $_POST["nazev_foto"], $_POST["popisek_foto"], $_POST["foto"]);
            $_SESSION["last_created_zeme"]  =  $id_zeme;
            $_SESSION["last_created_destinace"]  =  $id_destinace;

            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Foto("update", $zamestnanec->get_id(), $_GET["id_foto"], $id_zeme, $id_destinace, $_POST["nazev_foto"], $_POST["popisek_foto"], $_POST["foto"]);
            $_SESSION["last_created_zeme"]  =  $id_zeme;
            $_SESSION["last_created_destinace"]  =  $id_destinace;            
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Foto("delete", $zamestnanec->get_id(), $_GET["id_foto"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "mass_del") {
            $dotaz = new Foto("mass_del", $zamestnanec->get_id(),-1);
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto_list";
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
</head>
<body>
<?
if ($zamestnanec->get_correct_login()) {
    //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
    $foto_list = new Foto_list($zamestnanec->get_id(), $_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);

    //prihlaseni probehlo vporadku, muzu pokracovat
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

    //zobrazeni aktualnich informaci - nove rezervace, pozadavky...
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?
            //vypisu pripadne hlasky o uspechu operaci (create/update...)
            echo $hlaska_k_vypsani;
            //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
            echo $foto_list->get_error_message();

            //na zacatku zobrazim seznam dokumentù
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "foto_list";
            }

            /*----------------	seznam dokumentù -----------*/
            if ($_GET["typ"] == "foto_list") {
                if ($_GET["str"] == "") {
                    $_GET["str"] = 0;
                }
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=foto&amp;pozadavek=new">vytvoøit novou fotku</a>
                </div>
                <?

                //zobrazeni filtru pro vypis fotek
                echo $foto_list->show_filtr();
                ?>
                <h3>Seznam fotografií</h3>
                <?
                echo $foto_list->show_list_header();


                //zobrazeni jednotlivych zaznamu
                while ($foto_list->get_next_radek()) {
                    echo $foto_list->show_list_item("tabulka_foto");
                }
                ?>
                </table>
                <script type='text/javascript' src='js/massDel.js'></script>
                <form method="post" action="?typ=foto&pozadavek=mass_del">
                    <input type="hidden" id="ids-massdel" name="massdel_ids"/>
                    <input type="submit" value="Hromadnì smazat" class="action-delete" id="button-massdel" onclick="return !isEmpty();" disabled="disabled"/>
                </form>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($foto_list->getZacatek(), $foto_list->getPocetZajezdu(), $foto_list->getPocetZaznamu());
                /*----------------	nový dokument -----------*/
            } else if ($_GET["typ"] == "foto" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=foto_list">&lt;&lt; seznam fotografií</a>
                </div>
                <?
                $foto = new Foto("new", $zamestnanec->get_id(), "", $id_zeme, $id_destinace, $_POST["nazev_foto"], $_POST["popisek_foto"], $_POST["foto"], $_GET["pozadavek"]);

                ?><h3>Vytvoøit novou fotku</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $foto->show_form();

                /*----------------	editace dokumentu -----------*/
            } else if ($_GET["typ"] == "foto" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                ?>
                <div class="submenu">
                    <a href="?typ=foto_list">&lt;&lt; seznam fotografií</a>
                    <a href="?typ=foto&amp;pozadavek=new">vytvoøit novou fotku</a>
                </div>
                <?
                $foto = new Foto("edit", $zamestnanec->get_id(), $_GET["id_foto"], $id_zeme, $id_destinace, $_POST["nazev_foto"], $_POST["popisek_foto"], $_POST["foto"], $_GET["pozadavek"]);

                ?><h3>Editace fotografií</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $foto->show_form();

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