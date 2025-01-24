<?php
/** 
* informace_lists.inc.php - tridy pro zobrazeni seznamu informací
*/


/*------------------- SEZNAM informaci -------------------  */

class Slevy_list extends Generic_list{
	//vstupni data
	protected $nazev;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
//------------------- KONSTRUKTOR  -----------------	
/**konstruktor tøídy*/
	function __construct($id_zamestnance, $nazev, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
	//kontrola vstupnich dat
		$this->nazev = $this->check($nazev);//odpovida poli nazev
		$this->zacatek = $this->check_int($zacatek); 
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu); 
				
		if( $this->legal() ){
			//ziskam celkovy pocet zajezdu ktere odpovidaji
			$data_pocet=$this->database->query($this->create_query("show",1))
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$zaznam_pocet = mysqli_fetch_array($data_pocet);
			$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show"))
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
			}						
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku=="show"){
			//definice jednotlivych casti dotazu
			if($this->nazev!=""){
				$where_nazev=" `nazev_slevy` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}	
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			$order=$this->order_by($this->order_by);
		
			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$select="select count(`slevy`.`id_slevy`) as pocet";
				$limit="";
			}else{
				$select="select *";
			}
		
			$dotaz= $select." 
					from `slevy`
					where ".$where_nazev." 1
					order by ".$order."
					 ".$limit."";
			//echo $dotaz;
			return $dotaz;
		}	
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`slevy`.`id_slevy`";
   			 break;
			case "id_down":
				 return "`slevy`.`id_slevy` desc";
   			 break;				 
			case "nazev_up":
				 return "`slevy`.`nazev_slevy`";
   			 break;
			case "nazev_down":
				 return "`slevy`.`nazev_slevy`  desc";
   			 break;				 	 		
			case "castka_up":
				 return "`slevy`.`castka`,`slevy`.`nazev_slevy`";
   			 break;	
			case "castka_down":
				 return "`slevy`.`castka` desc,`slevy`.`nazev_slevy`";
   			 break;				 				 		 
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`slevy`.`id_slevy`";
	}
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		$_GET["id_zajezd"]?($serial.="&amp;id_zajezd=".$_GET["id_zajezd"]." "):($serial=$serial);
		//tvroba input nazev
		$input_nazev="<input name=\"nazev_slevy\" type=\"text\" value=\"".$this->nazev."\" />";
		
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
	
		
		//vysledny formular		
		$vystup="
			<form method=\"post\" action=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>Název slevy: ".$input_nazev."</td>
					<td>".$submit."</td>
				</tr>
			</table>
			</form>
		";
		return $vystup;		
	}		

	/**zobrazi hlavicku k seznamu*/
	function show_list_header(){
		if( !$this->get_error_message()){
			//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		$_GET["id_zajezd"]?($serial.="&amp;id_zajezd=".$_GET["id_zajezd"]." "):($serial=$serial);
			$vystup="
				<table class=\"list\">
				    <colgroup><col width='4%'><col width='31%'><col width='31%'><col width='11%'><col width='6%'><col width='12%'></colgroup>
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>					
						<th>Text zobrazený na webu
						</th>
						<th>Popis slevy (zobrazen u zájezdu po pøejetí myší)
						<div class='sort'>
							<a class='sort-up' href=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>
						<th>Platnost
						</th>						
						<th>Èástka
						<div class='sort'>
							<a class='sort-up' href=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=castka_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=slevy_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=castka_down".$serial."\"></a>
							</div>
						</th>							
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}
	}	
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){            
		if( !$this->get_error_message()){
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			$vypis = $vypis."
							<td class=\"id\">".$this->radek["id_slevy"]."</td>
							<td class=\"nazev\">".$this->radek["zkraceny_nazev"]."</td>	
							<td class=\"nazev\">".$this->radek["nazev_slevy"]."</td>		
							<td class=\"nazev\">".$this->change_date_en_cz( $this->radek["platnost_od"])." - ".$this->change_date_en_cz( $this->radek["platnost_do"])."</td>									
							<td class=\"castka\">".$this->radek["castka"]." ".$this->radek["mena"]."</td>																		
							<td class=\"menu\">
                                <a href=\"".$adresa_modulu."?id_slevy=".$this->radek["id_slevy"]."&amp;typ=slevy&amp;pozadavek=edit\">edit</a>
                                | <a href=\"".$adresa_modulu."?id_slevy=".$this->radek["id_slevy"]."&amp;typ=slevy&amp;pozadavek=serialy_zajezdy\">seriály&zájezdy</a>
                                | <a class='anchor-delete' href=\"".$adresa_modulu."?id_slevy=".$this->radek["id_slevy"]."&amp;typ=slevy&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>";
						
			return $vypis;
		
		} else if($typ_zobrazeni=="tabulka_serial"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($adresa_serial = $core->get_adress_modul_from_typ("serial") ){	
				GLOBAL $serial;		
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
				//text pro typ informaci
				$vypis = $vypis."
							<td class=\"id\">".$this->radek["id_slevy"]."</td>
							<td class=\"nazev\">".$this->radek["zkraceny_nazev"]."</td>	
							<td class=\"nazev\">".$this->radek["nazev_slevy"]."</td>		
							<td class=\"nazev\">".$this->change_date_en_cz( $this->radek["platnost_od"])." - ".$this->change_date_en_cz( $this->radek["platnost_do"])."</td>									
							<td class=\"castka\">".$this->radek["castka"]." ".$this->radek["mena"]."</td>										
							<td class=\"menu\">
								  <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_slevy=".$this->radek["id_slevy"]."&amp;typ=slevy&amp;pozadavek=create\">pøidat k seriálu</a>
							</td>
						</tr>";
						
				return $vypis;
			}
		}else if($typ_zobrazeni=="tabulka_zajezd"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($adresa_serial = $core->get_adress_modul_from_typ("serial") ){	
				GLOBAL $serial;		
				GLOBAL $zajezd;					
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
				//text pro typ informaci
				$vypis = $vypis."
							<td class=\"id\">".$this->radek["id_slevy"]."</td>
							<td class=\"nazev\">".$this->radek["zkraceny_nazev"]."</td>	
							<td class=\"nazev\">".$this->radek["nazev_slevy"]."</td>		
							<td class=\"nazev\">".$this->change_date_en_cz( $this->radek["platnost_od"])." - ".$this->change_date_en_cz( $this->radek["platnost_do"])."</td>									
							<td class=\"castka\">".$this->radek["castka"]." ".$this->radek["mena"]."</td>										
							<td class=\"menu\">
								  <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_zajezd=".$zajezd->get_id_zajezd()."&amp;id_slevy=".$this->radek["id_slevy"]."&amp;typ=slevy_zajezd&amp;pozadavek=create\">pøidat k zájezdu</a>
							</td>
						</tr>";
						
				return $vypis;
			}
		}
		}//no error message
	} 

	
	/**zobrazi odkazy na dalsi stranky vypisu*/
	function show_strankovani(){
		if( $this->pocet_zajezdu != 0 and $this->pocet_zaznamu != 0){
			//prvni cislo stranky ktere zobrazime
			$act_str=$this->zacatek-(10*$this->pocet_zaznamu);
			if($act_str<0){
				$act_str=0;
			}
		
			//zjistim vsechny dalsi promenne, odstranim z nich ale str
			$promenne= ereg_replace("str=[0-9]*&?","",$_SERVER["QUERY_STRING"]);
		
			//odkaz na prvni stranku
			$vypis = "<div class=\"strankovani\"><a href=\"?str=0&amp;".$promenne."\" title=\"první stránka\">&lt;&lt;</a> &nbsp;"; 
		
			//odkaz na dalsi stranky z rozsahu
			while(($act_str < $this->pocet_zajezdu) and ($act_str <= $this->zacatek+(10*$this->pocet_zaznamu))){
				if($this->zacatek!=$act_str){
					$vypis = $vypis."<a href=\"?str=".$act_str."&amp;".$promenne."\" title=\"strana ".(1+($act_str/$this->pocet_zaznamu))."\">".(1+($act_str/$this->pocet_zaznamu))."</a> ";					
				}else{
					$vypis = $vypis.(1+($act_str/$this->pocet_zaznamu))." ";
				}
				$act_str=$act_str+$this->pocet_zaznamu;
			}	
		
			//odkaz na posledni stranku
			$posl_str=$this->pocet_zaznamu*floor(($this->pocet_zajezdu-1)/$this->pocet_zaznamu);
				$vypis = $vypis." &nbsp; <a href=\"?str=".$posl_str."&amp;".$promenne."\" title=\"poslední stránka\">&gt;&gt;</a></div>";	
		
			return $vypis;
		}
	}	
	
	
	/**zjistim, zda mam opravneni k pozadovane akci*/
	function legal(){
		$zamestnanec = User_zamestnanec::get_instance();
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
				
		return $zamestnanec->get_bool_prava($id_modul,"read");
	}

    public function getZacatek()
    {
        return $this->zacatek;
    }

    public function getPocetZajezdu()
    {
        return $this->pocet_zajezdu;
    }

    public function getPocetZaznamu()
    {
        return $this->pocet_zaznamu;
    }

}
?>
