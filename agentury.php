<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();
session_start();

$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();

$CAcode = '';
$core = Core::get_instance();
$id_registrace = $core->get_id_modul_from_typ("registrace");
if($id_registrace !== false){
	$uzivatel = User::get_instance();	
	
	//pokud je uzivatel prihlasen, vypiseme uzivatelske menu, jinak formular pro prihlaseni
	if( $uzivatel->get_correct_login() ){
		$CAcode =  $uzivatel->show_klient_menu();
	}else{
		//echo $uzivatel->show_login_form();
		$CAcode =  $uzivatel->show_login_form();
	}
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('agentury.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'breadcrumbs' => array(
        new Breadcrumb('Přihlášení pro cestovné agentury', '/agentury.php')
    ),
    'CAcode' => $CAcode
]);