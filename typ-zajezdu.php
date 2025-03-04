<?php
require_once 'vendor/autoload.php';
require_once "./core/load_core.inc.php";
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes

$url = "$_SERVER[REQUEST_URI]";
$values = parse_url($url);
$path = explode('/', $values['path']);

$typeName = $path[count($path) - 1];

$type = getTourType($typeName);
$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();

$breadCrumbs = array(
    new Breadcrumb('Typy zájezdů', '/typy-zajezdu.php'),
    new Breadcrumb($type->name, $type->url)
);

$discountTours = getDiscountTours($typeName, "");

$popularTours = getPopularTours($typeName, "");

$newTours = getNewTours($typeName, "");

if ($typeName == 'za-sportem') {
    $countries = getSportCountries();
} else {
    $countries = getAllCountries($continentName, $typeName);
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('typ-zajezdu.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'type' => $type,
    'popularTours' => $popularTours,
    'discountTours' => $discountTours,
    'totalTours' => $type->numberOfTours,
    "totalDiscountedTours" => 157,
    'newTours' => $newTours,
    'countries' => $countries,
    'breadcrumbs' => $breadCrumbs
]);
