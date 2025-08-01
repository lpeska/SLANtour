<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExtendedQuerySender
 *
 * @author peska
 */
class ComplexQueryHandler extends AbstractQueryHandler implements QueryHandler {
    private $objectParams;
    private $sql;
    private $queryReceiver;
    private $query;
    private $attributesArray;
    private $userExpressionArray;
    private $userExpressionResult;
    private $objectExpressionArray;
    private $objectExpressionResult;
    private $objectsStartFrom;
    private $objectList;
    private $noOfObjects;
    private $select;
    private $queryResponseArray;
    private $queryObjectRelevance;
    private $sqlImportance;
    private $useObjectRestriction;
/**
 *
 * @param <type> $sql the sql part of the component query - specifies basic condition, that every object must accomplish
 * @param <type> $attributesArray array of Attribute class - other condition directly mapped into the sql query
 * @param <type> $userExpressionArray Array of UserExpression class instances - statements about the user
 * @param <type> $objectExpressionArray array of ObjectExpression class instances - statements about the objects
 * @param <type> $sqlImportance - importance of the sql query
 * @param <type> $useObjectRestriction - if true, then when first method from $objectExpressionArray returns some objects,
 * other methods in $objectExpressionArray will evaluate only theese objects (good if the first method is "primary" and others "supplementary"
 */
    public function __construct($sql, $attributesArray, $userExpressionArray, $objectExpressionArray, $sqlImportance = 1, $useObjectRestriction = false){
        $this->objectParams = "";
        $this->sql = $sql;
        $this->attributesArray = $attributesArray;
        $this->userExpressionArray = $userExpressionArray;
        $this->objectExpressionArray = $objectExpressionArray;
        $this->userExpressionResult = array();
        $this->objectExpressionResult = array();
        $this->createQuery();
        $this->sqlImportance = $sqlImportance;
        $this->useObjectRestriction = $useObjectRestriction;
//print_r($this);
    }

