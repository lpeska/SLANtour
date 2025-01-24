<?php
/**
 * serial_cena.inc.php - trida pro zobrazeni seznamu cen serialu v administracni casti
 *                                            - a jejich create, update, delete
 */

/*------------------- SEZNAM cen seriálu -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Termin_objektove_kategorie extends Generic_list
{
    protected $typ_pozadavku;
    protected $pocet;
    protected $id_zamestnance;

    protected $id_terminu;
    protected $id_objektu;
    protected $id_objektove_kategorie;
    protected $kapacita;
    protected $kapacita_bez_omezeni;
    protected $na_dotaz;
    protected $vyprodano;
    protected $date_od;
    protected $date_do;
    protected $nazev_tok;
    protected $cena;
    protected $typ_objektu;

    //znaci ze smim pokracovat s add_to query
    protected $legal_operation;

    //prubezne konstruovany dotaz do databaze
    protected $query_insert;
    protected $query_update;
    protected $query_delete;

    protected $pocet_zaznamu_insert;
    protected $pocet_zaznamu_update;
    protected $pocet_zaznamu_delete;
    protected $cislo_ceny; //cislo zpracovavane ceny
    protected $id_serialy_array;

    private $query_update_id_termin;
    private $query_update_id_ok;
    private $query_insert_id_termin;
    private $query_insert_id_ok;
    private $query_delete_id_termin;
    private $query_delete_id_ok;

    public $database; //trida pro odesilani dotazu

    //------------------- KONSTRUKTOR -----------------
    /**konstruktor tøídy*/
    function __construct($typ_pozadavku, $id_zamestnance, $id_objektu, $id_terminu = "", $date_od = "", $date_do = "", $nazev_tok = "")
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->legal_operation = 0;
        $this->pocet_zaznamu = 0;
        $this->cislo_ceny = 0;
        $this->query = array();


        //kontrola dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_zamestnance = $this->check_int($id_zamestnance);
        $this->id_objektu = $this->check_int($id_objektu);
        $this->id_terminu = $this->check_int($id_terminu);
        $this->date_od = $this->change_datetime_cz_en($this->check($date_od));
        $this->date_do = $this->change_datetime_cz_en($this->check($date_do));

        $this->nazev_tok = $this->check($nazev_tok);

        $typ_tok_data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("objekt_info"));
        while ($typ_tok = mysqli_fetch_array($typ_tok_data)) {
            $this->typ_objektu = $typ_tok["typ_objektu"];
        }
        $this->sql_insert_map = array();

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {

            //na zaklade typu pozadavku vytvorim dotaz
            $this->legal_operation = 1;

            if ($this->typ_pozadavku == "create") {
                $this->query_insert[0] = "INSERT INTO `objekt_kategorie_termin`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `datetime_od`, `datetime_do`,`nazev_tok`,`cena`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`, `kapacita_bez_omezeni`) VALUES ";                
                if ($this->probihajici_transakce == 0) { //pokud nejsem uprostred jine transakce, tak ji zahajim
                    $this->database->start_transaction();
                }
            } else if ($this->typ_pozadavku == "update") {

                $this->query_insert[0] = "INSERT INTO `objekt_kategorie_termin`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `datetime_od`, `datetime_do`,`nazev_tok`,`cena`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`, `kapacita_bez_omezeni`) VALUES ";                
                $this->query_update[0] = "UPDATE `objekt_kategorie_termin` SET ";
                $this->query_delete[0] = "DELETE FROM `objekt_kategorie_termin` WHERE ";
                $this->tok_update = array();
                //najdu vsechny ceny zajezdu ktere jiz jsou vytvoreny
                $data_tok = $this->database->query($this->create_query("get_tok"));
                while ($tok = mysqli_fetch_array($data_tok)) {
                    $this->tok_update_id[] = $tok["id_termin"] . "_" . $tok["id_objekt_kategorie"];
                    $this->tok_update[] = $tok;

                }
                $sql = "SELECT `serial`.`id_serial` FROM `serial` join `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                        where `objekt_serial`.`id_objektu`=" . $this->id_objektu . " and `id_ridici_objekt`=" . $this->id_objektu . "";
                //echo $sql;
                $this->id_serial_array = array();
                $data_serial = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row_serial = mysqli_fetch_array($data_serial)) {
                    $this->id_serial_array[] = $row_serial["id_serial"];
                }
                if ($this->probihajici_transakce == 0) { //pokud nejsem uprostred jine transakce, tak ji zahajim
                    $this->database->start_transaction();
                }

            } else if ($this->typ_pozadavku == "delete") {
                $delete_cena = $this->database->query($this->create_query("delete"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //vygenerování potvrzovací hlášky

                $sql = "SELECT `serial`.`id_serial` FROM `serial` join `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                        where `objekt_serial`.`id_objektu`=" . $this->id_objektu . " and `id_ridici_objekt`=" . $this->id_objektu . "";
                //echo $sql;
                $this->id_serial_array = array();
                $data_serial = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row_serial = mysqli_fetch_array($data_serial)) {
                    $this->id_serial_array[] = $row_serial["id_serial"];
                }
                if (sizeof((array)$this->id_serial_array) > 0) {
                    $sql_zajezdy = "select distinct `zajezd`.`id_zajezd` from
                                    `cena_zajezd_tok`
                                    join `zajezd` on (`cena_zajezd_tok`.`id_zajezd` = `zajezd`.`id_zajezd`  and `zajezd`.`id_serial` in (" . implode(",", $this->id_serial_array) . ")  )
                                    where `cena_zajezd_tok`.`id_termin` =  " . $this->id_terminu . "
                                    ";
                    $data_zajezdy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_zajezdy);
                    while ($row_zajezdy = mysqli_fetch_array($data_zajezdy)) {
                        $sql_update_zajezd = "DELETE FROM `zajezd`
                                        WHERE `id_zajezd`=" . $row_zajezdy["id_zajezd"] . "";
                        mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update_zajezd);
                    }
                }


                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
            } else if ($this->typ_pozadavku == "copy") {
                $this->typ_pozadavku = "create";
                $this->query_insert[0] = "INSERT INTO `objekt_kategorie_termin`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `datetime_od`, `datetime_do`,`nazev_tok`,`cena`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`, `kapacita_bez_omezeni`) VALUES ";

                $this->data = $this->database->query($this->create_query("show"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->pocet = mysqli_num_rows($this->data);
                if ($this->probihajici_transakce == 0) { //pokud nejsem uprostred jine transakce, tak ji zahajim
                    $this->database->start_transaction();
                }
                $id_termin = 0;
                $sql = "select max(`id_termin`) as `termin` from `objekt_kategorie_termin` where 1";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row_termin = mysqli_fetch_array($data)) {
                    $id_termin = intval($row_termin["termin"]);
                    $id_termin++;
                }
                while ($this->radek = mysqli_fetch_array($this->data)) {
                    //print_r($ceny);
                    if ($this->radek["id_termin"] > 0) {
                        $i++;
                        $this->date_od = $this->radek["datetime_od"];
                        $this->date_do = $this->radek["datetime_do"];
                        $this->nazev_tok = $this->radek["nazev_tok"];

                        $this->add_to_query($id_termin, $this->radek["id_objekt_kategorie"], $this->radek["cena"], $this->radek["kapacita_celkova"], $this->radek["kapacita_bez_omezeni"],
                            $this->radek["na_dotaz"], $this->radek["vyprodano"], 1);
                        //echo $_POST["castka_euro_".$i];
                    }
                }

                $this->finish_query();
                $this->id_terminu = $id_termin;


            } else if ($this->typ_pozadavku == "show") {
                $this->data = $this->database->query($this->create_query("show"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $this->pocet = mysqli_num_rows($this->data);
            } else if ($this->typ_pozadavku == "edit") {
                $this->data = $this->database->query($this->create_query("edit"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                $this->pocet = mysqli_num_rows($this->data);
            } else if ($this->typ_pozadavku == "new") {
                $this->data = $this->database->query($this->create_query("new"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                $this->pocet = mysqli_num_rows($this->data);
            }

        } else {
            $this->chyba($this->typ_pozadavku . $this->id_zamestnance . $this->id_objektu);
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	

    /**po jednotlivych radcich prijima data a vytvari z nich casti dotazu*/
    function add_to_query($id_terminu, $id_objektove_kategorie, $cena, $kapacita, $kapacita_bez_omezeni, $na_dotaz, $vyprodano, $pouzit)
    {
        //kontrola vstupnich dat

        $this->id_terminu = $this->check_int($id_terminu);
        $this->id_objektove_kategorie = $this->check_int($id_objektove_kategorie);

        $this->cena = $this->check_int($cena);

        $this->kapacita = $this->check_int($kapacita);
        $this->kapacita_bez_omezeni = $this->check_int($kapacita_bez_omezeni);
        $this->na_dotaz = $this->check_int($na_dotaz);
        $this->vyprodano = $this->check_int($vyprodano);

        $this->pouzit = $this->check_int($pouzit);

        //pokud jsou vporadku data, vytvorim danou cast dotazu
        if ($this->legal_data() and $this->legal_operation) {

            if ($this->typ_pozadavku == "create") {
                $this->pocet_zaznamu_insert++;

                if ($this->typ_objektu == 3) { //vstupenky
                    $this->query_insert[$this->pocet_zaznamu_insert] = "(" . $this->id_terminu . "," . $this->id_objektove_kategorie . "," . $this->id_objektu . ",\"" . $this->date_od . "\",\"" . $this->date_do . "\",
                                \"" . $this->nazev_tok . "\"," . $this->cena . "," . $this->kapacita . "," . $this->kapacita . "," . $this->na_dotaz . "," . $this->vyprodano . "," . $this->kapacita_bez_omezeni . ")";
                } else {
                    if ($this->nazev_tok == "") {
                        $this->nazev_tok = "NULL";
                    } else {
                        $this->nazev_tok = "\"" . $this->nazev_tok . "\"";
                    }
                    if ($this->cena == 0) {
                        $this->cena = "NULL";
                    }
                    $this->query_insert[$this->pocet_zaznamu_insert] = "(" . $this->id_terminu . "," . $this->id_objektove_kategorie . "," . $this->id_objektu . ",\"" . $this->date_od . "\",\"" . $this->date_do . "\",
                                " . $this->nazev_tok . "," . $this->cena . "," . $this->kapacita . "," . $this->kapacita . "," . $this->na_dotaz . "," . $this->vyprodano . "," . $this->kapacita_bez_omezeni . ")";
                }

            } else if ($this->typ_pozadavku == "update") {
                //nejdrive upravim vsechny zavisle zajezdy, potom vsechny jejich sluzby
                if ($this->update_zajezd != 1) {
                    $this->update_zajezd = 1;
                    $od = explode(" ", $this->date_od);
                    $do = explode(" ", $this->date_do);
                    if (sizeof((array)$this->id_serial_array) > 0) {
                        $sql_zajezdy = "select distinct `zajezd`.`id_zajezd` from
                                    `cena_zajezd_tok`
                                    join `zajezd` on (`cena_zajezd_tok`.`id_zajezd` = `zajezd`.`id_zajezd`  and `zajezd`.`id_serial` in (" . implode(",", $this->id_serial_array) . ")  )
                                    where `cena_zajezd_tok`.`id_termin` =  " . $this->id_terminu . "
                                    ";
                        //echo $sql_zajezdy."<br/>\n";
                        $data_zajezdy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_zajezdy);
                        while ($row_zajezdy = mysqli_fetch_array($data_zajezdy)) {
                            $sql_update_zajezd = "UPDATE `zajezd` SET `nazev_zajezdu`=\"" . $this->nazev_tok . "\",`od`=\"" . $od[0] . "\",`do`=\"" . $do[0] . "\",`last_change`=\"" . Date("Y-m-d") . "\"
                                        WHERE `id_zajezd`=" . $row_zajezdy["id_zajezd"] . "";
                            //echo $sql_update_zajezd."<br/>\n";
                            mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update_zajezd);
                        }
                    }
                }

                //hledame zda uz radek s touto cenou existuje, pokud ano -> update, jinak insert into
                $key = array_search($this->id_terminu . "_" . $this->id_objektove_kategorie, $this->tok_update_id);
                if ($key !== false) {
                    if ($this->pouzit) {
                        //vypocitam novou volnou kapacitu jako stara_volna + nova_celkova - stara_celkova
                        $this->kapacita_volna = $this->tok_update[$key]["kapacita_volna"] + $this->kapacita - $this->tok_update[$key]["kapacita_celkova"];
                        $this->pocet_zaznamu_update++;

                        if ($this->typ_objektu == 3) { //vstupenky
                            $this->query_update[$this->pocet_zaznamu_update] = "`datetime_od`=\"" . $this->date_od . "\", `datetime_do`=\"" . $this->date_do . "\",`nazev_tok`=\"" . $this->nazev_tok . "\",`cena`=" . $this->cena . ",
                                                                        `kapacita_celkova`=" . $this->kapacita . ",`kapacita_volna`=" . $this->kapacita_volna . " ,`na_dotaz`=" . $this->na_dotaz . ",`vyprodano`=" . $this->vyprodano . " ,`kapacita_bez_omezeni`=" . $this->kapacita_bez_omezeni . " WHERE `id_termin`=" . $this->id_terminu . " and `id_objekt_kategorie`=" . $this->id_objektove_kategorie . " LIMIT 1";
                        } else {
                            if ($this->nazev_tok == "") {
                                $this->nazev_tok = "NULL";
                            } else {
                                $this->nazev_tok = "\"" . $this->nazev_tok . "\"";
                            }
                            if ($this->cena == 0) {
                                $this->cena = "NULL";
                            }
                            $this->query_update[$this->pocet_zaznamu_update] = "`datetime_od`=\"" . $this->date_od . "\", `datetime_do`=\"" . $this->date_do . "\",`nazev_tok`=" . $this->nazev_tok . ",`cena`=" . $this->cena . ",
                                                                        `kapacita_celkova`=" . $this->kapacita . ",`kapacita_volna`=" . $this->kapacita_volna . " ,`na_dotaz`=" . $this->na_dotaz . ",`vyprodano`=" . $this->vyprodano . " ,`kapacita_bez_omezeni`=" . $this->kapacita_bez_omezeni . " WHERE `id_termin`=" . $this->id_terminu . " and `id_objekt_kategorie`=" . $this->id_objektove_kategorie . " LIMIT 1";
                        }
                        //toto se týká závislých seriálù, je tøeba ještì vytvoøit mechanismus pro update pouze závislých cen
                        if (sizeof((array)$this->id_serial_array) > 0) {
                            $sql_update_cena = "select `cena_zajezd`.`id_cena`, `cena_zajezd`.`id_zajezd`, `je_vstupenka`
                                                    from `cena_zajezd`
                                                    join `cena_zajezd_tok` on (`cena_zajezd`.`id_cena` = `cena_zajezd_tok`.`id_cena` 
                                                                                and `cena_zajezd`.`id_zajezd` = `cena_zajezd_tok`.`id_zajezd`)
                                                    join `zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd` 
                                                                                and `zajezd`.`id_serial` in (" . implode(",", $this->id_serial_array) . ") )
                                                    where `cena_zajezd_tok`.`id_termin`=" . $this->id_terminu . " and `cena_zajezd_tok`.`id_objekt_kategorie`=" . $this->id_objektove_kategorie;
                            //echo $sql_update_cena."<br/>\n";
                            $data_update_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update_cena);
                            while ($row_update_cena = mysqli_fetch_array($data_update_cena)) {
                                //if ($row_update_cena["je_vstupenka"]) {
                                    $update_cena = "UPDATE `cena_zajezd` SET `castka`=" . $this->cena . ",`mena`=\"Kè\",`kapacita_celkova`=" . $this->kapacita . ",`kapacita_volna`=" . $this->kapacita_volna . "
                                                                        WHERE `id_cena`=" . $row_update_cena["id_cena"] . " and `id_zajezd`=" . $row_update_cena["id_zajezd"] . "";
                                /*} else {
                                    $update_cena = "UPDATE `cena_zajezd` SET `kapacita_celkova`=" . $this->kapacita . ",`kapacita_volna`=" . $this->kapacita_volna . "
                                                                        WHERE `id_cena`=" . $row_update_cena["id_cena"] . " and `id_zajezd`=" . $row_update_cena["id_zajezd"] . "";
                                }*/
                                //echo $update_cena."<br/>\n";
                                mysqli_query($GLOBALS["core"]->database->db_spojeni,$update_cena);
                            }
                        }
                        $query_cena_sluzby = "select cena_zajezd.id_cena, cena_zajezd.id_zajezd from 
                                                cena_zajezd join
                                                cena_zajezd_tok on (cena_zajezd.id_cena = cena_zajezd_tok.id_cena 
                                                                        and cena_zajezd.id_zajezd = cena_zajezd_tok.id_zajezd ) join
                                                cena_objekt_kategorie on (cena_zajezd.id_cena = cena_objekt_kategorie.id_cena 
                                                                        and cena_objekt_kategorie.id_objekt_kategorie = cena_zajezd_tok.id_objekt_kategorie 
                                                                        and cena_objekt_kategorie.use_cena_tok = 1)
                                              where cena_zajezd_tok.id_termin=$this->id_terminu and cena_zajezd_tok.id_objekt_kategorie=$this->id_objektove_kategorie ";
                        $data_cena_sluzby = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_cena_sluzby);
                        while ($row_cena_sluzby = mysqli_fetch_array($data_cena_sluzby)) {
                            $update_cena = "UPDATE `cena_zajezd` SET `castka`=" . $this->cena . ",`mena`=\"Kè\",`kapacita_celkova`=" . $this->kapacita . ",`kapacita_volna`=" . $this->kapacita_volna . "
                                                                        WHERE `id_cena`=" . $row_cena_sluzby["id_cena"] . " and `id_zajezd`=" . $row_cena_sluzby["id_zajezd"] . "";
                             mysqli_query($GLOBALS["core"]->database->db_spojeni,$update_cena);
                        }
                    } else {
                        //cenu nechci pouzivat, musim ji smazat
                        $this->pocet_zaznamu_delete++;
                        $this->query_delete[$this->pocet_zaznamu_delete] = " `id_termin`=" . $this->id_terminu . " and `id_objekt_kategorie`=" . $this->id_objektove_kategorie . " LIMIT 1";
                        if (sizeof((array)$this->id_serial_array) > 0) {
                            $sql_update_cena = "select `cena_zajezd`.`id_cena`, `cena_zajezd`.`id_zajezd`, `je_vstupenka`
                                                    from `cena_zajezd`
                                                    join `cena_zajezd_tok` on (`cena_zajezd`.`id_cena` = `cena_zajezd_tok`.`id_cena` and `cena_zajezd`.`id_zajezd` = `cena_zajezd_tok`.`id_zajezd`)
                                                    join `zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd` and `zajezd`.`id_serial` in (" . implode(",", $this->id_serial_array) . ") )
                                                        where `cena_zajezd_tok`.`id_termin`=" . $this->id_terminu . " and `cena_zajezd_tok`.`id_objekt_kategorie`=" . $this->id_objektove_kategorie . "";
                            //echo $sql_update_cena;
                            $data_update_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update_cena);
                            while ($row_update_cena = mysqli_fetch_array($data_update_cena)) {
                                $delete_cena_zajezd_tok = "delete from `cena_zajezd_tok` where `cena_zajezd_tok`.`id_termin`=" . $this->id_terminu . " and `cena_zajezd_tok`.`id_objekt_kategorie`=" . $this->id_objektove_kategorie . "";
                                //echo $delete_cena_zajezd_tok."<br/>";
                                mysqli_query($GLOBALS["core"]->database->db_spojeni,$delete_cena_zajezd_tok);
                                $delete_cena = "Delete from `cena_zajezd`WHERE `id_cena`=" . $row_update_cena["id_cena"] . " and `id_zajezd`=" . $row_update_cena["id_zajezd"] . "";
                                //echo $delete_cena."<br/>";;
                                mysqli_query($GLOBALS["core"]->database->db_spojeni,$delete_cena);
                            }
                            //problem s cizima klicema, maze se vic veci, je treba definovat rucne

                        }
                    }
                } else {
                    if ($this->pouzit) {
                        $this->pocet_zaznamu_insert++;
                        if ($this->typ_objektu == 3) { //vstupenky
                            $this->query_insert[$this->pocet_zaznamu_insert] = "(" . $this->id_terminu . "," . $this->id_objektove_kategorie . "," . $this->id_objektu . ",\"" . $this->date_od . "\",\"" . $this->date_do . "\",
                                                                \"" . $this->nazev_tok . "\"," . $this->cena . "," . $this->kapacita . "," . $this->kapacita . "," . $this->na_dotaz . "," . $this->vyprodano . "," . $this->kapacita_bez_omezeni . ")";
                        } else {
                            if ($this->nazev_tok == "") {
                                $this->nazev_tok = "NULL";
                            } else {
                                $this->nazev_tok = "\"" . $this->nazev_tok . "\"";
                            }
                            if ($this->cena == 0) {
                                $this->cena = "NULL";
                            }
                            $this->query_insert[$this->pocet_zaznamu_insert] = "(" . $this->id_terminu . "," . $this->id_objektove_kategorie . "," . $this->id_objektu . ",\"" . $this->date_od . "\",\"" . $this->date_do . "\",
                                                        " . $this->nazev_tok . "," . $this->cena . "," . $this->kapacita . "," . $this->kapacita . "," . $this->na_dotaz . "," . $this->vyprodano . "," . $this->kapacita_bez_omezeni . ")";
                        }
                        if (sizeof((array)$this->id_serial_array) > 0) {
                            $sql_update_cena = "select distinct `cena`.`id_cena`, `zajezd`.`id_zajezd`
                                                    from `cena`
                                                    join `cena_objekt_kategorie` on (`cena`.`id_cena` = `cena_objekt_kategorie`.`id_cena` and `cena_objekt_kategorie`.`id_objekt_kategorie`=" . $this->id_objektove_kategorie . ")
                                                    join `serial` on (`cena`.`id_serial` = `serial`.`id_serial` and `serial`.`id_serial` in (" . implode(",", $this->id_serial_array) . ") )
                                                    join `zajezd` on (`serial`.`id_serial` = `zajezd`.`id_serial`)
                                                    join `cena_zajezd_tok` on (`zajezd`.`id_zajezd` = `cena_zajezd_tok`.`id_zajezd` and `cena_zajezd_tok`.`id_termin`=" . $this->id_terminu . ")
                                                    where 1
                                                        ";

                            $data_update_cena = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update_cena);
                            while ($row_update_cena = mysqli_fetch_array($data_update_cena)) {
                                $sql_insert_cena = "INSERT INTO `cena_zajezd`(`id_cena`, `id_zajezd`, `castka`, `mena`, `castka_euro`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`)
                                                                VALUES (" . $row_update_cena["id_cena"] . "," . $row_update_cena["id_zajezd"] . "," . $this->cena . ",\"Kè\",0," . $this->kapacita . "," . $this->kapacita . ",0,0)";
                                mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_insert_cena);
                                $this->sql_insert_map[] = "INSERT INTO `cena_zajezd_tok`(`id_cena`, `id_zajezd`, `id_termin`, `id_objekt_kategorie`, `je_vstupenka`)
                                                                        VALUES (" . $row_update_cena["id_cena"] . "," . $row_update_cena["id_zajezd"] . "," . $this->id_terminu . "," . $this->id_objektove_kategorie . ",1)";

                            }
                        }
                    }
                }
            }
            //echo $this->pocet_zaznamu;

        }
        //if legal_data
    }

    /**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
    function correct_data($typ_pozadavku)
    {
        $ok = 1;
        //kontrolovane pole id_cena, id_zajezd
        if (!Validace::int_min($this->id_objektu, 1)) {
            $ok = 0;
            $this->chyba("Objekt není identifikován");
        }
        /*if($typ_pozadavku == "new" or $typ_pozadavku == "create" or $typ_pozadavku == "update"){
            if(!Validace::int_min_max($this->pocet,1,MAX_CEN) ){
                $ok = 0;
                $this->chyba("Poèet Objekt kategorií není v povoleném intervalu 1 - ".MAX_CEN."");
            }
        }	*/
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
    function legal_data()
    {
        $ok = 1;
        $this->cislo_ceny++;
        //kontrolovane pole nazev_cena
        /*	if(!Validace::text($nazev_ceny) ){
                $ok = 0;
                $this->chyba("Název ceny è.".$this->cislo_ceny." není specifikován, cena nebude vytvoøena/zmìnìna");
            }*/
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    function finish_query()
    {
        if ($this->legal_operation) {


            if ($this->pocet_zaznamu_insert) {
                //vytvorim zacatek dotazu - prvni hodnoty by zde mely byt vzdy
                $dotaz = $this->query_insert[0] . $this->query_insert[1];
                //$i = 2 protoze prvni zaznam je uz ulozeny jako inicializace
                //(vzdy musi byt alespon jeden, jinak by neprosla podminka na pocet_zaznamu_insert )
                $i = 2;
                while ($i <= $this->pocet_zaznamu_insert) {
                    //skladam jednotlive casti dotazu - vznikne jeden insert s vice vkladanymi radky
                    $dotaz = $dotaz . " , " . $this->query_insert[$i];
                    $i++;
                }
                //echo $dotaz;
                // echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
                //odeslu dotaz

                $create_ceny = $this->database->transaction_query($dotaz)
                or $this->chyba("Chyba pøi dotazu do databáze" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                echo $dotaz;
            }
            if ($this->pocet_zaznamu_update) {
                $i = 1;
                while ($i <= $this->pocet_zaznamu_update) {
                    $dotaz = $this->query_update[0] . $this->query_update[$i];
                    //echo $dotaz;
                    //skladam jednotlive dotazy a rovnou je odesilam
                    $update_ceny = $this->database->transaction_query($dotaz)
                    or $this->chyba("Chyba pøi dotazu do databáze" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $i++;
                    // echo $dotaz;
                    // echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
                }
            }
            if ($this->pocet_zaznamu_delete) {
                $i = 1;
                while ($i <= $this->pocet_zaznamu_delete) {
                    $dotaz = $this->query_delete[0] . $this->query_delete[$i];
                    //echo $dotaz;
                    //skladam jednotlive dotazy a rovnou je odesilam
                    //echo $dotaz."<br/>";
                    $update_ceny = $this->database->transaction_query($dotaz)
                    or $this->chyba("Chyba pøi dotazu do databáze" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $i++;
                    // echo $dotaz;
                    //  echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
                }
            }
            foreach ($this->sql_insert_map as $key => $sql) {
                mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            }
            //vygenerování potvrzovací hlášky
            if (!$this->get_error_message()) {
                if ($this->probihajici_transakce == 0) { //pokud nejsem uprostred jine transakce, tak ji potvrdim
                    $this->database->commit();
                }
                $this->confirm("Požadovaná akce probìhla úspìšnì");
            }
        }
    }

    /**vytvoreni dotazu ze zadanych parametru*/
    function create_query($typ_pozadavku)
    {
        if ($typ_pozadavku == "edit" or $typ_pozadavku == "show") {
            $dotaz = "select `objekt_kategorie`.*,`objekt_kategorie_termin`.`id_termin`,`objekt_kategorie_termin`.`kapacita_celkova`, `objekt_kategorie_termin`.`kapacita_volna`, `objekt_kategorie_termin`.`na_dotaz`, `objekt_kategorie_termin`.`vyprodano`,
                                        `objekt_kategorie_termin`.`kapacita_bez_omezeni`, `objekt_kategorie_termin`.`datetime_od`, `objekt_kategorie_termin`.`datetime_do` , `objekt_kategorie_termin`.`nazev_tok`, `objekt_kategorie_termin`.`cena`
					from `objekt_kategorie` left join `objekt_kategorie_termin` on (`objekt_kategorie_termin`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` and `id_termin`= " . $this->id_terminu . ")
					where `objekt_kategorie`.`id_objektu`= " . $this->id_objektu . "
					order by `objekt_kategorie`.`id_objekt_kategorie` ";
            //echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "new") {
            $dotaz = "select `objekt_kategorie`.*
					from `objekt_kategorie` 
					where `objekt_kategorie`.`id_objektu`= " . $this->id_objektu . "
					order by `objekt_kategorie`.`id_objekt_kategorie` ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "delete
					  from `objekt_kategorie_termin`
					where `id_termin`= " . $this->id_terminu . " and `id_objektu`= " . $this->id_objektu . "  ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_tok") {
            $dotaz = "select *
					  from `objekt_kategorie_termin`
					where `id_termin`= " . $this->id_terminu . " ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "objekt_info") {
            $dotaz = "select *  from `objekt`
					where `id_objektu`= " . $this->id_objektu . " limit 1 ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `objekt`
						WHERE `id_objektu`=" . $this->id_objektu . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        }
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
            return $zamestnanec->get_bool_prava($id_modul, "read");

        } else if ($typ_pozadavku == "edit") {
            return $zamestnanec->get_bool_prava($id_modul, "read");

        } else if ($typ_pozadavku == "show") {
            return $zamestnanec->get_bool_prava($id_modul, "read");

        } else if ($typ_pozadavku == "create" or $typ_pozadavku == "copy") {
            //tvorba casti serialu := editace serialu
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
            //delete casti serialu := editace serialu
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create())
            ) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    function show_tok_name()
    {
        $query = "select distinct `datetime_od`, `datetime_do`, `nazev_tok` from `objekt_kategorie_termin`  where `id_termin` = " . $this->id_terminu . " limit 1";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
        while ($row = mysqli_fetch_array($data)) {
            $this->date_od = $row["datetime_od"];
            $this->date_do = $row["datetime_do"];
            $this->nazev_tok = $row["nazev_tok"];
        }
        $nazev = $this->nazev_tok != "" ? "<i><strong>$this->nazev_tok</strong></i>," : "";
        $vystup = "$nazev Termín: " . $this->change_date_en_cz($this->date_od) . " - " . $this->change_date_en_cz($this->date_do) . "<br/>";
        return $vystup;
    }

    /**zobrazi hlavicku k seznamu*/
    function show_list_header()
    {
        if ($this->typ_objektu == 3) { //vstupenky
            $cena_header = "<th>Cena</th>";
        }
        $vystup = "";
        $vystup .= "
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Název</th>
                                                " . $cena_header . "
						<th>Celková kapacita</th>
						<th>Volná kapacita</th>
						<th>Neomezená kapacita</th>
						<th>Jen na dotaz</th>
						<th>Vyprodáno</th>
						<th>Možnosti editace</th>
					</tr>
		";
        return $vystup;
    }

    /**zobrazime seznam  cen pro dany serial*/
    function show_list_item($typ_zobrazeni)
    {
        if ($typ_zobrazeni == "tabulka") {
            if ($this->suda == 1) {
                $vypis = $vypis . "<tr class=\"suda\">";
            } else {
                $vypis = $vypis . "<tr class=\"licha\">";
            }

            if ($this->typ_objektu == 3) { //vstupenky
                $cena_row = "<td><b>" . $this->get_cena() . " Kè</b>";
            } else {
                $cena_row = "";
            }
            //tvorba zkraceny_vypis
            if ($this->get_kapacita_bez_omezeni()) {
                $kapacita_bez_omezeni = "<span class=\"green\">ANO</span>";
            } else {
                $kapacita_bez_omezeni = "<span class=\"red\">NE</span>";
            }
            //tvorba kapacita na dotaz
            if ($this->get_na_dotaz()) {
                $na_dotaz = "<span class=\"red\">NA DOTAZ</span>";
            } else {
                $na_dotaz = "<span class=\"green\">NE</span>";
            }
            //tvorba kapacita vyprodano
            if ($this->get_vyprodano()) {
                $vyprodano = "<span class=\"red\">VYPRODÁNO</span>";
            } else {
                $vyprodano = "<span class=\"green\">NE</span>";
            }
            if ($this->get_kapacita() or $this->get_kapacita_bez_omezeni() or $this->get_na_dotaz() or $this->get_vyprodano() or $this->get_cena() ) {
                $kapacita = "" . $this->get_kapacita() . "";
                $kapacita_volna = "" . $this->get_kapacita_volna() . "";
                $edit_menu = "<a href=\"?id_objektu=" . $this->id_objektu . "&amp;typ=tok&amp;id_termin=" . $this->get_id_termin() . "&amp;pozadavek=edit\">editovat TOK</a>
								 | <a class='anchor-delete' href=\"?typ=tok&amp;pozadavek=delete&amp;id_objektu=" . $this->id_objektu . "&amp;id_termin=" . $this->get_id_termin() . "&amp;id_objekt_kategorie=" . $this->get_id_objekt_kategorie() . "\">delete</a>";
            } else {
                $kapacita = "<span class=\"red\">NEPOUŽÍVANÁ</span>";
                $kapacita_volna = "<span class=\"red\">NEPOUŽÍVANÁ</span>";
                $edit_menu = "";
                if ($this->typ_objektu == 3) { //vstupenky
                    $cena_row = "<td></td>";
                }
            }
            $vypis = $vypis . "
							<td class=\"nazev\">
								" . $this->get_id_objekt_kategorie() . "
							</td>
							<td class=\"nazev\">
								" . $this->get_nazev_ok() . "<br/>
                                                                " . $this->get_nazev_ok_en() . "
							</td>
                                                        " . $cena_row . "
                                                        <td class=\"kapacita_celkova\">
								" . $kapacita . "
							</td>
							<td class=\"kapacita_volna\">
								" . $kapacita_volna . "
							</td>
							<td class=\"kapacita_bez_omezeni\">
								" . $kapacita_bez_omezeni . "
							</td>		
							<td class=\"na_dotaz\">
								" . $na_dotaz . "
							</td>			
							<td class=\"vyprodano\">
								" . $vyprodano . "
							</td>		
							<td class=\"menu\">
								" . $edit_menu . "
							</td>
						</tr>";

            return $vypis;
        }
        //typ zobrazeni
    }

    /**zobrazime formular*/
    function show_form()
    {

        //podle typu pozadavku vypisu spravny cil scriptu
        if ($this->typ_pozadavku == "edit") {
            $action = "?id_objektu=" . $this->id_objektu . "&amp;id_termin=" . $this->id_terminu . "&amp;typ=tok&amp;pozadavek=update";
            //tlacitka pro odesilani
            $id_termin = "<tr class=\"suda\"><th>ID termínu<td>" . $this->id_terminu . "";
            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit TOK\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci TOK</strong>\n";
            }
        } else if ($this->typ_pozadavku == "new") {
            $action = "?id_objektu=" . $this->id_objektu . "&amp;typ=tok&amp;pozadavek=create";
            $id_termin = "";
            //tlacitka pro odesilani
            if ($this->legal("create")) {
                $submit = "<input type=\"submit\" value=\"Vytvoøit TOK\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení TOK</strong>\n";
            }
        } else {
            $action = "";
        }
        $query = "select distinct `datetime_od`, `datetime_do`, `nazev_tok` from `objekt_kategorie_termin`  where `id_termin` = " . $this->id_terminu . " limit 1";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
        while ($row = mysqli_fetch_array($data)) {
            $this->date_od = $row["datetime_od"];
            $this->date_do = $row["datetime_do"];
            $this->nazev_tok = $row["nazev_tok"];
        }
        //  if($this->typ_objektu ==3){
        $nazev = "<tr class=\"suda\"><th>Název TOK<td><input type=\"text\" name=\"nazev_tok\" size=\"40\" value=\"" . $this->nazev_tok . "\" />";
        if ($this->typ_objektu != 3) {
            $nazev .= "<tr class=\"suda\"><th>Vytvoøit ceny TOK<td><input type=\"checkbox\" name=\"checkbox_cena\" value=\"1\" onchange=\"enable_ceny();\"/>
                            <input type=\"hidden\" name=\"checkbox_cena_hidden\" value=\"0\" />";
        } else {
            $nazev .= "<input type=\"hidden\" name=\"checkbox_cena\" value=\"1\" checked=\"checked\"  />
                                  <input type=\"hidden\" name=\"checkbox_cena_hidden\" value=\"1\" />  ";
        }
        $cena_header = "<th>Cena</th>";
        //   }
        //hlavicka formulare
        $i = 1;
        $vypis = "
                        <script type=\"text/javascript\">
                            function enable_ceny(){
                                var checkbox = document.getElementsByName(\"checkbox_cena\")[0].checked;
                                var hidden = document.getElementsByName(\"checkbox_cena_hidden\")[0].value;
                                for(var j = 0; j < 50; j++) {
                                    var cena = document.getElementsByName(\"cena_\"+j)[0];
                                    if(cena!=undefined){
                                        if(checkbox == true || hidden == \"1\"){
                                            cena.disabled = false;
                                        }else{
                                            cena.disabled = true;
                                        }
                                    }
                                }           
                            }
                        </script>
                        <form action=\"" . $action . "\" method=\"post\" >
					<input name=\"pocet\" type=\"hidden\" value=\"" . $this->pocet . "\" />
                                        <table class=\"list\">
                                            " . $id_termin . "
                                            
                                            <tr class=\"suda\"><th>Datum od<td><input type=\"text\" name=\"datum_od\" value=\"" . $this->change_date_en_cz($this->date_od) . "\" />
                                            <tr class=\"suda\"><th>Datum do<td><input type=\"text\" name=\"datum_do\" value=\"" . $this->change_date_en_cz($this->date_do) . "\" />
                                                " . $nazev . "
                                        </table>
                                        
					<table  class=\"list\">
						<tr>
							<th>ID OK</th>
							<th>Název OK </th>
                                                        
							<th>Celková kapacita</th>
                                                        <th>Aktuální volná kapacita</th>
                                                        " . $cena_header . "
                                                        <th>Neomezená kapacita</th>
                                                        <th>Jen na dotaz</th>
                                                        <th>Vyprodáno</th>
                                                        <th>Použít OK</th>
						</tr>
						";
        $i = 0;
        while ($this->get_next_radek()) {
            $i++;
            /*posunu se na dalsi radek nactenych dat z databaze (pokud nejake mam)
                - to ze se neridim primo podle get_next_radek() nevadi - pocet_zaznamu jsem u editace ziskal jako mysqli_num_rows:))*/

            if ($this->suda == 1) {
                $cl_suda = " class=\"suda\"";
                $vypis = $vypis . "<tr class=\"suda\">";
            } else {
                $cl_suda = " class=\"licha\"";
                $vypis = $vypis . "<tr class=\"licha\">";
            }
            //tvorba jednotlivych poli formulare
            //tvorba select typ ceny

            //tvorba checkbox ucast ve vypisu
            if ($this->get_vyprodano()) {
                $checkbox_vyprodano = "<input type=\"checkbox\" name=\"vyprodano_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $checkbox_vyprodano = "<input type=\"checkbox\" name=\"vyprodano_" . $i . "\" value=\"1\" />";
            }

            if ($this->get_na_dotaz()) {
                $checkbox_na_dotaz = "<input type=\"checkbox\" name=\"na_dotaz_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $checkbox_na_dotaz = "<input type=\"checkbox\" name=\"na_dotaz_" . $i . "\" value=\"1\" />";
            }

            if ($this->get_kapacita_bez_omezeni()) {
                $checkbox_kapacita_bez_omezeni = "<input type=\"checkbox\" name=\"kapacita_bez_omezeni_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $checkbox_kapacita_bez_omezeni = "<input type=\"checkbox\" name=\"kapacita_bez_omezeni_" . $i . "\" value=\"1\" />";
            }
            // if($this->typ_objektu ==3){
            $cena = "<td><input type=\"text\" size=\"10\" name=\"cena_" . $i . "\" value=\"" . $this->get_cena() . "\"  /> Kè</td>";
            /*  }else{
                  $cena = "";
              }*/
            if ($this->get_pouzit() or $this->typ_pozadavku == "new") {
                $pouzit = "<input type=\"checkbox\" name=\"pouzit_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $pouzit = "<input type=\"checkbox\" name=\"pouzit_" . $i . "\" value=\"1\" />";
            }


            $vypis = $vypis . "
							<td >
                                                                <input type=\"hidden\" name=\"id_objekt_kategorie_" . $i . "\" value=\"" . $this->get_id_objekt_kategorie() . "\" checked=\"checked\" />
								" . $this->get_id_objekt_kategorie() . "
							</td>
							<td class=\"nazev\">
								" . $this->get_nazev_ok() . "
							</td>	
                                                        
							<td class=\"kapacita\">
								<input name=\"kapacita_" . $i . "\" type=\"text\" size=\"10\" value=\"" . $this->get_kapacita() . "\" />
							</td>	
                                                        <td class=\"kapacita\">
								" . $this->get_kapacita_volna() . "
							</td>
                                                        " . $cena . "
							<td class=\"kapacita_bez_omezeni\">
								" . $checkbox_kapacita_bez_omezeni . "
							</td>
                                                        <td class=\"kapacita_bez_omezeni\">
								" . $checkbox_na_dotaz . "
							</td>
							<td class=\"kapacita_bez_omezeni\">
								" . $checkbox_vyprodano . "
							</td>
                                                        <td class=\"pouzit\">
								" . $pouzit . "
							</td>
						</tr>
                                        ";

            $i++;
        }
        $disclaimer = "<script type=\"text/javascript\">
                            enable_ceny();
                        </script>";
        $vypis = $vypis . "</table>" . $submit . "</form>" . $disclaimer;
        return $vypis;

    }

    /*metody pro pristup k parametrum*/
    function get_id_objektu()
    {
        return $this->radek["id_objektu"];
    }

    function get_id_termin()
    {
        return $this->radek["id_termin"];
    }

    function get_inserted_id_termin()
    {
        return $this->id_terminu;
    }

    function get_id_objekt_kategorie()
    {
        return $this->radek["id_objekt_kategorie"];
    }

    function get_nazev_ok()
    {
        return $this->radek["nazev"];
    }

    function get_nazev_ok_en()
    {
        return $this->radek["cizi_nazev"];
    }

    function get_na_dotaz()
    {
        return $this->radek["na_dotaz"];
    }

    function get_vyprodano()
    {
        return $this->radek["vyprodano"];
    }

    function get_kapacita_bez_omezeni()
    {
        return $this->radek["kapacita_bez_omezeni"];
    }

    function get_kapacita()
    {
        return $this->radek["kapacita_celkova"];
    }

    function get_kapacita_volna()
    {
        return $this->radek["kapacita_volna"];
    }

    function get_cena()
    {
        return $this->radek["cena"];
    }

    function get_nazev_tok()
    {
        return $this->radek["nazev_tok"];
    }

    function get_pouzit()
    {
        if ($this->radek["id_termin"] == "") {
            return false;
        } else {
            return true;
        }
    }

    function get_id_user_create()
    {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_objektu == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }
}

?>
