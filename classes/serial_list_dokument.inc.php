<?php
/** 
* trida pro zobrazení seznamu dokumentù seriálu
*/
/*------------------- SEZNAM DOKUMENTU -------------------  */
/*rozsireni tridy Serial o seznam dokumentu*/
class Seznam_dokumentu_serial_list extends Seznam_dokumentu{
    protected $nazev;
    protected $doprava;
    protected $termin_od;
    protected $termin_do;
	protected $pocet_radku;
	
	public $database; //trida pro odesilani dotazu	

	//------------------- KONSTRUKTOR -----------------
	/**konstruktor tøídy na základì id serialu*/
	function __construct($nazev, $doprava, $termin_od, $termin_do){
		//trida pro odesilani dotazu
		$this->database = Database::get_instance();
				
        $this->nazev = $this->check($nazev); //odpovida poli nazev_typ_web
        $this->doprava = $this->check_int($doprava);//odpovida poli nazev_typ_web
        $this->termin_od = $this->check($termin_od);//odpovida poli nazev_zeme_web
        $this->termin_do = $this->check($termin_do);//odpovida poli id_destinace

	//ziskani zajezdu z databaze	
		$this->data=$this->database->query( $this->create_query() )
		 	or $this->chyba("Chyba pøi dotazu do databáze");
			
		$this->pocet_radku=mysqli_num_rows($this->data);
	}	
//------------------- METODY TRIDY -----------------	
	/**vytvoreni dotazu ze zadaneho id serialu*/
	function create_query(){
            if($this->nazev!=""){
                $where_nazev=" `serial`.`nazev_web` like '%".$this->nazev."%' and";
            }else{
                $where_nazev="";
            }
            if($this->doprava!=0){
                $where_doprava=" `serial`.`doprava` =".$this->doprava." and";
            }else{
                $where_doprava="";
            }
            if($this->termin_od!=""){
                $where_od=" `zajezd`.`od` >='".$this->termin_od."' and";
            }else{
                $where_od=" `zajezd`.`od` >='".Date("Y-m-d")."' and";
            }
            if($this->termin_do!=""){
                $where_do=" `zajezd`.`do` >='".$this->termin_do."' and";
            }else{
                $where_do="";
            }
		$dotaz ="select distinct
							`dokument`.`id_dokument`,`dokument`.`nazev_dokument`,
							`dokument`.`popisek_dokument`, `dokument`.`dokument_url` 
					from `serial` join
                                        `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
					`dokument_serial` on (`dokument_serial`.`id_serial` = `serial`.`id_serial`) join
					`dokument` on (`dokument`.`id_dokument` =`dokument_serial`.`id_dokument`) 
					where 1 and ".$where_nazev.$where_doprava.$where_od.$where_do." 1
                                        order by `dokument`.`id_dokument` ";
		//echo $dotaz;
		return $dotaz;
	}	

}



?>
