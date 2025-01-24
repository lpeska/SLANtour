<?php
/** 
* serial_dokument.inc.php - trida pro zobrazeni seznamu dokumentù serialu v administracni casti
*											- a jejich create a delete
*/
 

/*------------------- SEZNAM dokumentu -------------------  */
/*rozsireni tridy Serial o seznam dokumentu*/
class Dokument_serial extends Generic_list{
	protected $typ_pozadavku;
	protected $id_serialu;
	protected $id_zamestnance;
	protected $id_dokument;
	
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serialu,$id_dokument=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_serialu = $this->check_int($id_serialu);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_dokument = $this->check_int($id_dokument);
		$this->zakladni_foto = $this->check_int($zakladni_foto);
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku,$this->id_zamestnance,$this->id_serial)){
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
			$dotaz="select `dokument_serial`.`id_serial`, `dokument`.`id_dokument`,`dokument`.`dokument_url`,
							`dokument`.`nazev_dokument`,`dokument`.`popisek_dokument`  
					  from `dokument_serial` join
							`dokument` on (`dokument`.`id_dokument` =`dokument_serial`.`id_dokument`) 
					where `dokument_serial`.`id_serial`= ".$this->id_serialu." 
					order by `dokument`.`id_dokument` ";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `dokument_serial`
							(`id_serial`,`id_dokument`)
						VALUES
							(".$this->id_serialu.",".$this->id_dokument.");";
			//echo $dotaz;
			return $dotaz;
		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `dokument_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_dokument`=".$this->id_dokument."
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
	
	/**kontrola zda smim provest danou akci*/
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
		$id_modul_dokumenty = $core->get_id_modul_from_typ("dokumenty");
								
		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_dokumenty,"read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "delete"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_dokumenty,"read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_dokumenty,"read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;		
	}
		
	/**zobrazi hlavicku k seznamu dokumentù*/
	function show_list_header(){
		$vystup="
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Název</th>
						<th>Popisek</th>
						<th>Možnosti editace</th>
					</tr>
		";
		return $vystup;
	}
	
		/**zobrazime seznam  dokumentu pro dany serial*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
			$vypis=$vypis."
							<td class=\"id\">
								".$this->get_id_dokument()."
							</td>
							<td  class=\"nazev\">
								<a href=\"/".ADRESAR_DOKUMENT."/".$this->get_dokument_url()."\">
									".$this->get_nazev_dokument()."
								</a>
							</td>
							<td class=\"popisek\">".$this->get_popisek_dokument()."</td>
							<td class=\"menu\">
							  <a href=\"?id_serial=".$this->get_id_serial()."&amp;id_dokument=".$this->get_id_dokument()."&amp;typ=dokument&amp;pozadavek=delete\">odebrat</a>
							</td>
						</tr>";
			return $vypis;
		}
	}	

	/*metody pro pristup k parametrum*/
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_dokument() { return $this->radek["id_dokument"];}
	function get_nazev_dokument() { return $this->radek["nazev_dokument"];}
	function get_popisek_dokument() { return $this->radek["popisek_dokument"];}
	function get_dokument_url() { return $this->radek["dokument_url"];}
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
