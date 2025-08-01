<?php
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();

session_start();
require_once "./core/load_core.inc.php"; 

require_once "./classes/serial.inc.php"; //seznam serialu

if($_GET["lev1"]!=""){
    $_GET["ubytovani"] = $_GET["lev1"];
}
$serial = new Serial_ubytovani($_GET["ubytovani"]);
$ubytovani_data = $serial->get_data();
$ubytovani_id = $ubytovani_data["id_ubytovani"];
if($ubytovani_id > 0){
    header("Location: /vyhledavani?katalogFilter[]=" . urlencode($ubytovani_id), true, 301);
    exit;
} else {
    // pokud neni ubytovani nalezeno, presmerujeme na vyhledavani
    header("Location: /vyhledavani", true, 301);
    exit;
}