<?php
/**
 * trida pro zobrazeni seznamu serialu
 */
/* --------------------- SEZNAM SERIALU ----------------------------- */
/* * jednodussi verze - vstupni parametry pouze typ, podtyp, zeme, zacatek vyberu a order by
  - odpovida dotazu z katalogu zajezdu
 */

class Serial_list extends Generic_list {
    //vstupni data
    protected $nazev;
    protected $doprava;
    protected $termin_od;
    protected $termin_do;
    protected $cena_od;
    protected $cena_do;    
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
    private $objects = "";
    private $first = 1;
    private $last_num;
    private $global_od;
    private $global_do;
    private $global_cena_od;
    private $global_cena_do;    
    private $max_sleva_zajezd;
    private $description = array(
        "pobytove-zajezdy" => " - Chorvatsko, Itálie, Španìlsko, Francie - dovolená u moøe; pobyty v Èechách a na Slovensku",
        "poznavaci-zajezdy" => "",
        "lazenske-pobyty" => " - Èesko, Morava, Slovensko, Maïarsko, Nìmecko - láznì, wellness, termály, ubytování, pobyty pro seniory...",
        "za-sportem" => " - Premier League, Serie A, Formule 1, Moto GP, Tenisové turnaje, LOH 2016 Rio, zápasy Èeských národních týmù",
        "lyzovani" => " - Lyžování v Alpách ve Francii, Itálii a Rakousku",
        "jednodenni-zajezdy" => " - Drážïany, Passov, Tropický ostrov, Plavby lodí, Vídeò atd.",
        "pobyty-hory" => " - Ubytování a pobyty v Krkonoších, Jeseníkách, Beskydech, na Šumavì, Malé Fatøe, Vysokých èi Nízkých Tatrách...",
        "exotika" => " - Mexiko (Cancun), Bali, Filipíny, Spojené Arabské Emiráty (Dubai, Abu Dhabi)"
    );

    //------------------- KONSTRUKTOR  -----------------
    /*     * konstruktor podle specifikovaného filtru na typ, podtyp a zemi */
    function __construct($nazev, $zeme_text, $text, $ubytovani, $destinace, $nazev_serialu, $termin_od, $termin_do, $zacatek, $order_by, $pocet_zaznamu = POCET_ZAZNAMU, $typ_pozadavku = "", $id_serial = "") {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        $this->typ_pozadavku = $this->check($typ_pozadavku);
        //kontrola vstupnich dat
        $this->nazev = $this->check($nazev); //odpovida poli nazev_typ_web
        $this->text = $this->check($text); //odpovida poli text
        $this->zeme_text = $this->check($zeme_text); //odpovida poli nazev_typ_web
        $this->destinace = $this->check_int($destinace); //odpovida poli nazev_typ_web
        $this->nazev_serialu = $this->check($nazev_serialu); //odpovida poli nazev_typ_web
        $this->ubytovani = $this->check($ubytovani); //odpovida poli nazev_typ_web

        $this->termin_od = $this->change_date_cz_en($this->check($termin_od)); //odpovida poli nazev_zeme_web
        $this->termin_do = $this->change_date_cz_en($this->check($termin_do)); //odpovida poli id_destinace
        
        $this->cena_od = $this->check_int($_GET["cena_od"]); 
        $this->cena_do = $this->check_int($_GET["cena_do"]);     
        
        $this->zacatek = $this->check($zacatek);

        $this->id_serial = $this->check_int($id_serial);

        $this->order_by = $this->check($order_by);


        $this->use_slevy = $this->check($use_slevy);
        $this->pocet_zaznamu = $this->check($pocet_zaznamu);
        $this->last_num = -1;
        //ziskani seznamu z databaze
        $this->get_vyprodane_serialy();        

        //u nekterych vypisu chceme i celkovy pocet serialu
        if ($_GET["lev1"] == "zobrazit" or $typ_pozadavku == "select_seznam") {

            $data_pocet = $this->database->query($this->create_query("select_seznam", 1))
                    or $this->chyba("Chyba pøi dotazu do databáze");

            $zaznam_pocet = mysqli_fetch_array($data_pocet);
            $this->pocet_zajezdu = $zaznam_pocet["pocet"];

            $this->data = $this->database->query($this->create_query("select_seznam"))
                    or $this->chyba("Chyba pøi dotazu do databáze");
        } else if ($typ_pozadavku == "select_serialy_group") {
            $data_pocet = $this->database->query($this->create_query("select_serialy_group", 1))
                    or $this->chyba("Chyba pøi dotazu do databáze");

            $zaznam_pocet = mysqli_fetch_array($data_pocet);
            $this->pocet_zajezdu = $zaznam_pocet["pocet"];

            $this->data = $this->database->query($this->create_query("select_serialy_group"))
                    or $this->chyba("Chyba pøi dotazu do databáze");   
            
        } else if ($typ_pozadavku == "select_nove_zajezdy") {
            $this->data = $this->database->query($this->create_query("select_nove_zajezdy"))
                    or $this->chyba("Chyba pøi dotazu do databáze");  
            
        } else if ($typ_pozadavku == "select_ubytovani_group") {//3
            $data_pocet = $this->database->query($this->create_query("select_ubytovani_group", 1))
                    or $this->chyba("Chyba pøi dotazu do databáze");

            $zaznam_pocet = @mysqli_fetch_array($data_pocet);
            $this->pocet_zajezdu = $zaznam_pocet["pocet"];           

            $this->data = $this->database->query($this->create_query("select_ubytovani_group"))
                    or $this->chyba("Chyba pøi dotazu do databáze");
            
            //specialni zachazeni pro doporucovaci komponentu
        } else if ($typ_pozadavku == "recomended") {
            $dotaz = $this->create_query("recommended");
            $attributes = "";
            $userExprs = "";
            $user = ComponentCore::getUserId();

            $objectExprs = array(new ObjectExpression("", "ObjectRating", "3", "Aggregated", array("noOfObjects" => ($this->pocet_zaznamu + $this->zacatek) * 2, "implicitEventsList" => array("scroll", "onpageTime", "opened_vs_shown_fraction", "object_ordered"))),
                new ObjectExpression("", "ObjectRating", "1", "Dummy", array("noOfObjects" => ($this->pocet_zaznamu + $this->zacatek)))
            );

            $qHandler = new ComplexQueryHandler($dotaz, $attributes, $userExprs, $objectExprs);

            $qHandler->sendQuery();

            $this->serial_data = $qHandler->getQueryResponse();
            // print_r($this->serial_data);
        } else if ($typ_pozadavku == "recomended_item_related") {
            $dotaz = $this->create_query("recommended");
            //echo $dotaz;
            $attributes = "";
            $userExprs = "";
            $user = ComponentCore::getUserId();
            $objectExprs = array(new ObjectExpression("", "ObjectSimilarity", "5", "Item_item_CF", array("objectID" => $_GET["id_serial"], "noOfObjects" => ($this->pocet_zaznamu + $this->zacatek) * 2, "implicitEventsList" => array("scroll", "pageview", "onpageTime", "deep_pageview", "order"))),
                new ObjectExpression("", "ObjectRating", "1", "Dummy", array("noOfObjects" => ($this->pocet_zaznamu + $this->zacatek)))
            );

            $qHandler = new ComplexQueryHandler($dotaz, $attributes, $userExprs, $objectExprs);

            $qHandler->sendQuery();


            $this->serial_data = $qHandler->getQueryResponse();
            // print_r($this->serial_data);
        } else {//6
            $this->data = $this->database->query($this->create_query($typ_pozadavku))
                    or $this->chyba("Chyba pøi dotazu do databáze");
        }
        //kontrola zda jsme ziskali nejake zajezdy
        if(is_resource($this->data)){
        if (mysqli_num_rows($this->data) == 0) {
            $this->chyba("Pro zadané podmínky neexistuje žádný zájezd");
        }
        $this->pocet_radku = mysqli_num_rows($this->data);
        if (!$this->pocet_zajezdu) {
            $this->pocet_zajezdu = mysqli_num_rows($this->data);
        }
        }else{
            $this->pocet_radku = 0;            
        }
    }
    function get_pocet_radku(){
        return $this->pocet_radku;
    }
    function get_data() {
        return $this->data;
    }

    /** ziska vsechny dokumenty k danemu serialu */
    function create_dokumenty() {
        $this->dokumenty = new Seznam_dokumentu_serial_list($this->nazev, $this->doprava, $this->termin_od, $this->termin_do);
    }

    function get_dokumenty() {
        return $this->dokumenty;
    }

    //------------------- METODY TRIDY -----------------
    /*     * vytvoreni dotazu ze zadanych parametru */
    function create_query($typ_pozadavku, $only_count = 0) {
        //definice jednotlivych casti dotazu
        if ($this->nazev_serialu != "") {
            $where_nazev_serialu = " `serial`.`nazev_web` like '%" . $this->nazev_serialu . "%' and";
        } else {
            $where_nazev_serialu = "";
        }
        if ($this->id_serial != "") {
            $where_id_serial = " `serial`.`id_serial` = " . $this->id_serial . " and";
        } else {
            $where_id_serial = "";
        }
        if ($this->nazev != "") {
            $id_typ = 0;
            $sql = "select `id_typ` from `typ_serial` where `nazev_typ_web` like '%" . $this->nazev . "%' limit 1";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($zaznam = mysqli_fetch_array($data)) {
                $id_typ = $zaznam["id_typ"];
            }
            if($id_typ > 0){
               $where_typ = " `serial`.`id_typ` = " . $id_typ . " and"; 
            }else{
               $where_typ = "";
            }
            
        } else {
            $where_typ = "";
        }        
        if ($this->text != "") {
            $where_text = " (
                                `objekt_ubytovani`.`nazev_ubytovani`   like '%" . $this->text . "%' or
                                `serial`.`nazev`  like '%" . $this->text . "%' or
                                 `zajezd`.`nazev_zajezdu` like '%" . $this->text . "%' or  
                                `zeme`.`nazev_zeme`  like '%" . $this->text . "%' or
                                `destinace`.`nazev_destinace`  like '%" . $this->text . "%' 
                             ) and ";
        } else {
            $where_text = "";
        }
        if ($this->zeme_text != "") {
            $where_zeme = " `zeme`.`nazev_zeme_web` ='" . $this->zeme_text . "' and";
        } else {
            $where_zeme = " `zeme_serial`.`zakladni_zeme`=1 and";
        }

        if ($this->ubytovani != "") {
            $where_ubytovani = " `objekt_ubytovani`.`nazev_web`  ='" . $this->ubytovani . "' and";
        } else {
            $where_ubytovani = "";
        }
        if ($this->nazev_akce != "") {
            $where_nazev_akce = " (`serial`.`nazev` like '%" . $this->nazev_akce . "%'
                                    or `zajezd`.`nazev_zajezdu` like '%" . $this->nazev_akce . "%'
                                    or `objekt_ubytovani`.`nazev_ubytovani` ='" . $this->ubytovani . "') and";

