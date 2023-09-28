<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('destinace.html.twig', [
    'typesOfTours' => $tourTypes,
    'destination' => new Destination('Španělsko', 'img/dovolena.png'),
    'tours' => array(
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', 'img/lazne.png'),
        new Tour('Víkend v Budapešti - vlakem', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', 'img/dovolena.png'),
        new Tour('Jordánsko s pobytem u Rudého moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', 'img/poznavaci.png'),
        new Tour('Villa Dino, Mariánské Lázně', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', 'img/lazne.png')
    ),
    'breadcrumbs' => array(
        new Breadcrumb('Pobytové zájezdy', '/zajezdy/typ-zajezdu/poznavaci-zajezdy'),
        new Breadcrumb('Španělsko', '/destinace.php')
    )
]);