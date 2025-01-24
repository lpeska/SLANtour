<?php
/**
* zajezd.inc.php - trida pro zobrazeni zajezdu
*/

/*--------------------- SERIAL -------------------------------------------*/
class Zajezd extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat

	protected $id_serial;
	protected $id_zajezd;
        protected $id_zapas;
        protected $id_cena;
	protected $od;
	protected $do;
	protected $hit_zajezd;
	protected $poznamky_zajezd;
	protected $nazev_zajezdu;
	protected $nezobrazovat;
        protected $cena_pred_akci;
        protected $akcni_cena;
        protected $popis_akce;
        protected $provizni_koeficient;

	protected $data;
	protected $zajezd;
	protected $ceny_zajezdu;//trida pro zobrazeni cen zajezdu - pouziva se u typ_pozadavku=new

	public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct($typ_pozadavku,$id_serial,$id_zajezd="",$id_zapas="",$od="",$do="",$hit_zajezd="",$poznamky_zajezd="",$nazev_zajezdu="",$nezobrazovat="",$cena_pred_akci="",$akcni_cena="",$popis_akce="",$provizni_koeficient="",$minuly_pozadavek=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->minuly_pozadavek = $this->check($minuly_pozadavek);
		$this->id_serial = $this->check_int($id_serial);
		$this->id_zajezd = $this->check_int($id_zajezd);
                $this->id_zapas = $this->check_int($id_zapas);

		$this->od = $this->change_date_cz_en( $this->check($od) );
		$this->do = $this->change_date_cz_en( $this->check($do) );
		$this->hit_zajezd = $this->check_int($hit_zajezd);
		$this->poznamky_zajezd = $this->check_slashes( $this->my_nl2br( $this->check_with_html($poznamky_zajezd) ) );
		$this->nazev_zajezdu = $this->check_slashes( $this->check_with_html($nazev_zajezdu) );
		$this->nezobrazovat = $this->check_int($nezobrazovat);
                $this->cena_pred_akci = $this->check_int($cena_pred_akci);
                $this->akcni_cena = $this->check_int($akcni_cena);
                $this->popis_akce = $this->check_slashes( $this->check_with_html($popis_akce));
                $this->provizni_koeficient = $this->check($provizni_koeficient);
                $this->pocet_noci = 0;
                $this->export = "";
		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){

			if( $this->typ_pozadavku=="copy"){
			//ziskavani informaci ze soucasneho zajezdu
					$this->data=$this->database->query($this->create_query("show"))
		 				or $this->chyba("Chyba pøi dotazu do databáze show: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					$this->zajezd=mysqli_fetch_array($this->data);
                                            $this->id_zapas = $this->check_int($this->zajezd["id_zapas"]) ;
                                            if(isset($_POST["termin_od"])){
                                                $_POST["od"] = $_POST["termin_od"];
                                                $this->od = $this->change_date_cz_en($_POST["termin_od"]) ;
                                            }else{
                                                $this->od = $this->zajezd["od"] ;
                                            }
                                            if(isset($_POST["termin_do"])){
                                                $_POST["do"] = $_POST["termin_do"];
                                                $this->do = $this->change_date_cz_en($_POST["termin_do"]) ;
                                            }else{
                                                $this->do = $this->zajezd["do"] ;
                                            }
                                            $this->hit_zajezd = $this->check_int($this->zajezd["hit_zajezd"]);
                                            $this->pocet_noci = $this->check_int($this->zajezd["pocet_noci"]);
                                            $this->poznamky_zajezd = $this->zajezd["poznamky_zajezd"];
                                            $this->nazev_zajezdu = $this->zajezd["nazev_zajezdu"];
                                            $this->export = $this->zajezd["export"];
                                            $this->nezobrazovat = $this->check_int($this->zajezd["nezobrazovat_zajezd"]);
                                            $this->cena_pred_akci = $this->check_int($this->zajezd["cena_pred_akci"]);
                                            $this->akcni_cena = $this->check_int($this->zajezd["akcni_cena"]);
                                            $this->popis_akce = $this->zajezd["popis_akce"];
                                            $this->provizni_koeficient = $this->zajezd["provizni_koeficient"];

					//ceny
					$data_cena=$this->database->query($this->create_query("copy_ceny"))
		 				or $this->chyba("Chyba pøi dotazu do databáze copy_ceny: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					$i=0;
                                        //echo $this->create_query("copy_ceny");
					while($ceny = mysqli_fetch_array($data_cena)){
                                            //print_r($ceny);
                                            $i++;	
                                            $_POST["id_cena_".$i]=$ceny["id_cena"];
                                            //potrebuju vytvorit dotaz pro spravne ID ceny u noveho serialu - pokud kopirujeme i serial, bude ID ceny spatne:
                                            $sql_id_cena="select c2.id_cena from 
                                                                 cena_zajezd 
                                                            join cena c1 on (cena_zajezd.id_cena = c1.id_cena)
                                                            join cena c2 on (c1.nazev_ceny = c2.nazev_ceny and c1.poradi_ceny = c2.poradi_ceny )
                                                
                                                          where c2.id_serial=$this->id_serial and cena_zajezd.id_zajezd=$this->id_zajezd and cena_zajezd.id_cena=".$ceny["id_cena"]."";
                                            $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_id_cena);
                                            while ($row_id_cena = mysqli_fetch_array($query)) {
                                                //update id_ceny aby sedela s novym serialem
                                                $_POST["id_cena_".$i] = $row_id_cena["id_cena"];
                                            }
											
						$_POST["castka_".$i]=$ceny["castka"];
						$_POST["mena_".$i]=$ceny["mena"];
                                                $_POST["castka_euro_".$i] = $this->check_int($ceny["castka_euro"]);
						$_POST["kapacita_volna_".$i]=$ceny["kapacita_celkova"];
						$_POST["kapacita_celkova_".$i]=$ceny["kapacita_celkova"];
						$_POST["vyprodano_".$i]=$ceny["vyprodano"];
						$_POST["na_dotaz_".$i]=$ceny["na_dotaz"];
                                                $_POST["nezobrazovat_".$i]=$ceny["nezobrazovat"];
                                                $_POST["pouzit_cenu_".$i]=1;


                                                if($_POST["vytvorit_tok"]==1){
                                                    $this->id_cena = $ceny["id_cena"];
                                                    //echo $this->create_query("copy_tok");

                                                    $data_tok=$this->database->query($this->create_query("copy_tok"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: copy_tok ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

                                                    while($tok = mysqli_fetch_array($data_tok)){
                                                        $_POST["id_tok_".$i."_".$tok["id_objekt_kategorie"]]="new";
                                                        $_POST["kapacita_tok_".$i."_".$tok["id_objekt_kategorie"]]=$tok["kapacita_celkova"];
                                                        $_POST["je_vstupenka_".$i] = $tok["je_vstupenka"];
                                                    //echo $_POST["castka_euro_".$i];
                                                    }
                                                }

					}   //     print_r($_POST);
					$_POST["pocet"] = $i;
					$this->typ_pozadavku="create";
                                        //print_r ($_POST);
			}
			//predpokladana chyba: vymenim carku za tecku
			$this->provizni_koeficient = str_replace(",", ".", $this->provizni_koeficient);


			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
			if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update" or $this->typ_pozadavku=="update_dle_kv" or $this->typ_pozadavku=="soldout" or $this->typ_pozadavku=="delete" or $this->typ_pozadavku=="delete_with_objednavky"){
                            $start_transaction=1;
                            if($this->typ_pozadavku == "delete_with_objednavky"){
                                $query = "delete from `objednavka` where `id_zajezd`=".$this->id_zajezd." " ;
                                $d=$this->database->transaction_query($query, $start_transaction )
		 					or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                $this->typ_pozadavku = "delete";
                                $start_transaction=0;
                            }

                            if($this->typ_pozadavku == "create"){ //pouziju jinou funkci pro odeslani dotazu - vice dotazu v transakci
                                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku), 1 )
                                            or $this->chyba("Chyba pøi dotazu do databáze: create ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

                                    if( !$this->get_error_message() ){
                                            $this->id_zajezd = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                            $this->zajezd["id_zajezd"] = $this->id_zajezd;

                                            $dotaz2 = new Cena_zajezd("create",$this->id_serial,$this->id_zajezd,"",$_POST["pocet"],1);

                                            if(!$dotaz2->get_error_message()){
                                                    $i=1;

                                                    while($i <= $dotaz2->get_pocet() ){
                                                            $dotaz2->add_to_query($_POST["id_cena_".$i],$_POST["castka_".$i],$_POST["mena_".$i],$_POST["castka_euro_".$i],
                                                                            $_POST["kapacita_volna_".$i],$_POST["kapacita_celkova_".$i],
                                                                            $_POST["vyprodano_".$i],$_POST["na_dotaz_".$i],$_POST["pouzit_cenu_".$i],$_POST["nezobrazovat_".$i]);
                                                            
                                                            $i++;
                                                    }
                                                    $dotaz2->finish_query();
                                            }

                                            if($dotaz2->get_error_message()){
                                                    $this->chyba($dotaz2->get_error_message() );
                                            }else{
                                                    $this->database->commit(); //potvrdim transakci
                                                    $i=1;
                                                    while($i <= $dotaz2->get_pocet() ){
                                                            $dotaz2->remove_assignments_cenova_mapa($_POST["id_cena_".$i])   ;
                                                            $i++;
                                                    }
                                            }
                                    }

                            }else if($this->typ_pozadavku == "update_dle_kv"){ //pouziju jinou funkci pro odeslani dotazu - vice dotazu v transakci
                                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku), 1 )
                                            or $this->chyba("Chyba pøi dotazu do databáze: create ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

                                    if( !$this->get_error_message() ){

                                            $dotaz2 = new Cena_zajezd("update",$this->id_serial,$this->id_zajezd,"",$_POST["pocet"],1);

                                            if(!$dotaz2->get_error_message()){
                                                    $i=1;

                                                    while($i <= $dotaz2->get_pocet() ){
                                                            $dotaz2->add_to_query($_POST["id_cena_".$i],$_POST["castka_".$i],$_POST["mena_".$i],$_POST["castka_euro_".$i],
                                                                            $_POST["kapacita_volna_".$i],$_POST["kapacita_celkova_".$i],
                                                                            $_POST["vyprodano_".$i],$_POST["na_dotaz_".$i],$_POST["pouzit_cenu_".$i],$_POST["nezobrazovat_".$i],true);
                                                            
                                                            
                                                            $i++;
                                                    }
                                                    $dotaz2->finish_query();
                                            }

                                            if($dotaz2->get_error_message()){
                                                    $this->chyba($dotaz2->get_error_message() );
                                            }else{
                                                    $this->database->commit(); //potvrdim transakci
                                                    $i=1;
                                                    while($i <= $dotaz2->get_pocet() ){
                                                            $dotaz2->remove_assignments_cenova_mapa($_POST["id_cena_".$i]) ;
                                                            $i++;
                                                    }
                                            }
                                    }

                            }else{
                                    $this->data=$this->database->transaction_query($this->create_query($this->typ_pozadavku),$start_transaction )
                                            or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                    $this->database->commit();
                            }

                            //vygenerování potvrzovací hlášky
                            if( !$this->get_error_message() ){
                                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                            }

			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if( ($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update") or $this->typ_pozadavku=="show"){
					$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

					$this->zajezd=mysqli_fetch_array($this->data);
					//jednotlive sloupce ulozim do promennych tridy
						$this->id_serial = $this->zajezd["id_serial"];
						$this->id_zajezd = $this->zajezd["id_zajezd"];
                                                $this->id_zapas = $this->zajezd["id_zapas"];
						$this->od = $this->zajezd["od"];
						$this->do = $this->zajezd["do"];
						$this->hit_zajezd = $this->zajezd["hit_zajezd"];
						$this->poznamky_zajezd = $this->zajezd["poznamky_zajezd"];
						$this->nazev_zajezdu = $this->zajezd["nazev_zajezdu"];
                                                $this->export = $this->zajezd["export"];
						$this->nezobrazovat = $this->zajezd["nezobrazovat_zajezd"];
                                                $this->cena_pred_akci = $this->check_int($this->zajezd["cena_pred_akci"]);
                                                $this->akcni_cena = $this->check_int($this->zajezd["akcni_cena"]);
                                                $this->popis_akce = $this->zajezd["popis_akce"];
                                                $this->provizni_koeficient = $this->zajezd["provizni_koeficient"];
			}else if($this->typ_pozadavku=="new"){
				//id zajezdu v tuto chvili neznam, ale to nevadi - u dotazu pro zobrazeni formulare pro nove ceny neni potreba
				 $this->ceny_zajezdu = new Cena_zajezd("new",$this->id_serial,"");
			}
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
		}


	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="create"){
			$dotaz= "INSERT INTO `zajezd`
							(`id_serial`,`id_zapas`,`od`,`do`,`hit_zajezd`,`poznamky_zajezd`,`nazev_zajezdu`,`pocet_noci`,`export`,`nezobrazovat_zajezd`,`cena_pred_akci`,`akcni_cena`,`popis_akce`,`provizni_koeficient`, `last_change`)
						VALUES
							 (".$this->get_id_serial().",".$this->get_id_zapas().",'".$this->get_od()."','".$this->get_do()."',".$this->get_hit_zajezd().",'".$this->get_poznamky_zajezd()."','".$this->nazev_zajezdu."',
                                                             ".$this->pocet_noci.",'".$this->export."',".$this->nezobrazovat.",".$this->cena_pred_akci.",".$this->akcni_cena.",'".$this->popis_akce."','".$this->provizni_koeficient."','".Date("Y-m-d")."' )";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="update"){
			$dotaz= "UPDATE `zajezd`
						SET
							`id_zapas`=".$this->get_id_zapas().",`od`='".$this->get_od()."',`do`='".$this->get_do()."',`hit_zajezd`=".$this->get_hit_zajezd().",`poznamky_zajezd`='".$this->get_poznamky_zajezd()."',`nazev_zajezdu`='".$this->nazev_zajezdu."',`nezobrazovat_zajezd`=".$this->nezobrazovat."
                                                         ,`cena_pred_akci`=".$this->cena_pred_akci.",`akcni_cena`=".$this->akcni_cena.",`popis_akce`='".$this->popis_akce."',`provizni_koeficient`='".$this->provizni_koeficient."',`last_change`='".Date("Y-m-d")."'
						WHERE `id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
        
		}else if($typ_pozadavku=="soldout"){
			$dotaz= "UPDATE `cena_zajezd` SET `vyprodano`=1 WHERE `id_zajezd`=".$this->id_zajezd."";
			//echo $dotaz;
			return $dotaz;	

        }else if($typ_pozadavku=="update_dle_kv"){
			$dotaz= "UPDATE `zajezd`
						SET
							`od`='".$this->get_od()."',`do`='".$this->get_do()."',`last_change`='".Date("Y-m-d")."'
						WHERE `id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;                        
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `zajezd`
						WHERE `id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="edit"){
			$dotaz= "SELECT * FROM `zajezd`
						WHERE `id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="show"){
			$dotaz= "SELECT * FROM `zajezd`
						WHERE `id_zajezd`=".$this->id_zajezd."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="copy_ceny"){
                    //obcas se v databazi vyskytuji cena_zajezd takove, kde id ceny neodpovida id serialu soucasneho zajezdu (chyby ze spatneho kopirovani, bude chtit procistit, ale tohle to zatim zvlada)
			$dotaz="select distinct `cena_zajezd`.*
					  from `cena_zajezd` 
                                                join `zajezd` on (`cena_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                                join `cena` on (`cena_zajezd`.`id_cena` = `cena`.`id_cena` and `cena`.`id_serial` = `zajezd`.`id_serial`)
					where `cena_zajezd`.`id_zajezd`= ".$this->id_zajezd."
					";
			//echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="copy_tok"){
			$dotaz="select `cena_zajezd_tok`.*,`objekt_kategorie_termin`.*
					  from `cena_zajezd` join `cena_zajezd_tok` on (`cena_zajezd`.`id_cena` = `cena_zajezd_tok`.`id_cena`
                                                                                         and `cena_zajezd`.`id_zajezd` = `cena_zajezd_tok`.`id_zajezd`)
                                                             join `objekt_kategorie_termin` on (`objekt_kategorie_termin`.`id_termin` = `cena_zajezd_tok`.`id_termin`
                                                                                         and `objekt_kategorie_termin`.`id_objekt_kategorie` = `cena_zajezd_tok`.`id_objekt_kategorie`)                            
					where `cena_zajezd`.`id_zajezd`= ".$this->id_zajezd." and `cena_zajezd`.`id_cena` = ".$this->id_cena."
					";
			//echo $dotaz;
			return $dotaz;
                        
                     
                        
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `serial`
						WHERE `id_serial`=".$this->id_serial."
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
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "create"){
			//tvorba casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}
		}else if($typ_pozadavku == "copy"){
			//tvorba casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}
		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}
                }else if($typ_pozadavku == "update_dle_kv"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}        
		}else if($typ_pozadavku == "delete" or $typ_pozadavku == "soldout" ){
			//delete casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}
		}else if($typ_pozadavku == "delete_with_objednavky"){
			//delete casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
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
		//kontrolovane pole id_serial, od, do
		if($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "update_dle_kv"){
			if(!Validace::int_min($this->id_serial,1) ){
				$ok = 0;
				$this->chyba("Seriál není identifikován");
			}
			if(!Validace::datum_en($this->od) ){
				$ok = 0;
				$this->chyba("Datum odjezdu není ve tvaru dd.mm.RRRR");
			}
			if(!Validace::datum_en($this->do) ){
				$ok = 0;
				$this->chyba("Datum pøíjezdu není ve tvaru dd.mm.RRRR");
			}
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}

	/**zobrazeni menu - moznosti editace pro konkretni zajezd*/
	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];

		$vypis="Zájezd ".CommonUtils::czechDate($this->get_od())." - ".CommonUtils::czechDate($this->get_do()).":
						<a href=\"".$adresa_modulu."?id_serial=".$this->get_id_serial()."&amp;id_zajezd=".$this->get_id_zajezd()."&amp;typ=zajezd&amp;pozadavek=edit\">zájezd</a>
					 		<a href=\"".$adresa_modulu."?id_serial=".$this->get_id_serial()."&amp;id_zajezd=".$this->get_id_zajezd()."&amp;typ=cena_zajezd\">ceny zájezdu</a>
					 		<a href=\"".$adresa_modulu."?id_serial=".$this->get_id_serial()."&amp;id_zajezd=".$this->get_id_zajezd()."&amp;typ=slevy_zajezd\">slevy</a>
                                                         <a href=\"" . $adresa_modulu . "?id_serial=" . $this->get_id_serial() . "&amp;id_zajezd=" . $this->get_id_zajezd() . "&amp;typ=topologie&amp;pozadavek=show\">topologie</a>   ";
                

		if($adresa_objednavky = $core->get_adress_modul_from_typ("objednavky") ){
			$vypis = $vypis."       <a href=\"/admin/rezervace.php?typ=rezervace&pozadavek=new-objednavka&id_serial=" . $this->get_id_serial(). "&id_zajezd=" . $this->get_id_zajezd()  . "\">nová objednávka</a>
							<a href=\"".$adresa_objednavky."?id_serial=".$this->get_id_serial()."&amp;id_zajezd=".$this->get_id_zajezd()."&amp;typ=rezervace_list\">zobrazit objednávky</a>";
		}
		$vypis = $vypis."<a class='action-delete' href=\"".$adresa_modulu."?id_serial=".$this->get_id_serial()."&amp;id_zajezd=".$this->get_id_zajezd()."&amp;typ=zajezd&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>";
		return $vypis;
	}
	/**zobrazeni formulare pro vytvoreni/editaci zajezdu*/
	function show_form(){
                $disabled = "";

                $sql = "select `id_ridici_objekt`, `nazev_objektu`  from `serial` join `objekt` on (`serial`.`id_ridici_objekt` = `objekt`.`id_objektu`) where `id_serial`=".$this->id_serial." ";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row = mysqli_fetch_array($data)) {
                    $warning= "<br/><strong style=\"color:red;\">Tento seriál je podøízen objektu <a href=\"/admin/objekty.php?id_objektu=".$row["id_ridici_objekt"]."&typ=tok_list&pozadavek=show\">".$row["nazev_objektu"]."</a>. Nìkteré parametry zájezdù není možné editovat. </strong>";
                    $disabled = "disabled=\"disabled\"";
                }


		//vytvorim jednotliva pole
		$od="<div class='form_row'><div class='label_float_left'>odjezd: <span class=\"red\">*</span></div><div class='value'><input id=\"zajezd_termin_od\" class=\"date calendar-ymd\"  name=\"od\" type=\"text\" value=\"".CommonUtils::czechDate($this->get_od())."\"  ".$disabled." /></div></div>";
		$do="<div class='form_row'><div class='label_float_left'>pøíjezd: <span class=\"red\">*</span></div><div class='value'><input id=\"zajezd_termin_do\" class=\"date calendar-ymd\"  name=\"do\" type=\"text\" value=\"".CommonUtils::czechDate($this->get_do())."\"  ".$disabled."  /></div></div>";
		if($this->get_hit_zajezd()){
			$hit_zajezd="<div class='form_row'><div class='label_float_left'>hit zájezd:</div><div class='value'><input name=\"hit_zajezd\" type=\"checkbox\" value=\"1\" checked=\"checked\" /></div></div>";
		}else{
			$hit_zajezd="<div class='form_row'><div class='label_float_left'>hit zájezd:</div><div class='value'><input name=\"hit_zajezd\" type=\"checkbox\" value=\"1\" /></div></div>";
		}
          	$provizni_koeficient="<div class='form_row'><div class='label_float_left'>provizní koeficient:</div><div class='value'><input name=\"provizni_koeficient\" type=\"text\" value=\"".(($this->provizni_koeficient!="")?($this->provizni_koeficient):("1"))."\" /></div></div>";

            /*    $dotaz_zapas = "select `id_zapas`, `nazev`, `datum` from `zapas` where `datum` >= \"".Date("Y-m-d")."\" order by `datum`, `nazev`";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_zapas);
                $zapas = "
                        <tr><td>zápas (pouze hokej, fotbal atp.):<td>
                        <select name=\"id_zapas\">
                            <option value=\"0\">---</option> ";
                while($row = mysqli_fetch_array($data)){
                   $zapas .= "<option ".(($row["id_zapas"]==$this->id_zapas)?("selected=\"selected\""):(""))." value=\"".$row["id_zapas"]."\">".$this->change_date_en_cz($row["datum"]).": ".$row["nazev"]."</option> ";
                }
                $zapas .= "</select>
                        ";*/

		$poznamky="<div class='form_row'><div class='label_float_left'>poznamky:</div><div class='value'><textarea name=\"poznamky_zajezd\" rows=\"4\" cols=\"100\">".$this->get_poznamky_zajezd()."</textarea></div></div>";
		$nazev_zajezdu="<div class='form_row'><div class='label_float_left'>Název zájezdu (termínu):</div><div class='value'><input name=\"nazev_zajezdu\" type=\"text\" class=\"width-500px\" value=\"".$this->nazev_zajezdu."\" ".$disabled."/></div></div>";
		$nezobrazovat="<div class='form_row'><div class='label_float_left'>Nezobrazovat zájezd</div><div class='value'>Zaškrtnìte, pokud nechcete, aby se seriál zobrazoval klientùm<br/><input type=\"checkbox\" name=\"nezobrazovat\" value=\"1\" ".(($this->nezobrazovat==1)?("checked=\"checked\""):(""))." /></div></div>";
                $javascript="
                    <script type=\"text/javascript\">
            function updateDateArrival(){
                var valTerminDo = $( \"#zajezd_termin_do\").val();
                if(valTerminDo == \"\"){
                     $(\"#zajezd_termin_do\").val($(\"#zajezd_termin_od\").val())
                }
            }
                        $(document).ready(function () {
                            $(\"#zajezd_termin_od,#zajezd_termin_do\").change(function(){
                                searchTOK($this->id_serial);
                                updateDateArrival();                                    
                            });
                        });
			 function count_sleva(){
				var cena_pred = document.zajezd.cena_pred_akci.value;
				var akcni_cena = document.zajezd.akcni_cena.value;
                                if(cena_pred>0 && akcni_cena>0){
                                    var sleva = Math.round( (1 - (akcni_cena / cena_pred ) ) * 100);
                                    var y = document.getElementById(\"velikost_slevy\");
                                    y.innerHTML = \"<b>\"+sleva+\" %</b>\";
                                }
			}
                        </script>
			";

                $akcni_nabidka="
                        <div class='form_row'>Marketingová nabídka - zobrazí se na upoutávkách k zájezdu (napø. na lazenske-pobyty.info), ale nepromítá se do výpoètu ceny objednávky - musí k ní být odpovídajícím zpùsobem doplnìny služby a ceny zájezdu...</p></div>
                        <div class='form_row'><div class='label_float_left'>Cena pøed akcí:</div><div class='value'><input name=\"cena_pred_akci\" onChange=\"count_sleva()\" type=\"text\" value=\"".$this->cena_pred_akci."\" /> Kè</div></div>
                        <div class='form_row'><div class='label_float_left'>Akèní cena:</div><div class='value'><input name=\"akcni_cena\" onChange=\"count_sleva()\" type=\"text\" value=\"".$this->akcni_cena."\" /> Kè<br/>
                                             Sleva: <span id=\"velikost_slevy\"></span></div></div>
                        <div class='form_row'><div class='label_float_left'>Popis akce:</div><div class='value'><textarea name=\"popis_akce\" rows=\"4\" cols=\"100\">".$this->popis_akce."</textarea></div></div>";

		if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?id_serial=".$this->get_id_serial()."&amp;typ=zajezd&amp;pozadavek=create";
			if( $this->legal("create") ){
					$submit= "<input type=\"submit\" value=\"Vytvoøit zájezd\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení zájezdu</strong>\n";
			}

		}else if($this->typ_pozadavku=="edit"){
			//cil formulare
			$action="?id_serial=".$this->get_id_serial()."&amp;id_zajezd=".$this->get_id_zajezd()."&amp;typ=zajezd&amp;pozadavek=update";

			if( $this->legal("create") ){
					$submit= "<input type=\"submit\" value=\"Upravit zájezd\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto zájezdu</strong>\n";
			}
		}
        

		$vystup= $warning."<form action=\"".$action."\" name=\"zajezd\" method=\"post\">".$javascript."
                        <div class=\"tabs\">
                             <div id=\"povinne_udaje\"> 
                                <table>".
						$nazev_zajezdu.$od.$do.$hit_zajezd.$poznamky.$nezobrazovat.$provizni_koeficient
                            ."  </table>
                              </div>
                              <div id=\"akce\">
                                <h3>Akèní nabídka</h3>
                                <table>
                                    ".$akcni_nabidka."
                                </table>        
                              </div>
                        ";

		if($this->typ_pozadavku=="new"){
                        //zjistim u vsech sluzeb, zda maji prirazene kalkulacni vzorce a pripadne zobrazim data z nich.
                        $loaded_data = Cena_serial::load_kv_data($this->id_serial);
                        $script_kv = $loaded_data[0];
                    
			$vystup= $script_kv.$vystup."<h3>Služby</h3><div id=\"sluzby_zajezdu\">".$this->ceny_zajezdu->show_form("new_zajezd")."</div>";
		}
                $vystup .= "</div>";
		$vystup=$vystup.$submit."</form><br/>
		* název zájezdu se zobrazí pod názvem seriálu - využití hlavnì pro zápasy premier league apod.";

		return $vystup;
	}
	function get_id_serial() { return $this->id_serial;}
	function get_id_zajezd() { return $this->id_zajezd;}
        function get_id_zapas() { return $this->id_zapas;}
	function get_od() { return $this->od;}
	function get_do() { return $this->do;}
	function get_hit_zajezd() { return $this->hit_zajezd;}
	function get_poznamky_zajezd() { return $this->poznamky_zajezd;}
        function get_id_topologie_zajezdu($id_zajezdu){
            $id_zajezdu = $this->check_int($id_zajezdu);
            $query="SELECT id_topologie 
                        FROM `topologie_tok` 
                            join `zajezd_tok_topologie` on (zajezd_tok_topologie.id_tok_topologie = topologie_tok.id_tok_topologie)
                        where id_zajezd=$id_zajezdu";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            while ($row = mysqli_fetch_array($data)) {
                return $row["id_topologie"];
            }
        }
	function get_id_user_create() {
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_serial == 0){
			return $this->id_zamestnance;
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) );
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}
}




?>
