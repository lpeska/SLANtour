<?php


class PlatebniDokladViewPdf implements IPlatebniDokladModelPdfObserver
{

    /**
     * @var PlatebniDokladModel
     */
    private $model;

    /**
     * @param $model PlatebniDokladModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerPdfObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelPdfChanged()
    {
        $html = $this->pdfView();
        $this->model->generatePdf($html);
    }

    private function pdfView()
    {
        //note ObjednavkaDisplayer je napul model a napul view, ale model resi interne
        $objednavkaDisplayer = new ObjednavkaDisplayer($this->model->getObjednavka()->id_objednavka);

        $out = "<html><body>";
        $out .= $objednavkaDisplayer->getHeaderPlatebniDoklad($this->parseZakaznikInfo());
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810'>" . $objednavkaDisplayer->getZajezd() . $objednavkaDisplayer->getSluzby() . "</table>";
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810'>" . $objednavkaDisplayer->getPlatby() . "</table>";
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810'>" . $objednavkaDisplayer->getUhradit() . "</table>";
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >" . $objednavkaDisplayer->getOverview(ObjednavkaTS::TYPE_PLATEBNI_DOKLAD) . "</table>";
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810'>" . $objednavkaDisplayer->getVystavil() . "</table>";
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810'>" . $objednavkaDisplayer->getDatumObjednavky() . "</table>";
        $out .= "   <hr>";
        $out .= "   <table cellpadding='0' cellspacing='0' style='border-collapse: collapse;margin:8px;' width='810' >  " . $objednavkaDisplayer->getFooter() . "</table>";
        $out .= "</body></html>";

        return $out;
    }

    private function parseZakaznikInfo()
    {
        $objednavka = $this->model->getObjednavka();
        $klientSelected = $this->model->getKlientSelected();
        $objednavajici = $objednavka->objednavajici;
        $objednavajiciOrg = $objednavka->objednavajiciOrg;
        $ucastnici = $objednavka->ucastnici;
        $prodejce = $objednavka->prodejce;
        $out = "";

        list($prefix, $id) = explode("_", $klientSelected);
        $prefix .= "_";

        switch ($prefix) {
            case PlatebniDokladView::REQUEST_PREFIX_OBJEDNAVAJICI:
                if (is_null($objednavajiciOrg)) {
                    $out .= "<strong>$objednavajici->jmeno $objednavajici->prijmeni</strong><br/>";
                    $out .= "$objednavajici->adresa_ulice<br/>";
                    $out .= "$objednavajici->adresa_psc, $objednavajici->adresa_mesto";
                } else {
                    $adresa = $objednavajiciOrg->adresa;
                    $out .= "<strong>$objednavajiciOrg->nazev</strong><br/>";
                    $out .= "$adresa->ulice<br/>";
                    $out .= "$adresa->psc, $adresa->mesto";
                }
                break;
            case PlatebniDokladView::REQUEST_PREFIX_UCASTNIK:
                foreach ($ucastnici as $u) {
                    if ($u->id == $id) {
                        $adresa = $u->adresa;
                        $out .= "<strong>$u->jmeno $u->prijmeni</strong><br/>";
                        $out .= "$adresa->ulice<br/>";
                        $out .= "$adresa->psc, $adresa->mesto";
                    }
                }
                break;
            case PlatebniDokladView::REQUEST_PREFIX_PRODEJCE:
                $out .= "<strong>$prodejce->nazev</strong><br/>";
                $out .= "$prodejce->adresa_ulice<br/>";
                $out .= "$prodejce->adresa_psc, $prodejce->adresa_mesto";
                break;
        }

        return $out;
    }

}