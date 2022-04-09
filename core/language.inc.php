<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of newPHPClass
 *
 * @author doprava
 */
class Language{
    static private $instance;
    private $language;

    static function get_instance() {
   	if( !isset(self::$instance) ) {
            self::$instance = new Language();
        }
	return self::$instance ;
   }
   private function __construct(){
       GLOBAL $language;
       $this->language = $language;
    }
   public function get_language($name) {
       return $this->language[$name];
   }
}
?>
