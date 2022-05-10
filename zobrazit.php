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

//All dates
$dates = array();
while ($serial->get_zajezdy()->get_next_radek()) {
    $dates[] =  new TourDate(...$serial->get_zajezdy()->show_list_item("array"));
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
    if ($first) {
        $first = 0;
        $foto = $serial->get_foto()->show_list_item("url");
    }   
    
    $fotos[] = $serial->get_foto()->show_list_item("url");
    
}
if ($serial->create_foto_ubytovani()) {
    while ($serial->get_foto()->get_next_radek()) {
        $fotos[] = $serial->get_foto()->show_list_item("url");
    }
}

/*end of basic logic*/


/*Zatim neni zahrnuto v sablone, ale mohlo by*/

//$serial->get_popis_lazni("seznam");
//$serial->get_popis_strediska("seznam");
//$serial->show_map();

/*
 * smluvni podminky (lisi se dle typu zajezdu)
    echo "<div style=\"margin-top:10px;width:200px;text-align:center;\">
                <a href=\"".DOKUMENT_WEB."/dokumenty/".$serial->get_adresa_smluvni_podminky()."\">Smluvní podmínky</a><br/>
                <a href=\"".DOKUMENT_WEB."/dokumenty/3126-povinne-informace-k-zajezdu.pdf\">Povinné informace k zájezdu</a> <br/>
                <a href=\"".DOKUMENT_WEB."/dokumenty/3132-pojisteni-prehled.pdf\">Přehled pojištění</a>     
            </div>";   */

