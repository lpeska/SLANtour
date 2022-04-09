<?php

/**
 * zajezd_list.inc.php - trida pro zobrazeni seznamu zajezdu daneho serialu
 */
class Blackdays_list extends Generic_list {

    //vstupni data
    protected $id_zajezd;
    protected $count;
    protected $moznosti_editace;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR  -----------------	

    function __construct($id_zajezd, $moznosti_editace = "", $typ_dotazu = "show") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->moznosti_editace = $this->check($moznosti_editace);
        $this->id_zajezd = $this->check_int($id_zajezd);

        //ziskani seznamu z databaze	
        if ($typ_dotazu == "show") {
            $result = $this->database->query($this->create_query("count"));
            $this->count = mysqli_fetch_object($result)->count;            
            $this->data = $this->database->query($this->create_query("show"))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        }
    }

//------------------- METODY TRIDY -----------------	
    /* vytvoreni dotazu ze zadanych parametru */
    function create_query($typ_dotazu = "show") {
        if ($typ_dotazu == "show") {
            $today = date("Y-m-d");
            $dotaz = "SELECT * 
                        FROM zajezd_blackdays
                        WHERE id_zajezd = $this->id_zajezd AND do >= '$today'
                        ORDER BY od, do, id_zajezd
                    ";
            return $dotaz;
        } else if ($typ_dotazu == "count") {
            $dotaz = "SELECT COUNT(id_blackdays) AS count
                        FROM zajezd_blackdays
                        WHERE id_zajezd = $this->id_zajezd AND do >= '$today'                    
                    ";
            return $dotaz;
        }
    }

    /* ma zajezd black days nebo ne? */

    function isEmpty() {
        if($this->count > 0)
            return false;
        return true;
    }

    /* zobrazi nadpis seznamu */

    function show_header() {
        if ($this->moznosti_editace == "select_zajezd_objednavky") {
            $vystup = "
				<h3>Vyberte zájezd objednávky</h3>
			";
        } else {
            $vystup = "
				<h3>Seznam zájezdù seriálu</h3>
			";
        }
        return $vystup;
    }

    /* zobrazi hlavicku k seznamu zájezdù */

    function show_list_header($typ_zobrazeni = "") {
        if (!$this->get_error_message()) {
            $vystup = " <table class='list' id='table_blackdays'>
                            <tr>
                                <th>Id</th><th>Od</th><th>Do</th><th>Možnosti editace</th>
                            </tr>
		";
            return $vystup;
        }
    }

    /* zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni */

    function show_list() {
//        echo "  <button id='button' style='margin-top: 10px; align: center;'>vyprodané termíny</button>";        
        echo "<div class='akce' style='margin-top:10px;'>";
        echo "  <h3 id='h3-black-days' style='cursor: pointer;'>Vyprodané termíny</h3>";
        echo "  <script>
                $('#h3-black-days').click(function () {
                    $('#div-black-days').toggle('slow');
                });
                </script>";
        echo "  <div id='div-black-days'>";
        
        while ($this->get_next_radek()) {
            echo $this->change_date_en_cz($this->get_od()) . " - " .  $this->change_date_en_cz($this->get_do()) . "<br/>";
        }

        echo "</div></div>";
    }

    function get_id_blackdays() {
        return $this->radek["id_blackdays"];
    }

    function get_od() {
        $od = $this->radek["od"];
        //$od = substr($od, 0, strlen($od) - 9);
        return $od;
    }

    function get_do() {
        $do = $this->radek["do"];
        //$do = substr($do, 0, strlen($do) - 9);
        return $do;
    }

}

?>
