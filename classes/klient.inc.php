<?php

/**
 * trida pro praci s klientem
 * - formuláøe pro registraci, zmìnu osobních údajù + jejich zpracování
 */
/* --------------------- UZIVATEL ---------------------------- */
class Klient extends Generic_data_class {

    protected $typ_pozadavku;
    protected $id_klient;
    protected $salt_potvrzeni;
    protected $potvrzeni_expire;
    protected $uzivatelske_jmeno;
    protected $jmeno;
    protected $prijmeni;
    protected $titul;
    protected $email;
    protected $telefon;
    protected $datum_narozeni;
    protected $rodne_cislo;
    protected $cislo_pasu;
    protected $cislo_op;
    protected $ulice;
    protected $mesto;
    protected $psc;
    protected $ico;
    protected $uzivatel_je_ca;
    protected $stare_heslo;
    protected $heslo;
    protected $heslo2;
    protected $nove_heslo;
    public $database; //trida pro odesilani dotazu	

    /**
     * 	konstruktor tøídy 
     * - parametry jsou hodnoty sloupcù z tabulky user_klient
     */

    function __construct(
    $typ_pozadavku, $id_klient = "", $salt_potvrzeni = "", $uzivatelske_jmeno = "", $stare_heslo = "", $nove_heslo1 = "", $nove_heslo2 = "", $jmeno = "", $prijmeni = "", $titul = "", $datum_narozeni = "", $rodne_cislo = "", $email = "", $telefon = "", $cislo_pasu = "", $cislo_op = "", $ulice = "", $mesto = "", $psc = "", $ico = "", $uzivatel_je_ca = "", $minuly_pozadavek = ""
    ) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_klient = $this->check_int($id_klient);
        $this->salt_potvrzeni = $this->check($salt_potvrzeni);

        $this->uzivatelske_jmeno = strtolower($this->check($uzivatelske_jmeno));
        $this->jmeno = $this->check_slashes($this->check($jmeno));
        $this->prijmeni = $this->check_slashes($this->check($prijmeni));
        $this->titul = $this->check_slashes($this->check($titul));
        $this->email = $this->check_slashes($this->check($email));
        $this->telefon = $this->check_slashes($this->check($telefon));
        $this->datum_narozeni = $this->check_slashes($this->change_date_cz_en($this->check($datum_narozeni)));
        $this->rodne_cislo = $this->check_slashes($this->check($rodne_cislo));
        $this->cislo_pasu = $this->check_slashes($this->check($cislo_pasu));
        $this->cislo_op = $this->check_slashes($this->check($cislo_op));
        $this->ulice = $this->check_slashes($this->check($ulice));
        $this->mesto = $this->check_slashes($this->check($mesto));
        $this->psc = $this->check_slashes($this->check($psc));
        $this->ico = $this->check_slashes($this->check($ico));

        $this->uzivatel_je_ca = 1;  //je treba zmenit az se budou prihlasovat i klienti

