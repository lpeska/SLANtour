<?php


class SmluvniPodminkyNazevEnt
{

    private $id;
    private $nazev;
    private $dokumentId;

    /**
     * @var SmluvniPodminkyHolder
     */
    private $smluvniPodminkyHolder;

    /**
     * SmluvniPodminkyNazevEnt constructor.
     * @param $id
     * @param $nazev
     * @param $dokumentId
     */
    public function __construct($id, $nazev, $dokumentId)
    {
        $this->id = $id;
        $this->nazev = $nazev;
        $this->dokumentId = $dokumentId;
    }

    /**
     * @param SmluvniPodminkyEnt[] $smluvniPodminky
     */
    public function setSmluvniPodminky($smluvniPodminky)
    {
        $this->smluvniPodminkyHolder = new SmluvniPodminkyHolder($smluvniPodminky);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getNazev()
    {
        return $this->nazev;
    }

    /**
     * @return mixed
     */
    public function getDokumentId()
    {
        return $this->dokumentId;
    }

    /**
     * @return SmluvniPodminkyHolder
     */
    public function getSmluvniPodminkyHolder()
    {
        return $this->smluvniPodminkyHolder;
    }

}