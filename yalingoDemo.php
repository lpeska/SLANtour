<?php
require_once 'vendor/autoload.php';
use \YaLinqo\Enumerable;

$time0 = microtime();
require_once "./core/load_core.inc.php"; 
require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_collection.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu

$serialCol = new Serial_collection();

#get portion of data with zajezdy
$res = $serialCol->get_zajezdy();
$zajezdyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);

$time1 = microtime();

#TODO: zajezdy filters
$zajezdyFilters = array(
    '($z["od"] >= "2022-12-31" or ($z["do"] >= "2022-12-31" and $z["dlouhodobe_zajezdy"] == 1) )',
    '$z["od"] <= "2023-05-31"',    
    '($z["do"] <= "2023-05-31" or ($z["od"] <= "2023-05-31" and $z["dlouhodobe_zajezdy"] == 1) )',
    '$z["do"] >= "2022-12-31"',  
    '($z["strava"] == 1 or $z["strava"] == 2)'
);
if(count($zajezdyFilters)>0){
    $zajezdyFiltered = from($zajezdyArr)->where('$z ==>'.implode(" and ",$zajezdyFilters))->toArray();     
}else{
    $zajezdyFiltered = $zajezdyArr;
}
$time2 = microtime();
#print_r(array_slice($zajezdyFiltered, 1, 3));

#get all relevant zajezdy and serialy IDs

$serialyIDs = from($zajezdyFiltered)->distinct('$zaj ==> $zaj["id_serial"]')->select('$zaj ==> $zaj["id_serial"]')->toList();

$filters_for_zajezd = array("strava", "doprava", "ubytovani");


$time3 = microtime();
#get portion of data with zeme & destinace
$res = $serialCol->get_zeme_a_destinace_serialu($serialyIDs);
$zemeArr = mysqli_fetch_all($res, MYSQLI_ASSOC);

$zemeFilters = array(
    '($z["nazev_zeme_web"] == "francie" or $z["nazev_zeme_web"] == "ceska-republika")'
);
$zemeFiltered = from($zemeArr)->where('$z ==>'.implode(" and ",$zemeFilters))->toArray();    



$zajezdZemeMerged = from($zajezdyFiltered)
        ->groupJoin(
                from($zemeFiltered),
                '$z ==> $z["id_serial"]',
                '$zm ==> $zm["id_serial"]',
                '($z, $zm) ==> ["id_serial" => $z["id_serial"], "id_zajezd" => $z["id_zajezd"], "zajezd" => $z,"zeme" => $zm, "countZeme" => $zm->count() ]'
              )
        ->where('$z==> $z["countZeme"]>0')->toArrayDeep();

#print_r($zajezdZemeMerged[0]);

$zajezdyIDs = from($zajezdZemeMerged)->select('$z ==> $z["zajezd"]')->select('$z ==> $z["id_zajezd"]')->toList();
$time4 = microtime();

#get portion of data with ceny
$res = $serialCol->get_ceny_zajezdu($zajezdyIDs);
$cenyArr = mysqli_fetch_all($res, MYSQLI_ASSOC);
#print_r($cenyArr[0]);
$cenyFilters = array(
    '$c["vyprodano"] == 0',
    '$c["castka"] >= 100',
    '$c["castka"] <= 1500'
);

$cenyFiltered = from($cenyArr)->where('$c ==>'.implode(" and ",$cenyFilters))->toArray();  
#print_r(array_slice($cenyFiltered, 1, 3));


$zajezdZemeCenaMerged = from($zajezdZemeMerged)
        ->groupJoin(
                from($cenyFiltered),
                '$z ==> $z["id_zajezd"]',
                '$c ==> $c["id_zajezd"]',
                '($z, $c) ==> ["zajezd" => $z["zajezd"],"zeme" => $z["zeme"],"countZeme" => $z["countZeme"], "cena" => $c, "countCena" => $c->count() ]'
              )
        ->where('$z ==> $z["countCena"]>0')->toArrayDeep();

$time5 = microtime();

echo $time1-$time0."<br/>";            
echo $time2-$time1."<br/>"; 
echo $time3-$time2."<br/>"; 
echo $time4-$time3."<br/>"; 
echo $time5-$time4."<br/>"; 

print_r($zajezdZemeCenaMerged);



/*
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


print_r($r1->toArray());
print_r($r2->toList());

print_r($r3);*/