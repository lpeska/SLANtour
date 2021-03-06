<?php
require_once 'vendor/autoload.php';

//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/informace_destinace.inc.php"; //menu katalogu

require_once "./classes/serial.inc.php"; //seznam serialu

require_once "./classes/serial_zajezd.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_fotografie.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_dokument.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_informace.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_ceny.inc.php"; //seznam zajezdu serialu
require_once "./classes/zajezd_ubytovani.inc.php"; //seznam serialu
require_once "./classes/zajezd_topologie.inc.php"; //seznam serialu
require_once "./classes/rezervace_dotaz.inc.php"; //seznam serialu	

require_once "./classes/blackdays_list.inc.php"; //black days
/* vytvoreni instance serialu (nebo serialu se zajezdem) */


/*basic logic from .htaccess*/

if ($_GET["lev4"] != "") { //mame velmi pravdepodobne stary serial+zajezd
    $adresa = "/zajezdy/zobrazit/" . $_GET["lev3"] . "/" . $_GET["lev4"];
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: http://" . $_SERVER['HTTP_HOST'] . $adresa);
    exit;
} else if ($_GET["lev3"] != "") { //mame velmi pravdepodobne stary serial	
    $adresa = "/zajezdy/zobrazit/" . $_GET["lev3"];
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: http://" . $_SERVER['HTTP_HOST'] . $adresa);
    exit;
} else if ($_GET["lev2"] != "") { //mame velmi pravdepodobne stary
    // print_r($_GET);
    //mame jak zajezd, tak i konkretni termin
    $serial = new Serial_with_zajezd($_GET["lev1"], $_GET["lev2"]);

    $nazev_serialu = $_GET["lev1"];
    $_GET["id_serial"] = $serial->get_id_serial();
    $zobrazit_zajezd = 1;
    $_GET["id_serial"] = $serial->get_id_serial();
    $_GET["id_zajezd"] = $serial->get_id_zajezd();
    $valid_zajezd = $serial->is_zajezd_valid();
    
    $serial->create_zajezdy();
    
} else if ($_GET["lev1"] != "") {
    $serial = new Serial($_GET["lev1"]);
    $nazev_serialu = $_GET["lev1"];
    $_GET["id_serial"] = $serial->get_id();
    $serial->create_zajezdy();
    /*
     * TODO: dela bordel pokud mas prilis malo zajezdu
     * if ($serial->get_zajezdy()->get_next_radek()) { //mame jeden zajezd
        $id_zajezd = $serial->get_zajezdy()->get_id_zajezd();
    }
    //mame zajezd - pokud mame jen jeden termin, rovnou ho zobrazime - pokud ne, tak zobrazime seznam terminu
    if ($serial->get_zajezdy()->get_next_radek()) { //mame druhy zajezd, spatne
        $no_reload_with_zajezd = true;
    } else {
        $no_reload_with_zajezd = 0;
    }
    $valid_zajezd = 1;
    if (!$no_reload_with_zajezd and $id_zajezd != "") { //mame prave jeden zajezd
        $_GET["lev2"] = $id_zajezd;
        $serial = new Serial_with_zajezd($_GET["lev1"], $_GET["lev2"]);
        $zobrazit_zajezd = 1;
        $valid_zajezd = $serial->is_zajezd_valid();
    }*/
} else {

    $adresa = "/";
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: http://" . $_SERVER['HTTP_HOST'] . $adresa);
    exit;
}

//echo $_GET["id_serial"].",".$_GET["id_zajezd"];

$typ_serial = $serial->get_nazev_typ(); //Not shown in template at the moment
$typ_serial_web = $serial->get_nazev_typ_web();//might be necessary for breadcrumb links - search

$zeme_nazev = $serial->get_zeme();
$zeme_nazev_web = $serial->get_nazev_zeme_web(); //might be necessary for breadcrumb links - search

$destinace_id = $serial->get_id_destinace();//might be necessary for breadcrumb links - search
$destinace_nazev = $serial->get_destinace();
$destinace_nazev_web = Serial_list::nazev_web_static($serial->get_destinace()); //might be necessary for breadcrumb links - search

