<?php
use Pinq\ITraversable,
    Pinq\Traversable;

require_once 'vendor/autoload.php';



//print_r(get_declared_classes());
// Get the contents of the JSON file 
$strJsonFileContents = file_get_contents("allData.json");
// Convert to array 
$allDataJson = json_decode($strJsonFileContents, true);

$dataSimple = Traversable::from([0,1,2,3,4,5,6,7,8,9]);

$allData = Traversable::from($allDataJson);

foreach($dataSimple->where(function ($i) { return $i % 2 === 0; }) as $number) {
    echo $number;//2, 4, 6, 8...
}

foreach($allData->where(function ($row) { return $row['id_serial'] == "7847"; }) as $r) {
    print_r($r);
}

$slevy = $allData->where(function ($row) { return $row['final_max_sleva'] > 0; });

$groupedSlevy = $slevy 
        -> groupBy(function($row){return $row['strava']; })
        ->select(function(ITraversable $slevy){return ['strava' => $slevy->first()['strava'], 'count' => $slevy->count()];}
    );        

foreach($groupedSlevy as $g){    
    print_r($g);
}

 
    
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

