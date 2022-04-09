<?php
/**
 * serial_list.inc.php - trida pro zobrazeni seznamu serialu
 */

/*------------------- SEZNAM serialu -------------------  */

class Serial_list extends Generic_list
{
    //vstupni data
    protected $moznosti_editace;

    protected $typ;
    protected $podtyp;
    protected $nazev;
    protected $zeme;
    protected $zacatek;
    protected $order_by;
    protected $pocet_zaznamu;
    protected $show_pokrocily_filtr;
    protected $show_prehled_zajezdu_filtr;
    protected $pocet_zajezdu;
    protected $zobrazit_zajezdy;

    public $database; //trida pro odesilani dotazu


    static function nazev_web_static($vstup)
    {
        //need to change
        //vymenim hacky a carky
        $nazev_web = Str_Replace(
            Array("ä", "ë", "ö", "ü", "á", "è", "ï", "é", "ì", "í", "¾", "ò", "ó", "ø", "š", "", "ú", "ù", "ý", "ž", "Ä", "Ë", "Ö", "Ü", "Á", "È", "Ï", "É", "Ì", "Í", "¼", "Ò", "Ó", "Ø", "Š", "", "Ú", "Ù", "Ý", "Ž"), Array("a", "e", "o", "u", "a", "c", "d", "e", "e", "i", "l", "n", "o", "r", "s", "t", "u", "u", "y", "z", "A", "E", "O", "U", "A", "C", "D", "E", "E", "I", "L", "N", "O", "R", "S", "T", "U", "U", "Y", "Z"), $vstup);
        $nazev_web = Str_Replace(Array(" ", "_", "/"), "-", $nazev_web); //nahradí mezery a podtržítka pomlèkami
        $nazev_web = Str_Replace(Array("(", ")", ".", "!", ",", "\"", "'", "*"), "", $nazev_web); //odstraní ().!,"'
        $nazev_web = StrToLower($nazev_web); //velká písmena nahradí malými.
        return $nazev_web;
    }

//------------------- KONSTRUKTOR  -----------------	
    /**konstruktor tøídy*/
    function __construct($typ, $podtyp, $nazev, $zeme, $zacatek, $order_by, $moznosti_editace = "", $pocet_zaznamu = POCET_ZAZNAMU, $typ_dotazu = "show")
    {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->moznosti_editace = $this->check($moznosti_editace);
        $this->typ = $this->check_int($typ);
        $this->podtyp = $this->check_int($podtyp);
        $this->nazev = $this->check($nazev);
        if(!is_array($zeme)){
            $this->zeme = $this->check_int($zeme);
        }else{
            $this->zeme = $_SESSION["serial_zeme"];
        }
        
        $this->zacatek = $this->check_int($zacatek);
        $this->order_by = $this->check($order_by);
        $this->pocet_zaznamu = $this->check_int($pocet_zaznamu);
        //nova verze filtru poporujici multiple selected values
        $this->typ_podtyp = $_SESSION["serial_typ_podtyp"];
        $this->doprava = $_SESSION["serial_doprava"];
    //    print_r($_SESSION);
        $this->zeme_condition = "";
        if(is_array($this->zeme)){
            $this->zeme_condition = "`zeme_serial`.`id_zeme` in (".str_replace(":","",implode(",", $this->zeme)).") and ";   
            if($this->zeme_condition == "`zeme_serial`.`id_zeme` in (0) and " ){
                $this->zeme_condition = "";
            }
            $this->zemeArray = $this->zeme;
            $this->zeme = "";
        }
        
        $this->doprava_condition = "";
        if(is_array($this->doprava)){
            $this->doprava_condition = "`serial`.`doprava` in (".implode(",", $this->doprava).") and "; 
            if($this->doprava_condition == "`serial`.`doprava` in (0) and " ){
                $this->doprava_condition = "";
            }
            $this->dopravaArray = $this->doprava;
            $this->doprava = "";
        }
        
        $this->typ_podtyp_condition = ""; 
        $tp_conditions = array();
        if(is_array($this->typ_podtyp)){              
            $this->typ = 0;
            $this->podtyp = 0;
            foreach ($this->typ_podtyp as $tp) {
                $tp_array = explode(":", $tp);
                $typ = $tp_array[0];
                $podtyp = $tp_array[1];
                if($typ>0){
                    if($podtyp>0){
                        $tp_conditions[] = "(`serial`.`id_podtyp`=$podtyp and `serial`.`id_typ`=$typ)";
                    }else{
                        $tp_conditions[] = "(`serial`.`id_typ`=$typ)";
                    }
                }
            }
            $this->typ_podtypArray = $this->typ_podtyp;
            if(sizeof((array)$tp_conditions)>0){
                $this->typ_podtyp_condition = "(".implode(" or ", $tp_conditions).") and ";
            }
        }

        if ($this->legal()) {
            if ($typ_dotazu == "show") {
                //ziskani seznamu z databaze
                $this->data = $this->database->query($this->create_query("show")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));

                //ziskam celkovy pocet zajezdu ktere odpovidaji
                $data_pocet = $this->database->query($this->create_query("show", 1)) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $zaznam_pocet = mysqli_fetch_array($data_pocet);
                $this->pocet_zajezdu = $zaznam_pocet["pocet"];

                //kontrola zda jsme ziskali nejake zajezdy
                if ($this->pocet_zajezdu == 0) {
                    $this->chyba("Pro zadané podmínky neexistuje žádný zájezd!");
                }
            } else if ($typ_dotazu == "serialy-slevy") {
                $this->data = $this->database->query($this->create_query("serialy-slevy")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //$data_pocet = $this->database->query($this->create_query("serialy-slevy", 1)) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                //$zaznam_pocet = mysqli_fetch_array($data_pocet);
                //$this->pocet_zajezdu = $zaznam_pocet["pocet"];
                $this->pocet_zajezdu = mysqli_num_rows($this->data) ; // for performance reasons
                if ($this->pocet_zajezdu == 0) {
                    $this->chyba("Pro zadané podmínky neexistuje žádný zájezd!");
                }
            } else if ($typ_dotazu == "serialy-dokumenty") {
                $this->data = $this->database->query($this->create_query("serialy-dokumenty")) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $data_pocet = $this->database->query($this->create_query("serialy-dokumenty", 1)) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $zaznam_pocet = mysqli_fetch_array($data_pocet);
                $this->pocet_zajezdu = $zaznam_pocet["pocet"];

                if ($this->pocet_zajezdu == 0) {
                    $this->chyba("Pro zadané podmínky neexistuje žádný zájezd!");
                }
            } else if ($typ_dotazu == "selector") {
                $this->data = $this->database->query($this->create_query("selector"))
                or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }
    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu ze zadanych parametru*/
    function create_query($typ_pozadavku, $only_count = 0)
    {
        if ($only_count == 1) {
            return "SELECT FOUND_ROWS() as pocet;";
        }
        //pokud chceme zobrazit seznam seriálù
        if ($typ_pozadavku == "show" || $typ_pozadavku == "selector") {

            //definice jednotlivych casti dotazu
            if ($this->typ != 0) {
                $where_typ = " `serial`.`id_typ` =" . $this->typ . " and";
            } else {
                $where_typ = "";
            }

            if ($this->podtyp != 0) {
                $where_podtyp = " `serial`.`id_podtyp` =" . $this->podtyp . " and";
            } else {
                $where_podtyp = "";
            }

            if ($this->nazev != "") {
                $where_serial = "  (`serial`.`nazev` like '%" . $this->nazev . "%' or `objekt`.`nazev_objektu` like '%" . $this->nazev . "%') and";
            } else {
                $where_serial = " ";
            }

            if($_SESSION["bez_spravce"] != "") {
                $where_bez_spravce = " serial.id_spravce = 0 &&";
            } else {
                $where_bez_spravce = " ";
            }

            if($_SESSION["bez_provize"] != "") {
                $where_bez_provize = " serial.typ_provize IS NULL &&";
            } else {
                $where_bez_provize = " ";
            }
            if($this->zeme_condition !="" or $this->zeme != ""){
                $from_zeme = " join `zeme_serial`	 on (`serial`.`id_serial`=`zeme_serial`.`id_serial`) ";
            }else{
                $from_zeme = " ";
            }                
            if ($this->zeme != "") {
                $where_zeme = " `zeme_serial`.`id_zeme` =" . $this->zeme . " and ";
            } else {                
                $where_zeme = " ";
            }

            if ($this->zacatek != 0) { //pocet_zaznamu ma default hodnotu -> nemel by byt prazdny
                $limit = " limit " . $this->zacatek . "," . $this->pocet_zaznamu . " ";
            } else {
                $limit = " limit 0," . $this->pocet_zaznamu . " ";
            }
            //pokrocile filtrovani bude pouze u pozadavku show
            $pokrocily_filtr = 0;
            $prehled_obsazenosti = 0;
            
            if($_SESSION["f_pokrocily_filtr"]==1){
                $pokrocily_filtr = 1;                
            }
            if($_SESSION["f_prehled_obsazenosti"]=="1"){
                $prehled_obsazenosti = 1;
            }
            if($prehled_obsazenosti){
                $this->show_prehled_zajezdu_filtr = true;
                $this->show_pokrocily_filtr = false;
            }else if($pokrocily_filtr){
                $this->show_pokrocily_filtr = true;
               $this->show_prehled_zajezdu_filtr = false; 
            }
            
            if ($typ_pozadavku == "show") {
                if($prehled_obsazenosti or $pokrocily_filtr){
                    if($_SESSION["f_zobrazit_zajezdy"]==1){

                        $from_zajezdy = "group_concat(`zajezd`.`id_zajezd` order by `zajezd`.`id_zajezd` separator \";\") as `id_zajezd`,
                                        group_concat(`zajezd`.`nazev_zajezdu` order by `zajezd`.`id_zajezd` separator \";\") as `nazev_zajezdu`,
                                        group_concat(`zajezd`.`od` order by `zajezd`.`id_zajezd` separator \";\") as `od`,
                                        group_concat(DISTINCT `objednavka`.`id_zajezd` order by `objednavka`.`id_zajezd` separator \";\") as `objednavka`,
                            ";
                        $this->zobrazit_zajezdy = true;
                    }else{
                        $from_zajezdy = "";
                    }
                    if($_SESSION["f_prehled_obsazenosti"]=="1"){
                        $having_objednavka = " count(`objednavka`.`id_objednavka`)>0 and";
                    }
                    if($_SESSION["f_zajezd_od"]!="" and $_SESSION["f_zajezd_od"]!="0000-00-00"){
                        $where_od = " and (`zajezd`.`od` >= \"".$_SESSION["f_zajezd_od"]."\" or (`zajezd`.`do` >= \"".$_SESSION["f_zajezd_od"]."\" and `serial`.`dlouhodobe_zajezdy`=1))  ";
                    }else{
                        $where_od = "";
                    }
                    if($_SESSION["f_zajezd_do"]!="" and $_SESSION["f_zajezd_do"]!="0000-00-00"){
                        $where_do = " and (`zajezd`.`do` <= \"".$_SESSION["f_zajezd_do"]."\"  or (`zajezd`.`od` <= \"".$_SESSION["f_zajezd_do"]."\" and `serial`.`dlouhodobe_zajezdy`=1))  ";
                    }else{
                        $where_do = "";
                    }
                    if( $_SESSION["f_serial_no_zajezd"]==1){
                        $having_zajezdy = " count(`zajezd`.`id_zajezd`)=0 and";
                    }else if($_SESSION["f_serial_aktivni_zajezd"]==1){
                        $where_od .= " and (`zajezd`.`od` >= \"".Date("Y-m-d")."\" or (`zajezd`.`do` >= \"".Date("Y-m-d")."\" and `serial`.`dlouhodobe_zajezdy`=1))  ";
                        $having_zajezdy = " count(`zajezd`.`id_zajezd`)>0 and";
                    }else{
                        $having_zajezdy = "";
                    }

                    if( $_SESSION["f_zajezd_objednavka"]==1){
                        $having_objednavka = " count(`objednavka`.`id_objednavka`)>0 and";
                    }else if($_SESSION["f_zajezd_no_objednavka"]==1){
                        $having_objednavka = " count(`objednavka`.`id_objednavka`)=0 and";
                    }else{
                        $having_objednavka = "";
                    }
                    if($_SESSION["f_zobrazit_zajezdy"]==1){
                        $select_zajezdy = "group_concat(DISTINCT`zajezd`.`id_zajezd` order by `zajezd`.`id_zajezd` separator \";\") as `id_zajezd`,
                                        group_concat(DISTINCT `zajezd`.`id_zajezd`,'_',`zajezd`.`nazev_zajezdu` order by `zajezd`.`id_zajezd` separator \";\") as `nazev_zajezdu`,
                                        group_concat(DISTINCT `zajezd`.`od` order by `zajezd`.`id_zajezd` separator \";\") as `od`,
                                        group_concat(DISTINCT `zajezd`.`do` order by `zajezd`.`id_zajezd` separator \";\") as `do`,
                                        group_concat(DISTINCT concat(`objednavka`.`id_zajezd`,' ',`objednavka`.`pocet_osob`,' ',`objednavka`.`stav`,' ',`objednavka`.`id_objednavka`) order by `objednavka`.`id_zajezd` separator \";\") as `objednavka_details`,
                            ";
                    }else{
                        $select_zajezdy = "";
                    }
                }    
            }
            if ($typ_pozadavku == "selector") {
                $limit = "";
            }
 
           if($prehled_obsazenosti == 1){
                 $select = "select SQL_CALC_FOUND_ROWS `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`id_sablony_zobrazeni`,
                                `zajezd`.*, count(distinct `objednavka`.`id_objednavka`) as `pocet_objednavek`,`objekt`.`nazev_objektu` as `nazev_ubytovani`,`objekt`.`id_objektu`,
                                group_concat(DISTINCT concat(`topologie_tok`.`id_topologie`,\":\",`topologie_tok`.`id_tok_topologie`) order by `topologie_tok`.`id_tok_topologie` separator \";\") as `id_topologie`,
                                group_concat(DISTINCT concat(`objednavka`.`id_zajezd`,' ',`objednavka`.`pocet_osob`,' ',`objednavka`.`stav`,' ',`objednavka`.`id_objednavka`) order by `objednavka`.`id_zajezd` separator \";\") as `objednavka_details`,                     
                                `typ_serial`.`nazev_typ`";               
            }else if($pokrocily_filtr==1){
                $select = "select SQL_CALC_FOUND_ROWS `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`id_sablony_zobrazeni`,
                                count(`zajezd`.`id_zajezd`) as `pocet_zajezdu`, count(`objednavka`.`id_objednavka`) as `pocet_objednavek`,
                                $select_zajezdy
                                group_concat(DISTINCT `objekt`.`nazev_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `nazev_ubytovani`,
                                group_concat(DISTINCT `objekt`.`id_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `id_objektu`,
                                `typ_serial`.`nazev_typ`";
            }else{
                $select = "select SQL_CALC_FOUND_ROWS `serial`.`id_serial`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`id_sablony_zobrazeni`, `serial`.`dlouhodobe_zajezdy`,
                                group_concat(DISTINCT `objekt`.`nazev_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `nazev_ubytovani`,
                                group_concat(DISTINCT `objekt`.`id_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `id_objektu`,
                                `typ_serial`.`nazev_typ`";
            }

            $order = $this->order_by($this->order_by);

            //tvorba vysledneho dotazu
            if($prehled_obsazenosti==1){
                mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET SESSION GROUP_CONCAT_MAX_LEN = 50000");
                $dotaz = $select . "
                    from `serial`
                     join `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`)
                     join `objednavka` on (`zajezd`.`id_zajezd` = `objednavka`.`id_zajezd`)
                    left join (`objekt_serial` join
                        `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu` = 1) ) 
                         on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)                              
                    left join (`zajezd_tok_topologie`  join
                        `topologie_tok` on ( `zajezd_tok_topologie`.`id_tok_topologie` = `topologie_tok`.`id_tok_topologie`) 
                         ) on (`zajezd`.`id_zajezd` = `zajezd_tok_topologie`.`id_zajezd`)                         
                    " . $from_zeme . "
                    join `typ_serial`	 on (`serial`.`id_typ`=`typ_serial`.`id_typ`)
                    where " . $where_typ . $where_podtyp . $where_serial . $where_zeme . $where_bez_spravce . $where_bez_provize . $where_od . $where_do .$this->zeme_condition.$this->doprava_condition.$this->typ_podtyp_condition. " 1
                    group by `zajezd`.`id_zajezd`
                    having $having_objednavka 1
                    order by " . $order . "
                     " . $limit . "";
                 //echo "$dotaz<br/>";
            }else if($pokrocily_filtr==1){
                mysqli_query($GLOBALS["core"]->database->db_spojeni,"SET SESSION GROUP_CONCAT_MAX_LEN = 50000");
                $dotaz = $select . "
                    from `serial`
                    left join `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial` $where_od $where_do)
                    left join `objednavka` on (`zajezd`.`id_zajezd` = `objednavka`.`id_zajezd`)
                    left join (`objekt_serial` join
                        `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)                              
                    " . $from_zeme . "
                    join `typ_serial`	 on (`serial`.`id_typ`=`typ_serial`.`id_typ`)
                    where " . $where_typ . $where_podtyp . $where_serial . $where_zeme . $where_bez_spravce . $where_bez_provize . $this->zeme_condition.$this->doprava_condition.$this->typ_podtyp_condition. " 1
                    group by `serial`.`id_serial`
                    having $having_zajezdy $having_objednavka 1
                    order by " . $order . "
                     " . $limit . "";
               //  echo "$dotaz<br/>";
            }else {
                $dotaz = $select . "
                    from `serial`
                    left join (`objekt_serial` join
                        `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)                              
                            " . $from_zeme . "
                            join `typ_serial`	 on (`serial`.`id_typ`=`typ_serial`.`id_typ`)
                    where " . $where_typ . $where_podtyp . $where_serial . $where_zeme . $where_bez_spravce . $where_bez_provize .$this->zeme_condition.$this->doprava_condition.$this->typ_podtyp_condition. " 1
                    group by `serial`.`id_serial`
                    order by " . $order . "
                     " . $limit . "";
            }
           // echo $this->zeme_condition.$this->doprava_condition.$this->typ_podtyp_condition;
           // echo "$dotaz<br/>";
            

            echo "$dotaz<br/>";
            return $dotaz;

            //vypis vsech typu a podtypu zajezdu - potreba pro filtry
        } else if ($typ_pozadavku == "typ") {
            $dotaz = "select `typ`.`nazev_typ`,`typ`.`id_typ`,
								`podtyp`.`nazev_typ` as `nazev_podtyp`,`podtyp`.`id_typ` as `id_podtyp`
							 from `typ_serial` as `typ` 
							 left join `typ_serial` as `podtyp` on (`typ`.`id_typ`=`podtyp`.`id_nadtyp`) where `typ`.`id_nadtyp`=0";

            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "serialy-slevy") {
            if ($only_count == 1) {
                $select = "COUNT(distinct s.id_serial) AS pocet";
            } else {
                $select = "s.id_serial, s.nazev, ts.nazev_typ,
                group_concat(DISTINCT `objekt`.`nazev_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `nazev_ubytovani` ";
            }

            //SQL INJECTION!! 
            //ted uz nehrozi, idcka jsou vzdycky integery, $this->check_int pouziva intval, takze tam pripadne hodi nulu, pokud tam bude neco jineho nez cislo
            $id_slevy = $this->check_int($_GET["id_slevy"]);
            $dotaz = "SELECT $select
                            FROM slevy_serial ss
                            JOIN serial s ON ss.id_serial = s.id_serial
                            left join (`objekt_serial` join
                             `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) ) on (`s`.`id_serial` = `objekt_serial`.`id_serial` and objekt.typ_objektu = 1)                              

                            LEFT JOIN typ_serial ts ON s.id_typ = ts.id_typ
                            WHERE ss.id_slevy = $id_slevy
                            group by s.id_serial    
                    ";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "serialy-dokumenty") {
            $select = "s.id_serial, s.nazev, ts.nazev_typ,
                group_concat(DISTINCT `objekt`.`nazev_objektu` order by `objekt`.`nazev_objektu` separator \";\") as `nazev_ubytovani` ";

            //SQL INJECTION!!
             //ted uz nehrozi, idcka jsou vzdycky integery, $this->check_int pouziva intval, takze tam pripadne hodi nulu, pokud tam bude neco jineho nez cislo
            $id_dokument = $this->check_int($_GET["id_dokument"]);
            $dotaz = "SELECT $select
                            FROM dokument_serial ds
                                JOIN serial s ON ds.id_serial = s.id_serial
                                left join (`objekt_serial` join
                                `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) ) on (`s`.`id_serial` = `objekt_serial`.`id_serial` and objekt.typ_objektu = 1)                              

                                LEFT JOIN typ_serial ts ON s.id_typ = ts.id_typ
                            WHERE ds.id_dokument = $id_dokument
                             group by s.id_serial     
                    ";
            return $dotaz;
        }
    }

    /**na zaklade textoveho vstupu vytvori korektni cast retezce pro order by*/
    function order_by($vstup)
    {
        switch ($vstup) {
            case "id_up":
                return "`serial`.`id_serial`";
                break;
            case "id_down":
                return "`serial`.`id_serial` desc";
                break;
            case "typ_up":
                return "`typ_serial`.`nazev_typ`,`serial`.`id_podtyp`,`serial`.`nazev`";
                break;
            case "typ_down":
                return "`typ_serial`.`nazev_typ` desc ,`serial`.`id_podtyp` desc,`serial`.`nazev` desc";
                break;
            case "nazev_up":
                return "`serial`.`nazev`";
                break;
            case "nazev_down":
                return "`serial`.`nazev` desc";
                break;
            case "ubytovani_up":
                return "`objekt`.`nazev_objektu`";
                break;
            case "ubytovani_down":
                return "`objekt`.`nazev_objektu` desc";
                break;
            case "termin_up":
                return "`zajezd`.`od`,`zajezd`.`do`";
                break;
            case "termin_down":
                return "`zajezd`.`od` desc,`zajezd`.`do` desc";
                break;
            case "objednavky_up":
                if($this->show_prehled_zajezdu_filtr){
                    return "`pocet_objednavek`";
                    break;
                }
            case "objednavky_down":
                if($this->show_prehled_zajezdu_filtr){
                    return "`pocet_objednavek` desc";
                    break;      
                }    
        }
        //pokud zadan nespravny vstup, vratime zajezd.od
        return "`serial`.`id_serial`";
    }

    /* zobrazi filtry, ktere byly aplikovane - pro TS prehled obsazenosti*/
    function getAplikovaneFiltry(){
        $filtr = $this->show_filtr();
        
        $html = "<table>
                  <tr>
                    <th class=\"border0\">Typ seriálu
                    <th class=\"border0\">Doprava
                    <th class=\"border0\">Zemì
                    <th class=\"border0\">Název seriálu/ubytování
                    <th class=\"border0\">Termíny zájezdu
                    <th class=\"border0\">
                    ";
        
        $html .= "
                  <tr>
                    <td class=\"border0\">$this->typy_serialu_selected 
                    <td class=\"border0\">$this->doprava_selected 
                    <td class=\"border0\">$this->zeme_selected
                    <td class=\"border0\">$this->nazev
                    <td class=\"border0\">od: ".CommonUtils::czechDate($_SESSION["f_zajezd_od"])." <br/>do:".CommonUtils::czechDate($_SESSION["f_zajezd_do"])."
                    <td class=\"border0\">".($_SESSION["bez_spravce"] == 1 ? "BEZ SPRÁVCE" : "")." ".($_SESSION["bez_provize"] == 1 ? " BEZ PROVIZE" : "")."
                 </table>    ";
       
        return $html;
    }

    
     /**zobrazi formular pro filtorvani vypisu serialu*/
   static  function show_filtr_hlaseni_pojistovne()
    {


        $terminy = "
            <td >Zájezd od: <input type=\"text\" id=\"zajezd_termin_od\" name=\"zajezd_termin_od\" value=\"".CommonUtils::czechDate($_SESSION["zajezd_termin_od"])."\" class=\"calendar-ymd\" />
                do: <input type=\"text\"  id=\"zajezd_termin_do\" name=\"zajezd_termin_do\" value=\"".CommonUtils::czechDate($_SESSION["zajezd_termin_do"])."\"  class=\"calendar-ymd\" />
            <td >Objednávky od: <input type=\"text\" id=\"objednavka_termin_od\" name=\"objednavka_termin_od\" value=\"".CommonUtils::czechDate($_SESSION["objednavka_termin_od"])."\" class=\"calendar-ymd\" />
                do: <input type=\"text\"  id=\"objednavka_termin_do\" name=\"objednavka_termin_do\" value=\"".CommonUtils::czechDate($_SESSION["objednavka_termin_do"])."\"  class=\"calendar-ymd\" />                
            <td  >Vypsat i seznam objednávek: <input type=\"checkbox\"  id=\"zobrazit_objednavky\" name=\"zobrazit_objednavky\" value=\"1\" ".($_SESSION["zobrazit_objednavky"]==1?("checked=\"checked\""):(""))."/>
            <td  >Zahrnout pouze zájezdy dle zákona è. 159: <input type=\"checkbox\"  id=\"zajezdy_dle_zakona\" name=\"zajezdy_dle_zakona\" value=\"1\" ".($_SESSION["zajezdy_dle_zakona"]==1?("checked=\"checked\""):(""))." />";
         


//count_zajezdy, count_active_zajezdy, logika = or

        $create_pdf = "<input type=\"submit\" name=\"submit\" value=\"Zobrazit realizované zájezdy\" /><input type=\"submit\" name=\"submit\" value=\"Zobrazit nové objednávky\" />";
        

        $vystup = "
                <form method=\"post\" action=\"?typ=hlaseni_pojistovne&amp;pozadavek=change_filter\">
                <table class=\"filtr\">
                        <tr>$terminy
                        </tr>
                        <tr><td colspan=\"4\">$create_pdf                                          
                </table>
                </form>
        ";
        return $vystup;

    }

    
    /**zobrazi formular pro filtorvani vypisu serialu*/
    function show_filtr()
    {
        //promenne, ktere musime pridat do odkazu
        $vars = "&amp;moznosti_editace=" . $this->moznosti_editace;
        if ($this->moznosti_editace == "objednavka_change_serial") {
            if ($_REQUEST["storno_poplatek_zmena"] == 0) {
                $_REQUEST["storno_poplatek_zmena"] = $this->check_int($_REQUEST["storno"]);
            }
            $vars .= "&amp;id_objednavka=" . $_GET["id_objednavka"] . "&amp;id_klient=" . $_REQUEST["id_klient"] . "&amp;storno=" . $_REQUEST["storno_poplatek_zmena"] . "";
        }

        //tvroba input nazev
        $input_nazev = "<input name=\"nazev\" type=\"text\" value=\"" . $this->nazev . "\" />";
        //tlacitko pro odeslani
        $submit = "<input type=\"submit\" value=\"Zmìnit filtrování\" id=\"defaultSubmitButton\" /><input type=\"reset\" value=\"Pùvodní hodnoty\" />";

        //do promenne typy_serialu vytvorim instanci tridy seznam typu serialu a nasledne vypisu seznam typu
        $typy_zeme = new Zeme_list($this->id_zamestnance, "", $this->zemeArray, "");
        //pokud nastala chyba, zastavim praci
        if ($typy_zeme->get_error_message()) {
            $this->chyba($typy_zeme->get_error_message());
            $zeme = "";
        } else {
            //vypisu seznam typu a podtypu
            $zeme = "<select name=\"zeme[]\"  onchange=\"highlight_options(this)\" multiple style=\"height:150px\">\n<option value=\"0\">--libovolná--</option>\n";
            $zeme = $zeme . $typy_zeme->show_list("select_zeme");
            $zeme = $zeme . "</select>\n\n";
        }
        if($this->show_pokrocily_filtr){
            $filter_display = "block";
        }else{
            $filter_display = "none";
        }
        if($this->show_prehled_zajezdu_filtr){
            $filter_display_pz = "block";
        }else{
            $filter_display_pz = "none";
        }

        $control_extended_filtering = "<td><div class=\"submenu\" style=\"line-height:15px;padding:1px;background-color:#f5f5f5;\"><a href=\"#\" onclick=\"show_filtr();\">Použít pokroèilé filtry </a><a href=\"#\" onclick=\"show_filtr_prehled_zajezdu();\">Pøehled objednávek</a></div>";
         //zajezdy v AND formátu vùèi objednávkám, aktivní zájezdy se budou poèítat také s AND
        $terminy = "<td class='vybery-snaz' >Zájezd od: <input type=\"text\" id=\"f_zajezd_od\" name=\"f_zajezd_od\" value=\"".CommonUtils::czechDate($_SESSION["f_zajezd_od"])."\" onchange=\"filtr_change_zobrazit_zajezdy();\" class=\"calendar-ymd\" id=\"datepicker_zajezd_od\"/>
            <td class='vybery-snaz' >do: <input type=\"text\"  id=\"f_zajezd_do\" name=\"f_zajezd_do\" value=\"".CommonUtils::czechDate($_SESSION["f_zajezd_do"])."\" onchange=\"filtr_change_zobrazit_zajezdy();\" class=\"calendar-ymd\" id=\"datepicker_zajezd_do\"/>";
        //count_zajezdy, count_active_zajezdy, logika = or
        $zajezdy = "<td style=padding:5px;>
                <input type=\"hidden\" name=\"pokrocile_filtry\" id=\"pokrocile_filtry_switch\" value=\"".($this->show_pokrocily_filtr>0?"1":"-1")."\">
                Zobrazit také zájezdy <input type='checkbox' id='f_zobrazit_zajezdy' name='f_zobrazit_zajezdy' value='1' ".($_SESSION["f_zobrazit_zajezdy"]==1?(" checked=\"checked\""):(""))." /></td>
            $terminy
           <td class='vybery-snz' > 0 zájezdù <input type='checkbox' name='f_serial_no_zajezd' value='1' ".($_SESSION["f_serial_no_zajezd"]==1?(" checked=\"checked\""):(""))." /></td>
            <!-- <td class='vybery-snaz' > 0 aktivních zájezdù <input type='checkbox' name='f_serial_no_aktivni_zajezd' value='1'  ".($_SESSION["f_serial_no_aktivni_zajezd"]==1?(" checked=\"checked\""):(""))." /></td>-->
            <td class='vybery-sak' >1+ aktivní zájezd <input type='checkbox' name='f_serial_aktivni_zajezd' value='1' onchange=\"filtr_change_zobrazit_zajezdy();\" ".($_SESSION["f_serial_aktivni_zajezd"]==1?(" checked=\"checked\""):(""))." /> </td>     ";
       $zajezdy_pz = "<td style=padding:5px;><input type=\"hidden\" name=\"prehled_zajezdu\" id=\"prehled_zajezdu_switch\" value=\"".($this->show_prehled_zajezdu_filtr>0?"1":"-1")."\">
            <td class='vybery-snaz' >Zájezd od: <input type=\"text\" id=\"f_zajezd_od_pz\" name=\"f_zajezd_od_pz\" value=\"".CommonUtils::czechDate($_SESSION["f_zajezd_od"])."\" class=\"calendar-ymd\" id=\"datepicker_zajezd_od\"/>
            <td class='vybery-snaz' >do: <input type=\"text\" id=\"f_zajezd_do_pz\" name=\"f_zajezd_do_pz\" value=\"".CommonUtils::czechDate($_SESSION["f_zajezd_do"])."\" class=\"calendar-ymd\" id=\"datepicker_zajezd_do\"/> ";
        
        //count_objednavky, logika = or
        $objednavky = "<td class='vybery-zso' >1+ objednávka <input type='checkbox' name='f_zajezd_objednavka' value='1' onchange=\"filtr_change_zobrazit_zajezdy();\" ".($_SESSION["f_zajezd_objednavka"]==1?(" checked=\"checked\""):(""))." />
            <td class='vybery-zbo'>0 objednávek <input type='checkbox' name='f_zajezd_no_objednavka' value='1' onchange=\"filtr_change_zobrazit_zajezdy();\" ".($_SESSION["f_zajezd_no_objednavka"]==1?(" checked=\"checked\""):(""))." />";
 
        $seradit = "<td class='vybery-zso' >Øazení <select name=\"razeni\">
                <option value=\"\">---</option>
                <option value=\"id_up\" ".($this->order_by=="id_up" ? "selected=\"selected\"" : "").">Id seriálu vzestupnì</option>
                <option value=\"id_down\" ".($this->order_by=="id_down" ? "selected=\"selected\"" : "").">Id seriálu sestupnì</option>
                <option value=\"nazev_up\" ".($this->order_by=="nazev_up" ? "selected=\"selected\"" : "").">Název seriálu vzestupnì</option>
                <option value=\"nazev_down\" ".($this->order_by=="nazev_down" ? "selected=\"selected\"" : "").">Název seriálu sestupnì</option>
                <option value=\"termin_up\" ".($this->order_by=="termin_up" ? "selected=\"selected\"" : "").">Termín zájezdu vzestupnì</option>
                <option value=\"termin_down\" ".($this->order_by=="termin_down" ? "selected=\"selected\"" : "").">Termín zájezdu sestupnì</option>
                <option value=\"objednavky_up\" ".($this->order_by=="objednavky_up" ? "selected=\"selected\"" : "").">Poèet objednávek vzestupnì</option>
                <option value=\"objednavky_down\" ".($this->order_by=="objednavky_down" ? "selected=\"selected\"" : "").">Poèet objednávek sestupnì</option>                
            </select>";
        $create_pdf = "<input type=\"submit\" name=\"submit\" value=\"Vygenerovat PDF\" />";
        
         //typy dopravy
        $doprava = "<select name=\"doprava[]\"  onchange=\"highlight_options(this)\" multiple style=\"height:150px\">\n<option value=\"0\">--libovolná--</option>\n";
        $i = 0;
        $dopravaSelected = "";
        while(Serial_library::get_typ_dopravy($i)!=""){
            if(is_array($this->dopravaArray) and in_array(($i+1), $this->dopravaArray)){
                $select = " selected=\"selected\" class=\"selected\"";
                $dopravaSelected .= Serial_library::get_typ_dopravy($i).",";
            }else{
                $select = "";
            }
            $doprava .= "<option value=\"".($i+1)."\" $select>".Serial_library::get_typ_dopravy($i)."</option>\n";
            $i++;
        }
        $doprava .= "</select>";
        
        
        
        //tvorba select typ-podtyp
//chybi id_typ a id_podtyp
        
        //do promenne typy_serialu vytvorim instanci tridy seznam typu serialu a nasledne vypisu seznam typu
        $typy_serialu = new Typ_list($this->id_zamestnance, "", $_SESSION["serial_typ"], $_SESSION["serial_podtyp"], $_SESSION["serial_typ_podtyp"]);

        //pokud nastala chyba, zastavim praci
        if ($typy_serialu->get_error_message()) {
            $this->chyba($typy_serialu->get_error_message());
            $typ = "";
        } else {
            //vypisu seznam typu a podtypu
            $typ = "<select name=\"typ-podtyp[]\"  onchange=\"highlight_options(this)\" multiple style=\"height:150px\">\n<option value=\"0:0\">--libovolný--</option>\n";
            $typ = $typ . $typy_serialu->show_list("select_typ_podtyp");
            $typ = $typ . "</select>\n\n";
        }
        //vysledny formular
        $this->typy_serialu_selected = $typy_serialu->get_selected();
        $this->doprava_selected = $dopravaSelected;
        $this->zeme_selected = $typy_zeme->get_selected();
        $vystup = "
				<form method=\"post\" action=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=typ-podtyp-nazev" . $vars . "\">
				<table class=\"filtr\">
                                        <tr><td>Typ/podtyp: ".$typy_serialu->get_selected()."<td>Doprava: $dopravaSelected<td>Zemì: ".$typy_zeme->get_selected()."<td>Ubytování/Název:
					<tr valign=\"top\">
						<td> " . $typ . "</td>
                                                <td> " . $doprava . "</td>    
						<td> " . $zeme . "</td>
						<td> " . $input_nazev . "</td>
                        <td><input type='checkbox' name='bez_spravce' id='bez_spravce' value='1' ".($_SESSION["bez_spravce"] == 1 ? "checked" : "")."><label for='bez_spravce'> Bez správce</label></td>
                        <td><input type='checkbox' name='bez_provize' id='bez_provize' value='1' ".($_SESSION["bez_provize"] == 1 ? "checked" : "")."><label for='bez_provize'> Bez provize</label></td>
					</tr>
                                        <tr>$control_extended_filtering
                                        <tr >
                                        <table class=\"filtr\" id=\"prehled_zajezdu\" style=\"display:$filter_display_pz;\"><tr>$zajezdy_pz $seradit $create_pdf</table>
                                        <table class=\"filtr\" id=\"pokrocily_filtr\" style=\"display:$filter_display;\"><tr>$zajezdy $objednavky </table>
                                        <tr><td>" . $submit . "</td>    
                                </table>
				</form>
			";
        return $vystup;

    }

    /**zobrazi nadpis seznamu*/
    function show_header()
    {
        if ($this->moznosti_editace == "select_serial_objednavky" or $this->moznosti_editace == "objednavka_change_serial") {
            $vystup = "
				<h3>Vyberte seriál objednávky</h3>
			";
        } else {
            $vystup = "
				<h3>Seznam seriálù</h3>
			";
        }
        return $vystup;
    }

        /**zobrazi nadpis seznamu*/
    function createHTMLTSPrehledObjednavek()
    {
        $html = "<body>
            <table cellpadding=\"0\" cellspacing=\"0\" class=\"border2\" style=\"border-collapse: collapse;\"  width=\"810\">
            <tr>
                <th colspan=\"2\" style=\"padding-left:0;padding-right:0;\">
   			<h1>PØEHLED OBSAZENOSTI ZÁJEZDÙ</h1></br>
                </th>
            </tr>
            <tr>	   
		<th  valign=\"top\" >
                    Aplikované filtry:
		</th>	
		
		<td  valign=\"top\"   >
                    " . $this->getAplikovaneFiltry() . "
                </td>	
        
            </tr>
            </table>
            
            <table cellpadding=\"0\" cellspacing=\"0\" class=\"border2\" style=\"border-collapse: collapse;margin-top:10px;\"  width=\"810\">		
                    ".$this->show_list_header()."";
       while($this->get_next_radek()){
                $html .= $this->show_list_item("tabulka_ts_prehled_objednavek");
       }
        
       $html .= " 
		
        </table>  
        </body>
        ";
       
       return $html;
    }
    /**zobrazi hlavicku k seznamu seriálù*/
    function show_list_header()
    {
        if (!$this->get_error_message()) {
            //promenne, ktere musime pridat do odkazu
            $vars = "&amp;moznosti_editace=" . $this->moznosti_editace;
            if ($this->moznosti_editace == "objednavka_change_serial") {
                if ($_REQUEST["storno_poplatek_zmena"] == 0) {
                    $_REQUEST["storno_poplatek_zmena"] = $this->check_int($_REQUEST["storno"]);
                }
                $vars .= "&amp;id_objednavka=" . $_GET["id_objednavka"] . "&amp;id_klient=" . $_REQUEST["id_klient"] . "&amp;storno=" . $_REQUEST["storno_poplatek_zmena"] . "";
            }
            if($this->show_prehled_zajezdu_filtr){
                //zobrazujeme prehled objednavek, vypis bude trochu jiny
                if($this->moznosti_editace=="zadne"){
                      $vystup = "
				
					<tr>
						<th class=\"border1 border2b\" rowspan=\"2\">Id</th>
						<th class=\"border1 border2b\"  rowspan=\"2\">Seriál</th>
						<th class=\"border1 border2b\"  rowspan=\"2\">Zájezd</th>
						<th class=\"border1\"  colspan=\"6\">Poèty Osob</th>
                                                <th class=\"border1\"  colspan=\"6\">Poèty Objednávek</th>
                                                <th class=\"border1 border2b\"  rowspan=\"2\">Topologie</th>
						</th>
					</tr>
                                        <tr>
						
                                                <td class=\"border1lMP border1rDotted  border2b\">Op</td>
                                                <td class=\" border1rDotted border2b\">Rez</td>
                                                <td class=\" border1rDotted border2b\">Zal</td>
                                                <td class=\" border1rDotted border2b\">Prod</td>
                                                <td class=\" border1rDotted border2b\"><b>TOT</b></td>
                                                <td class=\"border1rMP  border2b\">St</td>
                                                
                                                
                                                <td class=\"border1lMP border1rDotted  border2b\">Op</td>
                                                <td class=\" border1rDotted border2b\">Rez</td>
                                                <td class=\" border1rDotted border2b\">Zal</td>
                                                <td class=\" border1rDotted border2b\">Prod</td>
                                                <td class=\" border1rDotted border2b\"><b>TOT</b></td>
                                                <td class=\"border1rMP  border2b\">St</td>
					</tr>
			"; 
                }else{
                     $vystup = "
				<table class=\"list\">
					<tr>
						<th>Id</th>
						<th>Seriál</th>
						<th>Zájezd</th>
						<th>Poèty Osob</th>
                                                <th>Poèty Objednávek</th>
                                                <th>Topologie</th>
						<th>Možnosti editace
						</th>
					</tr>
			"; 
                }
              
            }else{
            $vystup = "
				<table class=\"list\">
					<tr>
						<th>id
						<div class='sort'>
							<a class='sort-up' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=id_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Typ seriálu
						<div class='sort'>
							<a class='sort-up' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=typ_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=typ_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Objekty
						<div class='sort'>
							<a class='sort-up' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=ubytovani_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=ubytovani_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Název seriálu
						<div class='sort'>
							<a class='sort-up' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_up" . $vars . "\"></a>
							<a class='sort-down' href=\"?typ=serial_list&amp;pozadavek=change_filter&amp;pole=ord_by&amp;ord_by=nazev_down" . $vars . "\"></a>
							</div>
						</th>
						<th>Možnosti editace
						</th>
					</tr>
			";
            }
            return $vystup;
        }
    }

    /*z*obrazi jeden zaznam serialu v zavislosti na zvolenem typu zobrazeni*/
    function show_list_item($typ_zobrazeni)
    {
        if (!$this->get_error_message()) {
            $core = Core::get_instance();
            $current_modul = $core->show_current_modul();
            $adresa_modulu = $current_modul["adresa_modulu"];

            if ($typ_zobrazeni == "tabulka" or $typ_zobrazeni == "tabulka_slevy" or $typ_zobrazeni == "tabulka_ts_prehled_objednavek") {
                if ($typ_zobrazeni == "tabulka_slevy") {
                    $adresa_modulu = "serial.php";
                }
                if($this->show_pokrocily_filtr){
                    //serialy a zajezdy nalezite obarvime
                    if($this->radek["pocet_zajezdu"]==0){
                        $vypis = "<tr class=\"vybery-snz\">";
                    }else if($this->radek["pocet_objednavek"]==0){
                        $vypis = "<tr class=\"vybery-sno\">";
                    }else{
                        $vypis = "<tr class=\"vybery-sso\">";
                    }
                }else if($typ_zobrazeni == "tabulka_ts_prehled_objednavek") {
                        $vypis = "<tr>";
                   
                }else{
                    if ($this->suda == 1) {
                        $vypis = "<tr class=\"suda\">";
                    } else {
                        $vypis = "<tr class=\"licha\">";
                    }
                }
                $ubyt = "";
                if ($this->radek["nazev_ubytovani"] != "") {                    
                    $array_objekty = explode(";", $this->radek["nazev_ubytovani"]);
                    $array_id_objektu = explode(";", $this->radek["id_objektu"]);
                    foreach ($array_objekty as $key => $value) {
                        $ubyt .= "<a href=\"/admin/objekty.php?id_objektu=" . $array_id_objektu[$key] . "&typ=objekty&pozadavek=edit\">" . $value . "</a><br/>";
                    }
                } else {
                    $ubyt = "";
                }
                $topologie = "";
                if ($this->radek["id_topologie"] != "") {                    
                    $array_topologie = explode(";", $this->radek["id_topologie"]);
    
                    foreach ($array_topologie as $tp) {
                        $topo = explode(":", $tp);
                         $topologie .= "<a href=\"/admin/topologie_objektu.php?id_serial=".$this->radek["id_serial"]."&id_zajezd=".$this->radek["id_zajezd"]."&id_topologie=".$topo[0]."&id_tok_topologie=".$topo[1]."&typ=topologie&pozadavek=zasedaci_poradek\">Topologie ".$topo[1]."</a><br/>";        
                    }
                } else {
                    $topologie = "";
                }
                if($this->zobrazit_zajezdy or $this->show_prehled_zajezdu_filtr){
                    //sestavime seznam objednavek k zajezdum
                    $obj = array();
                    $pocet = array();
                    $objednavky_detaily = explode(";", $this->radek["objednavka_details"] );
                    foreach ($objednavky_detaily as $key => $value) {
                        $objednavka_array = explode(" ",$value);
                        //[0]=id_zajezdu, [1]=pocet_osob, [2]=stav_objednavky
                        if(!isset($obj[$objednavka_array[0]][$objednavka_array[2]])){
                            $obj[$objednavka_array[0]][$objednavka_array[2]] = 1;
                            $pocet[$objednavka_array[0]][$objednavka_array[2]] = $objednavka_array[1];
                        }else{
                            $obj[$objednavka_array[0]][$objednavka_array[2]] ++;
                            $pocet[$objednavka_array[0]][$objednavka_array[2]] += $objednavka_array[1];
                        }
                    }                    
                }
                if($typ_zobrazeni == "tabulka_ts_prehled_objednavek"){
                    $pocty_osob = $pocet[$this->radek["id_zajezd"]];
                    $pocty_objednavek = $obj[$this->radek["id_zajezd"]];
                    $radek_pocet_osob = $this->computePocetOsob($pocty_osob, "array");
                    $radek_objednavka = $this->computePocetObjednavek($pocty_objednavek, "array");
                   $vypis = $vypis . "
							<td class=\"id borderDotted border1r\">" . $this->radek["id_serial"] . " / ".$this->radek["id_zajezd"]."</td>
							<td class=\"nazev borderDotted border1r\">" . $this->radek["nazev"].", ".$this->radek["nazev_ubytovani"] . "</td>
							<td class=\"termin borderDotted border1r\">" . CommonUtils::czechDate($this->radek["od"])." - ". CommonUtils::czechDate($this->radek["do"]) . "</td>
                                                        
                                                            <td class=\"op borderDotted\" align=\"right\">".$radek_pocet_osob[1]."</td>
                                                            <td class=\"rez borderDotted\" align=\"right\">".$radek_pocet_osob[2]."</td>
                                                            <td class=\"zal borderDotted\" align=\"right\">".$radek_pocet_osob[3]."</td>
                                                            <td class=\"prod borderDotted\" align=\"right\">".$radek_pocet_osob[4]."</td>
                                                                <td class=\"tot border1rDotted borderDotted\" align=\"right\">".$radek_pocet_osob[0]."</td>
                                                            <td class=\"stor borderDotted border1rMP\" align=\"right\">".$radek_pocet_osob[5]."</td>
                                                        
                                                            <td class=\"op borderDotted\" align=\"right\">".$radek_objednavka[1]."</td>
                                                            <td class=\"rez borderDotted\" align=\"right\">".$radek_objednavka[2]."</td>
                                                            <td class=\"zal borderDotted\" align=\"right\">".$radek_objednavka[3]."</td>
                                                            <td class=\"prod borderDotted\" align=\"right\">".$radek_objednavka[4]."</td>
                                                                <td class=\"tot border1rDotted borderDotted\" align=\"right\">".$radek_objednavka[0]."</td>
                                                            <td class=\"stor borderDotted border1rMP\" align=\"right\">".$radek_objednavka[5]."</td>
                                                         <td class=\"topologie borderDotted\">" . $topologie. "</td>   ";
                }else if($this->show_prehled_zajezdu_filtr){
                    $pocty_osob = $pocet[$this->radek["id_zajezd"]];
                    $pocty_objednavek = $obj[$this->radek["id_zajezd"]];
                    $radek_pocet_osob = $this->computePocetOsob($pocty_osob);
                    $radek_objednavka = $this->computePocetObjednavek($pocty_objednavek);
                   $vypis = $vypis . "
							<td class=\"id\">" . $this->radek["id_serial"] . " / ".$this->radek["id_zajezd"]."</td>
							<td class=\"nazev\">" . $this->radek["nazev"].", ".$this->radek["nazev_ubytovani"] . "</td>
							<td class=\"termin\">" . CommonUtils::czechDate($this->radek["od"])." - ". CommonUtils::czechDate($this->radek["do"]) . "</td>
							<td class=\"hit_zajezd\"><b>" . $radek_pocet_osob. "</b></td>
                                                        <td class=\"hit_zajezd\"><b>" . $radek_objednavka. "</b></td>        
                                                        <td class=\"topologie\">" . $topologie. "</td>
							<td class=\"menu\" style=\"width:620px;\">";
                }else{
                   $vypis = $vypis . "
							<td class=\"id\">" . $this->radek["id_serial"] . "</td>
							<td class=\"nazev_typ\">" . $this->radek["nazev_typ"] . "</td>
							<td class=\"nazev\">" . $ubyt . "</td>
							<td class=\"nazev\"><b>" . $this->radek["nazev"] . "</b></td>
							<td class=\"menu\" style=\"width:620px;\">"; 
                }
                


                if ($this->moznosti_editace == "zadne") {
                    $vypis .= "";
                } else if ($this->moznosti_editace == "select_serial_objednavky") {
                    $vypis = $vypis . "
								<a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=zajezd_list&amp;moznosti_editace=select_zajezd_objednavky\">vybrat seriál</a>";
                } else if ($this->moznosti_editace == "objednavka_change_serial") {
                    if ($_REQUEST["storno_poplatek_zmena"] == 0) {
                        $_REQUEST["storno_poplatek_zmena"] = $this->check_int($_REQUEST["storno"]);
                    }
                    $vypis = $vypis . "    
								<a href=\"?typ=rezervace&amp;pozadavek=change_serial&amp;id_serial=" . $this->radek["id_serial"] . "&amp;moznosti_editace=objednavka_change_zajezd&amp;id_objednavka=" . $_GET["id_objednavka"] . "&amp;id_klient=" . $_REQUEST["id_klient"] . "&amp;storno=" . $_REQUEST["storno_poplatek_zmena"] . "\">zmìnit na tento seriál</a>";
                } else {
                    $showhide_start = " |  <span id=\"serial_" . $this->radek["id_serial"] . "\" style=\"display:none;\">";
                    $showhide_end = "</span><a href=\"#\" id=\"serial_" . $this->radek["id_serial"] . "_showhide\" onclick=\"showDetailActions('serial_" . $this->radek["id_serial"] . "');return false;\">další &gt;&gt;</a>";
                    if($this->show_prehled_zajezdu_filtr){
                        //by default se nezobrazi zadne moznosti editace
                        $vypis = $vypis . $showhide_start;
                    }
                    $vypis = $vypis . "
						 <a href=\"/zajezdy/zobrazit/" . $this->radek["nazev_web"] . "\" target=\"_blank\">zobrazit</a>
                        | <a href=\"http://google.com/search?q=" . $this->getFullNazev(" ") . "\" target=\"_blank\" title=\"Vyhledat na Googlu\"><img src=\"https://www.google.cz/images/branding/product/ico/googleg_lodp.ico\" style=\"height:16px;margin-bottom:-2px;\"/></a>
                        | <a href=\"http://search.seznam.cz/?q=" . $this->getFullNazev(" ")  . "\" target=\"_blank\" title=\"Vyhledat na Seznamu\"><img src=\"http://search.seznam.cz/r/img/favicon.ico\" style=\"height:14px;margin-bottom:-2px;\"/></a>
                                                               | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=serial&amp;pozadavek=edit\">seriál</a>
                                                                | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=serial_objekty\">objekty</a>
					 			| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=cena\">služby</a>
					 			| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=zajezd_list\">zájezdy</a>";

                    $vypis = $vypis . " | <a href=\"rezervace.php?id_serial=" . $this->radek["id_serial"] . "&amp;typ=rezervace_list&amp;filter=clear\">objednávky</a>";
                    $vypis = $vypis . " | <a href=\"rezervace.php?typ=rezervace&pozadavek=new-objednavka&id_serial=" . $this->radek["id_serial"] . "\">nová objednávka</a>";
                    $vypis = $vypis . " | <a href='seznamy_ucastniku.php?page=zajezdy&cb-serialy=" . $this->radek["id_serial"] . "' title=\"Zobrazit seznam úèastníkù k seriálu/zájezdu\">úèastníci</a>";
                    if ($adresa_foto = $core->get_adress_modul_from_typ("fotografie")) {
                        $vypis = $vypis . " | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=foto\">foto</a>";
                    }
                    
                    if(!$this->show_prehled_zajezdu_filtr){
                        $vypis = $vypis . $showhide_start;
                    }
                    $vypis = $vypis . " | <a href='ts_letaky.php?page=edit-settings&id_serial=" . $this->radek["id_serial"] . "' title=\"Vygenerovat letáky k seriálu/zájezdu\">letáky</a>";
                    if ($adresa_zeme = $core->get_adress_modul_from_typ("zeme")) {
                        $vypis = $vypis . " | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=zeme\">zemì/destinace</a>";
                    }
                    
                    if ($adresa_dokumenty = $core->get_adress_modul_from_typ("dokumenty")) {
                        $vypis = $vypis . " | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=dokument\">dokumenty</a>";
                    }
                    if ($adresa_informace = $core->get_adress_modul_from_typ("informace")) {
                        $vypis = $vypis . " | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=informace\">informace</a>";
                    }
                    if ($adresa_slevy = $core->get_adress_modul_from_typ("slevy")) {
                        $vypis = $vypis . " | <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=slevy\">slevy</a>";
                    }

                    $vypis = $vypis . " | <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=serial&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>";

                    $vypis = $vypis . " | <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=serial&amp;pozadavek=delete_with_objednavky\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">smazat vèetnì objednávek</a>";

                    $vypis = $vypis . " <form style=\"font-size:0.9em;\" action=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;typ=serial&amp;pozadavek=copy\" method=\"post\"><strong>kopírovat seriál</strong>: nový název <input type=\"text\" name=\"nazev\" /> Kopírovat posl. zájezd: <input type=\"checkbox\" name=\"copy_zajezd\" value=\"1\" title=\"Kopírovat také poslední zájezd\"/> <input type=\"submit\" value=\"&gt;&gt;\" /></form>";
                    $vypis = $vypis . $showhide_end;
                }
                $vypis = $vypis . "
							</td>
						</tr>";
                
                if($this->zobrazit_zajezdy){
                    $zajezd_ids = explode(";", $this->radek["id_zajezd"] );
   
                    $nazvy_zajezdu = explode(";", $this->radek["nazev_zajezdu"] );
                    $od_array = explode(";", $this->radek["od"] );
                    $do_array = explode(";", $this->radek["do"] );
                    $objednavky = explode(";", $this->radek["objednavka"] );
                    if($zajezd_ids[0]>0){
                        if ($this->suda == 1) {
                            $vypis .= "<tr class=\"suda\"><td colspan=\"5\"><table>";
                        } else {
                            $vypis .= "<tr class=\"licha\"><td colspan=\"5\"><table>";
                        }
                        foreach ($zajezd_ids as $key => $id) {
                            $od = $this->change_date_en_cz($od_array[$key]);
                            $do = $this->change_date_en_cz($do_array[$key]);
                            $nazev_array = explode("_", $nazvy_zajezdu[$key]);
                            $nazev = $nazev_array[1];
                            
                            $pocty_osob = $pocet[$id];
                            $pocty_objednavek = $obj[$id];
                            $radek_pocet_osob = $this->computePocetOsob($pocty_osob);
                            $radek_objednavka = $this->computePocetObjednavek($pocty_objednavek);

                            
                            $moznosti_editace_zajezdu = " <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;id_zajezd=" . $id . "&amp;typ=zajezd&amp;pozadavek=edit\">zájezd</a>
					 		| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;id_zajezd=" . $id . "&amp;typ=cena_zajezd\">ceny zájezdu</a>
					 		| <a href=\"" . $adresa_modulu . "?id_serial=" . $this->radek["id_serial"] . "&amp;id_zajezd=" . $id . "&amp;typ=slevy_zajezd\">slevy</a>";
                            if ($adresa_objednavky = $core->get_adress_modul_from_typ("objednavky")) {
                                $moznosti_editace_zajezdu .= " | <a href=\"" . $adresa_objednavky . "?id_serial=" . $this->radek["id_serial"] . "&amp;id_zajezd=" . $id . "&amp;typ=rezervace_list\">objednávky</a>"
                                                            ." | <a href=\"rezervace.php?typ=rezervace&pozadavek=new-objednavka&id_serial=" . $this->radek["id_serial"] . "&id_zajezd=" . $id . "\">nová objednávka</a>";
                            }
                            $moznosti_editace_zajezdu .= " | <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_serial=" .$this->radek["id_serial"] . "&amp;id_zajezd=" . $id  . "&amp;typ=zajezd&amp;pozadavek=delete\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">delete</a>"
                                                       . " | <a class='anchor-delete' href=\"" . $adresa_modulu . "?id_serial=" .$this->radek["id_serial"] . "&amp;id_zajezd=" . $id  . "&amp;typ=zajezd&amp;pozadavek=delete_with_objednavky\" onclick=\"javascript:return confirm('Opravdu chcete smazat objekt?')\">smazat vèetnì objednávek</a>	";
                            if(is_array($pocty_osob) and array_sum($pocty_osob)>0){
                                $vypis .= "<tr class=\"vybery-zso\">";
                                $vypis_objed = "Osoby: $radek_pocet_osob, Objednávky:$radek_objednavka";
                            }else{
                                $vypis .= "<tr class=\"vybery-zbo\">";
                                $vypis_objed = "<i>Žádné objednávky</i>";
                            }
                            $vypis .= "<td style=\"width:50px;\"> - $id "
                                    . "<td style=\"width:140px;\"><a href=\"\">$od - $do</a>"
                                    . "<td style=\"width:300px;\">$nazev"
                                    . "<td class=\"hit_zajezd\" width=\"360px;\">$vypis_objed"
                                    . "<td>$moznosti_editace_zajezdu";
                        }
                        $vypis .= "</table>";
                    }
                }
                return $vypis;
            }
        }
        //no error message
    }

    private function computePocetOsob($pocty_osob, $typ_zobrazeni=""){
        $pocty_osob[3] = $pocty_osob[1]+$pocty_osob[2]+$pocty_osob[3];
        $pocty_osob[6] = $pocty_osob[6]+$pocty_osob[7];
        $pocty_osob[8] = $pocty_osob[8]+$pocty_osob[9];
        $sum_osob = $pocty_osob[3]+$pocty_osob[4]+$pocty_osob[5]+$pocty_osob[6]+$pocty_osob[8];
         if($typ_zobrazeni == "array") {
             $radek_pocet_osob = array((intval($sum_osob)-intval($pocty_osob[8])),intval($pocty_osob[3]),intval($pocty_osob[4]),intval($pocty_osob[5]),intval($pocty_osob[6]),intval($pocty_osob[8]));
             return $radek_pocet_osob;
         }                  
                            
                            $radek_pocet_osob = "<strong>".(intval($sum_osob)-intval($pocty_osob[8]))."</strong>
                                <span title=\"opce\" class='osoby stav-opce'> ".intval($pocty_osob[3])."
                                </span><span title=\"rezervace\" class='osoby stav-rez'> ".intval($pocty_osob[4])."
                                </span><span title=\"záloha\" class='osoby stav-zal'> ".intval($pocty_osob[5])."
                                </span><span title=\"prodáno\" class='osoby stav-prodano'> ".intval($pocty_osob[6])."
                                </span><span title=\"storno\" class='osoby stav-storno'> ".intval($pocty_osob[8])." </span>";
                            return $radek_pocet_osob;
    }

    private function computePocetObjednavek($pocty_objednavek, $typ_zobrazeni=""){
                            $pocty_objednavek[3] = $pocty_objednavek[1]+$pocty_objednavek[2]+$pocty_objednavek[3];
                            $pocty_objednavek[6] = $pocty_objednavek[6]+$pocty_objednavek[7];
                            $pocty_objednavek[8] = $pocty_objednavek[8]+$pocty_objednavek[9];
                            $sum_objednavek = $pocty_objednavek[3]+$pocty_objednavek[4]+$pocty_objednavek[5]+$pocty_objednavek[6]+$pocty_objednavek[8];
        if($typ_zobrazeni == "array") {
             $radek_objednavka = array((intval($sum_objednavek)-intval($pocty_objednavek[8])),intval($pocty_objednavek[3]),intval($pocty_objednavek[4]),intval($pocty_objednavek[5]),intval($pocty_objednavek[6]),intval($pocty_objednavek[8]));
             return $radek_objednavka;
         } 
                            $radek_objednavka = "<strong>".(intval($sum_objednavek)-intval($pocty_objednavek[8]))."</strong>
                                <span title=\"opce\" class='osoby stav-opce'> ".intval($pocty_objednavek[3])."
                                </span><span title=\"rezervace\" class='osoby stav-rez'> ".intval($pocty_objednavek[4])."
                                </span><span title=\"záloha\" class='osoby stav-zal'> ".intval($pocty_objednavek[5])."
                                </span><span title=\"prodáno\" class='osoby stav-prodano'> ".intval($pocty_objednavek[6])."
                                </span><span title=\"storno\" class='osoby stav-storno'> ".intval($pocty_objednavek[8])." </span>";
                            return $radek_objednavka;
    }    
    public function printJson()
    {
        $out = "[";
        while ($this->get_next_radek()) {
            $zajezdList = new Zajezd_list($this->radek["id_serial"]);
            $hasActualZajezd = false;
            while ($zajezdList->get_next_radek()) {
                if ($zajezdList->radek["do"] >= date("Y-m-d") || $zajezdList->radek["do"] == '0000-00-00') {
                    $hasActualZajezd = true;
                }
            }
            if ($hasActualZajezd) {
                $out .= "{\"id\": \"" . $this->radek["id_serial"] . "\", \"nazev\": \"" . $this->getFullNazev() . "\", \"dlouhodobe_zajezdy\": \"" . $this->radek["dlouhodobe_zajezdy"] . "\"},";
            }
        }
        $out = substr($out, 0, strlen($out) - 1);
        $out .= "]";

        return $out;
    }

    /**zobrazi odkazy na dalsi stranky vypisu*/
    function show_strankovani()
    {
        if ($this->pocet_zajezdu != 0 and $this->pocet_zaznamu != 0) {
            //prvni cislo stranky ktere zobrazime
            $act_str = $this->zacatek - (10 * $this->pocet_zaznamu);
            if ($act_str < 0) {
                $act_str = 0;
            }

            //zjistim vsechny dalsi promenne, odstranim z nich ale str
            $promenne = ereg_replace("str=[0-9]*&?", "", $_SERVER["QUERY_STRING"]);

            //odkaz na prvni stranku
            $vypis = "<div class=\"strankovani\"><a href=\"?str=0&amp;" . $promenne . "\" title=\"první stránka\">&lt;&lt;</a> &nbsp;";

            //odkaz na dalsi stranky z rozsahu
            while (($act_str < $this->pocet_zajezdu) and ($act_str <= $this->zacatek + (10 * $this->pocet_zaznamu))) {
                if ($this->zacatek != $act_str) {
                    $vypis = $vypis . "<a href=\"?str=" . $act_str . "&amp;" . $promenne . "\" title=\"strana " . (1 + ($act_str / $this->pocet_zaznamu)) . "\">" . (1 + ($act_str / $this->pocet_zaznamu)) . "</a> ";
                } else {
                    $vypis = $vypis . (1 + ($act_str / $this->pocet_zaznamu)) . " ";
                }
                $act_str = $act_str + $this->pocet_zaznamu;
            }

            //odkaz na posledni stranku
            $posl_str = $this->pocet_zaznamu * floor(($this->pocet_zajezdu - 1) / $this->pocet_zaznamu);
            $vypis = $vypis . " &nbsp; <a href=\"?str=" . $posl_str . "&amp;" . $promenne . "\" title=\"poslední stránka\">&gt;&gt;</a></div>";

            return $vypis;
        }
    }


    /**zjistim, zda mam opravneni k pozadovane akci*/
    function legal()
    {
        $zamestnanec = User_zamestnanec::get_instance();
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        return $zamestnanec->get_bool_prava($id_modul, "read");
    }

    public function getZacatek()
    {
        return $this->zacatek;
    }

    /**
     * @return int Pocet zaznamu z DB ne pocet zajezdu
     */
    public function getPocetZajezdu()
    {
        return $this->pocet_zajezdu;
    }

    /**
     * @return int Pocet zaznamu na jednu stranku
     */
    public function getPocetZaznamu()
    {
        return $this->pocet_zaznamu;
    }

    public function getFullNazev($separator = ", ")
    {
        if ($this->radek["nazev_ubytovani"] != "" and $this->radek["id_sablony_zobrazeni"] == "12") {
            $ubyt = $this->radek["nazev_ubytovani"] . $separator;
        } else {
            $ubyt = "";
        }
        return $ubyt . $this->radek["nazev"];
    }
}

?>
