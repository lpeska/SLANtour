<?php
/**
* rezervace_osoba.inc.php - trida pro zobrazeni seznamu osob rezervace
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM osob -------------------  */

class Rezervace_osoba extends Generic_list {
	protected $typ_pozadavku;
	protected $id_objednavka;
	protected $id_zamestnance;
	protected $id_klient;

	private $pocet_osob; //pocet osob jiz prirazenych k objednavce

	public $database; //trida pro odesilani dotazu

	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku, $id_zamestnance, $id_objednavka, $id_klient=""){

		//trida pro odesilani dotazu
		$this->database = Database::get_instance();

	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->id_objednavka = $this->check_int($id_objednavka);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_klient = $this->check_int($id_klient);

		//pokud mam dostatecna prava pokracovat
		if( $this->legal($this->typ_pozadavku) ){
			//na zaklade typu pozadavku vytvorim dotaz

			if($this->typ_pozadavku == "create"){
				$this->database->start_transaction();
				$data_osoby = $this->database->transaction_query($this->create_query("get_pocet_osob"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

				if( !$this->get_error_message() ){
					$osoby = mysqli_fetch_array($data_osoby);
					$this->pocet_osob = $osoby["pocet"];

					$inserted_osoba = $this->database->transaction_query($this->create_query("create"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					if(!$this->get_error_message() ){
                                                $this->pocet_osob++;
                                                $data_osoby = $this->database->transaction_query($this->create_query("update_pocet_osob"))
                                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                                
						$this->database->commit();//potvrdim transakci
						$this->confirm("požadovaná akce probìhla úspìšnì");
					}
				}

			}else if($this->typ_pozadavku == "delete"){
                                        $data_osoby = $this->database->transaction_query($this->create_query("get_pocet_osob"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                        $osoby = mysqli_fetch_array($data_osoby);
					$this->pocet_osob = $osoby["pocet"];
                                        
					$deleted_osoba = $this->database->query($this->create_query("delete"))
		 				or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
					if(!$this->get_error_message() ){
                                            $this->pocet_osob--;
                                            $data_osoby = $this->database->query($this->create_query("update_pocet_osob"))
                                                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                            
                                            //hledani pripadne prirazene topologie ke klientovi
                                            $data_topologie = $this->database->query($this->create_query("get_tok_topologie"))
                                                or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                            
                                            while ($topologie = mysqli_fetch_array($data_topologie)) {
                                                $id_tok_topologie = $topologie["id_tok_topologie"];
                                                $id_serial = $topologie["id_serial"];
                                                $id_zajezd = $topologie["id_zajezd"];
                                                $zaj_topologie = new Zajezd_topologie("delete_klient_zasedaci_poradek", $id_serial, $id_zajezd, $id_tok_topologie, $this->id_klient);
                                            }
                                            $this->confirm("požadovaná akce probìhla úspìšnì");
					}
			}else if($this->typ_pozadavku == "show"){
				$this->data = $this->database->query($this->create_query("get_osoby"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			}
                        if($this->typ_pozadavku == "delete" or $this->typ_pozadavku == "create"){
                            //update pocet osob
                            $query = "select count(*) as `pocet` from `objednavka_osoby` where `id_objednavka`=".$this->id_objednavka." ";
                            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
                            while ($row = mysqli_fetch_array($data)) {
                                $update_pocet_osob = "update `objednavka` set `pocet_osob`=".$row["pocet"]." where `id_objednavka`=".$this->id_objednavka." limit 1 " ;
                                mysqli_query($GLOBALS["core"]->database->db_spojeni,$update_pocet_osob);                            
                            }
                        }
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
		}
	}
//------------------- METODY TRIDY -----------------
	/**vytvoreni dotazu podle typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="get_osoby"){
                    //dotaz s objednavatelem zajezdu
                     $dotaz = " SELECT * FROM 
                                    (SELECT `user_klient`.`id_klient`, `user_klient`.`jmeno`, `user_klient`.`prijmeni`, `user_klient`.`titul`, 
                                            `user_klient`.`datum_narozeni`, `user_klient`.`rodne_cislo`, `user_klient`.`telefon`,`user_klient`.`email`,
                                            `user_klient`.`cislo_pasu`,`user_klient`.`cislo_op`, `user_klient`.`ulice`, `user_klient`.`mesto`, 
                                            `user_klient`.`psc`,`objednavka_osoby`.`cislo_osoby` 
                                        FROM `objednavka_osoby` join `user_klient` ON (`objednavka_osoby`.`id_klient` = `user_klient`.`id_klient`) 
                                        WHERE `objednavka_osoby`.`id_objednavka`=$this->id_objednavka) AS a 
                                    UNION 
                                    (SELECT `user_klient`.`id_klient`, `user_klient`.`jmeno`, `user_klient`.`prijmeni`, `user_klient`.`titul`, 
                                            `user_klient`.`datum_narozeni`, `user_klient`.`rodne_cislo`, `user_klient`.`telefon`,`user_klient`.`email`,
                                            `user_klient`.`cislo_pasu`,`user_klient`.`cislo_op`, `user_klient`.`ulice`, `user_klient`.`mesto`, 
                                            `user_klient`.`psc`, CONCAT('0') AS `cislo_osoby` 
                                        FROM `objednavka` JOIN `user_klient` ON (`objednavka`.`id_klient` = `user_klient`.`id_klient`) 
                                        WHERE `objednavka`.`id_objednavka`=$this->id_objednavka) 
                                ORDER BY `cislo_osoby`";
//			echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="create"){
			$dotaz=$dotaz.
						"INSERT INTO `objednavka_osoby`
							(`id_objednavka`,`id_klient`,`cislo_osoby`)
						VALUES
							(".$this->id_objednavka.",".$this->id_klient.",".($this->pocet_osob + 1).");";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `objednavka_osoby`
						WHERE `id_objednavka`=".$this->id_objednavka." and `id_klient`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;

		}else if($typ_pozadavku=="get_pocet_osob"){
			$dotaz= "select count(`id_klient`) as `pocet`
						from `objednavka_osoby`
						where `id_objednavka` = ".$this->id_objednavka."
			";
			//echo $dotaz;
			return $dotaz;
                }else if($typ_pozadavku=="update_pocet_osob"){
			$dotaz= "update `objednavka` set `pocet_osob` = $this->pocet_osob
					where `id_objednavka` = ".$this->id_objednavka."
                                            limit 1
			";
			//echo $dotaz;
			return $dotaz;    
                }else if($typ_pozadavku=="get_tok_topologie"){
			$dotaz= "select `id_serial`,`id_tok_topologie`,`objednavka`.`id_zajezd` from `objednavka` join `zajezd_tok_topologie` on (`objednavka`.`id_zajezd` = `zajezd_tok_topologie`.`id_zajezd`)
					where `id_objednavka` = ".$this->id_objednavka."
			";
			echo $dotaz;
			return $dotaz;         
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `objednavka`
						WHERE `id_objednavka`=".$this->id_serialu."
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
		$id_modul_klient = $core->get_id_modul_from_typ("klienti");

		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_klient, "read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "update"	 or $typ_pozadavku == "delete"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_klient, "read") ) or
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_klient, "read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}
		}

		//neznámý požadavek zakážeme
		return false;
	}


	/**zobrazi hlavicku k seznamu osob*/
	function show_list_header($typ_zobrazeni = "tabulka"){
		if($typ_zobrazeni == "tabulka"){
			$vystup="
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Pøíjmení a jméno</th>
						<th>Datum narození</th>
						<th>Adresa</th>
						<th>Možnosti editace</th>
					</tr>
			";
		}else if($typ_zobrazeni == "tabulka_zobrazit"){
			$vystup="
				<table class=\"list\" id=\"table_osoby\">
					<tr>
						<th colspan=\"10\">Osoby pøihlášené k zájezdu</th>
					<tr>
					<tr>
						<th>Id</th>
						<th>Pøíjmení</th>
						<th>Jméno</th>
						<th>Narozen</th>
						<th>Rodné èíslo</th>
						<th>Telefon</th>
						<th>E-mail</th>
						<th>Èíslo pasu / OP</th>
						<th>Adresa (mesto, ulice, psc)</th>
                                                <th>Možnosti editace</th>
					</tr>
			";
		}
		return $vystup;

	}
	/**zobrazi prvek seznamu osob*/
	function show_list($typ_zobrazeni){
            if($typ_zobrazeni=="tabulka"){
                //inicializace
                $core = Core::get_instance();
                $vypis = "";

                while($this->get_next_radek()){		//pro vsechny radky
                    if($this->suda==1){
                            $vypis = $vypis."<tr class=\"suda\">";
                    }else{
                            $vypis = $vypis."<tr class=\"licha\">";
                    }
                    //text pro typ informaci
                    $vypis = $vypis."
                        <td class=\"id\">".$this->get_id_klient()."</td>
                        <td class=\"jmeno\">".$this->get_prijmeni()." ".$this->get_jmeno()."</td>
                        <td class=\"datum_narozeni\">".$this->change_date_en_cz( $this->get_datum_narozeni() )."</td>
                        <td class=\"adresa\">".$this->get_mesto().", ".$this->get_ulice().", ".$this->get_psc()."</td>

                        <td class=\"menu\">";

                    //mam-li povoleni otevrit klienty, vypisu menu na jejich zobrazeni
                    $adresa_klienti = $core->get_adress_modul_from_typ("klienti");
                    if($adresa_klienti !== false ){
                            $vypis = $vypis."
                                <a href=\"".$adresa_klienti."?id_klient=".$this->get_id_klient()."&amp;typ=klient&amp;pozadavek=edit\">zobrazit/upravit klienta</a>";
                    }
                    //klienta pujde vzdy smazat
                    $vypis = $vypis."
                                | <a href=\"?id_klient=".$this->get_id_klient()."&amp;id_objednavka=".$this->id_objednavka."&amp;typ=rezervace_osoby&amp;pozadavek=delete\">delete</a>
                        </td>
                    </tr>";

                }//end while
                return $vypis;
            }else if($typ_zobrazeni=="tabulka_zobrazit"){
                //inicializace
                $core = Core::get_instance();
                $vypis = "";

                $rowIndex = 0;
                while($this->get_next_radek()){		//pro vsechny radky
                    if($rowIndex == 0) {
                        $vypis = $vypis."<tr class=\"important\">";
                    }else if($this->suda==1){
                        $vypis = $vypis."<tr class=\"suda\">";
                    }else{
                        $vypis = $vypis."<tr class=\"licha\">";
                    }
                    //text pro typ informaci
                    $vypis = $vypis."
                                <td class=\"id\">".$this->get_id_klient()."</td>
                                <td class=\"jmeno\">".$this->get_prijmeni()."</td>
                                <td class=\"jmeno\">".$this->get_jmeno()."</td>
                                <td class=\"datum_narozeni\">".$this->change_date_en_cz( $this->get_datum_narozeni() )."</td>
                                <td class=\"datum_narozeni\">".$this->get_rodne_cislo()."</td>
                                <td class=\"telefon\">".$this->get_telefon()."</td>
                                <td class=\"email\">".$this->get_email()."</td>
                                <td class=\"cp\">".$this->get_cislo_pasu()." / ".$this->get_cislo_op()."</td>
                                <td class=\"adresa\">".$this->get_mesto().", ".$this->get_ulice().", ".$this->get_psc()."</td>";

                    //mam-li povoleni otevrit klienty, vypisu menu na jejich zobrazeni
                    $vypis .= " <td class=\"edit\">";
                    $adresa_klienti = $core->get_adress_modul_from_typ("klienti");
                    if($adresa_klienti !== false ){
                        $vypis .= " <form id='klient_form_update_$rowIndex' style='display: inline;' method='post' >
                                        <input type='button' value='Upravit' id='klient_upravit_$rowIndex' onclick='edit_obj_klient($rowIndex, " . $this->get_id_klient() . ");' />
                                    </form>";                        
                    }
                    //klienta pujde vzdy smazat - pokud to neni objednavatel zajezdu
                    if($rowIndex != 0) {
                        $vypis .= "     <form style='display: inline;' method='post' action='rezervace.php?id_klient=".$this->get_id_klient()."&amp;id_objednavka=".$this->id_objednavka."&amp;typ=rezervace_osoby&amp;pozadavek=delete'>
                                        <input type='submit' value='Odebrat' />
                                    </form>";
                        $vypis .= " <form id='klient_form_storno_$rowIndex' style='display: inline;' method='post' >
                                        <input disabled='true' type='button' value='Storno' id='klient_storno_$rowIndex' onclick='storno_obj_klient($rowIndex, " . $this->get_id_klient() . ");' />
                                    </form>";
                    }
                    $vypis .= "   </td>
                            </tr>";
                    $rowIndex++;

                }//end while

                //editace
                $vypis .= " <tr class='edit'>
                                <td></td>
                                <td>
                                    <input class='important' type='text' name='klient_prijmeni' onkeyup='searchKlient($this->id_objednavka);' id='klient_prijmeni' size='10' />
                                </td>
                                <td>
                                    <input class='important' type='text' name='klient_jmeno' onkeyup='searchKlient($this->id_objednavka);' id='klient_jmeno' size='10' />
                                </td>
                                <td><input class='important' type='text' name='klient_datum_narozeni' onkeyup='searchKlient($this->id_objednavka);' id='klient_datum_narozeni' size='8' /></td>
                                <td><input type='text' name='klient_rodne_cislo' id='klient_rodne_cislo' size='8' /></td>
                                <td><input type='text' name='klient_telefon' id='klient_telefon' size='8' /></td>
                                <td><input type='text' name='klient_email' id='klient_email' size='14' /></td>
                                <td>
                                    <input type='text' name='klient_cislo_pasu' id='klient_cislo_pasu' size='10' /> /
                                    <input type='text' name='klient_cislo_op' id='klient_cislo_op' size='10' />
                                </td>
                                <td id='klient_adresa'>
                                    <input type='text' name='klient_mesto' id='klient_mesto' size='16' />,
                                    <input type='text' name='klient_ulice' id='klient_ulice' size='16' />,
                                    <input type='text' name='klient_psc' id='klient_psc' size='4' />
                                </td>
                                <td>
                                    <form id='klient_form_create' method='post' >
                                        <input type='hidden' name='jmeno' id='hid_klient_jmeno' />
                                        <input type='hidden' name='prijmeni' id='hid_klient_prijmeni' />
                                        <input type='hidden' name='datum_narozeni' id='hid_klient_datum_narozeni' />
                                        <input type='hidden' name='rodne_cislo' id='hid_klient_rodne_cislo' />
                                        <input type='hidden' name='email' id='hid_klient_email' />
                                        <input type='hidden' name='telefon' id='hid_klient_telefon' />
                                        <input type='hidden' name='cislo_op' id='hid_klient_cislo_op' />
                                        <input type='hidden' name='cislo_pasu' id='hid_klient_cislo_pasu' />
                                        <input type='hidden' name='ulice' id='hid_klient_ulice' />
                                        <input type='hidden' name='mesto' id='hid_klient_mesto' />
                                        <input type='hidden' name='psc' id='hid_klient_psc' />
                                        <input type='submit' value='Vytvoøit a pøidat' onclick='request_klient_create($this->id_objednavka); return false;' />
                                    </form>
                                </td>
                            </tr>";                
                return $vypis;
            }//typ_zobrazeni=tabulka
	}

	/*metody pro pristup k parametrum*/
	function get_id_klient() { return $this->radek["id_klient"];}
	function get_jmeno() { return $this->radek["jmeno"];}
	function get_prijmeni() { return $this->radek["prijmeni"];}
	function get_datum_narozeni() { return $this->radek["datum_narozeni"];}
	function get_rodne_cislo() { return $this->radek["rodne_cislo"];}
	function get_telefon() { return $this->radek["telefon"];}
	function get_email() { return $this->radek["email"];}
	function get_cislo_pasu() { return $this->radek["cislo_pasu"];}
	function get_cislo_op() { return $this->radek["cislo_op"];}
	function get_mesto() { return $this->radek["mesto"];}
	function get_ulice() { return $this->radek["ulice"];}
	function get_psc() { return $this->radek["psc"];}

}

?>
