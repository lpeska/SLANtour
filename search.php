<?php
$url = "https://test.slantour.cz/vyhledat-zajezd.php";
$where = $_GET["place"];
$when = $_GET["dates"];

if ($where || $when) {
    $url = $url . "?";
    if ($where) {
        $url = $url . "txt=" . $where;
    }
    if ($when) {
        $url = $url . "&dates=" . $when;
    }
}

echo "where: " . $where;
echo "</br>";
echo "when: " . $when;
echo "</br>";
echo "url: " . $url;

header("Location: ". $url);
exit();


