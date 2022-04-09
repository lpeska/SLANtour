<?php
/** 
* trida pro zobrazeni seznamu serialu
*/
/*--------------------- SEZNAM SERIALU -----------------------------*/
/**jednodussi verze - vstupni parametry pouze typ, podtyp, zeme, zacatek vyberu a order by
	- odpovida dotazu z katalogu zajezdu
*/
class Serial_list_old extends Generic_list{
	//vstupni data
	protected $typ;
	protected $nazev_typ;
	protected $podtyp;
	protected $id_destinace;
	protected $nazev_destinace;
	protected $nazev_zeme;
	protected $nazev_zeme_cz;
	protected $zacatek;
	protected $order_by;
	protected $jen_tipy_na_zajezd;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
	
	protected $database; //trida pro odesilani dotazu	
	
//------------------- KONSTRUKTOR  -----------------	
	/**konstruktor podle specifikovaného filtru na typ, podtyp a zemi*/
	function __construct($typ, $podtyp, $nazev_zeme, $id_destinace, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU, $jen_tipy_na_zajezd=0){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
	//kontrola vstupnich dat
		$this->typ = $this->check($typ); //odpovida poli nazev_typ_web
		$this->podtyp = $this->check($podtyp);//odpovida poli nazev_typ_web
		$this->nazev_zeme = $this->check($nazev_zeme);//odpovida poli nazev_zeme_web
		$this->id_destinace = $this->check_int($id_destinace);//odpovida poli id_destinace
		$this->zacatek = $this->check($zacatek); 
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check($pocet_zaznamu); 
		$this->jen_tipy_na_zajezd = 0; // $this->check($jen_tipy_na_zajezd); //identifikator zda chceme jen zajezdy s hit_zajezd=1
		
		if($this->id_destinace){
			$this->nazev_destinace = $this->get_nazev_destinace();
		}
				
		if(defined("ZEME_WEB") and ZEME_WEB!=""){
			$this->nazev_zeme = ZEME_WEB;
		}

		if(defined("TYP_WEB") and TYP_WEB!=""){
			$this->typ = TYP_WEB;
		}
		
				
		if(defined("DALSI_PODMINKY_TYP") and DALSI_PODMINKY_TYP!=""){
			$this->dalsi_podminky_typ = DALSI_PODMINKY_TYP;
		}
		
		if($this->typ){
		//ziskani nazvu typu
			$data_typ = $this->database->query( $this->create_query("get_nazev_typ") )
		 		or $this->chyba("Chyba pøi dotazu do databáze!");
			$typ = mysqli_fetch_array($data_typ);
			$this->nazev_typ = $typ["nazev_typ"];
		}
		if($this->nazev_zeme){
		//ziskani nazvu zemì
			$data_zeme = $this->database->query( $this->create_query("get_nazev_zeme") )
		 		or $this->chyba("Chyba pøi dotazu do databáze!");
			$zeme = mysqli_fetch_array($data_zeme);
			$this->nazev_zeme_cz = $zeme["nazev_zeme"];		
		}
				
	//ziskam celkovy pocet zajezdu ktere odpovidaji
		$data_pocet=$this->database->query( $this->create_query("select_seznam",1) )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		$zaznam_pocet = mysqli_fetch_array($data_pocet);
		$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

	//ziskani seznamu z databaze	
		$this->data=$this->database->query( $this->create_query("select_seznam") )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		
	//kontrola zda jsme ziskali nejake zajezdy
		if(mysqli_num_rows($this->data)==0){
			$this->chyba("Pro zadané podmínky neexistuje žádný zájezd");
		}
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku == "select_seznam"){
			//definice jednotlivych casti dotazu
			if($this->typ!=""){
				$where_typ=" `typ_serial`.`nazev_typ_web` ='".$this->typ."' and";
			}else{
				$where_typ="";
			}
			if($this->podtyp!=""){
				$select_podtyp="`podtyp`.`nazev_typ_web` as `nazev_podtyp_web`,";
				$where_podtyp=" `podtyp`.`nazev_typ_web` ='".$this->podtyp."' and";
				$from_podtyp=" join `typ_serial`  as `podtyp` on (`serial`.`id_podtyp` = `podtyp`.`id_typ`)";
			}else{
				$where_podtyp="";
				$from_podtyp="";
				$select_podtyp="";
			}	
			if($this->nazev_zeme!=""){
				$where_zeme=" `zeme`.`nazev_zeme_web` ='".$this->nazev_zeme."' and";
			}else{
				$where_zeme=" `zeme_serial`.`zakladni_zeme`=1 and";
			}	
			if($this->id_destinace!=""){
				$from_destinace=" join `destinace_serial` on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)";
				$where_destinace=" `destinace_serial`.`id_destinace` ='".$this->id_destinace."' and";
			}else{
				$where_destinace=" ";
			}				
			if($this->jen_tipy_na_zajezd==1){
				$where_tipy=" `zajezd`.`hit_zajezd` =1 and";
			}else{
				$where_tipy=" ";
			}	
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `zajezd`.`od`";
			}		
			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$select="select count(`zajezd`.`id_zajezd`) as pocet";
				$limit="";
			}else{
				$select="select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`, 
							`zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,
							`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
							`zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
							".$select_podtyp."`typ_serial`.`nazev_typ_web`,
							 `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`,
							`foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
			}
		
			$dotaz= $select."
					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial` ) join
					`cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial`  on (`serial`.`id_typ` = `typ_serial`.`id_typ`) 
					left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
					".$from_podtyp."
					".$from_destinace."
					left join
					(`foto_serial` join
						`foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
					on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1) 

					where (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) ".$this->dalsi_podminky_typ."  and ".$where_typ.$where_podtyp.$where_zeme.$where_destinace.$where_tipy." 1
					order by ".$order."
					 ".$limit."";
			//echo $dotaz;
			return $dotaz;
			
		}else if($typ_pozadavku == "get_nazev_typ"){
			$dotaz = "select `nazev_typ`
				from `typ_serial`
				where `nazev_typ_web` = '".$this->typ."'
				limit 1
				";
			//echo $dotaz;
			return $dotaz;	
						
		}else if($typ_pozadavku == "get_nazev_zeme"){
			$dotaz =  "select `nazev_zeme`
				from `zeme`
				where `nazev_zeme_web` = '".$this->nazev_zeme."'
				limit 1
				";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku == "get_nazev_destinace"){
			$dotaz =  "select `nazev_destinace`
				from `destinace`
				where `id_destinace` = '".$this->id_destinace."'
				limit 1
				";
			//echo $dotaz;
			return $dotaz;					
		}
	}	


	
