<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Attribute
 *
 * @author peska
 */
class Attribute {
    //put your code here
    private $attributeName;
    private $attributeType;
    private $expectedValueFrom;
    private $expectedValueTo;
    private $toleranceFrom;
    private $toleranceTo;
    private $importance;
    private $excludeUnsufficient;

/**
 * "Full" Attributes constructor
 * @param <type> $attrName full SQL name of the attribute
 * @param <type> $attrType  type of the attribute
 * @param <type> $valueFrom "good" value of the attribute from (100% relevance)
 * @param <type> $valueTo "good" value of the attribute to (100% relevance
 * @param <type> $toleranceFrom  limit distance from $valueFrom, where relevance is 0
 * @param <type> $toleranceTo limit distance from $valueTo, where relevance is 0
 * @param <type> $importance 0-10 importance of this attribute
 * @param <type> $exclude  exclude results that doesnt get into the tolerance
 */
    function __construct($attrName, $attrType, $valueFrom, $valueTo, $toleranceFrom, $toleranceTo, $importance, $exclude){
        $this->attributeName = $attrName;
        $this->attributeType = $attrType;
        $this->expectedValueFrom = $valueFrom;
        $this->expectedValueTo = $valueTo;
        $this->toleranceFrom = $toleranceFrom;
        $this->toleranceTo = $toleranceTo;
        $this->importance = $importance;
        $this->excludeUnsufficient = $exclude;
    }  

/**
 * getter for $this->attributeName;
 * @return <type> attribute sql name
 */
    public function getAttributeName(){
        return $this->attributeName;
    }

/**
 * getter for $this->attributeType;
 * @return <type> attribute type
 */
    public function getAttributeType(){
        return $this->attributeType;
    }

/**
 * getter for $this->expectedValueFrom;
 * @return <type> attribute expected value - start of the interval
 */
    public function getExpectedValueFrom(){
        return $this->expectedValueFrom;
    }

/**
 * getter for $this->expectedValueTo;
 * @return <type> attribute expected value - end of the interval
 */
    public function getExpectedValueTo(){
        return $this->expectedValueTo;
    }

/**
 * getter for $this->toleranceFrom;
 * @return <type> attribute tolerance for the expectedValueFrom
 */
    public function getToleranceFrom(){
        return $this->toleranceFrom;
    }

/**
 * getter for $this->toleranceTo;
 * @return <type> attribute tolerance for the expectedValueTo
 */
    public function getToleranceTo(){
        return $this->toleranceTo;
    }

/**
 * getter for $this->importance;
 * @return <type> attribute importance
 */
    public function getImportance(){
        return $this->importance;
    }

/**
 * getter for $this->excludeUnsufficient;
 * @return <type> 1 if to exclude all objects that doesnt match this attribute, 0 otherwise
 */
    public function getExcludeUnsufficient(){
        return $this->excludeUnsufficient;
    }
}
?>
