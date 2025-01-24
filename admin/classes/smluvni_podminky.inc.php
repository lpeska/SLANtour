<?php

class SmluvniPodminky extends Generic_list {

    //smluvni_podminky
    protected $id_smluvni_podminky;
    protected $castka;
    protected $procento;
    protected $prodleva;
    protected $typ;    
    //smluvni_podminky_nazev
    protected $nazev;
    protected $dokument_id;
    protected $id_smluvni_podminky_nazev;
    //db
    public $database;    

    function __construct($typ_dotazu, $id_smluvni_podminky, $nazev, $castka, $procento, $prodleva, $typ, $dokument_id, $id_smluvni_podminky_nazev = "") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->id_smluvni_podminky = $this->check_int($id_smluvni_podminky);
        $this->castka = $this->check_double($castka);
        $this->procento = $this->check_int($procento);
        $this->prodleva = $this->check_int($prodleva);
        $this->typ = $this->check(iconv("UTF-8", "cp1250", $typ));
        $this->dokument_id = $this->check_int($dokument_id);

        $this->nazev = $this->check(iconv("UTF-8", "cp1250", $nazev));
        $this->id_smluvni_podminky_nazev = $this->check_int($id_smluvni_podminky_nazev);

        if ($this->legal()) {
            $this->data = $this->database->query($this->create_query($typ_dotazu)) || $this->chyba("Err: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            if ($typ_dotazu == "delete_nazev") {
                //kdyz jsem poslal 2 DELETE statementy v jednom dotazu (a jeden neovlivnil zadne radky), hodilo to chybu
                $this->data = $this->database->query($this->create_query($typ_dotazu, "", "secondary")) || $this->chyba("Err: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

    function create_query($typ_dotazu, $id = "", $secondary = "") {
        if ($typ_dotazu == "create_nazev") {
            $dotaz = "  INSERT INTO smluvni_podminky_nazev
                            (`nazev`, `dokument_id`)
                        VALUES
                            ('$this->nazev', '$this->dokument_id');";
        } else if ($typ_dotazu == "update_nazev") {
            $dotaz = "  UPDATE smluvni_podminky_nazev
                        SET `nazev`='$this->nazev', `dokument_id`='$this->dokument_id'
                        WHERE `id_smluvni_podminky_nazev`=$this->id_smluvni_podminky_nazev;";
        } else if ($typ_dotazu == "delete_nazev") {
            if ($secondary == "") {
                $dotaz = "  DELETE FROM smluvni_podminky_nazev
                            WHERE `id_smluvni_podminky_nazev`=" . $this->id_smluvni_podminky_nazev . ";";
            } else {
                $dotaz = "  DELETE FROM smluvni_podminky
                            WHERE `id_smluvni_podminky_nazev`=" . $this->id_smluvni_podminky_nazev . ";";
            }
        } else if ($typ_dotazu == "create") {
            $dotaz = "  INSERT INTO smluvni_podminky 
                            (`id_smluvni_podminky_nazev`,`castka`,`procento`,`prodleva`,`typ`)
                        VALUES
                            ('$this->id_smluvni_podminky_nazev', '$this->castka', '$this->procento', '$this->prodleva', '$this->typ');";
        } else if ($typ_dotazu == "update") {
            $dotaz = "  UPDATE smluvni_podminky 
                        SET `castka`='$this->castka',`procento`='$this->procento',`prodleva`='$this->prodleva',`typ`='$this->typ'
                        WHERE `id_smluvni_podminky`=$this->id_smluvni_podminky
                        LIMIT 1";
        } else if ($typ_dotazu == "delete") {
            $dotaz = "  DELETE FROM smluvni_podminky 
                        WHERE `id_smluvni_podminky`=" . $this->id_smluvni_podminky . "
                        LIMIT 1;";
        }
//        echo "$dotaz<br/>";exit();
        return $dotaz;
    }

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

}

?>
