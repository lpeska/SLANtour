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
class Standard extends AbstractMethod implements ObjectRating,ObjectSimilarity,UserSimilarity,UsersToObjectDataLookup{
    //put your code here
    private $objectsToScoreArray;
        private $objectToUserScoreArray;
        private $otherObjectsToUserScoreArray;
        private $objectList;
        private $userToObjectScoreArray;
        private $otherUsersToObjectScoreArray;
        private $usersList;
    private $objectToScoreArray;
    private $users;


/**
 * returns interval from $from to $from+$noOfObjects of the best objects from the $objectList
 * method aggregates object scores in eventValues for users specified in $usersArray of events from $implicitEventsList and $explicitEventsList
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
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => score ) )
 */
    public function getBestObjects($noOfObjects, $objectList="", $usersArray="", $implicitEventsList="", $explicitEventsList="") {
            $implicitTable = Config::$implicitEventStorageTable;
            $explicitTable = Config::$explicitEventStorageTable;
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
                if($useExplicit){
                    $queryExplicit = "select  distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$explicitTable."`
                                         where ".$eventQueryExplicit.$userQuery.$objectQuery." ";
                   //echo  $queryExplicit;
                    $this->objectRatingRate($queryExplicit);
                }

                arsort($this->objectsToScoreArray);
                //print_r($userSimilarity);
               // echo $noOfUsers;
              // print_r($this->userToObjectScoreArray);
             //  print_r($this->otherUsersToObjectScoreArray);
               return array_slice($this->objectsToScoreArray,0,$noOfObjects, true);
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
 * method counts score of similarity each object O to the object O1 like this:
 * objectScore O = sum_foreach_user(user_score(O1)*userScore(O))
 * this is based on assumption, that any positive score means positive interest in objects,
 * higher score means higher importance of the interest
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $implicitEventsList array of calculated implicitEvents
 * @param <type> $explicitEventsList array of calculated explicitEvents
 * @return <type> array( objectID => similarity: [0,1] ) )
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
                    $errLog->logError("No implicit or explicit events specified, no prediction made","Standard");
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
                    $absDistance = 0;
                    foreach ($this->otherObjectsToUserScoreArray as  $userID=>$userValue) {
                        if(array_key_exists($userID, $this->objectToUserScoreArray)){

                            //importance of this user
                            $value1 = $this->objectToUserScoreArray[$userID];
                            if(array_key_exists($objectID, $userValue)){
                                //importance of liking the other object
                                $value2 = $userValue[$objectID];
                            }else{
                                $value2 = 0;
                            }
                            //userDistance[] is in [0,1] interval
                            $objectScore += $value1*$value2;
                        }
                    }
                    $objectSimilarity[$objectID] = $objectScore;

                }
                arsort($objectSimilarity);
                //print_r($objectSimilarity);
               // echo $noOfUsers;
               //print_r($this->objectToUserScoreArray);
               //print_r($this->otherObjectsToUserScoreArray);
               return array_slice($objectSimilarity,0,$noOfObjects, true);
            }else{
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No information about object found, no prediction will be made","Standard");

                return false;
            }

        }


/**
 * returns $noOfUsers of the most similar users to the $userID
 * method counts score of similarity each user O to the user O1 like this:
 * userScore O = sum_foreach_Object(user_score(O1)*userScore(O))
 * this is based on assumption, that any positive score means positive interest in objects,
 * higher score means higher importance of the interest
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
                    $errLog->logError("No implicit or explicit events specified, no prediction made","Standard");
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
                                         where `userID`!=".$userID." and ".$eventQueryImplicit." and ".$objQuery." ".$users." ";
                    echo  "<!--". $queryOthersImplicit."-->";
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
                    $absDistance = 0;
                    foreach ($this->otherUsersToObjectScoreArray as  $objectID=>$objValue) {
                        if(array_key_exists($objectID, $this->userToObjectScoreArray)){
                            $value1 = $this->userToObjectScoreArray[$objectID];
                            if(array_key_exists($userID, $objValue)){
                                $value2 = $objValue[$userID];
                            }else{
                                $value2 = 0;
                            }
                            //userDistance[] is in [0,1] interval
                            $userScore += $value1*$value2;
                        }
                    }
                    $userSimilarity[$userID] = $userScore;

                }
                arsort($userSimilarity);
                //print_r($userSimilarity);
               // echo $noOfUsers;
              // print_r($this->userToObjectScoreArray);
             //  print_r($this->otherUsersToObjectScoreArray);
               return array_slice($userSimilarity,0,$noOfUsers, true);
            }else{
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No information about user found, no prediction will be made","Standard");
                return false;
            }

        }

    /**
     *returns interval $from, $from+$noOfObjects of the best rated objects for the group of $usersArray users
      * method aggregates object scores in eventValues for users specified in $usersArray of events from $implicitEventsList and $explicitEventsList
     * @param <type> $usersArray array of ("userID => weight(similarity) of user:element of (0,1] interval )
     * @param <type> $from start of the selected interval
     * @param <type> $noOfObjects number of similar objects, we search for
     * @param <type> $implicitEventsList array of calculated implicitEvents
     * @param <type> $explicitEventsList array of calculated explicitEvents
     * @return <type> array( objectID => score ) )
     * @return <type>
     */
    public function getBestObjectForUsersFrom($usersArray, $from, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList=""){
        //get whole list
        $result = $this->getBestObjectForUsers($usersArray, $from + $noOfObjects, $objectList, $implicitEventsList, $explicitEventsList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

    /**
     *returns $noOfObjects of the best rated objects for the group of $usersArray users
      * method aggregates object scores in eventValues for users specified in $usersArray of events from $implicitEventsList and $explicitEventsList
     * @param <type> $usersArray array of ("userID => weight(similarity) of user:element of (0,1] interval )
     * @param <type> $noOfObjects number of similar objects, we search for
     * @param <type> $implicitEventsList array of calculated implicitEvents
     * @param <type> $explicitEventsList array of calculated explicitEvents
     * @return <type> array( objectID => score ) )
     * @return <type>
     */
   public function getBestObjectForUsers($usersArray, $noOfObjects, $objectList="", $implicitEventsList="", $explicitEventsList=""){
            $implicitTable = Config::$implicitEventStorageTable;
            $explicitTable = Config::$explicitEventStorageTable;
            $this->users = $usersArray;
            $this->objectToScoreArray = array();

            //forming user array into the query
            if(is_array($this->users) and sizeof($this->users)!=0) {
                $userQuery = "`userID` in (";
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
            }else {
                return false;
            }
            //implicit events
            if(is_array($implicitEventsList) and sizeof($implicitEventsList)!=0) {
                //forming $implicitEventsList into the query
                $eventQuery = "`eventType` in (";
                $first = 1;
                foreach($implicitEventsList as $eType) {
                    if($first) {
                        $first = 0;
                        $eventQuery .= "\"".$eType."\"";
                    }else {
                        $eventQuery .= ", \"".$eType."\"";
                    }
                }
                $eventQuery .=")";

                $queryImplicit = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                  from `".$implicitTable."`
                                  where ".$userQuery." and ".$eventQuery."
                                  order by `objectID`  ";
                 //echo $queryImplicit;
                $this->usersToObjectRate($queryImplicit);
            }

            if(is_array($explicitEventsList) and sizeof($explicitEventsList)!=0){
            //forming $implicitEventsList into the query
                $eventQuery = "`eventType` in (";
                $first = 1;
                foreach($explicitEventsList as $eType) {
                    if($first) {
                        $first = 0;
                        $eventQuery .= "\"".$eType."\"";
                    }else {
                        $eventQuery .= ", \"".$eType."\"";
                    }
                }
                $eventQuery .=")";

                $queryExplicit = "select distinct `userID`,`objectID`,`eventType`,`eventValue`
                                  from `".$explicitTable."`
                                  where ".$userQuery." and ".$eventQuery."
                                  order by `objectID`  ";
                 //echo $queryExplicit;
                $this->usersToObjectRate($queryExplicit);
            }
                if(!$useImplicit and !$useExplicit){
                    $errLog = ErrorLog::get_instance();
                    $errLog->logError("No implicit or explicit events specified, no prediction made","Standard");
                }
            //rating of object finished - filter, sort + return the best
            arsort($this->objectToScoreArray);
            if(is_array($objectList) and sizeof($objectList)!=0){
                $i=0;
                $j=0;
                $all_objects = array_keys($this->objectToScoreArray);
                $res_objects = array();
                while ($i<$noOfObjects and $j<=sizeof($all_objects)) {
                    if(in_array( $all_objects[$j], $objectList)){
                        $res_objects[$all_objects[$j]] = $this->objectToScoreArray[$all_objects[$j]];
                        $i++;
                    }
                    $j++;
                }
                
                return $res_objects;
            }else{
                return array_slice($this->objectToScoreArray,0,$noOfObjects, TRUE);
            }
    }

/**
 *Returns set of users, that this heuristic approved to be used in similarity measuring
 */
private function getUserHeuristics($userID, $table, $eventNames, $objects){
    $query = "select `userID`, count(`objectID`) as `count`
               from `".$table."`
                where `userID`!=".$userID." and ".$eventQueryImplicit." ". $objects."
                group by `userID` having `count`>=4";
    //echo "<!--". $query."-->";
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

//rate single object
    protected function objectRatingRate($query){
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

//rate multiple objects - user-rating
 protected function usersToObjectRate($dbQuery){
     $database = ComponentDatabase::get_instance();
     $qr = $database->executeQuery( $dbQuery );

     $eventsList = $qr->getResponseList();

     //rating objects
     if(!$eventsList) {//wrong query
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events of the specified type found, no prediction made","Standard");       
         return false;
     }else{
         while( $record = $database->getNextRow($eventsList) ) {
            if( array_key_exists($record["objectID"],$this->objectToScoreArray) ){
               $objectRating = $this->objectToScoreArray[$record["objectID"]];
            }else{
               $objectRating = 0;
            }
            if(is_array($this->users) and array_key_exists($record["userID"],$this->users) ){
                $userImportance = $this->users[$record["userID"]];
            }else{
                $userImportance = 0;
            }
            $objectRating  = $this->ratingAggregation($objectRating,  $record["eventValue"], $record["eventType"], $this->eventImportance, $userImportance );
            $this->objectToScoreArray[$record["objectID"]] = $objectRating;
         }
     }
 }



}
?>
