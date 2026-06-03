<?php

function isDBAccessNeeded($filePath) {
    //FileCreatedMoreThan15MinutesAgo
    // Check if the file exists
    //return true;
    if($_GET["recalculate"] == true){
        return true;        
    }
    if (file_exists($filePath)) {
        // Get the file's creation time
        $fileCreationTime = filectime($filePath);

        // Get the current time
        $currentTime = time();

        // Calculate the difference between current time and file creation time
        $timeDifference = $currentTime - $fileCreationTime;

        // Return true if the file was created more than 15 minutes ago (900 seconds)
        return $timeDifference > 900;
    } else {
        // File does not exist
        return true;
    }
}


require_once 'vendor/autoload.php';



require_once "./core/load_core.inc.php"; 
require_once "./classes/serial_collection.inc.php"; //seznam serialu
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();


$serialCol = new Serial_collection();

#get all possible katalog menu fragments - based on ubytovani
if(isDBAccessNeeded("data_katalog.json")){

    $katalog = getKatalogMenu();
    $jsonKatalog = json_encode($katalog);
    #TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
    file_put_contents("data_katalog.json",$jsonKatalog,LOCK_EX);
    unset($jsonKatalog);
}else{
    $jsonData = file_get_contents("data_katalog.json"); // Read JSON file
    $katalog = json_decode($jsonData, true); // Convert JSON string to PHP array

}

#get portion of data with zajezdy_group
if(isDBAccessNeeded("data_group.json")){
    $resZ = $serialCol->get_zajezdy_group();
    $zajezdyArr = mysqli_fetch_all($resZ, MYSQLI_ASSOC);
    $jsonDataZG = json_encode($zajezdyArr);
    #TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
    file_put_contents("data_group.json",$jsonDataZG,LOCK_EX);
    unset($jsonDataZG);
}
#get portion of data with zajezdy
/*

$resZ = $serialCol->get_zajezdy_base();
$zajezdyArr = mysqli_fetch_all($resZ, MYSQLI_ASSOC);
$jsonDataZ = json_encode($zajezdyArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("data.json",$jsonDataZ,LOCK_EX);
unset($jsonDataZ);

*/


//$resZZ = $serialCol->get_main_zeme_serial();
if(isDBAccessNeeded("serial_zeme.json")){
    $resZZ = $serialCol->get_all_zeme_serial();
    $zemeDB = mysqli_fetch_all($resZZ, MYSQLI_ASSOC);
    #TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
    $zemeArr = [];
    foreach ($zemeDB as $key => $z) {
        $ids = explode(",", $z["zId"]);
        $names = explode(",", $z["zName"]);
        
        $zemeArr[$z["sId"]] = ["zId"=>$ids, "zName"=>$names];    
        //$zemeArr[$z["sId"]] =  $z
    }    
    unset($zemeDB);
    $jsonDataZZ = json_encode($zemeArr);
    file_put_contents("serial_zeme.json",$jsonDataZZ,LOCK_EX);
    unset($jsonDataZZ);
}


if(isDBAccessNeeded("serial_destinace.json")){
    $resD = $serialCol->get_all_destinace_serial();
    $destDB = mysqli_fetch_all($resD, MYSQLI_ASSOC);
    $destArr = [];
    foreach ($destDB as $key => $d) {
        $destArr[$d["sId"]] = $d;  
    }
    unset($destDB);
    $jsonDataD = json_encode($destArr);
    file_put_contents("serial_destinace.json",$jsonDataD,LOCK_EX);
    unset($jsonDataD);
}



    $resT = $serialCol->get_all_tour_types();
    $tourTypesDB = mysqli_fetch_all($resT, MYSQLI_ASSOC);
    $tourTypesArr = [];
    foreach ($tourTypesDB as $key => $tt) {
        $tourTypesArr[$tt["id_typ"]] = $tt;
    }
    unset($tourTypesDB);
    $jsonDataT = json_encode($tourTypesArr);
    #TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
    file_put_contents("tour_types.json",$jsonDataT,LOCK_EX);
    unset($jsonDataT);
    //echo memory_get_peak_usage(true)."/".memory_get_usage(true)."\n";




    $res = $serialCol->get_all_zeme();
    $zemeDB = mysqli_fetch_all($res, MYSQLI_ASSOC);
    $countries = [];
    foreach ($zemeDB as $key => $z) {
        $countries[$z["id_zeme"]] = array("id_zeme"=>$z["id_zeme"],"nazev"=>$z["nazev_zeme"],"nazev_web"=>$z["nazev_zeme_web"]);
    }
    unset($zemeDB);
    $jsonDataZM = json_encode($countries);
    #TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
    file_put_contents("zeme.json",$jsonDataZM,LOCK_EX);
    unset($jsonDataZM);


