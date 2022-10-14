<?php
require_once 'vendor/autoload.php';
use \YaLinqo\Enumerable;


require_once "./core/load_core.inc.php"; 
require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_collection.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu

$serialCol = new Serial_collection();

#get portion of data with zajezdy
$res = $serialCol->get_zajezdy();
$zajezdyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);

#TODO: zajezdy filters
$zajezdyFilters = array(
    '($z["od"] >= "2022-12-31" or ($z["do"] >= "2022-12-31" and $z["dlouhodobe_zajezdy"] == 1) )',
    '$z["od"] <= "2023-05-31"',    
    '($z["do"] <= "2023-05-31" or ($z["od"] <= "2023-05-31" and $z["dlouhodobe_zajezdy"] == 1) )',
    '$z["do"] >= "2022-12-31"',  
    '($z["strava"] == 1 or $z["strava"] == 2)'
);

$zajezdyFiltered = from($zajezdyArr)->where('$z ==>'.implode(" and ",$zajezdyFilters))->toArray();     

#print_r(array_slice($zajezdyFiltered, 1, 3));

#get all relevant zajezdy and serialy IDs
$zajezdyIDs = from($zajezdyFiltered)->select('$zaj ==> $zaj["id_zajezd"]')->toList();
$serialyIDs = from($zajezdyFiltered)->distinct('$zaj ==> $zaj["id_serial"]')->select('$zaj ==> $zaj["id_serial"]')->toList();


#get portion of data with zeme & destinace
$res = $serialCol->get_zeme_a_destinace_serialu($serialyIDs);
$zemeArr = mysqli_fetch_all($res, MYSQLI_ASSOC);

$zemeFilters = array(
    '($z["nazev_zeme_web"] == "francie" or $z["nazev_zeme_web"] == "ceska-republika")'
);
$zemeFiltered = from($zemeArr)->where('$z ==>'.implode(" and ",$zemeFilters))->toArray();    



$zajezdZemeMerged = from($zajezdyFiltered).groupJoin(
        from($zemeFiltered),
        '$z ==> $z["id_serial"]',
        '$zm ==> $zm["id_serial"]',
        '($z, $zm) ==> ["zajezd" => $z,"zeme" => $zm]');

print_r(array_slice($zajezdZemeMerged, 1, 5));

#get portion of data with ceny
$res = $serialCol->get_ceny_zajezdu($zajezdyIDs);
$cenyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);

$cenyFilters = array(
    '$c["vyprodano"] == 0',
    '$c["castka"] >= 100',
    '$c["castka"] <= 1500'
);

$cenyFiltered = from($cenyArr)->where('$c ==>'.implode(" and ",$cenyFilters))->toArray();              
print_r(array_slice($cenyFiltered, 1, 3));




print_r($zemeArr[0]);

#print_r($zajezdyArr);
// Data

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
$r3 = from($r0)->groupBy('$zaj ==> $zaj["id_serial"]')->toArrayDeep();

foreach($r3 as $id => $val){
    print_r($val);
    $count = from($val)->count();
    echo $id.": ".$count;    
}
/*
        ->select('$n ==> array(
                               "id_serial" => $n["id_serial"],
                               "count" => $n->count()
                          )');
*/

print_r($r1->toArray());
print_r($r2->toList());

print_r($r3);