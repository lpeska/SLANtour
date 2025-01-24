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
require_once "./config/config_export_sdovolena.inc.php"; //seznamy serialu


require_once "./classes/zeme_list.inc.php"; //seznamy serialu
require_once "./classes/foto_list.inc.php"; //seznamy serialu
require_once "./classes/objekty_list.inc.php"; //seznamy klientù
require_once "./classes/objekty_foto.inc.php"; //seznam fotografií serialu
require_once "./classes/objekt.inc.php"; //detail seriálu

require_once "./classes/serial.inc.php"; //detail seriálu
require_once "./classes/serial_objekty.inc.php"; //detail seriálu
require_once "./classes/serial_cena.inc.php"; //detail seriálu
require_once "./classes/serial_foto.inc.php"; //detail seriálu
require_once "./classes/serial_list.inc.php"; //detail seriálu
require_once "./classes/zeme.inc.php"; //detail seriálu
require_once "./classes/serial_zeme.inc.php"; //detail seriálu
require_once "./classes/zajezd.inc.php"; //detail seriálu
require_once "./classes/zajezd_cena.inc.php"; //detail seriálu

require_once "./classes/tok_list.inc.php"; //seznam fotografií serialu
require_once "./classes/tok.inc.php"; //seznam fotografií serialu

require_once "../global/lib/utils/CommonUtils.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";
/*
//pripojeni k databazi
$database = new Database();

//spusteni prace se sessions
	
	
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
    if ($_GET["typ"] == "objekty_list") {
        //zmenime filtry ulozene v sessions
        if ($_GET["pozadavek"] == "change_filter") {
            //kontrola vstupu je provadena pri volani konstruktoru tøidy klient_list
            //filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
            if ($_GET["pole"] == "nazev") {
                $_SESSION["objekt_nazev"] = $_POST["objekt_nazev"];
                $_SESSION["objekt_id_organizace"] = $_POST["objekt_id_organizace"];
                $_SESSION["objekt_typ"] = $_POST["objekt_typ"];
            } else if ($_GET["pole"] == "ord_by") {
                $_SESSION["objekt_order_by"] = $_GET["objekt_order_by"];
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list&moznosti_editace=" . $_GET["moznosti_editace"] . "";
        

        } else if ($_GET["pozadavek"] == "mass-autocheck-select") {
            $ids = implode(",", $_POST["selected_ids"]);

            $sql = "UPDATE `objekt_letenka` SET `automaticka_kontrola_cen`=1 WHERE `id_objektu` in ($ids) ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();

        } else if ($_GET["pozadavek"] == "mass-autocheck-unselect") {
            $ids = implode(",", $_POST["selected_ids"]);

            $sql = "UPDATE `objekt_letenka` SET `automaticka_kontrola_cen`=0 WHERE `id_objektu` in ($ids) ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();

        } else if ($_GET["pozadavek"] == "mass-autocheck-select-delayed") {
            $ids = implode(",", $_POST["selected_ids"]);

            $sql = "UPDATE `objekt_letenka` SET `automaticka_odlozena_kontrola_cen`=1 WHERE `id_objektu` in ($ids) ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();

        } else if ($_GET["pozadavek"] == "mass-autocheck-unselect-delayed") {
            $ids = implode(",", $_POST["selected_ids"]);

            $sql = "UPDATE `objekt_letenka` SET `automaticka_odlozena_kontrola_cen`=0 WHERE `id_objektu` in ($ids) ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();

        }
        /*---------------------serial---------------*/
    } else if ($_GET["typ"] == "create_serial") {
        if ($_GET["pozadavek"] == "create_from_vstupenka") {
            $sql = "SELECT * FROM  `objekt` join `objekt_vstupenka` on (`objekt`.`id_objektu` = `objekt_vstupenka`.`id_objektu`)
                                        where `objekt`.`id_objektu`=" . $_GET["id_objektu"] . " limit 1";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($row1 = mysqli_fetch_array($data)) {
                //$_POST["nazev"] = $_POST["nazev"];
                $_POST["popisek"] = $row1["popis_objektu"];
                $_POST["poznamky"] = $row1["poznamka"];
                $_POST["sport"] = $row1["sport"];
                $_POST["akce"] = $row1["akce"];
                $_POST["kod_vstupenky"] = $row1["kod"];
                $_POST["typ_serial"] = "4";
                $_POST["cena_zahrnuje"] = "";
                $_POST["cena_nezahrnuje"] = "";
                $_POST["strava"] = 1;
                $_POST["doprava"] = 1;
                $_POST["ubytovani"] = 1;
                $_POST["dlouhodobe_zajezdy"] = 0;
                $_POST["predregistrace"] = 0;
                $_POST["nezobrazovat"] = 0;
                $_POST["id_sml_podm"] = 38;
                $_POST["id_sablony_zobrazeni"] = 7;
                $_POST["id_sablony_objednavka"] = 0;
                $_POST["id_ridici_objekt"] = $_GET["id_objektu"];
                //create serial from data
                $query_zeme = "select * from `zeme` where `nazev_zeme_web` like \"" . Serial_list::nazev_web_static($row1["sport"]) . "\" limit 1";
                $data_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_zeme);
                while ($row4 = mysqli_fetch_array($data_zeme)) {
                    $_POST["id_zeme"] = $row4["id_zeme"];
                }
                $dotaz = new Serial("create", $zamestnanec->get_id(), "", $_POST["nazev"], $_POST["nazev"], $_POST["popisek"], "",
                    "", "", "", "", "",
                    $_POST["cena_zahrnuje"], $_POST["cena_nezahrnuje"], $_POST["poznamky"],
                    $_POST["typ_serial"], "", $_POST["strava"], $_POST["doprava"], $_POST["ubytovani"], "", $_POST["dlouhodobe_zajezdy"], "", "", $_POST["predregistrace"], $_POST["nezobrazovat"],
                    "", "", "", $_POST["id_sml_podm"], $_POST["id_sablony_zobrazeni"], $_POST["id_sablony_objednavka"], "", "", $_POST["id_ridici_objekt"]);


                $id_serial = $dotaz->get_id();
                $dotaz_objekt = new Objekty_serial("create", $zamestnanec->get_id(), $id_serial, $_GET["id_objektu"]);
                if ($dotaz_objekt->get_error_message()) {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_objekt->get_error_message();
                }
                //fotky
                $dotaz_foto = "select * from `foto_objekty` where `id_objektu`=" . $_GET["id_objektu"] . " ";
                $data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_foto);
                while ($row_foto = mysqli_fetch_array($data_foto)) {
                    $insert_foto = new Foto_serial("create", $zamestnanec->get_id(), $id_serial, $row_foto["id_foto"], $row_foto["zakladni_foto"]);
                    if ($insert_foto->get_error_message()) {
                        $_SESSION["hlaska"] = $_SESSION["hlaska"] . $insert_foto->get_error_message();
                    }

                }

                //sluzby
                $sql = "SELECT * FROM  `objekt_kategorie`
                                        where `objekt_kategorie`.`id_objektu`=" . $_GET["id_objektu"];
                $data2 = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                $_POST["pocet"] = mysqli_num_rows($data2);
                $dotaz_cena = new Cena_serial("create", $zamestnanec->get_id(), $id_serial, "", $_POST["pocet"]);
                $i = 0;
                while ($row2 = mysqli_fetch_array($data2)) {
                    $i++;
                    //create sluzba from data
                    $_POST["nazev_ceny"] = $row2["nazev"];
                    $_POST["kratky_nazev"] = $row2["kratky_nazev"];
                    $_POST["nazev_ceny_en"] = $row2["cizi_nazev"];
                    $_POST["zakladni_cena"] = $row2["zakladni_kategorie"];
                    $_POST["use_pocet_noci"] = 0;
                    $_POST["kapacita_bez_omezeni"] = 0;
                    if ($_POST["kratky_nazev"] != "") {
                        $_POST["zkraceny_vypis"] = 1;
                    } else {
                        $_POST["zkraceny_vypis"] = 0;
                    }
                    $_POST["poradi_ceny"] = 100 + $i;
                    $_POST["typ_ceny"] = 1;
                    $_POST["id_objekt_kategorie"] = $row2["id_objekt_kategorie"];
                    $_POST["id_objekt_kategorie_" . $i . "_1"] = $row2["id_objekt_kategorie"];

                    if (!$dotaz->get_error_message()) {
                        $dotaz_cena->add_to_query("", $_POST["id_objekt_kategorie"], $_POST["nazev_ceny"], $_POST["kratky_nazev"], $_POST["zkraceny_vypis"],
                            $_POST["poradi_ceny"], $_POST["typ_ceny"], $_POST["zakladni_cena"], $_POST["kapacita_bez_omezeni"], $_POST["use_pocet_noci"], $_POST["nazev_ceny_en"], "", "", "", "");
                    }
                }
                $dotaz_cena->finish_query();
                if ($dotaz_cena->get_error_message()) {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_cena->get_error_message();
                }

                $sql = "SELECT * FROM  `cena`
                                                join `cena_objekt_kategorie` on (`cena_objekt_kategorie`.`id_cena` = `cena`.`id_cena` and `cena`.`id_serial` = " . $id_serial . ")
                                                join `objekt_kategorie_termin` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`)
                                            where 1
                                            order by `objekt_kategorie_termin`.`id_termin`";
                $last_id_tok = 0;
                $i = 1;
                // echo $sql;
                $data_tok = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row3 = mysqli_fetch_array($data_tok)) {
                    if ($last_id_tok != $row3["id_termin"]) {
                        if ($last_id_tok != 0) {
                            $_POST["pocet"] = $i - 1;
                            $dotaz_zajezd = new Zajezd("create", $id_serial, "", "", $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], "", "", "", "");
                            //echo $dotaz_zajezd->get_error_message();
                            if ($dotaz_zajezd->get_error_message()) {
                                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_zajezd->get_error_message();
                            }

                        }
                        $od = explode(" ", $row3["datetime_od"]);
                        $do = explode(" ", $row3["datetime_do"]);
                        $_POST["od"] = $dotaz->change_date_en_cz($od[0]);
                        $_POST["do"] = $dotaz->change_date_en_cz($do[0]);
                        $_POST["hit_zajezd"] = 0;
                        $_POST["poznamky_zajezd"] = "";
                        $_POST["nazev_zajezdu"] = $row3["nazev_tok"];
                        $_POST["nezobrazovat"] = 0;
                        $last_id_tok = $row3["id_termin"];
                        $i = 1;
                    }

                    $_POST["id_cena_" . $i] = $row3["id_cena"];
                    $_POST["castka_" . $i] = $row3["cena"];
                    $_POST["mena_" . $i] = "Kè";
                    $_POST["castka_euro_" . $i] = "";
                    $_POST["kapacita_volna_" . $i] = 0;
                    $_POST["kapacita_celkova_" . $i] = 0;
                    $_POST["vyprodano_" . $i] = 0;
                    $_POST["na_dotaz_" . $i] = 0;
                    $_POST["pouzit_cenu_" . $i] = 1;
                    $_POST["id_tok_" . $i . "_" . $row3["id_objekt_kategorie"]] = $row3["id_termin"];
                    $_POST["je_vstupenka_" . $i] = 1;
                    $i++;
                    //print_r($_POST);
                }
                //zapsat posledni zajezd
                if ($last_id_tok != 0) {
                    $_POST["pocet"] = $i - 1;
                    $dotaz_zajezd = new Zajezd("create", $id_serial, "", "", $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], "", "", "", "");
                    if ($dotaz_zajezd->get_error_message()) {
                        $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz_zajezd->get_error_message();
                    }
                }
                //pridat fotografie

            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        }
    } else if ($_GET["typ"] == "create_zajezd") {
        if ($_GET["pozadavek"] == "create_from_vstupenka") {
            //vsechny serialy, ktere maji prirazeny tenhle objekt
            $sql = "SELECT `serial`.`id_serial` FROM `serial` join `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                        where `objekt_serial`.`id_objektu`=" . $_GET["id_objektu"] . " and `id_ridici_objekt`=" . $_GET["id_objektu"] . "";

            $data_serial = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);

            while ($row_serial = mysqli_fetch_array($data_serial)) {
                $id_serial = $row_serial["id_serial"];
                $dotaz = new Serial("show", $zamestnanec->get_id(), $id_serial);

                $sql = "SELECT * FROM  `cena`
                                                join `cena_objekt_kategorie` on (`cena_objekt_kategorie`.`id_cena` = `cena`.`id_cena` and `cena`.`id_serial` = " . $id_serial . ")
                                                join `objekt_kategorie_termin` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`)
                                            where `objekt_kategorie_termin`.`id_termin`=" . $_GET["id_termin"] . "
                                            order by `objekt_kategorie_termin`.`id_termin`";
                // echo $sql;
                $last_id_tok = 0;
                $i = 1;
                // echo $sql;
                $data_tok = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row3 = mysqli_fetch_array($data_tok)) {
                    if ($last_id_tok != $row3["id_termin"]) {
                        if ($last_id_tok != 0) {
                            $_POST["pocet"] = $i - 1;
                            $dotaz_zajezd = new Zajezd("create", $id_serial, "", "", $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], "", "", "", "");
                            echo $dotaz_zajezd->get_error_message();

                        }
                        $od = explode(" ", $row3["datetime_od"]);
                        $do = explode(" ", $row3["datetime_do"]);
                        $_POST["od"] = $dotaz->change_date_en_cz($od[0]);
                        $_POST["do"] = $dotaz->change_date_en_cz($do[0]);
                        $_POST["hit_zajezd"] = 0;
                        $_POST["poznamky_zajezd"] = "";
                        $_POST["nazev_zajezdu"] = $row3["nazev_tok"];
                        $_POST["nezobrazovat"] = 0;
                        $last_id_tok = $row3["id_termin"];
                        $i = 1;
                    }

                    $_POST["id_cena_" . $i] = $row3["id_cena"];
                    $_POST["castka_" . $i] = $row3["cena"];
                    $_POST["mena_" . $i] = "Kè";
                    $_POST["castka_euro_" . $i] = "";
                    $_POST["kapacita_volna_" . $i] = 0;
                    $_POST["kapacita_celkova_" . $i] = 0;
                    $_POST["vyprodano_" . $i] = 0;
                    $_POST["na_dotaz_" . $i] = 0;
                    $_POST["pouzit_cenu_" . $i] = 1;
                    $_POST["id_tok_" . $i . "_" . $row3["id_objekt_kategorie"]] = $row3["id_termin"];
                    $_POST["je_vstupenka_" . $i] = 1;
                    $i++;
                    //print_r($_POST);
                }
                //zapsat posledni zajezd
                if ($last_id_tok != 0) {
                    $_POST["pocet"] = $i - 1;
                    $dotaz_zajezd = new Zajezd("create", $id_serial, "", "", $_POST["od"], $_POST["do"], $_POST["hit_zajezd"], $_POST["poznamky_zajezd"], $_POST["nazev_zajezdu"], $_POST["nezobrazovat"], "", "", "", "");
                    echo $dotaz_zajezd->get_error_message();
                }
                //pridat fotografie
                if ($dotaz->get_error_message()) {
                    $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
                }

            }

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok&id_objektu=" . $_GET["id_objektu"] . "&typ=tok_list&pozadavek=show";


        }


    } else if ($_GET["typ"] == "objekty") {              
        if ($_GET["pozadavek"] == "create_from_ubytovani_organizace") {
            $sql = "SELECT * FROM  `organizace_ubytovani` where 1";            
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($row1 = mysqli_fetch_array($data)) {
                $sql_objekt = "SELECT * FROM `ubytovani`
                    left join `objekt_ubytovani` on (`objekt_ubytovani`.`nazev_web` = `ubytovani`.`nazev_web`) WHERE `ubytovani`.`id_ubytovani`=".$row1["id_ubytovani"]."";
                echo $sql_objekt;
                $data_objekt = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_objekt);
                while ($row_objekt = mysqli_fetch_array($data_objekt)) {
                    if($row_objekt["id_objektu"]>0){
                        $sql_update = "update `organizace_ubytovani` set id_ubytovani=".$row_objekt["id_objektu"]." where id_organizace=".$row1["id_organizace"]." limit 1";
                        mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update);
                    }else{
                        $sql_update = "delete from `organizace_ubytovani` where id_organizace=".$row1["id_organizace"]." limit 1";
                        mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update);
                    }
                    echo $sql_update;
                }
            }
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list";
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        } else if ($_GET["pozadavek"] == "ajax_get_goglobal_ceny") {    
            $dotaz = new Objekty("ajax_get_goglobal_ceny", $zamestnanec->get_id(), $_GET["id_objektu"]);
            if (!$dotaz->get_error_message()) {
                echo $dotaz->show_goglobal_ceny();
            }else{
                echo $dotaz->get_error_message();
                //print_r($_GET);
            }
            exit();
        } else if ($_GET["pozadavek"] == "create") {
            //insert do tabulky seriálù
            $dotaz = new Objekty("create", $zamestnanec->get_id());
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                if ($_POST["ulozit"] != "") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_objektu=" . $dotaz->get_id() . "&typ=objekty&pozadavek=edit";
                } else if ($_POST["ulozit_a_zavrit"]) {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list";
                }


                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Objekty("update", $zamestnanec->get_id(), $_GET["id_objektu"]);
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
                if ($_POST["ulozit"] != "") {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?id_objektu=" . $_GET["id_objektu"] . "&typ=objekty&pozadavek=edit";
                } else if ($_POST["ulozit_a_zavrit"]) {
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list";
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Objekty("delete", $zamestnanec->get_id(), $_GET["id_objektu"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list";
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
            //

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_objektu=" . $_GET["id_objektu"] . "";


        }
    } else if ($_GET["typ"] == "foto") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Foto_objekty("create", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_foto"], $_GET["zakladni_foto"], $_GET["zakladni_pro_typ"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_objektu=" . $_GET["id_objektu"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            $dotaz = new Foto_objekty("update", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_foto"], $_GET["zakladni_foto"], $_GET["zakladni_pro_typ"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_objektu=" . $_GET["id_objektu"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
            $dotaz = new Foto_objekty("delete", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_foto"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_objektu=" . $_GET["id_objektu"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

    } else if ($_GET["typ"] == "foto_ok") {
        if ($_GET["pozadavek"] == "create") {
            $dotaz = new Foto_objekty("create_ok", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_foto"], $_GET["zakladni_foto"], $_GET["zakladni_pro_typ"], $_GET["id_objekt_kategorie"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_objektu=" . $_GET["id_objektu"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete_ok") {
            $dotaz = new Foto_objekty("delete_ok", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_foto"], "", "", $_GET["id_objekt_kategorie"]);
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                //vytvorime adresu dalsi stranku automaticky nactenou pres http location
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=foto&id_objektu=" . $_GET["id_objektu"] . "";
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }
        }

    } else if ($_GET["typ"] == "tok") {
        if ($_GET["pozadavek"] == "create") {
            $id_termin = 0;
            $sql = "select max(`id_termin`) as `termin` from `objekt_kategorie_termin` where 1";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($row_termin = mysqli_fetch_array($data)) {
                $id_termin = intval($row_termin["termin"]);
                $id_termin++;
            }
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen
            $dotaz = new Termin_objektove_kategorie("create", $zamestnanec->get_id(), $_GET["id_objektu"], $id_termin, $_POST["datum_od"], $_POST["datum_do"], $_POST["nazev_tok"]);
            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= MAX_CEN) {
                    if ($_POST["pouzit_" . $i] == 1) {
                        $dotaz->add_to_query($id_termin, $_POST["id_objekt_kategorie_" . $i], $_POST["cena_" . $i], $_POST["kapacita_" . $i], $_POST["kapacita_bez_omezeni_" . $i],
                            $_POST["na_dotaz_" . $i], $_POST["vyprodano_" . $i], $_POST["pouzit_" . $i]);
                    }
                    $i++;
                }

                $dotaz->finish_query();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_objektu=" . $_GET["id_objektu"] . "&id_termin=" . $id_termin . "&typ=create_zajezd&pozadavek=create_from_vstupenka";

            //$adress = $_SERVER['SCRIPT_NAME']."?typ=tok&id_objektu=".$_GET["id_objektu"]."&id_termin=".$id_termin."&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "update") {
            //editace a tvorba cen se provadi hromadne pro vsechny ceny, v $_POST["pocet"] je ulozen celkovy pocet edit. cen
            $dotaz = new Termin_objektove_kategorie("update", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_termin"], $_POST["datum_od"], $_POST["datum_do"], $_POST["nazev_tok"]);
            if (!$dotaz->get_error_message()) {
                $i = 1;
                while ($i <= MAX_CEN) {
                    //echo "<br/>cena:".$_GET["id_termin"]."-".$_POST["id_objekt_kategorie_".$i]."-".$_POST["cena_".$i]."-".$_POST["pouzit_".$i];
                    if (isset($_POST["id_objekt_kategorie_" . $i])) {
                        $dotaz->add_to_query($_GET["id_termin"], $_POST["id_objekt_kategorie_" . $i], $_POST["cena_" . $i], $_POST["kapacita_" . $i], $_POST["kapacita_bez_omezeni_" . $i],
                            $_POST["na_dotaz_" . $i], $_POST["vyprodano_" . $i], $_POST["pouzit_" . $i]);
                    }
                    $i++;
                }

                $dotaz->finish_query();
            }
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location

            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok&id_objektu=" . $_GET["id_objektu"] . "&typ=tok_list&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "copy") {
            $dotaz = new Termin_objektove_kategorie("copy", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_termin"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $id_termin = $dotaz->get_inserted_id_termin();
            $adress = $_SERVER['SCRIPT_NAME'] . "?id_objektu=" . $_GET["id_objektu"] . "&id_termin=" . $id_termin . "&typ=create_zajezd&pozadavek=create_from_vstupenka";

            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "mass_delete") {
            $sql = "
                select distinct `typ_objektu`,`objekt_kategorie_termin`.`id_objektu`,`id_termin`,`datetime_od`,`datetime_do`,`nazev_tok` 
                    from `objekt_kategorie_termin` join objekt on (`objekt_kategorie_termin`.`id_objektu` = `objekt`.`id_objektu`)
                    where `objekt_kategorie_termin`.`id_objektu`=" . $_GET["id_objektu"] . "
            ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while($del_row = mysqli_fetch_array($data)){
                if($_POST["checkbox_".$del_row["id_termin"]]==1){
                    $dotaz = new Termin_objektove_kategorie("delete", $zamestnanec->get_id(), $_GET["id_objektu"], $del_row["id_termin"]);
                }                
            }
            
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok_list&id_objektu=" . $_GET["id_objektu"] . "&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }

        } else if ($_GET["pozadavek"] == "delete") {
           
            $dotaz = new Termin_objektove_kategorie("delete", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_termin"]);
            //vytvorime adresu dalsi stranku - automaticky nactenou pres http location
            $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok_list&id_objektu=" . $_GET["id_objektu"] . "&pozadavek=show";
            //pokud vse probehlo spravne, vypisu OK hlasku
            if (!$dotaz->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }             
        } else if ($_GET["pozadavek"] == "kv_update") {
           
            $dotaz = new Objekty("kalkulacni_vzorce_update", $zamestnanec->get_id(), $_GET["id_objektu"]);

            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                if($_POST["submit"]=="Uložit a zavøít"){
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=objekty_list";
                }else if($_POST["submit"]=="Uložit a aktualizovat zájezdy"){           
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok_list&id_objektu=" . $_GET["id_objektu"] . "&pozadavek=kalkulacni_vzorce_vygenerovat_terminy&typ_editace=aktualizace";
                }else{
                    $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok_list&id_objektu=" . $_GET["id_objektu"] . "&pozadavek=show_letuska"; //TODO get correct var here!
                }
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $dotaz->get_error_message();
            }            
        } else if ($_GET["pozadavek"] == "kalkulacni_vzorce_create_zajezdy") {
            $database = Database::get_instance();
            //print_r($_POST);
            $s = 1;
            while($_POST["id_serial_$s"]!=""){
                $id_serial = $_POST["id_serial_$s"];
                $i=1;                      
                while($_POST["termin_od_".$id_serial."_$i"]!=""){
                    $j=1;               
                    while($_POST["id_cena_".$id_serial."_$j"]!=""){
                        
                        $_POST["id_cena_$j"] = $_POST["id_cena_".$id_serial."_$j"];
                        $_POST["castka_$j"] = $_POST["cena_".$id_serial."_".$_POST["id_cena_$j"]."_".$i.""];
                        
                        if($_POST["castka_$j"] !== ""){
                            $_POST["pouzit_cenu_$j"] = 1;
                        }else{
                            $_POST["pouzit_cenu_$j"] = 0;
                        }  
                        $j++;
                    }
                    $_POST["pocet"] = ($j-1);
                    if($_POST["vytvorit_zajezd_".$id_serial."_$i"]=="1"){
                        $_POST["od"] = $_POST["termin_od_".$id_serial."_$i"];
                        $_POST["do"] = $_POST["termin_do_".$id_serial."_$i"];

                        if($_POST["existujici_zajezd_".$id_serial."_$i"]!=""){
                            $dotaz = new Zajezd("update_dle_kv", $id_serial, $_POST["existujici_zajezd_".$id_serial."_$i"], "", $_POST["od"], $_POST["do"], 0, "", "", 0, "", "", "", 1);
                        }else{
                            $dotaz = new Zajezd("create", $id_serial, "", "", $_POST["od"], $_POST["do"], 0, "", "", 0, "", "", "", 1);
                        }                                             
                    }
                    $i++;
                }
                $s++;
            }
            
            
            //pokud vse probehlo spravne, vypisu OK hlasku	
            if (!$dotaz->get_error_message()) {
                $query_clear_checkboxes = " update `cena_promenna_cenova_mapa` set no_dates_generation = 1 where id_objektu = ".$_GET["id_objektu"]."";
                $res_update = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_clear_checkboxes);
            
                $adress = $_SERVER['SCRIPT_NAME'] . "?typ=tok_list&id_objektu=" . $_GET["id_objektu"] . "&pozadavek=show_letuska"; //TODO get correct var here!
                
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
//print_r($_POST);
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
    
            <link type="text/css" href="/jqueryui/css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
    <script type="text/javascript" src="/jqueryui/js/jquery-1.7.1.min.js"></script>
        <script type="text/javascript" src="/jqueryui/js/jquery-ui-1.8.18.custom.min.js"></script>
        <script type="text/javascript" src="./js/jQueryRotate.js"></script>
    <script type="text/javascript" src="js/blackdays.js"></script>
    <script type="text/javascript" src="./js/common_functions.js"></script>
        <script type="text/javascript">
                    $(function() {
                    $( "#tabs" ).tabs();
                    });
                </script>
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

    //na zacatku zobrazim seznam
    if ($_GET["typ"] == "") {
        $_GET["typ"] = "objekty_list";
    }

    /*----------------	seznam seriálù -----------*/
    if ($_GET["typ"] == "objekty_list") {

        //pokud nemam strankovani, zacnu nazacatku:)
        if ($_GET["str"] == "") {
            $_GET["str"] = "0";
        }

        //vytvorime instanci objekty_list
        $objekty_list = new Objekty_list("show_all", $_SESSION["objekt_nazev"], $_SESSION["objekt_id_organizace"], $_SESSION["objekt_typ"], $_GET["str"], $_SESSION["objekt_order_by"], $_GET["moznosti_editace"]);
        //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
        echo $objekty_list->get_error_message();

        //vypisu menu
        ?>
        <div class="submenu">
            <? echo "<a href=\"?typ=objekty&amp;pozadavek=new&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "\">vytvoøit nový objekt</a>" ?>
        </div>
        <?
        //zobrazim filtry
        echo $objekty_list->show_filtr();
        //zobrazim nadpis seznamu
        echo $objekty_list->show_header();
        //zobrazim hlavicku seznamu
        echo $objekty_list->show_list_header();

        //vypis jednotlivych serialu
        while ($objekty_list->get_next_radek()) {
            echo $objekty_list->show_list_item("tabulka");
        }
        
        echo $objekty_list->show_list_footer();
        ?>


        
        <?
        //zobrazeni strankovani
        echo ModulView::showPaging($objekty_list->getZacatek(), $objekty_list->getPocetZajezdu(), $objekty_list->getPocetZaznamu());

        /*----------------	nový seriál -----------*/
    } else if ($_GET["typ"] == "objekty" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {

        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
        </div>
        <?
        $objekty = new Objekty("new", $zamestnanec->get_id());
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        ?><h3>Vytvoøit nový objekt</h3><?
        echo $objekty->show_form();

    } else if ($_GET["typ"] == "objekty" and  ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
        //nejaky objekty uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat

        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
            <a href="?typ=objekty&amp;pozadavek=new">vytvoøit nový objekt</a>
        </div>
        <?
        // print_r($_GET);
        //podle typu pozadvku vytvorim instanci tridy serial
        $objekty = new Objekty("edit", $zamestnanec->get_id(), $_GET["id_objektu"]);
        //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
        echo $objekty->show_submenu();
        ?>
        <h3>Editace objektu</h3>
        <?
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        echo $objekty->show_edit_form();
    } else if ($_GET["typ"] == "objekty" and $_GET["pozadavek"] == "objednavky") {

        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
            <a href="?typ=objekty&amp;pozadavek=new">vytvoøit nový objekt</a>
        </div>
        <?
        //podle typu pozadvku vytvorim instanci tridy serial
        $objekty = new Objekty("edit", $zamestnanec->get_id(), $_GET["id_objekty"]);
        echo $objekty->show_submenu();

        ?><h3>Objednávky související s objektù</h3><?
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        echo $objekty->show_objednavky();
    } else if ($_GET["typ"] == "foto") {
        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
            <a href="?typ=objekty&amp;pozadavek=new">vytvoøit nový objekt</a>
        </div>
        <?
        /*
            u fotografii zobrazuju aktuálnì pøipojené fotografie
            a seznam fotografií, které lze pøipojit (stránkovaný s filtry výbìru)
        */
        //seznam fotografii pripojenych k serialu
        $objekty = new Objekty("edit", $zamestnanec->get_id(), $_GET["id_objektu"]);
        $current_foto = new Foto_objekty("show", $zamestnanec->get_id(), $_GET["id_objektu"]);
        $current_foto2 = new Foto_objekty("show_ok", $zamestnanec->get_id(), $_GET["id_objektu"]);
        //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
        echo $objekty->show_submenu();

        ?>
        <h3>Fotografie pøiøazené k objektu</h3>
        <?
        echo $current_foto->show_list_header();
        while ($current_foto->get_next_radek()) {
            echo $current_foto->show_list_item("tabulka");
        }
        while ($current_foto2->get_next_radek()) {
            echo $current_foto2->show_list_item("tabulka");
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
        //get all Objekt kategorie
        $text = "";
        $sql = "select * from `objekt_kategorie` where `id_objektu` = \"" . $_GET["id_objektu"] . "\"";
        //echo $sql;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($row = mysqli_fetch_array($data)) {
            $text .= "<br/><a href=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;id_objekt_kategorie=" . $row["id_objekt_kategorie"] . "&amp;id_foto=[id_foto]&amp;typ=foto_ok&amp;pozadavek=create&amp;zakladni_foto=0\">pøidat k " . $row["nazev"] . "</a> ";
        }
        // echo $text;
        $foto_list->set_objekt_kategorie_name($text);
        //zobrazeni filtru pro vypis fotek
        echo $foto_list->show_filtr();
        ?>
        <h3>Seznam fotografií</h3>
        <?
        echo $foto_list->show_list_header();

        //zobrazeni jednotlivych zaznamu
        while ($foto_list->get_next_radek()) {
            echo $foto_list->show_list_item("tabulka_objekty");
        }
        ?>
        </table>
        <?
        //zobrazeni strankovani
        echo ModulView::showPaging($foto_list->getZacatek(), $foto_list->getPocetZajezdu(), $foto_list->getPocetZaznamu());
        
        
    } else if ($_GET["typ"] == "tok_list") {
        $objekty = new Objekty("edit", $zamestnanec->get_id(), $_GET["id_objektu"]);
        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
            <a href="?typ=objekty&amp;pozadavek=new">vytvoøit nový objekt</a>
        </div>
        <? 
        echo $objekty->show_submenu();       
        if($_GET["pozadavek"] == "show_letuska"){
            ?>
            <script type="text/javascript" src="./js/serial.js"></script>            
            <?php
            echo $objekty->show_form_kv_letuska();
            
        }else if($_GET["pozadavek"] == "show_goglobal"){
            ?>
            <script type="text/javascript" src="./js/serial.js"></script>            
            <?php
            echo $objekty->show_form_kv_goglobal();
            
        }else if($_GET["pozadavek"] == "kalkulacni_vzorce_vygenerovat_terminy"){
            ?>
            <script type="text/javascript" src="./js/serial.js"></script>            
            <?php
            echo $objekty->show_form_vygenerovane_terminy();            
            
        }else{
        
            /*vypis seznamu toku daneho serialu
                toku v serialu byva standartne max cca 20, takze strankovani ani filtrovani neni treba*/
            //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)

            if($_GET["pozadavek"]=="show_no_link"){
                $tok_list = new Tok_list($_GET["id_objektu"], $_GET["moznosti_editace"],"show_no_link");
            }else{
                $tok_list = new Tok_list($_GET["id_objektu"], $_GET["moznosti_editace"]);
            }
            //pokud nastala nejaka chyba, vypiseme chybovou hlasku...
            echo $tok_list->get_error_message();
            ?>
            <div class="submenu">
                <?php
                echo "<a href=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;typ=tok&amp;pozadavek=new\">vytvoøit nový termín objektovývch kategorií</a>";
                echo "<a href=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;typ=tok_list&amp;pozadavek=show_no_link\">zobrazit TOK bez pøiøazených služeb</a>";

                ?>
            </div>
            <?
            //nadpis seznamu


            echo $tok_list->show_header();

            echo "<form action=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;typ=tok&amp;pozadavek=mass_delete\" method=\"post\">";
            //hlavicka seznamu
            echo $tok_list->show_list_header();
            //vypis jednotlivych serialu
            while ($tok_list->get_next_radek()) {
                echo $tok_list->show_list_item("tabulka");
            }

            echo "</table>
                Zaškrtnuté TOK: <input type=\"submit\" value=\"Smazat!\" />";
            echo "</form>";
            ?>
            </table>
            <?
        }
        /*----------------	nový tok -----------*/
    } else if ($_GET["typ"] == "tok" and ($_GET["pozadavek"] == "new" or $_GET["pozadavek"] == "create")) {
        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
            <a href="?typ=objekty&amp;pozadavek=new">vytvoøit nový objekt</a>
        </div>
        <?
        $objekty = new Objekty("edit", $zamestnanec->get_id(), $_GET["id_objektu"]);
        //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
        echo $objekty->show_submenu();

        $tok = new Termin_objektove_kategorie("new", $zamestnanec->get_id(), $_GET["id_objektu"]);
        echo $tok->get_error_message();
        ?>
        <div class="submenu">
            <?
            echo "<a href=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;typ=tok_list\">&lt;&lt; seznam termínù</a>";
            ?>
        </div>
        <?
        //zobrazim formular pro editaci/vytvoreni noveho serialu
        ?><h3>Vytvoøit nový termín objektových kategorií</h3><?
        echo $tok->show_form();

        //pokud mame konkretni tok, vypiseme submenu pro toky
    } else if ($_GET["id_termin"]) {
        //vypisu menu
        ?>
        <div class="submenu">
            <a href="?typ=objekty_list">&lt;&lt; seznam objektù</a>
            <a href="?typ=objekty&amp;pozadavek=new">vytvoøit nový objekt</a>
        </div>
        <?
        $objekty = new Objekty("edit", $zamestnanec->get_id(), $_GET["id_objektu"]);
        echo $objekty->show_submenu();

        //podle typu pozadvku vytvorim instanci tridy serial
        if ($_GET["typ"] == "tok" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {
            $tok = new Termin_objektove_kategorie("edit", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_termin"]);
        } else {
            $tok = new Termin_objektove_kategorie("show", $zamestnanec->get_id(), $_GET["id_objektu"], $_GET["id_termin"]);
        }
        echo $tok->get_error_message();

        ?>
        <div class="submenu">
            <?
            echo $tok->show_tok_name();
            echo "<a href=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;typ=tok_list\">&lt;&lt; seznam termínù</a>
				    <a href=\"?id_objektu=" . $_GET["id_objektu"] . "&amp;typ=tok&amp;pozadavek=new\">vytvoøit nový termín objektových kategorií</a>";
            ?>
        </div>
        <?
        //vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
        //echo $tok->show_submenu();

        /*----------------	editace zájezdu -----------*/
        if ($_GET["typ"] == "tok" and ($_GET["pozadavek"] == "edit" or $_GET["pozadavek"] == "update")) {

            ?><h3>Editace termínù objektové kategorie</h3><?
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $tok->show_form();

        } else if ($_GET["typ"] == "tok" and $_GET["pozadavek"] == "show") {
            ?><h3>Termín objektových kategorií</h3><?
            //zobrazim formular pro editaci/vytvoreni noveho serialu
            echo $tok->show_list_header();
            while ($tok->get_next_radek()) {
                echo $tok->show_list_item("tabulka");
            }
            echo "</table>";
        }
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