<?php
$arrWords = array("áqwe", "asdčmas", "asdďmas", "asdémas", "asděmas", "asdímas", "asdňmas", "asdómas", "asdřmas", "asdšmas", "asdťmas", "asdúmas", "asdůmas", "asdýmas", "asdžmas");
$arr = array();
foreach ($arrWords as $w) {
    $cleanW = clean_special_chars($w);
//    echo "<pre>";
//    var_dump("w: $w, cleanw: $cleanW, query: " . $_GET["query"] . " - " . strpos($cleanW, $_GET["query"]));
//    echo "</pre>";
    if (strpos($cleanW, $_GET["query"]) !== false || strpos($w, $_GET["query"]) !== false) {
        $foo = new stdClass();
        $foo->nazev = $w;
        $arr[] = $foo;
    }
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($arr);

function clean_special_chars($s, $utf8 = false)
{
    if ($utf8) $s = utf8_decode($s);

    $chars = array(
        'a' => '/à|á|â|ã|ä|å|æ/',
        'A' => '/À|Á|Â|Ã|Ä|Å|Æ/',
        'c' => '/č|ć|ĉ|ç/',
        'C' => '/Č|Ć|Ĉ|Ç/',
        'd' => '/ď/',
        'D' => '/Ď/',
        'e' => '/è|é|ě|ê|ë/',
        'E' => '/È|É|Ê|Ë|Ě/',
        'i' => '/ì|í|î|ĩ|ï/',
        'I' => '/Ì|Í|Î|Ĩ|Ï/',
        'n' => '/ñ|ň/',
        'N' => '/Ñ|Ň/',
        'o' => '/ò|ó|ô|õ|ö|ø/',
        'O' => '/Ò|Ó|Ô|Õ|Ö|Ø/',
        'r' => '/ř/',
        'R' => '/Ř/',
        's' => '/š/',
        'S' => '/Š/',
        't' => '/ť/',
        'T' => '/Ť/',
        'u' => '/ù|ú|û|ű|ü|ů/',
        'U' => '/Ù|Ú|Û|Ũ|Ü|Ů/',
        'y' => '/ý|ŷ|ÿ/',
        'Y' => '/Ý|Ŷ|Ÿ/',
        'z' => '/ž/',
        'Z' => '/Ž/'
    );

    return preg_replace($chars, array_keys($chars), $s);
}