<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 
require_once "./classes/serial_collection.inc.php"; //seznam serialu
$serialCol = new Serial_collection();

$zajezdIDs = explode(",",$_POST["zajezdIDs"]);
$zajezdIDsInt = [];
foreach ($zajezdIDs as $key => $zID) {
    $zajezdIDsInt[] = intval($zID);
}     

$res = $serialCol->get_full_data_from_zajezdIDs($zajezdIDsInt);
$zajezdyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);
//print_r($zajezdyArr);
$tours = [];
foreach ($zajezdyArr as $key => $row) {
    $trID = $row["doprava"];
    $trText = Serial_library::get_typ_dopravy($row["doprava"]-1);
    switch($trID) {
        case "1":
            $d = new Feature('fa-car', $trText);
            break;
        case "2":
            $d = new Feature('fa-bus', $trText);
            break;
        case "3":
            $d = new Feature('fa-plane', $trText);
            break;
        case "4":
            $d = new Feature('fa-train', $trText);
            break;        
        default:
            $d = new Feature('fa-car', $trText);
            break;
    }
    $features = array(
        $d,         
        new Feature('fa-hotel', Serial_library::get_typ_ubytovani($row["ubytovani"]-1)), 
        new Feature('fa-bed', Serial_collection::get_nights($row)), 
        new Feature('fa-utensils', Serial_library::get_typ_stravy($row["strava"]-1))
    );
    
    $tours[] = new Tour(
            Serial_collection::get_nazev($row), 
            $row["nazev_typ"], 
            $row["min_castka"], // TODO: jedno z tech dvou je spatne, ale mozna se to lisi dle typu slevy... zjistit
            $row["final_max_sleva"],
            $row["min_castka"],
            Serial_collection::get_nights($row), 
            Serial_library::get_typ_stravy($row["strava"]-1), 
            Serial_library::get_typ_dopravy($row["doprava"]-1), 
            Serial_library::get_typ_ubytovani($row["ubytovani"]-1),             
            Serial_collection::get_destinace($row),
            "//slantour.cz/foto/full/".$row["foto_url"], 
            $features, 
            Serial_collection::get_description($row));
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());


/*
$tours = array(
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 'Poznávací', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', '/img/lazne.png', $features, $description),
        new Tour('Víkend v Budapešti - vlakem', 'Eurovíkendy', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', '/img/dovolena.png', $features, $description),
        new Tour('Jordánsko s pobytem u Rudého moře', 'Dovolená u moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', '/img/poznavaci.png', $features, $description),
        new Tour('Villa Dino, Mariánské Lázně', 'Sport', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', '/img/lazne.png', $features, $description),
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 'Poznávací', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', '/img/lazne.png', $features, $description),
        new Tour('Víkend v Budapešti - vlakem', 'Exotické zájezdy', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', '/img/dovolena.png', $features, $description),
        new Tour('Jordánsko s pobytem u Rudého moře', 'Lázně & Wellness', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', '/img/poznavaci.png', $features, $description),
        new Tour('Villa Dino, Mariánské Lázně', 'Lázně & Wellness', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', '/img/lazne.png', $features, $description)
    );
*/
echo $twig->render('_seznam_zajezdu.html.twig', [
    'tours' => $tours
]);

class Tour 
{
    public string $name;
    public string $type;
    public int $price;
    public int $priceDiscount;
    public int $priceOriginal;
    public string $nights;
    public string $meals;
    public string $transport;
    public string $accomodation;
    public string $destination;
    public string $image;
    public array $features;
    public string $description;

    public function __construct( string $name, string $type, int $price, int $priceDiscount, int $priceOriginal, string $nights, string $meals,string $transport,string $accomodation, string $destination, string $image, array $features, string $description)
    {
        $this->name = $name;
        $this->type = $type;
        $this->price = $price;
        $this->priceDiscount = $priceDiscount;
        $this->priceOriginal = $priceOriginal;
        $this->nights = $nights;
        $this->meals = $meals;
        $this->meals = $transport;
        $this->meals = $accomodation;
        $this->destination = $destination;
        $this->image = $image;
        $this->features = $features;
        $this->description = $description;
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
