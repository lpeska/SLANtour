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

    function get_zajezdy() {
        $query = 
                "select 
                    `serial`.`id_serial`,`serial`.`dlouhodobe_zajezdy`,`serial`.`nazev`,`serial`.`nazev_web`,`serial`.`popisek`,`serial`.`strava`,`serial`.`doprava`,`serial`.`ubytovani`,
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
        echo $query;
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;
    }    
    function get_ceny_zajezdu($valid_zajezdIDs) {
        $query = 
                "select cena.*, cena_zajezd.*
                from `zajezd` join                    
                    `cena` on (`cena`.`id_serial` = `zajezd`.`id_serial` and `cena`.`typ_ceny`<=2) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 )                    

                where `cena_zajezd`.`id_zajezd` in (".implode($valid_zajezdIDs).")                     
                 ";
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
        return $data;     
    }    
    function get_zeme_a_destinace_serialu($valid_serialIDs) {
        $query = 
                "select 
                    `zeme_serial`.`id_serial`,`zeme`.`nazev_zeme`,`zeme`.`nazev_zeme_web`, destinace.nazev_destinace
                from 
                 `zeme_serial` join
                    `zeme` on (`zeme_serial`.`id_zeme` =`zeme`.`id_zeme`)
                    left join (
                         `destinace_serial`
                         join `destinace` on (`destinace`.`id_destinace` = `destinace_serial`.`id_destinace`)
                    )  on (`zeme_serial`.`id_serial` = `destinace_serial`.`id_serial` and `zeme`.`id_zeme` = `destinace`.`id_zeme`)
                    
                where `zeme_serial`.`id_serial` in (".implode($valid_serialIDs).")                    
                 ";
        $data = $this->database->query($query) or $this->chyba("Chyba při dotazu do databáze");
        
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

    /** vytvori text pro titulek stranky */
    function show_titulek() {

        //tvorba vypisu titulku
        if ($_GET["typ"] != "" or $_GET["podtyp"] != "" or $_GET["zeme"] != "" or $_GET["destinace"] != "") {              
            if ($_GET["zeme"] == "" and $_GET["destinace"] == "") {
                if($this->get_name_for_typ("")=="Pobyty na horách ".Date("Y")){
                    $nazev_typ = "Ubytování a pobyty na horách ".Date("Y")." ";
                }else{
                    $nazev_typ = $this->get_name_for_typ(" ");
                }
                return $nazev_typ . $this->description[$_GET["typ"]] . $this->get_name_for_podtyp(" ") . " | SLAN tour";
            } else if($this->get_typ()!="za-sportem"  and $_GET["zeme"] != "" and $_GET["destinace"] == ""){
                
                return  $this->get_name_for_typ(", ").$this->get_name_for_zeme(" | ") . $this->get_name_for_podtyp(" | ") . " SLAN tour";
            
            }else {
                if($this->get_name_for_typ("")=="Pobyty na horách ".Date("Y")){
                    $nazev_typ = "Ubytování a pobyty na horách ".Date("Y")." | ";
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
            return "Katalog zájezdů " . Date("Y");
        }
    }

    /** vytvori text pro nadpis stranky */

    /** vytvori text pro meta keyword stranky */
    function show_keyword() {
        //tvorba vypisu titulku                    
        if ($_GET["typ"] != "" or $_GET["podtyp"] != "" or $_GET["zeme"] != "" or $_GET["destinace"] != "") {
            return $this->get_name_for_destinace() . $this->get_name_for_zeme() . $this->get_name_for_typ() . $this->get_name_for_podtyp() . " SLAN tour, zájezdy " . Date("Y");
        } else {
            return "SLAN tour, zájezdy " . Date("Y") . "Poznávací, dovolená, za sportem, lázně";
        }
    }

    /** vytvori text pro meta description stranky */
    function show_description() {
        //tvorba vypisu titulku
        if ($_GET["typ"] != "" or $_GET["podtyp"] != "" or $_GET["zeme"] != "" or $_GET["destinace"] != "") {
            return "Katalog zájezdů od CK SLAN tour do " . $this->get_name_for_destinace() . $this->get_name_for_zeme() . $this->get_name_for_typ() . $this->get_name_for_podtyp() . " SLAN tour, zájezdy " . Date("Y");
        } else {
            return "SLAN tour, zájezdy " . Date("Y") . ": Poznávací zájezdy, dovolená u moře, zájezdy za sportem, vstupenky na sportovní soutěže, lázně a termály v Čechách, na Moravě, Slovensku a Maďarsku";
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

        //debilni dotaz, ktery trva pres dve vteriny, docasne zruseny, snad nic moc nerozesere
        /*
        $dotaz = "select `serial`.`id_serial`,`cena_zajezd`.`vyprodano`,`cena_zajezd`.`na_dotaz`, `cena_zajezd`.`kapacita_volna`
            	    from `serial` join
                    `zajezd` on (`zajezd`.`id_serial` = `serial`.`id_serial`) join
                    `cena` on (`cena`.`id_serial` = `serial`.`id_serial` and (`cena`.`typ_ceny`=1 or `cena`.`typ_ceny`=2) ) join
                    `cena_zajezd` on (`cena`.`id_cena` = `cena_zajezd`.`id_cena` and `zajezd`.`id_zajezd` = `cena_zajezd`.`id_zajezd` and `cena_zajezd`.`nezobrazovat`!=1 )
                    where 1 
                    order by `serial`.`id_serial`,`zajezd`.`id_zajezd`";
        $data = $this->database->query($dotaz)
                or $this->chyba("Chyba při dotazu do databáze");

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
        }*/
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
                            před slevou: <span style=\"color:red;text-decoration:line-through;font-weight:bold;\">" .
                $this->radek["cena_pred_akci"] . " Kč</span></div>";
    }

    function get_akcni_cena() {
        return "<span style=\"color:#00ae35;font-size:1.2em;text-decoration:none;font-weight:bold;\">" .
                $this->radek["akcni_cena"] . " Kč</span>";
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
        if(trim($cena)=="1"){//neco je spatne, pravdepodobne se jedna o predbeznou registraci
            return "<span style=\"color:#00ae35;font-size:1.0em;text-decoration:none;font-weight:bold;\"> Předběžná registrace</span>";
        }
        return "<span style=\"color:#00ae35;font-size:1.2em;text-decoration:none;font-weight:bold;\">" .
                $cena . " Kč</span>";
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
        if ($this->radek["nazev_zeme"] == "Česká republika" or $this->radek["nazev_zeme"] == "Česká republika, víkendové pobyty") {
            return "ČR";
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
