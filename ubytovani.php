<?php
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();

session_start();
require_once "./core/load_core.inc.php"; 


require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu
require_once "./classes/informace_destinace.inc.php"; //menu katalogu
require_once "./classes/serial.inc.php"; //seznam serialu
require_once "./classes/serial_dokument.inc.php"; //seznam serialu
require_once "./classes/serial_list_dokument.inc.php"; //seznam serialu
require_once "./classes/serial_ceny.inc.php"; //seznam serialu
require_once "./classes/serial_fotografie.inc.php"; //seznam serialu
require_once "./classes/zajezd_ubytovani.inc.php"; //seznam serialu
//zpracovani hlasky (jsme za headerem pro presmerovani)	
	if($_SESSION["hlaska"]!=""){
		$hlaska_k_vypsani = $_SESSION["hlaska"];
		$_SESSION["hlaska"] = "";
	}else{
		$hlaska_k_vypsani = "";
	}	
	


	//zpracovani hlasky (jsme za headerem pro presmerovani)	
	if($_SESSION["hlaska"]!=""){
		$hlaska_k_vypsani = $_SESSION["hlaska"];
		$_SESSION["hlaska"] = "";
	}else{
		$hlaska_k_vypsani = "";
	}
        //print_r($_GET);
        //echo $_GET["zeme"];// - nazev zeme
        if($_GET["lev1"]!=""){
            $_GET["ubytovani"] = $_GET["lev1"];
        }
$serial = new Serial_ubytovani($_GET["ubytovani"]);
$ubytovani_data = $serial->get_data();
$zeme_nazev = $ubytovani_data["nazev_zeme_web"];
$destinace_nazev = $ubytovani_data["nazev_destinace"];
$destinace_nazev_web = Serial_list::nazev_web_static($ubytovani_data["nazev_destinace"]);

$_GET["typ"] = $ubytovani_data["nazev_typ_web"]; 
$_GET["zeme"] = $ubytovani_data["nazev_zeme_web"]; 
$_GET["destinace"] = $ubytovani_data["nazev_destinace"]; 
$_GET["id_destinace"] = $ubytovani_data["id_destinace"]; 
$_GET["id_ubytovani"] = $ubytovani_data["id_ubytovani"]; 
$_GET["nazev_ubytovani_web"] = $ubytovani_data["nazev_web"]; 


$typ_serial = $ubytovani_data["nazev_typ"]; 
$typ_serial_web = $ubytovani_data["nazev_typ_web"]; 

$zeme_nazev = $ubytovani_data["nazev_zeme"];
$zeme_nazev_web = $ubytovani_data["nazev_zeme_web"];

$destinace_id = $ubytovani_data["id_destinace"];
$destinace_nazev = $ubytovani_data["nazev_destinace"]; 
$destinace_nazev_web = Serial_list::nazev_web_static($ubytovani_data["nazev_destinace"]);

$ubytovani_nazev = $ubytovani_data["nazev"];
$ubytovani_nazev_web = $ubytovani_data["nazev_web"];

//print_r($_GET);

$serial_list = new Serial_list("", "", $_GET["text"], $_GET["nazev_ubytovani_web"], "", $_GET["zajezd"], $_GET["termin_od"],$_GET["termin_do"],$_GET["str"],"datum",100,"select_serial_zajezd"); 
//        $serial_list->get_next_radek();
//print_r($serial_list);
//print_r($ubytovani_data);
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
<?
	echo $ubytovani_data["nazev"].": ".$serial_list->show_titulek();
?>
</title>  
  <meta http-equiv="cache-control" content="no-cache" />

<meta http-equiv="Content-Type" content="text/html; charset=windows-1250" />
<meta name="Keywords" content="<?php echo $ubytovani_data["nazev"].", ".$serial_list->show_keyword();?> "/>
<meta name="Description" content="<?php echo $ubytovani_data["nazev"].", ".$serial_list->show_description();?>" />

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
  <script type="text/javascript">
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
<script type="text/javascript" src="/highslide/highslide-with-gallery.js"></script>
<link rel="stylesheet" type="text/css" href="/highslide/highslide.css" />
<script type="text/javascript">
hs.graphicsDir = '/highslide/graphics/';
hs.align = 'center';
hs.transitions = ['expand', 'crossfade'];
hs.outlineType = 'rounded-white';
hs.fadeInOut = true;
hs.numberPosition = 'caption';
hs.dimmingOpacity = 0.75;

// Add the controlbar
if (hs.addSlideshow) hs.addSlideshow({
	//slideshowGroup: 'group1',
	interval: 5000,
	repeat: false,
	useControls: true,
	fixedControls: 'fit',
	overlayOptions: {
		opacity: .75,
		position: 'bottom center',
		hideOnMouseOut: true
	}
});
</script>                
<?php
//component start
        ComponentCore::loadJavaScripts(0,"..".$_GET["id_ubytovani"].".".$_GET["id_destinace"].".".$_GET["zeme"].".".$_GET["typ"].".","katalog");
//component end
?>                
</head>

<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->

