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



        // if($_GET["lev4"] !=""){//mame velmi pravdepodobne stary serial+zajezd
		             
     
        //   $adresa = "/zajezdy/dotaz/".$_GET["lev3"]."/".$_GET["lev4"];             
        //     header ('HTTP/1.1 301 Moved Permanently');
        //     header ("Location: http://".$_SERVER['HTTP_HOST'].$adresa);
        //     exit;
            
        // }else if($_GET["lev3"] !=""){ //mame velmi pravdepodobne stary serial	
            
        //    $adresa = "/zajezdy/dotaz/".$_GET["lev3"];             
        //     header ('HTTP/1.1 301 Moved Permanently');
        //     header ("Location: http://".$_SERVER['HTTP_HOST'].$adresa);
        //     exit;     
            	                    
        // }else if($_GET["lev1"] !=""){
        //     if($_GET["lev2"] =="" and $_POST["id_zajezd"]>0){
        //         $_GET["lev2"] =  $_POST["id_zajezd"];        
        //     }            
        //     if($_GET["lev2"] !=""){
        //         $serial = new Serial_with_zajezd($_GET["lev1"],$_GET["lev2"]);
        //         $zobrazit_zajezd = true;
        //         $termin_od = $serial->get_termin_od();
        //         $termin_do = $serial->get_termin_do();
        //         $id_serial = $serial->get_id_serial();
        //         $zeme_text = $serial->get_nazev_zeme_web();
        //         $_GET["id_serial"]=$id_serial;
        //         $_GET["id_zajezd"]=$_GET["lev2"];
        //     }else{
        //         $serial = new Serial($_GET["lev1"]);
        //         $zobrazit_zajezd = false;
        //         $id_serial = $serial->get_id();
        //         $zeme_text = $serial->get_nazev_zeme_web();
        //         $_GET["id_serial"]=$id_serial;

        //         $serial->create_zajezdy();
        //         if ($serial->get_zajezdy()->get_next_radek()) {//mame jeden zajezd
        //             $id_zajezd = $serial->get_zajezdy()->get_id_zajezd();
        //         }
        //         if ($serial->get_zajezdy()->get_next_radek()) {//mame druhy zajezd, spatne
        //             $no_reload_with_zajezd = true;
        //         } else {
        //             $no_reload_with_zajezd = 0;
        //         }

        //         if (!$no_reload_with_zajezd and $id_zajezd != "") { //mame prave jeden zajezd
        //             $_GET["lev2"] = $id_zajezd;
        //             $_GET["id_zajezd"]=$_GET["lev2"];
        //             $serial = new Serial_with_zajezd($_GET["lev1"], $_GET["lev2"]);
        //             $zobrazit_zajezd = true;
        //         }                
                
                
        //     }    

        // }


				
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
        
       