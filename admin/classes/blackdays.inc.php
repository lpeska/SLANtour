<?php

/**
 * zajezd_list.inc.php - trida pro zobrazeni seznamu zajezdu daneho serialu
 */
class Blackdays extends Generic_list {

    //vstupni data
    protected $id_blackdays;
    protected $id_zajezd;
    protected $od;
    protected $do;
    protected $moznosti_editace;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR  -----------------	

    function __construct($id_zajezd, $od, $do, $typ_dotazu, $id_blackdays = "", $moznosti_editace = "") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();
        
        //kontrola vstupnich dat
        $this->moznosti_editace = $this->check($moznosti_editace);
        $this->id_zajezd = $this->check_int($id_zajezd);
        $this->od = $this->change_date_cz_en($this->check($od));
        $this->do = $this->change_date_cz_en($this->check($do));
        $this->id_blackdays = $this->check_int($id_blackdays);

        //ziskani seznamu z databaze	
        if ($this->legal()) {
            if ($typ_dotazu == "create") {
                $this->data = $this->database->query($this->create_query("create"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));                          
            } else if ($typ_dotazu == "update") {
                $this->data = $this->database->query($this->create_query("update"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            } else if ($typ_dotazu == "delete") {
                $this->data = $this->database->query($this->create_query("delete"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	
    /* vytvoreni dotazu ze zadanych parametru */
    function create_query($typ_dotazu) {
        if ($typ_dotazu == "create") {
            $dotaz = "  INSERT INTO zajezd_blackdays 
                            (`id_zajezd`,`od`,`do`)
                        VALUES
                            ('$this->id_zajezd', '$this->od', '$this->do');";
        } else if ($typ_dotazu == "update") {            
            $dotaz= "   UPDATE zajezd_blackdays 
                        SET `od`='$this->od',`do`='$this->do' 
                        WHERE `id_blackdays`=$this->id_blackdays
                        LIMIT 1";
        } else if ($typ_dotazu == "delete") {
            $dotaz= "   DELETE FROM zajezd_blackdays 
                        WHERE `id_blackdays`=".$this->id_blackdays."
                        LIMIT 1";
        }
        return $dotaz;
    }

    /* zjistim, zda mam opravneni k pozadovane akci */

    function legal() {
        $legal = true;
        $zamestnanec = User_zamestnanec::get_instance();
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        if (!$zamestnanec->get_bool_prava($id_modul, "read"))
            $legal = false;
        if (!$zamestnanec->get_bool_prava($id_modul, "edit_svuj"))
            $legal = false;        
        if (!$zamestnanec->get_bool_prava($id_modul, "edit_cizi"))
            $legal = false;        

        return $legal;
    }
    
    function getIdBlackdays() {
        return $this->id_blackdays;
    }

}

?>
