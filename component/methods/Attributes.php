<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Attributes
 * This method uses extendedQueryToDatabaseInteraction class to determine similarity of objects measured on certain attributes
 * This method currently implements only ObjectSimilarity, UserSimilarity is possible to extend
 * @author peska
 */
class Attributes extends AbstractMethod  implements ObjectSimilarity {
    //put your code here
    private $sqlObject;
    private $sqlOtherObjects;
    private $attributes;

    /**
     * returns  interval from $from to $from+$noOfUsers the most similar object to the one with $objectID
     * similarity is measured on the specified attributes - in the same way as in the ExtendedQueryHandler
     * @param <type> $objectID id of the selected object
     * @param <type> $from start index of the result
     * @param <type> $noOfObjects number of similar objects, we search for
     * @param <type> $objectList list of allowed objects
     * @param <type> $attributesSelectionSQL sql code which selects all necesary attributes (the most important is "FROM" part)
     * @param <type> $attributesList array of Attribute class instances - the attributes which we want to use for measuring the objects distance
     * @return array( objectID => similarity: [0-1]) )
     */
   public function getSimilarObjectsFrom($objectID, $from, $noOfObjects,$objectList="", $attributesSelectionSQL="",  $attributesList=""){
        //get whole list
        $result = $this->getSimilarObjects($objectID, $from + $noOfObjects, $objectList, $implicitEventsList, $explicitEventsList);
        //return demanded part
        return array_slice($result, $from, $noOfObjects,TRUE);
    }


    /**
     * returns  $noOfUsers the most similar object to the one with $objectID
     * similarity is measured on the specified attributes - in the same way as in the ExtendedQueryHandler
     * @param <type> $objectID id of the selected object
     * @param <type> $noOfObjects number of similar objects, we search for
     * @param <type> $objectList list of allowed objects
     * @param <type> $attributesSelectionSQL sql code which selects all necesary attributes (the most important is "FROM" part)
     * @param <type> $attributesList array of Attribute class instances - the attributes which we want to use for measuring the objects distance
     * @return array( objectID => similarity: [0-1]) )
     */
   public function getSimilarObjects($objectID, $noOfObjects, $objectList="", $attributesSelectionSQL="",  $attributesList="") {
        if(is_array($attributesList) and sizeof($attributesList)!=0 and $attributesSelectionSQL!="") {
            $table = Config::$objectTableName;
            $objectIDName = Config::$objectIDColumnName;

            $this->sqlObject = new Query($attributesSelectionSQL);
            
                $where = $this->sqlObject->getWhere();
                if($where == ""){
                    $where = "where `".$table."`.`".$objectIDName."`=".$objectID." ";
                }else{
                    $where = $where." and `".$table."`.`".$objectIDName."`=".$objectID." ";
                }
            $select = "select ";
            $attrNo=0;
            $first = true;
            foreach ($attributesList as $attribute) {
                if($first){
                    $first = false;
                    $select .=" ".$attribute->getAttributeName()." as `attribute".$attrNo."`";
                }else{
                    $select .=", ".$attribute->getAttributeName()." as `attribute".$attrNo."`";
                }
                $attrNo++;
            }
            $this->sqlObject->setSelect($select);
            $this->sqlObject->setWhere($where);
            //echo $this->sqlObject->getSQL();
            
            $bqHandler = new BasicQueryHandler($this->sqlObject->getSQL());
            $bqHandler->sendQuery();
            $objectResponse = $bqHandler->getQueryResponse();

            if($objectResponse->getQueryState()) {
            //we have the attribute values for demanded object
                $row = $objectResponse->getNextRow();
                //print_r($row);
                $attrNo=0;
                foreach ($attributesList as $attribute) {
                    if($row["attribute".$attrNo.""]!=""){
                        $this->attributes[] = new Attribute($attribute->getAttributeName(), $attribute->getAttributeType(), $row["attribute".$attrNo.""], $row["attribute".$attrNo.""],$attribute->getToleranceFrom(), $attribute->getToleranceTo(), $attribute->getImportance(),  $attribute->getExcludeUnsufficient());
                    }
                    $attrNo++;
                }

                if(is_array($objectList) and sizeof($objectList)!=0) {
                    $objectQuery = " `".$table."`.`".$objectIDName."` in (";
                    $first = 1;
                    foreach($objectList as $obj) {
                        if($first) {
                            $first = 0;
                            $objectQuery .= "".$obj."";
                        }else {
                            $objectQuery .= ", ".$obj."";
                        }
                    }
                    $objectQuery .=")";
                }else {
                    $objectQuery ="";
                }
                $this->sqlOtherObjects = new Query($attributesSelectionSQL);
                $where = $this->sqlOtherObjects->getWhere();
                if($where == ""){
                    $where = "where ".$objectQuery." and `".$table."`.`".$objectIDName."`!=".$objectID." ";
                }else{
                    $where = $where." and ".$objectQuery." and `".$table."`.`".$objectIDName."`!=".$objectID." ";
                }
                $this->sqlOtherObjects->setSelect("select `".$table."`.`".$objectIDName."` ");
                $this->sqlOtherObjects->setWhere($where);
                $this->sqlOtherObjects->setLimit(" limit 0,".$noOfObjects." ");
                $sql = $this->sqlOtherObjects->getSQL();
                //print_r($sql);
                //execute query
                $eqHandler = new ExtendedQueryHandler($sql, $this->attributes);
                $eqHandler->sendQuery();

                $eResponse = $eqHandler->getQueryResponse();
                return $this->associateToArray($eResponse);

            }else {
                //somthing sinister happend in the database:))                
                $errLog = ErrorLog::get_instance();
                $errLog->logError("Wrong Database query - no prediction made","Attributes");
                return false;
            }

         }else{
           $errLog = ErrorLog::get_instance();
           $errLog->logError("No attributes specified or no SQL selecting attributes provided, no prediction made","Attributes");
         }
   }

//change MySQL resource - response for the query into the array (objectID=>relevance)
    private function associateToArray($eResponse){
       $objectID = Config::$objectIDColumnName;
       $queryResponseArray = array();
         while( $record = $eResponse->getNextRow() ) {
             $queryResponseArray[$record[$objectID]] = $record["relevance"];
         }
       return $queryResponseArray;
    }

}
?>
