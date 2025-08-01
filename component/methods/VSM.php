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
class VSM extends AbstractMethod implements ObjectRating,ObjectSimilarity{
    //put your code here
    private $objectsToScoreArray;          
    private $users;
    private $user;
    private $similarObjectsScore;
    private $similarityMethod;
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
            $this->objectsToScoreArray = array();
            $this->users = array_keys($usersArray);
            $this->user = $this->users[0];
            $implicitTable = Config::$implicitEventStorageTable;
            $database = ComponentDatabase::get_instance();
            
            $query_restricted_objects = "select distinct `objectID` from `$implicitTable` where `userID`=$this->user  ";
            
            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = " and `oid` in (";
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
            
            //toto je drobna heuristika - nemelo by byt tak slozite to spocitat a udela to zakladni poradi objektu pro uzivatele a oreze zcela nevhodne objekty
            $query_objects = "SELECT `oid` FROM `vsm_object_model` left join `vsm_user_model` on (`vsm_user_model`.`feature`=`vsm_user_model`.`feature` and `uid`=$this->user) "
                    . " where 1 $objectQuery and `oid` not in ($query_restricted_objects) "
                    . " group by `oid` "
                    . " having sum(`vsm_user_model`.`relevance`*`vsm_object_model`.`relevance`)>0"
                    . " order by sum(`vsm_user_model`.`relevance`*`vsm_object_model`.`relevance`) desc"
                    . " limit 0,".($noOfObjects*5)."";
//echo $query_objects;
             
             $qr = $database->executeQuery( $query_objects );
             $objects = $qr->getResponseList();
             if(!$objects) {//wrong query
                   $errLog = ErrorLog::get_instance();
                   $errLog->logError("No object passed the heuristics, no results","VSM");
                 return false;
             }else{
                 $userFeatures = $this->getUserFeatures($this->user);//feature->similarity
                 while( $record = $database->getNextRow($objects) ) {
                    $object_features = $this->getObjectFeatures($record["oid"]);//feature->similarity
                    $similarity = $this->computeCosineSimilarity($userFeatures,$object_features);
                    $this->objectsToScoreArray[$record["oid"]] = $similarity;
                 }
             }            
            

            arsort($this->objectsToScoreArray);
            // print_r($userSimilarity);
            // echo $noOfUsers;
            // print_r($this->userToObjectScoreArray);
            // print_r($this->otherUsersToObjectScoreArray);
           // print_r(array_slice($this->objectsToScoreArray,0,$noOfObjects, true));
           return array_slice($this->objectsToScoreArray,0,$noOfObjects, true);            
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
        $this->objectsToScoreArray = array();
            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = " and `o1`.`oid` in (";
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
            
            //toto je drobna heuristika - nemelo by byt tak slozite to spocitat a udela to zakladni poradi objektu pro uzivatele a oreze zcela nevhodne objekty
            $query_objects = "SELECT `o1`.`oid` FROM `vsm_object_model` as `o1` left join `vsm_object_model` as `ref` on (`o1`.`feature`=`ref`.`feature` and `o1`.`oid`!=`ref`.`oid` and `ref`.`oid` = $objectID) "
                    . " where 1 $objectQuery "
                    . " group by `oid` "
                    . " having sum(`o1`.`relevance`*`ref`.`relevance`)>0"
                    . " order by  sum(`o1`.`relevance`*`ref`.`relevance`) desc"                    
                    . " limit 0,".($noOfObjects*5)."";
     //       echo $query_objects;
             $database = ComponentDatabase::get_instance();
             $qr = $database->executeQuery( $query_objects );
             $objects = $qr->getResponseList();
             if(!$objects) {//wrong query
                   $errLog = ErrorLog::get_instance();
                   $errLog->logError("No object passed the heuristics, no results","VSM");
                 return false;
             }else{
                 $userFeatures = $this->getObjectFeatures($objectID);//feature->similarity
                 while( $record = $database->getNextRow($objects) ) {
                    $object_features = $this->getObjectFeatures($record["oid"]);//feature->similarity
                    $similarity = $this->computeCosineSimilarity($userFeatures,$object_features);
                    $this->objectsToScoreArray[$record["oid"]] = $similarity;
                 }
             }            
            

            arsort($this->objectsToScoreArray);
            // print_r($userSimilarity);
            // echo $noOfUsers;
            // print_r($this->userToObjectScoreArray);
            // print_r($this->otherUsersToObjectScoreArray);
           // print_r(array_slice($this->objectsToScoreArray,0,$noOfObjects, true));
           return array_slice($this->objectsToScoreArray,0,$noOfObjects, true);        

        }
/**
 * return array of features and its relevance for current user
 * @param type $uid 
 */
   private function getUserFeatures($uid){
       $res_array = array();
       $query = "SELECT * FROM `vsm_user_model` WHERE `uid`=$uid";
       $database = ComponentDatabase::get_instance();
       $qr = $database->executeQuery( $query );
       $features = $qr->getResponseList();
       while( $record = $database->getNextRow($features) ) {
            $res_array[$record["feature"]] = $record["relevance"];
       }
       return $res_array;
   }     
        
  /**
 * return array of features and its relevance for current object
 * @param type $oid
 */
   private function getObjectFeatures($oid){
        $res_array = array();
       $query = "SELECT * FROM `vsm_object_model` WHERE `oid`=$oid";
       $database = ComponentDatabase::get_instance();
       $qr = $database->executeQuery( $query );
       $features = $qr->getResponseList();
       while( $record = $database->getNextRow($features) ) {
            $res_array[$record["feature"]] = $record["relevance"];
       }
       return $res_array;      
   }        
        

   private function computeCosineSimilarity($objectFeatures1, $objectFeatures2){
         $sumOF1 = 0.000000001; //nenulovy jmenovatel
         $sumOF2 = 0.000000001;
         $sumOF1_x_OF2 = 0;
         $features = array();
         //stanovuju globalni seznam vlastnosti
         foreach ($objectFeatures1 as $key => $value) {
             $features[$key] = 1;
         }
         foreach ($objectFeatures2 as $key => $value) {
             $features[$key] = 1;
         }
         //projdu seznam vlastnosti, spoctu podobnost
         foreach ($features as $key => $val) {
             if(!isset($objectFeatures1[$key])){
                 $objectFeatures1[$key]=0;
             }
             if(!isset($objectFeatures2[$key])){
                 $objectFeatures2[$key]=0;
             }
             $sumOF1 += $objectFeatures1[$key]*$objectFeatures1[$key];
             $sumOF2 += $objectFeatures2[$key]*$objectFeatures2[$key];
             $sumOF1_x_OF2 += $objectFeatures1[$key]*$objectFeatures2[$key];
         }
         $similarity = $sumOF1_x_OF2 /(sqrt($sumOF1)*sqrt($sumOF2));
      
         return $similarity;            
   }   
   



}
?>
