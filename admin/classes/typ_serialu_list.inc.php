<?php
/** 
* typ_serialu_list.inc.php - trida pro zobrazeni seznamu typu a podtypu seriálu
*							- bez filtru a strankovani (predpoklad ze u typù to nebude treba)
*/

/*------------------- SEZNAM typu serialu -------------------  */

class Typ_list extends Generic_list{
	//vstupni data
	protected $id_zamestnance;
	protected $nazev;
	protected $order_by;
	protected $selected_typ;
	protected $selected_podtyp;
	
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($id_zamestnance, $order_by, $selected_typ="", $selected_podtyp="", $array_typ_podtyp=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		
	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->order_by = $this->check($order_by);
		$this->selected_typ = $this->check_int($selected_typ);
		$this->selected_podtyp = $this->check_int($selected_podtyp);
		$this->array_typ_podtyp = $array_typ_podtyp;
		//pokud mam dostatecna prava pokracovat
		if( $this->legal() ){

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query())
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu do datatbaze*/
	function create_query(){
		//definice jednotlivych casti dotazu
		
			$order=$this->order_by($this->order_by);
		
			$dotaz="select `typ_serial`.`nazev_typ`,`typ_serial`.`id_typ`,
								`podtyp`.`nazev_typ` as `nazev_podtyp` ,`podtyp`.`id_typ` as `id_podtyp`
						 from `typ_serial` left join 
							  	`typ_serial` as `podtyp`  on (`typ_serial`.`id_typ`=`podtyp`.`id_nadtyp`) 
						where `typ_serial`.`id_nadtyp`=0
						order by ".$order." ";
			//echo $dotaz;
			return $dotaz;
	}	
	/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`typ_serial`.`id_typ`,`podtyp`.`id_typ` ";
   			 break;
			case "id_down":
				 return "`typ_serial`.`id_typ` desc,`podtyp`.`id_typ` desc ";
   			 break;				 
			case "nazev_up":
				 return "`typ_serial`.`nazev_typ`,`podtyp`.`nazev_typ`";
   			 break;
			case "nazev_down":
				 return "`typ_serial`.`nazev_typ` desc,`podtyp`.`nazev_typ` desc";
   			 break;				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`typ_serial`.`nazev_typ`,`podtyp`.`nazev_typ`";
	}
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		$vystup="
				<table class=\"list\">
					<tr>
						<th colspan=\"2\">Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=typ_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=typ_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Název
						<div class='sort'>
							<a class='sort-up' href=\"?typ=typ_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=typ_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
		";
		return $vystup;
	}	
	/**zobrazi seznam typu serialu*/	
	function show_list($typ_zobrazeni){	
		if($typ_zobrazeni=="tabulka_typ"){	
			$last_typ="";
			while($this->get_next_radek()){		
				//kazdou zemi zobrazime i bez destinace
				if($this->get_id_typ()!=$last_typ){
					$last_typ=$this->get_id_typ();		
					$tr="<tr class=\"suda\">";	 
							
					$vypis=$vypis.$tr."
							<td class=\"id_typ\">
								".$this->get_id_typ()."
							</td>
							<td class=\"id_podtyp\"></td>
							<td class=\"nazev\">
								<strong>".$this->get_nazev_typ()."</strong>
							</td>
							<td class=\"menu\">
							  <a href=\"?id_typ=".$this->get_id_typ()."&amp;typ=typ_serialu&amp;pozadavek=edit\">editovat typ</a>
                                                        | <a href=\"?id_typ=".$this->get_id_typ()."&amp;typ=foto\">fotografie</a>    
      
							| <a href=\"?id_typ=".$this->get_id_typ()."&amp;typ=typ_serialu&amp;pozadavek=new_podtyp\">vytvoøit nový podtyp</a>
							| <a class='anchor-delete' href=\"?id_typ=".$this->get_id_typ()."&amp;typ=typ_serialu&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";			
				}
				//pokud mame destinaci, vypiseme zemi i s ni
				if($this->get_id_podtyp()!=""){
					$tr="<tr class=\"licha\">";				
					$vypis=$vypis.$tr."
							<td class=\"id_zeme\">
								".$this->get_id_typ()."
							</td>
							<td class=\"id_destinace\">
								".$this->get_id_podtyp()."
							</td>					
							<td class=\"nazev\">
								&nbsp;&nbsp; - ".$this->get_nazev_podtyp()."
							</td>	
							<td class=\"menu\">
							  <a href=\"?id_typ=".$this->get_id_typ()."&amp;id_podtyp=".$this->get_id_podtyp()."&amp;typ=typ_serialu&amp;pozadavek=edit_podtyp\">editovat podtyp</a>						
							| <a class='anchor-delete' href=\"?id_typ=".$this->get_id_typ()."&amp;id_podtyp=".$this->get_id_podtyp()."&amp;typ=typ_serialu&amp;pozadavek=delete_podtyp\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";
				}
			}//end while	
			return $vypis;
			
		}else if($typ_zobrazeni=="select_typ_podtyp"){
			$last_typ="";
			$vypis="";
			while( $this->get_next_radek() ){
				//kazdy typ zobrazime i bez podtypu
				if( $this->get_id_typ() != $last_typ ){
					$last_typ = $this->get_id_typ();
					$vypis=$vypis."<option value=\"".$this->get_id_typ().":\"";			
					//kontrola zda nebyla minule vybrana tato polozka
						if($this->selected_typ == $this->get_id_typ() and $this->selected_podtyp==0){
                                                    $vypis=$vypis." selected=\"selected\" class=\"selected\"";
                                                    $this->selected .= $this->get_nazev_typ().", ";
						}else if(is_array($this->array_typ_podtyp) and in_array($this->get_id_typ().":",$this->array_typ_podtyp)){
                                                    $vypis=$vypis." selected=\"selected\" class=\"selected\"";
                                                    $this->selected .= $this->get_nazev_typ().", ";
                                                }			
					$vypis=$vypis." >".$this->get_nazev_typ()."</option>\n";
				}
				//pokud mame podtyp, vypiseme typ i s nim
				if($this->get_id_podtyp()!=""){
					$vypis=$vypis."<option value=\"".$this->get_id_typ().":".$this->get_id_podtyp()."\"";	
					//kontrola zda nebyla minule vybrana tato polozka
						if($this->selected_typ == $this->get_id_typ() and $this->selected_podtyp == $this->get_id_podtyp() ){
							$vypis=$vypis." selected=\"selected\" class=\"selected\"";
                                                        $this->selected .= $this->get_nazev_podtyp().", ";
						}else if(is_array($this->array_typ_podtyp) and in_array($this->get_id_typ().":".$this->get_id_podtyp(),$this->array_typ_podtyp)){
                                                    $vypis=$vypis." selected=\"selected\" class=\"selected\"";
                                                    $this->selected .= $this->get_nazev_podtyp().", ";
                                                }							
					$vypis=$vypis." > &nbsp; &nbsp; - ".$this->get_nazev_podtyp()."</option>\n";
				}
			}//end while
			return $vypis;
		
		}else if($typ_zobrazeni=="select_typ"){
			$last_typ="";
			$vypis="";
			while( $this->get_next_radek() ){
				//kazdy typ zobrazime i bez podtypu
				if( $this->get_id_typ() != $last_typ ){
					$last_typ = $this->get_id_typ();
					$vypis=$vypis."<option value=\"".$this->get_id_typ()."\"";			
					//kontrola zda nebyla minule vybrana tato polozka
						if($this->selected_typ == $this->get_id_typ() and $this->selected_podtyp==0){
                                                    $vypis=$vypis." selected=\"selected\" class=\"selected\"";
                                                    $this->selected .= $this->get_nazev_typ().", ";
						}else if(is_array($this->array_typ_podtyp) and in_array($this->get_id_typ().":",$this->array_typ_podtyp)){
                                                    $vypis=$vypis." selected=\"selected\" class=\"selected\"";
                                                    $this->selected .= $this->get_nazev_typ().", ";
                                                }			
					$vypis=$vypis." >".$this->get_nazev_typ()."</option>\n";
				}
			}//end while
			return $vypis;
		}    
	}	
	
	/**zjistim, zda mam opravneni k pozadovane akci*/
	function legal(){
		$zamestnanec = User_zamestnanec::get_instance();
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
				
		return $zamestnanec->get_bool_prava($id_modul,"read");
	}
	
	/*metody pro pristup k parametrum*/
	function get_id_typ() { return $this->radek["id_typ"];}
	function get_id_podtyp() { return $this->radek["id_podtyp"];}
	function get_nazev_typ() { return $this->radek["nazev_typ"];}
	function get_nazev_podtyp() { return $this->radek["nazev_podtyp"];}
        function get_selected() { return $this->selected;}
} 

?>
