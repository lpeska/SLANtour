<?php
/** 
* trida pro zobrazení seriálu bez specifikovaného zájezdu
* - zobrazuje seznam fotografií, informací, dokumentů a také seznam platných zájezdů
*/

/*--------------------- SERIAL -------------------------------------------*/
class Serial extends Generic_data_class{
	//vstupnidata                                          
	protected $nazev_serialu;
	
	protected $data;
	public $serial;
	
	//vnorene tridy
	protected $zajezdy;
	protected $fotografie;
	protected $dokumenty;	
	protected $informace;	
	
	protected $database; //trida pro odesilani dotazu	
		
//------------------- KONSTRUKTOR -----------------
	/**konstruktor třídy na základě nazvu serialu*/
	function __construct($nazev_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->nazev_serialu = $this->check_slashes($this->check($nazev_serialu));

	//ziskani serialu z databaze	
		$this->data=$this->database->query($this->create_query($this->nazev_serialu))
		 	or $this->chyba("Chyba při dotazu do databáze");
		
	//kontrola zda jsme ziskali prave 1 serial
		if(mysqli_num_rows($this->data)==1){
			$this->serial = mysqli_fetch_array($this->data);
		}else{
			$this->chyba("Název seriálu je neplatný");
		}

	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho nazvu serialu*/
	function create_query($nazev_serialu, $id_zajezdu=""){
		$dotaz= "select `serial`.*, `zeme_serial`.*, `zeme`.*, `typ_serial`.*, `destinace`.*,`dokument`.`dokument_url`,
                                                    
                                `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                                `objekt_ubytovani`.`highlights` as `highlights_ubytovani`,`objekt`.`poznamka` as `poznamka_ubytovani`, `objekt_ubytovani`.`pes`, `objekt_ubytovani`.`pes_cena` , `objekt_ubytovani`.`posX` , `objekt_ubytovani`.`posY`

					from `serial` join
                                        `smluvni_podminky_nazev` on (`smluvni_podminky_nazev`.`id_smluvni_podminky_nazev` = `serial`.`id_sml_podm`) join
                                        `dokument` on (`dokument`.`id_dokument` = `smluvni_podminky_nazev`.`dokument_id`) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
                                        left join (`destinace_serial` join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`) )
                                             on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                                             
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                            
                                        