$location = $zeme_nazev;

if($destinace_nazev){
    $location .= ", " . $destinace_nazev;
    
}

$ubytovani_nazev = $serial->get_nazev_ubytovani();
$ubytovani_nazev_web = $serial->get_nazev_ubytovani_web();//might be necessary for breadcrumb links - search

$serial_nazev = $serial->get_nazev_plain();
$serial_nazev_web = $serial->get_nazev_web();//might be necessary for breadcrumb links - search

$nazev = $serial->get_nazev();


$pozn = $serial->get_poznamky();
if ($zobrazit_zajezd) {
    $pozn .= $serial->get_poznamky_zajezd();
}

//var_dump($serial);

//All dates
$dates = array();
while ($serial->get_zajezdy()->get_next_radek()) {
    $tourDetails = $serial->get_zajezdy()->show_list_item("array");
    
    $services = array();
    $extras = array();
    $pickups = array();
    $details = "";
    $priceHeadline = -1;
    
    $tourPrices = new Seznam_cen($_GET["id_serial"],$tourDetails[0]);
    while($tourPrices->get_next_radek()){
        $prices = $tourPrices->show_list_item_array();
        
        //var_dump($prices);
        
        if($prices[3] <= 3){
            $services[] = new Service($prices[0], $prices[1], $prices[2]);
            if($prices[5] and $priceHeadline < 0){//dostupnasluzba
                $priceHeadline = $prices[2];                
            }
            
        }else if($prices[3] == 4){
            $extras[] = new Service($prices[0], $prices[1], $prices[2]);
        }else{
            $pickups[] = new Service($prices[0], $prices[1], $prices[2]);
        }
        
        if($prices[4]!=""){
            $details .= $prices[4];            
        }
        
    }
    

    $dates[] =  new TourDate($tourDetails[0],$tourDetails[1],$priceHeadline,$tourDetails[2],$details.$tourDetails[3],$services,$extras,$pickups);

}
//print_r($dates);

function minPrice($dates) {
    $prices = array();
    foreach ($dates as $dt) {
        $prices[] = intval($dt->price);
    }
    return min($prices);
}

function maxDiscount($dates) {
    $discounts = array();
    foreach ($dates as $dt) {
        $discounts[] = intval($dt->discount);
    }
    return max($discounts);
}

$minPrice = minPrice($dates);
$maxDiscount = maxDiscount($dates);

$serial->create_foto();
$first = 1;
$fotos = array();

while ($serial->get_foto()->get_next_radek()) {
    $ft = $serial->get_foto()->show_list_item("url");
    if ($first) {
        $first = 0;
        
        $foto = new Foto($ft[0],$ft[1]);
    }   
    
    $fotos[] = new Foto($ft[0],$ft[1]);
     
}
if ($serial->create_foto_ubytovani()) {
    while ($serial->get_foto()->get_next_radek()) {
        $ft = $serial->get_foto()->show_list_item("url");
        $fotos[] = new Foto($ft[0],$ft[1]);
    }
}

//var_dump($fotos);
/*end of basic logic*/


/*Zatim neni zahrnuto v sablone, ale mohlo by*/

//$serial->get_popis_lazni("seznam");
//$serial->get_popis_strediska("seznam");
//$serial->show_map();

/*
 * smluvni podminky (lisi se dle typu zajezdu)
    echo "<div style=\"margin-top:10px;width:200px;text-align:center;\">
                <a href=\"".DOKUMENT_WEB."/dokumenty/".$serial->get_adresa_smluvni_podminky()."\">Smluvn?? podm??nky</a><br/>
                <a href=\"".DOKUMENT_WEB."/dokumenty/3126-povinne-informace-k-zajezdu.pdf\">Povinn?? informace k z??jezdu</a> <br/>
                <a href=\"".DOKUMENT_WEB."/dokumenty/3132-pojisteni-prehled.pdf\">P??ehled poji??t??n??</a>     
            </div>";   */

