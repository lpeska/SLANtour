<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('zajezd.html.twig', [
    'name' => 'Hotel Esprit***, Špindlerův Mlýn',
    'priceFro,' => 1470,
    'priceDiscount' => 29,
    'nights' => 4,
    'accomodation' => 'Hotel',
    'meals' => 'Polopenze',
    'destination' => 'Krkonoše',
    'trans' => 'Letecky',
    'imageMain' => 'img/lazne.png',
    'images' => array('img/lazne.png', 'img/lazne.png', 'img/lazne.png', 'img/lazne.png', 'img/lazne.png', 'img/lazne.png'),
    'features' => array(
        new Feature('fa-plane', 'Letecky'), 
        new Feature('fa-hotel', 'Hotel 3&#9733;'), 
        new Feature('fa-bed', '4 noci'), 
        new Feature('fa-utensils', 'Polopenze'),
        new Feature('fa-umbrella-beach', 'Na pláži'),
        new Feature('fa-person-swimming', 'Bazén'),
        new Feature('fa-wifi', 'Wifi')
    ),
    'descriptionMain' => 'Per consequat adolescens ex, cu nibh commune temporibus vim, ad sumo viris eloquentiam sed. Mea appareat omittantur eloquentiam ad, nam ei quas oportere democritum. Prima causae admodum id est, ei timeam inimicus sed. Sit an meis aliquam, cetero inermis vel ut. An sit illum euismod facilisis, tamquam vulputate pertinacia eum at.',
    'descriptionMeals' => 'Per consequat adolescens ex, cu nibh commune temporibus vim, ad sumo viris eloquentiam sed. Mea appareat omittantur eloquentiam ad, nam ei quas oportere democritum. Prima causae admodum id est, ei timeam inimicus sed. Sit an meis aliquam, cetero inermis vel ut. An sit illum euismod facilisis, tamquam vulputate pertinacia eum at.',
    'descriptionAccomodation' => 'Per consequat adolescens ex, cu nibh commune temporibus vim, ad sumo viris eloquentiam sed. Mea appareat omittantur eloquentiam ad, nam ei quas oportere democritum. Prima causae admodum id est, ei timeam inimicus sed. Sit an meis aliquam, cetero inermis vel ut. An sit illum euismod facilisis, tamquam vulputate pertinacia eum at.',
    'notIncluded' => 'Per consequat adolescens ex, cu nibh commune temporibus vim, ad sumo viris eloquentiam sed. Mea appareat omittantur eloquentiam ad, nam ei quas oportere democritum. Prima causae admodum id est, ei timeam inimicus sed. Sit an meis aliquam, cetero inermis vel ut. An sit illum euismod facilisis, tamquam vulputate pertinacia eum at.',
    'descriptionProgram' => 'Per consequat adolescens ex, cu nibh commune temporibus vim, ad sumo viris eloquentiam sed. Mea appareat omittantur eloquentiam ad, nam ei quas oportere democritum. Prima causae admodum id est, ei timeam inimicus sed. Sit an meis aliquam, cetero inermis vel ut. An sit illum euismod facilisis, tamquam vulputate pertinacia eum at.',
    'program'  => array(
        new Program('Odlet Praha - Londýn', 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.', '/img/dovolena.jpg'), 
        new Program('Historické centrum Londýna', 'Dopoledne na vás čeká prohlídka tradičních míst historického centra Londýna:  Královská čtvrť Westminster - Westminster Abbey, Houses of Parliament s věží Big Ben. Slavnostní ceremonie střídání králov­ských gard na Whitehall či u Buckinghamského paláce. Trafalgar Square s nádherně nasvíceným vánočním stromem a zastavíme se i na populárním náměstí Picadilly Circus. Během prohlídky centra Londýna, která je plánována pěšky i místní dopravou, vyzkoušíte nejen londýnské metro, ale také i populární londýnské doubledeckery. Odpoledne se pak vydáte na proslulý Camden Town. Camden Town je neuvěřitelné rozsáhlá nákupní čtvrť v severní části Londýna. Právě sem chodí nakupovat londýňané!', '/img/dovolena.jpg'), 
        new Program('Návštěva OXFORD Street', 'nejproslulejší nákupní ulice Londýna.Najdete zde jak luxusní obchodní dům HARRODS, tak i atraktivními cenami známý PRIMARK či typicky anglický MARKS and SPENCER.  Dále pak zamíříte do Hyde Parku na proslulé WINTER WONDER LAND - tedy londýnské vánoční trhy s řadou atrakcí i pestrého občerstvení i nápojů. Projdete se rovněž  proslulými čtvrtěmi Soho a China Town. Později odpoledne pak zamíříte k Toweru a na Tower Bridge. Dále se vydáte na Londýnské oko. Navštívit můžete i blízké akvárium.  V některé z restaurací na břehu Temže pak můžete ochutnat populární fish and chips.', ""), 
        new Program('Soho a China Town', 'Dopoledne se můžete vydáte k  návštěvě těch míst a muzeí, které jste během prvních dní ještě navštívit nestihli  (v doprovodu průvodce či samostatně). Navštívit můžete muzeum voskových figurín Madame Tussaud´s případně  rozsáhlé Britské muzeum. Nebo si na Baker Street zajdete na návštěvu k Sherlocku Holmesovi (zda bude doma nemůžeme garantovat).  Projdete se rovněž pro proslulé Oxford Street a nevynecháte ani pověstné Soho a Čínskou čtvrť. Odpoledne odjezd na letiště a odlet  zpět do Prahy.', '/img/dovolena.jpg')
    ),
    'dates'  => array(
        new TourDate('22.04. - 25.04.2022', 16900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.'), 
        new TourDate('11.05. - 16.05.2022', 15900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.'), 
        new TourDate('22.07. - 25.07.2022', 17900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.'), 
        new TourDate('22.08. - 25.08.2022', 17900, 'Dopoledne odlet z Prahy do Londýna. Odpoledne ubytování v hotelu a dále návštěva proslulého Notting Hillu. Projdete se trhem, který znáte ze stejnojmeného filmu s Julií Roberts a Hugh Grantem. Večer pak můžete zamířit do některého z typických anglických pubů.')
    ),
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
    public string $details;

    public function __construct(string $date, int $price, string $details) {
        $this->date = $date;
        $this->price = $price;
        $this->details = $details;
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