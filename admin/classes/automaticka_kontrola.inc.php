<?php

/**
 * dokument.inc.php - tridy pro zobrazeni dokumentu
 */
/* --------------------- SERIAL ------------------------------------------- */
class Automaticka_kontrola extends Generic_data_class {

    //vstupni data
    protected $typ_pozadavku;
    protected $id_zamestnance;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /*     * konstruktor tøídy na základì typu pozadavku */

    function __construct($typ_pozadavku, $id_zamestnance) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //kontrola vstupnich dat
        $this->typ_pozadavku = $this->check($typ_pozadavku);
        $this->id_zamestnance = $this->check_int($id_zamestnance);

        //pokud mam dostatecna prava pokracovat
        if ($this->legal($this->typ_pozadavku) and $this->correct_data($this->typ_pozadavku)) {
            if ($this->typ_pozadavku == "new"){                
                //get all relevant flights
                $days_before = $this->check_int($_POST["days_before"]);
                if($days_before <= 0){$days_before = 60;}

                $days_after = $this->check_int($_POST["days_after"]);
                if($days_after <= 0){$days_after = 0;}

                $query = "
                    SELECT * FROM `objekt_letenka`
                    join cena_promenna_cenova_mapa on (cena_promenna_cenova_mapa.id_objektu = objekt_letenka.id_objektu)
                    WHERE `automaticka_kontrola_cen` = 1
                    and DATEDIFF(cena_promenna_cenova_mapa.termin_od, curdate()) < $days_before
                    and DATEDIFF(cena_promenna_cenova_mapa.termin_od, curdate()) > $days_after
                    and (DATEDIFF(curdate(),cena_promenna_cenova_mapa.posledni_kontrola) > 5 or cena_promenna_cenova_mapa.posledni_kontrola is NULL)
                    order by flight_from, flight_to, termin_od
                    ";
                //zjisti jaky soubor se k tomu vaze a smaz ho
                $data = $this->database->query($query) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                
                $this->run_check_queries($data);
                
            }

            if ($this->typ_pozadavku == "delete"){
                $id_kontrola = $this->check_int($_GET["id_kontrola"]);
                $query = "
                    DELETE FROM `automaticka_kontrola` WHERE `id_kontrola` = $id_kontrola LIMIT 1                      
                    ";
                //zjisti jaky soubor se k tomu vaze a smaz ho
                $data = $this->database->query($query) or $this->chyba("Chyba pøi mazání kontroly: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
            }
        } else {
            $this->chyba("Nemáte dostateèné oprávnìní k požadované akci");
        }

        //pokud se akce uspìšnì zapsala do databáze, vypíšu potvrzovací hlášku
        if (!$this->get_error_message() and
                ($this->typ_pozadavku == "create" or $this->typ_pozadavku == "update" or $this->typ_pozadavku == "delete")) {
            $this->confirm("Požadovaná akce probìhla úspìšnì");
        }
    }

    /*     * kontrola zda smi uzivatel provest danou akci */

