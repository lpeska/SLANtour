<?php
function print_time($last, $header, &$collector){
    $now = microtime(TRUE);
    $diff = round(($now - $last),3)*1000 ;
    $collector[] = $header. ": " . $diff;
    return $now;

}
$time = microtime(TRUE);
$collector = array();


//component start
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();

session_start();
require_once "./core/load_core.inc.php";

$time = print_time($time, "Loading RS component", $collector);

require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
//require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/informace_zeme.inc.php"; //seznam informaci katalogu
//zpracovani hlasky (jsme za headerem pro presmerovani)	
$t2 = time();
if ($_SESSION["hlaska"] != "") {
    $hlaska_k_vypsani = $_SESSION["hlaska"];
    $_SESSION["hlaska"] = "";
} else {
    $hlaska_k_vypsani = "";
}

if ($_GET["update_filter"]) {
    $_GET["termin_od"] = $_POST["termin_od"];
    $_GET["termin_do"] = $_POST["termin_do"];
    $_GET["cena_od"] = intval($_POST["cena_od"]);
    $_GET["cena_do"] = intval($_POST["cena_do"]);
    $_GET["order_by"] = $_POST["order_by"];    
    $_GET["text"] = $_POST["text"];
     //$_GET["doprava"]=$_POST["doprava"];
     //$_GET["zeme"]=$_POST["zeme"];
     //$_GET["typ_zajezdu"]=$_POST["typ_zajezdu"];   
    $_GET["filter_doprava"]=$_POST["doprava"];
     $_GET["filter_zeme"]=$_POST["zeme"];
     $_GET["filter_typ_zajezdu"]=$_POST["typ_zajezdu"]; 
}
//print_r($_GET);
/*
if ($_GET["termin_od"] == "") {
    $_GET["termin_od"] = $_SESSION["termin_od"];
}
if ($_GET["termin_do"] == "") {
    $_GET["termin_do"] = $_SESSION["termin_do"];
}
if ($_GET["cena_od"] == "") {
    $_GET["cena_od"] = $_SESSION["cena_od"];
}
if ($_GET["cena_do"] == "") {
    $_GET["cena_do"] = $_SESSION["cena_do"];
}
if ($_GET["order_by"] == "") {
    $_GET["order_by"] = $_SESSION["order_by"];
}
if ($_GET["text"] == "") {
    $_GET["text"] = $_SESSION["text"];
}

if($_GET["filter_doprava"]==""){
   $_GET["filter_doprava"] = $_SESSION["doprava"];
}
if($_GET["filter_typ_zajezdu"]==""){
   $_GET["filter_typ_zajezdu"] = $_SESSION["typ_zajezdu"];
}
if($_GET["filter_zeme"]==""){
   $_GET["filter_zeme"] = $_SESSION["zeme"];
}
*/
if ($_GET["lev1"] != "") {
    //test na typ_serialu
    if (Serial_list::get_id_from_typ($_GET["lev1"]) > 0) {
        //prvni level je typ
        $_GET["typ"] = $_GET["lev1"];
        if ($_GET["lev2"] != "") {
            //muze to byt zeme nebo podtyp, zeme ma prednost
            if (Serial_list::get_id_from_zeme($_GET["lev2"]) > 0) {
                $_GET["zeme"] = $_GET["lev2"];

                if ($_GET["lev3"] != "") {
                    //prvni zkoumam destinaci
                    $id_dest = Serial_list::get_id_from_destinace($_GET["lev3"],$_GET["zeme"]);
                    if ($id_dest > 0) {
                        $_GET["destinace"] = $_GET["lev3"];
                        $_GET["id_destinace"] = $id_dest;

                        if ($_GET["lev4"] != "") {
                            if (Serial_list::get_id_from_typ($_GET["lev4"]) > 0) {
                                $_GET["podtyp"] = $_GET["lev4"];
                            }
                        }
                    } else if (Serial_list::get_id_from_typ($_GET["lev3"]) > 0) {
                        $_GET["podtyp"] = $_GET["lev3"];
                    }
                }
            } else if (Serial_list::get_id_from_typ($_GET["lev2"]) > 0) {
                $_GET["podtyp"] = $_GET["lev2"];
            }
        }
    } else if (Serial_list::get_id_from_zeme($_GET["lev1"]) > 0) {
        //prvni level neni typ - mela by to byt zeme
        $_GET["typ"] = "";
        $_GET["zeme"] = $_GET["lev1"];
        //potrebuju vsechny typy dane zeme

        $only_typ = Serial_list::get_name_of_only_typ($_GET["zeme"]);
        if ($only_typ !== FALSE) {
            $_GET["typ"] = $only_typ;
        }
        if ($_GET["lev2"] != "") {
            //muze to byt jedine destinace, typy nemam zadane
            $id_dest = Serial_list::get_id_from_destinace($_GET["lev2"]);
            if ($id_dest > 0) {
                $_GET["destinace"] = $_GET["lev2"];
                $_GET["id_destinace"] = $id_dest;
            }
        }
    } else {
        //nejaka chyba, vyresit...
    }
}

