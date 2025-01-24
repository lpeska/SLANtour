<?php

class SmluvniPodminky_list extends Generic_list {

    private $id_smluvni_podminky_nazev;
    public $database; //trida pro odesilani dotazu    

    function __construct($id_smluvni_podminky_nazev, $typ_dotazu = "show_nazev_list") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->id_smluvni_podminky_nazev = $this->check_int($id_smluvni_podminky_nazev);

        //ziskani seznamu z databaze	
        if ($this->legal()) {
            $this->data = $this->database->query($this->create_query($typ_dotazu))
                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

    function create_query($typ_dotazu = "show_nazev_list") {
        if ($typ_dotazu == "show_nazev_list") {
            $dotaz = "  SELECT sp.*, d.* 
                        FROM smluvni_podminky_nazev sp LEFT JOIN dokument d ON (sp.dokument_id = d.id_dokument) 
                        WHERE 1";
        } else if ($typ_dotazu == "show_list") {
            $dotaz = "  SELECT sp.*
                        FROM smluvni_podminky sp LEFT JOIN smluvni_podminky_nazev spn ON (sp.id_smluvni_podminky_nazev = spn.id_smluvni_podminky_nazev)
                        WHERE sp.id_smluvni_podminky_nazev = $this->id_smluvni_podminky_nazev && typ != 'storno'
                        ORDER BY prodleva DESC;";
        } else if ($typ_dotazu == "show_list_storno") {
            $dotaz = "  SELECT sp.*
                        FROM smluvni_podminky sp LEFT JOIN smluvni_podminky_nazev spn ON (sp.id_smluvni_podminky_nazev = spn.id_smluvni_podminky_nazev)
                        WHERE sp.id_smluvni_podminky_nazev = $this->id_smluvni_podminky_nazev && typ = 'storno'
                        ORDER BY prodleva DESC;";
        }
        return $dotaz;
    }

    function legal() {
        $zamestnanec = User_zamestnanec::get_instance();
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        return $zamestnanec->get_bool_prava($id_modul, "read");
    }

    function show_list_header($typ_zobrazeni = "show_nazev_list") {
        if ($typ_zobrazeni == "show_nazev_list") {
            $vystup = " <table class='list' id='smluvni_podminky_nazev'>
                        <tr>
                            <th>Id</th><th>Název</th><th>Dokument</th><th>Možnosti editace</th>
                        </tr>";
        } else if ($typ_zobrazeni == "show_list") {
            $vystup = " <table class='list' id='smluvni_podminky'>
                        <tr>
                           <th>Id</th><th>Èástka</th><th>Procenta</th><th>Poèet dní do odjezdu</th><th>Typ</th><th>Možnosti editace</th>
                        </tr>";
        } else if ($typ_zobrazeni == "show_list_storno") {
            $vystup = " <table class='list' id='smluvni_podminky_storno'>
                        <tr>
                           <th>Id</th><th>Èástka</th><th>Procenta</th><th>Poèet dní do odjezdu</th><th>Typ</th><th>Možnosti editace</th>
                        </tr>";
        }
        return $vystup;
    }

    /* zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni */

    function show_list($typ_zobrazeni = "show_nazev_list") {
        if ($typ_zobrazeni == "show_nazev_list") {
            $rowIndex = 0;
            echo "<script type='text/javascript'>var selectors = new Array();</script>";
            while ($this->get_next_radek()) {
                if ($this->suda == 1) {
                    $vypis .= "<tr class=\"suda\">";
                } else {
                    $vypis .= "<tr class=\"licha\">";
                }
                $vypis .= " <td class='id'>" . $this->get_id_smluvni_podminky_nazev() . "</td>
                        <td>" . $this->get_nazev() . "</td>
                        <td>" . $this->get_dokument_nazev() . "<input type='hidden' id='dokument_id' value='" . $this->get_dokument_id() . "' /></td>
                        <td class='edit'>
                            <form id='form_update_$rowIndex' accept-charset='windows-1250' action='' method='post' style='display: inline;'>
                                <script type='text/javascript'>
                                    selectors[$rowIndex] = \"" . $this->doc_select("document_id_$rowIndex", "add_dokument_id", "", $this->get_dokument_id()) . "\";
                                </script>
                                <input onclick='return edit_smluv_podm_nazev($rowIndex, " . $this->get_id_smluvni_podminky_nazev() . ", \"smluvni_podminky_nazev\", selectors[$rowIndex]);' id='btn_update_$rowIndex' type='submit' value='Upravit'/>
                            </form>
                            <form action='smluvni_podminky.php?typ=smluvni_podminky_list&smluvni_podminky_nazev_id=" . $this->get_id_smluvni_podminky_nazev() . "' method='post' style='display: inline;'>
                                <input type='submit' value='Upravit termíny'/>
                            </form>
                            <form action='smluvni_podminky.php?typ=smluvni_podminky_nazev&id_smluvni_podminky_nazev=" . $this->get_id_smluvni_podminky_nazev() . "&pozadavek=delete' method='post' style='display: inline;'>
                                <input class='action-delete' type='submit' value='Smazat'/>
                            </form>
                        </td>";
                $rowIndex++;
            }
            $vypis .= " <tr class='edit'>
                        <td></td>
                        <td><input id='add_nazev' name='add_nazev' class='important' type='text' size='8' value='' /></td>                        
                        <td>
                            " . $this->doc_select("add_dokument_id", "add_dokument_id", "important") . "
                        </td>
                        <td>                            
                            <script type='text/javascript'>
                                var nazvy = new Array('nazev', 'dokument_id');
                                var povinne = new Array(0,1);
                                var requestUrl = './smluvni_podminky.php/?&typ=smluvni_podminky_nazev&pozadavek=create';
                            </script>
                            <input type='submit' value='Pøidat' onclick='return request_create(nazvy, povinne, requestUrl);'/>                            
                        </td>
                    </tr></table>";
        } else if ($typ_zobrazeni == "show_list" || $typ_zobrazeni == "show_list_storno") {
            $rowIndex = 0;
            $tableId = $typ_zobrazeni == "show_list" ? "smluvni_podminky" : "smluvni_podminky_storno";            
            $formUpdateId = $typ_zobrazeni == "show_list" ? "form_update_" : "form_update_storno_";
            $btnUpdateId = $typ_zobrazeni == "show_list" ? "btn_update_" : "btn_update_storno_";
            while ($this->get_next_radek()) {
                if ($this->suda == 1) {
                    $vypis .= "<tr class=\"suda\">";
                } else {
                    $vypis .= "<tr class=\"licha\">";
                }
                $vypis .= " <td class='id'>" . $this->get_id_smluvni_podminky() . "</td>
                        <td>" . $this->get_castka() . "</td>
                        <td>" . $this->get_procento() . "</td>
                        <td>" . $this->get_prodleva() . "</td>
                        <td>" . $this->get_typ() . "</td>                        
                        <td class='edit'>
                            <form id='$formUpdateId$rowIndex' action='' method='post' style='display: inline;'>
                                <input onclick='edit_smluv_podm($rowIndex, " . $this->get_id_smluvni_podminky() . ", \"$tableId\", \"$formUpdateId\", \"$btnUpdateId\");' id='$btnUpdateId$rowIndex' type='submit' value='Upravit'/>
                            </form>
                            <form action='smluvni_podminky.php?typ=smluvni_podminky&smluvni_podminky_nazev_id=$this->id_smluvni_podminky_nazev&id_smluvni_podminky=" . $this->get_id_smluvni_podminky() . "&pozadavek=delete' method='post' style='display: inline;'>
                                <input class='action-delete' type='submit' value='Smazat'/>
                            </form>        
                        </td>";
                $rowIndex++;
            }
            if ($typ_zobrazeni == "show_list_storno") {
                $vypis .= " <tr class='edit'>
                        <td></td>
                        <td><input id='add_castka' name='add_castka' class='important' type='text' size='8' value='' /></td>
                        <td><input id='add_procento' name='add_procento' class='important' type='text' size='8' value='' /></td>
                        <td><input id='add_prodleva' name='add_prodleva' class='important' type='text' size='8' value='' /></td>
                        <td>
                            <select class='important' id='add_typ' name='add_typ'>                                
                                <option value='záloha'>záloha</option>
                                <option value='doplatek'>doplatek</option>
                                <option value='storno'>storno</option>
                            </select>
                        </td>                        
                        <td>                                                         
                            <script type='text/javascript'>
                                var nazvy = new Array('castka','procento','prodleva','typ');
                                var povinne = new Array(0,0,1,1);
                                var requestUrl = './smluvni_podminky.php?&typ=smluvni_podminky&pozadavek=create&id_smluvni_podminky_nazev=$this->id_smluvni_podminky_nazev';
                            </script>
                            <input type='submit' value='Pøidat' onclick='return request_create(nazvy, povinne, requestUrl);'/>                        
                        </td>
                    </tr>";
            }
            $vypis .= "</table>";
        }

        return $vypis;
    }

    function get_id_smluvni_podminky() {
        return $this->radek["id_smluvni_podminky"];
    }

    function get_castka() {
        return $this->radek["castka"];
    }

    function get_procento() {
        return $this->radek["procento"];
    }

    function get_prodleva() {
        return $this->radek["prodleva"];
    }

    function get_typ() {
        return $this->radek["typ"];
    }

    function get_dokument_id() {
        return $this->radek["id_dokument"];
    }

    function get_dokument_nazev() {
        return $this->radek["nazev_dokument"];
    }

    function get_id_smluvni_podminky_nazev() {
        return $this->radek["id_smluvni_podminky_nazev"];
    }

    function get_nazev() {
        if (is_null($this->radek)) {
            $data = $this->database->query("SELECT nazev FROM smluvni_podminky_nazev WHERE id_smluvni_podminky_nazev = $this->id_smluvni_podminky_nazev;");
            $row = mysqli_fetch_array($data);
            return $row["nazev"];
        } else {
            return $this->radek["nazev"];
        }
    }

    function doc_select($id = "", $name = "", $classes = "", $selected = "") {
        $data = $this->database->query("SELECT * FROM dokument WHERE nazev_dokument LIKE '%smluvni%'");
        $selector = "<select class='$classes' id='$id' name='$name'>";
        while ($row = mysqli_fetch_object($data)) {
            $selector .= "<option value='$row->id_dokument' " . ($row->id_dokument == $selected ? "selected='selected'" : "") . ">$row->nazev_dokument</option>";
        }
        $selector .= "</select>";
        return $selector;
    }

}

?>
