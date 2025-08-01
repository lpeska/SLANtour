<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once "../public/ComponentCore.php";

//loadCore every time when computing meta preferences (PreferenceComputation)
    ComponentCore::loadCoreEvents();



$query = "insert into `feedback_traces`
                    (`userID`,`pageType`,`session`,`address`, `datetime`) values
                    (".$_POST["userID"].",
                     \"".$_POST["pageType"]."\",
                     ".$_POST["sessionID"].", 
                        \" tourType:".$_POST["tourType"].";
                            country:".$_POST["country"].";
                            destination:".$_POST["destination"].";
                            dates_from:".$_POST["dates_from"].";
                            dates_to:".$_POST["dates_to"].";
                            keyword:".$_POST["keyword"].";
                            accomodation:".$_POST["accomodation"].";
                            tourName:".$_POST["tourName"].";
                            termID:".$_POST["termID"].";   
                            purchase:".$_POST["purchase"].";     
                             \",
                      \"".Date("Y-m-d H:i:s")."\" ) ";

echo $query;
$database = ComponentDatabase::get_instance();
$database->executeQuery( $query );

?>
