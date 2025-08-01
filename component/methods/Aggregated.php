<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Aggregated
 * This method uses Aggregated implicit data, only interface it implements is ObjectRating
 * (other doesnt make much sense for aggregated data)
 * @author peska
 */
class Aggregated extends AbstractMethod  implements ObjectRating {
//put your code here

    private $objectsScoreArray;
    private $eventsArray;
/**
 * returns interval from $from to $from+$noOfUsers of the best objects from the $objectList
 * method aggregates object scores in eventValues of events from $aggregatedEventsList
 * @param <type> $objectID id of the selected object
 * @param <type> $from start index of the result
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $aggregatedEventsList array of calculated aggregatedEvents
 * @return <type> array( objectID => similarity: [0-1]) )
 */
    public function getBestObjectsFrom($from, $noOfObjects, $objectList="", $aggregatedEventsList="", $eventsValuesList=""){
        //get whole list
        $result = $this->getBestObjects($from + $noOfObjects, $objectList, $aggregatedEventsList, $eventsValuesList="");
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }
/**
 * returns $noOfUsers of the best objects from the $objectList
 * method aggregates object scores in eventValues of events from $aggregatedEventsList
 * @param <type> $objectID id of the selected object
 * @param <type> $noOfObjects number of similar objects, we search for
 * @param <type> $objectList list of allowed objects
 * @param <type> $aggregatedEventsList array of calculated aggregatedEvents
 * @return <type> array( objectID => similarity: [0-1]) )
 */
    public function getBestObjects($noOfObjects, $objectList="", $aggregatedEventsList="", $eventsValuesList="") {
        $aggregatedTable = Config::$aggregatedEventStorageTable;
        $implicitTable = Config::$implicitEventStorageTable;
        $this->eventsArray = $aggregatedEventsList;
        if($eventsValuesList!=""){
            $this->eventImportance = $eventsValuesList;
        }
        $this->objectsScoreArray = array();
        if(is_array($aggregatedEventsList) and sizeof($aggregatedEventsList)!=0) {

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
            }else{
                $objectQuery ="";
            }
            $implicit_events=array();
            $events=array();
            foreach($aggregatedEventsList as $eType) {
                if(in_array($eType, Config::$recognizedAggregatedEvaluationEvent)) {
                    if($eType == "opened_vs_shown_fraction") {
                        $events[] = "object_shown_in_list" ;
                        $events[] = "object_opened_from_list" ; 
                    }else {
                        $events[] = $eType ;
                    }
                }
            }
           $eventQuery = "`eventType` in (";
            $first = 1;
            foreach($events as $eType) {
                if($first) {
                    $first = 0;
                    $eventQuery .= "\"".$eType."\"";
                }else {
                    $eventQuery .= ", \"".$eType."\"";
                }
            }
            $eventQuery .=")";

            $query = "select  distinct `objectID`,`eventType`,`eventValue`
                                         from `".$aggregatedTable."`
                                         where ".$eventQuery.$objectQuery." order by `objectID`";
            //echo  $query;
            $this->rateObjects($query);
        }else{
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No events specified, no prediction made","Aggregated");
        }
        
        arsort($this->objectsScoreArray);
        //print_r($this->objectsScoreArray);
        // echo $noOfUsers;
        // print_r($this->userToObjectScoreArray);
        //  print_r($this->otherUsersToObjectScoreArray);
        return array_slice($this->objectsScoreArray,0,$noOfObjects, true);

    }

    //execute rating of all objects
    private function rateObjects($query) {
        $database = ComponentDatabase::get_instance();
        $qr = $database->executeQuery( $query );

        $eventsList = $qr->getResponseList();

        //rating objects
        if(!$eventsList) {//wrong query            
           //$errLog = ErrorLog::get_instance();
           //$errLog->logError("No events of the specified type found, no prediction made","Aggregated");
            return false;
        }else {
            $last_objectID = null;
            $objectEvents = array();
            while( $record = $database->getNextRow($eventsList) ) {

                if( $last_objectID == $record["objectID"] ) {
                    $objectEvents[$record["eventType"]] = $record["eventValue"];                   
                }else {
                    if($last_objectID != null) {
                        $this->rateObject($last_objectID, $objectEvents);
                    }
                    $last_objectID = $record["objectID"];
                    $objectEvents = array( $record["eventType"]=>$record["eventValue"] );
                }
            }
            $this->rateObject($last_objectID, $objectEvents);
        }
    }

    //execute rating of a single object
    private function rateObject($objectID, $eventsValue) {
        $objRating = 0;
        foreach ($this->eventsArray as $event) {

            if( is_array($this->eventImportance) and array_key_exists($event,$this->eventImportance) ) {
                $eventImportance = $this->eventImportance[$event];
            }else {
                $eventImportance = Config::$eventImportance[$event];
            }
            if($event == "opened_vs_shown_fraction") {
                if( array_key_exists("object_shown_in_list",$eventsValue)
                    and  array_key_exists("object_opened_from_list",$eventsValue)
                    and $eventsValue["object_shown_in_list"] > Config::$newObjectShownLimit) {

                    $objRating += $eventsValue["object_opened_from_list"] / $eventsValue["object_shown_in_list"] * 0.5*Config::$defaultListItemsCount * $eventImportance;

                }else if(Config::$newObjectShownLimit==0 or (array_key_exists("object_shown_in_list",$eventsValue) and $eventsValue["object_shown_in_list"] >= Config::$newObjectShownLimit)){
                    $objRating += 0; //one of the key values doesnt exist + we dont accept new objects as candidates for recomendation
                
                }else if(!array_key_exists("object_shown_in_list",$eventsValue) or $eventsValue["object_shown_in_list"]<=Config::$newObjectShownLimit) {
                        $objRating += 1*$eventImportance;
                }

            }else {
                if( array_key_exists($event,$eventsValue) ) {
                    $objRating += $eventsValue[$event] * $eventImportance;
                }
            }
        }
        $this->objectsScoreArray[$objectID] = $objRating;
    }

}
?>