/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "datum":
				 return "`zajezd`.`od`";
    			 break;
			case "cena":
				 return "`cena_zajezd`.`castka`,`zajezd`.`od`";
   			 break;
			case "nazev":
				 return "`serial`.`nazev`,`zajezd`.`od`";
   			 break;
			case "random":
				 return "RAND()";
   			 break;
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`zajezd`.`od`";
	}
	
	function show_ikony($typ_zobrazeni){
						if (is_file("strpix/vlajky/".$this->radek["nazev_zeme_web"].".png")){
							$zeme="";
							if (is_file("strpix/typ_stravovani/".$this->radek["strava"].".png")){
								//ikona typu dopravy
								$zeme=$zeme." <img src=\"/strpix/typ_stravovani/".$this->radek["strava"].".png\" alt=\"".Serial_library::get_typ_stravy($this->radek["strava"]-1)."\" title=\"".Serial_library::get_typ_stravy($this->radek["strava"]-1)."\" height=\"12\" width=\"14\"/>";						
							}
							if (is_file("strpix/typ_dopravy/".$this->radek["doprava"].".png")){
								//ikona typu dopravy
								$zeme=$zeme." <img src=\"/strpix/typ_dopravy/".$this->radek["doprava"].".png\" alt=\"".Serial_library::get_typ_dopravy($this->radek["doprava"]-1)."\" title=\"".Serial_library::get_typ_dopravy($this->radek["doprava"]-1)."\" height=\"12\" width=\"20\"/>";						
							}
							if (is_file("strpix/typ_ubytovani/".$this->radek["ubytovani"].".png")){
								//ikona typu ubytovani
								$zeme=$zeme." <img src=\"/strpix/typ_ubytovani/".$this->radek["ubytovani"].".png\" alt=\"".Serial_library::get_typ_ubytovani($this->radek["ubytovani"]-1)."\" title=\"".Serial_library::get_typ_ubytovani($this->radek["ubytovani"]-1)."\" height=\"12\" width=\"14\"/>";						
							}								
							//vlajka zeme
							$zeme=$zeme." <img src=\"/strpix/vlajky/".$this->radek["nazev_zeme_web"].".png\" alt=\"".$this->radek["nazev_zeme"]."\" height=\"12\" width=\"18\"/>";


						}else{
							$zeme=$this->radek["nazev_zeme"];
						}
		return $zeme;						
	}

	
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="katalog"){
			if($this->suda==1){
				$vypis="<div class=\"suda\">";
				}else{
				$vypis="<div class=\"licha\">";
			}
			if($this->get_id_foto()!=""){
				$foto =	"<img	src=\"".FOTO_WEB."/".ADRESAR_IKONA."/".$this->get_foto_url()."\" 
									alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
									class=\"float_left\" width=\"120\" height=\"85\"/>";
			}else{
				$foto="";
			}
			
			$core = Core::get_instance();
			$adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
			$adresa_katalog = $core->get_adress_modul_from_typ("katalog");
			if( $adresa_katalog !== false ){//pokud existuje modul pro zpracovani		
			
					
				$vypis = $vypis."<div class=\"header\">\n<div class=\"zeme\">".$this->show_ikony($typ_zobrazeni)."</div>
							<div class=\"nazev\"><a href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
												$this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\" class=\"normal\">".$this->get_nazev()."</a></div>\n</div>\n
							<div class=\"contend\">
								".$foto."
								<div class=\"popisek\">".$this->get_nazev_zajezdu().$this->get_popisek()."</div>	
								<div>
									<div class=\"termin\">termín: <strong>".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() )."</strong></div>
									<div class=\"cena\">cena od: <strong>".$this->get_castka()." ".$this->get_mena()."</strong></div>";
				
				if( $adresa_zobrazit !== false ){				
					$vypis = $vypis."<a href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
												$this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\">další informace</a>";
				}
				
				$vypis = $vypis."
								</div>
							</div>
						</div>
						";
				return $vypis;
			}
			
		}else if($typ_zobrazeni=="tipy_na_zajezd"){
			if($this->suda==1){
				$vypis="<div class=\"suda\">";
				}else{
				$vypis="<div class=\"licha\">";
			}
			if($this->get_id_foto()!=""){
				$foto =	"<img	src=\"".FOTO_WEB."/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\" 
									alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
									class=\"float_left\" width=\"80\" height=\"55\"/>";
			}else{
				$foto="";
			}

			$core = Core::get_instance();
			$adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
			$adresa_katalog = $core->get_adress_modul_from_typ("katalog");
			if( $adresa_katalog !== false ){//pokud existuje modul pro zpracovani		
				
				$vypis = $vypis."".$foto."
								<div class=\"zeme\">".$this->show_ikony($typ_zobrazeni)."</div><div class=\"nazev\">".$this->get_nazev()."</div>\n
								<div class=\"popisek\">".$this->get_nazev_zajezdu().substr(strip_tags($this->get_popisek()),0,175)."...</div>	
								<div class=\"termin\"><strong>".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() )."</strong></div>
								<div class=\"cena\">cena od: <strong>".$this->get_castka()." ".$this->get_mena()."</strong></div>";
				
				if( $adresa_zobrazit !== false ){				
					$vypis = $vypis."<a href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
												$this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\">další informace</a>";
				}
				
				$vypis = $vypis."</div>
						";
				return $vypis;		
			}
			
		}else{
			return "";
		}		
	}	
	
	
	/**zobrazi odkazy na dalsi stranky vypisu zajezdu*/
	function show_strankovani(){
			//prvni cislo stranky ktere zobrazime
		$act_str=$this->zacatek-(10*$this->pocet_zaznamu);
		if($act_str<0){
			$act_str=0;
		}
		
		//odkaz na prvni stranku
		$vypis = "<div class=\"strankovani\"><a href=\"?str=0\" title=\"první stránka zájezdù\">&lt;&lt;</a> &nbsp;"; 
		
		//odkaz na dalsi stranky z rozsahu
		while( ($act_str <= $this->pocet_zajezdu) and ($act_str <= $this->zacatek + (10*$this->pocet_zaznamu) ) ){
			if($this->zacatek!=$act_str){
				$vypis = $vypis."<a href=\"?str=".$act_str."\" title=\"strana ".(1+($act_str/$this->pocet_zaznamu))."\">".(1+($act_str/$this->pocet_zaznamu))."</a> ";					
			}else{
				$vypis = $vypis.(1+($act_str/$this->pocet_zaznamu))." ";
			}
			$act_str=$act_str+$this->pocet_zaznamu;
		}	
		
		//odkaz na posledni stranku
		$posl_str=$this->pocet_zaznamu*floor($this->pocet_zajezdu/$this->pocet_zaznamu);
			$vypis = $vypis." &nbsp; <a href=\"?str=".$posl_str."\" title=\"poslední stránka zájezdù\">&gt;&gt;</a></div>";	
		
		return $vypis;
	}	
	
	/** vytvori text pro titulek stranky*/
	function show_titulek(){

		//tvorba vypisu titulku
		if($this->nazev_typ!="" and $this->nazev_zeme_cz!="" and $this->nazev_destinace!=""){
			return TITLE_KEYWORD.$this->nazev_destinace." | ".$this->nazev_zeme_cz." | ".$this->nazev_typ."";
		}else if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
			return TITLE_KEYWORD.$this->nazev_zeme_cz." | ".$this->nazev_typ;
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ." | Katalog zájezdù";
		}else{
			return TITLE_TOP;
		}
	}
	
	/** vytvori text pro nadpis stranky*/
	function show_nadpis(){
		//tvorba vypisu titulku
		if($this->nazev_typ!="" and $this->nazev_zeme_cz!="" and $this->nazev_destinace!=""){
			return TITLE_KEYWORD.$this->nazev_destinace.", ".$this->nazev_zeme_cz." - ".$this->nazev_typ."";
		}else if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
			return $this->nazev_zeme_cz." - ".$this->nazev_typ;
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ." - Katalog zájezdù";
		}else{
			return TITLE_KEYWORD.ZEME.", Katalog zájezdù";
		}
	}	
	
	/** vytvori text pro meta keyword stranky*/
	function show_keyword(){
		//tvorba vypisu titulku
		if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
			return TITLE_KEYWORD.$this->nazev_destinace.", ".TITLE_KEYWORD.", ".$this->nazev_zeme_cz.", ".$this->nazev_destinace.",".$this->nazev_typ."";
		}else if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
			return TITLE_KEYWORD.$this->nazev_zeme_cz.", ".TITLE_KEYWORD.", ".$this->nazev_typ.", ".$this->nazev_zeme_cz."";
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ.", Katalog zájezdù";
		}else{
			return TITLE_KEYWORD.ZEME.",".ZEME.",".TITLE_KEYWORD;
		}
	}	

	/** vytvori text pro meta description stranky*/
	function show_description(){
		//tvorba vypisu titulku
		if($this->nazev_typ!="" and $this->nazev_zeme_cz!="" and $this->nazev_destinace!=""){
			return TITLE_KEYWORD.$this->nazev_destinace.", ".$this->nazev_typ.", ".$this->nazev_zeme_cz.", Katalog zájezdù";
		}else if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
			return $this->nazev_typ.", ".TITLE_KEYWORD.$this->nazev_zeme_cz.", Katalog zájezdù";
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ.", Katalog zájezdù";
		}else{
			return DESCRIPTION_TOP;
		}
	}	
		
	/*metody pro pristup k parametrum*/
	function get_nazev_destinace() { 
		if($this->id_destinace!=0){
			$data_destinace = $this->database->query( $this->create_query("get_nazev_destinace") )
		 		or $this->chyba("Chyba pøi dotazu do databáze!");
			$dest = mysqli_fetch_array($data_destinace);
			return $dest["nazev_destinace"];	
		}
	}	
	function get_nazev_zajezdu() { 
		if($this->radek["nazev_zajezdu"]!=""){
			return "<strong><i>".$this->radek["nazev_zajezdu"]."</i></strong> ";
		}
	}
	function get_id_serial() { return $this->radek["id_serial"];}
                function get_nazev() {          
                    if($this->radek["nazev_ubytovani"]!=""){
                         return $this->radek["nazev_ubytovani"]." - ".$this->radek["nazev"];
                    }else{
                         return $this->radek["nazev"];
                    }
                }
	function get_nazev_web() { return $this->radek["nazev_web"];}
                function get_popisek() { 
                    if($this->radek["popisek_ubytovani"]!=""){
                        return $this->radek["popisek"]." ".$this->radek["popisek_ubytovani"];
                    }else{
                        return $this->radek["popisek"];
                    }
                }
	
	function get_id_zajezd() { return $this->radek["id_zajezd"];}
	function get_termin_od() { return $this->radek["od"];}
	function get_termin_do() { return $this->radek["do"];}

	function get_castka() { return $this->radek["castka"];}
	function get_mena() { return $this->radek["mena"];}	

	function get_nazev_zeme() { return $this->radek["nazev_zeme"];}
	function get_nazev_zeme_web() { return $this->radek["nazev_zeme_web"];}	

	function get_id_foto() { return $this->radek["id_foto"];}
	function get_foto_url() { return $this->radek["foto_url"];}	
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { return $this->radek["popisek_foto"];}		
	
	function get_typ() { return $this->radek["nazev_typ_web"];}
	function get_podtyp() { return $this->radek["nazev_podtyp_web"];}

	function get_pocet_zajezdu(){ return $this->pocet_zajezdu;}
}



