<?php
/** 
* faktury_lists.inc.php - tridy pro zobrazeni seznamu informací
*/


/*------------------- SEZNAM informaci -------------------  */

class Faktury_list extends Generic_list{
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
/**konstruktor tøídy
 *$faktury_list = new Faktury_list($_SESSION["cislo_faktury"],$_SESSION["faktura_prijemce"],$_SESSION["serial_nazev"],$_SESSION["faktura_klient"],$_SESSION["datum_splatnost_do"],$_GET["str"],$_SESSION["faktury_order_by"]);

 */
	function __construct($cislo_faktury,$faktura_prijemce,$serial_nazev, $faktura_klient,  $datum_vystaveni,$id_objednavka, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
	//kontrola vstupnich dat
		$this->cislo_faktury = $this->check($cislo_faktury); //odpovida poli nazev_typ_web
		$this->faktura_prijemce = $this->check($faktura_prijemce); //odpovida poli nazev_typ_web	
		$this->serial_nazev = $this->check($serial_nazev); //odpovida poli nazev_typ_web
		$this->faktura_klient = $this->check($faktura_klient);//odpovida poli nazev
                $this->datum_vystaveni = $this->change_date_cz_en($this->check($datum_vystaveni));//odpovida poli nazev
                
                
		$this->id_objednavka = $this->check_int($id_objednavka);//odpovida poli nazev
                if($this->id_objednavka > 0 ){
                    $this->uhrazeno = -1; //odpovida poli nazev_typ_web
                }else{
                    $this->uhrazeno = $this->check_int($_SESSION["faktura_uhrazeno"]); //odpovida poli nazev_typ_web
                }
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
			if($this->cislo_faktury!=""){
				$where_cislo_faktury=" `faktury`.`cislo_faktury` like \"%".$this->cislo_faktury."%\" and";
			}else{
				$where_cislo_faktury="";
			}
			if($this->faktura_prijemce!=""){
				$where_faktura_prijemce=" `faktury`.`prijemce_text` like \"%".$this->faktura_prijemce."%\" and";
			}else{
				$where_faktura_prijemce="";
			}
                        if($this->serial_nazev!=""){
				$where_serial_nazev=" (`serial`.`nazev` like \"%".$this->serial_nazev."%\" or `objekt`.`nazev_objektu` like \"%".$this->serial_nazev."%\") and";
			}else{
				$where_serial_nazev="";
			}
                        if($this->faktura_klient!=""){
				$where_faktura_klient=" (`user_klient`.`prijmeni` like \"%".$this->faktura_klient."%\" or `organizace`.`nazev`  like \"%".$this->faktura_klient."%\") and";
			}else{
				$where_faktura_klient="";
			}
                        if($this->datum_vystaveni!=""){
				$where_datum_vystaveni=" `faktury`.`datum_vystaveni` = \"".$this->datum_vystaveni."\" and";
			}else{
				$where_datum_vystaveni="";
			}
                        if($this->uhrazeno!=-1){
                            if($this->uhrazeno<=10){
                                $where_uhrazeno=" `faktury`.`zaplaceno` = ".$this->uhrazeno." and";
                            }else if($this->uhrazeno==11){
                                $where_uhrazeno=" `faktury`.`zaplaceno` <= 1 and";
                            }else if($this->uhrazeno==12){
                                $where_uhrazeno=" `faktury`.`zaplaceno` >= 2 and";
                            }
				
			}else{
				$where_uhrazeno="";
			}
                        if($this->id_objednavka>0){
				$where_objednavka=" `faktury`.`id_objednavka` = ".$this->id_objednavka." and";
			}else{
				$where_objednavka="";
			}
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			
		
			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$select="select count(`faktury`.`id_faktury`) as pocet";
				$limit="";
                                $order_by="";
			}else{
				$select="select `faktury`.*,`objednavka`.`id_objednavka`, `serial`.`id_serial`, `serial`.`id_sablony_zobrazeni`, `serial`.`nazev` as `nazev_serialu`, `zajezd`.`nazev_zajezdu` , `zajezd`.`od` , `zajezd`.`do` , 
                                         `organizace`.`id_organizace`,`organizace`.`ico`,`organizace`.`nazev` as `nazev_organizace`,
                                         `user_klient`.`id_klient`,`user_klient`.`jmeno` as `klient_jmeno`,`user_klient`.`prijmeni` as `klient_prijmeni`,
                                         `user_zamestnanec`.*, `objekt`.`nazev_objektu` as `nazev_ubytovani` ";
                                
                                $order_by="order by ".$this->order_by($this->order_by);
			}
		/*TODO: zkontrolovat dotaz (neunikátní názvy polí u organizace, seriálu a lidí) */
			$dotaz= $select." 
					from `faktury`						
                                                join `objednavka`	 on (`faktury`.`id_objednavka`=`objednavka`.`id_objednavka`)
                                                join `serial`	 on (`objednavka`.`id_serial`=`serial`.`id_serial`)
                                                join `zajezd`	 on (`objednavka`.`id_zajezd`=`zajezd`.`id_zajezd`)
                                                
