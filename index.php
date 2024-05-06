<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 

require_once "./classes/loadDataTwig.inc.php"; //seznam serialu
require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu



$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();
$totalTours = getTotalTours($tourTypes);

$discountTours = getDiscountTours("", "");
$popularTours = getPopularTours("", "");
$newTours = getNewTours("", "");

$reviews = getOhlasy(4);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('index.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'popularTours' => $popularTours,
    'discountTours' => $discountTours,
    "totalDiscountedTours" => 157,
    "totalTours" => $totalTours,
    'newTours' => $newTours,
    'reviews' => $reviews,
    'news' => array(
        new News('Peking 2022', 'V únoru příštího roku se budou konat Zimní olympijské hry v Peking.', '5.', 'Únor','img/sport.png'),
        new News('NHL - Boston Bruins letecky', 'V současné chvíli rozšiřujeme naši nabídku leteckých zájezdů na NHL o zápasy Boston Bruins,...', '28.', 'Leden','img/sport.png'),
        new News('MOTO GP Brno', 'Vstupenky na MOTO GP do Brna jsou již v prodeji. Nejvýhodnější ceny platí do...', '14.', 'Leden','img/dovolena.png'),
        new News('Rezervace bez RIZIKA', 'POZNÁVACÍ ZÁJEZDY – REZERVACE  BEZ RIZIKA  Aktualizace podmínek ze dne 8.1.2021 Věřím, že po...', '1', 'Červenec','img/lazne.png'),
    )
    ]);