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
require_once "./classes/dokument_list.inc.php"; //seznamy serialu
require_once "./classes/aktuality_list.inc.php"; //seznam serialu
require_once "./classes/aktuality_foto.inc.php"; //seznam fotografií serialu
require_once "./classes/aktuality_dokument.inc.php"; //seznam fotografií serialu
require_once "./classes/aktuality.inc.php"; //detail seriálu

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
    if ($_GET["typ"] == "aktuality_list") {
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
                $_SESSION["nazev_aktuality"] = $_POST["nazev_aktuality"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["aktuality_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=aktuality_list";
        }

        /*--------------------- informace ---------------*/
    } else if ($_GET["typ"] == "aktuality") {

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
            $dotaz = new Aktuality("create", $zamestnanec->get_id(), "", $_POST["nadpis"], $_POST["popisek"], $_POST["datum"], $_POST["top_nabidka"], $_POST["weby"], $_POST["zobrazit"], $_POST["odkazy"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=aktuality_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Aktuality("update", $zamestnanec->get_id(), $_GET["id_aktuality"], $_POST["nadpis"], $_POST["popisek"], $_POST["datum"], $_POST["top_nabidka"], $_POST["weby"], $_POST["zobrazit"], $_POST["odkazy"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=aktuality_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Aktuality("delete", $zamestnanec->get_id(), $_GET["id_aktuality"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=aktuality_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

        /*--------------------- dokumenty ---------------*/
    } else if ($_GET["typ"] == "dokument_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru tøidy dokument_list
            //filtry menime bud formularem (nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_dokument"] = $_POST["nazev_dokument"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["dokument_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument&id_aktuality=" . $_GET["id_aktuality"] . "";
        }
    } else if ($_GET["typ"] == "dokument") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Dokument_aktuality("create", $zamestnanec->get_id(), $_GET["id_aktuality"], $_GET["id_dokument"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument&id_aktuality=" . $_GET["id_aktuality"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Dokument_aktuality("delete", $zamestnanec->get_id(), $_GET["id_aktuality"], $_GET["id_dokument"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument&id_aktuality=" . $_GET["id_aktuality"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku
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

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["foto_order_by"] = $_GET["ord_by"];
            }

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_aktuality=" . $_GET["id_aktuality"] . "";
        }
    } else if ($_GET["typ"] == "foto") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Foto_aktuality("create", $zamestnanec->get_id(), $_GET["id_aktuality"], $_GET["id_foto"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_aktuality=" . $_GET["id_aktuality"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Foto_aktuality("update", $zamestnanec->get_id(), $_GET["id_aktuality"], $_GET["id_foto"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_aktuality=" . $_GET["id_aktuality"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Foto_aktuality("delete", $zamestnanec->get_id(), $_GET["id_aktuality"], $_GET["id_foto"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_aktuality=" . $_GET["id_aktuality"] . "";
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
                $_GET["typ"] = "aktuality_list";
            }

            /*----------------	seznam seriálù -----------*/
            if ($_GET["typ"] == "aktuality_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci serial_list
                $aktuality_list = new Aktuality_list($_SESSION["nazev_aktuality"], $_GET["str"], $_SESSION["aktuality_order_by"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $aktuality_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=aktuality&amp;pozadavek=new">vytvoøit novou aktualitu</a>
                </div>
                <?
                //zobrazim filtry
                echo $aktuality_list->show_filtr();
                ?>
                <h3>Seznam aktualit</h3>
                <?
                //hlavièka tabulky
                echo $aktuality_list->show_list_header();
                //vypis jednotlivych serialu
                while ($aktuality_list->get_next_radek()) {
                    echo $aktuality_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($aktuality_list->getZacatek(), $aktuality_list->getPocetZajezdu(), $aktuality_list->getPocetZaznamu());

                /*----------------	nový seriál -----------*/
            } else if ($_GET["typ"] == "aktuality" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=aktuality_list">&lt;&lt; seznam aktualit</a>
                </div>

                <script>
                    function otevrit(url) {
                        win = window.open('' + url + '', '_blank', 'height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
                    }

                </script>

                <?
                $informace = new Aktuality("new", $zamestnanec->get_id(), "", $_POST["nadpis"], $_POST["popisek"], $_POST["datum"], $_POST["weby"], $_POST["zobrazit"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit novou aktualitu</h3><?
                echo $informace->show_form();

            } else if ($_GET["typ"] == "aktuality" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=aktuality_list">&lt;&lt; seznam aktualit</a>
                    <a href="?typ=aktuality&amp;pozadavek=new">vytvoøit novou aktualitu</a>
                </div>
                <?
                $aktuality = new Aktuality("edit", $zamestnanec->get_id(), $_GET["id_aktuality"], $_POST["nadpis"], $_POST["popisek"], $_POST["datum"], $_POST["top_nabidka"], $_POST["weby"], $_POST["zobrazit"], $_POST["odkazy"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $aktuality->show_submenu();
                ?>
                <h3>Editace aktuality</h3><?
                ?>
                <script>
                    function otevrit(url) {
                        win = window.open('' + url + '', '_blank', 'height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
                    }

                </script>
                <div class="copypaste" style="position:absolute;top:220px;left:650px;font-weight:bold;font-size:1.4em;color:red;">
                    <a href="copypaste.html" title="zobrazí v novém oknì pole pro kopírování HTML znaèek" target="_blank" onclick="otevrit('copypaste.html');return false;" style="color:red;">ZOBRAZIT POLE PRO
                        KOPÍROVÁNÍ</a>
                </div>
                <?


                echo $aktuality->show_form();

                /*----------------	editace  dokumentù -----------*/
            } else if ($_GET["typ"] == "dokument") {
                /*
                u dokumentu zobrazuju aktuálnì pøipojené dokumenty
                a seznam dokumentu, které lze pøipojit (stránkovaný s filtry výbìru)
                */
                $aktuality = new Aktuality("show", $zamestnanec->get_id(), $_GET["id_aktuality"]);
                echo $aktuality->show_submenu();
                //seznam dokumentu pripojenych k serialu
                $current_dokument = new Dokument_aktuality("show", $zamestnanec->get_id(), $_GET["id_aktuality"]);
                echo $current_dokument->get_error_message();

                if ($_GET["str"] == "") {
                    $_GET["str"] = 0;
                }
                //seznam dokumentu - parametry nazev_dokumentu, pocatek vypisu a pocet zaznamu(default. nastaveny)
                $dokument_list = new Dokument_list($zamestnanec->get_id(), $_SESSION["nazev_dokument"], $_GET["str"], $_SESSION["dokument_order_by"]);
                echo $dokument_list->get_error_message();

                ?>
                <div class="submenu">
                    <a href="?typ=aktuality_list">&lt;&lt; seznam aktualit</a>
                    <a href="?typ=aktuality&amp;pozadavek=new">vytvoøit novou aktualitu</a>
                </div>
                <?
                ?>
                <h3>Dokumenty pøiøazené k aktualitì</h3>
                <?
                echo $current_dokument->show_list_header();
                while ($current_dokument->get_next_radek()) {
                    echo $current_dokument->show_list_item("tabulka");
                }
                ?>
                </table>
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
                    echo $dokument_list->show_list_item("tabulka_aktuality");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($dokument_list->getZacatek(), $dokument_list->getPocetZajezdu(), $dokument_list->getPocetZaznamu());

                /*----------------	editace  fotografií -----------*/
            } else if ($_GET["typ"] == "foto") {
                ?>
                <div class="submenu">
                    <a href="?typ=aktuality_list">&lt;&lt; seznam aktualit</a>
                    <a href="?typ=aktuality&amp;pozadavek=new">vytvoøit novou aktualitu</a>
                </div>
                <?
                /*
                    u fotografii zobrazuju aktuálnì pøipojené fotografie
                    a seznam fotografií, které lze pøipojit (stránkovaný s filtry výbìru)
                */
                //seznam fotografii pripojenych k serialu
                $aktuality = new Aktuality("show", $zamestnanec->get_id(), $_GET["id_aktuality"]);
                echo $aktuality->show_submenu();
                $current_foto = new Foto_aktuality("show", $zamestnanec->get_id(), $_GET["id_aktuality"]);
                ?>
                <h3>Fotografie pøiøazené k aktualitì</h3>
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
                //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
                $foto_list = new Foto_list($zamestnanec->get_id(), $_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);

                //zobrazeni filtru pro vypis fotek
                echo $foto_list->show_filtr();
                ?>
                <h3>Seznam fotografií</h3>
                <?
                echo $foto_list->show_list_header();

                //zobrazeni jednotlivych zaznamu
                while ($foto_list->get_next_radek()) {
                    echo $foto_list->show_list_item("tabulka_aktuality");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($foto_list->getZacatek(), $foto_list->getPocetZajezdu(), $foto_list->getPocetZaznamu());
                /*----------------	editace  dokumentù -----------*/
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