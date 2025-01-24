<?php
/**
* rezervace_platba_list.inc.php - tridy pro zobrazeni seznamu plateb dane rezervace
*/


/*------------------- SEZNAM pladeb rezervaci -------------------  */

class Rezervace_platba_list extends Generic_list{
	//vstupni data
	protected $id_objednavka;
	protected $order_by;

	public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR  -----------------
/**konstruktor tøídy*/
	function __construct($id_objednavka,$order_by=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

	//kontrola vstupnich dat
		$this->order_by = $this->check($order_by);
		$this->id_objednavka = $this->check_int($id_objednavka);

	//ziskani seznamu z databaze
		if($this->legal()){
			$this->data=$this->database->query($this->create_query())
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádná platba");
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
		}

	}
//------------------- METODY TRIDY -----------------
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query(){
		$order=$this->order_by($this->order_by);

		$dotaz= "select `objednavka`.`mena`, `objednavka`.`id_objednavka`,`objednavka_platba`.`id_platba`,`objednavka_platba`.`castka`,
						`objednavka_platba`.`typ_dokladu`,`objednavka_platba`.`cislo_dokladu`,`objednavka_platba`.`vystaveno`,`objednavka_platba`.`splatit_do`, `objednavka_platba`.`splaceno`, `objednavka_platba`.`zpusob_uhrady`
					from `objednavka_platba`
					join `objednavka` on ( `objednavka_platba`.`id_objednavka` = `objednavka`.`id_objednavka` )
					where `objednavka`.`id_objednavka`=".$this->id_objednavka."
					order by ".$order."
					";
		//echo $dotaz;
		return $dotaz;
	}
/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`objednavka_platba`.`id_platba`";
   			 break;
			case "id_down":
				 return "`objednavka_platba`.`id_platba` desc";
   			 break;
			case "castka_up":
				 return "`objednavka_platba`.`castka`";
   			 break;
			case "castka_down":
				 return "`objednavka_platba`.`castka` desc";
   			 break;
			case "vystaveno_up":
				 return "`objednavka_platba`.`vystaveno`";
   			 break;
			case "vystaveno_down":
				 return "`objednavka_platba`.`vystaveno` desc";
   			 break;
			case "splatit_do_up":
				 return "`objednavka_platba`.`splatit_do`";
   			 break;
			case "splatit_do_down":
				 return "`objednavka_platba`.`splatit_do` desc";
   			 break;
			case "splaceno_up":
				 return "`objednavka_platba`.`splaceno`";
   			 break;
			case "splaceno_down":
				 return "`objednavka_platba`.`splaceno` desc";
   			 break;
                     case "cislo_dokladu_up":
				 return "`objednavka_platba`.`cislo_dokladu`";
   			 break;
			case "cislo_dokladu_down":
				 return "`objednavka_platba`.`cislo_dokladu` desc";
   			 break;
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`objednavka_platba`.`vystaveno`";
	}

	/**zjistim, zda mam opravneni k pozadovane akci*/
	function legal(){
		$zamestnanec = User_zamestnanec::get_instance();
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();

		return $zamestnanec->get_bool_prava($id_modul,"read");
	}

	/**zobrazi nadpis seznamu*/
	function show_header(){
			$vystup="
				<h3>Seznam plateb k objednávce</h3>
			";
		return $vystup;
	}

	/**zobrazi hlavicku k seznamu*/
	function show_list_header($typ_zobrazeni = "tabulka"){
	 	if($typ_zobrazeni == "tabulka"){
		 $core = Core::get_instance();
	 	 if($adresa_objednavka = $core->get_adress_modul_from_typ("objednavky") and  !$this->get_error_message()){
			$vystup="
                            <span id=\"platby\">&nbsp;</span>
				<table class=\"list\">
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=id_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=id_down\"></a>
							</div>
						</th>
                                                <th>Èíslo dokladu
                                                <div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=cislo_dokladu_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=cislo_dokladu_down\"></a>
							</div>
						</th>
						<th>Èástka
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=castka_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=castka_down\"></a>
							</div>
						</th>
						<th>Vystaveno
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=vystaveno_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=vystaveno_down\"></a>
							</div>
						</th>
						<th>Splatnost do
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=splatit_do_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=splatit_do_down\"></a>
							</div>
						</th>
						<th>Splaceno
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=splaceno_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_platba_list&amp;id_objednavka=".$this->id_objednavka."&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_platba_order_by=splaceno_down\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
				";

			}
	 	}else if($typ_zobrazeni == "tabulka_zobrazit"){
			$vystup="
                            <input type='hidden' name=\"platby\" value='&nbsp;'/>
				<table class=\"list\">
					<tr>
						<th colspan=\"8\">Seznam plateb objednávky</th>
					<tr>
					<tr>
						<th>Id</th>
                                                <th>Èíslo dokladu</th>
						<th>Èástka</th>
						<th>Vystaveno</th>
						<th>Splatnost do</th>
						<th>Uhrazeno</th>
						<th>Zpùsob úhrady</th>
                                                <th>Možnosti editace</th>
					</tr>
			";
		}
		return $vystup;
	}
	/*zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
		if( !$this->get_error_message()){
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			$splaceno = $this->get_splaceno();
			if(intval($splaceno) == 0){$splaceno = "";}
			$vypis = $vypis."
							<td class=\"id\">".$this->get_id_platba()." (".$this->get_typ_dokladu().")</td>
                                                        <td class=\"cena\">".$this->get_cislo_dokladu()."</td>
							<td class=\"cena\">".$this->get_castka()." Kè</td>
							<td class=\"datum\">".$this->change_date_en_cz( $this->get_vystaveno() )."</td>
							<td class=\"datum\">".$this->change_date_en_cz( $this->get_splatit_do() )."</td>
							<td class=\"datum\">".$this->change_date_en_cz( $splaceno )."</td>
							<td class=\"menu\">
								  <a href=\"?id_objednavka=".$this->get_id_objednavka()."&amp;id_platba=".$this->get_id_platba()."&amp;typ=rezervace_platba&amp;pozadavek=edit\">editovat platbu</a>
								| <a href=\"?id_objednavka=".$this->get_id_objednavka()."&amp;id_platba=".$this->get_id_platba()."&amp;typ=rezervace_platba&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";

			return $vypis;
		}else if($typ_zobrazeni=="tabulka_zobrazit"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			$splaceno = $this->get_splaceno();
			if(intval($splaceno) == 0){$splaceno = "";}
			$vypis = $vypis."
                                    <td class=\"id\">" . $this->get_id_platba() . " (".$this->get_typ_dokladu().")</td>
                                    <td class=\"cena\"><div id='cislo_dokladu_" . $this->get_id_platba() . "'>" . $this->get_cislo_dokladu() . "</div></td>
                                    <td class=\"cena\">".$this->get_castka()." Kè</td>
                                    <td class=\"datum\">" . $this->change_date_en_cz($this->get_vystaveno()) . "</td>
                                    <td class=\"datum\"><div id='splatit_do_" . $this->get_id_platba() . "'/>" . $this->change_date_en_cz($this->get_splatit_do()) . "</div></td>
                                    <td class=\"datum\"><div id='splaceno_" . $this->get_id_platba() . "'>" . $this->change_date_en_cz($splaceno) . "</div></td>
                                    <td><div id='zpusob_uhrady_" . $this->get_id_platba() . "'>" . $this->get_zpusob_uhrady() . "</div></td>
                                    <td class=\"edit\">
                                        <form id=\"form_platba_" . $this->get_id_platba() . "\" method=\"post\" action=\"?id_objednavka=$this->id_objednavka&id_platba=" . $this->get_id_platba() . "&typ=rezervace_platba&pozadavek=update\" style=\"display: inline\">                                            
                                            <input type=\"submit\" id=\"platby_upravit_" . $this->get_id_platba() . "\" onclick=\"return edit_platba('"
                                                                                                . $this->id_objednavka . "', '" 
                                                                                                . $this->get_id_platba() . "', '" 
                                                                                                . $this->get_cislo_dokladu() . "', '"                                                                                                
                                                                                                . $this->change_date_en_cz($this->get_splatit_do()) . "', '"
                                                                                                . $this->change_date_en_cz($splaceno) . "', '"
                                                                                                . $this->get_zpusob_uhrady() . 
                                                                                                "'); return false;\" value='Upravit' />
                                        </form>
                                        <form method='post' action='?id_objednavka=" . $this->id_objednavka . "&id_platba=" . $this->get_id_platba() . "&typ=rezervace_platba&pozadavek=delete' style='display: inline'>
                                            <input type='submit' value='Smazat' onclick='javascript:return confirm(\'Opravdu chcete smazat objekt?\')' />
                                        </form>
                                    </td>
                            </tr>";
			return $vypis;                        
		}
		}// not error message
	}

        public function show_list_footer($typ_zobrazeni) {
            if($typ_zobrazeni == "tabulka_zobrazit") {
                $vypis = "<tr class=\"edit\">
                            <td class=\"id\"><input type=\"radio\" id=\"add_typ_dokladu_prijmovy\" name=\"add_typ_dokladu\" checked=\"checked\" value=\"prijmovy\" /> Pøíjmový doklad<br/>
                                            <input type=\"radio\" id=\"add_typ_dokladu_dokladu_vydajovy\" name=\"add_typ_dokladu\" value=\"vydajovy\" /> Výdajový doklad</td>
                            <td class=\"cena\"><input type=\"input\" id=\"add_cislo_dokladu\" size=\"8\" /></td>
                            <td class=\"cena\"><input class=\"important\" type=\"input\" id=\"add_castka\" value=\"0\" size=\"8\" /> Kè </td>
                            <td class=\"datum\"></td>
                            <td class=\"datum\"><input type=\"input\" class=\"calendar-ymd\" id=\"add_splatit_do\" size=\"8\" /></td>
                            <td class=\"datum\"><input type=\"input\" class=\"calendar-ymd\" id=\"add_splaceno\" size=\"8\" /></td>
                            <td><input type=\"input\" id=\"add_zpusob_uhrady\" /></td>
                            <td>
                                <form id=\"form_add_platba\" method=\"post\" action=\"?id_objednavka=$this->id_objednavka&typ=rezervace_platba&pozadavek=create\">
                                    <input type=\"hidden\" value=\"\" name=\"typ_dokladu\" id=\"hid_typ_dokladu\" />
                                    <input type=\"hidden\" value=\"\" name=\"cislo_dokladu\" id=\"hid_cislo_dokladu\" />
                                    <input type=\"hidden\" value=\"\" name=\"castka\" id=\"hid_castka\" />
                                    <input type=\"hidden\" value=\"\" name=\"splatit_do\" id=\"hid_splatit_do\" />
                                    <input type=\"hidden\" value=\"\" name=\"splaceno\" id=\"hid_splaceno\" />
                                    <input type=\"hidden\" value=\"\" name=\"zpusob_uhrady\" id=\"hid_zpusob_uhrady\" />
                                    <input type=\"submit\" onclick=\"return copy_add_platba_form();\" value=\"Pøidat\" /></td>
                                </form>
                          </tr>";
            }
            return $vypis;
        }


	/*metody pro pristup k parametrum*/
	function get_id_objednavka() { return $this->radek["id_objednavka"];}
	function get_id_platba() { return $this->radek["id_platba"];}
	function get_cislo_dokladu() { return $this->radek["cislo_dokladu"];}
	function get_castka() { return $this->radek["castka"];}
	function get_vystaveno() { return $this->radek["vystaveno"];}
	function get_splatit_do() { return $this->radek["splatit_do"];}
	function get_splaceno() { return $this->radek["splaceno"];}
	function get_zpusob_uhrady() { return $this->radek["zpusob_uhrady"];}
        function get_typ_dokladu() { 
            if($this->radek["typ_dokladu"] == "prijmovy"){
                return "Pøíjmový doklad";
            }else{
                return "Výdajový doklad";
            }
            //return $this->radek["typ_dokladu"];

        }

}
?>
