<?php
/** 
* trida pro zobrazení seznamu fotografií seriálu
*/
/*------------------- SEZNAM fotografii -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Seznam_fotografii extends Generic_list{
	protected $id;
        protected $i;
	protected $typ_pozadavku;
	
	public $database; //trida pro odesilani dotazu
		
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor třídy na základě id serialu*/
	function __construct($typ_pozadavku, $id){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();			
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->id = $this->check_int($id);
                $this->i = 0;	
	//ziskani zajezdu z databaze	
		$this->data=$this->database->query($this->create_query($this->typ_pozadavku) )
		 	or $this->chyba("Chyba při dotazu do databáze");
		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku){
        //MARTIN - takhle ten dotaz srovna kazdy union zvlast, takze pokud jsou fotky u objektu zakladni foto nemusi byt (mozna dokonce nemuze byt) prvni
		if($typ_pozadavku == "serial"){
			$dotaz= "(SELECT `foto_serial`.`id_serial`, `foto_serial`.`zakladni_foto`,
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url` 
						FROM `foto_serial` JOIN
							`foto` on (`foto`.`id_foto` =`foto_serial`.`id_foto`) 
						WHERE `foto_serial`.`id_serial`= ".$this->id." 
						ORDER BY `foto_serial`.`zakladni_foto` desc,`foto`.`id_foto` )
                                                union 
                               (  SELECT `serial`.`id_serial`,`foto_objekty`.`zakladni_foto`,
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`
						FROM `serial` join
                                                    `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                                                    `foto_objekty` on (`objekt_serial`.`id_objektu` = `foto_objekty`.`id_objektu`) JOIN
							`foto` on (`foto`.`id_foto` =`foto_objekty`.`id_foto`)
						WHERE `serial`.`id_serial`= ".$this->id."  
                                                ORDER BY `foto_objekty`.`zakladni_foto` desc,`foto`.`id_foto` ) ORDER BY `zakladni_foto` desc
                                                ";
//			echo $dotaz;
			return $dotaz;
                }else if($typ_pozadavku == "serial_suppress_object_foto"){
			$dotaz= "(SELECT `foto_serial`.`id_serial`, `foto_serial`.`zakladni_foto`,
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`, 1 as `foto_from_serial` 
						FROM `foto_serial` JOIN
							`foto` on (`foto`.`id_foto` =`foto_serial`.`id_foto`) 
						WHERE `foto_serial`.`id_serial`= ".$this->id." 
						ORDER BY `foto_serial`.`zakladni_foto` desc,`foto`.`id_foto` )
                                                union 
                               (  SELECT `serial`.`id_serial`,`foto_objekty`.`zakladni_foto`,
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`, 0 as `foto_from_serial` 
						FROM `serial` join
                                                    `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                                                    `foto_objekty` on (`objekt_serial`.`id_objektu` = `foto_objekty`.`id_objektu`) JOIN
							`foto` on (`foto`.`id_foto` =`foto_objekty`.`id_foto`)
						WHERE `serial`.`id_serial`= ".$this->id."  
                                                ORDER BY `foto_objekty`.`zakladni_foto` desc,`foto`.`id_foto` ) ORDER BY `foto_from_serial` desc, `zakladni_foto` desc
                                                ";
//			echo $dotaz;
			return $dotaz;
                }else if($typ_pozadavku == "ubytovani"){
			$dotaz= "SELECT `foto_objekty`.`id_objektu`,`foto_objekty`.`zakladni_foto`,
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`
						FROM `foto_objekty` JOIN
							`foto` on (`foto`.`id_foto` =`foto_objekty`.`id_foto`)
						WHERE `foto_objekty`.`id_objektu`= ".$this->id."
						ORDER BY `foto_objekty`.`zakladni_foto` desc,`foto`.`id_foto` ";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku == "informace"){
			$dotaz= "SELECT `foto_informace`.`id_informace`,`foto_informace`.`zakladni_foto`, 
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url` 
						FROM `foto_informace` JOIN
							`foto` on (`foto`.`id_foto` =`foto_informace`.`id_foto`) 
						WHERE `foto_informace`.`id_informace`= ".$this->id." 
						ORDER BY `foto_informace`.`zakladni_foto` desc,`foto`.`id_foto` ";
			//echo $dotaz;
			return $dotaz;
		}
	}	
	/**zobrazeni prvku seznamu fotografií*/
		function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="nahled"){                        
			$vystup="<td colspan=\"2\">
                                <a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\"
							class=\"preview highslide\"  onclick=\"return hs.expand(this)\"
							title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" >
							<img class=\"round_fotoleft\"
									src=\"https://www.slantour.cz/".ADRESAR_NAHLED."/".$this->get_foto_url()."\"
									width=\"286\"  style=\"margin:3px 5px 0px 5px;padding:0;\"
									alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\"
									title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" />
						</a> 
                                <div class=\"highslide-caption\">
                                    ".$this->get_nazev_foto().$this->get_popisek_foto()."
                                </div>            
                                    </td>             ";
			return $vystup;
		}else if($typ_zobrazeni=="nahled_hotel"){
			$vystup="
					<div class=\"prvni_foto_hotel\">
					<a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\"
							class=\"preview highslide\"  onclick=\"return hs.expand(this)\"
							title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" >
							<img  class=\"round\"	src=\"https://www.slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\"
									width=\"250\"
									alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\"
									title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" />
					</a> 
                                <div class=\"highslide-caption\">
                                    ".$this->get_nazev_foto().$this->get_popisek_foto()."
                                </div>                                                
					</div>";
			return $vystup;
		}else if($typ_zobrazeni=="dalsi_foto"){
                    if($this->i % 2 ==0){
                        $vystup = "<tr><td>";
                        $margin = "3px 0px 0px 3px;";
                    }else{
                        $vystup = "<td style=\"margin:\">";
                        $margin = "3px 3px 0px 5px;";
                    }
                    $this->i++;
			$vystup.="           <a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\"
							class=\"preview highslide\"  onclick=\"return hs.expand(this)\"
							title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" >
							<img  class=\"round_fotoleft\" 	src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->get_foto_url()."\"
									alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\"
									title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" 
									width=\"138\" style=\"padding:0;margin:".$margin.";\">
							</a> 
                                <div class=\"highslide-caption\">
                                    ".$this->get_nazev_foto().$this->get_popisek_foto()."
                                </div>            
                                    ";
			return $vystup;
		}else if($typ_zobrazeni=="dalsi_foto_hotel"){
			$vystup="<div class=\"dalsi_foto_hotel\">
							<a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\"
							class=\"preview highslide\"  onclick=\"return hs.expand(this)\"
							title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" >
							<img  class=\"round\"	src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->get_foto_url()."\"
									alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\"
									title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" 
									width=\"120\" height=\"85\"/>
							</a> 
                                <div class=\"highslide-caption\">
                                    ".$this->get_nazev_foto().$this->get_popisek_foto()."
                                </div>            
                                    
						</div>";
			return $vystup;
		}else if($typ_zobrazeni=="tipy_na_zajezd"){
			$vystup="<a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\">
							<img 	class=\"float_left\"
									src=\"https://www.slantour.cz/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\"
									title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" />
						</a>";
			return $vystup;
		}else if($typ_zobrazeni=="url"){
			$vystup="https://www.slantour.cz/foto/full/".$this->get_foto_url()."";
			return $vystup;
		}
	}	
	/*metody pro pristup k parametrum*/
	function get_id_foto() { return $this->radek["id_foto"];}
	function get_zakladni_foto() { return $this->radek["zakladni_foto"];}
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { 
            if($this->radek["popisek_foto"]!=""){
                return ", ".$this->radek["popisek_foto"];
            }else{
               return $this->radek["popisek_foto"];
            }
        }
	function get_foto_url() { return $this->radek["foto_url"];}
}

?>
