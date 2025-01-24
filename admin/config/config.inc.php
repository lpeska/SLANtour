<?php
/** 
config.inc.php - nastavuje konfiguracni konstanty pro nastaveni cele administracni casti systemu
*/


/*--------------------- NASTAVENÍ VÝPISU SEZNAMÙ--------------------------------*/
//standartni pocet zobrazenych zaznamu (napø. poèet øádkù seznamu seriálù) na stranku
define("POCET_ZAZNAMU", 100); 

/*--------------------- NASTAVENÍ REZERVACÍ--------------------------------*/
//maximální poèet osob, který lze pøidat do jedné rezervace (èíslo se mùže lišit oproti klientské sekci)
define("MAX_OSOB", 100); 

//maximální poèet cen, které lze pøiøadit k seriálu
define("MAX_CEN", 100); 

/*--------------------- NASTAVENÍ VKLÁDÁNÍ HTML ZNAÈEK--------------------------------*/
//HTML znaèky které budou povoleny v popisech seriálu, informací a dalších polích
define("ALLOWED_HTML_TAGS", "<em><strong><div><p><p/><span><b><i><u><br><br/><a><a/><img><img/><h5><h4><h3><h2><h1><ul><ol><li><strong><table><tr><td><th>");


/*--------------------- NASTAVENÍ FOTOGRAFIÍ, DOKUMENTÙ--------------------------------*/
define("ADRESAR_FULL", "foto/full"); //nastaveni adresare ve kterem jsou fotky v plnem rozliseni
define("ADRESAR_NAHLED", "foto/nahled"); //adresar pro nahledy fotek
define("ADRESAR_IKONA", "foto/ico"); //adresar pro ikony fotek
define("ADRESAR_MINIIKONA", "foto/miniico"); //adresar pro miniikony
define("ADRESAR_DOKUMENT", "dokumenty"); //adresar pro dokumenty


				/*jméno odesilatele u automaticky generovaných e-mailù ze systému RSCK*/
				define("AUTO_MAIL_SENDER", "CK SLAN tour");

				/*email odesilatele u automaticky generovaných e-mailù ze systému RSCK*/
				define("AUTO_MAIL_EMAIL", "info@slantour.cz");

				/*email na ktery budou chodit dotazy, pøedbìžné poptávky a objednávky zajezdu ze systému RSCK*/
				define("PRIJIMACI_EMAIL", "info@slantour.cz");

?>
