<?php
/**    \file
 * rezervace.php  - seznam nových, prošlých a všech rezervací
 *                 - jejich editace -> zmìny stavu rezervací, editace plateb, služeb a osob
 *                 - tvorba nových rezervací
 * @param $typ = typ pozadavku
 * @param $pozadavek = upresneni pozadavku
 * @param $id_rezervace = id objednávky
 * @param $id_klient = id klienta
 * @param $id_zajezd = id zajezdu
 */
//spusteni prace se sessions
session_start();

//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

//global
require_once "../global/lib/utils/CommonUtils.php";
require_once "../global/lib/cfg/CommonConfig.php";

//note - v objednavce pouzita cast TS, kde je pouzit globalni model
require_once '../global/lib/model/entyties/ObjednavkaEnt.php';
require_once '../global/lib/model/entyties/SerialEnt.php';
require_once '../global/lib/model/entyties/ZajezdEnt.php';
require_once '../global/lib/model/entyties/SmluvniPodminkyNazevEnt.php';
require_once '../global/lib/model/entyties/SmluvniPodminkyEnt.php';
require_once '../global/lib/model/entyties/OrganizaceEnt.php';
require_once '../global/lib/model/entyties/AdresaEnt.php';
require_once '../global/lib/model/entyties/UserKlientEnt.php';
require_once '../global/lib/model/entyties/SluzbaEnt.php';
require_once '../global/lib/model/entyties/SlevaEnt.php';
require_once '../global/lib/model/entyties/FakturaEnt.php';
require_once '../global/lib/model/entyties/PlatbaEnt.php';
require_once '../global/lib/model/entyties/FotoEnt.php';
require_once "../global/lib/model/entyties/TerminObjektoveKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektovaKategorieEnt.php";
require_once "../global/lib/model/entyties/ObjektEnt.php";
require_once '../global/lib/model/holders/SmluvniPodminkyHolder.php';
require_once '../global/lib/model/holders/AdresaHolder.php';
require_once '../global/lib/model/holders/UserKlientHolder.php';
require_once '../global/lib/model/holders/SluzbaHolder.php';
require_once '../global/lib/model/holders/SlevaHolder.php';
require_once '../global/lib/model/holders/FakturaHolder.php';
require_once '../global/lib/model/holders/PlatbaHolder.php';
require_once "../global/lib/model/holders/TerminObjektoveKategorieHolder.php";
require_once "../global/lib/model/holders/ObjektovaKategorieHolder.php";
require_once '../global/lib/cfg/ViewConfig.php';
require_once '../global/lib/cfg/DatabaseConfig.php';
require_once '../global/lib/cfg/CommonConfig.php';
require_once '../global/lib/db/SQLQuery.php';
require_once '../global/lib/db/DatabaseProvider.php';
require_once '../global/lib/db/dao/ObjednavkyDAO.php';
require_once '../global/lib/db/dao/sql/ObjednavkySQLBuilder.php';
ObjednavkyDAO::init();

require_once "./classes/zeme_list.inc.php"; //seznamy klientù
require_once "./classes/typ_serialu_list.inc.php"; //seznamy klientù
require_once "./classes/klient_list.inc.php"; //seznamy klientù
require_once "./classes/klient.inc.php"; //detail klientù

require_once "./classes/rezervace_list.inc.php"; //seznamy rezervací
require_once "./classes/rezervace.inc.php"; //detail rezervací
require_once "./classes/rezervace_cena.inc.php"; //detail rezervací
require_once "./classes/rezervace_osoba.inc.php"; //detail rezervací
require_once "./classes/rezervace_platba_list.inc.php"; //seznamy plateb rezervací
require_once "./classes/rezervace_platba.inc.php"; //detail plateb rezervací
require_once "./classes/rezervace_sleva.inc.php"; //detail plateb rezervací
require_once "../classes/rezervace_zobrazit.inc.php"; //detail plateb rezervací
require_once "../classes/pdf_objednavka_prepare.inc.php"; //detail plateb rezervací
require_once "../classes/pdf_objednavka_prepare_vstupenka.inc.php"; //detail plateb rezervací
require_once "./classes/faktury_list.inc.php"; //detail plateb rezervací

require_once "./classes/vouchery/VoucheryModel.php";
require_once "./classes/vouchery/VoucheryModelConfig.php";
require_once "./classes/serial_list.inc.php"; //seznamy rezervací
require_once "./classes/zajezd_list.inc.php"; //seznamy rezervací
require_once "./classes/organizace_list.inc.php";
require_once "./classes/zajezd_topologie.inc.php";

require_once "./classes/ts/objednavka_displayer.inc.php";
require_once "./classes/ts/objednavka_dao.inc.php";
require_once "./classes/dataContainers/tsObjednavajici.php";
require_once "./classes/dataContainers/tsObjednavka.php";
require_once "./classes/dataContainers/tsPlatba.php";
require_once "./classes/dataContainers/tsProvize.php";
require_once "./classes/dataContainers/tsSluzba.php";
require_once "./classes/dataContainers/tsStaticDescription.php";
require_once "./classes/dataContainers/tsOsoba.php";
require_once "./classes/dataContainers/tsZajezd.php";
require_once "./classes/dataContainers/tsSmluvniPodminky.php";
require_once "./classes/dataContainers/tsObjektovaKategorie.php";
require_once "./classes/dataContainers/tsProdejce.php";
require_once "./classes/dataContainers/tsSleva.php";
require_once "./classes/dataContainers/tsOrganizace.php";
require_once "./classes/dataContainers/tsAdresa.php";

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

/* --------------	POZADAVKY DO DATABAZE	------------------------- */
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if ($zamestnanec->get_correct_login()) {
//obslouzim pozadavky do databaze - s automatickym reloadem stranky
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)
    $adress = "";
    /* ---------------------serial_list --------------- */
