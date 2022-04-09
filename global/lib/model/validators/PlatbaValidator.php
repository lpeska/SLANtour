<?php


class PlatbaValidator {

    const UCASTNIK_ADD_TYP_DOKLADU_EMPTY = 0;
    const UCASTNIK_ADD_CASTKA_EMPTY = 1;

    public static function isAddPlatbaValid($typDokladu, $castka)
    {
        $invalidResponses = null;

        if(ValuesValidator::isEmpty($typDokladu))
            $invalidResponses[] = self::UCASTNIK_ADD_TYP_DOKLADU_EMPTY;

        if(ValuesValidator::isEmpty($castka))
            $invalidResponses[] = self::UCASTNIK_ADD_CASTKA_EMPTY;

        return new ValidatorResponse($invalidResponses);
    }
}