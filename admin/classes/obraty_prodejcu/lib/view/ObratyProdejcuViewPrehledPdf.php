<?php

class ObratyProdejcuViewPrehledPdf extends ObratyProdejcuView implements IOPObserver {

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

    public function prehledPdfChanged()
    {
        $html = "";

        $html .= $this->header();
        $html .= $this->prehled();

        $this->model->generatePdf($html);
    }

    private function header()
    {
        $out = "";

        $out .= "<table cellpadding='0' cellspacing='8'  width='810'>";
        $out .= "   <tr>";
        $out .= "       <th colspan='3' style='padding-left:0;padding-right:0;'>";
        $out .= "           <h1>Obraty prodejcù</h1>";
        $out .= "       </th>";
        $out .= "   </tr>";
        $out .= "</table>";

        return $out;
    }

    private function prehled()
    {
        $reflector = new ReflectionClass('Serial_library');
        $typyOrganizace = $reflector->getDefaultProperties()['typ_organizace'];
        $organizaceList = $this->model->organizace;

        $out = "<table cellpadding='0' cellspacing='8'  width='810'>";
        $out .= "   <tr>";
        $out .= "       <td style='padding-left:0;padding-right:0;'>";
        $out .= "           role organizace: " . $typyOrganizace[$_REQUEST['role']];
        $out .= "       </td>";
        $out .= "       <td style='padding-left:0;padding-right:0;'>";
        $out .= "           zahrnout nulový obrat: " . ($_REQUEST['includeZero'] == 1 ? 'ano' : 'ne');
        $out .= "       </td>";
        $out .= "       <td style='padding-left:0;padding-right:0;'>";
        $out .= "           datum " . ($_REQUEST['dateType'] == 'objednavka' ? 'objednání' : 'odjezdu') . ": " . $_REQUEST['dateOd'] . " - " . $_REQUEST['dateDo'];
        $out .= "       </td>";
        $out .= "       <td style='padding-left:0;padding-right:0;'>";
        $out .= "           tøídit " . ($_REQUEST['sortBy'] == 'abc' ? 'abecednì' : 'dle obratu');
        $out .= "       </td>";
        $out .= "   </tr>";
        $out .= "</table>";

        if (!is_null($organizaceList->getOrganizace())) {

            $out .= "<form method='post' action='?page=prehled-pdf' id='form-prehled'>";
            $out .= "   <table class='list' style='border-collapse: collapse;border: 2px solid black; margin:8px;width: 100%;'>";
            $out .= "   <tr>";
	    $out .= "    <th class='b-br-1-solid-black p-a-10'>ID</th>";
            $out .= "    <th class='b-br-1-solid-black p-a-10'>organizace</th>";
            $out .= "    <th class='b-br-1-solid-black p-a-10'>obrat objednávek</th>";
            $out .= "    <th class='b-br-1-solid-black p-a-10'>poèet úèastníkù</th>";
            $out .= "    <th class='b-b-1-solid-black p-a-10'>uhrazeno</th>";
            $out .= "   </tr>";

            foreach ($organizaceList->getOrganizace() as $organizace) {
                $objednavky = $organizace->getObjednavkaHolder();
                if (is_null($objednavky))
                    continue;

                $out .= "<tr>";
		$out .= "    <td class='b-a-1-solid-black p-lr-10 p-tb-5'>$organizace->id</td>";
                $out .= "    <td class='b-a-1-solid-black p-lr-10 p-tb-5'>$organizace->nazev</td>";
                $out .= "    <td class='b-a-1-solid-black p-lr-10 p-tb-5'>" . CommonUtils::formatPrice($objednavky->calcFinalniCena(), 2) . " Kè</td>";
                $out .= "    <td class='b-a-1-solid-black p-lr-10 p-tb-5'>" . $objednavky->calcUcastniciCount() . "</td>";
                $out .= "    <td class='b-a-1-solid-black p-lr-10 p-tb-5'>" . CommonUtils::formatPrice($objednavky->calcUhrazenoHotove(), 2) . " Kè</td>";
                $out .= "</tr>";
            }

            $out .= "   <tr>";
            $out .= "    <td class='strong b-tr-1-solid-black p-a-10'>souhrn</td>";
            $out .= "    <td class='strong b-tr-1-solid-black p-a-10'>" . CommonUtils::formatPrice($organizaceList->calcObrat()) . " Kè</td>";
            $out .= "    <td class='strong b-tr-1-solid-black p-a-10'>" . CommonUtils::formatPrice($organizaceList->calcObjednavkyPocetUcastniku()) . " osob</td>";
            $out .= "    <td class='strong b-t-1-solid-black p-a-10'>" . CommonUtils::formatPrice($organizaceList->calcObjednavkyUhrazenoHotove()) . " Kè</td>";
            $out .= "   </tr>";

            $out .= "   </table>";
            $out .= "</form>";
        }

        return $out;
    }

    public function prehledChanged()
    {
        // TODO: Implement prehledPdfChanged() method.
    }
}