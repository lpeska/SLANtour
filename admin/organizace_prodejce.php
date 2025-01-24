<?php
//note casem se zbavit core - pouziva se hlavne kvuli prihlasenemu uzivateli
require_once __DIR__ . "/core/load_core.inc.php";

/* GLOBAL */
//cfg
require_once __DIR__ . "/../global/lib/cfg/DatabaseConfig.php";
require_once __DIR__ . "/../global/lib/cfg/CommonConfig.php";
require_once __DIR__ . "/../global/lib/cfg/ViewConfig.php";
//db
require_once __DIR__ . "/../global/lib/db/DatabaseProvider.php";
require_once __DIR__ . "/../global/lib/db/SQLQuery.php";
require_once __DIR__ . "/../global/lib/db/DBResult.php";
//model
require_once __DIR__ . "/../global/lib/model/entyties/ObjednavkaEnt.php";
require_once __DIR__ . "/../global/lib/model/entyties/UserKlientEnt.php";
require_once __DIR__ . "/../global/lib/model/entyties/SerialEnt.php";
require_once __DIR__ . "/../global/lib/model/entyties/FakturaEnt.php";
require_once __DIR__ . "/../global/lib/model/entyties/OrganizaceEnt.php";
require_once __DIR__ . "/../global/lib/model/entyties/SluzbaEnt.php";
require_once __DIR__ . "/../global/lib/model/entyties/SlevaEnt.php";
require_once __DIR__ . "/../global/lib/model/holders/ObjednavkaHolder.php";
require_once __DIR__ . "/../global/lib/model/holders/SluzbaHolder.php";
require_once __DIR__ . "/../global/lib/model/holders/SlevaHolder.php";
//dao
require_once __DIR__ . "/../global/lib/db/dao/OrganizaceDAO.php";
require_once __DIR__ . "/../global/lib/db/dao/sql/OrganizaceSQLBuilder.php";
require_once __DIR__ . "/../global/lib/db/dao/sql/SQLBuilder.php";
//utils
require_once __DIR__ . "/../global/lib/utils/CommonUtils.php";
require_once __DIR__ . "/../global/lib/utils/ViewUtils.php";

/* LOCAL */
//model
require_once __DIR__ . "/classes/organizace/lib/model/OrganizaceModel.php";
require_once __DIR__ . "/classes/organizace/lib/model/iface/IOrganizaceObserver.php";
//ctrl
require_once __DIR__ . "/classes/organizace/lib/ctrl/OrganizaceController.php";
//view
require_once __DIR__ . "/classes/organizace/lib/view/OrganizaceView.php";
require_once __DIR__ . "/classes/organizace/lib/view/OrganizaceProdejceObjednavkaListView.php";

/* NEW GUI */
require_once __DIR__ . "/new-menu/ModulView.php";
require_once __DIR__ . "/new-menu/entities/AdminModul.php";
require_once __DIR__ . "/new-menu/entities/AdminModulHolder.php";

//note zbavit se tohodle - pristupu k DB nebude vic nez 1 a kdyz bude potreba upravit, upravi se ten jeden, takze muzu v klidu pouzivat DB staticky
OrganizaceDAO::init();

$model = new OrganizaceModel();
new OrganizaceProdejceObjednavkaListView($model);
new OrganizaceController($model);