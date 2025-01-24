<?php
/** 
* serial_dokument.inc.php - trida pro zobrazeni seznamu informací serialu v administracni casti
*											- a jejich create a delete
*/
 

/*------------------- SEZNAM informaci -------------------  */
/*rozsireni tridy Serial o seznam informaci*/
class Zajezd_topologie extends Generic_list{
	protected $typ_pozadavku;
	protected $id_serialu;
	protected $id_zamestnance;
	protected $id_objektu;
	
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_serial,$id_zajezd,$id_tok_topologie="",$id_klienti=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_serialu = $this->check_int($id_serial);
                $this->id_zajezdu = $this->check_int($id_zajezd);
                $this->id_tok_topologie = $this->check_int($id_tok_topologie);
                $this->id_klienti = $this->check($id_klienti);
                
                $zamestnanec = User_zamestnanec::get_instance();
		$this->id_zamestnance = $zamestnanec->get_id();

		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku)){
                    //na zaklade typu pozadavku vytvorim dotaz			
                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku),1)
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		

                    if($this->typ_pozadavku=="delete"){
                        //1. odstranim klienty tohoto zajezdu ze zasedaciho poradku
                        //2. pokud uz k zasedacimu poradku neexistuje zadny zajezd, smazu i zasedaci poradek
                        $klienti_data=$this->database->transaction_query($this->create_query("update_klienti_topologie"))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                        
                        $data_zajezd =  $this->database->transaction_query($this->create_query("get_pocet_zajezdu"))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                        $pocet_zajezdu = mysqli_fetch_array($data_zajezd);
                        
                        if($pocet_zajezdu["pocet"]==0){
                            $data=$this->database->transaction_query($this->create_query("delete_topologie_tok"))
                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                        }
                    }

                    //vygenerování potvrzovací hlášky
                    if( !$this->get_error_message() ){
                            $this->confirm("Požadovaná akce probìhla úspìšnì");
                            $this->database->commit();
                    }						
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
                        $this->database->rollback();
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show"){ //pøiøazené topologie
                    
                        $dotaz = " select `topologie`.*,
                                    `topologie_tok`.`id_tok_topologie`,
                                    count(distinct `id_polozka`) as `sedadel`,
                                    max(`row`) as `rows`,
                                    max(`col`) as `colls`
                                    from `topologie`
                                        join  `topologie_tok` on (`topologie`.`id_topologie`=`topologie_tok`.`id_topologie`)
                                        join  `zajezd_tok_topologie` on (`zajezd_tok_topologie`.`id_tok_topologie`=`topologie_tok`.`id_tok_topologie`  and `zajezd_tok_topologie`.`id_zajezd`=".$this->id_zajezdu.")    
                                        left join `topologie_tok_polozka` on (
                                            `topologie_tok`.`id_topologie`=`topologie_tok_polozka`.`id_topologie`
                                            and `topologie_tok`.`id_termin`=`topologie_tok_polozka`.`id_termin` 
                                            and`topologie_tok`.`id_objekt_kategorie`=`topologie_tok_polozka`.`id_objekt_kategorie` 
                                            and `class` not like '%noseat%')
                                                 
					where 1
                                        group by `topologie_tok`.`id_tok_topologie`";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="show_all"){ //pøiøazené topologie
                    
                        $dotaz = " select `topologie`.*,
                                    `topologie_tok`.`id_tok_topologie`,
                                    count(distinct `id_polozka`) as `sedadel`,
                                    max(`row`) as `rows`,
                                    max(`col`) as `colls`,
                                    group_concat(DISTINCT `serial`.`nazev` order by `serial`.`nazev` separator \";\") as `nazev_serialu`,
                                    group_concat(DISTINCT `serial`.`id_serial` order by `serial`.`nazev` separator \";\") as `id_serial`,
                                    min(`zajezd`.`od`) as `termin_od`,
                                    max(`zajezd`.`do`) as `termin_do`
                                    from `topologie`
                                        join  `topologie_tok` on (`topologie`.`id_topologie`=`topologie_tok`.`id_topologie`)
                                        join  `objekt_kategorie_termin` on (`objekt_kategorie_termin`.`id_termin`=`topologie_tok`.`id_termin` and `objekt_kategorie_termin`.`id_objekt_kategorie`=`topologie_tok`.`id_objekt_kategorie` )                                        
                                        join  `zajezd_tok_topologie` on (`zajezd_tok_topologie`.`id_tok_topologie`=`topologie_tok`.`id_tok_topologie` and `zajezd_tok_topologie`.`id_zajezd`!=".$this->id_zajezdu." )    
                                        join  `zajezd` on (`zajezd_tok_topologie`.`id_zajezd`=`zajezd`.`id_zajezd`)    
                                        join  `serial` on (`serial`.`id_serial`=`zajezd`.`id_serial`)  
                                        left join `topologie_tok_polozka` on (
                                            `topologie_tok`.`id_topologie`=`topologie_tok_polozka`.`id_topologie`
                                            and `topologie_tok`.`id_termin`=`topologie_tok_polozka`.`id_termin` 
                                            and`topologie_tok`.`id_objekt_kategorie`=`topologie_tok_polozka`.`id_objekt_kategorie` 
                                            and `class` not like '%noseat%')
                                                 
					where 1 
                                       group by `topologie_tok`.`id_tok_topologie`";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `zajezd_tok_topologie`
							(`id_zajezd`,`id_tok_topologie`)
						VALUES
							(".$this->id_zajezdu.",".$this->id_tok_topologie.")";
			//echo $dotaz;
			return $dotaz;
		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `zajezd_tok_topologie` 
						WHERE `id_zajezd`=".$this->id_zajezdu." and `id_tok_topologie`=".$this->id_tok_topologie."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="update_klienti_topologie"){
			$dotaz= "UPDATE `topologie_tok_polozka` SET `id_klient`= NULL 
                                where `id_tok_topologie`=$this->id_tok_topologie and `id_klient` in 
                                    (SELECT `objednavka_osoby`.`id_klient` FROM  
                                        `objednavka` join 
                                        `objednavka_osoby` on (`objednavka`.`id_objednavka` = `objednavka_osoby`.`id_objednavka`)
                                        WHERE `id_zajezd`=".$this->id_zajezdu.")
				";
			//echo $dotaz;
			return $dotaz;		
			
                        
                }else if($typ_pozadavku=="delete_klient_zasedaci_poradek"){
			$dotaz= "UPDATE `topologie_tok_polozka` SET `id_klient`= NULL 
                                where `id_tok_topologie`=$this->id_tok_topologie and `id_klient` in 
                                    ($this->id_klienti)
				";
			echo $dotaz;
			return $dotaz;	        
		}else if($typ_pozadavku=="get_pocet_zajezdu"){
			$dotaz= "SELECT count(`id_zajezd`) as `pocet` FROM  
                                        `zajezd_tok_topologie`
                                    WHERE `id_tok_topologie`=$this->id_tok_topologie  ";
			//echo $dotaz;
			return $dotaz;		
                        
		}else if($typ_pozadavku=="delete_topologie_tok"){
			$dotaz= "DELETE FROM `topologie_tok` 
                                    WHERE `id_tok_topologie`=".$this->id_tok_topologie."
                                    LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
		}
	}	
	/**zobrazi hlavicku k seznamu informaci*/
	function show_list_header($typ_zobrazeni){
            if($typ_zobrazeni=="tabulka"){
		$vystup="
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Název</th>
                                                <th>Poèet sedadel</th>
                                                <th>Poznámka</th>
						<th>Možnosti editace</th>
					</tr>
		";
            }else if($typ_zobrazeni=="tabulka_pridat"){
                $vystup="
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Název</th>
                                                <th>Pøiøazené seriály</th>
                                                <th>Termíny zájezdù</th>
                                                <th>Poèet sedadel</th>
                                                <th>Poznámka</th>
						<th>Možnosti editace</th>
					</tr>
		";
            }   
            return $vystup;
	}
	
		//kontrola zda smim provest danou akci
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
		$id_modul_objekt = $core->get_id_modul_from_typ("serial");
								
		if($typ_pozadavku == "show" or $typ_pozadavku == "show_all" ){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_objekt,"read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "delete" or $typ_pozadavku == "delete_klient_zasedaci_poradek"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_objekt,"read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_objekt,"read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;		
	}
	/**zobrazime seznam informaci pro dany serial*/		
	function show_list_item($typ_zobrazeni){
		if($this->suda==1){
                    $vypis="<tr class=\"suda\">";
                }else{
                    $vypis="<tr class=\"licha\">";
                }			
		if($typ_zobrazeni=="tabulka"){
                    $vypis = $vypis."
                        <td class=\"id\">".$this->radek["id_topologie"]." / ".$this->radek["id_tok_topologie"]."</td>
                        <td class=\"nazev\">".$this->radek["nazev_topologie"]."</td>	
                        <td class=\"nazev\">".$this->radek["sedadel"]." (".$this->radek["rows"]." øad, ".$this->radek["colls"]." sloupcù)</td>
                        <td class=\"nazev\">".$this->radek["poznamka"]."</td>
                        <td class=\"menu\">
                        <a href=\"topologie_objektu.php?id_serial=".$this->id_serialu."&id_zajezd=".$this->id_zajezdu."&id_topologie=".$this->radek["id_topologie"]."&id_tok_topologie=".$this->radek["id_tok_topologie"]."&amp;typ=topologie&amp;pozadavek=zasedaci_poradek\">ZASEDACÍ POØÁDEK</a>"
                            . " | <a class=\"anchor-delete\" href=\"".$adresa_modulu."?id_serial=".$this->id_serialu."&id_zajezd=".$this->id_zajezdu."&id_topologie=".$this->radek["id_topologie"]."&id_tok_topologie=".$this->radek["id_tok_topologie"]."&amp;typ=topologie&amp;pozadavek=delete_zasedaci_poradek\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>";
                                        
                    return $vypis;
                } else if($typ_zobrazeni=="tabulka_pridat"){
                    $array_objekty = explode(";", $this->radek["nazev_serialu"]);
                    $array_id_objektu = explode(";", $this->radek["id_serial"]);
                    foreach ($array_objekty as $key => $value) {
                       $serial .= "<a href=\"/admin/serial.php?id_serial=".$array_id_objektu[$key]."&typ=serial&pozadavek=edit\">".$value."</a><br/>" ;
                    }
                    
                    $vypis = $vypis."
                        <td class=\"id\">".$this->radek["id_topologie"]." / ".$this->radek["id_tok_topologie"]."</td>
                        <td class=\"nazev\">".$this->radek["nazev_tok"]." (".$this->radek["nazev_topologie"].")</td>	
                        <td class=\"nazev\">".$serial."</td>   
                        <td class=\"nazev\">".$this->change_date_en_cz_short($this->radek["termin_od"])." - ".$this->change_date_en_cz($this->radek["termin_do"])."</td> 
                        <td class=\"nazev\">".$this->radek["sedadel"]." (".$this->radek["rows"]." øad, ".$this->radek["colls"]." sloupcù)</td>
                        <td class=\"nazev\">".$this->radek["poznamka"]."</td>
                        <td class=\"menu\">
                        <a href=\"".$adresa_modulu."?id_serial=".$this->id_serialu."&id_zajezd=".$this->id_zajezdu."&id_topologie=".$this->radek["id_topologie"]."&id_tok_topologie=".$this->radek["id_tok_topologie"]."&amp;typ=topologie&amp;pozadavek=add_existing\">pøiøadit k zájezdu</a>";
                    return $vypis;
                }     
	}	
	
} 

?>
