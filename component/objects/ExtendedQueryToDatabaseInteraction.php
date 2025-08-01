<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExtendedQueryReceiver
 *
 * @author peska
 */
class ExtendedQueryToDatabaseInteraction implements QueryToDatabaseInteraction {
    //put your code here
    private $query_handeler;
    private $query;
    private $query_response;
    private $total_importance;
    private $addedConditions;

/**
 * function returns response for the given query class to the specified queryHandler (call his setQueryResponse method)
 * (simplified listener design pattern)
 * @param <type> $query - Query class
 * @param <type> $queryHandeler - class implements QueryHandler interface
 */
    public function getQuery($query, $queryHandeler) {
        $this->query_handeler = $queryHandeler;
        $this->query = $query;
        $this->query_response = $this->executeQuery();
        $this->sendQueryResponse();
    }

    /**
     * set class type QueryResponse to $this->query_handeler after it processed the query
     * @param <QueryResponse> $qResponse answer for the previous query
     */
    private function sendQueryResponse() {
        $this->query_handeler->setQueryResponse($this->query_response);
    }

//creates sql code part for selecting objects for attributeType = int
    private function compareInt($attribute) {
        $minFrom = $attribute->getExpectedValueFrom() - $attribute->getToleranceFrom();
        $topFrom = $attribute->getExpectedValueFrom();
        $topTo = $attribute->getExpectedValueTo();
        $maxTo = $attribute->getExpectedValueTo() + $attribute->getToleranceTo();
        $name = $attribute->getAttributeName();

        $relevance .= "(";
        if($topFrom - $minFrom >0) {
            $relevance .=  "(".$name.">=".$minFrom.")*(".$name."<".$topFrom.")*((".$name."-".$minFrom.") / (".$topFrom."-".$minFrom."))+  ";
        }
        $relevance .=  "(".$name.">=".$topFrom.")*(".$name."<=".$topTo.")*1 +";
        if($maxTo - $topTo >0 ) {
            $relevance .=  "(".$name.">".$topTo.")*(".$name."<=".$maxTo.")*((".$maxTo."-".$name.") / (".$maxTo."-".$topTo."))";
        }else {
            $relevance .=  " 0 ";
        }
        $relevance .=  ")*".$attribute->getImportance()." + ";
        $this->total_importance += $attribute->getImportance();
        if($attribute->getExcludeUnsufficient()) {
            $this->addedConditions .=
                " and (".$attribute->getAttributeName()." >= ".($attribute->getExpectedValueFrom()-$attribute->getToleranceFrom())."
                                        and ".$attribute->getAttributeName()." <= ".($attribute->getExpectedValueTo()+$attribute->getToleranceTo()).")";
        } 
        return $relevance;
    }

//creates sql code part for selecting objects for attributeType = date
    private function compareDate($attribute) {
        $toleranceFrom = $attribute->getToleranceFrom();
        $topFrom = $attribute->getExpectedValueFrom();
        $topTo = $attribute->getExpectedValueTo();
        $toleranceTo = $attribute->getToleranceTo();
        $name = $attribute->getAttributeName();

        $relevance .= "(";
        if($toleranceFrom >0) {
            $relevance .=  "(".$name.">=".$topFrom." - INTERVAL ".$toleranceFrom." DAY)
                                *(".$name."<".$topFrom.")*(( DATEDIFF( ".$name.", ".$topFrom." - INTERVAL ".$toleranceFrom." DAY)   ) / ( DATEDIFF( ".$topFrom.", ".$topFrom." - INTERVAL ".$toleranceFrom." DAY)   ))+  ";
        }
        $relevance .=  "(".$name.">=".$topFrom.")*(".$name."<=".$topTo.")*1 +";
        if($toleranceTo >0 ) {
            $relevance .=  "(".$name.">".$topTo.")
                            *(".$name."<=".$topTo." + INTERVAL ".$toleranceTo." DAY)*( (DATEDIFF(".$topTo." + INTERVAL ".$toleranceTo." DAY, ".$name.")) / (DATEDIFF(".$topTo." + INTERVAL ".$toleranceTo." DAY, ".$topTo."))";
        }else {
            $relevance .=  " 0 ";
        }
        $relevance .=  ")*".$attribute->getImportance()." + ";
        $this->total_importance += $attribute->getImportance();
        if($attribute->getExcludeUnsufficient()) {
            $this->addedConditions .=
                " and (".$attribute->getAttributeName()." >= ".($attribute->getExpectedValueFrom())."
                                        and ".$attribute->getAttributeName()." <= ".($attribute->getExpectedValueTo()).")";
        }
        return $relevance;
    }

