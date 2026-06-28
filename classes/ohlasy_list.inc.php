<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of destinace_list
 *
 * @author lpeska
 */
class Ohlasy_list extends Generic_list
{

    protected $limit;
    protected $database; //trida pro odesilani dotazu

    //------------------- KONSTRUKTOR  -----------------	
    function __construct(int $limit)
    {
        //trida pro odesilani dotazu
        $this->limit = $limit;
        $this->database = Database::get_instance();

        //ziskani seznamu z databaze	
        $this->data = $this->database->query($this->create_query($this->typ))
            or $this->chyba("Chyba p�i dotazu do datab�ze");
    }

    //------------------- METODY TRIDY -----------------	
    /**vytvoreni dotazu ze zadanych parametru*/
    function create_query($typ_pozadavku, $only_count = 0)
    {

        $dotaz = "SELECT distinct ohlasy.*, 
                foto.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto` , 
                serial.id_serial, serial.nazev, serial.id_sablony_zobrazeni, serial.nazev_web,
                `objekt`.`nazev_objektu` AS `nazev_ubytovani`
                        from ohlasy 
                            left join (foto_ohlasy join foto on (foto_ohlasy.`id_foto` = foto.`id_foto`) ) on (foto_ohlasy.`id_ohlasu` = ohlasy.`id_ohlasu`)
                            left join serial on (serial.`id_serial` = ohlasy.`id_serial`)
                            left join (
                                `objekt_serial`
                                JOIN `objekt` 
                                    ON (`objekt`.`id_objektu` = `objekt_serial`.`id_objektu`)
                                ) ON (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                    WHERE  ohlasy.`zobrazit`=1 order by datum desc Limit 0, ".$this->limit."
                    ";

        //echo $dotaz;
        return $dotaz;
    }

    function get_ohlasy_list(){
        $ret = [];
        while($this->get_next_radek()){
            // print_r($this->radek);
            // echo "</br>";
            // echo "</br>";
            $this->radek["foto_url"] = "https://slantour.cz/foto/nahled/".$this->radek["foto_url"];
            if ($this->radek["id_serial"]>0){
                $this->radek["zajezd_url"] = "https://slantour.cz/zajezdy/zobrazit/".$this->radek["nazev_web"];
                
                if($this->radek["id_sablony_zobrazeni"] != 12 ){
                    $this->radek["zajezd_title"] = $this->radek["nazev"];
                }else{
                    $this->radek["zajezd_title"] = $this->radek["nazev_ubytovani"].", ".$this->radek["nazev"];
                }
            }else{
                $this->radek["zajezd_url"] = "https://slantour.cz/ohlasy";
                $this->radek["zajezd_title"] = "";
                
            }
            
            
            
            $ret[$this->radek["id_ohlasu"]] = $this->radek;  
        }
        return $ret;
    } 
}
