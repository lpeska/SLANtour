<?php
/**
 * zajezd_cena.inc.php - trida pro zobrazeni seznamu cen zajezdu v administracni casti
 *                                            - a jejich create, update, delete
 */

/*------------------- SEZNAM cen zajezdu -------------------  */
class Cena_zajezd extends Generic_list
{
    //vstupni data
    protected $typ_pozadavku;
    protected $probihajici_transakce; //pokud je objekt soucasti probihajici transakce,nebude zahajena ani ukoncena

    protected $id_serial;
    protected $id_cena;
    protected $id_zajezd;
    protected $id_termin;
    protected $pocet;
    protected $castka;
    protected $mena;
    protected $kapacita_volna;
    protected $kapacita_celkova;
    protected $vyprodano;
    protected $na_dotaz;
    protected $pouzit;
    protected $termin_od;
    protected $termin_do;
    //znaci ze smim pokracovat s add_to_query
    protected $legal_operation;

    //prubezne konstruovany dotaz do databaze
    protected $query_insert;
    protected $query_insert_tok;
    protected $query_insert_tok_cena;
    protected $query_update;
    protected $query_delete;
    //seznam cen ktere uz existuji (a maji se update misto insert into
    protected $ceny_update_id; //seznam id
    protected $ceny_update; //seznam cen
    //pocty zaznamu v query
    protected $pocet_zaznamu;
    protected $pocet_zaznamu_insert;
    protected $pocet_zaznamu_update;
    protected $pocet_zaznamu_delete;
    
        protected $id_ridici_objekt;
        protected $nazev_objektu;    

    public $database; //trida pro odesilani dotazu

    //------------------- KONSTRUKTOR -----------------
    /**konstruktor tøídy*/
    function __construct($typ_pozadavku, $id_serial, $id_zajezd, $id_cena = "", $pocet = "", $probihajici_transakce = 0)
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //inicializace
        $this->legal_operation = 0;

        $this->pocet_zaznamu = 0;
        $this->pocet_zaznamu_insert = 0;
        $this->pocet_zaznamu_update = 0;
        $this->pocet_zaznamu_delete = 0;

        $this->query_insert_tok = "";
        $this->query_insert_tok_cena = "";
        $this->query_insert = array();
        $this->query_update = array();
        $this->query_delete = array();
        //kontrola dat
        $this->id_termin = 0;
        $this->probihajici_transakce = $probihajici_transakce;
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_serial = $this->check_int($id_serial);
        $this->id_zajezd = $this->check_int($id_zajezd);
        $this->id_cena = $this->check_int($id_cena);
        $this->pocet = $this->check_int($pocet);

        $this->id_ridici_objekt = "";
        $this->nazev_objektu = "";
        $sql = "select `id_ridici_objekt`, `nazev_objektu`  from `serial` join `objekt` on (`serial`.`id_ridici_objekt` = `objekt`.`id_objektu`) where `id_serial`=" . $this->id_serial . " ";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($row = mysqli_fetch_array($data)) {
            $this->nazev_objektu = $row["nazev_objektu"];
            $this->id_ridici_objekt = $row["id_ridici_objekt"];
        }
        
