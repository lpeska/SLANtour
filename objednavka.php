<?php
/**   \file
* index.php - hlavni stranka klientske casti systemu
*	- je na ni provaden reload př zpracování formulářů ostatních modulů
* 	- zobrazuje podrobné vyhledávání a tipy na zájezdy
*/

//spusteni prace se sessions


	$adress = "";
//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php";

require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/informace_destinace.inc.php"; //menu katalogu

require_once "./classes/serial.inc.php"; //seznam serialu

require_once "./classes/serial_zajezd.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_fotografie.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_dokument.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_informace.inc.php"; //seznam zajezdu serialu
require_once "./classes/serial_ceny.inc.php"; //seznam zajezdu serialu
require_once "./classes/zajezd_ubytovani.inc.php"; //seznam serialu
require_once "./classes/spravce.inc.php"; //seznam zajezdu serialu
require_once "./classes/varovna_zprava.inc.php"; //seznam serialu
require_once "./classes/rezervace_objednavka.inc.php"; //seznam serialu

require_once './payments/EnumPaymentMethods.php';

        if($_GET["lev4"] !=""){//mame velmi pravdepodobne stary serial+zajezd
		             
     
          $adresa = "/zajezdy/objednavka/".$_GET["lev3"]."/".$_GET["lev4"];             
            header ('HTTP/1.1 301 Moved Permanently');
            header ("Location: http://".$_SERVER['HTTP_HOST'].$adresa);
            exit;
            
        }else if($_GET["lev3"] !=""){ //mame velmi pravdepodobne stary serial	
            
           $adresa = "/zajezdy/zobrazit/".$_GET["lev3"];             
            header ('HTTP/1.1 301 Moved Permanently');
            header ("Location: http://".$_SERVER['HTTP_HOST'].$adresa);
            exit;     
            	                    
        }else if($_GET["lev1"] !=""){
            if($_GET["lev2"] =="" and $_POST["id_zajezd"]>0){
                $_GET["lev2"] =  $_POST["id_zajezd"];        
            }else if($_GET["lev2"] =="" and $_GET["id_zajezd"]>0){
                $_GET["lev2"] =  $_GET["id_zajezd"];        
            }else{
                $serial = new Serial($_GET["lev1"]);
                $nazev_serialu = $_GET["lev1"];
                $_GET["id_serial"] = $serial->get_id();
                $serial->create_zajezdy();
		if($serial->get_zajezdy()->get_next_radek()){//mame jeden zajezd
			$id_zajezd = $serial->get_zajezdy()->get_id_zajezd();
		}
		if($serial->get_zajezdy()->get_next_radek()){//mame druhy zajezd, spatne
				$no_reload_with_zajezd = true;
			}else{
				$no_reload_with_zajezd = 0;
			}

			if(!$no_reload_with_zajezd and $id_zajezd!=""){ //mame prave jeden zajezd
				$_GET["lev2"] = $id_zajezd ;
			}
            }
            if($_GET["lev2"] !=""){
                $serial = new Serial_with_zajezd($_GET["lev1"],$_GET["lev2"]);
                $zobrazit_zajezd = true;
                $termin_od = $serial->get_termin_od();
                $termin_do = $serial->get_termin_do();
                $id_serial = $serial->get_id_serial();
                $zeme_text = $serial->get_nazev_zeme_web();
                $_GET["id_serial"]=$id_serial;
                $_GET["id_zajezd"]=$_GET["lev2"];
            }    

        }

//zpracovani hlasky (jsme za headerem pro presmerovani)
	if($_SESSION["hlaska"]!=""){
		$hlaska_k_vypsani = $_SESSION["hlaska"];
		$_SESSION["hlaska"] = "";
	}else{
		$hlaska_k_vypsani = "";
	}
