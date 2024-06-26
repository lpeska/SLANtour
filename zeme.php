<?php
require_once 'vendor/autoload.php';
require_once "./core/load_core.inc.php"; 
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();

$url = "$_SERVER[REQUEST_URI]";
$values = parse_url($url);
$path = explode('/',$values['path']);

$countryName = $path[count($path) - 1];
//echo $countryName;
$country = getCountry($countryName);

$breadCrumbs = array(
    new Breadcrumb('Země', '/zeme-seznam'),
    new Breadcrumb($country->name, $country->url)
);

$discountTours = getDiscountTours("", $countryName);

$popularTours = getPopularTours("", $countryName);

$newTours = getNewTours("", $countryName);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zeme.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'destination' => $country,
    'popularTours' => $popularTours,
    'discountTours' => $discountTours,
    'totalTours' => $country->numberOfTours,
    "totalDiscountedTours" => 157,
    'newTours' => $newTours,
    'breadcrumbs' => $breadCrumbs
]);