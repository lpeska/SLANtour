<?
	if($vyprodano == 1){
		$pokyny_k_platbe = "Platbu za objedn�vku - v p��pad� uvoln�n� kapacity - prove�te podle dal��ch pokyn� pracovn�k� CK SLAN tour.";
	
	}else if($obsazena_kapacita == 1 or $na_dotaz == 1){
		$pokyny_k_platbe = "Dostupnost n�kter�ch slu�eb o kter� jste projevil(a) z�jem jsou pouze \"na dotaz\" a tedy nebylo mo�n� je ihned rezervovat. Pracovn�ci CK SLAN tour prov��� jejich aktu�ln� dostupnost a budou V�s d�le informovat.	
									<br/><strong>S platbou za objedn�vku vy�kejte a� na potvrzen� dostupnosti V�mi objednan�ch slu�eb.</strong>
									<br/><br/>
<b>Platbu za objedn�vku m��ete prov�st n�sleduj�c�mi zp�soby: </b><br/><br/>
<b>a) bankovn�m p�evodem</b><br/>
��slo ��tu: 19-6706930207 / 0100<br/>
variabiln� symbol (��slo objedn�vky) = ".$id_objednavka."<br/><br/>
<b>b) Po�tovn� pouk�zkou</b><br/>
Adresa pro zasl�n� p��slu�n� ��stky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slan�, 27401<br/><br/>		
<b>c) Hotov�</b> na n�kter� z pobo�ek CK SLAN tour - Praha, Slan�, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
Zp�sob a datum �hrady n�m, pros�m, avizujte.<br/>							
								";
								
	}else	if( $zajezd["dlouhodobe_zajezdy"] != "1" ){
		$odjezd = explode("-", $zajezd["od"]);
		$datum_suma = $odjezd[0]*365 + $odjezd[1]*30 + $odjezd[2];
		$dnes_suma = Date("Y")*365 + Date("m")*30 + Date("d");
	
		if( ($datum_suma - $dnes_suma) >= MAX_TERMIN_ZALOHA  ){
			$zaloha = round($this->celkova_cena * VYSE_ZALOHY);
			$pokyny_k_platbe = "Vzhledem k tomu, �e objedn�v�te z�jezd s odjezdem za v�ce ne� ".MAX_TERMIN_ZALOHA." dn�, je k dokon�en� Va�� rezervace t�eba uhradit z�lohu min. 30% z celkov� ceny objedn�vky.  <br/>
			Po�adovan� z�loha je: <strong>".$zaloha." K�.</strong><br/>
<b>V��e uvedenou ��stku za va�i objedn�vku uhra�te, pros�m, nejpozd�ji do 2 pracovn�ch dn�. </b><br/>
Uvedenou ��stku m��ete uhradit n�sleduj�c�mi zp�soby:<br/><br/>
 
<b>a) bankovn�m p�evodem</b><br/>
��slo ��tu: 19-6706930207 / 0100<br/>
variabiln� symbol (��slo objedn�vky) = ".$id_objednavka."<br/><br/>
 
<b>b) Po�tovn� pouk�zkou</b><br/>
Adresa pro zasl�n� p��slu�n� ��stky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slan�, 27401<br/><br/>
 
<b>c) Hotov�</b> na n�kter� z pobo�ek CK SLAN tour - Praha, Slan�, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
 
Zp�sob a datum �hrady n�m, pros�m, avizujte.<br/>
<strong>Doplatek do celkov� ceny objedn�vky je nutn� uhradit do 30 dn� p�ed odjezdem na z�jezd.	</strong><br/><br/>		
			";
		}else{
			$zaloha = $this->celkova_cena;
			$pokyny_k_platbe = "Vzhledem k tomu, �e objedn�v�te z�jezd s odjezdem za m�n� ne� ".MAX_TERMIN_ZALOHA." dn�, je k dokon�en� Va�� rezervace t�eba uhradit celkovou cenu objedn�vky, tj: <strong>".$zaloha." K�.</strong><br/>
<b>V��e uvedenou ��stku za va�i objedn�vku uhra�te, pros�m, nejpozd�ji do 2 pracovn�ch dn�. </b><br/>
Uvedenou ��stku m��ete uhradit n�sleduj�c�mi zp�soby:<br/><br/>
 
<b>a) bankovn�m p�evodem</b><br/>
��slo ��tu: 19-6706930207 / 0100<br/>
variabiln� symbol (��slo objedn�vky) = ".$id_objednavka."<br/><br/>
 
<b>b) Po�tovn� pouk�zkou</b><br/>
Adresa pro zasl�n� p��slu�n� ��stky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slan�, 27401<br/><br/>
 
<b>c) Hotov�</b> na n�kter� z pobo�ek CK SLAN tour - Praha, Slan�, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
 
Zp�sob a datum �hrady n�m, pros�m, avizujte.<br/><br/>			
			";
		}
	}else{//dlouhodobe zajezdy
		$pokyny_k_platbe = "Pracovn�ci CK SLAN tour prov��� aktu�ln� dostupnost V�mi po�adovan�ch slu�eb v dan�m term�nu a budou V�s d�le informovat.<br/>
							V��e uveden� cenov� �daje se mohou v konkr�tn�ch term�nech / d�lce pobyt� m�rn� li�it - po zpracov�n� Va�eho po�adavku V�s budeme informovat o celkov� cen� Va�� objedn�vky.
							<br/>S platbou za objedn�vku vy�kejte a� na potvrzen� dostupnosti V�mi objednan�ch slu�eb.
									<br/><br/>
<b>Platbu za objedn�vku m��ete prov�st n�sleduj�c�mi zp�soby: </b><br/><br/>
<b>a) bankovn�m p�evodem</b><br/>
��slo ��tu: 19-6706930207 / 0100<br/>
variabiln� symbol (��slo objedn�vky) = ".$id_objednavka."<br/><br/>
<b>b) Po�tovn� pouk�zkou</b><br/>
Adresa pro zasl�n� p��slu�n� ��stky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slan�, 27401<br/><br/>		
<b>c) Hotov�</b> na n�kter� z pobo�ek CK SLAN tour - Praha, Slan�, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
Zp�sob a datum �hrady n�m, pros�m, avizujte.<br/>									
								";
	}

?>