//echo  "destinace".$_GET["destinace"].$_GET["lev2"].$_GET["lev3"].$_GET["lev4"];


if ($_GET["typ"]) {
    $nazev_typu = Serial_list::get_name_from_typ($_GET["typ"]) . ", ";
} else {
    $nazev_typu = "";
}
$t3 = time();
$id_serial = "";
$id_ubytovani = "";
if ($_GET["typ"] == "lazenske-pobyty") {
    if (isset($_GET["ajax_id"])) {
        $id_ubytovani = $_GET["ajax_id"];
    }
    $serial_list = new Serial_list($_GET["typ"], $_GET["zeme"], $_GET["text"], "", $_GET["id_destinace"], $_GET["zajezd"], $_GET["termin_od"], $_GET["termin_do"], $_GET["str"], $_GET["order_by"], 50, "select_ubytovani_group",$id_serial,$id_ubytovani);
} else {
    if (isset($_GET["ajax_id"])) {
        $id_serial = $_GET["ajax_id"];
    }    
    $serial_list = new Serial_list($_GET["typ"], $_GET["zeme"], $_GET["text"], "", $_GET["id_destinace"], $_GET["zajezd"], $_GET["termin_od"], $_GET["termin_do"], $_GET["str"], $_GET["order_by"], 50, "select_serialy_group",$id_serial,$id_ubytovani);
}
$t4 = time();
//ajax
if (isset($_GET["ajax_id"])) {
    while ($serial_list->get_next_radek()) {
        if($_GET["ajax_typ"] == "lazenske-pobyty")
            $ajax_id = $serial_list->get_id_ubytovani();
        else
            $ajax_id = $serial_list->get_id_serial();
//        echo "$ajax_id == " . $_GET["ajax_id"] . "\n";
        if($ajax_id == $_GET["ajax_id"])
            echo $serial_list->show_list_item("katalog_list_group", "", "", "", true);
    }
    exit;
}
$t5 = time();
$pocet_zajezdu = $serial_list->get_pocet_zajezdu();

