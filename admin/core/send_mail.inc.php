<?php
/** 
send_mail.inc.php - trida pro odesilani e-mailu na zaklade pozadavku
*/

/*--------------------- UZIVATEL ----------------------------*/
final class Send_mail{	
  	
	static $hlaska_registrace = "Tento e-mail Vám byl zaslán na základì vyplnìní registraèního formuláøe na adrese __.<br/>
		Pro potvrzení registrace do systému RSCK kliknìte na následující odkaz (kontrola, zda e-mail, který jste pøi registraci uvedl(a) je skuteènì Váš). Pozor, kód je platný pouze po následujících 48 hodin<br/>
		(pokud jste nic nevyplòoval(a), mùžete tento e-mail ignorovat)<br/><br/>";
	static $hlaska_rezervace_volno = "Vaše objednávka zájezdu byla pøijata do systému. Služby, o které jste projevil(a) zájem jsou volné a byly zarezervovány.";
	static $hlaska_rezervace_na_dotaz = "Vaše objednávka zájezdu byla pøijata do systému. Dostupnost nìkterých služeb o které jste projevil(a) zájem jsou pouze \"na dotaz\" a tedy nebylo možné je ihned rezervovat. Pracovníci CK provìøí jejich aktuální dostupnost a budou Vás dále informovat.";
	static $hlaska_rezervace_obsazeno = "Vaše objednávka zájezdu byla pøijata do systému. Nìkteré služby o které jste projevil(a) zájem jsou nyní vyprodány a tedy není možné je rezervovat. Pokud se požadované kapacity uvolní, budeme Vás informovat.";
	
	
	static function send($odesilatel_jmeno, $odesilatel_email, $prijemce_email, $predmet, $text){
		/*uprava vstupnich dat do bezpecne formy
		$typ_pozadavku = $this->check($typ_pozadavku);
		$odesilatel_jmeno = $this->check($odesilatel_jmeno);
		$odesilatel_email = $this->check($odesilatel_email);
		$prijemce_email = $this->check($prijemce_email);
		$predmet = $this->check($predmet);
		$text = $this->check_with_html($text);			
		*/

			//vytvoøení hlavièky
			//úprava formátu pøíjemce
			$jmeno_w1250= imap_8bit( $odesilatel_jmeno );
			$jmeno_w1250 = "=?windows-1250?Q?".$jmeno_w1250."?="; 
			
			//úprava formátu pøedmetu
			$predmet_w1250 = imap_8bit($predmet); 
			$predmet_w1250 = "=?windows-1250?Q?".$predmet_w1250."?="; 
			
			//tvorba jednotlivých headerù		
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