//bokem chceme zpracovat zmenu filtrovani na seznamu serialu
    if ($_GET["typ"] == "serial_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //rozdeleni pole typ:podtyp na id_typ a id_podtyp
            if ($_POST["typ-podtyp"] != "") {
                //vstup je ve tvaru typ:podtyp
                $typ_array = explode(":", $_POST["typ-podtyp"]);
                $id_typ = $typ_array[0];
                $id_podtyp = $typ_array[1];
            } else {
                $id_typ = "";
                $id_podtyp = "";
            }
            //kontrola vstupu je provadena pri volani konstruktoru tøidy serial_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "typ-podtyp-nazev") {
                $_SESSION["serial_typ"] = $id_typ;
                $_SESSION["serial_podtyp"] = $id_podtyp;
                $_SESSION["serial_nazev"] = $_POST["nazev"];
                $_SESSION["serial_zeme"] = $_POST["zeme"];

            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["serial_ord_by"] = $_GET["ord_by"];
            }
            $_GET["typ"] = "rezervace";
            $_GET["pozadavek"] = "update_stav";
            $_POST["typ_zmeny"] = "zmenit_serial";
            $_POST["submit_zmena"] = "Pokraèovat";
        }
    }

    // print_r($_POST);
    // print_r($_GET);
    if ($_GET["typ"] == "rezervace_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "massDelete") {
           $idsArray = explode(",", $_POST["listOfIDs"]);
        
        
           foreach($idsArray as $objID){
              $objID = intval($objID);
              echo "objednavka:".$objID;
              $dotaz = new Rezervace("delete", $zamestnanec->get_id(), $objID);
              //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
              
           
           }
           $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru tøidy klient_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "post_fields") {
                //rozdeleni pole typ:podtyp na id_typ a id_podtyp
                if ($_POST["typ-podtyp"] != "") {
                    //vstup je ve tvaru typ:podtyp
                    $typ_array = explode(":", $_POST["typ-podtyp"]);
                    $id_typ = $typ_array[0];
                    $id_podtyp = $typ_array[1];
                } else {
                    $id_typ = "";
                    $id_podtyp = "";
                }
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

                $_SESSION["rezervace_typ"] = $id_typ;
                $_SESSION["rezervace_podtyp"] = $id_podtyp;
                $_SESSION["rezervace_zeme"] = $id_zeme;
                $_SESSION["rezervace_destinace"] = $id_destinace;
                $_SESSION["rezervace_nazev_serialu"] = $_POST["rezervace_nazev_serialu"];
                $_SESSION["rezervace_datum_od"] = $_POST["rezervace_datum_od"];
                $_SESSION["rezervace_datum_do"] = $_POST["rezervace_datum_do"];
                $_SESSION["rezervace_datum_pobyt_od"] = $_POST["rezervace_datum_pobyt_od"];
                $_SESSION["rezervace_datum_pobyt_do"] = $_POST["rezervace_datum_pobyt_do"];
                $_SESSION["pouze_aktualni"] = $_POST["pouze_aktualni"];
                $_SESSION["rezervace_jmeno_klienta"] = $_POST["rezervace_jmeno"];
                $_SESSION["rezervace_prijmeni_klienta"] = $_POST["rezervace_prijmeni"];
                $_SESSION["rezervace_stav"] = $_POST["rezervace_stav"];
                $_SESSION["rezervace_id_objednavky"] = $_POST["id_objednavky"];
                $_SESSION["rezervace_ca_x_klient"] = $_POST["rezervace_ca_klient"];
                $_SESSION["rezervace_provize"] = $_POST["provize_filtr"];
                $_SESSION["rezervace_organizace"] = $_POST["rezervace_organizace"];
                $_SESSION["rezervace_nove_prosle_vse"] = $_POST["rezervace_nove_prosle"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["rezervace_order_by"] = $_GET["rezervace_order_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace_list";
        }
    } else if ($_GET["typ"] == "rezervace_platba_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            if ($_GET["pole"] == "ord_by") {
                $_SESSION["rezervace_platba_order_by"] = $_GET["rezervace_platba_order_by"];
            }
        }
        /* ---------------------serial--------------- */
    } else if ($_GET["typ"] == "rezervace") {
        if ($_GET["pozadavek"] == "ajax_provize") {

            $provize = Rezervace::calculateProvizeWrapper();
            echo $provize;
            exit;
        }
        if ($_GET["pozadavek"] == "new") {


            if ($_GET["clear"] == "1") {
                //vyprazdnim sessions
                $_SESSION["rezervace_id_serial"] = "";
                $_SESSION["rezervace_id_zajezd"] = "";
                $_SESSION["rezervace_id_klient"] = "";
            }

            if ($_GET["id_serial"] != "") {
                $_SESSION["rezervace_id_serial"] = $_GET["id_serial"];
            }
            if ($_GET["id_zajezd"] != "") {
                $_SESSION["rezervace_id_zajezd"] = $_GET["id_zajezd"];
            }
            if ($_GET["id_klient"] != "") {
                $_SESSION["rezervace_id_klient"] = $_GET["id_klient"];
            }

            $core = Core::get_instance();

            //presmeruju na vyber objektu, ktery jeste nemam
            if ($_SESSION["rezervace_id_serial"] == "") {
                $adresa_serial = $core->get_adress_modul_from_typ("serial");
                if ($adresa_serial !== false) {
                    $adress = $adresa_serial . "?typ=serial_list&moznosti_editace=select_serial_objednavky";
                } else {
                    $this->chyba("Nemáte oprávnìní k modulu seriál!");
                }
            } else if ($_SESSION["rezervace_id_zajezd"] == "") {
                $adresa_serial = $core->get_adress_modul_from_typ("serial");
                if ($adresa_serial !== false) {
                    $adress = $adresa_serial . "?typ=zajezd_list&moznosti_editace=select_zajezd_objednavky&id_serial=" . $_SESSION["rezervace_id_serial"];
                } else {
                    $this->chyba("Nemáte oprávnìní k modulu seriál!");
                }
            } else if ($_SESSION["rezervace_id_klient"] == "") {
                $adresa_klienti = $core->get_adress_modul_from_typ("klienti");
                if ($adresa_serial !== false) {
                    $adress = $adresa_klienti . "?typ=klient_list&moznosti_editace=select_klient_objednavky";
                } else {
                    $this->chyba("Nemáte oprávnìní k modulu klienti!");
                }
            }
        } else if ($_GET["pozadavek"] == "create") {
            print_r($_REQUEST);
            //insert do tabulky seriálù
            $dotaz = new Rezervace("create_new", $zamestnanec->get_id(), "", $_POST["id_objednavajici"], $_POST["id_serial"], $_POST["id_zajezd"],
                $_POST["rezervace_do"], $_POST["stav"], $_POST["pocet_osob"], $_POST["celkova_cena"], $_POST["poznamky"], $_POST["termin_od"], $_POST["termin_do"], $_POST["pocet_noci"], $_POST["nazev_slevy"], $_POST["castka_slevy"], $_POST["velikost_slevy"], $_POST["nazevprovize"], $_POST["sdphprovize"], $_POST["sumaprovize"]);
            $id_objednavka = $dotaz->get_id();

            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                if ($_POST["submit"] == "Uložit") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=$id_objednavka&typ=rezervace&pozadavek=show";
                } else {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace_list";
                }

                //vyprazdnim sessions
                $_SESSION["rezervace_id_serial"] = "";
                $_SESSION["rezervace_id_zajezd"] = "";
                $_SESSION["rezervace_id_klient"] = "";
                //potvrzovaci hlaska
                $data_objednavka = Rezervace::get_objednavka_info($id_objednavka);
                $objednavka = new Pdf_objednavka_prepare($id_objednavka, $data_objednavka["security_code"]);

                $objednavka->create_pdf_objednavka();
                $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                $platby_celkem = $objednavka->get_splacene_platby_celkem();
                //provedeme prepocet ceny sluzeb
                Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $id_objednavka);

                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "create_old") {

            //insert do tabulky seriálù
            $dotaz = new Rezervace("create", $zamestnanec->get_id(), "", $_POST["id_klient"], $_POST["id_serial"], $_POST["id_zajezd"],
                $_POST["rezervace_do"], $_POST["stav"], $_POST["pocet_osob"], $_POST["celkova_cena"], $_POST["poznamky"], $_POST["termin_od"], $_POST["termin_do"], $_POST["pocet_noci"], $_POST["nazev_slevy"], $_POST["castka_slevy"], $_POST["velikost_slevy"], $_POST["nazevprovize"], $_POST["sdphprovize"], $_POST["sumaprovize"]);
            $id_objednavka = $dotaz->get_id();

            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace_list";
                //vyprazdnim sessions
                $_SESSION["rezervace_id_serial"] = "";
                $_SESSION["rezervace_id_zajezd"] = "";
                $_SESSION["rezervace_id_klient"] = "";
                //potvrzovaci hlaska
                $data_objednavka = Rezervace::get_objednavka_info($id_objednavka);
                $objednavka = new Pdf_objednavka_prepare($id_objednavka, $data_objednavka["security_code"]);

                $objednavka->create_pdf_objednavka();
                $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                $platby_celkem = $objednavka->get_splacene_platby_celkem();
                //provedeme prepocet ceny sluzeb
                Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $id_objednavka);

                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "update_stav") {
            if ($_POST["submit_zmena"] != "Pokraèovat") {
                $dotaz = new Rezervace("update", $zamestnanec->get_id(), $_GET["id_objednavka"], $_POST["id_klient"], $_POST["id_serial"], $_POST["id_zajezd"],
                    $_POST["rezervace_do"], $_POST["stav"], $_POST["pocet_osob"], $_POST["celkova_cena"], $_POST["poznamky"], $_POST["termin_od"], $_POST["termin_do"], $_POST["pocet_noci"], "", "", "", $_POST["nazev_provize"], $_POST["sdph_provize"], $_POST["suma_provize"], $_POST["storno_poplatek"]);
                if (!$dotaz->get_error_message()) {

                    //update celkove ceny
                    $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                    $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);
                    $objednavka->create_pdf_objednavka();
                    $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                    $platby_celkem = $objednavka->get_splacene_platby_celkem();
                    //provedeme prepocet ceny sluzeb                
                    Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);
                    //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
                    //print_r($dotaz);
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
                } else {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
                }
            }
        } else if ($_GET["pozadavek"] == "change_zajezd") {
            //echo "inside change zajezd";
            //print_r($_SESSION);
            //print_r($_POST);
            $dotaz = new Rezervace("change_zajezd", $zamestnanec->get_id(), $_GET["id_objednavka"], $_GET["id_klient"], $_GET["id_serial"], $_GET["id_zajezd"]);
            if (!$dotaz->get_error_message()) {
                $dotaz->change_zajezd();
                //update celkove ceny
                $stare_sluzby = $dotaz->get_stare_sluzby();
                // print_r($stare_sluzby);
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "edit_ceny") {
            //stornuj nektere sluzby
            $dotaz = new Rezervace_cena("edit_ceny", $zamestnanec->get_id(), $_GET["id_objednavka"], "", "", "", $_POST["pocet_cen"], "", "", 0, $_REQUEST["id_cena_storno"], 0, $_REQUEST["pocet_cena_storno"], 0);

            if (!$dotaz->get_error_message()) {
                $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);

                $objednavka->create_pdf_objednavka();
                $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                $platby_celkem = $objednavka->get_splacene_platby_celkem();
                //provedeme prepocet ceny sluzeb
                Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);

                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "update_ceny") {
            $dotaz = new Rezervace_cena("update", $zamestnanec->get_id(), $_GET["id_objednavka"], "", "", "", $_POST["pocet_cen"]);
            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= $dotaz->get_pocet()) {
                    $dotaz->add_to_query($_POST["id_cena_" . $i], $_POST["pocet_" . $i], $_POST["castka_" . $i], $_POST["mena_" . $i], $_POST["use_pocet_noci_" . $i]);
                    $i++;
                }
            }
            $dotaz->finish_query();
            
            //update termin od a do
            if($_POST["upresneni_termin_od"]!=""){
                $dotaz2 = new Rezervace("update_terminy", $zamestnanec->get_id(), $_GET["id_objednavka"], "", "", "",
                     "", "", "", "", "", $_POST["upresneni_termin_od"], $_POST["upresneni_termin_do"], "", "", "", "", "", "", "", "");
            }
            if (!$dotaz->get_error_message()) {
                $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);

                $objednavka->create_pdf_objednavka();
                $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                $platby_celkem = $objednavka->get_splacene_platby_celkem();
                //provedeme prepocet ceny sluzeb
                Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);

                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "edit_ceny2") {
            $rezervace_cena = new Rezervace_cena("edit_ceny2", $zamestnanec->get_id(), $_GET["id_objednavka"], "", "", "", "", "", "", 0, 0, $_REQUEST["id_cena2_storno"], 0, $_REQUEST["pocet_cena2_storno"], 0);

            //update celkove ceny
            $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
            $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);

            $objednavka->create_pdf_objednavka();
            $celkova_cena_sluzby = $objednavka->get_celkova_cena();
            $platby_celkem = $objednavka->get_splacene_platby_celkem();
            //provedeme prepocet ceny sluzeb
            Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Rezervace("delete", $zamestnanec->get_id(), $_GET["id_objednavka"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "add_agentura" and intval($_GET["id_organizace"]) > 0 and intval($_GET["id_objednavka"]) > 0) {

            $dotaz = new Rezervace("add_agentura", $zamestnanec->get_id(), $_GET["id_objednavka"]);

            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "delete_agentura" and intval($_GET["id_objednavka"]) > 0) {

            $dotaz = new Rezervace("delete_agentura", $zamestnanec->get_id(), $_GET["id_objednavka"]);

            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    } else if ($_GET["typ"] == "rezervace_platba") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Rezervace_platba("create", $zamestnanec->get_id(), "", $_GET["id_objednavka"],
                $_POST["castka"], $_POST["vystaveno"], $_POST["splatit_do"], $_POST["splaceno"], $_POST["cislo_dokladu"], $_POST["zpusob_uhrady"]);
//upravit stavy objednavky dle zmen v platbach

            if (!$dotaz->get_error_message() && (is_null($dotaz2) || !$dotaz2->get_error_message())) {
//update celkove ceny
                    $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                    $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);
                    $objednavka->create_pdf_objednavka();
                    $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                    $platby_celkem = $objednavka->get_splacene_platby_celkem();
                    //provedeme prepocet ceny sluzeb                
                    Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);                
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace&pozadavek=show&id_objednavka=" . $_GET["id_objednavka"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Rezervace_platba("update", $zamestnanec->get_id(), $_GET["id_platba"], $_GET["id_objednavka"], "",
                $_POST["vystaveno"], $_POST["splatit_do"], $_POST["splaceno"], $_POST["cislo_dokladu"], $_POST["zpusob_uhrady"]);
            if (!$dotaz->get_error_message()) {
//update celkove ceny
                    $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                    $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);
                    $objednavka->create_pdf_objednavka();
                    $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                    $platby_celkem = $objednavka->get_splacene_platby_celkem();
                    //provedeme prepocet ceny sluzeb                
                    Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);                
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace&pozadavek=show&id_objednavka=" . $_GET["id_objednavka"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Rezervace_platba("delete", $zamestnanec->get_id(), $_GET["id_platba"], $_GET["id_objednavka"]);

            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace&pozadavek=show&id_objednavka=" . $_GET["id_objednavka"] . "";
            if (!$dotaz->get_error_message()) {
//update celkove ceny
                    $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                    $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);
                    $objednavka->create_pdf_objednavka();
                    $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                    $platby_celkem = $objednavka->get_splacene_platby_celkem();
                    //provedeme prepocet ceny sluzeb                
                    Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);                
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    } else if ($_GET["typ"] == "rezervace_sleva") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Rezervace_sleva("create", $zamestnanec->get_id(), $_GET["id_slevy"], $_GET["id_objednavka"], $_POST["nazev_slevy"], $_POST["castka"], $_POST["mena"], $_POST["old_nazev_slevy"], $_POST["old_velikost_slevy"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Rezervace_sleva("update", $zamestnanec->get_id(), "", $_GET["id_objednavka"], $_POST["nazev_slevy"], $_POST["castka"], $_POST["mena"], $_POST["old_nazev_slevy"], $_POST["old_velikost_slevy"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Rezervace_sleva("delete", $zamestnanec->get_id(), "", $_GET["id_objednavka"], "", "", "", $_POST["old_nazev_slevy"], $_POST["old_velikost_slevy"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
        }
        //print_r($_POST);
        //print_r($_GET);
        if ($_GET["pozadavek"] == "create" or $_GET["pozadavek"] == "update" or $_GET["pozadavek"] == "delete") {
            if (!$dotaz->get_error_message()) {
                $data_objednavka = Rezervace::get_objednavka_info($_GET["id_objednavka"]);
                $objednavka = new Pdf_objednavka_prepare($_GET["id_objednavka"], $data_objednavka["security_code"]);

                $objednavka->create_pdf_objednavka();
                $celkova_cena_sluzby = $objednavka->get_celkova_cena();
                $platby_celkem = $objednavka->get_splacene_platby_celkem();
                //provedeme prepocet ceny sluzeb
                Rezervace::update_cena($celkova_cena_sluzby, $data_objednavka["storno_poplatek"], $platby_celkem, $_GET["id_objednavka"]);

                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?id_objednavka=" . $_GET["id_objednavka"] . "&typ=rezervace&pozadavek=show";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }


    } else if ($_GET["typ"] == "klient_list") {
        if ($_GET["pozadavek"] == "change_filter") {

            //kontrola vstupu je provadena pri volani konstruktoru tøidy klient_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "jmeno_prijmeni_datum") {
                $_SESSION["klient_jmeno"] = $_POST["klient_jmeno"];
                $_SESSION["klient_prijmeni"] = $_POST["klient_prijmeni"];
                $_SESSION["klient_datum_narozeni"] = $_POST["klient_datum_narozeni"];
                if ($_POST["klient_ajax"] == "true") {
                    $klient_list = new Klient_list("show_all_ajax", $_SESSION["klient_jmeno"], $_SESSION["klient_prijmeni"], $_SESSION["klient_datum_narozeni"], "", $_GET["str"], $_SESSION["klient_order_by"], $_GET["moznosti_editace"]);
                    while ($klient_list->get_next_radek()) {
                        echo $klient_list->show_list_item("klient_ajax");
                    }
                    exit;
                } else if ($_POST["klient_ajax_uzivatele"] == "true") {
                    $klient_list = new Klient_list("show_all_ajax", $_SESSION["klient_jmeno"], $_SESSION["klient_prijmeni"], $_SESSION["klient_datum_narozeni"], "", $_GET["str"], $_SESSION["klient_order_by"], $_GET["moznosti_editace"]);
                    while ($klient_list->get_next_radek()) {
                        echo $klient_list->show_list_item("klient_ajax_uzivatele");
                    }
                    exit;
                }
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["klient_order_by"] = $_GET["klient_order_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace_osoby&id_objednavka=" . $_GET["id_objednavka"] . "";
        }
    } else if ($_GET["typ"] == "rezervace_osoby") {
        if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Rezervace_osoba("create", $zamestnanec->get_id(), $_GET["id_objednavka"], $_GET["id_klient"]);

            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace&pozadavek=show&id_objednavka=" . $_GET["id_objednavka"] . "";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Rezervace_osoba("delete", $zamestnanec->get_id(), $_GET["id_objednavka"], $_GET["id_klient"]);

            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=rezervace&pozadavek=show&id_objednavka=" . $_GET["id_objednavka"] . "";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }
    }


    if ($_GET["typ"] != "rezervace_list" and $_GET["id_objednavka"] > 0) {
        //zmena stavu objednavky (zaloha/prodano) podle toho kolik zbyva doplatit
        //muzu provadet obecne, je ale treba jeste kontrolovat platby
        $rezervace = new Rezervace("show", $zamestnanec->get_id(), $_GET["id_objednavka"]);
        $dotaz2 = null;
        if ($rezervace->get_stav() != Rezervace_library::$STAV_STORNO && $rezervace->get_stav() != Rezervace_library::$STAV_PREDBEZNA_POPTAVKA && $rezervace->get_stav() != Rezervace_library::$STAV_ODBAVENO
            && ($rezervace->get_celkova_cena() - $rezervace->get_zbyva_zaplatit()) != 0
        ) {
            if ($rezervace->get_zbyva_zaplatit() > 0) { //zaloha
                $dotaz2 = new Rezervace("edit_stav", $zamestnanec->get_id(), $_GET["id_objednavka"], "", "", "", "", Rezervace_library::$STAV_ZALOHA);
            } else { //zaplaceno
                $dotaz2 = new Rezervace("edit_stav", $zamestnanec->get_id(), $_GET["id_objednavka"], "", "", "", "", Rezervace_library::$STAV_PRODANO);
            }
        }
    }

    if (isset($_REQUEST["ajax"])) {
        if ($_REQUEST["ajax"] == "serialy") {
            header('Content-Type: application/json; charset=windows-1250');
            $serialList = new Serial_list(null, null, null, null, null, "nazev_up", "", POCET_ZAZNAMU, "selector");
            echo $serialList->printJson();
        } else if ($_REQUEST["ajax"] == "zajezdy") {
            header('Content-Type: application/json; charset=windows-1250');
            $zajezdList = new Zajezd_list($_GET["serial_id"], null, "show");
            echo $zajezdList->printJson($_REQUEST["oldZajezdy"] != "");
        } else if ($_REQUEST["ajax"] == "agentury") {
            header('Content-Type: application/json; charset=windows-1250');
            $organizaceList = new Organizace_list("show_agentury", null, null, null, null, null, null, 10000);
            echo $organizaceList->printJson();
        } else if ($_REQUEST["ajax"] == "organizace") {
            header('Content-Type: application/json; charset=windows-1250');
            $organizaceList = new Organizace_list("show_all", null, null, null, null, null, null, 10000);
            echo $organizaceList->printJson();            
        } else if ($_REQUEST["ajax"] == "osoby") {
            header('Content-Type: application/json; charset=windows-1250'); //nevim proc, ale mel jsem tu utf-8, kdyby blblo kodovani, je to tim (MJ)
            $osobyList = new Klient_list("show_no_limit_ajax", null, null, null, null, null, null, null);
            echo $osobyList->printJson();
        }
        exit();
    }
}

//vycistim filtr, pokud byl pozadavek zaregistrovan
if($_GET["filter"] == "clear") {
    unset($_SESSION["rezervace_typ"]);
    unset($_SESSION["rezervace_podtyp"]);
    unset($_SESSION["rezervace_zeme"]);
    unset($_SESSION["rezervace_destinace"]);
    unset($_SESSION["rezervace_nazev_serialu"]);
    unset($_SESSION["rezervace_datum_od"]);
    unset($_SESSION["rezervace_datum_do"]);
    unset($_SESSION["rezervace_datum_pobyt_od"]);
    unset($_SESSION["rezervace_datum_pobyt_do"]);
    unset($_SESSION["pouze_aktualni"]);
    unset($_SESSION["rezervace_jmeno_klienta"]);
    unset($_SESSION["rezervace_prijmeni_klienta"]);
    unset($_SESSION["rezervace_stav"]);
    unset($_SESSION["rezervace_id_objednavky"]);
    unset($_SESSION["rezervace_ca_x_klient"]);
    unset($_SESSION["rezervace_provize"]);
    unset($_SESSION["rezervace_nove_prosle_vse"]);
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
    <link type="text/css" href="./css/jquery-ui.min.css" rel="stylesheet" />
      
    <script type="text/javascript" src="./js/jquery-min.js"></script>
    <script type="text/javascript" src="./js/jquery-ui-cze.min.js"></script>
    <script type="text/javascript" src="./js/handlebars-v2.0.0.js"></script>
    <script type="text/javascript" src="./js/typeahead.bundle.js"></script>
    <script type="text/javascript" src="./js/common_functions.js"></script>
    <script type="text/javascript" src="./js/objednavky.js"></script>
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
    <?php
    //vypisu pripadne hlasky o uspechu operaci
    echo $hlaska_k_vypsani;

    //na zacatku zobrazim seznam
    if ($_GET["typ"] == "") {
        $_GET["typ"] = "rezervace_list";
    }

    /* ----------------	seznam seriálù ----------- */
    if ($_GET["typ"] == "rezervace_list" and ($_GET["id_klient"] != "" or $_GET["id_serial"] != "" or $_GET["id_zajezd"] != "")) {

        //pokud nemam strankovani, zacnu nazacatku:)
        if ($_GET["str"] == "") {
            $_GET["str"] = "0";
        }
        //vypisu menu

        /* zobrazení objednávek konkretniho klienta */

        //---------------------------nové rezervace---------------------------
        $nove_rezervace = new Rezervace_list("show_all", $_SESSION["rezervace_typ"], $_SESSION["rezervace_podtyp"], $_SESSION["rezervace_zeme"],
            $_SESSION["rezervace_destinace"], $_SESSION["rezervace_nazev_serialu"], $_SESSION["rezervace_datum_od"], $_SESSION["rezervace_datum_do"],
            $_SESSION["rezervace_datum_pobyt_od"], $_SESSION["rezervace_datum_pobyt_do"], $_SESSION["pouze_aktualni"],
            $_SESSION["rezervace_jmeno_klienta"], $_SESSION["rezervace_prijmeni_klienta"], $_SESSION["rezervace_stav"],
            $_GET["str"], $_SESSION["rezervace_order_by"], $_SESSION["rezervace_ca_x_klient"], $_SESSION["rezervace_provize"], $_GET["id_serial"], $_GET["id_zajezd"], $_GET["id_klient"]
        );
        //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
        echo $nove_rezervace->get_error_message();
        /* <a href="serial.php?id_serial=<?php echo $id_serialu;?>&typ=serial&pozadavek=objednavky">Objednávky seriálu <?php echo $nazev_serialu;?></a>
            <a href="rezervace.php?id_serial=<?php echo $id_serialu;?>&id_zajezd=<?php echo $id_zajezdu;?>&typ=rezervace_list">Objednávky zájezdu <?php echo $id_zajezdu;?></a> |
*/
        $backlinks = "";
        $id_serialu = $_GET["id_serial"];
        $id_zajezdu = $_GET["id_zajezd"];
        if ($_GET["id_serial"] > 0) {
            $backlinks .= "<a href=\"serial.php?id_serial=$id_serialu&typ=serial&pozadavek=edit\">Zpìt na seriál</a>";
            $backlinks .= "<a href=\"rezervace.php?id_serial=$id_serialu&typ=rezervace_list\">Objednávky seriálu</a>";
        }
        if ($_GET["id_zajezd"] > 0) {
            $backlinks .= "<a href=\"rezervace.php?id_serial=$id_serialu&id_zajezd=$id_zajezdu&typ=rezervace_list\">Objednávky zájezdu $id_zajezdu</a>";
        }
        ?>
        <div class="submenu">
            <!--<a href="?typ=rezervace&amp;pozadavek=new&amp;clear=1">vytvoøit novou rezervaci</a>-->
            <a href="?typ=rezervace&pozadavek=new-objednavka">vytvoøit novou rezervaci</a>
            <?php echo $backlinks; ?>
        </div>
        <?php
        //zobrazim filtry
        echo $nove_rezervace->show_filtr();
        ?>
        <h3>Objednávky</h3>
        <?php
        //zobrazim hlavicku vypisu serialu
        echo $nove_rezervace->show_list_header();

        //vypis jednotlivych serialu
        while ($nove_rezervace->get_next_radek()) {
            echo $nove_rezervace->show_list_item("tabulka");
        }
        ?>
        </table>
        <?php
        //zobrazeni strankovani
        echo ModulView::showPaging($nove_rezervace->getZacatek(), $nove_rezervace->getPocetZajezdu(), $nove_rezervace->getPocetZaznamu());


        /* ----------------	nový seriál ----------- */
    } else if ($_GET["typ"] == "rezervace_list") {

        //pokud nemam strankovani, zacnu nazacatku:)
        if ($_GET["str"] == "") {
            $_GET["str"] = "0";
        }

        /* zobrazíme seznam rezervací dle filtru */
        $rezervace = new Rezervace_list($_SESSION["rezervace_nove_prosle_vse"], $_SESSION["rezervace_typ"], $_SESSION["rezervace_podtyp"], $_SESSION["rezervace_zeme"],
            $_SESSION["rezervace_destinace"], $_SESSION["rezervace_nazev_serialu"], $_SESSION["rezervace_datum_od"], $_SESSION["rezervace_datum_do"],
            $_SESSION["rezervace_datum_pobyt_od"], $_SESSION["rezervace_datum_pobyt_do"], $_SESSION["pouze_aktualni"],
            $_SESSION["rezervace_jmeno_klienta"], $_SESSION["rezervace_prijmeni_klienta"], $_SESSION["rezervace_stav"],
            $_GET["str"], $_SESSION["rezervace_order_by"], $_SESSION["rezervace_ca_x_klient"], $_SESSION["rezervace_provize"]
        );
        //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
        echo $rezervace->get_error_message();

        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=rezervace&pozadavek=new-objednavka">vytvoøit novou rezervaci</a>
        </div>
        <?php
        //zobrazim filtry
        echo $rezervace->show_filtr();
        ?>
        <h3><? echo $rezervace->typ_rezervace_nadpis[$_SESSION["rezervace_nove_prosle_vse"]]; ?></h3>
        <?php
        //zobrazim hlavicku vypisu serialu
        echo $rezervace->show_list_header();

        //vypis jednotlivych serialu
        while ($rezervace->get_next_radek()) {
            echo $rezervace->show_list_item("tabulka");
        }
        ?>
        </table>
        <form id="massDeleteForm" action="?typ=rezervace_list&amp;pozadavek=massDelete" method="post">
            <input type="hidden" name="listOfIDs" id="listOfIDs" value="">
            
            <input type="submit" value="Hromadnì smazat zaškrtnuté" class="action-delete" id="button-massdel" onclick="return getIDsToMassDelete();">
        
        </form>
        
        <?php
        //zobrazeni strankovani
        echo ModulView::showPaging($rezervace->getZacatek(), $rezervace->getPocetZajezdu(), $rezervace->getPocetZaznamu());
        /*
         * --------------------------------------------------------------------------------------------------------------
          zde by mela byt id_objednavka !=""	a seznam moznych akci.. */


        /* ----------------	nový seriál ----------- */
    } else if ($_GET["typ"] == "rezervace" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")
        and $_SESSION["rezervace_id_serial"] != "" and $_SESSION["rezervace_id_zajezd"] != "" and $_SESSION["rezervace_id_klient"] != ""
    ) {
        //tvoba objednávky, už máme specifikovaný zájezd i klienta
        ?>
        <div class="submenu">
            <a href="?typ=rezervace_list">&lt;&lt; seznam rezervací</a>
        </div>
        <?php
        $rezervace = new Rezervace("new", $zamestnanec->get_id(), "",
            $_SESSION["rezervace_id_klient"], $_SESSION["rezervace_id_serial"], $_SESSION["rezervace_id_zajezd"],
            $_POST["rezervace_do"], $_POST["stav"], $_POST["pocet_osob"], $_POST["celkova_cena"], $_POST["poznamky"]);
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        ?><h3>Vytvoøit novou objednávku</h3><?php
        echo $rezervace->show_form();
    } else if ($_GET["typ"] == "rezervace" && $_GET["pozadavek"] == "new-objednavka") {
        echo "<script src='/admin/js/new-objednavka.js'></script>";
        echo "<script src='/admin/js/date-range-picker/moment.js'></script>";
        echo "<script src='/admin/js/date-range-picker/daterangepicker.js'></script>";
        echo "<link rel='stylesheet' type='text/css' href='/admin/js/date-range-picker/daterangepicker-bs3.css'>";

        $stavyObjednavky = "";
        $i = 1;
        while (($stav = Rezervace_library::get_stav($i)) != "") {
            $stavyObjednavky .= "<option value='" . ($i+1) . "'>$stav</option>";
            $i++;
        }

        echo "<h2 id='client-status' class='no-display'></h2>";
        echo "<h3>Vytvoøit novou objednávku</h3>";
        echo "<h4>Pøidat klienty</h4>";
        echo "<form id='frm-client' method='post' action='klienti.php?typ=klient&pozadavek=create'>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Pøímení: <span class='red'>*</span></div><div class='value'><input class='inputText' name='prijmeni' id='new-klient-prijmeni' type='text' placeholder='Zaèni pøíjmením/jménem/id klienta' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Jméno: <span class='red'>*</span></div><div class='value'><input class='inputText' name='jmeno' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Titul: </div><div class='value'><input class='inputText' name='titul' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Datum narození:</div><div class='value'><input class='calendar-ymd' name='datum_narozeni' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Rodné èíslo:</div><div class='value'><input class='inputText' name='rodne_cislo' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Email:</div><div class='value'><input class='inputText' name='email' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Telefon:</div><div class='value'><input class='inputText' name='telefon' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Èíslo obèanského prùkazu:</div><div class='value'><input class='inputText' name='cislo_op' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Èíslo pasu:</div><div class='value'><input class='inputText' name='cislo_pasu' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Mìsto:</div><div class='value'><input class='inputText' name='mesto' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>Ulice:</div><div class='value'><input class='inputText' name='ulice' type='text' /></div>
                </div>";
        echo "  <div class='form_row'>
                    <div class='label_float_left'>PSÈ:</div><div class='value'><input class='bigNumber' name='psc' type='text' /></div>
                </div>";
        echo "  <input type='hidden' name='klient_id' value=''>";
        echo "  <input type='button' id='add-klient-objednavajici' value='Pøidat jako objednávajícího'>";
        echo "  <input type='button' id='add-klient-ucastnik' value='Pøidat jako úèastníka'>";
        echo "</form>";

        echo "<h4>Obecné</h4>";
        echo "<form id='frm-objednavka' method='post' action='/admin/rezervace.php?typ=rezervace&pozadavek=create'>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Seriál:</div><div class='value'><input id='serialy-select' class='inputText' type='text' placeholder='Zaèni názvem/id seriálu'/><a tabindex='-1' target='_blank' class='valign-middle' id='serial-id'/></a><input type='hidden' name='id_serial' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Zájezd:</div><div class='value'><select id='zajezdy-select' name='id_zajezd'><option value='0'>[id] a období zájezdu</option></select></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Termín:</div><div class='value'><input class='float-left datepicker' type='text' name='termin' id='datepicker' autocomplete='off' readonly='readonly' /><span class='calendar-ico border-rad-rtb-5 float-left btn-black'></span></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Poèet nocí:</div><div class='value'><span id='pocet-noci'></span><input type='hidden' name='pocet_noci' value='0' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Opce do:</div><div class='value'><input name='rezervace_do' class='calendar-ymd' value='" . date("d.m. Y", strtotime("+7 day")) . "' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Stav objednávky:</div><div class='value'><select name='stav'>$stavyObjednavky</select></div>
              </div>";
        echo "<h4>Služby a slevy</h4>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Služby:</div><div class='value'><div id='sluzbyWrapper'></div><a id='sluzba-add' href='#'>pøidat službu</a></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Slevy:</div><div class='value'><div class='tooltip medium ioffset-bot-10'>Èástku zadávejte vždy jako kladné èíslo bez jednotek napø.: \"5%\" jako \"5\", \"1500 Kè\" jako \"1500\"</div><div id='slevyWrapper'></div><a id='sleva-add' href='#'>pøidat slevu</a></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Klientské slevy seriálu:</div><div class='value'><div class='tooltip medium ioffset-bot-10'>Èasové slevy se dopoèítávají automaticky</div><div id='slevyKlientSerialWrapper'></div></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Klientské slevy zájezdu:</div><div class='value'><div class='tooltip medium ioffset-bot-10'>Èasové slevy se dopoèítávají automaticky</div><div id='slevyKlientZajezdWrapper'></div></div>
              </div>";
        echo "<h4>Agentura</h4>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Agentura:</div><div class='value'><input class='inputText' id='agentury-select' type='text' placeholder='Zaèni názvem/id agentury'/><a tabindex='-1' target='_blank' class='valign-middle' href='' id='id_agentura'/></a><br/><input type='hidden' name='agentura' /></div>
              </div>";
        echo "<h4>Lidé</h4>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Objednávající:</div><div class='value'><div id='objednavajiciWrapper'><a tabindex='-1' target='_blank' class='valign-middle offset-right-10' href='' id='objednavajici-id'/></a><span class='valign-middle lh-30' id='objednavajici-select'></span></div><input tabindex='-1' type='checkbox' name='objednavajici-ucastnikem' id='objednavajici-ucastnikem' value='1'/><label for='objednavajici-ucastnikem'>objednávající je úèastníkem</label><input type='hidden' name='id_objednavajici' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Objednávající organizace:</div><div class='value'><input class='inputText' id='objednavajici-org-select' type='text' placeholder='Zaèni názvem/id organizace'/><a tabindex='-1' target='_blank' class='valign-middle' href='' id='id_organizace'/></a><br/><input type='hidden' name='organizace' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Úèastníci:</div><div class='value'><div id='ucastniciWrapper'></div></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Poèet osob:</div><div class='value'><div id='pocet-osob'>0</div><input type='hidden' name='pocet_osob' value='1'/></div>
              </div>";
        echo "<h4>Provize</h4>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Poznámka provize:</div><div class='value'><input type='text' name='nazevprovize' class='inputText'/></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Provize:</div><div class='value'><input name='sumaprovize' class='bigNumber' /><span class='valign-middle'>Kè</span><br/><input tabindex='-1' type='checkbox' name='sdphprovize' id='provize-dph' value='1' /><label for='provize-dph'>provize s dph</label></div>
              </div>";
        echo "<h4>Ostatní</h4>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Doprava (text na cest. smlouvu):</div><div class='value'><input name='doprava' class='inputText' type='text' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Stravování (text na cest. smlouvu):</div><div class='value'><input name='stravovani' class='inputText' type='text' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Ubytování (text na cest. smlouvu):</div><div class='value'><input name='ubytovani' class='inputText' type='text' /></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Pojištìní (text na cest. smlouvu):</div><div class='value'><input name='pojisteni' class='inputText' type='text' /></div>
              </div>";        
        echo "<div class='form_row'>
                <div class='label_float_left'>Veøejná poznámka objednávky:</div><div class='value'><textarea name='poznamky' class='inputTextArea' rows='5'></textarea></div>
              </div>";
        echo "<div class='form_row'>
                <div class='label_float_left'>Tajná poznámka objednávky:</div><div class='value'><textarea name='poznamky_secret' class='inputTextArea' rows='5'></textarea></div>
              </div>";
        echo "<input id='submit' type='submit' value='Vytvoøit objednávku'>";
        echo "</form>";
    } else if ($_GET["id_objednavka"]) {
        $rezervace = new Rezervace("show", $zamestnanec->get_id(), $_GET["id_objednavka"]);
        echo $rezervace->get_error_message();
        $nazev_serialu = $rezervace->get_nazev_serial();
        $id_zajezdu = $rezervace->get_id_zajezd();
        $id_serialu = $rezervace->get_id_serial();
        //nejakou objednavku uz mam vybranou, vypisu moznosti editace a dal zjistim co s nim chci delat
        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=rezervace_list">&lt;&lt; Seznam objednávek</a>
            <a href="rezervace.php?id_serial=<?php echo $id_serialu; ?>&typ=rezervace_list">Objednávky seriálu <?php echo $nazev_serialu; ?></a>
            <a href="rezervace.php?id_serial=<?php echo $id_serialu; ?>&id_zajezd=<?php echo $id_zajezdu; ?>&typ=rezervace_list">Objednávky zájezdu <?php echo $id_zajezdu; ?></a>


            <!--<a href="?typ=rezervace&amp;pozadavek=new&amp;clear=1">vytvoøit novou rezervaci</a>-->
            <a href="?typ=rezervace&pozadavek=new-objednavka">vytvoøit novou rezervaci</a>

            <br/>
            <?

            //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
            echo $rezervace->show_submenu();
            ?>
        </div>
        <?
        //print_r($_POST);
        // print_r($_GET);

        if ($_GET["typ"] == "rezervace" and $_GET["pozadavek"] == "update_stav" and $_POST["submit_zmena"] == "Pokraèovat") {
            //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat
            if ($_POST["typ_zmeny"] == "zmenit_serial") {
                echo "<h3>Zmìna seriálu u objednávky</h3>";
            } else {
                echo "<h3>Zmìna zájezdu u objednávky</h3>";
            }
            echo "<table>";
            $rezervace = new Rezervace("update", $zamestnanec->get_id(), $_GET["id_objednavka"], $_REQUEST["id_klient"], $_REQUEST["id_serial"], $_REQUEST["id_zajezd"],
                $_POST["rezervace_do"], $_POST["stav"], $_POST["pocet_osob"], $_POST["celkova_cena"], $_POST["poznamky"], $_POST["termin_od"], $_POST["termin_do"], $_POST["pocet_noci"], "", "", "", $_POST["nazev_provize"], $_POST["sdph_provize"], $_POST["suma_provize"], $_POST["storno_poplatek"]);

        } else if ($_GET["typ"] == "rezervace" and $_GET["pozadavek"] == "change_serial") {
           // echo "objednavka";
            echo "<h3>Zmìna zájezdu u objednávky</h3>";
            $rezervace = new Rezervace("update", $zamestnanec->get_id(), $_GET["id_objednavka"], $_REQUEST["id_klient"], $_REQUEST["id_serial"], $_REQUEST["id_zajezd"],
                $_POST["rezervace_do"], $_POST["stav"], $_POST["pocet_osob"], $_POST["celkova_cena"], $_POST["poznamky"], $_POST["termin_od"], $_POST["termin_do"], $_POST["pocet_noci"], "", "", "", $_POST["nazev_provize"], $_POST["sdph_provize"], $_POST["suma_provize"], $_POST["storno_poplatek"]);


        } else if ($_GET["typ"] == "rezervace" and $_GET["pozadavek"] == "change_zajezd") {
            //po zmene zajezdu je treba upravit zadani sluzeb
            //podle typu pozadvku vytvorim instanci tridy serial
            $rezervace = new Rezervace("change_termin", $zamestnanec->get_id(), $_GET["id_objednavka"]);
            
            echo "<form action=\"?id_objednavka=" . $_GET["id_objednavka"] . "&amp;typ=rezervace&amp;pozadavek=update_ceny\" method=\"post\">";
          //  echo $rezervace->show_termin();//nejak nejsem schopny dohledat, co mela tahle metoda delat (a ani tu metodu samotnou ve starych datech) - ale zda se, ze to dostatecne funguje i bez ni, Lada
            $rezervace_cena = new Rezervace_cena("edit", $zamestnanec->get_id(), $_GET["id_objednavka"]);
            echo $rezervace_cena->get_error_message();
            echo "<h3>Úprava cen po zmìnì zájezdu</h3>";
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $rezervace_cena->show_form($stare_sluzby, false);
            echo "<input type=\"submit\" value=\"Zmìnit seriál/zájezd\"/></form>";
            
            
            
        } else if ($_GET["typ"] == "rezervace" and $_GET["pozadavek"] == "show") {
            echo $rezervace->get_error_message();
            echo "<h3>Editace objednávky</h3>";
            echo $rezervace->show("tabulka", $_GET["sub_pozadavek"]);
        } else if ($_GET["typ"] == "rezervace" and $_GET["pozadavek"] == "edit_ceny2") {
            echo $rezervace->get_error_message();
            echo "<h3>Editace objednávky</h3>";
            echo $rezervace->show("tabulka");
        } else if ($_GET["typ"] == "rezervace" and $_GET["pozadavek"] == "edit_ceny") {
            //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat
            //podle typu pozadvku vytvorim instanci tridy serial
            $rezervace_cena = new Rezervace_cena("edit", $zamestnanec->get_id(), $_GET["id_objednavka"]);
            //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
            echo $rezervace_cena->get_error_message();
            ?>
            <h3>Editace služeb/cen objednávky</h3>
            <?
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $rezervace_cena->show_form();
        } else if ($_GET["typ"] == "rezervace_platba_list") {
            //seznam plateb ke konkretni objednavce
            echo "<div class=\"submenu\">
                         <a href=\"?typ=rezervace_platba&amp;pozadavek=new&amp;id_objednavka=" . $_GET["id_objednavka"] . "\">vytvoøit novou platbu</a>
                  </div>";

            $rezervace_platba_list = new Rezervace_platba_list($_GET["id_objednavka"], $_SESSION["rezervace_platba_order_by"]);
            //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
            echo $rezervace_platba_list->get_error_message();
            //nadpis seznamu
            echo $rezervace_platba_list->show_header();
            //zobrazim hlavicku vypisu serialu
            echo $rezervace_platba_list->show_list_header();

            //vypis jednotlivych serialu
            while ($rezervace_platba_list->get_next_radek()) {
                echo $rezervace_platba_list->show_list_item("tabulka");
            }
            ?>
            </table>
        <?
        } else if ($_GET["typ"] == "rezervace_platba" and $_GET["pozadavek"] == "new") {
            //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat
            //tvoba objednávky, už máme specifikovaný zájezd i klienta

            $rezervace_platba = new Rezervace_platba("new", $zamestnanec->get_id(), "", $_GET["id_objednavka"]);
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            ?><h3>Vytvoøit novou platbu k objednávce</h3><?
            echo $rezervace_platba->show_form();
        } else if ($_GET["typ"] == "rezervace_platba" and $_GET["pozadavek"] == "edit") {
            //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat
            //tvoba objednávky, už máme specifikovaný zájezd i klienta

            $rezervace_platba = new Rezervace_platba("edit", $zamestnanec->get_id(), $_GET["id_platba"], $_GET["id_objednavka"]);
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            ?><h3>Editovat platbu k objednávce</h3><?
            echo $rezervace_platba->show_form();
        } else if ($_GET["typ"] == "rezervace_osoby") {
            //nejaky klient uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat
            //tvoba objednávky, už máme specifikovaný zájezd i klienta

            $current_osoby = new Rezervace_osoba("show", $zamestnanec->get_id(), $_GET["id_objednavka"]);
            echo $current_osoby->get_error_message();
            ?>
            <h3>Klienti pøiøazení k objednávce</h3>
            <?
            echo $current_osoby->show_list_header();
            echo $current_osoby->show_list("tabulka");
            ?>
            </table>
            <?
            //vytvorime instanci klient_list
            $klient_list = new Klient_list("show_all", $_SESSION["klient_jmeno"], $_SESSION["klient_prijmeni"], $_SESSION["klient_datum_narozeni"], $_GET["str"], $_SESSION["klient_order_by"], $_GET["moznosti_editace"]);
            //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
            echo $klient_list->get_error_message();
            //zobrazim nadpis seznamu
            echo $klient_list->show_header();
            //zobrazim filtry
            echo $klient_list->show_filtr();

            //zobrazim hlavicku seznamu
            echo $klient_list->show_list_header();

            //vypis jednotlivych serialu
            while ($klient_list->get_next_radek()) {
                echo $klient_list->show_list_item("tabulka_objednavka");
            }
            ?>
            </table>
            <?
            //zobrazeni strankovani
            echo ModulView::showPaging($klient_list->getZacatek(), $klient_list->getPocetZajezdu(), $klient_list->getPocetZaznamu());
        }
    }//if get[id_objednavka]
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