    public function getObjectParams() {
        return $this->objectParams;
    
    }
/**
 * send query to the database handler
 */
    public function sendQuery() {
        
    /**
     * TODO: User and object expression processing!
     */

    //we will need the whole list of objects...
        if((is_array($this->userExpressionArray) and sizeof($this->userExpressionArray)!=0) or
            (is_array($this->objectExpressionArray) and sizeof($this->objectExpressionArray)!=0)) {
            $lim = $this->query->getLimit();
            $lim = str_ireplace("limit", "", $lim);
            if(strpos($lim, ",")!==false){
                $limArray = explode(",", $lim);
                $this->objectsStartFrom = intval( trim($limArray[0]) );
                $this->noOfObjects = intval( trim($limArray[1]) );
            }else {
                $this->objectsStartFrom = 0;
                $this->noOfObjects = intval( trim($lim) );
            }
            //we need only the id of the objects
            $this->select = $this->query->getSelect();
            $this->query->setSelect("select distinct `".Config::$objectTableName."`.`".Config::$objectIDColumnName."` ");
            //not good to keep unlimited - possible memory exhaustion problems (if too many objects)
            //$this->query->setLimit("limit ".Config::$safeObjectsInQueryLimit);
            $this->query->setLimit("");
            
           // print_r($this->query); 
            //echo "<!--inside QHandler-->";
        }
        //echo "<!--".$this->query->getSQL()."-->";
        /**
         * TODO: ensure that objectID is inside the query!!!
         */
        if(is_array($this->attributesArray) and sizeof($this->attributesArray)!=0) {
            $this->queryReceiver = new ExtendedQueryToDatabaseInteraction();
            $this->queryReceiver->getQuery($this->query, $this);
        }else {
            $this->queryReceiver = new BasicQueryToDatabaseInteraction();
            $this->queryReceiver->getQuery($this->query, $this);
        }

        if($this->queryResponse->getQueryState() ){
            //get list of object -> relevance
            $this->associateToArray($this->queryResponse->getResponseList());
            $this->objectList = array_keys($this->queryObjectRelevance);
            $this->objectExpressionResult[] = array($this->sqlImportance, $this->queryObjectRelevance);

            /**
             * todo: as function
             */
            if(is_array($this->userExpressionArray)) {
                foreach ($this->userExpressionArray as $expr) {
                    $exprResult = $this->evaluateExpression($expr);
                    $userExprResultsArray[] = array($expr->getImportance(), $this->normalize($exprResult) );
                }
                $this->userExpressionResult = $this->aggregateExprResults($userExprResultsArray);
                echo "<!-- users";
                    //print_r(array_slice($this->userExpressionResult, 0, 50, true));
                echo "-->";
            }
            

            if(is_array($this->objectExpressionArray)) {
                $first=true;
                foreach ($this->objectExpressionArray as $expr) {
                    $exprResult = $this->evaluateExpression($expr);
                    $this->objectExpressionResult[] = array($expr->getImportance(), $this->normalize($exprResult) );
                    //print_r($this->objectExpressionResult);
                    //for the other than first object expressions we will use only that ones, which was selected by the first method
                    //(this is if you expect, that first method gives the main set of objects - has big importance
                    //  and other methods just produces auxiliary feedback which can move objects a bit)

                    if($first and $this->useObjectRestriction and sizeof($exprResult)>=$this->noOfObjects ){
                        $this->objectList = array_keys($exprResult);
                        $first=false;
                    }
                }
            }
            //print_r($this->objectExpressionResult);

            //make the original search with specified objects
            $objectResult = $this->aggregateExprResults($this->objectExpressionResult);
           // print_r($objectResult);
            $objectResult = $this->getDemandedObjectsFromList($objectResult);
            $listOfObjects = " `".Config::$objectTableName."`.`".Config::$objectIDColumnName."` in(".implode(",", array_keys($objectResult) ).") ";
             //echo "<!--inside QHandler".$listOfObjects."-->";
            if($this->query->getWhere()!=""){
               $this->query->setWhere($this->query->getWhere()." and ".$listOfObjects);
            }else{
               $this->query->setWhere(" WHERE ".$listOfObjects);
            }
            $this->query->setSelect($this->select);
            $this->query->setLimit("limit ".$this->noOfObjects);
            
            //echo $this->query->getSQL();
            if(is_array($this->attributesArray) and sizeof($this->attributesArray)!=0) {
                $this->queryReceiver = new ExtendedQueryToDatabaseInteraction();
                $this->queryReceiver->getQuery($this->query, $this);
            }else {
                $this->queryReceiver = new BasicQueryToDatabaseInteraction();
                $this->queryReceiver->getQuery($this->query, $this);
            }

            if($this->queryResponse->getQueryState() ) {
            //get list of object -> relevance
                $this->associateToArray($this->queryResponse->getResponseList());
                $this->queryResponse = new QueryResponse(1, "", $this->filterObjects($objectResult));
            }else {
                return $this->queryResponse;
            }
        }else{
            return $this->queryResponse;
        }

        //we have qResponse, deal with user and object expressions

    }
/**
 * slices the list of objects or users to demanded size defined in the SQL
 * @param <type> $objectResult list of objects
 * @return <type> sliced list
 */
private function  getDemandedObjectsFromList($objectResult){
        return array_slice($objectResult, $this->objectsStartFrom, $this->noOfObjects, TRUE);
}

/**
 * this function creates result set of the sendQuery() method.
 * it adds relevance counted before to the other attributes of the Database response query and then slice it to the demanded size
 * @param <type> $objectResult list of objects
 * @return <type> array of array -  SQL result rows
 */
private function  filterObjects($objectResult){
    $result = array();
    foreach ($objectResult as $objectID => $relevance) {
        $record = $this->queryResponseArray[$objectID];
        $record["relevance"] = $relevance;
        $result[] = $record;
    }
    if($this->noOfObjects > 0){
        return array_slice($result, $this->objectsStartFrom, $this->noOfObjects);

    }
    return $result;
}

/**
 * aggregate values for objects from all objectExpressions
 * uses simple weighted aritmetic average
 * @param <type> $exprResults result array from objectExpressions
 * @return <type> array objectID -> relevance in [0,1] interval
 */
    private function aggregateExprResults($exprResults) {
        $aggregatedResultArray = array();
        $sumImportance = 0;
        if(is_array($exprResults)) {
            foreach($exprResults as $er) {
                $importance = $er[0];
                $exprResult = $er[1];
                $sumImportance += $importance;

                foreach ($exprResult as $objectID => $value) {
                    if(!isset($aggregatedResultArray[$objectID])) {
                        $aggregatedResultArray[$objectID] = 0;
                    }
                    $aggregatedResultArray[$objectID] = $aggregatedResultArray[$objectID] + $value * $importance;
                }
            }
            arsort($aggregatedResultArray);
        //print_r($aggregatedResultArray);
        }
        $resArray = $this->normalize($aggregatedResultArray, $sumImportance);
        return $resArray;
    }

