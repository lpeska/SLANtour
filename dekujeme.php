<?php
require_once 'vendor/autoload.php';
require_once "./classes/loadDataTwig.inc.php"; //funkce na nacitani zajezdu, menu a classes
require_once "./payments/SlantourPaymentsSimpleDatabase.php"; //kvuli statusu platby

$tourTypes = getAllTourTypes();
$countriesMenu = getCountriesMenu();

//zpracovani hlasky (jsme za headerem pro presmerovani)	
if ($_SESSION["hlaska"] != "") {
    $hlaska_k_vypsani = $_SESSION["hlaska"];
    $_SESSION["hlaska"] = "";
} else {
    $hlaska_k_vypsani = "";
}
$varovna_zprava = null;
if ($_SESSION["varovna_zprava"] != "") {
    $varovna_zprava = unserialize($_SESSION["varovna_zprava"]);
    $_SESSION["varovna_zprava"] = "";
}


$_SESSION["termin_od"] = "";
$_SESSION["termin_do"] = "";


$refId = @mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni, $_GET["refID"]);
$payId = @mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni, $_GET["payID"]);

$sql = "SELECT * FROM platba_agmo WHERE ref_id = $refId and transaction_id = \"" . $payId . "\";";
$result = @mysqli_query($GLOBALS["core"]->database->db_spojeni, $sql);
$platba = @mysqli_fetch_object($result);
$class = "vyhledavani";

$centralni_data = array();
$sql = "SELECT * FROM centralni_data WHERE 1";
$result = @mysqli_query($GLOBALS["core"]->database->db_spojeni, $sql);
while ($row = mysqli_fetch_array($result)) {
    $centralni_data[$row["nazev"]] = $row["text"];
}
$hlaska_alternativni_platby = $centralni_data["platebni_spojeni:hlavni"];
$hlaska_alternativni_platby = str_replace('[$id_objednavka]', $_SESSION["id_objednavky"], $hlaska_alternativni_platby);
$hlaska_alternativni_platby = str_replace('<br/><br/> <br/>', '<br/><br/>', $hlaska_alternativni_platby);

$typ = $_GET["typ"];

//text pro potvrzení objednávky
if ($typ == "dotaz") {
    $class = "vyhledavani";
    $nadpis = "Dotaz byl úspěšně odeslán";
    $dekujeme1 = "Děkujeme za Váš dotaz";
    $dekujeme2 = "Děkujeme za  Váš dotaz k zájezdu.";
    $potvrzeni = "Na Vámi zadaný e-mail by mělo během několika minut dorazit potvrzení s kopií dotazu. Pokud by se tak nestalo, kontaktujte nás prosím na níže uvedeném e-mailu.<br/><br/>";
} else {
    $nadpis = "Vaše objednávka byla úspěšně dokončena";
    $dekujeme1 = "Děkujeme za Vaši objednávku";
    $dekujeme2 = "Děkujeme za objednávku zájezdu.";
    $varovani = is_null($varovna_zprava) ? "" : $varovna_zprava->vypis(VarovnaZprava::STYL_OBJ_KONECNY_VYPIS);
    $class =  is_null($varovna_zprava) ? "vyhledavani" : "pending";
    $potvrzeni = "Na Vámi zadaný e-mail by mělo během několika minut dorazit potvrzení o objednávce s dalšími instrukcemi. Pokud by se tak nestalo, kontaktujte nás prosím na níže uvedeném e-mailu.<br/>
                Vaše objednávka bude nyní zkontrolována pracovníky CK. V případě jakýchkoliv nesrovnalostí budete obratem kontaktování emailem či telefonicky.<br/><br/>";
}

if ($platba->status === SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PENDING) {
    $class_platba = "pending";
    $nadpis_platba = "Potvrzení platby - platba probíhá";
    $dekujeme2_platba = "Platba za Váš zájezd stále probíhá, o výsledku platby Vás budeme informovat e-mailem.";
    $potvrzeni_platba = "";
} else if ($platba->status === SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_CANCELED) {
    $class_platba = "cancelled";
    $nadpis_platba = "Potvrzení platby - chyba";
    $dekujeme2_platba = "Vaše platba ve výši $platba->price Kč bohužel neproběhla správně.<br/>
                    Objednávku můžete zaplatit některou z alternativních metod:<br/>";

    //Platbu můžete buď <b><a href=\"http://".$_SERVER['HTTP_HOST']."/payments/payment.php\" title=\"Opakovat platbu\">opakovat</a></b>, nebo zaplatit některou z alternativních metod:<br/>";
    $potvrzeni_platba = "
                          $hlaska_alternativni_platby
                          O případné změně způsobu platby nás prosím informujte.";
} else if ($platba->status === SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_PAID) {
    $class_platba = "vyhledavani";
    $nadpis_platba = "Potvrzení platby - platba proběhla v pořádku";
    $dekujeme2_platba = "Vaše platba kartou ve výši $platba->price Kč proběhla úspěšně.<br/>";
    $potvrzeni_platba = "";
} else if ($_GET["status"] === SlantourPaymentsSimpleDatabase::TRANSACTION_STATUS_ERROR) {
    $class_platba = "cancelled";
    $nadpis_platba = "Potvrzení platby - chyba ověření";
    $dekujeme2_platba = "Při automatickém ověřování výsledku platby se vyskytla chyba. <br/>
                    Výsledek platby ověříme manuálně a budeme vás dále kontaktoat:<br/>";
    $potvrzeni_platba = "";
} else {
    //nejednalo se o platbu kartou, zobrazim misto toho moznosti platby
    $class_platba = "pending";
    $nadpis_platba = "Platba za objednávku";
    $dekujeme2_platba = "";
    $potvrzeni_platba = "Platbu za objednávku můžete provést některým z následujících způsobů:<br/><br/>
                $hlaska_alternativni_platby
                Informace o možnostech platby obdržíte také v potvrzovacím e-mailu.  ";
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo $twig->render('dekujeme.html.twig', [
    'typesOfTours' => $tourTypes,
    'countriesMenu' => $countriesMenu,
    'breadcrumbs' => array(
        new Breadcrumb('Objednávka dokončena', '/dekujeme.php')
    ),
    'typ' => $typ,
    'nadpis' => $nadpis,
    'dekujeme1' => $dekujeme1,
    'dekujeme2' => $dekujeme2,
    'varovani' => $varovani,
    'potvrzeni' => $potvrzeni,
    'class' => $class,

    'class_platba' => $class_platba,
    'nadpis_platba' => $nadpis_platba,
    'dekujeme2_platba' => $dekujeme2_platba,
    'potvrzeni_platba' => $potvrzeni_platba
]);