					where `zeme_serial`.`zakladni_zeme`=1 and `serial`.`nazev_web`= '".$nazev_serialu."' 
					limit 1";
//		echo $dotaz;
		return $dotaz;
	}	       

	/** vytvori text pro titulek stranky*/
	function show_titulek(){
		if(!$this->get_error_message()){
			$vystup = $this->get_nazev()." | ".$this->get_destinace(" | ").$this->get_zeme(" | ").$this->get_nazev_typ(" | ")."SLAN tour";
		}else{
			$vystup = "Chyba při přistupu k zájezdu".$this->get_nazev()." ";
		}
		return $vystup;
	}
	
  function show_map(){
    $vystup = "" ;
    if($this->serial["posX"] != 0 and  $this->serial["posY"] != 0){
        $vystup = '
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.6.0/dist/leaflet.css" integrity="sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==" crossorigin=""/>
            <script src="https://unpkg.com/leaflet@1.6.0/dist/leaflet.js" integrity="sha512-gZwIG9x3wUXg2hdXF6+rVkLF/0Vi9U8D2Ntg4Ga5I5BZpVkVxlJWbSQtXPSiUTtC0TjtGOmxa1AJPuV0CPthew==" crossorigin=""></script>
            <script>
              var latX =  '.$this->serial["posX"].';
              var latY =  '.$this->serial["posY"].';
              var latMarkerX =  '.$this->serial["posX"].';
              var latMarkerY =  '.$this->serial["posY"].';
              var markerText = "'.$this->get_nazev().'";
            </script>
            <style>
                .main #mapid img{
                     border:none;
                     padding:0;
                     margin:0;
                }
            </style>
            <script>   
               window.addEventListener("load", function(){
                  var mymap = L.map("mapid").setView([latY, latX], 10);
                  L.tileLayer(\'https://tile.openstreetmap.org/{z}/{x}/{y}.png\', {
                    maxZoom: 19,
                    attribution: \'&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>\'
                  }).addTo(mymap);                  
                  
                  var marker2 = L.marker([latMarkerY,latMarkerX]).addTo(mymap);
                  marker2.bindPopup(markerText);
                });

            </script>
         ';    
    
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

        function show_predregistrace(){
		$reg = $this->serial["predregistrace"];
		if($reg!=""){
			$reg_array = explode("," ,  $reg);
			$i=0;
			$reg_form="<table onmouseover=\"objectOperation();\" class=\"predregistrace\">
							<tr>";
			foreach ($reg_array as $reg_value){
				$i++;
				$reg_value = $this->check_with_html($reg_value);
				
				if($reg_value!=""){				
					$reg_form.="<td><input type=\"checkbox\" value=\"".$reg_value."\" name=\"predregistrace_".$i."\" style=\"width:18px;\"/>".$reg_value."</td>";
					if($i%4==0){
						$reg_form.= "</tr><tr>";
					}
				}
			}
			$reg_form.="</tr></table>
			<input type=\"hidden\" value=\"".$i."\" name=\"predregistrace\" />";

			$rezervace = new Rezervace_dotaz("vytvorit", $this->get_id(), $_GET["lev2"],"","");
			$rezervace->set_dalsi_form($reg_form);
			$dotaz = $rezervace->show_form_dotaz("predregistrace");
			
			$vystup="<div  class=\"akce\" style=\"clear:right;\">".$dotaz."</div>";				
			return $vystup;						
		}
	}	
        
    function get_popis_akce() { return $this->serial["popis_akce"];}

		function get_predregistrace(){
			return $this->serial["predregistrace"];
		}
        
        function get_cena_pred_akci() {
            return
                "dříve: <span style=\"color:red;text-decoration:line-through;font-size:1.2em;\">".
                $this->serial["cena_pred_akci"]." Kč</span>";

        }
        function get_akcni_cena() {
            return " <br/> nyní od: <span style=\"color:#00ae35;text-decoration:none;font-size:1.2em;font-weight:bold;\">".
                $this->serial["akcni_cena"]." Kč</span>";



        }
        function get_sleva() {
                $sleva = round ( ( 1 - ($this->serial["akcni_cena"] / $this->serial["cena_pred_akci"]) )*100);

            return  "<span style=\"color:#009e15;font-size:2.2em;font-weight:bold;\" title=\" Sleva až ".$sleva."% \">
                ".$sleva."%</span>";

        }
        
        function show_akcni_nabidka(){
                $vypis="";
                if($this->serial["cena_pred_akci"] >0 and $this->serial["akcni_cena"]){
                        $slevy = "<td style=\"padding:1px;border-right:1px solid grey;\">
                            ".$this->get_sleva()."
                            </td>";
                        
                        if( $this->get_popis_akce() ){
                          $popisek_text = "
                                    <p style=\"font-size:1.0em;padding:3px;\">
                                      <b>".$this->get_popis_akce()."</b>
                                    </p>
                                ";  
                        }
                        
                        $popisek = "<td>".$popisek_text."                                    
                                    ".$this->get_cena_pred_akci()."".$this->get_akcni_cena()."
                                        </td>
                       ";

                        $vypis = "
                            <div class=\"akce\" style=\"margin-bottom:10px\">
                            <h3>AKČNÍ NABÍDKA</h3>
                            <table style=\"margin:0px;padding:0px;width:195px;\">
                                    
                                        <tr>".$slevy.$popisek." </tr>
                            </table>
                            </div>
                            ";
                }
		return $vypis;
	}
        
        function show_slevy_zkracene($typ_zobrazeni=""){
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
		 	or $this->chyba("Chyba při dotazu do databáze");
                
                if($typ_zobrazeni=="array"){
                    $vystup = [];
                    while($sleva = mysqli_fetch_array($data)){
                        if($sleva["sleva_staly_klient"] == 1){
                            $poznamka = "Sleva bude odečtena po zkontrolování Vašich údajů pracovníkem CK";
                        }else{
                            $poznamka = "Sleva bude odečtena v průběhu objednávky";                        
                        } 

                        $sleva_record = [$sleva["nazev_slevy"], $sleva["zkraceny_nazev"],  $sleva["castka"], $sleva["mena"], $poznamka];
                        
                        $vystup[] = $sleva_record;
                    }
                    return $vystup;	
                    
                }
                
                $vystup = "
                    <tr><td colspan=\"2\">
                    <table style=\"width:296px;background-color:#ffefc0;margin-left:2px;\">";	
		while($sleva = mysqli_fetch_array($data)){
                        if($sleva["sleva_staly_klient"] == 1){
                            $text_slevy = "<span style=\"color:grey;font-size:0.8em;font-style:italic\">Sleva bude odečtena po zkontrolování Vašich údajů pracovníkem CK</span>";
                        }else{
                            $text_slevy = "<span style=\"color:grey;font-size:0.8em;font-style:italic\">Sleva bude odečtena v průběhu objednávky</span>";                        
                        }                    
                       $vystup .= "<tr><td style=\"border-bottom:1px dashed grey;margin:0px;padding:2px;\" valign=\"top\" title=\"".$sleva["nazev_slevy"]."\"><span class=\"serial_sleva\">".$sleva["castka"]."<span>".$sleva["mena"]."</span></span></td>
                                        <td style=\"border-bottom:1px dashed grey;margin:0px;padding:2px;\" title=\"".$sleva["nazev_slevy"]."\"><strong>SLEVA:</strong> ".$sleva["zkraceny_nazev"]."<br/>".$text_slevy."</td></tr>";
		}			
		$vystup .= "</table></td></tr>";	                	
		
		return $vystup;		
	}	
			
	/** ziska vsechny zajezdy k danemu serialu*/
	function create_zajezdy(){
		$this->zajezdy= new Seznam_zajezdu($this->get_id(),$this->get_nazev_typ_web(),$this->get_nazev_zeme_web(),$this->get_nazev_web());
	}
	/** ziska vsechny fotografie k danemu serialu*/
	function create_foto(){
      //print_r($this->serial);
      if($this->serial["nezobrazovat_data_objektu"] == 1){
          $this->foto= new Seznam_fotografii("serial_no_object_foto",$this->get_id());
      }else{
          $this->foto= new Seznam_fotografii("serial_suppress_object_foto",$this->get_id());
      }
          /*
            if($this->serial["id_typ"] == 2 or $this->serial["id_typ"] == 29 or $this->serial["id_typ"] == 30 ){
                $this->foto= new Seznam_fotografii("serial_suppress_object_foto",$this->get_id());
            }else{
                $this->foto= new Seznam_fotografii("serial",$this->get_id());
            }
          */
		
	}
        function create_foto_ubytovani(){
             if($this->get_id_ubytovani()){
		$this->foto= new Seznam_fotografii("ubytovani",$this->get_id_ubytovani());
             }else{
                 return false;
             }
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
            if($this->serial["id_sablony_zobrazeni"]!=12){
               return $this->serial["nazev"]; 
            }else if($this->serial["nazev_ubytovani"]!=""){
               return $this->serial["nazev"]." - ".$this->serial["nazev_ubytovani"];
            }else{
               return $this->serial["nazev"];      
            }
        }
        function get_nazev_plain() {             
               return $this->serial["nazev"];                  
        }
	function get_nazev_web() { return $this->serial["nazev_web"];}
	function get_nazev_zeme_web() { return $this->serial["nazev_zeme_web"];}
	function get_nazev_typ_web() { return $this->serial["nazev_typ_web"];}
	function get_popisek() { 
      if($this->serial["nezobrazovat_data_objektu"] == 1){
          return $this->serial["popisek"];
      }
      return "".$this->serial["popisek"]."
             ".$this->serial["popisek_ubytovani"]."";        
  }


	function get_adresa_sablony() { return $this->serial["adresa_sablony"];}
	
        function get_id_sablony_zobrazeni() { return $this->serial["id_sablony_zobrazeni"];}
        
        
	function get_highlights($typ_zobrazeni) { 
		$vypis = "";
		if($typ_zobrazeni=="seznam"){
			if($this->serial["highlights"]!=""){				
				$highlights = explode(",", $this->serial["highlights"]);
				foreach ($highlights as $value) {
    				$vypis .= "<li><strong>".$value."</strong></li>";
				
				}
			}
		}
		return $vypis;
	}

		
	function get_popis_ubytovani() {     
    if($this->serial["nezobrazovat_data_objektu"] == 1){
         if($this->serial["popis_ubytovani"]!=""){
               return "".$this->serial["popis_ubytovani"]."";
         }else{
              return "";
         }
    }
          
		if($this->serial["popis_ubytovani"]!="" or $this->serial["ubytovani_popis_ubytovani"]!=""){
        return "".$this->serial["ubytovani_popis_ubytovani"]."".$this->serial["popis_ubytovani"]."";
                      
		}else{ 
			return "";
		}
	}
	function get_popis_stravovani()  { 
		if($this->serial["popis_stravovani"]!=""){
			return "".$this->serial["popis_stravovani"]."";
		}else{ 
			return "";
			}
	}
	function get_program_zajezdu()  { 
		if($this->serial["program_zajezdu"]!=""){
			return "".$this->serial["program_zajezdu"];
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
				<li class=\"nohref\">Země: <strong>".$this->serial["nazev_zeme"]."</strong></li>
				<li class=\"nohref\">Doprava: ".Serial_library::get_typ_dopravy($this->serial["doprava"]-1)."</li>
				<li class=\"nohref\">Ubytování: ".Serial_library::get_typ_ubytovani($this->serial["ubytovani"]-1)."</li>
				<li class=\"nohref\">Stravování: ".Serial_library::get_typ_stravy($this->serial["strava"]-1)."</li>
			";
			return $vypis;
		}
	}
		
	function get_popis() { 
		if($this->serial["popis"]!=""){
			return "".$this->serial["popis"]."";
		}
	}
	
	function get_cena_zahrnuje() { 
		if($this->serial["cena_zahrnuje"]!=""){
			return "".$this->serial["cena_zahrnuje"]."";
		}
	}
	
	function get_cena_nezahrnuje() { 
		if($this->serial["cena_nezahrnuje"]!=""){
			return "".$this->serial["cena_nezahrnuje"]."";
		}
	}
	
	function get_poznamky() { 
    if($this->serial["nezobrazovat_data_objektu"] == 1){
         if($this->serial["poznamky"]!=""){
               return "".$this->serial["poznamky"]."";
         }else{
              return "";
         }
    }
    
		if($this->serial["poznamky"]!="" or $this->serial["poznamka_ubytovani"]!=""){
                        
                        if($this->serial["poznamky"]!=""){
                           $ret .= "".$this->serial["poznamky"]."";
                        }
                        if($this->serial["poznamka_ubytovani"]!=""){
                           $ret .= " ".$this->serial["poznamka_ubytovani"]."";
                        }
                        if($this->serial["pes"]==2){
                           $ret .= " Pobyt se psem: <b>nelze</b>";
                        }else if($this->serial["pes"]==1 and $this->serial["pes_cena"]!=""){
                           $ret .= " Pobyt se psem <b>je možný</b>, poplatek ".$this->serial["pes_cena"]." / den.";
                        }else if($this->serial["pes"]==1){
                           $ret .= " Pobyt se psem <b>je možný</b>";
                        }
			return $ret." ";
		}
	}
	
	function get_strava() { return $this->serial["strava"];}
	function get_doprava() { return $this->serial["doprava"];}
	function get_ubytovani() { return $this->serial["ubytovani"];}
        function get_id_ubytovani() { return $this->serial["id_ubytovani"];}
	function get_nazev_ubytovani() { 
            if($this->serial["id_sablony_zobrazeni"]==12){
                return $this->serial["nazev_ubytovani"];            
            }
        }    
        function get_nazev_ubytovani_web() {             
            if($this->serial["id_sablony_zobrazeni"]==12){
                return $this->serial["nazev_ubytovani_web"];
            }
        }
        
	function get_typ() { return $this->serial["id_typ"];}
	function get_nazev_typ($separator="") { 
            if($this->serial["nazev_typ"]==""){
                $separator="";
            }
            return $this->serial["nazev_typ"].$separator;         
        }
	function get_podtyp() { 
            return $this->serial["id_podtyp"];            
        }
	function get_zeme($separator="") { 
            if($this->serial["nazev_zeme"]==""){
                $separator="";
            }
            return $this->serial["nazev_zeme"].$separator;
            
            }
        function get_id_destinace() { return $this->serial["id_destinace"];}
	function get_destinace($separator="") { 
            if($this->serial["nazev_destinace"]==""){
                $separator="";
            }
            return $this->serial["nazev_destinace"].$separator;
            
            }
        function get_id_smluvni_podminky(){ return $this->serial["id_sml_podm"];}
        function get_adresa_smluvni_podminky(){
            return $this->serial["dokument_url"];
        }    
        function get_nezobrazovat() { return $this->serial["nezobrazovat"];}
        function get_dlouhodobe_zajezdy() { return $this->serial["dlouhodobe_zajezdy"];}        
} 

