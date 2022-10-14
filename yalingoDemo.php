<?php
require_once 'vendor/autoload.php';
use \YaLinqo\Enumerable;


require_once "./core/load_core.inc.php"; 
require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_collection.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu

$serialCol = new Serial_collection();

$res = $serialCol->get_zajezdy();
$zajezdyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);

#print_r($zajezdyArr);
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
//->where('$zaj ==> matches($zaj["nazev"],"\Hotel.*\")')
$result = from($zajezdyArr)
        ->where('$zaj ==> ($zaj["ubytovani"] == 7 and $zaj["doprava"] == 1 )')
        ->orderBy('$zaj ==> $zaj["od"]')
        ->thenBy('$zaj ==> $zaj["do"]')
        ->select('$zaj ==> array("id_serial" => $zaj["id_serial"], "nazev" => $zaj["nazev"], "id_zajezd" => $zaj["id_zajezd"], "od" => $zaj["od"], "do" => $zaj["do"], "ubytovani" => $zaj["ubytovani"], "doprava" => $zaj["doprava"])');

$r0 = $result->toArrayDeep();


$r1 = from($r0)->select('$zaj ==> $zaj["id_zajezd"]');
$r2 = from($r0)->distinct('$zaj ==> $zaj["id_serial"]')->select('$zaj ==> $zaj["id_serial"]');
$r3 = from($r0)->groupBy('$zaj ==> $zaj["id_serial"]')->count();


print_r($r1->toArray());
print_r($r2->toList());
print_r($r3->toList());
print_r($r0);