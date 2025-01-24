<?php
/**
* klient_list.inc.php - tridy pro zobrazeni seznamu klientù
*/


/*------------------- SEZNAM klientu -------------------  */

class Objekty_list extends Generic_list{
	//vstupni data
	protected $typ_pozadavku;
	protected $order_by;
	protected $nazev;
	protected $id_organizace;
	protected $typ_objektu;
        
	protected $zacatek;

	protected $moznosti_editace;

//------------------- KONSTRUKTOR  -----------------
/**konstruktor tøídy*/
	function __construct($typ_pozadavku, $nazev, $id_organizace, $typ_objektu, $zacatek, $order_by, $moznosti_editace, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

	//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->moznosti_editace = $this->check($moznosti_editace);

		$this->nazev = $this->check($nazev);
		$this->id_organizace = $this->check_int($id_organizace);
		$this->typ_objektu = $this->check_int($typ_objektu);


		$this->zacatek = $this->check_int($zacatek);
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu);

		//pokud mam dostatecna prava pokracovat
		if( $this->legal() ){
			//ziskam celkovy pocet zajezdu ktere odpovidaji
			$data_pocet=$this->database->query($this->create_query($this->typ_pozadavku,1))
			 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$zaznam_pocet = mysqli_fetch_array($data_pocet);
			$this->pocet_zajezdu = $zaznam_pocet["pocet"];

			//ziskani seznamu z databaze
			$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
			}

		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
		}
                //echo $this->getZacatek().",".$this->getPocetZajezdu().",".$this->getPocetZaznamu();

	}
