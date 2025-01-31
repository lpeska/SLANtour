<?php
//header('Content-Type: text/html; charset=windows-1250');
/** 
*load_core.inc.php - naincluduje pot?ebn? soubory pro ka?d? modul a vytvo?? instanci t??dy Core
*/
//require_once "./global/config.inc.php"; //nastaven? syst?mu - obecn?
require_once __DIR__ . "/../config/config.inc.php"; //nastaven? syst?mu - klientsk? ??st
require_once __DIR__ . "/generic_classes.inc.php"; //abstraktni tridy
require_once __DIR__ . "/../global/library_classes_w1250.inc.php"; //spolecne knihovni tridy
require_once __DIR__ . "/database.inc.php"; //odesilani dotazu do databaze
require_once __DIR__ . "/uzivatel.inc.php"; //prihlaseni uzivatele
require_once __DIR__ . "/send_mail.inc.php"; //odesilani e-mailu

require_once __DIR__ . "/core.inc.php"; //j?dro syst?mu

$core = Core::get_instance();	
setlocale(LC_ALL, 'cs_CZ');
?>
