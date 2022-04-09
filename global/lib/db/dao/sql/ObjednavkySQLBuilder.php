<?php

//todo proc mam v SQL builderech vsude jako parametr pole? Takhle spoleham na to, ze DAO vi v jakem poradi ma poslat parametry, to je dost hloupy.
class ObjednavkySQLBuilder
{
    const PLUS_MINUS_MODE_PLUS = "plus";
    const PLUS_MINUS_MODE_MINUS = "minus";
    const CENTRALNI_DATA_KURZ_EUR_TITLE = "kurz EUR";

    public static function readObjednavkaDetailsByIdSQL($params)
    {
        //note pocet osob je pocitan dynamicky - pocet_osob ukladanych v objednavce je redundantni
        $sql = "SELECT obj.*, s.nazev, z.nazev_zajezdu, f.foto_url, f.nazev_foto, z.id_zajezd, z.od, z.do, s.dlouhodobe_zajezdy, s.typ_provize, s.vyse_provize,
                  (SELECT COUNT(id_klient) FROM objednavka_osoby WHERE id_objednavka = obj.id_objednavka) AS pocet_osob
                FROM objednavka obj
                  LEFT JOIN serial s ON (obj.id_serial = s.id_serial)
                  LEFT JOIN zajezd z ON (obj.id_zajezd = z.id_zajezd)
                  LEFT JOIN foto_serial fs ON (s.id_serial = fs.id_serial)
                  LEFT JOIN foto f ON (fs.id_foto = f.id_foto)
                WHERE obj.id_objednavka = ?  
                order by fs.zakladni_foto desc limit 1
                ;";
        $params = array($params[0]);
        $sqlQuery = new SQLQuery($sql, $params);
        //&& (fs.zakladni_foto = ? || fs.zakladni_foto IS NULL)
        //var_dump($sqlQuery);
        return $sqlQuery;
    }

