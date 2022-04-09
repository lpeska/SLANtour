<?php


class SerialSQLBuilder
{

    public static function readSerialById($id_serial)
    {
        $sql = "
        SELECT `serial`.*,`typ_serial`.*, `objekt`.`nazev_objektu` as `nazev_ubytovani`                       
        from `serial`
            left join (`objekt_serial` join
                `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu`) 
            ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial` and `id_sablony_zobrazeni`=12 and `typ_objektu` = 1)   
            join `typ_serial`	 on (`serial`.`id_typ`=`typ_serial`.`id_typ`)
            where serial.id_serial = $id_serial
            limit 1
        ";
        
        $query = new SQLQuery($sql, array());

        return $query;
    }

    public static function readFotoByIdSerial($id_serial)
    {
        $sql = "
        SELECT `foto`.*                     
        from `foto`
            join `foto_serial` on (`foto`.`id_foto` = `foto_serial`.`id_foto`)
            where foto_serial.id_serial = $id_serial 
            order by `zakladni_foto` desc   
        ";
        
        $query = new SQLQuery($sql, array());

        return $query;
    }        
    
    public static function readZajezdyByIdSerial($id_serial)
    {
        $sql = "
        SELECT `zajezd`.*                     
        from `zajezd`
            where zajezd.id_serial = $id_serial and `do` > \"".Date("Y-m-d")."\" and `nezobrazovat_zajezd` <= 0
            order by `od`, `do`    
        ";
        
        $query = new SQLQuery($sql, array());

        return $query;
    }    
    
    public static function readZajezdSluzby($id_zajezd)
    {
        $sql = "
        SELECT `cena_zajezd`.* , `cena`.*                     
        from `cena_zajezd` 
            join `cena` on (`cena_zajezd`.`id_cena` = `cena`.`id_cena`)
            where cena_zajezd.id_zajezd = $id_zajezd and `nezobrazovat` <= 0
            order by  zakladni_cena desc, typ_ceny, poradi_ceny  
        ";
        
        $query = new SQLQuery($sql, array());

        return $query;
    }  

    public static function readZajezdSlevy($id_zajezd)
    {
        
        $sql = "(select `slevy`.id_slevy, `zkraceny_nazev` as `nazev_slevy`, `castka`, `mena`, `sleva_staly_klient`
                            from `slevy` 
                                join `slevy_serial` on (`slevy`.`id_slevy` = `slevy_serial`.`id_slevy`)
                                join `zajezd` on (`slevy_serial`.`id_serial` = `zajezd`.`id_serial` and `zajezd`.`id_zajezd` = $id_zajezd)
                            where 
                                (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
                            )
                        union distinct
                        (select `slevy`.id_slevy, `zkraceny_nazev` as `nazev_slevy`, `castka`, `mena`, `sleva_staly_klient`
                            from `slevy` 
                                join `slevy_zajezd` on (`slevy`.`id_slevy` = `slevy_zajezd`.`id_slevy`)
                            where `slevy_zajezd`.`id_zajezd` = ".$id_zajezd." 
                                and (`slevy`.`platnost_od` = \"0000-00-00\" or `slevy`.`platnost_od`<=\"".Date("Y-m-d")."\" )
                                and (`slevy`.`platnost_do` = \"0000-00-00\" or `slevy`.`platnost_do`>=\"".Date("Y-m-d")."\" ) 
			)
                        order by `mena`, `castka` desc";
      //  echo $sql;
        $query = new SQLQuery($sql, array());

        return $query;
    }      
    
}