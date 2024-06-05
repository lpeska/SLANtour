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
$zajezdyA = mysqli_fetch_all($res, MYSQLI_ASSOC);
$zajezdyArr = [];

foreach ($zajezdyA as $key => $row) {
    $zajezdyArr[$row["id_zajezd"]] = $row;
}

//print_r($zajezdyArr);
$tours = [];
foreach ($zajezdIDs as $key => $zID) {
    $row = $zajezdyArr[$zID];
    if(is_array($row)){
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
        $allDatesRes = $serialCol->get_all_dates_for_id_serial($row["id_serial"]);                
        $allDates = mysqli_fetch_all($allDatesRes, MYSQLI_ASSOC);
        
        foreach ($allDates as $key => $value) {
            $allDates[$key]["dates"] = Serial_collection::get_dates($value);
        }
        
        $totalDates = count($allDates);    
                
        if($totalDates >= 1){
            $totalDates = $totalDates - 1;

        }else{
            $totalDates = 0;
        }
        

        
        try{
            $tours[] = new Tour(
                Serial_collection::get_nazev($row), 
                $row["nazev_web"],
                $row["nazev_typ"], 
                $row["id_zajezd"], 
                Serial_collection::get_dates($row), 
                $totalDates, 
                $allDates,
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
        } catch(TypeError $e){
            //echo "wrong tour".$row["id_zajezd"];   
            //tohle by melo zachytit spatne vyplnene zajezdy
        }
    }
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
    public string $nights;
    public string $dates;
    public int $totalOtherDates;
    public array $allDates;
    public int $id_zajezd;
    public int $price;
    public int $priceDiscount;
    public int $priceOriginal;
    public string $meals;
    public string $transport;
    public string $accomodation;
    public string $destination;
    public string $image;
    public array $features;
    public string $description;

    public function __construct( string $name, string $escapedName, string $type, int $id_zajezd, string $dates, int $totalOtherDates, array $allDates, int $price, int $priceDiscount, int $priceOriginal, string $nights, string $meals,string $transport,string $accomodation, string $destination, string $image, array $features, string $description)
    {
        $this->name = $name;
        $this->escapedName = $escapedName;
        $this->type = $type;
        $this->id_zajezd = $id_zajezd;
        $this->dates = $dates;
        $this->totalOtherDates = $totalOtherDates;
        $this->allDates = $allDates;
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
