<?php

class ProdejniMista_list extends Generic_list {

    //vstupni data
    protected $nazev;
    protected $ulice;
    protected $mesto;
    protected $psc;
    protected $telefon;
    protected $order_by;
    public $database;

    function __construct($nazev, $ulice, $mesto, $psc, $telefon, $order_by, $typ_pozadavku) {
        $this->database = Database::get_instance();

        $this->nazev = $this->check($nazev);
        $this->ulice = $this->check($ulice);
        $this->mesto = $this->check($mesto);
        $this->psc = $this->check($psc);
        $this->telefon = $this->check($telefon);
        $this->order_by = $this->check($order_by);
        $this->zobrazeni = $this->check($pozadavek);

        //ziskani seznamu z databaze	
        $this->data = $this->database->query($this->create_query("show"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        //zjistuju, zda mam neco k zobrazeni
        if (mysqli_num_rows($this->data) == 0) {
            $this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
        }
    }

    function create_query($typ_pozadavku) {
        if ($typ_pozadavku == "show") {
            if ($this->nazev != "") {
                $where_nazev = " `nazev` like '%" . $this->nazev . "%' and";
            } else {
                $where_nazev = " ";
            }
            if ($this->ulice != "") {
                $where_ulice = " `ulice` like '%" . $this->ulice . "%' and";
            } else {
                $where_ulice = " ";
            }
            if ($this->mesto != "") {
                $where_mesto = " `mesto` like '%" . $this->mesto . "%' and";
            } else {
                $where_mesto = " ";
            }
            if ($this->psc != "") {
                $where_psc = " `psc` like '%" . $this->psc . "%' and";
            } else {
                $where_psc = " ";
            }
            if ($this->telefon != "") {
                $where_telefon = " `telefon` like '%" . $this->telefon . "%' and";
            } else {
                $where_telefon = " ";
            }

            if ($this->order_by != "") {
                $order_by = $this->order_by($this->order_by);
            } else {
                $order_by = " `prodejny`.`mesto`";
            }

            $dotaz = "  SELECT * 
                        FROM  prodejny
			WHERE $where_nazev $where_ulice $where_mesto $where_psc $where_telefon 1 
			ORDER BY $order_by";
//            echo $dotaz;
            return $dotaz;
        }
    }

    function order_by($vstup) {
        switch ($vstup) {
            case "id_up":
                return "`id_prodejny` ";
                break;
            case "id_down":
                return "`id_prodejny` desc";
                break;
            case "nazev_up":
                return "`nazev`";
                break;
            case "nazev_down":
                return "`nazev` desc";
                break;
            case "ulice_up":
                return "`ulice`";
                break;
            case "ulice_down":
                return "`ulice` desc";
                break;
            case "mesto_up":
                return "`mesto`";
                break;
            case "mesto_down":
                return "`mesto` desc";
                break;
        }
        return "`id_prodejny`"; 
    }

    function show_filtr() {
        //tvroba input nazev
        $input_nazev = "<input size=\"14\" name=\"nazev\" type=\"text\" value=\"" . $this->nazev . "\" />";
        $input_ulice = "<input size=\"14\" name=\"ulice\" type=\"text\" value=\"" . $this->ulice . "\" />";
        $input_mesto = "<input size=\"14\" name=\"mesto\" type=\"text\" value=\"" . $this->mesto . "\" />";
        $input_psc = "<input size=\"5\" name=\"psc\" type=\"text\" value=\"" . $this->psc . "\" />";
        $input_telefon = "<input size=\"14\" name=\"telefon\" type=\"text\" value=\"" . $this->telefon . "\" />";
        //tlacitko pro odeslani
        $submit = "<input type=\"submit\" value=\"Zmìnit filtrování\" />";

        //vysledny formular
        $vystup = " <form method=\"post\" action=\"\" style=\"margin: 10px;\">
                        <table>
                                <tr>
                                        <td>Název: $input_nazev</td>
                                        <td>Ulice: $input_ulice</td>
                                        <td>Mìsto: $input_mesto</td>
                                        <td>PSÈ: $input_psc</td>
                                        <td>Telefon: $input_telefon</td>
                                        <td valign=\"bottom\">$submit</td>
                                </tr>
                        </table>
                    </form>";
        return $vystup;
    }

    function show_list_header() {
        $vystup = " <table style=\"background-color: #F9E3B3;margin: 10px; border:black solid .1em;\" rules=\"all\">
                        <tr style=\"background-color: #E8BE53;border-top-left-radius: 5px;border-top-left-radius: 5px;\">
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Název</th>
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Ulice</th>						
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Mìsto</th>
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\" width=\"50px\">PSÈ</th>
                            <th align=\"left\" style=\"padding: 2px 4px 2px;\">Telefon</th>                                
                        </tr>";
//        $vystup = " <table>
//                        <tr>
//                            <th>Název
//                                <a href=\"?typ=prodejni_mista_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up" . $serial . "\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>
//                            <th>Ulice
//                                <a href=\"\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>						
//                            <th>Mìsto
//                                <a href=\"\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>
//                            <th width=\"40px\">PSÈ
//                                <a href=\"\"><img src=\"img/up.gif\" alt=\"seøadit vzestupnì\" title=\"seøadit vzestupnì\" /></a>
//                                <a href=\"\"><img src=\"img/down.gif\" alt=\"seøadit sestupnì\" title=\"seøadit sestupnì\" /></a>
//                            </th>
//                            <th>Telefon</th>                                
//                        </tr>";
        return $vystup;
    }
    
    function show_list_item($typ_zobrazeni = "") {
        $vypis = "";
        if ($typ_zobrazeni == "") {
            $vypis .= " <tr>";
            $vypis .= "     <td style=\"padding: 2px 4px 2px;\">" . $this->get_nazev() . "</td>
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_ulice() . "</td>									
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_mesto() . "</td>
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_psc() . "</td>
                            <td style=\"padding: 2px 4px 2px;\">" . $this->get_telefon() . "</td>";
            $vypis .= " </tr>";
        }
        return $vypis;
    }

    function show_list_footer() {
        return "</table>";
    }
    
    function get_nazev() {
        return $this->radek["nazev"];
    }

    function get_ulice() {
        return $this->radek["ulice"];
    }

    function get_mesto() {
        return $this->radek["mesto"];
    }

    function get_psc() {
        return $this->radek["psc"];
    }

    function get_telefon() {
        return $this->radek["telefon"];
    }

}

?>
