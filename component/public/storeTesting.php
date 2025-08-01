<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once "../public/ComponentCore.php";
ComponentCore::loadCoreEvents();

//print_r($_POST);

if(strpos($_POST["objectID"], ",")!==false){
    $params = "";
    $objects = explode(",", $_POST["objectID"]);
    
    foreach($objects as $key => $obj){
        $ev =  new TestingEvent($_POST["userID"], $obj, $_POST["eventName"], $_POST["eventValue"], $_POST["where"], $_POST["params"]);
        //echo $ev->getSQL();
        $eHandler = new TestingEventHandler($ev);
        $eHandler->saveEvent();
    }
}else {

    $ev =  new TestingEvent($_POST["userID"], $_POST["objectID"], $_POST["eventName"], $_POST["eventValue"], $_POST["where"], $_POST["params"]);
    $eHandler = new TestingEventHandler($ev);
    //echo $ev->getSQL();
    $eHandler->saveEvent();
}

?>
