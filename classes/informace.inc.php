<?php
/** 
* trida pro zobrazení seznamu informací seriálu
*/
/*rozsireni tridy Serial o seznam informací*/
class Informace extends Generic_list{
	protected $id_serialu;
        protected $id_zeme;
	protected $pocet_radku;
	
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/** konstruktor tøídy na základì id serialu*/
	function __construct($id_serialu,$id_zeme="",$typ="",$nazev_informace=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
		$this->id_serialu = $this->check_int($id_serialu);
                $this->id_zeme = $this->check_int($id_zeme);
                $this->typ = $this->check($typ);
                 $this->nazev_informace = $this->check($nazev_informace);

	//ziskani zajezdu z databaze	
		$this->data=$this->database->query($this->create_query())
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_radku=mysqli_num_rows($this->data);
	}	
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu ze zadaneho id serialu*/
	function create_query(){
            if($this->id_serialu >= 1 and $this->typ == ""){
		$dotaz ="select `informace_serial`.`id_serial`, 
							 `informace`.`id_informace`,`informace`.`nazev`,`informace`.`nazev_web`,`informace`.`popisek`,
                                                         `foto`.`id_foto`,`foto`.`foto_url`,`foto`.`nazev_foto`
					from `informace_serial` join
							`informace` on (`informace`.`id_informace` =`informace_serial`.`id_informace`)
                                                        left join (
                                                            `foto_informace` join
                                                            `foto` on (`foto`.`id_foto` =`foto_informace`.`id_foto` and `foto_informace`.`zakladni_foto` = 1)
                                                            ) on (`informace`.`id_informace` = `foto_informace`.`id_informace`)
					where `informace_serial`.`id_serial`= ".$this->id_serialu." 
					order by `informace`.`typ_informace`,`informace`.`nazev` ";
		//echo $dotaz;
		return $dotaz;
            }else if($this->typ == "zeme"){

                $dotaz ="select `informace`.*, `foto`.*
					from `informace` left join ( foto_informace join foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`  )
                                            ) on (`informace`.`id_informace` =`foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto`=1)
					where `informace`.`id_zeme`= ".$this->id_zeme." and `informace`.`typ_informace`=1
					limit 1 ";
		//echo $dotaz;
		return $dotaz;
            }else if($this->typ =="cela_informace" and $this->nazev_informace!=""){

                $dotaz ="select `informace`.*, `foto`.*
					from `informace` left join ( foto_informace join foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`  )
                                            ) on (`informace`.`id_informace` =`foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto`=1)
					where `informace`.`nazev_web`=\"".$this->nazev_informace."\" 
					limit 1 ";
		//echo $dotaz;
		return $dotaz;
            }else if($this->typ =="dalsi_informace" and $this->id_zeme!=""){

                $dotaz ="select `informace`.`id_informace`,`informace`.`nazev`,`informace`.`nazev_web`, `foto`.*
					from `informace` left join ( foto_informace join foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`  )
                                            ) on (`informace`.`id_informace` =`foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto`=1)
					where `informace`.`nazev_web`!=\"".$this->nazev_informace."\" and `informace`.`id_zeme`=".$this->id_zeme."
					 order by `informace`.`typ_informace`,`informace`.`nazev`";
		//echo $dotaz;
		return $dotaz;                
            }
	}	
	/**zobrazeni prvku seznamu informací*/
	function show_list_item($typ_zobrazeni){
		$core = Core::get_instance();
		$adresa_informace = "zajezdy/informace";
		if( $adresa_informace !== false ){//pokud existuje modul pro zpracovani			
			if($typ_zobrazeni=="seznam"){	
				$vystup="<li>
							<a href=\"".$this->get_adress( array($adresa_informace,$this->radek["nazev_web"]) )."\">
								".$this->radek["nazev"]."
							</a>			
						</li>";
				return $vystup;
			}else if($typ_zobrazeni=="zbytek_popisku"){
				//$vystup="<p>".$this->text_left."</p>";
                                $vystup = "";
                                //show all photoes
                                $dotaz ="select `foto`.*
					from `informace` join ( foto_informace join foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`  )
                                            ) on (`informace`.`id_informace` =`foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto`!=1)
					where `informace`.`id_informace`= ".$this->id_info." ";
                                        //echo $dotaz;
                                        //print_r($this->radek);
                                 $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                                 while( $zaznam = mysqli_fetch_array($data)){
                                   $vystup .= "<div class=\"round\" style=\"width:220px;\"><img SRC=\"https://slantour.cz/foto/nahled/".$zaznam["foto_url"]."\" title=\"".$zaznam["nazev_foto"]."\" alt=\"".$zaznam["nazev_foto"]."\" width=\"220\" /></div>";

                                 }