/*--------------------- PODROBNY SEZNAM SERIALU -----------------------------*/
/** trida pro zobrazeni seznamu serialu
	komplexnejsi verze - vstupni parametry jednak typ, podtyp, zeme, zacatek vyberu a order by
							- dale cena, destinace, odjezd od/do, doprava, ubytovani, stravovani
	- odpovida dotazu z podrobneho vyhledavani
*/
class Serial_list_podrobny extends Serial_list{
	//vstupni data
	protected $typ_id;
	protected $cena;
	protected $destinace;
	protected $od;
	protected $do;
	protected $doprava;
	protected $ubytovani;
	protected $stravovani;
	protected $keywords;
	//vyhledavani
	protected $podrobne_vyhledavani;

	
//------------------- KONSTRUKTOR  -----------------	
	function __construct($typ, $podtyp, $nazev_zeme, $zacatek, $order_by, $cena, $destinace, $od, $do, 
								$doprava, $ubytovani, $stravovani,$keywords, $pocet_zaznamu=POCET_ZAZNAMU,$jen_tipy_na_zajezd=0){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	//kontrola vstupnich dat
		$this->typ = $this->check($typ);
		$this->podtyp = $this->check($podtyp);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->zacatek = $this->check($zacatek);
		$this->order_by = $this->check($order_by);
		$this->jen_tipy_na_zajezd = $this->check($jen_tipy_na_zajezd);
		$this->pocet_zaznamu = $this->check($pocet_zaznamu);
		
		$this->cena = $this->check($cena);
		$this->destinace = $this->check($destinace);
		$this->od = $this->check($od);
		$this->do = $this->check($do);
		$this->doprava = $this->check($doprava);
		$this->ubytovani = $this->check($ubytovani);
		$this->stravovani = $this->check($stravovani);
		$this->keywords = $this->check($keywords);
		
		if(defined("ZEME_WEB") and ZEME_WEB!=""){
			$this->nazev_zeme = ZEME_WEB;
		}

		if(defined("TYP_ID") and TYP_ID!=""){
			$this->typ_id = TYP_ID;
		}

				
		if(defined("DALSI_PODMINKY_TYP") and DALSI_PODMINKY_TYP!=""){
			$this->dalsi_podminky_typ = DALSI_PODMINKY_TYP;
		}
		
	
	//ziskam celkovy pocet zajezdu ktere odpovidaji
		$data_pocet=$this->database->query( $this->create_query(1) )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		$zaznam_pocet = mysqli_fetch_array($data_pocet);
		$this->pocet_zajezdu = $zaznam_pocet["pocet"];			
				
	//ziskani seznamu z databaze	
		$this->data=$this->database->query( $this->create_query() )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
				
	//kontrola zda jsme ziskali nejake zajezdy
		if(mysqli_num_rows($this->data)==0){
			$this->chyba("Pro zadané podmínky neexistuje žádný zájezd");
		}
	}
	
