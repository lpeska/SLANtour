<?php
//note casem se zbavit core - pouziva se hlavne kvuli prihlasenemu uzivateli
require_once "./core/load_core.inc.php";

/* GLOBAL */
//cfg
require_once "../global/lib/cfg/DatabaseConfig.php";
require_once "../global/lib/cfg/CommonConfig.php";
require_once "../global/lib/cfg/ViewConfig.php";
//db
require_once "../global/lib/db/DatabaseProvider.php";
require_once "../global/lib/db/SQLQuery.php";
require_once "../global/lib/db/DBResult.php";
//model
require_once "../global/lib/model/entyties/ObjednavkaEnt.php";
require_once "../global/lib/model/entyties/UserKlientEnt.php";
require_once "../global/lib/model/entyties/FakturaEnt.php";
require_once "../global/lib/model/entyties/SerialEnt.php";
require_once "../global/lib/model/entyties/OrganizaceEnt.php";
require_once "../global/lib/model/holders/FakturaHolder.php";
//filters
require_once "../global/lib/model/filters/AbstractFilter.php";
require_once "../global/lib/model/filters/PagingFilter.php";
//dao
require_once "../global/lib/db/dao/sql/SQLBuilder.php";
require_once "../global/lib/db/dao/sql/AFakturaProvizeSQLBuilder.php";
require_once "../global/lib/db/dao/AFakturaProvizeDAO.php";
//utils
require_once "../global/lib/utils/CommonUtils.php";

/* LOCAL */
//model
require_once "./classes/a_faktura_provize/lib/cfg/AFakturaProvizeModelConfig.php";
require_once "./classes/a_faktura_provize/lib/model/AFakturaProvizeModel.php";
require_once "./classes/a_faktura_provize/lib/model/iface/IAFakturaProvizeObserver.php";
//ctrl
require_once "./classes/a_faktura_provize/lib/ctrl/AFakturaProvizeController.php";
//view
require_once "./classes/a_faktura_provize/lib/view/AFakturaProvizeView.php";
require_once "./classes/a_faktura_provize/lib/view/AFakturaProvizeViewFaktury.php";

/* NEW GUI */
require_once "./new-menu/ModulView.php";
require_once "./new-menu/entities/AdminModul.php";
require_once "./new-menu/entities/AdminModulHolder.php";

//note zbavit se tohodle - pristupu k DB nebude vic nez 1 a kdyz bude potreba upravit, upravi se ten jeden, takze muzu v klidu pouzivat DB staticky
AFakturaProvizeDAO::init();

$model = new AFakturaProvizeModel();
new AFakturaProvizeViewFaktury($model);
$ctrl = new AFakturaProvizeController($model);