//zajezd = nazev_serial_web
//$serial_list = new Serial_list($nazev, $zeme_text, $_GET["id_serial"], $nazev_ubytovani, $_GET["destinace"], $_GET["zajezd"], $_SESSION["termin_od"],$_SESSION["termin_do"],$_GET["str"],"datum",100);
    $objednavka_form = new Rezervace_objednavka("show_form", $_GET["id_serial"], $_GET["id_zajezd"], $_POST["upresneni_terminu_od"], $_POST["upresneni_terminu_do"]);

  $zajezd_nazev_text1p = "ZÁJEZDY";
  $podobne_zajezdy = "PODOBNÉ ZÁJEZDY/POBYTY";
  $objednavka_zajezdu = "OBJEDNÁVKA ZÁJEZDU/POBYTU";
  $dokumenty_k_zajezdu = "DOKUMENTY K ZÁJEZDU";
  $text_sluzby = "SLUŽBY A PŘÍPLATKY";
//modifikatory
if($serial->get_id_sablony_zobrazeni()!=""){
    if($serial->get_id_sablony_zobrazeni()==7){
        $zajezd_akce = "AKCE";        
        $zajezd_nazev_text1p = "VSTUPENKY";
        $podobne_zajezdy = "PODOBNÉ AKCE";
        $objednavka_zajezdu = "OBJEDNÁVKA VSTUPENEK";
        $dokumenty_k_zajezdu = "DOKUMENTY KE VSTUPENKÁM";
        $text_sluzby = "VSTUPENKY A DALŠÍ SLUŽBY";
    }else if($serial->get_id_sablony_zobrazeni()==6){
        $zajezd_akce = "ZÁJEZD/POBYT";
        $zajezd_nazev_text1p = "ZÁJEZDY";
         $podobne_zajezdy = "PODOBNÉ ZÁJEZDY";
         $objednavka_zajezdu = "OBJEDNÁVKA ZÁJEZDU";
         $dokumenty_k_zajezdu = "DOKUMENTY K ZÁJEZDU";
         $text_sluzby = "SLUŽBY A PŘÍPLATKY";
    }else{
        $zajezd_akce = "ZÁJEZD/POBYT";
        $zajezd_nazev_text1p = "ZÁJEZDY";
         $podobne_zajezdy = "PODOBNÉ ZÁJEZDY/POBYTY";
         $objednavka_zajezdu = "OBJEDNÁVKA ZÁJEZDU/POBYTU";
         $dokumenty_k_zajezdu = "DOKUMENTY K ZÁJEZDU";
         $text_sluzby = "SLUŽBY A PŘÍPLATKY";
        
    }
    
}        
        
        
if($serial!="") {
    $titulek= $objednavka_zajezdu.": ".$serial->show_titulek();
}


$display_sluzby=false;
$display_termin=false;
$display_kontakty=false;
if(!$_GET["form_update"]){
   
    $_SESSION["hotovo_sluzby"]=false;
    $_SESSION["hotovo_termin"]=false;
    $_SESSION["hotovo_kontakty"]=false;
}

$zpracovano_odeslani=false;

//konec_zpracovani_ubytovani
//zpracovani_sluzeb

if(!$zpracovano_odeslani){
if($_POST["zmenit_termin"]!="" and $serial->get_dlouhodobe_zajezdy()){
    $zpracovano_odeslani=true;
    $display_termin=true;
    $_SESSION["hotovo_termin"]=false;
}
if($_POST["submit_termin"]!=""){
    $_SESSION["upresneni_termin_od"]=$_POST["upresneni_termin_od"];
    $_SESSION["upresneni_termin_do"]=$_POST["upresneni_termin_do"];        
    
    $zpracovano_odeslani=true;
    $display_sluzby=true;
    $_SESSION["hotovo_termin"]=true;
}
}

if(!$zpracovano_odeslani){
if($_POST["zmenit_sluzby"]!=""){
    $zpracovano_odeslani=true;
    $display_sluzby=true;
    $_SESSION["hotovo_sluzby"]=false;
    $_SESSION["hotovo_termin"]=true;
}
if($_POST["submit_sluzby"]!=""){
    $_SESSION["pocet_osob"]=intval($_POST["pocet_osob"]);

    $zpracovano_odeslani=true;
    $display_kontakty=true;
    $_SESSION["hotovo_sluzby"]=true;
    $_SESSION["hotovo_termin"]=true;
    if($_SESSION["pocet_osob"]<=0){
        //chybova hlaska
    }
}
}


