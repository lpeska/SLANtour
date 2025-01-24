<?php
//component start
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();

session_start();
require_once "./core/load_core.inc.php";


require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/varovna_zprava.inc.php";

//kvuli statusu platby
require_once "./payments/SlantourPaymentsSimpleDatabase.php";

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


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

<!--  Version: Multiflex-3 Update-2 / Layout-1             -->
<!--  Date:    November 29, 2006                           -->
<!--  Author:  G. Wolfgang                                 -->
<!--  License: Fully open source without restrictions.     -->
<!--           Please keep footer credits with a link to   -->
<!--           G. Wolfgang (www.1-2-3-4.info). Thank you!  -->
<head>
    <title>
        SLAN tour | Děkujeme za objednávku
    </title>
    <meta http-equiv="cache-control" content="no-cache"/>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="Keywords" content="dovolená, poznávací, zájezdy, lázně, lyžování, 2016"/>
    <meta name="Description" content="SLAN tour: Poznávací zájezdy 2016 (Francie, Německo, Evropa, Mexiko, Indie, Rakousko, Itálie), Dovolená u moře (Chorvatsko, Francie, Španělsko, Mexiko - Cancun, Bali, SAE),
 Lyzování ve Francii, lázně v Čechách, Moravě, Slovensku a Maďarsku, pobyty na horách i u vody, LOH 2016, Premier League, Formule 1, tenis - vstupenky a zájezdy."/>

    <meta name="Robots" content="index, follow"/>

    <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_setup.css"/>
    <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_text.css"/>
    <link rel="shortcut icon" href="favicon.ico"/>
    <!--[if lt IE 9]>
    <script type="text/javascript" src="/js/html5.js"></script>
    <![endif]-->
    <link type="text/css" href="/jqueryui/css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet"/>


    <script language="JavaScript" type="text/javascript" src="/js/hide_show_div.js"></script>
    <script type="text/javascript" src="/jqueryui/js/jquery-1.7.1.min.js"></script>
    <script type="text/javascript" src="/jqueryui/js/jquery-ui-1.8.18.custom.min.js"></script>
    <script type="text/javascript">
        $(function () {
            var availableTags =
                <?php
                      include './autocomplete.php';
                            ?>
                ;

            // Accordion
            $("#keyword").autocomplete({
                source: availableTags,
                minLength: 3,
                select: function (event, ui) {
                    //set timer to send query
                }
            });

            $('#termin_od').datepicker({
                inline: true,
                dateFormat: 'dd.mm.yy',
                dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pa', 'So'],
                monthNames: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
                yearRange: 'c-12:c+2',
                firstDay: 1
            });
            $('#termin_do').datepicker({
                inline: true,
                dateFormat: 'dd.mm.yy',
                dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pa', 'So'],
                monthNames: ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
                yearRange: 'c-12:c+2',
                firstDay: 1


            });
        });
    </script>
    <?php
    //component start
    if ($_SESSION["ordered_serial"] > 0) {
        ComponentCore::loadJavaScripts($_SESSION["ordered_serial"],$_SESSION["ordered_serial"].".".$_SESSION["ordered_zajezd"].".....","zobrazit");
        
    } else {
        ComponentCore::loadJavaScripts(0,$_SESSION["ordered_serial"].".".$_SESSION["ordered_zajezd"].".....","zobrazit");
    }
    ?>
</head>

<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]>
<style type="text/css"> body {
    word-wrap: break-word;
}</style><![endif]-->

<body onload="objectOrder(1);purchase();">
<!-- Main Page Container -->
<div class="page-container">

<!-- For alternative headers START PASTE here -->

<!-- A. HEADER -->
<div class="header">

