<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * QueryHandler creates class type Query, then sends it to the DatabaseInterfaceClass and collect its result
 */
interface QueryHandler {

    /**
     * returns instance of QueryResponse class
     */
    function getQueryResponse();

    /**
     * sends query to selected QueryReceiver
     */
    function sendQuery();
}
?>
