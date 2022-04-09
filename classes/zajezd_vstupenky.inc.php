<?php
/** 
* trida pro zobrazení seznamu dokumentù seriálu
*/
/*------------------- SEZNAM DOKUMENTU -------------------  */
/*rozsireni tridy Serial o seznam dokumentu*/
class Seznam_vstupenek extends Generic_list{
	protected $id_serial;
        protected $termin_od;
        protected $termin_do;
	protected $pocet_radku;
        protected $typ_pozadavku;
	protected $celkova_cena_objednane_k_doobjednani;
        protected $celkova_cena_objednane_v_cene;
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì id serialu*/
	function __construct($typ_pozadavku, $id_serial, $termin_od="", $termin_do=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->id_serial = $this->check_int($id_serial);
                $this->termin_od = $this->check($termin_od);
                $this->termin_do = $this->check($termin_do);
	//ziskani zajezdu z databaze	
		$this->data=$this->database->query( $this->create_query($this->typ_pozadavku ) )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_radku=mysqli_num_rows($this->data);
	}
        function get_sum_cena(){
           $ceny=$this->database->query( $this->create_query("v_cene_suma_cen") )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
           $sum_cena = 0;
           while($zaznam = mysqli_fetch_array($ceny)){
             $sum_cena += $zaznam["cena"];
           }
           return $sum_cena;
        }
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho id serialu*/
	function create_query($typ_pozadavku){
            if($typ_pozadavku=="v_cene_suma_cen"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,max(`vstupenka`.`kategorie`) as `kategorie`, min(`vstupenka`.`cena`) as `cena`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial`
                                                                and `vstupenka_serial`.`zakladni_vstupenka`=1 ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial."
                                        and `vstupenka`.`vyprodano`!=1
                                        and `vstupenka`.`cena` > 0
                                group by `vstupenka`.`id_vstupenky`
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`  ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="v_cene_kapacity"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,max(`vstupenka`.`kategorie`) as `kategorie`, min(`vstupenka`.`cena`) as `cena`,`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                    GROUP_CONCAT(`vstupenka`.`vyprodano` SEPARATOR ',') as `vyprodano`,
                                    GROUP_CONCAT(`vstupenka`.`kapacita` SEPARATOR ',') as `kapacita`,
                                    GROUP_CONCAT(`vstupenka`.`na_dotaz` SEPARATOR ',') as `na_dotaz`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial`
                                                                and `vstupenka_serial`.`zakladni_vstupenka`=1 ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial."
                                        and `vstupenka`.`vyprodano`!=1
                                        and `vstupenka`.`cena` > 0
                                group by `vstupenka`.`id_vstupenky`
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`  ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="v_cene"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,min(`vstupenka`.`kategorie`),`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial`
                                                                and `vstupenka_serial`.`zakladni_vstupenka`=1 ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial."
                                group by `vstupenka`.`id_vstupenky`
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`  ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="k_doobjednani"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                    GROUP_CONCAT(`vstupenka`.`vyprodano` SEPARATOR ',') as `vyprodano`,
                                    GROUP_CONCAT(`vstupenka`.`kapacita` SEPARATOR ',') as `kapacita`,
                                    GROUP_CONCAT(`vstupenka`.`na_dotaz` SEPARATOR ',') as `na_dotaz`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial`
                                                                and `vstupenka_serial`.`zakladni_vstupenka`=0 ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial."
                                group by `vstupenka`.`id_vstupenky`
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`  ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="detail_k_doobjednani"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                    `vstupenka`.`kategorie`,`vstupenka`.`popis_kategorie`,`vstupenka`.`cena`,`vstupenka`.`kapacita`,`vstupenka`.`na_dotaz`,`vstupenka`.`vyprodano`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial`
                                                                and `vstupenka_serial`.`zakladni_vstupenka`=0 ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial."
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`, `vstupenka`.`id_vstupenky`,`vstupenka`.`kategorie` ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="detail_v_cene"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                    `vstupenka`.`kategorie`,`vstupenka`.`popis_kategorie`,`vstupenka`.`cena`,`vstupenka`.`kapacita`,`vstupenka`.`na_dotaz`,`vstupenka`.`vyprodano`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial`
                                                                and `vstupenka_serial`.`zakladni_vstupenka`=1 ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial."
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`, `vstupenka`.`id_vstupenky`,`vstupenka`.`kategorie` ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="objednavka_detail"){
		$dotaz ="select distinct `serial`.`id_serial`,
                                    `vstupenka`.`id_vstupenky`,`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                    `vstupenka`.`kategorie`,`vstupenka`.`popis_kategorie`,`vstupenka`.`cena`,`vstupenka`.`kapacita`,`vstupenka`.`na_dotaz`,`vstupenka`.`vyprodano`
				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`vstupenka_serial` on (`zajezd`.`id_serial` = `vstupenka_serial`.`id_serial` ) join
                                        `vstupenka` on (`vstupenka_serial`.`id_vstupenky` = `vstupenka`.`id_vstupenky`)

                                where `serial`.`id_serial`=".$this->id_serial." and `vstupenka`.`id_vstupenky`=".$this->id_vstupenky."
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`, `vstupenka`.`id_vstupenky`,`vstupenka`.`kategorie` ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="get_id_vstupenky"){
		$dotaz ="select distinct `vstupenka_serial`.`id_vstupenky`
                                       from `serial` join
					`vstupenka_serial` on (`serial`.`id_serial` = `vstupenka_serial`.`id_serial` )

                                where `serial`.`id_serial`=".$this->id_serial."  ";
		//echo $dotaz;
		return $dotaz;
            }
	}	
	/**zobrazeni prvku seznamu dokumentù*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="seznam"){
                        $kapacity_dotaz = explode(",", $this->get_na_dotaz());
                        $kapacity_volne = explode(",", $this->get_kapacita());
                        $kapacity = explode(",", $this->get_vyprodano());
                        $ceny = explode(",", $this->get_cena());
                        $min_cena = "";
                        $vyprodano = "<span style=\"color:red;font-weight:bold\">Vyprodáno!</span> ";
                        foreach ($kapacity as $i => $kapacita) {
                            if($kapacita == 0 ){
                                $min_cena = " cena od: ".$ceny[$i]." Kè";
                                $vyprodano = "";
                            }
                        }
                        if($vyprodano == "") {
                            $vyprodano = "<span style=\"color:blue;font-weight:bold\">Na Dotaz</span> ";
                            foreach ($kapacity_dotaz as $i => $dotaz) {
                                if($dotaz == 0 and $kapacity_volne[$i] > 0) {
                                    $vyprodano = "";
                                }
                            }
                        }
			$vystup="<li>".$vyprodano."<a href=\"?detail_vstupenky=".$this->get_id_vstupenky()."\"
                                        onclick=\"javascript:return showdiv('vstupenka_".$this->get_id_vstupenky()."');\"
                                        title=\"".$this->get_popis_souteze_noHTML()."\"><strong>
                                    ".$this->get_kod_souteze()." (".$this->get_sport().")</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
				 </a></li>";
			return $vystup;
		}else if($typ_zobrazeni=="seznam_kategorie"){
                      $kapacity_dotaz = explode(",", $this->get_na_dotaz());
                        $kapacity_volne = explode(",", $this->get_kapacita());
                        $kapacity = explode(",", $this->get_vyprodano());
                        $ceny = explode(",", $this->get_cena());
                        $min_cena = "";
                        $vyprodano = "<span style=\"color:red;font-weight:bold\">Vyprodáno!</span> ";
                        foreach ($kapacity as $i => $kapacita) {
                            if($kapacita == 0 ){
                                $min_cena = " cena od: ".$ceny[$i]." Kè";
                                $vyprodano = "";
                            }
                        }
                        if($vyprodano == "") {
                            $vyprodano = "<span style=\"color:blue;font-weight:bold\">Na Dotaz</span> ";
                            foreach ($kapacity_dotaz as $i => $dotaz) {
                                if($dotaz == 0 and $kapacity_volne[$i] > 0) {
                                    $vyprodano = "";
                                }
                            }
                        }
			$vystup="<li>".$vyprodano."<a href=\"?detail_vstupenky=".$this->get_id_vstupenky()."\"
                                        onclick=\"javascript:return showdiv('vstupenka_".$this->get_id_vstupenky()."');\"
                                        title=\"".$this->get_popis_souteze_noHTML()."\">
                                    ".$this->get_kod_souteze()." (".$this->get_sport().")<strong> kategorie ".$this->get_kategorie().", ".$this->get_cena()." Kè</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
				 </a></li>";
			return $vystup;
                }else if($typ_zobrazeni=="seznam_objednavka"){
			$vystup="<li title=\"".$this->get_popis_souteze_noHTML()."\">
                                    <input type=\"submit\" name=\"detail_vstupenky_".$this->get_id_vstupenky()."\"
                                        value=\"Objednat\" title=\"Zobrazit detail vstupenky s možností dokoupit vstupenky k zájezdu\"/>
                                    <strong>".$this->get_kod_souteze()." (".$this->get_sport().")</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
                                                                  </li>";
			return $vystup;
                }else if($typ_zobrazeni=="seznam_objednavka_nohref"){
			$vystup="<li title=\"".$this->get_popis_souteze_noHTML()."\">
                                    ".$this->get_kod_souteze()." (".$this->get_sport().")<strong> kategorie ".$this->get_kategorie().", ".$this->get_cena()." Kè</strong><br/>
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
                                    <input type=\"submit\" name=\"detail_vstupenky_".$this->get_id_vstupenky()."\"
                                        value=\"Pøíplatky\" title=\"Zobrazit detail vstupenky s možností zmìnit kategorii vstupenky\"/>

                                 </li>";
			return $vystup;
                }
	}
        function get_all_ids(){
           $ceny=$this->database->query( $this->create_query("get_id_vstupenky") )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
           $vstup = array();
           while($zaznam = mysqli_fetch_array($ceny)){
             $vstup[] = $zaznam["id_vstupenky"];
           }
           return $vstup;
        }
        function show_detail_objednavky($id_vstupenky) {
            $vstup_div = "";
            $close_button = "";
            $first=true;
            $last_id="";
            $this->id_vstupenky = intval($id_vstupenky);
                $data_vstup=$this->database->query( $this->create_query("objednavka_detail") )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                $i=0;
                while($vstupenka = mysqli_fetch_array($data_vstup)) {
                    if( $last_id != $vstupenka["id_vstupenky"]) {
                        //nova vstupenka
                        $last_id = $vstupenka["id_vstupenky"];
                        $close_button="<input type=\"submit\" name=\"vstupenky_objednat\" value=\"Pøidat vstupenky k objednávce\" />
                                      <input type=\"submit\" name=\"vstupenky_zrusit\" value=\"Zrušit objednávku vstupenek\" />";

                        if($this->id_vstupenky==$vstupenka["id_vstupenky"]) {
                            $show="style=\"display:block;\"";
                        }else {
                            $show="style=\"display:none;\"";
                        }
                        $vstup_div .= "<div class=\"hotel_detail_obal\" id=\"vstupenka_".$vstupenka["id_vstupenky"]."\" ".$show.">";
                        $vstup_div .= "
                            <input type=\"hidden\" name=\"id_editovane_vstupenky\" value=\"".$vstupenka["id_vstupenky"]."\" />
                            <div class=\"hotel_transparent_back\">&nbsp;</div>
                            <div class=\"hotel_detail\">
                                <h1>Detail vstupenky ".$vstupenka["kod_souteze"]." (".$vstupenka["sport"].")</h1>
                                <p>
                                    <b>Program:</b> ".$vstupenka["popis_souteze"]."<br/>
                                    <b>Datum a èas:</b> ".$this->change_date_en_cz($vstupenka["datum"]).", ".$vstupenka["cas_od"]." - ".$vstupenka["cas_do"]."<br/>
                                    <b>Místo konání:</b> ".$vstupenka["misto"]."<br/>
                                </p>
                                <p>
                                    <b>Nyní si zvolte kategorii a poèet vstupenek, který chcete zakoupit.</b>
                                </p>
                                <input type=\"hidden\" name=\"editovana_vstupenka\" value=\"".$vstupenka["id_vstupenky"]."\" />
                                <h3>Dostupné kategorie</h3>
                            <table cellspacing=\"2\" cellpadding=\"2\" >
                            <tr><th>Kategorie<th>Cena <th>Dostupnost <th>Popis kategorie <th>Objednávka </tr>
                            ";
                        $dostupnost="";
                        $cena="";
                        $kategorie="";
                        $popis="";
                        $zakladni_cena="";
                        $objednavka="";

                    }
                        if($vstupenka["zakladni_vstupenka"]==1){
                        //nova kategorie
                            if(intval($vstupenka["vyprodano"])!=1 and intval($vstupenka["cena"])>0){
                                $zakladni_cena = $vstupenka["cena"];
                                $zakladni_kategorie = $this->get_kategorie($vstupenka["kategorie"]);
                            }
                        }else{
                            $zakladni_cena = 0;
                            $zakladni_kategorie = 0;
                            $typ="doobjednani";
                        }
                        if(intval($vstupenka["vyprodano"])==1){
                            $dostupnost[$i]="<span style=\"color:red;\">Vyprodáno!</span>";
                        }else if(intval($vstupenka["na_dotaz"])==1 or intval($vstupenka["kapacita"])<=0){
                            $dostupnost[$i]="<span style=\"color:blue;\">Na dotaz</span>";
                        }else{
                            if(intval($vstupenka["kapacita"])<=5){
                                $kapacita_vstup = intval($vstupenka["kapacita"]);
                            }else{
                                $kapacita_vstup = "6+";
                            }
                            $dostupnost[$i]="<span style=\"color:green;\">Volno ".$kapacita_vstup."</span>";
                        }
                        $kategorie[$i] = $this->get_kategorie($vstupenka["kategorie"]);
                        $cena[$i]= $vstupenka["cena"];
                        $popis[$i]= $vstupenka["popis_kategorie"];
                        $objednavka[$i] = "<input style=\"width:50px;\" type=\"text\" name=\"vstupenka_pocet_".$vstupenka["id_vstupenky"]."_".$vstupenka["kategorie"]."\"
                                                    value=\"".$_SESSION["vstupenka_pocet_".$vstupenka["id_vstupenky"]."_".$vstupenka["kategorie"].""]."\" />";
                        $i++;
                 }

                 $j=0;
                 while($dostupnost[$j]!="") {
                     if($typ=="doobjednani"){
                          $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]."
                                    <td><b>cena ".$cena[$j]." Kè</b>
                                    <td><b>".$dostupnost[$j]." </b>
                                    <td>".$popis[$j]."
                                    <td>".$objednavka[$j]."
                                    </tr>";
                     }else if($kategorie[$j]==$zakladni_kategorie) {
                         $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]." (v cenì zájezdu)
                                    <td><b>pøíplatek 0 Kè</b>
                                    <td><b>".$dostupnost[$j]." </b>
                                    <td>".$popis[$j]."
                                    <td>".$objednavka[$j]."
                                    </tr>";
                     }else {
                         $priplatek = $cena[$j] - $zakladni_cena;
                         $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]."
                                    <td><b>pøíplatek ".$priplatek." Kè</b>
                                    <td><b>".$dostupnost[$j]." </b>
                                    <td>".$popis[$j]."
                                    <td>".$objednavka[$j]."
                                    </tr>";
                     }

                     $j++;

                 }
                 $vstup_div .= "</table>".$close_button."</div></div>";


             return $vstup_div;
         }


         function set_zakladni_vstupenky($pocet){
                $data_vstup=$this->database->query( $this->create_query("detail_v_cene") )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                $i=0;
                $last_id="";
                $vstupenky_list=array();
                $zakladni_cena=array();
                $zakladni_kategorie=array();
                while($vstupenka = mysqli_fetch_array($data_vstup)) {
                    if($last_id!=$vstupenka["id_vstupenky"]){
                       $last_id=$vstupenka["id_vstupenky"];
                       $vstupenky_list[]=$vstupenka["id_vstupenky"];
                    }
                    if(intval($vstupenka["vyprodano"])!=1 and intval($vstupenka["cena"])>0){
                            $zakladni_cena[$vstupenka["id_vstupenky"]] = $vstupenka["cena"];
                            $zakladni_kategorie[$vstupenka["id_vstupenky"]] = $vstupenka["kategorie"];
                    }
                }
                foreach ($vstupenky_list as $vstup) {
                    $_SESSION["vstupenka_zakladni_cena_".$vstup.""] = $zakladni_cena[$vstup];
                    for($i=1;$i<=6;$i++){
                       unset($_SESSION["vstupenka_pocet_".$vstup."_".$i.""]);
                    }
                    $_SESSION["vstupenka_pocet_".$vstup."_".$zakladni_kategorie[$vstup].""]=$pocet;
                }
         }

         function show_objedane_v_cene(){
                $data_vstup=$this->database->query( $this->create_query("detail_v_cene") )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                $i=0;
                $res="<ul class=\"list1\">";
                $cena = 0;
                $j=0;
                while($vstupenka = mysqli_fetch_array($data_vstup)) {
                    if($_SESSION["vstupenka_pocet_".$vstupenka["id_vstupenky"]."_".$vstupenka["kategorie"].""]!=""){
                        $j++;
                        $pocet = intval($_SESSION["vstupenka_pocet_".$vstupenka["id_vstupenky"]."_".$vstupenka["kategorie"].""]);  
                        $zakladni_cena = intval($_SESSION["vstupenka_zakladni_cena_".$vstupenka["id_vstupenky"].""]); 
                        $vysledna_cena = $vstupenka["cena"] - $zakladni_cena;
                        $cena+=$pocet*$vysledna_cena;
                        $vstupenka["cas_od"] = $this->get_cas($vstupenka["cas_od"]);
                        $vstupenka["cas_do"] = $this->get_cas($vstupenka["cas_do"]);
                       $res.="<li>
                                    ".$vstupenka["kod_souteze"]." (".$vstupenka["sport"]."), ".$this->change_date_en_cz($vstupenka["datum"]).", ".$vstupenka["cas_od"]." - ".$vstupenka["cas_do"].";
                                    <strong> kategorie ".$this->get_kategorie($vstupenka["kategorie"]).", poèet: ".$pocet.", pøíplatek celkem: ".($pocet*$vysledna_cena)." Kè</strong>
                                    <input type=\"hidden\" name=\"id_vstup_v_cene_".$j."\" value=\"".$vstupenka["id_vstupenky"]."\"/>
                                    <input type=\"hidden\" name=\"kategorie_vstup_v_cene_".$j."\" value=\"".$vstupenka["kategorie"]."\"/>
                                    <input type=\"hidden\" name=\"pocet_vstup_v_cene_".$j."\" value=\"".$pocet."\"/>
                                    <input type=\"hidden\" name=\"cena_vstup_v_cene_".$j."\" value=\"".$vysledna_cena."\"/>
                              </li>";
                    }
                }
                $this->celkova_cena_objednane_v_cene = $cena;
                return $res."</ul>
                        <input type=\"hidden\" name=\"vstupenek_v_cene\" value=\"".$j."\"/>
                        <strong>Pøíplatky celkem: ".$cena." Kè</strong>";
         }


         function show_objedane_k_doobjednani(){
                $data_vstup=$this->database->query( $this->create_query("detail_k_doobjednani") )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                $i=0;
                $j=0;
                $res="<ul class=\"list1\">";
                $cena = 0;
                while($vstupenka = mysqli_fetch_array($data_vstup)) {
                    $j++;
                    //print_r($vstupenka);                    
                    if($_SESSION["vstupenka_pocet_".$vstupenka["id_vstupenky"]."_".$vstupenka["kategorie"].""]!=""){
                        $pocet = intval($_SESSION["vstupenka_pocet_".$vstupenka["id_vstupenky"]."_".$vstupenka["kategorie"].""]);
                        $cena+=$pocet*$vstupenka["cena"];
                        $vstupenka["cas_od"] = $this->get_cas($vstupenka["cas_od"]);
                        $vstupenka["cas_do"] = $this->get_cas($vstupenka["cas_do"]);
                       $res.="<li>
                                    ".$vstupenka["kod_souteze"]." (".$vstupenka["sport"]."), ".$this->change_date_en_cz($vstupenka["datum"]).", ".$vstupenka["cas_od"]." - ".$vstupenka["cas_do"].";
                                    <strong> kategorie ".$this->get_kategorie($vstupenka["kategorie"]).", poèet: ".$pocet.", cena celkem: ".($pocet*$vstupenka["cena"])." Kè</strong>
                                    <input type=\"hidden\" name=\"id_vstup_k_doobjednani_".$j."\" value=\"".$vstupenka["id_vstupenky"]."\"/>
                                    <input type=\"hidden\" name=\"kategorie_vstup_k_doobjednani_".$j."\" value=\"".$vstupenka["kategorie"]."\"/>
                                    <input type=\"hidden\" name=\"pocet_vstup_k_doobjednani_".$j."\" value=\"".$pocet."\"/>
                                 </li>";
                    }
                }
                $this->celkova_cena_objednane_k_doobjednani = $cena;
                return $res."</ul>
                        <input type=\"hidden\" name=\"vstupenek_k_doobjednani\" value=\"".$j."\"/>
                        <strong>Cena dokoupených vstupenek celkem: ".$cena." Kè</strong>";
         }


        function show_detail_vstupenek($typ_pozadavku) {
            $vstup_div = "";
            $close_button = "";
            $first=true;
            $last_id="";
            if($typ_pozadavku=="detail_k_doobjednani") {
                $data_vstup=$this->database->query( $this->create_query($typ_pozadavku) )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                while($vstupenka = mysqli_fetch_array($data_vstup)) {
                    if( $last_id != $vstupenka["id_vstupenky"]) {
                        //nova vstupenka
                        $last_id = $vstupenka["id_vstupenky"];
                        if($first){
                            $first=false;
                        }else{
                           $vstup_div .= "</table>".$close_button."</div></div>";
                        }
                        $close_button="<a class=\"button\" href=\"?\" onclick=\"javascript:return hidediv('vstupenka_".$vstupenka["id_vstupenky"]."');\"
                                        title=\"zavøít detail vstupenky\">Zavøít detail vstupenky</a>";

                        if($_GET["detail_vstupenky"]==$vstupenka["id_vstupenky"]) {
                            $show="style=\"display:block;\"";
                        }else {
                            $show="style=\"display:none;\"";
                        }
                        $vstup_div .= "<div class=\"vstupenky_detail_obal\" id=\"vstupenka_".$vstupenka["id_vstupenky"]."\" ".$show.">";
                        $vstup_div .= "
                            <div class=\"hotel_transparent_back\">&nbsp;</div>
                            <div class=\"hotel_detail\">
                                <h1>Detail vstupenky ".$vstupenka["kod_souteze"]." (".$vstupenka["sport"].")</h1>
                                <p>
                                    <b>Program:</b> ".$vstupenka["popis_souteze"]."<br/>
                                    <b>Datum a èas:</b> ".$this->change_date_en_cz($vstupenka["datum"]).", ".$vstupenka["cas_od"]." - ".$vstupenka["cas_do"]."<br/>
                                    <b>Místo konání:</b> ".$vstupenka["misto"]."<br/>
                                </p>
                                <h3>Dostupné kategorie</h3>
                            <table cellspacing=\"2\" cellpadding=\"2\" >";
                        
                    }
                        //nova kategorie
                        if(intval($vstupenka["vyprodano"])==1){
                            $dostupnost="<span style=\"color:red;\">Vyprodáno!</span>";
                        }else if(intval($vstupenka["na_dotaz"])==1 or intval($vstupenka["kapacita"])<=0){
                            $dostupnost="<span style=\"color:blue;\">Na dotaz</span>";
                        }else{
                           /* if(intval($vstupenka["kapacita"])<=5){
                                $kapacita_vstup = intval($vstupenka["kapacita"]);
                            }else{
                                $kapacita_vstup = "6+";
                            }*/
                            $dostupnost="<span style=\"color:green;\">Volno ".$kapacita_vstup."</span>";
                        }
                        $vstup_div .= "
                            <tr><td>kategorie ".$this->get_kategorie($vstupenka["kategorie"])."
                            <td><b>cena ".$vstupenka["cena"]." Kè</b>
                            <td><b>".$dostupnost." </b>
                            <td>".$vstupenka["popis_kategorie"]."
                            </tr>";                        
                 }
                 $vstup_div .= "</table>".$close_button."</div></div>";


             }else if($typ_pozadavku=="detail_v_cene") {
                $data_vstup=$this->database->query( $this->create_query($typ_pozadavku) )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                while($vstupenka = mysqli_fetch_array($data_vstup)) {
                    if( $last_id != $vstupenka["id_vstupenky"]) {
                        //nova vstupenka
                        $i=0;
                        $last_id = $vstupenka["id_vstupenky"];
                        if($first){
                            $first=false;
                        }else{
                           $j=0;
                           while($dostupnost[$j]!="") {
                               if($kategorie[$j]==$zakladni_kategorie) {
                                   $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]." (v cenì zájezdu)
                                    <td><b>pøíplatek 0 Kè</b>
                                    <td><b>".$dostupnost[$j]."</b>
                                    <td>".$popis[$j]."
                                    </tr>";
                               }else {
                                   $priplatek = $cena[$j] - $zakladni_cena;
                                   $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]."
                                    <td><b>pøíplatek ".$priplatek." Kè</b>
                                    <td> <b>".$dostupnost[$j]." </b>
                                    <td>".$popis[$j]."
                                    </tr>";
                               }

                               $j++;
                           }
                           $vstup_div .= "</table>".$close_button."</div></div>";
                        }
                        $close_button="<a class=\"button\" href=\"?\" onclick=\"javascript:return hidediv('vstupenka_".$vstupenka["id_vstupenky"]."');\"
                                        title=\"zavøít detail vstupenky\">Zavøít detail vstupenky</a>";

                        if($_GET["detail_vstupenky"]==$vstupenka["id_vstupenky"]) {
                            $show="style=\"display:block;\"";
                        }else {
                            $show="style=\"display:none;\"";
                        }
                        $vstup_div .= "<div class=\"vstupenky_detail_obal\" id=\"vstupenka_".$vstupenka["id_vstupenky"]."\" ".$show.">";
                        $vstup_div .= "
                            <div class=\"hotel_transparent_back\">&nbsp;</div>
                            <div class=\"hotel_detail\">
                                <h1>Detail vstupenky ".$vstupenka["kod_souteze"]." (".$vstupenka["sport"].")</h1>
                                <p>
                                    <b>Program:</b> ".$vstupenka["popis_souteze"]."<br/>
                                    <b>Datum a èas:</b> ".$this->change_date_en_cz($vstupenka["datum"]).", ".$vstupenka["cas_od"]." - ".$vstupenka["cas_do"]."<br/>
                                    <b>Místo konání:</b> ".$vstupenka["misto"]."<br/>
                                </p>
                                <h3>Dostupné kategorie</h3>
                            <table cellspacing=\"2\" cellpadding=\"2\" >";
                        $dostupnost="";
                        $cena="";
                        $kategorie="";
                        $popis="";
                        $zakladni_cena="";

                    }
                        
                        //nova kategorie
                        if(intval($vstupenka["vyprodano"])!=1 and intval($vstupenka["cena"])>0){
                            $zakladni_cena = $vstupenka["cena"];
                            $zakladni_kategorie = $this->get_kategorie($vstupenka["kategorie"]);
                        }
                        if(intval($vstupenka["vyprodano"])==1){
                            $dostupnost[$i]="<span style=\"color:red;\">Vyprodáno!</span>";
                        }else if(intval($vstupenka["na_dotaz"])==1 or intval($vstupenka["kapacita"])<=0){
                            $dostupnost[$i]="<span style=\"color:blue;\">Na dotaz</span>";
                        }else{
                           /* if(intval($vstupenka["kapacita"])<=5){
                                $kapacita_vstup = intval($vstupenka["kapacita"]);
                            }else{
                                $kapacita_vstup = "6+";
                            }*/
                            $dostupnost[$i]="<span style=\"color:green;\">Volno ".$kapacita_vstup."</span>";
                        }
                        $kategorie[$i] = $this->get_kategorie($vstupenka["kategorie"]);
                        $cena[$i]= $vstupenka["cena"];
                        $popis[$i]= $vstupenka["popis_kategorie"];
                        $i++;
                 }

                 $j=0;
                 while($dostupnost[$j]!="") {
                     if($kategorie[$j]==$zakladni_kategorie) {
                         $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]." (v cenì zájezdu)
                                    <td><b>pøíplatek 0 Kè</b>
                                    <td> <b>".$dostupnost[$j]." </b>
                                    <td>".$popis[$j]."
                                    </tr>";
                     }else {
                         $priplatek = $cena[$j] - $zakladni_cena;
                         $vstup_div .= "
                                    <tr><td>kategorie ".$kategorie[$j]."
                                    <td><b>pøíplatek ".$priplatek." Kè</b>
                                    <td> <b>".$dostupnost[$j]." </b>
                                    <td>".$popis[$j]."
                                    </tr>";
                     }

                     $j++;

                 }
                 $vstup_div .= "</table>".$close_button."</div></div>";
                 
             }

             return $vstup_div;
         }
               
	/*metody pro pristup k parametrum*/
	function get_id_vstupenky() { return $this->radek["id_vstupenky"];}
	function get_sport() { return $this->radek["sport"];}
	function get_kod_souteze() { return $this->radek["kod_souteze"];}
        function get_kategorie($kat="") {
            if($kat==""){$kat=$this->radek["kategorie"];}
            switch ($kat) {
                case "1":
                   return "AA"; break;
                case "2":
                    return "A"; break;
                case "3":
                    return "B"; break;
                case "4":
                    return "C"; break;
                case "5":
                    return "D"; break;
                case "6":
                    return "E"; break;
            }

         }
        function get_cena() { return $this->radek["cena"];}
	function get_popis_souteze() { return $this->radek["popis_souteze"];}
        function get_popis_souteze_noHTML() { return strip_tags($this->radek["popis_souteze"]);}
	function get_datum() { return $this->radek["datum"];}
        function get_vyprodano() { return $this->radek["vyprodano"];}
        function get_na_dotaz() { return $this->radek["na_dotaz"];}
        function get_kapacita() { return $this->radek["kapacita"];}
        function get_cas($cas) {
            $casy = explode(":", $cas);
            return $casy[0].":".$casy[1];
        }
	function get_cas_od() {
            $casy = explode(":", $this->radek["cas_od"]);
            return $casy[0].":".$casy[1];
            }
	function get_cas_do() { 
            $casy = explode(":", $this->radek["cas_do"]);
            return $casy[0].":".$casy[1];
            }
	function get_misto() { return $this->radek["misto"];}

        function get_foto(){
            return "<img class=\"fleft\" style=\"margin:5px;\" src=\"/".ADRESAR_NAHLED."/".$this->get_foto_url()."\"
				alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
				title=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
				width=\"175\" />";
        }
	function get_pocet_radku() { return $this->pocet_radku;}

        function get_celkova_cena_objednane_v_cene(){
            return $this->celkova_cena_objednane_v_cene;
        }
        function get_celkova_cena_objednane_k_doobjednani(){
            return $this->celkova_cena_objednane_k_doobjednani;
        }

}



?>
