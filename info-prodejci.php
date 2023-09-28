<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('info-prodejci.html.twig', [
    'typesOfTours' => $tourTypes,
    'breadcrumbs' => array(
        new Breadcrumb('Info pro prodejce', '/info-prodejci.php')
    )
]);