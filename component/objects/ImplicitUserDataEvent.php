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
class ImplicitUserDataEvent {
    //put your code here
    private $userID; /*user about who is the event*/
    private $objectID; /*object about which is the event*/
    private $eventType; /* type of the event (list depends on the event receiver)*/
    private $eventValue;

/**
 * @param <type> $userID id of user who comitted event
 * @param <type> $objectID id of object where event occures
 * @param <type> $eventType type of the event (see Config:recognizedImplicitEvents)
 * @param <type> $eventValue value of the event
 */
    function __construct($userID, $objectID, $eventType, $eventValue){
        $this->userID = $userID;
        $this->objectID = $objectID;
        $this->eventType = $eventType;
        $this->eventValue = $eventValue;
        //echo $this->getSQL();

    }
    /**
     * @return SQL code for insert into table storing implicit events
     */
    public function getSQL(){
        $tableName = Config::$implicitEventStorageTable;
        $aggFunction = Config::$aggregationFunction[$this->eventType];
        //tamto zatim nepouzivam
        $aggFunction = " + ";
        if(is_array($this->objectID) and  sizeof($this->objectID)!=0){
                $objQuery = "VALUES ";
                $first = 1;
                foreach($this->objectID as $obj) {
                    if($obj!=""){
                        if($first) {
                            $first = 0;
                            $objQuery .= "(".$this->userID.",".$obj.", \"".$this->eventType."\", ".$this->eventValue.", \"".Date("Y-m-d H:i:s")."\") ";
                        }else {
                            $objQuery .= ", (".$this->userID.",".$obj.", \"".$this->eventType."\", ".$this->eventValue."), \"".Date("Y-m-d H:i:s")."\") ";
                        }
                    }
                }

          return "insert into `".$tableName."`
                    (`userID`,`objectID`,`eventType`,`eventValue`, `lastModified`) ".$objQuery."
                        ON DUPLICATE KEY
                  UPDATE `eventValue`= `eventValue` ".$aggFunction." VALUES(`eventValue`), `lastModified` = \"".Date("Y-m-d H:i:s")."\" ";
        }else if(!is_array($this->objectID)){
        
            return "insert into `".$tableName."`
                    (`userID`,`objectID`,`eventType`,`eventValue`, `lastModified`)
                    values (".$this->userID.",".$this->objectID.",\"".$this->eventType."\",".$this->eventValue.", \"".Date("Y-m-d H:i:s")."\" )
                        ON DUPLICATE KEY
                  UPDATE `eventValue`= `eventValue` ".$aggFunction." VALUES(`eventValue`), `lastModified` = \"".Date("Y-m-d H:i:s")."\" ";
        }
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
    public function getUserID(){
        return $this->userID;
    }     
}
?>
