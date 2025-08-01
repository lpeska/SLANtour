<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AggregatedUserDataEvent
 * This is Crate method is containing an Aggregated event and its SQL saving it into the database
 * @author peska
 */
class AggregatedDataEvent {
    //put your code here
    private $objectID; /*objectID or array of objectID about which is the event*/
    private $eventType; /* type of the event - only single type allowed for all objects*/
    private $eventValue; /* value to be aggregated - only single value allowed for all objects*/

    /*array - value - value might be a bit confusing, but idea is this: you have a couple of events type "object_shown_in_list"
     * (in list you usually show a couple of objects:)) - so you want to update (add 1) to each objects "object_shown_in_list" value
     * and you can do it easily with one update (on where clause just say "objectID IN (<objectID_list>)" )
     * so you need only one event type one value (add 1) and set of object where to apply
     */

/**
 *
 * @param <type> $objectID object ids array (or single id)
 * @param <type> $eventType type of the event (see Config:recognizedAggregatedEvents)
 * @param <type> $eventValue value of the event
 */
    function __construct($objectID, $eventType, $eventValue){
        $this->objectID = $objectID;
        $this->eventType = $eventType;
        $this->eventValue = $eventValue;

    }
    /**
     * @return SQL code for insert into table storing implicit events
     */
    public function getSQL(){
        $tableName = Config::$aggregatedEventStorageTable;
        $aggFunction = Config::$aggregationFunction[$this->eventType];

          echo $this->eventType.$this->eventValue;
          print_r($this->objectID);

        if(is_array($this->objectID) and  sizeof($this->objectID)!=0){
                $objQuery = "VALUES ";
                $first = 1;
                foreach($this->objectID as $obj) {
                    if($obj!=""){
                    if($first) {
                        $first = 0;
                        $objQuery .= "(".$obj.", \"".$this->eventType."\", ".$this->eventValue.") ";
                    }else {
                        $objQuery .= ", (".$obj.", \"".$this->eventType."\", ".$this->eventValue.") ";
                    }
                    }
                }

          return "insert into `".$tableName."`
                    (`objectID`,`eventType`,`eventValue`) ".$objQuery."
                        ON DUPLICATE KEY
                  UPDATE `eventValue`= `eventValue` ".$aggFunction." VALUES(`eventValue`)";
        }else if(!is_array($this->objectID)){
          return "insert into `".$tableName."`
                    (`objectID`,`eventType`,`eventValue`) VALUES (".$this->objectID.", \"".$this->eventType."\", ".$this->eventValue.")
                        ON DUPLICATE KEY
                  UPDATE `eventValue`= `eventValue` ".$aggFunction." VALUES(`eventValue`)";
        }
        //empty array
        return false;
    }
    /**
     * getter for the eventType var
     * @return <type> the event type
     */
    public function getEventType(){
        return $this->eventType;
    }
}
?>
