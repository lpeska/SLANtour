<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 

require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu

$menu = new Menu_katalog("dotaz_typy","", "", "");
$typy = $menu->get_typy_pobytu();
//print_r($typy);

$types_for_twig = array();
foreach ($typy as $typ) {
    switch ($typ["id_typ"]) {
        case 1:
            $foto = 'img/dovolena.png';
            break;
        case 2:
            $foto = 'img/poznavaci.png';
            break;
        case 29:
            $foto = 'img/eurovikendy.png';
            break;
        case 3:
            $foto = 'img/lazne.png';
            break;
        case 4:
            $foto = 'img/sport.png';
            break;        
        default:
            $foto = $typ["foto_url"];
    }
    
    $t = new TourType($typ["nazev_typ"], $typ["tourCount"], $typ["tourPrice"], $foto,  $typ["description"], "/zajezdy/typ-zajezdu/".$typ["nazev_typ_web"]);
    $types_for_twig[$typ["id_typ"]] = $t;
}


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('index.html.twig', [
    /*'typesOfTours' => array(
        new TourType('Poznávací', 139, 9900, 'img/poznavaci.png'),
        new TourType('Eurovíkendy', 62, 15900, 'img/eurovikendy.png'),
        new TourType('Dovolená', 140, 7900, 'img/dovolena.png'),
        new TourType('Lázně & Wellness', 79, 3900, 'img/lazne.png'),
        new TourType('Sport', 32, 7900, 'img/sport.png'),
    ),*/
    'typesOfTours' => $types_for_twig,
    'popularTours' => array(
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', 'img/lazne.png'),
        new Tour('Víkend v Budapešti - vlakem', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', 'img/dovolena.png'),
        new Tour('Jordánsko s pobytem u Rudého moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', 'img/poznavaci.png'),
        new Tour('Villa Dino, Mariánské Lázně', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', 'img/lazne.png')
    ),
    'discountTours' => array(
        new Tour('Jordánsko s pobytem u Rudého moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', 'img/poznavaci.png'),
        new Tour('Víkend v Budapešti - vlakem', 3590, 10, 3990, 4, 'bez stravy', 'Maďarsko', 'img/dovolena.png'),
        new Tour('Villa Dino, Mariánské Lázně', 4790, 5, 5290, 4, 'Polopenze', 'Mariánské Lázně', 'img/lazne.png'),
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', 'img/lazne.png')
    ),
    'newTours' => array(
        new Tour('Villa Dino, Mariánské Lázně', 4790, 5, 5290, 4, 'Polopenze', 'Mariánské Lázně', 'img/lazne.png'),
        new Tour('Víkend v Budapešti - vlakem', 3590, 0, 3990, 4, 'bez stravy', 'Maďarsko', 'img/dovolena.png'),
        new Tour('Jordánsko s pobytem u Rudého moře', 29990, 0, 39986, 7, 'All-inclusive', 'Jordánsko', 'img/poznavaci.png'),
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', 'img/lazne.png')
    ),
    'news' => array(
        new News('Peking 2022', 'V únoru příštího roku se budou konat Zimní olympijské hry v Peking.', '5.', 'Únor','img/sport.png'),
        new News('NHL - Boston Bruins letecky', 'V současné chvíli rozšiřujeme naši nabídku leteckých zájezdů na NHL o zápasy Boston Bruins,...', '28.', 'Leden','img/sport.png'),
        new News('MOTO GP Brno', 'Vstupenky na MOTO GP do Brna jsou již v prodeji. Nejvýhodnější ceny platí do...', '14.', 'Leden','img/dovolena.png'),
        new News('Rezervace bez RIZIKA', 'POZNÁVACÍ ZÁJEZDY – REZERVACE  BEZ RIZIKA  Aktualizace podmínek ze dne 8.1.2021 Věřím, že po...', '1', 'Červenec','img/lazne.png'),
    )
    ]);

class TourType {
    public string $name;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;
    public  $description;
    public string $url;

    public function __construct(string $name, int $numberOfTours, int $priceFrom, string $image, $description, $url) {
        $this->name = $name;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
        $this->description = $description;
        $this->url = $url;
        
    }
}

class Tour {
    public string $name;
    public int $price;
    public int $priceDiscount;
    public int $priceOriginal;
    public int $nights;
    public string $meals;
    public string $destination;
    public string $image;

    public function __construct(string $name, int $price, int $priceDiscount, int $priceOriginal, int $nights, string $meals, string $destination, string $image) {
        $this->name = $name;
        $this->price = $price;
        $this->priceDiscount = $priceDiscount;
        $this->priceOriginal = $priceOriginal;
        $this->nights = $nights;
        $this->meals = $meals;
        $this->destination = $destination;
        $this->image = $image;
    }
}

class News {
    public string $title;
    public string $description;
    public string $date;
    public string $month;
    public string $image;

    public function __construct(string $title, string $description, string $day, string $month, string $image) {
        $this->title= $title;
        $this->description = $description;
        $this->day = $day;
        $this->month = $month;
        $this->image = $image;
    }
}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

