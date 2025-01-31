<?php
//component start
require_once("./component/public/ComponentCore.php");
ComponentCore::loadCore();

session_start();
require_once "./core/load_core.inc.php"; 



require_once "./classes/menu.inc.php"; //seznam serialu
require_once "./classes/serial_lists.inc.php"; //seznam serialu
require_once "./classes/destinace_list.inc.php"; //menu katalogu

//zpracovani hlasky (jsme za headerem pro presmerovani)	
	if($_SESSION["hlaska"]!=""){
		$hlaska_k_vypsani = $_SESSION["hlaska"];
		$_SESSION["hlaska"] = "";
	}else{
		$hlaska_k_vypsani = "";
	}	
	



     $_SESSION["termin_od"]="";
     $_SESSION["termin_do"]="";
 
        if($_GET["typ"]){
            $nazev_typu = Serial_list::get_name_from_typ($_GET["typ"]).", ";
        }   else {
            $nazev_typu = "";
        } 
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
SLAN tour | přihlášení pro cestovní agentury
</title>  
  <meta http-equiv="cache-control" content="no-cache" />

<meta http-equiv="Content-Type" content="text/html; charset=windows-1250" />
<meta name="Keywords" content="dovolená, poznávací, zájezdy, lázně, lyžování, "/>
<meta name="Description" content="SLAN tour: Poznávací zájezdy  (Francie, Německo, Evropa, Mexiko, Indie, Rakousko, Itálie), Dovolená u moře (Chorvatsko, Francie, Španělsko, Mexiko - Cancun, Bali, SAE), 
 Lyzování ve Francii, lázně v Čechách, Moravě, Slovensku a Maďarsku, pobyty na horách i u vody, ZOH  Korea, Premier League, Formule 1, tenis - vstupenky a zájezdy." />

<meta name="Robots" content="index, follow"/>

  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_setup.css" />
  <link rel="stylesheet" type="text/css" media="screen,projection,print" href="/css/layout1_text.css" />
  <link rel="shortcut icon" href="favicon.ico" />
  <!--[if lt IE 9]>
  	<script type="text/javascript" src="/js/html5.js"></script>
  <![endif]-->   		
  <link type="text/css" href="/jqueryui/css/ui-lightness/jquery-ui-1.8.18.custom.css" rel="stylesheet" />

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
                                        dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pa', 'So'],
                                        monthNames:  ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
                                        yearRange:    'c-12:c+2',
                                        firstDay: 1
				});
                                $('#termin_do').datepicker({
					inline: true,
                                        dateFormat: 'dd.mm.yy',
                                        dayNamesMin: ['Ne', 'Po', 'Út', 'St', 'Čt', 'Pa', 'So'],
                                        monthNames:  ['Leden', 'Únor', 'Březen', 'Duben', 'Květen', 'Červen', 'Červenec', 'Srpen', 'Září', 'Říjen', 'Listopad', 'Prosinec'],
                                        yearRange:    'c-12:c+2',
                                        firstDay: 1


				});
                                });                    
  </script>
<?php
//component start
    ComponentCore::loadJavaScripts(0);
//component end
?>                
</head>

<!-- Global IE fix to avoid layout crash when single word size wider than column width -->
<!--[if IE]><style type="text/css"> body {word-wrap: break-word;}</style><![endif]-->

<body>
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
        

  <div class="column2-unit-left" >        
      <div class="zeme">
          <h3>KATALOG ZÁJEZDŮ A POBYTŮ</h3>
          

<table width="298">
 <tr>
          <?php
                $menu = new Menu_katalog("dotaz_typy","", "", "");
                echo $menu->show_typy_pobytu();
          ?>
</table>

          
<table width="298">
    <tr><td colspan="4"><b>Nejžádanější země</b></td>
    </tr><tr>    
          <?php
                $menu2 = new Menu_katalog("dotaz_top_zeme","", "", "");
                echo $menu2->show_top_zeme();
          ?>     
</table>     
<table width="298">
    <tr><td colspan="4"><b>Nejzajímavější sportovní akce</b></td>
    </tr><tr>    
          <?php
                $menu3 = new Menu_katalog("dotaz_top_sporty","", "", "");
                echo $menu3->show_top_sporty();
          ?>     
