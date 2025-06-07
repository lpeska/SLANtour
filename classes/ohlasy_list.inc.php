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

        $dotaz = "SELECT distinct ohlasy.*, foto.`foto_url`,`foto`.`id_foto`,`foto`.`nazev_foto`,`foto`.`popisek_foto` 
                    from ohlasy left join (foto_ohlasy join foto on (foto_ohlasy.`id_foto` = foto.`id_foto`) ) on (foto_ohlasy.`id_ohlasu` = ohlasy.`id_ohlasu`) 
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
            $ret[$this->radek["id_ohlasu"]] = $this->radek;  
        }
        return $ret;
    } 
}
