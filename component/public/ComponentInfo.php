<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ComponentInfo
 *
 * @author peska
 */
class ComponentInfo {
    //put your code here
    /**
     * method returns object IDs that user interacted (explicit/implicit) with
     * usable for decision which method to use
     * @param $eventType type of the interaction: "implicit" or "explicit"
     * @return <type> array of objectIDs
     */
    public static function getInteractedObjects($eventType){
        if($eventType=="implicit"){
            $table = Config::$implicitEventStorageTable;
        }else if($eventType=="explicit"){
            $table = Config::$explicitEventStorageTable;
        }else{
            return false;
        }

        $query = "select  distinct `objectID` from `".$table."`
                    where `userID`=".ComponentCore::getUserId()." ";
        $database = ComponentDatabase::get_instance();
        $qr = $database->executeQuery( $query );
        $objectsList = $qr->getResponseList();

         while( $record = $database->getNextRow($objectsList) ) {
             $res[] = $record["objectID"];
         }
         return $res;
    }

    /**
     * method returns user IDs that interacted (explicit/implicit) with current object
     * usable for decision which method to use
     * @param $eventType type of the interaction: "implicit" or "explicit"
     * @param $objectID id of the demanded object
     * @return <type> num. of users
     */
    public static function getInteractingUsersCount($eventType, $objectID){
        if($eventType=="implicit"){
            $table = Config::$implicitEventStorageTable;
        }else if($eventType=="explicit"){
            $table = Config::$explicitEventStorageTable;
        }else{
            return false;
        }

        $query = "select  count(`userID`) as `num` from `".$table."`
                    where `objectID`=".$objectID." ";
        $database = ComponentDatabase::get_instance();
        $qr = $database->executeQuery( $query );
        $objectsList = $qr->getResponseList();
        $record = $database->getNextRow($objectsList) ;
        $res = $record["num"];
        return $res;
    }

    /**
     * method returns user IDs that interacted (explicit/implicit) with current object and is useable for colaborative filtering
     * (they also interacted with some other objects)
     * usable for decision which method to use
     * @param $eventType type of the interaction: "implicit" or "explicit"
     * @param $objectID id of the demanded object
     * @param $noObjectsInteractedWith minimal number of object, that user interacted with
     * @return <type> array of userIDs
     */
    public static function getEligibleUsers($eventType, $noObjectsInteractedWith=2, $objectID=0 ){
        if($eventType=="implicit"){
            $table = Config::$implicitEventStorageTable;
        }else if($eventType=="explicit"){
            $table = Config::$explicitEventStorageTable;
        }else{
            return false;
        }

        if($objectID!=0){
            $query = "
                select distinct `userID`, count(distinct `objectID`) as `count` from `".$table."` where
                    `userID` in (select distinct `userID` from `".$table."` where `objectID`=".$objectID." )
                group by `userID`
                having `count`>=".$noObjectsInteractedWith."
            ";
        }else{
            $query = "
                select distinct `userID`, count(distinct `objectID`) as `count` from `".$table."` where
                    1
                group by `userID`
                having `count`>=".$noObjectsInteractedWith."
            ";
        }
        $database = ComponentDatabase::get_instance();
        $qr = $database->executeQuery( $query );
        while($object = $qr->getNextRow()){
            $res[] = $record["userID"];
        }
        return $res;
    }



    /**
     * method returns count of events specified type
     * usable for decision which method to use
     * @param $eventType type of the interaction: "implicit" or "explicit"
     * @param $eventName optional name of the event (pageview, user_rating etc.)
     * @return <type> num. of events
     */
    public static function getEventsCount($eventType, $eventName=""){
        if($eventType=="implicit"){
            $table = Config::$implicitEventStorageTable;
        }else if($eventType=="explicit"){
            $table = Config::$explicitEventStorageTable;
        }else{
            return false;
        }
        if($eventName!=""){
           $ev = "`eventType`=\"".$eventName."\"";
        }else{
            $ev="";
        }
        $query = "select  count(`objectID`) as `num` from `".$table."`
                    where ".$ev." ";
        $database = ComponentDatabase::get_instance();
        $qr = $database->executeQuery( $query );
        $objectsList = $qr->getResponseList();
        $record = $database->getNextRow($objectsList) ;
        $res = $record["num"];
        return $res;
    }

}
?>
