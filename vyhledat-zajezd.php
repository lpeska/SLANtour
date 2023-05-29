<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 
require_once "./classes/serial_collection.inc.php"; //seznam serialu
$serialCol = new Serial_collection();

#get portion of data with zajezdy
$resZ = $serialCol->get_zajezdy_base();
$zajezdyArr = mysqli_fetch_all($resZ, MYSQLI_ASSOC);
$jsonDataZ = json_encode($zajezdyArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("data.json",$jsonDataZ,LOCK_EX);

$resZZ = $serialCol->get_all_zeme_serial();
$zemeDB = mysqli_fetch_all($resZZ, MYSQLI_ASSOC);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
$zemeArr = [];
foreach ($zemeDB as $key => $z) {
    $zemeArr[$z["sId"]] = $z;    
}    
$jsonDataZZ = json_encode($zemeArr);
file_put_contents("serial_zeme.json",$jsonDataZZ,LOCK_EX);

$resD = $serialCol->get_all_destinace_serial();
$destDB = mysqli_fetch_all($resD, MYSQLI_ASSOC);
$destArr = [];
foreach ($destDB as $key => $d) {
    $destArr[$d["sId"]] = $d;  
}
$jsonDataD = json_encode($destArr);
file_put_contents("serial_destinace.json",$jsonDataD,LOCK_EX);



$resT = $serialCol->get_all_tour_types();
$tourTypesDB = mysqli_fetch_all($resT, MYSQLI_ASSOC);
$tourTypesArr = [];
foreach ($tourTypesDB as $key => $tt) {
    $tourTypesArr[$tt["id_typ"]] = $tt;
}
$jsonDataT = json_encode($tourTypesArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("tour_types.json",$jsonDataT,LOCK_EX);


/*
$res = $serialCol->get_all_zeme();
$zemeDB = mysqli_fetch_all($res, MYSQLI_ASSOC);
$zemeArr = [];
foreach ($zemeDB as $key => $z) {
    $zemeArr[$z["id_zeme"]] = $z;
    $zemeArr[$z["id_zeme"]]["counter"] = 0;
}
$jsonData = json_encode($zemeArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("zeme.json",$jsonData,LOCK_EX);

$res = $serialCol->get_all_destinace();
$destinaceDB = mysqli_fetch_all($res, MYSQLI_ASSOC);
$destinaceArr = [];
foreach ($destinaceDB as $key => $d) {
    $destinaceArr[$d["id_destinace"]] = $d;
    $destinaceArr[$d["id_destinace"]]["counter"] = 0;
}
$jsonData = json_encode($destinaceArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("destinace.json",$jsonData,LOCK_EX);

*/

# process zajezdyArr to get initial statistics on all available data
$tours = [];
foreach ($zajezdyArr as $key => $zajezdIdx) {
    
}


$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$features = array(
    new Feature('fa-plane', 'Letecky'), 
    new Feature('fa-hotel', 'Hotel 3&#9733;'), 
    new Feature('fa-bed', '4 noci'), 
    new Feature('fa-utensils', 'Polopenze')
);

$description = 'Zájezd nás zavede do oblasti, kde se prolíná historie v podobě unikátních památek s malebnou kopcovitou krajinou, 
která utváří ráz této jedinečné oblasti Itálie. Navštívíme historické skvosty často zařazené do seznamu UNESCO, např. 
Assisi, ale i další půvabná městečka s úžasnou atmosférou. Poznávání kulturních zajímavostí proložíme koupáním na italské adriatické riviéře.';

$typesTwig = [];
foreach ($tourTypesArr as $key => $tt) {
    $typesTwig[] = new TourType($tt["id_typ"], $tt["nazev_typ"], 0, 0, "/img/".$tt["nazev_typ_web"].".png");
}

echo $twig->render('vyhledat-zajezd.html.twig', [
    'types' => $typesTwig,
    'transports' => array(3=>'Letecky', 4=>'Vlakem', 2=>'Autokar', 1=>'Vlastní', 5=>'Vlastní nebo autobus'),
    'foods' => array(5=>'All-inclusive', 4=>'Plná penze', 3=>'Polopenze', 2=>'Snídaně', 1=>"Bez stravy"),
    'sales' => array('Akční nabídky', 'Slevy', 'Last Minute'),
    'tourLengths' => array("variabilni"=>'Variabilní', "jednodenni"=>'Jednodenní', "1-5noci"=>'1-5 nocí', "6-10noci"=>'6-10 nocí', "nad10noci"=>">10 nocí"),
    'tours' => array(
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 'Poznávací', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', '/img/lazne.png', $features, $description),
        new Tour('Víkend v Budapešti - vlakem', 'Eurovíkendy', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', '/img/dovolena.png', $features, $description),
        new Tour('Jordánsko s pobytem u Rudého moře', 'Dovolená u moře', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', '/img/poznavaci.png', $features, $description),
        new Tour('Villa Dino, Mariánské Lázně', 'Sport', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', '/img/lazne.png', $features, $description),
        new Tour('Hotel Esprit***, Špindlerův Mlýn', 'Poznávací', 1470, 29, 2070, 4, 'Polopenze', 'Krkonoše', '/img/lazne.png', $features, $description),
        new Tour('Víkend v Budapešti - vlakem', 'Exotické zájezdy', 3590, 0, 3590, 4, 'bez stravy', 'Maďarsko', '/img/dovolena.png', $features, $description),
        new Tour('Jordánsko s pobytem u Rudého moře', 'Lázně & Wellness', 29990, 25, 39986, 7, 'All-inclusive', 'Jordánsko', '/img/poznavaci.png', $features, $description),
        new Tour('Villa Dino, Mariánské Lázně', 'Lázně & Wellness', 4790, 0, 4790, 4, 'Polopenze', 'Mariánské Lázně', '/img/lazne.png', $features, $description)
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
    public string $description;

    public function __construct( string $name, string $type, int $price, int $priceDiscount, int $priceOriginal, int $nights, string $meals, string $destination, string $image, array $features, string $description)
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
        $this->description = $description;
    }
}

class TourType
{
    public int $id;
    public string $name;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;

    public function __construct(string $id, string $name, int $numberOfTours, int $priceFrom, string $image)
    {
        $this->id = $id;
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
