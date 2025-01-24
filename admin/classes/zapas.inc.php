<?php
/** 
* serial.inc.php - tridy pro zobrazeni serialu
*/

/*--------------------- SERIAL -------------------------------------------*/
class Zapas extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;


	protected $id_zapas;
	protected $nazev;
	protected $nazev_web;
	protected $datum;
	protected $popis;


	
	protected $data;
	protected $serial;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_zapas,$nazev="",$datum="",$popis="",$nazev_en="",$popis_en="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
					
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_zapas = $this->check_int($id_zapas);
		
		
		$this->nazev = $this->check_slashes( $this->check($nazev) );
                $this->nazev_en = $this->check_slashes( $this->check($nazev_en) );
		$this->datum = $this->change_date_cz_en($datum);
		$this->popis = $this->check_slashes(  $this->check_with_html($popis)  );
                $this->popis_en = $this->check_slashes(  $this->check_with_html($popis_en)  );

                

		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
		
			 if( ($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update") or $this->typ_pozadavku=="copy" or $this->typ_pozadavku=="show"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku,$this->id_zamestnance,$this->id_ubytovani))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$this->serial=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->datum = $this->serial["datum"];
						$this->popis = $this->serial["popis"];

						//echo $this->id_zeme;
					if($this->typ_pozadavku!="copy"){											
						$this->id_zapas = $this->serial["id_zapas"];
						$this->nazev = $this->serial["nazev"];
                                                $this->nazev_en = $this->serial["nazev_en"];
                                                $this->popis_en = $this->serial["popis_en"];
					}
                                        
					if($this->typ_pozadavku=="copy"){

						$this->data=$this->database->transaction_query($this->create_query("create"), 1 )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
						if( !$this->get_error_message() ){
							$this->id_zapas = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
							$this->database->commit(); //potvrdim transakci
						}					
						
					}
			}
				//	$this->chyba("popisy:".$this->popis_strediska."..".$this->popis_lazni);
			
			
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="delete"){
					
					if($this->typ_pozadavku == "create"){ //pouziju jinou funkci pro odeslani dotazu - vice dotazu v transakci
						$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );								
										
					}else{
						$this->data=$this->database->query($this->create_query($this->typ_pozadavku) )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );					
					}

					if( !$this->get_error_message() ){
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}		
	
	
			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
		}
                }
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `zapas`
							(`nazev`,`datum`,`popis`,`nazev_en`,`popis_en`)
						VALUES
							 ('".$this->nazev."','".$this->datum."','".$this->popis."','".$this->nazev_en."','".$this->popis_en."' )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){	
			$dotaz= "UPDATE  `zapas`
						SET
							`nazev`='".$this->nazev."',
							`datum`='".$this->datum."',
                                                        `popis`='".$this->popis."',
                                                         `nazev_en`='".$this->nazev_en."',
                                                         `popis_en`='".$this->popis_en."'

						WHERE `id_zapas`=".$this->id_zapas."
						LIMIT 1";
			echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `zapas`
						WHERE `id_zapas`=".$this->id_zapas."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `zapas`
						WHERE `id_zapas`=".$this->id_zapas."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `zapas`
						WHERE `zapas`.`id_zapas`=".$this->id_zapas."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="copy"){
			$dotaz= "SELECT * FROM `zapas`
						WHERE `zapas`.`id_zapas`=".$this->id_zapas."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
                }
	}	