/* 
 * doplnujici informace - o destinacich apod.
    if ($serial->get_informace()->get_pocet_radku() != 0) {
        //mame nejake dokumenty
        ?>
        <div class="kontakt" style="margin-top:10px;">
            <h3>DAL???? INFORMACE</h3>
            <ul style="list-style: none;">
                <?php
                $i = 0;
                while ($serial->get_informace()->get_next_radek()) {
                    $popis_strediska .= $serial->get_informace()->get_info_o_stredisku();
                    $popis_lazni .= $serial->get_informace()->get_zamereni_lazni();
                    if ($i == 0) {
                        $i++;
                        echo $serial->get_informace()->show_list_item("first");
                    } else {
                        echo $serial->get_informace()->show_list_item("seznam");
                    }
                }
                ?>
            </ul>
        </div>
 *  */

/*
*blackdays - zabrane terminy u dlouhodobych zajezdu. Nevim jestli se aktualne pouzivaji - zeptat se taty
 $blackdays = new Blackdays_list($_GET["lev2"]);
 if (!$blackdays->isEmpty()) {
        echo $blackdays->show_list();
    }
 */

/* 
 * kontrola zda se jedna o stale validni zajezd (jinak nechceme zobrazit objednavku a asi ani seznam terminu
if(!$valid_zajezd){
    $invalid_zajezd_text = "Zobrazili jste z??jezd s pro??l??mi term??ny, vyberte pros??m z??jezd z na???? aktu??ln?? nab??dky.";
}
 *  */

/* predregistrace zajemcu (zeptej se kdyztak taty o co jde - typicky nechceme nechat zobrazit objednavku ale predregistracni formular)
if ($serial->get_id_sablony_zobrazeni() != 8) {...}
 *  */
