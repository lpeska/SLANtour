<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

require_once "../global/lib/cfg/DatabaseConfig.php";

require_once "../global/lib/db/DatabaseProvider.php";
require_once "../global/lib/db/DatabaseProviderUTF.php";
require_once "../global/lib/db/SQLQuery.php";
require_once "../global/lib/db/dao/ObjednavkaProcesDAO.php";
require_once "../global/lib/db/dao/sql/SQLBuilder.php";
require_once "../global/lib/db/dao/sql/ObjednavkaProcesSQLBuilder.php";

require_once "../global/lib/utils/CommonUtils.php";

require_once "../global/lib/model/holders/SluzbaHolder.php";
require_once "../global/lib/model/holders/ZajezdHolder.php";

require_once "../global/lib/model/sessions/ObjednavkaSessionEnt.php";
require_once "../global/lib/model/sessions/ObjednatelSessionEnt.php";
require_once "../global/lib/model/sessions/UcastnikSessionEnt.php";
require_once "../global/lib/model/sessions/SluzbaSessionEnt.php";
require_once "../global/lib/model/msgs/MessagesEnt.php";
require_once "../global/lib/model/entyties/FotoEnt.php";
require_once "../global/lib/model/entyties/ObjektEnt.php";
require_once "../global/lib/model/entyties/OrganizaceEnt.php";
require_once "../global/lib/model/entyties/UbytovaniEnt.php";
require_once "../global/lib/model/entyties/ZajezdEnt.php";
require_once "../global/lib/model/entyties/SluzbaEnt.php";
require_once "../global/lib/model/entyties/CentralniDataEnt.php";
require_once "../global/lib/model/entyties/BlackDaysEnt.php";
require_once "../global/lib/model/entyties/SlevaEnt.php";
require_once "../global/lib/model/entyties/SerialEnt.php";

require_once "../global/lib/enum/EnumZemeImage.php";
require_once "../global/lib/enum/EnumMesice.php";

require_once "./lib/iface/IObjednavkaModelForController.php";
require_once "./lib/iface/IObjednavkaModelForView.php";
require_once "./lib/iface/IObjednavkaModelZajezdObserver.php";
require_once "./lib/iface/IObjednavkaModelOsobniUdajeObserver.php";
require_once "./lib/iface/IObjednavkaModelPlatbaObserver.php";
require_once "./lib/iface/IObjednavkaModelSouhrnObserver.php";
require_once "./lib/iface/IObjednavkaModelPotvrzeniObserver.php";
require_once "./lib/iface/IObjednavkaModelAJAXObserver.php";

require_once "./lib/model/ObjednavkaModel.php";

require_once "./lib/ctrl/ObjednavkaController.php";

require_once "./lib/view/ObjednavkaView.php";
require_once "./lib/view/ObjednavkaZajezdView.php";
require_once "./lib/view/ObjednavkaOsobniUdajeView.php";
require_once "./lib/view/ObjednavkaPlatbaView.php";
require_once "./lib/view/ObjednavkaSouhrnView.php";
require_once "./lib/view/ObjednavkaPotvrzeniView.php";
require_once "./lib/view/ObjednavkaAJAXView.php";

ObjednavkaProcesDAO::init();
$model = new ObjednavkaModel();
$view = new ObjednavkaZajezdView($model);
$view = new ObjednavkaOsobniUdajeView($model);
$view = new ObjednavkaPlatbaView($model);
$view = new ObjednavkaSouhrnView($model);
$view = new ObjednavkaAJAXView($model);
$ctrl = new ObjednavkaController($model, $view);

$ctrl->dispatchRequests();