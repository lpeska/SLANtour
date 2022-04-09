<?php

/*--------------------- PODROBNE VYHLEDAVANI -----------------------------*/
/** zobrazeni formulare pro podrobne i zakladni vyhledavani
*	- vola se v Serial_list_podrobne, ale muze byt pouzita i samostatne
*/
class Vyhledavani extends Generic_serial_class{
	//vstupni data	
	protected $typ;
	protected $podtyp;

	protected $nazev_zeme;
	protected $order_by;
	protected $cena;
	protected $destinace;
	protected $od;
	protected $do;
	protected $doprava;
	protected $ubytovani;
	protected $stravovani;
	protected $keywords;
	public $database; //trida pro odesilani dotazu	
	
	/**
	*	konstruktor tøídy 
	* @param $typ, $podtyp, $nazev_zeme, $destinace = hodnota sloupcù v prislusne tabulce
	* @param $od, $do = minimalni a maximalni hodnoty sloupce od z tabulky zajezd
	* @param $doprava, $ubytovani, $stravovani = ciselne hodnoty odpovidajici sloupcum tabulky serial
	*/
	function __construct($typ="", $podtyp="", $nazev_zeme="", $order_by="", $cena="", $destinace="", $od="", $do=""
								, $doprava="", $ubytovani="", $stravovani="", $keywords=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
											
	//kontrola + ulozeni vstupnich dat
		$this->typ = $this->check($typ);
		$this->podtyp = $this->check($podtyp);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->zacatek = $this->check_int($zacatek);
		$this->order_by = $this->check($order_by);
		$this->cena = $this->check_int($cena);	
		$this->destinace = $this->check_int($destinace);
		$this->od = $this->check($od);
		$this->do = $this->check($do);
		$this->doprava = $this->check_int($doprava);
		$this->ubytovani = $this->check_int($ubytovani);
		$this->stravovani = $this->check_int($stravovani);	
		$this->keywords = $this->check($keywords);
	}
	/**zobrazeni jednoducheho vyhledavani*/
	function show_vyhledavani(){
	//tvorba jednotlivých položek formuláøe
		$cena = $this->create_select_cena(1);
		$zeme = $this->create_select_zeme();		
		
	//tvorba select od
		//ziskam minule hodnoty pro den, mesic a rok
		$array_od=explode("-",$this->od);
				$last_od_den=$array_od[2];
				$last_od_mesic=$array_od[1];
				$last_od_rok=$array_od[0];		
		
		//select pro dny		
		$od_den="<select name=\"od_den\"><option value=\"\">---</option>";
		$i=1;
		while($i<=31){			
				$od_den=$od_den."<option value=\"".$i."\" ";
					if($i==$last_od_den){$od_den=$od_den." selected=\"selected\"";}
				$od_den=$od_den.">".$i."</option>"; 
				$i++;	
			}
		$od_den=$od_den."</select>";	
		
		//select pro mesice	
		$od_mesic="<select name=\"od_mesic\"><option value=\"\">---</option>";
		$i=1;
		while($i<=12){		 
				$od_mesic=$od_mesic."<option value=\"".$i."\" ";
					if($i==$last_od_mesic){$od_mesic=$od_mesic." selected=\"selected\"";}
				$od_mesic=$od_mesic.">".$i."</option>";
				$i++;	
			}
		$od_mesic=$od_mesic."</select>";	

		//select pro roky	
		$od_rok="<select name=\"od_rok\"><option value=\"\">---</option>";
		$i=0;
		$cur_rok=date("Y");
		while($i<=MAX_YEAR){			
				$od_rok=$od_rok."<option value=\"".($cur_rok+$i)."\" ";
					if(($cur_rok+$i)==$last_od_rok){$od_rok=$od_rok." selected=\"selected\"";}
				$od_rok=$od_rok.">".($cur_rok+$i)."</option>";
				$i++;			
			}
		$od_rok=$od_rok."</select>";	
						
		$od ="<div class=\"odjezd\"><div class=\"label\">Odjezd od: </div>
				<div class=\"hodnota\">".$od_den.$od_mesic.$od_rok."</div></div>" ;

    //tvorba input keywords
        $keyw = "<div class=\"label\">Název zájezdu / ubytování:</div>
				<div class=\"hodnota\">
                    <input type=\"text\" name=\"keywords\" value=\"".$this->keywords."\" style=\"width:140px;\"/>
                </div>";
	//tvorba výstupu				
		$core = Core::get_instance();
		$adresa_vyhledavani = $core->get_adress_modul_from_typ("vyhledavani");
		if( $adresa_vyhledavani !== false ){									
			$vystup="<form action=\"".$this->get_adress(array($adresa_vyhledavani))."\" method=\"post\">
						<ul>
							<li class=\"main\">VYHLEDÁVÁNÍ</li>						
							<li>					
								<input type=\"hidden\" name=\"vyhledavani\" value=\"1\" />
								".$keyw.$cena.$zeme.$od."
							</li>
							<li>	
								<input type=\"submit\" value=\"Vyhledat zájezdy\" />						
							</li>
						</ul>
					</form>
					";
		}
		return $vystup;

	}
	
