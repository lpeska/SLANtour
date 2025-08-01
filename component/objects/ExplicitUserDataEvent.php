<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExplicitUserDataEvent
 *
 * @author peska
 */
class ExplicitUserDataEvent {
    //put your code here
    private $userID; /*user about who is the event*/
    private $objectID; /*object about which is the event*/
    private $eventType; /* type of the event (list depends on the event receiver)*/
    private $eventValue; /* event value between 0 and 1*/

/**
 * @param <type> $userID id of user who comitted event
 * @param <type> $objectID id of object where event occures
 * @param <type> $eventType type of the event (see Config:recognizedExplicitEvents)
 * @param <type> $eventValue value of the event
 */
    function __construct($userID, $objectID, $eventType, $eventValue){
        $this->userID = $userID;
        $this->objectID = $objectID;
        $this->eventType = $eventType;
        $this->eventValue = $eventValue;

    }
    /**
     * returns SQL code for insert into table storing implicit events
     */
    public function getSQL(){
        $tableName = Config::$explicitEventStorageTable;
        return "insert into `".$tableName."`
                    (`userID`,`objectID`,`eventType`,`eventValue`)
                    values (".$this->userID.",".$this->objectID.",\"".$this->eventType."\",".$this->eventValue.")";
    }

/**
 * this function returns sql query to get averange value of specified object and eventType
 * @return <type>
 */
    public function getAverangeRatingSQL(){
        $tableName = Config::$explicitEventStorageTable;
        return "select avg(`eventValue`) as `rating` from `".$tableName."`
                    where `objectID` = ".$this->objectID."
                ";
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
