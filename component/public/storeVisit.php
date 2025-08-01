<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once "../public/ComponentCore.php";

function sendToExternalServer($uid, $oid, $session, $pageType, $startDateTime){


          $server_url = "http://herkules.ms.mff.cuni.cz:5003?";

	    if($uid> 0){
		$variant = strval(($uid % 5) +1);
	    }else{
		$variant = "0";
	    }

          $data = http_build_query( array("actionID"=>"visit", "variantID"=>$variant, "userID"=>$uid, "itemID" => $oid, "session" => $session, "pageType" => $pageType, "startDateTime" => $startDateTime  ) );
	  $server_url = $server_url.$data;
          //print_r($data);
          $ctx = stream_context_create(array( 
                'http' => array( 
                    'timeout' => 1 ,
                    'method'  => 'GET',
                    'header'  => 'Content-type: text/html'
                    ) 
                ) 
            ); 
            try {
                $file = file_get_contents($server_url,false,$ctx);
                //do not care about the file actually - just wanted to send it out  
                //echo "done"  ;        
            } catch (Exception $e) {
                // maybe report somewhere?
                //echo "exception:" ;
                //print_r(e);
            }

        
    }


//loadCore every time when computing meta preferences (PreferenceComputation)
    ComponentCore::loadCoreEvents();

    
//name of the target table
//database connection needs to be established

$tableName = "new_implicit_events";

if($_POST["visitID"]>0){
    $visit_name = "`visitID`,";
    $visit_val = "".$_POST["visitID"].",";
}else{
    $visit_name = "";
    $visit_val = "";
}
if($_POST["firstTime"]==1){
    /*first time this visit is being stored => send data to external server*/
    sendToExternalServer($_POST["userID"],$_POST["objectID"],$_POST["sessionID"],$_POST["pageType"],$_POST["startDatetime"]);
}
print_r($_POST);

//create SQL code
$sql = "
insert into `".$tableName."`
     (".$visit_name." `userID`,`objectID`,`sessionID`,`pageID`,`pageType`,`imagesCount`,
         `textSizeCount`,`linksCount`,`windowSizeX`,`windowSizeY`,`pageSizeX`,`pageSizeY`,`objectsListed`,
      `startDatetime`,`endDatetime`,`timeOnPage`,`mouseClicksCount`,`pageViewsCount`,`mouseMovingTime`,
      `mouseMovingDistance`,`scrollingCount`,`scrollingTime`,`scrollingDistance`,
      `printPageCount`,`selectCount`,`selectedText`,`copyCount`,`copyText`,`clickOnPurchaseCount`,
      `purchaseCount`,`forwardingToLinkCount`,`forwardedToLink`,`logFile`) 
     VALUES ( ".$visit_val." ".$_POST["userID"].",  ".$_POST["objectID"].",".$_POST["sessionID"].",\"".$_POST["pageID"]."\", \"".$_POST["pageType"]."\",".$_POST["imagesCount"].",
              ".$_POST["textSizeCount"].",".$_POST["linksCount"].", ".$_POST["windowSizeX"].", ".$_POST["windowSizeY"].", ".$_POST["pageSizeX"].", ".$_POST["pageSizeY"].", \"".$_POST["objectsListed"]."\",
            \"".$_POST["startDatetime"]."\", \"".$_POST["endDatetime"]."\", ".$_POST["timeOnPageMiliseconds"].", ".$_POST["mouseClicksCount"].", ".$_POST["pageViewsCount"].",".$_POST["mouseMovingTime"].",
              ".$_POST["mouseMovingDistance"].",".$_POST["scrollingCount"].", ".$_POST["scrollingTime"].", ".$_POST["scrollingDistance"].",
              ".$_POST["printPageCount"].",".$_POST["selectCount"].", \"".$_POST["selectedText"]."\", ".$_POST["copyCount"].", \"".$_POST["copyText"]."\", ".$_POST["clickOnPurchaseCount"].", 
              ".$_POST["purchaseCount"].", ".$_POST["forwardingToLinkCount"].",\"".$_POST["forwardedToLink"]."\",\"".$_POST["logFile"]."\"                                                                                                                                     
         )
ON DUPLICATE KEY
UPDATE  `endDatetime`= \"".$_POST["endDatetime"]."\",
        `timeOnPage`= `timeOnPage` + VALUES(`timeOnPage`),
        `mouseClicksCount`= `mouseClicksCount` + VALUES(`mouseClicksCount`),
        `pageViewsCount`= `pageViewsCount` + VALUES(`pageViewsCount`),
        `mouseMovingTime`= `mouseMovingTime` + VALUES(`mouseMovingTime`),
        `mouseMovingDistance`= `mouseMovingDistance` + VALUES(`mouseMovingDistance`),
        `scrollingCount`= `scrollingCount` + VALUES(`scrollingCount`),
        `scrollingTime`= `scrollingTime` + VALUES(`scrollingTime`),
        `scrollingDistance`= `scrollingDistance` + VALUES(`scrollingDistance`),
        `printPageCount`= `printPageCount` + VALUES(`printPageCount`),
        `selectCount`= `selectCount` + VALUES(`selectCount`),
        `selectedText`= concat(`selectedText` , VALUES(`selectedText`)),
        `searchedText`= concat(`searchedText` , VALUES(`searchedText`)),
        `copyCount`= `copyCount` + VALUES(`copyCount`),
        `copyText`= concat(`copyText` , VALUES(`copyText`)),
        `clickOnPurchaseCount`= `clickOnPurchaseCount` + VALUES(`clickOnPurchaseCount`),
        `purchaseCount`= `purchaseCount` + VALUES(`purchaseCount`),
        `forwardingToLinkCount`= `forwardingToLinkCount` + VALUES(`forwardingToLinkCount`),
        `forwardedToLink`= concat( `forwardedToLink` , VALUES(`forwardedToLink`) ),
        `logFile`= concat(`logFile`, VALUES(`logFile`) )
";
//echo $sql;

$database = ComponentIFDatabase::get_instance();
$database->executeQuery( "SET character_set_client=UTF8" );
$database->executeQuery($sql) ;

            //print_r($_POST);
            //echo $sql;
            //echo mysqli_error($GLOBALS["core"]->database->db_spojeni);
            if(!$_POST["visitID"]>0){
                echo mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
            }else{
                echo $_POST["visitID"];
            }



?>
