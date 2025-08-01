<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Query
 * Crate class for keeping the Component Query
 * Contains SQL, Attributes, User and Object expressions
 * @author peska
 */
class Query {
    //put your code here
    /*basic SQL Query, parsed*/
    private $sqlSelect;
    private $sqlFrom;
    private $sqlWhere;
    private $sqlGroup_by;
    private $sqlHaving;
    private $sqlOrder_by;
    private $sqlLimit;

    private $attributeArray;
    private $userExpressionArray;
    private $objectExpressionArray;

    /**
     *  constructor - parsing sql into the separate parts, sets other attributes
     * @param <type> $sqlSelectCode - sql code
     * @param <type> $attributeArray - adds other attributes into thedirect query
     * @param <type> $userExpressionArray - adds other expression concerning user (related user etc.)
     * @param <type> $objectExpressionArray - adds other object related expression (top voted, top buying, related...)
     */
    function __construct($sqlSelectCode, $attributeArray="", $userExpressionArray="", $objectExpressionArray=""){
        $this->parseSQL($sqlSelectCode);
        $this->attributeArray = $attributeArray;
        $this->userExpressionArray = $userExpressionArray;
        $this->objectExpressionArray = $objectExpressionArray;

    }

    /**
     * parse sql code - only the select statements (select ... from...where...)
     * and saves its parts into the sqlSth variables
     * @param <string> $sqlSelectCode sql code
     */
    private function parseSQL($sqlSelectCode){
        
        //get position of the keywords in the sql
        $posSelect = stripos($sqlSelectCode, "select ");
        $posFrom = stripos($sqlSelectCode, "from ");
        $posWhere = stripos($sqlSelectCode, "where ");
        $posGroupBy = stripos($sqlSelectCode, "group by ");
        $posHaving = stripos($sqlSelectCode, "having ");
        $posOrderBy = stripos($sqlSelectCode, "order by ");
        $posLimit = stripos($sqlSelectCode, "limit ");
        $posEnd = strlen($sqlSelectCode);

        //get them into the array
        $posArray = array( $posSelect, $posFrom, $posWhere, $posGroupBy, $posHaving, $posOrderBy, $posLimit, $posEnd);
        $i = count($posArray);

        //setting missing values
        while($i>1){
            if($posArray[$i-1]===false){
               $posArray[$i-1] = $posArray[$i];
            }
            $i--;
        }
        //setting the subSQLs
        $this->sqlSelect = substr($sqlSelectCode, $posArray[0], $posArray[1]-$posArray[0]);
        $this->sqlFrom = substr($sqlSelectCode, $posArray[1], $posArray[2]-$posArray[1]);
        $this->sqlWhere = substr($sqlSelectCode, $posArray[2], $posArray[3]-$posArray[2]);
        $this->sqlGroup_by = substr($sqlSelectCode, $posArray[3], $posArray[4]-$posArray[3]);
        $this->sqlHaving = substr($sqlSelectCode, $posArray[4], $posArray[5]-$posArray[4]);
        $this->sqlOrder_by = substr($sqlSelectCode, $posArray[5], $posArray[6]-$posArray[5]);
        $this->sqlLimit = substr($sqlSelectCode, $posArray[6], $posArray[7]-$posArray[6]);        
        
//        $sqlArray = array( $this->sqlSelect, $this->sqlFrom, $this->sqlWhere , $this->sqlGroup_by, $this->sqlHaving, $this->sqlOrder_by, $this->sqlLimit);
//        print_r($sqlArray);


    }

    /**
     * adds another attribute to the list
     * @param <type> $attribute  Attribute Class item
     */
    public function addAttribute($attribute){

    }


    /**
     * adds another user expression to the list
     * @param <type> $userExpression  UserExpression Class item
     */
    public function addUserExpression($userExpression){

    }

    /**
     * adds another object expression to the list
     * @param <type> $userExpression  ObjectExpression Class item
     */
    public function addObjectExpression($objectExpression){

    }

    /**
     * replaces current attributeArray with a new one
     * @param <type> $attributeArray  array of Attribute Class items
     */
    public function setAttributes($attributeArray){

    }

    /**
     * replaces current sqlParts with a new ones
     * @param <type> $sqlSelectCode  sql code to be parsed
     */
    public function setSQL($sqlSelectCode){

    }

    /**
     * setter for sqlSelect
     */
    public function setSelect($code){
         $this->sqlSelect = $code;
    }
    /**
     * setter for sqlFrom
     */
    public function setFrom($code){
         $this->sqlFrom = $code;
    }
    /**
     * setter for sqlWhere
     */
    public function setWhere($code){
         $this->sqlWhere = $code;
    }
        /**
     * setter for sqlGroup_by
     */
    public function setGroupBy($code){
         $this->sqlGroup_by = $code;
    }
    /**
     * setter for sqlHaving
     */
    public function setHaving($code){
         $this->sqlHaving = $code;
    }
    /**
     * setter for sqlOrder_by
     */
    public function setOrderBy($code){
         $this->sqlOrder_by = $code;
    }
    /**
     * setter for sqlLimit
     */
    public function setLimit($code){
         $this->sqlLimit = $code;
    }

     /**
     * getter for basic SQL part of the query
     */
    public function getSQL(){
        return $this->sqlSelect.$this->sqlFrom.$this->sqlWhere.$this->sqlGroup_by.$this->sqlHaving.$this->sqlOrder_by.$this->sqlLimit;
    }
    /**
     * getter for sqlSelect
     */
    public function getSelect(){
        return $this->sqlSelect;
    }
    /**
     * getter for sqlFrom
     */
    public function getFrom(){
        return $this->sqlFrom;
    }
    /**
     * getter for sqlWhere
     */
    public function getWhere(){
        return $this->sqlWhere;
    }
        /**
     * getter for sqlGroup_by
     */
    public function getGroupBy(){
        return $this->sqlGroup_by;
    }
    /**
     * getter for sqlHaving
     */
    public function getHaving(){
        return $this->sqlHaving;
    }
    /**
     * getter for sqlOrder_by
     */
    public function getOrderBy(){
        return $this->sqlOrder_by;
    }
    /**
     * getter for sqlLimit
     */
    public function getLimit(){
        return $this->sqlLimit;
    }

/**
 * getter for attributeArray
 */
    public function getAttributeArray(){
        return $this->attributeArray;
    }

}
?>
