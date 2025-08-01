<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validation
 *
 * @author peska
 */
class Validation {
    /**
     * checks/changes if the sql is quoted correctly (mysqli_magic_Quotes etc.)
     * @param <type> $sql sql code to be checked
     * @return <type> updated sql code
     */
    public static function checkSqlInput($sql){

        return $sql;
    }

    /*Complex types/classes validation*/
    public static function validateSelectSql($sql){

        return 1;
    }

    public static function validateAttribute($attribute){

        return 1;
    }

    public static function validateAttributeArray($attributeArray){

        return 1;
    }

    /*Basic types validation*/
    public static function validateInteger($var){

        return 1;
    }
    public static function validateNumber($var){

        return 1;
    }
    public static function validateString($var){

        return 1;
    }
    public static function validateBool($var){

        return 1;
    }
}
?>
