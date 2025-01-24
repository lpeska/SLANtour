<?
/**   \file
 * informace.php  - administrace dalších informací
 *                - pridavani fotek k jednotlivým informacím
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_foto = id fotky
 * @param $id_informace = id informace
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/zeme_list.inc.php"; //seznamy serialu
require_once "./classes/foto_list.inc.php"; //seznamy serialu
require_once "./classes/informace_list.inc.php"; //seznam serialu
require_once "./classes/informace_foto.inc.php"; //seznam fotografií serialu
require_once "./classes/informace.inc.php"; //detail seriálu

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
    /*---------------------serial_list ---------------*/
    if ($_GET["typ"] == "informace_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
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
                $_SESSION["typ_informace"] = $_POST["typ_informace"];
                $_SESSION["nazev_informace"] = $_POST["nazev_informace"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["informace_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace_list";
        }

        /*--------------------- informace ---------------*/
    } else if ($_GET["typ"] == "informace") {

        //rozdeleni pole zeme:destinace na zemi a destinaci:))
        if ($_POST["zeme-destinace"] != "") {
            //vstup je ve tvaru typ:podtyp
            $zeme_array = explode(":", $_POST["zeme-destinace"]);
            $id_zeme = $zeme_array[0];
            $id_destinace = $zeme_array[1];
        } else {
            $id_zeme = "";
            $id_destinace = "";
        }


        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Informace("create", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["popisek"], $_POST["popis"], $_POST["popis_lazni"], $_POST["popis_strediska"],
                $id_zeme, $id_destinace, $_POST["typ_informace"], $_POST["detailni_typ"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Informace("update", $zamestnanec->get_id(), $_GET["id_informace"], $_POST["nazev"], $_POST["popisek"], $_POST["popis"], $_POST["popis_lazni"], $_POST["popis_strediska"],
                $id_zeme, $id_destinace, $_POST["typ_informace"], $_POST["detailni_typ"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Informace("delete", $zamestnanec->get_id(), $_GET["id_informace"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

        /*--------------------- foto ---------------*/
    } else if ($_GET["typ"] == "foto_list") {
        if ($_GET["pozadavek"] == "change_filter") {
            //je-li to treba, zaregistrujeme sessions
            //INFO: deprecated - nemelo by byt treba
//				if(!isset($_SESSION["foto_order_by"])){
//					session_register("zeme"); 
//					session_register("destinace"); 
//					session_register("nazev_foto");
//					session_register("foto_order_by");
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

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["foto_order_by"] = $_GET["ord_by"];
            }

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_informace=" . $_GET["id_informace"] . "";
        }
    } else if ($_GET["typ"] == "foto") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Foto_informace("create", $zamestnanec->get_id(), $_GET["id_informace"], $_GET["id_foto"], $_GET["zakladni_foto"], $_GET["zakladni_pro_typ"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_informace=" . $_GET["id_informace"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Foto_informace("update", $zamestnanec->get_id(), $_GET["id_informace"], $_GET["id_foto"], $_GET["zakladni_foto"], $_GET["zakladni_pro_typ"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_informace=" . $_GET["id_informace"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Foto_informace("delete", $zamestnanec->get_id(), $_GET["id_informace"], $_GET["id_foto"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_informace=" . $_GET["id_informace"] . "";
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
                $_GET["typ"] = "informace_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "informace_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci serial_list
                $informace_list = new Informace_list($_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["typ_informace"], $_SESSION["nazev_informace"], $_GET["str"], $_SESSION["informace_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $informace_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=informace&amp;pozadavek=new">vytvoøit novou informaci</a>
                </div>
                <?
                //zobrazim filtry
                echo $informace_list->show_filtr();
                ?>
                <h3>Seznam informací</h3>
                <?
                //hlavièka tabulky
                echo $informace_list->show_list_header();
                //vypis jednotlivych serialu
                while ($informace_list->get_next_radek()) {
                    echo $informace_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($informace_list->getZacatek(), $informace_list->getPocetZajezdu(), $informace_list->getPocetZaznamu());

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "informace" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=informace_list">&lt;&lt; seznam informací</a>
                </div>

                <script>
                    function otevrit(url) {
                        win = window.open('' + url + '', '_blank', 'height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
                    }

                </script>


                <?
                $informace = new Informace("new", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["popisek"], $_POST["popis"], $_POST["popis_lazni"], $_POST["popis_strediska"],
                    $id_zeme, $id_destinace, $_POST["typ_informace"], $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit novou informaci</h3><?
                echo $informace->show_form();

            } else if ($_GET["typ"] == "informace" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=informace_list">&lt;&lt; seznam informací</a>
                    <a href="?typ=informace&amp;pozadavek=new">vytvoøit novou informaci</a>
                </div>
            <?
            $informace = new Informace("edit", $zamestnanec->get_id(), $_GET["id_informace"], $_POST["nazev"], $_POST["popisek"], $_POST["popis"], $_POST["popis_lazni"], $_POST["popis_strediska"], $id_zeme, $id_destinace, $_POST["typ_informace"], $_GET["pozadavek"]);
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
            //vypisu menu
            echo $informace->show_submenu();
            ?>
                <h3>Editace informace</h3><?
            ?>
                <script>
                    function otevrit(url) {
                        win = window.open('' + url + '', '_blank', 'height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
                    }
                </script>
                <?
                echo $informace->show_form();

                /*----------------	editace  fotografií -----------*/
            } else if ($_GET["typ"] == "foto") {
                /*
                    u fotografii zobrazuju aktuálnì pøipojené fotografie
                    a seznam fotografií, které lze pøipojit (stránkovaný s filtry výbìru)
                */
                //seznam fotografii pripojenych k serialu
                $informace = new Informace("show", $zamestnanec->get_id(), $_GET["id_informace"]);
                $current_foto = new Foto_informace("show", $zamestnanec->get_id(), $_GET["id_informace"]);
                ?>
                <div class="submenu">
                    <a href="?typ=informace_list">&lt;&lt; seznam informací</a>
                    <a href="?typ=informace&amp;pozadavek=new">vytvoøit novou informaci</a>
                </div>

                <?
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                echo $informace->show_submenu();
                ?>

                <h3>Fotografie pøiøazené k informaci</h3>
                <?
                echo $current_foto->show_list_header();
                while ($current_foto->get_next_radek()) {
                    echo $current_foto->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                if ($_GET["str"] == "") {
                    $_GET["str"] = 0;
                }
                if ($_SESSION["zeme"] == "" and $_SESSION["destinace"] == "") {
                    //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
                    $foto_list = new Foto_list($zamestnanec->get_id(), $informace->get_id_zeme(), "", $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);
                } else {
                    //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
                    $foto_list = new Foto_list($zamestnanec->get_id(), $_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);
                }

                //zobrazeni filtru pro vypis fotek
                echo $foto_list->show_filtr();
                ?>
                <h3>Seznam fotografií</h3>
                <?
                echo $foto_list->show_list_header();

                //zobrazeni jednotlivych zaznamu
                while ($foto_list->get_next_radek()) {
                    echo $foto_list->show_list_item("tabulka_informace");
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