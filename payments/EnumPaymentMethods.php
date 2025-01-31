<?php

class EnumPaymentMethods
{
    const METHOD_BANK_ALL = 'BANK_ALL';
    const METHOD_BANK_CZ_AB = 'BANK_CZ_AB';
    const METHOD_BANK_CZ_CS = 'BANK_CZ_CS';
    const METHOD_BANK_CZ_CS_P = 'BANK_CZ_CS_P';
    const METHOD_BANK_CZ_CSOB = 'BANK_CZ_CSOB';
    const METHOD_BANK_CZ_CSOB_P = 'BANK_CZ_CSOB_P';
    const METHOD_BANK_CZ_CTB = 'BANK_CZ_CTB';
    const METHOD_BANK_CZ_EB = 'BANK_CZ_EB';
    const METHOD_BANK_CZ_FB = 'BANK_CZ_FB';
    const METHOD_BANK_CZ_FB_2 = 'BANK_CZ_FB_2';
    const METHOD_BANK_CZ_GE = 'BANK_CZ_GE';
    const METHOD_BANK_CZ_GE_2 = 'BANK_CZ_GE_2';
    const METHOD_BANK_CZ_KB = 'BANK_CZ_KB';
    const METHOD_BANK_CZ_KB_2 = 'BANK_CZ_KB_2';
    const METHOD_BANK_CZ_MB = 'BANK_CZ_MB';
    const METHOD_BANK_CZ_MB_P = 'BANK_CZ_MB_P';
    const METHOD_BANK_CZ_OTHER = 'BANK_CZ_OTHER';
    const METHOD_BANK_CZ_PS = 'BANK_CZ_PS';
    const METHOD_BANK_CZ_PS_P = 'BANK_CZ_PS_P';
    const METHOD_BANK_CZ_RB = 'BANK_CZ_RB';
    const METHOD_BANK_CZ_RB_2 = 'BANK_CZ_RB_2';
    const METHOD_BANK_CZ_UC = 'BANK_CZ_UC';
    const METHOD_BANK_CZ_VB = 'BANK_CZ_VB';
    const METHOD_BANK_CZ_VB_2 = 'BANK_CZ_VB_2';
    const METHOD_BANK_CZ_ZB = 'BANK_CZ_ZB';

    const METHOD_CARD_ALL = 'CARD_ALL'; //jedina AGMO metoda, kterou pouzivame (19.8.2014)
    const METHOD_CARD_CZ_CS = 'CARD_CZ_CS';
    const METHOD_CARD_CZ_CSOB = 'CARD_CZ_CSOB';

    const METHOD_PAYSEC_CZ = 'PAYSEC_CZ';
    const METHOD_MPAY_CZ = 'MPAY_CZ';
    const METHOD_MPAY_PL = 'MPAY_PL';
    const METHOD_SMS_CZ = 'SMS_CZ';

    public static function getAll()
    {
        return array(
            self::METHOD_BANK_ALL,
            self::METHOD_BANK_CZ_AB,
            self::METHOD_BANK_CZ_CS,
            self::METHOD_BANK_CZ_CS_P,
            self::METHOD_BANK_CZ_CSOB,
            self::METHOD_BANK_CZ_CSOB_P,
            self::METHOD_BANK_CZ_CTB,
            self::METHOD_BANK_CZ_EB,
            self::METHOD_BANK_CZ_FB,
            self::METHOD_BANK_CZ_FB_2,
            self::METHOD_BANK_CZ_GE,
            self::METHOD_BANK_CZ_GE_2,
            self::METHOD_BANK_CZ_KB,
            self::METHOD_BANK_CZ_KB_2,
            self::METHOD_BANK_CZ_MB,
            self::METHOD_BANK_CZ_MB_P,
            self::METHOD_BANK_CZ_OTHER,
            self::METHOD_BANK_CZ_PS,
            self::METHOD_BANK_CZ_PS_P,
            self::METHOD_BANK_CZ_RB,
            self::METHOD_BANK_CZ_RB_2,
            self::METHOD_BANK_CZ_UC,
            self::METHOD_BANK_CZ_VB,
            self::METHOD_BANK_CZ_VB_2,
            self::METHOD_BANK_CZ_ZB,
            self::METHOD_PAYSEC_CZ,
            self::METHOD_CARD_ALL,
            self::METHOD_CARD_CZ_CS,
            self::METHOD_CARD_CZ_CSOB,
            self::METHOD_MPAY_CZ,
            self::METHOD_MPAY_PL,
            self::METHOD_SMS_CZ
        );
    }

    public static function getBankAll()
    {
        return array(
            self::METHOD_BANK_ALL,
            self::METHOD_BANK_CZ_AB,
            self::METHOD_BANK_CZ_CS,
            self::METHOD_BANK_CZ_CS_P,
            self::METHOD_BANK_CZ_CSOB,
            self::METHOD_BANK_CZ_CSOB_P,
            self::METHOD_BANK_CZ_CTB,
            self::METHOD_BANK_CZ_EB,
            self::METHOD_BANK_CZ_FB,
            self::METHOD_BANK_CZ_FB_2,
            self::METHOD_BANK_CZ_GE,
            self::METHOD_BANK_CZ_GE_2,
            self::METHOD_BANK_CZ_KB,
            self::METHOD_BANK_CZ_KB_2,
            self::METHOD_BANK_CZ_MB,
            self::METHOD_BANK_CZ_MB_P,
            self::METHOD_BANK_CZ_OTHER,
            self::METHOD_BANK_CZ_PS,
            self::METHOD_BANK_CZ_PS_P,
            self::METHOD_BANK_CZ_RB,
            self::METHOD_BANK_CZ_RB_2,
            self::METHOD_BANK_CZ_UC,
            self::METHOD_BANK_CZ_VB,
            self::METHOD_BANK_CZ_VB_2,
            self::METHOD_BANK_CZ_ZB
        );
    }
}