$res = $serialCol->get_all_sport_zeme();
$zemeDB = mysqli_fetch_all($res, MYSQLI_ASSOC);
$sports = [];
foreach ($zemeDB as $key => $z) {
    $sports[$z["id_zeme"]] = array("id_zeme"=>$z["id_zeme"],"nazev"=>$z["nazev_zeme"],"nazev_web"=>$z["nazev_zeme_web"]);
}
unset($zemeDB);


/*
$res = $serialCol->get_all_destinace();
$destinaceDB = mysqli_fetch_all($res, MYSQLI_ASSOC);
$destinaceArr = [];
foreach ($destinaceDB as $key => $d) {
    $destinaceArr[$d["id_destinace"]] = $d;
    $destinaceArr[$d["id_destinace"]]["counter"] = 0;
}
$jsonData = json_encode($destinaceArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("destinace.json",$jsonData,LOCK_EX);

*/

//print_r($countriesMenu);





# process zajezdyArr to get initial statistics on all available data

$katalog_hier_restr = [];
$last_id = 0;
foreach ($katalog as $key => $kItem) {
    if(!array_key_exists($kItem["nazev_zeme"], $katalog_hier_restr)){
        $katalog_hier_restr[$kItem["nazev_zeme"]] = [];    
    }
    if(!array_key_exists($kItem["nazev_destinace"], $katalog_hier_restr[$kItem["nazev_zeme"]])){
        $katalog_hier_restr[$kItem["nazev_zeme"]][$kItem["nazev_destinace"]] = []; 
    }
    
    if($last_id != $kItem["id_final"]){
       $last_id = $kItem["id_final"]; 
       $katalog_hier_restr[$kItem["nazev_zeme"]][$kItem["nazev_destinace"]][] = $kItem;        
    }
}

//print_r($katalog_hier_restr);


function searchDateToNativeInput($date)
{
    $date = trim(html_entity_decode((string) $date, ENT_QUOTES, 'UTF-8'));

    if(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $match)){
        if(checkdate((int) $match[2], (int) $match[3], (int) $match[1])){
            return $match[1]."-".$match[2]."-".$match[3];
        }
    }

    if(preg_match('/^(\d{1,2})[\/.](\d{1,2})[\/.](\d{2}|\d{4})$/', $date, $match)){
        $year = strlen($match[3]) === 2 ? "20".$match[3] : $match[3];
        if(checkdate((int) $match[2], (int) $match[1], (int) $year)){
            return sprintf('%04d-%02d-%02d', (int) $year, (int) $match[2], (int) $match[1]);
        }
    }

    return "";
}

function nativeInputToSearchDate($date)
{
    if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $date, $match)){
        return "";
    }

    return $match[3]."/".$match[2]."/".substr($match[1], -2);
}

$initFilters = array();
$keywords = array("txt","dates","minPrice","maxPrice");
$keywordsArrays = array("tourTypeFilter", "transportFilter", "foodFilter", "durGroupFilter", "countryFilter", "akceFilter", "katalogFilter", "destinaceFilter");

$filter_txt = "";
$filter_dates = "";
$filter_date_from = "";
$filter_date_to = "";

