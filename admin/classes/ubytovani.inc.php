<?php
/** 
* serial.inc.php - tridy pro zobrazeni serialu
*/

/*--------------------- SERIAL -------------------------------------------*/
class Ubytovani extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat	
	protected $id_zamestnance;


	protected $id_smluvni_podminky;	
	protected $id_ubytovani;
	protected $nazev;
	protected $nazev_web;
	protected $popisek;
	protected $popis;
        protected $kategorie;
        protected $zamereni_lazni;
        protected $pes;
        protected $pes_cena;

	protected $highlights;

	
	protected $data;
	protected $serial;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_ubytovani,$nazev="",$popisek="",$popis="",$kategorie="",$zamereni_lazni="",$highlights="",$pes="",$pes_cena="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		

				
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_ubytovani = $this->check_int($id_ubytovani);
		
		
		$this->nazev = $this->check_slashes( $this->check($nazev) );
		
		$this->popisek = $this->check_slashes(  $this->check_with_html($popisek)  );

		$this->popis = $this->check_slashes(  $this->check_with_html($popis)  );
		
		$this->kategorie = $this->check_int($kategorie);
                $this->zamereni_lazni = $this->check_slashes(  $this->check_with_html($zamereni_lazni));
                $this->highlights = $this->check_slashes(  $this->check_with_html($highlights));
		$this->pes = $this->check_int($pes);
                $this->pes_cena = $this->check($pes_cena);
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
		
			 if( ($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update") or $this->typ_pozadavku=="copy" or $this->typ_pozadavku=="show"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku,$this->id_zamestnance,$this->id_ubytovani))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$this->serial=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						
						$this->popisek = $this->serial["popisek"];
						$this->popis = $this->serial["popis"];						
						$this->highlights = $this->serial["highlights"];	
                                                $this->zamereni_lazni = $this->serial["zamereni_lazni"];
                                                $this->kategorie = $this->serial["kategorie"];
						$this->pes = $this->serial["pes"];
                                                $this->pes_cena = $this->serial["pes_cena"];
						//echo $this->id_zeme;
					if($this->typ_pozadavku!="copy"){											
						$this->id_ubytovani = $this->serial["id_ubytovani"];
						$this->nazev = $this->serial["nazev"];
					}
				
					if($this->typ_pozadavku=="copy"){
						$foto = new Foto_ubytovani("show",$this->id_zamestnance,$this->id_ubytovani);
						
						$this->data=$this->database->transaction_query($this->create_query("create"), 1 )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
						if( !$this->get_error_message() ){
							$this->id_ubytovani = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
										

							while($foto->get_next_radek()){
									$dotaz_foto = new Foto_ubytovani("create",$this->id_zamestnance,$this->id_ubytovani,$foto->get_id_foto(),0)	;
							}	
							
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
			$dotaz= "INSERT INTO `ubytovani`
							(`nazev`,`nazev_web`,`popisek`,`popis`,`zamereni_lazni`,`kategorie`,`highlights`,`pes`,`pes_cena`)
						VALUES
							 ('".$this->nazev."','".$this->nazev_web($this->nazev)."','".$this->popisek."','".$this->popis."',
							 '".$this->zamereni_lazni."',".$this->kategorie.",'".$this->highlights."',".$this->pes.",'".$this->pes_cena."' )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){	
			$dotaz= "UPDATE  `ubytovani`
						SET
							`nazev`='".$this->nazev."',
                                                        `nazev_web` = '".$this->nazev_web($this->nazev)."',
							`popisek`='".$this->popisek."',`popis`='".$this->popis."',
                                                         `highlights`='".$this->highlights."',
                                                         `zamereni_lazni`='".$this->zamereni_lazni."',
                                                         `pes_cena`='".$this->pes_cena."',
                                                         `pes`=".$this->pes.",
							`kategorie`=".$this->kategorie."

						WHERE `id_ubytovani`=".$this->id_ubytovani."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `ubytovani`
						WHERE `id_ubytovani`=".$this->id_ubytovani."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `ubytovani`
						WHERE `id_ubytovani`=".$this->id_ubytovani."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `ubytovani`
						WHERE `ubytovani`.`id_ubytovani`=".$this->id_ubytovani."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="copy"){
			$dotaz= "SELECT * FROM `ubytovani`
						WHERE `ubytovani`.`id_ubytovani`=".$this->id_ubytovani."
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
				$this->chyba("Musíte vyplnit název ubytování");
			}
			//echo $this->popisek;
			//echo "validace".Validace::text($this->popisek);
			
			if(!Validace::text($this->popisek) ){
				
				
				$ok = 0;
				$this->chyba("Musíte vyplnit popisek ubytování");
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
						<a href=\"".$adresa_modulu."?id_ubytovani=".$this->id_ubytovani."&amp;typ=ubytovani&amp;pozadavek=edit\">ubytování</a>";
	
		if($adresa_foto = $core->get_adress_modul_from_typ("fotografie") ){	 			
			$vypis = $vypis." | <a href=\"".$adresa_modulu."?id_ubytovani=".$this->id_ubytovani."&amp;typ=foto\">foto</a>";
		}		
		$vypis = $vypis."| <a href=\"".$adresa_modulu."?id_ubytovani=".$this->id_ubytovani."&amp;typ=ubytovani&amp;pozadavek=delete\">delete</a>";
		return $vypis;
	}
	
	
	/**zobrazeni formulare pro vytvoreni/editaci serialu*/
	function show_form(){
		
		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\"><b>Název ubytování</b>: <span class=\"red\">*</span></div> <div class=\"value\"> <input name=\"nazev\" type=\"text\" value=\"".$this->nazev."\" class=\"wide\"/></div></div>\n";

		$popisek="<div class=\"form_row\"> <div class=\"label_float_left\"><b>Popis a poloha</b>: <span class=\"red\">*</span></div>
                            <div class=\"value\"> <textarea name=\"popisek\" id=\"popisek_\" rows=\"5\" cols=\"100\">".$this->popisek."</textarea>
                            </div></div>\n";
	
		$popis="<div class=\"form_row\" id=\"popis\" > <div class=\"label_float_left\"><b>Pokoje a ubytování</b>:</div>
                        <div class=\"value\"> <textarea name=\"popis\" id=\"popis_\" rows=\"20\" cols=\"100\">".$this->popis."</textarea>
                        </div></div>\n";

		$zamereni_lazni="<div class=\"form_row\" id=\"zamereni_lazni\"  ><div class=\"label_float_left\"><b>Poznámky</b>: </div>
                        <div class=\"value\"> <textarea name=\"zamereni_lazni\" id=\"zamereni_lazni_\" rows=\"3\" cols=\"100\">".$this->zamereni_lazni."</textarea>
                        </div></div>\n";

		$pes="<div class=\"form_row\" id=\"pes\"  ><div class=\"label_float_left\"><b>Pobyt se psem</b>: </div>
                        <div class=\"value\">
                            Není známo, nezobrazovat <input type=\"radio\" name=\"pes\" value=\"0\" ".(($this->pes==0)?("checked=\"checked\""):(""))."/>,
                            Nelze <input type=\"radio\" name=\"pes\" value=\"2\" ".(($this->pes==2)?("checked=\"checked\""):(""))." />,
                            Lze <input type=\"radio\" name=\"pes\" value=\"1\" ".(($this->pes==1)?("checked=\"checked\""):(""))." />,
                                cena pobytu: <input type=\"text\" name=\"pes_cena\" value=\"".$this->pes_cena."\" size=\"4\" /> /den.
                        </div></div>\n";


		$kategorie="<div class=\"form_row\" id=\"kategorie\"  > <div class=\"label_float_left\"><b>kategorie ubytování</b>: </div>
                            <div class=\"value\">
                                <select name=\"kategorie\">
                                    <option value=\"0\">--- (neznámá kategorie) </option>
                                    <option value=\"1\" ".(($this->kategorie==1)?("selected=\"selected\""):("")).">*</option>
                                    <option value=\"2\" ".(($this->kategorie==2)?("selected=\"selected\""):("")).">**</option>
                                    <option value=\"3\" ".(($this->kategorie==3)?("selected=\"selected\""):("")).">***</option>
                                    <option value=\"4\" ".(($this->kategorie==4)?("selected=\"selected\""):("")).">****</option>
                                    <option value=\"5\" ".(($this->kategorie==5)?("selected=\"selected\""):("")).">*****</option>
                                </select>
                            </div></div>\n";
								

                $highlights="<div class=\"form_row\" id=\"highlights\"  > <div class=\"label_float_left\"><b>Highlights</b> (oddìlujte èárkou): </div>
                            <div class=\"value\"> <textarea name=\"highlights\" rows=\"3\" cols=\"100\">".$this->highlights."</textarea></div></div>\n";


		$make_whizywig = "								
				<script language=\"JavaScript\" type=\"text/javascript\">
					makeWhizzyWig(\"popisek_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");			
					makeWhizzyWig(\"popis_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
					makeWhizzyWig(\"zamereni_lazni_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\");
				</script>								
				";
			
		
		
		//tvorba select zeme (pouze pri novem serialu)
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=ubytovani&amp;pozadavek=create";
			
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit ubytování\" />\n";
						
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení ubytování</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){
			$zeme="";
			
			//cil formulare
			$action="?id_ubytovani=".$this->id_ubytovani."&amp;typ=ubytovani&amp;pozadavek=update";
				
			if(  $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit ubytování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto seriálu</strong>\n";
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
						$nazev.$popisek.$popis.$zamereni_lazni.
						$pes.$kategorie.$highlights.$submit.
						$make_whizywig.
					"</form>";
		return $vystup;
	}
	
	
	function get_id() { return $this->id_ubytovani;}
	function get_nazev() { return $this->nazev;}

	function get_id_user_create() { 
		return 0;
		
        }
} 




?>
