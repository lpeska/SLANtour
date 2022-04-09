<?php
/** 
config.inc.php - nastavuje konfiguracni konstanty pro nastaveni celeho systemu 
*/
				/*--------------------- NASTAVENÍ webu--------------------------------*/
				define("ZEME2p", "italii");
				define("ZEME", "Lázeòské zemì");
				
				define("TITLE_KEYWORD","");
				define("HLAVNI_TITULEK_WEBU", "SLAN tour");
				define("TITLE_TOP", "SLAN tour | Poznávací zájezdy ".Date("Y").", Láznì, Dovolená u moøe léto ".Date("Y").", Premier League, Formule 1, ATP Masters");
				
				define("DESCRIPTION_TOP", "SLAN tour: Poznávací zájezdy ".Date("Y")." (Francie, Nìmecko, Evropa, Mexiko, Indie, Rakousko, Itálie), Dovolená u moøe (Chorvatsko, Francie, Španìlsko, Mexiko - Cancun, Bali, SAE), Lyzování ve Francii, láznì v Èechách, Moravì, Slovensku a Maïarsku, pobyty na horách i u vody, Premier League, Formule 1, tenis - vstupenky a zájezdy.");
				define("TYP_ID", "3");
				define("DALSI_PODMINKY_TYP", "  and (`typ_serial`.`id_typ` =3 )");
				define("DALSI_PODMINKY_ZEME_INFORMACE", "  and `informace`.`detailni_typ` =\"lazne\" ");
				define("DALSI_PODMINKY_TYP_INFORMACE", "  and `typ`.`detailni_typ` =\"lazne\" ");
				define("FOTO_WEB", "https://www.slantour.cz");
				define("DOKUMENT_WEB", "https://www.slantour.cz");
				
				
/*--------------------- NASTAVENÍ KLIENTÙ, OBJEDNÁVEK --------------------------------*/

/*immediate rezervace dovolí klientùm se pøihlásit k zájezdu bez potvrzení CK, pokud je volná kapacita požadovaných služeb
	objednavka bude uložena ve stavu opce*/
define("ALLOW_IMMEDIATE_RESERVATION", 1);

/*pocet dni, po ktere plati opce (pokud by byl zacatek zajezdu drive nez konec platnosti opce, je automaticky zkrácena na 1 den)*/
define("PLATNOST_OPCE", 7);

/*standardni vyse zalohy*/
define("VYSE_ZALOHY", 0.3);

/*standardni vyse zalohy*/
define("VYSE_ZALOHY_LETECKY", 0.5);

/*minimalni doba (pocet dni) do zacatku zajezdu, kdy lze jeste platit jen zalohu (jinak cela cena pobytu)*/
define("MAX_TERMIN_ZALOHA", 35);

/*minimalni doba (pocet dni) do zacatku zajezdu, kdy lze jeste platit jen zalohu (jinak cela cena pobytu)*/
define("MAX_TERMIN_ZALOHA_LETECKY", 45);

/*cas platnosti kódu pro potvrzení (pøi registraci, obnovení zapomenutého hesla atd.) v hodinách 
(uživatel musí do POTVRZENI_EXPIRE_TIME hodin kliknout na odkaz, ktery mu prijde v e-mailu)*/
define("POTVRZENI_EXPIRE_TIME", 48);

/*cas platnosti kódu pro potvrzení registrace v hodinách (uživatel musí do POTVRZENI_EXPIRE_TIME hodin kliknout na odkaz, ktery mu prijde v e-mailu)*/
define("PLATNOST_POTVRZENI", 48); 

/*maximální poèet osob, pro které lze vytvoøit objednávku zájezdu
(na pøedbìžnou poptávku se nevztahuje, protoze ta automaticky neodeèítá kapacity)*/
define("MAX_OSOB", 20);

/*maximální poèet cen objednávky 
jde pouze o kontrolu pøed neautorizovanými formuláøi - mùže být relativnì vysoká*/
define("MAX_CEN", 100);


/*--------------------- NASTAVENÍ TYPU ADRESY--------------------------------*/
/* Zapnutím Using_mod_rewrite umožníte generování tzv. friendly_url adres, ale systém musí být spuštìn na webovém serveru APACHE
 hodnoty: 1=zapnuto, 0=vypnuto */
define("USING_MOD_REWRITE", 1); 

/*--------------------- NASTAVENÍ VYHLEDÁVÁNÍ--------------------------------*/
/*poèet rokù po souèasném se má zobrazit ve formuláøi pro vyhledávání, položku termín odjezdu
	pøíklad: souèasný rok = 2008, hodnota=2, ve vyhledávání bude možné zadat rok 2008, 2010, 2010
*/
define("MAX_YEAR", 1); 

//standartni pocet zobrazenych zaznamu (zájezdù) na stranku
define("POCET_ZAZNAMU", 10); 

/*--------------------- NASTAVENÍ FOTOGRAFIÍ, DOKUMENTÙ--------------------------------*/
//nastaveni adresare ve kterem se nalézají fotografie, toto nastavení doporuèujeme zachovat
define("ADRESAR_REWRITE", "photo"); 
define("ADRESAR_FULL", "foto/full"); 
define("ADRESAR_NAHLED", "foto/nahled"); 
define("ADRESAR_IKONA", "foto/ico");
define("ADRESAR_MINIIKONA", "foto/miniico");

//nastaveni adresare ve kterem se nalézají dokumenty, toto nastavení doporuèujeme zachovat
define("ADRESAR_DOKUMENT", "dokumenty");

				/*jméno odesilatele u automaticky generovaných e-mailù ze systému RSCK*/
				define("AUTO_MAIL_SENDER", "CK SLAN tour");

				/*email odesilatele u automaticky generovaných e-mailù ze systému RSCK*/
				define("AUTO_MAIL_EMAIL", "info@slantour.cz");

				/*email na ktery budou chodit dotazy, pøedbìžné poptávky a objednávky zajezdu ze systému RSCK*/
				define("PRIJIMACI_EMAIL", "info@slantour.cz");
?>
