<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AbstractQueryHandeler
 *
 * @author peska
 */
abstract class AbstractQueryHandler implements QueryHandler{
    //put your code here
    protected $queryResponse;

    /**
     * set class type QueryResponse from $this->queryReceiver after it processed the query
     * @param <QueryResponse> $qResponse answer for the previous query
     */
    public function setQueryResponse($qResponse) {
        $this->queryResponse = $qResponse;
    }
}
?>
