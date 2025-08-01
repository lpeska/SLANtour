<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UserExpression
 *
 * @author peska
 */
class UserExpression {
    //put your code here
    /**
     * user about who is the expression - can be null
     */
    private $userID;
    /**
     * what do we want to do: depends on Configuration i.e. ObjectRating, ObjectSimilarity
     */
    private $expressionType;
    /**
     * which method to use: depends on Configuration i.e. Standard, Dummy, Aggregated etc.
     */
    private $evaluationMethod;
    /**
     *array of parameterName => parameterValue
     */
    private $methodParameters;

    /**
     * importance of this expression: integer 1-n
     */
    private $importance;
/**
 *
 * @param <type> $userID user about who is the expression - can be null
 * @param <type> $expressionType what do we want to do: depends on Configuration i.e. ObjectRating, ObjectSimilarity
 * @param <type> $importance importance of this expression: integer 1-n
 * @param <type> $evaluationMethod which method to use: depends on Configuration i.e. Standard, Aggregated etc
 * @param <type> $methodParameters array of parameterName => parameterValue
 */
function __construct($userID, $expressionType, $importance="", $evaluationMethod="", $methodParameters="" ){
        /*TODO: check allowed values*/
        if($importance == ""){
            $importance = Config::$defaultImportanceForExpressionType[$expressionType];
        }
        if($evaluationMethod == ""){
            $evaluationMethod = Config::$defaultMethodForExpressionType[$expressionType];
            $methodParameters = Config::$defaultParametersForMethod[$evaluationMethod];
        }
        if(!is_array($methodParameters) ){
            $methodParameters = Config::$defaultParametersForMethod[$evaluationMethod];
        }

        $this->userID = $userID;
        $this->expressionType = $expressionType;
        $this->importance = $importance;
        $this->evaluationMethod = $evaluationMethod;
        $this->methodParameters = $methodParameters;
    }

/**
 * getter for userID
 * @return <type> userID
 */
    public function getUserID(){
        return $this->userID;

    }
/**
 * getter for expressionType
 * @return <type> expressionType
 */
    public function getExpressionType(){
        return $this->expressionType;

    }
/**
 * getter for evaluationMethod
 * @return <type> evaluationMethod
 */
    public function getEvaluationMethod(){
        return $this->evaluationMethod;

    }
/**
 * getter for methodParameters
 * @return <type> methodParameters
 */
    public function getMethodParameters(){
        return $this->methodParameters;

    }
/**
 * getter for importance
 * @return <type> importance
 */
    public function getImportance(){
        return $this->importance;

    }

}
?>
