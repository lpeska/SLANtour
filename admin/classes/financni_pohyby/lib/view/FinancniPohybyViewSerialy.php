<?php

class FinancniPohybyViewSerialy extends FinancniPohybyView implements IFPObserver
{

    /** @var  FinancniPohybyModel */
    private $model;

    /**
     * @param FinancniPohybyModel $model
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerFPObserver($this);
    }

    public function prehledChanged()
    {
        // note not interested in this model change
    }

    public function prehledPdfChanged()
    {
        // note not interested in this model change
    }

    public function serialListChanged()
    {
        echo $this->head();
        echo $this->main();
        echo $this->foot();
    }

    private function head()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(FinancniPohybyModel::$core->show_all_allowed_moduls()), FinancniPohybyModel::$zamestnanec, FinancniPohybyModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        if (is_null($serialy))
            $out .= "<h2 class='red'>Nenalezeny žádné záznamy vyhovující podmínkám.</h2>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(FinancniPohybyModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->filter();
        $out .= $this->serialyLoop();
        $out .= $this->paging();

        return $out;
    }

    private function filter()
    {
        $serialFilter = $this->model->getSerialFilter();
        $selectedZemeId = $serialFilter->getSerialZeme();
        $selectedTypId = $serialFilter->getSerialTyp();
        $zeme = $this->model->getZeme();
        $serialTypList = $this->model->getSerialTypList();
        $checkedNoAktivniZajezd = $serialFilter->getSerialNoAktivniZajezd() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedAktivniZajezd = $serialFilter->getSerialAktivniZajezd() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedObjednavka = $serialFilter->getZajezdObjednavka() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedNoObjednavka = $serialFilter->getZajezdNoObjednavka() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";

        //vypisu si do promenne seznam idecek z filtru
        $filterIds = "";
        if (!is_null($serialFilter->getSerialIds())) {
            $filterIds = "";
            foreach ($serialFilter->getSerialIds() as $serialId) {
                $filterIds .= "$serialId, ";
            }
            $filterIds = substr($filterIds, 0, strlen($filterIds) - 2);
        }

        $out = "";

        $out .= "   <form id='form-filter-serialy' method='post' action='?page=serialy&action=filter'>";
        $out .= "       <table class='filtr'>";
        $out .= "           <tr>";
        $out .= "               <td>";
        $out .= "                   id seriálù: <input type='text' name='filter_id' id='filter-id' value='$filterIds'/>";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   název seriálu: <input id='filter-nazev' type='text' name='filter_nazev' value='" . $serialFilter->getSerialNazev() . "'/>";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   typ: <select name='filter_serial_typ' id='filter-serial-typ' >";
        $out .= "                       <option></option>";

        foreach ($serialTypList as $t)
            $out .= "                   <option value='$t->id' " . ($selectedTypId == $t->id ? "selected='selected'" : "") . ">$t->nazev</option>";

        $out .= "                   </select>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   zemì: <select name='filter_zeme' id='filter-zeme' >";
        $out .= "                       <option></option>";

        foreach ($zeme as $z)
            $out .= "                   <option value='$z->id' " . ($selectedZemeId == $z->id ? "selected='selected'" : "") . ">$z->nazev</option>";

        $out .= "                   </select>";
        $out .= "               </td>";
        $out .= "               <td><input type='submit' value='Zmìnit filtrování' name='btn_change_filter' /><input type='reset' value='Pùvodní hodnoty' /><input type='button' id='filter_vynulovat_fp' value='Vynulovat filtr' class=\"action-delete\" /></td>";
        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "       <table class='filtr b-display'>";
        $out .= "           <tr>";
        $out .= "               <td class='vybery-snaz'>";
        $out .= "                   <label for='f-snaz'>0 aktivních zájezdù </label><input type='checkbox' name='filter_serial_no_aktivni_zajezd' id='f-snaz' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedNoAktivniZajezd />";
        $out .= "               </td>";
        $out .= "               <td class='vybery-sak'>";
        $out .= "                   <label for='f-sak'>1+ aktivní zájezd </label><input type='checkbox' name='filter_serial_aktivni_zajezd' id='f-sak' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedAktivniZajezd />";
        $out .= "               </td>";
        $out .= "               <td class='vybery-zso'>";
        $out .= "                   <label for='f-zso'>1+ objednávka </label><input type='checkbox' name='filter_zajezd_objednavka' id='f-zso' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedObjednavka />";
        $out .= "               </td>";
        $out .= "               <td class='vybery-zbo'>";
        $out .= "                   <label for='f-zbo'>0 objednávek </label><input type='checkbox' name='filter_zajezd_no_objednavka' id='f-zbo' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedNoObjednavka />";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   Zájezd od: <input name='filter_zajezd_od' class='calendar-ymd' value='" . CommonUtils::czechDate($serialFilter->getZajezdOd()) . "' />";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   do: <input name='filter_zajezd_do' class='calendar-ymd' value='" . CommonUtils::czechDate($serialFilter->getZajezdDo()) . "' />";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   Datum objednávky od: <input name='filter_objednavka_od' class='calendar-ymd' value='" . CommonUtils::czechDate($serialFilter->getObjednavkaOd()) . "' />";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   do: <input name='filter_objednavka_do' class='calendar-ymd' value='" . CommonUtils::czechDate($serialFilter->getObjednavkaDo()) . "' />";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   Upøesnìní realizace objednávky od: <input name='filter_realizace_od' class='calendar-ymd' value='" . CommonUtils::czechDate($serialFilter->getRealizaceOd()) . "' />";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   do: <input name='filter_realizace_do' class='calendar-ymd' value='" . CommonUtils::czechDate($serialFilter->getRealizaceDo()) . "' />";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";        
        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "   </form>";

        return $out;
    }

    private function serialyLoop()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = "";

        if (!is_null($serialy)) {
            $out .= "<h3>Seznam seriálù a jejich zájezdù</h3>";
            $out .= "<form method='post' action='?page=prehled' id='form-serialy'>";
            $out .= "   <table class='list'>";
            $out .= "       <colgroup><col width='2%'><col width='25%'><col width='10%'></colgroup>";

            foreach ($serialy as $serial) {
                $objekty = $serial->objekty;
                $zajezdy = $serial->getZajezdHolder()->getZajezdy();
                $typ = $serial->typ;
                $colorClassSerial = ViewUtils::serialStatusToClass($serial);

                $out .= "   <tr class='selectable fw-bold $colorClassSerial'>";
                $out .= "       <td class='align-center'><a id='select-single-serial-$serial->id' class='select-single-serial' href='serial.php?id_serial=$serial->id&typ=serial&pozadavek=edit' target='_blank'>$serial->id</a></td>";
                $out .= "       <td>$serial->nazev</td>";
                $out .= "       <td>$typ->nazev</td>";
                $out .= "       <td>";

                $out .= $this->objektyLoop($objekty);

                $out .= "       </td>";
                $out .= "   </tr>";

                $out .= $this->zajezdyLoop($zajezdy, $serial);
            }

            $out .= "   </table>";
            $out .= "</form>";
        }

        return $out;
    }

    /**
     * @param ObjektEnt[] $objekty
     * @return string
     */
    private function objektyLoop($objekty)
    {
        $out = "";

        if (!is_null($objekty)) {
            foreach ($objekty as $objekt) {
                $out .= "<a target='_blank' href='/admin/objekty.php?id_objektu=$objekt->id&typ=objekty&pozadavek=edit'>$objekt->nazev</a>, ";
            }
            $out = substr($out, 0, -3);
        }

        return $out;
    }

