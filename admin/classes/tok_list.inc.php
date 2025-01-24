<?php

/**
 * zajezd_list.inc.php - trida pro zobrazeni seznamu zajezdu daneho serialu
 */
class Tok_list extends Generic_list {

    //vstupni data
    protected $id_objektu;
    protected $moznosti_editace;
    public $database; //trida pro odesilani dotazu
    private $yearLineShowed;

//------------------- KONSTRUKTOR  -----------------	

    function __construct($id_objektu, $moznosti_editace = "", $typ_dotazu = "show") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->yearLineShowed = false;
        //kontrola vstupnich dat
        $this->moznosti_editace = $this->check($moznosti_editace);
        $this->id_objektu = $this->check_int($id_objektu);
        $this->typ_dotazu = $this->check($typ_dotazu);
        //ziskani seznamu z databaze	
        if ($this->legal()) {
            if ($typ_dotazu == "show") {
                $this->data = $this->database->query($this->create_query())
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            } else if ($typ_dotazu == "show_no_link") {
                $this->data = $this->database->query($this->create_query("show_no_link"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            } else if ($typ_dotazu == "zajezdy-slevy") {
                $this->data = $this->database->query($this->create_query("serialy-slevy"))
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                if (mysqli_num_rows($this->data) == 0) {
                    $this->chyba("Pro zadané podmínky neexistuje žádný zájezd!");
                }
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------	
    /* vytvoreni dotazu ze zadanych parametru */
    function create_query($typ_dotazu = "show") {
        if ($typ_dotazu == "show") {
            $dotaz = "select distinct `typ_objektu`,`objekt_kategorie_termin`.`id_objektu`,`id_termin`,`datetime_od`,`datetime_do`,`nazev_tok` 
                    from `objekt_kategorie_termin` join objekt on (`objekt_kategorie_termin`.`id_objektu` = `objekt`.`id_objektu`)
                    where `objekt_kategorie_termin`.`id_objektu`=" . $this->id_objektu . "
                    order by `datetime_od` desc,`datetime_do` desc
                    
                    ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_dotazu == "show_no_link") {
            $dotaz = "select distinct `typ_objektu`,`objekt_kategorie_termin`.`id_objektu`,`objekt_kategorie_termin`.`id_termin`,`datetime_od`,`datetime_do`,`nazev_tok` 
                    from `objekt_kategorie_termin` join objekt on (`objekt_kategorie_termin`.`id_objektu` = `objekt`.`id_objektu`)
                    left join (
                        cena_zajezd_tok join cena_zajezd on (cena_zajezd_tok.id_cena =cena_zajezd.id_cena and cena_zajezd_tok.id_zajezd =cena_zajezd.id_zajezd))
                    on (`objekt_kategorie_termin`.`id_termin` = `cena_zajezd_tok`.`id_termin`
                         and `objekt_kategorie_termin`.`id_objekt_kategorie` = `cena_zajezd_tok`.`id_objekt_kategorie`
                    )
                    where `objekt_kategorie_termin`.`id_objektu`=" . $this->id_objektu . " and `cena_zajezd`.`id_cena` is NULL
                    order by `datetime_od` desc,`datetime_do` desc
                    
                    ";
            //echo $dotaz;
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
        if ($this->moznosti_editace == "select_zajezd_objednavky") {
            $vystup = "
				<h3>Vyberte zájezd objednávky</h3>
			";
        } else {
            $vystup = "
				<h3>Seznam termínù objektových kategorií</h3>
			";
        }
        return $vystup;
    }

    /* zobrazi hlavicku k seznamu zájezdù */

    function show_list_header($typ_zobrazeni="") {
        if (!$this->get_error_message()) {
            
                $vystup = "
				<table class=\"list\">
					<tr>
						<th>ID</th>
                                                <th>Od</th>
						<th>Do</th>
                                                <th>Název</th>
						<th>Možnosti editace	</th>
					</tr>
                                        
		";
            
            return $vystup;
        }
    }

    /* zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni */

    function show_list_item($typ_zobrazeni) {       
        if ($typ_zobrazeni == "tabulka" or $typ_zobrazeni == "tabulka_slevy" ) {
            if(!$this->yearLineShowed and $this->get_od() < Date("Y-m-d")){
                
            }
            
            if ($this->suda == 1) {
                $vypis = "<tr class=\"suda\">";
            } else {
                $vypis = "<tr class=\"licha\">";                
            }
            if(!$this->yearLineShowed and $this->get_od() < Date("Y-m-d")){
                $this->yearLineShowed = true;
                $vypis .= "<td colspan=\"5\" style=\"height:3px;background-color:red;\"></td>".$vypis;
            }
            if(Serial_library::get_typ_objektu($this->radek["typ_objektu"])=="Vstupenka"){
           //      $create_serial = "| <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;id_termin=".$this->radek["id_termin"]."&amp;typ=create_zajezd&amp;pozadavek=create_from_vstupenka\">vytvoøit zájezd</a>";
            }else{
           //      $create_serial = "";
            }
            
            $checkbox = "<input type=\"checkbox\" value=\"1\" name=\"checkbox_" . $this->get_id_termin() . "\" />";

            if($this->typ_dotazu == "show_no_link"){
                $checkbox = "<input type=\"checkbox\" value=\"1\" name=\"checkbox_" . $this->get_id_termin() . "\" checked=\"checked\"/>";
            }
            $vypis = $vypis . "
                                                        <td class=\"id\">" . $checkbox. $this->get_id_termin() . "</td>
							<td class=\"od\">" . $this->change_date_en_cz($this->get_od()) . "</td>
							<td class=\"do\">" . $this->change_date_en_cz($this->get_do()) . "</td>
                                                        <td class=\"do\">" . $this->get_nazev_tok() . "</td>    
							<td class=\"menu\">";

            //z jadra ziskame informace o soucasnem modulu
            $core = Core::get_instance();
            $current_modul = $core->show_current_modul();
            $adresa_modulu = $current_modul["adresa_modulu"];
            
            
            //$zamestnanec = User_zamestnanec::get_instance();
           // $serial = new Serial("show",$zamestnanec->get_id() , $this->get_id_objektu());
           // $zajezd_zobrazit =" | <a href=\"/zajezdy/zobrazit/".$serial->get_nazev_web()."/".$this->get_id_termin()."\">zobrazit na webu</a>";

                $vypis = $vypis . " <a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_termin=" . $this->get_id_termin() . "&amp;typ=tok&amp;pozadavek=show\">zobrazit</a>";
                $vypis = $vypis . "	| <a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_termin=" . $this->get_id_termin() . "&amp;typ=tok&amp;pozadavek=edit\">EDITOVAT</a>";
                $vypis = $vypis . $create_serial;
                $vypis = $vypis . "	| <a href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_termin=" . $this->get_id_termin() . "&amp;typ=tok&amp;pozadavek=copy\">kopírovat termín</a>";                
                $vypis = $vypis . "	| <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_objektu=" . $this->get_id_objektu() . "&amp;id_termin=" . $this->get_id_termin() . "&amp;typ=tok&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>	";
            
            $vypis = $vypis . "			
							</td>
						</tr>";

            return $vypis;
        }
    }

    /* metody pro pristup k parametrum */

    function get_id_objektu() {
        return $this->radek["id_objektu"];
    }

    function get_id_termin() {
        return $this->radek["id_termin"];
    }

    function get_od() {
        return $this->radek["datetime_od"];
    }

    function get_do() {
        return $this->radek["datetime_do"];
    }

    function get_nazev_tok() {
        return $this->radek["nazev_tok"];
    }
}

?>
