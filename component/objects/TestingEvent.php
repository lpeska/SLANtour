<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImplicitUserDataEvent
 *
 * @author peska
 */
class TestingEvent {
    //put your code here
    private $userID; /*user about who is the event*/
    private $objectID; /*object about which is the event*/
    private $eventType; /* type of the event (list depends on the event receiver)*/
    private $eventValue;
    private $where;
    private $params;
    
/**
 * @param <type> $userID id of user who comitted event
 * @param <type> $objectID id of object where event occures
 * @param <type> $eventType type of the event (see Config:recognizedExplicitEvents)
 * @param <type> $eventValue value of the event
 */
    function __construct($userID, $objectID, $eventType, $eventValue, $where, $params = ""){
        $this->userID = $userID;
        $this->objectID = $objectID;
        $this->eventType = $eventType;
        $this->eventValue = $eventValue;
        $this->where = $where;
        $this->params = $params;
        //echo $this->getSQL();

    }
    /**
     * returns SQL code for insert into table storing implicit events
     */
    public function getSQL($objects){
        return "insert into `testing`
                    (`userID`,`objectID`,`visited_objects`,`eventType`,`eventValue`,`position`, `date`)
                    values (".$this->userID.",".$this->objectID.",".$objects.",\"".$this->eventType."\",".$this->eventValue.",\"".$this->where."\",\"".Date("Y-m-d H:i:s")."\")";
    }
    /**
     * getter for the eventType var
     * @return <type> the event type
     */
    public function getEventType(){
        return $this->eventType;
    }
    public function getObjectID(){
        return $this->objectID;
    }
    public function getWhere(){
        return $this->where;
    }
    public function getParams(){
        return $this->params;
    }
    public function getUserID(){
        return $this->userID;
    }
}
?>
