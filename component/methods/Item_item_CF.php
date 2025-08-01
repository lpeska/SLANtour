<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Standard
 * Standard methods applies mostly the collaborative filtering on specified implicit and explicit preferences
 * method implements all expressionType interfaces (ObjectRating,ObjectSimilarity,UserSimilarity,UsersToObjectDataLookup)
 * @author peska
 */
class Item_item_CF extends AbstractMethod implements ObjectRating, ObjectSimilarity{
    //put your code here
    private $objectsToScoreArray;
    private $similarObjectsScore;
    private $similarityMethod;
    
        
    private $users;


/**
 * returns interval from $from to $from+$noOfObjects of the best objects from the $objectList
 * method computes from prestored values of the item-to-item similarity weighted by the importance of the users link to this objects
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => score ) )
 */
    public function getBestObjectsFrom($from, $noOfObjects, $objectList="", $usersArray="", $implicitEventsList="", $explicitEventsList=""){
        //get whole list
        $result = $this->getBestObjects($from + $noOfObjects, $objectList, $usersArray, $implicitEventsList, $explicitEventsList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }


/**
 * returns $noOfObjects of the best objects from the $objectList
 * method aggregates object scores in eventValues for users specified in $usersArray of events from $implicitEventsList and $explicitEventsList
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => importance - e.g. for group recommendations) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => score ) )
 */
    public function getBestObjects($noOfObjects, $objectList="", $usersArray="", $implicitEventsList="", $explicitEventsList="") {
            $implicitTable = Config::$implicitEventStorageTable;
            
            $item_item_table = Config::$itemItemSimilarityTable;
            
            $this->similarObjectsScore = array();
            $this->objectsToScoreArray = array();
            $this->users = $usersArray;
            $useImplicit=false;
            $useExplicit=false;

            if(is_array($this->users) and sizeof($this->users)!=0) {
                $userQuery = " and `userID` in (";
                $first = 1;
                foreach($this->users as $user=>$val) {
                    if($first) {
                        $first = 0;
                        $userQuery .= $user;
                    }else {
                        $userQuery .= ", ".$user;
                    }
                }
                $userQuery .=")";
            }else{
                $userQuery ="";
            }

            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = " and `objectID` in (";
                $first = 1;
                foreach($objectList as $obj) {
                    if($first) {
                        $first = 0;
                        $objectQuery .= "".$obj."";
                    }else {
                        $objectQuery .= ", ".$obj."";
                    }
                }
                $objectQuery .=")";
            }else {
                $objectQuery ="";
            }
            if(is_array($implicitEventsList) and sizeof($implicitEventsList)!=0) {
                //forming $implicitEventsList into the query
                $useImplicit=true;
                $eventQueryImplicit = "`eventType` in (";
                $first = 1;
                foreach($implicitEventsList as $eType) {
                    if($first) {
                        $first = 0;
                        $eventQueryImplicit .= "\"".$eType."\"";
                    }else {
                        $eventQueryImplicit .= ", \"".$eType."\"";
                    }
                }
                $eventQueryImplicit .=")";
            }

                if(!$useImplicit and !$useExplicit){
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No implicit or explicit events specified, no prediction made","Standard");
                }
            if($useImplicit or $useExplicit){
                if($useImplicit){
                    $queryImplicit = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$implicitTable."`
                                         where ".$eventQueryImplicit.$userQuery.$objectQuery." ";
                    //echo  $queryImplicit;
                    $this->objectRatingRate($queryImplicit);
                }
                //prvni cast: mam ratingy objektu ktere uz uzivatel navstivil, ted chci najit jim podobne objekty
                
                
                //find_similar(objectsArray, events, allowedObjects="")
                $this->find_similar();

                arsort($this->similarObjectsScore);
                //print_r($userSimilarity);
               // echo $noOfUsers;
              // print_r($this->userToObjectScoreArray);
             //  print_r($this->otherUsersToObjectScoreArray);
               return array_slice($this->similarObjectsScore,0,$noOfObjects, true);
            }else{
                return false;
            }

        }

/**
 * returns interval from $from to $from+$noOfObjects of the most similar objects to the $objectID
 * method counts score of similarity each object O to the object O1 like this:
 * objectScore O = sum_foreach_user(user_score(O1)*userScore(O))
 * this is based on assumption, that any positive score means positive interest in objects,
 * higher score means higher importance of the interest
 * $param <type> $objectID id of the specified object
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity: [0,1] ) )
 */
    public function getSimilarObjectsFrom($objectID, $from, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList=""){
        //get whole list
        $result = $this->getSimilarObjects($objectID, $from + $noOfObjects, $objectList, $implicitEventsList, $explicitEventsList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

/**
 * returns $noOfObjects of the most similar objects to the $objectID
 * returns the most similar objects to the current one based on stored values
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity: [0,1] ) )
 */
   public function getSimilarObjects($objectID, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList="") {
        
            $implicitTable = Config::$implicitEventStorageTable;
            $item_item_table = Config::$itemItemSimilarityTable;
            
            $this->similarObjectsScore = array();
            $this->objectsToScoreArray = array($objectID=>1);
            //print_r($objectList);
            $this->find_similar($objectList);
            
                arsort($this->similarObjectsScore);
                
                //print_r($this->similarObjectsScore);
               // echo $noOfUsers;
             // echo $noOfObjects;
             // print_r($objectList);
                
               $obj = array_slice($this->similarObjectsScore,0,$noOfObjects, true);      
               //print_r($obj);
               return $obj;
        }


/**
 *  this function aggregates rating for concrete object or user
 * (right now it is simple addition, but in case of further improvements, it is made as a method)
 * @param <type> $originalValue previous rating value
 * @param <type> $newValue added value
 * @param $eventType type of the event
 * @param $eventImportanceArray array of the event importances
 * @return <type> new value
 */
    protected function ratingAggregation($originalValue, $additionRating, $eventType, $eventImportanceArray, $userImportance=1){
            if(is_array($eventImportanceArray) and array_key_exists($eventType,$eventImportanceArray) ){
               $eventImportance = $eventImportanceArray[$eventType];
            }else{
               $eventImportance = Config::$eventImportance[$eventType];
            }            
            /**
             * todo: one day add measuring users averange rating (maybe:))
             */
            if($eventType=="user_rating"){//dealing with the negative ratings
                $additionRating = ($additionRating - 0.5)*2;
            }
        return $originalValue + ($additionRating * $eventImportance * $userImportance);
    }

    
   protected function find_similar($allowedObjectList="", $method="") {
       $item_item_table = Config::$itemItemSimilarityTable;
       $database = ComponentDatabase::get_instance();
       if(is_array($this->objectsToScoreArray) and sizeof($this->objectsToScoreArray)!=0) {
                $objectQuery = "and `object_id1` in (";
                $first = 1;
                foreach($this->objectsToScoreArray as $obj=>$value) {
                    if($first) {
                        $first = 0;
                        $objectQuery .= "".$obj."";
                    }else {
                        $objectQuery .= ", ".$obj."";
                    }
                }
                $objectQuery .=")";
            }else {
                $objectQuery ="";
            }
       
       if(is_array($allowedObjectList) and sizeof($allowedObjectList)!=0) {
                $objectQuery2 = "and `object_id2` in (";
                $first = 1;
                foreach($allowedObjectList as $obj) {
                    if($first) {
                        $first = 0;
                        $objectQuery2 .= "".$obj."";
                    }else {
                        $objectQuery2 .= ", ".$obj."";
                    }
                }
                $objectQuery2 .=")";
            }else {
                $objectQuery2 ="";
            }
       if($method!=""){
           $methodQuery = "and `method`=\"".$method."\"";
       }
       
     $querySimilar = "select `object_id1`,`object_id2`,`similarity`
                                         from `".$item_item_table."`
                                         where 1 ".$objectQuery.$objectQuery2.$methodQuery."
                          ";          
     //echo $querySimilar;
     $qr = $database->executeQuery( $querySimilar );
     //$data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$querySimilar);
    
     $eventsList = $qr->getResponseList();
     
     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","ItemToItem");          
         return false;
     }else{
         while( $record = /*mysqli_fetch_array($data)*/ $database->getNextRow($eventsList) ) {
             //get previous value
            // print_r($record);
            // print_r($this->similarObjectsScore);
             
            if( array_key_exists($record["object_id2"],$this->similarObjectsScore) ){
               $objectRating = $this->similarObjectsScore[$record["object_id2"]];
            }else{
               $objectRating = 0;
            }
            if(is_array($this->objectsToScoreArray) and array_key_exists($record["object_id1"],$this->objectsToScoreArray) ){
                $parentObjectImportance = $this->objectsToScoreArray[$record["object_id1"]];
            }else{
                $parentObjectImportance = 1;
            }
            
            $objectRating = $this->ratingAggregation($objectRating, $record["similarity"], 1, "", $parentObjectImportance );
            
            $this->similarObjectsScore[$record["object_id2"]] = $objectRating;
         }
        // print_r($this->similarObjectsScore);
     }
       
   }    
    
//rate single object
    protected function objectRatingRate($query){
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $query );

     $eventsList = $qr->getResponseList();

     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","ItemToItem");
         return false;
     }else{
         while( $record = $database->getNextRow($eventsList) ) {
             //get previous value
            if( array_key_exists($record["objectID"],$this->objectsToScoreArray) ){
               $objectRating = $this->objectsToScoreArray[$record["objectID"]];
            }else{
               $objectRating = 0;
            }
            if(is_array($this->users) and array_key_exists($record["userID"],$this->users) ){
                $userImportance = $this->users[$record["userID"]];
            }else{
                $userImportance = 1;
            }
            $objectRating = $this->ratingAggregation($objectRating, $record["eventValue"], $record["eventType"], $this->eventImportance, $userImportance );
            $this->objectsToScoreArray[$record["objectID"]] = $objectRating;
         }
     }
 }
 
 static public  function compute_similarity($object_id) {
     /*todo compute*/
     $database = ComponentDatabase::get_instance();
     $object_similarities = array();
     $query_objects = "select distinct `objectID` from `".Config::$aggregatedEventStorageTable."` where `objectID`!=".$object_id."";
     $qResponse = $database->executeQuery( $query_objects );
     if($qResponse->getQueryState()){
         $dbResponse = $qResponse->getResponseList();
         while ($qRow = $database->getNextRow($dbResponse)){
             $object_similarities[$qRow["objectID"]] = Item_item_CF::compute_object_similarity($object_id, $qRow["objectID"]);             
         }
         
         arsort($object_similarities);
         $i=0;
         $query = "delete from `".Config::$itemItemSimilarityTable."` where `object_id1`=".$object_id."";
         $database->executeQuery( $query );
         //ulozim nove podobnosti do databaze, zajima me prvnich K zaznamu
         foreach ($object_similarities as $key => $value) {
             if($i>Config::$maxSimilarObjects){
                 break;
             }
             $query = "insert into `".Config::$itemItemSimilarityTable."` (`object_id1`, `object_id2`, `similarity`,`method`,`last_modified`) 
                                        values (".$object_id.", ".$key.", ".$value.",\"coll_cos\", ".Date("Y-m-d H:i:s").")";
             $database->executeQuery( $query );
             $i++;
         }
         
     }
 }

  static private  function compute_object_similarity($object_id1, $object_id2) {
     /*todo compute*/
      $database = ComponentDatabase::get_instance();
     $user_rating1 = array();
     $user_rating2 = array();
     $cosine_sum = 0;
     $cosine_sqr1 = 0;
     $cosine_sqr2 = 0;
     
     $result = 0;
     $query_users = "SELECT DISTINCT `ie1`.`userID`
                        FROM `".Config::$implicitEventStorageTable."` as `ie1` 
                        join `".Config::$implicitEventStorageTable."` as `ie2` on (`ie1`.`userID` = `ie2`.`userID`)
                        WHERE `ie1`.`objectID` =".$object_id1." and `ie2`.`objectID` = ".$object_id2."";
     
     $qResponse = $database->executeQuery( $query_users );
     if($qResponse->getQueryState()){
         $dbResponse = $qResponse->getResponseList();
         while ($qRow = $database->getNextRow($dbResponse)){
             //prubezne pocitani cosinove podobnosti; jine jsou take mozne - casem
             $ur1 = Item_item_CF::compute_user_object_rating($object_id1,$qRow["userID"]);     
             $ur2 = Item_item_CF::compute_user_object_rating($object_id2,$qRow["userID"]);  
             
             $cosine_sum += $ur1*$ur2;
             $cosine_sqr1 += $ur1*$ur1;
             $cosine_sqr2 += $ur2*$ur2;
         } 
         
         $result = $cosine_sum / sqrt($cosine_sqr1)*sqrt($cosine_sqr2);
     }
     
     return $result;
 }
 
 
  static private  function compute_user_object_rating($object_id, $user_id) {
     /*todo compute*/  
      $database = ComponentDatabase::get_instance();
     $feedback = array();  
     $rating = 0;
     $query_feedback = "select * from `".Config::$implicitEventStorageTable."` where `objectID`=".$object_id." and `userID`=".$user_id." ";
     $qResponse = $database->executeQuery( $query_objects );
     if($qResponse->getQueryState()){
         $dbResponse = $qResponse->getResponseList();
         while ($qRow = $database->getNextRow($dbResponse)){
             $feedback[$qRow["eventType"]] = $qRow["eventValue"];   
             
             //prozatim velmi primitivne agregace ratingu
             $rating += Config::$eventImportance[$qRow["eventType"]]* $qRow["eventValue"];
         }                           
     }
     return $rating;
  }
 
 
}
?>
