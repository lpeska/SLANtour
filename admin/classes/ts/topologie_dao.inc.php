<?php
/**
 * rezervace.inc.php - tridy pro zobrazeni rezervace - obecných informcí
 */

/*--------------------- SERIAL -------------------------------------------*/
class TopologieDAO extends Generic_data_class
{

    /**
     * @var Database
     */
    private static $database;

    static function init()
    {
        self::$database = Database::get_instance();
    }

    /**
     * @param $id_objednavka
     * @return tsTopologie
     */
    static function dataTopologie($id_tok_topologie)
    {
        $dataTopologie = self::$database->query(self::create_query("select_topologie", $id_tok_topologie));
        while ($row = mysqli_fetch_array($dataTopologie)) {
            $topologie = new tsTopologie($row["id_tok_topologie"], $row["nazev_tok"], $row["poznamka_tok"], $row["zobrazit_id_klient"], $row["zobrazit_id_objednavka"], $row["zobrazit_nazev"], $row["zobrazit_odjezd"]);
            return $topologie;
        }
    }


    /**
     * vrací jednotlivé sedadla zasedacího poøádku
     */
    static function dataPolozky($id_tok_topologie)
    {
        $dataPolozky = self::$database->query(self::create_query("select_polozky", $id_tok_topologie));
        $polozky = array();
        while ($row = mysqli_fetch_array($dataPolozky)) {
            if($row["id_sablony_zobrazeni"]== 12 and $row["nazev_ubytovani"]!=""){
                $row["nazev"] = $row["nazev_ubytovani"];
            }
            $polozka = new tsPolozkaTopologie($row["id_tok_topologie"], $row["jmeno"], $row["prijmeni"], $row["id_objednavka"], $row["nazev"], $row["odjezdova_mista"], $row["size_x"], $row["size_y"], $row["row"], $row["col"], $row["class"], $row["text"], $row["text_obsazeno"], $row["obsazeno"]);
            $polozky[] = $polozka;
        }
        return $polozky;
    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    private static function create_query($typ_pozadavku, $id_tok_topologie)
    {
        if ($typ_pozadavku == "select_polozky") {
            $dotaz = "select `topologie_tok_polozka`.*, jmeno,prijmeni,`objednavka`.`id_objednavka`,`zajezd_tok_topologie`.`id_zajezd`, `serial`.`nazev`,`serial`.`id_sablony_zobrazeni`,
                                    GROUP_CONCAT(distinct `cena`.`nazev_ceny` separator \", \") as `odjezdova_mista`,
                                    group_concat(DISTINCT `objekt`.`nazev_objektu` separator \", \") as `nazev_ubytovani`
                            FROM `topologie_tok_polozka` 
                            left join (user_klient
                                join objednavka_osoby on (`user_klient`.`id_klient` = `objednavka_osoby`.`id_klient`)
                                join objednavka on (`objednavka_osoby`.`id_objednavka` = `objednavka`.`id_objednavka`)
                                join zajezd_tok_topologie on (`objednavka`.`id_zajezd` = `zajezd_tok_topologie`.`id_zajezd`)
                                join serial on (`objednavka`.`id_serial` = `serial`.`id_serial`)  
                                left join (`objekt_serial` join
                                    `objekt` on ( `objekt`.`id_objektu` = `objekt_serial`.`id_objektu` and `objekt`.`typ_objektu`=1) ) on (`serial`.`id_serial` = `objekt_serial`.`id_serial`)
                                left join (
                                    `objednavka_cena` 
                                    join `cena` on (`cena`.`id_cena` = `objednavka_cena`.`id_cena` and `cena`.`typ_ceny`=5)
                                ) on (`objednavka`.`id_objednavka` = `objednavka_cena`.`id_objednavka` and `objednavka_cena`.`pocet`>0) 
                            ) on (`topologie_tok_polozka`.`id_tok_topologie` = `zajezd_tok_topologie`.`id_tok_topologie` and  `topologie_tok_polozka`.`id_klient` = `user_klient`.`id_klient`) 
                            WHERE `topologie_tok_polozka`.`id_tok_topologie`=" . $id_tok_topologie . "  group by `id_polozka` order by row,col  
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_topologie") {
            $dotaz = "select * from `topologie_tok` 
                               join `objekt_kategorie_termin` on (`topologie_tok`.`id_termin` = `objekt_kategorie_termin`.`id_termin` and `topologie_tok`.`id_objekt_kategorie` = `objekt_kategorie_termin`.`id_objekt_kategorie`) 
                               where `topologie_tok`.`id_tok_topologie`= ".$id_tok_topologie ." ";
            //echo $dotaz;
            return $dotaz;
        }
    }

}

//todo: tohle bych presunul do indexu danych modulu aby bylo videt, ze se to vola, tohle nikdo nikdy nenajde
TopologieDAO::init();