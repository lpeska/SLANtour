<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Standard
 * This method i an alternative for standard method - counts similarity of the users and objects via.
 * Pearson Correlation of theese objects
  * method implements  interfaces (ObjectSimilarity,UserSimilarity)
 * @author peska
 */
class PearsonCorrelation extends Standard implements ObjectSimilarity,UserSimilarity{
        private $objectToUserScoreArray;
        private $otherObjectsToUserScoreArray;
        private $objectList;
        private $userToObjectScoreArray;
        private $otherUsersToObjectScoreArray;
        private $usersList;
/**
 * returns interval from $from to $from+$noOfObjects of the most similar objects to the $objectID
 * method counts Pearsons Correlation of the objects accordingly to the users who made specified implicit or explicit events with it
 *
 * $param <type> $objectID id of the specified object
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getSimilarObjectsFrom($objectID, $from, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList=""){
        //get whole list
        $result = $this->getSimilarObjects($objectID, $from + $noOfObjects, $objectList, $implicitEventsList, $explicitEventsList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

/**
 * returns $noOfObjects of the most similar objects to the $objectID
 * method counts Pearsons Correlation of the objects accordingly to the users who made specified implicit or explicit events with it

 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity: 1 ) )
 */
   public function getSimilarObjects($objectID, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList="") {
        
            $implicitTable = Config::$implicitEventStorageTable;
            $explicitTable = Config::$explicitEventStorageTable;
            $this->objectToUserScoreArray = array();
            $this->otherObjectsToUserScoreArray = array(array());
            $this->objectList = array();
            $useImplicit=false;
            $useExplicit=false;

            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = "and `objectID` in (";
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

                $queryUser = "select  distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$implicitTable."`
                                         where `objectID`=".$objectID." and ".$eventQueryImplicit." order by `userID`";
                // echo  $queryUser;
                $this->objectSimilarityRateObject($queryUser);
            }
            if(is_array($explicitEventsList) and sizeof($explicitEventsList)!=0) {
            //forming $implicitEventsList into the query
                $useExplicit=true;
                $eventQueryExplicit = "`eventType` in (";
                $first = 1;
                foreach($explicitEventsList as $eType) {
                    if($first) {
                        $first = 0;
                        $eventQueryExplicit .= "\"".$eType."\"";
                    }else {
                        $eventQueryExplicit .= ", \"".$eType."\"";
                    }
                }
                $eventQueryExplicit .=")";
                $queryUser = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$explicitTable."`
                                         where `objectID`=".$objectID." and ".$eventQueryExplicit." order by `userID`";
                //echo  $queryUser;
                $this->objectSimilarityRateObject($queryUser);
            }
                if(!$useImplicit and !$useExplicit){
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No implicit or explicit events specified, no prediction made","NRmse");
                }
            //we have list of object we want
            if(is_array($this->objectToUserScoreArray) and sizeof($this->objectToUserScoreArray)!=0) {

                $userQuery = "`userID` in (";
                $first = 1;
                foreach($this->objectToUserScoreArray as $usId=>$value) {
                    if($first) {
                        $first = 0;
                        $userQuery .= $usId;
                    }else {
                        $userQuery .= ", ".$usId;
                    }
                }
                $userQuery .= ")";

                if($useImplicit){
                    $queryOthersImplicit = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$implicitTable."`
                                         where `objectID`!=".$objectID." and ".$eventQueryImplicit." and ".$userQuery.$objectQuery." ";
                   // echo  $queryOthersImplicit;
                    $this->objectSimilarityRateOthers($queryOthersImplicit);
                }
                if($useExplicit){
                    $queryOthersExplicit = "select  distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$explicitTable."`
                                         where `objectID`!=".$objectID." and ".$eventQueryExplicit." and ".$userQuery.$objectQuery."";
                   // echo  $queryOthersExplicit;
                    $this->objectSimilarityRateOthers($queryOthersExplicit);
                }
                $objectSimilarity = array();
                //print_r($this->userToObjectScoreArray);
                //print_r($this->otherUsersToObjectScoreArray);
                //print_r($this->usersList);


                //$this->objectToUserScoreArray
                $usersAverangeScore = $this->userSimilarityAverangeUsersRating();

                foreach ($this->objectList as $objectID) {
                    $objectUserScore = 0;
                    $firstObjectUserScore = 0;
                    $objectScore=0;
                    $objectFirstDenominator=0.000001;//dont want to handle division zero problems:))
                    $objectSecondDenominator=0.000001;//dont want to handle division zero problems:))
                    foreach ($this->otherObjectsToUserScoreArray as  $userID=>$userValue) {
                        if(array_key_exists($userID, $this->objectToUserScoreArray)){
                            //importance of this user
                            $firstObjectUserScore = $this->objectToUserScoreArray[$userID];
                            if(array_key_exists($objectID, $userValue)){
                                //importance of liking the other object
                                $objectUserScore = $userValue[$objectID];
                            }else{
                                $objectUserScore = 0;
                            }
                            //userDistance[] is in [0,1] interval
                            //possibly change - substract averangeScore of each user, not overall
                            $objectScore += ($objectUserScore - $usersAverangeScore )*($firstObjectUserScore - $usersAverangeScore );
                            $objectFirstDenominator += pow(($objectUserScore - $usersAverangeScore ),2);
                            $objectSecondDenominator += pow(($firstObjectUserScore - $usersAverangeScore ),2);
                        }
                    }
                    $objectSimilarity[$objectID] = $objectScore / ( sqrt($objectFirstDenominator)* sqrt($objectSecondDenominator) );

                }
                arsort($objectSimilarity);
                //print_r($objectSimilarity);
               // echo $noOfUsers;
               //print_r($this->objectToUserScoreArray);
               //print_r($this->otherObjectsToUserScoreArray);
               return array_slice($objectSimilarity,0,$noOfObjects, true);
            }else{
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No information about object found, no prediction will be made","PearsonCorrelation");

                return false;
            }

        }
        
/**
 * returns $noOfUsers of the most similar users to the $userID
 * method counts Pearsons Correlation of the objects accordingly to the users who made specified implicit or explicit events with it
 * @param <type> $noOfUsers number of similar users, we search for
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( userID => similarity: 1 ) )
 */
    public function getSimilarUsers($userID, $noOfUsers, $implicitEventsList="", $explicitEventsList="") {
            $implicitTable = Config::$implicitEventStorageTable;
            $explicitTable = Config::$explicitEventStorageTable;
            $this->userToObjectScoreArray = array();
            $this->otherUsersToObjectScoreArray = array(array());
            $this->usersList = array();
            $useImplicit=false;
            $useExplicit=false;
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

                $queryUser = "select  distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$implicitTable."`
                                         where `userID`=".$userID." and ".$eventQueryImplicit." order by `objectID`";
                 //echo  $queryUser;
                $this->userSimilarityRate($queryUser);
            }
            if(is_array($explicitEventsList) and sizeof($explicitEventsList)!=0) {
            //forming $implicitEventsList into the query
                $useExplicit=true;
                $eventQueryExplicit = "`eventType` in (";
                $first = 1;
                foreach($explicitEventsList as $eType) {
                    if($first) {
                        $first = 0;
                        $eventQueryExplicit .= "\"".$eType."\"";
                    }else {
                        $eventQueryExplicit .= ", \"".$eType."\"";
                    }
                }
                $eventQueryExplicit .=")";
                $queryUser = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$explicitTable."`
                                         where `userID`=".$userID." and ".$eventQueryExplicit." order by `objectID`";
                //echo  $queryUser;
                $this->userSimilarityRate($queryUser);
            }
                if(!$useImplicit and !$useExplicit){
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No implicit or explicit events specified, no prediction made","NRmse");
                }
            //we have list of object we want
            if(is_array($this->userToObjectScoreArray) and sizeof($this->userToObjectScoreArray)!=0) {

                $objQuery = "`objectID` in (";
                $first = 1;
                foreach($this->userToObjectScoreArray as $objId=>$value) {
                    if($first) {
                        $first = 0;
                        $objQuery .= $objId;
                    }else {
                        $objQuery .= ", ".$objId;
                    }
                }
                $objQuery .= ")";

                if($useImplicit){
                    $users = $this->getUserHeuristics($userID, $implicitTable, $eventQueryImplicit, $objQuery);
                    $queryOthersImplicit = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$implicitTable."`
                                         where `userID`!=".$userID." and ".$eventQueryImplicit." and $objQuery ";
                  //  echo  $queryOthersImplicit;
                    $this->userSimilarityRateOthers($queryOthersImplicit);
                }
                if($useExplicit){
                    $queryOthersExplicit = "select  distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$explicitTable."`
                                         where `userID`!=".$userID." and ".$eventQueryExplicit." and $objQuery ";
                   // echo  $queryOthersExplicit;
                    $this->userSimilarityRateOthers($queryOthersExplicit);
                }
                $userSimilarity = array();
                //print_r($this->userToObjectScoreArray);
                //print_r($this->otherUsersToObjectScoreArray);
                //print_r($this->usersList);

                $usersAverangeScore = $this->userSimilarityAverangeUsersRating("users");
                foreach ($this->usersList as $userID) {
                    $objectUserScore = 0;
                    $firstObjectUserScore = 0;
                    $userScore=0;
                    $userFirstDenominator=0.000001;//dont want to handle division zero problems:))
                    $userSecondDenominator=0.000001;//dont want to handle division zero problems:))
                    foreach ($this->otherUsersToObjectScoreArray as  $objectID=>$objValue) {
                        if(array_key_exists($objectID, $this->userToObjectScoreArray)){
                            //importance of this user
                            $firstObjectUserScore = $this->userToObjectScoreArray[$objectID];
                            if(array_key_exists($userID, $objValue)){
                                //importance of liking the other object
                                $objectUserScore = $objValue[$userID];
                            }else{
                                $objectUserScore = 0;
                            }
                            //possibly change - substract averangeScore of each object, not overall
                            $userScore += ($objectUserScore - $usersAverangeScore )*($firstObjectUserScore - $usersAverangeScore );
                            $objectFirstDenominator += pow(($objectUserScore - $usersAverangeScore ),2);
                            $objectSecondDenominator += pow(($firstObjectUserScore - $usersAverangeScore ),2);
                        }
                    }                
                    $userSimilarity[$userID] = $userScore / ( sqrt($objectFirstDenominator)* sqrt($objectSecondDenominator) );
                }
                arsort($userSimilarity);
                //print_r($userSimilarity);
               // echo $noOfUsers;
               // print_r($this->userToObjectScoreArray);
               // print_r($this->otherUsersToObjectScoreArray);
               return array_slice($userSimilarity,0,$noOfUsers, true);
            }else{
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No information about user found, no prediction will be made","PearsonCorrelation");
                return false;
            }

        }



//the averange score of users rating of an object - used in Pearsons correlation
   private function userSimilarityAverangeUsersRating($type="") {
       $scoreSum = 0;
       $objectCount = 0;
       if($type==""){
           foreach ($this->objectList as $objectID) {
               foreach ($this->otherObjectsToUserScoreArray as  $userID=>$userValue) {
                   if(array_key_exists($userID, $this->objectToUserScoreArray)) {
                       $objectCount++;
                       $scoreSum += $this->objectToUserScoreArray[$userID];
                       if(array_key_exists($objectID, $userValue)) {
                           $scoreSum += $userValue[$objectID];
                       }else {
                           $scoreSum += 0;
                       }
                       $objectCount++;
                   }
               }
           }
       }else{
           //print_r($this->usersList);
           foreach ($this->usersList as $userID) {
               foreach ($this->otherUsersToObjectScoreArray as  $objectID=>$objectValue) {
                   if(array_key_exists($objectID, $this->userToObjectScoreArray)) {
                       $objectCount++;
                       $scoreSum += $this->userToObjectScoreArray[$userID];
                       if(array_key_exists($userID, $objectValue)) {
                           $scoreSum += $objectValue[$userID];
                       }else {
                           $scoreSum += 0;
                       }
                       $objectCount++;
                   }
               }
           }

       }
       return $scoreSum/$objectCount;

    }
 



//rate single object - similarity
protected function objectSimilarityRateObject($queryUser){
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $queryUser );

     $eventsList = $qr->getResponseList();

     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","Standard");
         return false;
     }else{
         while( $record = $database->getNextRow($eventsList) ) {
            if( array_key_exists($record["userID"],$this->objectToUserScoreArray) ){
               $userRating = $this->objectToUserScoreArray[$record["userID"]];
            }else{
               $userRating = 0;
            }
            $userRating  = $this->ratingAggregation($userRating,  $record["eventValue"], $record["eventType"], $this->eventImportance );
            $this->objectToUserScoreArray[$record["userID"]] = $userRating;
         }
     }
 }

//rate multiple objects - similarity
protected function objectSimilarityRateOthers($query){
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $query );

     $eventsList = $qr->getResponseList();

     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","Standard");
         return false;
     }else{
         while( $record = $database->getNextRow($eventsList) ) {
             //add user if not present
             if(!in_array($record["objectID"], $this->objectList)){
                 $this->objectList[] = $record["objectID"];
             }
             //get previous value
            if( array_key_exists($record["userID"],$this->otherObjectsToUserScoreArray)
                and array_key_exists($record["objectID"],$this->otherObjectsToUserScoreArray[$record["userID"]]) ){
               $userRating = $this->otherObjectsToUserScoreArray[$record["userID"]][$record["objectID"]];
            }else{
               $userRating = 0;
            }

            $userRating  = $this->ratingAggregation($userRating,  $record["eventValue"], $record["eventType"], $this->eventImportance );
            $this->otherObjectsToUserScoreArray[ $record["userID"]][$record["objectID"]] = $userRating;
         }
     }
 }

//rate single user - similarity
 protected function userSimilarityRate($queryUser){
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $queryUser );

     $eventsList = $qr->getResponseList();

     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","Standard");
         return false;
     }else{
         while( $record = $database->getNextRow($eventsList) ) {
            if( array_key_exists($record["objectID"],$this->userToObjectScoreArray) ){
               $objectRating = $this->userToObjectScoreArray[$record["objectID"]];
            }else{
               $objectRating = 0;
            }

            $objectRating  = $this->ratingAggregation($objectRating,  $record["eventValue"], $record["eventType"], $this->eventImportance );
            $this->userToObjectScoreArray[$record["objectID"]] = $objectRating;
         }
     }
 }

//rate multiple users - similarity
    protected function userSimilarityRateOthers($query){
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $query );

     $eventsList = $qr->getResponseList();

     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","Standard");
         return false;
     }else{
         while( $record = $database->getNextRow($eventsList) ) {
             //add user if not present
             if(!in_array($record["userID"], $this->usersList)){
                 $this->usersList[] = $record["userID"];
             }
             //get previous value
            if( array_key_exists($record["objectID"],$this->otherUsersToObjectScoreArray)
                and array_key_exists($record["userID"],$this->otherUsersToObjectScoreArray[$record["objectID"]]) ){
               $objectRating = $this->userToObjectScoreArray[$record["objectID"]][$record["userID"]];
            }else{
               $objectRating = 0;
            }

            $objectRating  = $this->ratingAggregation($objectRating,  $record["eventValue"], $record["eventType"], $this->eventImportance );
            $this->otherUsersToObjectScoreArray[ $record["objectID"]][$record["userID"]] = $objectRating;
         }
     }
 }

/**
 *Returns set of users, that this heuristic approved to be used in similarity measuring
 */
private function getUserHeuristics($userID, $table, $eventNames, $objects){
    $query = "select `userID`, count(DISTINCT `objectID`) as `count`
               from `".$table."`
                where `userID`!=".$userID." and ".$eventQueryImplicit." ". $objects."
                group by `userID` having `count`>=2";
    //echo $query;
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $query );
     $eventsList = $qr->getResponseList();
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No user passed the heuristics, keeping the full no. Of Users","Standard");
         return " ";
     }else{
         $result = "and `userID` in (";
         $first = 1;
         while( $record = $database->getNextRow($eventsList) ) {
            if($first){
              $first=0;
              $result .= $record["userID"];
            }else{
              $result .= ",".$record["userID"];
            }
         }
         $result .= " )";
         return $result;
     }
}

}
?>
