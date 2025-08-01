<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author peska
 */
interface ConfigMethods {
    //put your code here
    /**
     *  array of evaluation methods presented in the system:
     * array is type of:
     * <method name> -> array(<method name>, <method type>, <default parameters array>)
     */
    private $arrayOfAttributes;

    /**
     * function configurate creates array of methods and fills it with propper values
     * static inicialization probably
     */
    private function configurate();

    /**
     * returns array for method of the given name (with its type, name and default params)
     * @param <type> $methodName name of the demanded method
     */
    public function getConfiguration($methodName);
}
?>
