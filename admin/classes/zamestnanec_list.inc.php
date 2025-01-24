<?php
/** 
* zamestnanec_list.inc.php - tridy pro zobrazeni seznamu zamestnancu
*/

/*------------------- SEZNAM zamestnancu -------------------  */

class Zamestnanec_list extends Generic_list{
	//vstupni data
	protected $id_zamestnance;
	protected $order_by;
	protected $username;
	protected $jmeno;
	protected $prijmeni;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR  -----------------	
/**konstruktor tøídy*/
	function __construct($id_zamestnance, $username, $jmeno, $prijmeni, $order_by){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		
	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->username = $this->check($username);
		$this->jmeno = $this->check($jmeno);
		$this->prijmeni = $this->check($prijmeni);		
		$this->order_by = $this->check($order_by);
		
				
		//pokud mam dostatecna prava pokracovat
		if( $this->legal() ){

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query())
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
			}		
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query(){
			//tvorba podminek dotazu
			if($this->username!=""){
				$where_username=" `user_klient`.`uzivatelske_jmeno` LIKE '%$this->username%' AND";
			}else{
				$where_username="";
			}	
			if($this->jmeno!=""){
				$where_jmeno=" `user_klient`.`jmeno` LIKE '%$this->jmeno%' AND";
			}else{
				$where_jmeno="";
			}	
			if($this->prijmeni!=""){
				$where_prijmeni=" `user_klient`.`prijmeni` LIKE '%$this->prijmeni%' AND";
			}else{
				$where_prijmeni="";
			}	
			
			$order=$this->order_by($this->order_by);
		
			$dotaz="SELECT `user_klient`.*,`user_zamestnanec`.`uzivatelske_jmeno`,`user_zamestnanec`.`id_user`
                                FROM `user_zamestnanec` LEFT JOIN `user_klient` ON (`user_zamestnanec`.`id_user_klient` = `user_klient`.`id_klient`)
                                WHERE $where_username $where_jmeno $where_prijmeni 1
                                ORDER BY $order";
//			echo $dotaz;
			return $dotaz;
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`user_zamestnanec`.`id_user`";
   			 break;
			case "id_down":
				 return "`user_zamestnanec`.`id_user` desc";
   			 break;				 
			case "jmeno_up":
				 return "`user_klient`.`prijmeni`,`user_zamestnanec`.`jmeno`";
   			 break;
			case "jmeno_down":
				 return "`user_klient`.`prijmeni` desc,`user_zamestnanec`.`jmeno` desc";
   			 break;				 
			case "username_up":
				 return "`user_klient`.`uzivatelske_jmeno`";
   			 break;			
			case "username_down":
				 return "`user_klient`.`uzivatelske_jmeno` desc";
   			 break;					 	 		 
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`user_klient`.`prijmeni`,`user_klient`.`jmeno`";
	}

	/**zobrazi formular pro filtorvani vypisu serialu*/
	function show_filtr(){
		
		//tvroba input uzivatelske jmeno
		$input_username="<input name=\"zamestnanec_username\" type=\"text\" value=\"".$this->username."\" />";
		//tvroba input jmeno
		$input_jmeno="<input name=\"zamestnanec_jmeno\" type=\"text\" value=\"".$this->jmeno."\" />";
		//tvroba input prijmeni
		$input_prijmeni="<input name=\"zamestnanec_prijmeni\" type=\"text\" value=\"".$this->prijmeni."\" />";
						
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
				
		//vysledny formular			
		$vystup="
			<form method=\"post\" action=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=username-jmeno-prijmeni\">
			<table class=\"filtr\">
				<tr>
					<td>Uživatelské jméno: ".$input_username."</td>
					<td>Jméno: ".$input_jmeno."</td>
					<td>Pøíjmení: ".$input_prijmeni."</td>
					<td>".$submit."</td>
				</tr>
			</table>
			</form>
		";
		return $vystup;		
	}		
	
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
		if( !$this->get_error_message()){
			$vystup="
				<table class=\"list\">
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up\"></a>
							<a class='sort-down' href=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down\"></a>
							</div>
						</th>
						<th>Uživatelské jméno
						<div class='sort'>
							<a class='sort-up' href=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=username_up\"></a>
							<a class='sort-down' href=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=username_down\"></a>
							</div>
						</th>							
						<th>Pøíjmení a jméno
						<div class='sort'>
							<a class='sort-up' href=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=jmeno_up\"></a>
							<a class='sort-down' href=\"?typ=zamestnanec_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=jmeno_down\"></a>
							</div>
						</th>						
						<th>E-mail
						</th>
						<th>Telefon
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}
	}		
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni, $selected=false){
		if( !$this->get_error_message()){
                    if($typ_zobrazeni=="tabulka"){
                            if($this->suda==1){
                                    $vypis="<tr class=\"suda\">";
                                    }else{
                                    $vypis="<tr class=\"licha\">";
                            }
                            //text pro typ informaci
                            $vypis = $vypis."
                                                            <td class=\"id\">".$this->get_id_user()."</td>
                                                            <td class=\"uzivatelske_jmeno\">".$this->get_uzivatelske_jmeno()."</td>
                                                            <td class=\"jmeno\">".$this->get_prijmeni()." ".$this->get_jmeno()."</td>
                                                            <td class=\"email\">".$this->get_email()."</td>
                                                            <td class=\"telefon\">".$this->get_telefon()."</td>

                                                            <td class=\"menu\">
                                                                      <a href=\"?id_zamestnanec=".$this->get_id_user()."&amp;typ=zamestnanec&amp;pozadavek=edit\">edit</a>
                                                                      | <a class='anchor-delete' href=\"?id_zamestnanec=".$this->get_id_user()."&amp;typ=zamestnanec&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">smazat</a>
                                                                      | <a class='anchor-delete' href=\"?id_zamestnanec=".$this->get_id_user()."&amp;typ=zamestnanec&amp;pozadavek=delete_all\" onclick=\"javascript:return confirm('Pokud ma klient objednavky, smazani nebude mozne. Presto se pokusit smazat?')\">smazat vcetne klienta</a>
                                                            </td>
                                                    </tr>";

                            return $vypis;
                    } else if($typ_zobrazeni=="serial_spravce"){
                        $sel = $selected ? "selected='selected'" : "";
                        $vypis = "<option $sel value='".$this->get_id_user()."'>".$this->get_uzivatelske_jmeno()."</option>";
                        return $vypis;
                    }
		}//no error message
	}
	
	
	
	/**zjistim, zda mam opravneni k pozadovane akci*/
	function legal(){
		$zamestnanec = User_zamestnanec::get_instance();
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
				
		return $zamestnanec->get_bool_prava($id_modul,"read");
	}
	
	/*metody pro pristup k parametrum*/
	function get_id_user() { return $this->radek["id_user"];}
	function get_uzivatelske_jmeno() { return $this->radek["uzivatelske_jmeno"];}
	function get_jmeno() { return $this->radek["jmeno"];}
	function get_prijmeni() { return $this->radek["prijmeni"];}
	function get_email() { return $this->radek["email"];}
	function get_telefon() { return $this->radek["telefon"];}
	function get_funkce() { return $this->radek["funkce"];}
			
	
}
?>
