<?php
/**
 * rezervace.inc.php - tridy pro zobrazeni rezervace - obecných informcí
 */

/*--------------------- SERIAL -------------------------------------------*/
class FakturaDAO extends Generic_data_class
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
     * @param $id_faktury
     * @return tsObjednavka
     */
    static function dataFaktura($id_faktury)
    {
        $dataObjednavka = self::$database->query(self::create_query("select_faktura", $id_faktury));
        while ($row = mysqli_fetch_array($dataObjednavka)) {
            return new tsFaktura(
                $row["id_faktury"],
                $row["id_objednavka"],
                $row["cislo_faktury"],
                $row["typ_dokladu"],
                $row["typ_faktury"],
                $row["cislo_faktury_vystavovatele"],
                $row["mena"], $row["kurz"],
                $row["celkova_castka"],
                $row["text_faktury"],
                $row["datum_vystaveni"],
                $row["datum_splatnosti"],
                $row["datum_zdanitelneho_plneni"],
                $row["zpusob_uhrady"],
                $row["zaplaceno"],
                $row["dph_snizene"],
                $row["dph_zakladni"],
                $row["dodavatel_text"],
                $row["prijemce_text"],
                $row["pata_faktury"]);
        }
    }

    /**
     * @param $id_faktury
     * @return tsOsoba[]
     */
    static function dataPolozky($id_faktury)
    {
        $polozky = array();
        $dataPolozky = self::$database->query(self::create_query("select_polozky", $id_faktury));
        while ($row = mysqli_fetch_array($dataPolozky)) {
            $polozky[] = new tsFakturyPolozka($row["id_polozky"], $row["id_faktury"], $row["nazev_polozky"], $row["jednotkova_cena"], $row["pocet"], $row["pocitat_dph"], $row["celkem"]);
        }

        return $polozky;
    }

    /**
     * @param $id_faktury
     * @return tsOsoba
     */
    static function dataVystavil($id_faktury)
    {
        $dataVystavil = self::$database->query(self::create_query("select_vystavil", $id_faktury));
        while ($row = mysqli_fetch_array($dataVystavil)) {
            return new tsOsoba(null, $row["jmeno"], $row["prijmeni"], "", "", $row["email"], $row["telefon"]);
        }
    }

    /**
     * @return array
     */
    static function dataCentralniData()
    {
        $dataCentralni = self::$database->query(self::create_query("select_centralni_data", null));
        $centralni_data = array();
        while ($row = mysqli_fetch_array($dataCentralni)) {
            $row["nazev"] = str_replace("hlavicka:", "", $row["nazev"]);
            $centralni_data[$row["nazev"]] = $row["text"];
        }
        return $centralni_data;
    }

//------------------- METODY TRIDY -----------------
    /**vytvoreni dotazu na zaklade typu pozadavku*/
    private static function create_query($typ_pozadavku, $id_faktury)
    {
        if ($typ_pozadavku == "select_faktura") {
            $dotaz = "SELECT * FROM `faktury`
                        WHERE `id_faktury`=" . $id_faktury . "
                        LIMIT 1";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_polozky") {
            $dotaz = "SELECT * FROM `faktury_polozka`
						WHERE `id_faktury`=" . $id_faktury . "
						";
            //echo $dotaz;
            return $dotaz;
        } else if ($typ_pozadavku == "select_vystavil") {
            $dotaz = "SELECT `user_klient`.`id_klient`, `user_klient`.`jmeno`, `user_klient`.`prijmeni`, `user_klient`.`telefon`,`user_klient`.`email`
                        FROM `faktury`
                            JOIN `user_zamestnanec` ON (`faktury`.`id_vystavil` = `user_zamestnanec`.`id_user`)
                            JOIN `user_klient` ON (`user_zamestnanec`.`id_user_klient` = `user_klient`.`id_klient`)
                        WHERE `id_faktury`=" . $id_faktury . "
                        Limit 1
						";
            // echo $dotaz;
            return $dotaz;

        } else if ($typ_pozadavku == "select_centralni_data") {
            $dotaz = "SELECT * FROM `centralni_data`
						WHERE `nazev` LIKE \"hlavicka:%\"
			";
            //echo $dotaz;
            return $dotaz;
        }
    }

}

//todo: tohle bych presunul do indexu danych modulu aby bylo videt, ze se to vola, tohle nikdo nikdy nenajde
FakturaDAO::init();