/* 
 * doplnujici informace - o destinacich apod.
    if ($serial->get_informace()->get_pocet_radku() != 0) {
        //mame nejake dokumenty
        ?>
        <div class="kontakt" style="margin-top:10px;">
            <h3>DALŠÍ INFORMACE</h3>
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
    $invalid_zajezd_text = "Zobrazili jste zájezd s prošlými termíny, vyberte prosím zájezd z naší aktuální nabídky.";
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
    'name' => $nazev,
    'priceFrom' => $minPrice,
    'priceDiscount' => $maxDiscount,
    'nights' => 4, //TODO: tohle bude u serialu slozitejsi - pocet noci muze byt v ramci terminu variabilni - pro zajezd to zvladnu urcit z terminu
    'accomodation' => Serial_library::get_typ_ubytovani($serial->serial["ubytovani"]-1),
    'meals' => Serial_library::get_typ_stravy($serial->serial["strava"]-1),
    'destination' => $location,
    'trans' => Serial_library::get_typ_dopravy($serial->serial["doprava"]-1),
    'imageMain' => $foto, //TODO: u fotek by to chtelo pridat minimalne description - zatim jsem nechtel hrabat do struktury...
    'images' => $fotos,
    'features' => array(
        new Feature('fa-plane', Serial_library::get_typ_dopravy($serial->serial["doprava"]-1)), 
        new Feature('fa-hotel', Serial_library::get_typ_ubytovani($serial->serial["ubytovani"]-1)),         
        new Feature('fa-utensils', Serial_library::get_typ_stravy($serial->serial["strava"]-1)),
        //new Feature('fa-bed', '4 noci'), 
        //new Feature('fa-umbrella-beach', 'Na pláži'),
        //new Feature('fa-person-swimming', 'Bazén'),
        //new Feature('fa-wifi', 'Wifi')
        //To zakomentovane zatim neumime na nic namapovat - mozna pres $serial->get_highlights(), ale tam aktualne chybi mapovani na ikony
    ),
    'descriptionMain' => 'Rimini je nejznámější i nejstarší letovisko na Adriatické riviéře. Nabízí kromě širokých pláží s jemným pískem o délce více než 15 km i pestrou možnost zábavy, nákupů či promenád. Na vlastní město Rimini kontinuálně navazuje řada menších letovisek, odlišených jen místními názvy - např. Miramare či Torre Pedrera di Rimini aj. Hotel Delfin se nachází na krásném ostrově Hvar. Je situován na nádherné promenáděa nabízí výhled na přístav a centrum města. OD centra města je hotel vzdálen jen 300 m a od pláže cca 100 m. K vybavení hotelu patří recepce, směnárna, restaurace s terasou, aperitiv bar, parkoviště.',
    'descriptionMeals' => $serial->get_popis_stravovani(),
    'descriptionAccomodation' => $serial->get_popis_ubytovani(),
    'descriptionDetails' => $serial->get_popis(),
    'descriptionNotes' => $pozn,
    
    'notIncluded' => array($serial->get_cena_nezahrnuje()), //tady je to mapovano zatim dost nedokonale (v originale je to proste textove pole, casem muzem zkusit text-based mapovani)
        /*array('autokarová doprava', 'Pobytová taxa - 2 Euro/osoba/den. (osoby starší 14 let)', 'neco dalsiho', 'neco dalsiho ale delsiho', 'neco jeste jineho', 'neco jeste jineho 2', 'neco jeste jineho 3'),*/
    'included' => array($serial->get_cena_zahrnuje()),
    'descriptionProgram' => $serial->get_program_zajezdu(),
    /*
     * Zatim neumim namapovat
     * 'program'  => array(
        new Program('Odlet Praha - Londýn', 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.', '/img/dovolena.jpg'), 
        new Program('Historické centrum Londýna', 'Dopoledne na vás čeká prohlídka tradičních míst historického centra Londýna:  Královská čtvrť Westminster - Westminster Abbey, Houses of Parliament s věží Big Ben. Slavnostní ceremonie střídání králov­ských gard na Whitehall či u Buckinghamského paláce. Trafalgar Square s nádherně nasvíceným vánočním stromem a zastavíme se i na populárním náměstí Picadilly Circus. Během prohlídky centra Londýna, která je plánována pěšky i místní dopravou, vyzkoušíte nejen londýnské metro, ale také i populární londýnské doubledeckery. Odpoledne se pak vydáte na proslulý Camden Town. Camden Town je neuvěřitelné rozsáhlá nákupní čtvrť v severní části Londýna. Právě sem chodí nakupovat londýňané!', '/img/dovolena.jpg'), 
        new Program('Návštěva OXFORD Street', 'nejproslulejší nákupní ulice Londýna.Najdete zde jak luxusní obchodní dům HARRODS, tak i atraktivními cenami známý PRIMARK či typicky anglický MARKS and SPENCER.  Dále pak zamíříte do Hyde Parku na proslulé WINTER WONDER LAND - tedy londýnské vánoční trhy s řadou atrakcí i pestrého občerstvení i nápojů. Projdete se rovněž  proslulými čtvrtěmi Soho a China Town. Později odpoledne pak zamíříte k Toweru a na Tower Bridge. Dále se vydáte na Londýnské oko. Navštívit můžete i blízké akvárium.  V některé z restaurací na břehu Temže pak můžete ochutnat populární fish and chips.', ""), 
        new Program('Soho a China Town', 'Dopoledne se můžete vydáte k  návštěvě těch míst a muzeí, které jste během prvních dní ještě navštívit nestihli  (v doprovodu průvodce či samostatně). Navštívit můžete muzeum voskových figurín Madame Tussaud´s případně  rozsáhlé Britské muzeum. Nebo si na Baker Street zajdete na návštěvu k Sherlocku Holmesovi (zda bude doma nemůžeme garantovat).  Projdete se rovněž pro proslulé Oxford Street a nevynecháte ani pověstné Soho a Čínskou čtvrť. Odpoledne odjezd na letiště a odlet  zpět do Prahy.', '/img/dovolena.jpg')
    ),*/
    'dates'  => $dates,
    /*TODO: aktualne u priceMap chybi detailni popis ktery je u nekterych cen (treba jake jsou tam konkretni lety, pripadne fotky konkretniho hotelu*/
    /*array(
        new TourDate('22.04. - 25.04.2022', 16900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.'), 
        new TourDate('11.05. - 16.05.2022', 15900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.'), 
        new TourDate('22.07. - 25.07.2022', 17900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.'), 
        new TourDate('22.08. - 25.08.2022', 17900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.')
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
    public string $date;
    public int $price;
    public string $discount;
    public string $details;
    public array $priceTable;

    public function __construct(string $date, int $price, string $discount, string $details, array $priceTable) {
        $this->date = $date;
        $this->price = $price;
        $this->discount = $discount;
        $this->details = $details;
        $this->priceTable = $priceTable;
    }
}

/*
priklady ikon

ubytovani:
new Feature('fa-hotel', 'Hotel'), 
new Feature('fa-campground', 'Stan'), 
new Feature('fa-house', 'Penzion'), 
new Feature('fa-building', 'Apartman'), 
new Feature('fa-spa', 'Lazeňský dům'), 
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
new Feature('fa-umbrella-beach', 'Na pláži'),
new Feature('fa-person-swimming', 'Bazén'),
new Feature('fa-wifi', 'Wifi')

*/