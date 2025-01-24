<?php

/**
 * klient.inc.php - tridy pro zobrazeni informcí o klientovi
 */
/* --------------------- SERIAL ------------------------------------------- */
class Topologie extends Generic_data_class {

    //vstupni data
    protected $typ_pozadavku;
    protected $minuly_pozadavek; //nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
    protected $id_zamestnance;
    protected $id_topologie;
    protected $id_tok_topologie;
    protected $nazev_topologie;
    protected $poznamka;
    protected $id_klient;
    protected $id_polozka;
    
    protected $zobrazit_id_klient;
    protected $zobrazit_id_objednavka;
    protected $zobrazit_nazev;
    protected $zobrazit_odjezd;
    protected $zobrazit_topologii;
    public $database; //trida pro odesilani dotazu


//------------------- KONSTRUKTOR -----------------
    /* konstruktor tøídy na základì typu požadavku a formularovych poli */

    function __construct(
    $typ_pozadavku, $id_zamestnance, $id_topologie = "", $id_tok_topologie = ""
    ) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_topologie = $this->check_int($id_topologie);
        $this->id_tok_topologie = $this->check_int($id_tok_topologie);
        $this->typ_pozadavku = $this->check($typ_pozadavku);

        $this->nazev_topologie = $this->check($_POST["nazev_topologie"]);
        $this->poznamka = $this->check($_POST["poznamka"]);

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            if ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update") {

