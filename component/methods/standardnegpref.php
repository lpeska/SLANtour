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
class StandardNegPref extends AbstractMethod implements ObjectRating,UsersToObjectDataLookup{
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
            $this->userID = ComponentCore::getUserId();
            $database = ComponentDatabase::get_instance();
            $useImplicit=false;
            $useExplicit=false;

            $objectRestrictionQuery = "
              SELECT distinct (`objectID`)
              FROM `".$implicitTable."`
              WHERE `userID`=".$this->userID." 
                  and `eventType`= \"object_shown_in_list\"
                  and `eventValue` >= 3
            ";   
            $objectAllowedQuery = "
              SELECT distinct (`objectID`)
              FROM `".$implicitTable."`
              WHERE `userID`=".$this->userID." 
                  and `eventType`= \"pageview\"
                  and `eventValue` >= 1
            "; 
            $shownArray = Array();
            $qrs = $database->executeQuery( $objectRestrictionQuery );    
            $shownObjects = $qrs->getResponseList();
            while ($recordSL = $database->getNextRow($shownObjects)) {
                $shownArray[] = $recordSL["objectID"];
            }
            
            $allowedArray = Array();
            $qra = $database->executeQuery( $objectAllowedQuery );
            $allowedObjects = $qra->getResponseList();
            while ($recordAO = $database->getNextRow($allowedObjects)) {
                $allowedArray[] = $recordAO["objectID"];
            }
            
            $restrictedObjects = array_diff($shownArray,$allowedArray);
            if(sizeof($restrictedObjects) > 0){
                $objectQueryNot = "and `objectID` not in (".implode(", ", $restrictedObjects).")";
            }else{
                $objectQueryNot = "";
            }
            
            
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
                                         where ".$eventQueryImplicit.$userQuery.$objectQuery.$objectQueryNot." ";
                    echo  "<!--QueryRatingNegPref ".$queryImplicit."-->";
                    $this->objectRatingRate($queryImplicit);
                }
                if($useExplicit){
                    $queryExplicit = "select  distinct `userID`,`objectID`,`eventType`,`eventValue`
                                         from `".$explicitTable."`
                                         where ".$eventQueryExplicit.$userQuery.$objectQuery.$objectQueryNot." ";
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
            $this->userID = ComponentCore::getUserId();
            $database = ComponentDatabase::get_instance();
            
            $objectRestrictionQuery = "
              SELECT distinct (`objectID`)
              FROM `".$implicitTable."`
              WHERE `userID`=".$this->userID." 
                  and `eventType`= \"object_shown_in_list\"
                  and `eventValue` >= 3
            ";   
            $objectAllowedQuery = "
              SELECT distinct (`objectID`)
              FROM `".$implicitTable."`
              WHERE `userID`=".$this->userID." 
                  and `eventType`= \"pageview\"
                  and `eventValue` >= 1
            "; 
            $shownArray = Array();
            $qrs = $database->executeQuery( $objectRestrictionQuery );    
            $shownObjects = $qrs->getResponseList();
            while ($recordSL = $database->getNextRow($shownObjects)) {
                $shownArray[] = $recordSL["objectID"];
            }
            
            $allowedArray = Array();
            $qra = $database->executeQuery( $objectAllowedQuery );
            $allowedObjects = $qra->getResponseList();
            while ($recordAO = $database->getNextRow($allowedObjects)) {
                $allowedArray[] = $recordAO["objectID"];
            }
            
            $restrictedObjects = array_diff($shownArray,$allowedArray);
            if(sizeof($restrictedObjects) > 0){
                $objectQuery = "`objectID` not in (".implode(", ", $restrictedObjects).")";
            }else{
                $objectQuery = " 1 ";
            }
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
                                  where ".$userQuery." and ".$eventQuery." and ".$objectQuery."
                                  order by `objectID`  ";
                 echo "<!--QueryCollaborativeNegPref ".$queryImplicit."-->";
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
                echo "<!-- resObjectCollab";print_r($res_objects);echo "-->";
                return $res_objects;
            }else{
                
                return array_slice($this->objectToScoreArray,0,$noOfObjects, TRUE);
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