    function legal($typ_pozadavku) {
        $zamestnanec = User_zamestnanec::get_instance();
        //z jadra zjistim ide soucasneho modulu
        $core = Core::get_instance();
        $id_modul = $core->get_id_modul();

        //podle jednotlivych typu pozadavku
        if ($typ_pozadavku == "new") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "edit") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "show") {
            return $zamestnanec->get_bool_prava($id_modul, "read");
        } else if ($typ_pozadavku == "create") {
            return $zamestnanec->get_bool_prava($id_modul, "create");
        } else if ($typ_pozadavku == "update") {
            if ($zamestnanec->get_bool_prava($id_modul, "edit_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "edit_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "delete") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        } else if ($typ_pozadavku == "mass_del") {
            if ($zamestnanec->get_bool_prava($id_modul, "delete_cizi") or
                    ($zamestnanec->get_bool_prava($id_modul, "delete_svuj") and $zamestnanec->get_id() == $this->get_id_user_create() )) {
                return true;
            } else {
                return false;
            }
        }

        //neznámý požadavek zakážeme
        return false;
    }

    /*     * kontrola zda mam odpovidajici data */

    function correct_data($typ_pozadavku) {
        $ok = 1;
        //pokud je vse vporadku...
        if ($ok == 1) {
            return true;
        } else {
            return false;
        }
    }

    
    function run_check_queries($records){
        $last_id_objektu = 0;
        while($row = mysqli_fetch_array($records)){
            //print_r($row);

            $from = $this->check($row["flight_from"]);
            $to = $this->check($row["flight_to"]);
            $direct = $this->check_int($row["flight_direct"]);
            $dates_from = $this->check($row["termin_od"]);
            $dates_to = $this->check($row["termin_do"]);
            $id_objektu = $row["id_objektu"];

            if($last_id_objektu != $id_objektu){
                $last_id_objektu = $id_objektu;
                echo "Provìøování objektu ID $id_objektu ($from - $to) ";
            }

            $url = "https://mapa.letuska.cz/v1/proxymp.php?cid=SLT&adults=1&children=0&infants=0&cabinclass=economy&rtn=1&multi=0&nbfrom=0&nbto=0&direct=$direct&date1=$dates_from&from1=$from&to1=$to&date_rtn=$dates_to";
            //echo $url;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_VERBOSE, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $res = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($res, 0, $header_size);
            $text = substr($res, $header_size);
           //echo $res;
            $this->letuska_pricelist = $text;
            $decoded_pricelist = json_decode($this->letuska_pricelist);

            $query = "UPDATE `cena_promenna_cenova_mapa` set `posledni_kontrola` = \"".Date("Y-m-d")."\" 
                        where `id_objektu`=$id_objektu and termin_od = \"$dates_from\" and termin_do = \"$dates_to\"";
            $this->database->query($query);
            if (isset($decoded_pricelist->error)){

                try {
                    echo "Chyba: ".$decoded_pricelist->error->descr ." u letenky: $from - $to, odlet: $dates_from, pøílet: $dates_to <a href=\"/admin/objekty.php?id_objektu=$id_objektu&typ=tok_list&pozadavek=show_letuska\">Detail</a><br/>" ;
                    $query = "INSERT INTO `automaticka_kontrola` (`id_objektu`,`price_from`, `price_to`, `flight_from`, `flight_to`, `date_from`, `date_to`, `date_check`, `note`) 
                                    values ($id_objektu, NULL, NULL, \"$from\",\"$to\",\"$dates_from\",\"$dates_to\",\"".Date("Y-m-d H:i:s")."\",\"".$this->check($decoded_pricelist->error->descr) ."\") ";
                    $this->database->query($query);
                } catch (Exception $e) {
                    echo "Neznámá chyba u letenky: $from - $to, odlet: $dates_from, pøílet: $dates_to <br/>" ;
                }

            }else{
                $cena = $decoded_pricelist->segments[0]->price->total;
                
                
                $cena_pred = $row["castka"];
                $pomer = $cena / ($cena_pred+0.1);
                //echo $cena.", ".$cena_pred;
                if($pomer > 1.15){
                    echo "Zmìna ceny z $cena_pred na $cena u letenky: $from - $to, odlet: $dates_from, pøílet: $dates_to; <a href=\"/admin/objekty.php?id_objektu=$id_objektu&typ=tok_list&pozadavek=show_letuska\">Detail</a><br/>" ;
                    $query = "INSERT INTO `automaticka_kontrola` (`id_objektu`,`price_from`, `price_to`, `flight_from`, `flight_to`, `date_from`, `date_to`, `date_check`, `note`) 
                                    values ($id_objektu, $cena_pred, $cena, \"$from\",\"$to\",\"$dates_from\",\"$dates_to\",\"".Date("Y-m-d H:i:s")."\",NULL) ";
                    $this->database->query($query);
                    //TODO: ulozit do databaze
                }
                
            }
                     
            
        }
        
        
    }

}

?>
