<?php
/** 
config.inc.php - nastavuje konfiguracni konstanty pro nastaveni cele administracni casti systemu
*/


/*--------------------- NASTAVEN� V�PISU SEZNAM�--------------------------------*/
//standartni pocet zobrazenych zaznamu (nap�. po�et ��dk� seznamu seri�l�) na stranku
define("POCET_ZAZNAMU", 100); 

/*--------------------- NASTAVEN� REZERVAC�--------------------------------*/
//maxim�ln� po�et osob, kter� lze p�idat do jedn� rezervace (��slo se m��e li�it oproti klientsk� sekci)
define("MAX_OSOB", 100); 

//maxim�ln� po�et cen, kter� lze p�i�adit k seri�lu
define("MAX_CEN", 100); 

/*--------------------- NASTAVEN� VKL�D�N� HTML ZNA�EK--------------------------------*/
//HTML zna�ky kter� budou povoleny v popisech seri�lu, informac� a dal��ch pol�ch
define("ALLOWED_HTML_TAGS", "<em><strong><div><p><p/><span><b><i><u><br><br/><a><a/><img><img/><h5><h4><h3><h2><h1><ul><ol><li><strong><table><tr><td><th>");


/*--------------------- NASTAVEN� FOTOGRAFI�, DOKUMENT�--------------------------------*/
define("ADRESAR_FULL", "foto/full"); //nastaveni adresare ve kterem jsou fotky v plnem rozliseni
define("ADRESAR_NAHLED", "foto/nahled"); //adresar pro nahledy fotek
define("ADRESAR_IKONA", "foto/ico"); //adresar pro ikony fotek
define("ADRESAR_MINIIKONA", "foto/miniico"); //adresar pro miniikony
define("ADRESAR_DOKUMENT", "dokumenty"); //adresar pro dokumenty


				/*jm�no odesilatele u automaticky generovan�ch e-mail� ze syst�mu RSCK*/
				define("AUTO_MAIL_SENDER", "CK SLAN tour");

				/*email odesilatele u automaticky generovan�ch e-mail� ze syst�mu RSCK*/
				define("AUTO_MAIL_EMAIL", "info@slantour.cz");

				/*email na ktery budou chodit dotazy, p�edb�n� popt�vky a objedn�vky zajezdu ze syst�mu RSCK*/
				define("PRIJIMACI_EMAIL", "info@slantour.cz");

?>