/*konec Zatim neni zahrnuto v sablone, ale mohlo by*/

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zajezd.html.twig', [
    'dateID' => $_GET["id_zajezd"],
    'name' => $nazev,
    'priceFrom' => $minPrice,
    'priceDiscount' => $maxDiscount,
    'nights' => 4, //TODO: tohle bude u serialu slozitejsi - pocet noci muze byt v ramci terminu variabilni - pro zajezd to zvladnu urcit z terminu
    'accomodation' => Serial_library::get_typ_ubytovani($serial->serial["ubytovani"]-1),
    'meals' => Serial_library::get_typ_stravy($serial->serial["strava"]-1),
    'destination' => $location,
    'trans' => Serial_library::get_typ_dopravy($serial->serial["doprava"]-1),
    'imageMain' => $foto,
    'images' => $fotos,
    'features' => array(
        new Feature('fa-plane', Serial_library::get_typ_dopravy($serial->serial["doprava"]-1)), 
        new Feature('fa-hotel', Serial_library::get_typ_ubytovani($serial->serial["ubytovani"]-1)),         
        new Feature('fa-utensils', Serial_library::get_typ_stravy($serial->serial["strava"]-1)),
        //new Feature('fa-bed', '4 noci'), 
        //new Feature('fa-umbrella-beach', 'Na pl????i'),
        //new Feature('fa-person-swimming', 'Baz??n'),
        //new Feature('fa-wifi', 'Wifi')
        //To zakomentovane zatim neumime na nic namapovat - mozna pres $serial->get_highlights(), ale tam aktualne chybi mapovani na ikony
    ),
    'descriptionMain' => strip_tags($serial->get_popisek()),  //TODO: tady by to spis chtelo ty tagy umoznit normalne zobrazit, stale dela problem treba &nbsp;
    'descriptionMeals' => $serial->get_popis_stravovani(),
    'descriptionAccomodation' => $serial->get_popis_ubytovani(),
    'descriptionDetails' => $serial->get_popis(),
    'descriptionNotes' => $pozn,
    
    'notIncluded' => array($serial->get_cena_nezahrnuje()), //tady je to mapovano zatim dost nedokonale (v originale je to proste textove pole, casem muzem zkusit text-based mapovani)
        /*array('autokarov?? doprava', 'Pobytov?? taxa - 2 Euro/osoba/den. (osoby star???? 14 let)', 'neco dalsiho', 'neco dalsiho ale delsiho', 'neco jeste jineho', 'neco jeste jineho 2', 'neco jeste jineho 3'),*/
    'included' => array($serial->get_cena_zahrnuje()),
    'descriptionProgram' => $serial->get_program_zajezdu(),
    'contractLink'  => 'https://www.slantour.cz/dokumenty/'.$serial->get_adresa_smluvni_podminky().'',
    'infoLink'  => 'https://www.slantour.cz/dokumenty/3126-povinne-informace-k-zajezdu.pdf',
    'insuranceLink'  => 'https://www.slantour.cz/dokumenty/3132-pojisteni-prehled.pdf',
    
    "mapContent" => $serial->show_map(),
    /*
     * Zatim neumim namapovat
     * 'program'  => array(
        new Program('Odlet Praha - Lond??n', 'Dopoledne odlet z Prahy do Lond??na. Odpoledne ubytov??n?? v hotelu a d??le n??v??t??va proslul??ho Notting Hillu. Projdete se trhem, kter?? zn??te ze stejnojmen??ho filmu s Juli?? Roberts a Hugh Grantem. Ve??er pak m????ete zam????it do n??kter??ho z typick??ch anglick??ch pub??.', '/img/dovolena.jpg'), 
        new Program('Historick?? centrum Lond??na', 'Dopoledne na v??s ??ek?? prohl??dka tradi??n??ch m??st historick??ho centra Lond??na:  Kr??lovsk?? ??tvr?? Westminster - Westminster Abbey, Houses of Parliament s v?????? Big Ben. Slavnostn?? ceremonie st????d??n?? kr??lov??sk??ch gard na Whitehall ??i u Buckinghamsk??ho pal??ce. Trafalgar Square s n??dhern?? nasv??cen??m v??no??n??m stromem a zastav??me se i na popul??rn??m n??m??st?? Picadilly Circus. B??hem prohl??dky centra Lond??na, kter?? je pl??nov??na p????ky i m??stn?? dopravou, vyzkou????te nejen lond??nsk?? metro, ale tak?? i popul??rn?? lond??nsk?? doubledeckery. Odpoledne se pak vyd??te na proslul?? Camden Town. Camden Town je neuv????iteln?? rozs??hl?? n??kupn?? ??tvr?? v severn?? ????sti Lond??na. Pr??v?? sem chod?? nakupovat lond????an??!', '/img/dovolena.jpg'), 
        new Program('N??v??t??va OXFORD Street', 'nejproslulej???? n??kupn?? ulice Lond??na.Najdete zde jak luxusn?? obchodn?? d??m HARRODS, tak i atraktivn??mi cenami zn??m?? PRIMARK ??i typicky anglick?? MARKS and SPENCER.  D??le pak zam??????te do Hyde Parku na proslul?? WINTER WONDER LAND - tedy lond??nsk?? v??no??n?? trhy s ??adou atrakc?? i pestr??ho ob??erstven?? i n??poj??. Projdete se rovn????  proslul??mi ??tvrt??mi Soho a China Town. Pozd??ji odpoledne pak zam??????te k Toweru a na Tower Bridge. D??le se vyd??te na Lond??nsk?? oko. Nav??t??vit m????ete i bl??zk?? akv??rium.  V n??kter?? z restaurac?? na b??ehu Tem??e pak m????ete ochutnat popul??rn?? fish and chips.', ""), 
        new Program('Soho a China Town', 'Dopoledne se m????ete vyd??te k  n??v??t??v?? t??ch m??st a muze??, kter?? jste b??hem prvn??ch dn?? je??t?? nav??t??vit nestihli  (v doprovodu pr??vodce ??i samostatn??). Nav??t??vit m????ete muzeum voskov??ch figur??n Madame Tussaud??s p????padn??  rozs??hl?? Britsk?? muzeum. Nebo si na Baker Street zajdete na n??v??t??vu k Sherlocku Holmesovi (zda bude doma nem????eme garantovat).  Projdete se rovn???? pro proslul?? Oxford Street a nevynech??te ani pov??stn?? Soho a ????nskou ??tvr??. Odpoledne odjezd na leti??t?? a odlet  zp??t do Prahy.', '/img/dovolena.jpg')
    ),*/
    'dates'  => $dates,
    /*array(
        new TourDate('22.04. - 25.04.2022', 16900, 'Dopoledne odlet z Prahy do Lond??na. Odpoledne ubytov??n?? v hotelu a d??le n??v??t??va proslul??ho Notting Hillu. Projdete se trhem, kter?? zn??te ze stejnojmen??ho filmu s Juli?? Roberts a Hugh Grantem. Ve??er pak m????ete zam????it do n??kter??ho z typick??ch anglick??ch pub??.'), 
        new TourDate('11.05. - 16.05.2022', 15900, 'Dopoledne odlet z Prahy do Lond??na. Odpoledne ubytov??n?? v hotelu a d??le n??v??t??va proslul??ho Notting Hillu. Projdete se trhem, kter?? zn??te ze stejnojmen??ho filmu s Juli?? Roberts a Hugh Grantem. Ve??er pak m????ete zam????it do n??kter??ho z typick??ch anglick??ch pub??.'), 
        new TourDate('22.07. - 25.07.2022', 17900, 'Dopoledne odlet z Prahy do Lond??na. Odpoledne ubytov??n?? v hotelu a d??le n??v??t??va proslul??ho Notting Hillu. Projdete se trhem, kter?? zn??te ze stejnojmen??ho filmu s Juli?? Roberts a Hugh Grantem. Ve??er pak m????ete zam????it do n??kter??ho z typick??ch anglick??ch pub??.'), 
        new TourDate('22.08. - 25.08.2022', 17900, 'Dopoledne odlet z Prahy do Lond??na. Odpoledne ubytov??n?? v hotelu a d??le n??v??t??va proslul??ho Notting Hillu. Projdete se trhem, kter?? zn??te ze stejnojmen??ho filmu s Juli?? Roberts a Hugh Grantem. Ve??er pak m????ete zam????it do n??kter??ho z typick??ch anglick??ch pub??.')
    ),*/
]);