    /**
     * @param ZajezdEnt[] $zajezdy
     * @param SerialEnt $serial
     * @return string
     */
    private function zajezdyLoop($zajezdy, $serial)
    {
        $serialFilter = $this->model->getSerialFilter();
        $zajezdySelected = $serialFilter->getZajezdIdsSelected();
        $out = "";

        if (!is_null($zajezdy)) {
            foreach ($zajezdy as $zajezd) {
                $colorClassZajezd = $zajezd->hasObjednavka() ? ViewConfig::CLASS_ZAJEZD_S_OBJ : ViewConfig::CLASS_ZAJEZD_BEZ_OBJ;

                $out .= "   <tr class='selectable $colorClassZajezd'>";
                $out .= "       <td class='align-center'><input type='checkbox' class='$serial->id' name='cb-zajezdy[]' value='$zajezd->id' " . (@in_array($zajezd->id, $zajezdySelected) ? "checked='checked'" : "") . "></th>";
                $out .= "       <td class='align-center'>[ <a href='serial.php?id_serial=$serial->id&id_zajezd=$zajezd->id&typ=zajezd&pozadavek=edit' target='_blank'>$zajezd->id</a> ] " . CommonUtils::czechDate($zajezd->terminOd) . " - " . CommonUtils::czechDate($zajezd->terminDo) . "</td>";
                $out .= "       <td class='align-center' colspan='2'></td>";
                $out .= "   </tr>";
            }
        }

        return $out;
    }

    private function paging()
    {
        $filter = $this->model->getSerialFilter();
        return ModulView::showPaging2($filter);
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'><a href='' id='btn-zobrazit-fp'>zobrazit finanèní pohyby</a></div>";

        return $out;
    }

}