            $this->global_nazev_akce = " (`serial`.`nazev` like '%" . $this->nazev_akce . "%'
                                    or `zajezd`.`nazev_zajezdu` like '%" . $this->nazev_akce . "%') and";
        } else {
            $this->global_nazev_akce = "";
        }

        if ($this->destinace != "") {
            $where_destinace = " `destinace_serial`.`id_destinace` ='" . $this->destinace . "' and";
        } else {
            $where_destinace = " ";
        }

        if ($this->termin_od != "" and $this->termin_od != "0000-00-00") {
            $where_od = " (`zajezd`.`od` >='" . $this->termin_od . "' or (`zajezd`.`do` >'" . $this->termin_od . "' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and";
        } else {
            $where_od = " (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and";
        }
        if ($this->termin_do != "" and $this->termin_do != "0000-00-00") {
            $where_do = " (`zajezd`.`do` <='" . $this->termin_do . "' or (`zajezd`.`od` <'" . $this->termin_do . "' and `serial`.`dlouhodobe_zajezdy`=1 ) ) and";
        } else {
            $where_do = "";
        }

        if ($this->cena_od >0 ) {
            $where_cena_od = " `max_castka` >=" . $this->cena_od . " and";
            $this->global_cena_od = " `cena_zajezd`.`castka` >=" . $this->cena_od . " and";
        } 
        if ($this->cena_do > 0 ) {
            $where_cena_do = " `min_castka` <=" . $this->cena_do . " and";
            $this->global_cena_do = " `cena_zajezd`.`castka` <=" . $this->cena_do . " and";
        }       
        
        $this->global_od = $where_od;
        $this->global_do = $where_do;

        if ($this->zacatek != "") {//pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
            $limit = " limit " . $this->zacatek . "," . $this->pocet_zaznamu . " ";
        } else {
            $limit = " limit 0," . $this->pocet_zaznamu . " ";
        }
        if ($this->order_by != "") {
            $order = $this->order_by($this->order_by);
            $group_order = $this->group_order_by($this->order_by);
        } else {
            $order = " `zajezd`.`od`, `zajezd`.`do`";
            $group_order = " `od`";
        }

        if ($typ_pozadavku == "select_seznam") {
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if ($only_count == 1) {
                $select = "select count(`zajezd`.`id_zajezd`) as pocet";
                $limit = "";
                $dotaz = $select . "
                    from `serial` left join
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    " . $from_destinace . "
                    where 1 and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_text . $where_nazev_serialu . $where_nazev . $where_typ . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                    
                     ";
            } else {
                $select = "select `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,`serial`.`id_sablony_zobrazeni`,
                            
                            `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,
                            
                            `zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,
                            `zajezd`.`cena_pred_akci`,`zajezd`.`akcni_cena`,
                            `cena_zajezd`.`castka`,`cena_zajezd`.`mena`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `destinace`.`nazev_destinace`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
                $dotaz = $select . "
                    from `serial` 
                    
                    left join
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   join
                        
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
                    where  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                    group by `zajezd`.`id_zajezd`
                    order by " . $order . "
                     " . $limit . "";
            }
            // echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_serialy_group") {            
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if ($only_count == 1) {
                $select = "select count( distinct `serial`.`id_serial`) as `pocet`, max(`cena_zajezd`.`castka`) as `max_castka`, min(`cena_zajezd`.`castka`) as `min_castka`";
                $limit = "";
                $dotaz = $select . "
                    from `serial` join
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
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   
                    left join (
			`slevy` 
			 join `slevy_serial` on (`slevy_serial`.`id_slevy` = `slevy`.`id_slevy`)
			) on (`slevy_serial`.`id_serial` = `serial`.`id_serial`						  			
			  and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_od` is null)
			  and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_do` is null) )
                     
			
                    				
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and 
                       " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_tipy . $where_od . $where_do . " 1
                    group by  `serial`.`id_serial`  
                    having " . $where_cena_od .$where_cena_do . " 1
                     ";
                //echo $dotaz;
            } else {
                $select = "select distinct
                            max(`slevy`.`castka`) as `sleva_castka`,`slevy`.`mena` as `sleva_mena`,`slevy`.`zkraceny_nazev`  as `sleva_nazev`,
                            max(1-(`zajezd`.`akcni_cena`/(`zajezd`.`cena_pred_akci`+1))) as `max_sleva`,
                            max(`cena_zajezd`.`castka`) as `max_castka`, min(`cena_zajezd`.`castka`) as `min_castka`,
                            min(`zajezd`.`od`) as `od`,`serial`.`id_sablony_zobrazeni`,
                            `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`dlouhodobe_zajezdy`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                          
                            `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,
                            
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
                $dotaz = $select . "
                    from `serial` join
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
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   
                    left join (
			`slevy` 
			 join `slevy_serial` on (`slevy_serial`.`id_slevy` = `slevy`.`id_slevy`)
			) on (`slevy_serial`.`id_serial` = `serial`.`id_serial`						  			
			  and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_od` is null)
			  and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_do` is null) )
                     
			
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                        " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_tipy . $where_od . $where_do . " 1
                    group by  `serial`.`id_serial`  
                    having " . $where_cena_od .$where_cena_do . " 1
                    order by " . $group_order . "
                     " . $limit . "";
            }
            //echo $dotaz;
            return $dotaz;
         } else if ($typ_pozadavku == "select_nove_zajezdy") {            
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
               $select = "select distinct        
                             max(`slevy`.`castka`) as `sleva_castka`,`slevy`.`mena` as `sleva_mena`,`slevy`.`zkraceny_nazev`  as `sleva_nazev`,	
                            `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`dlouhodobe_zajezdy`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,                            
                          `serial`.`id_sablony_zobrazeni`,
                            `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,
 
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
                $dotaz = $select . "
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    left join
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   
                    left join (
			`slevy` 
			 join `slevy_serial` on (`slevy_serial`.`id_slevy` = `slevy`.`id_slevy`)
			) on (`slevy_serial`.`id_serial` = `serial`.`id_serial`						  			
			  and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_od` is null)
			  and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_do` is null) )
                     
			
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                        " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_tipy . $where_od . $where_do . " 1
                    group by  `serial`.`id_serial`        
                    order by `zajezd`.`last_change` desc, `zajezd`.`id_zajezd` desc
                     " . $limit . "";
            
            //echo $dotaz;
            return $dotaz;
            
        } else if ($typ_pozadavku == "select_ubytovani_group") {
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy            
            if ($only_count == 1) {
                $select = "select count distinct(`ubytovani`.`id_ubytovani`) as pocet, max(`cena_zajezd`.`castka`) as `max_castka`, min(`cena_zajezd`.`castka`) as `min_castka`";
                $limit = "";
                $dotaz = $select . "
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join                    
                
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join 
                   join
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_tipy . $where_od . $where_do . " 1
                    group by  `objekt_ubytovani`.`id_objektu`  
                    having " . $where_cena_od .$where_cena_do . " 1                      
                     ";
            } else {
                $select = "select distinct
                            min(`zajezd`.`od`) as `od`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            max(`cena_zajezd`.`castka`) as `max_castka`, min(`cena_zajezd`.`castka`) as `min_castka`,
                        `serial`.`id_sablony_zobrazeni`,
                            `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani` as `nazev`, `objekt_ubytovani`.`nazev_web`,`objekt_ubytovani`.`popis_poloha` as `popisek`,

                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
                $dotaz = $select . "
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join                    
                 
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    join
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and
                        " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_tipy . $where_od . $where_do . " 1
                    group by  `objekt_ubytovani`.`id_objektu`  
                    having " . $where_cena_od .$where_cena_do . " 1                    
                    order by " . $group_order . "
                     " . $limit . "";
            }
           // echo "<!--".$dotaz."-->";
            return $dotaz;
        } else if ($typ_pozadavku == "recommended") {
            $select = "select `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,    `serial`.`id_sablony_zobrazeni`,                       
                            COALESCE(`zajezd`.`id_zajezd`) as `id_zajezd`,COALESCE(`zajezd`.`nazev_zajezdu`) as `nazev_zajezdu`,COALESCE(`zajezd`.`od`) as `od`,COALESCE(`zajezd`.`do`) as `do`, COALESCE(`zajezd`.`cena_pred_akci`) as `cena_pred_akci`, COALESCE(`zajezd`.`akcni_cena`) as `akcni_cena`, COALESCE(`zajezd`.`popis_akce`) as `popis_akce`,                            
                            `typ`.`nazev_typ_web`,
                           COALESCE(`cena_zajezd`.`castka`) as `castka`, COALESCE(`cena_zajezd`.`mena`) as `mena`,
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `destinace`.`nazev_destinace`,
                            
                             `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,
                            
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            $dotaz = $select . "
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`) join
                    `typ_serial` as `typ` on (`serial`.`id_typ` = `typ`.`id_typ`)
                    left join 
                     (`objekt_serial` join
                        `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                        `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                        ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)       
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
		    
                    left join
                    (`foto_serial` join
                        `foto` on (`foto_serial`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_serial`.`id_serial` = `serial`.`id_serial` and `foto_serial`.`zakladni_foto`=1)
		    where  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1
                            and " . $where_typ . $where_podtyp . $where_zeme . $where_tipy . $where_od . $where_do . " 1
                    group by `serial`.`id_serial`                                        
                    " . $limit . "";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ubytovani") {
            $dotaz = "
                    SELECT                             
                    `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani` as `nazev`, `objekt_ubytovani`.`nazev_web`,`objekt_ubytovani`.`popis_poloha` as `popisek`,

                    min(`cena_zajezd`.`castka`) as `castka`,
                            COALESCE(`zeme`.`nazev_zeme`) as `nazev_zeme`,  COALESCE(`zeme`.`nazev_zeme_web`)  as `nazev_zeme_web`, `foto`.`foto_url`
                        FROM `serial`
                        JOIN 
                            (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        join `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) 
                        join `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) 
                    
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        left join
                            (`foto_objekty` join
                                `foto` on (`foto_objekty`.`id_foto` = `foto`.`id_foto`) )
                            on (`foto_objekty`.`id_objektu` = `objekt`.`id_objektu` and `foto_objekty`.`zakladni_foto`=1)
                    
                        WHERE `serial`.`id_typ` = 3 and   `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_nazev_serialu . $where_text . $where_nazev . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                        GROUP BY `objekt_ubytovani`.`id_objektu` 
                        ORDER BY `objekt_ubytovani`.`nazev_ubytovani`            
                ";
            // echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_ubytovani_serial") {
            $dotaz = "
                    SELECT 
                            `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,
                            `serial`.`nazev`,`serial`.`id_sablony_zobrazeni`,`serial`.`nazev_web`,`foto`.`foto_url`, min(`cena_zajezd`.`castka`) as `castka`
                        FROM `serial`
                        JOIN 
                            (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        join `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) 
                        join `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) 
                                            
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        left join
                            (`foto_objekty` join
                                `foto` on (`foto_objekty`.`id_foto` = `foto`.`id_foto`) )
                             on (`foto_objekty`.`id_objektu` = `objekt`.`id_objektu` and `foto_objekty`.`zakladni_foto`=1)
                    
                        WHERE `serial`.`id_typ` = 3 and  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_nazev_serialu . $where_text . $where_nazev . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                        GROUP BY `nazev_ubytovani`,`serial`.`nazev`
                        ORDER BY `nazev_ubytovani`,`serial`.`nazev`               
                ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_serial_zajezd") {

            $dotaz = "
                    SELECT  distinct max(`slevy`.`castka`) as `sleva_castka`, max(`slevy_zaj`.`castka`) as `sleva_zajezd_castka`, 
                                `slevy`.`mena` as `sleva_mena`,`slevy`.`zkraceny_nazev`  as `sleva_nazev`,
                                `slevy_zaj`.`mena` as `sleva_zajezd_mena`,`slevy_zaj`.`zkraceny_nazev`  as `sleva_zajezd_nazev`,
                            `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,
                             `serial`.`nazev`,`serial`.`popisek`,`serial`.`nazev_web`,`serial`.`id_sablony_zobrazeni`,
                            `zajezd`.`od`,`zajezd`.`do`,`zajezd`.`id_zajezd`,
                            `zajezd`.`cena_pred_akci`,`zajezd`.`akcni_cena`,
                             `cena_zajezd`.`castka`
                        FROM `serial`
                        LEFT JOIN 
                            (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        join `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) 
                        join `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) 
                                            
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        
                        left join (
			`slevy` 
			 join `slevy_serial` on (`slevy_serial`.`id_slevy` = `slevy`.`id_slevy`)
			) on (`slevy_serial`.`id_serial` = `serial`.`id_serial`						  			
			  and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_od` is null)
			  and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_do` is null) )
                      
                       left join (
			`slevy` as  `slevy_zaj`
			 join `slevy_zajezd` on (`slevy_zajezd`.`id_slevy` = `slevy_zaj`.`id_slevy`)
			) on (`slevy_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`						  			
			  and (`slevy_zaj`.`platnost_od` = \"0000-00-00\" or `slevy_zaj`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy_zaj`.`platnost_od` is null)
			  and (`slevy_zaj`.`platnost_do` = \"0000-00-00\" or `slevy_zaj`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy_zaj`.`platnost_do` is null) )
                     
			   
			
                        WHERE `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and 
                        " . $where_text . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_od . $where_do . " 1       
                            group by `zajezd`.`id_zajezd`
                        ORDER BY `nazev_ubytovani`,`serial`.`nazev`,`zajezd`.`od`,`zajezd`.`do`               
                ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_vahy_ubytovani") {
            $datum_rezervace = (Date("Y") - 1) . "-" . Date("m") . "-" . Date("d");
            $dotaz = "
                    SELECT count( DISTINCT `id_objednavka` ) as `pocet` , 
                         `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani` as `nazev`, `objekt_ubytovani`.`nazev_web`,`objekt_ubytovani`.`popis_poloha` as `popisek`,

                        FROM `serial`
                        LEFT JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` )
                        JOIN 
                            (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        WHERE `serial`.`id_typ` = 3 and `objednavka`.`rezervace_do` > \"" . $datum_rezervace . "\" and " . $where_text . $where_nazev_serialu . $where_nazev . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                        GROUP BY  `objekt_ubytovani`.`id_objektu`
                        ORDER BY  `objekt_ubytovani`.`nazev_ubytovani`        
                ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_vahy") {
            $datum_rezervace = (Date("Y") - 1) . "-" . Date("m") . "-" . Date("d");
            $dotaz = "
                    SELECT sum( `celkova_cena` ) as `pocet` , `serial`.`nazev` , 
                        `objekt_ubytovani`.`nazev_ubytovani` , 
                        `serial`.`id_serial`, `serial`.`nazev_web`
                        FROM `serial`
                        JOIN `objednavka` ON ( `objednavka`.`id_serial` = `serial`.`id_serial` )
                        LEFT JOIN 
                            (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) 
                        JOIN `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                        JOIN `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) 
                        JOIN `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                        left join (
                            `destinace_serial`
                            join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                        )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                        WHERE `objednavka`.`rezervace_do` > \"" . $datum_rezervace . "\" and " . $where_text . $where_nazev_serialu . $where_nazev . $where_typ . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                        GROUP BY `serial`.`id_serial`
                        HAVING `pocet` > 100000
                        ORDER BY `serial`.`nazev`               
                ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_slevy") {
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            $select = "select distinct `serial`.`id_serial`,`serial`.`id_sablony_zobrazeni`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                            `serial`.`podtyp`, `serial`.`highlights`,
                            
                            `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,
                        
                            min(`cena_zajezd`.`vyprodano`) as `vyprodano`,
                            min(`zajezd`.`od`) as `od`,                             
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            $dotaz = $select . "
                    from `serial` left join
                           (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
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
		    where `objekt_ubytovani`.`id_objektu` is null and `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and 
                        " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                    group by  `serial`.`id_serial`
                    having `vyprodano` = 0
                    
                    limit  " . $this->pocet_zaznamu . "";

            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_slevy_ubytovani") {
            //jako select slevy, ale group by podle ubytovani
            $select = "select 
                         `objekt_ubytovani`.`nazev_ubytovani` as `nazev`, `objekt_ubytovani`.`nazev_web` ,`objekt_ubytovani`.`id_objektu` as `id_ubytovani`, 
                         
                            min(`cena_zajezd`.`vyprodano`) as `vyprodano`, max(`serial`.`dlouhodobe_zajezdy`) as `dlouhodobe_zajezdy`,
                            min(`zajezd`.`od`) as `od`,                             
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
            $dotaz = $select . "
                    from `serial` join
                           (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
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
		    where `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and 
                        " . $where_id_serial . $where_text . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_typ . $where_podtyp . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                    group by `objekt_ubytovani`.`id_objektu`
                    having `vyprodano` = 0
                    
                    limit  " . $this->pocet_zaznamu . "";

            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_podtypy") {
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy

            $select = "select  distinct `serial`.`podtyp` as `podtyp`";
            $dotaz = $select . "
                    from `serial` left join
                           (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`serial`.`id_serial` = `destinace_serial`.`id_serial`)
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_typ . $where_zeme . $where_destinace . " 1
                    ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_hotely") {
            //pokud chceme pouze spoèítat vsechny odpovídající záznamy
            if ($only_count == 1) {
                $select = "select count(`zajezd`.`id_zajezd`) as pocet";
                $limit = "";
                $dotaz = $select . "
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    " . $from_destinace . "
                    where 1 and  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_nazev_serialu . $where_nazev . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                    order by  `serial`. `nazev`
                     " . $limit . "";
            } else {
                $select = "select distinct
                            `objekt_ubytovani`.`id_objektu` as `id_ubytovani`,`objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek`,`objekt_ubytovani`.`highlights`,

                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`,`destinace`.`nazev_destinace`,
                            min(`cena_zajezd`.`castka`) as `castka`,
                            `foto`.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto`";
                $dotaz = $select . "
                    from `serial` join
                           (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
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
                    (`foto_objekty` join
                        `foto` on (`foto_objekty`.`id_foto` = `foto`.`id_foto`) )
                    on (`foto_objekty`.`id_objektu` = `objekt`.`id_objektu` and `foto_objekty`.`zakladni_foto`=1)
                    where `serial`.`id_typ` = 3 and  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_nazev_serialu . $where_nazev . $where_ubytovani . $where_zeme . $where_destinace . $where_od . $where_do . " 1
                    group by `objekt`.`id_objektu`
                    order by  " . $order . "
                     " . $limit . "";
            }

            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "zajezdy_serialu") {
            $select = "select distinct `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,
                            `cena_zajezd`.`castka`,
                            `zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,
                            `zajezd`.`cena_pred_akci`,`zajezd`.`akcni_cena`
                           ";
            $dotaz = $select . "
                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` )
		    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and " . $where_id_serial . $where_nazev_serialu . $where_nazev . $where_od . $where_do . " 1
                    order by  " . $order . "
                     " . $limit . "";
            //echo $dotaz;
            return $dotaz;
        }
    }

    function show_dotaz() {
        return $this->create_query("select_seznam");
    }

    /*     * na zaklade textoveho vstupu vytvori korektni cast retezce pro order by */

    function group_order_by($vstup) {
        switch ($vstup) {
            case "datum":
                return "`od`, `serial`.`nazev`";
                break;
            case "datum_up":
                return "`od`, `serial`.`nazev`";
                break;
            case "datum_down":
                return "`od` desc, `serial`.`nazev`";
                break;

            case "cena_up":
                return "`castka`";
                break;

            case "cena_down":
                return "`castka` desc";
                break;

            case "nazev":
                return "`serial`.`nazev`";
                break;

            case "nazev_up":
                return "`serial`.`nazev`,`od`";
                break;

            case "nazev_down":
                return "`serial`.`nazev` desc,`od`";
                break;

            case "zeme_up":
                return "`zeme`.`nazev_zeme`,`destinace`.`nazev_destinace`,`od`";
                break;

            case "zeme_down":
                return "`zeme`.`nazev_zeme` desc,`destinace`.`nazev_destinace` desc,`od`";
                break;

            case "random":
                return "RAND()";
                break;
            case "kluby";
                return "(`serial`.`nazev` like \"%" . $this->nazev_akce . "%\" ) desc, `od`";
        }
        //pokud zadan nespravny vstup, vratime zajezd.od
        return "`od`";
    }

    /*     * na zaklade textoveho vstupu vytvori korektni cast retezce pro order by */

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
                return "`objekt_ubytovani`.`nazev_ubytovani`,`serial`.`nazev`,`zajezd`.`od`";
                break;

            case "nazev_down":
                return "`objekt_ubytovani`.`nazev_ubytovani` desc,`serial`.`nazev` desc,`zajezd`.`od`";
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
        return "`zeme`.`nazev_zeme` ,`destinace`.`nazev_destinace` ,`zajezd`.`od`";
    }

    function show_ikony($typ_zobrazeni) {

        $zeme = "";
        if (is_file("strpix/typ_stravovani/" . $this->radek["strava"] . ".png")) {
            //ikona typu dopravy
            $zeme = $zeme . " <img style=\"border:none;margin-bottom:-3px;\" src=\"/strpix/typ_stravovani/" . $this->radek["strava"] . "-big.png\" alt=\"" . Serial_library::get_typ_stravy($this->radek["strava"] - 1) . "\" title=\"" . Serial_library::get_typ_stravy($this->radek["strava"] - 1) . "\" height=\"16\" />";
        }
        if (is_file("strpix/typ_dopravy/" . $this->radek["doprava"] . ".png")) {
            //ikona typu dopravy
            $zeme = $zeme . " <img style=\"border:none;margin-bottom:-3px;\" src=\"/strpix/typ_dopravy/" . $this->radek["doprava"] . "-big.png\" alt=\"" . Serial_library::get_typ_dopravy($this->radek["doprava"] - 1) . "\" title=\"" . Serial_library::get_typ_dopravy($this->radek["doprava"] - 1) . "\" height=\"16\" />";
        }
        if (is_file("strpix/typ_ubyt/" . $this->radek["ubytovani"] . ".png")) {
            //ikona typu ubytovani
            $zeme = $zeme . " <img style=\"border:none;margin-bottom:-3px;\" src=\"/strpix/typ_ubyt/" . $this->radek["ubytovani"] . "-big.png\" alt=\"" . Serial_library::get_typ_ubytovani($this->radek["ubytovani"] - 1) . "\" title=\"" . Serial_library::get_typ_ubytovani($this->radek["ubytovani"] - 1) . "\" height=\"16\" />";
        }
        if (is_file("strpix/vlajky/" . $this->radek["nazev_zeme_web"] . ".png")) {
            //vlajka zeme
            $zeme = $zeme . " <img style=\"margin-bottom:-3px;\" src=\"/strpix/vlajky/" . $this->radek["nazev_zeme_web"] . ".png\" alt=\"" . $this->radek["nazev_zeme"] . "\" height=\"12\" width=\"18\"/>";
        }
        return $zeme;
    }

    /*     * zobrazi formular pro filtorvani vypisu serialu */

    function show_filtr($typ_zobrazeni = "") {

        //tvroba input datum rezervace od
        $input_datum_od = "Odjezd od: <input id=\"termin_od\" name=\"termin_od\" type=\"text\" style=\"width:80px;\" value=\"" . ($_SESSION["termin_od"]) . "\"  />
        		
                        ";
        //tvroba input datum rezervace do
        $input_datum_do = " Pøíjezd do: <input id=\"termin_do\" name=\"termin_do\" type=\"text\" style=\"width:80px;\" value=\"" . ($_SESSION["termin_do"]) . "\"  />
        		
                        ";
        
        //tvroba input datum rezervace od
        $input_cena_od = "Cena od: <input id=\"cena_od\" name=\"cena_od\" type=\"text\" style=\"width:80px;\" value=\"" . ($_SESSION["cena_od"]) . "\"  />        		
                        ";
        //tvroba input datum rezervace do
        $input_cena_do = " Cena do: <input id=\"cena_do\" name=\"cena_do\" type=\"text\" style=\"width:80px;\" value=\"" . ($_SESSION["cena_do"]) . "\"  />        		
                        ";
        
        //tlacitko pro odeslani
        $text = "Název zájezdu/hotelu: <input id=\"keyword\" type=\"text\" name=\"text\" value=\"" . $this->text . "\" />";
        $submit = " <input type=\"submit\" value=\"Vyhledat\" />";

        $input_zeme = "  <select name=\"zeme-destinace\" >
                                <option value=\"\">---</option>
                                " . $this->show_zeme("option") . "
                            </select>
                        ";

        //vysledny formular

        if ($typ_zobrazeni == "pod_nadpisem") {
            $vystup = "
                    <div>
			<form method=\"post\" name=\"terminy\" action=\"?update_filter=1" . $promenne . "\">
                            " . $input_datum_od . $input_datum_do .$text . "<br/>".
                              $input_cena_od.$input_cena_do .  $submit . "
			</form>
                    </div>
                    ";
        } else if ($typ_zobrazeni == "pod_nadpisem_all") {
            $vystup = "
                    <div>
			<form method=\"post\" name=\"terminy\" action=\"?update_filter=1" . $promenne . "\">
                            Zemì: " . $input_zeme . " " . $text . "<br/>" . $input_datum_od . $input_datum_do . $submit . "
			</form>
                    </div>
                    ";
        } else if ($typ_zobrazeni == "vyhledavani") {

            $vystup = "  
                    <form method=\"post\" name=\"terminy\" action=\"/vyhledavani?update_filter=1" . $promenne . "\">
                        <fieldset>
                            Zájezd / Hotel: <input id=\"keyword\"  name=\"text\" class=\"field\"  value=\"" . $_SESSION["text"] . "\" />                           
                        </fieldset>
                        <fieldset>
                            Zemì: " . $input_zeme . "<br/>
                            " . $input_datum_od . $input_datum_do . "             
                        </fieldset>  
                        " . $submit . " 
                    </form>
                     ";
        } else {
            $vystup = "
                    <div style=\"position:absolute;right:5px;top:10px;z-index:10;\">
			<form method=\"post\" name=\"terminy\" action=\"?update_filter=1" . $promenne . "\">
                            " . $input_datum_od . $input_datum_do . $submit . "
			</form>
                    </div>
                    ";
        }
        return $vystup;
    }

    /* component functions--------------------------------- */

    function javascriptObjectClick() {
        if ($this->type == "katalog") {
            return "onmousedown=\"objectOpenedFromList(" . $this->get_id_serial() . ");\"";
        } else if ($this->type == "search") {
            return "onmousedown=\"objectOpenedFromDetailedSearch(" . $this->get_id_serial() . ");\"";
        } else if ($this->type == "related") {
            return "onmousedown=\"objectOpenedFromRelatedList(" . $this->get_id_serial() . ");\"";
        } else if ($this->type == "recomended") {
            return "onmousedown=\"objectOpenedFromRecomendedList(" . $this->get_id_serial() . ");\"";
        } else {
            return "";
        }
    }

    function javascriptObjectShown() {
        if ($this->type == "katalog") {
            return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInList(\"" . $this->objects . "\");
                               </script>
                                ";
        } else if ($this->type == "search") {
            return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInDetailedSearch(\"" . $this->objects . "\");
                               </script>
                                ";
        } else if ($this->type == "related") {
            return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInRelatedList(\"" . $this->objects . "\");
                               </script>
                                ";
        } else if ($this->type == "recomended") {
            return "<script  type=\"text/javascript\" language=\"javascript\">
                                        objectsShownInRecomendedList(\"" . $this->objects . "\");
                               </script>
                                ";
        } else {
            return "";
        }
    }

    function setListType($listType) {
        $this->type = $listType;
    }

//jiny zpusob jak dostat novy radek
    function get_next_radek_recommended() {
        //zmena parity radku
        if ($this->suda == 0) {
            $this->suda = 1;
        } else {
            $this->suda = 0;
        }
        //print_r($this->radek);
        return $this->radek = $this->serial_data->getNextRow();
    }

    /*     * zobrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni */

    function show_list_item($typ_zobrazeni, $text_max = "", $text_min = "", $text_ideal = "") {
        if (in_array($this->radek["id_serial"], $this->vyprodane_serialy)) {
            $vyprodano = "<span style=\"color:red;font-weight:bold;font-size:1.2em;\">Vyprodáno!</span> ";
        } else if (in_array($this->radek["id_serial"], $this->na_dotaz_serialy)) {
            $vyprodano = "<span style=\"color:blue;font-weight:bold;font-size:1.2em;\">Na Dotaz</span> ";
        } else {
            $vyprodano = "";
        }
        if (stripos($this->radek["podtyp"], "lecebne-pobyty") !== false) {
            $color = " class=\"violet\" ";
        } else if (stripos($this->radek["podtyp"], "welness") !== false) {
            $color = " class=\"green\" ";
        } else if (stripos($this->radek["podtyp"], "termalni-lazne") !== false or stripos($this->radek["nazev_web"], "termalni-koupaliste") !== false) {
            $color = " class=\"red\" ";
        } else {
            $color = " class=\"blue\" ";
        }
        if ($this->suda == 1) {
            $suda = " class=\"suda\"";
        } else {
            $suda = " class=\"licha\"";
        }
        if ($typ_zobrazeni == "termin_list_index") {
            $vypis = "<tr $suda><td>" . $vyprodano . "<a " . $color . " href=\"/zajezdy/" . $this->get_nazev_web() . "/" . $this->get_doprava_web() . "/" . $this->get_id_zajezd() . "\">
                                <strong>" . $this->get_nazev() . "</strong>, " . $this->get_doprava() . "</a>
                                <td>" . $this->change_date_en_cz_short($this->get_termin_od()) . " - " . $this->change_date_en_cz_short($this->get_termin_do()) . "
                                <td><strong style=\"color:#008000;font-size:1.2em;\">" . ($this->get_castka() + $this->get_cena_vstupenek()) . " " . $this->get_mena() . "</strong>
                            </tr>
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "termin_list") {//vypis zajezdu vcetne balicku a terminu
            $vypis = "<tr $suda><td>" . $vyprodano . "<a " . $color . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_ubytovani_web() . "/" . $this->get_id_zajezd() . "\">
                                <strong>" . $this->get_nazev() . "</strong></a>
                                <td>" . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "
                                <td><span style=\"color:#00aa00;font-size:1.2em;\"><strong>" . $this->get_castka() . " " . $this->get_mena() . "</strong></span>
                                <td><span style=\"font-weight:bold;color:#ca0000;font-size:1.2em;\">" . $this->get_highlights() . "</span>
                            </tr>
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "termin_serial_list") {//vypis zajezdu vcetne balicku a terminu
            $vypis = "<tr $suda><td>" . $vyprodano . "<a " . $color . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_ubytovani_web() . "/" . $this->get_id_zajezd() . "\">
                                " . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . "</a>" . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "

                                <td><span style=\"color:#00aa00;font-size:1.2em;\"><strong>" . $this->get_castka() . " " . $this->get_mena() . "</strong></span>
                                <td><span style=\"font-weight:bold;color:#ca0000;font-size:1.2em;\">" . $this->get_highlights() . "</span>
                            </tr>
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "ubytovani_vahy") {//vypis zajezdu vcetne balicku a terminu             
            $velikost = 0.8 + (6 * $this->radek["pocet"] / 100);
            if ($velikost > 3) {
                $velikost = 3;
            }
            $velikost = round($velikost, 2);
            if ($velikost > 2) {
                $bold = "font-weight:bold;";
            } else {
                $bold = "";
            }
            $vypis = "
                                <a href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" class=\"list_ubytovani\"  style=\"font-size:" . $velikost . "em;" . $bold . "\">" . $this->get_nazev() . "</a></span>, 
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "serial_vahy") {//vypis zajezdu vcetne balicku a terminu
            $velikost = 0.7 + ($this->radek["pocet"] / 1700000);
            if ($velikost > 2) {
                $velikost = 2;
            }
            $velikost = round($velikost, 2);
            if ($velikost >= 2) {
                $bold = "font-weight:bold;";
            } else {
                $bold = "";
            }
            $vypis = "
                                <a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" class=\"list_ubytovani\"  style=\"font-size:" . $velikost . "em; " . $bold . "\">" . $this->get_nazev() . "</a></span>, 
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "slevy_list_no_group") {//vypis zajezdu vcetne balicku a terminu
            //
                       
                      if ($this->get_foto_url()) {
                $foto = "                                
                                        <img height=\"45\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }

            $vypis = "
                            
                              <tr " . $class . " style=\"border-bottom:1px solid black;\">
                                <td >
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >
                                        $foto
                                    </a>
                                <td >    
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >
                                        <b><em>" . $this->get_nazev() . "</em></b>
                                    </a><br/>
                                     " . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "                                
                                <td width=\"100\" valign=\"top\">  
                                    " . $this->get_akcni_cena() . "<br/>
                                    " . $this->get_sleva() . "
                           
                          ";
            return $vypis;
        } else if ($typ_zobrazeni == "novinky_list") {//vypis zajezdu vcetne balicku a terminu
            //vcetne zgrupovanych terminu zajezdu
            $sql = "select `zajezd`.*,
                    max(`cena_zajezd`.`vyprodano`) as `vyprodano`,
                    min(`cena_zajezd`.`castka`) as `castka`

                    from
                    `serial` join
                    `zajezd` on ( `serial`.`id_serial` = `zajezd`.`id_serial` ) join                    
                    `cena_zajezd` on ( `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `cena` on ( `cena`.`id_cena` = `cena_zajezd`.`id_cena` and `cena`.`id_serial` = " . $this->get_id_serial() . " and (`cena`.`zakladni_cena`=1 ))
                    where `serial`.`id_serial` = " . $this->get_id_serial() . " and `zajezd`.`nezobrazovat_zajezd`<>1
                       and " . $this->global_od . $this->global_do . " 1
                    group by `zajezd`.`id_zajezd`
                    having `vyprodano` = 0
                    order by `od`, `do`
                    limit 20

                ";
            //echo $sql;
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $pocet = mysqli_num_rows($data);
            $sleva = 0;
            $min_cena = 10000000;
            $k = 0;
            $terminy = "";

            if ($pocet > 1) {
                $sleva_text = "až ";
                $cena_text = "od ";
                $show_cena_u_terminu = 1;
            } else {
                $sleva_text = "";
                $cena_text = "";
                $show_cena_u_terminu = 0;
            }
            while ($row = mysqli_fetch_array($data)) {
                $k++;
                if($row["akcni_cena"]>0 and $row["cena_pred_akci"] >0){
                    $act_sleva = round(( 1 - ($row["akcni_cena"] / $row["cena_pred_akci"]) ) * 100);
                    $cena = $row["akcni_cena"];
                }else{
                    $act_sleva = 0;
                    $cena = $row["castka"];
                }
                if ($min_cena > $cena) {
                    $min_cena = $cena;
                }
                if ($act_sleva > $sleva) {
                    $sleva = $act_sleva;
                }
                if ($k == 4) {
                    $terminy .= "<a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" title=\"Všechny termíny\">...</a>";
                } else if($k > 4){
                    //do nothing
                } else {
                    if($show_cena_u_terminu){
                        $act_sleva_text = ", <span style=\"color:green;\">" . $cena . " Kè</span><br/>";
                    }else{
                        $act_sleva_text = "";
                    }
                    
                    $terminy .= "&nbsp;&nbsp;<a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $row["id_zajezd"] . "\" >" . $this->change_date_en_cz($row["od"]) . " - " . $this->change_date_en_cz($row["do"]) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "</a>".$act_sleva_text ;
                }
            }

            if ($this->get_foto_url()) {
                $foto = "                                
                                        <img height=\"45\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }
            
            $sleva_celkova = "";           
            if($sleva>0){
               $sleva_celkova =  $this->get_sleva_param($sleva_text . $sleva);
            }
            //nechci aby se nedokonèené zájezdy zobrazovaly
            if($min_cena!=10000000){
            $vypis = "
                            
                              <tr " . $class . " style=\"border-bottom:1px solid black;\">
                                <td >
                                    <a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        $foto
                                    </a>
                                <td >    
                                    <a style=\"color:rgb(60,80,180);font-size:1.2em;\" href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        <b>" . $this->get_nazev() . "</b>
                                    </a><br/>
                                    " . $terminy . "
                                 <td width=\"100\" valign=\"top\">  
                                    " . $this->get_akcni_cena_param($cena_text . $min_cena) . "<br/>
                                    " . $sleva_celkova . "
                           
                          ";
                $this->max_sleva_zajezd = $sleva;
                return $vypis;
            }else{
                return "";
            }
            
            
            
        } else if ($typ_zobrazeni == "slevy_list") {//vypis zajezdu vcetne balicku a terminu
            //vcetne zgrupovanych terminu zajezdu
            $sql = "select `zajezd`.*,
                    max(`cena_zajezd`.`vyprodano`) as `vyprodano`
                    from
                    `serial` join
                    `zajezd` on ( `serial`.`id_serial` = `zajezd`.`id_serial` ) join                    
                    `cena_zajezd` on ( `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `cena` on ( `cena`.`id_cena` = `cena_zajezd`.`id_cena` and `cena`.`id_serial` = " . $this->get_id_serial() . " and (`cena`.`zakladni_cena`=1 or `cena`.`typ_ceny`=1 ))
                    where `serial`.`id_serial` = " . $this->get_id_serial() . " and `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and `zajezd`.`nezobrazovat_zajezd`<>1
                       and " . $this->global_od . $this->global_do . " 1
                    group by `zajezd`.`id_zajezd`
                    having `vyprodano` = 0
                    order by `od`, `do`
                    limit 20

                ";
            //echo $sql;
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $pocet = mysqli_num_rows($data);
            $sleva = 0;
            $min_cena = 10000000;
            $k = 0;
            $terminy = "";

            if ($pocet > 1) {
                $sleva_text = "až ";
                $cena_text = "od ";
                $show_cena_u_terminu = 1;
            } else {
                $sleva_text = "";
                $cena_text = "";
                $show_cena_u_terminu = 0;
            }
            while ($row = mysqli_fetch_array($data)) {
                $k++;
                $act_sleva = round(( 1 - ($row["akcni_cena"] / $row["cena_pred_akci"]) ) * 100);
                if ($min_cena > $row["akcni_cena"]) {
                    $min_cena = $row["akcni_cena"];
                }
                if ($act_sleva > $sleva) {
                    $sleva = $act_sleva;
                }
                if ($k == 4) {
                    $terminy .= "<a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" title=\"Všechny termíny\">...</a>";
                } else if($k > 4){
                    //do nothing
                } else {
                    $terminy .= "&nbsp;&nbsp;<a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $row["id_zajezd"] . "\" >" . $this->change_date_en_cz($row["od"]) . " - " . $this->change_date_en_cz($row["do"]) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "</a>" .
                            (($show_cena_u_terminu == 1) ? (", <span style=\"color:green;\">" . $row["akcni_cena"] . " Kè</span><br/>") : ("") );
                }
            }

            if ($this->get_foto_url()) {
                $foto = "                                
                                        <img height=\"45\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }

            $vypis = "
                            
                              <tr " . $class . " style=\"border-bottom:1px solid black;\">
                                <td >
                                    <a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        $foto
                                    </a>
                                <td >    
                                    <a style=\"color:rgb(60,80,180);font-size:1.2em;\" href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        <b>" . $this->get_nazev() . "</b>
                                    </a><br/>
                                    " . $terminy . "
                                 <td width=\"100\" valign=\"top\">  
                                    " . $this->get_akcni_cena_param($cena_text . $min_cena) . "<br/>
                                    " . $this->get_sleva_param($sleva_text . $sleva) . "
                           
                          ";
            $this->max_sleva_zajezd = $sleva;
            if($min_cena!=10000000){
                return $vypis;
            }else{
                return "";
            }
        } else if ($typ_zobrazeni == "slevy_list_ubytovani") {//vypis zajezdu vcetne balicku a terminu
            //vcetne zgrupovanych terminu zajezdu           
            $sql = "select `zajezd`.*, `serial`.`nazev` ,`serial`.`nazev_web`,                    
                    max(`cena_zajezd`.`vyprodano`) as `vyprodano`
                    from
                    `serial` join
                     (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                            
                    `zajezd` on ( `serial`.`id_serial` = `zajezd`.`id_serial` ) join                    
                    `cena_zajezd` on ( `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `cena` on ( `cena`.`id_cena` = `cena_zajezd`.`id_cena` and `cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`zakladni_cena`=1 or `cena`.`typ_ceny`=1 ))
                    where `objekt`.`id_objektu` = " . $this->get_id_ubytovani() . " and `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 
                       and " . $this->global_od . $this->global_do . " 1
                    group by `zajezd`.`id_zajezd`
                    having `vyprodano` = 0
                    order by `serial`.`nazev`,`od`, `do`
                    limit 10

                ";
            //echo $sql;
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $pocet = mysqli_num_rows($data);
            $sleva = 0;
            $min_cena = 10000000;
            $k = 0;
            $terminy = "";
            $last_serial = "";

            if ($pocet > 1) {
                $sleva_text = "až ";
                $cena_text = "od ";
                $show_cena_u_terminu = 1;
            } else {
                $sleva_text = "";
                $cena_text = "";
                $show_cena_u_terminu = 0;
            }
            while ($row = mysqli_fetch_array($data)) {
                $k++;
                $act_sleva = round(( 1 - ($row["akcni_cena"] / $row["cena_pred_akci"]) ) * 100);
                if ($min_cena > $row["akcni_cena"]) {
                    $min_cena = $row["akcni_cena"];
                }
                if ($act_sleva > $sleva) {
                    $sleva = $act_sleva;
                }
                if ($k == 3) {
                    $terminy .= "<a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" title=\"Všechny termíny\">...</a>";
                } else if($k > 3){
                    //do nothing
                } else {
                    if ($last_serial != $row["nazev"]) {
                        $last_serial = $row["nazev"];
                        $terminy .= "<b><i style=\"color:#757575;\">" . $row["nazev"] . "</i></b><br/>\n";
                    }
                    $terminy .= "&nbsp;&nbsp;<a href=\"/zajezdy/zobrazit/" . $row["nazev_web"] . "/" . $row["id_zajezd"] . "\" >" . $this->change_date_en_cz($row["od"]) . " - " . $this->change_date_en_cz($row["do"]) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "</a>" .
                            (($show_cena_u_terminu == 1) ? (", <span style=\"color:green;\">" . $row["akcni_cena"] . " Kè</span><br/>") : ("") );
                }
            }

            if ($this->get_foto_url()) {
                $foto = "                                
                                        <img height=\"45\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }

            $vypis = "
                            
                              <tr " . $class . " style=\"border-bottom:1px solid black;\">
                                <td >
                                    <a href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" >
                                        $foto
                                    </a>
                                <td >    
                                    <a style=\"color:rgb(60,80,180);font-size:1.2em;\" href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" >
                                        <b>" . $this->get_nazev() . "</b>
                                    </a><br/>
                                    " . $terminy . "
                                 <td width=\"100\" valign=\"top\">  
                                    " . $this->get_akcni_cena_param($cena_text . $min_cena) . "<br/>
                                    " . $this->get_sleva_param($sleva_text . $sleva) . "
                           
                          ";
            $this->max_sleva_zajezd = $sleva;
            return $vypis;
        } else if ($typ_zobrazeni == "katalog_ubytovani_list") {//vypis zajezdu vcetne balicku a terminu
            $text_base = strip_tags($this->get_popisek());
            $konec_textu = strpos($text_base, ".", 80);
            $konec_textu2 = strpos($text_base, "!", 80);
            $konec_textu3 = strpos($text_base, "?", 80);
            $konec_textu4 = strpos($text_base, " ", 80);

            if ($konec_textu < 100 and $konec_textu >= 80) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < 100 and $konec_textu2 >= 80) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < 100 and $konec_textu3 >= 80) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < 100 and $konec_textu4 >= 80) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, (90)) . "...";
            }

            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }

            $vypis = "
                            
                              <tr " . $class . " style=\"border-bottom:1px solid black;\">
                               
                                <td >    
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >
                                        <b style=\"font-size:1.2em;\"><em>" . $this->get_nazev() . "</em></b>
                                    </a>" . $text . "<br/>
                                     " . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "                                
                                 
                                    <span style=\"color:red;font-weight:bold;\">cena od: " . $this->get_castka() . " Kè</span>
                           
                          ";
            return $vypis;
        } else if ($typ_zobrazeni == "katalog_list") {//vypis zajezdu vcetne balicku a terminu
            if ($text_max == "") {
                $text_max = 180;
            }
            if ($text_min == "") {
                $text_min = 150;
            }
            if ($text_ideal == "") {
                $text_ideal = 160;
            }
            $text_base = strip_tags($this->get_popisek() . " \n" . $this->get_popisek_ubytovani());
            $konec_textu = strpos($text_base, ".", $text_min);
            $konec_textu2 = strpos($text_base, "!", $text_min);
            $konec_textu3 = strpos($text_base, "?", $text_min);
            $konec_textu4 = strpos($text_base, " ", $text_min);

            if ($konec_textu < $text_max and $konec_textu >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < $text_max and $konec_textu2 >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < $text_max and $konec_textu3 >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < $text_max and $konec_textu4 >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, ($text_ideal)) . "...";
            }


            if ($this->get_foto_url()) {
                $foto = "                                
                                        <img height=\"40\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }
            if ($this->radek["cena_pred_akci"] and $this->radek["akcni_cena"]) {
                $sleva = " <b style=\"color:red;\">SLEVA " . round((1 - $this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) * 100) . "%</b>";
            } else {
                $sleva = "";
            }
            $vypis = "
                            
                              <tr " . $class . " style=\"border-bottom:1px solid black;\">
                                <td>
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >
                                        $foto
                                    </a>
                                <td >    
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >
                                        <b><em>" . $this->get_nazev_ubytovani() . " - " . $this->get_nazev() . "</em></b>
                                    </a><br/>" . $text . $sleva . "<br/>
                                     " . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . ($this->get_dlouhodobe_zajezdy() ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : ("")) . "                                
                                 
                                   <b style=\"color:green;\">cena od: " . $this->get_castka() . " Kè</b>
                           
                          ";
            return $vypis;
        } else if ($typ_zobrazeni == "katalog_list_group") {//vypis zajezdu vcetne balicku a terminu
            //zacatek vyberu a vypisu terminu                        
            if ($this->typ_pozadavku == "select_ubytovani_group") {
                if ($_GET["all"] == $this->get_id_ubytovani()) {
                    $showall = true;
                    $limit = "limit 100";
                } else {
                    $limit = "limit 50";
                }
                
                  $sql="                 max(`cena_zajezd`.`vyprodano`) as `vyprodano`
                    from
                    `serial` join
                     (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                            
                    `zajezd` on ( `serial`.`id_serial` = `zajezd`.`id_serial` ) join                    
                    `cena_zajezd` on ( `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `cena` on ( `cena`.`id_cena` = `cena_zajezd`.`id_cena` and `cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`zakladni_cena`=1 or `cena`.`typ_ceny`=1 ))
                    where `objekt`.`id_objektu` = " . $this->get_id_ubytovani() . " and `zajezd`.`cena_pred_akci` > 0 and `zajezd`.`akcni_cena`>0 and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 
                       and " . $this->global_od . $this->global_do .  $this->global_cena_od . $this->global_cena_do . " 1
                    group by `zajezd`.`id_zajezd`
                    having `vyprodano` = 0
                    order by `serial`.`nazev`,`od`, `do`
                    limit 10";
                //  echo $sql;
                
                $sql = "select `zajezd`.*, `serial`.`nazev`, `serial`.`nazev_web`,`serial`.`dlouhodobe_zajezdy`,
                    max(`cena_zajezd`.`vyprodano`) as `vyprodano`,
                     GROUP_CONCAT(DISTINCT `slevy`.`castka` ORDER BY `slevy`.`castka` DESC SEPARATOR ',') as `sleva_castka`,GROUP_CONCAT(DISTINCT `slevy`.`mena` ORDER BY `slevy`.`castka` DESC SEPARATOR ',') as `sleva_mena`,GROUP_CONCAT(DISTINCT `slevy`.`zkraceny_nazev` ORDER BY `slevy`.`castka` DESC SEPARATOR ';_')  as `sleva_zkraceny_nazev`,
                     GROUP_CONCAT(DISTINCT `slevy_k_zajezdu`.`castka` ORDER BY `slevy_k_zajezdu`.`castka` DESC SEPARATOR ',') as `sleva_castka_zajezd` ,GROUP_CONCAT(DISTINCT `slevy_k_zajezdu`.`mena` ORDER BY `slevy_k_zajezdu`.`castka` DESC SEPARATOR ',') as `sleva_mena_zajezd`,GROUP_CONCAT(DISTINCT `slevy_k_zajezdu`.`zkraceny_nazev` ORDER BY `slevy_k_zajezdu`.`castka` DESC SEPARATOR ';_') as `sleva_zkraceny_nazev_zajezd`,
                     `cena_zajezd`.`castka`
                    from
                    `serial` join
                     (`objekt_serial` join
                            `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                            `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`) join
                            
                    `zajezd` on ( `serial`.`id_serial` = `zajezd`.`id_serial` ) join                    
                    `cena_zajezd` on ( `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `cena` on ( `cena`.`id_cena` = `cena_zajezd`.`id_cena` and `cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1)
                    left join (
			`slevy` as `slevy_k_zajezdu`
                            join `slevy_zajezd` on (`slevy_zajezd`.`id_slevy` = `slevy_k_zajezdu`.`id_slevy`) )
			on ( `slevy_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`						  			
			and (`slevy_k_zajezdu`.`platnost_od` = \"0000-00-00\" or `slevy_k_zajezdu`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy_k_zajezdu`.`platnost_od` is null)
			and (`slevy_k_zajezdu`.`platnost_do` = \"0000-00-00\" or `slevy_k_zajezdu`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy_k_zajezdu`.`platnost_do` is null) ) 
			
                    left join (
			`slevy` 
                            join `slevy_serial` on (`slevy_serial`.`id_slevy` = `slevy`.`id_slevy`) )
			on ( `slevy_serial`.`id_serial` = `serial`.`id_serial`						  			
			and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_od` is null)
			and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_do` is null) ) 
			    

                    where `objekt`.`id_objektu` = " . $this->get_id_ubytovani() . " and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 
                       and " . $this->global_od . $this->global_do . $this->global_nazev_akce . $this->global_cena_od . $this->global_cena_do .  " 1
                    group by `zajezd`.`id_zajezd`
                    order by `vyprodano`,`serial`.`nazev`,`od`, `do`
                    " . $limit . "
                ";
               // echo $sql;
//                echo $sql;
                $max_k = 8;
            } else {
                if ($_GET["all"] == $this->get_id_serial()) {
                    $showall = true;
                    $limit = "limit 100";
                } else {
                    $limit = "limit 15";
                }
                $sql = "select `zajezd`.*, `serial`.`nazev`,`serial`.`nazev_web`,`serial`.`dlouhodobe_zajezdy`,
                        GROUP_CONCAT(DISTINCT `slevy`.`castka` ORDER BY `slevy`.`castka` DESC SEPARATOR ',') as `sleva_castka`,GROUP_CONCAT(DISTINCT `slevy`.`mena` ORDER BY `slevy`.`castka` DESC SEPARATOR ',')  as `sleva_mena`, GROUP_CONCAT(DISTINCT `slevy`.`zkraceny_nazev` ORDER BY `slevy`.`castka` DESC SEPARATOR ';_')  as `sleva_zkraceny_nazev`,
                        GROUP_CONCAT(DISTINCT `slevy_k_zajezdu`.`castka` ORDER BY `slevy_k_zajezdu`.`castka` DESC SEPARATOR ',') as `sleva_castka_zajezd` ,GROUP_CONCAT(DISTINCT `slevy_k_zajezdu`.`mena` ORDER BY `slevy_k_zajezdu`.`castka` DESC SEPARATOR ',') as `sleva_mena_zajezd`,GROUP_CONCAT(DISTINCT `slevy_k_zajezdu`.`zkraceny_nazev` ORDER BY `slevy_k_zajezdu`.`castka` DESC SEPARATOR ';_') as `sleva_zkraceny_nazev_zajezd`,
                    
                        max(`cena_zajezd`.`vyprodano`) as `vyprodano`,
                    `cena_zajezd`.`castka`
                    from
                    
                    `serial` join
                    `zajezd` on ( `serial`.`id_serial` = `zajezd`.`id_serial` ) join                    
                    `cena_zajezd` on ( `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `cena` on ( `cena`.`id_cena` = `cena_zajezd`.`id_cena` and `cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1)
                    left join (
			`slevy` as `slevy_k_zajezdu`
                            join `slevy_zajezd` on (`slevy_zajezd`.`id_slevy` = `slevy_k_zajezdu`.`id_slevy`) )
			on ( `slevy_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`						  			
			and (`slevy_k_zajezdu`.`platnost_od` = \"0000-00-00\" or `slevy_k_zajezdu`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy_k_zajezdu`.`platnost_od` is null)
			and (`slevy_k_zajezdu`.`platnost_do` = \"0000-00-00\" or `slevy_k_zajezdu`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy_k_zajezdu`.`platnost_do` is null) ) 
                   left join (
			`slevy` 
                            join `slevy_serial` on (`slevy_serial`.`id_slevy` = `slevy`.`id_slevy`) )
			on ( `slevy_serial`.`id_serial` = `serial`.`id_serial`						  			
			and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_od` is null)
			and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy`.`platnost_do` is null) )          


                    where `serial`.`id_serial` = " . $this->get_id_serial() . " and `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 
                       and " . $this->global_od . $this->global_do . $this->global_nazev_akce . $this->global_cena_od . $this->global_cena_do .  " 1
                    group by `zajezd`.`id_zajezd`
                    order by `vyprodano`,`serial`.`nazev`,`od`, `do`
                    " . $limit . "
                ";
//                echo $sql;
                $max_k = 6;
            }
            
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);            
            $pocet = mysqli_num_rows($data);
            
            $max_sleva = 0;
            $max_sleva_mena = "Kè";
            $max_sleva_prepocitana_procenta_castka = 0;
            
            $max_sleva_serial = 0;
            $max_sleva_serial_mena = "Kè";
            $max_sleva_serial_prepocitana_procenta_castka = 0;
            $min_cena = 10000000;
            $k = 0;
            $terminy = "";
            $last_serial = "";
            $max_sleva_zajezd_procenta = 0;
            $max_sleva_zajezd_castka = 0;
            $sleva_zajezd_procenta_text = "";
            $sleva_zajezd_castka_text = "";
            if ($pocet > 1) {
                $sleva_text = "až ";
                $cena_text = "od ";
                $show_cena_u_terminu = 1;
            } else {
                $sleva_text = "";
                $cena_text = "";
                $show_cena_u_terminu = 0;
            }            
            while ($row = mysqli_fetch_array($data)) {     
                //ze vseho nejdriv zobrazim minuly serial
                    if ($last_serial != $row["nazev"]) {                       
                            //sleva k serialu 
                        if($last_serial!=""){
                        if ($this->typ_pozadavku == "select_ubytovani_group" and $max_sleva_serial > 0) {
                          if($max_sleva_serial_mena=="%"){
                            if($max_sleva_serial >= 1 and $max_sleva_serial <= 20 ){
                                $last_sleva_serial = "<b>SLEVA AŽ <img alt=\"" . $max_sleva_serial . "%\" title=\"" . $max_sleva_serial . "%\"  src=\"/slevy/".$max_sleva_serial."m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> ";
                            }else{
                                $last_sleva_serial = "<b >SLEVA AŽ ". $max_sleva_serial . "%</b> ";
                            }                        
                          }else{
                            if(in_array($max_sleva_serial, array(500,700,1000,1500,2000,2500,3000))){
                                $last_sleva_serial = "<b>SLEVA AŽ <img alt=\"" . $max_sleva_serial . "Kè\" title=\"" . $max_sleva_serial . "Kè\" src=\"/slevy/".$max_sleva_serial."m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> ";
                            }else{
                                $last_sleva_serial = "<b >SLEVA AŽ " . $max_sleva_serial . "Kè</b> ";
                            }                        
                          }
                            $max_sleva_serial=0;
                            $max_sleva_serial_prepocitana_procenta_castka=0;
                        } else {
                            $last_sleva_serial = "";
                        }

                        $last_serial_text = "<a href=\"/zajezdy/zobrazit/" . $last_serial_web . "/\"><b><i style=\"color:#757575;\">" . $last_serial . "</i></b></a> " . $last_sleva_serial . "<br/>\n";
                        
                                                                       
                        $terminy .= $last_serial_text.$last_terminy;
                        }
                        $last_serial = $row["nazev"]; 
                        $last_serial_web = $row["nazev_web"]; 
                        $last_serial_text="";
                        $last_terminy="";
                        $show_extra_zajezd = true;
                    }else{
                        $show_extra_zajezd = false;
                    }                
//                echo "<pre>";
//                var_dump($this->get_id_ubytovani());
//                var_dump($this->get_id_serial());
//                echo "</pre>";
                
                //get only first currency
                $sleva_array_mena_zajezd = explode(",", $row["sleva_mena_zajezd"]);               
                $sleva_array_mena = explode(",", $row["sleva_mena"]);     
                
                $sleva_array_castka_zajezd = explode(",", $row["sleva_castka_zajezd"]);
                $sleva_array_castka = explode(",", $row["sleva_castka"]);
                
                $sleva_array_zkraceny_nazev_zajezd = explode(";_", $row["sleva_zkraceny_nazev_zajezd"]);
                $sleva_array_zkraceny_nazev = explode(";_", $row["sleva_zkraceny_nazev"]);
                
                //slevy pro kazdy aktualni zajezd
                $text_slevy = "";
                $max_sleva_kc = 0;
                $max_sleva_procenta = 0;
                
                $k++;
                if ($row["akcni_cena"] > 0 and $row["cena_pred_akci"] > 0) {
                    $act_sleva = round(( 1 - ($row["akcni_cena"] / $row["cena_pred_akci"]) ) * 100);
                    if ($act_sleva > $max_sleva_procenta) {
                        $max_sleva_procenta = $act_sleva;
                        $text_slevy .= $act_sleva."% ".$row["popis_akce"]."\n";
                    }
                }
               
                foreach ($sleva_array_mena_zajezd as $key => $mena) {
                    if($sleva_array_castka_zajezd[$key]>0){
                        $text_slevy .= $sleva_array_castka_zajezd[$key].$mena." ".$sleva_array_zkraceny_nazev_zajezd[$key]."\n";
                        
                        if($mena=="Kè" and $max_sleva_kc < $sleva_array_castka_zajezd[$key]){
                            $max_sleva_kc = $sleva_array_castka_zajezd[$key];
                            
                        }else if($mena=="%" and $max_sleva_procenta < $sleva_array_castka_zajezd[$key]){
                            $max_sleva_procenta = $sleva_array_castka_zajezd[$key];
                        }
                    }
                }
                foreach ($sleva_array_mena as $key => $mena) {
                    if($sleva_array_castka[$key]>0){
                        $text_slevy .= $sleva_array_castka[$key].$mena." ".$sleva_array_zkraceny_nazev[$key]."\n";
                        
                        if($mena=="Kè" and $max_sleva_kc < $sleva_array_castka[$key]){
                            $max_sleva_kc = $sleva_array_castka[$key];
                            
                        }else if($mena=="%" and $max_sleva_procenta < $sleva_array_castka[$key]){
                            $max_sleva_procenta = $sleva_array_castka[$key];
                        }
                    }
                }
                //max sleva pro aktuální zájezd
                
                $max_sleva_procenta_castka = 0;
                if($max_sleva_procenta > 0){
                    //propocitam priblizne slevu v Kè
                    $max_sleva_procenta_castka = $row["castka"] * $max_sleva_procenta/100;
                }
                $act_max_sleva=0;
                $act_max_sleva_mena="Kè";
                
                if($max_sleva_procenta_castka > 0 or $max_sleva_kc >0){
                    if($max_sleva_procenta_castka > $max_sleva_kc){
                        $act_max_sleva=$max_sleva_procenta_castka;
                        $act_max_sleva_mena="%";
                            if($max_sleva_procenta > 3){
                                $sleva_zajezd_text = "<b style=\"color:red;\" title=\"".$text_slevy."\">SLEVA AŽ ". $max_sleva_procenta . "%</b> ";
                            }else{
                                $sleva_zajezd_text = "";
                            }
                        
                    }else{
                        $act_max_sleva=$max_sleva_kc;
                        $act_max_sleva_mena="Kè";
                            
                            $sleva_zajezd_text = "<b style=\"color:red;\" title=\"".$text_slevy."\">SLEVA AŽ " . $max_sleva_kc . "Kè</b> ";
                                               
                    }
                }else{
                    $sleva_zajezd_text = "";
                }
                
                
                
                //aktualizuju maximalni slevu ze vsech zajezdu aktuálního seriálu
                if($max_sleva_serial_mena == "Kè"){
                    $porovnani_max = $max_sleva_serial;
                }else{
                    $porovnani_max = $max_sleva_serial_prepocitana_procenta_castka;
                }
                if($porovnani_max < $act_max_sleva){
                    //nova nejvetsi sleva
                    if($act_max_sleva_mena=="Kè"){
                        $max_sleva_serial = $act_max_sleva;
                        $max_sleva_serial_mena = "Kè";
                        $max_sleva_serial_prepocitana_procenta_castka = 0;
                    }else{
                        $max_sleva_serial = $max_sleva_procenta;
                        $max_sleva_serial_mena = "%";
                        $max_sleva_serial_prepocitana_procenta_castka = $act_max_sleva;
                    }                    
                }
                
                //aktualizuju maximalni slevu ze vsech zajezdu
                if($max_sleva_mena == "Kè"){
                    $porovnani_max = $max_sleva;
                }else{
                    $porovnani_max = $max_sleva_prepocitana_procenta_castka;
                }
                if($porovnani_max < $act_max_sleva){
                    //nova nejvetsi sleva
                    if($act_max_sleva_mena=="Kè"){
                        $max_sleva = $act_max_sleva;
                        $max_sleva_mena = "Kè";
                        $max_sleva_prepocitana_procenta_castka = 0;
                    }else{
                        $max_sleva = $max_sleva_procenta;
                        $max_sleva_mena = "%";
                        $max_sleva_prepocitana_procenta_castka = $act_max_sleva;
                    }                    
                }
                
                if ($min_cena > $row["castka"]) {
                    $min_cena = $row["castka"];
                }
                $showall_text.="<input type='hidden' value='" . $this->get_id_ubytovani() . " - " . $this->get_id_zajezd() . "'/>";
                if ($k == $max_k and !$showall) {
                    if ($this->typ_pozadavku == "select_ubytovani_group") {
                        $showall_text.="<!--[if IE]><a href=\"" . $_SERVER["request_uri"] . "?all=" . $this->get_id_ubytovani() . "\"  style=\"color:#505090;\"><i>zobrazit všechny termíny</i></a><![endif]-->";
//                        $showall_text.="<![if !IE]><a href=\"" . $_SERVER["request_uri"] . "?all=" . $this->get_id_ubytovani() . "\"  style=\"color:#505090;\"><i>zobrazit všechny termíny</i></a><![endif]>";
                        $showall_text.="<![if !IE]><div style='cursor: pointer;text-decoration: underline;' onclick=\"getKatalogAJAX(this, " . $this->get_id_ubytovani() . ", 'ubytovani');\"><i>zobrazit všechny termíny</i></a><![endif]>";
                    } else {
                        $showall_text.="<!--[if IE]><a href=\"" . $_SERVER["request_uri"] . "?all=" . $this->get_id_serial() . "\"  style=\"color:#505090;\"><i>zobrazit všechny termíny</i></a><![endif]-->";
//                        $showall_text.="<![if !IE]><a href=\"" . $_SERVER["request_uri"] . "?all=" . $this->get_id_serial() . "\"  style=\"color:#505090;\"><i>zobrazit všechny termíny</i></a><![endif]>";
                        $showall_text.="<![if !IE]><div style='cursor: pointer;text-decoration: underline;' onclick=\"getKatalogAJAX(this, " . $this->get_id_serial() . ", 'serial');\"><i>zobrazit všechny termíny</i></a><![endif]>";
                    }
                
                }
                
                if($k <= $max_k or $showall or $show_extra_zajezd){
                    
                    if ($row["od"] == $row["do"]) {
                        $datum = "<meta itemprop=\"validFrom\" content=\"".$row["od"]."\"/>".$this->change_date_en_cz($row["od"])."</time>";
                    } else {
                        $datum = "<meta itemprop=\"validFrom\" content=\"".$row["od"]."\"/>"."<meta itemprop=\"validThrough\" content=\"".$row["do"]."\"/>".$this->change_date_en_cz_short($row["od"]) . " - " . $this->change_date_en_cz($row["do"]);
                    }
                    $podnazev = "<span  itemprop=\"offers\" itemscope itemtype=\"http://schema.org/Offer\">";
                    if ($row["nazev_zajezdu"]) {
                        if (preg_match("/[0-9]+\. *[0-9]+/", $row["nazev_zajezdu"]) != 0) {
                            $podnazev .= "<a href=\"/zajezdy/zobrazit/" . $row["nazev_web"] . "/" . $row["id_zajezd"] . "\">" . $row["nazev_zajezdu"] . "</a>";
                        } else {
                            $podnazev .= "<a href=\"/zajezdy/zobrazit/" . $row["nazev_web"] . "/" . $row["id_zajezd"] . "\">" . $row["nazev_zajezdu"] . ", " . $datum . "</a>" . ($row["dlouhodobe_zajezdy"] ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : (""));
                        }
                    } else {
                        $podnazev .= "<a href=\"/zajezdy/zobrazit/" . $row["nazev_web"] . "/" . $row["id_zajezd"] . "\">" . $datum . "</a>" . ($row["dlouhodobe_zajezdy"] ? ("<span title=\"Odjezd a pøíjezd libovolnì v rámci zadaného termínu\" style=\"color:green;font-weight:bold;font-size:1.2em;\">*</span>") : (""));
                    }

                    

                    $podnazev.= " od <b style=\"color:green\" itemprop=\"price\">" . $row["castka"] . " Kè</b> <meta itemprop=\"priceCurrency\" content=\"CZK\" />" . $sleva_zajezd_text . "";
                    $last_terminy.=$podnazev . "</span><br/>";
                }
            }
                      
            
            //musime jeste vypsat posledni serial
                        if($last_serial!=""){
                        if ($this->typ_pozadavku == "select_ubytovani_group" and $max_sleva_serial > 0) {
                          if($max_sleva_serial_mena=="%"){
                            if($max_sleva_serial >= 1 and $max_sleva_serial <= 20 ){
                                $last_sleva_serial = "<b>SLEVA AŽ <img alt=\"" . $max_sleva_serial . "%\" title=\"" . $max_sleva_serial . "%\"  src=\"/slevy/".$max_sleva_serial."m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> ";
                            }else{
                                $last_sleva_serial = "<b >SLEVA AŽ ". $max_sleva_serial . "%</b> ";
                            }                        
                          }else{
                            if(in_array($max_sleva_serial, array(500,700,1000,1500,2000,2500,3000))){
                                $last_sleva_serial = "<b>SLEVA AŽ <img alt=\"" . $max_sleva_serial . "Kè\" title=\"" . $max_sleva_serial . "Kè\" src=\"/slevy/".$max_sleva_serial."m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> ";
                            }else{
                                $last_sleva_serial = "<b >SLEVA AŽ " . $max_sleva_serial . "Kè</b> ";
                            }                        
                          }
                            $max_sleva_serial=0;
                            $max_sleva_serial_prepocitana_procenta_castka=0;
                        } else {
                            $last_sleva_serial = "";
                        }

                        $last_serial_text = "<a href=\"/zajezdy/zobrazit/" . $last_serial_web . "/\"><b><i style=\"color:#757575;\">" . $last_serial . "</i></b></a> " . $last_sleva_serial . "<br/>\n";
                        
                                                                       
                        $terminy .= $last_serial_text.$last_terminy;
                        }
                        $terminy .= $showall_text;
            //zacatek klasickeho vypisu
            if ($text_max == "") {
                $text_max = 180;
            }
            if ($text_min == "") {
                $text_min = 150;
            }
            if ($text_ideal == "") {
                $text_ideal = 160;
            }
            if($this->typ_pozadavku == "select_ubytovani_group"){
                $text_base = strip_tags($this->get_popisek() . " \n" . $this->get_popisek_ubytovani());
            }else{
                $text_base = strip_tags($this->get_popisek()) ;
            }
            
            $konec_textu = @strpos($text_base, ".", $text_min);
            $konec_textu2 = @strpos($text_base, "!", $text_min);
            $konec_textu3 = @strpos($text_base, "?", $text_min);
            $konec_textu4 = @strpos($text_base, " ", $text_min);

            if ($konec_textu < $text_max and $konec_textu >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < $text_max and $konec_textu2 >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < $text_max and $konec_textu3 >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < $text_max and $konec_textu4 >= $text_min) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, ($text_ideal)) . "...";
            }


            if ($this->get_foto_url()) {
                $foto = "                                
                                        <img itemprop=\"image\" height=\"80\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda round\"";
            } else {
                $class = " class=\"licha round\"";
            }
            
            
            if($max_sleva > 0 ){
               if($max_sleva_mena=="%"){
                        if($max_sleva >= 1 and $max_sleva <= 20 ){
                            $vypis_sleva = "<b>SLEVA AŽ <img alt=\"" . $max_sleva . "%\" title=\"".$max_sleva."%\" src=\"/slevy/".$max_sleva."m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> ";
                        }else{
                            $vypis_sleva = "<b style=\"font-size:1.2em;\">SLEVA " . $sleva_text ."<span style=\"color:green;font-size:1.6em;\">". $max_sleva . "%</span></b>";
                        }                        
               }else{
                        if(in_array($max_sleva, array(500,700,1000,1500,2000,2500,3000))){
                            $vypis_sleva = "<b>SLEVA AŽ <img alt=\"" . $max_sleva . "Kè\" title=\"" . $max_sleva . "Kè\" src=\"/slevy/".$max_sleva."m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> ";
                        }else{
                            $vypis_sleva = "<b style=\"font-size:1.2em;\">SLEVA " . $sleva_text ."<span style=\"color:green;font-size:1.6em;\">". $max_sleva . "Kè</span></b>";
                        }                        
                    }
            }else{
                    $vypis_sleva = "";
            }
            

            if ($this->typ_pozadavku == "select_ubytovani_group") {
                $odkaz = "<a itemprop=\"url\" href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\">";
                $odkaz_tlacitko = "<a  href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" class=\"obj-y-b\" style=\"display:inline-block;width:165px;padding:2px 1px 2px 1px;\">";
            } else {
                $odkaz = "<a itemprop=\"url\" href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\">";
                $odkaz_tlacitko = "<a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" class=\"obj-y-b\" style=\"display:inline-block;width:165px;padding:2px 1px 2px 1px;\">";
            }
            $vypis = "                            
                              <div " . $class . " style=\"border-bottom:1px solid black;\"  itemscope itemtype=\"http://schema.org/Product\">
                               <table style=\"width:100%\">  
                                <tr>
                                <td rowspan=\"3\" valign=\"top\" style=\"width:120px;\">
                                    $odkaz
                                        " . $foto . "
                                    </a>
                                <td valign=\"top\" style=\"height:25px;overflow:visible;\">    
                                    " . $odkaz . "
                                        <h2 ><i itemprop=\"name\">" . $this->get_nazev() . "</i></h2>
                                    </a> 
                                <td align=\"right\">
                                    " . $vypis_sleva . $this->show_ikony("") . "
                                </tr>
                                <tr>
                                <td colspan=\"2\" valign=\"top\" itemprop=\"description\">
                                    " . $text . "
                                </td></tr>
                                <tr>
                                    <td valign=\"top\">
                                        " . $terminy . " 
                                    </td>    
                                    <td valign=\"top\" style=\"text-align:right;\">
                                        " . $odkaz_tlacitko . " Informace a objednávky </a>
                                    </td>
                                </tr>
                               </table>      
                              </div>        
                          ";
            return $vypis;
        } else if ($typ_zobrazeni == "ubytovani_list") {//vypis zajezdu vcetne balicku a terminu                                                
            $text_base = strip_tags($this->get_popisek());
            $konec_textu = strpos($text_base, ".", 50);
            $konec_textu2 = strpos($text_base, "!", 50);
            $konec_textu3 = strpos($text_base, "?", 50);
            $konec_textu4 = strpos($text_base, " ", 50);

            if ($konec_textu < 80 and $konec_textu >= 50) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < 80 and $konec_textu2 >= 50) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < 80 and $konec_textu3 >= 50) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < 80 and $konec_textu4 >= 50) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, (60)) . "...";
            }


            if ($this->get_foto_url()) {
                $foto = "                                
                                        <img style=\"border:none;margin:2px 2px; 0 0;\" height=\"35\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            }
            if ($this->suda) {
                $class = " class=\"suda\"";
            } else {
                $class = " class=\"licha\"";
            }

            $vypis = "                            
                              <tr " . $class . " >
                                <td>
                                    <a  href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" >
                                        $foto
                                    </a>
                                <td style=\"font-family:arial;\">    
                                    <a  href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" >
                                        <b><em>" . $this->get_nazev() . "</em></b>
                                    </a> <span style=\"font-size:0.9em;\">" . $text . "</span>                                     
                                    cena od: <span style=\"color:red;font-weight:bold;\">" . $this->get_castka() . " Kè</span>                           
                          ";
            return $vypis;
        } else if ($typ_zobrazeni == "ubytovani_serial_list") {//vypis zajezdu vcetne balicku a terminu    
            $text_base = strip_tags($this->radek["popisek_ubytovani"]);
            $konec_textu = strpos($text_base, ".", 100);
            $konec_textu2 = strpos($text_base, "!", 100);
            $konec_textu3 = strpos($text_base, "?", 100);
            $konec_textu4 = strpos($text_base, " ", 100);

            if ($konec_textu < 120 and $konec_textu >= 100) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < 120 and $konec_textu2 >= 100) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < 120 and $konec_textu3 >= 100) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < 120 and $konec_textu4 >= 100) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, (110)) . "...";
            }

            if ($this->last_ubytovani != $this->radek["nazev_ubytovani"]) {
                $this->last_ubytovani = $this->radek["nazev_ubytovani"];
                if ($this->get_foto_url()) {
                    $foto = "                                
                                        <img height=\"45\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev_ubytovani() . "\" title=\"" . $this->get_nazev_ubytovani() . "\" />";
                }
                if ($this->suda2) {
                    $class = " class=\"suda\"";
                    $this->suda2 = 0;
                } else {
                    $class = " class=\"licha\"";
                    $this->suda2 = 1;
                }

                $vypis = "                            
                              <tr " . $class . " >
                                <td>
                                    <a  href=\"/zajezdy/ubytovani/" . $this->get_nazev_ubytovani_web() . "\" >
                                        $foto
                                    </a>
                                <td>    
                                    <a  href=\"/zajezdy/ubytovani/" . $this->get_nazev_ubytovani_web() . "\" >
                                        <h4><em>" . $this->get_nazev_ubytovani() . "</em></h4>
                                    </a> " . $text . "   
                               <tr " . $class . " >
                                <td colspan=\"2\" style=\"padding-left:10px;\">                                                                        
                          ";
            } else {
                $vypis = "";
            }
            $vypis.= "
                              
                                <a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >" . $this->get_nazev() . "</a> 
                                <span style=\"color:red;font-weight:bold;\">od: " . $this->get_castka() . " Kè</span>
                           <br/>
                            ";

            return $vypis;
        } else if ($typ_zobrazeni == "serial_list_table") {//vypis zajezdu vcetne balicku a terminu                                                   
            $vypis = "                              
                                <tr><td colspan=\"2\"><a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" > -&gt; " . strtoupper($this->get_nazev()) . "</a></td></tr> 
                            ";

            return $vypis;
        } else if ($typ_zobrazeni == "serial_zajezd_list_ubytovani") {
            ////vypis zajezdu a serialu pro dane ubytovani   

            $text_base = strip_tags($this->radek["popisek"]);
            $konec_textu = @strpos($text_base, ".", 200);
            $konec_textu2 = @strpos($text_base, "!", 200);
            $konec_textu3 = @strpos($text_base, "?", 200);
            $konec_textu4 = @strpos($text_base, " ", 200);

            if ($konec_textu < 220 and $konec_textu >= 200) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < 220 and $konec_textu2 >= 200) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < 220 and $konec_textu3 >= 200) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < 220 and $konec_textu4 >= 200) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, (210)) . "...";
            }

            if ($this->last_ubytovani != $this->radek["nazev"]) {
                $this->last_ubytovani = $this->radek["nazev"];
                if ($this->suda2) {
                    $class = "suda";
                    $this->suda2 = 0;
                } else {
                    $class = "licha";
                    $this->suda2 = 1;
                }
                
                if ($this->not_first) {
                    $vypis = "
                             </table>
                            </div>";
                } else {
                    $vypis = "";
                    $this->not_first = 1;
                }
                if ($this->radek["sleva_castka"] > 0) {
                     $sleva_serial = "
                         <div style=\"float:right; text-align:right;\">
                            <b>SLEVA <img alt=\"" . $this->radek["sleva_castka"] . " " . $this->radek["sleva_mena"] . "\" title=\"" . $this->radek["sleva_nazev"] . "\" src=\"/slevy/" . $this->radek["sleva_castka"] . "m.gif\" style=\"margin-bottom:-3px;border:none;\"/></b> 
                         </div>";
                } else{
                    $sleva_serial = "";
                }
                
                $vypis .= "                                                           
                            <div class=\"round " . $class . "\">
                              <table width=\"100%\">  
                              <tr>
                                
                                <td>    
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        $sleva_serial
                                        <h2><em>" . $this->get_nazev_reverse() . "</em></h2>
                                    </a> " . $text . "                                                                                                                                         
                          ";
            } else {
                $vypis = "";
            }
            if ($this->radek["cena_pred_akci"] and $this->radek["akcni_cena"]) {
                    $sleva = " (<b style=\"color:green;\">SLEVA " . round((1 - $this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) * 100) . "%</b>)";
            } else if($this->radek["sleva_zajezd_castka"] > 0){                   
                    $sleva = "
                         <div style=\"float:right; text-align:right;\">
                            <b title=\"" . $this->radek["sleva_zajezd_nazev"] . "\">SLEVA až ".$this->radek["sleva_zajezd_castka"] . " " . $this->radek["sleva_zajezd_mena"] . "</b> 
                         </div>";
            } else {
                    $sleva = "";
            }
            $vypis.= " <tr><td  style=\"padding-left:10px;\">                              
                                <a href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >" . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . "</a> 
                                <span style=\"color:red;font-weight:bold;\">od: " . $this->get_castka() . " Kè " . $sleva . "</span>
                           
                            ";

            return $vypis;
        } else if ($typ_zobrazeni == "serial_zajezd_list_only_current") {//vypis zajezdu vcetne balicku a terminu    
            $text_base = strip_tags($this->radek["popisek"]);
            $konec_textu = strpos($text_base, ".", 100);
            $konec_textu2 = strpos($text_base, "!", 100);
            $konec_textu3 = strpos($text_base, "?", 100);
            $konec_textu4 = strpos($text_base, " ", 100);

            if ($konec_textu < 120 and $konec_textu >= 100) {
                $text = substr($text_base, 0, ($konec_textu + 1));
            } else if ($konec_textu2 < 120 and $konec_textu2 >= 100) {
                $text = substr($text_base, 0, ($konec_textu2 + 1));
            } else if ($konec_textu3 < 120 and $konec_textu3 >= 100) {
                $text = substr($text_base, 0, ($konec_textu3 + 1));
            } else if ($konec_textu4 < 120 and $konec_textu4 >= 100) {
                $text = substr($text_base, 0, ($konec_textu4)) . "...";
            } else {
                $text = substr($text_base, 0, (110)) . "...";
            }

            if ($this->last_ubytovani != $this->radek["nazev"]) {
                $this->last_ubytovani = $this->radek["nazev"];
                if ($this->get_foto_url()) {
                    $foto = "                                
                                        <img height=\"45\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev_ubytovani() . "\" title=\"" . $this->get_nazev_ubytovani() . "\" />";
                }
                if ($this->suda2) {
                    $class = " class=\"suda\"";
                    $this->suda2 = 0;
                } else {
                    $class = " class=\"licha\"";
                    $this->suda2 = 1;
                }

                if ($this->radek["cena_pred_akci"] and $this->radek["akcni_cena"]) {
                    $sleva = " (<b style=\"color:green;\">SLEVA " . round((1 - $this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) * 100) . "%</b>)";
                } else {
                    $sleva = "";
                }

                $vypis = "                            
                              <tr " . $class . " >
                                <td>
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        $foto
                                    </a>
                                <td>    
                                    <a  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" >
                                        <h4><em>" . $this->get_nazev() . "</em></h4>
                                    </a> " . $text . "   
                               <tr " . $class . " >
                                <td colspan=\"2\" style=\"padding-left:10px;\">                                                                        
                          ";
            } else {
                $vypis = "";
            }
            if ($this->get_nazev_web() == $_GET["serial"]) {
                if ($this->get_id_zajezd() == $_GET["zajezd"]) {
                    $style = " style=\"background-color:#ffff75;color:#0050ca0;font-weight:bold;\" ";
                } else {
                    $style = "";
                }
                $vypis.= "
                              
                                <a " . $style . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "/" . $this->get_id_zajezd() . "\" >" . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . "</a> 
                                <span style=\"color:red;font-weight:bold;\">od: " . $this->get_castka() . " Kè " . $sleva . "</span>
                           <br/>
                            ";
            } else {
                
            }


            return $vypis;
        } else if ($typ_zobrazeni == "ubytovani_foto_list") {//vypis zajezdu vcetne balicku a terminu                                                                                                                         
            if ($this->radek["foto_url"] != "") {
                $foto = "<img width=\"142\" style=\"margin:0;padding:0;border:none;\" height=\"85\" src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->radek["foto_url"] . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" />";
            } else {
                $foto = "<img width=\"142\" style=\"margin:0;padding:0;border:none;\"  height=\"85\" src=\"http://lazenske-pobyty.info/images/empty_image.jpg\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\"/>";
            }
           if(strlen($this->get_nazev())>=25){
               $style_nazev = "display:block;width:146px;height:32px;background-color:#eace8e;margin-top:-32px;position:relative;z-index:10;font-weight:bold;line-height:1.2em;text-align:center;";  
           }else{
               $style_nazev = "display:block;width:146px;height:20px;background-color:#eace8e;margin-top:-20px;position:relative;z-index:10;font-weight:bold;text-align:center;";    
           }
                    
            return
                    "<div class=\"round\" style=\"height:85px;width:142px;overflow:hidden;margin:0 1px 1px 0px;\">
                        <a href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" >
                            $foto
                        </a>        
                    <a title=\"" . $this->get_nazev() . "\" href=\"/zajezdy/ubytovani/" . $this->get_nazev_web() . "\" style=\"".$style_nazev."\">" . $this->get_nazev() . " </a>
                            </div>";

            return $vypis;
        } else if ($typ_zobrazeni == "hotel_list") {//vypis zajezdu vcetne balicku a terminu
            $vypis = "<tr $suda><td>" . $vyprodano . "<a " . $color . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_ubytovani_web() . "\">
                                <strong>" . $this->get_nazev_ubytovani() . "</strong></a>
                                <td>" . $this->get_nazev_zeme() . ", " . $this->get_nazev_destinace() . "
                                <td style=\"text-align:right;\"><span style=\"font-weight:bold;color:#009a00;font-size:1.2em;\">" . $this->get_castka() . " Kè</span>
                            </tr>
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "doporucujeme_list_left") {//vypis zajezdu vcetne balicku a terminu
            if ($this->get_foto_url()) {
                $foto = "<a " . $color . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\"><img src=\"https://www.slantour.cz/" . ADRESAR_IKONA . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" width=\"120\" /></a>";
            }

            $vypis = "<div class=\"round\" style=\"background-color:white;text-align:center;\"><a " . $color . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\">
                                <strong>" . $this->get_nazev() . "</strong></a><br/>
                                    " . $foto . "<br/>
                                " . $this->get_nazev_zeme() . ", " . $this->get_nazev_destinace() . "<br/>
                                <span style=\"font-weight:bold;color:#009a00;font-size:1.2em;\">cena od: " . $this->get_castka() . " Kè</span>
                                 </div>   
                            ";
            return $vypis;
        } else if ($typ_zobrazeni == "doporucujeme_list") {//vypis zajezdu vcetne balicku a terminu
            if ($this->get_foto_url()) {
                $foto = "<a " . $color . " href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\"><img src=\"https://www.slantour.cz/" . ADRESAR_NAHLED . "/" . $this->get_foto_url() . "\" alt=\"" . $this->get_nazev() . "\" title=\"" . $this->get_nazev() . "\" width=\"152\" height=\"116\" /></a>";
            }
            $vypis = "<div  style=\"margin-left:2px;\">
                                <div style=\"height:27px;\"><a " . $color . "  href=\"/zajezdy/zobrazit/" . $this->get_nazev_web() . "\" title=\"" . $this->get_nazev() . "\">
                                <strong>" . $this->get_nazev() . "</strong></a></div>
                                    " . $foto . "<br/>
                                " . $this->get_nazev_zeme() . ", " . $this->get_nazev_destinace() . "<br/>
                                <span style=\"font-weight:bold;color:#009a00;font-size:1.2em;\">cena od: " . $this->get_castka() . " Kè</span>
                                 </div>   
                            ";
            return $vypis;
        } else {
            return "";
        }
    }

    /*     * zobrazi odkazy na dalsi stranky vypisu zajezdu */

    function show_strankovani() {
        //prvni cislo stranky ktere zobrazime
        $act_str = $this->zacatek - (10 * $this->pocet_zaznamu);
        if ($act_str < 0) {
            $act_str = 0;
        }
        if ($this->id_destinace != "") {
            $destinace = "&amp;destinace=" . $this->id_destinace . "";
        } else {
            $destinace = "";
        }

        //odkaz na prvni stranku
        $vypis = "<div class=\"strankovani\"><a href=\"?str=0" . $destinace . "\" title=\"první stránka zájezdù\">&lt;&lt;</a> &nbsp;";

        //odkaz na dalsi stranky z rozsahu
        while (($act_str <= $this->pocet_zajezdu) and ($act_str <= $this->zacatek + (10 * $this->pocet_zaznamu) )) {
            if ($this->zacatek != $act_str) {
                $vypis = $vypis . "<a href=\"?str=" . $act_str . $destinace . "\" title=\"strana " . (1 + ($act_str / $this->pocet_zaznamu)) . "\">" . (1 + ($act_str / $this->pocet_zaznamu)) . "</a> ";
            } else {
                $vypis = $vypis . (1 + ($act_str / $this->pocet_zaznamu)) . " ";
            }
            $act_str = $act_str + $this->pocet_zaznamu;
        }

        //odkaz na posledni stranku
        $posl_str = $this->pocet_zaznamu * floor($this->pocet_zajezdu / $this->pocet_zaznamu);
        $vypis = $vypis . " &nbsp; <a href=\"?str=" . $posl_str . $destinace . "\" title=\"poslední stránka zájezdù\">&gt;&gt;</a></div>";

        return $vypis;
    }

    function get_name_for_destinace($sep = ", ") {
        if ($this->nazev_destinace != "") {
            //pokud uz nazev mame, nebudeme opakovat dotaz
            return $this->nazev_destinace . $sep;
        }
        if ($_GET["id_destinace"]) {
            $dotaz = "select `nazev_destinace` from `destinace` where `id_destinace`=" . $_GET["id_destinace"] . " limit 1";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
            while ($zaznam = mysqli_fetch_array($data)) {
                $this->nazev_destinace = $zaznam["nazev_destinace"];
                return $zaznam["nazev_destinace"] . $sep;
                break;
            }
        } else {
            return "";
        }
    }

    function get_name_for_zeme($sep = ", ") {
        if ($this->nazev_zeme != "") {
            //pokud uz nazev mame, nebudeme opakovat dotaz
            return $this->nazev_zeme . $sep;
        }
        if ($_GET["zeme"]) {
            $sql = "select `nazev_zeme` from `zeme` where `nazev_zeme_web` = \"" . $_GET["zeme"] . "\"";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($zaznam = mysqli_fetch_array($data)) {
                $this->nazev_zeme = $zaznam["nazev_zeme"];
                return $zaznam["nazev_zeme"] . $sep;
                break;
            }
        } else {
            return "";
        }
    }

    /** vytvori text pro nadpis stranky */
    function get_name_for_typ($sep = ", ") {
        if ($this->nazev_typu != "") {            
            //pokud uz nazev mame, nebudeme opakovat dotaz
            return $this->nazev_typu . $sep;
        }
        if ($_GET["typ"]) {
            $sql = "select `nazev_typ` from `typ_serial` where `nazev_typ_web` = \"" . $_GET["typ"] . "\"";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            $dateNextYear = (Date("Y") + 1);
            $dateText = (string) $dateNextYear;
            while ($zaznam = mysqli_fetch_array($data)) {
                if (stripos($zaznam["nazev_typ"], Date("Y")) === false and stripos($zaznam["nazev_typ"], $dateText) === false) {
                    //nazev typu neobsahuje rok, pridam ho tam
                    if (Date("m") >= 9) {
                        $zaznam["nazev_typ"].=" " . Date("Y") . "/" . (Date("Y") + 1);
                    } else {
                        $zaznam["nazev_typ"].=" " . Date("Y");
                    }
                }
                $this->nazev_typu = $zaznam["nazev_typ"];
                return $zaznam["nazev_typ"] . $sep;
                break;
            }
        } else {
            return "";
        }
    }

    function get_name_for_podtyp($sep = ", ") {
        if ($this->nazev_podtypu != "") {
            //pokud uz nazev mame, nebudeme opakovat dotaz
            return $this->nazev_podtypu . $sep;
        }
        if ($_GET["typ"]) {
            $sql = "select `nazev_typ` from `typ_serial` where `nazev_typ_web` = \"" . $_GET["podtyp"] . "\"";
            $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
            while ($zaznam = mysqli_fetch_array($data)) {
                $this->nazev_podtypu = $zaznam["nazev_typ"];
                return $zaznam["nazev_typ"] . $sep;
                break;
            }
        } else {
            return "";
        }
    }

    static function nazev_web_static($vstup) {
        //need to change
        //vymenim hacky a carky
        $nazev_web = Str_Replace(
                Array("ä", "ë", "ö", "ü", "á", "è", "ï", "é", "ì", "í", "¾", "ò", "ó", "ø", "š", "", "ú", "ù", "ý", "ž", "Ä", "Ë", "Ö", "Ü", "Á", "È", "Ï", "É", "Ì", "Í", "¼", "Ò", "Ó", "Ø", "Š", "", "Ú", "Ù", "Ý", "Ž"), Array("a", "e", "o", "u", "a", "c", "d", "e", "e", "i", "l", "n", "o", "r", "s", "t", "u", "u", "y", "z", "A", "E", "O", "U", "A", "C", "D", "E", "E", "I", "L", "N", "O", "R", "S", "T", "U", "U", "Y", "Z"), $vstup);
        $nazev_web = Str_Replace(Array(" ", "_", "/"), "-", $nazev_web); //nahradí mezery a podtržítka pomlèkami
        $nazev_web = Str_Replace(Array("(", ")", ".", "!", ",", "\"", "'", "*"), "", $nazev_web); //odstraní ().!,"'
        $nazev_web = StrToLower($nazev_web); //velká písmena nahradí malými.
        return $nazev_web;
    }

    /** vytvori text pro nadpis stranky */
    static function get_name_from_typ($typ) {
        if(Serial_list::nazev_web_static($typ)!=""){
        $sql = "select `nazev_typ` from `typ_serial` where `nazev_typ_web` = \"" . Serial_list::nazev_web_static($typ) . "\"";
       // echo $sql;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["nazev_typ"];
            break;
        }
        }
    }

    static function get_id_from_typ($typ) {
        if(Serial_list::nazev_web_static($typ)!=""){
        $sql = "select `id_typ` from `typ_serial` where `nazev_typ_web` = \"" . Serial_list::nazev_web_static($typ) . "\"";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["id_typ"];
            break;
        }
        }
    }

    static function get_id_from_zeme($zeme) {
        if(Serial_list::nazev_web_static($zeme)!=""){
        $sql = "select `id_zeme` from `zeme` where `nazev_zeme_web` = \"" . Serial_list::nazev_web_static($zeme) . "\"";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$sql);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["id_zeme"];
            break;
        }
        }
    }

    static function getNameForIDZeme($zeme) {
        $zeme = strip_tags(trim($zeme));
        $zeme = str_replace(array(",", ";", ".", "!"), "", $zeme);
        if(intval($zeme)>0){
        $dotaz = "select `nazev_zeme` from `zeme` where `id_zeme`=" . $zeme . " limit 1";
        //echo $dotaz;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["nazev_zeme"];

            break;
        }
        }
    }

    static function getNameWebForIDZeme($zeme) {
        $zeme = strip_tags(trim($zeme));
        $zeme = str_replace(array(",", ";", ".", "!"), "", $zeme);
        if(intval($zeme)>0){
        $dotaz = "select `nazev_zeme_web` from `zeme` where `id_zeme`=" . $zeme . " limit 1";
        //echo $dotaz;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["nazev_zeme_web"];

            break;
        }
        }
    }

    static function getNameForZeme($zeme) {
        $zeme = strip_tags(trim($zeme));
        $zeme = str_replace(array(",", ";", ".", "!"), "", $zeme);
        if($zeme!=""){
        $dotaz = "select `nazev_zeme` from `zeme` where `nazev_zeme_web`=\"" . $zeme . "\" limit 1";
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["nazev_zeme"];

            break;
        }
        }
    }

    static function get_name_from_id_destinace($id_destinace) {
        $id_destinace = strip_tags(trim($id_destinace));
        $id_destinace = str_replace(array(",", ";", ".", "!"), "", $id_destinace);
        if(intval($id_destinace)>0){
        $dotaz = "select `nazev_destinace` from `destinace` where `id_destinace` =" . $id_destinace . "  limit 1";
        //echo $dotaz;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["nazev_destinace"];

            break;
        }
        }
    }

    static function get_name_of_only_typ($zeme) {
        $select = "select distinct `typ`.`nazev_typ_web`";
        $dotaz = $select . "
                    from `serial` join                    
                    `typ_serial` as `typ` on (`serial`.`id_typ` = `typ`.`id_typ`) join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)                    
                    where  `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `zajezd`.`od` > \"" . Date("Y-m-d") . "\" and `zeme`.`nazev_zeme_web` = \"" . $zeme . "\"
                    ";
        //echo $dotaz;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        if (mysqli_num_rows($data) > 1) {
            return false;
        }
        while ($zaznam = mysqli_fetch_array($data)) {
            return $zaznam["nazev_typ_web"];
        }
    }

    static function get_id_from_destinace($vstup, $zeme="") {
        if($zeme!=""){
           $dotaz = "select `nazev_destinace`, `id_destinace` from `destinace` join `zeme` on (`zeme`.`id_zeme` = `destinace`.`id_zeme`) where `zeme`.`nazev_zeme_web`=\"".$zeme."\""; 
        }else{
           $dotaz = "select `nazev_destinace`, `id_destinace` from `destinace` where 1"; 
        }
        
        //echo $dotaz;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        while ($zaznam = mysqli_fetch_array($data)) {
            //echo "test2".$zaznam["nazev_destinace"];
            $destinace_web = Serial_list::nazev_web_static($zaznam["nazev_destinace"]);
            if ($destinace_web == $vstup) {

                return $zaznam["id_destinace"];
                break;
            }
        }
    }

    /** vytvori text pro titulek stranky */
    function show_titulek() {

        //tvorba vypisu titulku
        if ($_GET["typ"] != "" or $_GET["podtyp"] != "" or $_GET["zeme"] != "" or $_GET["destinace"] != "") {              
            if ($_GET["zeme"] == "" and $_GET["destinace"] == "") {
                if($this->get_name_for_typ("")=="Pobyty na horách ".Date("Y")){
                    $nazev_typ = "Ubytování a pobyty na horách 2016 ";
                }else{
                    $nazev_typ = $this->get_name_for_typ(" ");
                }
                return $nazev_typ . $this->description[$_GET["typ"]] . $this->get_name_for_podtyp(" ") . " | SLAN tour";
            } else if($this->get_typ()!="za-sportem"  and $_GET["zeme"] != "" and $_GET["destinace"] == ""){
                
                return  $this->get_name_for_typ(", ").$this->get_name_for_zeme(" | ") . $this->get_name_for_podtyp(" | ") . " SLAN tour";
            
            }else {
                if($this->get_name_for_typ("")=="Pobyty na horách ".Date("Y")){
                    $nazev_typ = "Ubytování a pobyty na horách 2016 | ";
                }else{
                    $nazev_typ = $this->get_name_for_typ(" | ");
                }
                return $this->get_name_for_destinace(", ") . $this->get_name_for_zeme(" | ") . $nazev_typ . $this->get_name_for_podtyp(" | ") . " SLAN tour";
            }
        } else {

            return TITLE_TOP;
        }
    }

    function show_nadpis() {
        //tvorba vypisu titulku
        if (strpos($this->nazev_typ, Date("Y")) === false) {
            $date = " " . Date("Y");
        }
        if($this->get_name_for_typ("")=="Pobyty na horách"){
                    $nazev_typ = "Ubytování na horách ";
                }else{
                    $nazev_typ = $this->get_name_for_typ("");
                }
        if ($this->get_name_for_destinace("") != "") {
            if ($this->get_name_for_typ("") != "") {
                return $this->get_name_for_destinace() . $nazev_typ;
            } else {
                return $this->get_name_for_destinace() . $this->get_name_for_zeme("");
            }
        } else if ($this->get_name_for_zeme("") != "" and $this->get_name_for_typ("") != "") {
            return $this->get_name_for_zeme() . $nazev_typ;
        } else if ($this->get_name_for_zeme("") != "" and $this->get_name_for_typ("") != "") {
            return $this->get_name_for_zeme() . $nazev_typ;
        } else if ($this->get_name_for_zeme("") != "") {
            return $this->get_name_for_zeme("");
        } else if ($this->get_name_for_typ("") != "") {
            return $nazev_typ;
        } else {
            return "Katalog zájezdù " . Date("Y");
        }
    }

    /** vytvori text pro nadpis stranky */

    /** vytvori text pro meta keyword stranky */
    function show_keyword() {
        //tvorba vypisu titulku                    
        if ($_GET["typ"] != "" or $_GET["podtyp"] != "" or $_GET["zeme"] != "" or $_GET["destinace"] != "") {
            return $this->get_name_for_destinace() . $this->get_name_for_zeme() . $this->get_name_for_typ() . $this->get_name_for_podtyp() . " SLAN tour, zájezdy " . Date("Y");
        } else {
            return "SLAN tour, zájezdy " . Date("Y") . "Poznávací, dovolená, za sportem, láznì";
        }
    }

    /** vytvori text pro meta description stranky */
    function show_description() {
        //tvorba vypisu titulku
        if ($_GET["typ"] != "" or $_GET["podtyp"] != "" or $_GET["zeme"] != "" or $_GET["destinace"] != "") {
            return "Katalog zájezdù od CK SLAN tour do " . $this->get_name_for_destinace() . $this->get_name_for_zeme() . $this->get_name_for_typ() . $this->get_name_for_podtyp() . " SLAN tour, zájezdy " . Date("Y");
        } else {
            return "SLAN tour, zájezdy " . Date("Y") . ": Poznávací zájezdy, dovolená u moøe, zájezdy za sportem, vstupenky na sportovní soutìže, láznì a termály v Èechách, na Moravì, Slovensku a Maïarsku";
        }
    }

    function show_zajezdy_option() {

        while ($this->get_next_radek()) {
            $result .= "<option value=\"" . $this->get_id_zajezd() . "\">" . $this->change_date_en_cz($this->get_termin_od()) . " - " . $this->change_date_en_cz($this->get_termin_do()) . " </option>";
        }
        return $result;
    }

    function show_zeme($typ_zobrazeni) {


        $dotaz = "  select distinct
                            `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`

                    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial` ) join
                    `zeme_serial` on (`zeme_serial`.`id_serial` = `serial`.`id_serial`) join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)    
                    where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 
                    and `zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 )
                    order by `zeme`.`nazev_zeme`
                    ";
        //echo $dotaz;
        $data = mysqli_query($GLOBALS["core"]->database->db_spojeni,$dotaz);
        $result = "";
        $i = 0;
        $last_zeme = "";
        while ($zaznam = mysqli_fetch_array($data)) {
            if ($typ_zobrazeni == "list") {
                if ($i == 0) {
                    $result.="<ul style=\"float:left; width:150px; margin-right:10px;\">";
                    $i++;
                }
                if (($i % 12) == 1) {
                    $result.="</ul><ul style=\"float:left; width:140px; margin-right:5px;\">";
                }
                $result.="
                                    <li><img src=\"https://www.slantour.cz/strpix/vlajky10/" . $zaznam["nazev_zeme_web"] . ".png\" alt=\"" . $zaznam["nazev_zeme"] . "\" height=\"10\" style=\"margin-top:4px;\" />
                                        <a href=\"/katalog/" . $zaznam["nazev_zeme_web"] . "\">" . $zaznam["nazev_zeme"] . "</a>
                                ";
                $i++;
            } else {
                if ($zaznam["nazev_zeme_web"] != "ceska-republika-vikendove-pobyty") {
                    if ($last_zeme != $zaznam["nazev_zeme_web"]) {
                        $last_zeme = $zaznam["nazev_zeme_web"];
                        if ($_SESSION["zeme"] == $zaznam["nazev_zeme_web"] and $_SESSION["destinace"] == "") {
                            $result.= "<option selected=\"selected\" value=\"" . $zaznam["nazev_zeme_web"] . "\" style=\"background:url(https://www.slantour.cz/strpix/vlajky10/" . $zaznam["nazev_zeme_web"] . ".png) no-repeat left;padding-left:20px;\">" . $zaznam["nazev_zeme"] . "</option>";
                        } else {
                            $result.= "<option value=\"" . $zaznam["nazev_zeme_web"] . "\" style=\"background:url(https://www.slantour.cz/strpix/vlajky10/" . $zaznam["nazev_zeme_web"] . ".png) no-repeat left;padding-left:20px;\">" . $zaznam["nazev_zeme"] . "</option>";
                        }
                    }
                    if ($zaznam["id_destinace"] != "") {
                        if ($_SESSION["zeme"] == $zaznam["nazev_zeme_web"] and $_SESSION["destinace"] == $zaznam["id_destinace"]) {
                            $result.= "<option selected=\"selected\" value=\"" . $zaznam["nazev_zeme_web"] . " " . $zaznam["id_destinace"] . "\">" . $zaznam["nazev_zeme"] . ": " . $zaznam["nazev_destinace"] . "</option>";
                        } else {
                            $result.= "<option value=\"" . $zaznam["nazev_zeme_web"] . " " . $zaznam["id_destinace"] . "\">" . $zaznam["nazev_zeme"] . ": " . $zaznam["nazev_destinace"] . "</option>";
                        }
                    }
                }
            }
        }
        if ($typ_zobrazeni == "list") {
            $result.="</ul>";
        }
        return $result;
    }

    /* metody pro pristup k parametrum */

    function get_vyprodane_serialy() {
        $this->vyprodane_serialy = array();
        $this->na_dotaz_serialy = array();
        $this->vyprodane_zajezdy = array();
        $this->na_dotaz_zajezdy = array();

        $dotaz = "select `serial`.`id_serial`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`na_dotaz`, `cena_zajezd`.`kapacita_volna`
            	    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`typ_ceny`=1 or `cena`.`typ_ceny`=2) ) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` )
                    where 1 
                    order by `serial`.`id_serial`,`zajezd`.`id_zajezd`";
        $data = $this->database->query($dotaz)
                or $this->chyba("Chyba pøi dotazu do databáze");

        $last_ser = "";
        $last_zaj = "";
        $vyprodano = 0;
        $na_dotaz = 0;
        $vyprodano_zajezd = 0;
        $na_dotaz_zajezd = 0;
        while ($zaznam = mysqli_fetch_array($data)) {
            //uprava poli
            if ($last_ser != $zaznam["id_serial"]) {
                if ($vyprodano) {
                    $this->vyprodane_serialy[] = $last_ser;
                } else if ($na_dotaz) {
                    $this->na_dotaz_serialy[] = $last_ser;
                }
                $vyprodano = 1;
                $na_dotaz = 1;
                $last_ser = $zaznam["id_serial"];
            }
            if ($last_zaj != $zaznam["id_zajezd"]) {
                if ($vyprodano_zajezd) {
                    $this->vyprodane_zajezdy[] = $last_zaj;
                } else if ($na_dotaz_zajezd) {
                    $this->na_dotaz_zajezdy[] = $last_zaj;
                }
                $vyprodano_zajezd = 1;
                $na_dotaz_zajezd = 1;
                $last_zaj = $zaznam["id_zajezd"];
            }

            //kontrola, zda je neco nevyprodane
            if ($zaznam["vyprodano"] != 1) {
                $vyprodano = 0;
                $vyprodano_zajezd = 0;
            }
            if ($zaznam["na_dotaz"] != 1) {
                $na_dotaz = 0;
                $na_dotaz_zajezd = 0;
            }
        }
        if ($last_ser != $zaznam["id_serial"]) {
            if ($vyprodano) {
                $this->vyprodane_serialy[] = $last_ser;
            } else if ($na_dotaz) {
                $this->na_dotaz_serialy[] = $last_ser;
            }
        }
        if ($last_zaj != $zaznam["id_zajezd"]) {
            if ($vyprodano_zajezd) {
                $this->vyprodane_zajezdy[] = $last_zaj;
            } else if ($na_dotaz_zajezd) {
                $this->na_dotaz_zajezdy[] = $last_zaj;
            }
        }
    }

    /* metody pro pristup k parametrum */

    function get_cena_vstupenek() {
        require_once "./classes/zajezd_vstupenky.inc.php"; //seznam serialu
        $vstup = new Seznam_vstupenek("v_cene", $this->radek["id_serial"]);
        return $vstup->get_sum_cena();
    }

    function get_nazev_zajezdu() {
        if ($this->radek["nazev_zajezdu"] != "") {
            return "<strong><i>" . $this->radek["nazev_zajezdu"] . "</i></strong> ";
        }
    }

    function get_id_serial() {
        return $this->radek["id_serial"];
    }

    function get_nazev() {
        if($this->radek["id_sablony_zobrazeni"] != 12){
            return $this->radek["nazev"];
        }else if ($this->radek["nazev_ubytovani"]) {
            return $this->radek["nazev_ubytovani"] . ", " . $this->radek["nazev"];
        }
        return $this->radek["nazev"];
    }

    function get_nazev_reverse() {
        if ($this->radek["nazev_ubytovani"]) {
            return $this->radek["nazev"] . ", " . $this->radek["nazev_ubytovani"];
        }
        return $this->radek["nazev"];
    }

    function get_nazev_web() {
        return $this->radek["nazev_web"];
    }

    function get_nazev_ubytovani() {
        return $this->radek["nazev_ubytovani"];
    }

    function get_nazev_ubytovani_web() {
        return $this->radek["nazev_ubytovani_web"];
    }

    function get_popisek() {
        return $this->radek["popisek"];
    }

    function get_popisek_ubytovani() {
        return $this->radek["popisek_ubytovani"];
    }

    function get_popisek_serialu() {
        return strip_tags($this->radek["popisek"], "<b><strong><a><br><br/>");
    }

    function get_dlouhodobe_zajezdy() {
        return $this->radek["dlouhodobe_zajezdy"];
    }

    function get_doprava() {
        if ($this->radek["doprava"] == 1) {
            return "Vlastní dopravou";
        } else if ($this->radek["doprava"] == 2) {
            return "Autokarem";
        } else if ($this->radek["doprava"] == 3) {
            return "Letecky";
        }
    }

    function get_doprava_web() {
        if ($this->radek["doprava"] == 1) {
            return "vlastni-doprava";
        } else if ($this->radek["doprava"] == 2) {
            return "autokarem";
        } else if ($this->radek["doprava"] == 3) {
            return "letecky";
        }
    }

    function get_id_zajezd() {
        return $this->radek["id_zajezd"];
    }

    function get_termin_od() {
        return $this->radek["od"];
    }

    function get_termin_do() {
        return $this->radek["do"];
    }

    function get_highlights() {
        return $this->radek["highlights"];
    }

    function get_cena_pred_akci() {
        return
                "<div >
                            pøed slevou: <span style=\"color:red;text-decoration:line-through;font-weight:bold;\">" .
                $this->radek["cena_pred_akci"] . " Kè</span></div>";
    }

    function get_akcni_cena() {
        return "<span style=\"color:#00ae35;font-size:1.2em;text-decoration:none;font-weight:bold;\">" .
                $this->radek["akcni_cena"] . " Kè</span>";
    }

    function get_sleva($zobrazit = "span") {
        $sleva = round(( 1 - ($this->radek["akcni_cena"] / $this->radek["cena_pred_akci"]) ) * 100);
        if ($zobrazit == "castka_only") {
            return $sleva;
        }
        return "<span style=\"color:red;font-size:1.1em;font-weight:bold;\" title=\" Sleva až " . $sleva . "% \">
                        SLEVA <span style=\"font-size:1.4em;\">" . $sleva . "%</span></span>";
    }

    function get_akcni_cena_param($cena) {
        return "<span style=\"color:#00ae35;font-size:1.2em;text-decoration:none;font-weight:bold;\">" .
                $cena . " Kè</span>";
    }

    function get_sleva_param($sleva) {

        return "<span style=\"color:red;font-size:1.1em;font-weight:bold;\" title=\" Sleva až " . $sleva . "% \">
                        SLEVA <span style=\"font-size:1.4em;\">" . $sleva . "%</span></span>";
    }

    function get_castka() {
        return $this->radek["castka"];
    }

    function get_mena() {
        return $this->radek["mena"];
    }

    function get_nazev_zeme() {
        if ($this->radek["nazev_zeme"] == "Èeská republika" or $this->radek["nazev_zeme"] == "Èeská republika, víkendové pobyty") {
            return "ÈR";
        } else {
            return $this->radek["nazev_zeme"];
        }
    }

    function get_nazev_zeme_web() {
        return $this->radek["nazev_zeme_web"];
    }

    function get_nazev_destinace() {
        return $this->radek["nazev_destinace"];
    }

    function get_id_destinace() {
        return $this->radek["id_destinace"];
    }

    function get_id_foto() {
        return $this->radek["id_foto"];
    }

    function get_foto_url() {
        return $this->radek["foto_url"];
    }

    function get_nazev_foto() {
        return $this->radek["nazev_foto"];
    }

    function get_popisek_foto() {
        return $this->radek["popisek_foto"];
    }

    function get_popis_akce() {
        return $this->radek["popis_akce"];
    }

    function get_id_ubytovani() {
        return $this->radek["id_ubytovani"];
    }

    function get_typ() {
        return $this->radek["nazev_typ_web"];
    }

    function get_podtyp() {
        return $this->radek["nazev_podtyp_web"];
    }

    function get_podtyp_text() {
        return $this->radek["podtyp"];
    }

    function get_sleva_castka() {
        return $this->radek["sleva_castka"];
    }

    function get_sleva_mena() {
        return $this->radek["sleva_mena"];
    }

    function get_sleva_nazev() {
        return $this->radek["sleva_nazev"];
    }

    function get_max_sleva_zajezd() {
        return $this->max_sleva_zajezd;
    }

    function get_pocet_zajezdu() {
        return $this->pocet_zajezdu;
    }

}

?>
