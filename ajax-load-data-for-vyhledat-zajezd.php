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

$tours = [];
foreach ($zajezdIDs as $key => $zID) {
    $row = $zajezdyArr[$zID];
    // print_r($row);
    // echo "<br><br>";
    if(is_array($row)){
        $trID = $row["doprava"];
        $trText = Serial_library::get_typ_dopravy($row["doprava"]-1);
        switch($trID) {
            case "1":
                $d = new Feature('fa-car', $trText);
                break;
            case "2":
                $d = new Feature('fa-bus-simple', $trText);
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

        $lowestPrice = null;
        foreach ($allDates as $key => $value) {
            $allDates[$key]["dates"] = Serial_collection::get_dates($value);
            $price = $allDates[$key]["castka"];
            if (!$lowestPrice || $lowestPrice > $price) {
                $lowestPrice = $price;
            }
        }

        $totalDates = count($allDates);    
                
        if($totalDates >= 1){
            $totalDates = $totalDates - 1;

        }else{
            $totalDates = 0;
        }
        
        $predbeznaRegistrace = 0;
        if ($row["id_sablony_zobrazeni"] == 8){
            //jde o predbeznou registraci, musi se upravit prezentace zajezdu
            $predbeznaRegistrace = 1;                        
        }
        
        //get tour coordinates
        if($row["gps_lat"]!=""){
            $posY = $row["gps_lat"];
            $posX = $row["gps_long"];
        }else if($row["posX"]!=""){
            $posX = $row["posX"];
            $posY = $row["posY"];            
        }else{
            $posX = "";
            $posY = "";            
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
                $lowestPrice, // TODO: jedno z tech dvou je spatne, ale mozna se to lisi dle typu slevy... zjistit
                $row["final_max_sleva"],
                $row["min_castka"],
                Serial_collection::get_nights($row), 
                Serial_library::get_typ_stravy($row["strava"]-1), 
                Serial_library::get_typ_dopravy($row["doprava"]-1), 
                Serial_library::get_typ_ubytovani($row["ubytovani"]-1),             
                Serial_collection::get_destinace($row),
                "//slantour.cz/foto/nahled/".$row["foto_url"], 
                $features, 
                Serial_collection::get_description($row),
                $predbeznaRegistrace,
                $posX, $posY
                    
                    );
        } catch(TypeError $e){
            // echo "wrong tour ".$row["id_zajezd"];   
            // echo "<br>";
            // echo "nazev ". Serial_collection::get_nazev($row);
            // echo "<br>";
            // echo "nazev web ".$row["nazev_web"];
            // echo "<br>";
            // echo "nazev typ ". $row["nazev_typ"];
            // echo "<br>";
            // echo "id zajezd ".$row["id_zajezd"];
            // echo "<br>";
            // echo "get dates ".Serial_collection::get_dates($row);
            // echo "<br>"; 
            // echo "total dates ".$totalDates; 
            // echo "<br>";
            // echo "all dates ".$allDates;
            // echo "<br>";
            // echo "min castka ".$row["min_castka"]; // TODO: jedno z tech dvou je spatne, ale mozna se to lisi dle typu slevy... zjistit
            // echo "<br>";
            // echo "final sleva ".$row["final_max_sleva"];
            // echo "<br>";
            // echo "min castka ".$row["min_castka"];
            // echo "<br>";
            // echo "get nights ".Serial_collection::get_nights($row); 
            // echo "<br>";
            // echo "get strava ".Serial_library::get_typ_stravy($row["strava"]-1); 
            // echo "<br>";
            // echo "get typ dopravy ".Serial_library::get_typ_dopravy($row["doprava"]-1); 
            // echo "<br>";
            // echo "get typ ubytovani ".Serial_library::get_typ_ubytovani($row["ubytovani"]-1);  
            // echo "<br>";           
            // echo "get dstinace ".Serial_collection::get_destinace($row);
            // echo "<br>";
            // echo "foto url "."//slantour.cz/foto/full/".$row["foto_url"]; 
            // echo "<br>";
            // echo "featuress ".$features; 
            // echo "<br>";
            // echo "get desc ".Serial_collection::get_description($row);
            // echo "<br>";
            // echo "registrace ".$predbeznaRegistrace;
            // echo "<br>";
            // echo "posX ".$posX . $pos;
            // echo "<br>";
            // print_r($e);
            // echo "<br><br>";
            //tohle by melo zachytit spatne vyplnene zajezdy
        }
    }
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

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
    public int $predbeznaRegistrace;
    public $posX;
    public $posY;
            

    public function __construct( string $name, string $escapedName, string $type, int $id_zajezd, string $dates, int $totalOtherDates, array $allDates, int $price, int $priceDiscount, int $priceOriginal, string $nights, string $meals,string $transport,string $accomodation, string $destination, string $image, array $features, string $description, int $predbeznaRegistrace, $posX, $posY)
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
        $this->predbeznaRegistrace = $predbeznaRegistrace;
        $this->posX = $posX;
        $this->posY = $posY;
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
