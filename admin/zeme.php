<?
/**    \file
* zeme.php  - administrace zemí a destinací
*	@param $typ = typ pozadavku
*	@param $pozadavek = upresneni pozadavku
*	@param $id_zeme = id zemì
*	@param $id_destinace = id destinace
*/

//spusteni prace se sessions
	session_start(); 
	
//require_once potrebnych souboru
//nahrani potrebnych trid spolecnych pro vsechny moduly a vytvoreni instance tridy Core
require_once "./core/load_core.inc.php"; 

require_once "./classes/zeme.inc.php"; //seznamy dokumentu
require_once "./classes/zeme_list.inc.php"; //seznamy dokumentu

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

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

/*--------------------- zeme ---------------*/
	if($_GET["typ"]=="zeme_list"){
			//zmenime filtry ulozene v sessions
			if($_GET["pozadavek"]=="change_filter"){
				//je-li to treba, zaregistrujeme sessions
				if(!isset($_SESSION["zeme_order_by"])){
					session_register("zeme_order_by");
				}
				//kontrola vstupu je provadena pri volani konstruktoru tøidy zeme_list
				if($_GET["pole"]=="ord_by"){
					$_SESSION["zeme_order_by"]=$_GET["ord_by"];
				}	
				$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";				
			}	
	}else if($_GET["typ"]=="zeme"){
			if($_GET["pozadavek"]=="create"){		
				//insert do tabulky seriálù						
				$dotaz = new Zeme("create",$zamestnanec->get_id(),"","",$_POST["id_informace"],$_POST["nazev"]);	
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";					
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}						
				
			}else if($_GET["pozadavek"]=="update"){
				$dotaz = new Zeme("update",$zamestnanec->get_id(),$_GET["id_zeme"],"",$_POST["id_informace"],$_POST["nazev"]);
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}	
								
			}else if($_GET["pozadavek"]=="delete"){
				$dotaz = new Zeme("delete",$zamestnanec->get_id(),$_GET["id_zeme"]);
				//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
				$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";				
				if( !$dotaz->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}					
			}

	}else if($_GET["typ"]=="destinace"){
			if($_GET["pozadavek"]=="create"){		
				//insert do tabulky seriálù						
				$dotaz = new Zeme("create_destinace",$zamestnanec->get_id(),$_GET["id_zeme"],"",$_POST["id_informace"],$_POST["nazev"]);	
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}					
				
			}else if($_GET["pozadavek"]=="update"){
				$dotaz = new Zeme("update_destinace",$zamestnanec->get_id(),$_GET["id_zeme"],$_GET["id_destinace"],$_POST["id_informace"],$_POST["nazev"]);			
				if( !$dotaz->get_error_message() ){
					//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
					$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}					
				
			}else if($_GET["pozadavek"]=="delete"){
				$dotaz = new Zeme("delete_destinace",$zamestnanec->get_id(),$_GET["id_zeme"],$_GET["id_destinace"]);	
				//vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
				$adress = $_SERVER['SCRIPT_NAME']."?typ=zeme_list";			
				if( !$dotaz->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
				}	
			}

	}//if($_GET["typ"]==...
}
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
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,100italic,100,300,300italic,400italic,500,500italic,700,700italic&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="css/reset-min.css">
    <link rel="stylesheet" type="text/css" href="./new-menu/style.css" media="all"/>
