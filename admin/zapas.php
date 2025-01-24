<?
/**     \file
* serial.php  - administrace seriálù + zájezdù + cen
*				- pridavani zemí, fotek, dokumentù a informací k jednotlivým serialu
*				- seznam rezervací pro zájezd/seriál (odkaz do rezervací)
*	@param $typ = typ pozadavku
*	@param $pozadavek = upresneni pozadavku
*	@param $id_serial = id objednávky
*	@param $id_zajezd = id zájezdu
*	@param $id_cena = id služby seriálu
*	@param $id_zeme = id zeme
*	@param $id_destinace = id destinace
*	@param $id_foto = id zájezdu
*	@param $id_dokument = id dokumentu
*	@param $id_informace = id informace
*/

//spusteni prace se sessions
	session_start(); 
	
//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php"; 

require_once "./classes/zapas_list.inc.php"; //seznamy serialu
require_once "./classes/zapas.inc.php"; //detail seriálu
require_once "./classes/zapas_foto.inc.php"; //detail seriálu
require_once "./classes/foto_list.inc.php"; //seznamy fotografii
require_once "./classes/zeme_list.inc.php"; //seznamy fotografii
/*
//pripojeni k databazi
$database = new Database();

//spusteni prace se sessions
	session_start(); 
	
//vytvori do pormenne $zamestnanec instanci tridy User_zamestnanec na zaklade prihlaseni v $_POST nebo $_SESSION
	require_once "./includes/set_user.inc.php";
	*/
	
	
/*--------------	POZADAVKY DO DATABAZE	-------------------------*/
//nactu informace o prihlasenem uzivateli
$zamestnanec = User_zamestnanec::get_instance();

