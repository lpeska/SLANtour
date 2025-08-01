<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface EventHandler {
    //put your code here
/**
 *  creates event handeler for specified event
 *  @param <type> $event - the event
 */
    function __construct($event);
    
 /**
   * saves event into the database (or somewhere else - depends on the implementation)
 */
    function saveEvent();

}
?>
