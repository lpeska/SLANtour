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
class AggregatedEventHandler  implements EventHandler{
    //put your code here
    private $event;
/**
 *
 * @param <type> $event instance of the AggregatedEvent class
 */
    function __construct($event){
        $this->event = $event;
        echo "event";
    }

    /**
     * saves event to the database
     */
    function saveEvent(){
        /**
         * TODO: kontrola typu udalosti, pripadne akce svazane s typem
         */

         /* check whether we have an approved event*/
         if( in_array($this->event->getEventType(), Config::$recognizedAggregatedEvent ) ){

            $database = ComponentDatabase::get_instance();

            echo $this->event->getSQL();
            $database->executeQuery( $this->event->getSQL() );
            $database->disconnect();
         }
    }

}
?>
