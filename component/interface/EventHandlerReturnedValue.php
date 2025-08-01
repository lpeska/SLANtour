<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface EventHandlerReturnedValue {
    //put your code here
    /**
     * @return response for the sent event (success or error message)
     */
    public function getEventResponse();
}
?>
