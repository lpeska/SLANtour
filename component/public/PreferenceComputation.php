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
class PreferenceComputation{
    //put your code here
    private $eventType;
    
/**
 * @param <type> $event instance of the ExplicitUserEvent class
 */
    function __construct($eventType){
        $this->eventType = $eventType;
    }

    /**
     * saves event to the database
     */
    public function computePreferences($no_clusters=10){
        /**
         * TODO: kontrola typu udalosti, pripadne akce svazane s typem
         */

         /* check whether we have an approved event*/
         if( in_array($this->eventType, Config::$recognizedImplicitEvent ) ){
            $implicit_table = Config::$implicitEventStorageTable; 
            $database = ComponentDatabase::get_instance();
            $query_clusters = "
                    select count(`objectID`) as `count`, `eventValue` from
                        `".$implicit_table."`
                    where `eventType`=\"".$this->eventType."\" 
                    group by `eventValue` 
                    order by `eventValue`
                ";
            //echo $query_clusters;
            $sum_events = 0;
            $values_count = array();
            $qResponse = $database->executeQuery($query_clusters);
            if ($qResponse->getQueryState()) {                
                $dbResponse = $qResponse->getResponseList();
                
                while ($qRow = $database->getNextRow($dbResponse)) {
                    
                    //pole hodnota -> pocet
                    $values_count[$qRow["eventValue"]] = $qRow["count"];
                    //celkovy pocet udalosti
                    $sum_events = ($sum_events + intval($qRow["count"]));                   
                }
            }
            
            //echo $sum_events;
            $max_no = 10000000;
            $clusters = array();
            //velice primitivni clusterovani bude chtit vylepsit
            $ideal_cluster_size = ($sum_events/$no_clusters);
            $remaining_items = $sum_events;
            $remaining_clusters = $no_clusters;
            $last_key = 0;
            $item_no = 0;
            foreach ($values_count as $key => $value) {
                $division_point = ($ideal_cluster_size * 2/3);                
                if($item_no >= $division_point){
                   $clusters[$key] = $item_no;                   
                   $remaining_clusters--;
                   $remaining_items = $remaining_items - $item_no;
                   if($remaining_clusters>0){
                       $ideal_cluster_size = ($remaining_items/$remaining_clusters);
                   }else{
                       $ideal_cluster_size = $max_no;
                   } 
                   $last_key = $key;
                   $item_no = 0;
                }
                $item_no = $item_no + $value;
                
            }
            if($item_no>0){
                $clusters[$max_no] = $item_no; 
            }else{
                //posledni cluster bude mit maximalni hodnotu, aby do nej spadlo vse
                $clusters[$max_no] = $clusters[$last_key];
                unset($clusters[$last_key]);
            }

            //hodnoty se ukladaji pro vypocet rozptylu
            $value_array = array();
            $cluster_orders = array();
            $query_orders = "
                    select `ie1`.`eventValue` as `value`, `ie2`.`eventValue` as `order` from
                        `".$implicit_table."` as `ie1` join `".$implicit_table."` as `ie2` 
                            on(`ie1`.`userID` = `ie2`.`userID` and `ie1`.`objectID` = `ie2`.`objectID` and
                            `ie1`.`eventType`=\"".$this->eventType."\" and `ie2`.`eventType`=\"order\")
                    where 1   
                    order by `value`
                ";
            //echo $query_orders;
            
            $qResponse = $database->executeQuery($query_orders);
            if ($qResponse->getQueryState()) {
                $dbResponse = $qResponse->getResponseList();
                while ($qRow = $database->getNextRow($dbResponse)) {
                    //pole hodnota -> pocet
                    
                    
                    $value = $qRow["value"];
                    $order = $qRow["order"];                
                    $i=0;
                    foreach ($clusters as $key => $val) {
                        $i++;
                        if($key > $value){
                            $value_array[] = $i;//do kolikateho clusteru hodnota patri?
                            $cluster_orders[$key] = $cluster_orders[$key] + $order;
                            break;
                        }
                    }                   
                }
            }
            //nyni mame 2 pole - jedno s poctem prvku, druhe s poctem objednavek, udelame preferenci
            $resulting_clusters = array();
            foreach ($clusters as $key => $val) {
                $item_no = $clusters[$key];
                $purchase_no = $cluster_orders[$key];
                $preference = $purchase_no/$item_no;
                $resulting_clusters[$key] = $preference;
            }
            print_r($clusters);
            print_r($cluster_orders);
            print_r($resulting_clusters);
            //confidence of each data cluster
            $std_dev = sqrt($this->variance($value_array));
            
            $query_delete = "delete from `".Config::$meta_pref_table."` where `eventType`=\"".$this->eventType."\"";
            $database->executeQuery( $query_delete );
            
            $query_insert = "insert into `".Config::$meta_pref_table."` (`border_from`, `border_to`, `preference`, `confidence`, `eventType`, `lastModified`) values ";
            
            $i=0;
            $last_border = 0;
            foreach ($resulting_clusters as $key => $val) {
                if($i==0){
                    $i++;
                }else{
                    $query_insert.= ", ";
                }
                $query_insert.= "(".$last_border.", ".$key.", ".$val.", ".$std_dev.", \"".$this->eventType."\", \"".Date("Y-m-d H:i:s")."\")";
                $last_border = $key;
            }
            //echo $this->event->getSQL();
           // echo $query_insert;
            $database->executeQuery( $query_insert );
                       
           // $database->disconnect();
         }
    }
    
    /**
     * compute average of an array
     */
function average($arr){
    if (!count($arr)) return 0;
    $sum = 0;
    foreach ($arr as $key => $value) {
        $sum += $value;
    }
    return $sum / count($arr);
}

/**
 * compute variance of array values
 */
function variance($arr){
    if (!count($arr)) return 0;
    $mean = $this->average($arr);
    $sos = 0;    // Sum of squares
    foreach($arr as $key => $value){
        $sos += ($value - $mean) * ($value - $mean);
    }

    return $sos / (count($arr)-1);  // denominator = n-1; i.e. estimating based on sample
                                    // n-1 is also what MS Excel takes by default in the
                                    // VAR function
}
    
    
    
    
}
?>
