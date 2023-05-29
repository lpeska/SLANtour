<?php
require_once 'vendor/autoload.php';


require_once "./core/load_core.inc.php"; 

require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/serial_collection.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu



$url = "$_SERVER[REQUEST_URI]";
$values = parse_url($url);
$path = explode('/',$values['path']);

$typeName = $path[count($path) - 1];
//Loading tour type details
$serialDB = new Serial_collection();
$res = $serialDB->get_tour_type_for_nazev_web($typeName);
$typeDB = mysqli_fetch_all($res, MYSQLI_ASSOC)[0];
switch ($typeDB["id_typ"]) {
    case 1: 
        $foto = '/img/dovolena.png';
        break;
    case 2:
        $foto = '/img/poznavaci.png';
        break;
    case 29:
        $foto = '/img/eurovikendy.png';
        break;
    case 3:
        $foto = '/img/lazne.png';
        break;
    case 4:
        $foto = '/img/sport.png';
        break;        
    default:
        $foto = $typ["foto_url"];
}

$type = new TourType($typeDB["nazev_typ"], $foto, "/zajezdy/typ-zajezdu/".$typeDB["nazev_typ_web"]);

/*Loading tours_slevy*/
$discountTours = array();

$slevy_array = array();
$slevy_poradi = array();
$slevy_list = new Serial_list($typeName,"", "", "", "", "", "","","","random",40,"select_slevy");
while ($slevy_list->get_next_radek()) {
    $slevyObj = $slevy_list->show_list_item("slevy_list");
    
    $od = $slevy_list->get_termin_od();
    $data = explode("-", $od);
    if($dlouhodobe){
        $dny_rozdil = 60;
    }else{
        $time_od = mktime(0, 0, 0, $data[1], $data[2], $data[0]);
        $time_now = mktime(0, 0, 0, Date("m"), Date("d"), Date("Y"));
        $dny_rozdil = ($time_od - $time_now)/86400;  
    
        if($dny_rozdil < 0 or $dny_rozdil > 80){
        //dlouhodobe zajezdy, nemaji prednost
            $dny_rozdil = 80;
        }
        if($dny_rozdil < 5){
            $dny_rozdil = 5;
        }
    }
    $dny_rozdil = log($dny_rozdil);
    $sleva = $slevy_list->get_max_sleva_zajezd();
    $rand = mt_rand(1, 1000000)/500000;
    $poradi = ($sleva/$dny_rozdil) + $rand;
    
    if($slevyObj["best_zajezd"] > -1){
        $slevy_poradi[] = $poradi;
        $slevy_array[] = $slevyObj;   
    }
}
//print_r($slevy_array);
if(count($slevy_poradi) > 2){

    arsort($slevy_poradi);
    $k = 0;
    foreach ($slevy_poradi as $key => $val) {
        $k++;
        $currTour = $slevy_array[$key];
        $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
        if($k<=4){
            $discountTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"],  $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"]-1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"]);
        }else{
            break;
        }
    }
}
    

/*Loading tours_popularni*/
$popularTours = array();
$popular_array = array();
$popular_zajezdy = new Serial_list($typeName,"", "", "", "", "", "","","","random",10,"select_vahy");
$i = 0;
while ($popular_zajezdy->get_next_radek()) {
    $i++;
    $toursObj = $popular_zajezdy->show_list_item("new_tour_list");
    if($toursObj["best_zajezd"] > -1){
        $popular_array[] = $toursObj;    
    }
}
//print_r($popular_array);
shuffle($popular_array);
$k = 0;
foreach ($popular_array as $key => $currTour) {
    $k++;
    //$currTour = $novinky_array[$key];
    $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
    if($k<=4){
        $popularTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"]-1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"]);
    }else{
        break;
    }
}



/*Loading tours_novinky*/
$newTours = array();
$novinky_array = array();
$novinky_zajezdy = new Serial_list($typeName,"", "", "", "", "", "","","","random",20,"select_nove_zajezdy");
$i = 0;
while ($novinky_zajezdy->get_next_radek()) {
    $i++;
    $toursObj = $novinky_zajezdy->show_list_item("new_tour_list");
    $rand = mt_rand(1, 150)/10;
    $poradi = $i + $rand;
    
    if($toursObj["best_zajezd"] > -1){
        $novinky_poradi[] = $poradi;
        $novinky_array[] = $toursObj;  
    }
}
//print_r($novinky_array);
//print_r($novinky_poradi);
asort($novinky_poradi);
$k = 0;
foreach ($novinky_poradi as $key => $val) {
    $k++;
    $currTour = $novinky_array[$key];
    $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
    if($k<=4){
        $newTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"]-1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"]);
    }else{
        break;
    }
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('typ-zajezdu.html.twig', [
    'type' => $type,
    'popularTours' => $popularTours,
    'discountTours' => $discountTours,
    "totalDiscountedTours" => 157,
    'newTours' => $newTours,
    'breadcrumbs' => array(
        new Breadcrumb('Pobytové zájezdy', '/typ-zajezdu.php')
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


class Tour {
    public string $name;
    public string $escapedName;
    public int $id_zajezd;
    public int $price;
    public  $priceDiscount;
    public  $priceOriginal;
    public  $nights;
    public string $meals;
    public string $destination;
    public string $image;
    public $terminy;

    public function __construct(string $name, string $escapedName, int $id_zajezd, int $price,  $priceDiscount,  $priceOriginal, $nights, string $meals, string $destination, string $image, $terminy="") {
        $this->name = $name;
        $this->escapedName = $escapedName;
        $this->id_zajezd = $id_zajezd;
        $this->price = $price;
        $this->priceDiscount = $priceDiscount;
        $this->priceOriginal = $priceOriginal;
        $this->nights = $nights;
        $this->meals = $meals;
        $this->destination = $destination;
        $this->image = $image;
        $this->terminy = $terminy;
    }
}

class TourType {
    public string $name;
    public string $image;
    public string $url;

    public function __construct(string $name, string $image, $url) {
        $this->name = $name;
        $this->image = $image;
        $this->url = $url;
    }
}
