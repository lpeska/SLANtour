<?php

class SluzbaValidator {

    const SLUZBA_ADD_NAZEV_EMPTY = 0;
    const SLUZBA_ADD_CASTKA_EMPTY = 1;
    const SLUZBA_ADD_POCET_NOT_INT_GREATER_THEN_ZERO = 2;

    /**
     * @param $nazev
     * @param $castka
     * @param $pocet
     * @return ValidatorResponse
     */
    public static function validateSluzbaAdd($nazev, $castka, $pocet)
    {
        $invalidResponses = null;

        if(ValuesValidator::isEmpty($nazev))
            $invalidResponses[] = self::SLUZBA_ADD_NAZEV_EMPTY;

        if(ValuesValidator::isEmpty($castka))
            $invalidResponses[] = self::SLUZBA_ADD_CASTKA_EMPTY;

        if(!ValuesValidator::intGreaterThenZero($pocet))
            $invalidResponses[] = self::SLUZBA_ADD_POCET_NOT_INT_GREATER_THEN_ZERO;

        return new ValidatorResponse($invalidResponses);
    }
}