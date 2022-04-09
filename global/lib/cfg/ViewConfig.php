<?php


class ViewConfig {

    const CLASS_ZAJEZD_S_OBJ = "vybery-zso";
    const CLASS_ZAJEZD_BEZ_OBJ = "vybery-zbo";

    const CLASS_SERIAL_NO_ZAJEZD = "vybery-snz";
    const CLASS_SERIAL_NO_AKTIVNI_ZAJEZD = "vybery-snaz";
    const CLASS_SERIAL_AKTIVNI_ZAJEZD = "vybery-sak";

    const CZECH_DATE_FORMAT = "j.n. Y";

    const MENA_KC = 'Kè';
    const MENA_KC_OS = 'Kè/osoba';
    const MENA_PERCENT = '%';

    const JSON_POPUP_DATA_TYPE_SELECTOR = 'selector';

    public static function OBJEDNAVKA_STAVY()
    {
        return [
            ObjednavkaEnt::STAV_PREDBEZNA_POPTAVKA => 'Pøedbìžná poptávka',
            ObjednavkaEnt::STAV_PNR => 'Požadavek na rezervaci',
            ObjednavkaEnt::STAV_OPCE => 'Opce',
            ObjednavkaEnt::STAV_REZERVACE => 'Rezervace',
            ObjednavkaEnt::STAV_ZALOHA => 'Záloha',
            ObjednavkaEnt::STAV_PRODANO => 'Prodáno',
            ObjednavkaEnt::STAV_ODBAVENO => 'Odbaveno',
            ObjednavkaEnt::STAV_STORNO_KLIENT => 'Storno klientem',
            ObjednavkaEnt::STAV_STORNO_CK => 'Storno CK',
	    ObjednavkaEnt::STAV_VOUCHER => 'VOUCHER'
        ];
    }

    public static function OBJEDNAVKA_STAVY_CLASSES()
    {
        return [
            ObjednavkaEnt::STAV_PREDBEZNA_POPTAVKA => 'stav-predb',
            ObjednavkaEnt::STAV_PNR => 'stav-pozad',
            ObjednavkaEnt::STAV_OPCE => 'stav-opce',
            ObjednavkaEnt::STAV_REZERVACE => 'stav-rez',
            ObjednavkaEnt::STAV_ZALOHA => 'stav-zal',
            ObjednavkaEnt::STAV_PRODANO => 'stav-prodano',
            ObjednavkaEnt::STAV_ODBAVENO => 'stav-odbaveno',
            ObjednavkaEnt::STAV_STORNO_KLIENT => 'stav-storno',
            ObjednavkaEnt::STAV_STORNO_CK => 'stav-storno',
	    ObjednavkaEnt::STAV_VOUCHER => 'stav-voucher'
        ];
    }
    
}