//zobrazeni pouze ubytovani bez zvoleneho serialu
class Serial_ubytovani extends Serial{
 	/**konstruktor třídy na základě nazvu serialu*/
	function __construct($nazev_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

		$this->nazev_serialu = $this->check($nazev_serialu);
                

	//ziskani serialu z databaze
		$this->data=$this->database->query($this->create_query($this->nazev_serialu))
		 	or $this->chyba("Chyba při dotazu do databáze");

	//kontrola zda jsme ziskali prave 1 serial
		if(mysqli_num_rows($this->data)==1){
			$this->serial = mysqli_fetch_array($this->data);
                        $this->id_ubytovani = $this->serial["id_ubytovani"];
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
        function show_slevy_zkracene($typ_zobrazeni=''){
		return "";
	}
	function get_parametry_zajezdu($typ_zobrazeni) {
		return "";
	}
//------------------- METODY TRIDY -----------------
	/**vytvoreni dotazu ze zadaneho nazvu serialu*/
	function create_query($nazev, $id_zajezdu=""){
		$dotaz= "select `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani` as `nazev` ,`objekt_ubytovani`.`popis_poloha` as `popisek`, `objekt_ubytovani`.`pokoje_ubytovani` as `popis`,
                                `objekt_ubytovani`.`nazev_web`,
                                `objekt_ubytovani`.`highlights` ,`objekt`.`poznamka`, `objekt_ubytovani`.`pes`, `objekt_ubytovani`.`pes_cena`, `objekt_ubytovani`.`posX` , `objekt_ubytovani`.`posY`,
                            coalesce(`typ_serial`.`id_typ`) as `id_typ`, coalesce(`typ_serial`.`nazev_typ`) as `nazev_typ`, coalesce(`typ_serial`.`nazev_typ_web`) as `nazev_typ_web`,
                            coalesce(`zeme`.`id_zeme`) as `id_zeme`, coalesce(`zeme`.`nazev_zeme`) as `nazev_zeme`, coalesce(`zeme`.`nazev_zeme_web`) as `nazev_zeme_web`, 
                            coalesce(`destinace`.`id_destinace`) as `id_destinace` , coalesce(`destinace`.`nazev_destinace`) as `nazev_destinace`  
                                from    `objekt` join
                                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`) join
                                        `objekt_serial` on (`objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) 
                                        left join (
                                            `serial`
                                            join `zeme_serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial` and `zeme_serial`.`zakladni_zeme`=1)
                                            join `zeme` on (`zeme_serial`.`id_zeme` = `zeme`.`id_zeme`)
                                            join `destinace_serial` on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                                            join `typ_serial` on (`typ_serial`.`id_typ` = `serial`.`id_typ`)
                                        )on(`objekt_serial`.`id_serial` = `serial`.`id_serial`) 
					where `objekt_ubytovani`.`nazev_web`= '".$nazev."'
                                            group by `objekt_ubytovani`.`id_objektu`
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
                return  "<h3 class=\"plain_text\"><b>POPIS A POLOHA</b></h3>
                    ".$this->serial["popisek"];                
            }else{
                return "";
            }                       
        }
        function show_popis() { 
            if($this->serial["popis"]!=""){
                return  "<h3 class=\"plain_text\"><b>UBYTOVÁNÍ, POKOJE</b></h3>
                    ".$this->serial["popis"];                
            }else{
                return "";
            }                       
        }        
        function show_poznamka() {
            if($this->serial["zamereni_lazni"]!=""){
                return  "<h3 class=\"plain_text\"><b>POZNÁMKY</b></h3>
                ".$this->serial["zamereni_lazni"]." <br/>".$this->show_pes();                
            }else{
                return $this->show_pes();
            }                       
        }   
        function show_pes() { 
            if($this->serial["pes"]==2){
                return  "<b>Pobyt se psem: <em>nelze</em></b>.<br/>";
            }else if($this->serial["pes"]==1){
                if($this->serial["pes_cena"]!=""){
                   return  "<b>Pobyt se psem <em>je možný</em></b>, poplatek ".$this->serial["pes_cena"]." /den.<br/>";
                }else{
                   return  "<b>Pobyt se psem <em>je možný</em></b>.<br/>";
                }
            }
        }    
        function get_data(){
            return $this->serial;
        }

}
/**
*	trida pro zobrazeni serialu se specifikovaným zájezdem
* - zobrazuje seznam služeb zájezdu vč. kapacit a formuláře pro objednávku zájezdu
*/
/*--------------------- SERIAL se specifikovaným zájezdem ----------------------------*/
class Serial_with_zajezd extends Serial{
	//vstupni parametry
	protected $id_zajezdu;
	//vnorene tridy
	protected $ceny;
	
