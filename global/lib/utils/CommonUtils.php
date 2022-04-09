<?php


class CommonUtils
{

    public static function czechDate($date, $useYear = 1)
    {
        if (is_null($date) || $date == "" || $date == "0000-00-00")
            return "";
        if($useYear){
            return date("j.n. Y", strtotime($date));
        }else{
            return date("j.n.", strtotime($date));
        }
        
    }

    /**
     * @param $datetime
     * @param string $precision one of values ["seconds" | "minutes" | "hours"]
     * @return bool|string
     */
    public static function czechDatetime($datetime, $precision = "minutes")
    {
        if (is_null($datetime) || $datetime == "" || $datetime == "0000-00-00 00:00:00")
            return "";
        switch ($precision) {
            case"seconds":
                $timeFormat = "G:i:s";
                break;
            default:
            case"minutes":
                $timeFormat = "G:i";
                break;
            case"hours":
                $timeFormat = "G";
                break;
        }
        return date("j.n. Y $timeFormat", strtotime($datetime));
    }

    public static function czechToEnglishDate($date)
    {
        if (is_null($date) || $date == "" || $date == "00.00. 0000" || $date == "00.00.0000")
            return "";
        
        $date = str_replace(" ", "", $date);
        $date_array = explode(".", $date);

        //add leading 0
        $date_array[0] = strlen($date_array[0]) == 1 ? "0" . $date_array[0] : $date_array[0];
        $date_array[1] = strlen($date_array[1]) == 1 ? "0" . $date_array[1] : $date_array[1];

        $vystup = $date_array[2] . "-" . $date_array[1] . "-" . $date_array[0];
        return $vystup;
    }
    
    
    public static function engDate($date)
    {
        if (is_null($date) || $date == "")
            return "0000-00-00";
        return date("Y-m-d", strtotime(str_replace(" ", "", $date)));
    }

    /**
     * Z pole vybere jen hodnoty s klicem odpovidajicim retezci $needle a ulozi je do pole na id odpovidajici nalezenemu ciselnemu suffixu
     * @param $heystack
     * @param $needle
     * @return array
     */
    public static function filterArrayUseNumSuffix($heystack, $needle)
    {
        $fetchedArr = array();

        if(is_array($heystack)) {
            foreach ($heystack as $key => $row) {
                if (preg_match('/' . $needle . '(\d+)/', $key, $m)) {
                    $fetchedArr[$m[1]] = $row;
                }
            }
        }

        return $fetchedArr;
    }

    /**
     * Z pole objektu vybere jen objekty s danym atributem zacinajicim na retezec $needle
     * @param $heystack
     * @param $needle
     * @param $attribute
     * @return array
     */
    public static function filterArrayOfObjects($heystack, $needle, $attribute)
    {
        $fetchedArr = null;

        foreach ($heystack as $object) {
            if (preg_match('/' . $needle . '.*/', $object->$attribute)) {
                $fetchedArr[] = $object;
            }
        }

        return $fetchedArr;
    }

    /**
     * Z pole objektu vytahne pouze hodnoty daneho atributu a vrati je jako pole
     * @param $heystack
     * @param null $idGetter
     * @param null $idAttribute
     * @return mixed[]|null
     */
    public static function parseArrayGetObjectAttrValue($heystack, $idGetter = null, $idAttribute = null)
    {
        $fetchedArr = null;

        foreach ($heystack as $object) {
            $fetchedArr[] = is_null($idGetter) ? $object->$idAttribute : $object->$idGetter();
        }

        return $fetchedArr;
    }

    /**
     * Prohleda pole objektu, porovna atribut $idKey kazdeho objektu a pokud se rovna $value, vrati ho
     * @param $array [] pole, ktere se ma prohledat
     * @param $idGetter string nazev gettru, ktery vraci klic, ktery ma slouzit k porovnani
     * @param $idAttribute string nazev atributu, ktery ma slouzit k porovnani
     * @param $value mixed hledana hodnota atributu
     * @return stdClass
     */
    public static function searchArrayOfObjects($array, $value, $idGetter = null, $idAttribute = null)
    {
        $found = null;

        if (!is_array($array))
            return $found;

        foreach ($array as $object) {
            $objectValue = is_null($idGetter) ? $object->$idAttribute : $object->$idGetter();
            if ($objectValue == $value) {
                $found = $object;
                break;
            }
        }

        return $found;
    }

