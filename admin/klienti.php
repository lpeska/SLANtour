<?
/**   \file
 * klienti.php  - seznam klientù ck
 *                 - jejich pøidávání/editace
 *                 - odkazy na rezervace klientù
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_klient = id klienta
 */
//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/zeme_list.inc.php"; //seznamy klientù
require_once "./classes/typ_serialu_list.inc.php"; //seznamy klientù
require_once "./classes/klient_list.inc.php"; //seznamy klientù
require_once "./classes/klient.inc.php"; //detail seriálu

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

/* --------------	POZADAVKY DO DATABAZE	------------------------- */
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
    $adress = "";
    /* ---------------------serial_list --------------- 
    
    function array_to_csv_download($array, $filename = "export.csv", $delimiter=";") {
    // open raw memory as file so no temp files needed, you might run out of memory though
    $f = fopen('php://memory', 'w'); 
    // loop over the input array
    foreach ($array as $line) { 
        // generate csv lines from the inner arrays
        fputcsv($f, $line, $delimiter); 
    }
    // reset the file pointer to the start of the file
    fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: application/csv');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachment; filename="'.$filename.'";');
    // make php send the generated csv lines to the browser
    fpassthru($f);
}
    
    */
    if ($_GET["typ"] == "klient_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "show_complex_filter" and $_POST["export_csv"] != "") {
            //process klients as CSV and prompt to download
            $klient_list = new Klient_list("show_complex_filter", $_SESSION["klient_jmeno"], $_SESSION["klient_prijmeni"], $_SESSION["klient_datum_narozeni"], $_SESSION["je_ca"], $_GET["str"], $_SESSION["klient_order_by"],
                    $_GET["moznosti_editace"]);
                    
            $header = "\"ID\";\"prijmeni\";\"jmeno\";\"e-mail\";\"telefon\";\"mesto\"\n";
            $f = fopen('php://memory', 'w'); 
            fwrite($f,$header);
            
            while ($klient_list->get_next_radek()) {
                $row = $klient_list->show_list_item("csv_export");
                //echo $row; 
                fwrite($f, $row);
            }            
            // reset the file pointer to the start of the file
            fseek($f, 0);
            header('Content-Type: application/csv');
            // tell the browser we want to save it instead of displaying it
            header('Content-Disposition: attachment; filename="klienti.csv";');
            // make php send the generated csv lines to the browser
            fpassthru($f);
        
            $_POST["export_csv"] == "";
            exit();
        }
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy klient_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "jmeno_prijmeni_datum") {
                $_SESSION["klient_jmeno"] = $_POST["klient_jmeno"];
                $_SESSION["klient_prijmeni"] = $_POST["klient_prijmeni"];
                $_SESSION["klient_mesto"] = $_POST["klient_mesto"];
                $_SESSION["klient_rok_narozeni"] = $_POST["klient_rok_narozeni"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["klient_order_by"] = $_GET["klient_order_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=klient_list&moznosti_editace=" . $_GET["moznosti_editace"] . "";
        }

        /* ---------------------serial--------------- */
    } else if ($_GET["typ"] == "klient") {
        if ($_GET["pozadavek"] == "create_ajax") {
            $dotaz = new Klient("create_ajax", $zamestnanec->get_id(), "", $_POST["jmeno"], $_POST["prijmeni"], $_POST["titul"],
                $_POST["datum_narozeni"], $_POST["rodne_cislo"], $_POST["email"], $_POST["telefon"], $_POST["cislo_op"], $_POST["cislo_pasu"],
                $_POST["ulice"], $_POST["mesto"], $_POST["psc"]);
            if (!$dotaz->get_error_message()) {
                echo $dotaz->get_id_klient();
            }
            exit;
        } else if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù						
            $dotaz = new Klient("create", $zamestnanec->get_id(), "", $_POST["jmeno"], $_POST["prijmeni"], $_POST["titul"],
                $_POST["datum_narozeni"], $_POST["rodne_cislo"], $_POST["email"], $_POST["telefon"], $_POST["cislo_op"], $_POST["cislo_pasu"],
                $_POST["ulice"], $_POST["mesto"], $_POST["psc"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=klient_list&moznosti_editace=" . $_GET["moznosti_editace"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "update" or $_GET["pozadavek"] == "update_form") {
            $dotaz = new Klient($_GET["pozadavek"], $zamestnanec->get_id(), $_GET["id_klient"], $_POST["jmeno"], $_POST["prijmeni"], $_POST["titul"],
                $_POST["datum_narozeni"], $_POST["rodne_cislo"], $_POST["email"], $_POST["telefon"], $_POST["cislo_op"], $_POST["cislo_pasu"],
                $_POST["ulice"], $_POST["mesto"], $_POST["psc"]);
            if ($_GET["ajax"] == "true")
                exit();
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=klient_list&moznosti_editace=" . $_GET["moznosti_editace"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "create_account") {
            $dotaz = new Klient("create_account", $zamestnanec->get_id(), $_GET["id_klient"], "", "", "", "", "", "", "", "", "", "", "", "",
                $_POST["uzivatelske_jmeno"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=klient_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Klient("delete", $zamestnanec->get_id(), $_GET["id_klient"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=klient_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    }
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
    <script type="text/javascript" src="./js/common_functions.js"></script>
</head>
<body>
<?

if ($zamestnanec->get_correct_login()) {
//prihlaseni probehlo vporadku, muzu pokracovat
//zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());

//zobrazeni aktualnich informaci - nove rezervace, pozadavky...
    ?>
    <div class='main-wrapper'>
        <div class='main'>
            <?
            //vypisu pripadne hlasky o uspechu operaci
            echo $hlaska_k_vypsani;

            //na zacatku zobrazim seznam
            if ($_GET["typ"] == "") {
                $_GET["typ"] = "klient_list";
            }

            /* ---------------- seznam seriálù ----------- */
            if ($_GET["typ"] == "klient_list" and $_GET["pozadavek"] == "show_complex_filter") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci klient_list
                $klient_list = new Klient_list("show_complex_filter", $_SESSION["klient_jmeno"], $_SESSION["klient_prijmeni"], $_SESSION["klient_datum_narozeni"], $_SESSION["je_ca"], $_GET["str"], $_SESSION["klient_order_by"],
                    $_GET["moznosti_editace"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $klient_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=klient_list">&lt;&lt; seznam klientù</a>
                    <? echo "<a href=\"?typ=klient_list&amp;pozadavek=show_complex_filter&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">export klientù do CSV</a>" ?>                
                    <? echo "<a href=\"?typ=klient&amp;pozadavek=new&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">vytvoøit nového klienta</a>" ?>
                    
                </div>
                <?
                //zobrazim filtry
                echo $klient_list->show_complex_filter();
                //zobrazim nadpis seznamu
                echo $klient_list->show_header();
                //zobrazim hlavicku seznamu
                echo $klient_list->show_list_header();

                //vypis jednotlivych serialu
                while ($klient_list->get_next_radek()) {
                    echo $klient_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
            
            
            }else if ($_GET["typ"] == "klient_list") {

                //pokud nemam strankovani, zacnu nazacatku:)
                if ($_GET["str"] == "") {
                    $_GET["str"] = "0";
                }

                //vytvorime instanci klient_list
                $klient_list = new Klient_list("show_all", $_SESSION["klient_jmeno"], $_SESSION["klient_prijmeni"], $_SESSION["klient_datum_narozeni"], $_SESSION["je_ca"], $_GET["str"], $_SESSION["klient_order_by"],
                    $_GET["moznosti_editace"]);
                //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
                echo $klient_list->get_error_message();

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=klient_list">&lt;&lt; seznam klientù</a>
                    <? echo "<a href=\"?typ=klient_list&amp;pozadavek=show_complex_filter&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">export klientù do CSV</a>" ?>                   
                    <? echo "<a href=\"?typ=klient&amp;pozadavek=new&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">vytvoøit nového klienta</a>" ?>
                </div>
                <?
                //zobrazim filtry
                echo $klient_list->show_filtr();
                //zobrazim nadpis seznamu
                echo $klient_list->show_header();
                //zobrazim hlavicku seznamu
                echo $klient_list->show_list_header();

                //vypis jednotlivych serialu
                while ($klient_list->get_next_radek()) {
                    echo $klient_list->show_list_item("tabulka");
                }
                ?>
                </table>
                <?
                //zobrazeni strankovani
                echo ModulView::showPaging($klient_list->getZacatek(), $klient_list->getPocetZajezdu(), $klient_list->getPocetZaznamu());

                /* ----------------	nový seriál ----------- */
            } else if ($_GET["typ"] == "klient" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
                ?>
                <div class="submenu">
                    <a href="?typ=klient_list">&lt;&lt; seznam klientù</a>
                    <? echo "<a href=\"?typ=klient_list&amp;pozadavek=show_complex_filter&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">export klientù do CSV</a>" ?>
                </div>
                <?
                $klient = new Klient("new", $zamestnanec->get_id(), "", $_POST["jmeno"], $_POST["prijmeni"], $_POST["titul"],
                    $_POST["datum_narozeni"], $_POST["rodne_cislo"], $_POST["email"], $_POST["telefon"], $_POST["cislo_op"], $_POST["cislo_pasu"],
                    $_POST["ulice"], $_POST["mesto"], $_POST["psc"], "", $_GET["pozadavek"]);
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                ?><h3>Vytvoøit nového klienta</h3><?
                echo $klient->show_form();
            } else if ($_GET["typ"] == "klient" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=klient_list">&lt;&lt; seznam klientù</a>
                    <? echo "<a href=\"?typ=klient_list&amp;pozadavek=show_complex_filter&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">export klientù do CSV</a>" ?>
                    <a href="?typ=klient&amp;pozadavek=new">vytvoøit nového klienta</a>
                    
                    <?php
                     echo "<a href=\"?id_klient=" . $_GET["id_klient"] . "&amp;typ=klient&amp;pozadavek=objednavky\">objednávky</a>";
                   ?>                    
                    <br/>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $klient = new Klient("edit", $zamestnanec->get_id(), $_GET["id_klient"], $_POST["jmeno"], $_POST["prijmeni"], $_POST["titul"],
                    $_POST["datum_narozeni"], $_POST["rodne_cislo"], $_POST["email"], $_POST["telefon"], $_POST["cislo_op"], $_POST["cislo_pasu"],
                    $_POST["ulice"], $_POST["mesto"], $_POST["psc"], "", $_GET["pozadavek"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Editace klienta</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $klient->show_form();
            } else if ($_GET["typ"] == "klient" and $_GET["pozadavek"] == "objednavky") {

                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=klient_list">&lt;&lt; seznam klientù</a>
                    <a href="?typ=klient&amp;pozadavek=new">vytvoøit nového klienta</a>
                    <?php
                     echo "<a href=\"?id_klient=" . $_GET["id_klient"] . "&amp;typ=klient&amp;pozadavek=objednavky\">objednávky</a>";
                   ?>                      
                    <br/>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $klient = new Klient("edit", $zamestnanec->get_id(), $_GET["id_klient"], $_POST["jmeno"], $_POST["prijmeni"], $_POST["titul"],
                    $_POST["datum_narozeni"], $_POST["rodne_cislo"], $_POST["email"], $_POST["telefon"], $_POST["cislo_op"], $_POST["cislo_pasu"],
                    $_POST["ulice"], $_POST["mesto"], $_POST["psc"], "", $_GET["pozadavek"]);
                ?><h3>Objednávky klienta/agentury</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $klient->show_objednavky();
            } else if ($_GET["typ"] == "klient" and ($_GET["pozadavek"] == "new_account" or $_GET["pozadavek"] == "create_account")) {
                //klient kterého vytvoøila ck, ale žádá o vytvoøení úètu (aby se mohl pøihlásit do systému)
                //vypisu menu
                ?>
                <div class="submenu">
                    <a href="?typ=klient_list">&lt;&lt; seznam klientù</a>
                    <a href="?typ=klient&amp;pozadavek=new">vytvoøit nového klienta</a>
                    <br/>
                </div>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                $klient = new Klient("new_account", $zamestnanec->get_id(), $_GET["id_klient"], $_GET["id_klient"], "", "", "", "", "", "", "", "", "", "", "", "",
                    $_POST["uzivatelske_jmeno"], $_GET["pozadavek"]);
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                ?>
                <h3>Vytvoøení uživatelského úètu pro klienta</h3>
                <?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $klient->show_account_form();
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