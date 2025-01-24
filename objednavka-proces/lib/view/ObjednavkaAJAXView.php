<?php

class ObjednavkaAJAXView extends ObjednavkaView implements IObjednavkaModelAJAXObserver
{

    /**
     * @var IObjednavkaModelForView
     */
    private $model;

    /**
     * @param $model IObjednavkaModelForView
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerAJAXObserver($this);
    }

    /**
     * Vrati blackdays terminy zajezdu ve formatu json [["2014-11-02", "2014-11-04"], ["2014-11-24", "2014-11-27"]]
     */
    public function modelAJAXSSChanged()
    {
        $zajezd = $this->model->getZajezd();
        $blackDays = $zajezd->getBlackDays();

        if (is_null($blackDays))
            return;

        $out = "[";
        foreach ($blackDays as $bd) {
            $out .= "[\"$bd->terminOd\", \"$bd->terminDo\"],";
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        header("Content-Type: text/html; charset=utf-8");
        echo $out;
    }

    /**
     * Vrati sluzby zajezdu ve formatu json [{"id": "1", "nazev": "abc", "castka": "123"}]
     */
    public function modelAJAXSluzbyChanged()
    {
        $zajezd = $this->model->getZajezd();
        $sluzbyNoSlevy = $zajezd->getSluzbaHolder()->getSluzbyAllNoSlevy();

        if (is_null($sluzbyNoSlevy) || count((array)$sluzbyNoSlevy) == 0) {
            echo "";
            return;
        }

        $out = "[";
        foreach ($sluzbyNoSlevy as $s) {
            $out .= "{\"id\": \"$s->id_cena\", \"nazev\": \"$s->nazev_ceny\", \"castka\": \"$s->castka\"},";
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        header("Content-Type: text/html; charset=utf-8");
        echo $out;
    }

    public function modelAJAXSlevyChanged()
    {
        $zajezd = $this->model->getZajezd();
        $slevy = $zajezd->getSluzbaHolder()->getSlevy();

        if (is_null($slevy) || count((array)$slevy) == 0) {
            echo "";
            return;
        }

        $out = "[";
        foreach ($slevy as $s) {
            $out .= "{\"id\": \"$s->id_cena\", \"nazev\": \"$s->nazev_ceny\", \"castka\": \"$s->castka\"},";
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        header("Content-Type: text/html; charset=utf-8");
        echo $out;
    }

    public function modelAJAXSlevyKlientChanged()
    {
        $slevy = $this->model->getSlevyKlient();

        if (is_null($slevy) || count((array)$slevy) == 0) {
            echo "";
            return;
        }

        $out = "[";
        foreach ($slevy as $s) {
            $out .= "{\"id\": \"$s->id_slevy\", \"nazev\": \"$s->nazev_slevy\", \"castka\": \"$s->castka\", \"mena\": \"$s->mena\", \"slevaStalyKlient\": \"$s->sleva_staly_klient\"},";
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        header("Content-Type: text/html; charset=utf-8");
        echo $out;
    }

    public function modelAJAXTerminChanged()
    {
        $zajezd = $this->model->getZajezd();

        if (is_null($zajezd)) {
            echo "";
            return;
        }

        $terminOd = CommonUtils::czechDate($zajezd->terminOd);
        $terminDo = CommonUtils::czechDate($zajezd->terminDo);
        $out = $zajezd->isDlouhodoby() ? "{\"termin\": \"null\"}" : "{\"termin\": \"$terminOd - $terminDo\"}";

        header("Content-Type: text/html; charset=utf-8");
        echo $out;
    }

    public function modelAJAXSerialChanged()
    {
        $serial = $this->model->getSerial();

        if (is_null($serial)) {
            echo "";
            return;
        }

        $out = "{\"nazev\": \"" . $serial->constructNazev() . "\"}";

        header("Content-Type: text/html; charset=utf-8");
        echo $out;
    }
}