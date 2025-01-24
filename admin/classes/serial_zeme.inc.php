<?php
/** 
* serial_zeme.inc.php - trida pro zobrazeni seznamu zemi a destinaci serialu v administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM zemi a destinaci -------------------  */
/*rozsireni tridy Serial o seznam zemi*/
class Zeme_serial extends Generic_list{
	protected $typ_pozadavku;
	protected $id_serialu;
	protected $id_zamestnance;
	protected $id_zeme;
	protected $id_destinace;
	protected $polozka_menu;
	protected $zakladni_zeme;
		
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serialu,$id_zeme="",$zakladni_zeme="",$id_destinace="",$polozka_menu="",$probihajici_transakce=0){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_serialu = $this->check_int($id_serialu);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_zeme = $this->check_int($id_zeme);
		$this->zakladni_zeme = $this->check_int($zakladni_zeme);
		$this->id_destinace = $this->check_int($id_destinace);
		$this->polozka_menu = $this->check_int($polozka_menu);		
		//pokud mam dostatecna prava pokracovat
		if( $this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku) ){
			//na zaklade typu pozadavku vytvorim dotaz
			
			if($probihajici_transakce==0){//pokud nejsem uprostred jine transakce, tak ji zahajim
				$this->database->start_transaction();
			}	
				
				//pokud vytvarime destinaci, zkontrolujeme, zda mame pridanou zemi, jinak ji vytvorime taky
				if($this->typ_pozadavku=="create_destinace"){
					$count_zeme=mysqli_fetch_array($this->database->transaction_query($this->create_query("count_zeme_destinace")))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
					//pokud nemame zemi ke ktere patri prislusna destinace, tak ji pridame
					if(!$count_zeme["pocet"])	{
						$create_zeme=$this->database->transaction_query($this->create_query("create"))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
					}
					
				//pokud chceme smazat zemi, smazeme s ni i vsechny destinace
				}else if($this->typ_pozadavku=="delete"){
						$delete_destinace=$this->database->transaction_query($this->create_query("delete_all_destinace"))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );				
									
				//pokud nejakou zemi oznacujeme za zakladni, musime vsechny ostatni oznacit jako ne-zakladni
				}else if(($this->typ_pozadavku=="update" or $this->typ_pozadavku=="create") and $this->zakladni_zeme==1){
					$remove_zakladni=$this->database->transaction_query($this->create_query("remove_zakladni_zeme"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				}
				
				if(!$this->get_error_message()){
					$this->data = $this->database->transaction_query( $this->create_query($this->typ_pozadavku) )
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				}
				
				if(!$this->get_error_message() ){
					if($probihajici_transakce==0){//pokud nejsem uprostred jine transakce, tak ji potvrdim
						$this->database->commit();
					}						
					$this->confirm("požadovaná akce probìhla úspìšnì");
				}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show"){
	
			$dotaz="select `zeme`.`nazev_zeme`,`zeme`.`id_zeme`,`zeme_serial`.`zakladni_zeme`,`zeme_serial`.`polozka_menu` as `zeme_polozka_menu`,`zeme_serial`.`id_serial`,
								`destinace`.`nazev_destinace`,`destinace`.`id_destinace`,`destinace`.`id_destinace`,`destinace_serial`.`polozka_menu`
						 from 
							 	(`zeme` join 
							 	 `zeme_serial` on (`zeme`.`id_zeme`=`zeme_serial`.`id_zeme`) )
							  left join 
							  		(`destinace`  join 
							 		 `destinace_serial` on (`destinace`.`id_destinace`=`destinace_serial`.`id_destinace`) )
							  on (`zeme`.`id_zeme`=`destinace`.`id_zeme` and `zeme_serial`.`id_serial`=`destinace_serial`.`id_serial`) 
						where `zeme_serial`.`id_serial`=".$this->id_serialu."
						order by `zeme_serial`.`zakladni_zeme` desc,`zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="count_zeme_destinace"){
			$dotaz= "SELECT COUNT(`zeme_serial`.`id_zeme`) as pocet
								FROM `zeme_serial`
								WHERE `id_serial`=".$this->id_serialu." and `id_zeme`=".$this->id_zeme."";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="remove_zakladni_zeme"){
			$dotaz= "UPDATE `zeme_serial` 
								SET `zakladni_zeme`=0
								WHERE `id_serial`=".$this->id_serialu." and `zakladni_zeme`=1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `zeme_serial`
							(`id_serial`,`id_zeme`,`zakladni_zeme`,`polozka_menu`)
						VALUES
							(".$this->id_serialu.",".$this->id_zeme.",".$this->zakladni_zeme.",".$this->polozka_menu.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="create_destinace"){
			$dotaz=$dotaz.
						"INSERT INTO `destinace_serial`
							(`id_serial`,`id_destinace`,`polozka_menu`)
						VALUES
							(".$this->id_serialu.",".$this->id_destinace.",".$this->polozka_menu.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="update"){
			$dotaz=$dotaz. "UPDATE `zeme_serial` 
						SET `zakladni_zeme`=".$this->zakladni_zeme.", `polozka_menu`=".$this->polozka_menu."
						WHERE `id_serial`=".$this->id_serialu." and `id_zeme`=".$this->id_zeme."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `zeme_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_zeme`=".$this->id_zeme."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
				
		}else if($typ_pozadavku=="delete_destinace"){
			$dotaz= "DELETE FROM `destinace_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_destinace`=".$this->id_destinace."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete_all_destinace"){
			$dotaz= "DELETE FROM `destinace_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_destinace` 
							in (SELECT `id_destinace` FROM `destinace` WHERE `id_zeme`=".$this->id_zeme.") ";
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
	
	/**kontrola zda mam odpovidajici data*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovaná data: název seriálu, popisek,  id_typ,
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::int_min($this->id_zeme,1) ){
				$ok = 0;
				$this->chyba("Nevyplnìná zemì!");
			}

		}

		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}

	}
	
	/**kontrola zda smim provest danou akci*/
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
		$id_modul_zeme = $core->get_id_modul_from_typ("zeme");
						
		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_zeme, "read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "create_destinace" or $typ_pozadavku == "update"
					 or $typ_pozadavku == "delete"  or $typ_pozadavku == "delete_destinace"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_zeme, "read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_zeme, "read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;
	}
	
	
	/**zobrazi hlavicku k seznamu*/
	function show_list_header(){
		$vystup="
				<table class=\"list\">
					<tr>
						<th colspan=\"2\">Id</th>
						<th>Název</th>
						<th>Možnosti editace</th>
					</tr>
		";
		return $vystup;
	}	
	
	/**zobrazi seznam zemi a destinaci*/
	function show_list($typ_zobrazeni){
		if($typ_zobrazeni=="tabulka"){
			
			$last_zeme="";
			$last_parita="";
			while($this->get_next_radek()){		
				
				//kazdou zemi zobrazime i bez destinace
				if($this->get_id_zeme()!=$last_zeme){
					$last_zeme=$this->get_id_zeme();
					$tr="<tr class=\"suda\">";
					//pokud zeme neni zakladni, dame do menu moznost ji vytvorit, zakladni zemi nedovolime smazat - serial by se pak spatne zobrazoval
					if(!$this->get_zakladni_zeme()){
						$zakl_zeme="<a href=\"?id_serial=".$this->get_id_serial()."&amp;id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=update&amp;zakladni_zeme=1&amp;polozka_menu=1\">zmìnit na základní zemi</a> | 
									<a href=\"?id_serial=".$this->get_id_serial()."&amp;id_zeme=".$this->get_id_zeme()."&amp;typ=zeme&amp;pozadavek=delete\">odebrat</a>";
					}else{
						$zakl_zeme="<b>(základní zemì)</b>";
					}
					if(!$this->get_zeme_polozka_menu()){
                                            $pol_menu = "<i> | (nezobrazovat v menu)</i>";
                                        }else{
                                            $pol_menu = "";
                                        }		
					$vypis=$vypis.$tr."
							<td class=\"id_zeme\">".$this->get_id_zeme()."</td>
							<td class=\"id_destinace\"></td>					
							<td class=\"nazev\">
								".$this->get_nazev_zeme()."
							</td>
							<td class=\"menu\">
								".$zakl_zeme.$pol_menu."
							  

							</td>
						</tr>";			
				}
				//pokud mame destinaci, vypiseme zemi i s ni
				if($this->get_nazev_destinace()!=""){
					$tr="<tr class=\"licha\">";
					$vypis=$vypis.$tr."
							<td class=\"id_zeme\">".$this->get_id_zeme()."</td>
							<td class=\"id_destinace\">".$this->get_id_destinace()."</td>							
							<td class=\"nazev\">
								&nbsp;&nbsp; - ".$this->get_nazev_destinace()." ".($this->get_polozka_menu()?("(polozka menu)"):(""))."
							</td>	
							<td class=\"menu\">
							  <a href=\"serial.php?id_serial=".$this->get_id_serial()."&amp;id_zeme=".$this->get_id_zeme()."&amp;id_destinace=".$this->get_id_destinace()."&amp;typ=zeme&amp;pozadavek=delete_destinace\">delete</a>
							</td>
						</tr>";
				}
			}//end while			
			return $vypis;
		}//typ_zobrazeni=tabulka
	}	

	/*metody pro pristup k parametrum*/
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_zeme() { return $this->radek["id_zeme"];}
	function get_id_destinace() { return $this->radek["id_destinace"];}
	function get_zakladni_zeme() { return $this->radek["zakladni_zeme"];}
	function get_nazev_zeme() { return $this->radek["nazev_zeme"];}
	function get_nazev_destinace() { return $this->radek["nazev_destinace"];}
	function get_polozka_menu() { return $this->radek["polozka_menu"];}
        function get_zeme_polozka_menu() { return $this->radek["zeme_polozka_menu"];}
		
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
