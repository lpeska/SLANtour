<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * BasicQueryToDatabaseInteraction is a basic class for query send -> receiveResponse process
 * it handles only the standard SQL (no special attributes, object or user expressions)
 * @author peska
 */
class BasicQueryToDatabaseInteraction implements QueryToDatabaseInteraction{
    //put your code here
    private $query_handeler;
    private $query;
    private $query_response;

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

    /**
     * Sends query to the database, returns its response
     * @return queryResponse class
     */
    function executeQuery() {
         $database = ComponentDatabase::get_instance();
         return $database->executeQuery( $this->query->getSQL() );
     }


}
?>
