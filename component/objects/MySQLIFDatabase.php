<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Database
 * this is DatabaseInterface interface implementation for the MySQL database
 * @author peska
 */
 class ComponentIFDatabase extends ComponentDatabase implements DatabaseInterface{
     public $db_connection;
     private $db_result;
     static private $instance;
    //put your code here

/**
 * returns instance of the class (Singleton Design Pattern)
 * @return <type>
 */
    public static function get_instance() {
   	if( !isset(self::$instance) ) {
            self::$instance = new ComponentIFDatabase();
    	}
	return self::$instance ;
    }

    private function __construct() {
        $this->connect();
    }
/**
 * Connecting to the database
 */
    public function connect() {

        $dbServer = "127.0.0.1"; //adresa databazoveho serveru
	$dbName = "slantourcz001";		//prihlašovací jméno k databázi
	$dbPasswd = "dovolena50";			//heslo k databázi
	$dbNameOfDatabase = "slantourcz";	//název databáze

        $this->db_connection = mysqli_connect($dbServer, $dbName, $dbPasswd) or die(mysqli_error($this->db_connection,));
	$this->db_result = mysqli_select_db($this->db_connection, $dbNameOfDatabase) or die(mysqli_error($this->db_connection,));

        mysqli_query($this->db_connection,"SET character_set_results=cp1250");
        mysqli_query($this->db_connection,"SET character_set_connection=UTF8");
        mysqli_query($this->db_connection,"SET character_set_client=cp1250");

        }
/**
 * executes Query, creates and returns QueryResponse class
 * @param <type> $query SQL query
 * @return QueryResponse instance of QueryResponse class
 */
    public function executeQuery($query) {
            $query = mysqli_query($this->db_connection,$query);
            if( mysqli_errno($this->db_connection) ){
                $error = " - ".mysqli_error($this->db_connection,$this->db_connection);
                $errno = mysqli_errno($this->db_connection);
                $state = 0;
            }else{
                $error = "";
                $errno = "";
                $state = 1;

            }

            return new QueryResponse($state, $errno.$error, $query);
        }
}
?>
