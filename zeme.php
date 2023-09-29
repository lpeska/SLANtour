<?php
require_once 'vendor/autoload.php';
require_once "./core/load_core.inc.php"; 
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$url = "$_SERVER[REQUEST_URI]";
$values = parse_url($url);
$path = explode('/',$values['path']);

$countryName = $path[count($path) - 1];

getCountry($countryName);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zeme.html.twig', [
    'typesOfTours' => $tourTypes,
    'destination' => new Country('Španělsko', 'img/dovolena.png'),
    
    'breadcrumbs' => array(
        new Breadcrumb('Pobytové zájezdy', '/zajezdy/typ-zajezdu/poznavaci-zajezdy'),
        new Breadcrumb('Španělsko', '/destinace.php')
    )
]);