	/**zobrazeni podrobneho vyhledavani*/
	function show_podrobne_vyhledavani(){
					
	//tvorba jednotlivých položek formuláøe
		$ord_by = $this->create_select_order_by();	
		$cena = $this->create_select_cena();	
		$strava = $this->create_select_stravovani();	
		$doprava = $this->create_select_doprava();	
		$ubytovani = $this->create_select_ubytovani();	
		$zeme = $this->create_select_zeme();
		$typ = $this->create_select_typ();



	//tvorba select od, do
		//ziskam minule hodnoty pro den, mesic a rok
		$array_od=explode("-",$this->od);
				$last_od_den=$array_od[2];
				$last_od_mesic=$array_od[1];
				$last_od_rok=$array_od[0];
		$array_do=explode("-",$this->do);
				$last_do_den=$array_do[2];
				$last_do_mesic=$array_do[1];
				$last_do_rok=$array_do[0];
				
		//select pro dny		
		$od_den="<select name=\"od_den\"><option value=\"\">---</option>";
		$do_den="<select name=\"do_den\"><option value=\"\">---</option>";
		$i=1;
		while($i<=31){
				
				$od_den=$od_den."<option value=\"".$i."\" ";
					if($i==$last_od_den){$od_den=$od_den." selected=\"selected\"";}
				$od_den=$od_den.">".$i."</option>"; 
				
				$do_den=$do_den."<option value=\"".$i."\" ";
					if($i==$last_do_den){$do_den=$do_den." selected=\"selected\"";}
				$do_den=$do_den.">".$i."</option>";			
				$i++;	
			}
		$od_den=$od_den."</select>";	
		$do_den=$do_den."</select>";	
		
		//select pro mesice	
		$od_mesic="<select name=\"od_mesic\"><option value=\"\">---</option>";
		$do_mesic="<select name=\"do_mesic\"><option value=\"\">---</option>";
		$i=1;
		while($i<=12){
				 
				$od_mesic=$od_mesic."<option value=\"".$i."\" ";
					if($i==$last_od_mesic){$od_mesic=$od_mesic." selected=\"selected\"";}
				$od_mesic=$od_mesic.">".$i."</option>";
				
				$do_mesic=$do_mesic."<option value=\"".$i."\" ";
					if($i==$last_do_mesic){$do_mesic=$do_mesic." selected=\"selected\"";}
				$do_mesic=$do_mesic.">".$i."</option>";		
				$i++;		
			}
		$od_mesic=$od_mesic."</select>";	
		$do_mesic=$do_mesic."</select>";				

		//select pro roky	
		$od_rok="<select name=\"od_rok\"><option value=\"\">---</option>";
		$do_rok="<select name=\"do_rok\"><option value=\"\">---</option>";
		$i=0;
		$cur_rok=date("Y");
		while($i<=MAX_YEAR){
				
				$od_rok=$od_rok."<option value=\"".($cur_rok+$i)."\" ";
					if(($cur_rok+$i)==$last_od_rok){$od_rok=$od_rok." selected=\"selected\"";}
				$od_rok=$od_rok.">".($cur_rok+$i)."</option>";
				
				$do_rok=$do_rok."<option value=\"".($cur_rok+$i)."\" ";
					if(($cur_rok+$i)==$last_do_rok){$do_rok=$do_rok." selected=\"selected\"";}
				$do_rok=$do_rok.">".($cur_rok+$i)."</option>";	
				$i++;			
			}
		$od_rok=$od_rok."</select>";	
		$do_rok=$do_rok."</select>";			
						
		$od ="<div class=\"odjezd\"><div class=\"label\">Odjezd od: </div>
				<div class=\"hodnota\">".$od_den.$od_mesic.$od_rok."</div></div>" ;
				
		$do ="<div class=\"odjezd\"><div class=\"label\">Odjezd do: </div>
				<div class=\"hodnota\">".$do_den.$do_mesic.$do_rok."</div></div>" ;

	//--------tvorba vystupu--------------------------			
		$core = Core::get_instance();
		$adresa_vyhledavani = $core->get_adress_modul_from_typ("vyhledavani");
		if( $adresa_vyhledavani !== false ){								
			$vystup="
					
					<div class=\"main\">PODROBNÉ VYHLEDÁVÁNÍ</div>	
						
						<form action=\"".$this->get_adress(array("vyhledavani"))."\" method=\"post\">
							<table>										
								<tr><td><input type=\"hidden\" name=\"vyhledavani\" value=\"1\" />
										".$strava."</td><td>".$doprava."</td></tr>
								<tr><td>".$cena."</td><td>".$ubytovani."</td></tr>
								<tr><td>".$zeme."</td><td>".$typ."</td></tr>
								<tr><td>".$od."</td><td>".$do."</td></tr>
								<tr><td>".$ord_by."</td><td><input type=\"submit\" value=\"Vyhledat zájezdy\" /></td></tr>
						</table></form>
					
					";
		}
		return $vystup;
		
/*		
if( $adresa_vyhledavani !== false ){								
			$vystup="
					
					<div class=\"main\">PODROBNÉ VYHLEDÁVÁNÍ</div>	
						
						<form action=\"".$this->get_adress(array("vyhledavani"))."\" method=\"post\">
							<table><input type=\"hidden\" name=\"vyhledavani\" value=\"1\" />
							<div class=\"contend\">
								
								<div class=\"radek\">".$strava.$doprava."</div>
								<div class=\"radek\">".$cena.$ubytovani."</div>
								<div class=\"radek\">".$zeme.$typ."</div>
								<div class=\"radek\">".$od.$do."</div>
								<div class=\"radek\">".$ord_by."</div>
							</div>
							<input type=\"submit\" value=\"Vyhledat zájezdy\" />
						</table></form>
					
					";
		}
		return $vystup;
		
		*/
				
	}
	//----------dotazy do databaze pro zeme a typy zajezdu
	function create_query($typ){
		if($typ=="zeme"){//mel by vyhodit pouze ty zeme a destinace, kde existuje spojeni s nejakym serialem
			//
			//
			$dotaz="select distinct `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,`zeme`.`id_zeme`,
								`destinace`.`nazev_destinace`,`destinace`.`id_destinace`
							 from `zeme` 
							 	join `zeme_serial` on (`zeme`.`id_zeme`=`zeme_serial`.`id_zeme`) 
							 left join 
							 	(`destinace` 
								 join `destinace_serial` on (`destinace_serial`.`id_destinace`=`destinace`.`id_destinace`) ) 
								on (`zeme`.`id_zeme`=`destinace`.`id_zeme`) 														 
							 where 1 order by `zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`";
			//echo $dotaz;
			return $dotaz;
		}
		if($typ=="typ"){
			$dotaz="select `typ`.`nazev_typ`,`typ`.`nazev_typ_web`,
								`podtyp`.`nazev_typ` as `nazev_podtyp`,`podtyp`.`nazev_typ_web` as `nazev_podtyp_web`
							 from `typ_serial` as `typ` 
							 left join `typ_serial` as `podtyp` on (`typ`.`id_typ`=`podtyp`.`id_nadtyp`) where `typ`.`id_nadtyp`=0";
			return $dotaz;
		}
	}
	
