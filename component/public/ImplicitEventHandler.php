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
class ImplicitEventHandler  implements EventHandler{
    //put your code here
    private $event;
    
/**
 * @param <type> $event instance of the ExplicitUserEvent class
 */
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
         if( in_array($this->event->getEventType(), Config::$recognizedImplicitEvent ) ){

            $database = ComponentDatabase::get_instance();

            //echo $this->event->getSQL();
            $database->executeQuery( $this->event->getSQL() );
            
            //nekam sem to bude chtit kontrolu zda neni treba obnovit pocitani podobnosti objektu
            if($this->event->getEventType() == "pageview" or $this->event->getEventType() == "onpageTime"){
                //$this->update_user_vsm_model();     //creates working overload           
                //$this->calculateSimilarity();
            }
            
            //v pripade ziskani objednavky automaticky prepocitame hranice clusteru
         }
    }
    
    /*
     * updates VSM model of the current user 
     * computes actual features and its relevance
     * stores into the database (updates relevance)
     */
    function update_user_vsm_model(){
        $database = ComponentDatabase::get_instance();
        $tableName = Config::$implicitEventStorageTable;
        $uid = $this->event->getUserId();
        echo $uid." skupina: ".($uid % 4);
        
        $userObjectRelevance = array();
        $userFeatures = array();
        $query = "select * from `".$tableName."` where `userID` = $uid ";
        $qResponse = $database->executeQuery( $query );
        if($qResponse->getQueryState()){
            $dbResponse = $qResponse->getResponseList();
            while($qRow = $database->getNextRow($dbResponse)){
                $userObjectRelevance[$qRow["objectID"]] += $this->getFeedbackRelevance($qRow["userID"],$qRow["objectID"],$qRow["eventType"],$qRow["eventValue"]);
            }
            $query_object_features = "select * from `vsm_object_model` where `oid` in (".implode(",", array_keys($userObjectRelevance)).")  ";
           // echo $query_object_features;
            $qResponseOF = $database->executeQuery( $query_object_features );
            if($qResponseOF->getQueryState()){
                $dbResponseOF = $qResponseOF->getResponseList();
                while($record = $database->getNextRow($dbResponseOF)){
                   if($record["relevance"]>0 and $userObjectRelevance[$record["oid"]]>0){ 
                       if(!isset($userFeatures[$record["feature"]])){
                           $userFeatures[$record["feature"]] =  $userObjectRelevance[$record["oid"]]*$record["relevance"];
                       }else{
                           $userFeatures[$record["feature"]] +=  $userObjectRelevance[$record["oid"]]*$record["relevance"];
                       }  
                   }
                }
            }    
        }  
       // print_r($userFeatures);
        if(sizeof($userFeatures)>0){
            //odešleme dotaz který uloží userFeatures
            $first = 1;
            $query_start = "INSERT INTO `vsm_user_model`(`uid`, `relevance`, `feature`) VALUES";
            $query_end = "ON DUPLICATE KEY
                  UPDATE `relevance`= VALUES(`relevance`)";
            foreach ($userFeatures as $feature => $relevance) {
                if($first){
                    $first=0;                    
                }else{
                    $query_start .= ",";
                }
                if($relevance < 0.0001){
                    $relevance=0;                    
                }
                $query_start .= "($uid,$relevance,\"$feature\")";                
            }
            //echo $query_start.$query_end;
            $qResponse = $database->executeQuery( $query_start.$query_end );
            $qResponse = $database->executeQuery( "delete from `vsm_user_model` where `relevance`=0" );
        }
    }
    /*returns relevance for each feedback type according to the specific group the user belongs to*/
    private function getFeedbackRelevance($uid, $oid, $feedback, $value){
        
        $group = $uid % 4;
        //$group=3;
        if($group==1){
            //simple binary feedback
            if($feedback=="pageview"){
                return 1;
            }else{
                return 0;
            }
        }else if($group==2){
            return $this->getDirectFeedback($uid, $oid, $feedback, $value);
        }else if($group==0){
            //more complex computing of user preferences depending on the feedback type
            $feedback_types = array("onpageTime", "scroll", "deep_pageview", "pageview", "order");
            $epsilons = array(0, 0.01, 0.7, 0.7,0);
            $methods = array("direct", "knn", "knn", "knn", "order");
            
            $fid = array_search($feedback, $feedback_types);
            
            switch ($methods[$fid]) {
                case "direct":
                    return $this->getDirectFeedback($uid, $oid, $feedback, $value);
                    break;
                case "knn":
                    return $this->getKNNFeedback($uid, $oid, $feedback, $value, $epsilons[$fid]);
                    break;
                case "order":
                    return 1;
                    break; 
            }                        
        }else if($group==3){ 
            if($feedback=="pageview"){
                return $this->getConsolidatedFeedback($uid, $oid);
                
            }else{
                return 0;
            }
        }       
    }
    /**
     * returns aggregated value of ln(dwellTime)+ln(mouseTime)+ln(scrollTime) normalized to the per-user maximum
     * @param type $uid
     * @param type $oid
     */
    function getConsolidatedFeedback($uid, $oid, $option="non-negative"){
        //needs to select other database connection for slantour, irrelevant otherwise
        $dbServer = "127.0.0.1"; //adresa databazoveho serveru
	$dbName = "slantourcz001";		//prihlašovací jméno k databázi
	$dbPasswd = "dovolena50";			//heslo k databázi
	$dbNameOfDatabase = "slantourcz";	//název databáze
        $link = mysqli_connect($dbServer, $dbName, $dbPasswd);
        mysqli_select_db($link, $dbNameOfDatabase);
        
        if($option=="non-negative"){
            $user_max_query = "SELECT max(ln((timeOnPage/1000)+1)+ ln((mouseMovingTime/1000)+1) + ln((scrollingTime/1000)+1)) as `score` FROM `new_implicit_events` where objectID>0 and userID=$uid";
            $query = mysqli_query($link,$user_max_query, $link);            
            while ($row = mysqli_fetch_array($query)) {
               $user_max_score =  $row["score"];
            }
            if($user_max_score==0){
               $user_max_score = 1;
            }
            $user_query = "SELECT sum(ln((timeOnPage/1000)+1)+ ln((mouseMovingTime/1000)+1) + ln((scrollingTime/1000)+1)) as `score` FROM `new_implicit_events` where objectID=$oid and userID=$uid";
            $query = mysqli_query($link,$user_query, $link);
            $user_score = 0;
            while ($row = mysqli_fetch_array($query)) {
               $user_score =  $row["score"];
            }  
          //  echo "user:$uid, object:$oid, score:".$user_score/$user_max_score;            
            return $user_score/$user_max_score;
        }
        
    }
    /**
     * returns linear normalization of the feedback over current users feedback
     * @param type $uid
     * @param type $oid
     * @param type $feedback
     * @param type $value
     * @return int
     */
    function getDirectFeedback($uid, $oid, $feedback, $value){
        $database = ComponentDatabase::get_instance();
        $tableName = Config::$implicitEventStorageTable;
        //simple per_user linear normalization AKA the more the better
        $query_user_max = "select max(`eventValue`) as `max` from `".$tableName."` "
                . "where `userID` = $uid and eventType=\"$feedback\" limit 1 ";
       // echo $query_user_max;
        $qResponse = $database->executeQuery( $query_user_max );
        if($qResponse->getQueryState()){
            $data = $qResponse->getResponseList();
            while($record = $database->getNextRow($data)){
               $max = $record["max"];
               if($max==0){
                   $max=1;
               }               
               return $value/$max;
            }
        }
        return 0;
    }
    /**
     * returns product of KNN value for current feedback
     * @param type $uid
     * @param type $oid
     * @param type $feedback
     * @param type $value
     * @return int
     */
    function getKNNFeedback($uid, $oid, $feedback, $value, $epsilon){
        $database = ComponentDatabase::get_instance();
        $tableName = Config::$implicitEventStorageTable;
        $knn=0;
        $query = "select `preference` from `implicit_preferences` where `eventType`=\"$feedback\" and `method`=\"knn_$epsilon\" order by abs(`eventValue`-$value) limit 1";
        $qResponse = $database->executeQuery( $query );
        if($qResponse->getQueryState()){
            $dbResponse = $qResponse->getResponseList();
            $qRow = $database->getNextRow($dbResponse);
            return $qRow["preference"];
        }   
        return 0;
      //  return 1/(1+  exp($e_arg));
        
        

    }    
    function calculateSimilarity(){
        $database = ComponentDatabase::get_instance();
        $query_no_objects = "select count(`object_id`) as `object_id` from `currently_computed_obj` where 1";
       // echo $query_no_objects;
        $qResponse = $database->executeQuery( $query_no_objects );
            if($qResponse->getQueryState()){
                $dbResponse = $qResponse->getResponseList();
                $qRow = $database->getNextRow($dbResponse);
                if($qRow["object_id"] <= Config::$maxComputedSimilarities){
                    $computation_possible = true;
                }else{
                    $computation_possible = false;
                }
            }else{
                $computation_possible = true;
            }
            
        if($computation_possible){
            $start_computation = false;      
            //$database->executeQuery( "START TRANSACTION" );
            $query_this_object = "select count(`object_id`) as `object` from `currently_computed_obj` where `object_id` = ".$this->event->getObjectID()."";           
            //echo $query_this_object;
            $qResponse = $database->executeQuery( $query_this_object );
            if($qResponse->getQueryState()){
                $dbResponse = $qResponse->getResponseList();
                $qRow = $database->getNextRow($dbResponse);
                if($qRow["object"] <= 0){
                    //muzeme pokracovat dal
                    $query_similarityLastModified = "select max(`last_modified`) as `date` from `".Config::$itemItemSimilarityTable."` where 
                                    `object_id1` = ".$this->event->getObjectID()." ";
                    //echo $query_similarityLastModified;
                    $qResponse2 = $database->executeQuery( $query_similarityLastModified );
                    if($qResponse2->getQueryState()){
                        $dbResponse2 = $qResponse2->getResponseList();
                        $qRow2 = $database->getNextRow($dbResponse2);
                        if($qRow2["date"] == "" or (strtotime($qRow2["date"]) <= strtotime("-2 hours") )){
                            //muzeme pokracovat dal, bud nebylo pocitano nikdy, nebo dele nez pred hodinou
                            
                            //pokud je pocitano pred vic nez 24 hodinami, nemusim se ptat na pocet novych dat
                            if($qRow2["date"] == "" or (strtotime($qRow2["date"]) <= strtotime("-24 hours") )){
                           //     $query_set_computing = "insert into `currently_computed_obj` (`object_id`) values (".$this->event->getObjectID().")";
                           //     $database->executeQuery( $query_set_computing );
                                $database->executeQuery( "Commit" );
                                $start_computation = true;
                            }else{
                                $query_new_data = "select count(`id`) as `count` from `".Config::$implicitEventStorageTable."` where 
                                    `objectId` = ".$this->event->getObjectID()." and 
                                    `lastModified` >= \"".$similarityLastModified."\" ";
                                //echo $query_new_data;
                                $qResponse3 = $database->executeQuery( $query_new_data );
                                if($qResponse3->getQueryState()){
                                    $dbResponse3 = $qResponse3->getResponseList();
                                    $qRow3 = $database->getNextRow($dbResponse3);
                                    if($qRow3["count"] >= Config::$minItemItemSimilarityComputationData){
                                        //budeme pocitat podobnost driv    
                                        $query_set_computing = "insert into `currently_computed_obj` (`object_id`) values (".$this->event->getObjectID().")";
                                        $database->executeQuery( $query_set_computing );
                                        $database->executeQuery( "Commit" );
                                        $start_computation = true;                                        
                                    }else{
                                        $database->executeQuery( "Rollback" );
                                    }
                                }
                            }
                                        
                        }else{
                            $database->executeQuery( "Rollback" );
                        }
                    }
                }else{
                    $database->executeQuery( "Rollback" );
                }                                                                                   
            }  
            if(!$start_computation){
               $database->executeQuery( "Rollback" );
            }else{
                //tady zacnu volat fce na pocitani podobnosti

                $this->compute_similarity($this->event->getObjectID());
                
                
                //vymazu ze pocitame tenhle objekt
                $query_stop_computing = "delete from `currently_computed_obj` where `object_id`=".$this->event->getObjectID()." limit 1";
                $database->executeQuery( $query_stop_computing );               
            }                        
        }                     
    }

  
  private  function compute_similarity($object_id) {
     /*todo compute*/
     $database = ComponentDatabase::get_instance(); 
     $object_similarities = array();
     $query_objects = "select distinct `objectID` from `".Config::$aggregatedEventStorageTable."` where `objectID`!=".$object_id."";
     //echo $query_objects;
     $qResponse = $database->executeQuery( $query_objects );
     if($qResponse->getQueryState()){         
         $dbResponse = $qResponse->getResponseList();
         while ($qRow = $database->getNextRow($dbResponse)){             
             $object_similarities[$qRow["objectID"]] = $this->compute_object_similarity($object_id, $qRow["objectID"]);             
         }
         
         arsort($object_similarities);
         //print_r($object_similarities);
         $i=0;
         $query = "delete from `".Config::$itemItemSimilarityTable."` where `object_id1`=".$object_id."";
         $database->executeQuery( $query );
         $query = "insert into `".Config::$itemItemSimilarityTable."` (`object_id1`, `object_id2`, `similarity`,`method`,`last_modified`) values";
         //ulozim nove podobnosti do databaze, zajima me prvnich K zaznamu
         foreach ($object_similarities as $key => $value) {
             if($i<=Config::$maxSimilarObjects){                                              
                if($value > 0){
                    if($i>=1){
                        $query .=", ";
                    }
                    $query .= " (".$object_id.", ".$key.", ".$value.",\"coll_cos\", \"".Date("Y-m-d H:i:s")."\")";
                    $i++;                   
                }
             }else{
                 break;
             }
             
         }
         echo $query; 
         $database->executeQuery( $query );
         
     }
 }

  private  function compute_object_similarity($object_id1, $object_id2) {
     /*todo compute*/
      $database = ComponentDatabase::get_instance(); 
     $user_rating1 = array();
     $user_rating2 = array();
     $cosine_sum = 0;
     $cosine_sqr1 = 0;
     $cosine_sqr2 = 0;
     
     $result = 0;
     $query_users = "SELECT DISTINCT `ie1`.`userID`
                        FROM `".Config::$implicitEventStorageTable."` as `ie1` 
                        join `".Config::$implicitEventStorageTable."` as `ie2` on (`ie1`.`userID` = `ie2`.`userID`)
                        WHERE `ie1`.`objectID` =".$object_id1." and `ie2`.`objectID` = ".$object_id2."";
     //echo $query_users;
     $qResponse = $database->executeQuery( $query_users );
     if($qResponse->getQueryState()){
         $dbResponse = $qResponse->getResponseList();
         $count_users = 0;
         while ($qRow = $database->getNextRow($dbResponse)){
             //prubezne pocitani cosinove podobnosti; jine jsou take mozne - casem
             $ur1 = $this->compute_user_object_rating($object_id1,$qRow["userID"]);     
             $ur2 = $this->compute_user_object_rating($object_id2,$qRow["userID"]);  
             $count_users++;
             
             $cosine_sum = $cosine_sum + ($ur1*$ur2);
             $cosine_sqr1 = $cosine_sqr1 + ($ur1*$ur1);
             $cosine_sqr2 = $cosine_sqr2 +($ur2*$ur2);
         } 
         echo $object_id1.",".$object_id2.";".$cosine_sum."-".$cosine_sqr1."-".$cosine_sqr2.", ".$count_users."\n";
         //chci data alespon od 2 nezavislych uzivatelu
         if($cosine_sqr1>0 and $cosine_sqr2>0 and $count_users>=2){ 
            $result = $cosine_sum / (sqrt($cosine_sqr1)*sqrt($cosine_sqr2));
           // echo $result.";;".$cosine_sum."-".$cosine_sqr1."-".$cosine_sqr2."\n";
         }else{
             return 0;
         }
         //
     }
     
     return $result;
 }
 
 
  private  function compute_user_object_rating($object_id, $user_id) {
     /*todo compute*/  
      $database = ComponentDatabase::get_instance(); 
     $feedback = array();  
     $rating = 0;
     $query_feedback = "select * from `".Config::$implicitEventStorageTable."` where `objectID`=".$object_id." and `userID`=".$user_id." ";
     //echo $query_feedback;
     $qResponse = $database->executeQuery( $query_feedback );
     if($qResponse->getQueryState()){
         $dbResponse = $qResponse->getResponseList();
         while ($qRow = $database->getNextRow($dbResponse)){
             $feedback[$qRow["eventType"]] = $qRow["eventValue"];   
             //echo $object_id.$qRow["eventType"]." = ".$qRow["eventValue"]." ;;\n";
             //prozatim velmi primitivne agregace ratingu
             $rating = $rating + (Config::$eventImportance[$qRow["eventType"]] *  intval($qRow["eventValue"]) );
         }                           
     }
     //echo $object_id." --- ".$rating." ---\n";
     return $rating;
  }
   
    
    
}
?>
