<?php
/** 
* trida pro zobrazeni menu katalogu
*/
 
/*--------------------- MENU PRO KATALOG -------------------------------------------*/
class Menu_katalog extends Generic_list{
	//vstupni parametry
    protected $typ_pozadavku;
	protected $typ;
	protected $id_typ;
	protected $nazev_zeme;
        protected $last_ubytovani;
	protected $nazev_serialu; 
	protected $pocet_zajezdu;
        protected $last_destinace;
	public $database; //trida pro odesilani dotazu	
        
        private $description = array(
            
            "pobytove-zajezdy" => " - Chorvatsko, Itálie, Španělsko, Francie - dovolená u moře; pobyty v Čechách a na Slovensku",
            
            "poznavaci-zajezdy" => " - Poznávací zájezdy do Francie, Itálie, Rakouska, Německa, Mexika, Indie, Slovenska i jinam",
            
            "lazenske-pobyty" => " - Česko, Morava, Slovensko, Maďarsko, Německo - lázně, wellness, termály, pobyty pro seniory...",
            
            "za-sportem" => " - Premier League, Serie A, Formule 1, Moto GP, Tenisové turnaje,  zápasy Českých národních týmů",
            "lyzovani" => " - Lyžování v Alpách ve Francii, Itálii a Rakousku",
            "jednodenni-zajezdy" => " - Drážďany, Passov, Tropický ostrov, Plavby lodí, Vídeň atd.",
            "pobyty-hory" => " - Pobyty v Krkonoších, Jeseníkách, Beskydech, na Šumavě, Malé Fatře, Vysokých či Nízkých Tatrách...",
            "exotika" => " - Mexiko (Cancun), Bali, Filipíny, Spojené Arabské Emiráty (Dubai, Abu Dhabi)"
        );
//------------------- KONSTRUKTOR -----------------
	/**
	*	konstruktor třídy 
	* @param $typ = nazev_typ_web tabulky typ_serial
	* @param $nazev_zeme = nazev_zeme_web tabulky zeme
	* @param $nazev_serialu = nazev_serial_web tabulky serial
	*/
	function __construct($typ_pozadavku,$typ,$nazev_zeme,$nazev_serialu,$limit=20,$nazev_destinace="",$id_destinace="",$id_ubytovani=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
		
                $this->typ_pozadavku = $this->check($typ_pozadavku);
		$this->typ = $this->check($typ);
		$this->nazev_zeme = $this->check($nazev_zeme);
		$this->nazev_serialu = $this->check($nazev_serialu);
                $this->nazev_destinace = $this->check($nazev_destinace);
                $this->id_destinace = $this->check_int($id_destinace);
                $this->id_ubytovani = $this->check_int($id_ubytovani);
		$this->limit = intval($limit);
                $this->last_ubytovani = "";
                $this->last_destinace = "";
                $this->id_typ = "";
	//ziskani serialu z databaze	
                if($this->typ_pozadavku == "dotaz_top_zeme"){
                    //nedelam nic
                    
                }else if($this->create_query($this->typ_pozadavku)!=""){
                    $this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 	or $this->chyba("Chyba při dotazu do databáze");
                    $this->pocet_zajezdu = mysqli_num_rows($this->data);
                }
		
	}	
        function get_pocet_zajezdu(){
            return $this->pocet_zajezdu;
        }
        
//------------------- METODY TRIDY -----------------	
	/** vytvoreni dotazu ze zadaneho nazvu typu a zeme*/
	function create_query($typ=""){
		//funkce vytvori vsechny radky menu na jeden dotaz, do nej jsou pridavany tabulky podle toho, ktere parametry dostavame
      if($typ=="dotaz_zeme_destinace_ubytovani"){                    
			//mam nazev zeme i typu
				$dotaz="
					SELECT DISTINCT `foto`.`foto_url`,
                                                        `destinace_serial`.`id_destinace` , `destinace`.`nazev_destinace`,
                                                        `zeme`.`id_zeme`, `zeme`.`nazev_zeme`,
                                                        `serial`.`id_typ`,`serial`.`id_serial`,`serial`.`nazev`,`serial`.`id_sablony_zobrazeni`,
                                                        `objekt`.`id_objektu`, `objekt`.`nazev_objektu` as `nazev_ubytovani`
					FROM 
                                            `zeme`
                                            join `zeme_serial` on (`zeme_serial`.`id_zeme` = `zeme`.`id_zeme` AND `zeme_serial`.`zakladni_zeme` =1)

                                            join `serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`  and `serial`.`nezobrazovat`<>1)
                                            join `zajezd` ON ( `serial`.`id_serial` = `zajezd`.`id_serial` and `zajezd`.`nezobrazovat_zajezd`<>1 and  (`zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`serial`.`dlouhodobe_zajezdy`=1 and `zajezd`.`do` >=\"".Date("Y-m-d")."\") ) )                                           
                                                
                                            left join (
                                                `destinace` 
                                                join `destinace_serial` on (`destinace_serial`.`id_destinace` = `destinace`.`id_destinace` AND `destinace_serial`.`polozka_menu` =1)
                                            ) ON ( `zeme`.`id_zeme` = `destinace`.`id_zeme` and `serial`.`id_serial` = `destinace_serial`.`id_serial`)

                                            left join
                                                (`objekt_serial` join
                                                 `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                                                 `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                                                 ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial` and `serial`.`id_sablony_zobrazeni` = 12)
                                            left join 
                                                (`foto_serial` join `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto` and `foto_serial`.`zakladni_foto` = 1)
                                                )on (`foto_serial`.`id_serial` = `serial`.`id_serial`)

					ORDER BY `zeme`.`nazev_zeme`, `destinace`.`nazev_destinace`, `nazev_ubytovani`, serial.nazev
					";
                //echo $dotaz;
                return $dotaz;
            }
		if($this->typ!=""){
			//ziskam id typu serialu
			$dotaz_typ= "select `id_typ` from `typ_serial` where `nazev_typ_web`=\"".$this->typ."\"";
			$data_typ=$this->database->query($dotaz_typ);
			$zaznam_typ=mysqli_fetch_array($data_typ);
			$id_typ=$zaznam_typ["id_typ"];
            $this->id_typ = $id_typ;
		}
		if($this->nazev_zeme!=""){
			//ziskam id země
			$dotaz_zeme= "select `id_zeme` from `zeme` where `nazev_zeme_web`=\"".$this->nazev_zeme."\"";
			$data_zeme=$this->database->query($dotaz_zeme);
			$zaznam_zeme=mysqli_fetch_array($data_zeme);
			$id_zeme=$zaznam_zeme["id_zeme"];
		}
		if($id_typ != ""){
                        $where_typ = " AND `serial`.`id_typ` =".$id_typ."";
                    }else{
                        $where_typ = " ";
                    }                           
		if($id_zeme!="" and $typ=="dotaz_destinace"){
                    
			//mam nazev zeme i typu
				$dotaz="
					SELECT DISTINCT `foto`.`foto_url`,`destinace_serial`.`id_destinace` , `destinace`.`nazev_destinace`,`zeme`.`nazev_zeme_web`
					FROM `destinace`
                                            join `destinace_serial` on (`destinace_serial`.`id_destinace` = `destinace`.`id_destinace` AND `destinace_serial`.`polozka_menu` =1)
                                            join `serial` on (`serial`.`id_serial` = `destinace_serial`.`id_serial`  and `serial`.`nezobrazovat`<>1)
                                            join `zajezd` ON ( `serial`.`id_serial` = `zajezd`.`id_serial` and `zajezd`.`nezobrazovat_zajezd`<>1 and  (`zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`serial`.`dlouhodobe_zajezdy`=1 and `zajezd`.`do` >=\"".Date("Y-m-d")."\") ) )
                                            join `typ_serial` on ( `typ_serial`.`id_typ` = `serial`.`id_typ` ".$where_typ." )   
                                            join `zeme` ON ( `zeme`.`id_zeme` = `destinace`.`id_zeme`)
                                            left join (
                                                    `informace` join
                                                     foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                                     foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                                ) on (`informace`.`id_informace` = `destinace`.`id_info`)
						
					WHERE `destinace`.`id_zeme` = ".$id_zeme." 
					ORDER BY `destinace`.`nazev_destinace`
					";
                               // echo $dotaz;
                
                                
                }else if($id_zeme!="" and $typ=="dotaz_destinace_serial"){
                       if($id_typ != ""){
                        $where_typ = " AND `serial`.`id_typ` =".$id_typ."";
                    }else{
                        $where_typ = " ";
                    }
                    if($id_zeme != ""){
                        $where_zeme = " AND `zeme_serial`.`id_zeme` =".$id_zeme."";
                    }else{
                        $where_zeme = " ";
                    }
                    if($this->id_destinace != ""){
                        $where_destinace = " AND `destinace_serial`.`id_destinace` =".$this->id_destinace."";
                    }else{
                        $where_destinace = " ";
                    }
                    if($this->typ!="za-sportem" and $this->typ!="poznavaci-zajezdy"){
                        $select_objekt = "`objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,";
                        $from_objekt = "left join
                       (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)";  
                        $order_by_objekt = ",`nazev_ubytovani`,`nazev`";
                        
                        if($this->typ=="eurovikendy" or $this->typ=="fly-and-drive"){
                              $order_by_objekt = ",`nazev`,`nazev_ubytovani`";
                        }
                    }else{
                        $select_objekt="";
                        $from_objekt="";
                         $order_by_objekt = ",`nazev`";
                    }
                $select="select  distinct 
                                            
                    $select_objekt
   
                    `serial`.`nazev`, `serial`.`nazev_web`, `serial`.`id_serial`,`serial`.`id_sablony_zobrazeni`, `foto`.`foto_url`,
                     `destinace`.`nazev_destinace`,`zeme`.`nazev_zeme_web`";
            	 $dotaz= $select."
                    from `serial` $from_objekt  join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial` AND `zeme_serial`.`polozka_menu` =1) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial` and `destinace_serial`.`polozka_menu`=1)
                    left join (
                         foto_serial  join
                         foto on (`foto_serial`.`id_foto` = `foto`.`id_foto`)
                    ) on (`serial`.`id_serial` = `foto_serial`.`id_serial` and `foto_serial`.`zakladni_foto` = 1)
						
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                          (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) ".$where_typ.$where_zeme.$where_destinace." 
                    order by `destinace`.`nazev_destinace` $order_by_objekt   
                    ";  
                              //echo $dotaz;                 
               }else if( $typ=="dotaz_serialy"){  
                   
                     if($id_typ != ""){
                        $where_typ = " AND `serial`.`id_typ` =".$id_typ."";
                    }else{
                        $where_typ = " ";
                    }
                    if($id_zeme != ""){
                        $where_zeme = " AND `zeme_serial`.`id_zeme` =".$id_zeme."";
                    }else{
                        $where_zeme = " ";
                    }
                    if($this->id_destinace != ""){
                        $where_destinace = " AND `destinace_serial`.`id_destinace` =".$this->id_destinace."";
                    }else{
                        $where_destinace = " ";
                    }
                    if($this->typ!="za-sportem"  and $this->typ!="poznavaci-zajezdy"){
                        $select_objekt = "`objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,";
                        $from_objekt = "left join
                       (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)";  
                        $order_by_objekt = "`nazev_ubytovani`,";
                    }else{
                        $select_objekt="";
                        $from_objekt="";
                         $order_by_objekt = "";
                    }
                $select="select  distinct
                     $select_objekt
                    `serial`.`nazev`, `serial`.`nazev_web`, `serial`.`id_serial`,`serial`.`id_sablony_zobrazeni`, `foto`.`foto_url`";
            	 $dotaz= $select."
                    from `serial` $from_objekt  join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial` AND `zeme_serial`.`polozka_menu` =1) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    left join (
                         foto_serial  join
                         foto on (`foto_serial`.`id_foto` = `foto`.`id_foto`)
                    ) on (`serial`.`id_serial` = `foto_serial`.`id_serial` and `foto_serial`.`zakladni_foto` = 1)
						
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                          (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) ".$where_typ.$where_zeme.$where_destinace." 
                    order by $order_by_objekt `nazev`
                    ";  
                // echo $dotaz;
                 
             }else if( $typ=="dotaz_ubytovani"){  
                   
                     if($id_typ != ""){
                        $where_typ = " AND `serial`.`id_typ` =".$id_typ."";
                    }else{
                        $where_typ = " ";
                    }
                    if($id_zeme != ""){
                        $where_zeme = " AND `zeme_serial`.`id_zeme` =".$id_zeme."";
                    }else{
                        $where_zeme = " ";
                    }
                    if($this->id_destinace != ""){
                        $where_destinace = " AND `destinace_serial`.`id_destinace` =".$this->id_destinace."";
                    }else{
                        $where_destinace = " ";
                    }
                $select="select  distinct 
                    `objekt_ubytovani`.`nazev_ubytovani` as `nazev`, `objekt_ubytovani`.`nazev_web`, `foto`.`foto_url`";
            	 $dotaz= $select."
                    from `serial` join
                    (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)  join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial` AND `zeme_serial`.`polozka_menu` =1) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    left join (`foto_objekty` join
                                `foto` on (`foto_objekty`.`id_foto` = `foto`.`id_foto`) )
                             on (`foto_objekty`.`id_objektu` = `objekt`.`id_objektu` and `foto_objekty`.`zakladni_foto`=1)
			
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  
                          and  (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) ".$where_typ.$where_zeme.$where_destinace."                             
                    "; 
                // echo $dotaz;
                 
           }else if( $typ=="dotaz_serialy_ubytovani"){  
                   
                     if($id_typ != ""){
                        $where_typ = " AND `serial`.`id_typ` =".$id_typ."";
                    }else{
                        $where_typ = " ";
                    }
                    if($id_zeme != ""){
                        $where_zeme = " AND `zeme_serial`.`id_zeme` =".$id_zeme."";
                    }else{
                        $where_zeme = " ";
                    }
                    if($this->id_destinace != ""){
                        $where_destinace = " AND `destinace_serial`.`id_destinace` =".$this->id_destinace."";
                    }else{
                        $where_destinace = " ";
                    }
                    if($this->id_ubytovani != ""){
                        $where_ubytovani = " AND `objekt`.`id_objektu` =".$this->id_ubytovani."";
                    }else{
                        $where_ubytovani = " ";
                    }
                $select="select  distinct 
                    `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`, 
                    `serial`.`nazev`, `serial`.`nazev_web`, `serial`.`id_serial`, `foto`.`foto_url`";
            	 $dotaz= $select."
                    from `serial` join                   
                    (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial` AND `zeme_serial`.`polozka_menu` =1) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    left join (
                         foto_serial  join
                         foto on (`foto_serial`.`id_foto` = `foto`.`id_foto`)
                    ) on (`serial`.`id_serial` = `foto_serial`.`id_serial` and `foto_serial`.`zakladni_foto` = 1)
						
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                          (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) ".$where_typ.$where_zeme.$where_destinace.$where_ubytovani." 
                    ";  
                // echo $dotaz;
                 
         }else if( $typ=="dotaz_podtyp"){  
                   
                    if($id_typ != ""){
                        $where_typ = " AND `serial`.`id_typ` =".$id_typ."";
                    }else{
                        $where_typ = " ";
                    }
                    if($id_zeme != ""){
                        $where_zeme = " AND `zeme_serial`.`id_zeme` =".$id_zeme."";
                    }else{
                        $where_zeme = " ";
                    }
                    if($this->id_destinace != ""){
                        $where_destinace = " AND `destinace_serial`.`id_destinace` =".$this->id_destinace."";
                    }else{
                        $where_destinace = " ";
                    }
                $select="select  distinct `serial`.`podtyp` as `podtyp`";
            	 $dotaz= $select."
                    from `serial` left join
                    (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial` AND `zeme_serial`.`polozka_menu` =1) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  ".$where_typ.$where_zeme.$where_destinace." 
                    ";   
                  
		}else if( $typ=="dotaz_zeme_typ"){
			//mam pouze nazev typu
				$dotaz="
					SELECT DISTINCT `foto`.`foto_url`, `cross_zeme`.`id_zeme`,  `cross_zeme`.`nazev_zeme` , `cross_zeme`.`nazev_zeme_web` 
					FROM `typ_serial`

					JOIN (
						`zeme_serial` AS `cross_zeme_serial` 
						JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme`  AND `cross_zeme_serial`.`polozka_menu` =1 )
                                                left join (
                                                    `informace` join
                                                     foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                                     foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                                ) on (`informace`.`id_informace` = `cross_zeme`.`id_info`)
						join `serial` AS `cross_serial`  ON ( `cross_serial`.`id_serial` = `cross_zeme_serial`.`id_serial` and `cross_serial`.`jazyk` != \"english\" and `cross_serial`.`nezobrazovat`<>1)
						join `zajezd` AS `cross_zajezd` ON ( `cross_serial`.`id_serial` = `cross_zajezd`.`id_serial` and `cross_zajezd`.`nezobrazovat_zajezd`<>1 and  (`cross_zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`cross_serial`.`dlouhodobe_zajezdy`=1 and `cross_zajezd`.`do` >=\"".Date("Y-m-d")."\") ))
					) ON ( `typ_serial`.`id_typ` = `cross_serial`.`id_typ` )
					WHERE `typ_serial`.`nazev_typ_web` =\"".$this->typ."\" 
					ORDER BY `typ_serial`.`id_typ` , `cross_zeme`.`nazev_zeme` 
					";			
		//echo $dotaz;		
                
		}else if( $typ=="dotaz_zeme"){
			//mam pouze nazev typu
				$dotaz="
					SELECT DISTINCT `typ_serial`.`id_typ` , `typ_serial`.`nazev_typ` , `typ_serial`.`nazev_typ_web` ,  `cross_zeme`.`id_zeme`,  `cross_zeme`.`nazev_zeme` , `cross_zeme`.`nazev_zeme_web` 
					FROM `typ_serial`

					JOIN (
						`zeme_serial` AS `cross_zeme_serial` 
						JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme`  AND `cross_zeme_serial`.`polozka_menu` =1)                                                
						join `serial` AS `cross_serial`  ON ( `cross_serial`.`id_serial` = `cross_zeme_serial`.`id_serial` and `cross_serial`.`jazyk` != \"english\" and `cross_serial`.`nezobrazovat`<>1)
						join `zajezd` AS `cross_zajezd` ON ( `cross_serial`.`id_serial` = `cross_zajezd`.`id_serial` and `cross_zajezd`.`nezobrazovat_zajezd`<>1 and  (`cross_zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`cross_serial`.`dlouhodobe_zajezdy`=1 and `cross_zajezd`.`do` >=\"".Date("Y-m-d")."\") ))
					) ON ( `typ_serial`.`id_typ` = `cross_serial`.`id_typ` )
					WHERE `typ_serial`.`id_nadtyp` =0 
					ORDER BY `typ_serial`.`id_typ` , `cross_zeme`.`nazev_zeme` 
					";			
		//echo $dotaz;			
		}else if( $typ=="dotaz_zeme_list"){
            if($id_typ != ""){
                $where_typ = " AND `cross_serial`.`id_typ` =".$id_typ."";
            }else{
                $where_typ = " ";
            }
			// vsechny zeme s aktivnim zajezdem s foto
				$dotaz="
                    SELECT DISTINCT  `cross_zeme`.`id_zeme`,  `cross_zeme`.`nazev_zeme` , `cross_zeme`.`nazev_zeme_web` , foto.foto_url
                    FROM 
                    `zeme_serial` AS `cross_zeme_serial` 
                    JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme` )                                                
                    join `serial` AS `cross_serial`  ON ( `cross_serial`.`id_serial` = `cross_zeme_serial`.`id_serial` and `cross_serial`.`jazyk` != \"english\" and `cross_serial`.`nezobrazovat`<>1)
                    join `zajezd` AS `cross_zajezd` ON ( `cross_serial`.`id_serial` = `cross_zajezd`.`id_serial` and `cross_zajezd`.`nezobrazovat_zajezd`<>1 and  (`cross_zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`cross_serial`.`dlouhodobe_zajezdy`=1 and `cross_zajezd`.`do` >=\"".Date("Y-m-d")."\") )) 
                                            
                    left join (
                                `informace` join
                                foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                            ) on (`informace`.`id_informace` = `cross_zeme`.`id_info`)
                    WHERE cross_zeme.geograficka_zeme = 1 ".$where_typ." 
                    ORDER BY `cross_zeme`.`nazev_zeme`;
					";	
        }else if( $typ=="dotaz_sport_list"){
                    // vsechny zeme s aktivnim zajezdem s foto
                        $dotaz="
                            SELECT DISTINCT  `cross_zeme`.`id_zeme`,  `cross_zeme`.`nazev_zeme` , `cross_zeme`.`nazev_zeme_web` , foto.foto_url
                            FROM 
                            `zeme_serial` AS `cross_zeme_serial` 
                            JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme` )                                                
                            join `serial` AS `cross_serial`  ON ( `cross_serial`.`id_serial` = `cross_zeme_serial`.`id_serial` and `cross_serial`.`jazyk` != \"english\" and `cross_serial`.`nezobrazovat`<>1)
                            join `zajezd` AS `cross_zajezd` ON ( `cross_serial`.`id_serial` = `cross_zajezd`.`id_serial` and `cross_zajezd`.`nezobrazovat_zajezd`<>1 and  (`cross_zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`cross_serial`.`dlouhodobe_zajezdy`=1 and `cross_zajezd`.`do` >=\"".Date("Y-m-d")."\") )) 
                                                    
                            left join (
                                        `informace` join
                                        foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                        foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                    ) on (`informace`.`id_informace` = `cross_zeme`.`id_info`)
                            WHERE cross_zeme.geograficka_zeme = 0 and `cross_serial`.`id_typ` = 4 and  `cross_zajezd`.`nezobrazovat_zajezd`<>1 and `cross_serial`.`nezobrazovat`<>1
                            ORDER BY `cross_zeme`.`nazev_zeme`
                            ";	
                            // echo $dotaz;                    


        }else if( $typ=="dotaz_zeme_from_nazev"){
                    // zeme z nazev_zeme_web
                        $dotaz="
                            SELECT DISTINCT  `cross_zeme`.`id_zeme`,  `cross_zeme`.`nazev_zeme` , `cross_zeme`.`nazev_zeme_web` , foto.foto_url
                            FROM 
                            `zeme_serial` AS `cross_zeme_serial` 
                            JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme` )                                                
                                                                                
                            left join (
                                        `informace` join
                                        foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                        foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                    ) on (`informace`.`id_informace` = `cross_zeme`.`id_info`)
                            WHERE cross_zeme.nazev_zeme_web = \"".$this->nazev_zeme."\"
                            ORDER BY `cross_zeme`.`nazev_zeme`
                            ";	                    
		//echo "<br><br>";			
		//echo $dotaz;			
		}else if($typ=="dotaz_typy"){
			//nemam ani nazev typu ani zeme
                            if($this->nazev_zeme!=""){
                                $where_zeme = "and `cross_zeme`.`nazev_zeme_web` =\"".$this->nazev_zeme."\" ";
                            }
                            if($this->id_destinace!=""){
                                $from_destinace = "join `destinace_serial` on (`cross_serial`.`id_serial` = `destinace_serial`.`id_serial`)";
                                $where_destinace = "and `destinace_serial`.`id_destinace`=".$this->id_destinace." ";
                            }
                        	$dotaz="
					SELECT DISTINCT `typ_serial`.`id_typ` , `typ_serial`.`nazev_typ` , `typ_serial`.`nazev_typ_web` , `foto_url`, `text_foto`, `style_text`
					FROM `typ_serial`
                                        left join `foto` on (`typ_serial`.`id_foto` = `foto`.`id_foto`)

					JOIN (
						`zeme_serial` AS `cross_zeme_serial` 
						JOIN `zeme` AS `cross_zeme`  ON ( `cross_zeme_serial`.`id_zeme` = `cross_zeme`.`id_zeme`   AND `cross_zeme_serial`.`polozka_menu` =1 )                                                
						join `serial` AS `cross_serial`  ON ( `cross_serial`.`id_serial` = `cross_zeme_serial`.`id_serial` and `cross_serial`.`jazyk` != \"english\" and `cross_serial`.`nezobrazovat`<>1)
						join `zajezd` AS `cross_zajezd` ON ( `cross_serial`.`id_serial` = `cross_zajezd`.`id_serial` and `cross_zajezd`.`nezobrazovat_zajezd`<>1 and  (`cross_zajezd`.`od` >=\"".Date("Y-m-d")."\" or (`cross_serial`.`dlouhodobe_zajezdy`=1 and `cross_zajezd`.`do` >=\"".Date("Y-m-d")."\") ))
                                                ".$from_destinace."
					) ON ( `typ_serial`.`id_typ` = `cross_serial`.`id_typ` )
					WHERE `typ_serial`.`id_nadtyp` =0 ".$where_zeme.$where_destinace."
					ORDER BY `typ_serial`.`id_typ` , `cross_zeme`.`nazev_zeme` ";
                    //echo $dotaz;

		}else if($typ=="dotaz_top_zeme"){
			//nemam ani nazev typu ani zeme
                    $datum_rezervace=(Date("Y")-1)."-".Date("m")."-".Date("d");
                    $dotaz = "
                        SELECT sum( `celkova_cena` ) as `pocet` , `zeme`.`nazev_zeme`, `zeme`.`id_zeme`, `zeme`.`nazev_zeme_web`
                        FROM `serial`
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` and `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`   AND `zeme_serial`.`polozka_menu` =1) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        WHERE  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `objednavka`.`rezervace_do` > \"".$datum_rezervace."\" and ".$where_typ.$where_text.$where_nazev_serialu.$where_nazev.$where_zeme.$where_destinace.$where_od.$where_do." 
                            `zeme`.`nazev_zeme_web` not in (\"ceska-republika-vikendove-pobyty\") and
                            `serial`.`id_typ` != 4
                        GROUP BY `zeme`.`id_zeme`
                        ORDER BY `pocet`  desc 
                        limit 12
                    ";
                //echo $dotaz;
                 }else if($typ=="dotaz_top_zeme_foto"){
			//nemam ani nazev typu ani zeme
                    $datum_rezervace=(Date("Y")-1)."-".Date("m")."-".Date("d");
                    $dotaz = "
                        SELECT sum( `celkova_cena` ) as `pocet` , `foto`.`foto_url`,  `zeme`.`nazev_zeme`, `zeme`.`id_zeme`, `zeme`.`nazev_zeme_web`
                        FROM `serial`
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` and `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`  AND `zeme_serial`.`polozka_menu` =1) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        left join (
                                                    `informace` join
                                                     foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                                     foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                                ) on (`informace`.`id_informace` = `zeme`.`id_info`)
						
                        WHERE  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `objednavka`.`rezervace_do` > \"".$datum_rezervace."\" ".$where_typ." and ".$where_text.$where_nazev_serialu.$where_nazev.$where_zeme.$where_destinace.$where_od.$where_do." 
                            `zeme`.`nazev_zeme_web` not in (\"ceska-republika-vikendove-pobyty\") and
                            `serial`.`id_typ` != 4
                        GROUP BY `zeme`.`id_zeme`
                        ORDER BY `pocet`  desc 
                        limit ".$this->limit."
                    ";
           //      echo $dotaz;   //.$where_typ
                return $dotaz;	
            }else if($typ=="dotaz_top_sporty_zeme"){
			//nemam ani nazev typu ani zeme
                    $datum_rezervace=(Date("Y")-2)."-".Date("m")."-".Date("d");
                    $dotaz = "
                        SELECT sum( `celkova_cena` ) as `pocet`, `zeme`.`nazev_zeme`, `zeme`.`id_zeme`, `zeme`.`nazev_zeme_web`, `foto`.`foto_url`
                        FROM `serial`
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` and `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`  AND `zeme_serial`.`polozka_menu` =1) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        left join (
                                `informace` join
                                foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace` and `foto_informace`.`zakladni_foto` = 1) join
                                foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                            ) on (`informace`.`id_informace` = `zeme`.`id_info`)
                        WHERE `objednavka`.`rezervace_do` > \"".$datum_rezervace."\" and                             
                            `serial`.`id_typ` = 4 and (`destinace`.`id_destinace` is NULL or `zeme`.`id_zeme` not in (54,57,59)) and `zeme`.`geograficka_zeme`=0
                        GROUP BY `zeme`.`id_zeme`
                        ORDER BY `pocet`  desc 
                        limit 20
                    ";
                //echo $dotaz;
                return $dotaz;		
            
		}else if($typ=="dotaz_top_sporty_destinace"){
			//nemam ani nazev typu ani zeme
                    $datum_rezervace=(Date("Y")-2)."-".Date("m")."-".Date("d");
                    $dotaz = "
                        SELECT sum( `celkova_cena` ) as `pocet` , `zeme`.`nazev_zeme`, `zeme`.`id_zeme`, `zeme`.`nazev_zeme_web`, `destinace`.`id_destinace`,`destinace`.`nazev_destinace`
                        FROM `serial`
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` and `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                        join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                            JOIN `zeme` on (`destinace`.`id_zeme` =`zeme`.`id_zeme`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        WHERE `objednavka`.`rezervace_do` > \"".$datum_rezervace."\" and                              
                            `serial`.`id_typ` = 4  and `zeme`.`geograficka_zeme`=0
                        GROUP BY `zeme`.`id_zeme`,`destinace`.`id_destinace`
                        ORDER BY `pocet`  desc 
                        limit 50
                    ";
                //echo $dotaz;
                return $dotaz;
            
		}else if($typ=="dotaz_mozne_sporty"){
			//nemam ani nazev typu ani zeme
                    $datum_rezervace=(Date("Y")-2)."-".Date("m")."-".Date("d");
                    //JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` and `objednavka`.`id_zajezd` = `zajezd`.`id_zajezd` )
                    //`objednavka`.`rezervace_do` > \"".$datum_rezervace."\" and
                    $dotaz = "
                        SELECT distinct `zeme`.`id_zeme`, `destinace`.`id_destinace`
                        FROM `serial`
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`  AND `zeme_serial`.`polozka_menu` =1) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        WHERE  
                            (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and
                            `serial`.`id_typ` = 4 and  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1  and `zeme`.`geograficka_zeme`=0                                              
                    ";
               echo $dotaz;
            return $dotaz;				
		}
		
		
		
		//echo $dotaz;
		return $dotaz;
	}		  
        

        function get_allRows(){
            $ret = [];
            while($this->get_next_radek()){
                $this->radek["foto_url"] = "https://slantour.cz/foto/ico/".$this->radek["foto_url"];
                
                if($this->radek["id_sablony_zobrazeni"] == 12){
                   $this->radek["nazev_final"] = $this->radek["nazev_ubytovani"] ;
                   $this->radek["id_final"] = $this->radek["id_objektu"]; 
                }else{
                   $this->radek["nazev_final"] = $this->radek["nazev"] ;
                   $this->radek["id_final"] = $this->radek["id_serial"];  
                }
                    
                    
                $ret[] = $this->radek;  
            }
            return $ret;
        }         
        
        function get_zeme_list(){
            $ret = [];
            while($this->get_next_radek()){
                //echo $this->radek["nazev_zeme_web"];
                $this->radek["foto_url"] = "https://slantour.cz/foto/nahled/".$this->radek["foto_url"];
                $tourData = $this->get_country_data($this->radek["id_zeme"]);
                
                if ($tourData["min_cena"] == null) {
                    $tourData["min_cena"] = 0;
                }
                $this->radek["tourCount"] = $tourData["countSerial"];
                $this->radek["tourPrice"] = $tourData["min_cena"];
                
                $ret[$this->radek["nazev_zeme_web"]] = $this->radek;  
                //echo "</br>";
            }
            return $ret;
        } 

        function get_zeme($nazev_zeme_web){
            $ret = [];
            while($this->get_next_radek()){
                // echo $this->radek["nazev_zeme_web"];
                if ($this->radek["nazev_zeme_web"] == $nazev_zeme_web) {
                    $this->radek["foto_url"] = "https://slantour.cz/foto/full/".$this->radek["foto_url"];
                    $tourData = $this->get_country_data($this->radek["id_zeme"]);
                    if ($tourData["min_cena"] == null) {
                        $tourData["min_cena"] = 0;
                    }
                    $this->radek["tourCount"] = $tourData["countSerial"];
                    $this->radek["tourPrice"] = $tourData["min_cena"];
                    
                    $ret = $this->radek;
                    break;
                }
                // echo " </br>";
            }
            return $ret;
        } 

        function get_country_data($id_zeme){
            if($this->id_typ != ""){
                $where_typ = " AND `serial`.`id_typ` =".$this->id_typ."";
            }else{
                $where_typ = " ";
            }
            $query = "
                select count(distinct `serial`.`id_serial`) as countSerial, min(`cena_zajezd`.`castka`) as min_cena
                from zeme_serial join 
                `serial` on (`serial`.`id_serial` = `zeme_serial`.`id_serial`)  join
                `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1 and `cena_zajezd`.`castka`>100) 
                where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                    (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) 
                        and zeme_serial.id_zeme = '".$id_zeme."' ".$where_typ."
            ";
            
            $data = $this->database->query($query)
		 or $this->chyba("Chyba při dotazu do databáze");
            if ($row = mysqli_fetch_array($data)){
                return $row;                          
            }
        }

        function get_typy_pobytu(){
            $ret="";
            $i=0;            
            $ret = [];
            while($this->get_next_radek()){
                $this->radek["description"] = $this->description[$this->radek["nazev_typ_web"]];
                $this->radek["foto_url"] = "https://slantour.cz/foto/nahled/".$this->radek["foto_url"];
                $tourData = $this->get_tour_data($this->radek["nazev_typ_web"]);
                
                $this->radek["tourCount"] = $tourData["countSerial"];
                $this->radek["tourPrice"] = $tourData["min_cena"];
                
                
                $ret[$this->radek["nazev_typ_web"]] = $this->radek;  
                

            
            }
            return $ret;
        } 
        
        function get_tour_data($tour_type){
            $query = "select count(distinct `serial`.`id_serial`) as countSerial, min(`cena_zajezd`.`castka`) as min_cena
                    
                    from typ_serial join 
                    `serial` on (`serial`.`id_typ` = `typ_serial`.`id_typ`)  join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd`  and `cena_zajezd`.`nezobrazovat`!=1 and `cena_zajezd`.`castka`>100) 
						
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                          (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) 
                              and typ_serial.nazev_typ_web = '".$tour_type."'   
            ";
            
            $data = $this->database->query($query)
		 or $this->chyba("Chyba při dotazu do databáze");
            if ($row = mysqli_fetch_array($data)){
                return $row;                          
            }
        }

        function get_typ_pobytu($tour_type){
            $ret="";
            while($this->get_next_radek()){
                //echo $this->radek["nazev_typ_web"];
                if ($this->radek["nazev_typ_web"] == $tour_type) {
                    // "-true";
                    $this->radek["description"] = $this->description[$this->radek["nazev_typ_web"]];
                    $this->radek["foto_url"] = "https://slantour.cz/foto/full/".$this->radek["foto_url"];
                    $tourData = $this->get_tour_data($this->radek["nazev_typ_web"]);
                    
                    $this->radek["tourCount"] = $tourData["countSerial"];
                    $this->radek["tourPrice"] = $tourData["min_cena"];
                    $ret = $this->radek;  
                    break;
                }
                //echo "</br>";
            }
            return $ret;
        }

        function get_top_sports()
        {
            $sporty = array();
            // echo "fresh fetch </br>";
            $sportyDotaz = $this->database->query($this->create_query("dotaz_top_sporty_zeme"));
            while ($sport = mysqli_fetch_array($sportyDotaz)) {
                // echo print_r($sport);
                // echo "</br>";
                // echo "</br>";
                $sporty[$sport["id_zeme"]] = $sport;
            }
            return $sporty;
        }
        
        function show_destinace($typ_zobrazeni = "obrazkove"){
            $ret="";
            $i=0;            
            while($this->get_next_radek()){
             if($typ_zobrazeni=="obrazkove"){   
                if($_GET["typ"]){
                    $adresa = "/zajezdy/katalog/".$_GET["typ"]."/".$this->radek["nazev_zeme_web"]."/".$this->nazev_web($this->radek["nazev_destinace"])."";
                    
                    //zobrazit spravnou fotku u informaci
                    $dotaz = "
                        SELECT DISTINCT `foto`.`foto_url`
					FROM `destinace`                                             
                                            join (
                                                    `informace` join
                                                     foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace`) join
                                                      `typ_serial` on ( `typ_serial`.`id_typ` = `foto_informace`.`zakladni_pro_typ` ) join
                                                     foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                                ) on (`informace`.`id_informace` = `destinace`.`id_info`)                                                 						
					WHERE `destinace`.`id_destinace` = ".$this->radek["id_destinace"]." and `typ_serial`.`nazev_typ_web` = \"".$_GET["typ"]."\"
					LIMIT 1";
                    $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                    while ($row = mysqli_fetch_array($data)) {
                        $this->radek["foto_url"] = $row["foto_url"];
                    }
                }else{
                    $adresa = "/zajezdy/katalog/".$this->radek["nazev_zeme_web"]."/".$this->nazev_web($this->radek["nazev_destinace"])."";
                }
                
                if($this->radek["foto_url"]!=""){
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev_zeme"]."\" style=\"margin-bottom:10px;\"   class=\"leftnav\" />";
                }else{
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/img/empty_image.jpg\"/ alt=\"".$this->radek["nazev_zeme"]."\" style=\"margin-bottom:10px;\"  class=\"leftnav\" />";
                }        
                
                if(strlen($this->radek["nazev_destinace"])>=25){
                      $style_nazev = "display:block;width:146px;height:32px;background-color:#eace8e;margin-top:-32px;position:relative;z-index:10;font-weight:bold;line-height:1.2em;text-align:center;";  
                    }else{
                      $style_nazev = "display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;";    
                    }
                
                if($i % 2 ==0){
                        $ret.= "</tr><tr>";
                    }
                    $ret.= "<td>
                            <div class=\"round\" style=\"height:95px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                <a href=\"".$adresa."\" title=\"".$this->radek["nazev_destinace"]."\">
                                    ".$foto."
                                </a>
                                <a href=\"".$adresa."\" style=\"".$style_nazev."\">".$this->radek["nazev_destinace"]." </a>
                            </div>
                            </td>";
                    $i++;   
             }else{
                if($_GET["typ"]){
                    $adresa = "/zajezdy/katalog/".$_GET["typ"]."/".$this->radek["nazev_zeme_web"]."/".$this->nazev_web($this->radek["nazev_destinace"])."";
                }else{
                    $adresa = "/zajezdy/katalog/".$this->radek["nazev_zeme_web"]."/".$this->nazev_web($this->radek["nazev_destinace"])."";
                }                             
                    $ret.= "<tr><td colspan=\"2\">
                                <a href=\"".$adresa."\" >".$this->radek["nazev_destinace"]." </a>                            
                            </td></tr>";
                    $i++;   
             }      
           }
           return $ret;
        }
   
        function show_destinace_serial($typ_zobrazeni = "obrazkove"){
            $ret = "<table width=\"298\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin-top:-5px;\">";
            $i=0;      
            $this->last_destinace = "";
            $last_nazev = "";
            while($this->get_next_radek()){               
             if($typ_zobrazeni=="obrazkove"){   
                 if($this->last_destinace != $this->radek["nazev_destinace"]){
                     $this->last_destinace = $this->radek["nazev_destinace"];
                     $i=0;
                     $ret.= "<tr><td colspan=\"2\">
                                <a class=\"obj-y-b\" style=\"display:block;width:300px;padding:2px;font-size:14px;margin:5px -2px 2px -2px;\" href=\"/zajezdy/katalog/".$_GET["typ"]."/".$_GET["zeme"]."/".$this->nazev_web($this->radek["nazev_destinace"])."\"  title=\"".$this->radek["nazev_destinace"]."\">".$this->radek["nazev_destinace"]." </a>                            
                            </td></tr>";
                     
                 }
                 
                //u obrazkoveho vypisu zobrazim pouze ubytovani pokud existuje
                if($this->radek["foto_url"]!=""){
                        $foto = "<img width=\"28\" height=\"21\" style=\"margin:1px 3px 1px 2px;display:block;border:none\" class=\"round\" src=\"https://www.slantour.cz/".ADRESAR_MINIIKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev"]."\" style=\"margin-bottom:10px;\"   class=\"leftnav\" />";
                }else{
                        $foto = "<img width=\"28\" height=\"21\" style=\"margin:1px 3px 1px 2px;display:block;border:none\" class=\"round\" src=\"https://www.slantour.cz/img/empty_image.jpg\"/ alt=\"".$this->radek["nazev"]."\" style=\"margin-bottom:10px;\"  class=\"leftnav\" />";
                }                                
                
                    
                $adresa = "/zajezdy/zobrazit/".$this->radek["nazev_web"]."";    
                $adresa_ubyt = "/zajezdy/ubytovani/".$this->radek["nazev_ubytovani_web"].""; 
                 
              
                 if($i % 2 == 1){
                     //licha
                     $suda = "class=\"suda\"";
                 }else{
                     //suda
                      $suda = "class=\"sudaDark\"";
                 }
                
                 if($this->radek["nazev_ubytovani"]!="" and $this->radek["id_sablony_zobrazeni"]=="12"  and $this->radek["nazev_ubytovani"]!=$this->last_ubytovani){
                     //zobrazim ubytovani
                     $this->last_ubytovani = $this->radek["nazev_ubytovani"];
                     $i++;
                     $ret.= "<tr ".$suda." style=\"margin:0;padding:2px;\">
                                <td>                            
                                <a href=\"".$adresa_ubyt."\" title=\"".$this->radek["nazev_ubytovani"]."\">
                                    ".$foto."
                                </a>
                                </td>
                                <td>
                                <a href=\"".$adresa_ubyt."\" title=\"".$this->radek["nazev_ubytovani"]."\">".$this->radek["nazev_ubytovani"]." </a>                            
                                </td>
                            <tr>";                    
                 }else if($this->radek["nazev_ubytovani"]!="" and $this->radek["id_sablony_zobrazeni"]=="12" ){
                    //nedelam nic - dalsi serial od stejneho ubytovani 
                 }else if($this->radek["nazev"] != $last_nazev) {
                    $i++;
                    $last_nazev =  $this->radek["nazev"];
                    $ret.= "<tr ".$suda." style=\"margin:0;padding:2px;\">
                                <td>
                                <a href=\"".$adresa."\" title=\"".$this->radek["nazev"]."\">
                                    ".$foto."
                                </a>
                                </td><td>
                                <a href=\"".$adresa."\" title=\"".$this->radek["nazev"]."\">".$this->radek["nazev"]." </a>
                                </td>
                            <tr>";                     
                 }
                  
             }      
           }
           $ret .= "</table>";
           return $ret;
        }
        

        function show_serial($typ_zobrazeni = "obrazkove"){
            $ret="";
            $i=0;            
            while($this->get_next_radek()){
             if($typ_zobrazeni=="obrazkove"){   
                //u obrazkoveho vypisu zobrazim pouze ubytovani pokud existuje
                if($this->radek["foto_url"]!=""){
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev"]."\" style=\"margin-bottom:10px;\"   class=\"leftnav\" />";
                }else{
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/img/empty_image.jpg\"/ alt=\"".$this->radek["nazev"]."\" style=\"margin-bottom:10px;\"  class=\"leftnav\" />";
                }                                
                if($i % 2 ==0){
                        $ret.= "</tr><tr>";
                    }
                    
                $adresa = "/zajezdy/zobrazit/".$this->radek["nazev_web"]."";    
                $adresa_ubyt = "/zajezdy/ubytovani/".$this->radek["nazev_ubytovani_web"].""; 
                 
                 
                
                 if($this->radek["nazev_ubytovani"]!="" and $this->radek["id_sablony_zobrazeni"]=="12"  and $this->radek["nazev_ubytovani"]!=$this->last_ubytovani){
                     //zobrazim ubytovani
                     $this->last_ubytovani = $this->radek["nazev_ubytovani"];
                     
                    if(strlen($this->radek["nazev_ubytovani"])>=25){
                      $style_nazev = "display:block;width:146px;height:32px;background-color:#eace8e;margin-top:-32px;position:relative;z-index:10;font-weight:bold;line-height:1.2em;text-align:center;";  
                    }else{
                      $style_nazev = "display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;";    
                    }
                     $ret.= "<td>
                            <div class=\"round\" style=\"height:95px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                <a href=\"".$adresa_ubyt."\" title=\"".$this->radek["nazev_ubytovani"]."\">
                                    ".$foto."
                                </a>
                                <a href=\"".$adresa_ubyt."\" title=\"".$this->radek["nazev_ubytovani"]."\" style=\"".$style_nazev."\">".$this->radek["nazev_ubytovani"]." </a>
                            </div>
                            </td>";
                    $i++; 
                 }else if($this->radek["nazev_ubytovani"]!="" and $this->radek["id_sablony_zobrazeni"]=="12" ){
                    //nedelam nic - dalsi serial od stejneho ubytovani 
                 }else{
                    if(strlen($this->radek["nazev"])>=25){
                      $style_nazev = "display:block;width:146px;height:32px;background-color:#eace8e;margin-top:-32px;position:relative;z-index:10;font-weight:bold;line-height:1.2em;text-align:center;";  
                    }else{
                      $style_nazev = "display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;";    
                    }
                    $ret.= "<td>
                            <div class=\"round\" style=\"height:95px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                <a href=\"".$adresa."\" title=\"".$this->radek["nazev"]."\">
                                    ".$foto."
                                </a>
                                <a href=\"".$adresa."\" title=\"".$this->radek["nazev"]."\" style=\"".$style_nazev."\">".$this->radek["nazev"]." </a>
                            </div>
                            </td>";
                    $i++;   
                 }
                
                
                
                if($this->radek["nazev_ubytovani"]!="" and $this->radek["id_sablony_zobrazeni"]=="12" ){
                    $this->radek["nazev"] = $this->radek["nazev_ubytovani"].": ".$this->radek["nazev"];
                }
                  
             }else{                
                $adresa = "/zajezdy/zobrazit/".$this->radek["nazev_web"]."";
                                            
                    $ret.= "<tr><td colspan=\"2\">
                                <a href=\"".$adresa."\"  title=\"".$this->radek["nazev"]."\">".$this->radek["nazev"]." </a>                            
                            </td></tr>";
                    $i++;   
             }      
           }
           return $ret;
        }
        
        function show_ubytovani($typ_zobrazeni = "obrazkove"){
            $ret="";
            $i=0;            
            while($this->get_next_radek()){
             if($typ_zobrazeni=="obrazkove"){   
                
                $adresa = "/zajezdy/ubytovani/".$this->radek["nazev_web"]."";
                
                
                if($this->radek["foto_url"]!=""){
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev_zeme"]."\" style=\"margin-bottom:10px;\"   class=\"leftnav\" />";
                }else{
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/img/empty_image.jpg\"/ alt=\"".$this->radek["nazev_zeme"]."\" style=\"margin-bottom:10px;\"  class=\"leftnav\" />";
                }                                
                if($i % 2 ==0){
                        $ret.= "</tr><tr>";
                    }
                    if(strlen($this->radek["nazev"])>=25){
                      $style_nazev = "display:block;width:146px;height:32px;background-color:#eace8e;margin-top:-32px;position:relative;z-index:10;font-weight:bold;line-height:1.2em;text-align:center;";  
                    }else{
                      $style_nazev = "display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;";    
                    }
                    
                    
                    $ret.= "<td>
                            <div class=\"round\" style=\"height:95px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                <a href=\"".$adresa."\" title=\"".$this->radek["nazev"]."\">
                                    ".$foto."
                                </a>
                                <a href=\"".$adresa."\" style=\"".$style_nazev."\">".$this->radek["nazev"]." </a>
                            </div>
                            </td>";
                    $i++;   
             }else{                
                $adresa = "/zajezdy/ubytovani/".$this->radek["nazev_web"]."";
                                            
                    $ret.= "<tr><td colspan=\"2\">
                                <a href=\"".$adresa."\" >".$this->radek["nazev"]." </a>                            
                            </td></tr>";
                    $i++;   
             }      
           }
           return $ret;
        }
        
        function show_zeme_typ(){
            $ret="";
            $i=0;            
            while($this->get_next_radek()){
                if($_GET["typ"]){
                    //zobrazit spravnou fotku u informaci
                    $dotaz = "
                        SELECT DISTINCT `foto`.`foto_url`
					FROM `zeme`                                             
                                            join (
                                                    `informace` join
                                                     foto_informace on (`informace`.`id_informace` = `foto_informace`.`id_informace`) join
                                                      `typ_serial` on ( `typ_serial`.`id_typ` = `foto_informace`.`zakladni_pro_typ` ) join
                                                     foto on (`foto_informace`.`id_foto` = `foto`.`id_foto`)
                                                ) on (`informace`.`id_informace` = `zeme`.`id_info`)                                                 						
					WHERE `zeme`.`id_zeme` = ".$this->radek["id_zeme"]." and `typ_serial`.`nazev_typ_web` = \"".$_GET["typ"]."\"
					LIMIT 1";
                   // echo $dotaz;
                    $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                    while ($row = mysqli_fetch_array($data)) {
                        $this->radek["foto_url"] = $row["foto_url"];
                    }
                }
                if($this->radek["foto_url"]!=""){
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev_zeme"]."\"   class=\"leftnav\" style=\"margin-bottom:10px;\" />";
                }else{
                        $foto = "<img width=\"142\" height=\"85\" src=\"https://www.slantour.cz/img/empty_image.jpg\"/ alt=\"".$this->radek["nazev_zeme"]."\"  class=\"leftnav\"  style=\"margin-bottom:10px;\"/>";
                }
                if(strlen($this->radek["nazev_zeme"])>=25){
                      $style_nazev = "display:block;width:146px;height:33px;background-color:#eace8e;margin-top:-33px;position:relative;z-index:10;font-weight:bold;line-height:1.2em;text-align:center;";  
                    }else{
                      $style_nazev = "display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;";    
                    }
                if($i % 2 ==0){
                        $ret.= "</tr><tr>";
                    }
                    $ret.= "<td>
                            <div class=\"round\" style=\"height:95px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                <a href=\"/zajezdy/katalog/".$_GET["typ"]."/".$this->radek["nazev_zeme_web"]."\" title=\"".$this->radek["nazev_zeme"]."\">
                                    ".$foto."
                                </a>
                                <a href=\"/zajezdy/katalog/".$_GET["typ"]."/".$this->radek["nazev_zeme_web"]."\" style=\"".$style_nazev."\">".$this->radek["nazev_zeme"]." </a>
                            </div>
                            </td>";
                    $i++;                
            }
            return $ret;
        }
        
        function show_podtypy(){
            $ret="";
            $i=0;            
            $first = 1;
            while($this->get_next_radek()){
                if($first){
                    //prvni radek -> mame aspon jeden -> vypiseme nadpis
                    $first = 0;
                    $ret.= "<table width=\"298\">
                            <tr><td colspan=\"4\"><b>Typy zájezdů</b></td>
                            </tr><tr> ";
                }
                if($i % 2 ==0){
                        $ret.= "</tr><tr>";
                    }
                    $ret.= "<td>
                            <div class=\"round\" style=\"height:85px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                                <a href=\"/zajezdy/katalog/".$_GET["typ"]."/".$this->radek["nazev_zeme_web"]."\" title=\"".$this->radek["nazev_zeme"]."\">
                                    ".$foto."
                                </a>
                                <a href=\"/zajezdy/katalog/".$_GET["typ"]."/".$this->radek["nazev_zeme_web"]."\" style=\"display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;\">".$this->radek["nazev_zeme"]." </a>
                            </div>
                            </td>";
                    $i++;                
            }
            if(!$first){
                $ret.= "</table>";
            }
            return $ret;
        }
        
        function show_typy_pobytu(){
            $ret="";
            $i=0;            
            if($this->nazev_zeme!=""){
                $adr_zeme = "/".$this->nazev_zeme;
                if($this->nazev_destinace!=""){
                    $adr_destinace = "/".$this->nazev_destinace;
                }else{
                    $adr_destinace = "";
                }
            }else{
                $adr_zeme = "";
            }
            
            while($this->get_next_radek()){
                if($i % 2 ==0){
                        $ret.= "</tr><tr>";
                    }
              /*       $ret.= "<td>
                                <a href=\"/zajezdy/katalog/".$this->radek["nazev_typ_web"].$adr_zeme.$adr_destinace."\" title=\"".$this->radek["nazev_typ"].$this->description[$this->radek["nazev_typ_web"]]."\">
                                    <img src=\"/img/".$this->radek["nazev_typ_web"].".png\" alt=\"".$this->radek["nazev_typ"]."\"  class=\"leftnav\">
                                </a>
                            </td>";*/
                    
                    $ret.= "<td>
                                <a href=\"/zajezdy/katalog/".$this->radek["nazev_typ_web"].$adr_zeme.$adr_destinace."\" title=\"".$this->radek["nazev_typ"].$this->description[$this->radek["nazev_typ_web"]]."\" class=\"typ_serialu\">
                                    <img src=\"https://slantour.cz/foto/nahled/".$this->radek["foto_url"]."\" alt=\"".$this->radek["nazev_typ"]."\" class=\"typ_serialu\" />
                                    <p style=\"".$this->radek["style_text"]."\" class=\"typ_serialu\">".$this->radek["text_foto"]."</p>
                                </a>
                            </td>";
                    $i++;                
            }
            return $ret;
        }
        	
        function show_top_sporty(){
            $ret="";
            $i=0;  
            $zeme = array();
            $destinace = array();
            $array_info = array();
            $array_info2 = array();
            
            //pokud mame predpocitane sporty, nedelame dalsi dotaz
            $dotaz = "select count(`id`) as `pocet` from `precalculated_info` where `typ_informace` like 'top_sporty' and `datum_kalkulace`=\"".Date("Y-m-d")."\"";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
            $num_rows = mysqli_num_rows($data);
            $pocet = 0;
            while ($zaznam = mysqli_Fetch_Array($data)) {
                $pocet = $zaznam["pocet"];
            }            
            if($pocet==0){           
            
            $sporty = $this->database->query($this->create_query("dotaz_mozne_sporty"));
            while($data = mysqli_fetch_array($sporty)){
                $zeme[$data["id_zeme"]] = 1;
                $destinace[$data["id_destinace"]] = 1;
            }

            $zeme1 = $this->database->query($this->create_query("dotaz_top_sporty_zeme"));
            while($data = mysqli_fetch_array($zeme1)){
                if($zeme[ $data["id_zeme"] ] ==1 ){
                    $array_info[] = $data;
                }
            }
            $destinace1 = $this->database->query($this->create_query("dotaz_top_sporty_destinace"));
            while($data = mysqli_fetch_array($destinace1)){
                if($destinace[ $data["id_destinace"] ] ==1 ){
                    $array_info2[] = $data;
                }
            }
            $a1 = current($array_info);
            $a2 = current($array_info2);
            $last_zeme = $a1["pocet"];
            $last_destinace = $a2["pocet"];
            $last_nazev_zeme = "";
            
            $i=0;
            while(1){
                if(current($array_info)===FALSE and current($array_info2)===FALSE){
                    break;
                }
                if($i % 2 ==0){
                   $ret.= "</tr><tr>";
                }
                if($last_zeme >= $last_destinace){
                    $value = current($array_info);
                    $j=0;       
                                                
                    $url = "/img/sporty/".$value["nazev_zeme_web"].".png";
                    $handle = @fopen($url,'r');
                    if($handle !== false){
                        $img = "<img src=\"/img/sporty/".$value["nazev_zeme_web"].".png\" alt=\"".$value["nazev_zeme"]."\"  class=\"leftnav\" width=\"30\" height=\"20\" />";                       
                    } else{
                       $img = "<img src=\"/img/sporty/blank.png\" alt=\"".$value["nazev_zeme"]."\"  class=\"leftnav\" width=\"30\" height=\"20\" />";                                          
                    }
                    
                    if($value["nazev_zeme"]!=""){ 
                        $ret.= "<td  width=\"32\">
                                <a href=\"/zajezdy/katalog/".$value["nazev_zeme_web"]."\" title=\"".$value["nazev_zeme"]."\">
                                    ".$img."
                                </a>        
                            </td><td>
                                <a href=\"/zajezdy/katalog/".$value["nazev_zeme_web"]."\" title=\"".$value["nazev_zeme"]."\">
                                       ".$value["nazev_zeme"]."
                                </a>
                            </td>";
                    }
                    next($array_info);
                    $value = current($array_info); 
                    $last_zeme = $value["pocet"];                                                                                
                }else{
                    //od jedne zeme chceme maximalne 5 destinaci
                    $value = current($array_info2); 
                    
                    if($last_nazev_zeme != $value["nazev_zeme_web"]){
                        $last_nazev_zeme = $value["nazev_zeme_web"];
                        $j=0;
                    }
                    $j++;
                    if($j>5){ 
                        next($array_info2);
                        $value = current($array_info2); 
                        $last_destinace = $value["pocet"];                    
                        continue;
                    }                                        
                    
                    $url1 = "https://slantour.cz/img/sporty/".$this->nazev_web($value["nazev_destinace"]).".png";
                    $handle1 = @fopen($url1,'r');
                    
                    $url2 = "https://slantour.cz/img/sporty/".$value["nazev_zeme_web"].".png";
                    $handle2 = @fopen($url2,'r');
                    
                    if($value["nazev_zeme"]!="Fotbal" and $value["nazev_zeme"]!="Hokej" and $value["nazev_zeme"]!="Tenis"){                        
                        $nazev = $value["nazev_zeme"]." - ".$value["nazev_destinace"];
                    }else{
                        $nazev = $value["nazev_destinace"];
                    }
                    if($handle1 !== false){
                        $img = "<img src=\"/img/sporty/".$this->nazev_web($value["nazev_destinace"]).".png\" alt=\"".$value["nazev_zeme"]." - ".$value["nazev_destinace"]."\"  class=\"leftnav\" width=\"30\" height=\"20\" />";
                    } else if($handle2 !== false){
                        $img = "<img src=\"/img/sporty/".$value["nazev_zeme_web"].".png\" alt=\"".$value["nazev_zeme"]." - ".$value["nazev_destinace"]."\"  class=\"leftnav\" width=\"30\" height=\"20\" />";
                    }else{
                       $img = "<img src=\"/img/sporty/blank.png\" alt=\"".$value["nazev_zeme"]." - ".$value["nazev_destinace"]."\"  class=\"leftnav\" width=\"30\" height=\"20\" />";
                    }
                        
                    
                    if($value["nazev_destinace"]!=""){                   
                        
                        $ret.= "<td  width=\"32\">
                                <a href=\"/zajezdy/katalog/".$value["nazev_zeme_web"]."/".$this->nazev_web($value["nazev_destinace"])."\" title=\"".$value["nazev_zeme"].", ".$value["nazev_destinace"]."\">
                                    ".$img."
                                </a>        
                            </td><td>
                                <a href=\"/zajezdy/katalog/".$value["nazev_zeme_web"]."/".$this->nazev_web($value["nazev_destinace"])."\" title=\"".$value["nazev_zeme"]." - ".$value["nazev_destinace"]."\">
                                       ".$nazev."
                                </a>
                            </td>";
                    }
                    next($array_info2);
                    $value = current($array_info2); 
                    $last_destinace = $value["pocet"]; 
                    
                }

                
                if(current($array_info)===FALSE and current($array_info2)===FALSE){
                    break;
                }
                $i++;  
                if($i>9){break;}
            }    
                
                
              $dotaz = "delete from `precalculated_info` where `typ_informace` like 'top_sporty'";
              $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
              $dotaz = "insert into `precalculated_info` (`typ_informace`,`nazev`,`nazev_web`,`datum_kalkulace`) values(\"top_sporty\",\"top_sporty\",'".$ret."',\"".Date("Y-m-d")."\") ";
                
              $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);  
              return $ret;                               
            } //if pocet==0
              
            $dotaz = "select `nazev_web` from `precalculated_info` where `typ_informace` like 'top_sporty' and `datum_kalkulace`=\"".Date("Y-m-d")."\"";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz); 
            while ($row = mysqli_fetch_array($data)) {
                return $row["nazev_web"]; 
            }
                      
        }  
	
    function show_top_zeme($show_all_new=false ) {
        $ret = "";
        $i = 0;
        $pocet = 0;
        if($this->typ!=""){
            $typ_pobytu = $this->typ."/";
        }
        
        if($show_all_new!=true){
            //pokud mame predpocitane sporty, nedelame dalsi dotaz
            $dotaz = "select count(`id`) as `pocet` from `precalculated_info` where `typ_informace` like 'top_zeme' and `datum_kalkulace`=\"" . Date("Y-m-d") . "\"";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
            $num_rows = mysqli_num_rows($data);        
            while ($zaznam = mysqli_Fetch_Array($data)) {
                $pocet = $zaznam["pocet"];
            }
        }
        if ($pocet == 0) {
            $this->data = $this->database->query($this->create_query($this->typ_pozadavku))
                    or $this->chyba("Chyba při dotazu do databáze");
            while ($this->get_next_radek()) {
                if ($i % 2 == 0) {
                    $ret.= "</tr><tr>";
                }
                $ret.= "<td  width=\"24\">
                                <a href=\"/zajezdy/katalog/" .$typ_pobytu. $this->radek["nazev_zeme_web"] . "\" title=\"" . $this->radek["nazev_zeme"] . "\">
                                    <img src=\"/img/vlajky/". $this->radek["nazev_zeme_web"] . ".png\" alt=\"" . $this->radek["nazev_zeme"] . "\"  class=\"leftnav\" width=\"20\" />
                                </a>        
                            </td><td>
                                <a href=\"/zajezdy/katalog/" .$typ_pobytu. $this->radek["nazev_zeme_web"] . "\" title=\"" . $this->radek["nazev_zeme"] . "\">
                                       " . $this->radek["nazev_zeme"] . "
                                </a>
                            </td>";
                $i++;
            }

         if($show_all_new!=true){   
            $dotaz = "delete from `precalculated_info` where `typ_informace` like 'top_zeme'";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
            $dotaz = "insert into `precalculated_info` (`typ_informace`,`nazev`,`nazev_web`,`datum_kalkulace`) values(\"top_zeme\",\"top_zeme\",'" . $ret . "',\"" . Date("Y-m-d") . "\") ";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
         }   
            return $ret;
        } //if pocet==0

        $dotaz = "select `nazev_web` from `precalculated_info` where `typ_informace` like 'top_zeme' and `datum_kalkulace`=\"" . Date("Y-m-d") . "\"";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($row = mysqli_fetch_array($data)) {
            return $row["nazev_web"];
        }
    }

    function show_menu_new(){
 		$last_typ="";
		$last_zeme="";
		$last_destinace="";
		$menu="<ul>";
		$core = Core::get_instance();
		$adresa_katalog = "zajezdy/katalog";
		$adresa_zobrazit = "zobrazit";
		if( $adresa_katalog !== false ){		
			while($this->get_next_radek()){
				//mame novy typ zajezdu
				if($this->radek["id_typ"]!=$last_typ){
                                        if(!$not_first_typ){
                                            $not_first_typ = 1;
                                        }else{
                                            //ukoncit predchozi seznam
                                            $menu=$menu."
                                                </ul>
                                                <!--[if lte IE 6]></td></tr></table></a><![endif]-->
                                                    </li>
                                                </ul>";                                                                                    
                                        }
                                        $menu=$menu."
                                            <ul>
                                            <li><a href=\"/zajezdy/katalog/".$this->radek["nazev_typ_web"]."\" title=\"".$this->radek["nazev_typ"].$this->description[$this->radek["nazev_typ_web"]]."\">".$this->radek["nazev_typ"]."<!--[if IE 7]><!--></a><!--<![endif]-->
                                                <!--[if lte IE 6]><table><tr><td><![endif]-->
                                                <ul>
                                            
                                            ";
					$last_zeme="";
					$last_typ=$this->radek["id_typ"]; 
                                        if($this->radek["nazev_typ_web"]=="poznavaci-zajezdy"){
                                            $menu=$menu."<li>
								<a href=\"http://novinky.slantour.cz/category/poznavaci-zajezdy/\">
								Novinky</a></li>";                                            
                                        }
                                        if($this->radek["nazev_typ_web"]=="za-sportem"){
                                            $menu=$menu."<li>
								<a href=\"http://sport.slantour.cz/\">
								Novinky</a></li>";                                            
                                        }
                                            				
				}
				//mame novy typ zeme
				if($this->radek["id_zeme"]!="" and $this->radek["id_zeme"]!=$last_zeme){
					$last_zeme=$this->radek["id_zeme"];
					$menu=$menu."<li>
								<a href=\"/zajezdy/katalog/".$this->radek["nazev_typ_web"]."/".$this->radek["nazev_zeme_web"]."\">
								".$this->radek["nazev_zeme"]."</a></li>";
				}
				
			
			}
		}
		$menu=$menu."
                                                </ul>
                                                <!--[if lte IE 6]></td></tr></table></a><![endif]-->
                                                    </li>
                                                </ul>";
		return $menu;                                   
        }
        
	/** vypis menu katalogu*/
	function show_menu(){
		$last_typ="";
		$last_zeme="";
		$last_destinace="";
		$menu="<ul>";
		$core = Core::get_instance();
		$adresa_katalog = $core->get_adress_modul_from_typ("katalog");
		$adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
		if( $adresa_katalog !== false ){		
			while($this->get_next_radek()){
				//mame novy typ zajezdu
				if($this->radek["id_typ"]!=$last_typ){
					$last_zeme="";
					$last_typ=$this->radek["id_typ"];
					$menu=$menu."<li>
								<a href=\"".$this->get_adress(array($adresa_katalog, $this->radek["nazev_typ_web"]))."\">
								".$this->radek["nazev_typ"]."</a></li>";
				}
				//mame novy typ zeme
				if($this->radek["id_zeme"]!="" and $this->radek["id_zeme"]!=$last_zeme){
					$last_zeme=$this->radek["id_zeme"];
					$menu=$menu."<li class=\"level2\">
								<a href=\"".$this->get_adress(array($adresa_katalog, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"]))."\">
								".$this->radek["nazev_zeme"]."</a></li>";
				}
				//máme novou destinaci
				if($this->radek["id_destinace"]!="" and $this->radek["id_destinace"]!=$last_destinace){
					$last_destinace=$this->radek["id_destinace"];
					$menu=$menu."<li class=\"level_destinace\">
								<a href=\"".$this->get_adress(array($adresa_katalog, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"]))."?destinace=".$this->radek["id_destinace"]."\">
								".$this->radek["nazev_destinace"]."</a></li>";
				}
				//pole serialu je neprazdne

				if($this->radek["id_ubytovani"]!=""){
					if( $adresa_zobrazit !== false ){
                                                if($this->radek["nazev_typ_web"]=="za-sportem"){
                                                    $menu=$menu."<li class=\"level3\">
								<a href=\"".$this->get_adress(array($adresa_zobrazit, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]))."\"
								".(($_GET["lev3"]==$this->radek["nazev_web"])?(" class=\"open\" "):(" ")).">
								".$this->radek["nazev"].", ".$this->radek["nazev_ubytovani"]."</a></li>";
                                                }else{
                                                    $menu=$menu."<li class=\"level3\">
								<a href=\"".$this->get_adress(array($adresa_zobrazit, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]))."\"
								".(($_GET["lev3"]==$this->radek["nazev_web"])?(" class=\"open\" "):(" ")).">
								".$this->radek["nazev_ubytovani"].", ".$this->radek["nazev"]."</a></li>";
                                                }
						
					}
				}else if($this->radek["id_serial"]!=""){
					if( $adresa_zobrazit !== false ){
						$menu=$menu."<li class=\"level3\">
								<a href=\"".$this->get_adress(array($adresa_zobrazit, $this->radek["nazev_typ_web"], $this->radek["nazev_zeme_web"], $this->radek["nazev_web"]))."\"
								".(($_GET["lev3"]==$this->radek["nazev_web"])?(" class=\"open\" "):(" ")).">
								".$this->radek["nazev"]."</a></li>";
					}
				}
			
			}
		}
		$menu=$menu."</ul>";
		return $menu;
	}

} 




?>