				return $vystup;
                        }else if($typ_zobrazeni=="dalsi_foto"){
				//$vystup="<p>".$this->text_left."</p>";
                                $vystup = "";
                                //show all photoes
                                $dotaz ="select `foto`.*
					from `informace` join ( foto_informace join foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`  )
                                            ) on (`informace`.`id_informace` =`foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto`!=1)
					where `informace`.`id_informace`= ".$this->radek["id_informace"]." ";
                                        //echo $dotaz;
                                 $k = 0;
                                $vystup .= "<tr>";
                                 $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                                 while( $zaznam = mysqli_fetch_array($data)){
                                   $vystup .= "<td>
                                       <div  class=\"round\" style=\"height:85px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                            <a href=\"https://slantour.cz/foto/full/".$zaznam["foto_url"]."\" class=\"highslide\"  onclick=\"return hs.expand(this)\">
                                                <img SRC=\"https://slantour.cz/foto/ico/".$zaznam["foto_url"]."\" 
                                                title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" 
                                                alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" 
                                                width=\"142\" style=\"margin:0;padding:0;border:none;\" height=\"85\" />
                                            </a>
                                            <div class=\"highslide-caption\">
                                                ".$this->get_nazev_foto().$this->get_popisek_foto()."
                                            </div> 
                                         </div>   
                                        </td>";
                                   $k++;
                                    if ($k % 2 == 0) {
                                        $vystup .= "</tr><tr>";
                                    } 
                                 }

				return $vystup;                                
			}else if($typ_zobrazeni=="top_info"){
                             if(strlen(strip_tags($this->radek["popis"])) > 380){
                                $text_base = strip_tags($this->radek["popis"]);
                                $konec_textu = strpos($text_base, ".", 380);
                                $konec_textu2 = strpos($text_base, "!", 380);
                                $konec_textu3 = strpos($text_base, "?", 380);
                                $konec_textu4 = strpos($text_base, " ", 380);

                                if($konec_textu < 450 and $konec_textu >= 380 ) {
                                    $text = substr($text_base, 0, ($konec_textu + 1));
                                    $pouzity_konec_textu = ($konec_textu + 1);
                                }else if($konec_textu2 < 450 and $konec_textu2 >= 380 ) {
                                        $text = substr($text_base, 0, ($konec_textu2 + 1));
                                        $pouzity_konec_textu = ($konec_textu2 + 1);
                                    }else if($konec_textu3 < 450 and $konec_textu3 >= 380 ) {
                                            $text = substr($text_base, 0, ($konec_textu3 + 1));
                                            $pouzity_konec_textu = ($konec_textu3 + 1);
                                        }else if($konec_textu4 < 450 and $konec_textu4 >= 380 ) {
                                                $text = substr($text_base, 0, ($konec_textu4))."...";
                                                $pouzity_konec_textu = ($konec_textu4);
                                            }else {
                                                $text = substr($text_base, 0, 380)."...";
                                                $pouzity_konec_textu = 380;
                                            }

                                $this->text_left = nl2br(substr($text_base,$pouzity_konec_textu));
                                $text = nl2br($text);
                            }else{
                                $text = $this->radek["popis"];
                                $this->text_left = "";
                            }
                            $this->id_info = $this->radek["id_informace"];
                            if($this->radek["nazev"]!=Serial_list::getNameForZeme($_GET["zeme"])){
                                $nazev="<h3><em>".$this->radek["nazev"]."</em></h3>";
                            }else{
                                $nazev="";
                            }

                            $vystup = "
                                    <div class=\"round fright\"><img SRC=\"https://slantour.cz/foto/nahled/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev"]." - ".$this->radek["nazev_foto"]."\" /></div>
                                    ".$nazev."
                                    <p style=\"font-size:1.1em;color:black;\">".$this->radek["popisek"]."</p>
                                   <p>".$text."</p>
                                   <a href=\"/informace/".$this->radek["nazev_web"]."\">další informace</a>
                                ";
                                return $vystup;
                        }else if($typ_zobrazeni=="box"){
                                $vystup = "
                                     <div class=\"grid_4\">
                                        <!-- .box -->
                                            <div class=\"box\" style=\"padding:5px 5px 5px 5px; height: 180px; overflow:hidden;\">
                                            <a  href=\"/informace/".$this->radek["nazev_web"]."\">
                                                <h5>".$this->radek["nazev"]."</h5></a>
                                            <a  href=\"/informace/".$this->radek["nazev_web"]."\">
                                                    <img class=\"fright\" src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev"]."\"/></a>
                                            <p>
                                                ".$this->radek["popisek"]."
                                             </p>
                                        </div>
                                        <!-- /.box -->
                                    </div>
                                ";
                                return $vystup;
                        }else if($typ_zobrazeni=="small_box"){
                                $vystup = "
                                     <div class=\"grid_2\" style=\"margin:3px 2px 3px 2px;\">
                                        <!-- .box -->
                                            <div class=\"box\" style=\"padding:2px 5px 2px 5px; height: 130px; overflow:hidden;\">
                                            <a href=\"/informace/".$this->radek["nazev_web"]."\"  title=\"".$this->radek["nazev"]."\">
                                                <h5 style=\"font-size:0.8em; height:31px;overflow:hidden;\">".$this->radek["nazev"]."</h5></a>
                                            <a  href=\"/informace/".$this->radek["nazev_web"]."\">
                                                    <img src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev"]."\"/></a>          
                                        </div>
                                        <!-- /.box -->
                                    </div>
                                ";
                                return $vystup;                                
                        }
		}
	}	
	/*metody pro pristup k parametrum*/
	function get_id_informace() { return $this->radek["id_informace"];}
	function get_nazev_informace() { return $this->radek["nazev"];}
        function get_nazev_informace_web() { return $this->radek["nazev_web"];}
        function get_popisek_informace() { return $this->radek["popisek"];
                $this->id_info = $this->radek["id_informace"];
        }
        function get_popis_informace() { return $this->radek["popis"];}
        function get_id_zeme() { return $this->radek["id_zeme"];}
        function get_hlavni_foto() { return "
            <div  class=\"round\" style=\"width:290px;overflow:hidden;margin:0 1px 1px 0px;\">
                <a href=\"https://slantour.cz/foto/full/".$this->radek["foto_url"]."\" class=\"highslide\"  onclick=\"return hs.expand(this)\">
                <img src=\"https://slantour.cz/foto/nahled/".$this->radek["foto_url"]."\"
                    width=\"290\" style=\"margin:0;padding:0;border:none;\"
                    alt=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\" 
                    title=\"".$this->get_nazev_foto().$this->get_popisek_foto()."\"    />
                </a>
                <div class=\"highslide-caption\">
                    ".$this->get_nazev_foto().$this->get_popisek_foto()."
                </div> 
            </div>             ";
       
        }
        function get_nazev_foto() { return $this->radek["nazev_foto"];}
	function get_popisek_foto() { 
            if($this->radek["popisek_foto"]!=""){
                return ", ".$this->radek["popisek_foto"];
            }else{
               return $this->radek["popisek_foto"];
            }
        }
	function get_zamereni_lazni() { return $this->radek["popis_lazni"];}
        function get_info_o_stredisku() { return $this->radek["popis_strediska"];}
        
	function get_pocet_radku() { return $this->pocet_radku;}
}



?>
