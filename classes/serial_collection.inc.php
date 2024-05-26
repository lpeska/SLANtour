<?php
/**
 * trida pro zobrazeni seznamu serialu
 */
/* --------------------- SEZNAM SERIALU ----------------------------- */
/* * jednodussi verze - vstupni parametry pouze typ, podtyp, zeme, zacatek vyberu a order by
  - odpovida dotazu z katalogu zajezdu
 */

class Serial_collection extends Generic_list {


    function __construct() {
        $this->database = Database::get_instance();
        $this->typ_pozadavku = $this->check($typ_pozadavku);      
    }   
    function execQuery(){                
        $this->data = $this->database->query($this->create_query()) or $this->chyba("Chyba při dotazu do databáze");
    }
    
    
    function get_all_zeme() {
        $query = 
                "select distinct zeme.id_zeme, zeme.nazev_zeme, zeme.nazev_zeme_web                     
                    from 
                    `zeme` join
                    `zeme_serial` on `zeme_serial`.id_zeme = `zeme`.id_zeme
                    join (`serial` join zajezd on 
                                            (zajezd.id_serial = serial.id_serial and 
                                            `zajezd`.`nezobrazovat_zajezd`<>1 and 
                                            `serial`.`nezobrazovat`<>1 and 
                                            (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1))
                         ))
                         on (zeme_serial.id_serial = serial.id_serial and
                            serial.nezobrazovat <> 1)
                    
