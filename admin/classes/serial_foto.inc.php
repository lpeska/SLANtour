<?php
/** 
* serial_foto.inc.php - trida pro zobrazeni seznamu fotek serialu v administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM fotografii -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Foto_serial extends Generic_list{
	protected $typ_pozadavku;
	protected $id_serialu;
	protected $id_zamestnance;
	protected $id_foto;
	protected $zakladni_foto;
		
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serialu,$id_foto="",$zakladni_foto=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_serialu = $this->check_int($id_serialu);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_foto = $this->check_int($id_foto);
		$this->zakladni_foto = $this->check_int($zakladni_foto);
		
		//pokud mam dostatecna prava pokracovat
		if( $this->legal($this->typ_pozadavku) ){
			//na zaklade typu pozadavku vytvorim dotaz				
				$this->database->start_transaction();
				
				//pokud chceme vytvorit zakladnifoto, nejdriv vsechny oznacime za nezakladni
				if(($this->typ_pozadavku=="update" or $this->typ_pozadavku=="create") and $this->zakladni_foto==1){
					$remove_zakladni=$this->database->query($this->create_query("remove_zakladni_foto"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				}
				$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				
				//vygenerování potvrzovací hlášky
				if( !$this->get_error_message() ){
					$this->database->commit(); //potvrdim transakci
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
			$dotaz="select `foto_serial`.`id_serial`, 
							`foto`.`id_foto`,`foto_serial`.`zakladni_foto`,
							`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url` 
					  from `foto_serial` join
							`foto` on (`foto`.`id_foto` =`foto_serial`.`id_foto`) 
					where `foto_serial`.`id_serial`= ".$this->id_serialu." 
					order by `foto_serial`.`zakladni_foto` desc,`foto`.`id_foto` ";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="remove_zakladni_foto"){
			$dotaz= "UPDATE `foto_serial` 
								SET `zakladni_foto`=0
								WHERE `id_serial`=".$this->id_serialu." and `zakladni_foto`=1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `foto_serial`
							(`id_serial`,`id_foto`,`zakladni_foto`)
						VALUES
							(".$this->id_serialu.",".$this->id_foto.",".$this->zakladni_foto.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="update"){
			$dotaz=$dotaz. "UPDATE `foto_serial` 
						SET `zakladni_foto`=".$this->zakladni_foto."
						WHERE `id_serial`=".$this->id_serialu." and `id_foto`=".$this->id_foto."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `foto_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_foto`=".$this->id_foto."
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
		$id_modul_foto = $core->get_id_modul_from_typ("fotografie");
								
		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_foto,"read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "delete"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_foto,"read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_foto,"read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;		
	}
				
	/**zobrazi hlavicku k seznamu fotografii*/
	function show_list_header(){
		$vystup="
				<table class=\"list\">
					<tr>
						<th>Foto</th>
						<th>Id</th>
						<th>Název / popisek</th>
						<th>Možnosti editace</th>
					</tr>
		";
		return $vystup;
	}
	/**zobrazime seznam  fotografii pro dany serial*/	
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
			//pokud fotka neni zakladni, dame do menu moznost ji vytvorit
			if(!$this->get_zakladni_foto()){
				$zakl_foto="<a href=\"?id_serial=".$this->get_id_serial()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=update&amp;zakladni_foto=1\">zmìnit na základní foto</a> | ";
			}else{
				$zakl_foto="<a href=\"?id_serial=".$this->get_id_serial()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=update&amp;zakladni_foto=0\">odznaèit základní</a> | ";
			}
			
			$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									  width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"nazev\">".$this->get_id_foto()."</td>						
							<td class=\"nazev\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\">
								".$zakl_foto."
							  <a href=\"?id_serial=".$this->get_id_serial()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=delete\">odebrat</a>

							</td>
						</tr>";
			return $vypis;
		}
	}	

	/*metody pro pristup k parametrum*/
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_foto() { return $this->radek["id_foto"];}
	function get_zakladni_foto() { return $this->radek["zakladni_foto"];}
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { return $this->radek["popisek_foto"];}
	function get_foto_url() { return $this->radek["foto_url"];}
	
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
