<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of utils_ts
 *
 * @author Pesi
 */
class UtilsTS {

    /*
     * note (Martin) pouzil jsem na datum ve formatu dd-mm-yyyy a hazelo mi to rok jako prvni - nechtelo se mi studovat proc to tak je, tak jsem pridal metodu czechDate(); Mozna pujde pouzit ve vsech pripadech jako tato metoda, takze by se tahle dala nahradit a smazat
    zmìna formátu data z anglickeho do ceskeho */
    //zvlada take konverzi typu datetime
    static function change_date_en_cz($vstup) {
        if ($vstup != "") {
            $date_time = explode(" ", $vstup);
            $date_array = explode("-", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
            $vystup = $date_array[2] . "." . $date_array[1] . "." . $date_array[0];
            if ($date_time[1] != "") {
                $vystup = $vystup . " " . $date_time[1];
            }
            return $vystup;
        } else {
            return "";
        }
    }
    
    static function change_date_cz_en($vstup) {
        if ($vstup != "") {
            $date_time = explode(" ", $vstup);
            $date_array = explode(".", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
            $vystup = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];
            if ($date_time[1] != "") {
                $vystup = $vystup . " " . $date_time[1];
            }
            return $vystup;
        } else {
            return "";
        }
    }    
/* zmìna textu z reprezentace v DB na smysluplný text*/

    static function text_transform($vstup) {
        switch ($vstup) {
            case "Kè": return "CZK";
                break;
            case "vystavena": return "Vystavená faktura";
                break;
            case "prijata": return "Pøijatá faktura";
                break;
            case "prevod": return "Bankovním pøevodem";
                break;
            case "hotove": return "Hotovì";
                break;
            case "vcetneDPH": return "Vèetnì DPH";
                break;
            case "snizena": return "Snížená";
                break;
            case "zakladni": return "Základní";
                break;            
            default:
                return $vstup;
                break;
        }
    }

    public static function czechDate($datumCas)
    {
        if (is_null($datumCas) || $datumCas == "" || $datumCas == "0000-00-00" || $datumCas == "0000-00-00 00:00:00")
            return "";
        return date("j.m.Y", strtotime($datumCas));
    }
    
    public static function calculate_pocet_noci($od, $do) {        
        
        $pole_od = explode("-", $od);
        $pole_do = explode("-", $do);

        $time_od = mktime(0, 0, 0, intval($pole_od[1]), intval($pole_od[2]), intval($pole_od[0]));
        $time_do = mktime(0, 0, 0, intval($pole_do[1]), intval($pole_do[2]), intval($pole_do[0]));
        $pocet_noci = (round(($time_do - $time_od) / (24 * 60 * 60)));
        if ($pocet_noci < 0) {
            $pocet_noci = 0;
        }        
        return $pocet_noci;
    }
    
}

?>