</head>
<body>
<?
if($zamestnanec->get_correct_login()){
//prihlaseni probehlo vporadku, muzu pokracovat
    //zobrazeni hlavniho menu
    echo ModulView::showNavigation(new AdminModulHolder($core->show_all_allowed_moduls()), $zamestnanec, $core->get_id_modul());
	
	//zobrazeni aktualnich informaci - nove rezervace, pozadavky...
	?>
		<div class="main-wrapper">
		<div class="main">
	<?
    //vypisu pripadne hlasky o uspechu operaci
    echo $hlaska_k_vypsani;
	
	//na zacatku zobrazim seznam
	if($_GET["typ"]==""){$_GET["typ"]="zeme_list";}
	
/*----------------	seznam zrmí -----------*/	
	if($_GET["typ"]=="zeme_list"){

		//vypisu menu
		?>
			<div class="submenu">
			<a href="?typ=zeme&amp;pozadavek=new">vytvoøit novou zemi</a>
			</div>
			<?
			
			//seznam zemí a destinací - 
			$zeme_list = new Zeme_list($zamestnanec->get_id(),$_SESSION["zeme_order_by"]);
			?>
				<h3>Seznam zemí a destinací</h3>
			<?	
			//zobrazeni hlavicky seznamu
				echo $zeme_list->show_list_header();	
			//zobrazeni seznamu
				echo $zeme_list->show_list("tabulka_zeme");	
			?>
				</table>
			<?		
					

/*----------------	nová Zeme -----------*/	
	}else if($_GET["typ"]=="zeme"  and ($_GET["pozadavek"]=="new" or $_GET["pozadavek"]=="create") ){

		?>
			<div class="submenu">
			<a href="?typ=zeme_list">&lt;&lt; seznam zemí/destinací</a>
			</div>
		<?
			$zeme = new Zeme("new",$zamestnanec->get_id(),"","",$_POST["nazev"],$_GET["pozadavek"]);
		
			?><h3>Vytvoøit novou zemi</h3><?
			//zobrazim formular pro editaci/vytvoreni noveho dokumentu
			echo $zeme->show_form_zeme();		
	
/*----------------	editace zeme -----------*/		
	}else if($_GET["typ"]=="zeme" and ($_GET["pozadavek"]=="edit"  or $_GET["pozadavek"]=="update") ){	
		?>
			<div class="submenu">
			<a href="?">&lt;&lt; seznam zemí/destinací</a>
			<a href="?typ=zeme&amp;pozadavek=new">vytvoøit novou zemi</a>
			</div>
		<?		
			$zeme = new Zeme("edit",$zamestnanec->get_id(),$_GET["id_zeme"],"",$_POST["nazev"],$_GET["pozadavek"]);
			
			?><h3>Editace zemì</h3><?
			//zobrazim formular pro editaci/vytvoreni noveho dokumentu
			echo $zeme->show_form_zeme();
			
/*----------------	nová destinace -----------*/	
	}else if($_GET["typ"]=="destinace" and ($_GET["pozadavek"]=="new" or $_GET["pozadavek"]=="create") ){

		?>
			<div class="submenu">
			<a href="?typ=zeme_list">&lt;&lt; seznam zemí/destinací</a>
			</div>
		<?
			$zeme = new Zeme("new_destinace",$zamestnanec->get_id(),$_GET["id_zeme"],$_POST["nazev"],$_GET["pozadavek"]);
		
			?><h3>Vytvoøit novou destinaci</h3><?
			//zobrazim formular pro editaci/vytvoreni noveho dokumentu
			echo $zeme->show_form_destinace();		
	
/*----------------	editace destinace -----------*/		
	}else if($_GET["typ"]=="destinace" and ($_GET["pozadavek"]=="edit"  or $_GET["pozadavek"]=="update")){	
		?>
			<div class="submenu">
			<a href="?typ=zeme_list">&lt;&lt; seznam zemí/destinací</a>
			<a href="?typ=zeme&amp;pozadavek=new">vytvoøit novou zemi</a>
			</div>
		<?		
			$zeme = new Zeme("edit_destinace",$zamestnanec->get_id(),$_GET["id_zeme"],$_GET["id_destinace"],$_POST["nazev"],$_GET["pozadavek"]);
			
			?><h3>Editace destinace</h3><?
			//zobrazim formular pro editaci/vytvoreni noveho dokumentu
			echo $zeme->show_form_destinace();

	} //if typ
	?>
		</div>
		</div>
	<?
    //zobrazeni napovedy k modulu
    $core = Core::get_instance();
    echo ModulView::showHelp($core->show_current_modul()["napoveda"]);
}else{
    //zadny uzivatel neni prihlasen, vypisu logovaci formular
    echo ModulView::showLoginForm($zamestnanec->get_uzivatelske_jmeno());
    echo $zamestnanec->get_error_message();
}
?>

</body>
</html>