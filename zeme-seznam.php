<?php
require_once 'vendor/autoload.php';
require_once "./core/load_core.inc.php"; 
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$countries = getAllCountries();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zeme-seznam.html.twig', [
    'typesOfTours' => $tourTypes,
    'countries' => $countries,
    'breadcrumbs' => array(
        new Breadcrumb('Destinace', '/destinace-seznam.php')
    )
]);