<?php
/** 
* trida pro zobrazení seznamu zájezdů seriálu
*/
/*------------------- SEZNAM ZAJEZDU -------------------  */
/*rozsireni tridy Serial o seznam zajezdu*/
class Seznam_zajezdu extends Generic_list{
	protected $id_serialu;
	protected $nazev_typu;
	protected $nazev_zeme;
	protected $nazev_serial;
	
	protected $seznam_cen;
	protected $sloupce_cen;
	protected $pocet_cen;
	
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor třídy na základě id serialu*/
	function __construct($id_serialu,$nazev_typu,$nazev_zeme,$nazev_serial){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
		$this->id_serialu = $this->check($id_serialu);
		$this->nazev_typu = $this->check($nazev_typu);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->nazev_serial = $this->check($nazev_serial);
	
	//ziskani id vsech cen ktere nas zajimaji
		/*$this->seznam_cen = $this->database->query($this->check_ceny() )	
			or $this->chyba("Chyba při dotazu do databáze");
		$this->pocet_cen = mysqli_num_rows($this->seznam_cen);
		$i=0;
		$select_ceny="";
		$this->sloupce_cen=array();
		$join_ceny="";
		//ziskavani dotazu pro jednotlive ceny
		while($zaznam_cena = mysqli_fetch_array($this->seznam_cen)){
			$i++;
			$this->sloupce_cen[$i] = $zaznam_cena["nazev_ceny"];
			$select_ceny.="`cena_zajezd".$i."`.`id_cena` as `id_cena".$i."`,
								`cena_zajezd".$i."`.`castka` as `castka".$i."`,
								`cena_zajezd".$i."`.`mena` as `mena".$i."`,
								`cena_zajezd".$i."`.`vyprodano` as `vyprodano".$i."`,
                                                                `cena".$i."`.`typ_ceny` as `typ_ceny".$i."`,
								`cena".$i."`.`nazev_ceny` as `nazev_ceny".$i."`,
								`cena".$i."`.`kratky_nazev` as `kratky_nazev".$i."`,";
			$join_ceny.="left join (`cena` as `cena".$i."` join `cena_zajezd` as `cena_zajezd".$i."` on (`cena_zajezd".$i."`.`id_cena`=`cena".$i."`.`id_cena` and `cena_zajezd".$i."`.`nezobrazovat`!=1))
				on (`cena".$i."`.`id_serial` =`serial`.`id_serial` and `cena_zajezd".$i."`.`id_zajezd` =`zajezd`.`id_zajezd` and `cena".$i."`.`id_cena`=".$zaznam_cena["id_cena"].") ";
		}
		$dotaz = "select `serial`.`id_serial`,`zajezd`.`id_zajezd`,".$select_ceny."`zajezd`.`od`,`zajezd`.`nazev_zajezdu`,`zajezd`.`do` ,`zajezd`.`akcni_cena` ,`zajezd`.`cena_pred_akci` 
					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)"
					.$join_ceny."
					where `zajezd`.`do` >\"".date("Y-m-d")."\" and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`id_serial`= ".$this->id_serialu." order by `zajezd`.`od` ";
		*/	
                $dotaz = "select `zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`nazev_zajezdu`,`zajezd`.`do` ,`zajezd`.`akcni_cena` ,`zajezd`.`cena_pred_akci` 
					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
					where ((`serial`.`dlouhodobe_zajezdy` = 1 and `zajezd`.`do` >\"".date("Y-m-d")."\") or (`serial`.`dlouhodobe_zajezdy` = 0 and `zajezd`.`od` >\"".date("Y-m-d")."\")) and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`id_serial`= ".$this->id_serialu." order by `zajezd`.`od` ";

                
	//ziskani zajezdu z databaze	
                //echo $dotaz;
		$this->data=$this->database->query($dotaz)
		 	or $this->chyba("Chyba při dotazu do databáze");
		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho id serialu*/
	function check_ceny(){
		$dotaz = "select `serial`.`id_serial`,`cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`kratky_nazev`
					from `serial` join `cena` on (`cena`.`id_serial` =`serial`.`id_serial`) 
					where `serial`.`id_serial`= ".$this->id_serialu." order by `cena`.`poradi_ceny`,`cena`.`typ_ceny` ";
		//echo $dotaz;
		return $dotaz;
	}	
	
	/**vytvoreni dotazu ze zadaneho id serialu*/
	function create_query(){
		$dotaz = "select `serial`.`id_serial`, 
							`zajezd`.*,
							`cena_zajezd`.`castka`,`cena_zajezd`.`mena` 
					from `serial` join
					`zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`cena` on (`cena`.`id_serial` =`serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
					`cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 )
					where `zajezd`.`do` >\"".date("Y-m-d")."\" and `zajezd`.`nezobrazovat_zajezd`<>1  and `serial`.`id_serial`= ".$this->id_serialu." order by `zajezd`.`od` ";
		//echo $dotaz;
		return $dotaz;
	}	
        function get_max_sleva($id_zajezd){
                    $sql = "(select `nazev_slevy`, `castka`, `mena`, `sleva_staly_klient`
                            from `slevy` 
                                join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
                                join `zajezd` on (`slevy_serial`.`id_serial` = `zajezd`.`id_serial` and `zajezd`.`id_zajezd` = $id_zajezd)
                            where 
                                (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
                                and `sleva_staly_klient` = 0
                            )
                        union distinct
                        (select `nazev_slevy`, `castka`, `mena`, `sleva_staly_klient`
                            from `slevy` 
                                join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy`)
                            where `slevy_zajezd`.`id_zajezd` = ".$id_zajezd." 
                                and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
                                and `sleva_staly_klient` = 0    
			)
                        order by `castka` desc limit 1";		//echo $dotaz;
		$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row = mysqli_fetch_array($data)) {
                     $sleva =  $row["castka"] . $row["mena"];
                     return $sleva;
                }
                return "";
	}
	/**zobrazeni hlavicky seznamu zajezdu*/
	function show_list_header($typ=""){
		if($typ=="dalsi_terminy"){
			$vystup="<table class=\"zajezdy\">
							<tr>
								<th>Všechny termíny zájezdu</th>
								".$this->sloupce_cen."	
                                                                <th></th>    
							</tr>";		
		}else	if($typ=="vstupenky"){
			$vystup="<table class=\"zajezdy\">
							<tr>
								<th>Datum</th>
								".$this->sloupce_cen."
                                                                <th></th>     
							</tr>";		
		}else	if($typ=="vstupenky_dalsi_termin"){
			$vystup="<table class=\"zajezdy\">
							<tr>
								<th>Další vstupenky</th>
								".$this->sloupce_cen."	
                                                                 <th></th>    
							</tr>";		
		}else{
			$vystup="<table class=\"zajezdy\">
							<tr>
								<th>Termín zájezdu</th>
								".$this->sloupce_cen."	
                                                                 <th></th>    
							</tr>";
			
		}
		return $vystup;
	}		
	/**zobrazeni prvku  seznamu zajezdu na zaklade typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
		$core = Core::get_instance();
		$adresa_zobrazit = "zobrazit";
                $first_cena = false;
		if( $adresa_zobrazit !== false ){//pokud existuje modul pro zpracovani		
		
			if($typ_zobrazeni=="tabulka"){
                                
                                if ($this->radek["cena_pred_akci"] and $this->radek["akcni_cena"]) {
                                    $sleva = " <b style=\"color:green;\">SLEVA " . round((1 - $this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) * 100) . "%</b>";
                                } else {
                                    $sleva = $this->get_max_sleva($this->get_id_zajezd());
                                }
                            
				if( $this->get_termin_od() == $this->get_termin_do() ){
					$termin = $this->change_date_en_cz( $this->get_termin_od() );
				}else{
					$termin = $this->change_date_en_cz_short( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() );
				}
                                
				if( $this->get_nazev_zajezdu()!="" ){
					if( strpos( $this->get_nazev_zajezdu(), $termin)!== FALSE ){
						$termin = $this->get_nazev_zajezdu();
					}else{
						$termin = $this->get_nazev_zajezdu().", ".$termin;
					}
				}
				$termin.=$sleva;
				$vystup="<tr>
                                            <td><a href=\"".$this->get_adress(array($adresa_zobrazit,$this->nazev_serial,$this->get_id_zajezd()))."\" title=\"Detaily a informace o termínu, objednávka\">".$termin."</a></td>";
				
				//jednotlive ceny
				$i=0;
				while($i < $this->pocet_cen){
					$i++;
					if($this->radek["vyprodano".$i]){
						$vystup.="<td><strong><span class=\"red\">Vyprodáno!</span></strong></td>";
					}else{									
						$vystup.="<td>".$this->radek["castka".$i]." ".$this->radek["mena".$i]."</td>";
					}
				}								
				$vystup.="
						
                                   <td style=\"width:68px;\"><a href=\"".$this->get_adress(array($adresa_zobrazit,$this->nazev_serial,$this->get_id_zajezd()))."\" title=\"Detaily a informace o termínu, objednávka\">Podrobnosti</a></td>             
                                </tr>
                                ";
                                
				return $vystup;
			}else if($typ_zobrazeni=="list"){
                                if( $this->get_termin_od() == $this->get_termin_do() ){
					$termin = $this->change_date_en_cz( $this->get_termin_od() );
				}else{
					$termin = $this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() );
				}
				if( $this->get_nazev_zajezdu()!="" ){
					$termin = "<span title=\"".$termin."\">".$this->get_nazev_zajezdu()."</span>";					
				}
                                if($this->get_id_zajezd()==$_GET["id_zajezd"]){
                                   $vystup = "<li><b><i>".$termin."</i></b></li>";                                 
                                }else{
                                    $vystup = "<li> <a class=\"list_terminy\" href=\"".$this->get_adress(array($adresa_zobrazit,$this->nazev_serial,$this->get_id_zajezd()))."\" >".$termin."</a></li>";                                
                                }
                                return $vystup;
                                
                        }else if($typ_zobrazeni=="array"){
                            if ($this->radek["cena_pred_akci"] and $this->radek["akcni_cena"]) {
                                $sleva =  round((1 - $this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) * 100)." %";
                            } else {
                                $sleva = $this->get_max_sleva($this->get_id_zajezd());
                            }
                            
                            if( $this->get_termin_od() == $this->get_termin_do() ){
					$termin = $this->change_date_en_cz( $this->get_termin_od() );
                            }else{
					$termin = $this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() );
                            }
                            
                            
                            if( $this->get_nazev_zajezdu()!="" ){
				$termin = $this->get_nazev_zajezdu() ."(".$termin.")";					
                            }
                            
                            /*$priceMap = array();
                            $i=0;
                            while($i < $this->pocet_cen){
                                $i++;
                                if($this->radek["vyprodano".$i]){
                                    $priceMap[] = array($this->radek["nazev_ceny".$i],"Vyprodáno!",$this->radek["castka".$i]." ".$this->radek["mena".$i], $this->radek["typ_ceny".$i]);
                                }else {									
                                    $priceMap[] = array($this->radek["nazev_ceny".$i],"",$this->radek["castka".$i]." ".$this->radek["mena".$i], $this->radek["typ_ceny".$i]);
                                    if(!$first_cena){
                                        $first_cena = true;
                                        $price = $this->radek["castka".$i];
                                    }
                                }
                            }
                            //print_r($priceMap) ;*/
                            return array(
                                $this->get_id_zajezd(),
                                $termin,
                                //$price,                                
                                $sleva,
                                $this->get_poznamky_zajezd(),
                                //$priceMap 
                            );
                            
                        }
		}
	}	
	/*metody pro pristup k parametrum*/
        function get_pocet_cen() { return $this->pocet_cen;}
	function get_id_zajezd() { return $this->radek["id_zajezd"];}
	function get_termin_od() { return $this->radek["od"];}
	function get_termin_do() { return $this->radek["do"];}
	function get_nazev_zajezdu() { return $this->radek["nazev_zajezdu"];}
	function get_castka() { return $this->radek["castka"];}
	function get_mena() { return $this->radek["mena"];}
	function get_poznamky_zajezd() { return "".$this->radek["poznamky_zajezd"];}
}





?>
