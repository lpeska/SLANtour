<?php

class MessagesEnt
{

    /**
     * @var MessageEnt[]
     */
    private static $errMessages;
    /**
     * @var MessageEnt[]
     */
    private static $warnMessages;

    public static function addErrMsg($id)
    {
        self::$errMessages[] = new MessageEnt($id, MessageEnt::TYPE_ERROR);
    }

    /**
     * @return bool
     */
    public static function hasErrMsgs()
    {
        return count((array)self::$errMessages) > 0 ? true : false;
    }

    /**
     * Kontroluje, zda existuje chyba s danym id
     * @param $id
     * @return bool
     */
    public static function hasErrMsg($id)
    {
        foreach ((array)self::$errMessages as $m) {
            if ($m->id == $id)
                return true;
        }

        return false;
    }

    /**
     * @return \MessageEnt[]
     */
    public static function getErrMessages()
    {
        return self::$errMessages;
    }

    public static function addWarnMsg($id)
    {
        self::$warnMessages[] = new MessageEnt($id, MessageEnt::TYPE_WARNING);
    }

    /**
     * @return bool
     */
    public static function hasWarnMsgs()
    {
        return count((array)self::$warnMessages) > 0 ? true : false;
    }

    /**
     * Kontroluje, zda existuje warning s danym id
     * @param $id
     * @return bool
     */
    public static function hasWarnMsg($id)
    {
        foreach ((array)self::$warnMessages as $m) {
            if ($m->id == $id)
                return true;
        }

        return false;
    }

    /**
     * @return \MessageEnt[]
     */
    public static function getWarnMessages()
    {
        return self::$errMessages;
    }
}

class MessageEnt
{
    const TYPE_ERROR = "err";
    const TYPE_WARNING = "warn";

    const ID_ZAJEZD_TERMIN = "zajezd-termin";
    const ID_ZAJEZD_TERMIN_OUT_OF_SEASON = "zajezd-mimo-obdobi";
    const ID_ZAJEZD_POCET_OSOB = "zajezd-pocet-osob";
    const ID_ZAJEZD_SLUZBY_LASTMINUTE = "zajezd-sluzby-lastminute";
    const ID_ZAJEZD_SLUZBY = "zajezd-sluzby";
    const ID_ZAJEZD_LASTMINUTE = "zajezd-lastminute";
    const ID_ZAJEZD_ODJEZDOVE_MISTO = "zajezd-odjezdova-mista";
    const ID_OS_UDAJE_JMENO = "os-udaje-jmeno";
    const ID_OS_UDAJE_PRIJMENI = "os-udaje-prijmeni";
    const ID_OS_UDAJE_MESTO = "os-udaje-mesto";
    const ID_OS_UDAJE_DATUM_NAR_DAY = "os-udaje-datum-nar-day";
    const ID_OS_UDAJE_DATUM_NAR_MONTH = "os-udaje-datum-nar-month";
    const ID_OS_UDAJE_DATUM_NAR_YEAR = "os-udaje-datum-nar-year";
    const ID_OS_UDAJE_EMAIL = "os-udaje-email";
    const ID_OS_UDAJE_TELEFON = "os-udaje-telefon";
    const ID_OS_UDAJE_PSC = "os-udaje-psc";
    const ID_OS_UDAJE_UC_JMENO = "os-udaje-uc-jmeno";
    const ID_OS_UDAJE_UC_PRIJMENI = "os-udaje-uc-prijmeni";
    const ID_OS_UDAJE_SOUHLAS = "os-udaje-souhlas";

    const ID_SLUZBA_VYPRODANO = "sluzba-vyprodano";

    public $id;
    public $type;

    function __construct($id, $type)
    {
        $this->id = $id;
        $this->type = $type;
    }


}