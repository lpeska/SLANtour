<?
	if($vyprodano == 1){
		$pokyny_k_platbe = "Platbu za objednávku - v pøípadì uvolnìní kapacity - proveïte podle dalších pokynù pracovníkù CK SLAN tour.";
	
	}else if($obsazena_kapacita == 1 or $na_dotaz == 1){
		$pokyny_k_platbe = "Dostupnost nìkterých služeb o které jste projevil(a) zájem jsou pouze \"na dotaz\" a tedy nebylo možné je ihned rezervovat. Pracovníci CK SLAN tour provìøí jejich aktuální dostupnost a budou Vás dále informovat.	
									<br/><strong>S platbou za objednávku vyèkejte až na potvrzení dostupnosti Vámi objednaných služeb.</strong>
									<br/><br/>
<b>Platbu za objednávku mùžete provést následujícími zpùsoby: </b><br/><br/>
<b>a) bankovním pøevodem</b><br/>
èíslo úètu: 19-6706930207 / 0100<br/>
variabilní symbol (èíslo objednávky) = ".$id_objednavka."<br/><br/>
<b>b) Poštovní poukázkou</b><br/>
Adresa pro zaslání pøíslušné èástky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slaný, 27401<br/><br/>		
<b>c) Hotovì</b> na nìkteré z poboèek CK SLAN tour - Praha, Slaný, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
Zpùsob a datum úhrady nám, prosím, avizujte.<br/>							
								";
								
	}else	if( $zajezd["dlouhodobe_zajezdy"] != "1" ){
		$odjezd = explode("-", $zajezd["od"]);
		$datum_suma = $odjezd[0]*365 + $odjezd[1]*30 + $odjezd[2];
		$dnes_suma = Date("Y")*365 + Date("m")*30 + Date("d");
	
		if( ($datum_suma - $dnes_suma) >= MAX_TERMIN_ZALOHA  ){
			$zaloha = round($this->celkova_cena * VYSE_ZALOHY);
			$pokyny_k_platbe = "Vzhledem k tomu, že objednáváte zájezd s odjezdem za více než ".MAX_TERMIN_ZALOHA." dní, je k dokonèení Vaší rezervace tøeba uhradit zálohu min. 30% z celkové ceny objednávky.  <br/>
			Požadovaná záloha je: <strong>".$zaloha." Kè.</strong><br/>
<b>Výše uvedenou èástku za vaši objednávku uhraïte, prosím, nejpozdìji do 2 pracovních dní. </b><br/>
Uvedenou èástku mùžete uhradit následujícími zpùsoby:<br/><br/>
 
<b>a) bankovním pøevodem</b><br/>
èíslo úètu: 19-6706930207 / 0100<br/>
variabilní symbol (èíslo objednávky) = ".$id_objednavka."<br/><br/>
 
<b>b) Poštovní poukázkou</b><br/>
Adresa pro zaslání pøíslušné èástky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slaný, 27401<br/><br/>
 
<b>c) Hotovì</b> na nìkteré z poboèek CK SLAN tour - Praha, Slaný, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
 
Zpùsob a datum úhrady nám, prosím, avizujte.<br/>
<strong>Doplatek do celkové ceny objednávky je nutné uhradit do 30 dnù pøed odjezdem na zájezd.	</strong><br/><br/>		
			";
		}else{
			$zaloha = $this->celkova_cena;
			$pokyny_k_platbe = "Vzhledem k tomu, že objednáváte zájezd s odjezdem za ménì než ".MAX_TERMIN_ZALOHA." dní, je k dokonèení Vaší rezervace tøeba uhradit celkovou cenu objednávky, tj: <strong>".$zaloha." Kè.</strong><br/>
<b>Výše uvedenou èástku za vaši objednávku uhraïte, prosím, nejpozdìji do 2 pracovních dní. </b><br/>
Uvedenou èástku mùžete uhradit následujícími zpùsoby:<br/><br/>
 
<b>a) bankovním pøevodem</b><br/>
èíslo úètu: 19-6706930207 / 0100<br/>
variabilní symbol (èíslo objednávky) = ".$id_objednavka."<br/><br/>
 
<b>b) Poštovní poukázkou</b><br/>
Adresa pro zaslání pøíslušné èástky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slaný, 27401<br/><br/>
 
<b>c) Hotovì</b> na nìkteré z poboèek CK SLAN tour - Praha, Slaný, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
 
Zpùsob a datum úhrady nám, prosím, avizujte.<br/><br/>			
			";
		}
	}else{//dlouhodobe zajezdy
		$pokyny_k_platbe = "Pracovníci CK SLAN tour provìøí aktuální dostupnost Vámi požadovaných služeb v daném termínu a budou Vás dále informovat.<br/>
							Výše uvedené cenové údaje se mohou v konkrétních termínech / délce pobytù mírnì lišit - po zpracování Vašeho požadavku Vás budeme informovat o celkové cenì Vaší objednávky.
							<br/>S platbou za objednávku vyèkejte až na potvrzení dostupnosti Vámi objednaných služeb.
									<br/><br/>
<b>Platbu za objednávku mùžete provést následujícími zpùsoby: </b><br/><br/>
<b>a) bankovním pøevodem</b><br/>
èíslo úètu: 19-6706930207 / 0100<br/>
variabilní symbol (èíslo objednávky) = ".$id_objednavka."<br/><br/>
<b>b) Poštovní poukázkou</b><br/>
Adresa pro zaslání pøíslušné èástky<br/>
SLAN tour s.r.o.<br/>
Wilsonova 597<br/>
Slaný, 27401<br/><br/>		
<b>c) Hotovì</b> na nìkteré z poboèek CK SLAN tour - Praha, Slaný, Roudnice n.L., Kralupy n.Vlt.<br/><br/>
Zpùsob a datum úhrady nám, prosím, avizujte.<br/>									
								";
	}

?>