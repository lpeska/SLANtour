<?php
/** 
* trida pro zobrazení seznamu dokumentù seriálu
*/
/*------------------- SEZNAM DOKUMENTU -------------------  */
/*rozsireni tridy Serial o seznam dokumentu*/
class Seznam_dokumentu extends Generic_list{
	protected $id_serialu;
	protected $pocet_radku;
	
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì id serialu*/
	function __construct($id_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->id_serialu = $this->check_int($id_serialu);

	//ziskani zajezdu z databaze	
		$this->data=$this->database->query( $this->create_query() )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_radku=mysqli_num_rows($this->data);
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho id serialu*/
	function create_query(){
		$dotaz ="select `serial`.`id_serial`, 
							`dokument`.`id_dokument`,`dokument`.`nazev_dokument`,
							`dokument`.`popisek_dokument`, `dokument`.`dokument_url` 
					from `serial` join
					`dokument_serial` on (`dokument_serial`.`id_serial` = `serial`.`id_serial`) join
					`dokument` on (`dokument`.`id_dokument` =`dokument_serial`.`id_dokument`) 
					where `serial`.`id_serial`= ".$this->id_serialu." order by `dokument`.`id_dokument` ";
		//echo $dotaz;
		return $dotaz;
	}	
	/**zobrazeni prvku seznamu dokumentù*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="seznam"){	
			$vystup="<li class=\"dokumenty\">
						<a onmousedown=\"log_doc_click(event, '". $_SERVER["REQUEST_URI"]."');\"  href=\"https://www.slantour.cz/".ADRESAR_DOKUMENT."/".$this->get_dokument_url()."\">
							".$this->get_nazev_dokument()."
						</a> - ".$this->get_popisek_dokument()."
						</li>";
			return $vystup;
		}
	}	
	/*metody pro pristup k parametrum*/
	function get_id_dokument() { return $this->radek["id_dokument"];}
	function get_nazev_dokument() { return $this->radek["nazev_dokument"];}
	function get_popisek_dokument() { return $this->radek["popisek_dokument"];}
	function get_dokument_url() { return $this->radek["dokument_url"];}
	function get_pocet_radku() { return $this->pocet_radku;}
}



?>
