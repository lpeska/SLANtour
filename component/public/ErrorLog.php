<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ErrorLog
 * singleton class for catching errors inside the component
 * @author peska
 */
class ErrorLog {
     private $errorList;
     static private $instance;
    //put your code here
/**
 * returns instance of the class (Singleton Design Pattern)
 * @return <type>
 */
    public static function get_instance() {
   	if( !isset(self::$instance) ) {
            self::$instance = new ErrorLog();
    	}
	return self::$instance ;
    }
    private function __construct() {
        $this->errorList = array();
    }

    /**
     * adds error message into the list
     * @param <type> $errorMessage message of the error
     */
    public function logError($errorMessage,$class){
        $this->errorList[]= new Error_component($errorMessage,$class,Date("Y-m-d H:I:s"));
    }
    /**
     * getter for $this->errorList
     */
    public function getErrors(){
        return $this->errorList;
    }

    /**
     *getter for Error_component->errorMessage
     * @return <type> array of error messages
     */
    public function getErrorMessages(){
        $resultSet = array();
        foreach ($this->errorList as $error) {
            $resultSet[] = $error->getErrorMessage();
        }
        return $resultSet;
    }

    /**
     * method for informing about component status
     *returns true if no error occures, false otherwise
     * @return <type> bool; the state
     */
    public function componentState(){
        if( is_array($this->errorList) and sizeof($this->errorList)>=1 ){
           return false;
        }else{
            return true;
        }
    }
}
?>
