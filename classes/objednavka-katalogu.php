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
Objednávka katalogů | SLAN tour | Poznávací zájezdy 2020, Lázně, Dovolená u moře léto 2019, fotbal, Formule 1, ATP Masters, olympiáda, biatlon
</title>  
  <meta http-equiv="cache-control" content="no-cache" />

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="Keywords" content="objednávka katalogů, slan tour, dovolená, poznávací, zájezdy, lázně, lyžování, 2019"/>
<meta name="Description" content="SLAN tour: objednávka katalogů; Poznávací zájezdy 2020 (Francie, Německo, Evropa, Mexiko, Indie, Rakousko, Itálie, Nepál, Namibie), Dovolená u moře (Chorvatsko, Francie, Španělsko, Mexiko - Cancun, Bali, SAE), 
 , lázně v Čechách, Moravě, Slovensku a Maďarsku, pobyty na horách i u vody,  2019, 2020, Premier League, Formule 1, tenis - vstupenky a zájezdy." />

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
<script language="javascript" type="text/javascript">
<!--
/**
 *
 * @access public
 * @return void
 **/
function checkform(katalog){
	var hlaska = "";
	if(document.form1.jmeno.value == ""){
		hlaska = hlaska + "Je třeba vyplnit Vaše jméno!\n"
		var wrong = 1;
	}
	if(document.form1.prijmeni.value == ""){
		hlaska = hlaska + "Je třeba vyplnit Vaše příjmení!\n"
		var wrong = 1;
	}
	if(document.form1.email.value == ""){
		hlaska = hlaska + "Je třeba vyplnit Váš e-mail!\n"
		var wrong = 1;
	}
	if(document.form1.mesto.value == ""){
		hlaska = hlaska + "Je třeba vyplnit Vaši adresu - město!\n"
		var wrong = 1;
	}			
	if(document.form1.ulice.value == ""){
		hlaska = hlaska + "Je třeba vyplnit Vaši adresu - ulici!\n"
		var wrong = 1;
	}			
	if(document.form1.psc.value == ""){
		hlaska = hlaska + "Je třeba vyplnit Vaši adresu - PSČ!\n"
		var wrong = 1;
	}	
	if(document.form1.kod.value == ""){
		hlaska = hlaska + "Je třeba vyplnit SMS kód!\n"
		var wrong = 1;
	}		
	if(katalog == false){
		hlaska = hlaska + "Je třeba vybrat alespoň jeden katalog!\n"
		var wrong = 1;
	}		
	if(wrong == 1){
	return hlaska;
	}
	return "";

}
function odeslano()
{
var katalog = "";
var vybran = false;
var i=0;
for (i=0; i < document.form1.katalog.length; i++)
  {
  if (document.form1.katalog[i].checked) {
  	katalog = katalog + document.form1.katalog[i].value + "\n<br/>";
	vybran=true;
	}
  }
jmeno="<tr><td>jméno:<\/td><td>" + document.form1.jmeno.value + "<\/td><\/tr>\n";
prijmeni="<tr><td>příjmení:<\/td><td>" + document.form1.prijmeni.value + "<\/td><\/tr>\n";
email="<tr><td>email:<\/td><td>" + document.form1.email.value + "<\/td><\/tr>\n";
ulice="<tr><td>ulice:<\/td><td>" + document.form1.ulice.value + "<\/td><\/tr>\n";
mesto="<tr><td>město:<\/td><td>" + document.form1.mesto.value + "<\/td><\/tr>\n";
psc="<tr><td>PSČ:<\/td><td>" + document.form1.psc.value + "<\/td><\/tr>\n";
kod="<tr><td>SMS kód:<\/td><td>" + document.form1.kod.value + "<\/td><\/tr>\n";


document.form1.kat.value = "<table border=0>" + jmeno + prijmeni + email + ulice + mesto + psc +  kod + "<\/table>\n<br \/> katalogy:\n<br \/>" + katalog;
var hlaskaForm = checkform(vybran);
if(hlaskaForm == "" ){
	return true;
}else{
	window.alert(hlaskaForm);
	return false;
}
return vybran;
}

// -->
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
        
        <!-- Pagetitle 
        <h1 class="pagetitle">Lázně a termální lázně</h1>-->
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
     <?php include_once "./includes/kontakty.inc.php"; ?>             
        </div>
      
</div>
  <div class="column2-unit-right"> 
       <div class="akce">
           
           
<h3>Objednávka katalogů CK SLAN tour</h3>


