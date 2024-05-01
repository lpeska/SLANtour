<?php
require_once 'vendor/autoload.php';
require_once "./core/load_core.inc.php"; 
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();

$url = "$_SERVER[REQUEST_URI]";
$values = parse_url($url);
$path = explode('/',$values['path']);
$continentName = $path[count($path) - 1];

if ($continentName == "sport") {
    $countries = getSportCountries();

} else {
    $countries = getAllCountries($continentName);
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zeme-seznam.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'countries' => $countries,
    'continentName' => $continentName,
    'breadcrumbs' => array(
        new Breadcrumb('Země', '/zeme-seznam')
    )
]);