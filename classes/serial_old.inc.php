<?php
/** 
* trida pro zobrazení seriálu bez specifikovaného zájezdu
* - zobrazuje seznam fotografií, informací, dokumentù a také seznam platných zájezdù
*/

/*--------------------- SERIAL -------------------------------------------*/
class Serial extends Generic_data_class{
	//vstupnidata
	protected $nazev_serialu;
	
	protected $data;
	protected $serial;
	
	//vnorene tridy
	protected $zajezdy;
	protected $fotografie;
	protected $dokumenty;	
	protected $informace;	
	
	protected $database; //trida pro odesilani dotazu	
		
//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì nazvu serialu*/
	function __construct($nazev_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->nazev_serialu = $this->check_slashes($this->check($nazev_serialu));

	//ziskani serialu z databaze	
		$this->data=$this->database->query($this->create_query($this->nazev_serialu))
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		
	//kontrola zda jsme ziskali prave 1 serial
		if(mysqli_num_rows($this->data)==1){
			$this->serial = mysqli_fetch_array($this->data);
		}else{
			$this->chyba("Název seriálu je neplatný");
		}

	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho nazvu serialu*/
	function create_query($nazev_serialu){
		$dotaz= "select `serial`.*, `zeme_serial`.*, `zeme`.*, `typ_serial`.*, 
                                `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`, `ubytovani`.`popis` as `ubytovani_popis_ubytovani`,
                                `ubytovani`.`highlights` as `highlights_ubytovani`,`ubytovani`.`zamereni_lazni` as `poznamka_ubytovani`, `ubytovani`.`pes`, `ubytovani`.`pes_cena`

					from `serial` join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
					left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
					where `zeme_serial`.`zakladni_zeme`=1 and `serial`.`nazev_web`= '".$nazev_serialu."' 
					limit 1";
		//echo $dotaz;
		return $dotaz;
	}	       

	/** vytvori text pro titulek stranky*/
	function show_titulek(){
		if(!$this->get_error_message()){
			$vystup = $this->get_nazev()." | ".$this->get_zeme()." | ".$this->get_nazev_typ()." | ".HLAVNI_TITULEK_WEBU;
		}else{
			$vystup = "Chyba pøi pøistupu k zájezdu".$this->get_nazev()." ";
		}
		return $vystup;
	}
	
	/** vytvori text pro nadpis stranky*/
	function show_nadpis(){
		$vystup = $this->get_nazev()." - ".HLAVNI_TITULEK_WEBU;
		return $vystup;
	}
	
	/** vytvori text pro meta description stranky*/
	function show_description(){
		$vystup = $this->get_nazev().", ".$this->get_zeme().", ".$this->get_nazev_typ().", ".HLAVNI_TITULEK_WEBU;
		return $vystup;
	}
		
	/** vytvori text pro meta keyword stranky*/
	function show_keyword(){
		$vystup = $this->get_nazev().", ".$this->get_zeme().", ".$this->get_nazev_typ().", ".HLAVNI_TITULEK_WEBU;
		return $vystup;
	}

function show_slevy_zkracene(){
		$dotaz_slevy = "select * from `slevy` join
							`slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
							where `slevy_serial`.`id_serial` = ".$this->get_id()." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							order by `slevy`.`castka` desc limit 3";
		//echo $dotaz_slevy;
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
		//ziskani slev z databaze	
		$data = $this->database->query($dotaz_slevy)
		 	or $this->chyba("Chyba pøi dotazu do databáze");	
		$vystup = "<div class=\"sleva\">";	
		while($sleva = mysqli_fetch_array($data)){
			$vystup .= "<div style=\"clear:left;\" title=\"".$sleva["nazev_slevy"]."\"><img alt=\"".$sleva["castka"]." ".$sleva["mena"]."\" src=\"".FOTO_WEB."/slevy/".$sleva["castka"].".gif\" class=\"sleva_float_left\" /><strong>SLEVA:</strong> ".$sleva["zkraceny_nazev"]."</div>";
		}	
		$vystup .= "</div>";	
		
		return $vystup;		
	}	
			
	/** ziska vsechny zajezdy k danemu serialu*/
	function create_zajezdy(){
		$this->zajezdy= new Seznam_zajezdu($this->get_id(),$this->get_nazev_typ_web(),$this->get_nazev_zeme_web(),$this->get_nazev_web());
	}
	/** ziska vsechny fotografie k danemu serialu*/
	function create_foto(){
		$this->foto= new Seznam_fotografii("serial",$this->get_id());
	}
	/** ziska vsechny dokumenty k danemu serialu*/
	function create_dokumenty(){
		$this->dokumenty= new Seznam_dokumentu($this->get_id());
	}
	/** ziska vsechny informace k danemu serialu*/
	function create_informace(){
		$this->informace= new Seznam_informaci($this->get_id());
	}	
	/*metody pro pristup k parametrum*/
	function get_zajezdy() { return $this->zajezdy;}
	function get_foto() { return $this->foto;}
	function get_dokumenty() { return $this->dokumenty;}
	function get_informace() { return $this->informace;}
		
	function get_id() { return $this->serial["id_serial"];}
	function get_nazev() { 
            if($this->serial["nazev_ubytovani"]!=""){
               return $this->serial["nazev_ubytovani"]." - ".$this->serial["nazev"];
            }else{
               return $this->serial["nazev"];      
            }
        }
	function get_nazev_web() { return $this->serial["nazev_web"];}
	function get_nazev_zeme_web() { return $this->serial["nazev_zeme_web"];}
	function get_nazev_typ_web() { return $this->serial["nazev_typ_web"];}
	function get_popisek() { 
            if($this->serial["popisek_ubytovani"]!=""){
               return "<strong>".$this->serial["popisek"]."</strong>

				<table onmouseover=\"objectOperation();\" class=\"poloha\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin-right:290px;\">
					<tr>
						<td class=\"tl\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
						<td class=\"header\"><h2>Popis a poloha</h2>
						<td class=\"line\">&nbsp;</td>
						<td class=\"tr\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
					</tr>
					<tr>
						<td class=\"strl\">&nbsp;</td>
						<td class=\"content\" colspan=\"2\">
							".$this->serial["popisek_ubytovani"]."</td>
						<td class=\"strr\">&nbsp;</td>
					</tr>					
					<tr>
						<td class=\"bl\" height=\"15\">&nbsp;</td>
						<td class=\"footer\" colspan=\"2\">&nbsp;</td>
						<td class=\"br\">&nbsp;</td>
					</tr>
					
				</table>";
            }else{
               return "<strong>".$this->serial["popisek"]."</strong>";
            }
        }


	function get_adresa_sablony() { return $this->serial["adresa_sablony"];}
	
	function get_highlights($typ_zobrazeni) { 
		$vypis = "";
		if($typ_zobrazeni=="seznam"){
			if($this->serial["highlights"]!=""){				
				$highlights = explode(",", $this->serial["highlights"]);
				foreach ($highlights as $value) {
    				$vypis .= "<li class=\"nohref\"><img src=\"/strpix/ok.gif\" alt=\"\" style=\"vertical-align: baseline;margin-bottom:0;padding-bottom:0;\"/> <strong>".$value."</strong></li>";
				
				}
			}
		}
		return $vypis;
	}

		
	function get_popis_ubytovani() { 
		if($this->serial["popis_ubytovani"]!="" or $this->serial["ubytovani_popis_ubytovani"]!=""){
			return "
				<table onmouseover=\"objectOperation();\" class=\"ubytovani\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin-right:290px;\">
					<tr>
						<td class=\"tl\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
						<td class=\"header\"><h2>Ubytování</h2>
						<td class=\"line\">&nbsp;</td>
						<td class=\"tr\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
					</tr>
					<tr>
						<td class=\"strl\">&nbsp;</td>
						<td class=\"content\" colspan=\"2\">
							".$this->serial["ubytovani_popis_ubytovani"]."".$this->serial["popis_ubytovani"]."</td>
						<td class=\"strr\">&nbsp;</td>
					</tr>					
					<tr>
						<td class=\"bl\" height=\"15\">&nbsp;</td>
						<td class=\"footer\" colspan=\"2\">&nbsp;</td>
						<td class=\"br\">&nbsp;</td>
					</tr>
					
				</table>";
		}else{ 
			return "";
			}
	}
	function get_popis_stravovani()  { 
		if($this->serial["popis_stravovani"]!=""){
			return "
				<table class=\"strav\" cellspacing=\"0\" cellpadding=\"0\"  style=\"margin-right:290px;\">
					<tr>
						<td class=\"tl\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
						<td class=\"header\"><h2>Stravování</h2>
						<td class=\"line\">&nbsp;</td>
						<td class=\"tr\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
					</tr>
					<tr>
						<td class=\"strl\">&nbsp;</td>
						<td class=\"content\" colspan=\"2\">
															".$this->serial["popis_stravovani"]."</td>						
						<td class=\"strr\">&nbsp;</td>
					</tr>					
					<tr>
						<td class=\"bl\" height=\"15\">&nbsp;</td>
						<td class=\"footer\" colspan=\"2\">&nbsp;</td>
						<td class=\"br\">&nbsp;</td>
					</tr>
					
				</table>";
		}else{ 
			return "";
			}
	}
	function get_program_zajezdu()  { 
		if($this->serial["program_zajezdu"]!=""){
			return "<h3>Program zájezdu</h3>\n".$this->serial["program_zajezdu"];
		}else{ 
			return "";
			}
	}
	
	function get_popis_lazni($typ_zobrazeni) { 
		$vypis = "";
		if($typ_zobrazeni=="seznam"){
			if($this->serial["popis_lazni"]!=""){				
				$popis_lazni = explode(";", $this->serial["popis_lazni"]);
				foreach ($popis_lazni as $value) {
					if($value!=""){
    					$vypis .= "<li class=\"nohref\"><img src=\"/strpix/ok.gif\" alt=\"\" style=\"vertical-align: baseline;margin-bottom:0;padding-bottom:0;\"/> <strong>".$value."</strong></li>";
					}
				}
			}
		}
		return $vypis;
	}	
	
	
	function get_popis_strediska($typ_zobrazeni) { 
		$vypis = "";
		if($typ_zobrazeni=="seznam"){
			if($this->serial["popis_strediska"]!=""){				
				$popis_strediska = explode(";", $this->serial["popis_strediska"]);
				foreach ($popis_strediska as $value) {
    				$vypis .= "<li class=\"nohref\">".$value."</li>";
				
				}
			}
		}
		return $vypis;
	}	
	
	function get_parametry_zajezdu($typ_zobrazeni) { 
		if($typ_zobrazeni=="seznam"){
			$vypis = "
				<li class=\"nohref\">Typ: <strong>".$this->serial["nazev_typ"]."</strong></li>
				<li class=\"nohref\">Zemì: <strong>".$this->serial["nazev_zeme"]."</strong></li>
				<li class=\"nohref\">Doprava: ".Serial_library::get_typ_dopravy($this->serial["doprava"]-1)."</li>
				<li class=\"nohref\">Ubytování: ".Serial_library::get_typ_ubytovani($this->serial["ubytovani"]-1)."</li>
				<li class=\"nohref\">Stravování: ".Serial_library::get_typ_stravy($this->serial["strava"]-1)."</li>
			";
			return $vypis;
		}
	}
		
	function get_popis() { 
		if($this->serial["popis"]!=""){
			return "<p class=\"popis\">".$this->serial["popis"]."</p>";
		}
	}
	
	function get_cena_zahrnuje() { 
		if($this->serial["cena_zahrnuje"]!=""){
			return "<h3>Základní cena zahrnuje</h3><p>".$this->serial["cena_zahrnuje"]."</p>";
		}
	}
	
	function get_cena_nezahrnuje() { 
		if($this->serial["cena_nezahrnuje"]!=""){
			return "<h3>Základní cena nezahrnuje</h3><p>".$this->serial["cena_nezahrnuje"]."</p>";
		}
	}
	
	function get_poznamky() { 
		if($this->serial["poznamky"]!="" or $this->serial["poznamka_ubytovani"]!=""){
                        $ret = "<h3 onmouseover=\"objectOperation();\">Poznámky k zájezdu</h3>";
                        if($this->serial["poznamky"]!=""){
                           $ret .= "<p>".$this->serial["poznamky"]."</p>";
                        }
                        if($this->serial["poznamka_ubytovani"]!=""){
                           $ret .= "<p>".$this->serial["poznamka_ubytovani"]."</p>";
                        }
                        if($this->serial["pes"]==2){
                           $ret .= "<p>Pobyt se psem: <b>nelze</b></p>";
                        }else if($this->serial["pes"]==1 and $this->serial["pes_cena"]!=""){
                           $ret .= "<p>Pobyt se psem <b>je možný</b>, poplatek ".$this->serial["pes_cena"]." / den.</p>";
                        }else if($this->serial["pes"]==1){
                           $ret .= "<p>Pobyt se psem <b>je možný</b></p>";
                        }
			return $ret."<br/>";
		}
	}
	
	function get_strava() { return $this->serial["strava"];}
	function get_doprava() { return $this->serial["doprava"];}
	function get_ubytovani() { return $this->serial["ubytovani"];}
        function get_id_ubytovani() { return $this->serial["id_ubytovani"];}
	
	function get_typ() { return $this->serial["id_typ"];}
	function get_nazev_typ() { return $this->serial["nazev_typ"]; }
	function get_podtyp() { return $this->serial["id_podtyp"];}
	function get_zeme() { return $this->serial["nazev_zeme"];}
	function get_destinace() { return $this->serial["nazev_destinace"];}
        function get_nezobrazovat() { return $this->serial["nezobrazovat"];}

} 

//zobrazeni pouze ubytovani bez zvoleneho serialu
class Serial_ubytovani extends Serial{
 	/**konstruktor tøídy na základì nazvu serialu*/
	function __construct($nazev_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

		$this->nazev_serialu = $this->check_slashes($this->check($nazev_serialu));
                if(intval($this->nazev_serialu)>0){
                    //jde o id ubytovani
                    $this->id_ubytovani = intval($this->nazev_serialu);
                }

	//ziskani serialu z databaze
		$this->data=$this->database->query($this->create_query($this->id_ubytovani))
		 	or $this->chyba("Chyba pøi dotazu do databáze");

	//kontrola zda jsme ziskali prave 1 serial
		if(mysqli_num_rows($this->data)==1){
			$this->serial = mysqli_fetch_array($this->data);
		}else{
			$this->chyba("Název ubytování je neplatný");
		}

	}

                	/** ziska vsechny zajezdy k danemu serialu*/
	function create_zajezdy(){
		$this->zajezdy= new Seznam_zajezdu(0,"","","");
	}

	/** ziska vsechny dokumenty k danemu serialu*/
	function create_dokumenty(){
		$this->dokumenty= new Seznam_dokumentu(0);
	}
	/** ziska vsechny informace k danemu serialu*/
	function create_informace(){
		$this->informace= new Seznam_informaci(0);
	}
        function show_slevy_zkracene(){
		return "";
	}
	function get_parametry_zajezdu($typ_zobrazeni) {
		return "";
	}
//------------------- METODY TRIDY -----------------
	/**vytvoreni dotazu ze zadaneho nazvu serialu*/
	function create_query($id_ubytovani){
		$dotaz= "select *  from `ubytovani`
					where `ubytovani`.`id_ubytovani`= '".$id_ubytovani."'
					limit 1";
		//echo $dotaz;
		return $dotaz;
	}
	/** ziska vsechny fotografie k danemu serialu*/
	function create_foto(){
		$this->foto= new Seznam_fotografii("ubytovani",$this->id_ubytovani);
	}
        function show_popisek() {
            if($this->serial["popisek"]!=""){
                return "<table onmouseover=\"objectOperation();\" class=\"poloha\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin-right:290px;\">
					<tr>
						<td class=\"tl\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
						<td class=\"header\"><h2>Popis a poloha</h2>
						<td class=\"line\">&nbsp;</td>
						<td class=\"tr\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
					</tr>
					<tr>
						<td class=\"strl\">&nbsp;</td>
						<td class=\"content\" colspan=\"2\">
							".$this->serial["popisek"]."</td>
						<td class=\"strr\">&nbsp;</td>
					</tr>
					<tr>
						<td class=\"bl\" height=\"15\">&nbsp;</td>
						<td class=\"footer\" colspan=\"2\">&nbsp;</td>
						<td class=\"br\">&nbsp;</td>
					</tr>

				</table>";

            }else{
                return "";
            }
        }
        function show_popis() {
            if($this->serial["popis"]!=""){
                return  "<table onmouseover=\"objectOperation();\" class=\"ubytovani\" cellspacing=\"0\" cellpadding=\"0\" style=\"margin-right:290px;\">
					<tr>
						<td class=\"tl\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
						<td class=\"header\"><h2>Ubytování</h2>
						<td class=\"line\">&nbsp;</td>
						<td class=\"tr\"><span style=\"width:15px; overflow:visible;\">&nbsp;</span></td>
					</tr>
					<tr>
						<td class=\"strl\">&nbsp;</td>
						<td class=\"content\" colspan=\"2\">
							 ".$this->serial["popis"]."</td>
						<td class=\"strr\">&nbsp;</td>
					</tr>
					<tr>
						<td class=\"bl\" height=\"15\">&nbsp;</td>
						<td class=\"footer\" colspan=\"2\">&nbsp;</td>
						<td class=\"br\">&nbsp;</td>
					</tr>

				</table>";
            }else{
                return "";
            }
        }
        function show_poznamka() {
            if($this->serial["zamereni_lazni"]!=""){
                return  "<h4><em>Poznámky</em></h4>
                ".$this->serial["zamereni_lazni"]."";
            }else{
                return "";
            }
        }
        function show_pes() {
            if($this->serial["pes"]==2){
                return  "<br/><b>Pobyt se psem: <em>nelze</em></b>.<br/>";
            }else if($this->serial["pes"]==1){
                if($this->serial["pes_cena"]!=""){
                   return  "<br/><b>Pobyt se psem <em>je možný</em></b>, poplatek ".$this->serial["pes_cena"]." /den.<br/>";
                }else{
                   return  "<br/><b>Pobyt se psem <em>je možný</em></b>.<br/>";
                }
            }
        }

}
/**
*	trida pro zobrazeni serialu se specifikovaným zájezdem
* - zobrazuje seznam služeb zájezdu vè. kapacit a formuláøe pro objednávku zájezdu
*/
/*--------------------- SERIAL se specifikovaným zájezdem ----------------------------*/
class Serial_with_zajezd extends Serial{
	//vstupni parametry
	protected $id_zajezdu;
	//vnorene tridy
	protected $ceny;
	
	/** konstruktor tøídy na základì nazvu serialu a id zajezdu*/
	function __construct($nazev_serialu,$id_zajezdu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
		$this->nazev_serialu = $this->check_slashes($this->check($nazev_serialu));
		$this->id_zajezdu = $this->check_int($id_zajezdu);
		
	//ziskani serialu z databaze	
		$this->data=$this->database->query($this->create_query($this->nazev_serialu,$this->id_zajezdu)) 
			or $this->chyba("Chyba pøi dotazu do databáze");
		
	//kontrola zda jsme ziskali prave 1 serial
		if(mysqli_num_rows($this->data)==1){
			$this->serial = mysqli_fetch_array($this->data);
		}else{
			$this->chyba("Název seriálu, nebo èíslo zájezdu jsou neplatné");
		}
	}

	/** ziska vsechny ceny k danemu zajezdu serialu*/
	function create_ceny(){
		$this->ceny= new Seznam_cen($this->get_id(),$this->get_id_zajezd());
	}	
	/** vytvoreni dotazu ze zadaneho nazvu serialu a id zajezdu*/
	function create_query($nazev_serialu,$id_zajezdu){
		$dotaz= "select `serial`.*, `zajezd`.*, `zeme_serial`.*, `zeme`.*, `typ_serial`.*, 
                                `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek` as `popisek_ubytovani`, `ubytovani`.`popis` as `ubytovani_popis_ubytovani`,
                                `ubytovani`.`highlights` as `highlights_ubytovani`,`ubytovani`.`zamereni_lazni` as `poznamka_ubytovani`, `ubytovani`.`pes`, `ubytovani`.`pes_cena`


					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
					left join `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
					where `zeme_serial`.`zakladni_zeme`=1 and `serial`.`nazev_web`= '".$nazev_serialu."' 
						and `zajezd`.`id_zajezd`=".$id_zajezdu."
					limit 1";
		//echo $dotaz;
		return $dotaz;
	}	
	
	/** zobrazim menu s jednotlivymi typy rezervace*/
	function show_menu_rezervace($active,$skryt_predbeznou_poptavku=0){
		$uzivatel = User::get_instance();	
		if($active == "objednavka"){
			$class_rezervace = "menu_rezervace_active";
		}else{
			$class_rezervace = "menu_rezervace";
		}
		if($active == "predbezna_poptavka"){
			$class_poptavka = "menu_rezervace_active";
		}else{
			$class_poptavka = "menu_rezervace";
		}		
		if($active == "dotaz"){
			$class_dotaz = "menu_rezervace_active";
		}else{
			$class_dotaz = "menu_rezervace_konec";
		}		
		
		$core = Core::get_instance();
		$adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
		if( $adresa_zobrazit !== false ){//pokud existuje modul pro zpracovani
				
			//objednavka je mozna pouze pro prihlasene uzivatele
			//if($uzivatel->get_correct_login() ){
				$rezervace= "
					<div class=\"".$class_rezervace."\">				
					<a href=\"".$this->get_adress( array($adresa_zobrazit,$_GET["lev1"],$_GET["lev2"],$_GET["lev3"],$_GET["lev4"],"objednavka") )."\">
						Objednávka
					</a>
					</div>";
			//}else{
			//	$rezervace= "
			//		<div class=\"".$class_rezervace."\">				
			//		<span title=\"Objednávka zájezdu je dostupná pouze pro pøihlášené uživatele\">
			//			Objednávka
			//		</span>
			//		</div>";		
			//}
			if($skryt_predbeznou_poptavku!=1){
				$poptavka= "
					<div class=\"".$class_poptavka."\">
					<a href=\"".$this->get_adress( array($adresa_zobrazit,$_GET["lev1"],$_GET["lev2"],$_GET["lev3"],$_GET["lev4"],"predbezna_poptavka") )."\">
						Pøedbìžná poptávka
					</a>
					</div>";	
			}		
			$dotaz= "
				<div class=\"".$class_dotaz."\">
				<a href=\"".$this->get_adress( array($adresa_zobrazit,$_GET["lev1"],$_GET["lev2"],$_GET["lev3"],$_GET["lev4"],"dotaz") )."\">
					Dotaz k zájezdu
				</a>
				</div>
				<div style=\"float:left;width:100%;\">&nbsp;</div>";	
						
		}
		return $rezervace.$poptavka.$dotaz;
		
	}	
	
	function show_slevy_zkracene(){
		$dotaz_slevy = "select * from `slevy` join
							`slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
							where `slevy_serial`.`id_serial` = ".$this->get_id()." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							order by `slevy`.`castka` desc limit 3";
		$dotaz_slevy_zajezd = "select * from `slevy` join
							`slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy`)
							where `slevy_zajezd`.`id_zajezd` = ".$this->get_id_zajezd()." 
							and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
							and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
							order by `slevy`.`castka` desc limit 3";							
		//echo $dotaz_slevy;
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
		//ziskani slev z databaze	
		$data = $this->database->query($dotaz_slevy)
		 	or $this->chyba("Chyba pøi dotazu do databáze");			
						
		$data_zajezd = $this->database->query($dotaz_slevy_zajezd)
		 	or $this->chyba("Chyba pøi dotazu do databáze");	
			
		$vystup = "<div class=\"sleva\">";	
		while($sleva = mysqli_fetch_array($data)){
			$vystup .= "<div style=\"clear:left;\" title=\"".$sleva["nazev_slevy"]."\"><img alt=\"".$sleva["castka"]." ".$sleva["mena"]."\" src=\"".FOTO_WEB."/slevy/".$sleva["castka"].".gif\" class=\"sleva_float_left\" /><strong>SLEVA:</strong> ".$sleva["zkraceny_nazev"]."</div>";
		}	
		while($sleva = mysqli_fetch_array($data_zajezd)){
			$vystup .= "<div style=\"clear:left;\" title=\"".$sleva["nazev_slevy"]."\"><img alt=\"".$sleva["castka"]." ".$sleva["mena"]."\" src=\"".FOTO_WEB."/slevy/".$sleva["castka"].".gif\" class=\"sleva_float_left\" /><strong>SLEVA:</strong> ".$sleva["zkraceny_nazev"]."</div>";
		}
                if($this->serial["cena_pred_akci"]>0 and $this->serial["akcni_cena"]>0){
                        $sleva_procenta = round( 1-($this->serial["akcni_cena"] / $this->serial["cena_pred_akci"]),2) * 100 ;
			$vystup .= "<div style=\"clear:left;\" title=\"".strip_tags($this->serial["popis_akce"])."\">
                                    <div style=\"font-size:2.2em;color:blue;font-weight:bold;float:left;font-style:italic\" title=\"Sleva až ".$sleva_procenta."%\"/>".$sleva_procenta."%</div>
                                        &nbsp; &nbsp;<strong>AKCE:</strong> cena pøed: <span style=\"color:red;text-decoration:line-through;\">".$this->serial["cena_pred_akci"]."</span> Kè<br/>
                                        &nbsp; &nbsp;nyní od: <span style=\"color:green;font-size:1.2em\"><b>".$this->serial["akcni_cena"]."</b> Kè</span></div>";
                }
		$vystup .= "</div>";	
		
		return $vystup;		
	}	
	/**zobrazi formular pro objednavku zajezdu*/	
	function show_form_objednavka($skryt_predbeznou_poptavku=0){
		//$uzivatel = User::get_instance();
		//objednavka je dostupna pouze pro prihlaseneho uzivatele
		//if( $uzivatel->get_correct_login() ){
			$menu_rezervace = $this->show_menu_rezervace("objednavka",$skryt_predbeznou_poptavku);
			$rezervace = new Rezervace_objednavka("vytvorit", $this->get_id(), $this->get_id_zajezd(),"");
			$objednavka = $rezervace->show_form_ceny();	
					
			$vystup= "<div id=\"rezervace\">".$menu_rezervace.$objednavka."</div>";
			return $vystup;			
		//}
	}
	
	/**zobrazi formular pro predbeznou poptavku zajezdu*/	
	function show_form_predbezna_poptavka($skryt_predbeznou_poptavku=0){
		$uzivatel = User::get_instance();	
				
		$menu_rezervace = $this->show_menu_rezervace("predbezna_poptavka",$skryt_predbeznou_poptavku);
		$rezervace = new Rezervace_predbezna_poptavka("vytvorit", $this->get_id(), $this->get_id_zajezd(),"");
		$predbezna_poptavka = $rezervace->show_form_predbezna_poptavka();	
					
			$vystup= "<div id=\"rezervace\">".$menu_rezervace.$predbezna_poptavka."</div>";
			return $vystup;
	}	
	
	/**zobrazi formular pro dotaz k zajezdu*/	
	function show_form_dotaz($skryt_predbeznou_poptavku=0){
		$uzivatel = User::get_instance();
		
		$menu_rezervace = $this->show_menu_rezervace("dotaz",$skryt_predbeznou_poptavku);
		$rezervace = new Rezervace_dotaz("vytvorit", $this->get_id(), $this->get_id_zajezd(),"","");
		$dotaz = $rezervace->show_form_dotaz();
			
			$vystup="<div id=\"rezervace\">".$menu_rezervace.$dotaz."</div>";				
			return $vystup;
			
		
	}		
	/*metody pro pristup k parametrum*/
	function get_ceny(){return $this->ceny;}
	
	function get_id_zajezd() { return $this->serial["id_zajezd"];}
	function get_termin_od() { return $this->serial["od"];}
	function get_termin_do() { return $this->serial["do"];}
	function get_nazev_zajezdu() {return $this->serial["nazev_zajezdu"];	}	
	function get_nezobrazovat() {
            if($this->serial["nezobrazovat"] or $this->serial["nezobrazovat_zajezd"]){
                return 1;
            }else{
                return 0;
            }

        }
	function get_poznamky_zajezd() {
		if($this->serial["poznamky_zajezd"]!=""){
			return "<h3>Poznámky k termínu</h3><p>".$this->serial["poznamky_zajezd"]."</p>";
		}
	}
	
} 




?>