class Feature {
    public string $icon;
    public string $text;

    public function __construct(string $icon, string $text) {
        $this->icon = $icon;
        $this->text = $text;
    }
}

class Foto {
    public string $url;
    public string $description;

    public function __construct(string $url, string $description) {
        $this->url = $url;
        $this->description = $description;
    }
}

class Program {
    public string $title;
    public string $description;
    public string $image;

    public function __construct(string $title, string $description, string $image) {
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
    }
}

class TourDate {
    public int $dateID;
    public string $date;
    public int $price;
    public string $discount;
    public string $details;
    public array $services;
    public array $extraFees;
    public array $pickupSpots;

    public function __construct(int $dateID, string $date, int $price, string $discount, string $details, array $services, array $extraFees, array $pickupSpots) {
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

class Service {
    public string $title;
    public string $capacity;
    public int $price;

    public function __construct(string $title, string $capacity, int $price) {
        $this->title = $title;
        $this->capacity = $capacity;
        $this->price = $price;
    }
}

/*
priklady ikon

ubytovani:
new Feature('fa-hotel', 'Hotel'), 
new Feature('fa-campground', 'Stan'), 
new Feature('fa-house', 'Penzion'), 
new Feature('fa-building', 'Apartman'), 
new Feature('fa-spa', 'Laze??sk?? d??m'), 
new Feature('fa-house-chimney-window', 'Chatka'), 

doprava:
new Feature('fa-plane', 'Letecky'), 
new Feature('fa-bus-simple', 'Autokarem'), 
new Feature('fa-train', 'Vlakem'), 
new Feature('fa-car', 'Vlastni doprava'), 

strava:
new Feature('fa-champagne-glasses', 'All-inclusive'),
new Feature('fa-utensils', 'Plna penze'),
new Feature('fa-utensils', 'Polopenze'),
new Feature('fa-mug-saucer', 'Snidane'),

new Feature('fa-bed', '4 noci'), 
new Feature('fa-umbrella-beach', 'Na pl????i'),
new Feature('fa-person-swimming', 'Baz??n'),
new Feature('fa-wifi', 'Wifi')

*/