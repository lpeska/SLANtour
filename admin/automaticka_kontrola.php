<?php
//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/automaticka_kontrola.inc.php"; //seznamy dokumentu
require_once "./classes/automaticka_kontrola_list.inc.php"; //seznamy dokumentu

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
    
$adress = "";

    /*--------------------- zeme ---------------*/
    if ($_GET["typ"] == "kontroly_list" ) {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)

            if ($_GET["pole"] == "ord_by") {
                $_SESSION["kontrola_order_by"] = $_GET["ord_by"];
            }
            if ($_SESSION["kontrola_order_by"] == "") {
                $_SESSION["kontrola_order_by"] = "datum_down";
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=kontroly_list";
        }
        $_GET["typ"] = "export_list";


    } else if ($_GET["typ"] == "kontrola") {
        if ($_GET["pozadavek"] == "new") {
            $dotaz = new Automaticka_kontrola("new", $zamestnanec->get_id());

        }else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Automaticka_kontrola("delete", $zamestnanec->get_id());

        }

    }
    //if($_GET["typ"]==...
    $_GET["typ"] = "kontroly_list";
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

<?php
if ($zamestnanec->get_correct_login()) {
//prihlaseni probehlo vporadku, muzu pokracovat
    //zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

    //zobrazeni aktualnich informaci - nove rezervace, pozadavky...
    ?>
    <div class="main-wrapper">
        <div class="main">
            <?php
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            //na zacatku zobrazim seznam
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "kontroly_list";
            }

            if ($_GET["typ"] == "kontroly_list") {
                ?>
                <form action="automaticka_kontrola.php?typ=kontrola&pozadavek=new" method="post">

                    <input type="hidden" name="typ_serialu" value="cele"/>
                    <input type="hidden" name="format" value="vse"/>
                    Poèet dnù do odletu minimálnì: <input class="two_digit" type="text" name="days_after" value="5"/>, maximálnì: <input  class="two_digit" type="text" name="days_before" value="60"/>
                    <input type="submit" value="Vygenerovat automaticke kontroly"/>
                </form>
                <h3>Seznam objektù s automatickou kontrolou</h3>
                <?php
                    $query = "SELECT * FROM `objekt_letenka` WHERE `automaticka_kontrola_cen` = 1";
                    $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                    while($row = mysqli_fetch_array($res)){
                        echo "<a href=\"/admin/objekty.php?id_objektu=".$row["id_objektu"]."&typ=tok_list&pozadavek=show_letuska\">".$row["id_objektu"]."</a> (".$row["flight_from"]." - ".$row["flight_to"]."); \n";
                    }
                 ?>

                <h3>Seznam objektù s automatickou odloženou kontrolou</h3>
                <?php
                    $query = "SELECT * FROM `objekt_letenka` WHERE `automaticka_odlozena_kontrola_cen` = 1";
                    $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                    while($row = mysqli_fetch_array($res)){
                        echo "<a href=\"/admin/objekty.php?id_objektu=".$row["id_objektu"]."&typ=tok_list&pozadavek=show_letuska\">".$row["id_objektu"]."</a> (".$row["flight_from"]." - ".$row["flight_to"]."); \n";
                    }
                 ?>
                 
                <h3>Seznam existujících výstupù kontrol</h3>
                <?php

                $list = new Automaticka_kontrola_list($zamestnanec->get_id(), $_GET["str"], $_SESSION["kontrola_order_by"]);

                //zobrazeni hlavicky seznamu
                echo $list->show_list_header();
                //zobrazeni seznamu
                //zobrazeni jednotlivych zaznamu
                while ($list->get_next_radek()) {
                    echo $list->show_list_item("tabulka");
                }
                echo "</table>";


            } //if typ
            ?>
        </div>
    </div>
    <?php
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



