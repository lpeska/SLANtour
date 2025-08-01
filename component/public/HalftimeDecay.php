<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExtendedQuerySender
 *
 * @author peska
 */
class HalftimeDecay{
    /**
     * method removes $decayCoeficient portion of the events listed in $eventNames with the lowest id
     * in $eventsTable table from the database - deletes them permanently
     * @param <type> $eventsTable storing table name
     * @param <type> $eventNames array of events name
     * @param <type> $decayCoeficient [0,1] interval - portion of data to be deleted
     * @return <type> false on error, true otherwise
     */
    public static function removeEvents($eventsTable, $eventNames, $decayCoeficient=0.1){
        $correct=true;
        if($decayCoeficient<=0 or $decayCoeficient>1){
            $correct = false;
        }
        if($eventsTable=="implicitEvents") {
            $allowedEvents = Config::$recognizedImplicitEvent;
            $table = Config::$implicitEventStorageTable;
        }else if($eventsTable=="explicitEvents") {
            $allowedEvents = Config::$recognizedExplicitEvent;
            $table = Config::$explicitEventStorageTable;
        }else if($eventsTable=="aggregatedEvents") {
            $allowedEvents = Config::$recognizedAggregatedEvent;
            $table = Config::$aggregatedEventStorageTable;
        }else{
            $correct=false;
        }

        if(is_array($eventNames) and sizeof($eventNames)!=0) { 
            $evQuery = " `eventType` in (";
            $first = 1;
            foreach ($eventNames as $evName) {
                if(in_array($evName, $allowedEvents)){
                    if($first) {
                        $first = 0;
                        $evQuery .= "\"".$evName."\"";
                    }else {
                        $evQuery .= ", "."\"".$evName."\"";
                    }
                }else{
                   $correct=false;
                }
            }
            $evQuery .=")";
        } else{
            $evQuery ="1";
        }

        if($correct){
            $database = ComponentDatabase::get_instance();
            if($eventsTable=="implicitEvents" or $eventsTable=="explicitEvents"){
                $query = "select count(`id`) as `count` from ".$table."  where ".$evQuery." ";
                $qResponse = $database->executeQuery($query);
                if($qResponse->getQueryState()){
                    $event = $qResponse->getResponseList();
                    $rowCount = $qResponse->getNextRow($event);
                    $deletedRows = round($rowCount["count"] * $decayCoeficient);
                    if($deletedRows>0){
                        $query = "delete from ".$table." where ".$evQuery." order by `id` limit ".$deletedRows." ";
                        $qResponse = $database->executeQuery($query);
                        return $qResponse->getQueryState();
                    }else{
                        return true;
                    }
                }else{
                    return false;
                }

            }else{//aggregated events
                $portion = 1 - $decayCoeficient;
                $query = "update ".$table." set `eventValue` = round(`eventValue`* ".$portion.") where ".$evQuery." ";
                $qResponse = $database->executeQuery($query);
                return $qResponse->getQueryState();
            }
        }else{
            return false;
        }


    }
   
}
?>
