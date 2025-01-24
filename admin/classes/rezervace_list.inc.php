<?php
/** 
* rezervace_lists.inc.php - tridy pro zobrazeni seznamu rezervaci
*/


/*------------------- SEZNAM rezervaci -------------------  */

class Rezervace_list extends Generic_list{
	//vstupni data
	protected $typ_pozadavku;
	
	protected $id_typ;
	protected $id_podtyp;
	protected $id_zeme;
	protected $id_destinace;
	protected $id_objednavky;
	
	protected $nazev_serialu;
	protected $datum_od;
	protected $datum_do;
    protected $datum_pobyt_od;
    protected $datum_pobyt_do;
    protected $pouze_aktualni;

	protected $jmeno;
	protected $prijmeni;
	protected $stav;
	
	protected $id_serial;
	protected $id_zajezd;
	protected $id_klient;
	
	protected $order_by;
	protected $zacatek;	
	protected $pocet_zaznamu;

	protected $pocet_zajezdu;

        protected $ca_x_klient;
        protected $nove_prosle;
        protected $rezervace_ca_klient;
        protected $rezervace_new_old_all;
        public $typ_rezervace_nadpis;
        protected $provize;
        protected $provize_selects;
		
	public $database; //trida pro odesilani dotazu
	
//------------------- KONSTRUKTOR  -----------------	
/**konstruktor tøídy*/
	function __construct($typ_pozadavku, $id_typ, $id_podtyp, $id_zeme, $id_destinace, $nazev_serialu, $datum_od, $datum_do, $datum_pobyt_od, $datum_pobyt_do, $pouze_aktualni,  $jmeno, $prijmeni, $stav, $zacatek, $order_by, $ca_x_klient="", $velikost_provize="", $id_serial="", $id_zajezd="", $id_klient="", $pocet_zaznamu=POCET_ZAZNAMU){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	
		
	//kontrola vstupnich dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);
		
		$this->id_typ = $this->check_int($id_typ);
		$this->id_podtyp = $this->check_int($id_podtyp);
		$this->id_zeme = $this->check_int($id_zeme);
		$this->id_destinace = $this->check_int($id_destinace);
              //  print_r($_SESSION);
                $this->id_objednavky = $this->check_int($_SESSION["rezervace_id_objednavky"]);
                $this->organizace = $this->check($_SESSION["rezervace_organizace"]);
                
		$this->nazev_serialu = $this->check($nazev_serialu);
		$this->datum_od = $this->check($datum_od);
		$this->datum_do = $this->check($datum_do);
        $this->datum_pobyt_od = $this->check($datum_pobyt_od);
        $this->datum_pobyt_do = $this->check($datum_pobyt_do);
        $this->pouze_aktualni = $this->check($pouze_aktualni);

		$this->jmeno = $this->check($jmeno);
		$this->prijmeni = $this->check($prijmeni);	
			
		$this->stav = $this->check_int($stav);	
				
		$this->id_serial = $this->check_int($id_serial);
		$this->id_zajezd = $this->check_int($id_zajezd);
		$this->id_klient = $this->check_int($id_klient);
		
		$this->zacatek = $this->check_int($zacatek);			
		$this->order_by = $this->check($order_by);
		$this->pocet_zaznamu = $this->check_int($pocet_zaznamu);
                
                $this->ca_x_klient = $this->check($ca_x_klient);
                $this->provize = $this->check($velikost_provize);
                $this->rezervace_ca_klient = array(
                    "vse" => "vše",
                    "ca" => "pouze CA",
                    "klient" => "pouze klient"
                );
                if(!in_array($this->ca_x_klient,array_keys($this->rezervace_ca_klient)))$this->ca_x_klient = "vse";

                $this->provize_selects = array(
                    "vse" => "vše",
                    "s_provizi" => "s provizí",
                    "bez_provize" => "bez provize"
                );
                if(!in_array($this->provize,array_keys($this->provize_selects)))$this->provize = "vse";
                
                $this->rezervace_new_old_all = array(
                    "show_all" => "vše",
                    "show_new" => "nové",
                    "show_prosle" => "prošlé"
                );
                if(!in_array($this->typ_pozadavku,array_keys($this->rezervace_new_old_all)))$this->typ_pozadavku = "show_all";

                $this->typ_rezervace_nadpis= array(
                                            "" => "Seznam rezervací",
                                            "show_all" => "Seznam rezervací",
                                            "show_new" => "Nové požadavky na rezervaci",
                                            "show_prosle" => "Prošlé rezervace"
                                        );


		//pokud mam dostatecna prava pokracovat
		if( $this->legal() ){
			//ziskam celkovy pocet zajezdu ktere odpovidaji
				$data_pocet=$this->database->query( $this->create_query($this->typ_pozadavku,1) )
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
				$zaznam_pocet = mysqli_fetch_array($data_pocet);
				$this->pocet_zajezdu = $zaznam_pocet["pocet"];	

				//ziskani seznamu z databaze	
				$this->data=$this->database->query($this->create_query($this->typ_pozadavku))
		 			or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );
			
			//zjistuju, zda mam neco k zobrazeni
			if(mysqli_num_rows($this->data)==0){
				$this->chyba("Zadaným podmínkám nevyhovuje žádná objednávka");
			}
		
		}else{
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}	
		
	}
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavek,$only_count=0){
                if($typ_pozadavek=="select_storno") {
                        $dotaz = "  SELECT sp.*, o.celkova_cena, o.termin_od
                                    FROM `objednavka` o 
                                        JOIN `serial` s ON (o.id_serial = s.id_serial)
                                        JOIN `smluvni_podminky_nazev` spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                                        JOIN `smluvni_podminky` sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                                    WHERE o.`id_objednavka`='" . $this->get_id_objednavka() . "' AND sp.typ='storno' ORDER BY sp.prodleva DESC;";
//                        echo "$dotaz<br/>";
                        return $dotaz;
                }
		if(in_array($typ_pozadavek,array_keys($this->rezervace_new_old_all))){
			//tvorba podminek dotazu
			if($this->id_typ!=0){
				$where_typ=" `serial`.`id_typ` = ".$this->id_typ." and";
			}else{
				$where_typ=" ";
			}	
			if($this->id_podtyp!=0){
				$where_podtyp=" `serial`.`id_podtyp` = ".$this->id_podtyp." and";
			}else{
				$where_podtyp=" ";
			}
                        if($this->organizace!=""){
                            if(intval($this->organizace)>0){
                                $where_organizace=" (`org`.`id_organizace` = ".intval($this->organizace)." or `objednavka`.`id_agentury` = ".intval($this->organizace).") and ";
                            }else{
                                $where_organizace=" (`org`.`nazev` like \"%".$this->organizace."%\" or `organizace`.`nazev` like \"%".$this->organizace."%\") and "; 
                            }				
			}else{
				$where_organizace=" ";
			}
                        
			if($this->id_objednavky!=0){
				$where_id=" `objednavka`.`id_objednavka` = ".$this->id_objednavky." and";
			}else{
				$where_id=" ";
			}
			if($this->id_zeme!=0){
				$select_zeme=" `zeme_serial`.`id_zeme`,";
				$from_zeme=" join `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) ";
				$where_zeme=" `zeme_serial`.`id_zeme` = ".$this->id_zeme." and";
			}else{
				$select_zeme="";
				$from_zeme="";			
				$where_zeme=" ";
			}	
			if($this->id_destinace!=0){
				$select_destinace="`destinace_serial`.`id_destinace`,";
				$from_destinace=" join `destinace_serial` on (`destinace_serial`.`id_serial` = `serial`.`id_serial`) ";
				$where_destinace=" `destinace_serial`.`id_destinace` = ".$this->id_destinace." and";
			}else{
				$select_destinace="";
				$from_destinace="";
				$where_destinace=" ";
			}	
						
			if($this->nazev_serialu!=""){
				$where_nazev_serialu=" (`serial`.`nazev` like '%".$this->nazev_serialu."%' or `objekt`.`nazev_objektu` like '%".$this->nazev_serialu."%' )  and";
			}else{
				$where_nazev_serialu=" ";
			}	
			
			if($this->jmeno!=""){
				$where_jmeno = " (`userklient`.`jmeno` like '%".$this->jmeno."%' or `k`.`jmeno` like '%".$this->jmeno."%') and";
			}else{
				$where_jmeno = " ";
			}	
			if($this->prijmeni!=""){
				$where_prijmeni= " (`userklient`.`prijmeni` like '%".$this->prijmeni."%' or `k`.`prijmeni` like '%".$this->prijmeni."%') and";
			}else{
				$where_prijmeni= " ";
			}	
			if($this->datum_od!=""){
				$where_datum_od = " `objednavka`.`datum_rezervace` >= '".$this->change_date_cz_en( $this->datum_od )."' and";
			}else{
				$where_datum_od = " ";
			}			
			if($this->datum_do!=""){
				$where_datum_do = " `objednavka`.`datum_rezervace` <= '".($this->change_date_cz_en( $this->datum_do, 1))."' and";
			}else{
				$where_datum_do = " ";
			}
            if($this->datum_pobyt_od!=""){
                $where_datum_pobyt_od = " (`objednavka`.`termin_od` >= '".$this->change_date_cz_en( $this->datum_pobyt_od )."' or  (`objednavka`.`termin_od`='0000-00-00' and `zajezd`.`od` >= '".($this->change_date_cz_en( $this->datum_pobyt_od) )."')) and";
            }else{
                $where_datum_pobyt_od = " ";
            }
            if($this->datum_pobyt_do!=""){
                $where_datum_pobyt_do = " ((`objednavka`.`termin_do` <= '".($this->change_date_cz_en( $this->datum_pobyt_do, 1) )."' and `objednavka`.`termin_do`!='0000-00-00' ) or (`objednavka`.`termin_do`='0000-00-00' and `zajezd`.`do` <= '".($this->change_date_cz_en( $this->datum_pobyt_do) )."')) and";
            }else{
                $where_datum_pobyt_do = " ";
            }
            if($this->pouze_aktualni==1){
                $where_pouze_aktualni = " (`objednavka`.`termin_od` >= '".($this->change_date_cz_en( date('d.m.Y')) )."' or (`objednavka`.`termin_od`='0000-00-00' and `zajezd`.`od` >= '".($this->change_date_cz_en( date('d.m.Y')) )."')) and";
            }else{
                $where_pouze_aktualni = " ";
            }
            if($this->stav!=0){
				$where_stav=" `objednavka`.`stav` = ".$this->stav." and";
			}else{
				$where_stav=" ";
			}			
			if($this->id_serial!=0){
				$where_serial=" `serial`.`id_serial` = ".$this->id_serial." and";
			}else{
				$where_serial=" ";
			}		
			if($this->id_zajezd!=0){
				$where_zajezd=" `zajezd`.`id_zajezd` = ".$this->id_zajezd." and";
			}else{
				$where_zajezd=" ";
			}		
			if($this->id_klient!=0){
				$where_klient=" `userklient`.`id_klient` = ".$this->id_klient." and";
			}else{
				$where_klient=" ";
			}											
			if($this->zacatek!=0){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
				$limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." "; 
			}else{
				$limit=" limit 0,".$this->pocet_zaznamu." ";
			}					
			$order=$this->order_by($this->order_by);
			
			if($typ_pozadavek=="show_prosle"){
				$where_prosle=" `objednavka`.`rezervace_do` < '".Date("Y-m-d")."' and `objednavka`.`stav` = 3 and ";
			}else{
				$where_prosle=" ";
			}

			if($typ_pozadavek=="show_new"){
				//budeme pristupovat k metodam tridy aktualniho zamestnance - musi byt nainicializovana!
				GLOBAL $zamestnanec;		
				$where_new=" `objednavka`.`datum_rezervace` >= '".$_SESSION["last_logon"]."' and";
			}else{
				$where_new=" ";
			}		

                        if($this->ca_x_klient=="klient"){
				$where_ca_klient=" `objednavka`.`id_agentury` IS NULL and ";
			}elseif($this->ca_x_klient=="ca"){
				$where_ca_klient=" `objednavka`.`id_agentury` IS NOT NULL and ";
			}else{
				$where_ca_klient=" ";
			}

                        if($this->provize=="bez_provize"){
				$where_provize=" (`objednavka`.`suma_provize` IS NULL or `objednavka`.`suma_provize` = 0) and ";
			}elseif($this->provize=="s_provizi"){
				$where_provize=" `objednavka`.`suma_provize` IS NOT NULL and `objednavka`.`suma_provize` != 0 and ";
			}else{
				$where_provize=" ";
			}

			//pokud chceme pouze spoèítat vsechny odpovídající záznamy
			if($only_count==1){
				$select="select count(distinct `objednavka`.`id_objednavka`) as pocet";
				$limit="";
                                $group_by="";
			}else{
				$select="select ".$select_zeme.$select_destinace."
								`objednavka`.`id_objednavka`,`objednavka`.`security_code`,`objednavka`.`datum_rezervace`,`objednavka`.`rezervace_do`,`objednavka`.`stav`,`objednavka`.`pocet_osob`,`objednavka`.`termin_od`,`objednavka`.`termin_do`,
                                                                `objednavka`.`celkova_cena`,
                                                                (sum(`objednavka_platba`.`castka`)*count(distinct objednavka_platba.id_platba)/count(objednavka_platba.id_platba)) as `splaceno`, 
                                                                `org`.`nazev` as `nazev_organizace`,`org`.`id_organizace`,`org`.`ico`,
								`userklient`.`id_klient`, `userklient`.`jmeno`, `userklient`.`prijmeni`, `organizace`.`nazev` as `agentura_jmeno`, `organizace_email`.`poznamka` as `agentura_prijmeni`,
								`serial`.`id_serial`, `serial`.`nazev`,`serial`.`id_sablony_zobrazeni`,
                                                                `objekt`.`id_objektu`  as `id_ubytovani`,`objekt`.`nazev_objektu` as `nazev_ubytovani`,
                                                                count(`faktury`.`id_faktury`) as `pocet_faktur`,
								`zajezd`.`id_zajezd`, `zajezd`.`od`,`zajezd`.`do`,`zajezd`.`nazev_zajezdu`";
                                $group_by = "group by `objednavka`.`id_objednavka`";
			}

			//vysledny dotaz zkonstruujeme podle typu pozadavku
			$dotaz=	 $select."
						 from `objednavka` 
						 		left join  `user_klient` as `userklient` on (`objednavka`.`id_klient` = `userklient`.`id_klient`)
                                                                left join  `organizace` as `org` on (`objednavka`.`id_organizace` = `org`.`id_organizace`)
                                                                left join  (`objednavka_osoby` as `ok` 
                                                                        join  `user_klient` as `k` on (`ok`.`id_klient` = `k`.`id_klient`)) on (`objednavka`.`id_objednavka` = `ok`.`id_objednavka`)
						 		left join `organizace`  on (`objednavka`.`id_agentury` = `organizace`.`id_organizace`)
                                                                left join `organizace_email` on (`organizace`.`id_organizace` = `organizace_email`.`id_organizace` and `organizace_email`.`typ_kontaktu` = 0)                                      
								join  `serial` on (`objednavka`.`id_serial` = `serial`.`id_serial`)
								join  `zajezd` on (`objednavka`.`id_zajezd` = `zajezd`.`id_zajezd`)
                                                                left join  `objednavka_platba` on (`objednavka`.`id_objednavka` = `objednavka_platba`.`id_objednavka` )                                                     
                                                                left join (`objekt_serial` join
                                                                    `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                                                                left join `faktury` on (`objednavka`.`id_objednavka` = `faktury`.`id_objednavka`)
                                                           
								".$from_zeme."
								".$from_destinace."
								".$from_new."
						where ".$where_id.$where_typ.$where_podtyp.$where_zeme.$where_destinace.$where_organizace.$where_nazev_serialu.$where_jmeno.$where_prijmeni.
							$where_datum_do.$where_datum_od.$where_datum_pobyt_od.$where_datum_pobyt_do.$where_pouze_aktualni.$where_stav.$where_serial.$where_zajezd.$where_klient.$where_prosle.$where_new.$where_ca_klient.$where_provize." 1
						".$group_by."
                                                order by ".$order." ".$limit;
                        
                        //u joinu plateb bylo:  and `objednavka_platba`.`splaceno` is not null and `objednavka_platba`.`splaceno`!=\"0000-00-00\"
				if(!$only_count){
                                //    echo $dotaz;
                                }
                      //         echo $dotaz;
				return $dotaz;
                                
                                
                                
		}		
	}	

/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
	function order_by($vstup){
		switch ($vstup) {
			case "id_up":
				 return "`objednavka`.`id_objednavka`";
   			 break;
			case "id_down":
				 return "`objednavka`.`id_objednavka` desc";
   			 break;				 
			case "serial_up":
				 return "`serial`.`nazev`, `zajezd`.`od`";
   			 break;			
			case "serial_down":
				 return "`serial`.`nazev` desc, `zajezd`.`od` desc";
   			 break;	
			case "jmeno_up":
				 return "`userklient`.`prijmeni`,`userklient`.`jmeno`";
   			 break;
			case "jmeno_down":
				 return "`userklient`.`prijmeni` desc,`userklient`.`jmeno` desc";
   			 break;		

			case "datum_rezervace_up":
				 return "`objednavka`.`datum_rezervace`";
   			 break;			
			case "datum_rezervace_down":
				 return "`objednavka`.`datum_rezervace` desc";
   			 break;					 
			case "rezervace_do_up":
				 return "`objednavka`.`rezervace_do`";
   			 break;			
			case "rezervace_do_down":
				 return "`objednavka`.`rezervace_do` desc";
   			 break;	
                        case "zbyva_zaplatit_up":
				 return "`objednavka`.`zbyva_zaplatit`";
   			 break;			
			case "zbyva_zaplatit_down":
				 return "`objednavka`.`zbyva_zaplatit` desc";
   			 break;	
			case "stav_up":
				 return "`objednavka`.`stav`";
   			 break;			
			case "stav_down":
				 return "`objednavka`.`stav` desc";
   			 break;	
			case "pocet_osob_up":
				 return "`objednavka`.`pocet_osob`";
   			 break;			
			case "pocet_osob_down":
				 return "`objednavka`.`pocet_osob` desc";
   			 break;
			case "prodejce_up":
				 return "`organizace`.`nazev` ";
   			 break;
			case "prodejce_down":
				 return "`organizace`.`nazev` desc";
   			 break;
		}
		//pokud zadan nespravny vstup, vratime zajezd.od
		return "`objednavka`.`datum_rezervace` desc";
	}

	/**zobrazi formular pro filtorvani vypisu serialu*/
	function show_filtr(){
	
		//tvorba select_zeme_destinace
		$zeme="<select name=\"zeme-destinace\"><option value=\"0:0\">--libovolná--</option>";
		//do promenne typy_serialu vytvorim instanci tridy seznam zemi
		$zeme_informace = new Zeme_list($this->id_zamestnance,"",$this->id_zeme,$this->id_destinace);
		//vypisu seznam zemi
		$zeme = $zeme.$zeme_informace->show_list("select_zeme_destinace");	
		$zeme=$zeme."</select>\n";	
		
		//tvorba select typ-podtyp
		$typ="<select name=\"typ-podtyp\">\n<option value=\"0:0\">--libovolný--</option>\n";
		//do promenne typy_serialu vytvorim instanci tridy seznam typu serialu a nasledne vypisu seznam typu					
		$typy_serialu = new Typ_list($this->id_zamestnance,"",$this->id_typ,$this->id_podtyp);
		//vypisu seznam typu a podtypu
		$typ = $typ.$typy_serialu->show_list("select_typ_podtyp");											
		$typ=$typ."</select>\n\n";	

		//input id_objednavky
                $input_id_objednavky="<input name=\"id_objednavky\" type=\"text\" size=\"8\" value=\"".$this->id_objednavky."\" />";
                //tvroba input datum rezervace od
		$input_datum_od="<input name=\"rezervace_datum_od\" class=\"calendar-ymd\" type=\"text\" value=\"".$this->datum_od."\" size=\"10\"/>";
		//tvroba input datum rezervace do
		$input_datum_do="<input name=\"rezervace_datum_do\" class=\"calendar-ymd\" type=\"text\" value=\"".$this->datum_do."\" size=\"10\"/>";
        //tvroba input datum pobytu od
        $input_datum_pobyt_od="<input name=\"rezervace_datum_pobyt_od\" class=\"calendar-ymd\" type=\"text\" value=\"".$this->datum_pobyt_od."\" size=\"10\"/>";
        //tvroba input datum pobytu do
        $input_datum_pobyt_do="<input name=\"rezervace_datum_pobyt_do\" class=\"calendar-ymd\" type=\"text\" value=\"".$this->datum_pobyt_do."\" size=\"10\"/>";
        $input_pouze_aktualni="<input type='checkbox' name='pouze_aktualni' value='1'".($this->pouze_aktualni == 1 ? "checked='checked'" : "")." /> pouze aktuální";

                //radio rezervace nove, prosle, vse
                $input_nove_pros_vse = "";
                foreach($this->rezervace_new_old_all as $key => $value){
                    $input_nove_pros_vse.="<input type=\"radio\" name=\"rezervace_nove_prosle\" value=\"$key\"";
                    if($key == $this->typ_pozadavku){
                        $input_nove_pros_vse.=" checked=\"checked\"";
                    }
                    $input_nove_pros_vse.=" />$value ";
                }


                //radio vše, klient, ca
                $provize_form = "";
                foreach($this->provize_selects as $key => $value){
                    $provize_form.="<input type=\"radio\" name=\"provize_filtr\" value=\"$key\"";
                    if($key == $this->provize){
                        $provize_form.=" checked=\"checked\"";
                    }
                    $provize_form.=" />$value ";
                }
                //radio s provizí, bez provize, vše
                $input_ca_x_klient = "";
                foreach($this->rezervace_ca_klient as $key => $value){
                    $input_ca_x_klient.="<input type=\"radio\" name=\"rezervace_ca_klient\" value=\"$key\"";
                    if($key == $this->ca_x_klient){
                        $input_ca_x_klient.=" checked=\"checked\"";
                    }
                    $input_ca_x_klient.=" />$value ";
                }

		//tvroba input nazev serialu
		$input_nazev_serialu="<input name=\"rezervace_nazev_serialu\" type=\"text\" value=\"".$this->nazev_serialu."\" />";
		//tvroba input jmeno objednavajiciho
		$input_jmeno="<input name=\"rezervace_jmeno\" type=\"text\" size=\"8\" value=\"".$this->jmeno."\" />";
		//tvroba input prijmeni
		$input_prijmeni="<input name=\"rezervace_prijmeni\" type=\"text\"  size=\"8\" value=\"".$this->prijmeni."\" />";
                $input_organizace="<input name=\"rezervace_organizace\" type=\"text\" value=\"".$this->organizace."\" />";
		//tvorba select stav
		$i=0;
		$stav="<select name=\"rezervace_stav\">\n		
					<option	value=\"\"	>--libovolný--</option>		";
			while(Rezervace_library::get_stav($i)!=""){
				if($this->stav==($i+1)){
					$stav=$stav."<option value=\"".($i+1)."\" selected=\"selected\">".Rezervace_library::get_stav($i)."</option>\n";
				}else{
					$stav=$stav."<option value=\"".($i+1)."\">".Rezervace_library::get_stav($i)."</option>\n";
				}
				$i++;
			}
			$stav=$stav."</select></div></div>\n\n";	
						
		//tlacitko pro odeslani
		$submit= "<input type=\"submit\" value=\"Zmìnit filtrování\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";	
				
		//vysledny formular			
		$vystup="
			<form method=\"post\" action=\"rezervace.php?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=post_fields\">
			<table class=\"filtr\">
				<tr>
                    <td>ID objednávky (VS): $input_id_objednavky</td>
					<td>Typ seriálu: $typ</td>
					<td>Zemì: $zeme</td>
					<td>Název seriálu: $input_nazev_serialu Stav objednávky: $stav</td>
				</tr>
				<tr>
					<td>Pøíjmení: $input_prijmeni, Jméno: $input_jmeno</td>
					<td>Objednávající organizace: $input_organizace</td> 
					<td>Objednáno od: $input_datum_od do: $input_datum_do </td>
					<td> Pobyt od: $input_datum_pobyt_od do: $input_datum_pobyt_do</td>
				</tr>
                <tr>
                        <td cols=\"2\">Typ rezervace: ".$input_nove_pros_vse."</td><td cols=\"2\">Objednatel: ".$input_ca_x_klient."</td><td cols=\"2\">Provize: ".$provize_form."</td><td>$input_pouze_aktualni</td>
                </tr>
				<tr>
					<td colspan='5'>".$submit."</td>
				</tr>
			</table>
			</form>
		";
		return $vystup;		
	}		
	
	/**zobrazi hlavicku k seznamu seriálù*/
	function show_list_header(){
	  $core = Core::get_instance();
	  if($adresa_objednavka = $core->get_adress_modul_from_typ("objednavky") ){		
		if( !$this->get_error_message()){
			$vystup="
				<table class=\"list\">
				    <colgroup><col width='75px'><col ><col width='140px'><col width='120px'><col width='140px'><col width='70px'><col width='55px'><col width='130px'><col ></colgroup>
					<tr>
						<th>
             <span id=\"checkboxSelectAll\" style=\"cursor: pointer;display:inline-block;width:16px;text-align:center;background-color:green;font-weight:bold;color:black;\">&check;</span> 
             <span id=\"checkboxSelectNone\" style=\"cursor: pointer;display:inline-block;width:16px;text-align:center;background-color:red;font-weight:bold;color:black;\">&#128473</span>
            Id
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=id_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=id_down\"></a>
							</div>
						</th>		
						<th>Zájezd
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=serial_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=serial_down\"></a>
							</div>
						</th>									
						<th>Objednávající
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=jmeno_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=jmeno_down\"></a>
							</div>
						</th>		
						<th>Datum rezervace
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=datum_rezervace_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=datum_rezervace_down\"></a>
							</div>
						</th>		
						<th>Cena / Uhrazeno
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=zbyva_zaplatit_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=zbyva_zaplatit_down\"></a>
							</div>
						</th>							
						<th>Stav
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=stav_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=stav_down\"></a>
							</div>
						</th>		
						<th>Osob
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=pocet_osob_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=pocet_osob_down\"></a>
							</div>
						</th>
						<th>Prodejce
						<div class='sort'>
							<a class='sort-up' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=prodejce_up\"></a>
							<a class='sort-down' href=\"".$adresa_objednavka."?typ=rezervace_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;rezervace_order_by=prodejce_down\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
			return $vystup;
		}else{
			return "";
		}
	  }
	}		
	/**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
	function show_list_item($typ_zobrazeni){
                $this->radek["zbyva_zaplatit"] = intval($this->radek["celkova_cena"]) - intval($this->radek["splaceno"]);
		if( !$this->get_error_message() ){	
			if($typ_zobrazeni=="tabulka"){
				if($this->suda==1){
					$vypis="<tr class=\"suda\">";
					}else{
					$vypis="<tr class=\"licha\">";
				}
				$core = Core::get_instance();
				if($adresa_objednavka = $core->get_adress_modul_from_typ("objednavky") ){	
					//text pro typ informaci
                                        if($this->radek["termin_od"]!="" and $this->radek["termin_od"]!="0000-00-00" and $this->radek["termin_do"]!="" and $this->radek["termin_do"]!="0000-00-00"){
                                            $termin_od = $this->radek["termin_od"];
                                            $termin_do = $this->radek["termin_do"];
                                        }else{
                                            $termin_od = $this->get_zajezd_od() ;
                                            $termin_do = $this->get_zajezd_do() ;
                                        }
                                        if($this->radek["zbyva_zaplatit"] <= 0){
                                            $styl_uhrazeno = "stav-prodano";
                                        }else if($this->radek["zbyva_zaplatit"] < $this->radek["celkova_cena"]){
                                            $styl_uhrazeno = "stav-zal";
                                        }else{
                                            $styl_uhrazeno = "stav-storno";
                                        }
                                        if($this->radek["id_organizace"]>0){
                                            $objednavajici =  "<a target='_blank' href='organizace.php?id_organizace=" . $this->radek["id_organizace"] . "&typ=organizace&pozadavek=edit'>".$this->radek["nazev_organizace"]." (".$this->radek["ico"].")</a>";                                          
                                        }else{
                                            $objednavajici =  "<a target='_blank' href='klienti.php?id_klient=" . $this->get_id_klient() . "&typ=klient&pozadavek=edit'>".$this->get_prijmeni()." ".$this->get_jmeno()."</a>";
                                        }
                                        
                                        if($this->radek["pocet_faktur"]>0){
                                            $faktury = "|   Faktury: <a title=\"Vytvoøit novou fakturu\" href='faktury.php?id_objednavka=".$this->get_id_objednavka()."&typ=faktury&pozadavek=new'>TS Nová</a> /
                                                <a title=\"Zobrazit existující faktury\" href='faktury.php?id_objednavka=".$this->get_id_objednavka()."&typ=faktury_list'>existující (".$this->radek["pocet_faktur"].") </a>";
                                        }else{
                                            $faktury = "|   <a href='faktury.php?id_objednavka=".$this->get_id_objednavka()."&typ=faktury&pozadavek=new'>TS Nová Faktura</a>";
                                        }
					$vypis = $vypis."
							<td class=\"id ".Rezervace_library::get_stav_styl( ($this->get_stav()-1) )."\"><input class=\"toDeleteCheckbox\" value='" . $this->get_id_objednavka() . "' type='checkbox' /> <a href='objednavky.php?idObjednavka=".$this->get_id_objednavka()."'>".$this->get_id_objednavka()."</a></td>
							<td class=\"nazev\">
                                                            <a target='_blank' href='serial.php?id_serial=" . $this->get_id_serial() . "&id_zajezd=" . $this->get_id_zajezd() . "&typ=zajezd&pozadavek=edit'>".$this->get_nazev_serial()."</a>, ".$this->get_nazev_zajezdu()."<br/> "
                                                            .$this->change_date_en_cz( $termin_od )." - ".$this->change_date_en_cz( $termin_do )."
                                                        </td>
							<td class=\"jmeno\">
                                                            $objednavajici
                                                        </td>
							<td class=\"datum\">".$this->change_date_en_cz( $this->get_datum_rezervace() )."</td>		
							<td class=\"datum\">".$this->radek["celkova_cena"]." Kè / <span class='cena-stav $styl_uhrazeno' style=\"font-weight:bold;\">".intval($this->radek["splaceno"])." Kè</span></td>
							<td class=\"stav ".Rezervace_library::get_stav_styl( ($this->get_stav()-1) )."\">".Rezervace_library::get_stav( ($this->get_stav()-1) )."</td>				
							<td class=\"pocet_osob\">".$this->get_pocet_osob()."</td>
							<td class=\"pocet_osob\">".$this->get_prodejce_jmeno()." ".$this->get_prodejce_prijmeni()."</td>

							
							<td class=\"menu\">
                                <a href='objednavky.php?idObjednavka=".$this->get_id_objednavka()."'>EDITOVAT</a>
                                | <a href=\"ts_objednavka.php?id_objednavka=".$this->get_id_objednavka()."&amp;security_code=".$this->get_security_code()."&amp;type=cestovni_smlouva\" target=\"_blank\">TS Cestovní Smlouva</a>    
                                | <a href=\"ts_objednavka.php?id_objednavka=".$this->get_id_objednavka()."&amp;security_code=".$this->get_security_code()."&amp;type=potvrzeni\" target=\"_blank\">TS Potvrzení Obj</a>
                                | <a href=\"ts_objednavka.php?id_objednavka=".$this->get_id_objednavka()."&amp;security_code=".$this->get_security_code()."&amp;type=potvrzeni_prodejce\" target=\"_blank\">TS Potvrzení Obj Prodejci</a>
                                | <a href=\"vouchery_objednavka.php?page=edit-voucher&id_objednavka=".$this->get_id_objednavka()."&amp;security_code=".$this->get_security_code()."\" target=\"_blank\">TS Voucher</a>
                                | <a href=\"platebni_doklad.php?page=edit&id_objednavka=".$this->get_id_objednavka()."\" target=\"_blank\">TS Platební doklad</a>
                                <span id='serial_" . $this->get_id_objednavka() . "' class='inline no-display'>
                                    ".$faktury."
                                    |   <a href=\"ts_objednavka.php?id_objednavka=".$this->get_id_objednavka()."&amp;security_code=".$this->get_security_code()."&amp;type=storno_klient\" target=\"_blank\">TS Storno klienta</a>
                                    |   <a href=\"ts_objednavka.php?id_objednavka=".$this->get_id_objednavka()."&amp;security_code=".$this->get_security_code()."&amp;type=storno_ck\" target=\"_blank\">TS Storno CK</a>
                                    |   <a href='rezervace.php?id_objednavka=".$this->get_id_objednavka()."&typ=rezervace&pozadavek=show&sub_pozadavek=show_edit' class='anchor-delete'>storno</a>
                                    |   <a href=\"".$adresa_objednavka."?id_objednavka=".$this->get_id_objednavka()."&amp;typ=rezervace&amp;pozadavek=delete\" class='anchor-delete' onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>
								</span>
								| <a href='#' id='serial_" . $this->get_id_objednavka() . "_showhide' onclick=\"return showDetailActions('serial_" . $this->get_id_objednavka() . "');\">další >></a>
							";				
					return $vypis;
				}
			}
		}else{
			return "";
		}
	}
	
	/**zobrazi odkazy na dalsi stranky vypisu*/
	function show_strankovani(){
		if( $this->pocet_zajezdu != 0 and $this->pocet_zaznamu != 0){
			//prvni cislo stranky ktere zobrazime
			$act_str=$this->zacatek-(10*$this->pocet_zaznamu);
			if($act_str<0){
				$act_str=0;
			}
		
			//zjistim vsechny dalsi promenne, odstranim z nich ale str
			$promenne= ereg_replace("str=[0-9]*&?","",$_SERVER["QUERY_STRING"]);
                        $promenne = str_replace("typ=login", "", $promenne);
                        
			//odkaz na prvni stranku
			$vypis = "<div class=\"strankovani\"><a href=\"?str=0&amp;".$promenne."\" title=\"první stránka\">&lt;&lt;</a> &nbsp;"; 
		
			//odkaz na dalsi stranky z rozsahu
			while(($act_str < $this->pocet_zajezdu) and ($act_str <= $this->zacatek+(10*$this->pocet_zaznamu))){
				if($this->zacatek!=$act_str){
					$vypis = $vypis."<a href=\"?str=".$act_str."&amp;".$promenne."\" title=\"strana ".(1+($act_str/$this->pocet_zaznamu))."\">".(1+($act_str/$this->pocet_zaznamu))."</a> ";					
				}else{
					$vypis = $vypis.(1+($act_str/$this->pocet_zaznamu))." ";
				}
				$act_str=$act_str+$this->pocet_zaznamu;
			}	
		
			//odkaz na posledni stranku
			$posl_str=$this->pocet_zaznamu*floor(($this->pocet_zajezdu-1)/$this->pocet_zaznamu);
				$vypis = $vypis." &nbsp; <a href=\"?str=".$posl_str."&amp;".$promenne."\" title=\"poslední stránka\">&gt;&gt;</a></div>";	
		
			return $vypis;
		}
	}		
	
	
	/**zjistim, zda mam opravneni k pozadovane akci*/
	function legal(){
		$zamestnanec = User_zamestnanec::get_instance();
		$core = Core::get_instance();
		$id_modul = $core->get_id_modul();
				
		return $zamestnanec->get_bool_prava($id_modul,"read");
	}
	
	/*metody pro pristup k parametrum*/
	function get_id_klient() { return $this->radek["id_klient"];}
	function get_id_objednavka() { return $this->radek["id_objednavka"];}
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_zajezd() { return $this->radek["id_zajezd"];}
	function get_security_code() { return $this->radek["security_code"];}
        function get_nazev_zajezdu() { return $this->radek["nazev_zajezdu"];}
        
	function get_nazev_serial() { 
            if($this->radek["id_sablony_zobrazeni"]==12){
                return $this->radek["nazev_ubytovani"]." ".$this->radek["nazev"];
            }else{
                return $this->radek["nazev"];
            }                        
        }
	function get_zajezd_od() { return $this->radek["od"];}	
	function get_zajezd_do() { return $this->radek["do"];}	
		
	function get_jmeno() { return $this->radek["jmeno"];}
	function get_prijmeni() { return $this->radek["prijmeni"];}
	function get_datum_rezervace() { return $this->radek["datum_rezervace"];}
	function get_rezervace_do() { return $this->radek["rezervace_do"];}
	function get_stav() { return $this->radek["stav"];}
	function get_pocet_osob() { return $this->radek["pocet_osob"];}
	function get_nove_prosle() { return $this->typ_pozadavku;}
	function get_prodejce_jmeno() { return $this->radek["agentura_jmeno"];}
	function get_prodejce_prijmeni() { return $this->radek["agentura_prijmeni"];}
    public function getZacatek() {
        return $this->zacatek;
    }
    public function getPocetZajezdu() {
        return $this->pocet_zajezdu;
    }
    public function getPocetZaznamu() {
        return $this->pocet_zaznamu;
    }

}
?>
