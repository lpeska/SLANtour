<?php
/** 
* trida pro zobrazení seznamu cen zájezdu vè. kapacit
*/
/*------------------- SEZNAM CEN -------------------  */
/*rozsireni tridy Serial_with_zajezd o seznam cen*/
class Seznam_cen extends Generic_list{
	protected $id_zajezdu;
	protected $id_serialu;
	protected $pocet_cen;
	protected $last_typ_ceny;
        protected $celkova_castka;
	protected $vse_volne; //funguje az po precteni seznamu cen!! hlasi, zda jsou vsechny ceny volne
	private $id_pole;
	public $database; //trida pro odesilani dotazu
	private $scriptStart;
        private $scriptEnd;
	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì id serialu a zajezdu*/
	function __construct($id_serialu,$id_zajezdu){
		//trida pro odesilani dotazu
		$this->last_typ_ceny = 0;
		$this->database = Database::get_instance();
				
		$this->id_zajezdu = $this->check_int($id_zajezdu);
		$this->id_serialu = $this->check_int($id_serialu);

		$this->vse_volne = 1; //vse volne, dokud se neprokaze opak
	//ziskani zajezdu z databaze	
		$this->data=$this->database->query( $this->create_query() )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_cen = mysqli_num_rows($this->data);
                $this->id_pole = 0;
                $this->scriptStart = "
                 <script type=\"text/javascript\">   
   
                ";
                $this->scriptEnd = "</script>";
	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu do databaze*/
	function create_query(){
		$dotaz = "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`zakladni_cena`,`cena`.`typ_ceny`,`cena`.`kapacita_bez_omezeni`,`cena`.`use_pocet_noci`,                    
                            `cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`kapacita_volna`, `cena_zajezd`.`na_dotaz`, `cena_zajezd`.`vyprodano`,
                                sum(`objekt_kategorie_termin`.`kapacita_volna` * `objekt_kategorie`.`hlavni_kapacita`) as `objekt_kapacita_volna`,
                                max(`objekt_kategorie_termin`.`kapacita_bez_omezeni`) as `objekt_kapacita_bez_omezeni`,
                                min(`objekt_kategorie_termin`.`vyprodano`) as `objekt_vyprodano`,
                                min(`objekt_kategorie_termin`.`na_dotaz`) as `objekt_na_dotaz`,
                                group_concat(distinct `objekt_kategorie`.`popis_kategorie` separator \" <br/>\") as `popis_kategorie`,
                                group_concat(`foto_url` order by `foto`.`id_foto` separator \";\") as `foto_url`,
                                group_concat(`nazev_foto` order by `foto`.`id_foto` separator \";\") as `nazev_foto`
                            from `zajezd` join
					`cena` on (`zajezd`.`id_serial` = `cena`.`id_serial`) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1)
                                left join  (
                                    `cena_zajezd_tok` join
                                    `objekt_kategorie_termin` on (`cena_zajezd_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and 
                                                                  `cena_zajezd_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) join
                                    `objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `cena_zajezd_tok`.`id_objekt_kategorie`) join
                                    `cena_objekt_kategorie` on (`objekt_kategorie`.`id_objekt_kategorie` = `cena_objekt_kategorie`.`id_objekt_kategorie` )
                                   left join ( 
                                    `foto_objekt_kategorie`  join
                                    `foto` on (`foto`.`id_foto` = `foto_objekt_kategorie`.`id_foto`)
                                    )on (`cena_zajezd_tok`.`id_objekt_kategorie` = `foto_objekt_kategorie`.`id_objekt_kategorie`)
                                    
                                ) on(`cena_zajezd_tok`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and  `cena_zajezd_tok`.`id_cena` = `cena_zajezd`.`id_cena`
                                      and `cena_objekt_kategorie`.`id_cena` = `cena`.`id_cena`)    
                            where `zajezd`.`id_zajezd`= ".$this->id_zajezdu." 
                            group by `cena_zajezd`.`id_cena`,`cena_zajezd`.`id_zajezd`
                            order by `cena`.`zakladni_cena` desc,`cena`.`typ_ceny`,`cena`.`poradi_ceny`,`cena`.`nazev_ceny` ";		
                echo "<!--".$dotaz."-->";
		return $dotaz;
	}
	
	function name_of_typ_ceny($typ_ceny){
		if($typ_ceny == 1){
			return "Služby";
		}else if($typ_ceny == 2){
			return "LAST MINUTE";
		}else if($typ_ceny == 3){
			return "Slevy";
		}else if($typ_ceny == 4){
			return "Pøíplatky";
		}else if($typ_ceny == 5){
			return "Odjezdová místa";			
		}else{
			return "";
		}
	}
	
	function name_of_typ_ceny_vstupenka($typ_ceny){
		if($typ_ceny == 1){
			return "VSTUPENKY";
		}else if($typ_ceny == 2){
			return "LAST MINUTE VSTUPENKY";
		}else if($typ_ceny == 3){
			return "Slevy";
		}else if($typ_ceny == 4){
			return "Pøíplatky";
		}else if($typ_ceny == 5){
			return "Odjezdová místa";			
		}else{
			return ""; 
		}
	}
	
	/**zobrazeni hlavicky seznamu cen*/
	function show_list_header($vypis=""){

			if($vypis!=""){
				$out = "<tr><th colspan=\"4\">".$vypis."</th>";
			}
			$vystup="<table cellpadding=\"2\" cellspacing=\"2\" class=\"zajezdy\">
							".$out." ";
			return $vystup;
	}
	/**zobrazeni prvku seznamu cen*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="tabulka"){				
			$vystup="<tr>
							<td>".$this->get_nazev_ceny()."</td>
							<td>".$this->get_dostupnost()."</td>
							<td class=\"cena\">".$this->get_castka()." ".$this->get_mena()."</td>
						</tr>";
			return $vystup;
		}
	}	

        function get_script(){
            return $this->scriptStart.$this->scriptEnd;
        }
	/**zobrazeni prvku seznamu cen*/
	function show_list_item_vstupenka($typ_zobrazeni){
            $this->id_pole++;
		if($typ_zobrazeni=="tabulka"){	
                        //zjistim, zda jsou k dispozici doplnujici informace z objektu:
                        if($this->radek["popis_kategorie"]!="" or $this->radek["foto_url"]!=""){
                            $additional_row = "<tr style=\"margin:0;padding:0;\"><td colspan=\"4\" style=\"margin:0;padding:0;\">
                                <div  id=\"hidden_".$this->radek["id_cena"]."\" style=\"display:none;\">".$this->radek["popis_kategorie"]."<br/>";
                            $foto_url_array = explode(";", $this->radek["foto_url"]);
                            $foto_nazev_array = explode(";", $this->radek["nazev_foto"]);
                            foreach ($foto_url_array as $key => $url) {
                                if($url!=""){
                                    $additional_row .= "<a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$url."\"\" title=\"".$foto_nazev_array[$key]."\">
                                        <img class=\"round\" height=\"60\" src=\"https://www.slantour.cz/".ADRESAR_MINIIKONA."/".$url."\"\" alt=\"".$foto_nazev_array[$key]."\" /></a>";
                                }
                            }
                            $additional_row .= "</div>";
                            $priceLinkStart = "<a href=\"#\" title=\"Zobrazit detail služby\" id=\"showHide_".$this->radek["id_cena"]."\" > ";
                            $priceLinkEnd = "<div style=\"float:right;font-size:0.8em;\">[podrobnosti]</div></a>";
                            $this->scriptStart .= "
                                $(\"#showHide_".$this->radek["id_cena"]."\").click(function(){
                                    $(\"#hidden_".$this->radek["id_cena"]."\").toggle(\"blind\", 500);
                                    return false;
                                });
                            ";
                        }else{
                            $additional_row = "";
                            $priceLinkStart = "";
                            $priceLinkEnd = "";
                        }
                    
			if($this->last_typ_ceny != $this->radek["typ_ceny"]){
				$this->last_typ_ceny = $this->radek["typ_ceny"];
				$vystup="
					<tr>
						<th class=\"nadpis_cena_".$this->radek["typ_ceny"]."\">".$this->name_of_typ_ceny_vstupenka($this->radek["typ_ceny"])."</th>
						<th class=\"nadpis_cena_".$this->radek["typ_ceny"]."\">Dostupnost</th>
						<th class=\"nadpis_cena_".$this->radek["typ_ceny"]."\">Cena</th>
					
					</tr>
                                        
				";
			}else{
				$vystup="";
			}
			if($_POST["id_cena_".$i] == $this->get_id_cena() ) {
                            $pocet = $this->check_int($_POST["cena_pocet_".$i]);
                        }else {
                            $pocet = 0;
                        }
			$vystup.="<tr class=\"cena_".$this->radek["typ_ceny"]."\">
							<td>".$priceLinkStart.$this->get_nazev_ceny().$priceLinkEnd."</td>
							<td>".$this->get_dostupnost()."</td>
							<td class=\"cena\">".$this->get_castka()." ".$this->get_mena()."</td>
						<input type=\"hidden\" name=\"id_cena_".$this->id_pole."\" value=\"".$this->get_id_cena()."\" />
						<input type=\"hidden\" name=\"typ_ceny_".$this->id_pole."\" value=\"".$this->get_typ_ceny()."\" />
                                                        </td>
						</tr>"
                                    .$additional_row;
			return $vystup;
		}
	}		
	
	/**zobrazeni hlavicky seznamu cen*/
	function show_list_header_hotel($vypis=""){
	
			if($vypis!=""){
				$out = "<tr><th colspan=\"4\">".$vypis."</th>";
			}
			$vystup="<table class=\"ceny\">
							".$out." ";
			return $vystup;
	}	
	/**zobrazeni prvku seznamu cen*/
	function show_list_item_hotel($typ_zobrazeni){
                $this->id_pole++;
		if($typ_zobrazeni=="tabulka"){	
                        $let_text="";
                        $query_let = "select `cena_promenna_cenova_mapa`.`poznamka`, `cena_promenna_cenova_mapa`.`external_id`, 
                                        (abs(DATEDIFF(`cena_promenna_cenova_mapa`.termin_od, `zajezd`.`od`)) +  
                                            IF((`termin_do_shift` is null or `termin_do_shift`=0),
                                                abs(DATEDIFF(`cena_promenna_cenova_mapa`.termin_do, `zajezd`.`do`)),
                                                abs(DATEDIFF(`zajezd`.`do`,`cena_promenna_cenova_mapa`.termin_do)-1))) 
                                         as sanity_check   
                        
                                    from
                                    `cena_promenna_cenova_mapa` 
                                        join `cena_promenna` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                                        join `zajezd` on (`zajezd`.`id_zajezd`= ".$this->id_zajezdu.")
                                    where
                                        `cena_promenna_cenova_mapa`.`poznamka` != \"\" AND
                                       `cena_promenna`.`id_cena` = ".$this->radek["id_cena"]."  and `cena_promenna`.`typ_promenne` = \"letuska\"
                                        and ((`cena_promenna_cenova_mapa`.termin_od <= `zajezd`.`od` and `cena_promenna_cenova_mapa`.termin_do >= `zajezd`.`do` and `cena_promenna_cenova_mapa`.`castka` is not null and `cena_promenna_cenova_mapa`.`termin_do_shift` is null ) 
                                            or (`cena_promenna_cenova_mapa`.termin_od >= `zajezd`.`od` and `cena_promenna_cenova_mapa`.termin_do <= `zajezd`.`do` and `cena_promenna_cenova_mapa`.`castka` is not null and `cena_promenna_cenova_mapa`.`no_dates_generation` >= 1 ) 
                                            or (`cena_promenna_cenova_mapa`.termin_od <= `zajezd`.`od` and `cena_promenna_cenova_mapa`.termin_do >= DATE_ADD(`zajezd`.`do`, INTERVAL -(`cena_promenna_cenova_mapa`.`termin_do_shift`) DAY) and `cena_promenna_cenova_mapa`.`castka` is not null and `cena_promenna_cenova_mapa`.`termin_do_shift` is not null))
                                   order by sanity_check
                                    ";
                        //echo "<!--".$query_let."-->";
                        $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_let);
                        while ($row = mysqli_fetch_array($query)) {
                            $let_text = str_replace($row["external_id"].", ", "", $row["poznamka"]);
                            $let_text = preg_replace("/\. Nalezeno: .*/", "", $let_text);
                            $let_text = str_replace(array("[","]"), array("<",">"), $let_text);
                            $let_text = preg_replace("/(2[0-9][0-9][0-9])-([0-9][0-9])-([0-9]+)/", "\$3.\$2. \$1", $let_text);
                            $let_text = "<p>".$let_text."</p>";
                            
                           $pos_let_tam = stripos($let_text,"Let tam" );
                           $pos_let_zpet = stripos($let_text,"Let zpìt" );
                           preg_match('/[0-9]+\.[0-9]+\. 2[0-9][0-9][0-9]/', $let_text, $date_let_tam, PREG_OFFSET_CAPTURE, $pos_let_tam); 
                           preg_match('/[0-9]+\.[0-9]+\. 2[0-9][0-9][0-9]/', $let_text, $date_let_zpet, PREG_OFFSET_CAPTURE, $pos_let_zpet); 
                           
                           $datum_tam = $date_let_tam[0][0];
                           $datum_zpet = $date_let_zpet[0][0];
                           
                           $let_text = str_replace("<b>Let tam</b>:", "<b>Let tam: $datum_tam</b>", $let_text);
                           $let_text = str_replace("<b>Let zpìt</b>:", "<b>Let zpìt: $datum_zpet</b>", $let_text);
                           $let_text = preg_replace("/(\+[0-9])/", "<b>[\$1]</b>", $let_text);
                           $let_text = preg_replace("/- ([A-Z]+ [0-9]+):([^<]+)/", "- \$2 (\$1)", $let_text);
                           
                           break;
                           
                        }
                        
                        if($this->radek["popis_kategorie"]!="" or $this->radek["foto_url"]!="" or $let_text!=""){
                            if($let_text!=""){
                                $let_text = "<h4>Pøedpokládané lety:</h4>".$let_text."<br/>";
                            }
                            $additional_row = "<tr style=\"margin:0;padding:0;\"><td colspan=\"4\" style=\"margin:0;padding:0;\">
                                <div  id=\"hidden_".$this->radek["id_cena"]."\" style=\"display:none;\">".$this->radek["popis_kategorie"]."<br/>";
                            $foto_url_array = explode(";", $this->radek["foto_url"]);
                            $foto_nazev_array = explode(";", $this->radek["nazev_foto"]);
                            foreach ($foto_url_array as $key => $url) {
                                if($url!=""){
                                    $additional_row .= "<a href=\"https://www.slantour.cz/".ADRESAR_FULL."/".$url."\"\" title=\"".$foto_nazev_array[$key]."\">
                                        <img class=\"round\" height=\"60\" src=\"https://www.slantour.cz/".ADRESAR_MINIIKONA."/".$url."\"\" alt=\"".$foto_nazev_array[$key]."\" /></a>";
                                }
                            }
                            $additional_row .= $let_text."</div>";
                            $priceLinkStart = "<a href=\"#\" title=\"Zobrazit detail služby\" id=\"showHide_".$this->radek["id_cena"]."\" > ";
                            $priceLinkEnd = "<div style=\"float:right;font-size:0.8em;\">[podrobnosti]</div></a>";
                            

                            $this->scriptStart .= "
                                $(\"#showHide_".$this->radek["id_cena"]."\").click(function(){
                                    $(\"#hidden_".$this->radek["id_cena"]."\").toggle(\"blind\", 500);
                                    return false;
                                });
                            ";
                        }else{
                            $additional_row = "";
                            $priceLinkStart = "";
                            $priceLinkEnd = "";
                        }
			if($this->last_typ_ceny != $this->radek["typ_ceny"]){
				$this->last_typ_ceny = $this->radek["typ_ceny"];
				$vystup="
					<tr >
						<th class=\"nadpis_cena_".$this->radek["typ_ceny"]."\">".$this->name_of_typ_ceny($this->radek["typ_ceny"])."</th>
						<th style=\"width:70px;\" class=\"nadpis_cena_".$this->radek["typ_ceny"]."\">Kapacita</th>
						<th style=\"width:70px;\" class=\"nadpis_cena_".$this->radek["typ_ceny"]."\">Cena</th>
					</tr>
				";
			}else{
				$vystup="";
			}
                        if($_POST["id_cena_".$i] == $this->get_id_cena() ) {
                            $pocet = $this->check_int($_POST["cena_pocet_".$i]);
                        }else {
                            $pocet = 0;
                        }
			$vystup.="<tr class=\"suda cena_".$this->radek["typ_ceny"]."\">
							<td>".$priceLinkStart.$this->get_nazev_ceny().$priceLinkEnd."</td>
							<td>".$this->get_dostupnost()."</td>
							<td class=\"cena\"><strong>".$this->get_castka()." ".$this->get_mena()."</strong></td>
						<input type=\"hidden\" name=\"id_cena_".$this->id_pole."\" value=\"".$this->get_id_cena()."\" />
						<input type=\"hidden\" name=\"typ_ceny_".$this->id_pole."\" value=\"".$this->get_typ_ceny()."\" />
                                                        </td>
						</tr>"
                                .$additional_row;
			return $vystup;
		}
	}	


    function calculate_pocet_noci($od, $do, $upresneni_od, $upresneni_do){
	 if($upresneni_od!="" and $upresneni_od!="0000-00-00" and $upresneni_do!="" and $upresneni_do!="0000-00-00"){
	 	$pole_od=explode("-", $upresneni_od);
		$pole_do=explode("-", $upresneni_do);
	 }else{
	 	$pole_od=explode("-", $od);
		$pole_do=explode("-", $do);
	 }
	 	//echo "...........".$pole_od[2]."-".$pole_od[1]."-".$pole_od[0];
		//echo "...........".$pole_do[2]."-".$pole_do[1]."-".$pole_do[0];

	 	$time_od = mktime(0,0,0,$pole_od[1],$pole_od[2],$pole_od[0]);
		$time_do = mktime(0,0,0,$pole_do[1],$pole_do[2],$pole_do[0]);
		$pocet_noci = (round(($time_do - $time_od) / (24*60*60)));
		if($pocet_noci<0){
	 		$pocet_noci=0;
	 	}
		return $pocet_noci;
    }

    function calculate_prize($castka, $pocet, $pocet_noci, $use_pocet_noci=0){
	 if($pocet_noci==0){
	 	$pocet_noci=1;
	 }
	 if($use_pocet_noci!=0){
            return $castka*$pocet*$pocet_noci;
	 }else{
            return $castka*$pocet;
	 }

    }

	/**zobrazeni formulare pro objednavku sluzeb*/
        function show_form_objednavka() {
            GLOBAL $serial;
            $pocet_noci = $this->calculate_pocet_noci($serial->get_termin_od(), $serial->get_termin_do(), $serial->change_date_cz_en($_POST["upresneni_terminu_od"]), $serial->change_date_cz_en($_POST["upresneni_terminu_do"]));
            $javascript="<script type=\"text/javascript\">
			 function count_celkova_cena(){
				var pocet_cen = document.objednavka.pocet_cen.value;                               
				var i = 0;
				var soucet = 0;
				while(i < pocet_cen){
					i++;
					var pole_form = \"cena_pocet_\"+i;
					var x = document.getElementsByName(pole_form);
					var pocet = parseInt(x[0].value);
					soucet = soucet + pocet*pole_cen[i];
				}
				var y = document.getElementById(\"celkova_cena\");
				y.innerHTML = \"<b>Celková cena služeb: \"+soucet+\" Kè</b>\";
                                
                                if(typ_zalohy==\"procenta\"){
                                    var zaloha = Math.round( soucet * 0.01 * castka_zaloha );
                                    var z = document.getElementById(\"zaloha\");
                                    z.innerHTML = \"<b>Záloha \"+castka_zaloha+\"%: \"+zaloha+\" Kè</b>\";
                                }
                                
			}
			function set_pocet_cen(){
				var x = document.getElementsByName(\"pocet_osob\");
                                var pocet = parseInt(x[0].value);
                                var pole_form = \"cena_pocet_1\";
                                var y = document.getElementsByName(pole_form);
                                y[0].value = pocet;
                                count_celkova_cena();
			}
			var pole_cen;
			pole_cen = new Array(0
			";

            $vystup="   <br/>
                        <strong>Poèet úèastníkù zájezdu:</strong> 
                        <input type=\"text\" name=\"pocet_osob\" value=\"".$_POST["pocet_osob"]."\" />
                        <br/>
                        <table cellspacing=\"2\" cellpadding=\"2\" class=\"sluzby\">
				<tr>
					<th>Název služby</th>
					<th>Volná místa</th>
					<th>Cena</th>
					<th>Objednávka</th>
				</tr>
			";	
            $i=0;

            $sum_cena = 0;
            while( $this->get_next_radek() ) {
                $i++;
                
                if($_POST["id_cena_".$i] == $this->get_id_cena() ) {
                    $pocet = $this->check_int($_POST["cena_pocet_".$i]);
                }else {
                    $pocet = 0;
                }


                if($last_typ_ceny != $this->radek["typ_ceny"]) {
                    $last_typ_ceny = $this->radek["typ_ceny"];
                    $vystup.="
					<tr>
						<td  colspan=\"4\" class=\"nadpis_cena_objednavka\">".$this->name_of_typ_ceny($this->radek["typ_ceny"])."</td>
					</tr>
				";
                }
                if($last_typ_ceny == 5) {
                    $act_castka = $this->calculate_prize($this->get_castka(), 1, $pocet_noci, $this->radek["use_pocet_noci"]);
                    $javascript .= ", ".$act_castka;
                    $vystup=$vystup."
					<tr>
						<td>
						<input type=\"hidden\" name=\"id_cena_".$i."\" value=\"".$this->get_id_cena()."\" />
						<input type=\"hidden\" name=\"typ_ceny_".$i."\" value=\"".$this->get_typ_ceny()."\" />
						".$this->get_nazev_ceny()."</td> 
						<td>".$this->get_dostupnost()."</td>
						<td class=\"cena\">".$this->get_castka()." ".$this->get_mena()."</td>
						<td><input name=\"cena_pocet_".$i."\"  onChange=\"count_celkova_cena()\" type=\"text\" value=\"".$pocet ."\" />
                                                    <input name=\"nasobit_noci_".$i."\"  type=\"hidden\" value=\"".$this->get_nasobit_poctem_noci()."\" /></td>
					</tr>
					";
                     $sum_cena += $act_castka*$pocet;
                }else if($last_typ_ceny == 1 or $last_typ_ceny == 2) {
                        $act_castka = $this->calculate_prize($this->get_castka(), 1, $pocet_noci, $this->radek["use_pocet_noci"]);
                        $javascript .= ", ".$act_castka;
                        $vystup=$vystup."
					<tr>
						<td><input type=\"hidden\" name=\"id_cena_".$i."\" value=\"".$this->get_id_cena()."\" />
						<input type=\"hidden\" name=\"typ_ceny_".$i."\" value=\"".$this->get_typ_ceny()."\" />
						".$this->get_nazev_ceny()."</td> 
						<td>".$this->get_dostupnost()."</td>
						<td class=\"cena\">".($this->get_castka()+$cena_vstupenek)." ".$this->get_mena()."</td>
						<td><input name=\"cena_pocet_".$i."\"  onChange=\"count_celkova_cena()\" type=\"text\" value=\"".$pocet ."\" /> <span class=\"red\">**</span>
                                                     <input name=\"nasobit_noci_".$i."\"  type=\"hidden\" value=\"".$this->get_nasobit_poctem_noci()."\" /></td>
					</tr>
					";
                        $sum_cena += $act_castka*$pocet;
                 }else {
                        $act_castka = $this->calculate_prize($this->get_castka(), 1, $pocet_noci, $this->radek["use_pocet_noci"]);
                        $javascript .= ", ".$act_castka;
                        $vystup=$vystup."
					<tr>
						<td><input type=\"hidden\" name=\"id_cena_".$i."\" value=\"".$this->get_id_cena()."\" />
						<input type=\"hidden\" name=\"typ_ceny_".$i."\" value=\"".$this->get_typ_ceny()."\" />
						".$this->get_nazev_ceny()."</td> 
						<td>".$this->get_dostupnost()."</td>
						<td class=\"cena\">".$this->get_castka()." ".$this->get_mena()."</td>
						<td><input name=\"cena_pocet_".$i."\"  onChange=\"count_celkova_cena()\" type=\"text\" value=\"".$pocet ."\" />
                                                     <input name=\"nasobit_noci_".$i."\"  type=\"hidden\" value=\"".$this->get_nasobit_poctem_noci()."\" /></td>
					</tr>
					";
                         $sum_cena += $act_castka*$pocet;
                 }
            }
		$javascript.=");
		</script>";
		$vystup= $javascript."<input type=\"hidden\" name=\"pocet_cen\" value=\"".$i."\" />".$vystup."<br/>
					<tr><td colspan=\"4\"><span id=\"celkova_cena\"></span>\n</td></tr>
                                        <tr><td colspan=\"4\"><span id=\"zaloha\"></span>\n</td></tr></table>";

                $this->celkova_castka = $sum_cena;
		return $vystup;

	}	
/**zobrazeni formulare pro objednavku sluzeb*/
        function show_rekapitulace_objednavka() {
            $this->celkova_castka = 0;
            GLOBAL $serial;
            $pocet_noci = Rezervace_objednavka::static_calculate_pocet_noci($serial->get_termin_od(), $serial->get_termin_do(), $serial->change_date_cz_en($_POST["upresneni_terminu_od"]), $serial->change_date_cz_en($_POST["upresneni_terminu_do"]));
            $vystup="
                        <table cellspacing=\"2\" cellpadding=\"2\" class=\"rekapitulace_cen\" style=\"width:100%\">
				<tr>
					<th>Název služby</th>
                                        <th>Poèet</th>
					<th>Cena</th>
                                        <th>Celkem</th>
				</tr>
			";
            $i=0;
            while( $this->get_next_radek() ) {
                $i++;

                if($_POST["id_cena_".$i] == $this->get_id_cena() ) {
                    $pocet = $this->check_int($_POST["cena_pocet_".$i]);
                }else {
                    $pocet = 0;
                }

                    if($pocet>0){
                        $act_castka = ($this->calculate_prize($this->get_castka(), 1, $pocet_noci, $this->radek["use_pocet_noci"]))*$pocet;
                        $vystup=$vystup."
					<tr>
						<td>".$this->get_nazev_ceny()."</td>
                                                <td style=\"text-alighn:right;\">".$pocet."
						<td style=\"text-alighn:right;\" class=\"cena\">".$this->get_castka()." ".$this->get_mena()."</td>
                                                <td style=\"text-alighn:right;\">".$act_castka." ".$this->get_mena()."
					</tr>
					";
                        $this->celkova_castka += $act_castka;
                    }
            }
		$vystup= $vystup."</table>";
		return $vystup;

	}
	
	
	/*metody pro pristup k parametrum*/
        function get_celkova_castka() { return $this->celkova_castka;}
	function get_vse_volne() { return $this->vse_volne;}
	function get_pocet_cen() { return $this->pocet_cen;}
	function get_id_cena() { return $this->radek["id_cena"];}
	function get_nazev_ceny() { return $this->radek["nazev_ceny"];}
	function get_zakladni_cena() { return $this->radek["zakladni_cena"];}
	function get_typ_ceny() { return $this->radek["typ_ceny"];}
	function get_castka() { return $this->radek["castka"];}
	function get_mena() { return $this->radek["mena"];}
	    function get_nasobit_poctem_noci(){ return $this->radek["use_pocet_noci"];}
	
		//cena kde je kapacita vetsinou neomezena (napr. priplatky za vecere, vylety...)
	function get_na_dotaz() { return $this->radek["na_dotaz"];}
	function get_vyprodano() { return $this->radek["vyprodano"];}
	function get_kapacita() { return $this->radek["kapacita_volna"];}
	function get_kapacita_bez_omezeni() { return $this->radek["kapacita_bez_omezeni"];}
        
       function get_objekt_na_dotaz() { return $this->radek["objekt_na_dotaz"];}
	function get_objekt_vyprodano() { return $this->radek["objekt_vyprodano"];}
        function get_objekt_kapacita() { return $this->radek["objekt_kapacita_volna"];}
	function get_objekt_kapacita_bez_omezeni() { return $this->radek["objekt_kapacita_bez_omezeni"];}
                
	function get_dostupnost(){
		if($this->get_vyprodano()==1 or $this->get_objekt_vyprodano()==1){
			$this->vse_volne = 0;
			return "<span style=\"color:red;\"><b>Vyprodáno!</b></span>";
		}else if($this->get_na_dotaz()==1 or $this->get_objekt_na_dotaz()==1){
			$this->vse_volne = 0;
			return "<span style=\"color:blue;\"><b>Na dotaz</b></span>";
		}else if($this->get_kapacita_bez_omezeni()==1 or $this->get_objekt_kapacita_bez_omezeni()==1){
			return "<span style=\"color:green;\"><b>Volno</b></span>";
		}else if($this->get_kapacita()>0 or $this->get_objekt_kapacita()>0){			
			return "<span style=\"color:green;\"><b>Volno</b></span>";  //.$this->get_kapacita().
		}else{
                        $this->vse_volne = 0;
			return "<span style=\"color:blue;\"><b>Na dotaz</b></span>";
		}		
	}
	function cena_dostupna(){
		if($this->get_vyprodano()==1 or $this->get_objekt_vyprodano()==1){
			return 0;					
		}else if($this->get_na_dotaz()==1 or $this->get_objekt_na_dotaz()==1){
			return 0;					
		}else if($this->get_kapacita_bez_omezeni()==1 or $this->get_objekt_kapacita_bez_omezeni()==1){
			return 1;					
		}else if($this->get_kapacita()<=0 and $this->get_objekt_kapacita()<=0 ){
			return 0;					
		}else{
			return 1; //.$this->get_kapacita().
		}		
	}	
}




?>
