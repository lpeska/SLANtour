<?php
/** 
* foto.inc.php - tridy pro zobrazeni fotek
*/

/*--------------------- SERIAL -------------------------------------------*/
class Foto extends Generic_data_class{
	//vstupnidata
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
	protected $id_zamestnance;
	
	protected $id_foto;
	protected $id_zeme;
	protected $id_destinace;
	
	protected $nazev_foto;
	protected $popisek_foto;
	protected $foto_url;
	protected $id_user_create;
		
	protected $data;
	protected $foto;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct(
			$typ_pozadavku,$id_zamestnance,$id_foto,$id_zeme="",$id_destinace="",
			$nazev_foto="",$popisek_foto="",$posted_foto="",$minuly_pozadavek=""
	){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);		
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		
		$this->id_foto = $this->check_int($id_foto);
		$this->id_zeme = $this->check_int($id_zeme);
		$this->id_destinace = $this->check_int($id_destinace);	
					
		$this->nazev_foto = $this->check_slashes( $this->check($nazev_foto) );
		$this->popisek_foto = $this->check_slashes( $this->check($popisek_foto) );
		
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
			if($this->typ_pozadavku=="create"){
				
				@chmod("../".ADRESAR_FULL, 0777);
				@chmod("../".ADRESAR_NAHLED, 0777);
				@chmod("../".ADRESAR_IKONA, 0777);
				@chmod("../".ADRESAR_MINIIKONA, 0777);
				
				//uploaduju dokument	
				/*zjistim dalsi nepouzite cislo v autoindexu
					- funkce nezajisti ze jde presne o cislo ktere bude vlozeno - neziska dalsi hodnotu autoindexu, ale staci ze bude jedinecne*/
				$porad_cislo=$this->get_next_autoid("id_foto","foto");
			
				//podle pripony zjistim, jestli mame spravny typ souboru
				$pripona = strtolower( substr( $_FILES['foto']['name'], ( strrpos( $_FILES['foto']['name'],"." )+1 ) ) );
				
				if($pripona=="jpg" or $pripona=="jpeg"){
					//pokud jsme dostali fotku
					if($_FILES['foto']['size']!=0){	
						$foto_adress="../".ADRESAR_FULL."/".$porad_cislo."-".$this->nazev_web($this->nazev_foto).".".$pripona;
					
						//adresa do databaze (neukladame adresar dokumentu)
						$this->foto_url = $porad_cislo."-".$this->nazev_web($this->nazev_foto).".".$pripona;
					
						//pokud se nam podari uploadovany dokument presunout na spravne misto, muzeme ho vlozit do databaze
						if (@move_uploaded_file($_FILES['foto']['tmp_name'], $foto_adress)){
							//pokud se podarilo vytvorit vsechny zmensene fotky
							if($this->create_nahled($this->foto_url) and $this->create_ico($this->foto_url) and $this->create_miniico($this->foto_url)){							
								$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 							or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );						
								$this->id_foto = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
								$this->foto["id_foto"] = $this->id_foto;			
							}else{
								$this->chyba("Nepovedlo se vytvoøit náhled fotografie! Pravdìpodobnì bude tøeba zmìnit pøístupová práva k adresáøùm ".ADRESAR_NAHLED.", ".ADRESAR_IKONA." a ".ADRESAR_MINIIKONA."");
							}			
						}else{
							$this->chyba("Fotografii se nepodaøilo správnì uploadovat");
						}
					}else{
						$this->chyba("Fotografii se nepodaøilo správnì uploadovat");
					}
				}else{
					$this->chyba("Odeslaný soubor není povoleného typu!");
				}
							
			}else if($this->typ_pozadavku=="update"){
				/*oproti create zasadni rozdil: zadny soubor nemusi byt uploadovan - vymena souboru a update databaze se provadi nezavisle*/
				//pokud jsme dostali dokument
				if($_FILES['foto']['size']!=0){		
				
					@chmod("../".ADRESAR_FULL, 0777);
					@chmod("../".ADRESAR_NAHLED, 0777);
					@chmod("../".ADRESAR_IKONA, 0777);
					@chmod("../".ADRESAR_MINIIKONA, 0777);		
							
					//podle pripony zjistim, jestli mame spravny typ souboru
					$pripona = strtolower( substr( $_FILES['foto']['name'], ( strrpos( $_FILES['foto']['name'],"." )+1 ) ) );
					if($pripona=="jpg" or $pripona=="jpeg"){
					
						$foto_adress="../".ADRESAR_FULL."/".$this->id_foto."-".$this->nazev_web($this->nazev_foto).".".$pripona;
					
						//adresa do databaze (neukladame adresar dokumentu)
						$this->foto_url = $this->id_foto."-".$this->nazev_web($this->nazev_foto).".".$pripona;
					
						//pokud se nam podari uploadovany dokument presunout na spravne misto, muzeme ho vlozit do databaze
						if (@move_uploaded_file($_FILES['foto']['tmp_name'], $foto_adress)){
							//pokud se podarilo vytvorit vsechny zmensene fotky
							if($this->create_nahled($this->foto_url) and $this->create_ico($this->foto_url) and $this->create_miniico($this->foto_url)){							
								$this->data=$this->database->query($this->create_query($this->typ_pozadavku,"with_upload"))
		 							or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );						
								$this->id_foto = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
								$this->foto["id_foto"] = $this->id_foto;			
							}else{
								$this->chyba("Nepovedlo se vytvoøit náhled fotografie! Pravdìpodobnì bude tøeba zmìnit pøístupová práva k adresáøùm ".ADRESAR_NAHLED.", ".ADRESAR_IKONA." a ".ADRESAR_MINIIKONA."");
							}										
						}else{
								$this->chyba("Fotografii se nepodaøilo správnì uploadovat, pravdìpodobnì bude tøeba zmìnit pøístupová práva k adresáøi ".ADRESAR_FULL."");
						}
					}else{
						$this->chyba("Odeslaný soubor není povoleného typu!");
					}
				//neuploadovali jsme žádnou fotku
				}else{
						$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
				}
							
			}else if($this->typ_pozadavku=="delete"){
                            //zjisti jaky soubor se k tomu vaze a smaz ho
                            $this->data = $this->database->query($this->create_query("edit"))
                                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                            $this->foto = mysqli_fetch_array($this->data);
                            $foto_url = "../" . ADRESAR_FULL . "/" . $this->foto["foto_url"];
                            unlink($foto_url);
                            $foto_url = "../" . ADRESAR_NAHLED . "/" . $this->foto["foto_url"];
                            unlink($foto_url);
                            $foto_url = "../" . ADRESAR_IKONA . "/" . $this->foto["foto_url"];
                            unlink($foto_url);
                            $foto_url = "../" . ADRESAR_MINIIKONA . "/" . $this->foto["foto_url"];
                            unlink($foto_url);
                            $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
			//pro pozadavek edit je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update" ){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
						
					$this->foto=mysqli_fetch_array($this->data);		
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_foto = $this->foto["id_foto"];
						$this->id_zeme = $this->foto["id_zeme"];
						$this->id_destinace = $this->foto["id_destinace"];
																		
						$this->nazev_foto = $this->foto["nazev_foto"];
						$this->popisek_foto = $this->foto["popisek_foto"];
						$this->foto_url = $this->foto["foto_url"];
						$this->id_user_create = $this->foto["id_user_create"];						
			} else if ($this->typ_pozadavku == "mass_del") {  
                            //zjisti ktere fotky se k tomu vazou a smaz je
                            $this->data = $this->database->query($this->create_query("get_fotos"))
                                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                            while($this->foto = mysqli_fetch_array($this->data)) {
                                $foto_url = "../" . ADRESAR_FULL . "/" . $this->foto["foto_url"];                                
                                unlink($foto_url);
                                $foto_url = "../" . ADRESAR_NAHLED . "/" . $this->foto["foto_url"];                                
                                unlink($foto_url);
                                $foto_url = "../" . ADRESAR_IKONA . "/" . $this->foto["foto_url"];                                
                                unlink($foto_url);
                                $foto_url = "../" . ADRESAR_MINIIKONA . "/" . $this->foto["foto_url"];                                
                                unlink($foto_url);
                            }
                            $_POST["massdel_ids"] = $this->check($_POST["massdel_ids"]);
                            $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                                    or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                        }
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");		
		}
		
		//pokud se akce uspìšnì zapsala do databáze, vypíšu potvrzovací hlášku
		if(!$this->get_error_message() and 
			($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "delete") ){
			$this->confirm("Požadovaná akce probìhla úspìšnì");
		}		

	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku,$with_upload=""){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `foto` 
							(`nazev_foto`,`id_zeme`,`id_destinace`,`popisek_foto`,`foto_url`,`id_user_create`,`id_user_edit`)
						VALUES
							 ('".$this->nazev_foto."',".$this->id_zeme.",".$this->id_destinace.",'".$this->popisek_foto."','".$this->foto_url."',
							  ".$this->id_zamestnance.",".$this->id_zamestnance." )";			
		}else if($typ_pozadavku=="update" and $with_upload=="with_upload"){
			//predavame i adresu noveho dokumentu
			$dotaz= "UPDATE `foto` 
						SET
							`nazev_foto`='".$this->nazev_foto."',`popisek_foto`='".$this->popisek_foto."',
							`id_zeme`=".$this->id_zeme.",`id_destinace`=".$this->id_destinace.",
							`foto_url`='".$this->foto_url."',`id_user_edit`=".$this->id_zamestnance."
						WHERE `id_foto`=".$this->id_foto."
						LIMIT 1";			
		}else if($typ_pozadavku=="update"){
			//neupdatuju dokument_url
			$dotaz= "UPDATE `foto` 
						SET
							`nazev_foto`='".$this->nazev_foto."',`popisek_foto`='".$this->popisek_foto."',
							`id_zeme`=".$this->id_zeme.",`id_destinace`=".$this->id_destinace.",
							`id_user_edit`=".$this->id_zamestnance."
						WHERE `id_foto`=".$this->id_foto."
						LIMIT 1";			
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `foto` 
						WHERE `id_foto`=".$this->id_foto."
						LIMIT 1";			
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `foto` 
						WHERE `id_foto`=".$this->id_foto."
						LIMIT 1";			
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `foto` 
						WHERE `id_foto`=".$this->id_foto."
						LIMIT 1";
                } else if ($typ_pozadavku == "get_fotos") {            
                    $massDelIds = explode("::", trim($_POST["massdel_ids"], ":"));

                    $where = "WHERE ";
                    foreach($massDelIds as $id) {
                        $where .= " id_foto=$id OR ";
                    }
                    $where = substr($where, 0, strlen($where) - 4);

                    $dotaz = "SELECT * FROM `foto` $where";
		} else if ($typ_pozadavku == "mass_del") {            
                    $massDelIds = explode("::", trim($_POST["massdel_ids"], ":"));

                    $where = "WHERE ";
                    foreach($massDelIds as $id) {
                        $where .= " id_foto=$id OR ";
                    }
                    $where = substr($where, 0, strlen($where) - 4);

                    $dotaz = "DELETE FROM `foto` $where";
                }
                
                //echo $dotaz;
                return $dotaz;
	}	
	/**vytvoreni nahledu k fotce - zachovava pomery stran puvodniho obrazku, delsi ze stran musi byt vzdy <= nez maximalni*/
	function create_nahled ($foto){
		$max_sirka=350;
		$max_vyska=350;

		$foto_adress="../".ADRESAR_FULL."/".$foto;
		
		if(file_exists($foto_adress)){ //pokud existuje soubor s obrázkem
			$full_size=GetImageSize($foto_adress); //zjištìní pùvodních rozmìrù obrázku
			$full_fotka=ImageCreateFromJPEG($foto_adress); //naètení pùvodního obrázku ve formátu JPEG
			
			if($full_size[0]>$full_size[1]){  //Pokud je šíøka vìtší než výška
				if($full_size[0]>$max_sirka){
					$sirka=$max_sirka;       //Šíøka se rovná max. velikosti náhledu
					$vyska=intval(($max_sirka/$full_size[0])*$full_size[1]); //Nastavení výšky náhledu tak, aby byla v pùvodním pomìru k šíøce
				}else{//rozmery obrazku jsou mensi nez maximalni -> necham ho v puvodni velikosti
					$sirka = $full_size[0];
					$vyska = $full_size[1];
				}
			}else{                           //Pokud je výška vìtší než šíøka
				if($full_size[1]>$max_vyska){
					$vyska=$max_vyska;       //Šíøka se rovná max. velikosti náhledu
					$sirka=intval(($max_vyska/$full_size[1])*$full_size[0]); //Nastavení výšky náhledu tak, aby byla v pùvodním pomìru k šíøce
				}else{ //rozmery obrazku jsou mensi nez maximalni -> necham ho v puvodni velikosti
					$sirka = $full_size[0];
					$vyska = $full_size[1];
				}
			}
		
			$nova_fotka=ImageCreateTrueColor($sirka,$vyska); //vytvoøení nového True Color obrázku náhledu
			imagecopyresampled($nova_fotka,$full_fotka,0,0,0,0,$sirka,$vyska,$full_size[0],$full_size[1]); //zkopírování a zmenšení pùvodního obrázku do obrázku náhledu
			ImageJPEG($nova_fotka,"../".ADRESAR_NAHLED."/".$foto); //uložení obrázku do souboru do adresáøe pro náhledy ve formátu JPEG s dodržením 60 procentní kvality

			
			ImageDestroy($full_fotka); //odstranìní pùvodního obrázku z pamìti
			ImageDestroy($nova_fotka);     //odstranìní obrázku náhledu z pamìti
			
			//zjistime zda jsme skutecne fotku vytvorili
			$nove_foto_adress="../".ADRESAR_NAHLED."/".$foto;
			if(file_exists($nove_foto_adress)){
				return true;
			}
		}
		return false;
   }	
	
	/**vytvoreni maleho nahledu (ikony) k fotce - ma pevnou velikost 120x85*/
	function create_ico ($foto){
		$sirka=150;
		$vyska=110;

		$foto_adress="../".ADRESAR_FULL."/".$foto;
		
		if(file_exists($foto_adress)){ //pokud existuje soubor s obrázkem
			$full_size=GetImageSize($foto_adress); //zjištìní pùvodních rozmìrù obrázku
			$full_fotka=ImageCreateFromJPEG($foto_adress); //naètení pùvodního obrázku ve formátu JPEG
		
			$nova_fotka=ImageCreateTrueColor($sirka,$vyska); //vytvoøení nového True Color obrázku náhledu
			imagecopyresampled($nova_fotka,$full_fotka,0,0,0,0,$sirka,$vyska,$full_size[0],$full_size[1]); //zkopírování a zmenšení pùvodního obrázku do obrázku náhledu
			ImageJPEG($nova_fotka,"../".ADRESAR_IKONA."/".$foto); //uložení obrázku do souboru do adresáøe pro náhledy ve formátu JPEG s dodržením 60 procentní kvality

			ImageDestroy($full_fotka); //odstranìní pùvodního obrázku z pamìti
			ImageDestroy($nova_fotka);     //odstranìní obrázku náhledu z pamìti
			
			//zjistime zda jsme skutecne fotku vytvorili
			$nove_foto_adress="../".ADRESAR_IKONA."/".$foto;
			if(file_exists($nove_foto_adress)){
				return true;
			}
		}
		return false;
   }	
	/**vytvoreni miniikony k fotce - ma pevnou velikost 80*55*/
	function create_miniico ($foto){
		$sirka=80;
		$vyska=55;

		$foto_adress="../".ADRESAR_FULL."/".$foto;
		
		if(file_exists($foto_adress)){ //pokud existuje soubor s obrázkem
			$full_size=GetImageSize($foto_adress); //zjištìní pùvodních rozmìrù obrázku
			$full_fotka=ImageCreateFromJPEG($foto_adress); //naètení pùvodního obrázku ve formátu JPEG
		
			$nova_fotka=ImageCreateTrueColor($sirka,$vyska); //vytvoøení nového True Color obrázku náhledu
			imagecopyresampled($nova_fotka,$full_fotka,0,0,0,0,$sirka,$vyska,$full_size[0],$full_size[1]); //zkopírování a zmenšení pùvodního obrázku do obrázku náhledu
			ImageJPEG($nova_fotka,"../".ADRESAR_MINIIKONA."/".$foto); //uložení obrázku do souboru do adresáøe pro náhledy ve formátu JPEG s dodržením 60 procentní kvality

			ImageDestroy($full_fotka); //odstranìní pùvodního obrázku z pamìti
			ImageDestroy($nova_fotka);     //odstranìní obrázku náhledu z pamìti
			
			//zjistime zda jsme skutecne fotku vytvorili
			$nove_foto_adress="../".ADRESAR_MINIIKONA."/".$foto;
			if(file_exists($nove_foto_adress)){
				return true;
			}
		}
		return false;
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

		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}			

		}else if($typ_pozadavku == "delete"){
			if( $zamestnanec->get_bool_prava($id_modul,"delete_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		} else if ($typ_pozadavku == "mass_del") {
                    if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                            ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                        return true;
                    } else {
                        return false;
                    }
                }

		//neznámý požadavek zakážeme
		return false;
	}

	/**kontrola zda mam odpovidajici data*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovaná data: název typ/název destinace, id_zeme (u destinací)
		if($typ_pozadavku == "create" or $typ_pozadavku == "update"){
			if(!Validace::text($this->nazev_foto) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název fotografie");
			}					
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}
		
	
	/**zobrazeni formulare pro vytvoreni/editaci fotografie*/
	function show_form(){
		if($this->typ_pozadavku == "new"){
			$povinne_foto =" <span class=\"red\">*</span>";
      if($this->id_zeme <= 0 ){
         $this->id_zeme = $_SESSION["last_created_zeme"];
         $this->id_destinace = $_SESSION["last_created_destinace"];
      }
      
		}
		//vytvorim jednotliva pole
		$nazev="<div class=\"form_row\"> <div class=\"label_float_left\">název fotografie: <span class=\"red\">*</span></div><div class=\"value\"> <input name=\"nazev_foto\" type=\"text\" value=\"".$this->nazev_foto."\" class=\"width-500px\"/></div></div>\n";
		$popisek="<div class=\"form_row\"> <div class=\"label_float_left\">popisek fotografie:</div><div class=\"value\"> <textarea name=\"popisek_foto\" rows=\"3\" cols=\"100\">".$this->popisek_foto."</textarea></div></div>\n";
		$foto="<div class=\"form_row\"> <div class=\"label_float_left\">zadejte fotografii:".$povinne_foto."</div><div class=\"value\"> <input name=\"foto\" type=\"file\" size=\"56\" accept=\"image/jpg\"/>	</div></div>\n";


		//tvorba select_zeme_destinace
		$zeme="<div class=\"form_row\"> <div class=\"label_float_left\">Zemì a destinace fotky:</div>
					<div class=\"value\">
					<select name=\"zeme-destinace\">\n";
		//do promenne typy_serialu vytvorim instanci tridy seznam zemi
		$zeme_foto = new Zeme_list($this->id_zamestnance,"",$this->id_zeme,$this->id_destinace);
		//vypisu seznam zemi
		$zeme = $zeme.$zeme_foto->show_list("select_zeme_destinace");	
		$zeme=$zeme."</select>\n</div></div>\n";	
		
		//tvorba select zeme (pouze pri novem serialu)
		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=foto&amp;pozadavek=create";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "<input type=\"submit\" value=\"Vytvoøit fotografii\" />\n";	
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení fotografie</strong>\n";
			}
		}else if($this->typ_pozadavku=="edit"){	
			//cil formulare
			$action="?id_foto=".$this->id_foto."&amp;typ=foto&amp;pozadavek=update";
			
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni editovat dokument!
				//tzn pokud bud ma pravo editovat libovolny dokument, nebo pokud je to jeho dokument a on ma pravo ho editovat
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit fotografii\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte pdostateèné oprávnìní k editaci teto fotografie</strong>\n";
			}
		}
		
		$vystup= "<form action=\"".$action."\" method=\"post\" enctype=\"multipart/form-data\">".
						$nazev.$popisek.$foto.$zeme.$submit.
					"</form>";
		return $vystup;
	}
	
	
	function get_id() { return $this->serial["id_serial"];}
	function get_nazev() { return $this->serial["nazev"];}
	
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_foto == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}	

} 




?>
