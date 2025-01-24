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
require_once "../global/lib/model/filters/AbstractFilter.php";
require_once "../global/lib/model/filters/SerialFilter.php";
require_once "../global/lib/model/entyties/UserKlientEnt.php";
require_once "../global/lib/model/entyties/FakturaEnt.php";
require_once "../global/lib/model/entyties/PlatbaEnt.php";
require_once "../global/lib/model/entyties/AdresaEnt.php";
require_once "../global/lib/model/entyties/EmailEnt.php";
require_once "../global/lib/model/entyties/TelefonEnt.php";
require_once "../global/lib/model/entyties/ZemeEnt.php";
require_once "../global/lib/model/entyties/SerialTypEnt.php";
require_once "../global/lib/model/entyties/ObjednavkaEnt.php";
require_once "../global/lib/model/entyties/SlevaEnt.php";
require_once "../global/lib/model/entyties/SluzbaEnt.php";
require_once "../global/lib/model/entyties/ZajezdEnt.php";
require_once "../global/lib/model/entyties/SerialEnt.php";
require_once "../global/lib/model/entyties/ObjektEnt.php";
require_once "../global/lib/model/entyties/OrganizaceEnt.php";
require_once "../global/lib/model/holders/SlevaHolder.php";
require_once "../global/lib/model/holders/SerialHolder.php";
require_once "../global/lib/model/holders/SluzbaHolder.php";
require_once "../global/lib/model/holders/FakturaHolder.php";
require_once "../global/lib/model/holders/PlatbaHolder.php";
require_once "../global/lib/model/holders/ObjednavkaHolder.php";
require_once "../global/lib/model/holders/ZajezdHolder.php";
//dao
require_once "../global/lib/db/dao/sql/SQLBuilder.php";
require_once "../global/lib/db/dao/sql/ZemeSQLBuilder.php";
require_once "../global/lib/db/dao/sql/SerialTypSQLBuilder.php";
require_once "../global/lib/db/dao/sql/FinancniPohybySQLBuilder.php";
require_once "../global/lib/db/dao/ZemeDAO.php";
require_once "../global/lib/db/dao/SerialTypDAO.php";
require_once "../global/lib/db/dao/FinancniPohybyDAO.php";
//utils
require_once "../global/lib/utils/CommonUtils.php";
require_once "../global/lib/utils/ViewUtils.php";

/* LOCAL */
//model
require_once "./classes/financni_pohyby/lib/model/FinancniPohybyModel.php";
require_once "./classes/financni_pohyby/lib/model/FinancniPohybyModelConfig.php";
require_once "./classes/financni_pohyby/lib/model/iface/IFPObserver.php";
//ctrl
require_once "./classes/financni_pohyby/lib/ctrl/FinancniPohybyController.php";
//view
require_once "./classes/financni_pohyby/lib/view/FinancniPohybyView.php";
require_once "./classes/financni_pohyby/lib/view/FinancniPohybyViewSerialy.php";
require_once "./classes/financni_pohyby/lib/view/FinancniPohybyViewPrehled.php";
require_once "./classes/financni_pohyby/lib/view/FinancniPohybyViewPrehledPdf.php";

/* NEW GUI */
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

//note zbavit se tohodle - pristupu k DB nebude vic nez 1 a kdyz bude potreba upravit, upravi se ten jeden, takze muzu v klidu pouzivat DB staticky
SerialTypDAO::init();
ZemeDAO::init();
FinancniPohybyDAO::init();

$model = new FinancniPohybyModel();
new FinancniPohybyViewSerialy($model);
new FinancniPohybyViewPrehled($model);
new FinancniPohybyViewPrehledPdf($model);
$ctrl = new FinancniPohybyController($model);

