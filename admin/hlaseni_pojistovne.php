<?php
//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/serial_list.inc.php"; //seznamy serialu

require_once "./classes/zamestnanec_list.inc.php";

//global
require_once "../global/lib/utils/CommonUtils.php";


require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsZajezd.php";

require_once "./classes/ts/objednavka_dao.inc.php";
require_once "./classes/ts/hlaseni_pojistovne_displayer.inc.php";
require_once "./classes/ts/hlaseni_pojistovne_ts.inc.php";
require_once "./classes/ts/utils_ts.inc.php";


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
    if ($_GET["typ"] == "hlaseni_pojistovne") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {

            $_SESSION["zajezd_termin_od"] = CommonUtils::engDate($_POST["zajezd_termin_od"]);
            $_SESSION["zajezd_termin_do"] = CommonUtils::engDate($_POST["zajezd_termin_do"]);  

            $_SESSION["objednavka_termin_od"] = CommonUtils::engDate($_POST["objednavka_termin_od"]);
            $_SESSION["objednavka_termin_do"] = CommonUtils::engDate($_POST["objednavka_termin_do"]); 

            $_SESSION["zajezdy_dle_zakona"] = $_POST["zajezdy_dle_zakona"];
            $_SESSION["zobrazit_objednavky"] = $_POST["zobrazit_objednavky"];                                                           

            if($_POST["submit"]=="Zobrazit realizované zájezdy"){
                $adress = "/admin/hlaseni_pojistovne.php?typ=realizovane_objednavky";
            }else if($_POST["submit"]=="Zobrazit nové objednávky"){
                $adress = "/admin/hlaseni_pojistovne.php?typ=nove_objednavky";
            }
            
        }

        /*---------------------serial---------------*/     

    }
    //if-else typ editace

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
    <link type="text/css" href="./css/jquery-ui.min.css" rel="stylesheet" />
    <script type="text/javascript" src="./js/jquery-min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui-cze.min.js"></script>
    <script type="text/javascript" src="js/blackdays.js"></script>
    <script type="text/javascript" src="./js/common_functions.js"></script>
    <script type="text/javascript" src="./js/serial.js"></script>
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
        $_GET["typ"] = "hlaseni_pojistovne";
    }
    echo Serial_list::show_filtr_hlaseni_pojistovne();
        

    if($_GET["typ"] == "realizovane_objednavky" or $_GET["typ"] == "nove_objednavky"){
        
        $ts = new HlaseniPojistovneTS($_SESSION, $_GET["typ"]);
        $html = $ts->createHtml();
        echo $html;
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