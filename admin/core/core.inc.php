<?php
/**
 * core.inc.php - jádro aplikace, obsahuje tøídu Core:
 *    - singleton
 *    - pøipojí se do databáze
 *    - naloguje uživatele (nebo vytvoøí anonymního)
 *    - rozparsuje parametry scriptu
 *    - zjistí, zda je modul povolený a zda má uživatel k nìmu alespoò práva ke ètení
 */
/*--------------------- DATABAZE ----------------------------*/

class Core
{
    //instance Core
    static private $instance;


    public $db_spojeni;
    private $db_vysledek;
    private $array_moduly;//seznam všech modulù, které jsou povolené a uživatel k nim má oprávnìní
    private $current_modul; //aktualne otevreny modul

    public $database; //trida pro odesilani dotazu

    //staticka funkce pro vytvareni instance (pokud jeste neexistuje, tak ji vytvori, jinak vrati jiz existujici
    static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Core();
        }
        return self::$instance;
    }


    //konstruktor tridy
    private function __construct()
    {
        $INC_DIR = $_SERVER["DOCUMENT_ROOT"];
        require_once "$INC_DIR/global/prihlaseni_do_databaze.inc.php"; //prihlasovaci udaje do databaze

        //trida pro odesilani dotazu
        $this->database = Database::get_instance();

        //inicializace
        $adress = "";

        //pøipojení k databázi
        $this->database->connect($db_server, $db_jmeno, $db_heslo, $db_nazev_db);


        //vytvoøení uživatele
        $this->create_user();

        //zjistim id modulu na zaklade adresy
        $adresa_modulu = $_SERVER['SCRIPT_NAME'];
        $id_modulu = $this->get_modul_from_adress($adresa_modulu);
        if ($id_modulu != false) {
            //najdu vsechny moduly ke kterym ma uzivatel opravneni
            $this->array_moduly = array();
            $this->array_moduly = $this->get_all_allowed_moduls();


            //vytvorim pole id_modulu
            $array_id_modul = array();
            foreach ($this->array_moduly as $i => $modul) {
                $array_id_modul[$i] = $modul["id_modul"];
            }

            $key = array_search($id_modulu, $array_id_modul);

            //echo $key;
            if ($key !== false) {
                //otevøený modul je povolený
                $this->current_modul = array();
                $this->current_modul = $this->array_moduly[$key];
            } else {
                //pokud jsme modul nenasli, vyhodime chybovou hlasku presmerujeme uzivatele na hlavni stranku administrace a ukoncime script
                //toto neplati, pokud se uz na hlavni strance nalezame (obrana proti zacykleni) + osetreni neprihlasenych uzivatelu
                   $adress = "/admin/index.php";
     //          echo "spatny modul".$id_modulu.$adresa_modulu;

                if ($adress != "" and $adress != $adresa_modulu) {
                    $_SESSION["hlaska"] = " <h2 class=\"red\">Požadovaný modul buï není povolen, nebo k nìmu nemáte pøístupová práva!</h2>";
                    header("Location: " . $adress);
                    exit();
                }
            }
        } else {//id_modulu == false
            //pokud jsme modul nenasli, vyhodime chybovou hlasku presmerujeme uzivatele na hlavni stranku administrace a ukoncime script
            //toto neplati, pokud se uz na hlavni strance nalezame (obrana proti zacykleni) + osetreni neprihlasenych uzivatelu!!
            $adress = "/admin/index.php";
            
            

            if ($adress != "" and $adress != $adresa_modulu) {
                $_SESSION["hlaska"] = " <h2 class=\"red\">Požadovaný modul neexistuje!</h2>";
                header("Location: " . $adress);
                exit();
            }
        }
        //rozparsování parametrù
        $this->parse_parametr();

    }

    // pøipojení do databáze
    private function connect_to_database($db_server, $db_jmeno, $db_heslo, $db_nazev_db)
    {
        //pripojeni k databazi
        $this->db_spojeni = mysqli_connect($db_server, $db_jmeno, $db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $this->db_vysledek = mysqli_select_db($this->db_spojeni, $db_nazev_db) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        //nastaveni kodovani
        $this->database->query("SET character_set_results=cp1250");
        $this->database->query("SET character_set_connection=UTF8");
        $this->database->query("SET character_set_client=cp1250");
    }

    //pøihlášení uživatele
    private function create_user()
    {
        if ($_GET["typ"] == "logout") {
            $zamestnanec = User_zamestnanec::logout();
            $_SESSION["jmeno"] = "";
            $_SESSION["heslo"] = "";
            $_SESSION["last_logon"] = "";

            /*prijimam data z prihlasovaciho formulare*/
        } else if ($_GET["typ"] == "login") {
            $_GET["typ"] = "";
            $zamestnanec = User_zamestnanec::get_instance($_POST["name"], $_POST["passwd"]);

            /*nalezl jsem data v sessions, heslo neni stejne jako $_POST["passwd"], ale jeho hash, tedy musim
                pro prihlaseni pouzit jiny algoritmus (3. parametr v konstruktoru)*/
        } else if ($_SESSION["jmeno"] != "") {
           
            $zamestnanec = User_zamestnanec::get_instance($_SESSION["jmeno"], $_SESSION["heslo"], 1);

            /*anonymni uzivatel*/
        } else {
            $zamestnanec = User_zamestnanec::get_instance();
        }

        /*upraveni hlasky*/
        if ($_GET["typ"] != "logout") {
            if (!$zamestnanec->get_error_message()) {
                $_SESSION["hlaska"] = $_SESSION["hlaska"] . $zamestnanec->get_ok_message();
            } else {
                $_SESSION["hlaska"] = $zamestnanec->get_error_message();
            }
        }
    }


    //vrati pole se vsemi moduly, ktere jsou povolene a uzivatel k nim ma pristup
    private function get_all_allowed_moduls()
    {
        $zamestnanec = User_zamestnanec::get_instance();
        $array_moduly = array();
        //dotaz na vsechny moduly, ke kterym ma uzivatel pristupove pravo
        $dotaz_moduly = "
			SELECT * 
			FROM `modul_administrace`
				JOIN `user_zamestnanec_prava` ON (`modul_administrace`.`id_modul` = `user_zamestnanec_prava`.`id_modul`)
			WHERE `modul_administrace`.`povoleno` = 1 AND
					`user_zamestnanec_prava`.`prava` > 0 AND
					`user_zamestnanec_prava`.`id_user` = " . intval($zamestnanec->get_id()) . "
			ORDER BY `modul_administrace`.`nazev_modulu`";
      //  echo $dotaz_moduly;
        $data_moduly = $this->database->query($dotaz_moduly);

        while ($modul = mysqli_fetch_array($data_moduly, MYSQLI_ASSOC)) {
            //pridam modul do pole
            $array_moduly[] = $modul;
        }
     //   print_r($array_moduly);
        return $array_moduly;
    }

    function show_all_allowed_moduls()
    {
        return $this->array_moduly;
    }

    function show_current_modul()
    {
        return $this->current_modul;
    }

    function show_nazev_modulu()
    {
        return $this->current_modul["nazev_modulu"];
    }

    function get_id_modul()
    {
        return $this->current_modul["id_modul"];
    }

    function show_napoveda()
    {
        return $this->current_modul["napoveda"];
    }

    //vrati pole se vsemi moduly, ktere jsou povolene
    private function get_all_possible_moduls()
    {
        $array_moduly = array();
        //dotaz na vsechny moduly, ke kterym ma uzivatel pristupove pravo
        $dotaz_moduly = "
			SELECT * 
			FROM `modul_administrace`				
			WHERE `modul_administrace`.`povoleno` = 1";
        $data_moduly = $this->database->query($dotaz_moduly);

        while ($modul = mysqli_fetch_array($data_moduly, MYSQLI_ASSOC)) {
            //pridam modul do pole
            $array_moduly[] = $modul;
        }

        return $array_moduly;
    }


    //vrati id modulu s adresou $adress nebo false, pokud žádný neexistuje
    private function get_modul_from_adress($adress)
    {
        $dotaz_modul = "
			SELECT * FROM `modul_administrace`
			WHERE `adresa_modulu` = '" . $adress . "' LIMIT 1";

        $modul = mysqli_fetch_array($this->database->query($dotaz_modul));
        if ($modul["id_modul"] != "") {
            return $modul["id_modul"];
        } else {
            return false;
        }

    }

    //zjistí na základì typu modulu, jestli je povolený a uživatel má dostateèné oprávnìní
    function get_id_modul_from_typ($typ_modulu)
    {
        $array_typ_modul = array();
        foreach ($this->array_moduly as $i => $modul) {
            $array_typ_modul[$i] = $modul["typ_modulu"];
        }

        $key = array_search($typ_modulu, $array_typ_modul);
        //pokud jsme modul v seznamu povolených nalezli -> vratime id modulu, jinak false
        if ($key !== false) {
            return $this->array_moduly[$key]["id_modul"];
        } else {
            return false;
        }
    }

    //zjistí na základì typu modulu, jestli je povolený a uživatel má dostateèné oprávnìní
    function get_adress_modul_from_typ($typ_modulu)
    {
        $array_typ_modul = array();
        foreach ($this->array_moduly as $i => $modul) {
            $array_typ_modul[$i] = $modul["typ_modulu"];
        }

        $key = array_search($typ_modulu, $array_typ_modul);
        //pokud jsme modul v seznamu povolených nalezli -> vratime id modulu, jinak false
        if ($key !== false) {
            return $this->array_moduly[$key]["adresa_modulu"];
        } else {
            return false;
        }
    }

    //zjistí na základì názvu modulu, jestli je povolený a uživatel má dostateèné oprávnìní
    function modul_allowed($nazev_modulu)
    {
        $array_nazev_modul = array();
        foreach ($this->array_moduly as $i => $modul) {
            $array_nazev_modul[$i] = $modul["nazev_modulu"];
        }

        $key = array_search($nazev_modulu, $array_nazev_modul);
        //pokud jsme modul v seznamu povolených nalezli -> vratime adresu modulu, jinak false
        if ($key !== false) {
            return $this->array_moduly[$key]["adresa_modulu"];
        } else {
            return false;
        }
    }

    private function parse_parametr()
    {
        // parametry jsou v administracni casti prenaseny primo,
        //funkce parse_parametr() by byla potøeba v pøípadì rozdìlení parametru z mod_rewrite
        return true;
    }

    /*metody pro pristup k parametrum*/
    function get_db_spojeni()
    {
        return $this->db_spojeni;
    }

    function get_db_vysledek()
    {
        return $this->db_vysledek;
    }
}


?>