	//zobrazi nadpis stranky
	function show_nadpis(){

		//tvorba vypisu titulku
		if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
			return "Vyhledávání zájezdù - ".$this->nazev_typ." - ".$this->nazev_zeme_cz;
		}else if($this->nazev_typ!=""){
			return "Vyhledávání zájezdù - ".$this->nazev_typ;
		}else{
			return "Vyhledávání zájezdù";
		}
	}		
	
	
//------------------- METODY TRIDY -----------------	
	/** vytvori instanci tridy podrobne_vyhledavani*/
	function create_podrobne_vyhledavani(){
		$this->podrobne_vyhledavani = new Vyhledavani($this->typ, $this->podtyp, $this->nazev_zeme, $this->order_by,
										$this->cena,$this->destinace,$this->od,$this->do,$this->doprava,$this->ubytovani,$this->stravovani,$this->keywords);
	}
	
	/** vytvoreni dotazu ze zadanych parametru*/
	function create_query($only_count=0){
		
		//definice jednotlivych casti dotazu
		if($this->typ!=""){
			$where_typ=" `typ_serial`.`nazev_typ_web` ='".$this->typ."' and";
			}else{
			$where_typ="";
		}
		if($this->podtyp!=""){
			$select_podtyp="`podtyp`.`nazev_typ_web` as `nazev_podtyp_web`,";
			$where_podtyp=" `podtyp`.`nazev_typ_web` ='".$this->podtyp."' and";
			$from_podtyp="join `typ_serial`  as `podtyp` on (`serial`.`id_podtyp` = `podtyp`.`id_typ`)";
			}else{
			$where_podtyp="";
			$from_podtyp="";
			$select_podtyp="";
		}	
		if($this->nazev_zeme!=""){
			$where_zeme=" `zeme`.`nazev_zeme_web` ='".$this->nazev_zeme."' and";
			}else{
			$where_zeme=" `zeme_serial`.`zakladni_zeme`=1 and";
		}	
		if($this->cena!=""){
			$where_cena=" `cena_zajezd`.`castka` <='".$this->cena."' and";
			}else{
			$where_cena="";
		}	
		if($this->destinace!=""){
			$where_destinace=" `destinace_serial`.`id_destinace` ='".$this->destinace."' and";
			$from_destinace=" `destinace_serial` on (`destinace_serial`.`id_serial` =`serial`.`id_serial`) join";
			}else{
			$where_destinace="";
		}	
      if($this->keywords!=""){
           $where_keywords=" (`serial`.`nazev` like \"%".$this->keywords."%\" or `serial`.`popisek` like \"%".$this->keywords."%\" or `serial`.`popis` like \"%".$this->keywords."%\" or `zeme`.`nazev_zeme` like \"%".$this->keywords."%\" ) and";
      }else{
             $where_keywords="";
      }		
                    if($this->od!=""){
                        $where_od=" (`zajezd`.`od` >\"".$this->od."\" or (`zajezd`.`do` >\"".$this->od."\" and `serial`.`dlouhodobe_zajezdy`=1)) and";
                    }else{
                        $where_od=" (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) and";
                    }
                    if($this->do!=""){
                        $where_do=" `zajezd`.`do` <='".$this->do."' and";
                    }else{
                        $where_do="";
                    }
		if($this->stravovani!=""){
			$where_stravovani=" `serial`.`strava` ='".$this->stravovani."' and";
			}else{
			$where_stravovani="";
		}	
		if($this->doprava!=""){
			$where_doprava=" `serial`.`doprava` ='".$this->doprava."' and";
			}else{
			$where_doprava="";
		}			
		if($this->ubytovani!=""){
			$where_ubytovani=" `serial`.`ubytovani` ='".$this->ubytovani."' and";
			}else{
			$where_ubytovani="";
		}	
		if($this->jen_tipy_na_zajezd==1){
			$where_tipy=" `zajezd`.`hit_zajezd` =1 and";
			}else{
			$where_tipy=" ";
		}	
		if($this->zacatek!=""){
			$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
		}else{
			$limit=" limit 0,".$this->pocet_zaznamu." ";
		}
		if($this->order_by!=""){
			$order=$this->order_by($this->order_by);
		}else{
			$order=" `zajezd`.`od`";
		}		
		//pokud chceme pouze spoèítat vsechny odpovídající záznamy
		if($only_count==1){
			$select="select count(`zajezd`.`id_zajezd`) as pocet";
			$limit="";
		}else{
			$select="select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`, 
							`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,
							`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
							`zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
							".$select_podtyp."`typ_serial`.`nazev_typ_web`,
							`ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`,
							`foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
		}
		
		$dotaz= $select."
					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					".$from_destinace."
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial`  on (`serial`.`id_typ` = `typ_serial`.`id_typ`) 
					left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
					".$from_podtyp."
					left join
					(`foto_serial` join
						`foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
					on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1) 

					where ".$where_typ.$where_podtyp.$where_zeme.$where_cena.$where_destinace.$where_od.$where_do.
							$where_stravovani.$where_doprava.$where_ubytovani.$where_keywords.$where_tipy." 1  ".$this->dalsi_podminky_typ." 
					order by ".$order."
					 ".$limit."";
		//echo $dotaz;
		return $dotaz;
	}	

	/*metody pro pristup k parametrum*/
	function get_podrobne_vyhledavani(){return $this->podrobne_vyhledavani;}
}



