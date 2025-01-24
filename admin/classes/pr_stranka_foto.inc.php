<?php
/** 
* informace_foto.inc.php - trida pro zobrazeni seznamu fotek informací 
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM fotografii -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Foto_pr_stranka extends Generic_list{
	protected $typ_pozadavku;
	protected $id_pr_stranky;
	protected $id_zamestnance;
	protected $id_foto;
	protected $img_cislo;
        
        protected $img;     
        protected $img_alt;     
        protected $img_titulek;     
		
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì typu pozadavku*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_pr_stranky,$id_foto="",$img_cislo=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
	
                //kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_pr_stranky = $this->check_int($id_pr_stranky);
		$this->id_zamestnance = $this->check_int($id_zamestnance);
		$this->id_foto = $this->check_int($id_foto);
                if($img_cislo=="") 
                    $this->img_cislo = $this->check_int($_GET["img_cislo"]);
                else
                    $this->img_cislo = $this->check_int($img_cislo);
		
		//pokud mam dostatecna prava pokracovat                               
		if( $this->legal($this->typ_pozadavku) ){
			//na zaklade typu pozadavku vytvorim dotaz	                    
                                if($this->typ_pozadavku == "create") {                                    
                                    $this->data=$this->database->query($this->create_query("get_foto"))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
                                    while($this->get_next_radek()){
                                        $this->img = $this->radek["foto_url"];
                                        $this->img_alt = $this->radek["nazev_foto"] . $this->radek["popisek_foto"];
                                        $this->img_titulek = $this->radek["nazev_foto"] . $this->radek["popisek_foto"];
                                    }                                    
                                    
                                    echo $this->img . ", " . $this->img_alt . ", " . $this->img_titulek . "<br/>";
                                }
				
                                $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );		
				//vygenerování potvrzovací hlášky
				if( !$this->get_error_message() ){
					$this->confirm("Požadovaná akce probìhla úspìšnì");
				}						
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		

		
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu na zaklade typu pozadavku*/
	function create_query($typ_pozadavku){
		if($typ_pozadavku=="show"){
			$dotaz="SELECT `id_pr_stranky`, `img1`, `img1_alt`, `img1_titulek`, `img2`, `img2_alt`, `img2_titulek`
				FROM `pr_stranky` 
				WHERE `id_pr_stranky`= ".$this->id_pr_stranky."";
			//echo $dotaz;
			return $dotaz;
                }else if($typ_pozadavku=="create"){
                    $sql_set = $this->img_cislo == 1 ? "img1='$this->img', img1_alt='$this->img_alt', img1_titulek='$this->img_titulek'" : "img2='$this->img', img2_alt='$this->img_alt', img2_titulek='$this->img_titulek'";
                    //updatuj tabulku pr_stranky z tabulky foto
			$dotaz= "UPDATE `pr_stranky` SET $sql_set
				 WHERE `id_pr_stranky`=".$this->id_pr_stranky."
				 LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
			
		}else if($typ_pozadavku=="delete"){
                    $sql_set = $this->img_cislo == 1 ? "img1='', img1_alt='', img1_titulek=''" : "img2='', img2_alt='', img2_titulek=''";
			$dotaz= "UPDATE `pr_stranky` SET $sql_set
				 WHERE `id_pr_stranky`=".$this->id_pr_stranky."
				 LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
                }else if($typ_pozadavku=="get_foto"){                    
			$dotaz="SELECT `foto`.*
				FROM `foto` 
				WHERE `id_foto`= ".$this->id_foto."";
//			echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `pr_stranky` 
						WHERE `id_pr_stranky`=".$this->id_pr_stranky."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;					
		}
	}	
	
	/**kontrola zda smim provest danou akci*/
	function legal($typ_pozadavku){
		$zamestnanec = User_zamestnanec::get_instance();
		//z jadra zjistim ide soucasneho modulu
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
		$id_modul_foto = $core->get_id_modul_from_typ("fotografie");
								
		if($typ_pozadavku == "show"){
			return ( $zamestnanec->get_bool_prava($id_modul,"read") and  $zamestnanec->get_bool_prava($id_modul_foto,"read"));

		}else if($typ_pozadavku == "create" or $typ_pozadavku == "update" or $typ_pozadavku == "delete"){
			if( ( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") and  $zamestnanec->get_bool_prava($id_modul_foto,"read") ) or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and  $zamestnanec->get_bool_prava($id_modul_foto,"read") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				
		}

		//neznámý požadavek zakážeme
		return false;		
	}
				
	/**zobrazi hlavicku k seznamu fotografií*/
	function show_list_header(){            
		$vystup="
				<table class=\"list\">
					<tr>
						<th>Foto</th>						
						<th>Popisek</th>
						<th>Možnosti editace</th>
					</tr>
		";           
		return $vystup;
	}

	/**v tomhle pripade zobrazi obe fotky - jelikoz jsou obe ulozeny v jedne radce tabulky*/	
	function show_list_item($typ_zobrazeni){
		//z jadra ziskame informace o soucasnem modulu
		$core = Core::get_instance();
		$current_modul = $core->show_current_modul();
		$adresa_modulu = $current_modul["adresa_modulu"];	
			
		if($typ_zobrazeni=="tabulka"){						
                        if($this->get_img1() != "") {
                            $vypis.="<tr class=\"suda\">";
                            $vypis.="
                                        <td  class=\"foto\">
                                                <a href=\"/".ADRESAR_FULL."/".$this->get_img1()."\">
                                                <img src=\"/".ADRESAR_MINIIKONA."/".$this->get_img1()."\"
                                                          alt=\"".$this->get_img1_alt()."\"
                                                          width=\"80\" height=\"55\"/>
                                                </a>
                                        </td>					
                                        <td class=\"nazev\">".$this->get_img1_titulek()."</td>
                                        <td class=\"menu\">
                                                ".$zakl_foto."
                                          <a href=\"".$adresa_modulu."?id_pr_stranky=".$this->get_id_pr_stranky()."&amp;typ=foto&amp;pozadavek=delete&amp;img_cislo=1\">odebrat první</a>

                                        </td>
                                </tr>";
                        }
                        if($this->get_img2() != "") {
                            $vypis.="<tr class=\"licha\">";										
                            $vypis.="
                                        <td  class=\"foto\">
                                                <a href=\"/".ADRESAR_FULL."/".$this->get_img2()."\">
                                                <img src=\"/".ADRESAR_MINIIKONA."/".$this->get_img2()."\"
                                                          alt=\"".$this->get_img2_alt()."\"
                                                          width=\"80\" height=\"55\"/>
                                                </a>
                                        </td>					
                                        <td class=\"nazev\">".$this->get_img2_titulek()."</td>
                                        <td class=\"menu\">
                                                ".$zakl_foto."
                                          <a href=\"".$adresa_modulu."?id_pr_stranky=".$this->get_id_pr_stranky()."&amp;id_foto=".$this->get_id_foto()."&amp;typ=foto&amp;pozadavek=delete&amp;img_cislo=2\">odebrat druhou</a>

                                        </td>
                                </tr>";
                        }
			return $vypis;
		}
	}	

	/*metody pro pristup k parametrum*/
	function get_id_pr_stranky() { return $this->radek["id_pr_stranky"];}        
	function get_img1() { return $this->radek["img1"];}
	function get_img2() { return $this->radek["img2"];}
	function get_img1_alt() { return $this->radek["img1_alt"];}
	function get_img2_alt() { return $this->radek["img2_alt"];}
	function get_img1_titulek() { return $this->radek["img1_titulek"];}
	function get_img2_titulek() { return $this->radek["img2_titulek"];}
        
	function get_id_foto() { return $this->radek["id_foto"];}
	function get_zakladni_foto() { return $this->radek["zakladni_foto"];}
	function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { return $this->radek["popisek_foto"];}
	function get_foto_url() { return $this->radek["foto_url"];}
	
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_pr_stranky == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}		
} 

?>
