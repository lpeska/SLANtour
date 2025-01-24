<?php
/**
* klient.inc.php - tridy pro zobrazeni informcí o klientovi
*/

/*--------------------- SERIAL -------------------------------------------*/
class Objekty extends Generic_data_class{
	//vstupni data
	protected $typ_pozadavku;
	protected $minuly_pozadavek;	//nepovinny udaj, znaci zda byl formular spatne vyplnen -> ovlivnuje napr. nacitani dat
	protected $id_zamestnance;

	protected $id_klient;

	protected $jmeno;
	protected $prijmeni;
	protected $titul;
	protected $datum_narozeni;
	protected $rodne_cislo;

	protected $email;
	protected $telefon;
	protected $cislo_op;
	protected $cislo_pasu;

	protected $ulice;
	protected $mesto;
	protected $psc;

	protected $vytvoren_ck;

	protected $uzivatelske_jmeno;
	protected $salt;
	protected $heslo;
	protected $heslo2;

        protected $last_id_objekt_kategorie;

	protected $data;
	protected $user;

	public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
	/*konstruktor tøídy na základì typu požadavku a formularovych poli*/
	function __construct(
		$typ_pozadavku, $id_zamestnance, $id_objektu=""
	){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_objektu = $this->check_int($id_objektu);
		$this->typ_pozadavku = $this->check($typ_pozadavku);


		//pokud mam dostatecna prava pokracovat
		if($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)){
                        if($this->typ_pozadavku=="kalkulacni_vzorce_update" ){
                            $this->update_kv();
     
                        }else if($this->typ_pozadavku=="ajax_get_goglobal_ceny" ){
                            $this->ajax_get_goglobal_ceny();	
                            
                        }else if($this->typ_pozadavku=="create" or $this->typ_pozadavku=="update"){
			//pro pozadavky create,  update, a delete je treba poslat dotaz do databaze
                                                            //create organizace
                            $this->nazev = $this->check($_POST["nazev_objektu"]);
                            $this->kratky_nazev = $this->check($_POST["kratky_nazev_objektu"]);
                            $this->id_organizace = $this->check($_POST["id_organizace"]);
                            $this->typ_objektu = $this->check_int($_POST["typ_objektu"]);
                            $this->popis_objektu = $this->check_with_html($_POST["popis_objektu"]);
                            $this->poznamka = $this->check_with_html($_POST["poznamka"]);


                            if($this->typ_pozadavku=="create"){
                                        $data = $this->database->transaction_query($this->create_query("create_objekt"),1)
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					$this->id_objektu = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);

                            }else if($this->typ_pozadavku=="update"){
                               // print_r($_POST);
                                    //upravit data o organizaci
                                        $data = $this->database->transaction_query($this->create_query("update_objekt"),1)
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->create_query("update_objekt") );
                                    //smazat vsechny stara data
                                       // $this->deleteDataOrganizace();       takhle to nepujde
                            }

                                        $max_rows = 50;

