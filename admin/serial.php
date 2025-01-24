<?php
/**     \file
 * serial.php  - administrace seri�l� + z�jezd� + cen
 *                - pridavani zem�, fotek, dokument� a informac� k jednotliv�m serialu
 *                - seznam rezervac� pro z�jezd/seri�l (odkaz do rezervac�)
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_serial = id objedn�vky
 * @param $id_zajezd = id z�jezdu
 * @param $id_cena = id slu�by seri�lu
 * @param $id_zeme = id zeme
 * @param $id_destinace = id destinace
 * @param $id_foto = id z�jezdu
 * @param $id_dokument = id dokumentu
 * @param $id_informace = id informace
 */

//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";
require_once "./config/config_export_sdovolena.inc.php"; //seznamy serialu

require_once "./classes/serial_list.inc.php"; //seznamy serialu
require_once "./classes/zajezd_list.inc.php"; //seznam zajezdu serialu
require_once "./classes/foto_list.inc.php"; //seznamy fotografii
require_once "./classes/slevy_list.inc.php"; //seznamy fotografii
require_once "./classes/dokument_list.inc.php"; //seznamy dokumentu
require_once "./classes/zeme_list.inc.php"; //seznamy fotografii
require_once "./classes/informace_list.inc.php"; //seznamy fotografii
require_once "./classes/objekty_list.inc.php"; //seznamy fotografii
require_once "./classes/typ_serialu_list.inc.php"; //seznamy typu serialu

require_once "./classes/serial.inc.php"; //detail seri�lu
require_once "./classes/zajezd.inc.php"; //t��dy pro zajezdy

require_once "./classes/serial_cena.inc.php"; 
require_once "./classes/cena_kv.inc.php"; //t��dy pro p�ipojov�n� cen k seri�lu
require_once "./classes/serial_foto.inc.php"; //t��dy pro p�ipojov�n� fotografi� k seri�lu
require_once "./classes/serial_dokument.inc.php"; //t��dy pro p�ipojov�n� dokument� k seri�lu
require_once "./classes/serial_informace.inc.php"; //t��dy pro p�ipojov�n� informac� k seri�lu
require_once "./classes/serial_objekty.inc.php"; //t��dy pro p�ipojov�n� informac� k seri�lu
require_once "./classes/serial_zeme.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/serial_slevy.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu

require_once "./classes/zajezd_slevy.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/zajezd_cena.inc.php"; //t��dy pro ceny zajezdu
require_once "./classes/zajezd_topologie.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/topologie_list.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/topologie.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/tok.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/tok_topologie.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu
require_once "./classes/objekt.inc.php"; //t��dy pro p�ipojov�n� zem� k seri�lu

require_once "./classes/blackdays_list.inc.php"; //t��dy pro blackdays
require_once "./classes/blackdays.inc.php"; //t��dy pro blackdays

require_once "./classes/zamestnanec_list.inc.php";

