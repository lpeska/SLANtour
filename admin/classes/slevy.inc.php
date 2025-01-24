<?php

/**
 * foto.inc.php - tridy pro zobrazeni fotek
 */
/* --------------------- SERIAL ------------------------------------------- */
class Slevy extends Generic_data_class {

    //vstupnidata
    protected $typ_pozadavku;
    protected $minuly_pozadavek; //nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
    protected $id_zamestnance;
    protected $id_slevy;
    protected $nazev_slevy;
    protected $zkraceny_nazev;
    protected $platnost_od;
    protected $platnost_do;
    protected $castka;
    protected $mena;
    protected $poznamka;
    protected $data;
    protected $foto;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /** konstruktor tøídy na základì typu pozadavku */

    function __construct(
    $typ_pozadavku, $id_zamestnance, $id_slevy, $nazev_slevy = "", $zkraceny_nazev = "", $platnost_od = "", $platnost_do = "", $castka = "", $mena = "", $poznamka = "", $sleva_staly_klient = "", $minuly_pozadavek = ""
    ) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->minuly_pozadavek = $this->check($minuly_pozadavek);
        $this->id_zamestnance = $this->check_int($id_zamestnance);

        $this->id_slevy = $this->check_int($id_slevy);
        $this->nazev_slevy = $this->check($nazev_slevy);
        $this->zkraceny_nazev = $this->check($zkraceny_nazev);

        $this->platnost_od = $this->change_date_cz_en($this->check($platnost_od));
        $this->platnost_do = $this->change_date_cz_en($this->check($platnost_do));
        $this->castka = $this->check_int($castka);
        $this->mena = $this->check($mena);
        $this->poznamka = $this->check($poznamka);
        $this->sleva_staly_klient = $this->check_int($sleva_staly_klient);

        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {

            //pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
            if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "delete") {
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                //pokud vytvarime novy serial, ulozime si jeho id
                if ($this->typ_pozadavku == "create") {
                    $this->id_slevy = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                }

                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }

