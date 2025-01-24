<?php

/**
 * zajezd_list.inc.php - trida pro zobrazeni seznamu zajezdu daneho serialu
 */
class Blackdays_list extends Generic_list {

    //vstupni data
    protected $id_zajezd;
    protected $moznosti_editace;
    protected $zajezd;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR  -----------------	

    function __construct($id_zajezd, $moznosti_editace = "", $typ_dotazu = "show") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->moznosti_editace = $this->check($moznosti_editace);
        $this->id_zajezd = $this->check_int($id_zajezd);
        
        //info o zajezdu ke kteremu black days patri
        $this->zajezd = new Zajezd("show",$_GET["id_serial"],$_GET["id_zajezd"]);

        //ziskani seznamu z databaze	
        if ($this->legal()) {
            if ($typ_dotazu == "show") {
                $this->data = $this->database->query($this->create_query())
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	
    /* vytvoreni dotazu ze zadanych parametru */
    function create_query($typ_dotazu = "show") {
        if ($typ_dotazu == "show") {
            $dotaz = "SELECT * 
                        FROM zajezd_blackdays
                        WHERE id_zajezd = $this->id_zajezd
                        ORDER BY od, do, id_zajezd
                    ";
            return $dotaz;
        }
    }

    /* zjistim, zda mam opravneni k pozadovane akci */

    function legal() {
        $zamestnanec = User_zamestnanec::get_instance();
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        return $zamestnanec->get_bool_prava($id_modul, "read");
    }

    /* zobrazi nadpis seznamu */

    function show_header() {        
        $vystup = "<div class='submenu'>termín zájezdu: " . CommonUtils::czechDate($this->zajezd->get_od()) . " - " . CommonUtils::czechDate($this->zajezd->get_do()) . "</div>";
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

    function show_list($typ_zobrazeni = "") {
        $rowIndex = 0;
        echo "  <script src='/admin/js/jquery-ui-cze.min.js'></script>
                <link rel='stylesheet' href='/admin/css/jquery-ui.min.css' />";

        while ($this->get_next_radek()) {
            if ($this->suda == 1) {
                $vypis .= "<tr class=\"suda\">";
            } else {
                $vypis .= "<tr class=\"licha\">";
            }
            $vypis .= " <td class='id'>" . $this->get_id_blackdays() . "</td>
                        <td class='od'>" . CommonUtils::czechDate($this->get_od()) . "</td>
                        <td class='do'>" . CommonUtils::czechDate($this->get_do()) . "</td>
                        <td class='edit'>
                            <form id='blackdays_form_update_$rowIndex' action='' method='post'>
                                <input onclick='edit_blackdays($rowIndex, " . $this->get_id_blackdays() . ");' id='blackdays_upravit_$rowIndex' type='submit' value='Upravit'/>
                            </form>
                            <form action='serial.php?typ=blackdays&id_serial=" . $_GET["id_serial"] . "&id_zajezd=$this->id_zajezd&id_blackdays=" . $this->get_id_blackdays() . "&pozadavek=delete' method='post'>
                                <input class='action-delete' type='submit' value='Smazat'/>
                            </form>        
                        </td>";
            $rowIndex++;
        }
        $vypis .= " <tr class='edit'>
                        <td></td>
                        <td><input id='add_od' name='add_od' class='calendar-ymd important date' type='text' size='8' value='" . CommonUtils::czechDate($this->zajezd->get_od()) . "' /></td>
                        <td><input id='add_do' name='add_do' class='calendar-ymd important date' type='text' size='8' value='" . CommonUtils::czechDate($this->zajezd->get_do()) . "' /></td>
                        <td>
                            <form method='post' id='blackdays_form_create'>
                                <input type='hidden' id='hid_od_blackdays' name='od'>
                                <input type='hidden' id='hid_do_blackdays' name='do'>
                                <input type='hidden' id='hid_id_zajezd_blackdays' name='id_zajezd'>
                                <input type='submit' value='Pøidat' onclick='return request_blackdays_create($this->id_zajezd);'/>
                            </form>
                        </td>
                    </tr></table>";

        return $vypis;
    }

    function get_id_blackdays() {
        return $this->radek["id_blackdays"];
    }

    function get_od() {
        $od = $this->radek["od"];
      //  $od = substr($od, 0, strlen($od) - 9);
        return $od;
    }

    function get_do() {
        $do = $this->radek["do"];
       // $do = substr($do, 0, strlen($do) - 9);
        return $do;
    }

}

?>
