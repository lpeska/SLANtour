<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *  Interface for Attributes configuration
 * @author peska
 */
interface ConfigAttributes {
    //put your code here
    /**
     *  array of attributes default values:
     * array is type of: 
     * <attribute name> -> array(<attribute name>, <attribute type>, <default tolerance>, <default importance>)
     */
    private $arrayOfAttributes;
    
    /**
     * function configurate creates array of attributes and fills it with propper values
     * static inicialization or accessing to the database...
     */
    private function configurate();

    /**
     * returns class Attribute for attribute of the given name (with its default configurated values)
     * @param <type> $attributeName name of the demanded attribute
     */
    public function getConfiguration($attributeName);
}
?>
