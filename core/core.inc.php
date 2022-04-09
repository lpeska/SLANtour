<?php
/** 
core.inc.php - jádro aplikace, obsahuje třídu Core, funkce:
	- singleton
	- připojí se do databáze
	- naloguje uživatele (nebo vytvoří anonymního)
	- rozparsuje parametry scriptu
	- zjistí, zda je modul povolený a zda má uživatel k němu alespoň práva ke čtení
*/
/*--------------------- DATABAZE ----------------------------*/
class Core{
	//instance User_zamestnanec
	static private $instance; 

	public $db_spojeni; 
	private $db_vysledek; 
	public $array_moduly;//seznam všech modulů, které jsou povolené a uživatel k nim má oprávnění
	private $current_modul; //aktualne otevreny modul
	
	public $database; //trida pro odesilani dotazu	

	//staticka funkce pro vytvareni instance (pokud jeste neexistuje, tak ji vytvori, jinak vrati jiz existujici
	static function get_instance() { 
   	if( !isset(self::$instance) ) { 
      	self::$instance = new Core(); 
    	}
		return self::$instance ;
   } 
	
	
	//konstruktor tridy = pripojeni k databazi
	private function __construct(){

		require_once __DIR__ . "/../global/prihlaseni_do_databaze.inc.php"; //prihlasovaci udaje do databaze
			
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		//inicializace
			$adress = "";
		//připojení k databázi
			$this->database->connect($db_server,$db_jmeno,$db_heslo,$db_nazev_db);
		
		//vytvoření uživatele
			$this->create_user();
		
		//zjistim id modulu na zaklade adresy			
			$adresa_modulu = strtolower( substr( $_SERVER['SCRIPT_NAME'], 0,  strpos( $_SERVER['SCRIPT_NAME'],"." ) ) );
			$adresa_modulu = substr($adresa_modulu, (strrpos( $_SERVER['SCRIPT_NAME'],"/")+1) );
			
			//prepis nekterych adres modulu
			if($adresa_modulu=="info"){
				$adresa_modulu="informace";
			}
                        if($adresa_modulu=="objednavka"){
				$adresa_modulu="rezervace";
			}
			
			$id_modulu = $this->get_modul_from_adress($adresa_modulu);

			if($id_modulu!=false){
			//najdu vsechny moduly ke kterym ma uzivatel opravneni
				$this->array_moduly = array();
				$this->array_moduly = $this->get_all_allowed_moduls();
				
				
				//print_r($this->array_moduly);
			//vytvorim pole id_modulu
				$array_id_modul = array();
				foreach ($this->array_moduly as $i => $modul) {
					$array_id_modul[$i] = $modul["id_modul"];
				}
			
				$key = array_search( $id_modulu, $array_id_modul );
				
				//echo $key;
				if($key!== false){
					//otevřený modul je povolený
					$this->current_modul = array();
					$this->current_modul = $this->array_moduly[$key];
				}else{
					//pokud jsme modul nenasli, vyhodime chybovou hlasku presmerujeme uzivatele na hlavni stranku administrace a ukoncime script
					//toto neplati, pokud se uz na hlavni strance nalezame (obrana proti zacykleni) + osetreni neprihlasenych uzivatelu
					$adress = "http://".$_SERVER['SERVER_NAME'];

					if( $adress != "" and $adress != $adresa_modulu and $adresa_modulu!="index" ){
						$_SESSION["hlaska"]=$_SESSION["hlaska"]."\n <h2 class=\"red\">Požadovaný modul buď není povolen, nebo k němu nemáte přístupová práva!</h2>";
						//header("Location: ".$adress);
						//exit(); 						
					}
				}
			}else{//id_modulu == false
				//pokud jsme modul nenasli, vyhodime chybovou hlasku presmerujeme uzivatele na hlavni stranku administrace a ukoncime script
				//toto neplati, pokud se uz na hlavni strance nalezame (obrana proti zacykleni) + osetreni neprihlasenych uzivatelu!!
				$adress = "http://".$_SERVER['SERVER_NAME'];

				if( $adress != "" and $adress != $adresa_modulu and $adresa_modulu != "index" ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"]."\n <h2 class=\"red\">Požadovaný modul neexistuje!</h2>";
					//header("Location: ".$adress);
					//exit(); 						
				}				
			}
		//rozparsování parametrů
			$this->parse_parametr();			
		
	}
	
	// připojení do databáze
	private function connect_to_database(){
		//pripojeni k databazi
			$this->db_spojeni = mysqli_connect(Core::$db_server, Core::$db_jmeno, Core::$db_heslo) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. ".mysqli_error($this->db_spojeni));
			$this->db_vysledek = mysqli_select_db($this->db_spojeni, Core::$db_nazev_db )  or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. ".mysqli_error($this->db_spojeni));
		//nastaveni kodovani
			$this->database->query("SET character_set_results=cp1250"); 
			$this->database->query("SET character_set_connection=UTF8");
			$this->database->query("SET character_set_client=cp1250"); 	
	}

