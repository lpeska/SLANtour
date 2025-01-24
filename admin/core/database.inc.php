<?php

/**
 * database.inc.php - trida pro odesílání dotazù z databáze
 * - singleton
 * - funkce pro odesílání dotazù v transakcích i bez nich
 * - kontrola "transakcnich" chyb 1205 a 1213
 * èásteènì pøevzato z http://php.vrana.cz
 */
class Database {

    //instance Database
    static private $instance;
    private $attempts = 10;
    private $queries = array();

    /**
     * Singelton
     * @return Database
     */
    static function get_instance() {
        if (!isset(self::$instance)) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    //konstruktor tridy, prazdny
    private function __construct() {
        
    }

    //pøipojení k databázi
    public function connect($db_server, $db_jmeno, $db_heslo, $db_nazev_db) {
        //pripojeni k databazi
        $this->db_spojeni = mysqli_connect($db_server, $db_jmeno, $db_heslo) or die("Nepodaøilo se pøipojení k databázi - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysqli_error($this->db_spojeni));
        $this->db_vysledek = mysqli_select_db($this->db_spojeni, $db_nazev_db) or die("Nepodaøilo se otevøení databáze - pravdìpodobnì se jedná o krátkodobé problémy na serveru. " . mysqli_error($this->db_spojeni));
        //nastaveni kodovani
        mysqli_query($this->db_spojeni, "SET character_set_results=cp1250");
        mysqli_query($this->db_spojeni, "SET character_set_connection=UTF8");
        mysqli_query($this->db_spojeni, "SET character_set_client=cp1250");
    }

    /** Spuštìní pøíkazu v rámci InnoDB transakce
     * @param start_transaction - pokud == 1, zahájím transakci
     * @param int $start_transaction
     * @return výsledek funkce $this->database->query()
     * @copyright Jakub Vrána, http://php.vrana.cz
     */
    public function transaction_query($query, $start_transaction = 0) {
        if ($start_transaction == 1) {
            $this->start_transaction();
        }

        $this->queries[] = $query;
        for ($i = 0; $i < $this->attempts; $i++) {
            $return = mysqli_query($this->db_spojeni,$query);
            $errno = mysqli_errno($this->db_spojeni);
            if ($errno != 1205) {
                break;
            }
        }
        if ($errno == 1213) {
            for (; $i < $this->attempts; $i++) {
                mysqli_query($this->db_spojeni,"START TRANSACTION");
                foreach ($this->queries as $val) {
                    for (; $i < $this->attempts; $i++) {
                        $return = mysqli_query($this->db_spojeni,$val);
                        $errno = mysqli_errno($this->db_spojeni);
                        if ($errno != 1205) {
                            break;
                        }
                    }
                    if ($i >= $this->attempts || $errno == 1213) {
                        continue 2;
                    }
                }
                break; // OK
            }
        }
        return $return;
    }

    /** Spuštìní pøíkazu v rámci InnoDB, kontrola chyb transakcí, ale pøedpokládá se jen jeden dotaz v transakci
     * @param SQL dotaz
     * @return resource výsledek funkce $this->database->query()
     */
    public function query($query) {

        for ($i = 0; $i < $this->attempts; $i++) {
            $return = mysqli_query($this->db_spojeni,$query);
            $errno = mysqli_errno($this->db_spojeni);
            if ($errno != 1205 or $errno != 1213) {
                break;
            }
        }
        return $return;
    }

    public function commit() {
        $this->queries = array();
        return mysqli_query($this->db_spojeni,"COMMIT");
    }

    public function rollback() {
        $this->queries = array();
        return mysqli_query($this->db_spojeni,"ROLLBACK");
    }

    public function start_transaction() {
        $this->queries = array();
        return mysqli_query($this->db_spojeni,"START TRANSACTION");
    }
}

?>
