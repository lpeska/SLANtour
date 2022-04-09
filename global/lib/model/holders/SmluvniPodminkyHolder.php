<?php


class SmluvniPodminkyHolder
{

    /** @var  SmluvniPodminkyEnt[] */
    private $smluvniPodminky;

    function __construct($smluvniPodminky)
    {
        $this->smluvniPodminky = $smluvniPodminky;
    }

    /**
     * @return SmluvniPodminkyEnt[]
     */
    public function getSmluvniPodminky()
    {
        return $this->smluvniPodminky;
    }

    /**
     * @return SmluvniPodminkyEnt[]|null
     */
    public function getZalohy()
    {
        return $this->getSmluvniPodminkyByType(SmluvniPodminkyEnt::SMLUVNI_PODMINKY_TYP_ZALOHA);
    }

    /**
     * @return SmluvniPodminkyEnt[]|null
     */
    public function getStorna()
    {
        return $this->getSmluvniPodminkyByType(SmluvniPodminkyEnt::SMLUVNI_PODMINKY_TYP_STORNO);
    }

    /**
     * @param $type
     * @return SmluvniPodminkyEnt[]|null
     */
    private function getSmluvniPodminkyByType($type)
    {
        $smluvniPodminky = null;

        if (is_array($this->smluvniPodminky)) {
            foreach ($this->smluvniPodminky as $sp) {
                if ($sp->getTyp() == $type)
                    $smluvniPodminky[] = $sp;
            }
        }

        return $smluvniPodminky;
    }

    /**
     * @param $terminOd string termin od ktereho je objednavka objenana
     * @return null|SmluvniPodminkyEnt
     */
    public function getAktualniStorno($terminOd)
    {
        /** @var SmluvniPodminkyEnt[] $storna */
        $storna = $this->getStorna();

        if (is_array($storna)) {
            foreach ($storna as $storno) {
                $hranice = date('d-m-Y', strtotime($terminOd . " - " . $storno->getProdleva() . " days"));
                $today = date('d-m-Y');

                //spravny casovy usek byl nalezen, ukonci prochazeni
                if (strtotime($today) <= strtotime($hranice)) {
                    return $storno;
                }
            }
        }

        return new SmluvniPodminkyEnt(null, null, 0, 0, null, null);
    }

}