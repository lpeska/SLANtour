<?php
/** 
* trida pro zobrazení seznamu dokumentù seriálu
*/
/*------------------- SEZNAM DOKUMENTU -------------------  */
/*rozsireni tridy Serial o seznam dokumentu*/
class Vstupenky_list extends Generic_list{
        protected $id_vstupenky;
	protected $id_serial;
        protected $sport;
        protected $datum;
	protected $order_by;
        protected $last_datum;
        protected $typ_pozadavku;
	protected $first;
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì id serialu*/
	function __construct($typ_pozadavku, $sport, $datum, $order_by="", $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->sport = $this->check($sport);
                $this->datum = $this->change_date_cz_en($this->check($datum));
                $this->order_by = $this->check($order_by);
                $this->pocet_zaznamu = $this->check($pocet_zaznamu);

	//ziskani zajezdu z databaze	
		$this->data=$this->database->query( $this->create_query($this->typ_pozadavku ) )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_radku=mysqli_num_rows($this->data);
                $this->last_datum="";
                $this->first=true;
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
            if($typ_pozadavku=="volny_prodej"){
            if($this->sport!=""){
                $where_sport=" `serial`.`nazev_web` like '%".$this->sport."%' and";
            }else{
                $where_sport="";
            }
            if($this->datum!=0){
                $where_datum=" `serial`.`doprava` ='".$this->datum."' and";
            }else{
                $where_datum="";
            }
            if($this->order_by!=""){
                $order_by=" order by ".$this->order_by($this->order_by)."";
            }else{
                $order_by=" order by `vstupenka`.`datum`, `vstupenka`.`cas_od` ";
            }
            if($this->pocet_zaznamu!=""){
                $limit=" limit ".$this->pocet_zaznamu."";
            }else{
                $limit="";
            }

		$dotaz ="select `vstupenka`.`id_vstupenky`,`vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                `vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,`vstupenka`.`volny_prodej`,
                                GROUP_CONCAT(`vstupenka`.`kategorie` SEPARATOR ',') as `kategorie`,
                                GROUP_CONCAT(`vstupenka`.`vyprodano` SEPARATOR ',') as `vyprodano`,
                                GROUP_CONCAT(`vstupenka`.`kapacita` SEPARATOR ',') as `kapacita`,
                                GROUP_CONCAT(`vstupenka`.`na_dotaz` SEPARATOR ',') as `na_dotaz`,
                                GROUP_CONCAT(`vstupenka`.`cena` SEPARATOR ',') as `cena`
				from `vstupenka`

                                where  `vstupenka`.`cena` > 0 and
                                       `vstupenka`.`datum` >= '2012-01-01'
                                        and ".$where_sport.$where_datum." 1
                                group by `vstupenka`.`id_vstupenky`
                                ".$order_by.
                                $limit." ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="detail_vstupenky"){
		$dotaz ="select distinct `vstupenka`.`id_vstupenky`,`vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`popis_souteze`,
                                    `vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,`vstupenka`.`misto`,
                                    `vstupenka`.`kategorie`,`vstupenka`.`popis_kategorie`,`vstupenka`.`cena`,`vstupenka`.`kapacita`,`vstupenka`.`na_dotaz`,`vstupenka`.`vyprodano`
				from  `vstupenka`
                                where `vstupenka`.`id_vstupenky`=".$this->id_vstupenky."
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`, `vstupenka`.`id_vstupenky`,`vstupenka`.`kategorie` ";
		//echo $dotaz;
		return $dotaz;
            }else if($typ_pozadavku=="neprirazene"){
                 $dotaz ="select distinct `vstupenka`.`id_vstupenky`,`vstupenka`.`datum`,`vstupenka`.`cas_od`,`vstupenka`.`cas_do`,
                                `vstupenka`.`sport`,`vstupenka`.`kod_souteze`, `vstupenka`.`volny_prodej`
				from `vstupenka`
                                left join `vstupenka_serial` on (`vstupenka`.`id_vstupenky` = `vstupenka_serial`.`id_vstupenky` )
                                where  `vstupenka`.`cena` > 0 and
                                       `vstupenka`.`datum` >= '2012-01-01' and
                                       `vstupenka_serial`.`id_serial` is null
                                order by `vstupenka`.`datum` ";
		//echo $dotaz;
		return $dotaz;
            }
	}
/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
    function order_by($vstup){
        switch ($vstup) {
            case "datum":
                return "`vstupenka`.`datum`, `vstupenka`.`cas_od`";
                break;
            case "cena":
                return "`cena`";
                break;
            case "nazev":
                return "`vstupenka`.`sport`,`vstupenka`.`kod_souteze`";
                break;
            case "random":
                 return "RAND()";
                 break;
             }
                    //pokud zadan nespravny vstup, vratime zajezd.od
         return "`vstupenka`.`datum`, `vstupenka`.`cas_od`";
     }
	/**zobrazeni prvku seznamu dokumentù*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="seznam"){	
			$vystup="<li><a href=\"/vstupenky\" title=\"".$this->get_popis_souteze_noHTML()."\"><strong>
                                    ".$this->get_kod_souteze()." (".$this->get_sport().")</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
				 </a></li>";
			return $vystup;
		}else if($typ_zobrazeni=="seznam_kategorie"){

			$vystup="<li><a href=\"/vstupenky\" title=\"".$this->get_popis_souteze_noHTML()."\">
                                    ".$this->get_kod_souteze()." (".$this->get_sport().")<strong> ".$this->get_kategorie()." cena od: ".$this->get_cena()." Kè</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
				 </a></li>";
			return $vystup;
                }else if($typ_zobrazeni=="seznam_kategorie_odkazy"){
                        $vystup="";
                        if($this->get_datum()!=$this->last_datum){
                            $this->last_datum = $this->get_datum();
                            if($first){
                                $first=false;
                            }else{
                                $vystup.="</ul>";
                            }
                            $vystup.="<h4><b>".$this->change_date_en_cz($this->get_datum())."</b></h4>
                                        <ul class=\"list1\">";
                        }
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
                                if($dotaz == 0 or $kapacity_volne[$i] > 0) {
                                    $vyprodano = "";
                                }
                            }
                        }
			$vystup.="
                                 <li title=\"".$this->get_popis_souteze_noHTML()."\" style=\"clear:both;\">
                                    ".$vyprodano.$this->get_kod_souteze()." (".$this->get_sport().")<strong> ".$this->get_kategorie()." ".$min_cena."</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
                                    <a href=\"/zajezdy/vstupenka/".$this->get_kod_souteze()."\" title=\"Zobrazit všechny zájezdy s touto vstupenkou\">Zobrazit zájezdy</a> |
                                    <a href=\"?detail_vstupenky=".$this->get_id_vstupenky()."\">Detail vstupenky</a>";
                        if(intval($this->radek["volny_prodej"])==1){
                              $vystup.=" | <a href=\"/objednat/vstupenka/".$this->get_kod_souteze()."\" title=\"Objednat vstupenky\" >Objednat vstupenku</a>";
                        }
			$vystup.="</li>";
			return $vystup;

                }else if($typ_zobrazeni=="seznam_neprirazene"){
                        $vystup="";
                        if($this->get_datum()!=$this->last_datum){
                            $this->last_datum = $this->get_datum();
                            if($first){
                                $first=false;
                            }else{
                                $vystup.="</ul>";
                            }
                            $vystup.="<h4><b>".$this->change_date_en_cz($this->get_datum())."</b></h4>
                                        <ul class=\"list1\">";
                        }

			$vystup.="<li><strong>
                                    ".$this->get_kod_souteze()." (".$this->get_sport().")</strong>:
                                    ".$this->change_date_en_cz($this->get_datum()).", ".$this->get_cas_od()." - ".$this->get_cas_do()."
				 </li>";
			return $vystup;
                }
	}


        function show_detail_vstupenky($id_vstupenky) {
            $vstup_div = "";
            $close_button = "";
            $first=true;
            $this->id_vstupenky = $this->check_int($id_vstupenky);
            $last_id="";
                $data_vstup=$this->database->query( $this->create_query("detail_vstupenky") )
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
                        $close_button="<a class=\"button\" href=\"?\" 
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
                            $dostupnost="<span style=\"color:green;\">Volno</span>";
                        }
                        $vstup_div .= "
                            <tr><td>".$this->get_kategorie($vstupenka["kategorie"])."
                            <td><b>cena ".$vstupenka["cena"]." Kè</b>
                            <td>".$dostupnost."
                            <td>".$vstupenka["popis_kategorie"]."
                            </tr>";
                 }
                 $vstup_div .= "</table>".$close_button."</div></div>";

             return $vstup_div;
         }


	/*metody pro pristup k parametrum*/
	function get_id_vstupenky() { return $this->radek["id_vstupenky"];}
	function get_sport() { return $this->radek["sport"];}
	function get_kod_souteze() { return $this->radek["kod_souteze"];}
        function get_kategorie($vstup="") {
            if($vstup==""){
               $vstup = $this->radek["kategorie"];
            }
            $kategorie = "kategorie: ";
            $kats = explode(",", $vstup);
            foreach ($kats as $kat) {
                switch ($kat) {
                    case "1":
                        $kategorie .= "AA, "; break;
                    case "2":
                        $kategorie .= "A, "; break;
                    case "3":
                        $kategorie .= "B, "; break;
                    case "4":
                        $kategorie .= "C, "; break;
                    case "5":
                        $kategorie .= "D, "; break;
                    case "6":
                        $kategorie .= "E, "; break;
                }
            }
            return $kategorie;            
        }
        function get_cena() { return $this->radek["cena"];}
	function get_popis_souteze() { return $this->radek["popis_souteze"];}
        function get_popis_souteze_noHTML() { return strip_tags($this->radek["popis_souteze"]);}
	function get_datum() { return $this->radek["datum"];}
        function get_vyprodano() { return $this->radek["vyprodano"];}
        function get_na_dotaz() { return $this->radek["na_dotaz"];}
        function get_kapacita() { return $this->radek["kapacita"];}
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
}



?>
