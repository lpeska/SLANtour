<?php
//note casem se zbavit core - pouziva se hlavne kvuli prihlasenemu uzivateli
require_once "./core/load_core.inc.php";

/* GLOBAL */
//cfg
require_once "../global/lib/cfg/DatabaseConfig.php";
require_once "../global/lib/cfg/ViewConfig.php";
//db
require_once "../global/lib/db/DatabaseProvider.php";
require_once "../global/lib/db/SQLQuery.php";
require_once "../global/lib/db/DBResult.php";
//model
require_once "../global/lib/model/entyties/ObjednavkaEnt.php";
require_once "../global/lib/model/entyties/OrganizaceEnt.php";
require_once "../global/lib/model/entyties/SluzbaEnt.php";
require_once "../global/lib/model/entyties/SlevaEnt.php";
require_once "../global/lib/model/entyties/PlatbaEnt.php";
require_once "../global/lib/model/entyties/AdresaEnt.php";
require_once "../global/lib/model/holders/ObjednavkaHolder.php";
require_once "../global/lib/model/holders/SluzbaHolder.php";
require_once "../global/lib/model/holders/SlevaHolder.php";
require_once "../global/lib/model/holders/PlatbaHolder.php";
require_once "../global/lib/model/holders/OrganizaceHolder.php";
require_once "../global/lib/model/holders/AdresaHolder.php";
//dao
require_once "../global/lib/db/dao/sql/SQLBuilder.php";
require_once "../global/lib/db/dao/ObratyProdejcuDAO.php";
require_once "../global/lib/db/dao/sql/ObratyProdejcuSQLBuilder.php";
//utils
require_once "../global/lib/utils/CommonUtils.php";
require_once "../global/lib/utils/ViewUtils.php";

/* LOCAL */
//model
require_once "./classes/obraty_prodejcu/lib/model/ObratyProdejcuModel.php";
require_once "./classes/obraty_prodejcu/lib/model/iface/IOPObserver.php";
//ctrl
require_once "./classes/obraty_prodejcu/lib/ctrl/ObratyProdejcuController.php";
//view
require_once "./classes/obraty_prodejcu/lib/view/ObratyProdejcuView.php";
require_once "./classes/obraty_prodejcu/lib/view/ObratyProdejcuViewPrehled.php";
require_once "./classes/obraty_prodejcu/lib/view/ObratyProdejcuViewPrehledPdf.php";

/* NEW GUI */
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

//note zbavit se tohodle - pristupu k DB nebude vic nez 1 a kdyz bude potreba upravit, upravi se ten jeden, takze muzu v klidu pouzivat DB staticky
ObratyProdejcuDAO::init();

$model = new ObratyProdejcuModel();
new ObratyProdejcuViewPrehled($model);
new ObratyProdejcuViewPrehledPdf($model);
$ctrl = new ObratyProdejcuController($model);