                if ($this->typ_pozadavku == "create") {
                    $data = $this->database->transaction_query($this->create_query("create_topologie"), 1)
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $this->id_topologie = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                } else if ($this->typ_pozadavku == "update") {
                    $data = $this->database->transaction_query($this->create_query("update_topologie"), 1)
                            or $this->chyba("Chyba pøi dotazu do databáze: " . $this->create_query("update_topologie") . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $data2 = $this->database->transaction_query($this->create_query("delete_polozky_topologie"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . $this->create_query("delete_polozky_topologie") . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                }
                //  print_r( $_POST["serialized_grid"]);
                $polozky = json_decode(utf8_encode($_POST["serialized_grid"]));
                if (sizeof((array)$polozky) == 0) {
                    $this->chyba("Chyba pøi dekódování møížky sedadel: " . $_POST["serialized_grid"]);
                }

                foreach ($polozky as $polozka) {
                    $this->classes = $polozka->classes;
                    $this->col = $polozka->col;
                    $this->row = $polozka->row;
                    $this->size_x = $polozka->size_x;
                    $this->size_y = $polozka->size_y;
                    $this->text = utf8_decode($polozka->htmlContent);

                    $this->database->transaction_query($this->create_query("create_polozka"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . $this->create_query("create_polozka") . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                }

                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->database->commit();
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                } else {
                    $this->database->rollback();
                }
            } else if (($this->typ_pozadavku == "edit" or $this->typ_pozadavku == "show") and $this->minuly_pozadavek != "update") {
                $this->data = $this->database->query($this->create_query("edit"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->informace = mysqli_fetch_array($this->data);
                $this->id_topologie = $this->informace["id_topologie"];
                $this->nazev_topologie = $this->informace["nazev_topologie"];
                $this->poznamka = $this->informace["poznamka"];
                
            } else if (($this->typ_pozadavku == "zasedaci_poradek") and $this->minuly_pozadavek != "update") {
                $this->data = $this->database->query($this->create_query("edit_zasedaci_poradek"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->informace = mysqli_fetch_array($this->data);
                $this->id_topologie = $this->informace["id_topologie"];
                $this->id_termin = $this->informace["id_termin"];
                $this->id_objekt_kategorie = $this->informace["id_objekt_kategorie"];
                $this->nazev_tok = $this->informace["nazev_tok"];
                $this->poznamka_tok = $this->informace["poznamka_tok"];    
                
                $this->zobrazit_id_klient = $this->check_int($this->informace["zobrazit_id_klient"]);
                $this->zobrazit_id_objednavka = $this->check_int($this->informace["zobrazit_id_objednavka"]);
                $this->zobrazit_nazev = $this->check_int($this->informace["zobrazit_nazev"]);
                $this->zobrazit_odjezd = $this->check_int($this->informace["zobrazit_odjezd"]);
                $this->zobrazit_topologii = $this->check_int($this->informace["zobrazit_topologii"]);
                
            } else if ($this->typ_pozadavku == "update_zasedaci_poradek") {
                                
                $this->nazev = $this->check($_POST["nazev"]);
                $this->poznamka = $this->check($_POST["poznamka"]);
                $this->id_termin = $this->check_int($_POST["id_termin"]);
                $this->id_objekt_kategorie = $this->check_int($_POST["id_objekt_kategorie"]);
                
                $this->zobrazit_id_klient = $this->check_int($_POST["zobrazit_id_klient"]);
                $this->zobrazit_id_objednavka = $this->check_int($_POST["zobrazit_id_objednavka"]);
                $this->zobrazit_nazev = $this->check_int($_POST["zobrazit_nazev"]);
                $this->zobrazit_odjezd = $this->check_int($_POST["zobrazit_odjezd"]);         
                $this->zobrazit_topologii = $this->check_int($_POST["zobrazit_topologii"]);
        
                $this->database->transaction_query($this->create_query("update_tok"),1)                        
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->database->transaction_query($this->create_query("update_zasedaci_poradek_header"))                        
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $i = 1;
             //   print_r($_POST);
                while ($_POST["id_polozka_" . $i] > 0 and $i <= 1500) {
                    $this->id_polozka = $this->check_int($_POST["id_polozka_$i"]);
                    $this->id_klient = $this->check_int($_POST["id_klient_" . $_POST["id_polozka_$i"]]);
                    if ($this->id_klient < 0){
                        //misto je obsazeno nejakou technickou osobou (kuchar, pruvodce...)                        
                        $this->obsazeno = 1;
                        $this->text_obsazeno = $this->check($_POST["text_" . $this->id_klient]);
                   //     echo $this->text_obsazeno. "text_" . $this->id_klient;
                        $this->id_klient = "NULL";
                        
                    }else if ($this->id_klient == 0) {
                        //prazdna sedacka
                        $this->id_klient = "NULL";
                        $this->obsazeno = 0;
                        $this->text_obsazeno = "";
                    }else{
                        //sedacka obsazena klientem
                        $this->obsazeno = 1;
                        $this->text_obsazeno = "";
                    }
                    $this->database->transaction_query($this->create_query("update_zasedaci_poradek"))
                            or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                 //   echo $this->create_query("update_zasedaci_poradek") . "\n<br/>";
                    $i++;
                }

                if (!$this->get_error_message()) {
                    $this->database->commit();
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                } else {
                    $this->database->rollback();
                }
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	
    /*     * vytvoreni dotazu na zaklade typu pozadavku */
    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "create_topologie") {
            $dotaz = "INSERT INTO `topologie`(`nazev_topologie`, `poznamka`) 
                                    VALUES ('" . $this->nazev_topologie . "','" . $this->poznamka . "')";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "update_topologie") {
            $dotaz = "
                            UPDATE `topologie` SET `nazev_topologie`='" . $this->nazev_topologie . "', `poznamka`='" . $this->poznamka . "' WHERE `id_topologie`=" . $this->id_topologie . "
                            limit 1       ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "delete_polozky_topologie") {
            $dotaz = "
                            DELETE FROM `topologie_polozka` WHERE `id_topologie`=" . $this->id_topologie . "     ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "create_polozka") {
            $dotaz = "
                            INSERT INTO `topologie_polozka`(`id_topologie`, `size_x`, `size_y`, `col`, `row`, `class`, `text`) 
                            VALUES ($this->id_topologie,$this->size_x,$this->size_y,$this->col,$this->row,\"$this->classes\",\"$this->text\")  ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "edit") {
            $dotaz = "
                            select * FROM `topologie` WHERE `id_topologie`=" . $this->id_topologie . "     ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "edit_zasedaci_poradek") {
            $dotaz = "
                            select * from `topologie_tok` 
                               join `objekt_kategorie_termin` on (`topologie_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and `topologie_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) 
                               where `topologie_tok`.`id_tok_topologie`= ".$this->id_tok_topologie."    ";
            //echo $dotaz . "<br/>";
            return $dotaz;    
        } else if ($typ_pozadavku == "get_polozky") {
            $dotaz = "
                            select * FROM `topologie_polozka` WHERE `id_topologie`=" . $this->id_topologie . "  order by row,col   ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "update_zasedaci_poradek") {
            $dotaz = "
                            update `topologie_tok_polozka` set `id_klient`=" . $this->id_klient . ", `obsazeno`=" . $this->obsazeno . ",`text_obsazeno`=\"" . $this->text_obsazeno . "\" WHERE `id_polozka`=" . $this->id_polozka . " and `id_tok_topologie`=" . $this->id_tok_topologie . "  limit 1  ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "update_zasedaci_poradek_header") {
            $dotaz = "
                            update `topologie_tok` set `zobrazit_id_klient`=" . $this->zobrazit_id_klient . ",     
                                `zobrazit_id_objednavka`=" . $this->zobrazit_id_objednavka . ",
                                `zobrazit_nazev`=" . $this->zobrazit_nazev . ",
                                `zobrazit_odjezd`=" . $this->zobrazit_odjezd . ",
                                `zobrazit_topologii`=" . $this->zobrazit_topologii . "       
                                WHERE `id_tok_topologie`=" . $this->id_tok_topologie . "  limit 1  ";
            //echo $dotaz . "<br/>";
            return $dotaz;    
        } else if ($typ_pozadavku == "update_tok") {
            $dotaz = "
                            update `objekt_kategorie_termin` set `nazev_tok`=\"" . $this->nazev . "\",`poznamka_tok`=\"" . $this->poznamka . "\" WHERE `id_termin`=" . $this->id_termin . " and `id_objekt_kategorie`=" . $this->id_objekt_kategorie . "  limit 1  ";
            //echo $dotaz . "<br/>";
            return $dotaz;    
        } else if ($typ_pozadavku == "get_polozky_tok") {
            $dotaz = "
                            select `topologie_tok_polozka`.*, jmeno,prijmeni,objednavka_osoby.storno, `objednavka`.`id_objednavka`,`zajezd_tok_topologie`.`id_zajezd`, `serial`.`nazev`,`serial`.`id_sablony_zobrazeni`,
                                    GROUP_CONCAT(distinct `cena`.`nazev_ceny` separator \", \") as `odjezdova_mista` ,
                                    group_concat(DISTINCT `objekt`.`nazev_objektu` separator \", \") as `nazev_ubytovani`  
                            FROM `topologie_tok_polozka` 
                            left join (user_klient
                                join objednavka_osoby on (`user_klient`.`id_klient` = `objednavka_osoby`.`id_klient`)
                                join objednavka on (`objednavka_osoby`.`id_objednavka` = `objednavka`.`id_objednavka`)
                                join zajezd_tok_topologie on (`objednavka`.`id_zajezd` = `zajezd_tok_topologie`.`id_zajezd`)
                                join serial on (`objednavka`.`id_serial` = `serial`.`id_serial`)  
                                left join (`objekt_serial` join
                                    `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)                                
                                left join (
                                    `objednavka_cena` 
                                    join `cena` on (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `cena`.`typ_ceny`=5)
                                ) on (`objednavka`.`id_objednavka` = `objednavka_cena`.`id_objednavka` and `objednavka_cena`.`pocet`>0) 
                            ) on (`topologie_tok_polozka`.`id_tok_topologie` = `zajezd_tok_topologie`.`id_tok_topologie` and  `topologie_tok_polozka`.`id_klient` = `user_klient`.`id_klient`) 
                            WHERE `topologie_tok_polozka`.`id_tok_topologie`=" . $this->id_tok_topologie . "  group by `id_polozka` order by row,col   ";
            //echo $dotaz . "<br/>";
            return $dotaz;            
        } else if ($typ_pozadavku == "get_osoby_no_topologie") {
            $dotaz = "
                            select jmeno,prijmeni,`user_klient`.`id_klient`,`objednavka`.`id_objednavka`,`zajezd_tok_topologie`.`id_zajezd`, `serial`.`nazev`,`serial`.`id_sablony_zobrazeni`,
                                    GROUP_CONCAT(distinct `cena`.`nazev_ceny` separator \", \") as `odjezdova_mista`,
                                    group_concat(DISTINCT `objekt`.`nazev_objektu` separator \", \") as `nazev_ubytovani` 
                                    FROM `topologie_tok` 
                                join zajezd_tok_topologie on (`topologie_tok`.`id_tok_topologie` = `zajezd_tok_topologie`.`id_tok_topologie`)
                                join objednavka on (`zajezd_tok_topologie`.`id_zajezd` = `objednavka`.`id_zajezd`)                                
                                join objednavka_osoby on (`objednavka_osoby`.`id_objednavka` = `objednavka`.`id_objednavka`)
                                join user_klient on (`objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`)
                                join serial on (`objednavka`.`id_serial` = `serial`.`id_serial`)
                                left join (`objekt_serial` join
                                    `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)                                                                
                                left join (
                                    `objednavka_cena` 
                                    join `cena` on (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `cena`.`typ_ceny`=5)
                                ) on (`objednavka`.`id_objednavka` = `objednavka_cena`.`id_objednavka` and `objednavka_cena`.`pocet`>0) 
                                
                                left join `topologie_tok_polozka` on (`topologie_tok_polozka`.`id_klient` = `user_klient`.`id_klient` and `topologie_tok_polozka`.`id_tok_topologie`=`topologie_tok`.`id_tok_topologie`)
                            
                            WHERE objednavka.stav!=8 and  objednavka.stav!=9 and `topologie_tok`.`id_tok_topologie`=" . $this->id_tok_topologie . " and objednavka_osoby.storno!=1 and `topologie_tok_polozka`.`id_klient` is null
                            GROUP BY  `user_klient`.`id_klient`   
                            ";
            //echo $dotaz . "<br/>";
            return $dotaz;
        } else if ($typ_pozadavku == "get_pocet_sedadel") {
            $dotaz = "select count(distinct `id_polozka`) as `sedadel`
                                    from `topologie`
						left join `topologie_polozka` on (`topologie`.`id_topologie`=`topologie_polozka`.`id_topologie` and `class` not like '%noseat%')
					where `topologie`.`id_topologie`=" . $this->id_topologie . "";
            //echo $dotaz . "<br/>";
            return $dotaz;
        }
    }

    /* vyhodi pocet sedadel - potreba pro vytvareni TOK */

    public function get_pocet_sedadel() {
        $data_polozky = $this->database->query($this->create_query("get_pocet_sedadel"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        //echo $this->get_error_message();
        while ($row1 = mysqli_fetch_array($data_polozky)) {
            return $row1["sedadel"];
        }
    }

    /* vyhodi vsechny sedadla - pro kopirovani do topologie_polozka_tok */

    public function get_polozky() {
        $data_polozky = $this->database->query($this->create_query("get_polozky"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $polozky = array();
        while ($row1 = mysqli_fetch_array($data_polozky)) {
            $polozky[] = $row1;
        }
        return $polozky;
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
        } else if ($typ_pozadavku == "edit" or $typ_pozadavku == "zasedaci_poradek") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "show") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "create") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "create_ajax") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "create_account") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or ( $zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "update" or $typ_pozadavku == "update_zasedaci_poradek") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or ( $zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "delete" or $typ_pozadavku == "delete_zasedaci_poradek") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or ( $zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
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
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {
            if (!Validace::text($this->nazev_topologie)) {
                $ok = 0;
                $this->chyba("Musíte vyplnit název topologie");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /*     * zobrazeni formulare pro vytvoreni/editaci uzivatele */

    function show_form_tok() {
        /*
        $this->zobrazit_id_klient = $this->check_int($_POST["zobrazit_id_klient"]);
        $this->zobrazit_id_objednavka = $this->check_int($_POST["zobrazit_id_objednavka"]);
        $this->zobrazit_nazev = $this->check_int($_POST["zobrazit_nazev"]);
        $this->zobrazit_odjezd = $this->check_int($_POST["zobrazit_odjezd"]);*/
        
        //nazev, ico, role
        $nazev = "
                <div class='form_row'>
                    <div class='label_float_left'>Název Zasedacího Poøádku <span class=\"red\">*</span></div><div class='value'><input id=\"nazev\" name=\"nazev\" type=\"text\" value=\"" . $this->nazev_tok . "\" class=\"wide\" size=\"80\" /></div>
                </div> <div class='form_row'>       
                    <div class='label_float_left'>Poznámka </div><div class='value'><input id=\"poznamka\" name=\"poznamka\" type=\"text\" value=\"" . $this->poznamka_tok . "\" class=\"wide\" size=\"80\"/></div>
                </div>  
                <div class='form_row'>       
                    <div class='label_float_left'>Zobrazit informace: </div><div class='value'>
                        <input class=\"klientDisplayFields\" id=\"zobrazit_id_klient\" name=\"zobrazit_id_klient\" type=\"checkbox\" value=\"1\" ".($this->zobrazit_id_klient == 1 ? "checked=\"checked\"":"")." /> ID klienta
                        <input class=\"klientDisplayFields\" id=\"zobrazit_id_objednavka\" name=\"zobrazit_id_objednavka\" type=\"checkbox\" value=\"1\" ".($this->zobrazit_id_objednavka == 1 ? "checked=\"checked\"":"")." /> ID objednávky
                        <input class=\"klientDisplayFields\" id=\"zobrazit_nazev\" name=\"zobrazit_nazev\" type=\"checkbox\" value=\"1\" ".($this->zobrazit_nazev == 1 ? "checked=\"checked\"":"")." /> Název seriálu    
                        <input class=\"klientDisplayFields\" id=\"zobrazit_odjezd\" name=\"zobrazit_odjezd\" type=\"checkbox\" value=\"1\" ".($this->zobrazit_odjezd == 1 ? "checked=\"checked\"":"")." /> Odjezdové místo        
                    </div>
                </div> 
                <div class='form_row'>       
                    <div class='label_float_left'>Zobrazit topologii agenturám: </div><div class='value'>
                        <input id=\"zobrazit_topologii\" name=\"zobrazit_topologii\" type=\"checkbox\" value=\"1\" ".($this->zobrazit_topologii == 1 ? "checked=\"checked\"":"")." /> Zobrazí topologii (pouze volná/obsazená místa) pøihlášeným prodejcùm u konkrétního zájezdu
                    </div>
                </div> 
                <input name=\"id_termin\" type=\"hidden\" value=\"" . $this->id_termin . "\" />
                <input name=\"id_objekt_kategorie\" type=\"hidden\" value=\"" . $this->id_objekt_kategorie . "\" />
                   ";

        $submit = "";
        $width = 100;
        $height = 80;
        if ($this->typ_pozadavku == "zasedaci_poradek") {
            $polozky = "";
            $data_polozky = $this->database->query($this->create_query("get_polozky_tok"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            
            $max_width = 0;
            $max_height = 0;
            $cislo_sedadla = 0;
            $cislo_technickeho_sedadla = 0;
            $j = 0;
            while ($row1 = mysqli_fetch_array($data_polozky)) {
                $j++;
                $polozka_width = $row1["size_x"] * $width . "px";
                $polozka_height = $row1["size_y"] * $height . "px";
                $polozka_top = ($row1["row"] - 1) * $height . "px";
                $polozka_left = ($row1["col"] - 1) * $width . "px";
                $polozka_style = "width:$polozka_width;height:$polozka_height;top:$polozka_top;left:$polozka_left;";
                if ($max_width < ($row1["size_x"] + $row1["col"] - 1)) {
                    $max_width = ($row1["size_x"] + $row1["col"] - 1);
                }
                if ($max_height < ($row1["size_y"] + $row1["row"] - 1)) {
                    $max_height = ($row1["size_y"] + $row1["row"] - 1);
                }
                if($row1["storno"]==1){
                  $color = " style='background-color:red' title='Stornovaná osoba'";
                }else{
                  $color = "";
                }
                
                if (stripos($row1["class"], "noseat") !== FALSE) {
                    $polozka_class = "noseat";
                    $sedadlo_text = "<div class=\"text\">" . $row1["text"] . "<input type=\"hidden\" class=\"polozka_id\" name=\"id_polozka_$j\" value=\"" . $row1["id_polozka"] . "\"/></div>";
                } else {
                    $cislo_sedadla++;
                    if ($row1["id_klient"] > 0) {
                        $polozka_class = "drop-target-swap seat";                        
                        $row1["jmeno"] = substr($row1["jmeno"], 0, 1).".";
                        $row1["odjezdova_mista"] = trim(str_replace(array("odjezdové","odjezdová","místa","místo","odjezd"), "", $row1["odjezdova_mista"]));
                        if($row1["id_sablony_zobrazeni"]== 12 and $row1["nazev_ubytovani"]!=""){
                            $row1["nazev"] = $row1["nazev_ubytovani"];
                        }
                        
                        $sedadlo_text = "<div style=\"width:100px;height:22px;font-weight:bold;text-align:center;\">$cislo_sedadla"
                                . "<input type=\"hidden\" class=\"polozka_id\" name=\"id_polozka_$j\" value=\"" . $row1["id_polozka"] . "\"/></div>"
                                . "<div class=\"text\">" . $row1["text"] . "</div>"
                                . "<div id=\"person-" . $row1["id_klient"] . "\" draggable=\"true\" class=\"person dragable\" $color>" . $row1["prijmeni"] . " " . $row1["jmeno"] . "<span class=\"display_id_klient\"><br/>(ID: " . $row1["id_klient"] . ")</span><span class=\"display_id_objednavka\"><br/>(ID obj:" . $row1["id_objednavka"] . ")</span><span class=\"display_nazev\"><br/>" . $row1["nazev"] . "</span><span class=\"display_odjezd\"><br/>" . $row1["odjezdova_mista"] . "</span><input type=\"hidden\" class=\"klient_id\" name=\"id_klient_" . $row1["id_polozka"] . "\" value=\"" . $row1["id_klient"] . "\"/></div></li>";

                    }else if($row1["obsazeno"] == 1){
                        $cislo_technickeho_sedadla ++;
                        //technicke misto
                        $polozka_class = "drop-target-swap seat"; 
                        $sedadlo_text = "<div style=\"width:100px;height:22px;font-weight:bold;text-align:center;\">$cislo_sedadla"
                                . "<input type=\"hidden\" class=\"polozka_id\" name=\"id_polozka_$j\" value=\"" . $row1["id_polozka"] . "\"/></div>"
                                . "<div class=\"text\">" . $row1["text"] . "</div>"
                                . "<div id=\"person-new-100" .$cislo_technickeho_sedadla. "\" draggable=\"true\" class=\"new_field dragable\">" .$row1["text_obsazeno"]. "<input type=\"hidden\" name=\"text_-100" .$cislo_technickeho_sedadla. "\" value=\"" .$row1["text_obsazeno"].  "\" /><input type=\"hidden\"  class=\"klient_id\"  name=\"id_klient_" . $row1["id_polozka"] . "\" value=\"-100" .$cislo_technickeho_sedadla. "\" /></div></li>";                            
                        
                    }else{
                        $polozka_class = "drop-target seat";                        
                        $sedadlo_text = "<div style=\"width:100px;height:22px;font-weight:bold;text-align:center;\">$cislo_sedadla"
                                . "<input type=\"hidden\" class=\"polozka_id\" name=\"id_polozka_$j\" value=\"" . $row1["id_polozka"] . "\"/></div>"
                                . "<div class=\"text\">" . $row1["text"] . "</div>";
                    }
                }

                $polozka = "<li class=\"$polozka_class\" style=\"$polozka_style\">$sedadlo_text</li>";
                $polozky .= $polozka;
            }
            $ul_width = $max_width * $width;
            $ul_height = $max_height * $height;
            $ul_style = " style=\"width:" . $ul_width . "px; height:" . $ul_height . "px; \"";
            $polozky_start = "<h2>Dopravní prostøedek</h2><div id=\"seat-list\">"
                    . "<ul class=\"grid\" $ul_style>";

            $polozky = $polozky_start . $polozky . "</ul></div>";


            //cil formulare
            $action = "?typ=topologie&amp;pozadavek=update_zasedaci_poradek&amp;id_serial=".$_GET["id_serial"]."&id_zajezd=".$_GET["id_zajezd"]."&amp;id_tok_topologie=" . $this->id_tok_topologie . "&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("edit")) {
                $submit = "<input type=\"hidden\" name=\"id_topologie\" value=\"" . $this->id_topologie . "\" />";
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit .="  <input type=\"submit\" name=\"ulozit\" class=\"ulozit_topologii\" value=\"Uložit\" />\n
                                                    <input type=\"submit\" name=\"ulozit_a_zavrit\" class=\"ulozit_topologii\" value=\"Uložit a Zavøít\" />
                                                    <input type=\"submit\" name=\"ulozit_a_vygenerovat\" class=\"ulozit_topologii\" value=\"Uložit a Vygenerovat PDF\" />";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní</strong>\n";
            }
        }
        $data_osoby = $this->database->query($this->create_query("get_osoby_no_topologie"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $osoby = "<h2>Seznam osob</h2>
                <ul id=\"name-list\" class=\"drop-target\" style=\"margin-left:20px;\">
                    <li id=\"return_field\" class=\"drop-target\" style=\"width:150px;height:40px;background-color:#efefca;border:2px dashed orange;font-weight:bold;text-align:center;\" >Zde odstranit klienta ze zasedacího poøádku</li>";
        while ($row2 = mysqli_fetch_array($data_osoby)) {
            $row2["jmeno"] = substr($row2["jmeno"], 0, 1).".";
            $row2["odjezdova_mista"] = trim(str_replace(array("odjezdové","odjezdová","místa","místo","odjezd"), "", $row2["odjezdova_mista"]));
            if($row2["id_sablony_zobrazeni"]== 12 and $row2["nazev_ubytovani"]!=""){
                $row2["nazev"] = $row2["nazev_ubytovani"];
            }
            $osoby .= "<li id=\"person-" . $row2["id_klient"] . "\" draggable=\"true\" class=\"person dragable\">" . $row2["prijmeni"] . " " . $row2["jmeno"] . "<span class=\"display_id_klient\"><br/>(ID: " . $row2["id_klient"] . ")</span><span class=\"display_id_objednavka\"><br/>(ID obj:" . $row2["id_objednavka"] . ")</span><span class=\"display_nazev\"><br/>" . $row2["nazev"] . "</span><span class=\"display_odjezd\"><br/>" . $row2["odjezdova_mista"] . "</span><input type=\"hidden\" class=\"klient_id\" name=\"id_klient_0\" value=\"" . $row2["id_klient"] . "\"/></li>";
        }
        $osoby .=  "</ul>";

        $nove_osoby = "<h2>nové osoby/obsazené místa</h2>
                <ul id=\"new-list\" class=\"drop-target\" style=\"margin-left:20px;\">
                    <li id=\"create_new_field\" style=\"width:200px;height:40px;background-color:#daefda;border:2px dashed green;font-weight:bold;text-align:center;\" >
                        Pøidat obsazené místo s textem:<br/>
                        <input id=\"new_field_text\" type=\"text\" value=\"\" /><input type=\"button\" id=\"new_field_create\" value=\"&gt;&gt;\">
                        </li>";
        
        $vystup = $script . $gridster_deserialize . "<form action=\"" . $action . "\" method=\"post\">
                          " . $nazev .
                "<table><tr><td valign=\"top\">" . $polozky . "<td valign=\"top\">" . $osoby . "<td valign=\"top\">" . $nove_osoby . "</table>
                         " . $submit . "

                    </form>";
        return $vystup;
    }

    /*     * zobrazeni formulare pro vytvoreni/editaci uzivatele */

    function show_form() {
        //nazev, ico, role
        $nazev = "
                <div class='form_row'>
                    <div class='label_float_left'>Název topologie <span class=\"red\">*</span></div><div class='value'><input id=\"nazev\" name=\"nazev_topologie\" type=\"text\" value=\"" . $this->nazev_topologie . "\" class=\"wide\" size=\"80\" /></div>
                </div> <div class='form_row'>       
                    <div class='label_float_left'>Poznámka </div><div class='value'><input id=\"poznamka\" name=\"poznamka\" type=\"text\" value=\"" . $this->poznamka . "\" class=\"wide\" size=\"80\"/></div>
                </div>
                        
                   ";
        if ($this->typ_pozadavku == "new") {
            $create_grid_submit = "<input  name=\"createGrid\" type=\"button\" value=\"Vytvoøit\" onclick=\"create_grid();\" />";
        } else {
            $create_grid_submit = "<input  name=\"createGrid\" type=\"button\" value=\"Vytvoøit\" style=\"disabled: true;\" />";
        }
        $create_grid = "
                <div class='form_row'>
                    <div class='label_float_left'>Vytvoøit základní møížku:</div><div class='value'>
                        <input id=\"grid_x\" name=\"grid_x\" type=\"text\" value=\"5\" /> sloupcù,  
                        <input id=\"grid_y\" name=\"grid_y\" type=\"text\" value=\"12\" /> øádkù  
                        <input  name=\"createGrid\" type=\"button\" value=\"Vytvoøit\" onclick=\"create_grid();\" /></div></div>
                <div class='form_row' id=\"addSingleWidget\"></div>        
                   ";
        $grid = " <div class=\"gridster\">                
                        <ul></ul>
                    </div>";
        $submit = "";
        if ($this->typ_pozadavku == "new") {
            $gridster_deserialize = "";
            //cil formulare
            $action = "?typ=topologie&amp;pozadavek=create&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("create")) {
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit = "  <input type=\"submit\" name=\"ulozit\" class=\"ulozit_topologii\" value=\"Uložit\" />\n
                                                    <input type=\"submit\" name=\"ulozit_a_zavrit\" class=\"ulozit_topologii\" value=\"Uložit a Zavøít\" />\n
                                                    <input type=\"hidden\" name=\"serialized_grid\" id=\"serialized_grid\" />";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení objektu</strong>\n";
            }
        } else if ($this->typ_pozadavku == "edit") {

            $data_polozky = $this->database->query($this->create_query("get_polozky"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            $gridster_deserialize = "<script type=\"text/javascript\" >
                        var serialization = [";
            $i = 0;
            while ($row1 = mysqli_fetch_array($data_polozky)) {
                if ($i > 0) {
                    $gridster_deserialize .= ",";
                }
                $gridster_deserialize .= "{
                                col: " . $row1["col"] . ",
                                row: " . $row1["row"] . ",
                                size_x: " . $row1["size_x"] . ",
                                size_y: " . $row1["size_y"] . ",
                                class:  \"" . $row1["class"] . "\",
                                text: \"" . $row1["text"] . "\"
                            }";
                $i++;
            }
            $gridster_deserialize .= " ]; 
                 serialization = Gridster.sort_by_row_and_col_asc(serialization);


        $('document').ready( function() {
            document.getElementById(\"grid_x\").value=1;
            document.getElementById(\"grid_y\").value=1; 
            create_gridster();
            gridster.remove_all_widgets();
            $.each(serialization, function() {
                var buttons = '';
                var trida = '';
                if(this.class.indexOf(\"noseat\") >= 0){
                    buttons = buttons_noseat;
                    trida = 'class=\"gs_w noseat\"';
                }else{
                    buttons = buttons_seat;
                }
                var widget = ['<li data-row=\"'+this.row+'\" data-col=\"'+this.col+'\" '+trida+'><span class=\"count\">'+count+'</span><div class=\"buttons\" style=\"float:right;\">'+buttons+'</div><div class=\"text\" style=\"margin-top:20px;\">'+this.text+'</div></li>', this.size_x, this.size_y, this.col, this.row];
                gridster.add_widget.apply(gridster, widget);
            });
            recalculate_seat_numbers();
        });

                        </script>";

            //cil formulare
            $action = "?typ=topologie&amp;pozadavek=update&amp;id_topologie=" . $this->id_topologie . "&amp;moznosti_editace=" . $_GET["moznosti_editace"] . "";
            //tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
            if ($this->legal("edit")) {
                $submit = "<input type=\"hidden\" name=\"id_topologie\" value=\"" . $this->id_topologie . "\" />";
                //tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
                $submit .="  <input type=\"submit\" name=\"ulozit\" class=\"ulozit_topologii\" value=\"Uložit\" />\n
                                                    <input type=\"submit\" name=\"ulozit_a_zavrit\" class=\"ulozit_topologii\" value=\"Uložit a Zavøít\" />\n
                                                    <input type=\"hidden\" name=\"serialized_grid\" id=\"serialized_grid\" />";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení objektu</strong>\n";
            }
        }

        $script = "
               <script type=\"text/javascript\"  src=\"/admin/js/topologie.js\" ></script>
               <script src=\"/admin/gridster/dist/jquery.gridster.js\" type=\"text/javascript\" charset=\"utf-8\"></script>

            ";


        $vystup = $script . $gridster_deserialize . "<form action=\"" . $action . "\" method=\"post\">
                              " . $nazev . $create_grid . $grid . "
                             " . $submit . "
                        </form>";
        return $vystup;
    }

    function get_id() {
        return $this->id_objektu;
    }

    function get_nazev() {
        return $this->informace["nazev_topologie"];
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