	function create_select_order_by(){
		$i=0;
			$ord_by="<div class=\"label\">Setøídit podle:</div>
							<div class=\"hodnota\">
							<select name=\"order_by\">
							<option value=\"\">----</option>";						
			while(self::$array_order_by[$i]!=""){
				if($this->order_by==self::$array_order_by[$i]){
					$ord_by=$ord_by."<option value=\"".self::$array_order_by[$i]."\" selected=\"selected\">".self::$array_order_by[$i]."</option>";
				}else{
					$ord_by=$ord_by."<option value=\"".self::$array_order_by[$i]."\">".self::$array_order_by[$i]."</option>";
				}
				$i++;
			}
			$ord_by=$ord_by."</select></div>";
			
			return $ord_by;	
	}	
	function create_select_stravovani(){
		$i=0;
			$strava="<div class=\"label\">Stravování:</div>
							<div class=\"hodnota\">
							<select name=\"strava\">
							<option value=\"\">----</option>";						
			while(Serial_library::get_typ_stravy($i)!=""){
				if($this->strava==($i+1)){
					$strava=$strava."<option value=\"".($i+1)."\" selected=\"selected\">".Serial_library::get_typ_stravy($i)."</option>\n";
				}else{
					$strava=$strava."<option value=\"".($i+1)."\">".Serial_library::get_typ_stravy($i)."</option>\n";
				}
				$i++;
			}
			$strava=$strava."</select></div>";	
			
			return $strava;	
	}	
	function create_select_doprava(){
		$i=0;
			$doprava="<div class=\"label\">Doprava:</div>
							<div class=\"hodnota\">
							<select name=\"doprava\">
							<option value=\"\">----</option>";						
			while(Serial_library::get_typ_dopravy($i)!=""){
				if($this->doprava==($i+1)){
					$doprava=$doprava."<option value=\"".($i+1)."\" selected=\"selected\">".Serial_library::get_typ_dopravy($i)."</option>\n";
				}else{
					$doprava=$doprava."<option value=\"".($i+1)."\">".Serial_library::get_typ_dopravy($i)."</option>\n";
				}
				$i++;
			}
			$doprava=$doprava."</select></div>";	
			
			return $doprava;		
	}	
	
	
	function create_select_cena($delsi=0){
		$i=0;
            if($delsi){
                $style="width:122px;";
            }else{
                $style="width:100px;";
            }
			$cena="<div class=\"label\">Cena do:</div>
							<div class=\"hodnota\">
							<input type=\"text\" name=\"cena\" value=\"".(($this->cena!=0)?($this->cena):(""))."\" style=\"".$style."\"/>";
							//<option value=\"\">----</option>";						
			//while(self::$array_cena[$i]!=""){
				//if($this->cena==self::$array_cena[$i]){
				//	$cena=$cena."<option value=\"".self::$array_cena[$i]."\" selected=\"selected\">".self::$array_cena[$i]." Kè</option>";
				//}else{
				//	$cena=$cena."<option value=\"".self::$array_cena[$i]."\">".self::$array_cena[$i]." Kè</option>";
				//}
				//$i++;
			//}
			$cena=$cena." Kè </div>";	
			
			return $cena;	
	}
	

