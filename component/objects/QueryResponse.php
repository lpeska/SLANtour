<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QueryResponse
 * Crate for response on the Query
 * contains state of the response (OK or Fail), message from database and result set
 * @author peska
 */
class QueryResponse {
    //put your code here
    private $QueryState;
    private $DtbMessage;
    private $ResponseList;

    private $responseListPosition;
/**
 *
 * @param <type> $qState state of the query (1=OK, 0=Fail)
 * @param <type> $dtbMessage error message from the database
 * @param <type> $list array or resource of the response objects (rows in the DB)
 */
    public function  __construct($qState, $dtbMessage, $list) {
        $this->QueryState = $qState;
        $this->DtbMessage = $dtbMessage;
        $this->ResponseList = $list;
        $this->responseListPosition = 0;
    }

    /**
     * getter for $QueryState
     * @return int query state (0 = error, sth else = OK)
     */
    public function getQueryState(){
        return $this->QueryState;
    }

    /**
     * getter for $DtbMessage
     * @return String $DtbMessage - error, warningo or other message from DTB
     */
    public function getDtbMessage(){
        return $this->DtbMessage;
    }

    /**
     * getter for $ResponseList
     * @return array of returned rows from dtb
     */
    public function getResponseList(){
        return $this->ResponseList;
    }
    
    /**
     * @return next row from the ResponseList
     */
    public function getNextRow(){
        if(is_array($this->ResponseList)){
            if( isset( $this->ResponseList[$this->responseListPosition] ) ){
                $this->responseListPosition++;
                return $this->ResponseList[($this->responseListPosition-1)];
            }else{
                return false;
            }

        }else{
            $db = ComponentDatabase::get_instance();
            return $db->getNextRow($this->ResponseList);
        }
    }
}
?>
