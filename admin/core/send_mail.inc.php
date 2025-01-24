<?php
/** 
send_mail.inc.php - trida pro odesilani e-mailu na zaklade pozadavku
*/

/*--------------------- UZIVATEL ----------------------------*/
final class Send_mail{	
  	
	static $hlaska_registrace = "Tento e-mail V�m byl zasl�n na z�klad� vypln�n� registra�n�ho formul��e na adrese __.<br/>
		Pro potvrzen� registrace do syst�mu RSCK klikn�te na n�sleduj�c� odkaz (kontrola, zda e-mail, kter� jste p�i registraci uvedl(a) je skute�n� V�). Pozor, k�d je platn� pouze po n�sleduj�c�ch 48 hodin<br/>
		(pokud jste nic nevypl�oval(a), m��ete tento e-mail ignorovat)<br/><br/>";
	static $hlaska_rezervace_volno = "Va�e objedn�vka z�jezdu byla p�ijata do syst�mu. Slu�by, o kter� jste projevil(a) z�jem jsou voln� a byly zarezervov�ny.";
	static $hlaska_rezervace_na_dotaz = "Va�e objedn�vka z�jezdu byla p�ijata do syst�mu. Dostupnost n�kter�ch slu�eb o kter� jste projevil(a) z�jem jsou pouze \"na dotaz\" a tedy nebylo mo�n� je ihned rezervovat. Pracovn�ci CK prov��� jejich aktu�ln� dostupnost a budou V�s d�le informovat.";
	static $hlaska_rezervace_obsazeno = "Va�e objedn�vka z�jezdu byla p�ijata do syst�mu. N�kter� slu�by o kter� jste projevil(a) z�jem jsou nyn� vyprod�ny a tedy nen� mo�n� je rezervovat. Pokud se po�adovan� kapacity uvoln�, budeme V�s informovat.";
	
	
	static function send($odesilatel_jmeno, $odesilatel_email, $prijemce_email, $predmet, $text){
		/*uprava vstupnich dat do bezpecne formy
		$typ_pozadavku = $this->check($typ_pozadavku);
		$odesilatel_jmeno = $this->check($odesilatel_jmeno);
		$odesilatel_email = $this->check($odesilatel_email);
		$prijemce_email = $this->check($prijemce_email);
		$predmet = $this->check($predmet);
		$text = $this->check_with_html($text);			
		*/

			//vytvo�en� hlavi�ky
			//�prava form�tu p��jemce
			$jmeno_w1250= imap_8bit( $odesilatel_jmeno );
			$jmeno_w1250 = "=?windows-1250?Q?".$jmeno_w1250."?="; 
			
			//�prava form�tu p�edmetu
			$predmet_w1250 = imap_8bit($predmet); 
			$predmet_w1250 = "=?windows-1250?Q?".$predmet_w1250."?="; 
			
			//tvorba jednotliv�ch header�		
			$headers = "From: ".$jmeno_w1250." <".$odesilatel_email.">\n"; 
			$headers .= "Mime-Version: 1.0\n";
			$headers .= "X-Mailer: PHP\n"; // mailov� klient
			$headers .= "X-Priority: 3\n"; // norm�ln� vzkaz!
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
