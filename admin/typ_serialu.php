<?
/**    \file
 * typ_serialu.php  - administrace typu a podtypu seriálù
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_typ = id typu
 * @param $id_podtyp = id podtypu
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/typ_serialu_list.inc.php"; //seznamy serialu
require_once "./classes/typ_serialu.inc.php"; //detail seriálu
require_once "./classes/zeme_list.inc.php"; //seznamy serialu
require_once "./classes/foto_list.inc.php"; //seznamy serialu

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
    /*--------------------- informace ---------------*/
    if ($_GET["typ"] == "typ_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy zeme_list
            if ($_GET["pole"] == "ord_by") {
                $_SESSION["typ_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
        }
        /*--------------------- foto ---------------*/
    } else if ($_GET["typ"] == "foto_list") {
        if ($_GET["pozadavek"] == "change_filter") {
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

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["foto_order_by"] = $_GET["ord_by"];
            }

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_typ=" . $_GET["id_typ"] . "";
        }        
    } else if ($_GET["typ"] == "typ_serialu") {

        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Typ_serialu("create", $zamestnanec->get_id(), "", "", $_POST["nazev"], $_POST["nazev_web"]);

            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Typ_serialu("update", $zamestnanec->get_id(), $_GET["id_typ"], "", $_POST["nazev"], $_POST["nazev_web"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            
        } else if ($_GET["pozadavek"] == "update_foto") {
            $dotaz = new Typ_serialu("update_foto", $zamestnanec->get_id(), $_GET["id_typ"], "", "", "");
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Typ_serialu("delete", $zamestnanec->get_id(), $_GET["id_typ"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "create_podtyp") {
            //insert do tabulky seriálù
            $dotaz = new Typ_serialu("create_podtyp", $zamestnanec->get_id(), $_GET["id_typ"], "", $_POST["nazev"], $_POST["nazev_web"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update_podtyp") {
            $dotaz = new Typ_serialu("update_podtyp", $zamestnanec->get_id(), $_GET["id_typ"], $_GET["id_podtyp"], $_POST["nazev"], $_POST["nazev_web"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

            //echo $dotaz->get_error_message();
        } else if ($_GET["pozadavek"] == "delete_podtyp") {
            $dotaz = new Typ_serialu("delete_podtyp", $zamestnanec->get_id(), $_GET["id_typ"], $_GET["id_podtyp"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=typ_list";
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
//zpracovani hlasky k vypsani (jsme za headerem pro presmerovani, takze ji v sessions smazeme a zobrazime ve vypisu)	
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

            /*
                nejprve zjistim v jake objekty budu obsluhovat
                    -(serial, zajezd, cena, cena_zajezdu, foto, dokument, informace)
            */
            //na zacatku zobrazim seznam serialu
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "typ_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "typ_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //seznam zemí a destinací -
                $typ_list = new Typ_list($zamestnanec->get_id(), $_SESSION["typ_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $typ_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=typ_serialu&amp;pozadavek=new">vytvoøit nový typ seriálu</a>
                </div>
                <h3>Seznam typù seriálu</h3>
                <?
                //zobrazeni hlavicky seznamu
                echo $typ_list->show_list_header();
                //zobrazeni seznamu
                echo $typ_list->show_list("tabulka_typ");
                ?>
                </table>
                <?
                /*----------------	nový seriál -----------*/
                
                
            } else if ($_GET["typ"] == "typ_serialu" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
                ?>
                <div class="submenu">
                    <a href="?typ=typ_list">&lt;&lt; seznam typù/podtypù</a>
                </div>
                <?
                $typ = new Typ_serialu("new", $zamestnanec->get_id(), "", "", $_POST["nazev"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit nový typ seriálu</h3><?
                echo $typ->show_form();
            } else if ($_GET["typ"] == "typ_serialu" and ($_GET["pozadavek"] == "new_podtyp" or $_GET["pozadavek"] == "create_podtyp")) {

                ?>
                <div class="submenu">
                    <a href="?typ=typ_list">&lt;&lt; seznam typù/podtypù</a>
                </div>
                <?
                $typ = new Typ_serialu("new_podtyp", $zamestnanec->get_id(), $_GET["id_typ"], "", $_POST["nazev"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit nový podtyp seriálu</h3><?
                echo $typ->show_form();

            } else if ($_GET["typ"] == "typ_serialu" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=typ_list">&lt;&lt; seznam typù/podtypù</a>
                </div>

                <h3>Editace typu seriálu</h3><?
                $typ = new Typ_serialu("edit", $zamestnanec->get_id(), $_GET["id_typ"], "", $_POST["nazev"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $typ->show_form();

            } else if ($_GET["typ"] == "typ_serialu" and ($_GET["pozadavek"] == "edit_podtyp" or $_GET["pozadavek"] == "update_podtyp")) {
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=typ_list">&lt;&lt; seznam typù/podtypù</a>
                </div>

                <h3>Editace podtypu seriálu</h3><?
                $typ = new Typ_serialu("edit_podtyp", $zamestnanec->get_id(), $_GET["id_typ"], $_GET["id_podtyp"], $_POST["nazev"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $typ->show_form();

            } else if ($_GET["typ"] == "foto") {
                $typ = new Typ_serialu("edit", $zamestnanec->get_id(), $_GET["id_typ"], "", $_POST["nazev"], $_GET["pozadavek"]);
                
                ?>
                <div class="submenu">
                    <a href="?typ=typ_list">&lt;&lt; seznam typù/podtypù</a>
                </div>
              

                <h3>Fotografie pøiøazená k typu</h3>
                <?
                echo $typ->show_foto();


                $foto_list = new Foto_list($zamestnanec->get_id(), $_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);                
                //zobrazeni filtru pro vypis fotek
                echo $foto_list->show_filtr();
                ?>
                <h3>Seznam fotografií</h3>
                <?
                echo $foto_list->show_list_header();

                //zobrazeni jednotlivych zaznamu
                while ($foto_list->get_next_radek()) {
                    echo $foto_list->show_list_item("tabulka_typ");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($foto_list->getZacatek(), $foto_list->getPocetZajezdu(), $foto_list->getPocetZaznamu());
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