<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Exceptions
 *
 * @author lpeska
 */
class Exceptions {
     private $warnings_array;
     private $errors_array;
     static private $instance;
    //put your code here

    public static function get_instance() {
   	if( !isset(self::$instance) ) {
            self::$instance = new Database();
    	}
	return self::$instance ;
    }
    public function __construct() {
        $this->connect();
    }

    public function addWarning($wText){

    }

    public function addError($eText){

    }

    public function getWarnings() {
        return $this->warnings_array;
    }
    
    public function getErrors() {
        return $this->errors_array;
    }
}
?>
