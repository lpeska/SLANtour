<?php
/** 
* uzivatel_zamestnanec.inc.php
* 	- trida volana v jadru na kazde strance - kontrola prihlaseni uzivatele + vyzvednuti dat o uzivateli z dtb.
* 	- je koncipovana jako singleton (pr�va jsou pot�eba i ve v�t�in� podt��d)
*/

/*--------------------- UZIVATEL ----------------------------*/
class Agentura extends Generic_data_class{
	//instance User_zamestnanec
	static private $instance;  
	
	protected $id;
	protected $jmeno;
	protected $heslo;
	protected $heslo_sha1;
	protected $uzivatel;
	protected $correct_login;
	protected $from_sessions;
	protected $hlavni_administrator;
	
	private $prava; //seznam prav k jednotlivym modulum
		
	public $database; //trida pro odesilani dotazu
	
	//staticka funkce pro vytvareni instance (pokud jeste neexistuje, tak ji vytvori, jinak vrati jiz existujici
	static function get_instance($name="", $passwd="", $from_sessions=0, $hlavni_administrator=0) { 
   	if( !isset(self::$instance) ) { 
      	self::$instance = new Agentura($name, $passwd, $from_sessions, $hlavni_administrator);
    	}
		return self::$instance ;
   } 

 	//staticka funkce pro odhlaseni uzivatele (premaze soucasneho uzivatele anonymnim
	static function logout() { 
      self::$instance = new Agentura("","");
      return self::$instance ;
   }  
	
	/*privatni konstruktor tridy (zabranuje vicenasobne inicializaci)*/
	private function __construct($name, $passwd, $from_sessions=0, $hlavni_administrator=0){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
				
		$this->correct_login=false;
		
		/*uprava vstupnich dat do bezpecne formy*/
		$this->from_sessions=$this->check_int($from_sessions);
		$this->hlavni_administrator=$this->check_int($hlavni_administrator);
		
		$this->jmeno=strtolower($this->check($name));
		if($this->from_sessions){
			$this->heslo_sha1=$this->check($passwd);
		}else{
			$this->heslo=$this->check($passwd);
		}
		if($this->jmeno==""){
			/*vytvoreni anononymniho uzivatele*/
			$this->chyba("U�ivatel nep�ihl�en");
			$this->uzivatel=array();
		}else{

			/*kontrola spravnosti jmena a hesla*/
				if($this->correct_login()){
					$this->correct_login=true;

					//ziskam z databaze prava uzivatele k jednotlivym modulum
					$this->prava = array();

					
					if(!$this->get_error_message() ){//nactu seznam prav
			
					 	
						if($from_sessions==0){
							//pokud tam jeste nejsou, ulozim data do sessions - pro pristi kontrolu prihlaseni
							$_SESSION["jmeno"]=$this->uzivatel["uzivatelske_jmeno"];
							$_SESSION["heslo"]=$this->uzivatel["heslo_sha1"];
							$_SESSION["nazev_agentury"]=$this->uzivatel["nazev"];
                                                        $_SESSION["id_agentury"]=$this->uzivatel["id_klient"];
							if(!$this->get_error_message()){
								$this->confirm("U�ivatel byl �sp�n� p�ihl�en");
							}						
						}
					}
				}else{
					$this->chyba("�patn� jm�no nebo heslo");
					$this->uzivatel=array();
				}//of if legal()
		
		}//of if name=""
			
	}
	
