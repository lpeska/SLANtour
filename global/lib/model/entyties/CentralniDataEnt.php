<?php


class CentralniDataEnt
{
    const ID_PLATBA_HLAVNI = 24;
    const ID_UCET_SK = 25;
    const ID_PLATBA_PREVODEM_MAIL = 31;
        
    const ID_PLATBA_HOTOVE = 32;
    const ID_PLATBA_PREVODEM = 36;
    const ID_PLATBA_SLOVENSKO = 35;
    const ID_PLATBA_SLOZENKOU = 33;
    const ID_PLATBA_KARTOU = 34;

    public $id;
    public $nazev;
    public $text;

    public $nazevWeb;
    public $class;

    function __construct($id, $nazev, $text)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->text = $text;
        //je treba se zbavit const [$id_objednavka] v textu platebnich metod a nahradit to necim inteligentnejsim
        $this->text = str_replace('[$id_objednavka]', "<i>bude dopln�no po dokon�en� objedn�vky</i>", $this->text);
    }

    //todo tohle je hnus- presunout nekam do view
    public function loadPaymentViewData()
    {
        switch ($this->id) {
            case self::ID_PLATBA_HOTOVE:
                $this->nazevWeb = 'Hotov�';
                $this->class = 'method-h';
                break;
            case self::ID_PLATBA_PREVODEM:
                $this->nazevWeb = 'Bankovn�m p�evodem';
                $this->class = 'method-bp';
                break;
            case self::ID_PLATBA_SLOVENSKO:
                $this->nazevWeb = 'Bankovn�m p�evodem v eurech na Slovensku';
                $this->class = 'method-bp';
                break;
            case self::ID_PLATBA_KARTOU:
                $this->nazevWeb = 'Platebn� kartou';
                $this->class = 'method-c';
                break;
            case self::ID_PLATBA_SLOZENKOU:
                $this->nazevWeb = 'Po�tovn� pouk�zkou';
                $this->class = 'method-pp';
                break;
        }
    }

    /**
     * @param $zpusobyPlatby CentralniDataEnt[]
     * @param $zpId
     * @return \CentralniDataEnt
     */
    public static function findZpusobPlatbyById($zpusobyPlatby, $zpId)
    {
        foreach ($zpusobyPlatby as $zp) {
            if ($zp->id == $zpId)
                return $zp;
        }

        return null;
    }
}