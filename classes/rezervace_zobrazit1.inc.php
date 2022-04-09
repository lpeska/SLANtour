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
	
	public $database; //trida pro odesilani dotazu		

	
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
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
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
			
			$text_objednavka = "
						<table class=\"rezervace\">
							<tr><th colspan=\"2\">Z�jezd: ".$zajezd["nazev"]." (".$this->change_date_en_cz($zajezd["od"])." - ".$this->change_date_en_cz($zajezd["do"]).") </th></tr>
							<tr><td>Po�et osob: </td><td>".$objednavka["pocet_osob"]."</td></tr>
							<tr><td>Datum rezervace: </td><td>".$this->change_date_en_cz( $objednavka["datum_rezervace"] )."		</td></tr>
							<tr><td>Opce do: </td><td>".$this->change_date_en_cz( ($objednavka["stav"] == 3 ? $objednavka["rezervace_do"] : "" ) )."</td></tr>
							<tr><td>Stav objedn�vky: </td><td>".Rezervace_library::get_stav( ($objednavka["stav"]-1) )."		</td></tr>							
							<tr><td>Celkov� cena: </td><td>".$objednavka["celkova_cena"]." K�</td></tr>
							<tr><td>Zb�v� zaplatit: </td><td>".$objednavka["zbyva_zaplatit"]." K�		</td></tr>				
							<tr><td>Pozn�mky: </td><td>".nl2br($objednavka["poznamky"])."</td></tr>
						</table>
				";
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
					";					
			}
			$vystup = "
				<div id=\"rezervace\">
					<h3>Objedn�vka</h3>
						".$text_objednavka."
					<h3>Objedn�van� slu�by</h3>
					<table class=\"rezervace_ceny\">
						<tr><th>N�zev slu�by</th><th>Cena</th><th>Po�et</th></tr>
						".$text_ceny."
					</table>						
					<h3>Rozpis plateb</h3>	
					<table class=\"rezervace_platby\">
						<tr><th>��stka</th><th>Datum vystaven�</th><th>Datum splatnosti</th><th>Splaceno</th></tr>
						".$text_platby."
					</table>
					<h3>Osoby</h3>		
					<table class=\"rezervace_osoby\">
						".$text_osoby."
					</table>	

				</div>";		
			return $vystup;											
		}
	}
	
} 




?>
