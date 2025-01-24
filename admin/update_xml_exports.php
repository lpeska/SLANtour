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
require_once "./config/config_export_sdovolena.inc.php"; //seznamy dokumentu
require_once "./classes/create_export.inc.php"; //seznamy dokumentu


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

$format = array("zakladni", "cestujeme", "invia", "sdovolena");
$typ = array("cele");

foreach ($format as $f) {
    foreach ($typ as $t) {

        create_export($f, $t);
    }
}
