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
class TestingEventHandler  implements EventHandler{
    //put your code here
    private $event;

    function __construct($event){
        $this->event = $event;
    }

    /**
     * saves event to the database
     */
    function saveEvent(){
        /**
         * TODO: kontrola typu udalosti, pripadne akce svazane s typem
         */

         /* check whether we have an approved event*/
	 //echo $this->event->getEventType();
         //echo in_array($this->event->getEventType(), Config::$recognizedAggregatedEvent );
         if( in_array($this->event->getEventType(), Config::$recognizedAggregatedEvent ) ){

            $database = ComponentDatabase::get_instance();
            $implicitTable = Config::$implicitEventStorageTable;
            $sql_vsm = "select count(distinct objectID) as pocet from $implicitTable "
                    . "where userID=".$this->event->getUserID()." and eventType=\"pageview\" ";
            //echo $this->event->getSQL();
            $objects = 0;
            $d = $database->executeQuery($sql_vsm);
            $obj = $d->getResponseList();
            while( $rec = $database->getNextRow($obj) ) {
               $objects = $rec["pocet"];
            }
            //echo $sql_vsm;
            //echo $this->event->getSQL($objects) ;
            $database->executeQuery( $this->event->getSQL($objects) );
            
            //echo $this->event->getEventType();
            echo $this->event->getParams();

            // allow this if algorithm weights needs to be updated
            if($this->event->getEventType() == "object_opened_from_list" and $this->event->getParams() != ""){
                //forward event to the external server
                $this->sendToExternalServer("storeClicks");
            }else if($this->event->getEventType() == "object_shown_in_list" and $this->event->getWhere() == "recomended" and  $this->event->getParams() != ""){
                //forward event to the external server
                //$this->sendToExternalServer("storeViews");
            }

         }
         
    }
    
    function sendToExternalServer($actionName){
	    $server_url = "http://herkules.ms.mff.cuni.cz:5003?";
            $urlQuery = $server_url;
	    //echo $server_url;

	    $uid = $this->event->getUserID();
	    if($uid> 0){
		$variant = strval(($uid % 5) +1);
	    }else{
		$variant = "0";
	    }
	    

            
            $data = http_build_query( array( "rItemIDsWithResponsibility" => $this->event->getParams()  ) );
	    $data2 = http_build_query( array(
		"actionID" => "click", 
		"variantID" => $variant, 
		"userID" => $uid, 
		"itemID" => $this->event->getObjectID(),  
		"session" => $_SESSION["session_no"] , 
		"pageType" => $_POST["pageType"]  ) );

	    $urlQuery.=$data2;
            $ctx = stream_context_create(array( 
                'http' => array( 
                    'timeout' => 2,
                    'method'  => 'POST',
		    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $data

                    ) 
                ) 
            ); 
	    var_dump($urlQuery );
	    var_dump($ctx);

            try {
                $file = file_get_contents($urlQuery,false,$ctx);
		print_r($file);
                //do not care about the file actually - just wanted to send it out  
                echo "done"  ;        
            } catch (Exception $e) {
                // maybe report somewhere?
                echo "exception:" ;
                print_r(e);
            }

        
    }

}
?>
