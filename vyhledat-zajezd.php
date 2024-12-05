<?php


require_once 'vendor/autoload.php';



require_once "./core/load_core.inc.php"; 
require_once "./classes/serial_collection.inc.php"; //seznam serialu
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();


$serialCol = new Serial_collection();

#get all possible katalog menu fragments - based on ubytovani
$katalog = getKatalogMenu();
$jsonKatalog = json_encode($katalog);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("data_katalog.json",$jsonKatalog,LOCK_EX);
unset($jsonKatalog);


#get portion of data with zajezdy_group
$resZ = $serialCol->get_zajezdy_group();
$zajezdyArr = mysqli_fetch_all($resZ, MYSQLI_ASSOC);
$jsonDataZG = json_encode($zajezdyArr);
#TODO: dodelat nacitani z DB jen obcas - jinak nacitat z toho jsonu
file_put_contents("data_group.json",$jsonDataZG,LOCK_EX);
unset($jsonDataZG);

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
$jsonDataZM = json_encode($zemeArr);
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


$initFilters = array();
$keywords = array("txt","dates","minPrice","maxPrice");
$keywordsArrays = array("tourTypeFilter","transportFilter","foodFilter","durGroupFilter","countryFilter","akceFilter");

$filter_txt = "";
$filter_dates = "";

foreach ($keywords as $k) {
   if(isset($_GET[$k])){
       $strVal = htmlspecialchars(strip_tags($_GET[$k]));
       $initFilters[] = $k."_".$strVal;     
       
       if($k=="txt"){
           $filter_txt = $strVal;
       }else if($k=="dates"){
           $filter_dates = $_GET[$k];
       }
       
   } 
}
foreach ($keywordsArrays as $k) {
   if(isset($_GET[$k])){
       foreach($_GET[$k] as $val){ 
            $strVal = htmlspecialchars(strip_tags($val));
            $initFilters[] = $k."_".$strVal;  
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
        new Breadcrumb('Zájezdy', '../vyhledat-zajezd.php')
    ),
    'dates' => $filter_dates,
    'txt' => $filter_txt,
    'initFilters' => json_encode($initFilters)
]);