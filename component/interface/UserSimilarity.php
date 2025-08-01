<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface UserSimilarity {
    //put your code here
    /**
     * returns $noOfUsers the most similar users to the one with $userID
     * similarity method depends on implementation
     * @param <type> $userID id of the selected user
     * @param <type> $noOfUsers number of similar users, we search for
     * @return array( userID => similarity: [0-1]) )
     */
    public function getSimilarUsers($userID, $noOfUsers);


}
?>
