<?php

class SlouceniKlientuModel {

    const GET_RECENT_CLIENTS_PAGING_LIMIT = 100;

    /**
     *
     * @var Database
     */
    public static $db;

    /**
     * @var String nazev tabulky klientu
     */
    public static $TBL_USER_KLIENT;
    /**
     * @var String nazev tabulky objednavky
     */
    public static $TBL_OBJEDNAVKA;
    /**
     * @var String nazev tabulky osob figurujicich v objednavce
     */
    public static $TBL_OBJEDNAVKA_OSOBY;
    /**
     * @var String nazev pole pro ukladani parametru ziskanych z pozadavku na server $_REQUEST
     */
    const REQUEST_VALUES = "values";

    /***************************** PUBLIC METHODS ******************************/

    /**
     * Klienti, kteri nemaji vyplnene ZADNE informace (jmeno, prijmeni, ulice, mesto, psc) a nejsou ucastniky obj (mohou byt objednateli obj)
     * @return array
     */
    public static function getClientNoInfo()
    {
        $clients = null;

        $sql = "SELECT k.*, o.id_objednavka id_obj_objednavatel
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON (k.id_klient = o.id_klient)
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON (k.id_klient = oo.id_klient)
                WHERE k.jmeno = '' && k.prijmeni = '' && k.ulice = '' && k.mesto = '' && k.psc = ''
                GROUP BY k.id_klient
                HAVING COUNT(oo.id_klient) = 0;";
        $result = SlouceniKlientuModel::$db->query($sql);

        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    /**
     * Klienti, kteri nemaji platny email a zaroven nefiguruji v zadne objednavce
     * @return array
     */
    public static function getInvalidEmailsNoObj()
    {
        $clients = null;
        //Lada: tenhle dotaz je tak narocny ze skoro nikdy nedobehne, zkusim zjednodusenou kontrolu na prazdny e-mail
        //WTF proc takhle slozite? vzdyt ten e-mail neni potreba, ne?
        /*$sql = "SELECT k.email
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.email NOT REGEXP '^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]@[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]\.[a-zA-Z]{2,4}$' && o.id_klient IS NULL && oo.id_klient IS NULL
                GROUP BY k.email;";   */
        $sql = "SELECT k.id_klient, k.jmeno, k.prijmeni
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.email NOT REGEXP '^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]@[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]\.[a-zA-Z]{2,4}$' and o.id_klient IS NULL and oo.id_klient IS NULL                
                limit 300";  
         /*       
        $sql = "SELECT k.id_klient, k.jmeno, k.prijmeni
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.email == '' && o.id_klient IS NULL && oo.id_klient IS NULL                
                limit 300"; */
        //LP major simplification for the sake of performance                       
        
        $result = SlouceniKlientuModel::$db->query($sql);
        $clients = [];
        while($row = mysqli_fetch_object($result)) {
            /*if(array_key_exists($row->jmeno."_".$row->prijmeni, $clients)){
                $clients[$row->jmeno."_".$row->prijmeni]->clientsId[] = $row->id_klient;
                $clients[$row->jmeno."_".$row->prijmeni]->clientsFirstname[] = $row->jmeno;
                $clients[$row->jmeno."_".$row->prijmeni]->clientsLastname[] = $row->prijmeni;
            }else{ */
                $clients[$row->jmeno."_".$row->prijmeni."_".$row->id_klient] = $row;
                $clients[$row->jmeno."_".$row->prijmeni."_".$row->id_klient]->clientsId[] = $row->id_klient;
                $clients[$row->jmeno."_".$row->prijmeni."_".$row->id_klient]->clientsFirstname[] = $row->jmeno;
                $clients[$row->jmeno."_".$row->prijmeni."_".$row->id_klient]->clientsLastname[] = $row->prijmeni;
            //}
            
            /*
            $sql = "SELECT k.id_klient, k.jmeno, k.prijmeni
                    FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                        LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                        LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                    WHERE k.jmeno = '$row->jmeno' && k.prijmeni = '$row->prijmeni' && o.id_klient IS NULL && oo.id_klient IS NULL";
            $result2 = SlouceniKlientuModel::$db->query($sql);
            $ids = array();
            $names = array();
            $lastnames = array();
            while ($k = mysqli_fetch_object($result2)) {
                $ids[] = $k->id_klient;
                $names[] = $k->jmeno;
                $lastnames[] = $k->prijmeni;
            }
            $row->clientsId = $ids;
            $row->clientsFirstname = $names;
            $row->clientsLastname = $lastnames;
            $clients[] = $row;*/
        }
        /*
         * $clients je pole klientu
         * kazdy ma sve pole klientsId
         */

        return $clients;
    }

    /**
     * Klienti, kteri nemaji platny email a figuruji v objednavce
     * @return array
     */
    public static function getInvalidEmailsWithObj()
    {
        $clients = null;

        $sql = "SELECT k.jmeno, k.prijmeni, k.email, k.id_klient, o.id_objednavka id_obj_objednavatel, oo.id_objednavka id_obj_ucastnik
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.email != '' && k.email NOT REGEXP '^[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]@[a-zA-Z0-9][a-zA-Z0-9._-]*[a-zA-Z0-9]\.[a-zA-Z]{2,4}$' && 
                    (o.id_klient IS NOT NULL || oo.id_klient IS NOT NULL);";
        $result = SlouceniKlientuModel::$db->query($sql);

        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    /**
     * Testovaci klienti
     * @return array
     */
    public static function getTestClients()
    {
        $clients = null;

        $sql = "SELECT k.email, k.id_klient, k.jmeno, k.prijmeni, o.id_objednavka id_obj_objednavatel, oo.id_objednavka id_obj_ucastnik
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.email LIKE '%test%' || k.jmeno LIKE '%test%' || k.prijmeni LIKE '%test%';";
        $result = SlouceniKlientuModel::$db->query($sql);

        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    /**
     * Klienti filtrovani dle parametru (email nesmi byt prazdny)
     * @param $firstname String kresti jmeno klienta
     * @param $surname String prijmeni klienta
     * @param $email String email klienta
     * @param $chFirstname
     * @param $chSurname
     * @param $chStreet
     * @param $chCity
     * @param $chPsc
     * @return array|null pole klientu
     */
    public static function getFilteredClients($firstname, $surname, $email, $chFirstname, $chSurname, $chStreet, $chCity, $chPsc)
    {
        $clients = $groupBy = null;
        $firstname = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$firstname);
        $surname = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$surname);
        $email = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$email);
        $sqlFirstname = is_null($chFirstname) ? "&& 1=1" : "&& COUNT(k.jmeno) > 1";
        $sqlSurname = is_null($chSurname) ? "&& 1=1" : "&& COUNT(k.prijmeni) > 1";
        $sqlStreet = is_null($chStreet) ? "&& 1=1" : "&& COUNT(k.ulice) > 1";
        $sqlCity = is_null($chCity) ? "&& 1=1" : "&& COUNT(k.mesto) > 1";
        $sqlPsc = is_null($chPsc) ? "&& 1=1" : "&& COUNT(k.psc) > 1";
        $groupBy .= is_null($chFirstname) ? "" : ", k.jmeno";
        $groupBy .= is_null($chSurname) ? "" : ", k.prijmeni";
        $groupBy .= is_null($chStreet) ? "" : ", k.ulice";
        $groupBy .= is_null($chCity) ? "" : ", k.mesto";
        $groupBy .= is_null($chPsc) ? "" : ", k.psc";
        $groupBy = substr($groupBy, 2);

        //TODO: zjoinovat az po selectu
        $sql = "SELECT k.email, k.id_klient, k.jmeno, k.prijmeni, k.mesto, k.ulice, k.psc, o.id_objednavka id_obj_objednavatel, oo.id_objednavka id_obj_ucastnik
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.jmeno LIKE '%$firstname%' && k.prijmeni LIKE '%$surname%' && k.email LIKE '%$email%'
                GROUP BY $groupBy
                HAVING 1=1 $sqlFirstname $sqlSurname $sqlStreet $sqlCity $sqlPsc;";
        $result = SlouceniKlientuModel::$db->query($sql);
//        echo "$sql<br/>";
        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    /**
     * Klienti, kteri maji stejne parametry jako klient identifikovany prametrem
     * @param $id int identifikator klienta
     * @param null $chFirstname
     * @param null $chSurname
     * @param null $chStreet
     * @param null $chCity
     * @param null $chPsc
     * @return array|null pole klientu
     */
    public static function getClientsToMerge($id, $chFirstname = null, $chSurname = null, $chStreet = null, $chCity = null, $chPsc = null)
    {
        $clients = null;
        $id = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$id);

        $sqlFirstname = is_null($chFirstname) ? "&& 1=1" : "&& k.jmeno = (SELECT jmeno FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " WHERE id_klient = $id)";
        $sqlSurname = is_null($chSurname) ? "&& 1=1" : "&& k.prijmeni = (SELECT prijmeni FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " WHERE id_klient = $id)";
        $sqlStreet = is_null($chStreet) ? "&& 1=1" : "&& k.ulice = (SELECT ulice FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " WHERE id_klient = $id)";
        $sqlCity = is_null($chCity) ? "&& 1=1" : "&& k.mesto = (SELECT mesto FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " WHERE id_klient = $id)";
        $sqlPsc = is_null($chPsc) ? "&& 1=1" : "&& k.psc = (SELECT psc FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " WHERE id_klient = $id)";
        $sql = "SELECT k.email, k.id_klient, k.jmeno, k.prijmeni, o.id_objednavka id_obj_objednavatel, oo.id_objednavka id_obj_ucastnik
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE 1=1
                    $sqlFirstname $sqlSurname $sqlStreet $sqlCity $sqlPsc && k.id_klient != $id;";
        $result = SlouceniKlientuModel::$db->query($sql);