        $sql = "select `cena`.`id_cena`, `id_objekt_kategorie`  
            from `cena` join `cena_objekt_kategorie` on (`cena`.`id_cena` = `cena_objekt_kategorie`.`id_cena` and `cena_objekt_kategorie`.`use_cena_tok`=1) 
            where `cena`.`id_serial`=" . $this->id_serial . " ";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($row = mysqli_fetch_array($data)) {
            $this->use_cena_tok[$row["id_cena"]] = $row["id_objekt_kategorie"];
        }
                
        if ($this->id_zajezd > 0) {
            $data_zajezd = $this->database->query($this->create_query("get_zajezd"));
            while ($row = mysqli_fetch_array($data_zajezd)) {
                $this->termin_od = $row["od"];
                $this->termin_do = $row["do"];
            }
        }

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            $this->legal_operation = 1;

            if ($this->typ_pozadavku == "create") {
                //zacatek dotazu
                $this->query_insert[0] = "INSERT INTO `cena_zajezd` (`id_cena`,`id_zajezd`,`castka`,`mena`,`castka_euro`,`kapacita_celkova`,`kapacita_volna`,`na_dotaz`,`vyprodano`,`nezobrazovat`) VALUES ";

            } else if ($this->typ_pozadavku == "update") {
                //zacatek dotazu
                $this->query_insert[0] = "INSERT INTO `cena_zajezd` (`id_cena`,`id_zajezd`,`castka`,`mena`,`castka_euro`,`kapacita_celkova`,`kapacita_volna`,`na_dotaz`,`vyprodano`,`nezobrazovat`) VALUES ";
                $this->query_update[0] = "UPDATE `cena_zajezd` SET ";
                $this->query_delete[0] = "DELETE FROM `cena_zajezd` WHERE ";
                $this->ceny_update = array();
                //najdu vsechny ceny zajezdu ktere jiz jsou vytvoreny
                $data_ceny = $this->database->query($this->create_query("get_ceny"));
                while ($ceny = mysqli_fetch_array($data_ceny)) {

                    $this->ceny_update_id[] = $ceny["id_cena"];
                    $this->ceny_update[] = $ceny;

                }

            } else if ($this->typ_pozadavku == "delete") {
                $delete_cena = $this->database->query($this->create_query("delete"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //vygenerování potvrzovací hlášky
                if (!$this->get_error_message()) {
                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                }
            } else if ($this->typ_pozadavku == "show") {
                $this->data = $this->database->query($this->create_query("show"))
                or $this->chyba("Chyba pøi dotazu do databáze: cena_zajezd_show" . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            } else if ($this->typ_pozadavku == "edit" or $this->typ_pozadavku == "new") {
                $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                or $this->chyba("Chyba pøi dotazu do databáze: cena_zajezd_new" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }

        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------
    /* u DB tabulky cenova mapa pro prislusnou cenu zrusi oznaceni ze se na jejim zaklade maji generovat zajezdy*/	
    function remove_assignments_cenova_mapa($id_cena){
        $query = "update cena_promenna_cenova_mapa set no_dates_generation = 1 where id_cena_promenna in (SELECT id_cena_promenna FROM `cena_promenna` WHERE `id_cena`=".$id_cena.")";
        $ok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
        //echo  $ok_result;
        //echo  $query;
        
    }
    
    
    /**po jednotlivych radcich prijima data a vytvari z nich casti dotazu*/
    function add_to_query($id_cena, $castka, $mena, $castka_euro, $kapacita_volna, $kapacita_celkova, $vyprodano, $na_dotaz, $pouzit = 1,$nezobrazovat=0,$update_dle_kv=false)
    {
        //kontrola vstupnich dat
        $this->pocet_zaznamu++;
        $this->id_cena = $this->check_int($id_cena);
        $this->castka = $this->check_int($castka);
        $this->castka_euro = $this->check_int($castka_euro);
        $this->mena = $this->check($mena);
        $this->kapacita_volna = $this->check_int($kapacita_volna);
        $this->kapacita_celkova = $this->check_int($kapacita_celkova);
        $this->vyprodano = $this->check_int($vyprodano);
        $this->na_dotaz = $this->check_int($na_dotaz);
        $this->nezobrazovat = $this->check_int($nezobrazovat);
        
        $this->pouzit =  $this->check_int($pouzit);

        $_POST["je_vstupenka_" . $this->pocet_zaznamu] = intval($_POST["je_vstupenka_" . $this->pocet_zaznamu]);

        //pokud jsou vporadku data, vytvorim danou cast dotazu
        if ($this->legal_data($this->id_cena, $this->id_zajezd, $this->castka, $this->mena, $this->kapacita_volna, $this->kapacita_celkova, $this->vyprodano, $this->na_dotaz)
            and $this->legal_operation
        ) {
            if ($this->typ_pozadavku == "create") {
                if ($this->pouzit) {
                    //pridam cenu pouze pokud ji chci pridat
                    $this->pocet_zaznamu_insert++;
                    $this->query_insert[$this->pocet_zaznamu_insert] = "(" . $this->id_cena . "," . $this->id_zajezd . "," . $this->castka . ",\"Kè\"," . $this->castka_euro . ",
							" . $this->kapacita_celkova . "," . $this->kapacita_celkova . "," . $this->na_dotaz . "," . $this->vyprodano . ",".$this->nezobrazovat.")";

                    //TODO: najit vsechny OK, pokud vytvarim novy TOK, tak nejdriv vygeneruju novy id_termin

                    $query_ok = "select `objekt_kategorie`.`id_objektu`,`objekt_kategorie`.`id_objekt_kategorie`
                                                from `cena_objekt_kategorie` join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)                                                           
                                                where `cena_objekt_kategorie`.`id_cena`=" . $this->id_cena . " ";
                    // echo $query_ok;
                    $ok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                    while ($row = mysqli_fetch_array($ok_result)) {
                        $termin_od = $this->change_date_cz_en($_POST["od"]);
                        $termin_do = $this->change_date_cz_en($_POST["do"]);
                        //echo "TOK: id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"].",".$_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]];
                        
                        if (($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]] == "no") or ($_POST["TOK_chovani"]=="nevytvaret")) {
                            //nedìlám nic, nechci vytváøet spojení cena - tok

                            // print_r($_POST);
                        } else if ($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]] == "new" ) {
                            //vytvarim novy termin, pokud uz neni vytvoreny
                            $this->id_termin = $this->get_id_termin();
                            //vytvorim napojeni TOK - cena
                            if ($this->query_insert_tok_cena == "") {
                                $this->query_insert_tok_cena .= "(" . $this->id_cena . "," . $this->id_zajezd . "," . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            } else {
                                $this->query_insert_tok_cena .= ", (" . $this->id_cena . "," . $this->id_zajezd . "," . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            }
                            //vytvorim novy TOK
                            
                            $kapacita = intval($_POST["kapacita_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]);
                            if ($this->query_insert_tok == "") {

                                $this->query_insert_tok .= "(" . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $row["id_objektu"] . ",\"" . $termin_od . "\",\"" . $termin_do . "\",
                                                                                    " . $kapacita . "," . $kapacita . ",0,0,0)";
                            } else {
                                $this->query_insert_tok .= ", (" . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $row["id_objektu"] . ",\"" . $termin_od . "\",\"" . $termin_do . "\",
                                                                                    " . $kapacita . "," . $kapacita . ",0,0,0)";
                            }


                        } else if (intval($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]) > 0) {
                            //vytvarim z existujiciho terminu
                            $id_termin = intval($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]);
                            if ($this->query_insert_tok_cena == "") {
                                $this->query_insert_tok_cena .= "(" . $this->id_cena . "," . $this->id_zajezd . "," . $id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";                         
                            } else {
                                $this->query_insert_tok_cena .= ", (" . $this->id_cena . "," . $this->id_zajezd . "," . $id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            }
                        } else if(($_POST["TOK_chovani"]=="vytvorit_nebo_priradit") or ($_POST["TOK_chovani"]=="priradit")){
                            $externalID = $_POST["externalID_".$id_cena."_".$_POST["currentID"]];
                                    
                            
                            $this->execute_vytvorit_priradit_tok($termin_od, $termin_do, $kapacita, $row, $externalID);

                            
                        }
                    }
                }
            } else if ($this->typ_pozadavku == "update") {
               
                $termin_od = $this->change_date_cz_en($_POST["od"]);
                $termin_do = $this->change_date_cz_en($_POST["do"]);
                 
                //hledame zda uz radek s touto cenou existuje, pokud ano -> update, jinak insert into
                if(sizeof((array)$this->ceny_update_id)>0){
                    $key = array_search($this->id_cena, $this->ceny_update_id);
                }else{
                    $key = false;
                }
                if ($key !== false) {
                    if ($this->pouzit) {
                        //vypocitam novou volnou kapacitu jako stara_volna + nova_celkova - stara_celkova
                        $this->kapacita_volna = $this->ceny_update[$key]["kapacita_volna"] + $this->kapacita_celkova - $this->ceny_update[$key]["kapacita_celkova"];
                        $this->pocet_zaznamu_update++;
                        //u updatu dle KV menim pouze nektere polozky
                        if($update_dle_kv==true){
                            $this->query_update[$this->pocet_zaznamu_update] = "`castka`=" . $this->castka . " WHERE `id_cena`=" . $this->id_cena . " and `id_zajezd`=" . $this->id_zajezd . " LIMIT 1";
                            if(($_POST["TOK_chovani"]=="vytvorit_nebo_priradit") or ($_POST["TOK_chovani"]=="priradit")){
                                $query_ok = "select `objekt_kategorie`.`id_objektu`,`objekt_kategorie`.`id_objekt_kategorie`
                                                                from `cena_objekt_kategorie` join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)                                                           
                                                                where `cena_objekt_kategorie`.`id_cena`=" . $this->id_cena . " ";
                                    // echo $query_ok;
                                $ok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                                while ($row = mysqli_fetch_array($ok_result)) {
                                    $kapacita = intval($_POST["kapacita_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]);
                                    $externalID = $_POST["externalID_".$id_cena."_".$_POST["currentID"]];                                                                
                                    $this->execute_vytvorit_priradit_tok($termin_od, $termin_do, $kapacita, $row, $externalID);
                                }
                            }
                        }else{
                            $this->query_update[$this->pocet_zaznamu_update] = "`castka`=" . $this->castka . ",`mena`=\"Kè\",`castka_euro`=" . $this->castka_euro . ",`kapacita_celkova`=" . $this->kapacita_celkova . ",`kapacita_volna`=" . $this->kapacita_volna . " ,`na_dotaz`=" . $this->na_dotaz . ",`vyprodano`=" . $this->vyprodano . ", `nezobrazovat`=".$this->nezobrazovat." WHERE `id_cena`=" . $this->id_cena . " and `id_zajezd`=" . $this->id_zajezd . " LIMIT 1";
                        }                        
                    } else {
                        //cenu nechci pouzivat, musim ji smazat
                        $this->pocet_zaznamu_delete++;
                        $this->query_delete[$this->pocet_zaznamu_delete] = " `id_cena`=" . $this->id_cena . " and `id_zajezd`=" . $this->id_zajezd . " LIMIT 1";

                        //TODO: smazu nejdriv vsechny cena_zajezd_tok, ktere patri k soucasne cene


                    }
                } else {
                    if ($this->pouzit) {
                        $this->pocet_zaznamu_insert++;
                        $this->query_insert[$this->pocet_zaznamu_insert] = "(" . $this->id_cena . "," . $this->id_zajezd . "," . $this->castka . ",\"Kè\"," . $this->castka_euro . "," . $this->kapacita_celkova . "," . $this->kapacita_celkova . "," . $this->na_dotaz . "," . $this->vyprodano . ",".$this->nezobrazovat.")";
                        
                        if($update_dle_kv==true and (($_POST["TOK_chovani"]=="vytvorit_nebo_priradit") or ($_POST["TOK_chovani"]=="priradit"))){
                            $query_ok = "select `objekt_kategorie`.`id_objektu`,`objekt_kategorie`.`id_objekt_kategorie`
                                                            from `cena_objekt_kategorie` join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)                                                           
                                                            where `cena_objekt_kategorie`.`id_cena`=" . $this->id_cena . " ";
                                // echo $query_ok;
                            $ok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                            while ($row = mysqli_fetch_array($ok_result)) {
                                $kapacita = intval($_POST["kapacita_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]);
                                $externalID = $_POST["externalID_".$id_cena."_".$_POST["currentID"]];                                                                
                                $this->execute_vytvorit_priradit_tok($termin_od, $termin_do, $kapacita, $row, $externalID); 
                            }                          
                        }
                    }
                }

                if ($this->pouzit and $update_dle_kv==false) {
                    //TODO: najit vsechny OK, pokud vytvarim novy TOK, tak nejdriv vygeneruju novy id_termin

                    $query_ok = "select `objekt_kategorie`.`id_objektu`,`objekt_kategorie`.`id_objekt_kategorie`
                                                from `cena_objekt_kategorie` join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)                                                           
                                                where `cena_objekt_kategorie`.`id_cena`=" . $this->id_cena . " ";
                    // echo $query_ok;
                    $ok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                    while ($row = mysqli_fetch_array($ok_result)) {
                        //smazu spojeni cena-tok, pokud existuje - to udelam v kazdem pripade, pripadne ho muzu znova vytvorit v dalsim kroku
                            $sql = "delete from `cena_zajezd_tok` where `id_cena`=" . $this->id_cena . " and `id_zajezd`=" . $this->id_zajezd . " and `id_objekt_kategorie`=" . $row["id_objekt_kategorie"] . "";
                            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                        
                        if ($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]] == "no") {
                            //zaznam uz je smazan, pouze nic neobnovuju

                            // print_r($_POST);
                        } else if ($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]] == "new") {
                            //vytvarim novy termin, pokud uz neni vytvoreny
                            if ($this->id_termin == 0) {
                                $sql = "select max(`id_termin`) as `termin` from `objekt_kategorie_termin` where 1";
                                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                                while ($row_termin = mysqli_fetch_array($data)) {
                                    $this->id_termin = intval($row_termin["termin"]);
                                    $this->id_termin++;
                                }
                            }
                            //vytvorim napojeni TOK - cena
                            if ($this->query_insert_tok_cena == "") {
                                $this->query_insert_tok_cena .= "(" . $this->id_cena . "," . $this->id_zajezd . "," . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            } else {
                                $this->query_insert_tok_cena .= ", (" . $this->id_cena . "," . $this->id_zajezd . "," . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            }
                            //vytvorim novy TOK
                            $termin_od = $this->termin_od;
                            $termin_do = $this->termin_do;
                            $kapacita = intval($_POST["kapacita_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]);
                            if ($this->query_insert_tok == "") {

                                $this->query_insert_tok .= "(" . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $row["id_objektu"] . ",\"" . $termin_od . "\",\"" . $termin_do . "\",
                                                                                    " . $kapacita . "," . $kapacita . ",0,0,0)";
                            } else {
                                $this->query_insert_tok .= ", (" . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $row["id_objektu"] . ",\"" . $termin_od . "\",\"" . $termin_do . "\",
                                                                                    " . $kapacita . "," . $kapacita . ",0,0,0)";
                            }


                        } else if (intval($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]) > 0) {
                            //vytvarim z existujiciho terminu
                            $id_termin = intval($_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]);
                            if ($this->query_insert_tok_cena == "") {
                                $this->query_insert_tok_cena .= "(" . $this->id_cena . "," . $this->id_zajezd . "," . $id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            } else {
                                $this->query_insert_tok_cena .= ", (" . $this->id_cena . "," . $this->id_zajezd . "," . $id_termin . "," . $row["id_objekt_kategorie"] . "," . $_POST["je_vstupenka_" . $this->pocet_zaznamu] . ")";
                            }
                        }
                    }
                }

            }
            //echo $this->pocet_zaznamu;

        }
        //if legal_data
    }
    function execute_vytvorit_priradit_tok($termin_od, $termin_do, $kapacita, $row, $hotelID = 0){
        //zde by asi chtelo zavest kontrolu nad tim, 
        //zda se jedna o spravnou OK korespondujici s goglobal hotel ID - pokud je pouzito
        //(`cena_promenna_cenova_mapa`.`external_id` = `objekt_kategorie`.`goglobal_hotel_id_ok` or  `cena_promenna_cenova_mapa`.`external_id` = `objekt_ubytovani`.`goglobal_hotel_id`) 
        $sql_delete_old = "delete from `cena_zajezd_tok` where `id_cena` = $this->id_cena and  `id_zajezd` = $this->id_zajezd";
        $results_delete = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_delete_old);
        if($hotelID > 0){
            $sql_termin = "select * from `objekt_kategorie_termin` 
            join `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`)
            left join `objekt_ubytovani` on (`objekt_kategorie`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
            where `objekt_kategorie_termin`.`id_objekt_kategorie` = ".$row["id_objekt_kategorie"]." 
                and (`objekt_kategorie`.`goglobal_hotel_id_ok` = $hotelID or  `objekt_ubytovani`.`goglobal_hotel_id` = $hotelID) 
                and `objekt_kategorie_termin`.`datetime_od` =  \"$termin_od 00:00:00\"
                and `objekt_kategorie_termin`.`datetime_do` =  \"$termin_do 00:00:00\"   
                limit 1";
        }else{
            $sql_termin = "select * from `objekt_kategorie_termin` 
            join `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`)
            left join `objekt_ubytovani` on (`objekt_kategorie`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
            where `objekt_kategorie_termin`.`id_objekt_kategorie` = ".$row["id_objekt_kategorie"]." 
                and (`objekt_kategorie`.`goglobal_hotel_id_ok` = 0 or `objekt_kategorie`.`goglobal_hotel_id_ok` is NULL)
                and (`objekt_ubytovani`.`goglobal_hotel_id` is NULL)
                and `objekt_kategorie_termin`.`datetime_od` =  \"$termin_od 00:00:00\"
                and `objekt_kategorie_termin`.`datetime_do` =  \"$termin_do 00:00:00\"   
                limit 1";
        }
        
        //echo $sql_termin;
        $data_termin = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_termin);
        $existing_termin = false;
        while ($row_termin = mysqli_fetch_array($data_termin)) {
             $existing_termin = true;
            //existuje TOK, priradim ho
            $id_termin = $row_termin["id_termin"];
            if ($this->query_insert_tok_cena == "") {
                $this->query_insert_tok_cena .= "(" . $this->id_cena . "," . $this->id_zajezd . "," . $id_termin . "," . $row["id_objekt_kategorie"] . ",0)";                         
            } else {
                $this->query_insert_tok_cena .= ", (" . $this->id_cena . "," . $this->id_zajezd . "," . $id_termin . "," . $row["id_objekt_kategorie"] . ",0)";
            }
        }                            
        if(!$existing_termin and $_POST["TOK_chovani"]=="vytvorit_nebo_priradit"){
            //sem se muzu dostat pouze pokud jsem na generovani terminu z kalkulacnich vzorcu a TOK muze byt zalozeny pouze na hotelech z GOGlobalu
            //zjistim zda soucasne navrhovany TOK odpovida vybranemu hotelu - mel by byt prave jeden, jinak nedelam nic
            //vytvorim novy TOK, ale pouze pokud byl                                 
            $query_correct_tok = "select count(`cena_promenna_cenova_mapa`.`id_cena_promenna`) as pocet from
                `cena_promenna_cenova_mapa` 
                    join `cena_promenna` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    join `cena_objekt_kategorie` on (`cena_promenna`.`id_cena` = `cena_objekt_kategorie`.`id_cena`)
                    join `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `cena_objekt_kategorie`.`id_objekt_kategorie`)
                    left join `objekt_ubytovani` on (`objekt_kategorie`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                where
                    `cena_promenna`.`id_cena` = $this->id_cena 
                    and `objekt_kategorie`.`id_objekt_kategorie` = ".$row["id_objekt_kategorie"]."
                    and (`cena_promenna_cenova_mapa`.`external_id` = `objekt_kategorie`.`goglobal_hotel_id_ok` or  `cena_promenna_cenova_mapa`.`external_id` = `objekt_ubytovani`.`goglobal_hotel_id`) 
                    and ((`cena_promenna_cenova_mapa`.termin_od <= \"$termin_od\" and `cena_promenna_cenova_mapa`.termin_do >= \"$termin_do\" and `cena_promenna_cenova_mapa`.`castka` is not null and `cena_promenna_cenova_mapa`.`termin_do_shift` is null ) 
                        or (`cena_promenna_cenova_mapa`.termin_od >= \"$termin_od\" and `cena_promenna_cenova_mapa`.termin_do <= \"$termin_do\" and `cena_promenna_cenova_mapa`.`castka` is not null and `cena_promenna_cenova_mapa`.`no_dates_generation` >= 1 ) 
                        or (`cena_promenna_cenova_mapa`.termin_od <= \"$termin_od\" and `cena_promenna_cenova_mapa`.termin_do >= DATE_ADD(\"$termin_do\", INTERVAL -(`cena_promenna_cenova_mapa`.`termin_do_shift`) DAY) and `cena_promenna_cenova_mapa`.`castka` is not null and `cena_promenna_cenova_mapa`.`termin_do_shift` is not null))

                ";
            //echo $query_correct_tok;
            $pocet = 0;
            $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_correct_tok);
            while ($row1 = mysqli_fetch_array($query)) {
                $pocet = $row1["pocet"];
            }
            if($pocet > 0){ // correct TOK for the current hotel assignment
                $kapacita = $this->kapacita_celkova;
                $this->id_termin = $this->get_id_termin();

                $query_tok = "INSERT INTO `objekt_kategorie_termin`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `datetime_od`, `datetime_do`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`, `kapacita_bez_omezeni`)
                                VALUES (" . $this->id_termin . "," . $row["id_objekt_kategorie"] . "," . $row["id_objektu"] . "
                                ,\"" . $termin_od . "\",\"" . $termin_do . "\"," . $kapacita . "," . $kapacita . ",0,0,0)
                			ON DUPLICATE KEY UPDATE  `id_termin` = " . $this->id_termin . "";    
                //echo $query_tok;
                $create_tok = $this->database->transaction_query($query_tok) or $this->chyba("Chyba pøi dotazu do databáze Create TOK " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                if ($this->query_insert_tok_cena == "") {
                    $this->query_insert_tok_cena .= "(" . $this->id_cena . "," . $this->id_zajezd . "," . $this->id_termin . "," . $row["id_objekt_kategorie"] . ",0)";                         
                } else {
                    $this->query_insert_tok_cena .= ", (" . $this->id_cena . "," . $this->id_zajezd . "," . $this->id_termin . "," . $row["id_objekt_kategorie"] . ",0)";
                }   
            }
        }
        
    }
    function get_id_termin(){
        if($this->id_termin!=0){
            return $this->id_termin;
        }else{
            $sql = "select max(`id_termin`) as `termin` from `objekt_kategorie_termin` where 1";
                                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                                while ($row_termin = mysqli_fetch_array($data)) {
                                    $this->id_termin = intval($row_termin["termin"]);
                                    $this->id_termin++;
                                }
            return   $this->id_termin;                     
        }                                         
    }
    
    /**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
    function correct_data($typ_pozadavku)
    {
        $ok = 1;
        //kontrolovane pole id_cena, id_zajezd
        if (!Validace::int_min($this->id_serial, 1)) {
            $ok = 0;
            $this->chyba("Seriál není identifikován");
        }
        if ($typ_pozadavku == "create" or $typ_pozadavku == "update") {
            if (!Validace::int_min_max($this->pocet, 1, MAX_CEN)) {
                $ok = 0;
                $this->chyba("Poèet cen není v povoleném intervalu 1 - " . MAX_CEN . "");
            }
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
    function legal_data($id_cena, $id_zajezd, $castka, $mena, $kapacita_volna, $kapacita_celkova, $vyprodano, $na_dotaz)
    {
        $ok = 1;
        //kontrolovane pole id_cena, id_zajezd
        if (!Validace::int_min($id_cena, 1)) {
            $ok = 0;
            $this->chyba("Cena není identifikována");
        }
        if (!Validace::int_min($id_zajezd, 1)) {
            $ok = 0;
            $this->chyba("Zájezd není identifikován");
        }
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**po prijmuti vsech dat vytvori cely dotaz a odesle ho do mysql*/
    function finish_query()
    {
        if ($this->legal_operation) {

            if ($this->probihajici_transakce == 0) { //pokud nejsem uprostred jine transakce, tak ji zahajim
                $this->database->start_transaction();
            }
            if ($this->query_insert_tok) {
                $create_tok = $this->database->transaction_query("INSERT INTO `objekt_kategorie_termin`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `datetime_od`, `datetime_do`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`, `kapacita_bez_omezeni`) VALUES  " . $this->query_insert_tok)
                or $this->chyba("Chyba pøi dotazu do databáze Create TOK" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
            //$this->chyba("INSERT INTO `objekt_kategorie_termin`( `id_termin`, `id_objekt_kategorie`, `id_objektu`, `datetime_od`, `datetime_do`, `kapacita_celkova`, `kapacita_volna`, `na_dotaz`, `vyprodano`, `kapacita_bez_omezeni`) VALUES  ".$this->query_insert_tok);
            //$this->chyba("INSERT INTO `cena_zajezd_tok`(`id_cena`, `id_zajezd`, `id_termin`, `id_objekt_kategorie`) VALUES ".$this->query_insert_tok_cena);

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
                //odeslu dotaz
                $create_ceny = $this->database->transaction_query($dotaz)
                or $this->chyba("Chyba pøi dotazu do databáze insert_cena_zajezdu<br/>\n $dotaz \n<br/>" . mysqli_error($GLOBALS["core"]->database->db_spojeni));

            }
            if ($this->pocet_zaznamu_update) {
                $i = 1;
                while ($i <= $this->pocet_zaznamu_update) {
                    $dotaz = $this->query_update[0] . $this->query_update[$i];
                    //echo $dotaz;
                    //skladam jednotlive dotazy a rovnou je odesilam
                    $update_ceny = $this->database->transaction_query($dotaz)
                    or $this->chyba("Chyba pøi dotazu do databáze update_cena_zajezdu" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $i++;
                }
            }
            if ($this->pocet_zaznamu_delete) {
                $i = 1;
                while ($i <= $this->pocet_zaznamu_delete) {
                    $dotaz = $this->query_delete[0] . $this->query_delete[$i];
                    //echo $dotaz;
                    //skladam jednotlive dotazy a rovnou je odesilam
                    $update_ceny = $this->database->transaction_query($dotaz)
                    or $this->chyba("Chyba pøi dotazu do databáze delete_cena_zajezdu" . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                    $i++;
                }
            }

            if ($this->query_insert_tok_cena) {
                echo "INSERT INTO `cena_zajezd_tok`(`id_cena`, `id_zajezd`, `id_termin`, `id_objekt_kategorie`, `je_vstupenka`) VALUES " . $this->query_insert_tok_cena;
                $create_tok = $this->database->transaction_query("INSERT INTO `cena_zajezd_tok`(`id_cena`, `id_zajezd`, `id_termin`, `id_objekt_kategorie`, `je_vstupenka`) VALUES 
                    " . $this->query_insert_tok_cena."
                    ON DUPLICATE KEY UPDATE  `je_vstupenka` = `je_vstupenka`;
                    ")
                or $this->chyba("Chyba pøi dotazu do databáze Create TOK-CENA" . mysqli_error($GLOBALS["core"]->database->db_spojeni));

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
        if ($typ_pozadavku == "edit") {
            $dotaz = "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`kapacita_bez_omezeni`,
								`cena_zajezd`.`id_zajezd`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`castka_euro`,
								`cena_zajezd`.`kapacita_celkova`,`cena_zajezd`.`kapacita_volna`,
								`cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`,
                                                                `cena_zajezd`.`nezobrazovat`
					  from `cena` left join 
					  		 `cena_zajezd` on (`cena`.`id_cena`=`cena_zajezd`.`id_cena` and `id_zajezd`= " . $this->id_zajezd . ")
					where `id_serial`= " . $this->id_serial . "
					order by  `poradi_ceny`,  `zakladni_cena` desc,`typ_ceny`,`nazev_ceny` ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "new") {
            $dotaz = "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`kapacita_bez_omezeni`
					  from `cena` 
					where `id_serial`= " . $this->id_serial . "
					order by `poradi_ceny`,`zakladni_cena` desc,`typ_ceny`,`nazev_ceny` ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "show") {
            $dotaz = "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`kapacita_bez_omezeni`,
								`cena_zajezd`.`id_zajezd`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`castka_euro`,
								`cena_zajezd`.`kapacita_celkova`,`cena_zajezd`.`kapacita_volna`,
								`cena_zajezd`.`na_dotaz`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`nezobrazovat`
					  from `cena` left join 
					  		 `cena_zajezd` on (`cena`.`id_cena`=`cena_zajezd`.`id_cena` and `id_zajezd`= " . $this->id_zajezd . ")
					where `id_serial`= " . $this->id_serial . "
					order by `poradi_ceny`,`zakladni_cena` desc,`typ_ceny`,`nazev_ceny` ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_ceny") {
            $dotaz = "select `id_cena`,`kapacita_celkova`,`kapacita_volna`
					  from `cena_zajezd`
					where `id_zajezd`= " . $this->id_zajezd . " ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_zajezd") {
            $dotaz = "select *
					  from `zajezd`
					where `id_zajezd`= " . $this->id_zajezd . " ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "delete") {
            $dotaz = "DELETE FROM `cena_zajezd`
						WHERE `id_cena`=" . $this->id_cena . " and `id_zajezd`=" . $this->id_zajezd . "
						LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "get_user_create") {
            $dotaz = "SELECT `id_user_create` FROM `serial`
						WHERE `id_serial`=" . $this->id_serial . "
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

        } else if ($typ_pozadavku == "create") {
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


    /**zobrazi menu s moznostmi editace jednotlivych cen*/
    function show_submenu()
    {
        $vystup = "<div class='submenu'><a href=\"?id_serial=" . $this->id_serial . "&amp;id_zajezd=" . $this->id_zajezd . "&amp;typ=cena_zajezd&amp;pozadavek=edit\">editovat ceny</a></div>";
        return $vystup;
    }

    /**zobrazi hlavicku k seznamu cen*/
    function show_list_header()
    {
        $vystup = "
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Název</th>
						<th>Cena Kè</th>
                                                <th>Cena <br/>EURO</th>
                        <th title=\"Objednávky jednotlivých služeb\">Objednané osoby</th>
						<th title=\"Celková kapacita\">Celk. <br/>kap.</th>
						<th title=\"Volná kapacita\">Volná <br/>kap.</th>
						<th title=\"Neomezená kapacita\">Neomez. <br/>kap.</th>
						<th title=\"Jen na dotaz\">Na dotaz</th>
						<th>Vyprodáno</th>
                                                <th title=\"Nezobrazovat\">Nezobraz.</th>
                                                <th>Pøiøazené TOK</th>
						<th>Možnosti editace</th>
					</tr>					
		";
        return $vystup;
    }

    /**obrazime seznam cen pro dany serial*/
    function show_list_item($typ_zobrazeni)
    {
        if ($typ_zobrazeni == "tabulka") {       
            
            
            $query_tok = "select distinct `objekt_kategorie`.`hlavni_kapacita`,`objekt_kategorie`.`prodavat_jako_celek`,
                                        `objekt_kategorie`.`kratky_nazev`, `objekt_kategorie`.`nazev`, `objekt`.`nazev_objektu`, `objekt`.`kratky_nazev_objektu`,
                                         `cena_zajezd_tok`.`id_cena`,`cena_zajezd_tok`.`je_vstupenka`,`objekt_kategorie_termin`.*
                                        from `cena_objekt_kategorie`                                                    
                                                   join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)
                                                   join objekt_serial on (`objekt_kategorie`.`id_objektu` = `objekt_serial`.`id_objektu`
                                                                            and `objekt_serial`.`id_serial` = " . intval($this->get_id_serial()) . ") 
                                                   join `objekt` on (`objekt`.`id_objektu` = `objekt_kategorie`.`id_objektu`)
                                                   left join ( `objekt_kategorie_termin` 
                                                            join `cena_zajezd_tok` on (`cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie` 
                                                                         and `cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin`
                                                                         and `cena_zajezd_tok`.`id_zajezd`=" . intval($this->get_id_zajezd()) . "
                                                                         and `cena_zajezd_tok`.`id_cena`=" . intval($this->get_id_cena()) . " )
                                                   ) on (`cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` and `cena_zajezd_tok`.`id_cena`= `cena_objekt_kategorie`.`id_cena` and `cena_zajezd_tok`.`id_zajezd`=" . intval($this->get_id_zajezd()) . ")                          
                                        where `cena_objekt_kategorie`.`id_cena`=" . $this->get_id_cena() . "
                                        ";
           // echo $query_tok;
           
           $query_pocty= "
                SELECT sum(`objednavka_cena`.`pocet`) as `pocet`, `objednavka`.`stav`            
                    FROM `zajezd` 
                    JOIN `objednavka`  ON `zajezd`.`id_zajezd` = `objednavka`.`id_zajezd`
                    join `objednavka_cena` on `objednavka_cena`.`id_objednavka` = `objednavka`.`id_objednavka`
         
                    WHERE `zajezd`.`id_zajezd` =  " . intval($this->get_id_zajezd()) . " and  `objednavka_cena`.`id_cena` = " . intval($this->get_id_cena()) . "
                    group by `objednavka`.`stav`  
                    ";                   
           //echo   $query_pocty;
           $pocty_osob = array();
           $result_pocty = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_pocty);
           while ($row_pocty = mysqli_fetch_array($result_pocty)) {
                 $pocty_osob[$row_pocty["stav"]] = $row_pocty["pocet"];
           }
           
            $pocty_osob[3] = $pocty_osob[1]+$pocty_osob[2]+$pocty_osob[3];
            $pocty_osob[6] = $pocty_osob[6]+$pocty_osob[7];
            $pocty_osob[8] = $pocty_osob[8]+$pocty_osob[9];
            
            $radek_pocet_osob = "
                <span title=\"opce\" class='osoby stav-opce'> ".intval($pocty_osob[3])."
                    </span><span title=\"rezervace\" class='osoby stav-rez'> ".intval($pocty_osob[4])."
                        </span><span title=\"záloha\" class='osoby stav-zal'> ".intval($pocty_osob[5])."
                            </span><span title=\"prodáno\" class='osoby stav-prodano'> ".intval($pocty_osob[6])."
                                </span><span title=\"storno\" class='osoby stav-storno'> ".intval($pocty_osob[8])."
                                </span><span title=\"voucher\" class='osoby stav-voucher'> ".intval($pocty_osob[10])." </span>";
           
           
            $text_tok = "";
                $celkova_kapacita = 0;
                $volna_kapacita = 0;            
            $result_tok = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_tok);
            while ($row = mysqli_fetch_array($result_tok)) {
                $use_kapacita_tok = 1;
                if($row["kapacita_volna"]!=""){                   
                   if($row["prodavat_jako_celek"]==1){
                       $celkova_kapacita += $row["kapacita_celkova"];
                       $volna_kapacita += $row["kapacita_volna"];
                       $text_tok .= $row["nazev"].": volná kapacita ".$row["kapacita_volna"]." <br/> "; 
                   }else{
                       $celkova_kapacita += $row["kapacita_celkova"]*$row["hlavni_kapacita"];
                       $volna_kapacita += $row["kapacita_volna"]*$row["hlavni_kapacita"];
                       $text_tok .= $row["nazev"].": volná kapacita ".$row["kapacita_volna"]."*".$row["hlavni_kapacita"]." <br/> "; 
                   }
                }else{
                   $text_tok .= "<span class=\"nevyrazne\">".$row["nazev"].": TOK nepøiøazen</span><br/> "; 
                }                
            }
            if(!$use_kapacita_tok){
                $celkova_kapacita = $this->get_kapacita_celkova();
                $volna_kapacita = $this->get_kapacita_volna();  
            }
            if ($this->suda == 1) {
                $vypis = $vypis . "<tr class=\"suda\">";
            } else {
                $vypis = $vypis . "<tr class=\"licha\">";
            }

            //tvorba  kapacita bez omezeni
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
            if ($this->get_nezobrazovat()) {
                $nezobrazovat = "<span class=\"red\">NEZOBRAZOVAT</span>";
            } else {
                $nezobrazovat = "<span class=\"green\">ZOBRAZIT</span>";
            }
            if ($this->get_pouzita_cena()) {
                $cena = "" . $this->get_castka() . " " . $this->get_mena() . "";
                if ($this->get_castka_euro() == 0 or $this->get_castka_euro() == "") {
                    $cena_eur = " - ";
                } else {
                    $cena_eur = "" . $this->get_castka_euro() . " €";
                }

            } else {
                $cena =$cena_eur =$nezobrazovat = $vyprodano =$na_dotaz =$celkova_kapacita =$volna_kapacita = "<span class=\"red\" title=\"nepoužívaná služba\">X</span>";

            }
            $vypis = $vypis . "
							<td class=\"id\">
								" . $this->get_id_cena() . "
							</td>			
							<td class=\"nazev\">
								" . $this->get_nazev_ceny() . "
							</td>
							<td class=\"cena\">
								" . $cena . "
							</td>
                                                        <td class=\"cena\">
								" . $cena_eur . "
							</td>
                            <td class=\"hit_zajezd\">
                                                        
								" . $radek_pocet_osob . "
							</td>
							<td class=\"kapacita_celkova\">
                                                        
								" . $celkova_kapacita . "
							</td>
							<td class=\"kapacita_volna\">
								" . $volna_kapacita . "
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
                                                        <td class=\"nezobrazovat\">
								" . $nezobrazovat . "
							</td>
                                                        <td>
                                                            ".$text_tok."
                                                        </td>
							<td class=\"menu\">
								<a href=\"?id_serial=" . $this->id_serial . "&amp;id_zajezd=" . $this->id_zajezd . "&amp;typ=cena_zajezd&amp;pozadavek=edit\">editovat ceny</a>
								 | <a class='anchor-delete' href=\"?typ=cena_zajezd&amp;pozadavek=delete&amp;id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;id_cena=" . $this->get_id_cena() . "\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";

            return $vypis;
        }
        //typ zobrazeni
    }

    
    
    /**zobrazeni formulare*/
    function show_form($typ_zobrazeni = "", $termin_od = "", $termin_do = "")
    {

        //pro typ zobrazeni="new_zajezd" (pouzito jako soucast formulare pro novy zajezd) neni zobrazena hlavicka ani odesilaci tlacitko)
        //budeme pristupovat k metodam tridy aktualniho zamestnance a serialu - musi byt nainicializovany!
        $zamestnanec = User_zamestnanec::get_instance();
        GLOBAL $serial;

        //podle typu pozadavku vypisu spravny cil scriptu
        if ($this->typ_pozadavku == "edit") {
            $action = "?id_serial=" . $this->id_serial . "&amp;id_zajezd=" . $this->id_zajezd . "&amp;typ=cena_zajezd&amp;pozadavek=update";
            $terminy_zajezdu = "<input type=\"hidden\" id=\"some_kv_exists\" value=\"0\"/><input type=\"hidden\" id=\"form_type\" value=\"edit\"/><input type=\"hidden\" id=\"zajezd_termin_od\" value=\"".$this->change_date_en_cz($this->termin_od)."\"/><input type=\"hidden\" id=\"zajezd_termin_do\" value=\"".$this->change_date_en_cz($this->termin_do)."\"/>";
            $loaded_data = Cena_serial::load_kv_data($this->id_serial);
            $script_kv = $loaded_data[0];
            $script_update_prices = "<script type=\"text/javascript\">
                $(document).ready(function () {
                    updatePricesByKV();
                    }); 
                </script>";
            $cena_kv = "<th class=\"cena_dle_kv\">Cena dle KV</th>";
            //tlacitka pro odesilani
            if ($this->legal("update")) {
                $submit = "<input type=\"submit\" value=\"Upravit ceny zájezdu\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci cen zájezdu</strong>\n";
            }
         
            
        } else if ($this->typ_pozadavku == "new") {
            $action = "?id_serial=" . $this->id_serial . "&amp;id_zajezd=" . $this->id_zajezd . "&amp;typ=cena_zajezd&amp;pozadavek=create";
            $terminy_zajezdu = "";
            //tlacitka pro odesilani
            if ($this->legal("create")) {
                $submit = "<input type=\"submit\" value=\"Vytvoøit ceny zájezdu\" />\n";
            } else {
                $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení cen zájezdu</strong>\n";
            }
        } else {
            $action = "";
        }

        $i = 1;
        //hlavicka formulare - pokud je formular soucasti formulare pro novy zajezd, zadna hlavicka se nevypisuje
        if ($typ_zobrazeni == "new_zajezd") {
            $vypis = "";
        } else {
            $vypis = "$script_kv $script_update_prices <form action=\"" . $action . "\" method=\"post\" onLoad=\"calculateAllCapacities();\" onSubmit=\"enableAllInputnput();\">$terminy_zajezdu ";
        }
        $vypis = $vypis . "<table  class=\"list\">  
                                <tr>
						<th>Název</th>
						<th>Cena Kè</th>
                                                $cena_kv
                                                <th>Cena <br/>EURO</th>
						<th title=\"Celková kapacita\">Celk. <br/>kap.</th>
						<th title=\"Volná kapacita\">Volná <br/>kap.</th>
						<th title=\"Neomezená kapacita\">Neomez. <br/>kap.</th>
						<th title=\"Jen na dotaz\">Na dotaz</th>
						<th>Vyprodáno</th>
                                                <th title=\"Nezobrazovat\">Nezobraz.</th>
                                                <th>TOK</th>
						<th>Vytvoøit cenu</th>
				</tr>
						";
        $multiplicator_array = array();
        $kapacita_celkova_array = array();
        $kapacita_volna_array = array();
        $cena_array = array();
        $ok_list_array = array();
        $show_capacities = "";
        while ($this->get_next_radek()) {
            if ($this->suda == 1) {
                $vypis = $vypis . "<tr class=\"suda\">";
            } else {
                $vypis = $vypis . "<tr class=\"licha\">";
            }

            $query_ok = "select 
                            `objekt_kategorie`.`id_objektu`,`objekt_kategorie`.`id_objekt_kategorie`,`objekt_kategorie`.`hlavni_kapacita`,`objekt_kategorie`.`prodavat_jako_celek`,
                            `objekt_kategorie`.`kratky_nazev`, `objekt_kategorie`.`nazev`, `objekt`.`nazev_objektu`, `objekt`.`kratky_nazev_objektu`  
                        from `cena_objekt_kategorie` 
                            join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`)
                            join `objekt` on (`objekt`.`id_objektu` = `objekt_kategorie`.`id_objektu`)
                            join objekt_serial on (`objekt_kategorie`.`id_objektu` = `objekt_serial`.`id_objektu`
                                and `objekt_serial`.`id_serial` = " . intval($this->get_id_serial()) . ") 
                        where `cena_objekt_kategorie`.`id_cena`=" . $this->get_id_cena() . " ";
            //echo $query_ok;
            $objektove_kategorie_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
            $objekt_kategorie = "<table>";
            $disable_capacity = false;
            $disable_edit_tok = false;
            $disable_edit_je_vstupenka = false;
            $ok_list_array[$i] = "";
            $cena_je_vstupenka = 0;
            
            while ($row = mysqli_fetch_array($objektove_kategorie_result)) {
                $tok = "";
                $input_vstupenka = "";
                $je_vstupenka=0;
                $script="";
                $disable_capacity = true;
                if($row["id_objektu"]==$this->id_ridici_objekt){                                
                    $disable_edit_tok = true;
                    $disable_edit_je_vstupenka = true;
                    
                }else if($this->use_cena_tok[$this->get_id_cena()]==$row["id_objekt_kategorie"]){                                
                    $disable_edit_tok = true;
                    $show_additional_tok = true;
                    //$disable_edit_je_vstupenka = true;
                    $je_vstupenka=1;     
                    
                }
                

                if ($ok_list_array[$i] == "") {
                    $ok_list_array[$i] .= $row["id_objekt_kategorie"];
                } else {
                    $ok_list_array[$i] .= "," . $row["id_objekt_kategorie"];
                }

                if ($row["prodavat_jako_celek"] == 0) {
                    $multiplicator_array[$i . "_" . $row["id_objekt_kategorie"]] = $row["hlavni_kapacita"];
                } else {
                    $multiplicator_array[$i . "_" . $row["id_objekt_kategorie"]] = 1;
                }

                if ($termin_od != "" and $termin_do != "") {
                    $query_tok = "select `cena_zajezd_tok`.`id_cena`,`cena_zajezd_tok`.`je_vstupenka`,`objekt_kategorie_termin`.*
                                        from `objekt_kategorie_termin` 
                                        left join `cena_zajezd_tok` on (`cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie` 
                                                                         and `cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin`
                                                                         and `cena_zajezd_tok`.`id_zajezd`=" . intval($this->get_id_zajezd()) . "
                                                                         and `cena_zajezd_tok`.`id_cena`=" . intval($this->get_id_cena()) . " )                                                         
                                        where `objekt_kategorie_termin` .`id_objekt_kategorie`=" . $row["id_objekt_kategorie"] . " and `datetime_od` >= \"" . $termin_od . " 00:00:00\" and `datetime_do`  <= \"" . $termin_do . " 23:59:59\"
                                        ";
                      //echo $query_tok;
                    $tok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_tok);
                    $additional_tok = "";
                    $already_selected = 0;
                    while ($row_tok = mysqli_fetch_array($tok_result)) {
                        // print_r($row_tok);

                        $kapacita_celkova_array[$i . "_" . $row["id_objekt_kategorie"] . "_" . $row_tok["id_termin"]] = $row_tok["kapacita_celkova"];
                        $kapacita_volna_array[$i . "_" . $row["id_objekt_kategorie"] . "_" . $row_tok["id_termin"]] = $row_tok["kapacita_volna"];
                        $cena_array[$i . "_" . $row["id_objekt_kategorie"] . "_" . $row_tok["id_termin"]] = $row_tok["cena"];

                        if ($row_tok["id_cena"] != "") {
                            $cena_je_vstupenka = $row_tok["je_vstupenka"];
                            $select = "checked=\"checked\"";
                            if($disable_edit_je_vstupenka){
                                $input_vstupenka =  "<input id=\"je_vstupenka_" . $i . "\" name=\"je_vstupenka_" . $i . "\"  type=\"hidden\" value=\"" . $cena_je_vstupenka . "\"  onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" >
                                                    <input id=\"je_vstupenka_hidden_" . $i . "\" name=\"je_vstupenka_hidden_" . $i . "\" type=\"hidden\" value=\"" . $cena_je_vstupenka . "\"><br/> ";
                                
                                
                            }else{
                                if($cena_je_vstupenka){
                                    $checkbox_je_vstupenka = "checked=\"checked\"";
                                }else{
                                    $checkbox_je_vstupenka = "";
                                }
                                $input_vstupenka =  "Použít cenu TOK <input  id=\"je_vstupenka_" . $i . "\"  name=\"je_vstupenka_" . $i . "\" ".$checkbox_je_vstupenka."  type=\"checkbox\" value=\"1\"  onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" >
                                                <input id=\"je_vstupenka_hidden_" . $i . "\" name=\"je_vstupenka_hidden_" . $i . "\" type=\"hidden\" value=\"0\"><br/> ";
                            }
                            
                            $already_selected = 1;
                            $show_capacities .= "computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );";
                            if($disable_edit_tok){
                                $tok = "
                                     <input ".$select." name=\"id_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\"  type=\"radio\" value=\"" . $row_tok["id_termin"] . "\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\">
                                        Id: " . $row_tok["id_termin"] . " (" . $this->change_date_en_cz_short($row_tok["datetime_od"]) . " - " . $this->change_date_en_cz_short($row_tok["datetime_do"]) . ")  
                                               ";
                            }
                        } else {
                            $select = "";
                            $input_vstupenka =  "Použít cenu TOK <input  id=\"je_vstupenka_" . $i . "\"  name=\"je_vstupenka_" . $i . "\" ".$checkbox_je_vstupenka."  type=\"checkbox\" value=\"1\"  onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" >
                                        <input id=\"je_vstupenka_hidden_" . $i . "\" name=\"je_vstupenka_hidden_" . $i . "\" type=\"hidden\" value=\"0\"><br/> ";                            
                        }
                        if($je_vstupenka==1){
                            $onload_script.="computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\n";
                           /* $input_vstupenka =  "Použít cenu TOK <input id=\"je_vstupenka_" . $i . "\" name=\"je_vstupenka_" . $i . "\" checked=\"checked\" type=\"checkbox\" value=\"1\"  onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" />
                                                    <input id=\"je_vstupenka_hidden_" . $i . "\" name=\"je_vstupenka_hidden_" . $i . "\" type=\"hidden\" value=\"1\">";*/
                        }
                            $additional_tok .= "<input " . $select . " name=\"id_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\"  type=\"radio\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" value=\"" . $row_tok["id_termin"] . "\"> Id: " . $row_tok["id_termin"] . ",
                                                (" . $this->change_date_en_cz_short($row_tok["datetime_od"]) . " - " . $this->change_date_en_cz_short($row_tok["datetime_do"]) . ") <br/>";
                     }
                }
                if ($this->typ_pozadavku == "new") {
                    if ($row["kratky_nazev_objektu"] != "") {
                        $nazev_objektu = $row["kratky_nazev_objektu"];
                    } else {
                        $nazev_objektu = $row["nazev_objektu"];
                    }
                    if ($row["kratky_nazev"] != "") {
                        $nazev_ok = $row["kratky_nazev"];
                    } else {
                        $nazev_ok = $row["nazev"];
                    }
                    
                    if(!$disable_edit_tok){
                        $tok = "<input name=\"id_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\"  type=\"radio\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" value=\"new\" checked=\"checked\">
                                    Nový TOK <input name=\"kapacita_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\" title=\"Celková kapacita vytváøeného TOK\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" type=\"text\" value=\"0\" size=\"3\"><br/>
                                    " . $additional_tok . "
                                    <input name=\"id_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\"  type=\"radio\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" value=\"no\">Nevytváøet ";
                    }else if($show_additional_tok){
                        $tok = $additional_tok;
                    }
                    $objekt_kategorie .= "<tr><td>" . $nazev_objektu . ", " . $nazev_ok . "
                             <td>".$input_vstupenka.$tok;
                } else {
                    /*$query_tok = "SELECT `id_termin` FROM `cena_zajezd_tok` WHERE `id_cena`=".$this->get_id_cena()."
                                                        and `id_zajezd`=".$this->get_id_zajezd()."
                                                        and `id_objekt_kategorie`=".$row["id_objekt_kategorie"]." limit 1";
                    $tok_id = "";
                    $tok_result=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_tok);
                    while ($row_tok = mysqli_fetch_array($tok_result)) {
                        $tok_id = $row_tok["id_termin"];
                    }*/
                    if ($row["kratky_nazev_objektu"] != "") {
                        $nazev_objektu = $row["kratky_nazev_objektu"];
                    } else {
                        $nazev_objektu = $row["nazev_objektu"];
                    }
                    if ($row["kratky_nazev"] != "") {
                        $nazev_ok = $row["kratky_nazev"];
                    } else {
                        $nazev_ok = $row["nazev"];
                    }

                    /*if($tok_id!=""){
                        $objekt_kategorie .= "<tr><td>".$nazev_objektu.", ".$nazev_ok."<td> TOK id: ".$tok_id."<br/>";
                    }else{*/
                    if (!$already_selected) {
                        $select = "checked=\"checked\"";
                    } else {
                        $select = "";
                    }
                    if(!$disable_edit_tok){
                            $tok = "<input name=\"id_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" type=\"radio\" value=\"new\">Nový TOK <input name=\"kapacita_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\" title=\"Celková kapacita vytváøeného TOK\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array  );\" type=\"text\" value=\"0\" size=\"3\"><br/>
                                        " . $additional_tok . "
                                        <input name=\"id_tok_" . $i . "_" . $row["id_objekt_kategorie"] . "\" onChange=\"computeCapacitiesTOK($i, ok_list[$i], kapacita_celkova_array, kapacita_volna_array, multiplicator_array, cena_array  );\" type=\"radio\" value=\"no\" " . $select . ">Nevytváøet <br/>";
                    }else if($show_additional_tok){
                        $tok = $additional_tok;
                    }
                    $objekt_kategorie .= "<tr><td>" . $nazev_objektu . ", " . $nazev_ok . "
                             <td>".$input_vstupenka.$tok;                    
                    /*}*/

                }
            }
            $objekt_kategorie .= "</table>";
            //tvorba jednotlivych poli formulare


            //tvorba  kapacita bez omezeni
            if ($this->get_kapacita_bez_omezeni()) {
                $kapacita_bez_omezeni = "<span class=\"green\">ANO</span>";
            } else {
                $kapacita_bez_omezeni = "<span class=\"red\">NE</span>";
            }

            //tvorba kapacita na dotaz
            if ($this->get_na_dotaz()) {
                $na_dotaz = "<input type=\"checkbox\" name=\"na_dotaz_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $na_dotaz = "<input type=\"checkbox\" name=\"na_dotaz_" . $i . "\" value=\"1\" />";
            }

            //tvorba kapacita vyprodano
            if ($this->get_vyprodano()) {
                $vyprodano = "<input type=\"checkbox\" name=\"vyprodano_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $vyprodano = "<input type=\"checkbox\" name=\"vyprodano_" . $i . "\" value=\"1\" />";
            }
            
            if ($this->get_nezobrazovat()) {
                $nezobrazovat = "<input type=\"checkbox\" name=\"nezobrazovat_" . $i . "\" value=\"1\" checked=\"checked\" />";
            } else {
                $nezobrazovat = "<input type=\"checkbox\" name=\"nezobrazovat_" . $i . "\" value=\"1\" />";
            }
            
            if ($this->typ_pozadavku == "new") {
                $pouziti_ceny = "<input type=\"checkbox\" name=\"pouzit_cenu_" . $i . "\" value=\"1\"  checked=\"checked\" />";
            } else if ($this->get_pouzita_cena()) {
                $pouziti_ceny = "<input type=\"hidden\" name=\"pouzit_cenu_" . $i . "\" value=\"1\"/> <span class=\"green\">ANO</span>";
            } else {
                $pouziti_ceny = "<input type=\"checkbox\" name=\"pouzit_cenu_" . $i . "\" value=\"1\" />";
            }
            if ($disable_capacity) {
                $disable_capacity_input = " disabled=\"disabled\" ";
            } else {
                $disable_capacity_input = " ";
            }
            if ($cena_je_vstupenka) {
                $disable_cena_input = " disabled=\"disabled\" ";
            } else {
                $disable_cena_input = " ";
            }
            if ($this->typ_pozadavku == "edit") {
                $cena_dle_kv = "<td class=\"cena_dle_kv\" id=\"castka_kv_" . $i . "\" ></td>";
            }else{
                $cena_dle_kv = "";
            }
            $vypis = $vypis . "
							<td class=\"nazev\">
								<input class=\"cena_id\" name=\"id_cena_" . $i . "\" type=\"hidden\" value=\"" . $this->get_id_cena() . "\" />
								" . $this->get_nazev_ceny() . "
							</td>
							<td class=\"castka\">
								<input type=\"text\" id=\"castka_" . $i . "\" class=\"medNumber\" name=\"castka_" . $i . "\" value=\"" . $this->get_castka() . "\" $disable_cena_input /> Kè
							</td>
                                                        $cena_dle_kv
                                                        <td class=\"castka\">
								<input type=\"text\" name=\"castka_euro_" . $i . "\" class=\"medNumber\" value=\"" . $this->get_castka_euro() . "\" $disable_cena_input  />  €
							</td>
							<td class=\"kapacita_celkova\">
								<input type=\"text\" name=\"kapacita_celkova_" . $i . "\"  class=\"smallNumber\" value=\"" . $this->get_kapacita_celkova() . "\" size=\"4\" $disable_capacity_input />
							</td>					
							<td class=\"kapacita_volna\">
                                                                <input type=\"hidden\" id=\"kapacita_volna_" . $i . "\" name=\"kapacita_volna_" . $i . "\" value=\"" . $this->get_kapacita_volna() . "\" />
								<span id=\"kapacita_volna_text_" . $i . "\">" . $this->get_kapacita_volna() . "</span>
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
                                                        <td class=\"nezobrazovat\">
								" . $nezobrazovat . "
							</td>
							<td class=\"vyprodano\">
								" . $objekt_kategorie . "
							</td>
							<td class=\"vyprodano\">
								" . $pouziti_ceny . "
							</td>
						</tr>";

            $i++;
        }
        $script_tok = "<script type=\"text/javascript\">
                                            var multiplicator_array = new Array();
                                            var kapacita_volna_array = new Array();
                                            var kapacita_celkova_array = new Array();
                                            var cena_array = new Array();
                                            var ok_list = new Array();\n";
        foreach ($kapacita_volna_array as $key => $value) {
            $script_tok .= "kapacita_volna_array[\"$key\"] = ".intval($value).";\n";
        }
        foreach ($kapacita_celkova_array as $key => $value) {
            $script_tok .= "kapacita_celkova_array[\"$key\"] = ".intval($value).";\n";
        }
        foreach ($cena_array as $key => $value) {
            $script_tok .= "cena_array[\"$key\"] = ".intval($value).";\n";
        }
        foreach ($multiplicator_array as $key => $value) {
            $script_tok .= "multiplicator_array[\"$key\"] = ".intval($value).";\n";
        }
        foreach ($ok_list_array as $key => $value) {
            if ($value != "") {
                $script_tok .= "ok_list[$key] = \"$value\";\n";
            }
        }        
        $script_tok .= "window.onload=function(){
            ".$show_capacities." 
                };";
        $script_tok .= "</script>";
        //tlacitka pro odeslani - pokud je formular soucasti formulare pro novy zajezd, zadna hlavicka se nevypisuje
        if ($typ_zobrazeni == "new_zajezd") {
            $submit = "";
        }

        //posledni podminka selhala, skutecny pocet radku je i-1
        $i = $i - 1;
        $vypis = $script_tok . $vypis . "<input name=\"pocet\" type=\"hidden\" value=\"" . $i . "\" />";

        $vypis = $vypis . "</table>" . $submit . "";
        return $vypis;
    }

    /*metody pro pristup k parametrum*/
    function get_id_serial()
    {
        return $this->id_serial;
    }

    function get_pocet()
    {
        return $this->pocet;
    }

    function get_id_cena()
    {
        return $this->radek["id_cena"];
    }

    function get_id_zajezd()
    {
        return $this->radek["id_zajezd"];
    }

    function get_pouzita_cena()
    {
        if ($this->radek["id_zajezd"] == "") {
            return false;
        } else {
            return true;
        }
    }

    function get_nazev_ceny()
    {
        return $this->radek["nazev_ceny"];
    }

    function get_kapacita_bez_omezeni()
    {
        return $this->radek["kapacita_bez_omezeni"];
    }

    function get_castka()
    {
        return $this->radek["castka"];
    }

    function get_castka_euro()
    {
        if ($this->radek["castka_euro"] != 0) {
            return $this->radek["castka_euro"];
        } else {
            return "";
        }

    }

    function get_termin_od()
    {
        return $this->termin_od;
    }

    function get_termin_do()
    {
        return $this->termin_do;
    }

    function get_mena()
    {
        return $this->radek["mena"];
    }

    function get_kapacita_celkova()
    {
        return $this->radek["kapacita_celkova"];
    }

    function get_kapacita_volna()
    {
        return $this->radek["kapacita_volna"];
    }

    function get_na_dotaz()
    {
        return $this->radek["na_dotaz"];
    }

    function get_vyprodano()
    {
        return $this->radek["vyprodano"];
    }
    function get_nezobrazovat()
    {
        return $this->radek["nezobrazovat"];
    }
    function get_id_user_create()
    {
        //pokud uz id mame, vypiseme ho
        if ($this->id_user_create != 0) {
            return $this->id_user_create;
            //nemame id dokumentu (vytvarime ho)
        } else if ($this->id_serial == 0) {
            return $this->id_zamestnance;
        } else {
            $data_id = mysqli_fetch_array($this->database->query($this->create_query("get_user_create")));
            $this->id_user_create = $data_id["id_user_create"];
            return $data_id["id_user_create"];
        }
    }
}

?>
