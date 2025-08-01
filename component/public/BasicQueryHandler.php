<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of BasicQuerySender
 *
 * @author peska
 */
class BasicQueryHandler extends AbstractQueryHandler{
    private $sql;
    private $queryReceiver;
    private $query;
/**
 * @param <type> $sql sql code of the query - basic conditions for the objects which they must accomplish
 */
    public function __construct($sql){
        $this->sql = $sql;
        $this->createQuery();
    }
/**
 * send query to the database handler
 */
    public function sendQuery() {
        $this->queryReceiver = new BasicQueryToDatabaseInteraction();
        $this->queryReceiver->getQuery($this->query, $this);
    }

    /**
     * creates class type Query
     * (parameters, if any is expected to be stored as variables by other methods before createQuery() is called)
     */
    private function createQuery() {
        $this->query = new Query($this->sql);
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