//      echo "$sql<br/>";
        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    /**
     * Klienti filtrovani dle parametru (email nesmi byt prazdny)
     * @param int $limitStart
     * @return array|null
     */
    public static function getRecentClients($limitStart = 0)
    {
        $clients = null;
        $sql = "SELECT uk.*, o.id_objednavka, o.datum_rezervace
                FROM 
                    (SELECT k.*, COUNT(*) count
                    FROM user_klient k
                    WHERE k.jmeno != '' && k.prijmeni != '' && k.email != '' && k.datum_narozeni != '0000-00-00'
                    GROUP BY k.jmeno, k.prijmeni, k.email, k.datum_narozeni
                    HAVING count > 1) AS uk
                        LEFT JOIN objednavka_osoby oo ON (uk.id_klient = oo.id_klient)
                        LEFT JOIN objednavka o ON (oo.id_objednavka = o.id_objednavka)
                WHERE oo.id_objednavka IS NOT NULL 
                ORDER BY o.datum_rezervace DESC
                LIMIT $limitStart, " . self::GET_RECENT_CLIENTS_PAGING_LIMIT . ";";
        $result = SlouceniKlientuModel::$db->query($sql);
        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    public static function getRecentClientsToMerge($client)
    {
        $clients = null;
        $sql = "SELECT k.*, o.id_objednavka id_obj_objednavatel, oo.id_objednavka id_obj_ucastnik
                    FROM user_klient k
                        LEFT JOIN objednavka o ON (k.id_klient = o.id_klient)
                        LEFT JOIN objednavka_osoby oo ON (k.id_klient = oo.id_klient)
                    WHERE k.jmeno = '$client->jmeno' 
                          && k.prijmeni = '$client->prijmeni' 
                          && k.email = '$client->email' 
                          && k.datum_narozeni = '$client->datum_narozeni' 
                          && k.id_klient != $client->id_klient";

        $result = SlouceniKlientuModel::$db->query($sql);
        while ($row = mysqli_fetch_object($result)) {
            $clients[] = $row;
        }

        return $clients;
    }

    /**
     * Smaze vsechny oznacene klienty no info
     * @return IdsNotDeleted vraci objekt s atributem $succes true pokud byli vsichni klienti smazani, jinak je atribut false, pokud alespon jeden klient nebyl
     * smazan a atribut ids obsahuje pole nesmazanych idecek
     */
    public static function delClientsNoInfo()
    {
        $idsNotDeleted = new IdsNotDeleted();
        foreach ($_REQUEST["cb_clientNoInfo"] as $allIds) {
            $id = explode("-", $allIds);
            $idClient = $id[0];
            $idObjCObjednatel = $id[1];
            SlouceniKlientuModel::$db->start_transaction();

            $delClient = SlouceniKlientuModel::delClientById($idClient);
            $delObjObjednatel = $idObjCObjednatel == "" ? true : SlouceniKlientuModel::delObjById($idObjCObjednatel);
            if (!$delClient || !$delObjObjednatel) {
                SlouceniKlientuModel::$db->rollback();
                $idsNotDeleted->succes = false;
                $idsNotDeleted->ids[] = $idClient;
            } else {
                SlouceniKlientuModel::$db->commit();
            }
        }
        return $idsNotDeleted;
    }

    /**
     * Smaze vsechny oznacene klienty invalid emails no obj
     * @return boolean vraci true pokud byli vsichni klienti smazani, jinak false pokud alespone 1 klient nebyl smazan
     */
    public static function delClientsInvalidEmailsNoObj()
    {
        $succ = true;
        foreach ($_REQUEST["cb_invalidEmailsNoObj"] as $email) {
            if (!SlouceniKlientuModel::delClientByEmailNoObj($email))
                $succ = false;
        }
        return $succ;
    }

    /**
     * Smaze jednoho klienta invalid emails no obj
     * @return bool
     */
    public static function delClient()
    {
        $succ = true;
        if (!SlouceniKlientuModel::delClientById($_REQUEST["id"]))
            $succ = false;
        return $succ;
    }

    /**
     * Smaze jednoho klienta invalid emails no obj
     * @return bool
     */
    public static function delClients($ids)
    {
        $succ = true;
        foreach ($ids as $id) {
            if (!SlouceniKlientuModel::delClientById($id))
                $succ = false;
        }
        return $succ;
    }


    /**
     * Smaze vsechny oznacene test klienty i s objednavkami ve kterych figuruji
     * @return IdsNotDeleted vraci objekt s atributem $succes true pokud byli vsichni klienti smazani, jinak je atribut false, pokud alespon jeden klient nebyl
     * smazan a atribut ids obsahuje pole nesmazanych idecek
     */
    public static function delTestClientsWithObj()
    {
        $idsNotDeleted = new IdsNotDeleted();
        foreach ($_REQUEST["cb_testClients"] as $allIds) {
            $id = explode("-", $allIds);
            $idClient = $id[0];
            $idObjCObjednatel = $id[1];
            $idObjCUcastnik = $id[2];
            SlouceniKlientuModel::$db->start_transaction();

            $delClient = SlouceniKlientuModel::delClientById($idClient);
            $delObjObjednatel = $idObjCObjednatel == "" ? true : SlouceniKlientuModel::delObjById($idObjCObjednatel);
            $delObjUcastnik = $idObjCUcastnik == "" ? true : SlouceniKlientuModel::delObjById($idObjCUcastnik);
            if (!$delClient || !$delObjObjednatel || !$delObjUcastnik) {
                SlouceniKlientuModel::$db->rollback();
                $idsNotDeleted->succes = false;
                $idsNotDeleted->ids[] = $idClient;
            } else {
                SlouceniKlientuModel::$db->commit();
            }
        }
        return $idsNotDeleted;
    }

    /**
     * Zkontroluje zda je hodnota promenne nastavena
     * @param $value String promena ke zkontrolovani
     * @return bool true pokud je nastavena, jinak false
     */
    public static function isValueSet($value)
    {
        return isset($value) && $value != "" && !is_null($value);
    }

    /**
     * Vytahne promennou z $_REQUEST nebo ze $_SESSION
     * @param $name String nazev promenne
     * @return String|null hodnotu promenne vytazenou z requestu nebo ze session
     */
    public static function getRequestValue($name)
    {
        $value = null;

        if (SlouceniKlientuModel::isValueSet($_REQUEST[$name])) {
            $value = $_REQUEST[$name];
        } else if (SlouceniKlientuModel::isValueSet($_SESSION[SlouceniKlientuModel::REQUEST_VALUES][$name])) {
            $value = $_SESSION[SlouceniKlientuModel::REQUEST_VALUES][$name];
        }

        return $value;
    }

    /**
     * Spoji klienty, updatuje objednavku a smaze jiz nepotrebneho klienta
     * @param $mergedClient object klient do ktereho se udaje vkladaji
     * @param $clientToMerge object klient ze ktereho se udaje vytahuji
     * @return object
     */
    public static function mergeAndUpdateClient($mergedClient, $clientToMerge)
    {
        $clientAfterMerge = null;
        //prepis udaje z $clientToMerge do $client pokud ma nake navic
        $clientAfterMerge = SlouceniKlientuModel::mergeClientsProperties($mergedClient, $clientToMerge);
        SlouceniKlientuModel::upadteClient($clientAfterMerge);
        //pokud je $clientToMerge objednatelem nejake objednavky zmenit id_klient v tabulce objednavka na $mergedClient->id_klient
        if (!is_null($clientToMerge->id_obj_objednavatel))
            SlouceniKlientuModel::updateClientObjednavka($clientToMerge->id_obj_objednavatel, $mergedClient->id_klient);
        //pokud je $clientToMerge ucastnikem nejake objednavky zmenit id_klient v tabulce objednavka_osoby na $mergedClient->id_klient
        if (!is_null($clientToMerge->id_obj_ucastnik))
            SlouceniKlientuModel::updateClientObjednavkaOsoby($clientToMerge->id_obj_ucastnik, $clientToMerge->id_klient, $mergedClient->id_klient);
        //smaz stareho klienta z db
        SlouceniKlientuModel::delClientById($clientToMerge->id_klient);

        return $clientAfterMerge;
    }

    /**
     * Spoji udaje obou klientu
     * @param $mergedClient obj klient do ktereho se udaje vkladaji
     * @param $clientToMerge obj klient ze ktereho se udaje vytahuji
     * @return obj spojeny klient
     */
    public static function mergeClient($mergedClient, $clientToMerge)
    {
        $clientAfterMerge = null;
        //prepis udaje z $clientToMerge do $client pokud ma nake navic
        $clientAfterMerge = SlouceniKlientuModel::mergeClientsProperties($mergedClient, $clientToMerge);

        return $clientAfterMerge;
    }

    /**
     * @param $requests string[] pole pozadavku k ulozeni do session. Ve formatu "nazev-requestovaneho-pole" => "hodnota".
     */
    public static function saveRequestsInSession($requests)
    {
        foreach ($requests as $k => $v)
            SlouceniKlientuModel::saveRequestInSession($v, $k);
    }

    /**
     * Vrati klienta z databaze
     * @param int $id id klienta
     * @return null|object klient pokud byl nalezen, jinak null
     */
    public static function getClient($id)
    {
        $client = null;
        $id = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$id);

        $sql = "SELECT k.*, o.id_objednavka id_obj_objednavatel, oo.id_objednavka id_obj_ucastnik
                FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " k
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " o ON ( k.id_klient = o.id_klient )
                    LEFT JOIN " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " oo ON ( k.id_klient = oo.id_klient )
                WHERE k.id_klient = $id;";
        $result = SlouceniKlientuModel::$db->query($sql);
        while ($row = mysqli_fetch_object($result)) {
            $client = $row;
        }

        if ($client === false)
            $client = null;

        return $client;
    }

    /***************************** PRIVATE METHODS ******************************/

    /**
     * Updatuje klienta v databazi
     * @param $client obj klient ktrery ma byt updatovan
     * @return mixed vysledek databazoveho dotazu
     */
    private static function upadteClient($client)
    {
        foreach ($client as $property => $value)
            $client->$property = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$client->$property);

        $sql = "UPDATE " . SlouceniKlientuModel::$TBL_USER_KLIENT . "
                SET
                    uzivatelske_jmeno=(CASE WHEN uzivatelske_jmeno='' THEN '$client->uzivatelske_jmeno' ELSE uzivatelske_jmeno END),
                    jmeno=(CASE WHEN jmeno='' THEN '$client->jmeno' ELSE jmeno END),
                    prijmeni=(CASE WHEN prijmeni='' THEN '$client->prijmeni' ELSE prijmeni END),
                    titul=(CASE WHEN titul='' THEN '$client->titul' ELSE titul END),
                    telefon=(CASE WHEN telefon='' THEN '$client->telefon' ELSE telefon END),
                    datum_narozeni=(CASE WHEN datum_narozeni='0000-00-00' THEN '$client->datum_narozeni' ELSE datum_narozeni END),
                    cislo_pasu=(CASE WHEN cislo_pasu='' THEN '$client->cislo_pasu' ELSE cislo_pasu END),
                    cislo_op=(CASE WHEN cislo_op='' THEN '$client->cislo_op' ELSE cislo_op END),
                    rodne_cislo=(CASE WHEN rodne_cislo='' THEN '$client->rodne_cislo' ELSE rodne_cislo END),
                    ulice=(CASE WHEN ulice='' THEN '$client->ulice' ELSE ulice END),
                    mesto=(CASE WHEN mesto='' THEN '$client->mesto' ELSE mesto END),
                    psc=(CASE WHEN psc='' THEN '$client->psc' ELSE psc END),
                    ico=(CASE WHEN ico='' THEN '$client->ico' ELSE ico END)
                WHERE id_klient = $client->id_klient;";
        $result = SlouceniKlientuModel::$db->query($sql);
