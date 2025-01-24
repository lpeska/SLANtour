<?php

class OrganizaceProdejceObjednavkaListView extends OrganizaceView implements IOrganizaceObserver
{

    /**
     * @var OrganizaceModel
     */
    private $model;

    /**
     * @param $model OrganizaceModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerObserver($this);
    }

    public function objednavkaListChanged()
    {
        echo $this->head();
        echo $this->main();
        echo $this->foot();
    }

    private function head()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $objednavky = $objednavkaHolder->getObjednavky();

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(OrganizaceModel::$core->show_all_allowed_moduls()), OrganizaceModel::$zamestnanec, OrganizaceModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        if (is_null($objednavky))
            $out .= "<h2 class='red'>Nenalezeny žádné záznamy vyhovující podmínkám.</h2>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(OrganizaceModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->objednavkaList();

        return $out;
    }

    private function objednavkaList()
    {
        $objednavkaHolder = $this->model->getObjednavkaHolder();
        $organizace = new OrganizaceEnt(null, null, null, null, null);
        $out = "";

        if (is_null($objednavkaHolder->getObjednavky()))
            return "";

        $out .= "<h3>Seznam objednávek agentury $organizace->nazev</h3>";
        $out .= "<table class='list'>";
        $out .= "    <tr><th>Id</th><th>Objednávající</th><th>Zájezd</th><th>Datum rezervace</th><th>Celkem</th><th>Provize</th><th>Faktura provize</th><th>Stav</th></tr>";

        foreach ($objednavkaHolder->getObjednavky() as $objednavka) {
            $objednavajici = $objednavka->objednavajici;
            $serial = $objednavka->getSerial();
            $fakturaProdejce = $objednavka->getFakturaProdejce();

            $out .= "<tr>";
            $out .= "    <td><a target='_blank' href='objednavky.php?idObjednavka=$objednavka->id'>$objednavka->id</a></td>";
            $out .= "    <td><a target='_blank' href='klienti.php?id_klient=$objednavajici->id&typ=klient&pozadavek=edit'>$objednavajici->titul $objednavajici->jmeno $objednavajici->prijmeni</a></td>";
            $out .= "    <td>" . $serial->constructNazev() . " [" . CommonUtils::czechDate($objednavka->termin_od) . " - " . CommonUtils::czechDate($objednavka->termin_do) . "]</td>";
            $out .= "    <td>" . CommonUtils::czechDate($objednavka->datum_rezervace) . "</td>";
            $out .= "    <td>" . CommonUtils::formatPrice($objednavka->calcFinalniCenaObjednavky()) . " Kè</td>";
            $out .= "    <td>" . CommonUtils::formatPrice($objednavka->suma_provize) . " Kè</td>";

            if (!is_null($fakturaProdejce))
                $out .= "    <td><a target='_blank' href='#'>$fakturaProdejce->cislo_faktury</a><a target='_blank' href='" . CommonConfig::FAKTURA_PROVIZE_PDF_FOLDER_URL . "$fakturaProdejce->pdfFilename'>pdf</a></td>";
            else
                $out .= "    <td>není</td>";

            $out .= "   <td class='stav " . ViewUtils::objednavkaStavNoToClass($objednavka->stav) . "'>" . ViewUtils::objednavkaStavNoToString($objednavka->stav) . "</td>";

            $out .= "</tr>";
        }

        $out .= "</table>";

        return $out;
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a href='organizace.php?typ=organizace_list'><< seznam organizací</a>";
        $out .= "</div>";

        return $out;
    }
}