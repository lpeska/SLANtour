<?php
/** 
* trida pro zobrazeni seznamu serialu
*/
/*--------------------- SEZNAM SERIALU -----------------------------*/
/**jednodussi verze - vstupni parametry pouze typ, podtyp, zeme, zacatek vyberu a order by
    - odpovida dotazu z katalogu zajezdu
*/

class Serial_list_okenka extends Generic_list{
    //vstupni data
    protected $nazev;
    protected $doprava;
    protected $termin_od;
    protected $termin_do;
    protected $zacatek;
    protected $order_by;
    protected $vstupenka;
    protected $vstupenka_sport;
    protected $pocet_zaznamu;
    protected $pocet_radku;
    protected $pocet_zajezdu;

    protected $database; //trida pro odesilani dotazu
    protected $dokumenty;
    private $type;
    private $objects="";
    private $first=1;
    private $last_num;

    //------------------- KONSTRUKTOR  -----------------
    /**konstruktor podle specifikovaného filtru na typ, podtyp a zemi*/
    function __construct($nazev, $zeme_text, $id_serial, $ubytovani, $destinace, $nazev_serialu, $termin_od, $termin_do, $zacatek, $order_by, $pocet_zaznamu=POCET_ZAZNAMU, $typ_pozadavku="", $id_ubytovani=""){
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->nazev = $this->check($nazev); //odpovida poli nazev_typ_web
        $this->id_serial = $this->check_int($id_serial); //odpovida poli nazev_typ_web
        $this->zeme_text = $this->check($zeme_text);//odpovida poli nazev_typ_web
        $this->destinace = $this->check($destinace);//odpovida poli nazev_typ_web
        $this->nazev_serialu = $this->check($nazev_serialu);//odpovida poli nazev_typ_web
        $this->ubytovani = $this->check($ubytovani);//odpovida poli nazev_typ_web
        $this->id_ubytovani = $this->check($id_ubytovani);//odpovida poli nazev_typ_web

        $this->termin_od = $this->change_date_cz_en($this->check($termin_od));//odpovida poli nazev_zeme_web
        $this->termin_do = $this->change_date_cz_en($this->check($termin_do));//odpovida poli id_destinace
        $this->zacatek = $this->check($zacatek);

        if($order_by!="" and $typ_pozadavku == "select_slevy" ){
            $this->order_by = $this->check($order_by);
        }else{
            $this->order_by = $this->check($_GET["order_by"]);
        }
        
		  $this->use_slevy = $this->check($use_slevy); 
        $this->pocet_zaznamu = $this->check($pocet_zaznamu);
        $this->last_num = -1;
        //ziskani seznamu z databaze
        $this->get_vyprodane_serialy();
        if($typ_pozadavku == "select_serialy"){
           $this->data=$this->database->query( $this->create_query("select_serialy") )
                or $this->chyba("Chyba pøi dotazu do databáze");

        }else if($_GET["lev1"]=="zobrazit" or $typ_pozadavku == "select_seznam"){
           $this->data=$this->database->query( $this->create_query("select_seznam") )
                or $this->chyba("Chyba pøi dotazu do databáze");

        }else if($typ_pozadavku == "select_podtypy"){
           $this->data=$this->database->query( $this->create_query("select_podtypy") )
                or $this->chyba("Chyba pøi dotazu do databáze");

        }else if($typ_pozadavku == "select_slevy"){
           $this->data=$this->database->query( $this->create_query("select_slevy") )
                or $this->chyba("Chyba pøi dotazu do databáze");

        }else if($typ_pozadavku == "select_akce"){
           $this->data=$this->database->query( $this->create_query("select_akce") )
                or $this->chyba("Chyba pøi dotazu do databáze");
        }else{
            $this->data=$this->database->query( $this->create_query("select_hotely") )
                or $this->chyba("Chyba pøi dotazu do databáze");
        }
        //kontrola zda jsme ziskali nejake zajezdy
        if(mysqli_num_rows($this->data)==0){
            $this->chyba("Pro zadané podmínky neexistuje žádný zájezd");
        }
        $this->pocet_radku = mysqli_num_rows($this->data);
        $this->pocet_zajezdu = mysqli_num_rows($this->data);
    }
        function get_data(){
            return $this->data;
        }
	/** ziska vsechny dokumenty k danemu serialu*/
	function create_dokumenty(){
		$this->dokumenty= new Seznam_dokumentu_serial_list($this->nazev, $this->doprava, $this->termin_od, $this->termin_do);
	}
        function get_dokumenty() { return $this->dokumenty;}
    //------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu ze zadanych parametru*/
    function create_query($typ_pozadavku,$only_count=0){
        if($typ_pozadavku == "select_slevy" or $typ_pozadavku == "select_akce"  or $typ_pozadavku == "select_podtypy" or $typ_pozadavku == "select_seznam" or $typ_pozadavku == "select_hotely" or $typ_pozadavku == "select_serialy"){
            //definice jednotlivych casti dotazu
            if($this->nazev_serialu!=""){
                $where_nazev_serialu=" `serial`.`nazev_web` like '%".$this->nazev_serialu."%' and";
            }else{
                $where_nazev_serialu="";
            }
            if($this->nazev!=""){
                $where_nazev=" `serial`.`podtyp` like '%".$this->nazev."%' and";
            }else{
                $where_nazev="";
            }
            if($this->id_serial!=""){
                $where_id_serial=" `serial`.`id_serial` = ".$this->id_serial." and";
            }else{
                $where_id_serial="";
            }
            if($this->id_ubytovani!=""){
                $where_id_ubytovani=" `ubytovani`.`id_ubytovani` = ".$this->id_ubytovani." and";
            }else{
                $where_id_ubytovani="";
            }
            if($this->zeme_text!=""){
                $where_zeme=" `zeme`.`nazev_zeme_web` ='".$this->zeme_text."' and";
            }else{
                $where_zeme=" `zeme_serial`.`zakladni_zeme`=1 and";
            }
            if($this->ubytovani!=""){
                $where_ubytovani=" `ubytovani`.`nazev_web` ='".$this->ubytovani."' and";
            }else{
                $where_ubytovani="";
            }
            if($this->destinace!=""){
                $where_destinace=" `destinace_serial`.`id_destinace` ='".$this->destinace."' and";
            }else{
                $where_destinace=" ";
            }

            if($this->termin_od!="" and $this->termin_od!="0000-00-00"){
                $where_od=" (`zajezd`.`od` >='".$this->termin_od."' or (`zajezd`.`do` >'".$this->termin_od."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and";
            }else{
                $where_od=" (`zajezd`.`od` >='".Date("Y-m-d")."' or (`zajezd`.`do` >'".Date("Y-m-d")."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and";
            }
            if($this->termin_do!=""  and $this->termin_do!="0000-00-00"){
                $where_do=" (`zajezd`.`do` <='".$this->termin_do."' or (`zajezd`.`od` <'".$this->termin_do."' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and";

            }else{
                $where_do="";
            }

            if($this->zacatek!=""){//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
               // $limit=" limit ".$this->zacatek.",".$this->pocet_zaznamu." ";
            }else{
              //  $limit=" limit 0,".$this->pocet_zaznamu." ";
            }
            if($this->order_by!=""){
                $order=$this->order_by($this->order_by);
            }else{
                $order=" `zeme`.`nazev_zeme`, `destinace`.`nazev_destinace`";
            }
        }
        if($typ_pozadavku == "select_seznam"){
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if($only_count==1){
                $select="select count(`zajezd`.`id_zajezd`) as pocet";
                $limit="";
            	 $dotaz= $select."
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    ".$from_destinace."
                    where 1 and ".$where_id_serial.$where_nazev_serialu.$where_nazev.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    order by ".$order."
                     ".$limit."";
            }else {
                $select="select `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,
                            `ubytovani`.`nazev` as `nazev_ubytovani`, `ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                            `zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,
                            `cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `destinace`.`nazev_destinace`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            	 $dotaz= $select."
                    from `serial` join
                    `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
		    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
                    where `serial`.`id_typ` = 3 and ".$where_id_serial.$where_nazev_serialu.$where_nazev.$where_ubytovani.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    group by `zajezd`.`id_zajezd`
                    order by ".$order."
                     ".$limit."";
            }
            //echo $dotaz;
            return $dotaz;

        }else if($typ_pozadavku == "select_slevy"){
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
                $select="select `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,
                            `ubytovani`.`nazev` as `nazev_ubytovani`, `ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                            `zajezd`.*,
                            min(`cena_zajezd`.`vyprodano`) as `vyprodano`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            	 $dotaz= $select."
                    from `serial` join
                    `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`zakladni_cena`=1 or `cena`.`typ_ceny`=1 )) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
		    where `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and
                        ".$where_id_serial.$where_nazev_serialu.$where_nazev.$where_ubytovani.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    group by `zajezd`.`id_zajezd`
                    having `vyprodano` = 0
                    order by  ".$order."
                    limit  ".$this->pocet_zaznamu."";

            //echo $dotaz;
            return $dotaz;


        }else if($typ_pozadavku == "select_akce"){
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
                $select="select `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,
                            `ubytovani`.`nazev` as `nazev_ubytovani`, `ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                            `zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,`zajezd`.`hit_zajezd`,
                            min(`cena_zajezd`.`castka`) as `castka` ,coalesce(`cena_zajezd`.`mena`) as `mena`, max(`cena`.`typ_ceny`) as `typ_ceny`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`";
            	 $dotaz= $select."
                    from `serial` join
                    `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`zakladni_cena`=1 or `cena`.`typ_ceny`=2 )) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)

		    where `serial`.`id_typ` = 3 and
                        (`serial`.`podtyp` like \"%hit-serial%\" or
                         `zajezd`.`hit_zajezd` = 1 or
                         `cena`.`typ_ceny`=2)
                         and
                         `cena_zajezd`.`vyprodano` = 0 and
                         `cena_zajezd`.`na_dotaz` = 0 and

                        ".$where_id_serial.$where_nazev_serialu.$where_nazev.$where_ubytovani.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    group by `zajezd`.`id_zajezd`
                    order by  ".$order."
                    limit  ".$this->pocet_zaznamu."";

            //echo $dotaz;
            return $dotaz;

        }else if($typ_pozadavku == "select_podtypy"){
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy

                $select="select  distinct `serial`.`podtyp` as `podtyp`";
            	 $dotaz= $select."
                    from `serial` join
                    `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    where `serial`.`id_typ` = 3 and ".$where_nazev_serialu.$where_nazev.$where_ubytovani.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    ";
            //echo $dotaz;
            return $dotaz;
        }else if($typ_pozadavku == "select_hotely"){
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if($only_count==1){
                $select="select count(`zajezd`.`id_zajezd`) as pocet";
                $limit="";
            	 $dotaz= $select."
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    ".$from_destinace."
                    where 1 and ".$where_nazev_serialu.$where_nazev.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    order by  `serial`. `nazev`
                     ".$limit."";					 
            }else {
                $select="select distinct
                            `ubytovani`.`nazev` as `nazev_ubytovani`,`ubytovani`.`popisek`, `ubytovani`.`nazev_web`  as `nazev_ubytovani_web`,`ubytovani`.`highlights`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,`destinace`.`nazev_destinace`,
                            min(`cena_zajezd`.`castka`) as `castka`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            	 $dotaz= $select."
                    from `serial` join
                    `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
		    left join
                    (`foto_ubytovani` join
                        `foto` on (`foto_ubytovani`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_ubytovani`.`id_ubytovani` = `ubytovani`.`id_ubytovani` and `foto_ubytovani`.`zakladni_foto`=1)
                    where `serial`.`id_typ` = 3 and ".$where_nazev_serialu.$where_nazev.$where_ubytovani.$where_zeme.$where_destinace.$where_od.$where_do." 1
                    group by `ubytovani`.`id_ubytovani`
                    order by  ".$order."
                     ".$limit."";
            }

            //echo $dotaz;
            return $dotaz;

        }else if($typ_pozadavku == "select_serialy"){
             $select="select distinct `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`podtyp`, `serial`.`highlights`,
                            `ubytovani`.`nazev` as `nazev_ubytovani`, `ubytovani`.`nazev_web`  as `nazev_ubytovani_web`,
                            min(`cena_zajezd`.`castka`) as `castka`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            	 $dotaz= $select."
                    from `serial` join
                    `ubytovani` on (`serial`.`id_ubytovani` = `ubytovani`.`id_ubytovani`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` )
		    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
                    where `serial`.`id_typ` = 3 and ".$where_id_ubytovani.$where_id_serial.$where_nazev_serialu.$where_nazev.$where_ubytovani.$where_od.$where_do." 1
                    group by `serial`.`id_serial`
                    order by  `serial`. `nazev`
                     ".$limit."";
           // echo $dotaz;
            return $dotaz;
        }
    }



/**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
    function order_by($vstup) {
        switch ($vstup) {
            case "datum_up":
                return "`zajezd`.`od`,`zajezd`.`do`, `serial`.`nazev`";
                break;
            case "datum_down":
                return "`zajezd`.`od` desc,`zajezd`.`do` desc, `serial`.`nazev`";
                break;

            case "cena_up":
                return "`castka`,`zajezd`.`od`";
                break;

            case "cena_down":
                return "`castka` desc,`zajezd`.`od`";
                break;

            case "nazev":
                return "`ubytovani`.`nazev`";
                break;

            case "nazev_up":
                return "`ubytovani`.`nazev`,`serial`.`nazev`,`zajezd`.`od`";
                break;

            case "nazev_down":
                return "`ubytovani`.`nazev` desc,`serial`.`nazev` desc,`zajezd`.`od`";
                break;

            case "zeme_up":
                return "`zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`,`zajezd`.`od`";
                break;

            case "zeme_down":
                return "`zeme`.`nazev_zeme` desc,`destinace`.`nazev_destinace` desc,`zajezd`.`od`";
                break;

            case "random":
                return "RAND()";
                break;
        }
        //pokud zadan nespravny vstup, vratime zajezd.od
        return  "`zeme`.`nazev_zeme` ,`destinace`.`nazev_destinace` ,`zajezd`.`od`";
    }

                function show_ikony($typ_zobrazeni){

                    $zeme="";
                    if (is_file("strpix/typ_stravovani/".$this->radek["strava"].".png")){
                        //ikona typu dopravy
                        $zeme=$zeme." <img src=\"/strpix/typ_stravovani/".$this->radek["strava"].".png\" alt=\"".Serial_library::get_typ_stravy($this->radek["strava"]-1)."\" title=\"".Serial_library::get_typ_stravy($this->radek["strava"]-1)."\" height=\"12\" width=\"14\"/>";
                    }
                    if (is_file("strpix/typ_dopravy/".$this->radek["doprava"].".png")){
                        //ikona typu dopravy
                        $zeme=$zeme." <img src=\"/strpix/typ_dopravy/".$this->radek["doprava"].".png\" alt=\"".Serial_library::get_typ_dopravy($this->radek["doprava"]-1)."\" title=\"".Serial_library::get_typ_dopravy($this->radek["doprava"]-1)."\" height=\"12\" width=\"20\"/>";
                    }
                    if (is_file("strpix/typ_ubytovani/".$this->radek["ubytovani"].".png")){
                        //ikona typu ubytovani
                        $zeme=$zeme." <img src=\"/strpix/typ_ubytovani/".$this->radek["ubytovani"].".png\" alt=\"".Serial_library::get_typ_ubytovani($this->radek["ubytovani"]-1)."\" title=\"".Serial_library::get_typ_ubytovani($this->radek["ubytovani"]-1)."\" height=\"12\" width=\"14\"/>";
                    }
                    if (is_file("strpix/vlajky/".$this->radek["nazev_zeme_web"].".png")){
                        //vlajka zeme
                        $zeme=$zeme." <img src=\"/strpix/vlajky/".$this->radek["nazev_zeme_web"].".png\" alt=\"".$this->radek["nazev_zeme"]."\" height=\"12\" width=\"18\"/>";
                    }
                    return $zeme;
                }

/**zobrazi formular pro filtorvani vypisu serialu*/
	function show_filtr($typ_zobrazeni=""){

		//tvroba input datum rezervace od
		$input_datum_od="Odjezd od: <input name=\"termin_od\" type=\"text\" value=\"".($this->change_date_en_cz($this->termin_od)!="00.00.0000"?($this->change_date_en_cz($this->termin_od)):(""))."\" style=\"width:65px;height:14px;\" />
        		<script language=\"JavaScript\">
			     var o_cal = new tcal ({'formname': 'terminy', 'controlname': 'termin_od','selected' : '".($this->change_date_en_cz($this->termin_od)!="00.00.0000"?($this->change_date_en_cz($this->termin_od)):( Date("d.m.Y") ))."'});
			     o_cal.a_tpl.imgpath = '/calendar/img/';
			</script>
                        ";
		//tvroba input datum rezervace do
		$input_datum_do=" Pøíjezd do: <input name=\"termin_do\" type=\"text\" value=\"".($this->change_date_en_cz($this->termin_do)!="00.00.0000"?($this->change_date_en_cz($this->termin_do)):(""))."\" style=\"width:65px;height:14px;\" />
        		<script language=\"JavaScript\">
			     var o_cal = new tcal ({'formname': 'terminy', 'controlname': 'termin_do','selected' : '".($this->change_date_en_cz($this->termin_do)!="00.00.0000"?($this->change_date_en_cz($this->termin_do)):( Date("d.m.Y") ))."'});
			     o_cal.a_tpl.imgpath = '/calendar/img/';
			</script>
                        ";

		//tlacitko pro odeslani
		$submit= " <input type=\"submit\" value=\"Filtrovat\" />";

		//vysledny formular
                $promenne="";
                if($_GET["vstupenka"]!=""){
                     $promenne.="&vstupenka=".$_GET["vstupenka"];
                }
                if($typ_zobrazeni=="pod_nadpisem"){
                     $vystup="
                    <div>
			<form method=\"post\" name=\"terminy\" action=\"?update_filter=1".$promenne."\">
                            ".$input_datum_od.$input_datum_do.$submit."
			</form>
                    </div>
                    ";
                }else{
                    $vystup="
                    <div style=\"position:absolute;right:5px;top:10px;z-index:10;\">
			<form method=\"post\" name=\"terminy\" action=\"?update_filter=1".$promenne."\">
                            ".$input_datum_od.$input_datum_do.$submit."
			</form>
                    </div>
                    ";
                }
		return $vystup;
	}
/*component functions---------------------------------*/
                function javascriptObjectClick(){
                    if($this->type=="katalog"){
                       return "onmousedown=\"objectOpenedFromList(".$this->get_id_serial().");\"";
                    }else if($this->type=="search"){
                       return "onmousedown=\"objectOpenedFromDetailedSearch(".$this->get_id_serial().");\"";
                    }else if($this->type=="related"){
                       return "onmousedown=\"objectOpenedFromRelatedList(".$this->get_id_serial().");\"";
                    }else if($this->type=="recomended"){
                       return "onmousedown=\"objectOpenedFromRecomendedList(".$this->get_id_serial().");\"";
                    }else{
                       return "";
                    }
                }
                function javascriptObjectShown(){
                    if($this->type=="katalog"){
                       return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInList(\"".$this->objects."\");
                               </script>
                                ";
                    }else if($this->type=="search"){
                       return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInDetailedSearch(\"".$this->objects."\");
                               </script>
                                ";
                    }else if($this->type=="related"){
                       return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInRelatedList(\"".$this->objects."\");
                               </script>
                                ";
                    }else if($this->type=="recomended"){
                       return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInRecomendedList(\"".$this->objects."\");
                               </script>
                                ";
                    }else{
                       return "";
                    }
                }
                function setListType($listType){
                    $this->type = $listType;
                }

    /**zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
                function show_list_item($typ_zobrazeni){
                    if( in_array($this->radek["id_serial"], $this->vyprodane_serialy) ){
                        $vyprodano = "<span style=\"color:red;font-weight:bold;font-size:1.2em;\">Vyprodáno!</span> ";
                    }else if( in_array($this->radek["id_serial"], $this->na_dotaz_serialy) ){
                        $vyprodano = "<span style=\"color:blue;font-weight:bold;font-size:1.2em;\">Na Dotaz</span> ";

                    }else{
                        $vyprodano = "";
                    }
                    if( stripos($this->radek["podtyp"], "lecebne-pobyty")!==false){
                        $color=" class=\"violet\" ";
                    }else if( stripos($this->radek["podtyp"], "welness")!==false){
                        $color=" class=\"green\" ";
                    }else if( stripos($this->radek["podtyp"], "termalni-lazne")!==false or  stripos($this->radek["nazev_web"], "termalni-koupaliste")!==false){
                        $color=" class=\"red\" ";
                    }else {
                        $color=" class=\"blue\" ";
                    }
                    if($this->suda==1){
                            $suda=" class=\"suda\"";
                    }else{
                            $suda=" class=\"licha\"";
                    }
                    if($typ_zobrazeni=="termin_list_index"){
                        $vypis = "<tr $suda><td>".$vyprodano."<a ".$color." href=\"/zajezdy/".$this->get_nazev_web()."/".$this->get_doprava_web()."/".$this->get_id_zajezd()."\">
                                <strong>".$this->get_nazev()."</strong>, ".$this->get_doprava()."</a>
                                <td>".$this->change_date_en_cz_short( $this->get_termin_od() )." - ".$this->change_date_en_cz_short( $this->get_termin_do() )."
                                <td><strong style=\"color:#008000;font-size:1.2em;\">".($this->get_castka()+$this->get_cena_vstupenek())." ".$this->get_mena()."</strong>
                            </tr>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="termin_list"){//vypis zajezdu vcetne balicku a terminu
                        $vypis = "<tr $suda><td>".$vyprodano."<a ".$color." href=\"/zajezdy/zobrazit/".$this->get_nazev_ubytovani_web()."/".$this->get_id_zajezd()."\">
                                <strong>".$this->get_nazev()."</strong></a>
                                <td>".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() ).($this->get_dlouhodobe_zajezdy()?("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>"):(""))."
                                <td><span style=\"color:#00aa00;font-size:1.2em;\"><strong>".$this->get_castka()." ".$this->get_mena()."</strong></span>
                                <td><span style=\"font-weight:bold;color:#ca0000;font-size:1.2em;\">".$this->get_highlights()."</span>
                            </tr>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="termin_serial_list"){//vypis zajezdu vcetne balicku a terminu
                        $vypis = "<tr $suda><td>".$vyprodano."<a ".$color." href=\"/zajezdy/zobrazit/".$this->get_nazev_ubytovani_web()."/".$this->get_id_zajezd()."\">
                                ".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() )."</a>".($this->get_dlouhodobe_zajezdy()?("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>"):(""))."

                                <td><span style=\"color:#00aa00;font-size:1.2em;\"><strong>".$this->get_castka()." ".$this->get_mena()."</strong></span>
                                <td><span style=\"font-weight:bold;color:#ca0000;font-size:1.2em;\">".$this->get_highlights()."</span>
                            </tr>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="akce_list"){//vypis zajezdu vcetne balicku a terminu
                        if($this->radek["typ_ceny"] == 2){
                            $akce = "<span style=\"color:#00a120;font-weight:bold;font-size:1.2em;\">LAST MINUTE</span> ";
                        }else{
                            $akce = "<span style=\"color:blue;font-weight:bold;font-size:1.2em;\">HIT ZÁJEZD</span> ";
                        }

                        $vypis = "<li>".$akce."<a ".$color." href=\"/zajezdy/zobrazit/".$this->get_nazev_ubytovani_web()."/".$this->get_id_zajezd()."\">
                                <strong>".$this->get_nazev_ubytovani()." - ".$this->get_nazev()."</strong></a>
                                ; ".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() ).($this->get_dlouhodobe_zajezdy()?("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>"):(""))."
                                ; <span style=\"color:#00aa00;font-size:1.2em;\">cena od: <strong>".$this->get_castka()." ".$this->get_mena()."</strong></span>

                            </tr>
                            ";
                        return $vypis;
                    }else if($typ_zobrazeni=="slevy_list"){//vypis zajezdu vcetne balicku a terminu
                        if($this->get_foto_url()){
                            $foto = "<div class=\"round fleft\"><img src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->get_foto_url()."\" alt=\"".$this->get_nazev()."\" title=\"".$this->get_nazev()."\" /></div>";
                        }
                        $slevy = "<td  style=\"padding:5px;border-left:2px dashed grey;\">
                            ".$this->get_cena_pred_akci()."".$this->get_akcni_cena()."".$this->get_sleva()."
                            
                            <a  href=\"/zajezdy/zobrazit/".$this->get_nazev_ubytovani_web()."/".$this->get_id_zajezd()."\" class=\"button\">Zobrazit detaily</a>
                            </td>";
                        $popisek = "
                                    <p style=\"font-size:1.1em;padding:3px;\">
                                    Termín: ".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() ).($this->get_dlouhodobe_zajezdy()?("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>"):(""))."<br/>
                                    <b>".$this->get_popis_akce()."</b></p>
                                    
                        ";

                        $vypis = "
                            <div style=\"margin:5px;padding:5px;border:2px solid grey;background-color:#ffffc0;\">
                               <table>
                                <tr><td width=\"700\">
                                ".$foto."
                                <a  href=\"/zajezdy/zobrazit/".$this->get_nazev_ubytovani_web()."/".$this->get_id_zajezd()."\" >
                                    <h4 style=\"margin:0px;padding:0px;\"><em>".$this->get_nazev_ubytovani()." - ".$this->get_nazev()."</em></h4>
                                </a>
                                    ".$popisek."                                
                                    <div class=\"clear\">&nbsp;</div>
                                </td>
                                ".$slevy."
                                    </tr>
                                </table>
                            </div>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="hotel_list"){//vypis zajezdu vcetne balicku a terminu
                        $vypis = "<tr $suda><td>".$vyprodano."<a ".$color." href=\"/zajezdy/zobrazit/".$this->get_nazev_ubytovani_web()."\">
                                <strong>".$this->get_nazev_ubytovani()."</strong></a>
                                <td>".$this->get_nazev_zeme().", ".$this->get_nazev_destinace()."
                                <td style=\"text-align:right;\"><span style=\"font-weight:bold;color:#009a00;font-size:1.2em;\">".$this->get_castka()." Kè</span>
                            </tr>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="serial_list"){//vypis zajezdu vcetne balicku a terminu
                        if($this->get_foto_url()){
                            $foto = "<div class=\"round fleft\"><img src=\"https://www.slantour.cz/".ADRESAR_IKONA."/".$this->get_foto_url()."\" alt=\"".$this->get_nazev()."\" title=\"".$this->get_nazev()."\" /></div>";
                        }
                        $popisek = "
                                    <p style=\"font-size:1.1em;padding:3px;\">".$this->get_popisek_serialu()."</p>
                                    <a href=\"/zajezdy/zobrazit/".$_GET["lev2"]."/?id_serial=".$this->get_id_serial()."".$promenne."\" class=\"button\">Vybrat program</a>
                        ";

                        if($this->pocet_radku==1){
                           $grid="grid_12"  ;
                           $height="200";
                        }else if($this->pocet_radku==2){
                           $grid="grid_6"  ;
                           $height="200";
                        }else if($this->pocet_radku==3){
                           $grid="grid_4"  ;
                           $height="220";
                        }else{
                            $foto = "<div class=\"round\" style=\"width:180px\"><img width=\"180\" height=\"100\" src=\"https://www.slantour.cz/".ADRESAR_NAHLED."/".$this->get_foto_url()."\" alt=\"".$this->get_nazev()."\" title=\"".$this->get_nazev()."\" /></div>";
                            $grid="grid_3"  ;
                            $height="250";
                            $popisek = "
                                    <a href=\"/zajezdy/zobrazit/".$_GET["lev2"]."/?id_serial=".$this->get_id_serial()."".$promenne."\" class=\"button\" style=\"width:180px;text-aligh:center;\">Vybrat program</a>
                                    <p style=\"font-size:1.0em;padding:3px;\">".$this->get_popisek_serialu()."</p>
                            ";
                            $header = " font-size:1.1em;";
                        }
                        $vypis = "
                            <div class=\"".$grid."\" style=\"height:".$height."px;overflow:auto;\">
                                <div class=\"box\" style=\"margin:0px;padding:5px;\">
                                <h4 style=\"margin:0px;padding:0px;".$header."\"><em>".str_ireplace($this->get_nazev_ubytovani(), "", $this->get_nazev())."</em></h4>
                                   
                                    ".$foto.$popisek."
                                    <div class=\"clear\">&nbsp;</div>
                                </div>
                            </div>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="okenko_ubytovani"){//vypis zajezdu vcetne balicku a terminu

			if (strlen($this->get_popisek_serialu()) >= 100) {
                            $pozice_popisek = strpos($this->get_popisek_serialu(), '.', 100);
                        }else {
                            $pozice_popisek = strlen($this->get_popisek_serialu());
                        }
                        if ($pozice_popisek === false) {
                            $pozice_popisek = strlen($this->get_popisek_serialu());
                        }
                        $popisek_text = substr($this->get_popisek_serialu(), 0, ($pozice_popisek+1));

                        $popisek = "
                                <a href=\"/zajezdy/zobrazit/".$_GET["lev1"]."/".$_GET["lev2"]."/".$this->get_nazev_web()."\" class=\"button\">Vybrat program</a>
                                <p style=\"margin:0;padding:2px;\">".$popisek_text."</p>
                            ";
                            $foto = "<div class=\"round\" style=\"width:180px\"><img width=\"180\" height=\"100\" src=\"https://www.slantour.cz/".ADRESAR_NAHLED."/".$this->get_foto_url()."\" alt=\"".$this->get_nazev()."\" title=\"".$this->get_nazev()."\" /></div>";
                            $width="205"  ;
                            $height="240";
                            $header = " font-size:1.1em;";

                        $vypis = "
                            <div style=\"height:".$height."px;width:".$width."px;overflow:auto;float:left;background-color:#FFFCB7;margin:3px;\">
                                <div class=\"box\" style=\"margin:0px;padding:2px;\">
                                <h4 style=\"margin:0px;padding:0px;".$header."\"><em>".str_ireplace($this->get_nazev_ubytovani(), "", $this->get_nazev())."</em></h4>

                                    ".$foto.$popisek."
                                    <span style=\"font-weight:bold;color:#009a00;font-size:1.2em;\">Cena od: ".$this->get_castka()." Kè</span>
                                    <div class=\"clear\">&nbsp;</div>
                                </div>
                            </div>
                            ";
                        return $vypis;

                    }else if($typ_zobrazeni=="katalog"){
                        //component gathering info
                        if($this->first) {
                            $this->objects .= $this->get_id_serial();
                            $this->first=0;
                        }else {
                            $this->objects .= ",".$this->get_id_serial();
                        }

                        if($this->suda==1){
                            $vypis="<div class=\"suda\">";
                        }else{
                            $vypis="<div class=\"licha\">";
                        }
                        if($this->get_id_foto()!=""){
                            $foto =	"<img	src=\"/".ADRESAR_IKONA."/".$this->get_foto_url()."\"
                                    alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
                                    class=\"float_left\" width=\"120\" height=\"85\"/>";
                        }else{
                            $foto="";
                        }
								if($this->get_sleva_castka()!=""){
									$sleva="<strong>SLEVA AŽ</strong> <img alt=\"".$this->get_sleva_castka()." ".$this->get_sleva_mena()."\" title=\"".$this->get_sleva_nazev()."\" src=\"/slevy/".$this->get_sleva_castka()."m.gif\" style=\"margin-bottom:-3px;\"/>";
								}else{
									$sleva="";
								}
                        $core = Core::get_instance();
                        $adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
                        $adresa_katalog = $core->get_adress_modul_from_typ("katalog");
                        if( $adresa_katalog !== false ){//pokud existuje modul pro zpracovani


                            $vypis = $vypis."<div class=\"header\">\n
									 <div class=\"nazev_zajezdu\">". $this->get_nazev_zajezdu() ."&nbsp; ". $sleva ."&nbsp; </div>						 
									 <div class=\"zeme\">".$this->show_ikony($typ_zobrazeni)."</div>
                            <div class=\"nazev\"><a ".$this->javascriptObjectClick()." href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
                                    $this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\" class=\"normal\">".$this->get_nazev()."</a></div>\n</div>\n
                            <div class=\"contend\">
                                ".$foto."
                                <div class=\"popisek\">".$this->get_popisek()."</div>
                                <div>
                                    <div class=\"termin\">termín: <strong>".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() )."</strong></div>
                                    <div class=\"cena\">cena od: <strong>".$this->get_castka()." ".$this->get_mena()."</strong></div>";

                            if( $adresa_zobrazit !== false ){
                                $vypis = $vypis."<a ".$this->javascriptObjectClick()." href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
                                        $this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\">další informace</a>";
                            }

                            $vypis = $vypis."
                                </div>
                            </div>
                        </div>
                        ";
                            return $vypis;
                        }

                    }else if($typ_zobrazeni=="tipy_na_zajezd"){
                        //component gathering info
                        if($this->first) {
                            $this->objects .= $this->get_id_serial();
                            $this->first=0;
                        }else {
                            $this->objects .= ",".$this->get_id_serial();
                        }

                        if($this->suda==1){
                            $vypis="<div class=\"suda\">";
                        }else{
                            $vypis="<div class=\"licha\">";
                        }
                        if($this->get_id_foto()!=""){
                            $foto =	"<img	src=\"/".ADRESAR_MINIIKONA."/".$this->get_foto_url()."\"
                                    alt=\"".$this->get_nazev_foto()." - ".$this->get_popisek_foto()."\"
                                    class=\"float_left\" width=\"80\" height=\"55\"/>";
                        }else{
                            $foto="";
                        }

                        $core = Core::get_instance();
                        $adresa_zobrazit = $core->get_adress_modul_from_typ("zobrazit");
                        $adresa_katalog = $core->get_adress_modul_from_typ("katalog");
                        if( $adresa_katalog !== false ){//pokud existuje modul pro zpracovani
									if( strlen(strip_tags($this->get_popisek())) >= 220) {	
										$pozice_popisek = strpos(strip_tags($this->get_popisek()), ' ', 220);	
									}else{
										$pozice_popisek = strlen(strip_tags($this->get_popisek()));
									}
									if($pozice_popisek === false) {
										$pozice_popisek = strlen(strip_tags($this->get_popisek()));
									}
                            $vypis = $vypis."".$foto."
									 	  <div class=\"nazev_zajezdu\">".$this->get_nazev_zajezdu()."&nbsp; </div>
                                <div class=\"zeme\">".$this->show_ikony($typ_zobrazeni)."</div>
										  <div class=\"nazev\">".$this->get_nazev()."</div>\n
                                
										  <div class=\"popisek\">".substr(strip_tags($this->get_popisek()),0,$pozice_popisek)."...</div>
                                <div class=\"termin\"><strong>".$this->change_date_en_cz( $this->get_termin_od() )." - ".$this->change_date_en_cz( $this->get_termin_do() )."</strong></div>
                                <div class=\"cena\">cena od: <strong>".$this->get_castka()." ".$this->get_mena()."</strong></div>";

                            if( $adresa_zobrazit !== false ){
                                $vypis = $vypis."<a ".$this->javascriptObjectClick()." href=\"".$this->get_adress( array($adresa_zobrazit,$this->radek["nazev_typ_web"],
                                        $this->radek["nazev_zeme_web"],$this->radek["nazev_web"],$this->radek["id_zajezd"]) )."\">další informace</a>";
                            }

                            $vypis = $vypis."</div>
                        ";
                            return $vypis;
                        }

                    }else{
                        return "";
                    }
                }


    /**zobrazi odkazy na dalsi stranky vypisu zajezdu*/
                function show_strankovani(){
                    //prvni cislo stranky ktere zobrazime
                    $act_str=$this->zacatek-(10*$this->pocet_zaznamu);
                    if($act_str<0){
                        $act_str=0;
                    }
						  if($this->id_destinace!=""){
						  		$destinace = "&amp;destinace=".$this->id_destinace."";
						  }else{
						  		$destinace = "";
						  }

                    //odkaz na prvni stranku
                    $vypis = "<div class=\"strankovani\"><a href=\"?str=0".$destinace."\" title=\"první stránka zájezdù\">&lt;&lt;</a> &nbsp;";

                    //odkaz na dalsi stranky z rozsahu
                    while( ($act_str <= $this->pocet_zajezdu) and ($act_str <= $this->zacatek + (10*$this->pocet_zaznamu) ) ){
                        if($this->zacatek!=$act_str){
                            $vypis = $vypis."<a href=\"?str=".$act_str.$destinace."\" title=\"strana ".(1+($act_str/$this->pocet_zaznamu))."\">".(1+($act_str/$this->pocet_zaznamu))."</a> ";
                        }else{
                            $vypis = $vypis.(1+($act_str/$this->pocet_zaznamu))." ";
                        }
                        $act_str=$act_str+$this->pocet_zaznamu;
                    }

                    //odkaz na posledni stranku
                    $posl_str=$this->pocet_zaznamu*floor($this->pocet_zajezdu/$this->pocet_zaznamu);
                    $vypis = $vypis." &nbsp; <a href=\"?str=".$posl_str.$destinace."\" title=\"poslední stránka zájezdù\">&gt;&gt;</a></div>";

                    return $vypis;
                }

                function get_name_for_destinace(){
                    if($this->destinace){
                        $dotaz = "select `nazev_destinace` from `destinace` where `id_destinace`=".$this->destinace." limit 1";
                        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
                        if($zaznam = mysqli_fetch_array($data)){
                            return $zaznam["nazev_destinace"].", ";
                        }
                    }else{
                        return "";
                    }
                }
    /** vytvori text pro titulek stranky*/
                function show_titulek(){

                    //tvorba vypisu titulku
                    if($this->nazev!="" and $this->zeme_text!=""){
                        return $this->get_name_for_nazev()." | ".$this->get_name_for_destinace().$this->get_name_for_zeme()." | Láznì a Lázeòské pobyty s CK SLAN tour";
                    }else if($this->nazev!=""){
                        return $this->get_name_for_nazev()." | Lázeòské pobyty v Èeské republice, Slovensku a Maïarsku | CK SLAN tour";
                    }else if($this->zeme_text!=""){
                        return $this->get_name_for_destinace().$this->get_name_for_zeme()." | Láznì a Lázeòské pobyty | CK SLAN tour";
                    }else{
                        return "Láznì a lázeòské pobyty v Èeské republice, Slovensku a Maïarsku | CK SLAN tour";
                    }
                }

    /** vytvori text pro nadpis stranky*/
                function get_name_for_zeme($typ=""){
                       switch ($this->zeme_text) {
                        case "ceska-republika":
                            return "Èeská Republika";
                            break;
                        case "slovensko":
                            return "Slovensko";
                            break;
                        case "madarsko":
                            return "Maïarsko";
                            break;
                        default:
                            return $this->zeme_text;
                            break;
                        }                    
                }
                function get_name_for_nazev(){
                    switch ($this->nazev) {
                        case "relaxacni-pobyty":
                            return "Relaxaèní pobyty";
                            break;
                        case "lecebne-pobyty":
                            return "Léèebné pobyty";
                            break;
                        case "wellness-pobyty":
                            return "Wellness";
                            break;
                        case "termalni-lazne":
                            return "Termální láznì";
                            break;
                        case "termalni-koupaliste":
                            return "Termální koupalištì";
                            break;
                        case "seniorske-pobyty":
                            return "Seniorské pobyty";
                            break;
                        case "vikendove-pobyty":
                            return "Víkendové pobyty";
                            break;
                        case "specialni-balicky":
                            return "Speciální balíèky";
                            break;
                        default:
                            return $this->nazev;
                            break;
                    }
                }

    /** vytvori text pro meta keyword stranky*/
                function show_keyword(){
                    //tvorba vypisu titulku
                    if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
                        return $this->nazev_typ.", ".$this->nazev_zeme_cz.", Katalog zájezdù, zájezdy 2011";
                    }else if($this->nazev_typ!=""){
                        return $this->nazev_typ.", Katalog zájezdù, zájezdy 2011";
                    }else{
                        return "Katalog zájezdù, zájezdy 2011";
                    }
                }

    /** vytvori text pro meta description stranky*/
                function show_description(){
                    //tvorba vypisu titulku
                    if($this->nazev_typ!="" and $this->nazev_zeme_cz!=""){
                        return $this->nazev_typ.", ".$this->nazev_zeme_cz.", Katalog zájezdù pro rok 2011";
                    }else if($this->nazev_typ!=""){
                        return $this->nazev_typ.", Katalog zájezdù pro rok 2011";
                    }else{
                        return "Katalog zájezdù pro rok 2011";
                    }
                }


    /*metody pro pristup k parametrum*/
                function get_vyprodane_serialy(){
                 $this->vyprodane_serialy = array();
                 $this->na_dotaz_serialy = array();
                 $this->vyprodane_zajezdy = array();
                 $this->na_dotaz_zajezdy = array();
                 
                 $dotaz="select `serial`.`id_serial`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`na_dotaz`, `cena_zajezd`.`kapacita_volna`
            	    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`typ_ceny`=1 or `cena`.`typ_ceny`=2) ) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` )
                    where 1 
                    order by `serial`.`id_serial`,`zajezd`.`id_zajezd`";
                 $data=$this->database->query( $dotaz )
                    or $this->chyba("Chyba pøi dotazu do databáze");   
                    
                 $last_ser="";
                 $last_zaj = "";
                 $vyprodano = 0;
                 $na_dotaz = 0;
                 $vyprodano_zajezd = 0;
                 $na_dotaz_zajezd = 0;
                 while($zaznam = mysqli_fetch_array($data)){
                    //uprava poli
                    if($last_ser!=$zaznam["id_serial"]){
                        if($vyprodano){
                            $this->vyprodane_serialy[] = $last_ser;
                        }else if($na_dotaz){
                            $this->na_dotaz_serialy[] = $last_ser;
                        }
                        $vyprodano = 1;
                        $na_dotaz = 1;
                        $last_ser=$zaznam["id_serial"];
                    }
                    if($last_zaj!=$zaznam["id_zajezd"]){
                        if($vyprodano_zajezd){
                            $this->vyprodane_zajezdy[] = $last_zaj;
                        }else if($na_dotaz_zajezd){
                            $this->na_dotaz_zajezdy[] = $last_zaj;
                        }
                        $vyprodano_zajezd = 1;
                        $na_dotaz_zajezd = 1;
                        $last_zaj=$zaznam["id_zajezd"];
                    }

                    //kontrola, zda je neco nevyprodane
                    if($zaznam["vyprodano"]!=1){
                        $vyprodano = 0;
                        $vyprodano_zajezd = 0;
                    }
                    if($zaznam["na_dotaz"]!=1){
                        $na_dotaz = 0;
                        $na_dotaz_zajezd = 0;
                    }                    
                 }
                 if($last_ser!=$zaznam["id_serial"]){
                        if($vyprodano){
                            $this->vyprodane_serialy[] = $last_ser;
                        }else if($na_dotaz){
                            $this->na_dotaz_serialy[] = $last_ser;
                        }
                 }
                 if($last_zaj!=$zaznam["id_zajezd"]){
                        if($vyprodano_zajezd){
                            $this->vyprodane_zajezdy[] = $last_zaj;
                        }else if($na_dotaz_zajezd){
                            $this->na_dotaz_zajezdy[] = $last_zaj;
                        }
                    }
                    
                }

    /*metody pro pristup k parametrum*/
                function get_cena_vstupenek(){
                    require_once "./classes/zajezd_vstupenky.inc.php"; //seznam serialu
                    $vstup = new Seznam_vstupenek("v_cene", $this->radek["id_serial"]);
                    return $vstup->get_sum_cena();
                }
                function get_nazev_zajezdu() {
                    if($this->radek["nazev_zajezdu"]!=""){
                        return "<strong><i>".$this->radek["nazev_zajezdu"]."</i></strong> ";
                    }
                }
                function get_id_serial() { return $this->radek["id_serial"];}
                function get_nazev() { return $this->radek["nazev"];}
                function get_nazev_web() { return $this->radek["nazev_web"];}

                function get_nazev_ubytovani() { return $this->radek["nazev_ubytovani"];}
                function get_nazev_ubytovani_web() { return $this->radek["nazev_ubytovani_web"];}
                function get_popisek() { return $this->radek["popisek"];}
                function get_popisek_serialu() { return strip_tags($this->radek["popisek"]);}
                function get_dlouhodobe_zajezdy() { return $this->radek["dlouhodobe_zajezdy"];}
                function get_doprava() {
                    if($this->radek["doprava"]==1){ return "Vlastní dopravou";}
                        else if($this->radek["doprava"]==2){ return "Autokarem";}
                        else if($this->radek["doprava"]==3){ return "Letecky";}
                    }

                function get_doprava_web() {
                    if($this->radek["doprava"]==1){ return "vlastni-doprava";}
                        else if($this->radek["doprava"]==2){ return "autokarem";}
                        else if($this->radek["doprava"]==3){ return "letecky";}
                    }
                function get_id_zajezd() { return $this->radek["id_zajezd"];}
                function get_termin_od() { return $this->radek["od"];}
                function get_termin_do() { return $this->radek["do"];}
                function get_highlights() { return $this->radek["highlights"];}

                function get_cena_pred_akci() {
                    return
                        "<div >
                            pøed slevou: <span style=\"color:red;text-decoration:line-through;font-weight:bold;\">".
                        $this->radek["cena_pred_akci"]." Kè</span></div>";

                }
                function get_akcni_cena() {
                    return "<div style=\"padding-left:15px;\">cena od: <span style=\"color:#00ae35;text-decoration:none;font-size:1.6em;font-weight:bold;\">".
                        $this->radek["akcni_cena"]." Kè</span></div>";



                }
                function get_sleva() {
                        $sleva = round ( ( 1 - ($this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) )*100);

                    return  "<div style=\"color:red;font-size:1.8em;font-weight:bold;margin:4px;\" title=\" Sleva až ".$sleva."% \">
                        SLEVA ".$sleva."%</div>";

                }

                function get_castka() { return $this->radek["castka"];}
                function get_mena() { return $this->radek["mena"];}

                function get_nazev_zeme() {
                    if($this->radek["nazev_zeme"] == "Èeská republika" or $this->radek["nazev_zeme"] == "Èeská republika, víkendové pobyty"){
                        return "ÈR";
                    }else{
                        return $this->radek["nazev_zeme"];
                    }
                }
                function get_nazev_zeme_web() { return $this->radek["nazev_zeme_web"];}
                function get_nazev_destinace() { return $this->radek["nazev_destinace"];}

                function get_id_foto() { return $this->radek["id_foto"];}
                function get_foto_url() { return $this->radek["foto_url"];}
                function get_nazev_foto() { return $this->radek["nazev_foto"];}
                function get_popisek_foto() { return $this->radek["popisek_foto"];}
                function get_popis_akce() { return $this->radek["popis_akce"];}

                function get_typ() { return $this->radek["nazev_typ_web"];}
                function get_podtyp() { return $this->radek["nazev_podtyp_web"];}

					 function get_sleva_castka() { return $this->radek["sleva_castka"];}
                function get_sleva_mena() { return $this->radek["sleva_mena"];}
                function get_sleva_nazev() { return $this->radek["sleva_nazev"];}
					 
                function get_pocet_zajezdu(){ return $this->pocet_zajezdu;}
            }


            ?>
