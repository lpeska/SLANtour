<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface ObjectSimilarity {
    //put your code here
    /**
     * returns $noOfUsers the most similar object to the one with $objectID
     * similarity method depends on implementation
     * @param <type> $objectID id of the selected object
     * @param <type> $noOfObjects number of similar objects, we search for
     * @param <type> $objectList list of allowed objects
     * @return array( objectID => similarity: [0-1]) )
     */
    public function getSimilarObjects($objectID, $noOfObjects, $objectList="");

    /**
     * returns $noOfObjects the most similar  objects starts from $from rated object
     * i.e. $from = 5, $noOfObjects = 10 returns array from fifth to fifteenth most similar object
     * rating method depends on implementation
     * @param <type> $from start index of the result
     * @param <type> $noOfObjects number of objects, we search for
     * @param <type> $objectList list of allowed objects
     * @return array( objectID => rating) )
     */
    public function getSimilarObjectsFrom($from,$objectID, $noOfObjects, $objectList="");

}
?>
