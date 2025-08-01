<?php
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();

ComponentCore::loadCoreEvents("Config.php","./component");

require_once("./component/public/PreferenceComputation.php");


$_GET["userID"]= 531432;
$_GET["objectID"]= 390;
$_GET["eventName"]= "order";
$_GET["eventValue"]= 1;


$implicitEvent =  new ImplicitUserDataEvent($_GET["userID"], $_GET["objectID"], $_GET["eventName"], $_GET["eventValue"]);

$eHandler = new ImplicitEventHandler($implicitEvent);

$eHandler->saveEvent();


/*
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

*/
?>