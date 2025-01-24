<?php
/** 
* serial_cena.inc.php - trida pro zobrazeni seznamu cen serialu v administracni casti
*											- a jejich create, update, delete
*/

/*------------------- SEZNAM cen seriálu -------------------  */
/*rozsireni tridy Serial o seznam fotografii*/
class Cena_serial extends Generic_list{
    const MENA_BEZ_PREPOCTU = "42";
    
	protected $typ_pozadavku;
	protected $pocet;	
	protected $id_zamestnance;
	private $goglobal_pricelist;
	protected $id_ceny;
	protected $id_serial;
	protected $nazev_ceny;
	protected $kratky_nazev;
	protected $zkraceny_vypis;
        protected $query_insert_ok;
	protected $typ_ceny;
	protected $zakladni_cena;
	protected $kapacita_bez_omezeni;
	protected $use_pocet_noci;
	
	//znaci ze smim pokracovat s add_to query
	protected $legal_operation;
	
	//prubezne konstruovany dotaz do databaze
	protected $query;	
	protected $pocet_zaznamu;	//pocet platnych zaznamu do databaze
	protected $cislo_ceny; //cislo zpracovavane ceny
        protected $serial_typ_provize;
	protected $serial_vyse_provize;
        
        protected $id_ridici_objekt;
        protected $nazev_objektu;
        
	protected $typ_provize;
	protected $vyse_provize;	
        protected $ok_cena;
	public $database; //trida pro odesilani dotazu
	
	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy*/
	function __construct($typ_pozadavku,$id_zamestnance,$id_serial,$id_ceny="",$pocet="",$typ_terminu=""){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();	

		$this->legal_operation=0;
		$this->pocet_zaznamu=0;
		$this->cislo_ceny=0;
		$this->query = array();

	//kontrola dat
		$this->typ_pozadavku = $this->check($typ_pozadavku);	
		$this->id_zamestnance = $this->check_int($id_zamestnance);		
		$this->id_serial = $this->check_int($id_serial);
		$this->id_ceny = $this->check_int($id_ceny);
		$this->pocet = $this->check_int($pocet);
                $this->typ_terminu = $this->check($typ_terminu);
		
                $this->id_ridici_objekt="";
                $this->nazev_objektu = "";
                $sql = "select `id_ridici_objekt`, `nazev_objektu`  from `serial` join `objekt` on (`serial`.`id_ridici_objekt` = `objekt`.`id_objektu`) where `id_serial`=".$this->id_serial." ";
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                while ($row = mysqli_fetch_array($data)) {
                    $this->nazev_objektu = $row["nazev_objektu"];
                    $this->id_ridici_objekt = $row["id_ridici_objekt"];
                }
                
		//pokud mam dostatecna prava pokracovat
		if( $this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku) ){
                      
                        $sql = "select `typ_provize`, `vyse_provize` from `serial` where `id_serial`=".$this->id_serial." limit 1";

                        $result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                        $row = mysqli_fetch_array($result);
                        $this->serial_typ_provize = $row["typ_provize"];

                        $sql = "select max(`cena`) as `cena`, `id_objekt_kategorie` 
                            from `objekt_kategorie_termin` 
                                join `objekt_serial` on (`objekt_kategorie_termin`.`id_objektu`=`objekt_serial`.`id_objektu`) 
                            where `objekt_serial`.`id_serial`=".$this->id_serial." 
                            group by `id_objekt_kategorie`     ";
                        $query = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                        while ($row1 = mysqli_fetch_array($query)) {
                            $this->ok_cena[$row1["id_objekt_kategorie"]] = $row1["cena"];
                        }

                            //na zaklade typu pozadavku vytvorim dotaz
                            $this->legal_operation=1;
                            if($this->serial_typ_provize == 3){
                                    //provize dle sluzeb
                                    $provize_header = ",`typ_provize`, `vyse_provize` ";
                                }
                            $this->query_insert_ok = "";
                            $this->query_delete_ok = array();

                            if($this->typ_pozadavku=="create"){                            
                                    $this->query_insert[0]="INSERT INTO `cena` (`id_serial`,`nazev_ceny`,`kratky_nazev`,`odjezdove_misto`,`kod_letiste`,`nazev_ceny_en`,`kratky_nazev_en`,`zkraceny_vypis`,`poradi_ceny`,`typ_ceny`,`zakladni_cena`,`kapacita_bez_omezeni`,`id_kalkulacni_vzorec`,`use_pocet_noci`".$provize_header.") VALUES ";

                            }else if($this->typ_pozadavku=="update"){
                                    $this->query_insert[0]="INSERT INTO `cena` (`id_serial`,`nazev_ceny`,`kratky_nazev`,`odjezdove_misto`,`kod_letiste`,`nazev_ceny_en`,`kratky_nazev_en`,`zkraceny_vypis`,`poradi_ceny`,`typ_ceny`,`zakladni_cena`,`kapacita_bez_omezeni`,`use_pocet_noci`".$provize_header.") VALUES ";
                                    $this->query_update[0]="UPDATE `cena` SET ";		
                                    $this->query_delete[0]="DELETE FROM `cena` WHERE ";
                                    $this->ceny_update = array();		
                                    //najdu vsechny ceny zajezdu ktere jiz jsou vytvoreny
                                    $data_ceny=$this->database->query($this->create_query("get_ceny"));
                                    while($ceny=mysqli_fetch_array($data_ceny)){

                                            $this->ceny_update_id[]=$ceny["id_cena"];
                                            $this->ceny_update[]=$ceny;

                                    }	
                            }else if($this->typ_pozadavku=="delete"){				
                                            $delete_cena=$this->database->query($this->create_query("delete"))
                                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni));
                                            //vygenerování potvrzovací hlášky
                                            if( !$this->get_error_message() ){
                                                    $this->confirm("Požadovaná akce probìhla úspìšnì");
                                            }								
                            }else if($this->typ_pozadavku=="show"){
                                            $this->data=$this->database->query($this->create_query("show"))
                                                    or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	

                            }else if($this->typ_pozadavku=="edit"){
                                    $this->data=$this->database->query($this->create_query("edit"))
                                            or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	

                                    $this->pocet=mysqli_num_rows($this->data);	

                            }else if($this->typ_pozadavku=="kalkulacni_vzorce_edit"){
                                    $this->data=$this->database->query($this->create_query("kalkulacni_vzorce_edit"))
                                            or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	

                                    $this->pocet=mysqli_num_rows($this->data);	
                                    
                            }else if($this->typ_pozadavku=="kalkulacni_vzorce_vygenerovat_terminy"){
                                    $this->data=$this->database->query($this->create_query("kalkulacni_vzorce_vygenerovat_terminy"))
                                            or $this->chyba("Chyba pøi dotazu do databáze: ".mysqli_error($GLOBALS["core"]->database->db_spojeni) );	

                                    $this->pocet=mysqli_num_rows($this->data);	
                                    
                            }else if($this->typ_pozadavku=="kalkulacni_vzorce_update"){
                                    $this->kalkulacni_vzorce_update();		
                            }else if($this->typ_pozadavku=="kalkulacni_vzorce_deleteCM"){
                                    $this->kalkulacni_vzorce_smazat_terminy();		
                                            
                            }else if($this->typ_pozadavku=="ajax_get_goglobal_ceny"){
                                    $this->ajax_get_goglobal_ceny();			
                            }else if($this->typ_pozadavku=="ajax_get_letuska_ceny"){
                                    $this->ajax_get_letuska_ceny();			
                            }				
                    	
		}else{
			$this->chyba($this->typ_pozadavku.$this->id_zamestnance.$this->id_serial);
			$this->chyba("Nemáte dostateèné oprávnìní k požadované akci");	
		}		
	}	
//------------------- METODY TRIDY -----------------	

