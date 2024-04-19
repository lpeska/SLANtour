<?php
require_once "./core/load_core.inc.php";
require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/informace_zeme.inc.php"; //seznam informaci katalogu

function getDiscountTours(String $typeName, $countryName)
{
    $discountTours = array();
    $slevy_array = array();
    $slevy_poradi = array();
    $slevy_list = new Serial_list($typeName, $countryName, "", "", "", "", "", "", "", "random", 40, "select_slevy");
    while ($slevy_list->get_next_radek()) {
        $slevyObj = $slevy_list->show_list_item("slevy_list");

        $od = $slevy_list->get_termin_od();
        $data = explode("-", $od);
        if ($dlouhodobe) {
            $dny_rozdil = 60;
        } else {
            $time_od = mktime(0, 0, 0, $data[1], $data[2], $data[0]);
            $time_now = mktime(0, 0, 0, Date("m"), Date("d"), Date("Y"));
            $dny_rozdil = ($time_od - $time_now) / 86400;

            if ($dny_rozdil < 0 or $dny_rozdil > 80) {
                //dlouhodobe zajezdy, nemaji prednost
                $dny_rozdil = 80;
            }
            if ($dny_rozdil < 5) {
                $dny_rozdil = 5;
            }
        }
        $dny_rozdil = log($dny_rozdil);
        $sleva = $slevy_list->get_max_sleva_zajezd();
        $rand = mt_rand(1, 1000000) / 500000;
        $poradi = ($sleva / $dny_rozdil) + $rand;

        if ($slevyObj["best_zajezd"] > -1) {
            $slevy_poradi[] = $poradi;
            $slevy_array[] = $slevyObj;
        }
    }
    //print_r($slevy_array);
    if (count($slevy_poradi) > 2) {

        arsort($slevy_poradi);
        $k = 0;
        foreach ($slevy_poradi as $key => $val) {
            $k++;
            $currTour = $slevy_array[$key];
            $bestTermin = $currTour["terminy"][$currTour["best_zajezd"]];
            if ($k <= 4) {
                $discountTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], "", $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"] - 1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"], array(), "");
            } else {
                break;
            }
        }
    }
    return $discountTours;
}

function getPopularTours($typeName, $countryName)
{
    $popularTours = array();
    $popular_array = array();
    $popular_zajezdy = new Serial_list($typeName, $countryName, "", "", "", "", "", "", "", "random", 10, "select_vahy");
    $i = 0;
    while ($popular_zajezdy->get_next_radek()) {
        $i++;
        $toursObj = $popular_zajezdy->show_list_item("new_tour_list");
        if ($toursObj["best_zajezd"] > -1) {
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
        if ($k <= 4) {
            $popularTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], "", $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"] - 1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"], array(), "");
        } else {
            break;
        }
    }
    return $popularTours;
}

