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


require_once "./classes/pr_stranka.inc.php"; //seznamy dokumentu
require_once "./classes/pr_stranka_list.inc.php"; //seznamy dokumentu
require_once "./classes/pr_stranka_foto.inc.php";
require_once "./classes/zeme_list.inc.php";
require_once "./classes/foto_list.inc.php";

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
    /*--------------------- dokument_list ---------------*/
    if ($_GET["typ"] == "pr_stranka_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_pr_stranka"] = $_POST["nazev"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["pr_stranka_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=pr_stranka_list";
        }

        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "pr_stranka") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky
//                                $dotaz = new PrStranka("create",$zamestnanec->get_id(),"",$_POST["nazev_dokument"],$_POST["popisek_dokument"],$_POST["dokument"],$_POST["is_tiskova_zprava"]);	
            $dotaz = new PrStranka("create", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["nadpis"], $_POST["titulek"], $_POST["text"], $_POST["klicova_slova"], $_POST["adresa"], $_POST["adresy_list"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=pr_stranka_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
//				$dotaz = new PrStranka("update",$zamestnanec->get_id(),$_GET["id_dokument"],$_POST["nazev_dokument"],$_POST["popisek_dokument"],$_POST["dokument"],$_POST["is_tiskova_zprava"]);			
            $dotaz = new PrStranka("update", $zamestnanec->get_id(), $_GET["id_pr_stranky"], $_POST["nazev"], $_POST["nadpis"], $_POST["titulek"], $_POST["text"], $_POST["klicova_slova"], $_POST["adresa"], $_POST["adresy_list"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=pr_stranka_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
//				$dotaz = new PrStranka("delete",$zamestnanec->get_id(),$_GET["id_dokument"]);		
            $dotaz = new PrStranka("delete", $zamestnanec->get_id(), $_GET["id_pr_stranky"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=pr_stranka_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    } else if ($_GET["typ"] == "foto_list") {
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
                $_SESSION["foto_nepouzite"] = $_POST["foto_nepouzite"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["foto_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_pr_stranky=" . $_GET["id_pr_stranky"] . "";
        }
    } else if ($_GET["typ"] == "foto") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Foto_pr_stranka("create", $zamestnanec->get_id(), $_GET["id_pr_stranky"], $_GET["id_foto"], $_GET["pridat_jako"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_pr_stranky=" . $_GET["id_pr_stranky"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Foto_pr_stranka("update", $zamestnanec->get_id(), $_GET["id_pr_stranky"], $_GET["id_foto"], $_GET["pridat_jako"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_pr_stranky=" . $_GET["id_pr_stranky"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Foto_pr_stranka("delete", $zamestnanec->get_id(), $_GET["id_pr_stranky"], $_GET["id_foto"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_pr_stranky=" . $_GET["id_pr_stranky"] . "";
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

            //na zacatku zobrazim seznam dokumentù
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "pr_stranka_list";
            }

            /*----------------	seznam dokumentù -----------*/
            if ($_GET["typ"] == "pr_stranka_list") {
                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //seznam dokumentu - parametry nazev_dokumentu, pocatek vypisu a pocet zaznamu(default. nastaveny)
//				$pr_stranka_list = new PrStranka_list($zamestnanec->get_id(),$_SESSION["nazev_dokument"],$_GET["str"],$_SESSION["dokument_order_by"]);
                $pr_stranka_list = new PrStranka_list($zamestnanec->get_id(),$_SESSION["nazev_pr_stranka"],$_GET["str"],$_SESSION["pr_stranka_order_by"]);

                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $pr_stranka_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=pr_stranka&amp;pozadavek=new">vytvoøit novou PR stránku</a>
                </div>
                <?

                //zobrazeni filtru pro vypis dokumentù
                echo $pr_stranka_list->show_filtr();
                ?>
                <h3>Seznam dokumentù</h3>
                <?
                //zobrazeni hlavicky seznamu
                echo $pr_stranka_list->show_list_header();

                //zobrazeni jednotlivych zaznamu
                while ($pr_stranka_list->get_next_radek()) {
                    echo $pr_stranka_list->show_list_item("tabulka_pr_stranka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($pr_stranka_list->getZacatek(), $pr_stranka_list->getPocetZajezdu(), $pr_stranka_list->getPocetZaznamu());

                /*----------------	nový dokument -----------*/
            } else if ($_GET["typ"] == "pr_stranka" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

                ?>
                <div class="submenu">
                    <a href="?typ=pr_stranka_list">&lt;&lt; seznam PR stránek</a>
                </div>
<?
//			$pr_stranka = new PrStranka("new",$zamestnanec->get_id(),"",$_POST["nazev_dokument"],$_POST["popisek_dokument"],$_POST["dokument"],$_GET["pozadavek"]);
                $pr_stranka = new PrStranka("new", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["nadpis"], $_POST["titulek"], $_POST["text"], $_POST["img1"], $_POST["img1_alt"], $_POST["img1_titulek"], $_POST["img2"], $_POST["img2_alt"], $_POST["img2_titulek"], $_POST["klicova_slova"], $_POST["adresa"], $_POST["adresy_list"], $_GET["pozadavek"]);

                ?><h3>Vytvoøit nový dokument</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $pr_stranka->show_form();

                /*----------------	editace dokumentu -----------*/
            } else if ($_GET["typ"] == "pr_stranka" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                ?>
                <div class="submenu">
                    <a href="?typ=pr_stranka_list">&lt;&lt; seznam PR stránek</a>
                    <a href="?typ=pr_stranka&amp;pozadavek=new">vytvoøit novou PR stránku</a>
                </div>
<?php

//			$pr_stranka = new PrStranka("edit",$zamestnanec->get_id(),$_GET["id_dokument"],$_POST["nazev_dokument"],$_POST["popisek_dokument"],$_POST["dokument"],$_GET["pozadavek"]);
                $pr_stranka = new PrStranka("edit", $zamestnanec->get_id(), $_GET["id_pr_stranky"], $_POST["nazev"], $_POST["nadpis"], $_POST["titulek"], $_POST["text"], $_POST["img1"], $_POST["img1_alt"], $_POST["img1_titulek"], $_POST["img2"], $_POST["img2_alt"], $_POST["img2_titulek"], $_POST["klicova_slova"], $_POST["adresa"], $_POST["adresy_list"], $_GET["pozadavek"]);
                echo $pr_stranka->show_submenu();

                ?><h3>Editace PR stránky</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho dokumentu
                echo $pr_stranka->show_form();

                /*----------------	editace  fotografií -----------*/
            } else if ($_GET["typ"] == "foto") {

                /*
                    u fotografii zobrazuju aktuálnì pøipojené fotografie
                    a seznam fotografií, které lze pøipojit (stránkovaný s filtry výbìru)
                */
                //seznam fotografii pripojenych k serialu

                $pr_stranka = new PrStranka("edit", $zamestnanec->get_id(), $_GET["id_pr_stranky"]);
                $current_foto = new Foto_pr_stranka("show", $zamestnanec->get_id(), $_GET["id_pr_stranky"]);

                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)


                ?>
                <div class="submenu">
                    <a href="?typ=pr_stranka_list">&lt;&lt; seznam PR stránek</a>
                    <a href="?typ=pr_stranka&amp;pozadavek=new">vytvoøit novou PR stránku</a>
                </div>
                <?php
                echo $pr_stranka->show_submenu();
                ?>
                <h3>Fotografie pøiøazené k PR stránce</h3>
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
                    $foto_list = new Foto_list($zamestnanec->get_id(), "", "", $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);
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
                    echo $foto_list->show_list_item("tabulka_pr_stranky");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($foto_list->getZacatek(), $foto_list->getPocetZajezdu(), $foto_list->getPocetZaznamu());
                /*----------------	editace  dokumentù -----------*/
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