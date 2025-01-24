<?php

class ObratyProdejcuViewPrehled extends ObratyProdejcuView implements IOPObserver {

    /**
     * @var ObratyProdejcuModel
     */
    private $model;

    /**
     * @param $model ObratyProdejcuModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerFPObserver($this);
    }

    public function prehledChanged()
    {
        echo $this->head();
        echo $this->main();
        echo $this->foot();
    }

    private function head()
    {

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(ObratyProdejcuModel::$core->show_all_allowed_moduls()), ObratyProdejcuModel::$zamestnanec, ObratyProdejcuModel::$core->get_id_modul());

        $out .= "       <div class='main-wrapper'>";
        $out .= "           <div class='main'>";

        return $out;
    }

    private function foot()
    {
        $out = "";

        $out .= "           </div>";
        $out .= "       </div>";

        $out .= ModulView::showHelp(ObratyProdejcuModel::$core->show_current_modul()["napoveda"]);

        $out .=

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->actions();
        $out .= $this->filter();
        $out .= $this->prehled();

        return $out;
    }

    private function prehled()
    {
        $organizaceList = $this->model->organizace;

        $out .= "<h3>Pøehled obratù prodejcù</h3>";

        if (!is_null($organizaceList->getOrganizace())) {

            $out .= "<form method='post' action='?page=prehled-pdf' id='form-prehled' target='_blank'>";
            $out .= "   <table class='list'>";
            $out .= "   <tr>";
	    $out .= "    <th>ID</th>";
            $out .= "    <th>organizace</th>";
            $out .= "    <th>obrat objednávek</th>";
            $out .= "    <th>poèet úèastníkù</th>";
            $out .= "    <th>uhrazeno</th>";
            $out .= "   </tr>";

            foreach ($organizaceList->getOrganizace() as $organizace) {
                $objednavky = $organizace->getObjednavkaHolder();
                if (is_null($objednavky))
                    continue;

                $mesto = $organizace->getAdresaHolder()->getAdresy()[0]->mesto;
                $out .= "<tr>";
		$out .= "    <td class='selectable'>$organizace->id</td>";
                $out .= "    <td class='selectable'>$organizace->nazev - $mesto</td>";
                $out .= "    <td class='selectable'>" . CommonUtils::formatPrice($objednavky->calcFinalniCena(), 2) . " Kè</td>";
                $out .= "    <td class='selectable'>" . $objednavky->calcUcastniciCount() . "</td>";
                $out .= "    <td class='selectable'>" . CommonUtils::formatPrice($objednavky->calcUhrazenoHotove(), 2) . " Kè</td>";
                $out .= "</tr>";
            }

            $out .= "   <tr>";
            $out .= "    <th>souhrn</th>";
            $out .= "    <th>" . CommonUtils::formatPrice($organizaceList->calcObrat()) . " Kè</th>";
            $out .= "    <th>" . CommonUtils::formatPrice($organizaceList->calcObjednavkyPocetUcastniku()) . " osob</th>";
            $out .= "    <th>" . CommonUtils::formatPrice($organizaceList->calcObjednavkyUhrazenoHotove()) . " Kè</th>";
            $out .= "   </tr>";

            $out .= "   </table>";
            $out .= "</form>";
        }

        return $out;
    }

    private function actions()
    {
        $out = "";

        $out .= "<div class='submenu'>";
        $out .= "   <a href='?page=prehled-pdf' id='btn-zoprazit-fp-pdf'>zobrazit obraty pdf</a>";
        $out .= "</div>";

        return $out;
    }

    private function filter()
    {
        $reflector = new ReflectionClass('Serial_library');
        $typyOrganizace = $reflector->getDefaultProperties()['typ_organizace'];

        $out = "";

        $out .= "   <form id='form-filter' method='post' action='?page=prehled'>";
        $out .= "       <table class='filtr'>";
        $out .= "           <tr>";
        $out .= "               <td>";
        $out .= "                   Role organizace";
        $out .= "                   <select name='role' id='' >";

        foreach ($typyOrganizace as $key => $typOrganizace) {
            $out .= "                    <option value='$key' " . ($_REQUEST['role'] == $key ? "selected='selected'" : "") . ">$typOrganizace</option>";
        }

        $out .= "                   </select>";
        $out .= "               </td>";
        $out .= "               <td class=''>";
        $out .= "                   <input type='checkbox' name='includeZero' id='includeZero' value='1' " . ($_REQUEST['includeZero'] == 1 ? "checked='checked'" : "") . " /><label for='includeZero'>Zahrnout nulový obrat</label>";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   <input type='radio' name='dateType' class='' value='objednavka' " . (isset($_REQUEST['dateType']) ? $_REQUEST['dateType'] == 'objednavka' ? "checked='checked'" : "" : "checked='checked'") . " /> Datum objednání<br>";
        $out .= "                   <input type='radio' name='dateType' class='' value='odjezd' " . (isset($_REQUEST['dateType']) ? $_REQUEST['dateType'] == 'odjezd' ? "checked='checked'" : "" : "") . " /> Datum odjezdu";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   od: <input name='dateOd' class='calendar-ymd' value='" . $_REQUEST['dateOd'] . "' /><br>";
        $out .= "                   do: <input name='dateDo' class='calendar-ymd' value='" . $_REQUEST['dateDo'] . "' />";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   <input type='radio' name='sortBy' value='abc' " . (isset($_REQUEST['sortBy']) ? $_REQUEST['sortBy'] == 'abc' ? "checked='checked'" : "" : "checked='checked'") . " /> Tøídit abecednì<br>";
        $out .= "                   <input type='radio' name='sortBy' value='obrat' " . (isset($_REQUEST['sortBy']) ? $_REQUEST['sortBy'] == 'obrat' ? "checked='checked'" : "" : "") . " /> Tøídit dle obratu";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   <input type='submit' value='Zmìnit filtrování'><input type='reset' value='Pùvodní hodnoty'>";
        $out .= "               </td>";

        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "   </form>";

        return $out;
    }

    public function prehledPdfChanged()
    {
        // TODO: Implement prehledPdfChanged() method.
    }
}