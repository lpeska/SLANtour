<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AttributeFactory
 *
 * @author peska
 */
class AttributeFactory {
    /**
     * constructing integer sttribute
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $valueFrom "good" value of the attribute from (100% relevance)
     * @param <type> $valueTo "good" value of the attribute to (100% relevance
     * @param <type> $tolerance limit distance from $valueFrom and $valueTo, where relevance is 0
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt get into the tolerance
     */
    public static function IntegerAttribute($attrName, $valueFrom, $valueTo, $tolerance=0, $importance=1, $exclude=0){
        return new Attribute($attrName, "int", $valueFrom, $valueTo, $tolerance, $tolerance, $importance, $exclude);
    }
    
    /**
     * constructing date sttribute
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $valueFrom "good" value of the attribute from (100% relevance)
     * @param <type> $valueTo "good" value of the attribute to (100% relevance)
     * @param <type> $tolerance limit distance in days from $valueFrom and $valueTo, where relevance is 0
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt get into the tolerance
     */
    public static function DateAttribute($attrName, $valueFrom, $valueTo, $tolerance=0, $importance=1, $exclude=0){
        return new Attribute($attrName, "Date", $valueFrom, $valueTo, $tolerance, $tolerance, $importance, $exclude);
    }

     /**
     * constructing bool sttribute
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $value  value of the attribute (100% relevance)
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt meet the value
     */
    public static function BoolAttribute($attrName, $value, $importance=1, $exclude=0){
        return new Attribute($attrName, "bool", $value, "", 0, 0, $importance, $exclude);
    }

     /**
     * constructing string sttribute with single value
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $expectedValue  value of the attribute (100% relevance)
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt meet the value
     */
    public static function StringAttribute($attrName, $expectedValue, $importance=1, $exclude=0){
        return new Attribute($attrName, "String", $expectedValue, "", 0, $tolerance, $importance, $exclude);
    }

     /**
     * constructing string sttribute with single value
      * this attribute will be evaluated via "match(attr) against(string)" method (fulltext search)
      * your database table must support this kind of expression (i.e. MyISAM in MySQL)
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $expectedValue  value of the attribute (100% relevance)
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt meet the value
     */
    public static function StringFulltextAttribute($attrName, $expectedValue, $importance=1, $exclude=0){
        return new Attribute($attrName, "StringFulltext", $expectedValue, "", 0, $tolerance, $importance, $exclude);
    }

     /**
     * constructing string sttribute with multiple values (enumeration)
     * @param <type> $attrName full SQL name of the attribute
     * @param <array> $valuesList  array of string - allowed values
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt meet the value
     */
    public static function StringMultipleValuesAttribute($attrName, $valuesList, $importance=1, $exclude=0){
        return new Attribute($attrName, "StringMultival", $valuesList, "", 0, 0, $importance, $exclude);
    }


/**
     * constructing integer sttribute for Attribute ObjectSimilarity method
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $valueFrom "good" value of the attribute from (100% relevance)
     * @param <type> $valueTo "good" value of the attribute to (100% relevance
     * @param <type> $tolerance limit distance from $valueFrom and $valueTo, where relevance is 0
     * @param <type> $importance 0-10 importance of this attribute
     * @param <type> $exclude   exclude results that doesnt get into the tolerance
     */
    public static function MethodIntegerAttribute($attrName, $tolerance=0, $importance=1){
        return new Attribute($attrName, "int", 0, 0, $tolerance, $tolerance, $importance, 0);
    }

     /**
     * constructing bool sttribute for Attribute ObjectSimilarity method
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $importance 0-10 importance of this attribute
     */
    public static function MethodBoolAttribute($attrName, $importance=1){
        return new Attribute($attrName, "bool", false, false, 0, 0, $importance, 0);
    }

     /**
     * constructing string sttribute for Attribute ObjectSimilarity method
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $expectedValue  value of the attribute (100% relevance)
     * @param <type> $importance 0-10 importance of this attribute
     */
    public static function MethodStringAttribute($attrName, $importance=1){
        return new Attribute($attrName, "String", "", "", 0, 0, $importance, 0);
    }
     /**
     * constructing string sttribute for Attribute ObjectSimilarity method
      * this attribute will be evaluated via "match(attr) against(string)" method (fulltext search)
      * your database table must support this kind of expression (i.e. MyISAM in MySQL)
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $expectedValue  value of the attribute (100% relevance)
     * @param <type> $importance 0-10 importance of this attribute
     */
    public static function MethodStringMatchAttribute($attrName, $importance=1){
        return new Attribute($attrName, "StringFulltext", "", "", 0, 0, $importance, 0);
    }
     /**
     * constructing date sttribute for Attribute ObjectSimilarity method
     * @param <type> $attrName full SQL name of the attribute
     * @param <type> $expectedValue  value of the attribute (100% relevance)
     * @param <type> $importance 0-10 importance of this attribute
     */
    public static function MethodDateAttribute($attrName, $importance=1){
        return new Attribute($attrName, "Date", "", "", 0, 0, $importance, 0);
    }
}
?>
