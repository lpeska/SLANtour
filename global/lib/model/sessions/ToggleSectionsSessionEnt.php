<?php

/**
 * Class ToogleSectionsSessionEnt Predstavuje stav otevrenych sekci. Pokud je sekce pritomna ve stavu, znamenato, ze je otevrena, pokud neni, tak je zavrena.
 */
class ToggleSectionsSessionEnt {

    const SECTION_OBJEDNAVKA_EDIT_BLOCK_UCASTNICI = 'block-ucastnici';
    const SECTION_OBJEDNAVKA_EDIT_BLOCK_NEOBJEDNANE_SLUZBY = 'block-neobjednane-sluzby';
    const SECTION_OBJEDNAVKA_EDIT_BLOCK_NEOBJEDNANE_SLEVY = 'block-neobjednane-slevy';
    const SECTION_OBJEDNAVKA_EDIT_BLOCK_FAKTURY_PLATBY = 'block-faktury-platby';
    const SECTION_OBJEDNAVKA_EDIT_BLOCK_POZNAMKY_TS = 'block-poznamky-ts';

    private $state;

    public function __construct() {
    }

    public static function pullFromSession($sessionName)
    {
        $session = unserialize($_SESSION[$sessionName]);
        if (!$session)
            $session = new ToggleSectionsSessionEnt();

        return $session;
    }

    public static function clearFromSession($sessionName)
    {
        $_SESSION[$sessionName] = null;
    }

    /**
     * @return string[] pole konstatnt self::SECTION_
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param $sectionId string jedna z konstatnt self::SECTION_
     */
    public function push($sectionId) {
        if(isset($sectionId) && $sectionId != "")
            $this->state[] = $sectionId;
    }

    /**
     * @param $sectionIds[] string jedna z konstatnt self::SECTION_
     */
    public function pushMultiple($sectionIds) {
        if(!is_null($sectionIds)) {
            foreach ($sectionIds as $sectionId) {
                $this->push($sectionId);
            }
        }
    }

    /**
     * @param $sectionId string jedna z konstatnt self::SECTION_
     */
    public function remove($sectionId) {
        if(isset($sectionId) && $sectionId != "") {
            //note asynchroni pozadavky casto neprijdou ve spravnem poradi, takze se obcas duplikuji stavy
            while(($key = array_search($sectionId, $this->state)) !== false) {
                unset($this->state[$key]);
            }
        }
    }

    /**
     * @param $sectionIds[] string jedna z konstatnt self::SECTION_
     */
    public function removeMultiple($sectionIds) {
        if(!is_null($sectionIds)) {
            foreach ($sectionIds as $sectionId) {
                $this->remove($sectionId);
            }
        }
    }

    /**
     * @param $sectionId string jedna z konstatnt self::SECTION_
     * @return bool
     */
    public function contains($sectionId) {
        return in_array($sectionId, $this->state);
    }

    public function pushToSession($sessionName)
    {
        $_SESSION[$sessionName] = serialize($this);
    }
}