<form action="objednavka-katalog.php" method="post" name="form1" onsubmit="return odeslano();">
      <table align="center" cellpadding="1" cellspacing="0" width="100%">
        
        <tr>
          <th colspan="2">
           Objednané katalogy Vám zašleme poštou během několika dnů
          </th>
        </tr>
        <tr>
           <td  valign="top">   
                
                              <table align="left" border="0"   class="round_light">

                                <tr>
                                  <td>Jméno</td>
                                  <td><input name="jmeno" size="20" /></td>
                                </tr>
                                <tr>
                                  <td>Příjmení</td>
                                  <td><input name="prijmeni" size="20" /></td>
                                </tr>
                                <tr>
                                  <td>E-mail</td>
                                  <td><input name="email" size="20" /></td>
                                </tr>
                                <tr>
                                  <td>Ulice</td>
                                  <td><input name="ulice" size="20" /></td>
                                </tr>
                                <tr>
                                  <td>Město</td>
                                  <td><input name="mesto" size="20" /></td>
                                </tr>
                                <tr>
                                  <td>PSČ</td>
                                  <td><input name="psc" size="20" /></td>
                                </tr>
                              </table>
           </td><td  valign="top">
                              <table align="left" border="0"   class="round_light">
                                <tr valign="top"><td><input type="checkbox" name="katalog" value="Dovolená u moře" /> Dovolená u moře</td><td><a href="/pdf/katalog-dum.pdf">stáhnout PDF</a></td></tr>
                                <tr valign="top"><td><input type="checkbox" name="katalog" value="Fly and Drive" /> Fly and Drive</td><td><a href="https://www.slantour.cz/pdf/katalog FlyandDrive.pdf">stáhnout PDF</a></td></tr>
                               
                                <tr valign="top"><td><input type="checkbox" name="katalog" value="Svět na dosah - poznávací" /> Svět na dosah - poznávací (evropské státy)</td><td><a href="https://issuu.com/slantour/docs/svet-na-dosah">prohlédnout  PDF</a></td></tr>
                                  <tr valign="top"><td><input type="checkbox" name="katalog" value="Vzdálené světy - poznávací" /> Vzdálené světy  - poznávací (mimoevropské státy)</td><td><a href="https://issuu.com/slantour/docs/vzdalene-svety_dcb90dc4dbc26e">prohlédnout PDF</a></td>
                               
                                <tr valign="top"><td><input type="checkbox" name="katalog" value="Lázně" /> Lázně</td><td><a href="https://issuu.com/slantour/docs/slan_katalog_lat_2017">stáhnout PDF</a></td></tr>
                                <tr valign="top"><td><input type="checkbox" name="katalog" value="Exotika" /> Exotika</td><td><a href="/pdf/katalog-exotika.pdf">stáhnout PDF</a></td></tr>    
                                <tr valign="top"><td colspan="2">
                                  <b><i>Katalogy zasíláme pouze za cenu poštovného.<br/>
                                    Jedním požadavkem je možné objednat max. 3 katalogy. <br/>
                                    Katalogy jsou zasílány pouze na adresy v České republice.
                                    </i></b>
                                    </td></tr>
                         </table>
							
                </td>
              </tr>

	
<tr>
<td valign="top" >	
    <table border="0" cellpadding="1" cellspacing="0"   class="round_light">
		<tr>
                                  <td valign="top">SMS kód *</td>
                                  <td   valign="top"><input name="kod" size="20" /></td>
										</tr>
                              <tr>
                                  <td colspan="2"><input type="hidden" name="kat" value="" />
								<input type="submit" value="Zaslat katalogy" onclick="return odeslano();" /></td>
                              </tr>	
									</table> 											
<td valign="top" >	
    
    
									<table border="0" cellpadding="5" cellspacing="5"   class="round_light">										
										<tr>
											<td  valign="top">
											<b><i><font color="#0000C0">Pro dokončení objednávky katalogů je třeba získat SMS kód. </font></i></b><br/><br/>
 SMS kód získáte zasláním SMS ve tvaru: <FONT COLOR="#0000D2">SLAN "vaše jméno"</FONT> (bez uvozovek, není nutné rozlišovat malá a velká písmena) na číslo <b><FONT COLOR="#7E0000"><FONT SIZE=4>9021130</FONT></FONT></b> . Cena jedné SMS je 30 Kč (cena pokrývá náklady na poštovné). Ihned po odeslání SMS vám přijde <U>zpět zpráva s kódem</U>, který napište do příslušného pole ve formuláři v rámečku vlevo. Po vložení SMS kódu můžete objednávku odeslat..<br/>
<br/>
<I>Cena této služby je 30 Kč vč. DPH za odeslanou SMS. Za službu odpovídá Advanced Telecom Services, s.r.o. Infolinka 776999199,  www.platmobilem.cz.</I><br/>
<br/>

Příklad tvaru  zaslané sms: <I><FONT COLOR="#00006F">slan novak</FONT></I>

											</td>										
										</tr>																		
									</table>                  
                </td>
              </tr>
</table>				  
</form>
       
       
       </div>



      
     
      
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