if($zamestnanec->get_correct_login()){
//obslouzim pozadavky do databaze - s automatickym reloadem stranky		
//podle jednotlivych typu objektu
//promenna adress obsahuje pozadavek na reload stranky (adresu)	
$adress="";
/*---------------------serial_list ---------------*/
	if($_GET["typ"]=="zapas_list"){
			//zmenime filtry ulozene v sessions
			if($_GET["pozadavek"]=="change_filter"){
				
				//kontrola vstupu je provadena pri volani konstruktoru tøidy serial_list
				//filtry menime bud formularem (typ, podtyp, nazev) nebo odkazem (order by)
				if($_GET["pole"]=="typ-podtyp-nazev"){
					$_SESSION["zapas_nazev"]=$_POST["nazev"];
					$_SESSION["zapas_zeme"]=$_POST["zeme"];
					
				}else if($_GET["pole"]=="ord_by"){
					$_SESSION["zapas_ord_by"]=$_GET["ord_by"];
				}
				$adress = $_SERVER['SCRIPT_NAME']."?typ=zapas_list&moznosti_editace=".$_GET["moznosti_editace"]."";
			}

/*---------------------serial---------------*/
	}else if($_GET["typ"]=="zapas"){
	
			if($_GET["pozadavek"]=="copy"){							
				$dotaz = new Zapas("copy",$zamestnanec->get_id(),$_GET["id_zapas"],$_POST["nazev"]);
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zapas_list";
					//potvrzovaci hlaska
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{
					//chybova hlaska
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}
								
			}else if($_GET["pozadavek"]=="create"){		
				//insert do tabulky seriálù		
				$database = Database::get_instance();	
					
				$dotaz = new Zapas("create",$zamestnanec->get_id(),"",$_POST["nazev"],$_POST["datum"],$_POST["popis"],$_POST["nazev_en"],$_POST["popis_en"]);
				
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zapas_list";
					//potvrzovaci hlaska
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{
					//chybova hlaska
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}
					
		
				
			}else if($_GET["pozadavek"]=="update"){
				$dotaz = new Zapas("update",$zamestnanec->get_id(),$_GET["id_zapas"],$_POST["nazev"],$_POST["datum"],$_POST["popis"],$_POST["nazev_en"],$_POST["popis_en"]);
				//pokud vse probehlo spravne, vypisu OK hlasku	
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zapas_list";
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}						

				
			}else if($_GET["pozadavek"]=="delete"){
				$dotaz = new Zapas("delete",$zamestnanec->get_id(),$_GET["id_zapas"]);
				$adress = $_SERVER['SCRIPT_NAME']."?typ=zapas_list";
				//pokud vse probehlo spravne, vypisu OK hlasku	
				if( !$dotaz->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}						
			}
        }else if($_GET["typ"]=="foto_list"){
			if($_GET["pozadavek"]=="change_filter"){

				//rozdeleni pole zeme:destinace na id_zeme a id_destinace
				if($_POST["zeme-destinace"]!=""){
						//vstup je ve tvaru zeme:destinace
						$typ_array=explode(":", $_POST["zeme-destinace"]);
						$id_zeme=$typ_array[0];
						$id_destinace=$typ_array[1];
				}else{
						$id_zeme=""; $id_destinace="";
				}
				//kontrola vstupu je provadena pri volani konstruktoru tøidy foto_list
				//filtry menime bud formularem (zeme,destinace, nazev) nebo odkazem (order by)
				if($_GET["pole"]=="zeme-destinace-nazev"){
					$_SESSION["zeme"]=$id_zeme;
					$_SESSION["destinace"]=$id_destinace;
					$_SESSION["nazev_foto"]=$_POST["nazev_foto"];

				}else if($_GET["pole"]=="ord_by"){
					$_SESSION["foto_order_by"]=$_GET["ord_by"];
				}

				$adress = $_SERVER['SCRIPT_NAME']."?typ=foto&id_serial=".$_GET["id_serial"]."";
			}
	}else if($_GET["typ"]=="foto"){
			if($_GET["pozadavek"]=="create"){
				$dotaz = new Foto_zapas("create",$zamestnanec->get_id(),$_GET["id_zapas"],$_GET["id_foto"],$_GET["zakladni_foto"])	;
				//vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location
				$adress = $_SERVER['SCRIPT_NAME']."?typ=foto&id_zapas=".$_GET["id_zapas"]."";
				//pokud vse probehlo spravne, vypisu OK hlasku
				if( !$dotaz->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}

			}else if($_GET["pozadavek"]=="update"){
				$dotaz = new Foto_zapas("update",$zamestnanec->get_id(),$_GET["id_zapas"],$_GET["id_foto"],$_GET["zakladni_foto"])	;
				//vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location
				$adress = $_SERVER['SCRIPT_NAME']."?typ=foto&id_zapas=".$_GET["id_zapas"]."";
				//pokud vse probehlo spravne, vypisu OK hlasku
				if( !$dotaz->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}

			}else if($_GET["pozadavek"]=="delete"){
				$dotaz = new Foto_zapas("delete",$zamestnanec->get_id(),$_GET["id_zapas"],$_GET["id_foto"]);
				//vytvorime adresu dalsi stranku(spolecna pro vsechny typy editace fotek) - automaticky nactenou pres http location
				$adress = $_SERVER['SCRIPT_NAME']."?typ=foto&id_zapas=".$_GET["id_zapas"]."";
				//pokud vse probehlo spravne, vypisu OK hlasku
				if( !$dotaz->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}
			}

				
	}//if-else typ editace

}//if zamestnanec->correct_login

//pokud byl nejaky pozadavek na reload stranky, tak ho provedu
	if($adress){
			header("Location: https://".$_SERVER['SERVER_NAME'].$adress);
			exit; 		
	}

