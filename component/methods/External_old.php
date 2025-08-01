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
class External extends AbstractMethod  implements ObjectRating {
//put your code here

    private $objectsScoreArray;
    private $objectsParams;
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
  * @return <type> array( objectID => params of the object: text) )
  **/    
    public function getObjectParams(){
        return $this->objectsParams;
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
        $implicitTable = Config::$implicitEventStorageTable;
        $server_url = "http://herkules.ms.mff.cuni.cz/lineit-eval40/test.py";
        $user = ComponentCore::getUserId();
        if(is_array($objectList) and sizeof($objectList)!=0) {
            $allowedObjects = implode(",", $objectList);
        }
        $query = "select  distinct `objectID` as `oid`,`lastModified` as `datetime`
                from `".$implicitTable."`
                where userID = ".$user." and `eventType`=\"pageview\" order by `lastModified` desc limit 50";
        $this->objectsScoreArray = array();
        
        $database = ComponentDatabase::get_instance();
        $qr = $database->executeQuery( $query );

        #$objectsResult = $qr->getResponseList();
        
        $objects = array();
        $dates = array();
               
        while ($row = $qr->getNextRow()) {
            $objects[] = $row["oid"];
            $dates[] = $row["datetime"];
        }
        $objects = implode(",", array_reverse($objects));
        $dates = implode(",", array_reverse($dates));
        
        #print_r($objects);
        #print_r($dates);
        
        if($objects != ""){
            //?uid=2&visited_oids=1,5,11,18,5757&visits_datetime=2017-09-09%2022:00:00,2017-09-09%2022:00:00,2017-09-09%2022:00:00,2017-09-09%2022:00:00,2018-04-09%2022:00:00
            $urlQuery = $server_url;//&visited_oids=$objects&visits_datetime=$dates&allowed_oids=$allowedObjects";
            
            $data = http_build_query( array("uid"=>$user, "visited_oids" => $objects,"visits_datetime" => $dates,"allowed_oids" => $allowedObjects  ) );

            $ctx = stream_context_create(array( 
                'http' => array( 
                    'timeout' => 2 ,
                    'method'  => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $data
                    ) 
                ) 
            ); 
            try {
                $file = file_get_contents($urlQuery,false,$ctx);
                //print_r($file);
                if($file != false and $file != "error"){
                    $lines = explode("\n", $file);
                    $body = false;
                    #print_r($lines);
                    foreach ($lines as $line) {
                        if (intval($line)> 0){                            
                            $recObjects = explode(",", $line);
                            break;
                        }
                    }                                        
                    foreach($recObjects as $key => $value){
                          $vals =  explode(";", $value);
                          if(intval($vals[0])>0){ #we have valid object
                            $this->objectsScoreArray[intval($vals[0])] = 10/(10+$key);
                            $this->objectsParams[intval($vals[0])] = $vals[1];
                          } 
                    }
                    if(sizeof($this->objectsScoreArray)==0){
                            $errLog = ErrorLog::get_instance();
                            $errLog->logError("Error, no valid recommended objects","External");
                    }
                }else{
                   $errLog = ErrorLog::get_instance();
                   $errLog->logError("Error while loading recommendations","External");
                }
            } catch (Exception $e) {
                $errLog = ErrorLog::get_instance();
                $errLog->logError("Error while loading recommendations","External");
            }

        }else{           
           $errLog = ErrorLog::get_instance();
           $errLog->logError("Error, no past feedback","External");
        
        }

        #print_r($this->objectsScoreArray);
        #$errLog = ErrorLog::get_instance();
        #print_r($errLog->getErrorMessages());
        
        //arsort($this->objectsScoreArray);
        //
        //print_r($this->objectsScoreArray);
        // echo $noOfUsers;
        // print_r($this->userToObjectScoreArray);
        //  print_r($this->otherUsersToObjectScoreArray);
        return $this->objectsScoreArray;

    }
}
?>
