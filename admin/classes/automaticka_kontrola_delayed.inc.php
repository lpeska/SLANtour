<?php

/**
 * dokument.inc.php - tridy pro zobrazeni dokumentu
 */
/* --------------------- SERIAL ------------------------------------------- */
class Automaticka_kontrola extends Generic_data_class {

    //vstupni data
    protected $typ_pozadavku;
    public $database; //trida pro odesilani dotazu

//------------------- KONSTRUKTOR -----------------
    /*     * konstruktor tøídy na základì typu pozadavku */

    function __construct($typ_pozadavku) {
        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //echo "typ".$typ_pozadavku;
        if ($typ_pozadavku == "new"){                
                //get all relevant flights
                $queryDates = "Select * from centralni_data where nazev like 'autocheck:%'";
                $dataDates = $this->database->query($queryDates) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                $days_before = 60;
                $days_after = 5;
                
                while($row = mysqli_fetch_array($dataDates)){
                    if($row["nazev"]=="autocheck:daysBefore"){
                          $days_before = intval($row["text"]);
                    } else if($row["nazev"]=="autocheck:daysAfter"){
                          $days_after = intval($row["text"]);
                    }
                }


                $query = "
                    SELECT * FROM `objekt_letenka`
                    join cena_promenna_cenova_mapa on (cena_promenna_cenova_mapa.id_objektu = objekt_letenka.id_objektu)
                    WHERE `automaticka_odlozena_kontrola_cen` = 1
                    and DATEDIFF(cena_promenna_cenova_mapa.termin_od, curdate()) < $days_before
                    and DATEDIFF(cena_promenna_cenova_mapa.termin_od, curdate()) > $days_after
                    and (DATEDIFF(curdate(),cena_promenna_cenova_mapa.posledni_kontrola) > 5 or cena_promenna_cenova_mapa.posledni_kontrola is NULL)
                    order by flight_from, flight_to, termin_od
                    ";
                //zjisti jaky soubor se k tomu vaze a smaz ho
                //echo $query;
                $data = $this->database->query($query) or $this->chyba("Chyba pøi dotazu do databáze: " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
                
                
                
                $this->run_check_queries($data);
                
        }

    }

    /*     * kontrola zda smi uzivatel provest danou akci */

    
    function run_check_queries($records){
        $pocet_zaznamu = mysqli_num_rows($records);
        
        $myfile = fopen("log_delayed_autocheck.txt", "w") or die("Unable to open file!");
        fwrite($myfile, Date("Y-m-d h:i:s"));
        $txt = "\n\n Zacinam provadet automatickou kontrolu, ke zpracovani $pocet_zaznamu zaznamu\n\n";
        fwrite($myfile, $txt);
    
        $last_id_objektu = 0;
        $k = 0;
        while($row = mysqli_fetch_array($records)){
            //print_r($row);
            $k++;
            
            

            $from = $this->check($row["flight_from"]);
            $to = $this->check($row["flight_to"]);
            $direct = $this->check_int($row["flight_direct"]);
            $dates_from = $this->check($row["termin_od"]);
            $dates_to = $this->check($row["termin_do"]);
            $id_objektu = $row["id_objektu"];
                        

            if($last_id_objektu != $id_objektu){
                $last_id_objektu = $id_objektu;
                fwrite($myfile,"Provìøování objektu ID $id_objektu ($from - $to) \n");
            }
            
            fwrite($myfile, "Zaznam #$k  ($dates_from - $dates_to) ");
            

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
                    echo "Chyba: ".$decoded_pricelist->error->descr ." u letenky: $from - $to, odlet: $dates_from, pøílet: $dates_to<br/>" ;
                    $query = "INSERT INTO `automaticka_kontrola` (`id_objektu`,`price_from`, `price_to`, `flight_from`, `flight_to`, `date_from`, `date_to`, `date_check`, `note`) 
                                    values ($id_objektu, NULL, NULL, \"$from\",\"$to\",\"$dates_from\",\"$dates_to\",\"".Date("Y-m-d H:i:s")."\",\"".$this->check($decoded_pricelist->error->descr) ."\") ";
                    $this->database->query($query);
                } catch (Exception $e) {
                    echo "Neznámá chyba u letenky: $from - $to, odlet: $dates_from, pøílet: $dates_to <br/>" ;
                }
                fwrite($myfile, "Dotaz skoncil chybou \n");

            }else{
                $cena = $decoded_pricelist->segments[0]->price->total;
                
                
                $cena_pred = $row["castka"];
                $pomer = $cena / ($cena_pred+0.1);
                //echo $cena.", ".$cena_pred;
                fwrite($myfile, "cena pred: $cena_pred, cena po: $cena, pomer: $pomer \n");
                
                if($pomer > 1.15){
                    echo "Zmìna ceny z $cena_pred na $cena u letenky: $from - $to, odlet: $dates_from, pøílet: $dates_to;<br/>" ;
                    $query = "INSERT INTO `automaticka_kontrola` (`id_objektu`,`price_from`, `price_to`, `flight_from`, `flight_to`, `date_from`, `date_to`, `date_check`, `note`) 
                                    values ($id_objektu, $cena_pred, $cena, \"$from\",\"$to\",\"$dates_from\",\"$dates_to\",\"".Date("Y-m-d H:i:s")."\",NULL) ";
                    $this->database->query($query);
                    fwrite($myfile, " --- ULOZENO DO DB --- \n");
                }
                
            }
                     
            
        }
        
        fwrite($myfile, "\n\n ------ KONTROLA USPESNE DOKONCENA ------ \n");
        fwrite($myfile, Date("Y-m-d h:i:s"));
        fclose($myfile); 
    }

}

?>