	//přihlášení uživatele
	private function create_user(){
		/*-------------vytvoreni uzivatele - podle jmena+hesla ze sessions, z formulare nebo anonymniho---------*/
			if($_GET["typ"]=="logout"){
				$uzivatel = User::logout();
				$_SESSION["jmeno_klient"]="";
				$_SESSION["heslo_klient"]="";
				$_SESSION["last_logon_klient"]="";
		
			/*prijimam data z prihlasovaciho formulare*/
			}else if($_GET["typ"]=="login"){
				$_GET["typ"]="";
				$uzivatel = User::get_instance($_POST["jmeno"],$_POST["heslo"]);

			/*nalezl jsem data v sessions, heslo neni stejne jako $_POST["passwd"], ale jeho hash, tedy musim 
				pro prihlaseni pouzit jiny algoritmus (3. parametr v konstruktoru)*/
			}else if($_SESSION["jmeno_klient"]!=""){
				$uzivatel = User::get_instance($_SESSION["jmeno_klient"],$_SESSION["heslo_klient"],1);
		
			/*anonymni uzivatel*/
			}else{
				$uzivatel = User::get_instance();
			}
	
			//vypis chybove/potvrzovaci hlasky
			if($_GET["typ"]!="logout"){
				if( !$uzivatel->get_error_message() ){
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$uzivatel->get_ok_message();
				}else{			
					$_SESSION["hlaska"]=$_SESSION["hlaska"].$uzivatel->get_error_message();
				}	
			}		
	}

	
	//vrati pole se vsemi moduly, ktere jsou povolene a uzivatel k nim ma pristup
	private function get_all_allowed_moduls(){ 
		$array_moduly = array();
		//dotaz na vsechny moduly, ke kterym ma uzivatel pristupove pravo
		$dotaz_moduly = "
			SELECT * 
			FROM `modul_klient`
			WHERE `modul_klient`.`povoleno` = 1 
			ORDER BY `modul_klient`.`id_modul`";	
		$data_moduly = $this->database->query( $dotaz_moduly );
		
		while($modul = mysqli_fetch_array($data_moduly,MYSQLI_ASSOC) ){
			//pridam modul do pole
			$array_moduly[] = $modul;
		}		
		return $array_moduly;
	}	
	
	function show_all_allowed_moduls(){
	 	return $this->array_moduly;
	}
	
	function show_current_modul(){
	 	return $this->current_modul;
	}	
	function show_nazev_modulu(){
	 	return $this->current_modul["nazev_modulu"];
	}			
	function get_id_modul(){
	 	return $this->current_modul["id_modul"];
	}		
	
	
	//vrati id modulu s adresou $adress nebo false, pokud žádný neexistuje
	private function get_modul_from_adress($adress){
		$dotaz_modul = "
			SELECT * FROM `modul_klient`
			WHERE `adresa_modulu` = '".$adress."' limit 1";
		
		$modul = mysqli_fetch_array( $this->database->query( $dotaz_modul ) );
		
		if($modul["id_modul"] != ""){
			return $modul["id_modul"];	
		}else{
			return false;	
		}

	}	

	//zjistí na základě typu modulu, jestli je povolený a uživatel má dostatečné oprávnění
	function get_id_modul_from_typ($typ_modulu){
		$array_typ_modul = array();
                if(is_array($this->array_moduly)){
                    foreach ($this->array_moduly as $i => $modul) {
			$array_typ_modul[$i] = $modul["typ_modulu"];
                    }

                    $key = array_search( $typ_modulu, $array_typ_modul );
                    //pokud jsme modul v seznamu povolených nalezli -> vratime id modulu, jinak false
                    if( $key !== false ){
			return $this->array_moduly[$key]["id_modul"];
                    }else{
			return false;
                    }
                }else{
                    return false;
                }
	}
	
	//zjistí na základě typu modulu, jestli je povolený a uživatel má dostatečné oprávnění
	function get_adress_modul_from_typ($typ_modulu){
		$array_typ_modul = array();
		foreach ($this->array_moduly as $i => $modul) {
			$array_typ_modul[$i] = $modul["typ_modulu"];
		}

		$key = array_search( $typ_modulu, $array_typ_modul );
		//pokud jsme modul v seznamu povolených nalezli -> vratime id modulu, jinak false
		if( $key !== false ){
			return $this->array_moduly[$key]["adresa_modulu"];
		}else{
			return false;
		}
	}	
	
	//rozdeli string parametru na jednotlive parametry
	private function parse_parametr(){
		$parametry = $_GET["params"];
		if( $parametry!="" ){
			$array_parametry = explode("/",$parametry);
		}
		$i=0;
		while($array_parametry[$i] != ""){
			$level = "lev".($i+1);
			$_GET[$level] = $array_parametry[$i];
			$i++;
		}
	}		
		
/*metody pro pristup k parametrum*/
	function get_db_spojeni(){ return $this->db_spojeni;}
	function get_db_vysledek(){ return $this->db_vysledek;}		
}



?>
