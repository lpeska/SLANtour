<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('typ-zajezdu.html.twig', [
    'type' => new TourType('Poznávací', 139, 9900, 'img/poznavaci.png'),
    'tours' => array(
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', 'img/lazne.png'),
        new Tour('Víkend v Budapešti - vlakem', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', 'img/dovolena.png'),
        new Tour('Jordánsko s pobytem u Rudého moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', 'img/poznavaci.png'),
        new Tour('Villa Dino, Mariánské Lázně', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', 'img/lazne.png')
    )
]);

class Tour
{
    public string $name;
    public int $price;
    public int $priceDiscount;
    public int $priceOriginal;
    public int $nights;
    public string $meals;
    public string $destination;
    public string $image;

    public function __construct(string $name, int $price, int $priceDiscount, int $priceOriginal, int $nights, string $meals, string $destination, string $image)
    {
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

class TourType
{
    public string $name;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;

    public function __construct(string $name, int $numberOfTours, int $priceFrom, string $image)
    {
        $this->name = $name;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
    }
}
