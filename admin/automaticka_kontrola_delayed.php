<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

session_start();
$INC_DIR = $_SERVER["DOCUMENT_ROOT"];
require_once "$INC_DIR/global/prihlaseni_do_databaze.inc.php"; //prihlasovaci udaje do databaze
require_once "$INC_DIR/admin/config/config.inc.php";
require_once "$INC_DIR/admin/core/generic_classes.inc.php"; //abstraktni tridy
require_once "$INC_DIR/global/library_classes.inc.php"; //spolecne knihovni tridy
require_once "$INC_DIR/admin/core/database.inc.php"; //odesilani dotazu do databaze

require_once "./classes/automaticka_kontrola_delayed.inc.php"; //seznamy dokumentu


//trida pro odesilani dotazu
$database = Database::get_instance();
//pøipojení k databázi
$database->connect($db_server, $db_jmeno, $db_heslo, $db_nazev_db);

class core{//mock-up
    public $database;
    public function __construct($database) {
        $this->database = $database;
    }    
}
$core = new core($database);

$dotaz = new Automaticka_kontrola("new");


