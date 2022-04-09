<?php
/** 
* trida pro zobrazeni seznamu serialu
*/
/*--------------------- SEZNAM SERIALU -----------------------------*/
/**jednodussi verze - vstupni parametry pouze typ, podtyp, zeme, zacatek vyberu a order by
	- odpovida dotazu z katalogu zajezdu
*/
class Informace_list extends Generic_list{
	//vstupni data
	protected $typ;
	protected $nazev_typ;
	protected $podtyp;
	protected $id_destinace;
	protected $nazev_zeme;
	protected $nazev_zeme_cz;
	protected $zacatek;
	protected $order_by;
	protected $jen_tipy_na_zajezd;
	protected $pocet_zaznamu;
	protected $dalsi_podminky_zeme;
	
	protected $pocet_zajezdu;
	
	protected $database; //trida pro odesilani dotazu	
	
//------------------- KONSTRUKTOR  -----------------	
	/**konstruktor podle specifikovan�ho filtru na typ, podtyp a zemi*/
	function __construct($typ, $podtyp, $nazev_zeme, $id_destinace, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
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
		
		if(defined("DALSI_PODMINKY_ZEME_INFORMACE") and DALSI_PODMINKY_ZEME_INFORMACE!=""){
			$this->dalsi_podminky_zeme = DALSI_PODMINKY_ZEME_INFORMACE;
		}		
						
		if(defined("ZEME_WEB") and ZEME_WEB!=""){
			$this->nazev_zeme = ZEME_WEB;
		}
		
		if($this->nazev_zeme){
		//ziskani nazvu zem�
			$data_zeme = $this->database->query( $this->create_query("get_nazev_zeme") )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze!");
			$zeme = mysqli_fetch_array($data_zeme);
			$this->nazev_zeme_cz = $zeme["nazev_zeme"];		
		}
				
	//ziskam celkovy pocet zajezdu ktere odpovidaji
		$data_pocet=$this->database->query( $this->create_query("select_seznam",1) )
		 	or $this->chyba("Chyba p�i dotazu do datab�ze");
		$zaznam_pocet = mysqli_fetch_array($data_pocet);
		$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

	//ziskani seznamu z databaze	
		$this->data=$this->database->query( $this->create_query("select_seznam") )
		 	or $this->chyba("Chyba p�i dotazu do datab�ze");
		
	//kontrola zda jsme ziskali nejake zajezdy
		if(mysqli_num_rows($this->data)==0){
			$this->chyba("Pro zadan� podm�nky neexistuje ��dn� z�jezd");
		}
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){

		if($this->typ!=""){						
			//ziskam id typu informace
					if($this->typ == "zeme"){
						$id_typ = 1;		
										
					}else if($this->typ == "zajimavosti"){
						$id_typ = 2;		
										
					}else if($this->typ == "prakticke"){
						$id_typ = 3;						
					}
		}
	 
			if($typ_pozadavku == "select_seznam"){
				if($id_typ!=""){
					$dotaz_typ=" and `informace`.`typ_informace` = ".$id_typ." ";
				}
				if($this->nazev_zeme!=""){
					$dotaz_zeme=" and `zeme`.`nazev_zeme_web` = \"".$this->nazev_zeme."\" ";
				}		
				if($this->id_destinace!=""){
					$dotaz_destinace=" and `informace`.`id_destinace` = ".$this->id_destinace." ";
			}		
				
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			
			
			$order=$this->order_by($this->order_by);
				
				
			if($only_count==1){
				$select = "select count(`informace`.`id_informace`) as pocet ";
				$limit="";
			}else{
				$select = "select * ";
			}		
						
			$dotaz= $select."
					from `informace` 
						join `zeme` on (`informace`.`id_zeme` = `zeme`.`id_zeme`)

						left join
							(`foto_informace` join
							`foto` on (`foto_informace`.`id_foto` = `foto`.`id_foto`) 
						)on (`foto_informace`.`id_informace` = `informace`.`id_informace` and `foto_informace`.`zakladni_foto`=1) 


					where 1 ".$dotaz_typ.$dotaz_zeme.$dotaz_destinace." ".$this->dalsi_podminky_zeme."
					order by ".$order."
					".$limit."";
					
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
		}
	}	


	
/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "nazev":
				 return "`informace`.`nazev`";
   			 break;
			case "random":
				 return "RAND()";
   			 break;
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`informace`.`nazev`";
	}
	
	function show_ikony($typ_zobrazeni){
						if (is_file("strpix/vlajky/".$this->radek["nazev_zeme_web"].".png")){
							$zeme="";
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
			$adresa_informace = $core->get_adress_modul_from_typ("informace");
			if( $adresa_informace !== false ){//pokud existuje modul pro zpracovani		
			
			
					if($this->radek["typ_informace"] == "1"){
						$typ = "zeme";		
										
					}else if($this->radek["typ_informace"] == "2"){
						$typ = "zajimavosti";		
										
					}else if($this->radek["typ_informace"] == "3"){
						$typ = "prakticke";						
					}			
					
				$vypis = $vypis."<div class=\"header\">\n<div class=\"zeme\">".$this->show_ikony($typ_zobrazeni)."</div>
							<div class=\"nazev\"><a href=\"".$this->get_adress(array($typ, $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]),0,"informace")."\" class=\"normal\">".$this->get_nazev()."</a></div>\n</div>\n
							<div class=\"contend\">
								".$foto."
								<div class=\"popisek\">".$this->get_popisek()."</div>									
							</div>
						</div>
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
		$vypis = "<div class=\"strankovani\"><a href=\"?str=0\" title=\"prvn� str�nka z�jezd�\">&lt;&lt;</a> &nbsp;"; 
		
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
			$vypis = $vypis." &nbsp; <a href=\"?str=".$posl_str."\" title=\"posledn� str�nka z�jezd�\">&gt;&gt;</a></div>";	
		
		return $vypis;
	}	
	
	/** vytvori text pro titulek stranky*/
	function show_titulek(){

		//tvorba vypisu titulku
		if($this->nazev_zeme_cz!=""){
			return $this->nazev_zeme_cz." | Informace a zaj�mavosti";
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ." | Informace a zaj�mavosti";
		}else{
			return " Informace a zaj�mavosti";
		}
	}
	
	/** vytvori text pro nadpis stranky*/
	function show_nadpis(){
		//tvorba vypisu titulku
		if( $this->nazev_zeme_cz!=""){
			return $this->nazev_zeme_cz." -  Informace a zaj�mavosti";
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ." -  Informace a zaj�mavosti";
		}else{
			return " Informace a zaj�mavosti";
		}
	}	
	
	/** vytvori text pro meta keyword stranky*/
	function show_keyword(){
		//tvorba vypisu titulku
		if($this->nazev_zeme_cz!=""){
			return $this->nazev_zeme_cz.",  Informace, zaj�mavosti, pobytov� m�sta";
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ.", Katalog z�jezd�";
		}else{
			return " Informace, zaj�mavosti,pobytov� m�sta,";
		}
	}	

	/** vytvori text pro meta description stranky*/
	function show_description(){
		//tvorba vypisu titulku
		if($this->nazev_zeme_cz!=""){
			return $this->nazev_zeme_cz.",Informace, zaj�mavosti,pobytov� m�sta,";
		}else if($this->nazev_typ!=""){
			return $this->nazev_typ.", Informace, zaj�mavosti,pobytov� m�sta,";
		}else{
			return "Informace, zaj�mavosti,pobytov� m�sta,";
		}
	}	
		
	/*metody pro pristup k parametrum*/
	function get_nazev_zajezdu() { 
		if($this->radek["nazev_zajezdu"]!=""){
			return "<strong><i>".$this->radek["nazev_zajezdu"]."</i></strong> ";
		}
	}
	function get_id_informace() { return $this->radek["id_informace"];}
	function get_nazev() { return $this->radek["nazev"];}
	function get_nazev_web() { return $this->radek["nazev_web"];}
	function get_popisek() { 
		if($this->radek["popisek"]!=""){
			return nl2br($this->radek["popisek"]);
		}else{
			return nl2br(substr(strip_tags($this->radek["popis"]),0,strrpos(substr(strip_tags($this->radek["popis"]),0,250)," ")));
		}
	}	

	function get_nazev_zeme() { return $this->radek["nazev_zeme"];}
	function get_nazev_zeme_web() { return $this->radek["nazev_zeme_web"];}	

	function get_id_foto() { return $this->radek["id_foto"];}
	function get_foto_url() { return $this->radek["foto_url"];}	
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { return $this->radek["popisek_foto"];}		
	
	function get_typ() { return $this->radek["typ_informace"];}
	function get_podtyp() { return $this->radek["nazev_podtyp_web"];}

	function get_pocet_zajezdu(){ return $this->pocet_zajezdu;}
}




?>