/**kontrola zda smi uzivatel provest danou akci*/
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
		
		//podle jednotlivych typu pozadavku
		if($typ_pozadavku == "new"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
			
		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create"){
			return $zamestnanec->get_bool_prava($id_modul,"create");	
					
		}else if($typ_pozadavku == "copy"){
			return $zamestnanec->get_bool_prava($id_modul,"create");			

		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi")){
				return true;
			}else {
				return false;
			}			

		}else if($typ_pozadavku == "delete"){
			if( $zamestnanec->get_bool_prava($id_modul,"delete_cizi") ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;
	}

	/**kontrola zda mam odpovidajici data*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovaná data: název seriálu, popisek,  id_typ, 
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název zápasu");
			}
			//echo $this->popisek;
			//echo "validace".Validace::text($this->popisek);
			
			if(!Validace::datum_en($this->datum) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit datum zápasu ve formátu dd.mm.rrrr");
			}								
		
		}

		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}

	}

	/**zobrazeni menu - moznosti editace pro konkretni serial*/
	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];
			
		$vypis=" ".$this->nazev.": ";	
		$vypis = $vypis."
						<a href=\"".$adresa_modulu."?id_zapas=".$this->id_zapas."&amp;typ=zapas&amp;pozadavek=edit\">zápas</a>";
		$vypis = $vypis."| <a href=\"".$adresa_modulu."?id_zapas=".$this->id_zapas."&amp;typ=foto\">foto</a>";
		$vypis = $vypis."| <a href=\"".$adresa_modulu."?id_zapas=".$this->id_zapas."&amp;typ=zapas&amp;pozadavek=delete\">delete</a>";
		return $vypis;
	}
	
	
	/**zobrazeni formulare pro vytvoreni/editaci serialu*/
	function show_form(){
		
		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\"><b>Název/soupeø</b>: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" class=\"wide\"/></div></div>\n
                        <div class=\"form_row\"> <div class=\"label_float_left\"><b>Název v angliètinì</b>: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev_en\" type=\"text\" value=\"".$this->nazev_en."\" class=\"wide\"/></div></div>\n
                        ";

                $datum="<div class=\"form_row\"> <div class=\"label_float_left\">Datum: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"datum\" type=\"text\" value=\"".$this->change_date_en_cz($this->datum)."\" /></div></div>\n";
	
		$popis="<div class=\"form_row\" id=\"popis\" > <div class=\"label_float_left\"><b>Popis zápasu</b>:</div>
                        <div class=\"value\"> <textarea name=\"popis\" id=\"popis_\" rows=\"20\" cols=\"100\">".$this->popis."</textarea>
                        </div></div>\n

                        <div class=\"form_row\" id=\"popis\" > <div class=\"label_float_left\"><b>Popis zápasu anglicky</b>:</div>
                        <div class=\"value\"> <textarea name=\"popis_en\" id=\"popis_en_\" rows=\"20\" cols=\"100\">".$this->popis_en."</textarea>
                        </div></div>\n";
		
		$make_whizywig = "								
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"popis_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
                                        makeWhizzyWig(\"popis_en_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
                                </script>
				";
			
		
		
		//tvorba select zeme (pouze pri novem serialu)
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=zapas&amp;pozadavek=create";
			
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit zápas\" />\n";
						
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení zápasu</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){
			$zeme="";
			
			//cil formulare
			$action="?id_zapas=".$this->id_zapas."&amp;typ=zapas&amp;pozadavek=update";
				
			if(  $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit zápas\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto zápasu</strong>\n";
			}
		}
		

		$javascript_funkce="
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig60.js\"></script>
		<script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script>
		<script  language=\"JavaScript\">
			function hidediv(id,id_odkaz) {
				//safe function to hide an element with a specified id
				if (document.getElementById) { // DOM3 = IE5, NS6
					document.getElementById(id).style.display = 'none';
					document.getElementById(id_odkaz).href = \"javascript:showdiv('\" +id+ \"','\" +id_odkaz+ \"');\";					
				}
				else {
					if (document.layers) { // Netscape 4
						document.id.display = 'none';
						document.id_odkaz.href = \"javascript:showdiv('\" +id+ \"','\" +id_odkaz+ \"');\";						
					}
					else { // IE 4
						document.all.id.style.display = 'none';
						document.all.id_odkaz.href = \"javascript:showdiv('\" +id+ \"','\" +id_odkaz+ \"');\";						
					}
				}
			}
			
			function showdiv(id,id_odkaz) {
				//safe function to show an element with a specified id
				//	  document.write('neco neco' + id);
				if (document.getElementById) { // DOM3 = IE5, NS6
					document.getElementById(id).style.display = 'block';
					document.getElementById(id_odkaz).href = \"javascript:hidediv('\" +id+ \"','\" +id_odkaz+ \"');\";
				}
				else {
					if (document.layers) { // Netscape 4
						document.id.display = 'block';
						document.id_odkaz.href = \"javascript:hidediv('\" +id+ \"','\" +id_odkaz+ \"');\";
					}
					else { // IE 4
						document.all.id.style.display = 'block';
						document.all.id_odkaz.href = \"javascript:hidediv('\" +id+ \"','\" +id_odkaz+ \"');\";
					}
				}
			}
		
		</script>
		
		";

		
		$vystup= $javascript_funkce.
					"<form action=\"".$action."\" method=\"post\" onsubmit=\"syncTextarea()\">".
						$nazev.$datum.$popis.$submit.
						$make_whizywig.
					"</form>";
		return $vystup;
	}
	
	
	function get_id() { return $this->id_zapas;}
	function get_nazev() { return $this->nazev;}
        function get_datum() { return $this->datum;}

	function get_id_user_create() { 
		return 0;
		
        }
} 




?>
