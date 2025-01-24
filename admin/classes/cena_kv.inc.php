<?php
/** 
* serial_cena.inc.php - trida pro zobrazeni seznamu cen serialu v administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM cen seriálu -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Cena_kv extends Generic_list{
    const MENA_BEZ_PREPOCTU = "42";
        public $data;
	protected $typ_pozadavku;
	protected $pocet;	
	protected $id_zamestnance;
	protected $id_ceny;
	protected $id_serial;

	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serial,$id_ceny="",$id_cena_promenna=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
                

		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_zamestnance = $this->check_int($id_zamestnance);		
		$this->id_serial = $this->check_int($id_serial);
		$this->id_ceny = $this->check_int($id_ceny);
                $this->id_cena_promenna = $this->check_int($id_cena_promenna);
		$this->pocet = $this->check_int($pocet);
		
		//pokud mam dostatecna prava pokracovat

                if($this->typ_pozadavku=="load_cena_promenna"){                            
			$dotaz="select *
				from `cena_promenna` 
				where `id_cena`= ".$this->id_ceny." 
			 ";
                        $data=$this->database->query($dotaz)
                              or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
			$this->data = $data;
                }else if($this->typ_pozadavku=="load_cenova_mapa"){
			$dotaz="select *
				from `cena_promenna_cenova_mapa` 
				where `id_cena_promenna`= ".$this->id_cena_promenna." 
			 ";  
                        $data=$this->database->query($dotaz)
                              or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        $this->data = $data;
                
                }			

	}	
        function create_cena_promenna($id_cena,$row_kv){
            if($row_kv["fixni_castka"]==""){
                $row_kv["fixni_castka"] = "NULL";
            }
            if($row_kv["flight_from"]==""){
                $row_kv["flight_from"] = "NULL";
            }else{
                $row_kv["flight_from"] = "\"".$row_kv["flight_from"]."\"";
            }
            if($row_kv["flight_to"]==""){
                $row_kv["flight_to"] = "NULL";
            }else{
                $row_kv["flight_to"] = "\"".$row_kv["flight_to"]."\"";
            }
            if($row_kv["flight_direct"]==""){
                $row_kv["flight_direct"] = "NULL";
            }
            $dotaz="INSERT INTO `cena_promenna`
                    ( `id_cena`, `id_vzorec`, `nazev_promenne`, `typ_promenne`, `id_mena`, 
                    `fixni_castka`, `flight_from`, `flight_to`, `flight_direct`) 
                    VALUES ($id_cena,".$row_kv["id_vzorec"].",\"".$row_kv["nazev_promenne"]."\",
                        \"".$row_kv["typ_promenne"]."\",".$row_kv["id_mena"].",".$row_kv["fixni_castka"].",
                        ".$row_kv["flight_from"].",".$row_kv["flight_to"].",".$row_kv["flight_direct"].")                
            ";
            $dt = $this->database->query($dotaz);
            return mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
        }
        function create_cena_promenna_cenova_mapa($id_cena_promenna,$row_kv){
            $dotaz="INSERT INTO `cena_promenna_cenova_mapa`
                    (`id_cena_promenna`, `termin_od`, `termin_do`, `no_dates_generation`, 
                    `termin_do_shift`, `castka`, `external_id`, `poznamka`)             
                    VALUES ($id_cena_promenna,\"".$row_kv["termin_od"]."\",\"".$row_kv["termin_do"]."\",
                        ".$row_kv["no_dates_generation"].",".$row_kv["termin_do_shift"].",".$row_kv["castka"].",
                        \"".$row_kv["external_id"]."\",\"".$row_kv["poznamka"]."\")                
            ";
            
            $dt = $this->database->query($dotaz);
            return mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
        }        
} 

?>