                     where zeme.geograficka_zeme=1 order by nazev_zeme ";
        
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }       
    
    function get_all_destinace() {
        $query = 
                "select * from `destinace` ";
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }       

    
    function get_main_zeme_serial() {
        $query = 
            "select 
                distinct
                serial.id_serial as sId,
                `zeme_serial`.id_zeme as zId,
                `zeme`.nazev_zeme as zName
                from `zeme_serial` 
                    join zeme on `zeme_serial`.id_zeme = `zeme`.id_zeme
                    join (`serial` join zajezd on 
                            (zajezd.id_serial = serial.id_serial and 
                            zajezd.do >= '".Date("Y-m-d")."' and
                            zajezd.nezobrazovat_zajezd <> 1)    
                         )
                         on (zeme_serial.id_serial = serial.id_serial and
                            serial.nezobrazovat <> 1)
                    
                where `zeme_serial`.zakladni_zeme = 1";
        
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }        
    
    function get_all_zeme_serial() {
        $query = 
            "select 
                serial.id_serial as sId,
                GROUP_CONCAT(distinct `zeme_serial`.id_zeme order by `zeme_serial`.zakladni_zeme desc separator ',') as zId,
                GROUP_CONCAT(distinct `zeme`.nazev_zeme order by `zeme_serial`.zakladni_zeme desc separator ',') as zName
                from `zeme_serial` 
                    join zeme on `zeme_serial`.id_zeme = `zeme`.id_zeme
                    join (`serial` join zajezd on 
                            (zajezd.id_serial = serial.id_serial and 
                            zajezd.do >= '".Date("Y-m-d")."' and
                            zajezd.nezobrazovat_zajezd <> 1)    
                         )
                         on (zeme_serial.id_serial = serial.id_serial and
                            serial.nezobrazovat <> 1)
                    
                group by serial.id_serial";
        
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }      
    
    function get_all_destinace_serial() {
        $query = 
            "select 
                serial.id_serial as sId,
                GROUP_CONCAT(distinct `destinace_serial`.id_destinace order by `destinace_serial`.polozka_menu desc separator ',') as dId,
                GROUP_CONCAT(distinct `destinace`.nazev_destinace order by `destinace_serial`.polozka_menu desc separator ',') as dName
                from `destinace_serial` 
                    join destinace on `destinace_serial`.id_destinace = `destinace`.id_destinace
                    join (`serial` join zajezd on 
                            (zajezd.id_serial = serial.id_serial and 
                            zajezd.do >= '".Date("Y-m-d")."' and
                            zajezd.nezobrazovat_zajezd <> 1)    
                         )
                         on (destinace_serial.id_serial = serial.id_serial and
                            serial.nezobrazovat <> 1)
                    
                group by serial.id_serial";
        
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }     
    
    function get_tour_type_for_nazev_web($nazev) {
        $query = 
                "select * from `typ_serial` where nazev_typ_web = '".$nazev."' ";
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }      

    function get_all_sales_vol() {
        $query = 
                "select id_serial, count(id_objednavka) as pocet, sum(celkova_cena) as suma from `objednavka` 
                    where datum_rezervace >= '".Date("Y-m-d",strtotime("-18 months"))."'
                    group by id_serial
                    ";
        echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }       
    
    function get_all_tour_types() {
        $query = 
                "select distinct `typ_serial`.id_typ, nazev_typ, nazev_typ_web from `typ_serial` 
                                    join (`serial` join zajezd on 
                                            (zajezd.id_serial = serial.id_serial and 
                                            `zajezd`.`nezobrazovat_zajezd`<>1 and 
                                            `serial`.`nezobrazovat`<>1 and 
                                            (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1))
                                            )    
                                         )
                                         on (typ_serial.id_typ = serial.id_typ and
                                            serial.nezobrazovat <> 1);                   
                    ";
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }        

    function get_zajezdy_base() {
        //`objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`, `serial`.`nazev_web`,
        $query = 
                "select 
                    `serial`.`id_serial`,`serial`.`id_typ`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                    `objekt_ubytovani`.`nazev_ubytovani`,`zajezd`.`id_zajezd`,`zajezd`.`od`,`zajezd`.`do`,`cena_zajezd`.castka
                from `serial` left join
                 (`objekt_serial` join
                    `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                    `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                    ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   join
                `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join                    
                `cena` on (`cena`.`id_serial` = `zajezd`.`id_serial` and `cena`.`zakladni_cena` = 1) join
                `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 )   
                
                where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) )                    
                 ";
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }   
    
    function get_zajezdy_group() {
        //`objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`, `serial`.`nazev_web`,
        $query = 
                "select 
                    `serial`.`id_serial`,`serial`.`id_typ`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                    `objekt_ubytovani`.`nazev_ubytovani`,
                    concat(\"[\",GROUP_CONCAT( `zajezd`.`id_zajezd` ORDER BY `zajezd`.`id_zajezd` SEPARATOR ','),\"]\")  as `id_zajezd`, 
                    GROUP_CONCAT( `zajezd`.`od` ORDER BY `zajezd`.`id_zajezd` SEPARATOR ',')  as `od` ,
                    GROUP_CONCAT( `zajezd`.`do` ORDER BY `zajezd`.`id_zajezd` SEPARATOR ',')  as `do` ,
                    concat(\"[\",GROUP_CONCAT( `cena_zajezd`.castka ORDER BY `zajezd`.`id_zajezd` SEPARATOR ','),\"]\")  as `castka` 

                from `serial` left join
                 (`objekt_serial` join
                    `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                    `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                    ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   join
                `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join                    
                `cena` on (`cena`.`id_serial` = `zajezd`.`id_serial` and `cena`.`zakladni_cena` = 1) join
                `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 )   
                
                where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) )                    
                group by `serial`.`id_serial`

                ";
        //echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }         


    function get_all_dates_for_id_serial($id_serial) {
        $id_serial = intval($id_serial);
        $query = 
                "select 
                    `zajezd`.`id_zajezd`, od, do, nazev_zajezdu, castka, vyprodano
                from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1) 
                where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `serial`.`id_serial` =  $id_serial 
                    and (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) )
                 ";
        //echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }  
    
    function get_full_data_from_zajezdIDs($zajezdIDs) {
        $query = 
                "select 
                    `serial`.`id_serial`,`serial`.`id_typ`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,`serial`.`id_sablony_zobrazeni`,
                    `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,`objekt_ubytovani`.`posX` , `objekt_ubytovani`.`posY`,
                    `zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,`zajezd`.`cena_pred_akci`,`zajezd`.`akcni_cena`,
                    `zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`, coalesce(`destinace`.`nazev_destinace`) as nazev_destinace,
                    `typ_serial`.`nazev_typ`,
                    max(`cena_zajezd`.`castka`) as `max_castka`, min(`cena_zajezd`.`castka`) as `min_castka`,
                    greatest(
                            coalesce(max(1-((`zajezd`.`akcni_cena`+1)/(`zajezd`.`cena_pred_akci`+1)))*100,0),
                            coalesce(IF(max(`slevy`.`castka`)<100,max(`slevy`.`castka`),((max(`slevy`.`castka`)+1)/(avg(`cena_zajezd`.`castka`)+1))*100),0),
                            coalesce(IF(max(`slevy_zaj`.`castka`)<100,max(`slevy_zaj`.`castka`),((max(`slevy_zaj`.`castka`)+1)/(avg(`cena_zajezd`.`castka`)+1))*100),0)
                    ) as final_max_sleva, 
                    `foto`.`foto_url`

                from `zajezd` join
                    `serial` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `typ_serial` on (`typ_serial`.`id_typ` = `serial`.`id_typ`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and `cena`.`zakladni_cena`=1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 ) join                    
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
                    left join (
			`slevy` as  `slevy_zaj`
			 join `slevy_zajezd` on (`slevy_zajezd`.`id_slevy` = `slevy_zaj`.`id_slevy`)
			) on (`slevy_zajezd`.`id_zajezd` = `zajezd`.`id_zajezd`						  			
			  and (`slevy_zaj`.`platnost_od` = \"0000-00-00\" or `slevy_zaj`.`platnost_od`<=\"" . Date("Y-m-d") . "\" or `slevy_zaj`.`platnost_od` is null)
			  and (`slevy_zaj`.`platnost_do` = \"0000-00-00\" or `slevy_zaj`.`platnost_do`>=\"" . Date("Y-m-d") . "\" or `slevy_zaj`.`platnost_do` is null) )
                   

                where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and `zajezd`.`id_zajezd` in (".implode(",",$zajezdIDs).")  
                group by `zajezd`.`id_zajezd`
                 ";
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }  

    
    function get_zajezdy() {
        $query = 
                "select 
                    `serial`.`id_serial`,`serial`.`id_typ`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
                    `objekt_ubytovani`.`nazev_ubytovani`, `objekt_ubytovani`.`nazev_web` as `nazev_ubytovani_web`,`objekt_ubytovani`.`popis_poloha` as `popisek_ubytovani`,`objekt_ubytovani`.`posX` , `objekt_ubytovani`.`posY`,
                    `zajezd`.`id_zajezd`,`zajezd`.`nazev_zajezdu`,`zajezd`.`od`,`zajezd`.`do`,`zajezd`.`cena_pred_akci`,`zajezd`.`akcni_cena` 
                from `serial` left join
                 (`objekt_serial` join
                    `objekt` on (`objekt`.`typ_objektu`= 1 and `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) join
                    `objekt_ubytovani` on (`objekt`.`id_objektu` = `objekt_ubytovani`.`id_objektu`)
                    ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)   join
                `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                where `zajezd`.`nezobrazovat_zajezd`<>1 and `serial`.`nezobrazovat`<>1 and (`zajezd`.`od` >='" . Date("Y-m-d") . "' or (`zajezd`.`do` >'" . Date("Y-m-d") . "' and `serial`.`dlouhodobe_zajezdy`=1 ) )                    
                 ";
        #echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }    
    function get_ceny_zajezdu($valid_zajezdIDs) {
        $query = 
                "select cena.*, cena_zajezd.*
                from `zajezd` join                    
                    `cena` on (`cena`.`id_serial` = `zajezd`.`id_serial` and `cena`.`zakladni_cena` = 1) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 )                    

                where `cena_zajezd`.`id_zajezd` in (".implode(",", $valid_zajezdIDs).")                     
                 ";
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        #echo $query;        
        return $data;     
    }    
    function get_zeme_a_destinace_serialu($valid_serialIDs) {
        $query = 
                "select 
                    `zeme_serial`.`id_serial`,`zeme`.`id_zeme`,`zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`, destinace.id_destinace, destinace.nazev_destinace
                from 
                 `zeme_serial` join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`zeme_serial`.`id_serial` = `destinace_serial`.`id_serial` and `zeme`.`id_zeme` = `destinace`.`id_zeme`)
                    
                where `zeme_serial`.`id_serial` in (".implode(",", $valid_serialIDs).")                    
                 ";
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        #echo $query;        
        return $data; 
    }    
    function get_slevy_zajezdu($valid_zajezdIDs) {
        $query = 
                "TODO";
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;         
    }    

    static function nazev_web_static($vstup) {
        //need to change
        //vymenim hacky a carky
        $nazev_web = Str_Replace(
                Array("ä", "ë", "ö", "ü", "á", "č", "ď", "é", "ě", "í", "ľ", "ň", "ó", "ř", "š", "ť", "ú", "ů", "ý", "ž", "Ä", "Ë", "Ö", "Ü", "Á", "Č", "Ď", "É", "Ě", "Í", "Ľ", "Ň", "Ó", "Ř", "Š", "Ť", "Ú", "Ů", "Ý", "Ž"), Array("a", "e", "o", "u", "a", "c", "d", "e", "e", "i", "l", "n", "o", "r", "s", "t", "u", "u", "y", "z", "A", "E", "O", "U", "A", "C", "D", "E", "E", "I", "L", "N", "O", "R", "S", "T", "U", "U", "Y", "Z"), $vstup);
        $nazev_web = Str_Replace(Array(" ", "_", "/"), "-", $nazev_web); //nahradí mezery a podtržítka pomlčkami
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
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 ) join
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

    static function get_nazev($radek) {
        if($radek["id_sablony_zobrazeni"] != 12){
            return "".$radek["nazev"];
        }else if ($radek["nazev_ubytovani"]) {
            return $radek["nazev_ubytovani"] . ", " . $radek["nazev"];
        }
    }

    static function get_dates($radek) {
        $from = explode("-",$radek["od"]);
        $to = explode("-",$radek["do"]);
        
        if($from[0] == $to[0]){
            if($from[1] == $to[1]){
                if($from[2] == $to[2]){
                    return $from[2].".".$from[1].". ".$from[0];
                }else{
                    return $from[2].". - ".$to[2].".".$from[1].". ".$from[0];
                }
            }else{
                return $from[2].".".$from[1].". - ".$to[2].".".$to[1].". ".$from[0];
            }
        }else{
            return $from[2].".".$from[1].". ".$from[0]." - ".$to[2].".".$to[1].". ".$to[0];
        }
        
        if($radek["id_sablony_zobrazeni"] != 12){
            return $radek["nazev"];
        }else if ($radek["nazev_ubytovani"]) {
            return $radek["nazev_ubytovani"] . ", " . $radek["nazev"];
        }
    }

    

    function get_description($radek) {
        return strip_tags($radek["popisek"].$radek["popisek_ubytovani"]); //, "<b><strong><a><br><br/>"
    }

    function get_cena_pred_akci($radek) {
        return
                "<div >
                            před slevou: <span style=\"color:red;text-decoration:line-through;font-weight:bold;\">" .
                $this->radek["cena_pred_akci"] . " Kč</span></div>";
    }

    static function get_destinace($radek) {
        if ($radek["nazev_destinace"] != "") {
            return $radek["nazev_destinace"];
        } else {
            return $radek["nazev_zeme"];
        }
    }
    
    static function get_nights($radek) {
        if ($radek["dlouhodobe_zajezdy"] == "1") {
            return "variabilní";
        } else if ($radek["od"] == $radek["do"]){
            return "jednodenní";
        } else{
            $od = strtotime($radek["od"]);
            $do = strtotime($radek["do"]);
            /*if($od > $do){
                print_r([$od,$do,$radek["od"],$radek["do"]]);                
            }*/
            $datediff = $do - $od;
            $nights = round($datediff / (60 * 60 * 24));
            if($nights == 1){
                return strval($nights)." noc";
            }else if($nights <= 4){
                return strval($nights)." noci";
            }else{
                return strval($nights)." nocí";
            }
            
        }
    }    
    


}

?>
