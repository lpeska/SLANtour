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
                if (get_magic_quotes_gpc()==1){
                    $vstup = stripslashes($vstup);
                } 
                //$vstup = str_ireplace("\\", "", $vstup);
                //$vstup = str_ireplace("\"\"", "\"", $vstup);
                //$vstup = str_ireplace("\'\'", "\'", $vstup);
                
                $vstup = str_ireplace("…", "...", $vstup);   
                $vstup = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$vstup);
		//zkontroluju zda uz neni olomitkovano pomoci magic_quotas
		
		return $vstup;
	}
	/*nejprve prevede kombinace enter+br na enter (ochrana proti vicenasobnemu <br>) a pak zavola nl2br*/
	function my_nl2br($vstup){
		$vstup = nl2br($vstup);
		$vstup = Str_Replace("<br /><br />", "<br />", $vstup);		
		//$ok_message = $vstup;
		return $vstup;
	}
        /*je treba obalit konce radku ukoncenim a navazanim stringu - JS je row - sensitive*/
        function javascript_text_transform($text){
               return str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string)$text), "\0..\37'\\"))); 
        }
	/*funkce pro zmenu nekterych znaku neulozitelnych do databaze na jejich normalni ekvivalenty*/
	function znaky_replace($text){
            
	$vystup = Str_Replace(
		Array("–","„","“","€"),
		Array("-","\"","\"","EUR"),
		$text);
       
	return $vystup;
	}
        /*kontrola vstupnich dat s povolenymi html znackami*/
	function check($vstup){

                $ret = trim( strip_tags( $this->znaky_replace( $vstup ) ) ) ;
                if($ret == "<br />"){
                    return "";
                }
		return $ret;

	}
	/*kontrola vstupnich dat s povolenymi html znackami*/
	function check_with_html($vstup){
                        
        	
        $vstup = str_ireplace("normal\"=\"\"", "", $vstup);
        $vstup = str_ireplace("Normal\"", "", $vstup);	
        $vstup = str_ireplace("normal\\\"=\\\"\\\"", "", $vstup);
        $vstup = str_ireplace("Normal\\\"", "", $vstup);
                $ret = trim( strip_tags( $this->znaky_replace($vstup) ,ALLOWED_HTML_TAGS));
                if($ret == "<br />"){
                    return "";
                }
                //echo $this->chyba($ret);
		return $ret;

	}
	/*kontrola vstupnich dat typu integer*/
	function check_int($vstup){

		$vstup = intval(trim(strip_tags($vstup)));

		//echo $vstup;
		return $vstup;

	}
        
        /*kontrola vstupnich dat typu double*/
	function check_double($vstup){

		$vstup = doubleval(trim(strip_tags($vstup)));

		//echo $vstup;
		return $vstup;

	}

	/*zmìna formátu data z anglickeho do ceskeho*/
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
			return "";
		}
	}	
	/*zmìna formátu data z anglickeho do ceskeho*/
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
		/*zmìna formátu data z anglickeho do ceskeho*/
	//zvlada take konverzi typu datetime
	function change_date_en_cz_short($vstup){
		if($vstup!=""){
			$date_time=explode(" ", $vstup);
			$date_array=explode("-", $date_time[0]); //prvni parametr v datetime je datum, druhy cas
			$vystup = $date_array[2].".".$date_array[1].".";
			if($date_time[1]!="" and $date_time[1]!="00:00:00"){
				$vystup = $vystup." ".$date_time[1];
			}
			return $vystup;
		}else{
			return "00.00";
		}
	}
	/*zmìna formátu data z ceskeho do anglickeho - offset = +pricte pocet dni k datu*/
	function change_date_cz_en($vstup, $daysOffset = 0){
		if($vstup!=""){                        
			$vstup = Str_Replace(" ", "", $vstup);	
			$date_array=explode(".", $vstup);
			$date = $date_array[2]."-".$date_array[1]."-".$date_array[0];
            $date = date("Y-m-d", strtotime($date . "+$daysOffset days"));
            return $date;
		}else{
			return "";
		}			
	}

    function change_date_cz_en_ommitable_year($vstup){
        if($vstup!=""){
            $vstup = Str_Replace(" ", "", $vstup);
            $date_array=explode(".", $vstup);
            $year = $date_array[2] == "" ? "" : $date_array[2]."-";
            $month = str_pad($date_array[1], 2, "0", STR_PAD_LEFT) . "-";
            $day = str_pad($date_array[0], 2, "0", STR_PAD_LEFT);
            $date = $year.$month.$day;
            return $date;
        }else{
            return "";
        }
    }
        /*zmìna formátu data z ceskeho do anglickeho*/
	function change_datetime_cz_en($vstup){
		if($vstup!=""){    
                        $date_time=explode(" ", $vstup);				
			$date_array=explode(".", $date_time[0]);
			$vystup = $date_array[2]."-".$date_array[1]."-".$date_array[0];
                        if($date_time[1]!=""){
				$vystup = $vystup." ".$date_time[1];
			}
                        return $vystup;
		}else{
			return "";
		}			
	}

	/*odstraneni ceskych znaku, mezer a dalsich podivnosti ze vstupu*/
	function nazev_web($vstup){
		//need to change
		//vymenim hacky a carky
		$nazev_web = Str_Replace(
						Array("ä","ë","ö","ü",   "á","è","ï","é","ì","í","¾","ò","ó","ø","š","","ú","ù","ý","ž",  "Ä","Ë","Ö","Ü",  "Á","È","Ï","É","Ì","Í","¼","Ò","Ó","Ø","Š","","Ú","Ù","Ý","Ž") ,
						Array("a","e","o","u",   "a","c","d","e","e","i","l","n","o","r","s","t","u","u","y","z",  "A","E","O","U",  "A","C","D","E","E","I","L","N","O","R","S","T","U","U","Y","Z") ,
						$vstup);
		$nazev_web = Str_Replace(Array(" ", "_", "/"), "-", $nazev_web); //nahradí mezery a podtržítka pomlèkami
		$nazev_web = Str_Replace(Array("(",")",".","!",",","\"","'","*","&","+"), "", $nazev_web); //odstraní ().!,"'
		$nazev_web = StrToLower($nazev_web); //velká písmena nahradí malými.
		return $nazev_web;
	}


	/*vrati cislo pristiho id*/
	function get_next_autoid($id,$table){
  		 $dotaz = 'SELECT MAX(`'.$id.'`) AS last_id FROM `'.$table.'`';
  		 $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
   	 $zaznam_id = mysqli_fetch_array($data);
   	return ($zaznam_id["last_id"]+1);
	}	

	
		
	/*chyby pøi zpracování dotazu*/
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
	
	/*na zaklade konstanty USING MOD REWRITE a vstupniho pole vrati prislusnou adresu*/
	function get_adress($array_adress){
		$adress="";
		$i=0;
		//používám mod rewrite, adresu vypíšu v "adresáøovém" stylu
		if(USING_MOD_REWRITE==1){	
			while($array_adress[$i]!=""){
				if($i==0){
					$adress.="/zajezdy/".$array_adress[$i];
					}else{
					$adress.="/".$array_adress[$i];
				}
				$i++;
			}
		}else{
			while($array_adress[$i]!=""){
				if($i==0){
					$adress.="/".$array_adress[$i].".php";
					}else if($i==1){
					$adress.="?lev".$i."=".$array_adress[$i];
					}else{
					$adress.="&amp;lev".$i."=".$array_adress[$i];
				}
				$i++;
			}		
		}
		return $adress;
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


?>
