<?php
/** 
* trida pro zobrazení seznamu dokumentù seriálu
*/
/*------------------- SEZNAM DOKUMENTU -------------------  */
/*rozsireni tridy Serial o seznam dokumentu*/
class Seznam_ubytovani extends Generic_list{
	protected $id_serial;
        protected $termin_od;
        protected $termin_do;
	protected $pocet_radku;
	protected $data_backup;
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì id serialu*/
	function __construct($id_serial, $termin_od, $termin_do){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->id_serial = $this->check_int($id_serial);
                $this->termin_od = $this->check($termin_od);
                $this->termin_do = $this->check($termin_do);
	//ziskani zajezdu z databaze	
		$this->data=$this->database->query( $this->create_query() )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		$this->data_backup = $this->data;
		$this->pocet_radku=mysqli_num_rows($this->data);
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho id serialu*/
	function create_query(){
		$dotaz ="select `serial`.`id_serial`,
                                    `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev`,`ubytovani`.`nazev_web`,`ubytovani`.`popisek`, `ubytovani`.`popis`,
                                     `cena_zajezd`.`castka`, `cena`.`kratky_nazev`,
                                    `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`

				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`ubytovani` on (`zajezd`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                                        `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                                        `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1 )
                                        left join
                                            (`foto_ubytovani` join
                                             `foto` on (`foto_ubytovani`.`id_foto` = `foto`.`id_foto`) )
                                        on (`foto_ubytovani`.`id_ubytovani` = `ubytovani`.`id_ubytovani` and `foto_ubytovani`.`zakladni_foto`=1)
				where `zajezd`.`id_serial`=".$this->id_serial."
                                        and `zajezd`.`od`='".$this->termin_od."'
                                        and `zajezd`.`do`='".$this->termin_do."'
                                order by `ubytovani`.`nazev` ";
//		echo $dotaz;
		return $dotaz;
	}	
	/**zobrazeni prvku seznamu dokumentù*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni=="seznam"){	
			$vystup="<li>
						<a  href=\"/".ADRESAR_DOKUMENT."/".$this->get_dokument_url()."\">
							".$this->get_nazev_dokument()."
						</a> - ".$this->get_popisek_dokument()."
						</li>";
			return $vystup;
		}
	}
      function show_detail_ubytovani() {
          GLOBAL $cena_vstupenek;
            $dotaz ="select `serial`.`id_serial`,
                                    `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev`,`ubytovani`.`nazev_web`,`ubytovani`.`popisek`, `ubytovani`.`popis`

				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`ubytovani` on (`zajezd`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
   				where `zajezd`.`id_serial`=".$this->id_serial."
                                        and `zajezd`.`od`='".$this->termin_od."'
                                        and `zajezd`.`do`='".$this->termin_do."'
                                order by `ubytovani`.`nazev` ";
          //echo $dotaz;

            $vstup_div = "";
            $close_button = "";
            $first=true;
            $last_id="";

                $data_ubyt=$this->database->query( $dotaz )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                while($ubytovani = mysqli_fetch_array($data_ubyt)) {
                        if($_GET["detail_ubytovani"]==$ubytovani["id_ubytovani"]) {
                            $show="style=\"display:block;\"";
                        }else {
                            $show="style=\"display:none;\"";
                        }
                        $vstup_div .= "<div class=\"hotel_detail_obal\" id=\"ubytovani_".$ubytovani["id_ubytovani"]."\" ".$show.">";
                        $dotaz_foto= "SELECT `foto_ubytovani`.`zakladni_foto`,
							`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`, `foto`.`foto_url`
						FROM `foto_ubytovani` JOIN
							`foto` on (`foto`.`id_foto` =`foto_ubytovani`.`id_foto`)
						WHERE `foto_ubytovani`.`id_ubytovani`= ".$ubytovani["id_ubytovani"]."
						ORDER BY `foto_ubytovani`.`zakladni_foto` desc,`foto`.`id_foto` ";
              //echo $dotaz_foto;
                        $data_foto=$this->database->query( $dotaz_foto )
                            or $this->chyba("Chyba pøi dotazu do databáze");
                        $first_foto=true;
                        $vystup_foto="";
                        while($foto = mysqli_fetch_array($data_foto)) {
                            if($first_foto){
                                $first_foto=false;
                                $vystup_foto="
					<div class=\"prvni_foto\">
					<a href=\"/".ADRESAR_FULL."/".$foto["foto_url"]."\"
							class=\"preview\"  target=\"_blank\"
							title=\"".$foto["nazev_foto"]." - ".$foto["popisek_foto"]."\" >
							<img 	src=\"/".ADRESAR_NAHLED."/".$foto["foto_url"]."\"
									width=\"210\"
									alt=\"".$foto["nazev_foto"]." - ".$foto["popisek_foto"]."\"
									title=\"".$foto["nazev_foto"]." - ".$foto["popisek_foto"]."\" />
					</a>
					</div>
                                        <div class=\"dalsi_foto_obal\">";
                            }else{
                               $vystup_foto.="<div class=\"dalsi_foto\">
							<a href=\"/".ADRESAR_FULL."/".$foto["foto_url"]."\"
							class=\"preview\"  target=\"_blank\"
							title=\"".$foto["nazev_foto"]." - ".$foto["popisek_foto"]."\" >
							<img 	src=\"/".ADRESAR_IKONA."/".$foto["foto_url"]."\"
									alt=\"".$foto["nazev_foto"]." - ".$foto["popisek_foto"]."\"
									title=\"".$foto["nazev_foto"]." - ".$foto["popisek_foto"]."\"
									width=\"100\" />
							</a>
						</div>";

                            }
                        }
                        if($vystup_foto!=""){
                            $vystup_foto.="</div>";
                        }

                        $vstup_div .= "
                            <div class=\"hotel_transparent_back\">&nbsp;</div>
                            <div class=\"hotel_detail\">
                                <h1>Detail ubytování ".$ubytovani["nazev"]." </h1>
                                ".$vystup_foto."
                                <p>
                                    <b>".$ubytovani["popisek"]."</b>
                                </p>
                                <p>
                                    ".$ubytovani["popis"]."
                                </p>                                
                                <h3>Ceny služeb</h3>
                                <table cellspacing=\"2\" cellpadding=\"2\" class=\"sluzby\">
				<tr>
					<th>Název služby</th>
					<th>Volná místa</th>
					<th>Cena</th>
				</tr> ";


                        $dotaz_ceny= "select `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`zakladni_cena`,`cena`.`typ_ceny`,`cena`.`kapacita_bez_omezeni`,
							`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`cena_zajezd`.`kapacita_volna`, `cena_zajezd`.`na_dotaz`, `cena_zajezd`.`vyprodano` 
					from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                                        `ubytovani` on (`zajezd`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
					`cena` on (`zajezd`.`id_serial` = `cena`.`id_serial`) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1 )
					where `ubytovani`.`id_ubytovani`=".$ubytovani["id_ubytovani"]." 
                                        and `serial`.`id_serial`=".$this->id_serial."
                                    order by `cena`.`zakladni_cena` desc,`cena`.`typ_ceny`,`cena`.`poradi_ceny`,`cena`.`nazev_ceny` ";
                        $data_ceny=$this->database->query( $dotaz_ceny )
                            or $this->chyba("Chyba pøi dotazu do databáze");
                        $vystup_ceny="";
                        $last_typ_ceny="";
                        while($ceny = mysqli_fetch_array($data_ceny)) {
                                if($ceny["typ_ceny"]==1 or $ceny["typ_ceny"]==2){
                                    $cena = $ceny["castka"] + $cena_vstupenek;
                                }else{
                                    $cena = $ceny["castka"];
                                }
                                if($last_typ_ceny != $ceny["typ_ceny"]) {
                                    $last_typ_ceny = $ceny["typ_ceny"];
                                    $vystup_ceny.="
					<tr>
						<td  colspan=\"3\" class=\"nadpis_cena_objednavka\">".$this->name_of_typ_ceny($ceny["typ_ceny"])."</td>
					</tr>
                                    ";
                                }
                               $vystup_ceny.="<tr>
							<td><b>".$ceny["nazev_ceny"]."</b></td>
							<td>".$this->get_dostupnost($ceny["vyprodano"],$ceny["na_dotaz"],$ceny["kapacita_bez_omezeni"],$ceny["kapacita_volna"])."</td>
							<td class=\"cena\">".$cena." ".$ceny["mena"]."</td>
						</tr>";                            
                        }

                        $vstup_div .= $vystup_ceny;

                        $close_button="<a class=\"button\" href=\"?\" onclick=\"javascript:return hidediv('ubytovani_".$ubytovani["id_ubytovani"]."');\"
                                        title=\"zavøít detail ubytování\">Zavøít detail ubytovani</a>";
                        $vstup_div .= "</table>".$close_button."<div class=\"clear\">&nbsp;</div></div></div>";
                 }


             return $vstup_div;
         }
        function get_all_nazev_web() {
            $dotaz ="select `serial`.`id_serial`,
                                    `ubytovani`.`id_ubytovani`,`ubytovani`.`nazev`,`ubytovani`.`nazev_web`

				from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`ubytovani` on (`zajezd`.`id_ubytovani` = `ubytovani`.`id_ubytovani`)
   				where `zajezd`.`id_serial`=".$this->id_serial."
                                        and `zajezd`.`od`='".$this->termin_od."'
                                        and `zajezd`.`do`='".$this->termin_do."'
                                order by `ubytovani`.`nazev` ";
          //echo $dotaz;

                $vystup = array();
                $data_ubyt=$this->database->query( $dotaz )
                    or $this->chyba("Chyba pøi dotazu do databáze");
                while($ubytovani = mysqli_fetch_array($data_ubyt)) {
                        $vystup[]=$ubytovani["nazev_web"];
                 }
             return $vystup;
         }
	function get_dostupnost($vyprodano,$na_dotaz,$kapacita_bez_omezeni,$kapacita){
		if($vyprodano==1){
			$this->vse_volne = 0;
			return "<span style=\"color:red;\"><b>Vyprodáno!</b></span>";
		}else if($na_dotaz==1){
			$this->vse_volne = 0;
			return "<span style=\"color:blue;\"><b>Na dotaz</b></span>";
		}else if($kapacita_bez_omezeni==1){
			return "<span style=\"color:green;\"><b>Volno</b></span>";
		}else if($kapacita<=0){
			$this->vse_volne = 0;
			return "<span style=\"color:red;\"><b>Na dotaz</b></span>";
		}else{
			return "<span style=\"color:green;\"><b>Volno</b></span>"; //.$this->get_kapacita().
		}
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

        function get_nazev_for_nazev_web( $nazev_web){
                $dotaz ="select `ubytovani`.`nazev`
				from `ubytovani` 
                                where `ubytovani`.`nazev_web`='".$nazev_web."'
                                limit 1";
		//echo $dotaz;
           $ubyt=$this->database->query( $dotaz )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
           $zaznam = mysqli_fetch_array($ubyt);
           return $zaznam["nazev"];
        }
	/*metody pro pristup k parametrum*/
	function get_id_ubytovani() { return $this->radek["id_ubytovani"];}
	function get_nazev() { return $this->radek["nazev"];}
        function get_nazev_web() {return $this->radek["nazev_web"];            }
	function get_popisek() { return $this->radek["popisek"];}
	function get_popis() { return $this->radek["popis"];}

        function get_cena() { return $this->radek["castka"];}
        function get_nazev_ceny() { return $this->radek["kratky_nazev"];}
	function get_id_foto() { return $this->radek["id_foto"];}
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { return $this->radek["popisek_foto"];}
	function get_foto_url() { return $this->radek["foto_url"];}

        function get_foto(){
            return "<img class=\"fleft\" style=\"margin:5px;\" src=\"/".ADRESAR_NAHLED."/".$this->get_foto_url()."\"
				alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
				title=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
				width=\"175\" />";
        }
	function get_pocet_radku() { return $this->pocet_radku;}
}



?>
