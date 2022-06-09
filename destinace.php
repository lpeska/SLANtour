<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('destinace.html.twig', [
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

class Breadcrumb {
    public string $label;
    public string $link;

    public function __construct(string $label, string $link) {
        $this->label = $label;
        $this->link = $link;
    }
}

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

class Destination
{
    public string $name;
    public string $image;

    public function __construct(string $name, string $image)
    {
        $this->name = $name;
        $this->image = $image;
    }
}