if ($_GET["typ"]) {
    $typ_serial = Serial_list::get_name_from_typ($_GET["typ"]);
    $typ_serial_web = $_GET["typ"];
}
if ($_GET["zeme"]) {
    $zeme_nazev = Serial_list::getNameForZeme($_GET["zeme"]);
    $zeme_nazev_web = $_GET["zeme"];
}
if ($_GET["id_destinace"]) {
    $destinace_id = $_GET["id_destinace"];
    $destinace_nazev = Serial_list::get_name_from_id_destinace($destinace_id);
    $destinace_nazev_web = $_GET["destinace"];
}
$_GET["pageType"] = "katalog";
$time = print_time($time, "Loading menu", $collector);
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">

    <!--  Version: Multiflex-3 Update-2 / Layout-1             -->
    <!--  Date:    November 29, 2006                           -->
    <!--  Author:  G. Wolfgang                                 -->
    <!--  License: Fully open source without restrictions.     -->
    <!--           Please keep footer credits with a link to   -->
    <!--           G. Wolfgang (www.1-2-3-4.info). Thank you!  -->

    <head>
        <title>
            <?php
            echo $serial_list->show_titulek();
            ?></title>  
        <meta http-equiv="cache-control" content="no-cache" />

        <meta http-equiv="Content-Type" content="text/html; charset=windows-1250" />
        <meta name="Keywords" content="
        <?php
        echo $serial_list->show_keyword();
        ?>
              "/>
        <meta name="Description" content="
        <?php
        echo $serial_list->show_description();
        ?>
              "/>
        <meta name="Robots" content="index, follow"/>

        <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_setup.css" />
        <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_text.css" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <!--[if lt IE 9]>
              <script type="text/javascript" src="/js/html5.js"></script>
        <![endif]-->   		
        <link type="text/css" href="/jqueryui/css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
        <script language="JavaScript" type="text/javascript" src="/js/storeTrace.js"></script>
        <script language="JavaScript" type="text/javascript" src="/js/hide_show_div.js"></script>
        <script type="text/javascript" src="/jqueryui/js/jquery-1.7.1.min.js"></script>
        <script type="text/javascript" src="/jqueryui/js/jquery-ui-1.8.18.custom.min.js"></script>
        <script>
            <?php 
            echo "var get_typ = \"" . $_GET["typ"] . "\";"; 
            echo "var get_id_destinace = \"" . $_GET["id_destinace"] . "\";"; 
            ?>
            function getKatalogAJAX(e, id, typ) {
                typ = typ == "ubytovani" ? "lazenske-pobyty" : typ;
                var url = "katalog.php?ajax_id=" + id + "&all=" + id + "&ajax_typ=" + typ + "&typ=" + get_typ + "&id_destinace=" + get_id_destinace;
                $.ajax({
                    url: url,
                    beforeSend : function(xhr) {
                        xhr.overrideMimeType('text/html; charset=windows-1250');
                    },
                    success:function(result){
                        console.log(e.parentNode);
                        console.log(result);
                        //odstran redundatni div
                     /*   result = result.replace('<div  class="licha round" style="border-bottom:1px solid black;"  itemscope itemtype="http://schema.org/Product">',"");
                        result = result.replace('<div  class="suda round" style="border-bottom:1px solid black;"  itemscope itemtype="http://schema.org/Product">',"");
                        result = result.replace('</div>',"");*/
                    //    e.parentNode.parentNode.parentNode.parentNode.parentNode.innerHTML = result;
                        e.parentNode.innerHTML = result;
                    }
                });
            }
            $(function(){
                var availableTags = 
<?php
include './autocomplete.php';
?>
        ;
                      
        // Accordion
        $( "#keyword" ).autocomplete({
            source: availableTags,
            minLength: 3,
            select: function( event, ui ) {
                //set timer to send query
            }
        });

        $('#termin_od').datepicker({
            inline: true,
            dateFormat: 'dd.mm.yy',
            dayNamesMin: ['Ne', 'Po', '�t', 'St', '�t', 'Pa', 'So'],
            monthNames:  ['Leden', '�nor', 'B�ezen', 'Duben', 'Kv�ten', '�erven', '�ervenec', 'Srpen', 'Z���', '��jen', 'Listopad', 'Prosinec'],
            yearRange:    'c-12:c+2',
            firstDay: 1
        });
        $('#termin_do').datepicker({
            inline: true,
            dateFormat: 'dd.mm.yy',
            dayNamesMin: ['Ne', 'Po', '�t', 'St', '�t', 'Pa', 'So'],
            monthNames:  ['Leden', '�nor', 'B�ezen', 'Duben', 'Kv�ten', '�erven', '�ervenec', 'Srpen', 'Z���', '��jen', 'Listopad', 'Prosinec'],
            yearRange:    'c-12:c+2',
            firstDay: 1


        });
    });                    
        </script>
        <?php
