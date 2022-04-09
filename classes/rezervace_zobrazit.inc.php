<?php
/** 
* trida pro zobrazeni konkretni klientovy objednavky zajezdu 
*/

/*--------------------- SERIAL -------------------------------------------*/
class Rezervace_zobrazit extends Generic_data_class{
	//vstupni data
	protected $id_objednavka;	
	protected $id_klient;	
	protected $id_serial;	
	protected $id_zajezd;		
	protected $id_agentury;	
	protected $database; //trida pro odesilani dotazu

	
//------------------- KONSTRUKTOR -----------------
	/**konstruktor t��dy na z�klad� id objedn�vky*/
	function __construct($id_objednavka	){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//kontrola vstupnich dat
		$this->id_objednavka = $this->check_int($id_objednavka);
		$uzivatel = User::get_instance();	
		$this->id_klient = $uzivatel->get_id();		

		//pokud mam dostatecna prava pokracovat
		if( $uzivatel->get_correct_login() ){			
			//ziskani seznamu z databaze	
			$data_objednavka =  $this->database->query($this->create_query("show_objednavka") ) 
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$pocet_objednavek = mysqli_num_rows($data_objednavka);					
			
			//zjistuju, zda mam neco k zobrazeni
			if($pocet_objednavek==0){
				$this->chyba("Nem�te p��stup k dan� objedn�vce!");
			}		
		}else{
			$this->chyba("Nejste p�ihl�en!");		
		}


	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show_objednavka"){
			$dotaz= "SELECT * FROM `objednavka` 
						WHERE `id_objednavka`=".$this->id_objednavka." and `id_agentury`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="select_zajezd"){
			$dotaz= "SELECT `serial`.`id_serial`,`serial`.`nazev`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do` 
						FROM `serial` JOIN  `zajezd` ON (`serial`.`id_serial` = `zajezd`.`id_serial`)
						WHERE `serial`.`id_serial`=".$this->id_serial." and `zajezd`.`id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="select_ceny"){
			$dotaz= "SELECT `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet` 
						FROM `serial` 
							JOIN  `cena` ON (`serial`.`id_serial` = `cena`.`id_serial`)
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena` and `cena_zajezd`.`nezobrazovat`!=1)
							LEFT JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=".$this->id_objednavka.")
						WHERE `serial`.`id_serial`=".$this->id_serial." and `cena_zajezd`.`id_zajezd`=".$this->id_zajezd." 
						";
			//echo $dotaz;
			return $dotaz;														
		}else if($typ_pozadavku=="select_platby"){
			$dotaz= "select `objednavka`.`mena`, `objednavka`.`id_objednavka`,`objednavka_platba`.`id_platba`,`objednavka_platba`.`castka`, 
						`objednavka_platba`.`vystaveno`,`objednavka_platba`.`splatit_do`, `objednavka_platba`.`splaceno`
					from `objednavka_platba` 
					join `objednavka` on ( `objednavka_platba`.`id_objednavka` = `objednavka`.`id_objednavka` )
					where `objednavka`.`id_objednavka`=".$this->id_objednavka."
					order by `objednavka_platba`.`splaceno`
					";
			//echo $dotaz;
			return $dotaz;														
		}else if($typ_pozadavku=="select_osoby"){
			$dotaz="select `objednavka_osoby`.`id_objednavka`,`objednavka_osoby`.`id_klient`,`jmeno`,`prijmeni`,`titul`,
								`email`,`telefon`,`datum_narozeni`,`rodne_cislo`,`cislo_pasu`,`cislo_op`,`ulice`,`mesto`,`psc`
					  from 	`objednavka_osoby`
					  			JOIN `user_klient` ON (`objednavka_osoby`.`id_klient`=`user_klient`.`id_klient`)
								WHERE `objednavka_osoby`.`id_objednavka` = ".$this->id_objednavka."
					  order by `prijmeni`, `jmeno`,`datum_narozeni` ";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="select_klient"){
			$dotaz= "SELECT `objednavka`.`id_klient`,`jmeno`,`prijmeni`,`datum_narozeni`,`email`,`ulice`,`mesto`,`psc`       
					  from 	`objednavka`
					  			JOIN `user_klient` ON (`objednavka`.`id_klient`=`user_klient`.`id_klient`)
								WHERE `objednavka`.`id_objednavka` = ".$this->id_objednavka."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;																	
		}else if($typ_pozadavku=="show_agentura"){
                        $dotaz = "SELECT `objednavka`.`id_objednavka`,`objednavka`.`id_agentury`,`organizace`.`nazev` as `nazev_agentury`,`organizace`.`ico`,
                                        `organizace_email`.`email`,`organizace_email`.`poznamka` as `kontaktni_osoba`,
                                        `organizace_telefon`.`telefon`,
                                        `stat`,`mesto`,`ulice`,`psc`,
                                        `nazev_banky`,`kod_banky`,`cislo_uctu`
                                    from `objednavka` 
                                     join `organizace` on (`organizace`.`id_organizace` = `objednavka`.`id_agentury`)
                                     left join `organizace_adresa` on (`organizace`.`id_organizace` = `organizace_adresa`.`id_organizace` and `organizace_adresa`.`typ_kontaktu` = 1) 
                                     left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0) 
                                     left join `organizace_telefon` on (`organizace`.`id_organizace` = `organizace_telefon`.`id_organizace` and `organizace_telefon`.`typ_kontaktu` = 0) 
                                     left join `organizace_www` on (`organizace`.`id_organizace` = `organizace_www`.`id_organizace` and `organizace_www`.`typ_kontaktu` = 0) 
                                     left join `organizace_bankovni_spojeni` on (`organizace`.`id_organizace` = `organizace_bankovni_spojeni`.`id_organizace` and `organizace_bankovni_spojeni`.`typ_kontaktu` = 1) 
				WHERE `objednavka`.`id_objednavka`=".$this->id_objednavka."
				LIMIT 1";

			return $dotaz;															
		}
	}	
	
	
	/**zobrazeni informaci o objednan�ch slu�b�ch*/
	function show_ceny_form(){
		//zobrazeni seznamu cen, pouziju tridu Rezervace_cena
		$ceny = new Rezervace_cena("new", $this->id_zamestnance, "", $this->id_serial, $this->id_zajezd);
		return "<h3>Objedn�vka slu�eb/cen</h3>
				".$ceny->show_form();
	
	}		
	/**zobrazeni informaci o objedn�vce*/
	function show_objednavka(){
		if(!$this->get_error_message() ){
			$objednavka = mysqli_fetch_array( $this->database->query($this->create_query("show_objednavka") ) )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				
			$klient = mysqli_fetch_array( $this->database->query($this->create_query("select_klient") ) )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				
			if($objednavka["id_agentury"]!=""){
			$this->id_agentury = $objednavka["id_agentury"];
				$agentura = mysqli_fetch_array( $this->database->query($this->create_query("show_agentura") ) )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			}
				
			$this->id_serial = $objednavka["id_serial"];
			$this->id_zajezd = $objednavka["id_zajezd"];
			
			$zajezd = mysqli_fetch_array( $this->database->query($this->create_query("select_zajezd") ) )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );				
				
			$ceny = $this->database->query($this->create_query("select_ceny") ) 
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			$platby =  $this->database->query($this->create_query("select_platby") ) 
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			$osoby =  $this->database->query($this->create_query("select_osoby") )
		 		or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );				
			if($objednavka["suma_provize"]){
					$provize="	<tr><td>Provize: </td><td><b>".$objednavka["suma_provize"]."</b>, ".(($objednavka["provize_vc_dph"]==1)?("v�etn� DPH"):("bez DPH")).",<br/>
											Pozn�mky: ".$objednavka["poznamka_provize"]."</td></tr>";

			}else{
					$provize="<tr><td>Provize: </td><td>dosud nebyla vypln�na</td></tr>";
			}
			$text_objednavka = "
						<table class=\"rezervace\">
							<tr><th colspan=\"2\">Z�jezd: ".$zajezd["nazev"]." (".$this->change_date_en_cz($zajezd["od"])." - ".$this->change_date_en_cz($zajezd["do"]).") </th></tr>
							<tr><td>Klient: </td><td><strong>".$klient["prijmeni"]." ".$klient["jmeno"]."</strong>, ".$this->change_date_en_cz($klient["datum_narozeni"])."; ".$klient["mesto"].", ".$klient["ulice"].", ".$klient["psc"]."</td></tr>
							<tr><td>Objedn�vaj�c� agentura: </td><td>".$agentura["nazev_agentury"].", ".$agentura["ico"]."</td></tr>
							<tr><td>��slo objedn�vky: </td><td>".$objednavka["id_objednavka"]."</td></tr>
							<tr><td>Po�et osob: </td><td>".$objednavka["pocet_osob"]."</td></tr>
							<tr><td>Datum rezervace: </td><td>".$this->change_date_en_cz( $objednavka["datum_rezervace"] )."		</td></tr>
							<tr><td>Celkov� cena: </td><td>".$objednavka["celkova_cena"]." K�</td></tr>			
							<tr><td>Pozn�mky: </td><td>".nl2br($objednavka["poznamky"])."</td></tr>
							
							<tr><td>Slevy: </td><td>".$objednavka["nazev_slevy"].", ".$objednavka["castka_slevy"].",<br/>
															celkov� ��stka: <b>".$objednavka["velikost_slevy"]." K�</b></td></tr>
							".$provize."
						</table>
				";
				/*
							<tr><td>Opce do: </td><td>".$this->change_date_en_cz( ($objednavka["stav"] == 3 ? $objednavka["rezervace_do"] : "" ) )."</td></tr>
							<tr><td>Stav objedn�vky: </td><td>".Rezervace_library::get_stav( ($objednavka["stav"]-1) )."		</td></tr>							
							<tr><td>Zb�v� zaplatit: </td><td>".$objednavka["zbyva_zaplatit"]." K�		</td></tr>	
				
				*/
			$text_platby="";
			$text_osoby="";
			$text_ceny="";
			while($cena = mysqli_fetch_array($ceny)){
					$text_ceny=$text_ceny."
						<tr><td>".$cena["nazev_ceny"]."</td><td>".$cena["castka"]." ".$cena["mena"]."</td><td>".$cena["pocet"]."</td></tr>
					";
			}
			while($platba = mysqli_fetch_array($platby)){
					$splaceno = $platba["splaceno"];
					if($splaceno == "0000-00-00"){
						$splaceno = "";
					}
					$text_platby=$text_platby."
						<tr><td>".$platba["castka"]." K�</td><td>".$this->change_date_en_cz($platba["vystaveno"])."</td><td>".$this->change_date_en_cz($platba["splatit_do"])."</td><td>".$this->change_date_en_cz($splaceno)."</td></tr>
					";			
			}	
			$i=0;					
			while($osoba = mysqli_fetch_array($osoby)){
				$i++;		
				$text_osoby=$text_osoby."
				<table class=\"rezervace_osoby\" style=\"float:left;\">
					<tr><th align=\"left\">Osoba �. ".$i."</th></tr>
					<tr><td> id:".$osoba["id_klient"]."</td></tr> 
					<tr><td> jm�no a p��jmen�: <strong>".$osoba["titul"]." ".$osoba["jmeno"]." ".$osoba["prijmeni"]."</strong></td></tr>
					<tr><td> datum narozen�: ".$this->change_date_en_cz( $osoba["datum_narozeni"])."</td></tr>
					<tr><td> rodn� ��slo: ".$osoba["rodne_cislo"]."</td></tr>
					<tr><td> e-mail: ".$osoba["email"]."</td></tr>
					<tr><td> telefon: ".$osoba["telefon"]."</td></tr>
					<tr><td> ��slo pasu: ".$osoba["cislo_pasu"]."</td></tr>
					<tr><td> ��slo op: ".$osoba["cislo_op"]."</td></tr>
					<tr><td> adresa: ".$osoba["mesto"].", ".$osoba["ulice"].", ".$osoba["psc"]."</td></tr>		
				</table>					
					";					
			}
			$vystup = "
				<div id=\"rezervace\">
					<h2><a href=\"https://www.slantour.cz/pdf_objednavka.php?id_objednavka=".$objednavka["id_objednavka"]."&amp;security_code=".$objednavka["security_code"]."\" target=\"_blank\">Zobrazit PDF cestovn� smlouvu</a></h2>
					<h3>Objedn�vka</h3>
						".$text_objednavka."
					<h3>Objedn�van� slu�by</h3>
					<table class=\"rezervace_ceny\">
						<tr><th>N�zev slu�by</th><th>Cena</th><th>Po�et</th></tr>
						".$text_ceny."
					</table>	
				
					
					<h3>Osoby</h3>		
					
						".$text_osoby."
						
					<div style=\"clear:left;\">&nbsp;</div>
				</div>";		
			return $vystup;	
					/*					"<h3>Rozpis plateb</h3>	
					<table class=\"rezervace_platby\">
						<tr><th>��stka</th><th>Datum vystaven�</th><th>Datum splatnosti</th><th>Splaceno</th></tr>
						".$text_platby."
					</table>" */														
		}
	}
	
} 




?>
