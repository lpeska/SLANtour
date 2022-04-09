<?php


class ViewUtils
{
    const CESKE_SKLONOVANI_NA = -1;
    const CESKE_SKLONOVANI_0 = 0;
    const CESKE_SKLONOVANI_1 = 1;
    const CESKE_SKLONOVANI_2_4 = 2;
    const CESKE_SKLONOVANI_5_VIC = 3;

    /**
     * @param SerialEnt $serial
     * @return mixed
     */
    public static function serialStatusToClass($serial)
    {
        if (!$serial->hasZajezd()) {
            return ViewConfig::CLASS_SERIAL_NO_ZAJEZD;
        } else {
            if ($serial->hasAktivniZajezd()) {
                return ViewConfig::CLASS_SERIAL_AKTIVNI_ZAJEZD;
            } else {
                return ViewConfig::CLASS_SERIAL_NO_AKTIVNI_ZAJEZD;
            }
        }
    }

    public static function objednavkaStavNoToString($stavNo)
    {
        return ViewConfig::OBJEDNAVKA_STAVY()[$stavNo];
    }

    public static function objednavkaStavNoToClass($stavNo)
    {
        return ViewConfig::OBJEDNAVKA_STAVY_CLASSES()[$stavNo];
    }

    public static function ceskeSklonovaniPoctu($number)
    {
        if($number < 0)
            return self::CESKE_SKLONOVANI_NA;
        if($number == 0)
            return self::CESKE_SKLONOVANI_0;
        if($number == 1)
            return self::CESKE_SKLONOVANI_1;
        if($number > 1 && $number < 5)
            return self::CESKE_SKLONOVANI_2_4;
        if($number >= 5)
            return self::CESKE_SKLONOVANI_5_VIC;

        return self::CESKE_SKLONOVANI_NA;
    }

}