function getNewTours($typeName, $countryName)
{
    $newTours = array();
    $novinky_array = array();
    $novinky_zajezdy = new Serial_list($typeName, $countryName, "", "", "", "", "", "", "", "random", 20, "select_nove_zajezdy");
    $i = 0;
    while ($novinky_zajezdy->get_next_radek()) {
        $i++;
        $toursObj = $novinky_zajezdy->show_list_item("new_tour_list");
        $rand = mt_rand(1, 150) / 10;
        $poradi = $i + $rand;

        if ($toursObj["best_zajezd"] > -1) {
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
        if ($k <= 4) {
            $newTours[] = new Tour($currTour["nazev"], $currTour["nazev_web"], "", $bestTermin["id_zajezd"], $bestTermin["akcni_cena"], $bestTermin["sleva"], $bestTermin["cena_pred_akci"], $bestTermin["pocet_dni"] - 1, $currTour["strava"], $currTour["lokace"], $currTour["foto_url"], $currTour["terminy"], array(), "");
        } else {
            break;
        }
    }
    return $newTours;
}

function getTourType($typeName)
{
    $menu = new Menu_katalog("dotaz_typy", "", "", "");
    $typ = $menu->get_typ_pobytu($typeName);

    //setup img for type
    $foto = getTypeImage($typ["id_typ"], $typ["foto_url"]);
    $type = new TourType($typ["id_typ"], $typ["nazev_typ"], $typ["tourCount"], $typ["tourPrice"], $foto,  $typ["description"], "/zajezdy/typ-zajezdu/" . $typ["nazev_typ_web"]);
    return $type;
}

function getAllTourTypes()
{
    /*Loading tour types*/
    $menu = new Menu_katalog("dotaz_typy", "", "", "");
    $typy = $menu->get_typy_pobytu();
    //print_r($typy);

    $tourTypes = array();
    foreach ($typy as $typ) {
        $foto = getTypeImage($typ["id_typ"], $typ["foto_url"]);
        $t = new TourType(
            $typ["id_typ"],
            $typ["nazev_typ"],
            $typ["tourCount"],
            $typ["tourPrice"],
            $foto,
            $typ["description"],
            "/zajezdy/typ-zajezdu/" . $typ["nazev_typ_web"]
        );

        $tourTypes[$typ["id_typ"]] = $t;
    }
    return $tourTypes;
}

function getCountry($countryName)
{
    $menu = new Menu_katalog("dotaz_zeme_list", "", "", "");
    $countryDB = $menu->get_zeme($countryName);
    $infoDB = new Informace_zeme("zeme", "", $countryName, "", 0, "random", 1);
    $infoDB->get_next_radek();
    
    if(strlen($infoDB->get_popisek())>0){
        $popisek = $infoDB->get_popisek();        
    }else{
        $popisek = "";
    }
    
    $country = new Country(
        $countryDB["id_zeme"],
        $countryDB["nazev_zeme"],
        $popisek,
        $countryDB["tourCount"],
        $countryDB["tourPrice"],
        "https://slantour.cz/foto/full/".$infoDB->get_foto_url(),
        "/zeme/" . $countryDB["nazev_zeme_web"]
    );
    return $country;
}

function getAllCountries()
{
    /*Loading countries*/
    $menu = new Menu_katalog("dotaz_zeme_list", "", "", "");
    $countriesDB = $menu->get_zeme_list();
    //echo print_r($countriesDB);

    $countries = array();
    foreach ($countriesDB as $country) {
        $c = new Country(
            $country["id_zeme"],
            $country["nazev_zeme"],
            "no description",
            $country["tourCount"],
            $country["tourPrice"],
            $country["foto_url"],
            "/zeme/" . $country["nazev_zeme_web"]
        );

        $countries[$country["id_zeme"]] = $c;
    }
    return $countries;
}

function getCountriesMenu()
{
    /*Loading countries*/
    $menu = new Menu_katalog("dotaz_zeme_list", "", "", "");
    $countriesDB = $menu->get_zeme_list();
    //echo print_r($countriesDB);

    $countries = array();
    $euCountries = array();
    $worldCountries = array();
    $sportCountries = array();
    $euCount = 0;
    $worldCount = 0;
    $sportCount = 0;
    foreach ($countriesDB as $country) {
        // echo $country["nazev_zeme_web"];
        // echo "</br>";
        // echo "isEu: " . isEurope($country["nazev_zeme_web"]);
        // echo "</br>";
        $c = new Country(
            $country["id_zeme"],
            $country["nazev_zeme"],
            "no description",
            $country["tourCount"],
            $country["tourPrice"],
            $country["foto_url"],
            "/zeme/" . $country["nazev_zeme_web"]
        );

        if( isEurope($country["nazev_zeme_web"])) {
            $euCountries[$country["id_zeme"]] = $c;
        } else {
            $worldCountries[$country["id_zeme"]] = $c;
        }
    }

    $menuSport = new Menu_katalog("dotaz_mozne_sporty", "", "", ""); 
    $topSportsDB = $menuSport->get_top_sports();

    foreach ($topSportsDB as $topSport) {
        // echo $topSport["nazev_zeme_web"];
        // echo "</br>";
        // echo "isEu: " . isEurope($topSport["nazev_zeme_web"]);
        // echo "</br>";
        $c = new Country(
            $topSport["id_zeme"],
            $topSport["nazev_zeme"],
            "no description",
            0,
            0,
            $topSport["foto_url"],
            "/zeme/" . $topSport["nazev_zeme_web"]
        );
        $sportCountries[$topSport["id_zeme"]] = $c;
    }

    $euCount = count($euCountries);
    $worldCount = count($worldCountries);
    $sportCount = count($sportCountries);

    $countries = array_merge(getTopCountries($euCountries, 10), getTopCountries($worldCountries, 5), array_slice($sportCountries, 0, 5));  
    // echo "</br>";
    // echo "</br>";
    // echo "merged";
    // echo "</br>";
    // echo printCountries($countries);
    // echo "</br>";
    // echo "</br>";
    
    return new CountryMenu($countries, $euCount, $worldCount, $sportCount);
}

function getTopCountries($countries, $count)
{
    // echo "start";
    // echo "</br>";
    // echo printCountries($countries);
    // echo "</br>";
    // echo "</br>";
    // echo "sorted";
    // echo "</br>";
    usort($countries, fn($a, $b) => $b->numberOfTours - $a->numberOfTours);
    // echo printCountries($countries);
    // echo "</br>";
    // echo "</br>";
    // echo "sliced";
    // echo "</br>";
    // echo printCountries(array_slice($countries, 0, $count));
    return array_slice($countries, 0, $count);
}

function printCountries($array)
{
    echo "size: " . sizeof($array);
    echo "</br>";
    foreach ($array as $c) {
        echo print_r($c);
        echo "</br>";
    }
}

function getTotalTours(array $tourTypes)
{
    $totalTours = 0;
    foreach ($tourTypes as $type) {
        $totalTours += $type->numberOfTours;
    }
    return $totalTours;
}

function getTypeImage($typeId, $defaultFoto)
{
    switch ($typeId) {
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
            $foto = $defaultFoto;
    }
    return $foto;
}

function isEurope($countryNameWeb) {
     $euCountries = array(
        "anglie",
        "belgie",
        "ceska-republika",
        "estonsko",
        "finsko",
        "francie",
        "holandsko",
        "chorvatsko",
        "irsko",
        "island",
        "italie",
        "madarsko",
        "monako",
        "nemecko",
        "norsko",
        "polsko",
        "portugalsko",
        "rakousko",
        "recko",
        "severni-irsko",
        "skotsko",
        "slovensko",
        "slovinsko",
        "spanelsko",
        "svedsko",
        "svycarsko",
        "turecko"
     );
    return in_array($countryNameWeb, $euCountries);
}

class Breadcrumb
{
    public string $label;
    public string $link;

    public function __construct(string $label, string $link)
    {
        $this->label = $label;
        $this->link = $link;
    }
}


class Tour
{
    public string $name;
    public string $escapedName;
    public string $type;
    public int $id_zajezd;
    public int $price;
    public  $priceDiscount;
    public  $priceOriginal;
    public  $nights;
    public string $meals;
    public string $destination;
    public string $image;
    public $terminy;
    public array $features;
    public string $description;

    public function __construct(string $name, string $escapedName, string $type, int $id_zajezd, int $price,  $priceDiscount,  $priceOriginal, $nights, string $meals, string $destination, string $image, $terminy = "", array $features, string $description)
    {
        $this->name = $name;
        $this->escapedName = $escapedName;
        $this->type = $type;
        $this->id_zajezd = $id_zajezd;
        $this->price = $price;
        $this->priceDiscount = $priceDiscount;
        $this->priceOriginal = $priceOriginal;
        $this->nights = $nights;
        $this->meals = $meals;
        $this->destination = $destination;
        $this->image = $image;
        $this->terminy = $terminy;
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
    public $description;
    public $url;

    public function __construct(int $id, string $name, int $numberOfTours, int $priceFrom, string $image, $description, $url)
    {
        $this->id = $id;
        $this->name = $name;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
        $this->description = $description;
        $this->url = $url;
    }
}

class News
{
    public string $title;
    public string $description;
    public string $day;
    public string $month;
    public string $image;

    public function __construct(string $title, string $description, string $day, string $month, string $image)
    {
        $this->title = $title;
        $this->description = $description;
        $this->day = $day;
        $this->month = $month;
        $this->image = $image;
    }
}

class Country
{
    public int $id;
    public string $name;
    public string $description;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;
    public $url;

    public function __construct(int $id, string $name, string $description, int $numberOfTours, int $priceFrom, string $image, $url)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
        $this->url = $url;
    }
}

class CountryMenu
{
    public array $countries;
    public int $euCount;
    public int $worldCount;
    public int $sportCount;

    public function __construct(array $countries, int $euCount, int $worldCount, int $sportCount)
    {
        $this->countries = $countries;
        $this->euCount = $euCount;
        $this->worldCount = $worldCount;
        $this->sportCount = $sportCount;
    }
}

class Feature
{
    public string $icon;
    public string $text;

    public function __construct(string $icon, string $text)
    {
        $this->icon = $icon;
        $this->text = $text;
    }
}

class Foto
{
    public string $url;
    public string $description;

    public function __construct(string $url, string $description)
    {
        $this->url = $url;
        $this->description = $description;
    }
}

class Program {
    public string $day;
    public string $title;
    public string $description;
    public string $image;

    public function __construct(string $day, string $title, string $description, string $image) {
        $this->day = $day;
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
    }
}

class Service
{
    public string $title;
    public string $capacity;
    public int $price;

    public function __construct(string $title, string $capacity, int $price)
    {
        $this->title = $title;
        $this->capacity = $capacity;
        $this->price = $price;
    }
}

class TourDate
{
    public int $dateID;
    public string $date;
    public int $price;
    public string $discount;
    public string $details;
    public array $services;
    public array $extraFees;
    public array $pickupSpots;

    public function __construct(int $dateID, string $date, int $price, string $discount, string $details, array $services, array $extraFees, array $pickupSpots)
    {
        $this->dateID = $dateID;
        $this->date = $date;
        $this->price = $price;
        $this->discount = $discount;
        $this->details = $details;
        $this->services = $services;
        $this->extraFees = $extraFees;
        $this->pickupSpots = $pickupSpots;
    }
}