<body <?php
        echo "onload='storeTrace(".$_SESSION["user"].", ".$_SESSION["session_no"].", \"katalog_ubytovani\", \"".$_GET["typ"]."\", \"".$_GET["zeme"]."\", \"".$_GET["id_destinace"]."\", \"".$_GET["termin_od"]."\", \"".$_GET["termin_do"]."\", \"".$_GET["text"]."\", \"".$_GET["nazev_ubytovani_web"]."\", \"\", \"\", \"\", \"\");'"
    ?> >
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
        ?>  
          </div>
        <!-- Pagetitle 
        <h1 class="pagetitle">L�zn� a term�ln� l�zn�</h1>-->
  <div class="column2-unit-left" >        
      <div class="zeme_notop">
          <h3>
        <?php
            if ($_GET["id_ubytovani"]) {
               // echo $ubytovani_data["nazev"];
            } else {
		//echo $serial_list->show_nadpis();
            }    
	?>
          </h3>
          
<div class="highslide-gallery">
<table width="298">
 <tr>
          <?php
    //mam typ a nemam zemi
if ($_GET["id_ubytovani"]) {
    $serial->create_foto();
    $first = 1;
    while($serial->get_foto()->get_next_radek()){
        if($first){
            $first = 0;
            echo $serial->get_foto()->show_list_item("nahled");
        }else{
            echo $serial->get_foto()->show_list_item("dalsi_foto");
        }
    }
    echo "<tr><td colspan=\"2\"><h4><b><em>Typy pobyt� - ".$ubytovani_data["nazev"]."</em></b></h4>";
    $menu = new Menu_katalog("dotaz_serialy_ubytovani", $_GET["typ"], "", "", 20, "", "", $_GET["id_ubytovani"]);
    echo $menu->show_serial("list");
} 
          ?>
</table>

</div>
  </div>
 
      
 <?php
 if($pocet_zajezdu >=8 ){         
 ?>
      <div class="akce">
          
          <h3>DOPORU�UJEME</h3>
<?php

$pocet_doporuceni = min( array( 6, floor(($pocet_zajezdu/4)) ) );

$doporucujeme = new Serial_list($_GET["typ"],  $_GET["zeme"], $_GET["text"], $nazev_ubytovani, $_GET["id_destinace"], $_GET["zajezd"], $_GET["termin_od"],$_GET["termin_do"],$_GET["str"],"random",$pocet_doporuceni,"recomended");
$k=0;
echo "<table><tr>";
while ($doporucujeme->get_next_radek_recommended()) {
    echo "<td>";
    echo $doporucujeme->show_list_item("doporucujeme_list_left");
    $k++;
    if($k % 2 == 0){
        echo "<tr>";
    }
}
echo "</table>";
?>
      </div>      
      <?php
        if($_GET["destinace"]){
            require_once "./classes/informace_destinace.inc.php"; //seznam informaci katalogu
            require "./includes/informace_destinace.inc.php";	
        }else if($_GET["zeme"]){
            require_once "./classes/informace_zeme.inc.php"; //seznam informaci katalogu
            require "./includes/informace_zeme.inc.php";
        }
      ?>
  <?php
 }
  ?>    
      
  <div class="kontakt" style="margin-top:10px;">
          <h3>KONTAKTN� INFORMACE</h3>
          <a href="https://www.slantour.cz"><img style="border:none;" src="/img/slantour_logo.gif" class="fright" alt="Logo CK SLAN tour"/></a>
          <p>Web slantour.cz provozuje <b>SLAN tour s.r.o.</b></p>          
          <p style="padding: 0 2px 0 10px; margin-top:0; color: #191970;">
                                         	tel.: (+ 420) 312522702<br/>
                                		mob.: (+ 420) 604255018<br/>
				e-mail: info@slantour.cz<br/>
                                web: <a href="https://www.slantour.cz">www.slantour.cz</a><br/></p>
          <a  href="/o-nas.html" title="V�echny kontakty - CK SLAN tour">kompletn� kontakty</a>
  </div> 
       
  <div style="margin-top:10px;">	
<a href="http://www.goparking.cz/rezervace/krok1/?promo=SLAN790" title="Nab�dka v�hodn�ho a bezpe�n�ho parkov�n� pro na�e klienty p��mo u leti�t� Praha - Ruzyn�"><img style="border: 1px solid black;" src="https://www.slantour.cz/pix/go210x75.jpg" alt="Parkov�n� na leti�ti" width="300"></a>
  </div>  
 
        
<div class="akce" style="margin-top:10px;">
          <h3>POJI�T�N�</h3>          
		Cestovn� kancel��  SLAN tour s.r.o. je poji�t�na dle z�kona <I>159/1999 Sb.</I> pro p��pad insolvence CK u <b>poji��ovny UNIQA a. s.</b><br /><br />
		CK SLAN tour je �lenem odborn�ho profesn�ho sdru�en� <b>Albatros</b>        
        </div>   
  
    </div>      
        
   
            

  <div class="column2-unit-right"> 

<?php
 echo "<h1>".$ubytovani_data["nazev"]."</h1>";

 echo $serial->show_popisek();

 echo $serial->show_popis();

 echo $serial->show_poznamka();
 
 echo "<h3 class=\"plain_text\"><b>P�EHLED Z�JEZD� A TERM�N�</b></h3>";
?>      
      

          <?php

while ($serial_list->get_next_radek()) {
    echo $serial_list->show_list_item("serial_zajezd_list_ubytovani");
}
echo "</table>
     </div>";
     
     
     echo $serial->show_map(); 
?>


        </div>
      
      
 </div>
  
 
 <hr class="clear-contentunit" />   
 

	 
	 
      </div>

      
    <!-- C. FOOTER AREA -->      

    <div class="footer">
   <? include_once "./includes/pata.inc.php"; ?>     
  	 </div> 
  
</body>
</html>