    /**
     *function normalizes array of objectID -> value: array is expected to be sorted by value decs
     * function divides each value by the first one, so results is in [0,1] interval
     * @param <type> $array array of id->value
     * @return <type> normalized array of id->value
     */
    private function normalize($array, $first=null){
        $result = array();
       // print_r($array);
       if(is_array($array)){
        foreach($array as $object=>$value){
            if($first == null){
                $first = $value;
                if($first == 0){
                    $first=1;
                }
            }
            $newval = $value / $first;
            if($newval <= 0){
                $newval = 0;
            }
            $result[$object] = $newval;
        }
       }
        
        return $result;
    }

//gets results from one object or user expression
    private function evaluateExpression($expr) {
       // echo $expr->getExpressionType().$expr->getEvaluationMethod();
        if( in_array($expr->getExpressionType(),Config::$allowedExpressionTypes ) and
            in_array($expr->getEvaluationMethod(),Config::$allowedExpressionTypeMethods )) {
            $reflClass = new ReflectionClass($expr->getEvaluationMethod());
            if( $reflClass->implementsInterface($expr->getExpressionType()) ) {
                $reflMethod = $reflClass->getMethod(Config::getMethodForExpression($expr));
                $arguments = array();
                $methodParams = $expr->getMethodParameters();

                foreach($reflMethod->getParameters() as $param) {
                            /* @var $param ReflectionParameter */
                    if($param->getName()=="usersArray" and isset( $methodParams[$param->getName()]) and is_array($methodParams[$param->getName()]) ){
                           $arguments[] =  $methodParams[$param->getName()];
                    }else if($param->getName()=="usersArray"){
                           $arguments[] = $this->userExpressionResult;

                    }else if( isset( $methodParams[$param->getName()] ) ) {
                        $arguments[] = $methodParams[$param->getName()];
                    }else {
                        if($param->getName()=="objectList") {
                                $arguments[] = $this->objectList;
                        }else if($param->isDefaultValueAvailable()) {
                            $arguments[] = $param->getDefaultValue();
                        }else{
                                $arguments[] = Config::getDefaultValueForParameter($param);
                        }
                    }

                }
                //print_r("<!--".$arguments."-->");
                /**
                 * TODO: improve getting methods!! (eval)
                 */
                //print_r($reflMethod);
                $methodClass = $expr->getEvaluationMethod();
                $class = new $methodClass;
                $result = $reflMethod->invokeArgs($class, $arguments);
                //print_r($result);
                
                /*hack to get textual attributes for individual objects*/
                
                if($expr->getEvaluationMethod() == "External"){
                    $this->objectParams = $class->getObjectParams();
                }
                
                
                return $result;


            }else {
            //method doesnt support expression type
                return false;
            }
        }else {
        //method or expression type not allowed
            return false;
        }
    }


/**
 * transform MySQL resource into the arrays
 * @param <type> $mysqlResponse response from the Database - QueryResponse class
 */
    private function associateToArray($mysqlResponse){
        //not necesarry to save all this data (possible second query) and in first get only the objectID
       $database = ComponentDatabase::get_instance();
       $objectID = Config::$objectIDColumnName;

       $this->queryResponseArray = array();
         while( $record = $database->getNextRow($mysqlResponse) ) {
             $this->queryResponseArray[$record[$objectID]] = $record;
             if($record["relevance"]!=""){
                 $this->queryObjectRelevance[$record[$objectID]] =  $record["relevance"];
             }else{
                 $this->queryObjectRelevance[$record[$objectID]] =  1;
             }
         }
       $database->freeResult($mysqlResponse);
    }

    /**
     * creates class type Query
     * (parameters, if any is expected to be stored as variables by other methods before createQuery() is called)
     */
    private function createQuery() {
        $this->query = new Query($this->sql, $this->attributesArray);
    }

/**
 * returns response for the given query
 * @return <type> instance of QueryResponse class
 */
    public function getQueryResponse() {
        return $this->queryResponse;
    }
}
?>
