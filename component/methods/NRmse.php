<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Nrmse
 * NRmse is an alternatve to the standard method for similarity evaluations. it counts similarity of the object or users as
 * normalized root mean square error of the objects
 * method implements  interfaces (ObjectSimilarity,UserSimilarity)
 * @author peska
 */
class NRmse extends Standard implements ObjectSimilarity,UserSimilarity{
/**
 * returns interval from $from to $from+$noOfObjects of the most similar objects to the $objectID
 * method counts normalized RMSE of the objects accordingly to the $implicitEventsList and $explicitEventsList
 * $param <type> $objectID id of the specified object
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity:  [0,1] ) )
 */
    public function getSimilarObjectsFrom($objectID, $from, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList=""){
        //get whole list
        $result = $this->getSimilarObjects($objectID, $from + $noOfObjects, $objectList, $implicitEventsList, $explicitEventsList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

/**
 * returns $noOfObjects of the most similar objects to the $objectID
 * method counts normalized RMSE of the objects accordingly to the $implicitEventsList and $explicitEventsList
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity:  [0,1]  ) )
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

                foreach ($this->objectList as $objectID) {
                    $objectScore = 0;
                    $objCount = 0;
                    $maxScore=NULL;
                    $minScore=NULL;
                    foreach ($this->otherObjectsToUserScoreArray as  $userID=>$userValue) {
                        if(array_key_exists($userID, $this->objectToUserScoreArray)){
                            $value1 = $this->objectToUserScoreArray[$userID];
                            if(array_key_exists($objectID, $userValue)){
                                $value2 = $userValue[$objectID];
                            }else{
                                $value2 = 0;
                            }
                            //userDistance[] is in [0,1] interval
                            $objectScore += pow(($value1 - $value2), 2);
                            $objCount++;
                            
                            $candidateMin = ($value1<$value2)?$value1:$value2;
                            $candidateMax = ($value1>$value2)?$value1:$value2;
                            if($minScore==NULL or $candidateMin<$minScore){
                                $minScore = $candidateMin;
                            }
                            if($maxScore==NULL or $candidateMax<$maxScore){
                                $maxScore = $candidateMax;
                            }                            
                        }
                    }

                    $objectTotalDistance = (sqrt($objectScore)/$objCount)/($maxScore-$minScore);
                    $objectSimilarity[$objectID] = 1-$objectTotalDistance;

                }
                arsort($objectSimilarity);
                //print_r($objectSimilarity);
               // echo $noOfUsers;
               print_r($this->objectToUserScoreArray);
               print_r($this->otherObjectsToUserScoreArray);
               return array_slice($objectSimilarity,0,$noOfObjects, true);
            }else{
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No information about object found, no prediction will be made","NRmse");
                return false;
            }

        }
        
   /**
 * returns $noOfUsers of the most similar users to the $userID
 * method counts normalized RMSE of the users accordingly to the $implicitEventsList and $explicitEventsList
 * @param <type> $noOfUsers number of similar objects, we search for
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( $userID => similarity: [0,1] ) )
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

                foreach ($this->usersList as $userID) {
                    $userScore = 0;
                    $userCount = 0;
                    $maxScore=NULL;
                    $minScore=NULL;
                    foreach ($this->otherUsersToObjectScoreArray as  $objectID=>$objValue) {
                        if(array_key_exists($objectID, $this->userToObjectScoreArray)){
                            $value1 = $this->userToObjectScoreArray[$objectID];
                            if(array_key_exists($objectID, $userValue)){
                                $value2 = $objValue[$userID];
                            }else{
                                $value2 = 0;
                            }
                            //userDistance[] is in [0,1] interval
                            $userScore += pow(($value1 - $value2), 2);
                            $userCount++;

                            $candidateMin = ($value1<$value2)?$value1:$value2;
                            $candidateMax = ($value1>$value2)?$value1:$value2;
                            if($minScore==NULL or $candidateMin<$minScore){
                                $minScore = $candidateMin;
                            }
                            if($maxScore==NULL or $candidateMax<$maxScore){
                                $maxScore = $candidateMax;
                            }
                        }
                    }

                    $userTotalDistance = (sqrt($userScore)/$userCount)/($maxScore-$minScore);
                    $userSimilarity[$userID] = 1-$userTotalDistance;

                }
                arsort($userSimilarity);
                //print_r($userSimilarity);
               // echo $noOfUsers;
              // print_r($this->userToObjectScoreArray);
             //  print_r($this->otherUsersToObjectScoreArray);
               return array_slice($userSimilarity,0,$noOfUsers, true);
            }else{
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No information about user found, no prediction will be made","NRmse");
                return false;
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
