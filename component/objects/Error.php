<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Error_component
 * crate for error log
 * @author peska
 */
class Error_component {
    //put your code here
    private $errorMessage;
    private $class;
    private $time;
    /**
     *
     * @param <type> $errorMessage error message
     * @param <type> $class class where the error occures
     * @param <type> $datetime date and time when the error occures
     */
    public function __construct($errorMessage,$class,$datetime){
        $this->errorMessage = $errorMessage;
        $this->class = $class;
        $this->time = $datetime;
    }
    
    /**
     * getter for $this->errorMessage
     */
    public function getErrorMessage(){
        return $this->errorMessage;
    }

     /**
     * getter for $this->class
     */
    public function getClass(){
        return $this->class;
    }

    /**
     * getter for $this->time
     */
    public function getTime(){
        return $this->time;
    }
}
?>
