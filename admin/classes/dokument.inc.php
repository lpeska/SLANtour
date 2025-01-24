<?php

/**
 * dokument.inc.php - tridy pro zobrazeni dokumentu
 */
/* --------------------- SERIAL ------------------------------------------- */
class Dokument extends Generic_data_class {

    //vstupni data
    protected $typ_pozadavku;
    protected $minuly_pozadavek; //nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
    protected $id_zamestnance;
    protected $id_dokument;
    protected $datum_vytvoreni;
    protected $nazev_dokument;
    protected $popisek_dokument;
    protected $dokument_adress;
    protected $id_user_create;
    protected $data;
    protected $dokument;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /*     * konstruktor tøídy na základì typu pozadavku */

    function __construct($typ_pozadavku, $id_zamestnance, $id_dokument = "", $nazev_dokument = "", $popisek_dokument = "", $posted_dokument = "", $is_tiskova_zprava = "", $minuly_pozadavek = "") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->minuly_pozadavek = $this->check($minuly_pozadavek);
        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_dokument = $this->check_int($id_dokument);
        $this->is_tiskova_zprava = $this->check_int($is_tiskova_zprava);
        $this->datum_vytvoreni = Date("Y-m-d");
        $this->nazev_dokument = $this->check_slashes($this->check($nazev_dokument));
        $this->popisek_dokument = $this->check_slashes($this->check($popisek_dokument));
        $this->id_user_create = $this->get_id_user_create();

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            if ($this->typ_pozadavku == "create") {

                @chmod("../" . ADRESAR_DOKUMENT, 0777);

                //uploaduju dokument	
                /* zjistim dalsi nepouzite cislo v autoindexu
                  - funkce nezajisti ze jde presne o cislo ktere bude vlozeno - neziska dalsi hodnotu autoindexu, ale staci ze bude jedinecne */
                $porad_cislo = $this->get_next_autoid("id_dokument", "dokument");

                $pripona = strtolower(substr($_FILES['dokument']['name'], ( strrpos($_FILES['dokument']['name'], ".") + 1)));

                //pokud jsme dostali dokument
                if ($_FILES['dokument']['size'] != 0) {
                    $dokument_url = "../" . ADRESAR_DOKUMENT . "/" . $porad_cislo . "-" . $this->nazev_web($this->nazev_dokument) . "." . $pripona;

                    //adresa do databaze (neukladame adresar dokumentu)
                    $this->dokument_adress = $porad_cislo . "-" . $this->nazev_web($this->nazev_dokument) . "." . $pripona;

                    //pokud se nam podari uploadovany dokument presunout na spravne misto, muzeme ho vlozit do databaze
                    if (@move_uploaded_file($_FILES['dokument']['tmp_name'], $dokument_url)) {
                        $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                        $this->id_dokument = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                        $this->dokument["id_dokument"] = $this->id_dokument;
                    } else {
                        $this->chyba("Dokument se nepodaøilo správnì uploadovat");
                    }
                } else {
                    $this->chyba("Dokument se nepodaøilo správnì uploadovat");
                }
            } else if ($this->typ_pozadavku == "update") {
                /* oproti create zasadni rozdil: zadny soubor nemusi byt uploadovan - vymena souboru a update databaze se provadi nezavisle */
                //pokud jsme dostali dokument

                if ($_FILES['dokument']['size'] != 0) {

                    @chmod("../" . ADRESAR_DOKUMENT, 0777);

                    $pripona = strtolower(substr($_FILES['dokument']['name'], ( strrpos($_FILES['dokument']['name'], ".") + 1)));


                    $dokument_url = "../" . ADRESAR_DOKUMENT . "/" . $this->id_dokument . "-" . $this->nazev_web($this->nazev_dokument) . "." . $pripona;

                    //adresa do databaze (neukladame adresar dokumentu)
                    $this->dokument_adress = $this->id_dokument . "-" . $this->nazev_web($this->nazev_dokument) . "." . $pripona;

                    //pokud se nam podari uploadovany dokument presunout na spravne misto, muzeme ho vlozit do databaze
                    if (@move_uploaded_file($_FILES['dokument']['tmp_name'], $dokument_url)) {
                        $this->data = $this->database->query($this->create_query($this->typ_pozadavku, "with_upload"))
                                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    } else {
                        $this->chyba("Dokument se nepodaøilo správnì uploadovat");
                    }
                } else {
                    $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                }
            } else if ($this->typ_pozadavku == "delete") {
                //zjisti jaky soubor se k tomu vaze a smaz ho
                $this->data = $this->database->query($this->create_query("edit"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->dokument = mysqli_fetch_array($this->data);
                $dokument_url = "../" . ADRESAR_DOKUMENT . "/" . $this->dokument["dokument_url"];                
                unlink($dokument_url);
                    
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                //pro pozadavek edit je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
            } else if ($this->typ_pozadavku == "edit" and $this->minuly_pozadavek != "update") {
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                $this->dokument = mysqli_fetch_array($this->data);
                //jednotlive sloupce ulozim do promennych tridy
                $this->id_dokument = $this->dokument["id_dokument"];
                $this->is_tiskova_zprava = $this->dokument["is_tiskova_zprava"];
                $this->nazev_dokument = $this->dokument["nazev_dokument"];
                $this->popisek_dokument = $this->dokument["popisek_dokument"];
                $this->dokument_adress = $this->dokument["dokument_url"];
            } else if ($this->typ_pozadavku == "mass_del") {
                //zjisti ktere dokumenty se k tomu vazou a smaz je
                $this->data = $this->database->query($this->create_query("get_docs"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                while($this->dokument = mysqli_fetch_array($this->data)) {
                    $dokument_url = "../" . ADRESAR_DOKUMENT . "/" . $this->dokument["dokument_url"];
                    unlink($dokument_url);
                }
                $_POST["massdel_ids"] = $this->check($_POST["massdel_ids"]);
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }

        //pokud se akce uspìšnì zapsala do databáze, vypíšu potvrzovací hlášku
        if (!$this->get_error_message() and
                ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "delete")) {
            $this->confirm("Požadovaná akce probìhla úspìšnì");
        }
    }

//------------------- METODY TRIDY -----------------	
    /*     * vytvoreni dotazu na zaklade typu pozadavku */
    function create_query($typ_pozadavku, $with_upload = "") {
        if ($typ_pozadavku == "create") {
            $dotaz = "  INSERT INTO `dokument` 
                                (`datum_vytvoreni`,`nazev_dokument`,`popisek_dokument`,`dokument_url`,`is_tiskova_zprava`,`id_user_create`,`id_user_edit`)
                        VALUES
                                 ('" . $this->datum_vytvoreni . "','" . $this->nazev_dokument . "','" . $this->popisek_dokument . "','" . $this->dokument_adress . "',
                                  " . $this->is_tiskova_zprava . "," . $this->id_zamestnance . "," . $this->id_zamestnance . " )";            
        } else if ($typ_pozadavku == "update" and $with_upload == "with_upload") {
            //predavame i adresu noveho dokumentu
            $dotaz = "  UPDATE `dokument` 
                        SET
                                `datum_vytvoreni`='" . $this->datum_vytvoreni . "',`nazev_dokument`='" . $this->nazev_dokument . "',`popisek_dokument`='" . $this->popisek_dokument . "',
                                `dokument_url`='" . $this->dokument_adress . "',`is_tiskova_zprava`=" . $this->is_tiskova_zprava . ",`id_user_edit`=" . $this->id_zamestnance . "
                        WHERE `id_dokument`=" . $this->id_dokument . "
                        LIMIT 1";
        } else if ($typ_pozadavku == "update") {
            //neupdatuju dokument_url
            $dotaz = "  UPDATE `dokument` 
                        SET
                                `datum_vytvoreni`='" . $this->datum_vytvoreni . "',`nazev_dokument`='" . $this->nazev_dokument . "',`popisek_dokument`='" . $this->popisek_dokument . "',
                                `is_tiskova_zprava`=" . $this->is_tiskova_zprava . ",`id_user_edit`=" . $this->id_zamestnance . "
                        WHERE `id_dokument`=" . $this->id_dokument . "
                        LIMIT 1";
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "  DELETE FROM `dokument` 
                        WHERE `id_dokument`=" . $this->id_dokument . "
                        LIMIT 1";
        } else if ($typ_pozadavku == "edit") {
            $dotaz = "  SELECT * FROM `dokument` 
                        WHERE `id_dokument`=" . $this->id_dokument . "
                        LIMIT 1";
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "  SELECT `id_user_create` FROM `dokument` 
                        WHERE `id_dokument`=" . $this->id_dokument . "
                        LIMIT 1";
        } else if ($typ_pozadavku == "get_docs") {            
            $massDelIds = explode("::", trim($_POST["massdel_ids"], ":"));
            
            $where = "WHERE ";
            foreach($massDelIds as $id) {
                $where .= " id_dokument=$id OR ";
            }
            $where = substr($where, 0, strlen($where) - 4);
            
            $dotaz = "SELECT * FROM `dokument` $where";
        } else if ($typ_pozadavku == "mass_del") {            
            $massDelIds = explode("::", trim($_POST["massdel_ids"], ":"));
            
            $where = "WHERE ";
            foreach($massDelIds as $id) {
                $where .= " id_dokument=$id OR ";
            }
            $where = substr($where, 0, strlen($where) - 4);
            
            $dotaz = "DELETE FROM `dokument` $where";
        }
//        echo $dotaz;
        return $dotaz;
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
        } else if ($typ_pozadavku == "mass_del") {
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
            if (!Validace::text($this->nazev_dokument)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit název dokumentu");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*     * zobrazeni formulare pro vytvoreni/editaci dokumentu */

    function show_form() {
        if ($this->typ_pozadavku == "new") {
            $povinny_dokument = " <span class=\"red\">*</span>";
        }
        //vytvorim jednotliva pole
        $nazev = "<div class=\"form_row\"> <div class=\"label_float_left\">název dokumentu: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev_dokument\" type=\"text\" value=\"" . $this->nazev_dokument . "\" class=\"wide\"/></div></div>\n";
        $popisek = "<div class=\"form_row\"> <div class=\"label_float_left\">popisek dokumentu:</div> <div class=\"value\"> <textarea name=\"popisek_dokument\" rows=\"3\" cols=\"100\">" . $this->popisek_dokument . "</textarea></div></div>\n";
        $dokument = "<div class=\"form_row\"> <div class=\"label_float_left\">zadejte dokument:" . $povinny_dokument . "</div> <div class=\"value\"> <input name=\"dokument\" type=\"file\" size=\"56\" /></div></div>\n";
        $tiskova_zprava = "<div class=\"form_row\"> <div class=\"label_float_left\">Dokument je tisková zpráva: </div> <div class=\"value\"> <input name=\"is_tiskova_zprava\" type=\"checkbox\" " . (($this->is_tiskova_zprava == 1) ? ("checked=\"checked\"") : ("")) . " value=\"1\"/></div></div>\n";

        //tvorba select zeme (pouze pri novem serialu)
        if ($this->typ_pozadavku == "new") {
            //cil formulare
            $action = "?typ=dokument&amp;pozadavek=create";
            //tlacitko pro odeslani
            if ($this->legal("create")) {
                $submit = "<input type=\"submit\" value=\"Vytvoøit dokument\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení dokumentu</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {
            //cil formulare
            $action = "?id_dokument=" . $this->id_dokument . "&amp;typ=dokument&amp;pozadavek=update";
            //tlacitko pro odeslani
            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit dokument\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci tohoto dokumentu</strong>\n";
            }
        }

        $vystup = "<form action=\"" . $action . "\" method=\"post\" enctype=\"multipart/form-data\">" .
                $nazev . $popisek . $tiskova_zprava . $dokument . $submit .
                "</form>";
        return $vystup;
    }

    function get_id() {
        return $this->serial["id_serial"];
    }

    function get_nazev() {
        return $this->serial["nazev"];
    }

    function get_id_user_create() {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_dokument == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }

}

?>
