<?php


class UserKlientValidator {

    const UCASTNIK_ADD_JMENO_EMPTY = 0;
    const UCASTNIK_ADD_PRIJMENI_EMPTY = 1;

    public static function isUcastnikValid($jmeno, $prijmeni)
    {
        $invalidResponses = null;

        if(ValuesValidator::isEmpty($jmeno))
            $invalidResponses[] = self::UCASTNIK_ADD_JMENO_EMPTY;

        if(ValuesValidator::isEmpty($prijmeni))
            $invalidResponses[] = self::UCASTNIK_ADD_PRIJMENI_EMPTY;

        return new ValidatorResponse($invalidResponses);
    }
}