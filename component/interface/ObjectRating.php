<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface ObjectRating {
    //put your code here
    /**
     * returns $noOfObjects the best rated objects
     * rating method depends on implementation
     * @param <type> $noOfObjects number of objects, we search for
     * @param <type> $objectList list of allowed objects
     * @return array( objectID => rating) )
     */
    public function getBestObjects($noOfObjects, $objectList="");

    /**
     * returns $noOfObjects the best rated objects starts from $from rated object
     * i.e. $from = 5, $noOfObjects = 10 returns array from fifth to fifteenth best object
     * rating method depends on implementation
     * @param <type> $from start index of the result
     * @param <type> $noOfObjects number of objects, we search for
     * @param <type> $objectList list of allowed objects
     * @return array( objectID => rating) )
     */
    public function getBestObjectsFrom($from, $noOfObjects, $objectList="");

}
?>