	function create_select_ubytovani(){
		$i=0;
			$ubytovani="<div class=\"label\">Ubytování:</div>
							<div class=\"hodnota\">
							<select name=\"ubytovani\">
							<option value=\"\">----</option>";		
							
			while(Serial_library::get_typ_ubytovani($i)!=""){
				if($this->ubytovani==($i+1)){
					$ubytovani=$ubytovani."<option value=\"".($i+1)."\" selected=\"selected\">".Serial_library::get_typ_ubytovani($i)."</option>\n";
				}else{
					$ubytovani=$ubytovani."<option value=\"".($i+1)."\">".Serial_library::get_typ_ubytovani($i)."</option>\n";
				}
				$i++;
			}
			$ubytovani=$ubytovani."</select></div>";	
			
			return $ubytovani;			
	}
	
	
	function create_select_zeme(){
		$data_zeme=$this->database->query($this->create_query("zeme"))
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			$zeme="<div class=\"label\">Zemì / destinace:</div>
							<div class=\"hodnota\">
							<select name=\"zeme-destinace\">
							<option value=\"\">----</option>";		
		$last_zeme="";
		while($zaznam_zeme = mysqli_fetch_array($data_zeme)){
			//kazdou zemi zobrazime i bez destinace
			if($zaznam_zeme["nazev_zeme_web"]!=$last_zeme){
				$last_zeme=$zaznam_zeme["nazev_zeme_web"];
				$zeme=$zeme."<option value=\"".$zaznam_zeme["nazev_zeme_web"].":\"";			
				//kontrola zda nebyla minule vybrana tato polozka
					if($this->nazev_zeme==$zaznam_zeme["nazev_zeme_web"] and $this->destinace==0){
						$zeme=$zeme." selected=\"selected\"";
					}			
				$zeme=$zeme." >".$zaznam_zeme["nazev_zeme"]."</option>";
			}
			//pokud mame destinaci, vypiseme zemi i s ni
			if($zaznam_zeme["nazev_destinace"]!=""){
				$zeme=$zeme."<option value=\"".$zaznam_zeme["nazev_zeme_web"].":".$zaznam_zeme["id_destinace"]."\"";	
				//kontrola zda nebyla minule vybrana tato polozka
					if($this->nazev_zeme==$zaznam_zeme["nazev_zeme_web"] and $this->destinace==$zaznam_zeme["id_destinace"]){
						$zeme=$zeme." selected=\"selected\"";
					}							
				$zeme=$zeme." > &nbsp; &nbsp; - ".$zaznam_zeme["nazev_destinace"]."</option>";
			}
		}//end while
		$zeme=$zeme."</select></div>";
		
		return $zeme;		
	}
	
	
//-------------------------------------------------------------------
//----------- tvorba selectu pro polozky typ a podtyp
	function create_select_typ(){
		$data_typ=$this->database->query($this->create_query("typ"))
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			$typ="<div class=\"label\">Typ zájezdu:</div>
							<div class=\"hodnota\">
							<select name=\"typ-podtyp\">
							<option value=\"\" >----</option>";		
		$last_typ="";
		while($zaznam_typ = mysqli_fetch_array($data_typ)){
			//kazdy typ zobrazime i bez podtypu
			if($zaznam_typ["nazev_typ_web"]!=$last_typ){
				$last_typ=$zaznam_typ["nazev_typ_web"];
				$typ=$typ."<option value=\"".$zaznam_typ["nazev_typ_web"].":\"";			
				//kontrola zda nebyla minule vybrana tato polozka
					if($this->typ==$zaznam_typ["nazev_typ_web"] and $this->podtyp==""){
						$typ=$typ." selected=\"selected\"";
					}			
				$typ=$typ." >".$zaznam_typ["nazev_typ"]."</option>";
			}
			//pokud mame podtyp, vypiseme typ i s nim
			if($zaznam_typ["nazev_podtyp_web"]!=""){
				$typ=$typ."<option value=\"".$zaznam_typ["nazev_typ_web"].":".$zaznam_typ["nazev_podtyp_web"]."\"";	
				//kontrola zda nebyla minule vybrana tato polozka
					if($this->typ==$zaznam_typ["nazev_typ_web"] and $this->podtyp==$zaznam_typ["nazev_podtyp_web"]){
						$typ=$typ." selected=\"selected\"";
					}							
				$typ=$typ." > &nbsp; &nbsp; - ".$zaznam_typ["nazev_podtyp"]."</option>";
			}
		}//end while
		$typ=$typ."</select></div>";	
		
		return $typ;
	}	
	
}
?>