<!-- A.1 HEADER TOP -->
<div class="header-top">
    <? include_once "./includes/nadpis.inc.php"; ?>


    <!-- A.3 HEADER BOTTOM -->
    <div class="header-bottom">

        <!-- Navigation Level 2 (Drop-down menus) -->
        <div class="nav2">
            <? include_once "./includes/menu.inc.php"; ?>
        </div>
    </div>

    <!-- A.4 HEADER BREADCRUMBS -->

    <!-- Breadcrumbs -->
    <div class="header-breadcrumbs">
        <? include_once "./includes/navigace.inc.php"; ?>

    </div>
</div>

<!-- For alternative headers END PASTE here -->

<!-- B. MAIN -->
<div class="main">

    <!-- B.1 MAIN CONTENT -->
    <div class="main-content">

        <!-- Pagetitle --><?php        
        $refId = @mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$_GET["refID"]);
        $payId = @mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$_GET["payID"]);
        
        $sql = "SELECT * FROM platba_agmo WHERE ref_id = $refId and transaction_id = \"".$payId."\";";
        $result = @mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        $platba = @mysqli_fetch_object($result);
        $class = "vyhledavani";
        
        $centralni_data = array();
        $sql = "SELECT * FROM centralni_data WHERE 1";
        $result = @mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($row = mysqli_fetch_array($result)) {
            $centralni_data[$row["nazev"]] = $row["text"];
        }
        $hlaska_alternativni_platby = $centralni_data["platebni_spojeni:hlavni"];
        $hlaska_alternativni_platby = str_replace('[$id_objednavka]', $_SESSION["id_objednavky"], $hlaska_alternativni_platby) ;
        
        //text pro potvrzení objednávky
        if($_GET["typ"] == "dotaz"){
            $class="vyhledavani";
            $nadpis = "Dotaz byl úspěšně odeslán";
            $dekujeme1 = "Děkujeme za Váš dotaz";
            $dekujeme2 = "Děkujeme za  Váš dotaz k zájezdu.";
            $potvrzeni = "Na Vámi zadaný e-mail by mělo během několika minut dorazit potvrzení s kopií dotazu. Pokud by se tak nestalo, kontaktujte nás prosím na níže uvedeném e-mailu.<br/><br/>";
            
        }else{
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
            $class_platba="vyhledavani";
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
            $potvrzeni_platba = "Platbu za objednávku můžete provést některým z následujících způsobů:<br/>
                $hlaska_alternativni_platby
                Informace o možnostech platby obdržíte také v potvrzovacím e-mailu.  ";
         }

        ?>
        <h1 class="pagetitle"><?php echo $nadpis; ?></h1>

        <div class="column2-unit-left">
            <?php
            /*login z agentur*/
            $core = Core::get_instance();
            $id_registrace = $core->get_id_modul_from_typ("registrace");

            if ($id_registrace !== false) {
                $uzivatel = User::get_instance();
//	print_r($uzivatel);
                //pokud je uzivatel prihlasen, vypiseme uzivatelske menu, jinak formular pro prihlaseni
                if ($uzivatel->get_correct_login()) {
                    echo $uzivatel->show_klient_menu();
                } else {
                    //echo $uzivatel->show_login_form();
                }
            }
            ?>

            <div class="zeme">
                <h3>KATALOG ZÁJEZDŮ A POBYTŮ</h3>


                <table width="298">
                    <tr>
                        <?php
                        $menu = new Menu_katalog("dotaz_typy", "", "", "");
                        echo $menu->show_typy_pobytu();
                        ?>
                </table>


                <table width="298">
                    <tr>
                        <td colspan="4"><b>Nejžádanější země</b></td>
                    </tr>
                    <tr>
                        <?php
                        $menu2 = new Menu_katalog("dotaz_top_zeme", "", "", "");
                        echo $menu2->show_top_zeme();
                        ?>
                </table>
                <table width="298">
                    <tr>
                        <td colspan="4"><b>Nejzajímavější sportovní akce</b></td>
                    </tr>
                    <tr>
                        <?php
                        $menu3 = new Menu_katalog("dotaz_top_sporty", "", "", "");
                        echo $menu3->show_top_sporty();
                        ?>
                </table>

            </div>

            <div class="kontakt" style="margin-top:10px;">
                <h3>KONTAKTNÍ INFORMACE</h3>
                <a href="https://www.slantour.cz"><img style="border:none;" src="/img/slantour_logo.gif" class="fright" alt="Logo CK SLAN tour"/></a>

                <p>Web slantour.cz provozuje <b>SLAN tour s.r.o.</b></p>

                <p style="padding: 0 2px 0 10px; margin-top:0; color: #191970;">
                    tel.: (+ 420) 312522704<br/>
                    mob.: (+ 420) 604255018<br/>
                    e-mail: info@slantour.cz<br/>
                    web: <a href="https://www.slantour.cz">www.slantour.cz</a><br/></p>
                <a href="/o-nas.html" title="Všechny kontakty - CK SLAN tour">kompletní kontakty</a>
            </div>

            <div style="margin-top:10px;">
                <a href="http://www.goparking.cz/rezervace/krok1/?promo=SLAN790" title="Nabídka výhodného a bezpečného parkování pro naše klienty přímo u letiště Praha - Ruzyně"><img
                        style="border: 1px solid black;" src="https://www.slantour.cz/pix/go210x75.jpg" alt="Parkování na letišti" width="300"></a>
            </div>


            <div class="akce" style="margin-top:10px;">
                <h3>POJIŠTĚNÍ</h3>
                Cestovní kancelář SLAN tour s.r.o. je pojištěna dle zákona <I>159/1999 Sb.</I> pro případ insolvence CK u <b>pojišťovny UNIQA a. s.</b><br/><br/>
                CK SLAN tour je členem odborného profesního sdružení <b>Albatros</b>
            </div>

        </div>
        <div class="column2-unit-right">           
            <div class="<?php echo $class; ?>" style="padding: 0px 5px 5px 5px;margin-bottom:10px;">
                <h3><?php echo $nadpis; ?></h3>
                <p><strong><?php echo $dekujeme2; ?></strong><br/>
                    <?php echo $potvrzeni; ?>
                    <?php echo $varovani; ?>
                    V případě nejasností, či dalších požadavků nás můžete kontaktovat na:</p>
                <ul style="margin-left:30px;margin-top:10px;">
                    <li><b>SLAN tour, s.r.o.</b></li>
                    <li>Wilsonova 597, Slaný</li>
                    <li>tel. 312522704</li>
                    <li>mob. 604255018</li>
                    <li>email: info@slantour.cz</li>
                    <li><a HREF="https://www.slantour.cz" title="CK SLAN tour">www.slantour.cz</a></li>
                </ul>
            </div>
          <?php if($_GET["typ"] != "dotaz"){ ?>  
            <div class="<?php echo $class_platba; ?>" style="padding: 0px 5px 5px 5px;">
                <h3><?php echo $nadpis_platba; ?></h3>

                <p><strong><?php echo $dekujeme2_platba; ?></strong><br/>
                    <?php echo $potvrzeni_platba; ?>
                </ul>
            </div>
            <?php } ?>  
        </div>


        <hr class="clear-contentunit"/>
        <div class="column1-unit">
            <h3>DOPORUČUJEME</h3>
            <?php
            $doporucujeme = new Serial_list("", "", "", "", "", "", "", "", "", "random", 12, "recomended");
            $k = 0;
            echo "<table><tr>";
            while ($doporucujeme->get_next_radek_recommended()) {
                echo "<td class=\"round\">";
                echo $doporucujeme->show_list_item("doporucujeme_list");
                $k++;
                if ($k % 6 == 0) {
                    echo "<tr>";
                }
            }
            echo "</table>";
            ?>

        </div>

        <hr class="clear-contentunit"/>

    </div>
</div>


<!-- C. FOOTER AREA -->

<div class="footer">
    <? include_once "./includes/pata.inc.php"; ?>
</div>

</body>
</html>
