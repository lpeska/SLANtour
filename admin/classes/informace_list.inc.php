<?php
/** 
* informace_lists.inc.php - tridy pro zobrazeni seznamu informací
*/


/*------------------- SEZNAM informaci -------------------  */

class Informace_list extends Generic_list{
	//vstupni data
	protected $typ;
	protected $zeme;
	protected $destinace;	
	protected $nazev;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
//------------------- KONSTRUKTOR  -----------------	
/**konstruktor tøídy*/
	function __construct($zeme,$destinace,$typ, $nazev, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
	//kontrola vstupnich dat
		$this->zeme = $this->check_int($zeme); //odpovida poli nazev_typ_web
		$this->destinace = $this->check_int($destinace); //odpovida poli nazev_typ_web	
		$this->typ = $this->check_int($typ); //odpovida poli nazev_typ_web
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
			if($this->typ!=0){
				$where_typ=" `informace`.`typ_informace` =".$this->typ." and";
			}else{
				$where_typ="";
			}
			if($this->zeme!=0){
				$where_zeme=" `informace`.`id_zeme` =".$this->zeme." and";
			}else{
				$where_zeme="";
			}	
			if($this->destinace!=0){
				$where_destinace=" `informace`.`id_destinace` =".$this->destinace." and";
			}else{
				$where_destinace="";
			}			
			if($this->nazev!=""){
				$where_nazev=" `informace`.`nazev` like '%".$this->nazev."%' and";
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
				$select="select count(`informace`.`id_informace`) as pocet";
				$limit="";
			}else{
				$select="select `informace`.`id_informace`,`informace`.`nazev`,`informace`.`typ_informace`
				 ,`zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`";
			}
		
			$dotaz= $select." 
					from `informace`
						join `zeme`	 on (`informace`.`id_zeme`=`zeme`.`id_zeme`)
						left join `destinace`	 on (`informace`.`id_destinace`=`destinace`.`id_destinace`)
					where ".$where_typ.$where_zeme.$where_destinace.$where_nazev." 1
					order by ".$order."
					 ".$limit."";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="zeme"){
			$dotaz="select `zeme`.`nazev_zeme`,`zeme`.`id_zeme`,
								`destinace`.`nazev_destinace`,`destinace`.`id_destinace`
							 from `zeme` left join 
							 		`destinace`  on (`zeme`.`id_zeme`=`destinace`.`id_zeme`) 
							where 1 
							order by `zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`";

			//echo $dotaz;
			return $dotaz;				
		}
	
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`informace`.`id_informace`";
   			 break;
			case "id_down":
				 return "`informace`.`id_informace` desc";
   			 break;				 
			case "nazev_up":
				 return "`informace`.`nazev`";
   			 break;
			case "nazev_down":
				 return "`informace`.`nazev`  desc";
   			 break;				 
			case "zeme_up":
				 return "`zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`,`informace`.`nazev`";
   			 break;
			case "zeme_down":
				 return "`zeme`.`nazev_zeme` desc,`destinace`.`nazev_destinace` desc,`informace`.`nazev` desc";
   			 break;				 		
			case "typ_up":
				 return "`informace`.`typ_informace`,`informace`.`nazev`";
   			 break;	
			case "typ_down":
				 return "`informace`.`typ_informace` desc,`informace`.`nazev` desc";
   			 break;				 				 		 
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`informace`.`id_informace`";
	}
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		
		//tvroba input nazev
		$input_nazev="<input name=\"nazev_informace\" type=\"text\" value=\"".$this->nazev."\" />";
		
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
		
		//tvorba select_zeme_destinace
		$zeme="<select name=\"zeme-destinace\"><option value=\"0:0\">--libovolná--</option>";
		//do promenne typy_serialu vytvorim instanci tridy seznam zemi
		$zeme_informace = new Zeme_list($this->id_zamestnance,"",$this->zeme,$this->destinace);
		//vypisu seznam zemi		
		$zeme = $zeme.$zeme_informace->show_list("select_zeme_destinace");	
		$zeme=$zeme."</select>\n";	
		$zeme=$zeme."</select>";			
		
		//vysledny formular		
		$vystup="
			<form method=\"post\" action=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=zeme-destinace-nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>Zemì/destinace: ".$zeme."</td>
					<td>Název informace: ".$input_nazev."</td>
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
			$vystup="
				<table class=\"list\">
					<tr>
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Typ
						<div class='sort'>
							<a class='sort-up' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=typ_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=typ_down".$serial."\"></a>
							</div>
						</th>						
						<th>Zemì
						<div class='sort'>
							<a class='sort-up' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zeme_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zeme_down".$serial."\"></a>
							</div>
						</th>
						<th>Název
						<div class='sort'>
							<a class='sort-up' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=informace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
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
							<td class=\"id\">".$this->radek["id_informace"]."</td>
							<td class=\"typ\">".Informace_library::get_typ_informace( ($this->radek["typ_informace"]-1) )."</td>
							<td class=\"zeme-destinace\">".$this->radek["nazev_zeme"]." (".$this->radek["nazev_destinace"].")</td>
							<td class=\"nazev\">".$this->radek["nazev"]."</td>											
							<td class=\"menu\">
								  <a href=\"".$adresa_modulu."?id_informace=".$this->radek["id_informace"]."&amp;typ=informace&amp;pozadavek=edit\">edit</a>";
			if($adresa_foto = $core->get_adress_modul_from_typ("fotografie") ){					  
				$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_informace=".$this->radek["id_informace"]."&amp;typ=foto\">foto</a>";
			}
                        $vypis = $vypis." | <a href=\"http://google.com/search?q=".$this->radek["nazev"]."\" target=\"_blank\" title=\"Vyhledat na Googlu\"><img src=\"https://www.google.cz/images/branding/product/ico/googleg_lodp.ico\" style=\"height:16px;margin-bottom:-2px;\"/></a>";
                        $vypis = $vypis." | <a href=\"http://search.seznam.cz/?q=".$this->radek["nazev"]."\" target=\"_blank\" title=\"Vyhledat na Seznamu\"><img src=\"http://search.seznam.cz/r/img/favicon.ico\" style=\"height:14px;margin-bottom:-2px;\"/></a>";
			$vypis = $vypis." | <a class='anchor-delete' href=\"".$adresa_modulu."?id_informace=".$this->radek["id_informace"]."&amp;typ=informace&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
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
							<td class=\"id\">".$this->radek["id_informace"]."</td>
							<td class=\"typ\">".Informace_library::get_typ_informace( ($this->radek["typ_informace"]-1) )."</td>
							<td class=\"zeme-destinace\">".$this->radek["nazev_zeme"]." (".$this->radek["nazev_destinace"].")</td>
							<td class=\"nazev\">".$this->radek["nazev"]."</td>											
							<td class=\"menu\">
								  <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_informace=".$this->radek["id_informace"]."&amp;typ=informace&amp;pozadavek=create\">pøidat k seriálu</a>
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

    public function getZacatek() {
        return $this->zacatek;
    }

    public function getPocetZajezdu() {
        return $this->pocet_zajezdu;
    }

    public function getPocetZaznamu() {
        return $this->pocet_zaznamu;
    }
		
}
?>
