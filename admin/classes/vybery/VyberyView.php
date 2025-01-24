<?php

class VyberyView implements IVyberyModelSerialyObserver
{
    /**
     * @var VyberyModel
     */
    private $model;

    /**
     * @param $model VyberyModel
     */
    function __construct($model)
    {
        $this->model = $model;
        $this->model->registerSerialyObserver($this);
    }

    // PUBLIC METHODS ********************************************************************
    //todo: tahle metoda by mela byt v samostatnem view, ale kvuli 1 metode jsem nove nedelal, kdyz se jich objevi vic, muze se vytvborit dalsi view
    public static function loginErr()
    {
        $out = "";
        $out .= VyberyModel::$zamestnanec->get_error_message();
        $out .= VyberyModel::$zamestnanec->show_login_form();

        echo $out;
    }

    private static function htmlHead()
    {
        $out = "";

        $out .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>";
        $out .= "<html>";
        $out .= "   <head>";
        $out .= "       <title>" . VyberyModel::$core->show_nazev_modulu() . " | Administrace systému RSCK</title>";
        $out .= "       <meta http-equiv='Content-Type' content='text/html; charset=windows-1250'/>";
        $out .= "       <meta name='copyright' content='&copy; Slantour'/>";
        $out .= "       <meta http-equiv='pragma' content='no-cache' />";
        $out .= "       <meta name='robots' content='noindex,noFOLLOW' />";
        $out .= "       <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='css/reset-min.css'>";
        $out .= "       <link rel='stylesheet' type='text/css' href='./new-menu/style.css' media='all'/>";
        $out .= "       <script type='text/javascript' src='./js/jquery-min.js'></script>";
        $out .= "       <script type='text/javascript' src='./classes/vybery/js/vybery.js'></script>";
        $out .= "       <script type='text/javascript' src='./js/common_functions.js'></script>";
        $out .= "   </head>";
        $out .= "   <body>";

        return $out;
    }

    private static function htmlFoot()
    {
        $out = "";

        $out .= "</body></html>";

        return $out;
    }

    public function modelInitFilterChanged()
    {
        $this->serialy();
    }

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
        $serialy = $serialyHolder == null ? null : $serialyHolder->getSerialList();

        $out = self::htmlHead();

        //zobrazeni hlavniho menu
        $out .= ModulView::showNavigation(new AdminModulHolder(VyberyModel::$core->show_all_allowed_moduls()), VyberyModel::$zamestnanec, VyberyModel::$core->get_id_modul());

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

        $out .= ModulView::showHelp(VyberyModel::$core->show_current_modul()["napoveda"]);

        $out .= self::htmlFoot();