/**po jednotlivych radcich prijima data a vytvari z nich casti dotazu*/
	function add_to_query($id_ceny,$id_objekt_kategorie,$nazev_ceny,$kratky_nazev,$odjezdove_misto,$kod_letiste,$zkraceny_vypis,$poradi_ceny,$typ_ceny,$zakladni_cena,$kapacita_bez_omezeni,$use_pocet_noci,$nazev_ceny_en,$kratky_nazev_en, $typ_provize=0, $vyse_provize=0,$nezobrazovat=0,$id_kv=0){
			//kontrola vstupnich dat
			$this->id_ceny = $this->check_int($id_ceny);
                        //$this->id_objekt_kategorie = $this->check_int($id_objekt_kategorie);
			$this->nazev_ceny = $this->check($nazev_ceny);
			$this->kratky_nazev = $this->check($kratky_nazev);
                        $this->odjezdove_misto = $this->check($odjezdove_misto);
                        $this->kod_letiste = $this->check($kod_letiste);
			$this->zkraceny_vypis = $this->check_int($zkraceny_vypis);
			$this->poradi_ceny = $this->check_int($poradi_ceny);
			$this->typ_ceny = $this->check_int($typ_ceny);
			$this->zakladni_cena = $this->check_int($zakladni_cena);
			$this->kapacita_bez_omezeni = $this->check_int($kapacita_bez_omezeni);
			$this->use_pocet_noci = $this->check_int($use_pocet_noci);
                        $this->nazev_ceny_en = $this->check($nazev_ceny_en);
                        $this->kratky_nazev_en = $this->check($kratky_nazev_en);
                        $this->typ_provize = $this->check_int($typ_provize);
			$this->vyse_provize = $this->check_int($vyse_provize);
                        $this->nezobrazovat = $this->check_int($nezobrazovat);
                        $this->id_kv = $this->check_int($id_kv);
                        if($this->id_kv == 0){
                            $this->id_kv = "NULL";
                        }
                $max_ok = 20;        
		//pokud jsou vporadku data, vytvorim danou cast dotazu
		if($this->legal_data($this->id_ceny,$this->nazev_ceny,$this->typ_ceny,$this->zakladni_cena,$this->kapacita_bez_omezeni,$this->nezobrazovat) and $this->legal_operation){
                        
			$this->pocet_zaznamu++;
                        if($this->serial_typ_provize == 3){
                                //provize dle sluzeb
                                $provize_list_insert = ",".$this->typ_provize.", ".$this->vyse_provize." ";
                                $provize_list_update = ",`typ_provize`=".$this->typ_provize.", `vyse_provize`=".$this->vyse_provize." ";
                        }
                        if($this->id_objekt_kategorie<=0){
                               $this->id_objekt_kategorie = "NULL" ;
                        }
			if($this->typ_pozadavku=="create"){	
                            
                                $this->pocet_zaznamu_insert++;
                                
				$this->query_insert[$this->pocet_zaznamu_insert]="(".$this->id_serial.",\"".$this->nazev_ceny."\",\"".$this->kratky_nazev."\",\"".$this->odjezdove_misto."\",\"".$this->kod_letiste."\",\"".$this->nazev_ceny_en."\",\"".$this->kratky_nazev_en."\",".$this->zkraceny_vypis.",
																".$this->poradi_ceny.",".$this->typ_ceny.",".$this->zakladni_cena.",".$this->kapacita_bez_omezeni.",".$this->id_kv.",".$this->use_pocet_noci."".$provize_list_insert.")";
                                for($i=1;$i<=$max_ok;$i++){
                                    if($_POST["id_objekt_kategorie_".$this->pocet_zaznamu_insert."_".$i.""]>0){  
                                        $use_cena_tok = $this->check_int($_POST["use_cena_tok_".$this->pocet_zaznamu_insert."_".$i.""]);
                                        $id_objekt_kategorie = $this->check_int($_POST["id_objekt_kategorie_".$this->pocet_zaznamu_insert."_".$i.""]);
                                        if($this->query_insert_ok==""){
                                            $this->query_insert_ok.="(cena_".$this->pocet_zaznamu_insert.", ".$id_objekt_kategorie.", ".$use_cena_tok.")";
                                        }else{
                                            $this->query_insert_ok.=",(cena_".$this->pocet_zaznamu_insert.", ".$id_objekt_kategorie.", ".$use_cena_tok.")";
                                        }
                                        
                                    }                                    
                                }                                
                               
                            
                                
			}else if($this->typ_pozadavku=="update"){										
				$this->pocet_zaznamu_update++;
				$this->query_update[$this->pocet_zaznamu_update]="`nazev_ceny_en`=\"".$this->nazev_ceny_en."\",`kratky_nazev_en`=\"".$this->kratky_nazev_en."\",`nazev_ceny`=\"".$this->nazev_ceny."\",`kratky_nazev`=\"".$this->kratky_nazev."\",
                                                                `odjezdove_misto`=\"".$this->odjezdove_misto."\",`kod_letiste`=\"".$this->kod_letiste."\",
								`zkraceny_vypis`=".$this->zkraceny_vypis.",`poradi_ceny`=".$this->poradi_ceny.",`typ_ceny`=".$this->typ_ceny.",
								`zakladni_cena`=".$this->zakladni_cena.",`kapacita_bez_omezeni`=".$this->kapacita_bez_omezeni.",`use_pocet_noci`=".$this->use_pocet_noci.$provize_list_update."  
								WHERE `id_cena`=".$this->id_ceny." LIMIT 1";
                                
				$this->query_delete_ok[]="delete from `cena_objekt_kategorie` where `id_cena` = ".$this->id_ceny."";
                                for($i=1;$i<=$max_ok;$i++){
                                    
                                    if($_POST["id_objekt_kategorie_".$this->id_ceny."_".$i.""]>0){    
                                        $id_objekt_kategorie = $this->check_int($_POST["id_objekt_kategorie_".$this->id_ceny."_".$i.""]);
                                        $use_cena_tok = $this->check_int($_POST["use_cena_tok_".$this->id_ceny."_".$i.""]);
                                        if($this->query_insert_ok==""){
                                            $this->query_insert_ok.="(".$this->id_ceny.", ".$id_objekt_kategorie.", ".$use_cena_tok.")";
                                        }else{
                                            $this->query_insert_ok.=",(".$this->id_ceny.", ".$id_objekt_kategorie.", ".$use_cena_tok.")";
                                        }
                                        $this->query_update_tok[] = "UPDATE `cena_zajezd_tok` SET `je_vstupenka`=".$use_cena_tok." where `id_objekt_kategorie`=".$id_objekt_kategorie." and `id_cena`=$this->id_ceny";

                                    }                                    
                                } 
                                
                            
			}
			//echo $this->pocet_zaznamu;
			
		}//if legal_data
	}

	/**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
	function correct_data($typ_pozadavku){
		$ok = 1;
		//kontrolovane pole id_cena, id_zajezd
                if($typ_pozadavku == "ajax_get_letuska_ceny" ){
                    return true;
                }
			if(!Validace::int_min($this->id_serial,1) ){
				$ok = 0;
				$this->chyba("Seriál není identifikován");
			}
                        if($typ_pozadavku == "ajax_get_goglobal_ceny" ){
                            if(!Validace::int_min($this->id_ceny,1) ){
                                    $ok = 0;
                                    $this->chyba("Služba není identifikována");
                            }
                        }
			if($typ_pozadavku == "new" or $typ_pozadavku == "create" or $typ_pozadavku == "update"){
				if(!Validace::int_min_max($this->pocet,1,MAX_CEN) ){
					$ok = 0;
					$this->chyba("Poèet cen není v povoleném intervalu 1 - ".MAX_CEN."");
				}		
			}									
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}	
	}	
	
	/**kontrola zda data jsou legalni (neprazdne nazvy, nenulova id atd.*/
	function legal_data($id_ceny,$nazev_ceny,$typ_ceny,$zakladni_cena,$kapacita_bez_omezeni, $nezobrazovat=0){
		$ok = 1;
		$this->cislo_ceny++;
                if(!$nezobrazovat){
		//kontrolovane pole nazev_cena
			if(!Validace::text($nazev_ceny) ){
				$ok = 0;
				$this->chyba("Název ceny è.".$this->cislo_ceny." není specifikován, cena nebude vytvoøena/zmìnìna");
			}
                }        
		//pokud je vse vporadku...
		if($ok == 1){
			return true;
		}else{
			return false;
		}	
	}
	
	/**po prijmuti vsech dat vytvori cely dotaz a odesle ho do mysql*/
	function finish_query(){
		//print_r($this->query);
		$this->database->start_transaction();		                
                if($this->pocet_zaznamu_insert){
				//vytvorim zacatek dotazu - prvni hodnoty by zde mely byt vzdy
				
				$i=1;
				while($i<=$this->pocet_zaznamu_insert){
					$dotaz=$this->query_insert[0].$this->query_insert[$i];
					//echo $dotaz;
					//skladam jednotlive dotazy a rovnou je odesilam
                                       // $this->chyba($dotaz);
					$create_ceny=$this->database->transaction_query($dotaz)
		 				or $this->chyba("Chyba pøi dotazu do databáze create ceny".mysqli_error($GLOBALS["core"]->database->db_spojeni).$dotaz);
                                        $id_cena = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                        $this->id_ceny = $id_cena;
                                        //postupne doplnuju id_ceny ve vkladani OK
                                        $this->query_insert_ok = str_replace("cena_".$i.",", $id_cena.",", $this->query_insert_ok);
					$i++;
				}
                                
				//echo $dotaz;
				//odeslu dotaz
				/*$create_ceny=$this->database->transaction_query($dotaz)
		 			or $this->chyba("Chyba pøi dotazu do databáze".mysqli_error($GLOBALS["core"]->database->db_spojeni));*/				
			}
			if($this->pocet_zaznamu_update){
				$i=1;
				while($i<=$this->pocet_zaznamu_update){
					$dotaz=$this->query_update[0].$this->query_update[$i];
					//echo $dotaz;
					//skladam jednotlive dotazy a rovnou je odesilam
					$update_ceny=$this->database->transaction_query($dotaz)
		 				or $this->chyba("Chyba pøi dotazu do databáze update ceny".mysqli_error($GLOBALS["core"]->database->db_spojeni));	
					$i++;
				}			
			}
                        
			foreach ($this->query_delete_ok as $key => $dotaz) {
                                $delete_ok=$this->database->transaction_query($dotaz)
		 				or $this->chyba("Chyba pøi dotazu do databáze delete OK".mysqli_error($GLOBALS["core"]->database->db_spojeni).$dotaz);	
                        }
                        
                        if($this->query_insert_ok!=""){
                                    $create_ok=$this->database->transaction_query("insert into `cena_objekt_kategorie` (`id_cena`,`id_objekt_kategorie`,`use_cena_tok`) values ".$this->query_insert_ok )
		 				or $this->chyba("Chyba pøi dotazu do databáze Create OK".mysqli_error($GLOBALS["core"]->database->db_spojeni).$this->query_insert_ok);
                                  //  echo "insert into `cena_objekt_kategorie` (`id_cena`,`id_objekt_kategorie`,`use_cena_tok`) values ".$this->query_insert_ok ;
                        }
                        foreach ($this->query_update_tok as $key => $dotaz) {
                                $update_tok=$this->database->transaction_query($dotaz)
		 				or $this->chyba("Chyba pøi dotazu do databáze update TOK".mysqli_error($GLOBALS["core"]->database->db_spojeni).$dotaz);	
                        }
                                                                
		//vygenerování potvrzovací hlášky
		if( !$this->get_error_message() ){
			$this->database->commit();
                        
                        //zkontroluju zda existuje zakladni cena
                        $sql = "select * from `cena` where `zakladni_cena` = 1 and `id_serial` = ".$this->id_serial."";
                        $result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
                        $pocet = mysqli_num_rows($result);
                        if($pocet==0){
                            $this->chyba("Požadovaná akce probìhla úspìšnì, ale zkontrolujte, zda je alespoò jedna služba oznaèena za základní");
                        }                        
			$this->confirm("Požadovaná akce probìhla úspìšnì");
		}			
	}
        
 /*vytvori SOAP request do GoGlobal API, z odpovedi vybere nejlevnejsi ceny pro kazdy hotel a ty pak ulozi do goglobal_pricelist */
        public static function ajax_get_goglobal_hotel_by_name(){
            //$goglobal_hotel_name
            //$goglobal_citycode
            $city = $_POST["city"];
            $country = $_POST["country"];
            $goglobal_hotel_name = $_POST["hotelName"];
            $termin_od = $_POST["terminOd"];             
            $pocet_noci = $_POST["pocetNoci"];
            
            if($goglobal_hotel_name==""){
                     $goglobal_response = array("error"=>"Invalid hotel name"); 
            }else{
                        
                $query = "select * from goglobal_citycodes where city like \"$city%\" ";
                if ($country !=""){
                     $query .= "and country like \"$country%\"";     
                }
                $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);    
                $results = mysqli_num_rows($data);
                
                $cityCodesList = "";
                while ($row = mysqli_fetch_array($data)) {                                   
                    $cityCodesList .= $row["city"].", ".$row["country"].", ".$row["cityID"]."<br/>";
                }
                mysqli_data_seek($data, 0);
                
                //$goglobal_response = Array();
                #$wsdl = 'http://slantour.xml.goglobal.travel/xmlwebservice.asmx?WSDL';
                $wsdl = 'https://slantour.xml.goglobal.travel/xmlwebservice.asmx?WSDL';
                $trace = true;
                $exceptions = true;
                $xml_array['requestType'] = '1';
                $query_part_rooms = "<Room Adults=\"2\" RoomCount=\"1\"></Room>";
                
                $gg_correctResult = false;
                
                while ($row = mysqli_fetch_array($data)) {                
                    $goglobal_citycode = $row["cityID"];
                    //$goglobal_response = array("error"=>"Invalid city code");
                    // print_r($goGlobalIDs);
                    
                    if($goglobal_citycode!=""){
                         
                        $xml_array['xmlRequest'] = '
                            <Root>
                                <Header>
                                        <Agency>62282</Agency>
                                        <User>SLANTOURXML</User>
                                        <Password>slan2021</Password>
                                        <Operation>HOTEL_SEARCH_REQUEST</Operation>
                                        <OperationType>Request</OperationType>
                                </Header>
                                <Main>
                                        <SortOrder>1</SortOrder>
                                        <FilterPriceMin>0</FilterPriceMin>
                                        <FilterPriceMax>100000</FilterPriceMax>
                                        <MaximumWaitTime>3</MaximumWaitTime>
                                        <MaxResponses>100</MaxResponses>
                                        <FilterRoomBasises>
                                                <FilterRoomBasis></FilterRoomBasis>
                                        </FilterRoomBasises>
                                        <CityCode>'.$goglobal_citycode.'</CityCode>
                                        <HotelName>'.$goglobal_hotel_name.'</HotelName>
                                        <Apartments>false</Apartments>
                                        <ArrivalDate>'.$termin_od.'</ArrivalDate>
                                        <Nights>'.$pocet_noci.'</Nights>
                                        <Rooms>
                                            <Room Adults="2" RoomCount="1"></Room>
                                        </Rooms>
                                </Main>
                            </Root>';

                       // echo $xml_array['xmlRequest'] ;
                        try
                        {
                           $client = new SoapClient($wsdl, array('trace' => $trace, 'exceptions' => $exceptions));
                           $response = $client->MakeRequest($xml_array);
                            $objectResponse = simplexml_load_string($response->MakeRequestResult, null, LIBXML_NOCDATA);
                           //   print_r($objectResponse);
                            $operation_type = $objectResponse->Header->OperationType;

                            $hotels = $objectResponse->Main->Hotel;
                            // print_r($objectResponse->Main->Hotel);
                            $errors = $objectResponse->Main->Error;
                            //  print_r($objectResponse);
                            if((string) $operation_type!="Error" ){
                                foreach ($hotels as $key => $hotel) {                                    
                                    $gg_correctResult = true;
                                   // echo $hotel->HotelCode.$hotel->HotelName.$hotel->RoomType;
                                    $goglobal_response["hotel_".$hotel->HotelCode]["name"] = (string) $hotel->HotelName;
                                    $goglobal_response["hotel_".$hotel->HotelCode]["code"] = (string) $hotel->HotelCode;
                                 } 

                            }else{
                                $goglobal_error = array("error"=>(string) $errors);
                            }    

                        }
                        catch (Exception $e)
                        {
                           $goglobal_error = array("error"=>$e->getMessage());     
                        }              
                    }
                }
                if($results > 1){
                    $goglobal_response["warning"] = "<br/> Warning: Multiple (".$results.") citicodes found <br/><br/>".$cityCodesList;
                    $goglobal_error["warning"] = "<br/> Warning: Multiple (".$results.") citicodes found<br/><br/>".$cityCodesList;
                }  
                // print_r($goglobal_pricelist);
                if($gg_correctResult){
                    return json_encode($goglobal_response);
                }else{
                    return json_encode($goglobal_error);
                }
                
            }
        }        
        
        static function ajax_get_goglobal_for_hotelids($goGlobalIDs, $max_kapacita, $termin_od, $termin_do){
            // print_r($goGlobalIDs);
            if(sizeof((array)$goGlobalIDs)==0){
                $goglobal_pricelist = array("error"=>"No hotel IDs found!"); 
            }else{

                $wsdl = "https://slantour.xml.goglobal.travel/xmlwebservice.asmx?WSDL";       // 'http://slantour.xml.goglobal.travel/xmlwebservice.asmx?WSDL';
                $trace = true;
                $exceptions = true;
                $xml_array['requestType'] = '1';
                
                $query_part_hotels = "";
                $query_part_rooms = "<Room Adults=\"$max_kapacita\" RoomCount=\"1\" ChildCount=\"0\"></Room>";
                
               
                $pocet_noci = config_export_sdovolena::calculate_pocet_noci($termin_od, $termin_do);
                foreach ($goGlobalIDs as $key => $id) {      
                    if($id>0) {                                                          
                        $query_part_hotels .="<HotelId>$id</HotelId>\n";      
                    }                                  
                }
               
        /*
        
                                <Agency>62282</Agency>
                                <User>SLANTOURXML</User>
                                <Password>slan2021</Password>
                                
                                <Agency>135015</Agency> 
                                <User>SLANXMLTEST</User>
                                <Password>45YBTHA6N34</Password>      
        
        */

                //<Main>
                $xml_array['xmlRequest'] = '
                    <Root>
                        <Header>
                                <Agency>62282</Agency>
                                <User>SLANTOURXML</User>
                                <Password>slan2021</Password>  
                                <Operation>HOTEL_SEARCH_REQUEST</Operation>
                                <OperationType>Request</OperationType>
                        </Header>
                        <Main Version="2.3" ResponseFormat="JSON" IncludeGeo="false">                                
                                <SortOrder>1</SortOrder> 
                                <FilterPriceMin>0</FilterPriceMin>
                                <FilterPriceMax>100000</FilterPriceMax>
                                <MaximumWaitTime>40</MaximumWaitTime>
                                <MaxResponses>500</MaxResponses>
                                
                                <FilterRoomBasises>
                                </FilterRoomBasises>
                                <Nationality>CZ</Nationality>
                                <Apartments>false</Apartments>
                                <Hotels>
                                        '.$query_part_hotels.'
                                </Hotels>
                                <ArrivalDate>'.$termin_od.'</ArrivalDate>
                                <Nights>'.$pocet_noci.'</Nights>
                                <Rooms>
                                        '.$query_part_rooms.'
                                </Rooms>
                        </Main>
                    </Root>';
                
             
            } 
            
            //print_r($xml_array);
            try
            {
               $client = new SoapClient($wsdl, array(
                    'trace' => $trace, 
                    'exceptions' => $exceptions,        
                    'soap_version' => SOAP_1_2,  
                    'stream_context' => stream_context_create(array(
                      'http' => array(
                        //'method'=>"POST",
                        'header'=> "Content-Type: application/soap+xml; charset=utf-8\r\n"
                                 . "API-Operation: HOTEL_SEARCH_REQUEST\r\n"
                                 . "API-AgencyID: 62282\r\n"       //     62282
                      )
                    ))
                ));
               
               $response = $client->MakeRequest($xml_array);   
               //print_r($client->__getLastRequestHeaders()  );
               //print_r($client->__getLastRequest());
            }
            catch (Exception $e)
            {
               //print_r($e) ;     
               $goglobal_pricelist = array("error"=>$e->getMessage());     
            }
            
            //echo  $response->MakeRequestResult;

            $objectResponse = json_decode($response->MakeRequestResult);
            
            //print_r($objectResponse);
             
            $operation_type = $objectResponse->Header->OperationType;
            
            
            $hotels = $objectResponse->Hotels;
            // print_r($objectResponse->Main->Hotel);
            //$errors = $objectResponse->Main->Error;
            // print_r($objectResponse);
            if((string) $operation_type=="Response" ){
                foreach ($hotels as $key => $hotel) {
                   // echo $hotel->HotelCode.$hotel->HotelName.$hotel->RoomType;
                   $offers = $hotel->Offers;
                   foreach ($offers as $key => $offer){ 
                      $goglobal_pricelist["hotel_".$hotel->HotelCode]["name"] = strip_tags((string) $hotel->HotelName);
                      $goglobal_pricelist["hotel_".$hotel->HotelCode]["code"] = strip_tags((string) $hotel->HotelCode);
                      $goglobal_pricelist["hotel_".$hotel->HotelCode]["offer"][] = array("room"=>strip_tags((string) $offer->Rooms[0]), "roomBasis"=>strip_tags((string) $offer->RoomBasis), "price"=>strip_tags((string) $offer->TotalPrice), "currency"=>strip_tags((string) $offer->Currency), "policy"=>strip_tags((string) $offer->Remark));                  
                    }
                }   
            }else{
                $goglobal_pricelist = array("error"=>(string)  $objectResponse->Main->Error->Message);
            }
            //print_r($goglobal_pricelist);
            return $goglobal_pricelist;            
            
            
           /* varianta pro xml zpracovani
           $objectResponse = simplexml_load_string($response->MakeRequestResult, null, LIBXML_NOCDATA); 
           $operation_type = $objectResponse->Header->OperationType;
           
           $hotels = $objectResponse->Main->Hotel;
           // print_r($objectResponse->Main->Hotel);
            $errors = $objectResponse->Main->Error;
          //  print_r($objectResponse);
            if((string) $operation_type!="Error" ){
                foreach ($hotels as $key => $hotel) {
                   // echo $hotel->HotelCode.$hotel->HotelName.$hotel->RoomType;
                    $goglobal_pricelist["hotel_".$hotel->HotelCode]["name"] = strip_tags((string) $hotel->HotelName);
                    $goglobal_pricelist["hotel_".$hotel->HotelCode]["code"] = strip_tags((string) $hotel->HotelCode);
                    $goglobal_pricelist["hotel_".$hotel->HotelCode]["offer"][] = array("room"=>strip_tags((string) $hotel->RoomType), "roomBasis"=>strip_tags((string) $hotel->RoomBasis), "price"=>strip_tags((string) $hotel->TotalPrice), "currency"=>strip_tags((string) $hotel->Currency), "policy"=>strip_tags((string) $hotel->Remark));
                }   
            }else{
                $goglobal_pricelist = array("error"=>(string) $errors);
            }
            return $goglobal_pricelist;  */
        }
        
        /*vytvori SOAP request do GoGlobal API, z odpovedi vybere nejlevnejsi ceny pro kazdy hotel a ty pak ulozi do goglobal_pricelist */
        function ajax_get_goglobal_ceny(){
            $goGlobalIDs = array();
            $max_kapacita = 1;
            $query = $this->create_query("get_ok", $this->id_ceny);
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);            
            while ($row = mysqli_fetch_array($data)) {
                if($row["hlavni_kapacita"]>$max_kapacita){
                    $max_kapacita = $row["hlavni_kapacita"];
                }
                if($row["goglobal_hotel_id_ok"]!="" and !in_array($row["goglobal_hotel_id_ok"], $goGlobalIDs)){
                    $goGlobalIDs[]=$row["goglobal_hotel_id_ok"];
                }else if($row["goglobal_hotel_id"]!="" and !in_array($row["goglobal_hotel_id"], $goGlobalIDs)){
                    $goGlobalIDs[]=$row["goglobal_hotel_id"];
                }
            }
            $termin_od = $this->change_date_cz_en($this->check($_POST["termin_od"]));
            $termin_do = $this->change_date_cz_en($this->check($_POST["termin_do"]));
           $this->goglobal_pricelist = Cena_serial::ajax_get_goglobal_for_hotelids($goGlobalIDs, $max_kapacita, $termin_od, $termin_do);
           // print_r($this->goglobal_pricelist);
            //echo $xml_array['xmlRequest'] ;
        }
        /*vytvori JSON z promenne goglobal_pricelist a ten vrati*/
        function show_goglobal_ceny(){
            return json_encode($this->goglobal_pricelist);
        }        
        
        /*vytvori SOAP request do Letuska.cz API, z odpovedi vybere jednotlive lety a ty pak zobrazi */
        function ajax_get_letuska_ceny(){

            $query = $this->create_query("get_ok", $this->id_ceny);
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);            

            ##adults=2&rtn=1&direct=0&multi=0&nbfrom=0&nbto=0&date1=2016-11-03&from1=PRG&to1=PAR&date_rtn=2016-11-13
            $from = $this->check($_POST["from_code"]);
            $to = $this->check($_POST["to_code"]);
            $direct = $this->check_int($_POST["direct"]);
            $dates_from = $this->change_date_cz_en($this->check($_POST["termin_od"]));
            $dates_to = $this->change_date_cz_en($this->check($_POST["termin_do"]));
            if($direct == 1){
                $limit = 20;
            }else{
                $limit = 50;
            }                                                //cid=SLT&      $direct
            $url = "https://mapa.letuska.cz/v1/proxymp.php?cid=SLT&companyID=SLT&adults=1&children=0&infants=0&cabinclass=economy&rtn=1&multi=0&nbfrom=0&nbto=0&direct=$direct&date1=$dates_from&from1=$from&to1=$to&date_rtn=$dates_to";      //&limit=$limit
            

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $res = curl_exec($ch);
            if($res === false){
              echo $url;
              echo 'Curl error: ' . curl_error($ch);
              
            }
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($res, 0, $header_size);
            $text = substr($res, $header_size);
           //echo $res;
            $this->letuska_pricelist = $text;
                
               
            #print_r($this->letuska_pricelist);
            #print_r(json_encode($this->letuska_pricelist));
        }
        /*vytvori JSON z promenne goglobal_pricelist a ten vrati*/
        function show_letuska_ceny(){
            return $this->letuska_pricelist;
        }
        //provede update tabulky cena o data z pouzitych kalkulacnich vzorcu
	function kalkulacni_vzorce_update(){
            $i=1;
            //var_dump($_POST);
            $this->database->start_transaction();
            while($_POST["id_cena_$i"]!=""){                
                $id_cena = $this->check_int($_POST["id_cena_$i"]);
                $id_kalkulacni_vzorec = $this->check_int($_POST["id_kalkulacni_vzorec_$i"]);
                if($id_kalkulacni_vzorec <=0 ){
                    $id_kalkulacni_vzorec = "NULL";
                }
                $error = 0;
                $query = "UPDATE `cena` SET `id_kalkulacni_vzorec`=$id_kalkulacni_vzorec where `id_cena`=$id_cena limit 1";
                $create_ceny=$this->database->transaction_query($query,1)
		            or $this->chyba("Chyba pøi dotazu do databáze create ceny".mysqli_error($GLOBALS["core"]->database->db_spojeni).$query);
                if(!$create_ceny){$error = 1;}  // initiate DB rollback later on
                //vytvorim jednotlive promenne vzorce pokud existuji (pokud ne, nedelam nic)
                $j=1;

                while($_POST["name_variable_".$j."_cena_".$id_cena.""]!=""){
                        $variable_name = $this->check($_POST["name_variable_".$j."_cena_".$id_cena.""]);
                        $variable_type = $this->check($_POST["type_variable_".$j."_cena_".$id_cena.""]);
                        $variable_mena = $this->check($_POST["variable_currency_".$j."_cena_".$id_cena.""]);
                        if($variable_type=="const"){
                            $variable_castka = $this->check($_POST["value_variable_".$j."_cena_".$id_cena.""]);

                            $query_const = "INSERT INTO `cena_promenna`(`id_cena`, `id_vzorec`, `nazev_promenne`, `typ_promenne`,`id_mena`, `fixni_castka`) 
                                        VALUES ($id_cena,$id_kalkulacni_vzorec,\"$variable_name\",\"$variable_type\",$variable_mena,$variable_castka)
                                        ON DUPLICATE KEY UPDATE `typ_promenne`=\"$variable_type\", `id_mena`=$variable_mena , `fixni_castka`=$variable_castka, `id_cena_promenna`=LAST_INSERT_ID(`id_cena_promenna`)";
                            $res = $this->database->transaction_query($query_const)
                                or $this->chyba("Chyba pøi dotazu do databáze".mysqli_error($GLOBALS["core"]->database->db_spojeni).$query_const);
                            if(!$res){$error = 1;}  // initiate DB rollback later on
                        }else if($variable_type=="timeMap" or $variable_type=="external" or $variable_type=="letuska"){
                            $data_from_object = "";
                            if($variable_type=="letuska"){
                                $data_from_object = $this->check($_POST["select_letuska_".$j."_cena_".$id_cena.""]);
                            }else if($variable_type=="external"){
                                $data_from_object = $this->check($_POST["select_goglobal_".$j."_cena_".$id_cena.""]);
                            }


                            $name_obj = "";
                            $val_obj = "";
                            $val_dupl = "";
                            if($data_from_object != "" and $data_from_object!="-1"){
                                $name_obj = "`data_from_object`,";
                                $val_obj = "$data_from_object,";
                                $val_dupl = "`data_from_object`=$data_from_object,";
                            }

                            if($variable_type=="letuska"){
                                $flight_from = $this->check($_POST["flight_from_cena_".$id_cena."_".$variable_name]);
                                $flight_to = $this->check($_POST["flight_to_cena_".$id_cena."_".$variable_name]);
                                $flight_direct = $this->check_int($_POST["flight_direct_cena_".$id_cena."_".$variable_name]);
                            }
                            if($variable_type=="letuska" and $flight_from!="" and $flight_to !=""){

                                $query_timeMap = "INSERT INTO `cena_promenna`(`id_cena`,$name_obj `id_vzorec`, `nazev_promenne`, `typ_promenne`,`id_mena`, `fixni_castka`, `flight_from`, `flight_to`, `flight_direct`) 
                                        VALUES ($id_cena,$val_obj $id_kalkulacni_vzorec,\"$variable_name\",\"$variable_type\",$variable_mena,NULL,\"$flight_from\", \"$flight_to\", $flight_direct)
                                        ON DUPLICATE KEY UPDATE $val_dupl `typ_promenne`=\"$variable_type\", `flight_from`=\"$flight_from\",`flight_to`=\"$flight_to\",`flight_direct`=$flight_direct, `id_mena`=$variable_mena , `fixni_castka`=NULL, `id_cena_promenna`=LAST_INSERT_ID(`id_cena_promenna`)";

                            }else{
                                $query_timeMap = "INSERT INTO `cena_promenna`(`id_cena`,$name_obj `id_vzorec`, `nazev_promenne`, `typ_promenne`,`id_mena`, `fixni_castka`) 
                                        VALUES ($id_cena,$val_obj $id_kalkulacni_vzorec,\"$variable_name\",\"$variable_type\",$variable_mena,NULL)
                                        ON DUPLICATE KEY UPDATE $val_dupl `typ_promenne`=\"$variable_type\", `id_mena`=$variable_mena , `fixni_castka`=NULL, `id_cena_promenna`=LAST_INSERT_ID(`id_cena_promenna`)";
                            }

                            $res = $this->database->transaction_query($query_timeMap)
                                    or $this->chyba("Chyba pøi dotazu do databáze".mysqli_error($GLOBALS["core"]->database->db_spojeni).$query_timeMap);
                            if(!$res){$error = 1;}  // initiate DB rollback later on

                            $k=1;
                            if($_POST["cenova_mapa_opened_cena_".$id_cena."_".$variable_name.""]!=""){
                                $id_cena_promenna = mysqli_insert_id($GLOBALS["core"]->database->db_spojeni);
                                //vyplnoval jsem terminovou mapu -> misom ji premazat a updatovat
                                $query_delete_cm = "DELETE FROM `cena_promenna_cenova_mapa` WHERE `id_cena_promenna`=$id_cena_promenna";
                                $res = $this->database->transaction_query($query_delete_cm);
                                if(!$res){$error = 1;}  // initiate DB rollback later on
                                //DIRTY fix - potential breach through empty rows in termin_map
                                $max_rows = 300;
                                //while($_POST["cm_termin_od_".$k."_cena_".$id_cena."_".$variable_name.""]!=""){
                                while($k < $max_rows){
                                  if($_POST["cm_termin_od_".$k."_cena_".$id_cena."_".$variable_name.""]!=""){  
                                    $termin_od = $this->change_date_cz_en($this->check($_POST["cm_termin_od_".$k."_cena_".$id_cena."_".$variable_name.""]));
                                    $termin_do = $this->change_date_cz_en($this->check($_POST["cm_termin_do_".$k."_cena_".$id_cena."_".$variable_name.""]));
                                    $termin_do_shift = $this->check_int($_POST["termin_do_shift_".$k."_cena_".$id_cena."_".$variable_name.""]);
                                    $update_dates = $this->check_int($_POST["use_dates_".$k."_cena_".$id_cena."_".$variable_name.""]);
                                    $no_dates_update = 1 - $update_dates;
                                    echo $no_dates_update;
                                    /*if($update_dates == 0){
                                        $no_dates_update = 1;
                                    }*/
                                    $castka = $this->check($_POST["cm_castka_".$k."_cena_".$id_cena."_".$variable_name.""]);
                                    if($castka === ""){
                                        $castka = "NULL";
                                    }
                                    if($variable_type=="external" or $variable_type=="letuska"){
                                       $poznamka = $this->check($_POST["cm_poznamka_".$k."_cena_".$id_cena."_".$variable_name.""]); 
                                       $external_id = $this->check($_POST["cm_externalID_".$k."_cena_".$id_cena."_".$variable_name.""]); 
                                       $query_cm = "INSERT INTO `cena_promenna_cenova_mapa`(`id_cena_promenna`, `termin_od`, `termin_do`,`no_dates_generation`, `termin_do_shift`, `castka`, `external_id`, `poznamka`) 
                                                    VALUES ($id_cena_promenna,\"$termin_od\",\"$termin_do\",$no_dates_update, $termin_do_shift, $castka, \"$external_id\", \"$poznamka\")"; 
                                    }else{
                                       $query_cm = "INSERT INTO `cena_promenna_cenova_mapa`(`id_cena_promenna`, `termin_od`, `termin_do`,`no_dates_generation`, `castka`) 
                                                    VALUES ($id_cena_promenna,\"$termin_od\",\"$termin_do\",$no_dates_update,$castka)"; 
                                    }

                                    //echo $query_cm;
                                    $res = $this->database->transaction_query($query_cm);
                                    if(!$res){$error = 1;}  // initiate DB rollback later on

                                   
                                  }
                                  $k++;
                                }
                            }
                        }

                    
                    $j++;
                }
                $i++;
            }
            
            if( !$error ){
		        $this->database->commit();
            }else{
                $this->database->rollback();
                $this->chyba("Bìhem ukládání dat do databáze došlo k chybì. Poslední chyba:".mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        }
//provede update tabulky cena o data z pouzitych kalkulacnich vzorcu
	function kalkulacni_vzorce_smazat_terminy(){
            if($_POST["termin_deleteCM"]!=""){                
                $datum_mazani = $this->change_date_cz_en($_POST["termin_deleteCM"]);
                $id_serial = $this->check($_GET["id_serial"]);
                $query = "DELETE from `cena_promenna_cenova_mapa` 
                    where `termin_do` < \"".$datum_mazani."\" and 
                    id_cena_promenna in  (SELECT id_cena_promenna FROM `cena_promenna` join cena on (cena_promenna.id_cena = cena.id_cena) WHERE cena.id_serial=".$id_serial.") ";                
                mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            }                     
        }        
	/**vytvoreni dotazu ze zadanych parametru*/
	function create_query($typ_pozadavku, $param1=""){
		if($typ_pozadavku=="edit"){
			$dotaz="select *
					  from `cena` 
                                          left join `kalkulacni_vzorec_definice` on (id_vzorec_def = id_kalkulacni_vzorec) 
					where `id_serial`= ".$this->id_serial." 
					order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` ";
			//echo $dotaz;
			return $dotaz;
		
		}else if($typ_pozadavku=="show"){
			$dotaz="select *
					  from `cena` 
                                          left join `kalkulacni_vzorec_definice` on (id_vzorec_def = id_kalkulacni_vzorec) 
					where `id_serial`= ".$this->id_serial." 
					order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` ";
//			echo $dotaz;
			return $dotaz;
		}else if($typ_pozadavku=="kalkulacni_vzorce_edit"){
			$dotaz="select *
					  from `cena` 
					where `cena`.`id_serial`= ".$this->id_serial." 
					order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` ";
			//echo $dotaz;
			return $dotaz;
                        
		}else if($typ_pozadavku=="kalkulacni_vzorce_vygenerovat_terminy"){
			$dotaz="select *
					  from `cena` 
                                          left join `kalkulacni_vzorec_definice` on (id_vzorec_def = id_kalkulacni_vzorec) 
					where `cena`.`id_serial`= ".$this->id_serial." 
					order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` ";
			//echo $dotaz;
			return $dotaz;
				
		}else if($typ_pozadavku=="delete"){
			$dotaz= "DELETE FROM `cena` 
						WHERE `id_cena`=".$this->id_ceny."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;		
		}else if($typ_pozadavku=="get_ceny"){
			$dotaz="select *
					  from `cena`
					where `id_serial`= ".$this->id_serial." ";
			//echo $dotaz;
			return $dotaz;	
		}else if($typ_pozadavku=="get_user_create"){
			$dotaz= "SELECT `id_user_create` FROM `serial` 
						WHERE `id_serial`=".$this->id_serial."
						LIMIT 1";
			//echo $dotaz;
			return $dotaz;			
		}else if($typ_pozadavku=="get_ok"){
			$dotaz= "select distinct `objekt_kategorie`.`id_objekt_kategorie`,`objekt_kategorie`.`goglobal_hotel_id_ok`,`objekt_kategorie`.`kratky_nazev`, `objekt_kategorie`.`nazev`, `objekt_kategorie`.`hlavni_kapacita`,
                                                `objekt`.`nazev_objektu`, `objekt`.`kratky_nazev_objektu`, `objekt`.`id_objektu`,
                                                `cena_objekt_kategorie`.`use_cena_tok`,
                                                `objekt_ubytovani`.`goglobal_hotel_id`
                                                from `cena_objekt_kategorie` 
                                                join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie`) 
                                                join `objekt` on (`objekt`.`id_objektu` = `objekt_kategorie`.`id_objektu`) 
                                               join objekt_serial on (`objekt_kategorie`.`id_objektu` = `objekt_serial`.`id_objektu`
                                                                    and `objekt_serial`.`id_serial` = " . max( array(intval($this->get_id_serial()),intval($this->id_serial)  )) . ") 
                                                left join `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`) 
                                                where `id_cena`=".$param1.";";
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
				
		//podle jednotlivych typu pozadavku
		if($typ_pozadavku == "new"){
			return $zamestnanec->get_bool_prava($id_modul,"read");
                        
		}else if($typ_pozadavku == "ajax_get_goglobal_ceny" or $typ_pozadavku == "ajax_get_letuska_ceny" or $typ_pozadavku == "ajax_dalsi_sluzby"){
			return $zamestnanec->get_bool_prava($id_modul,"read");	
                        
		}else if($typ_pozadavku == "edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");

		}else if($typ_pozadavku == "show"){
			return $zamestnanec->get_bool_prava($id_modul,"read");		

		}else if($typ_pozadavku == "create"){
			//tvorba casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}				

		}else if($typ_pozadavku == "update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}
		}else if($typ_pozadavku == "kalkulacni_vzorce_update"){
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}  
                        
		}else if($typ_pozadavku == "kalkulacni_vzorce_edit"){
			return $zamestnanec->get_bool_prava($id_modul,"read");
                        
                }else if($typ_pozadavku == "kalkulacni_vzorce_deleteCM"){
			return $zamestnanec->get_bool_prava($id_modul,"read");    
                         
		}else if($typ_pozadavku == "kalkulacni_vzorce_vygenerovat_terminy"){
			return $zamestnanec->get_bool_prava($id_modul,"read");    
				
		}else if($typ_pozadavku == "delete"){
			//delete casti serialu := editace serialu
			if( $zamestnanec->get_bool_prava($id_modul,"edit_cizi") or 
				($zamestnanec->get_bool_prava($id_modul,"edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() ) ){
				return true;
			}else {
				return false;
			}						
		}

		//neznámý požadavek zakážeme
		return false;
	}
	
	/**zobrazi menu s moznostmi editace jednotlivych cen*/
	function show_submenu(){
                if($this->typ_pozadavku == "new"){
                    $submit = "<input type=\"button\" id=\"generate_nove_sluzby\" value=\"&gt;&gt;\"/>";
                }else{
                    $submit = "<input type=\"submit\"  value=\"&gt;&gt;\"/>";
                }
            
		$vystup= "  <form action=\"?typ=cena&amp;pozadavek=new&amp;id_serial=".$this->id_serial."\" method=\"post\">
		                <div class='submenu'>
		                    <a href='?id_serial=$this->id_serial&typ=cena'>seznam služeb</a>
                                    Pøidat <input type=\"text\" id=\"pocet_novych_cen\" name=\"pocet_cen\" value=\"1\" size=\"2\"/> další služby
                                        $submit
                                        <input type=\"hidden\" id=\"pocet_existujicich_cen\" value=\"$this->pocet\"/>
                                    <a href=\"?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=edit\">editovat služby</a>
                                    <a href=\"?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=kalkulacni_vzorce_edit\">kalkulaèní vzorce</a>
                                    <a href=\"?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=kalkulacni_vzorce_vygenerovat_terminy\" title=\"Vygenerovat termíny na základì termínových map kalkulaèních vzorcù\">vygenerovat termíny</a>
                                </div>
                            </form>				    ";
		return $vystup;
	}
	
	/**zobrazi hlavicku k seznamu*/
	function show_list_header(){
            $show_warning = "";
            if($this->id_ridici_objekt!="") {
                $show_warning = "<br/><strong style=\"color:red;\">Tento seriál je podøízen objektu <a href=\"/admin/objekty.php?id_objektu=".$this->id_ridici_objekt."&typ=objekty&pozadavek=edit\">".$this->nazev_objektu."</a>. Závislé služby není možné editovat. </strong>";                
            }
            
		$vystup = $show_warning.  "
				<table class=\"list\">
					<tr>

						<th>Id</th>
						<th>Název</th>
						<th>Krátký název</th>
						<th title=\"Vypsat ve zkráceném výpisu\">Zkr. výpis</th>                                                  
						<th>Poøadí sl.</th>
						<th>Typ ceny</th>
						<th title=\"Základní cena\">Zákl. cena</th>
						<th title=\"Neomezená kapacita\">Neomez. kap.</th>
						<th title=\"Násobit poètem nocí\">*#nocí</th>
                                                <th>Provize</th>
                                                <th title=\"Pøiøazené Objektové Kapacity\">Pøiøazené OK</th> 
                                                <th title=\"Pøiøazené Kalkulaèní vzorec\">Kalkulaèní vzorec</th>   
						<th>Možnosti editace</th>
					</tr>
		";
		return $vystup;
	}
	
	/**zobrazime seznam  cen pro dany serial*/
	function show_list_item($typ_zobrazeni){
		if($typ_zobrazeni="tabulka"){
			if($this->suda==1){
				$vypis=$vypis."<tr class=\"suda\">";
				}else{
				$vypis=$vypis."<tr class=\"licha\">";
			}
                        $povolit_editaci = true;
                        $pouzite_ok = "";
                        $query_ok = $this->create_query("get_ok", $this->get_id_cena());
//            echo $query_ok;
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);                        
                        while ($row_ok = mysqli_fetch_array($data)) {
                            if($row_ok["kratky_nazev_objektu"]!=""){
                                $nazev_objektu = $row_ok["kratky_nazev_objektu"];
                            }else{
                                $nazev_objektu = $row_ok["nazev_objektu"];
                            }
                            if($row_ok["kratky_nazev"]!=""){
                                 $nazev_ok = $row_ok["kratky_nazev"];
                            }else{
                                $nazev_ok = $row_ok["nazev"];
                            }
                            if($row_ok["use_cena_tok"]>0){
                                 $nazev_ok .= "; <b style=\"color:red\">Použít cenu TOK</b>";
                            }
                            $pouzite_ok .= $row_ok["id_objekt_kategorie"].": ".$nazev_objektu.", ".$nazev_ok."<br/>";
                            if($row_ok["id_objektu"]==$this->id_ridici_objekt){                                
                                $povolit_editaci = false;
                            }
                            
                        }
			//tvorba typu ceny
				$typ_ceny=Serial_library::get_typ_ceny( ($this->get_typ_ceny()-1) );

			//tvorba zkraceny_vypis
				if($this->get_zkraceny_vypis()){
						$zkraceny_vypis="<span class=\"green\">ANO</span>";
				}else{
						$zkraceny_vypis="<span class=\"red\">NE</span>";
				}
                                if($this->serial_typ_provize!=3){
					$provize="dle seriálu";
				}else{
                                    if($this->radek["typ_provize"]==2){
                                        $provize=$this->radek["vyse_provize"]." %";
                                    }else{
                                        $provize=$this->radek["vyse_provize"]." Kè";
                                    }
						
				}
				
			//tvorba zakladni_cena
				if($this->get_zakladni_cena()){
						$zakladni_cena="<span class=\"green\">ANO</span>";
				}else{
						$zakladni_cena="<span class=\"red\">NE</span>";
				}			
						
			//tvorba  kapacita bez omezeni
				if($this->get_kapacita_bez_omezeni()){
						$kapacita_bez_omezeni="<span class=\"green\">ANO</span>";
				}else{
						$kapacita_bez_omezeni="<span class=\"red\">NE</span>";
				}
				if($this->get_use_pocet_noci()){
						$use_pocet_noci="<span class=\"green\">ANO</span>";
				}else{
						$use_pocet_noci="<span class=\"red\">NE</span>";
				}
                               
                                    $edit = "<a href=\"?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=edit\">editovat</a>
						| <a href=\"?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=kalkulacni_vzorce_edit&amp;open_kv_cena=".$this->get_id_cena()."\">kalkulaèní vzorce</a>
						| <a class='anchor-delete' onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\" href=\"?typ=cena&amp;pozadavek=delete&amp;id_serial=".$this->get_id_serial()."&amp;id_cena=".$this->get_id_cena()."\">delete</a>";
                                
			$vypis=$vypis."
							<td class=\"nazev\">
								".$this->get_id_cena()."
							</td>
                                                        
							<td class=\"nazev\">
								".$this->get_nazev_ceny()."<br/>
                                                                ".$this->get_nazev_ceny_en()."
							</td>
							<td class=\"nazev\">
								".$this->get_kratky_nazev()."<br/>
                                                                ".$this->get_kratky_nazev_en()."
							</td>		
							<td class=\"zakladni_cena\">
								".$zkraceny_vypis."
							</td>	
							<td class=\"poradi_ceny\">
								".$this->get_poradi_ceny()."
							</td>											
							<td class=\"typ_ceny\">
								".$typ_ceny."
							</td>
							<td class=\"zakladni_cena\">
								".$zakladni_cena."
							</td>
							<td class=\"kapacita_bez_omezeni\">
								".$kapacita_bez_omezeni."
							</td>
							<td class=\"use_pocet_noci\">
								".$use_pocet_noci."
							</td>
                                                        <td class=\"provize\">
								".$provize."
							</td>
                                                        <td class=\"nazev\">
								".$pouzite_ok."
							</td>
                                                        <td class=\"nazev\">
								".$this->radek["nazev_vzorce"]."
							</td>
							<td class=\"menu\">
								".$edit."
							</td>
						</tr>";
			
			return $vypis;
		}//typ zobrazeni
	}	
        function show_select_objekt_kategorie($ok_array, $current_ok, $i, $id_sluzby, $use_cena_tok=0){
            if(!empty ($ok_array)){
            $result = "<select name=\"id_objekt_kategorie_".$id_sluzby."_".$i."\">
                        <option value=\"0\">---</option>";
            foreach ($ok_array as $key => $value) {
                if($value["kratky_nazev_objektu"]!=""){
                    $nazev_objektu = $value["kratky_nazev_objektu"];
                }else{
                    $nazev_objektu = $value["nazev_objektu"];
                }
                if($value["kratky_nazev"]!=""){
                    $nazev_ok = $value["kratky_nazev"];
                }else{
                    $nazev_ok = $value["nazev"];
                }
                
                if($key == $current_ok){
                    $result.="<option value=\"$key\" selected=\"selected\">".$nazev_objektu.", ".$nazev_ok."</option>";
                }else{
                    $result.="<option value=\"$key\" >".$nazev_objektu.", ".$nazev_ok."</option>";
                }                
            }
            $result.="</select>";
            $checked_cena_tok = "";
            if($use_cena_tok){
                $checked_cena_tok = "checked=\"checked\"";
            }
            $result.=" <input type=\"checkbox\" value=\"1\" name=\"use_cena_tok_".$id_sluzby."_".$i."\" $checked_cena_tok /> Použít cenu TOK<br/>";
            return $result;    
            }else{
                return "Žádné Objekty/OK pøiøazené k seriálu!";
            }
        }
        
        function ajax_show_form_ceny($posledni_cena, $pocet_novych) {
            $this->posledni_cena = $this->check_int($posledni_cena);
            $this->pocet = $this->check_int($pocet_novych) + $this->check_int($posledni_cena);
            
            return iconv("cp1250", "utf-8//TRANSLIT", $this->show_form()) ;
        }
        
	/**zobrazime formular*/
	function show_form(){
		//podle typu pozadavku vypisu spravny cil scriptu
		if($this->typ_pozadavku=="edit"){
			$action="?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=update";
			//tlacitka pro odesilani
			if( $this->legal("update") ){
					$submit= "<input type=\"submit\" value=\"Upravit ceny seriálu\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci  cen seriálu</strong>\n";
			}			
		}else if($this->typ_pozadavku=="new"){
			$action="?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=create";
			//tlacitka pro odesilani
			if( $this->legal("create") ){
					$submit= "<input type=\"submit\" value=\"Vytvoøit ceny seriálu\" />\n";
			}else{
					$submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k vytvoøení cen seriálu</strong>\n";
			}			
		}else{
			$action="";
		}
		
		//hlavicka formulare
		$i=1;
                if($this->typ_pozadavku!="ajax_dalsi_sluzby"){                    
                
                    $vypis="                
                                        <form action=\"".$action."\" method=\"post\" />					
					<table  class=\"list\" id=\"table_nove_sluzby\">
						<tr>
							<th>Id</th>                                                                                                               
							<th>Název ceny <span class=\"red\">*</span></th>
							<th>Krátký název</th>                                                        
							<th title=\"Vypsat ve zkráceném výpisu\">Zkr. výpis</th>                                                        
							<th>Poøadí ceny</th>
							<th>Typ ceny</th>
							<th title=\"Základní cena\">Zákl. cena</th>
							<th title=\"Kapacita bez omezení\">Kap. bez om.</th>
							<th title=\"Násobit poètem nocí\">* #nocí</th>
                                                        <th>Provize</th>
                                                        <th title=\"Pøiøazené Objektové Kapacity\">Pøiøazené OK</th>
						</tr>
						";
                }else{
                    $vypis="";
                }
                //do pole si dam zaznamy o vsech OK prirazenych k soucasnemu serialu
                $query_ok = "select `objekt_kategorie`.`id_objektu`,`objekt_kategorie`.`id_objekt_kategorie`,
                                         `objekt_kategorie`.`kratky_nazev`,`objekt_kategorie`.`nazev`, `objekt`.`nazev_objektu`  , `objekt`.`kratky_nazev_objektu`  
                                    from `objekt_serial` join `objekt_kategorie` on (`objekt_serial`.`id_objektu` = `objekt_kategorie`.`id_objektu`)
                                                           join `objekt` on (`objekt`.`id_objektu` = `objekt_kategorie`.`id_objektu`)
                                    where `objekt_serial`.`id_serial`=".$this->id_serial." ";
                //echo $query_ok;
                $objektove_kategorie_result=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_ok);
                $array_ok = array();
                while ($row = mysqli_fetch_array($objektove_kategorie_result)) {
                    $array_ok[$row["id_objekt_kategorie"]]=$row;
                }
                
		while($i<=$this->pocet){
                    if($this->typ_pozadavku!="ajax_dalsi_sluzby" or $i > $this->posledni_cena){
                        $povolit_editaci = true;
			/*posunu se na dalsi radek nactenych dat z databaze (pokud nejake mam)
				- to ze se neridim primo podle get_next_radek() nevadi - pocet_zaznamu jsem u editace ziskal jako mysqli_num_rows:))*/
			$select_objektove_kategorie="";
                        $input_objekt_kategorie = "";
                        if($this->typ_pozadavku=="edit"){
                            $this->get_next_radek();
                            
                            
                            //pokud jde o editaci, podívám se na pøiøazené OK
                            if($this->get_id_cena()){
                                $query_current_ok = "select * from `cena_objekt_kategorie` 
                                                        join `objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie`=`objekt_kategorie`.`id_objekt_kategorie` )
                                                        where `id_cena`=".$this->get_id_cena()."";    
                                $current_ok_result = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_current_ok);
                                $k=1;
                                while ($current_ok_row = mysqli_fetch_array($current_ok_result)) {
                                    $select_objektove_kategorie .= $this->show_select_objekt_kategorie($array_ok, $current_ok_row["id_objekt_kategorie"], $k, $this->get_id_cena(), $current_ok_row["use_cena_tok"]);
                                    
                                    if($current_ok_row["id_objektu"]==$this->id_ridici_objekt){    
                                        $input_objekt_kategorie = "".$array_ok[$current_ok_row["id_objekt_kategorie"]]["nazev_objektu"].", ".$array_ok[$current_ok_row["id_objekt_kategorie"]]["nazev"]."
                                                        <input type=\"hidden\" value=\"".$current_ok_row["id_objekt_kategorie"]."\" name=\"id_objekt_kategorie_".$this->get_id_cena()."_".$k."\">";
                                        $povolit_editaci = false;
                                       // $this->pocet--;
                                    }
                                    $k++;
                                }
                            } 
                            $select_objektove_kategorie .= $this->show_select_objekt_kategorie($array_ok, 0, $k, $this->get_id_cena());
                            if($input_objekt_kategorie!=""){
                                $select_objektove_kategorie=$input_objekt_kategorie;
                            }
                        }else{
                            //pridam jedno pole pro zacatek, zatim nemam id_ceny, proto tam dam misto toho aktualni $i - prepocte se po vytvoreni ceny ve finish()
                            $select_objektove_kategorie .= $this->show_select_objekt_kategorie($array_ok, 0, 1, $i);
                        }
                        //pridam jeden radek pro novou OK
			
                            if($povolit_editaci){  
                                $disabled="";
                            }else{
                                $disabled=" disabled=\"disabled\"";
                            }
                            if($i%2 == 1){
                                $cl_suda = " style=\"background-color: #f5f5f5;margin-bottom:3px;\"";
				$vypis=$vypis."<tr  style=\"background-color: #f5f5f5;\">";
                            }else{
                                $cl_suda = " style=\"background-color: #e7e7e7;margin-bottom:3px;\"";
				$vypis=$vypis."<tr  style=\"background-color: #e7e7e7;\">";
                            }
                            //tvorba jednotlivych poli formulare
				//tvorba select typ ceny
				$j=0;
				$select_typ_ceny="<select name=\"typ_ceny_".$i."\">";			
							
				while( Serial_library::get_typ_ceny($j)!="" ){
					if($this->get_typ_ceny()==($j+1)){
						$select_typ_ceny=$select_typ_ceny."<option value=\"".($j+1)."\" selected=\"selected\">".Serial_library::get_typ_ceny($j)."</option>";
					}else{
						$select_typ_ceny=$select_typ_ceny."<option value=\"".($j+1)."\">".Serial_library::get_typ_ceny($j)."</option>";
					}
					$j++;
				}
				$select_typ_ceny=$select_typ_ceny."</select>";	

					//tvorba $ratio_zakladni_cena
                                
				if($this->get_zakladni_cena()){
						$ratio_zakladni_cena="<input type=\"radio\" name=\"zakladni_cena\" value=\"".$i."\" checked=\"checked\" />";
                                                if($disabled){
                                                    $ratio_zakladni_cena .= "<input type=\"hidden\" name=\"zakladni_cena\" value=\"".$i."\"/>";
                                                }
				}else{
						$ratio_zakladni_cena="<input type=\"radio\" name=\"zakladni_cena\" value=\"".$i."\" />";
				}			
				
				//tvorba checkbox ucast ve vypisu
				if($this->get_zkraceny_vypis()){
						$checkbox_zkraceny_vypis="<input type=\"checkbox\" name=\"zkraceny_vypis_".$i."\" value=\"1\" onclick=\"copyToShortPriceName(".$i.")\" checked=\"checked\" />";
       				}else{
						$checkbox_zkraceny_vypis="<input type=\"checkbox\" name=\"zkraceny_vypis_".$i."\" value=\"1\" onclick=\"copyToShortPriceName(".$i.")\" />";
				}
				
				//tvorba checkbox kapacita bez omezeni
				if($this->get_kapacita_bez_omezeni()){
						$checkbox_kapacita_bez_omezeni="<input $disabled type=\"checkbox\" name=\"kapacita_bez_omezeni_".$i."\" value=\"1\" checked=\"checked\" />";
                                                if($disabled){
                                                    $ratio_zakladni_cena .= "<input type=\"hidden\" name=\"kapacita_bez_omezeni_".$i."\" value=\"1\"/>";
                                                }
				}else{
						$checkbox_kapacita_bez_omezeni="<input $disabled type=\"checkbox\" name=\"kapacita_bez_omezeni_".$i."\" value=\"1\" />";
				}
				//tvorba checkbox kapacita bez omezeni
				if($this->get_use_pocet_noci()){
						$checkbox_use_pocet_noci="<input type=\"checkbox\" name=\"use_pocet_noci_".$i."\" value=\"1\" checked=\"checked\" />";
				}else{
						$checkbox_use_pocet_noci="<input type=\"checkbox\" name=\"use_pocet_noci_".$i."\" value=\"1\" />";
				}				
                                if($this->serial_typ_provize!=3){
					$provize="dle seriálu";
				}else{
                                    $provize1 = "
                                        Typ: <select name=\"typ_provize_".$i."\">
                                                <option value=\"1\" ".(($this->radek["typ_provize"]==1)?("selected=\"selected\""):(""))."> Fixní v Kè</option>
                                                <option value=\"2\" ".(($this->radek["typ_provize"]==2)?("selected=\"selected\""):(""))."> Procentuelní v %</option>
                                            </select>";
                                     $provize2 = "
                                        Sazba: <input type=\"text\" name=\"vyse_provize_".$i."\" value=\"".$this->radek["vyse_provize"]."\"  />";
						
				}
                                if($disabled){
                                      $ceny = "<input name=\"nazev_cena_".$i."\"  type=\"hidden\" value=\"".$this->get_nazev_ceny()."\" class=\"wide\"/>
                                            <input name=\"kratky_nazev_".$i."\"  type=\"hidden\" value=\"".$this->get_kratky_nazev()."\" class=\"wide\"/>
                                            <input name=\"nazev_cena_en_".$i."\"  type=\"hidden\" value=\"".$this->get_nazev_ceny_en()."\" class=\"wide\"/>
                                            <input name=\"kratky_nazev_en_".$i."\"  type=\"hidden\" value=\"".$this->get_kratky_nazev_en()."\" class=\"wide\"/>";
                                }else{
                                    $ceny = "";
                                }
                            $vypis=$vypis."
							<td class=\"nazev\">
								".$this->get_id_cena()."
							</td>
                                                         
                                                        
							<td class=\"nazev\">
                                                        ".$ceny."
								<input name=\"id_cena_".$i."\" type=\"hidden\" value=\"".$this->get_id_cena()."\" />								 
								 <input name=\"nazev_cena_".$i."\" $disabled type=\"text\" value=\"".$this->get_nazev_ceny()."\" class=\"width-350px\"/>                                                                     
							</td>
							<td class=\"nazev\">						 
								 <input name=\"kratky_nazev_".$i."\" $disabled type=\"text\" value=\"".$this->get_kratky_nazev()."\" />
							</td>                                                       
							<td class=\"typ_ceny\">
								".$checkbox_zkraceny_vypis."
							</td>		
							<td class=\"poradi_ceny\">
								<input name=\"poradi_ceny_".$i."\" type=\"text\" value=\"".$this->get_poradi_ceny()."\" style=\"width:30px;\"/>
							</td>																	
							<td class=\"typ_ceny\">
								".$select_typ_ceny."
							</td>
							<td class=\"zakladni_cena\">
								".$ratio_zakladni_cena."
							</td>
							<td class=\"kapacita_bez_omezeni\">
								".$checkbox_kapacita_bez_omezeni."
							</td>
							<td class=\"kapacita_bez_omezeni\">
								".$checkbox_use_pocet_noci."
							</td>	
                                                        <td class=\"kapacita_bez_omezeni\">
								".$provize1."
							</td>	
                                                        <td rowspan=\"2\">
								".$select_objektove_kategorie."
							</td>
						</tr>
                                                <tr ".$cl_suda.">
                                                    <td>Anglicky:</td>                                                    
                                                        <td class=\"nazev\">
								 <input name=\"nazev_cena_en_".$i."\" $disabled type=\"text\" value=\"".$this->get_nazev_ceny_en()."\" class=\"width-350px\"/>
							</td>
							<td class=\"nazev\">
								 <input name=\"kratky_nazev_en_".$i."\" $disabled type=\"text\" value=\"".$this->get_kratky_nazev_en()."\" />
							</td>                                                        
                                                        <td colspan=\"6\" title=\"Vyplnìní je dùležité pro správný export XML dat v pøípadì že odlet NENÍ z Prahy!\">
                                                            Odletové/Odjezdové místo: <input name=\"odjezdove_misto_".$i."\" class=\"width-50px\" type=\"text\" value=\"".$this->get_odjezdove_misto()."\" />, 
                                                            Kód letištì: <input name=\"kod_letiste_".$i."\" type=\"text\" class=\"width-30px\" value=\"".$this->get_kod_letiste()."\" />
                                                        </td>
                                                        <td class=\"kapacita_bez_omezeni\">
								".$provize2."
							</td>	
                                                </tr>
                                                <tr style=\"background-color:white;\"><td colspan=\"11\" style=\"height:5px;\">
                                        "; 
                    }
                    $i++;

                }
                if($this->typ_pozadavku!="ajax_dalsi_sluzby"){ 
                    $vypis=$vypis."</table>".$submit."<input name=\"pocet\" id=\"celkovy_pocet_cen\" type=\"hidden\" value=\"".$this->pocet."\" /> </form>";
                }
                return $vypis;

	}	
 /**zobrazime formular*/
        function get_existing_zajezdy_for_termin($termin){
            foreach ($this->zajezdy as $id => $zajezd) {
                if($termin[0]==$zajezd["od"] and $termin[1]==$zajezd["do"]){
                    return $zajezd;
                }                                    
            }
            return null;
        }
        

	function show_form_vygenerovane_terminy(){
            //získat všechny termíny služeb
            $query_zajezdy = "
                select * from `zajezd` 
                    join `cena_zajezd` on (`zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) 
                    where `zajezd`.`id_serial`=".$this->id_serial."
                ";
            $data_zajezdy = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_zajezdy);
            $id_zajezd = "";
            $this->zajezdy = array();
            while ($row_zajezdy = mysqli_fetch_array($data_zajezdy)) {
                if($row_zajezdy["id_zajezd"]!=$id_zajezd){
                    $id_zajezd = $row_zajezdy["id_zajezd"];
                    $this->zajezdy[$id_zajezd] = array("od" => $row_zajezdy["od"], "do" => $row_zajezdy["do"], "id" => $id_zajezd);
                }
                $this->zajezdy[$id_zajezd][$row_zajezdy["id_cena"]] = array("id_cena"=>$row_zajezdy["id_cena"], "castka"=>$row_zajezdy["castka"]);
            }
          //  print_r($this->zajezdy);
            if($this->typ_terminu == "kombinovane"){
                $query_intervals = "                    
                    (SELECT distinct termin_od FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE id_serial=".$this->id_serial." and (`no_dates_generation` = 0 or `no_dates_generation` is null))

                    union distinct (
                    SELECT distinct termin_do FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE id_serial=".$this->id_serial." and termin_do_shift is null and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )
                        
                    union distinct (
                    SELECT distinct DATE_ADD(`termin_do`, INTERVAL `termin_do_shift` DAY) FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE id_serial=".$this->id_serial." and termin_do_shift is not null and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )    
                    
                    union distinct (
                    SELECT distinct termin_od FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object`)
                    WHERE id_serial=".$this->id_serial." and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )

                    union distinct (
                    SELECT distinct termin_do FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object`)
                    WHERE id_serial=".$this->id_serial." and termin_do_shift is null and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )

                    union distinct (
                    SELECT distinct DATE_ADD(`termin_do`, INTERVAL `termin_do_shift` DAY) FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object`)
                    WHERE id_serial=".$this->id_serial." and termin_do_shift is not null and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )   
                        
                    order by  termin_od
                    ";
            
                //echo $query_intervals;
                $data_intervals = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_intervals);
                $startDate = 0;
                $endDate = 0;
                $terminy = array();
                $terms = array();
                while ($row_intervals = mysqli_fetch_array($data_intervals)) {                
                    //platný termín
                    if($row_intervals["termin_od"] > Date("Y-m-d")){
                        $terms[] = $row_intervals["termin_od"];
                    }    
                }
                foreach ($terms as $t1) {
                    foreach ($terms as $t2) {
                        if($t1 <= $t2){
                           $terminy[] = array($t1,$t2);
                       }                
                    }
                }
            }else if($this->typ_terminu == "prime"){
                $query_intervals = "                    
                    (SELECT distinct termin_od, termin_do, 
                      0 as dateShift, termin_do as unshifted_termin_do, termin_od as shifted_termin_od 
                    FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`and `cena`.`id_kalkulacni_vzorec` = `cena_promenna`.`id_vzorec`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE id_serial=".$this->id_serial." and (`termin_do_shift` is null or `termin_do_shift` = 0 ) and (`no_dates_generation` = 0 or `no_dates_generation` is null))

                    union distinct (

                    SELECT distinct termin_od, DATE_ADD(`termin_do`, INTERVAL `termin_do_shift` DAY) as termin_do, 
                      `termin_do_shift` as dateShift, termin_do as unshifted_termin_do, DATE_ADD(`termin_od`, INTERVAL `termin_do_shift` DAY) as shifted_termin_od 
                    FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`and `cena`.`id_kalkulacni_vzorec` = `cena_promenna`.`id_vzorec`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE id_serial=".$this->id_serial." and termin_do_shift is not null and `termin_do_shift` != 0  and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )                                
                        
                    union distinct (
                    SELECT distinct termin_od, termin_do, 
                      0 as dateShift, termin_do as unshifted_termin_do, termin_od as shifted_termin_od 
                    FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`and `cena`.`id_kalkulacni_vzorec` = `cena_promenna`.`id_vzorec`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object`)
                    WHERE id_serial=".$this->id_serial." and (`termin_do_shift` is null or `termin_do_shift` = 0 ) and (`no_dates_generation` = 0 or `no_dates_generation` is null))

                    union distinct (
                    SELECT distinct termin_od, DATE_ADD(`termin_do`, INTERVAL `termin_do_shift` DAY) as termin_do, 
                      `termin_do_shift` as dateShift, termin_do as unshifted_termin_do, DATE_ADD(`termin_od`, INTERVAL `termin_do_shift` DAY) as shifted_termin_od 
                    FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`and `cena`.`id_kalkulacni_vzorec` = `cena_promenna`.`id_vzorec`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object`)
                    WHERE id_serial=".$this->id_serial." and termin_do_shift is not null and `termin_do_shift` != 0  and (`no_dates_generation` = 0 or `no_dates_generation` is null)
                        )  
                        
                         
                        
                    order by  termin_od, termin_do
                    ";
               //echo $query_intervals;
            
                //echo $query_intervals;
                $data_intervals = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_intervals);
                $terminy = array();
                while ($row_intervals = mysqli_fetch_array($data_intervals)) {                
                    //platný termín
                    if($row_intervals["termin_od"] >= Date("Y-m-d")){
                        $terminy[] = array($row_intervals["termin_od"],$row_intervals["termin_do"],$row_intervals["dateShift"],$row_intervals["unshifted_termin_do"]); 
                    }
                }              
            }
            /*
            while ($row_intervals = mysqli_fetch_array($data_intervals)) {
                $endDate = $row_intervals["termin_od"];
                //platný termín
                if($startDate!=0 and $endDate > Date("Y-m-d")){
                    $terminy[] = array($startDate,$endDate);
                }    
                $startDate = $endDate;
            }
                        
            $query_same_intervals = "SELECT distinct termin_od FROM   `cena` 
                    join `cena_promenna` on  (`cena`.`id_cena` = `cena_promenna`.`id_cena`)
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE id_serial=".$this->id_serial." and termin_od = termin_do  and (`no_dates_generation` = 0 or `no_dates_generation` is null)";
            $data_same_intervals = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_same_intervals);
            
            while ($row_same_intervals = mysqli_fetch_array($data_same_intervals)) {
                $terminy[] = array($row_same_intervals["termin_od"],$row_same_intervals["termin_od"]);
            }*/
            usort($terminy, function ($a, $b){
                if($a[0] < $b[0]){
                    return -1;
                }else if($a[0] > $b[0]){
                    return 1;
                }
                return ($a[1] < $b[1])?(-1):(1);
            });
            //print_r($terminy);
            $ceny = array();
            while($this->get_next_radek()){
                $ceny[$this->radek["id_cena"]] = array($this->radek["nazev_ceny"], $this->radek["kratky_nazev"], $this->radek["vzorec"], $this->radek["nazev_vzorce"], $this->radek["id_kalkulacni_vzorec"], $this->radek["kapacita_bez_omezeni"]);                
            }
            
          
            $action="?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=kalkulacni_vzorce_create_zajezdy";
            $output_table = "<form action=\"$action\" method=\"post\" ><table class=\"list\"><tr><th><i>Zaškrtnout vše: </i><input type=\"checkbox\" class=\"check_by_class\" id=\"checkbox_cena\" title=\"Zaškrtnout všechny služby a termíny\"> <br/>Termíny <th> Vytvoøit / Aktualizovat";
            $i = 1;
            foreach ($ceny as $id_cena => $cena_array) {
                if($cena_array[5]==1){
                    $kapacita_bez_omezeni = " / <span class=\"green\" title=\"Neomezená kapacita služby\">NEOMEZENÁ</span>";
                }else{
                    $kapacita_bez_omezeni = "";
                }
                $output_table .= "<th>
                        <input type=\"checkbox\" class=\"checkbox_cena check_by_class\" id=\"checkbox_cena_".$id_cena."\" title=\"zaškrtnout všechny termíny této služby\"> 
                        ".$cena_array[0]."<br/> <i style=\"font-weight:normal\">".$cena_array[3]."<br/> </i>
                        <input type=\"hidden\" name=\"id_cena_".$i."\" value=\"".$id_cena."\">    
                        <span style=\"font-weight:normal\">
                            <input type=\"text\" class=\"smallNumber\" name=\"kapacita_celkova_".$i."\" value=\"10\" title=\"Volná kapacita\"> Kapacita   $kapacita_bez_omezeni 
                        </span>    "; 
                $i++;
            }
            $index = 0;
            foreach ($terminy as $id => $termin) {
                $existing_zajezd = $this->get_existing_zajezdy_for_termin($termin);
                //echo "inside termin";
                if($existing_zajezd != null){
                    $note_zajezd = "<span class=\"green\" title=\"Zájezd s tímto termínem již byl vytvoøen - ceny budou editovány\"\">Již vytvoøen!</span>
                        <input type=\"hidden\" id=\"existujici_zajezd_".($index+1)."\" name=\"existujici_zajezd_".($index+1)."\" value=\"".$existing_zajezd["id"]."\" /> 
                        ";
                }else{
                    $note_zajezd = "";
                }
                $valid_cena = false; //indikator zda jsem vypocitali alespon jednu cenu
                //odkaz na smazani radku: <a href=\"#\" class=\"delete_row\" onclick=\"javascript:vygenerovane_terminy_delete_row('".($index+1)."');\" title=\"smazat øádek\"><img width=\"10\" src=\"./img/delete-cross.png\" alt=\"smazat øádek\" ></a>
                $output_row = "\n<tr class=\"termin_row\" id=\"termin_row_".($index+1)."\"><th>
                    $note_zajezd                     
                    <input class=\"date\" id=\"termin_od_".($index+1)."\" name=\"termin_od_".($index+1)."\" value=\"".$this->change_date_en_cz($termin[0])."\" /> 
                    - <input class=\"date\" id=\"termin_do_".($index+1)."\" name=\"termin_do_".($index+1)."\" value=\"".$this->change_date_en_cz($termin[1])."\" />
                     <th><input type=\"checkbox\" id=\"vytvorit_zajezd_".($index+1)."\" name=\"vytvorit_zajezd_".($index+1)."\" value=\"1\" checked=\"checked\" />       
                        ";
                $i_c = 0;
                foreach ($ceny as $id_cena => $cena_array) {
                    $i_c ++;
                    $note = "";
                    //top priorita je pouzit cenu TOK, pokud neexistuje, zkusim ji vypocitat z KV
                    $cena_tok = "";
                    $post_tok = "";
                    $externalIDInput = "";
                    //TODO
                    if($this->typ_terminu == "kombinovane"){
                        $query_tok = "                    
                            SELECT distinct `objekt_kategorie_termin`.`cena`, `objekt_kategorie_termin`.`id_termin`, `objekt_kategorie_termin`.`id_objekt_kategorie` FROM   `objekt_kategorie_termin` 
                            join `objekt_kategorie` on  (`objekt_kategorie_termin`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` )
                            join `cena_objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` and `cena_objekt_kategorie`.`id_cena` = ".$id_cena.")
                            
                            WHERE `cena_objekt_kategorie`.`use_cena_tok` = 1 and
                                `objekt_kategorie_termin`.`datetime_od` >= \"".$termin[0]."\" and
                                `objekt_kategorie_termin`.`datetime_do` <= \"".$termin[1]."\"                                                  
                            ";
                    }else if($this->typ_terminu == "prime"){
                        $query_tok = "  
                            SELECT distinct `objekt_kategorie_termin`.`cena`, `objekt_kategorie_termin`.`id_termin`, `objekt_kategorie_termin`.`id_objekt_kategorie` FROM   `objekt_kategorie_termin` 
                            join `objekt_kategorie` on  (`objekt_kategorie_termin`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` )
                            join `cena_objekt_kategorie` on (`cena_objekt_kategorie`.`id_objekt_kategorie` = `objekt_kategorie`.`id_objekt_kategorie` and `cena_objekt_kategorie`.`id_cena` = ".$id_cena.")
                            
                            WHERE `cena_objekt_kategorie`.`use_cena_tok` = 1 and
                                (`objekt_kategorie_termin`.`datetime_od` = \"".$termin[0]."\") and
                                (`objekt_kategorie_termin`.`datetime_do` = \"".$termin[1]."\" or `objekt_kategorie_termin`.`datetime_do` = \"".$termin[3]."\") 
                            ";
                        
                    }
                    //echo $query_tok;
                    //name=\"cena_".$id_cena."_".($index+1)."\"
                    $data_tok = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_tok);
                    while ($row_tok = mysqli_fetch_array($data_tok)) {                
                        //platný termín
                        $post_tok = "<input type=\"hidden\" name=\"id_tok_".($i_c)."_".$row_tok["id_objekt_kategorie"]."\" value=\"".$row_tok["id_termin"]."\" />
                                     <input type=\"hidden\" name=\"je_vstupenka_".($i_c)."\" value=\"1\" />  
                                    ";
                        $cena_tok =  $row_tok["cena"];
                    }
                    if($cena_tok !=""){
                        $value = $cena_tok;
                        $valid_cena = true;
                    }else if($cena_array[2] != ""){
                        $res = $this->evaluate_vzorec($id_cena, $termin[0],$termin[1], $termin[2], $cena_array[4], $cena_array[2]);
                        $value = $res[0];
                        $externalID = $res[1];
                        
                        if(is_numeric($value)){
                            $valid_cena = true;
                            if($externalID > 0){
                                $externalIDInput = "<input type=\"hidden\" name=\"externalID_".$id_cena."_".($index+1)."\" value=\"$externalID\" />";
                            }
                        }else{
                            $note = "<span class=\"red\">$value</span>";
                            $value = "";                            
                        }
                    }else{
                        $value = "";
                    }
                    
                    if($value!=""){
                        $puvodni_cena = "/<span title=\"Kalkulovaná cena pøed zaokrouhlením\"> KC: <span class=\"orange\" ><span class=\"kc_val\">".$value."</span> Kè</span></span> ";
                    }else{
                        $puvodni_cena = "";
                    }                      
                    
                    if($existing_zajezd != null and isset($existing_zajezd[$id_cena]["castka"])){
                        $zajezd_cena = "/<span title=\"Cena evidovaná na již existujícím zájezdu\"> ZC: <span class=\"blue\" ><span class=\"zc_val\">".$existing_zajezd[$id_cena]["castka"]."</span> Kè</span></span> ";
                        $value = $existing_zajezd[$id_cena]["castka"];
                    }else{
                        $zajezd_cena = "";
                    }
                      
                    
                    $output_row.= "<td class=\"cena_cell\" id=\"cena_cell_".$id_cena."\">"
                            . $externalIDInput
                            . "<input type=\"checkbox\" class=\"checkbox_cena checkbox_cena_".$id_cena."\" id=\"checkbox_cena_".$id_cena."_".($index+1)."\"> "
                            . $post_tok."<input class=\"bigNumber\" id=\"cena_".$id_cena."_".($index+1)."\" name=\"cena_".$id_cena."_".($index+1)."\" value=\"".$value."\" /> $note $puvodni_cena $zajezd_cena";                    
                }
                if($valid_cena){
                    $output_table .= $output_row;
                    $index++;
                }
            }
            
            
            echo $output_table."
                    <tr><th colspan=\"2\">Vytváøení TOK u nových zájezdù:</th>
                    <tr><td colspan=\"2\">
                        <input name=\"TOK_chovani\" type=\"radio\" value=\"nevytvaret\"/> Žádné TOK nevytváøet ani nepøiøazovat<br/>
                        <input name=\"TOK_chovani\" type=\"radio\" value=\"vzdy_vytvorit\"/> Vždy vytvoøit nový TOK, pokud je pøiøazena OK ke službì<br/>
                        <input name=\"TOK_chovani\" type=\"radio\" checked=\"checked\" value=\"vytvorit_nebo_priradit\"/> Pokud existuje TOK, pøiøadit; pokud TOK neexistuje, vytvoøit nový<br/>
                        <input name=\"TOK_chovani\" type=\"radio\" value=\"priradit\"/> Pokud existuje TOK, pøiøadit; pokud TOK neexistuje, nedìlat nic<br/>
                    </td>
                </table>
                <div class=\"submenu\">
                    <input type=\"submit\" value=\"Vytvoøit zájezdy\"> Zaškrtnuté ceny: 
                    <a href=\"#\" onclick=\"javascript:refresh_checked('.kc_val');\" title=\"Aktualizovat podle novì vypoètené ceny\" style=\"background-color: #36ff00;\">Aktualizovat dle KC</a> 
                    | <a href=\"#\" onclick=\"javascript:refresh_checked('.zc_val');\" title=\"Vrátit na døíve vypoètenou cenu\" style=\"background-color: #ff3400;\">Vrátit dle ZC</a> 
                    | <a href=\"#\" onclick=\"javascript:round_checked('10');\">Zaokrouhlit na desítky</a> 
                    | <a href=\"#\" onclick=\"javascript:round_checked('100');\">Zaokrouhlit na stovky</a> 
                    | <a href=\"#\" onclick=\"javascript:round_checked('90');\">Zaokrouhlit na devadesát</a>
                </div> 
                </form>
                ";
            
            
        }    

        function evaluate_vzorec($id_cena, $termin_od, $termin_do, $termin_do_shift, $id_vzorec, $vzorec){
            if($this->typ_terminu == "kombinovane"){
                $query_promenne = "SELECT `cena_promenna`.*,`cena_promenna_cenova_mapa`.*,`centralni_data`.`text` as `kurz`, 
                    (cast(termin_od = \"$termin_od\"  AS SIGNED INTEGER)
                        + cast(termin_do = \"$termin_do\" AS SIGNED INTEGER) 
                        + cast(`castka` is not null AS SIGNED INTEGER) 
                        + cast((`termin_do_shift` is null or `termin_do_shift`=0) AS SIGNED INTEGER)) 
                    as sanity_check   
                    FROM   `cena_promenna`
                    join `centralni_data` on (`cena_promenna`.`id_mena` = `centralni_data`.`id_data`)
                    left join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE cena_promenna.id_cena=$id_cena and id_vzorec=$id_vzorec and '$vzorec' LIKE CONCAT('%', `nazev_promenne` ,'%') 
                        and ((termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null and `termin_do_shift` is null )
                        or (termin_od <= \"$termin_od\" and termin_do >= \"$termin_do\" and `castka` is not null and `no_dates_generation` >= 1 )                         
                        or (termin_od >= \"$termin_od\" and termin_do <= \"$termin_do\" and `castka` is not null and `no_dates_generation` >= 1 ) 
                        or (termin_od <= \"$termin_od\" and termin_do >= DATE_ADD(\"$termin_do\", INTERVAL -(`termin_do_shift`) DAY) and `castka` is not null and `termin_do_shift` is not null)
                        or (termin_od is null and typ_promenne = \"const\" and `fixni_castka` is not null))
                order by sanity_check DESC, id_objektu DESC";
            }else if($this->typ_terminu == "prime"){
                $query_promenne = "SELECT `cena_promenna`.*,`cena_promenna_cenova_mapa`.*,`centralni_data`.`text` as `kurz`, 
                    (cast(termin_od = \"$termin_od\"  AS SIGNED INTEGER)
                        + cast(termin_do = \"$termin_do\" AS SIGNED INTEGER) 
                        + cast(`castka` is not null AS SIGNED INTEGER) 
                        + cast((`termin_do_shift` is null or `termin_do_shift`=0) AS SIGNED INTEGER)) 
                    as sanity_check    
                    FROM   `cena_promenna`
                    join `centralni_data` on (`cena_promenna`.`id_mena` = `centralni_data`.`id_data`)
                    left join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `cena_promenna`.`data_from_object` or `cena_promenna_cenova_mapa`.`id_cena_promenna` = `cena_promenna`.`id_cena_promenna`)
                    WHERE cena_promenna.id_cena=$id_cena and id_vzorec=$id_vzorec and '$vzorec' LIKE CONCAT('%', `nazev_promenne` ,'%') 
                        and ((termin_od = \"$termin_od\" and termin_do = \"$termin_do\" and `castka` is not null)
                        or (termin_od = \"$termin_od\" and termin_do = DATE_ADD(\"$termin_do\", INTERVAL -($termin_do_shift) DAY) and `castka` is not null)
                        or (termin_od = DATE_ADD(\"$termin_od\", INTERVAL 1 DAY) and termin_do = DATE_ADD(\"$termin_do\", INTERVAL -($termin_do_shift) DAY) and `castka` is not null)
                        or (termin_od = DATE_ADD(\"$termin_od\", INTERVAL 1 DAY) and termin_do = \"$termin_do\"  and `castka` is not null)
                        or (termin_od is null and typ_promenne = \"const\" and `fixni_castka` is not null))
                order by sanity_check DESC, id_objektu DESC";
            }
            //echo $query_promenne;
            
           
            //echo "<br/>$id_cena, $termin_od, $termin_do<br/>";
            //echo $query_promenne;
            $data_promenne = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_promenne);
            $instanciovany_vzorec = $vzorec;
            while ($row = mysqli_fetch_array($data_promenne)) {
                if($row["typ_promenne"]=="const"){
                    $replace = $row["fixni_castka"]*$row["kurz"];
                }else{
                    //cenova mapa
                    $replace = $row["castka"]*$row["kurz"];
                    
                    if(intval($row["external_id"])>0){
                        $externalID = intval($row["external_id"]);
                    }
                }
                
                //numerical external ids correspond with the ID of the hotel
                
                
                $instanciovany_vzorec = str_replace($row["nazev_promenne"], $replace, $instanciovany_vzorec);
            }
            if(preg_match("/[a-zA-Z]/", $instanciovany_vzorec)){
                return array("chyba promìnné",0);
            }else{
                return array(eval("return $instanciovany_vzorec;"),$externalID);
            }
            
            
        }
        
        //nacte veskera data tykajici se kalkulacnich vzorcu pro dany serial
        static function load_kv_data($id_serial){
            //do pole si dam zaznamy o vsech kalkulacnich vzorcich                
                $query_kv = "select * from  `kalkulacni_vzorec_definice` 
						where 1 
						order by `id_vzorec_def` ";
                $data_kv=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_kv);
                $vzorec_def = array();
                while ($row = mysqli_fetch_array($data_kv)) {
                    $vzorec_def[] = "vzorec_".$row["id_vzorec_def"].": \"".$row["vzorec"]."\"";
                    $arr = array();
                    $array_kv[$row["id_vzorec_def"]]=$row;
                    $seznam_promennych = explode(";", $row["seznam_promennych"]);
                    $seznam_typu = explode(";", $row["seznam_typu"]);
                    $default_values = explode(";", $row["default_values"]);
                    $bez_meny = explode(";", $row["bez_meny"]);
                    foreach ($seznam_promennych as $key => $promenna) {
                        if($promenna!=""){
                            $arr[] = "[".  implode(",", array("\"$promenna\"","\"".$seznam_typu[$key]."\"","\"".$default_values[$key]."\"","\"".$bez_meny[$key]."\""))."]";
                        }                        
                    }
                    $var_array = implode(",", $arr);
                    $vzorec[]= "vzorec_".$row["id_vzorec_def"].":[$var_array]";                    
                }
                
                $query_km= "SELECT * FROM `centralni_data` 
						WHERE `nazev` like \"%kalkulace_mena:%\"
						Order by `nazev`"; 
                $data_km=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_km);
                while ($row = mysqli_fetch_array($data_km)) {
                    $mena[] = "[".  implode(",", array($row["id_data"],"\"".str_replace("kalkulace_mena:","",$row["nazev"])."\"", "\"".$row["text"]."\"") )."]";
                }
                
                $query_objekty_letuska= "SELECT * FROM `objekt` 
                    join `objekt_serial` on (`objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and  `objekt_serial`.`id_serial` = ".$id_serial." )
                    WHERE `objekt`.`typ_objektu` = 5
                    Order by `objekt`.`nazev_objektu`"; 
                $data_ol=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_objekty_letuska);
                while ($row = mysqli_fetch_array($data_ol)) {
                    $objekty_letuska[] = "[".  implode(",", array($row["id_objektu"],"\"".$row["nazev_objektu"]."\"", "\"".$row["poznamka"]."\"") )."]";
                }
                
                $query_objekty_goglobal= "SELECT * FROM `objekt` 
                    join `objekt_serial` on (`objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and  `objekt_serial`.`id_serial` = ".$id_serial." )
                    WHERE `objekt`.`typ_objektu` = 6
                    Order by `objekt`.`nazev_objektu`"; 
                $data_ogg=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_objekty_goglobal);
                while ($row = mysqli_fetch_array($data_ogg)) {
                    $objekty_goglobal[] = "[".  implode(",", array($row["id_objektu"],"\"".$row["nazev_objektu"]."\"", "\"".$row["poznamka"]."\"") )."]";
                }
                
                
                $query_objekty_cenova_mapa= "SELECT `cena_promenna_cenova_mapa`.* FROM `objekt` 
                    join `objekt_serial` on (`objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and  `objekt_serial`.`id_serial` = ".$id_serial." )
                    join `cena_promenna_cenova_mapa` on (`cena_promenna_cenova_mapa`.`id_objektu` = `objekt`.`id_objektu` )
                    WHERE (`objekt`.`typ_objektu` = 5 or `objekt`.`typ_objektu` = 6) and termin_do >= \"".Date("Y-m-d")."\"
                    Order by `objekt`.`id_objektu`, termin_od, termin_do"; 
                
                //echo $query_objekty_cenova_mapa;
                $data_ocm=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_objekty_cenova_mapa);
                while ($row = mysqli_fetch_array($data_ocm)) {
                    if ($row["no_dates_generation"] == 1) {
                        $useDates = 0;
                    } else {
                        $useDates = 1;
                    }
                    $mapa_ocm[$row["id_objektu"]][] .= "[\"". CommonUtils::czechDate($row["termin_od"])."\",\"".CommonUtils::czechDate($row["termin_do"])."\",\"".$row["castka"]."\",\"".$row["external_id"]."\",\"".$row["poznamka"]."\",\"$useDates\",\"".$row["termin_do_shift"]."\"]";
                }
                if(sizeof((array)$mapa_ocm) >0){
                    foreach ($mapa_ocm as $key => $value) {
                        $cenove_mapy_objekt[] = $key.":[".implode(",",$value)."]";
                    }
                }
                //nactu defaultni hodnoty meny a kalkulacniho vzorce
                $query_default = "select `id_default_kalkulacni_mena`,`id_default_kalkulacni_vzorec` from  `serial` 
						where `id_serial`=".$id_serial."";                
                $data_default=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_default);                
                while ($row = mysqli_fetch_array($data_default)) {
                    $default_serial_kv=$row["id_default_kalkulacni_vzorec"];
                    $default_serial_mena=$row["id_default_kalkulacni_mena"];
                }                
                
                //nactu skutecne pouzite kalkulacni vzorce - pro editaci              
                $query_pouzite_kv = "select `id_kalkulacni_vzorec`, `id_cena` , id_default_kalkulacni_vzorec
                                                from  `cena` join serial on (`cena`.`id_serial` = `serial`.`id_serial`)
						where `cena`.`id_serial`=".$id_serial."
                                                order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` "; 
                $data_pouzite_kv =mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_pouzite_kv);        
                $id_kv_list = array();
                $kv_promenne_list = array();
                $cenove_mapy = array();
                while ($row = mysqli_fetch_array($data_pouzite_kv )) {
                    if($row["id_kalkulacni_vzorec"] <= 0){
                        $row["id_kalkulacni_vzorec"] = $row["id_default_kalkulacni_vzorec"];
                    }
                    $id_kv_list[] = "cena_".$row["id_cena"].":\"".$row["id_kalkulacni_vzorec"]."\"";
                    //nactu promenne pouzitych kv - dulezite pro editaci  
                    if($row["id_kalkulacni_vzorec"]>0){
                        $query_pouzite_kv_promenne = "select * from  `cena_promenna` 
                            where `id_cena`=".$row["id_cena"]." and `id_vzorec`=".$row["id_kalkulacni_vzorec"]."
                            "; 
                        $data_pouzite_kv_promenne =mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_pouzite_kv_promenne);
                        $prom = array();
                        while ($row_promenne = mysqli_fetch_array($data_pouzite_kv_promenne )) {
                            if($row_promenne["flight_direct"]!=1){
                                $row_promenne["flight_direct"] = 0;
                            }
                            $prom[] = "[\"".$row_promenne["nazev_promenne"]."\",\"".$row_promenne["typ_promenne"]."\",\"".$row_promenne["fixni_castka"]."\",\"".$row_promenne["id_mena"]."\",\"".$row_promenne["flight_from"]."\",\"".$row_promenne["flight_to"]."\",".$row_promenne["flight_direct"].",\"".$row_promenne["data_from_object"]."\"]";
                            if($row_promenne["typ_promenne"] == "timeMap" or $row_promenne["typ_promenne"] == "external" or $row_promenne["typ_promenne"] == "letuska"){
                                $query_promenne_timemap = "select * from  `cena_promenna_cenova_mapa` 
                                    where `id_cena_promenna`=".$row_promenne["id_cena_promenna"]." 
                                    order by `termin_od`, `termin_do`, `id_cena_promenna`
                                    ";
                                $mapa = array();
                                $data_promenne_timemap =mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_promenne_timemap);
                                while ($row_timemap = mysqli_fetch_array($data_promenne_timemap )) {
                                    if($row_timemap["no_dates_generation"]==1){
                                        $useDates = 0;
                                    }else{
                                        $useDates = 1;
                                    }
                                    $mapa[] = "[\"". CommonUtils::czechDate($row_timemap["termin_od"])."\",\"".CommonUtils::czechDate($row_timemap["termin_do"])."\",\"".$row_timemap["castka"]."\",\"".$row_timemap["external_id"]."\",\"".$row_timemap["poznamka"]."\",\"$useDates\",\"".$row_timemap["termin_do_shift"]."\"]";
                                }
                                $cenove_mapy[] = "timemap_cena_".$row["id_cena"]."_".$row["id_kalkulacni_vzorec"]."_".$row_promenne["nazev_promenne"].":[".implode(",",$mapa)."]";
                            }
                            
                        }
                        $kv_promenne_list[] = "vars_".$row["id_cena"]."_".$row["id_kalkulacni_vzorec"].":[".implode(",",$prom)."]";
                    }
                }          
		$scr = "";                                       
                if(sizeof((array)$objekty_letuska)>0){
			$scr_obj .= "
				var objekty_letuska = [
                            		".implode(",\n", $objekty_letuska)."
                        	];
			";
		}else{
			$scr_obj .= "
				var objekty_letuska = [];
			";
		}
		if(sizeof((array)$objekty_goglobal)>0){
			$scr_obj .= "
                        	var objekty_goglobal = [
                        	    ".implode(",\n", $objekty_goglobal)."
                        	];
			";
		}else{
			$scr_obj .= "
				var objekty_goglobal = [];
			";
		}
                if(sizeof((array)$cenove_mapy_objekt)>0){                    
                    $scr_ocm .= "
			var objekty_cenova_mapa = {
                            ".implode(",\n", $cenove_mapy_objekt)."
                        };
			";
                }else{
                    $scr_ocm .= "
			var objekty_cenova_mapa = {};
			";                    
                }
                $script = "<script type=\"text/javascript\">
                        var id_objektu = 'no';
			".$scr_obj."
                        ".$scr_ocm."
                        var vzorec_promenne = {
                            ".implode(",\n", $vzorec)."
                        };
                        var vzorec = {
                            ".implode(",\n", $vzorec_def)."
                        };
                        var meny = [
                            ".implode(",\n", $mena)."
                        ];
                        var id_kv_list = {
                            ".implode(",\n", $id_kv_list)."
                        };
                        var kv_promenne_list = {
                            ".implode(",\n", $kv_promenne_list)."
                        };
                        var cenove_mapy = {
                            ".implode(",\n", $cenove_mapy)."
                        };
                        var default_mena = \"".$default_serial_mena."\";
                        var mena_bez_prepoctu = \"".Cena_serial::MENA_BEZ_PREPOCTU."\";    
                    </script>
                    ";
                return array($script,$array_kv, $default_serial_kv);
        }
        
/**zobrazime formular*/
	function show_form_kalkulacni_vzorce(){
		//podle typu pozadavku vypisu spravny cil scriptu

                $action="?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=kalkulacni_vzorce_update";
                //tlacitka pro odesilani
                if( $this->legal("update") ){
                                $submit= "<input type=\"submit\" name=\"submit\" value=\"Uložit\" />
                                    <input type=\"submit\" name=\"submit\" value=\"Uložit a zavøít\" />
                                    <input type=\"submit\" name=\"submit\" value=\"Uložit a Generovat kombinované termíny\" />
                                    <input type=\"submit\" name=\"submit\" value=\"Uložit a Generovat pouze pøímé termíny\" />\n";
                }else{
                                $submit= "<strong class=\"red\">Nemáte dostateèné oprávnìní k editaci  cen seriálu</strong>\n";
                }			

		if($_GET["open_kv_cena"]>0){
                    $scriptOpenCena = "
                       <script type=\"text/javascript\">
                            $(document).ready(function () {
                                var id_ceny = $('.hidden_cena_id[value=\"".$_GET["open_kv_cena"]."\"]')[0].id;
                                id_ceny = id_ceny.replace(\"id_cena_\", \"\");
                                zpv(id_ceny);
                            });
                        </script> 
                    ";
                    
                }
		//hlavicka formulare
		$i=1;
               
                $vypis= $scriptOpenCena."                
                                        <form action=\"".$action."\" method=\"post\" />					
					<table  class=\"list\">
						<tr>
							<th>Id</th>                                                                                                               
							<th>Název ceny</th>
							<th>Použitý vzorec</th> 
                                                        <th>kopírovat vzorec ze služby</th>                                                                                                             
							<th>Nastavení promìnných</th>
						</tr>
						";
                
                
                $loaded_data = Cena_serial::load_kv_data($this->id_serial);
                $script = $loaded_data[0];
                $array_kv = $loaded_data[1];
                $default_serial_kv = $loaded_data[2];

                //zjistim zda uz existuje nejaky kalkulacni vzorec - tedy jestli jsem v rezimu edit nebo create
                $query_default = "select max(`id_kalkulacni_vzorec`) as max_id
                                        from  `cena` 
					where `id_serial`=".$this->id_serial."";                
                $type = "new";
                $data_default=mysqli_query($GLOBALS["core"]->database->db_spojeni,$query_default);
                while ($row = mysqli_fetch_array($data_default)) {
                    if($row["max_id"]>0 and $row["max_id"]!="NULL"){
                        $type = "edit";
                    }
                }
                
		while($this->get_next_radek()){                    
                    $kalkulacni_vzorce = Cena_serial::show_kalkulacni_vzorec_select($i,$array_kv, $default_serial_kv, $this->radek["id_kalkulacni_vzorec"], $type);
                    $select_copy_from_sluzby = $this->show_select_copy_from_sluzby($i);
                    $vypis=$vypis."
                        <tr>
                        <td class=\"nazev darkBlue1\">
                                ".$this->get_id_cena()."
                        </td>


                        <td class=\"nazev darkBlue1 darkBlue3\">
                                <input id=\"id_cena_".$i."\" class=\"hidden_cena_id\" name=\"id_cena_".$i."\" type=\"hidden\" value=\"".$this->get_id_cena()."\" />								 
                                ".$this->get_nazev_ceny()."                                                                   
                        </td>
                        <td class=\"nazev darkBlue1\">						 
                                 $kalkulacni_vzorce
                        </td> 
                        <td class=\"nazev darkBlue1\">						 
                                 $select_copy_from_sluzby
                        </td> 
                        <td class=\"nazev darkBlue1\">						 
                                 <input class=\"nastavit_vzorec\" id=\"nastavit_vzorec_$i\" name=\"nastavit_vzorec_".$i."\" type=\"button\" value=\"Nastavit kalkulaèní vzorec\" />
                        </td> 

                        </tr>
                        <tr>
                            <td colspan=\"5\" class=\"nastaveni_vzorce darkBlue2\"  id=\"nastaveni_vzorce_$i\">
                                
                            </td>
                        </tr>
                        ";
                    $i++;

                }
                $vypis=$script.$vypis."</table>".$submit."<input name=\"pocet\" type=\"hidden\" value=\"".$this->pocet."\" /> </form>";
                
                
                $termin_od = Date("Y-m-d", time() + (60*60*24*30));                
                $pocet_noci = 1;
                //generovani id hotelu podle jmena a destinace
                $deleteCMForm = "
                    <form action=\"?id_serial=".$this->id_serial."&amp;typ=cena&amp;pozadavek=kalkulacni_vzorce_deleteCM\" method=\"post\">
                    <table class=\"list\">
                        <tr>
                            <th colspan=\"4\"><h4>Smazání prošlých termínù cenových map</h4>
                        <tr>
                            <td>Smazat všechny termíny starší než:<input type=\"text\" name=\"termin_deleteCM\" value=\"".Date("d.m.Y")."\" />
                            <td><input type=\"submit\" value=\"odeslat\" />
                        </td>    
                    </table> 
                    </form>
                    ";
                
                $hotelsForm = "
                    <table class=\"list\">
                        <tr>
                            <th colspan=\"4\"><h4>Vyhledávání GoGlobal ID hotelù</h4>
                        <tr>
                            <td>Jméno hotelu: <input type=\"text\" id=\"goGlobalHotelName\" />
                            <td>Mìsto*: <input type=\"text\" id=\"goGlobalCity\" />
                            <td>Zemì*: <input type=\"text\" id=\"goGlobalCountry\" />
                            <td>Termín od: <input type=\"text\" id=\"goGlobalTerminOd\" value=\"".$termin_od."\"/>
                            <td>Poèet nocí: <input type=\"text\" id=\"goGlobalPocetNoci\" value=\"".$pocet_noci."\"/>
                            <td><a href=\"#\" id=\"update_goGlobalHotelName\">Vyhledat <img height=\"11\" src=\"/admin/img/ico-reload.png\" alt=\"Naèíst externí data\"/></a>
                            <td style=\"width:50%\">Výsledky: <div id=\"ext_hotel_ids\"></div>
                        </td>  
                        <tr>
                            <td colspan=\"4\">U mìsta je možné použít na zaèátku znak \"%\" jako náhradu za libovolný podøetìzec. Napøíklad \"%celona\" najde mìsto \"Barcelona\".
                    </table>    
                    ";
                
                return $vypis.$deleteCMForm.$hotelsForm;

	}       
        function show_select_copy_from_sluzby($index){
            $query = "select * from `cena` where `id_serial`= ".$this->id_serial." 
                        and `id_cena` != ".$this->get_id_cena()."
			order by `zakladni_cena` desc,`poradi_ceny`,`nazev_ceny` ";
            $sluzby = mysqli_query($GLOBALS["core"]->database->db_spojeni,$query);
            $result = "<select class=\"copy_vzorec_from_cena\" id=\"copy_vzorec_from_cena_".$this->get_id_cena()."_".$index."\">
                        <option value=\"\">---</option>
                        ";  
            while ($row = mysqli_fetch_array($sluzby)) {
                $result .= "<option value=\"".$row["id_cena"]."\">".$row["nazev_ceny"]."</option>\n";
            }
            $result .= "</select>";
            return $result;
        }
	static function show_kalkulacni_vzorec_select($i,$array_kv, $default_serial_kv, $sluzba_kv, $type){
            $out = "<select id=\"id_kalkulacni_vzorec_$i\" name=\"id_kalkulacni_vzorec_$i\" class=\"wide select_kalkulacni_vzorec\" onchange=\"javascript:changed_vzorec(this);\">
                    <option value=\"NULL\">--- Žádný ---</option>\n";
            foreach($array_kv as  $row_kv){
                if($row_kv["id_vzorec_def"] == $sluzba_kv){
                        $kv_selected=" selected=\"selected\" ";
                //defaultni serialove nastaveni pouziju pouze pokud jsem jeste zadne KV nenastavoval         
                }else if(($sluzba_kv<=0 or  $sluzba_kv=="NULL") and $type=="new" and $row_kv["id_vzorec_def"] == $default_serial_kv){
                        $kv_selected=" selected=\"selected\" ";
                }else{
                        $kv_selected=" ";
                }
                $out .= "<option value=\"".$row_kv["id_vzorec_def"]."\"".$kv_selected.">".$row_kv["nazev_vzorce"]."</option>\n";			
            }
            $out .= "</select>\n <span id=\"vzorec_params_$i\"></span>\n";
            return $out;
        }        
        
        
	/*metody pro pristup k parametrum*/
	function get_id_serial() { return $this->radek["id_serial"];}
	function get_id_cena() { return $this->radek["id_cena"];}
        function get_id_kv() { return $this->radek["id_kalkulacni_vzorec"];}
        function get_inserted_id_ceny() { return $this->id_ceny;}
        function get_id_objekt_kategorie() { return $this->radek["id_objekt_kategorie"];}
	function get_pocet() { return $this->pocet;}

        function get_nazev_objektu() { return $this->radek["nazev_objektu"];}
        function get_nazev_objekt_kategorie() { return $this->radek["nazev"];}
        
	function get_nazev_ceny() { 
            if($this->radek["nazev_ceny"]!=""){
                return $this->radek["nazev_ceny"];
            }else{
                return $this->radek["nazev"];
            }
         }
	function get_kratky_nazev() { return $this->radek["kratky_nazev"];}
	function get_nazev_ceny_en() { return $this->radek["nazev_ceny_en"];}
	function get_kratky_nazev_en() { return $this->radek["kratky_nazev_en"];}
        
        function get_odjezdove_misto() { return $this->radek["odjezdove_misto"];}
        function get_kod_letiste() { return $this->radek["kod_letiste"];}
        
	function get_zkraceny_vypis() { return $this->radek["zkraceny_vypis"];}		
	function get_typ_ceny() { return $this->radek["typ_ceny"];}
	function get_poradi_ceny() { return $this->radek["poradi_ceny"];}
	function get_zakladni_cena() { return $this->radek["zakladni_cena"];}
	function get_kapacita_bez_omezeni() { return $this->radek["kapacita_bez_omezeni"];}
	function get_use_pocet_noci() { return $this->radek["use_pocet_noci"];}	
	
        function get_typ_provize() { return $this->radek["typ_provize"];}
	function get_vyse_provize() { return $this->radek["vyse_provize"];}
        
	function get_id_user_create() { 
		//pokud uz id mame, vypiseme ho
		if($this->id_user_create != 0){
			return $this->id_user_create;
		//nemame id dokumentu (vytvarime ho)
		}else if($this->id_serial == 0){
			return $this->id_zamestnance;	
		}else{
			$data_id = mysqli_fetch_array( $this->database->query( $this->create_query("get_user_create") ) ); 
			$this->id_user_create = $data_id["id_user_create"];
			return $data_id["id_user_create"];
		}
	}	
} 

?>
