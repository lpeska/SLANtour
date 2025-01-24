<?php

/**
 * klient.inc.php - tridy pro zobrazeni informcí o klientovi
 */
/* --------------------- SERIAL ------------------------------------------- */
class Klient extends Generic_data_class {

    //vstupni data
    protected $typ_pozadavku;
    protected $minuly_pozadavek; //nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
    protected $id_zamestnance;
    protected $id_klient;
    protected $jmeno;
    protected $prijmeni;
    protected $titul;
    protected $datum_narozeni;
    protected $rodne_cislo;
    protected $email;
    protected $telefon;
    protected $cislo_op;
    protected $cislo_pasu;
    protected $ulice;
    protected $mesto;
    protected $psc;
    protected $vytvoren_ck;
    protected $uzivatelske_jmeno;
    protected $salt;
    protected $heslo;
    protected $heslo2;
    protected $data;
    protected $user;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /* konstruktor tøídy na základì typu požadavku a formularovych poli */

    function __construct(
    $typ_pozadavku, $id_zamestnance, $id_klient, $jmeno = "", $prijmeni = "", $titul = "", $datum_narozeni = "", $rodne_cislo = "", $email = "", $telefon = "", $cislo_op = "", $cislo_pasu = "", $ulice = "", $mesto = "", $psc = "", $uzivatelske_jmeno = "", $minuly_pozadavek = ""
    ) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->minuly_pozadavek = $this->check($minuly_pozadavek);

        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_klient = $this->check_int($id_klient);
        if($this->typ_pozadavku=="create_ajax"){
            $this->jmeno = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $jmeno)));
            $this->prijmeni = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $prijmeni)));
            $this->titul = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $titul)));
            $this->datum_narozeni = $this->change_date_cz_en($this->check($datum_narozeni));
            $this->rodne_cislo = $this->check_slashes($this->check($rodne_cislo));

            $this->email = $this->check_slashes($this->check($email));
            $this->telefon = $this->check_slashes($this->check($telefon));
            $this->cislo_op = $this->check_slashes($this->check($cislo_op));
            $this->cislo_pasu = $this->check_slashes($this->check($cislo_pasu));
            $this->ulice = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $ulice)));
            $this->mesto = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $mesto)));
            $this->psc = $this->check_slashes($this->check($psc));
        }else if($this->typ_pozadavku=="update_form" or $this->typ_pozadavku=="create"){
            
            $this->jmeno = $this->check_slashes($this->check($jmeno));
            $this->prijmeni = $this->check_slashes($this->check( $prijmeni));
            $this->titul = $this->check_slashes($this->check( $titul));
            $this->datum_narozeni = $this->change_date_cz_en($this->check($datum_narozeni));
            $this->rodne_cislo = $this->check_slashes($this->check($rodne_cislo));

            $this->email = $this->check_slashes($this->check($email));
            $this->telefon = $this->check_slashes($this->check($telefon));
            $this->cislo_op = $this->check_slashes($this->check($cislo_op));
            $this->cislo_pasu = $this->check_slashes($this->check($cislo_pasu));
            $this->ulice = $this->check_slashes($this->check($ulice));
            $this->mesto = $this->check_slashes($this->check($mesto));
            $this->psc = $this->check_slashes($this->check($psc));
            
        }else{
            $this->jmeno = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $jmeno)));
            $this->prijmeni = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $prijmeni)));
            $this->titul = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $titul)));
            $this->datum_narozeni = $this->change_date_cz_en($this->check($datum_narozeni));
            $this->rodne_cislo = $this->check_slashes($this->check($rodne_cislo));

            $this->email = $this->check_slashes($this->check($email));
            $this->telefon = $this->check_slashes($this->check($telefon));
            $this->cislo_op = $this->check_slashes($this->check($cislo_op));
            $this->cislo_pasu = $this->check_slashes($this->check($cislo_pasu));
            $this->ulice = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $ulice)));
            $this->mesto = $this->check_slashes($this->check(iconv("UTF-8", "cp1250", $mesto)));
            $this->psc = $this->check_slashes($this->check($psc));
        }


        $this->uzivatelske_jmeno = $this->check_slashes(strtolower($this->check($uzivatelske_jmeno)));
        $this->nove_heslo = "";

        //pokud mam dostatecna prava pokracovat        
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            //pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
            if ($this->typ_pozadavku == "create_account") {
                //vygeneruju nahodne heslo
                $nahodny_retezec = sha1(mt_rand() . mt_rand());
                $nahodne_heslo = substr($nahodny_retezec, 1, mt_rand(6, 10));

                //echo $nahodne_heslo;
                //vytvorim nahodny retezec nahodne delky:)
                $nahodny_retezec = sha1(mt_rand() . mt_rand());
                $this->salt = substr($nahodny_retezec, 1, mt_rand(10, 20));

                //vytvorim nove heslo ktere pouziju do databaze
                $this->nove_heslo = sha1($nahodne_heslo . $this->salt);
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
            } else if ($this->typ_pozadavku == "create_ajax") {
                $this->data = $this->database->query($this->create_query("create"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
                $this->id_klient = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
            } else if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "update_form" or $this->typ_pozadavku == "delete") {
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }

                //pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
            } else if ($this->typ_pozadavku == "edit" and $this->minuly_pozadavek != "update") {
                //ziskam data o uzivateli
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->user = mysqli_fetch_array($this->data);

                //jednotlive sloupce ulozim do promennych tridy
                $this->jmeno = $this->user["jmeno"];
                $this->prijmeni = $this->user["prijmeni"];
                $this->titul = $this->user["titul"];
                $this->datum_narozeni = $this->user["datum_narozeni"];
                $this->rodne_cislo = $this->user["rodne_cislo"];

                $this->email = $this->user["email"];
                $this->telefon = $this->user["telefon"];
                $this->cislo_op = $this->user["cislo_op"];
                $this->cislo_pasu = $this->user["cislo_pasu"];
                $this->ulice = $this->user["ulice"];
                $this->mesto = $this->user["mesto"];
                $this->psc = $this->user["psc"];
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	
    /*     * vytvoreni dotazu na zaklade typu pozadavku */
    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "create") {
            $dotaz = "INSERT INTO `user_klient` 
							(`jmeno`,`prijmeni`, `titul`, `datum_narozeni`,`rodne_cislo`,`email`,`telefon`,
							`cislo_op`,`cislo_pasu`,`ulice`,`mesto`,`psc`,
							`vytvoren_ck`, `id_user_create`,`id_user_edit`)
						VALUES
							 ('" . $this->jmeno . "','" . $this->prijmeni . "', '$this->titul', '" . $this->datum_narozeni . "','" . $this->rodne_cislo . "','" . $this->email . "','" . $this->telefon . "',
							 '" . $this->cislo_op . "','" . $this->cislo_pasu . "','" . $this->ulice . "','" . $this->mesto . "','" . $this->psc . "',
							  1," . $this->id_zamestnance . "," . $this->id_zamestnance . ")";
//			echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "update" or $typ_pozadavku == "update_form") {
            $dotaz = "UPDATE `user_klient` 
                        SET
                                 `jmeno`='" . $this->jmeno . "',`prijmeni`='" . $this->prijmeni . "', `titul`='$this->titul', `datum_narozeni`='" . $this->datum_narozeni . "',`rodne_cislo`='" . $this->rodne_cislo . "',
                                 `email`='" . $this->email . "',`telefon`='" . $this->telefon . "',`cislo_op`='" . $this->cislo_op . "',`cislo_pasu`='" . $this->cislo_pasu . "',
                                 `ulice`='" . $this->ulice . "',`mesto`='" . $this->mesto . "',`psc`='" . $this->psc . "',
                                 `id_user_edit`= " . $this->id_zamestnance . "
                        WHERE `id_klient`=" . $this->id_klient . "
                        LIMIT 1";
//            echo "$dotaz<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "show_objednavky") {
            $dotaz = " ( SELECT `serial`.`id_serial`, `serial`.`nazev`, `zajezd`.`od`, `zajezd`.`do`, `ubytovani`.`nazev` as `nazev_ubytovani`,
                                        `objednavka`.*,
                                        `klient`.`id_klient`,`klient`.`jmeno`, `klient`.`prijmeni`, 
                                        `organizace`.`id_organizace` as `id_agentura`,`organizace`.`ico` as `kontaktni_osoba`, `organizace`.`nazev` as `nazev_ca`,
                                        0 as `id_osoba`, \"\" as  `osoba_jmeno`, \"\" as `osoba_prijmeni` 
                                                FROM `serial`                                                    
                                                    join `objednavka` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
                                                    join `user_klient` as `klient` on (`objednavka`.`id_klient` = `klient`.`id_klient`)
                                                    left join `organizace` on (`objednavka`.`id_agentury` = `organizace`.`id_organizace`)
                                                    
                                                     join `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                                    left join `ubytovani` on  (`serial`.`id_ubytovani`= `ubytovani`.`id_ubytovani`)
						WHERE `klient`.`id_klient`=" . $this->id_klient . ")
                                                    
                                    union all
                                 ( SELECT `serial`.`id_serial`, `serial`.`nazev`, `zajezd`.`od`, `zajezd`.`do`, `ubytovani`.`nazev` as `nazev_ubytovani`,
                                        `objednavka`.*,
                                        `klient`.`id_klient`,`klient`.`jmeno`, `klient`.`prijmeni`, 
                                        `organizace`.`id_organizace` as `id_agentura`,`organizace`.`ico` as `kontaktni_osoba`, `organizace`.`nazev` as `nazev_ca`,
                                        `osoba`.`id_klient` as `id_osoba` ,`osoba`.`jmeno`as  `osoba_jmeno`, `osoba`.`prijmeni` as `osoba_prijmeni`
                                                FROM `serial`                                                    
                                                    join `objednavka` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
                                                    left join `user_klient` as `klient` on (`objednavka`.`id_klient` = `klient`.`id_klient`)
                                                    left join `organizace` on (`objednavka`.`id_agentury` = `organizace`.`id_organizace`)
                                                     join (`objednavka_osoby` 
                                                         join `user_klient` as `osoba` on (`objednavka_osoby`.`id_klient` = `osoba`.`id_klient`))
                                                         on(`objednavka_osoby`.`id_objednavka` = `objednavka`.`id_objednavka`)
                                                     join `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                                    left join `ubytovani` on  (`serial`.`id_ubytovani`= `ubytovani`.`id_ubytovani`)
						WHERE `osoba`.`id_klient`=" . $this->id_klient . ")
                                                    order by `od` desc
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "create_account") {
            $dotaz = "UPDATE `user_klient` 
						SET
							 `uzivatelske_jmeno`='" . $this->uzivatelske_jmeno . "',`heslo_sha1`='" . $this->nove_heslo . "',`salt`='" . $this->salt . "',
							 `vytvoren_ck`= 0, `id_user_edit`= " . $this->id_zamestnance . "
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "edit") {
            $dotaz = "SELECT * FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
    }

    /*     * kontrola zda smim provest danou akci */

    function legal($typ_pozadavku) {
        $zamestnanec = User_zamestnanec::get_instance();
        //z jadra zjistim ide soucasneho modulu
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        //podle jednotlivych typu pozadavku
        if ($typ_pozadavku == "new") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "edit") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "show") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "create") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "create_ajax") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "create_account") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "update") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "update_form") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }    
        } else if ($typ_pozadavku == "delete") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /*     * kontrola zda mam odpovidajici data */

    function correct_data($typ_pozadavku) {
        $ok = 1;
        //kontrolovaná data: název seriálu, popisek,  id_typ,
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "update_form") {
            if (!Validace::text($this->jmeno)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit jméno");
            }
            if (!Validace::text($this->prijmeni)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit pøíjmení");
            }
          /*  if (!Validace::datum_en($this->datum_narozeni)) {
                $ok = 0;
                $this->chyba("Datum narození musí být ve formátu dd.mm.rrrr");
            }*/
            //pokud jde o objednatele zajezdu musim validovat i email a adresu
            if ($_GET["owner"] == "true") {
               /* if (!Validace::email($this->email)) {
                    $ok = 0;
                    $this->chyba("špatnì vyplnìný e-mail");
                }
                if (!Validace::text($this->ulice)) {
                    $ok = 0;
                    $this->chyba("Musíte vyplnit ulici a èíslo popisné");
                }
                if (!Validace::text($this->mesto)) {
                    $ok = 0;
                    $this->chyba("Musíte vyplnit mìsto");
                }
                if (!Validace::text($this->psc)) {
                    $ok = 0;
                    $this->chyba("Musíte vyplnit PSÈ");
                }*/
            }
        }
        if ($typ_pozadavku == "create_account") {
            if (!Validace::text($this->uzivatelske_jmeno)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit uživatelské jméno");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    function show_objednavky() {
        $data = $this->database->query($this->create_query("show_objednavky"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $result = "<table class=\"list\">
                <tr>
                    <th>Id objednávky
                    <th>Zájezd
                    <th>Objednávající
                    <th>Prodejce
                    <th>Celková cena
                    <th>Stav
                    <th>Typ vztahu
                    ";
        while ($row = mysqli_fetch_array($data)) {
            if ($row["id_osoba"] == $this->id_klient) {
                $vztah = "Pøihlášená osoba";
            } else if ($row["id_agentura"] == $this->id_klient) {
                $vztah = "Prodejce";
            } else {
                $vztah = "Objednávající";
            }

            $result.="
                    <tr class=\"suda\">
                        <td><a href=\"objednavky.php?idObjednavka=" . $row["id_objednavka"] . "\">" . $row["id_objednavka"] . "</a>
                        <td>" . $row["nazev"] . " " . $row["nazev_ubytovani"] . "<br/> " . $row["nazev_zajezdu"] . " " . $row["od"] . " - " . $row["do"] . "
                        <td><a href=\"klienti.php?id_klient=" . $row["id_klient"] . "&typ=klient&pozadavek=edit\">" . $row["jmeno"] . " " . $row["prijmeni"] . "</a>
                        <td><a href=\"organizace.php?id_organizace=" . $row["id_agentura"] . "&typ=organizace&pozadavek=edit\">" . $row["nazev_ca"] . " " . $row["kontaktni_osoba"] . "</a>
                        <td>" . $row["celkova_cena"] . " Kè
                        <td>" . Rezervace_library::get_stav(($row["stav"] - 1)) . "
                        <td>" . $vztah;
        }
        $result.="</table>";
        return $result;
    }

    /*     * zobrazeni formulare pro pristup k vlastnimu uctu */

    function show_account_form() {

        //vytvorim jednotliva pole
        $username = "	<div class=\"form_row\"> <div class=\"label_float_left\">uživatelské jméno:</div> <div class=\"value\"><input name=\"uzivatelske_jmeno\" type=\"text\" value=\"" . $this->prijmeni . "\" class=\"wide\"/></div></div>\n";
        $heslo = "<div class=\"form_row\"> <div class=\"label_float_left\">Heslo:</div> <div class=\"value\">Heslo je generováno automaticky a je odesláno na e-mail uživatele</div></div>\n";

        $action = "?id_klient=" . $this->id_klient . "&amp;typ=klient&amp;pozadavek=create_account";

        //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni editovat dokument!
        //tzn pokud bud ma pravo editovat libovolny dokument, nebo pokud je to jeho dokument a on ma pravo ho editovat
        if ($this->legal("create_account")) {
            $submit = "<input type=\"submit\" value=\"Vytvoøit úèet\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
        } else {
            $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení úètu klientovi</strong>\n";
        }

        $vystup = "<form action=\"" . $action . "\" method=\"post\">" .
                $username . $heslo .
                $submit .
                "</form>";
        return $vystup;
    }

    /*     * zobrazeni formulare pro vytvoreni/editaci uzivatele */

    function show_form() {

        //vytvorim jednotliva pole
        $id = "	<div class=\"form_row\"> <div class=\"label_float_left\">id: </div> <div class=\"value\"><div class=\"wide\">$this->id_klient</div></div></div>\n";
        $jmeno = "	<div class=\"form_row\"> <div class=\"label_float_left\">jméno: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"jmeno\" type=\"text\" value=\"" . $this->jmeno . "\" class=\"wide\"/></div></div>\n";
        $prijmeni = "	<div class=\"form_row\"> <div class=\"label_float_left\">pøíjmení: <span class=\"red\">*</span></div> <div class=\"value\"><input name=\"prijmeni\" type=\"text\" value=\"" . $this->prijmeni . "\" class=\"wide\"/></div></div>\n";
        $titul = "	<div class=\"form_row\"> <div class=\"label_float_left\">titul: </div> <div class=\"value\"><input name=\"titul\" type=\"text\" value=\"" . $this->titul . "\" class=\"wide\"/></div></div>\n";
        $datum_narozeni = " <div class=\"form_row\"> <div class=\"label_float_left\">datum narození: </div> <div class=\"value\"><input  name=\"datum_narozeni\" type=\"text\" value=\"" . $this->change_date_en_cz($this->datum_narozeni) . "\" /></div></div>\n";
        $rodne_cislo = "	<div class=\"form_row\"> <div class=\"label_float_left\">rodné èíslo:</div> <div class=\"value\"><input  name=\"rodne_cislo\" type=\"text\" value=\"" . $this->rodne_cislo . "\" /></div></div>\n";
        $email = " <div class=\"form_row\"> <div class=\"label_float_left\">e-mail: </div> <div class=\"value\"><input  name=\"email\" type=\"text\" value=\"" . $this->email . "\" /></div></div>\n";
        $telefon = " <div class=\"form_row\"> <div class=\"label_float_left\">telefon:</div> <div class=\"value\"><input  name=\"telefon\" type=\"cislo_pasu\" value=\"" . $this->telefon . "\" /></div></div>\n";
        $c_op = " <div class=\"form_row\"> <div class=\"label_float_left\">èíslo obèanského prùkazu:</div> <div class=\"value\"><input  name=\"cislo_op\" type=\"text\" value=\"" . $this->cislo_op . "\" /></div></div>\n";
        $c_pasu = " <div class=\"form_row\"> <div class=\"label_float_left\">èíslo pasu:</div> <div class=\"value\"><input  name=\"cislo_pasu\" type=\"cislo_pasu\" value=\"" . $this->cislo_pasu . "\" /></div></div>\n";
        $ulice = " <div class=\"form_row\"> <div class=\"label_float_left\">ulice: </div> <div class=\"value\"><input  name=\"ulice\" type=\"ulice\" value=\"" . $this->ulice . "\" /></div></div>\n";
        $mesto = "	<div class=\"form_row\"> <div class=\"label_float_left\">mìsto: </div> <div class=\"value\"><input  name=\"mesto\" type=\"mesto\" value=\"" . $this->mesto . "\" /></div></div>\n";
        $psc = " <div class=\"form_row\"> <div class=\"label_float_left\">psc: </div> <div class=\"value\"><input  name=\"psc\" type=\"psc\" value=\"" . $this->psc . "\" /></div></div>\n";

        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=klient&amp;pozadavek=create&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit = "<input type=\"submit\" value=\"Vytvoøit klienta\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení klienta</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {
            //cil formulare
            $action = "?id_klient=" . $this->id_klient . "&amp;typ=klient&amp;pozadavek=update_form&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";

            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit klienta\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateìné oprávnìní k editaci tohoto klienta</strong>\n";
            }
        }


        $vystup = "<form action=\"" . $action . "\" method=\"post\">" .
                $id . $prijmeni . $jmeno . $titul . $datum_narozeni . $rodne_cislo . $email . $telefon . $c_op . $c_pasu . $ulice . $mesto . $psc .
                $submit .
                "</form>";
        return $vystup;
    }

    function get_id() {
        return $this->informace["id_informace"];
    }

    function get_nazev() {
        return $this->informace["nazev"];
    }

    function get_id_klient() {
        return $this->id_klient;
    }

    function get_id_user_create() {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_klient == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }

}

?>
