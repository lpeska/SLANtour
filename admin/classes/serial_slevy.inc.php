<?php
/** 
* serial_dokument.inc.php - trida pro zobrazeni seznamu informací serialu v administracni casti
*											- a jejich create a delete
*/
 

/*------------------- SEZNAM informaci -------------------  */
/*rozsireni tridy Serial o seznam informaci*/
class Slevy_serial extends Generic_list{
	protected $typ_pozadavku;
	protected $id_serialu;
	protected $id_zamestnance;
	protected $id_slevy;
	protected $platnost;	
	
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serialu,$id_slevy="",$platnost=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_serialu = $this->check_int($id_serialu);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_slevy = $this->check_int($id_slevy);
		$this->platnost = $this->check_int($platnost);
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku)){
			//na zaklade typu pozadavku vytvorim dotaz			
			$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			//vygenerování potvrzovací hlášky
			if( !$this->get_error_message() ){
				$this->confirm("Požadovaná akce probìhla úspìšnì");
			}						
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show"){
			$dotaz="select `slevy_serial`.`id_serial`, `slevy`.`id_slevy`, `slevy`.`castka`, `slevy`.`mena`,
							`slevy`.`nazev_slevy`,`slevy_serial`.`platnost`  
					  from `slevy_serial` join
							`slevy` on (`slevy`.`id_slevy` =`slevy_serial`.`id_slevy`) 
					where `slevy_serial`.`id_serial`= ".$this->id_serialu." 
					order by `slevy`.`id_slevy` ";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `slevy_serial`
							(`id_serial`,`id_slevy`,`platnost`)
						VALUES
							(".$this->id_serialu.",".$this->id_slevy.",".$this->platnost.");";
			//echo $dotaz;
			return $dotaz;
		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `slevy_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_slevy`=".$this->id_slevy."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `serial` 
						WHERE `id_serial`=".$this->id_serialu."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;					
		}
	}	
	/**zobrazi hlavicku k seznamu informaci*/
	function show_list_header(){
		$vystup="
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Název</th>
						<th>Velikost slevy</th>
						<th>Možnosti editace</th>
					</tr>
		";
		return $vystup;
	}
	
		//kontrola zda smim provest danou akci
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
		$id_modul_informace = $core->get_id_modul_from_typ("informace");
								
		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_informace,"read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "delete"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_informace,"read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_informace,"read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;		
	}
	/**zobrazime seznam informaci pro dany serial*/		
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
			$vypis=$vypis."
							<td class=\"id\">
								".$this->get_id_slevy()."
							</td>					
							<td  class=\"nazev\">
								".$this->get_nazev_slevy()."
							</td>
							<td  class=\"nazev\">
								".$this->get_castka()."
							</td>							
							<td class=\"menu\">
							  <a href=\"?id_serial=".$this->get_id_serial()."&amp;id_slevy=".$this->get_id_slevy()."&amp;typ=slevy&amp;pozadavek=delete\">odebrat</a>
							</td>
						</tr>";
			return $vypis;
		}
	}	

	/*metody pro pristup k parametrum*/
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_slevy() { return $this->radek["id_slevy"];}
	function get_nazev_slevy() { return $this->radek["nazev_slevy"];}
	function get_castka() { return $this->radek["castka"]." ".$this->radek["mena"];}
	
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_serial == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}	
} 

?>
