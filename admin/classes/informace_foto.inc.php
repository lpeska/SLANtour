<?php
/** 
* informace_foto.inc.php - trida pro zobrazeni seznamu fotek informací 
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM fotografii -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Foto_informace extends Generic_list{
	protected $typ_pozadavku;
	protected $id_informace;
	protected $id_zamestnance;
	protected $id_foto;
	protected $zakladni_foto;
	protected $zakladni_pro_typ;
        
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_informace,$id_foto="",$zakladni_foto="",$zakladni_pro_typ=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_informace = $this->check_int($id_informace);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_foto = $this->check_int($id_foto);
		$this->zakladni_foto = $this->check_int($zakladni_foto);
		$this->zakladni_pro_typ = $this->check_int($zakladni_pro_typ);
		//pokud mam dostatecna prava pokracovat
		if( $this->legal($this->typ_pozadavku) ){
			//na zaklade typu pozadavku vytvorim dotaz
				
				//pokud chceme vytvorit zakladnifoto, nejdriv vsechny oznacime za nezakladni
				if(($this->typ_pozadavku=="update" or $this->typ_pozadavku=="create") and $this->zakladni_foto==1){
					$remove_zakladni=$this->database->query($this->create_query("remove_zakladni_foto"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				}
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
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show"){
			$dotaz="select `foto_informace`.`id_informace`, `foto_informace`.`zakladni_foto`,`foto_informace`.`zakladni_pro_typ`,
							`foto`.`id_foto`,	`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`,
                                                        `typ_serial`.`id_typ`, `typ_serial`.`id_nadtyp`, `typ_serial`.`nazev_typ`, `typ_serial`.`nazev_typ_web`
					  from  `foto_informace` join
						`foto` on (`foto`.`id_foto` =`foto_informace`.`id_foto`) 
                                                    left join
                                                `typ_serial` on (`foto_informace`.`zakladni_pro_typ` = `typ_serial`.`id_typ`)
					where `foto_informace`.`id_informace`= ".$this->id_informace." 
					order by `foto_informace`.`zakladni_foto` desc,`foto`.`id_foto` ";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="remove_zakladni_foto"){
			$dotaz= "UPDATE `foto_informace` 
								SET `zakladni_foto`=0
								WHERE `id_informace`=".$this->id_informace." and `zakladni_foto`=1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `foto_informace`
							(`id_informace`,`id_foto`,`zakladni_foto`,`zakladni_pro_typ`)
						VALUES
							(".$this->id_informace.",".$this->id_foto.",".$this->zakladni_foto.",".$this->zakladni_pro_typ.");";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku=="update"){
			$dotaz=$dotaz. "UPDATE `foto_informace` 
						SET `zakladni_foto`=".$this->zakladni_foto.",
                                                    `zakladni_pro_typ`=".$this->zakladni_pro_typ."
						WHERE `id_informace`=".$this->id_informace." and `id_foto`=".$this->id_foto."
						LIMIT 1;";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `foto_informace` 
						WHERE `id_informace`=".$this->id_informace." and `id_foto`=".$this->id_foto."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `informace` 
						WHERE `id_informace`=".$this->id_informace."
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
				
	/**zobrazi hlavicku k seznamu fotografií*/
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

	/**zobrazi prvek  seznamu fotek*/	
	function show_list_item($typ_zobrazeni){
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
			//pokud fotka neni zakladni, dame do menu moznost ji vytvorit
			if(!$this->get_zakladni_foto()){                                
				$zakl_foto="<a href=\"".$adresa_modulu."?id_informace=".$this->get_id_informace()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=update&amp;zakladni_foto=1\">zmìnit na základní foto</a> | ";
                                if($this->radek["zakladni_pro_typ"]){
                                    $text_zakladni = "<b style=\"color:blue\">Základní pro ".$this->radek["nazev_typ"]."</b>";                                    
                                }else{
                                    $text_zakladni="";
                                }
                                
                        }else{
                                $text_zakladni = "<b style=\"color:green;\">Základní foto</b>";                                
				$zakl_foto="<a href=\"".$adresa_modulu."?id_informace=".$this->get_id_informace()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=update&amp;zakladni_foto=0\">odznaèit základní</a> | ";
			}
			$dotaz = "SELECT distinct `nazev_typ`,`id_typ`
                            FROM `typ_serial`
                            where `id_nadtyp`=0 order by `nazev_typ` ";

                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                        while($zaznam = mysqli_Fetch_Array($data)){
                                $option_zakladni_pro_typ .= "<option value=\"".$zaznam["id_typ"]."\" >".$zaznam["nazev_typ"]."</option>";
                        }
                        $form_zakladni_pro_typ = "
                            <form method=\"get\" action=\"".$adresa_informace."\">
                                 <input type=\"hidden\" name=\"id_informace\" value=\"".$this->get_id_informace()."\" />
                                 <input type=\"hidden\" name=\"id_foto\" value=\"".$this->get_id_foto()."\" />
                                 <input type=\"hidden\" name=\"typ\" value=\"foto\" />
                                 <input type=\"hidden\" name=\"pozadavek\" value=\"update\" />
                                 <input type=\"hidden\" name=\"zakladni_foto\" value=\"0\" />
                                     
                                Zmìnit na základní pro typ: <select name=\"zakladni_pro_typ\">
                                 ".$option_zakladni_pro_typ."
                                 </select>
                                 <input type=\"submit\" value=\"&gt;&gt;\"/>
                            </form>";
                        
			$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									  width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"nazev\">".$this->get_id_foto()."</td>						
							<td class=\"nazev\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto().$text_zakladni."</td>
							<td class=\"menu\">
								".$zakl_foto."
							  <a href=\"".$adresa_modulu."?id_informace=".$this->get_id_informace()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=delete\">odebrat</a> <br/>
                                                              ".$form_zakladni_pro_typ."
							</td>
						</tr>";
			return $vypis;
		}
	}	

	/*metody pro pristup k parametrum*/
	function get_id_informace() { return $this->radek["id_informace"];}
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
		}else if($this->id_informace == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}		
} 

?>
