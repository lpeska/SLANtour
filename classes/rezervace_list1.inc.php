<?php
/** 
* trida pro zobrazení seznamu rezervací klienta
* - volá se po kliknutí na Moje Objednávky klientského menu
*/

class Rezervace_list extends Generic_list{
	protected $id_klient;
	
	public $database; //trida pro odesilani dotazu	

//------------------- KONSTRUKTOR  -----------------	
	/**konstruktor tøidy*/
	function __construct(){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$uzivatel = User::get_instance();	
		$this->id_klient = $uzivatel->get_id();	
		//pokud mam dostatecna prava pokracovat
		if( $uzivatel->get_correct_login() ){
			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show_objednavky_klienta"))
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$pocet_objednavek = mysqli_num_rows($this->data);
				
			//zjistuju, zda mam neco k zobrazeni
			if($pocet_objednavek==0){
				$this->chyba("Nemáte žádnou objednávku");
			}
		
		}else{
			$this->chyba("Nejste pøihlášen!");	
		}	
		
	}
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show_objednavky_klienta"){	
			
			$dotaz = "select 
								`objednavka`.`id_objednavka`,`objednavka`.`datum_rezervace`,`objednavka`.`rezervace_do`,`objednavka`.`stav`,`objednavka`.`pocet_osob`,   
								`serial`.`id_serial`, `serial`.`nazev`,
								`zajezd`.`id_zajezd`, `zajezd`.`od`,`zajezd`.`do`
						 from `objednavka` 
						 		join	`user_klient` on (`objednavka`.`id_klient` = `user_klient`.`id_klient`)
								join  `serial` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
								join  `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
						where `objednavka`.`id_agentury` = ".$this->id_klient."
						order by `objednavka`.`datum_rezervace` desc";
				//echo $dotaz;
				return $dotaz;

		}		
	}	

	/** zobrazi hlavicku k seznamu objednávek*/
	function show_list_header(){
		if( !$this->get_error_message() ){
			$vystup="
				<table class=\"list\" cellpadding=\"2\" width=\"100%\" style=\"background-color:#f9de9e; border:2px solid #baaa9a;\">
					<tr style=\"background-color:#debe8e;\">
						<th>Zájezd
						</th>									
						<th>Datum rezervace
						</th>		
						<th>Opce do
						</th>							
						<th>Stav
						</th>		
						<th>Osob
						</th>																							
						<th>
						</th>
					</tr>
			";
			return $vystup;
		}else{
			return "";
		}
	}		
	/** zobrazi jeden zaznam objednávky */
	function show_list_item($typ_zobrazeni){
		$core = Core::get_instance();
		$adresa_rezervace = $core->get_adress_modul_from_typ("rezervace");
		
		if( $adresa_rezervace !== false ){		
			if( !$this->get_error_message() ){	
				if($typ_zobrazeni=="tabulka"){
					if($this->suda==1){
						$vypis="<tr class=\"suda\">";
						}else{
						$vypis="<tr class=\"licha\">";
					}
					//text pro typ informaci
					$vypis = $vypis."
							<td class=\"nazev\">".$this->get_nazev_serial()."<br/> ".$this->change_date_en_cz( $this->get_zajezd_od() )." - ".$this->change_date_en_cz( $this->get_zajezd_do() )."</td>
							<td class=\"datum\">".$this->change_date_en_cz( $this->get_datum_rezervace() )."</td>		
							<td class=\"datum\">".$this->change_date_en_cz( $this->get_rezervace_do() )."</td>	
							<td class=\"stav\">".Rezervace_library::get_stav( ($this->get_stav()-1) )."</td>				
							<td class=\"pocet_osob\">".$this->get_pocet_osob()."</td>			

							
							<td class=\"menu\">								  
								  <a href=\"".$this->get_adress(array($adresa_rezervace,"zobrazit_objednavky",$this->get_id_objednavka() ),0)."\">zobrazit objednávku</a>
							";				
					return $vypis;
				}
			}else{
				return "";
			}
		}
	}
	
	/*metody pro pristup k parametrum*/
	function get_id_klient() { return $this->radek["id_klient"];}
	function get_id_objednavka() { return $this->radek["id_objednavka"];}

	function get_nazev_serial() { return $this->radek["nazev"];}	
	function get_zajezd_od() { return $this->radek["od"];}	
	function get_zajezd_do() { return $this->radek["do"];}	

	function get_datum_rezervace() { return $this->radek["datum_rezervace"];}
	function get_rezervace_do() { return ($this->get_stav() == 3 ? $this->radek["rezervace_do"] : "" );}
	function get_stav() { return $this->radek["stav"];}
	function get_pocet_osob() { return $this->radek["pocet_osob"];}
	
	
}
?>
