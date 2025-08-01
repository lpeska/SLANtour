<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface QueryToDatabaseInteraction {
    //put your code here

    /**
     * receives class type Query, register its sender, executes query
     * and sends class type QueryResponse back to the sender
     * @param <type> $query the Query class from sender
     * @param <type> $querySender the sender (querySender class)
     */
    function getQuery($query, $querySender);

}
?>