//component start
        ComponentCore::loadJavaScripts(0,"...".$_GET["id_destinace"].".".$_GET["zeme"].".".$_GET["typ"].".","katalog");
//component end
        ?>                
    </head>

    <!-- Global IE fix to avoid layout crash when single word size wider than column width -->
    <!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->

    <body <?php
        echo "onload='storeTrace(" . $_SESSION["user"] . ", " . $_SESSION["session_no"] . ", \"katalog\", \"" . $_GET["typ"] . "\", \"" . $_GET["zeme"] . "\", \"" . $_GET["id_destinace"] . "\", \"" . $_GET["termin_od"] . "\", \"" . $_GET["termin_do"] . "\", \"" . $_GET["text"] . "\", \"\", \"\", \"\", \"\", \"\");'"
        ?> 
        >
        <!-- Main Page Container -->
        <div class="page-container">

            <!-- For alternative headers START PASTE here -->

            <!-- A. HEADER -->      
            <div class="header">

                <!-- A.1 HEADER TOP -->
                <div class="header-top">
                    <?php include_once "./includes/nadpis.inc.php"; ?>


                    <!-- A.3 HEADER BOTTOM -->
                    <div class="header-bottom">

                        <!-- Navigation Level 2 (Drop-down menus) -->
                        <div class="nav2">
                            <?php include_once "./includes/menu.inc.php"; ?>         			 		   
                        </div>
                    </div>

                    <!-- A.4 HEADER BREADCRUMBS -->

                    <!-- Breadcrumbs -->
                    <div class="header-breadcrumbs">
                        <?php include_once "./includes/navigace.inc.php"; ?>

                    </div>
                </div>

                <!-- For alternative headers END PASTE here -->

                <!-- B. MAIN -->
                <div class="main">

                    <!-- B.1 MAIN CONTENT -->
                    <div class="main-content">

                        <div class="column1-unit" style="margin-top:-10px;margin-bottom:-10px;padding:0;font-size:1.1em;font-style: italic;">  
                            <?php
                            include "./includes/zpetna_navigace.inc.php";
                            $time = print_time($time, "Headers", $collector);
                            ?>  
                        </div>
                        <!-- Pagetitle 
                        <h1 class="pagetitle">L�zn� a term�ln� l�zn�</h1>-->
                        <div class="column2-unit-left" >
                            <?php
                            /* login z agentur */
                            $core = Core::get_instance();
                            $id_registrace = $core->get_id_modul_from_typ("registrace");
                            if ($id_registrace !== false) {
                                $uzivatel = User::get_instance();

                                //pokud je uzivatel prihlasen, vypiseme uzivatelske menu, jinak formular pro prihlaseni
                                if ($uzivatel->get_correct_login()) {
                                    echo $uzivatel->show_klient_menu();
                                } else {
                                    //echo $uzivatel->show_login_form();
                                }
                            }
                            ?>      


                            <div class="zeme">
                                <h3>
                                    <?php
                                    echo $serial_list->show_nadpis();
                                    ?>
                                </h3>


                                <table width="298">
                                    <tr>
                                        <?php
                                        //mam typ a nemam zemi
                                        if ($_GET["typ"] and !$_GET["zeme"]) {
                                            if ($_GET["typ"] == "poznavaci-zajezdy"  or $_GET["typ"] == "eurovikendy") {
                                                //u poznavacek vypisem jen prvnich par zemi s obrazkem
                                                $menu = new Menu_katalog("dotaz_top_zeme_foto", $_GET["typ"], "", "", 8);
                                                echo $menu->show_zeme_typ();
                                                ?>
                                                <table width="298">
                                                    <tr><td colspan="4"><b>Dal�� zem�</b></td>
                                                    </tr><tr>    
                                                        <?php
                                                        $menu2 = new Menu_katalog("dotaz_zeme_typ", $_GET["typ"], "", "");
                                                        echo $menu2->show_top_zeme(true);
                                                        ?>     
                                                </table>  

                                                <?php
                                            } else {
                                                $menu = new Menu_katalog("dotaz_zeme_typ", $_GET["typ"], "", "");
                                                echo $menu->show_zeme_typ();
                                            }
                                        } else if ($_GET["zeme"]) {
                                            if (!$_GET["typ"]) {

                                                $menu = new Menu_katalog("dotaz_typy", "", $_GET["zeme"], "", 10, $_GET["destinace"], $_GET["id_destinace"]);
                                                echo $menu->show_typy_pobytu();

                                                if (!$_GET["destinace"]) {
                                                    $menu = new Menu_katalog("dotaz_destinace", $_GET["typ"], $_GET["zeme"], "");
                                                    echo $menu->show_destinace("list");
                                                }
                                            } else {
                                                if (!$_GET["destinace"]) {
                                                    $menu = new Menu_katalog("dotaz_destinace", $_GET["typ"], $_GET["zeme"], "");
                                                    if ($menu->get_pocet_zajezdu() > 1) {
                                                       /* if ($_GET["typ"] != "za-sportem" and $_GET["typ"] != "pobytove-zajezdy" and $_GET["typ"] != "pobyty-hory" and $_GET["typ"] != "lyzovani") {
                                                            echo $menu->show_destinace(); 
                                                        }*/
                                                        $show_destinace = true;
                                                    } else {
                                                        $show_destinace = false;
                                                    }
                                                }
                                            }

                                            if ($_GET["typ"] == "lazenske-pobyty" and $_GET["destinace"] != "") {
                                                //zobrazit ubytovani
                                               /* $menu = new Menu_katalog("dotaz_ubytovani", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                echo $menu->show_ubytovani();*/
                                                $menu = new Menu_katalog("dotaz_destinace_serial", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                echo $menu->show_destinace_serial();
                                            } else if ($_GET["typ"] == "jednodenni-zajezdy" or $_GET["typ"] == "poznavaci-zajezdy"  or $_GET["typ"] == "eurovikendy" /*or $_GET["destinace"] != ""*/) {
                                                //zobrazit serialy
                                                //$menu = new Menu_katalog("dotaz_serialy", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                //echo $menu->show_serial();
                                                $menu = new Menu_katalog("dotaz_destinace_serial", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                echo $menu->show_destinace_serial();
                                            } else if (($_GET["typ"] == "za-sportem" or $_GET["typ"] == "pobytove-zajezdy" or $_GET["typ"] == "pobyty-hory" or $_GET["typ"] == "lyzovani") and $show_destinace = true) {
                                                //zobrazit serialy za sportem - podle destinace/serial
                                                $menu = new Menu_katalog("dotaz_destinace_serial", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                echo $menu->show_destinace_serial();

                                                //zobrazit informace                                                                     
                                            } else if ($_GET["typ"] != "" and $_GET["typ"] != "lazenske-pobyty") {
                                               /* if ($show_destinace) {
                                                    $menu = new Menu_katalog("dotaz_serialy", $_GET["typ"], $_GET["zeme"], "", 80);
                                                    echo $menu->show_serial("list");
                                                } else {
                                                    $menu = new Menu_katalog("dotaz_serialy", $_GET["typ"], $_GET["zeme"], "", 30);
                                                    echo $menu->show_serial();
                                                }*/
                                                $menu = new Menu_katalog("dotaz_destinace_serial", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                echo $menu->show_destinace_serial();
                                                
                                                //zobrazit serialy, radkove        
                                            } else if ($_GET["typ"] == "lazenske-pobyty") {
                                                //zobrazit serialy, radkove
                                               /* if ($show_destinace) {
                                                    $menu = new Menu_katalog("dotaz_ubytovani", $_GET["typ"], $_GET["zeme"], "", 80);
                                                    echo $menu->show_ubytovani("list");
                                                } else {
                                                    $menu = new Menu_katalog("dotaz_ubytovani", $_GET["typ"], $_GET["zeme"], "", 30);
                                                    echo $menu->show_ubytovani();
                                                }*/
                                                $menu = new Menu_katalog("dotaz_destinace_serial", $_GET["typ"], $_GET["zeme"], "", 50, "", $_GET["id_destinace"]);
                                                echo $menu->show_destinace_serial();
                                            }
                                            //mam zemi, nemam typ zajezdu    
                                        } else if (!$_GET["typ"] and $_GET["zeme"]) {
                                            $menu = new Menu_katalog("dotaz_typy", "", $_GET["zeme"], "");
                                            echo $menu->show_typy_pobytu();

                                            //mam zemi, typ, nemam destinaci             
                                        } else if ($_GET["typ"] and $_GET["zeme"] and !$_GET["destinace"]) {
                                            //zalezi na typu serialu                
                                            $menu = new Menu_katalog("dotaz_destinace", $_GET["typ"], $_GET["zeme"], "");
                                            echo $menu->show_destinace();


                                            /* $menu = new Menu_katalog("dotaz_podtypy",$_GET["typ"], $_GET["zeme"], "");
                                              echo $menu->show_podtypy(); */

                                            //mam zemi, typ, i destinaci                   
                                        } else {
                                            $menu = new Menu_katalog("dotaz_typy", "", "", "");
                                            echo $menu->show_typy_pobytu();

                                            echo "<table width=\"298\">
            <tr><td colspan=\"4\"><b>Nej��dan�j�� zem�</b></td>
            </tr><tr>    ";

                                            $menu2 = new Menu_katalog("dotaz_top_zeme", "", "", "");
                                            echo $menu2->show_top_zeme();

                                            echo "</table>     
        <table width=\"298\">
            <tr><td colspan=\"4\"><b>Nejzaj�mav�j�� sportovn� akce</b></td>
            </tr><tr>    ";

                                            $menu3 = new Menu_katalog("dotaz_top_sporty", "", "", "");
                                            echo $menu3->show_top_sporty();

                                            echo "</table>";
                                        }
                                        $time = print_time($time, "Left menu", $collector);
                                        ?>
                                </table>


                            </div>


                                        <?php
					echo $serial_list->show_map();

                                        // zobrazit zamereni lazni
                                        if ($_GET["id_destinace"] != "") {
                                            $info_destinace = new Informace_zeme("", "", $_GET["zeme"], $_GET["id_destinace"], 0, "random", 1);
                                            $info_destinace->get_next_radek();

                                            if ($info_destinace->get_popis_lazni() != "") {
                                                echo "
           <div class=\"vyhledavani\" style=\"margin-top:10px;\">
            <h3>ZAM��EN� L�ZN�</h3> 
            <ul class=\"licha\" style=\"padding-left:10px;\">";
                                                $lazne_array = explode(";", $info_destinace->get_popis_lazni());
                                                foreach ($lazne_array as $lazne) {
                                                    echo "<li>" . $lazne . "</li>\n";
                                                }
                                                echo "
            </ul>
           </div>";
                                            }
                                            if ($info_destinace->get_popis_strediska() != "") {
                                                echo "
           <div class=\"vyhledavani\" style=\"margin-top:10px;\">
            <h3>POPIS ST�EDISKA</h3> 
            <ul class=\"licha\" style=\"padding-left:10px;\">";
                                                $stredisko_array = explode(";", $info_destinace->get_popis_strediska());
                                                foreach ($stredisko_array as $stredisko) {
                                                    echo "<li>" . $stredisko . "</li>\n";
                                                }
                                                echo "
            </ul>
           </div>";
                                            }
                                        } else if ($_GET["zeme"] != "") {
                                            $info_destinace = new Informace_zeme("zeme", "", $_GET["zeme"], "", 0, "random", 1);
                                            $info_destinace->get_next_radek();
                                        }


                                        $time = print_time($time, "Before recs", $collector);
                                     //   if ($pocet_zajezdu >= 8) {
                                            ?>


                                <div class="akce" style="margin-top:10px;">

                                    <h3>DOPORU�UJEME</h3>
                                <?php
                              //  $pocet_doporuceni = min(array(6, floor(($pocet_zajezdu / 4))));
                                



                              echo "<div id=\"loading_doporuceni\" class=\"lds-spinner-wrapper\"><div class=\"lds-spinner\"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>";

                              echo "<table id=\"recommended_katalog\">";
                              
                              
                              $ajax_query = array("ajax_action=recommended_katalog");
                              foreach ($_GET as $key => $value) { 
                                  $ajax_query[] = "$key=$value";
                              }
                              $ajax_query = implode("&", $ajax_query);
                              $ajax_query = "/ajax_loader.php?".$ajax_query;
                              //echo $ajax_query;
                              ?>
                              <script  type="text/javascript">
                                  $.ajax({url: "<?php echo $ajax_query;?>", success: function(result){
                                      $("#recommended_katalog").html(result);
                                      $("#recommended_katalog").find("script").each(function(i) {
                                                  eval($(this).text());
                                              });
                                  },
                                  complete: function(){
                                          $('#loading_doporuceni').hide();
                                      }
                                  });
                              
                              </script>
                              <?php


                                echo "</table>";
                                $time = print_time($time, "after recs", $collector);
                                ?>
                                </div>      
                                    <?php
                              //  }
                                //zobrazit info o zemi/destinaci     
                                if ($_GET["id_destinace"] != "") {
                                    if ($info_destinace->get_nazev() != "") {
                                        ?>      
                                    <div class="kontakt" style="margin-top:10px;">
                                        <h3><?php echo $info_destinace->get_nazev(); ?></h3>
                                    <?php
                                    if ($info_destinace->get_id_foto() != "") {
                                        $foto = "<img	src=\"https://www.slantour.cz/foto/nahled/" . $info_destinace->get_foto_url() . "\"
                                            alt=\"" . $info_destinace->get_nazev_foto() . " - " . $info_destinace->get_popisek_foto() . "\"
                                            width=\"150\" class=\"round fright\" />";
                                    } else {
                                        $foto = "";
                                    }
                                    echo $foto .
                                    "<p style=\"font-weight:bold;\">" . $info_destinace->get_popisek_clear() . "</p>" .
                                    "<p>" . $info_destinace->get_popis_short() .
                                    " &nbsp; &nbsp; &nbsp; &nbsp;<a href=\"" . $info_destinace->get_adress(array("informace", $info_destinace->get_nazev_web())) . "\">dal�� informace &gt;&gt;</a>" .
                                    "</p>";
                                    ?>      
                                    </div> 
                                        <?php
                                    }
                                }
                                ?>    

                            <div class="kontakt" style="margin-top:10px;">
                                <h3>KONTAKTN� INFORMACE</h3>
                                <a href="https://www.slantour.cz"><img style="border:none;" src="/img/slantour_logo.gif" class="fright" alt="Logo CK SLAN tour"/></a>
                                <p style="padding: 3px;">Web slantour.cz provozuje <b>SLAN tour s.r.o.</b></p>          
                                <p style="padding: 0 2px 0 10px; margin-top:0; color: #191970;">
                                    tel.: (+ 420) 312522702<br/>
                                    mob.: (+ 420) 604255018<br/>
                                    e-mail: info@slantour.cz<br/>
                                    web: <a href="https://www.slantour.cz">www.slantour.cz</a><br/></p>
                                <p style="padding: 3px;"><a  href="/o-nas.html" title="V�echny kontakty - CK SLAN tour">kompletn� kontakty</a></p>   
                            </div> 

                            <div style="margin-top:10px;">	
                                <a href="http://www.goparking.cz/rezervace/krok1/?promo=SLAN790" title="Nab�dka v�hodn�ho a bezpe�n�ho parkov�n� pro na�e klienty p��mo u leti�t� Praha - Ruzyn�"><img style="border: 1px solid black;" src="https://www.slantour.cz/pix/go210x75.jpg" alt="Parkov�n� na leti�ti" width="300"></a>
                            </div>  


                            <div class="akce" style="margin-top:10px;">
                                <h3>POJI�T�N�</h3>          
                                <p style="padding: 3px;">Cestovn� kancel��  SLAN tour s.r.o. je poji�t�na dle z�kona <I>159/1999 Sb.</I> pro p��pad insolvence CK u <b>poji��ovny UNIQA a. s.</b><br /><br />
                                <p style="padding: 3px;">CK SLAN tour je �lenem odborn�ho profesn�ho sdru�en� <b>Albatros</b>        
                            </div>   

                        </div>      






                        <div class="column2-unit-right"> 

                            <!--
                            <div class="vyhledavani" >
                                <h3>VYHLED�V�N�</h3>          
<?php
// echo $serial_list->show_filtr("vyhledavani");
?>
                              </div>   
                            -->

                            <div id="slevy_katalog" >    
                            <?php
                            $time = print_time($time, "Left done", $collector);
 


                            //echo "<div id=\"loading_slevy\" class=\"lds-spinner-wrapper\"><div class=\"lds-spinner\"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>";
                            $time = print_time($time, "Left bar included", $collector);
                            
                            $ajax_query = array("ajax_action=slevy_katalog");
                            foreach ($_GET as $key => $value) { 
                                $ajax_query[] = "$key=$value";
                            }
                            $ajax_query = implode("&", $ajax_query);
                            $ajax_query = "/ajax_loader.php?".$ajax_query;
                            ?>
                            <script  type="text/javascript">
                                //$('#loading_slevy').show();
                                $.ajax({url: "<?php echo $ajax_query;?>", 
                                    success: function(result){
                                        $("#slevy_katalog").html(result);
                                    },
                                    complete: function(){
                                       // $('#loading_slevy').hide();
                                    }
                            
                                });
                            
                            </script>
                            <?php

                            $time = print_time($time, "Slevy", $collector);
                            ?>
                            </div>

                            <div class="kontakt">        
                                <h3>KATALOG Z�JEZD�</h3>

                            <?php
                            echo $serial_list->show_filtr("pod_nadpisem");
                             $serial_list->show_list();
                            
                            echo $serial_list->show_strankovani();
                            $time = print_time($time, "katalog", $collector);
                            ?>  

                            </div>  


                        </div>


                        <hr class="clear-contentunit" />   

                        <!-- Content unit - One column -->




                        <div class="column1-unit" style="padding-top:0.5em">
         <?php
            echo "<div id=\"loading_vahy\" style=\"background-color: lightgrey;\" class=\"lds-spinner-wrapper\"><div class=\"lds-spinner\"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div></div>";

         ?>                  
          <p id="bottom_katalog">
              <?php
                $ajax_query = array("ajax_action=bottom_katalog");
                foreach ($_GET as $key => $value) { 
                    $ajax_query[] = "$key=$value";
                }
                $ajax_query = implode("&", $ajax_query);
                $ajax_query = "/ajax_loader.php?".$ajax_query;
                //echo $ajax_query;
                ?>
                <script  type="text/javascript">
                    $.ajax({url: "<?php echo $ajax_query;?>", success: function(result){
                        $("#bottom_katalog").html(result);
                    },
                    complete: function(){
                            $('#loading_vahy').hide();
                        }
                    });

                </script>

            
          </p>
        </div>     
        
<?php
$time = print_time($time, "vahy", $collector);
?>
        
                        <hr class="clear-contentunit" />

                    </div>
                </div>


                <!-- C. FOOTER AREA -->      

                <div class="footer">
<?php include_once "./includes/pata.inc.php"; 


?>     
                </div> 


<?php
    echo "<?--";
    print_r( $collector);
    echo "-->";
mysqli_close($GLOBALS["core"]->database->db_spojeni);
?>
                </body>
                </html>
