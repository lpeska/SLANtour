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
    private $queryResponseArray;
    private $queryObjectRelevance;
    private $sqlImportance;

    public function __construct($sql, $attributesArray, $userExpressionArray, $objectExpressionArray, $sqlImportance = 1){
        $this->sql = $sql;
        $this->attributesArray = $attributesArray;
        $this->userExpressionArray = $userExpressionArray;
        $this->objectExpressionArray = $objectExpressionArray;
        $this->userExpressionResult = array();
        $this->objectExpressionResult = array();
        $this->createQuery();
        $this->sqlImportance = $sqlImportance;
    }

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
                $this->noOfObjects = intval( trim($limArray[1]) )+1;
            }else {
                $this->objectsStartFrom = 0;
                $this->noOfObjects = intval( trim($lim) )+1;
            }
            //not good to keep unlimited - possible memory exhaustion problems (if too many objects)
            $this->query->setLimit("limit ".Config::$safeObjectsInQueryLimit);
           // $this->query->setLimit("");
        }
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
                    $this->userExpressionResult[] = $this->normalize($exprResult);
                }                
            }
            if(is_array($this->objectExpressionArray)) {
                foreach ($this->objectExpressionArray as $expr) {
                    $exprResult = $this->evaluateExpression($expr);
                    $this->objectExpressionResult[] = array($expr->getImportance(), $this->normalize($exprResult) );
                }
            }
            //print_r($this->objectExpressionResult);
            $objectResult = $this->aggregateExprResults($this->objectExpressionResult);
            $this->queryResponse = new QueryResponse(1, "", $this->filterObjects($objectResult));
        }else{
            return false;
        }

        //we have qResponse, deal with user and object expressions

    }

private function  filterObjects($objectResult){
    /*improve this - not to remember all of the objects, but just top - limit*/
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
        foreach($exprResults as $er) {
            $importance = $er[0];
            $exprResult = $er[1];
            $sumImportance += $importance;

            foreach ($exprResult as $objectID => $value) {
                    if(!isset($aggregatedResultArray[$objectID])){
                        $aggregatedResultArray[$objectID] = 0;
                    }
                    $aggregatedResultArray[$objectID] = $aggregatedResultArray[$objectID] + $value * $importance;
            }
        }
        arsort($aggregatedResultArray);
        //print_r($aggregatedResultArray);
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
        //print_r($result);
        return $result;
    }


    private function evaluateExpression($expr) {
        if( in_array($expr->getExpressionType(),Config::$allowedExpressionTypes ) and
            in_array($expr->getEvaluationMethod(),Config::$allowedExpressionTypeMethods )) {
            $reflClass = new ReflectionClass($expr->getEvaluationMethod());
            if( $reflClass->implementsInterface($expr->getExpressionType()) ) {
                $reflMethod = $reflClass->getMethod(Config::getMethodForExpression($expr));
                $arguments = array();
                $methodParams = $expr->getMethodParameters();

                foreach($reflMethod->getParameters() as $param) {
                            /* @var $param ReflectionParameter */
                    if($param->getName()=="usersArray" and isset( $methodParams[$param->getName()] )){
                        if(!is_array($methodParams[$param->getName()])){
                           $arguments[] = array_keys( $this->userExpressionResult[$methodParams[$param->getName()]] );
                        }else{
                           $arguments[] =  $methodParams[$param->getName()];
                        }

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
                //print_r($arguments);
                /**
                 * TODO: improve getting methods!! (eval)
                 */
                $methodClass = $this->getMethod($expr->getEvaluationMethod());
                $result = $reflMethod->invokeArgs($methodClass, $arguments);
                //print_r($result);
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
 * returns class instance for the given name
 * @param <type> $methodName name of the demanded class
 * @return instance of the class
 */
    private function getMethod($methodName){
        switch ($methodName) {
            case "Aggregated":
                return new Aggregated();
                break;
            case "Dummy":
                return new Dummy();
                break;
            case "Standard":
                return new Standard();
                break;

            default:
                return NULL;
                break;
        }
    }


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
 * @return <type>
 */
    public function getQueryResponse() {
        return $this->queryResponse;
    }
}
?>
