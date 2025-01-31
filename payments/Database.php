<?php


class Database
{
    static private $instance;

    public $db_spojeni;
    private $db_vysledek;

    private static $ATTEMPTS = 10;

    private static $DB_SERVER = "127.0.0.1";
    private static $DB_JMENO = "tatraturcz001";
    private static $DB_HESLO = "dovolena50";
    private static $DB_NAZEV = "tatraturcz";

    static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    public function connect()
    {
        $this->mysqli = mysqli_connect(self::$DB_SERVER, self::$DB_JMENO, self::$DB_HESLO, self::$DB_NAZEV) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . $this->mysqli->error());
        //$this->db_vysledek = mysqli_select_db(self::$DB_NAZEV, $this->db_spojeni) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysqli_error());

        //legacy DB connection
        $this->db_spojeni = mysqli_connect(self::$DB_SERVER, self::$DB_JMENO, self::$DB_HESLO) or die("Nepodařilo se připojení k databázi - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysqli_error($GLOBALS["core"]->database->db_spojeni));
        $this->db_vysledek = mysqli_select_db($this->db_spojeni, self::$DB_NAZEV ) or die("Nepodařilo se otevření databáze - pravděpodobně se jedná o krátkodobé problémy na serveru. " . mysqli_error($GLOBALS["core"]->database->db_spojeni));


        //kodovani
        $this->mysqli->query("SET character_set_results=cp1250");
        $this->mysqli->query("SET character_set_connection=UTF8");
        $this->mysqli->query("SET character_set_client=cp1250");
    }

    public function query($query)
    {
        $return = null;

        for ($i = 0; $i < self::$ATTEMPTS; $i++) {
            $return = $this->mysqli->query($query);
            $errno = $this->mysqli->errno;
            if ($errno != 1205 or $errno != 1213) {
                break;
            }
        }

        return $return;
    }

    public function commit()
    {
        return $this->mysqli->query("COMMIT");
    }

    public function rollback()
    {
        return $this->mysqli->query("ROLLBACK");
    }

    public function start_transaction()
    {
        return $this->mysqli->query("START TRANSACTION");
    }

    public function close() {
        $this->mysqli->close();
    }

}