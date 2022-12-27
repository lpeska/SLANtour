<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 
require_once "./classes/serial_collection.inc.php"; //seznam serialu
$serialCol = new Serial_collection();

#get portion of data with zajezdy
$res = $serialCol->get_zajezdy_base();
$zajezdyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);
$jsonData = json_encode($zajezdyArr);
file_put_contents("data.json",$jsonData,LOCK_EX);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$features = array(
    new Feature('fa-plane', 'Letecky'), 
    new Feature('fa-hotel', 'Hotel 3&#9733;'), 
    new Feature('fa-bed', '4 noci'), 
    new Feature('fa-utensils', 'Polopenze'),
    new Feature('fa-umbrella-beach', 'Na pláži'),
    new Feature('fa-person-swimming', 'Bazén'),
    new Feature('fa-wifi', 'Wifi')
);

echo $twig->render('vyhledat-zajezd.html.twig', [
    'types' => array(
        new TourType('Poznávací', 139, 9900, 'img/poznavaci.png'),
        new TourType('Eurovíkendy', 62, 15900, 'img/eurovikendy.png'),
        new TourType('Dovolená u moře', 140, 7900, 'img/dovolena.png'),
        new TourType('Lázně & Wellness', 79, 3900, 'img/lazne.png'),
        new TourType('Sport', 32, 7900, 'img/sport.png'),
        new TourType('Tuzemské pobyty', 139, 9900, 'img/poznavaci.png'),
        new TourType('Fly and Drive', 140, 7900, 'img/dovolena.png'),
        new TourType('Exotické zájezdy', 79, 3900, 'img/lazne.png'),
        new TourType('Jednodenní zájezdy', 32, 7900, 'img/sport.png'),
    ),
    'transports' => array('Letecky', 'Vlakem', 'Autokar', 'Vlastní'),
    'foods' => array('All-inclusive', 'Plná penze', 'Polopenze', 'Snídaně'),
    'sales' => array('Akční nabídky', 'Slevy', 'Last Minute'),
    'tourLenght' => array('Jednodenní', '1-7 nocí', '7-14 nocí', '> 14 nocí'),
    'tours' => array(
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 'Poznávací', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', '/img/lazne.png', $features),
        new Tour('Víkend v Budapešti - vlakem', 'Eurovíkendy', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', '/img/dovolena.png', $features),
        new Tour('Jordánsko s pobytem u Rudého moře', 'Dovolená u moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', '/img/poznavaci.png', $features),
        new Tour('Villa Dino, Mariánské Lázně', 'Sport', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', '/img/lazne.png', $features),
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 'Poznávací', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', '/img/lazne.png', $features),
        new Tour('Víkend v Budapešti - vlakem', 'Exotické zájezdy', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', '/img/dovolena.png', $features),
        new Tour('Jordánsko s pobytem u Rudého moře', 'Lázně & Wellness', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', '/img/poznavaci.png', $features),
        new Tour('Villa Dino, Mariánské Lázně', 'Lázně & Wellness', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', '/img/lazne.png', $features)
    ),
    'breadcrumbs' => array(
        new Breadcrumb('Zájezdy', '../vyhledat-zajezd.php')
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
    public string $type;
    public int $price;
    public int $priceDiscount;
    public int $priceOriginal;
    public int $nights;
    public string $meals;
    public string $destination;
    public string $image;
    public array $features;

    public function __construct(string $name, string $type, int $price, int $priceDiscount, int $priceOriginal, int $nights, string $meals, string $destination, string $image, array $features)
    {
        $this->name = $name;
        $this->type = $type;
        $this->price = $price;
        $this->priceDiscount = $priceDiscount;
        $this->priceOriginal = $priceOriginal;
        $this->nights = $nights;
        $this->meals = $meals;
        $this->destination = $destination;
        $this->image = $image;
        $this->features = $features;
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

class Feature {
    public string $icon;
    public string $text;

    public function __construct(string $icon, string $text) {
        $this->icon = $icon;
        $this->text = $text;
    }
}