/*--------------------- PODROBNY SEZNAM SERIALU -----------------------------*/
/** trida pro zobrazeni seznamu serialu - zavislych na id_informace tabulce serial_informace
    - odpovida dotazu z jednotlivych informaci
*/
            class Serial_list_informace extends Serial_list{
                //vstupni data
                protected $typ_pozadavku;
                protected $id_informace;

                //------------------- KONSTRUKTOR  -----------------
                function __construct($typ_pozadavku, $id_informace, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU,$jen_tipy_na_zajezd=0){
                    //trida pro odesilani dotazu
                    $this->database = Database::get_instance();
                    //kontrola vstupnich dat
                    $this->typ_pozadavku = $this->check($typ_pozadavku);
                    $this->id_informace = $this->check($id_informace);
                    $this->zacatek = $this->check($zacatek);
                    $this->order_by = $this->check($order_by);
                    $this->jen_tipy_na_zajezd = $this->check($jen_tipy_na_zajezd);
                    $this->pocet_zaznamu = $this->check($pocet_zaznamu);


                    if($this->typ_pozadavku == "menu"){
                        $this->data=$this->database->query( $this->create_menu() )
                        or $this->chyba("Chyba pøi dotazu do databáze");

                        $this->pocet_zajezdu = mysqli_num_rows($this->data);

                    }else{
                        
                        //ziskani seznamu z databaze
                        $this->data=$this->database->query( $this->create_query() )
                        or $this->chyba("Chyba pøi dotazu do databáze");

                        $this->pocet_zajezdu = mysqli_num_rows($this->data);

                    }
                }


                //------------------- METODY TRIDY -----------------

    /** vytvoreni dotazu ze zadanych parametru*/
                function create_query($only_count=0){

                    //definice jednotlivych casti dotazu
                    if($this->id_informace!=""){
                        $where_informace=" `informace_serial`.`id_informace` =".$this->id_informace." and";
                    }else{
                        $where_informace="";
                    }

                    if($this->jen_tipy_na_zajezd==1){
                        $where_tipy=" `zajezd`.`hit_zajezd` =1 and";
                    }else{
                        $where_tipy=" ";
                    }
                    if($this->zacatek!=""){
                        $limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
                    }else{
                        $limit=" limit 0,".$this->pocet_zaznamu." ";
                    }
                    if($this->order_by!=""){
                        $order=$this->order_by($this->order_by);
                    }else{
                        $order=" `zajezd`.`od`";
                    }
  				  
							if($typ_serialu!=""){
								$where_typ="`typ_serial`.`nazev_typ_web`='".$typ_serialu."' and";
							}else{
								$where_typ=" ";
							}
													  
                    $select="select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,
                            min(`zajezd`.`od`) as `od`,`zajezd`.`id_zajezd`,`zajezd`.`do`,`zajezd`.`hit_zajezd`,
                            `cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,`zeme_serial`.`zakladni_zeme`,
                            `typ`.`nazev_typ_web`,
                            `informace_serial`.`id_informace`,
									 `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";


                    $dotaz= $select."
                    from
                    `informace_serial` join
                    `serial` on (`serial`.`id_serial` =`informace_serial`.`id_serial`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join

                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
                    `typ_serial` as `typ` on (`serial`.`id_typ` = `typ`.`id_typ`)
							left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)

                    where (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `zeme_serial`.`zakladni_zeme`=1 and ".$where_informace.$where_tipy.$where_typ." 1
                    group by `serial`.`id_serial`
                    order by ".$order."
                     ".$limit."";
                    //echo $dotaz;
                    return $dotaz;
                }
/** vytvoreni dotazu ze zadanych parametru*/
                function create_menu(){

                    //definice jednotlivych casti dotazu
                    if($this->id_informace!=""){
                        $where_informace=" `informace_serial`.`id_informace` =".$this->id_informace." and";
                    }else{
                        $where_informace="";
                    }


                    if($this->order_by!=""){
                        $order=$this->order_by($this->order_by);
                    }else{
                        $order=" `zajezd`.`od`";
                    }

                    $dotaz= 	"
                    SELECT DISTINCT `typ_serial`.`nazev_typ_web`, `zeme`.`nazev_zeme_web` ,
						  		`ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`,
                        `serial`.`id_serial` , `serial`.`nazev` , `serial`.`nazev_web`
                    FROM
                        `informace_serial` join
                        `serial` on (`serial`.`id_serial` =`informace_serial`.`id_serial`) join
                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                        `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                        `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
                        `typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
								left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
                    WHERE (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1)) and
                        `zeme_serial`.`zakladni_zeme`=1 and ".$where_informace.$where_typ." 1
                    ORDER BY `serial`.`nazev`";
                   // echo $dotaz;
                    return $dotaz;
                }
    /** vypis menu katalogu*/
                function show_menu_informace(){
                    $menu="";
                    $core = Core::get_instance();
                    $adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
                    while($this->get_next_radek()){
                        if($this->radek["id_serial"]!=""){
                            if( $adresa_zobrazit !== false ){
                                $menu=$menu."<li>
                                <a href=\"".$this->get_adress(array($adresa_zobrazit, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]))."\">
                                ".$this->get_nazev()."</a></li>";
                            }
                        }

                    }
                    $menu=$menu."";
                    return $menu;
                }

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
    function order_by($vstup){
        switch ($vstup) {
            case "datum":
                return "`zajezd`.`od`";
                break;
            case "cena":
                return "`cena_zajezd`.`castka`,`zajezd`.`od`";
                break;
                case "nazev":
                    return "`serial`.`nazev`,`zajezd`.`od`";
                    break;
                    case "random":
                        return " RAND()";
                        break;
                    }
                    //pokud zadan nespravny vstup, vratime zajezd.od
                    return "`zajezd`.`od`";
                }

            }

class Serial_list_akce extends Serial_list{
    private $type_of_query;
    private $serial_data;

    function __construct($typ, $podtyp, $nazev_zeme, $id_destinace, $zacatek, $type_of_query="akce", $pocet_zaznamu=POCET_ZAZNAMU,  $use_slevy=1, $id_serial="") {
        $this->database = Database::get_instance();
        //kontrola vstupnich dat
        $this->typ = $this->check($typ); //odpovida poli nazev_typ_web
        $this->podtyp = $this->check($podtyp);//odpovida poli nazev_typ_web
        $this->nazev_zeme = $this->check($nazev_zeme);//odpovida poli nazev_zeme_web
        $this->id_destinace = $this->check_int($id_destinace);//odpovida poli id_destinace
        $this->zacatek = $this->check($zacatek);
        $this->use_slevy = $this->check($use_slevy);
        $this->pocet_zaznamu = $this->check($pocet_zaznamu);
        $this->type_of_query = $this->check($type_of_query);
       

        //creating the query
        //definice jednotlivych casti dotazu
        if($this->typ!="") {
            $where_typ=" `typ`.`nazev_typ_web` ='".$this->typ."' and";
        }else {
            $where_typ="";
        }
        if($this->nazev_zeme!="") {
            $where_zeme=" `zeme`.`nazev_zeme_web` ='".$this->nazev_zeme."' and";
        }else {
            $where_zeme=" `zeme_serial`.`zakladni_zeme`=1 and";
        }
        if($this->id_destinace!="") {
            $from_destinace=" join `destinace_serial` on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)";
            $where_destinace=" `destinace_serial`.`id_destinace` ='".$this->id_destinace."' and";
        }else {
            $from_destinace=" ";
            $where_destinace=" ";
        }
        if($this->order_by!=""){
             $order=$this->order_by($this->order_by);
         }else{
             $order=" Rand() ";
        }
        if($this->zacatek!="") {//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
            $limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
        }else {
            $limit=" limit 0,".$this->pocet_zaznamu." ";
        }
        if(defined("DALSI_PODMINKY_TYP2") and DALSI_PODMINKY_TYP2!=""){
			$this->dalsi_podminky_typ = DALSI_PODMINKY_TYP2;
		}
                
        if(!$this->use_slevy) {
            $select="select `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,
                            `ubytovani`.`nazev` as `nazev_ubytovani`, `ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                            `zajezd`.*,
                            `typ`.`nazev_typ_web`,
                            min(`cena_zajezd`.`vyprodano`) as `vyprodano`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            	 $dotaz= $select."
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial` ) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`zakladni_cena`=1 or `cena`.`typ_ceny`=1 )) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
                    `typ_serial` as `typ` on (`serial`.`id_typ` = `typ`.`id_typ`)
                    left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
                    ".$from_podtyp."
                    ".$from_destinace."
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
		    where `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and
                        (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))
                            and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1
                             ".$this->dalsi_podminky_typ." and ".$where_typ.$where_podtyp.$where_zeme.$where_destinace.$where_tipy." 1
                    group by `zajezd`.`id_zajezd`
                    having `vyprodano` = 0
                    order by  ".$order."
                    ".$limit."";
        }
        //echo $dotaz;

       $this->data=$this->database->query( $dotaz )
        or $this->chyba("Chyba pøi dotazu do databáze");

        //kontrola zda jsme ziskali nejake zajezdy
        $this->pocet_zajezdu = mysqli_num_rows($this->data);

    }
 function show_list_item($typ_zobrazeni){
                    if($typ_zobrazeni=="akce"){
                        //component gathering info
                        //there might be some empty results
                        if($this->get_id_serial()){


                        if($this->first) {
                            $this->objects .= $this->get_id_serial();
                            $this->first=0;
                        }else {
                            $this->objects .= ",".$this->get_id_serial();
                        }

                        if($this->suda==1){
                            $vypis="<div class=\"suda\">";
                        }else{
                            $vypis="<div class=\"licha\">";
                        }

                        if($this->get_id_foto()!=""){
                            $foto =	"<img src=\"".FOTO_WEB."/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
                                    alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
                                    class=\"float_left\" width=\"80\" height=\"55\"/>";
                        }else{
                            $foto="";
                        }
                        $core = Core::get_instance();
                        $adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
                        $adresa_katalog = $core->get_adress_modul_from_typ("katalog");

                        if( $adresa_zobrazit !== false ){
                                $odkaz = "<a  href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
                                        $this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\">další informace</a>";
                        }

                        if( $adresa_katalog !== false ){//pokud existuje modul pro zpracovani
									if( strlen(strip_tags($this->get_popisek())) >= 100) {
										$pozice_popisek = strpos(strip_tags($this->get_popisek()), '.', 100);
									}else{
										$pozice_popisek = strlen(strip_tags($this->get_popisek()));
									}
									if($pozice_popisek === false) {
										$pozice_popisek = strlen(strip_tags($this->get_popisek()));
									}
                            $vypis = $vypis."".$foto."
				<div class=\"nazev_zajezdu\">".$this->get_nazev_zajezdu()."&nbsp;
                                  <br/>".$this->get_sleva()."<br/>".$odkaz."
                                </div>
                                <div class=\"zeme\">".$this->show_ikony($typ_zobrazeni)."</div>
				<div class=\"nazev\">".$this->get_nazev()."</div>\n

				<div class=\"popisek\">
                                            <b>".strip_tags($this->get_popis_akce())."</b>
                                            ".substr(strip_tags($this->get_popisek()),0,($pozice_popisek+1))."</div>
                                <div class=\"termin\"><strong>".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() )."</strong> &nbsp;
                                pøed slevou: <span style=\"color:red;text-decoration:line-through;\">".$this->get_cena_pred_akci()."</span> |
                                                    Nyní cena od: <span style=\"color:green;font-weight:bold;font-size:1.2em;\">".$this->get_akcni_cena()."</span></div>";
                            $vypis = $vypis."</div>
                        ";
                            return $vypis;
                        }
                       }
                    }else{
                        return "";
                    }

                }

            function get_cena_pred_akci() {
                    return $this->radek["cena_pred_akci"]." Kè";
                }
            function get_akcni_cena() {
                    return $this->radek["akcni_cena"]." Kè";
                }
            function get_sleva() {
                        $sleva = round ( ( 1 - ($this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) )*100);
                    return  "<span style=\"color:red;font-size:1.6em;font-weight:bold;\" title=\" Sleva až ".$sleva."% \">
                        SLEVA ".$sleva."%</span>";
            }
            function get_popis_akce() { return $this->radek["popis_akce"];}
}

				
/*--------------------- PODROBNY SEZNAM SERIALU -----------------------------*/
/** trida pro zobrazeni seznamu serialu - zavislych na id_informace tabulce serial_informace
    - odpovida dotazu z jednotlivych informaci
*/
            class Serial_list_single_zajezd extends Serial_list{
                //vstupni data


                //------------------- KONSTRUKTOR  -----------------
  	/**konstruktor podle specifikovaného filtru na typ, podtyp a zemi*/
	function __construct($typ, $podtyp, $nazev_zeme, $id_destinace, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU, $jen_tipy_na_zajezd=0){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

	//kontrola vstupnich dat
		$this->typ = $this->check($typ); //odpovida poli nazev_typ_web
		$this->podtyp = $this->check($podtyp);//odpovida poli nazev_typ_web
		$this->nazev_zeme = $this->check($nazev_zeme);//odpovida poli nazev_zeme_web
		$this->id_destinace = $this->check_int($id_destinace);//odpovida poli id_destinace
		$this->zacatek = $this->check($zacatek);
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check($pocet_zaznamu);
		$this->jen_tipy_na_zajezd = $this->check($jen_tipy_na_zajezd); //identifikator zda chceme jen zajezdy s hit_zajezd=1

		if(defined("ZEME_WEB") and ZEME_WEB!=""){
			$this->nazev_zeme = ZEME_WEB;
		}

		if(defined("TYP_WEB") and TYP_WEB!=""){
			$this->typ = TYP_WEB;
		}


		if(defined("DALSI_PODMINKY_TYP") and DALSI_PODMINKY_TYP!=""){
			$this->dalsi_podminky_typ = DALSI_PODMINKY_TYP;
		}

		if($this->typ){
		//ziskani nazvu typu
			$data_typ = $this->database->query( $this->create_query("get_nazev_typ") )
		 		or $this->chyba("Chyba pøi dotazu do databáze!");
			$typ = mysqli_fetch_array($data_typ);
			$this->nazev_typ = $typ["nazev_typ"];
		}
		if($this->nazev_zeme){
		//ziskani nazvu zemì
			$data_zeme = $this->database->query( $this->create_query("get_nazev_zeme") )
		 		or $this->chyba("Chyba pøi dotazu do databáze!");
			$zeme = mysqli_fetch_array($data_zeme);
			$this->nazev_zeme_cz = $zeme["nazev_zeme"];
		}

	//ziskam celkovy pocet zajezdu ktere odpovidaji
		$data_pocet=$this->database->query( $this->create_query("select_seznam",1) )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		$zaznam_pocet = mysqli_fetch_array($data_pocet);
		$this->pocet_zajezdu = $zaznam_pocet["pocet"];

	//ziskani seznamu z databaze
		$this->data=$this->database->query( $this->create_query("select_seznam") )
		 	or $this->chyba("Chyba pøi dotazu do databáze");

	//kontrola zda jsme ziskali nejake zajezdy
		if(mysqli_num_rows($this->data)==0){
			$this->chyba("Pro zadané podmínky neexistuje žádný zájezd");
		}
	}


//------------------- METODY TRIDY -----------------
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku == "select_seznam"){
			//definice jednotlivych casti dotazu
			if($this->typ!=""){
				$where_typ=" `typ_serial`.`nazev_typ_web` ='".$this->typ."' and";
			}else{
				$where_typ="";
			}
			if($this->podtyp!=""){
				$select_podtyp="`podtyp`.`nazev_typ_web` as `nazev_podtyp_web`,";
				$where_podtyp=" `podtyp`.`nazev_typ_web` ='".$this->podtyp."' and";
				$from_podtyp=" join `typ_serial`  as `podtyp` on (`serial`.`id_podtyp` = `podtyp`.`id_typ`)";
			}else{
				$where_podtyp="";
				$from_podtyp="";
				$select_podtyp="";
			}
			if($this->nazev_zeme!=""){
				$where_zeme=" `zeme`.`nazev_zeme_web` ='".$this->nazev_zeme."' and";
			}else{
				$where_zeme=" `zeme_serial`.`zakladni_zeme`=1 and";
			}
			if($this->id_destinace!=""){
				$from_destinace=" join `destinace_serial` on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)";
				$where_destinace=" `destinace_serial`.`id_destinace` ='".$this->id_destinace."' and";
                                $select_destinace=" `destinace_serial`.`id_destinace`,";
			}else{
				$where_destinace=" ";
			}
			/*if($this->jen_tipy_na_zajezd==1){
				$where_tipy=" `zajezd`.`hit_zajezd` =1 and";
			}else{*/
				$where_tipy=" ";
			/*}	*/
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `zajezd`.`od`";
			}
			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$dotaz="select count(`serial`.`id_serial`) as pocet
                                        from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial`  on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
					".$from_podtyp."
					".$from_destinace."
					left join
					(`foto_serial` join
						`foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
					on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)

					where  (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))
                                         AND ".$where_typ.$where_podtyp.$where_zeme.$where_destinace.$where_tipy." 1
					";
			}else{
				$select="select `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
							min(`zajezd`.`od`) as `od`,`zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`do`,
							`typ_serial`.`id_typ`,
                                                        `cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
							`zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,".$select_destinace."
							".$select_podtyp."`typ_serial`.`nazev_typ_web`,
							`foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
			

			$dotaz = $select."
					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial`  on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
					".$from_podtyp."
					".$from_destinace."
					left join
					(`foto_serial` join
						`foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
					on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)

					where  (`zajezd`.`od` >\"".date("Y-m-d")."\" or (`zajezd`.`do` >\"".date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))

                                        group by `serial`.`id_serial`
                                        having  ".$where_typ.$where_podtyp.$where_zeme.$where_destinace.$where_tipy." 1
					order by ".$order."
					 ".$limit."";
                      //echo $dotaz;
                    }
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku == "get_nazev_typ"){
			$dotaz = "select `nazev_typ`
				from `typ_serial`
				where `nazev_typ_web` = '".$this->typ."'
				limit 1
				";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku == "get_nazev_zeme"){
			$dotaz =  "select `nazev_zeme`
				from `zeme`
				where `nazev_zeme_web` = '".$this->nazev_zeme."'
				limit 1
				";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku == "get_nazev_destinace"){
			$dotaz =  "select `nazev_destinace`
				from `destinace`
				where `id_destinace` = '".$this->id_destinace."'
				limit 1
				";
			//echo $dotaz;
			return $dotaz;
		}		
	}
      }

?>