//echo "$sql<br/>";
        return $result;
    }

    /**
     * Zmeni objednavatele objednavky na novou hodnotu
     * @param $idObj int id objednavky, ktera se ma upravit
     * @param $idClient int id objednavatele, na ktere se ma puvodni zmenit
     * @return mixed vysledek db dotazu
     */
    private static function updateClientObjednavka($idObj, $idClient)
    {
        $sql = "UPDATE " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . "
                SET id_klient = $idClient
                WHERE id_objednavka = $idObj;";
        $result = SlouceniKlientuModel::$db->query($sql);
//        echo "$sql<br/>";

        return $result;
    }

    /**
     * Zmeni ucastnika objednavky na novou hodnotu
     * @param $idObj int id menene objednavky
     * @param $idSearch int id klienta, ktery se ma zmenit
     * @param $idChange int id klienta, na ktereho se ma puvodni zmenit
     * @return mixed
     */
    private static function updateClientObjednavkaOsoby($idObj, $idSearch, $idChange)
    {
        $sql = "UPDATE " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . "
                SET id_klient = $idChange
                WHERE id_objednavka = $idObj && id_klient = $idSearch;";
        $result = SlouceniKlientuModel::$db->query($sql);
//        echo "$sql<br/>";

        return $result;
    }

    /**
     * Projde vsechny property objektu $client, zkontroluje zda je property prazdna a pokud ano prepise ji hodnotou property objektu $clientToMerge
     * @param obj $client klient do ktereho se maji vlastnosti sloucit
     * @param obj $clientToMerge klient z ktereho se vlastnosti slucuji
     * @return obj $client zmergovany klient
     */
    private static function mergeClientsProperties($client, $clientToMerge)
    {
        //potrebuju noveho clienta ne jen referenci na stareho, kvuli zobrazeni rozdilu mezi klienty po provedeni merge
        $mergedClient = clone $client;
        foreach ($client as $property => $value) {
            //client nema nastaveno, ale clientToMerge ano
            if (!SlouceniKlientuModel::isValueSet($client->$property) && SlouceniKlientuModel::isValueSet($clientToMerge->$property)) {
                $mergedClient->$property = $clientToMerge->$property;
                //datum
            } else if ($client->$property == "0000-00-00" && $clientToMerge->$property != "0000-00-00") {
                $mergedClient->$property = $clientToMerge->$property;
            }
        }

        return $mergedClient;
    }

    /**
     * Smaze jednoho klienta dle id
     * @param $id int
     * @return bool true pokud byl klient uspesne smazan, jinak false
     */
    private static function delClientById($id)
    {
        $id = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$id);

        $sql = "DELETE FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . " WHERE id_klient = $id;";
        if (SlouceniKlientuController::$DEBUG)
            echo "$sql<br/>";
        else
            $result = SlouceniKlientuModel::$db->query($sql);

        return $result;
    }

    /**
     * Smaze klienty dle emailu
     * @param $email
     * @internal param int $id
     * @return bool true pokud byli klienti uspesne smazani, jinak false
     */
    private static function delClientByEmailNoObj($email)
    {
        $email = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$email);

        $sql = "DELETE FROM " . SlouceniKlientuModel::$TBL_USER_KLIENT . "
                WHERE email = '$email' && id_klient 
                    NOT IN (
                        SELECT id_klient FROM " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " WHERE id_klient = user_klient.id_klient
                        UNION 
                        SELECT id_klient FROM " . SlouceniKlientuModel::$TBL_OBJEDNAVKA_OSOBY . " WHERE id_klient = user_klient.id_klient
                    );";
        if (SlouceniKlientuController::$DEBUG)
            echo "$sql<br/>";
        else
            $result = SlouceniKlientuModel::$db->query($sql);

        return $result;
    }

    /**
     * Smaze objednavky dle id
     * @param $id int
     * @return bool true pokud byla objednavky uspesne smazany, jinak false
     */
    private static function delObjById($id)
    {
        $id = mysqli_real_escape_string($GLOBALS["core"]->database->db_spojeni,$id);

        $sql = "DELETE FROM " . SlouceniKlientuModel::$TBL_OBJEDNAVKA . " WHERE id_objednavka = '$id';";
        if (SlouceniKlientuController::$DEBUG)
            echo "$sql<br/>";
        else
            $result = SlouceniKlientuModel::$db->query($sql);

        return $result;
    }

    /**
     * Ulozi do session requested promennou do pole @link(SlouceniKlientuModel::REQUEST_VALUES)
     * @param $value String hodnota promenne
     * @param $name String nazev pod kterym se ma ulozit
     */
    private static function saveRequestInSession($value, $name)
    {
        $_SESSION[SlouceniKlientuModel::REQUEST_VALUES][$name] = $value;
    }

}

/**
 * "Create" - potrebuju vratit z metody vic jak jednu hodnotu, tak si vyrobim objekt slouzici pouze pro vraceni hodnot.
 * V tomhle pripade idecka, ktera nebyla smazana spolu s boolean hodnotou rikajici ano vse smazano / ne 1 a vice neni smazano
 */
class IdsNotDeleted {
    public $succes = true;
    public $ids = array();

}

?>
