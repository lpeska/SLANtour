<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();

$reviews = getOhlasy(20);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('ohlasy.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'reviews' => $reviews,
    'breadcrumbs' => array(
        new Breadcrumb('Ohlasy klientů', '/ohlasy')
    )
]);