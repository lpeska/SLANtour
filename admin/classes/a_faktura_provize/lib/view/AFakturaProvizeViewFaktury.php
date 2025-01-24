<?php

class AFakturaProvizeViewFaktury extends AFakturaProvizeView implements IAFakturaProvizeObserver
{

    /**
     * @var AFakturaProvizeModel
     */
    private $model;

    /**
     * @param $model AFakturaProvizeModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerFPObserver($this);
    }

    public function fakturyChanged()
    {
        echo $this->head();
        echo $this->main();
        echo $this->paging();
        echo $this->foot();
    }

    private function head()
    {
//        $serialyHolder = $this->model->getSerialHolder();
//        $serialy = $serialyHolder->getSerialList();

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(AFakturaProvizeModel::$core->show_all_allowed_moduls()), AFakturaProvizeModel::$zamestnanec, AFakturaProvizeModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

//        if (is_null($serialy))
//            $out .= "<h2 class='red'>Nenalezeny žádné záznamy vyhovující podmínkám.</h2>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(AFakturaProvizeModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $faktury = $this->model->getFakturaHolder()->getFaktury();
        if (is_null($faktury))
            return "";

        $out = "";

        $out .= "<h3>Seznam faktur</h3>";
        $out .= "<table class='list'>";
        $out .= "    <tr><th>è. faktury</th><th>id objednávky</th><th>vystaveno</th><th>seriál, zájezd</th><th>objednávající</th><th>agentura</th><th>provize</th><th>uhrazeno</th><th></th></tr>";

        foreach ($faktury as $faktura) {
            $objednavka = $faktura->objednavka;
            $prodejce = $objednavka->getProdejce();
            $objednavajici = $objednavka->objednavajici;
            $serial = $objednavka->getSerial();

            $out .= "<tr>";
            $out .= "   <td><a target='_blank' href='" . CommonConfig::FAKTURA_PROVIZE_PDF_FOLDER_URL . "$faktura->pdfFilename'>$faktura->cislo_faktury</a></td>";
            $out .= "   <td><a target='_blank' href='objednavky.php?idObjednavka=$objednavka->id'>$objednavka->id</a></td>";
            $out .= "   <td>" . CommonUtils::czechDate($faktura->datum_vystaveni) . "</td>";
            $out .= "   <td>" . $serial->constructNazev() . "</td>";
            $out .= "   <td><a target='_blank' href='klienti.php?id_klient=$objednavajici->id&typ=klient&pozadavek=edit'>$objednavajici->titul $objednavajici->jmeno $objednavajici->prijmeni</a></td>";
            $out .= "   <td><a target='_blank' href='organizace.php?id_organizace=$prodejce->id&typ=organizace&pozadavek=edit'>$prodejce->nazev</a></td>";
            $out .= "   <td>$faktura->celkova_castka,-</td>";
            $out .= "   <td class='" . ($faktura->zaplaceno ? 'green' : 'red') . "'>" . ($faktura->zaplaceno ? 'ANO' : 'NE') . "</td>";
            $out .= "   <td><a class='confirm-action' href='faktura_provize.php?page=faktury&action=zaplatit&id=$faktura->id'>zaplatit</a></td>";
            $out .= "</tr>";
        }


        $out .= "</table>";

        return $out;
    }

    private function paging()
    {
        $filter = $this->model->getPagingFilter();
        return ModulView::showPaging2($filter);
    }
}