                //pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
            } else if (($this->typ_pozadavku == "edit" or $this->typ_pozadavku == "show") and $this->minuly_pozadavek != "update") {
                $data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                $slevy = mysqli_fetch_array($data);
                //jednotlive sloupce ulozim do promennych tridy

                $this->id_slevy = $slevy["id_slevy"];
                $this->nazev_slevy = $slevy["nazev_slevy"];
                $this->zkraceny_nazev = $slevy["zkraceny_nazev"];

                $this->platnost_od = $slevy["platnost_od"];
                $this->platnost_do = $slevy["platnost_do"];
                $this->castka = $slevy["castka"];
                $this->mena = $slevy["mena"];
                $this->poznamka = $slevy["poznamka"];
                $this->sleva_staly_klient = $slevy["sleva_staly_klient"];
            } else if ($this->typ_pozadavku == "edit") {
                $data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $slevy = mysqli_fetch_array($data);
                //jednotlive sloupce ulozim do promennych tridy

                $this->id_slevy = $slevy["id_slevy"];
                $this->nazev_slevy = $slevy["nazev_slevy"];
                $this->zkraceny_nazev = $slevy["zkraceny_nazev"];
                $this->platnost_od = $slevy["platnost_od"];
                $this->platnost_do = $slevy["platnost_do"];
                $this->castka = $slevy["castka"];
                $this->mena = $slevy["mena"];
                $this->poznamka = $slevy["poznamka"];
                $this->sleva_staly_klient = $slevy["sleva_staly_klient"];
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	
    /*     * vytvoreni dotazu na zaklade typu pozadavku */
    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "create") {
            $dotaz = "INSERT INTO `slevy` 
							(`nazev_slevy`,`zkraceny_nazev`,`platnost_od`,`platnost_do`,`castka`,`mena`,`poznamka`,`sleva_staly_klient`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('" . $this->nazev_slevy . "','" . $this->zkraceny_nazev . "','" . $this->platnost_od . "','" . $this->platnost_do . "'," . $this->castka . ",'" . $this->mena . "','" . $this->poznamka . "'," . $this->sleva_staly_klient . ",
							  " . $this->id_zamestnance . "," . $this->id_zamestnance . " )";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "update") {
            //neupdatuju dokument_url
            $dotaz = "UPDATE `slevy` 
						SET
							`nazev_slevy`='" . $this->nazev_slevy . "',`zkraceny_nazev`='" . $this->zkraceny_nazev . "',
							`platnost_od`='" . $this->platnost_od . "',`platnost_do`='" . $this->platnost_do . "',
							`castka`=" . $this->castka . ",`mena`='" . $this->mena . "',`poznamka`='" . $this->poznamka . "',`sleva_staly_klient`='" . $this->sleva_staly_klient . "',
							`id_user_edit`=" . $this->id_zamestnance . "
						WHERE `id_slevy`=" . $this->id_slevy . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `slevy` 
						WHERE `id_slevy`=" . $this->id_slevy . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "edit") {
            $dotaz = "SELECT * FROM `slevy` 
						WHERE `id_slevy`=" . $this->id_slevy . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show") {
            $dotaz = "SELECT * FROM `slevy` 
						WHERE `id_slevy`=" . $this->id_slevy . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `slevy` 
						WHERE `id_slevy`=" . $this->id_slevy . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
    }

    /*     * kontrola zda smi uzivatel provest danou akci */

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
        } else if ($typ_pozadavku == "update") {
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
        //kontrolovaná data: název typ/název destinace, id_zeme (u destinací)
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {
            if (!Validace::text($this->nazev_slevy)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit název fotografie");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*     * zobrazeni formulare pro vytvoreni/editaci fotografie */

    function show_form() {
        //vytvorim jednotliva pole
        $nazev = "<div class=\"form_row\"> <div class=\"label_float_left\">Popis slevy (zobrazen u zájezdu po pøejetí myší): <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"nazev_slevy\" type=\"text\" value=\"" . $this->nazev_slevy . "\" class=\"width-500px\"/></div></div>\n";
        $zkr_nazev = "<div class=\"form_row\"> <div class=\"label_float_left\">Text zobrazený na webu: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"zkraceny_nazev\" type=\"text\" value=\"" . $this->zkraceny_nazev . "\" class=\"width-500px\" /></div></div>\n";
        $platnost_od = "<div class=\"form_row\"> <div class=\"label_float_left\">platnost od (dd.mm.rrrr): </div><div class=\"value\"> <input name=\"platnost_od\" type=\"text\" value=\"" . $this->change_date_en_cz($this->platnost_od) . "\" class=\"date calendar-ymd\"/></div></div>\n";
        $platnost_do = "<div class=\"form_row\"> <div class=\"label_float_left\">platnost do (nevypln=neomez): </div><div class=\"value\"> <input name=\"platnost_do\" type=\"text\" value=\"" . $this->change_date_en_cz($this->platnost_do) . "\" class=\"date calendar-ymd\"/></div></div>\n";
        $staly_klient = "<div class=\"form_row\"> <div class=\"label_float_left\">sleva pro stálé klienty: </div><div class=\"value\"> <input name=\"sleva_staly_klient\" type=\"checkbox\" value=\"1\" " . ($this->sleva_staly_klient == 1 ? ("checked=\"checked\"") : ("")) . "/></div></div>\n";



        $castka = "<div class=\"form_row\"> <div class=\"label_float_left\">velikost slevy: </div><div class=\"value\"> <input name=\"castka\" type=\"text\" value=\"" . $this->castka . "\" class=\"bigNumber\"/></div></div>\n";
        $mena = "<div class=\"form_row\"> <div class=\"label_float_left\">mìna slevy (procenta nebo kè): </div><div class=\"value\"> 
			<select name=\"mena\">
					<option value=\"%\" " . (($this->mena == "%") ? ("selected=\"selected\"") : ("")) . ">%</option>
					<option value=\"Kè\" " . (($this->mena == "Kè") ? ("selected=\"selected\"") : ("")) . ">Kè</option>
				</select>\n</div></div>\n";

        $poznamka = "<div class=\"form_row\"> <div class=\"label_float_left\">poznamka (neveøejná):</div><div class=\"value\"> <textarea name=\"poznamka\" rows=\"3\" cols=\"100\">" . $this->poznamka . "</textarea></div></div>\n";



        //tvorba select zeme (pouze pri novem serialu)
        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=slevy&amp;pozadavek=create";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit = "<input type=\"submit\" value=\"Vytvoøit slevu\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení slevy</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {
            //cil formulare
            $action = "?id_slevy=" . $this->id_slevy . "&amp;typ=slevy&amp;pozadavek=update";

            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni editovat dokument!
            //tzn pokud bud ma pravo editovat libovolny dokument, nebo pokud je to jeho dokument a on ma pravo ho editovat
            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit slevu\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci teto slevy</strong>\n";
            }
        }

        $vystup = "<form action=\"" . $action . "\" method=\"post\">" .
                $zkr_nazev .$nazev .  $staly_klient . $platnost_od . $platnost_do . $castka . $mena . $poznamka . $submit .
                "</form>";
        return $vystup;
    }

    function get_id() {
        return $this->id_slevy;
    }

    function get_nazev() {
        return $this->serial["nazev"];
    }
    
    function get_nazev_slevy() {
        return $this->nazev_slevy;
    }

    function get_id_user_create() {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_foto == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }

}

?>
