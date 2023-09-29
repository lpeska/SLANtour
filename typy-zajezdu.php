<?php
require_once 'vendor/autoload.php';
require_once "./core/load_core.inc.php"; 
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('typy-zajezdu.html.twig', [
    'typesOfTours' => $tourTypes,
    'breadcrumbs' => array(
        new Breadcrumb('Typy zájezdů', '/typy-zajezdu.php')
    )
]);