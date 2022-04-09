<?php
/** 
* trida pro zobrazeni konkretni klientovy objednavky zajezdu 
*/

/*--------------------- SERIAL -------------------------------------------*/
class Pdf_objednavka_prepare_vstupenka extends Rezervace_zobrazit{
	//vstupni data
	protected $security_code;	
	protected $celkova_cena;


	
//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì id objednávky*/
	function __construct($id_objednavka, $security_code){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//kontrola vstupnich dat
		$this->id_objednavka = $this->check_int($id_objednavka);
		$this->security_code = $this->check($security_code);
		
			//ziskani seznamu z databaze	
			$data_objednavka =  $this->database->query($this->create_query("get_objednavka") ) 
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$pocet_objednavek = mysqli_num_rows($data_objednavka);					
			
			
		//zjistuju, zda mam neco k zobrazeni
		if($pocet_objednavek==0){
				$this->chyba("Nemáte pøístup k dané objednávce!");
		}		


	}	
          function get_celkova_cena(){
             return $this->celkova_cena;
         }       
        function get_kategorie($kat="") {
            if($kat==""){$kat=$this->radek["kategorie"];}
            switch ($kat) {
                case "1":
                   return "AA"; break;
                case "2":
                    return "A"; break;
                case "3":
                    return "B"; break;
                case "4":
                    return "C"; break;
                case "5":
                    return "D"; break;
                case "6":
                    return "E"; break;
            }

         }
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="get_objednavka"){
			$dotaz= "SELECT * FROM `objednavka` 
						WHERE `id_objednavka`=".$this->id_objednavka." and `security_code`='".$this->security_code."'
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show_objednavka"){
			$dotaz= "SELECT * FROM `objednavka` 
						WHERE `id_objednavka`=".$this->id_objednavka." and `security_code`='".$this->security_code."'
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku=="select_klient"){
			$dotaz= "SELECT * FROM `user_klient` 
						WHERE `id_klient`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;      
								
		}else if($typ_pozadavku == "get_vstupenky"){
                                $dotaz ="select distinct 
                                    `vstupenka`.*,
                                    `objednavka_vstupenky`.*
				from `objednavka_vstupenky` join
                                        `vstupenka` on (`objednavka_vstupenky`.`id_vstupenky` = `vstupenka`.`id_vstupenky` and 
                                                         `objednavka_vstupenky`.`kategorie` = `vstupenka`.`kategorie`   )

                                where `objednavka_vstupenky`.`id_objednavka`=".$this->id_objednavka." 
                                order by `vstupenka`.`datum`, `vstupenka`.`cas_od`, `vstupenka`.`id_vstupenky`,`vstupenka`.`kategorie` ";

			//echo $dotaz;
			return $dotaz;
 

                        
		}else if($typ_pozadavku=="select_zajezd"){
			$dotaz= "SELECT `serial`.`id_serial`,`serial`.`nazev`,`serial`.`doprava`,`serial`.`strava`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,`ubytovani`.`nazev` as `nazev_zajezdu` 
						FROM `serial` JOIN  `zajezd` ON (`serial`.`id_serial` = `zajezd`.`id_serial`)
                                                              JOIN  `ubytovani` ON (`ubytovani`.`id_ubytovani` = `zajezd`.`id_ubytovani`)
						WHERE `serial`.`id_serial`=".$this->id_serial." and `zajezd`.`id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
                        
		}else if($typ_pozadavku=="select_ceny"){
			$dotaz= "SELECT `cena`.`id_cena`,`cena`.`nazev_ceny`,`cena`.`zakladni_cena`,`cena`.`use_pocet_noci`,`cena_zajezd`.`castka`,`cena_zajezd`.`mena`,`objednavka_cena`.`pocet` 
						FROM `serial` 
							JOIN  `cena` ON (`serial`.`id_serial` = `cena`.`id_serial`)
							JOIN  `cena_zajezd` ON (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
							JOIN `objednavka_cena` ON (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `objednavka_cena`.`id_objednavka`=".$this->id_objednavka.")
						WHERE `serial`.`id_serial`=".$this->id_serial." and `cena_zajezd`.`id_zajezd`=".$this->id_zajezd." and `objednavka_cena`.`pocet`>0
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
                } else if ($typ_pozadavku == "select_slevy") {
                    $dotaz = "select `objednavka_sleva`.*
					from `objednavka_sleva` 
					where `id_objednavka`=" . $this->id_objednavka . "
					order by `objednavka_sleva`.`velikost_slevy` desc
					";        
		} else if ($typ_pozadavku == "get_centralni_data") {
                    $dotaz = "SELECT * FROM `centralni_data` 
						WHERE `nazev` like \"hlavicka:%\"
			";
                    //echo $dotaz;
                    return $dotaz; 												
		} else if ($typ_pozadavku == "select_agentura") {
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
                    //echo $dotaz;
                    return $dotaz;
                }
	}	


function calculate_prize($castka, $pocet, $pocet_noci, $use_pocet_noci=0){	  
    //dummy
	 if($pocet_noci==0){
	 	$pocet_noci=1;
	 }
	 if($use_pocet_noci!=0){
    	$this->celkova_cena = $this->celkova_cena + ($castka*$pocet*$pocet_noci);
    	return $castka*$pocet*$pocet_noci;	 
	 }else{
    	$this->celkova_cena = $this->celkova_cena + ($castka*$pocet);
    	return $castka*$pocet;	 
	 }
  }
function doprava($doprava){  
 switch ($doprava) {
     case 1:
         return "Vlastní doprava";
         break;
     case 2:
         return "Autokar";
         break;
     case 3:
         return "Letecky";
         break;
 }	
}

function strava($strava){  
 switch ($strava) {
     case 1:
         return "Bez stravy";
         break;
     case 2:
         return "Snídanì";
         break;
     case 3:
         return "Polopenze";
         break;
     case 4:
         return "Plná penze";
         break;
     case 5:
         return "All inclusive";
         break;
 }	
}
	/**zobrazeni informaci o objednávce*/
	function create_pdf_objednavka(){
		if(!$this->get_error_message() ){
		  
			$objednavka = mysqli_fetch_array( $this->database->query($this->create_query("show_objednavka") ) )
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			$this->id_serial = $objednavka["id_serial"];
			$this->id_zajezd = $objednavka["id_zajezd"];
			$this->id_klient = $objednavka["id_klient"];
			
			$klient = mysqli_fetch_array( $this->database->query($this->create_query("select_klient") ) )
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
			
			$zajezd = mysqli_fetch_array( $this->database->query($this->create_query("select_zajezd") ) )
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );				
				
			$ceny = $this->database->query($this->create_query("select_ceny") ) 
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                        
                        $slevy = $this->database->query($this->create_query("select_slevy") ) 
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

                        $data_vstup_k_doobjednani = $this->database->query($this->create_query("get_vstupenky") ) 
			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
                        
			$platby ="" ; 
                        /*$this->database->query($this->create_query("select_platby") ) 
		 		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		*/
				

			$this->celkova_cena=0;
                       // echo "after osoby";

      $seznam_osob="";
			

                //vyhrazeni kapacity cen
		while($vstupenky = mysqli_fetch_array( $data_vstup_k_doobjednani ) ){
                        $cislo_vstupenky = false;

                        $pocet = $vstupenky[ "pocet" ];
			//vycleneni kapacit provadim pouze pro specifikovane ceny
	
				if($pocet!=0){
					//pridam do celkove ceny
                                        $cena = $vstupenky["cena"]+300;
					$cena_sluzby = $this->calculate_prize($cena,$pocet);

                                        $kategorie =  $this->get_kategorie($vstupenky["kategorie"]);
					//upravim textovou informaci o objednavanych kapacitach (do e-mailu)

                                        
					$this->text_vstupenky_klient .="<tr>
										<td class=\"border2l borderDotted\" colspan=\"4\"> ".$this->change_date_en_cz($vstupenky["datum"]).": ".$vstupenky["sport"]." (".$vstupenky["kod_souteze"]."), kategorie: ".$kategorie."</td>
                                                                                <td class=\"border2l borderDotted\" align=\"right\">".$cena." Kè</td>
                                                                                <td class=\"border2l borderDotted\" align=\"right\">".$pocet."</td>
                                                                                <td class=\"border2l border2r borderDotted\" align=\"right\">".$cena_sluzby." Kè</td>
									</tr>";
					
				}
			
		}//end while   
                        
		$seznam_cen="";
		while($cena = mysqli_fetch_array($ceny)){
                       // echo $cena_navic."-".$cena["castka"];
                            if($cena["zakladni_cena"]==1){
                                $cena["castka"] += $cena_navic;
                            }
					$seznam_cen=$seznam_cen."					
							<tr>	
								<td  class=\"border2l borderDotted\" colspan=\"4\">".$cena["nazev_ceny"]."</td>
                <td class=\"border2l borderDotted\" align=\"right\">".($cena["castka"])." ".$cena["mena"]."</td>
                <td  class=\"border2l borderDotted\" align=\"right\">".$cena["pocet"]."</td>
                <td class=\"border2l border2r borderDotted\" align=\"right\">".$this->calculate_prize($cena["castka"],$cena["pocet"],$objednavka["pocet_noci"],$cena["use_pocet_noci"])." ".$cena["mena"]."</td>								
							</tr>		
					";
			}
                 $seznam_cen=$seznam_cen."<tr>        
                        <td  class=\"border2l borderDotted\" colspan=\"4\"><b>Balíèky vstupenka + ÈOD + doruèení:</b></td>
                        <td class=\"border2l borderDotted\" align=\"right\"></td>
                        <td  class=\"border2l borderDotted\" align=\"right\"></td>
                        <td class=\"border2l border2r borderDotted\" align=\"right\"></td>
                      </tr>  
                ";
                 $seznam_cen .= $this->text_vstupenky_klient;        
               
                        
                        
                        
            while ($row_slevy = mysqli_fetch_array($slevy)) {
                $seznam_cen = $seznam_cen . "	
					<tr>	
					 <td  class=\"border2l borderDotted\" colspan=\"4\">" . $row_slevy["nazev_slevy"] . "</td>
                <td class=\"border2l borderDotted\" align=\"right\">" . $row_slevy["velikost_slevy"] . " ".$row_slevy["mena"] ."</td>
                <td  class=\"border2l borderDotted\" align=\"right\"></td>
                <td class=\"border2l border2r borderDotted\" align=\"right\">- " . $row_slevy["castka_slevy"] . " Kè</td>								
					</tr>		
					";
                $this->celkova_cena = $this->celkova_cena - $row_slevy["castka_slevy"];
            }        
                   /*     
			if($objednavka["velikost_slevy"]!=""){
				$seznam_cen=$seznam_cen."	
					<tr>	
					 <td  class=\"border2l borderDotted\" colspan=\"4\">".$objednavka["nazev_slevy"]."</td>
                <td class=\"border2l borderDotted\" align=\"right\">".$objednavka["castka_slevy"]."</td>
                <td  class=\"border2l borderDotted\" align=\"right\"></td>
                <td class=\"border2l border2r borderDotted\" align=\"right\">- ".$objednavka["velikost_slevy"]." Kè</td>								
					</tr>		
					";
				$this->celkova_cena = $this->celkova_cena - 	$objednavka["velikost_slevy"];
			}*/
			//echo $seznam_cen;
			
				$text_agentura=	"";
			
			$text_objednavka="

			
			
&lt;!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\"&gt;
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=windows-1250\" />
<style>
*{
	font-family: DejaVuSans, Helvetica, Arial,  sans-serif;
	font-size: 7pt;
	margin: 0;
	padding: 0;
	
}
body{
	font-family: DejaVuSans, Helvetica, Arial,  sans-serif;
	font-size: 7pt;
}
table{
	font-size: 7pt;
	margin-top:-3px;
	margin-bottom:-3px;
}
td{
	padding-left:8px;
	padding-right:8px;
}
th{
	padding-left:8px;
	padding-right:8px;
}
.content table{
	font-size: 7pt;
}


.border2{
	border: 2px solid #101010; 
	padding:3px 8px 3px 8px;
}
.border2l{
	border-left: 2px solid #101010; 
	padding-left:8px;
	padding-right:8px;
}
.border2r{
	border-right: 2px solid #101010; 
	padding-right:8px;
	padding-left:8px;
}
.border2t{
	border-top: 2px solid #101010; 
	padding-top:3px;
}
.border2b{
	border-bottom: 2px solid #101010; 
	padding-bottom:3px;
}

.border1{
	border: 1px solid #101010; 
	padding:3px 10px 3px 10px;
}

.border1b{
	border-bottom: 1px solid #101010; 
	padding-bottom:3px; 
  padding-top:4px;
}

.borderDotted{
	border-bottom: 1px dotted #101010; 
	padding-bottom:3px; 
  padding-top:3px;
}

.content p{
	margin-left:20px;
	font-weight: bold;
	clear:left;
}
.content table{
	margin-left:20px;
	clear:left;
}
.content table td,
.content table th{
	padding-right:20px;
}
h1{
	font-size: 2.6em;
}
h2{
	font-size: 1.4em;
}


</style>
	<title>Objednávka</title>
</head>

<body>
<table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
	<tr>
	 <th colspan=\"3\" style=\"padding-left:0;padding-right:0;\">
   			<h1>OBJEDNÁVKA SLUŽEB</h1>
    </th>
  </tr>
  <tr>
    <th colspan=\"3\" align=\"right\" style=\"padding-right:20px;\">   			
			<h3>uzavøená ve smyslu zákona è. 159/1999 Sb.</h3>
   </th>
	</tr>
  
  <tr>	   
		<td class=\"border2\" valign=\"top\" rowspan=\"2\">
	      <h2>".$this->centralni_data["nazev_spolecnosti"]."</h2>
	     <p style=\"font-size:1.1em;\">
      	".$this->centralni_data["adresa"]."<br/>
				".$this->centralni_data["firma_zapsana"]."<br/>
				tel.: ".$this->centralni_data["telefon"]."<br/>
				IÈO: ".$this->centralni_data["ico"]." DIÈ: ".$this->centralni_data["dic"]."<br/>
				Bankovní spojení: ".$this->centralni_data["bankovni_spojeni"]."<br/>
				e-mail: ".$this->centralni_data["email"].", web: ".$this->centralni_data["web"]."<br/>	    
       </p> 
		</td>	
		
		<td rowspan=\"2\"  class=\"border2\" valign=\"top\"  width=\"280\" >
      <h2>Obchodní zástupce - prodejce</h2> 
		".$text_agentura."
    </td>	
        
 		<td lign=\"center\" valign=\"top\" width=\"180\">
			<img src=\"https://www.slantour.cz/pix/logo_slantour.gif\" width=\"150\" height=\"83\" /><br/>
			<b>ÈÍSLO SMLOUVY - REZERVACE:</b>
		</td>
	</tr>
  <tr>
    <td class=\"border2\"  align=\"center\">		
      <b>".$this->id_objednavka."
      </b>
		</td>    	
	</tr>
</table>  
<table cellpadding=\"0\" cellspacing=\"8\"  width=\"810\">
	<tr>
		<td class=\"border2 content\" valign=\"top\">
			<h3>ZÁKAZNÍK - OBJEDNAVATEL</h3>
			<table width=\"100%\">
				<tr>
					<td><strong>".$klient["titul"]." ".$klient["jmeno"]." ".$klient["prijmeni"]." </strong></td> <td><b>e-mail:</b> ".$klient["email"]."</td> <td><b>tel.:</b> ".$klient["telefon"]."</td>
				</tr>
				<tr>
					<td colspan=\"2\"><b>Adresa:</b> ".$klient["ulice"].", ".$klient["psc"].", ".$klient["mesto"]."</td><td><b>RÈ/datum nar.:</b> ".(($klient["rodne_cislo"]!="")?($klient["rodne_cislo"]):($this->change_date_en_cz($klient["datum_narozeni"])))."</td>
				</tr>			        					
			</table>
		</td>				
	</tr>
</table>


	<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
	<tr>
		<td class=\"border2\" valign=\"top\" colspan=\"4\"  style=\"width:60%\">
			<h3>SLUŽBY</h3>
			<strong>".$zajezd["nazev"]."</strong>
		</td>
		<td nowrap class=\"border2\" valign=\"bottom\" align=\"right\" colspan=\"2\"  width=\"150\"><b>TERMÍN:</b> ".(($objednavka["termin_od"]!="0000-00-00" and $objednavka["termin_od"]!="")?($this->change_date_en_cz($objednavka["termin_od"])." - ".$this->change_date_en_cz($objednavka["termin_do"])):($this->change_date_en_cz($zajezd["od"])." - ".$this->change_date_en_cz($zajezd["do"])))."</td>
		<td nowrap class=\"border2\" valign=\"bottom\" align=\"right\" width=\"100\"><b>POÈET NOCÍ:</b> ".$objednavka["pocet_noci"]."</td>
	</tr>
  <tr>
		<th colspan=\"4\" class=\"border2l border1b\" align=\"left\"  width=\"540\">Název služby</th>
    <th align=\"right\" class=\"border2l border1b\" width=\"100\">Cenový rozpis</th>
    <th align=\"right\" class=\"border2l border1b\">Poèet</th>
    <th align=\"right\" class=\"border2l border2r border1b\">Celkem</th>
	</tr>
	".$seznam_cen." 
						
							<tr>
							  <th align=\"left\" class=\"border2\">Platba zájezdu</th>
							  <th align=\"left\" class=\"border2\">Èástka</th>
							  <th align=\"left\" class=\"border2\">Datum úhrady</th>
							  <th align=\"left\" class=\"border2\">Zpùsob úhrady</th>							
								<th colspan=\"2\"  class=\"border2l border2t border2b\" align=\"left\"><strong>Celková cena</strong></th>
                <th class=\"border2r border2t border2b\" align=\"right\"><strong>".$this->celkova_cena." Kè</strong></th>
							</tr>	
              																										
							<tr>
							  <td class=\"border2\">Záloha</td>
							  <td class=\"border2\"> </td>
							  <td class=\"border2\"> </td>
							  <td class=\"border2\"> </td>							
								<td colspan=\"3\" rowspan=\"3\"  class=\"border2\"  width=\"250\">
                  * Zákazník je povinen uhradit platbu CK. Úhradu provede sám (hotovì,
složenkou na úèet nebo adresu, bankovním pøevodem), nebo udìlí plnou moc
prodejci k provedení úhrady plateb. Za øádnou a vèasnou úhradu platby
odpovídá cestovní kanceláøi vždy zákazník.
                </td>
							</tr>						
							<tr>
							  <td class=\"border2\">Doplatek</td>
							  <td class=\"border2\"> </td>
							  <td class=\"border2\"> </td>
							  <td class=\"border2\"> </td>							
							</tr>	
							<tr>
							  <td class=\"border2\" height=\"18\"> </td>
							  <td class=\"border2\"> </td>
							  <td class=\"border2\"> </td>
							  <td class=\"border2\"> </td>							
							</tr>		

							<tr>
							  <td class=\"border2\" colspan=\"4\" rowspan=\"4\" valign=\"top\">
                  <h3>POZNÁMKY/UPOZORNÌNÍ</h3>
                  ".nl2br($objednavka["poznamky"])."
                 </td>
							  <td class=\"border2\" colspan=\"3\"> 
                  <h3>ÚDAJE CK</h3>
                </td>					
							</tr>	
							<tr>
							  <td class=\"border2\" colspan=\"2\"><b>Odeslání voucheru a pokynù</b></td>
							  <td class=\"border2\"> </td>					
							</tr>	
							<tr>
							  <td class=\"border2\" colspan=\"2\"><b>STORNO DNE</b></td>
							  <td class=\"border2\"> </td>					
							</tr>	
							<tr>
							  <td class=\"border2\" colspan=\"2\"><b>STORNO POPLATEK</b></td>
							  <td class=\"border2\"> </td>					
							</tr>	              							
							 		
</table>

	<table cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse: collapse;margin:8px;\" width=\"810\" >
	<tr>
		<td class=\"border2\" valign=\"top\" colspan=\"3\">
			<h3>PROHLÁŠENÍ ZÁKAZNÍKA</h3>
      Prohlašuji že souhlasím se Všeobecnými podmínkami úèasti na zájezdech, které jsou nedílnou souèástí této smlouvy a s ostatními podmínkami uvedenými v této 
smlouvì, a to i jménem výše uvedených osob, které mne k jejich pøihlášení a úèasti zmocnily.
		</td>				
	</tr>
	<tr>
		<td class=\"border2\" valign=\"top\" height=\"40\">
      <b>DATUM:</b>
		</td>	
		<td class=\"border2\" valign=\"top\">
      <b>Podpis zákazníka:</b>
		</td>		
		<td class=\"border2\" valign=\"top\">
      <b>Podpis CK (prodejce):</b>
		</td>		        			
	</tr>	
</table>
		
	
</body>
</html>	
		";
		
		
		
			$ret=$text_objednavka;	
                        //echo $ret;
			return $ret;											
		}else{
    return "";
    }
	}
	
} 




?>
