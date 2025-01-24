<?php


class DatabaseProviderUTF
{
    static private $instance;

    /**
     * @var PDO
     */
    private $conn;

    /**
     * @var PDOStatement
     */
    private $stmt;

    private $lastInsertedId;

    static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new DatabaseProviderUTF();
        }
        return self::$instance;
    }

    private function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getLastInsertedId()
    {
        return $this->lastInsertedId;
    }

    public function connect()
    {
        try {
            $this->conn = new PDO('mysql:host=' . DatabaseConfig::DB_SERVER . ';dbname=' . DatabaseConfig::DB_NAZEV, DatabaseConfig::DB_JMENO, DatabaseConfig::DB_HESLO);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            $this->conn->exec("SET character_set_results=UTF8;");
            $this->conn->exec("SET character_set_connection=UTF8;");
            $this->conn->exec("SET character_set_client=UTF8;");
        } catch (PDOException $ex) {
            echo "DatabaseProvider->connect() - " . $ex->getTraceAsString();
        }
    }

    /**
     * @param $query SQLQuery
     * @return null|PDOStatement
     */
    public function query($query)
    {
        for ($i = 0; $i < DatabaseConfig::DB_ATTEMPTS; $i++) {
            $this->stmt = $this->prepare($query->sql);
            
            $result = $this->stmt->execute($query->params);
            if($result)
                break;
        }

        return $this->stmt;
    }

    /**
     * Prepare query for multiple executes @see(Database::execute()).
     * @param $sql
     * @return \PDOStatement
     */
    public function prepare($sql)
    {
        $this->stmt = $this->conn->prepare($sql);

        return $this->stmt;
    }

    /**
     * Executes prepared statemet with given params @see(Database::prepare())
     * @param $params
     * @return bool
     */
    public function execute($params)
    {
        $result = $this->stmt->execute($params);
        $this->lastInsertedId = $this->conn->lastInsertId();

        return $result;
    }

    public function commit()
    {
        $this->conn->exec("COMMIT;");
    }

    public function rollback()
    {
        $this->conn->exec("ROLLBACK;");
    }

    public function start_transaction()
    {
        $this->conn->exec("SET AUTOCOMMIT=0;");
        $this->conn->exec("START TRANSACTION;");
    }

    public function close()
    {
        $this->conn = null;
    }

}