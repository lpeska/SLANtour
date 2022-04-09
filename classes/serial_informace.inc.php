<?php
/** 
* trida pro zobrazení seznamu informací seriálu
*/
/*rozsireni tridy Serial o seznam informací*/
class Seznam_informaci extends Generic_list{
	protected $id_serialu;
	protected $pocet_radku;
	
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì id serialu*/
	function __construct($id_serialu){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->id_serialu = $this->check_int($id_serialu);

	//ziskani zajezdu z databaze	
		$this->data=$this->database->query($this->create_query())
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_radku=mysqli_num_rows($this->data);
	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu ze zadaneho id serialu*/
	function create_query(){
		$dotaz ="select `informace_serial`.`id_serial`, 
							 `informace`.`id_informace`,`informace`.`nazev`,`informace`.`nazev_web`,
                                                         `informace`.`popisek`,`informace`.`popis`,`informace`.`popis_lazni`,`informace`.`popis_strediska`,
                                                         `foto`.`foto_url`
					from `informace_serial` join
						`informace` on (`informace`.`id_informace` =`informace_serial`.`id_informace`) 
                                              left join (`foto_informace` join
                                                `foto` on (`foto_informace`.`id_foto` = `foto`.`id_foto`) )
                                                on (`foto_informace`.`id_informace` = `informace`.`id_informace` and `foto_informace`.`zakladni_foto`=1)
                             
					where `informace_serial`.`id_serial`= ".$this->id_serialu." 
					order by `informace`.`typ_informace` desc,`informace`.`nazev` ";
		//echo $dotaz;
		return $dotaz;
	}	
	/**zobrazeni prvku seznamu informací*/
    function show_list_item($typ_zobrazeni) {
        $core = Core::get_instance();
        $adresa_informace = $core->get_adress_modul_from_typ("informace");
        if ($adresa_informace !== false) {//pokud existuje modul pro zpracovani			
            if($typ_zobrazeni == "seznam") {
                $vystup = "<li class=\"dokumenty\">
				<a onmousedown=\"log_info_click(event, '" . $_SERVER["REQUEST_URI"] . "');\"  href=\"" . $this->get_adress(array($adresa_informace, $this->radek["nazev_web"])) . "\">
					" . $this->radek["nazev"] . "
				</a>			
                            </li>";
                return $vystup;
            }else if($typ_zobrazeni == "first") {
                $text_base = strip_tags($this->radek["popisek"]."\n".$this->radek["popis"]);
                if(strlen($text_base)>130){
                $konec_textu = strpos($text_base, ".", 130);
                $konec_textu2 = strpos($text_base, "!", 130);
                $konec_textu3 = strpos($text_base, "?", 130);
                $konec_textu4 = strpos($text_base, " ", 130);

                if($konec_textu < 170 and $konec_textu >= 130) {
                    $text = substr($text_base, 0, ($konec_textu + 1));
                }else if($konec_textu2 < 170 and $konec_textu2 >= 130) {
                    $text = substr($text_base, 0, ($konec_textu2 + 1));
                }else if($konec_textu3 < 170 and $konec_textu3 >= 130) {
                    $text = substr($text_base, 0, ($konec_textu3 + 1));
                }else if($konec_textu4 < 170 and $konec_textu4 >= 130) {
                    $text = substr($text_base, 0, ($konec_textu4)) . "...";
                }else{
                    $text = substr($text_base, 0, (140)) . "...";
                }
                }else{
                    $text = $text_base;
                }
                
                $vystup = "<li class=\"dokumenty\">
				<a onmousedown=\"log_info_click(event, '" . $_SERVER["REQUEST_URI"] . "');\"  href=\"" . $this->get_adress(array($adresa_informace, $this->radek["nazev_web"])) . "\">
					<b>" . $this->radek["nazev"] . "</b></a>
                                            <br/>
                                <a onmousedown=\"log_info_click(event, '" . $_SERVER["REQUEST_URI"] . "');\"  href=\"" . $this->get_adress(array($adresa_informace, $this->radek["nazev_web"])) . "\">            
                                        <img alt=\"".$this->radek["nazev"]."\" src=\"https://www.slantour.cz/".ADRESAR_MINIIKONA."/".$this->radek["foto_url"]."\" class=\"fright\" /></a>                                            
                                        ".$text."    
							
			</li>";
                return $vystup;
            }
        }
    }	
	/*metody pro pristup k parametrum*/
	function get_id_informace() { return $this->radek["id_informace"];}
	function get_info_o_stredisku() { return $this->radek["popis_strediska"];}
        function get_zamereni_lazni() { return $this->radek["popis_lazni"];}
        function get_nazev_informace() { return $this->radek["nazev_informace"];}
	function get_pocet_radku() { return $this->pocet_radku;}
}



?>
