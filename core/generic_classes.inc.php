<?php
/** 
generic.inc.php - definuje obecne (abstraktni) tridy
*/

/*--------------------- OBECNA DATOVA TRIDA-----------------------------*/
abstract class Generic_data_class{
	protected $error_message;
	protected $ok_message;
	
	/*escapovani znaku pred vlozenim do databaze*/
	function check_slashes($vstup){
		//zkontroluju zda uz neni olomitkovano pomoci magic_quotas
		if (!get_magic_quotes_gpc()) {
   		 $vstup = addslashes($vstup);
		}
		return $vstup;
	}
			
	/*kontrola vstupnich dat*/
	function check($vstup){
		return trim(strip_tags($vstup));
	}
	
	/*kontrola vstupnich dat s povolenymi html znackami*/
	function check_with_html($vstup){
		return trim(strip_tags($vstup,ALLOWED_HTML_TAGS));
	}
		
	/*kontrola vstupnich dat typu integer*/
	function check_int($vstup){
		
		$vstup = intval(trim(strip_tags($vstup)));
		//echo $vstup;
		return $vstup;

	}

	/*změna formátu data z anglickeho do ceskeho*/
	//zvlada take konverzi typu datetime
	function change_date_en_cz($vstup){
		if($vstup!=""){
			$date_time=explode(" ", $vstup);
			$date_array=explode("-", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
			$vystup = $date_array[2].".".$date_array[1].".".$date_array[0];
			if($date_time[1]!=""){
				$vystup = $vystup." ".$date_time[1];
			}
			return $vystup;
		}else{
			return "00.00.0000";
		}
	}	
		/*změna formátu data z anglickeho do ceskeho*/
	//zvlada take konverzi typu datetime
	function change_date_en_cz_short($vstup){
		if($vstup!=""){
			$date_time=explode(" ", $vstup);
			$date_array=explode("-", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
			$vystup = $date_array[2].".".$date_array[1].".";
			if($date_time[1]!=""){
				$vystup = $vystup." ".$date_time[1];
			}
			return $vystup;
		}else{
			return "00.00";
		}
	}
	/*změna formátu data z ceskeho do anglickeho*/
	function change_date_cz_en($vstup){
		$vstup = Str_Replace(" ", "", $vstup);	
		if($vstup!=""){
			
			$date_array=explode(".", $vstup);
			return $date_array[2]."-".$date_array[1]."-".$date_array[0];
		}else{
			return "0000-00-00";
		}			
	}	
		/*změna formátu data z anglickeho do ceskeho*/
	//zvlada take konverzi typu datetime
	function change_date_en_cz_no_time($vstup){
		if($vstup!=""){
			$date_time=explode(" ", $vstup);
			$date_array=explode("-", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
			$vystup = $date_array[2].".".$date_array[1].".".$date_array[0];

			return $vystup;
		}else{
			return "";
		}
	}  
	/*na zaklade konstanty USING MOD REWRITE a vstupniho pole vrati prislusnou adresu*/
	function get_adress($array_adress,$zabezpecene_spojeni=0,$uvodni_cast="zajezdy",$server=""){
		$adress="";
		$i=0;
		if($server==""){
			$server = $_SERVER['SERVER_NAME'];
		}
		//používám mod rewrite, adresu vypíšu v "adresářovém" stylu
		if(USING_MOD_REWRITE==1){	
			while($array_adress[$i]!=""){
				if($i==0){
					if($_SESSION["zabezpecene"]==1 or $zabezpecene_spojeni==1){
						$protokol="https://";
					}else{
						$protokol="http://";
					}
					$adress .= $protokol.$server."/".$uvodni_cast."/".$array_adress[$i];
				}else{
					$adress .= "/".$array_adress[$i];
				}
				$i++;
			}
		}else{
			while($array_adress[$i]!=""){
				if($i==0){
					if($_SESSION["zabezpecene"]==1 or $zabezpecene_spojeni==1){
						$protokol="https://";
					}else{
						$protokol="http://";
					}				
					$adress .= $protokol.$_SERVER['SERVER_NAME']."/".$array_adress[$i].".php";
				}else if($i==1){
					$adress .= "?lev".$i."=".$array_adress[$i];
				}else{
					$adress .= "&amp;lev".$i."=".$array_adress[$i];
				}
				$i++;
			}		
		}
		return $adress;
	}
	
	
	/*chyby při zpracování dotazu*/
	function chyba($text_chyby){	
		if($this->error_message == ""){
			$this->error_message = $text_chyby;		
		}else{
			$this->error_message = $this->error_message." <br/>\n".$text_chyby;
		}
	}
	/*potvrzeni spravneho zpracování dotazu*/
	function confirm($text_hlasky){	
		if($this->ok_message == ""){
			$this->ok_message = $text_hlasky;		
		}else{
			$this->ok_message = $this->ok_message." <br/>\n".$text_hlasky;
		}	
	}	
	
	
	function get_error_message(){ 
		if($this->error_message!=""){
		   return "<h2 class=\"red\">".$this->error_message."</h2>";
		}else{
			return "";
		}
	}	
	function get_ok_message(){ 
		if($this->ok_message!=""){
		   return "<h2 class=\"green\">".$this->ok_message."</h2>";
		}else{
			return "";
		}
	}
	

	/*odstraneni ceskych znaku, mezer a dalsich podivnosti ze vstupu*/
	function nazev_web($vstup){
		//need to change
		//vymenim hacky a carky
		$nazev_web = Str_Replace(
						Array("ä","ë","ö","ü",   "á","č","ď","é","ě","í","ľ","ň","ó","ř","š","ť","ú","ů","ý","ž",  "Ä","Ë","Ö","Ü",  "Á","Č","Ď","É","Ě","Í","Ľ","Ň","Ó","Ř","Š","Ť","Ú","Ů","Ý","Ž") ,
						Array("a","e","o","u",   "a","c","d","e","e","i","l","n","o","r","s","t","u","u","y","z",  "A","E","O","U",  "A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z") ,
						$vstup);
		$nazev_web = Str_Replace(Array(" ", "_", "/"), "-", $nazev_web); //nahradí mezery a podtržítka pomlčkami
		$nazev_web = Str_Replace(Array("(",")",".","!",",","\"","'","*","&","+"), "", $nazev_web); //odstraní ().!,"'
		$nazev_web = StrToLower($nazev_web); //velká písmena nahradí malými.
		return $nazev_web;
	}
		
}


/*--------------------- OBECNY SEZNAM-----------------------------*/
abstract class Generic_list extends Generic_data_class{
	protected $data; 	//vysledek mysql dotazu
	protected $radek;	//jeden radek vysledku
	protected $suda;	//identifikator zda je radek sudy nebo lichy (kvuli zobrazeni)
	
	
	
	/*posun na dalsi radek tabulky*/
	function get_next_radek(){
		//zmena parity radku
		if($this->suda==0){
			$this->suda=1;
		}else{
			$this->suda=0;
		}
		return $this->radek=mysqli_fetch_array($this->data);
	}	

	/*posun na prvni radek tabulky*/
	function get_first_radek(){
		//pokud mam alespon 1 radek
		if( mysqli_num_rows($this->data) ){
			mysqli_data_seek($this->data,0);
		}
	}		
	
}


/*--------------------- TŘÍDA PRO VYHLEDÁVÁNÍ / PRÁCI S INTEGERovymi hodnotami z databaze-----------------------------*/
abstract class Generic_serial_class extends Generic_data_class{
	//překlady integrových hodnot z databáze
		protected static $array_doprava = array("vlastní","autokarem","letecky");
		protected static $array_cena = array("1000","5000","10000","15000","20000","30000","50000");
		protected static $array_order_by = array("datum","cena","nazev");
		protected static $array_strava = array("bez stravy","snídaně","polopenze","plná penze","all inclusive");
		protected static $array_ubytovani=array("bez ubytování","stan","chatky","apartmány","penzion","hotel","hotel 2*","hotel 3*","hotel 4*","hotel 5*");

		
}

?>