</table>  
          
  </div>
      
  <div class="kontakt" style="margin-top:10px;">
          <h3>KONTAKTNÍ INFORMACE</h3>
          <a href="https://www.slantour.cz"><img style="border:none;" src="/img/slantour_logo.gif" class="fright" alt="Logo CK SLAN tour"/></a>
          <p>Web slantour.cz provozuje <b>SLAN tour s.r.o.</b></p>          
          <p style="padding: 0 2px 0 10px; margin-top:0; color: #191970;">
                                         	tel.: (+ 420) 312522702<br/>
                                		mob.: (+ 420) 604255018<br/>
				e-mail: info@slantour.cz<br/>
                                web: <a href="https://www.slantour.cz">www.slantour.cz</a><br/></p>
          <a  href="/o-nas.html" title="Všechny kontakty - CK SLAN tour">kompletní kontakty</a>
  </div> 
       
  <div style="margin-top:10px;">	
<a href="http://www.goparking.cz/rezervace/krok1/?promo=SLAN790" title="Nabídka výhodného a bezpečného parkování pro naše klienty přímo u letiště Praha - Ruzyně"><img style="border: 1px solid black;" src="https://www.slantour.cz/pix/go210x75.jpg" alt="Parkování na letišti" width="300"></a>
  </div>  
 
        
<div class="akce" style="margin-top:10px;">
          <h3>POJIŠTĚNÍ</h3>          
		Cestovní kancelář  SLAN tour s.r.o. je pojištěna dle zákona <I>159/1999 Sb.</I> pro případ insolvence CK u <b>pojišťovny UNIQA a. s.</b><br /><br />
		CK SLAN tour je členem odborného profesního sdružení <b>Albatros</b>        
        </div>   
  
    </div>      
        
   
            

        
        
  <div class="column2-unit-right"> 

      <div class="kontakt" style="margin-bottom:10px;" >
          <h3>PŘIHLÁŠENÍ DO SYSTÉMU PRO AGENTURY</h3>
          <?php
$core = Core::get_instance();
$id_registrace = $core->get_id_modul_from_typ("registrace");
if($id_registrace !== false){
	$uzivatel = User::get_instance();	
	
	//pokud je uzivatel prihlasen, vypiseme uzivatelske menu, jinak formular pro prihlaseni
	if( $uzivatel->get_correct_login() ){
		echo $uzivatel->show_klient_menu();
	}else{
		//echo $uzivatel->show_login_form();
		echo $uzivatel->show_login_form();
	}
}
?>
        </div> 
      
      <div class="akce">
          <h3>Registrace a přihlášení do systému agentur CK SLAN tour</h3>
			Vítejte v přihlašování pro cestovní agentury do systému ALBATROS 3000. V následujícím textu je shrnuto několik základních informací o fungování systému, proto doporučujeme, abyste si ho přečetli ještě před první registrací.
<h3>REGISTRACE</h3>
Pro vstup do našeho on-line rezervačního systému RISCK je nutná registrace.<br/>
Přihlašovací údaje (přihlašovací jméno a heslo) vám zašleme na vyžádání. Stačí zaslat jednoduchý email na naši centrálu, nejlépe na info@slantour.cz .

