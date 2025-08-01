<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface UsersToObjectDataLookup {
    //put your code here
    /**
     * returns array of id_object, sized $noOfObjects: object which was the most "desired" by the users in the userArray
     * "desired" depends on implementation (and should depend on implicit/explicit events of theese users stored in DTB)
     * @param <type> $usersArray array of userIDs
     * @param <type> $noOfObject number of object, we search for
     * @param <type> $implicitEventsList array of implicit eventTypes we wish to apply
     * @param <type> $explicitEventsList array of explicit eventTypes we wish to apply
     * @return array of id_object=>score of the object
     */
    public function getBestObjectForUsers($usersArray, $noOfObjects, $objectList="");

    /**
     * returns $noOfObjects the most similar  objects starts from $from rated object
     * i.e. $from = 5, $noOfObjects = 10 returns array from fifth to fifteenth most similar object
     * rating method depends on implementation
     * @param <type> $from start index of the result
     * @param <type> $noOfObjects number of objects, we search for
     * @param <type> $objectList list of allowed objects
     * @return array( objectID => rating) )
     */
    public function getBestObjectForUsersFrom($from,$usersArray, $noOfObjects, $objectList="");

}
?>
