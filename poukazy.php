<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('poukazy.html.twig', [
    'typesOfTours' => $tourTypes,
    'breadcrumbs' => array(
        new Breadcrumb('Slevové karty a poukazy', '/poukazy.php')
    )
]);