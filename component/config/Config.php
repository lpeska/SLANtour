<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Config
 *
 * @author peska
 */
 class Config {
    //put your code here
    /**
     * @var <type> name of the table which stores implicit events
     */
    public static $implicitEventStorageTable = "implicit_events2";
    
    public static $itemItemSimilarityTable = "item_to_item_similarity";

    /**
     * @var <type> integer of maximal number of simultanously computed item-item similarities
     */
    public static $maxComputedSimilarities = 5;
    
    /**
     * @var <type> integer minimal required ammount of new data to start recomputation of item-item similarity early (after 2 hours wrt current settings)
     */
    public static $minItemItemSimilarityComputationData = 500;
    
    /**
     * @var <type> maximal number of stored objects similar to Oi
     */
    public static $maxSimilarObjects = 50;
    
    /*---------------------------prubezne pocitani vah udalosti-----------------*/
    /**
     * @var <type> ideal number of clusters into which the eventValues are divided
     */
    public static $noOfClusters = 10;
    
   /**
     * @var <type> table name of meta_preferences computed from feedback factors
     */
    public static $meta_pref_table = "meta_preference";
    
    /**
     * @var <type> array of known/allowed implicit eventNames
    */
    public static $recognizedImplicitEvent = array("order", "pageview", "deep_pageview","object_shown_in_list","object_opened_from_list","scroll","onpageTime","link_open","mouse_clicks",  "print", "comment", "bookmark");

    /**
     * @var <type> name of the table which stores explicit events
     */
    public static $explicitEventStorageTable = "explicit_events";

    /**
     * @var <type> array of known/allowed explicit eventNames
    */
    public static $recognizedExplicitEvent = array("user_rating");

    /**
     * @var <type> name of the table which stores aggregated events
     */
    public static $aggregatedEventStorageTable = "aggregated_events";

    /**
     * @var <type> array of known/allowed aggregated eventNames which is stored in the database
    */
    public static $recognizedAggregatedEvent = array("object_shown_in_list","object_opened_from_list","object_ordered","pageview","deep_pageview","scroll","onpageTime");

    /**
     * @var <type> array of known/allowed aggregated eventNames which can be evaluated by methods
    new functions: need to be implemented*/
    public static $recognizedAggregatedEvaluationEvent = array("opened_vs_shown_fraction","object_opened_from_list","object_ordered","pageview","deep_pageview","scroll","onpageTime");
    //public static $recognizedAggregatedEvaluationEvent = array("opened_vs_shown_fraction","object_opened_from_list","object_ordered");

    /**
     * @var <type> standard number of the objects shown in the list (used by Aggregated method for opened_vs_shown_fraction event)
    */
    public static $defaultListItemsCount = 10;

    /** new objects gets bonus in its opened_vs_shown_fraction score. this variable determines the limit which object is still new
     * if you want to cancel new object bonuses, just set this variable to 0
     * @var <type> specifies after how many "shown_in_list" count the new object stops to be threated as new
     * (used by Aggregated method for opened_vs_shown_fraction event)
    */
    public static $newObjectShownLimit = 20;

/** this var determines how to aggregate new values into the aggregation tables
 * i.e. if there is row that says objectID: 1 eventName: object_shown_in_list, eventValue: 11
 * ant new event is objectID: 1 eventName: object_shown_in_list, eventValue: 2
 * the result eventValue is eval(11 $aggregationFunction[object_shown_in_list] 2) in current case 11+2=13
 * @var <type> array of (AggregatedEventName => aggregation function)
 */
    public static $aggregationFunction = array("object_shown_in_list"=>"+", "object_opened_from_list"=>"+", "object_ordered"=>"+", "pageview"=>"+", "deep_pageview"=>"+", "scroll"=>"+", "onpageTime"=>"+");

/**
 * @var <type> array of standard events importance (eventName=>importance); importance is positive int
 */
    public static $eventImportance = array( 1=>1, "order"=>10, "pageview"=>1, "deep_pageview"=>5,  "print"=>2, "comment"=>2, "bookmark"=>2, "user_rating"=>5, "opened_vs_shown_fraction"=>5,"object_opened_from_list"=>1, "object_ordered"=>1, "scroll"=>2, "onpageTime"=>2);

/**
 * Known/allowed types of evaluation
 * @var <type> array of expressionNames
 */
    public static $allowedExpressionTypes = array("ObjectRating", "ObjectSimilarity", "UsersToObjectDataLookup",  "UserSimilarity");
/**
 * Known/allowed methods of evaluation
 * @var <type> array of methodNames
 * Note: not all methods have to support all expressionTypes - supported expressionTypes can be determined via implemented interfaces
 */
    public static $allowedExpressionTypeMethods = array("Dummy","Item_item_CF","VSM", "Standard", "StandardNegPref",  "Aggregated", "Attributes", "Randomized", "NRmse", "PearsonCorrelation","External");

/**
 * @var <type>  name of the table which stores users data - or at least users ID
 */
    public static $userTableName = "users";

/**
 * @var <type>  name of the collum that is unique identifier of the users.
 * Note: It is expected, that collumn type is INT!!
 */
    public static $userIDColumnName = "id_user";

/**
 * @var <type>  name of the table which stores objects data - or at least objects ID
 */
    public static $objectTableName = "serial";

/**
 * @var <type>  name of the collum that is unique identifier of the objects.
 * Note: It is expected, that collumn type is INT!!
 */
    public static $objectIDColumnName = "id_serial";

/**
 *this array is used by the UserHandler class to determine whether user displaying the page is human or any kind of the crawler
 * (we dont store any implicit/explicit/aggregated data for crawlers)
 * @var <type> array of the restricted substrings in the $SERVER[HTTP_USER_AGENT] var
 */
    public static $botsAndCrawlersNames = array("bot", "crawl", "yahoo", "slurp", "seznam", "morfeo", "msn", "google", "spider", "bing");

/**
 *this var is used by the Randomized method - it is the maximal number of DB rows, it is allowed to compute with
 * (each query to the database consists of " limit ".$safeObjectsInQueryLimit.")
 * @var <type> maximal limit of the rows in query
 * Note: this setting affect only the Randomized method - no other
 */
    public static $safeObjectsInQueryLimit = 1500;

/**
 *this var is used by the ExtendedQueryHandler class. if any attribute passed to the query is type "StringFulltext",
 * then mysql fultext search is started. it rates each object with Double value which is unfortunately unbounded.
 * while counting overall rating of the object, each StringFultext attribute subrating is divided with this number
 * @var <type>
 */
    public static $mysqlMatchDenominator = 20;


    /**this method is used by ComplexQueryHandler classes
     * this method returns function name for the passed expressionType
      * @param <type> $expr expressionType
      * @return <type>  method name
      */
    public static function getMethodForExpression($expr){
        /* @var $expr ObjectExpression */
        switch ($expr->getExpressionType()) {
                case "ObjectRating":
                    return "getBestObjects";
                    break;
                case "ObjectSimilarity":
                    return "getSimilarObjects";
                    break;
                case "UserSimilarity":
                    return "getSimilarUsers";
                    break;
                case "UsersToObjectDataLookup":
                    return "getBestObjectForUsers";
                    break;
                default:
                    break;
            }

        return "getBestObjects";
    }

    /**this method is used by ComplexQueryHandler classes
     * this method returns default parameter value for those parameters which was not specified in the query
     * @param <type> $param ReflectionParameter class instance
     * @return <type> parameter default value
     */
    public static function getDefaultValueForParameter($param){
        // todo: change this dummy
        return "";
    }
}
?>
