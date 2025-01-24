<?php
/** 
* foto_list.inc.php - trida pro zobrazeni seznamu fotek 
*/

/*------------------- SEZNAM fotografii -------------------  */

class Foto_list extends Generic_list{
	//vstupni data
	protected $id_zamestnance;
	protected $id_zeme;
	protected $id_destinace;
	protected $nazev;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	protected $pridat_pro_objekt_kategorie;
	protected $pocet_zajezdu;
		
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($id_zamestnance, $id_zeme, $id_destinace, $nazev, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
                $this->pridat_pro_objekt_kategorie = "";
	//kontrola vstupnich dat
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_zeme = $this->check_int($id_zeme);//odpovida poli nazev_typ_web
		$this->id_destinace = $this->check_int($id_destinace);//odpovida poli nazev_typ_web
		$this->nazev = $this->check($nazev);//odpovida poli nazev
		$this->zacatek = $this->check_int($zacatek);
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu);
		
		//pokud mam dostatecna prava pokracovat
		if( $this->legal() ){
			//ziskam celkovy pocet zajezdu ktere odpovidaji
                      if($_SESSION["foto_nepouzite"]==1){
                             $data_pocet=$this->database->query($this->create_query("nepouzite",1))
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				$zaznam_pocet = mysqli_fetch_array($data_pocet);
				$this->pocet_zajezdu = $zaznam_pocet["pocet"];

                            //ziskani seznamu z databaze
                            $this->data=$this->database->query($this->create_query("nepouzite"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                            //zjistuju, zda mam neco k zobrazeni

                      }else{
			$data_pocet=$this->database->query($this->create_query("show",1))
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				$zaznam_pocet = mysqli_fetch_array($data_pocet);
				$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//zjistuju, zda mam neco k zobrazeni
                         }
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádný objekt");
			}							

		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
  
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu do databaze*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku=="show"){
			//definice jednotlivych casti dotazu
			if($this->id_zeme!=""){
				$where_zeme=" `foto`.`id_zeme` =".$this->id_zeme." and";
			}else{
				$where_zeme="";
			}
			if($this->id_destinace!=""){
				$where_destinace=" `foto`.`id_destinace` =".$this->id_destinace." and";
			}else{
				$where_destinace="";
			}	
			if($this->nazev!=""){
				$where_nazev=" `nazev_foto` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}		
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `foto`.`id_foto`";
			}		
			if($only_count==1){
				$select="select count(`id_foto`) as pocet";
				$limit="";
			}else{
				$select="select * ";
			}
		
			$dotaz=	$select."
					  from  `foto` 
					  	join `zeme` on (`foto`.`id_zeme` = `zeme`.`id_zeme` )
						where ".$where_zeme.$where_destinace.$where_nazev." 1 
						order by ".$order." ".
						$limit;
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
		}else if($typ_pozadavku =="nepouzite"){
//definice jednotlivych casti dotazu
			if($this->id_zeme!=""){
				$where_zeme=" `foto`.`id_zeme` =".$this->id_zeme." and";
			}else{
				$where_zeme="";
			}
			if($this->id_destinace!=""){
				$where_destinace=" `foto`.`id_destinace` =".$this->id_destinace." and";
			}else{
				$where_destinace="";
			}
			if($this->nazev!=""){
				$where_nazev=" `nazev_foto` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			if($this->order_by!=""){
				$order=$this->order_by($this->order_by);
			}else{
				$order=" `foto`.`id_foto`";
			}
			if($only_count==1){
				$select="select count(`foto`.`id_foto`) as pocet";
				$limit="";
			}else{
				$select="select `foto`.*,`zeme`.*  ";
			}

			$dotaz=	$select."
					  from  `foto`
					  	join `zeme` on (`foto`.`id_zeme` = `zeme`.`id_zeme` )
                                                left join `foto_serial`  on (`foto`.`id_foto` = `foto_serial`.`id_foto` )
                                                left join `foto_aktuality`  on (`foto`.`id_foto` = `foto_aktuality`.`id_foto` )
                                                left join `foto_informace`  on (`foto`.`id_foto` = `foto_informace`.`id_foto` )
                                                left join `foto_ohlasy`  on (`foto`.`id_foto` = `foto_ohlasy`.`id_foto` )
                                                left join `foto_objekty`  on (`foto`.`id_foto` = `foto_objekty`.`id_foto` )
                                                left join `foto_objekt_kategorie`  on (`foto`.`id_foto` = `foto_objekt_kategorie`.`id_foto` )
						where ".$where_zeme.$where_destinace.$where_nazev." 1 and
                                                    `foto_serial`.`id_foto` is null and
                                                    `foto_aktuality`.`id_foto` is null and
                                                    `foto_informace`.`id_foto` is null and
                                                    `foto_ohlasy`.`id_foto` is null and
                                                    `foto_objekty`.`id_foto` is null and
                                                    `foto_objekt_kategorie`.`id_foto` is null
						order by ".$order." ".
						$limit;
			//echo $dotaz;
			return $dotaz;

                }
	}	
	/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`foto`.`id_foto` ";
   			 break;
			case "id_down":
				 return "`foto`.`id_foto` desc ";
   			 break;				 
			case "nazev_up":
				 return "`foto`.`nazev_foto`";
   			 break;
			case "nazev_down":
				 return "`foto`.`nazev_foto` desc";
   			 break;
			case "zeme_up":
				 return "`foto`.`nazev_zeme`,`foto`.`id_destinace`,`foto`.`nazev_foto`";
   			 break;
			case "zeme_down":
				 return "`foto`.`nazev_zeme` desc,`foto`.`id_destinace` desc,`foto`.`nazev_foto` desc";
   			 break;				 				 				 
		}
		//pokud zadan nespravny vstup, vratime id_foto
		return "`foto`.`id_foto`";
	}
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$serial="";
                        $_GET["id_pr_stranky"]?($serial="&amp;id_pr_stranky=".$_GET["id_pr_stranky"]." "):($serial=$serial);
			$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial=$serial);
			$_GET["id_informace"]?($serial="&amp;id_informace=".$_GET["id_informace"]." "):($serial=$serial);
			$_GET["id_aktuality"]?($serial="&amp;id_aktuality=".$_GET["id_aktuality"]." "):($serial=$serial);
			$_GET["id_ohlasu"]?($serial="&amp;id_ohlasu=".$_GET["id_ohlasu"]." "):($serial=$serial);
                        $_GET["id_objektu"]?($serial="&amp;id_objektu=".$_GET["id_objektu"]." "):($serial=$serial);
			$_GET["id_ubytovani"]?($serial="&amp;id_ubytovani=".$_GET["id_ubytovani"]." "):($serial=$serial);
                        $_GET["id_typ"]?($serial="&amp;id_typ=".$_GET["id_typ"]." "):($serial=$serial);
                        $_GET["id_zapas"]?($serial="&amp;id_zapas=".$_GET["id_zapas"]." "):($serial=$serial);

		//tvroba input nazev
		$input_nazev="<input name=\"nazev_foto\" type=\"text\" value=\"".$this->nazev."\" />";
		$input_nepouzite="<input name=\"foto_nepouzite\" type=\"checkbox\" value=\"1\" ".($_SESSION["foto_nepouzite"]==1?("checked=\"checked\""):(""))." />";
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
		
		//tvorba select_zeme_destinace
		$zeme="<select name=\"zeme-destinace\"><option value=\"0:0\">--libovolná--</option>";
		//do promenne typy_serialu vytvorim instanci tridy seznam zemi
		$zeme_informace = new Zeme_list($this->id_zamestnance,"",$this->id_zeme,$this->id_destinace);
		//vypisu seznam zemi
		$zeme = $zeme.$zeme_informace->show_list("select_zeme_destinace");	
		$zeme=$zeme."</select>\n";	
				
		//vysledny formular		
		$vystup="
			<form method=\"post\" action=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=zeme-destinace-nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>Zemì/destinace: ".$zeme."</td>
					<td>Název fotky: ".$input_nazev."</td>
                                        <td>Zobrazit pouze nepoužívané fotky: ".$input_nepouzite."</td>
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
		$serial="";                        
                        $_GET["id_pr_stranky"]?($serial="&amp;id_pr_stranky=".$_GET["id_pr_stranky"]." "):($serial=$serial);
			$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial=$serial);
			$_GET["id_informace"]?($serial="&amp;id_informace=".$_GET["id_informace"]." "):($serial=$serial);
			$_GET["id_aktuality"]?($serial="&amp;id_aktuality=".$_GET["id_aktuality"]." "):($serial=$serial);
			$_GET["id_ohlasu"]?($serial="&amp;id_ohlasu=".$_GET["id_ohlasu"]." "):($serial=$serial);
                        $_GET["id_objektu"]?($serial="&amp;id_objektu=".$_GET["id_objektu"]." "):($serial=$serial);
                        $_GET["id_ubytovani"]?($serial="&amp;id_ubytovani=".$_GET["id_ubytovani"]." "):($serial=$serial);
                        $_GET["id_zapas"]?($serial="&amp;id_zapas=".$_GET["id_zapas"]." "):($serial=$serial);
			$vystup="
				<table class=\"list\">
				    <colgroup><col width='15%'><col width='15%'><col width='20%'><col width='20%'><col width='30%'></colgroup>
					<tr>
						<th>Foto</th>
						<th>Id
							<div class='sort'>
							    <a class='sort-up' href=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							    <a class='sort-down' href=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
						<th>Zemì
						    <div class='sort'>
							    <a class='sort-up' href=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zeme_up".$serial."\"></a>
							    <a class='sort-down' href=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=zeme_down".$serial."\"></a>
							</div>
						</th>
						<th>Název
						    <div class='sort'>
							    <a class='sort-up' href=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							    <a class='sort-down' href=\"?typ=foto_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}
	}	

	/**zobrazi polozku ze seznamu*/
	function show_list_item($typ_zobrazeni){
		if( !$this->get_error_message()){
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
				
		if($typ_zobrazeni=="tabulka_serial"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($adresa_serial = $core->get_adress_modul_from_typ("serial") ){
				GLOBAL $serial;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}

						
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
								 <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=1\">pøidat jako základní</a>
							 		| <a href=\"".$adresa_serial."?id_serial=".$serial->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=0\">pøidat</a>
							</td>
						</tr>";
				return $vypis;
			}
			
		}else if($typ_zobrazeni=="tabulka_objekty"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($adresa_serial = $core->get_adress_modul_from_typ("objekty") ){
				
                                
                            
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
                                $text_objekt_kategorie = str_replace("[id_foto]", $this->get_id_foto(), $this->pridat_pro_objekt_kategorie);
						
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
								 <a href=\"".$adresa_serial."?id_objektu=".$_GET["id_objektu"]."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=1\">pøidat jako základní</a>
							 		| <a href=\"".$adresa_serial."?id_objektu=".$_GET["id_objektu"]."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=0\">pøidat</a>
                                                                 ".$text_objekt_kategorie."
							</td>
						</tr>";
				return $vypis;
			}
		}else if($typ_zobrazeni=="tabulka_typ"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
								 <a href=\"?id_typ=".$_GET["id_typ"]."&amp;id_foto=".$this->get_id_foto()."&amp;typ=typ_serialu&amp;pozadavek=update_foto\">zmìnit na tuto fotografii</a>
							</td>
						</tr>";
				return $vypis;
			
			
		}else if($typ_zobrazeni=="tabulka_informace"){		
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Informace
			if($adresa_informace = $core->get_adress_modul_from_typ("informace") ){
				GLOBAL $informace;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
                                
                        $dotaz = "SELECT distinct `nazev_typ`,`id_typ`
                            FROM `typ_serial`
                            where `id_nadtyp`=0 order by `nazev_typ` ";

                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                        while($zaznam = mysqli_Fetch_Array($data)){
                                $option_zakladni_pro_typ .= "<option value=\"".$zaznam["id_typ"]."\" >".$zaznam["nazev_typ"]."</option>";
                        }
                        $form_zakladni_pro_typ = "
                            <form method=\"get\" action=\"".$adresa_informace."\">
                                 <input type=\"hidden\" name=\"id_informace\" value=\"".$informace->get_id()."\" />
                                 <input type=\"hidden\" name=\"id_foto\" value=\"".$this->get_id_foto()."\" />
                                 <input type=\"hidden\" name=\"typ\" value=\"foto\" />
                                 <input type=\"hidden\" name=\"pozadavek\" value=\"create\" />
                                 <input type=\"hidden\" name=\"zakladni_foto\" value=\"0\" />
                                     
                                Vložit jako základní pro typ: <select name=\"zakladni_pro_typ\">
                                 ".$option_zakladni_pro_typ."
                                 </select>
                                 <input type=\"submit\" value=\"&gt;&gt;\"/>
                            </form>";
                        
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
							  <a href=\"".$adresa_informace."?id_informace=".$informace->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=1\">pøidat jako základní</a>
							 | <a href=\"".$adresa_informace."?id_informace=".$informace->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=0\">pøidat</a><br/>
                                                           ".$form_zakladni_pro_typ."  

							</td>
						</tr>";
				return $vypis;
			}
                }else if($typ_zobrazeni=="tabulka_pr_stranky"){		
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Informace
			if($adresa_pr_stranka = $core->get_adress_modul_from_typ("pr_stranky") ){
				GLOBAL $pr_stranka;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
			
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
                                                            <a href=\"".$adresa_pr_stranka."?id_pr_stranky=".$pr_stranka->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;pridat_jako=1\">pøidat jako první</a>
                                                            | <a href=\"".$adresa_pr_stranka."?id_pr_stranky=".$pr_stranka->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;pridat_jako=2\">pøidat jako druhou</a>
							</td>
						</tr>";
				return $vypis;
			}
                }else if($typ_zobrazeni=="tabulka_ubytovani"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Informace
			if($adresa_informace = $core->get_adress_modul_from_typ("ubytovani") ){
				GLOBAL $serial;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}

				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
							  <a href=\"".$adresa_informace."?id_ubytovani=".$serial->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=1\">pøidat jako základní</a>
							 | <a href=\"".$adresa_informace."?id_ubytovani=".$serial->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=0\">pøidat</a>

							</td>
						</tr>";
				return $vypis;
			}
                }else if($typ_zobrazeni=="tabulka_zapas"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Informace
			if($adresa_informace = $core->get_adress_modul_from_typ("zapas") ){
				GLOBAL $serial;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}

				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
							  <a href=\"".$adresa_informace."?id_zapas=".$serial->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=1\">pøidat jako základní</a>
							 | <a href=\"".$adresa_informace."?id_zapas=".$serial->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create&amp;zakladni_foto=0\">pøidat</a>

							</td>
						</tr>";
				return $vypis;
			}
		}else if($typ_zobrazeni=="tabulka_aktuality"){		
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Informace
			if($adresa_aktuality = $core->get_adress_modul_from_typ("aktuality") ){
				GLOBAL $aktuality;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
			
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
							 	<a href=\"".$adresa_aktuality."?id_aktuality=".$aktuality->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create\">pøidat</a>

							</td>
						</tr>";
				return $vypis;
			}			
		}else if($typ_zobrazeni=="tabulka_ohlasy"){		
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Informace
			if($adresa_aktuality = $core->get_adress_modul_from_typ("ohlasy") ){
				GLOBAL $aktuality;
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
			
				$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\" valign=\"top\">".$this->get_id_foto()."</td>
							<td class=\"zeme\" valign=\"top\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\" valign=\"top\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\" valign=\"top\">
							 	<a href=\"".$adresa_aktuality."?id_ohlasu=".$aktuality->get_id()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=create\">pøidat</a>

							</td>
						</tr>";
				return $vypis;
			}					
		}else if($typ_zobrazeni=="tabulka_foto"){
		//pro spravne zobrazeni musi byt v promenne $serial nainicializivana trida typu Serial
			if($this->suda==1){
				$vypis="<tr class=\"suda\">";
				}else{
				$vypis="<tr class=\"licha\">";
			}
			
			$vypis=$vypis."
							<td  class=\"foto\">
								<a href=\"/".ADRESAR_FULL."/".$this->get_foto_url()."\">
								<img src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
									  alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\" 
									   width=\"80\" height=\"55\"/>
								</a>
							</td>
							<td class=\"id\">".$this->get_id_foto()."</td>
							<td class=\"zeme\">".$this->get_nazev_zeme()."</td>
							<td class=\"nazev\">".$this->get_nazev_foto()."<br/>".$this->get_popisek_foto()."</td>
							<td class=\"menu\">
								<a href=\"".$adresa_modulu."?id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=edit\">editovat foto</a>
                                                                | <a href=\"https://images.google.com/searchbyimage?image_url=https://slantour.cz/".ADRESAR_FULL."/".$this->get_foto_url()."\" target=\"_blank\">podobné fotografie (google)</a>   
								| <a href=\"".$adresa_modulu."?id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=delete\" class='anchor-delete' onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
                                                                |  <input value='" . $this->get_id_foto() . "' type='checkbox' onchange='markUnmarkToDel(this);' />
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
			$promenne= preg_replace("/str=[0-9]*&?/","",$_SERVER["QUERY_STRING"]);
		
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
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_foto() { return $this->radek["id_foto"];}
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_nazev_zeme() { return $this->radek["nazev_zeme"];}
	function get_popisek_foto() { return $this->radek["popisek_foto"];}
	function get_foto_url() { return $this->radek["foto_url"];}
        function set_objekt_kategorie_name($text){
            $this->pridat_pro_objekt_kategorie = $text;
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