                           /*uprava zavislych objektu*/
                            $sql = "SELECT `serial`.`id_serial` FROM `serial` join `objekt_serial` on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                        where `objekt_serial`.`id_objektu`=".$this->id_objektu." and `id_ridici_objekt`=".$this->id_objektu."";
                                //echo $sql;
                            $id_serial_array = array();
                            $data_serial = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql) ;
                            while ($row_serial = mysqli_fetch_array($data_serial)) {
                                    $id_serial_array[] = $row_serial["id_serial"];
                            }
                           //zadam objektove kategorie
                                        for($i=1; $i<$max_rows; $i++){
                                            if(isset ($_POST["ok_nazev_kategorie_".$i])){
                                                $this->ok["id_objekt_kategorie"] = $this->check_int($_POST["id_objekt_kategorie_".$i]);
                                                $this->ok["nazev"] = $this->check($_POST["ok_nazev_kategorie_".$i]);
                                                $this->ok["kratky_nazev"] = $this->check($_POST["ok_kratky_nazev_kategorie_".$i]);
                                                $this->ok["cizi_nazev"] = $this->check($_POST["ok_cizi_nazev_kategorie_".$i]);
                                                $this->ok["goglobal_hotel_id_ok"] = $this->check($_POST["goglobal_hotel_id_ok_".$i]);
                                                $this->ok["zakladni_kategorie"] = $this->check_int($_POST["ok_zakladni_kategorie_".$i]);
                                                $this->ok["hlavni_kapacita"] = $this->check_int($_POST["ok_hlavni_kapacita_".$i]);
                                                $this->ok["vedlejsi_kapacita"] = $this->check_int($_POST["ok_vedlejsi_kapacita_".$i]);
                                                $this->ok["ok_jako_celek"] = $this->check_int($_POST["ok_jako_celek_".$i]);
                                                $this->ok["poznamka"] = $this->check_with_html($_POST["ok_poznamka_kategorie_".$i]);
                                                $this->ok["popis_kategorie"] = $this->check_with_html($_POST["ok_popis_kategorie_".$i]);
                                                $this->ok["smazat"] = $this->check_with_html($_POST["ok_smazat_".$i]);
                                                if($this->ok["kratky_nazev"]!=""){
                                                    $zkraceny_vypis = 1;
                                                }else{
                                                    $zkraceny_vypis = 0;
                                                }

                                                $sql_insert_map = array();
                                                foreach ($id_serial_array as $key => $id_serial) {

                                                    if($this->ok["smazat"]==1){
                                                        //OK se ruší, je tøeba vymazat pøíslušné služby
                                                        $sql_delete = "SELECT * FROM
                                                             `cena_objekt_kategorie`
                                                            join `cena` on ( `cena`.`id_cena` = `cena_objekt_kategorie`.`id_cena`  and `id_serial` = ".$id_serial.")
                                                            where `cena_objekt_kategorie`.`id_objekt_kategorie`=".$this->ok["id_objekt_kategorie"] ." limit 1" ;
                                                        $data_cena_delete = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_delete);
                                                       // echo $sql_delete;
                                                        while ($row_cena_delete = mysqli_fetch_array($data_cena_delete)) {
                                                            $sql_delete_cena = "DELETE FROM `cena` WHERE `id_cena`=".$row_cena_delete["id_cena"]." limit 1";
                                                           // echo $sql_delete_cena;
                                                            mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_delete_cena);
                                                        }
                                                    }else if($this->ok["id_objekt_kategorie"]<=0){
                                                        //nová OK
                                                        $sql_insert_sluzba = "INSERT INTO `cena` (`id_serial`,`nazev_ceny`,`kratky_nazev`,`nazev_ceny_en`,`kratky_nazev_en`,`zkraceny_vypis`,`poradi_ceny`,`typ_ceny`,`zakladni_cena`,`kapacita_bez_omezeni`,`use_pocet_noci`) VALUES
                                                                                (".$id_serial.",\"".$this->ok["nazev"]."\",\"".$this->ok["kratky_nazev"]."\",\"".$this->ok["cizi_nazev"]."\",\"".$this->ok["cizi_nazev"]."\",
                                                                                    ".$zkraceny_vypis.",".($i+100).",1,0,0,0)";
                                                        mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_insert_sluzba);
                                                        $id_cena = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                                        $sql_insert_map[] = "INSERT INTO `cena_objekt_kategorie`(`id_cena`, `id_objekt_kategorie`) VALUES (".$id_cena.",[id_OK])";
                                                        //mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_insert_map) ;

                                                    }else{ //update OK
                                                        $sql_get_sluzba = "SELECT * FROM  `objekt_kategorie`
                                                            join `cena_objekt_kategorie` on ( `objekt_kategorie`.`id_objekt_kategorie` = `cena_objekt_kategorie`.`id_objekt_kategorie`) 
                                                            join `cena` on ( `cena`.`id_cena` = `cena_objekt_kategorie`.`id_cena`  and `id_serial` = ".$id_serial.")
                                                            where `objekt_kategorie`.`id_objekt_kategorie`=".$this->ok["id_objekt_kategorie"] ." limit 1" ;
                                                        $data_sluzba = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_get_sluzba) ;

                                                        while ($row_sluzba = mysqli_fetch_array($data_sluzba)) {
                                                            $sql_update_cena = "UPDATE `cena` SET  `nazev_ceny_en`=\"".$this->ok["cizi_nazev"]."\",`nazev_ceny`=\"".$this->ok["nazev"]."\",`kratky_nazev`=\"".$this->ok["kratky_nazev"]."\"
								WHERE `id_cena`=".$row_sluzba["id_cena"]." LIMIT 1";
                                                            mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_update_cena);
                                                        }

                                                    }

                                                }
                                                $this->database->transaction_query($this->create_query("create_ok"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni). $this->create_query("create_ok"));
                                              //  echo $this->create_query("create_ok");
                                             //   echo mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                                $this->last_id_objekt_kategorie = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                                foreach($sql_insert_map as $sql_insert){
                                                    $sql_insert = str_replace("[id_OK]", mysqli_insert_id($GLOBALS["core"]->database->db_spojeni), $sql_insert);
                                                    mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_insert);
                                                }
                                            }
                                        }


                                        if($this->typ_objektu==1){
                                            //vytvorit ubytovani
                                            $this->ubytovani["id_objektu"] = $this->id_objektu;
                                            $this->ubytovani["nazev_ubytovani"] = $this->check($_POST["nazev_ubytovani"]);
                                            $this->ubytovani["nazev_ubytovani_web"] = $this->check($_POST["nazev_ubytovani_web"]);
                                            $this->ubytovani["popis_poloha"] = $this->check_with_html($_POST["popis_poloha"]);
                                            $this->ubytovani["pokoje_ubytovani"] = $this->check_with_html($_POST["pokoje_ubytovani"]);
                                            $this->ubytovani["pes"] = $this->check_int($_POST["pes"]);
                                            $this->ubytovani["pes_cena"] = $this->check($_POST["pes_cena"]);
                                            $this->ubytovani["posX"] = $this->check($_POST["posX"]);
                                            $this->ubytovani["posY"] = $this->check($_POST["posY"]);
                                            $this->ubytovani["typ_ubytovani"] = $this->check_int($_POST["typ_ubytovani"]);
                                            $this->ubytovani["kategorie_ubytovani"] = $this->check($_POST["kategorie_ubytovani"]);
                                            $this->ubytovani["goglobal_hotel_id"] = $this->check($_POST["goglobal_hotel_id"]);
                                            $this->ubytovani["highlights"] = $this->check($_POST["highlights"]);

                                            if($this->ubytovani["nazev_ubytovani_web"]==""){
                                                $this->ubytovani["nazev_ubytovani_web"]= $this->ubytovani["nazev_ubytovani"];
                                            }
                                            $this->ubytovani["nazev_ubytovani_web"] = $this->nazev_web($this->ubytovani["nazev_ubytovani_web"]);

                                            $this->database->transaction_query($this->create_query("create_ubytovani"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->create_query("create_ubytovani") );

                                        }else if($this->typ_objektu==3){
                                            //vytvorit ubytovani
                                            $this->vstupenka["id_objektu"] = $this->id_objektu;
                                            $this->vstupenka["sport"] = $this->check_with_html($_POST["sport"]);
                                            $this->vstupenka["kod"] = $this->check_with_html($_POST["kod"]);
                                            $this->vstupenka["akce"] = $this->check_with_html($_POST["akce"]);

                                            $this->database->transaction_query($this->create_query("create_vstupenky"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                        }else if($this->typ_objektu==5){
                                            //vytvorit letenku pres Asianu
                                            $this->letenka["id_objektu"] = $this->id_objektu;
                                            $this->letenka["flight_from"] = $this->check_with_html($_POST["flight_from"]);
                                            $this->letenka["flight_to"] = $this->check_with_html($_POST["flight_to"]);
                                            $this->letenka["flight_direct"] = $this->check_with_html($_POST["flight_direct"]);
                                            $this->letenka["automaticka_kontrola_cen"] = $this->check_int($_POST["automaticka_kontrola_cen"]);
                                            //TODO
                                            $this->database->transaction_query($this->create_query("create_letenka"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                        }

					//vygenerování potvrzovací hlášky
					if( !$this->get_error_message() ){
                                                $this->database->commit();
						$this->confirm("Požadovaná akce probìhla úspìšnì");
					}else{
                                            $this->database->rollback();
                                        }


			//pro pozadavky edit a show je treba poslat dotaz do databaze a nasledne zpracovat vystup do promennych tridy
			}else if($this->typ_pozadavku=="edit" and $this->minuly_pozadavek!="update" ){
                            $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("show"));
                            while ($row = mysqli_fetch_array($query)) {
                                $this->id_objektu = $row["id_objektu"];
                                $this->typ_objektu = $row["typ_objektu"];
                                $this->topologie = $row["topologie"];
                                $this->nazev = $row["nazev_objektu"];
                                $this->nazevSerialu = $row["nazev_serialu"];
                                $this->id_serialu = $row["id_serial"];
                            }

			}else if($this->typ_pozadavku=="delete"){
                            $this->database->query($this->create_query("delete"))
                                                        or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                        }
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
		}


	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){

                /*vytvoreni organizace*/
		if($typ_pozadavku=="create_objekt"){
                        if($this->id_organizace){
                            $objekt_nazev = "`id_organizace`,";
                            $objekt_value = "".$this->id_organizace.", ";
                        }else{
                            $objekt_nazev = "";
                            $objekt_value = "";
                        }
			$dotaz= "INSERT INTO `objekt`
							(`nazev_objektu`,`kratky_nazev_objektu`,".$objekt_nazev." `typ_objektu`, `popis_objektu`, `poznamka`, `id_user_create`,`id_user_edit`)
						VALUES
							 ('".$this->nazev."','".$this->kratky_nazev."',".$objekt_value." '".$this->typ_objektu."','".$this->popis_objektu."','".$this->poznamka."',
                                                             ".$this->id_zamestnance.",".$this->id_zamestnance.")";
			//echo $dotaz . "<br/>";
			return $dotaz;
		}else if($typ_pozadavku=="update_objekt"){
                        if($this->id_organizace){
                            $objekt = "`id_organizace` = ".$this->id_organizace.",";
                        }else{
                            $objekt = "`id_organizace` = NULL,";
                        }
			$dotaz= "UPDATE `objekt`  set
							`nazev_objektu`='".$this->nazev."',`kratky_nazev_objektu`='".$this->kratky_nazev."',".$objekt." `typ_objektu`='".$this->typ_objektu."',`popis_objektu`='".$this->popis_objektu."',
                                                            `poznamka`='".$this->poznamka."', `id_user_edit`=".$this->id_zamestnance."
						where
                                                   `id_objektu` = ". $this->id_objektu."
                                                limit 1       ";
			//echo $dotaz . "<br/>";
			return $dotaz;
		}else if($typ_pozadavku=="create_ok"){
                        if($this->ok["id_objekt_kategorie"]==0){
                            //create
                            $dotaz= "INSERT INTO `objekt_kategorie`
							(`id_objektu`,`nazev`,`kratky_nazev`,`cizi_nazev`,`goglobal_hotel_id_ok`, `zakladni_kategorie`,`hlavni_kapacita`,`vedlejsi_kapacita`,`popis_kategorie`,`prodavat_jako_celek`,`poznamka`)
						VALUES
							 (".$this->id_objektu.",'".$this->ok["nazev"]."','".$this->ok["kratky_nazev"]."','".$this->ok["cizi_nazev"]."','".$this->ok["goglobal_hotel_id_ok"]."',".$this->ok["zakladni_kategorie"].",".$this->ok["hlavni_kapacita"].",".$this->ok["vedlejsi_kapacita"]."
                                                             ,'".$this->ok["popis_kategorie"]."',".$this->ok["ok_jako_celek"].",'".$this->ok["poznamka"]."')";
                        }else if($this->ok["smazat"] == 1){
                            $dotaz= "DELETE from `objekt_kategorie`
						where
                                                   `id_objekt_kategorie` = ".$this->ok["id_objekt_kategorie"]."
                                                limit 1       ";
                        }else{
                            //update
                            $dotaz= "UPDATE `objekt_kategorie`  set
							`nazev`='".$this->ok["nazev"]."', `kratky_nazev`='".$this->ok["kratky_nazev"]."', `cizi_nazev`='".$this->ok["cizi_nazev"]."',`goglobal_hotel_id_ok`='".$this->ok["goglobal_hotel_id_ok"]."',`zakladni_kategorie`=".$this->ok["zakladni_kategorie"].",`hlavni_kapacita`=".$this->ok["hlavni_kapacita"].",
                                                            `vedlejsi_kapacita`=".$this->ok["vedlejsi_kapacita"].", `popis_kategorie`='".$this->ok["popis_kategorie"]."', `prodavat_jako_celek`=".$this->ok["ok_jako_celek"].",
                                                            `poznamka`='".$this->ok["poznamka"]."'
						where
                                                   `id_objekt_kategorie` = ".$this->ok["id_objekt_kategorie"]."
                                                limit 1       ";
                        }

			//echo $dotaz . "<br/>";
			return $dotaz;

                  }else if($typ_pozadavku=="create_ubytovani"){
			if($this->ubytovani["posX"]==0){
				$position_create = "";
				$position_create_values = "";
				$position_update = ",`posX`=NULL,`posY`=NULL";
			}else{
				$position_create = ", `posX`, `posY`";
				$position_create_values = ", ".$this->ubytovani["posX"].",".$this->ubytovani["posY"]."";
				$position_update = ",`posX`=".$this->ubytovani["posX"].",`posY`=".$this->ubytovani["posY"]."";

			}

			$dotaz= "INSERT INTO `objekt_ubytovani`(`id_objektu`, `nazev_ubytovani`, `nazev_web`, `popis_poloha`, `pokoje_ubytovani`, `typ_ubytovani`, `kategorie`, `goglobal_hotel_id`, `pes`, `pes_cena`, `highlights` $position_create )
						VALUES
							 (".$this->ubytovani["id_objektu"].",'".$this->ubytovani["nazev_ubytovani"]."','".$this->ubytovani["nazev_ubytovani_web"]."',
                                                          '".$this->ubytovani["popis_poloha"]."','".$this->ubytovani["pokoje_ubytovani"]."',".$this->ubytovani["typ_ubytovani"].",
                                                          ".$this->ubytovani["kategorie_ubytovani"].",'".$this->ubytovani["goglobal_hotel_id"]."',".$this->ubytovani["pes"].",\"".$this->ubytovani["pes_cena"]."\",
                                                          '".$this->ubytovani["highlights"]."' $position_create_values)
                                                On duplicate key UPDATE `nazev_ubytovani`='".$this->ubytovani["nazev_ubytovani"]."', `nazev_web`='".$this->ubytovani["nazev_ubytovani_web"]."', `popis_poloha`='".$this->ubytovani["popis_poloha"]."',
                                                         `pokoje_ubytovani`='".$this->ubytovani["pokoje_ubytovani"]."', `typ_ubytovani`=".$this->ubytovani["typ_ubytovani"].", `kategorie`=".$this->ubytovani["kategorie_ubytovani"].",
                                                         `goglobal_hotel_id` = '".$this->ubytovani["goglobal_hotel_id"]."', `pes`=".$this->ubytovani["pes"].", `pes_cena`=\"".$this->ubytovani["pes_cena"]."\", `highlights`='".$this->ubytovani["highlights"]."' $position_update";
			//echo $dotaz . "<br/>";
			return $dotaz;

                  }else if($typ_pozadavku=="create_letenka"){
                        if($this->letenka["flight_direct"] == ""){
                            $this->letenka["flight_direct"] = "0";                            
                        }
                        if($this->letenka["automaticka_kontrola_cen"] == ""){
                            $this->letenka["automaticka_kontrola_cen"] = "0";                            
                        }
                        
			$dotaz= "INSERT INTO `objekt_letenka`(`id_objektu`, `flight_from`, `flight_to`, `flight_direct`, `automaticka_kontrola_cen`)
						VALUES
							 (".$this->letenka["id_objektu"].",'".$this->letenka["flight_from"]."','".$this->letenka["flight_to"]."',
                                                          ".$this->letenka["flight_direct"].",".$this->letenka["automaticka_kontrola_cen"].")
                                                On duplicate key UPDATE `flight_from`='".$this->letenka["flight_from"]."', `flight_to`='".$this->letenka["flight_to"]."',
                                                         `flight_direct`=".$this->letenka["flight_direct"].",
                                                         `automaticka_kontrola_cen`=".$this->letenka["automaticka_kontrola_cen"]."";
			//echo $dotaz . "<br/>";
			return $dotaz;
                  }else if($typ_pozadavku=="create_vstupenky"){
			$dotaz= "INSERT INTO `objekt_vstupenka`(`id_objektu`, `sport`, `akce`, `kod`)
						VALUES
							 (".$this->vstupenka["id_objektu"].",'".$this->vstupenka["sport"]."','".$this->vstupenka["akce"]."','".$this->vstupenka["kod"]."')
                                                On duplicate key UPDATE `sport`='".$this->vstupenka["sport"]."', `akce`='".$this->vstupenka["akce"]."', `kod`='".$this->vstupenka["kod"]."'";
			//echo $dotaz . "<br/>";
			return $dotaz;

                        /*dotazy pro smazani pri updatu*/



                 /*dotazy pro zobrazeni a editaci*/
                 }else if($typ_pozadavku=="show"){
                                   
                     
			$dotaz= "SELECT `objekt`.*, 
                                    group_concat(concat(`topologie_tok`.`id_tok_topologie`,' ',`topologie_tok`.`id_topologie`) separator \";\") as `topologie`,
                                    group_concat(DISTINCT `serial`.`nazev` order by `serial`.`nazev` separator \";\") as `nazev_serialu`,
                                    group_concat(DISTINCT `serial`.`id_serial` order by `serial`.`nazev` separator \";\") as `id_serial`
                                    FROM `objekt` 
                                        left join `topologie_tok` on (`objekt`.`id_objektu` = `topologie_tok`.`id_objektu`)  
                                        left join (`objekt_serial` join `serial` on (`objekt_serial`.`id_serial` = `serial`.`id_serial`)
                                            )on(`objekt`.`id_objektu` = `objekt_serial`.`id_objektu`)
                                    WHERE `objekt`.`id_objektu`=".$this->id_objektu."
                                    GROUP BY `objekt`.`id_objektu`
                                    LIMIT 1";
			//echo $dotaz;
			return $dotaz;
                  }else if($typ_pozadavku=="show_ok"){
			$dotaz= "SELECT * FROM `objekt_kategorie`
						WHERE `id_objektu`=".$this->id_objektu."
						order by `id_objekt_kategorie`";
			//echo $dotaz;
			return $dotaz;
                  }else if($typ_pozadavku=="show_ubytovani"){
			$dotaz= "SELECT * FROM `objekt_ubytovani`
						WHERE `id_objektu`=".$this->id_objektu."
						";
//			echo $dotaz;
			return $dotaz;
                  }else if($typ_pozadavku=="show_vstupenky"){
			$dotaz= "SELECT * FROM `objekt_vstupenka`
						WHERE `id_objektu`=".$this->id_objektu."
						";
//			echo $dotaz;
			return $dotaz;
                        
                   }else if($typ_pozadavku=="show_letenky"){
			$dotaz= "SELECT * FROM `objekt_letenka`
						WHERE `id_objektu`=".$this->id_objektu."
						";
//			echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `objekt`
						WHERE `id_objektu`=".$this->id_objektu."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `organizace`
						WHERE `id_klient`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;
		}
	}

        function showSelectOrganizace($id_organizace=0, $role_organizace="(2,3,4,5)") {
            $query = "select * from `organizace` where `role` in ".$role_organizace." ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            $result = "<select name=\"id_organizace\">
                    <option value=\"0\">---</option>";
            while ($row = mysqli_fetch_array($data)) {
                if($id_organizace==$row["id_organizace"]){
                    $select = "selected=\"selected\"";
                }else{
                    $select = "";
                }
                $result.="<option value=\"".$row["id_organizace"]."\" ".$select.">".$row["nazev"]."(".$row["ico"].")</option>";
            }
            $result.="</select>";
            return $result;
        }

      	function show_submenu(){
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];

                if(Serial_library::get_typ_objektu($this->typ_objektu)=="Vstupenka"){		
                    $create_serial = " <a href=\"?id_objektu=".$this->id_objektu."&amp;typ=create_serial&amp;pozadavek=create_from_vstupenka\">vytvoøit seriál</a>";
                }else{
                    $create_serial = "";
                }

                if($this->topologie!=""){
                    $array_topologie = explode(";", $this->topologie);
                    foreach ($array_topologie as $key => $top_enum) {
                       $topologie_ids = explode(" ", $top_enum); 
                       $id_tok_topologie = $topologie_ids[0];
                       $id_topologie = $topologie_ids[1];
                       $topologie .= "<a href=\"/admin/topologie_objektu.php?id_tok_topologie=".$id_tok_topologie."&id_topologie=".$id_topologie."&typ=topologie&pozadavek=zasedaci_poradek\">Zasedací poøádek $id_tok_topologie</a>" ;
                    }
                }else{
                    $topologie = "";
                }
                
                if($this->nazevSerialu !=""){
                    $serial="<br/>Pøiøazené seriály: ";
                    $array_objekty = explode(";", $this->nazevSerialu);
                    $array_id_objektu = explode(";", $this->id_serialu);
                    foreach ($array_objekty as $key => $value) {
                       $serial .= "<a href=\"/admin/serial.php?id_serial=".$array_id_objektu[$key]."&typ=cena\">".$value."</a> | " ;
                    }
                }else{
                    $serial="";
                }
                
                if($this->typ_objektu=="5"){
                    $cenovaMapa .= "<a href=\"/admin/objekty.php?id_objektu=".$this->id_objektu."&typ=tok_list&pozadavek=show_letuska\">Cenová mapa</a>" ;                    
                }else if($this->typ_objektu=="6"){
                    $cenovaMapa .= "<a href=\"/admin/objekty.php?id_objektu=".$this->id_objektu."&typ=tok_list&pozadavek=show_goglobal\">Cenová mapa</a>" ;                    
                }else{
                    $cenovaMapa = "";
                }
                
                
		$vypis= "<div class='submenu'>$this->nazev: ";
		$vypis .= "<a href=\"".$adresa_modulu."?id_objektu=".$this->id_objektu."&amp;typ=objekty&amp;pozadavek=edit\">upravit/zobrazit</a>
                   $create_serial 
				    <a href=\"".$adresa_modulu."?id_objektu=".$this->id_objektu."&amp;typ=tok_list&amp;pozadavek=show\">TOK</a>
                    <a href=\"".$adresa_modulu."?id_objektu=".$this->id_objektu."&amp;typ=foto\">foto</a>
                        $topologie $cenovaMapa 
				    <a class='action-delete' href=\"".$adresa_modulu."?id_objektu=".$this->id_objektu."&amp;typ=objekty&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">smazat</a>
                        $serial                
			    </div>";

		return $vypis;
	}

/**kontrola zda smim provest danou akci*/
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
                        
                }else if($typ_pozadavku == "kalkulacni_vzorce_update"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
                        
                }else if($typ_pozadavku == "ajax_get_goglobal_ceny"){
			return $zamestnanec->get_bool_prava($id_modul,"read");
                                
		}else if($typ_pozadavku == "create"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
                        
                }else if($typ_pozadavku == "create_ajax"){
			return $zamestnanec->get_bool_prava($id_modul,"create");
                        
		}else if($typ_pozadavku == "create_account"){
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

		}else if($typ_pozadavku == "delete"){
			if( $zamestnanec->get_bool_prava($id_modul,"delete_cizi") or
				($zamestnanec->get_bool_prava($id_modul,"delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
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
			if(!Validace::text($_POST["nazev_objektu"]) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit název objektu");
			}
                        if(!Validace::int($_POST["typ_objektu"]) ){
				$ok = 0;
				$this->chyba("Musíte vyplnit typ objektu");
			}

		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}


        /**zobrazeni formulare pro vytvoreni/editaci uzivatele*/
	function show_edit_form(){
	//nazev, ico, role
            $objekt_list = $this->database->query($this->create_query("show") )
		 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
            $objekt = mysqli_fetch_array($objekt_list);

            $typ_objektu = "<select id=\"typ_objektu\" name=\"typ_objektu\" onchange=\"showSpecialEdit();\">";
            $i=1;

            $select_organizace = $this->showSelectOrganizace($objekt["id_organizace"]);

            while(Serial_library::get_typ_objektu($i)!=""){
                if($objekt["typ_objektu"]==$i){
                    $selected_role = "selected=\"selected\"";
                }else{
                    $selected_role = "";
                }
                $typ_objektu .= "<option value=\"".$i."\" ".$selected_role.">".Serial_library::get_typ_objektu($i)."</option>";
                $i++;
            }
            $typ_objektu .= "</select>";

            $povinne_udaje = "
                    <div class='form_row'>
                    <div class='label_float_left'>Název objektu</div><div class='value'><input id=\"nazev\" name=\"nazev_objektu\" type=\"text\" value=\"".$objekt["nazev_objektu"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Zkrácený název</div><div class='value'><input id=\"kratky_nazev\" name=\"kratky_nazev_objektu\" type=\"text\" value=\"".$objekt["kratky_nazev_objektu"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Pøiøazená organizace</div><div class='value'>$select_organizace</div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Typ objektu</div><div class='value'>".$typ_objektu."</div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Poznámka</div><div class='value'><textarea name=\"poznamka\"  rows=\"5\" cols=\"60\">".$objekt["poznamka"]." </textarea>
                        <input type=\"hidden\" name=\"id_objektu\" value=\"".$objekt["id_objektu"]."\" /></div></div>
                ";

          $ok_list = $this->database->query($this->create_query("show_ok") )
		 	or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
            $i=0;
            $js_wizz="";
            while($ok = mysqli_fetch_array($ok_list)){
               $i++;
               $foto_text = "";
               $sql_foto = "select * from `foto` join `foto_objekt_kategorie` on (`foto`.`id_foto`=`foto_objekt_kategorie`.`id_foto`)
                                where `foto_objekt_kategorie`.`id_objekt_kategorie`= ".$ok["id_objekt_kategorie"]."
                        ";
               $data_foto = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_foto);
               $foto_text = "<div style=\"float:right;\">";
               while ($row_foto = mysqli_fetch_array($data_foto)) {
                   $foto_text .= "<a  href=\"/".ADRESAR_FULL."/".$row_foto["foto_url"]."\" title=\"Zvìtšit fotografii ".$row_foto["nazev_foto"]."\"><img style=\"border:2px solid #1C94C4\" src=\"/".ADRESAR_NAHLED."/".$row_foto["foto_url"]."\" alt=\"".$row_foto["nazev_foto"]."\" height=\"100\" /></a>";
               }
               $foto_text .= "<div class=\"submenu\">
                                <a href=\"objekty.php?id_objektu=".$objekt["id_objektu"]."&amp;id_objekt_kategorie=".$ok["id_objekt_kategorie"]."&amp;typ=foto\">upravit fotografie</a>
                            </div>
                        </div>";
               $ok_text .= "<h4>Objektová kategorie ".$i." <input type=\"hidden\" name=\"id_objekt_kategorie_$i\" value=\"".$ok["id_objekt_kategorie"]."\" /></h4>
                       ".$foto_text."
                    <div class='form_row'>
                    <div class='label_float_left'>Název kategorie</div><div class=\"value\"><input name=\"ok_nazev_kategorie_$i\" type=\"text\" value=\"".$ok["nazev"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Krátký název</div><div class=\"value\"><input name=\"ok_kratky_nazev_kategorie_$i\" type=\"text\" value=\"".$ok["kratky_nazev"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Cizí název</div><div class=\"value\"><input name=\"ok_cizi_nazev_kategorie_$i\" type=\"text\" value=\"".$ok["cizi_nazev"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>GoGlobal hotel ID</div><div class=\"value\"><input name=\"goglobal_hotel_id_ok_$i\" type=\"text\" value=\"".$ok["goglobal_hotel_id_ok"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Základní kategorie</div><div class=\"value\"><input name=\"ok_zakladni_kategorie_$i\" type=\"checkbox\" value=\"1\" ".($ok["zakladni_kategorie"]==1?("checked=\"checked\""):(""))."/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Hlavní kapacita</div><div class=\"value\"><input name=\"ok_hlavni_kapacita_$i\" type=\"text\" value=\"".$ok["hlavni_kapacita"]."\" class=\"smallNumber\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Vedlejší kapacita (pøistýlka atp.)</div><div class=\"value\"><input name=\"ok_vedlejsi_kapacita_$i\" type=\"text\" value=\"".$ok["vedlejsi_kapacita"]."\" class=\"smallNumber\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Objektovou kategorii prodávat jako celek *</div><div class=\"value\"><input name=\"ok_jako_celek_$i\" type=\"checkbox\" value=\"1\" ".($ok["prodavat_jako_celek"]==1?("checked=\"checked\""):(""))."></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Poznámka</div><div class=\"value\"><input name=\"ok_poznamka_kategorie_$i\" type=\"text\" value=\"".$ok["poznamka"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Popis kategorie</div><div class=\"value\"><textarea id=\"ok_popis_kategorie_".$i."_\"  name=\"ok_popis_kategorie_$i\"  rows=\"4\" cols=\"80\" >".$ok["popis_kategorie"]." </textarea></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Smazat objektovou kategorii</div><div class=\"value\"><input name=\"ok_smazat_$i\" type=\"checkbox\" value=\"1\" ></div></div>
            ";
               $js_wizz .= "    makeWhizzyWig(\"ok_popis_kategorie_".$i."_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\"); \n  ";

            }
            $last_ok = $i;

            $ok_text = "<div class='tabs' id=\"ok\">
                ".$ok_text."
                    <div id=\"ok_next\"></div>
                    <div class='form_row'><a href=\"#\" onclick=\"return addOK();\" class=\"button\">Pøidat další objektovou kategorii</a></div>
                * Napøíklad pro nìkteré apartmány: Poèet osob resp. hlavní/vedlejší kapacity nebudou mít vliv, apartmán se prodává jako celek jedno jakému poètu lidí
                
                </div>";

            $special = "
                <div id=\"spec\">                 
                    <div id=\"special_text\">
                    </div>
                
                </div>
                ";



           if($this->typ_pozadavku=="edit"){
			//cil formulare
			$action="?id_objektu=".$this->id_objektu."&amp;typ=objekty&amp;pozadavek=update";

			if( $this->legal("update") ){
					$submit= "  <input type=\"submit\" name=\"ulozit\" value=\"Uložit\" />\n
                                                    <input type=\"submit\" name=\"ulozit_a_zavrit\" value=\"Uložit a Zavøít\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci tohoto objektu</strong>\n";
			}
		}
            $script = "
               <script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig60.js\"></script>
               <script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script> 
               <script type=\"text/javascript\"  src=\"/admin/js/objekty.js\" ></script>
               
            ";

            $vstupenky = array();
            $ubytovani = array();

            if($objekt["typ_objektu"]==1){
                $ubytovani_data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("show_ubytovani"));
                while ($row_ubytovani = mysqli_fetch_array($ubytovani_data)) {
                    $ubytovani = $row_ubytovani;
                }
            }else if($objekt["typ_objektu"]==3){
                $vstupenky_data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("show_vstupenky"));
                while ($row_vstupenky = mysqli_fetch_array($vstupenky_data)) {
                    $vstupenky = $row_vstupenky;
                }
            }else if($objekt["typ_objektu"]==5){
                $vstupenky_data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$this->create_query("show_letenky"));
                while ($row_vstupenky = mysqli_fetch_array($vstupenky_data)) {
                    $letenky = $row_vstupenky;
                }
            }

            $sql_zeme = "select * from `zeme`
                left join `objekt_vstupenka` 
                    on (`objekt_vstupenka`.`sport` = `zeme`.`nazev_zeme` and `objekt_vstupenka`.`id_objektu`=".$objekt["id_objektu"].")
                where 1 order by `nazev_zeme`";
            $query_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_zeme);
            $select_zeme = "<select name=\"sport\">";
            while ($row_zeme = mysqli_fetch_array($query_zeme)) {
                if($row_zeme["id_objektu"]!=""){
                    $select="selected=\"selected\"";
                }else{
                    $select="";
                }
                $select_zeme .= "<option ".$select." value=\"".$row_zeme["nazev_zeme"]."\">".$row_zeme["nazev_zeme"]."</option>";
            }
            $select_zeme .= "</select>";
              $script2 = "
               <script type=\"text/javascript\"  onload=\"showSpecialEdit();\">
                    var ok_count = $last_ok;
                    var nazev_ubytovani = decodeURIComponent('".$this->javascript_text_transform($ubytovani["nazev_ubytovani"])."');
                    var nazev_ubytovani_web = decodeURIComponent('".$this->javascript_text_transform($ubytovani["nazev_web"])."');
                    var popis_poloha = decodeURIComponent('".$this->javascript_text_transform($ubytovani["popis_poloha"])."');
                    var pokoje_ubytovani = decodeURIComponent('".$this->javascript_text_transform($ubytovani["pokoje_ubytovani"])."');
                    var pes = ".intval($ubytovani["pes"]).";
                    var pes_cena = '".$ubytovani["pes_cena"]."';
                    var posX = '".$ubytovani["posX"]."';
                    var posY = '".$ubytovani["posY"]."';
                    var typ_ubytovani = ".intval($ubytovani["typ_ubytovani"]).";
                    var kategorie_ubytovani = ".floatval($ubytovani["kategorie"]).";
                    var goglobal_hotel_id = '".$ubytovani["goglobal_hotel_id"]."';
                    var highlights = decodeURIComponent('".$this->javascript_text_transform($ubytovani["highlights"])."');
                        
                    var vstupenka_sport = '".$select_zeme."';
                    var vstupenka_akce = decodeURIComponent('".$this->javascript_text_transform($vstupenky["akce"])."');
                    var vstupenka_kod = decodeURIComponent('".$this->javascript_text_transform($vstupenky["kod"])."');
                    
                    var flight_from = '".$letenky["flight_from"]."';
                    var flight_to = '".$letenky["flight_to"]."';
                    var flight_direct = '".$letenky["flight_direct"]."';    
                    var automaticka_kontrola_cen = '".$letenky["automaticka_kontrola_cen"]."';  


                    var popis = decodeURIComponent('".$this->javascript_text_transform($objekt["popis_objektu"])."');
                    var ubytovani_seznam = '".$ubytovani_select."';
                    showSpecialEdit();
                    ".$js_wizz."
                        
                    
                </script>
            ";


    		$vystup= $script."<form action=\"".$action."\" method=\"post\">
                              <div id=\"tabs\">
                                <ul>
                                    ".$nadpisy."
                                </ul>".
                                        $povinne_udaje.$special.$ok_text.
                            " </div>
                             ".$submit."
                        </form>".$script2;
		return $vystup;

	}
    function update_kv(){
        //print_r($_POST);
        $query_delete = "DELETE FROM `cena_promenna_cenova_mapa` WHERE `id_objektu`=".$this->id_objektu."";
        $data = $this->database->transaction_query($query_delete,1)
		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
        $i = 1;
        $queryInsert = "INSERT INTO `cena_promenna_cenova_mapa`
            ( `id_objektu`, `termin_od`, `termin_do`, `no_dates_generation`, `termin_do_shift`, `castka`, `external_id`, `poznamka`) 
                VALUES ";
        $queryVals = array();
        while($_POST["cm_termin_od_".$i."_cena_0_var1"] !=""){             
            $termin_od = $this->change_date_cz_en($this->check($_POST["cm_termin_od_".$i."_cena_0_var1"]));
            $termin_do = $this->change_date_cz_en($this->check($_POST["cm_termin_do_".$i."_cena_0_var1"]));
            $useDates = 1-$this->check_int($_POST["use_dates_".$i."_cena_0_var1"]);
            $castka = $this->check_int($_POST["cm_castka_".$i."_cena_0_var1"]);
            $terminShift = $this->check_int($_POST["termin_do_shift_".$i."_cena_0_var1"]);
            $poznamka = $this->check($_POST["cm_poznamka_".$i."_cena_0_var1"]);
            $extID = $this->check($_POST["cm_externalID_".$i."_cena_0_var1"]);
            $queryVals[] = "($this->id_objektu,\"$termin_od\",\"$termin_do\",$useDates,$terminShift,$castka,\"$extID\",\"$poznamka\")";
            
            $i++;
        }
        $query = $queryInsert.implode(",", $queryVals);
        $data = $this->database->transaction_query($query,0)
		or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
	if( !$this->get_error_message() ){
            $this->database->commit();
            $this->confirm("Požadovaná akce probìhla úspìšnì");
        } else {
            $this->database->rollback();
        }                                   
        
    }
    
    function show_form_vygenerovane_terminy(){
        //TODO: edit action var
        $action="?id_objektu=".$this->id_objektu."&amp;typ=tok&amp;pozadavek=kalkulacni_vzorce_create_zajezdy";
        $output = "<form action=\"$action\" method=\"post\" >
        <div class=\"submenu\">
          <a href=\"#\" onclick=\"javascript:toggle_check_first_price();\" title=\"Zaškrtnout / odškrtnout první cenu u každého seriálu\" style=\"background-color: #aaaaff;\">Zaškrtnout/odškrtnout první cenu</a> 
        </div> ";
        
        $query_serialy = "
            select * from `objekt_serial` 
                join `serial` on `objekt_serial`.`id_serial` = `serial`.`id_serial`
            where `objekt_serial`.`id_objektu`=".$this->id_objektu."
            ";
        $data_serialy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_serialy);
        $poradi = 1;
        while ($row_serialy = mysqli_fetch_array($data_serialy)) {
            $this->id_serial = $row_serialy["id_serial"];
            $this->typ_editace = $_GET["typ_editace"];
            $this->typ_editace = "create";
            //$this->typ_terminu = "prime";
            $this->typ_terminu = "kombinovane";
            $output .= $this->show_form_vygenerovane_terminy_for_serial($row_serialy["id_serial"], $row_serialy["nazev"], $poradi);
            $poradi++;
        }
        
        $output .= "
                <table>
                    <tr><th colspan=\"2\">Vytváøení TOK u nových zájezdù:</th>
                    <tr><td colspan=\"2\">
                        <input name=\"TOK_chovani\" type=\"radio\" value=\"nevytvaret\"/> Žádné TOK nevytváøet ani nepøiøazovat<br/>
                        <input name=\"TOK_chovani\" type=\"radio\" value=\"vzdy_vytvorit\"/> Vždy vytvoøit nový TOK, pokud je pøiøazena OK ke službì<br/>
                        <input name=\"TOK_chovani\" type=\"radio\" checked=\"checked\" value=\"vytvorit_nebo_priradit\"/> Pokud existuje TOK, pøiøadit; pokud TOK neexistuje, vytvoøit nový<br/>
                        <input name=\"TOK_chovani\" type=\"radio\" value=\"priradit\"/> Pokud existuje TOK, pøiøadit; pokud TOK neexistuje, nedìlat nic<br/>
                    </td>
                </table>
                <div class=\"submenu\">
                <a name=\"#bottom\" style=\"margin:0;padding:0;width:0;border:none;\"> </a>
                    <input type=\"submit\" value=\"Vytvoøit / aktualizovat zájezdy\"> Zaškrtnuté ceny: 
                    <a href=\"#bottom\" onclick=\"javascript:refresh_and_round();\" title=\"Aktualizovat podle novì vypoètené ceny a zaokrouhlit na devadesát\" style=\"background-color: #aaaaff;\">Aktualizovat a zaokrouhlit</a> 
                    | <a href=\"#bottom\" onclick=\"javascript:refresh_checked('.kc_val');\" title=\"Aktualizovat podle novì vypoètené ceny\" style=\"background-color: #36ff00;\">Aktualizovat dle KC</a> 
                    | <a href=\"#bottom\" onclick=\"javascript:refresh_checked('.zc_val');\" title=\"Vrátit na døíve vypoètenou cenu\" style=\"background-color: #ff3400;\">Vrátit dle ZC</a> 
                    | <a href=\"#bottom\" onclick=\"javascript:round_checked('10');\">Zaokrouhlit na desítky</a> 
                    | <a href=\"#bottom\" onclick=\"javascript:round_checked('100');\">Zaokrouhlit na stovky</a> 
                    | <a href=\"#bottom\" onclick=\"javascript:round_checked('90');\">Zaokrouhlit na devadesát</a>                 
                </div> 
                </form>
                ";
        echo $output;
    }

    function get_existing_zajezdy_for_termin($termin){
        foreach ($this->zajezdy as $id => $zajezd) {
            if($termin[0]==$zajezd["od"] and $termin[1]==$zajezd["do"]){
                return $zajezd;
            }                                    
        }
        return null;
    }


        function evaluate_vzorec($id_cena, $termin_od, $termin_do, $id_vzorec, $vzorec){
            /*if($this->typ_terminu == "kombinovane"){
                $query_promenne = "SELECT `cena_promenna`.*,`cena_promenna_cenova_mapa`.*,`centralni_data`.`text` as `kurz`, (termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null and (`termin_do_shift` is null or `termin_do_shift`=0)) as sanity_check   
                    FROM   `cena_promenna`
                    join `centralni_data` on (`cena_promenna`.`id_mena` = `centralni_data`.`id_data`)
                    left join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE cena_promenna.id_cena=$id_cena and id_vzorec=$id_vzorec and '$vzorec' LIKE CONCAT('%', `nazev_promenne` ,'%') 
                        and ((termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null and `termin_do_shift` is null )
                        or (termin_od <= \"$termin_od\" and termin_do >= \"$termin_do\" and `castka` is not null and `no_dates_generation` >= 1 )                         
                        or (termin_od >= \"$termin_od\" and termin_do <= \"$termin_do\" and `castka` is not null and `no_dates_generation` >= 1 ) 
                        or (termin_od <= \"$termin_od\" and termin_do >= DATE_ADD(\"$termin_do\", INTERVAL -(`termin_do_shift`) DAY) and `castka` is not null and `termin_do_shift` is not null)
                        or (termin_od is null and typ_promenne = \"const\" and `fixni_castka` is not null))
                order by sanity_check DESC, id_objektu DESC";
            }else if($this->typ_terminu == "prime"){
                $query_promenne = "SELECT `cena_promenna`.*,`cena_promenna_cenova_mapa`.*,`centralni_data`.`text` as `kurz`, (termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null and (`termin_do_shift` is null or `termin_do_shift`=0) ) as sanity_check   
                    FROM   `cena_promenna`
                    join `centralni_data` on (`cena_promenna`.`id_mena` = `centralni_data`.`id_data`)
                    left join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE cena_promenna.id_cena=$id_cena and id_vzorec=$id_vzorec and '$vzorec' LIKE CONCAT('%', `nazev_promenne` ,'%') 
                        and ((termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null and `termin_do_shift` is null)
                        or (termin_od = \"$termin_od\" and termin_do = DATE_ADD(\"$termin_do\", INTERVAL -(`termin_do_shift`) DAY) and `castka` is not null and `termin_do_shift` is not null)
                        or (termin_od is null and typ_promenne = \"const\" and `fixni_castka` is not null))
                order by sanity_check DESC, id_objektu DESC";
            }  */
            
            if($this->typ_terminu == "kombinovane"){
                $query_promenne = "SELECT `cena_promenna`.*,`cena_promenna_cenova_mapa`.*,`centralni_data`.`text` as `kurz`, 
                    (cast(termin_od = \"$termin_od\"  AS SIGNED INTEGER)
                        + cast(termin_do = \"$termin_do\" AS SIGNED INTEGER) 
                        + cast(`castka` is not null AS SIGNED INTEGER) 
                        + cast((`termin_do_shift` is null or `termin_do_shift`=0) AS SIGNED INTEGER)) 
                    as sanity_check   
                    FROM   `cena_promenna`
                    join `centralni_data` on (`cena_promenna`.`id_mena` = `centralni_data`.`id_data`)
                    left join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE cena_promenna.id_cena=$id_cena and id_vzorec=$id_vzorec and '$vzorec' LIKE CONCAT('%', `nazev_promenne` ,'%') 
                        and ((termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null and `termin_do_shift` is null )
                        or (termin_od <= \"$termin_od\" and termin_do >= \"$termin_do\" and `castka` is not null and `no_dates_generation` >= 1 )                         
                        or (termin_od >= \"$termin_od\" and termin_do <= \"$termin_do\" and `castka` is not null and `no_dates_generation` >= 1 ) 
                        or (termin_od <= \"$termin_od\" and termin_do >= DATE_ADD(\"$termin_do\", INTERVAL -(`termin_do_shift`) DAY) and `castka` is not null and `termin_do_shift` is not null)
                        or (termin_od is null and typ_promenne = \"const\" and `fixni_castka` is not null))
                order by sanity_check DESC, id_objektu DESC";
            }else if($this->typ_terminu == "prime"){
                $query_promenne = "SELECT `cena_promenna`.*,`cena_promenna_cenova_mapa`.*,`centralni_data`.`text` as `kurz`, 
                    (cast(termin_od = \"$termin_od\"  AS SIGNED INTEGER)
                        + cast(termin_do = \"$termin_do\" AS SIGNED INTEGER) 
                        + cast(`castka` is not null AS SIGNED INTEGER) 
                        + cast((`termin_do_shift` is null or `termin_do_shift`=0) AS SIGNED INTEGER)) 
                    as sanity_check    
                    FROM   `cena_promenna`
                    join `centralni_data` on (`cena_promenna`.`id_mena` = `centralni_data`.`id_data`)
                    left join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE cena_promenna.id_cena=$id_cena and id_vzorec=$id_vzorec and '$vzorec' LIKE CONCAT('%', `nazev_promenne` ,'%') 
                        and ((termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null)
                        or (termin_od = \"$termin_od\" and termin_do = DATE_ADD(\"$termin_do\", INTERVAL -($termin_do_shift) DAY) and `castka` is not null)
                        or (termin_od = DATE_ADD(\"$termin_od\", INTERVAL 1 DAY) and termin_do = DATE_ADD(\"$termin_do\", INTERVAL -($termin_do_shift) DAY) and `castka` is not null)
                        or (termin_od = DATE_ADD(\"$termin_od\", INTERVAL 1 DAY) and termin_do = \"$termin_do\"  and `castka` is not null)
                        or (termin_od is null and typ_promenne = \"const\" and `fixni_castka` is not null))
                order by sanity_check DESC, id_objektu DESC";
            }            
            
            //echo $query_promenne;
            
           
            //echo "<br/>$id_cena, $termin_od, $termin_do<br/>";
            //echo $query_promenne;
            $data_promenne = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_promenne);
            $instanciovany_vzorec = $vzorec;
            
            $last_from = "";
            $last_to = "";
            $this->nesouhlasici_terminy = False;
            $nazvy_promennych = [];
            
            while ($row = mysqli_fetch_array($data_promenne)) {
                if($row["typ_promenne"]=="const"){
                    $replace = $row["fixni_castka"]*$row["kurz"];
                }else{
                    //cenova mapa
                    //check jestli maji vsechny CM stejne terminy
                    
                    if($last_from == "" and $row["termin_od"]!=""){
                         $last_from = $row["termin_od"] ;
                         $last_to =   $row["termin_do"] ;
                     //promenna ktera jeste nebyla pouzita, ale ma jine data nez predchozi    
                    }else if( !in_array($row["nazev_promenne"], $nazvy_promennych) and (($last_from != $row["termin_od"]) or ($last_to != $row["termin_do"]))){
                        //echo   $last_from. $row["termin_od"]. $last_to.  $row["termin_do"];
                        //nesouhlasi terminy
                        $this->nesouhlasici_terminy = True;
                    }
                    
                    $nazvy_promennych[] =   $row["nazev_promenne"];
                    
                    
                    $replace = $row["castka"]*$row["kurz"];
                }
                $instanciovany_vzorec = str_replace($row["nazev_promenne"], $replace, $instanciovany_vzorec);
            }
            if(preg_match("/[a-zA-Z]/", $instanciovany_vzorec)){
                return "chyba promìnné";
            }else{
                return eval("return $instanciovany_vzorec;");
            }                        
        }    
    
        
    function show_form_vygenerovane_terminy_for_serial($id_serial, $nazev, $poradi){
            //získat všechny termíny služeb

            $query_zajezdy = "
                select * from `zajezd` 
                    join `cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) 
                    where `zajezd`.`id_serial`=".$this->id_serial." and `do` >= \"".Date("Y-m-d")."\"
                ";
            //echo $query_zajezdy;
            $data_zajezdy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_zajezdy);
            $id_zajezd = "";
            $this->zajezdy = array();
            while ($row_zajezdy = mysqli_fetch_array($data_zajezdy)) {
                if($row_zajezdy["id_zajezd"]!=$id_zajezd){
                    $id_zajezd = $row_zajezdy["id_zajezd"];
                    $this->zajezdy[$id_zajezd] = array("od" => $row_zajezdy["od"], "do" => $row_zajezdy["do"], "id" => $id_zajezd);
                }
                $this->zajezdy[$id_zajezd][$row_zajezdy["id_cena"]] = array("id_cena"=>$row_zajezdy["id_cena"], "castka"=>$row_zajezdy["castka"]);
            }
            //print_r($this->zajezdy);
            //if($this->typ_terminu == "prime"){
                $query_intervals = "                    
                    (SELECT distinct termin_od, termin_do FROM   
                    `cena_promenna_cenova_mapa` 
                    WHERE id_objektu=".$this->id_objektu." and (`termin_do_shift` is null or `termin_do_shift` = 0 ) and (`no_dates_generation` = 0 or `no_dates_generation` is null))

                    union distinct (

                    SELECT distinct termin_od, DATE_ADD(`termin_do`, INTERVAL `termin_do_shift` DAY) as termin_do FROM   
                    `cena_promenna_cenova_mapa` 
                    WHERE id_objektu=".$this->id_objektu." and termin_do_shift is not null and `termin_do_shift` != 0  and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )   
                        
                    union distinct (
                    SELECT distinct termin_od, termin_do FROM   
                    `cena_promenna_cenova_mapa` 
                    WHERE id_objektu=".$this->id_objektu." and (`termin_do_shift` is null or `termin_do_shift` = 0 ) and (`no_dates_generation` = 0 or `no_dates_generation` is null))

                    union distinct (
                    SELECT distinct termin_od, DATE_ADD(`termin_do`, INTERVAL `termin_do_shift` DAY) as termin_do FROM   
                    `cena_promenna_cenova_mapa` 
                    WHERE id_objektu=".$this->id_objektu." and termin_do_shift is not null and `termin_do_shift` != 0  and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )  
                        
                    order by  termin_od, termin_do
                    ";
               //echo $query_intervals;
            
                //echo $query_intervals;
                $data_intervals = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_intervals);
                $terminy = array();
                while ($row_intervals = mysqli_fetch_array($data_intervals)) {                
                    //platný termín
                    if($row_intervals["termin_od"] >= Date("Y-m-d")){
                        $terminy[] = array($row_intervals["termin_od"],$row_intervals["termin_do"]); 
                    }
                }              
            //}
            
            usort($terminy, function ($a, $b){
                if($a[0] < $b[0]){
                    return -1;
                }else if($a[0] > $b[0]){
                    return 1;
                }
                return ($a[1] < $b[1])?(-1):(1);
            });
            //print_r($terminy);
            $ceny = array();
            $dotaz_ceny="select *
                      from `cena` 
                      left join `kalkulacni_vzorec_definice` on (id_vzorec_def = id_kalkulacni_vzorec) 
                    where `cena`.`id_serial`= ".$this->id_serial." 
                    order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` ";
            $data_ceny = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz_ceny);
            while($row = mysqli_fetch_array($data_ceny)){
                $ceny[$row["id_cena"]] = array($row["nazev_ceny"], $row["kratky_nazev"], $row["vzorec"], $row["nazev_vzorce"], $row["id_kalkulacni_vzorec"], $row["kapacita_bez_omezeni"]);                
            }
            
          
            
            $output_table = "
                <h3>$id_serial: $nazev</h3>
                <input type=\"hidden\" name=\"id_serial_$poradi\" value=\"$id_serial\"/>
                <table class=\"list\">
                    <tr><th><i>Zaškrtnout vše: </i><input type=\"checkbox\" class=\"check_by_class\" id=\"checkbox_cena\" title=\"Zaškrtnout všechny služby a termíny\"> <br/>Termíny <th> Vytvoøit / Aktualizovat";
            $i = 1;
            foreach ($ceny as $id_cena => $cena_array) {
                if($cena_array[5]==1){
                    $kapacita_bez_omezeni = " / <span class=\"green\" title=\"Neomezená kapacita služby\">NEOMEZENÁ</span>";
                }else{
                    $kapacita_bez_omezeni = "";
                }
                $output_table .= "<th>
                        <input type=\"checkbox\" class=\"checkbox_cena check_by_class\" id=\"checkbox_cena_".$id_serial."_".$id_cena."\" title=\"zaškrtnout všechny termíny této služby\"> 
                        ".$cena_array[0]."<br/> <i style=\"font-weight:normal\">".$cena_array[3]."<br/> </i>
                        <input type=\"hidden\" name=\"id_cena_".$id_serial."_".$i."\" value=\"".$id_cena."\">    
                        <span style=\"font-weight:normal\">
                            <input type=\"text\" class=\"smallNumber\" name=\"kapacita_celkova_".$id_serial."_".$i."\" value=\"10\" title=\"Volná kapacita\"> Kapacita   $kapacita_bez_omezeni 
                        </span>    "; 
                $i++;
            }
            $index = 0;//TODO: zmìnit indexování, pravdìpodobnì na dvousložkové: seriál a následnì index termínu
            foreach ($terminy as $id => $termin) {
                $existing_zajezd = $this->get_existing_zajezdy_for_termin($termin);
                //echo "inside termin";
                if($existing_zajezd != NULL or $this->typ_editace != "aktualizace"){
                    if($existing_zajezd != null){
                        $note_zajezd = "<span class=\"green\" title=\"Zájezd s tímto termínem již byl vytvoøen - ceny budou editovány\"\">Již vytvoøen!</span>
                            <input type=\"hidden\" id=\"existujici_zajezd_".$id_serial."_".($index+1)."\" name=\"existujici_zajezd_".$id_serial."_".($index+1)."\" value=\"".$existing_zajezd["id"]."\" /> 
                            ";
                    }else{
                        $note_zajezd = "";
                    }
                    $valid_cena = false; //indikator zda jsem vypocitali alespon jednu cenu
                    //odkaz na smazani radku: <a href=\"#\" class=\"delete_row\" onclick=\"javascript:vygenerovane_terminy_delete_row('".($index+1)."');\" title=\"smazat øádek\"><img width=\"10\" src=\"./img/delete-cross.png\" alt=\"smazat øádek\" ></a>
                    $output_row = "\n<tr class=\"termin_row\" id=\"termin_row_".$id_serial."_".($index+1)."\"><th>
                        $note_zajezd                     
                        <input class=\"date\" id=\"termin_od_".$id_serial."_".($index+1)."\" name=\"termin_od_".$id_serial."_".($index+1)."\" value=\"".$this->change_date_en_cz($termin[0])."\" /> 
                        - <input class=\"date\" id=\"termin_do_".$id_serial."_".($index+1)."\" name=\"termin_do_".$id_serial."_".($index+1)."\" value=\"".$this->change_date_en_cz($termin[1])."\" />
                         <th><input type=\"checkbox\" id=\"vytvorit_zajezd_".$id_serial."_".($index+1)."\" name=\"vytvorit_zajezd_".$id_serial."_".($index+1)."\" value=\"1\" checked=\"checked\" />       
                            ";
                    $i_c = 0;
                    foreach ($ceny as $id_cena => $cena_array) {
                        $i_c ++;
                        $note = "";
                        //top priorita je pouzit cenu TOK, pokud neexistuje, zkusim ji vypocitat z KV
                        $cena_tok = "";
                        $post_tok = "";
                        //TODO
                        if($this->typ_terminu == "kombinovane"){
                            $query_tok = "                    
                                SELECT distinct `objekt_kategorie_termin`.`cena`, `objekt_kategorie_termin`.`id_termin`, `objekt_kategorie_termin`.`id_objekt_kategorie` FROM   `objekt_kategorie_termin` 
                                join `objekt_kategorie` on  (`objekt_kategorie_termin`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` )
                                join `cena_objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` and `cena_objekt_kategorie`.`id_cena` = ".$id_cena.")

                                WHERE `cena_objekt_kategorie`.`use_cena_tok` = 1 and
                                    `objekt_kategorie_termin`.`datetime_od` >= \"".$termin[0]."\" and
                                    `objekt_kategorie_termin`.`datetime_do` <= \"".$termin[1]."\"                                                  
                                ";
                        }else if($this->typ_terminu == "prime"){
                            $query_tok = "  
                                SELECT distinct `objekt_kategorie_termin`.`cena`, `objekt_kategorie_termin`.`id_termin`, `objekt_kategorie_termin`.`id_objekt_kategorie` FROM   `objekt_kategorie_termin` 
                                join `objekt_kategorie` on  (`objekt_kategorie_termin`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` )
                                join `cena_objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` and `cena_objekt_kategorie`.`id_cena` = ".$id_cena.")

                                WHERE `cena_objekt_kategorie`.`use_cena_tok` = 1 and
                                    `objekt_kategorie_termin`.`datetime_od` = \"".$termin[0]."\" and
                                    `objekt_kategorie_termin`.`datetime_do` = \"".$termin[1]."\" 
                                ";

                        }
                        //echo $query_tok; $_POST["id_tok_" . $this->pocet_zaznamu . "_" . $row["id_objekt_kategorie"]]   ;
                        //name=\"cena_".$id_cena."_".($index+1)."\"
                        $data_tok = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_tok);
                        while ($row_tok = mysqli_fetch_array($data_tok)) {                
                            //platný termín
                            $post_tok = "<input type=\"hidden\" name=\"id_tok_".$id_serial."_".($i_c)."_".$row_tok["id_objekt_kategorie"]."\" value=\"".$row_tok["id_termin"]."\" />
                                         <input type=\"hidden\" name=\"je_vstupenka_".$id_serial."_".($i_c)."\" value=\"1\" />  
                                        ";
                            $cena_tok =  $row_tok["cena"];
                        }
                        if($cena_tok !=""){
                            $value = $cena_tok;
                            $valid_cena = true;
                        }else if($cena_array[2] != ""){
                            $value = $this->evaluate_vzorec($id_cena, $termin[0],$termin[1], $cena_array[4], $cena_array[2]);
                            if(is_numeric($value)){
                                $valid_cena = true;
                            }else{
                                $note = "<span class=\"red\">$value</span>";
                                $value = "";                            
                            }
                            if($this->nesouhlasici_terminy==True){
                                $note =  "<span class=\"red\" title=\"Termíny se pøekrývají pouze èásteènì\">!termín!</span>";
                            }
                            
                            
                        }else{
                            $value = "";
                        }

                        if($value!=""){
                            $puvodni_cena = "/<span title=\"Kalkulovaná cena pøed zaokrouhlením\"> KC: <span class=\"orange\" ><span class=\"kc_val\">".$value."</span> Kè</span></span> ";
                        }else{
                            $puvodni_cena = "";
                        }                      

                        if($existing_zajezd != null and isset($existing_zajezd[$id_cena]["castka"])){
                            $zajezd_cena = "/<span title=\"Cena evidovaná na již existujícím zájezdu\"> ZC: <span class=\"blue\" ><span class=\"zc_val\">".$existing_zajezd[$id_cena]["castka"]."</span> Kè</span></span> ";
                            $value = $existing_zajezd[$id_cena]["castka"];
                        }else{
                            $zajezd_cena = "";
                        }


                        $output_row.= "<td class=\"cena_cell\" id=\"cena_cell_".$id_serial."_".$id_cena."\">"
                                . "<input type=\"checkbox\" class=\"checkbox_cena checkbox_cena_".$id_serial."_".$id_cena."\" id=\"checkbox_cena_".$id_serial."_".$id_cena."_".($index+1)."\"> "
                                . $post_tok."<input class=\"bigNumber\" id=\"cena_".$id_serial."_".$id_cena."_".($index+1)."\" name=\"cena_".$id_serial."_".$id_cena."_".($index+1)."\" value=\"".$value."\" /> $note $puvodni_cena $zajezd_cena";                    
                    }
                    if($valid_cena){
                        $output_table .= $output_row;
                        $index++;
                    }
                }
            }
            
            
            return $output_table."</table><br/><br/>";
            
            
    }      
    
    
    
    function show_form_kv_letuska() {
        $action = "?id_objektu=" . $this->id_objektu . "&amp;typ=tok&amp;pozadavek=kv_update";
        //tlacitka pro odesilani
        if ($this->legal("update")) {
            $submit = "<input type=\"submit\" name=\"submit\" value=\"Uložit\" />
                       <input type=\"submit\" name=\"submit\" value=\"Uložit a zavøít\" />
                       <input type=\"submit\" name=\"submit\" value=\"Uložit a aktualizovat zájezdy\" />";
        } else {
            $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci objektu</strong>\n";
        }
        $i = 1;
        $vypis = "                
                                        <form action=\"" . $action . "\" method=\"post\" />	
                                        ".$submit . "   
					<table  class=\"list\">
						";

        $script = Objekty::load_kv_data($this->id_objektu);
        
        
        $query = "select * from objekt_letenka where id_objektu=" . $this->id_objektu . " limit 1";
        $res = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
        $letenky = mysqli_fetch_array($res);
        $hiddenVars = "
            <input type='hidden' id='flight_from_cena_0_var1' value='".$letenky["flight_from"]."' />
            <input type='hidden' id='flight_to_cena_0_var1' value='".$letenky["flight_to"]."' /> 
            <input type='checkbox' id='flight_direct_cena_0_var1' value='1' ".(($letenky["flight_direct"] == 1)?("checked=\"checked\""):(""))." style=\" visibility:hidden;height:1px;width:1px\"/>     
        ";
        $vypis = $vypis . "
                        <tr>
                            <td colspan=\"5\" class=\"nastaveni_vzorce darkBlue2\"  id=\"cenova_mapa\">
                                <script language=\"javascript\">
                                    $(document).ready(function(){
                                        show_cenova_mapa_objekt($this->typ_objektu, '".$letenky["flight_from"]."', '".$letenky["flight_to"]."', '".$letenky["flight_direct"]."');
                                    });
                                </script>
                                <a href=\"#\" onclick=\"javascript:show_cenova_mapa_objekt($this->typ_objektu, '".$letenky["flight_from"]."', '".$letenky["flight_to"]."', '".$letenky["flight_direct"]."');\">zobrazit termínovou mapu -&gt;</a> 
                            </td>
                        </tr>
                        ";

        $vypis = $script . $vypis . "</table>" . $submit .$hiddenVars. "<input name=\"pocet\" type=\"hidden\" value=\"" . $this->pocet . "\" /> </form>";

        return $vypis;
    }
    
 
    function show_form_kv_goglobal() {
        $action = "?id_objektu=" . $this->id_objektu . "&amp;typ=tok&amp;pozadavek=kv_update";
        //tlacitka pro odesilani
        if ($this->legal("update")) {
            $submit = "<input type=\"submit\" name=\"submit\" value=\"Uložit\" />
                       <input type=\"submit\" name=\"submit\" value=\"Uložit a zavøít\" />
                       <input type=\"submit\" name=\"submit\" value=\"Uložit a aktualizovat zájezdy\" />";
        } else {
            $submit = "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci objektu</strong>\n";
        }
        $i = 1;
        $vypis = "                
                                        <form action=\"" . $action . "\" method=\"post\" />					
					<table  class=\"list\">
						";

        $script = Objekty::load_kv_data($this->id_objektu);

        $vypis = $vypis . "
                        <tr>
                            <td colspan=\"5\" class=\"nastaveni_vzorce darkBlue2\"  id=\"cenova_mapa\">
                                <script language=\"javascript\">
                                    $(document).ready(function(){
                                        show_cenova_mapa_objekt($this->typ_objektu, '', '', '');
                                    });
                                </script>
                                <a href=\"#\" onclick=\"javascript:show_cenova_mapa_objekt($this->typ_objektu, '', '', '');\">zobrazit termínovou mapu -&gt;</a> 
                            </td>
                        </tr>
                        ";

        $vypis = $script . $vypis . "</table>" . $submit .$hiddenVars. "<input name=\"pocet\" type=\"hidden\" value=\"" . $this->pocet . "\" /> </form>";

        return $vypis;
    }
    
    function ajax_get_goglobal_ceny(){
            $goGlobalIDs = array();
            $max_kapacita = 1;
            $query = $this->create_query("show_ok");
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);            
            while ($row = mysqli_fetch_array($data)) {
                if($row["hlavni_kapacita"]>$max_kapacita){
                    $max_kapacita = $row["hlavni_kapacita"];
                }
                if($row["goglobal_hotel_id_ok"]!="" and !in_array($row["goglobal_hotel_id_ok"], $goGlobalIDs)){
                    $goGlobalIDs[]=$row["goglobal_hotel_id_ok"];
                }else if($row["goglobal_hotel_id"]!="" and !in_array($row["goglobal_hotel_id"], $goGlobalIDs)){
                    $goGlobalIDs[]=$row["goglobal_hotel_id"];
                }
            }
            
            $termin_od = $this->change_date_cz_en($this->check($_POST["termin_od"]));
            $termin_do = $this->change_date_cz_en($this->check($_POST["termin_do"]));
           $this->goglobal_pricelist = Cena_serial::ajax_get_goglobal_for_hotelids($goGlobalIDs, $max_kapacita, $termin_od, $termin_do);
           // print_r($this->goglobal_pricelist);
            //echo $xml_array['xmlRequest'] ;
        }
        
        
    /*vytvori JSON z promenne goglobal_pricelist a ten vrati*/
    function show_goglobal_ceny(){
            return json_encode($this->goglobal_pricelist);
        }   
        
        
    static function load_kv_data($id_objektu){
            
                
        $query_km = "SELECT * FROM `centralni_data` 
						WHERE `nazev` like \"%kalkulace_mena:%\"
						Order by `nazev`";
        $data_km = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_km);
        while ($row = mysqli_fetch_array($data_km)) {
            $mena[] = "[" . implode(",", array($row["id_data"], "\"" . str_replace("kalkulace_mena:", "", $row["nazev"]) . "\"", "\"" . $row["text"] . "\"")) . "]";
        }

        $query_promenne_timemap = "select * from  `cena_promenna_cenova_mapa` 
                      where `id_objektu`=" . $id_objektu . " 
                      order by `termin_od`, `termin_do`, `id_objektu`
                ";
        $mapa = array();
        $data_promenne_timemap = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_promenne_timemap);
        while ($row_timemap = mysqli_fetch_array($data_promenne_timemap)) {
            if ($row_timemap["no_dates_generation"] == 1) {
                $useDates = 0;
            } else {
                $useDates = 1;
            }
            $mapa[] = "[\"" . CommonUtils::czechDate($row_timemap["termin_od"]) . "\",\"" . CommonUtils::czechDate($row_timemap["termin_do"]) . "\",\"" . $row_timemap["castka"] . "\",\"" . $row_timemap["external_id"] . "\",\"" . $row_timemap["poznamka"] . "\",\"$useDates\",\"" . $row_timemap["termin_do_shift"] . "\"]";
        }
        $cenove_mapy =  implode(",", $mapa);
        

       
        $typ = "letuska"; //todo:get true type
        $script = "<script type=\"text/javascript\">
                        var id_objektu = ".$_GET["id_objektu"].";
                        var meny = [
                            ".implode(",\n", $mena)."
                        ];
                        var cenove_mapy = {
                            timemap_cena_0_0_var1:[". $cenove_mapy ."]
                        };
                        var id_kv_list = {
                            cena_0:'0'
                        };
                        var vzorec = {
                            'vzorec_0':'var1'
                        };
                        var vzorec_promenne = {
                            'vzorec_0': [[".  implode(",", array("\"var1\"","\"".$typ."\"","\"".$default."\"","\"".$bez_meny."\""))."]]                        
                        };
                        var mena_bez_prepoctu = \"".Cena_serial::MENA_BEZ_PREPOCTU."\";    
                    </script>
                    ";
         return $script;
        }    
	/**zobrazeni formulare pro vytvoreni/editaci uzivatele*/
	function show_form(){
	//nazev, ico, role


            $typ_objektu = "<select id=\"typ_objektu\" name=\"typ_objektu\" onchange=\"showSpecial();\">
                                    <option value=\"0\">---</option>";
            $i=1;
            while(Serial_library::get_typ_objektu($i)!=""){
                if($_POST["typ_objektu"]==$i){
                    $selected_role = "selected=\"selected\"";
                }else{
                    $selected_role = "";
                }
                $typ_objektu .= "<option value=\"".$i."\" ".$selected_role.">".Serial_library::get_typ_objektu($i)."</option>";
                $i++;
            }
            $typ_objektu .= "</select>";

            $select_organizace = $this->showSelectOrganizace();

            $povinne_udaje = "
                    <div class='form_row'>
                    <div class='label_float_left'>Název objektu</div><div class='value'><input id=\"nazev\" name=\"nazev_objektu\" type=\"text\" value=\"".$_POST["nazev_objektu"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Zkrácený název</div><div class='value'><input id=\"kratky_nazev\" name=\"kratky_nazev_objektu\" type=\"text\" value=\"".$objekt["kratky_nazev_objektu"]."\" class=\"inputText\"/></div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Pøiøazená organizace</div><div class='value'>".$select_organizace."</div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Typ objektu</div><div class='value'>".$typ_objektu."</div></div>
                    <div class='form_row'>
                    <div class='label_float_left'>Poznámka</div><div class='value'><textarea name=\"poznamka\"  rows=\"5\" cols=\"60\">".$_POST["poznamka"]." </textarea></div></div>
                ";

            $ok = "
                <div id=\"spec\">                 
                    <div id=\"special_text\">
                    </div>
                
                </div>

                <h3>Objektové kategorie</h3>
                <div class='form_row'>
                    <div class='label_float_left'>Název kategorie</div><div class='value'><input name=\"ok_nazev_kategorie_1\" type=\"text\" value=\"".$_POST["nazev_kategorie_1"]."\" class=\"inputText\"/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Krátký název</div><div class='value'><input name=\"ok_kratky_nazev_kategorie_1\" type=\"text\" value=\"".$_POST["kratky_nazev_kategorie_1"]."\" class=\"inputText\"/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Cizí název</div><div class='value'><input name=\"ok_cizi_nazev_kategorie_1\" type=\"text\" value=\"".$_POST["cizi_nazev_kategorie_1"]."\" class=\"inputText\"/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>GoGlobal hotel ID</div><div class='value'><input name=\"goglobal_hotel_id_ok_1\" type=\"text\" value=\"".$_POST["goglobal_hotel_id_ok_1"]."\" class=\"inputText\"/></div></div>                
                <div class='form_row'>                
                    <div class='label_float_left'>Základní kategorie</div><div class='value'><input name=\"ok_zakladni_kategorie_1\" type=\"checkbox\" value=\"1\" ".(($_POST["zakladni_kategorie_1"]==1 or !isset($_POST["zakladni_kategorie_1"]))?("checked=\"checked\""):(""))."/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Hlavní kapacita</div><div class='value'><input name=\"ok_hlavni_kapacita_1\" type=\"text\" value=\"".$_POST["hlavni_kapacita_1"]."\" class=\"inputText\"/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Vedlejší kapacita (pøistýlka atp.)</div><div class='value'><input name=\"ok_vedlejsi_kapacita_1\" type=\"text\" value=\"".$_POST["vedlejsi_kapacita_1"]."\" class=\"inputText\"/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Objektovou kategorii prodávat jako celek *</div><div class='value'><input name=\"ok_jako_celek_1\" type=\"checkbox\" value=\"1\" ".($_POST["ok_jako_celek_1"]==1?("checked=\"checked\""):(""))."></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Poznámka</div><div class='value'><input name=\"ok_poznamka_kategorie_1\" type=\"text\" value=\"".$_POST["poznamka_kategorie_1"]."\" class=\"inputText\"/></div></div>
                <div class='form_row'>
                    <div class='label_float_left'>Popis kategorie</div><div class='value'><textarea id=\"ok_popis_kategorie_1_\" name=\"ok_popis_kategorie_1\" rows=\"4\" cols=\"80\" >".$_POST["popis_kategorie_1"]." </textarea></div></div>

                <div id=\"ok_next\"></div>
                <tr><td><a href=\"#\" onclick=\"return addOK();\" class=\"button\">Pøidat další objektovou kategorii</a>

                * Napøíklad pro nìkteré apartmány: Poèet osob resp. hlavní/vedlejší kapacity nebudou mít vliv, apartmán se prodává jako celek jedno jakému poètu lidí

                
                ";
                $js_wizz = "    makeWhizzyWig(\"ok_popis_kategorie_1_\", \"fontname fontsize clean | bold italic underline | left center right | number bullet indent outdent | undo redo | color hilite rule | link image |  html fullscreen\"); \n  ";



           $submit = "";
           if($this->typ_pozadavku=="new"){
			//cil formulare
			$action="?typ=objekty&amp;pozadavek=create&amp;moznosti_editace=".$_GET["moznosti_editace"]."";
			//tlacitko pro odeslani serialu zobrazime jen pokud ma zamestnanec opravneni vytvorit serial!
			if( $this->legal("create") ){
					//tlacitko pro odeslani a pocet cen ktere se maji zobrazot v dalsim kroku
					$submit= "  <input type=\"submit\" name=\"ulozit\" value=\"Uložit\" />\n
                                                    <input type=\"submit\" name=\"ulozit_a_zavrit\" value=\"Uložit a Zavøít\" />\n";
			}else{
					$submit="<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení objektu</strong>\n";
			}
		}

            $script = "
               <script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/whizzywig60.js\"></script>
               <script language=\"JavaScript\" type=\"text/javascript\" src=\"/admin/whizz/slovensky.js\"></script> 
               <script type=\"text/javascript\"  src=\"/admin/js/objekty.js\" ></script>
            ";
            $sql_zeme = "select * from `zeme` where 1 order by `nazev_zeme`";
            $query_zeme = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql_zeme);
            $select_zeme = "<select name=\"sport\">";
            while ($row_zeme = mysqli_fetch_array($query_zeme)) {
                $select_zeme .= "<option value=\"".$row_zeme["nazev_zeme"]."\">".$row_zeme["nazev_zeme"]."</option>";
            }
            $select_zeme .= "</select>";
           // $ubytovani_select = $this->showSelectUbytovani();
            $script2 = "
               <script type=\"text/javascript\"  onload=\"showSpecial();\">
                    var ok_count = 1;
                    var popis_poloha = '';
                    var pokoje_ubytovani = '';
                    var pes = 0;
                    var pes_cena = '';
                    var posX = 0;
                    var posY = 0;
                    var typ_ubytovani = 0;
                    var kategorie_ubytovani = 0;
                    var goglobal_hotel_id = '';
                    var highlights = '';
                    var popis = '';    
                    var ubytovani_seznam = '".$ubytovani_select."';
                        
                    var vstupenka_sport = '".$select_zeme."';
                    var vstupenka_akce = '';
                    var vstupenka_kod = '';   
                    
                    var flight_from = '';
                    var flight_to = '';
                    var flight_direct = ''; 
                    var automaticka_kontrola_cen = ''; 

                    showSpecial();
                    ".$js_wizz."
                </script>
            ";

		$vystup= $script."<form action=\"".$action."\" method=\"post\">
                              <div id=\"tabs\">
                                <ul>
                                    ".$nadpisy."
                                </ul>".
                                        $povinne_udaje.$ok.
                            " </div> 
                             ".$submit."
                        </form>".$script2;
		return $vystup;
	}

        function get_id_ok() { return $this->last_id_objekt_kategorie;}
	function get_id() { return $this->id_objektu;}
	function get_nazev() { return $this->informace["nazev"];}
        function get_id_klient() { return $this->id_klient; }
	function get_id_user_create() {
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_klient == 0){
			return $this->id_zamestnance;
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) );
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}

	}
}




?>
