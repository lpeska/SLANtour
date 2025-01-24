<?php
/** 
* serial_dokument.inc.php - trida pro zobrazeni seznamu informací serialu v administracni casti
*											- a jejich create a delete
*/
 

/*------------------- SEZNAM informaci -------------------  */
/*rozsireni tridy Serial o seznam informaci*/
class Objekty_serial extends Generic_list{
	protected $typ_pozadavku;
	protected $id_serialu;
	protected $id_zamestnance;
	protected $id_objektu;
	
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serialu,$id_objektu=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_serialu = $this->check_int($id_serialu);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_objektu = $this->check_int($id_objektu);
		
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
			$dotaz="select `objekt_serial`.`id_serial`, `objekt`.`id_objektu`,
					`objekt`.`nazev_objektu`,`objekt`.`typ_objektu`,
                                        group_concat(concat(`topologie_tok`.`id_tok_topologie`,' ',`topologie_tok`.`id_topologie`) separator \";\") as `topologie`                 
					from `objekt_serial` join
							`objekt` on (`objekt`.`id_objektu` =`objekt_serial`.`id_objektu`) 
                                                         left join `topologie_tok` on (`objekt`.`id_objektu` = `topologie_tok`.`id_objektu`)    
					where `objekt_serial`.`id_serial`= ".$this->id_serialu." 
                                        group by `objekt`.`id_objektu` 
					order by `objekt_serial`.`id_objektu` ";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `objekt_serial`
							(`id_serial`,`id_objektu`)
						VALUES
							(".$this->id_serialu.",".$this->id_objektu.");";
			//echo $dotaz;
			return $dotaz;
		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `objekt_serial` 
						WHERE `id_serial`=".$this->id_serialu." and `id_objektu`=".$this->id_objektu."
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
						<th>Typ objekt</th>
						<th>Název</th>
                                                <th>Topologie</th>
                                                <th>Objektové kategorie</th>
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
		$id_modul_objekt = $core->get_id_modul_from_typ("serial");
								
		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_objekt,"read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "delete"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_objekt,"read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_objekt,"read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
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
			$pouzite_ok = "";
                        $query_ok = "select `objekt_kategorie`.`id_objekt_kategorie`,`objekt_kategorie`.`nazev` 
                                                from `objekt_kategorie`
                                                where `id_objektu`=".$this->get_id_objektu()."";
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                        while ($row_ok = mysqli_fetch_array($data)) {
                            $pouzite_ok .= $row_ok["id_objekt_kategorie"].": ".$row_ok["nazev"]."<br/>";
                        }
                        if($this->radek["topologie"]!=""){
                            $array_topologie = explode(";", $this->radek["topologie"]);
                            foreach ($array_topologie as $key => $top_enum) {
                               $topologie_ids = explode(" ", $top_enum); 
                               $id_tok_topologie = $topologie_ids[0];
                               $id_topologie = $topologie_ids[1];
                               $topologie .= "<a href=\"/admin/topologie_objektu.php?id_tok_topologie=".$id_tok_topologie."&id_topologie=".$id_topologie."&typ=topologie&pozadavek=zasedaci_poradek\">Zasedací poøádek $id_tok_topologie</a><br/>" ;
                            }
                        }else{
                            $topologie="";
                        }
                        if($this->get_typ_objekt() == 5){
                            $ref = "\"objekty.php?id_objektu=".$this->get_id_objektu()."&amp;typ=tok_list&pozadavek=show_letuska\"";
                        }else{
                            $ref = "\"objekty.php?id_objektu=".$this->get_id_objektu()."&amp;typ=objekty&amp;pozadavek=edit\"";
                        }
                        
			$vypis=$vypis."
							<td class=\"id\">
								".$this->get_id_objektu()."
							</td>
                                                        
							<td  class=\"typ\">
								".Serial_library::get_typ_objektu( $this->get_typ_objekt() )."
							</td>							
							<td  class=\"nazev\">
								<a href=".$ref.">".$this->get_nazev_objekt()."</a>
							</td>
                                                        <td class=\"id\">
								$topologie
							</td>
                                                        <td>".$pouzite_ok."</td>
							<td class=\"menu\">
							  <a href=\"?id_serial=".$this->get_id_serial()."&amp;id_objektu=".$this->get_id_objektu()."&amp;typ=serial_objekty&amp;pozadavek=delete\">odebrat</a>
							</td>
						</tr>";
			return $vypis;
		}
	}	

	/*metody pro pristup k parametrum*/
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_objektu() { return $this->radek["id_objektu"];}
	function get_nazev_objekt() { return $this->radek["nazev_objektu"];}
	function get_typ_objekt() { return $this->radek["typ_objektu"];}
	
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