        return $out;
    }

    private function main()
    {
        $out = "";

        $out .= $this->filter();
        $out .= $this->serialyLoop();
        $out .= $this->paging();
        $out .= $this->legenda();

        return $out;
    }

    private function filter()
    {
        $typySerialu = $this->model->getSerialTypList();
        $zeme = $this->model->getZeme();
        $filter = $this->model->getSerialFilter();
        $selectedTypId = $filter->getSerialTyp();
        $selectedZemeId = $filter->getSerialZeme();
        $checkedNoZajezd = $filter->getSerialNoZajezd() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedNoAktivniZajezd = $filter->getSerialNoAktivniZajezd() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedAktivniZajezd = $filter->getSerialAktivniZajezd() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedObjednavka = $filter->getZajezdObjednavka() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";
        $checkedNoObjednavka = $filter->getZajezdNoObjednavka() == SerialFilter::VOLBA_ZASKRTNUTA ? "checked='checked'" : "";

        $out = "";

        $out .= "   <form id='form-filter' method='post' action='vybery_serialy_zajezdy.php?page=filter-serialy'>";
        $out .= "       <table class='filtr'>";
        $out .= "           <tr>";
        $out .= "               <td class='align-center'>";
        $out .= "                   typ: <select name='f_serial_typ'>";
        $out .= "                       <option></option>";

        foreach ($typySerialu as $t)
            $out .= "                   <option value='$t->id' " . ($selectedTypId == $t->id ? "selected='selected'" : "") . ">$t->nazev</option>";

        $out .= "                   </select>";
        $out .= "               </td>";

        $out .= "               <td class='align-center'>";
        $out .= "                   zemì: <select name='f_serial_zeme'>";
        $out .= "                       <option></option>";

        foreach ($zeme as $z)
            $out .= "                   <option value='$z->id' " . ($selectedZemeId == $z->id ? "selected='selected'" : "") . ">$z->nazev</option>";

        $out .= "                   </select>";
        $out .= "               </td>";
        $out .= "               <td class='" . VyberyModelConfig::CLASS_SERIAL_NO_ZAJEZD . "'>";
        $out .= "                   0 zájezdù <input type='checkbox' name='f_serial_no_zajezd' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedNoZajezd />";
        $out .= "               </td>";
        $out .= "               <td class='" . VyberyModelConfig::CLASS_SERIAL_NO_AKTIVNI_ZAJEZD . "'>";
        $out .= "                   0 aktivních zájezdù <input type='checkbox' name='f_serial_no_aktivni_zajezd' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedNoAktivniZajezd />";
        $out .= "               </td>";
        $out .= "               <td class='" . VyberyModelConfig::CLASS_SERIAL_AKTIVNI_ZAJEZD . "'>";
        $out .= "                   1+ aktivní zájezd <input type='checkbox' name='f_serial_aktivni_zajezd' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedAktivniZajezd />";
        $out .= "               </td>";
        $out .= "               <td class='" . VyberyModelConfig::CLASS_ZAJEZD_S_OBJ . "'>";
        $out .= "                   1+ objednávka <input type='checkbox' name='f_zajezd_objednavka' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedObjednavka />";
        $out .= "               </td>";
        $out .= "               <td class='" . VyberyModelConfig::CLASS_ZAJEZD_BEZ_OBJ . "'>";
        $out .= "                   0 objednávek <input type='checkbox' name='f_zajezd_no_objednavka' value='" . SerialFilter::VOLBA_ZASKRTNUTA . "' $checkedNoObjednavka />";
        $out .= "               </td>";
        $out .= "               <td>";
        $out .= "                   <input type='hidden' name='f-hid-issend' value='foo' />";
        $out .= "                   <input type='submit' value='Zmìnit filtrování' />";
        $out .= "               </td>";
        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "   </form>";

        return $out;
    }

    private function serialyLoop()
    {
        $serialyHolder = $this->model->getSerialHolder();
        $serialy = $serialyHolder == null ? null : $serialyHolder->getSerialList();
        $filter = $this->model->getSerialFilter();
        $actualPage = $filter->getPagingPage();

        $out = "";

        $out .= "<div class=''>";
        $out .= "   <form id='form-serialy' method='post' action='vybery_serialy_zajezdy.php?page=delete-zajezd-selected&p=$actualPage'>";
        $out .= "       <h3>Seznam seriálù a jejich zájezdù</h3>";
        $out .= "       <table class='list'>";

        if (!is_null($serialy)) {
            foreach ($serialy as $serial) {
                $zajezdy = $serial->zajezdy;
                $colorClassSerial = $this->serialStatusToClass($serial);

                $out .= "   <tr class='$colorClassSerial'>";
                $out .= "       <td class='fw-bold' colspan='4'>Seriál [ <a target='_blank' href='serial.php?typ=serial&pozadavek=edit&id_serial=$serial->id'>$serial->id</a> ] $serial->nazev</td>";
                $out .= "       <td>";
                $out .= "           <a class='confirm-delete' href='vybery_serialy_zajezdy.php?page=delete-serial&id=$serial->id&p=$actualPage'>";
                $out .= "               <img width='10' src='./img/delete-cross.png' />";
                $out .= "           </a>";
                $out .= "       </td>";
                $out .= "   </tr>";

                $out .= $this->zajezdyLoop($zajezdy, $colorClassSerial, $serial);
            }
        }

        $out .= "           <tr class='edit'>";
        $out .= "               <td colspan='5' class='align-left inner-offset-1'>";
        $out .= "                   <a class='anchor-delete confirm-delete' href='' id='btn-delete-all'>smazat všechny oznaèené zájezdy</a>";
        $out .= "               </td>";
        $out .= "           </tr>";
        $out .= "       </table>";
        $out .= "   </form>";
        $out .= "</div>";

        return $out;
    }

    /**
     * @param $zajezdy tsZajezd[]
     * @param $colorClassSerial
     * @param $serial tsSerial
     * @return string
     */
    private function zajezdyLoop($zajezdy, $colorClassSerial, $serial)
    {
        $filter = $this->model->getSerialFilter();
        $actualPage = $filter->getPagingPage();
        $zajezdObjednavka = $filter->getZajezdObjednavka();
        $zajezdNoObjednavka = $filter->getZajezdNoObjednavka();

        $out = "";

        if (is_null($zajezdy))
            return $out;

        //hlavicka jen tam kde ma serial zajezdy
        $out .= "   <tr>";
        $out .= "       <th class='$colorClassSerial'></th>";
        $out .= "       <th class='$colorClassSerial'>id</th>";
        $out .= "       <th class='$colorClassSerial'>název zájezdu</th>";
        $out .= "       <th class='$colorClassSerial'>termín</th>";
        $out .= "       <th class='$colorClassSerial'></th>";
        $out .= "   </tr>";

        foreach ($zajezdy as $zajezd) {
            $colorClassZajezd = $zajezd->hasObjednavky ? VyberyModelConfig::CLASS_ZAJEZD_S_OBJ : VyberyModelConfig::CLASS_ZAJEZD_BEZ_OBJ;
            $zajezdTerminOd = VyberyUtils::czechDate($zajezd->terminOd);
            $zajezdTerminDo = VyberyUtils::czechDate($zajezd->terminDo);

            $out .= "   <tr class='$colorClassZajezd selectable hoverable-element'>";
            $out .= "       <td><input type='checkbox' name='cb-zajezdy[]' value='$zajezd->id' /></td>";
            $out .= "       <td><a href='serial.php?id_serial=$serial->id&id_zajezd=$zajezd->id&typ=zajezd&pozadavek=edit' target='_blank'>$zajezd->id</a></td>";
            $out .= "       <td>$zajezd->nazevUbytovani</td>";
            $out .= "       <td>$zajezdTerminOd - $zajezdTerminDo</td>";
            $out .= "       <td>";

            //vyber zajezdu s 1 a vice objednavkami zobrazuje i serialy se zajezdy, ktere nemaji objednavky - to je dobre, ale neni zadouci aby se dali smazat
            if (!($zajezdObjednavka && is_null($zajezdNoObjednavka) && !$zajezd->hasObjednavky))
                $out .= "           <a class='confirm-delete' href='vybery_serialy_zajezdy.php?page=delete-zajezd&id=$zajezd->id&p=$actualPage'><img width='10' src='./img/delete-cross.png' /></a>";

            $out .= "       </td>";
            $out .= "   </tr>";
        }

        return $out;
    }

    private function legenda()
    {
        $out = "";

        $out .= "<div class='list-add'>";
        $out .= "<table class='list width-auto'>";
        $out .= "   <tr><th>Legenda</th></tr>";
        $out .= "   <tr class='" . VyberyModelConfig::CLASS_SERIAL_NO_ZAJEZD . " fw-500'><td class='ioffset-5'>Seriál, který nemá žádný zájezd</td></tr>";
        $out .= "   <tr class='" . VyberyModelConfig::CLASS_SERIAL_NO_AKTIVNI_ZAJEZD . " fw-500'><td class='ioffset-5'>Seriál, který nemá žádný aktivní zájezd</td></tr>";
        $out .= "   <tr class='" . VyberyModelConfig::CLASS_SERIAL_AKTIVNI_ZAJEZD . " fw-500'><td class='ioffset-5'>Seriál s alespoò 1 aktivním zájezdem</td></tr>";
        $out .= "   <tr class='" . VyberyModelConfig::CLASS_ZAJEZD_S_OBJ . " fw-500'><td class='ioffset-5'>Zájezd s alespoò 1 objednávkou</td></tr>";
        $out .= "   <tr class='" . VyberyModelConfig::CLASS_ZAJEZD_BEZ_OBJ . " fw-500'><td class='ioffset-5'>Zájezd, který nemá žádné objednávky</td></tr>";
        $out .= "</table>";
        $out .= "</div>";

        return $out;
    }

    private function paging()
    {
        $filter = $this->model->getSerialFilter();
        if (is_null($filter))
            return "";

        $out = ModulView::showPaging2($filter);

        return $out;
    }

    /**
     * @param $serial tsSerial
     * @return mixed
     */
    private function serialStatusToClass($serial)
    {
        if (!$serial->hasZajezdy) {
            return VyberyModelConfig::CLASS_SERIAL_NO_ZAJEZD;
        } else {
            if ($serial->hasAktivniZajezd()) {
                return VyberyModelConfig::CLASS_SERIAL_AKTIVNI_ZAJEZD;
            } else {
                return VyberyModelConfig::CLASS_SERIAL_NO_AKTIVNI_ZAJEZD;
            }
        }
    }
}