	/** konstruktor třídy na základě nazvu serialu a id zajezdu*/
	function __construct($nazev_serialu,$id_zajezdu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
		$this->nazev_serialu = $this->check_slashes($this->check($nazev_serialu));
		$this->id_zajezdu = $this->check_int($id_zajezdu);
		
	//ziskani serialu z databaze	
		$this->data=$this->database->query($this->create_query($this->nazev_serialu,$this->id_zajezdu)) 
			or $this->chyba("Chyba při dotazu do databáze");
		
	//kontrola zda jsme ziskali prave 1 serial
		if(mysqli_num_rows($this->data)==1){
			$this->serial = mysqli_fetch_array($this->data);
		}else{
			$this->chyba("Název seriálu, nebo číslo zájezdu jsou neplatné");
		}
	}
        function is_zajezd_valid(){
            $termin_od = $this->serial["od"];
            $termin_do = $this->serial["do"];
            $date = Date("Y-m-d");
            $dlouhodobe = $this->serial["dlouhodobe_zajezdy"];
            if($dlouhodobe){
               if( $termin_do >= $date){
                   return 1;
               }
            }else{
               if( $termin_od >= $date){
                   return 1;
               } 
            }
            return 0;
        }
	/** ziska vsechny ceny k danemu zajezdu serialu*/
	function create_ceny(){
		$this->ceny= new Seznam_cen($this->get_id(),$this->get_id_zajezd());
	}	
	/** vytvoreni dotazu ze zadaneho nazvu serialu a id zajezdu*/
	function create_query($nazev_serialu,$id_zajezdu=""){
		$dotaz= "select `serial`.*, `zajezd`.*, `zeme_serial`.*, `zeme`.*, `typ_serial`.*,  `destinace`.*,
                                `dokument`.`dokument_url`,
                                
                                `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`, `objekt_ubytovani`.`pokoje_ubytovani` as `ubytovani_popis_ubytovani`,
                                `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                                `objekt_ubytovani`.`highlights` as `highlights_ubytovani`,`objekt`.`poznamka` as `poznamka_ubytovani`, `objekt_ubytovani`.`pes`, `objekt_ubytovani`.`pes_cena` , `objekt_ubytovani`.`posX` , `objekt_ubytovani`.`posY`


					from `serial` join
                                        `smluvni_podminky_nazev` on (`smluvni_podminky_nazev`.`id_smluvni_podminky_nazev` = `serial`.`id_sml_podm`) join
                                        `dokument` on (`dokument`.`id_dokument` = `smluvni_podminky_nazev`.`dokument_id`) join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
					`zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
					`typ_serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)
                                        left join (`destinace_serial` join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`) )
                                             on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
					
					                                             
					left join (`objekt_serial` join
                                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  
                            
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
			//		<span title=\"Objednávka zájezdu je dostupná pouze pro přihlášené uživatele\">
			//			Objednávka
			//		</span>
			//		</div>";		
			//}
			if($skryt_predbeznou_poptavku!=1){
				$poptavka= "
					<div class=\"".$class_poptavka."\">
					<a href=\"".$this->get_adress( array($adresa_zobrazit,$_GET["lev1"],$_GET["lev2"],$_GET["lev3"],$_GET["lev4"],"predbezna_poptavka") )."\">
						Předběžná poptávka
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
	
	function show_slevy_zkracene($typ_zobrazeni=""){
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
		 	or $this->chyba("Chyba při dotazu do databáze");			
						
		$data_zajezd = $this->database->query($dotaz_slevy_zajezd)
		 	or $this->chyba("Chyba při dotazu do databáze");	

                
                if($typ_zobrazeni=="array"){
                    $vystup = [];
                    while($sleva = mysqli_fetch_array($data)){
                        if($sleva["sleva_staly_klient"] == 1){
                            $poznamka = "Sleva bude odečtena po zkontrolování Vašich údajů pracovníkem CK";
							$typ = "staly";
                        }else{
                            $poznamka = "Sleva bude odečtena v průběhu objednávky"; 
							$typ = "sleva";
                        }                         
                        $sleva_record = [$sleva["nazev_slevy"], $sleva["zkraceny_nazev"],  $sleva["castka"], $sleva["mena"], $poznamka, $typ];
                        
                        $vystup[] = $sleva_record;
                    }   
                     while($sleva = mysqli_fetch_array($data_zajezd)){
                        if($sleva["sleva_staly_klient"] == 1){
                            $poznamka = "Sleva bude odečtena po zkontrolování Vašich údajů pracovníkem CK";
							$typ = "staly";
                        }else{
                            $poznamka = "Sleva bude odečtena v průběhu objednávky";  
							$typ = "sleva";                      
                        } 
                        $sleva_record = [$sleva["nazev_slevy"], $sleva["zkraceny_nazev"],  $sleva["castka"], $sleva["mena"], $poznamka, $typ];
                        
                        $vystup[] = $sleva_record;    
                     }    
                    if($this->serial["cena_pred_akci"]>0 and $this->serial["akcni_cena"]>0){
                            $sleva_procenta = round( 1-($this->serial["akcni_cena"] / $this->serial["cena_pred_akci"]),2) * 100 ;
                            $poznamka = "Cena před akcí: ".$this->serial["cena_pred_akci"]." Kč, nyní cena od: ".$this->serial["akcni_cena"]." Kč.";
                                    
                            $sleva_record = [$this->serial["popis_akce"], $this->serial["popis_akce"],  $sleva_procenta, "%", $poznamka, "akce"];
                            
                            $vystup[] = $sleva_record;  
                    }                        
                        
                    return $vystup;	
                }
                
		$vystup = "
                    <table style=\"width:296px;background-color:#ffefc0;margin-left:2px;\">";	
		while($sleva = mysqli_fetch_array($data)){
                        if($sleva["sleva_staly_klient"] == 1){
                            $text_slevy = "<span style=\"color:grey;font-size:0.8em;font-style:italic\">Sleva bude odečtena po zkontrolování Vašich údajů pracovníkem CK</span>";
                        }else{
                            $text_slevy = "<span style=\"color:grey;font-size:0.8em;font-style:italic\">Sleva bude odečtena v průběhu objednávky</span>";                        
                        }
                       $vystup .= "<tr><td style=\"border-bottom:1px dashed grey;margin:0px;padding:2px;\" valign=\"top\" title=\"".$sleva["nazev_slevy"]."\"><span class=\"serial_sleva\">".$sleva["castka"]."<span>".$sleva["mena"]."</span></span></td>
                                        <td style=\"border-bottom:1px dashed grey;margin:0px;padding:2px;\" title=\"".$sleva["nazev_slevy"]."\"><strong>SLEVA:</strong> ".$sleva["zkraceny_nazev"]."<br/>".$text_slevy."</td></tr>";
		}	
		while($sleva = mysqli_fetch_array($data_zajezd)){
                        if($sleva["sleva_staly_klient"] == 1){
                            $text_slevy = "<span style=\"color:grey;font-size:0.8em;font-style:italic\">Sleva bude odečtena po zkontrolování Vašich údajů pracovníkem CK</span>";
                        }else{
                            $text_slevy = "<span style=\"color:grey;font-size:0.8em;font-style:italic\">Sleva bude odečtena v průběhu objednávky</span>";                        
                        }                    
                       $vystup .= "<tr><td style=\"border-bottom:1px dashed grey;margin:0px;padding:2px;\" valign=\"top\" title=\"".$sleva["nazev_slevy"]."\"><span class=\"serial_sleva\">".$sleva["castka"]."<span>".$sleva["mena"]."</span></span></td>
                                        <td style=\"border-bottom:1px dashed grey;margin:0px;padding:2px;\" title=\"".$sleva["nazev_slevy"]."\"><strong>SLEVA:</strong> ".$sleva["zkraceny_nazev"]."<br/>".$text_slevy."</td></tr>";
		}
                if($this->serial["cena_pred_akci"]>0 and $this->serial["akcni_cena"]>0){
                        $sleva_procenta = round( 1-($this->serial["akcni_cena"] / $this->serial["cena_pred_akci"]),2) * 100 ;
			$vystup .= "<tr><td>
                                    <span style=\"font-size:2.2em;color:#009015;font-weight:bold;float:left;font-style:italic\" title=\"Sleva až ".$sleva_procenta."%\"/><span class=\"serial_sleva\">".$sleva_procenta."<span>%</span></span>
                                        </td><td title=\"".strip_tags($this->serial["popis_akce"])."\">
                                        <strong style=\"font-size:1.2em;\">AKCE:</strong> cena před akcí: <span style=\"color:red;text-decoration:line-through;font-size:1.2em;\">".$this->serial["cena_pred_akci"]."</span> Kč<br/>
                                        nyní cena od: <span style=\"color:green;font-size:1.6em;font-style:italic;\"><b>".$this->serial["akcni_cena"]." Kč</b></span></td></tr>";
                }
		$vystup .= "</table>";	
		
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
	
        function show_titulek(){
		if(!$this->get_error_message()){
                    if($this->get_nazev_zajezdu()){
                        $vystup = $this->get_nazev_zajezdu()." | ".$this->get_nazev()." | ".$this->get_destinace(" | ").$this->get_zeme(" | ").$this->get_nazev_typ(" | ")."SLAN tour";
                    }else{
                        $vystup = $this->get_nazev()." | ".$this->get_destinace(" | ").$this->get_zeme(" | ").$this->get_nazev_typ(" | ")." Termín: ".$this->change_date_en_cz_short($this->get_termin_od())." - ".$this->change_date_en_cz($this->get_termin_do())." | SLAN tour"; 
                    }
			
		}else{
			$vystup = "Chyba při přistupu k zájezdu".$this->get_nazev()." ";
		}
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
        function get_id_serial() { return $this->serial["id_serial"];}
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
			return "<h3 class=\"plain_text\">POZNÁMKY K TERMÍNU</h3><p>".$this->serial["poznamky_zajezd"]."</p>";
		}
	}
	
} 




?>
