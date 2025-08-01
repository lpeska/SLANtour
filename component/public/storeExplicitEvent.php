<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "../public/ComponentCore.php";
ComponentCore::loadCoreEvents();
$explicitEvent =  new ExplicitUserDataEvent($_POST["userID"], $_POST["objectID"], $_POST["eventName"], $_POST["eventValue"]);
$eHandler = new ExplicitEventHandler($explicitEvent);
$eHandler->saveEvent();
if( $eHandler->getEventResponse()->getQueryState() ){
    echo $eHandler->getAverangeRatingForObject();
}else{
    echo "false";
}

?>
