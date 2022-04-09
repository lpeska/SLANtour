<?php
/** 
config.inc.php - nastavuje konfiguracni konstanty pro nastaveni celeho systemu 
*/
				/*--------------------- NASTAVEN� webu--------------------------------*/
				define("ZEME2p", "italii");
				define("ZEME", "L�ze�sk� zem�");
				
				define("TITLE_KEYWORD","");
				define("HLAVNI_TITULEK_WEBU", "SLAN tour");
				define("TITLE_TOP", "SLAN tour | Pozn�vac� z�jezdy ".Date("Y").", L�zn�, Dovolen� u mo�e l�to ".Date("Y").", Premier League, Formule 1, ATP Masters");
				
				define("DESCRIPTION_TOP", "SLAN tour: Pozn�vac� z�jezdy ".Date("Y")." (Francie, N�mecko, Evropa, Mexiko, Indie, Rakousko, It�lie), Dovolen� u mo�e (Chorvatsko, Francie, �pan�lsko, Mexiko - Cancun, Bali, SAE), Lyzov�n� ve Francii, l�zn� v �ech�ch, Morav�, Slovensku a Ma�arsku, pobyty na hor�ch i u vody, Premier League, Formule 1, tenis - vstupenky a z�jezdy.");
				define("TYP_ID", "3");
				define("DALSI_PODMINKY_TYP", "  and (`typ_serial`.`id_typ` =3 )");
				define("DALSI_PODMINKY_ZEME_INFORMACE", "  and `informace`.`detailni_typ` =\"lazne\" ");
				define("DALSI_PODMINKY_TYP_INFORMACE", "  and `typ`.`detailni_typ` =\"lazne\" ");
				define("FOTO_WEB", "https://www.slantour.cz");
				define("DOKUMENT_WEB", "https://www.slantour.cz");
				
				
/*--------------------- NASTAVEN� KLIENT�, OBJEDN�VEK --------------------------------*/

/*immediate rezervace dovol� klient�m se p�ihl�sit k z�jezdu bez potvrzen� CK, pokud je voln� kapacita po�adovan�ch slu�eb
	objednavka bude ulo�ena ve stavu opce*/
define("ALLOW_IMMEDIATE_RESERVATION", 1);

/*pocet dni, po ktere plati opce (pokud by byl zacatek zajezdu drive nez konec platnosti opce, je automaticky zkr�cena na 1 den)*/
define("PLATNOST_OPCE", 7);

/*standardni vyse zalohy*/
define("VYSE_ZALOHY", 0.3);

/*standardni vyse zalohy*/
define("VYSE_ZALOHY_LETECKY", 0.5);

/*minimalni doba (pocet dni) do zacatku zajezdu, kdy lze jeste platit jen zalohu (jinak cela cena pobytu)*/
define("MAX_TERMIN_ZALOHA", 35);

/*minimalni doba (pocet dni) do zacatku zajezdu, kdy lze jeste platit jen zalohu (jinak cela cena pobytu)*/
define("MAX_TERMIN_ZALOHA_LETECKY", 45);

/*cas platnosti k�du pro potvrzen� (p�i registraci, obnoven� zapomenut�ho hesla atd.) v hodin�ch 
(u�ivatel mus� do POTVRZENI_EXPIRE_TIME hodin kliknout na odkaz, ktery mu prijde v e-mailu)*/
define("POTVRZENI_EXPIRE_TIME", 48);

/*cas platnosti k�du pro potvrzen� registrace v hodin�ch (u�ivatel mus� do POTVRZENI_EXPIRE_TIME hodin kliknout na odkaz, ktery mu prijde v e-mailu)*/
define("PLATNOST_POTVRZENI", 48); 

/*maxim�ln� po�et osob, pro kter� lze vytvo�it objedn�vku z�jezdu
(na p�edb�nou popt�vku se nevztahuje, protoze ta automaticky neode��t� kapacity)*/
define("MAX_OSOB", 20);

/*maxim�ln� po�et cen objedn�vky 
jde pouze o kontrolu p�ed neautorizovan�mi formul��i - m��e b�t relativn� vysok�*/
define("MAX_CEN", 100);


/*--------------------- NASTAVEN� TYPU ADRESY--------------------------------*/
/* Zapnut�m Using_mod_rewrite umo�n�te generov�n� tzv. friendly_url adres, ale syst�m mus� b�t spu�t�n na webov�m serveru APACHE
 hodnoty: 1=zapnuto, 0=vypnuto */
define("USING_MOD_REWRITE", 1); 

/*--------------------- NASTAVEN� VYHLED�V�N�--------------------------------*/
/*po�et rok� po sou�asn�m se m� zobrazit ve formul��i pro vyhled�v�n�, polo�ku term�n odjezdu
	p��klad: sou�asn� rok = 2008, hodnota=2, ve vyhled�v�n� bude mo�n� zadat rok 2008, 2010, 2010
*/
define("MAX_YEAR", 1); 

//standartni pocet zobrazenych zaznamu (z�jezd�) na stranku
define("POCET_ZAZNAMU", 10); 

/*--------------------- NASTAVEN� FOTOGRAFI�, DOKUMENT�--------------------------------*/
//nastaveni adresare ve kterem se nal�zaj� fotografie, toto nastaven� doporu�ujeme zachovat
define("ADRESAR_REWRITE", "photo"); 
define("ADRESAR_FULL", "foto/full"); 
define("ADRESAR_NAHLED", "foto/nahled"); 
define("ADRESAR_IKONA", "foto/ico");
define("ADRESAR_MINIIKONA", "foto/miniico");

//nastaveni adresare ve kterem se nal�zaj� dokumenty, toto nastaven� doporu�ujeme zachovat
define("ADRESAR_DOKUMENT", "dokumenty");

				/*jm�no odesilatele u automaticky generovan�ch e-mail� ze syst�mu RSCK*/
				define("AUTO_MAIL_SENDER", "CK SLAN tour");

				/*email odesilatele u automaticky generovan�ch e-mail� ze syst�mu RSCK*/
				define("AUTO_MAIL_EMAIL", "info@slantour.cz");

				/*email na ktery budou chodit dotazy, p�edb�n� popt�vky a objedn�vky zajezdu ze syst�mu RSCK*/
				define("PRIJIMACI_EMAIL", "info@slantour.cz");
?>