if(!$zpracovano_odeslani){
if($_POST["zmenit_kontakty"]!=""){
    $zpracovano_odeslani=true;
    $display_kontakty=true;
    $_SESSION["hotovo_termin"]=true;
    $_SESSION["hotovo_sluzby"]=true;
    $_SESSION["hotovo_kontakty"]=false;
}
}
if(!$zpracovano_odeslani){
if($_POST["submit_kontakty"]!=""){
    $zpracovano_odeslani=true;
    $display_kontakty=false;
    $_SESSION["hotovo_termin"]=true;
    $_SESSION["hotovo_sluzby"]=true;
    $_SESSION["hotovo_kontakty"]=true;
}
}




if( $_SESSION["hotovo_termin"] and $_SESSION["hotovo_sluzby"] and $_SESSION["hotovo_kontakty"]){

    $objednavka = new Rezervace_objednavka("finish", $_GET["id_serial"], $_GET["id_zajezd"], $_POST["upresneni_terminu_od"], $_POST["upresneni_terminu_do"]);

    if( !$objednavka->get_error_message() ){
        $_SESSION["ordered_serial"] = $_GET["id_serial"];
        $_SESSION["ordered_zajezd"] = $_GET["id_zajezd"];
        $_SESSION["id_objednavky"] = $objednavka->get_id_objednavka();
	$adress="/dekujeme.php";
        //pokud jsem zadal platbu kartou, presmeruju na platebni branu!!
        if($_POST["zpusob_platby"]=="".EnumPaymentMethods::METHOD_CARD_ALL."" or $_REQUEST["zpusob_platby"]== Rezervace_objednavka::ID_PLATBA_KARTOU){
            $_POST["zpusob_platby"] = EnumPaymentMethods::METHOD_CARD_ALL;
            $_SESSION["id_objednavky"]=$objednavka->get_id_objednavka();
            $_SESSION["email"]=$objednavka->get_email();
            $_SESSION["price"]=$objednavka->get_castka();
            $_SESSION["method"]=$_POST["zpusob_platby"];
            //budeme chtit presmerovat na https, az bude k dispozici
            header("Location: http://".$_SERVER['HTTP_HOST']."/payments/payment.php");
	    exit;
        }
	$_SESSION["hlaska"]=$_SESSION["hlaska"].$objednavka->get_ok_message();
    }else{
	$_SESSION["hlaska"]=$_SESSION["hlaska"].$objednavka->get_error_message();
        $_SESSION["hotovo_sluzby"]=false;
        $_SESSION["hotovo_vstupenky"]=false;
        $_SESSION["hotovo_kontakty"]=false;
        $display_sluzby=true;
        $display_vstupenky=false;
        $display_kontakty=false;
    }
    //print_r($objednavka);
}

if(!$_GET["form_update"] and !$zpracovano_odeslani){
    if($serial->get_dlouhodobe_zajezdy()){
       $display_termin=true;
       $_SESSION["hotovo_termin"]=false;
       
    }else{
       $display_termin=false;
       $_SESSION["hotovo_termin"]=true;
       $display_sluzby=true;
       
    }
}
	if($adress){
			header("Location: http://".$_SERVER['HTTP_HOST'].$adress);
			exit;
	}

//zpracovani hlasky (jsme za headerem pro presmerovani)
	if($_SESSION["hlaska"]!=""){
		$hlaska_k_vypsani = $_SESSION["hlaska"];
		$_SESSION["hlaska"] = "";
	}else{
		$hlaska_k_vypsani = "";
	}
//print_r($_SESSION);
//print_r($_POST);
//print_r($_GET);
        
