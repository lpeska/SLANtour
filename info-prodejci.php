<?php
require_once 'vendor/autoload.php';

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('info-prodejci.html.twig', [
    'breadcrumbs' => array(
        new Breadcrumb('Info pro prodejce', '/info-prodejci.php')
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