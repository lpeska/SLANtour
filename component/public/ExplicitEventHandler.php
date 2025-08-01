<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ImplicitDataSender
 *
 * @author peska
 */
class ExplicitEventHandler  implements EventHandler, EventHandlerReturnedValue{

    //put your code here
    private $event;
    private $eventResponse;

/**
 * @param <type> $event instance of the ExplicitUserEvent class
 */
    function __construct($event){
        $this->event = $event;
        $this->eventResponse = null;
    }

    /**
     * saves event to the database
     */
    function saveEvent(){
        /**
         * TODO: kontrola typu udalosti, pripadne akce svazane s typem
         */

         /* check whether we have an approved event*/
         if( in_array($this->event->getEventType(), Config::$recognizedExplicitEvent ) ){

            $database = ComponentDatabase::get_instance();

            //echo $this->event->getSQL();
            $this->eventResponse = $database->executeQuery( $this->event->getSQL() );

         }else{
            $this->eventResponse = new QueryResponse(0, "event not in list of approved event types", "");
         }

    }


    /**
     * getter for EventResponse
     */
    function getEventResponse(){
        return $this->eventResponse;
    }

    /**
     * returns averange rating of the object specified in the event
     */
    function getAverangeRatingForObject() {
            $database = ComponentDatabase::get_instance();
            //echo $this->event->getSQL();
            $qResponse = $database->executeQuery( $this->event->getAverangeRatingSQL() );
            if($qResponse->getQueryState()){
                $dbResponse = $qResponse->getResponseList();
                $qRow = $database->getNextRow($dbResponse);
                return $qRow[0];
            }else{
                return "0";
            }
    }

/**
 * returns averange rating for the specified object
 * @param <type> $objectID object we concerned
 * @return <type> integer averange object rating
 */
    static function getObjectRatings($objectID){
        $database = ComponentDatabase::get_instance();
        $tableName = Config::$explicitEventStorageTable;
        $qResponse = $database->executeQuery( "select  avg(`eventValue`) as `rating` from  `".$tableName."` where `objectID`=".$objectID." and `eventType`=\"user_rating\" " );
        $qResponse2 = $database->executeQuery( "select  count(`eventValue`) as `rating` from  `".$tableName."` where `objectID`=".$objectID." and `eventType`=\"user_rating\" " );

       //there is at least one rating
       if($qResponse2->getQueryState()){
           $dbResponse2 = $qResponse2->getResponseList();
           $qRow2 = $database->getNextRow($dbResponse2);
           if($qRow2[0]=="0"){
               return false;
           }
        }
        $userRatingArray = array();
        if($qResponse->getQueryState()) {
           $dbResponse = $qResponse->getResponseList();
           $qRow = $database->getNextRow($dbResponse);
           return $qRow[0];
        }
        return false;
    }

/**
 * returns all ratings for the user
 * @param <type> $user
 * @return <type> array of objectID -> rating
 */
    static function getUserRatings($user){
        $database = ComponentDatabase::get_instance();
        $tableName = Config::$explicitEventStorageTable;
        $qResponse = $database->executeQuery( "select * from  `".$tableName."` where `userID`=".$user." and `eventType`=\"user_rating\" " );

        $userRatingArray = array();
        if($qResponse->getQueryState()) {
            $dbResponse = $qResponse->getResponseList();
            while($qRow = $database->getNextRow($dbResponse)) {
                $userRatingArray[$qRow["objectID"] ] = $qRow["eventValue"];
            }

        }
        return $userRatingArray;
    }

}
?>
