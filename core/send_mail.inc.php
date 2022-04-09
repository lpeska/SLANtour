<?php
/** 
send_mail.inc.php - trida pro odesilani e-mailu na zaklade pozadavku
*/

/*--------------------- UZIVATEL ----------------------------*/
final class Send_mail{	
	static $hlaska_osobni_udaje = "<p><i>Zasláním uvedených informací souhlasíte s jejich zpracováním a archivací cestovní kanceláří pro účely zpracování Vašich objednávek dle zákona č. 101/2000 Sb. o ochraně osobních údajů. <br/>Data nebudou poskytnuta třetí straně.</i></p>";
  	
	static $hlaska_rezervace_volno = "Vaše objednávka zájezdu byla přijata do systému. Služby, o které jste projevil(a) zájem jsou volné a byly zarezervovány.";
	static $hlaska_rezervace_na_dotaz = "Vaše objednávka zájezdu byla přijata do systému. Dostupnost některých služeb o které jste projevil(a) zájem jsou pouze \"na dotaz\" a tedy nebylo možné je ihned rezervovat. Pracovníci CK prověří jejich aktuální dostupnost a budou Vás dále informovat.";
	static $hlaska_rezervace_obsazeno = "Vaše objednávka zájezdu byla přijata do systému. Některé služby o které jste projevil(a) zájem jsou nyní vyprodány a tedy není možné je rezervovat. Pokud se požadované kapacity uvolní, budeme Vás informovat.";
	
	
	static function send($odesilatel_jmeno, $odesilatel_email, $prijemce_email, $predmet, $text){
		/*uprava vstupnich dat do bezpecne formy
		$typ_pozadavku = $this->check($typ_pozadavku);
		$odesilatel_jmeno = $this->check($odesilatel_jmeno);
		$odesilatel_email = $this->check($odesilatel_email);
		$prijemce_email = $this->check($prijemce_email);
		$predmet = $this->check($predmet);
		$text = $this->check_with_html($text);			
		*/

			//vytvoření hlavičky
			//úprava formátu příjemce
			$jmeno_w1250= imap_8bit( $odesilatel_jmeno );
			$jmeno_w1250 = "=?windows-1250?Q?".$jmeno_w1250."?="; 
			
			//úprava formátu předmetu
			$predmet_w1250 = imap_8bit($predmet); 
			$predmet_w1250 = "=?windows-1250?Q?".$predmet_w1250."?="; 
			
			//tvorba jednotlivých headerů		
			$headers = "From: ".$jmeno_w1250." <".$odesilatel_email.">\n"; 
			$headers .= "Mime-Version: 1.0\n";
			$headers .= "X-Mailer: PHP\n"; // mailový klient
			$headers .= "X-Priority: 3\n"; // normální vzkaz!
			$headers .= "Content-Type: text/html; charset=windows-1250"; 			
			
			if( mail($prijemce_email, $predmet_w1250, $text, $headers) ){
				return true;
			}else{
				return false;
			}
		
	}
	static function get_hlaska_registrace(){ return self::$hlaska_registrace;	}	
	static function get_hlaska_rezervace_volno(){ return self::$hlaska_rezervace_volno;	}	
	static function get_hlaska_rezervace_na_dotaz(){ return self::$hlaska_rezervace_na_dotaz;	}	
	static function get_hlaska_rezervace_obsazeno(){ return self::$hlaska_rezervace_obsazeno;	}	
					
	
}




?>
