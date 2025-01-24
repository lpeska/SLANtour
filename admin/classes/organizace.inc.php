<?php
/**
 * klient.inc.php - tridy pro zobrazeni informcí o klientovi
 */

/*--------------------- SERIAL -------------------------------------------*/
class Organizace extends Generic_data_class
{
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
    /*konstruktor tøídy na základì typu požadavku a formularovych poli*/
    function __construct(
        $typ_pozadavku, $id_zamestnance, $id_organizace = ""
    )
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_organizace = $this->check_int($id_organizace);
        $this->typ_pozadavku = $this->check($typ_pozadavku);


        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update") {
                //pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
                //create organizace
                $this->nazev = $this->check($_POST["nazev"]);
                $this->ico = $this->check($_POST["ico"]);
                $this->dic = $this->check($_POST["dic"]);
                $this->role = $this->check_int($_POST["role"]);

                if ($this->typ_pozadavku == "create") {
                    $data = $this->database->transaction_query($this->create_query("create_organizace"), 1)
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $this->id_organizace = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);

                } else if ($this->typ_pozadavku == "update") {
                    // print_r($_POST);
                    //upravit data o organizaci
                    $data = $this->database->transaction_query($this->create_query("update_organizace"), 1)
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    //smazat vsechny stara data
                    $this->deleteDataOrganizace();
                }

                $max_rows = 50;

                //nejdriv adresy
                for ($i = 1; $i < $max_rows; $i++) {
                    if (isset ($_POST["adresa_typ_" . $i])) {
                        $this->adresa["stat"] = $this->check($_POST["stat_" . $i]);
                        $this->adresa["mesto"] = $this->check($_POST["mesto_" . $i]);
                        $this->adresa["ulice"] = $this->check($_POST["ulice_" . $i]);
                        $this->adresa["psc"] = $this->check($_POST["psc_" . $i]);
                        $this->adresa["poznamka"] = $this->check($_POST["adresa_poznamka_" . $i]);
                        $this->adresa["typ_kontaktu"] = $this->check_int($_POST["adresa_typ_" . $i]);
                        $this->adresa["lat"] = $this->check_double($_POST["lat_" . $i]);
                        $this->adresa["lng"] = $this->check_double($_POST["lng_" . $i]);

                        if ($this->adresa["mesto"] != "" or $this->adresa["psc"] != "" or $this->adresa["ulice"] != "") {
                            $this->database->transaction_query($this->create_query("create_adresa_organizace"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
                    }
                }

                //bankovni spojeni
                for ($i = 1; $i < $max_rows; $i++) {
                    if (isset ($_POST["banka_typ_" . $i])) {
                        $this->bank_spojeni["nazev_banky"] = $this->check($_POST["nazev_banky_" . $i]);
                        $this->bank_spojeni["kod_banky"] = $this->check($_POST["kod_banky_" . $i]);
                        $this->bank_spojeni["cislo_uctu"] = $this->check($_POST["cislo_uctu_" . $i]);
                        $this->bank_spojeni["poznamka"] = $this->check($_POST["banka_poznamka_" . $i]);
                        $this->bank_spojeni["typ_kontaktu"] = $this->check_int($_POST["banka_typ_" . $i]);

                        if ($this->bank_spojeni["kod_banky"] != "" and $this->bank_spojeni["cislo_uctu"] != "") {
                            $this->database->transaction_query($this->create_query("create_bankovni_spojeni_organizace"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
                    }
                }

                //kontakty
                for ($i = 0; $i < $max_rows; $i++) {
                    if (isset ($_POST["kontakt_typ_" . $i])) {
                        $this->kontakt["www"] = $this->check($_POST["web_" . $i]);
                        $this->kontakt["telefon"] = $this->check($_POST["telefon_" . $i]);
                        $this->kontakt["email"] = $this->check($_POST["email_" . $i]);
                        $this->kontakt["poznamka"] = $this->check($_POST["kontakt_poznamka_" . $i]);
                        $this->kontakt["typ_kontaktu"] = $this->check_int($_POST["kontakt_typ_" . $i]);

                        if ($this->kontakt["www"] != "") {
                            $this->database->transaction_query($this->create_query("create_web_organizace"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
                        if ($this->kontakt["telefon"] != "") {
                            $this->database->transaction_query($this->create_query("create_telefon_organizace"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
                        if ($this->kontakt["email"] != "") {
                            $this->database->transaction_query($this->create_query("create_email_organizace"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
                    }
                }

                if ($this->role == 1) {
                    if ($_POST["uzivatelske_jmeno"] != "" || $_POST["heslo"] != "" || $_POST["koeficient_prodejce"] != "") {
                        //vytvorit prodejce
                        $this->prodejce["provizni_koeficient"] = $this->check(str_replace(",", ".",$_POST["koeficient_prodejce"]));
                        $this->prodejce["uzivatelske_jmeno"] = $this->check($_POST["uzivatelske_jmeno"]);
                        $this->prodejce["heslo"] = $this->check($_POST["heslo"]);
                        $this->prodejce["last_logon"] = $this->check($_POST["last_logon"]);
                        if($this->prodejce["uzivatelske_jmeno"]!=""){
                            if ($_POST["heslo_sha1"] != "" and $_POST["heslo"] == "") {
                                $this->prodejce["heslo_sha1"] = $this->check($_POST["heslo_sha1"]);
                                $this->prodejce["salt"] = $this->check($_POST["salt"]);
                            } else {
                                $nahodny_retezec = sha1(mt_rand() . mt_rand());
                                $this->prodejce["salt"] = substr($nahodny_retezec, 1, mt_rand(10, 20));
                                $this->prodejce["heslo_sha1"] = sha1($this->prodejce["heslo"] . $this->prodejce["salt"]);
                            }
                            $this->database->transaction_query($this->create_query("create_prodejce_organizace"))
                                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }else{
                            $this->database->transaction_query($this->create_query("create_prodejce_organizace_no_account"))
                                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
                        
                    }

                } else if ($this->role == 2) {
                    //vytvorit ubytovani
                    $this->ubytovani["id_ubytovani"] = $this->check_int($_POST["id_ubytovani"]);
                    $this->ubytovani["typ_kontaktu"] = 1;
                    if($this->ubytovani["id_ubytovani"]>0){
                        $this->database->transaction_query($this->create_query("create_ubytovani_organizace"))                    
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    }    
                }

                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->database->commit();
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                } else {
                    $this->database->rollback();
                }

                //pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
            } else if ($this->typ_pozadavku == "edit" and $this->minuly_pozadavek != "update") {

            } else if ($this->typ_pozadavku == "delete") {
                $this->database->query($this->create_query("delete"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }


    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    function create_query($typ_pozadavku)
    {

        /*vytvoreni organizace*/
        if ($typ_pozadavku == "create_organizace") {

            $dotaz = "INSERT INTO `organizace`
							(`nazev`,`ico`,`dic`,`role`,`last_change`, `id_user_create`,`id_user_edit`)
						VALUES
							 ('" . $this->nazev . "','" . $this->ico . "','" . $this->dic . "'," . $this->role . ",'" . Date("Y-m-d") . "'
                                                             ," . $this->id_zamestnance . "," . $this->id_zamestnance . ")";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "update_organizace") {

            $dotaz = "UPDATE `organizace`  set
							`nazev`='" . $this->nazev . "',`ico`='" . $this->ico . "',`dic`='" . $this->dic . "',`role` = " . $this->role . ",
                                                            `last_change`='" . Date("Y-m-d") . "', `id_user_edit`=" . $this->id_zamestnance . "
						where
                                                   `id_organizace` = " . $this->id_organizace . "
                                                limit 1       ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "create_adresa_organizace") {

            $dotaz = "INSERT INTO `organizace_adresa`
							(`id_organizace`,`stat`,`mesto`,`ulice`,`psc`, `typ_kontaktu`,`poznamka`,`lat`,`lng`)
						VALUES
							 (" . $this->id_organizace . ",'" . $this->adresa["stat"] . "','" . $this->adresa["mesto"] . "','" . $this->adresa["ulice"] . "','" . $this->adresa["psc"] . "'
                                                             ," . $this->adresa["typ_kontaktu"] . ",'" . $this->adresa["poznamka"] . "','" . $this->adresa["lat"] . "','" . $this->adresa["lng"] . "')";
            //echo $dotaz . "<br/>";
            return $dotaz;

        } else if ($typ_pozadavku == "create_web_organizace") {

            $dotaz = "INSERT INTO `organizace_www`
							(`id_organizace`,`www`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (" . $this->id_organizace . ",'" . $this->kontakt["www"] . "'
                                                             ," . $this->kontakt["typ_kontaktu"] . ",'" . $this->kontakt["poznamka"] . "')";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "create_telefon_organizace") {

            $dotaz = "INSERT INTO `organizace_telefon`
							(`id_organizace`,`telefon`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (" . $this->id_organizace . ",'" . $this->kontakt["telefon"] . "'
                                                             ," . $this->kontakt["typ_kontaktu"] . ",'" . $this->kontakt["poznamka"] . "')";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "create_email_organizace") {

            $dotaz = "INSERT INTO `organizace_email`
							(`id_organizace`,`email`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (" . $this->id_organizace . ",'" . $this->kontakt["email"] . "'
                                                             ," . $this->kontakt["typ_kontaktu"] . ",'" . $this->kontakt["poznamka"] . "')";
            //echo $dotaz . "<br/>";
            return $dotaz;

        } else if ($typ_pozadavku == "create_bankovni_spojeni_organizace") {

            $dotaz = "INSERT INTO `organizace_bankovni_spojeni`
							(`id_organizace`,`nazev_banky`,`kod_banky`,`cislo_uctu`,`typ_kontaktu`,`poznamka`)
						VALUES
							 (" . $this->id_organizace . ",'" . $this->bank_spojeni["nazev_banky"] . "','" . $this->bank_spojeni["kod_banky"] . "','" . $this->bank_spojeni["cislo_uctu"] . "'
                                                             ," . $this->bank_spojeni["typ_kontaktu"] . ",'" . $this->bank_spojeni["poznamka"] . "')";
            //echo $dotaz . "<br/>";
            return $dotaz;

        } else if ($typ_pozadavku == "create_prodejce_organizace") {
            $dotaz = "INSERT INTO `prodejce`
							(`id_organizace`,`provizni_koeficient`,`uzivatelske_jmeno`,`heslo`,`heslo_sha1`,`salt`,`last_logon`,`ucet_potvrzen`)
						VALUES
							 (" . $this->id_organizace . "," . $this->prodejce["provizni_koeficient"] . ",'" . $this->prodejce["uzivatelske_jmeno"] . "'
                                                             ,'" . $this->prodejce["heslo"] . "','" . $this->prodejce["heslo_sha1"] . "','" . $this->prodejce["salt"] . "'
                                                             ,'" . $this->prodejce["last_logon"] . "',1)";
            //echo $dotaz . "<br/>";
            return $dotaz;
            
        } else if ($typ_pozadavku == "create_prodejce_organizace_no_account") {
            $dotaz = "INSERT INTO `prodejce`
							(`id_organizace`,`provizni_koeficient`,`ucet_potvrzen`)
						VALUES
							 (" . $this->id_organizace . "," . $this->prodejce["provizni_koeficient"] . ",0)";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "create_ubytovani_organizace") {
            $dotaz = "INSERT INTO `organizace_ubytovani`
							(`id_organizace`,`id_ubytovani`,`typ_kontaktu`)
						VALUES
							 (" . $this->id_organizace . "," . $this->ubytovani["id_ubytovani"] . "," . $this->ubytovani["typ_kontaktu"] . " )";
            //echo $dotaz . "<br/>";
            return $dotaz;


            /*dotazy pro smazani pri updatu*/
        } else if ($typ_pozadavku == "delete_adresy_organizace") {
            $dotaz = "Delete FROM `organizace_adresa`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_bankovni_spojeni_organizace") {
            $dotaz = "Delete FROM `organizace_bankovni_spojeni`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_web_organizace") {
            $dotaz = "Delete FROM `organizace_www`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_telefon_organizace") {
            $dotaz = "Delete FROM `organizace_telefon`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_email_organizace") {
            $dotaz = "Delete FROM `organizace_email`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_prodejce_organizace") {
            $dotaz = "Delete FROM `prodejce`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete_ubytovani_organizace") {
            $dotaz = "Delete FROM `organizace_ubytovani`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;


            /*dotazy pro zobrazeni a editaci*/
        } else if ($typ_pozadavku == "show") {
            $dotaz = "SELECT * FROM `organizace`
						WHERE `id_organizace`=" . $this->id_organizace . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_adresy") {
            $dotaz = "SELECT * FROM `organizace_adresa`
						WHERE `id_organizace`=" . $this->id_organizace . "
						order by `typ_kontaktu`";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_bankovni_spojeni") {
            $dotaz = "SELECT * FROM `organizace_bankovni_spojeni`
						WHERE `id_organizace`=" . $this->id_organizace . "
						order by `typ_kontaktu`";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_web") {
            $dotaz = "SELECT * FROM `organizace_www`
						WHERE `id_organizace`=" . $this->id_organizace . "
						order by `typ_kontaktu`";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_telefon") {
            $dotaz = "SELECT * FROM `organizace_telefon`
						WHERE `id_organizace`=" . $this->id_organizace . "
						order by `typ_kontaktu`";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_email") {
            $dotaz = "SELECT * FROM `organizace_email`
						WHERE `id_organizace`=" . $this->id_organizace . "
						order by `typ_kontaktu`";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_prodejce") {
            $dotaz = "SELECT * FROM `prodejce`
						WHERE `id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show_ubytovani") {
            $dotaz = "SELECT * FROM `objekt` join
                                                `organizace_ubytovani` on (`objekt`.`id_objektu` = `organizace_ubytovani`.`id_ubytovani`)
                                             join `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu` and `objekt`.`typ_objektu`=1)   
						WHERE `organizace_ubytovani`.`id_organizace`=" . $this->id_organizace . "
						";
            //echo $dotaz;
            return $dotaz;


        } else if ($typ_pozadavku == "select_ubytovani") {
            $dotaz = "SELECT * FROM `objekt` join `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu` and `objekt`.`typ_objektu`=1)
						WHERE 1
						order by `nazev_ubytovani`,`objekt`.`id_objektu`";
            //echo $dotaz;
            return $dotaz;


        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `organizace`
						WHERE `id_organizace`=" . $this->id_organizace . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `organizace`
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
    }

    private function deleteDataOrganizace()
    {
        $data = $this->database->transaction_query($this->create_query("delete_adresy_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $data = $this->database->transaction_query($this->create_query("delete_bankovni_spojeni_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $data = $this->database->transaction_query($this->create_query("delete_web_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $data = $this->database->transaction_query($this->create_query("delete_telefon_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $data = $this->database->transaction_query($this->create_query("delete_email_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $data = $this->database->transaction_query($this->create_query("delete_prodejce_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $data = $this->database->transaction_query($this->create_query("delete_ubytovani_organizace"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
    }

    /**kontrola zda smim provest danou akci*/
    function legal($typ_pozadavku)
    {
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
                ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }

        } else if ($typ_pozadavku == "update") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }

        } else if ($typ_pozadavku == "delete") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /**kontrola zda mam odpovidajici data*/
    function correct_data($typ_pozadavku)
    {
        $ok = 1;
        //kontrolovaná data: název seriálu, popisek,  id_typ,
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {
            if (!Validace::text($_POST["nazev"])) {
                $ok = 0;
                $this->chyba("Musíte vyplnit název organizace");
            }
            if (!Validace::int($_POST["role"])) {
                $ok = 0;
                $this->chyba("Musíte vyplnit roli organizace");
            }

        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    function show_objednavky()
    {
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
            if ($row["id_klient"] == $this->id_klient) {
                $vztah = "Objednávající";
            } else if ($row["id_agentura"] == $this->id_klient) {
                $vztah = "Prodejce";
            } else {
                $vztah = "Pøihlášená osoba";
            }

            $result .= "
                    <tr class=\"suda\">
                        <td><a href=\"rezervace.php?id_objednavka=" . $row["id_objednavka"] . "&typ=rezervace&pozadavek=show\">" . $row["id_objednavka"] . "</a>
                        <td>" . $row["nazev"] . " " . $row["nazev_ubytovani"] . "<br/> " . $row["nazev_zajezdu"] . " " . $row["od"] . " - " . $row["do"] . "
                        <td><a href=\"klienti.php?id_klient=" . $row["id_klient"] . "&typ=klient&pozadavek=edit\">" . $row["jmeno"] . " " . $row["prijmeni"] . "</a>
                        <td><a href=\"klienti.php?id_klient=" . $row["id_agentura"] . "&typ=klient&pozadavek=edit\">" . $row["nazev_ca"] . " " . $row["kontaktni_osoba"] . "</a>
                        <td>" . $row["celkova_cena"] . " Kè
                        <td>" . Rezervace_library::get_stav(($row["stav"] - 1)) . "
                        <td>" . $vztah;
        }
        $result .= "</table>";
        return $result;
    }

    function showSelectUbytovani($id_ubytovani = 0)
    {
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("select_ubytovani"));
        $result = "<select name=\"id_ubytovani\"><option value=\"\">---</option>";
        while ($row = mysqli_fetch_array($data)) {
            if ($id_ubytovani == $row["id_objektu"]) {
                $select = "selected=\"selected\"";
            } else {
                $select = "";
            }
            $result .= "<option value=\"" . $row["id_objektu"] . "\" " . $select . ">" . $row["nazev_ubytovani"] . "</option>";
        }
        $result .= "</select>";
        return $result;
    }


    /**zobrazeni formulare pro vytvoreni/editaci uzivatele*/
    function show_edit_form()
    {
        //nazev, ico, role
        $organizace_list = $this->database->query($this->create_query("show"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $organizace = mysqli_fetch_array($organizace_list);

        $role_organizace = "<select id=\"role_organizace\" name=\"role\" onchange=\"showSpecial();\">";
        $i = 1;
        while (Serial_library::get_typ_organizace($i) != "") {
            if ($organizace["role"] == $i) {
                $selected_role = "selected=\"selected\"";
            } else {
                $selected_role = "";
            }
            $role_organizace .= "<option value=\"" . $i . "\" " . $selected_role . ">" . Serial_library::get_typ_organizace($i) . "</option>";
            $i++;
        }
        $role_organizace .= "</select>";

        $povinne_udaje = "
                <div class='form_row'>
                    <div class='label_float_left'>Název organizace</div><div class='value'><input name=\"nazev\" type=\"text\" value=\"" . $organizace["nazev"] . "\" class=\"width-350px\"/></div>
                </div>
                <div class='form_row'>
                    <div class='label_float_left'>IÈO</div><div class='value'><input name=\"ico\" type=\"text\" value=\"" . $organizace["ico"] . "\" class=\"wide\"/></div>
                </div>
                <div class='form_row'>
                    <div class='label_float_left'>DIÈ</div><div class='value'><input name=\"dic\" type=\"text\" value=\"" . $organizace["dic"] . "\" class=\"wide\"/></div>
                </div>
                <div class='form_row'>
                    <div class='label_float_left'>Role</div><div class='value'>" . $role_organizace . "</div>
                </div>
                <div id=\"special_text\">
                </div>
                ";


        $adresy = "
                <div id=\"adresy\">
                <h3>Adresy</h3>
                ";
        $adresa_list = $this->database->query($this->create_query("show_adresy"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $i = 0;
        $mame_sidlo = false;
        $mame_kontaktni = false;
        while ($adresa = mysqli_fetch_array($adresa_list)) {
            $i++;
            if ($adresa["typ_kontaktu"] == 1) {
                $mame_sidlo = true;
            } else if ($adresa["typ_kontaktu"] == 2) {
                $mame_kontaktni = true;
            }
            $adresy .= "<h4>" . Serial_library::get_typ_adresy($adresa["typ_kontaktu"]) . "</h4><input type=\"hidden\" name=\"adresa_typ_$i\" value=\"" . $adresa["typ_kontaktu"] . "\" />
                    <div class='edit_wrapper'>
                        <div class='left_edit_box'>
                            <div class='form_row'>
                            <div class='label_float_left'>Stát</div><div class='value'><input name=\"stat_$i\" type=\"text\" value=\"" . $adresa["stat"] . "\"  class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>Mìsto</div><div class='value'><input name=\"mesto_$i\" type=\"text\" value=\"" . $adresa["mesto"] . "\"  class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>Ulice a ÈP</div><div class='value'><input name=\"ulice_$i\" type=\"text\" value=\"" . $adresa["ulice"] . "\"  class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>PSÈ</div><div class='value'><input name=\"psc_$i\" type=\"text\" value=\"" . $adresa["psc"] . "\"  class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>poznámka</div><div class='value'><input name=\"adresa_poznamka_$i\" type=\"text\" value=\"" . $adresa["poznamka"] . "\"  class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>zemìpisná šíøka</div><div class='value'><input name=\"lat_$i\" type=\"text\" value=\"" . $adresa["lat"] . "\" /></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>zemìpisná délka</div><div class='value'><input name=\"lng_$i\" type=\"text\" value=\"" . $adresa["lng"] . "\" /></div></div>
                        </div>
                        <div class='right_edit_box'>
                            <div class='form_box'>
                                <input type='submit' class='search-adr' value='Najít adresu na mapì' />
                                <div class='map_canvas'></div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>";
        }
        //k adresam musime pridat sidlo a kontaktni adresu - pokud nebyla vyplnena
        if (!$mame_sidlo) {
            $i++;
            $adresy .= "<h4>Sídlo spoleènosti</h4><input type=\"hidden\" name=\"adresa_typ_$i\" value=\"1\" />
                    <div class='edit_wrapper'>
                        <div class='left_edit_box'>
                            <div class='form_row'>
                            <div class='label_float_left'>Stát</div><div class='value'><input name=\"stat_$i\" type=\"text\" value=\"" . $_POST["stat_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>Mìsto</div><div class='value'><input name=\"mesto_$i\" type=\"text\" value=\"" . $_POST["mesto_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>Ulice a ÈP</div><div class='value'><input name=\"ulice_$i\" type=\"text\" value=\"" . $_POST["ulice_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>PSÈ</div><div class='value'><input name=\"psc_$i\" type=\"text\" value=\"" . $_POST["psc_$i"] . "\" class=\"width-350px\"/>   </div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>poznámka</div><div class='value'><input name=\"adresa_poznamka_$i\" type=\"text\" value=\"" . $_POST["adresa_poznamka_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>zemìpisná šíøka</div><div class='value'><input name=\"lat_$i\" type=\"text\" value=\"" . $adresa["lat"] . "\" /></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>zemìpisná délka</div><div class='value'><input name=\"lng_$i\" type=\"text\" value=\"" . $adresa["lng"] . "\" /></div></div>
                        </div>
                        <div class='right_edit_box'>
                            <div class='form_box'>
                                <input type='submit' class='search-adr' value='Najít adresu na mapì' />
                                <div class='map_canvas'></div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>";
        }
        if (!$mame_kontaktni) {
            $i++;
            $adresy .= "
                    <h4>Kontaktní adresa</h4><input type=\"hidden\" name=\"adresa_typ_$i\" value=\"2\" />
                    <div class='edit_wrapper'>
                        <div class='left_edit_box'>
                            <div class='form_row'>
                            <div class='label_float_left'>Stát</div><div class='value'><input name=\"stat_$i\" type=\"text\" value=\"" . $_POST["stat_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>Mìsto</div><div class='value'><input name=\"mesto_$i\" type=\"text\" value=\"" . $_POST["mesto_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>Ulice a ÈP</div><div class='value'><input name=\"ulice_$i\" type=\"text\" value=\"" . $_POST["ulice_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>PSÈ</div><div class='value'><input name=\"psc_$i\" type=\"text\" value=\"" . $_POST["psc_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>poznámka</div><div class='value'><input name=\"adresa_poznamka_$i\" type=\"text\" value=\"" . $_POST["adresa_poznamka_$i"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>zemìpisná šíøka</div><div class='value'><input name=\"lat_$i\" type=\"text\" value=\"" . $adresa["lat"] . "\" /></div></div>
                            <div class='form_row'>
                            <div class='label_float_left'>zemìpisná délka</div><div class='value'><input name=\"lng_$i\" type=\"text\" value=\"" . $adresa["lng"] . "\" /></div></div>
                        </div>
                        <div class='right_edit_box'>
                            <div class='form_box'>
                                <input type='submit' class='search-adr' value='Najít adresu na mapì' />
                                <div class='map_canvas'></div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>";
        }
        $adresy .= "
                    <div id=\"address_next\"></div>
                    <div class='form_row'><a href=\"#\" onclick=\"return addAddress();\">Pøidat další</a></div>
                </div>
                ";
        $last_adresa = $i;

        $bankovni_spojeni = "
                <div id=\"bankovni_spojeni\">
                <h3>Bankovní spojení</h3>";

        $bankovni_spojeni_list = $this->database->query($this->create_query("show_bankovni_spojeni"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $i = 0;
        $mame_hlavni_kontakt = false;
        while ($banka = mysqli_fetch_array($bankovni_spojeni_list)) {
            $i++;
            if ($banka["typ_kontaktu"] == 1) {
                $mame_hlavni_kontakt = true;
            }
            $bankovni_spojeni .= "<h4>" . Serial_library::get_typ_bankovniho_spojeni($banka["typ_kontaktu"]) . "</h4><input type=\"hidden\" name=\"banka_typ_$i\" value=\"" . $banka["typ_kontaktu"] . "\" />
                    <div class='form_row'>
                    <div class='label_float_left'>Název banky</div><div class='value'><input name=\"nazev_banky_$i\" type=\"text\" value=\"" . $banka["nazev_banky"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Kód banky</div><div class='value'><input name=\"kod_banky_$i\" type=\"text\" value=\"" . $banka["kod_banky"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Èíslo úètu</div><div class='value'><input name=\"cislo_uctu_$i\" type=\"text\" value=\"" . $banka["cislo_uctu"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>poznámka</div><div class='value'><input name=\"banka_poznamka_$i\" type=\"text\" value=\"" . $banka["poznamka"] . "\" class=\"wide\"/></div></div>
                    ";
        }
        //k adresam musime pridat sidlo a kontaktni adresu - pokud nebyla vyplnena
        if (!$mame_hlavni_kontakt) {
            $i++;
            $bankovni_spojeni .= "
                    <h4>Hlavní bankovní spojení</h4><input type=\"hidden\" name=\"banka_typ_$i\" value=\"1\" />
                    <div class='form_row'>
                    <div class='label_float_left'>Název banky</div><div class='value'><input name=\"nazev_banky_$i\" type=\"text\" value=\"" . $_POST["nazev_banky_$i"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Kód banky</div><div class='value'><input name=\"kod_banky_$i\" type=\"text\" value=\"" . $_POST["kod_banky_$i"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Èíslo úètu</div><div class='value'><input name=\"cislo_uctu_$i\" type=\"text\" value=\"" . $_POST["cislo_uctu_$i"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>poznámka</div><div class='value'><input name=\"banka_poznamka_$i\" type=\"text\" value=\"" . $_POST["banka_poznamka_$i"] . "\" class=\"wide\"/></div></div>";
        }
        $bankovni_spojeni .= "
                    <div id=\"bankovni_spojeni_next\"></div>
                    <div class='form_row'><a href=\"#\" onclick=\"return addBankovniSpojeni();\">Pøidat další</a></div>
                </div>  ";

        $last_bank = $i;

        $kontakty = array();
        $weby = array();
        $telefony = array();
        $emaily = array();

        $web_list = $this->database->query($this->create_query("show_web"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        while ($web = mysqli_fetch_array($web_list)) {
            // print_r($web);
            if ($web["typ_kontaktu"] != 3) {
                $kontakty["web"][$web["typ_kontaktu"]] = $web["www"];
                $kontakty["poznamka"][$web["typ_kontaktu"]] = $web["poznamka"];
            } else {
                $weby["www"][] = $web["www"];
                $weby["poznamka"][] = $web["poznamka"];
            }
        }
        $email_list = $this->database->query($this->create_query("show_email"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        while ($email = mysqli_fetch_array($email_list)) {
            //print_r($email);
            if ($email["typ_kontaktu"] != 3) {
                $kontakty["email"][$email["typ_kontaktu"]] = $email["email"];
                $kontakty["poznamka"][$email["typ_kontaktu"]] = $email["poznamka"];
            } else {
                $emaily["email"][] = $email["email"];
                $emaily["poznamka"][] = $email["poznamka"];
            }
        }
        $telefon_list = $this->database->query($this->create_query("show_telefon"))
        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        while ($telefon = mysqli_fetch_array($telefon_list)) {
            // print_r($telefon);
            if ($telefon["typ_kontaktu"] != 3) {
                $kontakty["telefon"][$telefon["typ_kontaktu"]] = $telefon["telefon"];
                $kontakty["poznamka"][$telefon["typ_kontaktu"]] = $telefon["poznamka"];
            } else {
                $telefony["telefon"][] = $telefon["telefon"];
                $telefony["poznamka"][] = $telefon["poznamka"];
            }
        }
        // print_r($kontakty);

        $kontakty_table = "
                <div id=\"kontakty\">
                    <h3>Kontakty</h3>
                    <table class='list'>
                    <tr><th></th><th colspan=\"2\">E-mail</th><th colspan=\"2\">Telefon</th><th colspan=\"2\">Web</th><th colspan=\"2\">Poznámka</th>
                    ";
        $i = 0;
        while (Serial_library::get_typ_kontaktu($i) != "") {
            //neplati pro ostatni kontakty
            if ($i != 3) {
                $kontakty_table .= "
                        <tr><th>" . Serial_library::get_typ_kontaktu($i) . "<input type=\"hidden\" name=\"kontakt_typ_$i\" value=\"" . $i . "\" /></th>
                        <td>E-mail</td><td><input name=\"email_$i\" type=\"text\" value=\"" . $kontakty["email"][$i] . "\" style=\"width:150px;\"/></td>
                        <td>Telefon</td><td><input name=\"telefon_$i\" type=\"text\" value=\"" . $kontakty["telefon"][$i] . "\" style=\"width:150px;\"/></td>
                        <td>Web</td><td><input name=\"web_$i\" type=\"text\" value=\"" . $kontakty["web"][$i] . "\"  style=\"width:200px;\"/></td>
                        <td>Poznámka</td><td><input name=\"kontakt_poznamka_$i\" type=\"text\" value=\"" . $kontakty["poznamka"][$i] . "\"  style=\"width:200px;\"/></td></tr>
                    ";
            }
            $i++;
        }

        $kontakty_table .= "
                    </table>
                    <h3>Další kontakty</h3>
                    <table class='list'>
                    <tr><th>Telefon</th><th>Poznámka</th></tr>";
        if (is_array($telefony["telefon"])) {
            foreach ($telefony["telefon"] as $key => $value) {
                $kontakty_table .= "
                    <tr><td>
                        <input type=\"hidden\" name=\"kontakt_typ_$i\" value=\"3\" />
                        <input name=\"telefon_$i\" type=\"text\" value=\"" . $value . "\"  style=\"width:150px;\"/></td>
                     <td>
                        <input name=\"kontakt_poznamka_$i\" type=\"text\" value=\"" . $telefony["poznamka"][$key] . "\"  style=\"width:200px;\"/></td></tr>";
                $i++;
            }
        }
        $kontakty_table .= "<tr id=\"telefon_next\"></tr>
                <tr><td colspan='2'><a href=\"#\" onclick=\"return addTelefon();\">Pøidat další</a></td></tr>
               ";


        $kontakty_table .= "
                    <tr><th>E-mail</th><th>Poznámka</th></tr>";
        if (is_array($emaily["email"])) {
            foreach ($emaily["email"] as $key => $value) {
                $kontakty_table .= "
                    <tr><td>
                        <input type=\"hidden\" name=\"kontakt_typ_$i\" value=\"3\" />
                        <input name=\"telefon_$i\" type=\"text\" value=\"" . $value . "\"  style=\"width:150px;\"/></td>
                     <td>
                        <input name=\"kontakt_poznamka_$i\" type=\"text\" value=\"" . $emaily["poznamka"][$key] . "\"  style=\"width:200px;\"/></td></tr>";
                $i++;
            }
        }
        $kontakty_table .= "<tr id=\"email_next\"></tr>
                <tr><td colspan='2'><a href=\"#\" onclick=\"return addEmail();\">Pøidat další</a></td></tr>
               ";


        $kontakty_table .= "
                    <tr><th >Web<th >Poznámka";
        if (is_array($weby["www"])) {
            foreach ($weby["www"] as $key => $value) {
                $kontakty_table .= "
                    <tr><td>
                        <input type=\"hidden\" name=\"kontakt_typ_$i\" value=\"3\" />
                        <input name=\"web_$i\" type=\"text\" value=\"" . $value . "\"  style=\"width:150px;\"/></td>
                     <td>
                        <input name=\"kontakt_poznamka_$i\" type=\"text\" value=\"" . $weby["poznamka"][$key] . "\"  style=\"width:200px;\"/></td></tr>";
                $i++;
            }
        }
        $kontakty_table .= "<tr id=\"web_next\"></tr>
                <tr><td colspan='2'><a href=\"#\" onclick=\"return addWeb();\">Pøidat další</a></td></tr>
               ";


        $kontakty_table .= "</table></div>
                    ";
        $last_kontakt = $i;

        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=organizace&amp;pozadavek=create&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit = "<input type=\"submit\" value=\"Vytvoøit organizaci\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení organizace</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {
            //cil formulare
            $action = "?id_organizace=" . $this->id_organizace . "&amp;typ=organizace&amp;pozadavek=update";

            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit organizaci\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci této organizace</strong>\n";
            }
        }
        $script = "
               <script type=\"text/javascript\"  src=\"/admin/js/organizace.js\" ></script>
            ";

        $ubytovani_select = $this->showSelectUbytovani();
        if ($organizace["role"] == 2) {
            $ubytovani_data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("show_ubytovani"));
            while ($row_ubytovani = mysqli_fetch_array($ubytovani_data)) {
                $ubytovani_select = $this->showSelectUbytovani($row_ubytovani["id_ubytovani"]);
            }
        } 
        $prodejce_jmeno = "";
        $prodejce_heslo = "";
        $prodejce_last_logon = "0000-00-00 00:00:00";
        if ($organizace["role"] == 1) {
            $prodejce_data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("show_prodejce"));
            while ($row_prodejce = mysqli_fetch_array($prodejce_data)) {
                $prodejce_jmeno = $row_prodejce["uzivatelske_jmeno"];
                $prodejce_heslo = $row_prodejce["heslo"];
                $prodejce_heslo_sha1 = $row_prodejce["heslo_sha1"];
                $prodejce_salt = $row_prodejce["salt"];
                $prodejce_last_logon = $row_prodejce["last_logon"];
                $prodejce_provizni_koeficient = $row_prodejce["provizni_koeficient"];
            }
        }

        $script2 = "
               <script type=\"text/javascript\" >
                    var address_count = " . $last_adresa . ";
                    var bankovni_spojeni_count = " . $last_bank . ";
                    var kontakty_count = " . $last_kontakt . ";
                    var uzivatelske_jmeno = \"" . $prodejce_jmeno . "\";
                    var heslo = \"" . $prodejce_heslo . "\";
                    var heslo_sha1 = \"" . $prodejce_heslo_sha1 . "\";
                    var salt = \"" . $prodejce_salt . "\";    
                    var last_logon = '" . $prodejce_last_logon . "';
                    var provizni_koeficient = '" . $prodejce_provizni_koeficient . "';
                    var ubytovani_seznam = '" . str_replace("'","",$ubytovani_select) . "';
                   
                     showSpecial();   
                </script>
            ";


        $vystup = $script . "<form action=\"" . $action . "\" method=\"post\">
                              <div class=\"tabs\">" .
            $povinne_udaje . $adresy . $bankovni_spojeni . $kontakty_table .
            " </div>
             " . $submit . "
                        </form>" . $script2;
        return $vystup;
    }

    /**zobrazeni formulare pro vytvoreni/editaci uzivatele*/
    function show_form()
    {
        //nazev, ico, role


        $role_organizace = "<select id=\"role_organizace\" name=\"role\" onchange=\"showSpecial();\">
                                    <option value=\"0\">---</option>";
        $i = 1;
        while (Serial_library::get_typ_organizace($i) != "") {
            if ($_POST["role"] == $i) {
                $selected_role = "selected=\"selected\"";
            } else {
                $selected_role = "";
            }
            $role_organizace .= "<option value=\"" . $i . "\" " . $selected_role . ">" . Serial_library::get_typ_organizace($i) . "</option>";
            $i++;
        }
        $role_organizace .= "</select>";

        $povinne_udaje = "
                    <div class='form_row'>
                        <div class='label_float_left'>Název organizace</div><div class='value'><input name=\"nazev\" type=\"text\" value=\"" . $_POST["nazev"] . "\" class=\"width-350px\"/></div></div>
                    <div class='form_row'>
                        <div class='label_float_left'>IÈO</div><div class='value'><input name=\"ico\" type=\"text\" value=\"" . $_POST["ico"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                        <div class='label_float_left'>DIÈ</div><div class='value'><input name=\"dic\" type=\"text\" value=\"" . $_POST["dic"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                        <div class='label_float_left'>Role</div><div class='value'>" . $role_organizace . "</div></div>
                    <div id=\"special_text\">
                    </div>
                ";

        $adresy = "<div id='adresy'>
                    <h3>Adresy</h3>
                    <h4>Sídlo spoleènosti<input type=\"hidden\" name=\"adresa_typ_1\" value=\"1\" /></h4>
                    <div class='edit_wrapper'>
                        <div class='left_edit_box'>
                            <div class='form_row'>
                                <div class='label_float_left'>Stát</div><div class='value'><input name=\"stat_1\" type=\"text\" value=\"" . $_POST["stat_1"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>Mìsto</div><div class='value'><input name=\"mesto_1\" type=\"text\" value=\"" . $_POST["mesto_1"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>Ulice a ÈP</div><div class='value'><input name=\"ulice_1\" type=\"text\" value=\"" . $_POST["ulice_1"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>PSÈ</div><div class='value'><input name=\"psc_1\" type=\"text\" value=\"" . $_POST["psc_1"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>poznámka</div><div class='value'><input name=\"adresa_poznamka_1\" type=\"text\" value=\"" . $_POST["adresa_poznamka_1"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>zemìpisná šíøka</div><div class='value'><input name='lat_1' value='" . $_POST["lat_1"] . "' /></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>zemìpisná délka</div><div class='value'><input name='lng_1' value='" . $_POST["lng_1"] . "' /></div></div>
                        </div>
                        <div class='right_edit_box'>
                            <div class='form_box'>
                                <input type='submit' class='search-adr' value='Najít adresu na mapì' />
                                <div class='map_canvas'></div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>
                         
                    <h4>Kontaktní adresa<input type=\"hidden\" name=\"adresa_typ_2\" value=\"2\" /></h4>
                    <div class='edit_wrapper'>
                        <div class='left_edit_box'>
                            <div class='form_row'>
                                <div class='label_float_left'>Stát</div><div class='value'><input name=\"stat_2\" type=\"text\" value=\"" . $_POST["stat_2"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>Mìsto</div><div class='value'><input name=\"mesto_2\" type=\"text\" value=\"" . $_POST["mesto_2"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>Ulice a ÈP</div><div class='value'><input name=\"ulice_2\" type=\"text\" value=\"" . $_POST["ulice_2"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>PSÈ</div><div class='value'><input name=\"psc_2\" type=\"text\" value=\"" . $_POST["psc_2"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>poznámka</div><div class='value'><input name=\"adresa_poznamka_2\" type=\"text\" value=\"" . $_POST["adresa_poznamka_2"] . "\" class=\"width-350px\"/></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>zemìpisná šíøka</div><div class='value'><input name='lat_2' value='" . $_POST["lat_2"] . "' /></div></div>
                            <div class='form_row'>
                                <div class='label_float_left'>zemìpisná délka</div><div class='value'><input name='lng_2' value='" . $_POST["lng_2"] . "' /></div></div>
                        </div>
                        <div class='right_edit_box'>
                            <div class='form_box'>
                                <input type='submit' class='search-adr' value='Najít adresu na mapì' />
                                <div class='map_canvas'></div>
                            </div>
                        </div>
                        <div class='clearfix'></div>
                    </div>
                    <div id=\"address_next\"></div>
                    <div class='form_row'><a href=\"#\" onclick=\"return addAddress();\">Pøidat další</a></div>
                </div>
                ";

        $bankovni_spojeni = "
                    <h3>Bankovní spojení</h3>
                    <h4>Hlavní bankovní spojení<input type=\"hidden\" name=\"banka_typ_1\" value=\"1\" /></h4>
                    <div class='form_row'>
                        <div class='label_float_left'>Název banky</div><div class='value'><input name=\"nazev_banky_1\" type=\"text\" value=\"" . $_POST["nazev_banky_1"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                        <div class='label_float_left'>Kód banky</div><div class='value'><input name=\"kod_banky_1\" type=\"text\" value=\"" . $_POST["kod_banky_1"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                        <div class='label_float_left'>Èíslo úètu</div><div class='value'><input name=\"cislo_uctu_1\" type=\"text\" value=\"" . $_POST["cislo_uctu_1"] . "\" class=\"wide\"/></div></div>
                    <div class='form_row'>
                        <div class='label_float_left'>poznámka</div><div class='value'><input name=\"banka_poznamka_1\" type=\"text\" value=\"" . $_POST["banka_poznamka_1"] . "\" class=\"wide\"/></div></div>
                    <div id=\"bankovni_spojeni_next\"></div>
                    <div class='form_row'><a href=\"#\" onclick=\"return addBankovniSpojeni();\">Pøidat další</a></div>
                ";


        $kontakty = "
                <div id=\"kontakty\">
                <h3>Kontakty</h3>
                <table class='list'>
                    <tr><th></th><th colspan='2'>E-mail</th><th colspan='2'>Telefon</th><th colspan='2'>Web</th><th colspan='2'>Poznámka</th></tr>";
        $i = 0;
        while (Serial_library::get_typ_kontaktu($i) != "") {
            if (Serial_library::get_typ_kontaktu($i) == "Hlavní kontakt" or Serial_library::get_typ_kontaktu($i) == "Ostatní") {
                $kontakty .= "
                        <tr><th>" . Serial_library::get_typ_kontaktu($i) . " <input type=\"hidden\" name=\"kontakt_typ_$i\" value=\"" . $i . "\" /></th>
                        <td>E-mail</td><td><input name=\"email_$i\" type=\"text\" value=\"" . $_POST["email_$i"] . "\" class=\"wide\"/></td>
                        <td>Telefon</td><td><input name=\"telefon_$i\" type=\"text\" value=\"" . $_POST["telefon_$i"] . "\" class=\"wide\"/></td>
                        <td>Web</td><td><input name=\"web_$i\" type=\"text\" value=\"" . $_POST["web_$i"] . "\" class=\"wide\"/></td>
                        <td>Poznámka</td><td><input name=\"kontakt_poznamka_$i\" type=\"text\" value=\"" . $_POST["kontakt_poznamka_$i"] . "\" class=\"wide\"/></td></tr>
                    ";
            } else {
                $kontakty .= "
                        <tr><th>" . Serial_library::get_typ_kontaktu($i) . " <input type=\"hidden\" name=\"kontakt_typ_$i\" value=\"" . $i . "\" /></th>
                        <td>E-mail</td><td><input name=\"email_$i\" type=\"text\" value=\"" . $_POST["email_$i"] . "\" class=\"wide\"/></td>
                        <td>Telefon</td><td><input name=\"telefon_$i\" type=\"text\" value=\"" . $_POST["telefon_$i"] . "\" class=\"wide\"/></td>
                        <td colspan='2'></td>
                        <td>Poznámka</td><td><input name=\"kontakt_poznamka_$i\" type=\"text\" value=\"" . $_POST["kontakt_poznamka_$i"] . "\" class=\"wide\"/></td></tr>
                    ";
            }
            $i++;
        }
        $kontakty .= "
                <tr id=\"kontakty_next\"></tr>
                <tr><td colspan='9'><a href=\"#\" onclick=\"return addKontakty();\">Pøidat další</a></td></tr>
                </table></div>";

        $submit = "";
        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=organizace&amp;pozadavek=create&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit = "<input type=\"submit\" value=\"Vytvoøit organizaci\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení klienta</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {
            //cil formulare
            $action = "?id_organizace=" . $this->id_organizace . "&amp;typ=organizace&amp;pozadavek=create";

            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit klienta\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto klienta</strong>\n";
            }
        }

        $script = "
               <script type=\"text/javascript\"  src=\"/admin/js/organizace.js\" ></script>
            ";
        $ubytovani_select = $this->showSelectUbytovani();
        $script2 = "
               <script type=\"text/javascript\"  onload=\"showSpecial();\">
                    var address_count = 2;
                    var bankovni_spojeni_count = 1;
                    var kontakty_count = 4;
                    var uzivatelske_jmeno = \"\";
                    var heslo = \"\";
                    var heslo_sha1 = \"\";
                    var salt = \"".  mt_rand(100, 10000)."\";  
                    var provizni_koeficient = '1';
                    var last_logon = \"0000-00-00 00:00:00\";
                   var ubytovani_seznam = '" . str_replace("'","",$ubytovani_select) . "';
                        showSpecial();
                </script>
            ";

        
        
        $vystup =   "<form action=\"" . $action . "\" method=\"post\">
                              <div class=\"tabs\">" .
            $povinne_udaje . $adresy . $bankovni_spojeni . $kontakty .
            " </div>
             " . $submit . "
           </form>
          " . $script.$script2."";
        return $vystup;
    }


    function get_id()
    {
        return $this->informace["id_informace"];
    }

    function get_nazev()
    {
        return $this->informace["nazev"];
    }

    function get_id_klient()
    {
        return $this->id_klient;
    }

    function get_id_user_create()
    {
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
