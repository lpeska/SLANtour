<?php

/**
 * zajezd_list.inc.php - trida pro zobrazeni seznamu zajezdu daneho serialu
 */
class Zajezd_list extends Generic_list {

    //vstupni data
    protected $id_serial;
    protected $moznosti_editace;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR  -----------------	

    function __construct($id_serial, $moznosti_editace = "", $typ_dotazu = "show") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->moznosti_editace = $this->check($moznosti_editace);
        $this->id_serial = $this->check_int($id_serial);

        //ziskani seznamu z databaze	
        if ($this->legal()) {
            if ($typ_dotazu == "show") {
                $this->data = $this->database->query($this->create_query())
                        or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            } else if ($typ_dotazu == "show-last") {
                $this->data = $this->database->query($this->create_query("show-last"))
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
            $dotaz = "select `zajezd`.* , 
                        count(`objednavka`.`id_objednavka`) as `pocet_objednavek`,
                        sum(`objednavka`.`pocet_osob`) as `pocet_osob`,
                        group_concat(CONCAT(`objednavka`.`pocet_osob`, ',' , `objednavka`.`stav`) separator ';' ) as `objednavka_pocty`,
                        `cena_zajezd`.`nezobrazovat` as cena_nezobrazovat, `cena_zajezd`.`vyprodano`  as cena_vyprodano
                        
                    from `zajezd`
                    left join  `objednavka` on ( `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                    
                    left join `cena` on (`cena`.`id_serial` = `zajezd`.`id_serial` and `cena`.`zakladni_cena`=1) 
                    left join `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`)
                    where `zajezd`.`id_serial`=" . $this->id_serial . "
                    group by `zajezd`.`id_zajezd`
                    order by `od` asc,`do` asc,`id_zajezd` asc
                    ";
            return $dotaz;
        } else if ($typ_dotazu == "show-last") {
            $dotaz = "select * 
                    from `zajezd`
                    where `zajezd`.`id_serial`=" . $this->id_serial . "
                    order by `od` desc,`do` desc,`id_zajezd` desc
                    limit 1
                    ";
            return $dotaz;
        }else if($typ_dotazu == "analyze_tok"){
            $dotaz="select  count(distinct `objekt_kategorie_termin`.`id_termin`) as pocet_tok , min(`objekt_kategorie_termin`.`datetime_do`) as tok_do, max(`objekt_kategorie_termin`.`datetime_od`) as tok_od, od, do
                              from `cena_zajezd` join `cena_zajezd_tok` on (`cena_zajezd`.`id_cena` = `cena_zajezd_tok`.`id_cena`
                                                                             and `cena_zajezd`.`id_zajezd` = `cena_zajezd_tok`.`id_zajezd`)
                                                 join  `zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)                          
                                                 join `objekt_kategorie_termin` on (`objekt_kategorie_termin`.`id_termin` = `cena_zajezd_tok`.`id_termin`
                                                                             and `objekt_kategorie_termin`.`id_objekt_kategorie` = `cena_zajezd_tok`.`id_objekt_kategorie`)                            
                            where `cena_zajezd`.`id_zajezd`= ".$this->get_id_zajezd()." 
                            group by `objekt_kategorie_termin`.`id_objekt_kategorie`
                            ";
            //echo $dotaz;
            return $dotaz;              
        } else {
            $select = "z.*, `serial`.`nazev`,
                group_concat(DISTINCT `objekt`.`nazev_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `nazev_ubytovani`,
                count(`objednavka`.`id_objednavka`) as `pocet_objednavek`,
                sum(`objednavka`.`pocet_osob`) as `pocet_osob`,
                group_concat(CONCAT(`objednavka`.`pocet_osob`, ',' , `objednavka`.`stav`) separator ';' ) as `objednavka_pocty`";
            $id_slevy = $_GET["id_slevy"];
            $dotaz = "SELECT $select
                    FROM `slevy_zajezd` `sz`
                    JOIN `zajezd` `z` ON `sz`.`id_zajezd` = `z`.`id_zajezd`
                    join `serial` on `serial`.`id_serial` = `z`.`id_serial`
                                left join (`objekt_serial` join
                                `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial` and objekt.typ_objektu = 1)                              
                    left join  `objednavka` on ( `objednavka`.`id_zajezd` = `z`.`id_zajezd` )            
                    WHERE sz.id_slevy = $id_slevy
                        group by z.id_zajezd 
                    ORDER BY `nazev_ubytovani`, `serial`.`nazev`, `od`,`do`,`id_zajezd`;";
           // echo $dotaz;
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
//        $vystup = "<form method='post' action=\"?id_serial=$this->id_serial&typ=zajezd&pozadavek=mass-delete\">";
        $vystup = "<table class=\"list\">";
        if ($this->moznosti_editace == "select_zajezd_objednavky") {
            $vystup .= "<h3>Vyberte zájezd objednávky</h3>";
        } else {
            $vystup .= "<h3>Seznam zájezdù seriálu</h3>";
        }
        return $vystup;
    }

    public function show_footer()
    {
        $vystup = "";

        $vystup .= "<tr>
                        <td colspan='10'>
                        <input type='submit' class='action-delete' value='smazat oznaèené' id='delete-selected'/> 
                        <input type='submit' class='action-warning' value='Oznaèit vybrané jako vyprodané' id='soldout-selected'/></td>
                    </tr>
                </table>";

        return $vystup;
    }

    /* zobrazi hlavicku k seznamu zájezdù */

    function show_list_header($typ_zobrazeni="") {
        if (!$this->get_error_message()) {
            if($typ_zobrazeni == "tabulka_slevy"){
                $vystup = "
				<table class=\"list\">
   					<tr>
                        <th>Seriál</th>
			<th width=\"50\">ID</th>
                        <th width=\"80\">Odjezd</th>
						<th width=\"80\" >Pøíjezd</th>
                        
						<th>Název</th>
                        <th width=\"150\">Pøihlášené osoby</th>
                        <th width=\"150\">Objednávky</th>
                        <th>Akce</th>
			<th>Možnosti editace</th>
					</tr>
		";
            }else{
                $vystup = "
                    <table class=\"list\">
                    <colgroup><col width='2%'><col width='4%'><col width='5%'><col width='5%'><col width='8%'><col width='8%'><col width='4%'><col width='12%'><col width='12%'><col width='9%'><col width='31%'></colgroup>
					<tr>
						<th></th>
						<th>ID</th>
                        <th>Odjezd</th>
						<th>Pøíjezd</th>
                        <th>Pøíznaky</th>
                        <th>Název</th>
						<th>Hit zájezd</th>
                        <th>Pøihlášené osoby</th>
                        <th>Objednávky</th>
                        <th>Akce</th>
						<th>Možnosti editace</th>
					</tr>
		";
            }
            return $vystup;
        }
    }

    /* zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni */

    function show_list_item($typ_zobrazeni) {
        if ($typ_zobrazeni == "tabulka" or $typ_zobrazeni == "tabulka_slevy" or $typ_zobrazeni == "tabulka_change_zajezd") {
            $pocty_osob = array();
            $pocty_objednavek = array();

            //vyresime pocty objednavek
            $objednavka_list  = explode(";",$this->radek["objednavka_pocty"]);
            foreach ($objednavka_list as $objed) {
                $objednavka = explode(",", $objed);
                $pocty_osob[$objednavka[1]] += (int)$objednavka[0];
                $pocty_objednavek[$objednavka[1]] ++;
            }

            $pocty_osob[3] = $pocty_osob[1]+$pocty_osob[2]+$pocty_osob[3];
            $pocty_objednavek[3] = $pocty_objednavek[1]+$pocty_objednavek[2]+$pocty_objednavek[3];
            $pocty_osob[6] = $pocty_osob[6]+$pocty_osob[7];
            $pocty_objednavek[6] = $pocty_objednavek[6]+$pocty_objednavek[7];
            $pocty_osob[8] = $pocty_osob[8]+$pocty_osob[9];
            $pocty_objednavek[8] = $pocty_objednavek[8]+$pocty_objednavek[9];
            $radek_pocet_osob = "<strong>".(intval($this->radek["pocet_osob"])-intval($pocty_osob[8])-intval($pocty_osob[10]))."</strong>
                <span title=\"opce\" class='osoby stav-opce'> ".intval($pocty_osob[3])."
                    </span><span title=\"rezervace\" class='osoby stav-rez'> ".intval($pocty_osob[4])."
                        </span><span title=\"záloha\" class='osoby stav-zal'> ".intval($pocty_osob[5])."
                            </span><span title=\"prodáno\" class='osoby stav-prodano'> ".intval($pocty_osob[6])."
                                </span><span title=\"storno\" class='osoby stav-storno'> ".intval($pocty_osob[8])."
                                </span><span title=\"voucher\" class='osoby stav-voucher'> ".intval($pocty_osob[10])." </span>";
            $radek_objednavka = "<strong>".(intval($this->radek["pocet_objednavek"])-intval($pocty_objednavek[8])-intval($pocty_objednavek[10]))."</strong>
                <span title=\"opce\" class='osoby stav-opce'> ".intval($pocty_objednavek[3])."
                    </span><span title=\"rezervace\" class='osoby stav-rez'> ".intval($pocty_objednavek[4])."
                        </span><span title=\"záloha\" class='osoby stav-zal'> ".intval($pocty_objednavek[5])."
                            </span><span title=\"prodáno\" class='osoby stav-prodano'> ".intval($pocty_objednavek[6])."
                                </span><span title=\"storno\" class='osoby stav-storno'> ".intval($pocty_objednavek[8])."
                                </span><span title=\"voucher\" class='osoby stav-voucher'> ".intval($pocty_objednavek[10])." </span>";

            $vypis = "<tr class='selectable'>";
            if ($this->get_hit_zajezd()) {
                $hit = "<span class=\"green\">ANO</span>";
            } else {
                $hit = "<span class=\"red\">NE</span>";
            }
            if ($this->radek["akcni_cena"] > 0) {
                $akce = "<span class=\"green\">ANO, " . $this->radek["akcni_cena"] . " Kè</span>";
            } else {
                $akce = "<span class=\"red\">NE</span>";
            }
            
            if($typ_zobrazeni == "tabulka"){
               $priznak = "";
               if($this->radek["cena_vyprodano"]){
               $priznak.="<b style='color:red'>VYPRODÁNO</b><br/>";
               } 
               if($this->radek["cena_nezobrazovat"]){
               $priznak.="<b style='color:orange'>cena: nezobrazovat</b><br/>";
               }
               if($this->radek["nezobrazovat_zajezd"]){
               $priznak.="<b style='color:orange'>zajezd: nezobrazovat</b><br/>";
               }
            }
            
            if($typ_zobrazeni == "tabulka_slevy"){
               $vypis = $vypis . "<td>" . $this->radek["nazev"] . ", " . $this->radek["nazev_ubytovani"] . "</td>";
            }
            if($typ_zobrazeni == "tabulka" or $typ_zobrazeni == "tabulka_change_zajezd" ){
                $nazev = $this->get_nazev_zajezdu();
            }
            if($typ_zobrazeni == "tabulka_change_zajezd"){
                $vypis = $vypis . "<td></td>";
            }else if($typ_zobrazeni != "tabulka_slevy"){
                $vypis = $vypis . "<td><input type='checkbox' name='zajezd_delete_ids' value='".$this->get_id_zajezd()."'/></td>";
            }   
            if($typ_zobrazeni != "tabulka_slevy"){
                $hit_text = "<td class=\"hit_zajezd\">" . $hit ."</td>";
            }else{
                $hit_text =  "";
            }
            
            $vypis = $vypis ."  <td class=\"id\">" . $this->get_id_zajezd() . "</td>
				<td class=\"od\">" . $this->change_date_en_cz($this->get_od()) . "</td>
				<td class=\"do\">" . $this->change_date_en_cz($this->get_do()) . "</td> "  ;
                
            if($typ_zobrazeni == "tabulka"){
                $vypis = $vypis ."  <td class=\"hit_zajezd\">" . $priznak . "</td> ";
            }    
                
			$vypis = $vypis ."<td class=\"hit_zajezd\">" . $this->get_nazev_zajezdu() ."</td>
				$hit_text
                            <td class=\"hit_zajezd\">".$radek_pocet_osob."</td>
                            <td class=\"hit_zajezd\">".$radek_objednavka."</td>
                            <td class=\"hit_zajezd\">" . $akce . "</td>
							<td class=\"menu\">";

            //z jadra ziskame informace o soucasnem modulu
            $core = Core::get_instance();
            $current_modul = $core->show_current_modul();

            if($typ_zobrazeni == "tabulka_slevy"){
                $adresa_modulu = "serial.php";

            }else{
                $adresa_modulu = $current_modul["adresa_modulu"];
            }

            //$zamestnanec = User_zamestnanec::get_instance();
           // $serial = new Serial("show",$zamestnanec->get_id() , $this->get_id_serial());
           // $zajezd_zobrazit =" | <a href=\"/zajezdy/zobrazit/".$serial->get_nazev_web()."/".$this->get_id_zajezd()."\">zobrazit na webu</a>";

            if ($this->moznosti_editace == "select_zajezd_objednavky") {
                if ($adresa_objednavky = $core->get_adress_modul_from_typ("objednavky")) {
                    $vypis = $vypis . "<a href=\"" . $adresa_objednavky . "?typ=rezervace&amp;pozadavek=new&amp;id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "\">vybrat zájezd</a>";
                }
            } else if ($this->moznosti_editace == "objednavka_change_zajezd") {
                if ($adresa_objednavky = $core->get_adress_modul_from_typ("objednavky")) {
                    if($_REQUEST["storno_poplatek_zmena"] == 0){
                        $_REQUEST["storno_poplatek_zmena"] = $this->check_int($_REQUEST["storno"]);
                    }
                    $vypis = $vypis . "<a href=\"" . $adresa_objednavky . "?typ=rezervace&amp;pozadavek=change_zajezd&amp;id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;id_objednavka=" . $_GET["id_objednavka"] . "&amp;id_klient=" . $_REQUEST["id_klient"] . "&amp;storno=" . $_REQUEST["storno_poplatek_zmena"] . "\">zmìnit za tento zájezd</a>";
                }
            } else {
                
                    $showhide_start = " |  <span id=\"zajezd_" . $this->get_id_zajezd() . "\" style=\"display:none;\">";
                    $showhide_end = "</span><a href=\"#\" id=\"zajezd_".$this->get_id_zajezd()."_showhide\" onclick=\"showDetailActions('zajezd_".$this->get_id_zajezd()."');return false;\">další &gt;&gt;</a>";


                $vypis = $vypis . " <a href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=zajezd&amp;pozadavek=edit\">zájezd</a>
					 		| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=cena_zajezd\">ceny zájezdu</a>
					 		| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=slevy_zajezd\">slevy</a>
                                                        | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=topologie&amp;pozadavek=show\">topologie</a>   
  
                                                        ".$zajezd_zobrazit;
                if ($adresa_objednavky = $core->get_adress_modul_from_typ("objednavky")) {
                    $vypis = $vypis . " | <a href=\"" . $adresa_objednavky . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=rezervace_list&amp;filter=clear\">objednávky</a>";
                }
                if ($adresa_objednavky = $core->get_adress_modul_from_typ("objednavky")) {
                    $vypis = $vypis . " | <a href=\"https://slantour.cz/admin/rezervace.php?typ=rezervace&pozadavek=new-objednavka&id_serial=" . $this->radek["id_serial"] . "&id_zajezd=" . $this->radek["id_zajezd"] . "\">nová objednávka</a>";
                }
                
                $errorTOK = "";
                $warningTOK = "";
                $countTOK = 0;
                $dataAnalyzeTok = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("analyze_tok"));
                while ($rowAnalyzeTok = mysqli_fetch_array($dataAnalyzeTok)) {
                    $countTOK++;
                    if($rowAnalyzeTok["pocet_tok"]>1){
                        $errorTOK = "K zájezdu jsou pøiøazeny 2 rùzné TOK od stejné OK, nelze kopárovat TOK";
                        break;
                    }
                    if(strtotime($rowAnalyzeTok["tok_od"])!=strtotime($rowAnalyzeTok["od"]) 
                            or strtotime($rowAnalyzeTok["tok_do"])!=strtotime($rowAnalyzeTok["do"])){
                        $warningTOK = "Termín zájezdu a termín TOK si neodpovídají, nedoporuèujeme zájezd kopírovat vè. TOK";
                    }                    
                }
                
                $vypis = $vypis . $showhide_start;
                $vypis = $vypis . "	| <span id=\"copy_zajezd_".$this->get_id_zajezd()."\"><a href=\"#\" onclick='return show_copy_zajezd_form(\"copy_zajezd_".$this->get_id_zajezd()."\"," . $this->get_id_zajezd() . "," . $this->get_id_serial() . ", \"" . $this->change_date_en_cz($this->get_od()) . "\", \"" . $this->change_date_en_cz($this->get_do()) . "\",\"$errorTOK\",\"$warningTOK\",$countTOK);'>kopírovat zájezd</a></span>";
                $vypis = $vypis . "	| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=blackdays&amp;pozadavek=show\">black days</a>";

                $vypis = $vypis . "	| <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=zajezd&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>	";
                $vypis = $vypis . "	| <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=zajezd&amp;pozadavek=delete_with_objednavky\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">smazat vèetnì objednávek</a>	";
                $vypis = $vypis . $showhide_end;
            }
            $vypis .= "</td></tr>";
//href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=zajezd&amp;pozadavek=copy\"
            return $vypis;
        } else if($typ_zobrazeni == "selector") {
            $vypis = "<option>".$this->get_id_zajezd(). " - ".$this->change_date_en_cz($this->get_od()).$this->change_date_en_cz($this->get_do())."</option>";
            return $vypis;
        }
    }

    public function printJson($oldZajezdy = false) {
        $out = "[";
        while ($this->get_next_radek()) {
            if($oldZajezdy || ($this->get_do() == '0000-00-00' || $this->get_do() >= date("Y-m-d")))
                $out .= "{\"id\": \"".$this->radek["id_zajezd"]."\", \"nazev\": \"".$this->change_date_en_cz($this->get_od())." - ".$this->change_date_en_cz($this->get_do())."\"},";
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        return $out;
    }

    /* metody pro pristup k parametrum */

    function get_id_serial() {
        return $this->radek["id_serial"];
    }

    function get_id_zajezd() {
        return $this->radek["id_zajezd"];
    }

    function get_od() {
        return $this->radek["od"];
    }

    function get_do() {
        return $this->radek["do"];
    }
    function get_nazev_zajezdu() {
        return $this->radek["nazev_zajezdu"];
    }
    function get_hit_zajezd() {
        return $this->radek["hit_zajezd"];
    }

}

?>
