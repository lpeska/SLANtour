<?php
/** 
* trida pro zobrazeni menu katalogu
*/
 
/*--------------------- MENU PRO KATALOG -------------------------------------------*/
class Menu_serialy extends Generic_list{
	//vstupni parametry
	protected $typ;
	protected $nazev_zeme;
	protected $destinace;
	
	public $database; //trida pro odesilani dotazu	
//------------------- KONSTRUKTOR -----------------
	/**
	*	konstruktor tøídy 
	* @param $typ = nazev_typ_web tabulky typ_serial
	* @param $nazev_zeme = nazev_zeme_web tabulky zeme
	* @param $nazev_serialu = nazev_serial_web tabulky serial
	*/
	function __construct($typ,$nazev_zeme,$destinace){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->typ = $this->check($typ);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->destinace = $this->check_int($destinace);
		
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
				 SELECT distinct `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,
				 						`foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`,
                            `typ_serial`.`nazev_typ_web`,`zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`, `destinace_serial`.`id_destinace` , `destinace`.`nazev_destinace`
             FROM `serial` 		join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join

                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
                    `typ_serial`  on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
 							 JOIN (
								`destinace_serial`
								join `destinace` ON ( `destinace_serial`.`id_destinace` = `destinace`.`id_destinace` )
							) ON (`destinace`.`id_zeme` =`zeme`.`id_zeme` and `destinace_serial`.`id_serial` = `serial`.`id_serial` AND `destinace_serial`.`polozka_menu` =1 )
							left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
						  							
                WHERE (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) and
                    `destinace_serial`.`id_destinace`=".$this->destinace." and `serial`.`id_typ` =".$id_typ." 
					 ORDER BY `serial`.`nazev`
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

				//máme novou destinaci
				if($this->radek["id_destinace"]!="" and $this->radek["id_destinace"]!=$last_destinace){
					$last_destinace=$this->radek["id_destinace"];
					$menu=$menu."<li class=\"main\">
								<a href=\"".$this->get_adress(array($adresa_katalog, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"]))."?destinace=".$this->radek["id_destinace"]."\">
								Ubytování - ".$this->radek["nazev_destinace"]."</a></li>";
				}			
				//pole serialu je neprazdne
				if($this->radek["id_serial"]!=""){
					if( $adresa_zobrazit !== false ){
						$menu=$menu."<li style=\"clear:left;min-height:22px;overflow:visible\">
								<a style=\"min-height:22px;\" href=\"".$this->get_adress(array($adresa_zobrazit, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]))."\"
								".(($_GET["lev3"]==$this->radek["nazev_web"])?(" class=\"open\" "):(" ")).">
								<img	src=\"/".ADRESAR_MINIIKONA."/".$this->radek["foto_url"]."\" 
                                            alt=\"".$this->radek["nazev_foto"]." - ".$this->radek["popisek_foto"]."\"
                                            style=\"float:left;margin:2px 2px 2px 0px;padding:0;\"  width=\"28\" height=\"20\"/>
														  
								".$this->radek["nazev"]."</a></li>";
					}
				}				
			}
		}
		$menu=$menu."</ul>";
		return $menu;
	}

} 




?>
