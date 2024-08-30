<?php
/**   \file
* index.php - hlavni stranka klientske casti systemu
*	- je na ni provaden reload p� zpracov�n� formul��� ostatn�ch modul�
* 	- zobrazuje podrobn� vyhled�v�n� a tipy na z�jezdy
*/

//spusteni prace se sessions




	session_start();
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


require_once "./classes/rezervace_dotaz.inc.php"; //operace s daty klienta

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $secretKey = '6Len0DIqAAAAALXC3nx5Y3UyzjgSAOZFi1j4z2Bh'; // Replace with your Secret Key
  $recaptchaToken = $_POST['recaptchaToken'];
  $userIP = $_SERVER['REMOTE_ADDR'];

  $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$secretKey&response=$recaptchaToken&remoteip=$userIP");
  $responseKeys = json_decode($response, true);

  if (intval($responseKeys["success"]) !== 1 || $responseKeys["score"] < 0.5) {
      echo print_r($responseKeys);
      echo print_r($_POST);
      echo 'Failed CAPTCHA verification. Please try again.';
  } else {
    // CAPTCHA verified successfully
    $dotaz = new Rezervace_dotaz("odeslat",$_POST["serialId"],$_POST["zajezdId"],$_POST["id_klient"],$_POST["message"],
      $_POST["fname"],$_POST["lname"],$_POST["email"],$_POST["phone"],$_POST["novinky"]);	
    
    //vytvorime adresu dalsi stranku - automaticky nactenou pres http location							
    if( !$dotaz->get_error_message() ){
      // $adress = "/dekujeme.php?id_serial=".$_POST["id_serial"]."&typ=dotaz";
      // $_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_ok_message();
      echo "OK";
    }else{			
      // $_SESSION["hlaska"]=$_SESSION["hlaska"].$dotaz->get_error_message();
      echo "ERR";
    }
  }
}

				
        
       