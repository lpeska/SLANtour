<?php


class SmluvniPodminkyEnt
{
    const SMLUVNI_PODMINKY_TYP_ZALOHA = "záloha";
    const SMLUVNI_PODMINKY_TYP_DOPLATEK = "doplatek";
    const SMLUVNI_PODMINKY_TYP_STORNO = "storno";

    private $id;
    private $idNazev;
    private $castka;
    private $procento;
    private $prodleva;
    private $typ;

    /**
     * SmulvniPodminkyEnt constructor.
     * @param $id
     * @param $idNazev
     * @param $castka
     * @param $procento
     * @param $prodleva
     * @param $typ
     */
    public function __construct($id, $idNazev, $castka, $procento, $prodleva, $typ)
    {
        $this->id = $id;
        $this->idNazev = $idNazev;
        $this->castka = $castka;
        $this->procento = $procento;
        $this->prodleva = $prodleva;
        $this->typ = $typ;
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
    public function getIdNazev()
    {
        return $this->idNazev;
    }

    /**
     * @return mixed
     */
    public function getCastka()
    {
        return $this->castka;
    }

    /**
     * @return mixed
     */
    public function getProcento()
    {
        return $this->procento;
    }

    /**
     * @return mixed
     */
    public function getProdleva()
    {
        return $this->prodleva;
    }

    /**
     * @return mixed
     */
    public function getTyp()
    {
        return $this->typ;
    }

    public function calcCastka($baseSum)
    {
        if($this->castka == 0)
            return round($baseSum * ($this->procento / 100));

        return $this->castka;
    }
}