        $this->stare_heslo = $this->check($stare_heslo);
        $this->heslo = $this->check($nove_heslo1);
        $this->heslo2 = $this->check($nove_heslo2);
        $this->nove_heslo = "";

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            //podle typu pozadavku
            if ($this->typ_pozadavku == "create") {
                //pokud odpovidaji hesla
                if ($this->heslo == $this->heslo2 and !$this->heslo2 = "") {
                    //vytvorim zakodovane heslo
                    $nahodny_retezec = sha1(mt_rand() . mt_rand());
                    $this->salt = substr($nahodny_retezec, 1, mt_rand(10, 20));
                    $this->nove_heslo = sha1($this->heslo . $this->salt);

                    //potvrzovaci kod pro e-mail
                    $this->salt_potvrzeni = sha1(mt_rand() . mt_rand());
                    //cas kdy vyprsi tento potvrzovaci kod					
                    $this->potvrzeni_expire = date("Y-m-d H:i:s", (time() + (PLATNOST_POTVRZENI * 60 * 60)));

                    $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $this->id_klient = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                    if (!$this->get_error_message()) {
                        //odeslu klientovi e-mail s potvrzovacim kodem
                        $predmet = "Potvrzení registrace v systému agentur CK SLAN tour";
                        $odesilatel_jmeno = AUTO_MAIL_SENDER;
                        $odesilatel_email = AUTO_MAIL_EMAIL;
                        $text = "Tento e-mail Vám byl zaslán na základì vyplnìní registraèního formuláøe na adrese " . $_SERVER['SERVER_NAME'] . ".<br/><br/>
									ID klienta:  " . $this->id_klient . "<br/>	
									Uživatelské jméno: " . $this->uzivatelske_jmeno . "<br/>									
									Jméno: " . $this->jmeno . "<br/>
									Pøíjmení: " . $this->prijmeni . "<br/><br/>
									Pro potvrzení registrace do systému agentur CK SLAN tour kliknìte na následující odkaz (kontrola, zda e-mail, který jste pøi registraci uvedl(a) je skuteènì Váš). Pozor, kód je platný pouze po následujících " . POTVRZENI_EXPIRE_TIME . " hodin<br/>
									(pokud jste nic nevyplòoval(a), mùžete tento e-mail ignorovat)<br/><br/>
									http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "?lev1=potvrzeni_registrace&amp;id_klient=" . $this->id_klient . "&amp;salt=" . $this->salt_potvrzeni . "<br/><br/>
									Pokud na odkaz nejde kliknout, zkopírujte ho do øádku adresy Vašeho prohlížeèe.
								";

                        //odeslani potvrzovaciho e-mailu						
                        $mail = Send_mail::send($odesilatel_jmeno, $odesilatel_email, $this->email, $predmet, $text);
                        if (!$mail) {
                            $this->chyba("Nepodaøilo se odeslat kontrolní e-mail. Zaregistrujte se prosím ještì jednou.");
                        }
                    }

                    //vygenerování potvrzovací hlášky
                    if (!$this->get_error_message()) {
                        $this->confirm("První èást registrace probìhla úspìšnì, na Vaši adresu byl odeslán potvrzovací e-mail.");
                    }
                } else {
                    $this->chyba("Heslo a kontrolní heslo nejsou stejné nebo jsou prázdné!");
                }

                //vytvoreni uziv. jmena a hesla pro existujiciho klienta					
            } else if ($this->typ_pozadavku == "create_ucet") {
                //pripojeni k databazi
                $this->database->start_transaction();
                //zjistim zda klient zadal spravne id, jmeno a prijmeni	
                $data_user = $this->database->transaction_query($this->create_query("get_created_ucet"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $user = mysqli_fetch_array($data_user);

                //ziskani dat o uzivateli
                $this->email = $user["email"];

                //nalezli jsme spravneho klienta
                if (!$this->get_error_message() and mysqli_num_rows($data_user) == 1) {

                    //pokud odpovidaji hesla
                    if ($this->heslo == $this->heslo2) {
                        //vytvorim zakodovane heslo
                        $nahodny_retezec = sha1(mt_rand() . mt_rand());
                        $this->salt = substr($nahodny_retezec, 1, mt_rand(10, 20));
                        $this->nove_heslo = sha1($this->heslo . $this->salt);

                        //potvrzovaci kod pro e-mail
                        $this->salt_potvrzeni = sha1(mt_rand() . mt_rand());
                        //cas kdy vyprsi tento potvrzovaci kod					
                        $this->potvrzeni_expire = date("Y-m-d H:i:s", (time() + (PLATNOST_POTVRZENI * 60 * 60)));

                        $this->data = $this->database->transaction_query($this->create_query("create_ucet"))
                                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                        if (!$this->get_error_message()) {
                            //odeslu klientovi e-mail s potvrzovacim kodem
                            $predmet = "Potvrzení registrace v systému RSCK";
                            $odesilatel_jmeno = AUTO_MAIL_SENDER;
                            $odesilatel_email = AUTO_MAIL_EMAIL;
                            $text = "Tento e-mail Vám byl zaslán na základì vyplnìní registraèního formuláøe na adrese " . $_SERVER['SERVER_NAME'] . ".<br/><br/>
									ID klienta:  " . $this->id_klient . "<br/>	
									Uživatelské jméno: " . $this->uzivatelske_jmeno . "<br/>									
									Název CA: " . $this->jmeno . "<br/>
									Kontaktní osoba: " . $this->prijmeni . "<br/><br/>
									Pro potvrzení registrace do systému agentur CK SLAN tour kliknìte na následující odkaz (kontrola, zda e-mail, který jste pøi registraci uvedl(a) je skuteènì Váš). Pozor, kód je platný pouze po následujících " . POTVRZENI_EXPIRE_TIME . " hodin<br/>
									(pokud jste nic nevyplòoval(a), mùžete tento e-mail ignorovat)<br/><br/>
									http://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'] . "?lev1=potvrzeni_registrace&amp;id_klient=" . $this->id_klient . "&amp;salt=" . $this->salt_potvrzeni . "<br/><br/>
									Pokud na odkaz nejde kliknout, zkopírujte ho do øádku adresy Vašeho prohlížeèe.
								";

                            //odeslani potvrzovaciho e-mailu						
                            $mail = Send_mail::send($odesilatel_jmeno, $odesilatel_email, $this->email, $predmet, $text);
                            if (!$mail) {
                                $this->database->rollback(); //zrusim transakci
                                $this->chyba("Nepodaøilo se odeslat kontrolní e-mail. Zaregistrujte se prosím ještì jednou.");
                            }
                        }

                        //vygenerování potvrzovací hlášky
                        if (!$this->get_error_message()) {
                            $this->database->commit(); //potvrzeni transakce
                            $this->confirm("První èást registrace probìhla úspìšnì, na Vaši adresu byl odeslán potvrzovací e-mail.");
                        }
                    } else {
                        $this->chyba("Heslo a kontrolní heslo nejsou stejné!");
                    }
                } else {
                    $this->chyba("Podle zadaného ID, jména a pøíjmení nebyl nalezen žádný úèet!");
                }
            } else if ($this->typ_pozadavku == "update") {
                $uzivatel = User::get_instance();
                $this->id_klient = $uzivatel->get_id();
                //pripojeni k databazi
                $this->database->start_transaction();
                //pokud odpovidaji hesla
                //pokud chceme zmenit heslo
                if ($this->stare_heslo != "" and $this->heslo != "") {
                    $data_user = mysqli_fetch_array($this->database->transaction_query($this->create_query("get_user_password"))) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    //pokud jsme spravne napsali stare heslo								
                    if (sha1($this->stare_heslo . $data_user["salt"]) == $data_user["heslo_sha1"]) {
                        if ($this->heslo == $this->heslo2 and $this->heslo2 != "") {
                            //vytvorim nove heslo ktere pouziju do databaze
                            $this->nove_heslo = sha1($this->heslo . $data_user["salt"]);
                        } else {
                            $this->chyba("Nové heslo a kontrolní nové heslo nejsou stejné!");
                        }
                    } else {
                        $this->chyba("Staré heslo není správné!");
                    }
                }
                $this->data = $this->database->transaction_query($this->create_query($this->typ_pozadavku)) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                if ($this->nove_heslo) {
                    $_SESSION["heslo_klient"] = $this->nove_heslo;
                }
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->database->commit(); //potvrzeni transakce
                    $this->confirm("Zmìna osobních údajù byla úspìšnì provedena");
                }
            } else if ($this->typ_pozadavku == "editace_osobnich_udaju") {
                $uzivatel = User::get_instance();
                $this->id_klient = $uzivatel->get_id();

                $data_uzivatel = mysqli_fetch_array($this->database->query($this->create_query($this->typ_pozadavku)))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                $this->uzivatelske_jmeno = $data_uzivatel["uzivatelske_jmeno"];
                $this->jmeno = $data_uzivatel["jmeno"];
                $this->prijmeni = $data_uzivatel["prijmeni"];

                if ($minuly_pozadavek != "update_osobnich_udaju") {
                    $this->titul = $data_uzivatel["titul"];
                    $this->datum_narozeni = $data_uzivatel["datum_narozeni"];
                    $this->rodne_cislo = $data_uzivatel["rodne_cislo"];
                    $this->email = $data_uzivatel["email"];
                    $this->telefon = $data_uzivatel["telefon"];
                    $this->mesto = $data_uzivatel["mesto"];
                    $this->ulice = $data_uzivatel["ulice"];
                    $this->psc = $data_uzivatel["psc"];
                    $this->ico = $data_uzivatel["ico"];
                }
            } else if ($this->typ_pozadavku == "potvrzeni_registrace") {
                $data_uzivatel = mysqli_fetch_array($this->database->query($this->create_query("get_potvrzeni_uctu")))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                if ($data_uzivatel["pocet"] == 1) {
                    //nastavim ucet jako potvrzeny
                    $set_potvrzeni = $this->database->query($this->create_query("set_ucet_potvrzen"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    //vygenerování potvrzovací hlášky
                    if (!$this->get_error_message()) {
                        $this->confirm("Registrace byla úspìšnì dokonèena, nyní se prosím pøihlašte.");
                    }
                } else {
                    $this->chyba("Kód je buï špatný, nebo je již prošlý. Zkontrolujte prosím správnost kódu.");
                }
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

    /*     * vytvoreni dotazu na zaklade typu pozadavku */

    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "create") {
            $dotaz = "INSERT INTO `user_klient` 
							(`uzivatelske_jmeno`,`heslo_sha1`,`salt`,`jmeno`,`prijmeni`,`titul`,`datum_narozeni`,`rodne_cislo`,`email`,`telefon`,
							`cislo_op`,`cislo_pasu`,`ulice`,`mesto`,`psc`,`ico`,`uzivatel_je_ca`,`salt_potvrzeni`,`potvrzeni_expire`)
						VALUES
							 ('" . $this->uzivatelske_jmeno . "','" . $this->nove_heslo . "','" . $this->salt . "','" . $this->jmeno . "','" . $this->prijmeni . "','" . $this->titul . "','" . $this->datum_narozeni . "','" . $this->rodne_cislo . "',
							 '" . $this->email . "','" . $this->telefon . "','" . $this->cislo_op . "','" . $this->cislo_pasu . "','" . $this->ulice . "','" . $this->mesto . "','" . $this->psc . "','" . $this->ico . "','" . $this->uzivatel_je_ca . "',
							  '" . $this->salt_potvrzeni . "','" . $this->potvrzeni_expire . "')";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "create_ucet") {
            $dotaz = "UPDATE `user_klient` 
						SET
							`uzivatelske_jmeno`='" . $this->uzivatelske_jmeno . "',`heslo_sha1`='" . $this->nove_heslo . "', `salt`='" . $this->salt . "',
							 `salt_potvrzeni`='" . $this->salt_potvrzeni . "',`potvrzeni_expire`='" . $this->potvrzeni_expire . "'
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "update") {
            if ($this->nove_heslo) {
                $update_heslo = "`heslo_sha1`='" . $this->nove_heslo . "', ";
            } else {
                $update_heslo = "";
            }
            if ($this->rodne_cislo) {
                $rc = "`rodne_cislo`='" . $this->rodne_cislo . "',";
            } else {
                $rc = "";
            }
            $dotaz = "UPDATE `user_klient` 
                                    SET
                                             " . $update_heslo . " `titul`='" . $this->titul . "',`datum_narozeni`='" . $this->datum_narozeni . "'," . $rc . "
                                             `email`='" . $this->email . "',`telefon`='" . $this->telefon . "',`cislo_op`='" . $this->cislo_op . "',`cislo_pasu`='" . $this->cislo_pasu . "',
                                             `ulice`='" . $this->ulice . "',`mesto`='" . $this->mesto . "',`psc`='" . $this->psc . "',`ico`='" . $this->ico . "'
                                    WHERE `id_klient`=" . $this->id_klient . "
                                    LIMIT 1";
//			echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "editace_osobnich_udaju") {
            $dotaz = "SELECT `id_klient`,`uzivatelske_jmeno`,`jmeno`,`prijmeni`,`titul`,`datum_narozeni`,`rodne_cislo`,`email`,`telefon`,`cislo_op`,`cislo_pasu`,`ulice`,`mesto`,`psc`,`ico`,`uzivatel_je_ca`
						FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1 ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_password") {
            $dotaz = "SELECT `id_klient`,`heslo_sha1`,`salt` FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_created_ucet") {
            $dotaz = "SELECT `id_klient`,`jmeno`,`prijmeni`,`email` FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . " and `jmeno`='" . $this->jmeno . "'  and `prijmeni`='" . $this->prijmeni . "' and `ucet_potvrzen_klientem` <> 1 
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_potvrzeni_uctu") {
            $dotaz = "SELECT count(`id_klient`) as `pocet`
						FROM `user_klient` 
						WHERE `id_klient`=" . $this->id_klient . " and `salt_potvrzeni`='" . $this->salt_potvrzeni . "'  and `potvrzeni_expire` >='" . Date("Y-m-d H:i:s") . "' ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "set_ucet_potvrzen") {
            $dotaz = "UPDATE `user_klient` 
						SET
							 `ucet_potvrzen_klientem`= 1, `salt_potvrzeni`= NULL, `potvrzeni_expire`= NULL 
						WHERE `id_klient`=" . $this->id_klient . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
    }

    /*     * kontrola zda smi uzivatel provest danou akci */

    function legal($typ_pozadavku) {
        $uzivatel = User::get_instance();

        if ($typ_pozadavku == "create" or $typ_pozadavku == "create_ucet" or $typ_pozadavku == "potvrzeni_registrace") {
            return true;
        } else if ($uzivatel->get_correct_login()) {
            return true;
        }
        return false;
    }

    /*     * kontrola zda mam odpovidajici data */

    function correct_data($typ_pozadavku) {
        $ok = 1;
        //kontrolovaná data: název seriálu, popisek,  id_typ, 
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {

            /* if(!Validace::datum_en($this->datum_narozeni) ){
              $ok = 0;
              $this->chyba("Datum narození musí být ve formátu dd.mm.rrrr".$this->datum_narozeni);
              } */

            if (!Validace::email($this->email)) {
                $ok = 0;
                $this->chyba("Špatnì vyplnìný e-mail");
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
            }
            if (!Validace::text($this->ico)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit IÈO");
            }
        }
        if ($typ_pozadavku == "create") {//je treba jeste vyplnit uzivatelske jmeno a hesla
            if (!Validace::text($this->jmeno)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit název CA");
            }
            if (!Validace::text($this->prijmeni)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit kontaktní osobu");
            }
            if (!Validace::text($this->uzivatelske_jmeno)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit uživatelské jméno");
            }
            if (!Validace::text($this->heslo)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit heslo");
            }
            if (!Validace::text($this->heslo2)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit kontrolní heslo");
            }
        }
        if ($typ_pozadavku == "create_ucet") {//je treba jeste vyplnit uzivatelske jmeno a hesla
            if (!Validace::text($this->id_klient)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit id klienta");
            }
            if (!Validace::text($this->jmeno)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit jméno");
            }
            if (!Validace::text($this->prijmeni)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit pøíjmení");
            }
            if (!Validace::text($this->uzivatelske_jmeno)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit uživatelské jméno");
            }
            if (!Validace::text($this->heslo)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit heslo");
            }
            if (!Validace::text($this->heslo2)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit kontrolní heslo");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*     * zobrazeni formulare pro registraci klienta */

    function show_registration_form() {
        $core = Core::get_instance();
        $adresa_registrace = $core->get_adress_modul_from_typ("registrace");
        if ($adresa_registrace !== false) {

            if ($this->typ_pozadavku == "new") {
                //cil formulare
                $action = "" . $this->get_adress(array($adresa_registrace, "nova_registrace"), 0) . "";
                $submit = "<input type=\"submit\" value=\"Zaregistrovat se\" />\n";
                $username = "
					<tr>
						<td>Uživatelské jméno: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"uzivatelske_jmeno\" value=\"" . $this->uzivatelske_jmeno . "\" size=\"40\" maxlength=\"40\" /></td>
					</tr>";
                $heslo =
                        "<tr>
						<td>Heslo: <span class=\"red\">*</span></td>
						<td><input type=\"password\" name=\"heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>					
					<tr>
						<td>Heslo - kontrola: <span class=\"red\">*</span></td>
						<td><input type=\"password\" name=\"heslo_kontrola\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";
                $jmeno = "					
					<tr>
						<td>Název cestovní agentury: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"jmeno\" value=\"" . $this->jmeno . "\" size=\"100\" /></td>
					</tr>	";
            } else if ($this->typ_pozadavku == "editace_osobnich_udaju") {
                //cil formulare
                $action = "" . $this->get_adress(array($adresa_registrace, "update_osobnich_udaju"), 0) . "";
                $submit = "<input type=\"submit\" value=\"Editovat osobní údaje\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
                $username = "
					<tr>
						<td>Uživatelské jméno:</td>
						<td>" . $this->uzivatelske_jmeno . "</td>
					</tr>	";
                $stare_heslo = "					
					<tr>
						<td>Staré heslo:</td>
						<td><input type=\"password\" name=\"stare_heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";
                $heslo =
                        "<tr>
						<td>Heslo:</td>
						<td><input type=\"password\" name=\"heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>					
					<tr>
						<td>Heslo - kontrola:</td>
						<td><input type=\"password\" name=\"heslo_kontrola\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";
                $jmeno = "					
					<tr>
						<td>Název CA:</td>
						<td>" . $this->jmeno . "</td>
					</tr>		";
            }
            $prijmeni = "					
					<tr>
						<td>Kontaktní osoba: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"prijmeni\" value=\"" . $this->prijmeni . "\" size=\"100\" /></td>
					</tr>	";

            $vystup = "
			<div id=\"uzivatel\">
			<form action=\"" . $action . "\" method=\"post\">
				<table class=\"uzivatel\">
					" . $username . "
					" . $stare_heslo . "
					" . $heslo . "
					" . $jmeno . "
					" . $prijmeni . "
					<tr>
						<td>Ièo: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"ico\" value=\"" . $this->ico . "\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>E-mail: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"email\" value=\"" . $this->email . "\" size=\"40\" /></td>
					</tr>
					<tr>
						<td>Telefon:</td>
						<td><input type=\"text\" name=\"telefon\" value=\"" . $this->telefon . "\" size=\"40\" /></td>
					</tr>
					<tr><td><strong>Kontaktní adresa</strong></td></tr>
					<tr>
						<td>Mìsto: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"mesto\" value=\"" . $this->mesto . "\" size=\"40\" /></td>
					</tr>		
					<tr>
						<td>Ulice a ÈP: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"ulice\" value=\"" . $this->ulice . "\" size=\"40\" /></td>
					</tr>		
					<tr>
						<td>PSÈ: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"psc\" value=\"" . $this->psc . "\" size=\"40\" /></td>
					</tr>																																							
				</table>
				" . $submit . "
		</form>
			<p><span class=\"red\">*</span> - pole oznaèená hvìzdièkou je tøeba vyplnit.</p>";

            if ($this->typ_pozadavku == "new") {
                $vystup = $vystup . "	<h3>Co se stane po odeslání?</h3>
				<p>Po odeslání zkontroluje systém vaše údaje a pokud bude vše vpoøádku, odešle na Váš e-mail potvrzovací kód - odkaz<br/>
				Teprve po kliknutí na odkaz bude Vaše registrace dokonèena.</p>
				" . Send_mail::$hlaska_osobni_udaje . "	";
            }

            $vystup = $vystup . "</div>";
            return $vystup;
        }//adresa_registrace !==false
    }

    /** zobrazi formular pro vytvoreni uzivatelskeho uctu k existujicimu zaznamu o klientovi */
    function show_create_ucet_form() {
        $core = Core::get_instance();
        $adresa_registrace = $core->get_adress_modul_from_typ("registrace");
        if ($adresa_registrace !== false) {
            //cil formulare
            $action = "" . $this->get_adress(array($adresa_registrace, "vytvorit_ucet"), 0) . "";
            $submit = "<input type=\"submit\" value=\"Vytvoøit úèet ke klientovi\" />\n";
            $username = "
					<tr>
						<td>Uživatelské jméno: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"uzivatelske_jmeno\" value=\"" . $this->uzivatelske_jmeno . "\" size=\"40\" maxlength=\"40\" /></td>
					</tr>";
            $heslo =
                    "<tr>
						<td>Heslo: <span class=\"red\">*</span></td>
						<td><input type=\"password\" name=\"heslo\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>					
					<tr>
						<td>Heslo - kontrola: <span class=\"red\">*</span></td>
						<td><input type=\"password\" name=\"heslo_kontrola\" value=\"\" size=\"40\" maxlength=\"40\" /></td>
					</tr>	";
            $id = "					
					<tr>
						<td>ID klienta: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"id_klient\" value=\"" . $this->id_klient . "\" size=\"40\" /></td>
					</tr>	";
            $jmeno = "					
					<tr>
						<td>Jméno: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"jmeno\" value=\"" . $this->jmeno . "\" size=\"40\" /></td>
					</tr>	";
            $prijmeni = "					
					<tr>
						<td>Pøíjmení: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"prijmeni\" value=\"" . $this->prijmeni . "\" size=\"40\" /></td>
					</tr>	";



            $vystup = "
			<div id=\"rezervace\">
			<form action=\"" . $action . "\" method=\"post\">
				<table class=\"uzivatel\">		
					" . $id . "			
					" . $jmeno . "
					" . $prijmeni . "
					" . $username . "
					" . $heslo . "																																						
				</table>
				" . $submit . "
			</form>
			<p><span class=\"red\">*</span> - pole oznaèená hvìzdièkou je tøeba vyplnit.</p>
			<h3>Co se stane po odeslání?</h3>
			<p>Po odeslání zkontroluje systém vaše údaje a pokusí se nalézt úèet se zadaným Id. Pokud bude vše vpoøádku, odešle na e-mail zadaný u úètu potvrzovací kód - odkaz<br/>
				Teprve po kliknutí na odkaz bude Vaše registrace dokonèena.</p>	
			" . Send_mail::$hlaska_osobni_udaje . "		
			</div>";
            return $vystup;
        }//adresa_registrace !==false
    }

    /* metody pro pristup k parametrum */

    function get_id() {
        return $this->uzivatel["id_klient"];
    }

    function get_uzivatelske_jmeno() {
        return $this->uzivatel["uzivatelske_jmeno"];
    }

    function get_jmeno() {
        return $this->uzivatel["jmeno"];
    }

    function get_prijmeni() {
        return $this->uzivatel["prijmeni"];
    }

    function get_correct_login() {
        return $this->correct_login;
    }

}

?>
