<?php
/** 
uzivatel.inc.php - tridy pro praci s uzivatelem
*/

/*--------------------- UZIVATEL ----------------------------*/
class User extends Generic_data_class
{
	//instance User
	static private $instance; 

	protected $id;
	protected $jmeno;
	protected $heslo;
	protected $heslo_sha1;
	protected $uzivatel;
	protected $correct_login;
	protected $from_sessions;
	
	public $database; //trida pro odesilani dotazu	
	
	//staticka funkce pro vytvareni instance (pokud jeste neexistuje, tak ji vytvori, jinak vrati jiz existujici
	static function get_instance($name="", $passwd="", $from_sessions=0) { 
   	if( !isset(self::$instance) ) { 
      	self::$instance = new User($name, $passwd, $from_sessions); 
    	}
		return self::$instance ;
  } 
  
 	//staticka funkce pro odhlaseni uzivatele (premaze soucasneho uzivatele anonymnim
	static function logout() { 
      self::$instance = new User("",""); 
      return self::$instance ;
  }  
  	
	/*konstruktor třídy na základě jména a hesla uživatele
	mohou být i prázdné -> anonymní uživatel*/
	private function __construct($name, $passwd, $from_sessions=0){
			//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
		/*uprava vstupnich dat do bezpecne formy*/
		$this->from_sessions=$this->check_int($from_sessions);
		$this->jmeno=strtolower($this->check($name));
		if($this->from_sessions){
			$this->heslo_sha1=$this->check($passwd);
		}else{
			$this->heslo=$this->check($passwd);
		}
			
		if($this->jmeno==""){
			/*vytvoreni anononymniho uzivatele*/
			$this->uzivatel=array();
		}else{
			/*kontrola spravnosti jmena a hesla*/			
				if($this->correct_login()){
					$this->correct_login=true;
					
					if($from_sessions==0){
						//pokud tam jeste nejsou, ulozim data do sessions - pro pristi kontrolu prihlaseni
                                                $_SESSION["id_klient"]=$this->uzivatel["id_organizace"];
						$_SESSION["jmeno_klient"]=$this->uzivatel["uzivatelske_jmeno"];
						$_SESSION["heslo_klient"]=$this->uzivatel["heslo_sha1"];
						$_SESSION["last_logon_klient"]=$this->uzivatel["last_logon"];
						$update_logon = $this->database->query( $this->create_query("update_logon_time") );
						
						if(!$this->get_error_message()){
							$this->confirm("Uživatel byl úspěšně přihlášen");
						}						
					}					
				}else{
					$this->chyba("Špatné jméno nebo heslo");
					$this->uzivatel=array();
				}//of if legal()
		
		}//of if name=""
		
	}
	
	/*kontrola spravnosti prihlasovacich udaju*/
	function correct_login(){
		$data_user=$this->database->query( $this->create_query("get_login_information") )
		 	or $this->chyba("Chyba při dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		$this->uzivatel=mysqli_fetch_array($data_user);	
		
		//v sessions mame ulozeny hash hesla (uzivatel uz byl pripojeny...) - pouzivame jinou kontrolu hesla
		if($this->from_sessions){
			//musi odpovidat uzivatelske jmeno a hash hesla - v sessions i v databazi je ulozeny hash hesla, takze neni treba volat sha1()
			if(strtolower($this->uzivatel["uzivatelske_jmeno"]) == $this->jmeno  and  $this->uzivatel["heslo_sha1"] == $this->heslo_sha1){
				return 1;
			}
		}else{
			//musi odpovidat uzivatelske jmeno a hash hesla
			if(strtolower($this->uzivatel["uzivatelske_jmeno"]) == $this->jmeno  and  $this->uzivatel["heslo_sha1"] == sha1($this->heslo.$this->uzivatel["salt"])  ){
				return 1;
			}
		}
		return 0;
	}

	/*vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="get_login_information"){
			$dotaz= "SELECT * FROM `prodejce` join
                                        `organizace` on (`organizace`.`id_organizace` = `prodejce`.`id_organizace`)
						WHERE `uzivatelske_jmeno` ='".$this->jmeno."' and `ucet_potvrzen` = 1
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
		}else if($typ_pozadavku=="update_logon_time"){
			$dotaz= "UPDATE `prodejce` 
							SET `last_logon` = '".Date("Y-m-d h:i:s")."'
							WHERE `id_organizace` ='".$this->get_id()."'
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;	
		}
	}	

	//zobrazi formular pro prihlaseni
	function show_login_form(){
		$core = Core::get_instance();
		$adresa_registrace = $core->get_adress_modul_from_typ("registrace");
		if( $adresa_registrace !== false ){		
			$vystup="
				<div id=\"prihlaseni\" >
				<ul>
						
					<li><form action=\"/?typ=login\" method=\"post\">
						Jméno: <input name=\"jmeno\" type=\"text\" />
						Heslo: <input name=\"heslo\" type=\"password\" />
						<input value=\"přihlásit\" type=\"submit\" />
					</form></li>
				</ul>	
				</div>";
		}
                /*	<li><a href=\"".$this->get_adress(array($adresa_registrace,"new"),0)."\">registrace nové agentury</a></li>
					<li><a href=\"".$this->get_adress(array($adresa_registrace,"zapomenute_heslo"),0)."\">zapomenuté heslo</a></li>
                */
		return $vystup;
	}
	
//zobrazi hlavni menu administrace (v zavislosti na pravech uzivatele)
	function show_klient_menu(){
		$core = Core::get_instance();
		$adresa_registrace = $core->get_adress_modul_from_typ("registrace");
		$adresa_rezervace = $core->get_adress_modul_from_typ("rezervace");
		if( $adresa_registrace !== false ){
			$vystup="<div class=\"akce\" style=\"margin-bottom:10px;\">
                            <h3>".$this->uzivatel["prijmeni"]." ".$this->uzivatel["nazev"]." (".$this->uzivatel["ico"].")</h3>
						<ul>												 
						 <li><a href=\"".$this->get_adress(array($adresa_registrace,"editace_osobnich_udaju"),0)."\">Údaje prodejce</a></li>";
			if( $adresa_rezervace !== false ) {
				$vystup=$vystup." <li><a href=\"".$this->get_adress(array($adresa_rezervace,"faktura-provize-obj-list"),0)."\">Moje objednávky</a></li>";
			}
			$vystup=$vystup."<li><a href=\"/?typ=logout\">Odhlásit se</a></li></ul></div>";
		}
		return $vystup;
	}

	/*metody pro pristup k parametrum*/
	function get_id(){ return $this->uzivatel["id_organizace"];	}
	function get_uzivatelske_jmeno(){ return $this->uzivatel["uzivatelske_jmeno"];}	
	function get_nazev(){ return $this->uzivatel["nazev"];}
	function get_ico(){ return $this->uzivatel["ico"];}
	function get_correct_login(){ return $this->correct_login;}
}




?>
