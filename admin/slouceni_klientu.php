<?
session_start();

require_once "./core/load_core.inc.php";
require_once "./classes/MessageManager.php";
require_once "./classes/SlouceniKlientuView.php";
require_once "./classes/SlouceniKlientuModel.php";
require_once "./classes/SlouceniKlientuController.php";

require_once "../global/lib/utils/CommonUtils.php";

//new menu
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

SlouceniKlientuController::run();