    /**
     * Prohleda pole objektu, porovna atribut $idKey kazdeho objektu a pokud se rovna $value, vrati ho prida ho do vysledneho pole, ktere nakonec vrati
     * @param $array [] pole, ktere se ma prohledat
     * @param $idGetter string nazev gettru, ktery vraci klic, ktery ma slouzit k porovnani
     * @param $idAttribute string nazev atributu, ktery ma slouzit k porovnani
     * @param $value mixed hledana hodnota atributu
     * @return stdClass[]
     */
    public static function searchArrayOfObjectsReturnArray($array, $value, $idGetter = null, $idAttribute = null)
    {
        $found = null;

        if (!is_array($array))
            return $found;

        foreach ($array as $object) {
            $objectValue = is_null($idGetter) ? $object->$idAttribute : $object->$idGetter();
            if ($objectValue == $value) {
                $found[] = $object;
            }
        }

        return $found;
    }

    public static function redirect($url, $statusCode = 303)
    {
        header('Location: ' . $url, true, $statusCode);
        die();
    }

    public static function formatPrice($number, $decimalPlaces = 0, $tSeparator = " ")
    {
        return number_format($number, $decimalPlaces, ',', $tSeparator);
    }

    /**
     * @param $query SQLQuery
     */
    public static function printQuery($query)
    {
        $final = "";
        $j = 0;
        for ($i = 0; $i < strlen($query->sql); $i++) {
            if ($query->sql[$i] === '?')
                $final .= "'" . $query->params[$j++] . "'";
            else
                $final .= $query->sql[$i];
        }
        echo "<pre>$final</pre>";
    }

    /**
     * Spocita pocet poli v $_POST dle daneho prefixu
     * @param $prefix
     * @return int
     */
    public static function cntMultipleFilledPostInputs($prefix)
    {
        $cnt = 0;

        foreach ($_POST as $k => $v) {
            if (strpos($k, $prefix) === 0 && $v > 0) {
                $cnt++;
            }
        }

        return $cnt;
    }

    /**
     * K aktualni URI prida nebo zmeni (je-li jiz nastaven) sadu parametru a hodnot, ktere jsou za sebou vypsany v poli [nazev, hodnota, nazev, hodnota]
     * Pozor, nepredvidatelne vysledky, pokud je v URI pouzita kotva.
     * USAGE: /lib/view/templates/pagination.tpl
     * @param string[] $pairs
     * @return string upravena uri
     */
    public static function addUri($pairs)
    {
        $uri = $_SERVER["REQUEST_URI"];
        for ($j = 0; $j < count((array)$pairs) - 1; $j += 2) {
            $name = $pairs[$j];
            $value = $pairs[$j + 1];

            $pos = strpos($uri, "?");
            if ($pos !== false) {
                $request = substr($uri, $pos + 1);
                $vars = explode("&", $request);
                foreach ((array)$vars as $var) {
                    $foo = explode("=", $var);
                    for ($i = 0; $i < count((array)$foo); $i += 2) {
                        $result[$foo[$i]] = $foo[$i + 1];
                    }
                }
                $uri = substr($uri, 0, $pos);

                if (is_array($result)) {
                    $uri .= "?";
                    foreach ((array)$result as $k => $v)
                        if ($k != $name)
                            $uri .= "$k=$v&";
                    $uri .= "$name=$value";
                }
            } else {
                $uri .= "?$name=$value";
            }
        }

        return $uri;
    }

    public static function refractorDateTime($dateTimeDump)
    {
        $dateTimeDump = preg_replace("/-/", "/", $dateTimeDump, 2);
        $dateTimeDump = preg_replace("/-/", " ", $dateTimeDump, 1);
        $dateTimeDump = preg_replace("/-/", ":", $dateTimeDump, 2);
        return $dateTimeDump;
    }
}