$zeme_nazev = $serial->get_zeme();
$zeme_nazev_web = $serial->get_nazev_zeme_web();
$destinace_nazev = $serial->get_destinace();
$destinace_nazev_web = Serial_list::nazev_web_static($serial->get_destinace());
$_GET["destinace"] =  $serial->get_id_destinace();
$serial_list = new Serial_list("", "", $_GET["text"], "", "", $_GET["zajezd"], $_GET["termin_od"],$_GET["termin_do"],$_GET["str"],"datum",100,"select_serial_zajezd"); 


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
<head>
  <title><?php echo $titulek;?></title>
  <meta http-equiv="cache-control" content="no-cache" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Keywords" content="<?php echo $serial->get_nazev().", ".$serial_list->show_keyword();?> "/>
<meta name="Description" content="<?php echo $serial->get_nazev().", ".$serial_list->show_description();?>" />

<meta name="Robots" content="index, follow"/>

  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_setup.css" />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_text.css" />
  <link rel="icon" type="image/x-icon" href="/img/favicon.gif" />
  <!--[if lt IE 9]>
  	<script type="text/javascript" src="/js/html5.js"></script>
  <![endif]-->  
 <link type="text/css" href="/jqueryui/css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />
 <script language="JavaScript" type="text/javascript" src="/js/storeTrace.js"></script>
  <script language="JavaScript" type="text/javascript" src="/js/hide_show_div.js"></script>
 		<script type="text/javascript" src="/jqueryui/js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="/jqueryui/js/jquery-ui-1.8.18.custom.min.js"></script>
  <script type="text/javascript">
       $(function(){
                      var availableTags = 
			<?php
                  include './autocomplete.php';
                        ?>
                      
                      // Accordion
                      $( "#keyword" ).autocomplete({
			source: availableTags,
			minLength: 3,
			select: function( event, ui ) {
				//set timer to send query
			}
                    });                
        });                    
  </script>
 <?php
 if($_SESSION["hotovo_termin"]==true and $serial->get_dlouhodobe_zajezdy()){
                            $value_od_en =  $serial->change_date_cz_en($_POST["upresneni_terminu_od"]);
                            $value_do_en =  $serial->change_date_cz_en($_POST["upresneni_terminu_do"]);
            }else{
                            $zajezd_od = strtotime($serial->get_termin_od());
                            $today = strtotime(Date("Y-m-d")); 
                            if ($zajezd_od > $today) { 
                                $value_od_en = $serial->get_termin_od() ;                                
                            }else{ 
                                $value_od_en =  Date("Y-m-d") ;                                    
                            }
                            $value_do_en =  $serial->get_termin_do() ;
            }
            
 echo " <script type='text/javascript'>
            $(function(){
                $('#upresneni_terminu_od').datepicker({                        
                    inline: true,
                    dateFormat: 'dd.mm.yy',
                    dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pa', 'So'],
                    monthNames:  ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
                    yearRange:    'c-12:c+2',
                    firstDay: 1
                });
                $('#upresneni_terminu_do').datepicker({                 
                    gotoCurrent: true,
                    inline: true,
                    dateFormat: 'dd.mm.yy',
                    dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pa', 'So'],
                    monthNames:  ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
                    yearRange:    'c-12:c+2',
                    firstDay: 1
                });
                var odStr=\"".$value_od_en."\"; 
                var doStr=\"".$value_do_en."\";     
              
                var od_array = odStr.split(\"-\");
                var do_array = doStr.split(\"-\");
               

                var termin_od =new Date(od_array[0],(od_array[1]-1),od_array[2]);
                    
                var termin_do =new Date(do_array[0],(do_array[1]-1),do_array[2]);
                   
                
                $('#upresneni_terminu_od').datepicker('setDate', termin_od);
                $('#upresneni_terminu_do').datepicker('setDate', termin_do);
            });
        </script>";
 
//component start
 /*
if($serial->get_id()){
    ComponentCore::loadJavaScripts($serial->get_id());
}else{
    ComponentCore::loadJavaScripts(0);
}*/
//component end
?>  
</head>
<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->

<body  <?php
        echo "onload='storeTrace(".$_SESSION["user"].", ".$_SESSION["session_no"].", \"objednavka\", \"".$serial->get_nazev_typ_web()."\", \"".$serial->get_nazev_zeme_web()."\", \"".$serial->get_id_destinace()."\", \"".$_GET["termin_od"]."\", \"".$_GET["termin_do"]."\", \"".$_GET["text"]."\", \"\", \"".$_GET["lev1"]."\", \"\", \"".$_GET["lev2"]."\", \"1\");'"
    ?> >
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
        <?php echo $hlaska_k_vypsani; ?>
        <!-- Pagetitle -->
        <h1 class="pagetitle"><?php echo $objednavka_zajezdu.": ".$serial->get_nazev()."";?></h1>
  <div class="column2-unit-left" >      
      
            <?php
      /*login z agentur*/
$core = Core::get_instance();
$id_registrace = $core->get_id_modul_from_typ("registrace");

if($id_registrace !== false){
	$uzivatel = User::get_instance();	
//	print_r($uzivatel);
	//pokud je uzivatel prihlasen, vypiseme uzivatelske menu, jinak formular pro prihlaseni
	if( $uzivatel->get_correct_login() ){
            echo $uzivatel->show_klient_menu();
	}else{
		//echo $uzivatel->show_login_form();
	}
}
      ?>
      
      <div class="zeme_notop">          
<table width="298">
 <tr>
          <?php
    //mam typ a nemam zemi
if ($_GET["id_serial"]) {
    $serial->create_foto();
    $first = 1;
    while($serial->get_foto()->get_next_radek()){
        if($first){
            $first = 0;
            echo $serial->get_foto()->show_list_item("nahled");
            echo $serial->show_slevy_zkracene();
        }else{
            echo $serial->get_foto()->show_list_item("dalsi_foto");
        }
    }
} 
          ?>
</table>
  </div>
 
          
               
     <?php
     if($serial->get_highlights("seznam")!=""){
	?>						
    <div class="highlights" style="margin-top:10px;">
        <ul class="highlights">
	<?php		
		echo $serial->get_highlights("seznam");
	?>
        </ul>    
    </div>
	<?php	
    }
    
    $serial->create_dokumenty();
        //echo "dokumenty";
       // print_r($serial->get_dokumenty());
    if($serial->get_dokumenty()->get_pocet_radku()!=0){
		//mame nejake dokumenty
	?>
	<div class="vyhledavani" style="margin-top:10px;">
            <h3><?php echo $dokumenty_k_zajezdu; ?></h3>
	<ul>		
	<?php		
            while($serial->get_dokumenty()->get_next_radek()){
		echo $serial->get_dokumenty()->show_list_item("seznam");
            }
	?>
	</ul>
	</div>
	<?php	
    }
    
    $serial->create_informace();
    $popis_strediska = "";
    $popis_lazni = "";
    if($serial->get_informace()->get_pocet_radku()!=0){
	//mame nejake dokumenty
	?>
	<div class="kontakt" style="margin-top:10px;">
            <h3>DALŠÍ INFORMACE</h3>
		<ul>
	<?php		
        $i=0;
	while($serial->get_informace()->get_next_radek()){
            $popis_strediska .= $serial->get_informace()->get_info_o_stredisku();
            $popis_lazni .= $serial->get_informace()->get_zamereni_lazni();

            if($i==0){
                $i++;
                echo $serial->get_informace()->show_list_item("first");
            }else{
                echo $serial->get_informace()->show_list_item("seznam");
            }
	}
	?>
		</ul>
	</div>
	<?php	
    }
    
      if($popis_lazni!=""){
          echo "
           <div class=\"vyhledavani\" style=\"margin-top:10px;\">
            <h3>ZAMĚŘENÍ LÁZNÍ</h3> 
            <ul class=\"licha\" style=\"padding-left:10px;\">";
          $lazne_array = explode(";",$popis_lazni);
          foreach ($lazne_array as $lazne) {
              echo "<li>".$lazne."</li>\n";
          }
          echo "
            </ul>
           </div>";
      }
      if($popis_strediska!=""){
          echo "
           <div class=\"vyhledavani\" style=\"margin-top:10px;\">
            <h3>INFORMACE O STŘEDISKU</h3> 
            <ul class=\"licha\" style=\"padding-left:10px;\">";
          $lazne_array = explode(";",$popis_strediska);
          foreach ($lazne_array as $lazne) {
              echo "<li>".$lazne."</li>\n";
          }
          echo "
            </ul>
           </div>";
      }                 
     ?>
    
  <div class="kontakt" style="margin-top:10px;">
          <h3>KONTAKTNÍ INFORMACE</h3>
          <a href="https://www.slantour.cz"><img style="border:none;" src="/img/slantour_logo.gif" class="fright" alt="Logo CK SLAN tour"/></a>
          <p>Web termalni-lazne.info provozuje <b>SLAN tour s.r.o.</b></p>          
          <p style="padding: 0 2px 0 10px; margin-top:0; color: #191970;">
                                         	tel.: (+ 420) 312522702<br/>
                                		mob.: (+ 420) 604255018<br/>
				e-mail: info@slantour.cz<br/>
                                web: <a href="https://www.slantour.cz">www.slantour.cz</a><br/></p>
          <a href="/kontakty.php" title="Všechny kontakty - CK SLAN tour">kompletní kontakty</a>
  </div> 
      
      
      
</div>
  <div class="column2-unit-right"> 

<div class="akce">
        	<h3><?php  echo $objednavka_zajezdu; ?> od CK SLAN tour</h3>
                <?php echo $hlaska_k_vypsani;?>
<p><mark>Objednávka </mark> se sestává z 4 kroků:</p>
<ol style="font-size:0.8em;">
    <li class="item_1<?php echo ($display_termin!=false and $serial->get_dlouhodobe_zajezdy())?("_big"):("");?>"><strong>Upřesnění termínu:</strong> Některé naše zájezdy nemají specifikovaný přesný termín, ale pouze rámcové termíny, ve kterých si můžete zvolit příjezd a odjezd sami.</li>
    <li class="item_2<?php echo ($display_sluzby!=false)?("_big"):("");?>"><strong>Výběr služeb:</strong> Ve 2. kroku uvedete počet osob a vyplníte služby a příplatky, o které máte zájem.</li>
    <li class="item_3<?php echo ($display_kontakty!=false)?("_big"):("");?>"><strong>Kontaktní informace:</strong> V posledním kroku vyplníte Vaše kontaktní informace a základní osobní údaje účastníků zájezdu.</li>
    <li class="item_4"><strong>Potvrzovací e-mail:</strong> Po úspěšném odeslání objednávky vždy obdržíte potvrzovací e-mail s informacemi o způsobu platby. Pokud se tak nestane, kontaktujte nás na info@slantour.cz.</li>

</ol>

</p>
</div>
   
      
      <form name="objednavka" action="?form_update=1" method="post">
  <section id="content">
<section class="row-1">
    	<div class="container_12">
      	
<?php
$sluzby = "<span style=\"font-size:18px;font-weight:normal;color:black;\"><i>".$serial->change_date_en_cz($serial->get_termin_od())." - ".$serial->change_date_en_cz($serial->get_termin_do())." </i></span>";
 echo " <h1 class=\"item_1\">".$zajezd_akce.":
            <span style=\"font-size:18px;font-weight:normal;color:black;\"><i>".
            $serial->get_nazev()
            ." </i></span>
        </h1>";
 echo "<input type=\"hidden\" name=\"id_serial\" value=\"".$serial->get_id()."\"/>";
 echo "<input type=\"hidden\" name=\"nazev_zajezdu\" value=\"".$serial->get_nazev()."\"/>";
 echo "<input type=\"hidden\" name=\"id_zajezd\" value=\"".$serial->get_id_zajezd()."\"/>";

?>        
      </div>
 </section>	
      


    	<div class="container_12">
        <?php
            if($display_termin==false or !$serial->get_dlouhodobe_zajezdy()){
                $display = " style=\"display:none;\" ";
            }else{
                $display = "";
            }            

        //takova dost hloupa berlicka, zobrazi vyber terminu pouze pokud jsem v danem kroku, jinak je vyber schovaný v hidden polich   
           
        ?>
        <div class="clearfix" <?php echo $display;?>>
          <h1 class="item_1">Upřesnění termínu pobytu</h1>
          <div class="grid_12">
          	<!-- .box -->
          	<div class="kontakt">
                    <h3>Upřesnění termínu pobytu</h3>
                    <strong>Zde prosím zvolte o jaký termín (z daného rozmezí) máte zájem.<br/> Některé pobyty mají povinnou délku (např. "rekreační pobyt na 5 dní"). V takovém přípdě musí Vámi zvolený termín takové podmínky splňovat.<br/></strong>
                    <?php       
                        //echo $value_od.$value_do;
                        $value_od = $serial->change_date_en_cz($value_od_en);
                        $value_do = $serial->change_date_en_cz($value_do_en);
			$upresneni_term="Upřesnění termínu odjezdu: <span class=\"red\">*</span>
                                        <input id=\"upresneni_terminu_od\" name=\"upresneni_terminu_od\" type=\"text\" value=\"".$value_od."\" />						
					<br/>\n
                                        Upřesnění termínu návratu: <span class=\"red\">*</span>
                                        <input id=\"upresneni_terminu_do\" name=\"upresneni_terminu_do\" type=\"text\" value=\"".$value_do."\" />						
					\n";
                        echo $upresneni_term;
                        echo "<br/><input type=\"submit\" name=\"submit_termin\" value=\"Uložit upřesnění termínu\" /><br/>";

                    ?>                    
                </div>
          	<!-- /.box -->
          </div>
        </div>
        <?php
        
            if($_SESSION["hotovo_termin"]==true and $serial->get_dlouhodobe_zajezdy()){
                //print_r($_POST);
                $sluzby = "<span style=\"font-size:18px;font-weight:normal;color:black;\"><i>".$_POST["upresneni_terminu_od"]." - ".$_POST["upresneni_terminu_do"]." </i></span> 
                        ";
                echo "<h1 class=\"item_2\">Upřesnění termínu: ".$sluzby." <span style=\"font-size:14px;font-weight:bold;\"><input type=\"submit\" name=\"zmenit_termin\" value=\"Změnit\" /></span></h1>";
                //echo "<input type=\"hidden\" name=\"celkovy_pocet_noci\" value=\"".Rezervace_objednavka::static_calculate_pocet_noci($serial->get_termin_od(), $serial->get_termin_do(), $serial->change_date_cz_en($_POST["upresneni_terminu_od"]), $serial->change_date_cz_en($_POST["upresneni_terminu_do"]))."\" />";
            }else{
                if($serial->get_nazev_zajezdu()!=""){
                    $nazev_zajezdu = ", ".$serial->get_nazev_zajezdu();
                }
                $sluzby = "<span style=\"font-size:18px;font-weight:normal;color:black;\"><i>".$serial->change_date_en_cz($serial->get_termin_od())." - ".$serial->change_date_en_cz($serial->get_termin_do())." </i></span>";
                echo "<h1 class=\"item_2\">Termín: ".$sluzby.$nazev_zajezdu." </h1>";
                //echo "<input type=\"hidden\" name=\"celkovy_pocet_noci\" value=\"".Rezervace_objednavka::static_calculate_pocet_noci($serial->get_termin_od(), $serial->get_termin_do(),"","")."\" />";

            }
        ?>
      </div>


        <?php
            if($display_sluzby==false){
                $display = " style=\"display:none;\" ";
            }else{
                $display = "";
            }

        ?>
        <div class="clearfix" <?php echo $display;?>>
          <h1 class="item_2">Výběr služeb</h1>
          <div class="grid_12">
          	<!-- .box -->
          	<div class="kontakt">
                    <h3><?php echo $text_sluzby;?></h3>
                    <strong>Nejprve prosím vyplňte počet účastníků zájezdu.<br/> Dále pak u každé požadované služby specifikujte počet, o který máte zájem. <br/>Po dokončení klikněte na "Uložit služby".<br/></strong>
                    <?php
                        $serial->create_ceny();
                        echo $serial->get_ceny()->show_form_objednavka();
                    //tabulka sluzeb
                        echo "<br/><input type=\"submit\" name=\"submit_sluzby\" value=\"Uložit služby\" /><br/>";
                    ?>
                    
                    <p><strong>** Vyplňte alespoň jednu službu.</strong></p>
                </div>
          	<!-- /.box -->
          </div>
        </div>
        <?php
            if($_SESSION["hotovo_sluzby"]==true){
                $sluzby = "<span style=\"font-size:18px;font-weight:normal;color:black;\"><i>Celková cena služeb: ".$serial->get_ceny()->get_celkova_castka()." Kč</i></span>";
                echo "<h1 class=\"item_2\">Výběr služeb: ".$sluzby." <span style=\"font-size:14px;font-weight:bold;\"><input type=\"submit\" name=\"zmenit_sluzby\" value=\"Změnit\" /></span></h1>";
            }
        ?>
      

    
        <?php
            if($display_kontakty==false){
                $display = " style=\"display:none;\" ";
            }else{
                $display = "";
            }

        ?>

    	
      	
        <div class="clearfix" <?php echo $display;?>>
          <h1 class="item_4">Kontaktní informace</h1>  
          <div class="grid_12">
          	<!-- .box -->
          	<div class="kontakt">
                    <h3>Informace o objednavateli a účastnících zájezdu</h3>
                    <p><strong> * Údaje označené hvězdičkou jsou povinné.</strong></p>
                    <?php
                        echo $objednavka_form->show_form_kontaktni_informace();
                        
                    //tabulka sluzeb
                    ?>
                    <p><strong> * Údaje označené hvězdičkou jsou povinné.</strong>

<br/>Mám zájem o zasílání aktuálních nabídek CK: <input type="checkbox" name="novinky" checked="checked" value="ano"/></p>
<h4 style="color:#505050;font-weight:bold;margin:15px 5px 5px 5px;">Smluvní podmínky</h4>
<p>Odesláním objednávky souhlasíte se smluvními podmínkami CK SLAN tour pro daný lázeňský pobyt:
    <?php
        $podminky = "https://www.slantour.cz/dokumenty/".$serial->get_adresa_smluvni_podminky();
    ?>
    <a href="<?php echo $podminky;?>" title="smluvní podmínky">Smluvní podmínky</a></p>

<h4 style="color:#505050;font-weight:bold;margin:15px 5px 5px 5px;">Co se stane po odeslání?</h4>
<p>Po odeslání formuláře systém zkontroluje termíny a kapacity služeb. Pokud jsou volné, provede časově omezenou rezervaci zájezdu.
Pracovníci CK SLAN tour objednávku zkontrolují a v případě nejasností či problémů Vás budou dále kontaktovat. <br/><br/>

<b>Po úspěšném odeslání objednávky vždy obdržíte potvrzovací e-mail na Vámi zadanou adresu. </b>Pokud se tak nestane během několika minut po odeslání objednávky, kontaktujte nás na e-mailu info@slantour.cz.

                    </p>
                </div>
                                
          	<!-- /.box -->
          </div>
        </div>
        <?php
            if($_SESSION["hotovo_kontakty"]==true){
                $sluzby = "<span style=\"font-size:18px;font-weight:normal;color:black;\"><i></i></span>";
                echo "<h1 class=\"item_4\">Kontaktní informace: ".$sluzby." <span style=\"font-size:14px;font-weight:bold;\"><input type=\"submit\" name=\"zmenit_kontakty\" value=\"Změnit\" /></span></h1>";
            }
        ?>
      



    </form>
          

            

 </div>
    

      

  
 
 <hr class="clear-contentunit" />   


	 
	 </div>
      </div>

      
    <!-- C. FOOTER AREA -->      

    <div class="footer">
   <? include_once "./includes/pata.inc.php"; ?>     
  	 </div> 
  
</body>
</html>
   
   
   */
  