//zpracovani hlasky poslane z minule stranky (jsme za headerem pro presmerovani)	
	if($_SESSION["hlaska"]!=""){
		$hlaska_k_vypsani = $_SESSION["hlaska"];
		$_SESSION["hlaska"] = "";
	}else{
		$hlaska_k_vypsani = "";
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<?
	$core = Core::get_instance();
		echo  "<title>".$core->show_nazev_modulu()." | Administrace systému RSCK</title>";
?>

	<meta http-equiv="Content-Type" content="text/html; charset=windows-1250"/> 		
	<meta name="copyright" content="&copy; Slantour"/>
	<meta http-equiv= "pragma" content="no-cache" />
	<meta name="robots" content="noindex,noFOLLOW" />

	<link rel="stylesheet" type="text/css" href="styly.css" media="all"/>
</head>
<body>
<h1>Administrace systému RSCK</h1>
<?
//vypisu pripadne hlasky o uspechu operaci
	echo $hlaska_k_vypsani;

if($zamestnanec->get_correct_login()){
//prihlaseni probehlo vporadku, muzu pokracovat
	//zobrazim informace o prihlasenem uzivateli
	echo $zamestnanec->show_info_about_user();
	
	//zobrazeni hlavniho menu
	echo $zamestnanec->show_main_menu();
	
	//zobrazeni aktualnich informaci - nove rezervace, pozadavky...
	?>
		<div class="main">
	<?
		echo "<h2>Modul ".$core->show_nazev_modulu()."</h2>";
	
	/*
		nejprve zjistim v jake objekty budu obsluhovat 
			-(serial, zajezd, cena, cena_zajezdu, foto, dokument, informace)
	*/
	//na zacatku zobrazim seznam serialu
	if($_GET["typ"]==""){$_GET["typ"]="zapas_list";}
	
/*----------------	seznam seriálù -----------*/	
	if($_GET["typ"]=="zapas_list"){

		//pokud nemam strankovani, zacnu nazacatku:)
		if($_GET["str"]==""){$_GET["str"]="0";}
		//vypisu menu
		?>
			<div class="submenu">
			<a href="?typ=zapas&amp;pozadavek=new">vytvoøit nový zápas</a>
			</div>
			<?
				//vytvorime instanci serial_list
					
					$serial_list = new Zapas_list($_SESSION["zapas_typ"],$_SESSION["zapas_podtyp"],$_SESSION["zapas_nazev"],$_SESSION["zapas_zeme"],$_GET["str"],$_SESSION["zapas_ord_by"],$_GET["moznosti_editace"]);
				//pokud nastala nejaka chyba, vypiseme chybovou hlasku...
					echo $serial_list->get_error_message();		
				//zobrazim filtry	
					echo $serial_list->show_filtr();
					
				if(!$serial_list->get_error_message() ){	
					
					//nadpis seznamu
					echo $serial_list->show_header();					
					//zobrazim hlavicku vypisu serialu		
					echo $serial_list->show_list_header();
					
					//vypis jednotlivych serialu
					while($serial_list->get_next_radek()){
						echo $serial_list->show_list_item("tabulka");
					}
					?>
					</table>
					<?
					//vypisu odkazy dalsi stranky
					echo $serial_list->show_strankovani();
				}

/*----------------	nový seriál -----------*/	
	}else if($_GET["typ"]=="zapas" and ($_GET["pozadavek"]=="new" or $_GET["pozadavek"]=="create") ){

		?>
			<div class="submenu">
			<a href="?typ=ubytovani_list">&lt;&lt; seznam ubytování</a>
			</div>
			
			<script>
				function otevrit(url){
					win = window.open(''+url+'','_blank','height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
				}

			</script>
			<div class="copypaste" style="position:absolute;top:220px;left:650px;font-weight:bold;font-size:1.4em;color:red;">
			<a href="copypaste.html" title="zobrazí v novém oknì pole pro kopírování HTML znaèek" target="_blank" onclick="otevrit('copypaste.html');return false;" style="color:red;">ZOBRAZIT POLE PRO KOPÍROVÁNÍ</a>
			</div>			
		<?
                $serial = new Zapas("new",$zamestnanec->get_id(),"",$_POST["nazev"],$_POST["datum"],$_POST["popis"],$_POST["nazev_en"],$_POST["popis_en"],$_GET["pozadavek"]);
		//zobrazim formular pro editaci/vytvoreni noveho serialu
		echo $serial->get_error_message();
		?><h3>Vytvoøit nový zápas</h3><?
		echo $serial->show_form();		
			
	}else if($_GET["id_zapas"]){
	//nejaky serial uz mam vybrany, vypisu moznosti editace a dal zjistim co s nim chci delat	
		
		//vypisu menu
		?>
			<div class="submenu">
			<a href="?typ=zapas_list">&lt;&lt; seznam zápasù</a> |
			<a href="?typ=zapas&amp;pozadavek=new">vytvoøit nový zápas</a>
			<br/>
		<?		
		
		
		//podle typu pozadvku vytvorim instanci tridy serial - bud serial edituju, nebo pouze zobrazim menu serialu
		if($_GET["typ"]=="zapas" and ($_GET["pozadavek"]=="edit" or $_GET["pozadavek"]=="update") ){
			$serial = new Zapas("edit",$zamestnanec->get_id(),$_GET["id_zapas"],$_POST["nazev"],$_POST["datum"],$_POST["popis"],$_POST["nazev_en"],$_POST["popis_en"],$_GET["pozadavek"]);
		
		?>
			<script>
				function otevrit(url){
					win = window.open(''+url+'','_blank','height=350,width=450,top=50,left=550,toolbar=no,minimize=no,status=no,resizable=yes,menubar=no,location=no,scrollbars=no');
				}

			</script>
			<div class="copypaste" style="position:absolute;top:220px;left:650px;font-weight:bold;font-size:1.4em;color:red;">
			<a href="copypaste.html" title="zobrazí v novém oknì pole pro kopírování HTML znaèek" target="_blank" onclick="otevrit('copypaste.html');return false;" style="color:red;">ZOBRAZIT POLE PRO KOPÍROVÁNÍ</a>
			</div>	
		<?			
		}else{
			$serial = new Zapas("show",$zamestnanec->get_id(),$_GET["id_zapas"]);
		}
		
		
			echo $serial->get_error_message();
			//vypisu moznosti editace pro dany serial (pokud vytvarim novy, nejsou zadne - serial jeste neexistuje)
			echo $serial->show_submenu();		
		?>
			</div>
		<?
		
	/*----------------	editace  seriálu -----------*/		
		if($_GET["typ"]=="zapas" and ($_GET["pozadavek"]=="edit" or $_GET["pozadavek"]=="update") ){
			?><h3>Editace zápasu</h3><?
			//zobrazim formular pro editaci/vytvoreni noveho serialu
			echo $serial->show_form();

	/*----------------	vytvoøení cen seriálu -----------*/		
		
	/*----------------	editace  fotografií -----------*/		
		}else if($_GET["typ"]=="foto"){
		/*
			u fotografii zobrazuju aktuálnì pøipojené fotografie
			a seznam fotografií, které lze pøipojit (stránkovaný s filtry výbìru)
		*/
                    
			//seznam fotografii pripojenych k serialu
			$current_foto = new Foto_zapas("show",$zamestnanec->get_id(),$_GET["id_zapas"]);
			echo $current_foto->get_error_message();
			?>
				<h3>Fotografie pøiøazené k zapasu</h3>
			<?
			echo $current_foto->show_list_header();
			while($current_foto->get_next_radek()){
					echo $current_foto->show_list_item("tabulka");
			}
			?>
				</table>
			<?
                       
			if($_GET["str"]==""){$_GET["str"]=0;}
                       
			//seznam fotografii - parametry id_zeme, id_destinace, cast nazvu fotky, pocatek vypisu a pocet zaznamu(default. nastaveny)
			if($_SESSION["zeme"] == "" and $_SESSION["destinace"]==""){
                            
				//defaultne nastaveny filtr na fotky pouze ze zeme serialu
				$foto_list = new Foto_list($zamestnanec->get_id(),"","",$_SESSION["nazev_foto"],$_GET["str"],$_SESSION["foto_order_by"]);
			}else{
                          
				$foto_list = new Foto_list($zamestnanec->get_id(),$_SESSION["zeme"],$_SESSION["destinace"],$_SESSION["nazev_foto"],$_GET["str"],$_SESSION["foto_order_by"]);

			}
				echo $foto_list->get_error_message();
				echo $foto_list->show_filtr();
			?>
				<h3>Seznam fotografií</h3>
			<?
			echo $foto_list->show_list_header();
			//zobrazeni jednotlivych zaznamu
			while($foto_list->get_next_radek()){
					echo $foto_list->show_list_item("tabulka_zapas");
			}
			?>
				</table>
			<?
			//zobrazeni strankovani
			echo $foto_list->show_strankovani();
	/*----------------	editace  dokumentù -----------*/
		}
		
	} //if($_GET["id_serial"])
	
	//zobrazeni napovedy k modulu
	$core = Core::get_instance();
		echo "<h2 class=\"napoveda\">Nápovìda k modulu</h2>";
		echo  "<p class=\"napoveda\">".$core->show_napoveda()."</p>";
	
	?>
		</div>
	<?
	
}else{
//zadny uzivatel neni prihlasen, vypisu logovaci formular
	echo $zamestnanec->get_error_message(); //vypisu pripadnou chybovou hlasku
	echo $zamestnanec->show_login_form(); //vypisu formular pro prihlaseni

}
?>

</body>
</html>