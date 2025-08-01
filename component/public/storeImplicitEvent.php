<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once "../public/ComponentCore.php";

//loadCore every time when computing meta preferences (PreferenceComputation)
    ComponentCore::loadCoreEvents();





if(strpos($_POST["objectID"], ",")!==false){
    $object = explode(",", $_POST["objectID"]);
}else{
    $object = $_POST["objectID"];
}

$implicitEvent =  new ImplicitUserDataEvent($_POST["userID"], $object, $_POST["eventName"], $_POST["eventValue"]);
$eHandler = new ImplicitEventHandler($implicitEvent);
$eHandler->saveEvent();
if($_POST["eventName"]=="order"){
    ComponentCore::loadCore("Config.php", "..");
    require_once("../public/PreferenceComputation.php");        
    
    
                $pref2 = new PreferenceComputation("scroll");
                $pref2->computePreferences(10);

                $pref3 = new PreferenceComputation("pageview");
                $pref3->computePreferences(10);

                $pref = new PreferenceComputation("onpageTime");
                $pref->computePreferences(10);

                $pref = new PreferenceComputation("object_shown_in_list");
                $pref->computePreferences(10);

                $pref = new PreferenceComputation("deep_pageview");
                $pref->computePreferences(10);

                $pref = new PreferenceComputation("object_opened_from_list");
                $pref->computePreferences(10);

}
?>
