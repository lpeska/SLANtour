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
]);


class Feature {
    public string $icon;
    public string $text;

    public function __construct(string $icon, string $text) {
        $this->icon = $icon;
        $this->text = $text;
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