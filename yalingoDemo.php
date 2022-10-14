<?php
require_once 'vendor/autoloader.php';
use \YaLinqo\Enumerable;

// 'from' can be called as a static method or via a global function shortcut
Enumerable::from(array(1, 2, 3));
from(array(1, 2, 3));


// Data
$products = array(
    array('name' => 'Keyboard',    'catId' => 'hw', 'quantity' =>  10, 'id' => 1),
    array('name' => 'Mouse',       'catId' => 'hw', 'quantity' =>  20, 'id' => 2),
    array('name' => 'Monitor',     'catId' => 'hw', 'quantity' =>   0, 'id' => 3),
    array('name' => 'Joystick',    'catId' => 'hw', 'quantity' =>  15, 'id' => 4),
    array('name' => 'CPU',         'catId' => 'hw', 'quantity' =>  15, 'id' => 5),
    array('name' => 'Motherboard', 'catId' => 'hw', 'quantity' =>  11, 'id' => 6),
    array('name' => 'Windows',     'catId' => 'os', 'quantity' => 666, 'id' => 7),
    array('name' => 'Linux',       'catId' => 'os', 'quantity' => 666, 'id' => 8),
    array('name' => 'Mac',         'catId' => 'os', 'quantity' => 666, 'id' => 9),
);
$categories = array(
    array('name' => 'Hardware',          'id' => 'hw'),
    array('name' => 'Operating systems', 'id' => 'os'),
);

// Put products with non-zero quantity into matching categories;
// sort categories by name;
// sort products within categories by quantity descending, then by name.
$result = from($categories)
    ->orderBy('$cat ==> $cat["name"]')
    ->groupJoin(
        from($products)
            ->where('$prod ==> $prod["quantity"] > 0')
            ->orderByDescending('$prod ==> $prod["quantity"]')
            ->thenBy('$prod ==> $prod["name"]'),
        '$cat ==> $cat["id"]', '$prod ==> $prod["catId"]',
        '($cat, $prods) ==> array(
            "name" => $cat["name"],
            "products" => $prods
        )'
    );