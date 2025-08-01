<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Dummy
 * basic method for preference guessing - always perform only random selection from allowed objects or users
 * method implements all expressionType interfaces (ObjectRating,ObjectSimilarity,UserSimilarity,UsersToObjectDataLookup)
 * @author peska
 */
class Dummy extends AbstractMethod implements ObjectRating,ObjectSimilarity,UserSimilarity,UsersToObjectDataLookup{
/**
 *
 * returns interval from $from to $from+$noOfUsers of the best objects from the $objectList
 * method just pick randomly demanded number of the objects from the allowed ones
 * @param <type> $objectID id of the selected object
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getBestObjectsFrom($from, $noOfObjects, $objectList=""){
        //get whole list
        $result = $this->getBestObjects($from + $noOfObjects, $objectList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

/**
 * returns $noOfObjects of the best objects from the $objectList
 * method just pick randomly demanded number of the objects from the allowed ones
 * @param <type> $objectID id of the selected object
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getBestObjects($noOfObjects, $objectList="") {
        $table = Config::$objectTableName;
        $objectIDName = Config::$objectIDColumnName;

            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = " `".$objectIDName."` in (";
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
            }else{
                $objectQuery =" 1 ";
            }

            $query = "select distinct `".$objectIDName."`
                                         from `".$table."`
                                         where ".$objectQuery." order by RAND() limit ".$noOfObjects."";
            //echo  $query;
            $database = ComponentDatabase::get_instance();
            $qr = $database->executeQuery( $query );

            $objectsList = $qr->getResponseList();

            if(!$objectsList){//wrong query
                $errLog = ErrorLog::get_instance();
                $errLog->logError("Wrong SQL query, no prediction made","Dummy");
                return false;
 
            }else{
                $returnlist = array();
                while( $record = $database->getNextRow($objectsList) ){
                    $returnlist[$record[$objectIDName]] = 1; //array of objectId => rating = 1
                }
                return $returnlist;
            }

    }

/**
 * returns $noOfObjects of the most similar objects to the $objectID
 * method just pick randomly demanded number of the objects from the allowed ones
 * @param <type> $objectID id of the selected object
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getSimilarObjectsFrom($objectID, $from, $noOfObjects, $objectList=""){
        //get whole list
        $result = $this->getSimilarObjects($objectID, $from + $noOfObjects, $objectList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

/**
 * returns $noOfObjects of the most similar objects to the $objectID
 * method just pick randomly demanded number of the objects from the allowed ones
 * @param <type> $objectID id of the selected object
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getSimilarObjects($objectID, $noOfObjects,  $objectList="") {
            $table = Config::$objectTableName;
            $objectIDName = Config::$objectIDColumnName;

            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = " `".$objectIDName."` in (";
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
            }else{
                $objectQuery =" 1 ";
            }

            $query = "select distinct `".$objectIDName."` from `".$table."`
                    where `".$objectIDName."`!=".$objectID." and ".$objectQuery."
                    order by RAND() limit ".$noOfObjects." ";

            $database = ComponentDatabase::get_instance();
            $qr = $database->executeQuery( $query );

            $objectsList = $qr->getResponseList();

            if(!$objectsList){//wrong query                
                $errLog = ErrorLog::get_instance();
                $errLog->logError("Wrong SQL query, no prediction made","Dummy");
                return false;
            }else{
                $returnlist = array();
                while( $record = $database->getNextRow($objectsList) ){
                    $returnlist[$record[$objectIDName]] = 1; //array of objectId => similarity = 1
                }
                return $returnlist;
            }
        }

/**
 * returns $noOfUsers of the most similar users to the $userID
 * method just pick randomly demanded number of the users from the allowed ones
 * @param <type> $userID id of the selected object
 * @param <type> $noOfObjects number of similar objects, we search for
 * @return <type> array( userID => similarity: 1 ) )
 */
    public function getSimilarUsers($userID, $noOfUsers) {
            $table = Config::$userTableName;
            $userIDName = Config::$userIDColumnName;
            $query = "select distinct `".$userIDName."` from `".$table."` where `".$userIDName."`!=".$userID."  order by RAND() limit ".$noOfUsers." ";

            $database = ComponentDatabase::get_instance();
            $qr = $database->executeQuery( $query );

            $usersList = $qr->getResponseList();

            if(!$usersList){//wrong query
                $errLog = ErrorLog::get_instance();
                $errLog->logError("Wrong SQL query, no prediction made","Dummy");  
                return false;
                
            }else{
                $returnlist = array();
                while( $record = $database->getNextRow($usersList) ){
                    $returnlist[$record[$userIDName]] = 1; //array of userId  => similarity = 1
                }
                return $returnlist;
            }
        }


/**
 * returns $noOfObjects of the best objects for users in $usersArray
 * method just pick randomly demanded number of the objects from the allowed ones
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of objects, we search for
 * @param <type> $objectList list of allowed objects
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getBestObjectForUsersFrom($usersArray, $from, $noOfObjects, $objectList=""){
        //get whole list
        $result = $this->getBestObjectForUsers($usersArray, $from + $noOfObjects, $objectList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }

/**
 * returns $noOfObjects of the best objects for users in $usersArray
 * method just pick randomly demanded number of the objects from the allowed ones
 * @param <type> $usersArray array of (userID => similarity) of the selected users
 * @param <type> $noOfObjects number of objects, we search for
 * @param <type> $objectList list of allowed objects
 * @return <type> array( objectID => similarity: 1 ) )
 */
    public function getBestObjectForUsers($usersArray, $noOfObjects,  $objectList=""){
            $table = Config::$objectTableName;
            $objectIDName = Config::$objectIDColumnName;

            if(is_array($objectList) and sizeof($objectList)!=0) {
                $objectQuery = " `".$objectIDName."` in (";
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
            }else{
                $objectQuery =" 1 ";
            }

            $query = "select distinct `".$objectIDName."` from `".$table."` where ".$objectQuery." order by RAND() limit ".$noOfObjects." ";
            //echo $query;
            $database = ComponentDatabase::get_instance();
            $qr = $database->executeQuery( $query );

            $objectsList = $qr->getResponseList();

            //packing answer into the array
            if(!$objectsList){//wrong query
                $errLog = ErrorLog::get_instance();
                $errLog->logError("Wrong SQL query, no prediction made","Dummy");  
                return false;
            }else{
                $returnlist = array();
                while( $record = $database->getNextRow($objectsList) ){
                    $returnlist[$record[$objectIDName]] = 1;
                }
                return $returnlist;
            }


    }
}
?>
