<?php
/** 
* trida pro zobrazeni menu katalogu
*/
 
/*--------------------- MENU PRO KATALOG -------------------------------------------*/
class Menu_informace extends Generic_list{
	//vstupni parametry
	protected $typ;
	protected $nazev_zeme;
	protected $dalsi_podminky_zeme;
	protected $dalsi_podminky_typ;
	protected $nazev_informace;
	
	public $database; //trida pro odesilani dotazu	
//------------------- KONSTRUKTOR -----------------
	/**
	*	konstruktor tøídy 
	* @param $typ = nazev_typ_web tabulky typ_serial
	* @param $nazev_zeme = nazev_zeme_web tabulky zeme
	* @param $nazev_serialu = nazev_serial_web tabulky serial
	*/
	function __construct($typ,$nazev_zeme,$nazev_informace){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->typ = $this->check($typ);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->nazev_informace = $this->check($nazev_informace);
		
		if(defined("DALSI_PODMINKY_ZEME_INFORMACE") and DALSI_PODMINKY_ZEME_INFORMACE!=""){
			$this->dalsi_podminky_zeme = DALSI_PODMINKY_ZEME_INFORMACE;
		}		
		if(defined("DALSI_PODMINKY_TYP_INFORMACE") and DALSI_PODMINKY_TYP_INFORMACE!=""){
			$this->dalsi_podminky_typ = DALSI_PODMINKY_TYP_INFORMACE;
		}		
		if(defined("ZEME_WEB") and ZEME_WEB!=""){
			$this->nazev_zeme = ZEME_WEB;
		}
		
	//ziskani serialu z databaze	
		$this->data=$this->database->query($this->create_query($this->typ,$this->nazev_zeme))
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		
	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu ze zadaneho nazvu typu a zeme*/
	function create_query($nazev_typ,$nazev_zeme){
		//funkce vytvori vsechny radky menu na jeden dotaz, do nej jsou pridavany tabulky podle toho, ktere parametry dostavame
		
		
		
		if($nazev_typ!=""){						
			//ziskam id typu informace
					if($nazev_typ== "zeme"){
						$id_typ = 1;		
										
					}else if($nazev_typ== "zajimavosti"){
						$id_typ = 2;		
										
					}else if($nazev_typ== "prakticke"){
						$id_typ = 3;						
					}
		}
		
		if($nazev_zeme!=""){
			//ziskam id zemì
			$dotaz_zeme= "select `id_zeme` from `zeme` where `nazev_zeme_web`=\"".$nazev_zeme."\"";
			$data_zeme=$this->database->query($dotaz_zeme);
			$zaznam_zeme=mysqli_fetch_array($data_zeme);
			$id_zeme=$zaznam_zeme["id_zeme"];
		}
		
		if($id_typ!=""){
			if($id_zeme!=""){
			//mam nazev zeme i typu
				$dotaz="
					SELECT DISTINCT `typ`.`typ_informace`, `zeme`.`nazev_zeme`, `zeme`.`nazev_zeme_web`,`informace`.`id_zeme`,`informace`.`id_informace`, `informace`.`nazev`  , `informace`.`nazev_web`  
					FROM `informace` as `typ`
					left join (`informace` 
						join `zeme` on (`zeme`.`id_zeme`=`informace`.`id_zeme`)						
					)on (`informace`.`id_zeme` = ".$id_zeme." and `typ`.`typ_informace` = `informace`.`typ_informace` and `informace`.`typ_informace` = ".$id_typ." ".$this->dalsi_podminky_zeme.")					
					WHERE 1  ".$this->dalsi_podminky_typ."
					ORDER BY `typ`.`typ_informace` , `zeme`.`nazev_zeme`,`informace`.`nazev` 
					";		

				
					/*SELECT DISTINCT `typ`.`typ_informace`, `destinace`.`nazev_destinace`, `destinace`.`id_destinace` ,`zeme`.`nazev_zeme`, `zeme`.`nazev_zeme_web`,`informace`.`id_zeme`,`informace`.`id_informace`, `informace`.`nazev`  , `informace`.`nazev_web`  
					FROM `informace` as `typ`
					left join (`zeme` 
						left join `destinace` on (`destinace`.`id_zeme` = `zeme`.`id_zeme`  )
						left join `informace` on ( `informace`.`id_zeme` = ".$id_zeme." and `informace`.`typ_informace` = ".$id_typ." ".$this->dalsi_podminky_zeme.")		
					)on (`typ`.`typ_informace` = `informace`.`typ_informace` and `zeme`.`id_zeme`=`typ`.`id_zeme` and `destinace`.`id_destinace`=`typ`.`id_destinace`)
								
					WHERE 1 ".$this->dalsi_podminky_typ."
					ORDER BY `typ`.`typ_informace` , `zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`,`informace`.`nazev` 
					";*/
			}else{
			//mam pouze nazev typu
				$dotaz="
					SELECT DISTINCT `typ`.`typ_informace` ,`zeme`.`nazev_zeme`, `zeme`.`nazev_zeme_web`,`informace`.`id_zeme` 
					FROM `informace` as `typ`
					left join (`informace` 
						join `zeme` on (`zeme`.`id_zeme`=`informace`.`id_zeme`)
						left join `destinace` on (`destinace`.`id_destinace`=`informace`.`id_destinace`)
					)on (`typ`.`typ_informace` = `informace`.`typ_informace` and `informace`.`typ_informace` = ".$id_typ." ".$this->dalsi_podminky_zeme.")					
					WHERE 1  ".$this->dalsi_podminky_typ."
					ORDER BY `typ`.`typ_informace` , `zeme`.`nazev_zeme` 
					";			
			
			}
		}else{
			if($id_zeme!=""){
			//mam pouze nazev zeme 
				$dotaz="
					SELECT DISTINCT `informace`.`typ_informace` 
					FROM `informace`
					WHERE `informace`.`id_zeme` = ".$id_zeme." ".$this->dalsi_podminky_zeme."
					ORDER BY `informace`.`typ_informace` 
					";
			}else{			
			//nemam ani nazev typu ani zeme
				$dotaz="
					SELECT DISTINCT `informace`.`typ_informace`  
					FROM `informace` 
					WHERE 1  ".$this->dalsi_podminky_zeme."
					ORDER BY `informace`.`typ_informace` 
					";		
			}		
		}
		
			
		//echo $dotaz;
		return $dotaz;
	}	
	
	/** vypis menu katalogu*/
	function show_menu(){
		$last_typ="";
		$last_zeme="";
		$last_destinace="";
		$menu="<ul>";
			while($this->get_next_radek()){					
				//mame novy typ zajezdu
				if($this->radek["typ_informace"]!=$last_typ){
					//zjisteni typu informace
					if($this->radek["typ_informace"]==1){
						$nazev_typ = "".ZEME." - informace";
						$nazev_typ_web = "zeme";						
					}else if($this->radek["typ_informace"]==2){
						$nazev_typ = "Lázeòská místa";
						$nazev_typ_web = "zajimavosti";						
					}else if($this->radek["typ_informace"]==3){
						$nazev_typ = "Praktické informace";
						$nazev_typ_web = "prakticke";						
					}
				
					$last_zeme="";
					$last_typ=$this->radek["typ_informace"];
					$menu=$menu."<li>
								<a href=\"".$this->get_adress(array( $nazev_typ_web),0,"informace")."\">
								".$nazev_typ."</a></li>";
				}
				//mame novy typ zeme
				//pokud mame jen jednu zemi, nezobrazovat
				
				if($this->radek["id_zeme"]!="" and $this->radek["id_zeme"]!=$last_zeme){
					$last_zeme=$this->radek["id_zeme"];
					$menu=$menu."<li class=\"level2\">
								<a href=\"".$this->get_adress(array( $nazev_typ_web, $this->radek["nazev_zeme_web"]),0,"informace")."\">
								".$this->radek["nazev_zeme"]."</a></li>";
				}
				//máme novou destinaci
				if($this->radek["id_destinace"]!="" and $this->radek["id_destinace"]!=$last_destinace){
					$last_destinace=$this->radek["id_destinace"];
					$menu=$menu."<li class=\"level_destinace\">
								<a href=\"".$this->get_adress(array( $nazev_typ_web, $this->radek["nazev_zeme_web"]),0,"informace")."?destinace=".$this->radek["id_destinace"]."\">
								".$this->radek["nazev_destinace"]."</a></li>";
				}				
				//pole serialu je neprazdne
				if($this->radek["id_informace"]!=""){
					if( $adresa_zobrazit !== false ){
						$menu=$menu."<li class=\"level3\">
								<a href=\"".$this->get_adress(array($nazev_typ_web, $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]),0,"informace")."\"
								".(($_GET["lev3"]==$this->radek["nazev_web"])?(" class=\"open\" "):(" ")).">
								".$this->radek["nazev"]."</a></li>";
					}
				}
			
			}

		$menu=$menu."</ul>";
		return $menu;
	}

} 




?>