//------------------- METODY TRIDY -----------------
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku=="show_all" or $typ_pozadavku=="show_serial"){
			//tvorba podminek dotazu
			if($this->nazev!=""){
				$where_nazev=" `objekt`.`nazev_objektu` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}
			if($this->id_organizace!=0){
				$where_id_organizace=" `objekt`.`id_organizace` = ".$this->id_organizace." and";
			}else{
				$where_id_organizace=" ";
			}
			if($this->typ_objektu!=""){
				$where_typ_objektu=" `objekt`.`typ_objektu` = ".$this->typ_objektu."  and";
			}else{
				$where_typ_objektu=" ";
			}
                        
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			$order=$this->order_by($this->order_by);

			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$select="select count(distinct `objekt`.`id_objektu`) as `pocet`";
				$limit="";
                                $group_by = "";
			}else{
				$select="select `objekt`.*, `objekt_letenka`.automaticka_kontrola_cen,`objekt_letenka`.automaticka_odlozena_kontrola_cen, `organizace`.*,
                                    group_concat(DISTINCT `serial`.`nazev` order by `serial`.`nazev` separator \";\") as `nazev_serialu`,
                                    group_concat(concat(`topologie_tok`.`id_tok_topologie`,' ',`topologie_tok`.`id_topologie`) separator \";\") as `topologie`,
                                    group_concat(DISTINCT `serial`.`id_serial` order by `serial`.`nazev` separator \";\") as `id_serial`";
                                $group_by = "group by `objekt`.`id_objektu` ";
			}
			//vysledny dotaz
			$dotaz= $select."
					 from `objekt`
                                         left join `organizace` on (`objekt`.`id_organizace` = `organizace`.`id_organizace`)
                                         left join `topologie_tok` on (`objekt`.`id_objektu` = `topologie_tok`.`id_objektu`)
										 left join `objekt_letenka` on (`objekt`.`id_objektu` = `objekt_letenka`.`id_objektu`)
                                         left join (`objekt_serial` join `serial` on (`objekt_serial`.`id_serial` = `serial`.`id_serial`)										 
                                            )on(`objekt`.`id_objektu` = `objekt_serial`.`id_objektu`)
					where ".$where_nazev.$where_id_organizace.$where_typ_objektu." 1
                                        $group_by     
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
				 return "`objekt`.`id_objektu`";
   			 break;
			case "id_down":
				 return "`objekt`.`id_objektu` desc";
   			 break;
			case "nazev_up":
				 return "`objekt`.`nazev_objektu`";
   			 break;
			case "nazev_down":
				 return "`objekt`.`nazev_objektu` desc";
   			 break;
			case "typ_up":
				 return "`objekt`.`typ_objektu`";
   			 break;
			case "typ_down":
				 return "`objekt`.`typ_objektu` desc";
   			 break;
                        case "organizace_up":
				 return "`organizace`.`nazev`";
   			 break;
			case "organizace_down":
				 return "`organizace`.`nazev` desc";
   			 break;
                       
                       
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`objekt`.`nazev_objektu`";
	}

	/**zobrazi formular pro filtorvani vypisu serialu*/
	function show_filtr(){

		//predani id_objednavka (pokud existuje - editace serialu->foto)
		$_GET["id_objednavka"]?($objednavka="&amp;id_objednavka=".$_GET["id_objednavka"].""):($objednavka="");

		//promenne, ktere musime pridat do odkazu
		$vars = "&amp;moznosti_editace=".$this->moznosti_editace;
                
		//tvroba input uzivatelske jmeno
		$input_nazev_objektu="<input name=\"objekt_nazev\" type=\"text\" value=\"".$this->nazev."\" />";
                
                //tvroba input organizace
		$objekt_id_organizace = "<select name=\"objekt_id_organizace\" >
                                        <option value=\"0\">---</option>";
                
                $objekt_id_organizace .= Serial_library::get_organizace_objektu($this->id_organizace);                
                $objekt_id_organizace .= "</select>";
                
            
                
		//tvroba input jmeno
		$typ_objektu = "<select name=\"objekt_typ\" >
                                        <option value=\"0\">---</option>";
                 $i=1;
                while(Serial_library::get_typ_objektu($i)!=""){
                if($this->typ_objektu==$i){
                    $selected_role = "selected=\"selected\"";
                }else{
                    $selected_role = "";
                }
                $typ_objektu .= "<option value=\"".$i."\" ".$selected_role.">".Serial_library::get_typ_objektu($i)."</option>";
                $i++;
                }
                $typ_objektu .= "</select>";
                
            
     
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";
                if($this->typ_pozadavku=="show_serial"){
                    $action = "?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=nazev";
                    $vars .= "&amp;id_serial=".$_GET["id_serial"];
                }else{
                    $action = "?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=nazev";
                }
		//vysledny formular
		$vystup="
			<form method=\"post\" action=\"".$action.$objednavka.$vars."\">
			<table class=\"filtr\">
				<tr>
					<td>Název objektu: ".$input_nazev_objektu."</td>
					<td>Typ objektu: ".$typ_objektu."</td>
                                        <td>Objekt pøiøazen k organizaci: ".$objekt_id_organizace."</td> 
					<td>".$submit."</td>
				</tr>
			</table>
			</form>
		";
		return $vystup;
	}

	/**zobrazi nadpis seznamu*/
	function show_header(){
		if($this->moznosti_editace=="select_klient_objednavky"){
			$vystup="
				<h3>Vyberte klienta, který zájezd objednává</h3>
			";
		}else{
			$vystup="
				<h3>Seznam objektù</h3>
			";
		}
		return $vystup;
	}

	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
		if( !$this->get_error_message()){
			$vars="&amp;moznosti_editace=".$this->moznosti_editace;
                        if($this->typ_pozadavku=="show_serial"){
                            $vars .= "&amp;id_serial=".$_GET["id_serial"];
                            $headerSerialy = "";
                        }else{
                            $headerSerialy = "<th>Pøiøazené seriály
						</th> ";
                        }
                          $vystup="

						  <script type='text/javascript' src='js/objekty.js'></script>
				<table class=\"list\">
					<tr>
						<th title=\"Automatická kontrola\">Auto. kontr.
						<th>Id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=id_up".$vars."\"></a>
							<a class='sort-down' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=id_down".$vars."\"></a>
							</div>
						</th>
						<th>Název objektu
						<div class='sort'>
							<a class='sort-up' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=nazev_up".$vars."\"></a>
							<a class='sort-down' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=nazev_down".$vars."\"></a>
							</div>
						</th>
                                                <th>Typ objektu
                                                <div class='sort'>
							<a class='sort-up' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=typ_up".$vars."\"></a>
							<a class='sort-down' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=typ_down".$vars."\"></a>
							</div>
						</th>
                                                ".$headerSerialy."
						<th>Organizace
						<div class='sort'>
							<a class='sort-up' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=organizace_up".$vars."\"></a>
							<a class='sort-down' href=\"?typ=objekty_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;objekt_order_by=organizace_down".$vars."\"></a>
							</div>
						</th>      
                                                <th>Objektové kategorie
						</th> 
						<th>Možnosti editace
						</th>
					</tr>
			";                                                  
			
			return $vystup;
		}
	}

    public function show_list_footer()
    {
        $vystup = "";

        $vystup .= "<tr>
                        <td colspan='7'><input type='submit' value='Oznaèené pøidat do automatické kontroly' id='autocheck-selected'/>  <input type='submit' value='Oznaèené pøidat do odložené automatické kontroly' id='autocheck-selected-delayed'/> <input type='submit' class='action-warning' value='Oznaèené odebrat z automatické kontroly' id='remAutocheck-selected'/><input type='submit' class='action-warning' value='Oznaèené odebrat z odložené automatické kontroly' id='remAutocheck-selected-delayed'/></td>
                    </tr>
                </table>";

        return $vystup;
    }	
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
		if( !$this->get_error_message()){
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $core->get_adress_modul_from_typ("klienti");

		if($typ_zobrazeni=="tabulka"){                    
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			//text pro typ informaci
                       $pouzite_ok = "";
                        $query_ok = "select `objekt_kategorie`.`id_objekt_kategorie`,`objekt_kategorie`.`nazev` 
                                                from `objekt_kategorie`
                                                where `id_objektu`=".$this->radek["id_objektu"]."";
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                        //echo mysqli_error();
                        //echo $query_ok;
                        while ($row_ok = mysqli_fetch_array($data)) {
                            $pouzite_ok .= $row_ok["id_objekt_kategorie"].": ".$row_ok["nazev"]."<br/>";
                        }
                        
                        if($this->radek["nazev_serialu"]!=""){
                            $serial="";
                            $array_objekty = explode(";", $this->radek["nazev_serialu"]);
                            $array_id_objektu = explode(";", $this->radek["id_serial"]);
                            foreach ($array_objekty as $key => $value) {
                               $serial .= "<a href=\"/admin/serial.php?id_serial=".$array_id_objektu[$key]."&typ=serial&pozadavek=edit\">".$value."</a><br/>" ;
                            }
                        }else{
                            $serial="";
                        }
                        if($this->radek["topologie"]!=""){
                            $array_topologie = explode(";", $this->radek["topologie"]);
                            foreach ($array_topologie as $key => $top_enum) {
                               $topologie_ids = explode(" ", $top_enum); 
                               $id_tok_topologie = $topologie_ids[0];
                               $id_topologie = $topologie_ids[1];
                               $topologie .= "<br/><div style=\"margin-left:25px;\"> - <a href=\"/admin/topologie_objektu.php?id_tok_topologie=".$id_tok_topologie."&id_topologie=".$id_topologie."&typ=topologie&pozadavek=zasedaci_poradek\">Zasedací poøádek $id_tok_topologie</a></div>" ;
                            }
                        }else{
                            $topologie = "";
                        }
                        if($this->typ_pozadavku=="show_serial"){
                            $listItemSerialy = "";
                        }else{
                            $listItemSerialy = "<td class=\"nazev\">".$serial."</td>  ";
                        }
                        $vypis = $vypis."
							<td class=\"id\"><input type='checkbox' name='selected_ids' value='".$this->radek["id_objektu"]."'>
							 (".($this->radek["automaticka_kontrola_cen"]?("<span class='green'>ANO</span>"):("<span class='red'>NE</span>"))."
                              | ".($this->radek["automaticka_odlozena_kontrola_cen"]?("<span class='green'>ANO</span>"):("<span class='red'>NE</span>")).")
							</td>
							<td class=\"id\">".$this->radek["id_objektu"]."</td>
							<td class=\"jmeno\"> <a href=\"objekty.php?id_objektu=".$this->radek["id_objektu"]."&amp;typ=objekty&amp;pozadavek=edit\">".$this->radek["nazev_objektu"]."</a>$topologie</td>
							<td class=\"datum_narozeni\">".Serial_library::get_typ_objektu($this->radek["typ_objektu"])."</td>
                                                         ".$listItemSerialy."  
                                                        <td class=\"datum_narozeni\"><a href=\"organizace.php?id_organizace=".$this->radek["id_organizace"]."&typ=organizace&pozadavek=edit\">".$this->radek["nazev"]."</a></td>      
							<td>".$pouzite_ok."
                                                        <td class=\"menu\">"; 
                        if($this->typ_pozadavku=="show_serial"){
                                $vypis = $vypis."
								 <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;id_serial=".$_GET["id_serial"]."&amp;typ=serial_objekty&amp;pozadavek=create\">Pøidat objekt</a>";
                            
                        }else{		
                            if(Serial_library::get_typ_objektu($this->radek["typ_objektu"])=="Vstupenka"){
                              /*  $create_serial = "| <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=create_serial&amp;pozadavek=create_from_vstupenka\">vytvoøit seriál</a>
                                                  | <a title=\"U seriálù aktualizuje služby, fotografie a zájezdy \" href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=update_serial&amp;pozadavek=update_from_vstupenka\">aktualizovat vytvoøené seriály</a>  ";*/
                                $create_serial = "<br/> <form style=\"font-size:0.9em;\" action=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=create_serial&amp;pozadavek=create_from_vstupenka\" method=\"post\"><strong>Vytvoøit seriál</strong>: nový název <input type=\"text\" name=\"nazev\" /><input type=\"submit\" value=\"&gt;&gt;\" /></form>";
                              //  $create_serial .= "| <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=update_serial&amp;pozadavek=update_from_vstupenka\">update pøiøazených seriálù</a> ";
                            
                            }else{
                                $create_serial = "";
                            }
                            if(Serial_library::get_typ_objektu($this->radek["typ_objektu"])=="Letuška API"){
                                $tokVypis = "| <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=tok_list&amp;pozadavek=show_letuska\">Cenova mapa</a> ";
                                
                            }else if(Serial_library::get_typ_objektu($this->radek["typ_objektu"])=="GoGlobal API"){
                                $tokVypis = "| <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=tok_list&amp;pozadavek=show_goglobal\">Cenova mapa</a> ";
                                
                            }else{
                                $tokVypis = "| <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=tok_list&amp;pozadavek=show\">TOK</a> ";
                            }
				$vypis = $vypis."
								 <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=objekty&amp;pozadavek=edit\">upravit/zobrazit</a>
                                                                    
                                                                $tokVypis   
                                                                | <a href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=foto\">foto</a>     
								| <a class='anchor-delete' href=\"?id_objektu=".$this->radek["id_objektu"]."&amp;typ=objekty&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
								 ".$create_serial."			
                                                            ";
                        }
				//pokud je klient vytvoøen cestovní kanceláøí, je zde odkaz pro vytvoøení uživ. jména a hesla
			

			$vypis = $vypis."

							</td>
						</tr>";

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

	/*metody pro pristup k parametrum*/
	function get_id_klient() { return $this->radek["id_klient"];}

	function get_jmeno() { return $this->radek["jmeno"];}
	function get_prijmeni() { return $this->radek["prijmeni"];}
	function get_datum_narozeni() { return $this->radek["datum_narozeni"];}
        function get_ico() { return $this->radek["ico"];}
	function get_telefon() { return $this->radek["telefon"];}
	function get_email() { return $this->radek["email"];}
	function get_cislo_pasu() { return $this->radek["cislo_pasu"];}
	function get_cislo_op() { return $this->radek["cislo_op"];}
	function get_mesto() { return $this->radek["mesto"];}
	function get_ulice() { return $this->radek["ulice"];}
	function get_psc() { return $this->radek["psc"];}

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