//global
require_once "../global/lib/utils/CommonUtils.php";

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
    if ($_GET["typ"] == "serial_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {

            //rozdeleni pole typ:podtyp na id_typ a id_podtyp
         /*   if ($_POST["typ-podtyp"] != "") {
                //vstup je ve tvaru typ:podtyp
                $typ_array = explode(":", $_POST["typ-podtyp"]);
                $id_typ = $typ_array[0];
                $id_podtyp = $typ_array[1];
            } else {
                $id_typ = "";
                $id_podtyp = "";
            }*/
            //kontrola vstupu je provadena pri volani konstruktoru t�idy serial_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "typ-podtyp-nazev") {
                $_SESSION["serial_typ_podtyp"] = $_POST["typ-podtyp"];
               // $_SESSION["serial_typ"] = $id_typ;
               // $_SESSION["serial_podtyp"] = $id_podtyp;
                $_SESSION["serial_nazev"] = $_POST["nazev"];
                $_SESSION["serial_doprava"] = $_POST["doprava"];
                $_SESSION["serial_zeme"] = $_POST["zeme"];
                $_SESSION["bez_spravce"] = $_POST["bez_spravce"];
                $_SESSION["bez_provize"] = $_POST["bez_provize"];

                //pokrocile filtry
                if($_POST["pokrocile_filtry"] == "1"){
                    $_SESSION["f_pokrocily_filtr"] = $_POST["pokrocile_filtry"];     
                    $_SESSION["f_prehled_obsazenosti"] = 0;
                    
                    $_SESSION["f_zajezd_od"] = CommonUtils::engDate($_POST["f_zajezd_od"]);
                    $_SESSION["f_zajezd_do"] = CommonUtils::engDate($_POST["f_zajezd_do"]);                    
                    
                    $_SESSION["f_serial_no_zajezd"] = $_POST["f_serial_no_zajezd"];
                    $_SESSION["f_serial_no_aktivni_zajezd"] = $_POST["f_serial_no_aktivni_zajezd"];
                    $_SESSION["f_serial_aktivni_zajezd"] = $_POST["f_serial_aktivni_zajezd"];
                    $_SESSION["f_zajezd_objednavka"] = $_POST["f_zajezd_objednavka"];
                    $_SESSION["f_zajezd_no_objednavka"] = $_POST["f_zajezd_no_objednavka"];
                    $_SESSION["f_zobrazit_zajezdy"] =  $_POST["f_zobrazit_zajezdy"];
                    
                }else if($_POST["prehled_zajezdu"] == "1"){
                    $_SESSION["f_prehled_obsazenosti"] = $_POST["prehled_zajezdu"];     
                    $_SESSION["f_pokrocily_filtr"] = 0;
                    
                    $_SESSION["f_zajezd_od"] = CommonUtils::engDate($_POST["f_zajezd_od_pz"]);
                    $_SESSION["f_zajezd_do"] = CommonUtils::engDate($_POST["f_zajezd_do_pz"]);
                    $_SESSION["f_zobrazit_zajezdy"] =  0;                    
                                   
                    $_SESSION["serial_ord_by"] = $_POST["razeni"];
                    
                }else{
                    //vsechny pokrocile filtry se smazou                    
                    $_SESSION["f_pokrocily_filtr"] = 0;
                    $_SESSION["f_prehled_obsazenosti"] = 0;
                    
                    $_SESSION["f_zajezd_od"] = "";
                    $_SESSION["f_zajezd_do"] = "";
                    
                    $_SESSION["f_serial_no_zajezd"] = 0;
                    $_SESSION["f_serial_no_aktivni_zajezd"] = 0;
                    $_SESSION["f_serial_aktivni_zajezd"] = 0;
                    $_SESSION["f_zajezd_objednavka"] = 0;
                    $_SESSION["f_zajezd_no_objednavka"] = 0;
                    $_SESSION["f_zobrazit_zajezdy"] =  0;
                    
                }

                


            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["serial_ord_by"] = $_GET["ord_by"];
            }
            if($_POST["submit"]=="Vygenerovat PDF"){
                $adress = "/admin/ts_serial.php?typ=prehled_objednavek&moznosti_editace=zadne";
            }else{
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_list&moznosti_editace=" . $_GET["moznosti_editace"] . "";
            }
            
        }

        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "serial") {

        //rozdeleni pole typ-podtyp na typ a podtyp
        if ($_GET["pozadavek"] == "create" or $_GET["pozadavek"] == "update") {
            if ($_POST["typ-podtyp"] != "") {
                //vstup je ve tvaru typ:podtyp
                $typ_array = explode(":", $_POST["typ-podtyp"]);
                $id_typ = $typ_array[0];
                $id_podtyp = $typ_array[1];
            } else {
                $id_typ = "";
                $id_podtyp = "";
            }
        }
        if ($_GET["pozadavek"] == "copy") {
            $dotaz = new Serial("copy", $zamestnanec->get_id(), $_GET["id_serial"], $_POST["nazev"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_list";
                //potvrzovaci hlaska
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                //chybova hlaska
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seri�l�		
            $database = Database::get_instance();

            $dotaz = new Serial("create", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["nazev_web"], $_POST["popisek"], $_POST["popis"],
                $_POST["popis_ubytovani"], $_POST["popis_stravovani"], $_POST["popis_strediska"], $_POST["popis_lazni"], $_POST["program_zajezdu"],
                $_POST["cena_zahrnuje"], $_POST["cena_nezahrnuje"], $_POST["poznamky"],
                $id_typ, $id_podtyp, $_POST["strava"], $_POST["doprava"], $_POST["ubytovani"], $_POST["ubytovani_kategorie"], $_POST["dlouhodobe_zajezdy"], $_POST["highlights"], $_POST["jazyk"], $_POST["predregistrace"], $_POST["nezobrazovat"],
                $_POST["typ_provize"], $_POST["vyse_provize"], $_POST["id_smluvni_podminky"], $_POST["id_sml_podm"], $_POST["id_sablony_zobrazeni"], $_POST["id_sablony_objednavka"], "", $_POST["spravce"]);

            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&pozadavek=new&id_serial=" . $dotaz->get_id() . "&pocet_cen=" . intval($_POST["pocet_cen"]) . "";
                //potvrzovaci hlaska
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                //chybova hlaska
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }


        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Serial("update", $zamestnanec->get_id(), $_GET["id_serial"], $_POST["nazev"], $_POST["nazev_web"], $_POST["popisek"], $_POST["popis"],
                $_POST["popis_ubytovani"], $_POST["popis_stravovani"], $_POST["popis_strediska"], $_POST["popis_lazni"], $_POST["program_zajezdu"],
                $_POST["cena_zahrnuje"], $_POST["cena_nezahrnuje"], $_POST["poznamky"],
                $id_typ, $id_podtyp, $_POST["strava"], $_POST["doprava"],
                $_POST["ubytovani"], $_POST["ubytovani_kategorie"], $_POST["dlouhodobe_zajezdy"], $_POST["highlights"], $_POST["jazyk"], $_POST["predregistrace"], $_POST["nezobrazovat"],
                $_POST["typ_provize"], $_POST["vyse_provize"], $_POST["id_smluvni_podminky"], $_POST["id_sml_podm"], $_POST["id_sablony_zobrazeni"], $_POST["id_sablony_objednavka"], $zamestnanec->get_id(), $_POST["spravce"]);
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_list";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }


        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Serial("delete", $zamestnanec->get_id(), $_GET["id_serial"]);
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_list";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "delete_with_objednavky") {
            $dotaz = new Serial("delete_with_objednavky", $zamestnanec->get_id(), $_GET["id_serial"]);
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_list";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }


        /*---------------------zajezd---------------*/
    } else if ($_GET["typ"] == "zajezd") {
        if ($_GET["pozadavek"] == "copy") {

            $database = Database::get_instance();

            $dotaz = new Zajezd("copy", $_GET["id_serial"], $_GET["id_zajezd"]);
            //zaroven se zajezdem vytvarim take ceny zajezdu
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen	
            $id_topologie = $dotaz->get_id_topologie_zajezdu($_GET["id_zajezd"]);            
            if($id_topologie>0){
                $id_zajezd = $dotaz->get_id_zajezd();
                //jeste musime vytvorit topologii k zajezdu
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=topologie&id_serial=".$_GET["id_serial"]."&id_zajezd=$id_zajezd&id_topologie=$id_topologie&pozadavek=add_new&return=zajezd_list";
            }else if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location		
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                 $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

        if ($_GET["pozadavek"] == "create") {

            $database = Database::get_instance();
            //print_r($_POST);
            $dotaz = new Zajezd("create", $_GET["id_serial"], "", $_POST["id_zapas"], $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], $_POST["cena_pred_akci"], $_POST["akcni_cena"], $_POST["popis_akce"], $_POST["provizni_koeficient"]);
            //zaroven se zajezdem vytvarim take ceny zajezdu
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen	
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }


        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Zajezd("update", $_GET["id_serial"], $_GET["id_zajezd"], $_POST["id_zapas"], $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], $_POST["cena_pred_akci"], $_POST["akcni_cena"], $_POST["popis_akce"], $_POST["provizni_koeficient"]);

            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Zajezd("delete", $_GET["id_serial"], $_GET["id_zajezd"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "mass-delete") {
            foreach ($_POST["zajezd_delete_ids"] as $zajezdId) {
                $dotaz = new Zajezd("delete", $_GET["id_serial"], $zajezdId);
                //pokud vse probehlo spravne, vypisu OK hlasku
                if (!$dotaz->get_error_message()) {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
                } else {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
                }
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
//            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
            exit();
        } else if ($_GET["pozadavek"] == "mass-soldout") {
            foreach ($_POST["zajezd_delete_ids"] as $zajezdId) {
                $dotaz = new Zajezd("soldout", $_GET["id_serial"], $zajezdId);
                //pokud vse probehlo spravne, vypisu OK hlasku
                if (!$dotaz->get_error_message()) {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
                } else {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
                }
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
//            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
            exit();            
        } else if ($_GET["pozadavek"] == "delete_with_objednavky") {
            $dotaz = new Zajezd("delete_with_objednavky", $_GET["id_serial"], $_GET["id_zajezd"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

        /*--------------------- cena ---------------*/
    } else if ($_GET["typ"] == "cena") {
        if ($_GET["pozadavek"] == "ajax_get_goglobal_ceny") {
            $dotaz = new Cena_serial("ajax_get_goglobal_ceny", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_cena"], "");
            if (!$dotaz->get_error_message()) {
                echo $dotaz->show_goglobal_ceny();
            }else{
                echo $dotaz->get_error_message();
                //print_r($_GET);
            }
            exit();

        } else if ($_GET["pozadavek"] == "ajax_get_letuska_ceny") {
            $dotaz = new Cena_serial("ajax_get_letuska_ceny", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_cena"], "");
            if (!$dotaz->get_error_message()) {
                echo $dotaz->show_letuska_ceny();
            }else{
                echo $dotaz->get_error_message();
                //print_r($_GET);
            }
            exit();

        } else if ($_GET["pozadavek"] == "ajax_get_goglobal_hotel_id") {
            echo Cena_serial::ajax_get_goglobal_hotel_by_name();

            exit();

        } else if ($_GET["pozadavek"] == "ajax_dalsi_sluzby") {
            $dotaz = new Cena_serial("ajax_dalsi_sluzby", $zamestnanec->get_id(), $_GET["id_serial"], "", "");
            if (!$dotaz->get_error_message()) {
                echo $dotaz->ajax_show_form_ceny($_GET["posledni_cena"], $_GET["pocet_novych"]);
            }else{
                echo $dotaz->get_error_message();
                //print_r($_GET);
            }
            exit();

        } else if ($_GET["pozadavek"] == "create") {
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen
            $dotaz = new Cena_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], "", $_POST["pocet"]);

            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= $dotaz->get_pocet()) {
                    //test zda zakladni cena je prave tahle
                    if ($_POST["zakladni_cena"] == $i) {
                        $zaklad_cena = 1;
                    } else {
                        $zaklad_cena = 0;
                    }
                    $dotaz->add_to_query("", $_POST["id_objekt_kategorie_" . $i], $_POST["nazev_cena_" . $i], $_POST["kratky_nazev_" . $i], $_POST["odjezdove_misto_" . $i], $_POST["kod_letiste_" . $i], $_POST["zkraceny_vypis_" . $i], $_POST["poradi_ceny_" . $i], $_POST["typ_ceny_" . $i], $zaklad_cena, $_POST["kapacita_bez_omezeni_" . $i], $_POST["use_pocet_noci_" . $i], $_POST["nazev_cena_en_" . $i], $_POST["kratky_nazev_en_" . $i], $_POST["typ_provize_" . $i], $_POST["vyse_provize_" . $i], $_POST["nevytvaret_" . $i]);
                    $i++;
                }

                $dotaz->finish_query();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location	
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "";

            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }


        } else if ($_GET["pozadavek"] == "update") {
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen
            $dotaz = new Cena_serial("update", $zamestnanec->get_id(), $_GET["id_serial"], "", $_POST["pocet"]);

            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= $dotaz->get_pocet()) {
                    //test zda zakladni cena je prave tahle
                    if ($_POST["zakladni_cena"] == $i) {
                        $zaklad_cena = 1;
                    } else {
                        $zaklad_cena = 0;
                    }
                    $dotaz->add_to_query($_POST["id_cena_" . $i], $_POST["id_objekt_kategorie_" . $i], $_POST["nazev_cena_" . $i], $_POST["kratky_nazev_" . $i], $_POST["odjezdove_misto_" . $i], $_POST["kod_letiste_" . $i], $_POST["zkraceny_vypis_" . $i], $_POST["poradi_ceny_" . $i], $_POST["typ_ceny_" . $i], $zaklad_cena, $_POST["kapacita_bez_omezeni_" . $i], $_POST["use_pocet_noci_" . $i], $_POST["nazev_cena_en_" . $i], $_POST["kratky_nazev_en_" . $i], $_POST["typ_provize_" . $i], $_POST["vyse_provize_" . $i], $_POST["nevytvaret_" . $i]);
                    $i++;
                }
                $dotaz->finish_query();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location		
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {

                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "";
        } else if ($_GET["pozadavek"] == "kalkulacni_vzorce_deleteCM") { 
            $dotaz = new Cena_serial("kalkulacni_vzorce_deleteCM", $zamestnanec->get_id(), $_GET["id_serial"]);

            //pokud vse probehlo spravne, vypisu OK hlasku
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "&pozadavek=kalkulacni_vzorce_edit";
            
            if (!$dotaz->get_error_message()) {
                
               
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            
        } else if ($_GET["pozadavek"] == "kalkulacni_vzorce_update") {
            $dotaz = new Cena_serial("kalkulacni_vzorce_update", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_cena"]);

            //pokud vse probehlo spravne, vypisu OK hlasku
            
            if (!$dotaz->get_error_message()) {
                if($_POST["submit"]=="Ulo�it a zav��t"){
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "";
                }else if($_POST["submit"]=="Ulo�it a Generovat kombinovan� term�ny"){
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "&pozadavek=kalkulacni_vzorce_vygenerovat_terminy&typ_terminu=kombinovane";
                }else if($_POST["submit"]=="Ulo�it a Generovat pouze p��m� term�ny"){
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "&pozadavek=kalkulacni_vzorce_vygenerovat_terminy&typ_terminu=prime";                   
                }else{
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena&id_serial=" . $_GET["id_serial"] . "&pozadavek=kalkulacni_vzorce_edit";
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            
        } else if ($_GET["pozadavek"] == "kalkulacni_vzorce_create_zajezdy") {    
           $database = Database::get_instance();
            //print_r($_POST);
           $i=1;                      
           while($_POST["termin_od_$i"]!=""){
               $j=1;               
               while($_POST["id_cena_$j"]!=""){
                   $_POST["castka_$j"] = $_POST["cena_".$_POST["id_cena_$j"]."_".$i.""];
                   if($_POST["castka_$j"] !== ""){
                       $_POST["pouzit_cenu_$j"] = 1;
                   }else{
                       $_POST["pouzit_cenu_$j"] = 0;
                   }  
                   $j++;
               }
               $_POST["pocet"] = ($j-1);
               if($_POST["vytvorit_zajezd_$i"]=="1"){
                   $_POST["od"] = $_POST["termin_od_$i"];
                   $_POST["do"] = $_POST["termin_do_$i"];
                   $_POST["currentID"] = $i;
                   if($_POST["existujici_zajezd_$i"]!=""){
                       $dotaz = new Zajezd("update_dle_kv", $_GET["id_serial"], $_POST["existujici_zajezd_$i"], "", $_POST["termin_od_$i"], $_POST["termin_do_$i"], 0, "", "", 0, "", "", "", 1);
                   }else{
                       $dotaz = new Zajezd("create", $_GET["id_serial"], "", "", $_POST["termin_od_$i"], $_POST["termin_do_$i"], 0, "", "", 0, "", "", "", 1);
                   }                                             
               }
               $i++;
           }       
           //echo $i;
           
            //zaroven se zajezdem vytvarim take ceny zajezdu
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen	
            
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                //$adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message(). $dotaz->get_error_message();
            


            
         } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Cena_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_cena"]);

            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
           
        }

        /*--------------------- cena_zajezdu ---------------*/
    } else if ($_GET["typ"] == "cena_zajezd") {
        if ($_GET["pozadavek"] == "create") {
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen
            $dotaz = new Cena_zajezd("create", $_GET["id_serial"], $_GET["id_zajezd"], "", $_POST["pocet"]);
            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= $dotaz->get_pocet()) {
                    $dotaz->add_to_query($_POST["id_cena_" . $i], $_POST["castka_" . $i], $_POST["mena_" . $i], $_POST["castka_euro_" . $i],
                        $_POST["kapacita_volna_" . $i], $_POST["kapacita_celkova_" . $i],
                        $_POST["vyprodano_" . $i], $_POST["na_dotaz_" . $i], $_POST["pouzit_cenu_" . $i], $_POST["nezobrazovat_" . $i]);
                    $i++;
                }

                $dotaz->finish_query();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena_zajezd&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen
            $dotaz = new Cena_zajezd("update", $_GET["id_serial"], $_GET["id_zajezd"], "", $_POST["pocet"]);
            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= $_POST["pocet"]) {
                    $dotaz->add_to_query($_POST["id_cena_" . $i], $_POST["castka_" . $i], $_POST["mena_" . $i], $_POST["castka_euro_" . $i],
                        $_POST["kapacita_volna_" . $i], $_POST["kapacita_celkova_" . $i],
                        $_POST["vyprodano_" . $i], $_POST["na_dotaz_" . $i], $_POST["pouzit_cenu_" . $i], $_POST["nezobrazovat_" . $i]);
                    $i++;
                }

                $dotaz->finish_query();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
           // print_r($_POST);
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena_zajezd&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Cena_zajezd("delete", $_GET["id_serial"], $_GET["id_zajezd"], $_GET["id_cena"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=cena_zajezd&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "ajax_get_ceny") {
            //pro AJAX - pouze zobrazi zdrojak, kterym je treba prepsat cast u tvorby sluzeb
            $ceny_zajezd = new Cena_zajezd("new", $_GET["id_serial"], "");
            //echo $ceny_zajezd->get_error_message();	
            //echo $ceny_zajezd->show_submenu();	
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo iconv("cp1250", "UTF-8", $ceny_zajezd->show_form("new_zajezd", $ceny_zajezd->change_date_cz_en($_POST["termin_od"]), $ceny_zajezd->change_date_cz_en($_POST["termin_do"])));
            exit;
        }
        /*--------------------- zeme/destinace ---------------*/
    } else if ($_GET["typ"] == "zeme_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //je-li to treba, zaregistrujeme sessions
            //INFO: deprecated - nemelo by byt treba
//				if(!isset($_SESSION["zeme_order_by"])){
//					session_register("zeme_order_by");
//				}
            //kontrola vstupu je provadena pri volani konstruktoru t�idy zeme_list
            if ($_GET["pole"] == "ord_by") {
                $_SESSION["zeme_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zeme&id_serial=" . $_GET["id_serial"] . "";
        }
    } else if ($_GET["typ"] == "zeme") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Zeme_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zeme"], $_GET["zakladni_zeme"], "", $_GET["polozka_menu"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zeme&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "create_destinace") {
            $dotaz = new Zeme_serial("create_destinace", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zeme"], $_GET["zakladni_zeme"], $_GET["id_destinace"], $_GET["polozka_menu"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zeme&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Zeme_serial("update", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zeme"], $_GET["zakladni_zeme"], "", $_GET["polozka_menu"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zeme&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Zeme_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zeme"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zeme&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete_destinace") {
            $dotaz = new Zeme_serial("delete_destinace", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zeme"], "", $_GET["id_destinace"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zeme&id_serial=" . $_GET["id_serial"] . "";
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
            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "zeme-destinace-nazev") {
                $_SESSION["zeme"] = $id_zeme;
                $_SESSION["destinace"] = $id_destinace;
                $_SESSION["nazev_foto"] = $_POST["nazev_foto"];
                $_SESSION["foto_nepouzite"] = intval($_POST["foto_nepouzite"]);

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["foto_order_by"] = $_GET["ord_by"];
            }

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_serial=" . $_GET["id_serial"] . "";
        }
    } else if ($_GET["typ"] == "foto") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Foto_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_foto"], $_GET["zakladni_foto"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Foto_serial("update", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_foto"], $_GET["zakladni_foto"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Foto_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_foto"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

        /*--------------------- dokumenty ---------------*/
    } else if ($_GET["typ"] == "dokument_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru t�idy dokument_list
            //filtry menime bud formularem (nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_dokument"] = $_POST["nazev_dokument"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["dokument_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument&id_serial=" . $_GET["id_serial"] . "";
        }
    } else if ($_GET["typ"] == "dokument") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Dokument_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_dokument"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Dokument_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_dokument"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=dokument&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

        /*--------------------- informace ---------------*/
    } else if ($_GET["typ"] == "informace_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            ///rozdeleni pole zeme:destinace na id_zeme a id_destinace
            if ($_POST["zeme-destinace"] != "") {
                //vstup je ve tvaru zeme:destinace
                $typ_array = explode(":", $_POST["zeme-destinace"]);
                $id_zeme = $typ_array[0];
                $id_destinace = $typ_array[1];
            } else {
                $id_zeme = "";
                $id_destinace = "";
            }
            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "zeme-destinace-nazev") {
                $_SESSION["zeme"] = $id_zeme;
                $_SESSION["destinace"] = $id_destinace;
                $_SESSION["typ_informace"] = $_POST["typ_informace"];
                $_SESSION["nazev_informace"] = $_POST["nazev_informace"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["informace_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace&id_serial=" . $_GET["id_serial"] . "";
        }
    } else if ($_GET["typ"] == "informace") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Informace_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_informace"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Informace_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_informace"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=informace&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
        /*--------------------- objekty ---------------*/
    } else if ($_GET["typ"] == "objekty_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["objekt_nazev"] = $_POST["objekt_nazev"];
                $_SESSION["objekt_id_organizace"] = $_POST["objekt_id_organizace"];
                $_SESSION["objekt_typ"] = $_POST["objekt_typ"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["objekt_order_by"] = $_GET["objekt_order_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_objekty&id_serial=" . $_GET["id_serial"] . "";
        }
    } else if ($_GET["typ"] == "serial_objekty") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Objekty_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_objektu"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_objekty&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Objekty_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_objektu"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=serial_objekty&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
        /*--------------------- slevy ---------------*/
    } else if ($_GET["typ"] == "slevy_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_slevy"] = $_POST["nazev_slevy"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["slevy_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy&id_serial=" . $_GET["id_serial"] . "";
        }
    } else if ($_GET["typ"] == "slevy_list_zajezd") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_slevy"] = $_POST["nazev_slevy"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["slevy_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_zajezd&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "";
        }
    } else if ($_GET["typ"] == "slevy") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Slevy_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_slevy"], 1);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Slevy_serial("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_slevy"], 1);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy&id_serial=" . $_GET["id_serial"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

    } else if ($_GET["typ"] == "slevy_zajezd") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Slevy_zajezd("create", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zajezd"], $_GET["id_slevy"], 1);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_zajezd&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Slevy_zajezd("delete", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zajezd"], $_GET["id_slevy"], 1);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=slevy_zajezd&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

    } else if ($_GET["typ"] == "blackdays") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Blackdays($_POST["id_zajezd"], $_POST["od"], $_POST["do"], "create");
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            exit;
        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Blackdays("", $_POST["od"], $_POST["do"], "update", $_GET["id_blackdays"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            exit;

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Blackdays("", "", "", "delete", $_GET["id_blackdays"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?pozadavek=show&typ=blackdays&id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"];
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    } else if ($_GET["typ"] == "topologie_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru t�idy foto_list
            //filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["nazev_topologie"] = $_POST["nazev_topologie"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["topologie_order_by"] = $_GET["ord_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "&typ=topologie&pozadavek=show";
        }
    } else if ($_GET["typ"] == "topologie") {
        if ($_GET["pozadavek"] == "add_new") {
            
            //je t�eba vytvo�it nov� objekt s topologi�
            $zajezd = new Zajezd("show",$_GET["id_serial"],$_GET["id_zajezd"]);
            $serial = new Serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            $topologie = new Topologie("show", $zamestnanec->get_id(), $_GET["id_topologie"]);
            
            $_POST["nazev_objektu"] = "Doprava k z�jezdu ".$_GET["id_zajezd"];
            $_POST["kratky_nazev_objektu"] = "Doprava k z�jezdu ".$_GET["id_zajezd"];
            $_POST["typ_objektu"] = 2;
            $_POST["popis_objektu"] ="";
            $_POST["poznamka"] = "Automaticky vytvo�en� objekt - p�i�azov�n� topologie ".$topologie->get_nazev()." k seri�lu ".$serial->get_nazev()." a z�jezdu ".CommonUtils::czechDate($zajezd->get_od())." - ".CommonUtils::czechDate($zajezd->get_do())."";
            $i=1;
            $_POST["ok_nazev_kategorie_".$i] = $topologie->get_nazev();
            $_POST["ok_kratky_nazev_kategorie_".$i] = $topologie->get_nazev();
            $_POST["ok_cizi_nazev_kategorie_".$i] ="";
            $_POST["ok_zakladni_kategorie_".$i] = 1;
            $_POST["ok_hlavni_kapacita_".$i] = 1;
            $_POST["ok_vedlejsi_kapacita_".$i] = 0;
            $_POST["ok_jako_celek_".$i] = 0;
            $_POST["ok_poznamka_kategorie_".$i] = "Automaticky vytvo�en� OK";
            $_POST["ok_popis_kategorie_".$i] = "";
            
            $dotaz_objekt = new Objekty("create", $zamestnanec->get_id());
            $id_objektu = $dotaz_objekt->get_id();
            $id_objekt_kategorie = $dotaz_objekt->get_id_ok();
            //priradime objekt k serialu
            $obj_serial = new Objekty_serial("create", $zamestnanec->get_id(), $_GET["id_serial"], $id_objektu);
            
            $datum_od = CommonUtils::czechDate($zajezd->get_od());//TODO
            $datum_do = CommonUtils::czechDate($zajezd->get_do());//TODO
            //
            //nyn� mus�me vytvo�it TOK
            $id_termin = 0;
            $sql = "select max(`id_termin`) as `termin` from `objekt_kategorie_termin` where 1";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($row_termin = mysqli_fetch_array($data)) {
                $id_termin = intval($row_termin["termin"]);
                $id_termin++;
            }          
            if($dotaz_objekt->get_error_message()){
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_objekt->get_error_message();
            }
            $topologie_kapacita = $topologie->get_pocet_sedadel();      
            $nazev_tok = "Doprava: ".$serial->get_nazev().", ".CommonUtils::czechDate($zajezd->get_od())." - ".CommonUtils::czechDate($zajezd->get_do()).", ".$topologie->get_nazev();
            $dotaz = new Termin_objektove_kategorie("create", $zamestnanec->get_id(), $id_objektu, $id_termin, $datum_od, $datum_do, $nazev_tok);
            if (!$dotaz->get_error_message()) {
                $dotaz->add_to_query($id_termin, $id_objekt_kategorie, 0, $topologie_kapacita, 0, 0, 0, 1);
                $dotaz->finish_query();
            }
            if($dotaz->get_error_message()){
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            
            //a na zaver vytvorime radek tabulky id_tok_topologie
            $dotaz_tt = new TOK_topologie("create", $zamestnanec->get_id(), $id_objektu, $id_termin, $id_objekt_kategorie, $_GET["id_topologie"]);
            $id_tok_topologie = $dotaz_tt->get_id_tok_topologie();
            if($dotaz_tt->get_error_message()){
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_tt->get_error_message();
            }
            
            //priradim TOK topologii k seri�lu
            $dotaz = new Zajezd_topologie("create", $_GET["id_serial"], $_GET["id_zajezd"],  $id_tok_topologie);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "&typ=topologie&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
            
            //zkopirujeme sedadla
            $polozky_topologie = $topologie->get_polozky();
            $tt_polozky = new TOK_topologie("add_sedadla", $zamestnanec->get_id(), $id_objektu, $id_termin, $id_objekt_kategorie, $_GET["id_topologie"],$id_tok_topologie);
            foreach ($polozky_topologie as $key => $sedadlo) {
                $tt_polozky->add_to_query($sedadlo);
            }
            $tt_polozky->finish_query();
            
            if($_GET["return"]=="zajezd_list"){
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=zajezd_list&id_serial=" . $_GET["id_serial"] . "";
            }
            
        } else if ($_GET["pozadavek"] == "add_existing") {
            //priradim TOK topologii k seri�lu
            $dotaz = new Zajezd_topologie("create", $_GET["id_serial"], $_GET["id_zajezd"],   $_GET["id_tok_topologie"]);
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "&typ=topologie&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if($_GET["pozadavek"] == "delete_zasedaci_poradek") {
            $dotaz = new Zajezd_topologie("delete",  $_GET["id_serial"], $_GET["id_zajezd"],  $_GET["id_tok_topologie"]);           
            
            //vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location							
           // $adress = $_SERVER['SCRIPT_NAME'] . "?id_serial=" . $_GET["id_serial"] . "&id_zajezd=" . $_GET["id_zajezd"] . "&typ=topologie&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

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
    echo "<title>" . $core->show_nazev_modulu() . " | Administrace syst�mu RSCK</title>";
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
    <script type="text/javascript" src="./js/jQueryRotate.js"></script>
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
        $_GET["typ"] = "serial_list";
    }

    /*----------------	seznam seri�l� -----------*/
    if ($_GET["typ"] == "serial_list") {

        //pokud nemam strankovani, zacnu nazacatku:)
        if ($_GET["str"] == "") {
            $_GET["str"] = "0";
        }
        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=serial&amp;pozadavek=new">vytvo�it nov� seri�l</a>
        </div>
        <?
        //vytvorime instanci serial_list

        $serial_list = new Serial_list($_SESSION["serial_typ"], $_SESSION["serial_podtyp"], $_SESSION["serial_nazev"], $_SESSION["serial_zeme"], $_GET["str"], $_SESSION["serial_ord_by"], $_GET["moznosti_editace"]);
        //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
        echo $serial_list->get_error_message();
        //zobrazim filtry	
        echo $serial_list->show_filtr();

        if (!$serial_list->get_error_message()) {

            //nadpis seznamu
            echo $serial_list->show_header();
            //zobrazim hlavicku vypisu serialu		
            echo $serial_list->show_list_header();

            //vypis jednotlivych serialu
            while ($serial_list->get_next_radek()) {
                echo $serial_list->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($serial_list->getZacatek(), $serial_list->getPocetZajezdu(), $serial_list->getPocetZaznamu());
        }

        /*----------------	nov� seri�l -----------*/

    } else if ($_GET["typ"] == "serial" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

        ?>
        <div class="submenu">
            <a href="?typ=serial_list">&lt;&lt; seznam seri�l�</a>
        </div>

        <script>
            function otevrit(url) {
                win = window.open('' + url + '', '_blank', 'height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
            }

        </script>

        <?
        $serial = new Serial("new", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["nazev_web"], $_POST["popisek"], $_POST["popis"],
            $_POST["popis_ubytovani"], $_POST["popis_stravovani"], $_POST["popis_strediska"], $_POST["popis_lazni"], $_POST["program_zajezdu"],
            $_POST["cena_zahrnuje"], $_POST["cena_nezahrnuje"], $_POST["poznamky"],
            $id_typ, $id_podtyp, $_POST["strava"], $_POST["doprava"], $_POST["ubytovani"], $_POST["ubytovani_kategorie"], $_POST["dlouhodobe_zajezdy"], $_POST["highlights"], $_POST["jazyk"], $_POST["predregistrace"], $_POST["nezobrazovat"],
            $_POST["typ_provize"], $_POST["vyse_provize"], $_POST["id_smluvni_podminky"], $_POST["id_sml_podm"], $_POST["id_sablony_zobrazeni"], $_POST["id_sablony_objednavka"], $_GET["pozadavek"]);
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        echo $serial->get_error_message();
        ?><h3>Vytvo�it nov� seri�l</h3><?
        echo $serial->show_form();

    } else if ($_GET["id_serial"]) {
        //nejaky serial uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat	

        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=serial_list">&lt;&lt; seznam seri�l�</a>
            <a href="?typ=serial&amp;pozadavek=new">vytvo�it nov� seri�l</a>
            <br/>
            <?


            //podle typu pozadvku vytvorim instanci tridy serial - bud serial edituju, nebo pouze zobrazim menu serialu
            if ($_GET["typ"] == "serial" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                $serial = new Serial("edit", $zamestnanec->get_id(), $_GET["id_serial"], $_POST["nazev"], $_POST["nazev_web"], $_POST["popisek"], $_POST["popis"],
                    $_POST["popis_ubytovani"], $_POST["popis_stravovani"], $_POST["popis_strediska"], $_POST["popis_lazni"], $_POST["program_zajezdu"],
                    $_POST["cena_zahrnuje"], $_POST["cena_nezahrnuje"], $_POST["poznamky"],
                    $id_typ, $id_podtyp, $_POST["strava"], $_POST["doprava"], $_POST["ubytovani"], $_POST["ubytovani_kategorie"], $_POST["dlouhodobe_zajezdy"], $_POST["highlights"], $_POST["jazyk"], $_POST["predregistrace"], $_POST["nezobrazovat"],
                    $_POST["typ_provize"], $_POST["vyse_provize"], $_POST["id_smluvni_podminky"], $_POST["id_sml_podm"], $_POST["id_sablony_zobrazeni"], $_POST["id_sablony_objednavka"], $_GET["pozadavek"]);

                ?>
                <script>
                    function otevrit(url) {
                        win = window.open('' + url + '', '_blank', 'height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
                    }

                </script>

            <?
            } else {
                $serial = new Serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            }


            echo $serial->get_error_message();
            //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
            echo $serial->show_submenu();
            ?>
        </div>
        <?

        /*----------------	editace  seri�lu -----------*/
        if ($_GET["typ"] == "serial" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
            ?><h3>Editace seri�lu</h3><?
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $serial->show_form();

        } else if ($_GET["typ"] == "serial" and $_GET["pozadavek"] == "objednavky") {
            ?><h3>Objedn�vky seri�lu</h3><?
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $serial->show_objednavky();

            /*----------------	vytvo�en� cen seri�lu -----------*/
        } else if ($_GET["typ"] == "cena" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
            ?><h3>Vytvo�it slu�by k seri�lu</h3><?
            //seznam cen
            if ($_POST["pocet"]) {
                $_GET["pocet_cen"] = $_POST["pocet"];
            } else if ($_POST["pocet_cen"]) {
                $_GET["pocet_cen"] = $_POST["pocet_cen"];
            }
            $ceny = new Cena_serial("new", $zamestnanec->get_id(), $_GET["id_serial"], "", $_GET["pocet_cen"]);

            echo $ceny->get_error_message();
            //zobrazim menu pro editaci cen
            echo $ceny->show_submenu();

            //zobrazim formular pro editaci/vytvoreni cen
            echo $ceny->show_form();

            /*----------------	editace cen seri�lu -----------*/
        } else if ($_GET["typ"] == "cena" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
            ?><h3>Editace slu�eb seri�lu</h3><?
            //seznam cen
            $ceny = new Cena_serial("edit", $zamestnanec->get_id(), $_GET["id_serial"], "", $_POST["pocet"]);
            echo $ceny->get_error_message();
            //zobrazim menu pro editaci cen
            echo $ceny->show_submenu();

            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $ceny->show_form();
            /*----------------	zobrazen� cen seri�lu -----------*/
        } else if ($_GET["typ"] == "cena" and ($_GET["pozadavek"] == "kalkulacni_vzorce_edit" or $_GET["pozadavek"] == "kalkulacni_vzorce_update")) {
            ?><h3>Kalkula�n� vzorce u slu�eb</h3><?
            //seznam cen
            $ceny = new Cena_serial("kalkulacni_vzorce_edit", $zamestnanec->get_id(), $_GET["id_serial"], "", $_POST["pocet"]);
            echo $ceny->get_error_message();
            //zobrazim menu pro editaci cen
            echo $ceny->show_submenu();

            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $ceny->show_form_kalkulacni_vzorce();
            /*----------------	zobrazen� cen seri�lu -----------*/
        } else if ($_GET["typ"] == "cena" and ($_GET["pozadavek"] == "kalkulacni_vzorce_vygenerovat_terminy")) {
            ?><h3>Term�ny a ceny slu�eb vygenerovan� na z�klad� kalkula�n�ch vzorc�</h3><?
            //seznam cen
            $ceny = new Cena_serial("kalkulacni_vzorce_vygenerovat_terminy", $zamestnanec->get_id(), $_GET["id_serial"], "", $_POST["pocet"], $_GET["typ_terminu"]);
            echo $ceny->get_error_message();
            //zobrazim menu pro editaci cen
            echo $ceny->show_submenu();

            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $ceny->show_form_vygenerovane_terminy();            
            /*----------------	zobrazen� cen seri�lu -----------*/            
        } else if ($_GET["typ"] == "cena") {
            ?><h3>Seznam slu�eb seri�lu</h3><?
            //seznam cen
            $ceny = new Cena_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $ceny->get_error_message();
            //zobrazim menu pro editaci cen
            echo $ceny->show_submenu();

            echo $ceny->show_list_header();
            while ($ceny->get_next_radek()) {
                echo $ceny->show_list_item("tabulka");
            }
            ?>
            </table>
            <?

            /*----------------	editace  fotografi� -----------*/
        } else if ($_GET["typ"] == "foto") {
            /*
			u fotografii zobrazuju aktu�ln� p�ipojen� fotografie 
			a seznam fotografi�, kter� lze p�ipojit (str�nkovan� s filtry v�b�ru) 
		*/
            //seznam fotografii pripojenych k serialu
            $current_foto = new Foto_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $current_foto->get_error_message();
            ?>
            <h3>Fotografie p�i�azen� k seri�lu</h3>
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
            if ($_SESSION["zeme"] == "" and $_SESSION["destinace"] == "") {
                //defaultne nastaveny filtr na fotky pouze ze zeme serialu
                $foto_list = new Foto_list($zamestnanec->get_id(), $serial->get_id_zeme(), "", $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);
            } else {
                $foto_list = new Foto_list($zamestnanec->get_id(), $_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["nazev_foto"], $_GET["str"], $_SESSION["foto_order_by"]);

            }
            echo $foto_list->get_error_message();
            echo $foto_list->show_filtr();
            ?>
            <h3>Seznam fotografi�</h3>
            <?
            echo $foto_list->show_list_header();

            //zobrazeni jednotlivych zaznamu
            while ($foto_list->get_next_radek()) {
                echo $foto_list->show_list_item("tabulka_serial");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($foto_list->getZacatek(), $foto_list->getPocetZajezdu(), $foto_list->getPocetZaznamu());
            /*----------------	editace  dokument� -----------*/
        } else if ($_GET["typ"] == "dokument") {
            /*
			u dokumentu zobrazuju aktu�ln� p�ipojen� dokumenty 
			a seznam dokumentu, kter� lze p�ipojit (str�nkovan� s filtry v�b�ru) 
			*/
            //seznam dokumentu pripojenych k serialu
            $current_dokument = new Dokument_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $current_dokument->get_error_message();
            ?>
            <h3>Dokumenty p�i�azen� k seri�lu</h3>
            <?
            echo $current_dokument->show_list_header();
            while ($current_dokument->get_next_radek()) {
                echo $current_dokument->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            if ($_GET["str"] == "") {
                $_GET["str"] = 0;
            }
            //seznam dokumentu - parametry nazev_dokumentu, pocatek vypisu a pocet zaznamu(default. nastaveny)
            $dokument_list = new Dokument_list($zamestnanec->get_id(), $_SESSION["nazev_dokument"], $_GET["str"], $_SESSION["dokument_order_by"]);
            echo $dokument_list->get_error_message();
            //zobrazeni filtru pro vypis dokument�
            echo $dokument_list->show_filtr();
            ?>
            <h3>Seznam dokument�</h3>
            <?
            //zobrazeni hlavicky seznamu
            echo $dokument_list->show_list_header();
            //zobrazeni jednotlivych zaznamu
            while ($dokument_list->get_next_radek()) {
                echo $dokument_list->show_list_item("tabulka_serial");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($dokument_list->getZacatek(), $dokument_list->getPocetZajezdu(), $dokument_list->getPocetZaznamu());

            /*----------------	editace  zem�/destinac� -----------*/
        } else if ($_GET["typ"] == "zeme") {
            /*
			u zemi zobrazuju aktu�ln� p�ipojen� zeme a destinace
			a seznam zemi/destinaci, kter� lze p�ipojit  
		*/
            //seznam fotografii pripojenych k serialu
            $current_zeme = new Zeme_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $current_zeme->get_error_message();
            ?>
            <h3>Zem� a destinace p�i�azen� k seri�lu</h3>
            <?
            echo $current_zeme->show_list_header();
            echo $current_zeme->show_list("tabulka");
            ?>
            </table>
            <?
            //seznam zem� a destinac� - 
            $zeme_list = new Zeme_list($zamestnanec->get_id(), $_SESSION["zeme_order_by"]);
            echo $zeme_list->get_error_message();
            ?>
            <h3>Seznam zem� a destinac�</h3>
            <?
            //zobrazeni hlavicky seznamu
            echo $zeme_list->show_list_header();
            //zobrazeni seznamu
            echo $zeme_list->show_list("tabulka_serial");
            ?>
            </table>
        <?

        } else if ($_GET["typ"] == "informace") {
            /*
				u fotografii zobrazuju aktu�ln� p�ipojen� fotografie 
				a seznam fotografi�, kter� lze p�ipojit (str�nkovan� s filtry v�b�ru) 
			*/
            //seznam fotografii pripojenych k serialu
            $current_informace = new Informace_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $current_informace->get_error_message();
            ?>
            <h3>Informace p�i�azen� k seri�lu</h3>
            <?
            echo $current_informace->show_list_header();
            while ($current_informace->get_next_radek()) {
                echo $current_informace->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            if ($_GET["str"] == "") {
                $_GET["str"] = 0;
            }
            if ($_SESSION["zeme"] == "" and $_SESSION["destinace"] == "") {
                //defaultne nastaveny filtr na fotky pouze ze zeme serialu
                $informace_list = new Informace_list ($serial->get_id_zeme(), "", $_SESSION["typ_informace"], $_SESSION["nazev_informace"], $_GET["str"], $_SESSION["informace_order_by"]);
            } else {
                $informace_list = new Informace_list ($_SESSION["zeme"], $_SESSION["destinace"], $_SESSION["typ_informace"], $_SESSION["nazev_informace"], $_GET["str"], $_SESSION["informace_order_by"]);
            }

            echo $informace_list->get_error_message();
            //zobrazeni filtru pro vypis fotek
            echo $informace_list->show_filtr();
            ?>
            <h3>Seznam informac�</h3>
            <?
            echo $informace_list->show_list_header();

            //zobrazeni jednotlivych zaznamu
            while ($informace_list->get_next_radek()) {
                echo $informace_list->show_list_item("tabulka_serial");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($informace_list->getZacatek(), $informace_list->getPocetZajezdu(), $informace_list->getPocetZaznamu());

        } else if ($_GET["typ"] == "serial_objekty") {
            /*
				u fotografii zobrazuju aktu�ln� p�ipojen� fotografie 
				a seznam fotografi�, kter� lze p�ipojit (str�nkovan� s filtry v�b�ru) 
			*/
            //seznam fotografii pripojenych k serialu
            $current_objekty = new Objekty_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $current_objekty->get_error_message();
            ?>
            <h3>Objekty p�i�azen� k seri�lu</h3>
            <?
            echo $current_objekty->show_list_header();
            while ($current_objekty->get_next_radek()) {
                echo $current_objekty->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            if ($_GET["str"] == "") {
                $_GET["str"] = 0;
            }
            $objekty_list = new Objekty_list("show_serial", $_SESSION["objekt_nazev"], $_SESSION["objekt_id_organizace"], $_SESSION["objekt_typ"], $_GET["str"], $_SESSION["objekt_order_by"], $_GET["moznosti_editace"]);

            echo $objekty_list->get_error_message();
            //zobrazeni filtru pro vypis fotek
            echo $objekty_list->show_filtr();
            ?>
            <h3>Seznam objekt�</h3>
            <?
            echo $objekty_list->show_list_header();

            //zobrazeni jednotlivych zaznamu
            while ($objekty_list->get_next_radek()) {
                echo $objekty_list->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($objekty_list->getZacatek(), $objekty_list->getPocetZajezdu(), $objekty_list->getPocetZaznamu());

        } else if ($_GET["typ"] == "slevy") {
            /*
				u fotografii zobrazuju aktu�ln� p�ipojen� fotografie 
				a seznam fotografi�, kter� lze p�ipojit (str�nkovan� s filtry v�b�ru) 
			*/
            //seznam fotografii pripojenych k serialu
            $current_informace = new Slevy_serial("show", $zamestnanec->get_id(), $_GET["id_serial"]);
            echo $current_informace->get_error_message();
            ?>
            <h3>Slevy p�i�azen� k seri�lu</h3>
            <?
            echo $current_informace->show_list_header();
            while ($current_informace->get_next_radek()) {
                echo $current_informace->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            if ($_GET["str"] == "") {
                $_GET["str"] = 0;
            }
            //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
            $slevy_list = new Slevy_list ($zamestnanec->get_id(), $_SESSION["nazev_slevy"], $_GET["str"], $_SESSION["slevy_order_by"]);
            echo $slevy_list->get_error_message();
            //zobrazeni filtru pro vypis fotek
            echo $slevy_list->show_filtr();
            ?>
            <h3>Seznam slev</h3>
            <?
            echo $slevy_list->show_list_header();

            //zobrazeni jednotlivych zaznamu
            while ($slevy_list->get_next_radek()) {
                echo $slevy_list->show_list_item("tabulka_serial");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($slevy_list->getZacatek(), $slevy_list->getPocetZajezdu(), $slevy_list->getPocetZaznamu());


        } else if ($_GET["typ"] == "slevy_zajezd") {

            ?>
            <div class="submenu">
                <?
                echo "<a href=\"?id_serial=" . $_GET["id_serial"] . "&amp;typ=zajezd_list\">&lt;&lt; seznam z�jezd�</a><br/>";

                $zajezd = new Zajezd("show", $_GET["id_serial"], $_GET["id_zajezd"]);
                echo $zajezd->get_error_message();
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                echo $zajezd->show_submenu();
                ?>
            </div>
            <?

            //seznam fotografii pripojenych k serialu
            $slevy_zajezd = new Slevy_zajezd("show", $zamestnanec->get_id(), $_GET["id_serial"], $_GET["id_zajezd"]);
            echo $slevy_zajezd->get_error_message();
            ?>
            <h3>Slevy p�i�azen� k z�jezdu</h3>
            <?
            echo $slevy_zajezd->show_list_header();
            while ($slevy_zajezd->get_next_radek()) {
                echo $slevy_zajezd->show_list_item("tabulka");
            }
            ?>
            </table>
            <?
            if ($_GET["str"] == "") {
                $_GET["str"] = 0;
            }
            //seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
            $slevy_list = new Slevy_list ($zamestnanec->get_id(), $_SESSION["nazev_slevy"], $_GET["str"], $_SESSION["slevy_order_by"]);
            echo $slevy_list->get_error_message();
            //zobrazeni filtru pro vypis fotek
            echo $slevy_list->show_filtr();
            ?>
            <h3>Seznam slev</h3>
            <?
            echo $slevy_list->show_list_header();

            //zobrazeni jednotlivych zaznamu
            while ($slevy_list->get_next_radek()) {
                echo $slevy_list->show_list_item("tabulka_zajezd");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($slevy_list->getZacatek(), $slevy_list->getPocetZajezdu(), $slevy_list->getPocetZaznamu());

        } else if ($_GET["typ"] == "blackdays") {
            $blackdays = new Blackdays_list($_GET["id_zajezd"]);
            echo $blackdays->show_header();
            echo $blackdays->get_error_message();
            echo $blackdays->show_list_header();
            echo $blackdays->show_list();
        } else if ($_GET["typ"] == "zajezd_list") {
            /*vypis seznamu zajezdu daneho serialu
			zajezdu v serialu byva standartne max cca 20, takze strankovani ani filtrovani neni treba*/
            ?>
            <div class="submenu">
                <?
                $show_novy = true;
                $sql = "select `id_ridici_objekt`, `nazev_objektu`  from `serial` join `objekt` on (`serial`.`id_ridici_objekt` = `objekt`.`id_objektu`) where `id_serial`=" . $_GET["id_serial"] . " ";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row = mysqli_fetch_array($data)) {
                    echo "<br/><strong style=\"color:red;\">Tento seri�l je pod��zen objektu <a href=\"/admin/objekty.php?id_objektu=" . $row["id_ridici_objekt"] . "&typ=tok_list&pozadavek=show\">" . $row["nazev_objektu"] . "</a>. K vytvo�en� z�jezdu je t�eba vytvo�it nov� TOK na ��d�c�m objektu. </strong>";
                    $show_novy = false;
                }
                if ($show_novy) {
                    echo "<a href=\"?id_serial=" . $_GET["id_serial"] . "&amp;typ=zajezd&amp;pozadavek=new\">vytvo�it nov� zajezd</a>";
                }
                ?>
            </div>
            <?
            //vytvorime instanci zajezd_list
            $zajezd_list = new Zajezd_list($_GET["id_serial"], $_GET["moznosti_editace"]);
            //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
            echo $zajezd_list->get_error_message();
            //nadpis seznamu
            echo $zajezd_list->show_header();
            //hlavicka seznamu
            echo $zajezd_list->show_list_header();
            //vypis jednotlivych serialu
            while ($zajezd_list->get_next_radek()) {
                echo $zajezd_list->show_list_item("tabulka");
            }
            echo $zajezd_list->show_footer();
            /*----------------	nov� zajezd -----------*/
        } else if ($_GET["typ"] == "zajezd" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

            ?>
            <div class="submenu">
                <?
                echo "<a href=\"?id_serial=" . $_GET["id_serial"] . "&amp;typ=zajezd_list\">&lt;&lt; seznam z�jezd�</a>";
                ?>
            </div>
            <?
            $zajezd = new Zajezd("new", $_GET["id_serial"], "", $_POST["id_zapas"], $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], $_POST["cena_pred_akci"], $_POST["akcni_cena"], $_POST["popis_akce"], $_POST["provizni_koeficient"], $_GET["pozadavek"]);
            echo $zajezd->get_error_message();
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            ?><h3>Vytvo�it nov� z�jezd</h3><?
            echo $zajezd->show_form();

            //pokud mame konkretni zajezd, vypiseme submenu pro zajezdy
        } else if ($_GET["id_zajezd"]) {
            //vypisu menu
            ?>
            <div class="submenu">
                <?
                $show_novy = true;
                $sql = "select `id_ridici_objekt`, `nazev_objektu`  from `serial` join `objekt` on (`serial`.`id_ridici_objekt` = `objekt`.`id_objektu`) where `id_serial`=" . $_GET["id_serial"] . " ";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row = mysqli_fetch_array($data)) {
                    echo "
                                        <a href=\"?id_serial=" . $_GET["id_serial"] . "&amp;typ=zajezd_list\">&lt;&lt; seznam z�jezd�</a>
                                        <br/><strong style=\"color:red;\">Tento seri�l je pod��zen objektu <a href=\"/admin/objekty.php?id_objektu=" . $row["id_ridici_objekt"] . "&typ=tok_list&pozadavek=show\">" . $row["nazev_objektu"] . "</a>. K vytvo�en� z�jezdu je t�eba vytvo�it nov� TOK na ��d�c�m objektu. </strong>";
                    $show_novy = false;
                }
                if ($show_novy) {
                    echo "<a href=\"?id_serial=" . $_GET["id_serial"] . "&amp;typ=zajezd_list\">&lt;&lt; seznam z�jezd�</a>
						<a href=\"?id_serial=" . $_GET["id_serial"] . "&amp;typ=zajezd&amp;pozadavek=new\">vytvo�it nov� zajezd</a>";
                }
                ?>
                <br/>
                <?
                //podle typu pozadvku vytvorim instanci tridy serial
                if ($_GET["typ"] == "zajezd" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                    $zajezd = new Zajezd("edit", $_GET["id_serial"], $_GET["id_zajezd"], $_POST["id_zapas"], $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], $_POST["cena_pred_akci"], $_POST["akcni_cena"], $_POST["popis_akce"], $_POST["provizni_koeficient"], $_GET["pozadavek"]);
                } else {
                    $zajezd = new Zajezd("show", $_GET["id_serial"], $_GET["id_zajezd"]);

                }
                echo $zajezd->get_error_message();
                //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
                echo $zajezd->show_submenu();
                ?>
            </div>
            <?

            /*----------------	editace z�jezdu -----------*/
            if ($_GET["typ"] == "zajezd" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                ?><h3>Editace z�jezdu</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $zajezd->show_form();

                /*----------------	vytvo�en� cen zajezdu -----------*/
            } else if ($_GET["typ"] == "cena_zajezd" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
                ?><h3>Vytvo�it ceny z�jezdu</h3><?
                //seznam cen
                $ceny_zajezd = new Cena_zajezd("new", $_GET["id_serial"], $_GET["id_zajezd"]);
                echo $ceny_zajezd->get_error_message();
                //zobrazim formular pro editaci/vytvoreni cen
                echo $ceny_zajezd->show_form();

                /*----------------	editace cen seri�lu -----------*/
            } else if ($_GET["typ"] == "cena_zajezd" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
                //seznam cen
                $ceny_zajezd = new Cena_zajezd("edit", $_GET["id_serial"], $_GET["id_zajezd"]);
                echo $ceny_zajezd->get_error_message();
                echo $ceny_zajezd->show_submenu();
                ?><h3>Editace cen z�jezdu</h3><?
                //zobrazim formular pro editaci/vytvoreni noveho serialu
                echo $ceny_zajezd->show_form("", $ceny_zajezd->get_termin_od(), $ceny_zajezd->get_termin_do());

            } else if ($_GET["typ"] == "topologie" and ($_GET["pozadavek"] == "show")) {
                //seznam cen
                $existujici_topologie_zajezd = new Zajezd_topologie("show", $_GET["id_serial"], $_GET["id_zajezd"]);
                
                $existujici_topologie = new Zajezd_topologie("show_all",  $_GET["id_serial"], $_GET["id_zajezd"]);
                //TODO lep�� inicializace
                $topologie_list = new Topologie_list($_SESSION["topologie_nazev"], $_GET["str"], $_SESSION["topologie_order_by"]);
                
                ?><h3>Topologie p�i�azen� k z�jezdu</h3><?
                echo $existujici_topologie_zajezd->show_list_header("tabulka");
                while ($existujici_topologie_zajezd->get_next_radek()) {
                    echo $existujici_topologie_zajezd->show_list_item("tabulka");
                }
                echo "</table>";
                ?><h3>Vytvo�it Objekt z topologie a p�i�adit k z�jezdu</h3><?
                echo $topologie_list->show_filtr();
                echo $topologie_list->show_list_header();
                while ($topologie_list->get_next_radek()) {
                    echo $topologie_list->show_list_item("tabulka_zajezd");
                }
                echo "</table>";
                ?><h3>Pou��t existuj�c� objekt s topologi� (sd�len� kapacita autobusu pro v�ce z�jezd�)</h3><?
                echo $existujici_topologie->show_list_header("tabulka_pridat");
                while ($existujici_topologie->get_next_radek()) {
                    echo $existujici_topologie->show_list_item("tabulka_pridat");
                }
                echo "</table>";
                //zobrazim formular pro editaci/vytvoreni noveho serialu              
                /*----------------	zobrazen� cen seri�lu -----------*/
            } else if ($_GET["typ"] == "cena_zajezd") {
                //seznam cen
                $ceny_zajezd = new Cena_zajezd("show", $_GET["id_serial"], $_GET["id_zajezd"]);
                echo $ceny_zajezd->get_error_message();
                echo $ceny_zajezd->show_submenu();

                ?><h3>Ceny z�jezdu</h3><?

                echo $ceny_zajezd->show_list_header();
                while ($ceny_zajezd->get_next_radek()) {
                    echo $ceny_zajezd->show_list_item("tabulka");
                }
                ?>
                </table>
            <?
            }
        }
        //if($_GET["id_zajezd"])

    } //if($_GET["id_serial"])

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