<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of destinace_list
 *
 * @author lpeska
 */
class Destinace_list extends Generic_list{
    //put your code here

	protected $podtyp;
	protected $id_destinace;
	protected $nazev_zeme;
	protected $nazev_zeme_cz;
	protected $zacatek;
	protected $order_by;
	protected $pocet_zaznamu;
	
	protected $pocet_zajezdu;
	
	protected $database; //trida pro odesilani dotazu
        //
//------------------- KONSTRUKTOR  -----------------	
	/**konstruktor podle specifikovaného filtru na typ, podtyp a zemi*/
	function __construct($nazev_zeme, $podtyp, $order_by, $typ="list", $zacatek=0,  $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
			
	//kontrola vstupnich dat
		$this->typ = $this->check($typ); //odpovida poli nazev_typ_web
		$this->podtyp = $this->check($podtyp);//odpovida poli nazev_typ_web
		$this->nazev_zeme = $this->check($nazev_zeme);//odpovida poli nazev_zeme_web
		$this->id_destinace = $this->check_int($id_destinace);//odpovida poli id_destinace
		$this->zacatek = $this->check($zacatek); 
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check($pocet_zaznamu); 		
	
				
	//ziskam celkovy pocet zajezdu ktere odpovidaji


	//ziskani seznamu z databaze	
		$this->data=$this->database->query( $this->create_query($this->typ) )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
		
	}  
        
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku,$only_count=0){
            
		
            if ($this->nazev_zeme != "") {//byl vybran take typ serialu
                $zeme = " and `zeme`.`nazev_zeme_web` like \"%" . $this->nazev_zeme . "%\" ";
            }
            if($this->podtyp!=""){//byl vybran take typ serialu
                $typ_pobytu = " and `serial`.`podtyp` like \"%".$this->podtyp."%\" ";
            }
        if ($typ_pozadavku == "list") {    
            $dotaz = "
        select distinct `destinace`.`id_destinace`, `destinace`.`nazev_destinace`,
        `foto`.`foto_url`
        from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1 ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme` ".$zeme.")
                    join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    left join (
                       `informace` join
                       foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                       foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                    ) on (`informace`.`id_informace` = `destinace`.`id_info`)
		    where `serial`.`id_typ` = 3 and 
                        (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) )
                       " . $typ_pobytu . "
                    order by ".$this->order_by($this->order_by)."
                    ";
            
        }
        //echo $dotaz;
        return $dotaz;
    }	


	
/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "nazev_up":
				 return "`destinace`.`nazev_destinace`";
   			 break;
			case "random":
				 return "RAND()";
   			 break;
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`informace`.`nazev`";
	}        



/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
                $nazev_destinace_web = $this->nazev_web($this->radek["nazev_destinace"]);
                $promenne = "";
                if($_GET["typ"]!=""){
                    $promenne = "?typ=".$_GET["typ"];
                }
		if($typ_zobrazeni=="list_item"){
			return "
                             <a href=\"/zeme/".$this->nazev_zeme."/".$nazev_destinace_web.$promenne."\">".$this->radek["nazev_destinace"]."</a>
                            ";
					
		}else if($typ_zobrazeni=="list_foto"){                    
                    if($this->radek["foto_url"]!=""){
                        $foto = "<img width=\"142\" style=\"margin:0;padding:0;border:none;\" height=\"85\" src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" />";
                    }else{
                        $foto = "<img width=\"142\" style=\"margin:0;padding:0;border:none;\"  height=\"85\" src=\"http://lazenske-pobyty.info/images/empty_image.jpg\"/>";
                    }                    
			return 
                            "<div class=\"round\" style=\"height:85px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">".
                            $foto.
                                "<a href=\"/zeme/".$this->nazev_zeme."/".$nazev_destinace_web.$promenne."\" style=\"display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;\">".$this->radek["nazev_destinace"]." </a>
                            </div>";
					
		}else{
			return "";
		}		
	}	
	
}
?>
