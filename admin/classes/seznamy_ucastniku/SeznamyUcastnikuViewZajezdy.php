<?php

class SeznamyUcastnikuViewZajezdy extends SeznamyUcastnikuView implements ISeznamyUcastnikuModelZajezdyObserver
{
    /**
     * @var SeznamyUcastnikuModel
     */
    private $model;

    /**
     * @param $model SeznamyUcastnikuModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerZajezdyObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelZajezdyChanged()
    {
        $this->zajezdy();
    }

    // PRIVATE METHODS *******************************************************************
    private function zajezdy()
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
        $out .= ModulView::showNavigation(new AdminModulHolder(SeznamyUcastnikuModel::$core->show_all_allowed_moduls()), SeznamyUcastnikuModel::$zamestnanec, SeznamyUcastnikuModel::$core->get_id_modul());

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

        $out .= ModulView::showHelp(SeznamyUcastnikuModel::$core->show_current_modul()["napoveda"]);;

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->zajezdyFilter();
        $out .= $this->zajezdyList();

        return $out;
    }

    private function zajezdyFilter()
    {
        $filterValues = $this->model->getSerialFilter();
        $checkedSkrytProsle = $filterValues->getZajezdSkrytProsle() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";

        $out = "";

        $out .= "<form id='form-filter-zajezdy' method='post' action='?page=zajezdy&action=filter'>";
        $out .= "       <table class='filtr'>";
        $out .= "           <tr class='align-center'>";
        $out .= "               <td>skrýt prošlé: <input type='checkbox' name='filter-skryt-prosle' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedSkrytProsle /></td>";
        $out .= "               <td>novìjší než: ";
        $out .= "                   <input class='calendar-ymd' name='filter-novejsi-nez' value='" . CommonUtils::czechDate($filterValues->getZajezdNovejsiNez()) . "' />";
        $out .= "                   <a class='del-input' href=''><img width='10' src='./img/delete-cross.png'></a>";
        $out .= "               </td>";
        $out .= "               <td><input type='submit' value='Zmìnit filtrování' /><input type='reset' value='Pùvodní hodnoty' /></td>";
        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "   </form>";

        return $out;
    }

    private function zajezdyList()
    {
        $filterValues = $this->model->getSerialFilter();
        $zajezdySelected = $filterValues->getZajezdIdsSelected();
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder->getSerialList();

        $out = "";

        if (!is_null($serialy)) {
            $out .= "<h3>Seznam zájezdù vybraných seriálù</h3>";
            $out .= "<form method='post' action='seznamy_ucastniku.php?page=ucastnici' id='form-zajezdy'>";
            $out .= "   <table class='list'>";

            foreach ($serialy as $serial) {
                $zajezdy = $serial->zajezdy;
                if (!is_null($zajezdy)) {
                    $out .= "   <tr>";
                    $out .= "       <th colspan='5'>Seriál: $serial->nazev</th>";
                    $out .= "   </tr>";
                    $out .= "   <tr>";
                    $out .= "       <th><input type='checkbox' class='check-all' value='$serial->id' /></th><th>id</th><th>od</th><th>do</th><th>název</th>";
                    $out .= "   </tr>";

                    foreach ($zajezdy as $zajezd) {
                        $out .= "   <tr class='selectable'>";
                        $out .= "       <td class='align-center'><input type='checkbox' class='$serial->id' name='cb-zajezdy[]' value='$zajezd->id' " . (@in_array($zajezd->id, $zajezdySelected) ? "checked='checked'" : "") . "></th>";
                        $out .= "       <td class='align-center'>$zajezd->id</td>";
                        $out .= "       <td class='align-center'>" . SeznamyUcastnikuUtils::czechDate($zajezd->terminOd) . "</td>";
                        $out .= "       <td class='align-center'>" . SeznamyUcastnikuUtils::czechDate($zajezd->terminDo) . "</td>";
                        $out .= "       <td>$zajezd->nazevUbytovani</td>";
                        $out .= "   </tr>";
                    }
                }
            }

            $out .= "   </table>";
            $out .= "   <input type='hidden' name='cb-serialy' value='" . $filterValues->getSerialIdsAsString() . "' />";
            $out .= "   <input type='hidden' name='filter-skryt-prosle' value='" . $filterValues->getZajezdSkrytProsle() . "' />";
            $out .= "   <input type='hidden' name='filter-novejsi-nez' value='" . $filterValues->getZajezdNovejsiNez() . "' />";
            $out .= "</form>";
        }

        return $out;
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a href='seznamy_ucastniku.php?page=serialy'><< seznam seriálù</a>";
        $out .= "   <a href='' id='btn-vyber-ucastniku'>pokraèovat</a>";
        $out .= "</div>";

        return $out;
    }
}