                                                left join (`objekt_serial` 
                                                        join `objekt` on (`objekt`.`id_objektu`= `objekt_serial`.`id_objektu` and `typ_objektu` = 1)
                                                    )on  (`serial`.`id_serial`= `objekt_serial`.`id_serial`)
                                                left join `user_zamestnanec`	 on (`faktury`.`id_vystavil`=`user_zamestnanec`.`id_user`)
						left join `user_klient`	 on (`faktury`.`id_objednavatele_klient`=`user_klient`.`id_klient`)
                                                left join `organizace`	 on (`faktury`.`id_objednavatele_organizace`=`organizace`.`id_organizace`)
					where ".$where_cislo_faktury.$where_faktura_prijemce.$where_serial_nazev.$where_faktura_klient.$where_datum_vystaveni.$where_uhrazeno.$where_objednavka." 1
                                        group by   `faktury`.`id_faktury` 
					".$order_by."
					 ".$limit."";
			//echo $dotaz;
			return $dotaz;
		}
	
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`faktury`.`cislo_faktury`";
   			 break;
			case "id_down":
				 return "`faktury`.`cislo_faktury` desc";
   			 break;				 
			case "prijemce_up":
				 return "`faktury`.`prijemce_text`";
   			 break;
			case "prijemce_down":
				 return "`faktury`.`prijemce_text`  desc";
   			 break;	
                        case "klient_up":
				 return "`klient_prijmeni`,`klient_jmeno`";
   			 break;
			case "klient_down":
				 return "`klient_prijmeni` desc,`klient_jmeno` desc";
   			 break;	
			case "vystaveno_up":
				 return "`faktury`.`datum_vystaveni`";
   			 break;
			case "vystaveno_down":
				 return "`faktury`.`datum_vystaveni` desc";
   			 break;	
                        case "zaplaceno_up":
				 return "`faktury`.`zaplaceno`";
   			 break;
			case "zaplaceno_down":
				 return "`faktury`.`zaplaceno` desc";
   			 break;	
                        case "splatnost_up":
				 return "`faktury`.`datum_splatnosti`";
   			 break;
			case "splatnost_down":
				 return "`faktury`.`datum_splatnosti` desc";
   			 break;
			case "castka_up":
				 return "`faktury`.`celkova_castka`";
   			 break;	
			case "castka_down":
				 return "`faktury`.`celkova_castka` desc";
   			 break;	
                         case "zajezd_up":
				 return "`serial`.`nazev`, `zajezd`.`od`";
   			 break;
			case "zajezd_down":
				 return "`serial`.`nazev`  desc, `zajezd`.`od` desc";
   			 break;
                         case "objednavka_up":
				 return "`objednavka`.`id_objednavka`";
   			 break;
			case "objednavka_down":
				 return "`objednavka`.`id_objednavka`  desc";
   			 break;                     
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`faktury`.`cislo_faktury` desc";
	}
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		
		//tvroba input nazev
		$input_cislo="<input name=\"cislo_faktury\" type=\"text\" value=\"".$this->cislo_faktury."\" />";
                
                $select_zaplaceno="<select name=\"faktura_uhrazeno\" >
                        <option value=\"-1\">---</option>
                        <option value=\"11\" ".($this->uhrazeno==11? "selected=\"selected\"":"").">Neuhrazené a èásteènì uhrazené</option>
                        <option value=\"12\" ".($this->uhrazeno==12? "selected=\"selected\"":"").">Uhrazené a pøeplacené</option>
                        <option value=\"0\" ".($this->uhrazeno==0? "selected=\"selected\"":"").">Neuhrazené</option>
                        <option value=\"1\" ".($this->uhrazeno==1? "selected=\"selected\"":"").">Èásteènì uhrazené</option>
                        <option value=\"2\" ".($this->uhrazeno==2? "selected=\"selected\"":"").">Uhrazené</option>
                        <option value=\"3\" ".($this->uhrazeno==3? "selected=\"selected\"":"").">Pøeplacené</option>
                        </select>";
                
		$input_prijemce="<input name=\"faktura_prijemce\" type=\"text\" value=\"".$this->faktura_prijemce."\" />";
                $input_klient="<input name=\"faktura_klient\" type=\"text\" value=\"".$this->faktura_klient."\" />";
                $input_serial="<input name=\"serial_nazev\" type=\"text\" value=\"".$this->serial_nazev."\" />";
                $input_datum="<input name=\"datum_vystaveni\" type=\"text\" value=\"".$this->change_date_en_cz($this->datum_vystaveni)."\" />";
                $input_objednavka="<input name=\"id_objednavka\" type=\"text\" value=\"".$this->id_objednavka."\" />";
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
		
		
		
		//vysledny formular		
		$vystup="
			<form method=\"post\" action=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=zeme-destinace-nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>Èíslo faktury: <td>".$input_cislo."<td>Text pøíjemce: <td>".$input_prijemce."<td>Klient / organizace: <td>".$input_klient." <td>Stav splacení: <td>".$select_zaplaceno."
                                <tr>            
                                        <td>Název seriálu: <td>".$input_serial."<td>ID objednávky: <td>".$input_objednavka." <td>Datum vystavení: <td>".$input_datum."  
                                <tr>            
					<td colspan=\"2\">".$submit."</td>
			</table>
			</form>
		";
		return $vystup;		
	}		

	/**zobrazi hlavicku k seznamu*/
	function show_list_header($typ=""){
		if( !$this->get_error_message()){
			//predani id_serial (pokud existuje - editace serialu->foto)
			$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
                        if($typ=="plain"){
                            $vystup="
                                <script type='text/javascript' src='./js/faktury_list.js'></script>
				<table class=\"list\">
                                        <tr>
                                        <th colspan=\"8\">Faktury
					<tr>
						<th>Èíslo faktury
						</th>
						<th>Pøíjemce
						</th>	
                                                <th>Klient
						</th>	
						<th width=70>Èástka
						</th>
						<th width=80>Vystaveno
						</th>
                                                <th width=80>Splatnost
                                                </th>
                                                <th>Uhrazeno
						</th>
						<th>Možnosti editace
						</th>
					</tr>
                            ";
                        }else{
                            $vystup="
                                <script type='text/javascript' src='./js/faktury_list.js'></script>
				<table class=\"list\">
					<tr>
						<th width=75><span title=\"Èíslo faktury\">È. FA.</span>
						<div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
                                                <th width=65><span title=\"id objedávky\">ID obj.</span>
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=objednavka_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=objednavka_down".$serial."\"></a>
							</div>
						</th>
						<th>Pøíjemce
						<div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=prijemce_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=prijemce_down".$serial."\"></a>
							</div>
						</th>	
                                                <th>Klient
						<div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=klient_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=klient_down".$serial."\"></a>
							</div>
						</th>
						<th width=65>Èástka
						<div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=castka_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=castka_down".$serial."\"></a>
							</div>
						</th>
						<th width=80>Vystaveno
						<div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=vystaveno_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=vystaveno_down".$serial."\"></a>
							</div>
						</th>
                                                <th width=75>Splatnost
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=splatnost_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=splatnost_down".$serial."\"></a>
							</div>
						</th>
                                                <th width=80>Uhrazeno
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zaplaceno_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zaplaceno_down".$serial."\"></a>
							</div>
						</th>
                                                <th>Seriál, zájezd
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zajezd_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=faktury_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zajezd_down".$serial."\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
                            ";
                        }
			return $vystup;
		}
	}	
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
            
            if( !$this->get_error_message()){
		//z jadra ziskame faktury o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
                        if($this->radek["id_organizace"]>0){
                             $klient =  "<a target='_blank' href='organizace.php?id_organizace=" . $this->radek["id_organizace"] . "&typ=organizace&pozadavek=edit'>".$this->radek["nazev_organizace"]." (".$this->radek["ico"].")</a>";                                          
                        }else{
                            $klient =  "<a target='_blank' href='klienti.php?id_klient=" . $this->radek["id_klient"] . "&typ=klient&pozadavek=edit'>".$this->radek["klient_prijmeni"]." ".$this->radek["klient_jmeno"]."</a>";
                        }

                        $prijemce_text = trim(strip_tags($this->radek["prijemce_text"]));
                        $prijemce_text = trim(str_replace("Odbìratel:", "", $prijemce_text));
                        $prijemce_text = trim(str_replace("&nbsp;", "", $prijemce_text));
                        //vyhodim text za IÈO
                        $pos1 = stripos($prijemce_text, "IÈO:");
                        $pos2 = stripos($prijemce_text, "IÈ:");
                        if($pos1!==false){
                            $prijemce_text = trim(substr($prijemce_text, 0, $pos1));
                        }else if($pos2!==false){
                            $prijemce_text = trim(substr($prijemce_text, 0, $pos2));
                        }
                        $prijemce_text = nl2br($prijemce_text);                        
                        if($this->radek["zaplaceno"]==0){
                            $uhrazeno = "<td class=\"red\">NE</td>"; 
                        }else if($this->radek["zaplaceno"]==1){    
                            $uhrazeno = "<td class=\"orange\">Èásteènì</td>";
                        }else if($this->radek["zaplaceno"]==2){    
                            $uhrazeno = "<td class=\"green\">UHRAZENO</td>";
                        }else if($this->radek["zaplaceno"]==3){    
                            $uhrazeno = "<td class=\"blue\">Pøeplaceno</td>";
                        } 
                        
		if($typ_zobrazeni=="tabulka"){
			if($this->suda==1){
				$vypis="<tr style=\"background-color:#ececec\">";
				}else{
				$vypis="<tr style=\"background-color:#f5f5f5\">";
			}

                        if($this->radek["nazev_ubytovani"]!="" and $this->radek["id_sablony_zobrazeni"]==12){
                            $ubyt = $this->radek["nazev_ubytovani"].", ";
                        }else{
                            $ubyt="";
                        }
                        $zajezd = $ubyt.$this->radek["nazev_serialu"];
                        $zajezd = "<a href=\"/admin/serial.php?id_serial=".$this->radek["id_serial"]."&typ=serial&pozadavek=edit\">".$zajezd."</a>";
                        if($this->radek["nazev_zajezdu"]!=""){
                            $zajezd .= ", ".$this->radek["nazev_zajezdu"];
                        }else{
                            $zajezd .= ", ".$this->change_date_en_cz_short($this->radek["od"])." - ".$this->change_date_en_cz($this->radek["do"]);
                        }

                        

                        
			$vypis = $vypis."
							<td >".$this->radek["cislo_faktury"]."</td>
                            
                                                        <td ><a href='/admin/objednavky.php?idObjednavka=".$this->radek["id_objednavka"]."'>".$this->radek["id_objednavka"]."</a></td>
							<td >".$prijemce_text."</td>
                                                        <td >".$klient."</td>    
							<td >".$this->radek["celkova_castka"]." ".$this->radek["mena"]."</td>
							<td >".$this->change_date_en_cz($this->radek["datum_vystaveni"])."</td>	
                                                        <td >".$this->change_date_en_cz($this->radek["datum_splatnosti"])."</td>
                                                       ".$uhrazeno."    
                                                        <td >".$zajezd."</td>
							<td class=\"menu\">
								  <a href=\"/admin/faktury.php?id_faktury=".$this->radek["id_faktury"]."&amp;typ=faktury&amp;pozadavek=edit\">EDIT</a>";
			$vypis = $vypis." | <a href=\"/admin/ts_faktura.php?id_faktury=".$this->radek["id_faktury"]."\">Zobrazit PDF</a>";
                        $vypis = $vypis." | <a onclick=\"faktura_pridat_platbu(".$this->radek["id_faktury"].",".$this->radek["id_objednavka"].");\" href=\"#\">Pøidat platbu</a>";
			$vypis = $vypis." | <a class='anchor-delete' href=\"/admin/faktury.php?id_faktury=".$this->radek["id_faktury"]."&amp;typ=faktury&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>
                                                <tr id=\"nova_platba_".$this->radek["id_faktury"]."\"></tr>";
						
			return $vypis;
		
		}else if($typ_zobrazeni=="tabulka_objednavka"){
  			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}

                        $zajezd = $this->radek["nazev_serialu"];
                        $zajezd = "<a href=\"/admin/serial.php?id_serial=".$this->radek["id_serial"]."&typ=serial&pozadavek=edit\">".$zajezd."</a>";
                        if($this->radek["nazev_zajezdu"]!=""){
                            $zajezd .= ", ".$this->radek["nazev_zajezdu"];
                        }else{
                            $zajezd .= ", ".$this->change_date_en_cz_short($this->radek["od"])." - ".$this->change_date_en_cz($this->radek["do"]);
                        }
                        
			$vypis = $vypis."
							<td >".$this->radek["cislo_faktury"]."</td>
                                            		<td >".$prijemce_text."</td>
                                                            <td >".$klient."</td>  
							<td >".$this->radek["celkova_castka"]." ".$this->radek["mena"]."</td>
							<td >".$this->change_date_en_cz($this->radek["datum_vystaveni"])."</td>	
                                                        <td >".$this->change_date_en_cz($this->radek["datum_splatnosti"])."</td>
                                                       ".$uhrazeno."
							<td class=\"menu\">
								  <a href=\"/admin/faktury.php?id_faktury=".$this->radek["id_faktury"]."&amp;typ=faktury&amp;pozadavek=edit\">edit</a>";
			$vypis = $vypis." | <a href=\"/admin/ts_faktura.php?id_faktury=".$this->radek["id_faktury"]."\">Zobrazit PDF</a>";
                        $vypis = $vypis." | <a onclick=\"faktura_pridat_platbu(".$this->radek["id_faktury"].",".$this->radek["id_objednavka"].");\" href=\"#\">Pøidat platbu</a>";
			$vypis = $vypis." | <a class='anchor-delete' href=\"/admin/faktury.php?id_faktury=".$this->radek["id_faktury"]."&amp;typ=faktury&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
							</td>
						</tr>
                                                <tr id=\"nova_platba_".$this->radek["id_faktury"]."\"></tr>";
						
			return $vypis;                  
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
