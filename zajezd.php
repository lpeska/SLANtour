<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zajezd.html.twig', [
    'typesOfTours' => $tourTypes,
    'name' => 'HOTEL BUSIGNANI***, RIMINI RIVABELLA',
    'priceFrom' => 15900,
    'priceDiscount' => 29,
    'nights' => 5,
    'accomodation' => 'Hotel',
    'meals' => 'Polopenze',
    'destination' => 'Itálie',
    'trans' => 'Letecky',
    'imageMain' => 'img/dovolena.png',
    'images' => array('img/lazne.png', 'img/dovolena.png', 'img/eurovikendy.png', 'img/poznavaci.png', 'img/sport.png', 'img/dovolena.png'),
    'features' => array(
        new Feature('fa-plane', 'Letecky'), 
        new Feature('fa-hotel', 'Hotel 3&#9733;'), 
        new Feature('fa-bed', '4 noci'), 
        new Feature('fa-utensils', 'Polopenze'),
        new Feature('fa-umbrella-beach', 'Na pláži'),
        new Feature('fa-person-swimming', 'Bazén'),
        new Feature('fa-wifi', 'Wifi')
    ),
    'descriptionMain' => 'Rimini je nejznámější i nejstarší letovisko na Adriatické riviéře. Nabízí kromě širokých pláží s jemným pískem o délce více než 15 km i pestrou možnost zábavy, nákupů či promenád. Na vlastní město Rimini kontinuálně navazuje řada menších letovisek, odlišených jen místními názvy - např. Miramare či Torre Pedrera di Rimini aj. Hotel Delfin se nachází na krásném ostrově Hvar. Je situován na nádherné promenáděa nabízí výhled na přístav a centrum města. OD centra města je hotel vzdálen jen 300 m a od pláže cca 100 m. K vybavení hotelu patří recepce, směnárna, restaurace s terasou, aperitiv bar, parkoviště.',
    'descriptionMeals' => 'Snídaně formou bufetu, večeře výběrem ze 3 menu. Za příplatek obědy výběrem ze 3 menu.',
    'descriptionAccomodation' => 'Hotel disponuje 55 pokoji s možností přistýlky a výhledem na moře nebo park. Pokoje mají sprchu, WC, telefon. Hotel se nachází v části Rimini - Rivabella jen 70 m od pláže. Hostům je k dispozici vstupní hala s recepcí, restaurace a bar. Před hotelem je terasa se zahrádkou. Ubytování v klimatizovaných dvoulůžkových pokojích s možností 2 přistýlek, které jsou vybaveny koupelnou, fénem, TV/SAT a balkonem.',
    'descriptionDetails' => 'Koupání:  písečná pláž s pozvolným vstupem do moře se nachází jen 70 m od ubytování. Plážový servis za poplatek cca 12 - 15 EUR (slunečník, 2 lehátka).',
    'descriptionNotes' => 'Doprava je zajištěna autokarem do letoviska Rimini.

    - odjezdy jsou vždy v pátek v odpoledních hodinách
    - příjezd do Itálie je v sobotu cca v poledních hodinách
    - odjezd z Itálie je v sobotu v podvečerních hodinách od hotelu
    - příjezd do ČR v neděli v dopoledních hodinách do Prahy
    
    Doprava je zajištěna autobusem s klimatizací, TV-Video.
    Mezi nejoblíbenější výlety bezpochyby patří ten do San Marina, které je unikátní svými památkami a nádhernými horami.
    
    Pobyt je bez delegátského servisu.
    
    
    Více o letovisku Rimini též na: Bližší informace Rimini',
    'notIncluded' => array('autokarová doprava', 'Pobytová taxa - 2 Euro/osoba/den. (osoby starší 14 let)', 'neco dalsiho', 'neco dalsiho ale delsiho', 'neco jeste jineho', 'neco jeste jineho 2', 'neco jeste jineho 3'),
    'included' => array('7x ubytování', '7x polopenze'),
    'descriptionProgram' => 'Per consequat adolescens ex, cu nibh commune temporibus vim, ad sumo viris eloquentiam sed. Mea appareat omittantur eloquentiam ad, nam ei quas oportere democritum. Prima causae admodum id est, ei timeam inimicus sed. Sit an meis aliquam, cetero inermis vel ut. An sit illum euismod facilisis, tamquam vulputate pertinacia eum at.',
    'contractLink'  => 'https://www.slantour.cz/dokumenty/193-smluvni-podminky.pdf',
    'infoLink'  => 'https://www.slantour.cz/dokumenty/3126-povinne-informace-k-zajezdu.pdf',
    'insuranceLink'  => 'https://www.slantour.cz/dokumenty/3132-pojisteni-prehled.pdf',
    'program'  => array(
        new Program('1', 'Odlet Praha - Londýn', 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.', '/img/dovolena.jpg'), 
        new Program('2-3', 'Historické centrum Londýna', 'Dopoledne na vás čeká prohlídka tradičních míst historického centra Londýna:  Královská čtvrť Westminster - Westminster Abbey, Houses of Parliament s věží Big Ben. Slavnostní ceremonie střídání králov­ských gard na Whitehall či u Buckinghamského paláce. Trafalgar Square s nádherně nasvíceným vánočním stromem a zastavíme se i na populárním náměstí Picadilly Circus. Během prohlídky centra Londýna, která je plánována pěšky i místní dopravou, vyzkoušíte nejen londýnské metro, ale také i populární londýnské doubledeckery. Odpoledne se pak vydáte na proslulý Camden Town. Camden Town je neuvěřitelné rozsáhlá nákupní čtvrť v severní části Londýna. Právě sem chodí nakupovat londýňané!', '/img/dovolena.jpg'), 
        new Program('4', 'Návštěva OXFORD Street', 'nejproslulejší nákupní ulice Londýna.Najdete zde jak luxusní obchodní dům HARRODS, tak i atraktivními cenami známý PRIMARK či typicky anglický MARKS and SPENCER.  Dále pak zamíříte do Hyde Parku na proslulé WINTER WONDER LAND - tedy londýnské vánoční trhy s řadou atrakcí i pestrého občerstvení i nápojů. Projdete se rovněž  proslulými čtvrtěmi Soho a China Town. Později odpoledne pak zamíříte k Toweru a na Tower Bridge. Dále se vydáte na Londýnské oko. Navštívit můžete i blízké akvárium.  V některé z restaurací na břehu Temže pak můžete ochutnat populární fish and chips.', ""), 
        new Program('5', 'Soho a China Town', 'Dopoledne se můžete vydáte k  návštěvě těch míst a muzeí, které jste během prvních dní ještě navštívit nestihli  (v doprovodu průvodce či samostatně). Navštívit můžete muzeum voskových figurín Madame Tussaud´s případně  rozsáhlé Britské muzeum. Nebo si na Baker Street zajdete na návštěvu k Sherlocku Holmesovi (zda bude doma nemůžeme garantovat).  Projdete se rovněž pro proslulé Oxford Street a nevynecháte ani pověstné Soho a Čínskou čtvrť. Odpoledne odjezd na letiště a odlet  zpět do Prahy.', '/img/dovolena.jpg')
    ),
    'dates'  => array(
        new TourDate(
            '22.04. - 25.04.2022', 
            16900, 
            'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.',
            array(new Service('cena za osobu', 16900, 'Volno', 'Služby')),
            array(
                new Service('příplatek: pokoj 1/1', 9990, 'Volno', 'Příplatky'),
                new Service('příplatek za zajištění ESTA / os.', 500, 'Volno', 'Příplatky'),
                new Service('příplatek: komplexní pojištění - Kooperativa', 350, 'Volno', 'Příplatky'),
                new Service('transfer z letiště JFK na Manhattan od', 4300, 'Volno', 'Příplatky'),
                new Service('zavazadlo k odbavení', 1990, 'Volno', 'Příplatky')
            ),
            array(
                new Service('odlet z Prahy (základní cena)', 0, 'Volno', 'Odjezdová Místa'),
                new Service('odlet z Vídně', 0, 'Volno', 'Odjezdová Místa')
            )
        ), 
        new TourDate(
            '11.05. - 16.05.2022', 
            15900, 
            'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.',
            array(new Service('cena za osobu', 15900, 'Volno', 'Služby')),
            array(
                new Service('příplatek: pokoj 1/1', 9990, 'Volno', 'Příplatky'),
                new Service('příplatek za zajištění ESTA / os.', 500, 'Volno', 'Příplatky'),
                new Service('příplatek: komplexní pojištění - Kooperativa', 350, 'Volno', 'Příplatky'),
                new Service('transfer z letiště JFK na Manhattan od', 4300, 'Volno', 'Příplatky'),
                new Service('zavazadlo k odbavení', 1990, 'Volno', 'Příplatky')
            ),
            array(
                new Service('odlet z Prahy (základní cena)', 0, 'Volno', 'Odjezdová Místa'),
                new Service('odlet z Vídně', 0, 'Volno', 'Odjezdová Místa')
            )
        ), 
        new TourDate(
            '22.07. - 25.07.2022', 
            17900, 
            'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.',
            array(new Service('cena za osobu', 17900, 'Volno', 'Služby')),
            array(
                new Service('příplatek: pokoj 1/1', 9990, 'Volno', 'Příplatky'),
                new Service('příplatek za zajištění ESTA / os.', 500, 'Volno', 'Příplatky'),
                new Service('příplatek: komplexní pojištění - Kooperativa', 350, 'Volno', 'Příplatky'),
                new Service('transfer z letiště JFK na Manhattan od', 4300, 'Volno', 'Příplatky'),
                new Service('zavazadlo k odbavení', 1990, 'Volno', 'Příplatky')
            ),
            array(
                new Service('odlet z Prahy (základní cena)', 0, 'Volno', 'Odjezdová Místa'),
                new Service('odlet z Vídně', 0, 'Volno', 'Odjezdová Místa')
            )
        ), 
        new TourDate(
            '22.08. - 25.08.2022', 
            17900, 
            'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.',
            array(new Service('cena za osobu', 17900, 'Volno', 'Služby')),
            array(
                new Service('příplatek: pokoj 1/1', 9990, 'Volno', 'Příplatky'),
                new Service('příplatek za zajištění ESTA / os.', 500, 'Volno', 'Příplatky'),
                new Service('příplatek: komplexní pojištění - Kooperativa', 350, 'Volno', 'Příplatky'),
                new Service('transfer z letiště JFK na Manhattan od', 4300, 'Volno', 'Příplatky'),
                new Service('zavazadlo k odbavení', 1990, 'Volno', 'Příplatky')
            ),
            array(
                new Service('odlet z Prahy (základní cena)', 0, 'Volno', 'Odjezdová Místa'),
                new Service('odlet z Vídně', 0, 'Volno', 'Odjezdová Místa')
            )
        )
    ),
    'breadcrumbs' => array(
        new Breadcrumb('Pobytové zájezdy', '/zajezdy/typ-zajezdu/poznavaci-zajezdy'),
        new Breadcrumb('Španělsko', '/destinace.php'),
        new Breadcrumb('Hotel Busignani ***, Rimini Rivabella', '/zajezd.php')
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

class Feature {
    public string $icon;
    public string $text;

    public function __construct(string $icon, string $text) {
        $this->icon = $icon;
        $this->text = $text;
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

class TourDate {
    public string $date;
    public int $price;
    public string $details;
    public array $services;
    public array $extraFees;
    public array $pickupSpots;

    public function __construct(string $date, int $price, string $details, array $services, array $extraFees, array $pickupSpots) {
        $this->date = $date;
        $this->price = $price;
        $this->details = $details;
        $this->services = $services;
        $this->extraFees = $extraFees;
        $this->pickupSpots = $pickupSpots;
    }
}

class Service {
    public string $title;
    public int $price;
    public string $capacity;
    public string $type;

    public function __construct(string $title, int $price, string $capacity, string $type) {
        $this->title = $title;
        $this->price = $price;
        $this->capacity = $capacity;
        $this->type = $type;
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