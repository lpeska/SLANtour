<?php
/** 
* trida pro zobrazeni menu katalogu
*/
 
/*--------------------- MENU PRO KATALOG -------------------------------------------*/
class Menu_destinace extends Generic_list{
	//vstupni parametry
	protected $typ;
	protected $nazev_zeme;
	protected $nazev_serialu;
	
	public $database; //trida pro odesilani dotazu	
//------------------- KONSTRUKTOR -----------------
	/**
	*	konstruktor tøídy 
	* @param $typ = nazev_typ_web tabulky typ_serial
	* @param $nazev_zeme = nazev_zeme_web tabulky zeme
	* @param $nazev_serialu = nazev_serial_web tabulky serial
	*/
	function __construct($typ,$nazev_zeme,$nazev_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->typ = $this->check($typ);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->nazev_serialu = $this->check($nazev_serialu);
		
	//ziskani serialu z databaze	
		$this->data=$this->database->query($this->create_query($this->typ,$this->nazev_zeme))
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		
	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu ze zadaneho nazvu typu a zeme*/
	function create_query($nazev_typ,$nazev_zeme){
		//funkce vytvori vsechny radky menu na jeden dotaz, do nej jsou pridavany tabulky podle toho, ktere parametry dostavame
		if($nazev_typ!=""){
			//ziskam id typu serialu
			$dotaz_typ= "select `id_typ` from `typ_serial` where `nazev_typ_web`=\"".$nazev_typ."\"";
			$data_typ=$this->database->query($dotaz_typ);
			$zaznam_typ=mysqli_fetch_array($data_typ);
			$id_typ=$zaznam_typ["id_typ"];
		}
		if($nazev_zeme!=""){
			//ziskam id zemì
			$dotaz_zeme= "select `id_zeme` from `zeme` where `nazev_zeme_web`=\"".$nazev_zeme."\"";
			$data_zeme=$this->database->query($dotaz_zeme);
			$zaznam_zeme=mysqli_fetch_array($data_zeme);
			$id_zeme=$zaznam_zeme["id_zeme"];
		}
		if($id_typ!="" and $id_zeme!=""){
			//mam nazev zeme i typu
				$dotaz="
					SELECT DISTINCT `typ_serial`.`id_typ` , `typ_serial`.`nazev_typ` , `typ_serial`.`nazev_typ_web` , `cross_zeme`.`id_zeme`,  `cross_zeme`.`nazev_zeme` , `cross_zeme`.`nazev_zeme_web` , `destinace_serial`.`id_destinace` , `destinace`.`nazev_destinace` 
					FROM `typ_serial`

					JOIN (
						`zeme_serial` AS `cross_zeme_serial` 
						JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme` and `cross_zeme`.`id_zeme` =".$id_zeme." )
						join `serial` AS `cross_serial`  ON ( `cross_serial`.`id_serial` = `cross_zeme_serial`.`id_serial`  and `cross_serial`.`jazyk` != \"english\")
						join `zajezd` AS `cross_zajezd` ON ( `cross_serial`.`id_serial` = `cross_zajezd`.`id_serial` and  `cross_zajezd`.`do` >=\"".Date("Y-m-d")."\")
 							 JOIN (
								`destinace_serial`
								join `destinace` ON ( `destinace_serial`.`id_destinace` = `destinace`.`id_destinace` )
							) ON ( `destinace_serial`.`id_serial` = `cross_serial`.`id_serial` AND `destinace_serial`.`polozka_menu` =1 )
					) ON ( `typ_serial`.`id_typ` = `cross_serial`.`id_typ` AND `cross_serial`.`id_typ` =".$id_typ." )
					WHERE `typ_serial`.`id_nadtyp` =0 
					ORDER BY `destinace`.`nazev_destinace`
					";	
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
		$core = Core::get_instance();
		$adresa_katalog = $core->get_adress_modul_from_typ("katalog");
		$adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
		if( $adresa_katalog !== false ){		
			while($this->get_next_radek()){

				//mame novy typ zeme
				if($this->radek["id_zeme"]!="" and $this->radek["id_zeme"]!=$last_zeme){
					$last_zeme=$this->radek["id_zeme"];
					$menu=$menu."<li class=\"main\">
								<a href=\"".$this->get_adress(array($adresa_katalog, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"]))."\">
								".$this->radek["nazev_zeme"]." - destinace</a></li>";
				}
				//máme novou destinaci
				if($this->radek["id_destinace"]!="" and $this->radek["id_destinace"]!=$last_destinace){
					$last_destinace=$this->radek["id_destinace"];
					$menu=$menu."<li>
								<a href=\"".$this->get_adress(array($adresa_katalog, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"]))."?destinace=".$this->radek["id_destinace"]."\">
								".$this->radek["nazev_destinace"]."</a></li>";
				}			
			}
		}
		$menu=$menu."</ul>";
		return $menu;
	}

} 




?>