<br/>
<h3>PŘIHLÁŠENÍ DO SYSTÉMU</h3>
Poté co od nás obdržíte přihlašovací jméno a heslo je již vstup do rezervačního systému snadný: <br/>
Na webových stránkách CK SLAN tour klikněte na odkaz "přihlášení pro CA" vpravo nahoře pod logem CK SLAN tour a na následující stránce vyplňte vaše uživatelské jméno a heslo.<br/>
Po přihlášení do systému se v pravém sloupci zobrazí uživatelské menu Vaší CA, kde můžete zobrazit vaše již existující objednávky, nebo případně změnit údaje o CA.<br/>
<br/>
<h3>OBJEDNÁVKA ZÁJEZDU</h3>
Před objednávkou zájezdu je třeba se do systému přihlásit, jinak sice objednávka proběhne, ale nezaznamená se, že ji podala vaše agentura (pokud se zapomenete přihlásit -> sekce Řešení problémů).<br/>
Objednávka zájezdu probíhá stejně jako u objednávky zájezdu "normálním" klientem: vyhledáte požadovaný zájezd, vyplníte informace o klientovi - objednavateli, požadované služby a jména dalších osob.<br/>
<br/>
<h3>SEZNAM OBJEDNÁVEK</h3>
Každá Vaše objednávka se zobrazí v seznamu objednávek dostupného z uživatelského menu - <strong>"Moje Objednávky"</strong>. <br/>
Každou objednávku ze seznamu je možné rozkliknout a zjistit o ní detailní informace, nebo zobrazit PDF dokument s předvyplněnou <strong>cestovní smlouvou</strong>.<br/>
<br/>
<h3>PDF CESTOVNÍ SMLOUVA</h3>
Cestovní smlouvu ke každé Vámi vyplněné objednávce můžete vygenerovat kliknutím na odkaz "PDF cestovní smlouva" buď ze seznamu objednávek, nebo přímo u detailu objednávky.<br/>
<br/>
<h3>MOŽNOSTI A VÝHODY SYSTÉMU ALBATROS 3000</h3>
- Aktuální <strong>stav obsazenosti zájezdů</strong>: Ihned vidíte, zda je vybraný zájezd dostupný nebo vyprodaný, bez nutnosti telefonicky ověřovat kapacitu.<br/>
- Snadné <strong>vyplnění objednávky po internetu</strong><br/>
- Snadná a rychlá elektronická komunikace s naší centrálou<br/>
- Přehled a <strong>kontrola objednávek</strong> on-line v uživatelské sekci<br/>
- On-line dostupná <strong>cestovní smlouva</strong> ke každé objednávce<br/>
- Možnost vystavení <strong>faktur na provizi</strong> přímo z našeho systému. Bez dalšího vyplňování jakýchkoliv údajů<br/>
<br/>
<h3>ŘEŠENÍ PROBLÉMŮ</h3>
Typické problémy se kterými se můžete setkat a jejich řešení:<br/>
<strong><em>Vyplnil jsem objednávku a zjistil jsem, že nejsem přihlášený / nemohu najít uživatelské menu vpravo / objednávku v seznamu objednávek nevidím</em></strong><br/>
Buď jste se zapoměli přihlásit, nebo vás systém po příliš dlouhé nečinnosti (30 minut) automaticky odhlásil.<br/>
Objednávka byla pravděpodobně odeslána, ale nebylo zaznamenáno, že je vyplněna Vaší agenturou, kontaktujte nás prosím e-mailem se Jménem klienta, objednávaným zájezdem a Vaším uživatelským jménem a my zkontrolujeme zda byla objednávka vytvořena a přidáme informaci o Vaší CA.
 <br/> <br/>
 <strong><em>Klient v okamžiku objednávky nevěděl všechny potřebné údaje, nyní bych je chtěl doplnit</em></strong><br/>
 Do budoucna připravujeme formulář pro doplnění objednávky, nyní nám prosím pošlete e-mail s identifikací objednávky a doplněnými údaji.
 <br/> <br/>

 <br/> <br/> 
      </div>
    
            
      
      
 </div>
       
 <hr class="clear-contentunit" />  
 <div class="column1-unit">
           <h3>DOPORUČUJEME</h3>
<?php
$doporucujeme = new Serial_list($_GET["typ"], $zeme_text, $_GET["id_serial"], $nazev_ubytovani, $_GET["destinace"], $_GET["zajezd"], "","",$_GET["str"],"random",12,"recomended");
$k=0;
echo "<table><tr>";
while ($doporucujeme->get_next_radek_recommended()) {
    echo "<td class=\"round\">";
    echo $doporucujeme->show_list_item("doporucujeme_list");
    $k++;
    if($k % 6 == 0){
        echo "<tr>";
    }
}
echo "</table>";
?>

 </div>  
 
 <hr class="clear-contentunit" />   
 
        <!-- Content unit - One column -->
        <div class="column1-unit">
                           
          <p>
              <?php
                $ubytovani_vahy = new Serial_list($_GET["typ"], "", "", "", "", "", "", "", 0, "nazev", 100,"select_vahy");
                while ($ubytovani_vahy->get_next_radek()) {
                    echo $ubytovani_vahy->show_list_item("serial_vahy");
                    $k++;
                }
              ?>
          </p>
        </div>          
        <hr class="clear-contentunit" />
	 
	 </div>
      </div>

      
    <!-- C. FOOTER AREA -->      

    <div class="footer">
   <?php include_once "./includes/pata.inc.php"; ?>     
  	 </div> 
  
    
    <?php
mysqli_close($GLOBALS["core"]->database->db_spojeni);
?>
</body>
</html>
