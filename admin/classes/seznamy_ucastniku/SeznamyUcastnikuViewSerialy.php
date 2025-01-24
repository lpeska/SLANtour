<?php

class SeznamyUcastnikuViewSerialy extends SeznamyUcastnikuView implements ISeznamyUcastnikuModelSerialyObserver
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
        $this->model->registerSerialyObserver($this);
    }

    // PUBLIC METHODS ********************************************************************

    public function modelSerialyChanged()
    {
        $this->serialy();
    }

    // PRIVATE METHODS *******************************************************************

    private function serialy()
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

        $out .= ModulView::showHelp(SeznamyUcastnikuModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->filter();
        $out .= $this->serialyList();
        $out .= $this->paging();

        return $out;
    }

    private function filter()
    {
        $filterValues = $this->model->getSerialFilter();
        $selectedZemeId = $filterValues->getSerialZeme();
        $zeme = $this->model->getZeme();

        //vypisu si do promenne seznam idecek z filtru
        $filterIds = "";
        if (!is_null($filterValues->getSerialIds())) {
            $filterIds = "";
            foreach ($filterValues->getSerialIds() as $serialId) {
                $filterIds .= "$serialId, ";
            }
            $filterIds = substr($filterIds, 0, strlen($filterIds) - 2);
        }

        $out = "";

        $out .= "   <form id='form-filter-serialy' method='post' action='seznamy_ucastniku.php?page=serialy&action=filter'>";
        $out .= "       <table class='filtr'>";
        $out .= "           <tr>";
        $out .= "               <td>";
        $out .= "                   id: <input type='text' name='filter_id' value='$filterIds'/>";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   název seriálu: <input id='filter-nazev' type='text' name='filter_nazev' value='" . $filterValues->getSerialNazev() . "'/>";
        $out .= "                   <a class='del-input' href=''>";
        $out .= "                       <img width='10' src='./img/delete-cross.png' />";
        $out .= "                   </a>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   zemì: <select name='filter_zeme'>";
        $out .= "                       <option></option>";

        foreach ($zeme as $z)
            $out .= "                   <option value='$z->id' " . ($selectedZemeId == $z->id ? "selected='selected'" : "") . ">$z->nazev</option>";

        $out .= "                   </select>";
        $out .= "               </td>";
        $out .= "               <td><input type='submit' value='Zmìnit filtrování' /><input type='reset' value='Pùvodní hodnoty' /></td>";
        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "   </form>";

        return $out;
    }

    private function serialyList()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $filterValues = $this->model->getSerialFilter();
        $serialySelected = $filterValues->getSerialIdsSelected();
        $serialy = $serialyHolder->getSerialList();

        $out = "";

        if (!is_null($serialy)) {
            $out .= "<h3>Seznam seriálù</h3>";
            $out .= "<form method='post' action='seznamy_ucastniku.php?page=zajezdy' id='form-serialy'>";
            $out .= "   <table class='list'>";
            $out .= "       <tr><th><input type='checkbox' class='check-all'/></th><th>id</th><th>název</th><th>objekty</th><th>typ</th></tr>";

            foreach ($serialy as $serial) {
                $objekty = $serial->objekty;

                $out .= "   <tr class='selectable'>";
                $out .= "       <td class='align-center'><input type='checkbox' name='cb-serialy[]' value='$serial->id' " . (@in_array($serial->id, $serialySelected) ? "checked='checked'" : "") . "></td>";
                $out .= "       <td class='align-center'><a id='select-single-serial-$serial->id' class='select-single-serial' href=''>$serial->id</a></td>";
                $out .= "       <td>$serial->nazev</td>";
                $out .= "       <td>";

                if (!is_null($objekty)) {
                    $objOut = "";
                    foreach ($objekty as $objekt) {
                        $objOut .= "<a target='_blank' href='/admin/objekty.php?id_objektu=$objekt->id&typ=objekty&pozadavek=edit'>$objekt->nazev_objektu</a>, ";
                    }
                    $out .= substr($objOut, 0, count((array)$objOut) - 3);
                }

                $out .= "       </td>";
                $out .= "       <td>$serial->typ</td>";
                $out .= "   </tr>";
            }

            $out .= "   </table>";
            $out .= "</form>";
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

        $out .= "<div class='submenu'><a href='' id='btn-vyber-zajezdu'>pokraèovat</a></div>";

        return $out;
    }
}