	/*kontrola spravnosti prihlasovacich udaju*/
	function correct_login(){
		$data_user=$this->database->query($this->create_query("get_login_information"))
		 				or $this->chyba("Chyba p�i dotazu do datab�ze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
		$this->uzivatel=mysqli_fetch_array($data_user);	
		
		//v sessions mame ulozeny hash hesla (uzivatel uz byl pripojeny...) - pouzivame jinou kontrolu hesla
		if($this->from_sessions){
			//musi odpovidat uzivatelske jmeno a hash hesla - v sessions i v databazi je ulozeny hash hesla, takze neni treba volat sha1()
			if($this->uzivatel["uzivatelske_jmeno"] == $this->jmeno  and  $this->uzivatel["heslo_sha1"] == $this->heslo_sha1){
				return 1;
			}
		}else{
			//musi odpovidat uzivatelske jmeno a hash hesla
			if($this->uzivatel["uzivatelske_jmeno"] == $this->jmeno  and  $this->uzivatel["heslo_sha1"] == sha1($this->heslo.$this->uzivatel["salt"])  ){
				return 1;
			}		
		}
		return 0;
	}
	
/*vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="get_login_information"){
			

			
			$dotaz= "SELECT * FROM `agentura`
						WHERE `uzivatelske_jmeno` ='".$this->jmeno."' ".$admin."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
				
		}
	}

	//zobrazi informace o uzivateli
	function show_info_about_user($typ_zobrazeni=""){
		if($typ_zobrazeni==""){
			$vystup="
				<div class=\"uzivatel\">
				<p>U�ivatel: <strong>".$this->get_uzivatelske_jmeno()." (".$this->get_nazev().")</strong>
				<span style=\"margin-left:60%;\">/<a href=\"?typ=logout\" title=\"odhl�s� aktu�ln� p�ihl�en�ho u�ivatele\">odhl�sit</a>/</span></p>
				</div>";
			return $vystup;
		}else if($typ_zobrazeni=="tabulka_zamestnanci"){
			$vystup="
				<table>
					<tr>
						<td>U�ivatelsk� jm�no:</td>
						<td>".$this->get_uzivatelske_jmeno()."</td>
					</tr>
					<tr>
						<td>Jm�no:</td>
						<td>".$this->get_jmeno()."</td>
					</tr>
					<tr>
						<td>P��jmen�:</td>
						<td>".$this->get_prijmeni()."</td>
					</tr>
					<tr>
						<td>E-mail</td>
						<td>".$this->get_email()."</td>
					</tr>
					<tr>
						<td>Telefon</td>
						<td>".$this->get_telefon()."</td>
					</tr>																			
				</table>";
			return $vystup;
		
		}
	}	
	//zobrazi formular pro prihlaseni
	function show_login_form(){
		$vystup="
			<div class=\"uzivatel\">
			<form action=\"http://".$_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"]."?typ=login\" method=\"post\">
				<p>
					<strong>u�ivatel:</strong> <input type=\"text\" name=\"name\" value=\"".$this->get_uzivatelske_jmeno()."\" size=\"20\" maxlength=\"20\" />
					<strong>heslo:</strong> 	<input type=\"password\" value=\"\" name=\"passwd\" size=\"20\" maxlength=\"20\" /> 
					<input type=\"submit\" value=\"p�ihl�sit\" />
				</p>
		</form>
		</div>";
		return $vystup;
	}
	//zobrazi hlavni menu administrace (v zavislosti na modulech syst�mu pravech uzivatele)
	function show_main_menu(){
		$core = Core::get_instance();
		//najdu vsechny moduly ke kterym ma uzivatel opravneni
		$array_moduly = array();
		$array_moduly = $core->show_all_allowed_moduls();
		
		$vystup = "<div id=\"menu\">";
		
		foreach ($array_moduly as $i => $modul) {
			$vystup=$vystup."[<a href=\"".$modul["adresa_modulu"]."\">".$modul["nazev_modulu"]."</a>] \n";
		}
		$vystup = $vystup."[<a href=\"/xml_creator.php\">Generov�n� XML</a>] \n</div>";
		return $vystup;				
	}
	
	//vrati pole se seznamem prav uzivatele
	function get_prava(){
		return $this->prava;
	}
	
	//zobrazi vsechna prava daneho uzivatele
	function show_prava($typ_zobrazeni){

		if($typ_zobrazeni=="tabulka"){
		
			$array_prava=array("read","create","edit_svuj","edit_cizi","delete_svuj","delete_cizi");
				
			$vystup="<table class=\"list\">
							<tr>
								<th>Id</th>
								<th>N�zev modulu</th>
								<th>zobrazit objekty</th>
								<th>vytvo�it objekt</th>
								<th>upravit sv�j objekt</th>
								<th>upravit ciz� objekt</th>
								<th>smazat sv�j objekt</th>
								<th>smazat ciz� objekt</th>
							</tr>";
							
			//while pres vsechny druhy prav
			$j=0;
			foreach($this->prava as $id_modulu => $modul){
				$i=0;
				//test parity radku
				($i % 2 == 0)?$parita="class=\"suda\"":$parita="class=\"licha\"";
				
				$vystup=$vystup."<tr ".$parita.">\n<td>".$id_modulu."</td>
					<td><strong>".$modul["nazev_modulu"].":</strong></td>\n";

				$j=0;
				while($array_prava[$j]!=""){//u kazdeho typu opravneni zjistime, jestli na nej ma uzivatel narok
					$vystup=$vystup."<td>".(($this->get_bool_prava2($id_modulu, $array_prava[$j], $modul["prava"]))?"<span class=\"green\">ANO</span>":"<span class=\"red\">NE</span>")."</td>";
					$j++;
				}
				$vystup=$vystup."\n</tr>\n";
			$j++;
			}	
			$vystup=$vystup."\n</table>\n";
			return $vystup;
		}
	}
	/*metody pro pristup k parametrum*/
		function get_id(){ return $this->uzivatel["id_user"];	}
		function get_uzivatelske_jmeno(){ return $this->uzivatel["uzivatelske_jmeno"];}
		function get_nazev(){ return $this->uzivatel["nazev"];}
		function get_prijmeni(){ return $this->uzivatel["prijmeni"];}
		function get_email(){ return $this->uzivatel["email"];}
		function get_telefon(){ return $this->uzivatel["telefon"];}		
		
		function get_correct_login(){ return $this->correct_login;}	
	
	/*//verze funkce pro vypis prav ktera je pouzita v tvorbe/editaci uzivatele (umoznuje pruchod v cyklu pres nazvy prav)
	//kontrola promenne $objekt je nutna (obrana proti zneuziti k vypisu jinych polozek dtb.
		function get_prava($objekt){ 
			$array_nazev_prava=array("prava_serial","prava_informace","prava_typ_serial","prava_zeme","prava_destinace","prava_foto",
				"prava_dokumenty","prava_export_serial","prava_export_klienti","prava_import_serial","prava_zamestnanci",
				"prava_klienti","prava_objednavky");	
			if(in_array( $objekt, $array_nazev_prava )){
				return $this->uzivatel[$objekt];
			}
			//pokud objekt nenalezneme, vratime (pro jistotu) nulu
			return 0;
		}
		
		*/


	//pristupova prava - vrati hodnotu true/false na zaklade daneho objektu a typu pozadovanych prav
		function get_bool_prava($id_modulu,$typ){
			//kontrola vstupu
				$id_modulu=$this->check_int($id_modulu);
				$typ=$this->check($typ);				
				$prava = intval($this->prava[$id_modulu]["prava"]);
				
				return $this->get_bool_prava2($id_modulu,$typ,$prava);
	
		}			
		
	//pristupova prava - vrati hodnotu true/false na zaklade daneho objektu a typu pozadovanych prav a integru znacici jaka prava skutecne mam
	//hodnota id_modulu zatim neni potreba, ale bylo by mozno ji vyuzit v pripade rozdilneho vyhodnocovani prav pro ruzne moduly
		private function get_bool_prava2($id_modulu,$typ,$prava){
			//kontrola vstupu
				$id_modulu=$this->check_int($id_modulu);
				$typ=$this->check($typ);			
				$prava=$this->check_int($prava);
				
				switch ($typ){
					case "read":
						return ( ($prava >= 1) ? true : false);
						break;
					case "create":
						return ( ($prava >= 2) ? true : false);
						break;
					case "edit_svuj":
						return ( ($prava >= 2) ? true : false);
						break;
					case "edit_cizi":
						return ( ($prava >= 3) ? true : false);
						break;
					case "delete_svuj":
						return ( ($prava >= 2) ? true : false);
						break;
					case "delete_cizi":
						return ( ($prava >= 3) ? true : false);
						break;																														
				}
			//k neznamemu pravu vratim false
			return false;		
		}			
		
		

		

	
}




?>
