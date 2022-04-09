<?php


class ValuesValidator
{

    /**
     * Validuje cele cislo vetsi nez nebo rovne 0
     * @param $pocet
     * @return bool
     */
    public static function intGreaterThenEqualZero($pocet)
    {
        if (isset($pocet) && $pocet != "" && $pocet >= 0)
            return true;

        return false;
    }

    /**
     * Validuje cele cislo vetsi nez 0
     * @param $pocet
     * @return bool
     */
    public static function intGreaterThenZero($pocet)
    {
        if (isset($pocet) && $pocet != "" && $pocet > 0)
            return true;

        return false;
    }

    /**
     * Validuje neprazdny retezec
     * @param $string
     * @return bool
     */
    public static function isEmpty($string)
    {
        if(isset($string) && $string != "")
            return false;

        return true;
    }

}