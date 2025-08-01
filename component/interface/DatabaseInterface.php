<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Singleton interface of the class connecting into the database
 * @author peska
 */
interface DatabaseInterface {

    /**
     * singleton method for creating first instance or returning existing one
     */
    static function get_instance();


    /**
     * connecting into the database
     */
    function connect();

    /**
     * disconnecting into the database
     */
    function disconnect();

    /**
     * execute given query
     * @param <type> $query - sql (or other - depends on database) query
     */
    function executeQuery($query);
    /**
     * return autoincrement ID from the last query
     */
    function getInsertedId();
}
?>