//creates sql code part for selecting objects for attributeType = bool
    private function compareBool($attribute) {
        $topFrom = $attribute->getExpectedValueFrom();
        $name = $attribute->getAttributeName();
        $relevance .= "(
             (".$name."=".$topFrom.")*".$attribute->getImportance().") + ";
        $this->total_importance += $attribute->getImportance();
        if($attribute->getExcludeUnsufficient()) {
            $this->addedConditions .=
                " and (".$attribute->getAttributeName()." = ".$attribute->getExpectedValueFrom().")";
        }
        return $relevance;
    }

//creates sql code part for selecting objects for attributeType = String
    private function compareString($attribute) {
        $topFrom = $attribute->getExpectedValueFrom();
        $name = $attribute->getAttributeName();
        $relevance .= "(
             (".$name." like \"".$topFrom."\")*".$attribute->getImportance().") + ";
        $this->total_importance += $attribute->getImportance();
        if($attribute->getExcludeUnsufficient()) {
            $this->addedConditions .=
                " and (".$attribute->getAttributeName()." LIKE \"".$attribute->getExpectedValueFrom()."\")";
        }
        return $relevance;
    }

//creates sql code part for selecting objects for attributeType = StringFulltext
    private function compareStringFulltext($attribute) {
        if(isset(Config::$mysqlMatchDenominator) and Config::$mysqlMatchDenominator>0){
            $constantDenominator = Config::$mysqlMatchDenominator;
        }else{
            $constantDenominator = 1;
        }
        $topFrom = $attribute->getExpectedValueFrom();
        $name = $attribute->getAttributeName();
        $relevance .= "(
             ( match(".$name.") against(\"".$topFrom."\") )*".$attribute->getImportance()."/".$constantDenominator.") + ";
        $this->total_importance += $attribute->getImportance();
        if($attribute->getExcludeUnsufficient()) {
            $this->addedConditions .=
                " and (".$attribute->getAttributeName()." LIKE \"".$attribute->getExpectedValueFrom()."\")";
        }
        return $relevance;
    }

//creates sql code part for selecting objects for attributeType = String multival
    private function compareStringMultival($attribute) {
        $topFrom = $attribute->getExpectedValueFrom();
        $name = $attribute->getAttributeName();

        $relevance .= "(";
        foreach($topFrom as $value) {
            $relevance .="(".$name." like \"".$value."\") or";
        }
        $relevance .= " 0) *".$attribute->getImportance().") + ";
        $this->total_importance += $attribute->getImportance();
        if($attribute->getExcludeUnsufficient()) {
            $this->addedConditions .= "and (0 ";
            foreach($topFrom as $value) {
                $this->addedConditions .= " or ".$attribute->getAttributeName()." LIKE ".$value."";
            }
            $this->addedConditions .= ")";
        }
    }


    /**
     * Sends query to the database, returns its queryResponse
     * @return queryResponse class type
     */
    function executeQuery() {
         $relevance = "";
         $attrArray = $this->query->getAttributeArray();
         if(sizeof($attrArray)!=0){
            //some attributes to process
            $relevance = ", (";
            foreach($attrArray as $attribute){
                $type = $attribute->getAttributeType();

                switch ($type) {
                    case "int":
                        $relevance.= $this->compareInt($attribute);
                        break;

                    case "Date":
                        $relevance.= $this->compareDate($attribute);
                        break;

                    case "bool":
                        $relevance.= $this->compareBool($attribute);
                        break;

                    case "String":
                        $relevance.= $this->compareString($attribute);
                        break;

                    case "StringFulltext":
                        $relevance.= $this->compareStringFulltext($attribute);
                        break;

                    case "StringMultival":
                        $relevance.= $this->compareStringMultival($attribute);
                        break;
                }
            }
            if($relevance!=""){
                $relevance .= "0 )/".$this->total_importance." as `relevance` ";
                $this->query->setSelect($this->query->getSelect().$relevance);
                $this->query->setOrderBy(" order by `relevance` desc ");
            }
            if($this->query->getWhere()!=""){
              $addedConditions  = " and (1 ".$this->addedConditions.") ";
            }else{
              $addedConditions  = " where (1 ".$this->addedConditions.") ";
            }
            $this->query->setWhere($this->query->getWhere().$this->addedConditions);

         }
         $database = ComponentDatabase::get_instance();
         //echo $this->query->getSQL();
         return $database->executeQuery( $this->query->getSQL() );
     }


}
?>
