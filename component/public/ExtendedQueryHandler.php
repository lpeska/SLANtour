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
class ExtendedQueryHandler extends AbstractQueryHandler implements QueryHandler {
    private $sql;
    private $queryReceiver;
    private $query;
    private $attributesArray;
/**
 *
 * @param <type> $sql sql code of the query - basic conditions for the objects which they must accomplish
 * @param <type> $attributesArray array of Attribute class
 */
    public function __construct($sql, $attributesArray){
        $this->sql = $sql;
        $this->attributesArray = $attributesArray;
        $this->createQuery();
    }

/**
 * send query to the database handler
 */
    public function sendQuery() {
        $this->queryReceiver = new ExtendedQueryToDatabaseInteraction();
        $this->queryReceiver->getQuery($this->query, $this);
    }

    /**
     * creates class type Query
     * (parameters, if any is expected to be stored as variables by other methods before createQuery() is called)
     */
    private function createQuery() {
        $this->query = new Query($this->sql, $this->attributesArray);
    }

/**
 *gets response from the database handler
 * @return <type> instance of QueryResponse class
 */
    public function getQueryResponse() {
        return $this->queryResponse;
    }
}
?>