    public static function readObjednavajiciByObjednavkaIdSQL($params)
    {
        $sql = "SELECT uk.*
                FROM objednavka obj
                  RIGHT JOIN user_klient uk ON (obj.id_klient = uk.id_klient)
                WHERE obj.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readObjednavajiciOrganizaceByObjednavkaIdSQL($params)
    {
        $sql = "SELECT org.*
                FROM objednavka obj
                  RIGHT JOIN organizace org ON (obj.id_organizace = org.id_organizace)
                  LEFT JOIN organizace_adresa orga ON (org.id_organizace = orga.id_organizace)
                WHERE obj.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readProvizniAgenturaByObjednavkaIdSQL($params)
    {
        $sql = "SELECT org.*, orga.*, p.provizni_koeficient
                FROM objednavka obj
                  RIGHT JOIN organizace org ON (obj.id_agentury = org.id_organizace)
                  LEFT JOIN organizace_adresa orga ON (org.id_organizace = orga.id_organizace)
                  LEFT JOIN prodejce p ON (org.id_organizace = p.id_organizace)
                WHERE obj.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readUcastniciByObjednavkaIdSQL($params)
    {
        $sql = "SELECT uk.*, oo.storno
                FROM objednavka obj
                  JOIN objednavka_osoby oo ON (obj.id_objednavka = oo.id_objednavka)
                  JOIN user_klient uk ON (oo.id_klient = uk.id_klient)
                WHERE obj.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function countUcastnici($params)
    {
        $sql = "SELECT count(uk.id_klient) as pocet
                FROM objednavka obj
                  JOIN objednavka_osoby oo ON (obj.id_objednavka = oo.id_objednavka and oo.storno = 0)
                  JOIN user_klient uk ON (oo.id_klient = uk.id_klient)
                WHERE obj.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }
    
    public static function readSluzbyByObjednavkaIdSQL($params)
    {
        $typRucnePridanaSluzba = SluzbaEnt::TYP_RUCNE_PRIDANA;
        $sql = "(SELECT c.id_cena, c.nazev_ceny, c.typ_ceny, c.typ_provize, c.vyse_provize, oc.pocet, oc.pocet_storno, COALESCE(oc.cena_castka, cz.castka) AS castka, COALESCE(oc.cena_mena, cz.mena) AS mena, COALESCE(oc.use_pocet_noci, c.use_pocet_noci) AS use_pocet_noci
                FROM objednavka obj
                  JOIN cena_zajezd cz ON (obj.id_zajezd = cz.id_zajezd)
                  JOIN cena c ON (cz.id_cena = c.id_cena)
                  LEFT JOIN objednavka_cena oc ON (c.id_cena = oc.id_cena && oc.id_objednavka = ?)
                WHERE obj.id_objednavka = ?
                ORDER BY c.poradi_ceny)
                UNION ALL
                (SELECT oc2.id_cena, oc2.nazev_ceny, '$typRucnePridanaSluzba' AS typ_ceny, 0, 0, oc2.pocet, oc2.pocet_storno, oc2.castka, oc2.mena, oc2.use_pocet_noci
                FROM objednavka obj
                    RIGHT JOIN objednavka_cena2 oc2 ON (obj.id_objednavka = oc2.id_objednavka)
                WHERE obj.id_objednavka = ? && (oc2.pocet != 0 || oc2.pocet_storno != 0)
                ORDER BY c.poradi_ceny);";

        $sqlQuery = new SQLQuery($sql, $params);
//        CommonUtils::printQuery($sqlQuery);
        return $sqlQuery;
    }

    public static function readFakturaProdejceByObjednavkaIdSQL($params)
    {
        $sql = "SELECT fp.*
                FROM faktury_provize fp
                WHERE fp.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readFakturyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT f.*
                FROM faktury f
                WHERE f.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readPlatbyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT op.*
                FROM objednavka_platba op
                WHERE op.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readZajezdByZajezdIdSQL($params)
    {
        $sql = "SELECT z.*
                FROM zajezd z
                WHERE z.id_zajezd = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }    
    
    public static function readSlevyKlientByObjednavkaId($params)
    {
        $slevaTypNeobjednana = "'" . SlevaEnt::SLEVA_TYP_NEOBJEDNANA . "'";
        $slevaTypObjednana = "'" . SlevaEnt::SLEVA_TYP_OBJEDNANA . "'";
        $sql = "(
                  SELECT s.id_slevy, s.nazev_slevy COLLATE utf8_general_ci AS nazev_slevy, s.castka, s.mena COLLATE utf8_general_ci AS mena, s.sleva_staly_klient, $slevaTypNeobjednana AS typ
                  FROM slevy s
                    RIGHT JOIN slevy_zajezd sz ON (sz.id_slevy = s.id_slevy)
                  WHERE
                    sz.id_zajezd = (SELECT id_zajezd FROM objednavka WHERE id_objednavka = ?) &&
                    sz.platnost = ? &&
                    (s.platnost_od = '0000-00-00' || s.platnost_od <= ?) &&
                    (s.platnost_do = '0000-00-00' || s.platnost_do >= ?)
                ) UNION ALL (
                  SELECT s.id_slevy, s.nazev_slevy COLLATE utf8_general_ci AS nazev_slevy, s.castka, s.mena COLLATE utf8_general_ci AS mena, s.sleva_staly_klient, $slevaTypNeobjednana AS typ
                  FROM slevy s
                    RIGHT JOIN slevy_serial ss ON(ss.id_slevy = s.id_slevy)
                  WHERE
                    ss.id_serial = (SELECT id_serial FROM objednavka WHERE id_objednavka = ?) &&
                    ss.platnost = ? &&
                    (s.platnost_od = '0000-00-00' || s.platnost_od <= ?) &&
                    (s.platnost_do = '0000-00-00' || s.platnost_do >= ?)
                ) UNION ALL (
                  SELECT CONCAT(os.id_objednavka, os.nazev_slevy, os.velikost_slevy) AS id_slevy, os.nazev_slevy, os.velikost_slevy AS castka, os.mena, 0 AS sleva_staly_klient, $slevaTypObjednana AS typ
                  FROM objednavka_sleva os
                  WHERE
                    os.id_objednavka = ?
                )";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readSmluvniPodminkyByObjednavkaIdSQL($params)
    {
        $sql = "SELECT spn.*, sp.*
                FROM objednavka o
                LEFT JOIN serial s ON (o.id_serial = s.id_serial)
                LEFT JOIN smluvni_podminky_nazev spn ON (s.id_sml_podm = spn.id_smluvni_podminky_nazev)
                LEFT JOIN smluvni_podminky sp ON (spn.id_smluvni_podminky_nazev = sp.id_smluvni_podminky_nazev)
                WHERE o.id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readObjednavkaSluzbaByIdSQL($params)
    {
        $sql = "(SELECT oc.id_cena, oc.pocet, oc.pocet_storno
                FROM objednavka_cena oc
                WHERE oc.id_objednavka = ? && oc.id_cena = ?)
                UNION ALL
                (SELECT oc2.id_cena, oc2.pocet, oc2.pocet_storno
                FROM objednavka_cena2 oc2
                WHERE oc2.id_objednavka = ? && oc2.id_cena = ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readTOKBySluzbaZajezdIdSQL($params)
    {
        $sql = "SELECT okt.*, ok.*
                FROM cena_zajezd_tok czt
                  LEFT JOIN objekt_kategorie_termin okt ON (czt.id_termin = okt.id_termin)
                  LEFT JOIN objekt_kategorie ok ON (okt.id_objekt_kategorie = ok.id_objekt_kategorie)
                WHERE czt.id_cena = ? && czt.id_zajezd = ?
                ORDER BY okt.kapacita_bez_omezeni IN (1), okt.kapacita_volna DESC;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function readObjektyBySerialId($params)
    {
        $sql = "SELECT o.*
                FROM objekt o
                  LEFT JOIN objekt_serial os ON (o.id_objektu = os.id_objektu)
                WHERE os.id_serial = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveObecneInfoTerminByObjednavkaId($params)
    {
        $sql = "UPDATE objednavka
                SET
                  termin_od = ?,
                  termin_do = ?,
                  pocet_noci = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }
    
    public static function saveCountUcastnici($params)
    {
        $sql = "UPDATE objednavka
                SET
                  pocet_osob = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveTsPoznamkyByObjednavkaIdSQL($params)
    {
        $sql = "UPDATE objednavka
                SET
                  poznamky = COALESCE(?, poznamky),
                  poznamky_tajne = COALESCE(?, poznamky_tajne),
                  doprava = COALESCE(?, doprava),
                  stravovani = COALESCE(?, stravovani),
                  ubytovani = COALESCE(?, ubytovani),
                  pojisteni = COALESCE(?, pojisteni)
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveTsKUhradeZalohaByObjednavkaIdSQL($params)
    {
        $sql = "UPDATE objednavka
                SET
                  k_uhrade_zaloha = ?,
                  k_uhrade_zaloha_datspl = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveTsKUhradeDoplatekByObjednavkaIdSQL($params)
    {
        $sql = "UPDATE objednavka
                SET
                  k_uhrade_doplatek_datspl = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveFinancePlatbaByIdSQL($params)
    {
        $sql = "UPDATE objednavka_platba
                SET cislo_dokladu = ?, typ_dokladu = ?, castka = ?, splatit_do = ?, splaceno = ?, zpusob_uhrady = ?
                WHERE id_platba = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveOsobyUcastnikByIdSQL($params)
    {
        $sql = "UPDATE user_klient
                SET
                  titul = COALESCE(?, titul),
                  jmeno = COALESCE(?, jmeno),
                  prijmeni = COALESCE(?, prijmeni),
                  datum_narozeni = COALESCE(?, datum_narozeni),
                  rodne_cislo = COALESCE(?, rodne_cislo),
                  email = COALESCE(?, email),
                  telefon = COALESCE(?, telefon),
                  cislo_op = COALESCE(?, cislo_op),
                  cislo_pasu = COALESCE(?, cislo_pasu),
                  ulice = COALESCE(?, ulice),
                  mesto = COALESCE(?, mesto),
                  psc = COALESCE(?, psc)
                WHERE id_klient = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveProvizniAgenturaSQL($params)
    {
        $sql = "UPDATE objednavka
                SET id_agentury = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveObjednavajiciOsobaSQL($params)
    {
        $sql = "UPDATE objednavka
                SET id_klient = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveObjednavajiciOrganizaceSQL($params)
    {
        $sql = "UPDATE objednavka
                SET id_organizace = ?
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveObecneInfoStavSQL($params, $datum_storna = "0000-00-00")
    {
       
        $sql = "UPDATE objednavka
                SET stav = ?, storno_poplatek = COALESCE(?, storno_poplatek), storno_datum = \"".$datum_storna."\"
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveObecneInfoStornoPoplatekSQL($params) {
        $sql = "UPDATE objednavka
                SET storno_poplatek = COALESCE(?, storno_poplatek), storno_datum = ".Date("Y-m-d")."
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function saveFinanceProvizeSQL($params)
    {
        $sql = "UPDATE objednavka
                SET suma_provize = COALESCE(?, suma_provize)
                WHERE id_objednavka = ?";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function createFinancePlatbaSQL($params)
    {
        $sql = "INSERT INTO objednavka_platba
                (id_objednavka, cislo_dokladu, typ_dokladu, castka, vystaveno, splatit_do, splaceno, zpusob_uhrady, id_user_create, id_user_edit)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function createOsobyUserKlientSQL($params)
    {
        $sql = "INSERT INTO user_klient
                (titul, jmeno, prijmeni, datum_narozeni, rodne_cislo, email, telefon, cislo_op, cislo_pasu, ulice, mesto, psc, vytvoren_ck, id_user_create, id_user_edit)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function createSluzbyManualSluzbaSQL($params)
    {
        $sql = "INSERT INTO objednavka_cena2
                (id_objednavka, pocet, nazev_ceny, castka, mena, use_pocet_noci)
                VALUES
                (?, ?, ?, ?, ?, COALESCE(?, 0));";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function createSlevyManualSlevaSQL($params)
    {
        $sql = "INSERT INTO objednavka_sleva
                (id_objednavka, nazev_slevy, velikost_slevy, mena)
                VALUES
                (?, ?, ?, ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function addOsobyUserKlientToObjednavkaSQL($params)
    {
        $sql = "INSERT INTO objednavka_osoby
                (id_objednavka, id_klient)
                VALUES
                (?, ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function addExistingUcastnikSQL($params)
    {
        $sql = "INSERT INTO objednavka_osoby
                (id_objednavka, id_klient)
                VALUES
                (?, ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function addSluzbaToObjednavkaSQL($params)
    {
        $sql = "INSERT INTO objednavka_cena
                (id_objednavka, id_cena, pocet, cena_castka, cena_mena, use_pocet_noci)
                SELECT ?, ?, ?, cz.castka, cz.mena, (
                  SELECT use_pocet_noci FROM cena c WHERE c.id_cena = ?
                )
                FROM cena_zajezd cz
                WHERE cz.id_cena = ? && cz.id_zajezd = (
                  SELECT id_zajezd FROM objednavka obj WHERE id_objednavka = ?
                );";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function removeOsobyUcastnikByIdSQL($params)
    {
        $sql = "DELETE FROM objednavka_osoby
                WHERE id_klient = ? && id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function deleteFinancePlatbaByIdSQL($params)
    {
        $sql = "DELETE FROM objednavka_platba
                WHERE id_platba = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function deleteSluzbaFromObjednavkaSQL($params)
    {
        $sql = "DELETE FROM objednavka_cena
                WHERE id_objednavka = ? && id_cena = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function deleteManualSluzbaFromObjednavkaSQL($params)
    {
        $sql = "DELETE FROM objednavka_cena2
                WHERE id_objednavka = ? && id_cena = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function deleteSlevaFromObjednavkaSQL($params)
    {
        $sql = "DELETE FROM objednavka_sleva
                WHERE id_objednavka = ? && nazev_slevy = ? && velikost_slevy = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function payFakturaProvizeByIdSQL($params)
    {
        $sql = "UPDATE faktury_provize
                SET zaplaceno = 1
                WHERE id_faktury = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function changeObjednavkaStavByIdSQL($params)
    {
        $sql = "UPDATE objednavka
                SET stav = ?
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function changeObjednavkaCelkovaCastkaByIdSQL($params)
    {
        $sql = "UPDATE objednavka
                SET celkova_cena = ?
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }
    
    public static function changeSerialAndZajezdById($params)
    {
        $sql = "UPDATE objednavka
                SET id_serial = ?, id_zajezd = ?
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    /**
     * @param string $mode ObjednavkySQLBuilder::PLUS_MINUS_MODE_PLUS nebo ObjednavkySQLBuilder::PLUS_MINUS_MODE_MINUS
     * @param $type
     * @param $params
     * @return null|SQLQuery
     */
    public static function plusMinusSluzbaPocetSQL($mode, $type, $params)
    {
        //plus / minus mode
        if ($mode == self::PLUS_MINUS_MODE_MINUS) {
            $sign = '-';
        } else if ($mode == self::PLUS_MINUS_MODE_PLUS) {
            $sign = '+';
        } else return null;

        //normalni / rucne pridana sluzba
        if ($type == SluzbaEnt::TYP_RUCNE_PRIDANA) {
            $table = objednavka_cena2;
        } else {
            $table = objednavka_cena;
        }

        $sql = "UPDATE $table
                SET pocet = COALESCE(pocet, 0) $sign 1
                WHERE id_objednavka = ? && id_cena = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function plusMinusSluzbaPocetStornoSQL($mode, $type, $params)
    {
        //plus / minus mode
        if ($mode == self::PLUS_MINUS_MODE_MINUS) {
            $sign = '-';
        } else if ($mode == self::PLUS_MINUS_MODE_PLUS) {
            $sign = '+';
        } else return null;

        //normalni / rucne pridana sluzba
        if ($type == SluzbaEnt::TYP_RUCNE_PRIDANA) {
            $table = objednavka_cena2;
        } else {
            $table = objednavka_cena;
        }

        $sql = "UPDATE $table
                SET pocet_storno = COALESCE(pocet_storno, 0) $sign 1
                WHERE id_objednavka = ? && id_cena = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function plusMinusPocetOsobObjednavkaSQL($mode, $params)
    {
        if($mode == self::PLUS_MINUS_MODE_MINUS) {
            $sign = '-';
        } else if ($mode == self::PLUS_MINUS_MODE_PLUS) {
            $sign = '+';
        } else return null;

        $sql = "UPDATE objednavka
                SET pocet_osob = COALESCE(pocet_osob, 0) $sign 1
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function plusMinusStornoPoplatekSQL($mode, $params)
    {
        if($mode == self::PLUS_MINUS_MODE_MINUS) {
            $sign = '-';
        } else if ($mode == self::PLUS_MINUS_MODE_PLUS) {
            $sign = '+';
        } else return null;

        $sql = "UPDATE objednavka
                SET storno_poplatek = COALESCE(storno_poplatek, 0) $sign ?
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function clearObjednavajiciOrganizaceSQL($params)
    {
        $sql = "UPDATE objednavka
                SET id_organizace = NULL
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function clearObjednavajiciOsobaSQL($params)
    {
        $sql = "UPDATE objednavka
                SET id_klient = NULL
                WHERE id_objednavka = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function getCelkovaKapacitaCenySQL($params)
    {
        $sql = "select sum(objednavka_cena.pocet) as pocet 
		from objednavka_cena 
		join objednavka on (objednavka_cena.id_objednavka = objednavka.id_objednavka 
			and objednavka_cena.id_cena=? 
			and objednavka.id_zajezd=? 
			and objednavka.stav > 2 and objednavka.stav < 8);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }



    public static function reserveKapacitySluzbySQL($params)
    {
        $sql = "UPDATE cena_zajezd
                SET kapacita_volna = kapacita_celkova - ?
                WHERE id_cena = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function reserveKapacityTOKSQL($params)
    {
        $sql = "UPDATE objekt_kategorie_termin
                SET kapacita_volna = kapacita_volna - ?
                WHERE id_termin = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function pridejOvlivneneTOKKObjednavceSQL($params)
    {
        $sql = "INSERT INTO objednavka_tok
                (id_objednavka, id_termin, id_objekt_kategorie, pocet)
                VALUES
                (?, ?, ?, ?);";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function refreshObjednanaSluzbaCenaSQL($params)
    {
        $sql = "UPDATE objednavka_cena
                SET
                  cena_castka = (SELECT castka FROM cena_zajezd WHERE id_cena = ? && id_zajezd = (SELECT id_zajezd FROM objednavka WHERE id_objednavka = ?)),
                  cena_mena = (SELECT mena FROM cena_zajezd WHERE id_cena = ? && id_zajezd = (SELECT id_zajezd FROM objednavka WHERE id_objednavka = ?))
                WHERE id_objednavka = ? && id_cena = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function stornoObjednavkaUcastnikSQL($params)
    {
        $sql = "UPDATE objednavka_osoby
                SET storno = 1
                WHERE id_objednavka = ? && id_klient = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function stornoUndoObjednavkaUcastnikSQL($params)
    {
        $sql = "UPDATE objednavka_osoby
                SET storno = 0
                WHERE id_objednavka = ? && id_klient = ?;";

        $sqlQuery = new SQLQuery($sql, $params);
        return $sqlQuery;
    }

    public static function centralniDataByName($params)
    {
        $sql = "SELECT *
                FROM centralni_data
                WHERE nazev LIKE :nazev
                ORDER BY nazev";
        $query = new SQLQuery($sql, $params);

        return $query;
    }
}