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
 class ComponentDatabase implements DatabaseInterface{
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
            self::$instance = new ComponentDatabase();
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
	$dbName = "tatraturcz001";		//prihlašovací jméno k databázi
	$dbPasswd = "dovolena50";			//heslo k databázi
	$dbNameOfDatabase = "tatraturcz";	//název databáze

        $this->db_connection = mysqli_connect($dbServer, $dbName, $dbPasswd) or die(mysqli_error($GLOBALS["core"]->database->db_spojeni));
	$this->db_result = mysqli_select_db($this->db_connection, $dbNameOfDatabase) or die(mysqli_error($GLOBALS["core"]->database->db_spojeni));

        mysqli_query($this->db_connection,"SET character_set_results=cp1250");
        mysqli_query($this->db_connection,"SET character_set_connection=UTF8");
        mysqli_query($this->db_connection,"SET character_set_client=cp1250");


        }
/**
 * Disconnecting from the DB
 */
    public function disconnect() {
        //  mysqli_close($this->db_connection);
        }

/**
 * get next row from the MySQL Resource (if there is any)
 * @param <type> $resource MySQL resource
 * @return <type> array - row from the resource
 */
    public function getNextRow($resource){
        return mysqli_fetch_array($resource);
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
/**
 * returns last inserted ID to the database
 * @return <type> int last inserted id
 */
    public function getInsertedId(){
        return mysqli_insert_id($this->db_connection);
    }
/**
 * free Mysql resource
 * @param <type> $resuorce Mysql resource
 */
    public function freeResult($resuorce){
        if(is_resource($resource)){
            return mysqli_free_result($resource);
        }else{
            return false;
        }
    }
}
?>
