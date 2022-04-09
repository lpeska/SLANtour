<?php

class SlevaValidator {

    const SLEVA_ADD_NAZEV_EMPTY = 0;
    const SLEVA_ADD_VYSE_EMPTY = 1;
    const SLEVA_ADD_TYP_EMPTY = 2;

    public static function isAddSlevaValid($nazev, $vyse, $typ)
    {
        $invalidResponses = null;

        if(ValuesValidator::isEmpty($nazev))
            $invalidResponses[] = self::SLEVA_ADD_NAZEV_EMPTY;

        if(ValuesValidator::isEmpty($vyse))
            $invalidResponses[] = self::SLEVA_ADD_VYSE_EMPTY;

        if(ValuesValidator::isEmpty($typ))
            $invalidResponses[] = self::SLEVA_ADD_TYP_EMPTY;

        return new ValidatorResponse($invalidResponses);
    }

}