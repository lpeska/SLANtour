<?php
/** 
* trida pro praci s klientem
* - formul��e pro obnoven� zapomenut�ho hesla + jejich zpracov�n�
*/

/*--------------------- UZIVATEL ----------------------------*/
class Klient_zapomenute_heslo extends Generic_data_class{
	protected $typ_pozadavku;
	protected $jmeno;
	protected $prijmeni;
	protected $datum_narozeni;	
	protected $email;		
	protected $id_klient;
	protected $salt_potvrzeni;		
	protected $potvrzeni_expire;		
	
	protected $nove_heslo;	
	protected $nove_heslo_sha1;		
	protected $salt_heslo;		
	
	public $database; //trida pro odesilani dotazu	

  	
	/**
	*	konstruktor t��dy 
	* - parametry jsou hodnoty sloupc� z tabulky user_klient
	*/
	function __construct(
		$typ_pozadavku,$jmeno="",$prijmeni="",$datum_narozeni="",$id_klient="",$salt_potvrzeni=""
	){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->jmeno = $this->check_slashes( $this->check($jmeno) );
		$this->prijmeni = $this->check_slashes( $this->check($prijmeni) );
		$this->datum_narozeni = $this->check_slashes( $this->change_date_cz_en( $this->check($datum_narozeni) ) );				

		$this->id_klient = $this->check_int($id_klient);
		$this->salt_potvrzeni = $this->check($salt_potvrzeni) ;
		
		$this->nove_heslo = "";

			if($this->typ_pozadavku=="odeslani_potvrzeni" and $this->correct_data($this->typ_pozadavku) ){
						$data_klient = $this->database->query($this->create_query("get_user") ) 
		 					or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );			
						$pocet_klient = mysqli_num_rows($data_klient);
						if(!$this->get_error_message() and $pocet_klient != 0){
							
							//pro kazdeho odpovidajiciho klienta, teoreticky jich m��e b�t v�c
							while($klient = mysqli_fetch_array($data_klient)){
							
								$this->id_klient = $klient["id_klient"];
								$this->uzivatelske_jmeno = $klient["uzivatelske_jmeno"];
								$this->email = $klient["email"];
								//potvrzovaci kod pro e-mail
								$this->salt_potvrzeni = sha1(mt_rand().mt_rand());
								//cas kdy vyprsi tento potvrzovaci kod					
								$this->potvrzeni_expire = date("Y-m-d H:i:s", (time() + (PLATNOST_POTVRZENI * 60 * 60) ) );					
							
								$data_klient = $this->database->query($this->create_query("set_salt") ) 
		 							or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	
														
								if(!$this->get_error_message()){
									//odeslu klientovi e-mail s potvrzovacim kodem
									$predmet="Vygenerov�n� nov�ho hesla do syst�mu RSCK";
									$odesilatel_jmeno=AUTO_MAIL_SENDER;
									$odesilatel_email=AUTO_MAIL_EMAIL;
									$text = "Na z�klad� Va�eho po�adavku na zm�nu zapomenut�ho hesla k ��tu:<br/><br/>
										Id: ".$this->id_klient."<br/>
										U�ivatelsk� jm�no: ".$this->uzivatelske_jmeno."<br/>
										Jm�no a p��jmen�: ".$this->jmeno." ".$this->prijmeni." <br/>
										Datum narozen�: ".$this->change_date_en_cz( $this->datum_narozeni )."<br/><br/>
										 V�m zas�l�me potvrzovac� k�d. Pro potvrzen� ��dosti o zm�nu hesla do syst�mu RSCK klikn�te na n�sleduj�c� odkaz.<br/>
										Pokud jste nic nevypl�oval(a), m��ete tento e-mail ignorovat (n�kdo jin� pravd�podobn� vyplnil Va�e osobn� �daje, ale dokud nekliknete na odkaz n�e, heslo nebude zm�n�no).<br/><br/>
										http://".$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME']."?lev1=zapomenute_heslo&amplev2=potvrzeni_zmeny_hesla&amp;id_klient=".$this->id_klient."&amp;salt=".$this->salt_potvrzeni."<br/><br/>
										Pokud na odkaz nejde kliknout, zkop�rujte ho do ��dku adresy Va�eho prohl�e�e.
									";
							
									//odeslani potvrzovaciho e-mailu						
									$mail = Send_mail::send($odesilatel_jmeno, $odesilatel_email, $this->email, $predmet, $text);
									if(!$mail){
										$this->chyba("Nepoda�ilo se odeslat kontroln� e-mail. Zkuste to pros�m je�t� jednou.");
									}
								}	
							}//of while							
						}else{
							$this->chyba("K zadan�mu jm�nu, p��jmen� a datu narozen� neexistuje ��dn� klient!");
						}

							
						//vygenerov�n� potvrzovac� hl�ky
						if( !$this->get_error_message() ){
							$this->confirm("Na v� e-mail byla odesl�na kontroln� zpr�va");
						}			
						
			}else if($this->typ_pozadavku=="potvrzeni_zmeny_hesla"){
			
				$data_klient =  $this->database->query( $this->create_query("get_potvrzeni_hesla") ) 
		 			or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				
				$pocet_klient = mysqli_num_rows($data_klient);
				if(!$this->get_error_message() and $pocet_klient != 0){
					$uzivatel = mysqli_fetch_array($data_klient);
					$this->uzivatelske_jmeno = $uzivatel["uzivatelske_jmeno"];
					$this->email = $uzivatel["email"];
					
					//vygeneruji nove heslo
					$nahodny_retezec= sha1(mt_rand().mt_rand());
					$this->nove_heslo = substr($nahodny_retezec, 1, mt_rand(6,10));
					//a jeho sha1
					$nahodny_retezec= sha1(mt_rand().mt_rand());
					$this->salt_heslo = substr($nahodny_retezec, 1, mt_rand(10,20));
					$this->nove_heslo_sha1 = sha1($this->nove_heslo.$this->salt_heslo);					

					
					//nastavim ucet jako potvrzeny
					$set_heslo = $this->database->query( $this->create_query("set_nove_heslo") ) 
		 				or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );

					//vygenerov�n� potvrzovac� hl�ky					
					if( !$this->get_error_message() ){
							//odeslu klientovi e-mail s potvrzovacim kodem
							$predmet="Vygenerovan� heslo ze syst�mu RSCK";
							$odesilatel_jmeno=AUTO_MAIL_SENDER;
							$odesilatel_email=AUTO_MAIL_EMAIL;
							$text = "Na z�klad� Va�eho po�adavku na zm�nu zapomenut�ho hesla V�m zas�l�me nov� heslo: <br/><br/>
										U�ivatelsk� jm�no: ".$this->uzivatelske_jmeno."<br/>
										Heslo: ".$this->nove_heslo."<br/>
										Doporu�ujeme heslo co nejd��ve zm�nit.
									";
							
							//odeslani potvrzovaciho e-mailu						
							$mail = Send_mail::send($odesilatel_jmeno, $odesilatel_email, $this->email, $predmet, $text);
							if(!$mail){
								$this->chyba("Nepoda�ilo se odeslat kontroln� e-mail. Zkuste to pros�m je�t� jednou.");
							}else{
								$this->confirm("Nov� heslo bylo odesl�no na V� e-mail");
							}															
						
					}
				}else{
					$this->chyba("K�d je bu� �patn�, nebo je ji� pro�l�. Zkontrolujte pros�m spr�vnost k�du.");
				}
				echo $this->get_error_message();
				
			}					
	}
	

	/** vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		 if($typ_pozadavku=="get_user"){
			$dotaz= "SELECT `id_klient`,`uzivatelske_jmeno`,`email`,`jmeno`,`prijmeni`
						FROM `user_klient` 
						WHERE `jmeno`='".$this->jmeno."' and `prijmeni`='".$this->prijmeni."' and `datum_narozeni`='".$this->datum_narozeni."' and `ucet_potvrzen_klientem` = 1
						LIMIT 1 ";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="set_salt"){
			$dotaz= "UPDATE `user_klient` 
						SET
							 `salt_potvrzeni`= '".$this->salt_potvrzeni."', `potvrzeni_expire`= '".$this->potvrzeni_expire."' 
						WHERE `id_klient`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
		}else if($typ_pozadavku=="get_user_password"){
			$dotaz= "SELECT `id_klient`,`heslo_sha1`,`salt` FROM `user_klient` 
						WHERE `id_klient`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
									
		}else if($typ_pozadavku=="get_potvrzeni_hesla"){
			$dotaz= "SELECT `id_klient`,`uzivatelske_jmeno`,`email`
						FROM `user_klient` 
						WHERE `id_klient`=".$this->id_klient." and `salt_potvrzeni`='".$this->salt_potvrzeni."'  and `potvrzeni_expire` >='".Date("Y-m-d H:i:s")."' ";
			//echo $dotaz;
			return $dotaz;			
					
		}else if($typ_pozadavku=="set_nove_heslo"){
			$dotaz= "UPDATE `user_klient` 
						SET
							 `heslo_sha1`= '".$this->nove_heslo_sha1."', `salt`= '".$this->salt_heslo."', `salt_potvrzeni`= NULL, `potvrzeni_expire`= NULL 
						WHERE `id_klient`=".$this->id_klient."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;			
		}
	}	

	/** kontrola zda mam odpovidajici data*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovan� data: n�zev informace, popisek
		if($typ_pozadavku == "odeslani_potvrzeni"){
			if(!Validace::text($this->jmeno) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit jm�no");
			}
			if(!Validace::text($this->prijmeni) ){
				$ok = 0;
				$this->chyba("Mus�te vyplnit p��jmen�");
			}						
			if(!Validace::datum_en($this->datum_narozeni) ){
				$ok = 0;
				$this->chyba("Datum narozen� mus� b�t ve form�tu dd.mm.rrrr");
			}				
		}
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}
	}

	
	
	/** zobrazi formular pro obnoveni zapomenuteho hesla*/
	function show_form_heslo(){
	  $core = Core::get_instance();
	  $adresa_registrace = $core->get_adress_modul_from_typ("registrace");
	  if( $adresa_registrace !== false ){			
		$action="".$this->get_adress( array($adresa_registrace,"zapomenute_heslo","odeslani_potvrzeni" ) )."";
			$submit= "<input type=\"submit\" value=\"Zm�nit heslo\" />\n";	
			$jmeno="
					<tr>
						<td>Jm�no: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"jmeno\" value=\"".$this->jmeno."\" size=\"40\" maxlength=\"40\" /></td>
					</tr>";
			$prijmeni="
					<tr>
						<td>P��jmen�: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"prijmeni\" value=\"".$this->prijmeni."\" size=\"40\" maxlength=\"40\" /></td>
					</tr>";
			$datum_narozeni="
					<tr>
						<td>Datum narozen�: <span class=\"red\">*</span></td>
						<td><input type=\"text\" name=\"datum_narozeni\" value=\"".$this->change_date_en_cz($this->datum_narozeni)."\" size=\"40\" maxlength=\"40\" /></td>
					</tr>";										
			
		$vystup="
			<div id=\"uzivatel\">
			<form action=\"".$action."\" method=\"post\">
				<table class=\"uzivatel\">
				<th colspan=\"2\">Zm�nit zapomenut� heslo</th>
					".$jmeno."
					".$prijmeni."		
					".$datum_narozeni."																																								
				</table>
				".$submit."
			</form>
			<p><span class=\"red\">*</span> - pole ozna�en� hv�zdi�kou je t�eba vyplnit.</p>
			<h3>Co se stane po odesl�n�?</h3>
			<p>Po odesl�n� zkontroluje syst�m va�e �daje a pokud se shoduj� s ��tem, za�le na e-mail, kter� jste vyplnil p�i registraci, potvrzovac� k�d - odkaz.<br/>
				Teprve po kliknut� na odkaz bude provedeno vygenerov�n� nov�ho hesla, kter� V�m bude zasl�no e-mailem (ochrana proti zneu�it� t�et� osobou).</p>
			</div>";
		return $vystup;
	 }//if( $adresa_registrace !== false )
	}
	
}




?>
