<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
echo $_POST["objectID"]. $_POST["eventName"]. $_POST["eventValue"];
require_once "../public/ComponentCore.php";
ComponentCore::loadCoreEvents();

if(strpos($_POST["objectID"], ",")!==false){
    $object = explode(",", $_POST["objectID"]);
}else{
    $object = $_POST["objectID"];
}

$aggregatedEvent =  new AggregatedDataEvent($object, $_POST["eventName"], $_POST["eventValue"]);
$eHandler = new AggregatedEventHandler($aggregatedEvent);
$eHandler->saveEvent();
?>
