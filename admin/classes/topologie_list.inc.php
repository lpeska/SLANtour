<?php
/** 
* informace_lists.inc.php - tridy pro zobrazeni seznamu informac�
*/


/*------------------- SEZNAM informaci -------------------  */

class Topologie_list extends Generic_list{
	//vstupni data

	protected $nazev;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
//------------------- KONSTRUKTOR  -----------------	
/**konstruktor t��dy*/
	function __construct($nazev, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU){
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
			 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$zaznam_pocet = mysqli_fetch_array($data_pocet);
			$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

			//ziskani seznamu z databaze	
			$this->data=$this->database->query($this->create_query("show"))
			 	or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadan�m podm�nk�m nevyhovuje ��dn� objekt");
			}						
		}else{
			$this->chyba("Nem�te dostate�n� opr�vn�n� k po�adovan� akci");	
		}	
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
		if($typ_pozadavku=="show"){
			//definice jednotlivych casti dotazu						
			if($this->nazev!=""){
				$where_nazev=" `topologie`.`nazev_topologie` like '%".$this->nazev."%' and";
			}else{
				$where_nazev=" ";
			}	
			if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}
			$order=$this->order_by($this->order_by);
		
			//pokud chceme pouze spo��tat vsechny odpov�daj�c� z�znamy
			if($only_count==1){
				$dotaz="select count(`topologie`.`id_topologie`) as pocet
                                        from `topologie`
					where ".$where_nazev." 1";
			}else{
				$dotaz="select `topologie`.*,
                                    count(distinct `id_polozka`) as `sedadel`,
                                    max(`row`) as `rows`,
                                    max(`col`) as `colls`
                                    from `topologie`
						left join `topologie_polozka` on (`topologie`.`id_topologie`=`topologie_polozka`.`id_topologie` and `class` not like '%noseat%')
					where ".$where_nazev." 1
                                        group by `topologie`.`id_topologie`
					order by ".$order."
					".$limit."";
			}		 
					
			//echo $dotaz;
			return $dotaz;
		}
	
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`topologie`.`id_topologie`";
   			 break;
			case "id_down":
				 return "`topologie`.`id_topologie` desc";
   			 break;				 
			case "nazev_up":
				 return "`topologie`.`nazev_topologie`";
   			 break;
			case "nazev_down":
				 return "`topologie`.`nazev_topologie`  desc";
   			 break;				 
			case "seat_up":
				 return "sedadel";
   			 break;
			case "seat_down":
				 return "sedadel desc";
   			 break;				 					 				 		 
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`topologie`.`id_topologie` desc";
	}
	/**zobrazi formular pro filtorvani vypisu*/
	function show_filtr(){
		//predani id_serial (pokud existuje - editace serialu->foto)
		$_GET["id_serial"]?($serial="&amp;id_serial=".$_GET["id_serial"]." "):($serial="");
		
		//tvroba input nazev
		$input_nazev="<input name=\"nazev_topologie\" type=\"text\" value=\"".$this->nazev."\" />";
		
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zm�nit filtrov�n�\" /><input type=\"reset\" value=\"P�vodn� hodnoty\" />";	
					
		
		//vysledny formular		
		$vystup="
			<form method=\"post\" action=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=nazev".$serial."\">
			<table class=\"filtr\">
				<tr>
					<td>N�zev topologie: ".$input_nazev."</td>
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
							<a class='sort-up' href=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down".$serial."\"></a>
							</div>
						</th>
                                                <th>N�zev
						<div class='sort'>
							<a class='sort-up' href=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down".$serial."\"></a>
							</div>
						</th>
						<th>Po�et sedadel
						<div class='sort'>
							<a class='sort-up' href=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=seat_up".$serial."\"></a>
							<a class='sort-down' href=\"?typ=topologie_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=seat_down".$serial."\"></a>
							</div>
						</th>						
						<th>Pozn�mka	
						</th>
						
						<th>Mo�nosti editace
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
                if($this->suda==1){
                        $vypis="<tr class=\"suda\">";
                        }else{
                        $vypis="<tr class=\"licha\">";
                }			
		if($typ_zobrazeni=="tabulka"){

                    $vypis = $vypis."
                                                    <td class=\"id\">".$this->radek["id_topologie"]."</td>
                                                    <td class=\"nazev\">".$this->radek["nazev_topologie"]."</td>	
                                                    <td class=\"nazev\">".$this->radek["sedadel"]." (".$this->radek["rows"]." �ad, ".$this->radek["colls"]." sloupc�)</td>
                                                    <td class=\"nazev\">".$this->radek["poznamka"]."</td>
                                                    <td class=\"menu\">
                                                              <a href=\"".$adresa_modulu."?id_topologie=".$this->radek["id_topologie"]."&amp;typ=topologie&amp;pozadavek=edit\">edit</a>";
                    $vypis = $vypis." | <a class='anchor-delete' href=\"".$adresa_modulu."?id_topologie=".$this->radek["id_topologie"]."&amp;typ=topologie&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
                                                    </td>
                                            </tr>";

                    return $vypis;
		
                }else if($typ_zobrazeni=="tabulka_zajezd"){
                    $vypis = $vypis."
                                                    <td class=\"id\">".$this->radek["id_topologie"]."</td>
                                                    <td class=\"nazev\">".$this->radek["nazev_topologie"]."</td>	
                                                    <td class=\"nazev\">".$this->radek["sedadel"]." (".$this->radek["rows"]." �ad, ".$this->radek["colls"]." sloupc�)</td>
                                                    <td class=\"nazev\">".$this->radek["poznamka"]."</td>
                                                    <td class=\"menu\">
                                                              <a href=\"".$adresa_modulu."?id_serial=".$_GET["id_serial"]."&id_zajezd=".$_GET["id_zajezd"]."&id_topologie=".$this->radek["id_topologie"]."&amp;typ=topologie&amp;pozadavek=add_new\">p�idat topologii k z�jezdu</a>";
                    $vypis = $vypis."  </td>
                                            </tr>";

                    return $vypis;
                }
            }//no error message
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