foreach ($keywords as $k) {
   if(isset($_GET[$k])){
       $rawVal = $_GET[$k];
       if(is_array($rawVal)){
           $rawVal = reset($rawVal);
       }
       $strVal = trim(strip_tags((string) $rawVal));
       if($strVal === ""){
           continue;
       }
       
       if($k=="txt"){
           $filter_txt = $strVal;
       }else if($k=="dates"){
           $filter_dates = $strVal;
           continue;
       }

       $initFilters[] = $k."_".$strVal;
       
   } 
}
foreach ($keywordsArrays as $k) {
   if(isset($_GET[$k])){
       foreach((array) $_GET[$k] as $val){
           $strVal = trim(strip_tags($val));
           if($strVal !== ""){
               $initFilters[] = $k."_".$strVal;
           }
       }
   } 
}

if($filter_dates !== ""){
   $datesParts = preg_split('/\s*>\s*/', html_entity_decode($filter_dates, ENT_QUOTES, 'UTF-8'));
   if(is_array($datesParts) && count($datesParts) === 2){
       $filter_date_from = searchDateToNativeInput($datesParts[0]);
       $filter_date_to = searchDateToNativeInput($datesParts[1]);
       if($filter_date_from !== "" && $filter_date_to !== ""){
           if($filter_date_from > $filter_date_to){
               $original_filter_date_from = $filter_date_from;
               $filter_date_from = $filter_date_to;
               $filter_date_to = $original_filter_date_from;
           }

           $filter_dates = nativeInputToSearchDate($filter_date_from)." > ".nativeInputToSearchDate($filter_date_to);
           $initFilters[] = "dates_".$filter_dates;
       }else{
           $filter_dates = "";
       }
   }
}

//echo "txt".json_encode($initFilters);

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

$features = array(
    new Feature('fa-plane', 'Letecky'), 
    new Feature('fa-hotel', 'Hotel 3&#9733;'), 
    new Feature('fa-bed', '4 noci'), 
    new Feature('fa-utensils', 'Polopenze')
);

$description = 'Zájezd nás zavede do oblasti, kde se prolíná historie v podobě unikátních památek s malebnou kopcovitou krajinou, 
která utváří ráz této jedinečné oblasti Itálie. Navštívíme historické skvosty často zařazené do seznamu UNESCO, např. 
Assisi, ale i další půvabná městečka s úžasnou atmosférou. Poznávání kulturních zajímavostí proložíme koupáním na italské adriatické riviéře.';

$typesTwig = [];
foreach ($tourTypesArr as $key => $tt) {
    $typesTwig[] = new TourType($tt["id_typ"], $tt["nazev_typ"], 0, 0, "/img/".$tt["nazev_typ_web"].".png", "", "");
}

echo $twig->render('vyhledat-zajezd.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'types' => $typesTwig,
    'countries' => $countries,
    'sports' => $sports,
    'transports' => array(3=>'Letecky', 4=>'Vlakem', 2=>'Autokar', 1=>'Vlastní', 5=>'Vlastní nebo autobus'),
    'foods' => array(5=>'All-inclusive', 4=>'Plná penze', 3=>'Polopenze', 2=>'Snídaně', 1=>"Bez stravy"),
    'sales' => array(1=>'Akční nabídky', 2=>'Slevy', 3=>'Last Minute'),
    'tourLengths' => array("variabilni"=>'Variabilní', "jednodenni"=>'Jednodenní', "1-5noci"=>'1-5 nocí', "6-10noci"=>'6-10 nocí', "nad10noci"=>">10 nocí"),
    'tours' => array(
      ),
    'katalog' => $katalog_hier_restr,
    'breadcrumbs' => array(
        new Breadcrumb('Vyhledat zájezdy', '/vyhledavani')
    ),
    'dates' => $filter_dates,
    'dateFrom' => $filter_date_from,
    'dateTo' => $filter_date_to,
    'txt' => $filter_txt,
    'initFilters' => json_encode($initFilters, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
]);
