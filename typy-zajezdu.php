<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('typy-zajezdu.html.twig', [
    'typesOfTours' => array(
        new TourType('Poznávací', 139, 9900, 'img/poznavaci.png'),
        new TourType('Eurovíkendy', 62, 15900, 'img/eurovikendy.png'),
        new TourType('Dovolená u moře', 140, 7900, 'img/dovolena.png'),
        new TourType('Lázně & Wellness', 79, 3900, 'img/lazne.png'),
        new TourType('Sport', 32, 7900, 'img/sport.png'),
        new TourType('Tuzemské pobyty', 139, 9900, 'img/poznavaci.png'),
        new TourType('Fly and Drive', 140, 7900, 'img/dovolena.png'),
        new TourType('Exotické zájezdy', 79, 3900, 'img/lazne.png'),
        new TourType('Jednodenní zájezdy', 32, 7900, 'img/sport.png'),
    )
    ]);

class TourType {
    public string $name;
    public int $numberOfTours;
    public int $priceFrom;
    public string $image;

    public function __construct(string $name, int $numberOfTours, int $priceFrom, string $image) {
        $this->name = $name;
        $this->numberOfTours = $numberOfTours;
        $this->priceFrom = $priceFrom;
        $this->image = $image;
        
    }
}